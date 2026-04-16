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

require_once '../koneksi.php';

if (!isset($_GET['id'])) {
    echo "ID tidak ditemukan.";
    exit;
}

$id = intval($_GET['id']);

// Ambil data lengkap dari berita acara
$cek = $koneksi->query("SELECT nama_pembuat, approval_1, approval_2 FROM berita_acara_kerusakan WHERE id = $id");
if ($cek->num_rows === 0) {
    echo "Data tidak ditemukan.";
    exit;
}

$data = $cek->fetch_assoc();
$nama_pembuat = $data['nama_pembuat'];
$approval_1   = $data['approval_1'];
$approval_2   = $data['approval_2'];
$nama_sesi    = $_SESSION['nama'];
$hak_akses    = $_SESSION['hak_akses'];

// Cek apakah bukan pembuat dan bukan Super Admin
if ($nama_sesi !== $nama_pembuat && $hak_akses !== 'Super Admin') {
    $redirect_url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'ba_kerusakan.php';
    header("Location: " . $redirect_url);
    exit;
}

// // Cek jika sudah disetujui oleh kedua pihak
// if ($approval_1 == 1 && $approval_2 == 1) {
//     $redirect_url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'ba_kerusakan.php';
//     header("Location: " . $redirect_url);
//     exit;
// }

// ===== HAPUS FILE GAMBAR TERKAIT (ROBUST) =====
$upload_dir = realpath(__DIR__ . '/../assets/database-gambar');
if ($upload_dir === false) {
    // fallback ke path relatif kalau realpath gagal
    $upload_dir = __DIR__ . '/../assets/database-gambar';
}

// ambil semua file_path terkait
$result_gambar = $koneksi->query("SELECT file_path FROM gambar_ba_kerusakan WHERE ba_kerusakan_id = $id");
$not_deleted = []; // simpan path yang tidak berhasil dihapus (untuk debugging)

if ($result_gambar && $result_gambar->num_rows > 0) {
    while ($row_gambar = $result_gambar->fetch_assoc()) {
        $stored = $row_gambar['file_path'];
        $deleted = false;

        // kandidat path yang akan dicoba (order penting)
        $candidates = [];

        // 1) jika DB menyimpan path absolut/relatif seperti "../assets/..." atau "/var/www/..."
        if (!empty($stored)) {
            $candidates[] = $stored;
        }

        // 2) coba sebagai nama file di folder upload_dir
        if (!empty($stored)) {
            $candidates[] = $upload_dir . DIRECTORY_SEPARATOR . basename($stored);
        }

        // 3) coba treat stored sebagai path relatif terhadap project root
        if (!empty($stored)) {
            $candidates[] = __DIR__ . '/../' . ltrim($stored, '/');
        }

        // cek kandidat satu per satu
        foreach ($candidates as $p) {
            // jika realpath ada, gunakan realpath (resolves symlink dan normalisasi)
            $try = realpath($p) ?: $p;

            if (file_exists($try)) {
                // coba hapus; gunakan @ untuk mencegah warning tampil ke user, kita log jika gagal
                if (@unlink($try)) {
                    $deleted = true;
                    break;
                } else {
                    // file ada tapi gagal di-unlink (permissions dll)
                    error_log("delete.php: Gagal unlink file (permission?) -> $try for ba_id=$id");
                    $not_deleted[] = $try;
                    $deleted = true; // tandai sudah ditemukan, walau gagal dihapus
                    break;
                }
            }
        }

        if (!$deleted) {
            // tidak menemukan file di kandidat manapun
            $not_deleted[] = $stored;
            error_log("delete.php: File tidak ditemukan di kandidat path -> $stored for ba_id=$id");
        }
    }

    // lalu hapus semua record gambar dari tabel (biar tidak orphan)
    $stmt_del_img = $koneksi->prepare("DELETE FROM gambar_ba_kerusakan WHERE ba_kerusakan_id = ?");
    if ($stmt_del_img) {
        $stmt_del_img->bind_param("i", $id);
        $stmt_del_img->execute();
        $stmt_del_img->close();
    } else {
        error_log("delete.php: Gagal prepare DELETE gambar_ba_kerusakan for ba_id=$id - error: " . $koneksi->error);
    }
}

// (opsional) log ringkasan bila ada kegagalan hapus file
if (!empty($not_deleted)) {
    // catat sedikit info ke error_log — tidak tampil ke user
    error_log("delete.php: Beberapa file terkait ba_id=$id tidak terhapus/tidak ditemukan: " . implode(', ', array_slice($not_deleted,0,10)));
}

// ===== HAPUS DATA HISTORIKAL EDIT BERDASARKAN ID DAN NAMA BA =====
$stmt_del_hist = $koneksi->prepare("DELETE FROM historikal_edit_ba WHERE id_ba = ? AND nama_ba = 'kerusakan'");
if ($stmt_del_hist) {
    $stmt_del_hist->bind_param("i", $id);
    $stmt_del_hist->execute();
    $stmt_del_hist->close();
} else {
    error_log("delete.php: Gagal prepare DELETE historikal_edit_ba for id_ba=$id - error: " . $koneksi->error);
}

// Lakukan penghapusan
$sql = "DELETE FROM berita_acara_kerusakan WHERE id = $id";

if ($koneksi->query($sql)) {
    // Redirect ke halaman utama setelah berhasil hapus
    header("Location: ba_kerusakan.php?status=sukses");
    $_SESSION['message'] = "Data berhasil dihapus dari database.";
    exit;
} else {
    // echo "Gagal menghapus data: " . $koneksi->error;
    $_SESSION['message'] = "Gagal menghapus data.";
    header("Location: ba_kerusakan.php?status=gagal");
}
?>