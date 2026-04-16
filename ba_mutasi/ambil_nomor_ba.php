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

$pt_filter = $_SESSION['pt'];
if (is_array($pt_filter)) {
    $pt_filter = reset($pt_filter);
}
$pt_filter = trim($pt_filter);



if (isset($_GET['tanggal'])) {
    $tanggal = $_GET['tanggal']; // format Y-m-d
    $bulan = date('m', strtotime($tanggal));
    $tahun = date('Y', strtotime($tanggal));

    // $stmt = $koneksi->prepare("SELECT COUNT(*) AS total FROM berita_acara_mutasi WHERE MONTH(tanggal) = ? AND YEAR(tanggal) = ? AND pt_asal = ?");
    $stmt = $koneksi->prepare("SELECT MAX(CAST(nomor_ba AS UNSIGNED)) AS max_nomor FROM berita_acara_mutasi WHERE MONTH(tanggal) = ? AND YEAR(tanggal) = ? AND pt_asal = ? AND dihapus = 0");
    $stmt->bind_param('iis', $bulan, $tahun, $pt_filter);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    // $nextNomor = $result['total'] + 1;
    if ($result && $result['max_nomor'] !== null) {
        $nextNomor = $result['max_nomor'] + 1;
    } else {
        $nextNomor = 1;
    }
    echo str_pad($nextNomor, 3, '0', STR_PAD_LEFT); // contoh hasil: 001, 002, dst
}

