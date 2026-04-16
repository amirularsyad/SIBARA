<?php
session_start();

// Jika belum login, arahkan ke halaman login
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../login_registrasi.php");
    exit();
}

// Jika login tapi bukan Admin, arahkan ke halaman approval
if (!isset($_SESSION['hak_akses']) || $_SESSION['hak_akses'] !== 'Admin') {
    header("Location: approval.php");
    exit();
}

// Jika login tapi bukan Admin, arahkan ke halaman utama
// if (!isset($_SESSION['hak_akses']) || $_SESSION['hak_akses'] !== 'Admin') {
//     header("Location: ../personal/approval.php");
//     exit();
// }
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Status Approval</title>

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
    .tabelba{
        height: 42px;
        transition: all .3s ease-in-out;
        overflow: hidden;
    }
    .tabelba2{
        height: 42px;
        transition: all .3s ease-in-out;
        overflow: hidden;
    }
    .tabelba3{
        height: 42px;
        transition: all .3s ease-in-out;
        overflow: hidden;
    }
    .tabelAktif{
        height: max-content;
        transition: all .3s ease-in-out;
    }
    .tabelAktif2{
        height: max-content;
        transition: all .3s ease-in-out;
    }
    .tabelAktif3{
        height: max-content;
        transition: all .3s ease-in-out;
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
        font-size: .7rem;
    }

    th, td{
        text-align: center !important;
    }

    /* .tabel-judul th:first-child{ width: 4%; text-align: center; } 
    .tabel-judul th:nth-child(2) { width: 6%; }  
    .tabel-judul th:nth-child(3) { width: 10%; }  
    .tabel-judul th:nth-child(4) { width: 20%; }
    .tabel-judul th:nth-child(5) { width: 35%; }
    .tabel-judul th:last-child{ width: 10%; height:100% !important; text-align: center; }   

    .tabel-judul2 th:first-child, .tabel-judul2 th:last-child {
        width: 50%;
    }
    td:first-child {width:4%;}
    td:nth-child(2) {width:6%;}
    td:nth-child(3) {width:10%;}
    td:nth-child(4) {width:20%;}
    td:nth-child(5) {width:35%;}
    td:last-child {width:10%; height:100% !important; text-align: center;} */
    @media (max-width: 1670px) {
        .btn{
        margin-bottom: 5px;
    }
    }

    .bi-list, .bi-arrows-fullscreen, .bi-fullscreen-exit {
            color: #fff !important;
    }
</style>

<style>/*animista.net*/ 

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



<style>/*Animista*/

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
            <div id="akunInfo" class="akun-info card position-absolute bg-white p-2" style="width:300px;height:160px;top:50px;transition:all .3s ease-in-out">
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
            <!-- <?php if ($_SESSION['hak_akses'] === 'Admin'): ?>
            <li class="nav-item">
                <a href="#" class="nav-link">
                <i class="nav-icon bi bi-clipboard2-fill text-white"></i>
                <p class="text-white">
                    Status Approval BA
                </p>
                </a>
            </li>
            <?php endif; ?> -->
            <li class="nav-item">
                <a href="approval.php" class="nav-link">
                <i class="nav-icon bi bi-clipboard2-check"></i>
                <p>
                    Approve BA
                </p>
                </a>
            </li>
            <li class="nav-item">
                <a href="riwayat.php" class="nav-link">
                <i class="nav-icon bi bi-clipboard2-data"></i>
                <p>
                    Riwayat Approval
                </p>
                </a>
            </li>
            <?php if ($_SESSION['hak_akses'] === 'Admin'): ?>
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
include '../koneksi.php';

// Ambil data Berita Acara Kerusakan
$queryKerusakan = "
    SELECT 
        bak.id, 
        bak.tanggal, 
        bak.nomor_ba, 
        bak.approval_1, 
        bak.approval_2, 
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
    ORDER BY bak.tanggal DESC, bak.nomor_ba DESC
";

$resultKerusakan = $koneksi->query($queryKerusakan);

// Ambil data Berita Acara Pengembalian
// $queryPengembalian = "SELECT id, tanggal, nomor_ba, approval_1, approval_2, approval_3 FROM berita_acara_pengembalian ORDER BY tanggal DESC, nomor_ba DESC";
// $resultPengembalian = $koneksi->query($queryPengembalian);

$queryPengembalian = "
    SELECT 
        bap.id, 
        bap.tanggal, 
        bap.nomor_ba, 
        bap.approval_1, 
        bap.approval_2,
        bap.approval_3, 
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
    ORDER BY bap.tanggal DESC, bap.nomor_ba DESC
";

$resultPengembalian = $koneksi->query($queryPengembalian);

