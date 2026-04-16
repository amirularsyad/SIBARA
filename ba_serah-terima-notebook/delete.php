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
?>
<?php
require_once '../koneksi.php';

if (!isset($_GET['id'])) {
    echo "ID tidak ditemukan.";
    exit;
}

$id = intval($_GET['id']);

// Cek apakah data dengan ID tersebut ada
$cek = $koneksi->query("SELECT * FROM ba_serah_terima_notebook WHERE id = $id");
if ($cek->num_rows === 0) {
    echo "Data tidak ditemukan.";
    exit;
}

// Ambil SN dari data yang akan dihapus
$data = $cek->fetch_assoc();
$sn = $koneksi->real_escape_string($data['sn']);

// Update status di tabel barang_notebook_laptop menjadi 'tersedia' berdasarkan SN
$update_status = $koneksi->query("UPDATE barang_notebook_laptop SET status = 'tersedia' WHERE serial_number = '$sn'");
if (!$update_status) {
    echo "Gagal memperbarui status barang: " . $koneksi->error;
    exit;
}

// Hapus data dari ba_serah_terima_notebook
$sql = "DELETE FROM ba_serah_terima_notebook WHERE id = $id";
if ($koneksi->query($sql)) {
    header("Location: ba_serah-terima-notebook.php?status=sukses_hapus");
    exit;
} else {
    echo "Gagal menghapus data: " . $koneksi->error;
}
?>