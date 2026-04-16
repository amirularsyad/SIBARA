<?php
session_start();

// Jika belum login, arahkan ke halaman login
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../login_registrasi.php");
    exit();
}
include '../koneksi.php';

$ptSekarang = $_SESSION['pt'];
if (is_array($ptSekarang)) {
    $ptSekarang = reset($ptSekarang);
}
$ptSekarang = trim($ptSekarang);

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
    } elseif ($manajemen_akun_akses === 2) {
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

    $textColorStyle = 'font-size: 3rem;
      font-weight: bold;
      color: ' . $warna_menu . ';';
}

$jumlah_approval_notif = require '../approval_notification_badge.php';

?>



<!DOCTYPE html>
<html lang="id">
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Detail BA Serah Terima Asset Inventaris</title>

  <!-- Bootstrap 5 -->
    <link 
      rel="stylesheet" 
      href="../assets/bootstrap-5.3.6-dist/css/bootstrap.min.css"
    />

  <!-- Bootstrap Icons -->
    <link
        rel="stylesheet"
        href="../assets/icons/icons-main/font/bootstrap-icons.min.css"
    />

    <link 
        rel="icon" type="image/png" 
        href="../assets/css/datatables.min.css"
    />

    <link 
        rel="stylesheet" 
        href="../assets/css/datatables.min.css"
    />

  <!-- AdminLTE -->
    <link 
        rel="stylesheet" 
        href="../assets/adminlte/css/adminlte.css" 
    />

  <!-- OverlayScrollbars -->
    <link
        rel="stylesheet"
        href="../assets/css/overlayscrollbars.min.css"
    />

  <!-- Favicon -->
    <link 
      rel="icon" type="image/png" 
      href="../assets/img/logo.png"
    />