$queryNotebook = "
    SELECT 
            ban.id, 
            ban.nama_peminjam, 
            ban.saksi,
            ban.tanggal,
            ban.nomor_ba,
            ban.approval_1,
            ban.approval_2,
            ban.approval_3,
            ban.approval_4,
            k2.jabatan AS jabatan_aprv2,
            k2.departemen AS departemen_aprv2,
            k3.jabatan AS jabatan_aprv3,
            k3.departemen AS departemen_aprv3
        FROM ba_serah_terima_notebook ban
        LEFT JOIN data_karyawan k2 
            ON ban.nama_peminjam = k2.nama
        LEFT JOIN data_karyawan k3 
            ON ban.saksi = k3.nama
    ORDER BY ban.tanggal DESC, ban.nomor_ba DESC
";

$resultNotebook = $koneksi->query($queryNotebook);

function statusBadge($approval) {
    if ($approval == 1) {
        return "<div class='border fw-bold bg-success-subtle border-success-subtle text-success' style='border-radius:6px; padding:6px 6px;'>Disetujui</div>";
    } else {
        return "<div class='border fw-bold bg-warning-subtle border-warning-subtle text-warning' style='border-radius:6px; padding:6px 6px;'>Belum Disetujui</div>";
    }
}
?>

    <main class="app-main"><!-- Main Content -->
    
    

    <section class="table-wrapper bg-white position-relative overflow-visible">
        
        <h2>Daftar Status Approval Berita Acara</h2>
        <!-- <form method="get" class="mb-3 d-flex gap-2 flex-wrap align-items-end"> -->
            <!-- Filter PT -->
            <!-- <div>
                <label class="form-label">PT</label>
                <select name="pt" class="form-select">
                    <option value="">Semua PT</option>
                    <option value="PT.MSAL (HO)" >PT.MSAL (HO)</option>
                    
                </select>
            </div> -->

            <!-- Filter Jenis BA -->
            <!-- <div>
                <label class="form-label">Jenis BA</label>
                <select name="jenis_ba" class="form-select">
                    <option value="">Semua</option>
                    <option value="kerusakan">BA Kerusakan</option>
                    <option value="pengembalian">BA Pengembalian</option>
                </select>
            </div> -->

            <!-- Filter Tahun -->
            <!-- <div>
                <label class="form-label">Tahun</label>
                <select name="tahun" class="form-select">
                    <option value="">Semua</option>
                    <option value="2024">2024</option>
                    <option value="2025">2025</option>
                </select>
            </div> -->

            <!-- Filter Bulan -->
            <!-- <div>
                <label class="form-label">Bulan</label>
                <select name="bulan" class="form-select">
                    <option value="">Semua</option>
                    <option value="Januari">Januari</option>
                    <option value="Februari">Februari</option>
                    <option value="Maret">Maret</option>
                    <option value="April">April</option>
                    <option value="Mei">Mei</option>
                    <option value="Juni">Juni</option>
                    <option value="Juli">Juli</option>
                    <option value="Agustus">Agustus</option>
                    <option value="September">September</option>
                    <option value="Oktober">Oktober</option>
                    <option value="November">November</option>
                    <option value="Desember">Desember</option>
                </select>
            </div> -->

            
        <!-- </form> -->
        
