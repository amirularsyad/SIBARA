<?php //File sedang proses support 5.6 PHP
session_start();

// Jika belum login, arahkan ke halaman login
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../login_registrasi.php");
    exit();
}

// Jika login tapi bukan Admin, arahkan ke halaman approval
if (!isset($_SESSION['hak_akses']) || ($_SESSION['hak_akses'] !== 'Admin' && $_SESSION['hak_akses'] !== 'Super Admin')) {
    header("Location: personal/approval.php");
    exit();
}

//setup akses
include '../koneksi.php';
// ======================
// SUPPORT MULTI PT USER
// ======================
$pt_raw = isset($_SESSION['pt']) ? $_SESSION['pt'] : '';
$pt_list = array();

if (is_array($pt_raw)) {
    foreach ($pt_raw as $p) {
        $p = trim($p);
        if ($p !== '') $pt_list[] = $p;
    }
} else {
    $p = trim($pt_raw);
    if ($p !== '') $pt_list[] = $p;
}

$pt_default = (count($pt_list) > 0) ? $pt_list[0] : '';
$is_multi_pt = (count($pt_list) > 1);
$is_user_ho  = in_array('PT.MSAL (HO)', $pt_list, true);

// Mapping PT -> id_pt (tb_assets)
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

// Reverse map id_pt -> PT
$pt_map_rev = array();
foreach ($pt_map as $k => $v) {
    $pt_map_rev[(string)$v] = $k;
}
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

include '../koneksi.php';
$jumlah_approval_notif = require '../approval_notification_badge.php';
?>

<?php 
$ptSekarang = $_SESSION['pt'];
if (is_array($ptSekarang)) {
    $ptSekarang = reset($ptSekarang);
}
$ptSekarang = trim($ptSekarang);
?>
<?php

if (isset($_GET['pt']) && $_GET['pt'] !== '' && $_GET['pt'] !== 'all') {
    $pt_session_query = trim($_GET['pt']);
} else {

    $pt_session_query = $pt_default; 
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>BA Kerusakan</title>

    <!-- Bootstrap 5 -->
    <link
        rel="stylesheet"
        href="../assets/bootstrap-5.3.6-dist/css/bootstrap.min.css" />

    <!-- Bootstrap Icons -->
    <link
        rel="stylesheet"
        href="../assets/icons/icons-main/font/bootstrap-icons.min.css" />

    <!-- AdminLTE -->
    <link
        rel="stylesheet"
        href="../assets/adminlte/css/adminlte.css" />

    <!-- OverlayScrollbars -->
    <link
        rel="stylesheet"
        href="../assets/css/overlayscrollbars.min.css" />

    <!-- Favicon -->
    <link
        rel="icon" type="image/png"
        href="../assets/img/logo.png" />

    <link
        rel="icon" type="image/png"
        href="../assets/css/datatables.min.css" />

    <link
        rel="stylesheet"
        href="../assets/css/datatables.min.css" />

    <style>
        /* Main Styles */

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

        /* .personalia-menu{
        background:linear-gradient(135deg,#515bd4,#dd2a7b,#F58529);
        transition: all .3s ease;
        } */

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

        .custom-form-penambahan{
            padding: 12px 12px 0 12px ;
        }

        /* style table */

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
            font-size: .7rem;
        }

        th,
        td {
            text-align: center !important;
        }

        #thead th:nth-child(1),
        #tbody td:nth-child(1) {
            width: 4%;
            text-align: center;
        }

        /* No */
        #thead th:nth-child(2),
        #tbody td:nth-child(2) {
            width: 6%;
        }

        /* Tanggal */
        #thead th:nth-child(3),
        #tbody td:nth-child(3) {
            width: 6%;
        }

        /* Tanggal */
        #thead th:nth-child(4),
        #tbody td:nth-child(4) {
            width: 10%;
        }

        /* Jenis Perangkat */
        #thead th:nth-child(5),
        #tbody td:nth-child(5) {
            width: 220px;
        }

        /* Merek */
        #thead th:nth-child(6),
        #tbody td:nth-child(6) {
            width: 220px;
        }

        /* User */
        #thead th:nth-child(7),
        #tbody td:nth-child(7) {
            width: 200px;
        }

        /* Lokasi */
        #thead th:nth-child(8),
        #tbody td:nth-child(8) {
            width: 350px;
        }

        /* Jenis Kerusakan */
        /*th:nth-child(9), td:nth-child(9) { width: 50px; }   Status Approval 1 */
        /*th:nth-child(10), td:nth-child(10) { width: 50px; }   Status Approval 2 */
        #thead th:nth-child(11),
        #tbody td:nth-child(11) {
            width: 50px;
            height: 100% !important;
            text-align: center;
        }

        /* Actions */

        #myTable2 td {
            cursor: pointer;
        }

        .popupInput,
        .popupEdit {

            width: 100%;
            padding: 25px 30px;
            border-radius: 10px;
        }

        #popupBoxInput,
        #popupBoxEdit,
        #popupBoxDetail {
            max-height: 80vh;
            overflow-y: scroll;
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

        .popup-box {
            display: none;
        }

        .popup-bg {
            display: none;
        }

        .aktifPopup {
            display: flex;
        }

        #popupDetailTable th,
        #popupDetailTable td {
            white-space: nowrap;
            /* supaya tidak wrap */
            width: max-content !important;
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

        @media (max-width: 1670px) {
            .btn {
                margin-bottom: 5px;
            }
        }

        .bi-list,
        .bi-arrows-fullscreen,
        .bi-fullscreen-exit {
            color: #fff !important;
        }

        .custom-footer {
            background-color: white;
        }
    </style>

    <style>
        /* Placeholder Skeleton */
        .skeleton {
            height: 16px;
            width: 100%;
            background: linear-gradient(90deg,
                    #e0e0e0 25%,
                    #f5f5f5 37%,
                    #e0e0e0 63%);
            background-size: 400% 100%;
            animation: skeleton-loading 1.4s ease infinite;
            border-radius: 4px;
        }

        .skeleton-header {
            height: 20px;
        }

        @keyframes skeleton-loading {
            0% {
                background-position: 100% 0;
            }

            100% {
                background-position: -100% 0;
            }
        }
    </style>

    <style>
        /* Responsive */
        @media (max-width: 1440px) {

            /* Formulir BA 
        ========================================================
        */
            .custom-input-tanggal {
                width: max-content;
            }

            

            .custom-font-form {
                font-size: 12px;
            }

            .custom-form-sn .input-group .input-group-text {
                padding-right: 60px !important;
            }

            .custom-form-merk .input-group .input-group-text {
                padding-right: 42px !important;
            }

            .custom-form-pengguna .input-group .input-group-text {
                padding-right: 22px !important;
            }

            .custom-form-lantai,
            .custom-form-lokasi {
                width: max-content;
            }

            .custom-row-rm-kat {
                margin-top: 0 !important;
            }

            .custom-form-jk,
            .custom-form-pk,
            .custom-form-rm {
                width: 100%;
                padding-right: 0 !important;
                margin-top: 0 !important;
                margin-bottom: 16px;
            }

            .custom-form-jk .input-group .input-group-text {
                padding-right: 37px !important;
            }

            .custom-form-rm .input-group .input-group-text {
                padding-right: 27px !important;
            }


            /* .custom-form-nomor{

        } */

            /* End:Formulir BA 
        ========================================================
        */
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

            /* .dt-orderable-none{
            min-width: 100px;
        } */
            /* Form input */
            .custom-input-form {
                width: 100%;
            }

            /* .custom-row-search-db{
            width: 160px;
        }
        .custom-row-search-db .col-4{
            width: 100%;
        } */
            .custom-input-tanggal {
                width: 235px !important;
            }

            /* .custom-input-tanggal .input-group{
            width: 100% !important;
        } */
            .custom-form-sn .input-group-text {
                padding-right: 55px !important;
            }

            .custom-form-merk .input-group-text {
                padding-right: 35px !important;
            }

            .custom-form-lokasi,
            .custom-form-lantai {
                width: 50%;
            }

            .custom-form-jk,
            .custom-form-pk {
                width: 100%;
            }

            .custom-form-pk {
                margin-top: 1rem !important;
            }

            .custom-form-jk {
                margin-top: 1rem !important;
                padding-right: 0 !important;
            }

            /* .custom-form-jp{
            width: 100%;
        } */
            .custom-form-jk .input-group-text {
                padding-right: 40px !important;
            }

            .custom-form-rm .input-group-text {
                padding-right: 28px !important;
            }

            /* Form input gambar*/
            .custom-input-gambar-section {
                width: 100%;
            }

            .custom-input-gambar {
                width: 100%;
                height: 35vh !important;
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

            /* #tombolInputPopup, #tombolHistorikal{
            padding: 12px 24px;
        } */
            #tombolInputPopup i,
            #tombolHistorikal i {
                font-size: 25px !important;
            }

            .custom-footer p {
                font-size: 10px;
            }

            /* Formulir BA 
        ========================================================
        */
            .custom-row-tgl-no {
                width: 100%;
                display: flex;
                flex-direction: column;
            }

            .custom-input-tanggal,
            .custom-form-nomor {
                margin-bottom: 8px;
            }
            

            .custom-input-tanggal,
            .custom-form-nomor,
            .custom-form-pt-top,
            .custom-input-tanggal .input-group,
            .custom-form-nomor .input-group,
            .custom-form-pt-top .input-group,
            .custom-form-penambahan .form-check {
                width: 100% !important;
            }

            .custom-row-search-db .custom-row-search-db-child {
                flex-direction: row !important;
                width: 100%;
                justify-content: space-between;
                padding-right: 0;
            }

            /* .custom-btn-data-barang{
            align-self: flex-end;
        } */

            .custom-font-form {
                font-size: 12px;
            }

            .custom-form-sn,
            .custom-form-merk,
            .custom-form-pengguna,
            .custom-form-jp,
            .custom-form-nopo,
            .custom-form-tp {
                margin-top: 16px;
                width: 100%;
            }

            .custom-form-sn .input-group,
            .custom-form-merk .input-group,
            .custom-form-pengguna .input-group,
            .custom-form-jp .input-group,
            .custom-form-nopo .input-group,
            .custom-form-tp .input-group {
                display: flex !important;
                flex-direction: column !important;
                align-items: flex-start;
            }

            .custom-form-sn .input-group .input-group-text,
            .custom-form-merk .input-group .input-group-text,
            .custom-form-pengguna .input-group .input-group-text,
            .custom-form-jp .input-group .input-group-text,
            .custom-form-nopo .input-group .input-group-text,
            .custom-form-tp .input-group .input-group-text {
                padding-right: 0px !important;
                width: 100%;
                border-radius: 5px 5px 0 0 !important;
                border-bottom: none;
                margin: 0 !important;
            }

            .custom-form-sn .input-group .form-control,
            .custom-form-merk .input-group .form-control,
            .custom-form-pengguna .input-group .form-control,
            .custom-form-jp .input-group .form-control,
            .custom-form-nopo .input-group .form-control,
            .custom-form-tp .input-group .form-control {
                width: 100%;
                border-radius: 0 0 0 0;
                margin: 0 !important;
                border-radius: 0 0 5px 5px !important;
            }

            .custom-row-lokasi-lantai {
                padding-right: 0;
            }

            .custom-form-lantai,
            .custom-form-lokasi {
                width: 100%;
                margin-top: 16px;
            }

            .custom-form-lokasi .input-group,
            .custom-form-lantai .input-group,
            .custom-form-pengguna2 .input-group,
            .custom-form-atasan .input-group {
                
                flex-direction: column !important;
                align-items: flex-start;
            }

            .custom-form-lokasi .input-group .input-group-text,
            .custom-form-lantai .input-group .input-group-text,
            .custom-form-pengguna2 .input-group .input-group-text,
            .custom-form-atasan .input-group .input-group-text {
                padding-right: 0px !important;
                width: 100%;
                border-radius: 5px 5px 0 0 !important;
                border-bottom: none;
                margin: 0 !important;
            }

            .custom-form-lokasi .input-group .form-select,
            .custom-form-lantai .input-group .form-select,
            .custom-form-pengguna2 .input-group .form-select,
            .custom-form-atasan .input-group .form-select {
                width: 100%;
                border-radius: 0 0 0 0;
                margin: 0 !important;
                border-radius: 0 0 5px 5px !important;
            }

            .custom-row-pengguna-atasan {
                margin-top: 0 !important;
            }

            .custom-form-pengguna2,
            .custom-form-atasan {
                margin-top: 16px;
                width: 100%;
            }

            .custom-form-atasan .input-group {
                padding-right: 12px;
            }

            .text-data-pengguna {
                margin-bottom: 0;
            }

            .footer-form {
                flex-direction: column;
            }

            .text-formulir {
                font-size: 16px;
            }

            .custom-form-submit {
                width: 100% !important;
            }

            .custom-row-rm-kat {
                margin-top: 0 !important;
            }

            .custom-form-jk,
            .custom-form-pk,
            .custom-form-rm,
            .custom-form-ae {
                width: 100%;
                padding-right: 0 !important;
                margin-top: 0 !important;
                margin-bottom: 16px;
            }

            .custom-form-jk .input-group .input-group-text,
            .custom-form-pk .input-group .input-group-text,
            .custom-form-rm .input-group .input-group-text,
            .custom-form-ae .input-group .input-group-text {
                padding-right: 0px !important;
                padding-left: 0px !important;
                width: 75px !important;
                white-space: normal !important;
            }

            .custom-form-kk {
                width: 100%;
                padding-right: 0;
            }

            /* .custom-form-nomor{

        } */

            /* End:Formulir BA 
        ========================================================
        */
            /* Detail Popup
        */
            /* #popupDetailBody{
            
        } */

            .custom-detail-container {
                flex-direction: column;
                width: 100%;
            }

            .custom-detail-approval {
                width: 100%;
                overflow-x: auto;
            }

            .custom-detail-table {
                overflow-y: auto !important;
                width: 100% !important;
            }

            .custom-detail-table-child {
                width: 100% !important;
                overflow-y: auto;
            }

            .custom-detail-gambar {
                width: 100% !important;
            }

            .custom-detail-histori {
                width: 100% !important;
            }

            .custom-popup-box-delete {
                width: 100vw !important;
            }
        }
    </style>

    <style>
        /* scroll styling */
        .scroll-container {
            height: 100vh;
            /* tinggi penuh layar */
            overflow-y: scroll;
            /* scroll tetap aktif */
            -ms-overflow-style: none;
            /* IE dan Edge */
            scrollbar-width: none;
            /* Firefox */
        }

        .scroll-container::-webkit-scrollbar {
            display: none;
            /* Chrome, Safari, Opera */
        }
    </style>

    <style>
        /*animista.net*/
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

    <script src="../assets/js/html5-qrcode.min.js"></script>


    <style>
        .scan-container {
            width: 100%;
            max-width: 500px;
            padding: 20px;
            box-sizing: border-box;
        }

        #reader {
            width: 100%;
            max-width: 300px;
            margin: 0 auto;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        #reader video {
            width: 100%;
            height: auto;
            object-fit: cover;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 10% auto;
            padding: 20px;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
            position: relative;
        }
    </style>

</head>

