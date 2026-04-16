<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../login_registrasi.php");
    exit();
}

if (!isset($_SESSION['hak_akses']) || ($_SESSION['hak_akses'] !== 'Admin' && $_SESSION['hak_akses'] !== 'Super Admin')) {
    header("Location: ../personal/approval.php");
    exit();
}

require_once '../koneksi.php';

/* =========================================================
| Helper
========================================================= */
function safeTrim($value)
{
    return trim((string)$value);
}

function tableExists($koneksi, $table)
{
    $table = trim((string)$table);
    if ($table === '') {
        return false;
    }

    $sql = "SHOW TABLES LIKE ?";
    $stmt = $koneksi->prepare($sql);
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param("s", $table);
    if (!$stmt->execute()) {
        $stmt->close();
        return false;
    }

    $res = $stmt->get_result();
    $exists = ($res && $res->num_rows > 0);
    $stmt->close();

    return $exists;
}

function columnExists($koneksi, $table, $column)
{
    $table = trim((string)$table);
    $column = trim((string)$column);

    if ($table === '' || $column === '') {
        return false;
    }

    $sql = "SHOW COLUMNS FROM `" . $table . "` LIKE ?";
    $stmt = $koneksi->prepare($sql);
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param("s", $column);
    if (!$stmt->execute()) {
        $stmt->close();
        return false;
    }

    $res = $stmt->get_result();
    $exists = ($res && $res->num_rows > 0);
    $stmt->close();

    return $exists;
}

function executePrepared($koneksi, $sql, $types, $params)
{
    $stmt = $koneksi->prepare($sql);
    if (!$stmt) {
        throw new Exception("Prepare query gagal: " . $koneksi->error);
    }

    if ($types !== '' && !empty($params)) {
        $bindParams = array($types);
        $refs = array();

        foreach ($params as $key => $value) {
            $bindParams[] = $params[$key];
        }

        foreach ($bindParams as $key => $value) {
            $refs[$key] = &$bindParams[$key];
        }

        call_user_func_array(array($stmt, 'bind_param'), $refs);
    }

    if (!$stmt->execute()) {
        $err = $stmt->error;
        $stmt->close();
        throw new Exception("Execute query gagal: " . $err);
    }

    $stmt->close();
    return true;
}

/* =========================================================
| Validasi input
========================================================= */
if (!isset($_POST['id']) || !is_numeric($_POST['id']) || (int)$_POST['id'] <= 0) {
    $_SESSION['message'] = "ID data tidak valid.";
    header("Location: ba_pengembalian.php?status=gagal");
    exit();
}

$id = (int) $_POST['id'];
// var_dump($id);
// var_dump($_POST);
// exit;
$pending = isset($_POST['pending']) ? (int) $_POST['pending'] : 0;
$alasan_hapus = isset($_POST['alasan_hapus']) ? trim($_POST['alasan_hapus']) : '';

/* =========================================================
| Ambil data BA Pengembalian
========================================================= */
$sqlData = "SELECT
                id,
                nama_pembuat,
                pt,
                pengembali,
                penerima,
                diketahui,
                approval_1,
                approval_2,
                approval_3
            FROM berita_acara_pengembalian_v2
            WHERE id = ?
            LIMIT 1";

$stmtData = $koneksi->prepare($sqlData);
if (!$stmtData) {
    $_SESSION['message'] = "Gagal mengambil data BA Pengembalian.";
    header("Location: ba_pengembalian.php?status=gagal");
    exit();
}

$stmtData->bind_param("i", $id);

if (!$stmtData->execute()) {
    $stmtData->close();
    $_SESSION['message'] = "Gagal mengambil data BA Pengembalian.";
    header("Location: ba_pengembalian.php?status=gagal");
    exit();
}

$resData = $stmtData->get_result();
if (!$resData || $resData->num_rows === 0) {
    $stmtData->close();
    $_SESSION['message'] = "Data BA Pengembalian tidak ditemukan.";
    header("Location: ba_pengembalian.php?status=gagal");
    exit();
}

$data = $resData->fetch_assoc();
$stmtData->close();

/* =========================================================
| Validasi pembuat / super admin
========================================================= */
$nama_pembuat = isset($data['nama_pembuat']) ? trim((string)$data['nama_pembuat']) : '';
$nama_sesi = isset($_SESSION['nama']) ? trim((string)$_SESSION['nama']) : '';
$hak_akses = isset($_SESSION['hak_akses']) ? trim((string)$_SESSION['hak_akses']) : '';

if ($nama_sesi !== $nama_pembuat && $hak_akses !== 'Super Admin') {
    $_SESSION['message'] = "Anda bukan pembuat data ini atau tidak memiliki izin.";
    header("Location: ba_pengembalian.php?status=gagal");
    exit();
}

