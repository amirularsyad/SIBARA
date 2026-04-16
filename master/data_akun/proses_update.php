<?php
session_start();
include "../../koneksi.php"; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id       = isset($_POST['id']) ? $_POST['id'] : '';
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';
    $email    = isset($_POST['email']) ? trim($_POST['email']) : '';
    $manajemen_akun_akses = isset($_POST['manajemen_akun_akses']) ? $_POST['manajemen_akun_akses'] : null;


    if ($id && $username && $password && $email && $manajemen_akun_akses !== null) {
        $stmtCheck = $koneksi->prepare("SELECT id FROM akun_akses WHERE username = ? AND id != ?");
        $stmtCheck->bind_param("si", $username, $id);
        $stmtCheck->execute();
        $resCheck = $stmtCheck->get_result();

        if ($resCheck->num_rows > 0) {
            echo "<script>alert('Username sudah digunakan, silakan pilih yang lain'); window.history.back();</script>";
            exit;
        }

        $signature_data = isset($_POST['signature_data_edit']) ? $_POST['signature_data_edit'] : '';

        if (!empty($signature_data)) {
            $signature_data = str_replace('data:image/png;base64,', '', $signature_data);
            $signature_data = base64_decode($signature_data);
        } else {
            // ambil autograph lama jika tidak ada perubahan
            $resultOld = $koneksi->query("SELECT autograph FROM akun_akses WHERE id = '$id'");
            $rowOld = $resultOld->fetch_assoc();
            $signature_data = $rowOld['autograph'];
        }

        $stmt = $koneksi->prepare("UPDATE akun_akses SET username = ?, password = ?, email = ?, manajemen_akun_akses = ?, autograph = ? WHERE id = ?");
        $stmt->bind_param("sssibi", $username, $password, $email, $manajemen_akun_akses, $signature_data, $id);
        $stmt->send_long_data(4, $signature_data);


        if ($stmt->execute()) {
            $_SESSION['message'] = 'Sukses Edit';
            $_SESSION['success'] = true;
            echo json_encode(["success" => true]);
            header("Location: tabel.php");
            exit;
        } else {
            $_SESSION['message'] = 'Gagal memperbarui data: ' . $koneksi->error;
            $_SESSION['success'] = false;
            echo json_encode(["success" => false]);
            header("Location: tabel.php");
            exit;
        }
    } else {
        echo "<script>alert('Data tidak lengkap'); window.history.back();</script>";
    }
}
