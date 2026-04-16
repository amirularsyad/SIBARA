<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(array('error' => 'Session login tidak valid.'));
    exit();
}

if (!isset($_SESSION['hak_akses']) || ($_SESSION['hak_akses'] !== 'Admin' && $_SESSION['hak_akses'] !== 'Super Admin')) {
    echo json_encode(array('error' => 'Anda tidak memiliki akses.'));
    exit();
}

include '../koneksi.php';

/* =========================================================
| Helper
========================================================= */
function jsonResponse($data) {
    $json = json_encode($data, JSON_UNESCAPED_UNICODE);

    if ($json === false) {
        echo json_encode(array(
            'error' => 'Gagal encode JSON: ' . json_last_error_msg()
        ));
        exit();
    }

    echo $json;
    exit();
}

function getSessionPtList() {
    $pt_raw = isset($_SESSION['pt']) ? $_SESSION['pt'] : '';
    $pt_list = array();

    if (is_array($pt_raw)) {
        foreach ($pt_raw as $p) {
            $p = trim($p);
            if ($p !== '') {
                $pt_list[] = $p;
            }
        }
    } else {
        $p = trim($pt_raw);
        if ($p !== '') {
            $pt_list[] = $p;
        }
    }

    return $pt_list;
}

function mapStatusNama($status) {
    $status = (int)$status;

    switch ($status) {
        case 1: return 'Data Baru';
        case 0: return 'Data Lama';
        default: return '-';
    }
}

function mapPendingStatusNama($pending_status) {
    $pending_status = (int)$pending_status;

    switch ($pending_status) {
        case 1: return 'Pending Edit';
        case 2: return 'Ditolak';
        default: return 'History';
    }
}

/* =========================================================
| Ambil ID
========================================================= */
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    jsonResponse(array('error' => 'ID tidak valid.'));
}

/* =========================================================
| Validasi PT akses
========================================================= */
$pt_list = getSessionPtList();
$is_super_admin = (isset($_SESSION['hak_akses']) && $_SESSION['hak_akses'] === 'Super Admin');

$is_admin_ho = false;
if (
    isset($_SESSION['hak_akses']) &&
    $_SESSION['hak_akses'] === 'Admin' &&
    in_array('PT.MSAL (HO)', $pt_list, true)
) {
    $is_admin_ho = true;
}

/* =========================================================
| Ambil data utama
========================================================= */
$sql = "SELECT
            id,
            tanggal,
            nomor_ba,
            pt,
            id_pt,
            nama_pembuat,
            pengembali,
            jabatan_pengembali,
            penerima,
            jabatan_penerima,
            diketahui,
            jabatan_diketahui,
            approval_1,
            approval_2,
            approval_3,
            pending_hapus,
            dihapus
        FROM berita_acara_pengembalian_v2
        WHERE id = ?
        LIMIT 1";

$stmt = $koneksi->prepare($sql);
if (!$stmt) {
    jsonResponse(array('error' => 'Prepare gagal: ' . $koneksi->error));
}

$stmt->bind_param("i", $id);

if (!$stmt->execute()) {
    $stmt->close();
    jsonResponse(array('error' => 'Execute gagal: ' . $stmt->error));
}

$result = $stmt->get_result();
$data = $result ? $result->fetch_assoc() : null;
$stmt->close();

if (!$data) {
    jsonResponse(array('error' => 'Data tidak ditemukan.'));
}

/* =========================================================
| Validasi akses PT
========================================================= */
if (!$is_super_admin && !$is_admin_ho) {
    if (!in_array(trim((string)$data['pt']), $pt_list, true)) {
        jsonResponse(array('error' => 'Tidak ada akses.'));
    }
}

/* =========================================================
| Ambil barang
========================================================= */
$barangList = array();

$sqlBarang = "SELECT 
                id,
                id_ba,
                pt,
                id_pt,
                po,
                coa,
                kode_assets,
                merk,
                sn,
                user,
                harga_beli,
                tahun_perolehan,
                kondisi,
                keterangan
              FROM barang_pengembalian_v2
              WHERE id_ba = ?
              ORDER BY id ASC";

$stmtBarang = $koneksi->prepare($sqlBarang);

if ($stmtBarang) {
    $stmtBarang->bind_param("i", $id);

    if ($stmtBarang->execute()) {
        $res = $stmtBarang->get_result();

        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $barangList[] = array(
                    'pt' => $row['pt'],
                    'id_pt' => $row['id_pt'],
                    'po' => $row['po'],
                    'coa' => $row['coa'],
                    'kode_assets' => $row['kode_assets'],
                    'merk' => $row['merk'],
                    'sn' => $row['sn'],
                    'user' => $row['user'],
                    'harga_beli' => (int)$row['harga_beli'],
                    'tahun_perolehan' => (int)$row['tahun_perolehan'],
                    'kondisi' => $row['kondisi'],
                    'keterangan' => $row['keterangan']
                );
            }
        }
    }

    $stmtBarang->close();
}

/* =========================================================
| Ambil gambar
========================================================= */
$gambarList = array();

$sqlGambar = "SELECT id, id_ba, file_path, keterangan
              FROM gambar_ba_pengembalian_v2
              WHERE id_ba = ?
              ORDER BY id ASC";

$stmtGambar = $koneksi->prepare($sqlGambar);

if ($stmtGambar) {
    $stmtGambar->bind_param("i", $id);

    if ($stmtGambar->execute()) {
        $res = $stmtGambar->get_result();

        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $gambarList[] = array(
                    'file_path' => $row['file_path'],
                    'keterangan' => $row['keterangan']
                );
            }
        }
    }

    $stmtGambar->close();
}

/* =========================================================
| Ambil histori
========================================================= */
$data_history = array();

$sqlHistory = "SELECT
                    id,
                    id_ba,
                    status,
                    pending_status,
                    alasan_edit,
                    alasan_tolak,
                    tanggal,
                    nomor_ba,
                    created_at
               FROM history_n_temp_ba_pengembalian_v2
               WHERE id_ba = ?
               AND (
                    (pending_status = 1 AND status = 0)
                    OR
                    (pending_status <> 1 OR pending_status IS NULL)
               )
               ORDER BY created_at DESC, id DESC";

$stmtHistory = $koneksi->prepare($sqlHistory);

if ($stmtHistory) {
    $stmtHistory->bind_param("i", $id);

    if ($stmtHistory->execute()) {
        $res = $stmtHistory->get_result();

        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $row['status_nama'] = mapStatusNama($row['status']);
                $row['pending_status_nama'] = mapPendingStatusNama($row['pending_status']);
                $data_history[] = $row;
            }
        }
    }

    $stmtHistory->close();
}

/* =========================================================
| Output
========================================================= */
jsonResponse(array(
    'success' => true,
    'data' => $data,
    'barangList' => $barangList,
    'gambarList' => $gambarList,
    'data_history' => $data_history
));