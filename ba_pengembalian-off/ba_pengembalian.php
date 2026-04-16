<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../login_registrasi.php");
    exit();
}
if (!isset($_SESSION['hak_akses']) || $_SESSION['hak_akses'] !== 'Admin') {
    header("Location: ../personal/approval.php");
    exit();
}

//setup akses
include '../koneksi.php';
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
  <title>BA Pengembalian</title>

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
        margin-bottom: 25px;
    }

    .app-main{
        display: flex;
        align-items: center;
        margin-top: 40px;
    }

    .col-jenis-barang{
        position: relative;
    }

    .btn-jenis-barang{
        position: relative;
        right: 5px;
    }
    /* style table */

    .table-wrapper{
        width: 97%;
        height: auto;
        overflow-x: hidden;
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

    th:first-child, td:first-child { width: 4%;} /* No */
    th:nth-child(2), td:nth-child(2) { width: 6%; }  /* Tanggal */
    th:nth-child(3), td:nth-child(3) { width: 6%; }  /* No BA */
    th:nth-child(4), td:nth-child(4) { width: 90px; }  /* Pengembali */
    th:nth-child(5), td:nth-child(5) { width: 310px; }  /* Jenis Barang */
    /* th:nth-child(6), td:nth-child(6) { width: 40px; }
    th:nth-child(7), td:nth-child(7) { width: 40px; }
    th:nth-child(8), td:nth-child(8) { width: 40px; } */
    th:last-child, td:last-child { width: 30px;}   /* Actions */

    .popupInput, .popupEdit {
        
        width: 100%;
        padding: 25px 30px;
        border-radius: 10px;
        background: #ffffff;
    }

    .form-section {
        margin-bottom: 20px;
    }

    .popupInput label {
        display: flex;
        align-items: center;
        margin: 15px 0 8px 0;
        padding: 3px 3px;
        font-weight: normal;
        color:rgba(0, 0, 0, 1);
        width:max-content;
        font-size: small;
    }

    .form-section input, .form-section textarea,.form-control {
        width: 100%;
        padding: 8px 10px;
        border: 1px solid   #ccc;
        border-radius: 5px;
        box-sizing: border-box;
        font-size: small;
    }

    .form-section textarea {
        resize: vertical;
        min-height: 60px;
    }

    .form-section select{
        margin-top: 15px;
        margin-bottom: 8px;
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

    @media (max-width: 1670px) {
        .btn{
        margin-bottom: 5px;
    }
    }

    .bi-list, .bi-arrows-fullscreen, .bi-fullscreen-exit {
            color: #fff !important;
    }
    .custom-footer{
      background-color: white;
    }
    .dt-search input{
      height: 21px !important;
      width: 159px !important;
      padding: 4px 8px !important;
      margin-left: 7px !important;
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

<!-- <style>/* Pagination Styling */
    .pagination-container{
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 5px;
    }

    .pagination-container select {
    padding: 4px 8px;
    border-radius: 5px;
    border: none
    }

    .pagination-links a {
        text-decoration: none;
        border-radius: 4px;
        padding: 5px 10px;
        color: #333;
    }


  .pagination-links span {
    border: 1px solid #ccc;
    border-radius: 4px;
    padding: 5px 10px;
    color: #aaa;
  }
</style> -->

</head>
<body class="layout-fixed sidebar-expand-lg bg-body-tertiary overflow-x-hidden">

    <script src="../assets/js/jquery-3.7.1.min.js"></script>
    <script src="../assets/js/datatables.min.js"></script>

    <div class="app-wrapper">
    
    <nav class="app-header navbar navbar-expand sticky-top"> <!-- Header -->
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

            <li class="personalia-menu nav-item me-3 rounded">
                <i id="personaliaBtn" class="bi bi-brush-fill btn fw-bold text-white" style="box-shadow:none;"></i>
            </li>

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
            </div>
        </ul>
        <!--end::End Navbar Links-->
        </div>
        <!--end::Container-->
    </nav>

    <aside class="app-sidebar shadow" data-bs-theme="dark"> <!-- Sidebar -->
        <div class="sidebar-brand" style="border:none;">
        <a href="../index.php" class="brand-link">
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
            <!-- List BA Pengembalian -->
            <li class="nav-item">
                <a href="#" class="nav-link" aria-disabled="true">
                <i class="nav-icon bi bi-newspaper text-white"></i>
                <p class="text-white">
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
            <li class="nav-item">
                <a href="../ba_mutasi/ba_mutasi.php" class="nav-link">
                <i class="nav-icon bi bi-newspaper"></i>
                <p>
                    BA Mutasi
                </p>
                </a>
            </li>
            <!-- <li class="nav-item">
                <a href="../ba_mutasi/ba_mutasi.php" class="nav-link">
                <i class="nav-icon bi bi-newspaper"></i>
                <p>
                    BA Mutasi
                </p>
                </a>
            </li> -->
            <li class="nav-header">
                USER
            </li>
            <!-- <?php if ($_SESSION['hak_akses'] === 'Admin'): ?>
            <li class="nav-item">
                <a href="../personal/status.php" class="nav-link">
                <i class="nav-icon bi bi-clipboard2-fill"></i>
                <p>
                    Status Approval BA
                </p>
                </a>
            </li>
            <?php endif; ?> -->
            <li class="nav-item">
                <a href="../personal/approval.php" class="nav-link">
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
                <a href="../personal/riwayat.php" class="nav-link">
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

    <?php //Koneksi database
    include '../koneksi.php';

    // Ambil nilai filter dari parameter GET
    // $filter_pt = $_GET['lokasi_penerima'] ?? '';
    // $filter_tahun = $_GET['tahun'] ?? '';
    // $filter_bulan = $_GET['bulan'] ?? '';

    $filter_pt    = isset($_GET['lokasi_penerima']) ? $_GET['lokasi_penerima'] : '';
    $filter_tahun = isset($_GET['tahun']) ? $_GET['tahun'] : '';
    $filter_bulan = isset($_GET['bulan']) ? $_GET['bulan'] : '';


    // Siapkan bagian WHERE sesuai filter yang diisi
    $where_clauses = [];
    $params = [];
    $types = '';

    // Filter berdasarkan PT
    if (!empty($filter_pt) && $filter_pt !== 'all') {
        $where_clauses[] = "lokasi_penerima = ?";
        $params[] = $filter_pt;
        $types .= 's';
    }

    // Filter berdasarkan tahun
    if (!empty($filter_tahun) && $filter_tahun !== 'all') {
        $where_clauses[] = "YEAR(tanggal) = ?";
        $params[] = $filter_tahun;
        $types .= 's';
    }

    // Filter berdasarkan bulan
    if (!empty($filter_bulan) && $filter_bulan !== 'all') {
        $where_clauses[] = "MONTH(tanggal) = ?";
        $params[] = $filter_bulan;
        $types .= 's';
    }

    // Gabungkan WHERE jika ada filter
    $where_sql = '';
    if (!empty($where_clauses)) {
        $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
    }

    // Query utama
    $query = "SELECT * FROM berita_acara_pengembalian $where_sql ORDER BY tanggal DESC, nomor_ba DESC";

    // Eksekusi query
    $stmt = $koneksi->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();


    //------------------------------------------------------------------------------
    $tanggal_hari_ini = date('Y-m-d');
    $bulan_ini = date('m');
    $tahun_ini = date('Y');

    // Ambil nomor_ba tertinggi di bulan & tahun yang sama
    $stmt2 = $koneksi->prepare("
    SELECT nomor_ba 
    FROM berita_acara_pengembalian 
    WHERE MONTH(tanggal) = ? AND YEAR(tanggal) = ? 
    ORDER BY CAST(nomor_ba AS UNSIGNED) DESC 
    LIMIT 1
    ");
    $stmt2->bind_param("ss", $bulan_ini, $tahun_ini);
    $stmt2->execute();
    $result2 = $stmt2->get_result();
    $row2 = $result2->fetch_assoc();

    if ($row2 && is_numeric($row2['nomor_ba'])) {
        $last_nomor = (int)$row2['nomor_ba'];
        $nomor_ba_baru = str_pad($last_nomor + 1, 3, '0', STR_PAD_LEFT);
    } else {
        $nomor_ba_baru = '001';
    }
    ?>
    <!--Koneksi Atasan Karyawan-->
    <?php
    // Ambil semua data Dept. Head
    $query_atasan = $koneksi->query("SELECT nama, posisi, departemen FROM data_karyawan WHERE jabatan = 'Dept. Head' ORDER BY nama ASC");
    $data_atasan = [];
    while ($row2 = $query_atasan->fetch_assoc()) {
        $data_atasan[] = $row2;
    }
    ?>
    <!--Koneksi Nama Karyawan-->
    <?php
    // Ambil semua data user, nanti difilter via JavaScript
    $query_karyawan = $koneksi->query("SELECT nama, posisi, departemen, lantai FROM data_karyawan ORDER BY nama ASC");
    $data_karyawan = [];
    while ($row2 = $query_karyawan->fetch_assoc()) {
        $data_karyawan[] = $row2;
    }
    ?>

    <main class="custom-main app-main"><!-- Main Content -->
    
    <section class="table-wrapper bg-white position-relative overflow-visible d-flex flex-column">
        
        
        <h2>Daftar Berita Acara Pengembalian Inventaris</h2>

        <form method="GET" class="mb-3 d-flex flex-wrap gap-3">
            <select name="lokasi_penerima" class="form-select" onchange="this.form.submit()" style="width: 200px;">
                <option value="all">Semua PT</option>
                <option value="PT.MSAL (HO)" <?= $filter_pt === 'PT.MSAL (HO)' ? 'selected' : '' ?>>PT.MSAL (HO)</option>
                <!-- <option value="PT.MSAL (SITE)" <?= $filter_pt === 'PT.MSAL (SITE)' ? 'selected' : '' ?>>PT.MSAL (SITE)</option> -->
            </select>
            
            <select name="tahun" class="form-select" onchange="this.form.submit()" style="width: 200px;">
                <option value="all">Semua Tahun</option>
                <?php
                $current_year = date('Y');
                for ($y = $current_year; $y >= 2024; $y--) {
                    $selected = ($filter_tahun == $y) ? 'selected' : '';
                    echo "<option value='$y' $selected>$y</option>";
                }
                ?>
            </select>

            <select name="bulan" class="form-select" onchange="this.form.submit()" style="width: 200px;">
                <option value="all" <?= $filter_bulan === 'all' ? 'selected' : '' ?>>Semua Bulan</option>
                <?php
                $bulanIndo = [
                    1 => 'Januari', 2 => 'Februari', 3 => 'Maret',
                    4 => 'April', 5 => 'Mei', 6 => 'Juni',
                    7 => 'Juli', 8 => 'Agustus', 9 => 'September',
                    10 => 'Oktober', 11 => 'November', 12 => 'Desember'
                ];

                for ($i = 1; $i <= 12; $i++) {
                    $selected = ($filter_bulan == $i) ? 'selected' : '';
                    echo "<option value='$i' $selected>{$bulanIndo[$i]}</option>";
                }
                ?>
            </select>

            

        </form>
        
        <table id="myTable" class="table table-bordered table-striped text-center">
            <!-- <a href="form_input_ba_pengembalian.php" class="btn btn-success position-absolute" style="width:max-content;height:max-content;top:127px;left:220px;z-index:1;"><i class="bi bi-plus-lg"></i></a> -->
            <a href="#" id="tombolInputPopup" class="btn btn-success position-absolute" style="width:max-content;height:max-content;top:127px;left:220px;z-index:1;"><i class="bi bi-plus-lg"></i></a>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Tanggal</th>
                    <th>No BA</th>
                    <th>Pengembali</th>
                    <th>Jenis Barang</th>
                    
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $no = 1;
                while ($row = $result->fetch_assoc()):
                    // Ambil 2 jenis_barang dari barang_pengembalian
                    $barang_semua = [];
                    $barang_query = $koneksi->query("SELECT jenis_barang FROM barang_pengembalian WHERE ba_pengembalian_id = {$row['id']}");
                    while ($b = $barang_query->fetch_assoc()) {
                        $barang_semua[] = $b['jenis_barang'];
                    }
                    $barang_tampil = array_slice($barang_semua, 0, 2); // ambil max 2
                    $barang_json = htmlspecialchars(json_encode($barang_semua));
                ?>
                <tr>
                    <td><?= $no ?></td>
                    <td><?= htmlspecialchars($row['tanggal']) ?></td>
                    <td><?= htmlspecialchars($row['nomor_ba']) ?></td>
                    <td><?= htmlspecialchars($row['nama_pengembali']) ?></td>
                    <td class="col-jenis-barang" >
                        <div class="d-flex justify-content-between">
                        <div class="">
                            <?= implode(', ', $barang_tampil); ?>
                            <?php if (count($barang_semua) > 2): ?>
                                <span class="text-muted">+<?= count($barang_semua) - 2 ?> lainnya</span>
                            <?php endif; ?>
                        </div>
                        <div class="ps-3" style="width: max-content;">
                            <button type="button" class="btn-jenis-barang btn btn-sm btn-primary view-barang" data-barang="<?= $barang_json ?>" data-nomorba="<?= htmlspecialchars($row["nomor_ba"]) ?>" title="Lihat Barang">
                            <i class="bi bi-box-arrow-up-right"></i>
                            </button>
                        </div>
                        </div>
                        
                        
                    </td>
                    
                    <td>
                        <a class='btn btn-secondary btn-sm' href='detail_barang.php?id=<?= $row['id'] ?>'><i class="bi bi-eye-fill"></i></a>
                        <a class='btn btn-primary btn-sm' href='surat_output.php?id=<?= $row['id'] ?>' target='_blank'><i class="bi bi-file-earmark-text-fill"></i></a>
                        <!-- <a class='btn btn-warning btn-sm' href='form_edit_ba_pengembalian.php?id=<?= $row['id'] ?>'><i class="bi bi-feather"></i></a> -->
                        
                        <a class='btn btn-warning btn-sm tombolPopupEdit' href='#' data-id="<?= $row['id'] ?>">
                            <i class="bi bi-feather"></i>
                        </a>
                        
                        <a class='btn btn-danger btn-sm' href='delete.php?id=<?= $row['id'] ?> ' onclick="return confirm('Apakah Anda yakin ingin menghapus data ini?')"><i class="bi bi-x-lg"></i></a>
                    </td>
                </tr>
                <?php $no++; endwhile; ?>
            </tbody>
        </table>

        <div id="popupBoxInput" class="popup-box position-absolute bg-white top-0 rounded-1 flex-column justify-content-start align-items-center p-2" style="height: max-content;align-self:center; z-index: 9;width: 95%;">
            <div class="w-100 d-flex justify-content-between mb-2" style="height: max-content;">
                <h4 class="m-0 p-0">Input Berita Acara</h4>
                <a id="tombolClosePopup" class='btn btn-danger btn-sm' href='#' ><i class="bi bi-x-lg"></i></a>
            </div>

            <form class="popupInput" method="post" action="proses_simpan.php" enctype="multipart/form-data">
                <div class="form-section">
                    <div class="row">
                    <!-- <div class="ms-auto">
                        <a href="javascript:history.back()" class="btn btn-outline-warning fw-bold position-absolute"><i class="bi bi-arrow-90deg-left"></i></a>
                    </div> -->
                    <div class="col-6">
                        <div class="d-flex justify-content-center"><h4>Data Berita Acara</h4></div>
                        <div class="row mt-3 mb-3">
                            <div class="col-4">
                            <div class="input-group" style="width: 200px;">
                            <span class="input-group-text">Tanggal</span>
                            <input type="date" class="form-control" name="tanggal" id="tanggal" max="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d') ?>" required>
                            </div>
                            </div>
                            <div class="col-6">
                            <div class="input-group" style="width: 170px;">
                            <span class="input-group-text">Nomor BA</span>
                            <input type="number" name="nomor_ba" id="nomor_ba" class="form-control" value="<?= $nomor_ba_baru ?>" style="width: 10px;cursor:default;" readonly>
                            </div>
                            </div>
                            
                        </div>

                        <div class="pengembali border border-1 p-1 m-1 rounded-3">
                            <div class="row">
                            <h4>Pengembali</h4>
                            <div class="input-group">
                                <label class="input-group-text ps-2" style="width: 70px;" for="lokasi-pengembali">Lokasi</label>
                                <select name="lokasi_pengembali" id="lokasi-pengembali" class="form-select" onchange="tampilkanLantai('pengembali')" required>
                                <option value="">-- Pilih Lokasi --</option>
                                <option value="PT.MSAL (HO)">PT.MSAL (HO)</option>
                                </select>
                                <label id="lantai-pengembali-container" class="input-group-text" for="lantai-pengembali" style="display:none;width: 50px;">Lantai</label>
                                <select name="lantai_pengembali" id="lantai-pengembali" class="form-select" style="display:none;" onchange="filterNamaKaryawan('pengembali')">
                                </select>
                            </div>
                            </div>
                            
                            <div class="row">
                            <div class="input-group">
                                <label class="input-group-text ps-2" style="width: 70px;" for="nama-pengembali">Nama</label>
                                <select name="nama_pengembali" id="nama-pengembali" class="form-select" onchange="loadAtasan('pengembali')" required>
                                <option value="">-- Pilih Nama --</option>
                                </select>
                            </div>
                            <div class="input-group">
                                <label class="input-group-text ps-2" style="width: 70px;" for="atasan-pengembali">Atasan</label>
                                <input type="text" class="form-control" style="margin-top: 15px;margin-bottom:8px;cursor:default;" name="atasan_pengembali" id="atasan-pengembali" readonly>
                            </div>
                            </div>
                        </div>
                        
                        <div class="penerima border border-1 p-1 m-1 rounded-3">
                            <div class="row">
                            <h4>Penerima</h4>
                            <div class="input-group">
                                <label class="input-group-text ps-2" style="width: 70px;" for="lokasi-penerima">Lokasi</label>
                                <select name="lokasi_penerima" id="lokasi-penerima" class="form-select" onchange="tampilkanLantai('penerima')" required>
                                <option value="">-- Pilih Lokasi --</option>
                                <option value="PT.MSAL (HO)">PT.MSAL (HO)</option>
                                </select>
                                <label id="lantai-penerima-container" class="input-group-text" style="display:none;width: 50px;" for="lantai-penerima">Lantai</label>
                                <select name="lantai_penerima" id="lantai-penerima" class="form-select" style="display:none;" onchange="filterNamaKaryawan('penerima')">
                                </select>
                            </div>
                            </div>
                            <div class="row">
                            <div class="input-group">
                            <label class="input-group-text ps-2" style="width: 70px;" for="nama-penerima">Nama</label>
                            <select name="nama_penerima" id="nama-penerima" class="form-select" onchange="loadAtasan('penerima')" required>
                                <option value="">-- Pilih Nama --</option>
                            </select>
                            </div>
                            <div class="input-group">
                                <label class="input-group-text ps-2" style="width: 70px;" for="atasan-penerima">Atasan</label>
                                <input type="text" class="form-control" style="margin-top: 15px;margin-bottom:8px;cursor:default;" name="atasan_penerima" id="atasan-penerima" readonly>
                            </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-6">
                        <div class="row"> <!-- Data Barang -->
                            <div class="d-flex justify-content-center"><h4>Data Barang</h4></div>
                        <div class="data-barang border border-1 p-1 m-1 rounded-3 overflow-y-auto overflow-x-hidden" style="max-height: 300px;">
                            
                            <div class="row">
                            <div class="input-group mt-3">
                                <span class="input-group-text">Jenis Barang</span>
                                <textarea class="form-control" name="jenis_barang[]" required></textarea>
                            </div>
                            <div class="input-group" style="width: 500px;">
                                <label class="input-group-text ps-3" style="width: 113px;">Jumlah</label>
                                <input class="form-control" type="number" style="margin-top: 15px;margin-bottom:8px;" name="jumlah[]" required>
                                <label class="input-group-text" for="kondisi">Kondisi</label>
                                <select name="kondisi[]" class="form-select" required>
                                <option value="">-- Pilih Kondisi --</option>
                                <option value="Baik">Baik</option>
                                <option value="Rusak">Rusak</option>
                                </select>
                            </div>
                            <div class="input-group">
                                <span class="input-group-text" style="width: 113px;">Keterangan</span>
                                <textarea class="form-control" name="keterangan[]"></textarea>
                            </div>
                            </div>
                            <div id="data-barang" class="d-flex flex-column gap-2 mt-2"></div>
                            <div class="d-flex flex-column"><button type="button" class="btn btn-primary w-50 align-self-center" onclick="tambahDataBarang()">+ Tambah Data Barang</button></div>
                        </div>
                        </div>

                        <div class="row"> <!-- Data Gambar -->
                            <div class="d-flex justify-content-center">
                                <h4 class="mt-0 pt-0 mb-0">Gambar Lampiran</h4>
                            </div>
                        <div class="data-gambar border border-1 p-1 m-1 rounded-3">
                            
                            <div class="form-section mb-0 overflow-auto " style="max-height: 215px;">
                            <div id="gambar-container" class="d-flex flex-column gap-2"></div>
                            <div class="d-flex flex-column"><button type="button" class="btn btn-primary w-50 align-self-center" onclick="tambahGambar()">+ Tambah Gambar Lampiran</button></div>
                            </div>
                        </div>
                        
                        </div>
                    </div>  
                    </div>
                    </div>
                <div class="simpan d-flex flex-column"><input type="submit" value="Simpan" class="btn btn-success w-25 mt-3 align-self-end"></div>
            </form>
        </div>

        <div id="popupBoxEdit" class="popup-box position-absolute bg-white top-0 rounded-1 flex-column justify-content-start align-items-center p-2" style="height: max-content;align-self: center;z-index: 9;width: 95%;">
            <div class="w-100 d-flex justify-content-between mb-2" style="height: max-content;">
                <h4 class="m-0 p-0">Edit Berita Acara dengan ID : (isi ID)</h4>
                <a id="tombolClosePopupEdit" class='btn btn-danger btn-sm' href='#' ><i class="bi bi-x-lg"></i></a>
            </div>

            <div id="popupContentEdit"></div>
        </div>

    </section>
    
    </main>

    <div id="popupBG" class="popup-bg position-absolute w-100 h-100" style="background-color: rgba(0,0,0,0.5);"></div>
    
        <!--Awal::Footer Content-->
        <footer class="custom-footer d-flex position-relative p-2" style="border-top: whitesmoke solid 1px; box-shadow: 0px 7px 10px black; color: grey;">
            <p class="position-absolute" style="right: 15px;bottom:7px;color: grey;"><strong>Version </strong>1.1.0</p>
        <p class="pt-2 ps-1"><strong>Copyright &copy 2025</p></strong><p class="pt-2 ps-1 fw-bold text-primary">MIS MSAL.</p><p class="pt-2 ps-1"> All rights reserved</p>
        </footer>
        <!--Akhir::Footer Content-->

    </div>
    
    <!-- Modal -->
<div class="modal fade" id="barangModal" tabindex="-1" aria-labelledby="barangModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title" id="barangModalLabel">Jenis Barang</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
        </div>
        <div class="modal-body">
            <table class="table table-bordered table-striped text-center">
            <colgroup>
                <col style="width: 10%;">
                <col style="width: 90%;">
            </colgroup>
            <thead>
                <tr>
                <th>No</th>
                <th>Jenis Barang</th>
                </tr>
            </thead>
            <tbody id="barangTableBody">
                <!-- diisi JS -->
            </tbody>
            </table>
        </div>
        </div>
    </div>
</div>

<!-- Script -->

<script>// Fungsi untuk menambahkan input gambar
function tambahGambar() {
    const container = document.getElementById('gambar-container');

    const wrapper = document.createElement('div');
    wrapper.className = 'gambar-wrapper';
    wrapper.style.position = 'relative';
    wrapper.style.display = 'flex';
    wrapper.style.flexDirection = 'column';
    wrapper.style.gap = '5px';
    wrapper.style.marginBottom = '1rem';

    const input = document.createElement('input');
    input.type = 'file';
    input.name = 'gambar[]';
    input.accept = 'image/*';
    input.required = true;
    input.onchange = function () {
        const preview = wrapper.querySelector('img');
        const file = this.files[0];
        if (file) {
        preview.src = URL.createObjectURL(file);
        preview.style.display = 'block';
        }
    };

    const preview = document.createElement('img');
    preview.style.maxWidth = '200px';
    preview.style.height = 'auto';
    preview.style.marginTop = '5px';
    preview.style.display = 'none';
    preview.style.border = '1px solid #ccc';
    preview.style.borderRadius = '5px';

    const tombolHapus = document.createElement('div');
    tombolHapus.className = 'd-flex flex-column';
    const btnHapus = document.createElement('button');
    btnHapus.type = 'button';
    btnHapus.textContent = 'Hapus';
    btnHapus.className = 'btn btn-danger w-50 align-self-center';
    btnHapus.style.marginTop = '5px';
    btnHapus.onclick = function () {
        container.removeChild(wrapper);
    };
    tombolHapus.appendChild(btnHapus);

    wrapper.appendChild(input);
    wrapper.appendChild(preview);
    wrapper.appendChild(tombolHapus);

    container.appendChild(wrapper);
}
</script>

<script>// Lihat Detail Barang
    document.querySelectorAll('.view-barang').forEach(btn => {
        btn.addEventListener('click', () => {
        const data = JSON.parse(btn.getAttribute('data-barang'));
        const nomorBA = btn.getAttribute('data-nomorba');

        // Ganti judul modal
        document.getElementById('barangModalLabel').textContent = 'Jenis Barang BA ' + nomorBA;
        
        const tbody = document.getElementById('barangTableBody');
        tbody.innerHTML = '';
        data.forEach((item, i) => {
            tbody.innerHTML += `
            <tr>
                <td>${i + 1}</td>
                <td>${item}</td>
            </tr>
            `;
        });
        new bootstrap.Modal(document.getElementById('barangModal')).show();
        });
    });
</script>

<script>//datatables
    $(document).ready(function () {
        $('#myTable').DataTable({
        responsive: true,
        autoWidth: false,
        language: {
            url: "../assets/json/id.json"
        },
        columnDefs: [
            { targets: -1, orderable: false }, // Kolom Actions tidak bisa di-sort
            
        ]
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