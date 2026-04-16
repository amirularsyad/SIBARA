<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['nama'])) {
    die("Akses ditolak!");
}

$namaLogin = $_SESSION['nama'];
$warna = isset($_POST['warna_menu']) ? $_POST['warna_menu'] : 0;

// Jika default, simpan 0
if ($warna == "0") {
    $warnaFinal = "0";
} else {
    // Tambahkan "#" di depan jika belum ada
    if (strpos($warna, "#") !== 0) {
        $warnaFinal = "#" . $warna;
    } else {
        $warnaFinal = $warna;
    }
}

// Update akun_akses
$sqlUpdate = "UPDATE akun_akses SET warna_menu = ? WHERE nama = ?";
if ($stmt = $koneksi->prepare($sqlUpdate)) {
    $stmt->bind_param("ss", $warnaFinal, $namaLogin);
    if ($stmt->execute()) {
        // Kembali ke halaman asal (fallback ke index.php kalau referer kosong)
        $redirect = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'index.php';
        header("Location: " . $redirect . "?pesan=warna_disimpan");
        exit;
    } else {
        echo "Gagal menyimpan: " . $stmt->error;
    }
    $stmt->close();
}

$koneksi->close();
?>