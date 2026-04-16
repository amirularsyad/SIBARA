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

// Ambil PT user dari session
    $pt_user = isset($_SESSION['pt']) ? $_SESSION['pt'] : '';

    // Daftar PT
    $daftar_pt = array(
        "PT.MSAL (HO)"
        //,
        //"PT.MSAL (SITE)",
        //"PT.WCJU",
        //"PT.MAPA",
        //"PT.PSAM",
        //"PT.PEAK",
        //"PT.KPP"
    );

?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>BA Mutasi</title>

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

    /* .personalia-menu{
        background:linear-gradient(135deg,#515bd4,#dd2a7b,#F58529);
        transition: all .3s ease;
    } */

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

    .aktifLT{
        display: flex;
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

    /* style table */

    .table-wrapper{
        width: 97%;
        height: auto;
        overflow-x: auto;
        margin: 20px 0;
        border-radius: 10px;
        padding: 10px;
    }

    #tabelUtama{
        display: none;
    }

    th,td,table tbody tr td .btn-sm{
        font-size: .7rem;
    }

    th, td{
        text-align: center !important;
    }

    #thead th:nth-child(1), #tbody td:nth-child(1) { width: 4%; text-align: center; } 
    #thead th:nth-child(2), #tbody td:nth-child(2) { width: 6%; }  
    #thead th:nth-child(3), #tbody td:nth-child(3) { width: 6%; }  
    #thead th:nth-child(4), #tbody td:nth-child(4) { width: 10%; }  
    #thead th:nth-child(5), #tbody td:nth-child(5) { width: 220px; }  
    #thead th:nth-child(6), #tbody td:nth-child(6) { width: 220px; }  
    #thead th:nth-child(7), #tbody td:nth-child(7) { width: 200px; } 
    #thead th:nth-child(8), #tbody td:nth-child(8) { width: 350px; }  
    #thead th:nth-child(11), #tbody td:nth-child(11) { width: 50px; height:100% !important; text-align: center; }

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

    .aktifPopup, .aktifPopupDetail, .aktifPopupInput, .aktifPopupEdit, .aktifPopup2{
        display:flex;
    }

    .table-approval th,.table-approval td{
        border: none;
        padding: 5px;
    }

    .dataTable {
        width: 100% !important;
    }

    .bi-list, .bi-arrows-fullscreen, .bi-fullscreen-exit {
            color: #fff !important;
    }

    /* style tabel detail BA*/
    #popupDetailBody table td,
    #popupDetailBody table th {
        text-align: left !important;
        vertical-align: top;
    }

    .div-gambar-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr); /* 2 gambar per baris */
        gap: 10px;
        width: 100%;
    }

    .div-gambar-grid img {
        width: 100%;
        object-fit: cover; /* biar tidak gepeng */
        box-shadow: 0 0 6px rgba(0,0,0,0.1);
        transition: transform 0.2s ease;
    }

/*    .div-gambar-grid img:hover {
        transform: scale(1.03);
    }*/
    .custom-footer{
        background-color: white;
    }
</style>

<style>
    /* Responsive */
    @media (max-width: 1670px) {
        .btn{
        margin-bottom: 5px;
    }
    }
    @media (max-width: 1024px) {
        #res-fullscreen{
            display: none;
        }
        .custom-main{
            padding-bottom: 100px;
            height: max-content;
            padding-top: 10px;
        }
        .custom-btn-action{
            padding: 6px 12px !important;
            font-size: 1rem !important;
        }
        .custom-footer{
            position:absolute !important;
            bottom: 0;
            width: 100vw;
        }
    }
    @media (max-width: 450px){
        #date, #clock{
            display: none;
        }
        .custom-btn-input-history{
            flex-direction: column !important;
            right: 10px !important;
            left: auto !important;
        }
        #tombolInputPopup i, #tombolHistorikal i{
            font-size: 25px !important;
        }
                #myTable_wrapper{
            width: 100%;
        }
        #myTable_wrapper .row:nth-child(2){
            width: 100%;
            overflow-x: auto;
            max-height: 250px;
        }
        .custom-footer p{
            font-size: 10px;
        }
    }
</style>

<style>
    /* Placeholder Skeleton */
    .skeleton {
    height: 16px;
    width: 100%;
    background: linear-gradient(
        90deg,
        #e0e0e0 25%,
        #f5f5f5 37%,
        #e0e0e0 63%
    );
    background-size: 400% 100%;
    animation: skeleton-loading 1.4s ease infinite;
    border-radius: 4px;
}

.skeleton-header {
    height: 20px;
}

