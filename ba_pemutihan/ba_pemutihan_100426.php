<?php
session_start();

// Jika belum login, arahkan ke halaman login
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../login_registrasi.php");
    exit();
}

// Jika login tapi bukan Admin, arahkan ke halaman approval
if (!isset($_SESSION['hak_akses']) || ($_SESSION['hak_akses'] !== 'Admin' && $_SESSION['hak_akses'] !== 'Super Admin')) {
    header("Location: ../personal/approval.php");
    exit();
}

// setup akses
include '../koneksi.php';

// ======================
// SUPPORT MULTI PT USER
// ======================
$pt_raw = isset($_SESSION['pt']) ? $_SESSION['pt'] : '';
$pt_list = array();

if (is_array($pt_raw)) {
    foreach ($pt_raw as $p) {
        $p = trim($p);
        if ($p !== '') {
            $pt_list[] = $p;
        }
    }
} else {
    $p = trim($pt_raw);
    if ($p !== '') {
        $pt_list[] = $p;
    }
}

$pt_default = (count($pt_list) > 0) ? $pt_list[0] : '';
$is_multi_pt = (count($pt_list) > 1);
$is_user_ho = in_array('PT.MSAL (HO)', $pt_list, true);

// Mapping PT -> id_pt 
$pt_map = array(
    'PT.MSAL (HO)'    => 1,
    'PT.MSAL (PKS)'   => 2,
    'PT.MSAL (SITE)'  => 3,
    'PT.PSAM (PKS)'   => 4,
    'PT.PSAM (SITE)'  => 5,
    'PT.MAPA'         => 6,
    'PT.PEAK (PKS)'   => 7,
    'PT.PEAK (SITE)'  => 8,
    'RO PALANGKARAYA' => 9,
    'RO SAMPIT'       => 10,
    'PT.WCJU (SITE)'  => 11,
    'PT.WCJU (PKS)'   => 12
);

$pt_map_rev = array();
foreach ($pt_map as $k => $v) {
    $pt_map_rev[(string) $v] = $k;
}

$manajemen_akun_akses = 0;
$warna_menu = null;
if (isset($_SESSION['nama'])) {
    $namaLogin = $_SESSION['nama'];
    $sqlAkses = "SELECT manajemen_akun_akses, warna_menu FROM akun_akses WHERE nama = ? LIMIT 1";
    if ($stmt = $koneksi->prepare($sqlAkses)) {
        $stmt->bind_param("s", $namaLogin);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $rowAkses = $res->fetch_assoc()) {
            $manajemen_akun_akses = (int) $rowAkses['manajemen_akun_akses'];
            $warna_menu = $rowAkses['warna_menu'];
            $_SESSION['manajemen_akun_akses'] = $manajemen_akun_akses;
        }
        $stmt->close();
    }
}

$showDataAkunMenu = false;
if (isset($_SESSION['hak_akses']) && $_SESSION['hak_akses'] === 'Super Admin') {
    $showDataAkunMenu = true;
} else {
    if ($manajemen_akun_akses === 1 || $manajemen_akun_akses === 2) {
        $showDataAkunMenu = true;
    }
}

// Warna Menu
if ($warna_menu == "0" || $warna_menu === "" || is_null($warna_menu)) {
    $bgMenu = 'linear-gradient(to bottom right, #3e02be 0%, rgb(1, 64, 159) 50%, rgb(2, 59, 159) 100%)';
} else {
    $bgMenu = $warna_menu;
}

// Warna Navbar
if ($warna_menu == "0" || $warna_menu === "" || is_null($warna_menu)) {
    $bgNav = 'linear-gradient(to bottom right, #3e02be 0%, rgb(1, 64, 159) 50%, rgb(2, 59, 159) 100%)';
} else {
    $bgNav = $warna_menu;
}

$jumlah_approval_notif = require '../approval_notification_badge.php';

$ptSekarang = isset($_SESSION['pt']) ? $_SESSION['pt'] : '';
if (is_array($ptSekarang)) {
    $ptSekarang = reset($ptSekarang);
}
$ptSekarang = trim($ptSekarang);

if (isset($_GET['pt']) && $_GET['pt'] !== '' && $_GET['pt'] !== 'all') {
    $pt_session_query = trim($_GET['pt']);
} else {
    $pt_session_query = $pt_default;
}

// ======================
// TABEL UTAMA
// ======================
$filter_pt = isset($_GET['pt']) ? $_GET['pt'] : '';
$filter_tahun = isset($_GET['tahun']) ? $_GET['tahun'] : '';
$filter_bulan = isset($_GET['bulan']) ? $_GET['bulan'] : '';

$where_clauses = array();
$params = array();
$types = '';

$pt_filter = $pt_default;

if (!empty($filter_pt) && $filter_pt !== 'all') {
    $where_clauses[] = "bap.pt = ?";
    $params[] = $filter_pt;
    $types .= 's';
} elseif ($filter_pt === 'all') {
    if (!(isset($_SESSION['hak_akses']) && $_SESSION['hak_akses'] === 'Admin' && $is_user_ho)) {
        if (count($pt_list) > 0) {
            $placeholders = implode(',', array_fill(0, count($pt_list), '?'));
            $where_clauses[] = "bap.pt IN ($placeholders)";
            foreach ($pt_list as $p) {
                $params[] = $p;
                $types .= 's';
            }
        } else {
            $where_clauses[] = "1=0";
        }
    }
} else {
    if (count($pt_list) > 1) {
        $placeholders = implode(',', array_fill(0, count($pt_list), '?'));
        $where_clauses[] = "bap.pt IN ($placeholders)";
        foreach ($pt_list as $p) {
            $params[] = $p;
            $types .= 's';
        }
    } else {
        $where_clauses[] = "bap.pt = ?";
        $params[] = $pt_filter;
        $types .= 's';
    }
}

if (!empty($filter_tahun) && $filter_tahun !== 'all') {
    $where_clauses[] = "YEAR(bap.tanggal) = ?";
    $params[] = $filter_tahun;
    $types .= 's';
}

if (!empty($filter_bulan) && $filter_bulan !== 'all') {
    $where_clauses[] = "MONTH(bap.tanggal) = ?";
    $params[] = $filter_bulan;
    $types .= 's';
}

$where_clauses[] = "bap.dihapus = 0";

$where_sql = '';
if (!empty($where_clauses)) {
    $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
}

$query = "SELECT bap.*
          FROM berita_acara_pemutihan bap
          $where_sql
          ORDER BY bap.tanggal DESC, bap.nomor_ba DESC";

$stmt = $koneksi->prepare($query);
if (!empty($params)) {
    $bind_names = array();
    $bind_names[] = $types;
    for ($i = 0; $i < count($params); $i++) {
        $bind_name = 'bind' . $i;
        $$bind_name = $params[$i];
        $bind_names[] = &$$bind_name;
    }
    call_user_func_array(array($stmt, 'bind_param'), $bind_names);
}
$stmt->execute();
$result = $stmt->get_result();

// ======================
// NOMOR BA BARU
// ======================
$tanggal_hari_ini = date('Y-m-d');
$bulan_ini = date('m');
$tahun_ini = date('Y');
$pt_nomor_ba = $pt_default;

if (!empty($pt_nomor_ba)) {
    $stmt2 = $koneksi->prepare("SELECT nomor_ba
                                FROM berita_acara_pemutihan
                                WHERE MONTH(tanggal) = ?
                                AND YEAR(tanggal) = ?
                                AND pt = ?
                                AND dihapus = 0
                                ORDER BY CAST(nomor_ba AS UNSIGNED) DESC
                                LIMIT 1");
    $stmt2->bind_param("sss", $bulan_ini, $tahun_ini, $pt_nomor_ba);
    $stmt2->execute();
    $result2 = $stmt2->get_result();
    $row2 = $result2->fetch_assoc();

    if ($row2 && is_numeric($row2['nomor_ba'])) {
        $last_nomor = (int) $row2['nomor_ba'];
        $nomor_ba_baru = str_pad($last_nomor + 1, 3, '0', STR_PAD_LEFT);
    } else {
        $nomor_ba_baru = '001';
    }
    $stmt2->close();
} else {
    $nomor_ba_baru = '001';
}

// // ======================
// // DATA KARYAWAN HO MIS STAF
// // ======================
// $data_karyawan_ho = array();
// $query_ho = $koneksi->query("SELECT nama, posisi, departemen, jabatan
//                              FROM data_karyawan
//                              WHERE jabatan IN ('Dept. Head', 'Sect. Head', 'AVP', 'CEO', 'Direktur', 'VICE CEO') AND dihapus = 0
//                              ORDER BY nama ASC");
// if ($query_ho) {
//     while ($row_ho = $query_ho->fetch_assoc()) {
//         $data_karyawan_ho[] = $row_ho;
//     }
// }

// ======================
// DATA DEPT HEAD HO
// ======================
$data_karyawan_ho = array();
$query_ho = $koneksi->query("SELECT nama, departemen, jabatan, posisi
                             FROM data_karyawan
                             WHERE jabatan = 'Dept. Head'
                             AND departemen IN ('HRO', 'MIS')
                             AND dihapus = 0
                             ORDER BY departemen ASC, nama ASC");
if ($query_ho) {
    while ($row_ho = $query_ho->fetch_assoc()) {
        $data_karyawan_ho[] = $row_ho;
    }
}

// ======================
// DATA KARYAWAN NON HO
// ======================
$data_karyawan_test = array();
$query_test = $koneksi->query("SELECT nama, posisi, pt
                               FROM data_karyawan_test
                               ORDER BY nama ASC");
if ($query_test) {
    while ($row_test = $query_test->fetch_assoc()) {
        $data_karyawan_test[] = $row_test;
    }
}

// ======================
// DATA BARANG: ambil sesuai SEMUA PT user
// ======================
$id_pt_list = array();
foreach ($pt_list as $p) {
    if (isset($pt_map[$p])) {
        $id_pt_list[] = (int)$pt_map[$p];
    }
}
$id_pt_sql = (count($id_pt_list) > 0) ? implode(',', $id_pt_list) : '0';

$query_assets2 = "
SELECT 
    tb_assets.id_pt,
    tb_assets.id_assets,
    tb_assets.no_po,
    tb_assets.serial_number,
    tb_assets.merk AS asset_merk,
    tb_assets.tgl_pembelian,
    tb_assets.user,
    tb_assets.harga,
    tb_qty_assets.category AS coa
FROM tb_assets
LEFT JOIN tb_qty_assets ON tb_assets.qty_id = tb_qty_assets.id_qty
WHERE tb_assets.id_pt IN ($id_pt_sql)
ORDER BY tb_qty_assets.category ASC, tb_assets.id_assets ASC
";

$result_assets2 = $koneksi2->query($query_assets2);

// ======================
// DATA JSON UNTUK JS
// ======================
$pt_map_json = json_encode($pt_map);
$pt_list_json = json_encode($pt_list);
$data_karyawan_ho_json = json_encode($data_karyawan_ho);
$data_karyawan_test_json = json_encode($data_karyawan_test);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta charset="UTF-8">
    <title>BA Pemutihan</title>

    <link rel="stylesheet" href="../assets/bootstrap-5.3.6-dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="../assets/icons/icons-main/font/bootstrap-icons.min.css" />
    <link rel="stylesheet" href="../assets/adminlte/css/adminlte.css" />
    <link rel="stylesheet" href="../assets/css/overlayscrollbars.min.css" />
    <link rel="icon" type="image/png" href="../assets/img/logo.png" />
    <link rel="stylesheet" href="../assets/css/datatables.min.css" />

    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f9f9f9;
        }

        .app-wrapper {
            position: relative;
        }

        .custom-main {
            overflow-y: hidden !important;
        }

        #date {
            margin-right: 10px;
        }

        #clock {
            font-size: 16px;
            color: white;
            margin-right: 20px;
        }

        .akun-info {
            right: -300px;
            opacity: 0;
        }

        .aktif {
            right: 0;
            opacity: 1;
            transition: all .3s ease-in-out;
        }

        .display-state {
            display: none;
        }

        .aktifLT {
            display: flex;
        }

        .app-sidebar {
            background: <?php echo $bgMenu; ?> !important;
        }

        .navbar {
            background: <?php echo $bgNav; ?> !important;
        }

        h2,
        h3 {
            color: #2c3e50;
            text-align: center;
            margin-bottom: 25px;
        }

        .app-main {
            display: flex;
            align-items: center;
            margin-top: 40px;
        }

        .custom-form-penambahan {
            padding: 12px 12px 0 12px;
        }

        .table-wrapper {
            width: 97%;
            height: auto;
            overflow-x: auto;
            margin: 20px 0;
            border-radius: 10px;
            padding: 10px;
        }

        th,
        td,
        table tbody tr td .btn-sm {
            font-size: .8rem;
        }

        th,
        td {
            text-align: center !important;
            vertical-align: middle !important;
        }

        .popupInput,
        .popupEdit {
            width: 100%;
            padding: 25px 30px;
            border-radius: 10px;
        }

        #popupBoxInput,
        #popupBoxEdit,
        #popupBoxDetail,
        #popupBoxDataBarang {
            max-height: 78vh;
            overflow-y: auto;
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

        .popup-box,
        .popup-bg {
            display: none;
        }

        .aktifPopup {
            display: flex;
        }

        .table-approval th,
        .table-approval td {
            border: none;
            padding: 5px;
        }

        .dataTable {
            width: 100% !important;
        }

        .custom-gambar-detail {
            width: 49%;
        }

        .custom-selected-barang th,
        .custom-selected-barang td,
        .custom-selected-barang .btn-sm {
            font-size: .75rem;
        }

        .custom-selected-barang .empty-row {
            color: #888;
            font-style: italic;
        }

        .custom-image-item {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 10px;
            margin-bottom: 10px;
            background: #fff;
        }

        .custom-image-item img {
            max-width: 100%;
            height: auto;
            display: block;
            border-radius: 6px;
            border: 1px solid #ddd;
            margin-top: 8px;
        }

        .custom-footer {
            background-color: white;
        }

        .skeleton {
            height: 16px;
            width: 100%;
            background: linear-gradient(90deg, #e0e0e0 25%, #f5f5f5 37%, #e0e0e0 63%);
            background-size: 400% 100%;
            animation: skeleton-loading 1.4s ease infinite;
            border-radius: 4px;
        }

        .skeleton-header {
            height: 20px;
        }

        .custom-detail-approval-child tr td {
            font-size: .7rem;
        }

        @keyframes skeleton-loading {
            0% {
                background-position: 100% 0;
            }

            100% {
                background-position: -100% 0;
            }
        }

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

        .custom-detail-table-child {
            max-height: 200px !important;
            overflow-y: auto;
        }

        @media (min-width: 1025px) {
            .custom-main {
                height: calc(100vh - 130px);
            }
        }

        @media (max-width: 1024px) {
            #res-fullscreen {
                display: none;
            }

            .custom-footer {
                position: absolute !important;
                bottom: 0;
                width: 100vw;
            }

            .custom-main {
                padding-bottom: 100px;
                height: max-content;
                padding-top: 10px;
            }

            .custom-btn-action {
                padding: 6px 12px !important;
                font-size: 1rem !important;
            }

            .custom-gambar-detail {
                width: 100%;
            }
        }

        @media (max-width: 450px) {

            #date,
            #clock {
                display: none;
            }

            .custom-main {
                width: 100%;
            }

            #myTable_wrapper {
                width: 100%;
            }

            #myTable_wrapper .row:nth-child(2) {
                width: 100%;
                overflow-x: auto;
                max-height: 250px;
            }

            .custom-btn-input-history {
                flex-direction: column !important;
                right: 10px !important;
                left: auto !important;
            }

            .custom-footer p {
                font-size: 10px;
            }

            .custom-detail-container {
                flex-direction: column;
                width: 100%;
            }

            .custom-detail-approval,
            .custom-detail-table,
            .custom-detail-gambar,
            .custom-detail-histori {
                width: 100% !important;
                overflow-x: auto;
            }

            .custom-popup-box-delete {
                width: 100vw !important;
            }
        }

        .bi-list,
        .bi-arrows-fullscreen,
        .bi-fullscreen-exit {
            color: #fff !important;
        }
    </style>
