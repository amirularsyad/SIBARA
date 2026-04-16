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
// Koneksi database
$koneksi = new mysqli("localhost", "root", "", "db_surat_ba");
if ($koneksi->connect_error) {
    die("Koneksi gagal: " . $koneksi->connect_error);
}

// Ambil data dari form
$tanggal            = $_POST['tanggal'];
$nomor_ba           = $_POST['nomor_ba'];   
$lokasi_pengembali  = $_POST['lokasi_pengembali'];
$nama_pengembali    = $_POST['nama_pengembali'];
$lokasi_penerima    = $_POST['lokasi_penerima'];
$nama_penerima      = $_POST['nama_penerima'];
$atasan_pengembali  = $_POST['atasan_pengembali'];
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

// Set default nilai approval
$approval_1 = 0;
$approval_2 = 0;
$approval_3 = 0;

// Simpan ke tabel utama
$sql = "INSERT INTO berita_acara_pengembalian 
(tanggal, nomor_ba, lokasi_pengembali, nama_pengembali, lokasi_penerima, nama_penerima, atasan_pengembali, atasan_penerima, diketahui, approval_1, approval_2, approval_3, nama_pembuat) 
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $koneksi->prepare($sql);
$stmt->bind_param("sssssssssiiis", $tanggal, $nomor_ba, $lokasi_pengembali, $nama_pengembali, $lokasi_penerima, $nama_penerima, $atasan_pengembali, $atasan_penerima, $diketahui, $approval_1, $approval_2, $approval_3, $nama_pembuat);

if ($stmt->execute()) {
    $ba_pengembalian_id = $stmt->insert_id;

    // =========================
    // Simpan Data Barang
    // =========================
    if (!empty($_POST['jenis_barang']) && is_array($_POST['jenis_barang'])) {
        foreach ($_POST['jenis_barang'] as $index => $jenis_barang) {
            $jumlah     = $_POST['jumlah'][$index];
            $kondisi    = $_POST['kondisi'][$index];
            $keterangan = $_POST['keterangan'][$index];

            $stmt_barang = $koneksi->prepare("INSERT INTO barang_pengembalian (ba_pengembalian_id, jenis_barang, jumlah, kondisi, keterangan) VALUES (?, ?, ?, ?, ?)");
            $stmt_barang->bind_param("isiss", $ba_pengembalian_id, $jenis_barang, $jumlah, $kondisi, $keterangan);
            $stmt_barang->execute();
            $stmt_barang->close();
        }
    }

    // =========================
    // Simpan Gambar
    // =========================
    $upload_dir = '../assets/database-gambar/';
    foreach ($_FILES['gambar']['tmp_name'] as $key => $tmp_name) {
        if (!empty($tmp_name)) {
            $filename = basename($_FILES['gambar']['name'][$key]);
            $target_path = $upload_dir . time() . '_' . $filename;

            if (move_uploaded_file($tmp_name, $target_path)) {
                $stmt_img = $koneksi->prepare("INSERT INTO gambar_ba_pengembalian (ba_pengembalian_id, file_path) VALUES (?, ?)");
                $stmt_img->bind_param("is", $ba_pengembalian_id, $target_path);
                $stmt_img->execute();
                $stmt_img->close();
            }
        }
    }

    // Redirect ke form dengan notifikasi sukses
    header("Location: ba_pengembalian.php?status=sukses");
    exit();

} else {
    echo "Gagal menyimpan data: " . $stmt->error;
}

$stmt->close();
$koneksi->close();
?>
