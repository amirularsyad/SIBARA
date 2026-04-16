<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['nama'])) {
    header("Location: ../login_registrasi.php");
    exit;
}

//setup akses
include '../koneksi.php';
$manajemen_akun_akses = 0;
if (isset($_SESSION['nama'])) {
    $namaLogin = $_SESSION['nama'];
    $sqlAkses = "SELECT manajemen_akun_akses FROM akun_akses WHERE nama = ? LIMIT 1";
    if ($stmt = $koneksi->prepare($sqlAkses)) {
        $stmt->bind_param("s", $namaLogin);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $rowAkses = $res->fetch_assoc()) {
            $manajemen_akun_akses = (int)$rowAkses['manajemen_akun_akses'];
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

$namaUser = $_SESSION['nama'];

$filterPT      = isset($_GET['pt']) ? $_GET['pt'] : '';
$filterJenisBA = isset($_GET['jenis_ba']) ? $_GET['jenis_ba'] : 'kerusakan';
$filterTahun   = isset($_GET['tahun']) ? $_GET['tahun'] : '';
$filterBulan   = isset($_GET['bulan']) ? $_GET['bulan'] : '';


$filtersKerusakan = [];
$filtersPengembalian = [];
$filtersNotebook = [];

if (!empty($filterPT)) {
    $filtersKerusakan[]    = "pt = '" . $koneksi->real_escape_string($filterPT) . "'";
    $filtersPengembalian[] = "bap.lokasi_penerima = '" . $koneksi->real_escape_string($filterPT) . "'";
    $filtersNotebook[]     = "pt = '" . $koneksi->real_escape_string($filterPT) . "'";
}

if (!empty($filterTahun)) {
    $filtersKerusakan[]    = "YEAR(bak.tanggal) = " . intval($filterTahun);
    $filtersPengembalian[] = "YEAR(bap.tanggal) = " . intval($filterTahun);
    $filtersNotebook[]     = "YEAR(ban.tanggal) = " . intval($filterTahun);
}

if (!empty($filterBulan)) {
    $filtersKerusakan[]    = "MONTH(bak.tanggal) = " . intval($filterBulan);
    $filtersPengembalian[] = "MONTH(bap.tanggal) = " . intval($filterBulan);
    $filtersNotebook[]     = "MONTH(ban.tanggal) = " . intval($filterBulan);
}

$whereKerusakan    = $filtersKerusakan ? " WHERE " . implode(" AND ", $filtersKerusakan) : "";
$wherePengembalian = $filtersPengembalian ? "WHERE " . implode(" AND ", $filtersPengembalian) : "";
$whereNotebook = $filtersNotebook ? "WHERE " . implode(" AND ", $filtersNotebook) : "";

// Ambil data Berita Acara Kerusakan
$baseQueryKerusakan = "
    SELECT 
        bak.id, 
        bak.tanggal, 
        bak.nomor_ba, 
        bak.approval_1, 
        bak.approval_2,
        bak.autograph_1,
        bak.autograph_2, 
        bak.nama_aprv1, 
        bak.nama_aprv2,
        k1.jabatan AS jabatan_aprv1,
        k1.departemen AS departemen_aprv1,
        k2.jabatan AS jabatan_aprv2,
        k2.departemen AS departemen_aprv2
    FROM berita_acara_kerusakan bak
    LEFT JOIN data_karyawan k1 
        ON bak.nama_aprv1 = k1.nama
    LEFT JOIN data_karyawan k2 
        ON bak.nama_aprv2 = k2.nama
    
";
$queryKerusakan = $baseQueryKerusakan ."
    $whereKerusakan
    ORDER BY bak.tanggal DESC, bak.nomor_ba DESC
";
$resultKerusakan = $koneksi->query($queryKerusakan);

$whereUserKerusakan = $whereKerusakan
    . (!empty($whereKerusakan) ? " AND " : " WHERE ") . "
        (
            bak.nama_aprv1 = '" . $koneksi->real_escape_string($namaUser) . "'
            OR bak.nama_aprv2 = '" . $koneksi->real_escape_string($namaUser) . "'
        )";
$queryUserKerusakan = $baseQueryKerusakan . "
    $whereUserKerusakan
    ORDER BY bak.tanggal DESC, bak.nomor_ba DESC
";
$resultUserKerusakan = $koneksi->query($queryUserKerusakan);

// Query dasar (SELECT + JOIN tanpa WHERE)
$baseQuery = "
    SELECT 
        bap.id, 
        bap.tanggal, 
        bap.nomor_ba, 
        bap.approval_1, 
        bap.approval_2,
        bap.approval_3,
        bap.autograph_1,
        bap.autograph_2,
        bap.autograph_3, 
        bap.nama_pengembali, 
        bap.nama_penerima,
        bap.diketahui,
        k1.jabatan AS jabatan_aprv1,
        k1.departemen AS departemen_aprv1,
        k2.jabatan AS jabatan_aprv2,
        k2.departemen AS departemen_aprv2,
        k3.jabatan AS jabatan_aprv3,
        k3.departemen AS departemen_aprv3
    FROM berita_acara_pengembalian bap
    LEFT JOIN data_karyawan k1 
        ON bap.nama_pengembali = k1.nama
    LEFT JOIN data_karyawan k2 
        ON bap.nama_penerima = k2.nama
    LEFT JOIN data_karyawan k3
        ON TRIM(SUBSTRING_INDEX(bap.diketahui, ' - ', 1)) = k3.nama
";

// Query data pengembalian
$queryPengembalian = $baseQuery . "
    $wherePengembalian
    ORDER BY bap.tanggal DESC, bap.nomor_ba DESC
";
$resultPengembalian = $koneksi->query($queryPengembalian);

// Query identifikasi (gabungan filter + identifikasi user)
$whereIdentifikasi = $wherePengembalian 
    . (!empty($wherePengembalian) ? " AND " : " WHERE ") . "
    (
        bap.nama_pengembali = '" . $koneksi->real_escape_string($namaUser) . "'
        OR bap.nama_penerima = '" . $koneksi->real_escape_string($namaUser) . "'
        OR TRIM(SUBSTRING_INDEX(bap.diketahui, ' - ', 1)) = '" . $koneksi->real_escape_string($namaUser) . "'
    )";

$queryIdentifikasi = $baseQuery . "
    $whereIdentifikasi
    ORDER BY bap.tanggal DESC, bap.nomor_ba DESC
";
$resultIdentifikasi = $koneksi->query($queryIdentifikasi);

$baseQueryNotebook = "
    SELECT 
            ban.id,
            ban.pertama,
            ban.nama_peminjam, 
            ban.saksi,
            ban.diketahui,
            ban.tanggal,
            ban.nomor_ba,
            ban.approval_1,
            ban.approval_2,
            ban.approval_3,
            ban.approval_4,
            ban.autograph_1,
            ban.autograph_2,
            ban.autograph_3,
            ban.autograph_4,
            ban.jabatan_pertama,
            k2.jabatan AS jabatan_aprv2,
            k2.departemen AS departemen_aprv2,
            k3.jabatan AS jabatan_aprv3,
            k3.departemen AS departemen_aprv3,
            k4.jabatan AS jabatan_aprv4,
            k4.departemen AS departemen_aprv4
        FROM ba_serah_terima_notebook ban
        LEFT JOIN data_karyawan k2 
            ON ban.nama_peminjam = k2.nama
        LEFT JOIN data_karyawan k3 
            ON ban.saksi = k3.nama
        LEFT JOIN data_karyawan k4 
            ON ban.diketahui = k4.nama    
";
// Query data pengembalian
$queryNotebook = $baseQueryNotebook . "
    $whereNotebook
    ORDER BY ban.tanggal DESC, ban.nomor_ba DESC
";
$resultNotebook = $koneksi->query($queryNotebook);

// Query identifikasi (gabungan filter + identifikasi user)
$whereUserNotebook = $whereNotebook 
    . (!empty($whereNotebook) ? " AND " : " WHERE ") . "
    (
        ban.pertama = '" . $koneksi->real_escape_string($namaUser) . "'
        OR ban.nama_peminjam = '" . $koneksi->real_escape_string($namaUser) . "'
        OR ban.saksi = '" . $koneksi->real_escape_string($namaUser) . "'
        OR ban.diketahui = '" . $koneksi->real_escape_string($namaUser) . "'
    )";

$queryUserNotebook = $baseQueryNotebook . "
    $whereUserNotebook
    ORDER BY ban.tanggal DESC, ban.nomor_ba DESC
";
$resultUserNotebook = $koneksi->query($queryUserNotebook);


// Helper badge status
function statusBadge($approval) {
    if ($approval == 1) {
        return "<i class='bi bi-check-square-fill text-success fs-6'></i>";
    }elseif ($approval == 2){
        return "<i class='bi bi-x-square-fill text-danger fs-6'></i>";
    }
    elseif ($approval == 0) {
        return "<i class='bi bi-hourglass text-warning fs-6'></i><div class='tombolAutographPopup btn btn-warning btn-sm' style='float: right;'><i class='bi bi-pencil-square'></i></div>";
    }
    else{
        return "<i class='bi bi-question-circle text-info fs-6'></i>";
    }
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Approve BA</title>

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

    <link 
        rel="icon" type="image/png" 
        href="../assets/css/datatables.min.css"
    />

    <link 
        rel="stylesheet" 
        href="../assets/css/datatables.min.css"
    />

<style> /* Main Styles */
    
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

    .app-sidebar{
            background: linear-gradient(to bottom right, #3e02be 0%,rgb(1, 64, 159) 50%, rgb(2, 59, 159) 100%) !important;
    }

    .navbar{
            background: linear-gradient(to right, rgb(1, 64, 159) 0%, rgb(2, 77, 190) 60%, rgb(2, 77, 207) 100%) !important;
    }

    h2, h3 {
        color: #2c3e50;
        text-align: center;
        margin-bottom: 25px;
    }

    .app-main{
        display: flex;
        align-items: center;
        margin-top: 40px;
    }

    /* style table */

    .table-wrapper{
        width: 97%;
        height: auto;
        overflow-x: auto;
        margin: 20px 0;
        border-radius: 10px;
        padding: 10px;
    }

    th,td,table tbody tr td .btn-sm{
        font-size: .9rem;
    }

    th, td{
        text-align: center !important;
    }

    /* td:first-child { width: 5%; text-align: center; } 
    td:nth-child(2) { width: 10%; }  
    td:nth-child(3) { width: 10%; }  
    td:nth-child(4) { width: 20%; }  
    td:nth-child(5) { width: 14%; }  
    td:nth-child(6) { width: 14%; }  
    td:nth-child(7) { width: 14%; }  
    td:last-child { width: 13%; }   */

    

    .popup-box{
        display: none;
    }
    .popup-bg{
        display:none;
        background-color: rgba(0,0,0,0.5);
        z-index: 5;
    }

    .aktifPopup{
        display:flex;
    }

    @media (max-width: 1670px) {
        .btn{
        margin-bottom: 5px;
    }
    }

    .bi-list, .bi-arrows-fullscreen, .bi-fullscreen-exit {
            color: #fff !important;
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
        .custom-popup{
            left: 12vw !important;
        }
        .custom-popup2{
            left: 15vw !important;
        }
        .dt-orderable-none{
            max-width: 80px;
        }
        /* Font */
        .custom-font{
            font-size: small;
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

<style> /* scroll styling */
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
<body  class="layout-fixed sidebar-expand-lg bg-body-tertiary overflow-x-hidden">

    <script src="../assets/js/jquery-3.7.1.min.js"></script>
    <script src="../assets/js/datatables.min.js"></script>

    <div class="app-wrapper">

    <nav class="app-header navbar navbar-expand bg-body sticky-top" style="z-index: 10;"> <!-- Header -->
        <!--begin::Container-->
        <div class="container-fluid">
        <!--begin::Start Navbar Links-->
        <ul class="navbar-nav">
            <li class="nav-item">
            <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button">
                <i class="bi bi-list"></i>
            </a>
            </li>
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
                        <a href="../logout.php" class="btn btn-outline-danger fw-bold d-flex ps-3 gap-2 mt-2 d-flex" onclick="return confirm('Yakin ingin logout?')" title="Logout">
                        <i class="bi bi-box-arrow-right fw-bolder"></i><p class="m-0">Logout</p>
                    </a>
                </div>
        </ul>
        <!--end::End Navbar Links-->
        </div>
        <!--end::Container-->
    </nav>

    <aside class="app-sidebar shadow" data-bs-theme="dark"> <!-- Sidebar -->
        <div class="sidebar-brand" style="border:none;">
        <a href="
            <?php if ($_SESSION['hak_akses'] === 'Admin'): ?>../index.php
            <?php elseif ($_SESSION['hak_akses'] === 'User'): ?>#
            <?php endif; ?>
            " class="brand-link">
            <img
            src="../assets/img/logo.png"
            alt="MSAL Logo"
            class="brand-image opacity-75 shadow"
            />
            <span class="brand-text fw-bold">Berita Acara</span>
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
                <a href="../ba_kerusakan-fix/ba_kerusakan.php" class="nav-link" aria-disabled="true">
                <i class="nav-icon bi bi-newspaper "></i>
                <p>
                    BA Kerusakan
                </p>
                </a>
            </li>
            <!-- List BA Pengembalian -->
            <li class="nav-item">
                <a href="../ba_pengembalian/ba_pengembalian.php" class="nav-link">
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
                        <a href="../ba_serah-terima-notebook/ba_serah-terima-notebook.php" class="nav-link">
                        <i class="bi bi-laptop"></i>
                        <p>
                            Notebook
                        </p>
                        </a>
                    </li>
                </ul>
            </li>
            <?php endif; ?>
            <li class="nav-header">
                USER
            </li>
            
            <li class="nav-item">
                <a href="#" class="nav-link">
                <i class="nav-icon bi bi-clipboard2-check text-white"></i>
                <p class="text-white">
                    Approve BA
                </p>
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

    <main class="app-main"><!-- Main Content -->
        <section class="table-wrapper bg-white position-relative overflow-visible">
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
                
            <h2>Daftar Approval BA 
            <?php 
            if ($_SESSION['hak_akses'] === 'User') {
                echo "Anda";
            }
            ?>
            </h2>

            <?php 
            
            if ($_SESSION['hak_akses'] === 'Admin') {
                if ($filterJenisBA === 'kerusakan') {
                    $resultAkses        = $resultKerusakan;    
                    $isKerusakan        = true;
                    $isPengembalian     = false;
                    $isNotebook         = false;
                } elseif ($filterJenisBA === 'pengembalian') {
                    $resultAkses        = $resultPengembalian; 
                    $isKerusakan        = false;
                    $isPengembalian     = true;
                    $isNotebook         = false;
                } elseif ($filterJenisBA === 'notebook'){
                    $resultAkses        = $resultNotebook; 
                    $isKerusakan        = false;
                    $isPengembalian     = false;
                    $isNotebook         = true;
                }
            } else {
                if ($filterJenisBA === 'kerusakan') {
                    $resultAkses        = $resultUserKerusakan; 
                    $isKerusakan        = true;
                    $isPengembalian     = false;
                    $isNotebook         = false;
                } elseif ($filterJenisBA === 'pengembalian') {
                    $resultAkses        = $resultIdentifikasi;  
                    $isKerusakan        = false;
                    $isPengembalian     = true;
                    $isNotebook         = false;
                } elseif ($filterJenisBA === 'notebook'){
                    $resultAkses        = $resultUserNotebook; 
                    $isKerusakan        = false;
                    $isPengembalian     = false;
                    $isNotebook         = true;
                }
            }
            ?>
            
            <!-- Filter -->
            <form method="get" class="mb-3 d-flex gap-2 flex-wrap align-items-end"> 
                <!-- Filter PT -->
                <div>
                    <label class="form-label">PT</label>
                    <select name="pt" class="form-select" onchange="this.form.submit()">
                        <option value="">Semua</option>
                        <option value="PT.MSAL (HO)" <?= ($filterPT === 'PT.MSAL (HO)') ? 'selected' : '' ?>>PT.MSAL (HO)</option>
                    </select>
                </div>

                <!-- Filter Jenis BA (fixed hanya pengembalian) -->
                <div>
                    <label class="form-label">Jenis BA</label>
                    <select name="jenis_ba" class="form-select" onchange="this.form.submit()">
                        <option value="kerusakan" <?= ($filterJenisBA === 'kerusakan') ? 'selected' : '' ?>>BA Kerusakan</option>
                        <option value="pengembalian" <?= ($filterJenisBA === 'pengembalian') ? 'selected' : '' ?>>BA Pengembalian</option>
                        <option value="notebook" <?= ($filterJenisBA === 'notebook') ? 'selected' : '' ?>>BA Serah Terima Notebook</option>
                    </select>
                </div>

                <!-- Filter Tahun -->
                <div>
                    <label class="form-label">Tahun</label>
                    <select name="tahun" class="form-select" onchange="this.form.submit()">
                        <option value="">Semua</option>
                        <?php
                        if ($filterJenisBA === 'kerusakan') {
                            $tahunQuery = "SELECT DISTINCT YEAR(tanggal) as tahun FROM berita_acara_kerusakan ORDER BY tahun DESC";
                        } elseif ($filterJenisBA === 'pengembalian') {
                            $tahunQuery = "SELECT DISTINCT YEAR(tanggal) as tahun FROM berita_acara_pengembalian ORDER BY tahun DESC";
                        } elseif ($filterJenisBA === 'notebook') {
                            $tahunQuery = "SELECT DISTINCT YEAR(tanggal) as tahun FROM ba_serah_terima_notebook ORDER BY tahun DESC";
                        }
                        $tahunRes = $koneksi->query($tahunQuery);
                        while ($t = $tahunRes->fetch_assoc()) {
                            $sel = ($filterTahun == $t['tahun']) ? 'selected' : '';
                            echo "<option value='{$t['tahun']}' $sel>{$t['tahun']}</option>";
                        }
                        ?>
                    </select>
                </div>

                <!-- Filter Bulan -->
                <div>
                    <label class="form-label">Bulan</label>
                    <select name="bulan" class="form-select" onchange="this.form.submit()">
                        <option value="">Semua</option>
                        <?php
                        $bulanIndo = [
                            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
                        ];
                        foreach ($bulanIndo as $num => $nama) {
                            $sel = ($filterBulan == $num) ? 'selected' : '';
                            echo "<option value='$num' $sel>$nama</option>";
                        }
                        ?>
                    </select>
                </div>
                <!-- <div>
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="">Semua</option>
                    </select>
                </div> -->
            </form>

            <div class="" style="width: 100%;">

                <table id="myTable" class="table table-bordered table-striped text-center" style="text-align: center !important;">
                    <thead class="bg-secondary">
                        <tr class="tabel-judul">
                            <th rowspan="2">No</th>
                            <th class="p-3" rowspan="2">Tanggal</th>
                            <th class="p-3" rowspan="2">Nomor BA</th>
                            <th class="p-3" rowspan="2">Jenis BA</th>
                            <?php if ($isKerusakan): ?>
                                <th colspan="2">Status Approval</th>
                            <?php elseif($isPengembalian): ?>
                                <th colspan="3">Status Approval</th>
                            <?php elseif($isNotebook): ?>
                                <th colspan="4">Status Approval</th>
                            <?php endif; ?>
                            <th class="p-3" rowspan="2">Actions</th>
                        </tr>
                        <tr class="tabel-judul2">
                            <?php if ($isKerusakan): ?>
                                <th>Pembuat</th>
                                <th>Yang Mengetahui</th>
                            <?php elseif($isPengembalian): ?>
                                <th>Yang Menyerahkan</th>
                                <th>Penerima</th>
                                <th>Yang Mengetahui</th>
                            <?php elseif($isNotebook): ?>
                                <th>Pihak Pertama</th>
                                <th>Pihak Kedua</th>
                                <th>Saksi</th>
                                <th>Yang Mengetahui</th>
                            <?php endif; ?>
                        </tr>

                    </thead>
                    <tbody>
                    <?php
                    $no = 1;
                    if ($resultAkses) {
                        
                        if (!isset($isKerusakan)) $isKerusakan = ($filterJenisBA === 'kerusakan');
                        if (!isset($isPengembalian)) $isPengembalian = ($filterJenisBA === 'pengembalian');
                        if (!isset($isNotebook)) $isNotebook = ($filterJenisBA === 'notebook');

                        while ($row = $resultAkses->fetch_assoc()) {
                            // $sessionUser = $_SESSION['nama'] ?? '';

                            // $tanggal   = $row['tanggal'] ?? '';
                            // $nomor_ba  = $row['nomor_ba'] ?? '';
                            // $approval_1 = intval($row['approval_1'] ?? 0);
                            // $approval_2 = intval($row['approval_2'] ?? 0);
                            // $approval_3 = intval($row['approval_3'] ?? 0);
                            // $approval_4 = intval($row['approval_4'] ?? 0);

                            $sessionUser = isset($_SESSION['nama']) ? $_SESSION['nama'] : '';

                            $tanggal    = isset($row['tanggal']) ? $row['tanggal'] : '';
                            $nomor_ba   = isset($row['nomor_ba']) ? $row['nomor_ba'] : '';
                            $approval_1 = isset($row['approval_1']) ? intval($row['approval_1']) : 0;
                            $approval_2 = isset($row['approval_2']) ? intval($row['approval_2']) : 0;
                            $approval_3 = isset($row['approval_3']) ? intval($row['approval_3']) : 0;
                            $approval_4 = isset($row['approval_4']) ? intval($row['approval_4']) : 0;
                            
                            $ids = isset($row['id']) ? intval($row['id']) : 0;

                            echo "<tr>";
                            echo "<td class='custom-font pt-3'>{$no}</td>";
                            echo "<td class='custom-font pt-3'>" . (!empty($tanggal) ? date('Y/m/d', strtotime($tanggal)) : '-') . "</td>";
                            echo "<td class='custom-font pt-3'>" . htmlspecialchars($nomor_ba, ENT_QUOTES) . "</td>";

                            
                            if ($isKerusakan) {
                                echo "<td class='custom-font pt-3'>Berita Acara Kerusakan</td>";
                            } elseif ($isPengembalian) {
                                echo "<td class='custom-font pt-3'>Berita Acara Pengembalian Inventaris</td>";
                            } elseif ($isNotebook) {
                                echo "<td class='custom-font pt-3'>Berita Acara Serah Terima Notebook</td>";
                            } else {
                                echo "<td class='custom-font pt-3'>Jenis BA Lain</td>"; 
                            }

                            if ($isKerusakan) {
                                    
                                // $jab1 = trim(($row['jabatan_aprv1'] ?? '') . ' ' . ($row['departemen_aprv1'] ?? ''));
                                // $jab2 = trim(($row['jabatan_aprv2'] ?? '') . ' ' . ($row['departemen_aprv2'] ?? ''));

                                // $namaPembuat = trim($row['nama_aprv1'] ?? '');
                                // $namaDiketahui = trim($row['nama_aprv2'] ?? '');

                                //Start: Temporary Unused Logic
                                $jab1 = trim(
                                    (isset($row['jabatan_aprv1']) ? $row['jabatan_aprv1'] : '') . ' ' .
                                    (isset($row['departemen_aprv1']) ? $row['departemen_aprv1'] : '')
                                );

                                $jab2 = trim(
                                    (isset($row['jabatan_aprv2']) ? $row['jabatan_aprv2'] : '') . ' ' .
                                    (isset($row['departemen_aprv2']) ? $row['departemen_aprv2'] : '')
                                );
                                //End: Temporary Unused Logic

                                $namaPembuat   = trim(isset($row['nama_aprv1']) ? $row['nama_aprv1'] : '');
                                $namaDiketahui = trim(isset($row['nama_aprv2']) ? $row['nama_aprv2'] : '');


                                $isNamaPembuat = ($namaPembuat !== '' && $namaPembuat === $sessionUser);
                                $isNamaDiketahui = ($namaDiketahui !== '' && $namaDiketahui === $sessionUser);

                                $labelAprv1 = $isNamaPembuat ? "<p class='custom-font m-0 text-primary'>Anda</p>" : htmlspecialchars($namaPembuat ?: '-', ENT_QUOTES);
                                $labelAprv2 = $isNamaDiketahui ? "<p class='custom-font m-0 text-primary'>Anda</p>" : htmlspecialchars($namaDiketahui ?: '-', ENT_QUOTES);

                                echo "<td class='custom-font pt-3'>" . statusBadge($approval_1) . "<br><div class='custom-font m-0 p-0 mt-1'>{$labelAprv1}";
                                ?>
                                
                                <?php
                                echo "</div></td>";
                                echo "<td class='custom-font pt-3'>" . statusBadge($approval_2) . "<br><div class='custom-font m-0 p-0 mt-1'>{$labelAprv2}</div></td>";
                                echo "<td class='pt-3'>";
                                echo "<a class='btn btn-secondary btn-sm' href='detail_barang_kerusakan.php?id=" . intval(isset($row['id']) ? $row['id'] : 0) . "'><i class='bi bi-eye-fill'></i></a> ";
                                echo "<a class='btn btn-primary btn-sm' href='surat_output_kerusakan.php?id=" . intval(isset($row['id']) ? $row['id'] : 0) . "' target='_blank'><i class='bi bi-file-earmark-text-fill'></i></a> ";

                            } elseif ($isPengembalian) {
                            
                                // $nama_pengembali = trim($row['nama_pengembali'] ?? '');
                                // $nama_penerima   = trim($row['nama_penerima'] ?? '');
                                // $diketahui_raw   = trim($row['diketahui'] ?? '');
                                // $diketahui_name  = trim(explode(' - ', $diketahui_raw)[0] ?? '');

                                $nama_pengembali = trim(isset($row['nama_pengembali']) ? $row['nama_pengembali'] : '');
                                $nama_penerima   = trim(isset($row['nama_penerima']) ? $row['nama_penerima'] : '');
                                $diketahui_raw   = trim(isset($row['diketahui']) ? $row['diketahui'] : '');

                                $tmp             = explode(' - ', $diketahui_raw);
                                $diketahui_name  = trim(isset($tmp[0]) ? $tmp[0] : '');


                                $isPengembali = ($nama_pengembali !== '' && $nama_pengembali === $sessionUser);
                                $isPenerima   = ($nama_penerima !== '' && $nama_penerima === $sessionUser);
                                $isDiketahui  = ($diketahui_name !== '' && $diketahui_name === $sessionUser);

                                // $jab1 = trim(($row['jabatan_aprv1'] ?? '') . ' ' . ($row['departemen_aprv1'] ?? ''));
                                // $jab2 = trim(($row['jabatan_aprv2'] ?? '') . ' ' . ($row['departemen_aprv2'] ?? ''));
                                // $jab3 = trim(($row['jabatan_aprv3'] ?? '') . ' ' . ($row['departemen_aprv3'] ?? ''));

                                $jab1 = trim(
                                    (isset($row['jabatan_aprv1']) ? $row['jabatan_aprv1'] : '') . ' ' .
                                    (isset($row['departemen_aprv1']) ? $row['departemen_aprv1'] : '')
                                );

                                $jab2 = trim(
                                    (isset($row['jabatan_aprv2']) ? $row['jabatan_aprv2'] : '') . ' ' .
                                    (isset($row['departemen_aprv2']) ? $row['departemen_aprv2'] : '')
                                );

                                $jab3 = trim(
                                    (isset($row['jabatan_aprv3']) ? $row['jabatan_aprv3'] : '') . ' ' .
                                    (isset($row['departemen_aprv3']) ? $row['departemen_aprv3'] : '')
                                );


                                $labelAprv1 = $isPengembali ? "<p class='custom-font m-0 text-primary'>Anda</p>" : htmlspecialchars($jab1 ?: '-', ENT_QUOTES);
                                $labelAprv2 = $isPenerima   ? "<p class='custom-font m-0 text-primary'>Anda</p>" : htmlspecialchars($jab2 ?: '-', ENT_QUOTES);
                                $labelAprv3 = $isDiketahui  ? "<p class='custom-font m-0 text-primary'>Anda</p>" : htmlspecialchars($jab3 ?: '-', ENT_QUOTES);

                                echo "<td class='custom-font pt-3'>" . statusBadge($approval_1) . "<br><div>{$labelAprv1}</div></td>";
                                echo "<td class='custom-font pt-3'>" . statusBadge($approval_2) . "<br><div>{$labelAprv2}</div></td>";
                                echo "<td class='custom-font pt-3'>" . statusBadge($approval_3) . "<br><div>{$labelAprv3}</div></td>";
                                echo "<td class='pt-3'>";
                                // echo "<a class='btn btn-secondary btn-sm' href='detail_barang_pengembalian.php?id=" . intval($row['id'] ?? 0) . "'><i class='bi bi-eye-fill'></i></a> ";
                                // echo "<a class='btn btn-primary btn-sm' href='surat_output_pengembalian.php?id=" . intval($row['id'] ?? 0) . "' target='_blank'><i class='bi bi-file-earmark-text-fill'></i></a> ";
                                echo "<a class='btn btn-secondary btn-sm' href='detail_barang_pengembalian.php?id={$ids}'><i class='bi bi-eye-fill'></i></a> ";
                                echo "<a class='btn btn-primary btn-sm' href='surat_output_pengembalian.php?id={$ids}' target='_blank'><i class='bi bi-file-earmark-text-fill'></i></a> ";

                            } elseif ($isNotebook) {
                            
                                $nama_pertama   = trim(isset($row['pertama']) ? $row['pertama'] : '');
                                $nama_kedua     = trim(isset($row['nama_peminjam']) ? $row['nama_peminjam'] : '');
                                $nama_saksi     = trim(isset($row['saksi']) ? $row['saksi'] : '');
                                $nama_diketahui = trim(isset($row['diketahui']) ? $row['diketahui'] : '');

                                $isPertama   = ($nama_pertama !== '' && $nama_pertama === $sessionUser);
                                $isKedua     = ($nama_kedua !== '' && $nama_kedua === $sessionUser);
                                $isSaksi     = ($nama_saksi !== '' && $nama_saksi === $sessionUser);
                                $isDiketahui = ($nama_diketahui !== '' && $nama_diketahui === $sessionUser);

                                $jab1 = isset($row['jabatan_pertama']) ? $row['jabatan_pertama'] : '';

                                $jab2 = trim(
                                    (isset($row['jabatan_aprv2']) ? $row['jabatan_aprv2'] : '') . ' ' .
                                    (isset($row['departemen_aprv2']) ? $row['departemen_aprv2'] : '')
                                );

                                $jab3 = trim(
                                    (isset($row['jabatan_aprv3']) ? $row['jabatan_aprv3'] : '') . ' ' .
                                    (isset($row['departemen_aprv3']) ? $row['departemen_aprv3'] : '')
                                );

                                $jab4 = trim(
                                    (isset($row['jabatan_aprv4']) ? $row['jabatan_aprv4'] : '') . ' ' .
                                    (isset($row['departemen_aprv4']) ? $row['departemen_aprv4'] : '')
                                );

                                $labelAprv1 = $isPertama ? "<p class='custom-font m-0 text-primary'>Anda</p>" : htmlspecialchars($jab1 ?: '-', ENT_QUOTES);
                                $labelAprv2 = $isKedua   ? "<p class='custom-font m-0 text-primary'>Anda</p>" : htmlspecialchars($jab2 ?: '-', ENT_QUOTES);
                                $labelAprv3 = $isSaksi  ? "<p class='custom-font m-0 text-primary'>Anda</p>" : htmlspecialchars($jab3 ?: '-', ENT_QUOTES);
                                $labelAprv4 = $isDiketahui  ? "<p class='custom-font m-0 text-primary'>Anda</p>" : htmlspecialchars($jab4 ?: '-', ENT_QUOTES);

                                echo "<td class='custom-font pt-3'>" . statusBadge($approval_1) . "<br><div>{$labelAprv1}</div></td>";
                                echo "<td class='custom-font pt-3'>" . statusBadge($approval_2) . "<br><div>{$labelAprv2}</div></td>";
                                echo "<td class='custom-font pt-3'>" . statusBadge($approval_3) . "<br><div>{$labelAprv3}</div></td>";
                                echo "<td class='custom-font pt-3'>" . statusBadge($approval_4) . "<br><div>{$labelAprv4}</div></td>";
                                echo "<td class='pt-3'>";
                                // echo "<a class='btn btn-secondary btn-sm' href='detail_barang_notebook.php?id=" . intval($row['id'] ?? 0) . "'><i class='bi bi-eye-fill'></i></a> ";
                                // echo "<a class='btn btn-primary btn-sm' href='surat_output_notebook.php?id=" . intval($row['id'] ?? 0) . "' target='_blank'><i class='bi bi-file-earmark-text-fill'></i></a> ";
                                echo "<a class='btn btn-secondary btn-sm' href='detail_barang_notebook.php?id={$ids}'><i class='bi bi-eye-fill'></i></a> ";
                                echo "<a class='btn btn-primary btn-sm' href='surat_output_notebook.php?id={$ids}' target='_blank'><i class='bi bi-file-earmark-text-fill'></i></a> ";
                            }

                            

                            if ($isPengembalian) {
                                
                                if (!$isPengembali && !$isPenerima && !$isDiketahui) {
                                    $styleHide = "style='display:none;'";
                                }

                                $styleHide = "";
                                if ($isPengembali && $approval_1 === 1) $styleHide = "style='display:none;'";
                                if ($isPenerima && $approval_2 === 1)   $styleHide = "style='display:none;'";
                                if ($isDiketahui && $approval_3 === 1)  $styleHide = "style='display:none;'";

                                echo "<a class='btn btn-success btn-sm js-open-approve btn-disapear' 
                                        href='approval.php?id=" . (isset($row['id']) ? intval($row['id']) : 0) . "' 
                                        data-id='" . (isset($row['id']) ? intval($row['id']) : 0) . "' 
                                        data-nomor='" . htmlspecialchars($nomor_ba, ENT_QUOTES) . "' 
                                        data-tanggal='" . htmlspecialchars($tanggal, ENT_QUOTES) . "' 
                                        data-nama-pengembali='" . htmlspecialchars(isset($nama_pengembali) ? $nama_pengembali : '', ENT_QUOTES) . "' 
                                        data-nama-penerima='" . htmlspecialchars(isset($nama_penerima) ? $nama_penerima : '', ENT_QUOTES) . "' 
                                        data-diketahui='" . htmlspecialchars(isset($diketahui_name) ? $diketahui_name : '', ENT_QUOTES) . "' 
                                        data-approval-1='{$approval_1}' 
                                        data-approval-2='{$approval_2}' 
                                        data-approval-3='{$approval_3}'
                                        data-jenis-ba='pengembalian'
                                        {$styleHide}><i class='bi bi-check-circle'></i></a>";

                            }
                            elseif ($isKerusakan){
                                $styleHide = "";

                                if (!$isNamaPembuat && !$isNamaDiketahui) {
                                    $styleHide = "style='display:none;'";
                                }

                                if ($isNamaPembuat && $approval_1 === 1) $styleHide = "style='display:none;'";
                                if ($isNamaDiketahui && $approval_2 === 1) $styleHide = "style='display:none;'";

                                echo "<a class='btn btn-success btn-sm js-open-approve btn-disapear' 
                                    href='approval.php?id=" . (isset($row['id']) ? intval($row['id']) : 0) . "' 
                                    data-id='" . (isset($row['id']) ? intval($row['id']) : 0) . "' 
                                    data-nomor='" . htmlspecialchars(isset($nomor_ba) ? $nomor_ba : '', ENT_QUOTES) . "' 
                                    data-tanggal='" . htmlspecialchars(isset($tanggal) ? $tanggal : '', ENT_QUOTES) . "' 
                                    data-nama-aprv1='" . htmlspecialchars(isset($namaPembuat) ? $namaPembuat : '', ENT_QUOTES) . "' 
                                    data-nama-aprv2='" . htmlspecialchars(isset($namaDiketahui) ? $namaDiketahui : '', ENT_QUOTES) . "' 
                                    data-approval-1='{$approval_1}' 
                                    data-approval-2='{$approval_2}' 
                                    data-jenis-ba='kerusakan'
                                    {$styleHide}><i class='bi bi-check-circle'></i></a>";

                            }
                            elseif ($isNotebook){
                                $styleHide = "";

                                if (!$isPertama && !$isKedua && !$isSaksi && !$isDiketahui) {
                                    $styleHide = "style='display:none;'";
                                }

                                if ($isPertama && $approval_1 === 1) $styleHide = "style='display:none;'";
                                if ($isKedua && $approval_2 === 1) $styleHide = "style='display:none;'";
                                if ($isSaksi && $approval_3 === 1) $styleHide = "style='display:none;'";
                                if ($isDiketahui && $approval_4 === 1) $styleHide = "style='display:none;'";

                                echo "<a class='btn btn-success btn-sm js-open-approve btn-disapear' 
                                    href='approval.php?id=" . (isset($row['id']) ? intval($row['id']) : 0) . "' 
                                    data-id='" . (isset($row['id']) ? intval($row['id']) : 0) . "' 
                                    data-nomor='" . htmlspecialchars(isset($nomor_ba) ? $nomor_ba : '', ENT_QUOTES) . "' 
                                    data-tanggal='" . htmlspecialchars(isset($tanggal) ? $tanggal : '', ENT_QUOTES) . "' 
                                    data-nama-pertama='" . htmlspecialchars(isset($nama_pertama) ? $nama_pertama : '', ENT_QUOTES) . "' 
                                    data-nama-kedua='" . htmlspecialchars(isset($nama_kedua) ? $nama_kedua : '', ENT_QUOTES) . "' 
                                    data-nama-saksi='" . htmlspecialchars(isset($nama_saksi) ? $nama_saksi : '', ENT_QUOTES) . "' 
                                    data-nama-diketahui='" . htmlspecialchars(isset($nama_diketahui) ? $nama_diketahui : '', ENT_QUOTES) . "' 
                                    data-approval-1='{$approval_1}' 
                                    data-approval-2='{$approval_2}' 
                                    data-approval-3='{$approval_3}' 
                                    data-approval-4='{$approval_4}' 
                                    data-jenis-ba='notebook'
                                    {$styleHide}><i class='bi bi-check-circle'></i></a>";

                            }

                            echo "</td>";
                            echo "</tr>";
                            $no++;
                        }
                    }
                    ?>
                    </tbody>
                </table>
            </div>

                <div id="popupBoxAutograph" class="popup-box custom-popup position-absolute bg-white rounded-1 flex-column justify-content-start align-items-center p-2" 
                    style="height: max-content;align-self: center;z-index: 999;width:max-content;min-width:500px;left:35.5%;top:30vh;">
                    <div class="w-100 d-flex justify-content-between mb-2 p-0" style="height: max-content;">
                        <h4 class="m-0 p-0">Tanda tangan</h4>
                        <a id="tombolClosePopupAutograph" class='btn btn-danger btn-sm' href='#' ><i class="bi bi-x-lg"></i></a>
                    </div>
                    <div class="autograph-container p-3">
                        <canvas id="signature" width="500" height="200" style="border: 1px solid black; border-radius: 8px;"></canvas>
                        <div class="d-flex justify-content-between mt-2">
                            <button id="clear" class="btn btn-warning btn-sm">Bersihkan</button>
                            <button id="save" class="btn btn-success btn-sm">Simpan</button>
                        </div>  
                    </div>
                </div>

                <div id="popupBoxAprv" class="popup-box custom-popup2 position-absolute bg-white rounded-1 flex-column justify-content-start align-items-center p-5" 
                    style="height: max-content;align-self: center;z-index: 999;width:30%;min-width:500px;left:35.5%;top:30vh;">
                    <div class="w-100 d-flex justify-content-start mb-2" style="height: max-content;">
                        <h6 class="m-0 p-0">BA (Jenis BA) Nomor (Nomor BA) Periode (Bulan Romawi)</h6>
                        
                    </div>
                    <div id="popupContent" class="w-100">
                    <p class="mb-2">ID Data: <span id="popupDataId"></span></p>
                    <input type="hidden" id="popupRowId" name="row_id" value="">
                    </div>
                    <div class="w-100 d-flex justify-content-start">
                        <p class="m-0 p-0 mb-3" style="font-weight: 500;">Peran anda : </p>
                        <p class="m-0 p-0 mb-3 ms-1 peran-text">(Peran)</p>
                    </div>
                    <div class="w-100 d-flex justify-content-between mt-2 position-relative">
                        <div class="w-100 d-flex justify-content-start">
                            <a class='btn btn-secondary me-2' href='detail?id='><i class='bi bi-eye-fill'></i></a>
                            <a class='btn btn-primary' href='surat?id=' target='_blank'><i class='bi bi-file-earmark-text-fill'></i></a>
                        </div>
                        <div class="w-100 d-flex justify-content-center">
                            <a href="#" class="btn btn-success me-2" id="tombolSetuju">Setujui</a>
                            <!-- <a href="#" class="btn btn-danger" id="tombolTolakAprv">Tolak</a> -->
                        </div>
                        <div class="w-100 d-flex justify-content-end">
                            <a href="#" class="btn btn-secondary"id="tombolClosePopup"> Batal</a>
                        </div>
                        
                    </div>
                
                </div>

                
            
        </section>
    </main>

        <!--Awal::Footer Content-->
        <footer class="custom-footer d-flex position-relative p-2" style="border-top: whitesmoke solid 1px; box-shadow: 0px 7px 10px black; color: grey;">
            <p class="position-absolute" style="right: 15px;bottom:7px;color: grey;"><strong>Version </strong>1.1.0</p>
        <p class="pt-2 ps-1"><strong>Copyright &copy 2025</p></strong><p class="pt-2 ps-1 fw-bold text-primary">MIS MSAL.</p><p class="pt-2 ps-1"> All rights reserved</p>
        </footer>
        <!--Akhir::Footer Content-->

    <div id="popupBG" class="popup-bg position-absolute w-100 h-100"></div>
    </div>
    


<!-- Bootstrap 5 -->
<script src="../assets/bootstrap-5.3.6-dist/js/bootstrap.min.js"></script>

<!-- popperjs Bootstrap 5 -->
<script src="../assets/js/popper.min.js"></script>

<!-- AdminLTE -->
<script src="../assets/adminlte/js/adminlte.js"></script>

<!-- OverlayScrollbars -->
<script src="../assets/js/overlayscrollbars.browser.es6.min.js"></script>

<!-- signaturPad -->
<script src="../assets/js/signature_pad.umd.min.js"></script>
<!-- <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script> -->

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

<script>//DataTables
    $(document).ready(function () {
        $('#myTable').DataTable({
        responsive: true,
        autoWidth: true,
        language: {
            url: "../assets/json/id.json"
        },
        scrollY: "450px",     // batasi tinggi scroll 300px
        scrollCollapse: true, // tabel ikut mengecil jika data kurang
        paging: true,
        columnDefs: [
            { targets: -1, orderable: false }, // Kolom Actions tidak bisa di-sort
            
        ]
        });
    });
</script>

<script>///popup approval
document.addEventListener('DOMContentLoaded', function () {
    const box = document.getElementById('popupBoxAprv');
    const background = document.getElementById('popupBG');
    const dataIdSpan = document.getElementById('popupDataId');
    const dataIdInput = document.getElementById('popupRowId');
    const popupTitle = document.querySelector('#popupBoxAprv h6');
    const linkDetail = document.querySelector('#popupBoxAprv a[href^="detail"]');
    const linkSurat = document.querySelector('#popupBoxAprv a[href^="surat"]');
    const peranText = document.querySelector('#popupBoxAprv .peran-text');
    const tombolSetuju = document.getElementById('tombolSetuju');
    const btnDisapear = document.getElementById('btn-disapear');

    const namaSession = "<?php echo $_SESSION['nama']; ?>";

    function bulanRomawi(tgl) {
        const bulan = new Date(tgl).getMonth() + 1;
        const romawi = ["I","II","III","IV","V","VI","VII","VIII","IX","X","XI","XII"];
        return romawi[bulan - 1] || '';
    }

    function mapRoleToApprovalCols(role, jenisBa) {
        if (jenisBa === 'kerusakan') {
            if (role === 'Pembuat') return ['approval_1'];
            if (role === 'Diketahui') return ['approval_2'];
        } else if (jenisBa === 'pengembalian'){ 
            if (role === 'Pengembali') return ['approval_1'];
            if (role === 'Penerima') return ['approval_2'];
            if (role === 'Diketahui') return ['approval_3'];
        } else if (jenisBa === 'notebook'){ 
            if (role === 'Pertama') return ['approval_1'];
            if (role === 'Kedua') return ['approval_2'];
            if (role === 'Saksi') return ['approval_3'];
            if (role === 'Diketahui') return ['approval_4'];
        }
        return [];
    }

    document.addEventListener('click', function (e) {
        const openBtn = e.target.closest('.js-open-approve');
        if (openBtn) {
            e.preventDefault();

            const id = openBtn.getAttribute('data-id') || '';
            const nomor = openBtn.getAttribute('data-nomor') || '';
            const tanggal = openBtn.getAttribute('data-tanggal') || '';

            let jenisBa = '';

            if (openBtn.hasAttribute('data-nama-aprv1')) {
                jenisBa = 'kerusakan';
            } else if (openBtn.hasAttribute('data-nama-pengembali')) {
                jenisBa = 'pengembalian';
            } else if (openBtn.hasAttribute('data-nama-pertama')) {
                jenisBa = 'notebook';
            }

            const appr1 = Number(openBtn.getAttribute('data-approval-1') || 0);
            const appr2 = Number(openBtn.getAttribute('data-approval-2') || 0);
            const appr3 = Number(openBtn.getAttribute('data-approval-3') || 0);
            const appr4 = Number(openBtn.getAttribute('data-approval-4') || 0);

            let roles = [];
            if (jenisBa === 'kerusakan') {
                const namaAprv1 = openBtn.getAttribute('data-nama-aprv1') || '';
                const namaAprv2 = openBtn.getAttribute('data-nama-aprv2') || '';
                if (namaAprv1 === namaSession) roles.push('Pembuat');
                if (namaAprv2 === namaSession) roles.push('Diketahui');
            } else if(jenisBa === 'pengembalian'){
                const namaPengembali = openBtn.getAttribute('data-nama-pengembali') || '';
                const namaPenerima = openBtn.getAttribute('data-nama-penerima') || '';
                const diketahui = openBtn.getAttribute('data-diketahui') || '';
                if (namaPengembali === namaSession) roles.push('Pengembali');
                if (namaPenerima === namaSession) roles.push('Penerima');
                if (diketahui.split(' - ')[0].trim() === namaSession) roles.push('Diketahui');
            } else if(jenisBa === 'notebook'){
                const namaPertama = openBtn.getAttribute('data-nama-pertama') || '';
                const namaKedua = openBtn.getAttribute('data-nama-kedua') || '';
                const namaSaksi = openBtn.getAttribute('data-nama-saksi') || '';
                const namaDiketahui = openBtn.getAttribute('data-nama-diketahui') || '';
                if (namaPertama === namaSession) roles.push('Pihak Pertama');
                if (namaKedua === namaSession) roles.push('Pihak Kedua');
                if (namaSaksi === namaSession) roles.push('Saksi');
                if (namaDiketahui === namaSession) roles.push('Diketahui');
            }

            if (peranText) peranText.textContent = roles.join(', ');
            if (dataIdSpan) dataIdSpan.textContent = id;
            if (dataIdInput) dataIdInput.value = id;

            if (popupTitle) {
                if (jenisBa === 'kerusakan') {
                    popupTitle.textContent = `BA Kerusakan Nomor ${nomor} Periode ${bulanRomawi(tanggal)}`;
                } else if (jenisBa === 'pengembalian') {
                    popupTitle.textContent = `BA Pengembalian Nomor ${nomor} Periode ${bulanRomawi(tanggal)}`;
                } else if(jenisBa === 'notebook') {
                    popupTitle.textContent = `BA Serah Terima Nomor ${nomor} Periode ${bulanRomawi(tanggal)}`;
                }
            }

            if (jenisBa === 'kerusakan') {
                if (linkDetail) linkDetail.href = `detail_barang_kerusakan.php?id=${id}`;
                if (linkSurat) linkSurat.href = `surat_output_kerusakan.php?id=${id}`;
            } else if(jenisBa === 'pengembalian'){
                if (linkDetail) linkDetail.href = `detail_barang_pengembalian.php?id=${id}`;
                if (linkSurat) linkSurat.href = `surat_output_pengembalian.php?id=${id}`;
            } else if(jenisBa === 'notebook'){
                if (linkDetail) linkDetail.href = `detail_barang_notebook.php?id=${id}`;
                if (linkSurat) linkSurat.href = `surat_output_notebook.php?id=${id}`;
            }

            
            if (tombolSetuju) {
                let showApprove = roles.length > 0;
                
                function approvalValueForRole(role) {
                    if (jenisBa === 'kerusakan') {
                        if (role === 'Pembuat') return appr1;
                        if (role === 'Diketahui') return appr2;
                    } else if (jenisBa === 'pengembalian'){
                        if (role === 'Pengembali') return appr1;
                        if (role === 'Penerima') return appr2;
                        if (role === 'Diketahui') return appr3;
                    } else if(jenisBa === 'notebook'){
                        if (role === 'Pertama') return appr1;
                        if (role === 'Kedua') return appr2;
                        if (role === 'Saksi') return appr3;
                        if (role === 'Diketahui') return appr4;
                    }
                    return 0;
                }

                
                let alreadyApprovedAll = roles.length > 0 && roles.every(r => approvalValueForRole(r) === 1);

                if (showApprove) {
                    tombolSetuju.style.display = 'inline-block';
                    if (!alreadyApprovedAll) {
                        tombolSetuju.textContent = 'Setujui';
                        tombolSetuju.classList.remove('btn-warning');
                        tombolSetuju.classList.add('btn-success');
                        tombolSetuju.dataset.action = 'approve';
                    } else {
                        
                        if (btnDisapear) btnDisapear.style.display = 'none';
                        tombolSetuju.style.display = 'none';
                        
                        // tombolSetuju.textContent = 'Batal Setujui';
                        // tombolSetuju.classList.remove('btn-success');
                        // tombolSetuju.classList.add('btn-warning');
                        // tombolSetuju.dataset.action = 'cancel';
                    }
                } else {
                    tombolSetuju.style.display = 'none';
                }

                
                tombolSetuju.dataset.jenisBa = jenisBa;
            }

            box.classList.add('aktifPopup');
            box.classList.add('scale-in-center');
            box.classList.remove('scale-out-center');
            background.classList.add('aktifPopup');
            background.classList.add('fade-in');
            background.classList.remove('fade-out');
            return; 
        }

        if (e.target.closest('#tombolClosePopup')) {
            e.preventDefault();
            // box.classList.remove('aktifPopup');
            box.classList.remove('scale-in-center');
            box.classList.add('scale-out-center');
            setTimeout(() => {
                background.classList.remove('aktifPopup');
            }, 300); 
            background.classList.remove('fade-in');
            background.classList.add('fade-out');
            return;
        }

        if (e.target.id === 'popupBG') {
            // box.classList.remove('aktifPopup');
            box.classList.remove('scale-in-center');
            box.classList.add('scale-out-center');
            setTimeout(() => {
                background.classList.remove('aktifPopup');
            }, 300); 
            background.classList.remove('fade-in');
            background.classList.add('fade-out');
        }
    });

    
    if (tombolSetuju) {
        tombolSetuju.addEventListener('click', function(e) {
            e.preventDefault();

            const id = dataIdInput.value;
            const roles = peranText.textContent.split(',').map(r => r.trim()).filter(Boolean);
            if (!id || roles.length === 0) return;

            const jenisBa = tombolSetuju.dataset.jenisBa || 'pengembalian';

            
            let approvals = [];
            roles.forEach(role => {
                mapRoleToApprovalCols(role, jenisBa).forEach(col => {
                    if (!approvals.includes(col)) approvals.push(col);
                });
            });

            
            let validCols = [];

            if (jenisBa === 'kerusakan') {
                validCols = ['approval_1', 'approval_2'];
            } else if (jenisBa === 'pengembalian') {
                validCols = ['approval_1', 'approval_2', 'approval_3'];
            } else if (jenisBa === 'notebook') {
                validCols = ['approval_1', 'approval_2', 'approval_3', 'approval_4'];
            }

            approvals = approvals.filter(a => validCols.includes(a));

            if (approvals.length === 0) {
                alert('Tidak ada kolom approval yang valid untuk peran Anda.');
                console.log('Debug: approvals kosong, roles=', roles, 'jenisBa=', jenisBa);
                return;
            }

            const action = tombolSetuju.dataset.action || 'approve';

            const payload = {
                id: id,
                approvals: approvals,
                action: action,
                jenis_ba: jenisBa
            };

            // console.log('kirim payload approve ->', payload); //Pengecekan data approve

            fetch('proses_approve.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(payload)
            })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    location.reload(); 
                } else {
                    location.reload();
                }
            })
            .catch(err => {
                console.error(err);
                alert('Gagal network / server. Cek console.');
            });
        });
    }
});
</script>

<script>
    const canvas = document.getElementById("signature");
    const signaturePad = new SignaturePad(canvas, {
      backgroundColor: "white",
      penColor: "black"
    });

    document.getElementById("clear").addEventListener("click", () => {
      signaturePad.clear();
    });

    document.getElementById("save").addEventListener("click", () => {
      if (signaturePad.isEmpty()) {
        alert("Belum ada tanda tangan!");
      } else {
        const data = signaturePad.toDataURL("image/png");
        console.log("Hasil PNG Base64:", data);
        alert("Tanda tangan sudah disimpan (cek console log).");
      }
    });
  </script>

<script>//Popup tanda tangan
    document.addEventListener('DOMContentLoaded', function () {
        const close = document.getElementById('tombolClosePopupAutograph');
        const box = document.getElementById('popupBoxAutograph');
        const background = document.getElementById('popupBG');

        // Delegasi klik: berlaku untuk tombol di form input maupun form edit
        document.addEventListener('click', function (e) {
            if (e.target.closest('.tombolAutographPopup')) {
                box.classList.add('aktifPopup');
                background.classList.add('aktifPopup');
                box.classList.add('scale-in-center');
                box.classList.remove('scale-out-center');
                background.classList.add('fade-in');
                background.classList.remove('fade-out');
            }
        });

        close.addEventListener('click', function () {
            // box.classList.remove('aktifPopup');
            // background.classList.remove('aktifPopup');
            setTimeout(() => {
                background.classList.remove('aktifPopup');
                box.classList.remove('aktifPopup');
            }, 300); 
            box.classList.remove('scale-in-center');
            box.classList.add('scale-out-center');
            background.classList.remove('fade-in');
            background.classList.add('fade-out');
        });

        background.addEventListener('click', function () {
            // box.classList.remove('aktifPopup');
            // background.classList.remove('aktifPopup');
            setTimeout(() => {
                background.classList.remove('aktifPopup');
                box.classList.remove('aktifPopup');
            }, 300); 
            box.classList.remove('scale-in-center');
            box.classList.add('scale-out-center');
            background.classList.remove('fade-in');
            background.classList.add('fade-out');
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
