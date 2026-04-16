<?php
include "../../koneksi.php";

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    $stmt = $koneksi->prepare("UPDATE akun_akses SET deleted = 1 WHERE id = ? AND deleted = 0");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        header("Location: tabel.php?deleted=1");
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