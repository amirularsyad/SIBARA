<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login_registrasi.php");
    exit();
}
if (!isset($_SESSION['hak_akses']) || ($_SESSION['hak_akses'] !== 'Admin' && $_SESSION['hak_akses'] !== 'Super Admin')) {
    header("Location: personal/approval.php");
    exit();
}

//setup akses dan personalia
include 'koneksi.php';
$manajemen_akun_akses = 0;
if (isset($_SESSION['nama'])) {
    $namaLogin = $_SESSION['nama'];
    $sqlAkses = "SELECT manajemen_akun_akses, warna_menu FROM akun_akses WHERE nama = ? LIMIT 1";
    if ($stmt = $koneksi->prepare($sqlAkses)) {
        $stmt->bind_param("s", $namaLogin);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $rowAkses = $res->fetch_assoc()) {
            $manajemen_akun_akses = (int)$rowAkses['manajemen_akun_akses'];
            $warna_menu = $rowAkses['warna_menu'];
            $_SESSION['manajemen_akun_akses'] = $manajemen_akun_akses;
        } 
        $stmt->close();
    }
}

$showDataAkunMenu = false;

if ($_SESSION['hak_akses'] === 'Super Admin') {
    $showDataAkunMenu = true;
} else {
    if ($manajemen_akun_akses === 1) {
        $showDataAkunMenu = true;
    }elseif ($manajemen_akun_akses === 2) {
        $showDataAkunMenu = true;
    }
}

//Warna Menu
if ($warna_menu == "0" || $warna_menu === "" || is_null($warna_menu)) {
    // default (gradient)
    $bgMenu = 'linear-gradient(to bottom right, #3e02be 0%, rgb(1, 64, 159) 50%, rgb(2, 59, 159) 100%)';
} else {
    $bgMenu = $warna_menu;
}

//Warna Navbar
if ($warna_menu == "0" || $warna_menu === "" || is_null($warna_menu)) {
    // default (gradient)
    $bgNav = 'linear-gradient(to bottom right, #3e02be 0%, rgb(1, 64, 159) 50%, rgb(2, 59, 159) 100%)';
} else {
    $bgNav = $warna_menu;
}

if ($warna_menu === "0" || $warna_menu === "" || is_null($warna_menu)) {
    // default pakai gradient
    $textColorStyle = 'font-size: 3rem;
      font-weight: bold;
      background: linear-gradient(to bottom right,
        #1702d5,
        #2100a5,
        #0012ce,
        #3262ff,
        #5e74ff
      );
      background-size: 300% 300%;
      animation: gradient-shift 4s ease infinite;
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      color: transparent;';
} else {
    // kalau ada nilai warna → warna solid (hitam)
    $textColorStyle = 'font-size: 3rem;
      font-weight: bold;
      color: ' . $warna_menu . ';';
}

//notif tombol approval
$jumlah_approval_notif = require 'approval_notification_badge.php';

?>

