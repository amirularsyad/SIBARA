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
<?php
include '../koneksi.php';

if (isset($_GET['tanggal'])) {
    $tanggal = $_GET['tanggal']; // format Y-m-d
    $bulan = date('m', strtotime($tanggal));
    $tahun = date('Y', strtotime($tanggal));

    $stmt = $koneksi->prepare("SELECT COUNT(*) AS total FROM berita_acara_pengembalian WHERE MONTH(tanggal) = ? AND YEAR(tanggal) = ?");
    $stmt->bind_param('ii', $bulan, $tahun);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    $nextNomor = $result['total'] + 1;
    echo str_pad($nextNomor, 3, '0', STR_PAD_LEFT); // contoh hasil: 001, 002, dst
}
?>
