<?php
session_start();
include '../koneksi.php';

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

?>
<?php 
$ptSekarang = $_SESSION['pt'];
if (is_array($ptSekarang)) {
    $ptSekarang = reset($ptSekarang);
}
$ptSekarang = trim($ptSekarang);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Histori Edit</title>

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

    .app-sidebar {
        background: <?php echo $bgMenu; ?> !important;
    }

    .navbar {
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

    table.dataTable tbody tr:hover {
        background-color: #e9ecef;
        cursor: pointer;
    }

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
            
            <li class="nav-item">
                <a href="../ba_pengembalian/ba_pengembalian.php" class="nav-link" aria-disabled="true">
                    <i class="nav-icon bi bi-newspaper"></i>
                    <p>BA Pengembalian</p>
                </a>
            </li>
            <?php if ($ptSekarang == "PT.MSAL (HO)"){ ?>
            <!-- List BA Serah Terima -->
            <!-- <li class="nav-item">
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
            </li> -->
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
            
            <li class="nav-item">
                <a href="../personal/approval.php" class="nav-link">
                <i class="nav-icon bi bi-clipboard2-check"></i>
                <p class="">
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
    <main class="app-main">
        <div class="container m-0">
            <h2 class="text-center mb-4">Histori Perubahan Berita Acara</h2>

            <!-- FILTER -->
            <div class="row g-2 mb-3 align-items-center filter-row">
                <div class="row">
                    <h6 class="m-0">Filter Tabel</h6>
                </div>
                <div class="col-auto m-0">
                    <input type="text" id="filterNomor" class="form-control form-control-sm" placeholder="Nomor BA">
                </div>
                <div class="col-auto m-0">
                    <input type="date" id="filterTanggal" class="form-control form-control-sm">
                </div>
                <div class="col-auto m-0">
                    <select id="filterNamaBA" class="form-select form-select-sm">
                        <option value="">Semua Jenis BA</option>
                        <option value="mutasi">Mutasi</option>
                        <option value="kerusakan">Kerusakan</option>
                        <option value="pengembalian">Pengembalian</option>
                    </select>
                </div>
                <div class="col-auto m-0">
                    <select id="filterNoPO" class="form-select form-select-sm">
                        <option value="">Semua PT</option>
                        <option value="PT.MSAL (HO)">PT.MSAL (HO)</option>
                        <option value="PT.MSAL (SITE)">PT.MSAL (SITE)</option>
                    </select>
                </div>
                <div class="col-auto m-0">
                    <button id="resetFilter" class="btn btn-secondary btn-sm filter-btn">Reset</button>
                </div>
            </div>

            <!-- TABEL HISTORI -->
            <div class="table-responsive mt-0">
                <table id="tabelHistori" class="table table-striped table-bordered mt-0">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Tanggal BA</th>
                            <th>Nomor BA</th>
                            <th>Nama BA</th>
                            <th>PT</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $data_ba = [];
                        $query = mysqli_query($koneksi, "SELECT * FROM historikal_edit_ba WHERE pending_status = '0' ORDER BY tanggal_edit DESC");

                        if ($query && mysqli_num_rows($query) > 0) {
                            // Simpan histori tiap BA ke array $data_ba
                            while ($row = mysqli_fetch_assoc($query)) {
                                $id_ba   = $row['id_ba'];
                                $nama_ba = strtolower($row['nama_ba']);

                                switch ($nama_ba) {
                                    case 'mutasi':
                                        $q_ba = mysqli_query($koneksi, "SELECT nomor_ba, tanggal FROM berita_acara_mutasi WHERE id='$id_ba' LIMIT 1");
                                        $display_nama = 'Berita Acara Mutasi Aset';
                                        break;
                                    case 'kerusakan':
                                        $q_ba = mysqli_query($koneksi, "SELECT nomor_ba, tanggal FROM berita_acara_kerusakan WHERE id='$id_ba' LIMIT 1");
                                        $display_nama = 'Berita Acara Kerusakan';
                                        break;
                                    case 'pengembalian':
                                        $q_ba = mysqli_query($koneksi, "SELECT nomor_ba, tanggal FROM berita_acara_pengembalian WHERE id='$id_ba' LIMIT 1");
                                        $display_nama = 'Berita Acara Pengembalian';
                                        break;
                                    default:
                                        $q_ba = false;
                                        $display_nama = ucfirst($nama_ba);
                                }

                                $nomor_ba = '-';
                                $tanggal_ba = '-';
                                if ($q_ba && mysqli_num_rows($q_ba) > 0) {
                                    $ba_data = mysqli_fetch_assoc($q_ba);
                                    $nomor_ba = htmlspecialchars($ba_data['nomor_ba']);
                                    $tanggal_ba = !empty($ba_data['tanggal']) ? date('d-m-Y', strtotime($ba_data['tanggal'])) : '-';
                                }

                                $key = $nama_ba . ';' . $id_ba;
                                $data_ba[$key][] = [
                                    'histori_edit' => $row['histori_edit'],
                                    'pengedit' => $row['pengedit'],
                                    'tanggal_edit' => date('d-m-Y H:i:s', strtotime($row['tanggal_edit']))
                                ];
                            }

                            // Render tabel
                            $no = 1;
                            foreach ($data_ba as $key => $histories) {
                                list($nama_ba, $id_ba) = explode(';', $key);

                                switch ($nama_ba) {
                                    case 'mutasi':
                                        $q_ba = mysqli_query($koneksi, "SELECT nomor_ba, tanggal, pt_asal FROM berita_acara_mutasi WHERE id='$id_ba' LIMIT 1");
                                        $display_nama = 'Berita Acara Mutasi Aset';
                                        break;
                                    case 'kerusakan':
                                        $q_ba = mysqli_query($koneksi, "SELECT nomor_ba, tanggal, pt FROM berita_acara_kerusakan WHERE id='$id_ba' LIMIT 1");
                                        $display_nama = 'Berita Acara Kerusakan';
                                        break;
                                    case 'pengembalian':
                                        $q_ba = mysqli_query($koneksi, "SELECT nomor_ba, tanggal, lokasi_penerima FROM berita_acara_pengembalian WHERE id='$id_ba' LIMIT 1");
                                        $display_nama = 'Berita Acara Pengembalian';
                                        break;
                                    case 'st_asset':
                                        $q_ba = mysqli_query($koneksi, "SELECT nomor_ba, tanggal, pt FROM ba_serah_terima_asset WHERE id='$id_ba' LIMIT 1");
                                        $display_nama = 'Berita Acara Serah Terima Penggunaan Aset Inventaris';
                                        break;
                                    default:
                                        $q_ba = false;
                                        $display_nama = ucfirst($nama_ba);
                                }

                                $nomor_ba = '-';
                                $tanggal_ba = '-';
                                $pt_ba = '-';
                                if ($q_ba && mysqli_num_rows($q_ba) > 0) {
                                    $ba_data = mysqli_fetch_assoc($q_ba);
                                    $nomor_ba = htmlspecialchars($ba_data['nomor_ba']);
                                    if($nama_ba === 'mutasi'){
                                    $pt_ba = htmlspecialchars($ba_data['pt_asal']);
                                        if($pt_ba === 'PT.MSAL (HO)'){
                                            $pt_ba2 = 'PT Mulia Sawit Agro Lestari (HO)';
                                        }elseif($pt_ba === 'PT.MSAL (SITE)'){
                                            $pt_ba2 = 'PT Mulia Sawit Agro Lestari (SITE)';
                                        }
                                    }
                                    elseif($nama_ba === 'kerusakan'){
                                    $pt_ba = htmlspecialchars($ba_data['pt']);
                                        if($pt_ba === 'PT.MSAL (HO)'){
                                            $pt_ba2 = 'PT Mulia Sawit Agro Lestari (HO)';
                                        }elseif($pt_ba === 'PT.MSAL (SITE)'){
                                            $pt_ba2 = 'PT Mulia Sawit Agro Lestari (SITE)';
                                        }
                                    } elseif($nama_ba === 'st_asset'){
                                    $pt_ba = htmlspecialchars($ba_data['pt']);
                                        if($pt_ba === 'PT.MSAL (HO)'){
                                            $pt_ba2 = 'PT Mulia Sawit Agro Lestari (HO)';
                                        }elseif($pt_ba === 'PT.MSAL (SITE)'){
                                            $pt_ba2 = 'PT Mulia Sawit Agro Lestari (SITE)';
                                        }
                                    }
                                    
                                    $tanggal_ba = !empty($ba_data['tanggal']) ? date('d-m-Y', strtotime($ba_data['tanggal'])) : '-';
                                    
                                }

                                $histori_json_b64 = htmlspecialchars(base64_encode(json_encode($histories, JSON_UNESCAPED_UNICODE)), ENT_QUOTES, 'UTF-8');
                                $id_ba_attr = (int)$id_ba;

                                echo "<tr class='row-ba' data-idba='{$id_ba_attr}' data-namaba='{$nama_ba}' data-histori-b64='{$histori_json_b64}'>";
                                echo "<td>{$no}</td>";
                                echo "<td>{$tanggal_ba}</td>";
                                echo "<td>{$nomor_ba}</td>";
                                echo "<td>{$display_nama}</td>";
                                echo "<td>{$pt_ba2}</td>";
                                echo "</tr>";

                                $no++;
                            }

                        } else {
                            echo "<tr><td colspan='5' class='text-center'>Belum ada histori perubahan</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>

        </div>

        <!-- MODAL HISTORI -->
        <div class="modal fade" id="modalHistori" tabindex="-1" aria-labelledby="modalHistoriLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalHistoriLabel">Detail Histori BA</h5>
                        <button type="button" class="btn btn-danger btn-sm" data-bs-dismiss="modal" aria-label="Tutup">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        <table class="table table-bordered" id="tabelModalHistori">
                            <thead>
                                <tr>
                                    <th>Riwayat Perubahan</th>
                                    <th>Pengedit</th>
                                    <th>Tanggal Edit</th>
                                </tr>
                            </thead>
                            <tbody id="modalBodyHistori"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- TEMPLATE MODAL -->
        <div id="modalHistoriTemplate" class="modal fade" tabindex="-1">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Detail Histori BA</h5>
                        <button type="button" class="btn btn-danger btn-sm" data-bs-dismiss="modal">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        <table class="table table-bordered" id="tabelModalHistori">
                            <thead>
                                <tr>
                                    <th>Riwayat Perubahan</th>
                                    <th>Pengedit</th>
                                    <th>Tanggal Edit</th>
                                </tr>
                            </thead>
                            <tbody id="modalBodyHistori"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>

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
                            <?php while ($row = $resultWarna->fetch_assoc()): ?>
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

<script>
    $(document).ready(function () {

        // ============================================================
        // 🔹 Inisialisasi DataTable Utama
        // ============================================================
        const table = $('#tabelHistori').DataTable({
            responsive: true,
            autoWidth: true,
            language: { url: "../assets/json/id.json" },
            scrollY: "450px",
            scrollCollapse: true,
            paging: true,
            order: [[0, "asc"]]
        });

        // ============================================================
        // 🔹 Fungsi Format Tanggal (YYYY-MM-DD → DD-MM-YYYY)
        // ============================================================
        function formatDateToDMy(isoDate) {
            if (!isoDate) return '';
            const parts = isoDate.split('-');
            return parts.length === 3 ? `${parts[2]}-${parts[1]}-${parts[0]}` : '';
        }

        // ============================================================
        // 🔹 Filter DataTable Utama
        // ============================================================
        $('#filterNomor').on('keyup', function () {
            table.column(2).search(this.value).draw();
        });

        $('#filterTanggal').on('change', function () {
            table.column(1).search(formatDateToDMy(this.value)).draw();
        });

        $('#filterNamaBA').on('change', function () {
            const val = this.value ? this.value.toLowerCase() : '';
            table.column(3).search(val, true, false).draw();
        });

        $('#filterNoPO').on('change', function () {
            const val = this.value;
            table.column(4).search(val).draw();
        });


        $('#resetFilter').on('click', function () {
            $('#filterNomor, #filterTanggal, #filterNamaBA, #filterNoPO').val('');
            table.search('').columns().search('').draw();
        });

        // ============================================================
        // 🔹 Klik Row → Tampilkan Modal Histori
        // ============================================================
        $(document).on('click', '#tabelHistori tbody tr.row-ba', function () {
            const historiB64 = $(this).attr('data-histori-b64');
            if (!historiB64) return;

            try {
                const historiJson = atob(historiB64);
                const data = JSON.parse(historiJson);

                // Bangun isi tabel modal
                let html = '';
                data.forEach(item => {
                    const perubahanList = item.histori_edit
                        .split(';')
                        .map(p => p.trim())
                        .filter(Boolean)
                        .join('<br>');

                    html += `
                        <tr>
                            <td>${perubahanList}</td>
                            <td>${item.pengedit}</td>
                            <td>${item.tanggal_edit}</td>
                        </tr>
                    `;
                });

                // Isi konten modal
                $('#modalBodyHistori').html(html);

                // Reset dan re-init DataTable di modal
                if ($.fn.DataTable.isDataTable('#tabelModalHistori')) {
                    $('#tabelModalHistori').DataTable().destroy();
                }

                $('#tabelModalHistori').DataTable({
                    paging: false,
                    searching: false,
                    info: false,
                    ordering: false,
                    scrollY: "300px",
                    scrollCollapse: true
                });

                // Bersihkan modal lama jika masih ada
                const modalEl = document.getElementById('modalHistori');
                const existingModal = bootstrap.Modal.getInstance(modalEl);
                if (existingModal) {
                    existingModal.hide();
                    modalEl.remove();
                }

                // Clone modal baru dari template
                const newModal = $('#modalHistoriTemplate')
                    .clone()
                    .prop('id', 'modalHistori')
                    .appendTo('body');

                $('#modalBodyHistori', newModal).html(html);

                // Tampilkan modal baru
                new bootstrap.Modal(newModal[0]).show();

            } catch (err) {
                console.error('Gagal parse histori:', err);
            }
        });
    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const open = document.getElementById('personaliaBtn');
        const box = document.getElementById('popupBoxPersonalia');
        const background = document.getElementById('popupBG');

        open.addEventListener('click', function() {
            box.classList.add('aktifPopup');
            background.classList.add('aktifPopup');
            box.classList.add('scale-in-center');
            box.classList.remove('scale-out-center');
            background.classList.add('fade-in');
            background.classList.remove('fade-out');
        });

        background.addEventListener('click', function() {
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
        const alert = document.querySelector('.infoin-approval');
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