/* =========================================================
| Cek approval
========================================================= */
$ada_approval = false;

for ($i = 1; $i <= 3; $i++) {
    $field = 'approval_' . $i;
    if (isset($data[$field]) && (int)$data[$field] === 1) {
        $ada_approval = true;
        break;
    }
}

if ($pending === 1 && !$ada_approval) {
    $pending = 0;
}

/* =========================================================
| Tentukan approver pending delete
| HO     : data_karyawan.nama (jabatan = Dept. Head, departemen = MIS)
| non HO : data_karyawan_test.nama (posisi = KTU, pt(array) = pt surat)
========================================================= */
$namaApprover = ' ';

if ($pending === 1) {
    if ($alasan_hapus === '') {
        $_SESSION['message'] = "Alasan hapus wajib diisi untuk pending delete.";
        header("Location: ba_pengembalian.php?status=gagal");
        exit();
    }

    $ptSurat = isset($data['pt']) ? safeTrim($data['pt']) : '';

    if ($ptSurat === 'PT.MSAL (HO)') {
        $sqlApprover = "SELECT nama
                        FROM data_karyawan
                        WHERE jabatan = ?
                          AND departemen = ?
                        LIMIT 1";

        $stmtApprover = $koneksi->prepare($sqlApprover);
        if (!$stmtApprover) {
            throw new Exception("Prepare approver HO gagal: " . $koneksi->error);
        }

        $jabatanCari = 'Dept. Head';
        $departemenCari = 'MIS';

        $stmtApprover->bind_param("ss", $jabatanCari, $departemenCari);

        if (!$stmtApprover->execute()) {
            $err = $stmtApprover->error;
            $stmtApprover->close();
            throw new Exception("Execute approver HO gagal: " . $err);
        }

        $resApprover = $stmtApprover->get_result();
        if ($resApprover && $rowApprover = $resApprover->fetch_assoc()) {
            if (isset($rowApprover['nama']) && safeTrim($rowApprover['nama']) !== '') {
                $namaApprover = safeTrim($rowApprover['nama']);
            }
        }

        $stmtApprover->close();
    } else {
        $sqlApprover = "SELECT nama
                        FROM data_karyawan_test
                        WHERE posisi = ?
                          AND (
                                TRIM(pt) = ?
                                OR FIND_IN_SET(?, REPLACE(REPLACE(REPLACE(pt,'; ',','), ';', ','), ', ', ',')) > 0
                              )
                        LIMIT 1";

        $stmtApprover = $koneksi->prepare($sqlApprover);
        if (!$stmtApprover) {
            throw new Exception("Prepare approver non HO gagal: " . $koneksi->error);
        }

        $posisiCari = 'KTU';

        $stmtApprover->bind_param("sss", $posisiCari, $ptSurat, $ptSurat);

        if (!$stmtApprover->execute()) {
            $err = $stmtApprover->error;
            $stmtApprover->close();
            throw new Exception("Execute approver non HO gagal: " . $err);
        }

        $resApprover = $stmtApprover->get_result();
        if ($resApprover && $rowApprover = $resApprover->fetch_assoc()) {
            if (isset($rowApprover['nama']) && safeTrim($rowApprover['nama']) !== '') {
                $namaApprover = safeTrim($rowApprover['nama']);
            }
        }

        $stmtApprover->close();
    }

    if ($namaApprover === '' || $namaApprover === ' ') {
        $namaApprover = ($nama_sesi !== '') ? $nama_sesi : ' ';
    }
}

/* =========================================================
| Transaksi
========================================================= */
$koneksi->autocommit(false);

