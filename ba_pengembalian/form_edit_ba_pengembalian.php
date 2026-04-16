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
  <title>Form BA Pengembalian</title>

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

    h4{
      margin: 0;
      margin-top: 10px;
    }

    .app-main{
      display: flex;
      align-items: center;
      padding-top: 40px;
    }
    .bi-check-circle{
      color: green;
      font-size: 50px;
    }
    form {
      background: #ffffff;
      width: 95%;
      padding: 25px 30px;
      border-radius: 10px;
    }

    .form-section {
      margin-bottom: 20px;
    }

    label {
      display: flex;
      align-items: center;
      margin: 15px 0 8px 0;
      padding: 3px 0px;
      
      color:rgba(0, 0, 0, 1);
      width:max-content;
      border-radius: 15px;
      font-size: small;
    }

    input, textarea,.form-control {
      width: 100%;
      padding: 8px 10px;
      border: 1px solid   #ccc;
      border-radius: 5px;
      box-sizing: border-box;
      
      font-size: 14px;
    }

    textarea {
      resize: vertical;
      min-height: 60px;
    }

    select{
      margin-top: 15px;
      margin-bottom: 8px;
    }

    .input-group-text{
      
      color: rgba(0, 0, 0, 1);
      
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

    .grid-2 {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 20px;
    }

    @media (max-width: 600px) {
      .grid-2 {
        grid-template-columns: 1fr;
      }
    }

    .bi-list, .bi-arrows-fullscreen, .bi-fullscreen-exit {
            color: #fff !important;
    }
  </style>

    <style>
    /* Responsive */
    @media (max-width: 1024px) {
        #res-fullscreen{
            display: none;
        }
        /* Containers */
        .custom-column-container{
            width: 100% !important;
        }
        .custom-pengembali, .custom-penerima{
          width: 50% !important;
        }
        .custom-pengembali-penerima-container{
          display: flex;
          
        }
        .custom-barang-gambar{
          display: flex;
          width: 100% !important;
          gap: 5px;
          padding: 0 12px !important;
          
        }
        .custom-barang{
          width: 65% !important;
          margin: 0 !important;
        }
        .custom-gambar{
          width: 33% !important;
          margin: 0 !important;
          margin-right: 4px;
        }
        .custom-gambar-btn{
          width: 70% !important;
        }
        /* Font */
        .custom-font{
          font-size: small;
        }
      }
      

  </style>

  <style>
    
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
    
    <!--begin::Header-->
    <nav class="app-header navbar navbar-expand bg-body sticky-top" style="margin-bottom: 0;">
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
    <!--end::Header-->

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
                <a href="ba_pengembalian.php" class="nav-link">
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
            <!-- <li class="nav-item">
                <a href="../master/data_notebook/tabel.php" class="nav-link">
                <i class="nav-icon bi bi-laptop"></i>
                <p>
                    Data Notebook
                </p>
                </a>
            </li> -->
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

    // Ambil data utama
    $stmt = $koneksi->prepare("SELECT * FROM berita_acara_pengembalian WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    $stmt->close();

    if (!$data) {
        echo "Data tidak ditemukan.";
        exit;
    }

    // Ambil data barang
    $barang_result = $koneksi->query("SELECT * FROM barang_pengembalian WHERE ba_pengembalian_id = $id");
    $barang_list = $barang_result->fetch_all(MYSQLI_ASSOC);

    // Ambil gambar lama
    $gambarQuery = $koneksi->prepare("SELECT * FROM gambar_ba_pengembalian WHERE ba_pengembalian_id = ?");
    $gambarQuery->bind_param("i", $data['id']);
    $gambarQuery->execute();
    $gambarResult = $gambarQuery->get_result();

    // Data atasan
    $query_atasan = $koneksi->query("SELECT nama, posisi, departemen FROM data_karyawan WHERE jabatan = 'Dept. Head' ORDER BY nama ASC");
    $data_atasan = [];
    while ($row = $query_atasan->fetch_assoc()) {
        $data_atasan[] = $row;
    }

    // Data karyawan
    $query_karyawan = $koneksi->query("SELECT nama, posisi, departemen, lantai FROM data_karyawan ORDER BY nama ASC");
    $data_karyawan = [];
    while ($row = $query_karyawan->fetch_assoc()) {
        $data_karyawan[] = $row;
    }
    
    ?>
    <?php
      $lantai_pengembali = '';
      $lantai_penerima = '';
      foreach ($data_karyawan as $k) {
          if ($k['nama'] === $data['nama_pengembali']) {
              $lantai_pengembali = $k['lantai'];
          }
          if ($k['nama'] === $data['nama_penerima']) {
              $lantai_penerima = $k['lantai'];
          }
      }
      ?>

    <!--Awal::Main Content-->
    <main class="app-main">
      <h2 class="text-black">Form Berita Acara Pengembalian Inventaris</h2>

      <!--Status Sukses Pop Up-->
      <?php if (isset($_GET['status']) && $_GET['status'] == 'sukses'): ?>
          <div class="alert alert-success d-flex flex-column align-items-center" role="alert" style="margin-top: 20px;">
              <i class="bi bi-check-circle"></i> <p>Data berhasil disimpan ke database.</p>
          </div>
      <?php endif; ?>


<form method="post" action="proses_edit.php" enctype="multipart/form-data">
  <input type="hidden" name="id" value="<?= $id ?>">
  <div class="form-section">
    <div class="row">
      <!-- <div class="ms-auto">
        <a href="javascript:history.back()" class="btn btn-outline-warning fw-bold position-absolute"><i class="bi bi-arrow-90deg-left"></i></a>
      </div> -->
      <div class="custom-column-container col-6">
        <div class="d-flex justify-content-center"><h4>Data Berita Acara</h4></div>
          <div class="row mt-3 mb-3">
            <div class="col-4">
            <div class="input-group" style="width: 200px;">
              <span class="input-group-text">Tanggal</span>
              <input type="date" class="form-control" name="tanggal" id="tanggal" max="<?= date('Y-m-d') ?>" value="<?= $data['tanggal'] ?>" required>
            </div>
            </div>
            <div class="col-6">
            <div class="input-group" style="width: 170px;">
              <span class="input-group-text">Nomor BA</span>
              <input type="number" name="nomor_ba" id="nomor-ba" class="form-control" value="<?= $data['nomor_ba'] ?>" style="width: 10px;cursor:default;" readonly>
            </div>
            </div>
            
          </div>

          <div class="custom-pengembali-penerima-container d-flex justify-content-between">

            <div class="custom-pengembali pengembali border border-1 p-1 m-1 rounded-3">
              <div class="row">
                <h4>Pengembali</h4>
                <div class="input-group">
                  <label class="custom-font input-group-text" style="width: 75px;" for="lokasi-pengembali">Lokasi</label>
                  <select name="lokasi_pengembali" id="lokasi-pengembali" class="custom-font form-select" onchange="tampilkanLantai('pengembali')" required>
                    <option value="PT.MSAL (HO)"<?= $data['lokasi_pengembali'] == 'PT.MSAL (HO)' ? 'selected' : '' ?>>PT.MSAL (HO)</option>
                  </select>
                    <label id="lantai-pengembali-container" class="custom-font input-group-text" for="lantai-pengembali" style="display:none">Lantai</label>
                    <select name="lantai_pengembali" id="lantai-pengembali" class="custom-font form-select" style="display:none;" onchange="filterNamaKaryawan('pengembali')">
                    </select>
                </div>
            </div>

            <div class="row">
              <div class="input-group">
                  <label class="custom-font input-group-text" style="width: 75px;" for="nama-pengembali">Nama</label>
                  <select name="nama_pengembali" id="nama-pengembali" class="custom-font form-select" onchange="loadAtasan('pengembali')" required>
                  <option value="" disabled>-- Pilih Nama --</option>
                  </select>
                </div>
                <div class="input-group">
                  <label class="custom-font input-group-text" for="atasan-pengembali">Atasan</label>
                  <input type="text" class="custom-font form-control" style="margin-top: 15px;margin-bottom:8px;cursor:default;" name="atasan_pengembali" id="atasan-pengembali" readonly>
                </div>
            </div>
            </div>
            
            <div class="custom-penerima penerima border border-1 p-1 m-1 rounded-3">
              <div class="row">
                <h4>Penerima</h4>
                <div class="input-group">
                  <label class="custom-font input-group-text" style="width: 75px;" for="lokasi-penerima">Lokasi</label>
                  <select name="lokasi_penerima" id="lokasi-penerima" class="custom-font form-select" onchange="tampilkanLantai('penerima')" required>
                    <option value="PT.MSAL (HO)" <?= $data['lokasi_penerima'] == 'PT.MSAL (HO)' ? 'selected' : '' ?>>PT.MSAL (HO)</option>
                  </select>
                  <label id="lantai-penerima-container" class="custom-font input-group-text" style="display:none" for="lantai-penerima">Lantai</label>
                  <select name="lantai_penerima" id="lantai-penerima" class="custom-font form-select" style="display:none;" onchange="filterNamaKaryawan('penerima')">
                  </select>
                </div>
              </div>
              <div class="row">
                <div class="input-group">
                <label class="custom-font input-group-text" style="width: 75px;" for="nama-penerima">Nama</label>
                <select name="nama_penerima" id="nama-penerima" class="custom-font form-select" onchange="loadAtasan('penerima')" required>
                  <option value="">-- Pilih Nama --</option>
                </select>
                </div>
                <div class="input-group">
                  <label class="custom-font input-group-text" for="atasan-penerima">Atasan</label>
                  <input type="text" class="custom-font form-control" style="margin-top: 15px;margin-bottom:8px;cursor:default;" name="atasan_penerima" id="atasan-penerima" readonly>
                </div>
              </div>
            </div>

          </div>

      </div>

      <div class="custom-barang-gambar col-6">
        <div class="custom-barang row">
          <div class="data-barang border border-1 p-1 m-1 rounded-3">
            <?php if (!empty($barang_list)): ?>
            <?php $barang = $barang_list[0]; ?>
            <div class="d-flex justify-content-center"><h4>Data Barang</h4></div>
            <div class="row">
              <div class="input-group mt-3">
                <span class="custom-font input-group-text">Jenis Barang</span>
                <textarea class="custom-font form-control" name="jenis_barang[]" required><?= htmlspecialchars($barang['jenis_barang']) ?></textarea>
              </div>
              <div class="input-group" style="width: 500px;">
                <label class="custom-font input-group-text" style="width: 113px;">Jumlah</label>
                <input class="custom-font form-control" type="number" style="margin-top: 15px;margin-bottom:8px;" name="jumlah[]" value="<?= $barang['jumlah'] ?>" required>
                <label class="custom-font input-group-text" for="kondisi">Kondisi</label>
                <select name="kondisi[]" class="custom-font form-select" required>
                  <option value="">-- Pilih Kondisi --</option>
                  <option value="Baik" <?= $barang['kondisi'] === 'Baik' ? 'selected' : '' ?>>Baik</option>
                  <option value="Rusak" <?= $barang['kondisi'] === 'Rusak' ? 'selected' : '' ?>>Rusak</option>
                </select>
              </div>
              <div class="input-group">
                <span class="custom-font input-group-text" style="width: 113px;">Keterangan</span>
                <textarea class="custom-font form-control" name="keterangan[]"><?= htmlspecialchars($barang['keterangan']) ?></textarea>
              </div>

            </div>
            <?php endif; ?>
            <div id="data-barang" class="d-flex flex-column gap-2 mt-2">
              <?php for ($i = 1; $i < count($barang_list); $i++): ?>
                <?php $barang = $barang_list[$i]; ?>
                <div class="barang-wrapper mb-2" style="border-top: black 1px solid;">
                  <div class="row">
                    <div class="input-group mt-3">
                      <span class="custom-font input-group-text">Jenis Barang</span>
                      <textarea class="custom-font form-control" name="jenis_barang[]" required><?= htmlspecialchars($barang['jenis_barang']) ?></textarea>
                    </div>
                    <div class="input-group" style="width: 500px;">
                      <label class="custom-font input-group-text" style="width: 113px;">Jumlah</label>
                      <input class="form-control" type="number" style="margin-top: 15px;margin-bottom:8px;" name="jumlah[]" value="<?= $barang['jumlah'] ?>" required>
                      <label class="custom-font input-group-text" for="kondisi">Kondisi</label>
                      <select name="kondisi[]" class="custom-font form-select" required>
                        <option value="">-- Pilih Kondisi --</option>
                        <option value="Baik" <?= $barang['kondisi'] === 'Baik' ? 'selected' : '' ?>>Baik</option>
                        <option value="Rusak" <?= $barang['kondisi'] === 'Rusak' ? 'selected' : '' ?>>Rusak</option>
                      </select>
                    </div>
                    <div class="input-group">
                      <span class="custom-font input-group-text" style="width: 113px;">Keterangan</span>
                      <textarea class="custom-font form-control" name="keterangan[]"><?= htmlspecialchars($barang['keterangan']) ?></textarea>
                    </div>
                  </div>
                  <div class="d-flex flex-column mt-2">
                    <button type="button" class="custom-font btn btn-danger w-50 align-self-center" onclick="this.closest('.barang-wrapper').remove()">Hapus</button>
                  </div>
                </div>
              <?php endfor; ?>
            </div>


            <div class="d-flex flex-column"><button type="button" class="custom-font btn btn-primary w-50 align-self-center" onclick="tambahDataBarang()">+ Tambah Data Barang</button></div>
          </div>
        </div>

        <div class="custom-gambar row">
          <div class="data-gambar border border-1 p-1 m-1 rounded-3">
            <div class="form-section mb-0">
              <div class="d-flex justify-content-center">
                <h4 class="mt-0 pt-0 mb-3">Gambar Lampiran</h4>
              </div>
              <div id="gambar-container" class="d-flex flex-column gap-2">
              <?php while ($row = $gambarResult->fetch_assoc()): ?>
                <div class="gambar-wrapper" style="position: relative; display: flex; flex-direction: column; gap: 5px; margin-bottom: 1rem;">
                  <input type="hidden" name="gambar_lama_id[]" value="<?= $row['id'] ?>">
                  <input type="file" name="gambar_lama_file[<?= $row['id'] ?>]" accept="image/*" onchange="previewGantiGambar(this)" style="margin-bottom: 5px;">
                  <img src="<?= htmlspecialchars($row['file_path']) ?>" style="max-width: 300px; height: auto; border: 1px solid #ccc; border-radius: 5px;">
                  <button type="button" class="custom-font btn btn-danger" onclick="hapusGambarLama(this, <?= $row['id'] ?>)">Hapus</button>
                  <input type="hidden" name="hapus_gambar[]" value="" class="hapus-gambar-<?= $row['id'] ?>">
                </div>
              <?php endwhile; ?>
            </div>
              <div id="gambar-container" class="d-flex flex-column gap-2"></div>
              <div class="d-flex flex-column"><button type="button" class="custom-font btn btn-primary w-50 align-self-center" onclick="tambahGambar()">+ Tambah Gambar Lampiran</button></div>
            </div>
          </div>
        </div>
      </div>  
      </div>
    </div>
  <div class="simpan d-flex flex-column"><input type="submit" value="Simpan" class="btn btn-success w-25 mt-3 align-self-end"></div>
</form>
    </main>
    <!--Akhir::Main Content-->

  </div>
    
<script>
const nomorInput = document.getElementById('nomor-ba');
const feedback = document.getElementById('nomor-ba-feedback');

nomorInput.addEventListener('blur', function () {
  const nomor = this.value.padStart(3, '0');

  fetch(window.location.pathname + '?ajax=1', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'nomor_ba=' + encodeURIComponent(nomor)
  })
  .then(res => res.json())
  .then(data => {
    if (data.exists) {
      nomorInput.classList.add('is-invalid');
      feedback.style.display = 'block';
    } else {
      nomorInput.classList.remove('is-invalid');
      feedback.style.display = 'none';
    }
  })
  .catch(err => console.error('AJAX error:', err));
});

