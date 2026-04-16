<?php
include "../../koneksi.php"; // sesuaikan path

// $pt = $_POST['pt'] ?? '';
$pt = isset($_POST['pt']) ? $_POST['pt'] : '';

$options = '<option value="">-- Pilih Peran --</option>';

if (!empty($pt)) {
    // cek apakah sudah ada admin
    $stmt = $koneksi->prepare("SELECT COUNT(*) as jml FROM akun_akses WHERE pt = ? AND hak_akses = 'Admin'");
    $stmt->bind_param("s", $pt);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($result['jml'] == 0) {
        $options .= '<option value="Admin">Admin</option>';
    }
    $options .= '<option value="User">User</option>';
}

echo $options;
