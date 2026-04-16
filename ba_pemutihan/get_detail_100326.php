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

function jsonResponse($data) {
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
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
        case 1:
            return 'Perubahan';
        case 2:
            return 'Data Lama';
        case 3:
            return 'Data Baru';
        default:
            return '-';
    }
}

function mapPendingStatusNama($pending_status) {
    $pending_status = (int)$pending_status;

    switch ($pending_status) {
        case 1:
            return 'Pending';
        case 2:
            return 'Ditolak';
        case 3:
            return 'Disetujui';
        default:
            return '-';
    }
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    jsonResponse(array('error' => 'ID tidak valid.'));
}

/*
|--------------------------------------------------------------------------
| Support akses PT user
|--------------------------------------------------------------------------
*/
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

/*
|--------------------------------------------------------------------------
| Ambil data utama
|--------------------------------------------------------------------------
*/
$sql = "SELECT *
        FROM berita_acara_pemutihan
        WHERE id = ?
          AND dihapus = 0
        LIMIT 1";

$stmt = $koneksi->prepare($sql);
if (!$stmt) {
    jsonResponse(array('error' => 'Prepare data utama gagal: ' . $koneksi->error));
}

$stmt->bind_param("i", $id);

if (!$stmt->execute()) {
    $stmt->close();
    jsonResponse(array('error' => 'Execute data utama gagal: ' . $stmt->error));
}

$result = $stmt->get_result();
$data = $result ? $result->fetch_assoc() : null;
$stmt->close();

if (!$data) {
    jsonResponse(array('error' => 'Data tidak ditemukan.'));
}

/*
|--------------------------------------------------------------------------
| Validasi akses PT
|--------------------------------------------------------------------------
*/
if (!$is_super_admin && !$is_admin_ho) {
    if (!in_array(trim((string)$data['pt']), $pt_list, true)) {
        jsonResponse(array('error' => 'Anda tidak memiliki akses ke data ini.'));
    }
}

/*
|--------------------------------------------------------------------------
| Deteksi nama kolom PT di barang_pemutihan
|--------------------------------------------------------------------------
| Support 2 kemungkinan:
| - pt
| - pt_asal
|--------------------------------------------------------------------------
*/
$barang_pt_column = 'pt';

$cekPtAsal = $koneksi->query("SHOW COLUMNS FROM barang_pemutihan LIKE 'pt_asal'");
if ($cekPtAsal && $cekPtAsal->num_rows > 0) {
    $barang_pt_column = 'pt_asal';
} else {
    $cekPt = $koneksi->query("SHOW COLUMNS FROM barang_pemutihan LIKE 'pt'");
    if ($cekPt && $cekPt->num_rows > 0) {
        $barang_pt_column = 'pt';
    }
}

/*
|--------------------------------------------------------------------------
| Ambil data barang
|--------------------------------------------------------------------------
*/
$barangList = array();

$sqlBarang = "SELECT 
                    id,
                    id_ba,
                    pt AS pt_asal,
                    po,
                    coa,
                    kode_assets,
                    merk,
                    sn,
                    user,
                    harga_beli,
                    tahun_perolehan,
                    alasan_penghapusan,
                    kondisi
                FROM barang_pemutihan
                WHERE id_ba = ?
                ORDER BY kode_assets ASC";

