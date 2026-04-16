<?php
session_start();
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
  <title>Detail BA Notebook</title>

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
                <a href="ba_kerusakan.php" class="nav-link">
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

    // Query untuk ambil data BA dan data laptop berdasarkan SN
    $query = "
    SELECT 
        * 
    FROM ba_serah_terima_notebook 
    WHERE id = ?
    ";

    $stmt = $koneksi->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows < 1) {
        echo "Data tidak ditemukan.";
        exit;
    }

    $data = $result->fetch_assoc();

    // Query untuk ambil data peran
    $queryNotebook = "
        SELECT 
            ban.id, 
            ban.nama_peminjam, 
            ban.saksi,
            k2.jabatan AS jabatan_aprv2,
            k2.departemen AS departemen_aprv2,
            k3.jabatan AS jabatan_aprv3,
            k3.departemen AS departemen_aprv3
        FROM ba_serah_terima_notebook ban
        LEFT JOIN data_karyawan k2 
            ON ban.nama_peminjam = k2.nama
        LEFT JOIN data_karyawan k3 
            ON ban.saksi = k3.nama
        WHERE ban.id = $id
    ";

    $resultNotebook = $koneksi->query($queryNotebook);
    $peran = $resultNotebook->fetch_assoc();
    ?>
    <?php
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
    <main class="app-main">


    <section class="table-wrapper bg-white position-relative overflow-hidden">
        
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

        <div class=" d-flex align-items-center justify-content-center position-relative">
            <div class=" position-absolute" style="left: 0;">
                <a class='btn btn-primary btn-sm' href='surat_output.php?id=<?= $data['id'] ?>' target='_blank'><i class="bi bi-file-earmark-text-fill"></i></a>
                <?php
                    $sessionUser = isset($_SESSION['nama']) ? $_SESSION['nama'] : '';

                    $namaAprv1 = isset($data['pertama']) ? trim($data['pertama']) : '';
                    $namaAprv2 = isset($data['nama_peminjam']) ? trim($data['nama_peminjam']) : '';
                    $namaAprv3 = isset($data['saksi']) ? trim($data['saksi']) : '';
                    $namaAprv4 = isset($data['diketahui']) ? trim($data['diketahui']) : '';
                    $approval1 = isset($data['approval_1']) ? intval($data['approval_1']) : 0;
                    $approval2 = isset($data['approval_2']) ? intval($data['approval_2']) : 0;
                    $approval3 = isset($data['approval_3']) ? intval($data['approval_3']) : 0;
                    $approval4 = isset($data['approval_4']) ? intval($data['approval_4']) : 0;

                    $approvalField = '';
                    $tampilTombol = false;
                    $jenis_ba = 'notebook';

                    if ($sessionUser !== '') {
                        if ($sessionUser === $namaAprv1 && $approval1 === 0) {
                            $approvalField = 'approval_1';
                            $tampilTombol = true;
                        } elseif ($sessionUser === $namaAprv2 && $approval2 === 0) {
                            $approvalField = 'approval_2';
                            $tampilTombol = true;
                        } elseif ($sessionUser === $namaAprv3 && $approval3 === 0) {
                            $approvalField = 'approval_3';
                            $tampilTombol = true;
                        } elseif ($sessionUser === $namaAprv4 && $approval4 === 0) {
                            $approvalField = 'approval_4';
                            $tampilTombol = true;
                        }
                    }

                    if ($tampilTombol && $approvalField !== '') {
                        echo '<a href="#"
                                class="btn btn-success btn-sm ms-1 tombolSetuju"
                                data-id="' . intval($data['id']) . '"
                                data-approval="' . $approvalField . '"
                                data-jenis="' . $jenis_ba . '">
                                <i class="bi bi-check-circle"></i>
                            </a>';
                    }
                ?>
                <?php if ($data['approval_1'] != 1 && $data['approval_2'] != 1 && $data['approval_3'] != 1 && $data['approval_4'] != 1 && $_SESSION['nama'] === $data['nama_pembuat']): ?>
                <a class='btn btn-warning btn-sm' href='form_edit.php?id=<?= $data['id'] ?>'><i class="bi bi-feather"></i></a>
                <a class='btn btn-danger btn-sm' href='delete.php?id=<?= $row['id'] ?> ' onclick="return confirm('Apakah Anda yakin ingin menghapus data ini?')"><i class="bi bi-x-lg"></i></a>
                <?php endif; ?>
            </div>
            
            <h2 class="">Detail Data Peminjaman Notebook <?= htmlspecialchars($data['nomor_ba']) ?> Periode <?php echo formatTanggalRomawi($data['tanggal']); ?></h2>
        </div>

        <table class="table table-approval" style="width:35%;min-width: 100px;">
            <thead>
                <tr>
                    <th>PIHAK 1</th>
                    <th>PIHAK 2</th>
                    <th>Saksi</th>
                    <th>Diketahui</th>
                </tr>
            </thead>
            <tbody>
                        <tr>
                            <td>Direksi MIS</td>
                            <td><?php echo $peran['jabatan_aprv2'] . " " . $peran['departemen_aprv2'] ?></td>
                            <td><?php echo $peran['jabatan_aprv3'] . " " . $peran['departemen_aprv3'] ?></td>
                            <td>Dept. Head HRGA</td>
                        </tr>
                        <tr>
                            <td>
                                <span class="border fw-bold <?= $data['approval_1'] == 1 ? 'bg-success-subtle border-success-subtle text-success' : 'bg-warning-subtle border-warning-subtle text-warning' ?>" style="border-radius: 6px; padding: 6px 12px;">
                                    <?= htmlspecialchars($data['approval_1'] == 1 ? 'Disetujui' : 'Menunggu') ?>
                                </span> 
                            </td>
                            <td>
                                <span class="border fw-bold <?= $data['approval_2'] == 1 ? 'bg-success-subtle border-success-subtle text-success' : 'bg-warning-subtle border-warning-subtle text-warning' ?>" style="border-radius: 6px; padding: 6px 12px;">
                                    <?= htmlspecialchars($data['approval_2'] == 1 ? 'Disetujui' : 'Menunggu') ?>
                                </span>
                            </td>
                            <td>
                                <span class="border fw-bold <?= $data['approval_3'] == 1 ? 'bg-success-subtle border-success-subtle text-success' : 'bg-warning-subtle border-warning-subtle text-warning' ?>" style="border-radius: 6px; padding: 6px 12px;">
                                    <?= htmlspecialchars($data['approval_3'] == 1 ? 'Disetujui' : 'Menunggu') ?>
                                </span>
                            </td>
                            <td>
                                <span class="border fw-bold <?= $data['approval_4'] == 1 ? 'bg-success-subtle border-success-subtle text-success' : 'bg-warning-subtle border-warning-subtle text-warning' ?>" style="border-radius: 6px; padding: 6px 12px;">
                                    <?= htmlspecialchars($data['approval_4'] == 1 ? 'Disetujui' : 'Menunggu') ?>
                                </span>
                            </td>
                        </tr>
                        
            </tbody>
        </table>

        <table class="table table-bordered table-striped">
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
                    <th>Nama Peminjam</th>
                    <td><?= htmlspecialchars($data['nama_peminjam']) ?></td>
                </tr>
                <tr>
                    <th>Serial Number</th>
                    <td><?= htmlspecialchars($data['sn']) ?></td>
                </tr>
                <tr>
                    <th>Merek</th>
                    <td><?= htmlspecialchars(isset($data['merek']) ? $data['merek'] : '-') ?></td>
                </tr>
                <tr>
                    <th>Prosesor</th>
                    <td><?= htmlspecialchars(isset($data['prosesor']) ? $data['prosesor'] : '-') ?></td>
                </tr>
                <tr>
                    <th>Penyimpanan</th>
                    <td><?= htmlspecialchars(isset($data['penyimpanan']) ? $data['penyimpanan'] : '-') ?></td>
                </tr>
                <tr>
                    <th>Monitor</th>
                    <td><?= htmlspecialchars(isset($data['monitor']) ? $data['monitor'] : '-') ?></td>
                </tr>
                <tr>
                    <th>Baterai</th>
                    <td><?= htmlspecialchars(isset($data['baterai']) ? $data['baterai'] : '-') ?></td>
                </tr>
                <tr>
                    <th>VGA</th>
                    <td><?= htmlspecialchars(isset($data['vga']) ? $data['vga'] : '-') ?></td>
                </tr>
                <tr>
                    <th>RAM</th>
                    <td><?= htmlspecialchars(isset($data['ram']) ? $data['ram'] : '-') ?></td>
                </tr>
            </tbody>
        </table>
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