document.querySelector('form').addEventListener('submit', function(e) {
  if (nomorInput.classList.contains('is-invalid')) {
    e.preventDefault();
    nomorInput.focus();
  }
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

<script>// Fungsi untuk data karyawan

const dataKaryawan = <?php echo json_encode($data_karyawan); ?>;
const dataAtasan = <?php echo json_encode($data_atasan); ?>;

function tampilkanLantai(peran) {
  const lokasi = document.getElementById(`lokasi-${peran}`).value;
  const container = document.getElementById(`lantai-${peran}-container`);
  const select = document.getElementById(`lantai-${peran}`);
  select.innerHTML = "";

  if (lokasi === 'PT.MSAL (HO)') {
    const lantaiSet = new Set(dataKaryawan.map(k => k.lantai));
    lantaiSet.forEach(l => {
      const opt = document.createElement("option");
      opt.value = l;
      opt.textContent = l;
      select.appendChild(opt);
    });
    container.style.display = 'block';
    select.style.display = 'block';
    filterNamaKaryawan(peran); // Load nama awal
  } else {
    container.style.display = 'none';
    select.style.display = 'none';
    document.getElementById(`nama-${peran}`).innerHTML = "";
  }
}

function filterNamaKaryawan(peran) {
  const lantai = document.getElementById(`lantai-${peran}`).value;
  const select = document.getElementById(`nama-${peran}`);
  select.innerHTML = '<option value="" disabled>-- Pilih Nama --</option>';

  dataKaryawan.filter(k => k.lantai === lantai).forEach(k => {
    const opt = document.createElement("option");
    opt.value = k.nama;
    opt.textContent = `${k.nama} - ${k.posisi} (${k.departemen})`;
    select.appendChild(opt);
  });
}

function loadAtasan(peran) {
  const nama = document.getElementById(`nama-${peran}`).value;
  const karyawan = dataKaryawan.find(k => nama.startsWith(k.nama));
  if (!karyawan) return document.getElementById(`atasan-${peran}`).value = "";

  const atasan = dataAtasan.find(a => a.departemen === karyawan.departemen);
  document.getElementById(`atasan-${peran}`).value = atasan ? `${atasan.nama} - ${atasan.posisi} (${atasan.departemen})` : 'Tidak ada';
}

</script>

<script>// Prefill data di form edit
window.addEventListener('DOMContentLoaded', () => {
  // Prefill pengembali
  document.getElementById('lokasi-pengembali').value = "<?= $data['lokasi_pengembali'] ?>";
  tampilkanLantai('pengembali');
  
  setTimeout(() => {
    document.getElementById('lantai-pengembali').value = "<?= $lantai_pengembali ?>";
    filterNamaKaryawan('pengembali');

    setTimeout(() => {
      document.getElementById('nama-pengembali').value = "<?= $data['nama_pengembali'] ?>";
      loadAtasan('pengembali');
    }, 200);
  }, 200);

});
window.addEventListener('DOMContentLoaded', () => {
  // Penerima
  document.getElementById('lokasi-penerima').value = "<?= $data['lokasi_penerima'] ?>";
  tampilkanLantai('penerima');

  setTimeout(() => {
    document.getElementById('lantai-penerima').value = "<?= $lantai_penerima ?>";
    filterNamaKaryawan('penerima');

    setTimeout(() => {
      document.getElementById('nama-penerima').value = "<?= $data['nama_penerima'] ?>";
      loadAtasan('penerima');
    }, 200);
  }, 200);
});
</script>

<script>// Fungsi untuk menambah input Data Barang dinamis
function tambahDataBarang() {
  const container = document.getElementById("data-barang");

  const wrapper = document.createElement("div");
  wrapper.className = "data-barang";
  wrapper.style.borderTop = "1px solid black";

  const rowContainer = document.createElement("div");
  rowContainer.className = "row";

  // Baris Jenis Barang
  const inputGroupJenis = document.createElement("div");
  inputGroupJenis.className = "input-group mt-3";
  inputGroupJenis.innerHTML = `
    <span class="input-group-text">Jenis Barang</span>
    <textarea class="form-control" name="jenis_barang[]" required></textarea>
  `;

  // Baris Jumlah & Kondisi
  const inputGroupJumlahKondisi = document.createElement("div");
  inputGroupJumlahKondisi.className = "input-group";
  inputGroupJumlahKondisi.innerHTML = `
    <label class="input-group-text">Jumlah</label>
    <input class="form-control" style="margin-top:15px; margin-bottom:8px;" type="number" name="jumlah[]" required>
    <label class="input-group-text" for="kondisi">Kondisi</label>
    <select name="kondisi[]" class="form-select" required>
      <option value="">-- Pilih Kondisi --</option>
      <option value="Baik">Baik</option>
      <option value="Rusak">Rusak</option>
    </select>
  `;

  // Baris Keterangan
  const inputGroupKeterangan = document.createElement("div");
  inputGroupKeterangan.className = "input-group";
  inputGroupKeterangan.innerHTML = `
    <span class="input-group-text">Keterangan</span>
    <textarea class="form-control" name="keterangan[]"></textarea>
  `;

  // Tombol Hapus
  const tombolHapus = document.createElement("div");
  tombolHapus.className = "d-flex flex-column";
  const btnHapus = document.createElement("button");
  btnHapus.type = "button";
  btnHapus.textContent = "Hapus";
  btnHapus.className = "custom-font btn btn-danger w-50 align-self-center";
  btnHapus.style.marginTop = "10px";
  btnHapus.style.marginBottom = "10px";
  btnHapus.onclick = function () {
    container.removeChild(wrapper);
  };
  tombolHapus.appendChild(btnHapus);

  // Gabungkan semua ke rowContainer lalu ke wrapper
  rowContainer.appendChild(inputGroupJenis);
  rowContainer.appendChild(inputGroupJumlahKondisi);
  rowContainer.appendChild(inputGroupKeterangan);
  rowContainer.appendChild(tombolHapus);

  wrapper.appendChild(rowContainer);
  container.appendChild(wrapper);
}
</script>



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
  input.name = 'gambar_baru[]';
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
  btnHapus.className = 'custom-font btn btn-danger';
  btnHapus.style.marginTop = '5px';
  btnHapus.onclick = function () {
    container.removeChild(wrapper);
  };

  wrapper.appendChild(input);
  wrapper.appendChild(preview);
  wrapper.appendChild(btnHapus);
  container.appendChild(wrapper);
}

function previewGantiGambar(input) {
  const preview = input.nextElementSibling;
  const file = input.files[0];
  if (file && preview.tagName.toLowerCase() === 'img') {
    preview.src = URL.createObjectURL(file);
    preview.style.display = 'block';
  }
}

function hapusGambarLama(button, id) {
  const wrapper = button.closest('.gambar-wrapper');
  wrapper.style.display = 'none';
  const hiddenInput = document.querySelector(`.hapus-gambar-${id}`);
  if (hiddenInput) hiddenInput.value = 'hapus';
}

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