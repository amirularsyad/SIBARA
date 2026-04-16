<?php
include "../../koneksi.php"; // sesuaikan path koneksi.php sesuai struktur foldermu

// Ambil ID dari parameter
// $id = $_GET['id'] ?? '';
$id = isset($_GET['id']) ? $_GET['id'] : '';

if ($id) {
    // Siapkan query hapus
    $stmt = $koneksi->prepare("DELETE FROM akun_akses WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        // Jika sukses, kembali ke tabel
        header("Location: tabel.php?deleted=1"); // sesuaikan dengan nama file tabel utamamu
        exit;
    } else {
        echo "Gagal menghapus data: " . $koneksi->error;
    }

    $stmt->close();
} else {
    echo "ID tidak ditemukan.";
}

$koneksi->close();
?>
