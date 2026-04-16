<?php
session_start();
require_once '../koneksi.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(array('error' => 'Session login habis.'));
    exit();
}

if (!isset($_SESSION['hak_akses']) || ($_SESSION['hak_akses'] !== 'Admin' && $_SESSION['hak_akses'] !== 'Super Admin')) {
    echo json_encode(array('error' => 'Akses ditolak.'));
    exit();
}

function json_error($msg)
{
    echo json_encode(array('error' => $msg));
    exit();
}

function utf8ize_recursive(&$mixed)
{
    if (is_array($mixed)) {
        foreach ($mixed as $key => $value) {
            utf8ize_recursive($mixed[$key]);
        }
    } else {
        if (is_string($mixed) && $mixed !== '') {
            if (!preg_match('//u', $mixed)) {
                $mixed = utf8_encode($mixed);
            }
        }
    }
}

function detectDepartemenPenggunaFromJabatan($jabatanDeptPengguna)
{
    $jabatanDeptPengguna = strtoupper(trim((string)$jabatanDeptPengguna));

    if ($jabatanDeptPengguna === '') {
        return '';
    }

    if (strpos($jabatanDeptPengguna, ' MIS') !== false || strpos($jabatanDeptPengguna, 'MIS ') !== false || strpos($jabatanDeptPengguna, '(MIS') !== false || strpos($jabatanDeptPengguna, ' MIS ') !== false) {
        return 'MIS';
    }

    if (strpos($jabatanDeptPengguna, ' HRO') !== false || strpos($jabatanDeptPengguna, 'HRO ') !== false || strpos($jabatanDeptPengguna, '(HRO') !== false || strpos($jabatanDeptPengguna, ' HRO ') !== false) {
        return 'HRO';
    }

    if (strpos($jabatanDeptPengguna, 'MIS') !== false) {
        return 'MIS';
    }

    if (strpos($jabatanDeptPengguna, 'HRO') !== false) {
        return 'HRO';
    }

    return '';
}


if (!isset($_GET['id']) || trim($_GET['id']) === '') {
    json_error('ID tidak ditemukan');
}

$id = (int) $_GET['id'];
if ($id <= 0) {
    json_error('ID tidak valid');
}

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

