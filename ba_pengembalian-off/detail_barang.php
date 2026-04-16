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

$showDataAkunMenu = false;

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
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Form BA Kerusakan</title>

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
      margin-bottom: 10px;
    }

    .app-main{
      display: flex;
      align-items: center;
    }

    .detail-pengembalian td,.gambar-pengembalian td{
        width: 75%;
    }
    .detail-pengembalian th,.gambar-pengembalian th{
        width: 25%;
    }

    th,td,table tbody tr td .btn-sm{
        font-size: 1rem;
    }

    .table-wrapper{
        width: 95%;
        height: auto;
        overflow-x: auto;
        margin: 20px 0;
        border-radius: 10px;
        padding: 10px;
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

    <!--Awal::Sidebar-->
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
                <a href="ba_pengembalian.php" class="nav-link" aria-disabled="true">
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
            <!-- <li class="nav-item">
                <a href="../ba_mutasi/ba_mutasi.php" class="nav-link">
                <i class="nav-icon bi bi-newspaper"></i>
                <p>
                    BA Mutasi
                </p>
                </a>
            </li> -->
            <?php if ($_SESSION['hak_akses'] === 'Admin'): ?>
            <li class="nav-header">
                USER
            </li>
            <!-- <li class="nav-item">
                <a href="../personal/status.php" class="nav-link">
                <i class="nav-icon bi bi-clipboard2-fill"></i>
                <p>
                    Status Approval BA
                </p>
                </a>
            </li> -->
            <?php endif; ?>
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
    <!--Akhir::Sidebar-->

    <?php
    require_once '../koneksi.php';

    if (!isset($_GET['id'])) {
        echo "ID tidak ditemukan.";
        exit;
    }

    $id = intval($_GET['id']);

    // Ambil data utama dari berita_acara_pengembalian
    $query = "SELECT * FROM berita_acara_pengembalian WHERE id = $id";
    $result = $koneksi->query($query);

    if ($result->num_rows == 0) {
        echo "Data tidak ditemukan.";
        exit;
    }

    $data = $result->fetch_assoc();

    $queryPengembalian = "
        SELECT 
            bap.id,  
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
        WHERE bap.id = $id
    ";

    $resultPengembalian = $koneksi->query($queryPengembalian);
    $peran = $resultPengembalian->fetch_assoc();

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

    // Ambil data barang yang terkait
    $barang = [];
    $sql_barang = "SELECT * FROM barang_pengembalian WHERE ba_pengembalian_id = $id";
    $res_barang = $koneksi->query($sql_barang);
    while ($row = $res_barang->fetch_assoc()) {
        $barang[] = $row;
    }

    // Ambil gambar yang terkait
    $gambar = [];
    $sql_gambar = "SELECT * FROM gambar_ba_pengembalian WHERE ba_pengembalian_id = $id";
    $res_gambar = $koneksi->query($sql_gambar);
    while ($row = $res_gambar->fetch_assoc()) {
        $gambar[] = $row;
    }
    ?>
    <main class="app-main">
        <section class="table-wrapper bg-white">
        <div class=" d-flex align-items-center justify-content-center position-relative">
            <div class=" position-absolute" style="left: 0;">
                <a class='btn btn-primary btn-sm' href='surat_output.php?id=<?= $data['id'] ?>' target='_blank'><i class="bi bi-file-earmark-text-fill"></i></a>
                <?php if ($data['approval_1'] != 1 && $data['approval_2'] != 1 && $data['approval_3'] != 1 && $_SESSION['nama'] === $data['nama_pembuat']): ?>
                <a class='btn btn-warning btn-sm' href='form_edit_ba_pengembalian.php?id=<?= $data['id'] ?>'><i class="bi bi-feather"></i></a>
                <a class='btn btn-danger btn-sm' href='delete.php?id=<?= $row['id'] ?> ' onclick="return confirm('Apakah Anda yakin ingin menghapus data ini?')"><i class="bi bi-x-lg"></i></a>
                <?php endif; ?>
            </div>
            
            <h2 class="">Detail Data Pengembalian <?= htmlspecialchars($data['nomor_ba']) ?> Periode <?php echo formatTanggalRomawi($data['tanggal']); ?></h2>
        </div>
        <table class="table w-25 table-approval" style="min-width: 50px;">
            <thead>
                <tr>
                    <th>Pengembali</th>
                    <th>Penerima</th>
                    <th>Yang Mengetahui</th>
                </tr>
            </thead>
            <tbody>
                        <tr>
                            <td><?php echo $peran['jabatan_aprv1'] . " " . $peran['departemen_aprv1'] ?></td>
                            <td><?php echo $peran['jabatan_aprv2'] . " " . $peran['departemen_aprv2'] ?></td>
                            <td><?php echo $peran['jabatan_aprv3'] . " " . $peran['departemen_aprv3'] ?></td>
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
                        </tr>
                        
            </tbody>
        </table>

        <table class="table table-bordered table-striped detail-pengembalian">
            <tbody>
                <!-- <tr>
                    <th>Status: PIHAK 1/PIHAK 2/Yang Mengetahui</th>
                    <td>
                        <span class="border fw-bold <?= $data['approval_1'] == 1 ? 'bg-success-subtle border-success-subtle text-success' : 'bg-warning-subtle border-warning-subtle text-warning' ?>" style="border-radius: 6px; padding: 6px 12px;">
                            <?= htmlspecialchars($data['approval_1'] == 1 ? 'Disetujui' : 'Menunggu') ?>
                        </span> 
                        /
                        <span class="border fw-bold <?= $data['approval_2'] == 1 ? 'bg-success-subtle border-success-subtle text-success' : 'bg-warning-subtle border-warning-subtle text-warning' ?>" style="border-radius: 6px; padding: 6px 12px;">
                            <?= htmlspecialchars($data['approval_2'] == 1 ? 'Disetujui' : 'Menunggu') ?>
                        </span>
                        /
                        <span class="border fw-bold <?= $data['approval_3'] == 1 ? 'bg-success-subtle border-success-subtle text-success' : 'bg-warning-subtle border-warning-subtle text-warning' ?>" style="border-radius: 6px; padding: 6px 12px;">
                            <?= htmlspecialchars($data['approval_3'] == 1 ? 'Disetujui' : 'Menunggu') ?>
                        </span>
                    </td>
                </tr> -->
                <tr>
                    <th>Nomor BA</th>
                    <td>
                        <?= htmlspecialchars($data['nomor_ba']) ?>
                    </td>
                </tr>
                <tr>
                    <th>Tanggal Surat</th>
                    <td><?php echo formatTanggal(date('Y-m-d', strtotime($data['tanggal']))); ?></td>
                </tr>
                <tr>
                    <th>Lokasi Pengembali</th>
                    <td><?= htmlspecialchars($data['lokasi_pengembali']) ?></td>
                </tr>
                <tr>
                    <th>Nama Pengembali</th>
                    <td><?= htmlspecialchars($data['nama_pengembali']) ?></td>
                </tr>
                <tr>
                    <th>Atasan Pengembali</th>
                    <td><?= htmlspecialchars($data['atasan_pengembali']) ?></td>
                </tr>
                <tr>
                    <th>Lokasi Penerima</th>
                    <td><?= htmlspecialchars($data['lokasi_penerima']) ?></td>
                </tr>
                <tr>
                    <th>Nama Penerima</th>
                    <td><?= htmlspecialchars($data['nama_penerima']) ?></td>
                </tr>
                <tr>
                    <th>Atasan Penerima</th>
                    <td><?= htmlspecialchars($data['atasan_penerima']) ?></td>
                </tr>

                

                
            </tbody>
        </table>
        
<!-- Bagian Detail Barang -->
<h3>Barang Pengembalian</h3>
<table class="table table-bordered table-striped">
    <thead>
        <tr>
            <th>No</th>
            <th>Jenis Barang</th>
            <th>Jumlah</th>
            <th>Kondisi</th>
            <th>Keterangan</th>
        </tr>
    </thead>
    <tbody>
        <?php if (count($barang) > 0): ?>
            <?php foreach ($barang as $index => $item): ?>
                <tr>
                    <td><?= $index + 1 ?></td>
                    <td><?= htmlspecialchars($item['jenis_barang']) ?></td>
                    <td><?= htmlspecialchars($item['jumlah']) ?></td>
                    <td><?= htmlspecialchars($item['kondisi']) ?></td>
                    <td><?= htmlspecialchars($item['keterangan']) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="6">Tidak ada data barang.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<table class="table table-bordered table-striped gambar-pengembalian">
    <tbody>
        <tr>
            <th>Gambar Lampiran</th>
            <td>
                <?php if (count($gambar) > 0): ?>
                <div class="gambar-lampiran d-flex flex-wrap">
                    <?php foreach ($gambar as $g): ?>
                        <img style="width:48%;object-fit:contain;margin:5px;" src="<?= htmlspecialchars($g['file_path']) ?>" alt="Gambar BA">
                    <?php endforeach; ?>
                <?php else: ?>
                            <p>Tidak ada gambar. <a style="text-decoration:none;" href='form_edit_ba_pengembalian.php?id=<?= $data['id'] ?>'>Input gambar?</a></p>
                    </div>
                <?php endif; ?>
            </td>
        </tr>
    </tbody>
</table>
<!-- Bagian Gambar Lampiran -->
</section>
    </main>

        <!--Awal::Footer Content-->
            <footer class="custom-footer d-flex position-relative p-2" style="border-top: whitesmoke solid 1px; box-shadow: 0px 7px 10px black; color: grey;">
                <p class="position-absolute" style="right: 15px;bottom:7px;color: grey;"><strong>Version </strong>1.1.0</p>
            <p class="pt-2 ps-1"><strong>Copyright &copy 2025</p></strong><p class="pt-2 ps-1 fw-bold text-primary">MIS MSAL.</p><p class="pt-2 ps-1"> All rights reserved</p>
            </footer>
        <!--Akhir::Footer Content-->

    </div>
    


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
