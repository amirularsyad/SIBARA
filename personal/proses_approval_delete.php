<?php
session_start();
require_once '../koneksi.php';

// if (!isset($_SESSION['nama'])) {
//     header("Location: ../login_registrasi.php");
//     exit;
// }

// /* ================= VALIDASI LOGIN ================= */
// if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
//     $_SESSION['success'] = false;
//     $_SESSION['message'] = "Sesi login tidak valid.";
//     header("Location: ../login_registrasi.php");
//     exit();
// }

/* ================= AMBIL DATA POST ================= */
$id_ba   = isset($_POST['id_ba']) ? intval($_POST['id_ba']) : 0;
$jenisBA = isset($_POST['jenisBA']) ? $_POST['jenisBA'] : '';
$approver = isset($_POST['approver']) ? $_POST['approver'] : '';
$aksi    = isset($_POST['aksi']) ? $_POST['aksi'] : '';

/* ================= VALIDASI AWAL ================= */
if ($id_ba <= 0 || empty($jenisBA) || empty($aksi)) {
header('Content-Type: application/json');
echo json_encode([
    'success' => false,
    'message' => 'Data tidak lengkap.'
]);
exit;
}

/* ================= VALIDASI JENIS BA ================= */
$allowedJenis = array('kerusakan', 'pengembalian', 'mutasi', 'st_asset', 'pemutihan');
if (!in_array($jenisBA, $allowedJenis)) {
    $_SESSION['success'] = false;
    $_SESSION['message'] = "Jenis Berita Acara tidak valid.";
    header("Location: ../personal/approval.php");
    exit();
}

/* ================= NAMA TABEL ================= */
switch ($jenisBA) {
    case 'kerusakan':
        $tabel_ba         = 'berita_acara_kerusakan';
        $tabel_history_ba = 'history_n_temp_ba_kerusakan';
        break;

    case 'pengembalian':
        $tabel_ba         = 'berita_acara_pengembalian_v2';
        $tabel_history_ba = 'history_n_temp_ba_pengembalian_v2';
        break;

    case 'mutasi':
        $tabel_ba         = 'berita_acara_mutasi';
        $tabel_history_ba = 'history_n_temp_ba_mutasi';
        break;

    case 'st_asset':
        $tabel_ba         = 'ba_serah_terima_asset';
        $tabel_history_ba = 'history_n_temp_ba_serah_terima_asset';
        break;

    case 'pemutihan':
        $tabel_ba         = 'berita_acara_pemutihan';
        $tabel_history_ba = 'history_n_temp_ba_pemutihan';
        break;

    default:
        header('Content-Type: application/json');
        echo json_encode(array(
            'success' => false,
            'message' => 'Jenis Berita Acara tidak valid'
        ));
        exit;
}
$tabel_historikal_edit = "historikal_edit_ba";

/* ================= VALIDASI APPROVER VS SESSION ================= */
$sessionNama = isset($_SESSION['nama']) ? trim($_SESSION['nama']) : '';
$approver    = trim($approver);

if ($sessionNama === '' || $approver === '' || $approver !== $sessionNama) {
    $_SESSION['success'] = false;
    $_SESSION['message'] = "Tidak ada akses.";
    header("Location: ../personal/approval.php?jenis_ba=" . urlencode($jenisBA));
    exit();
}

/* ================= VALIDASI APPROVER DI DATABASE ================= */
$sqlCekApprover = "SELECT pending_hapus, pending_hapus_approver FROM $tabel_ba WHERE id = ? LIMIT 1";
$stmtCekApprover = mysqli_prepare($koneksi, $sqlCekApprover);

if (!$stmtCekApprover) {
    $_SESSION['success'] = false;
    $_SESSION['message'] = "Gagal validasi approver.";
    header("Location: ../personal/approval.php?jenis_ba=" . urlencode($jenisBA));
    exit();
}

mysqli_stmt_bind_param($stmtCekApprover, "i", $id_ba);
mysqli_stmt_execute($stmtCekApprover);
$resultCekApprover = mysqli_stmt_get_result($stmtCekApprover);
$rowCekApprover = $resultCekApprover ? mysqli_fetch_assoc($resultCekApprover) : false;
mysqli_stmt_close($stmtCekApprover);

if (
    !$rowCekApprover ||
    (int)$rowCekApprover['pending_hapus'] !== 1 ||
    trim($rowCekApprover['pending_hapus_approver']) !== $sessionNama
) {
    $_SESSION['success'] = false;
    $_SESSION['message'] = "Tidak ada akses.";
    header("Location: ../personal/approval.php?jenis_ba=" . urlencode($jenisBA));
    exit();
}

/* ================= PROSES BERDASARKAN AKSI ================= */
mysqli_begin_transaction($koneksi);

