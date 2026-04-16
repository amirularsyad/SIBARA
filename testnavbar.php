<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login_registrasi.php");
    exit();
}
if (!isset($_SESSION['hak_akses']) || $_SESSION['hak_akses'] !== 'Admin') {
    header("Location: personal/approval.php");
    exit();
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

    #date{
        margin-right: 10px;
    }

    #clock {
      font-size: 16px;
      color: white;
      margin-right: 20px;
    }

    .app-sidebar{
            background: linear-gradient(to bottom right, #3e02be 0%,rgb(1, 64, 159) 50%, rgb(2, 59, 159) 100%) !important;
    }

    .navbar{
            background: linear-gradient(to right, rgb(1, 64, 159) 0%, rgb(2, 77, 190) 60%, rgb(2, 77, 207) 100%) !important;
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

    .grid-2 {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 20px;
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
    @media (max-width: 992px) {
      .custom-nav-link{
        display: none !important;
      }
      .custom-nav{
        height: 10vh;
      }
      .custom-navbar-container, .custom-navbar-r{
        height: 100%;
      }
      .custom-navbar-r{
        display: flex;
        align-items: center;
      }
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
      .custom-chart-1,.custom-chart-2{
        width: 100%;
        padding: 1%;
      }
      .custom-card{
        width: 50%;
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
        width: 100px !important;
        height: 100px !important;
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
    }
    #res-fullscreen{
      display: none;
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
      font-size: 3rem;
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
      /* Untuk Firefox */
      background-clip: text;
      color: transparent;
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

  <div class="app-wrapper">
    
    <!--begin::Header-->
    <nav class="custom-nav app-header navbar navbar-expand bg-body sticky-top" style="margin-bottom: 0; z-index: 5;">
        <!--begin::Container-->
        <div class="custom-navbar-container container-fluid">
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
        <ul class="custom-navbar-r navbar-nav ms-auto">
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
    $query = "SELECT COUNT(*) AS jumlah FROM berita_acara_kerusakan WHERE approval_2 = 0";
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
            <?php if ($_SESSION['hak_akses'] === 'Admin'): ?>
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
            <!-- List BA Pengembalian -->
            <li class="nav-item">
                <a href="ba_pengembalian/ba_pengembalian.php" class="nav-link">
                <i class="nav-icon bi bi-newspaper"></i>
                <p>
                    BA Pengembalian
                </p>
                </a>
            </li>
            <!-- List BA Serah Terima -->
            <li class="nav-item">
                <a href="#" class="nav-link">
                <i class="nav-icon bi bi-newspaper"></i>
                <p>
                    BA Serah Terima
                    <i class="nav-arrow bi bi-chevron-right"></i>
                </p>
                </a>
                <ul class="nav nav-treeview">
                    <li class="nav-item">
                        <a href="ba_serah-terima-notebook/ba_serah-terima-notebook.php" class="nav-link">
                        <i class="bi bi-laptop"></i>
                        <p>
                            Notebook
                        </p>
                        </a>
                    </li>
                </ul>
            </li>
            <li class="nav-item">
                <a href="#" class="nav-link">
                <i class="nav-icon bi bi-newspaper"></i>
                <p>
                    BA Peminjaman
                </p>
                </a>
            </li>
            <li class="nav-item">
                <a href="ba_mutasi/ba_mutasi.php" class="nav-link">
                <i class="nav-icon bi bi-newspaper"></i>
                <p>
                    BA Mutasi
                </p>
                </a>
            </li>
            <li class="nav-header">
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
            </li>
            
            
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
            <?php if ($_SESSION['hak_akses'] === 'Super Admin'): ?>
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
            
            </ul>
              
            </ul>

        </nav>
        </div>
    </aside>
    <!--Akhir::Sidebar-->
      <?php if ($_SESSION['hak_akses'] === 'Admin'): ?>
    <?php
    include 'koneksi.php'; 
    
    // Ambil tanggal sekarang
    $tanggalSekarang = date('Y-m-d');
    $bulanIni = date('m');
    $tahunIni = date('Y');

    // Hitung total BA bulan ini
    $queryBulan = mysqli_query($koneksi, "SELECT COUNT(*) as total_bulan FROM berita_acara_kerusakan WHERE MONTH(tanggal) = '$bulanIni' AND YEAR(tanggal) = '$tahunIni'");
    $dataBulan = mysqli_fetch_assoc($queryBulan);
    $totalBulan = $dataBulan['total_bulan'];

    $queryBulan2 = mysqli_query($koneksi, "SELECT COUNT(*) as total_bulan FROM berita_acara_pengembalian WHERE MONTH(tanggal) = '$bulanIni' AND YEAR(tanggal) = '$tahunIni'");
    $dataBulan2 = mysqli_fetch_assoc($queryBulan2);
    $totalBulan2 = $dataBulan2['total_bulan'];

    $queryBulan3 = mysqli_query($koneksi, "SELECT COUNT(*) as total_bulan FROM ba_serah_terima_notebook WHERE MONTH(tanggal) = '$bulanIni' AND YEAR(tanggal) = '$tahunIni'");
    $dataBulan3 = mysqli_fetch_assoc($queryBulan3);
    $totalBulan3 = $dataBulan3['total_bulan'];

    // Hitung total BA tahun ini
    $queryTahun = mysqli_query($koneksi, "SELECT COUNT(*) as total_tahun FROM berita_acara_kerusakan WHERE YEAR(tanggal) = '$tahunIni'");
    $dataTahun = mysqli_fetch_assoc($queryTahun);
    $totalTahun = $dataTahun['total_tahun'];

    $queryTahun2 = mysqli_query($koneksi, "SELECT COUNT(*) as total_tahun FROM berita_acara_pengembalian WHERE YEAR(tanggal) = '$tahunIni'");
    $dataTahun2 = mysqli_fetch_assoc($queryTahun2);
    $totalTahun2 = $dataTahun2['total_tahun'];

    $queryTahun3 = mysqli_query($koneksi, "SELECT COUNT(*) as total_tahun FROM ba_serah_terima_notebook WHERE YEAR(tanggal) = '$tahunIni'");
    $dataTahun3 = mysqli_fetch_assoc($queryTahun3);
    $totalTahun3 = $dataTahun3['total_tahun'];

    // Hitung total semua BA
    $queryTotal = mysqli_query($koneksi, "SELECT COUNT(*) as total_semua FROM berita_acara_kerusakan");
    $dataTotal = mysqli_fetch_assoc($queryTotal);
    $totalSemua = $dataTotal['total_semua'];

    $queryTotal2 = mysqli_query($koneksi, "SELECT COUNT(*) as total_semua FROM berita_acara_pengembalian");
    $dataTotal2 = mysqli_fetch_assoc($queryTotal2);
    $totalSemua2 = $dataTotal2['total_semua'];

    $queryTotal3 = mysqli_query($koneksi, "SELECT COUNT(*) as total_semua FROM ba_serah_terima_notebook");
    $dataTotal3 = mysqli_fetch_assoc($queryTotal3);
    $totalSemua3 = $dataTotal3['total_semua'];

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
      <?php if ($_SESSION['hak_akses'] === 'Admin'): ?>
      
      <div class="custom-row row w-100 d-flex pt-3 mb-3 flex-wrap" style="height: 400px;">
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
          WHERE YEAR(tanggal) = ?
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

        // ==================== BA SERAH TERIMA NOTEBOOK ====================
        $stmt = $koneksi->prepare("
          SELECT MONTH(tanggal) AS m, COUNT(*) AS total
          FROM ba_serah_terima_notebook
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
        <div class="custom-chart-1 col-12">
          <div class="card card-primary" style="width: 100%; height: 100%;">
          <div class="card-body">
            <div class="chart">
              <canvas id="baGab" height="300px"></canvas>
            </div>
          </div>
          </div>
        </div>
        
        <!-- <div class="custom-chart-2 col-4">
          <div class="card card-primary" style="width: 100%; height: 100%">
          <div class="card-body">
            <div class="chart">
              <canvas id="baKerusakan" height="300px"></canvas>
            </div>
          </div>
          </div>
        </div> -->
        
        
        
        
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

        
        <div class="custom-card col-4 h-100">
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

        <div class="custom-card col-4 h-100">
          <div class="position-relative card background-gradasi-biru-ungu h-100 w-100 overflow-hidden" >
            <div class="card-header" style="z-index: 2;background-color: transparent;">
              <h3 class="card-title text-white">BA Serah Terima Notebook</h3>
            </div>
            <a href="ba_serah-terima-notebook/ba_serah-terima-notebook.php" class="custom-card-links position-absolute card-header border-0 d-flex justify-content-center btn btn-primary tombol-ba-kerusakan" style="box-shadow: none;">
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

  </div>
    
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


<?php if ($_SESSION['hak_akses'] === 'Admin'): ?>
<script>//Chart data barang rusak BA Kerusakan
  const ctx = document.getElementById('baKerusakan');

  new Chart(ctx, {
    type: 'pie',
    data: {
      labels: ['Laptop', 'Printer', 'Monitor', 'Keyboard', 'THIN Client', 'SSD','Charger Laptop'],
      datasets: [{
        label: ' Jumlah kerusakan',
        data: [8, 2, 1, 1, 1, 2, 1],
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
            text: 'Total Data Barang Rusak Tahun 2025',
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
          label: 'Jumlah BA Serah Terima Notebook',
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
