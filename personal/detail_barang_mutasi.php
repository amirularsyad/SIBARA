<?php
session_start();
require_once "../koneksi.php"; // pastikan file koneksi mengisi $koneksi

// Jika belum login, arahkan ke halaman login
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../login_registrasi.php");
    exit();
}

// Validasi ID dari query string
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID tidak valid.");
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

$id_ba = (int) $_GET['id'];

// Ambil data utama berita_acara_mutasi
$stmt = $koneksi->prepare("SELECT * FROM berita_acara_mutasi WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $id_ba);
$stmt->execute();
$res = $stmt->get_result();
$ba = $res->fetch_assoc();
$stmt->close();

if (!$ba) {
    die("Data Berita Acara Mutasi tidak ditemukan.");
}

// Ambil daftar barang terkait
$stmtb = $koneksi->prepare("SELECT * FROM barang_mutasi WHERE id_ba = ? ORDER BY id ASC");
$stmtb->bind_param("i", $id_ba);
$stmtb->execute();
$result_barang = $stmtb->get_result();
$stmtb->close();

// Ambil gambar terkait
$stmti = $koneksi->prepare("SELECT * FROM gambar_ba_mutasi WHERE id_ba = ? ORDER BY id ASC");
$stmti->bind_param("i", $id_ba);
$stmti->execute();
$result_gambar = $stmti->get_result();
$stmti->close();

// Helper untuk format tanggal (Indonesia)
function formatTanggal($tanggal) {
    if (empty($tanggal)) return '-';
    $ts = strtotime($tanggal);
    if ($ts === false) return htmlspecialchars($tanggal);

    // Daftar bulan dalam Bahasa Indonesia
    $bulanIndo = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret',
        4 => 'April', 5 => 'Mei', 6 => 'Juni',
        7 => 'Juli', 8 => 'Agustus', 9 => 'September',
        10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    ];

    $tgl = date('d', $ts);
    $bln = $bulanIndo[(int)date('n', $ts)];
    $thn = date('Y', $ts);

    return $tgl . ' ' . $bln . ' ' . $thn;
}


// Helper tampil approval (menampilkan apa adanya)
function tampilApproval($val) {
    if ($val === null || $val === '') return '-';
    return htmlspecialchars($val);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Detail BA Mutasi</title>

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

    <!-- DataTables -->
    <link 
        rel="icon" type="image/png" 
        href="../assets/css/datatables.min.css"
    />

    <link 
        rel="stylesheet" 
        href="../assets/css/datatables.min.css"
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
        height: max-content;

        margin: 20px 0;
        border-radius: 10px;
        padding: 10px;
    }

    th,td,table tbody tr td .btn-sm{
        font-size: 16px;
    }
    th{
        min-width: 100px;
    }

    .table-custom th{
        width: 25%;
        min-width: 300px;
    }

    .table-approval th,.table-approval td{
        font-size: .8rem;
        vertical-align: text-top;
    }

    .table-approval th,.table-approval td{
        border: none;
        padding: 5px;
    }
    .table-approval th{
        padding-bottom: 0;
    }
#myTableDetail {
    width: 100%;
    max-width: 100%;
    table-layout: fixed;      /* biar kolom proporsional */
    border-collapse: collapse;
}

#myTableDetail th,
#myTableDetail td {
    white-space: normal;      /* teks bisa turun ke bawah */
    word-break: break-word;   /* pecah kata panjang */
    padding: 6px 8px;
    vertical-align: middle;
    font-size: 12px;
}

