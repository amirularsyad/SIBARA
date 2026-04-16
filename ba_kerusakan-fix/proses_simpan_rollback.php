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

include '../koneksi.php';

// Ambil data dari form
$nomor_ba            = $_POST['nomor_ba'];
$tanggal             = $_POST['tanggal'];
$jenis_perangkat     = $_POST['jenis_perangkat'];
$merek               = $_POST['merek'];
$pt                  = $_POST['pt'];
$lokasi_input        = $_POST['lokasi'];
$user_form           = $_POST['user'];
$tahun_perolehan     = $_POST['tahun_perolehan'];
$deskripsi           = $_POST['deskripsi'];
$sn                  = $_POST['sn'];
$penyebab_kerusakan  = $_POST['penyebab_kerusakan'];
$rekomendasi_mis     = $_POST['rekomendasi_mis'];
$atasan_peminjam     = isset($_POST['atasan_peminjam']) ? trim($_POST['atasan_peminjam']) : '';

// Ambil nama pembuat dari session
// $nama_pembuat = $_SESSION['nama'] ?? '';
$nama_pembuat = isset($_SESSION['nama']) ? $_SESSION['nama'] : '';


// Set nama approver berdasarkan lokasi PT
if ($pt === 'PT.MSAL (HO)') {
    $nama_aprv1 = 'Rizki Sunandar';
    $nama_aprv2 = 'Tedy Paronto';
} else {
    $nama_aprv1 = '';
    $nama_aprv2 = '';
}

// Set nilai approval
$approval_1 = 1; // langsung true
$approval_2 = 0; // default false

// Format lokasi
if (preg_match('/^LT\.(\d+)/i', $lokasi_input, $match)) {
    $lokasi = 'Lantai ' . $match[1];
} else {
    $lokasi = $lokasi_input;
}

// Simpan ke database
$sql = "INSERT INTO berita_acara_kerusakan 
(nomor_ba, tanggal, jenis_perangkat, merek, pt, lokasi, user, deskripsi, sn, tahun_perolehan, penyebab_kerusakan, rekomendasi_mis, atasan_peminjam, nama_pembuat, nama_aprv1, nama_aprv2, approval_1, approval_2) 
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $koneksi->prepare($sql);
$stmt->bind_param("ssssssssssssssssii", 
    $nomor_ba, $tanggal, $jenis_perangkat, $merek, $pt, $lokasi, $user_form, $deskripsi, 
    $sn, $tahun_perolehan, $penyebab_kerusakan, $rekomendasi_mis, $atasan_peminjam, 
    $nama_pembuat, $nama_aprv1, $nama_aprv2, $approval_1, $approval_2
);

if ($stmt->execute()) {
    $ba_kerusakan_id = $stmt->insert_id;

    // Proses Upload Gambar
    $upload_dir = '../assets/database-gambar/';
    foreach ($_FILES['gambar']['tmp_name'] as $key => $tmp_name) {
        $filename = basename($_FILES['gambar']['name'][$key]);
        $target_path = $upload_dir . time() . '_' . $filename;

        if (move_uploaded_file($tmp_name, $target_path)) {
            $stmt_img = $koneksi->prepare("INSERT INTO gambar_ba_kerusakan (ba_kerusakan_id, file_path) VALUES (?, ?)");
            $stmt_img->bind_param("is", $ba_kerusakan_id, $target_path);
            $stmt_img->execute();
            $stmt_img->close();
        }
    }
    $stmt->close();
    $koneksi->close();

    header("Location: ba_kerusakan.php?status=sukses");
    $_SESSION['message'] = "Data berhasil disimpan ke database.";
    exit();
} else {
    // echo "Gagal menyimpan data: " . $stmt->error;
    $_SESSION['message'] = "Gagal menyimpan data.";
    header("Location: ba_kerusakan.php?status=gagal");
    exit();
}

// $stmt->close();
// $koneksi->close();
?>
