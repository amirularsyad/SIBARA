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

$id      = trim((int) $_POST['id']);

/* ================= CEK ID ================= */
if (!isset($id) || !is_numeric($id)) {
    $_SESSION['message'] = "ID data tidak valid.";
    header("Location: ba_kerusakan.php?status=gagal");
    exit;
}


$pending = isset($_POST['pending']) ? (int) $_POST['pending'] : 0;
$alasan_hapus = isset($_POST['alasan_hapus']) ? $_POST['alasan_hapus'] : NULL;
// echo '<pre>';
// print_r($_POST);
// echo '</pre>';
// exit;

/* ================= AMBIL DATA BA ================= */
$cek = $koneksi->query("
    SELECT nama_pembuat, approval_1, approval_2, pt
    FROM berita_acara_kerusakan
    WHERE id = $id
");

if (!$cek) {
    $_SESSION['message'] = "Gagal mengambil data BA Kerusakan.";
    header("Location: ba_kerusakan.php?status=gagal");
    exit;
}

if ($cek->num_rows === 0) {
    $_SESSION['message'] = "Data BA Kerusakan tidak ditemukan.";
    header("Location: ba_kerusakan.php?status=gagal");
    exit;
}

$data = $cek->fetch_assoc();
$nama_pembuat = $data['nama_pembuat'];
$approval_1   = $data['approval_1'];
$approval_2   = $data['approval_2'];
$pt_ba        = $data['pt'];

$nama_sesi = $_SESSION['nama'];
$hak_akses = $_SESSION['hak_akses'];

/* ================= VALIDASI PEMBUAT ================= */
if ($nama_sesi !== $nama_pembuat && $hak_akses !== 'Super Admin') {
    $_SESSION['message'] = "Anda bukan pembuat data ini atau tidak memiliki izin.";
    $redirect = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'ba_kerusakan.php';
    header("Location: $redirect");
    exit;
}

/* ================= TENTUKAN APPROVER PENDING HAPUS ================= */
$namaApprover = null;

if ($pending === 1) {

    if ($pt_ba === 'PT.MSAL (HO)') {

        $namaApprover = 'Tedy Paronto';

    } else {

        $stmt_ktu = $koneksi->prepare("
            SELECT nama 
            FROM data_karyawan_test
            WHERE pt = ?
              AND posisi = 'KTU'
            LIMIT 1
        ");

        if (!$stmt_ktu) {
            $_SESSION['message'] = "Gagal menyiapkan data approver.";
            header("Location: ba_kerusakan.php?status=gagal");
            exit;
        }

        $stmt_ktu->bind_param("s", $pt_ba);
        $stmt_ktu->execute();
        $result_ktu = $stmt_ktu->get_result();

        if ($result_ktu->num_rows === 0) {
            $_SESSION['message'] = "Approver tidak ditemukan.";
            header("Location: ba_kerusakan.php?status=gagal");
            exit;
        }

        $namaApprover = $result_ktu->fetch_assoc()['nama'];
        $stmt_ktu->close();
    }
}

/* ================= TRANSAKSI ================= */
$koneksi->begin_transaction();

try {

    /* ===== 1. UPDATE BA KERUSAKAN ===== */
    if ($pending === 1) {

        $stmt_ba = $koneksi->prepare("
            UPDATE berita_acara_kerusakan
            SET pending_hapus = 1,
                pending_hapus_approver = ?,
                alasan_hapus = ?
            WHERE id = ?
        ");

        if (!$stmt_ba) {
            throw new Exception("Gagal menyiapkan query pending delete BA Kerusakan.");
        }

        $stmt_ba->bind_param("ssi", $namaApprover, $alasan_hapus, $id);

    } else {

        $stmt_ba = $koneksi->prepare("
            UPDATE berita_acara_kerusakan
            SET dihapus = 1
            WHERE id = ?
        ");

        if (!$stmt_ba) {
            throw new Exception("Gagal menyiapkan query delete BA Kerusakan.");
        }

        $stmt_ba->bind_param("i", $id);
    }

    if (!$stmt_ba->execute()) {
        throw new Exception("Gagal memproses BA Kerusakan.");
    }

    $stmt_ba->close();

    /* ===== 2. UPDATE HISTORIKAL EDIT ===== */
    if ($pending === 1) {

        $stmt_hist = $koneksi->prepare("
            UPDATE historikal_edit_ba
            SET pending_hapus = 1,
                pending_hapus_approver = ?
            WHERE id_ba = ?
              AND nama_ba = 'kerusakan'
        ");

        if (!$stmt_hist) {
            throw new Exception("Gagal menyiapkan query pending delete historikal.");
        }

        $stmt_hist->bind_param("si", $namaApprover, $id);

    } else {

        $stmt_hist = $koneksi->prepare("
            UPDATE historikal_edit_ba
            SET dihapus = 1
            WHERE id_ba = ?
              AND nama_ba = 'kerusakan'
        ");

        if (!$stmt_hist) {
            throw new Exception("Gagal menyiapkan query delete historikal.");
        }

        $stmt_hist->bind_param("i", $id);
    }

    if (!$stmt_hist->execute()) {
        throw new Exception("Gagal memproses historikal edit.");
    }

    $stmt_hist->close();

    /* ===== 3. UPDATE HISTORY TEMP ===== */
    if ($pending === 1) {

        $stmt_temp = $koneksi->prepare("
            UPDATE history_n_temp_ba_kerusakan
            SET pending_hapus = 1,
                pending_hapus_approver = ?
            WHERE id_ba = ?
        ");

        if (!$stmt_temp) {
            throw new Exception("Gagal menyiapkan query pending delete history temp.");
        }

        $stmt_temp->bind_param("si", $namaApprover, $id);

    } else {

        $stmt_temp = $koneksi->prepare("
            UPDATE history_n_temp_ba_kerusakan
            SET dihapus = 1
            WHERE id_ba = ?
        ");

        if (!$stmt_temp) {
            throw new Exception("Gagal menyiapkan query delete history temp.");
        }

        $stmt_temp->bind_param("i", $id);
    }

    if (!$stmt_temp->execute()) {
        throw new Exception("Gagal memproses history temporary.");
    }

    $stmt_temp->close();

    /* ===== COMMIT ===== */
    $koneksi->commit();

    $_SESSION['message'] = ($pending === 1)
        ? "Permintaan penghapusan berhasil dikirim untuk approval."
        : "Data BA Kerusakan berhasil dihapus.";

    header("Location: ba_kerusakan.php?status=sukses");
    exit;

} catch (Exception $e) {

    $koneksi->rollback();

    error_log("Delete BA Kerusakan gagal (ID: $id): " . $e->getMessage());

    $_SESSION['message'] = "Proses penghapusan gagal: " . $e->getMessage();
    header("Location: ba_kerusakan.php?status=gagal");
    exit;
}
?>