<body class="layout-fixed sidebar-expand-lg bg-body-tertiary overflow-x-hidden">

    <script src="../assets/js/jquery-3.7.1.min.js"></script>
    <script src="../assets/js/datatables.min.js"></script>
    <script src="../assets/js/html5-qrcode.min.js"></script>


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
                                <i class="bi bi-box-arrow-right fw-bolder"></i>
                                <p class="m-0">Logout</p>
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
                        class="brand-image opacity-75 shadow" />
                    <span class="brand-text fw-bold">SIBARA</span>
                </a>
            </div>
            <div class="sidebar-wrapper">
                <nav class="mt-2">

                    <ul
                        class="nav sidebar-menu flex-column"
                        data-lte-toggle="treeview"
                        role="menu"
                        data-accordion="false">
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

                        <?php if ($ptSekarang == "PT.MSAL (HO)"){ ?>
                        <!-- List BA Pengembalian -->
                        <!-- <li class="nav-item">
                            <a href="../ba_pengembalian/ba_pengembalian.php" class="nav-link">
                                <i class="nav-icon bi bi-newspaper"></i>
                                <p>
                                    BA Pengembalian
                                </p>
                            </a>
                        </li> -->
                        <?php } ?>
                        
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


                        <li class="nav-header">
                            USER
                        </li>

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

        //Tabel
        // Ambil nilai filter dari parameter GET
        $filter_pt = isset($_GET['pt']) ? $_GET['pt'] : '';
        $filter_tahun = isset($_GET['tahun']) ? $_GET['tahun'] : '';
        $filter_bulan = isset($_GET['bulan']) ? $_GET['bulan'] : '';

        // Siapkan bagian WHERE sesuai filter yang diisi
        $where_clauses = array();
        $params = array();
        $types = '';

        // PT default dari session (kalau multi PT, pakai PT pertama)
        $pt_filter = $pt_default;

        // ======================
        // FILTER PT (SUPPORT MULTI PT)
        // ======================
        if (!empty($filter_pt) && $filter_pt !== 'all') {

            // pilih 1 PT spesifik
            $where_clauses[] = "bak.pt = ?";
            $params[] = $filter_pt;
            $types .= 's';

        } elseif ($filter_pt === 'all') {

            // Admin HO => all beneran (tanpa filter PT)
            if (!($_SESSION['hak_akses'] === 'Admin' && $is_user_ho)) {

                // selain Admin HO: all = semua PT milik user
                if (count($pt_list) > 0) {
                    $placeholders = implode(',', array_fill(0, count($pt_list), '?'));
                    $where_clauses[] = "bak.pt IN ($placeholders)";
                    foreach ($pt_list as $p) {
                        $params[] = $p;
                        $types .= 's';
                    }
                } else {
                    $where_clauses[] = "1=0";
                }
            }

        } else {

            // default (kalau user punya banyak PT => tampil semua PT milik user)
            if (count($pt_list) > 1) {
                $placeholders = implode(',', array_fill(0, count($pt_list), '?'));
                $where_clauses[] = "bak.pt IN ($placeholders)";
                foreach ($pt_list as $p) {
                    $params[] = $p;
                    $types .= 's';
                }
            } else {
                $where_clauses[] = "bak.pt = ?";
                $params[] = $pt_filter;
                $types .= 's';
            }
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

        // Hanya tampilkan data yang belum dihapus
        $where_clauses[] = "bak.dihapus = 0";

        // Gabungkan WHERE jika ada filter
        $where_sql = '';
        if (!empty($where_clauses)) {
            $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
        }

        // Query utama
        $query =    "SELECT bak.*, cb.nama AS kategori_nama
                FROM berita_acara_kerusakan bak
                LEFT JOIN categories_broken cb 
                ON bak.kategori_kerusakan_id = cb.id
                $where_sql
                ORDER BY bak.tanggal DESC, bak.nomor_ba DESC";

        // Eksekusi query
        $stmt = $koneksi->prepare($query);
        if (!empty($params)) {
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
        ?>



        <?php
        //nomor form input

        $tanggal_hari_ini = date('Y-m-d');
        $bulan_ini = date('m');
        $tahun_ini = date('Y');

        // PT default form input
        $pt_nomor_ba = $pt_default;

        // Ambil nomor_ba tertinggi di bulan, tahun, dan PT yang sama
        if (!empty($pt_nomor_ba)) {
            $stmt2 = $koneksi->prepare("
                SELECT nomor_ba 
                FROM berita_acara_kerusakan 
                WHERE MONTH(tanggal) = ? 
                AND YEAR(tanggal) = ? 
                AND pt = ?
                AND dihapus = 0
                ORDER BY CAST(nomor_ba AS UNSIGNED) DESC 
                LIMIT 1
            ");
            $stmt2->bind_param("sss", $bulan_ini, $tahun_ini, $pt_nomor_ba);
            $stmt2->execute();
            $result2 = $stmt2->get_result();
            $row2 = $result2->fetch_assoc();

            if ($row2 && is_numeric($row2['nomor_ba'])) {
                $last_nomor = (int)$row2['nomor_ba'];
                $nomor_ba_baru = str_pad($last_nomor + 1, 3, '0', STR_PAD_LEFT);
            } else {
                $nomor_ba_baru = '001';
            }
        } else {
            // fallback kalau PT kosong
            $nomor_ba_baru = '001';
        }
        ?>

        <!--Koneksi Atasan Karyawan-->
        <?php
        // Ambil semua data atasan HO (Dept. Head / AVP)
        $query_atasan = $koneksi->query("
            SELECT nama, posisi, departemen, jabatan
            FROM data_karyawan
            WHERE dihapus = 0
            AND jabatan IN ('Dept. Head', 'AVP')
            ORDER BY nama ASC
        ");
        $data_atasan = array();
        while ($row2 = $query_atasan->fetch_assoc()) {
            $data_atasan[] = $row2;
        }
        ?>
        <!--Koneksi Nama Karyawan-->
        <?php
        // Ambil semua data user, nanti difilter via JavaScript
        $query_karyawan = $koneksi->query("SELECT nama, posisi, departemen, lantai, jabatan FROM data_karyawan WHERE dihapus = 0 ORDER BY nama ASC");
        $data_karyawan = [];
        while ($row2 = $query_karyawan->fetch_assoc()) {
            $data_karyawan[] = $row2;
        }
        ?>
        
        <?php
        // off
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
        WHERE assets.location_id = '8'
        ORDER BY categories.name ASC
        ";

        $result_assets = $koneksi->query($query_assets);
        ?>

        <?php

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
            tb_qty_assets.id_qty AS qty_id,
            tb_qty_assets.category
        FROM tb_assets
        LEFT JOIN tb_qty_assets ON tb_assets.qty_id = tb_qty_assets.id_qty
        WHERE tb_assets.id_pt IN ($id_pt_sql)
        ORDER BY tb_qty_assets.category ASC
        ";

        $result_assets2 = $koneksi2->query($query_assets2);
        ?>

        <main id="custom-main" class="custom-main app-main"><!-- Main Content -->

            <!--Status Sukses Gagal Pop Up-->
            <?php if (isset($_SESSION['message'])): ?>
                <?php if (isset($_GET['status']) && $_GET['status'] == 'sukses'): ?>
                    <div class="w-100 d-flex justify-content-center position-absolute" style="height: max-content;">
                        <div class="d-flex p-0 alert alert-success border-0 text-center fw-bold mb-0 position-absolute fade-in infoin-approval" style="z-index:8;transition: opacity 0.5s ease;right:20px;width:max-content;height:max-content;">
                            <div class="d-flex justify-content-center align-items-center bg-success pe-2 ps-2 rounded-start text-white fw-bolder">
                                <i class="bi bi-check-lg"></i>
                            </div>
                            <p class="p-2 m-0" style="font-weight: 500;"><?= htmlspecialchars($_SESSION['message']);
                                                                            unset($_SESSION['message']); ?></p>
                        </div>
                    </div>
                <?php elseif (isset($_GET['status']) && $_GET['status'] == 'gagal'): ?>
                    <div class="w-100 d-flex justify-content-center position-absolute" style="height: max-content;">
                        <div class="d-flex p-0 alert alert-danger border-0 text-center fw-bold mb-0 position-absolute fade-in infoin-approval" style="z-index:8;transition: opacity 0.5s ease;right:20px;width:max-content;height:max-content;">
                            <div class="d-flex justify-content-center align-items-center bg-danger pe-2 ps-2 rounded-start text-white fw-bolder">
                                <i class="bi bi-x-lg"></i>
                            </div>
                            <p class="p-2 m-0" style="font-weight: 500;"><?= htmlspecialchars($_SESSION['message']);
                                                                            unset($_SESSION['message']); ?></p>
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


            <section id="table-wrapper" class="table-wrapper bg-white position-relative overflow-visible d-flex flex-column">
                <h2>Daftar Berita Acara Kerusakan Aset</h2>

                <form method="GET" class="mb-3 d-flex flex-wrap gap-3">
                    <?php
                    $pt_session = $_SESSION['pt'];

                    if (is_array($pt_session)) {
                        $pt_session = reset($pt_session);
                    }
                    $pt_session = trim($pt_session);
                    ?>

                    <select name="pt" class="form-select" onchange="this.form.submit()" style="width: 200px;">
                        <?php
                        // Admin + punya HO => boleh lihat semua PT (all beneran)
                        if ($_SESSION['hak_akses'] === 'Admin' && $is_user_ho) {
                            echo '<option value="all" '.($filter_pt==='all'?'selected':'').'>Semua PT</option>';
                        } else {
                            // Non-HO tapi multi PT => "all" = semua PT milik user
                            if (count($pt_list) > 1) {
                                echo '<option value="all" '.($filter_pt==='all'?'selected':'').'>Semua PT</option>';
                            }
                        }

                        // Daftar PT user
                        foreach ($pt_list as $ptx) {
                            $sel = ($filter_pt === $ptx) ? 'selected' : '';
                            echo '<option value="'.htmlspecialchars($ptx).'" '.$sel.'>'.htmlspecialchars($ptx).'</option>';
                        }

                        // fallback kalau pt_list kosong
                        if (count($pt_list) === 0) {
                            echo '<option value="-">-</option>';
                        }
                        ?>
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
                                <th>
                                    <div class="skeleton skeleton-header"></div>
                                </th>
                                <th>
                                    <div class="skeleton skeleton-header d-none"></div>
                                </th>
                                <th>
                                    <div class="skeleton skeleton-header d-none"></div>
                                </th>
                                <th>
                                    <div class="skeleton skeleton-header d-none"></div>
                                </th>
                                <th>
                                    <div class="skeleton skeleton-header"></div>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php for ($i = 0; $i < 8; $i++) { ?>
                                <tr>
                                    <td style="border: #e0e0e0 1px solid;">
                                        <div class="skeleton"></div>
                                    </td>
                                    <td style="border: #e0e0e0 1px solid;">
                                        <div class="skeleton"></div>
                                    </td>
                                    <td style="border: #e0e0e0 1px solid;">
                                        <div class="skeleton"></div>
                                    </td>
                                    <td style="border: #e0e0e0 1px solid;">
                                        <div class="skeleton"></div>
                                    </td>
                                    <td style="border: #e0e0e0 1px solid;">
                                        <div class="skeleton"></div>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>

                <div id="tabelUtama" style="display: none;">
                    <table id="myTable" class="table table-bordered table-striped text-center">
                        <!-- <a href="form_input_ba_kerusakan.php" class="btn btn-success position-absolute" style="width:max-content;height:max-content;top:127px;left:220px;z-index:1;"><i class="bi bi-plus-lg"></i></a> -->
                        <div class="custom-btn-input-history position-absolute d-flex gap-2" style="top:127px;left:220px;z-index:1;width:max-content;height:max-content;">
                            <a href="#" id="tombolInputPopup" class="<?php if ($_SESSION['hak_akses'] === 'Super Admin'): ?>d-none<?php endif; ?>
                            btn btn-success"><i class="bi bi-plus-lg"></i></a>
                            <a href="../master/histori_edit.php" id="tombolHistorikal" class="btn btn-warning"><i class="bi bi-clock-history"></i></a>
                        </div>
                        <!-- <a href="#" id="tombolInputPopup" class="
                        <?php if ($_SESSION['hak_akses'] === 'Super Admin'): ?>
                        d-none
                        <?php endif; ?>
                        btn btn-success position-absolute" style="width:max-content;height:max-content;top:127px;left:220px;z-index:1;"><i class="bi bi-plus-lg"></i></a> -->
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
                                <th>Kategori</th>
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
                                    <td><?= htmlspecialchars($row['pt']) ?>
                                        <?php if ($row['pt'] === 'PT.MSAL (HO)') { ?>
                                            <?= htmlspecialchars($row['lokasi']) ?>
                                        <?php } ?>
                                    </td>
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
                                    <td>
                                        <?= htmlspecialchars($row['kategori_nama']) ?>
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

                                        <a class="custom-btn-action btn btn-secondary btn-sm btn-detail-ba" href="#" data-id="<?= $row['id'] ?>">
                                            <i class="bi bi-eye-fill"></i>
                                        </a>

                                        <a class='custom-btn-action btn btn-primary btn-sm' href='surat_output.php?id=<?= $row['id'] ?>' target='_blank'><i class="bi bi-file-earmark-text-fill"></i></a>
                                        <?php
                                        
                                        if ($_SESSION['nama'] === $row['nama_pembuat']):
                                        ?>
                                            <?php if($row['pending_hapus'] != 1){ ?>
                                            <a class='custom-btn-action btn btn-warning btn-sm tombolPopupEdit' href='#' data-id="<?= $row['id'] ?>">
                                                <i class="bi bi-feather"></i>
                                            </a>
                                            <?php
                                            
                                            $approvalPending = false;

                                            $approvalFields = array(
                                                isset($row['approval_1']) ? $row['approval_1'] : 0,
                                                isset($row['approval_2']) ? $row['approval_2'] : 0,
                                                isset($row['approval_3']) ? $row['approval_3'] : 0,
                                                isset($row['approval_4']) ? $row['approval_4'] : 0,
                                                isset($row['approval_5']) ? $row['approval_5'] : 0
                                            );


                                            foreach ($approvalFields as $approval) {
                                                if ((int)$approval === 1) {
                                                    $approvalPending = true;
                                                    break;
                                                }
                                            }
                                            ?>
                                            <a class='custom-btn-action btn btn-danger btn-sm tombolPopupDelete' href='#' data-id="<?= $row['id'] ?> " data-pending="<?= $approvalPending ? 'true' : 'false' ?>"><i class="bi bi-trash-fill"></i></a>
                                        <?php }
                                        endif; ?>
                                        <?php if($row['pending_hapus'] === 1 && $_SESSION['nama'] === $row['nama_pembuat']){ ?>
                                            <br>
                                            <p class="custom-font-form m-0 mb-1 text-warning"><i class="bi bi-exclamation-triangle"></i> Surat sedang pending delete.</p>
                                        <?php } ?>
                                    </td>
                                </tr>
                            <?php $no++;
                            endwhile; ?>
                        </tbody>
                    </table>
                </div>

                <div id="popupBoxDelete" class="custom-popup-box-delete popup-box position-absolute bg-white rounded-1 flex-column justify-content-start align-items-center p-2"
                    style="top:30vh; height: max-content;align-self: center;z-index: 9;width: 500px;">

                    <div class="w-100 d-flex justify-content-between mb-2" style="height: max-content;">
                        <h4 class="m-0 p-0"></h4>
                    </div>

                    <!-- FORM (POST) -->
                    <form id="formDelete" method="POST" action="delete.php" class="d-flex flex-column align-items-center w-100">

                        <input type="hidden" name="id" id="deleteId">
                        <input type="hidden" name="pending" id="deletePending">

                        <p>Apakah anda yakin ingin menghapus data ini?</p>

                        <!-- ALASAN HAPUS (DEFAULT: HIDDEN) -->
                        <div id="alasanWrapper" class="w-100 d-none">
                            <div class="input-group">
                                <span class="input-group-text">Alasan Hapus</span>
                                <textarea
                                    name="alasan_hapus"
                                    id="alasanHapus"
                                    class="form-control"></textarea>
                            </div>
                        </div>

                        <!-- TOMBOL ASLI (TIDAK DIUBAH) -->
                        <div class="w-50 d-flex justify-content-around mt-2">
                            <button id="tombolAccDelete" type="submit" class="btn btn-danger">Hapus</button>
                            <a id="tombolClosePopupDelete" class="custom-btn-action btn btn-secondary" href="#">Batal</a>
                        </div>

                    </form>
                </div>


                <div id="popupBoxInput" class="popup-box position-absolute bg-white top-0 rounded-1 flex-column justify-content-start align-items-center p-2" style="height: max-content;align-self: center;z-index: 9;width: 95%;">

                    <div class="w-100 d-flex justify-content-between mb-2" style="height: max-content;">
                        <h4 class="m-0 p-0">Input Berita Acara</h4>
                        <a id="tombolClosePopup" class='custom-btn-action btn btn-danger btn-sm' href='#'><i class="bi bi-x-lg"></i></a>
                    </div>

                    <form class="popupInput d-flex flex-column" method="post" action="proses_simpan.php" enctype="multipart/form-data">
                        <div class="form-section">
                            <div class="row position-relative">

                                <div class="custom-input-form col-8">
                                    <h3 data-ba-kerusakan-text>Data Berita Acara Kerusakan</h3>
                                    <div class="custom-row-tgl-no row">
                                        <div class="custom-input-tanggal col-3">
                                            <div class="input-group" style="width:100%;">
                                                <span class="input-group-text custom-font-form">Tanggal</span>
                                                <input class="form-control custom-font-form" type="date" name="tanggal" id="tanggal" max="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d') ?>" required>
                                            </div>
                                        </div>
                                        <div class="custom-form-nomor" style="width:180px;">
                                            <div class="input-group" style="width:100%;">
                                                <span class="input-group-text custom-font-form">Nomor BA</span>
                                                <input type="text" class="form-control custom-font-form" maxlength="3" name="nomor_ba" id="nomor_ba" value="<?= $nomor_ba_baru ?>" readonly>
                                            </div>
                                        </div>
                                        <div class="custom-form-pt-top col-4">
                                            <div class="input-group" style="width:220px;">
                                                <span class="input-group-text custom-font-form">PT</span>
                                                <select name="pt" id="perusahaan" class="form-select custom-font-form" required>
                                                    <option value="">-- Pilih PT --</option>
                                                    <?php foreach ($pt_list as $ptx): ?>
                                                        <option value="<?= htmlspecialchars($ptx) ?>" <?= ($ptx === $pt_default ? 'selected' : '') ?>>
                                                            <?= htmlspecialchars($ptx) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="custom-form-penambahan">
                                            <div class="form-check" style="width: 100%;">
                                                <input class="form-check-input" type="checkbox" value="" id="checkPenambahanInput">
                                                <label class="form-check-label custom-font-form" for="checkPenambahanInput">
                                                    BA Penambahan
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    

                                    <div class="row mt-1 border border-1 p-1 rounded-2 me-1">
                                        <div class="custom-row-search-db row pt-1 pb-2">
                                            <div class="col-4 d-flex flex-column custom-row-search-db-child">
                                                <h5>Data barang</h5>

                                                <div class="custom-btn-data-barang d-flex">
                                                    <div class="tombolDataBarangPopup btn btn-primary rounded-end-0 btn-lg" data-target="input"><i class="bi bi-search"></i></div>
                                                    <!-- <div class="btn btn-primary rounded-start-0" id="openScanModal"><i class="bi bi-qr-code-scan"></i></div> -->
                                                    <button type="button" id="openScanModal" class="btn btn-primary rounded-start-0 btn-lg">
                                                        <i class="bi bi-qr-code-scan"></i>
                                                    </button>
                                                </div>

                                            </div>

                                        </div>

                                        <div class="row pe-0 w-100">

                                            <div class="custom-form-sn col-6">
                                                <div class="input-group">
                                                    <span class="input-group-text custom-font-form" style="padding-right:63px;">SN</span>
                                                    <input id="serial_number_input" class="form-control custom-font-form" type="text" name="sn" value="" readonly>
                                                </div>
                                            </div>
                                            <div class="custom-form-nopo col-6">
                                                <div class="input-group">
                                                    <span class="input-group-text custom-font-form" style="padding-right:52px;">Nomor PO</span>
                                                    <input id="nomor_po_input" class="form-control custom-font-form" type="text" name="nomor_po" value="" readonly>
                                                </div>
                                            </div>

                                            <div class="custom-form-merk col-6 mt-3">
                                                <div class="input-group">
                                                    <span class="input-group-text custom-font-form" style="padding-right:37px;">Merek</span>
                                                    <input id="merek_input" class="form-control custom-font-form" type="text" name="merek" value="" readonly>
                                                </div>
                                            </div>
                                            <div class="custom-form-jp col-6 mt-3">
                                                <div class="input-group">
                                                    <span class="input-group-text custom-font-form" style="padding-right:18px;">Jenis Perangkat</span>
                                                    <input id="jenis_perangkat_input" class="form-control custom-font-form" type="text" name="jenis_perangkat" value="" readonly>
                                                </div>
                                            </div>
                                            
                                            <div class="custom-form-pengguna col-6 mt-3">
                                                <div class="input-group ">
                                                    <span class="input-group-text custom-font-form">Pengguna</span>
                                                    <input id="pengguna_input" type="text" class="form-control custom-font-form" name="user" value="" readonly>
                                                </div>
                                            </div>
                                            <div class="custom-form-tp col-6 mt-3">
                                                <div class="input-group">
                                                    <span class="input-group-text custom-font-form" style="padding-right:12px;">Tahun Perolehan</span>
                                                    <input id="tahun_perolehan_input" type="text" class="form-control custom-font-form" name="tahun_perolehan" value="" readonly>
                                                </div>
                                            </div>
                                            

                                        </div>

                                    </div>

                                    <div class="row mt-3 border border-1 p-1 rounded-2 me-1">
                                        <div class="row">
                                            <h5 class="text-data-pengguna">Data Pengguna</h5>
                                        </div>

                                        <div class="custom-row-lokasi-lantai row">

                                            <div class="custom-form-lantai col-5" id="lantai-wrapper" style="display: none;">
                                                <!-- HO: LANTAI -->
                                                <div class="input-group" id="group_lantai_ho" style="display:none;">
                                                    <span class="input-group-text custom-font-form">Lantai</span>
                                                    <select name="lokasi" id="lokasi" class="form-select custom-font-form" disabled>
                                                        <option value="">-- Pilih Lantai --</option>
                                                        <?php
                                                        $resultLantai = $koneksi->query("SELECT DISTINCT lantai FROM data_karyawan WHERE dihapus = 0 ORDER BY lantai ASC");
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

                                                <!-- NON-HO: LOKASI TEXT -->
                                                <div class="input-group" id="group_lokasi_nonho" style="display:none;">
                                                    <span class="input-group-text custom-font-form">Lokasi</span>
                                                    <input type="text" name="lokasi" id="lokasi_text" class="form-control custom-font-form" placeholder="Detail Lokasi" disabled>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="custom-row-pengguna-atasan row mt-3 pe-0" id="user-wrapper" style="display: none;">
                                            <div class="custom-form-pengguna2 col-6">
                                                <!-- HO: SELECT PENGGUNA -->
                                                <div class="input-group" id="group_peminjam_ho" style="display:none;">
                                                    <span class="input-group-text custom-font-form">Pengguna</span>
                                                    <select name="peminjam" id="user" class="form-select custom-font-form" disabled>
                                                        <option value="">-- Pilih Pengguna --</option>
                                                    </select>
                                                </div>

                                                <!-- NON-HO: TEXT PENGGUNA -->
                                                <div class="input-group" id="group_peminjam_nonho" style="display:none;">
                                                    <span class="input-group-text custom-font-form">Pengguna</span>
                                                    <input type="text" name="peminjam" id="peminjam_text" class="form-control custom-font-form" placeholder="Nama Pengguna" disabled>
                                                </div>
                                            </div>

                                            <div class="custom-form-atasan col-6 pe-0">
                                                <!-- HO: SELECT ATASAN -->
                                                <div class="input-group" id="group_atasan_ho" style="display:none;">
                                                    <span class="input-group-text custom-font-form">Atasan Pengguna</span>
                                                    <select name="atasan_peminjam" id="atasan_peminjam" class="form-select custom-font-form" disabled>
                                                        <option value="">-- Pilih Atasan Pengguna --</option>
                                                    </select>
                                                </div>

                                                <!-- NON-HO: TEXT ATASAN -->
                                                <div class="input-group" id="group_atasan_nonho" style="display:none;">
                                                    <span class="input-group-text custom-font-form">Atasan Pengguna</span>
                                                    <input type="text" name="atasan_peminjam" id="atasan_text" class="form-control custom-font-form" placeholder="Atasan Pengguna" disabled>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row mt-3 border border-1 p-1 rounded-2 me-1" style="z-index: 11;">
                                        <div class="row">
                                            <h5 data-ba-kerusakan-text>Laporan Kerusakan</h5>
                                        </div>
                                        <div class="row pe-0">
                                            <div class="col-6 custom-form-jk">
                                                <div class="input-group">
                                                    <span class="input-group-text custom-font-form" style="padding-right: 27px;" data-ba-kerusakan-text>Jenis Kerusakan</span>
                                                    <textarea name="deskripsi" class="form-control custom-font-form" style="font-size:small;" rows="3" required></textarea>
                                                </div>
                                            </div>
                                            <div class="custom-form-pk  col-6 pe-0">
                                                <div class="input-group">
                                                    <span class="input-group-text custom-font-form" data-ba-kerusakan-text>Penyebab Kerusakan</span>
                                                    <textarea name="penyebab_kerusakan" class="form-control custom-font-form" style="font-size:small;" rows="3" required></textarea>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="custom-row-rm-kat row mt-3 pe-0">
                                            <div class="custom-form-rm  col-12 mb-3 pe-0">
                                                <div class="input-group">
                                                    <span class="input-group-text custom-font-form">Rekomendasi MIS</span>
                                                    <textarea name="rekomendasi_mis" class="form-control custom-font-form" style="font-size:small;" rows="2" required></textarea>
                                                </div>
                                            </div>
                                            <?php
                                            $query = "SELECT id, nama FROM categories_broken ORDER BY id ASC";
                                            $broken = mysqli_query($koneksi, $query);
                                            ?>
                                            <div class="custom-form-kk  col-6 mb-3">
                                                <div class="input-group">
                                                    <span class="input-group-text custom-font-form" style="padding-right: 20px;">Kategori</span>
                                                    <select name="kategori_kerusakan" class="form-select kategoriKerusakan custom-font-form" required>
                                                        <option value="">-- Pilih Kategori --</option>
                                                        <?php
                                                        if ($broken && mysqli_num_rows($broken) > 0) {
                                                            while ($row = mysqli_fetch_assoc($broken)) {
                                                                echo '<option value="' . htmlspecialchars($row['id']) . '">' . htmlspecialchars($row['nama']) . '</option>';
                                                            }
                                                        }
                                                        ?>
                                                    </select>

                                                </div>
                                            </div>
                                            <div class="custom-input-dll  col-6 pe-0 dllWrapper" style="display: none;">
                                                <div class="input-group">
                                                    <span class="input-group-text">Keterangan</span>
                                                    <textarea name="keterangan_dll" class="form-control keteranganDll" style="font-size:small;" rows="1"></textarea>
                                                </div>
                                            </div>
                                        </div>

                                    </div>

                                </div>

                                <div class="custom-input-gambar-section col-4">
                                    <h3>Gambar</h3>
                                    <div class="custom-input-gambar border border-2 rounded-3 p-1" style="height:485px; overflow-y:auto;">
                                        <div class=" d-flex flex-column">
                                            <div id="gambar-container" class="d-flex flex-column gap-2"></div>
                                            <button type="button" class="btn btn-primary w-50 align-self-center mb-1" onclick="tambahGambar()">+ Tambah Gambar Kerusakan</button>
                                        </div>
                                    </div>
                                </div>

                            </div>

                        </div>
                        <div class="footer-form d-flex w-100 justify-content-between">
                            <h5 class="text-formulir m-0 mt-3" style="color: darkgray;">*Formulir ini untuk melaporkan kerusakan dan rekomendasi perbaikan aset</h5>
                            <input class="custom-form-submit w-25 align-self-end" type="submit" value="Simpan">
                        </div>

                    </form>
                    
                </div>

                <!-- Modal untuk QR Scanner -->
                <div id="scanModal" class="modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5);">
                    <div class="modal-content bg-white p-3" style="margin:10% auto; width:400px; position:relative;">
                        <span class="close-button btn btn-sm btn-danger" style="position:absolute; top:5px; right:5px;">X</span>
                        <p id="loadingText">Mengaktifkan kamera...</p>
                        <div id="reader" style="width:100%; height:300px;"></div>
                        <button id="stopScanButton" class="btn btn-warning mt-2" style="display:none;">Stop Scan</button>
                        <input type="hidden" id="asset_id">
                    </div>
                </div>

                <div id="popupBoxEdit" class="popup-box position-absolute bg-white top-0 rounded-1 flex-column justify-content-start align-items-center p-2" style="height: max-content;align-self: center;z-index: 9;width: 95%;">

                    <div class="w-100 d-flex justify-content-between mb-2" style="height: max-content;">
                        <h4 id="popupEditTitle" class="m-0 p-0">Edit Berita Acara</h4>
                        <a id="tombolClosePopupEdit" class='custom-btn-action btn btn-danger btn-sm' style="height: max-content;" href='#'>
                            <i class="bi bi-x-lg"></i>
                        </a>
                    </div>
                    <div id="popupEditBody" class="w-100"></div>
                    <!-- Form diisi JavaScript -->
                </div>

                <div id="popupBoxDetail" class="popup-box position-absolute bg-white top-0 rounded-1 flex-column justify-content-start align-items-center p-2" style="height: max-content;align-self: center;z-index: 9;width: 95%;">
                    <div class="w-100 d-flex justify-content-between mb-2" style="height: max-content;">
                        <h4 id="popupDetailTitle" class="m-0 p-0">Detail Berita Acara</h4>
                        <a id="tombolClosePopupDetail" class='custom-btn-action btn btn-danger btn-sm' href='#'>
                            <i class="bi bi-x-lg"></i>
                        </a>
                    </div>
                    <div id="popupDetailBody" class="w-100"></div>
                </div>

                <div id="popupBoxDataBarang" class="popup-box position-absolute bg-white top-0 rounded-1 flex-column justify-content-start align-items-center p-2" style="height: max-content;align-self: center;z-index: 10;width: 95%;">
                    <div class="w-100 d-flex justify-content-between mb-2" style="height: max-content;">
                        <h4 id="popupDataBarangTitle" class="m-0 p-0">Tabel Data Barang</h4>
                        <a id="tombolClosePopupDataBarang" class='custom-btn-action btn btn-danger btn-sm' href='#'>
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
                                    <th>Nomor PO</th>
                                    <th>Jenis Perangkat</th>
                                    <th>Merek</th>
                                    <th>Tahun Perolehan</th>
                                    <th>Nama Pengguna</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $no = 1;
                                while ($row = $result_assets2->fetch_assoc()):

                                    // PO
                                    $no_po = !empty($row['no_po']) ? htmlspecialchars($row['no_po']) : '-';

                                    // Serial Number
                                    $serial = !empty($row['serial_number']) ? htmlspecialchars($row['serial_number']) : '-';

                                    // Jenis Perangkat
                                    $jenis_perangkat = !empty($row['category']) ? htmlspecialchars($row['category']) : '-';

                                    // Merek (gabungan manufacturer + asset name)
                                    $merek = !empty($row['asset_merk']) ? htmlspecialchars($row['asset_merk']) : '-';

                                    // Tahun Perolehan
                                    if (!empty($row['tgl_pembelian']) && $row['tgl_pembelian'] !== '0000-00-00') {
                                        $tahun = date('Y', strtotime($row['tgl_pembelian']));
                                    } else {
                                        $tahun = '-';
                                    }

                                    // Pengguna (user)
                                    $user = !empty($row['user']) ? htmlspecialchars($row['user']) : '-';
                                    $pt_name_barang = isset($pt_map_rev[(string)$row['id_pt']]) ? $pt_map_rev[(string)$row['id_pt']] : '-';
                                ?>
                                    <tr
                                        class="pilih-barang"
                                        data-pt="<?= htmlspecialchars($pt_name_barang) ?>"
                                        data-serial="<?= $serial ?>"
                                        data-nopo="<?= $no_po ?>"
                                        data-jenis="<?= $jenis_perangkat ?>"
                                        data-merek="<?= $merek ?>"
                                        data-tahun="<?= $tahun ?>"
                                        data-user="<?= $user ?>">
                                        <td><?= $no ?></td>
                                        <td><?= $serial ?></td>
                                        <td><?= $no_po ?></td>
                                        <td><?= $jenis_perangkat ?></td>
                                        <td><?= $merek ?></td>
                                        <td><?= $tahun ?></td>
                                        <td><?= $user ?></td>
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

        <div id="popupBG" class="popup-bg position-absolute w-100 h-100" style="background-color: rgba(0,0,0,0.5);z-index: 8;"></div>

        <!-- <div id="popupBGClear" class="popup-bg position-absolute w-100 h-100" style="background-color: rgba(0,0,0,1);z-index: 9;"></div> -->

        <div id="popupBG2" class="popup-bg position-absolute w-100 h-100" style="background-color: rgba(0,0,0,0.2); z-index: 9;"></div>

        <!--Awal::Footer Content-->
        <footer class="custom-footer d-flex position-relative p-2" style="border-top: whitesmoke solid 1px; box-shadow: 0px 7px 10px black; color: grey;">
            <p class="position-absolute" style="right: 15px;bottom:7px;color: grey;"><strong>Version </strong>1.1.0</p>
            <p class="pt-2 ps-1"><strong>Copyright &copy 2025</p></strong>
            <p class="pt-2 ps-1 fw-bold text-primary">MIS MSAL.</p>
            <p class="pt-2 ps-1"> All rights reserved</p>
        </footer>
        <!--Akhir::Footer Content-->

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
        let html5QrCode = null;
        let isScanning = false;
        let scanTimeout = null; // ⬅️ timer untuk deteksi gagal

        const modal = document.getElementById("scanModal");
        const openModalBtn = document.getElementById("openScanModal");
        const closeModalBtn = document.querySelector(".close-button");
        const stopScanButton = document.getElementById("stopScanButton");

        // Open Modal
        openModalBtn.addEventListener("click", function() {
            modal.style.display = "block";
            if (!isScanning) {
                initScanner();
            }
        });

        // Close Modal
        closeModalBtn.addEventListener("click", function() {
            modal.style.display = "none";
            stopScanner();
        });

        // Stop Scan manual
        stopScanButton.addEventListener("click", function() {
            stopScanner();
        });

        // Init Scanner
        function initScanner() {
            html5QrCode = new Html5Qrcode("reader");

            Html5Qrcode.getCameras().then(devices => {
                if (devices && devices.length) {
                    let backCamera = devices[0];
                    for (let d of devices) {
                        if (d.label.toLowerCase().includes("back")) {
                            backCamera = d;
                            break;
                        }
                    }

                    html5QrCode.start({
                            deviceId: {
                                exact: backCamera.id
                            }
                        }, {
                            fps: 10,
                            qrbox: {
                                width: 250,
                                height: 250
                            }
                        },
                        onScanSuccess,
                        onScanFailure
                    ).then(() => {
                        document.getElementById("loadingText").style.display = "none";
                        stopScanButton.style.display = "block";
                        isScanning = true;

                        // Mulai timer 10 detik ⏳
                        scanTimeout = setTimeout(() => {
                            if (isScanning) {
                                alert("QR tidak terdeteksi, coba lagi.");
                                stopScanner();
                                modal.style.display = "none";
                            }
                        }, 10000);
                    }).catch(err => {
                        document.getElementById("loadingText").textContent = "Gagal membuka kamera. Gunakan HTTPS atau localhost.";
                    });
                } else {
                    document.getElementById("loadingText").textContent = "Kamera tidak ditemukan.";
                }
            }).catch(err => {
                document.getElementById("loadingText").textContent = "Tidak bisa mengakses kamera.";
            });
        }

        // Stop Scanner
        function stopScanner() {
            if (html5QrCode && isScanning) {
                html5QrCode.stop().then(() => {
                    stopScanButton.style.display = "none";
                    document.getElementById("loadingText").style.display = "block";
                    document.getElementById("loadingText").textContent = "Klik tombol Scan untuk memulai lagi.";
                    isScanning = false;

                    // reset scanner supaya bisa dipanggil ulang
                    html5QrCode.clear();
                    html5QrCode = null;

                    // hapus timer
                    if (scanTimeout) {
                        clearTimeout(scanTimeout);
                        scanTimeout = null;
                    }
                }).catch(err => console.error("Failed to stop:", err));
            }
        }

        // Success callback
        function onScanSuccess(decodedText) {
            // hapus timer
            if (scanTimeout) {
                clearTimeout(scanTimeout);
                scanTimeout = null;
            }

            let assetId = decodedText;
            try {
                const url = new URL(decodedText);
                const parts = url.pathname.split('/');
                assetId = parts[parts.length - 1];
            } catch (e) {}

            document.getElementById('asset_id').value = assetId;
            modal.style.display = "none";
            stopScanner();
            checkAsset(assetId);
        }

        // Failure callback
        function onScanFailure(error) {
            // bisa diabaikan (tidak perlu console log biar tidak spam)
        }

        // Fetch data
        function checkAsset(assetId) {
            fetch('cek_asset.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'asset_id=' + encodeURIComponent(assetId)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const asset = data.data;
                        document.getElementById('serial_number').value = asset.serial;
                        document.getElementById('jenis_perangkat').value = asset.name;
                        document.getElementById('merek').value = asset.model_name;
                        document.getElementById('tahun_perolehan').value = asset.tahun;
                    } else {
                        alert("Aset tidak ditemukan");
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert("Gagal cek aset");
                });
        }
    </script>

    <script>
        //Menghilangkan alert
        const alertBox = document.querySelector('.infoin-approval');
        if (alertBox) {
        setTimeout(() => {
            alertBox.classList.add('fade-out');
            alertBox.classList.remove('fade-in');
        }, 3000);

        setTimeout(() => {
            alertBox.style.display = 'none';
        }, 3500);
        }
    </script>

    <script>
        //Info Akun
        document.addEventListener('DOMContentLoaded', function() {
            const button = document.getElementById('tombolAkun');
            const box = document.getElementById('akunInfo');

            button.addEventListener('click', function() {
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
        //Popup Data Barang
        document.addEventListener('DOMContentLoaded', function() {
            var close = document.getElementById('tombolClosePopupDataBarang');
            var box = document.getElementById('popupBoxDataBarang');
            var background = document.getElementById('popupBG2');

            // Delegasi klik: berlaku untuk tombol di form input maupun form edit
            document.addEventListener('click', function(e) {
                if (e.target.closest('.tombolDataBarangPopup')) {
                    box.classList.add('aktifPopup');
                    background.classList.add('aktifPopup');
                    box.classList.add('scale-in-center');
                    box.classList.remove('scale-out-center');
                    background.classList.add('fade-in');
                    background.classList.remove('fade-out');
                }
            });

            close.addEventListener('click', function() {
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

    <script>
        //Nilai data barang
        var activeTarget = 'input'; // default: form input

        document.addEventListener('DOMContentLoaded', function() {
            // simpan target aktif saat buka popup
            $(document).on('click', '.tombolDataBarangPopup', function() {
                activeTarget = $(this).data('target');
            });

            // klik baris barang
            $(document).on('click', '.pilih-barang', function() {
                var serial = $(this).data('serial');
                var nopo = $(this).data('nopo');
                var jenis = $(this).data('jenis');
                var merek = $(this).data('merek');
                var tahun = $(this).data('tahun');
                var user = $(this).data('user');

                if (activeTarget === 'edit') {
                    $('#serial_number_edit').val(serial);
                    $('#nomor_po_edit').val(nopo);
                    $('#jenis_perangkat_edit').val(jenis);
                    $('#merek_edit').val(merek);
                    $('#tahun_perolehan_edit').val(tahun);
                    $('#pengguna_edit').val(user);
                } else {
                    $('#serial_number_input').val(serial);
                    $('#nomor_po_input').val(nopo);
                    $('#jenis_perangkat_input').val(jenis);
                    $('#merek_input').val(merek);
                    $('#tahun_perolehan_input').val(tahun);
                    $('#pengguna_input').val(user);
                }

                // Tutup popup
                $('#popupBoxDataBarang').removeClass('aktifPopup');
                $('#popupBG2').removeClass('aktifPopup');
            });
        });
    </script>

    <script>
        //Delete data
        //Sistem tombol popup delete
        document.addEventListener('DOMContentLoaded', function() {
            // const open = document.querySelector('.tombolPopupDelete');
            const close = document.getElementById('tombolClosePopupDelete');
            const accDelete = document.getElementById('tombolAccDelete');
            const box = document.getElementById('popupBoxDelete');
            const background = document.getElementById('popupBG');

            let selectedId = null;

            document.querySelectorAll('.tombolPopupDelete').forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();

                    const selectedId = this.getAttribute('data-id');
                    const isPending = this.getAttribute('data-pending') === 'true';
                    const pendingValue = isPending ? 1 : 0;

                    // set POST value
                    document.getElementById('deleteId').value = selectedId;
                    document.getElementById('deletePending').value = pendingValue;

                    const alasanWrapper = document.getElementById('alasanWrapper');
                    const alasanHapus = document.getElementById('alasanHapus');

                    // reset dulu
                    alasanWrapper.classList.add('d-none');
                    alasanHapus.required = false;
                    alasanHapus.value = '';

                    // ===== JIKA PENDING =====
                    if (isPending) {
                        alasanWrapper.classList.remove('d-none');
                        alasanHapus.required = true;
                    }

                    // ===== WARNING TETAP PUNYA KAMU =====
                    const oldWarning = box.querySelector('.warning-approval');
                    if (oldWarning) oldWarning.remove();

                    if (isPending) {
                        const buttonWrapper = box.querySelector('.w-50.d-flex.justify-content-around');

                        const warning = document.createElement('p');
                        warning.className = 'warning-approval text-warning mt-3 text-center fs-7 border border-start-0 border-end-0 border-bottom-0';
                        warning.innerHTML = `
                            <i class="bi bi-exclamation-triangle"></i>
                            Surat sudah ada yang menyetujui, data yang akan dihapus butuh approval pihak terkait.
                        `;

                        buttonWrapper.insertAdjacentElement('afterend', warning);
                    }

                    // ===== SISTEM POPUP ASLI (TIDAK DIUBAH) =====
                    box.classList.add('aktifPopup');
                    background.classList.add('aktifPopup');
                    box.classList.add('scale-in-center');
                    box.classList.remove('scale-out-center');
                    background.classList.add('fade-in');
                    background.classList.remove('fade-out');
                });
            });


            close.addEventListener('click', function(e) {
                e.preventDefault();
                box.classList.remove('aktifPopup');
                background.classList.remove('aktifPopup');
                // setTimeout(() => {
                //     background.classList.remove('aktifPopup');
                //     box.classList.remove('aktifPopup');
                // }, 300); 
                // box.classList.remove('scale-in-center');
                // box.classList.add('scale-out-center');
                // background.classList.remove('fade-in');
                // background.classList.add('fade-out');
            });


            background.addEventListener('click', function() {
                box.classList.remove('aktifPopup');
                background.classList.remove('aktifPopup');
                // setTimeout(() => {
                //     background.classList.remove('aktifPopup');
                //     box.classList.remove('aktifPopup');
                // }, 300); 
                // box.classList.remove('scale-in-center');
                // box.classList.add('scale-out-center');
                // background.classList.remove('fade-in');
                // background.classList.add('fade-out');
            });

        });
    </script>

    <script> //modul penambahan
        document.addEventListener('DOMContentLoaded', function () {
            const UPGRADE_CATEGORY_NAME = 'UPGRADE';
            const FALLBACK_UPGRADE_ID = '12';

            function getUpgradeCategoryValue(selectEl) {
                if (!selectEl) return FALLBACK_UPGRADE_ID;

                const opt = Array.from(selectEl.options).find(o =>
                    (o.textContent || '').trim().toUpperCase() === UPGRADE_CATEGORY_NAME
                );

                return opt ? String(opt.value) : FALLBACK_UPGRADE_ID;
            }

            function replaceKerusakanText(baseText, toPenambahan) {
                if (!baseText) return baseText;
                if (!toPenambahan) return baseText;

                // Ganti "Kerusakan" & "kerusakan"
                return baseText
                    .replace(/Kerusakan/g, 'Penambahan')
                    .replace(/kerusakan/g, 'penambahan');
            }

            function applyBAPenambahanMode(formEl, isChecked) {
                if (!formEl) return;

                // 1) Ubah teks label/h3/h5 yang ditandai
                const textTargets = formEl.querySelectorAll('[data-ba-kerusakan-text]');
                textTargets.forEach(el => {
                    if (!el.dataset.originalText) {
                        el.dataset.originalText = el.textContent.trim();
                    }
                    el.textContent = replaceKerusakanText(el.dataset.originalText, isChecked);
                });

                // 2) Atur dropdown kategori (hide UPGRADE jika tidak centang)
                const kategoriSelect = formEl.querySelector('.kategoriKerusakan, .kategoriKerusakanEdit');
                if (kategoriSelect) {
                    const upgradeValue = getUpgradeCategoryValue(kategoriSelect);
                    const options = Array.from(kategoriSelect.options);
                    const upgradeOption = options.find(opt => String(opt.value) === String(upgradeValue));

                    // reset dulu semua option ke normal
                    options.forEach(opt => {
                        opt.disabled = false;
                        opt.hidden = false;
                    });

                    if (isChecked) {
                        // Tampilkan UPGRADE lalu paksa pilih UPGRADE
                        if (upgradeOption) {
                            upgradeOption.hidden = false;
                            upgradeOption.disabled = false;
                        }

                        kategoriSelect.value = String(upgradeValue);

                        // Kunci pilihan hanya UPGRADE (placeholder & lainnya disable)
                        options.forEach(opt => {
                            const isUpgrade = String(opt.value) === String(upgradeValue);
                            opt.disabled = !isUpgrade;
                        });

                    } else {
                        // Sembunyikan & disable UPGRADE
                        if (upgradeOption) {
                            // kalau sebelumnya sedang terpilih UPGRADE, reset ke placeholder
                            if (String(kategoriSelect.value) === String(upgradeValue)) {
                                kategoriSelect.value = '';
                            }
                            upgradeOption.hidden = true;
                            upgradeOption.disabled = true;
                        }

                        // opsi lain tetap aktif
                        options.forEach(opt => {
                            if (String(opt.value) !== String(upgradeValue)) {
                                opt.disabled = false;
                            }
                        });
                    }

                    // trigger event supaya logic DLL tetap jalan
                    kategoriSelect.dispatchEvent(new Event('change', { bubbles: true }));
                }
            }

            // init generic per form
            window.initBAPenambahanToggle = function(formEl, checkboxEl) {
                if (!formEl || !checkboxEl) return;

                // hindari double binding
                if (checkboxEl.dataset.baPenambahanBound === '1') return;
                checkboxEl.dataset.baPenambahanBound = '1';

                // kalau kategori sudah UPGRADE (kasus edit), auto aktifkan mode penambahan
                const kategoriSelect = formEl.querySelector('.kategoriKerusakan, .kategoriKerusakanEdit');
                if (kategoriSelect) {
                    const upgradeValue = getUpgradeCategoryValue(kategoriSelect);
                    if (String(kategoriSelect.value) === String(upgradeValue)) {
                        checkboxEl.checked = true;
                    }
                }

                checkboxEl.addEventListener('change', function () {
                    applyBAPenambahanMode(formEl, this.checked);
                });

                // apply awal (terutama untuk form edit yang datanya kategori=UPGRADE)
                applyBAPenambahanMode(formEl, checkboxEl.checked);
            };

            // ===== INIT FORM INPUT =====
            const formInput = document.querySelector('#popupBoxInput form.popupInput');
            const checkInput = document.getElementById('checkPenambahanInput');
            if (formInput && checkInput) {
                window.initBAPenambahanToggle(formInput, checkInput);
            }
        });
    </script>

    <script> //Form Input
        //Sistem tombol popup input
        document.addEventListener('DOMContentLoaded', function() {
            const open = document.getElementById('tombolInputPopup');
            const close = document.getElementById('tombolClosePopup');
            const box = document.getElementById('popupBoxInput');
            const background = document.getElementById('popupBG');
            const tabel = document.getElementById('custom-main');

            open.addEventListener('click', function() {
                box.classList.add('aktifPopup');
                background.classList.add('aktifPopup');
                box.classList.add('scale-in-center');
                box.classList.remove('scale-out-center');
                background.classList.add('fade-in');
                background.classList.remove('fade-out');
                tabel.style.overflowY = 'hidden';
            });

            close.addEventListener('click', function() {
                box.classList.remove('aktifPopup');
                background.classList.remove('aktifPopup');
                tabel.style.overflowY = 'auto';
                // setTimeout(() => {
                //     background.classList.remove('aktifPopup');
                //     box.classList.remove('aktifPopup');
                // }, 300); 
                // box.classList.remove('scale-in-center');
                // box.classList.add('scale-out-center');
                // background.classList.remove('fade-in');
                // background.classList.add('fade-out');
            });
            background.addEventListener('click', function() {
                box.classList.remove('aktifPopup');
                background.classList.remove('aktifPopup');
                tabel.style.overflowY = 'auto';
                // setTimeout(() => {
                //     background.classList.remove('aktifPopup');
                //     box.classList.remove('aktifPopup');
                // }, 300); 
                // box.classList.remove('scale-in-center');
                // box.classList.add('scale-out-center');
                // background.classList.remove('fade-in');
                // background.classList.add('fade-out');
            });
        });

        //Fungsi nomor BA
        document.addEventListener('DOMContentLoaded', function() {
            const tanggalInput = document.getElementById('tanggal');
            const nomorBaInput = document.getElementById('nomor_ba');
            const ptInput = document.getElementById('perusahaan');

            function updateNomorBA() {
                const tanggal = tanggalInput ? tanggalInput.value : '';
                const pt = ptInput ? ptInput.value : '';

                if (!tanggal || !pt) return;

                fetch(`ambil_nomor_ba.php?tanggal=${encodeURIComponent(tanggal)}&pt=${encodeURIComponent(pt)}`)
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
            if (tanggalInput) tanggalInput.addEventListener('change', updateNomorBA);

            // Update saat PT diubah
            if (ptInput) ptInput.addEventListener('change', updateNomorBA);
        });

        //Trigger data karyawan via PT (DINAMIS mengikuti pilihan PT)
        const ptSelect = document.getElementById('perusahaan');
        const lantaiWrapper = document.getElementById('lantai-wrapper');
        const userWrapper = document.getElementById('user-wrapper');

        const groupLantaiHO = document.getElementById('group_lantai_ho');
        const groupLokasiNonHO = document.getElementById('group_lokasi_nonho');

        const lokasiSelect = document.getElementById('lokasi');
        const lokasiText = document.getElementById('lokasi_text');

        const groupPeminjamHO = document.getElementById('group_peminjam_ho');
        const groupPeminjamNonHO = document.getElementById('group_peminjam_nonho');

        const userSelectEl = document.getElementById('user');
        const peminjamText = document.getElementById('peminjam_text');

        const groupAtasanHO = document.getElementById('group_atasan_ho');
        const groupAtasanNonHO = document.getElementById('group_atasan_nonho');

        const atasanSelectEl = document.getElementById('atasan_peminjam');
        const atasanText = document.getElementById('atasan_text');

        function setModeByPT(selectedPT) {
            selectedPT = (selectedPT || '').trim();

            if (!selectedPT) {
                lantaiWrapper.style.display = 'none';
                userWrapper.style.display = 'none';
                return;
            }

            lantaiWrapper.style.display = 'flex';
            userWrapper.style.display = 'flex';

            // CLEAR data barang kalau PT diganti (biar tidak mismatch)
            $('#serial_number_input').val('');
            $('#nomor_po_input').val('');
            $('#jenis_perangkat_input').val('');
            $('#merek_input').val('');
            $('#tahun_perolehan_input').val('');
            $('#pengguna_input').val('');

            if (selectedPT === 'PT.MSAL (HO)') {
                // ===== HO MODE (SELECT) =====
                groupLantaiHO.style.display = 'flex';
                groupLokasiNonHO.style.display = 'none';

                lokasiSelect.disabled = false;
                lokasiText.disabled = true;
                lokasiText.value = '';

                groupPeminjamHO.style.display = 'flex';
                groupPeminjamNonHO.style.display = 'none';

                userSelectEl.disabled = false;
                peminjamText.disabled = true;
                peminjamText.value = '';

                groupAtasanHO.style.display = 'flex';
                groupAtasanNonHO.style.display = 'none';

                atasanSelectEl.disabled = false;
                atasanText.disabled = true;
                atasanText.value = '';

            } else {
                // ===== NON-HO MODE (TEXT) =====
                groupLantaiHO.style.display = 'none';
                groupLokasiNonHO.style.display = 'flex';

                lokasiSelect.disabled = true;
                lokasiSelect.value = '';
                lokasiText.disabled = false;

                groupPeminjamHO.style.display = 'none';
                groupPeminjamNonHO.style.display = 'flex';

                userSelectEl.disabled = true;
                userSelectEl.innerHTML = '<option value="">-- Pilih Pengguna --</option>';
                peminjamText.disabled = false;

                groupAtasanHO.style.display = 'none';
                groupAtasanNonHO.style.display = 'flex';

                atasanSelectEl.disabled = true;
                atasanSelectEl.innerHTML = '<option value="">-- Pilih Atasan Pengguna --</option>';
                atasanText.disabled = false;
            }

            // redraw tabel barang (lock PT)
            if ($.fn.DataTable.isDataTable('#myTable2')) {
                $('#myTable2').DataTable().draw();
            }
        }

        // init saat popup dibuka / halaman load
        setModeByPT(ptSelect.value);

        // saat PT diganti
        ptSelect.addEventListener('change', function() {
            setModeByPT(this.value);
        });

        //Fungsi Sortir Karyawan dan Atasan Karyawan
        const userSelect = document.getElementById('user');
        const lantaiSelect = document.getElementById('lokasi');

        // Data user dari PHP dimasukkan ke JS
        const dataKaryawan = <?= json_encode($data_karyawan) ?>;
        const dataDeptHead = <?= json_encode($data_atasan) ?>;

        lantaiSelect.addEventListener('change', function() {
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

        function isAutoDashJabatan(jabatan) {
            jabatan = String(jabatan || '').trim();
            return (
                jabatan === "Dept. Head" ||
                jabatan === "AVP" ||
                jabatan === "Direktur" ||
                jabatan === "CEO" ||
                jabatan === "VICE CEO"
            );
        }

        function buildAtasanOptionsByDept(atasanSelect, dataAtasan, departemen, selectedValue) {
            atasanSelect.innerHTML = '<option value="">-- Pilih Atasan Pengguna --</option>';

            var filteredAtasan = dataAtasan.filter(function(a) {
                return String(a.departemen || '').trim() === String(departemen || '').trim();
            });

            for (var i = 0; i < filteredAtasan.length; i++) {
                var atasan = filteredAtasan[i];
                var option = document.createElement('option');
                option.value = atasan.nama;
                option.textContent = atasan.nama + ' - ' + atasan.posisi + ' (' + atasan.departemen + ')';

                if (selectedValue && atasan.nama === selectedValue) {
                    option.selected = true;
                }

                atasanSelect.appendChild(option);
            }

            atasanSelect.disabled = filteredAtasan.length === 0;
        }

        userSelect.addEventListener('change', function() {
            var selectedNama = this.value;
            var userData = dataKaryawan.find(function(k) {
                return k.nama === selectedNama;
            });

            atasanSelect.innerHTML = '<option value="">-- Pilih Atasan Pengguna --</option>';

            if (!userData) {
                atasanSelect.disabled = true;
                return;
            }

            if (isAutoDashJabatan(userData.jabatan)) {
                atasanSelect.innerHTML = '<option value="-">-</option>';
                atasanSelect.disabled = false;
                return;
            }

            buildAtasanOptionsByDept(atasanSelect, dataDeptHead, userData.departemen, '');
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
            input.onchange = function() {
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
            jepretKamera.onclick = async function() {
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
                            video: {
                                deviceId: targetCam.deviceId
                            }
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

                btnSwitch.onclick = async function() {
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

                btnCapture.onclick = function() {
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
                    btnGroup.remove();

                    preview.src = canvas.toDataURL("image/png");
                    preview.style.display = "block";

                    canvas.toBlob(function(blob) {
                        const timestamp = Date.now();
                        const nomorBA = document.getElementById('nomor_ba').value || "NOBA";

                        const tanggalBAraw = document.getElementById('tanggal').value || "NOTGL";
                        let tanggalBA = "NOTGL";

                        if (tanggalBAraw.includes("-")) {
                            const [yyyy, mm, dd] = tanggalBAraw.split("-");
                            tanggalBA = `${dd}${mm}${yyyy}`;
                        }

                        const filename = `camera${nomorBA}BAK${tanggalBA}-${timestamp}.png`;

                        const file = new File([blob], filename, {
                            type: "image/png"
                        });
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
            btnHapus.onclick = function() {
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

    <script> //Form Edit
        document.addEventListener('DOMContentLoaded', function() {
            // utility function untuk escape HTML
            function escapeHtml(str = '') {
                return String(str)
                    .replaceAll('&', '&amp;')
                    .replaceAll('<', '&lt;')
                    .replaceAll('>', '&gt;')
                    .replaceAll('"', '&quot;')
                    .replaceAll("'", '&#039;');
            }

            // Konversi tanggal ke format Romawi (MM/YYYY)
            function formatTanggalRomawi(tanggalStr) {
                const bulanRomawi = ['', 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];
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
            const button = document.querySelector('.tombolPopupEdit');
            const tabel = document.getElementById('custom-main');

            button.addEventListener('click', function() {
                tabel.style.overflowY = 'hidden';
            });

            closeBtn.addEventListener('click', function() {
                tabel.style.overflowY = 'auto';
            });
            bg.addEventListener('click', function() {
                tabel.style.overflowY = 'auto';
            });

            if (!box || !bg || !body) {
                console.error('Popup elements not found: pastikan #popupBoxEdit, #popupBG, #popupEditBody ada di DOM.');
                return;
            }
            // Untuk Judul Popup
            document.addEventListener('click', function(e) {
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
                fetch('get_edit_ba_kerusakan.php?id=' + encodeURIComponent(id), {
                        cache: 'no-store'
                    })
                    .then(resp => {
                        if (!resp.ok) throw new Error('HTTP ' + resp.status);
                        return resp.json();
                    })
                    .then(res => {
                        if (res.error) throw new Error(res.error);
                        // render form langsung (tanpa loading)
                        console.log("DEBUG RESPONSE:", res);
                        if (!res || !res.data) {
                            throw new Error('Data tidak ditemukan atau kosong');
                        }
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
                box.classList.add('scale-in-center');
                box.classList.remove('scale-out-center');
                bg.classList.add('fade-in');
                bg.classList.remove('fade-out');

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
            document.addEventListener('keydown', function(e) {
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
            <button type="button" class="btn btn-danger btn-sm" onclick="hapusGambarLama(this, ${escapeHtml(row.id)})"><i class="bi bi-trash3-fill"></i></button>
            <input type="hidden" name="hapus_gambar[]" value="" class="hapus-gambar-${escapeHtml(row.id)}">
            </div>`;
                });

                body.innerHTML = `
        <form class="popupEdit d-flex flex-column" method="post" action="proses_edit.php" enctype="multipart/form-data">
            <input type="hidden" name="id" value="${escapeHtml(data.id)}">
            <div class="form-section">
            <div class="row position-relative">
                <div class="custom-input-form col-8">
                <h3 data-ba-kerusakan-text>Data Berita Acara Kerusakan</h3>

                <div class="custom-row-tgl-no row">
                    <div class="custom-input-tanggal col-3">
                        <div class="input-group" style="width:100%;">
                            <span class="input-group-text custom-font-form">Tanggal</span>
                            <input class="form-control custom-font-form" type="date" name="tanggal" id="tanggal_edit" max="${new Date().toISOString().slice(0,10)}" value="${escapeHtml(data.tanggal||'')}" required>
                        </div>
                    </div>
                    <div class="custom-form-nomor" style="width:180px;">
                        <div class="input-group" style="width:100%;">
                            <span class="input-group-text custom-font-form">Nomor BA</span>
                            <input type="text" class="form-control custom-font-form" maxlength="3" name="nomor_ba" id="nomor_ba_edit" value="${escapeHtml(data.nomor_ba||'')}" readonly>
                        </div>
                    </div>
                    <div class="custom-form-pt-top col-4">
                        <div class="input-group" style="width:220px;">
                            <span class="input-group-text custom-font-form">PT</span>
                            <select name="pt" id="edit-pt" class="form-select custom-font-form" required>
                                <option value="">-- Pilih PT --</option>
                                <?php foreach ($pt_list as $ptx): ?>
                                    <option value="<?= htmlspecialchars($ptx, ENT_QUOTES) ?>" ${data.pt === <?= json_encode($ptx) ?> ? 'selected' : ''}>
                                        <?= htmlspecialchars($ptx) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="custom-form-penambahan">
                        <div class="form-check" style="width: 100%;">
                            <input class="form-check-input" type="checkbox" value="" id="checkPenambahanEdit" ${String(data.kategori_kerusakan_id) === "12" ? "checked" : ""}>
                            <label class="form-check-label custom-font-form" for="checkPenambahanEdit">
                                BA Penambahan
                            </label>
                        </div>
                    </div>
                </div>

                

                <div class="row mt-3 border border-1 p-1 rounded-2 me-1">
                    <div class="custom-row-search-db row pt-1 pb-2">
                        <div class="col-4 d-flex flex-column custom-row-search-db-child">
                            <h5>Data barang</h5>

                            <div class="custom-btn-data-barang d-flex">
                                <div id="" class=" tombolDataBarangPopup btn btn-primary rounded-end-0 " data-target="edit"><i class="bi bi-search"></i></div>
                                <!-- <div class="btn btn-primary rounded-start-0" id="openScanModal"><i class="bi bi-qr-code-scan"></i></div> -->
                                <button type="button" id="openScanModal" class="btn btn-primary rounded-start-0">
                                    <i class="bi bi-qr-code-scan"></i>
                                </button>
                            </div>
                            
                        </div>
                        
                    </div>

                    <div class="row pe-0 w-100">

                    <div class="custom-form-sn col-6">
                        <div class="input-group">
                            <span class="input-group-text custom-font-form" style="padding-right:63px;">SN</span>
                            <input id="serial_number_edit" class="form-control custom-font-form" type="text" name="sn" value="${escapeHtml(data.sn||'')}" readonly>
                        </div>
                    </div>
                    <div class="custom-form-nopo col-6">
                        <div class="input-group">
                            <span class="input-group-text custom-font-form" style="padding-right:52px;">Nomor PO</span>
                            <input id="nomor_po_edit" class="form-control custom-font-form" type="text" name="nomor_po" value="${escapeHtml(data.no_po||'')}" readonly>
                        </div>
                    </div>

                    <div class="custom-form-merk col-6 mt-3">
                        <div class="input-group">
                            <span class="input-group-text custom-font-form" style="padding-right:37px;">Merek</span>
                            <input id="merek_edit" class="form-control custom-font-form" type="text" name="merek" value="${escapeHtml(data.merek||'')}" readonly>
                        </div>
                    </div>
                    <div class="custom-form-jp col-6 mt-3">
                        <div class="input-group">
                        <span class="input-group-text custom-font-form" style="padding-right:18px;">Jenis Perangkat</span>
                        <input id="jenis_perangkat_edit" class="form-control custom-font-form" type="text" name="jenis_perangkat" value="${escapeHtml(data.jenis_perangkat||'')}" readonly>
                        </div>
                    </div>

                    <div class="custom-form-pengguna col-6 mt-3">
                        <div class="input-group">
                            <span class="input-group-text custom-font-form">Pengguna</span>
                            <input id="pengguna_edit" type="text" class="form-control custom-font-form" name="user" value="${escapeHtml(data.user||'')}" readonly>
                        </div>
                    </div>
                    <div class="custom-form-tp  col-6 mt-3">
                        <div class="input-group">
                            <span class="input-group-text custom-font-form" style="padding-right:12px;">Tahun Perolehan</span>
                            <input id="tahun_perolehan_edit" type="text" class="form-control custom-font-form" name="tahun_perolehan" min="2007" max="${currentYear}" step="1" value="${escapeHtml(data.tahun_perolehan||'')}" readonly>
                        </div>
                    </div>

                    </div>

                </div>

                <div class="row mt-3 border border-1 p-1 rounded-2 me-1">
                    <div class="row"><h5>Data Pengguna</h5></div>
                    <div class="custom-row-lokasi-lantai row">
                    <input type="hidden" name="id_pt" value="${escapeHtml(data.id_pt||'')}">
                    <?php
                    if ($pt_filter === 'PT.MSAL (HO)') {
                    ?>
                    <div class="custom-form-lantai col-3">
                        <div class="input-group">
                        <span class="input-group-text custom-font-form">Lantai</span>
                        <select name="lokasi" id="edit-lokasi" class="form-select custom-font-form" ${data.pt !== 'PT.MSAL (HO)' ? 'disabled' : ''} required>
                            <option value="">-- Pilih Lantai --</option>
                        </select>
                        </div>
                    </div>
                    <?php } elseif ($pt_filter === 'PT.MSAL (SITE)' || $pt_filter !== 'PT.MSAL (HO)') {
                    ?>
                        <div class="custom-form-lantai col-5">
                            <div class="input-group">
                                <span class="input-group-text custom-font-form">Lokasi</span>
                                <input type="text" name="lokasi" class="form-control custom-font-form " placeholder="Detail Lokasi" value="${escapeHtml(data.lokasi||'')}" required>
                            </div>
                        </div>
                    <?php } else {
                    ?>
                        <div class="custom-form-lantai col-5">
                            <div class="input-group">
                                <span class="input-group-text custom-font-form">Lokasi</span>
                                <input type="text" name="lokasi" class="form-control custom-font-form " placeholder="Detail Lokasi" required>
                            </div>
                        </div>
                    <?php
                    }
                    ?>
                    </div>

                    <div class="custom-row-pengguna-atasan row mt-3 pe-0">
                    <div class="custom-form-pengguna2 col-6">
                        <div class="input-group">
                        <?php
                        if ($pt_filter === 'PT.MSAL (HO)') {
                        ?>
                        <span class="input-group-text custom-font-form">Pengguna</span>
                        <select name="peminjam" id="edit-user" class="form-select custom-font-form" required>
                            <option value="">-- Pilih Pengguna --</option>
                            ${karyawan.map(user => `
                                <option value="${escapeHtml(user.nama)}" ${data.peminjam === user.nama ? 'selected' : ''}>
                                    ${escapeHtml(user.nama)}
                                </option>
                            `).join('')}
                        </select>
                        <?php
                        } elseif ($pt_filter === 'PT.MSAL (SITE)' || $pt_filter !== 'PT.MSAL (HO)') {
                        ?>
                            <span class="input-group-text custom-font-form">Pengguna</span>
                            <input type="text" name="peminjam" class="form-control custom-font-form" placeholder="Nama Pengguna" value="${escapeHtml(data.peminjam||'')}" required>
                        <?php
                        } else {
                        ?>
                            <span class="input-group-text custom-font-form">Pengguna</span>
                            <input type="text" name="peminjam" class="form-control custom-font-form" placeholder="Nama Pengguna" required>
                        <?php
                        }
                        ?>
                        </div>
                    </div>
                    <div class="custom-form-atasan col-6 pe-0">
                        <div class="input-group">

                        <?php
                        if ($pt_filter === 'PT.MSAL (HO)') {
                        ?>

                        <span class="input-group-text custom-font-form">Atasan Pengguna</span>
                        <select name="atasan_peminjam" id="edit-atasan" class="form-select custom-font-form">
                            <option value="">-- Pilih Atasan Pengguna --</option>
                            ${atasan.map(a => `
                            <option value="${escapeHtml(a.nama)}" ${data.atasan_peminjam === a.nama ? 'selected' : ''}>
                                ${escapeHtml(a.nama)}
                            </option>
                        `).join('')}
                        </select>
                        <?php
                        } elseif ($pt_filter === 'PT.MSAL (SITE)' || $pt_filter !== 'PT.MSAL (HO)') {
                        ?>
                            <span class="input-group-text custom-font-form">Atasan Pengguna</span>
                            <input type="text" name="atasan_peminjam" class="form-control custom-font-form" placeholder="Atasan Pengguna" value="${escapeHtml(data.atasan_peminjam||'')}" required>
                        <?php
                        } else {
                        ?>
                            <span class="input-group-text custom-font-form">Atasan Pengguna</span>
                            <input type="text" name="atasan_peminjam" class="form-control custom-font-form" placeholder="Atasan Pengguna" required>
                        <?php
                        }
                        ?>
                        </div>
                    </div>
                    </div>
                </div>

                <div class="row mt-3 border border-1 p-1 rounded-2 me-1" style="z-index: 11;">
                    <div class="row"><h5 data-ba-kerusakan-text>Laporan Kerusakan</h5></div>
                    <div class="row pe-0">
                        <div class="custom-form-jk  col-6">
                            <div class="input-group">
                                <span class="input-group-text custom-font-form" style="padding-right: 27px;" data-ba-kerusakan-text>Jenis Kerusakan</span>
                                <textarea name="deskripsi" class="form-control custom-font-form" style="font-size:small;" rows="3" required>${escapeHtml(data.deskripsi||'')}</textarea>
                            </div>
                        </div>
                        <div class="custom-form-pk  col-6 pe-0">
                            <div class="input-group">
                                <span class="input-group-text custom-font-form" data-ba-kerusakan-text>Penyebab Kerusakan</span>
                                <textarea name="penyebab_kerusakan" class="form-control custom-font-form" style="font-size:small;" rows="3" required>${escapeHtml(data.penyebab_kerusakan||'')}</textarea>
                            </div>
                        </div>
                    </div>
                    <div class="custom-row-rm-kat row mt-3 pe-0">
                        <div class="custom-form-rm  col-12 mb-3 pe-0">
                            <div class="input-group">
                                <span class="input-group-text custom-font-form">Rekomendasi MIS</span>
                                <textarea name="rekomendasi_mis" class="form-control custom-font-form" style="font-size:small;" rows="2" required>${escapeHtml(data.rekomendasi_mis||'')}</textarea>
                            </div>
                        </div>
                    
                        <div class="custom-form-kk  col-6 mb-3">
                            <div class="input-group">
                                <span class="input-group-text custom-font-form" style="padding-right: 20px;">Kategori</span>
                                <select name="kategori_kerusakan" class="form-select kategoriKerusakanEdit custom-font-form" required>
                                    <option value="">-- Pilih Kategori --</option>
                                    ${(() => {
                                        let html = "";
                                        <?php
                                        $query = "SELECT id, nama FROM categories_broken ORDER BY id ASC";
                                        $broken = mysqli_query($koneksi, $query);
                                        if ($broken && mysqli_num_rows($broken) > 0) {
                                            while ($row = mysqli_fetch_assoc($broken)) {
                                        ?>
                                                html += '<option value="<?= htmlspecialchars($row['id']) ?>" ' +
                                                        (data.kategori_kerusakan_id == "<?= htmlspecialchars($row['id']) ?>" ? "selected" : "") +
                                                        '><?= htmlspecialchars($row['nama']) ?></option>';
                                                <?php
                                            }
                                        }
                                                ?>
                                        return html;
                                    })()}
                                </select>
                                
                            </div>
                        </div>
                        <div class="custom-input-dll  col-6 pe-0 dllWrapperEdit" style="display: none;">
                            <div class="input-group">
                                <span class="input-group-text">Keterangan</span>
                                <textarea name="keterangan_dll" class="form-control keteranganDllEdit" style="font-size:small;" rows="1"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                </div>

                <div class="custom-input-gambar-section col-4">
                    <h3>Gambar</h3>
                    <div class="custom-input-gambar border border-2 rounded-3 p-1" style="height:485px; overflow-y:auto;">
                        <div class="d-flex flex-column">
                            <div id="edit-gambar-container" class="d-flex flex-column gap-2">
                                ${gambarHTML}
                            </div>
                            <button type="button" class="btn btn-primary w-50 align-self-center mb-1" onclick="tambahGambarEdit()">+ Tambah Gambar Kerusakan</button>
                        </div>
                    </div>

                    <div class="mt-1" style="height:max-content;">
                        <div class="row mt-3 pe-0 custom-form-ae">
                            <div class="input-group">
                                <span class="input-group-text custom-font-form">Alasan perubahan</span>
                                <textarea name="alasan_perubahan" class="form-control custom-font-form" style="font-size:small;" rows="2" required></textarea>
                            </div>
                        </div>
                    </div>
                    
                    ${
                    (data.pending_edit == "1")
                    ? `
                    <div class="mt-1 ps-1" style="height:max-content;">
                        <div class="row"><h6 class="text-warning">*Ada data edit anda yang masih menunggu persetujuan</h6></div>
                        <div class="row overflow-x-auto ps-2">
                            <table id="" class="table table-bordered table-striped text-start"
                            style="width: max-content; table-layout: auto; white-space: nowrap;"
                            >
                                <thead>
                                <tr>
                                    <th class="text-start">Data</th>
                                    ${data.header_edit.map(h => `<th class="text-start">${h}</th>`).join('')}
                                </tr>
                                </thead>
                                <tbody>
                                <tr>
                                    <td class="text-start">Lama</td>
                                    ${data.data_edit_lama.map(v => `<td class="text-start">${v}</td>`).join('')}
                                </tr>
                                <tr>
                                    <td class="text-start">Baru</td>
                                    ${data.data_edit_baru.map(v => `<td class="text-start">${v}</td>`).join('')}
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    `
                    : ``
                    }

                </div>
                
                

            </div>
            </div>

            <div class="footer-form d-flex w-100 justify-content-between">
                <h5 class="custom-font-form text-formulir m-0 mt-3" style="color: darkgray;">*Formulir ini untuk melaporkan kerusakan dan rekomendasi perbaikan aset</h5>
                <div class="custom-form-submit w-25 align-self-end">
                        ${
                        (data.pending_edit == "1")
                        ? `
                        <p class="custom-font-form m-0 mb-1 text-warning"><i class="bi bi-exclamation-triangle"></i> Dengan melakukan submit, data edit anda yang saat ini menunggu persetujuan akan dihapus.</p>
                        `
                        :
                        (
                            (data.approval_1 == 1 ||
                            data.approval_2 == 1 ||
                            data.approval_3 == 1 ||
                            data.approval_4 == 1 ||
                            data.approval_5 == 1)
                            ? `
                            <p class="custom-font-form m-0 mb-1 text-warning"><i class="bi bi-exclamation-triangle"></i> Surat sudah ada yang menyetujui, data yang diedit akan butuh approval pihak terkait.</p>
                            `
                            : ``
                        )
                        }
                    <input class="w-100 mt-0" type="submit" value="Simpan">
                </div>
                
            </div>
        </form>
        `;

                const kategoriSelect = body.querySelector('.kategoriKerusakanEdit');
                const dllWrapper = body.querySelector('.dllWrapperEdit');
                const keteranganDll = body.querySelector('.keteranganDllEdit');

                if (kategoriSelect && dllWrapper && keteranganDll) {
                    // inisialisasi awal sesuai data
                    if (String(data.kategori_kerusakan_id) === "10") {
                        dllWrapper.style.display = 'block';
                        keteranganDll.setAttribute('required', 'required');
                        keteranganDll.value = data.keterangan_dll ? escapeHtml(data.keterangan_dll) : '';
                    } else {
                        dllWrapper.style.display = 'none';
                        keteranganDll.removeAttribute('required');
                        keteranganDll.value = '';
                    }

                    // pasang listener change
                    kategoriSelect.addEventListener('change', function() {
                        if (this.value === "10") {
                            dllWrapper.style.display = 'block';
                            keteranganDll.setAttribute('required', 'required');
                        } else {
                            dllWrapper.style.display = 'none';
                            keteranganDll.removeAttribute('required');
                            keteranganDll.value = '';
                        }
                    });
                }

                // INIT BA Penambahan (form edit)
                try {
                    const formEdit = body.querySelector('form.popupEdit');
                    const checkEdit = body.querySelector('#checkPenambahanEdit');
                    if (window.initBAPenambahanToggle && formEdit && checkEdit) {
                        window.initBAPenambahanToggle(formEdit, checkEdit);
                    }
                } catch (ex) {
                    console.warn('initBAPenambahanToggle edit error:', ex);
                }
                // setelah render, wire select controls
                try {
                    wireEditFormSelects(data, karyawan, atasan);
                } catch (ex) {
                    console.warn('wireEditFormSelects error:', ex);
                }
            }

            // ====== WIRING SELECT (lantai->user->atasan) ======
            function wireEditFormSelects(data, karyawan, atasan) {
                const ptSelect = document.getElementById('edit-pt');
                const lantaiSelect = document.getElementById('edit-lokasi');
                const userSelect = document.getElementById('edit-user');
                const atasanSelect = document.getElementById('edit-atasan');

                if (!ptSelect || !lantaiSelect || !userSelect || !atasanSelect) {
                    console.warn('Some edit selects not found');
                    return;
                }

                // unique lantai
                const uniqueLantai = [...new Set(karyawan.map(function(k) {
                    return k.lantai;
                }).filter(Boolean))].sort(function(a, b) {
                    var ma = /^LT\.(\d+)/i.exec(a);
                    var mb = /^LT\.(\d+)/i.exec(b);
                    if (ma && mb) return parseInt(ma[1], 10) - parseInt(mb[1], 10);
                    return String(a).localeCompare(String(b));
                });

                lantaiSelect.innerHTML = '<option value="">-- Pilih Lantai --</option>' + uniqueLantai.map(v => {
                    const m = /^LT\.(\d+)/i.exec(v);
                    const label = m ? ('Lantai ' + m[1]) : v;
                    const sel = (data.lokasi === v) ? ' selected' : '';
                    return `<option value="${escapeHtml(v)}"${sel}>${escapeHtml(label)}</option>`;
                }).join('');

                function loadUsersByLantai(lantai, selectedUser) {
                    userSelect.innerHTML = '<option value="">-- Pilih Pengguna --</option>';
                    karyawan.filter(k => k.lantai === lantai).forEach(k => {
                        const label = `${k.nama} - ${k.posisi} (${k.departemen})`;
                        const sel = (selectedUser ? (k.nama === selectedUser) : (k.nama === data.peminjam)) ? ' selected' : '';
                        userSelect.insertAdjacentHTML('beforeend', `<option value="${escapeHtml(k.nama)}"${sel}>${escapeHtml(label)}</option>`);
                    });
                }

                function isAutoDashJabatanEdit(jabatan) {
                    jabatan = String(jabatan || '').trim();
                    return (
                        jabatan === "Dept. Head" ||
                        jabatan === "AVP" ||
                        jabatan === "Direktur" ||
                        jabatan === "CEO" ||
                        jabatan === "VICE CEO"
                    );
                }

                function loadAtasanByDept(dept, selected) {
                    atasanSelect.innerHTML = '<option value="">-- Pilih Atasan Pengguna --</option>';

                    var filtered = atasan.filter(function(a) {
                        return String(a.departemen || '').trim() === String(dept || '').trim();
                    });

                    for (var i = 0; i < filtered.length; i++) {
                        var a = filtered[i];
                        var option = document.createElement('option');
                        option.value = a.nama;
                        option.textContent = a.nama + ' - ' + a.posisi + ' (' + a.departemen + ')';

                        if ((selected ? a.nama === selected : a.nama === data.atasan_peminjam)) {
                            option.selected = true;
                        }

                        atasanSelect.appendChild(option);
                    }

                    atasanSelect.disabled = filtered.length === 0;
                }

                // initial populate
                if (ptSelect.value === 'PT.MSAL (HO)') {
                    lantaiSelect.disabled = false;

                    if (data.lokasi) {
                        loadUsersByLantai(data.lokasi, data.peminjam);
                    }

                    var userData = karyawan.find(function(k) {
                        return k.nama === data.peminjam;
                    });

                    if (userData) {
                        if (isAutoDashJabatanEdit(userData.jabatan)) {
                            atasanSelect.innerHTML = '<option value="-">-</option>';
                            atasanSelect.disabled = false;
                        } else {
                            loadAtasanByDept(userData.departemen, data.atasan_peminjam);
                        }
                    } else {
                        atasanSelect.innerHTML = '<option value="">-- Pilih Atasan Pengguna --</option>';
                        atasanSelect.disabled = true;
                    }
                } else {
                    lantaiSelect.disabled = true;
                }


                ptSelect.addEventListener('change', function() {
                    if (this.value === 'PT.MSAL (HO)') {
                        lantaiSelect.disabled = false;
                    } else {
                        lantaiSelect.disabled = true;
                        lantaiSelect.value = '';
                        userSelect.innerHTML = '<option value="">-- Pilih Pengguna --</option>';
                        atasanSelect.innerHTML = '<option value="">-- Pilih Atasan Pengguna --</option>';
                        atasanSelect.disabled = true;
                    }
                });

                lantaiSelect.addEventListener('change', function() {
                    loadUsersByLantai(this.value, null);
                    atasanSelect.innerHTML = '<option value="">-- Pilih Atasan Pengguna --</option>';
                    atasanSelect.disabled = true;
                });

                userSelect.addEventListener('change', function() {
                    var selectedNama = this.value;
                    var userData = karyawan.find(function(k) {
                        return k.nama === selectedNama;
                    });

                    atasanSelect.innerHTML = '<option value="">-- Pilih Atasan Pengguna --</option>';

                    if (!userData) {
                        atasanSelect.disabled = true;
                        return;
                    }

                    if (isAutoDashJabatanEdit(userData.jabatan)) {
                        atasanSelect.innerHTML = '<option value="-">-</option>';
                        atasanSelect.disabled = false;
                        return;
                    }

                    console.log("Bukan Dept. Head, AVP, Direktur, atau Div. Head, load atasan by dept:", userData.departemen);
                    loadAtasanByDept(userData.departemen, '');
                });
            }

            // ====== Gambar helpers ======
            window.tambahGambarEdit = function() {
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

                input.onchange = function() {
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

                // === Tombol Jepret Kamera (sama seperti form input) ===
                const logoJepret = document.createElement('i');
                logoJepret.className = 'bi bi-camera-fill';

                const btnCamera = document.createElement('button');
                btnCamera.type = 'button';
                btnCamera.className = 'btn btn-secondary btn-lg';
                btnCamera.style.marginTop = '5px';
                btnCamera.style.width = 'max-content';
                btnCamera.prepend(logoJepret);

                let currentCamera = "environment"; // default belakang

                // === Event kamera toggle ===
                btnCamera.onclick = async function() {
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

                        // tampilkan kembali preview ketika kamera dimatikan
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

                    // fungsi start kamera dengan memilih deviceId (sama seperti form input)
                    async function startCamera() {
                        const devices = await navigator.mediaDevices.enumerateDevices();
                        const cams = devices.filter(d => d.kind === "videoinput");

                        let targetCam = null;
                        if (currentCamera === "environment") {
                            targetCam = cams.find(c =>
                                c.label.toLowerCase().includes("back") ||
                                c.label.toLowerCase().includes("rear")
                            );
                        } else {
                            targetCam = cams.find(c =>
                                c.label.toLowerCase().includes("front")
                            );
                        }

                        if (!targetCam) targetCam = cams[0];

                        try {
                            const stream = await navigator.mediaDevices.getUserMedia({
                                video: {
                                    deviceId: targetCam.deviceId
                                }
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

                    btnSwitch.onclick = async function() {
                        currentCamera = currentCamera === "environment" ? "user" : "environment";

                        if (video._stream) {
                            video._stream.getTracks().forEach(t => t.stop());
                        }

                        startCamera();
                    };

                    // tombol ambil foto
                    const btnCapture = document.createElement('button');
                    btnCapture.textContent = "Ambil Foto";
                    btnCapture.className = "btn btn-success btn-capture mt-0";
                    btnCapture.style.width = "max-content";

                    btnCapture.onclick = function() {
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
                        btnGroup.remove();

                        preview.src = canvas.toDataURL("image/png");
                        preview.style.display = "block";

                        canvas.toBlob(function(blob) {
                            const timestamp = Date.now();
                            const nomorBA = document.getElementById('nomor_ba_edit').value || "NOBA";

                            const tanggalBAraw = document.getElementById('tanggal_edit').value || "NOTGL";
                            let tanggalBA = "NOTGL";

                            if (tanggalBAraw.includes("-")) {
                                const [yyyy, mm, dd] = tanggalBAraw.split("-");
                                tanggalBA = `${dd}${mm}${yyyy}`;
                            }

                            const filename = `camera${nomorBA}BAK${tanggalBA}-${timestamp}.png`;
                            const file = new File([blob], filename, {
                                type: "image/png"
                            });
                            const dt = new DataTransfer();
                            dt.items.add(file);
                            input.files = dt.files;
                        }, "image/png");
                    };

                    // container tombol sejajar
                    const btnGroup = document.createElement('div');
                    btnGroup.className = "d-flex gap-1 mt-0 btn-group-kamera";
                    btnGroup.appendChild(btnSwitch);
                    btnGroup.appendChild(btnCapture);

                    wrapper.insertBefore(btnGroup, preview);

                    // mulai kamera pertama kali + sembunyikan preview
                    await startCamera();
                    preview.style.display = "none";
                };

                // ========== TOMBOL HAPUS ==========
                const btnHapus = document.createElement('button');
                btnHapus.type = 'button';
                btnHapus.innerHTML = '<i class="bi bi-trash3-fill"></i>';
                btnHapus.className = 'btn btn-danger btn-sm';
                btnHapus.style.marginTop = '5px';

                btnHapus.onclick = function() {
                    const videoAktif = wrapper.querySelector('video');
                    if (videoAktif && videoAktif._stream) {
                        videoAktif._stream.getTracks().forEach(track => track.stop());
                    }

                    container.removeChild(wrapper);

                    if (preview.src && preview.src.startsWith("blob:")) {
                        URL.revokeObjectURL(preview.src);
                    }
                };

                wrapper.appendChild(input);
                wrapper.appendChild(btnCamera);
                wrapper.appendChild(preview);
                wrapper.appendChild(btnHapus);

                container.appendChild(wrapper);
            }


            window.previewGantiGambar = function(input) {
                const preview = input.nextElementSibling;
                const file = input.files && input.files[0];
                if (file && preview && preview.tagName.toLowerCase() === 'img') {
                    if (preview.src && preview.src.startsWith('blob:')) URL.revokeObjectURL(preview.src);
                    preview.src = URL.createObjectURL(file);
                    preview.style.display = 'block';
                }
            };

            window.hapusGambarLama = function(button, id) {
                const wrapper = button.closest('.gambar-wrapper');
                if (wrapper) wrapper.style.display = 'none';
                const hiddenInput = document.querySelector(`.hapus-gambar-${id}`);
                if (hiddenInput) hiddenInput.value = 'hapus';
            };
        });
    </script>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const kategoriSelect = document.querySelector(".kategoriKerusakan");
            const dllWrapper = document.querySelector(".dllWrapper");
            const keteranganDll = document.querySelector(".keteranganDll");

            kategoriSelect.addEventListener("change", function() {
                if (this.value === "10") {
                    dllWrapper.style.display = "block";
                    keteranganDll.setAttribute("required", "required");
                } else {
                    dllWrapper.style.display = "none";
                    keteranganDll.removeAttribute("required");
                    keteranganDll.value = ""; // reset kalau ganti pilihan
                }
            });
        });
        document.addEventListener("DOMContentLoaded", function() {
            const kategoriSelect = document.querySelector(".kategoriKerusakanEdit");
            const dllWrapper = document.querySelector(".dllWrapperEdit");
            const keteranganDll = document.querySelector(".keteranganDllEdit");

            if (!kategoriSelect || !dllWrapper || !keteranganDll) return;

            kategoriSelect.addEventListener("change", function() {
                if (this.value === "10") {
                    dllWrapper.style.display = "block";
                    keteranganDll.setAttribute("required", "required");
                } else {
                    dllWrapper.style.display = "none";
                    keteranganDll.removeAttribute("required");
                    keteranganDll.value = ""; // reset kalau ganti pilihan
                }
            });
        });
    </script>

    <script> //Detail Popup
        // Sistem tombol popup detail
        document.addEventListener('DOMContentLoaded', function() {
            const btnDetailList = document.querySelectorAll('.btn-detail-ba');
            const popupBox = document.getElementById('popupBoxDetail');
            const popupBody = document.getElementById('popupDetailBody');
            const closeBtn = document.getElementById('tombolClosePopupDetail');
            const popupBG = document.getElementById('popupBG');
            const tabel = document.getElementById('custom-main');

            if (!popupBox || !popupBody || !closeBtn || !popupBG) return console.error('Popup elements missing');

            function escapeHtml(str = '') {
                return String(str)
                    .replaceAll('&', '&amp;')
                    .replaceAll('<', '&lt;')
                    .replaceAll('>', '&gt;')
                    .replaceAll('"', '&quot;')
                    .replaceAll("'", '&#039;');
            }

            function formatRomawi(tanggal) {
                const bulanRomawi = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];
                const d = new Date(tanggal);
                const bulan = d.getMonth(); // 0-11
                const tahun = d.getFullYear();
                return `${bulanRomawi[bulan]}/${tahun}`;
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
                // popupBox.classList.remove('aktifPopup');
                // popupBG.classList.remove('aktifPopup');
                popupBox.classList.remove('aktifPopup');
                popupBG.classList.remove('aktifPopup');

                popupBox.classList.remove('scale-in-center');
                popupBox.classList.add('scale-out-center');
                popupBG.classList.remove('fade-in');
                popupBG.classList.add('fade-out');
                tabel.style.overflowY = 'auto';
            }

            closeBtn.addEventListener('click', function(e) {
                e.preventDefault();
                closePopup();
            });

            popupBG.addEventListener('click', closePopup);

            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') closePopup();
            });

            btnDetailList.forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const id = this.dataset.id;
                    if (!id) return alert('ID tidak ditemukan');

                    fetch('get_detail.php?id=' + encodeURIComponent(id), {
                            cache: 'no-store'
                        })
                        .then(resp => {
                            if (!resp.ok) throw new Error('HTTP ' + resp.status);
                            return resp.json(); // JSON: { data, peran, gambarList }
                        })
                        .then(res => {
                            if (res.error) throw new Error(res.error);

                            const data = res.data;
                            const peran = res.peran;
                            const gambarList = res.gambarList || [];

                            // label fleksibel: Kerusakan / Penambahan
                            const isPenambahan =
                                String(data.kategori_kerusakan_id ?? '').trim() === '12' ||
                                String(data.kategori_nama ?? '').trim().toLowerCase() === 'upgrade';

                            const labelObjek = isPenambahan ? 'Penambahan' : 'Kerusakan';

                            // build HTML tabel
                            let html = `<h2>Detail Data ${labelObjek} ${escapeHtml(data.nomor_ba)} Periode ${formatRomawi(data.tanggal)}</h2>`;

                            // Table Approval dinamis
                            let approvalHeader = "";
                            let approvalJabatan = "";
                            let approvalStatus = "";

                            <?php if ($pt_session_query === 'PT.MSAL (HO)') { ?>

                            // Pembuat
                            if (res.peran.pembuat && res.peran.pembuat !== "-") {
                                approvalHeader += `<th>Pembuat</th>`;
                                <?php
                                if ($pt_session_query === 'PT.MSAL (HO)') {
                                ?>
                                    approvalJabatan += `<td>${escapeHtml(peran.jabatan_aprv1)}</td>`;
                                <?php
                                } elseif ($pt_session_query === 'PT.MSAL (SITE)' || $pt_session_query !== 'PT.MSAL (HO)') {
                                ?>
                                    approvalJabatan += `<td>${escapeHtml(peran.posisi1)}</td>`;
                                <?php
                                } else {
                                ?>
                                    approvalJabatan += `<td>N/A</td>`;
                                <?php
                                }
                                ?>
                                approvalStatus += `<td><span class="border fw-bold ${data.approval_1==1?'bg-success-subtle border-success-subtle text-success':'bg-warning-subtle border-warning-subtle text-warning'}" style="border-radius:6px;padding:6px 12px;">${data.approval_1==1?'Disetujui':'Menunggu'}</span></td>`;
                            }

                            // Pengguna
                            if (data.peminjam && data.peminjam !== "-") {
                                approvalHeader += `<th>Pengguna</th>`;
                                <?php
                                if ($pt_session_query === 'PT.MSAL (HO)') {
                                ?>
                                    approvalJabatan += `<td>${escapeHtml(peran.jabatan_aprv3)} </td>`;
                                <?php
                                } elseif ($pt_session_query === 'PT.MSAL (SITE)' || $pt_session_query !== 'PT.MSAL (HO)') {
                                ?>
                                    if (peran.posisi3 !== null && peran.posisi3 !== undefined && peran.posisi3 !== '') {
                                        approvalJabatan += `<td>${escapeHtml(peran.posisi3)}</td>`;
                                    } else {
                                        approvalJabatan += `<td>Pengguna</td>`;
                                    }
                                <?php
                                } else {
                                ?>
                                    approvalJabatan += `<td>N/A</td>`;
                                <?php
                                }
                                ?>
                                approvalStatus += `<td><span class="border fw-bold ${data.approval_3==1?'bg-success-subtle border-success-subtle text-success':'bg-warning-subtle border-warning-subtle text-warning'}" style="border-radius:6px;padding:6px 12px;">${data.approval_3==1?'Disetujui':'Menunggu'}</span></td>`;
                            }

                            // Atasan Pengguna
                            if (data.atasan_peminjam && data.atasan_peminjam !== "-") {
                                approvalHeader += `<th>Atasan Pengguna</th>`;
                                <?php
                                if ($pt_session_query === 'PT.MSAL (HO)') {
                                ?>
                                    approvalJabatan += `<td>${escapeHtml(peran.jabatan_aprv4)} </td>`;
                                <?php
                                } elseif ($pt_session_query === 'PT.MSAL (SITE)' || $pt_session_query !== 'PT.MSAL (HO)') {
                                ?>
                                    if (peran.posisi4 !== null && peran.posisi4 !== undefined && peran.posisi4 !== '') {
                                        approvalJabatan += `<td>${escapeHtml(peran.posisi4)}</td>`;
                                    } else {
                                        approvalJabatan += `<td>Atasan Pengguna</td>`;
                                    }
                                <?php
                                } else {
                                ?>
                                    approvalJabatan += `<td>N/A</td>`;
                                <?php
                                }
                                ?>
                                approvalStatus += `<td><span class="border fw-bold ${data.approval_4==1?'bg-success-subtle border-success-subtle text-success':'bg-warning-subtle border-warning-subtle text-warning'}" style="border-radius:6px;padding:6px 12px;">${data.approval_4==1?'Disetujui':'Menunggu'}</span></td>`;
                            }

                            // Diketahui
                            if (data.diketahui && data.diketahui !== "-") {
                                approvalHeader += `<th>Diketahui</th>`;
                                <?php
                                if ($pt_session_query === 'PT.MSAL (HO)') {
                                ?>
                                    approvalJabatan += `<td>${escapeHtml(peran.jabatan_aprv5)}</td>`;
                                <?php
                                } elseif ($pt_session_query === 'PT.MSAL (SITE)' || $pt_session_query !== 'PT.MSAL (HO)') {
                                ?>
                                    approvalJabatan += `<td>${escapeHtml(peran.posisi5)}</td>`;
                                <?php
                                } else {
                                ?>
                                    approvalJabatan += `<td>N/A</td>`;
                                <?php
                                }
                                ?>
                                approvalStatus += `<td><span class="border fw-bold ${data.approval_5==1?'bg-success-subtle border-success-subtle text-success':'bg-warning-subtle border-warning-subtle text-warning'}" style="border-radius:6px;padding:6px 12px;">${data.approval_5==1?'Disetujui':'Menunggu'}</span></td>`;
                            }

                            // Penyetujui
                            if (res.peran.penyetujui && res.peran.penyetujui !== "-") {
                                approvalHeader += `<th>Penyetujui</th>`;
                                <?php
                                if ($pt_session_query === 'PT.MSAL (HO)') {
                                ?>
                                    approvalJabatan += `<td>${escapeHtml(peran.jabatan_aprv2)}</td>`;
                                <?php
                                } elseif ($pt_session_query === 'PT.MSAL (SITE)' || $pt_session_query !== 'PT.MSAL (HO)') {
                                ?>
                                    approvalJabatan += `<td>${escapeHtml(peran.posisi2)}</td>`;
                                <?php
                                } else {
                                ?>
                                    approvalJabatan += `<td>N/A</td>`;
                                <?php
                                }
                                ?>
                                approvalStatus += `<td><span class="border fw-bold ${data.approval_2==1?'bg-success-subtle border-success-subtle text-success':'bg-warning-subtle border-warning-subtle text-warning'}" style="border-radius:6px;padding:6px 12px;">${data.approval_2==1?'Disetujui':'Menunggu'}</span></td>`;
                            }

                            <?php } else { ?>

                            // Pengguna
                            if (data.peminjam && data.peminjam !== "-") {
                                approvalHeader += `<th>Pengguna</th>`;
                                <?php
                                if ($pt_session_query === 'PT.MSAL (HO)') {
                                ?>
                                    approvalJabatan += `<td>${escapeHtml(peran.jabatan_aprv3)}</td>`;
                                <?php
                                } elseif ($pt_session_query === 'PT.MSAL (SITE)' || $pt_session_query !== 'PT.MSAL (HO)') {
                                ?>
                                    if (peran.posisi3 !== null && peran.posisi3 !== undefined && peran.posisi3 !== '') {
                                        approvalJabatan += `<td>${escapeHtml(peran.posisi3)}</td>`;
                                    } else {
                                        approvalJabatan += `<td>Pengguna</td>`;
                                    }
                                <?php
                                } else {
                                ?>
                                    approvalJabatan += `<td>N/A</td>`;
                                <?php
                                }
                                ?>
                                approvalStatus += `<td><span class="border fw-bold ${data.approval_3==1?'bg-success-subtle border-success-subtle text-success':'bg-warning-subtle border-warning-subtle text-warning'}" style="border-radius:6px;padding:6px 12px;">${data.approval_3==1?'Disetujui':'Menunggu'}</span></td>`;
                            }

                            // Atasan Pengguna
                            if (data.atasan_peminjam && data.atasan_peminjam !== "-") {
                                approvalHeader += `<th>Atasan Pengguna</th>`;
                                <?php
                                if ($pt_session_query === 'PT.MSAL (HO)') {
                                ?>
                                    approvalJabatan += `<td>${escapeHtml(peran.jabatan_aprv4)}</td>`;
                                <?php
                                } elseif ($pt_session_query === 'PT.MSAL (SITE)' || $pt_session_query !== 'PT.MSAL (HO)') {
                                ?>
                                    if (peran.posisi4 !== null && peran.posisi4 !== undefined && peran.posisi4 !== '') {
                                        approvalJabatan += `<td>${escapeHtml(peran.posisi4)}</td>`;
                                    } else {
                                        approvalJabatan += `<td>Atasan Pengguna</td>`;
                                    }
                                <?php
                                } else {
                                ?>
                                    approvalJabatan += `<td>N/A</td>`;
                                <?php
                                }
                                ?>
                                approvalStatus += `<td><span class="border fw-bold ${data.approval_4==1?'bg-success-subtle border-success-subtle text-success':'bg-warning-subtle border-warning-subtle text-warning'}" style="border-radius:6px;padding:6px 12px;">${data.approval_4==1?'Disetujui':'Menunggu'}</span></td>`;
                            }

                            // Diketahui
                            if (data.diketahui && data.diketahui !== "-") {
                                approvalHeader += `<th>Diketahui</th>`;
                                <?php
                                if ($pt_session_query === 'PT.MSAL (HO)') {
                                ?>
                                    approvalJabatan += `<td>${escapeHtml(peran.jabatan_aprv5)}</td>`;
                                <?php
                                } elseif ($pt_session_query === 'PT.MSAL (SITE)' || $pt_session_query !== 'PT.MSAL (HO)') {
                                ?>
                                    approvalJabatan += `<td>${escapeHtml(peran.posisi5)}</td>`;
                                <?php
                                } else {
                                ?>
                                    approvalJabatan += `<td>N/A</td>`;
                                <?php
                                }
                                ?>
                                approvalStatus += `<td><span class="border fw-bold ${data.approval_5==1?'bg-success-subtle border-success-subtle text-success':'bg-warning-subtle border-warning-subtle text-warning'}" style="border-radius:6px;padding:6px 12px;">${data.approval_5==1?'Disetujui':'Menunggu'}</span></td>`;
                            }

                            // Penyetujui
                            if (res.peran.penyetujui && res.peran.penyetujui !== "-") {
                                approvalHeader += `<th>Penyetujui</th>`;
                                <?php
                                if ($pt_session_query === 'PT.MSAL (HO)') {
                                ?>
                                    approvalJabatan += `<td>${escapeHtml(peran.jabatan_aprv2)}</td>`;
                                <?php
                                } elseif ($pt_session_query === 'PT.MSAL (SITE)' || $pt_session_query !== 'PT.MSAL (HO)') {
                                ?>
                                    approvalJabatan += `<td>${escapeHtml(peran.posisi2)}</td>`;
                                <?php
                                } else {
                                ?>
                                    approvalJabatan += `<td>N/A</td>`;
                                <?php
                                }
                                ?>
                                approvalStatus += `<td><span class="border fw-bold ${data.approval_2==1?'bg-success-subtle border-success-subtle text-success':'bg-warning-subtle border-warning-subtle text-warning'}" style="border-radius:6px;padding:6px 12px;">${data.approval_2==1?'Disetujui':'Menunggu'}</span></td>`;
                            }

                            // Pembuat (dipindah ke urutan terakhir & header diganti jadi "Diketahui")
                            if (res.peran.pembuat && res.peran.pembuat !== "-") {
                                approvalHeader += `<th>Diketahui</th>`;
                                <?php
                                if ($pt_session_query === 'PT.MSAL (HO)') {
                                ?>
                                    approvalJabatan += `<td>${escapeHtml(peran.jabatan_aprv1)}</td>`;
                                <?php
                                } elseif ($pt_session_query === 'PT.MSAL (SITE)' || $pt_session_query !== 'PT.MSAL (HO)') {
                                ?>
                                    approvalJabatan += `<td>${escapeHtml(peran.posisi1)}</td>`;
                                <?php
                                } else {
                                ?>
                                    approvalJabatan += `<td>N/A</td>`;
                                <?php
                                }
                                ?>
                                approvalStatus += `<td><span class="border fw-bold ${data.approval_1==1?'bg-success-subtle border-success-subtle text-success':'bg-warning-subtle border-warning-subtle text-warning'}" style="border-radius:6px;padding:6px 12px;">${data.approval_1==1?'Disetujui':'Menunggu'}</span></td>`;
                            }

                            <?php } ?>

                            // Render tabel approval hanya kalau ada minimal 1 data
                            if (approvalHeader) {
                                html += `
                    <div class="custom-detail-approval">
                    <table class="custom-detail-approval-child table w-25 table-approval">
                        <thead>
                            <tr>${approvalHeader}</tr>
                        </thead>
                        <tbody>
                            <tr>${approvalJabatan}</tr>
                            <tr>${approvalStatus}</tr>
                        </tbody>
                    </table>
                    </div>
                    `;
                            }


                            html += `<div class="custom-detail-container d-flex gap-2 h-100">
                <div class="custom-detail-table w-50">
                <table class="custom-detail-table-child table table-bordered table-striped" style="width:100%;">
                    <tbody>
                        <tr><th style="font-size:14px;width:20%;min-width:150px;">Nomor BA          </th><td style="font-size:14px;width:80%;">${escapeHtml(data.nomor_ba)}</td></tr>
                        <tr><th style="font-size:14px;width:20%;min-width:150px;">Tanggal           </th><td style="font-size:14px;width:80%;">${escapeHtml(data.tanggal)}</td></tr>
                        <tr><th style="font-size:14px;width:20%;min-width:150px;">Jenis Perangkat   </th><td style="font-size:14px;width:80%;">${escapeHtml(data.jenis_perangkat)}</td></tr>
                        <tr><th style="font-size:14px;width:20%;min-width:150px;">Merek             </th><td style="font-size:14px;width:80%;">${escapeHtml(data.merek)}</td></tr>
                        <tr><th style="font-size:14px;width:20%;min-width:150px;">Nomor PO          </th><td style="font-size:14px;width:80%;">${escapeHtml(data.no_po)}</td></tr>
                        <tr><th style="font-size:14px;width:20%;min-width:150px;">Serial Number     </th><td style="font-size:14px;width:80%;">${escapeHtml(data.sn)}</td></tr>
                        <tr><th style="font-size:14px;width:20%;min-width:150px;">Tahun Perolehan   </th><td style="font-size:14px;width:80%;">${escapeHtml(data.tahun_perolehan)}</td></tr>
                        <tr><th style="font-size:14px;width:20%;min-width:150px;">Lokasi            </th><td style="font-size:14px;width:80%;">${escapeHtml(data.pt)} ${escapeHtml(data.lokasi)}</td></tr>
                        <tr><th style="font-size:14px;width:20%;min-width:150px;">Pengguna          </th><td style="font-size:14px;width:80%;">${escapeHtml(data.peminjam)}</td></tr>
                        <tr><th style="font-size:14px;width:20%;min-width:150px;">Jenis ${labelObjek}</th><td style="font-size:14px;width:80%;">${escapeHtml(data.deskripsi)}</td></tr>
                        <tr><th style="font-size:14px;width:20%;min-width:150px;">Kategori          </th><td style="font-size:14px;width:80%;">${escapeHtml(data.kategori_nama || '-')}</td></tr>
                        ${data.kategori_nama === "DLL" ? `
                        <tr>
                            <th style="font-size:14px;width:20%;min-width:150px;">Keterangan DLL    </th><td style="font-size:14px;width:80%;">${escapeHtml(data.keterangan_dll || "-")}</td>
                        </tr>` : ""}
                        <tr><th style="font-size:14px;width:20%;min-width:150px;">Penyebab ${labelObjek}</th><td style="font-size:14px;width:80%;">${escapeHtml(data.penyebab_kerusakan)}</td></tr>
                        <tr><th style="font-size:14px;width:20%;min-width:150px;">Rekomendasi MIS   </th><td style="font-size:14px;width:80%;">${escapeHtml(data.rekomendasi_mis)}</td></tr>
                        <!-- <tr><th style="width:20%;min-width:150px;">Atasan Pengguna</th><td style="width:80%;">${escapeHtml(data.atasan_peminjam)}</td></tr> -->
                    </tbody>
                </table>
                </div>
                        <div class="custom-detail-gambar w-50 d-flex border rounded-1 mb-1 overflow-auto p-2" style="height:490px;">`;
                            if (gambarList.length > 0) {
                                html += `<div style="display:flex;flex-wrap:wrap;gap:5px;height:max-content;width:100%;">`;
                                gambarList.forEach(g => {
                                    html += `<div class="custom-gambar-detail"><img src="${escapeHtml(g)}" style="max-width:100%;height:auto;display:block;"></div>`;
                                });
                                html += `</div>`;
                            } else {
                                html += `Tidak ada gambar.`;
                            }
                            html += `</div>
                        
                </div>
                <div class="custom-detail-histori w-50" style="height:max-content; min-width:200px">
                <div class=" w-auto">
                <h6>Histori & Pending Perubahan</h6>
                </div>
                    <table id="popupDetailTable" class="table table-bordered table-striped" 
                    style="font-size:16px; width: auto; table-layout: auto;"
                    >
                        <thead>
                            <tr>
                                <th class="text-start">Tanggal Edit</th>
                                <th class="text-start">Status</th>
                                <th class="text-start">Alasan Edit</th>
                                <th class="text-start">Alasan Tolak</th>
                                <th class="text-start">Tanggal Surat</th>
                                <th class="text-start">Nomor Surat</th>
                                <th class="text-start">Jenis Perangkat</th>
                                <th class="text-start">Merek</th>
                                <th class="text-start">Nomor PO</th>
                                <th class="text-start">SN</th>
                                <th class="text-start">Tahun Perolehan</th>
                                <th class="text-start">Kategori Rusak</th>
                                <th class="text-start">Jenis Kerusakan</th>
                                <th class="text-start">Penyebab Kerusakan</th>
                                <th class="text-start">Rekomendasi MIS</th>
                            </tr>
                        </thead>
                        <tbody>
                        `;

                            // Loop data_history dari response JSON
                            (res.data_history || []).forEach(h => {
                                // Tampilkan semua data
                                // Jika ada lebih dari 1 pending_status = 1, tampilkan hanya yang take_for_pending = true untuk status = 1
                                const showRow = h.pending_status != 1 || (h.pending_status == 1 && h.take_for_pending === true);
                                if (showRow) {
                                    let rowColor = '';
                                    let textColor = '';
                                    if (h.pending_status == 1) {
                                        rowColor = 'background-color: rgba(255, 234, 0, 0.5) !important;'; // kuning
                                        textColor = 'color: #856404 !important;'; // teks gelap
                                    } else if (h.pending_status == 2) {
                                        rowColor = 'background-color: #f8d7da !important;'; // merah
                                        textColor = 'color: #721c24 !important;'; // teks gelap
                                    }
                                    html += `<tr>
                                    <td class="text-start" style="${rowColor} ${textColor}">${escapeHtml(h.created_at)}</td>
                                    <td class="text-start" style="${rowColor} ${textColor}">${escapeHtml(h.pending_status_nama)}</td>
                                    <td class="text-start" style="${rowColor} ${textColor}">${escapeHtml(h.alasan_edit)}</td>
                                    <td class="text-start" style="${rowColor} ${textColor}">${escapeHtml(h.alasan_tolak)}</td>
                                    <td class="text-start" style="${rowColor} ${textColor}">${escapeHtml(h.tanggal)}</td>
                                    <td class="text-start" style="${rowColor} ${textColor}">${escapeHtml(h.nomor_ba)}</td>
                                    <td class="text-start" style="${rowColor} ${textColor}">${escapeHtml(h.jenis_perangkat)}</td>
                                    <td class="text-start" style="${rowColor} ${textColor}">${escapeHtml(h.merek)}</td>
                                    <td class="text-start" style="${rowColor} ${textColor}">${escapeHtml(h.no_po)}</td>
                                    <td class="text-start" style="${rowColor} ${textColor}">${escapeHtml(h.sn)}</td>
                                    <td class="text-start" style="${rowColor} ${textColor}">${escapeHtml(h.tahun_perolehan)}</td>
                                    <td class="text-start" style="${rowColor} ${textColor}">${escapeHtml(h.kategori_kerusakan_id)}</td>
                                    <td class="text-start" style="${rowColor} ${textColor}">${escapeHtml(h.deskripsi)}</td>
                                    <td class="text-start" style="${rowColor} ${textColor}">${escapeHtml(h.penyebab_kerusakan)}</td>
                                    <td class="text-start" style="${rowColor} ${textColor}">${escapeHtml(h.rekomendasi_mis)}</td>
                                </tr>`;
                                }
                            });

                            html += `
                        </tbody>
                    </table>
                </div>`;

                            popupBody.innerHTML = html;
                            openPopup();
                            if ($.fn.DataTable) {
                                $('#popupDetailTable').DataTable({
                                    paging: false,
                                    searching: false,
                                    info: false,
                                    ordering: false,
                                    scrollY: "410px",
                                    scrollCollapse: true,
                                    autoWidth: true,
                                    language: {
                                        url: "../assets/json/id.json"
                                    }
                                });
                            }
                        })
                        .catch(err => {
                            popupBody.innerHTML = `<div class="alert alert-danger">Gagal memuat data: ${escapeHtml(err.message)}</div>`;
                            openPopup();
                        });
                });
            });
        });
    </script>

    <script>
        //DataTables
        $(document).ready(function() {
            $('#myTable').DataTable({
                responsive: true,
                autoWidth: true,
                // scrollY: "410px",     
                // scrollCollapse: true,
                language: {
                    url: "../assets/json/id.json"
                },
                columnDefs: [{
                        targets: -1,
                        orderable: false
                    }, // Kolom Actions tidak bisa di-sort

                ],
                initComplete: function() {
                    // Sembunyikan skeleton
                    $('#tableSkeleton').fadeOut(200, function() {
                        $('#tabelUtama').fadeIn(200);
                    });
                }
            });
        });
    </script>

    <script>
        //DataTables
        $(document).ready(function() {
            $('#myTable2').DataTable({
                responsive: true,
                autoWidth: true,
                language: {
                    url: "../assets/json/id.json"
                },
                scrollY: "310px",
                scrollCollapse: true,
                paging: true,
                columnDefs: []
            });
        });
    </script>

    <script>
    (function(){
        // Filter Data Barang berdasarkan PT yang dipilih di form input (#perusahaan)
        $.fn.dataTable.ext.search.push(function(settings, data, dataIndex){
            if (!settings.nTable || settings.nTable.id !== 'myTable2') return true;

            var ptSelected = $('#perusahaan').val();
            if (!ptSelected) return true; // kalau belum pilih PT, tampilkan semua (tapi default kita set selected)

            var tr = settings.aoData[dataIndex].nTr;
            var rowPT = $(tr).data('pt') || '';

            return rowPT === ptSelected;
        });

        // redraw kalau PT berubah
        $(document).on('change', '#perusahaan', function(){
            if ($.fn.DataTable.isDataTable('#myTable2')) {
                $('#myTable2').DataTable().draw();
            }
        });

        // redraw setiap popup barang dibuka
        $(document).on('click', '.tombolDataBarangPopup', function(){
            if ($.fn.DataTable.isDataTable('#myTable2')) {
                $('#myTable2').DataTable().draw();
            }
        });
    })();
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

    <script>
        //Konfigurasi OverlayScrollbars

        //-----------------------------------------------------------------------------------
        const SELECTOR_SIDEBAR_WRAPPER = '.sidebar-wrapper';
        const Default = {
            scrollbarTheme: 'os-theme-light',
            scrollbarAutoHide: 'leave',
            scrollbarClickScroll: true,
        };
        document.addEventListener('DOMContentLoaded', function() {
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
        //Sidebar

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

    <script>
        //Tanggal

        //-----------------------------------------------------------------------------------
        function updateDate() {
            const now = new Date();
            const options = {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            };
            const formattedDate = now.toLocaleDateString('id-ID', options);
            document.getElementById('date').textContent = formattedDate;
        }
        setInterval(updateDate, 1000); // Update setiap detik
        updateDate(); // Panggil langsung saat halaman load
        //-----------------------------------------------------------------------------------
    </script>

    <script>
        // Jam Digital
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