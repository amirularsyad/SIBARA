<?php
include "../../koneksi.php"; // sesuaikan path koneksinya

// Ambil parameter
// $id   = $_GET['id'] ?? '';
// $role = $_GET['role'] ?? '';
$id   = isset($_GET['id']) ? $_GET['id'] : '';
$role = isset($_GET['role']) ? $_GET['role'] : '';


if ($id && ($role === 'Admin' || $role === 'User')) {
    // Update hak_akses
    $stmt = $koneksi->prepare("UPDATE akun_akses SET hak_akses = ? WHERE id = ?");
    $stmt->bind_param("si", $role, $id);

    if ($stmt->execute()) {
        // Sukses
        header("Location: tabel.php?success=1"); // ganti tabel.php sesuai nama file utama
        exit;
    } else {
        echo "Gagal update data: " . $koneksi->error;
    }

    $stmt->close();
} else {
    echo "Parameter tidak valid.";
}

$koneksi->close();
?>