@keyframes skeleton-loading {
    0% { background-position: 100% 0; }
    100% { background-position: -100% 0; }
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

<style>
/* Batasi tinggi dropdown hasil pencarian */
.dropdown-menu.custom-dropdown-scroll {
  max-height: 200px;
  overflow-y: auto;
  overflow-x: hidden;
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
                    <a href="../logout.php" id="logoutTombol" class="btn btn-outline-danger fw-bold ps-3 gap-2 mt-2 d-flex" onclick="return confirm('Yakin ingin logout?')" title="Logout">
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
                <a href="../ba_kerusakan-fix/ba_kerusakan.php" class="nav-link" aria-disabled="true">
                <i class="nav-icon bi bi-newspaper"></i>
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
            <li class="nav-item">
                <a href="#" class="nav-link">
                <i class="nav-icon bi bi-newspaper text-white"></i>
                <p class="text-white">
                    BA Mutasi
                </p>
                </a>
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
        $where_clauses[] = "pt_asal = ?";
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
    $query = "SELECT * FROM berita_acara_mutasi $where_sql ORDER BY tanggal DESC, nomor_ba DESC";

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
    
    $tanggal_hari_ini = date('Y-m-d');
    $bulan_ini = date('m');
    $tahun_ini = date('Y');

    // Ambil nomor_ba tertinggi di bulan & tahun yang sama
    $stmt2 = $koneksi->prepare("
    SELECT nomor_ba 
    FROM berita_acara_mutasi 
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
    // var_dump($nomor_ba_baru);
    ?>

    <?php 

    //Critical data barang filtering!
    $where_barang = '';
    $id_pt_list = [];
    $map_pt = [
        'PT.MSAL (HO)'          => 1,
        'PT.MSAL (SITE)'        => 3,

        // tambahkan sesuai tabel tb_pt
    ];
    foreach ($pt_user as $pt) {
        if (isset($map_pt[$pt])) {
            $id_pt_list[] = (int)$map_pt[$pt];
        }
    }

    if (!empty($id_pt_list)) {
        $where_barang = " WHERE a.id_pt IN (" . implode(',', $id_pt_list) . ") ";
    }
    // var_dump($where_barang);


    $query_assets = "
    SELECT 
        a.id_assets,
        a.serial_number,
        q.category AS jenis_perangkat,
        a.merk,
        a.user,
        a.no_po,
        a.kode_assets,
        i.nama_pt AS pt
    FROM tb_assets AS a
    LEFT JOIN tb_qty_assets AS q ON a.qty_id = q.id_qty
    LEFT JOIN tb_pt AS i ON a.id_pt = i.id_pt
    $where_barang
    ORDER BY q.category ASC
    ";

    $result_assets = $koneksi2->query($query_assets);
    if (!$result_assets) {
    die('Query assets gagal: ' . $koneksi->error);
    }
    $result_assets2 = $koneksi2->query($query_assets);
    if (!$result_assets2) {
    die('Query assets gagal: ' . $koneksi->error);
    }
    ?>

    <main class="custom-main app-main"><!-- Main Content -->

        <!--Status Sukses Pop Up-->
        <?php if (isset($_SESSION['message'])): ?>
            <?php if (isset($_GET['status']) && $_GET['status'] == 'sukses'): ?>
                <div class="w-100 d-flex justify-content-center position-absolute" style="height: max-content;">
                    <div class="d-flex p-0 alert alert-success border-0 text-center fw-bold mb-0 position-absolute fade-in infoin-approval" style="z-index:8;transition: opacity 0.5s ease;right:20px;width:max-content;height:max-content;">
                        <div class="d-flex justify-content-center align-items-center bg-success pe-2 ps-2 rounded-start text-white fw-bolder">
                            <i class="bi bi-check-lg"></i>
                        </div>
                        <p class="p-2 m-0" style="font-weight: 500;"><?= htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); ?></p>
                    </div>
                </div>
            <?php elseif (isset($_GET['status']) && $_GET['status'] == 'gagal'): ?>
                <div class="w-100 d-flex justify-content-center position-absolute" style="height: max-content;">
                    <div class="d-flex p-0 alert alert-danger border-0 text-center fw-bold mb-0 position-absolute fade-in infoin-approval" style="z-index:8;transition: opacity 0.5s ease;right:20px;width:max-content;height:max-content;">
                        <div class="d-flex justify-content-center align-items-center bg-danger pe-2 ps-2 rounded-start text-white fw-bolder">
                            <i class="bi bi-x-lg"></i>
                        </div>
                        <p class="p-2 m-0" style="font-weight: 500;"><?= htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); ?></p>
                    </div>
                </div>
            <?php endif; ?>
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
        <h2>Daftar Berita Acara Mutasi Aset Internal</h2>

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
                        <th><div class="skeleton skeleton-header"></div></th>
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
        <div id="tabelUtama" style="display: none;">
            <table id="myTable" class="table table-bordered table-striped text-center">
                <div class="custom-btn-input-history position-absolute d-flex gap-2" style="top:127px;left:220px;z-index:1;width:max-content;height:max-content;">
                    <a href="#" id="tombolInputPopup" class="btn btn-success"><i class="bi bi-plus-lg"></i></a>
                    <a href="../master/histori_edit.php" id="tombolHistorikal" class="btn btn-warning"><i class="bi bi-clock-history"></i></a>
                </div>
                
                <!-- <a href="#" id="tombolInputPopup2" class="btn btn-success position-absolute" style="width:max-content;height:max-content;top:127px;left:270px;z-index:1;"><i class="bi bi-plus-lg"></i></a> -->
                <thead class="bg-secondary" id="thead-utama">
                    <tr>
                        <th>No</th>
                        <th>Tanggal</th>
                        <th>Nomor BA</th>
                        <th>Lokasi Asal</th>
                        <th>Lokasi Tujuan</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody id="tbody-utama">
                    <?php 
                    // Loop melalui hasil query dan tampilkan data dalam tabel
                    $no = 1;
                    while ($row = $result->fetch_assoc()):
                    ?>
                    <tr>
                        <td><?= $no++; ?></td>
                        <td><?= htmlspecialchars($row['tanggal']); ?></td>
                        <td><?= htmlspecialchars($row['nomor_ba']); ?></td>
                        <td><?= htmlspecialchars($row['pt_asal']); ?></td>
                        <td><?= htmlspecialchars($row['pt_tujuan']); ?></td>
                        <td>
                            <a class="custom-btn-action btn btn-secondary btn-sm tombolDetailPopup" href="#" 
                                data-id="<?= $row['id']; ?>"
                                data-tanggal="<?= htmlspecialchars($row['tanggal']); ?>"
                                data-nomor_ba="<?= htmlspecialchars($row['nomor_ba']); ?>"
                                data-lokasi_asal="<?= htmlspecialchars($row['pt_asal']); ?>"
                                data-lokasi_tujuan="<?= htmlspecialchars($row['pt_tujuan']); ?>"
                                data-nama_pengirim="<?= htmlspecialchars($row['pengirim1']); ?>"
                                data-nama_pengirim2="<?= htmlspecialchars($row['pengirim2']); ?>"
                                data-nama_hrd_pengirim="<?= htmlspecialchars($row['hrd_ga_pengirim']); ?>"
                                data-nama_penerima="<?= htmlspecialchars($row['penerima1']); ?>"
                                data-nama_penerima2="<?= htmlspecialchars($row['penerima2']); ?>"
                                data-nama_hrd_penerima="<?= htmlspecialchars($row['hrd_ga_penerima']); ?>"
                                data-nama_diketahui="<?= htmlspecialchars($row['diketahui']); ?>"
                                data-nama_pemeriksa1="<?= htmlspecialchars($row['pemeriksa1']); ?>"
                                data-nama_pemeriksa2="<?= htmlspecialchars($row['pemeriksa2']); ?>"
                                data-nama_penyetujui1="<?= htmlspecialchars($row['penyetujui1']); ?>"
                                data-nama_penyetujui2="<?= htmlspecialchars($row['penyetujui2']); ?>"
                                data-approval1="<?= htmlspecialchars($row['approval_1']); ?>"
                                data-approval2="<?= htmlspecialchars($row['approval_2']); ?>"
                                data-approval3="<?= htmlspecialchars($row['approval_3']); ?>"
                                data-approval4="<?= htmlspecialchars($row['approval_4']); ?>"
                                data-approval5="<?= htmlspecialchars($row['approval_5']); ?>"
                                data-approval6="<?= htmlspecialchars($row['approval_6']); ?>"
                                data-approval7="<?= htmlspecialchars($row['approval_7']); ?>"
                                data-approval8="<?= htmlspecialchars($row['approval_8']); ?>"
                                data-approval9="<?= htmlspecialchars($row['approval_9']); ?>"
                                data-approval10="<?= htmlspecialchars($row['approval_10']); ?>"
                                data-approval11="<?= htmlspecialchars($row['approval_11']); ?>"
                                data-keterangan="<?= htmlspecialchars($row['keterangan']); ?>"
                                data-pembuat="<?= htmlspecialchars($row['pembuat']); ?>"
                                <?php 
                                $for_dept = $row['id'];
                                $query_departemen = "
                                    SELECT dk.departemen 
                                    FROM berita_acara_mutasi bam
                                    JOIN data_karyawan dk ON dk.nama = bam.diketahui
                                    WHERE bam.id = $for_dept
                                ";

                                $result_departemen = mysqli_query($koneksi, $query_departemen);

                                if ($result_departemen && mysqli_num_rows($result_departemen) > 0) {
                                    $row_dep = mysqli_fetch_assoc($result_departemen);
                                    $departemen_diketahui = $row_dep['departemen'];
                                } else {
                                    $departemen_diketahui = '-'; // fallback jika tidak ditemukan
                                }
                                ?>
                                data-departemen_diketahui = <?= htmlspecialchars($departemen_diketahui); ?>
                            >
                                <i class="bi bi-eye-fill"></i>
                            </a>
                            <a class='custom-btn-action btn btn-primary btn-sm' href='surat_output.php?id=<?= $row['id']; ?>' target='_blank'><i class="bi bi-file-earmark-text-fill"></i></a>
                            
                            <?php if ($_SESSION['nama'] === $row['pembuat']): ?>
                            <?php 
                            // if (!(
                            //     $row['approval_1'] == 1 &&
                            //     $row['approval_2'] == 1 &&
                            //     $row['approval_3'] == 1 &&
                            //     $row['approval_4'] == 1 &&
                            //     $row['approval_5'] == 1 &&
                            //     $row['approval_6'] == 1 &&
                            //     $row['approval_7'] == 1 &&
                            //     $row['approval_8'] == 1 &&
                            //     $row['approval_9'] == 1 &&
                            //     $row['approval_10'] == 1 &&
                            //     $row['approval_11'] == 1 
                            // )): 
                            ?>
                            <a class='custom-btn-action btn btn-warning btn-sm tombolEditPopup' href='#' 
                                data-id="<?= $row['id']; ?>"
                                data-tanggal="<?= htmlspecialchars($row['tanggal']); ?>"
                                data-nomor_ba="<?= htmlspecialchars($row['nomor_ba']); ?>"
                                data-lokasi_asal="<?= htmlspecialchars($row['pt_asal']); ?>"
                                data-lokasi_tujuan="<?= htmlspecialchars($row['pt_tujuan']); ?>"
                                data-nama_pengirim="<?= htmlspecialchars($row['pengirim1']); ?>"
                                data-nama_pengirim2="<?= htmlspecialchars($row['pengirim2']); ?>"
                                data-nama_penerima="<?= htmlspecialchars($row['penerima1']); ?>"
                                data-nama_penerima2="<?= htmlspecialchars($row['penerima2']); ?>"
                                data-keterangan="<?= htmlspecialchars($row['keterangan']); ?>"
                            >
                                <i class="bi bi-feather"></i>
                            </a>
                            <?php 
                            // endif; 
                            ?>

                            <!-- <a href="#" class="btn btn-warning btn-sm tombolHistorikalPopup"
                            data-id="<?= $row['id']; ?>"
                            data-tanggal="<?= htmlspecialchars($row['tanggal']); ?>"
                            data-nomor_ba="<?= htmlspecialchars($row['nomor_ba']); ?>"
                            data-lokasi_asal="<?= htmlspecialchars($row['pt_asal']); ?>"
                            data-lokasi_tujuan="<?= htmlspecialchars($row['pt_tujuan']); ?>"
                            ><i class="bi bi-clock-history"></i></a> -->

                            <!-- <a 
                            class='btn btn-danger btn-sm <?php if ($row['approval_1'] == 1 || $row['approval_2'] == 1 || $row['approval_3'] == 1 || $row['approval_4'] == 1 || $row['approval_5'] == 1): ?>d-none<?php endif; ?>' 
                            href='delete.php?id=<?= $row['id']; ?>' onclick="return confirm('Apakah Anda yakin ingin menghapus data ini?')"><i class="bi bi-x-lg"></i></a> -->
                            
                            <a 
                            class='custom-btn-action btn btn-danger btn-sm' 
                            href='delete.php?id=<?= $row['id']; ?>' onclick="return confirm('Apakah Anda yakin ingin menghapus data ini?')"><i class="bi bi-x-lg"></i></a>
                            
                            <?php endif; ?>
                            
                        </td>
                    </tr>
                    <?php endwhile; ?>

                </tbody>
            </table>
        </div>
        <div id="popupBoxInput" class="popup-box position-absolute bg-white top-0 rounded-1 flex-column justify-content-start align-items-center p-2" style="height: max-content;align-self: center;z-index: 9;width: 95%;">
            
            <div class="w-100 d-flex justify-content-between mb-2" style="height: max-content;">
                <h4 class="m-0 p-0">Input Berita Acara</h4>
                <a id="tombolClosePopupInput" class='btn btn-danger btn-sm' href='#' ><i class="bi bi-x-lg"></i></a>
            </div>
            
            <form class="popupInput d-flex flex-column" method="post" action="proses_simpan.php" enctype="multipart/form-data">
                <div class="form-section">
                    <div class="row position-relative">
                    
                        <div class="custom-input-form col-8">
                            <h3>Data Berita Acara Mutasi</h3>
                            <div class="row">
                            <div class="custom-input-tanggal col-3">
                                <div class="input-group" style="width:220px;">
                                <span class="input-group-text">Tanggal</span>
                                <input class="form-control "  type="date" name="tanggal" id="tanggal" max="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d') ?>" required>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="input-group" style="width:180px;">
                                <span class="input-group-text">Nomor BA</span>
                                <input type="text" class="form-control" name="nomor_ba" id="nomor_ba" value="<?= $nomor_ba_baru ?>" required>
                                </div>
                            </div>
                            </div>

                            <div class="row mt-3">
                                <div class="accordion w-100" id="accordionForm">

                                    <!-- ROW 1: DATA MUTASI -->
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="headingMutasi">
                                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseMutasi" aria-expanded="true" aria-controls="collapseMutasi">
                                        Data Mutasi
                                        </button>
                                        </h2>
                                        <div id="collapseMutasi" class="accordion-collapse collapse show" aria-labelledby="headingMutasi" data-bs-parent="#accordionForm">
                                        <div class="accordion-body">
                                            <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="lokasi_asal" class="form-label">Lokasi Asal</label>
                                                <select class="form-select" id="lokasi_asal" name="lokasi_asal" required>
                                                    <option value="">-- Pilih Lokasi Asal --</option>
                                                    <?php 
                                                    // Kunci PT sesuai user SESSION pt
                                                    foreach ($daftar_pt as $pt) {
                                                        if (is_array($pt_user) && in_array($pt, $pt_user)) {
                                                            // Jika user punya akses ke PT ini
                                                            echo "<option value='$pt'>$pt</option>";
                                                        } elseif ($pt === $pt_user) {
                                                            // Kompatibilitas jika $_SESSION['pt'] masih string (user lama)
                                                            echo "<option value='$pt'>$pt</option>";
                                                        } else {
                                                            // Tidak punya akses
                                                            echo "<option value='$pt' disabled>$pt</option>";
                                                        }
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="lokasi_tujuan" class="form-label">Lokasi Tujuan</label>
                                                <select class="form-select" id="lokasi_tujuan" name="lokasi_tujuan" required>
                                                    <option value="">-- Pilih Lokasi Tujuan --</option>
                                                    <option value="PT.MSAL (HO)">PT.MSAL (HO)</option>
                                                    <option value="PT.MSAL (SITE)">PT. MSAL (SITE)</option>
                                                    <option value="PT.WCJU">PT. WCJU</option>
                                                    <option value="PT.MAPA">PT. MAPA</option>
                                                    <option value="PT.PSAM">PT. PSAM</option>
                                                    <option value="PT.PEAK">PT. PEAK</option>
                                                    <option value="PT.KPP">PT. KPP</option>
                                                </select>
                                            </div>
                                            </div>

                                            <div class="row mt-3">
                                                <!-- Data Pengirim -->
                                                <div class="col-md-6 mb-3 position-relative">
                                                    <label class="form-label">Yang Menyerahkan Aset </label>
                                                    <div class="input-group mb-2">
                                                        <input type="text" class="form-control" id="nama_pengirim" name="nama_pengirim" placeholder="Nama karyawan" required readonly>
                                                        <button type="button" class="btn btn-outline-secondary" id="btnCariPengirim" title="Cari karyawan">
                                                            <i class="bi bi-search"></i>
                                                        </button>
                                                    </div>
                                                    <div id="dropdownPengirim" class="dropdown-menu w-100"></div>

                                                    <div class="mt-3">
                                                        <label class="form-label">Yang Menyerahkan Aset 2</label>
                                                        <div class="input-group mb-2">
                                                            <input type="text" class="form-control" id="nama_pengirim2" name="nama_pengirim2" placeholder="Nama karyawan" required readonly>
                                                            <button type="button" class="btn btn-outline-secondary" id="btnCariPengirim2" title="Cari karyawan">
                                                                <i class="bi bi-search"></i>
                                                            </button>
                                                        </div>
                                                        <div id="dropdownPengirim2" class="dropdown-menu w-100"></div>
                                                    </div>
                                                </div>
                                                <!-- Data Penerima -->
                                                <div class="col-md-6 mb-3 position-relative">
                                                    <label class="form-label">Nama Penerima</label>
                                                    <!-- PENERIMA 1 -->
                                                    <div class="input-group mb-2">
                                                        <input type="text" id="nama_penerima" name="nama_penerima" class="form-control" placeholder="Nama karyawan" required readonly>
                                                        <button type="button" id="btnCariPenerima" class="btn btn-outline-secondary">
                                                            <i class="bi bi-search"></i>
                                                        </button>
                                                    </div>
                                                    <div id="dropdownPenerima" class="position-absolute w-100"></div>

                                                    <!-- PENERIMA 2 (muncul setelah penerima 1 terisi) -->
                                                    <div id="penerima2Wrapper" class="mt-3">
                                                        <label for="nama_penerima2" class="form-label">Nama Penerima 2</label>
                                                        <div class="input-group mb-2">
                                                            <input type="text" id="nama_penerima2" name="nama_penerima2" class="form-control" placeholder="Nama karyawan" required readonly>
                                                            <button type="button" id="btnCariPenerima2" class="btn btn-outline-secondary">
                                                                <i class="bi bi-search"></i>
                                                            </button>
                                                        </div>
                                                        <div id="dropdownPenerima2" class="position-absolute w-100"></div>
                                                    </div>

                                                </div>
                                            </div>
                                        </div>
                                        </div>
                                    </div>

                                    <!-- ROW 2: DATA BARANG -->
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="headingBarang">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseBarang" aria-expanded="false" aria-controls="collapseBarang">
                                        Data Barang
                                        </button>
                                        </h2>
                                        <div id="collapseBarang" class="accordion-collapse collapse" aria-labelledby="headingBarang" data-bs-parent="#accordionForm">
                                        <div class="accordion-body">
                                            <div class="row mb-3">
                                                
                                                <div class="d-flex mb-3">
                                                    <div class="tombolDataBarangPopup btn btn-primary btn-md" data-target="input"><i class="bi bi-search"></i></div>
                                                    <!-- <div class="btn btn-primary rounded-start-0" id="openScanModal"><i class="bi bi-qr-code-scan"></i></div> -->
                                                    <!-- <button type="button" id="openScanModal" class="btn btn-primary rounded-start-0 btn-md">
                                                        <i class="bi bi-qr-code-scan"></i>
                                                    </button> -->
                                                    
                                                </div>
                                                
                                                <div class="row mb-3">
                                                    <div class="col-12">
                                                        <h5>Barang Terpilih</h5>
                                                        <table id="tabelBarangMutasi" class="table table-bordered table-striped text-center" style="width:100%;">
                                                            <thead class="bg-secondary text-white">
                                                                <tr>
                                                                    <th>No</th>
                                                                    <th>PT Asal</th>
                                                                    <th>No PO</th>
                                                                    <th>Serial Number</th>
                                                                    <th>Jenis Perangkat</th>
                                                                    <th>Kode Aset</th>
                                                                    <th>Merek</th>
                                                                    <th>Pengguna</th>
                                                                    <th class="text-center">Actions</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody></tbody>
                                                        </table>

                                                        <div id="hiddenBarangInputs"></div>
                                                    </div>
                                                </div>
                                                                                            
                                            </div>
                                        </div>
                                        </div>
                                    </div>
                                    
                                    <!-- ROW 3: KETERANGAN -->
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="headingKeterangan">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseKeterangan" aria-expanded="false" aria-controls="collapseKeterangan">
                                        Keterangan
                                        </button>
                                        </h2>
                                        <div id="collapseKeterangan" class="accordion-collapse collapse" aria-labelledby="headingKeterangan" data-bs-parent="#accordionForm">
                                        <div class="accordion-body">
                                            <div class="mb-3">
                                                <label for="keterangan" class="form-label">Keterangan</label>
                                                <textarea class="form-control" id="keterangan" name="keterangan" rows="3" placeholder="Masukkan kondisi barang"></textarea>
                                            </div>
                                        </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            
                        </div>

                        <div class="custom-input-gambar-section col-4" >
                            <h3>Gambar</h3>
                            <div class="custom-input-gambar border border-2 rounded-3 p-1" style="height:485px; overflow-y:auto;">
                                <div class=" d-flex flex-column">
                                    <div id="gambar-container" class="d-flex flex-column gap-2"></div>
                                    <button type="button" class="btn btn-primary w-50 align-self-center mb-1" onclick="tambahGambar()">+ Tambah Gambar Lampiran Mutasi</button>
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
                <a id="tombolClosePopupEdit" class='btn btn-danger btn-sm' href='#'><i class="bi bi-x-lg"></i></a>
            </div>
            <form class="popupEdit d-flex flex-column" method="post" action="proses_edit.php" enctype="multipart/form-data">
                <input type="hidden" name="id" id="id_edit">

                <div class="form-section">
                    <div class="row position-relative">

                        <div class="custom-input-form col-8">
                            <h3>Data Berita Acara Mutasi</h3>
                            <div class="row">
                                <div class="custom-input-tanggal col-3">
                                    <div class="input-group" style="width:220px;">
                                        <span class="input-group-text">Tanggal</span>
                                        <input class="form-control" type="date" name="tanggal" id="tanggal_edit" max="<?= date('Y-m-d') ?>" required>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="input-group" style="width:180px;">
                                        <span class="input-group-text">Nomor BA</span>
                                        <input type="text" class="form-control" name="nomor_ba" id="nomor_ba_edit" readonly>
                                    </div>
                                </div>
                            </div>

                            <div class="row mt-3">
                                <div class="accordion w-100" id="accordionForm2">

                                    <!-- ROW 1: DATA MUTASI -->
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="headingMutasi">
                                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseMutasi" aria-expanded="true" aria-controls="collapseMutasi">
                                                Data Mutasi
                                            </button>
                                        </h2>
                                        <div id="collapseMutasi" class="accordion-collapse collapse show" aria-labelledby="headingMutasi" data-bs-parent="#accordionForm2">
                                            <div class="accordion-body">
                                                <div class="row">
                                                    <div class="col-md-6 mb-3">
                                                        <label for="lokasi_asal" class="form-label">Lokasi Asal</label>
                                                        <select class="form-select" id="lokasi_asal_edit" name="lokasi_asal" required>
                                                            <option value="">-- Pilih Lokasi Asal --</option>
                                                        <?php 
                                                        // Kunci PT sesuai user SESSION pt
                                                        foreach ($daftar_pt as $pt) {
                                                            if (is_array($pt_user) && in_array($pt, $pt_user)) {
                                                                // Jika user punya akses ke PT ini
                                                                echo "<option value='$pt'>$pt</option>";
                                                            } elseif ($pt === $pt_user) {
                                                                // Kompatibilitas jika $_SESSION['pt'] masih string (user lama)
                                                                echo "<option value='$pt'>$pt</option>";
                                                            } else {
                                                                // Tidak punya akses
                                                                echo "<option value='$pt' disabled>$pt</option>";
                                                            }
                                                        }
                                                        ?>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-6 mb-3">
                                                        <label for="lokasi_tujuan" class="form-label">Lokasi Tujuan</label>
                                                        <select class="form-select" id="lokasi_tujuan_edit" name="lokasi_tujuan" required>
                                                            <option value="">-- Pilih Lokasi Tujuan --</option>
                                                            <option value="PT.MSAL (HO)">PT.MSAL (HO)</option>
                                                            <option value="PT.MSAL (SITE)">PT. MSAL (SITE)</option>
                                                            <option value="PT.WCJU">PT. WCJU</option>
                                                            <option value="PT.MAPA">PT. MAPA</option>
                                                            <option value="PT.PSAM">PT. PSAM</option>
                                                            <option value="PT.PEAK">PT. PEAK</option>
                                                            <option value="PT.KPP">PT. KPP</option>
                                                        </select>
                                                    </div>
                                                </div>

                                                <div class="row mt-3">
                                                    <!-- Data Pengirim -->
                                                    <div class="col-md-6 mb-3 position-relative">
                                                        <label class="form-label">Yang Menyerahkan Aset</label>
                                                        <div class="input-group">
                                                            <input type="text" class="form-control" id="nama_pengirim_edit" name="nama_pengirim" placeholder="Nama karyawan" required readonly>
                                                            <button type="button" class="btn btn-outline-secondary" id="btnCariPengirimEdit" title="Cari karyawan">
                                                                <i class="bi bi-search"></i>
                                                            </button>
                                                        </div>
                                                        <div id="dropdownPengirimEdit" class="dropdown-menu w-100"></div>
                                                        
                                                        <div class="mt-3">
                                                            <label class="form-label">Yang Menyerahkan Aset 2</label>
                                                            <div class="input-group mb-2">
                                                                <input type="text" class="form-control" id="nama_pengirim2_edit" name="nama_pengirim2" placeholder="Nama karyawan" required readonly>
                                                                <button type="button" class="btn btn-outline-secondary" id="btnCariPengirim2Edit" title="Cari karyawan">
                                                                    <i class="bi bi-search"></i>
                                                                </button>
                                                            </div>
                                                            <div id="dropdownPengirim2Edit" class="dropdown-menu w-100"></div>
                                                        </div>
                                                    </div>

                                                    <!-- Data Penerima -->
                                                    <div class="col-md-6 mb-3 position-relative">
                                                        <label class="form-label">Nama Penerima</label>
                                                        <div class="input-group mb-2">
                                                            <input type="text" id="nama_penerima_edit" name="nama_penerima" class="form-control" placeholder="Nama karyawan" required readonly>
                                                            <button type="button" id="btnCariPenerimaEdit" class="btn btn-outline-secondary">
                                                                <i class="bi bi-search"></i>
                                                            </button>
                                                        </div>
                                                        <div id="dropdownPenerimaEdit" class="position-absolute w-100"></div>

                                                        <div id="penerima2WrapperEdit" class="mt-3">
                                                            <label for="nama_penerima2" class="form-label">Nama Penerima 2</label>
                                                            <div class="input-group mb-2">
                                                                <input type="text" id="nama_penerima2_edit" name="nama_penerima2" class="form-control" placeholder="Nama karyawan" required readonly>
                                                                <button type="button" id="btnCariPenerima2Edit" class="btn btn-outline-secondary">
                                                                    <i class="bi bi-search"></i>
                                                                </button>
                                                            </div>
                                                            <div id="dropdownPenerima2Edit" class="position-absolute w-100"></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- ROW 2: DATA BARANG -->
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="headingBarang">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseBarang" aria-expanded="false" aria-controls="collapseBarang">
                                                Data Barang
                                            </button>
                                        </h2>
                                        <div id="collapseBarang" class="accordion-collapse collapse" aria-labelledby="headingBarang" data-bs-parent="#accordionForm2">
                                            <div class="accordion-body">
                                                <div class="row mb-3">
                                                    <div class="d-flex mb-3">
                                                        <div class="tombolDataBarangPopupEdit btn btn-primary btn-md" data-target="input"><i class="bi bi-search"></i></div>
                                                    </div>

                                                    <div class="row mb-3">
                                                        <div class="col-12">
                                                            <h5>Barang Terpilih</h5>
                                                            <table id="tabelBarangMutasiEdit" class="table table-bordered table-striped text-center" style="width:100%;">
                                                                <thead class="bg-secondary text-white">
                                                                    <tr>
                                                                        <th>No</th>
                                                                        <th>PT Asal</th>
                                                                        <th>No PO</th>
                                                                        <th>Serial Number</th>
                                                                        <th>Jenis Perangkat</th>
                                                                        <th>Kode Aset</th>
                                                                        <th>Merek</th>
                                                                        <th>Pengguna</th>
                                                                        <th class="text-center">Action</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody></tbody>
                                                            </table>

                                                            <div id="hiddenBarangInputsEdit"></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- ROW 3: KETERANGAN -->
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="headingKeterangan">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseKeterangan" aria-expanded="false" aria-controls="collapseKeterangan">
                                        Keterangan
                                        </button>
                                        </h2>
                                        <div id="collapseKeterangan" class="accordion-collapse collapse" aria-labelledby="headingKeterangan" data-bs-parent="#accordionForm2">
                                        <div class="accordion-body">
                                            <div class="mb-3">
                                                <label for="keterangan" class="form-label"></label>
                                                <textarea class="form-control" id="keterangan_edit" name="keterangan" rows="3" placeholder="Masukkan kondisi barang"></textarea>
                                            </div>
                                        </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Gambar -->
                        <div class="custom-input-gambar-section col-4">
                            <h3>Gambar</h3>
                            <div class="custom-input-gambar border border-2 rounded-3 p-1" style="height:485px; overflow-y:auto;">
                                <div class="d-flex flex-column">
                                    <div id="gambarEdit-container" class="d-flex flex-column gap-2"></div>
                                    <button type="button" class="btn btn-primary w-50 align-self-center mb-1" onclick="tambahGambarEdit()">+ Tambah Gambar Lampiran Mutasi</button>
                                </div>
                            </div>
                            <div class="mt-1" style="height:max-content;">
                                <div class="row mt-3 pe-0 custom-form-ae">
                                    <div class="input-group">
                                        <span class="input-group-text custom-font-form">Alasan perubahan</span>
                                        <textarea name="alasan_perubahan" class="form-control custom-font-form" style="font-size:small;" rows="2" name="alasan_perubahan" required></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
                <div class="footer-form d-flex w-100 justify-content-between mt-1">
                    <h5 class="custom-font-form text-formulir m-0 mt-3" style="color: darkgray;">*Formulir ini untuk melaporkan perpindahan aset</h5>
                    <div class="custom-form-submit w-25 align-self-end">
                        <?php 
                        
                        ?>
                        <p id="warningPendingEdit" class="custom-font-form m-0 mb-1 text-warning" style="display: none;"><i class="bi bi-exclamation-triangle"></i> Dengan melakukan submit, data edit anda yang saat ini menunggu persetujuan akan dihapus.</p>
                        <p id="warningApprovalExist" class="custom-font-form m-0 mb-1 text-warning" style="display:none;"><i class="bi bi-exclamation-triangle"></i> Surat sudah ada yang menyetujui, data yang diedit akan butuh approval pihak terkait.</p>
                        <!-- <p class="m-0" style="color: darkgray;">Approval akan direset saat meyimpan perubahan</p> -->
                        <input class="w-100 mt-0" type="submit" value="Simpan">
                    </div>
                </div>
            </form>
            
        </div>

        <div id="popupBoxDetail" class="popup-box position-absolute bg-white top-0 rounded-1 flex-column justify-content-start align-items-center p-2" style="height: max-content;align-self: center;z-index: 9;width: 95%;">
            <div class="w-100 d-flex justify-content-between mb-2" style="height: max-content;">
                <h4 id="popupDetailTitle" class="m-0 p-0">Detail Berita Acara</h4>
                <a id="tombolClosePopupDetail" class='btn btn-danger btn-sm' href='#'> 
                    <i class="bi bi-x-lg"></i>
                </a>
            </div>
            <div id="popupDetailBody" class="w-100">
                <h2 id="judul_detail"></h2>
                <table id="tabelAktorDetail" class="table w-25 table-borderless tabel-aktor" >
                    <thead></thead>
                    <tbody></tbody>
                    <!-- <thead>
                        <tr>
                            <th>
                                Pengirim
                            </th>
                            <th>
                                Diketahui 1
                            </th>
                            <th>
                                Diketahui 2
                            </th>
                            <th>
                                Penerima 1
                            </th>
                            <th>
                                Penerima 2
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Jabatan Pengirim</td>
                            <td>Jabatan Diketahui 1</td>
                            <td>Jabatan Diketahui 2</td>
                            <td>Jabatan Penerima 1</td>
                            <td>Jabatan Penerima 2</td>
                        </tr>
                        <tr>
                            <td>
                                <span 
                                class="border fw-bold bg-warning-subtle border-warning-subtle text-warning"
                                style="border-radius:6px; padding:6px 12px;"
                                >Statusnya
                                </span>
                            </td>
                            <td>
                                <span 
                                class="border fw-bold bg-warning-subtle border-warning-subtle text-warning"
                                style="border-radius:6px; padding:6px 12px;"
                                >Statusnya
                                </span>
                            </td>
                            <td>
                                <span 
                                class="border fw-bold bg-warning-subtle border-warning-subtle text-warning"
                                style="border-radius:6px; padding:6px 12px;"
                                >Statusnya
                                </span>
                            </td>
                            <td>
                                <span 
                                class="border fw-bold bg-warning-subtle border-warning-subtle text-warning"
                                style="border-radius:6px; padding:6px 12px;"
                                >Statusnya
                                </span>
                            </td>
                            <td>
                                <span 
                                class="border fw-bold bg-warning-subtle border-warning-subtle text-warning"
                                style="border-radius:6px; padding:6px 12px;"
                                >Statusnya
                                </span>
                            </td>
                        </tr>
                    </tbody> -->
                </table>
                <div class="w-100 d-flex justify-content-start"><p class="m-0">Pembuat Surat: <span id="pembuat_detail"></span></p></div>
                <div class="d-flex gap-2 h-100">
                    <div class="w-50 d-flex flex-column" style="height: 200px;">
                        <table class="table table-bordered table-striped">
                            <tbody>
                                <tr style="display: none;">
                                    <th style="width: 200px;">ID</th>
                                    <td id="id_detail"></td>
                                </tr>
                                <tr>
                                    <th style="font-size:14px;width:20%;min-width:150px;">Nomor BA</th>
                                    <td style="font-size:14px;width:80%;" id="nomor_ba_detail"></td>
                                </tr>
                                <tr>
                                    <th style="font-size:14px;width:20%;min-width:150px;">Tanggal</th>
                                    <td style="font-size:14px;width:80%;" id="tanggal_detail"></td>
                                </tr>
                                <tr>
                                    <th style="font-size:14px;width:20%;min-width:150px;">Lokasi Asal</th>
                                    <td style="font-size:14px;width:80%;" id="lokasi_asal_detail"></td>
                                </tr>
                                <tr>
                                    <th style="font-size:14px;width:20%;min-width:150px;">Lokasi Tujuan</th>
                                    <td style="font-size:14px;width:80%;" id="lokasi_tujuan_detail"></td>
                                </tr>
                                <tr>
                                    <th style="font-size:14px;width:20%;min-width:150px;">Keterangan</th>
                                    <td style="font-size:14px;width:80%;" id="keterangan_detail"></td>
                                </tr>
                                <tr style="display: none;">
                                    <th style="font-size:14px;width:20%;min-width:150px;">Nama Pengirim</th>
                                    <td style="font-size:14px;width:80%;" id="nama_pengirim_detail"></td>
                                </tr>
                                <tr style="display: none;">
                                    <th style="font-size:14px;width:20%;min-width:150px;">Nama Penerima 1</th>
                                    <td style="font-size:14px;width:80%;" id="nama_penerima_detail"></td>
                                </tr>
                                <tr style="display: none;">
                                    <th style="font-size:14px;width:20%;min-width:150px;">Nama Penerima 2</th>
                                    <td style="font-size:14px;width:80%;" id="nama_penerima2_detail"></td>
                                </tr>

                            </tbody>
                        </table>

                        <table id="myTableDetail" class="table table-bordered table-striped" style="width: 100%;">
                            <thead class="bg-secondary text-white">
                                <tr>
                                    <th>No</th>
                                    <th>PT Asal</th>
                                    <th>No PO</th>
                                    <th>Serial Number</th>
                                    <th>Jenis Perangkat</th>
                                    <th>Kode Aset</th>
                                    <th>Merek</th>
                                    <th>Pengguna</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>

                    </div>
                    <div class="w-50 d-flex border rounded-1 mb-1 overflow-auto p-2" style="height:525px;"></div>

                </div>
                
            </div>
        </div>

        <div id="popupBoxDataBarang" class="popup-box position-absolute bg-white top-0 rounded-1 flex-column justify-content-start align-items-center p-2" style="height: max-content;align-self: center;z-index: 10;width: 95%;">
            <div class="w-100 d-flex justify-content-between mb-2" style="height: max-content;">
                <h4 id="popupDataBarangTitle" class="m-0 p-0">Pilih Barang Mutasi</h4>
                <a id="tombolClosePopupDataBarang" class='btn btn-danger btn-sm' href='#'>
                    <i class="bi bi-x-lg"></i>
                </a>
            </div>
            <!-- <p class="m-0 p-0 align-self-start">Klik pada baris tabel data untuk memilih</p> -->
            <div class="d-flex w-100 flex-column">
                <p>Scan kode QR barang</p>
                <button style="max-width: 50px;" type="button" id="openScanModal" class="btn btn-primary btn-md">
                    <i class="bi bi-qr-code-scan"></i>
                </button>
                <p class="m-0 mt-3">atau pilih lewat tabel</p>
            </div>
            
            <div class="w-100" style="height: max-content;">
                <table id="myTable2" class="table table-bordered table-striped text-center" style="width: 100%;">
                    <thead class="bg-secondary">
                        <tr>
                            <th>No</th>
                            <th>PT Asal</th>
                            <th>No PO</th>
                            <th>Serial Number</th>
                            <th>Jenis Perangkat</th>
                            <th>Kode Aset</th>
                            <th>Merek</th>
                            <th>Pengguna</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $no = 1;
                    while ($row = $result_assets->fetch_assoc()):
                    ?>
                    <tr data-id="<?= $row['id_assets']; ?>" 
                        data-pt="<?= htmlspecialchars($row['pt']); ?>"
                        data-nopo="<?= htmlspecialchars($row['no_po']); ?>"
                        data-serial="<?= htmlspecialchars($row['serial_number']); ?>" 
                        data-jenis="<?= htmlspecialchars($row['jenis_perangkat']); ?>" 
                        data-kode-assets="<?= htmlspecialchars($row['kode_assets']); ?>"
                        data-merk="<?= htmlspecialchars($row['merk']); ?>"
                        data-user="<?= htmlspecialchars($row['user']); ?>"
                        >
                        <td><?= $no++; ?></td>
                        <td><?= htmlspecialchars($row['pt']); ?></td>
                        <td><?= htmlspecialchars($row['no_po']); ?></td>
                        <td><?= htmlspecialchars($row['serial_number']); ?></td>
                        <td><?= htmlspecialchars($row['jenis_perangkat']); ?></td>
                        <td><?= htmlspecialchars($row['kode_assets']); ?></td>
                        <td><?= htmlspecialchars($row['merk']); ?></td>
                        <td><?= htmlspecialchars($row['user']); ?></td>
                    </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <div class="w-100 d-flex flex-column">
                <div class="d-flex justify-content-between align-items-center mb-2 mt-3">
                    <h5 class="mb-0">Daftar Barang Terpilih</h5>
                    <button id="clearTable3" class="btn btn-warning btn-sm">
                        Kosongkan List
                    </button>
                </div>

                <div class="w-100 border" style="min-height:100px; max-height: 400px; height:max-content; overflow:auto;">
                <table id="myTable3" class="table table-bordered table-striped text-center" style="width:100%;">
                    <thead class="bg-secondary">
                        <tr>
                            <th>No</th>
                            <th>PT Asal</th>
                            <th>No PO</th>
                            <th>Serial Number</th>
                            <th>Jenis Perangkat</th>
                            <th>Kode Aset</th>
                            <th>Merek</th>
                            <th>Pengguna</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
                </div>
            </div>

            <div class="w-100 d-flex justify-content-end mt-3">
                <button type="button" id="submitBarangTerpilih" class="btn btn-success btn-md">
                    <i class="bi bi-check-lg"></i> Simpan Barang Terpilih
                </button>
            </div>

        </div>

        <div id="popupBoxDataBarangEdit" class="popup-box position-absolute bg-white top-0 rounded-1 flex-column justify-content-start align-items-center p-2" style="height: max-content;align-self: center;z-index: 10;width: 95%;">
            <div class="w-100 d-flex justify-content-between mb-2" style="height: max-content;">
                <h4 id="popupDataBarangTitleEdit" class="m-0 p-0">Pilih Barang Mutasi</h4>
                <a id="tombolClosePopupDataBarangEdit" class='btn btn-danger btn-sm' href='#'>
                    <i class="bi bi-x-lg"></i>
                </a>
            </div>

            <div class="d-flex w-100 flex-column">
                <p>Scan kode QR barang</p>
                <button style="max-width: 50px;" type="button" id="openScanModalEdit" class="btn btn-primary btn-md">
                    <i class="bi bi-qr-code-scan"></i>
                </button>
                <p class="m-0 mt-3">atau pilih lewat tabel</p>
            </div>
            
            <div class="w-100" style="height: max-content;">
                <table id="myTable2Edit" class="table table-bordered table-striped text-center" style="width: 100%;">
                    <thead class="bg-secondary">
                        <tr>
                            <th>No</th>
                            <th>PT Asal</th>
                            <th>No PO</th>
                            <th>Serial Number</th>
                            <th>Jenis Perangkat</th>
                            <th>Kode Aset</th>
                            <th>Merek</th>
                            <th>Pengguna</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $no = 1;
                    while ($row = $result_assets2->fetch_assoc()):
                    ?>
                    <tr data-id="<?= $row['id_assets']; ?>" 
                        data-pt="<?= htmlspecialchars($row['pt']); ?>"
                        data-nopo="<?= htmlspecialchars($row['no_po']); ?>"
                        data-serial="<?= htmlspecialchars($row['serial_number']); ?>" 
                        data-jenis="<?= htmlspecialchars($row['jenis_perangkat']); ?>"
                        data-kode-assets="<?= htmlspecialchars($row['kode_assets']); ?>"
                        data-merk="<?= htmlspecialchars($row['merk']); ?>"
                        data-user="<?= htmlspecialchars($row['user']); ?>"
                        >
                        <td><?= $no++; ?></td>
                        <td><?= htmlspecialchars($row['pt']); ?></td>
                        <td><?= htmlspecialchars($row['no_po']); ?></td>
                        <td><?= htmlspecialchars($row['serial_number']); ?></td>
                        <td><?= htmlspecialchars($row['jenis_perangkat']); ?></td>
                        <td><?= htmlspecialchars($row['kode_assets']); ?></td>
                        <td><?= htmlspecialchars($row['merk']); ?></td>
                        <td><?= htmlspecialchars($row['user']); ?></td>
                    </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <div class="w-100 d-flex flex-column">
                <div class="d-flex justify-content-between align-items-center mb-2 mt-3">
                    <h5 class="mb-0">Daftar Barang Terpilih</h5>
                    <button id="clearTable3Edit" class="btn btn-warning btn-sm">
                        Kosongkan List
                    </button>
                </div>

                <div class="w-100 border" style="min-height:100px; max-height: 400px; height:max-content; overflow:auto;">
                <table id="myTable3Edit" class="table table-bordered table-striped text-center" style="width:100%;">
                    <thead class="bg-secondary">
                        <tr>
                            <th>No</th>
                            <th>PT Asal</th>
                            <th>No PO</th>
                            <th>Serial Number</th>
                            <th>Jenis Perangkat</th>
                            <th>Kode Aset</th>
                            <th>Merek</th>
                            <th>Pengguna</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
                </div>
            </div>

            <div class="w-100 d-flex justify-content-end mt-3">
                <button type="button" id="submitBarangTerpilihEdit" class="btn btn-success btn-md">
                    <i class="bi bi-check-lg"></i> Simpan Barang Terpilih
                </button>
            </div>
        </div>

    </section>
    
    </main>
        <div id="popupBGDelete" class="popup-bg position-absolute w-100 h-100" style="background-color: rgba(0,0,0,0.5); z-index: 8;"></div>
        <div id="popupBGEdit"   class="popup-bg position-absolute w-100 h-100" style="background-color: rgba(0,0,0,0.5); z-index: 8;"></div>
        <div id="popupBGInput"  class="popup-bg position-absolute w-100 h-100" style="background-color: rgba(0,0,0,0.5); z-index: 8;"></div>
        <div id="popupBG"       class="popup-bg position-absolute w-100 h-100" style="background-color: rgba(0,0,0,0.5); z-index: 8;"></div>
        <div id="popupBG2"      class="popup-bg position-absolute w-100 h-100" style="background-color: rgba(0,0,0,0.2); z-index: 9;"></div>
        <div id="popupBG2Edit"  class="popup-bg position-absolute w-100 h-100" style="background-color: rgba(0,0,0,0.2); z-index: 9;"></div>
        
        <footer class="custom-footer d-flex position-relative p-2" style="border-top: whitesmoke solid 1px; box-shadow: 0px 7px 10px black; color: grey;">
            <p class="position-absolute" style="right: 15px;bottom:7px;color: grey;"><strong>Version </strong>1.1.0</p>
            <p class="pt-2 ps-1"><strong>Copyright &copy 2025</p></strong><p class="pt-2 ps-1 fw-bold text-primary">MIS MSAL.</p><p class="pt-2 ps-1"> All rights reserved</p>
        </footer>

        <?php   
        // Ambil data warna
        $sqlWarna = "SELECT nama, warna FROM personalia_menucolor ORDER BY nama ASC";
        $resultWarna = $koneksi->query($sqlWarna);
        ?>

        <div id="popupBoxPersonalia" class="popup-box position-fixed end-0" style="z-index: 15; top: 50px;">
            <div id="theme-panel" class="card position-relative bg-white p-2 m-2" style="width:200px; height:max-content; box-shadow: 0px 4px 8px rgba(0,0,0,0.1); ">
                <h5 class="card-title border-bottom pb-2 mb-0">Personalia</h5>
                <form action="../proses_simpan_personalia.php" method="post" class="p-0">
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

    </div>
    
<!-- Bootstrap 5 -->
<script src="../assets/bootstrap-5.3.6-dist/js/bootstrap.min.js"></script>

<!-- popperjs Bootstrap 5 -->
<script src="../assets/js/popper.min.js"></script>

<!-- AdminLTE -->
<script src="../assets/adminlte/js/adminlte.js"></script>

<!-- OverlayScrollbars -->
<script src="../assets/js/overlayscrollbars.browser.es6.min.js"></script>

<script>//Popup Data Barang
    document.addEventListener('DOMContentLoaded', function () {
        var close = document.getElementById('tombolClosePopupDataBarang');
        var box = document.getElementById('popupBoxDataBarang');
        var background = document.getElementById('popupBG2');


        document.addEventListener('click', function (e) {
            if (e.target.closest('.tombolDataBarangPopup')) {
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

<script>//Popup Data Barang Edit
    document.addEventListener('DOMContentLoaded', function () {
        var close = document.getElementById('tombolClosePopupDataBarangEdit');
        var box = document.getElementById('popupBoxDataBarangEdit');
        var background = document.getElementById('popupBG2Edit');


        document.addEventListener('click', function (e) {
            if (e.target.closest('.tombolDataBarangPopupEdit')) {
                box.classList.add('aktifPopup2');
                background.classList.add('aktifPopup2');
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
                background.classList.remove('aktifPopup2');
                box.classList.remove('aktifPopup2');
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
                background.classList.remove('aktifPopup2');
                box.classList.remove('aktifPopup2');
            }, 300); 
            box.classList.remove('scale-in-center');
            box.classList.add('scale-out-center');
            background.classList.remove('fade-in');
            background.classList.add('fade-out');
        });
    });
</script>

<script>//Submit barang terpilih ke tabel utama 
document.addEventListener('DOMContentLoaded', function () {
    const btnSubmitBarang = document.getElementById('submitBarangTerpilih');
    const popupBox = document.getElementById('popupBoxDataBarang');

    btnSubmitBarang.addEventListener('click', function () {
        // Pastikan DataTable sudah tersedia
        if (typeof window.tableBarangMutasi === 'undefined') {
            alert('Tabel belum siap. Silakan coba lagi.');
            return;
        }

        const rows = document.querySelectorAll('#myTable3 tbody tr');

        // Bersihkan DataTable terlebih dahulu
        window.tableBarangMutasi.clear();

        let no = 1;
        rows.forEach(row => {
            const pt = row.children[1].textContent.trim();
            const no_po = row.children[2].textContent.trim();
            const serial = row.children[3].textContent.trim();
            const jenis = row.children[4].textContent.trim();
            const kode = row.children[5].textContent.trim();
            const merk = row.children[6].textContent.trim();
            const user = row.children[7].textContent.trim();

            // Tambah menggunakan API DataTables (jaga konsistensi internal)
            window.tableBarangMutasi.row.add([
                no,
                pt,
                no_po,
                serial,
                jenis,
                kode,
                merk,
                user,
                `<button class="btn btn-danger btn-sm hapusRow">Hapus</button>`
            ]);
            no++;
        });

        // Render perubahan
        window.tableBarangMutasi.draw(false);

        // Tutup popup dengan animasi (sama seperti sebelumnya)
        const background = document.getElementById('popupBG2');
        const box = document.getElementById('popupBoxDataBarang');

        box.classList.remove('scale-in-center');
        box.classList.add('scale-out-center');
        background.classList.remove('fade-in');
        background.classList.add('fade-out');

        setTimeout(() => {
            background.classList.remove('aktifPopup');
            box.classList.remove('aktifPopup');
        }, 300);
    });

    // Event delegation: tangani klik Hapus pada tabel utama (#tabelBarangMutasi)
    document.querySelector('#tabelBarangMutasi tbody').addEventListener('click', function (e) {
        if (e.target && e.target.matches('.hapusRow')) {
            const tr = e.target.closest('tr');
            // gunakan DataTables API untuk remove
            if (window.tableBarangMutasi) {
                window.tableBarangMutasi.row(tr).remove().draw(false);
                // update nomor urut (kolom 0)
                window.tableBarangMutasi.rows().every(function (idx) {
                    this.cell(idx, 0).data(idx + 1);
                });
            } else {
                
                tr.remove();
            }
        }
    });

});
</script>

<script>//Submit barang terpilih ke tabel utama (EDIT)
document.addEventListener('DOMContentLoaded', function () {
    const btnSubmitBarangEdit = document.getElementById('submitBarangTerpilihEdit');
    const popupBoxEdit = document.getElementById('popupBoxDataBarangEdit');

    btnSubmitBarangEdit.addEventListener('click', function () {
        // Pastikan DataTable edit sudah tersedia
        if (typeof window.tableBarangMutasiEdit === 'undefined') {
            alert('Tabel belum siap. Silakan coba lagi.');
            return;
        }

        const rows = document.querySelectorAll('#myTable3Edit tbody tr');

        // Bersihkan DataTable terlebih dahulu
        window.tableBarangMutasiEdit.clear();

        let no = 1;
        rows.forEach(row => {
            const pt = row.children[1].textContent.trim();
            const no_po = row.children[2].textContent.trim();
            const serial = row.children[3].textContent.trim();
            const jenis = row.children[4].textContent.trim();
            const kode = row.children[5].textContent.trim();
            const merk = row.children[6].textContent.trim();
            const user = row.children[7].textContent.trim();

            // Tambah menggunakan API DataTables (edit)
            window.tableBarangMutasiEdit.row.add([
                no,
                pt,
                no_po,
                serial,
                jenis,
                kode,
                merk,
                user,
                `<button class="btn btn-danger btn-sm hapusRowEdit">Hapus</button>`
            ]);
            no++;
        });

        // Render perubahan
        window.tableBarangMutasiEdit.draw(false);

        // Tutup popup edit dengan animasi (sesuai sistem kamu)
        const backgroundEdit = document.getElementById('popupBG2Edit');
        const boxEdit = document.getElementById('popupBoxDataBarangEdit');

        boxEdit.classList.remove('scale-in-center');
        boxEdit.classList.add('scale-out-center');
        backgroundEdit.classList.remove('fade-in');
        backgroundEdit.classList.add('fade-out');

        setTimeout(() => {
            backgroundEdit.classList.remove('aktifPopup2');
            boxEdit.classList.remove('aktifPopup2');
        }, 300);
    });

    // Event delegation: tangani klik Hapus di tabel barang mutasi EDIT
    document.querySelector('#tabelBarangMutasiEdit tbody').addEventListener('click', function (e) {
        if (e.target && e.target.matches('.hapusRowEdit')) {
            const tr = e.target.closest('tr');
            // gunakan DataTables API untuk remove
            if (window.tableBarangMutasiEdit) {
                window.tableBarangMutasiEdit.row(tr).remove().draw(false);
                // update nomor urut (kolom 0)
                window.tableBarangMutasiEdit.rows().every(function (idx) {
                    this.cell(idx, 0).data(idx + 1);
                });
            } else {
                tr.remove();
            }
        }
    });
});
</script>

<script>// Datatables pilih barang dan tabel terpilih
document.addEventListener('DOMContentLoaded', function () {
    const table2 = $('#myTable2').DataTable({
        responsive: true,
        autoWidth: true,
        scrollY: '200px',        
        scrollCollapse: true,    
        info: false,
        language: {
            url: "../assets/json/id.json"
        },
        columnDefs: [
            { targets: "_all", className: "text-start" } // bootstrap text-start = rata kiri
        ]
    });

    // buat table3 juga global (opsional)
    window.table3 = $('#myTable3').DataTable({
        responsive: true,
        autoWidth: true,
        scrollY: '200px',
        scrollCollapse: true,
        paging: false,
        searching: false,
        info: false,
        language: { 
            url: "../assets/json/id.json" 
        },
        columnDefs: [
            { targets: "_all", className: "text-start" },
            { targets: -1, orderable: false, className: "text-center" }
        ]
    });

    // buat tableBarangMutasi global supaya handler submit bisa akses
    window.tableBarangMutasi = $('#tabelBarangMutasi').DataTable({
        responsive: true,
        autoWidth: true,
        scrollY: '200px',
        scrollCollapse: true,
        paging: false,
        searching: false,
        info: false,
        language: { 
            url: "../assets/json/id.json" 
        },
        columnDefs: [
            { targets: "_all", className: "text-start" },
            { targets: -1, orderable: false, className: "text-center" }
        ]
    });

    const clearBtn = document.getElementById('clearTable3');
    let selectedItems = [];

    // Klik baris di myTable2 → Tambahkan ke myTable3
    $('#myTable2 tbody').on('click', 'tr', function () {
        const data = table2.row(this).data();
        const id = this.dataset.id;

        if (!id) return;
        if (selectedItems.includes(id)) {
            alert('Barang ini sudah ditambahkan!');
            return;
        }

        selectedItems.push(id);
        const no = window.table3.rows().count() + 1;
        const pt = $(this).data('pt');
        const no_po = $(this).data('nopo');
        const serial = $(this).data('serial');
        const jenis = $(this).data('jenis');
        const kode = $(this).data('kode-assets');
        const merk = $(this).data('merk');
        const user = $(this).data('user');

        window.table3.row.add([
            no,
            pt,
            no_po,
            serial,
            jenis,
            kode,
            merk,
            user,
            `<button class="btn btn-danger btn-sm delete-row" data-id="${id}">Hapus</button>`
        ]).draw(false);
    });

    // Hapus satu baris dari myTable3 (delegation)
    $('#myTable3 tbody').on('click', '.delete-row', function () {
        const id = $(this).data('id');
        selectedItems = selectedItems.filter(item => item !== id);
        window.table3.row($(this).parents('tr')).remove().draw(false);

        // Update nomor urut ulang
        window.table3.rows().every(function (rowIdx, tableLoop, rowLoop) {
            this.cell(rowIdx, 0).data(rowIdx + 1);
        });
    });

    // Kosongkan semua isi myTable3
    clearBtn.addEventListener('click', function () {
        window.table3.clear().draw();
        selectedItems = [];
    });
});
</script>

<script>// DataTables pilih barang dan tabel terpilih (EDIT)
document.addEventListener('DOMContentLoaded', function () {
    const table2Edit = $('#myTable2Edit').DataTable({
        responsive: true,
        autoWidth: true,
        scrollY: '200px',        
        scrollCollapse: true,    
        info: false,
        language: {
            url: "../assets/json/id.json"
        },
        columnDefs: [
            { targets: "_all", className: "text-start" }
        ]
    });

    // buat table3Edit juga global (opsional)
    window.table3Edit = $('#myTable3Edit').DataTable({
        responsive: true,
        autoWidth: true,
        scrollY: '200px',
        scrollCollapse: true,
        paging: false,
        searching: false,
        info: false,
        language: { 
            url: "../assets/json/id.json" 
        },
        columnDefs: [
            { targets: "_all", className: "text-start" },
            { targets: -1, orderable: false, className: "text-center" }
        ]
    });

    // buat tableBarangMutasiEdit global supaya handler submit bisa akses
    window.tableBarangMutasiEdit = $('#tabelBarangMutasiEdit').DataTable({
        responsive: true,
        autoWidth: true,
        scrollY: '200px',
        scrollCollapse: true,
        paging: false,
        searching: false,
        info: false,
        language: { 
            url: "../assets/json/id.json" 
        },
        columnDefs: [
            { targets: "_all", className: "text-start" },
            { targets: -1, orderable: false, className: "text-center" }
        ]
    });

    const clearBtnEdit = document.getElementById('clearTable3Edit');
    let selectedItemsEdit = [];

    // Klik baris di myTable2Edit → Tambahkan ke myTable3Edit
    $('#myTable2Edit tbody').on('click', 'tr', function () {
        const data = table2Edit.row(this).data();
        const id = this.dataset.id;

        if (!id) return;
        if (selectedItemsEdit.includes(id)) {
            alert('Barang ini sudah ditambahkan!');
            return;
        }

        selectedItemsEdit.push(id);
        const no = window.table3Edit.rows().count() + 1;
        const pt = $(this).data('pt');
        const no_po = $(this).data('nopo');
        const serial = $(this).data('serial');
        const jenis = $(this).data('jenis');
        const kode = $(this).data('kode-assets');
        const merk = $(this).data('merk');
        const user = $(this).data('user');

        window.table3Edit.row.add([
            no,
            pt,
            no_po,
            serial,
            jenis,
            kode,
            merk,
            user,
            `<button class="btn btn-danger btn-sm delete-row-edit" data-id="${id}">Hapus</button>`
        ]).draw(false);
    });

    // Hapus satu baris dari myTable3Edit (delegation)
    $('#myTable3Edit tbody').on('click', '.delete-row-edit', function () {
        const id = $(this).data('id');
        selectedItemsEdit = selectedItemsEdit.filter(item => item !== id);
        window.table3Edit.row($(this).parents('tr')).remove().draw(false);

        // Update nomor urut ulang
        window.table3Edit.rows().every(function (rowIdx) {
            this.cell(rowIdx, 0).data(rowIdx + 1);
        });
    });

    // Kosongkan semua isi myTable3Edit
    clearBtnEdit.addEventListener('click', function () {
        window.table3Edit.clear().draw();
        selectedItemsEdit = [];
    });
});
</script>

<script>// fungsi input data barang form input 
document.querySelector('.popupInput').addEventListener('submit', function (e) {
    if (!window.tableBarangMutasi || window.tableBarangMutasi.rows().count() === 0) {
        alert('Silakan pilih minimal satu barang sebelum menyimpan.');
        e.preventDefault(); // hentikan submit form
        return;
    }

    const hiddenContainer = document.getElementById('hiddenBarangInputs');
    hiddenContainer.innerHTML = ''; // bersihkan data lama

    // Ambil data dari DataTable (lebih aman dan sinkron)
    if (window.tableBarangMutasi) {
        window.tableBarangMutasi.rows().every(function () {
            const data = this.data();

            const pt_asal = data[1] || ''; // PT Asal
            const po      = data[2] || ''; // No PO
            const sn      = data[3] || ''; // Serial Number
            const coa     = data[4] || ''; // Jenis Perangkat
            const kode    = data[5] || ''; //Kode Aset
            const merk    = data[6] || ''; // Merek
            const user    = data[7] || ''; // User

            const fields = { pt_asal, po, coa, kode, merk, sn, user };
            for (const [key, value] of Object.entries(fields)) {
                const input = document.createElement('input');
                input.type  = 'hidden';
                input.name  = `${key}[]`;
                input.value = value;
                hiddenContainer.appendChild(input);
            }
        });
    } else {
        // fallback kalau DataTables belum inisialisasi (jarang terjadi)
        const rows = document.querySelectorAll('#tabelBarangMutasi tbody tr');
        rows.forEach((row) => {
            const pt_asal = row.children[1]?.textContent.trim() || '';
            const po      = row.children[2]?.textContent.trim() || '';
            const sn      = row.children[3]?.textContent.trim() || '';
            const coa     = row.children[4]?.textContent.trim() || '';
            const kode    = row.children[5]?.textContent.trim() || '';
            const merk    = row.children[6]?.textContent.trim() || '';
            const user    = row.children[7]?.textContent.trim() || '';

            const fields = { pt_asal, po, coa, kode, merk, sn, user };
            for (const [key, value] of Object.entries(fields)) {
                const input = document.createElement('input');
                input.type  = 'hidden';
                input.name  = `${key}[]`;
                input.value = value;
                hiddenContainer.appendChild(input);
            }
        });
    }
});
</script>

<script>// fungsi load data barang saat edit
// === LOAD DATA BARANG SAAT EDIT ===
document.addEventListener('DOMContentLoaded', function () {
    const openButtons = document.querySelectorAll('.tombolEditPopup');

    openButtons.forEach(btn => {
        btn.addEventListener('click', async function (e) {
            e.preventDefault();
            const id_ba = this.dataset.id;
            if (!id_ba) return;

            // Kosongkan tabel sebelum isi ulang
            window.table3Edit?.clear().draw();
            window.tableBarangMutasiEdit?.clear().draw();

            try {
                const response = await fetch(`get_barang_mutasi.php?id_ba=${id_ba}`);
                if (!response.ok) throw new Error('Gagal ambil data barang');
                const data = await response.json();

                let no = 1;
                data.forEach(item => {
                    const pt_asal = item.pt_asal || '-';
                    const po      = item.po || '-';
                    const coa     = item.coa || '-';
                    const kode    = item.kode_assets || '-';
                    const merk    = item.merk || '-';
                    const sn      = item.sn || '-';
                    const user    = item.user || '-';

                    // Tambah ke popup list (myTable3Edit)
                    window.table3Edit.row.add([
                        no,
                        pt_asal,
                        po,
                        sn,
                        coa,
                        kode,
                        merk,
                        user,
                        `<button class="btn btn-danger btn-sm delete-row-edit">Hapus</button>`
                    ]).draw(false);

                    // Tambah ke tabel utama (tabelBarangMutasiEdit)
                    window.tableBarangMutasiEdit.row.add([
                        no,
                        pt_asal,
                        po,
                        sn,
                        coa,
                        kode,
                        merk,
                        user,
                        `<button class="btn btn-danger btn-sm hapusRowEdit">Hapus</button>`
                    ]).draw(false);

                    no++;
                });

            } catch (err) {
                console.error('Error saat load barang edit:', err);
            }
        });
    });
});
</script>

<script>// fungsi input data barang form edit
document.querySelector('.popupEdit').addEventListener('submit', function (e) {
    if (!window.tableBarangMutasiEdit || window.tableBarangMutasiEdit.rows().count() === 0) {
        alert('Silakan pilih minimal satu barang sebelum menyimpan.');
        e.preventDefault(); // hentikan submit form
        return;
    }

    const hiddenContainer = document.getElementById('hiddenBarangInputsEdit');
    hiddenContainer.innerHTML = ''; // bersihkan data lama

    // Ambil data dari DataTable edit
    if (window.tableBarangMutasiEdit) {
        window.tableBarangMutasiEdit.rows().every(function () {
            const data = this.data();

            const pt_asal = data[1] || ''; // PT Asal
            const po      = data[2] || ''; // No PO
            const sn      = data[3] || ''; // Serial Number
            const coa     = data[4] || ''; // Jenis Perangkat
            const kode    = data[5] || ''; // Kode Aset
            const merk    = data[6] || ''; // Merek
            const user    = data[7] || ''; // User

            const fields = { pt_asal, po, coa, kode, merk, sn, user };
            for (const [key, value] of Object.entries(fields)) {
                const input = document.createElement('input');
                input.type  = 'hidden';
                input.name  = `${key}[]`;
                input.value = value;
                hiddenContainer.appendChild(input);
            }
        });
    } else {
        // fallback kalau DataTables belum inisialisasi
        const rows = document.querySelectorAll('#tabelBarangMutasiEdit tbody tr');
        rows.forEach((row) => {
            const pt_asal = row.children[1]?.textContent.trim() || '';
            const po      = row.children[2]?.textContent.trim() || '';
            const sn      = row.children[3]?.textContent.trim() || '';
            const coa     = row.children[4]?.textContent.trim() || '';
            const kode    = row.children[5]?.textContent.trim() || '';
            const merk    = row.children[6]?.textContent.trim() || '';
            const user    = row.children[7]?.textContent.trim() || '';

            const fields = { pt_asal, po, coa, kode, merk, sn, user };
            for (const [key, value] of Object.entries(fields)) {
                const input = document.createElement('input');
                input.type  = 'hidden';
                input.name  = `${key}[]`;
                input.value = value;
                hiddenContainer.appendChild(input);
            }
        });
    }
});
</script>

<script>// fungsi input gambar form input
// === Fungsi untuk menambahkan input gambar ===
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

// === Tombol Jepret Kamera ===
const logoJepret = document.createElement('i');
logoJepret.className = 'bi bi-camera-fill';

const jepretKamera = document.createElement('button');
jepretKamera.type = 'button';
jepretKamera.className = 'btn btn-secondary btn-lg';
jepretKamera.style.marginTop = '5px';
jepretKamera.style.width = 'max-content';
jepretKamera.prepend(logoJepret);

let currentCamera = "environment"; // default belakang

// === Event kamera toggle ===
jepretKamera.onclick = async function () {
    const existingVideo = wrapper.querySelector('video');
    const existingCapture = wrapper.querySelector('.btn-capture');
    const existingSwitch = wrapper.querySelector('.btn-switch');
    const existingBtnGroup = wrapper.querySelector('.btn-group-kamera');

    // kalau kamera sedang aktif → matikan
    if (existingVideo && existingVideo._stream) {
        existingVideo._stream.getTracks().forEach(track => track.stop());
        existingVideo.remove();
        if (existingCapture) existingCapture.remove();
        if (existingSwitch) existingSwitch.remove();
        if (existingBtnGroup) existingBtnGroup.remove();
        preview.style.display = "block";
        return;
    }

    // buat elemen video
    const video = document.createElement('video');
    video.autoplay = true;
    video.style.maxWidth = "300px";
    video.style.border = "1px solid #ccc";
    video.style.borderRadius = "5px";
    wrapper.insertBefore(video, preview);
    preview.style.display = "none";

    // ==== fungsi start kamera dengan memilih deviceId ====
    async function startCamera() {
        const devices = await navigator.mediaDevices.enumerateDevices();
        const cams = devices.filter(d => d.kind === "videoinput");

        // pilih kamera sesuai currentCamera
        let targetCam = null;
        if (currentCamera === "environment") {
            targetCam = cams.find(c => c.label.toLowerCase().includes("back") ||
                                       c.label.toLowerCase().includes("rear"));
        } else {
            targetCam = cams.find(c => c.label.toLowerCase().includes("front"));
        }

        // fallback pakai kamera pertama
        if (!targetCam) targetCam = cams[0];

        try {
            const stream = await navigator.mediaDevices.getUserMedia({
                video: { deviceId: targetCam.deviceId }
            });

            video.srcObject = stream;
            video._stream = stream;
        } catch (err) {
            alert("Gagal membuka kamera: " + err);
        }
    }

    // tombol switch kamera
    const btnSwitch = document.createElement('button');
    btnSwitch.className = "btn btn-warning btn-switch mt-0";
    btnSwitch.style.width = "max-content";

    const iconSwitch = document.createElement('i');
    iconSwitch.className = "bi bi-arrow-clockwise";
    btnSwitch.appendChild(iconSwitch);

    btnSwitch.onclick = async function () {
        // ganti mode
        currentCamera = currentCamera === "environment" ? "user" : "environment";

        // matikan kamera sebelumnya
        if (video._stream) {
            video._stream.getTracks().forEach(t => t.stop());
        }

        // mulai kamera baru
        startCamera();
    };

    // tombol ambil foto
    const btnCapture = document.createElement('button');
    btnCapture.textContent = "Ambil Foto";
    btnCapture.className = "btn btn-success btn-capture mt-0";
    btnCapture.style.width = "max-content";

    btnCapture.onclick = function () {
        const canvas = document.createElement('canvas');
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        const ctx = canvas.getContext('2d');
        ctx.drawImage(video, 0, 0);

        if (video._stream) {
            video._stream.getTracks().forEach(track => track.stop());
        }

        video.remove();
        btnCapture.remove();
        btnSwitch.remove();

        preview.src = canvas.toDataURL("image/png");
        preview.style.display = "block";

        canvas.toBlob(function (blob) {
            const timestamp = Date.now();
            const nomorBA = document.getElementById('nomor_ba').value || "NOBA";

            const tanggalBAraw = document.getElementById('tanggal').value || "NOTGL";
            let tanggalBA = "NOTGL";

            if (tanggalBAraw.includes("-")) {
                const [yyyy, mm, dd] = tanggalBAraw.split("-");
                tanggalBA = `${dd}${mm}${yyyy}`;
            }

            const filename = `camera${nomorBA}BAM${tanggalBA}-${timestamp}.png`;

            const file = new File([blob], filename, { type: "image/png" });
            const dt = new DataTransfer();
            dt.items.add(file);
            input.files = dt.files;
        }, "image/png");
    };

    // === buat container sejajar ===
    const btnGroup = document.createElement('div');
    btnGroup.className = "d-flex gap-1 mt-0 btn-group-kamera";

    btnGroup.appendChild(btnSwitch);
    btnGroup.appendChild(btnCapture);

    wrapper.insertBefore(btnGroup, preview);

    // === mulai kamera pertama kali ===
    startCamera();
};


const btnHapus = document.createElement('button');
btnHapus.type = 'button';
btnHapus.innerHTML = '<i class="bi bi-trash3-fill"></i>';
btnHapus.className = 'btn btn-danger mt-1';
btnHapus.onclick = function () {
    const videoAktif = wrapper.querySelector('video');
    if (videoAktif && videoAktif._stream) {
        videoAktif._stream.getTracks().forEach(track => track.stop());
    }
    container.removeChild(wrapper);
};

wrapper.appendChild(input);
wrapper.appendChild(jepretKamera);
wrapper.appendChild(preview);
wrapper.appendChild(btnHapus);

container.appendChild(wrapper);
}
</script>

<script>// fungsi load gambar form edit
// === FUNGSI LOAD GAMBAR EDIT BERITA ACARA MUTASI ===
document.addEventListener('DOMContentLoaded', function () {
    const openButtons = document.querySelectorAll('.tombolEditPopup');

    openButtons.forEach(btn => {
        btn.addEventListener('click', async function (e) {
            e.preventDefault();
            const id_ba = this.dataset.id;
            if (!id_ba) return;

            // Kosongkan tabel sebelum isi ulang
            if (window.table3Edit) window.table3Edit.clear().draw();
            if (window.tableBarangMutasiEdit) window.tableBarangMutasiEdit.clear().draw();

            try {


                // === Ambil dan tampilkan gambar lama ===
                const gambarContainer = document.getElementById('gambarEdit-container');
                if (!gambarContainer) {
                    console.warn("Elemen #gambarEdit-container tidak ditemukan di DOM");
                    return;
                }
                gambarContainer.innerHTML = ''; // kosongkan dulu

                const responseGambar = await fetch(`get_gambar_mutasi.php?id_ba=${id_ba}`);
                if (!responseGambar.ok) throw new Error('Gagal ambil data gambar');
                const gambarList = await responseGambar.json();

                gambarList.forEach(img => {
                    const wrapper = document.createElement('div');
                    wrapper.className = 'gambarEdit-wrapper d-flex flex-column gap-2 mb-3 p-2 rounded-3 bg-light';

                    // === Hidden input ID gambar lama ===
                    const inputId = document.createElement('input');
                    inputId.type = 'hidden';
                    inputId.name = 'gambar_lama_id[]';
                    inputId.value = img.id;

                    // === Preview gambar lama ===
                    const preview = document.createElement('img');
                    preview.src = img.file_path;
                    preview.alt = "Gambar lampiran";
                    preview.style.maxWidth = '300px';
                    preview.style.border = '1px solid #ccc';
                    preview.style.borderRadius = '5px';
                    preview.style.cursor = 'pointer';

                    // Klik preview untuk membuka gambar penuh
                    preview.onclick = () => window.open(img.file_path, '_blank');

                    // === Input file untuk ganti gambar ===
                    const inputFile = document.createElement('input');
                    inputFile.type = 'file';
                    inputFile.name = `gambar_lama_file[${img.id}]`;
                    inputFile.accept = 'image/*';

                    // Tombol hapus gambar
                    const btnHapus = document.createElement('button');
                    btnHapus.type = 'button';
                    btnHapus.innerHTML = '<i class="bi bi-trash3-fill"></i>';
                    btnHapus.className = 'btn btn-danger mt-1';
                    btnHapus.onclick = () => {
                        // Tambahkan hidden input untuk menandai gambar lama akan dihapus
                        const inputHapus = document.createElement('input');
                        inputHapus.type = 'hidden';
                        inputHapus.name = 'hapus_gambar[]';
                        inputHapus.value = img.id; // id gambar lama
                        wrapper.appendChild(inputHapus);

                        // Hapus elemen preview dari DOM
                        wrapper.classList.add('d-none');
                    };

                    wrapper.appendChild(inputId);
                    wrapper.appendChild(preview);
                    wrapper.appendChild(inputFile);
                    wrapper.appendChild(btnHapus);

                    gambarContainer.appendChild(wrapper);
                });

            } catch (err) {
                console.error('❌ Error saat load data edit:', err);
            }
        });
    });
});
</script>

<script>// fungsi input gambar form edit
// === Fungsi untuk menambahkan input gambar (FORM EDIT) ===
function tambahGambarEdit() {
    const container = document.getElementById('gambarEdit-container');

    const wrapper = document.createElement('div');
    wrapper.className = 'gambarEdit-wrapper';
    wrapper.style.position = 'relative';
    wrapper.style.display = 'flex';
    wrapper.style.flexDirection = 'column';
    wrapper.style.gap = '5px';
    wrapper.style.marginBottom = '1rem';

    const input = document.createElement('input');
    input.type = 'file';
    input.name = 'gambar_edit[]';
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

    // === Tombol Jepret Kamera (Edit) ===
    const logoJepret = document.createElement('i');
    logoJepret.className = 'bi bi-camera-fill';

    const jepretKamera = document.createElement('button');
    jepretKamera.type = 'button';
    jepretKamera.className = 'btn btn-secondary btn-lg';
    jepretKamera.style.marginTop = '5px';
    jepretKamera.style.width = 'max-content';
    jepretKamera.prepend(logoJepret);

    let currentCamera = "environment";

    // === Event kamera toggle ===
    jepretKamera.onclick = async function () {
        const existingVideo = wrapper.querySelector('video');
        const existingCapture = wrapper.querySelector('.btn-capture');
        const existingSwitch = wrapper.querySelector('.btn-switch');
        const existingBtnGroup = wrapper.querySelector('.btn-group-kamera');

        // kalau kamera sedang aktif → matikan
        if (existingVideo && existingVideo._stream) {
            existingVideo._stream.getTracks().forEach(track => track.stop());
            existingVideo.remove();
            if (existingCapture) existingCapture.remove();
            if (existingSwitch) existingSwitch.remove();
            if (existingBtnGroup) existingBtnGroup.remove();
            preview.style.display = "block";
            return;
        }

        // buat elemen video
        const video = document.createElement('video');
        video.autoplay = true;
        video.style.maxWidth = "300px";
        video.style.border = "1px solid #ccc";
        video.style.borderRadius = "5px";
        wrapper.insertBefore(video, preview);
        preview.style.display = "none";

        // ==== fungsi start kamera dengan memilih deviceId ====
        async function startCamera() {
            const devices = await navigator.mediaDevices.enumerateDevices();
            const cams = devices.filter(d => d.kind === "videoinput");

            // pilih kamera sesuai currentCamera
            let targetCam = null;
            if (currentCamera === "environment") {
                targetCam = cams.find(c => c.label.toLowerCase().includes("back") ||
                    c.label.toLowerCase().includes("rear"));
            } else {
                targetCam = cams.find(c => c.label.toLowerCase().includes("front"));
            }

            // fallback pakai kamera pertama
            if (!targetCam) targetCam = cams[0];

            try {
                const stream = await navigator.mediaDevices.getUserMedia({
                    video: { deviceId: targetCam.deviceId }
                });

                video.srcObject = stream;
                video._stream = stream;
            } catch (err) {
                alert("Gagal membuka kamera: " + err);
            }
        }

        // tombol switch kamera
        const btnSwitch = document.createElement('button');
        btnSwitch.className = "btn btn-warning btn-switch mt-0";
        btnSwitch.style.width = "max-content";

        const iconSwitch = document.createElement('i');
        iconSwitch.className = "bi bi-arrow-clockwise";
        btnSwitch.appendChild(iconSwitch);

        btnSwitch.onclick = async function () {
            // ganti mode
            currentCamera = currentCamera === "environment" ? "user" : "environment";

            // matikan kamera sebelumnya
            if (video._stream) {
                video._stream.getTracks().forEach(t => t.stop());
            }

            // mulai kamera baru
            startCamera();
        };

        // tombol ambil foto
        const btnCapture = document.createElement('button');
        btnCapture.textContent = "Ambil Foto";
        btnCapture.className = "btn btn-success btn-capture mt-0";
        btnCapture.style.width = "max-content";

        btnCapture.onclick = function () {
            const canvas = document.createElement('canvas');
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(video, 0, 0);

            if (video._stream) {
                video._stream.getTracks().forEach(track => track.stop());
            }

            video.remove();
            btnCapture.remove();
            btnSwitch.remove();

            preview.src = canvas.toDataURL("image/png");
            preview.style.display = "block";

            canvas.toBlob(function (blob) {
                const timestamp = Date.now();
                const nomorBA = document.getElementById('nomor_ba_edit').value || "NOBA";

                const tanggalBAraw = document.getElementById('tanggal_edit').value || "NOTGL";
                let tanggalBA = "NOTGL";

                if (tanggalBAraw.includes("-")) {
                    const [yyyy, mm, dd] = tanggalBAraw.split("-");
                    tanggalBA = `${dd}${mm}${yyyy}`;
                }

                const filename = `camera${nomorBA}BAM${tanggalBA}-${timestamp}.png`;

                const file = new File([blob], filename, { type: "image/png" });
                const dt = new DataTransfer();
                dt.items.add(file);
                input.files = dt.files;
            }, "image/png");
        };

        // === buat container sejajar ===
        const btnGroup = document.createElement('div');
        btnGroup.className = "d-flex gap-1 mt-0 btn-group-kamera";

        btnGroup.appendChild(btnSwitch);
        btnGroup.appendChild(btnCapture);

        wrapper.insertBefore(btnGroup, preview);

        // === mulai kamera pertama kali ===
        startCamera();
    };


    // === Tombol Hapus Gambar (Edit) ===
    const btnHapus = document.createElement('button');
    btnHapus.type = 'button';
    btnHapus.innerHTML = '<i class="bi bi-trash3-fill"></i>';
    btnHapus.className = 'btn btn-danger mt-1';
    btnHapus.onclick = function () {
        const videoAktif = wrapper.querySelector('video');
        if (videoAktif && videoAktif._stream) {
            videoAktif._stream.getTracks().forEach(track => track.stop());
        }
        container.removeChild(wrapper);
    };

    wrapper.appendChild(input);
    wrapper.appendChild(jepretKamera);
    wrapper.appendChild(preview);
    wrapper.appendChild(btnHapus);

    container.appendChild(wrapper);
}
</script>

<script>//Form Input
//Sistem tombol popup form input
document.addEventListener('DOMContentLoaded', function () {
    const open          = document.getElementById('tombolInputPopup');
    const close         = document.getElementById('tombolClosePopupInput');
    const box           = document.getElementById('popupBoxInput');
    const background    = document.getElementById('popupBGInput');

    open.addEventListener('click', function () {
        box.classList.add('aktifPopupInput');
        // box.classList.add('scale-in-center');
        // box.classList.remove('scale-out-center');
        background.classList.add('aktifPopupInput');
        // background.classList.add('fade-in');
        // background.classList.remove('fade-out');
    });

    close.addEventListener('click', function () {
        box.classList.remove('aktifPopupInput');
        // box.classList.remove('scale-in-center');
        // box.classList.add('scale-out-center');
        background.classList.remove('aktifPopupInput');
        // background.classList.remove('fade-in');
        // background.classList.add('fade-out');
    });

    background.addEventListener('click', function () {
        box.classList.remove('aktifPopupInput');
        // box.classList.remove('scale-in-center');
        // box.classList.add('scale-out-center');
        background.classList.remove('aktifPopupInput');
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
</script>

<script>// Detail
// === Popup Detail BA Mutasi ===
$(document).ready(function () {

    // === 1️⃣ Inisialisasi DataTables untuk tabel barang detail ===
    const tableDetail = $('#myTableDetail').DataTable({
        responsive: true,
        autoWidth: true,
        paging: false,
        scrollY: '200px',        
        scrollCollapse: true,
        info: false,
        searching: false,
        language: { url: "../assets/json/id.json" },
        columnDefs: [
            { targets: "_all", className: "text-start" },
            { targets: -1, orderable: false, className: "text-center" },
        ]
    });

    // === 2️⃣ Elemen popup dan field detail ===
    const openButtons   = document.querySelectorAll('.tombolDetailPopup');
    const close         = document.getElementById('tombolClosePopupDetail');
    const box           = document.getElementById('popupBoxDetail');
    const background    = document.getElementById('popupBG');

    const idDetail             = document.getElementById('id_detail');
    const tanggalDetail        = document.getElementById('tanggal_detail');
    const nomorBADetail        = document.getElementById('nomor_ba_detail');
    const lokasiAsalDetail     = document.getElementById('lokasi_asal_detail');
    const lokasiTujuanDetail   = document.getElementById('lokasi_tujuan_detail');
    const namaPengirimDetail   = document.getElementById('nama_pengirim_detail');
    const namaPenerimaDetail   = document.getElementById('nama_penerima_detail');
    const namaPenerima2Detail  = document.getElementById('nama_penerima2_detail');
    const keteranganDetail     = document.getElementById('keterangan_detail');
    const judulDetail          = document.getElementById('judul_detail');
    const pembuatDetail        = document.getElementById('pembuat_detail')

    const divGambarContainer   = document.querySelector('#popupBoxDetail .w-50.d-flex.border');

    // === 3️⃣ Fungsi bantu ===
    function bulanKeRomawi(bulan) {
        const romawi = ['I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII'];
        return romawi[bulan - 1] || '';
    }

    function formatTanggalIndo(tanggal) {
        if (!tanggal) return '-';
        const bulanIndo = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
        const d = new Date(tanggal);
        if (isNaN(d)) return tanggal;
        return `${d.getDate()} ${bulanIndo[d.getMonth()]} ${d.getFullYear()}`;
    }

    // === 4️⃣ Tombol buka popup ===
    openButtons.forEach(btn => {
        btn.addEventListener('click', async function (e) {
            e.preventDefault();

            const id            = this.dataset.id                   || '-';
            const tanggal       = this.dataset.tanggal              || '-';
            const nomorBA       = this.dataset.nomor_ba             || '-';
            const asal          = this.dataset.lokasi_asal          || '-';
            const tujuan        = this.dataset.lokasi_tujuan        || '-';
            const pengirim      = this.dataset.nama_pengirim        || '-';
            const pengirim2     = this.dataset.nama_pengirim2       || '-';
            const hrdPengirim  = this.dataset.nama_hrd_pengirim    || '-';
            const penerima1     = this.dataset.nama_penerima        || '-';
            const penerima2     = this.dataset.nama_penerima2       || '-';
            const hrdPenerima  = this.dataset.nama_hrd_penerima    || '-';
            const diketahui     = this.dataset.nama_diketahui       || '-';
            const pemeriksa1    = this.dataset.nama_pemeriksa1      || '-';
            const pemeriksa2    = this.dataset.nama_pemeriksa2      || '-';
            const penyetujui1   = this.dataset.nama_penyetujui1     || '-';
            const penyetujui2   = this.dataset.nama_penyetujui2     || '-';
            const penyetujui3   = this.dataset.nama_penyetujui3     || '-';
            const keterangan    = this.dataset.keterangan           || '-';
            const pembuat       = this.dataset.pembuat              || '-';
            const departemenDiketahui = this.dataset.departemen_diketahui||'-';

            // === Data aktor ===
            const actors = [
                { label: 'Pengirim',                nama: this.dataset.nama_pengirim        || '', status: this.dataset.approval1 || '' },
                { label: 'Pengirim 2',              nama: this.dataset.nama_pengirim2       || '', status: this.dataset.approval2 || '' },
                { label: 'Kasie HRD/GA Pengirim',   nama: this.dataset.nama_hrd_pengirim    || '', status: this.dataset.approval3 || '' },
                { label: 'Penerima',                nama: this.dataset.nama_penerima        || '', status: this.dataset.approval4 || '' },
                { label: 'Penerima 2',              nama: this.dataset.nama_penerima2       || '', status: this.dataset.approval5 || '' },
                { label: 'Kasie HRD/GA Penerima',   nama: this.dataset.nama_hrd_penerima    || '', status: this.dataset.approval6 || '' },
                { label: 'Dept.MIS',                nama: this.dataset.nama_diketahui       || '', status: this.dataset.approval7 || '' },
                { label: 'Pemeriksa',               nama: this.dataset.nama_pemeriksa1      || '', status: this.dataset.approval8 || '' },
                { label: 'Pemeriksa 2',             nama: this.dataset.nama_pemeriksa2      || '', status: this.dataset.approval9 || '' },
                { label: 'Penyetujui 1',            nama: this.dataset.nama_penyetujui1     || '', status: this.dataset.approval10|| '' },
                { label: 'Penyetujui 2',            nama: this.dataset.nama_penyetujui2     || '', status: this.dataset.approval11|| '' },
                { label: 'Penyetujui 3',            nama: this.dataset.nama_penyetujui3     || '', status: this.dataset.approval12|| '' },
            ];

            // Hanya tampilkan yang ada nama
            const activeActors = actors.filter(a => a.nama.trim() !== '');

            // === Bangun ulang tabel aktor ===
            const tabelAktor = document.querySelector('#popupBoxDetail .tabel-aktor');
            if (tabelAktor) {
                const thead = tabelAktor.querySelector('thead');
                const tbody = tabelAktor.querySelector('tbody');
                thead.innerHTML = '';
                tbody.innerHTML = '';

                // Baris 1: label
                const trHeader = document.createElement('tr');
                activeActors.forEach(a => {
                    const th = document.createElement('th');
                    th.textContent = a.label;
                    trHeader.appendChild(th);
                });
                thead.appendChild(trHeader);

                // Baris 2: nama
                const trNama = document.createElement('tr');
                activeActors.forEach(a => {
                    const td = document.createElement('td');
                    td.textContent = a.nama || '-';
                    trNama.appendChild(td);
                });

                // Baris 3: status
                const trStatus = document.createElement('tr');
                activeActors.forEach(a => {
                    const td = document.createElement('td');
                    const span = document.createElement('span');
                    const status = a.status == 1 ? 'Disetujui' : 'Menunggu';
                    span.textContent = status;

                    if (a.status == 1) {
                        span.className = 'border fw-bold bg-success-subtle border-success-subtle text-success';
                    } else {
                        span.className = 'border fw-bold bg-warning-subtle border-warning-subtle text-warning';
                    }

                    span.style.borderRadius = '6px';
                    span.style.padding = '6px 12px';
                    td.appendChild(span);
                    trStatus.appendChild(td);
                });

                tbody.appendChild(trNama);
                tbody.appendChild(trStatus);
            }

            // === Format tanggal & judul ===
            const tglObj = new Date(tanggal);
            const bulanRomawi = !isNaN(tglObj) ? bulanKeRomawi(tglObj.getMonth() + 1) : '';
            const tahun = !isNaN(tglObj) ? tglObj.getFullYear() : '';
            const tanggalFormat = formatTanggalIndo(tanggal);

            idDetail.textContent            = id;
            tanggalDetail.textContent       = tanggalFormat;
            nomorBADetail.textContent       = nomorBA;
            lokasiAsalDetail.textContent    = asal;
            lokasiTujuanDetail.textContent  = tujuan;
            namaPengirimDetail.textContent  = pengirim;
            namaPenerimaDetail.textContent  = penerima1;
            namaPenerima2Detail.textContent = penerima2;
            keteranganDetail.textContent    = keterangan;
            pembuatDetail.textContent       = pembuat;

            judulDetail.textContent = `Detail Data BA Mutasi ${nomorBA} Periode ${bulanRomawi}/${tahun}`;

            // === 5️⃣ Ambil data barang ===
            try {
                const resBarang = await fetch(`get_barang_mutasi.php?id_ba=${id}`);
                const dataBarang = await resBarang.json();
                tableDetail.clear();
                let no = 1;
                dataBarang.forEach(item => {
                    tableDetail.row.add([
                        no++,
                        item.pt_asal || '-',
                        item.po || '-',
                        item.sn || '-',
                        item.coa || '-',
                        item.kode_assets|| '-',
                        item.merk || '-',
                        item.user || '-'
                    ]);
                });
                tableDetail.draw(false);
            } catch (err) {
                console.error('Gagal memuat data barang:', err);
                tableDetail.clear().draw();
            }

            // === 6️⃣ Ambil data gambar ===
            try {
                const resGambar = await fetch(`get_gambar_mutasi.php?id_ba=${id}`);
                const dataGambar = await resGambar.json();
                divGambarContainer.innerHTML = '';

                if (dataGambar.length === 0) {
                    divGambarContainer.innerHTML = '<p class="text-center w-100 text-muted m-auto">Tidak ada gambar lampiran</p>';
                } else {
                    const grid = document.createElement('div');
                    grid.className = 'div-gambar-grid';
                    dataGambar.forEach(img => {
                        const imgEl = document.createElement('img');
                        imgEl.src = img.file_path;
                        imgEl.alt = 'Lampiran';
                        grid.appendChild(imgEl);
                    });
                    divGambarContainer.appendChild(grid);
                }
            } catch (err) {
                console.error('Gagal memuat gambar:', err);
                divGambarContainer.innerHTML = '<p class="text-danger text-center w-100">Gagal memuat gambar</p>';
            }

            // === 7️⃣ Tampilkan popup ===
            box.classList.add('aktifPopupDetail');
            background.classList.add('aktifPopupDetail');
        });
    });

    // === 8️⃣ Tutup popup ===
    close.addEventListener('click', function (e) {
        e.preventDefault();
        box.classList.remove('aktifPopupDetail');
        background.classList.remove('aktifPopupDetail');
    });
    background.addEventListener('click', function () {
        box.classList.remove('aktifPopupDetail');
        background.classList.remove('aktifPopupDetail');
    });
});
</script>

<script>// Form Edit
document.addEventListener('DOMContentLoaded', function () {
    const openButtons   = document.querySelectorAll('.tombolEditPopup');
    const close         = document.getElementById('tombolClosePopupEdit');
    const box           = document.getElementById('popupBoxEdit');
    const background    = document.getElementById('popupBGEdit');

    const idInput             = document.getElementById('id_edit');
    const tanggalInput        = document.getElementById('tanggal_edit');
    const nomorBAInput        = document.getElementById('nomor_ba_edit');
    const lokasiAsalInput     = document.getElementById('lokasi_asal_edit');
    const lokasiTujuanInput   = document.getElementById('lokasi_tujuan_edit');
    const namaPengirimInput   = document.getElementById('nama_pengirim_edit');
    const namaPengirim2Input  = document.getElementById('nama_pengirim2_edit');
    const namaPenerimaInput   = document.getElementById('nama_penerima_edit');
    const namaPenerima2Input  = document.getElementById('nama_penerima2_edit');
    const keteranganInput     = document.getElementById('keterangan_edit');

    // Function: only compare exact values (no normalization) and disable matching option on tujuan
    function updateLokasiTujuanEdit() {
        if (!lokasiAsalInput || !lokasiTujuanInput) return;
        const asalValue = (lokasiAsalInput.value || '').trim();

        // enable all first
        for (let option of lokasiTujuanInput.options) {
            option.disabled = false;
        }

        if (asalValue !== '') {
            for (let option of lokasiTujuanInput.options) {
                if ((option.value || '').trim() === asalValue) {
                    option.disabled = true;
                }
            }
            // if tujuan equals asal, reset tujuan so user chooses another
            if ((lokasiTujuanInput.value || '').trim() === asalValue) {
                lokasiTujuanInput.value = '';
            }
        }
    }

    openButtons.forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();

            // ---- set values (existing code) ----
            idInput.value            = this.dataset.id || '';
            tanggalInput.value       = this.dataset.tanggal || '';
            nomorBAInput.value       = this.dataset.nomor_ba || '';
            lokasiAsalInput.value    = this.dataset.lokasi_asal || '';
            lokasiTujuanInput.value  = this.dataset.lokasi_tujuan || '';
            namaPengirimInput.value  = this.dataset.nama_pengirim || '';
            namaPengirim2Input.value = this.dataset.nama_pengirim2|| '';
            namaPenerimaInput.value  = this.dataset.nama_penerima || '';
            namaPenerima2Input.value = this.dataset.nama_penerima2 || '';
            keteranganInput.value    = this.dataset.keterangan || '';
            // ------------------------------------

            const warningPending  = document.getElementById('warningPendingEdit');
            const warningApproval = document.getElementById('warningApprovalExist');

            fetch('cek_pending_mutasi.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'id_ba=' + encodeURIComponent(idInput.value)
            })
            .then(res => res.json())
            .then(res => {

                // reset dulu
                warningPending.style.display  = 'none';
                warningApproval.style.display = 'none';

                if (res.pending_edit === true) {
                    warningPending.style.display = 'block';
                    return;
                }

                if (res.approval_exist === true) {
                    warningApproval.style.display = 'block';
                }

            })
            .catch(() => {
                warningPending.style.display  = 'none';
                warningApproval.style.display = 'none';
            });

            // *** KUNCI: jalankan update setelah nilai di-set ***
            // jalankan synchronously agar opsi tujuan di-disable sebelum popup tampil
            updateLokasiTujuanEdit();

            // tampilkan popup
            box.classList.add('aktifPopupEdit');
            background.classList.add('aktifPopupEdit');
        });
    });

    close.addEventListener('click', e => {
        e.preventDefault();
        box.classList.remove('aktifPopupEdit');
        background.classList.remove('aktifPopupEdit');
    });

    background.addEventListener('click', () => {
        box.classList.remove('aktifPopupEdit');
        background.classList.remove('aktifPopupEdit');
    });
});
</script>

<script>
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

<script> // Dropdown cari karyawan pengirim & penerima
document.addEventListener('DOMContentLoaded', () => {

    // =========================================================
    // Elemen tombol & dropdown
    // =========================================================
    const btnCariPengirim   = document.getElementById('btnCariPengirim');
    const btnCariPengirim2   = document.getElementById('btnCariPengirim2');
    const btnCariPenerima   = document.getElementById('btnCariPenerima');
    const btnCariPenerima2  = document.getElementById('btnCariPenerima2');

    const dropdownPengirim  = document.getElementById('dropdownPengirim');
    const dropdownPengirim2 = document.getElementById('dropdownPengirim2');
    const dropdownPenerima  = document.getElementById('dropdownPenerima');
    const dropdownPenerima2 = document.getElementById('dropdownPenerima2');

    const penerima2Wrapper  = document.getElementById('penerima2Wrapper');


    // =========================================================
    // Fungsi: Ambil data karyawan berdasarkan lokasi
    // =========================================================
    async function cariKaryawan(lokasi, role = "") {
        if (!lokasi) {
            alert('Pilih lokasi terlebih dahulu!');
            return [];
        }

        try {
            const response = await fetch(`get_karyawan.php?lokasi=${encodeURIComponent(lokasi)}&role=${role}`);
            if (!response.ok) throw new Error(`HTTP ${response.status}`);

            const data = await response.json();
            console.log('Data karyawan diterima:', data);
            return data;

        } catch (err) {
            console.error('Gagal fetch data:', err);
            alert('Gagal mengambil data karyawan.');
            return [];
        }
    }


    // =========================================================
    // Fungsi: Tampilkan dropdown hasil pencarian
    // =========================================================
    function tampilkanDropdown(list, dropdown, inputTarget) {
        // Buat header pencarian
        dropdown.innerHTML = `
            <div class="p-2 border-bottom">
                <input 
                    type="text"
                    class="form-control form-control-sm"
                    placeholder="Cari karyawan..."
                    id="searchInput${dropdown.id}">
            </div>
        `;
        dropdown.classList.add('dropdown-menu', 'custom-dropdown-scroll');

        const searchInput = dropdown.querySelector(`#searchInput${dropdown.id}`);
        searchInput.addEventListener('input', () => {
            const keyword = searchInput.value.toLowerCase();
            dropdown.querySelectorAll('.dropdown-item').forEach(item => {
                item.style.display = item.textContent.toLowerCase().includes(keyword)
                    ? 'block' : 'none';
            });
        });

        // Isi daftar karyawan
        if (list.length === 0) {
            dropdown.innerHTML += `
                <span class="dropdown-item text-muted">Tidak ada hasil</span>
            `;
        } else {
            list.forEach(karyawan => {
                const item = document.createElement('button');
                item.type = 'button';
                item.className = 'dropdown-item text-start';
                item.textContent = `${karyawan.nama} - ${karyawan.posisi} (${karyawan.departemen})`;

                item.addEventListener('click', () => {
                    inputTarget.value = karyawan.nama;
                    dropdown.classList.remove('show');
                });

                dropdown.appendChild(item);
            });
        }

        dropdown.classList.add('show');
    }


    // =========================================================
    // Event: Tombol cari pengirim
    // =========================================================
    btnCariPengirim.addEventListener('click', async () => {
        const lokasi = document.getElementById('lokasi_asal').value;
        const dataKaryawan = await cariKaryawan(lokasi, "ktu");
        tampilkanDropdown(dataKaryawan, dropdownPengirim, document.getElementById('nama_pengirim'));
    });

    // =========================================================
    // Event: Tombol cari pengirim 2 (filter agar tidak sama)
    // =========================================================

    btnCariPengirim2.addEventListener('click',async()=>{
        const lokasi = document.getElementById('lokasi_asal').value;
        const pengirim1 = document.getElementById('nama_pengirim').value.trim().toLowerCase();

        const dataKaryawan = await cariKaryawan(lokasi, "gm");
        const filtered = dataKaryawan.filter(k => k.nama.toLowerCase() !== pengirim1);

        tampilkanDropdown(filtered, dropdownPengirim2, document.getElementById('nama_pengirim2'));
    });

    // =========================================================
    // Event: Tombol cari penerima
    // =========================================================
    btnCariPenerima.addEventListener('click', async () => {
        const lokasi = document.getElementById('lokasi_tujuan').value;
        const dataKaryawan = await cariKaryawan(lokasi, "ktu");
        tampilkanDropdown(dataKaryawan, dropdownPenerima, document.getElementById('nama_penerima'));
    });


    // =========================================================
    // Event: Tombol cari penerima 2 (filter agar tidak sama)
    // =========================================================
    btnCariPenerima2.addEventListener('click', async () => {
        const lokasi = document.getElementById('lokasi_tujuan').value;
        const penerima1 = document.getElementById('nama_penerima').value.trim().toLowerCase();

        const dataKaryawan = await cariKaryawan(lokasi, "gm");
        const filtered = dataKaryawan.filter(k => k.nama.toLowerCase() !== penerima1);

        tampilkanDropdown(filtered, dropdownPenerima2, document.getElementById('nama_penerima2'));
    });


    // =========================================================
    // Tutup dropdown jika klik di luar area
    // =========================================================
    document.addEventListener('click', e => {
        const targets = [
            { btn: btnCariPengirim,  drop: dropdownPengirim  },
            { btn: btnCariPengirim2, drop: dropdownPengirim2 },
            { btn: btnCariPenerima,  drop: dropdownPenerima  },
            { btn: btnCariPenerima2, drop: dropdownPenerima2 }
        ];

        targets.forEach(({ btn, drop }) => {
            if (!drop.contains(e.target) && e.target !== btn) {
                drop.classList.remove('show');
            }
        });
    });

});
</script>

<script> // Dropdown cari karyawan pengirim & penerima (edit)

document.addEventListener('DOMContentLoaded', () => {

    const btnCariPengirimEdit   = document.getElementById('btnCariPengirimEdit');
    const btnCariPengirim2Edit  = document.getElementById('btnCariPengirim2Edit');
    const btnCariPenerimaEdit   = document.getElementById('btnCariPenerimaEdit');
    const btnCariPenerima2Edit  = document.getElementById('btnCariPenerima2Edit');

    const dropdownPengirimEdit  = document.getElementById('dropdownPengirimEdit');
    const dropdownPengirim2Edit = document.getElementById('dropdownPengirim2Edit');
    const dropdownPenerimaEdit  = document.getElementById('dropdownPenerimaEdit');
    const dropdownPenerima2Edit = document.getElementById('dropdownPenerima2Edit');

    // Ambil data karyawan berdasarkan lokasi
    async function cariKaryawan(lokasi, role = "") {
        if (!lokasi) {
            alert('Pilih lokasi terlebih dahulu!');
            return [];
        }

        try {
            const response = await fetch(`get_karyawan.php?lokasi=${encodeURIComponent(lokasi)}&role=${role}`);
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            return await response.json();
        } catch (err) {
            console.error('Gagal fetch data:', err);
            alert('Gagal mengambil data karyawan.');
            return [];
        }
    }

    // Tampilkan dropdown hasil pencarian
    function tampilkanDropdown(list, dropdown, inputTarget) {
        dropdown.innerHTML = `
            <div class="p-2 border-bottom">
                <input type="text" class="form-control form-control-sm" placeholder="Cari karyawan..." id="search_${dropdown.id}">
            </div>
        `;
        dropdown.classList.add('dropdown-menu', 'custom-dropdown-scroll');

        const searchInput = dropdown.querySelector(`#search_${dropdown.id}`);
        searchInput.addEventListener('input', () => {
            const keyword = searchInput.value.toLowerCase();
            dropdown.querySelectorAll('.dropdown-item').forEach(item => {
                item.style.display = item.textContent.toLowerCase().includes(keyword) ? 'block' : 'none';
            });
        });

        if (list.length === 0) {
            dropdown.innerHTML += `<span class="dropdown-item text-muted">Tidak ada hasil</span>`;
        } else {
            list.forEach(karyawan => {
                const item = document.createElement('button');
                item.type = 'button';
                item.className = 'dropdown-item text-start';
                item.textContent = `${karyawan.nama} - ${karyawan.posisi} (${karyawan.departemen})`;
                item.addEventListener('click', () => {
                    inputTarget.value = karyawan.nama;
                    dropdown.classList.remove('show');
                });
                dropdown.appendChild(item);
            });
        }

        dropdown.classList.add('show');
    }

    // Tombol Cari Pengirim (edit)
    btnCariPengirimEdit.addEventListener('click', async () => {
        const lokasi = document.getElementById('lokasi_asal_edit').value;
        const data = await cariKaryawan(lokasi, "ktu");
        tampilkanDropdown(data, dropdownPengirimEdit, document.getElementById('nama_pengirim_edit'));
    });

    // Tombol Cari Pengirim 2 (edit)
    btnCariPengirim2Edit.addEventListener('click', async () => {
        const lokasi = document.getElementById('lokasi_asal_edit').value;
        const pengirim1 = document.getElementById('nama_pengirim_edit').value.trim().toLowerCase();

        const data = await cariKaryawan(lokasi, "gm");
        const filtered = data.filter(k => k.nama.toLowerCase() !== pengirim1);
        tampilkanDropdown(data, dropdownPengirim2Edit, document.getElementById('nama_pengirim2_edit')); 
    })

    // Tombol Cari Penerima (edit)
    btnCariPenerimaEdit.addEventListener('click', async () => {
        const lokasi = document.getElementById('lokasi_tujuan_edit').value;
        const data = await cariKaryawan(lokasi, "ktu");
        tampilkanDropdown(data, dropdownPenerimaEdit, document.getElementById('nama_penerima_edit'));
    });

    // Tombol Cari Penerima 2 (edit)
    btnCariPenerima2Edit.addEventListener('click', async () => {
        const lokasi = document.getElementById('lokasi_tujuan_edit').value;
        const penerima1 = document.getElementById('nama_penerima_edit').value.trim().toLowerCase();

        const data = await cariKaryawan(lokasi, "gm");
        const filtered = data.filter(k => k.nama.toLowerCase() !== penerima1);
        tampilkanDropdown(filtered, dropdownPenerima2Edit, document.getElementById('nama_penerima2_edit'));
    });

    // Tutup dropdown saat klik di luar area
    document.addEventListener('click', e => {
        [dropdownPengirimEdit, dropdownPengirim2Edit, dropdownPenerimaEdit, dropdownPenerima2Edit].forEach(drop => {
            if (!drop.contains(e.target)) drop.classList.remove('show');
        });
    });
});
</script>

<script> 
document.addEventListener('DOMContentLoaded', function() {
    const lokasiAsal = document.getElementById('lokasi_asal');
    const lokasiTujuan = document.getElementById('lokasi_tujuan');

    // Cegah lokasi tujuan sama dengan asal
    lokasiAsal.addEventListener('change', function() {
        const asalValue = lokasiAsal.value;

        if (lokasiTujuan.value === asalValue) {
            lokasiTujuan.value = '';
        }

        for (let option of lokasiTujuan.options) {
            option.disabled = (option.value === asalValue && option.value !== '');
        }
    });
});

document.addEventListener('DOMContentLoaded', function() {
    const lokasiAsal = document.getElementById('lokasi_asal_edit');
    const lokasiTujuan = document.getElementById('lokasi_tujuan_edit');

    if (!lokasiAsal || !lokasiTujuan) return;

    // Fungsi untuk update opsi tujuan
    function updateTujuanOptions() {
        const asalValue = lokasiAsal.value;

        if (lokasiTujuan.value === asalValue) {
            lokasiTujuan.value = '';
        }

        for (let option of lokasiTujuan.options) {
            option.disabled = (option.value === asalValue && option.value !== '');
        }
    }

    // Jalankan langsung saat halaman edit dibuka (agar data lama langsung aktif)
    updateTujuanOptions();

    // Jalankan juga kalau user mengubah lokasi asal
    lokasiAsal.addEventListener('change', updateTujuanOptions);
});
</script>

<script>//Menghilangkan alert
        const alert = document.querySelector('.infoin-approval');
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

<script>//DataTables
    $(document).ready(function () {
        $('#myTable').DataTable({
        responsive: true,
        autoWidth: false,
        language: {
            url: "../assets/json/id.json"
        },

        columnDefs: [
            { targets: "_all", className: "text-start" } // bootstrap text-start = rata kiri
            ,
            { targets: -1, orderable: false, className: "text-center" }, // Kolom Actions tidak bisa di-sort
        ],

        initComplete: function () {
            // Sembunyikan skeleton
            $('#tableSkeleton').fadeOut(200, function () {
                $('#tabelUtama').fadeIn(200);
            });
        }
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
