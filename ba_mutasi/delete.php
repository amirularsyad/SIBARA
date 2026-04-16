<?php
session_start();
require_once '../koneksi.php';

/* ================= VALIDASI LOGIN ================= */
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../login_registrasi.php");
    exit();
}

/* ================= CEK POST ================= */
if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    $_SESSION['message'] = "ID data tidak valid.";
    header("Location: ba_mutasi.php?status=gagal");
    exit;
}

$id           = (int) $_POST['id'];
$pending      = isset($_POST['pending']) ? (int) $_POST['pending'] : 0;
$alasan_hapus = isset($_POST['alasan_hapus']) ? trim($_POST['alasan_hapus']) : '';

/* ================= AMBIL DATA BA ================= */
$cek = $koneksi->query("
    SELECT pembuat, nomor_ba, tanggal
    FROM berita_acara_mutasi
    WHERE id = $id
");

if (!$cek || $cek->num_rows === 0) {
    $_SESSION['message'] = "Data tidak ditemukan.";
    header("Location: ba_mutasi.php?status=gagal");
    exit;
}

$data = $cek->fetch_assoc();

$nama_pembuat = $data['pembuat'];
$nomor_ba     = $data['nomor_ba'];
$tanggal      = $data['tanggal'];

$nama_sesi = $_SESSION['nama'];
$hak_akses = $_SESSION['hak_akses'];

/* ================= VALIDASI PEMBUAT ================= */
if ($nama_sesi !== $nama_pembuat && $hak_akses !== 'Super Admin') {
    $_SESSION['message'] = "Anda tidak memiliki akses untuk menghapus data.";
    $redirect = $_SERVER['HTTP_REFERER'] ?? 'ba_mutasi.php';
    header("Location: $redirect");
    exit;
}

$qPt = $koneksi->query("
    SELECT pt_asal 
    FROM berita_acara_mutasi 
    WHERE id = $id
");

$rowPt = $qPt->fetch_assoc();
$pt = $rowPt['pt_asal'];

if ($pt === 'PT.MSAL (HO)') {
    $namaApprover = 'Tedy Paronto';
} else {
    $stmtAppr = $koneksi->prepare("
        SELECT nama 
        FROM data_karyawan_test
        WHERE pt = ?
          AND posisi = 'KTU'
        LIMIT 1
    ");
    $stmtAppr->bind_param("s", $pt);
    $stmtAppr->execute();
    $resAppr = $stmtAppr->get_result();

    if ($resAppr->num_rows === 0) {
        throw new Exception("Approver tidak ditemukan untuk PT $pt");
    }

    $rowAppr = $resAppr->fetch_assoc();
    $namaApprover = $rowAppr['nama'];
    $stmtAppr->close();
}


/* ================= TRANSAKSI ================= */
$koneksi->begin_transaction();

try {

    /* ======================================================
       MODE PENDING HAPUS
    ====================================================== */
    if ($pending === 1) {

        /* === 1. UPDATE BA MUTASI === */
        $stmt1 = $koneksi->prepare("
            UPDATE berita_acara_mutasi
            SET pending_hapus = 1,
                pending_hapus_approver = ?,
                alasan_hapus = ?
            WHERE id = ?
        ");
        $stmt1->bind_param("ssi", $namaApprover, $alasan_hapus, $id);
        $stmt1->execute();
        $stmt1->close();

        /* === 2. UPDATE HISTORIKAL EDIT === */
        $stmt2 = $koneksi->prepare("
            UPDATE historikal_edit_ba
            SET pending_hapus = 1,
                pending_hapus_approver = ?
            WHERE id_ba = ?
              AND nama_ba = 'mutasi'
        ");
        $stmt2->bind_param("si", $namaApprover, $id);
        $stmt2->execute();
        $stmt2->close();

        /* === 3. UPDATE HISTORY TEMP BA MUTASI === */
        $stmt3 = $koneksi->prepare("
            UPDATE history_n_temp_ba_mutasi
            SET pending_hapus = 1,
                pending_hapus_approver = ?
            WHERE id_ba = ?
        ");
        $stmt3->bind_param("si", $namaApprover, $id);
        $stmt3->execute();
        $stmt3->close();

        // /* === 4. UPDATE HISTORY TEMP BARANG MUTASI === */
        // $stmt4 = $koneksi->prepare("
        //     UPDATE history_n_temp_barang_mutasi
        //     SET pending_hapus = 1
        //     WHERE id_ba = ?
        // ");
        // $stmt4->bind_param("i", $id);
        // $stmt4->execute();
        // $stmt4->close();

    }

    /* ======================================================
       MODE SOFT DELETE LANGSUNG
    ====================================================== */
    else {

        /* === 1. SOFT DELETE BA MUTASI === */
        $stmt1 = $koneksi->prepare("
            UPDATE berita_acara_mutasi
            SET dihapus = 1,
                alasan_hapus = ?
            WHERE id = ?
        ");
        $stmt1->bind_param("si", $alasan_hapus, $id);
        $stmt1->execute();
        $stmt1->close();

        /* === 2. SOFT DELETE HISTORIKAL EDIT === */
        $stmt2 = $koneksi->prepare("
            UPDATE historikal_edit_ba
            SET dihapus = 1
            WHERE id_ba = ?
              AND nama_ba = 'mutasi'
        ");
        $stmt2->bind_param("i", $id);
        $stmt2->execute();
        $stmt2->close();

        /* === 3. SOFT DELETE HISTORY TEMP BA MUTASI === */
        $stmt3 = $koneksi->prepare("
            UPDATE history_n_temp_ba_mutasi
            SET dihapus = 1
            WHERE id_ba = ?
        ");
        $stmt3->bind_param("i", $id);
        $stmt3->execute();
        $stmt3->close();

        // /* === 4. SOFT DELETE HISTORY TEMP BARANG MUTASI === */
        // $stmt4 = $koneksi->prepare("
        //     UPDATE history_n_temp_barang_mutasi
        //     SET dihapus = 1
        //     WHERE id_ba = ?
        // ");
        // $stmt4->bind_param("i", $id);
        // $stmt4->execute();
        // $stmt4->close();
    }

    /* ================= COMMIT ================= */
    $koneksi->commit();

    if ($pending === 1) {
        $_SESSION['message'] = "Permintaan penghapusan berhasil dikirim untuk approval.";
    } else {
        $_SESSION['message'] = "Data BA Mutasi berhasil dihapus.";
    }
    header("Location: ba_mutasi.php?status=sukses");
    exit;

} catch (Exception $e) {

    /* ================= ROLLBACK ================= */
    $koneksi->rollback();
    error_log("Soft Delete BA Mutasi gagal (ID: $id): " . $e->getMessage());

    $_SESSION['message'] = "Proses penghapusan gagal.";
    header("Location: ba_mutasi.php?status=gagal");
    exit;
}
?>
