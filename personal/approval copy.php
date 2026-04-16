<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['nama'])) {
    header("Location: ../login_registrasi.php");
    exit;
}

$namaUser = $_SESSION['nama'];

$filterPT       = $_GET['pt'] ?? '';
$filterJenisBA  = $_GET['jenis_ba'] ?? '';
$filterTahun    = $_GET['tahun'] ?? '';
$filterBulan    = $_GET['bulan'] ?? '';

$filtersKerusakan = [];
$filtersPengembalian = [];

if (!empty($filterPT)) {
    $filtersKerusakan[]    = "pt = '" . $koneksi->real_escape_string($filterPT) . "'";
    $filtersPengembalian[] = "lokasi_penerima = '" . $koneksi->real_escape_string($filterPT) . "'";
}

if (!empty($filterTahun)) {
    $filtersKerusakan[]    = "YEAR(tanggal) = " . intval($filterTahun);
    $filtersPengembalian[] = "YEAR(tanggal) = " . intval($filterTahun);
}

if (!empty($filterBulan)) {
    $filtersKerusakan[]    = "MONTH(tanggal) = " . intval($filterBulan);
    $filtersPengembalian[] = "MONTH(tanggal) = " . intval($filterBulan);
}

$whereKerusakan    = $filtersKerusakan ? " AND " . implode(" AND ", $filtersKerusakan) : "";
$wherePengembalian = $filtersPengembalian ? " AND " . implode(" AND ", $filtersPengembalian) : "";

$sql = "";

// Kerusakan
if ($filterJenisBA === '' || $filterJenisBA === 'kerusakan') {
    $sql .= "(SELECT id, tanggal, nomor_ba,
                nama_aprv1 AS role_name1, approval_1 AS aprv1,
                nama_aprv2 AS role_name2, approval_2 AS aprv2,
                NULL AS role_name3, NULL AS aprv3,
                'kerusakan' AS jenis_ba
            FROM berita_acara_kerusakan
            WHERE ((nama_aprv1 = ? AND approval_1 = 0) 
                OR (nama_aprv2 = ? AND approval_2 = 0))
            $whereKerusakan)";
}

if ($filterJenisBA === '') {
    $sql .= " UNION ALL ";
}

// Pengembalian + Role 3
// Rollback ready
if ($filterJenisBA === '' || $filterJenisBA === 'pengembalian') {
    $sql .= "(SELECT id, tanggal, nomor_ba,
                nama_pengembali AS role_name1, approval_1 AS aprv1,
                nama_penerima AS role_name2, approval_2 AS aprv2,
                SUBSTRING_INDEX(diketahui, ' - ', 1) AS role_name3, approval_3 AS aprv3,
                'pengembalian' AS jenis_ba
            FROM berita_acara_pengembalian
            WHERE ((nama_pengembali = ? AND approval_1 = 0) 
                OR (nama_penerima = ? AND approval_2 = 0)
                OR (SUBSTRING_INDEX(diketahui, ' - ', 1) = ? AND approval_3 = 0))
            $wherePengembalian)";
}

//New
if ($filterJenisBA === '' || $filterJenisBA === 'pengembalian') {
    $sql .= "(SELECT id, tanggal, nomor_ba,
                nama_pengembali AS role_name1, approval_1 AS aprv1,
                nama_penerima AS role_name2, approval_2 AS aprv2,
                SUBSTRING_INDEX(diketahui, ' - ', 1) AS role_name3, approval_3 AS aprv3,
                'pengembalian' AS jenis_ba
            FROM berita_acara_pengembalian
            WHERE (approval_1 = 0 OR approval_2 = 0 OR approval_3 = 0)
            $wherePengembalian)";
}

$sql .= " ORDER BY tanggal DESC";

$stmt = $koneksi->prepare($sql);
if (!$stmt) {
    die("Prepare failed: " . $koneksi->error);
}