$stmtBarang = $koneksi->prepare($sqlBarang);
if ($stmtBarang) {
    $stmtBarang->bind_param("i", $id);
    if ($stmtBarang->execute()) {
        $resBarang = $stmtBarang->get_result();
        if ($resBarang) {
            while ($rowBarang = $resBarang->fetch_assoc()) {
                $barangList[] = array(
                    'id' => isset($rowBarang['id']) ? $rowBarang['id'] : '',
                    'id_ba' => isset($rowBarang['id_ba']) ? $rowBarang['id_ba'] : '',
                    'pt_asal' => isset($rowBarang['pt_asal']) && trim($rowBarang['pt_asal']) !== '' ? $rowBarang['pt_asal'] : '-',
                    'po' => isset($rowBarang['po']) && trim($rowBarang['po']) !== '' ? $rowBarang['po'] : '-',
                    'coa' => isset($rowBarang['coa']) && trim($rowBarang['coa']) !== '' ? $rowBarang['coa'] : '-',
                    'kode_assets' => isset($rowBarang['kode_assets']) && trim($rowBarang['kode_assets']) !== '' ? $rowBarang['kode_assets'] : '-',
                    'merk' => isset($rowBarang['merk']) && trim($rowBarang['merk']) !== '' ? $rowBarang['merk'] : '-',
                    'sn' => isset($rowBarang['sn']) && trim($rowBarang['sn']) !== '' ? $rowBarang['sn'] : '-',
                    'user' => isset($rowBarang['user']) && trim($rowBarang['user']) !== '' ? $rowBarang['user'] : '-',
                    'harga_beli' => isset($rowBarang['harga_beli']) && $rowBarang['harga_beli'] !== '' ? (int)$rowBarang['harga_beli'] : 0,
                    'tahun_perolehan' => isset($rowBarang['tahun_perolehan']) && $rowBarang['tahun_perolehan'] !== '' ? (int)$rowBarang['tahun_perolehan'] : 0,
                    'alasan_penghapusan' => isset($rowBarang['alasan_penghapusan']) && trim($rowBarang['alasan_penghapusan']) !== '' ? $rowBarang['alasan_penghapusan'] : '-',
                    'kondisi' => isset($rowBarang['kondisi']) && trim($rowBarang['kondisi']) !== '' ? $rowBarang['kondisi'] : '-'
                );
            }
        }
    }
    $stmtBarang->close();
}

/*
|--------------------------------------------------------------------------
| Ambil data gambar
|--------------------------------------------------------------------------
*/
$gambarList = array();

$sqlGambar = "SELECT id, id_ba, file_path, keterangan
              FROM gambar_ba_pemutihan
              WHERE id_ba = ?
              ORDER BY id ASC";

$stmtGambar = $koneksi->prepare($sqlGambar);
if ($stmtGambar) {
    $stmtGambar->bind_param("i", $id);
    if ($stmtGambar->execute()) {
        $resGambar = $stmtGambar->get_result();
        if ($resGambar) {
            while ($rowGambar = $resGambar->fetch_assoc()) {
                $gambarList[] = array(
                    'id' => isset($rowGambar['id']) ? $rowGambar['id'] : '',
                    'id_ba' => isset($rowGambar['id_ba']) ? $rowGambar['id_ba'] : '',
                    'file_path' => isset($rowGambar['file_path']) ? $rowGambar['file_path'] : '',
                    'keterangan' => isset($rowGambar['keterangan']) ? $rowGambar['keterangan'] : ''
                );
            }
        }
    }
    $stmtGambar->close();
}

/*
|--------------------------------------------------------------------------
| Ambil histori
|--------------------------------------------------------------------------
*/
$data_history = array();

$sqlHistory = "SELECT
                    id,
                    id_ba,
                    status,
                    pending_status,
                    pending_approver,
                    alasan_edit,
                    alasan_tolak,
                    tanggal,
                    nomor_ba,
                    created_at
               FROM history_n_temp_ba_pemutihan
               WHERE id_ba = ?
               ORDER BY created_at DESC, id DESC";

$stmtHistory = $koneksi->prepare($sqlHistory);
if ($stmtHistory) {
    $stmtHistory->bind_param("i", $id);
    if ($stmtHistory->execute()) {
        $resHistory = $stmtHistory->get_result();
        if ($resHistory) {
            while ($rowHistory = $resHistory->fetch_assoc()) {
                $rowHistory['status_nama'] = mapStatusNama(isset($rowHistory['status']) ? $rowHistory['status'] : 0);
                $rowHistory['pending_status_nama'] = mapPendingStatusNama(isset($rowHistory['pending_status']) ? $rowHistory['pending_status'] : 0);
                $data_history[] = $rowHistory;
            }
        }
    }
    $stmtHistory->close();
}

/*
|--------------------------------------------------------------------------
| Output JSON
|--------------------------------------------------------------------------
*/
jsonResponse(array(
    'success' => true,
    'data' => $data,
    'barangList' => $barangList,
    'gambarList' => $gambarList,
    'data_history' => $data_history
));
?>