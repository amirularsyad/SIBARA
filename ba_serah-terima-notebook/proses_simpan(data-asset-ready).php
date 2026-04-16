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

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Ambil dan filter data dari form
    $tanggal           = $_POST['tanggal'] ?? '';
    $nomor_ba          = str_pad($_POST['nomor_ba'] ?? '', 3, '0', STR_PAD_LEFT);
    $nama_peminjam     = $_POST['nama_peminjam'] ?? '';
    $alamat_array = $_POST['alamat'] ?? [];
    $alamat_peminjam = is_array($alamat_array) ? implode(", ", $alamat_array) : $alamat_array;
    $sn                = $_POST['sn'] ?? '';
    $saksi             = $_POST['saksi'] ?? '';

    // Validasi wajib isi
    // if (empty($tanggal) || empty($nomor_ba) || empty($nama_peminjam) || empty($alamat_peminjam) || empty($sn) || empty($saksi)) {
    //     die("Data wajib diisi tidak lengkap.");
    // }

    // Siapkan dan jalankan prepared statement
    $stmt = $koneksi->prepare("INSERT INTO ba_serah_terima_notebook 
        (tanggal, nomor_ba, nama_peminjam, alamat_peminjam, sn, saksi) 
        VALUES (?, ?, ?, ?, ?, ?)");

    if ($stmt) {
        $stmt->bind_param(
            "ssssss",
            $tanggal,
            $nomor_ba,
            $nama_peminjam,
            $alamat_peminjam,
            $sn,
            $saksi
        );

        if ($stmt->execute()) {
            // Update status SN jadi digunakan berdasarkan serial_number
            $stmt_status = $koneksi->prepare("UPDATE barang_notebook_laptop SET status = 'digunakan' WHERE serial_number = ?");
            $stmt_status->bind_param("s", $sn);
            $stmt_status->execute();
            // Redirect ke form dengan notifikasi sukses
            header("Location: form_input.php?status=sukses");
            exit();
        } else {
            echo "Gagal menyimpan data: " . $stmt->error;
        }
        $stmt->close();
    } else {
        echo "Query error: " . $koneksi->error;
    }
} else {
    echo "Akses tidak valid.";
}
?>