<div class="d-flex flex-wrap justify-content-start gap-1">

    <div class="d-flex flex-wrap gap-2" style="width: 49.5%;height: max-content;">

        <div id="tabelBA" class="tabelba border p-1 rounded-1" style="width: 100%;">
            <div class="m-0 p-0 d-flex flex-column">
                <div class="d-flex justify-content-between">
                    <h5>Berita Acara Kerusakan</h5> <a id="minimizeDash" class='btn btn-sm' style="background-color: whitesmoke;" href='#'><i id="iconDash" class='bi bi-dash'></i></a>
                </div>
                
                <div class="d-flex align-items-center gap-1">
                    <h6 class="mb-0 pb-0">Lakukan Edit dan Hapus di</h6>
                    <a class='text-decoration-none' href='../ba_kerusakan-fix/ba_kerusakan.php'>Halaman BA Kerusakan</a>
                </div>
            </div>
            <table id="myTable" class="table table-bordered table-striped text-center" style="text-align: center !important;">
                <thead class="bg-secondary">
                    <tr class="tabel-judul">
                        <th class="p-3" rowspan="2">No</th>
                        <th class="p-3" rowspan="2">Tanggal</th>
                        <th class="p-3" rowspan="2">Nomor BA</th>
                        <th colspan="2">Status Approval</th>
                        <th class="p-3" rowspan="2">Actions</th>
                    </tr>
                    <tr class="tabel-judul2">
                        <th>Pembuat</th>
                        <th>Yang mengetahui</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $no = 1;
                    while ($row = $resultKerusakan->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td class='p-3'>{$no}</td>";
                        echo "<td class='p-3'>" . date('Y/m/d', strtotime($row['tanggal'])) . "</td>";
                        echo "<td class='p-3'>{$row['nomor_ba']}</td>";
                        echo "<td class='p-3 position-relative'>" . statusBadge($row['approval_1']) . "<br><div class='m-0 p-0 mt-1'>{$row['jabatan_aprv1']} {$row['departemen_aprv1']}</div></td>";
                        echo "<td class='p-3'>" . statusBadge($row['approval_2']) . "<br><div class='m-0 p-0 mt-1'>{$row['jabatan_aprv2']} {$row['departemen_aprv2']}</div></td>";
                        echo "<td class=''>
                                <a class='btn btn-secondary btn-sm' href='detail_barang_kerusakan.php?id={$row['id']}'><i class='bi bi-eye-fill'></i></a>
                                <a class='btn btn-primary btn-sm' href='surat_output_kerusakan.php?id={$row['id']}' target='_blank'><i class='bi bi-file-earmark-text-fill'></i></a>
                            </td>";
                        echo "</tr>";
                        $no++;
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <div id="tabelBA3" class="tabelba3 border p-1 rounded-1" style="width: 100%;">
            <div class="m-0 p-0 d-flex flex-column">
                <div class="d-flex justify-content-between">
                    <h5>Berita Acara Serah Terima Notebook</h5> <a id="minimizeDash3" class='btn btn-sm' style="background-color: whitesmoke;" href='#'><i id="iconDash3" class='bi bi-dash'></i></a>
                </div>
                <div class="d-flex align-items-center gap-1">
                    <h6 class="mb-0 pb-0">Lakukan Edit dan Hapus di</h6>
                    <a class='text-decoration-none' href='../ba_serah-terima-notebook/ba_serah-terima-notebook.php'>Halaman BA Serah Terima Notebook</a>
                </div>
            </div>
            <table id="myTable3" class="table table-bordered table-striped text-center" style="text-align: center !important;">
                <thead class="bg-secondary">
                    <tr class="tabel-judul">
                        <th class="p-3" rowspan="2">No</th>
                        <th class="p-3" rowspan="2">Tanggal</th>
                        <th class="p-3" rowspan="2">Nomor BA</th>
                        <th colspan="4">Status Approval</th>
                        <th class="p-3" rowspan="2">Actions</th>
                    </tr>
                    <tr class="tabel-judul2">
                        <th>Pihak 1</th>
                        <th>Pihak 2</th>
                        <th>Saksi</th>
                        <th>Diketahui</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $no = 1;
                    while ($row = $resultNotebook->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td class='pt-3 pb-3'>{$no}</td>";
                        echo "<td class='pt-3 pb-3'>" . date('Y/m/d', strtotime($row['tanggal'])) . "</td>";
                        echo "<td class='pt-3 pb-3'>{$row['nomor_ba']}</td>";
                        echo "<td class='pt-3 pb-3'>" . statusBadge($row['approval_1']) . "<br><div class='m-0 p-0 mt-1'>Direksi MIS</div></td>";
                        echo "<td class='pt-3 pb-3'>" . statusBadge($row['approval_2']) . "<br><div class='m-0 p-0 mt-1'>{$row['jabatan_aprv2']} {$row['departemen_aprv2']}</div></td>";
                        echo "<td class='pt-3 pb-3'>" . statusBadge($row['approval_3']) . "<br><div class='m-0 p-0 mt-1'>{$row['jabatan_aprv3']} {$row['departemen_aprv3']}</div></td>";
                        echo "<td class='pt-3 pb-3'>" . statusBadge($row['approval_4']) . "<br><div class='m-0 p-0 mt-1'>Dept. Head HRGA</div></td>";
                        echo "<td class=''>
                                <a class='btn btn-secondary btn-sm' href='detail_barang_pengembalian.php?id={$row['id']}'><i class='bi bi-eye-fill'></i></a>
                                <a class='btn btn-primary btn-sm' href='surat_output_pengembalian.php?id={$row['id']}' target='_blank'><i class='bi bi-file-earmark-text-fill'></i></a>
                            </td>";
                        echo "</tr>";
                        $no++;
                    }
                    ?>
                </tbody>
            </table>
        </div>

    </div>

    <div class="d-flex flex-wrap" style="width: 49.5%;">
        <div id="tabelBA2" class="tabelba2 border p-1 rounded-1" style="width: 100%;">
            <div class="m-0 p-0 d-flex flex-column">
                <div class="d-flex justify-content-between">
                    <h5>Berita Acara Pengembalian</h5> <a id="minimizeDash2" class='btn btn-sm' style="background-color: whitesmoke;" href='#'><i id="iconDash2" class='bi bi-dash'></i></a>
                </div>
                <div class="d-flex align-items-center gap-1">
                    <h6 class="mb-0 pb-0">Lakukan Edit dan Hapus di</h6>
                    <a class='text-decoration-none' href='../ba_pengembalian/ba_pengembalian.php'>Halaman BA Pengembalian</a>
                </div>
            </div>
            <table id="myTable2" class="table table-bordered table-striped text-center" style="text-align: center !important;">
                <thead class="bg-secondary">
                    <tr class="tabel-judul">
                        <th class="p-3" rowspan="2">No</th>
                        <th class="p-3" rowspan="2">Tanggal</th>
                        <th class="p-3" rowspan="2">Nomor BA</th>
                        <th colspan="3">Status Approval</th>
                        <th class="p-3" rowspan="2">Actions</th>
                    </tr>
                    <tr class="tabel-judul2">
                        <th>Yang menyerahkan</th>
                        <th>Penerima</th>
                        <th>Yang mengetahui</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $no = 1;
                    while ($row = $resultPengembalian->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td class='pt-3 pb-3'>{$no}</td>";
                        echo "<td class='pt-3 pb-3'>" . date('Y/m/d', strtotime($row['tanggal'])) . "</td>";
                        echo "<td class='pt-3 pb-3'>{$row['nomor_ba']}</td>";
                        echo "<td class='pt-3 pb-3'>" . statusBadge($row['approval_1']) . "<br><div class='m-0 p-0 mt-1'>{$row['jabatan_aprv1']} {$row['departemen_aprv1']}</div></td>";
                        echo "<td class='pt-3 pb-3'>" . statusBadge($row['approval_2']) . "<br><div class='m-0 p-0 mt-1'>{$row['jabatan_aprv2']} {$row['departemen_aprv2']}</div></td>";
                        echo "<td class='pt-3 pb-3'>" . statusBadge($row['approval_3']) . "<br><div class='m-0 p-0 mt-1'>{$row['jabatan_aprv3']} {$row['departemen_aprv3']}</div></td>";
                        echo "<td class=''>
                                <a class='btn btn-secondary btn-sm' href='detail_barang_pengembalian.php?id={$row['id']}'><i class='bi bi-eye-fill'></i></a>
                                <a class='btn btn-primary btn-sm' href='surat_output_pengembalian.php?id={$row['id']}' target='_blank'><i class='bi bi-file-earmark-text-fill'></i></a>
                            </td>";
                        echo "</tr>";
                        $no++;
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    

    
</div>
        
        
        
    </section>
    
    </main>
    </div>
    


<!-- Bootstrap 5 -->
<script src="../assets/bootstrap-5.3.6-dist/js/bootstrap.min.js"></script>

<!-- popperjs Bootstrap 5 -->
<script src="../assets/js/popper.min.js"></script>

<!-- AdminLTE -->
<script src="../assets/adminlte/js/adminlte.js"></script>

<!-- OverlayScrollbars -->
<script src="../assets/js/overlayscrollbars.browser.es6.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const button = document.getElementById('tombolAkun');
    const box = document.getElementById('akunInfo');

    button.addEventListener('click', function () {
    box.classList.toggle('aktif');
    });
});

