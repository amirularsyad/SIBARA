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

include '../koneksi.php';

$tanggal = isset($_GET['tanggal']) ? trim($_GET['tanggal']) : date('Y-m-d');
$pt = isset($_GET['pt']) ? trim($_GET['pt']) : '';

if ($pt === '' || !$tanggal) {
    echo '001';
    exit;
}

$bulan = date('m', strtotime($tanggal));
$tahun = date('Y', strtotime($tanggal));

$stmt = $koneksi->prepare("
    SELECT nomor_ba
    FROM berita_acara_kerusakan
    WHERE MONTH(tanggal) = ?
      AND YEAR(tanggal) = ?
      AND pt = ?
      AND dihapus = 0
    ORDER BY CAST(nomor_ba AS UNSIGNED) DESC
    LIMIT 1
");
$stmt->bind_param("sss", $bulan, $tahun, $pt);
$stmt->execute();

$result = $stmt->get_result();
$row = $result->fetch_assoc();

if ($row && is_numeric($row['nomor_ba'])) {
    echo str_pad(((int)$row['nomor_ba']) + 1, 3, '0', STR_PAD_LEFT);
} else {
    echo '001';
}