<?php
  // --- Ambil semua kategori ---
  $kategoriLabels = [];
  $kategoriTotals = [];

  // Ambil daftar kategori
  $qKategori = mysqli_query($koneksi, "
      SELECT id, nama 
      FROM categories_broken
      ORDER BY id ASC
  ");

  $kategoriMap = [];

  while ($k = mysqli_fetch_assoc($qKategori)) {
      $kategoriLabels[] = $k['nama'];
      $kategoriMap[$k['id']] = 0; // inisialisasi total = 0
  }

  // Ambil tahun saat ini
  $tahunSekarang = date('Y');
  $ptSekarang = $_SESSION['pt'];
  if (is_array($ptSekarang)) {
      $ptSekarang = reset($ptSekarang);
  }
  $ptSekarang = trim($ptSekarang);

  // --- Ambil data BA Kerusakan ---
  $qBA = mysqli_query($koneksi, "
      SELECT kategori_kerusakan_id 
      FROM berita_acara_kerusakan
      WHERE kategori_kerusakan_id IS NOT NULL
      AND YEAR(tanggal) = '$tahunSekarang'
      AND pt = '$ptSekarang'
      AND dihapus = 0
  ");

  while ($ba = mysqli_fetch_assoc($qBA)) {
      $idKategori = $ba['kategori_kerusakan_id'];

      if (isset($kategoriMap[$idKategori])) {
          $kategoriMap[$idKategori]++;
      }
  }

  // Susun data sesuai urutan labels
  foreach ($kategoriMap as $total) {
      $kategoriTotals[] = $total;
  }

  // Mapping label chart → nama PT di database
  $ptMap = [
      'MSAL HO'   => 'PT.MSAL (HO)',
      'MSAL SITE' => 'PT.MSAL (SITE)',
      'MAPA'      => 'PT.MAPA',
      'PEAK'      => 'PT.PEAK',
      'PSAM'      => 'PT.PSAM',
      'WCJU'      => 'PT.WCJU',
      'KPP'       => 'PT.KPP'
  ];

  // Inisialisasi hasil
  $ptTotalsKerusakan = array_fill_keys(array_keys($ptMap), 0);

  // Query hitung BA per PT
  $qPT = mysqli_query($koneksi, "
      SELECT pt, COUNT(*) AS total
      FROM berita_acara_kerusakan
      WHERE pt IS NOT NULL
      AND YEAR(tanggal) = '$tahunSekarang'
      AND dihapus = 0
      GROUP BY pt
  ");

  // Masukkan hasil ke array sesuai mapping
  while ($row = mysqli_fetch_assoc($qPT)) {
      foreach ($ptMap as $label => $namaPT_DB) {
          if ($row['pt'] === $namaPT_DB) {
              $ptTotalsKerusakan[$label] = (int)$row['total'];
          }
      }
  }

  // Ambil hanya nilainya (urutan sesuai label)
  $chartDataPT = array_values($ptTotalsKerusakan);

  $ptTotalsSTA = array_fill_keys(array_keys($ptMap), 0);

  // Query hitung BA per PT
  $staPT = mysqli_query($koneksi, "
      SELECT pt, COUNT(*) AS total
      FROM ba_serah_terima_asset
      WHERE pt IS NOT NULL
      AND YEAR(tanggal) = '$tahunSekarang'
      AND dihapus = 0
      GROUP BY pt
  ");

  // Masukkan hasil ke array sesuai mapping
  while ($row = mysqli_fetch_assoc($staPT)) {
      foreach ($ptMap as $label => $namaPT_DB) {
          if ($row['pt'] === $namaPT_DB) {
              $ptTotals[$label] = (int)$row['total'];
          }
      }
  }

  // Ambil hanya nilainya (urutan sesuai label)
  $chartDataPTSTA = array_values($ptTotalsSTA);

  $ptTotalsMutasi = array_fill_keys(array_keys($ptMap), 0);

  // Query hitung BA per PT
  $staPT = mysqli_query($koneksi, "
      SELECT pt_asal, COUNT(*) AS total
      FROM berita_acara_mutasi
      WHERE pt_asal IS NOT NULL
      AND YEAR(tanggal) = '$tahunSekarang'
      AND dihapus = 0
      GROUP BY pt_asal
  ");

  // Masukkan hasil ke array sesuai mapping
  while ($row = mysqli_fetch_assoc($staPT)) {
      foreach ($ptMap as $label => $namaPT_DB) {
          if ($row['pt_asal'] === $namaPT_DB) {
              $ptTotals[$label] = (int)$row['total'];
          }
      }
  }

  // Ambil hanya nilainya (urutan sesuai label)
  $chartDataPTM = array_values($ptTotalsMutasi);
?>

<?php 
// 1
$approvalFlow = [
    'BA_KERUSAKAN' => [
        'label' => 'BA Kerusakan',
        'table' => 'berita_acara_kerusakan',
        'approvals' => [
            1 => ['user' => 'pembuat',          'status' => 'approval_1'],
            3 => ['user' => 'peminjam',         'status' => 'approval_3'],
            4 => ['user' => 'atasan_peminjam',  'status' => 'approval_4'],
            5 => ['user' => 'diketahui',        'status' => 'approval_5'],
            2 => ['user' => 'penyetujui',       'status' => 'approval_2'],
        ]
    ]
];

// 2
$namaUser = $_SESSION['nama'];

// 3
$sql = "SELECT * FROM berita_acara_kerusakan";
$query = $koneksi->query($sql);

$dataBAApproval = [];

// 4

while ($row = $query->fetch_assoc()) {

    $flow = $approvalFlow['BA_KERUSAKAN']['approvals'];

    // cari user login ada di approval ke berapa
    $approvalKeUser = null;

    foreach ($flow as $order => $cfg) {
        if (trim($row[$cfg['user']]) === $namaUser) {
            $approvalKeUser = $order;
            break;
        }
    }

    // user bukan approver → skip
    if ($approvalKeUser === null) {
        continue;
    }

    $statusFieldUser = $flow[$approvalKeUser]['status'];

    // kalau user sudah approve → skip
    if ((int)$row[$statusFieldUser] === 1) {
        continue;
    }

    /**
     * Cek approval sebelumnya
     * mundur sampai ketemu user approver yang valid
     */
    $bolehTampil = true;
    $orders = array_keys($flow);
    $index = array_search($approvalKeUser, $orders);

    for ($i = $index - 1; $i >= 0; $i--) {
        $prevOrder = $orders[$i];
        $prevUserField   = $flow[$prevOrder]['user'];
        $prevStatusField = $flow[$prevOrder]['status'];

        // lewati approver kosong / "-"
        if (trim($row[$prevUserField]) === '' || trim($row[$prevUserField]) === '-') {
            continue;
        }

        // jika approval sebelumnya belum approve → stop
        if ((int)$row[$prevStatusField] !== 1) {
            $bolehTampil = false;
        }

        break;
    }

    if (!$bolehTampil) {
        continue;
    }

    // lolos semua → simpan
    $dataBAApproval[] = [
        'jenis' => 'BA Kerusakan',
        'data'  => $row
    ];
}

function bulanRomawi($bulan)
{
    $romawi = [
        1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV',
        5 => 'V', 6 => 'VI', 7 => 'VII', 8 => 'VIII',
        9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII'
    ];
    return $romawi[(int)$bulan];
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Dashboard</title>

  <!-- Bootstrap 5 -->
    <link 
      rel="stylesheet" 
      href="assets/bootstrap-5.3.6-dist/css/bootstrap.min.css"
    />

  <!-- Bootstrap Icons -->
    <link
        rel="stylesheet"
        href="assets/icons/icons-main/font/bootstrap-icons.min.css"
    />

  <!-- AdminLTE -->
    <link 
        rel="stylesheet" 
        href="assets/adminlte/css/adminlte.css" 
    />

    <link 
        rel="stylesheet" 
        href="assets/css/all.min.css" 
    />

  <!-- OverlayScrollbars -->
    <link
        rel="stylesheet"
        href="assets/css/overlayscrollbars.min.css"
    />

  <!-- Favicon -->
    <link 
      rel="icon" type="image/png" 
      href="assets/img/logo.png"
    />

  <style>
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: #f9f9f9;
    }

    .tombol-ba-kerusakan{
      width: 50px; height: 100%;
      right: 0;
      transform: translateY(-60%);
      transition: all .3s ease-in-out !important;
      
    }

    .tombol-ba-kerusakan:hover{
      transition: all .3s ease-in-out !important;
      width: 100%;
      transform: translateY(0);
      z-index: 1;
    }

    .teks-ba-kerusakan{
      font-size: 32px;
      transform: translateY(20px);
      transition: all .3s ease-in-out;
    }

    .tombol-ba-kerusakan:hover .teks-ba-kerusakan{
      font-size: 64px;
      transform: translateY(-50px);
      transition: all .3s ease-in-out;
    }

    .tombol-ba-kerusakan-pen{
      width: 50px; height: 100%;
      right: 50px;
      transform: translateY(-60%);
      transition: all .3s ease-in-out !important;
      
    }
    .tombol-ba-kerusakan-pen:hover{
      transition: all .3s ease-in-out !important;
      width: 100%;
      transform: translateY(0)translateX(50px);
      z-index: 1;
    }
    .teks-ba-kerusakan-pen{
      font-size: 32px;
      transform: translateY(20px);
      transition: all .3s ease-in-out;
    }

    .tombol-ba-kerusakan-pen:hover .teks-ba-kerusakan-pen{
      font-size: 64px;
      transform: translateY(-50px);
      transition: all .3s ease-in-out;
    }

    nav{
      margin-bottom: 40px;
    }

    .button-navigation-bar {
      background-color: transparent;
      color: white;
      border-radius: 5px;
      border: #f9f9f9 1px solid;
      padding: 8px 12px;
      text-decoration: none;
    }

    .button-navigation-bar:hover {
      background-color: green;
      color: white;
      border: #f9f9f9 1px solid;
    }
    /* .personalia-menu{
      background:linear-gradient(135deg,#515bd4,#dd2a7b,#F58529);
      transition: all .3s ease;
    } */

    #date{
        margin-right: 10px;
    }

    #clock {
      font-size: 16px;
      color: white;
      margin-right: 20px;
    }

    .popup-box{
        display: none;
    }

    .popup-bg{
        display:none;
    }

    .aktifPopup{
        display:flex;
    }

    .app-sidebar{
            background: <?php echo $bgMenu; ?> !important;
    }

    .navbar{
            background: <?php echo $bgNav; ?> !important;
    }

    h2, h3 {
      color: #2c3e50;
      text-align: center;
      margin-bottom: 10px;
    }

    .app-main{
      display: flex;
      align-items: center;
      padding-top: 40px;
    }

    .bi-check-circle{
      color: green;
      font-size: 50px;
    }
    form {
      background: #ffffff;
      width: 95%;
      padding: 25px 30px;
      border-radius: 10px;
    }

    .form-section {
      margin-bottom: 20px;
    }

    label {
      display: flex;
      align-items: center;
      margin: 15px 0 8px 0;
      padding: 3px 0px;
      font-weight: 500;
      color:rgba(0, 0, 0, 1);
      width:max-content;
      border-radius: 15px;
      font-size: small;
    }

    input, textarea {
      width: 100%;
      padding: 8px 10px;
      border: 1px solid   #ccc;
      border-radius: 5px;
      box-sizing: border-box;
      background-color: whitesmoke;
      font-size: 14px;
    }

    textarea {
      resize: vertical;
      min-height: 60px;
    }

    .gambar-wrapper {
      display: flex;
      flex-direction: column;
      max-width: 100%;
      margin-bottom: 5px;
    }

    .gambar-wrapper img {
      margin-top: 5px;
    }


    input[type="submit"] {
      background: #2980b9;
      color: white;
      padding: 10px 20px;
      border: none;
      font-size: 16px;
      cursor: pointer;
      border-radius: 5px;
      margin-top: 20px;
      
    }

    input[type="submit"]:hover {
      background: #1c5980;
    }
    .custom-popup-autograph {
        height: max-content;
        align-self: center;
        z-index: 999;
        width: max-content;
        min-width: 500px;
        left: 35.5%;
        top: 30vh;
    }
    /* .grid-2 {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 20px;
    } */

    .custom-chart-2{
      flex: 1;
    }
    .custom-chart-2point1{
      flex: 2;
    }

    .custom-chart-2, .custom-chart-2point1{
      margin: 0px 7px 15px 7px;
    }

    .chart-wrapper{
      position: relative;
      width: 100%;
      height: 100%;
  }

    .custom-chart-3,.custom-chart-4, .custom-chart-5, .custom-chart-6{
      width: 49%;
      height: 300px;
      margin: 0px 7px 15px 7px;
    }

    .akun-info{
      right:-300px;
      opacity: 0;
    }

    .aktif{
    right: 0;
    opacity: 1;
    transition: all .3s ease-in-out;
    }

    .display-state{
        display:none;
    }

    .bi-list, .bi-arrows-fullscreen, .bi-fullscreen-exit {
            color: #fff !important;
    }
    .custom-footer{
      background-color: white;
    }
  </style>

  <style>/* responsive */
    @media (max-width: 1024px) {
      /* .custom-nav-link{
        display: none !important;
      } */
      .custom-footer{
        position:absolute !important;
        bottom: 0;
        width: 100vw;
      }
      .custom-main{
        padding-bottom: 100px;
        height: max-content;
        padding-top: 10px;
      }
      .custom-row{
        height: max-content !important;
        padding: 0px !important;
        margin:0px !important;
      }
      .custom-chart-1,.custom-chart-2,.custom-chart-3{
        width: 100%;
        padding: 1%;
      }
      .custom-card{
        width: 50%;
        height: 25vw !important;
        padding: 3px;
      }
      .custom-card-links{
        z-index: 2 !important;
        transform: translateY(-67%);
      }
      .custom-card-links:hover .teks-ba-kerusakan{
        transform: translateY(-20%);
      }
      .custom-card-body{
        padding: 0 !important;
        padding-top: 5px !important;
        justify-content: center !important;
        gap: 5px;
      }
      .custom-card-title-text{
        font-size: .8rem;
      }
      .custom-number{
        font-size: 48px !important;
      }
      .custom-card-number{
        width: 30% !important;
        height: 75% !important;
        padding: 5px !important;
        margin-bottom: 5px !important;
      }
    }
    @media (max-width: 450px) {
      #date{
        display: none;
      }

      #clock {
        display: none;
      }
      .custom-bak-card-body{
        height: max-content !important;
      }
      .custom-chart-2, .custom-chart-3, .custom-chart-4,.custom-chart-5,.custom-chart-6{
        width: 100%;
        padding-left: 0;
        padding-right: 0;
      }
      .custom-chart-3, .custom-chart-3 .card,
      .custom-chart-4, .custom-chart-4 .card,
      .custom-chart-5, .custom-chart-5 .card,
      .custom-chart-6, .custom-chart-6 .card{
        height: max-content !important;
      }
    }
    #res-fullscreen{
      display: none;
    }

    /* Responsive Tablet (termasuk iPad lama) */
    @media only screen 
    and (min-device-width: 768px) 
    and (max-device-width: 1024px) {
      .custom-footer {
        position: absolute !important;
        bottom: 0;
        width: 100%; /* ganti dari 100vw */
      }
      .custom-main {
        padding-bottom: 100px;
        height: auto; /* ganti dari max-content */
        padding-top: 10px;
      }
      .custom-row {
        height: auto !important; /* ganti dari max-content */
        padding: 0 !important;
        margin: 0 !important;
      }
      .custom-chart-1{
        width: 100%;
        padding: 1%;
      }
      .custom-chart-2{
        width: 100%;
        padding: 1%;
      }
      .custom-card {
        width: 50%;
        height: auto !important; /* jangan pakai vw di Safari lama */
        padding: 3px;
      }
      .custom-card-links {
        z-index: 2 !important;
        /* hati-hati translateY % di Safari lama */
        transform: translateY(-60%);
      }
      .custom-card-links:hover .teks-ba-kerusakan {
        transform: translateY(-20%);
      }
      .custom-card-body {
        padding: 5px 0 0 0 !important;
        justify-content: center !important;
        gap: 5px;
      }
      .custom-card-title-text {
        font-size: .8rem;
      }
      .custom-number {
        font-size: 48px !important;
      }
      .custom-card-number {
        width: 30% !important;
        height: auto !important;
        padding: 5px !important;
        margin-bottom: 5px !important;
      }
    }

    /* Responsive HP kecil */
    @media (max-width: 450px) {
      #date, #clock {
        display: none;
      }
      .chart-wrapper{
        height: 260px;      /* mobile */
      }
      .custom-footer p{
        font-size: 10px;
      }
    }

    #res-fullscreen {
      display: none;
    }
  </style>

  <style>
    /* Portrait */
    @media only screen 
    and (min-device-width: 768px) 
    and (max-device-width: 768px) 
    and (orientation: portrait) {
      /* CSS khusus iPad 2 portrait */
    }

    /* Landscape */
    @media only screen 
    and (min-device-width: 1024px) 
    and (max-device-width: 1024px) 
    and (orientation: landscape) {
      /* CSS khusus iPad 2 landscape */
    }
  </style>

  <style> /* gradient-bg 24s */
    .background-gradasi-biru-ungu{
      background: linear-gradient(to bottom right,
        #1702d5,   
        #3953f9,   
        #0012ce,   
        #3262ff,   
        #5e74ff    
      );
      background: -webkit-linear-gradient(to bottom right, #1702d5, #3953f9, #0012ce, #3262ff, #5e74ff);
      background-size: 300% 300%;
      animation: gradient-shift 24s ease infinite;
    }
        @keyframes gradient-shift {
      0% {
        background-position: 0% 50%;
        
      }
      25% {
        background-position: 100% 50%;
      }
      50% {
        background-position: 100% 0%;
      }
      75% {
        background-position: 50% 0%;
      }
      100% {
        background-position: 0% 50%;
      }
    }
  </style>

  <style>/* Scroll */
    .scroll-container {
      height: 100vh;          /* tinggi penuh layar */
      overflow-y: scroll;     /* scroll tetap aktif */
      -ms-overflow-style: none;  /* IE dan Edge */
      scrollbar-width: none;     /* Firefox */
    }

    .scroll-container::-webkit-scrollbar {
      display: none;            /* Chrome, Safari, Opera */
    }
    
  </style>

  <style>/* gradient-text 4s */
    .gradient-text {
      background: inherit;
      <?php echo $textColorStyle; ?>
    }
      @keyframes gradient-shift {
      0% {
        background-position: 0% 50%;
      }
      25% {
        background-position: 100% 50%;
      }
      50% {
        background-position: 100% 0%;
      }
      75% {
        background-position: 50% 0%;
      }
      100% {
        background-position: 0% 50%;
      }
    }
  </style>

</head>

<body class="layout-fixed sidebar-expand-lg bg-body-tertiary overflow-x-hidden">

  <div class="app-wrapper position-relative">
    
    <!--begin::Header-->
    <nav class="app-header navbar navbar-expand bg-body sticky-top" style="margin-bottom: 0; z-index: 5;">
        <!--begin::Container-->
        <div class="container-fluid">
        <!--begin::Start Navbar Links-->
        <ul class="navbar-nav">
            <li class="nav-item">
            <a class="custom-nav-link nav-link" data-lte-toggle="sidebar" href="#" role="button">
                <i class="bi bi-list"></i>
            </a>
            </li>
            
        </ul>
        <!--end::Start Navbar Links-->
        <!--begin::End Navbar Links-->
        <ul class="navbar-nav ms-auto">
            <!--begin::Fullscreen Toggle-->
            <li id="res-fullscreen" class="nav-item">
            <a class="nav-link" href="#" data-lte-toggle="fullscreen">
                <i data-lte-icon="maximize" class="bi bi-arrows-fullscreen"></i>
                <i data-lte-icon="minimize" class="bi bi-fullscreen-exit" style="display: none"></i>
            </a>
            </li>
            <!--end::Fullscreen Toggle-->
            <!--begin::Clock-->
            <li class="nav-item pt-2">
                <span id="date" class="text-white fw-bold" style="min-width: 120px; text-align: right;"></span>
                <span id="clock" class="text-white fw-bold" style="min-width: 75px; text-align: right;"></span>
            </li>
            <!--end::Clock-->

            <li class="personalia-menu nav-item me-3 rounded">
              <i id="personaliaBtn" class="bi bi-brush-fill btn fw-bold text-white" style="box-shadow:none;"></i>
            </li>
            <!--begin::Akun-->
            <div class="ms-auto me-2 position-relative">
              <i id="tombolAkun" class="bi bi-person-circle btn fw-bold text-white border border-white"></i>
              <div id="akunInfo" class="akun-info card position-absolute bg-white p-2 display-state" style="width:300px;height:160px;top:50px;right:0;transition:all .2s ease-in-out">
                <div class=" d-flex p-3 align-items-center justify-content-around border-bottom">
                  <i class="bi bi-person-circle text-primary" style="font-size:44px"></i>
                  <div class="">
                    <h6><?= htmlspecialchars($_SESSION['nama']) ?></h6>
                    <h6 class="" style="color:gray"><?= htmlspecialchars($_SESSION['hak_akses']) ?></h6>
                  </div>
                </div>
                    <a href="logout.php" class="btn btn-outline-danger fw-bold d-flex ps-3 gap-2 mt-2" onclick="return confirm('Yakin ingin logout?')" title="Logout">
                      <i class="bi bi-box-arrow-right fw-bolder"></i><p class="m-0">Logout</p>
                  </a>
              </div>
            </div>
            
        </ul>
        <!--end::End Navbar Links-->
        </div>
        <!--end::Container-->
    </nav>
    <!--end::Header-->

<?php //Buka nanti untuk user lainnya
// if (session_status() == PHP_SESSION_NONE) session_start();
// include 'koneksi.php'; // atau ../koneksi.php tergantung path

// $namaLogin = $_SESSION['nama'] ?? '';

// $jumlah_pending = 0;
// if (!empty($namaLogin)) {
//     $stmt = $koneksi->prepare("SELECT COUNT(*) AS jumlah FROM berita_acara_kerusakan WHERE approval_2 = 0 AND user = ?");
//     $stmt->bind_param("s", $namaLogin);
//     $stmt->execute();
//     $result = $stmt->get_result();
//     if ($row = $result->fetch_assoc()) {
//         $jumlah_pending = $row['jumlah'];
//     }
//     $stmt->close();
// }
?>
<?php
if (session_status() == PHP_SESSION_NONE) session_start();
include 'koneksi.php'; 
$jumlah_pending = 0;

if (isset($_SESSION['nama']) && $_SESSION['nama'] === 'Tedy Paronto') {
    $query = "SELECT COUNT(*) AS jumlah FROM berita_acara_kerusakan WHERE approval_2 = 0 AND dihapus = 0";
    $result = mysqli_query($koneksi, $query);
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        // $jumlah_pending = $row['jumlah'] ?? 0;
        $jumlah_pending = isset($row['jumlah']) ? $row['jumlah'] : 0;

    }
}
?>
    <!--Awal::Sidebar-->
    <aside class="app-sidebar shadow" data-bs-theme="dark">
        <div class="sidebar-brand" style="border:none;">
        <a href="" class="brand-link">
            <img
            src="assets/img/logo.png"
            alt="MSAL Logo"
            class="brand-image opacity-75 shadow"
            />
            <span class="brand-text fw-bold">SIBARA</span>
        </a>
        </div>
        <div class="sidebar-wrapper">
        <nav class="mt-2">
            <ul
            class="nav sidebar-menu flex-column"
            data-lte-toggle="treeview"
            role="menu"
            data-accordion="false"
            >
            <li class="nav-item">
                <a href="#" class="nav-link" aria-disabled="true">
                <i class="bi bi-house-fill text-white"></i>
                <p class="text-white">
                    Dashboard
                </p>
                </a>
            </li>
            <?php if ($_SESSION['hak_akses'] === 'Admin' || $_SESSION['hak_akses'] === 'Super Admin'): ?>
            <li class="nav-header">
                LIST BERITA ACARA
            </li>
            <!-- List BA Kerusakan -->
            <li class="nav-item">
                <a href="ba_kerusakan-fix/ba_kerusakan.php" class="nav-link">
                <i class="nav-icon bi bi-newspaper"></i>
                <p>
                    BA Kerusakan
                </p>
                </a>
            </li>

            <?php if ($ptSekarang == "PT.MSAL (HO)"){ ?>
            <!-- List BA Pengembalian -->
            <!-- <li class="nav-item">
                <a href="ba_pengembalian/ba_pengembalian.php" class="nav-link">
                <i class="nav-icon bi bi-newspaper"></i>
                <p>
                    BA Pengembalian
                </p>
                </a>
            </li> -->
            <?php } ?> 

            <li class="nav-item">
                <a href="ba_pemutihan/ba_pemutihan.php" class="nav-link" aria-disabled="true">
                    <i class="nav-icon bi bi-newspaper"></i>
                    <p>BA Pemutihan</p>
                </a>
            </li>

            <li class="nav-item">
                <a href="ba_pengembalian/ba_pengembalian.php" class="nav-link" aria-disabled="true">
                    <i class="nav-icon bi bi-newspaper"></i>
                    <p>BA Pengembalian</p>
                </a>
            </li>

            <?php if ($ptSekarang == "PT.MSAL (HO)"){ ?>
            <!-- List BA Serah Terima -->
            <li class="nav-item">
                <a href="ba_serah-terima-asset/ba_serah-terima-asset.php" class="nav-link">
                <i class="nav-icon bi bi-newspaper"></i>
                <p>
                    BA Serah Terima Asset Inventaris
                </p>
                </a>
            </li>
            <?php } ?>
            <!-- <li class="nav-item">
                <a href="#" class="nav-link">
                <i class="nav-icon bi bi-newspaper"></i>
                <p>
                    BA Peminjaman
                </p>
                </a>
            </li> -->

            <?php 
            //if ($ptSekarang == "PT.MSAL (HO)" || $ptSekarang == "PT.MSAL (SITE)"){ 
              ?>
            <li class="nav-item position-relative">
                <a href="ba_mutasi/ba_mutasi.php" class="nav-link">
                <i class="nav-icon bi bi-newspaper"></i>
                <p>
                    BA Mutasi
                </p>
                </a>
            </li>
            <?php 
            //} 
            ?>
            <!-- <li class="nav-header">
                LAIN LAIN
            </li>
            <li class="nav-item">
                <a href="#" class="nav-link">
                <i class="nav-icon bi bi-newspaper"></i>
                <p>
                    List Lainnya
                    <i class="nav-arrow bi bi-chevron-right"></i>
                </p>
                </a>
                <ul class="nav nav-treeview">
                    <li class="nav-item">
                        <a href="#" class="nav-link">
                        <i class="nav-icon bi bi-pc-display"></i>
                        <p>
                            Job Order
                        </p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#" class="nav-link">
                        <i class="nav-icon bi bi-pc-display"></i>
                        <p>
                            Work Order
                        </p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#" class="nav-link">
                        <i class="nav-icon bi bi-file-earmark-text-fill"></i>
                        <p>
                            Pengajuan Dokumen
                        </p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#" class="nav-link">
                        <i class="nav-icon bi bi-stickies-fill"></i>
                        <p>
                            Notulensi
                        </p>
                        </a>
                    </li>
                </ul>
            </li> -->
            
            
            <?php endif; ?>
            <li class="nav-header">
                USER
            </li>
            <!-- <?php if ($_SESSION['hak_akses'] === 'Admin'): ?>
            <li class="nav-item">
                <a href="personal/status.php" class="nav-link">
                <i class="nav-icon bi bi-clipboard2-fill"></i>
                <p>
                    Status Approval BA
                </p>
                </a>
            </li>
            <?php endif; ?> -->
            <li class="nav-item position-relative">
                <a href="personal/approval.php" class="nav-link">
                <i class="nav-icon bi bi-clipboard2-check"></i>
                <p>
                    Approve BA
                    <?php if ($jumlah_pending > 0): ?>
                        <span class="badge bg-danger rounded-5 ms-1"><?= $jumlah_pending ?></span>
                    <?php endif; ?>
                </p>
                <?php if ($jumlah_approval_notif > 0): ?>
                <span class="position-absolute translate-middle badge rounded-pill bg-danger" style="right: 0;top:20px">
                <?= $jumlah_approval_notif ?>
                </span>
                <?php endif; ?>
                </a>
            </li>
            <!-- <li class="nav-item">
                <a href="personal/riwayat.php" class="nav-link">
                <i class="nav-icon bi bi-clipboard2-data"></i>
                <p>
                    Riwayat Approval
                </p>
                </a>
            </li> -->
            <?php if ($showDataAkunMenu): ?>
            <li class="nav-header">
                MASTER
            </li>
            <li class="nav-item">
                <a href="master/data_akun/tabel.php" class="nav-link">
                <i class="nav-icon bi bi-person-circle"></i>
                <p>
                    Data Akun
                </p>
                </a>
            </li>
            <?php endif; ?>

            <?php if ($_SESSION['hak_akses'] === 'Admin'): ?>
            <!-- <li class="nav-header">
                SUPER ADMIN LOGIN
            </li>
            <li class="nav-item">
                <form action="proses_login_registrasi.php" method="post" class="w-100 p-2 pt-0 m-0" style="background-color: transparent;">
                <div class="mb-3">
                    <label for="username_l" class="form-label" style="display: none;">Username</label>
                    <input type="text" name="username" autocomplete="on" class="form-control bg-white" id="username_l" placeholder="Username" required>
                </div>
                <div class="mb-3">
                    <label for="passwords_l" class="form-label" style="display: none;">Password</label>
                    <input type="password" name="passwords" autocomplete="on" class="form-control bg-white" id="passwords_l" placeholder="Password" required>
                </div>
                <button type="submit" name="login" class="btn btn-primary w-100 fw-bold fs-5 " style="height: 50px;">Login</button>
                </form>
            </li> -->
            <?php endif; ?>
            </ul>
              
            </ul>

        </nav>
        </div>
    </aside>
    <!--Akhir::Sidebar-->
      <?php if ($_SESSION['hak_akses'] === 'Admin' || $_SESSION['hak_akses'] === 'Super Admin'): ?>

    <?php
    include 'koneksi.php'; 
    
    // Ambil tanggal sekarang
    $tanggalSekarang = date('Y-m-d');
    $bulanIni = date('m');
    $tahunIni = date('Y');

    // Hitung total BA bulan ini
    $queryBulan = mysqli_query($koneksi, "SELECT COUNT(*) as total_bulan FROM berita_acara_kerusakan WHERE MONTH(tanggal) = '$bulanIni' AND YEAR(tanggal) = '$tahunIni' AND dihapus = 0");
    $dataBulan = mysqli_fetch_assoc($queryBulan);
    $totalBulan = $dataBulan['total_bulan'];

    $queryBulan2 = mysqli_query($koneksi, "SELECT COUNT(*) as total_bulan FROM berita_acara_pengembalian WHERE MONTH(tanggal) = '$bulanIni' AND YEAR(tanggal) = '$tahunIni' AND dihapus = 0");
    $dataBulan2 = mysqli_fetch_assoc($queryBulan2);
    $totalBulan2 = $dataBulan2['total_bulan'];

    $queryBulan3 = mysqli_query($koneksi, "SELECT COUNT(*) as total_bulan FROM ba_serah_terima_asset WHERE MONTH(tanggal) = '$bulanIni' AND YEAR(tanggal) = '$tahunIni' AND dihapus = 0");
    $dataBulan3 = mysqli_fetch_assoc($queryBulan3);
    $totalBulan3 = $dataBulan3['total_bulan'];

    $queryBulan4 = mysqli_query($koneksi, "SELECT COUNT(*) as total_bulan FROM berita_acara_mutasi WHERE MONTH(tanggal) = '$bulanIni' AND YEAR(tanggal) = '$tahunIni' AND dihapus = 0");
    $dataBulan4 = mysqli_fetch_assoc($queryBulan4);
    $totalBulan4 = $dataBulan4['total_bulan'];

    // Hitung total BA tahun ini
    $queryTahun = mysqli_query($koneksi, "SELECT COUNT(*) as total_tahun FROM berita_acara_kerusakan WHERE YEAR(tanggal) = '$tahunIni' AND dihapus = 0");
    $dataTahun = mysqli_fetch_assoc($queryTahun);
    $totalTahun = $dataTahun['total_tahun'];

    $queryTahun2 = mysqli_query($koneksi, "SELECT COUNT(*) as total_tahun FROM berita_acara_pengembalian WHERE YEAR(tanggal) = '$tahunIni' AND dihapus = 0");
    $dataTahun2 = mysqli_fetch_assoc($queryTahun2);
    $totalTahun2 = $dataTahun2['total_tahun'];

    $queryTahun3 = mysqli_query($koneksi, "SELECT COUNT(*) as total_tahun FROM ba_serah_terima_asset WHERE YEAR(tanggal) = '$tahunIni' AND dihapus = 0");
    $dataTahun3 = mysqli_fetch_assoc($queryTahun3);
    $totalTahun3 = $dataTahun3['total_tahun'];

    $queryTahun4 = mysqli_query($koneksi, "SELECT COUNT(*) as total_tahun FROM berita_acara_mutasi WHERE YEAR(tanggal) = '$tahunIni' AND dihapus = 0");
    $dataTahun4 = mysqli_fetch_assoc($queryTahun4);
    $totalTahun4 = $dataTahun4['total_tahun'];

    // Hitung total semua BA
    $queryTotal = mysqli_query($koneksi, "SELECT COUNT(*) as total_semua FROM berita_acara_kerusakan WHERE dihapus = 0");
    $dataTotal = mysqli_fetch_assoc($queryTotal);
    $totalSemua = $dataTotal['total_semua'];

    $queryTotal2 = mysqli_query($koneksi, "SELECT COUNT(*) as total_semua FROM berita_acara_pengembalian");
    $dataTotal2 = mysqli_fetch_assoc($queryTotal2);
    $totalSemua2 = $dataTotal2['total_semua'];

    $queryTotal3 = mysqli_query($koneksi, "SELECT COUNT(*) as total_semua FROM ba_serah_terima_asset");
    $dataTotal3 = mysqli_fetch_assoc($queryTotal3);
    $totalSemua3 = $dataTotal3['total_semua'];

    $queryTotal4 = mysqli_query($koneksi, "SELECT COUNT(*) as total_semua FROM berita_acara_mutasi");
    $dataTotal4 = mysqli_fetch_assoc($queryTotal4);
    $totalSemua4 = $dataTotal4['total_semua'];

    // Nama bulan (Indonesia)
    $bulanNama = date('F');
    $bulanNamaID = [
        'January' => 'Januari',
        'February' => 'Februari',
        'March' => 'Maret',
        'April' => 'April',
        'May' => 'Mei',
        'June' => 'Juni',
        'July' => 'Juli',
        'August' => 'Agustus',
        'September' => 'September',
        'October' => 'Oktober',
        'November' => 'November',
        'December' => 'Desember'
    ];
    $bulanNamaSekarang = $bulanNamaID[$bulanNama];
    ?>
      <?php endif; ?>
    <?php ?>
    <!--Awal::Main Content-->
    <main class="custom-main app-main ms-5 me-5">
      <h1 class="gradient-text">Dashboard</h1>
      <?php if ($_SESSION['hak_akses'] === 'Admin' || $_SESSION['hak_akses'] === 'Super Admin'): ?>

      
      <div class="custom-row row w-100 d-flex pt-3 mb-3 flex-wrap" style="height: max-content;">
        <?php 
        
        ?>
        <?php
        include 'koneksi.php'; // pastikan path benar

        // Siapkan arrays bulan dan hasil
        $labels = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
        $dataKerusakan = array_fill(0, 12, 0);
        $dataPengembalian = array_fill(0, 12, 0);
        $dataSerahTerima  = array_fill(0, 12, 0);

        // Ambil data jumlah untuk setiap bulan di tahun ini
        $tahunIni = date('Y');

        // ==================== BA KERUSAKAN ====================
        $stmt = $koneksi->prepare("
          SELECT MONTH(tanggal) AS m, COUNT(*) AS total
          FROM berita_acara_kerusakan
          WHERE YEAR(tanggal) = ? AND dihapus = 0
          GROUP BY MONTH(tanggal)
        ");
        $stmt->bind_param("s", $tahunIni);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $bulanIndex = intval($row['m']) - 1; // Jan = index 0
            $dataKerusakan[$bulanIndex] = intval($row['total']);
        }

        $stmt->close();
        // ==================== BA PENGEMBALIAN ====================
        $stmt = $koneksi->prepare("
          SELECT MONTH(tanggal) AS m, COUNT(*) AS total
          FROM berita_acara_pengembalian
          WHERE YEAR(tanggal) = ?
          GROUP BY MONTH(tanggal)
        ");
        $stmt->bind_param("s", $tahunIni);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $bulanIndex = intval($row['m']) - 1;
            $dataPengembalian[$bulanIndex] = intval($row['total']);
        }
        $stmt->close();

        // ==================== BA SERAH TERIMA ASSET ====================
        $stmt = $koneksi->prepare("
          SELECT MONTH(tanggal) AS m, COUNT(*) AS total
          FROM ba_serah_terima_asset
          WHERE YEAR(tanggal) = ?
          GROUP BY MONTH(tanggal)
        ");
        $stmt->bind_param("s", $tahunIni);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $bulanIndex = intval($row['m']) - 1;
            $dataSerahTerima[$bulanIndex] = intval($row['total']);
        }
        $stmt->close();
        ?>
        
        <!-- <div class="custom-chart-1 col-8">
          <div class="card card-primary" style="width: 100%; height: 100%;">
          <div class="card-body">
            <div class="chart">
              <canvas id="baGab" height="300px"></canvas>
            </div>
          </div>
          </div>
        </div> -->
        
        <div class="d-flex flex-wrap">

          <div class="custom-chart-2" style="height: 400px;">
            <div class="card card-primary" style="width: 100%; height: 100%">
            <div class="card-body">
              <div class="chart">
                <canvas id="baKerusakan"></canvas>
              </div>
            </div>
            </div>
          </div>

          <!-- off -->
          <div class="custom-chart-2point1 d-none" style="height: 400px;">
            <div class="card card-primary" style="width: 100%; height: 100%">
            <div class="card-body">
              <h5 class="mb-3 fw-bold">BA yang perlu anda approve</h5>

                <div class="table-responsive">
                  <table class="table table-bordered table-hover align-middle">
                    <thead class="table-light">
                      <tr class="text-center">
                        <th style="width: 50px;">No</th>
                        <th>Jenis BA</th>
                        <th>Kode BA</th>
                        <th style="width: 260px;">Action</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php if (empty($dataBAApproval)) : ?>
                        <tr>
                          <td colspan="4" class="text-center text-muted">
                            Tidak ada BA yang perlu anda approve
                          </td>
                        </tr>
                      <?php else : ?>
                        <?php $no = 1; ?>
                        <?php foreach ($dataBAApproval as $item) : ?>

                          <?php
                            $row = $item['data'];

                            // ===== Generate Kode BA =====
                            $nomorBA = $row['nomor_ba'];

                            $ptLokasi = ($row['pt'] === 'PT.MSAL (HO)') ? 'HO' : 'SITE';

                            $tgl = date_create($row['tanggal']);
                            $bulanRomawi = bulanRomawi(date_format($tgl, 'n'));
                            $tahun = date_format($tgl, 'Y');

                            $kodeBA = "{$nomorBA}/MIS-{$ptLokasi}/{$bulanRomawi}/{$tahun}";
                          ?>

                          <tr>
                            <td class="text-center"><?= $no++; ?></td>
                            <td><?= htmlspecialchars($item['jenis']); ?></td>
                            <td><?= htmlspecialchars($kodeBA); ?></td>
                            <td class="text-center">
                              <div 
                                  class='tombolAutographPopup custom-btn-action btn btn-warning btn-sm'  
                                  data-jenis=''
                                  data-id=''
                                  data-tanggal=''
                                  data-nomor=''
                                  data-approval-col=''
                                  data-autograph-col=''
                                  data-nama=''
                                  data-peran=''
                                  data-picture=''
                                  data-user-picture=''
                              >
                                  <i class='bi bi-pencil-square'></i>
                              </div>
                              <?php if ($item['jenis'] === 'BA Kerusakan') : ?>
                                <a 
                                  href="personal/detail_barang_kerusakan.php?id=<?= (int)$row['id']; ?>" 
                                  class="btn btn-secondary btn-sm"
                                  title="Detail Surat"
                                >
                                  <i class="bi bi-eye-fill"></i>
                                </a>
                              <?php endif; ?>

                              <?php if ($item['jenis'] === 'BA Kerusakan') : ?>
                                <a 
                                  href="personal/surat_output_kerusakan.php?id=<?= (int)$row['id']; ?>" 
                                  target="_blank"
                                  class="btn btn-primary btn-sm"
                                  title="Lihat Surat"
                                >
                                  <i class="bi bi-file-earmark-text-fill"></i>
                                </a>
                              <?php else : ?>
                                <a 
                                  href="#" 
                                  class="btn btn-primary btn-sm"
                                  title="Lihat Surat"
                                >
                                  <i class="bi bi-file-earmark-text-fill"></i>
                                </a>
                              <?php endif; ?>

                            </td>
                          </tr>

                        <?php endforeach; ?>
                      <?php endif; ?>
                    </tbody>

                  </table>
                </div>

            </div>
            </div>
          </div>

        </div>

        
        <div class="d-flex flex-wrap">
          
          <div class="custom-chart-3">
            <div class="card card-primary" style="width: 100%; height: 100%">
            <div class="card-body custom-bak-card-body">
              <div class="chart chart-wrapper">
                <canvas id="chartBaKerusakan"></canvas>
              </div>
            </div>
            </div>
          </div>

          <div class="custom-chart-5
          <?php if ($ptSekarang !== 'PT.MSAL (HO)'): ?>
            d-none
          <?php endif; ?>
          ">
            <div class="card card-primary" style="width: 100%; height: 100%">
            <div class="card-body">
              <div class="chart chart-wrapper">
                <canvas id="chartBaSTAsset" height="100%"></canvas>
              </div>
            </div>
            </div>
          </div>

          <div class="custom-chart-6">
            <div class="card card-primary" style="width: 100%; height: 100%">
            <div class="card-body">
              <div class="chart chart-wrapper">
                <canvas id="chartBaMutasi" height="100%"></canvas>
              </div>
            </div>
            </div>
          </div>

        </div>
        

        <div class="custom-chart-4 d-none">
          <div class="card card-primary" style="width: 100%; height: 100%">
          <div class="card-body">
            <div class="chart chart-wrapper">
              <canvas id="chartBaPengembalian" height="100%"></canvas>
            </div>
          </div>
          </div>
        </div>

        

        

      </div>

      <div class="custom-row row w-100 mb-2" style="height: 250px;">

        <div class="custom-card col-4 h-100">
          <div class="position-relative card background-gradasi-biru-ungu h-100 w-100 overflow-hidden" >
            <div class="card-header" style="z-index: 2;background-color: transparent;">
              <h3 class="card-title text-white">BA Kerusakan Aset</h3>
            </div>
            <a href="ba_kerusakan-fix/ba_kerusakan.php" class="custom-card-links position-absolute card-header border-0 d-flex justify-content-center btn btn-primary tombol-ba-kerusakan" style="box-shadow: none;">
              <i class="bi bi-newspaper text-white position-absolute teks-ba-kerusakan" style="bottom: 20px;"></i>
            </a>
            <!-- <a href="ba_kerusakan-fix/ba_kerusakan.php" class="custom-card-links position-absolute card-header border-0 d-flex justify-content-center btn btn-primary tombol-ba-kerusakan" style="box-shadow: none;">
              <i class="bi bi-newspaper text-white position-absolute teks-ba-kerusakan" style="bottom: 20px;"></i>
            </a> -->
            <!-- <a href="ba_kerusakan-fix/form_input_ba_kerusakan.php" class="position-absolute card-header border-0 d-flex justify-content-center btn btn-primary tombol-ba-kerusakan-pen" style="box-shadow: none;">
              <i class="bi bi-feather text-white position-absolute teks-ba-kerusakan-pen" style="bottom: 20px;"></i>
            </a> -->
            <div class="custom-card-body card-body w-100 p-0 d-flex justify-content-around align-items-center pt-5">
              <div class="custom-card-number card p-0 d-flex flex-column justify-content-around" style="height: 90%;width: 130px; background-color: transparent;  box-shadow: none;">
                <h2 class="custom-number text-white p-0 m-0" style="font-size: 64px;"><?php echo $totalBulan; ?></h2>
                <h3 class="custom-card-title-text card-title text-white">Bulan <?php echo $bulanNamaSekarang; ?></h3>
              </div>
              <div class="custom-card-number card p-0 d-flex flex-column justify-content-around" style="height: 90%;width: 130px; background-color: transparent;  box-shadow: none;">
                <h2 class="custom-number text-white" style="font-size: 64px;"><?php echo $totalTahun; ?></h2>
                <h3 class="custom-card-title-text card-title text-white">Tahun <?php echo $tahunIni; ?></h3>
              </div>
              <div class="custom-card-number card p-0 d-flex flex-column justify-content-around" style="height: 90%;width: 130px; background-color: transparent;  box-shadow: none;">
                <h2 class="custom-number text-white" style="font-size: 64px;"><?php echo $totalSemua; ?></h2>
                <h3 class="custom-card-title-text card-title text-white">Total</h3>
              </div>
            </div>
          </div>
        </div>

        
        <div class="custom-card col-4 h-100 d-none">
          <div class="position-relative card background-gradasi-biru-ungu h-100 w-100 overflow-hidden" >
            <div class="card-header" style="z-index: 2;background-color: transparent;">
              <h3 class="card-title text-white">BA Pengembalian Inventaris</h3>
            </div>
            <a href="ba_pengembalian/ba_pengembalian.php" class="custom-card-links position-absolute card-header border-0 d-flex justify-content-center btn btn-primary tombol-ba-kerusakan" style="box-shadow: none;">
              <i class="bi bi-newspaper text-white position-absolute teks-ba-kerusakan" style="bottom: 20px;"></i>
            </a>
            <!-- <a href="ba_kerusakan-fix/form_input_ba_kerusakan.php" class="position-absolute card-header border-0 d-flex justify-content-center btn btn-primary tombol-ba-kerusakan-pen" style="box-shadow: none;">
              <i class="bi bi-feather text-white position-absolute teks-ba-kerusakan-pen" style="bottom: 20px;"></i>
            </a> -->
            <div class="custom-card-body card-body w-100 p-0 d-flex justify-content-around align-items-center pt-5">
              <div class="custom-card-number card p-0 d-flex flex-column justify-content-around" style="height: 90%;width: 130px; background-color: transparent;  box-shadow: none;">
                <h2 class="custom-number text-white p-0 m-0" style="font-size: 64px;"><?php echo $totalBulan2; ?></h2>
                <h3 class="custom-card-title-text card-title text-white">Bulan <?php echo $bulanNamaSekarang; ?></h3>
              </div>
              <div class="custom-card-number card p-0 d-flex flex-column justify-content-around" style="height: 90%;width: 130px; background-color: transparent;  box-shadow: none;">
                <h2 class="custom-number text-white" style="font-size: 64px;"><?php echo $totalTahun2; ?></h2>
                <h3 class="custom-card-title-text card-title text-white">Tahun <?php echo $tahunIni; ?></h3>
              </div>
              <div class=" custom-card-number card p-0 d-flex flex-column justify-content-around" style="height: 90%;width: 130px; background-color: transparent;  box-shadow: none;">
                <h2 class="custom-number text-white" style="font-size: 64px;"><?php echo $totalSemua2; ?></h2>
                <h3 class="custom-card-title-text card-title text-white">Total</h3>
              </div>
            </div>
          </div>
        </div>

        <div class="custom-card col-4 h-100
        <?php if ($ptSekarang !== 'PT.MSAL (HO)'): ?>
          d-none
          <?php endif; ?>
        ">
          <div class="position-relative card background-gradasi-biru-ungu h-100 w-100 overflow-hidden" >
            <div class="card-header" style="z-index: 2;background-color: transparent;">
              <h3 class="card-title text-white">BA Serah Terima Penggunaan Asset Inventaris</h3>
            </div>
            <a href="ba_serah-terima-asset/ba_serah-terima-asset.php" class="custom-card-links position-absolute card-header border-0 d-flex justify-content-center btn btn-primary tombol-ba-kerusakan" style="box-shadow: none;">
              <i class="bi bi-newspaper text-white position-absolute teks-ba-kerusakan" style="bottom: 20px;"></i>
            </a>
            <!-- <a href="ba_kerusakan-fix/form_input_ba_kerusakan.php" class="position-absolute card-header border-0 d-flex justify-content-center btn btn-primary tombol-ba-kerusakan-pen" style="box-shadow: none;">
              <i class="bi bi-feather text-white position-absolute teks-ba-kerusakan-pen" style="bottom: 20px;"></i>
            </a> -->
            <div class="custom-card-body card-body w-100 p-0 d-flex justify-content-around align-items-center pt-5">
              <div class="custom-card-number card p-0 d-flex flex-column justify-content-around" style="height: 90%;width: 130px; background-color: transparent;  box-shadow: none;">
                <h2 class="custom-number text-white p-0 m-0" style="font-size: 64px;"><?php echo $totalBulan3; ?></h2>
                <h3 class="custom-card-title-text card-title text-white">Bulan <?php echo $bulanNamaSekarang; ?></h3>
              </div>
              <div class="custom-card-number card p-0 d-flex flex-column justify-content-around" style="height: 90%;width: 130px; background-color: transparent;  box-shadow: none;">
                <h2 class="custom-number text-white" style="font-size: 64px;"><?php echo $totalTahun3; ?></h2>
                <h3 class="custom-card-title-text card-title text-white">Tahun <?php echo $tahunIni; ?></h3>
              </div>
              <div class="custom-card-number card p-0 d-flex flex-column justify-content-around" style="height: 90%;width: 130px; background-color: transparent;  box-shadow: none;">
                <h2 class="custom-number text-white" style="font-size: 64px;"><?php echo $totalSemua3; ?></h2>
                <h3 class="custom-card-title-text card-title text-white">Total</h3>
              </div>
            </div>
          </div>
        </div>

        <div class="custom-card col-4 h-100">
          <div class="position-relative card background-gradasi-biru-ungu h-100 w-100 overflow-hidden" >
            <div class="card-header" style="z-index: 2;background-color: transparent;">
              <h3 class="card-title text-white">BA Mutasi Asset</h3>
            </div>
            <a href="ba_serah-terima-asset/ba_serah-terima-asset.php" class="custom-card-links position-absolute card-header border-0 d-flex justify-content-center btn btn-primary tombol-ba-kerusakan" style="box-shadow: none;">
              <i class="bi bi-newspaper text-white position-absolute teks-ba-kerusakan" style="bottom: 20px;"></i>
            </a>
            <!-- <a href="ba_kerusakan-fix/form_input_ba_kerusakan.php" class="position-absolute card-header border-0 d-flex justify-content-center btn btn-primary tombol-ba-kerusakan-pen" style="box-shadow: none;">
              <i class="bi bi-feather text-white position-absolute teks-ba-kerusakan-pen" style="bottom: 20px;"></i>
            </a> -->
            <div class="custom-card-body card-body w-100 p-0 d-flex justify-content-around align-items-center pt-5">
              <div class="custom-card-number card p-0 d-flex flex-column justify-content-around" style="height: 90%;width: 130px; background-color: transparent;  box-shadow: none;">
                <h2 class="custom-number text-white p-0 m-0" style="font-size: 64px;"><?php echo $totalBulan3; ?></h2>
                <h3 class="custom-card-title-text card-title text-white">Bulan <?php echo $bulanNamaSekarang; ?></h3>
              </div>
              <div class="custom-card-number card p-0 d-flex flex-column justify-content-around" style="height: 90%;width: 130px; background-color: transparent;  box-shadow: none;">
                <h2 class="custom-number text-white" style="font-size: 64px;"><?php echo $totalTahun3; ?></h2>
                <h3 class="custom-card-title-text card-title text-white">Tahun <?php echo $tahunIni; ?></h3>
              </div>
              <div class="custom-card-number card p-0 d-flex flex-column justify-content-around" style="height: 90%;width: 130px; background-color: transparent;  box-shadow: none;">
                <h2 class="custom-number text-white" style="font-size: 64px;"><?php echo $totalSemua3; ?></h2>
                <h3 class="custom-card-title-text card-title text-white">Total</h3>
              </div>
            </div>
          </div>
        </div>

      </div>

      <?php endif; ?>
      
      
      
    </main>
    <!--Akhir::Main Content-->

    <!--Awal::Footer Content-->
        <footer class="custom-footer d-flex position-relative p-2" style="border-top: whitesmoke solid 1px; box-shadow: 0px 7px 10px black; color: grey;">
            <p class="position-absolute" style="right: 15px;bottom:7px;color: grey;"><strong>Version </strong>1.1.0</p>
        <p class="pt-2 ps-1"><strong>Copyright &copy 2025</p></strong><p class="pt-2 ps-1 fw-bold text-primary">MIS MSAL.</p><p class="pt-2 ps-1"> All rights reserved</p>
        </footer>
    <!--Akhir::Footer Content-->
    
    <?php
    include 'koneksi.php';

    // Ambil data warna
    $sqlWarna = "SELECT nama, warna FROM personalia_menucolor ORDER BY nama ASC";
    $resultWarna = $koneksi->query($sqlWarna);
    ?>

    <div id="popupBoxPersonalia" class="popup-box position-fixed end-0" style="z-index: 15; top: 50px;">
      <div id="theme-panel" class="card position-relative bg-white p-2 m-2" style="width:200px; height:max-content; box-shadow: 0px 4px 8px rgba(0,0,0,0.1); ">
        <h5 class="card-title border-bottom pb-2 mb-0">Personalia</h5>
        <form action="proses_simpan_personalia.php" method="post" class="p-0">
        <div class="mb-2">
          <label for="themeSelect" class="form-label mt-0">Warna Tema:</label>
          <select id="themeSelect" name="warna_menu" class="form-select">
            <option value="0" selected>Default</option>
            <?php while($row = $resultWarna->fetch_assoc()): ?>
              <option value="<?= htmlspecialchars($row['warna']); ?>">
                <?= htmlspecialchars($row['nama']); ?>
              </option>
            <?php endwhile; ?>
          </select>
        </div>
        <button type="submit" class="btn btn-primary btn-sm">Simpan</button>
        </form>
      </div>
    </div>

    <div id="popupBoxAutograph" class="popup-box custom-popup-autograph position-absolute bg-white rounded-1 flex-column justify-content-start align-items-center p-2">
        <div class="w-100 d-flex justify-content-between mb-2 p-0" style="height: max-content;">
            <h4 class="m-0 p-0">Tanda tangan</h4>
            <a id="tombolClosePopupAutograph" class='custom-btn-action btn btn-danger btn-sm' href='#'><i class="bi bi-x-lg"></i></a>
        </div>
        <div class="w-100 d-flex justify-content-start d-none">
            <p class="m-0 p-0" style="font-weight: 500;">Anda akan menandatangani BA dengan informasi berikut :<br>
                <span id="idBAAutograph">-</span> <span id="approvalBAAutograph">-</span> <span id="autographBAAutograph">-</span><br>
                Nama: <span id="namaBAAutograph">-</span><br>
                Peran: <span id="peranBAAutograph">-</span><br>
                <!-- Data TTD: <span id="pictBAAutograph">-</span><br> -->
                Data TTD: <img id="userBAAutograph" src="" alt="TTD User" style="max-width:150px; border:1px solid #ccc;">
                <br>
                Data BA: <span id="jenisBAAutograph">-</span>/<span id="nomorBAAutograph">-</span>/MIS/<span id="periodeBAAutograph">-</span>/<span id="tahunBAAutograph">-</span>
            </p>
        </div>
        <input type="hidden" name="id" id="inputIdBAAutograph">
        <input type="hidden" name="approvalCol" id="inputApprovalBAAutograph">
        <input type="hidden" name="autographCol" id="inputAutographBAAutograph">
        <input type="hidden" name="nama" id="inputNamaBAAutograph">
        <input type="hidden" name="peran" id="inputPeranBAAutograph">
        <input type="hidden" name="jenis" id="inputJenisBAAutograph">
        <input type="hidden" name="nomor" id="inputNomorBAAutograph">
        <input type="hidden" name="periode" id="inputPeriodeBAAutograph">
        <input type="hidden" name="tahun" id="inputTahunBAAutograph">
        <div class="autograph-container p-3 pb-1">
            <canvas id="signature" width="500" height="200" style="border: 1px solid black; border-radius: 8px;"></canvas>
            <div class="d-flex justify-content-between mt-2">
                <button id="clear" class="btn btn-warning btn-sm">Bersihkan</button>
                <button id="load" class="btn btn-primary btn-sm"><i class="bi bi-arrow-clockwise"></i></button>
                <button id="save" class="btn btn-success btn-sm">Simpan</button>
            </div>
        </div>
        <!-- <div id="instant" class="w-100 flex-column align-items-center mb-2 p-0" style="height: max-content;">
            <p class="m-0 p-0" style="font-size: 12px;">Anda memiliki tanda tangan tersimpan</p>
            <button id="setujui" class="btn btn-success btn-sm w-50"> Setujui instan </button>
        </div> -->
    </div>

    <div id="popupBG" class="popup-bg position-absolute w-100 h-100" style="background-color: rgba(0,0,0,0);z-index: 8;"></div>
    <div id="popupBGAprv" class="popup-bg position-absolute w-100 h-100" style="background-color: rgba(0,0,0,0.5);z-index: 8;"></div>

  </div>

