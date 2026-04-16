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
$cek = $koneksi->query("SELECT * FROM berita_acara_pengembalian WHERE id = $id");
if ($cek->num_rows === 0) {
    echo "Data tidak ditemukan.";
    exit;
}

// Lakukan penghapusan
$sql = "DELETE FROM berita_acara_pengembalian WHERE id = $id";

if ($koneksi->query($sql)) {
    // Redirect ke halaman utama setelah berhasil hapus
    header("Location: ba_pengembalian.php?status=sukses_hapus");
    exit;
} else {
    echo "Gagal menghapus data: " . $koneksi->error;
}
?>