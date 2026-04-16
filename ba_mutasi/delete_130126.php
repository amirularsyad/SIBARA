<?php
session_start();
require_once '../koneksi.php'; // pastikan koneksi mysqli tersambung sebagai $koneksi
$ids = $_GET['id'];
$cek = $koneksi->query("SELECT pembuat, nomor_ba, tanggal FROM berita_acara_mutasi WHERE id = $ids");
if ($cek->num_rows === 0) {
    echo "Data tidak ditemukan.";
    exit;
}
$data = $cek->fetch_assoc();
$nama_pembuat   = $data['pembuat'];
$nomor_ba       = $data['nomor_ba'];
$tanggal        = $data['tanggal'];
$nama_sesi      = $_SESSION['nama'];
$hak_akses      = $_SESSION['hak_akses'];

$bulan_angka = date('m', strtotime($tanggal));
$tahun = date( 'Y', strtotime($tanggal));
$bulan_romawi_map = [
    '01' => 'I',
    '02' => 'II',
    '03' => 'III',
    '04' => 'IV',
    '05' => 'V',
    '06' => 'VI',
    '07' => 'VII',
    '08' => 'VIII',
    '09' => 'IX',
    '10' => 'X',
    '11' => 'XI',
    '12' => 'XII'
];

$bulan_romawi = $bulan_romawi_map[$bulan_angka];

if ($nama_sesi !== $nama_pembuat && $hak_akses !== 'Super Admin') {
    $redirect_url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'ba_mutasi.php';
    $_SESSION['message'] = "Anda tidak memiliki akses untuk menghapus data $nomor_ba/BAMTA/MIS/$bulan_romawi/$tahun.";
    header("Location: " . $redirect_url);
    exit;
}

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    try {
        // === 1. Ambil daftar gambar terkait ===
        $stmtGambar = $koneksi->prepare("SELECT file_path FROM gambar_ba_mutasi WHERE id_ba = ?");
        $stmtGambar->bind_param("i", $id);
        $stmtGambar->execute();
        $resultGambar = $stmtGambar->get_result();

        // === 2. Hapus file fisik sesuai path dari database ===
        while ($row = $resultGambar->fetch_assoc()) {
            $filePath = $row['file_path'];
            if (!empty($filePath) && file_exists($filePath)) {
                unlink($filePath);
            }
        }
        $stmtGambar->close();

        // === 3. Hapus semua data di historikal_edit_ba ===
        $stmtHist1 = $koneksi->prepare("
            DELETE FROM historikal_edit_ba
            WHERE id_ba = ?
        ");
        $stmtHist1->bind_param("i", $id);
        $stmtHist1->execute();
        $stmtHist1->close();

        // === 4. Hapus semua data di history_n_temp_barang_mutasi ===
        $stmtHist2 = $koneksi->prepare("
            DELETE FROM history_n_temp_barang_mutasi
            WHERE id_ba = ?
        ");
        $stmtHist2->bind_param("i", $id);
        $stmtHist2->execute();
        $stmtHist2->close();

        // === 5. Hapus semua data di history_n_temp_ba_mutasi ===
        $stmtHist3 = $koneksi->prepare("
            DELETE FROM history_n_temp_ba_mutasi
            WHERE id_ba = ?
        ");
        $stmtHist3->bind_param("i", $id);
        $stmtHist3->execute();
        $stmtHist3->close();

        // === 6. Hapus data utama BA (ON DELETE CASCADE akan hapus relasi lain) ===
        $stmtDelete = $koneksi->prepare("
            DELETE FROM berita_acara_mutasi
            WHERE id = ?
        ");
        $stmtDelete->bind_param("i", $id);

        if ($stmtDelete->execute()) {
            $_SESSION['message'] = "Data berita acara berhasil dihapus.";
            header("Location: ba_mutasi.php?status=sukses");
            exit();
        } else {
            $_SESSION['message'] = "Gagal menghapus data: " . $stmtDelete->error;
            header("Location: ba_mutasi.php?status=gagal");
            exit();
        }

    } catch (Exception $e) {
        $_SESSION['message'] = "Terjadi kesalahan: " . $e->getMessage();
        header("Location: ba_mutasi.php?status=gagal");
        exit();
    }

} else {
    $_SESSION['message'] = "ID tidak ditemukan.";
    header("Location: ba_mutasi.php?status=gagal");
    exit();
}
?>
