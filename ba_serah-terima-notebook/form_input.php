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
  <meta charset="UTF-8">
  <title>Form Serah Terima Penggunaan Notebook Inventaris</title>

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
      font-weight: 500;
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

      <?php include '../koneksi.php'; ?>

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
    <aside class="app-sidebar shadow" data-bs-theme="dark">
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
                <a href="../ba_pengembalian/ba_pengembalian.php" class="nav-link">
                <i class="nav-icon bi bi-newspaper"></i>
                <p>
                    BA Pengembalian
                </p>
                </a>
            </li>
            <!-- List BA Serah Terima -->
            <li class="nav-item menu-open">
                <a href="#" class="nav-link">
                <i class="nav-icon bi bi-newspaper"></i>
                <p>
                    BA Serah Terima
                    <i class="nav-arrow bi bi-chevron-right"></i>
                </p>
                </a>
                <ul class="nav nav-treeview">
                  <li class="nav-item">
                      <a href="ba_serah-terima-notebook.php" class="nav-link">
                      <i class="bi bi-laptop text-white"></i>
                      <p class="text-white">
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
            
            </ul>

        </nav>
        </div>
    </aside>
    <!--Akhir::Sidebar-->

      <?php include '../koneksi.php';
      
        $tanggal_hari_ini = date('Y-m-d');
        $bulan_ini = date('m');
        $tahun_ini = date('Y');

        // Ambil nomor_ba tertinggi di bulan & tahun yang sama
        $stmt = $koneksi->prepare("
          SELECT nomor_ba 
          FROM ba_serah_terima_notebook 
          WHERE MONTH(tanggal) = ? AND YEAR(tanggal) = ? 
          ORDER BY CAST(nomor_ba AS UNSIGNED) DESC 
          LIMIT 1
        ");
        $stmt->bind_param("ss", $bulan_ini, $tahun_ini);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        if ($row && is_numeric($row['nomor_ba'])) {
            $last_nomor = (int)$row['nomor_ba'];
            $nomor_ba_baru = str_pad($last_nomor + 1, 3, '0', STR_PAD_LEFT);
        } else {
            $nomor_ba_baru = '001';
        }
      ?>

      <?php

      $query_atasan = $koneksi->query("SELECT nama, posisi, departemen FROM data_karyawan WHERE jabatan = 'Dept. Head' ORDER BY nama ASC");
      $data_atasan = [];
      while ($row = $query_atasan->fetch_assoc()) {
          $data_atasan[] = $row;
      }
      ?>
    <!--Koneksi Nama Karyawan-->
      <?php
      // Ambil semua data user, nanti difilter via JavaScript
      $query_karyawan = $koneksi->query("SELECT nama, posisi, departemen, lantai FROM data_karyawan ORDER BY nama ASC");
      $data_karyawan = [];
      while ($row = $query_karyawan->fetch_assoc()) {
          $data_karyawan[] = $row;
      }
      ?>

      <?php
      // Validasi AJAX nomor BA 
      if (isset($_GET['ajax']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
          $nomor = str_pad($_POST['nomor_ba'], 3, '0', STR_PAD_LEFT);
          $stmt = $koneksi->prepare("SELECT COUNT(*) as total FROM ba_serah_terima_notebook WHERE nomor_ba = ?");
          $stmt->bind_param("s", $nomor);
          $stmt->execute();
          $result = $stmt->get_result()->fetch_assoc();

          header('Content-Type: application/json');
          echo json_encode(['exists' => $result['total'] > 0]);
          exit;
      }
?>

      <?php
      // Ambil total data dari ba_serah_terima_notebook
      $query = $koneksi->query("SELECT COUNT(*) AS total FROM ba_serah_terima_notebook");
      $result = $query->fetch_assoc();
      $nomorBerikutnya = $result['total'] + 1;

      // Ambil semua SN dari database
      $query = "SELECT id, serial_number FROM barang_notebook_laptop WHERE status != 'digunakan'";
      $result = $koneksi->query($query);
      ?>

    <!--Awal::Main Content-->
    <main class="app-main">
      
    <!--Status Sukses Pop Up-->
      <?php if (isset($_GET['status']) && $_GET['status'] == 'sukses'): ?>
          <div class="alert alert-success d-flex flex-column align-items-center" role="alert" style="margin-top: 20px;">
              <i class="bi bi-check-circle"></i> <p>Data berhasil disimpan ke database.</p>
          </div>
      <?php endif; ?>

      <h2 class="text-black">Form Serah Terima Penggunaan Notebook Inventaris</h2>
<form method="post" action="proses_simpan.php" enctype="multipart/form-data">
  <div class="form-section">
    <div class="row">

      <div class="col-6">
          <div class="row mt-3 mb-3">
            <div class="col-4">
                <div class="input-group" style="width:max-content;">
                    <span class="input-group-text">Tanggal</span>
                    <input type="date" class="form-control" name="tanggal" id="tanggal" max="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d') ?>" required>
                </div>
            </div>
            <div class="col-6">
                <div class="input-group" style="width:max-content;">
                    <span class="input-group-text">Nomor BA</span>
                    <input type="number" name="nomor_ba" id="nomor_ba" class="form-control" value="<?= $nomor_ba_baru ?>" style="width:60px; cursor:default;" readonly>
                </div>
                
            </div>
            
          </div>

          <div class="border border-1 p-1 rounded-3">
            <div class="row">
              <h4>Peminjam</h4>
              <div class="input-group">
                <label class="input-group-text" for="lokasi-peminjam">Lokasi</label>
                <select name="lokasi_peminjam" id="lokasi-peminjam" class="form-select" onchange="tampilkanLantai('peminjam')" required>
                  <option value="">-- Pilih Lokasi --</option>
                  <option value="PT.MSAL (HO)">PT.MSAL (HO)</option>
                </select>
                  <label id="lantai-peminjam-container" class="input-group-text" for="lantai-peminjam" style="display:none">Lantai</label>
                  <select name="lantai_peminjam" id="lantai-peminjam" class="form-select" style="display:none;" onchange="filterNamaKaryawan('peminjam')">
                  </select>
              </div>
            </div>
            
            <div class="row">
              <div class="input-group">
                <label class="input-group-text" for="nama-peminjam">Nama</label>
                <select name="nama_peminjam" id="nama-peminjam" class="form-select" onchange="loadAtasan('peminjam')" required>
                <option value="" disabled>-- Pilih Nama --</option>
                </select>
              </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <div class="input-group">
                        <span class="input-group-text">Alamat peminjam</span>
                        <textarea class="form-control" rows="3" name="alamat[]" required></textarea>
                    </div>
                </div>
            </div>
          </div>
          
          
      </div>
        
      <div class="col-6">
        <input type="hidden" name="sn" id="hidden_sn">
        <div class="row mt-3 mb-3 border border-1 rounded-3 p-1">
            <h4>Data Barang</h4>
            <div class="row mt-3">
                <div class="col-6 p-0">
                    <div class="input-group">
                        <span class="input-group-text" style="width: 160px;">Serial Number</span>
                        <input type="text" class="form-control" name="sn" required>
                    </div>
                </div>
            
                <div class="col-6 p-0">
                    <div class="input-group ms-2">
                        <span class="input-group-text" style="width: 120px;">Merk</span>
                        <input type="text" class="form-control" name="merk" required>
                    </div>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-6 p-0">
                    <div class="input-group">
                        <span class="input-group-text" style="width: 160px;">Prosesor</span>
                        <input type="text" class="form-control" name="prosesor" required>
                    </div>
                </div>

                <div class="col-6 p-0">
                    <div class="input-group ms-2">
                        <span class="input-group-text" style="width: 120px;">Penyimpanan</span>
                        <input type="text" class="form-control" name="penyimpanan" required>
                    </div>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-6 p-0">
                    <div class="input-group">
                        <span class="input-group-text" style="width: 160px;">Monitor</span>
                        <input type="text" class="form-control" name="monitor" required>
                    </div>
                </div>

                <div class="col-6 p-0">
                    <div class="input-group ms-2">
                        <span class="input-group-text" style="width: 120px;">Baterai</span>
                        <input type="text" class="form-control" name="baterai" required>
                    </div>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-6 p-0">
                    <div class="input-group">
                        <span class="input-group-text" style="width: 160px;">VGA</span>
                        <input type="text" class="form-control" name="vga" required>
                    </div>
                </div>

                <div class="col-6 p-0">
                    <div class="input-group ms-2">
                        <span class="input-group-text" style="width: 120px;">RAM</span>
                        <input type="text" class="form-control" name="ram" required>
                    </div>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-5 p-0">
                    <div class="input-group">
                        <span class="input-group-text" style="width: 160px;">Tanggal Pembelian</span>
                        <input type="date" class="form-control" name="tanggal-pembelian" id="tanggal-pembelian" max="<?= date('Y-m-d') ?>" value="" required>
                    </div>
                </div>
            </div>
            <!-- <div class="row justify-content-center align-items-center">
                <div class="col-4">Merk</div>
                <div class="col">: <span id="merk">-</span></div>
            </div>
            <div class="row justify-content-center align-items-center">
                <div class="col-4">Prosesor</div>
                <div class="col">: <span id="processor">-</span></div>
            </div>
            <div class="row justify-content-center align-items-center">
                <div class="col-4">Penyimpanan</div>
                <div class="col">: <span id="penyimpanan">-</span></div>
            </div>
            <div class="row justify-content-center align-items-center">
                <div class="col-4">Monitor</div>
                <div class="col">: <span id="monitor">-</span></div>
            </div>
            <div class="row justify-content-center align-items-center">
                <div class="col-4">Baterai</div>
                <div class="col">: <span id="baterai">-</span></div>
            </div>
            <div class="row justify-content-center align-items-center">
                <div class="col-4">VGA</div>
                <div class="col">: <span id="vga">-</span></div>
            </div>
            <div class="row justify-content-center align-items-center">
                <div class="col-4">RAM</div>
                <div class="col">: <span id="ram">-</span></div>
            </div>
            <div class="row justify-content-center align-items-center">
                <div class="col-4">Tanggal Pembelian</div>
                <div class="col">: <span id="tgl_beli">-</span></div>
            </div> -->
        </div>

        <div class="row">
          <div class="col-12 d-flex justify-content-end">
            <div class="input-group" style="width: 35%;">
                <label class="input-group-text" for="saksi">Saksi</label>
                <select name="saksi" id="saksi" class="form-select" required>
                    <option value="">-- Pilih Saksi --</option>
                    <option value="Tedy Paronto">Tedy Paronto</option>
                    <option value="M. Diecy Firmansyah">M. Diecy Firmansyah</option>
                    <option value="Rizki Sunandar">Rizki Sunandar</option>
                    <option value="Cikal Pramudya Alif">Cikal Pramudya Alif</option>
                </select>
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

<!-- Bootstrap 5 -->
<script src="../assets/bootstrap-5.3.6-dist/js/bootstrap.min.js"></script>

<!-- popperjs Bootstrap 5 -->
<script src="../assets/js/popper.min.js"></script>

<!-- AdminLTE -->
<script src="../assets/adminlte/js/adminlte.js"></script>

<!-- OverlayScrollbars -->
<script src="../assets/js/overlayscrollbars.browser.es6.min.js"></script>

<script>//Fungsi nomor BA
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
  const karyawan = dataKaryawan.find(k => k.nama === nama);
  if (!karyawan) return document.getElementById(`atasan-${peran}`).value = "";

  const atasan = dataAtasan.find(a => a.departemen === karyawan.departemen);
  document.getElementById(`atasan-${peran}`).value = atasan ? `${atasan.nama} - ${atasan.posisi} (${atasan.departemen})` : 'Tidak ada';
}

</script>

<script>// Fungsi untuk data Notebook/Laptop
// document.getElementById('serial_number').addEventListener('change', function () {
//     const select = this;
//     const selectedOption = select.options[select.selectedIndex];
//     const id = select.value;
//     const sn = selectedOption.getAttribute('data-serial');

//     // Isi input hidden dengan serial number
//     document.getElementById('hidden_sn').value = sn;

//     if (id === "") {
//         resetDetail();
//         return;
//     }

//     fetch('get_detail_barang.php?id=' + id)
//         .then(res => res.json())
//         .then(data => {
//             document.getElementById('merk').textContent = data.merk || '-';
//             document.getElementById('processor').textContent = data.processor || '-';
//             document.getElementById('penyimpanan').textContent = data.penyimpanan || '-';
//             document.getElementById('monitor').textContent = data.monitor || '-';
//             document.getElementById('baterai').textContent = data.baterai || '-';
//             document.getElementById('vga').textContent = data.vga || '-';
//             document.getElementById('ram').textContent = data.ram || '-';
//             document.getElementById('tgl_beli').textContent = data.tgl_beli || '-';
//         })
//         .catch(err => {
//             console.error('Gagal mengambil data:', err);
//             resetDetail();
//         });

//     function resetDetail() {
//         const fields = ['merk', 'processor', 'penyimpanan', 'monitor', 'baterai', 'vga', 'ram', 'tgl_beli'];
//         fields.forEach(id => {
//             document.getElementById(id).textContent = '-';
//         });
//     }
// });
</script>

<script>
document.getElementById('serial_number').addEventListener('change', function () {
    const selectedOption = this.options[this.selectedIndex];
    const id = selectedOption.getAttribute('data-id'); // ambil ID untuk fetch
    if (!id) {
        resetDetail();
        return;
    }

    fetch('get_detail_barang.php?id=' + id)
        .then(res => res.json())
        .then(data => {
            document.getElementById('merk').textContent = data.merk || '-';
            document.getElementById('processor').textContent = data.processor || '-';
            document.getElementById('penyimpanan').textContent = data.penyimpanan || '-';
            document.getElementById('monitor').textContent = data.monitor || '-';
            document.getElementById('baterai').textContent = data.baterai || '-';
            document.getElementById('vga').textContent = data.vga || '-';
            document.getElementById('ram').textContent = data.ram || '-';
            document.getElementById('tgl_beli').textContent = data.tgl_beli || '-';
        })
        .catch(err => {
            console.error('Gagal mengambil data:', err);
            resetDetail();
        });

    function resetDetail() {
        const fields = ['merk', 'processor', 'penyimpanan', 'monitor', 'baterai', 'vga', 'ram', 'tgl_beli'];
        fields.forEach(id => {
            document.getElementById(id).textContent = '-';
        });
    }
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