<script src="assets/js/signature_pad.umd.min.js"></script>

<!-- Chart.js -->
<script src="node_modules/chart.js/dist/chart.umd.min.js"></script>

<script src="assets/adminlte/js/jquery.min.js"></script>

<!-- Bootstrap 5 -->
<script src="assets/bootstrap-5.3.6-dist/js/bootstrap.min.js"></script>

<!-- popperjs Bootstrap 5 -->
<script src="assets/js/popper.min.js"></script>

<!-- AdminLTE -->
<script src="assets/adminlte/js/adminlte.js"></script>

<!-- OverlayScrollbars -->
<script src="assets/js/overlayscrollbars.browser.es6.min.js"></script>

<script>//Info Akun
document.addEventListener('DOMContentLoaded', function () {
    const button = document.getElementById('tombolAkun');
    const box = document.getElementById('akunInfo');

    button.addEventListener('click', function () {
        if (box.classList.contains('display-state')) {
            // Buka
            box.classList.remove('display-state');
            setTimeout(() => {
                box.classList.add('aktif');
            }, 200);
        } else {
            // Tutup
            box.classList.remove('aktif');
            setTimeout(() => {
                box.classList.add('display-state');
            }, 200);
        }
    });
});
</script>

<script>//Form Input
//Sistem tombol popup input
document.addEventListener('DOMContentLoaded', function () {
    const open = document.getElementById('personaliaBtn');
    const box = document.getElementById('popupBoxPersonalia');
    const background = document.getElementById('popupBG');

    open.addEventListener('click', function () {
        box.classList.add('aktifPopup');
        background.classList.add('aktifPopup');
        box.classList.add('scale-in-center');
        box.classList.remove('scale-out-center');
        background.classList.add('fade-in');
        background.classList.remove('fade-out');
    });

    background.addEventListener('click', function () {
        box.classList.remove('aktifPopup');
        background.classList.remove('aktifPopup');
        // setTimeout(() => {
        //     background.classList.remove('aktifPopup');
        //     box.classList.remove('aktifPopup');
        // }, 300); 
        // box.classList.remove('scale-in-center');
        // box.classList.add('scale-out-center');
        // background.classList.remove('fade-in');
        // background.classList.add('fade-out');
    });
});
</script>