try {

    /* =======================================================
        ======================= SETUJU ========================
        ======================================================= */
    if ($aksi === 'setuju') {

        // berita_acara_(jenis)
        $sql1 = "
            UPDATE $tabel_ba
            SET pending_hapus = 0,
                pending_hapus_approver = '',
                dihapus = 1
            WHERE id = $id_ba
        ";
        if (!mysqli_query($koneksi, $sql1)) {
            throw new Exception("Gagal update $tabel_ba");
        }

        // history_n_temp_ba_(jenis)
        $sql2 = "
            UPDATE $tabel_history_ba
            SET pending_hapus = 0,
                pending_hapus_approver = '',
                dihapus = 1
            WHERE id_ba = $id_ba
        ";
        if (!mysqli_query($koneksi, $sql2)) {
            throw new Exception("Gagal update $tabel_history_ba");
        }

        // historikal_edit_ba
        $sql3 = "
            UPDATE $tabel_historikal_edit
            SET pending_hapus = 0,
                pending_hapus_approver = '',
                dihapus = 1
            WHERE id_ba = $id_ba
              AND nama_ba = '$jenisBA'
        ";
        if (!mysqli_query($koneksi, $sql3)) {
            throw new Exception("Gagal update historikal_edit_ba");
        }

        // khusus mutasi
        if ($jenisBA === 'mutasi') {
            $sql4 = "
                UPDATE history_n_temp_barang_mutasi
                SET pending_hapus = 0,
                    pending_hapus_approver = '',
                    dihapus = 1
                WHERE id_ba = $id_ba
            ";
            if (!mysqli_query($koneksi, $sql4)) {
                throw new Exception("Gagal update history_n_temp_barang_mutasi");
            }
        }

        // khusus pengembalian
        if ($jenisBA === 'pengembalian') {
            $sql4 = "
                UPDATE history_n_temp_barang_pengembalian_v2
                SET pending_hapus = 0,
                    pending_hapus_approver = '',
                    dihapus = 1
                WHERE id_ba = $id_ba
            ";
            if (!mysqli_query($koneksi, $sql4)) {
                throw new Exception("Gagal update history_n_temp_barang_pengembalian_v2");
            }
        }

        mysqli_commit($koneksi);

        $_SESSION['success'] = true;
        $_SESSION['message'] = "Penghapusan data berhasil disetujui.";

    }

    /* =======================================================
       ======================== TOLAK =========================
       ======================================================= */
    elseif ($aksi === 'tolak') {

        // berita_acara_(jenis)
        $sql1 = "
            UPDATE $tabel_ba
            SET pending_hapus = 0,
                pending_hapus_approver = '',
                alasan_hapus = '',
                dihapus = 0
            WHERE id = $id_ba
        ";
        if (!mysqli_query($koneksi, $sql1)) {
            throw new Exception("Gagal update $tabel_ba");
        }

        // history_n_temp_ba_(jenis)
        $sql2 = "
            UPDATE $tabel_history_ba
            SET pending_hapus = 0,
                pending_hapus_approver = '',
                dihapus = 0
            WHERE id_ba = $id_ba
        ";
        if (!mysqli_query($koneksi, $sql2)) {
            throw new Exception("Gagal update $tabel_history_ba");
        }

        // historikal_edit_ba
        $sql3 = "
            UPDATE $tabel_historikal_edit
            SET pending_hapus = 0,
                pending_hapus_approver = '',
                dihapus = 0
            WHERE id_ba = $id_ba
                AND nama_ba = '$jenisBA'
        ";
        if (!mysqli_query($koneksi, $sql3)) {
            throw new Exception("Gagal update historikal_edit_ba");
        }

        // khusus pengembalian
        if ($jenisBA === 'pengembalian') {
            $sql4 = "
                UPDATE history_n_temp_barang_pengembalian_v2
                SET pending_hapus = 0,
                    pending_hapus_approver = '',
                    dihapus = 0
                WHERE id_ba = $id_ba
            ";
            if (!mysqli_query($koneksi, $sql4)) {
                throw new Exception("Gagal update history_n_temp_barang_pengembalian_v2");
            }
        }

        // khusus mutasi
        if ($jenisBA === 'mutasi') {
            $sql4 = "
                UPDATE history_n_temp_barang_mutasi
                SET pending_hapus = 0,
                    pending_hapus_approver = '',
                    dihapus = 0
                WHERE id_ba = $id_ba
            ";
            if (!mysqli_query($koneksi, $sql4)) {
                throw new Exception("Gagal update history_n_temp_barang_mutasi");
            }
        }

        mysqli_commit($koneksi);

        $_SESSION['success'] = true;
        $_SESSION['message'] = "Penghapusan data berhasil ditolak.";

    }

    /* ================= AKSI TIDAK VALID ================= */
    else {
        throw new Exception("Aksi tidak valid.");
    }

} catch (Exception $e) {

    mysqli_rollback($koneksi);

    $_SESSION['success'] = false;
    $_SESSION['message'] = $e->getMessage();
}

/* ================= REDIRECT ================= */
header('Content-Type: application/json');
echo json_encode(array(
    'success' => $_SESSION['success'],
    'message' => $_SESSION['message']
));
exit();

