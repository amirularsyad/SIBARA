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

function json_error($msg) {
    echo json_encode(array('error' => $msg));
    exit();
}

function utf8ize_recursive(&$mixed) {
    if (is_array($mixed)) {
        foreach ($mixed as $key => $value) {
            utf8ize_recursive($mixed[$key]);
        }
    } else {
        if (is_string($mixed)) {
            $mixed = @utf8_encode($mixed);
        }
    }
}


if (!isset($_GET['id']) || trim($_GET['id']) === '') {
    json_error('ID tidak ditemukan');
}

$id = (int) $_GET['id'];
if ($id <= 0) {
    json_error('ID tidak valid');
}

try {
    // =============================
    // DATA UTAMA
    // =============================
    $stmt = $koneksi->prepare("SELECT * FROM berita_acara_pemutihan WHERE id = ? LIMIT 1");
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
            pembuat,
            jabatan_pembuat,
            pemeriksa,
            jabatan_pemeriksa,
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

    $header_edit = array();
    $data_edit_lama = array();
    $data_edit_baru = array();

    $headerMap = array(
        'tanggal' => 'Tanggal',
        'nomor_ba' => 'Nomor BA',
        'pt' => 'PT',
        'pembuat' => 'Pembuat',
        'jabatan_pembuat' => 'Jabatan Pembuat',
        'pemeriksa' => 'Pemeriksa',
        'jabatan_pemeriksa' => 'Jabatan Pemeriksa'
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

    for ($i = 1; $i <= 9; $i++) {
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

    $json = json_encode($output);
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
?>