<?php if ($_SESSION['hak_akses'] === 'Admin' || $_SESSION['hak_akses'] === 'Super Admin'): ?>

<script>//Chart data barang rusak BA Kerusakan
  const ctx = document.getElementById('baKerusakan');

  new Chart(ctx, {
    type: 'pie',
    data: {
      labels: <?= json_encode($kategoriLabels); ?>,
      datasets: [{
        label: ' Jumlah kerusakan',
        data: <?= json_encode($kategoriTotals); ?>,
        borderWidth: 0
      }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false, // agar tinggi fleksibel
        plugins: {
          legend: { position: 'top' },
          title: {
            display: true,
            text: 'Total Kerusakan per Kategori Tahun <?= ($tahunSekarang) ?>',
            font:{
              size:18,
              weight:'bold',
              family:'arial',
            }
        }
      }
    }
  });
</script>

<script>
  const ctxBAK = document.getElementById('chartBaKerusakan').getContext('2d');
  const myBarChart = new Chart(ctxBAK, {
    type: 'bar',
    data: {
      labels: ['MSAL HO', 'MSAL SITE', 'MAPA', 'PEAK', 'PSAM', 'WCJU', 'KPP'],
      datasets: [{
        label: 'Jumlah Data <?= ($tahunSekarang) ?>',
        data: <?= json_encode($chartDataPT) ?>,
        backgroundColor: 'rgba(54, 162, 235, 0.8)',
        borderColor: 'none',
        borderWidth: 0
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      resizeDelay: 100,
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
          precision: 0
          },
          title: {
            display: true,
            text: 'Jumlah',
            font:{
            weight:'bold',
            family:'arial',
            }
          }
        },
        x: {
          title: {
            display: true,
            text: 'PT',
            font:{
            weight:'bold',
            family:'arial',
            }
          }
        }
      },
      plugins: {
        legend: {
          display: true,
          position: 'top'
        },
        title: {
          display: true,
          text: 'Berita Acara Kerusakan Asset',
          font:{
            size:24,
            weight:'bold',
            family:'arial',
          }
        }
      }
    }
  });