// Binding parameter
// if ($filterJenisBA === '') {
//     // Kerusakan (2 param) + Pengembalian (3 param) = total 5 param
//     $stmt->bind_param("sssss", $namaUser, $namaUser, $namaUser, $namaUser, $namaUser);
// } elseif ($filterJenisBA === 'kerusakan') {
//     $stmt->bind_param("ss", $namaUser, $namaUser);
// } elseif ($filterJenisBA === 'pengembalian') {

    // Pengembalian (3 param) Rollback ready
    // $stmt->bind_param("sss", $namaUser, $namaUser, $namaUser);

// }

$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Approve BA</title>

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

    th:nth-child(1), td:nth-child(1) { width: 6%; text-align: center; } /* No */
    th:nth-child(2), td:nth-child(2) { width: 10%; }  /* Tanggal */
    th:nth-child(3), td:nth-child(3) { width: 10%; }  /* Nomor BA */
    th:nth-child(4), td:nth-child(4) { width: 28%; }  /* Jenis BA */
    th:nth-child(5), td:nth-child(5) { width: 14%; }  /* Peran */
    th:nth-child(6), td:nth-child(6) { width: 12%; }  /* Status Approval */
    th:nth-child(7), td:nth-child(7) { width: 20%; }  /* Action */

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
            <?php if ($_SESSION['hak_akses'] === 'Admin'): ?>
            <li class="nav-item">
                <a href="status.php" class="nav-link">
                <i class="nav-icon bi bi-clipboard2-fill"></i>
                <p>
                    Status Approval BA
                </p>
                </a>
            </li>
            <?php endif; ?>
            <li class="nav-item">
                <a href="#" class="nav-link">
                <i class="nav-icon bi bi-clipboard2-check text-white"></i>
                <p class="text-white">
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

    <main class="app-main"><!-- Main Content -->
        <section class="table-wrapper bg-white position-relative overflow-visible">
            <?php if (isset($_SESSION['message'])): ?>
                <div class="w-100 d-flex justify-content-center">
                    <div class="alert alert-info text-center fw-bold mb-0" id="infoin-approval" style="transition: opacity 0.5s ease;width:450px;">
                        <?= htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); ?>
                    </div>
                </div>
            <?php endif; ?>

            <h2>Daftar BA 
                <!-- yang belum Anda Approve -->
            Approval
            </h2>

            <form method="get" class="mb-3 d-flex gap-2 flex-wrap align-items-end">
                <!-- Filter PT -->
                <div>
                    <label class="form-label">PT</label>
                    <select name="pt" class="form-select" onchange="this.form.submit()">
                        <option value="">Semua PT</option>
                        <option value="PT.MSAL (HO)" <?= ($filterPT === 'PT.MSAL (HO)') ? 'selected' : '' ?>>PT.MSAL (HO)</option>
                        <!-- Tambah PT lain kalau ada -->
                    </select>
                </div>

                <!-- Filter Jenis BA -->
                <div>
                    <label class="form-label">Jenis BA</label>
                    <select name="jenis_ba" class="form-select" onchange="this.form.submit()">
                        <!-- <option value="">Semua</option> -->
                        <!-- <option value="kerusakan" <?= ($filterJenisBA === 'kerusakan') ? 'selected' : '' ?>>BA Kerusakan</option> -->
                        <option value="pengembalian" <?= ($filterJenisBA === 'pengembalian') ? 'selected' : '' ?>>BA Pengembalian</option>
                    </select>
                </div>

                <!-- Filter Tahun -->
                <div>
                    <label class="form-label">Tahun</label>
                    <select name="tahun" class="form-select" onchange="this.form.submit()">
                        <option value="">Semua</option>
                        <?php
                        $tahunList = [];
                        $tahunQuery = "
                            SELECT DISTINCT YEAR(tanggal) as tahun FROM berita_acara_kerusakan
                            UNION
                            SELECT DISTINCT YEAR(tanggal) as tahun FROM berita_acara_pengembalian
                            ORDER BY tahun DESC
                        ";
                        $tahunRes = $koneksi->query($tahunQuery);
                        while ($t = $tahunRes->fetch_assoc()) {
                            $tahunList[] = $t['tahun'];
                        }
                        foreach ($tahunList as $th) {
                            $sel = ($filterTahun == $th) ? 'selected' : '';
                            echo "<option value='$th' $sel>$th</option>";
                        }
                        ?>
                    </select>
                </div>

                <!-- Filter Bulan -->
                <div>
                    <label class="form-label">Bulan</label>
                    <select name="bulan" class="form-select" onchange="this.form.submit()">
                        <option value="">Semua</option>
                        <?php
                        $bulanIndo = [
                            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
                        ];
                        foreach ($bulanIndo as $num => $nama) {
                            $sel = ($filterBulan == $num) ? 'selected' : '';
                            echo "<option value='$num' $sel>$nama</option>";
                        }
                        ?>
                    </select>
                </div>

                
            </form>

            <div class="">
                <table id="myTable" class="table table-bordered table-striped text-center w-100 position-relative">
                    <thead class="bg-secondary">
                        <tr>
                            <th>No</th>
                            <th>Tanggal</th>
                            <th>Nomor BA</th>
                            <th>Jenis BA</th>
                            <th>Peran Anda</th>
                            <th>Status Approval</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $no = 1;
                        while ($row = $result->fetch_assoc()):
                            // ambil nilai dengan fallback supaya tidak ada warning
                            $jenis_ba = $row['jenis_ba'] ?? 'kerusakan';
                            $role_name1 = $row['role_name1'] ?? '';
                            $role_name2 = $row['role_name2'] ?? '';
                            $role_name3 = $row['role_name3'] ?? ''; 
                            $aprv1 = isset($row['aprv1']) ? (int)$row['aprv1'] : 0;
                            $aprv2 = isset($row['aprv2']) ? (int)$row['aprv2'] : 0;
                            $aprv3 = isset($row['aprv3']) ? (int)$row['aprv3'] : 0;

                            // Tentukan peran dan field approval yang relevan (hanya jika memang pending)

                            //Rollback ready
                            // $peran = '';
                            // $approval_status = 0;
                            // $approval_field = 1; // 1 atau 2
                            // if 


                            // ($jenis_ba === 'kerusakan') {
                            //     if ($role_name1 === $namaUser && $aprv1 === 0) {
                            //         $peran = 'Pembuat';
                            //         $approval_status = $aprv1;
                            //         $approval_field = 1;
                            //     } elseif ($role_name2 === $namaUser && $aprv2 === 0) {
                            //         $peran = 'Diketahui';
                            //         $approval_status = $aprv2;
                            //         $approval_field = 2;
                            //     } elseif ($role_name3 === $namaUser && $aprv3 === 0) { 
                            //         $peran = 'Approval 3'; // sesuaikan label sesuai kebutuhan
                            //         $approval_status = $aprv3;
                            //         $approval_field = 3;
                            //     } else {
                            //         // Tidak eligible (safety), skip baris
                            //         continue;
                            //     }
                            // } elseif 

                            //Rollback ready
                            // ($jenis_ba === 'pengembalian') { 
                            //     // pengembalian
                            //     if ($role_name1 === $namaUser && $aprv1 === 0) {
                            //         $peran = 'Pengembali';
                            //         $approval_status = $aprv1;
                            //         $approval_field = 1;
                            //     } elseif ($role_name2 === $namaUser && $aprv2 === 0) {
                            //         $peran = 'Penerima';
                            //         $approval_status = $aprv2;
                            //         $approval_field = 2;
                            //     } elseif ($role_name3 === $namaUser && $aprv3 === 0) {
                            //     $peran = 'Yang Mengetahui'; // sesuaikan label sesuai kebutuhan
                            //     $approval_status = $aprv3;
                            //     $approval_field = 3;
                            //     } else {
                            //         continue;
                            //     }
                            // }

                            $peran = '';
                            $approval_field = 0;
                            $approval_status = 0;

                            // tentukan peran sesuai siapa yg pending
                            if ($aprv1 === 0) {
                                $peran = 'Pengembali';
                                $approval_field = 1;
                                $approval_status = $aprv1;
                            } elseif ($aprv2 === 0) {
                                $peran = 'Penerima';
                                $approval_field = 2;
                                $approval_status = $aprv2;
                            } elseif ($aprv3 === 0) {
                                $peran = 'Yang Mengetahui';
                                $approval_field = 3;
                                $approval_status = $aprv3;
                            }

                            // Status label & class (icon)
                            $status_icon = $approval_status == 1 ? '<i class="bi bi-check-square"></i>' : '<i class="bi bi-square"></i>';
                            $status_label = $approval_status == 1 ? 'Disetujui' : 'Menunggu';
                            $status_class = $approval_status == 1 ? 'bg-success-subtle border-success-subtle text-success' : 'bg-warning-subtle border-warning-subtle text-warning';

                            // Tentukan URL detail & surat berdasarkan jenis
                            // if ($jenis_ba === 'kerusakan') {
                            //     $detail_url = "detail_barang_kerusakan.php?id=" . intval($row['id']);
                            //     $surat_url = "surat_output.php?id=" . intval($row['id']);
                            // } else {
                                $detail_url = "detail_barang_pengembalian.php?id=" . intval($row['id']);
                                $surat_url = "surat_output_pengembalian.php?id=" . intval($row['id']);
                            // }

                            // URL approve (kirim jenis & field)
                            $approve_url = "toggle_approval.php?id=" . intval($row['id']) . "&jenis=" . urlencode($jenis_ba) . "&field=" . intval($approval_field);
                        
                            $can_approve = false;
                            if (($approval_field == 1 && $row['role_name1'] === $namaUser) ||
                                ($approval_field == 2 && $row['role_name2'] === $namaUser) ||
                                ($approval_field == 3 && $row['role_name3'] === $namaUser)) {
                                $can_approve = true;
                            }
                        ?>
                        <tr>
                            <td><?= $no ?></td>
                            <td><?= htmlspecialchars($row['tanggal']) ?></td>
                            <td><?= htmlspecialchars($row['nomor_ba']) ?></td>
                            <td><?= ($jenis_ba === 'kerusakan') ? 'BA Kerusakan' : 'BA Pengembalian' ?></td>
                            <td><?= htmlspecialchars($peran) ?></td>
                            <td style="padding-top:13px;">
                                <span class="border fw-bold <?= $status_class ?>" style="border-radius: 6px; padding: 6px 12px;">
                                    <?= htmlspecialchars($status_label) ?>
                                </span>
                            </td>
                            <td class="d-flex flex-column w-100 align-items-center gap-1">
                                <div class="d-flex gap-2">

                                
                                    <!-- Rollback ready -->
                                    <?php if ($approval_status != 1): ?>
                                        <!-- <a class='btn btn-success btn-sm' href='<?= $approve_url ?>' onclick="return confirm('Apakah anda yakin ingin mengapprove?')">Approve</a> -->
                                    <?php else: ?>
                                        <!-- jika sudah disetujui, tidak tampil tombol approve -->
                                    <?php endif; ?>


                                    <?php if ($can_approve && $approval_status == 0): ?>
                                        <a class='btn btn-success btn-sm' href='<?= $approve_url ?>'
                                        onclick="return confirm('Apakah anda yakin ingin mengapprove?')">Approve</a>
                                    <?php endif; ?>

                                    <a class='btn btn-secondary btn-sm' href='<?= $detail_url ?>'><i class="bi bi-eye-fill"></i></a>
                                    <a class='btn btn-primary btn-sm' href='<?= $surat_url ?>' target='_blank'><i class="bi bi-file-earmark-text-fill"></i></a>
                                </div>
                            </td>
                        </tr>
                        <?php $no++; endwhile; ?>
                    </tbody>
                </table>
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

<script>
        // Menghilangkan alert setelah 5 detik
        setTimeout(() => {
            const alert = document.getElementById('infoin-approval');
            if (alert) {
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500); // Hapus elemen setelah efek transisi
            }
        }, 2000);
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
