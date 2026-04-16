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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "Invalid request method.";
    exit;
}

$id = intval($_POST['id']);
$tanggal            = $_POST['tanggal'];
$lokasi_pengembali  = $_POST['lokasi_pengembali'];
$nama_pengembali    = $_POST['nama_pengembali'];
$atasan_pengembali  = $_POST['atasan_pengembali'];
$lokasi_penerima    = $_POST['lokasi_penerima'];
$nama_penerima      = $_POST['nama_penerima'];
$atasan_penerima    = $_POST['atasan_penerima'];
$nama_pembuat       = $_SESSION['nama'];

// Penentuan field "diketahui"
$diketahui = '';
if ($lokasi_penerima === 'PT.MSAL (HO)') {
    if ($nama_pengembali === 'Tedy Paronto') {
        $query_diecy = $koneksi->prepare("SELECT jabatan, departemen FROM data_karyawan WHERE nama = ? LIMIT 1");
        $nama_diecy = 'M. Diecy Firmansyah';
        $query_diecy->bind_param("s", $nama_diecy);
        $query_diecy->execute();
        $result_diecy = $query_diecy->get_result();
        if ($row = $result_diecy->fetch_assoc()) {
            $diketahui = $nama_diecy . ' - ' . $row['jabatan'] . ' (' . $row['departemen'] . ')';
        }
        $query_diecy->close();
    } else {
        $query_tedy = $koneksi->prepare("SELECT jabatan, departemen FROM data_karyawan WHERE nama = ? LIMIT 1");
        $nama_tedy = 'Tedy Paronto';
        $query_tedy->bind_param("s", $nama_tedy);
        $query_tedy->execute();
        $result_tedy = $query_tedy->get_result();
        if ($row = $result_tedy->fetch_assoc()) {
            $diketahui = $nama_tedy . ' - ' . $row['jabatan'] . ' (' . $row['departemen'] . ')';
        }
        $query_tedy->close();
    }
}

$update = $koneksi->prepare("UPDATE berita_acara_pengembalian SET 
    tanggal = ?, 
    lokasi_pengembali = ?, nama_pengembali = ?, atasan_pengembali = ?, 
    lokasi_penerima = ?, nama_penerima = ?, atasan_penerima = ?,
    diketahui = ?, nama_pembuat = ?
    WHERE id = ?");
$update->bind_param("sssssssssi", 
    $tanggal, 
    $lokasi_pengembali, $nama_pengembali, $atasan_pengembali, 
    $lokasi_penerima, $nama_penerima, $atasan_penerima, $diketahui, $nama_pembuat,
    $id
);

if (!$update->execute()) {
    die("Gagal mengupdate berita acara: " . $update->error);
}



// =======================
// Update Barang
// =======================
$koneksi->query("DELETE FROM barang_pengembalian WHERE ba_pengembalian_id = $id");

if (!empty($_POST['jenis_barang'])) {
    foreach ($_POST['jenis_barang'] as $i => $jenis_barang) {
        $jumlah     = $_POST['jumlah'][$i];
        $kondisi    = $_POST['kondisi'][$i];
        $keterangan = $_POST['keterangan'][$i];

        $stmt_barang = $koneksi->prepare("INSERT INTO barang_pengembalian 
            (ba_pengembalian_id, jenis_barang, jumlah, kondisi, keterangan) 
            VALUES (?, ?, ?, ?, ?)");
        $stmt_barang->bind_param("isiss", $id, $jenis_barang, $jumlah, $kondisi, $keterangan);
        $stmt_barang->execute();
        $stmt_barang->close();
    }
}


// =======================
// Kelola Gambar
// =======================
// Folder penyimpanan gambar
$upload_dir = '../assets/database-gambar/';

// Proses penghapusan gambar lama jika ditandai
if (isset($_POST['hapus_gambar'])) {
    foreach ($_POST['hapus_gambar'] as $key => $value) {
        if ($value === 'hapus') {
            $gambar_id = intval($_POST['gambar_lama_id'][$key]);
            $get_path = $koneksi->prepare("SELECT file_path FROM gambar_ba_pengembalian WHERE id = ?");
            $get_path->bind_param("i", $gambar_id);
            $get_path->execute();
            $res = $get_path->get_result();
            if ($row = $res->fetch_assoc()) {
                if (file_exists($row['file_path'])) {
                    unlink($row['file_path']);
                }
            }
            $del_stmt = $koneksi->prepare("DELETE FROM gambar_ba_pengembalian WHERE id = ?");
            $del_stmt->bind_param("i", $gambar_id);
            $del_stmt->execute();
            $del_stmt->close();
        }
    }
}

// Proses penggantian file gambar lama
if (!empty($_FILES['gambar_lama_file']['name'])) {
    foreach ($_FILES['gambar_lama_file']['name'] as $id_gambar => $filename) {
        if (!empty($filename)) {
            $tmp_name = $_FILES['gambar_lama_file']['tmp_name'][$id_gambar];
            $target_path = $upload_dir . time() . '_' . basename($filename);
            if (move_uploaded_file($tmp_name, $target_path)) {
                $update_stmt = $koneksi->prepare("UPDATE gambar_ba_pengembalian SET file_path = ?, uploaded_at = NOW() WHERE id = ?");
                $update_stmt->bind_param("si", $target_path, $id_gambar);
                $update_stmt->execute();
                $update_stmt->close();
            }
        }
    }
}

// Proses gambar baru
if (isset($_FILES['gambar_baru'])) {
    foreach ($_FILES['gambar_baru']['tmp_name'] as $key => $tmp_name) {
        if (!empty($tmp_name)) {
            $filename = basename($_FILES['gambar_baru']['name'][$key]);
            $target_path = $upload_dir . time() . '_' . $filename;

            if (move_uploaded_file($tmp_name, $target_path)) {
                $stmt_img = $koneksi->prepare("INSERT INTO gambar_ba_pengembalian (ba_pengembalian_id, file_path, uploaded_at) VALUES (?, ?, NOW())");
                $stmt_img->bind_param("is", $id, $target_path);
                $stmt_img->execute();
                $stmt_img->close();
            }
        }
    }
}

$update->close();

header("Location: form_edit_ba_pengembalian.php?id=$id&status=sukses");
exit;
?>