</head>

<body class="layout-fixed sidebar-expand-lg bg-body-tertiary overflow-x-hidden">
    <script src="../assets/js/jquery-3.7.1.min.js"></script>
    <script src="../assets/js/datatables.min.js"></script>

    <div class="app-wrapper">
        <nav class="app-header navbar navbar-expand bg-body sticky-top" style="z-index: 10;">
            <div class="container-fluid">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button">
                            <i class="bi bi-list"></i>
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a id="res-fullscreen" class="nav-link" href="#" data-lte-toggle="fullscreen">
                            <i data-lte-icon="maximize" class="bi bi-arrows-fullscreen"></i>
                            <i data-lte-icon="minimize" class="bi bi-fullscreen-exit" style="display:none"></i>
                        </a>
                    </li>
                    <li class="nav-item pt-2">
                        <span id="date" class="text-white fw-bold" style="min-width:120px; text-align:right;"></span>
                        <span id="clock" class="text-white fw-bold" style="min-width:75px; text-align:right;"></span>
                    </li>
                    <li class="personalia-menu nav-item me-3 rounded">
                        <i id="personaliaBtn" class="bi bi-brush-fill btn fw-bold text-white" style="box-shadow:none;"></i>
                    </li>

                    <div class="ms-auto me-2 position-relative">
                        <i id="tombolAkun" class="bi bi-person-circle btn fw-bold text-white border border-white"></i>
                        <div id="akunInfo" class="akun-info card position-absolute bg-white p-2 display-state" style="width:300px;height:160px;top:50px;right:0;transition:all .2s ease-in-out">
                            <div class="d-flex p-3 align-items-center justify-content-around border-bottom">
                                <i class="bi bi-person-circle text-primary" style="font-size:44px"></i>
                                <div>
                                    <h6><?php echo htmlspecialchars($_SESSION['nama']); ?></h6>
                                    <h6 style="color:gray"><?php echo htmlspecialchars($_SESSION['hak_akses']); ?></h6>
                                </div>
                            </div>
                            <a href="../logout.php" id="logoutTombol" class="btn btn-outline-danger fw-bold ps-3 gap-2 mt-2 d-flex" onclick="return confirm('Yakin ingin logout?')" title="Logout">
                                <i class="bi bi-box-arrow-right fw-bolder"></i>
                                <p class="m-0">Logout</p>
                            </a>
                        </div>
                    </div>
                </ul>
            </div>
        </nav>

        <aside class="app-sidebar shadow" data-bs-theme="dark">
            <div class="sidebar-brand" style="border:none;">
                <a href="../index.php" class="brand-link">
                    <img src="../assets/img/logo.png" alt="MSAL Logo" class="brand-image opacity-75 shadow" />
                    <span class="brand-text fw-bold">SIBARA</span>
                </a>
            </div>
            <div class="sidebar-wrapper">
                <nav class="mt-2">
                    <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="menu" data-accordion="false">
                        <li class="nav-item">
                            <a href="../index.php" class="nav-link">
                                <i class="bi bi-house-fill"></i>
                                <p>Dashboard</p>
                            </a>
                        </li>

                        <li class="nav-header">LIST BERITA ACARA</li>
                        <li class="nav-item">
                            <a href="../ba_kerusakan-fix/ba_kerusakan.php" class="nav-link">
                                <i class="nav-icon bi bi-newspaper"></i>
                                <p>BA Kerusakan</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link" aria-disabled="true">
                                <i class="nav-icon bi bi-newspaper text-white"></i>
                                <p class="text-white">BA Pemutihan</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="../ba_pengembalian/ba_pengembalian.php" class="nav-link" aria-disabled="true">
                                <i class="nav-icon bi bi-newspaper"></i>
                                <p>BA Pengembalian</p>
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
                                <p>BA Mutasi</p>
                            </a>
                        </li>

                        <li class="nav-header">USER</li>
                        <li class="nav-item">
                            <a href="../personal/approval.php" class="nav-link">
                                <i class="nav-icon bi bi-clipboard2-check"></i>
                                <p>Approve BA</p>
                                <?php if ($jumlah_approval_notif > 0): ?>
                                    <span class="position-absolute translate-middle badge rounded-pill bg-danger" style="right:0;top:20px">
                                        <?php echo $jumlah_approval_notif; ?>
                                    </span>
                                <?php endif; ?>
                            </a>
                        </li>

                        <?php if ($showDataAkunMenu): ?>
                            <li class="nav-header">MASTER</li>
                            <li class="nav-item">
                                <a href="../master/data_akun/tabel.php" class="nav-link">
                                    <i class="nav-icon bi bi-person-circle"></i>
                                    <p>Data Akun</p>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        </aside>

        <main id="custom-main" class="custom-main app-main">
            <?php if (isset($_SESSION['message'])): ?>
                <?php if (isset($_GET['status']) && $_GET['status'] == 'sukses'): ?>
                    <div class="w-100 d-flex justify-content-center position-absolute" style="height:max-content;">
                        <div class="d-flex p-0 alert alert-success border-0 text-center fw-bold mb-0 position-absolute fade-in infoin-approval" style="z-index:8;transition: opacity 0.5s ease;right:20px;width:max-content;height:max-content;">
                            <div class="d-flex justify-content-center align-items-center bg-success pe-2 ps-2 rounded-start text-white fw-bolder">
                                <i class="bi bi-check-lg"></i>
                            </div>
                            <p class="p-2 m-0" style="font-weight:500;"><?php echo htmlspecialchars($_SESSION['message']);
                                                                        unset($_SESSION['message']); ?></p>
                        </div>
                    </div>
                <?php elseif (isset($_GET['status']) && $_GET['status'] == 'gagal'): ?>
                    <div class="w-100 d-flex justify-content-center position-absolute" style="height:max-content;">
                        <div class="d-flex p-0 alert alert-danger border-0 text-center fw-bold mb-0 position-absolute fade-in infoin-approval" style="z-index:8;transition: opacity 0.5s ease;right:20px;width:max-content;height:max-content;">
                            <div class="d-flex justify-content-center align-items-center bg-danger pe-2 ps-2 rounded-start text-white fw-bolder">
                                <i class="bi bi-x-lg"></i>
                            </div>
                            <p class="p-2 m-0" style="font-weight:500;"><?php echo htmlspecialchars($_SESSION['message']);
                                                                        unset($_SESSION['message']); ?></p>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <section id="table-wrapper" class="table-wrapper bg-white position-relative overflow-visible d-flex flex-column">
                <h2>Daftar Berita Acara Pemutihan Aset</h2>

                <form method="GET" class="mb-3 d-flex flex-wrap gap-3">
                    <select name="pt" class="form-select" onchange="this.form.submit()" style="width:200px;">
                        <?php
                        if (isset($_SESSION['hak_akses']) && $_SESSION['hak_akses'] === 'Admin' && $is_user_ho) {
                            echo '<option value="all" ' . ($filter_pt === 'all' ? 'selected' : '') . '>Semua PT</option>';
                        } else {
                            if (count($pt_list) > 1) {
                                echo '<option value="all" ' . ($filter_pt === 'all' ? 'selected' : '') . '>Semua PT</option>';
                            }
                        }

                        foreach ($pt_list as $ptx) {
                            $sel = ($filter_pt === $ptx) ? 'selected' : '';
                            echo '<option value="' . htmlspecialchars($ptx) . '" ' . $sel . '>' . htmlspecialchars($ptx) . '</option>';
                        }

                        if (count($pt_list) === 0) {
                            echo '<option value="-">-</option>';
                        }
                        ?>
                    </select>

                    <select name="tahun" class="form-select" onchange="this.form.submit()" style="width:200px;">
                        <option value="all">Semua Tahun</option>
                        <?php
                        $current_year = date('Y');
                        for ($y = $current_year; $y >= 2025; $y--) {
                            $selected = ($filter_tahun == $y) ? 'selected' : '';
                            echo "<option value='$y' $selected>$y</option>";
                        }
                        ?>
                    </select>

                    <select name="bulan" class="form-select" onchange="this.form.submit()" style="width:200px;">
                        <option value="all" <?php echo ($filter_bulan === 'all' ? 'selected' : ''); ?>>Semua Bulan</option>
                        <?php
                        $bulanIndo = array(
                            1 => 'Januari',
                            2 => 'Februari',
                            3 => 'Maret',
                            4 => 'April',
                            5 => 'Mei',
                            6 => 'Juni',
                            7 => 'Juli',
                            8 => 'Agustus',
                            9 => 'September',
                            10 => 'Oktober',
                            11 => 'November',
                            12 => 'Desember'
                        );
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
                                <th>
                                    <div class="skeleton skeleton-header"></div>
                                </th>
                                <th>
                                    <div class="skeleton skeleton-header"></div>
                                </th>
                                <th>
                                    <div class="skeleton skeleton-header"></div>
                                </th>
                                <th>
                                    <div class="skeleton skeleton-header"></div>
                                </th>
                                <th>
                                    <div class="skeleton skeleton-header"></div>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php for ($i = 0; $i < 8; $i++) { ?>
                                <tr>
                                    <td style="border:#e0e0e0 1px solid;">
                                        <div class="skeleton"></div>
                                    </td>
                                    <td style="border:#e0e0e0 1px solid;">
                                        <div class="skeleton"></div>
                                    </td>
                                    <td style="border:#e0e0e0 1px solid;">
                                        <div class="skeleton"></div>
                                    </td>
                                    <td style="border:#e0e0e0 1px solid;">
                                        <div class="skeleton"></div>
                                    </td>
                                    <td style="border:#e0e0e0 1px solid;">
                                        <div class="skeleton"></div>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>

                <div id="tabelUtama" style="display:none;">
                    <table id="myTable" class="table table-bordered table-striped text-center">
                        <div class="custom-btn-input-history position-absolute d-flex gap-2" style="top:127px;left:220px;z-index:1;width:max-content;height:max-content;">
                            <a href="#" id="tombolInputPopup" class="<?php if (isset($_SESSION['hak_akses']) && $_SESSION['hak_akses'] === 'Super Admin') {
                                                                            echo 'd-none';
                                                                        } ?> btn btn-success"><i class="bi bi-plus-lg"></i></a>
                            <a href="../master/histori_edit.php" id="tombolHistorikal" class="btn btn-warning"><i class="bi bi-clock-history"></i></a>
                        </div>
                        <thead class="bg-secondary" id="thead-utama">
                            <tr>
                                <th>No</th>
                                <th>Tanggal</th>
                                <th>Nomor BA</th>
                                <th>Lokasi</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="tbody-utama">
                            <?php
                            $no = 1;
                            while ($row = $result->fetch_assoc()):
                                $approvalPending = false;
                                for ($ap = 1; $ap <= 11; $ap++) {
                                    $fieldAp = 'approval_' . $ap;
                                    if (isset($row[$fieldAp]) && (int) $row[$fieldAp] === 1) {
                                        $approvalPending = true;
                                        break;
                                    }
                                }
                            ?>
                                <tr>
                                    <td><?php echo $no; ?></td>
                                    <td><?php echo htmlspecialchars($row['tanggal']); ?></td>
                                    <td><?php echo htmlspecialchars($row['nomor_ba']); ?></td>
                                    <td><?php echo htmlspecialchars($row['pt']); ?></td>
                                    <td>
                                        <a class="custom-btn-action btn btn-secondary btn-sm btn-detail-ba-pemutihan" href="#" data-id="<?php echo $row['id']; ?>">
                                            <i class="bi bi-eye-fill"></i>
                                        </a>
                                        <a class="custom-btn-action btn btn-primary btn-sm" href="surat_output.php?id=<?php echo $row['id']; ?>" target="_blank">
                                            <i class="bi bi-file-earmark-text-fill"></i>
                                        </a>
                                        <?php if (isset($_SESSION['nama']) && $_SESSION['nama'] === $row['nama_pembuat']): ?>
                                            <?php if ((int) $row['pending_hapus'] !== 1): ?>
                                                <a class="custom-btn-action btn btn-warning btn-sm tombolPopupEdit" href="#" data-id="<?php echo $row['id']; ?>">
                                                    <i class="bi bi-feather"></i>
                                                </a>
                                                <a class="custom-btn-action btn btn-danger btn-sm tombolPopupDelete" href="#" data-id="<?php echo $row['id']; ?>" data-pending="<?php echo ($approvalPending ? 'true' : 'false'); ?>">
                                                    <i class="bi bi-trash-fill"></i>
                                                </a>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                        <?php if ((int) $row['pending_hapus'] === 1 && isset($_SESSION['nama']) && $_SESSION['nama'] === $row['nama_pembuat']): ?>
                                            <br>
                                            <p class="m-0 mb-1 text-warning" style="font-size:12px;"><i class="bi bi-exclamation-triangle"></i> Surat sedang pending delete.</p>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php
                                $no++;
                            endwhile;
                            ?>
                        </tbody>
                    </table>
                </div>

                <div id="popupBoxDelete" class="custom-popup-box-delete popup-box position-absolute bg-white rounded-1 flex-column justify-content-start align-items-center p-2" style="top:30vh; height:max-content;align-self:center;z-index:9;width:500px;">
                    <div class="w-100 d-flex justify-content-between mb-2" style="height:max-content;">
                        <h4 class="m-0 p-0"></h4>
                    </div>
                    <form id="formDelete" method="POST" action="delete.php" class="d-flex flex-column align-items-center w-100">
                        <input type="hidden" name="id" id="deleteId">
                        <input type="hidden" name="pending" id="deletePending">
                        <p>Apakah anda yakin ingin menghapus data ini?</p>
                        <div id="alasanWrapper" class="w-100 d-none">
                            <div class="input-group">
                                <span class="input-group-text">Alasan Hapus</span>
                                <textarea name="alasan_hapus" id="alasanHapus" class="form-control"></textarea>
                            </div>
                        </div>
                        <div class="w-50 d-flex justify-content-around mt-2">
                            <button id="tombolAccDelete" type="submit" class="btn btn-danger">Hapus</button>
                            <a id="tombolClosePopupDelete" class="custom-btn-action btn btn-secondary" href="#">Batal</a>
                        </div>
                    </form>
                </div>

                <div id="popupBoxInput" class="popup-box position-absolute bg-white top-0 rounded-1 flex-column justify-content-start align-items-center p-2" style="height:max-content;align-self:center;z-index:9;width:95%;">
                    <div class="w-100 d-flex justify-content-between mb-2" style="height:max-content;">
                        <h4 class="m-0 p-0">Input Berita Acara</h4>
                        <a id="tombolClosePopup" class="custom-btn-action btn btn-danger btn-sm" href="#"><i class="bi bi-x-lg"></i></a>
                    </div>

                    <form class="popupInput d-flex flex-column" method="post" action="proses_simpan.php" enctype="multipart/form-data">
                        <!-- <input type="hidden" name="nama_pembuat" value="<?php echo htmlspecialchars($_SESSION['nama']); ?>">
                        <input type="hidden" name="id_pt" id="id_pt_input" value="<?php echo isset($pt_map[$pt_default]) ? (int) $pt_map[$pt_default] : ''; ?>">
                        <input type="hidden" name="jabatan_pembuat" id="jabatan_pembuat_input" value="">
                        <input type="hidden" name="jabatan_pemeriksa" id="jabatan_pemeriksa_input" value="">
                        <input type="hidden" name="jabatan_diketahui1_site" id="jabatan_diketahui1_site_input" value="">
                        <input type="hidden" name="jabatan_disetujui1_site" id="jabatan_disetujui1_site_input" value=""> -->

                        <input type="hidden" name="nama_pembuat" value="<?php echo htmlspecialchars($_SESSION['nama']); ?>">
                        <input type="hidden" name="id_pt" id="id_pt_input" value="<?php echo isset($pt_map[$pt_default]) ? (int) $pt_map[$pt_default] : ''; ?>">
                        <input type="hidden" name="dept_pengguna" id="dept_pengguna_input" value="">
                        <input type="hidden" name="jabatan_dept_pengguna" id="jabatan_dept_pengguna_input" value="">
                        <input type="hidden" name="jabatan_diketahui1_site" id="jabatan_diketahui1_site_input" value="">
                        <input type="hidden" name="jabatan_disetujui1_site" id="jabatan_disetujui1_site_input" value="">

                        <div class="form-section">
                            <div class="row position-relative">
                                <div class="custom-input-form col-8">
                                    <h3>Data Berita Acara Pemutihan</h3>

                                    <div class="row">
                                        <div class="col-md-3 mb-3">
                                            <div class="input-group">
                                                <span class="input-group-text">Tanggal</span>
                                                <input class="form-control" type="date" name="tanggal" id="tanggal" max="<?php echo date('Y-m-d'); ?>" value="<?php echo date('Y-m-d'); ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <div class="input-group">
                                                <span class="input-group-text">Nomor BA</span>
                                                <input type="text" class="form-control" maxlength="3" name="nomor_ba" id="nomor_ba" value="<?php echo $nomor_ba_baru; ?>" readonly>
                                            </div>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <div class="input-group">
                                                <span class="input-group-text">PT</span>
                                                <select name="pt" id="perusahaan" class="form-select" required>
                                                    <option value="">-- Pilih PT --</option>
                                                    <?php foreach ($pt_list as $ptx): ?>
                                                        <option value="<?php echo htmlspecialchars($ptx); ?>" <?php echo ($ptx === $pt_default ? 'selected' : ''); ?>>
                                                            <?php echo htmlspecialchars($ptx); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row mt-1 border border-1 p-2 rounded-2 me-1">
                                        <div class="row pt-1 pb-2">
                                            <div class="col-12 d-flex flex-column align-items-start flex-wrap gap-2">
                                                <h5 class="m-0">Data Barang</h5>
                                                <div class="custom-btn-data-barang d-flex gap-2">
                                                    <button type="button" class="tombolDataBarangPopup btn btn-primary" data-target="input"><i class="bi bi-search"></i></button>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-12">
                                                <div class="table-responsive border rounded p-2 bg-light">
                                                    <table class="table table-bordered table-striped custom-selected-barang mb-0" id="selectedBarangTableInput">
                                                        <thead>
                                                            <tr>
                                                                <th>No</th>
                                                                <th>PT Asal</th>
                                                                <th>PO</th>
                                                                <th>COA</th>
                                                                <th>Kode Asset</th>
                                                                <th>Merek</th>
                                                                <th>SN</th>
                                                                <th>User</th>
                                                                <th>Harga Beli</th>
                                                                <th>Tahun Perolehan</th>
                                                                <th>Alasan Penghapusan</th>
                                                                <th>Kondisi</th>
                                                                <th>Hapus</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody id="selectedBarangBodyInput">
                                                            <tr class="empty-row">
                                                                <td colspan="13">Belum ada barang dipilih.</td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- <div class="row mt-3 border border-1 p-2 rounded-2 me-1">
                                        <div class="row">
                                            <h5 class="mb-2">Data Pengguna</h5>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <div class="input-group">
                                                <span class="input-group-text">Pembuat</span>
                                                <select name="pembuat" id="input-pembuat" class="form-select" required>
                                                    <option value="">-- Pilih Pembuat --</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <div class="input-group">
                                                <span class="input-group-text">Pemeriksa</span>
                                                <select name="pemeriksa" id="input-pemeriksa" class="form-select" required>
                                                    <option value="">-- Pilih Pemeriksa --</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="row d-none" id="site-extra-row-input">
                                            <div class="col-md-6 mb-3">
                                                <div class="input-group">
                                                    <span class="input-group-text">Diketahui</span>
                                                    <select name="diketahui1_site" id="input-diketahui-site" class="form-select">
                                                        <option value="">-- Pilih Diketahui --</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <div class="input-group">
                                                    <span class="input-group-text">Disetujui</span>
                                                    <input type="text" name="disetujui1_site" id="input-disetujui-site" class="form-control" value="" placeholder="Kepala Project">
                                                </div>
                                            </div>
                                        </div>
                                    </div> -->

                                    <div class="row mt-3 border border-1 p-2 rounded-2 me-1">
                                        <div class="row">
                                            <h5 class="mb-2">Data Pengguna</h5>
                                        </div>

                                        <div class="col-md-6 mb-3 d-none" id="dept-row-input">
                                            <div class="input-group">
                                                <span class="input-group-text">Departemen</span>
                                                <select name="departemen_pengguna" id="input-departemen" class="form-select">
                                                    <option value="">-- Pilih Departemen --</option>
                                                    <option value="HRO">HRO</option>
                                                    <option value="MIS">MIS</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="row d-none" id="site-extra-row-input">
                                            <div class="col-md-6 mb-3">
                                                <div class="input-group">
                                                    <span class="input-group-text">Diketahui</span>
                                                    <select name="diketahui1_site" id="input-diketahui-site" class="form-select">
                                                        <option value="">-- Pilih Diketahui --</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <div class="input-group">
                                                    <span class="input-group-text">Disetujui</span>
                                                    <input type="text" name="disetujui1_site" id="input-disetujui-site" class="form-control" value="" placeholder="Kepala Project">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                </div>

                                <div class="custom-input-gambar-section col-4">
                                    <h3>Gambar</h3>
                                    <div class="custom-input-gambar border border-2 rounded-3 p-2" style="height:485px; overflow-y:auto;">
                                        <div class="d-flex flex-column">
                                            <div id="gambar-container"></div>
                                            <button type="button" class="btn btn-primary w-75 align-self-center mb-1" onclick="tambahGambar('gambar-container', 'gambar[]', 'keterangan_gambar[]')">+ Tambah Gambar</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="footer-form d-flex w-100 justify-content-between">
                            <h5 class="m-0 mt-3" style="color: darkgray;">*Formulir ini untuk membuat berita acara pemutihan aset</h5>
                            <input class="w-25 align-self-end" type="submit" value="Simpan">
                        </div>
                    </form>
                </div>

                <div id="popupBoxEdit" class="popup-box position-absolute bg-white top-0 rounded-1 flex-column justify-content-start align-items-center p-2" style="height:max-content;align-self:center;z-index:9;width:95%;">
                    <div class="w-100 d-flex justify-content-between mb-2" style="height:max-content;">
                        <h4 id="popupEditTitle" class="m-0 p-0">Edit Berita Acara</h4>
                        <a id="tombolClosePopupEdit" class="custom-btn-action btn btn-danger btn-sm" href="#" style="height:max-content;"><i class="bi bi-x-lg"></i></a>
                    </div>
                    <div id="popupEditBody" class="w-100"></div>
                </div>

                <div id="popupBoxDetail" class="popup-box position-absolute bg-white top-0 rounded-1 flex-column justify-content-start align-items-center p-2" style="height:max-content;align-self:center;z-index:9;width:95%;">
                    <div class="w-100 d-flex justify-content-between mb-2" style="height:max-content;">
                        <h4 id="popupDetailTitle" class="m-0 p-0">Detail Berita Acara</h4>
                        <a id="tombolClosePopupDetail" class="custom-btn-action btn btn-danger btn-sm" href="#"><i class="bi bi-x-lg"></i></a>
                    </div>
                    <div id="popupDetailBody" class="w-100"></div>
                </div>

                <div id="popupBoxDataBarang" class="popup-box position-absolute bg-white top-0 rounded-1 flex-column justify-content-start align-items-center p-2" style="height:max-content;align-self:center;z-index:10;width:95%;">
                    <div class="w-100 d-flex justify-content-between mb-2" style="height:max-content;">
                        <h4 id="popupDataBarangTitle" class="m-0 p-0">Tabel Data Barang</h4>
                        <a id="tombolClosePopupDataBarang" class="custom-btn-action btn btn-danger btn-sm" href="#"><i class="bi bi-x-lg"></i></a>
                    </div>
                    <div class="w-100 d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                        <p class="m-0 p-0">Pilih satu atau lebih barang.</p>
                        <button type="button" id="gunakanBarangTerpilih" class="btn btn-success btn-sm"><i class="bi bi-check2-circle"></i> Gunakan Barang Terpilih</button>
                    </div>
                    <div class="w-100" style="height:max-content;">
                        <table id="myTable2" class="table table-bordered table-striped text-center" style="width:100%;">
                            <thead class="bg-secondary">
                                <tr>
                                    <th>No</th>
                                    <th>PT Asal</th>
                                    <th>PO</th>
                                    <th>COA</th>
                                    <th>Kode Asset</th>
                                    <th>Merek</th>
                                    <th>SN</th>
                                    <th>User</th>
                                    <th>Harga Beli</th>
                                    <th>Tahun Perolehan</th>
                                    <th>Pilih</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $no = 1;
                                if ($result_assets2) {
                                    while ($row_assets = $result_assets2->fetch_assoc()):
                                        $id_pt_barang = !empty($row_assets['id_pt']) ? $row_assets['id_pt'] : '-';
                                        $pt_name_barang = isset($pt_map_rev[(string)$row_assets['id_pt']]) ? $pt_map_rev[(string)$row_assets['id_pt']] : '-';
                                        $no_po = !empty($row_assets['no_po']) ? $row_assets['no_po'] : '-';
                                        $coa = !empty($row_assets['coa']) ? $row_assets['coa'] : '-';
                                        $kode_assets = !empty($row_assets['id_assets']) ? $row_assets['id_assets'] : '-';
                                        $merek = !empty($row_assets['asset_merk']) ? $row_assets['asset_merk'] : '-';
                                        $serial = !empty($row_assets['serial_number']) ? $row_assets['serial_number'] : '-';
                                        $user_asset = !empty($row_assets['user']) ? $row_assets['user'] : '-';

                                        $harga_beli_raw = (isset($row_assets['harga']) && $row_assets['harga'] !== '' && $row_assets['harga'] !== null) ? (int)$row_assets['harga'] : 0;
                                        $harga_beli_tampil = ($harga_beli_raw > 0) ? 'Rp ' . number_format($harga_beli_raw, 0, ',', '.') : '-';

                                        $tahun_beli = '-';
                                        if (!empty($row_assets['tgl_pembelian']) && $row_assets['tgl_pembelian'] !== '0000-00-00' && $row_assets['tgl_pembelian'] !== '1970-01-01') {
                                            $tahun_beli = date('Y', strtotime($row_assets['tgl_pembelian']));
                                        }
                                ?>
                                        <tr class="barang-row"
                                            data-idpt="<?php echo htmlspecialchars($id_pt_barang); ?>"
                                            data-pt="<?php echo htmlspecialchars($pt_name_barang); ?>"
                                            data-po="<?php echo htmlspecialchars($no_po); ?>"
                                            data-coa="<?php echo htmlspecialchars($coa); ?>"
                                            data-kode="<?php echo htmlspecialchars($kode_assets); ?>"
                                            data-merk="<?php echo htmlspecialchars($merek); ?>"
                                            data-sn="<?php echo htmlspecialchars($serial); ?>"
                                            data-user="<?php echo htmlspecialchars($user_asset); ?>"
                                            data-harga="<?php echo htmlspecialchars($harga_beli_raw); ?>"
                                            data-tahun="<?php echo htmlspecialchars($tahun_beli); ?>">
                                            <td><?php echo $no; ?></td>
                                            <td><?php echo htmlspecialchars($pt_name_barang); ?></td>
                                            <td><?php echo htmlspecialchars($no_po); ?></td>
                                            <td><?php echo htmlspecialchars($coa); ?></td>
                                            <td><?php echo htmlspecialchars($kode_assets); ?></td>
                                            <td><?php echo htmlspecialchars($merek); ?></td>
                                            <td><?php echo htmlspecialchars($serial); ?></td>
                                            <td><?php echo htmlspecialchars($user_asset); ?></td>
                                            <td><?php echo htmlspecialchars($harga_beli_tampil); ?></td>
                                            <td><?php echo htmlspecialchars($tahun_beli); ?></td>
                                            <td>
                                                <input type="checkbox" class="form-check-input pilih-barang-checkbox">
                                            </td>
                                        </tr>
                                <?php
                                        $no++;
                                    endwhile;
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </main>

        <div id="popupBG" class="popup-bg position-absolute w-100 h-100" style="background-color: rgba(0,0,0,0.5);z-index:8;"></div>
        <div id="popupBG2" class="popup-bg position-absolute w-100 h-100" style="background-color: rgba(0,0,0,0.2);z-index:9;"></div>

        <footer class="custom-footer d-flex position-relative p-2" style="border-top: whitesmoke solid 1px; box-shadow: 0px 7px 10px black; color: grey;">
            <p class="position-absolute" style="right:15px;bottom:7px;color:grey;"><strong>Version </strong>1.1.0</p>
            <p class="pt-2 ps-1"><strong>Copyright &copy; 2025</strong></p>
            <p class="pt-2 ps-1 fw-bold text-primary">MIS MSAL.</p>
            <p class="pt-2 ps-1">All rights reserved</p>
        </footer>

        <?php
        $sqlWarna = "SELECT nama, warna FROM personalia_menucolor ORDER BY nama ASC";
        $resultWarna = $koneksi->query($sqlWarna);
        ?>
        <div id="popupBoxPersonalia" class="popup-box position-fixed end-0" style="z-index:15; top:50px;">
            <div id="theme-panel" class="card position-relative bg-white p-2 m-2" style="width:200px; height:max-content; box-shadow:0px 4px 8px rgba(0,0,0,0.1);">
                <h5 class="card-title border-bottom pb-2 mb-0">Personalia</h5>
                <form action="../proses_simpan_personalia.php" method="post" class="p-0">
                    <div class="mb-2">
                        <label for="themeSelect" class="form-label mt-0">Warna Tema:</label>
                        <select id="themeSelect" name="warna_menu" class="form-select">
                            <option value="0" selected>Default</option>
                            <?php if ($resultWarna): ?>
                                <?php while ($rowWarna = $resultWarna->fetch_assoc()): ?>
                                    <option value="<?php echo htmlspecialchars($rowWarna['warna']); ?>"><?php echo htmlspecialchars($rowWarna['nama']); ?></option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm">Simpan</button>
                </form>
            </div>
        </div>
    </div>

    <script src="../assets/bootstrap-5.3.6-dist/js/bootstrap.min.js"></script>
    <script src="../assets/js/popper.min.js"></script>
    <script src="../assets/adminlte/js/adminlte.js"></script>
    <script src="../assets/js/overlayscrollbars.browser.es6.min.js"></script>

    <script>
        var PT_MAP = <?php echo ($pt_map_json ? $pt_map_json : '{}'); ?>;
        var PT_LIST = <?php echo ($pt_list_json ? $pt_list_json : '[]'); ?>;
        var DATA_KARYAWAN_HO = <?php echo ($data_karyawan_ho_json ? $data_karyawan_ho_json : '[]'); ?>;
        var DATA_KARYAWAN_TEST = <?php echo ($data_karyawan_test_json ? $data_karyawan_test_json : '[]'); ?>;

        var activeTarget = 'input';
        var selectedBarangInput = [];
        var selectedBarangEdit = [];

        function escapeHtml(str) {
            if (str === null || typeof str === 'undefined') {
                return '';
            }
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function formatRomawiMonth(dateString) {
            var bulanRomawi = ['', 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];
            if (!dateString) {
                return '';
            }
            var parts = String(dateString).split('-');
            if (parts.length < 2) {
                return '';
            }
            var month = parseInt(parts[1], 10);
            return bulanRomawi[month] || '';
        }

        function getYearFromDate(dateString) {
            if (!dateString) {
                return '';
            }
            var parts = String(dateString).split('-');
            return parts.length > 0 ? parts[0] : '';
        }

        function ptMatches(ptCsv, selectedPT) {
            var csv = String(ptCsv || '');
            var selected = String(selectedPT || '');
            if (!csv || !selected) {
                return false;
            }
            var arr = csv.split(',');
            for (var i = 0; i < arr.length; i++) {
                if ($.trim(arr[i]) === $.trim(selected)) {
                    return true;
                }
            }
            return false;
        }

        function setIdPtBySelect(selectId, hiddenId) {
            var ptValue = $('#' + selectId).val();
            if ($('#' + hiddenId).length) {
                $('#' + hiddenId).val(PT_MAP[ptValue] ? PT_MAP[ptValue] : '');
            }
        }

        // function buildOptionHtml(items, selectedValue) {
        //     var html = '<option value="">-- Pilih --</option>';
        //     for (var i = 0; i < items.length; i++) {
        //         var item = items[i];
        //         var selected = (selectedValue && String(selectedValue) === String(item.nama)) ? ' selected' : '';
        //         html += '<option value="' + escapeHtml(item.nama) + '" data-jabatan="' + escapeHtml(item.jabatan_value) + '"' + selected + '>' + escapeHtml(item.nama) + ' - ' + escapeHtml(item.jabatan_value) + '</option>';
        //     }
        //     return html;
        // }

        // function isHoPT(selectedPT) {
        //     return String(selectedPT || '') === 'PT.MSAL (HO)';
        // }

        // function getFormContext(mode) {
        //     if (mode === 'edit') {
        //         return {
        //             pt: $('#edit-pt').val(),
        //             pembuatSelect: $('#edit-pembuat'),
        //             pemeriksaSelect: $('#edit-pemeriksa'),
        //             jabatanPembuat: $('#jabatan_pembuat_edit'),
        //             jabatanPemeriksa: $('#jabatan_pemeriksa_edit'),
        //             diketahuiSiteSelect: $('#edit-diketahui-site'),
        //             disetujuiSiteInput: $('#edit-disetujui-site'),
        //             jabatanDiketahuiSite: $('#jabatan_diketahui1_site_edit'),
        //             jabatanDisetujuiSite: $('#jabatan_disetujui1_site_edit'),
        //             siteExtraRow: $('#site-extra-row-edit')
        //         };
        //     }
        //     return {
        //         pt: $('#perusahaan').val(),
        //         pembuatSelect: $('#input-pembuat'),
        //         pemeriksaSelect: $('#input-pemeriksa'),
        //         jabatanPembuat: $('#jabatan_pembuat_input'),
        //         jabatanPemeriksa: $('#jabatan_pemeriksa_input'),
        //         diketahuiSiteSelect: $('#input-diketahui-site'),
        //         disetujuiSiteInput: $('#input-disetujui-site'),
        //         jabatanDiketahuiSite: $('#jabatan_diketahui1_site_input'),
        //         jabatanDisetujuiSite: $('#jabatan_disetujui1_site_input'),
        //         siteExtraRow: $('#site-extra-row-input')
        //     };
        // }

        // function getPembuatCandidates(selectedPT) {
        //     var items = [];
        //     if (selectedPT === 'PT.MSAL (HO)') {
        //         for (var i = 0; i < DATA_KARYAWAN_HO.length; i++) {
        //             items.push({
        //                 nama: DATA_KARYAWAN_HO[i].nama,
        //                 jabatan_value: DATA_KARYAWAN_HO[i].posisi ? DATA_KARYAWAN_HO[i].posisi : DATA_KARYAWAN_HO[i].jabatan
        //             });
        //         }
        //     } else {
        //         for (var j = 0; j < DATA_KARYAWAN_TEST.length; j++) {
        //             if (ptMatches(DATA_KARYAWAN_TEST[j].pt, selectedPT) && String(DATA_KARYAWAN_TEST[j].posisi) === 'IT Support') {
        //                 items.push({
        //                     nama: DATA_KARYAWAN_TEST[j].nama,
        //                     jabatan_value: DATA_KARYAWAN_TEST[j].posisi
        //                 });
        //             }
        //         }
        //     }
        //     return items;
        // }

        // function getPemeriksaCandidates(selectedPT, pembuatDipilih) {
        //     var items = [];
        //     if (selectedPT === 'PT.MSAL (HO)') {
        //         for (var i = 0; i < DATA_KARYAWAN_HO.length; i++) {
        //             if (String(DATA_KARYAWAN_HO[i].nama) !== String(pembuatDipilih || '')) {
        //                 items.push({
        //                     nama: DATA_KARYAWAN_HO[i].nama,
        //                     jabatan_value: DATA_KARYAWAN_HO[i].posisi ? DATA_KARYAWAN_HO[i].posisi : DATA_KARYAWAN_HO[i].jabatan
        //                 });
        //             }
        //         }
        //     } else {
        //         for (var j = 0; j < DATA_KARYAWAN_TEST.length; j++) {
        //             if (ptMatches(DATA_KARYAWAN_TEST[j].pt, selectedPT) && String(DATA_KARYAWAN_TEST[j].posisi) === 'Staf GA' && String(DATA_KARYAWAN_TEST[j].nama) !== String(pembuatDipilih || '')) {
        //                 items.push({
        //                     nama: DATA_KARYAWAN_TEST[j].nama,
        //                     jabatan_value: DATA_KARYAWAN_TEST[j].posisi
        //                 });
        //             }
        //         }
        //     }
        //     return items;
        // }

        function getDiketahuiSiteCandidates(selectedPT) {
            var items = [];
            for (var j = 0; j < DATA_KARYAWAN_TEST.length; j++) {
                if (ptMatches(DATA_KARYAWAN_TEST[j].pt, selectedPT) && String(DATA_KARYAWAN_TEST[j].posisi) === 'KTU') {
                    items.push({
                        nama: DATA_KARYAWAN_TEST[j].nama,
                        jabatan_value: DATA_KARYAWAN_TEST[j].posisi
                    });
                }
            }
            return items;
        }

        // function updateJabatanHidden(selectEl, hiddenEl) {
        //     if (!selectEl || !hiddenEl || !selectEl.length || !hiddenEl.length) {
        //         return;
        //     }
        //     var jabatan = selectEl.find('option:selected').data('jabatan');
        //     hiddenEl.val(jabatan ? jabatan : '');
        // }

        // function syncSiteExtraFields(mode, presetDiketahuiSite, presetDisetujuiSite) {
        //     var ctx = getFormContext(mode);
        //     var selectedPT = ctx.pt;
        //     var isHO = isHoPT(selectedPT);

        //     if (ctx.siteExtraRow && ctx.siteExtraRow.length) {
        //         ctx.siteExtraRow.toggleClass('d-none', isHO);
        //     }

        //     if (!ctx.diketahuiSiteSelect.length || !ctx.disetujuiSiteInput.length) {
        //         return;
        //     }

        //     ctx.diketahuiSiteSelect.prop('required', !isHO);
        //     ctx.disetujuiSiteInput.prop('required', !isHO);

        //     if (isHO) {
        //         ctx.diketahuiSiteSelect.html('<option value="">-- Pilih Diketahui --</option>').val('');
        //         ctx.disetujuiSiteInput.val('');
        //         if (ctx.jabatanDiketahuiSite.length) {
        //             ctx.jabatanDiketahuiSite.val('');
        //         }
        //         if (ctx.jabatanDisetujuiSite.length) {
        //             ctx.jabatanDisetujuiSite.val('');
        //         }
        //         return;
        //     }

        //     var diketahuiSiteCandidates = getDiketahuiSiteCandidates(selectedPT);
        //     var selectedDiketahuiSite = (typeof presetDiketahuiSite !== 'undefined' && presetDiketahuiSite !== null) ?
        //         presetDiketahuiSite :
        //         ctx.diketahuiSiteSelect.val();
        //     var currentDisetujuiSite = (typeof presetDisetujuiSite !== 'undefined' && presetDisetujuiSite !== null) ?
        //         presetDisetujuiSite :
        //         ctx.disetujuiSiteInput.val();

        //     ctx.diketahuiSiteSelect.html('<option value="">-- Pilih Diketahui --</option>' + buildOptionHtml(diketahuiSiteCandidates, selectedDiketahuiSite).replace('<option value="">-- Pilih --</option>', ''));
        //     if (selectedDiketahuiSite) {
        //         ctx.diketahuiSiteSelect.val(selectedDiketahuiSite);
        //     }
        //     updateJabatanHidden(ctx.diketahuiSiteSelect, ctx.jabatanDiketahuiSite);

        //     ctx.disetujuiSiteInput.val(currentDisetujuiSite ? currentDisetujuiSite : '');
        // }

        // function populateActorSelects(mode, presetPembuat, presetPemeriksa, presetDiketahuiSite, presetDisetujuiSite) {
        //     var ctx = getFormContext(mode);
        //     var selectedPT = ctx.pt;

        //     if (!selectedPT) {
        //         ctx.pembuatSelect.html('<option value="">-- Pilih Pembuat --</option>');
        //         ctx.pemeriksaSelect.html('<option value="">-- Pilih Pemeriksa --</option>');
        //         ctx.jabatanPembuat.val('');
        //         ctx.jabatanPemeriksa.val('');

        //         if (ctx.siteExtraRow && ctx.siteExtraRow.length) {
        //             ctx.siteExtraRow.addClass('d-none');
        //         }
        //         if (ctx.diketahuiSiteSelect && ctx.diketahuiSiteSelect.length) {
        //             ctx.diketahuiSiteSelect.html('<option value="">-- Pilih Diketahui --</option>').prop('required', false).val('');
        //         }
        //         if (ctx.disetujuiSiteInput && ctx.disetujuiSiteInput.length) {
        //             ctx.disetujuiSiteInput.prop('required', false).val('');
        //         }
        //         if (ctx.jabatanDiketahuiSite && ctx.jabatanDiketahuiSite.length) {
        //             ctx.jabatanDiketahuiSite.val('');
        //         }
        //         if (ctx.jabatanDisetujuiSite && ctx.jabatanDisetujuiSite.length) {
        //             ctx.jabatanDisetujuiSite.val('');
        //         }
        //         return;
        //     }

        //     var pembuatCandidates = getPembuatCandidates(selectedPT);
        //     var selectedPembuat = presetPembuat ? presetPembuat : ctx.pembuatSelect.val();
        //     ctx.pembuatSelect.html('<option value="">-- Pilih Pembuat --</option>' + buildOptionHtml(pembuatCandidates, selectedPembuat).replace('<option value="">-- Pilih --</option>', ''));
        //     if (selectedPembuat) {
        //         ctx.pembuatSelect.val(selectedPembuat);
        //     }
        //     updateJabatanHidden(ctx.pembuatSelect, ctx.jabatanPembuat);

        //     var currentPembuat = ctx.pembuatSelect.val();
        //     var pemeriksaCandidates = getPemeriksaCandidates(selectedPT, currentPembuat);
        //     var selectedPemeriksa = presetPemeriksa ? presetPemeriksa : ctx.pemeriksaSelect.val();
        //     ctx.pemeriksaSelect.html('<option value="">-- Pilih Pemeriksa --</option>' + buildOptionHtml(pemeriksaCandidates, selectedPemeriksa).replace('<option value="">-- Pilih --</option>', ''));
        //     if (selectedPemeriksa) {
        //         ctx.pemeriksaSelect.val(selectedPemeriksa);
        //     }
        //     updateJabatanHidden(ctx.pemeriksaSelect, ctx.jabatanPemeriksa);

        //     syncSiteExtraFields(mode, presetDiketahuiSite, presetDisetujuiSite);
        // }

        function buildOptionHtml(items, selectedValue) {
            var html = '<option value="">-- Pilih --</option>';
            for (var i = 0; i < items.length; i++) {
                var item = items[i];
                var selected = (selectedValue && String(selectedValue) === String(item.nama)) ? ' selected' : '';
                html += '<option value="' + escapeHtml(item.nama) + '" data-jabatan="' + escapeHtml(item.jabatan_value) + '"' + selected + '>' + escapeHtml(item.nama) + ' - ' + escapeHtml(item.jabatan_value) + '</option>';
            }
            return html;
        }

        function isHoPT(selectedPT) {
            return String(selectedPT || '') === 'PT.MSAL (HO)';
        }

        function getDeptHeadByDepartemen(departemen) {
            var dept = String(departemen || '');
            for (var i = 0; i < DATA_KARYAWAN_HO.length; i++) {
                if (String(DATA_KARYAWAN_HO[i].departemen) === dept && String(DATA_KARYAWAN_HO[i].jabatan) === 'Dept. Head') {
                    return DATA_KARYAWAN_HO[i].nama ? DATA_KARYAWAN_HO[i].nama : '';
                }
            }
            return '';
        }

        function getJabatanDeptHeadByDepartemen(departemen) {
            var dept = String(departemen || '');
            var jabatan = '';
            var posisi = '';

            for (var i = 0; i < DATA_KARYAWAN_HO.length; i++) {
                if (String(DATA_KARYAWAN_HO[i].departemen) === dept && String(DATA_KARYAWAN_HO[i].jabatan) === 'Dept. Head') {
                    jabatan = DATA_KARYAWAN_HO[i].jabatan ? String(DATA_KARYAWAN_HO[i].jabatan) : '';
                    posisi = DATA_KARYAWAN_HO[i].posisi ? String(DATA_KARYAWAN_HO[i].posisi) : '';

                    if (jabatan !== '' && dept !== '' && posisi !== '') {
                        return jabatan + ' ' + dept + ' (' + posisi + ')';
                    }

                    if (jabatan !== '' && dept !== '') {
                        return jabatan + ' ' + dept;
                    }

                    return '';
                }
            }

            return '';
        }

        function getFormContext(mode) {
            if (mode === 'edit') {
                return {
                    pt: $('#edit-pt').val(),
                    departemenSelect: $('#edit-departemen'),
                    deptPenggunaHidden: $('#dept_pengguna_edit'),
                    jabatanDeptPenggunaHidden: $('#jabatan_dept_pengguna_edit'),
                    deptRow: $('#dept-row-edit'),
                    diketahuiSiteSelect: $('#edit-diketahui-site'),
                    disetujuiSiteInput: $('#edit-disetujui-site'),
                    jabatanDiketahuiSite: $('#jabatan_diketahui1_site_edit'),
                    jabatanDisetujuiSite: $('#jabatan_disetujui1_site_edit'),
                    siteExtraRow: $('#site-extra-row-edit')
                };
            }
            return {
                pt: $('#perusahaan').val(),
                departemenSelect: $('#input-departemen'),
                deptPenggunaHidden: $('#dept_pengguna_input'),
                jabatanDeptPenggunaHidden: $('#jabatan_dept_pengguna_input'),
                deptRow: $('#dept-row-input'),
                diketahuiSiteSelect: $('#input-diketahui-site'),
                disetujuiSiteInput: $('#input-disetujui-site'),
                jabatanDiketahuiSite: $('#jabatan_diketahui1_site_input'),
                jabatanDisetujuiSite: $('#jabatan_disetujui1_site_input'),
                siteExtraRow: $('#site-extra-row-input')
            };
        }

        function updateDeptPengguna(mode) {
            var ctx = getFormContext(mode);
            if (!ctx.departemenSelect.length || !ctx.deptPenggunaHidden.length) {
                return;
            }

            var selectedPT = ctx.pt;
            var departemen = ctx.departemenSelect.val();

            if (!isHoPT(selectedPT) || !departemen) {
                ctx.deptPenggunaHidden.val('');
                if (ctx.jabatanDeptPenggunaHidden && ctx.jabatanDeptPenggunaHidden.length) {
                    ctx.jabatanDeptPenggunaHidden.val('');
                }
                return;
            }

            ctx.deptPenggunaHidden.val(getDeptHeadByDepartemen(departemen));

            if (ctx.jabatanDeptPenggunaHidden && ctx.jabatanDeptPenggunaHidden.length) {
                ctx.jabatanDeptPenggunaHidden.val(getJabatanDeptHeadByDepartemen(departemen));
            }
        }

        function updateJabatanHidden(selectEl, hiddenEl) {
            if (!selectEl || !hiddenEl || !selectEl.length || !hiddenEl.length) {
                return;
            }
            var jabatan = selectEl.find('option:selected').data('jabatan');
            hiddenEl.val(jabatan ? jabatan : '');
        }

        function syncSiteExtraFields(mode, presetDiketahuiSite, presetDisetujuiSite) {
            var ctx = getFormContext(mode);
            var selectedPT = ctx.pt;
            var isHO = isHoPT(selectedPT);

            if (ctx.siteExtraRow && ctx.siteExtraRow.length) {
                ctx.siteExtraRow.toggleClass('d-none', isHO);
            }

            if (ctx.deptRow && ctx.deptRow.length) {
                ctx.deptRow.toggleClass('d-none', !isHO);
            }

            if (ctx.departemenSelect && ctx.departemenSelect.length) {
                ctx.departemenSelect.prop('required', isHO);
                if (!isHO) {
                    ctx.departemenSelect.val('');
                }
            }

            if (ctx.deptPenggunaHidden && ctx.deptPenggunaHidden.length && !isHO) {
                ctx.deptPenggunaHidden.val('');
            }
            if (ctx.jabatanDeptPenggunaHidden && ctx.jabatanDeptPenggunaHidden.length && !isHO) {
                ctx.jabatanDeptPenggunaHidden.val('');
            }

            if (!ctx.diketahuiSiteSelect.length || !ctx.disetujuiSiteInput.length) {
                updateDeptPengguna(mode);
                return;
            }

            ctx.diketahuiSiteSelect.prop('required', !isHO);
            ctx.disetujuiSiteInput.prop('required', !isHO);

            if (isHO) {
                ctx.diketahuiSiteSelect.html('<option value="">-- Pilih Diketahui --</option>').val('');
                ctx.disetujuiSiteInput.val('');
                if (ctx.jabatanDiketahuiSite.length) {
                    ctx.jabatanDiketahuiSite.val('');
                }
                if (ctx.jabatanDisetujuiSite.length) {
                    ctx.jabatanDisetujuiSite.val('');
                }
                updateDeptPengguna(mode);
                return;
            }

            var diketahuiSiteCandidates = getDiketahuiSiteCandidates(selectedPT);
            var selectedDiketahuiSite = (typeof presetDiketahuiSite !== 'undefined' && presetDiketahuiSite !== null) ?
                presetDiketahuiSite :
                ctx.diketahuiSiteSelect.val();
            var currentDisetujuiSite = (typeof presetDisetujuiSite !== 'undefined' && presetDisetujuiSite !== null) ?
                presetDisetujuiSite :
                ctx.disetujuiSiteInput.val();

            ctx.diketahuiSiteSelect.html('<option value="">-- Pilih Diketahui --</option>' + buildOptionHtml(diketahuiSiteCandidates, selectedDiketahuiSite).replace('<option value="">-- Pilih --</option>', ''));
            if (selectedDiketahuiSite) {
                ctx.diketahuiSiteSelect.val(selectedDiketahuiSite);
            }
            updateJabatanHidden(ctx.diketahuiSiteSelect, ctx.jabatanDiketahuiSite);

            ctx.disetujuiSiteInput.val(currentDisetujuiSite ? currentDisetujuiSite : '');
            updateDeptPengguna(mode);
        }

        function populateActorSelects(mode, presetDepartemen, presetDiketahuiSite, presetDisetujuiSite) {
            var ctx = getFormContext(mode);
            var selectedPT = ctx.pt;

            if (!selectedPT) {
                if (ctx.departemenSelect && ctx.departemenSelect.length) {
                    ctx.departemenSelect.val('').prop('required', false);
                }
                if (ctx.deptPenggunaHidden && ctx.deptPenggunaHidden.length) {
                    ctx.deptPenggunaHidden.val('');
                }
                if (ctx.jabatanDeptPenggunaHidden && ctx.jabatanDeptPenggunaHidden.length) {
                    ctx.jabatanDeptPenggunaHidden.val('');
                }
                if (ctx.deptRow && ctx.deptRow.length) {
                    ctx.deptRow.addClass('d-none');
                }

                if (ctx.siteExtraRow && ctx.siteExtraRow.length) {
                    ctx.siteExtraRow.addClass('d-none');
                }
                if (ctx.diketahuiSiteSelect && ctx.diketahuiSiteSelect.length) {
                    ctx.diketahuiSiteSelect.html('<option value="">-- Pilih Diketahui --</option>').prop('required', false).val('');
                }
                if (ctx.disetujuiSiteInput && ctx.disetujuiSiteInput.length) {
                    ctx.disetujuiSiteInput.prop('required', false).val('');
                }
                if (ctx.jabatanDiketahuiSite && ctx.jabatanDiketahuiSite.length) {
                    ctx.jabatanDiketahuiSite.val('');
                }
                if (ctx.jabatanDisetujuiSite && ctx.jabatanDisetujuiSite.length) {
                    ctx.jabatanDisetujuiSite.val('');
                }
                return;
            }

            if (ctx.departemenSelect && ctx.departemenSelect.length) {
                if (typeof presetDepartemen !== 'undefined' && presetDepartemen !== null) {
                    ctx.departemenSelect.val(presetDepartemen);
                }
            }

            syncSiteExtraFields(mode, presetDiketahuiSite, presetDisetujuiSite);
        }

        function getBarangStorage(mode) {
            return mode === 'edit' ? selectedBarangEdit : selectedBarangInput;
        }

        function setBarangStorage(mode, arr) {
            if (mode === 'edit') {
                selectedBarangEdit = arr;
            } else {
                selectedBarangInput = arr;
            }
        }

        function getBarangTableBodyId(mode) {
            return mode === 'edit' ? 'selectedBarangBodyEdit' : 'selectedBarangBodyInput';
        }

        function cleanValue(val, def) {
            if (val === null || typeof val === 'undefined') {
                return def;
            }
            val = String(val).replace(/\s+/g, ' ').trim();
            return val === '' ? def : val;
        }

        function cleanLongText(val, def) {
            if (val === null || typeof val === 'undefined') {
                return def;
            }
            val = String(val).replace(/\r\n/g, '\n').replace(/\r/g, '\n').trim();
            return val === '' ? def : val;
        }

        function formatRupiah(value) {
            var num = parseInt(String(value || '0').replace(/[^\d]/g, ''), 10);
            if (isNaN(num) || num <= 0) {
                return '-';
            }
            return 'Rp ' + String(num).replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        }

        function normalizeBarang(item) {
            return {
                id_pt: cleanValue(item.id_pt, '0'),
                pt_asal: cleanValue(item.pt_asal, '-'),
                po: cleanValue(item.po, '-'),
                coa: cleanValue(item.coa, '-'),
                kode_assets: cleanValue(item.kode_assets, '-'),
                merk: cleanValue(item.merk, '-'),
                sn: cleanValue(item.sn, '-'),
                user: cleanValue(item.user, '-'),
                harga_beli: cleanValue(item.harga_beli, '0'),
                tahun_perolehan: cleanValue(item.tahun_perolehan, '-'),
                alasan_penghapusan: cleanLongText(item.alasan_penghapusan, ''),
                kondisi: cleanLongText(item.kondisi, '')
            };
        }

        function barangUniqueKey(item) {
            return [
                item.pt_asal,
                item.po,
                item.coa,
                item.kode_assets,
                item.merk,
                item.sn,
                item.user
            ].join('||');
        }

        function updateBarangField(mode, index, field, value) {
            var arr = getBarangStorage(mode);
            if (typeof arr[index] === 'undefined') {
                return;
            }
            arr[index][field] = value;
            setBarangStorage(mode, arr);
        }

        function renderSelectedBarang(mode) {
            var bodyId = getBarangTableBodyId(mode);
            var tbody = document.getElementById(bodyId);
            if (!tbody) {
                return;
            }

            var arr = getBarangStorage(mode);
            var html = '';

            if (!arr.length) {
                html = '<tr class="empty-row"><td colspan="13">Belum ada barang dipilih.</td></tr>';
            } else {
                for (var i = 0; i < arr.length; i++) {
                    var item = normalizeBarang(arr[i]);

                    html += '<tr>' +
                        '<td>' + (i + 1) + '<input type="hidden" name="barang_id_pt[]" value="' + escapeHtml(item.id_pt) + '"></td>' +

                        '<td>' + escapeHtml(item.pt_asal) +
                        '<input type="hidden" name="barang_pt_asal[]" value="' + escapeHtml(item.pt_asal) + '"></td>' +

                        '<td>' + escapeHtml(item.po) +
                        '<input type="hidden" name="barang_po[]" value="' + escapeHtml(item.po) + '"></td>' +

                        '<td>' + escapeHtml(item.coa) +
                        '<input type="hidden" name="barang_coa[]" value="' + escapeHtml(item.coa) + '"></td>' +

                        '<td>' + escapeHtml(item.kode_assets) +
                        '<input type="hidden" name="barang_kode_assets[]" value="' + escapeHtml(item.kode_assets) + '"></td>' +

                        '<td>' + escapeHtml(item.merk) +
                        '<input type="hidden" name="barang_merk[]" value="' + escapeHtml(item.merk) + '"></td>' +

                        '<td>' + escapeHtml(item.sn) +
                        '<input type="hidden" name="barang_sn[]" value="' + escapeHtml(item.sn) + '"></td>' +

                        '<td>' + escapeHtml(item.user) +
                        '<input type="hidden" name="barang_user[]" value="' + escapeHtml(item.user) + '"></td>' +

                        '<td>' + formatRupiah(item.harga_beli) +
                        '<input type="hidden" name="barang_harga_beli[]" value="' + escapeHtml(item.harga_beli) + '"></td>' +

                        '<td>' + escapeHtml(item.tahun_perolehan) +
                        '<input type="hidden" name="barang_tahun_perolehan[]" value="' + escapeHtml(item.tahun_perolehan) + '"></td>' +

                        '<td style="min-width:220px;">' +
                        '<textarea name="barang_alasan_penghapusan[]" class="form-control form-control-sm" rows="2" required ' +
                        'oninput="updateBarangField(\'' + mode + '\',' + i + ',\'alasan_penghapusan\', this.value)">' +
                        escapeHtml(item.alasan_penghapusan) +
                        '</textarea></td>' +

                        '<td style="min-width:220px;">' +
                        '<textarea name="barang_kondisi[]" class="form-control form-control-sm" rows="2" required ' +
                        'oninput="updateBarangField(\'' + mode + '\',' + i + ',\'kondisi\', this.value)">' +
                        escapeHtml(item.kondisi) +
                        '</textarea></td>' +

                        '<td><button type="button" class="btn btn-danger btn-sm" onclick="hapusBarangTerpilih(\'' + mode + '\',' + i + ')"><i class="bi bi-trash3-fill"></i></button></td>' +
                        '</tr>';
                }
            }

            tbody.innerHTML = html;
        }

        function hapusBarangTerpilih(mode, index) {
            var arr = getBarangStorage(mode);
            arr.splice(index, 1);
            setBarangStorage(mode, arr);
            renderSelectedBarang(mode);
            syncCheckboxSelection();
        }

        function syncCheckboxSelection() {
            var arr = getBarangStorage(activeTarget);
            var keys = {};

            for (var i = 0; i < arr.length; i++) {
                keys[barangUniqueKey(arr[i])] = true;
            }

            $('#myTable2 tbody tr').each(function() {
                var item = normalizeBarang({
                    pt_asal: $(this).data('pt'),
                    po: $(this).data('po'),
                    coa: $(this).data('coa'),
                    kode_assets: $(this).data('kode'),
                    merk: $(this).data('merk'),
                    sn: $(this).data('sn'),
                    user: $(this).data('user'),
                    harga_beli: $(this).data('harga'),
                    tahun_perolehan: $(this).data('tahun')
                });

                var key = barangUniqueKey(item);
                $(this).find('.pilih-barang-checkbox').prop('checked', !!keys[key]);
            });
        }

        function tambahGambar(containerId, inputName, ketName) {
            var container = document.getElementById(containerId);
            if (!container) {
                return;
            }

            var wrapper = document.createElement('div');
            wrapper.className = 'custom-image-item';

            var input = document.createElement('input');
            input.type = 'file';
            input.name = inputName;
            input.accept = 'image/*';
            input.className = 'form-control mb-2';
            input.required = true;

            var ket = document.createElement('input');
            ket.type = 'text';
            ket.name = ketName;
            ket.className = 'form-control mb-2';
            ket.placeholder = 'Keterangan gambar (opsional)';

            var preview = document.createElement('img');
            preview.style.display = 'none';

            input.onchange = function() {
                var file = this.files[0];
                if (file) {
                    preview.src = URL.createObjectURL(file);
                    preview.style.display = 'block';
                }
            };

            var btnHapus = document.createElement('button');
            btnHapus.type = 'button';
            btnHapus.className = 'btn btn-danger btn-sm mt-2';
            btnHapus.innerHTML = '<i class="bi bi-trash3-fill"></i>';
            btnHapus.onclick = function() {
                if (preview.src && preview.src.indexOf('blob:') === 0) {
                    URL.revokeObjectURL(preview.src);
                }
                container.removeChild(wrapper);
            };

            wrapper.appendChild(input);
            wrapper.appendChild(ket);
            wrapper.appendChild(preview);
            wrapper.appendChild(btnHapus);
            container.appendChild(wrapper);
        }

function removeExistingImage(button, rowIndex) {
    var wrapper = button.closest('.custom-image-item');
    if (wrapper) {
        var fileInput = wrapper.querySelector('input[type="file"]');
        if (fileInput) {
            fileInput.value = '';
        }
        wrapper.style.display = 'none';
    }

    var hidden = document.getElementById('hapus_gambar_' + rowIndex);
    if (hidden) {
        hidden.value = 'hapus';
    }

    var flag = document.getElementById('gambar_change_flag_edit');
    if (flag) {
        flag.value = '1';
    }
}

function previewReplaceImage(input) {
    var file = input.files && input.files[0] ? input.files[0] : null;
    var preview = input.parentNode.querySelector('img');

    if (file && preview) {
        preview.src = URL.createObjectURL(file);
        preview.style.display = 'block';
    }

    if (file) {
        var flag = document.getElementById('gambar_change_flag_edit');
        if (flag) {
            flag.value = '1';
        }
    }
}

    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var alertBox = document.querySelector('.infoin-approval');
            if (alertBox) {
                setTimeout(function() {
                    alertBox.classList.add('fade-out');
                    alertBox.classList.remove('fade-in');
                }, 3000);

                setTimeout(function() {
                    alertBox.style.display = 'none';
                }, 3500);
            }
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var button = document.getElementById('tombolAkun');
            var box = document.getElementById('akunInfo');
            if (!button || !box) {
                return;
            }
            button.addEventListener('click', function() {
                if (box.classList.contains('display-state')) {
                    box.classList.remove('display-state');
                    setTimeout(function() {
                        box.classList.add('aktif');
                    }, 200);
                } else {
                    box.classList.remove('aktif');
                    setTimeout(function() {
                        box.classList.add('display-state');
                    }, 200);
                }
            });
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var open = document.getElementById('tombolInputPopup');
            var close = document.getElementById('tombolClosePopup');
            var box = document.getElementById('popupBoxInput');
            var background = document.getElementById('popupBG');
            var tabel = document.getElementById('custom-main');

            if (open) {
                open.addEventListener('click', function(e) {
                    e.preventDefault();
                    box.classList.add('aktifPopup');
                    background.classList.add('aktifPopup');
                    box.classList.add('scale-in-center');
                    box.classList.remove('scale-out-center');
                    background.classList.add('fade-in');
                    background.classList.remove('fade-out');
                    tabel.style.overflowY = 'hidden';
                });
            }

            if (close) {
                close.addEventListener('click', function(e) {
                    e.preventDefault();
                    box.classList.remove('aktifPopup');
                    background.classList.remove('aktifPopup');
                    tabel.style.overflowY = 'auto';
                });
            }

            if (background) {
                background.addEventListener('click', function() {
                    box.classList.remove('aktifPopup');
                    background.classList.remove('aktifPopup');
                    tabel.style.overflowY = 'auto';
                });
            }
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var tanggalInput = document.getElementById('tanggal');
            var nomorBaInput = document.getElementById('nomor_ba');
            var ptInput = document.getElementById('perusahaan');

            function updateNomorBA() {
                var tanggal = tanggalInput ? tanggalInput.value : '';
                var pt = ptInput ? ptInput.value : '';
                if (!tanggal || !pt) {
                    return;
                }

                fetch('ambil_nomor_ba.php?tanggal=' + encodeURIComponent(tanggal) + '&pt=' + encodeURIComponent(pt))
                    .then(function(response) {
                        return response.text();
                    })
                    .then(function(data) {
                        nomorBaInput.value = data;
                    })
                    .catch(function(err) {
                        console.error('Gagal mengambil nomor BA:', err);
                    });
            }

            if (tanggalInput) {
                tanggalInput.addEventListener('change', updateNomorBA);
            }
            // if (ptInput) {
            //     ptInput.addEventListener('change', function() {
            //         setIdPtBySelect('perusahaan', 'id_pt_input');
            //         updateNomorBA();
            //         populateActorSelects(
            //             'input',
            //             $('#input-pembuat').val(),
            //             $('#input-pemeriksa').val(),
            //             $('#input-diketahui-site').val(),
            //             $('#input-disetujui-site').val()
            //         );
            //         if ($.fn.DataTable.isDataTable('#myTable2')) {
            //             $('#myTable2').DataTable().draw();
            //         }
            //     });
            // }

            if (ptInput) {
                ptInput.addEventListener('change', function() {
                    setIdPtBySelect('perusahaan', 'id_pt_input');
                    updateNomorBA();
                    populateActorSelects(
                        'input',
                        $('#input-departemen').val(),
                        $('#input-diketahui-site').val(),
                        $('#input-disetujui-site').val()
                    );
                    if ($.fn.DataTable.isDataTable('#myTable2')) {
                        $('#myTable2').DataTable().draw();
                    }
                });
            }

            // setIdPtBySelect('perusahaan', 'id_pt_input');
            // populateActorSelects('input');
            // updateNomorBA();

            // $('#input-pembuat').on('change', function() {
            //     populateActorSelects(
            //         'input',
            //         $(this).val(),
            //         $('#input-pemeriksa').val(),
            //         $('#input-diketahui-site').val(),
            //         $('#input-disetujui-site').val()
            //     );
            // });

            // $('#input-pemeriksa').on('change', function() {
            //     updateJabatanHidden($('#input-pemeriksa'), $('#jabatan_pemeriksa_input'));
            // });

            // $('#input-diketahui-site').on('change', function() {
            //     updateJabatanHidden($('#input-diketahui-site'), $('#jabatan_diketahui1_site_input'));
            // });
            setIdPtBySelect('perusahaan', 'id_pt_input');
            populateActorSelects('input');
            updateNomorBA();

            $('#input-departemen').on('change', function() {
                updateDeptPengguna('input');
            });

            $('#input-diketahui-site').on('change', function() {
                updateJabatanHidden($('#input-diketahui-site'), $('#jabatan_diketahui1_site_input'));
            });
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var close = document.getElementById('tombolClosePopupDataBarang');
            var box = document.getElementById('popupBoxDataBarang');
            var background = document.getElementById('popupBG2');
            var gunakanBtn = document.getElementById('gunakanBarangTerpilih');

            function getBarangFromRow($row) {
                return normalizeBarang({
                    id_pt: $row.data('idpt'),
                    pt_asal: $row.data('pt'),
                    po: $row.data('po'),
                    coa: $row.data('coa'),
                    kode_assets: $row.data('kode'),
                    merk: $row.data('merk'),
                    sn: $row.data('sn'),
                    user: $row.data('user'),
                    harga_beli: $row.data('harga'),
                    tahun_perolehan: $row.data('tahun'),
                    alasan_penghapusan: '',
                    kondisi: ''
                });
            }

            function addBarangToStorage(mode, item) {
                var arr = getBarangStorage(mode);
                var key = barangUniqueKey(item);
                var found = false;
                var i;

                for (i = 0; i < arr.length; i++) {
                    if (barangUniqueKey(arr[i]) === key) {
                        found = true;
                        break;
                    }
                }

                if (!found) {
                    arr.push(item);
                    setBarangStorage(mode, arr);
                }
            }

            function removeBarangFromStorage(mode, item) {
                var arr = getBarangStorage(mode);
                var key = barangUniqueKey(item);
                var newArr = [];
                var i;

                for (i = 0; i < arr.length; i++) {
                    if (barangUniqueKey(arr[i]) !== key) {
                        newArr.push(arr[i]);
                    }
                }

                setBarangStorage(mode, newArr);
            }

            $(document).on('click', '.tombolDataBarangPopup', function(e) {
                e.preventDefault();
                activeTarget = $(this).data('target');

                box.classList.add('aktifPopup');
                background.classList.add('aktifPopup');
                box.classList.add('scale-in-center');
                box.classList.remove('scale-out-center');
                background.classList.add('fade-in');
                background.classList.remove('fade-out');

                if ($.fn.DataTable.isDataTable('#myTable2')) {
                    $('#myTable2').DataTable().draw(false);
                }

                syncCheckboxSelection();
            });

            function closePopupDataBarang(e) {
                if (e) {
                    e.preventDefault();
                }

                setTimeout(function() {
                    background.classList.remove('aktifPopup');
                    box.classList.remove('aktifPopup');
                }, 300);

                box.classList.remove('scale-in-center');
                box.classList.add('scale-out-center');
                background.classList.remove('fade-in');
                background.classList.add('fade-out');
            }

            if (close) {
                close.addEventListener('click', closePopupDataBarang);
            }

            if (background) {
                background.addEventListener('click', closePopupDataBarang);
            }

            // Checkbox berubah -> langsung update storage utama
            $(document).on('change', '.pilih-barang-checkbox', function() {
                var $row = $(this).closest('tr');
                var item = getBarangFromRow($row);

                if ($(this).is(':checked')) {
                    addBarangToStorage(activeTarget, item);
                } else {
                    removeBarangFromStorage(activeTarget, item);
                }
            });

            // Klik baris -> toggle checkbox -> trigger change
            $(document).on('click', '#myTable2 tbody tr', function(e) {
                if ($(e.target).is('input[type="checkbox"]')) {
                    return;
                }

                var checkbox = $(this).find('.pilih-barang-checkbox');
                checkbox.prop('checked', !checkbox.prop('checked')).trigger('change');
            });

            // Tombol gunakan -> JANGAN hitung ulang dari row visible
            if (gunakanBtn) {
                gunakanBtn.addEventListener('click', function() {
                    renderSelectedBarang(activeTarget);
                    closePopupDataBarang();
                });
            }

            // Saat DataTable redraw / pindah halaman / search, checklist disinkronkan lagi
            if ($.fn.DataTable.isDataTable('#myTable2')) {
                $('#myTable2').on('draw.dt', function() {
                    syncCheckboxSelection();
                });
            }
        });
    </script>

    <script>
        (function() {
            $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
                if (!settings.nTable || settings.nTable.id !== 'myTable2') {
                    return true;
                }
                var ptSelected = activeTarget === 'edit' ? $('#edit-pt').val() : $('#perusahaan').val();
                if (!ptSelected) {
                    return true;
                }
                var tr = settings.aoData[dataIndex].nTr;
                var rowPT = $(tr).data('pt') || '';
                return rowPT === ptSelected;
            });
        })();
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var close = document.getElementById('tombolClosePopupDelete');
            var box = document.getElementById('popupBoxDelete');
            var background = document.getElementById('popupBG');

            document.querySelectorAll('.tombolPopupDelete').forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    var selectedId = this.getAttribute('data-id');
                    var isPending = this.getAttribute('data-pending') === 'true';
                    var pendingValue = isPending ? 1 : 0;

                    document.getElementById('deleteId').value = selectedId;
                    document.getElementById('deletePending').value = pendingValue;

                    var alasanWrapper = document.getElementById('alasanWrapper');
                    var alasanHapus = document.getElementById('alasanHapus');
                    alasanWrapper.classList.add('d-none');
                    alasanHapus.required = false;
                    alasanHapus.value = '';

                    if (isPending) {
                        alasanWrapper.classList.remove('d-none');
                        alasanHapus.required = true;
                    }

                    var oldWarning = box.querySelector('.warning-approval');
                    if (oldWarning) {
                        oldWarning.remove();
                    }

                    if (isPending) {
                        var buttonWrapper = box.querySelector('.w-50.d-flex.justify-content-around');
                        var warning = document.createElement('p');
                        warning.className = 'warning-approval text-warning mt-3 text-center fs-7 border border-start-0 border-end-0 border-bottom-0';
                        warning.innerHTML = '<i class="bi bi-exclamation-triangle"></i> Surat sudah ada yang menyetujui, data yang akan dihapus butuh approval pihak terkait.';
                        buttonWrapper.insertAdjacentElement('afterend', warning);
                    }

                    box.classList.add('aktifPopup');
                    background.classList.add('aktifPopup');
                    box.classList.add('scale-in-center');
                    box.classList.remove('scale-out-center');
                    background.classList.add('fade-in');
                    background.classList.remove('fade-out');
                });
            });

            if (close) {
                close.addEventListener('click', function(e) {
                    e.preventDefault();
                    box.classList.remove('aktifPopup');
                    background.classList.remove('aktifPopup');
                });
            }
            if (background) {
                background.addEventListener('click', function() {
                    box.classList.remove('aktifPopup');
                    background.classList.remove('aktifPopup');
                });
            }
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            function renderEditForm(data, barangList, gambarList) {
                var body = document.getElementById('popupEditBody');
                if (!body) {
                    return;
                }

                // var isHOData = isHoPT(data.pt);
                // var editPembuatValue = isHOData ? (data.pembuat ? data.pembuat : '') : (data.pembuat_site ? data.pembuat_site : '');
                // var editPemeriksaValue = isHOData ? (data.pemeriksa ? data.pemeriksa : '') : (data.pemeriksa_site ? data.pemeriksa_site : '');
                // var editJabatanPembuatValue = isHOData ? (data.jabatan_pembuat ? data.jabatan_pembuat : '') : (data.jabatan_pembuat_site ? data.jabatan_pembuat_site : '');
                // var editJabatanPemeriksaValue = isHOData ? (data.jabatan_pemeriksa ? data.jabatan_pemeriksa : '') : (data.jabatan_pemeriksa_site ? data.jabatan_pemeriksa_site : '');
                // var editDiketahuiSiteValue = isHOData ? '' : (data.diketahui1_site ? data.diketahui1_site : '');
                // var editDisetujuiSiteValue = isHOData ? '' : (data.disetujui1_site ? data.disetujui1_site : '');
                // var editJabatanDiketahuiSiteValue = isHOData ? '' : (data.jabatan_diketahui1_site ? data.jabatan_diketahui1_site : '');
                // var editJabatanDisetujuiSiteValue = isHOData ? '' : (data.jabatan_disetujui1_site ? data.jabatan_disetujui1_site : '');

                var isHOData = isHoPT(data.pt);
                var editDepartemenValue = isHOData ? (data.departemen_pengguna ? data.departemen_pengguna : '') : '';
                var editDiketahuiSiteValue = isHOData ? '' : (data.diketahui1_site ? data.diketahui1_site : '');
                var editDisetujuiSiteValue = isHOData ? '' : (data.disetujui1_site ? data.disetujui1_site : '');
                var editJabatanDiketahuiSiteValue = isHOData ? '' : (data.jabatan_diketahui1_site ? data.jabatan_diketahui1_site : '');
                var editJabatanDisetujuiSiteValue = isHOData ? '' : (data.jabatan_disetujui1_site ? data.jabatan_disetujui1_site : '');

                var gambarHTML = '';
                for (var i = 0; i < gambarList.length; i++) {
                    var g = gambarList[i];
                    gambarHTML += '' +
                        '<div class="custom-image-item" data-row-index="' + i + '">' +
                        '   <input type="hidden" name="gambar_lama_id[]" value="' + escapeHtml(g.id ? g.id : 0) + '">' +
                        '   <input type="hidden" name="gambar_lama_path[]" value="' + escapeHtml(g.file_path ? g.file_path : '') + '">' +
                        '   <input type="hidden" name="hapus_gambar[' + i + ']" id="hapus_gambar_' + i + '" value="">' +
                        '   <input type="file" name="gambar_lama_file[' + i + ']" accept="image/*" class="form-control mb-2" onchange="previewReplaceImage(this)">' +
                        '   <input type="text" name="keterangan_gambar_lama[' + i + ']" class="form-control mb-2" placeholder="Keterangan gambar (opsional)" value="' + escapeHtml(g.keterangan ? g.keterangan : '') + '">' +
                        '   <img src="' + escapeHtml(g.file_path) + '" style="display:block;">' +
                        '   <button type="button" class="btn btn-danger btn-sm mt-2" onclick="removeExistingImage(this, ' + i + ')"><i class="bi bi-trash3-fill"></i></button>' +
                        '</div>';
                }

                body.innerHTML = '' +
                    '<form class="popupEdit d-flex flex-column" method="post" action="proses_edit.php" enctype="multipart/form-data">' +
                    
                    // '   <input type="hidden" name="id" value="' + escapeHtml(data.id) + '">' +
                    // '   <input type="hidden" name="gambar_change_flag" id="gambar_change_flag_edit" value="0">' +
                    // '   <input type="hidden" name="nama_pembuat" value="' + escapeHtml(data.nama_pembuat ? data.nama_pembuat : '') + '">' +
                    // '   <input type="hidden" name="id_pt" id="id_pt_edit" value="' + escapeHtml(data.id_pt ? data.id_pt : '') + '">' +
                    // '   <input type="hidden" name="jabatan_pembuat" id="jabatan_pembuat_edit" value="' + escapeHtml(editJabatanPembuatValue) + '">' +
                    // '   <input type="hidden" name="jabatan_pemeriksa" id="jabatan_pemeriksa_edit" value="' + escapeHtml(editJabatanPemeriksaValue) + '">' +
                    // '   <input type="hidden" name="jabatan_diketahui1_site" id="jabatan_diketahui1_site_edit" value="' + escapeHtml(editJabatanDiketahuiSiteValue) + '">' +
                    // '   <input type="hidden" name="jabatan_disetujui1_site" id="jabatan_disetujui1_site_edit" value="' + escapeHtml(editJabatanDisetujuiSiteValue) + '">' +

                    '   <input type="hidden" name="id" value="' + escapeHtml(data.id) + '">' +
                    '   <input type="hidden" name="gambar_change_flag" id="gambar_change_flag_edit" value="0">' +
                    '   <input type="hidden" name="nama_pembuat" value="' + escapeHtml(data.nama_pembuat ? data.nama_pembuat : '') + '">' +
                    '   <input type="hidden" name="id_pt" id="id_pt_edit" value="' + escapeHtml(data.id_pt ? data.id_pt : '') + '">' +
                    '   <input type="hidden" name="dept_pengguna" id="dept_pengguna_edit" value="' + escapeHtml(data.dept_pengguna ? data.dept_pengguna : '') + '">' +
                    '   <input type="hidden" name="jabatan_dept_pengguna" id="jabatan_dept_pengguna_edit" value="' + escapeHtml(data.jabatan_dept_pengguna ? data.jabatan_dept_pengguna : '') + '">' +
                    '   <input type="hidden" name="jabatan_diketahui1_site" id="jabatan_diketahui1_site_edit" value="' + escapeHtml(editJabatanDiketahuiSiteValue) + '">' +
                    '   <input type="hidden" name="jabatan_disetujui1_site" id="jabatan_disetujui1_site_edit" value="' + escapeHtml(editJabatanDisetujuiSiteValue) + '">' +
                    '   <div class="form-section mb-2">' +
                    '       <div class="row position-relative">' +
                    '           <div class="custom-input-form col-8">' +
                    '               <h3>Data Berita Acara Pemutihan</h3>' +
                    '               <div class="row">' +
                    '                   <div class="col-md-3 mb-3">' +
                    '                       <div class="input-group">' +
                    '                           <span class="input-group-text">Tanggal</span>' +
                    '                           <input class="form-control" type="date" name="tanggal" id="tanggal_edit" max="' + escapeHtml(new Date().toISOString().slice(0, 10)) + '" value="' + escapeHtml(data.tanggal ? data.tanggal : '') + '" required>' +
                    '                       </div>' +
                    '                   </div>' +
                    '                   <div class="col-md-3 mb-3">' +
                    '                       <div class="input-group">' +
                    '                           <span class="input-group-text">Nomor BA</span>' +
                    '                           <input type="text" class="form-control" maxlength="3" name="nomor_ba" id="nomor_ba_edit" value="' + escapeHtml(data.nomor_ba ? data.nomor_ba : '') + '" readonly>' +
                    '                       </div>' +
                    '                   </div>' +
                    '                   <div class="col-md-4 mb-3">' +
                    '                       <div class="input-group">' +
                    '                           <span class="input-group-text">PT</span>' +
                    '                           <select name="pt" id="edit-pt" class="form-select" required>' +
                    '                               <option value="">-- Pilih PT --</option>' +
                    buildEditPtOptions(data.pt) +
                    '                           </select>' +
                    '                       </div>' +
                    '                   </div>' +
                    '               </div>' +
                    '               <div class="row mt-1 border border-1 p-2 rounded-2 me-1">' +
                    '                   <div class="row pt-1 pb-2">' +
                    '                       <div class="col-12 d-flex flex-column align-items-start flex-wrap gap-2">' +
                    '                           <h5 class="m-0">Data Barang</h5>' +
                    '                           <div class="custom-btn-data-barang d-flex gap-2">' +
                    '                               <button type="button" class="tombolDataBarangPopup btn btn-primary" data-target="edit"><i class="bi bi-search"></i></button>' +
                    '                           </div>' +
                    '                       </div>' +
                    '                   </div>' +
                    '                   <div class="row">' +
                    '                       <div class="col-12">' +
                    '                           <div class="table-responsive border rounded p-2 bg-light">' +
                    '                               <table class="table table-bordered table-striped custom-selected-barang mb-0">' +
                    '                                   <thead>' +
                    '                                       <tr>' +
                    '                                           <th>No</th><th>PT Asal</th><th>PO</th><th>COA</th><th>Kode Asset</th><th>Merek</th><th>SN</th><th>User</th><th>Harga Beli</th><th>Tahun Perolehan</th><th>Alasan Penghapusan</th><th>Kondisi</th><th>Hapus</th>' +
                    '                                       </tr>' +
                    '                                   </thead>' +
                    '                                   <tbody id="selectedBarangBodyEdit"></tbody>' +
                    '                               </table>' +
                    '                           </div>' +
                    '                       </div>' +
                    '                   </div>' +
                    '               </div>' +
                    // '               <div class="row mt-3 border border-1 p-2 rounded-2 me-1">' +
                    // '                   <div class="row"><h5 class="mb-2">Data Pengguna</h5></div>' +
                    // '                   <div class="col-md-6 mb-3">' +
                    // '                       <div class="input-group">' +
                    // '                           <span class="input-group-text">Pembuat</span>' +
                    // '                           <select name="pembuat" id="edit-pembuat" class="form-select" required>' +
                    // '                               <option value="">-- Pilih Pembuat --</option>' +
                    // '                           </select>' +
                    // '                       </div>' +
                    // '                   </div>' +
                    // '                   <div class="col-md-6 mb-3">' +
                    // '                       <div class="input-group">' +
                    // '                           <span class="input-group-text">Pemeriksa</span>' +
                    // '                           <select name="pemeriksa" id="edit-pemeriksa" class="form-select" required>' +
                    // '                               <option value="">-- Pilih Pemeriksa --</option>' +
                    // '                           </select>' +
                    // '                       </div>' +
                    // '                   </div>' +

                    // '                   <div class="row d-none" id="site-extra-row-edit">' +
                    // '                       <div class="col-md-6 mb-3">' +
                    // '                           <div class="input-group">' +
                    // '                               <span class="input-group-text">Diketahui</span>' +
                    // '                               <select name="diketahui1_site" id="edit-diketahui-site" class="form-select">' +
                    // '                                   <option value="">-- Pilih Diketahui --</option>' +
                    // '                               </select>' +
                    // '                           </div>' +
                    // '                       </div>' +
                    // '                       <div class="col-md-6 mb-3">' +
                    // '                           <div class="input-group">' +
                    // '                               <span class="input-group-text">Disetujui</span>' +
                    // '                               <input type="text" name="disetujui1_site" id="edit-disetujui-site" class="form-control" value="' + escapeHtml(editDisetujuiSiteValue) + '" placeholder="Kepala Project">' +
                    // '                           </div>' +
                    // '                       </div>' +
                    // '                   </div>' +
                    // '               </div>' +

                    '               <div class="row mt-3 border border-1 p-2 rounded-2 me-1">' +
                    '                   <div class="row"><h5 class="mb-2">Data Pengguna</h5></div>' +

                    '                   <div class="col-md-6 mb-3 d-none" id="dept-row-edit">' +
                    '                       <div class="input-group">' +
                    '                           <span class="input-group-text">Departemen</span>' +
                    '                           <select name="departemen_pengguna" id="edit-departemen" class="form-select">' +
                    '                               <option value="">-- Pilih Departemen --</option>' +
                    '                               <option value="HRO"' + ((data.departemen_pengguna && data.departemen_pengguna === 'HRO') ? ' selected' : '') + '>HRO</option>' +
                    '                               <option value="MIS"' + ((data.departemen_pengguna && data.departemen_pengguna === 'MIS') ? ' selected' : '') + '>MIS</option>' +
                    '                           </select>' +
                    '                       </div>' +
                    '                   </div>' +

                    '                   <div class="row d-none" id="site-extra-row-edit">' +
                    '                       <div class="col-md-6 mb-3">' +
                    '                           <div class="input-group">' +
                    '                               <span class="input-group-text">Diketahui</span>' +
                    '                               <select name="diketahui1_site" id="edit-diketahui-site" class="form-select">' +
                    '                                   <option value="">-- Pilih Diketahui --</option>' +
                    '                               </select>' +
                    '                           </div>' +
                    '                       </div>' +
                    '                       <div class="col-md-6 mb-3">' +
                    '                           <div class="input-group">' +
                    '                               <span class="input-group-text">Disetujui</span>' +
                    '                               <input type="text" name="disetujui1_site" id="edit-disetujui-site" class="form-control" value="' + escapeHtml(editDisetujuiSiteValue) + '" placeholder="Kepala Project">' +
                    '                           </div>' +
                    '                       </div>' +
                    '                   </div>' +
                    '               </div>' +
                    '           </div>' +
                    '           <div class="custom-input-gambar-section col-4">' +
                    '               <h3>Gambar</h3>' +
                    '               <div class="custom-input-gambar border border-2 rounded-3 p-2" style="height:485px; overflow-y:auto;">' +
                    '                   <div class="d-flex flex-column">' +
                    '                       <div id="edit-gambar-container">' + gambarHTML + '</div>' +
                    '                       <button type="button" class="btn btn-primary w-75 align-self-center mb-1" onclick="tambahGambar(\'edit-gambar-container\', \'gambar_baru[]\', \'keterangan_gambar_baru[]\')">+ Tambah Gambar</button>' +
                    '                   </div>' +
                    '               </div>' +
                    '               <div class="mt-3">' +
                    '                   <div class="input-group">' +
                    '                       <span class="input-group-text">Alasan perubahan</span>' +
                    '                       <textarea name="alasan_perubahan" class="form-control" rows="2" required></textarea>' +
                    '                   </div>' +
                    '               </div>' +
                    '           </div>' +
                    '       </div>' +
                    '   </div>' +
                    '   <div class="footer-form d-flex w-100 justify-content-between">' +
                    '       <h5 class="m-0 mt-3" style="color: darkgray;">*Formulir ini untuk membuat berita acara pemutihan aset</h5>' +
                    '       <div class="w-25 align-self-end">' +
                    '           <input class="w-100 mt-0" type="submit" value="Simpan">' +
                    '       </div>' +
                    '   </div>' +
                    '</form>';

                var normalizedBarang = [];
                for (var b = 0; b < barangList.length; b++) {
                    normalizedBarang.push(normalizeBarang({
                        id_pt: barangList[b].id_pt,
                        pt_asal: barangList[b].pt_asal,
                        po: barangList[b].po,
                        coa: barangList[b].coa,
                        kode_assets: barangList[b].kode_assets,
                        merk: barangList[b].merk,
                        sn: barangList[b].sn,
                        user: barangList[b].user,
                        harga_beli: barangList[b].harga_beli,
                        tahun_perolehan: barangList[b].tahun_perolehan,
                        alasan_penghapusan: barangList[b].alasan_penghapusan,
                        kondisi: barangList[b].kondisi
                    }));
                }
                selectedBarangEdit = normalizedBarang;
                renderSelectedBarang('edit');

                // setIdPtBySelect('edit-pt', 'id_pt_edit');
                // populateActorSelects(
                //     'edit',
                //     editPembuatValue,
                //     editPemeriksaValue,
                //     editDiketahuiSiteValue,
                //     editDisetujuiSiteValue
                // );

                // $('#edit-pt').on('change', function() {
                //     setIdPtBySelect('edit-pt', 'id_pt_edit');
                //     populateActorSelects(
                //         'edit',
                //         $('#edit-pembuat').val(),
                //         $('#edit-pemeriksa').val(),
                //         $('#edit-diketahui-site').val(),
                //         $('#edit-disetujui-site').val()
                //     );
                //     if ($.fn.DataTable.isDataTable('#myTable2')) {
                //         $('#myTable2').DataTable().draw();
                //     }
                // });

                // $('#edit-pembuat').on('change', function() {
                //     populateActorSelects(
                //         'edit',
                //         $(this).val(),
                //         $('#edit-pemeriksa').val(),
                //         $('#edit-diketahui-site').val(),
                //         $('#edit-disetujui-site').val()
                //     );
                // });

                // $('#edit-pemeriksa').on('change', function() {
                //     updateJabatanHidden($('#edit-pemeriksa'), $('#jabatan_pemeriksa_edit'));
                // });

                // $(document).on('change', '#edit-diketahui-site', function() {
                //     updateJabatanHidden($('#edit-diketahui-site'), $('#jabatan_diketahui1_site_edit'));
                // });

                setIdPtBySelect('edit-pt', 'id_pt_edit');
                populateActorSelects(
                    'edit',
                    editDepartemenValue,
                    editDiketahuiSiteValue,
                    editDisetujuiSiteValue
                );

                $('#edit-pt').on('change', function() {
                    setIdPtBySelect('edit-pt', 'id_pt_edit');
                    populateActorSelects(
                        'edit',
                        $('#edit-departemen').val(),
                        $('#edit-diketahui-site').val(),
                        $('#edit-disetujui-site').val()
                    );
                    if ($.fn.DataTable.isDataTable('#myTable2')) {
                        $('#myTable2').DataTable().draw();
                    }
                });

                $(document).on('change', '#edit-departemen', function() {
                    updateDeptPengguna('edit');
                });

                $(document).on('change', '#edit-diketahui-site', function() {
                    updateJabatanHidden($('#edit-diketahui-site'), $('#jabatan_diketahui1_site_edit'));
                });
            }

            function buildEditPtOptions(selectedPt) {
                var html = '';
                for (var i = 0; i < PT_LIST.length; i++) {
                    var selected = String(selectedPt || '') === String(PT_LIST[i]) ? ' selected' : '';
                    html += '<option value="' + escapeHtml(PT_LIST[i]) + '"' + selected + '>' + escapeHtml(PT_LIST[i]) + '</option>';
                }
                return html;
            }

            var box = document.getElementById('popupBoxEdit');
            var bg = document.getElementById('popupBG');
            var closeBtn = document.getElementById('tombolClosePopupEdit');
            var titleEl = document.getElementById('popupEditTitle');
            var tabel = document.getElementById('custom-main');

            function openPopup() {
                box.classList.add('aktifPopup');
                bg.classList.add('aktifPopup');
                box.classList.add('scale-in-center');
                box.classList.remove('scale-out-center');
                bg.classList.add('fade-in');
                bg.classList.remove('fade-out');
                tabel.style.overflowY = 'hidden';
            }

            function closePopup(e) {
                if (e) {
                    e.preventDefault();
                }
                document.getElementById('popupEditBody').innerHTML = '';
                titleEl.textContent = 'Edit Berita Acara';
                box.classList.remove('aktifPopup');
                bg.classList.remove('aktifPopup');
                tabel.style.overflowY = 'auto';
            }

            closeBtn.addEventListener('click', closePopup);
            bg.addEventListener('click', function() {
                if (box.classList.contains('aktifPopup')) {
                    closePopup();
                }
            });

            $(document).on('click', '.tombolPopupEdit', function(e) {
                e.preventDefault();
                var id = $(this).data('id');
                if (!id) {
                    return;
                }
                fetch('get_edit_ba_pemutihan.php?id=' + encodeURIComponent(id), {
                        cache: 'no-store'
                    })
                    .then(function(resp) {
                        if (!resp.ok) {
                            throw new Error('HTTP ' + resp.status);
                        }
                        return resp.json();
                    })
                    .then(function(res) {
                        if (res.error) {
                            throw new Error(res.error);
                        }
                        if (!res || !res.data) {
                            throw new Error('Data tidak ditemukan');
                        }
                        renderEditForm(res.data, res.barangList ? res.barangList : [], res.gambarList ? res.gambarList : []);
                        // titleEl.textContent = 'Edit Berita Acara ' + (res.data.nomor_ba ? res.data.nomor_ba : '');
                        titleEl.textContent = 'Edit Berita Acara ';
                        openPopup();
                    })
                    .catch(function(err) {
                        document.getElementById('popupEditBody').innerHTML = '<div class="alert alert-danger">Gagal memuat data: ' + escapeHtml(err.message) + '</div>';
                        titleEl.textContent = 'Edit Berita Acara';
                        openPopup();
                    });
            });
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var popupBox = document.getElementById('popupBoxDetail');
            var popupBody = document.getElementById('popupDetailBody');
            var closeBtn = document.getElementById('tombolClosePopupDetail');
            var popupBG = document.getElementById('popupBG');
            var tabel = document.getElementById('custom-main');
            var titleEl = document.getElementById('popupDetailTitle');

            function approvalBadge(value) {
                if (parseInt(value, 10) === 1) {
                    return '<span class="border fw-bold bg-success-subtle border-success-subtle text-success" style="border-radius:6px;padding:6px 12px;">Disetujui</span>';
                }
                return '<span class="border fw-bold bg-warning-subtle border-warning-subtle text-warning" style="border-radius:6px;padding:6px 12px;">Menunggu</span>';
            }

            function openPopup() {
                popupBox.classList.add('aktifPopup');
                popupBG.classList.add('aktifPopup');
                popupBox.classList.add('scale-in-center');
                popupBox.classList.remove('scale-out-center');
                popupBG.classList.add('fade-in');
                popupBG.classList.remove('fade-out');
                tabel.style.overflowY = 'hidden';
            }

            function closePopup() {
                popupBody.innerHTML = '';
                popupBox.classList.remove('aktifPopup');
                popupBG.classList.remove('aktifPopup');
                popupBox.classList.remove('scale-in-center');
                popupBox.classList.add('scale-out-center');
                popupBG.classList.remove('fade-in');
                popupBG.classList.add('fade-out');
                tabel.style.overflowY = 'auto';
            }

            function buildApprovalTable(data) {
                var isHO = isHoPT(data.pt);
                var actors = [];

                if (isHO) {
                    actors = [{
                            label: 'Pembuat',
                            nama: data.pembuat,
                            jabatan: 'Staf MIS',
                            approval: data.approval_1
                        },
                        {
                            label: 'Pemeriksa',
                            nama: data.pemeriksa,
                            jabatan: 'Staf MIS',
                            approval: data.approval_2
                        },
                        {
                            label: 'Diketahui 1',
                            nama: data.diketahui1,
                            jabatan: 'Dept. Head MIS',
                            approval: data.approval_3
                        },
                        {
                            label: 'Diketahui 2',
                            nama: data.diketahui2,
                            jabatan: 'Dept. Head HRO',
                            approval: data.approval_4
                        },
                        {
                            label: 'Diketahui 3',
                            nama: data.diketahui3,
                            jabatan: 'Dept. Head HRD',
                            approval: data.approval_5
                        },
                        {
                            label: 'Dibukukan',
                            nama: data.dibukukan,
                            jabatan: 'Direktur Finance',
                            approval: data.approval_6
                        },
                        {
                            label: 'Disetujui 1',
                            nama: data.disetujui1,
                            jabatan: 'Direktur MIS',
                            approval: data.approval_7
                        },
                        {
                            label: 'Disetujui 2',
                            nama: data.disetujui2,
                            jabatan: 'Direktur HRD & Umum',
                            approval: data.approval_8
                        },
                        {
                            label: 'Disetujui 3',
                            nama: data.disetujui3,
                            jabatan: 'Vice CEO',
                            approval: data.approval_9
                        }
                    ];
                } else {
                    actors = [{
                            label: 'Pembuat',
                            nama: data.pembuat_site,
                            jabatan: data.jabatan_pembuat_site ? data.jabatan_pembuat_site : '-',
                            approval: data.approval_1
                        },
                        {
                            label: 'Pemeriksa',
                            nama: data.pemeriksa_site,
                            jabatan: data.jabatan_pemeriksa_site ? data.jabatan_pemeriksa_site : '-',
                            approval: data.approval_2
                        },
                        {
                            label: 'Diketahui',
                            nama: data.diketahui1_site,
                            jabatan: data.jabatan_diketahui1_site ? data.jabatan_diketahui1_site : '-',
                            approval: data.approval_3
                        },
                        {
                            label: 'Disetujui',
                            nama: data.disetujui1_site,
                            jabatan: data.jabatan_disetujui1_site ? data.jabatan_disetujui1_site : '-',
                            approval: data.approval_4
                        },
                        {
                            label: 'Diketahui 2',
                            nama: data.diketahui2_site,
                            jabatan: 'Dept. Head MIS',
                            approval: data.approval_5
                        },
                        {
                            label: 'Diperiksa',
                            nama: data.diperiksa_site,
                            jabatan: 'Dept. Head HRO',
                            approval: data.approval_6
                        },
                        {
                            label: 'Dibukukan',
                            nama: data.dibukukan,
                            jabatan: 'Direktur Finance',
                            approval: data.approval_7
                        },
                        {
                            label: 'Disetujui 1',
                            nama: data.disetujui1,
                            jabatan: 'Direktur MIS',
                            approval: data.approval_8
                        },
                        {
                            label: 'Disetujui 2',
                            nama: data.disetujui2,
                            jabatan: 'Direktur HRD & Umum',
                            approval: data.approval_9
                        },
                        {
                            label: 'Disetujui 3',
                            nama: data.disetujui3,
                            jabatan: 'Vice CEO',
                            approval: data.approval_10
                        },
                        {
                            label: 'Mengetahui',
                            nama: data.mengetahui_site,
                            jabatan: data.jabatan_mengetahui_site ? data.jabatan_mengetahui_site : '-',
                            approval: data.approval_11
                        }
                    ];
                }

                var header = '';
                var jabatan = '';
                var status = '';

                for (var i = 0; i < actors.length; i++) {
                    if (actors[i].nama && String(actors[i].nama) !== '-') {
                        header += '<th>' + escapeHtml(actors[i].label) + '</th>';
                        jabatan += '<td>' + escapeHtml(actors[i].jabatan ? actors[i].jabatan : '-') + '</td>';
                        status += '<td>' + approvalBadge(actors[i].approval) + '</td>';
                    }
                }

                if (!header) {
                    return '';
                }

                return '' +
                    '<div class="custom-detail-approval">' +
                    '   <table class="custom-detail-approval-child table w-auto table-approval">' +
                    '       <thead><tr>' + header + '</tr></thead>' +
                    '       <tbody><tr>' + jabatan + '</tr><tr>' + status + '</tr></tbody>' +
                    '   </table>' +
                    '</div>';
            }

            $(document).on('click', '.btn-detail-ba-pemutihan', function(e) {
                e.preventDefault();
                var id = $(this).data('id');
                if (!id) {
                    return;
                }
                fetch('get_detail.php?id=' + encodeURIComponent(id), {
                        cache: 'no-store'
                    })
                    .then(function(resp) {
                        if (!resp.ok) {
                            throw new Error('HTTP ' + resp.status);
                        }
                        return resp.json();
                    })
                    .then(function(res) {
                        if (res.error) {
                            throw new Error(res.error);
                        }

                        var data = res.data ? res.data : {};
                        var barangList = res.barangList ? res.barangList : [];
                        var gambarList = res.gambarList ? res.gambarList : [];
                        var dataHistory = res.data_history ? res.data_history : [];
                        var romawi = formatRomawiMonth(data.tanggal);
                        var tahun = getYearFromDate(data.tanggal);

                        var html = '<h2>Detail Data Pemutihan ' + escapeHtml(data.nomor_ba ? data.nomor_ba : '') + '/BAP/MIS/' + escapeHtml(romawi) + '/' + escapeHtml(tahun) + '</h2>';
                        html += buildApprovalTable(data);
                        html += '' +
                            '<div class="custom-detail-container d-flex gap-2 h-100">' +
                            '   <div class="custom-detail-table w-75">' +
                            '       <table class="custom-detail-table-child table table-bordered table-striped" style="width:100%;">' +
                            '           <tbody>' +
                            '               <tr><th style="font-size:14px;width:20%;min-width:150px;">Nomor BA</th><td style="font-size:14px;">' + escapeHtml(data.nomor_ba ? data.nomor_ba : '-') + '</td></tr>' +
                            '               <tr><th style="font-size:14px;width:20%;min-width:150px;">Tanggal</th><td style="font-size:14px;">' + escapeHtml(data.tanggal ? data.tanggal : '-') + '</td></tr>' +
                            '               <tr><th style="font-size:14px;width:20%;min-width:150px;">Lokasi</th><td style="font-size:14px;">' + escapeHtml(data.pt ? data.pt : '-') + '</td></tr>' +
                            '           </tbody>' +
                            '       </table>' +
                            '       <div class="table-responsive border rounded p-2 mb-2">' +
                            '           <h6>Data Barang</h6>' +
                            '           <table class="table table-bordered table-striped mb-0">' +
                            '               <thead>' +
                            '                   <tr><th>No</th><th>PT Asal</th><th>PO</th><th>COA</th><th>Kode Asset</th><th>Merk</th><th>SN</th><th>User</th><th>Harga Beli</th><th>Tahun Perolehan</th><th>Alasan Penghapusan</th><th>Kondisi</th></tr>' +
                            '               </thead>' +
                            '               <tbody>';

                        if (barangList.length) {
                            for (var i = 0; i < barangList.length; i++) {
                                html += '<tr>' +
                                    '<td>' + (i + 1) + '</td>' +
                                    '<td>' + escapeHtml(barangList[i].pt_asal ? barangList[i].pt_asal : '-') + '</td>' +
                                    '<td>' + escapeHtml(barangList[i].po ? barangList[i].po : '-') + '</td>' +
                                    '<td>' + escapeHtml(barangList[i].coa ? barangList[i].coa : '-') + '</td>' +
                                    '<td>' + escapeHtml(barangList[i].kode_assets ? barangList[i].kode_assets : '-') + '</td>' +
                                    '<td>' + escapeHtml(barangList[i].merk ? barangList[i].merk : '-') + '</td>' +
                                    '<td>' + escapeHtml(barangList[i].sn ? barangList[i].sn : '-') + '</td>' +
                                    '<td>' + escapeHtml(barangList[i].user ? barangList[i].user : '-') + '</td>' +
                                    '<td>' + formatRupiah(barangList[i].harga_beli ? barangList[i].harga_beli : 0) + '</td>' +
                                    '<td>' + escapeHtml(barangList[i].tahun_perolehan ? barangList[i].tahun_perolehan : '-') + '</td>' +
                                    '<td>' + escapeHtml(barangList[i].alasan_penghapusan ? barangList[i].alasan_penghapusan : '-') + '</td>' +
                                    '<td>' + escapeHtml(barangList[i].kondisi ? barangList[i].kondisi : '-') + '</td>' +
                                    '</tr>';
                            }
                        } else {
                            html += '<tr><td colspan="12">Tidak ada data barang.</td></tr>';
                        }

                        html += '' +
                            '               </tbody>' +
                            '           </table>' +
                            '       </div>' +
                            '   </div>' +
                            '   <div class="custom-detail-gambar w-50 d-flex border rounded-1 mb-1 overflow-auto p-2" style="height:490px;">';

                        if (gambarList.length) {
                            html += '<div style="display:flex;flex-wrap:wrap;gap:10px;height:max-content;width:100%;">';
                            for (var g = 0; g < gambarList.length; g++) {
                                html += '<div class="custom-gambar-detail">' +
                                    '<img src="' + escapeHtml(gambarList[g].file_path ? gambarList[g].file_path : gambarList[g]) + '" style="max-width:100%;height:auto;display:block;">' +
                                    '<p class="small text-muted mt-1 mb-0">' + escapeHtml(gambarList[g].keterangan ? gambarList[g].keterangan : '') + '</p>' +
                                    '</div>';
                            }
                            html += '</div>';
                        } else {
                            html += 'Tidak ada gambar.';
                        }

                        html += '</div></div>' +
                            '<div class="custom-detail-histori w-50" style="height:max-content; min-width:200px">' +
                            '   <div class="w-auto"><h6>Histori & Pending Perubahan</h6></div>' +
                            '   <table id="popupDetailTable" class="table table-bordered table-striped" style="font-size:16px; width:auto; table-layout:auto; white-space:nowrap;">' +
                            '       <thead>' +
                            '           <tr>' +
                            '               <th class="text-start">Tanggal Edit</th>' +
                            '               <th class="text-start">Status</th>' +
                            '               <th class="text-start">Alasan Edit</th>' +
                            '               <th class="text-start">Alasan Tolak</th>' +
                            '               <th class="text-start">Tanggal Surat</th>' +
                            '               <th class="text-start">Nomor Surat</th>' +
                            '           </tr>' +
                            '       </thead>' +
                            '       <tbody>';

                        if (dataHistory.length) {
                            for (var h = 0; h < dataHistory.length; h++) {
                                var rowColor = '';
                                var textColor = '';
                                if (parseInt(dataHistory[h].pending_status, 10) === 1) {
                                    rowColor = 'background-color: rgba(255, 234, 0, 0.5) !important;';
                                    textColor = 'color: #856404 !important;';
                                } else if (parseInt(dataHistory[h].pending_status, 10) === 2) {
                                    rowColor = 'background-color: #f8d7da !important;';
                                    textColor = 'color: #721c24 !important;';
                                }
                                html += '<tr>' +
                                    '<td class="text-start" style="' + rowColor + ' ' + textColor + '">' + escapeHtml(dataHistory[h].created_at ? dataHistory[h].created_at : '-') + '</td>' +
                                    '<td class="text-start" style="' + rowColor + ' ' + textColor + '">' + escapeHtml(dataHistory[h].pending_status_nama ? dataHistory[h].pending_status_nama : (dataHistory[h].status_nama ? dataHistory[h].status_nama : '-')) + '</td>' +
                                    '<td class="text-start" style="' + rowColor + ' ' + textColor + '">' + escapeHtml(dataHistory[h].alasan_edit ? dataHistory[h].alasan_edit : '-') + '</td>' +
                                    '<td class="text-start" style="' + rowColor + ' ' + textColor + '">' + escapeHtml(dataHistory[h].alasan_tolak ? dataHistory[h].alasan_tolak : '-') + '</td>' +
                                    '<td class="text-start" style="' + rowColor + ' ' + textColor + '">' + escapeHtml(dataHistory[h].tanggal ? dataHistory[h].tanggal : '-') + '</td>' +
                                    '<td class="text-start" style="' + rowColor + ' ' + textColor + '">' + escapeHtml(dataHistory[h].nomor_ba ? dataHistory[h].nomor_ba : '-') + '</td>' +
                                    '</tr>';
                            }
                        } else {
                            html += '<tr>' +
                                '<td class="text-start">-</td>' +
                                '<td class="text-start">-</td>' +
                                '<td class="text-start">Belum ada histori perubahan.</td>' +
                                '<td class="text-start">-</td>' +
                                '<td class="text-start">-</td>' +
                                '<td class="text-start">-</td>' +
                                '</tr>';
                        }

                        html += '</tbody></table></div>';
                        popupBody.innerHTML = html;
                        titleEl.textContent = 'Detail Berita Acara';
                        openPopup();

                        if ($.fn.DataTable) {
                            $('#popupDetailTable').DataTable({
                                paging: false,
                                searching: false,
                                info: false,
                                ordering: false,
                                scrollY: '410px',
                                scrollCollapse: true,
                                autoWidth: true,
                                language: {
                                    url: '../assets/json/id.json'
                                }
                            });
                        }
                    })
                    .catch(function(err) {
                        popupBody.innerHTML = '<div class="alert alert-danger">Gagal memuat data: ' + escapeHtml(err.message) + '</div>';
                        openPopup();
                    });
            });

            closeBtn.addEventListener('click', function(e) {
                e.preventDefault();
                closePopup();
            });

            popupBG.addEventListener('click', function() {
                if (popupBox.classList.contains('aktifPopup')) {
                    closePopup();
                }
            });
        });
    </script>

    <script>
        $(document).ready(function() {
            $('#myTable').DataTable({
                responsive: true,
                autoWidth: true,
                language: {
                    url: '../assets/json/id.json'
                },
                columnDefs: [{
                    targets: -1,
                    orderable: false
                }],
                initComplete: function() {
                    $('#tableSkeleton').fadeOut(200, function() {
                        $('#tabelUtama').fadeIn(200);
                    });
                }
            });

            $('#myTable2').DataTable({
                responsive: true,
                autoWidth: true,
                language: {
                    url: '../assets/json/id.json'
                },
                scrollY: '310px',
                scrollCollapse: true,
                paging: true,
                columnDefs: [{
                    targets: 0,
                    orderable: false
                }]
            });

            renderSelectedBarang('input');
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var open = document.getElementById('personaliaBtn');
            var box = document.getElementById('popupBoxPersonalia');
            var background = document.getElementById('popupBG');
            if (!open || !box || !background) {
                return;
            }

            open.addEventListener('click', function() {
                box.classList.add('aktifPopup');
                background.classList.add('aktifPopup');
                box.classList.add('scale-in-center');
                box.classList.remove('scale-out-center');
                background.classList.add('fade-in');
                background.classList.remove('fade-out');
            });

            background.addEventListener('click', function() {
                setTimeout(function() {
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

    <script>
        const SELECTOR_SIDEBAR_WRAPPER = '.sidebar-wrapper';
        const Default = {
            scrollbarTheme: 'os-theme-light',
            scrollbarAutoHide: 'leave',
            scrollbarClickScroll: true
        };
        document.addEventListener('DOMContentLoaded', function() {
            var sidebarWrapper = document.querySelector(SELECTOR_SIDEBAR_WRAPPER);
            if (sidebarWrapper && typeof OverlayScrollbarsGlobal !== 'undefined' && typeof OverlayScrollbarsGlobal.OverlayScrollbars !== 'undefined') {
                OverlayScrollbarsGlobal.OverlayScrollbars(sidebarWrapper, {
                    scrollbars: {
                        theme: Default.scrollbarTheme,
                        autoHide: Default.scrollbarAutoHide,
                        clickScroll: Default.scrollbarClickScroll
                    }
                });
            }
        });
    </script>

    <script>
        function updateDate() {
            var now = new Date();
            var options = {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            };
            var formattedDate = now.toLocaleDateString('id-ID', options);
            document.getElementById('date').textContent = formattedDate;
        }
        setInterval(updateDate, 1000);
        updateDate();

        function updateClock() {
            var now = new Date();
            var jam = String(now.getHours()).padStart(2, '0');
            var menit = String(now.getMinutes()).padStart(2, '0');
            var detik = String(now.getSeconds()).padStart(2, '0');
            document.getElementById('clock').textContent = jam + ':' + menit + ':' + detik;
        }
        setInterval(updateClock, 1000);
        updateClock();
    </script>
</body>

</html>