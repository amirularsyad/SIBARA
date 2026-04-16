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
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>BA Serah Terima Penggunaan Notebook Inventaris</title>

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
        overflow-x: hidden;
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

    th:first-child, td:first-child { width: 4%; } /* No */
    th:nth-child(2), td:nth-child(2) { width: 6%; }  /* Tanggal */
    th:nth-child(3), td:nth-child(3) { width: 6%; }  /* No BA */
    th:nth-child(4), td:nth-child(4) { width: 20%; }  /* Peminjam */
    th:nth-child(5), td:nth-child(5) { width: 20%; }  /* Serial Number */
    th:nth-child(6), td:nth-child(6) { width: 30%; }  /* Merek */
    th:last-child, td:last-child { width: 30px;}   /* Actions */

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

<style>/* Pagination Styling */
    .pagination-container{
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 5px;
    }

    .pagination-container select {
    padding: 4px 8px;
    border-radius: 5px;
    border: none
    }

    .pagination-links a {
        text-decoration: none;
        border-radius: 4px;
        padding: 5px 10px;
        color: #333;
    }


  .pagination-links span {
    border: 1px solid #ccc;
    border-radius: 4px;
    padding: 5px 10px;
    color: #aaa;
  }
</style>

<style>/*Animista*/

</style>

</head>
<body  class="layout-fixed sidebar-expand-lg bg-body-tertiary">

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
            <div class="ms-auto">
                <a href="../logout.php" class="btn btn-danger fw-bold" onclick="return confirm('Yakin ingin logout?')" title="Logout"><i class="bi bi-box-arrow-right fw-bolder"></i></a>
            </div>
        </ul>
        <!--end::End Navbar Links-->
        </div>
        <!--end::Container-->
    </nav>

    <aside class="app-sidebar shadow" style="z-index: 10;" data-bs-theme="dark"> <!-- Sidebar -->
        <div class="sidebar-brand" style="border:none;">
        <a href="../index.php" class="brand-link">
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
                        <a href="#" class="nav-link text-white" aria-disabled="true">
                        <i class="bi bi-laptop"></i>
                        <p class="text-white">
                            Notebook
                        </p>
                        </a>
                    </li>
                </ul>
            </li>
            <li class="nav-header">
                APPROVAL
            </li>
            <li class="nav-item">
                <a href="../personal/approval.php" class="nav-link">
                <i class="nav-icon bi bi-clipboard2-check"></i>
                <p>
                    Approve BA
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

    <?php //Koneksi database
    include '../koneksi.php';
    
    // Konfigurasi pagination
    $per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 25;
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($page - 1) * $per_page;

    $result = mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM ba_serah_terima_notebook");
    $total_rows = mysqli_fetch_assoc($result)['total'];
    $total_pages = ceil($total_rows / $per_page);

    ?>
    <?php
    $query = "
        SELECT 
            ba.id, 
            ba.tanggal, 
            ba.nomor_ba, 
            ba.nama_peminjam, 
            ba.sn, 
            barang.merk 
        FROM ba_serah_terima_notebook ba
        LEFT JOIN barang_notebook_laptop barang 
            ON ba.sn = barang.serial_number
        ORDER BY ba.tanggal DESC
    ";

    $result = $koneksi->query($query);
    $no = 1;
    ?>

    <main class="app-main"><!-- Main Content -->
    
    

    <section class="table-wrapper bg-white position-relative overflow-visible">
        <h2>Daftar Serah Terima Penggunaan Notebook Inventaris</h2>

        <table id="myTable" class="table table-bordered table-striped text-center">
            <a href="form_input.php" class="btn btn-success position-absolute" style="width:max-content;height:max-content;top:72px;left:220px;z-index:1;"><i class="bi bi-plus-lg"></i></a>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Tanggal</th>
                    <th>Nomor BA</th>
                    <th>Nama Peminjam</th>
                    <th>Serial Number</th>
                    <th>Merek</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><?= htmlspecialchars($row['tanggal']) ?></td>
                    <td><?= htmlspecialchars($row['nomor_ba']) ?></td>
                    <td><?= htmlspecialchars($row['nama_peminjam']) ?></td>
                    <td><?= htmlspecialchars($row['sn']) ?></td>
                    <td><?= htmlspecialchars($row['merk'] ?? '-') ?></td>
                    <td>
                        <a class='btn btn-secondary btn-sm' href='detail.php?id=<?= $row['id'] ?>'><i class="bi bi-eye-fill"></i></a>
                        <a class='btn btn-primary btn-sm' href='surat_output.php?id=<?= $row['id'] ?>' target="_blank"><i class="bi bi-file-earmark-text-fill"></i></a>
                        <a class='btn btn-warning btn-sm' href= "form_edit.php?id=<?= $row['id'] ?>"><i class="bi bi-feather"></i></a>
                        <a class='btn btn-danger btn-sm' href='delete.php?id=<?= $row['id'] ?> ' onclick="return confirm('Apakah Anda yakin ingin menghapus data ini?')"><i class="bi bi-x-lg"></i></a>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php else: ?>
                <tr>
                    <td colspan="7">Belum ada data peminjaman notebook.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <!-- <div class="pagination-container">
            <div>
                <strong><?php echo ($offset + 1) . " - " . min($offset + $per_page, $total_rows); ?></strong>
                dari <?php echo $total_rows; ?>
            </div>

            <form method="GET" style="display: flex; align-items: center; gap: 0.5rem;">
                <select name="per_page" onchange="this.form.submit()">
                <?php foreach ([10, 25, 50, 100] as $opt) {
                    $selected = $per_page == $opt ? "selected" : "";
                    echo "<option value='$opt' $selected>$opt</option>";
                } ?>
                </select>
                <span>Data Ditampilkan</span>
            </form>

            <div class="pagination-links" style="display: flex; gap: 0.25rem;">
                <?php
                function pageLink($page_num, $label = null, $disabled = false) {
                global $page, $per_page;
                $label = $label ?? $page_num;
                if ($disabled) {
                    return "<span style='padding: 5px 10px; opacity: 0.5;'>$label</span>";
                } else {
                    $active = $page == $page_num ? "style='font-weight:bold; text-decoration:underline;'" : "";
                    return "<a href='?page=$page_num&per_page=$per_page' class='btn btn-primary text-white' style='padding: 5px 10px;' $active>$label</a>";
                }
                }

                echo pageLink(1, "&laquo;", $page == 1);
                echo pageLink($page - 1, "&lsaquo;", $page == 1);

                for ($i = 1; $i <= $total_pages; $i++) {
                echo pageLink($i);
                if ($i >= 9) break; // Maksimal 9 halaman tampil
                }

                echo pageLink($page + 1, "&rsaquo;", $page == $total_pages);
                echo pageLink($total_pages, "&raquo;", $page == $total_pages);
                ?>
            </div>
        </div> -->
    </section>
    
    </main>
    </div>
    
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

<!-- Bootstrap 5 -->
<script src="../assets/bootstrap-5.3.6-dist/js/bootstrap.min.js"></script>

<!-- popperjs Bootstrap 5 -->
<script src="../assets/js/popper.min.js"></script>

<!-- AdminLTE -->
<script src="../assets/adminlte/js/adminlte.js"></script>

<!-- OverlayScrollbars -->
<script src="../assets/js/overlayscrollbars.browser.es6.min.js"></script>


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