function getSessionPtList()
{
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

try {
    // =============================
    // DATA UTAMA
    // =============================
    $stmt = $koneksi->prepare("SELECT * FROM berita_acara_pemutihan WHERE id = ? AND dihapus = 0 LIMIT 1");
    if (!$stmt) {
        throw new Exception('Prepare data utama gagal: ' . $koneksi->error);
    }

    $stmt->bind_param("i", $id);
    if (!$stmt->execute()) {
        throw new Exception('Execute data utama gagal: ' . $stmt->error);
    }

    $res = $stmt->get_result();
    if (!$res || $res->num_rows === 0) {
        $stmt->close();
        json_error('Data tidak ditemukan');
    }

    $data = $res->fetch_assoc();
    $stmt->close();

    if (!isset($data['departemen_pengguna']) || trim((string)$data['departemen_pengguna']) === '') {
        $data['departemen_pengguna'] = detectDepartemenPenggunaFromJabatan(
            isset($data['jabatan_dept_pengguna']) ? $data['jabatan_dept_pengguna'] : ''
        );
    }

    if (!$is_super_admin && !$is_admin_ho) {
        if (!in_array(trim((string)$data['pt']), $pt_list, true)) {
            json_error('Anda tidak memiliki akses ke data ini.');
        }
    }

    // =============================
    // GAMBAR
    // =============================
    $gambarList = array();

    $stmtG = $koneksi->prepare("
        SELECT id, file_path, keterangan
        FROM gambar_ba_pemutihan
        WHERE id_ba = ?
        ORDER BY id ASC
    ");
    if (!$stmtG) {
        throw new Exception('Prepare gambar gagal: ' . $koneksi->error);
    }

    $stmtG->bind_param("i", $id);
    if (!$stmtG->execute()) {
        throw new Exception('Execute gambar gagal: ' . $stmtG->error);
    }

    $resG = $stmtG->get_result();
    if ($resG) {
        while ($rowG = $resG->fetch_assoc()) {
            $gambarList[] = $rowG;
        }
    }
    $stmtG->close();

    // =============================
    // BARANG
    // barang_pemutihan ambil dari $koneksi
    // jenis_perangkat lookup dari $koneksi2
    // =============================
    $barangList = array();

    $stmtB = $koneksi->prepare("
    SELECT 
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
        alasan_penghapusan,
        kondisi
    FROM barang_pemutihan
    WHERE id_ba = ?
    ORDER BY id ASC
");
    if (!$stmtB) {
        throw new Exception('Prepare barang gagal: ' . $koneksi->error);
    }

    $stmtB->bind_param("i", $id);
    if (!$stmtB->execute()) {
        throw new Exception('Execute barang gagal: ' . $stmtB->error);
    }

    $resB = $stmtB->get_result();
    if ($resB) {
        while ($rowB = $resB->fetch_assoc()) {
            $barangList[] = array(
                'id' => isset($rowB['id']) ? $rowB['id'] : '',
                'id_ba' => isset($rowB['id_ba']) ? $rowB['id_ba'] : '',
                'id_pt' => isset($rowB['id_pt']) ? $rowB['id_pt'] : '',
                'pt_asal' => (isset($rowB['pt']) && trim($rowB['pt']) !== '') ? $rowB['pt'] : '-',
                'po' => (isset($rowB['po']) && trim($rowB['po']) !== '') ? $rowB['po'] : '-',
                'coa' => (isset($rowB['coa']) && trim($rowB['coa']) !== '') ? $rowB['coa'] : '-',
                'kode_assets' => (isset($rowB['kode_assets']) && trim($rowB['kode_assets']) !== '') ? $rowB['kode_assets'] : '-',
                'merk' => (isset($rowB['merk']) && trim($rowB['merk']) !== '') ? $rowB['merk'] : '-',
                'sn' => (isset($rowB['sn']) && trim($rowB['sn']) !== '') ? $rowB['sn'] : '-',
                'user' => (isset($rowB['user']) && trim($rowB['user']) !== '') ? $rowB['user'] : '-',
                'harga_beli' => (isset($rowB['harga_beli']) && $rowB['harga_beli'] !== '' && $rowB['harga_beli'] !== null) ? (int)$rowB['harga_beli'] : 0,
                'tahun_perolehan' => (isset($rowB['tahun_perolehan']) && $rowB['tahun_perolehan'] !== '' && $rowB['tahun_perolehan'] !== null) ? (int)$rowB['tahun_perolehan'] : 0,
                'alasan_penghapusan' => (isset($rowB['alasan_penghapusan']) && trim($rowB['alasan_penghapusan']) !== '') ? $rowB['alasan_penghapusan'] : '',
                'kondisi' => (isset($rowB['kondisi']) && trim($rowB['kondisi']) !== '') ? $rowB['kondisi'] : ''
            );
        }
    }
    $stmtB->close();

    // =============================
    // CEK PENDING EDIT
    // =============================
    $pending_edit = 0;

    $stmtP = $koneksi->prepare("
        SELECT id
        FROM history_n_temp_ba_pemutihan
        WHERE id_ba = ?
          AND pending_status = 1
        LIMIT 1
    ");
    if ($stmtP) {
        $stmtP->bind_param("i", $id);
        if ($stmtP->execute()) {
            $resP = $stmtP->get_result();
            if ($resP && $resP->num_rows > 0) {
                $pending_edit = 1;
            }
        }
        $stmtP->close();
    }

    // =============================
    // AMBIL DATA HISTORY STATUS 0 & 1
    // =============================
    $oldRow = null;
    $newRow = null;

    $stmtH = $koneksi->prepare("
        SELECT
            tanggal,
            nomor_ba,
            pt,

            askep_mill,
            jabatan_askep_mill,
            mill_manager,
            jabatan_mill_manager,
            dept_avp_engineering,
            jabatan_avp_engineering,
            dept_pengguna,
            jabatan_dept_pengguna,
            asisten_pga,
            jabatan_asisten_pga,
            ktu,
            jabatan_ktu,
            group_manager,
            jabatan_group_manager,
            vice_president,
            jabatan_vice_president,
            dept_hrops,
            jabatan_dept_hrops,
            dept_hrd,
            jabatan_dept_hrd,
            dept_accounting,
            jabatan_dept_accounting,
            dir_operation,
            jabatan_dir_operation,
            dir_finance,
            jabatan_dir_finance,
            dir_hr,
            jabatan_dir_hr,
            vice_ceo,
            jabatan_vice_ceo,
            ceo,
            jabatan_ceo,

            pembuat,
            jabatan_pembuat,
            pembuat_site,
            jabatan_pembuat_site,
            pemeriksa,
            jabatan_pemeriksa,
            pemeriksa_site,
            jabatan_pemeriksa_site,
            diketahui1_site,
            jabatan_diketahui1_site,
            disetujui1_site,
            jabatan_disetujui1_site,
            status
        FROM history_n_temp_ba_pemutihan
        WHERE id_ba = ?
        AND pending_status = 1
        AND status IN (0,1)
        ORDER BY status ASC, id ASC
    ");

    if ($stmtH) {
        $stmtH->bind_param("i", $id);
        if ($stmtH->execute()) {
            $resH = $stmtH->get_result();
            if ($resH) {
                while ($r = $resH->fetch_assoc()) {
                    if ((int)$r['status'] === 0 && $oldRow === null) {
                        $oldRow = $r;
                    }
                    if ((int)$r['status'] === 1 && $newRow === null) {
                        $newRow = $r;
                    }
                }
            }
        }
        $stmtH->close();
    }

    if ($oldRow !== null && (!isset($oldRow['departemen_pengguna']) || trim((string)$oldRow['departemen_pengguna']) === '')) {
        $oldRow['departemen_pengguna'] = detectDepartemenPenggunaFromJabatan(
            isset($oldRow['jabatan_dept_pengguna']) ? $oldRow['jabatan_dept_pengguna'] : ''
        );
    }

    if ($newRow !== null && (!isset($newRow['departemen_pengguna']) || trim((string)$newRow['departemen_pengguna']) === '')) {
        $newRow['departemen_pengguna'] = detectDepartemenPenggunaFromJabatan(
            isset($newRow['jabatan_dept_pengguna']) ? $newRow['jabatan_dept_pengguna'] : ''
        );
    }

    $header_edit = array();
    $data_edit_lama = array();
    $data_edit_baru = array();

    $headerMap = array(
        'tanggal' => 'Tanggal',
        'nomor_ba' => 'Nomor BA',
        'pt' => 'PT',

        'pembuat' => 'Pembuat',
        'jabatan_pembuat' => 'Jabatan Pembuat',
        'pembuat_site' => 'Pembuat Site',
        'jabatan_pembuat_site' => 'Jabatan Pembuat Site',
        'pemeriksa' => 'Pemeriksa',
        'jabatan_pemeriksa' => 'Jabatan Pemeriksa',
        'pemeriksa_site' => 'Pemeriksa Site',
        'jabatan_pemeriksa_site' => 'Jabatan Pemeriksa Site',
        'diketahui1_site' => 'Diketahui Site',
        'jabatan_diketahui1_site' => 'Jabatan Diketahui Site',
        'disetujui1_site' => 'Disetujui Site',
        'jabatan_disetujui1_site' => 'Jabatan Disetujui Site',

        'dept_pengguna' => 'Dept Pengguna',
        'jabatan_dept_pengguna' => 'Jabatan Dept Pengguna',
        'dept_hrops' => 'Dept HROPS',
        'jabatan_dept_hrops' => 'Jabatan Dept HROPS',
        'dept_hrd' => 'Dept HRD',
        'jabatan_dept_hrd' => 'Jabatan Dept HRD',
        'dept_accounting' => 'Dept Accounting',
        'jabatan_dept_accounting' => 'Jabatan Dept Accounting',
        'dir_operation' => 'Direktur Operation',
        'jabatan_dir_operation' => 'Jabatan Direktur Operation',
        'dir_finance' => 'Direktur Finance',
        'jabatan_dir_finance' => 'Jabatan Direktur Finance',
        'dir_hr' => 'Direktur HR',
        'jabatan_dir_hr' => 'Jabatan Direktur HR',
        'vice_ceo' => 'Vice CEO',
        'jabatan_vice_ceo' => 'Jabatan Vice CEO',
        'ceo' => 'CEO',
        'jabatan_ceo' => 'Jabatan CEO'
    );



    if ($oldRow && $newRow) {
        foreach ($oldRow as $key => $val) {
            if ($key === 'status') {
                continue;
            }

            $oldVal = isset($oldRow[$key]) ? (string)$oldRow[$key] : '';
            $newVal = isset($newRow[$key]) ? (string)$newRow[$key] : '';

            if ($oldVal !== $newVal) {
                $header_edit[] = isset($headerMap[$key]) ? $headerMap[$key] : $key;
                $data_edit_lama[] = $oldVal;
                $data_edit_baru[] = $newVal;
            }
        }
    }

    $data['pending_edit'] = $pending_edit;
    $data['header_edit'] = $header_edit;
    $data['data_edit_lama'] = $data_edit_lama;
    $data['data_edit_baru'] = $data_edit_baru;

    for ($i = 1; $i <= 11; $i++) {
        $field = 'tanggal_approve_' . $i;
        if (isset($data[$field])) {
            if ($data[$field] === '0000-00-00 00:00:00' || $data[$field] === '1970-01-01 00:00:00') {
                $data[$field] = null;
            }
        }
    }

    utf8ize_recursive($data);
    utf8ize_recursive($barangList);
    utf8ize_recursive($gambarList);

    $output = array(
        'data' => $data,
        'barangList' => $barangList,
        'gambarList' => $gambarList
    );

    $json = json_encode($output, JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        throw new Exception('JSON encode gagal: ' . json_last_error_msg());
    }

    echo $json;
    exit();
} catch (Exception $e) {
    error_log('get_edit_ba_pemutihan error: ' . $e->getMessage());
    echo json_encode(array('error' => 'Terjadi kesalahan: ' . $e->getMessage()));
    exit();
}
