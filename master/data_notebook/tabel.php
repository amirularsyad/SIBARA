<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Data Notebook</title>

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

    <link 
        rel="stylesheet" 
        href="../../assets/css/datatables.min.css"
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
                <a href="" class="btn btn-danger fw-bold"><i class="bi bi-box-arrow-right fw-bolder"></i></a>
            </div>
        </ul>
        <!--end::End Navbar Links-->
        </div>
        <!--end::Container-->
    </nav>

    <aside class="app-sidebar shadow border-end border-1" style="border-color: #2c3e50; z-index: 10;" data-bs-theme="dark"> <!-- Sidebar -->
        <div class="sidebar-brand">
        <a href="../../index.php" class="brand-link">
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
                        <i class="bi bi-laptop"></i>
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
                <a href="#" class="nav-link text-white" aria-disabled="true">
                <i class="nav-icon bi bi-laptop"></i>
                <p class="text-white">
                    Data Notebook
                </p>
                </a>
            </li>
            </ul>

            

        </nav>
        </div>
    </aside>

    <?php //Koneksi database
    include '../../koneksi.php';
    
    
    // Konfigurasi pagination
    $per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 25;
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($page - 1) * $per_page;

    // Hitung total data
    $result = mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM barang_notebook_laptop");
    $total_rows = mysqli_fetch_assoc($result)['total'];
    $total_pages = ceil($total_rows / $per_page);

    $query = "SELECT * FROM barang_notebook_laptop ORDER BY created_at DESC LIMIT $offset, $per_page";// ganti nama tabel sesuai
    $result = mysqli_query($koneksi, $query);
    $no = 1;
    ?>

    <main class="app-main"><!-- Main Content -->
    
    

    <section class="table-wrapper bg-white position-relative">
        <a href="input.php" class="btn btn-success position-absolute" style="width:100px;height:max-content;"><i class="bi bi-plus-lg"></i></a>
        <h2>Daftar Notebook Inventaris</h2>

        <table class="table table-bordered table-striped text-center">
            <colgroup>
                <col style="width: 5%;">
                <col style="width: 10%;">
                <col style="width: 10%;">
                <col style="width: 18%;">
                <col style="width: 17%;">
                <col style="width: 18%;">
                <col style="width: 12%;">
                <col style="width: 10%;">
            </colgroup>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Tanggal Perolehan</th>
                    <th>Serial Number</th>
                    <th>Merk</th>
                    <th>Prosesor</th>
                    <th>VGA</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><?= htmlspecialchars($row['tgl_beli']) ?></td>
                    <td><?= htmlspecialchars($row['serial_number']) ?></td>
                    <td><?= htmlspecialchars($row['merk']) ?></td>
                    <td><?= htmlspecialchars($row['processor']) ?></td>
                    <td><?= htmlspecialchars($row['vga']) ?></td>
                    <?php
                    $status = $row['status'] ?? '-';
                    $class = '';

                    if ($status === 'digunakan') {
                        $class = 'bg-warning';
                    } elseif ($status === 'tersedia') {
                        $class = 'bg-primary text-white';
                    }
                    ?>
                    <td class="<?= $class ?> fs-6 fw-bold"><?= htmlspecialchars($status) ?></td>
                    <td>
                        <a class='btn btn-secondary btn-sm' href='detail.php?id=<?= $row['id'] ?>'><i class="bi bi-eye-fill"></i></a>
                        <a class='btn btn-warning btn-sm' href= "edit.php?id=<?= $row['id'] ?>"><i class="bi bi-feather"></i></a>
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
        <div class="pagination-container">
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
        </div>
    </section>
    
    </main>
    </div>
    


<!-- Bootstrap 5 -->
<script src="../../assets/bootstrap-5.3.6-dist/js/bootstrap.min.js"></script>

<!-- popperjs Bootstrap 5 -->
<script src="../../assets/js/popper.min.js"></script>

<!-- AdminLTE -->
<script src="../../assets/adminlte/js/adminlte.js"></script>

<!-- OverlayScrollbars -->
<script src="../../assets/js/overlayscrollbars.browser.es6.min.js"></script>

<script src="../../assets/js/datatables.min.js"></script>

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
