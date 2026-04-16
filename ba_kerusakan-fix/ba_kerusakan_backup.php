<?php //File sedang proses support 5.6 PHP
session_start();

// Jika belum login, arahkan ke halaman login
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../login_registrasi.php");
    exit();
}

// Jika login tapi bukan Admin, arahkan ke halaman approval
if (!isset($_SESSION['hak_akses']) || $_SESSION['hak_akses'] !== 'Admin') {
    header("Location: ../personal/approval.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>BA Kerusakan</title>

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

    .aktifLT{
        display: flex;
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

    #thead th:nth-child(1), #tbody td:nth-child(1) { width: 4%; text-align: center; } /* No */
    #thead th:nth-child(2), #tbody td:nth-child(2) { width: 6%; }  /* Tanggal */
    #thead th:nth-child(3), #tbody td:nth-child(3) { width: 6%; }  /* Tanggal */
    #thead th:nth-child(4), #tbody td:nth-child(4) { width: 10%; }  /* Jenis Perangkat */
    #thead th:nth-child(5), #tbody td:nth-child(5) { width: 220px; }  /* Merek */
    #thead th:nth-child(6), #tbody td:nth-child(6) { width: 220px; }  /* User */
    #thead th:nth-child(7), #tbody td:nth-child(7) { width: 200px; }  /* Lokasi */
    #thead th:nth-child(8), #tbody td:nth-child(8) { width: 350px; }  /* Jenis Kerusakan */
    /*th:nth-child(9), td:nth-child(9) { width: 50px; }   Status Approval 1 */
    /*th:nth-child(10), td:nth-child(10) { width: 50px; }   Status Approval 2 */
    #thead th:nth-child(11), #tbody td:nth-child(11) { width: 50px; height:100% !important; text-align: center; }   /* Actions */

    #myTable2 td{
        cursor: pointer;
    }

    .popupInput, .popupEdit {
        
        width: 100%;
        padding: 25px 30px;
        border-radius: 10px;
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

    .popup-box{
        display: none;
    }

    .popup-bg{
        display:none;
    }

    .aktifPopup{
        display:flex;
    }

    .table-approval th,.table-approval td{
        border: none;
        padding: 5px;
    }

    .dataTable {
        width: 100% !important;
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
                    <a href="../logout.php" id="logoutTombol" class="btn btn-outline-danger fw-bold ps-3 gap-2 mt-2" onclick="return confirm('Yakin ingin logout?')" title="Logout">
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
                <a href="#" class="nav-link" aria-disabled="true">
                <i class="nav-icon bi bi-newspaper text-white"></i>
                <p class="text-white">
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
            <?php if ($_SESSION['hak_akses'] === 'Super Admin'): ?>
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

    //Tabel
    // Ambil nilai filter dari parameter GET
    $filter_pt = isset($_GET['pt']) ? $_GET['pt'] : '';
    $filter_tahun = isset($_GET['tahun']) ? $_GET['tahun'] : '';
    $filter_bulan = isset($_GET['bulan']) ? $_GET['bulan'] : '';

    // Siapkan bagian WHERE sesuai filter yang diisi
    $where_clauses = array();
    $params = array();
    $types = '';

    // Filter berdasarkan PT
    if (!empty($filter_pt) && $filter_pt !== 'all') {
        $where_clauses[] = "pt = ?";
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
    $query = "SELECT * FROM berita_acara_kerusakan $where_sql ORDER BY tanggal DESC, nomor_ba DESC";

    // Eksekusi query
    $stmt = $koneksi->prepare($query);
    if (!empty($params)) {
        $bind_names[] = $types;
        for ($i=0; $i<count($params); $i++) {
            $bind_name = 'bind' . $i;
            $$bind_name = $params[$i];
            $bind_names[] = &$$bind_name;
        }
        call_user_func_array(array($stmt, 'bind_param'), $bind_names);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    ?>


    
    <?php 
    //form input
    
    $tanggal_hari_ini = date('Y-m-d');
    $bulan_ini = date('m');
    $tahun_ini = date('Y');

    // Ambil nomor_ba tertinggi di bulan & tahun yang sama
    $stmt2 = $koneksi->prepare("
    SELECT nomor_ba 
    FROM berita_acara_kerusakan 
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
    <?php 
    $query_assets = "
    SELECT 
        assets.id,
        assets.serial,
        assets.name AS asset_name,
        assets.order_number,
        models.id AS model_id,
        models.category_id,
        models.manufacturer_id,
        categories.name AS category_name,
        manufacturers.name AS manufacturer_name
    FROM assets
    LEFT JOIN models ON assets.model_id = models.id
    LEFT JOIN categories ON models.category_id = categories.id
    LEFT JOIN manufacturers ON models.manufacturer_id = manufacturers.id
    ORDER BY assets.id DESC
    ";

    $result_assets = $koneksi->query($query_assets);
    ?>

    <main class="app-main"><!-- Main Content -->

        <!--Status Sukses Pop Up-->
        <?php if (isset($_SESSION['message'])): ?>
            <div class="w-100 d-flex justify-content-center position-absolute" style="height: max-content;">
                <div class="d-flex p-0 alert alert-success border-0 text-center fw-bold mb-0 position-absolute fade-in" id="infoin-approval" style="z-index:10;transition: opacity 0.5s ease;right:20px;width:max-content;height:max-content;">
                    <div class="d-flex justify-content-center align-items-center bg-success pe-2 ps-2 rounded-start text-white fw-bolder">
                        <i class="bi bi-check-lg"></i>
                    </div>
                    <p class="p-2 m-0" style="font-weight: 500;"><?= htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); ?></p>
                </div>
            </div>
        <?php endif; ?>

            <!-- <div class="w-100 d-flex justify-content-center position-absolute" style="height: max-content;">
                <div class="d-flex p-0 alert alert-warning border-0 text-center fw-bold mb-0 position-absolute fade-in" id="infoin-approval" style="z-index:10;transition: opacity 0.5s ease;right:20px;width:max-content;height:max-content;">
                    <div class="d-flex justify-content-center align-items-center bg-warning pe-2 ps-2 rounded-start text-white fw-bolder">
                        <i class="bi bi-check-lg"></i>
                    </div>
                    <p class="p-2 m-0" style="font-weight: 500;">Data berhasil disimpan ke database.</p>
                </div>
            </div> -->
                
    
    <section class="table-wrapper bg-white position-relative overflow-visible d-flex flex-column">
        <h2>Daftar Berita Acara Kerusakan Aset</h2>

        <form method="GET" class="mb-3 d-flex flex-wrap gap-3">
            <select name="pt" class="form-select" onchange="this.form.submit()" style="width: 200px;">
                <option value="all">Semua PT</option>
                <option value="PT.MSAL (HO)" <?= $filter_pt === 'PT.MSAL (HO)' ? 'selected' : '' ?>>PT.MSAL (HO)</option>
                <!-- <option value="PT.MSAL (SITE)" <?= $filter_pt === 'PT.MSAL (SITE)' ? 'selected' : '' ?>>PT.MSAL (SITE)</option> -->
            </select>
            
            <select name="tahun" class="form-select" onchange="this.form.submit()" style="width: 200px;">
                <option value="all">Semua Tahun</option>
                <?php
                $current_year = date('Y');
                for ($y = $current_year; $y >= 2025; $y--) {
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

        <div id="tableSkeleton">
            
            <table class="table table-borderless">
                <thead>
                    <tr>
                        <th><div class="skeleton skeleton-header"></div></th>
                        <th><div class="skeleton skeleton-header d-none"></div></th>
                        <th><div class="skeleton skeleton-header d-none"></div></th>
                        <th><div class="skeleton skeleton-header d-none"></div></th>
                        <th><div class="skeleton skeleton-header d-none"></div></th>
                    </tr>
                </thead>
                <tbody>
                    <?php for ($i = 0; $i < 8; $i++) { ?>
                    <tr>
                        <td style="border: #e0e0e0 1px solid;"><div class="skeleton"></div></td>
                        <td style="border: #e0e0e0 1px solid;"><div class="skeleton"></div></td>
                        <td style="border: #e0e0e0 1px solid;"><div class="skeleton"></div></td>
                        <td style="border: #e0e0e0 1px solid;"><div class="skeleton"></div></td>
                        <td style="border: #e0e0e0 1px solid;"><div class="skeleton"></div></td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
        
        <table id="myTable" class="table table-bordered table-striped text-center">
            <!-- <a href="form_input_ba_kerusakan.php" class="btn btn-success position-absolute" style="width:max-content;height:max-content;top:127px;left:220px;z-index:1;"><i class="bi bi-plus-lg"></i></a> -->
            <a href="#" id="tombolInputPopup" class="btn btn-success position-absolute" style="width:max-content;height:max-content;top:127px;left:220px;z-index:1;"><i class="bi bi-plus-lg"></i></a>
            <!-- <a href="#" id="tombolInputPopup2" class="btn btn-success position-absolute" style="width:max-content;height:max-content;top:127px;left:270px;z-index:1;"><i class="bi bi-plus-lg"></i></a> -->
            <thead class="bg-secondary" id="thead-utama">
                <tr>
                    <th>No</th>
                    <th>Tanggal</th>
                    <th>Nomor BA</th>
                    <th>Jenis Perangkat</th>
                    <th>Merek</th>
                    <th>User</th>
                    <th>Lokasi</th>
                    <th>Jenis Kerusakan</th>
                    <!-- <th>Status Approval 1</th>
                    <th>Status Approval 2</th> -->
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="tbody-utama">
                <?php
                $no = 1;
                while ($row = $result->fetch_assoc()):
                ?>
                <tr>
                    <td><?= $no ?></td>
                    <td><?= htmlspecialchars($row['tanggal']) ?></td>
                    <td><?= htmlspecialchars($row['nomor_ba']) ?></td>
                    <td><?= htmlspecialchars($row['jenis_perangkat']) ?></td>
                    <td><?php
                        $merek = htmlspecialchars($row['merek']);
                        $words = explode(' ', $merek);
                        $limited = array_slice($words, 0, 4);
                        echo nl2br(implode(' ', $limited));
                        if (count($words) > 8) {
                            echo '...';
                        }
                        ?>
                    </td>
                    <td><?= htmlspecialchars($row['user']) ?></td>
                    <td><?= htmlspecialchars($row['pt']) ?> <?= htmlspecialchars($row['lokasi']) ?></td>
                    <td>
                        <?php
                        $deskripsi = htmlspecialchars($row['deskripsi']);
                        $words = explode(' ', $deskripsi);
                        $limited = array_slice($words, 0, 4);
                        echo nl2br(implode(' ', $limited));
                        if (count($words) > 8) {
                            echo '...';
                        }
                        ?>
                    </td>
                    <!-- <td style="padding-top:13px;">
                        <span class="border fw-bold <?= $row['approval_1'] == 1 ? 'bg-success-subtle border-success-subtle text-success' : 'bg-warning-subtle border-warning-subtle text-warning' ?>" style="border-radius: 6px; padding: 6px 12px;">
                            <?= htmlspecialchars($row['approval_1'] == 1 ? 'Disetujui' : 'Menunggu') ?>
                        </span>
                    </td>
                    <td style="padding-top:13px;">
                        <span class="border fw-bold <?= $row['approval_2'] == 1 ? 'bg-success-subtle border-success-subtle text-success' : 'bg-warning-subtle border-warning-subtle text-warning' ?>" style="border-radius: 6px; padding: 6px 12px;">
                            <?= htmlspecialchars($row['approval_2'] == 1 ? 'Disetujui' : 'Menunggu') ?>
                        </span>
                    </td> -->
                    <td>
                        <!-- <div class="d-flex gap-2"> -->

                            <a class="btn btn-secondary btn-sm btn-detail-ba" href="#" data-id="<?= $row['id'] ?>">
                                <i class="bi bi-eye-fill"></i>
                            </a>
                            
                            <a class='btn btn-primary btn-sm' href='surat_output.php?id=<?= $row['id'] ?>' target='_blank'><i class="bi bi-file-earmark-text-fill"></i></a>
                        <!-- </div> -->
                        <?php if ($row['approval_2'] != 1 && $_SESSION['nama'] === $row['nama_pembuat']): ?>
                        <!-- <div class="d-flex gap-2"> -->
                            <!-- <a class='btn btn-warning btn-sm' href='form_edit_ba_kerusakan.php?id=<?= $row['id'] ?>'><i class="bi bi-feather"></i></a> -->
                            
                            <a class='btn btn-warning btn-sm tombolPopupEdit' href='#' data-id="<?= $row['id'] ?>">
                                <i class="bi bi-feather"></i>
                            </a>

                            <a class='btn btn-danger btn-sm' href='delete.php?id=<?= $row['id'] ?> ' onclick="return confirm('Apakah Anda yakin ingin menghapus data ini?')"><i class="bi bi-x-lg"></i></a>
                        <!-- </div> -->
                        <?php endif; ?>
                        
                    </td>
                </tr>
                <?php $no++; endwhile; ?>
            </tbody>
        </table>
        
        <div id="popupBoxInput" class="popup-box position-absolute bg-white top-0 rounded-1 flex-column justify-content-start align-items-center p-2" style="height: max-content;align-self: center;z-index: 9;width: 95%;">
            
            <div class="w-100 d-flex justify-content-between mb-2" style="height: max-content;">
                <h4 class="m-0 p-0">Input Berita Acara</h4>
                <a id="tombolClosePopup" class='btn btn-danger btn-sm' href='#' ><i class="bi bi-x-lg"></i></a>
            </div>
            
            <form class="popupInput d-flex flex-column" method="post" action="proses_simpan.php" enctype="multipart/form-data">
                <div class="form-section">
                    <div class="row position-relative">
                    
                        <div class="col-8">
                            <h3>Data Berita Acara</h3>
                            <div class="row">
                            <div class="col-3">
                                <div class="input-group" style="width:220px;">
                                <span class="input-group-text">Tanggal</span>
                                <input class="form-control "  type="date" name="tanggal" id="tanggal" max="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d') ?>" required>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="input-group" style="width:180px;">
                                <span class="input-group-text">Nomor BA</span>
                                <input type="text" class="form-control" name="nomor_ba" id="nomor_ba" value="<?= $nomor_ba_baru ?>" readonly>
                                </div>
                            </div>
                            </div>

                            <div class="row mt-3 border border-1 p-1 rounded-2 me-1">
                                <div class="row pt-1 pb-2">
                                    <div class="col-4 d-flex flex-column">
                                        <h5>Data barang</h5>
                                        <div class="d-flex">
                                            <div id="tombolDataBarangPopup" class="btn btn-primary rounded-end-0"><i class="bi bi-search"></i></div>
                                            <div class="btn btn-primary rounded-start-0"><i class="bi bi-qr-code-scan"></i></div>
                                        </div>
                                        
                                    </div>
                                    
                                </div>
                                <div class="col-6">
                                    <div class="input-group">
                                    <span class="input-group-text" style="padding-right:37px;">SN</span>
                                    <input id="serial_number" class="form-control" type="text" name="sn" value="" readonly>
                                    </div>
                                </div>
                                <div class="input-group w-50">
                                    <span class="input-group-text" style="padding-right:52px;">Jenis Perangkat</span>
                                    <input id="jenis_perangkat" class="form-control" type="text" name="jenis_perangkat" value="" readonly>
                                </div>
                                <div class="col-6 mt-3">
                                <div class="input-group">
                                    <span class="input-group-text">Merek</span>
                                    <input id="merek" class="form-control" type="text" name="merek" value="" readonly>
                                </div>
                                </div>
                                <div class="col-6 mt-3">
                                <div class="input-group">
                                    <span class="input-group-text" style="padding-right:45px;">Tahun Perolehan</span>
                                    <input id="tahun_perolehan" type="text" class="form-control" name="tahun_perolehan" value="" readonly>
                                </div>
                                </div>
                            </div>

                            <!-- <div class="row mt-3 border border-1 p-1 rounded-2 me-1">
                            <div class="row">
                                <h5>Data barang</h5>
                            </div>
                            <div class="col-6">
                                <div class="input-group">
                                <span class="input-group-text" style="padding-right:19px;">Jenis perangkat</span>
                                <input type="text" class="form-control" name="jenis_perangkat" required>
                                </div>
                            </div>
                            
                            <div class="input-group w-50">
                                <span class="input-group-text" style="padding-right:22px;">Merek</span>
                                <textarea rows="1" class="form-control" name="merek" required></textarea>
                            </div>
                            <div class="col-6 mt-3">
                                <div class="input-group">
                                <span class="input-group-text">Tahun Perolehan</span>
                                <input class="form-control" type="number" name="tahun_perolehan" id="tahun_perolehan" min="2007" max="<?= date('Y') ?>" step="1" required>
                                
                            </div>
                            </div>
                            <div class="col-6 mt-3">
                                <div class="input-group">
                                <span class="input-group-text" style="padding-right: 45px;">SN</span>
                                <input type="text" class="form-control" name="sn" required>
                                </div>
                            </div>
                            
                            </div> -->

                            <div class="row mt-3 border border-1 p-1 rounded-2 me-1">
                            <div class="row">
                                <h5>Data Pengguna</h5>
                            </div>
                            <div class="row">
                                <div class="col-4">
                                <div class="input-group">
                                    <span class="input-group-text">Lokasi</span>
                                    <select name="pt" id="pt" class="form-select" required>
                                    <option value="">-- Pilih Lokasi --</option>
                                    <option value="PT.MSAL (HO)">PT.MSAL (HO)</option>
                                    </select>
                                </div>
                                </div>
                                <div class="col-3" id="lantai-wrapper" style="display: none;">
                                <div class="input-group">
                                    <span class="input-group-text">Lantai</span>
                                    <select name="lokasi" id="lokasi" class="form-select">
                                    <option value="">-- Pilih Lantai --</option>
                                    
                                    <!--Koneksi Label lantai-->
                                    <?php
                                    $resultLantai = $koneksi->query("SELECT DISTINCT lantai FROM data_karyawan ORDER BY lantai ASC");
                                    while ($row2 = $resultLantai->fetch_assoc()):
                                        $value = $row2['lantai'];
                                        if (preg_match('/^LT\.(\d+)/i', $value, $match)) {
                                            $label = "Lantai " . $match[1];
                                        } else {
                                            $label = $value;
                                        }
                                    ?>

                                        <option value="<?= htmlspecialchars($value) ?>"><?= htmlspecialchars($label) ?></option>
                                    <?php endwhile; ?>
                                </select>
                                </div>
                                </div>
                                
                            </div>

                            <div class="row mt-3 pe-0" id="user-wrapper" style="display: none;">
                                <div class="col-6">
                                <div class="input-group">
                                    <span class="input-group-text">Pengguna</span>
                                    <select name="user" id="user" class="form-select" required>
                                        <option value="">-- Pilih Pengguna --</option>
                                    </select>
                                </div>
                                </div>
                                <div class="col-6 pe-0">
                                <div class="input-group">
                                    <span class="input-group-text">Atasan Peminjam</span>
                                    <select name="atasan_peminjam" id="atasan_peminjam" class="form-select" required>
                                    <option value="">-- Pilih Atasan Peminjam --</option>
                                    </select>
                                </div>
                                </div>
                            </div>
                            </div>

                            <div class="row mt-3 border border-1 p-1 rounded-2 me-1">
                            <div class="row">
                                <h5>Laporan Kerusakan</h5>
                            </div>
                            <div class="row pe-0">
                                <div class="col-6">
                                <div class="input-group">
                                    <span class="input-group-text" style="padding-right: 27px;">Jenis Kerusakan</span>
                                    <textarea name="deskripsi" class="form-control" style="font-size:small;" rows="3" required></textarea>
                                </div>
                                </div>
                                <div class="col-6 pe-0">
                                <div class="input-group">
                                    <span class="input-group-text">Penyebab Kerusakan</span>
                                    <textarea name="penyebab_kerusakan" class="form-control" style="font-size:small;" rows="3" required></textarea>
                                </div>
                                </div>
                            </div>
                            <div class="row mt-3 pe-0">
                                <div class="col-12 pe-0">
                                <div class="input-group">
                                    <span class="input-group-text">Rekomendasi MIS</span>
                                    <textarea name="rekomendasi_mis" class="form-control" style="font-size:small;" rows="2" required></textarea>
                                </div>
                                </div>
                            </div>
                            </div>
                            
                        </div>

                        <div class="col-4" >
                            <h3>Gambar Kerusakan</h3>
                            <div class="border border-2 rounded-3 p-1" style="height:485px; overflow-y:auto;">
                                <div class=" d-flex flex-column">
                                <div id="gambar-container" class="d-flex flex-column gap-2"></div>
                                <button type="button" class="btn btn-primary w-50 align-self-center mb-1" onclick="tambahGambar()">+ Tambah Gambar Laporan</button>
                                </div>
                            </div>
                        </div>

                    </div>
                    
                </div>
                <input class="w-25 align-self-end" type="submit" value="Simpan">
            </form>
        </div>

        <div id="popupBoxEdit" class="popup-box position-absolute bg-white top-0 rounded-1 flex-column justify-content-start align-items-center p-2" style="height: max-content;align-self: center;z-index: 9;width: 95%;">
            
            <div class="w-100 d-flex justify-content-between mb-2" style="height: max-content;">
                <h4 id="popupEditTitle" class="m-0 p-0">Edit Berita Acara</h4>
                <a id="tombolClosePopupEdit" class='btn btn-danger btn-sm' href='#'>
                    <i class="bi bi-x-lg"></i>
                </a>
            </div>
            <div id="popupEditBody" class="w-100"></div>
            <!-- Form diisi JavaScript -->
        </div>

        <div id="popupBoxDetail" class="popup-box position-absolute bg-white top-0 rounded-1 flex-column justify-content-start align-items-center p-2" style="height: max-content;align-self: center;z-index: 9;width: 95%;">
            <div class="w-100 d-flex justify-content-between mb-2" style="height: max-content;">
                <h4 id="popupDetailTitle" class="m-0 p-0">Detail Berita Acara</h4>
                <a id="tombolClosePopupDetail" class='btn btn-danger btn-sm' href='#'> 
                    <i class="bi bi-x-lg"></i>
                </a>
            </div>
            <div id="popupDetailBody" class="w-100"></div>
        </div>

        <div id="popupBoxDataBarang" class="popup-box position-absolute bg-white top-0 rounded-1 flex-column justify-content-start align-items-center p-2" style="height: max-content;align-self: center;z-index: 10;width: 95%;">
            <div class="w-100 d-flex justify-content-between mb-2" style="height: max-content;">
                <h4 id="popupDataBarangTitle" class="m-0 p-0">Tabel Data Barang</h4>
                <a id="tombolClosePopupDataBarang" class='btn btn-danger btn-sm' href='#'>
                    <i class="bi bi-x-lg"></i>
                </a>
            </div>
            <p class="m-0 p-0 align-self-start">Klik pada baris tabel data untuk memilih</p>
            <div class="w-100" style="height: max-content;">
                <table id="myTable2" class="table table-bordered table-striped text-center" style="width: 100%;">
                <thead class="bg-secondary">
                    <tr>
                        <th>No</th>
                        <th>Serial Number</th>
                        <th>Jenis Perangkat</th>
                        <th>Merek</th>
                        <th>Tahun Perolehan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $no = 1;
                    while ($row = $result_assets->fetch_assoc()):
                        // Serial Number
                        $serial = !empty($row['serial']) ? htmlspecialchars($row['serial']) : '-';

                        // Jenis Perangkat
                        $jenis_perangkat = !empty($row['category_name']) ? htmlspecialchars($row['category_name']) : '-';

                        // Merek (gabungan manufacturer + asset name)
                        $merek = '';
                        if (!empty($row['manufacturer_name'])) {
                            $merek .= htmlspecialchars($row['manufacturer_name']) . ' ';
                        }
                        $merek .= htmlspecialchars($row['asset_name']);

                        // Tahun Perolehan
                        $tahun = '-';
                        if (!empty($row['order_number']) && $row['order_number'] !== '1') {
                            $parts = explode('/', $row['order_number']);
                            if (isset($parts[4]) && is_numeric($parts[4])) {
                                $tahun = "20" . $parts[4];
                            }
                        }
                    ?>
                    <tr 
                        class="pilih-barang"
                        data-serial="<?= $serial ?>"
                        data-jenis="<?= $jenis_perangkat ?>"
                        data-merek="<?= $merek ?>"
                        data-tahun="<?= $tahun ?>"
                    >
                        <td><?= $no ?></td>
                        <td><?= $serial ?></td>
                        <td><?= $jenis_perangkat ?></td>
                        <td><?= $merek ?></td>
                        <td><?= $tahun ?></td>
                    </tr>
                <?php 
                $no++;
                endwhile;
                ?>
                </tbody>
            </table>
            </div>
            
        </div>

    </section>
    
    </main>
        
        <div id="popupBG" class="popup-bg position-absolute w-100 h-100" style="background-color: rgba(0,0,0,0.5);"></div>
        <div id="popupBG2" class="popup-bg position-absolute w-100 h-100" style="background-color: rgba(0,0,0,0.2); z-index: 9;"></div>

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
    //Menghilangkan alert
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
    const logoutButton = document.getElementById('logoutTombol');

    button.addEventListener('click', function () {
    box.classList.toggle('aktif');
    logoutButton.style.display = box.classList.contains('aktif') ? 'flex' : 'none';
    });
});
</script>

<script>
    //Sistem tombol popup data barang
document.addEventListener('DOMContentLoaded', function () {
    const open = document.getElementById('tombolDataBarangPopup');
    const close = document.getElementById('tombolClosePopupDataBarang');
    const box = document.getElementById('popupBoxDataBarang');
    const background = document.getElementById('popupBG2');

    open.addEventListener('click', function () {
        box.classList.add('aktifPopup');
        // box.classList.add('scale-in-center');
        // box.classList.remove('scale-out-center');
        background.classList.add('aktifPopup');
        // background.classList.add('fade-in');
        // background.classList.remove('fade-out');
    });

    close.addEventListener('click', function () {
        box.classList.remove('aktifPopup');
        // box.classList.remove('scale-in-center');
        // box.classList.add('scale-out-center');
        background.classList.remove('aktifPopup');
        // background.classList.remove('fade-in');
        // background.classList.add('fade-out');
    });
    background.addEventListener('click', function () {
        box.classList.remove('aktifPopup');
        // box.classList.remove('scale-in-center');
        // box.classList.add('scale-out-center');
        background.classList.remove('aktifPopup');
        // background.classList.remove('fade-in');
        // background.classList.add('fade-out');
    });
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Saat klik salah satu baris tabel Data Barang
    $(document).on('click', '.pilih-barang', function () {
        const serial = $(this).data('serial');
        const jenis = $(this).data('jenis');
        const merek = $(this).data('merek');
        const tahun = $(this).data('tahun');

        // Isi field readonly di form Input BA
        $('#serial_number').val(serial);
        $('#jenis_perangkat').val(jenis);
        $('#merek').val(merek);
        $('#tahun_perolehan').val(tahun);

        // Tutup popup Data Barang
        $('#popupBoxDataBarang').removeClass('aktifPopup');
        $('#popupBG2').removeClass('aktifPopup');
    });
});
</script>


<script>//Form Input
//Sistem tombol popup input
document.addEventListener('DOMContentLoaded', function () {
    const open = document.getElementById('tombolInputPopup');
    const close = document.getElementById('tombolClosePopup');
    const box = document.getElementById('popupBoxInput');
    const background = document.getElementById('popupBG');

    open.addEventListener('click', function () {
        box.classList.add('aktifPopup');
        // box.classList.add('scale-in-center');
        // box.classList.remove('scale-out-center');
        background.classList.add('aktifPopup');
        // background.classList.add('fade-in');
        // background.classList.remove('fade-out');
    });

    close.addEventListener('click', function () {
        box.classList.remove('aktifPopup');
        // box.classList.remove('scale-in-center');
        // box.classList.add('scale-out-center');
        background.classList.remove('aktifPopup');
        // background.classList.remove('fade-in');
        // background.classList.add('fade-out');
    });
    background.addEventListener('click', function () {
        box.classList.remove('aktifPopup');
        // box.classList.remove('scale-in-center');
        // box.classList.add('scale-out-center');
        background.classList.remove('aktifPopup');
        // background.classList.remove('fade-in');
        // background.classList.add('fade-out');
    });
});

//Fungsi nomor BA
document.addEventListener('DOMContentLoaded', function () {
const tanggalInput = document.getElementById('tanggal');
const nomorBaInput = document.getElementById('nomor_ba');

function updateNomorBA() {
    const tanggal = tanggalInput.value;
    if (!tanggal) return;

    fetch(`ambil_nomor_ba.php?tanggal=${tanggal}`)
    .then(response => response.text())
    .then(data => {
        nomorBaInput.value = data;
    })
    .catch(err => {
        console.error('Gagal mengambil nomor BA:', err);
    });
}

// Trigger pertama kali saat halaman load
updateNomorBA();

// Update saat tanggal diubah
tanggalInput.addEventListener('change', updateNomorBA);
});

//Trigger data karyawan via PT
const ptSelect = document.getElementById('pt');
const lantaiWrapper = document.getElementById('lantai-wrapper');
const userWrapper = document.getElementById('user-wrapper');

// Saat PT dipilih
ptSelect.addEventListener('change', function () {
const selectedPT = this.value;
if (selectedPT === 'PT.MSAL (HO)') {
    lantaiWrapper.style.display = 'flex';
    userWrapper.style.display = 'flex';
} else {
    lantaiWrapper.style.display = 'none';
    userWrapper.style.display = 'none';
    document.getElementById('lokasi').value = '';
    document.getElementById('user').innerHTML = '<option value="">-- Pilih Pengguna --</option>';
    document.getElementById('atasan_peminjam').innerHTML = '<option value="">-- Pilih Atasan Peminjam --</option>';
}
});

//Fungsi Sortir Karyawan dan Atasan Karyawan
const userSelect = document.getElementById('user');
const lantaiSelect = document.getElementById('lokasi');

// Data user dari PHP dimasukkan ke JS
const dataKaryawan = <?= json_encode($data_karyawan) ?>;
const dataDeptHead = <?= json_encode($data_atasan) ?>;

lantaiSelect.addEventListener('change', function () {
const selectedLantai = this.value;
userSelect.innerHTML = '<option value="">-- Pilih Pengguna --</option>';

if (selectedLantai === '') {
userSelect.disabled = true;
return;
}

// Filter berdasarkan lantai
const filtered = dataKaryawan.filter(row => row.lantai === selectedLantai);

filtered.forEach(row => {
const label = `${row.nama} - ${row.posisi} (${row.departemen})`;
const option = document.createElement('option');
option.value = row.nama;
option.textContent = label;
userSelect.appendChild(option);
});

userSelect.disabled = false;
});
const atasanSelect = document.getElementById('atasan_peminjam');

userSelect.addEventListener('change', function () {
const selectedNama = this.value;
const userData = dataKaryawan.find(k => k.nama === selectedNama);

atasanSelect.innerHTML = '<option value="">-- Pilih Atasan Peminjam --</option>';

if (!userData) {
atasanSelect.disabled = true;
return;
}

// Filter atasan berdasarkan departemen yang sama
const userDept = userData.departemen;
const filteredAtasan = dataDeptHead.filter(a => a.departemen === userDept);

filteredAtasan.forEach(atasan => {
const option = document.createElement('option');
option.value = atasan.nama;
option.textContent = `${atasan.nama} - ${atasan.posisi} (${atasan.departemen})`;
atasanSelect.appendChild(option);
});

atasanSelect.disabled = filteredAtasan.length === 0;
});

// Fungsi untuk menambahkan input gambar
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
preview.style.maxWidth = '300px';
preview.style.height = 'auto';
preview.style.marginTop = '5px';
preview.style.display = 'none';
preview.style.border = '1px solid #ccc';
preview.style.borderRadius = '5px';

const btnHapus = document.createElement('button');
btnHapus.type = 'button';
btnHapus.textContent = 'Hapus';
btnHapus.className = 'btn btn-danger';
btnHapus.style.marginTop = '5px';
btnHapus.onclick = function () {
    container.removeChild(wrapper);
};

wrapper.appendChild(input);
wrapper.appendChild(preview);
wrapper.appendChild(btnHapus);

container.appendChild(wrapper);
}
</script>

<script>//Form Input Rollback
// //Sistem tombol popup input
// document.addEventListener('DOMContentLoaded', function () {
//     const open = document.getElementById('tombolInputPopup');
//     const close = document.getElementById('tombolClosePopup');
//     const box = document.getElementById('popupBoxInput');
//     const background = document.getElementById('popupBG');

//     open.addEventListener('click', function () {
//         box.classList.add('aktifPopup');
//         // box.classList.add('scale-in-center');
//         // box.classList.remove('scale-out-center');
//         background.classList.add('aktifPopup');
//         // background.classList.add('fade-in');
//         // background.classList.remove('fade-out');
//     });

//     close.addEventListener('click', function () {
//         box.classList.remove('aktifPopup');
//         // box.classList.remove('scale-in-center');
//         // box.classList.add('scale-out-center');
//         background.classList.remove('aktifPopup');
//         // background.classList.remove('fade-in');
//         // background.classList.add('fade-out');
//     });
//     background.addEventListener('click', function () {
//         box.classList.remove('aktifPopup');
//         // box.classList.remove('scale-in-center');
//         // box.classList.add('scale-out-center');
//         background.classList.remove('aktifPopup');
//         // background.classList.remove('fade-in');
//         // background.classList.add('fade-out');
//     });
// });

// //Fungsi nomor BA
// document.addEventListener('DOMContentLoaded', function () {
// const tanggalInput = document.getElementById('tanggal');
// const nomorBaInput = document.getElementById('nomor_ba');

// function updateNomorBA() {
//     const tanggal = tanggalInput.value;
//     if (!tanggal) return;

//     fetch(`ambil_nomor_ba.php?tanggal=${tanggal}`)
//     .then(response => response.text())
//     .then(data => {
//         nomorBaInput.value = data;
//     })
//     .catch(err => {
//         console.error('Gagal mengambil nomor BA:', err);
//     });
// }

// // Trigger pertama kali saat halaman load
// updateNomorBA();

// // Update saat tanggal diubah
// tanggalInput.addEventListener('change', updateNomorBA);
// });

// //Trigger data karyawan via PT
// const ptSelect = document.getElementById('pt');
// const lantaiWrapper = document.getElementById('lantai-wrapper');
// const userWrapper = document.getElementById('user-wrapper');

// // Saat PT dipilih
// ptSelect.addEventListener('change', function () {
// const selectedPT = this.value;
// if (selectedPT === 'PT.MSAL (HO)') {
//     lantaiWrapper.style.display = 'flex';
//     userWrapper.style.display = 'flex';
// } else {
//     lantaiWrapper.style.display = 'none';
//     userWrapper.style.display = 'none';
//     document.getElementById('lokasi').value = '';
//     document.getElementById('user').innerHTML = '<option value="">-- Pilih Pengguna --</option>';
//     document.getElementById('atasan_peminjam').innerHTML = '<option value="">-- Pilih Atasan Peminjam --</option>';
// }
// });

// //Fungsi Sortir Karyawan dan Atasan Karyawan
// const userSelect = document.getElementById('user');
// const lantaiSelect = document.getElementById('lokasi');

// // Data user dari PHP dimasukkan ke JS
// const dataKaryawan = <?= json_encode($data_karyawan) ?>;
// const dataDeptHead = <?= json_encode($data_atasan) ?>;

// lantaiSelect.addEventListener('change', function () {
// const selectedLantai = this.value;
// userSelect.innerHTML = '<option value="">-- Pilih Pengguna --</option>';

// if (selectedLantai === '') {
// userSelect.disabled = true;
// return;
// }

// // Filter berdasarkan lantai
// const filtered = dataKaryawan.filter(row => row.lantai === selectedLantai);

// filtered.forEach(row => {
// const label = `${row.nama} - ${row.posisi} (${row.departemen})`;
// const option = document.createElement('option');
// option.value = row.nama;
// option.textContent = label;
// userSelect.appendChild(option);
// });

// userSelect.disabled = false;
// });
// const atasanSelect = document.getElementById('atasan_peminjam');

// userSelect.addEventListener('change', function () {
// const selectedNama = this.value;
// const userData = dataKaryawan.find(k => k.nama === selectedNama);

// atasanSelect.innerHTML = '<option value="">-- Pilih Atasan Peminjam --</option>';

// if (!userData) {
// atasanSelect.disabled = true;
// return;
// }

// // Filter atasan berdasarkan departemen yang sama
// const userDept = userData.departemen;
// const filteredAtasan = dataDeptHead.filter(a => a.departemen === userDept);

// filteredAtasan.forEach(atasan => {
// const option = document.createElement('option');
// option.value = atasan.nama;
// option.textContent = `${atasan.nama} - ${atasan.posisi} (${atasan.departemen})`;
// atasanSelect.appendChild(option);
// });

// atasanSelect.disabled = filteredAtasan.length === 0;
// });

// // Fungsi untuk menambahkan input gambar
// function tambahGambar() {
// const container = document.getElementById('gambar-container');

// const wrapper = document.createElement('div');
// wrapper.className = 'gambar-wrapper';
// wrapper.style.position = 'relative';
// wrapper.style.display = 'flex';
// wrapper.style.flexDirection = 'column';
// wrapper.style.gap = '5px';
// wrapper.style.marginBottom = '1rem';

// const input = document.createElement('input');
// input.type = 'file';
// input.name = 'gambar[]';
// input.accept = 'image/*';
// input.required = true;
// input.onchange = function () {
//     const preview = wrapper.querySelector('img');
//     const file = this.files[0];
//     if (file) {
//     preview.src = URL.createObjectURL(file);
//     preview.style.display = 'block';
//     }
// };

// const preview = document.createElement('img');
// preview.style.maxWidth = '300px';
// preview.style.height = 'auto';
// preview.style.marginTop = '5px';
// preview.style.display = 'none';
// preview.style.border = '1px solid #ccc';
// preview.style.borderRadius = '5px';

// const btnHapus = document.createElement('button');
// btnHapus.type = 'button';
// btnHapus.textContent = 'Hapus';
// btnHapus.className = 'btn btn-danger';
// btnHapus.style.marginTop = '5px';
// btnHapus.onclick = function () {
//     container.removeChild(wrapper);
// };

// wrapper.appendChild(input);
// wrapper.appendChild(preview);
// wrapper.appendChild(btnHapus);

// container.appendChild(wrapper);
// }
</script>

<script>//Form Edit
document.addEventListener('DOMContentLoaded', function () {
  // utility function untuk escape HTML
    function escapeHtml(str='') {
        return String(str)
            .replaceAll('&','&amp;')
            .replaceAll('<','&lt;')
            .replaceAll('>','&gt;')
            .replaceAll('"','&quot;')
            .replaceAll("'",'&#039;');
    }

  // Konversi tanggal ke format Romawi (MM/YYYY)
    function formatTanggalRomawi(tanggalStr) {
        const bulanRomawi = ['', 'I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII'];
        const d = new Date(tanggalStr);
        if (isNaN(d)) return tanggalStr;
        return bulanRomawi[d.getMonth() + 1] + '/' + d.getFullYear();
    }

    // Ambil elemen popup
    const box = document.getElementById('popupBoxEdit');
    const bg = document.getElementById('popupBG');
    const closeBtn = document.getElementById('tombolClosePopupEdit');
    const body = document.getElementById('popupEditBody');
    const titleEl = document.getElementById('popupEditTitle') || (box ? box.querySelector('h4') : null);

    if (!box || !bg || !body) {
        console.error('Popup elements not found: pastikan #popupBoxEdit, #popupBG, #popupEditBody ada di DOM.');
        return;
    }
    // Untuk Judul Popup
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.tombolPopupEdit');
        if (!btn) return;
        e.preventDefault();

        const id = btn.getAttribute('data-id');
        if (!id) {
        body.innerHTML = `<div class="alert alert-danger">ID tidak ditemukan.</div>`;
        if (titleEl) titleEl.textContent = 'Edit Berita Acara';
        openPopup();
        return;
        }

        // fetch data JSON (harus mengembalikan { data, gambar, atasan, karyawan })
        fetch('get_edit_ba_kerusakan.php?id=' + encodeURIComponent(id), { cache: 'no-store' })
        .then(resp => {
            if (!resp.ok) throw new Error('HTTP ' + resp.status);
            return resp.json();
        })
        .then(res => {
            if (res.error) throw new Error(res.error);
            // render form langsung (tanpa loading)
            renderEditForm(res.data, res.gambar || [], res.atasan || [], res.karyawan || []);
            if (titleEl) 
                titleEl.textContent = 'Edit Berita Acara ' + escapeHtml(res.data.nomor_ba) +
                            ' Periode ' + escapeHtml(formatTanggalRomawi(res.data.tanggal));
            openPopup();
        })
        .catch(err => {
            console.error('Gagal load data edit:', err);
            body.innerHTML = `<div class="alert alert-danger">Gagal memuat data: ${escapeHtml(err.message)}</div>`;
            if (titleEl) titleEl.textContent = 'Edit Berita Acara';
            openPopup();
        });
    });

    function openPopup() {
        box.classList.add('aktifPopup');
        bg.classList.add('aktifPopup');
    }
    function closePopup(e) {
        if (e) e.preventDefault();
        body.querySelectorAll('img').forEach(img => {
        if (img.src && img.src.startsWith('blob:')) URL.revokeObjectURL(img.src);
        });
        body.innerHTML = '';
        if (titleEl) titleEl.textContent = 'Edit Berita Acara';
        box.classList.remove('aktifPopup');
        bg.classList.remove('aktifPopup');
    }

    closeBtn.addEventListener('click', closePopup);
    bg.addEventListener('click', closePopup);
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closePopup();
    });

    // ====== Render form (meng-generate HTML form di popupEditBody) ======
    function renderEditForm(data, gambar, atasan, karyawan) {
        const currentYear = new Date().getFullYear();

        // buat HTML gambar lama
        let gambarHTML = '';
        gambar.forEach(row => {
        gambarHTML += `
            <div class="gambar-wrapper" style="position:relative; display:flex; flex-direction:column; gap:5px; margin-bottom:1rem;">
            <input type="hidden" name="gambar_lama_id[]" value="${escapeHtml(row.id)}">
            <input type="file" name="gambar_lama_file[${escapeHtml(row.id)}]" accept="image/*" onchange="previewGantiGambar(this)" style="margin-bottom:5px;">
            <img src="${escapeHtml(row.file_path)}" style="max-width:300px; height:auto; border:1px solid #ccc; border-radius:5px;">
            <button type="button" class="btn btn-danger btn-sm" onclick="hapusGambarLama(this, ${escapeHtml(row.id)})">Hapus</button>
            <input type="hidden" name="hapus_gambar[]" value="" class="hapus-gambar-${escapeHtml(row.id)}">
            </div>`;
        });

        body.innerHTML = `
        <form class="popupEdit d-flex flex-column" method="post" action="proses_edit.php" onsubmit="return confirm('Simpan perubahan?')" enctype="multipart/form-data">
            <input type="hidden" name="id" value="${escapeHtml(data.id)}">
            <div class="form-section">
            <div class="row position-relative">
                <div class="col-8">
                <h3>Data Berita Acara</h3>

                <div class="row">
                    <div class="col-3">
                    <div class="input-group" style="width:220px;">
                        <span class="input-group-text">Tanggal</span>
                        <input class="form-control" type="date" name="tanggal" max="${new Date().toISOString().slice(0,10)}" value="${escapeHtml(data.tanggal||'')}" required>
                    </div>
                    </div>
                    <div class="col-4">
                    <div class="input-group" style="width:180px;">
                        <span class="input-group-text">Nomor BA</span>
                        <input type="number" class="form-control" name="nomor_ba" value="${escapeHtml(data.nomor_ba||'')}" required>
                    </div>
                    </div>
                </div>

                <div class="row mt-3 border border-1 p-1 rounded-2 me-1">
                    <div class="row"><h5>Data barang</h5></div>
                    <div class="col-6">
                    <div class="input-group">
                        <span class="input-group-text" style="padding-right:19px;">Jenis perangkat</span>
                        <input type="text" class="form-control" name="jenis_perangkat" value="${escapeHtml(data.jenis_perangkat||'')}" required>
                    </div>
                    </div>
                    <div class="input-group w-50">
                    <span class="input-group-text" style="padding-right:22px;">Merek</span>
                    <textarea rows="1" class="form-control" name="merek" required>${escapeHtml(data.merek||'')}</textarea>
                    </div>
                    <div class="col-6 mt-3">
                    <div class="input-group">
                        <span class="input-group-text">Tahun Perolehan</span>
                        <input class="form-control" type="number" name="tahun_perolehan" min="2007" max="${currentYear}" step="1" value="${escapeHtml(data.tahun_perolehan||'')}" required>
                    </div>
                    </div>
                    <div class="col-6 mt-3">
                    <div class="input-group">
                        <span class="input-group-text" style="padding-right:45px;">SN</span>
                        <input type="text" class="form-control" name="sn" value="${escapeHtml(data.sn||'')}" required>
                    </div>
                    </div>
                </div>

                <div class="row mt-3 border border-1 p-1 rounded-2 me-1">
                    <div class="row"><h5>Data Pengguna</h5></div>
                    <div class="row">
                    <div class="col-4">
                        <div class="input-group">
                        <span class="input-group-text">Lokasi</span>
                        <select name="pt" id="edit-pt" class="form-select" required>
                            <option value="">-- Pilih Lokasi --</option>
                            <option value="PT.MSAL (HO)" ${data.pt === 'PT.MSAL (HO)' ? 'selected' : ''}>PT.MSAL (HO)</option>
                        </select>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="input-group">
                        <span class="input-group-text">Lantai</span>
                        <select name="lokasi" id="edit-lokasi" class="form-select" ${data.pt !== 'PT.MSAL (HO)' ? 'disabled' : ''} required>
                            <option value="">-- Pilih Lantai --</option>
                        </select>
                        </div>
                    </div>
                    </div>

                    <div class="row mt-3 pe-0">
                    <div class="col-6">
                        <div class="input-group">
                        <span class="input-group-text">Pengguna</span>
                        <select name="user" id="edit-user" class="form-select" required>
                            <option value="">-- Pilih Pengguna --</option>
                        </select>
                        </div>
                    </div>
                    <div class="col-6 pe-0">
                        <div class="input-group">
                        <span class="input-group-text">Atasan Peminjam</span>
                        <select name="atasan_peminjam" id="edit-atasan" class="form-select">
                            <option value="">-- Pilih Atasan Peminjam --</option>
                        </select>
                        </div>
                    </div>
                    </div>
                </div>

                <div class="row mt-3 border border-1 p-1 rounded-2 me-1">
                    <div class="row"><h5>Laporan Kerusakan</h5></div>
                    <div class="row pe-0">
                    <div class="col-6">
                        <div class="input-group">
                        <span class="input-group-text">Jenis Kerusakan</span>
                        <textarea name="deskripsi" class="form-control" style="font-size:small;" rows="3" required>${escapeHtml(data.deskripsi||'')}</textarea>
                        </div>
                    </div>
                    <div class="col-6 pe-0">
                        <div class="input-group">
                        <span class="input-group-text">Penyebab Kerusakan</span>
                        <textarea name="penyebab_kerusakan" class="form-control" style="font-size:small;" rows="3" required>${escapeHtml(data.penyebab_kerusakan||'')}</textarea>
                        </div>
                    </div>
                    </div>
                    <div class="row mt-3 pe-0">
                    <div class="col-12 pe-0">
                        <div class="input-group">
                        <span class="input-group-text">Rekomendasi MIS</span>
                        <textarea name="rekomendasi_mis" class="form-control" style="font-size:small;" rows="2" required>${escapeHtml(data.rekomendasi_mis||'')}</textarea>
                        </div>
                    </div>
                    </div>
                </div>
                </div>

                <div class="col-4">
                <h3>Gambar Kerusakan</h3>
                <div class="border border-2 rounded-3 p-1" style="height:485px; overflow-y:auto;">
                    <div class="d-flex flex-column">
                    <div id="edit-gambar-container" class="d-flex flex-column gap-2">
                        ${gambarHTML}
                    </div>
                    <button type="button" class="btn btn-primary w-50 align-self-center mb-1" onclick="tambahGambarEdit()">+ Tambah Gambar Laporan</button>
                    </div>
                </div>
                </div>

            </div>
            </div>

            <input class="w-25 align-self-end btn btn-success mt-3" type="submit" value="Simpan">
        </form>
        `;

        // setelah render, wire select controls
        try {
        wireEditFormSelects(data, karyawan, atasan);
        } catch (ex) {
        console.warn('wireEditFormSelects error:', ex);
        }
    }

    // ====== WIRING SELECT (lantai->user->atasan) ======
    function wireEditFormSelects(data, karyawan, atasan) {
        const ptSelect     = document.getElementById('edit-pt');
        const lantaiSelect = document.getElementById('edit-lokasi');
        const userSelect   = document.getElementById('edit-user');
        const atasanSelect = document.getElementById('edit-atasan');

        if (!ptSelect || !lantaiSelect || !userSelect || !atasanSelect) {
        console.warn('Some edit selects not found');
        return;
        }

        // unique lantai
        const uniqueLantai = [...new Set(karyawan.map(k => k.lantai).filter(Boolean))].sort((a,b)=>{
        const ma = /^LT\.(\d+)/i.exec(a), mb = /^LT\.(\d+)/i.exec(b);
        if (ma && mb) return parseInt(ma[1]) - parseInt(mb[1]);
        return String(a).localeCompare(String(b));
        });

        lantaiSelect.innerHTML = '<option value="">-- Pilih Lantai --</option>' + uniqueLantai.map(v=>{
        const m = /^LT\.(\d+)/i.exec(v);
        const label = m ? ('Lantai ' + m[1]) : v;
        const sel = (data.lokasi === v) ? ' selected' : '';
        return `<option value="${escapeHtml(v)}"${sel}>${escapeHtml(label)}</option>`;
        }).join('');

        function loadUsersByLantai(lantai, selectedUser) {
        userSelect.innerHTML = '<option value="">-- Pilih Pengguna --</option>';
        karyawan.filter(k => k.lantai === lantai).forEach(k => {
            const label = `${k.nama} - ${k.posisi} (${k.departemen})`;
            const sel = (selectedUser ? (k.nama === selectedUser) : (k.nama === data.user)) ? ' selected' : '';
            userSelect.insertAdjacentHTML('beforeend', `<option value="${escapeHtml(k.nama)}"${sel}>${escapeHtml(label)}</option>`);
        });
        }

        function loadAtasanByDept(dept, selected) {
        atasanSelect.innerHTML = '<option value="">-- Pilih Atasan Peminjam --</option>';
        const filtered = atasan.filter(a => a.departemen === dept);
        filtered.forEach(a => {
            const label = `${a.nama} - ${a.posisi} (${a.departemen})`;
            const sel = (selected ? (a.nama === selected) : (a.nama === data.atasan_peminjam)) ? ' selected' : '';
            atasanSelect.insertAdjacentHTML('beforeend', `<option value="${escapeHtml(a.nama)}"${sel}>${escapeHtml(label)}</option>`);
        });
        atasanSelect.disabled = filtered.length === 0;
        }

        // initial populate
        if (ptSelect.value === 'PT.MSAL (HO)') {
        lantaiSelect.disabled = false;
        if (data.lokasi) loadUsersByLantai(data.lokasi, data.user);
        const userData = karyawan.find(k => k.nama === data.user);
        if (userData) loadAtasanByDept(userData.departemen, data.atasan_peminjam);
        } else {
        lantaiSelect.disabled = true;
        }

        ptSelect.addEventListener('change', function() {
        if (this.value === 'PT.MSAL (HO)') {
            lantaiSelect.disabled = false;
        } else {
            lantaiSelect.disabled = true; lantaiSelect.value = '';
            userSelect.innerHTML = '<option value="">-- Pilih Pengguna --</option>';
            atasanSelect.innerHTML = '<option value="">-- Pilih Atasan Peminjam --</option>';
            atasanSelect.disabled = true;
        }
        });

        lantaiSelect.addEventListener('change', function() {
        loadUsersByLantai(this.value, null);
        atasanSelect.innerHTML = '<option value="">-- Pilih Atasan Peminjam --</option>';
        atasanSelect.disabled = true;
        });

        userSelect.addEventListener('change', function() {
        const userData = karyawan.find(k => k.nama === this.value);
        if (userData) loadAtasanByDept(userData.departemen, null);
        });
    }

    // ====== Gambar helpers ======
    window.tambahGambarEdit = function () {
        tambahGambarKe('edit-gambar-container', 'gambar_baru[]');
    };

    function tambahGambarKe(containerId, inputName) {
        const container = document.getElementById(containerId);
        if (!container) return;

        const wrapper = document.createElement('div');
        wrapper.className = 'gambar-wrapper';
        wrapper.style.position = 'relative';
        wrapper.style.display = 'flex';
        wrapper.style.flexDirection = 'column';
        wrapper.style.gap = '5px';
        wrapper.style.marginBottom = '1rem';

        const input = document.createElement('input');
        input.type = 'file';
        input.name = inputName;
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
        preview.style.maxWidth = '300px';
        preview.style.height = 'auto';
        preview.style.marginTop = '5px';
        preview.style.display = 'none';
        preview.style.border = '1px solid #ccc';
        preview.style.borderRadius = '5px';

        const btnHapus = document.createElement('button');
        btnHapus.type = 'button';
        btnHapus.textContent = 'Hapus';
        btnHapus.className = 'btn btn-danger btn-sm';
        btnHapus.style.marginTop = '5px';
        btnHapus.onclick = function () {
        container.removeChild(wrapper);
        if (preview.src && preview.src.startsWith('blob:')) URL.revokeObjectURL(preview.src);
        };

        wrapper.appendChild(input);
        wrapper.appendChild(preview);
        wrapper.appendChild(btnHapus);
        container.appendChild(wrapper);
    }

    window.previewGantiGambar = function (input) {
        const preview = input.nextElementSibling;
        const file = input.files && input.files[0];
        if (file && preview && preview.tagName.toLowerCase() === 'img') {
        if (preview.src && preview.src.startsWith('blob:')) URL.revokeObjectURL(preview.src);
        preview.src = URL.createObjectURL(file);
        preview.style.display = 'block';
        }
    };

    window.hapusGambarLama = function (button, id) {
        const wrapper = button.closest('.gambar-wrapper');
        if (wrapper) wrapper.style.display = 'none';
        const hiddenInput = document.querySelector(`.hapus-gambar-${id}`);
        if (hiddenInput) hiddenInput.value = 'hapus';
    };
    });
</script>

<script>//Detail Popup
// Sistem tombol popup detail
document.addEventListener('DOMContentLoaded', function () {
    const btnDetailList = document.querySelectorAll('.btn-detail-ba');
    const popupBox = document.getElementById('popupBoxDetail');
    const popupBody = document.getElementById('popupDetailBody');
    const closeBtn = document.getElementById('tombolClosePopupDetail');
    const popupBG = document.getElementById('popupBG');

    if (!popupBox || !popupBody || !closeBtn || !popupBG) return console.error('Popup elements missing');

    function escapeHtml(str='') {
        return String(str)
            .replaceAll('&','&amp;')
            .replaceAll('<','&lt;')
            .replaceAll('>','&gt;')
            .replaceAll('"','&quot;')
            .replaceAll("'",'&#039;');
    }

    function formatRomawi(tanggal) {
        const bulanRomawi = ['I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII'];
        const d = new Date(tanggal);
        const bulan = d.getMonth(); // 0-11
        const tahun = d.getFullYear();
        return `${bulanRomawi[bulan]}/${tahun}`;
    }

    function openPopup() {
        popupBox.classList.add('aktifPopup');
        popupBG.classList.add('aktifPopup');
    }

    function closePopup() {
        popupBody.innerHTML = '';
        popupBox.classList.remove('aktifPopup');
        popupBG.classList.remove('aktifPopup');
    }

    closeBtn.addEventListener('click', function(e){
        e.preventDefault();
        closePopup();
    });

    popupBG.addEventListener('click', closePopup);

    document.addEventListener('keydown', function(e){
        if(e.key === 'Escape') closePopup();
    });

    btnDetailList.forEach(btn => {
        btn.addEventListener('click', function(e){
            e.preventDefault();
            const id = this.dataset.id;
            if(!id) return alert('ID tidak ditemukan');

            fetch('get_detail.php?id=' + encodeURIComponent(id), { cache: 'no-store' })
            .then(resp => {
                if(!resp.ok) throw new Error('HTTP ' + resp.status);
                return resp.json(); // JSON: { data, peran, gambarList }
            })
            .then(res => {
                if(res.error) throw new Error(res.error);

                const data = res.data;
                const peran = res.peran;
                const gambarList = res.gambarList || [];

                // build HTML tabel
                let html = `<h2>Detail Data Kerusakan ${escapeHtml(data.nomor_ba)} Periode ${formatRomawi(data.tanggal)}</h2>`;

                html += `
                <table class="table w-25 table-approval">
                    <thead>
                        <tr>
                            <th>Pembuat</th>
                            <th>Penyetujui</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>${escapeHtml(peran.jabatan_aprv1)} ${escapeHtml(peran.departemen_aprv1)}</td>
                            <td>${escapeHtml(peran.jabatan_aprv2)} ${escapeHtml(peran.departemen_aprv2)}</td>
                        </tr>
                        <tr>
                            <td><span class="border fw-bold ${data.approval_1==1?'bg-success-subtle border-success-subtle text-success':'bg-warning-subtle border-warning-subtle text-warning'}" style="border-radius:6px;padding:6px 12px;">${data.approval_1==1?'Disetujui':'Menunggu'}</span></td>
                            <td><span class="border fw-bold ${data.approval_2==1?'bg-success-subtle border-success-subtle text-success':'bg-warning-subtle border-warning-subtle text-warning'}" style="border-radius:6px;padding:6px 12px;">${data.approval_2==1?'Disetujui':'Menunggu'}</span></td>
                        </tr>
                    </tbody>
                </table>`;

                html += `<div class="d-flex gap-2 h-100">
                <table class="table table-bordered table-striped" style="width:50%;">
                    <tbody>
                        <tr><th style="width:20%;min-width:150px;">Nomor BA</th><td style="width:80%;">${escapeHtml(data.nomor_ba)}</td></tr>
                        <tr><th style="width:20%;min-width:150px;">Tanggal</th><td style="width:80%;">${escapeHtml(data.tanggal)}</td></tr>
                        <tr><th style="width:20%;min-width:150px;">Jenis Perangkat</th><td style="width:80%;">${escapeHtml(data.jenis_perangkat)}</td></tr>
                        <tr><th style="width:20%;min-width:150px;">Merek</th><td style="width:80%;">${escapeHtml(data.merek)}</td></tr>
                        <tr><th style="width:20%;min-width:150px;">Serial Number</th><td style="width:80%;">${escapeHtml(data.sn)}</td></tr>
                        <tr><th style="width:20%;min-width:150px;">Tahun Perolehan</th><td style="width:80%;">${escapeHtml(data.tahun_perolehan)}</td></tr>
                        <tr><th style="width:20%;min-width:150px;">Lokasi</th><td style="width:80%;">${escapeHtml(data.pt)} ${escapeHtml(data.lokasi)}</td></tr>
                        <tr><th style="width:20%;min-width:150px;">Pengguna</th><td style="width:80%;">${escapeHtml(data.user)}</td></tr>
                        <tr><th style="width:20%;min-width:150px;">Jenis Kerusakan</th><td style="width:80%;">${escapeHtml(data.deskripsi)}</td></tr>
                        <tr><th style="width:20%;min-width:150px;">Penyebab Kerusakan</th><td style="width:80%;">${escapeHtml(data.penyebab_kerusakan)}</td></tr>
                        <tr><th style="width:20%;min-width:150px;">Rekomendasi MIS</th><td style="width:80%;">${escapeHtml(data.rekomendasi_mis)}</td></tr>
                        <tr><th style="width:20%;min-width:150px;">Atasan Peminjam</th><td style="width:80%;">${escapeHtml(data.atasan_peminjam)}</td></tr>
                        </tbody></table>
                        <div class="w-50 d-flex border rounded-1 mb-1 overflow-auto p-2" style="height:525px;">`;
                        if(gambarList.length>0){
                            html += `<div style="display:flex;flex-wrap:wrap;gap:5px;height:max-content;width:100%;">`;
                            gambarList.forEach(g=>{
                                html += `<div style="width:49%"><img src="${escapeHtml(g)}" style="max-width:100%;height:auto;display:block;"></div>`;
                            });
                            html += `</div>`;
                        } else {
                            html += `Tidak ada gambar.`;
                        }
                html += `</div>
                        </div>`;

                popupBody.innerHTML = html;
                openPopup();
            })
            .catch(err=>{
                popupBody.innerHTML = `<div class="alert alert-danger">Gagal memuat data: ${escapeHtml(err.message)}</div>`;
                openPopup();
            });
        });
    });
});
</script>

<script>//DataTables
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
<script>//DataTables
    $(document).ready(function () {
        $('#myTable2').DataTable({
        responsive: true,
        autoWidth: true,
        language: {
            url: "../assets/json/id.json"
        },
        scrollY: "410px",     
        scrollCollapse: true, 
        paging: true,
        columnDefs: [
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
