<?php
session_start();
include '../koneksi.php';

// Pastikan login
if (!isset($_SESSION['nama'])) {
    header("Location: ../login_registrasi.php");
    exit;
}

// Cek parameter wajib
if (!isset($_GET['id'], $_GET['jenis'], $_GET['field'])) {
    $_SESSION['message'] = 'Parameter tidak lengkap.';
    header("Location: approval.php");
    exit;
}

$id = intval($_GET['id']);
$jenis = $_GET['jenis'] === 'pengembalian' ? 'pengembalian' : 'kerusakan';
$field = intval($_GET['field']);
if ($field < 1 || $field > 3) {
    $_SESSION['message'] = 'Field approval tidak valid.';
    header("Location: approval.php");
    exit;
}

// Tentukan mapping kolom
$approval_col = "approval_" . $field;
if ($jenis === 'kerusakan') {
    $table = 'berita_acara_kerusakan';
    $name_col_1 = 'nama_aprv1';
    $name_col_2 = 'nama_aprv2';
    $name_col_3 = 'nama_aprv3';
} else {
    $table = 'berita_acara_pengembalian';
    $name_col_1 = 'nama_pengembali';
    $name_col_2 = 'nama_penerima';
    $name_col_3 = 'diketahui';
}

// Ambil data dengan penyesuaian khusus pengembalian field 3
if ($jenis === 'pengembalian' && $field === 3) {
    $stmt = $koneksi->prepare("
        SELECT $approval_col,
                $name_col_1,
                $name_col_2,
                SUBSTRING_INDEX($name_col_3, ' - ', 1) AS $name_col_3
        FROM $table
        WHERE id = ?
    ");
} else {
    $stmt = $koneksi->prepare("
        SELECT $approval_col,
                $name_col_1,
                $name_col_2,
                $name_col_3
        FROM $table
        WHERE id = ?
    ");
}

$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
if (!$res || $res->num_rows === 0) {
    $_SESSION['message'] = 'Data tidak ditemukan.';
    header("Location: approval.php");
    exit;
}
$row = $res->fetch_assoc();

// Data approval & nama
// $current = (int)($row[$approval_col] ?? 0);
$current = isset($row[$approval_col]) ? (int)$row[$approval_col] : 0;

// $name1 = $row[$name_col_1] ?? '';
// $name2 = $row[$name_col_2] ?? '';
// $name3 = $row[$name_col_3] ?? '';
$name1 = isset($row[$name_col_1]) ? $row[$name_col_1] : '';
$name2 = isset($row[$name_col_2]) ? $row[$name_col_2] : '';
$name3 = isset($row[$name_col_3]) ? $row[$name_col_3] : '';


// Periksa otorisasi
$namaUser = $_SESSION['nama'];
$allowed = false;
if ($field === 1 && $name1 === $namaUser && $current === 0) $allowed = true;
if ($field === 2 && $name2 === $namaUser && $current === 0) $allowed = true;
if ($field === 3 && $name3 === $namaUser && $current === 0) $allowed = true;

if (!$allowed) {
    $_SESSION['message'] = 'Akses ditolak atau approval sudah dilakukan.';
    header("Location: approval.php");
    exit;
}

// Set approval ke 1 (approved)
$new = 1;
$upd = $koneksi->prepare("UPDATE $table SET $approval_col = ? WHERE id = ?");
$upd->bind_param("ii", $new, $id);
if ($upd->execute()) {
    $_SESSION['message'] = 'Approval berhasil.';
} else {
    $_SESSION['message'] = 'Gagal mengupdate approval: ' . $koneksi->error;
}

header("Location: approval.php");
exit;