try {
    /* =====================================================
    | 1. UPDATE berita_acara_pengembalian_v2
    ===================================================== */
    if ($pending === 1) {
        $sqlMain = "UPDATE berita_acara_pengembalian_v2
                    SET pending_hapus = 1,
                        pending_hapus_approver = ?,
                        alasan_hapus = ?
                    WHERE id = ?";
        executePrepared($koneksi, $sqlMain, "ssi", array($namaApprover, $alasan_hapus, $id));
    } else {
        $sqlMain = "UPDATE berita_acara_pengembalian_v2
                    SET dihapus = 1,
                        pending_hapus = 0,
                        pending_hapus_approver = '',
                        alasan_hapus = ?
                    WHERE id = ?";
        executePrepared($koneksi, $sqlMain, "si", array($alasan_hapus, $id));
    }

    /* =====================================================
| 2. UPDATE history_n_temp_ba_pengembalian_v2
| WAJIB update semua row history dengan id_ba terkait
===================================================== */
    $jumlah_history_ba = 0;

    $stmtCekHistoryBa = $koneksi->prepare("
    SELECT COUNT(*)
    FROM history_n_temp_ba_pengembalian_v2
    WHERE id_ba = ?
");

    /* Jika prepare gagal, jangan hentikan proses utama */
    if ($stmtCekHistoryBa) {

        $stmtCekHistoryBa->bind_param("i", $id);

        if ($stmtCekHistoryBa->execute()) {
            $stmtCekHistoryBa->bind_result($jumlah_history_ba);
            $stmtCekHistoryBa->fetch();
        }

        $stmtCekHistoryBa->close();
    }

    /* HANYA update jika data memang ada */
    if ((int)$jumlah_history_ba > 0) {

        if ($pending === 1) {
            $stmtHistoryBa = $koneksi->prepare("
            UPDATE history_n_temp_ba_pengembalian_v2
            SET pending_hapus = 1,
                pending_hapus_approver = ?,
                dihapus = 0
            WHERE id_ba = ?
        ");
            if (!$stmtHistoryBa) {
                throw new Exception("Prepare update history_n_temp_ba_pengembalian_v2 gagal: " . $koneksi->error);
            }

            $stmtHistoryBa->bind_param("si", $namaApprover, $id);

            if (!$stmtHistoryBa->execute()) {
                $err = $stmtHistoryBa->error;
                $stmtHistoryBa->close();
                throw new Exception("Execute update history_n_temp_ba_pengembalian_v2 gagal: " . $err);
            }

            $stmtHistoryBa->close();
        } else {
            $stmtHistoryBa = $koneksi->prepare("
            UPDATE history_n_temp_ba_pengembalian_v2
            SET pending_hapus = 0,
                pending_hapus_approver = '',
                dihapus = 1
            WHERE id_ba = ?
        ");
            if (!$stmtHistoryBa) {
                throw new Exception("Prepare update history_n_temp_ba_pengembalian_v2 gagal: " . $koneksi->error);
            }

            $stmtHistoryBa->bind_param("i", $id);

            if (!$stmtHistoryBa->execute()) {
                $err = $stmtHistoryBa->error;
                $stmtHistoryBa->close();
                throw new Exception("Execute update history_n_temp_ba_pengembalian_v2 gagal: " . $err);
            }

            $stmtHistoryBa->close();
        }
    }

    /* =====================================================
    | 3. UPDATE history_n_temp_barang_pengembalian_v2
    ===================================================== */
    if (tableExists($koneksi, 'history_n_temp_barang_pengembalian_v2')) {
        $setBarang = array();
        $paramsBarang = array();
        $typesBarang = '';

        if (columnExists($koneksi, 'history_n_temp_barang_pengembalian_v2', 'pending_hapus')) {
            $setBarang[] = "pending_hapus = ?";
            $paramsBarang[] = ($pending === 1 ? 1 : 0);
            $typesBarang .= 'i';
        }

        if (columnExists($koneksi, 'history_n_temp_barang_pengembalian_v2', 'pending_hapus_approver')) {
            $setBarang[] = "pending_hapus_approver = ?";
            $paramsBarang[] = ($pending === 1 ? $namaApprover : '');
            $typesBarang .= 's';
        }

        if (columnExists($koneksi, 'history_n_temp_barang_pengembalian_v2', 'dihapus')) {
            $setBarang[] = "dihapus = ?";
            $paramsBarang[] = ($pending === 1 ? 0 : 1);
            $typesBarang .= 'i';
        }

        if (!empty($setBarang)) {
            $sqlBarang = "UPDATE history_n_temp_barang_pengembalian_v2
                          SET " . implode(', ', $setBarang) . "
                          WHERE id_ba = ?";
            $paramsBarang[] = $id;
            $typesBarang .= 'i';

            executePrepared($koneksi, $sqlBarang, $typesBarang, $paramsBarang);
        }
    }

    /* =====================================================
    | 4. UPDATE historikal_edit_ba
    | FIX:
    | - tidak pakai columnExists()
    | - tidak pakai alasan_hapus
    | - tidak mengubah proses 1, 2, dan 3
    | - support PHP 5.6
    ===================================================== */

    $namaBaHistorikal = 'pengembalian';
    $jumlahHistorikal = 0;

    $pendingHapusValue = ($pending === 1) ? 1 : 0;
    $pendingApproverValue = ($pending === 1) ? $namaApprover : '';
    $dihapusValue = ($pending === 1) ? 0 : 1;

    /* -----------------------------------------------
    | 4A. Cek data historikal_edit_ba
    ----------------------------------------------- */
    $sqlCekHistorikal = "SELECT COUNT(*)
                        FROM historikal_edit_ba
                        WHERE id_ba = ?
                        AND TRIM(LOWER(nama_ba)) = ?";

    $stmtCekHistorikal = $koneksi->prepare($sqlCekHistorikal);
    if (!$stmtCekHistorikal) {
        throw new Exception("Prepare cek historikal_edit_ba gagal: " . $koneksi->error);
    }

    $stmtCekHistorikal->bind_param("is", $id, $namaBaHistorikal);

    if (!$stmtCekHistorikal->execute()) {
        $err = $stmtCekHistorikal->error;
        $stmtCekHistorikal->close();
        throw new Exception("Execute cek historikal_edit_ba gagal: " . $err);
    }

    $stmtCekHistorikal->bind_result($jumlahHistorikal);
    $stmtCekHistorikal->fetch();
    $stmtCekHistorikal->close();

    /* -----------------------------------------------
    | 4B. Update jika data ditemukan
    ----------------------------------------------- */
    if ((int)$jumlahHistorikal > 0) {

        $sqlUpdateHistorikal = "UPDATE historikal_edit_ba
                                SET pending_hapus = ?,
                                    pending_hapus_approver = ?,
                                    dihapus = ?
                                WHERE id_ba = ?
                                AND TRIM(LOWER(nama_ba)) = ?";

        $stmtUpdateHistorikal = $koneksi->prepare($sqlUpdateHistorikal);
        if (!$stmtUpdateHistorikal) {
            throw new Exception("Prepare update historikal_edit_ba gagal: " . $koneksi->error);
        }

        $stmtUpdateHistorikal->bind_param(
            "isiis",
            $pendingHapusValue,
            $pendingApproverValue,
            $dihapusValue,
            $id,
            $namaBaHistorikal
        );

        if (!$stmtUpdateHistorikal->execute()) {
            $err = $stmtUpdateHistorikal->error;
            $stmtUpdateHistorikal->close();
            throw new Exception("Execute update historikal_edit_ba gagal: " . $err);
        }

        $stmtUpdateHistorikal->close();

        /* -------------------------------------------
        | 4C. Verifikasi hasil update
        ------------------------------------------- */
        $pendingHapusDb = null;
        $pendingApproverDb = null;
        $dihapusDb = null;

        $sqlVerifikasiHistorikal = "SELECT pending_hapus,
                                        pending_hapus_approver,
                                        dihapus
                                    FROM historikal_edit_ba
                                    WHERE id_ba = ?
                                    AND TRIM(LOWER(nama_ba)) = ?
                                    LIMIT 1";

        $stmtVerifikasiHistorikal = $koneksi->prepare($sqlVerifikasiHistorikal);
        if (!$stmtVerifikasiHistorikal) {
            throw new Exception("Prepare verifikasi historikal_edit_ba gagal: " . $koneksi->error);
        }

        $stmtVerifikasiHistorikal->bind_param("is", $id, $namaBaHistorikal);

        if (!$stmtVerifikasiHistorikal->execute()) {
            $err = $stmtVerifikasiHistorikal->error;
            $stmtVerifikasiHistorikal->close();
            throw new Exception("Execute verifikasi historikal_edit_ba gagal: " . $err);
        }

        $stmtVerifikasiHistorikal->bind_result(
            $pendingHapusDb,
            $pendingApproverDb,
            $dihapusDb
        );
        $stmtVerifikasiHistorikal->fetch();
        $stmtVerifikasiHistorikal->close();

        if ((int)$pendingHapusDb !== (int)$pendingHapusValue) {
            throw new Exception("Verifikasi historikal_edit_ba gagal pada kolom pending_hapus.");
        }

        if (trim((string)$pendingApproverDb) !== trim((string)$pendingApproverValue)) {
            throw new Exception("Verifikasi historikal_edit_ba gagal pada kolom pending_hapus_approver.");
        }

        if ((int)$dihapusDb !== (int)$dihapusValue) {
            throw new Exception("Verifikasi historikal_edit_ba gagal pada kolom dihapus.");
        }

    } else {
        error_log("historikal_edit_ba tidak ditemukan untuk id_ba = " . $id . " dan nama_ba = " . $namaBaHistorikal);
    }

    $koneksi->commit();
    $koneksi->autocommit(true);

    $_SESSION['message'] = ($pending === 1)
        ? "Permintaan penghapusan berhasil dikirim untuk approval."
        : "Data BA Pengembalian berhasil dihapus.";

    header("Location: ba_pengembalian.php?status=sukses");
    exit();
} catch (Exception $e) {
    $koneksi->rollback();
    $koneksi->autocommit(true);

    error_log("Delete BA Pengembalian gagal (ID: " . $id . "): " . $e->getMessage());

    $_SESSION['message'] = "Proses penghapusan gagal: " . $e->getMessage();
    header("Location: ba_pengembalian.php?status=gagal");
    exit();
}