<style>
    
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background: #f9f9f9;
    }

    .app-wrapper{
        position: relative;
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

    .app-sidebar {
        background: <?php echo $bgMenu; ?> !important;
    }

    .navbar {
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

    .table-wrapper{
        width: 95%;
        height: auto;
        overflow-x: auto;
        margin: 20px 0;
        border-radius: 10px;
        padding: 10px;
    }

    th,td,table tbody tr td .btn-sm{
        font-size: 1rem;
    }
    th{
        min-width: 100px;
    }

    .custom-table-utama{
        overflow-x: auto !important;
        display: flex;
        gap: 5px;
    }

    .table-custom:first-child{
        width: 40%;
        max-height: 250px;
    }

    .table-custom th{
        width: 25%;
        min-width: 100px;
    }

    .table-approval th,.table-approval td{
        font-size: .8rem;
    }

    .table-approval th,.table-approval td{
        border: none;
        padding: 5px;
    }

    @media (max-width: 1670px) {
        .btn{
        margin-bottom: 5px;
    }
    }

    .bi-list, .bi-arrows-fullscreen, .bi-fullscreen-exit {
            color: #fff !important;
    }
    @media (max-width: 1024px) {
    .custom-h2{
        margin-bottom: 70px;
    }
    .custom-btn-ctr{
        top: 40px;
    }
    .custom-btn{
        padding: 8px 16px;
        font-size: 24px;
    }
    }
</style>

<style>/* Responsive */
    @media (max-width: 1024px) {
        #res-fullscreen{
            display: none;
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

        /* .dt-orderable-none{
            min-width: 100px;
        } */
            /* Font */
        .custom-font{
            font-size: small;
        }
    }
    @media (max-width: 450px) {
        #date, #clock{
            display: none;
        }
        .custom-main{
            width: 100vw;
        }
        .custom-table-wrapper{
            width: 100vw !important;
            overflow-x: hidden;
        }

        .custom-footer p{
            font-size: 10px;
        }

        .custom-btn-ctr{
            position: relative !important;
            top: 0;
            width: 100%;
            display: flex;
            justify-content: flex-end;
        }
        .custom-h2{
            margin-bottom: 0;
        }

        .custom-container-btn-h2{
            flex-direction: column-reverse !important;
            height: max-content !important;
        }

        .custom-table-aktor{
            overflow-x: auto !important;
        }
        .custom-table-utama{
            overflow-x: auto !important;
            gap: 0;
            flex-direction: column;
        }
        .table-custom:first-child{
            width: 100%;
        }
        .table-custom th{
            width: 100px !important;
        }
        .table-custom th, .table-custom td{
            font-size: 16px;
        }
    }
</style>

<style>/*animista.net*/ 
    .scale-in-center {
	animation: scale-in-center .3s cubic-bezier(0.250, 0.460, 0.450, 0.940) both;
    }
    @keyframes scale-in-center {
    0% {
        transform: scale(0);
        opacity: 1;
    }
    100% {
        transform: scale(1);
        opacity: 1;
    }
    }
    .fade-in {
	animation: fade-in .3s cubic-bezier(0.390, 0.575, 0.565, 1.000) both;
    }
    @keyframes fade-in {
    0% {
        opacity: 0;
    }
    100% {
        opacity: 1;
    }
    }
    .scale-out-center {
	animation: scale-out-center .3s cubic-bezier(0.550, 0.085, 0.680, 0.530) both;
    }
    @keyframes scale-out-center {
    0% {
        transform: scale(1);
        opacity: 1;
    }
    100% {
        transform: scale(0);
        opacity: 1;
    }
    }
    .fade-out {
	animation: fade-out .3s ease-out both;
    }
    @keyframes fade-out {
    0% {
        opacity: 1;
    }
    100% {
        opacity: 0;
    }
    }
    .slide-in-right {
	animation: slide-in-right 0.5s cubic-bezier(0.250, 0.460, 0.450, 0.940) both;
    }
    @keyframes slide-in-right {
    0% {
        transform: translateX(1000px);
        opacity: 0;
    }
    100% {
        transform: translateX(0);
        opacity: 1;
    }
    }
    .slide-out-right {
	animation: slide-out-right 0.5s cubic-bezier(0.550, 0.085, 0.680, 0.530) both;
    }
    @keyframes slide-out-right {
    0% {
        transform: translateX(0);
        opacity: 1;
    }
    100% {
        transform: translateX(1000px);
        opacity: 0;
    }
    }

</style>

<style>
    .bi-list, .bi-arrows-fullscreen, .bi-fullscreen-exit {
            color: #fff !important;
    }
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

</head>
<body class="layout-fixed sidebar-expand-lg bg-body-tertiary overflow-x-hidden">



    <div class="app-wrapper">
    <nav class="app-header navbar navbar-expand bg-body sticky-top"> <!-- Header -->
        <!--begin::Container-->
        <div class="container-fluid">
        <!--begin::Start Navbar Links-->
        <ul class="navbar-nav">
            <li class="nav-item">
            <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button">
                <i class="bi bi-list"></i>
            </a>
            </li>
            <!-- <li class="nav-item">
                <div class="ms-auto">
                <a href="javascript:history.back()" class="btn btn-outline-warning fw-bold"><i class="bi bi-arrow-90deg-left"></i></a>
                </div>
            </li> -->
        </ul>
        <!--end::Start Navbar Links-->
        <!--begin::End Navbar Links-->
        <ul class="navbar-nav ms-auto">
            <!--begin::Fullscreen Toggle-->
            <li class="nav-item">
            <a id="res-fullscreen" class="nav-link" href="#" data-lte-toggle="fullscreen">
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
                    <a href="../logout.php" class="btn btn-outline-danger fw-bold d-flex ps-3 gap-2 mt-2" onclick="return confirm('Yakin ingin logout?')" title="Logout">
                    <i class="bi bi-box-arrow-right fw-bolder"></i><p class="m-0">Logout</p>
                </a>
            </div>
            </div>
        </ul>
        <!--end::End Navbar Links-->
        </div>
        <!--end::Container-->
    </nav>

    <aside class="app-sidebar shadow" data-bs-theme="dark"> <!-- Sidebar -->
        <div class="sidebar-brand" style="border:none;">
        <a href="" class="brand-link">
            <img
            src="../assets/img/logo.png"
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
            <?php if ($_SESSION['hak_akses'] === 'Admin'): ?>
            <li class="nav-item">
                <a href="../index.php" class="nav-link">
                <i class="bi bi-house-fill"></i>
                <p>
                    Dashboard
                </p>
                </a>
            </li>
            <li class="nav-header">
                LIST BERITA ACARA
            </li>
            <!-- List BA Kerusakan -->
            <li class="nav-item">
                <a href="../ba_kerusakan-fix/ba_kerusakan.php" class="nav-link">
                <i class="nav-icon bi bi-newspaper"></i>
                <p>
                    BA Kerusakan
                </p>
                </a>
            </li>
            <?php if ($ptSekarang == "PT.MSAL (HO)"){ ?>
            <!-- List BA Pengembalian -->
            <!-- <li class="nav-item">
                <a href="../ba_pengembalian/ba_pengembalian.php" class="nav-link">
                <i class="nav-icon bi bi-newspaper"></i>
                <p>
                    BA Pengembalian
                </p>
                </a>
            </li> -->
            <?php } ?>
            <li class="nav-item">
                <a href="../ba_pemutihan/ba_pemutihan.php" class="nav-link" aria-disabled="true">
                    <i class="nav-icon bi bi-newspaper"></i>
                    <p>BA Pemutihan</p>
                </a>
            </li>
            <?php if ($ptSekarang == "PT.MSAL (HO)"){ ?>
            <!-- List BA Serah Terima -->
            <li class="nav-item">
                <a href="../ba_serah-terima-asset/ba_serah-terima-asset.php" class="nav-link">
                <i class="nav-icon bi bi-newspaper"></i>
                <p>
                    BA Serah Terima Asset Inventaris
                </p>
                </a>
            </li>
            <?php } ?>

            <li class="nav-item">
                <a href="../ba_mutasi/ba_mutasi.php" class="nav-link">
                    <i class="nav-icon bi bi-newspaper"></i>
                    <p>
                        BA Mutasi
                    </p>
                </a>
            </li>

            <?php endif; ?>
            <li class="nav-header">
                USER
            </li>
            <!-- <?php if ($_SESSION['hak_akses'] === 'Admin'): ?>
            <li class="nav-item">
                <a href="status.php" class="nav-link">
                <i class="nav-icon bi bi-clipboard2-fill"></i>
                <p>
                    Status Approval BA
                </p>
                </a>
            </li>
            <?php endif; ?> -->
            <li class="nav-item position-relative">
                <a href="approval.php" class="nav-link">
                <i class="nav-icon bi bi-clipboard2-check"></i>
                <p>
                    Approve BA
                    
                </p>
                <?php if ($jumlah_approval_notif > 0): ?>
                <span class="position-absolute translate-middle badge rounded-pill bg-danger" style="right: 0;top:20px">
                <?= $jumlah_approval_notif ?>
                </span>
                <?php endif; ?>
                </a>
            </li>
            <!-- <li class="nav-item">
                <a href="riwayat.php" class="nav-link">
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
                    <a href="../master/data_akun/tabel.php" class="nav-link">
                        <i class="nav-icon bi bi-person-circle"></i>
                        <p>
                            Data Akun
                        </p>
                    </a>
                </li>
            <?php endif; ?>
            </ul>
            
        </nav>
        </div>
    </aside>

    <?php
    require_once '../koneksi.php';

    if (!isset($_GET['id'])) {
        echo "ID tidak ditemukan.";
        exit;
    }

    $id = intval($_GET['id']);

    // Ambil detail data berdasarkan ID
    $sql = "SELECT * FROM ba_serah_terima_asset WHERE id = $id";
    $result = $koneksi->query($sql);



    if ($result->num_rows === 0) {
        echo "Data tidak ditemukan.";
        exit;
    }

    $data = $result->fetch_assoc();

$queryKerusakan = "
    SELECT 
        bak.id, 
        bak.approval_1, 
        bak.approval_2,
        bak.approval_3,
        bak.approval_4, 
        bak.peminjam, 
        bak.saksi,
        bak.diketahui,
        bak.pihak_pertama,
        k1.jabatan AS jabatan_aprv1,
        k1.departemen AS departemen_aprv1,
        k2.jabatan AS jabatan_aprv2,
        k2.departemen AS departemen_aprv2,
        k3.jabatan AS jabatan_aprv3,
        k3.departemen AS departemen_aprv3,
        k4.jabatan AS jabatan_aprv4,
        k4.departemen AS departemen_aprv4
    FROM ba_serah_terima_asset bak
    LEFT JOIN data_karyawan k1 
        ON bak.peminjam = k1.nama
    LEFT JOIN data_karyawan k2 
        ON bak.saksi = k2.nama
    LEFT JOIN data_karyawan k3
        ON bak.diketahui = k3.nama
    LEFT JOIN data_karyawan k4
        ON bak.pihak_pertama = k4.nama
    WHERE bak.id = $id
    LIMIT 1
";

$resultKerusakan = $koneksi->query($queryKerusakan);

$peran = $resultKerusakan->fetch_assoc();

    function formatTanggalRomawi($tanggalromawi) {
    $bulan = array(
        1 => 'I',
        'II',
        'III',
        'IV',
        'V',
        'VI',
        'VII',
        'VIII',
        'IX',
        'X',
        'XI',
        'XII'
    );
    $pecah = explode('-', $tanggalromawi);
    return $bulan[(int)$pecah[1]];
    }

    function formatTanggal($tanggal) {
    $bulan = array(
        1 => 'Januari',
        'Februari',
        'Maret',
        'April',
        'Mei',
        'Juni',
        'Juli',
        'Agustus',
        'September',
        'Oktober',
        'November',
        'Desember'
    );
    $pecah = explode('-', $tanggal);
    return $pecah[2] . ' ' . $bulan[(int)$pecah[1]] . ' ' . $pecah[0];
    }
    ?>

    <?php 
    // Ambil gambar yang berelasi dengan berita_acara_kerusakan
    $sqlGambar = "SELECT file_path FROM gambar_ba_kerusakan WHERE ba_kerusakan_id = ?";
    $stmtGambar = $koneksi->prepare($sqlGambar);
    $stmtGambar->bind_param("i", $id);
    $stmtGambar->execute();
    $resultGambar = $stmtGambar->get_result();

    $gambarList = [];
    while ($rowGambar = $resultGambar->fetch_assoc()) {
        $gambarList[] = $rowGambar['file_path'];
    }
    $stmtGambar->close();
    ?>

    <main class="custom-main app-main">

    <section class="custom-table-wrapper table-wrapper bg-white position-relative">
            <?php if (isset($_SESSION['message'])): ?>
                <div class="w-100 d-flex justify-content-center position-absolute" style="height: max-content;">
                    <div class="d-flex p-0 alert alert-success border-0 text-center fw-bold mb-0 position-absolute fade-in" id="infoin-approval" style="transition: opacity 0.5s ease;right:20px;width:max-content;height:max-content;">
                        <div class="d-flex justify-content-center align-items-center bg-success pe-2 ps-2 rounded-start text-white fw-bolder">
                            <i class="bi bi-check-lg"></i>
                        </div>
                        <p class="p-2 m-0" style="font-weight: 500;"><?= htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); ?></p>
                    </div>
                </div>
            <?php endif; ?>
                <!-- <div class="w-100 d-flex justify-content-center position-absolute" style="height: max-content;">
                    <div class="d-flex p-0 alert alert-success border-0 text-center fw-bold mb-0 position-absolute fade-in" id="infoin-approval" style="transition: opacity 0.5s ease;right:20px;width:max-content;height:max-content;">
                        <div class="d-flex justify-content-center align-items-center bg-success pe-2 ps-2 rounded-start text-white fw-bolder">
                            <i class="bi bi-check-lg"></i>
                        </div>
                        <p class="p-2 m-0" style="font-weight: 500;">Tester</p>
                    </div>
                </div> -->

        <div class="custom-container-btn-h2 d-flex align-items-center justify-content-center position-relative">
            <div class="custom-btn-ctr position-absolute" style="left: 0;">
                <a class='custom-btn btn btn-primary' href='surat_output_serah_terima_asset.php?id=<?= $data['id'] ?>' target='_blank'><i class="bi bi-file-earmark-text-fill"></i></a>
                <?php
                    $sessionUser = isset($_SESSION['nama']) ? $_SESSION['nama'] : '';

                    $namaPeminjam   = isset($data['peminjam']) ? trim($data['peminjam']) : '';
                    $namaSaksi      = isset($data['saksi']) ? trim($data['saksi']) : '';
                    $namaDiketahui  = isset($data['diketahui']) ? trim($data['diketahui']) : '';
                    $namaDireksiMIS = isset($data['pihak_pertama']) ? trim($data['pihak_pertama']) : '';
                    $approval1      = isset($data['approval_1']) ? intval($data['approval_1']) : 0;
                    $approval2      = isset($data['approval_2']) ? intval($data['approval_2']) : 0;
                    $approval3      = isset($data['approval_3']) ? intval($data['approval_3']) : 0;
                    $approval4      = isset($data['approval_4']) ? intval($data['approval_4']) : 0;

                    $tampilTombol = false;

                    if ($sessionUser !== '') {
                        if ($sessionUser === $namaPeminjam && $approval1 === 0) {
                            $tampilTombol = true;
                        } elseif ($sessionUser === $namaSaksi && $approval2 === 0) {
                            $tampilTombol = true;
                        } elseif ($sessionUser === $namaDiketahui && $approval3 === 0) {
                            $tampilTombol = true;
                        } elseif ($sessionUser === $namaDireksiMIS && $approval4 === 0) {
                            $tampilTombol = true;
                        }
                    }

                    if ($tampilTombol) {
                        $approvalField = '';
                        $jenis_ba = 'st_asset'; 

                        if ($sessionUser === $namaPeminjam) {
                            $approvalField = 'approval_1';
                        } elseif ($sessionUser === $namaSaksi) {
                            $approvalField = 'approval_2';
                        } elseif ($sessionUser === $namaDiketahui) {
                            $approvalField = 'approval_3';
                        }  elseif ($sessionUser === $namaDireksiMIS) {
                            $approvalField = 'approval_4';
                        } 

                        
                        if ($approvalField !== '') {
                            echo '<a href="#" 
                                    class="custom-btn btn btn-success ms-1 tombolSetuju d-none"
                                    data-id="' . intval($data['id']) . '" 
                                    data-approval="' . $approvalField . '" 
                                    data-jenis="' . $jenis_ba . '">
                                    <i class="bi bi-check-circle"></i>
                                </a>';
                        }
                    }
                ?>
            </div>
            
            <h2 class="custom-h2">Detail Data Serah Terima Penggunaan Asset Inventaris Nomor <?= htmlspecialchars($data['nomor_ba']) ?> Periode <?php echo formatTanggalRomawi($data['tanggal']); ?></h2>
        </div>
        <div class="d-flex justify-content-center flex-column gap-1">
            <!-- <div class="card" style="width: fit-content;height:300px;font-size: 12px;">
                <div class="card-header"><h5>Status Approval</h3></div>
                <div class="card-body d-flex flex-column justify-content-around">
                    
                    <h6 class="m-0" style="font-size: 12px;">Pembuat</h6>
                    <?php echo $peran['jabatan_aprv1'] . " " . $peran['departemen_aprv1'] ?>
                    <span class="border fw-bold <?= $data['approval_1'] == 1 ? 'bg-success-subtle border-success-subtle text-success' : 'bg-warning-subtle border-warning-subtle text-warning' ?>" style="border-radius: 6px; padding: 6px 12px;width: fit-content;font-size: 12px;">
                        <?= htmlspecialchars($data['approval_1'] == 1 ? 'Disetujui' : 'Menunggu') ?>
                    </span>
                    <h6 class="m-0" style="font-size: 12px;">Yang Menyetujui</h6>
                    <?php echo $peran['jabatan_aprv2'] . " " . $peran['departemen_aprv2'] ?>
                    <span class="border fw-bold <?= $data['approval_2'] == 1 ? 'bg-success-subtle border-success-subtle text-success' : 'bg-warning-subtle border-warning-subtle text-warning' ?>" style="border-radius: 6px; padding: 6px 12px;width: fit-content;font-size: 12px;">
                        <?= htmlspecialchars($data['approval_2'] == 1 ? 'Disetujui' : 'Menunggu') ?>
                    </span>
                </div>
            </div> -->
        
        <div class="custom-table-aktor">
        <table class="table table-approval" style="min-width: 100px; width: 600px;">
            <thead>
                <tr>
                    <?php if ($namaPeminjam !== "-"): ?>
                    <th>Peminjam</th>
                    <?php endif; ?>
                    
                    <?php if ($namaSaksi !== "-"): ?>
                    <th>Saksi</th>
                    <?php endif; ?>

                    <?php if ($namaDiketahui !== "-"): ?>
                        <th>Diketahui</th>
                    <?php endif; ?>

                    <?php if ($namaDireksiMIS !== "-"): ?>
                        <th>Direksi MIS</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                        <tr>
                            <?php if ($namaPeminjam !== "-"): ?>
                            <td><?php echo $peran['jabatan_aprv1'] . " " . $peran['departemen_aprv1'] ?></td>
                            <?php endif; ?>

                            <?php if ($namaSaksi !== "-"): ?>
                            <td><?php echo $peran['jabatan_aprv2'] . " " . $peran['departemen_aprv2'] ?></td>
                            <?php endif; ?>
                            
                            <?php if ($namaDiketahui !== "-"): ?>
                            <td><?php echo $peran['jabatan_aprv3'] . " " . $peran['departemen_aprv3'] ?></td>
                            <?php endif; ?>

                            <?php if ($namaDireksiMIS !== "-"): ?>
                                <td><?php echo $peran['jabatan_aprv4'] . " " . $peran['departemen_aprv4'] ?></td>
                            <?php endif; ?>

                            
                        </tr>
                        <tr>
                            <?php if ($namaPeminjam !== "-"): ?>
                            <td>
                                <span class="border fw-bold <?= $data['approval_1'] == 1 ? 'bg-success-subtle border-success-subtle text-success' : 'bg-warning-subtle border-warning-subtle text-warning' ?>" style="border-radius: 6px; padding: 6px 12px;">
                                    <?= htmlspecialchars($data['approval_1'] == 1 ? 'Disetujui' : 'Menunggu') ?>
                                </span> 
                            </td>
                            <?php endif; ?>

                            <?php if ($namaSaksi !== "-"): ?>
                            <td>
                                <span class="border fw-bold <?= $data['approval_2'] == 1 ? 'bg-success-subtle border-success-subtle text-success' : 'bg-warning-subtle border-warning-subtle text-warning' ?>" style="border-radius: 6px; padding: 6px 12px;">
                                    <?= htmlspecialchars($data['approval_2'] == 1 ? 'Disetujui' : 'Menunggu') ?>
                                </span>
                            </td>
                            <?php endif; ?>
                            
                            <?php if ($namaDiketahui !== "-"): ?>
                            <td>
                                <span class="border fw-bold <?= $data['approval_3'] == 1 ? 'bg-success-subtle border-success-subtle text-success' : 'bg-warning-subtle border-warning-subtle text-warning' ?>" style="border-radius: 6px; padding: 6px 12px;">
                                    <?= htmlspecialchars($data['approval_3'] == 1 ? 'Disetujui' : 'Menunggu') ?>
                                </span>
                            </td>
                            <?php endif; ?>

                            <?php if ($namaDireksiMIS !== "-"): ?>
                            <td>
                                <span class="border fw-bold <?= $data['approval_4'] == 1 ? 'bg-success-subtle border-success-subtle text-success' : 'bg-warning-subtle border-warning-subtle text-warning' ?>" style="border-radius: 6px; padding: 6px 12px;">
                                    <?= htmlspecialchars($data['approval_4'] == 1 ? 'Disetujui' : 'Menunggu') ?>
                                </span>
                            </td>
                            <?php endif; ?>

                            
                        </tr>
                        
            </tbody>
        </table>
        </div>
        <div class="custom-table-utama">
        <table class="table-custom table table-bordered table-striped">
            <tbody>
                <tr>
                    <th>Nomor BA</th>
                    <td>
                        <?= htmlspecialchars($data['nomor_ba']) ?>
                    </td>
                </tr>
                
                <tr>
                    <th>Tanggal</th>
                    <td><?php echo formatTanggal(date('Y-m-d', strtotime($data['tanggal']))); ?></td>
                </tr>
                <tr>
                    <th>Lokasi</th>
                    <td><?= htmlspecialchars($data['pt']) ?></td>
                </tr>
                <tr>
                    <th>Pengguna</th>
                    <td><?= htmlspecialchars($data['peminjam']) ?></td>
                </tr>
                
                
            </tbody>
        </table>
        <table class="table-custom table table-bordered table-striped">
            <tbody>

                
                <tr>
                    <th>Jenis Perangkat</th>
                    <td><?= htmlspecialchars($data['categories']) ?></td>
                </tr>
                

                
                <tr>
                    <th>Nomor PO</th>
                    <td><?= htmlspecialchars($data['no_po']) ?></td>
                </tr>
                

                
                <tr>
                    <th>Kode Asset</th>
                    <td><?= htmlspecialchars($data['kode_assets']) ?></td>
                </tr>
                

                
                <tr>
                    <th>Tanggal Pembelian</th>
                    <td><?= formatTanggal(date('Y-m-d', strtotime($data['tgl_pembelian']))) ?></td>
                </tr>
                

                
                <tr>
                    <th>Merek</th>
                    <td><?= htmlspecialchars($data['merek']) ?></td>
                </tr>
                

                
                <tr>
                    <th>Serial Number</th>
                    <td><?= htmlspecialchars($data['sn']) ?></td>
                </tr>
                

                
                <tr>
                    <th>CPU</th>
                    <td><?= htmlspecialchars($data['cpu']) ?></td>
                </tr>
                

                
                <tr>
                    <th>RAM</th>
                    <td><?= htmlspecialchars($data['ram']) ?></td>
                </tr>
                

                
                <tr>
                    <th>Penyimpanan</th>
                    <td><?= htmlspecialchars($data['storage']) ?></td>
                </tr>
                

                
                <tr>
                    <th>Graphic Card</th>
                    <td><?= htmlspecialchars($data['gpu']) ?></td>
                </tr>
                

                
                <tr>
                    <th>Display</th>
                    <td><?= htmlspecialchars($data['display']) ?></td>
                </tr>
                

                
                <tr>
                    <th>Lainnya</th>
                    <td><?= htmlspecialchars($data['lain']) ?></td>
                </tr>
                

                <?php if ($data['merk_monitor'] !== '' && $data['merk_monitor'] !== '-') : ?>
                <tr>
                    <th>Merk Monitor</th>
                    <td><?= htmlspecialchars($data['merk_monitor']) ?></td>
                </tr>
                <?php endif; ?>

                <?php if ($data['sn_monitor'] !== '' && $data['sn_monitor'] !== '-') : ?>
                <tr>
                    <th>SN Monitor</th>
                    <td><?= htmlspecialchars($data['sn_monitor']) ?></td>
                </tr>
                <?php endif; ?>

                <?php if ($data['merk_keyboard'] !== '' && $data['merk_keyboard'] !== '-') : ?>
                <tr>
                    <th>Merk Keyboard</th>
                    <td><?= htmlspecialchars($data['merk_keyboard']) ?></td>
                </tr>
                <?php endif; ?>

                <?php if ($data['sn_keyboard'] !== '' && $data['sn_keyboard'] !== '-') : ?>
                <tr>
                    <th>SN Keyboard</th>
                    <td><?= htmlspecialchars($data['sn_keyboard']) ?></td>
                </tr>
                <?php endif; ?>

                <?php if ($data['merk_mouse'] !== '' && $data['merk_mouse'] !== '-') : ?>
                <tr>
                    <th>Merk Mouse</th>
                    <td><?= htmlspecialchars($data['merk_mouse']) ?></td>
                </tr>
                <?php endif; ?>

                <?php if ($data['sn_mouse'] !== '' && $data['sn_mouse'] !== '-') : ?>
                <tr>
                    <th>SN Mouse</th>
                    <td><?= htmlspecialchars($data['sn_mouse']) ?></td>
                </tr>
                <?php endif; ?>

            </tbody>
        </table>

        </div>
        </div>
        <div class="w-100 p-1" style="height:max-content; min-width:300px;">
            <div class="">
                <h6>Histori & Pending Perubahan</h6>
            </div>
            <table id="popupDetailTable" class="table table-bordered table-striped" 
            style="font-size:16px; width: 100%;"
            >
                <thead>
                    <tr>
                        <th class="text-start">Tanggal Edit</th>
                        <th class="text-start">Status</th>
                        <th class="text-start">Alasan Edit</th>
                        <th class="text-start">Alasan Tolak</th>
                        <th class="text-start">Tanggal Surat</th>
                        <th class="text-start">Nomor Surat</th>
                        <th class="text-start">Jenis Perangkat</th>
                        <th class="text-start">Merek</th>
                        <th class="text-start">Nomor PO</th>
                        <th class="text-start">SN</th>
                        <th class="text-start">Tanggal Pembelian</th>
                    </tr>
                </thead>
