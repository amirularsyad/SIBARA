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
include '../koneksi.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id']);
    $sn_lama = $_POST['sn_lama'];
    $tanggal = $_POST['tanggal'];
    $nomor_ba = str_pad($_POST['nomor_ba'], 3, '0', STR_PAD_LEFT);
    $nama_peminjam = $_POST['nama_peminjam'];
    $alamat_peminjam = isset($_POST['alamat'][0]) ? $_POST['alamat'][0] : '';
    $sn_baru = $_POST['sn'];
    $saksi = $_POST['saksi'];

    // Update data berita acara
    $stmt = $koneksi->prepare("UPDATE ba_serah_terima_notebook SET 
        tanggal = ?, 
        nomor_ba = ?, 
        nama_peminjam = ?, 
        alamat_peminjam = ?, 
        sn = ?, 
        saksi = ?
        WHERE id = ?");
    
    $stmt->bind_param("ssssssi", 
        $tanggal, 
        $nomor_ba, 
        $nama_peminjam, 
        $alamat_peminjam, 
        $sn_baru, 
        $saksi, 
        $id
    );

    $success = $stmt->execute();

    // Update status barang jika SN berubah
    if ($sn_lama !== $sn_baru) {
        // Set SN lama jadi "tersedia"
        $koneksi->query("UPDATE barang_notebook_laptop SET status = 'tersedia' WHERE serial_number = '$sn_lama'");
        // Set SN baru jadi "digunakan"
        $koneksi->query("UPDATE barang_notebook_laptop SET status = 'digunakan' WHERE serial_number = '$sn_baru'");
    }

    if ($success) {
        header("Location: form_edit.php?id=$id&status=sukses");
        exit;
    } else {
        echo "Terjadi kesalahan saat menyimpan data: " . $stmt->error;
    }
} else {
    echo "Akses tidak valid.";
}
?>
