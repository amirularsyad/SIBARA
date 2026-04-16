<?php
session_start();
require_once 'koneksi.php';

// Ambil token dan id surat dari URL
if (!isset($_GET['token']) || !isset($_GET['id'])) {
    die("Akses tidak valid.");
}

$token = $_GET['token'];
$id_surat = intval($_GET['id']);
$jenis_ba = isset($_GET['jenis_ba']) ? trim($_GET['jenis_ba']) : '';
$permintaan = isset($_GET['permintaan']) ? trim($_GET['permintaan']) : '';
$namaPeminta = isset($_GET['nama_peminta']) ? trim($_GET['nama_peminta']) : '';

// echo "<pre>";
// print_r($token);
// echo "</pre>";
// exit;

if (empty($token) || empty($id_surat) || empty($jenis_ba)) {
    die("Parameter tidak lengkap.");
}

// Cek token
$stmt = $koneksi->prepare("SELECT username, expire_at, used FROM login_tokens WHERE token = ?");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Token tidak valids.");
}

$row = $result->fetch_assoc();


// if (strtotime($row['expire_at']) < time()) {
//     die("Link ini sudah kedaluwarsa.");
// }

$username = $row['username'];

// Ambil data lengkap user dari akun_akses
$stmtUser = $koneksi->prepare("SELECT * FROM akun_akses WHERE username = ?");
$stmtUser->bind_param("s", $username);
$stmtUser->execute();
$resultUser = $stmtUser->get_result();

if ($resultUser->num_rows === 0) {
    die("Akun tidak ditemukan.");
}

$user = $resultUser->fetch_assoc();

// Set session persis seperti login biasa
    $_SESSION['logged_in'] = true;
    $_SESSION['username'] = $user['username'];
    $_SESSION['nama'] = $user['nama'];
    $_SESSION['hak_akses'] = $user['hak_akses'];
    $_SESSION['nik'] = $user['nik'];
    $ptList = array_map('trim', explode(',', $user['pt']));
    $_SESSION['pt'] = $ptList;
    $_SESSION['manajemen_akun_akses'] = $user['manajemen_akun_akses'];
    $_SESSION['warna_menu'] = $user['warna_menu'];

// Tandai token sudah digunakan
$update = $koneksi->prepare("UPDATE login_tokens SET used = 1 WHERE token = ?");
$update->bind_param("s", $token);
$update->execute();

// Redirect ke halaman detail surat berdasarkan jenis permintaan
if ($permintaan === 'approval') {

    // === KONDISI ASLI REDIRECT APPROVAL ===
    if ($jenis_ba === 'kerusakan') {
        $redirect_page = "personal/email_access_approval.php?jenis={$jenis_ba}&id={$id_surat}";
    } elseif ($jenis_ba === 'mutasi') {
        $redirect_page = "personal/email_access_approval.php?jenis={$jenis_ba}&id={$id_surat}";
    } elseif ($jenis_ba === 'st_asset') {
        $redirect_page = "personal/email_access_approval.php?jenis={$jenis_ba}&id={$id_surat}";
    } elseif ($jenis_ba === 'pemutihan') {
        $redirect_page = "personal/email_access_approval.php?jenis={$jenis_ba}&id={$id_surat}";
    } elseif ($jenis_ba === 'pengembalian') {
        $redirect_page = "personal/email_access_approval.php?jenis={$jenis_ba}&id={$id_surat}";
    } else {
        die("Jenis BA tidak dikenal.");
    }

} elseif ($permintaan === 'edit') {

    // === TEMPLATE KONDISI UNTUK EDIT ===
    if ($jenis_ba === 'kerusakan') {
        $redirect_page = "personal/email_access_edit.php?jenis={$jenis_ba}&id={$id_surat}&nama_peminta={$namaPeminta}";
    } elseif ($jenis_ba === 'mutasi') {
        $redirect_page = "personal/email_access_edit.php?jenis={$jenis_ba}&id={$id_surat}&nama_peminta={$namaPeminta}";
    } elseif ($jenis_ba === 'st_asset') {
        $redirect_page = "personal/email_access_edit.php?jenis={$jenis_ba}&id={$id_surat}&nama_peminta={$namaPeminta}";
    } elseif ($jenis_ba === 'pemutihan') {
        $redirect_page = "personal/email_access_edit.php?jenis={$jenis_ba}&id={$id_surat}&nama_peminta={$namaPeminta}";
    } elseif ($jenis_ba === 'pengembalian') {
        $redirect_page = "personal/email_access_edit.php?jenis={$jenis_ba}&id={$id_surat}&nama_peminta={$namaPeminta}";
    } else {
        die("Jenis BA tidak dikenal.");
    }
} elseif ($permintaan === 'delete') {

    // === TEMPLATE KONDISI UNTUK DELETE ===
    if ($jenis_ba === 'kerusakan') {
        $redirect_page = "personal/email_access_delete.php?jenis={$jenis_ba}&id={$id_surat}&nama_peminta={$namaPeminta}";
    } elseif ($jenis_ba === 'mutasi') {
        $redirect_page = "personal/email_access_delete.php?jenis={$jenis_ba}&id={$id_surat}&nama_peminta={$namaPeminta}";
    } elseif ($jenis_ba === 'st_asset') {
        $redirect_page = "personal/email_access_delete.php?jenis={$jenis_ba}&id={$id_surat}&nama_peminta={$namaPeminta}";
    } elseif ($jenis_ba === 'pemutihan') {
        $redirect_page = "personal/email_access_delete.php?jenis={$jenis_ba}&id={$id_surat}&nama_peminta={$namaPeminta}";
    } elseif ($jenis_ba === 'pengembalian') {
        $redirect_page = "personal/email_access_delete.php?jenis={$jenis_ba}&id={$id_surat}&nama_peminta={$namaPeminta}";
    } else {
        die("Jenis BA tidak dikenal.");
    }
} else {
    die("Permintaan tidak dikenal.");
}

header("Location: {$redirect_page}");
exit;
?>
