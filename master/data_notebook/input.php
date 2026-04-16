<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Form Data Notebook Inventaris</title>

  <!-- Bootstrap 5 -->
    <link 
      rel="stylesheet" 
      href="../../assets/bootstrap-5.3.6-dist/css/bootstrap.min.css"
    />

  <!-- Bootstrap Icons -->
    <link
        rel="stylesheet"
        href="../../assets/icons/icons-main/font/bootstrap-icons.min.css"
    />

  <!-- AdminLTE -->
    <link 
        rel="stylesheet" 
        href="../../assets/adminlte/css/adminlte.css" 
    />

  <!-- OverlayScrollbars -->
    <link
        rel="stylesheet"
        href="../../assets/css/overlayscrollbars.min.css"
    />

  <!-- Favicon -->
    <link 
      rel="icon" type="image/png" 
      href="../../assets/img/logo.png"
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

      <?php include '../../koneksi.php'; ?>

<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">

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
            <div class="ms-auto">
                <a href="" class="btn btn-danger fw-bold"><i class="bi bi-box-arrow-right fw-bolder"></i></a>
            </div>
        </ul>
        <!--end::End Navbar Links-->
        </div>
        <!--end::Container-->
    </nav>
    <!--end::Header-->

    <!--Awal::Sidebar-->
    <aside class="app-sidebar shadow border-end border-1" data-bs-theme="dark">
        <div class="sidebar-brand">
        <a href="" class="brand-link">
            <img
            src="../../assets/img/logo.png"
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
            <li class="nav-item">
                <a href="../../index.php" class="nav-link">
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
                <a href="../../ba_kerusakan-fix/ba_kerusakan.php" class="nav-link">
                <i class="nav-icon bi bi-newspaper"></i>
                <p>
                    BA Kerusakan
                </p>
                </a>
            </li>
            <!-- List BA Pengembalian -->
            <li class="nav-item">
                <a href="../../ba_pengembalian/ba_pengembalian.php" class="nav-link">
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
                        <a href="../../ba_serah-terima-notebook/ba_serah-terima-notebook.php" class="nav-link">
                        <i class="bi bi-laptop text-white"></i>
                        <p>
                            Notebook
                        </p>
                        </a>
                    </li>
                </ul>
            </li>
            <li class="nav-header">
                MASTER
            </li>
            <li class="nav-item">
                <a href="#" class="nav-link">
                <i class="nav-icon bi bi-person-circle"></i>
                <p>
                    Data Karyawan
                </p>
                </a>
            </li>
            <li class="nav-item">
                <a href="tabel.php" class="nav-link">
                <i class="nav-icon bi bi-laptop text-white"></i>
                <p class="text-white">
                    Data Notebook
                </p>
                </a>
            </li>
            </ul>

        </nav>
        </div>
    </aside>
    <!--Akhir::Sidebar-->

    

    <!--Awal::Main Content-->
    <main class="app-main">
      
    <!--Status Sukses Pop Up-->
    <?php if (isset($_GET['status']) && $_GET['status'] == 'sukses'): ?>
        <div class="alert alert-success d-flex flex-column align-items-center" role="alert" style="margin-top: 20px;">
            <i class="bi bi-check-circle"></i> <p>Data berhasil disimpan ke database.</p>
        </div>
    <?php endif; ?>

        <h2 class="text-black">Form Data Notebook Inventaris</h2>
<form method="post" action="simpan.php" enctype="multipart/form-data" style="width: 1100px;">
    <div class="form-section">
        <div class="row">
        <div class="col-12">
            <div class="row">
                <div class="col-4">
                <div class="input-group" style="width:max-content;">
                    <span class="input-group-text">Tanggal Perolehan</span>
                    <input type="date" class="form-control" name="tgl_beli" id="tgl_beli" max="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d') ?>" required>
                </div>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-6">
                <div class="input-group">
                    <span class="input-group-text" style="padding-right:45px;">Merk</span>
                    <input type="text" name="merk" id="merk" class="form-control" value="">
                </div>
                </div>
                <div class="col-6">
                <div class="input-group">
                    <span class="input-group-text">Serial Number</span>
                    <input type="text" name="serial_number" id="serial_number" class="form-control" value="">
                </div>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-6">
                <div class="input-group">
                    <span class="input-group-text" style="padding-right: 12px;">Processor</span>
                    <input type="text" name="processor" id="processor" class="form-control" value="">
                </div>
                </div>
                <div class="col-6">
                    <div class="input-group">
                        <label class="input-group-text mt-0 mb-4"style="padding-right: 18px;" for="penyimpanan">Penyimpanan</label>
                            <select name="penyimpanan" id="penyimpanan" class="form-select mt-0 mb-4" required>
                            <option value="" disabled>-- Pilih Penyimpanan --</option>
                            <option value="128GB">128GB</option>
                            <option value="256GB">256GB</option>
                            <option value="512GB">512GB</option>
                            <option value="1TB">1TB</option>
                            <option value="">Lainnya</option>
                            </select>
                        
                            <select name="jenis-penyimpanan" id="jenis-penyimpanan" class="form-select mt-0 mb-4" required>
                            <option value="" disabled>-- Pilih Jenis --</option>
                            <option value="SSD NVME">SSD NVME</option>
                            <option value="SSD SATA">SSD SATA</option>
                            <option value="HDD">HDD</option>
                            <option value="">Lainnya</option>
                            </select>
                    </div>
                
                </div>
            </div>
            <div class="row">
                <div class="col-6">
                <div class="input-group">
                    <span class="input-group-text" style="padding-right:45px;">RAM</span>
                    <input type="text" name="ram" id="ram" class="form-control" value="">
                </div>
                </div>
                <div class="col-6">
                <div class="input-group">
                    <span class="input-group-text" style="padding-right: 80px;">VGA</span>
                    <input type="text" name="vga" id="vga" class="form-control" value="">
                </div>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-6">
                <div class="input-group">
                    <span class="input-group-text" style="padding-right:22px;">Monitor</span>
                    <input type="text" name="monitor" id="monitor" class="form-control" value="">
                </div>
                </div>
                <div class="col-6">
                <div class="input-group">
                    <span class="input-group-text" style="padding-right:62px;">Baterai</span>
                    <input type="text" name="baterai" id="baterai" class="form-control" value="">
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
    



<!-- Bootstrap 5 -->
<script src="../../assets/bootstrap-5.3.6-dist/js/bootstrap.min.js"></script>

<!-- popperjs Bootstrap 5 -->
<script src="../../assets/js/popper.min.js"></script>

<!-- AdminLTE -->
<script src="../../assets/adminlte/js/adminlte.js"></script>

<!-- OverlayScrollbars -->
<script src="../../assets/js/overlayscrollbars.browser.es6.min.js"></script>

    

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