</script>

<script>
  const ctxBAP = document.getElementById('chartBaPengembalian').getContext('2d');
  const myBarChart2 = new Chart(ctxBAP, {
    type: 'bar',
    data: {
      labels: ['MSAL HO', 'MSAL SITE', 'MAPA', 'PEAK', 'PSAM', 'WCJU', 'KPP'],
      datasets: [{
        label: 'Jumlah Data 2025',
        data: [4, 0, 0, 0, 0, 0, 0],
        backgroundColor: 'rgba(54, 162, 235, 0.8)',
        borderColor: 'none',
        borderWidth: 0
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      resizeDelay: 100,
      scales: {
        y: {
          beginAtZero: true,
          title: {
            display: true,
            text: 'Jumlah',
            font:{
            weight:'bold',
            family:'arial',
            }
          }
        },
        x: {
          title: {
            display: true,
            text: 'PT',
            font:{
            weight:'bold',
            family:'arial',
            }
          }
        }
      },
      plugins: {
        legend: {
          display: true,
          position: 'top'
        },
        title: {
          display: true,
          text: 'Berita Acara Pengembalian Inventaris',
          font:{
            size:24,
            weight:'bold',
            family:'arial',
          }
        }
      }
    }
  });
</script>

<script>
  const ctxBAN = document.getElementById('chartBaSTAsset').getContext('2d');
  const myBarChart3 = new Chart(ctxBAN, {
    type: 'bar',
    data: {
      labels: ['MSAL HO', 'MSAL SITE', 'MAPA', 'PEAK', 'PSAM', 'WCJU', 'KPP'],
      datasets: [{
        label: 'Jumlah Data <?= ($tahunSekarang) ?>',
        data: <?= json_encode($chartDataPTSTA) ?>,
        backgroundColor: 'rgba(54, 162, 235, 0.8)',
        borderColor: 'none',
        borderWidth: 0
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      resizeDelay: 100,
      scales: {
        y: {
          beginAtZero: true,
          title: {
            display: true,
            text: 'Jumlah',
            font:{
            weight:'bold',
            family:'arial',
            }
          }
        },
        x: {
          title: {
            display: true,
            text: 'PT',
            font:{
            weight:'bold',
            family:'arial',
            }
          }
        }
      },
      plugins: {
        legend: {
          display: true,
          position: 'top'
        },
        title: {
          display: true,
          text: 'Berita Acara Serah Terima Penggunaan Asset Inventaris',
          font:{
            size:24,
            weight:'bold',
            family:'arial',
          }
        }
      }
    }
  });
</script>

<script>
  const ctxBAM = document.getElementById('chartBaMutasi').getContext('2d');
  const myBarChart4 = new Chart(ctxBAM, {
    type: 'bar',
    data: {
      labels: ['MSAL HO', 'MSAL SITE', 'MAPA', 'PEAK', 'PSAM', 'WCJU', 'KPP'],
      datasets: [{
        label: 'Jumlah Data <?= ($tahunSekarang) ?>',
        data: <?= json_encode($chartDataPTM) ?>,
        backgroundColor: 'rgba(54, 162, 235, 0.8)',
        borderColor: 'none',
        borderWidth: 0
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      resizeDelay: 100,
      scales: {
        y: {
          beginAtZero: true,
          title: {
            display: true,
            text: 'Jumlah',
            font:{
            weight:'bold',
            family:'arial',
            }
          }
        },
        x: {
          title: {
            display: true,
            text: 'PT',
            font:{
            weight:'bold',
            family:'arial',
            }
          }
        }
      },
      plugins: {
        legend: {
          display: true,
          position: 'top'
        },
        title: {
          display: true,
          text: 'Berita Acara Mutasi Asset',
          font:{
            size:24,
            weight:'bold',
            family:'arial',
          }
        }
      }
    }
  });
</script>

<script>
  const labels = <?= json_encode(array_slice($labels, 0, 12)) ?>; 
  const dataKerusakan = <?= json_encode($dataKerusakan) ?>;
  const dataPengembalian = <?= json_encode($dataPengembalian) ?>;
  const dataSerahTerima  = <?= json_encode($dataSerahTerima) ?>;
  const ctx5 = document.getElementById('baGab');

  new Chart(ctx5, {
    type: 'line', 
    data: {
      labels: labels,
      datasets: [
        {
          label: 'Jumlah BA Kerusakan Aset',
          data: dataKerusakan,
          borderColor: '#f74242ff',
          backgroundColor: '#f7424233',
          fill: true,
          tension: 0.4,
          cubicInterpolationMode: 'monotone'
        },
        {
          label: 'Jumlah BA Pengembalian Inventaris',
          data: dataPengembalian,
          borderColor: '#0ba064ff',
          backgroundColor: '#0ba06433',
          fill: true,
          tension: 0.4,
          cubicInterpolationMode: 'monotone'
        },
        {
          label: 'Jumlah BA Serah Terima Penggunaan Asset Inventaris',
          data: dataSerahTerima,
          borderColor: '#64adf5ff',
          backgroundColor: '#64c2f533',
          fill: true,
          tension: 0.4,
          cubicInterpolationMode: 'monotone'
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      interaction: {
        mode: 'index',
        intersect: false
      },
      scales: {
        x: {
          beginAtZero: true
        },
        y: {
          beginAtZero: true
        }
      },
      plugins: {
        legend: { position: 'top' },
        title: {
          display: true,
          text: 'Grafik Data Berita Acara Tahun 2025',
          font: {
            size: 24,
            weight: 'bold',
            family: 'Arial'
          }
        }
      }
    }
  });
</script>

<?php endif; ?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // === Popup handling ===
        const close = document.getElementById('tombolClosePopupAutograph');
        const box = document.getElementById('popupBoxAutograph');
        const background = document.getElementById('popupBGAprv');


        document.addEventListener('click', function(e) {
            const btn = e.target.closest('.tombolAutographPopup');
            if (btn) {
                // Ambil data dari tombol
                const id = btn.dataset.id;
                const approval = btn.dataset.approvalCol;
                const autograph = btn.dataset.autographCol;
                const nama = btn.dataset.nama;
                const peran = btn.dataset.peran;
                const jenis = btn.dataset.jenis;
                const nomor = btn.dataset.nomor;
                const tanggal = btn.dataset.tanggal;
                const pictAutograph = btn.dataset.picture;
                const userAutograph = btn.dataset.userPicture;

                // const instantDiv = document.getElementById("instant");

                // === Load gambar TTD ke canvas ===
                if (userAutograph && userAutograph.trim() !== "") {

                    const img = new Image();
                    img.src = "data:image/png;base64," + userAutograph;
                    img.onload = function() {
                        const canvas = document.getElementById("signature");
                        const ctx = canvas.getContext("2d");

                        ctx.clearRect(0, 0, canvas.width, canvas.height);

                        const scale = Math.min(canvas.width / img.width, canvas.height / img.height);
                        const x = (canvas.width / 2) - (img.width / 2) * scale;
                        const y = (canvas.height / 2) - (img.height / 2) * scale;

                        ctx.drawImage(img, x, y, img.width * scale, img.height * scale);
                    };
                    img.onerror = function() {
                        console.error("Gagal load gambar");
                    };
                } else {
                    signaturePad.clear(); // Jika tidak ada gambar, canvas tetap bersih
                }

                // Format tanggal → romawi + tahun
                const d = new Date(tanggal);
                const bulan = d.getMonth() + 1;
                const tahun = d.getFullYear();
                const romawi = ["I", "II", "III", "IV", "V", "VI", "VII", "VIII", "IX", "X", "XI", "XII"][bulan - 1];

                // Isi span
                document.getElementById("idBAAutograph").innerText = id;
                document.getElementById("approvalBAAutograph").innerText = approval;
                document.getElementById("autographBAAutograph").innerText = autograph;
                document.getElementById("namaBAAutograph").innerText = nama;
                document.getElementById("peranBAAutograph").innerText = peran;
                document.getElementById("jenisBAAutograph").innerText = jenis;
                document.getElementById("nomorBAAutograph").innerText = nomor;
                document.getElementById("periodeBAAutograph").innerText = romawi;
                document.getElementById("tahunBAAutograph").innerText = tahun;
                // document.getElementById("pictBAAutograph").src = pictAutograph;
                document.getElementById("userBAAutograph").src = userAutograph ? "data:image/png;base64," + userAutograph : "";

                // Isi hidden input
                document.getElementById("inputIdBAAutograph").value = id;
                document.getElementById("inputApprovalBAAutograph").value = approval;
                document.getElementById("inputAutographBAAutograph").value = autograph;
                document.getElementById("inputNamaBAAutograph").value = nama;
                document.getElementById("inputPeranBAAutograph").value = peran;
                document.getElementById("inputJenisBAAutograph").value = jenis;
                document.getElementById("inputNomorBAAutograph").value = nomor;
                document.getElementById("inputPeriodeBAAutograph").value = romawi;
                document.getElementById("inputTahunBAAutograph").value = tahun;

                // Tampilkan popup
                box.classList.add('aktifPopup', 'scale-in-center');
                box.classList.remove('scale-out-center');
                background.classList.add('aktifPopup', 'fade-in');
                background.classList.remove('fade-out');

                // Reset signature pad saat popup dibuka
                // signaturePad.clear();


            }
        });

        close.addEventListener('click', function() {
            setTimeout(() => {
                background.classList.remove('aktifPopup');
                box.classList.remove('aktifPopup');
            }, 300);
            box.classList.remove('scale-in-center');
            box.classList.add('scale-out-center');
            background.classList.remove('fade-in');
            background.classList.add('fade-out');
        });

        background.addEventListener('click', function() {
            setTimeout(() => {
                background.classList.remove('aktifPopup');
                box.classList.remove('aktifPopup');
            }, 300);
            box.classList.remove('scale-in-center');
            box.classList.add('scale-out-center');
            background.classList.remove('fade-in');
            background.classList.add('fade-out');
        });

        // === SignaturePad ===
        const canvas = document.getElementById("signature");

        // resize canvas agar ukuran internal mengikuti tampilan,
        // khususnya saat lebar layar <= 450px
        function resizeSignatureCanvas() {
            if (window.innerWidth <= 450) {
                const displayWidth = canvas.offsetWidth || canvas.clientWidth || 300;
                canvas.width = displayWidth;
                canvas.height = 200;
            } else {
                canvas.width = 500;
                canvas.height = 200;
            }
        }

        // panggil sekali saat awal
        resizeSignatureCanvas();

        const signaturePad = new SignaturePad(canvas, {
            backgroundColor: "white",
            penColor: "black"
        });

        // jika ukuran layar berubah, sesuaikan lagi
        window.addEventListener("resize", function() {
            const wasEmpty = signaturePad.isEmpty();
            resizeSignatureCanvas();
            if (!wasEmpty) {
                signaturePad.clear();
            }
        });

        document.getElementById("clear").addEventListener("click", (e) => {
            e.preventDefault();
            signaturePad.clear();
        });

        document.getElementById("load").addEventListener("click", (e) => {
            e.preventDefault();

            const userAutograph = document.getElementById("userBAAutograph").src;

            if (!userAutograph || userAutograph.trim() === "" || !userAutograph.startsWith("data:image")) {
                window.alert("Tidak ada data TTD sebelumnya untuk dimuat ulang.");
                return;
            }

            const img = new Image();
            img.src = userAutograph;

            img.onload = function() {
                const canvas = document.getElementById("signature");
                const ctx = canvas.getContext("2d");

                ctx.clearRect(0, 0, canvas.width, canvas.height);

                const scale = Math.min(canvas.width / img.width, canvas.height / img.height);
                const x = (canvas.width / 2) - (img.width / 2) * scale;
                const y = (canvas.height / 2) - (img.height / 2) * scale;

                ctx.drawImage(img, x, y, img.width * scale, img.height * scale);
            };

            img.onerror = function() {
                console.error("Gagal memuat ulang gambar userAutograph");
            };
        });

        document.getElementById("save").addEventListener("click", async (e) => {
            const userAutographSrc = document.getElementById("userBAAutograph").src;
            const hasLoadedAutograph = userAutographSrc && userAutographSrc.startsWith("data:image");

            if (signaturePad.isEmpty() && !hasLoadedAutograph) {
                window.alert("Silakan buat tanda tangan terlebih dahulu sebelum menyimpan!");
                return;
            }

            const base64 = signaturePad.toDataURL("image/png");

            const response = await fetch(base64);
            const blob = await response.blob();

            const form = document.createElement("form");
            form.method = "POST";
            form.action = "proses_autograph.php";
            form.enctype = "multipart/form-data";

            function addField(name, value) {
                const input = document.createElement("input");
                input.type = "hidden";
                input.name = name;
                input.value = value;
                form.appendChild(input);
            }

            addField("id", document.getElementById("inputIdBAAutograph").value);
            addField("approvalCol", document.getElementById("inputApprovalBAAutograph").value);
            addField("autographCol", document.getElementById("inputAutographBAAutograph").value);
            addField("nama", document.getElementById("inputNamaBAAutograph").value);
            addField("peran", document.getElementById("inputPeranBAAutograph").value);
            addField("jenis", document.getElementById("inputJenisBAAutograph").value);
            addField("nomor", document.getElementById("inputNomorBAAutograph").value);
            addField("periode", document.getElementById("inputPeriodeBAAutograph").value);
            addField("tahun", document.getElementById("inputTahunBAAutograph").value);

            // === PENTING: signature harus FILE (sesuai $_FILES['signature']) ===
            const fileInput = document.createElement("input");
            fileInput.type = "file";
            fileInput.name = "signature";

            const dt = new DataTransfer();
            dt.items.add(new File([blob], "signature.png", {
                type: "image/png"
            }));
            fileInput.files = dt.files;

            form.appendChild(fileInput);

            document.body.appendChild(form);
            form.submit();
        });

    });
</script>

<script> //Konfigurasi OverlayScrollbars

//-----------------------------------------------------------------------------------
const SELECTOR_SIDEBAR_WRAPPER = '.sidebar-wrapper';
const Default = {
    scrollbarTheme: 'os-theme-light',
    scrollbarAutoHide: 'leave',
    scrollbarClickScroll: true,
};
document.addEventListener('DOMContentLoaded', function () {
    const sidebarWrapper = document.querySelector(SELECTOR_SIDEBAR_WRAPPER);
    if (sidebarWrapper && typeof OverlayScrollbarsGlobal?.OverlayScrollbars !== 'undefined') {
    OverlayScrollbarsGlobal.OverlayScrollbars(sidebarWrapper, {
        scrollbars: {
        theme: Default.scrollbarTheme,
        autoHide: Default.scrollbarAutoHide,
        clickScroll: Default.scrollbarClickScroll,
        },
    });
    }
});
//-----------------------------------------------------------------------------------

</script>

<script> //Sidebar

//-----------------------------------------------------------------------------------
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    sidebar.classList.toggle('show');

    // Event listener satu kali untuk klik luar
    if (sidebar.classList.contains('show')) {
        document.addEventListener('click', handleClickOutsideSidebar);
    } else {
        document.removeEventListener('click', handleClickOutsideSidebar);
    }
}

function handleClickOutsideSidebar(event) {
    const sidebar = document.getElementById('sidebar');
    const toggleButton = event.target.closest("button[onclick='toggleSidebar()']");
    
    if (!sidebar.contains(event.target) && !toggleButton) {
        sidebar.classList.remove('show');
        document.removeEventListener('click', handleClickOutsideSidebar);
    }
}
//-----------------------------------------------------------------------------------

</script>

<script>//Tanggal

//-----------------------------------------------------------------------------------
function updateDate() {
    const now = new Date();
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    const formattedDate = now.toLocaleDateString('id-ID', options);
    document.getElementById('date').textContent = formattedDate;
}
setInterval(updateDate, 1000); // Update setiap detik
updateDate(); // Panggil langsung saat halaman load
//-----------------------------------------------------------------------------------

</script>

<script> // Jam Digital
//-----------------------------------------------------------------------------------

function updateClock() {
    const now = new Date();
    const jam = String(now.getHours()).padStart(2, '0');
    const menit = String(now.getMinutes()).padStart(2, '0');
    const detik = String(now.getSeconds()).padStart(2, '0');
    document.getElementById('clock').textContent = `${jam}:${menit}:${detik}`;
}

setInterval(updateClock, 1000);
updateClock(); // Panggil langsung saat halaman load
//-----------------------------------------------------------------------------------

</script>

</body>
</html>