<tbody>
<?php
$queryHist = $koneksi->prepare("
    SELECT created_at, pending_status, alasan_edit, alasan_tolak, tanggal, nomor_ba,
        categories, merek, no_po, sn, tgl_pembelian 
    FROM history_n_temp_ba_serah_terima_asset
    WHERE id_ba = ?
    AND NOT (pending_status = 1 AND status = 0)
    ORDER BY created_at DESC
");
$queryHist->bind_param("i", $id);
$queryHist->execute();
$resHist = $queryHist->get_result();


while ($row = $resHist->fetch_assoc()):

$statusText = "History";
if ($row['pending_status'] == 1) {
    $statusText = "Menunggu";
} elseif ($row['pending_status'] == 2) {
    $statusText = "Ditolak";
}

$color = "";
if ($row['pending_status'] == 1) {
    $color = "background-color:#fff3cd;"; // kuning
} elseif ($row['pending_status'] == 2) {
    $color = "background-color:#f8d7da;"; // merah
}
?>
    <tr>
        <td class="text-start" style="<?= $color ?>"><?= htmlspecialchars($row['created_at']) ?></td>
        <td class="text-start" style="<?= $color ?>"><?= htmlspecialchars($statusText) ?></td>
        <td class="text-start" style="<?= $color ?>"><?= htmlspecialchars($row['alasan_edit']) ?></td>
        <td class="text-start" style="<?= $color ?>"><?= htmlspecialchars($row['alasan_tolak']) ?></td>
        <td class="text-start" style="<?= $color ?>"><?= htmlspecialchars($row['tanggal']) ?></td>
        <td class="text-start" style="<?= $color ?>"><?= htmlspecialchars($row['nomor_ba']) ?></td>
        <td class="text-start" style="<?= $color ?>"><?= htmlspecialchars($row['categories']) ?></td>
        <td class="text-start" style="<?= $color ?>"><?= htmlspecialchars($row['merek']) ?></td>
        <td class="text-start" style="<?= $color ?>"><?= htmlspecialchars($row['no_po']) ?></td>
        <td class="text-start" style="<?= $color ?>"><?= htmlspecialchars($row['sn']) ?></td>
        <td class="text-start" style="<?= $color ?>"><?= htmlspecialchars($row['tgl_pembelian']) ?></td>
    </tr>
<?php endwhile; ?>
</tbody>

            </table>
        </div>
        
    </section>
        
    </main>

        <!--Awal::Footer Content-->
        <footer class="custom-footer d-flex position-relative p-2" style="border-top: whitesmoke solid 1px; box-shadow: 0px 7px 10px black; color: grey;">
            <p class="position-absolute" style="right: 15px;bottom:7px;color: grey;"><strong>Version </strong>1.1.0</p>
        <p class="pt-2 ps-1"><strong>Copyright &copy 2025</p></strong><p class="pt-2 ps-1 fw-bold text-primary">MIS MSAL.</p><p class="pt-2 ps-1"> All rights reserved</p>
        </footer>
        <!--Akhir::Footer Content-->

    </div>
    
    <script src="../assets/js/jquery-3.7.1.min.js"></script>
    <script src="../assets/js/datatables.min.js"></script>

<script>
$(document).on("click", ".tombolSetuju", function(e) {
    e.preventDefault();

    var id        = $(this).data("id");
    var approval  = $(this).data("approval");
    var jenis_ba  = $(this).data("jenis");

    if (!id || !approval || !jenis_ba) {
        alert("Data tidak lengkap.");
        return;
    }

    if (!confirm("Apakah Anda yakin ingin menyetujui berita acara ini?")) {
        return;
    }

    $.ajax({
        url: "proses_approve.php",
        method: "POST",
        contentType: "application/json",
        data: JSON.stringify({
            id: id,
            approvals: [approval],
            action: "approve",
            jenis_ba: jenis_ba
        }),
        success: function(response) {
            if (response.success) {
                // alert("Berhasil menyetujui.");
                location.reload();
            } else {
                alert("Gagal: " + (response.message || "Terjadi kesalahan."));
            }
        },
        error: function(xhr, status, error) {
            alert("Error AJAX: " + error);
        }
    });
});
</script>

<script>//DataTables
    $(document).ready(function () {
        $('#popupDetailTable').DataTable({
        paging: false,
        searching: false,
        info: false,
        ordering: false,
        scrollY: "230px",
        scrollCollapse: true,
        autoWidth: true,
        language: {
            url: "../assets/json/id.json"
        }
        });
    });
</script>

<!-- Bootstrap 5 -->
<script src="../assets/bootstrap-5.3.6-dist/js/bootstrap.min.js"></script>

<!-- popperjs Bootstrap 5 -->
<script src="../assets/js/popper.min.js"></script>

<!-- AdminLTE -->
<script src="../assets/adminlte/js/adminlte.js"></script>

<!-- OverlayScrollbars -->
<script src="../assets/js/overlayscrollbars.browser.es6.min.js"></script>

<script>
        const alert = document.getElementById('infoin-approval');
        setTimeout(() => {
                alert.classList.add('fade-out');
                alert.classList.remove('fade-in');
            }, 3000);
        setTimeout(() => {
            alert.style.display = 'none';
            }, 3500);  
</script>

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

<script> //Konfigurasi OverlayScrollbars (gatau ini efeknya dimana tapi jangan diapus)

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