</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    function setupToggle(buttonId, boxId, iconId, activeClass) {
        const button = document.getElementById(buttonId);
        const box = document.getElementById(boxId);
        const icon = document.getElementById(iconId);

        button.addEventListener('click', function (e) {
            e.preventDefault();

            // Tutup semua tabel lain dulu
            document.querySelectorAll('.tabelBox').forEach(function (el) {
                el.classList.remove('tabelAktif', 'tabelAktif2', 'tabelAktif3');
            });

            // Reset semua icon ke plus
            document.querySelectorAll('.iconDash').forEach(function (el) {
                el.classList.remove('bi-dash');
                el.classList.add('bi-plus');
            });

            // Toggle hanya tabel yang dipilih
            box.classList.toggle(activeClass);

            // Ubah icon sesuai kondisi
            if (box.classList.contains(activeClass)) {
                icon.classList.remove('bi-plus');
                icon.classList.add('bi-dash');
            } else {
                icon.classList.remove('bi-dash');
                icon.classList.add('bi-plus');
            }
        });
    }

    // Daftarkan semua toggle
    setupToggle('minimizeDash',  'tabelBA',  'iconDash',  'tabelAktif');
    setupToggle('minimizeDash2', 'tabelBA2', 'iconDash2', 'tabelAktif2');
    setupToggle('minimizeDash3', 'tabelBA3', 'iconDash3', 'tabelAktif3');
});

</script>

<script>
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
<script>
    $(document).ready(function () {
        $('#myTable2').DataTable({
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

<script>
    $(document).ready(function () {
        $('#myTable3').DataTable({
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