/* Lebar proporsional per kolom (pakai persentase biar responsif) */
#myTableDetail th:nth-child(1),
#myTableDetail td:nth-child(1) { width: 7%; text-align: center; }   /* No */
#myTableDetail th:nth-child(2),
#myTableDetail td:nth-child(2) { width: 15%; }                      /* PT Asal */
#myTableDetail th:nth-child(3),
#myTableDetail td:nth-child(3) { width: 12%; }                      /* No PO */
#myTableDetail th:nth-child(4),
#myTableDetail td:nth-child(4) { width: 15%; }                      /* Serial Number */
#myTableDetail th:nth-child(5),
#myTableDetail td:nth-child(5) { width: 16%; }                      /* Jenis Perangkat */
#myTableDetail th:nth-child(6),
#myTableDetail td:nth-child(6) { width: 12%; }                      /* Merek */
#myTableDetail th:nth-child(7),
#myTableDetail td:nth-child(7) { width: 18%; }                      /* Pengguna */



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
        .custom-isi-data{
            overflow-x: auto;
            height: fit-content;
        }
    }
    @media (max-width: 1500px) {
        .custom-btn-title{
            flex-direction: column;
        }
        .custom-btn-ctr{
            align-self: flex-start;
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
            <!-- List BA Pengembalian -->
            <!-- <li class="nav-item">
                <a href="../ba_pengembalian/ba_pengembalian.php" class="nav-link">
                <i class="nav-icon bi bi-newspaper"></i>
                <p>
                    BA Pengembalian
                </p>
                </a>
            </li> -->

            <li class="nav-item">
                <a href="../ba_pemutihan/ba_pemutihan.php" class="nav-link" aria-disabled="true">
                    <i class="nav-icon bi bi-newspaper"></i>
                    <p>BA Pemutihan</p>
                </a>
            </li>

            <?php if ($ptSekarang == "PT.MSAL (HO)"){ ?>

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
<main class="app-main">
    <section class="table-wrapper bg-white position-relative">
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

        <div class="custom-btn-title d-flex align-items-center justify-content-between position-relative">
            <div class="custom-btn-ctr start-0" style="left: 0;">
                <a class='custom-btn btn btn-primary' href='surat_output_mutasi.php?id=<?= intval($ba['id']) ?>' target='_blank'><i class="bi bi-file-earmark-text-fill"></i></a>
                <?php
                $sessionUser = isset($_SESSION['nama']) ? $_SESSION['nama'] : '';

                $namaAprv1  = isset($ba['pengirim']) ? trim($ba['pengirim']) : '';
                $namaAprv2  = isset($ba['diketahui1']) ? trim($ba['diketahui1']) : '';
                $pengirim   = isset($ba['diketahui2']) ? trim($ba['diketahui2']) : '';
                $penerima   = isset($ba['penerima1']) ? trim($ba['penerima1']) : '';
                $penerima2  = isset($ba['penerima2']) ? trim($ba['penerima2']) : '';

                $approval1  = isset($ba['approval_1']) ? intval($ba['approval_1']) : 0;
                $approval2  = isset($ba['approval_2']) ? intval($ba['approval_2']) : 0;
                $approval3  = isset($ba['approval_3']) ? intval($ba['approval_3']) : 0;
                $approval4  = isset($ba['approval_4']) ? intval($ba['approval_4']) : 0;
                $approval5  = isset($ba['approval_5']) ? intval($ba['approval_5']) : 0;

                $tampilTombol = false;
                $approvalField = '';
                $jenis_ba = 'mutasi';

                if ($sessionUser !== '') {
                    if ($sessionUser === $namaAprv1 && $approval1 === 0) {
                        $tampilTombol = true;
                        $approvalField = 'approval_1';
                    } elseif ($sessionUser === $namaAprv2 && $approval2 === 0) {
                        $tampilTombol = true;
                        $approvalField = 'approval_2';
                    } elseif ($sessionUser === $pengirim && $approval3 === 0) {
                        $tampilTombol = true;
                        $approvalField = 'approval_3';
                    } elseif ($sessionUser === $penerima && $approval4 === 0) {
                        $tampilTombol = true;
                        $approvalField = 'approval_4';
                    } elseif ($sessionUser === $penerima2 && $approval5 === 0) {
                        $tampilTombol = true;
                        $approvalField = 'approval_5';
                    }
                }

                if ($tampilTombol && $approvalField !== '') {
                    echo '<a href="#" 
                            class="custom-btn btn btn-success ms-1 tombolSetuju d-none"
                            data-id="' . intval($ba['id']) . '" 
                            data-approval="' . $approvalField . '" 
                            data-jenis="' . $jenis_ba . '">
                            <i class="bi bi-check-circle"></i>
                        </a>';
                }
                ?>
                <a href="#" class="custom-btn btn btn-warning ms-1 tombolTandatangan d-none">
                    <i class="bi bi-pencil-square"></i>
                </a>

            </div>
            
            <h2 class="custom-h2">
                Detail Data Kerusakan Nomor <span id="nomor_ba"><?= htmlspecialchars($ba['nomor_ba']) ?></span>
                Periode <span id="periode_ba">
                    <?php
                    if (!empty($ba['tanggal'])) {
                        $bulan = date('n', strtotime($ba['tanggal']));
                        $romawi = [
                            1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV',
                            5 => 'V', 6 => 'VI', 7 => 'VII', 8 => 'VIII',
                            9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII'
                        ];
                        echo $romawi[$bulan];
                    } else {
                        echo '-';
                    }
                    ?>
                </span>
            </h2>
            <div class=""></div>
        </div>
        <div class="custom-isi-data d-flex justify-content-center flex-column gap-1 w-100">
            <table class="table table-approval" style="min-width: 100px; width: 600px;">
            <thead>
                <tr>
                    <th>Pengirim</th>
                    <th>Pengirim 2</th>
                    <th>Kasie HRD/GA Pengirim</th>
                    <th>Penerima</th>
                    <th>Penerima 2</th>
                    <th>Kasie HRD/GA Penerima</th>
                    <th>Diketahui</th>
                    <th>Dept HRD & GA</th>
                    <th>Div Accounting</th>
                    <th>Direktur HRD & GA</th>
                    <th>Direktur FA</th>
                </tr>
            </thead>
            <tbody>
                    <tr>
                        <td><?= htmlspecialchars($ba['pengirim1'] ?: '-') ?></td>
                        <td><?= htmlspecialchars($ba['pengirim2'] ?: '-') ?></td>
                        <td><?= htmlspecialchars($ba['hrd_ga_pengirim'] ?: '-') ?></td>
                        <td><?= htmlspecialchars($ba['penerima1'] ?: '-') ?></td>
                        <td><?= htmlspecialchars($ba['penerima2'] ?: '-') ?></td>
                        <td><?= htmlspecialchars($ba['hrd_ga_penerima'] ?: '-') ?></td>
                        <td><?= htmlspecialchars($ba['diketahui'] ?: '-') ?></td>
                        <td><?= htmlspecialchars($ba['pemeriksa1'] ?: '-') ?></td>
                        <td><?= htmlspecialchars($ba['pemeriksa2'] ?: '-') ?></td>
                        <td><?= htmlspecialchars($ba['penyetujui1'] ?: '-') ?></td>
                        <td><?= htmlspecialchars($ba['penyetujui2'] ?: '-') ?></td>
                    </tr>
                    <tr>
                        <td>
                            <span class="border fw-bold 
                                <?= $ba['approval_1'] == 1 ? 'bg-success-subtle border-success-subtle text-success' : 'bg-warning-subtle border-warning-subtle text-warning' ?>" 
                                style="border-radius: 6px; padding: 6px 12px;">
                                <?= $ba['approval_1'] == 1 ? 'Disetujui' : 'Menunggu' ?>
                            </span>
                        </td>
                        <td>
                            <span class="border fw-bold 
                                <?= $ba['approval_2'] == 1 ? 'bg-success-subtle border-success-subtle text-success' : 'bg-warning-subtle border-warning-subtle text-warning' ?>" 
                                style="border-radius: 6px; padding: 6px 12px;">
                                <?= $ba['approval_2'] == 1 ? 'Disetujui' : 'Menunggu' ?>
                            </span>
                        </td>
                        <td>
                            <span class="border fw-bold 
                                <?= $ba['approval_3'] == 1 ? 'bg-success-subtle border-success-subtle text-success' : 'bg-warning-subtle border-warning-subtle text-warning' ?>" 
                                style="border-radius: 6px; padding: 6px 12px;">
                                <?= $ba['approval_3'] == 1 ? 'Disetujui' : 'Menunggu' ?>
                            </span>
                        </td>
                        <td>
                            <span class="border fw-bold 
                                <?= $ba['approval_4'] == 1 ? 'bg-success-subtle border-success-subtle text-success' : 'bg-warning-subtle border-warning-subtle text-warning' ?>" 
                                style="border-radius: 6px; padding: 6px 12px;">
                                <?= $ba['approval_4'] == 1 ? 'Disetujui' : 'Menunggu' ?>
                            </span>
                        </td>
                        <td>
                            <span class="border fw-bold 
                                <?= $ba['approval_5'] == 1 ? 'bg-success-subtle border-success-subtle text-success' : 'bg-warning-subtle border-warning-subtle text-warning' ?>" 
                                style="border-radius: 6px; padding: 6px 12px;">
                                <?= $ba['approval_5'] == 1 ? 'Disetujui' : 'Menunggu' ?>
                            </span>
                        </td>
                        <td>
                            <span class="border fw-bold 
                                <?= $ba['approval_6'] == 1 ? 'bg-success-subtle border-success-subtle text-success' : 'bg-warning-subtle border-warning-subtle text-warning' ?>" 
                                style="border-radius: 6px; padding: 6px 12px;">
                                <?= $ba['approval_6'] == 1 ? 'Disetujui' : 'Menunggu' ?>
                            </span>
                        </td>
                        <td>
                            <span class="border fw-bold 
                                <?= $ba['approval_7'] == 1 ? 'bg-success-subtle border-success-subtle text-success' : 'bg-warning-subtle border-warning-subtle text-warning' ?>" 
                                style="border-radius: 6px; padding: 6px 12px;">
                                <?= $ba['approval_7'] == 1 ? 'Disetujui' : 'Menunggu' ?>
                            </span>
                        </td>
                        <td>
                            <span class="border fw-bold 
                                <?= $ba['approval_8'] == 1 ? 'bg-success-subtle border-success-subtle text-success' : 'bg-warning-subtle border-warning-subtle text-warning' ?>" 
                                style="border-radius: 6px; padding: 6px 12px;">
                                <?= $ba['approval_8'] == 1 ? 'Disetujui' : 'Menunggu' ?>
                            </span>
                        </td>
                        <td>
                            <span class="border fw-bold 
                                <?= $ba['approval_9'] == 1 ? 'bg-success-subtle border-success-subtle text-success' : 'bg-warning-subtle border-warning-subtle text-warning' ?>" 
                                style="border-radius: 6px; padding: 6px 12px;">
                                <?= $ba['approval_9'] == 1 ? 'Disetujui' : 'Menunggu' ?>
                            </span>
                        </td>
                        <td>
                            <span class="border fw-bold 
                                <?= $ba['approval_10'] == 1 ? 'bg-success-subtle border-success-subtle text-success' : 'bg-warning-subtle border-warning-subtle text-warning' ?>" 
                                style="border-radius: 6px; padding: 6px 12px;">
                                <?= $ba['approval_10'] == 1 ? 'Disetujui' : 'Menunggu' ?>
                            </span>
                        </td>
                        <td>
                            <span class="border fw-bold 
                                <?= $ba['approval_11'] == 1 ? 'bg-success-subtle border-success-subtle text-success' : 'bg-warning-subtle border-warning-subtle text-warning' ?>" 
                                style="border-radius: 6px; padding: 6px 12px;">
                                <?= $ba['approval_11'] == 1 ? 'Disetujui' : 'Menunggu' ?>
                            </span>
                        </td>

                    </tr>
            </tbody>
            </table>
            <div class="d-flex gap-2 h-100">
                <div class="w-50 d-flex flex-column" style="height: 200px;">
                    <table class="table-custom table table-bordered table-striped">
                        <tbody>

                            <tr>
                                <th>Nomor BA</th>
                                <td>
                                    <?= htmlspecialchars($ba['nomor_ba'] ?: '-') ?>
                                </td>
                            </tr>
                            
                            <tr>
                                <th>Tanggal</th>
                                <td><?= formatTanggal($ba['tanggal']) ?></td>
                            </tr>
                            <tr>
                                <th>Lokasi Asal</th>
                                <td><?= htmlspecialchars($ba['pt_asal'] ?: '-') ?></td>
                            </tr>
                            <tr>
                                <th>Lokasi TUjuan</th>
                                <td><?= htmlspecialchars($ba['pt_tujuan'] ?: '-') ?></td>
                            </tr>

                            
                        </tbody>
                    </table>
                    <div class="">
                        <table id="myTableDetail" class="table table-bordered table-striped">
                            <thead class="bg-secondary text-white">
                                <tr>
                                    <th>No</th>
                                    <th>PT Asal</th>
                                    <th>No PO</th>
                                    <th>Serial Number</th>
                                    <th>Jenis Perangkat</th>
                                    <th>Merek</th>
                                    <th>Pengguna</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $no = 1;
                                if ($result_barang && $result_barang->num_rows > 0):
                                    while ($rowBarang = $result_barang->fetch_assoc()):
                                ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><?= htmlspecialchars($rowBarang['pt_asal'] ?: '-') ?></td>
                                    <td><?= htmlspecialchars($rowBarang['po'] ?: '-') ?></td>
                                    <td><?= htmlspecialchars($rowBarang['sn'] ?: '-') ?></td>
                                    <td><?= htmlspecialchars($rowBarang['coa'] ?: '-') ?></td>
                                    <td><?= htmlspecialchars($rowBarang['merk'] ?: '-') ?></td>
                                    <td><?= htmlspecialchars($rowBarang['user'] ?: '-') ?></td>
                                </tr>
                                <?php
                                    endwhile;
                                else:
                                ?>
                                <tr>
                                    <td colspan="7" class="text-center">Tidak ada data barang.</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                </div>
                <div class="w-50 d-flex border rounded-1 mb-1 overflow-auto p-2" style="height:525px;">
                    <?php
                    if ($result_gambar && $result_gambar->num_rows > 0) {
                        while ($g = $result_gambar->fetch_assoc()) {
                            $file = $g['file_path'];
                            // pastikan path apa adanya sesuai catatan (../assets/...)
                            echo '<div class="me-2 mb-2" style="min-width:150px;">';
                            echo '<img src="'.htmlspecialchars($file).'" alt="bukti" style="max-width:100%;height:auto;border-radius:6px;display:block;">';
                            echo '</div>';
                        }
                    } else {
                        echo '<div class="text-muted">Tidak ada gambar.</div>';
                    }
                    ?>
                </div>
            </div>
        </div>
        
        
    </section>
</main>

<script src="../assets/js/jquery-3.7.1.min.js"></script>
<script src="../assets/js/datatables.min.js"></script>

<!-- Bootstrap 5 -->
<script src="../assets/bootstrap-5.3.6-dist/js/bootstrap.min.js"></script>

<!-- popperjs Bootstrap 5 -->
<script src="../assets/js/popper.min.js"></script>

<!-- AdminLTE -->
<script src="../assets/adminlte/js/adminlte.js"></script>

<!-- OverlayScrollbars -->
<script src="../assets/js/overlayscrollbars.browser.es6.min.js"></script>

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

<script>//DataTables
    $(document).ready(function () {
        $('#myTableDetail').DataTable({
        responsive: true,
        autoWidth: false,
        paging: false,
        info: false,
        searching: false,
        language: { url: "../assets/json/id.json" },
        columnDefs: [
            { targets: "_all", className: "text-start" },
            { targets: -1, orderable: false, className: "text-center" },
        ]
        });
    });
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
