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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['message'] = "Invalid request method.";
    header("Location: ba_pengembalian.php?status=gagal");
    exit();
}

/* =========================================================
| Helper
========================================================= */
function normalizeString($value, $default)
{
    $value = trim((string)$value);
    return ($value === '') ? $default : $value;
}

function normalizeText($value, $default)
{
    $value = str_replace(array("\r\n", "\r"), "\n", trim((string)$value));
    return ($value === '') ? $default : $value;
}

function normalizeInt($value, $default)
{
    $value = preg_replace('/[^0-9]/', '', (string)$value);
    if ($value === '') {
        return (int)$default;
    }
    return (int)$value;
}

function safeUploadFileName($name)
{
    $name = basename((string)$name);
    $name = preg_replace('/[^A-Za-z0-9\.\-_]/', '_', $name);
    return $name;
}

function ensureDirExists($dir)
{
    if (!is_dir($dir)) {
        if (!mkdir($dir, 0777, true)) {
            throw new Exception("Folder upload tidak bisa dibuat.");
        }
    }
}

function findKtuByPt($koneksi, $pt)
{
    $sql = "SELECT nama, posisi
            FROM data_karyawan_test
            WHERE UPPER(TRIM(posisi)) = 'KTU'
              AND FIND_IN_SET(?, REPLACE(pt, ', ', ',')) > 0
            ORDER BY nama ASC
            LIMIT 1";

    $stmt = $koneksi->prepare($sql);
    if (!$stmt) {
        throw new Exception("Prepare pencarian KTU gagal: " . $koneksi->error);
    }

    $stmt->bind_param("s", $pt);

    if (!$stmt->execute()) {
        $err = $stmt->error;
        $stmt->close();
        throw new Exception("Execute pencarian KTU gagal: " . $err);
    }

    $res = $stmt->get_result();
    $data = null;

    if ($res && $row = $res->fetch_assoc()) {
        $data = array(
            'nama'   => trim((string)$row['nama']),
            'posisi' => trim((string)$row['posisi']) !== '' ? trim((string)$row['posisi']) : 'KTU'
        );
    }

    $stmt->close();
    return $data;
}

function refValues($arr)
{
    $refs = array();
    foreach ($arr as $key => $value) {
        $refs[$key] = &$arr[$key];
    }
    return $refs;
}

function getTableColumns($koneksi, $table)
{
    static $cache = array();

    if (isset($cache[$table])) {
        return $cache[$table];
    }

    $cols = array();
    $sql = "SHOW COLUMNS FROM `" . $table . "`";
    $res = $koneksi->query($sql);

    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $cols[$row['Field']] = true;
        }
        $res->free();
    }

    $cache[$table] = $cols;
    return $cols;
}

function tableHasColumns($koneksi, $table, $requiredColumns)
{
    $cols = getTableColumns($koneksi, $table);

    if (empty($cols)) {
        return false;
    }

    foreach ($requiredColumns as $col) {
        if (!isset($cols[$col])) {
            return false;
        }
    }

    return true;
}

function safeDeleteIfSchemaReady($koneksi, $table, $requiredColumns, $sql, $types, $params)
{
    if (!tableHasColumns($koneksi, $table, $requiredColumns)) {
        return true;
    }

    return deletePrepared($koneksi, $sql, $types, $params);
}

function filterExistingColumns($koneksi, $table, $data)
{
    $cols = getTableColumns($koneksi, $table);
    $filtered = array();

    foreach ($data as $key => $value) {
        if (isset($cols[$key])) {
            $filtered[$key] = $value;
        }
    }

    return $filtered;
}

function bindTypeOf($value)
{
    if (is_int($value)) {
        return 'i';
    }
    if (is_float($value)) {
        return 'd';
    }
    return 's';
}

function insertAssoc($koneksi, $table, $data)
{
    $data = filterExistingColumns($koneksi, $table, $data);

    if (empty($data)) {
        return 0;
    }

    $fields = array_keys($data);
    $placeholders = array();
    $types = '';
    $params = array();

    foreach ($fields as $field) {
        if (is_string($data[$field]) && trim($data[$field]) === 'NOW()') {
            $placeholders[] = 'NOW()';
        } else {
            $placeholders[] = '?';
            $types .= bindTypeOf($data[$field]);
            $params[] = $data[$field];
        }
    }

    $sql = "INSERT INTO `" . $table . "` (`" . implode("`,`", $fields) . "`) VALUES (" . implode(',', $placeholders) . ")";
    $stmt = $koneksi->prepare($sql);

    if (!$stmt) {
        throw new Exception("Prepare insert " . $table . " gagal: " . $koneksi->error);
    }

    if ($types !== '') {
        $bindParams = array_merge(array($types), $params);
        call_user_func_array(array($stmt, 'bind_param'), refValues($bindParams));
    }

    if (!$stmt->execute()) {
        $err = $stmt->error;
        $stmt->close();
        throw new Exception("Execute insert " . $table . " gagal: " . $err);
    }

    $insertId = $stmt->insert_id;
    $stmt->close();

    return $insertId;
}

function updateAssoc($koneksi, $table, $data, $whereSql, $whereTypes, $whereParams)
{
    $data = filterExistingColumns($koneksi, $table, $data);

    if (empty($data)) {
        return true;
    }

    $setParts = array();
    foreach ($data as $field => $value) {
        $setParts[] = "`" . $field . "` = ?";
    }

    $sql = "UPDATE `" . $table . "` SET " . implode(', ', $setParts) . " WHERE " . $whereSql;
    $stmt = $koneksi->prepare($sql);

    if (!$stmt) {
        throw new Exception("Prepare update " . $table . " gagal: " . $koneksi->error);
    }

    $types = '';
    $params = array();

    foreach ($data as $field => $value) {
        $types .= bindTypeOf($value);
        $params[] = $value;
    }

    $types .= $whereTypes;
    $params = array_merge($params, $whereParams);

    $bindParams = array_merge(array($types), $params);
    call_user_func_array(array($stmt, 'bind_param'), refValues($bindParams));

    if (!$stmt->execute()) {
        $err = $stmt->error;
        $stmt->close();
        throw new Exception("Execute update " . $table . " gagal: " . $err);
    }

    $stmt->close();
    return true;
}

function deletePrepared($koneksi, $sql, $types, $params)
{
    $stmt = $koneksi->prepare($sql);
    if (!$stmt) {
        throw new Exception("Prepare delete gagal: " . $koneksi->error);
    }

    if ($types !== '' && !empty($params)) {
        $bindParams = array_merge(array($types), $params);
        call_user_func_array(array($stmt, 'bind_param'), refValues($bindParams));
    }

    if (!$stmt->execute()) {
        $err = $stmt->error;
        $stmt->close();
        throw new Exception("Execute delete gagal: " . $err);
    }

    $stmt->close();
    return true;
}

function fetchOldBarangPengembalian($koneksi, $id_ba)
{
    $list = array();

    $sql = "SELECT *
            FROM barang_pengembalian_v2
            WHERE id_ba = ?
            ORDER BY id ASC";

    $stmt = $koneksi->prepare($sql);
    if (!$stmt) {
        throw new Exception("Prepare ambil barang lama gagal: " . $koneksi->error);
    }

    $stmt->bind_param("i", $id_ba);

    if (!$stmt->execute()) {
        $err = $stmt->error;
        $stmt->close();
        throw new Exception("Execute ambil barang lama gagal: " . $err);
    }

    $res = $stmt->get_result();
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $list[] = array(
                'id' => isset($row['id']) ? (int)$row['id'] : 0,
                'id_ba' => isset($row['id_ba']) ? (int)$row['id_ba'] : 0,
                'id_pt' => isset($row['id_pt']) ? (int)$row['id_pt'] : 0,
                'pt' => isset($row['pt']) ? normalizeString($row['pt'], '-') : '-',
                'po' => isset($row['po']) ? normalizeString($row['po'], '-') : '-',
                'coa' => isset($row['coa']) ? normalizeString($row['coa'], '-') : '-',
                'kode_assets' => isset($row['kode_assets']) ? normalizeString($row['kode_assets'], '-') : '-',
                'merk' => isset($row['merk']) ? normalizeString($row['merk'], '-') : '-',
                'sn' => isset($row['sn']) ? normalizeString($row['sn'], '-') : '-',
                'user' => isset($row['user']) ? normalizeString($row['user'], '-') : '-',
                'tahun_perolehan' => isset($row['tahun_perolehan']) ? (int)$row['tahun_perolehan'] : 0,
                'harga_beli' => isset($row['harga_beli']) ? (int)$row['harga_beli'] : 0,
                'keterangan' => isset($row['keterangan']) ? normalizeText($row['keterangan'], '') : '',
                'kondisi' => isset($row['kondisi']) ? normalizeText($row['kondisi'], '') : ''
            );
        }
    }

    $stmt->close();
    return $list;
}

function buildPostedBarangList($post, $pt_map)
{
    $barang_id_pt   = (isset($post['barang_id_pt']) && is_array($post['barang_id_pt'])) ? $post['barang_id_pt'] : array();
    $barang_pt      = (isset($post['barang_pt']) && is_array($post['barang_pt'])) ? $post['barang_pt'] : array();
    $barang_po      = (isset($post['barang_po']) && is_array($post['barang_po'])) ? $post['barang_po'] : array();
    $barang_coa     = (isset($post['barang_coa']) && is_array($post['barang_coa'])) ? $post['barang_coa'] : array();
    $barang_kode    = (isset($post['barang_kode_assets']) && is_array($post['barang_kode_assets'])) ? $post['barang_kode_assets'] : array();
    $barang_merk    = (isset($post['barang_merk']) && is_array($post['barang_merk'])) ? $post['barang_merk'] : array();
    $barang_sn      = (isset($post['barang_sn']) && is_array($post['barang_sn'])) ? $post['barang_sn'] : array();
    $barang_user    = (isset($post['barang_user']) && is_array($post['barang_user'])) ? $post['barang_user'] : array();
    $barang_harga   = (isset($post['barang_harga_beli']) && is_array($post['barang_harga_beli'])) ? $post['barang_harga_beli'] : array();
    $barang_tahun   = (isset($post['barang_tahun_perolehan']) && is_array($post['barang_tahun_perolehan'])) ? $post['barang_tahun_perolehan'] : array();
    $barang_ket     = (isset($post['barang_keterangan']) && is_array($post['barang_keterangan'])) ? $post['barang_keterangan'] : array();
    $barang_kondisi = (isset($post['barang_kondisi']) && is_array($post['barang_kondisi'])) ? $post['barang_kondisi'] : array();

    $count = max(
        count($barang_id_pt),
        count($barang_pt),
        count($barang_po),
        count($barang_coa),
        count($barang_kode),
        count($barang_merk),
        count($barang_sn),
        count($barang_user),
        count($barang_harga),
        count($barang_tahun),
        count($barang_ket),
        count($barang_kondisi)
    );

    $list = array();

    for ($i = 0; $i < $count; $i++) {
        $id_pt = isset($barang_id_pt[$i]) ? normalizeInt($barang_id_pt[$i], 0) : 0;
        $pt    = isset($barang_pt[$i]) ? normalizeString($barang_pt[$i], '-') : '-';

        if ($id_pt <= 0 && isset($pt_map[$pt])) {
            $id_pt = (int)$pt_map[$pt];
        }

        $item = array(
            'id_pt' => $id_pt,
            'pt' => $pt,
            'po' => isset($barang_po[$i]) ? normalizeString($barang_po[$i], '-') : '-',
            'coa' => isset($barang_coa[$i]) ? normalizeString($barang_coa[$i], '-') : '-',
            'kode_assets' => isset($barang_kode[$i]) ? normalizeString($barang_kode[$i], '-') : '-',
            'merk' => isset($barang_merk[$i]) ? normalizeString($barang_merk[$i], '-') : '-',
            'sn' => isset($barang_sn[$i]) ? normalizeString($barang_sn[$i], '-') : '-',
            'user' => isset($barang_user[$i]) ? normalizeString($barang_user[$i], '-') : '-',
            'harga_beli' => isset($barang_harga[$i]) ? normalizeInt($barang_harga[$i], 0) : 0,
            'tahun_perolehan' => isset($barang_tahun[$i]) ? normalizeInt($barang_tahun[$i], 0) : 0,
            'keterangan' => isset($barang_ket[$i]) ? normalizeText($barang_ket[$i], '') : '',
            'kondisi' => isset($barang_kondisi[$i]) ? normalizeText($barang_kondisi[$i], '') : ''
        );

        $isBlank =
            $item['id_pt'] <= 0 &&
            $item['pt'] === '-' &&
            $item['po'] === '-' &&
            $item['coa'] === '-' &&
            $item['kode_assets'] === '-' &&
            $item['merk'] === '-' &&
            $item['sn'] === '-' &&
            $item['user'] === '-' &&
            $item['harga_beli'] <= 0 &&
            $item['tahun_perolehan'] <= 0;

        if ($isBlank) {
            continue;
        }

        if ($item['keterangan'] === '' || $item['kondisi'] === '') {
            throw new Exception("Keterangan dan kondisi wajib diisi untuk setiap barang.");
        }

        if ($item['kondisi'] !== 'Baik' && $item['kondisi'] !== 'Rusak') {
            throw new Exception("Nilai kondisi barang tidak valid.");
        }

        $list[] = $item;
    }

    return $list;
}

function barangSignature($item)
{
    return implode('||', array(
        isset($item['id_pt']) ? (string)$item['id_pt'] : '0',
        isset($item['pt']) ? trim((string)$item['pt']) : '-',
        isset($item['po']) ? trim((string)$item['po']) : '-',
        isset($item['coa']) ? trim((string)$item['coa']) : '-',
        isset($item['kode_assets']) ? trim((string)$item['kode_assets']) : '-',
        isset($item['merk']) ? trim((string)$item['merk']) : '-',
        isset($item['sn']) ? trim((string)$item['sn']) : '-',
        isset($item['user']) ? trim((string)$item['user']) : '-',
        isset($item['tahun_perolehan']) ? (string)(int)$item['tahun_perolehan'] : '0',
        isset($item['harga_beli']) ? (string)(int)$item['harga_beli'] : '0',
        isset($item['keterangan']) ? trim((string)$item['keterangan']) : '',
        isset($item['kondisi']) ? trim((string)$item['kondisi']) : ''
    ));
}

function barangListsDifferent($oldList, $newList)
{
    $oldSig = array();
    $newSig = array();

    foreach ($oldList as $row) {
        $oldSig[] = barangSignature($row);
    }

    foreach ($newList as $row) {
        $newSig[] = barangSignature($row);
    }

    sort($oldSig);
    sort($newSig);

    return $oldSig !== $newSig;
}

function hasAnyApproval($row, $max)
{
    for ($i = 1; $i <= $max; $i++) {
        $field = 'approval_' . $i;
        if (isset($row[$field]) && (int)$row[$field] === 1) {
            return true;
        }
    }
    return false;
}

function findDeptHeadMisHo($koneksi)
{
    $sql = "SELECT nama
            FROM data_karyawan
            WHERE TRIM(jabatan) = 'Dept. Head'
              AND TRIM(departemen) = 'MIS'
            ORDER BY nama ASC
            LIMIT 1";

    $stmt = $koneksi->prepare($sql);
    if (!$stmt) {
        throw new Exception("Prepare pencarian Dept. Head MIS gagal: " . $koneksi->error);
    }

    if (!$stmt->execute()) {
        $err = $stmt->error;
        $stmt->close();
        throw new Exception("Execute pencarian Dept. Head MIS gagal: " . $err);
    }

    $res = $stmt->get_result();
    $nama = '';

    if ($res && $row = $res->fetch_assoc()) {
        $nama = trim((string)$row['nama']);
    }

    $stmt->close();
    return $nama;
}

function resolvePendingApproverPengembalian($koneksi, $pt, $fallback)
{
    $pt = trim((string)$pt);
    $fallback = trim((string)$fallback);

    if ($pt === 'PT.MSAL (HO)') {
        $nama = findDeptHeadMisHo($koneksi);
        if ($nama !== '') {
            return $nama;
        }
    } else {
        $ktu_data = findKtuByPt($koneksi, $pt);
        if ($ktu_data && isset($ktu_data['nama'])) {
            $nama = trim((string)$ktu_data['nama']);
            if ($nama !== '') {
                return $nama;
            }
        }
    }

    return ($fallback !== '') ? $fallback : ' ';
}

function compareMainField($oldValue, $newValue, $label, &$changes)
{
    $oldValue = trim((string)$oldValue);
    $newValue = trim((string)$newValue);

    if ($oldValue !== $newValue) {
        $changes[] = $label . " : " . ($oldValue === '' ? '(-)' : $oldValue) . " diubah ke " . ($newValue === '' ? '(-)' : $newValue);
    }
}

function buildBarangMerkHistoryText($barangList)
{
    $merkList = array();

    if (is_array($barangList)) {
        foreach ($barangList as $row) {
            $merk = isset($row['merk']) ? trim((string)$row['merk']) : '-';
            if ($merk === '') {
                $merk = '-';
            }
            $merkList[] = $merk;
        }
    }

    if (count($merkList) <= 0) {
        return '-';
    }

    return implode(', ', $merkList);
}

function fetchOldImagesPengembalian($koneksi, $id_ba)
{
    $images = array();

    $sql = "SELECT id, file_path, keterangan
            FROM gambar_ba_pengembalian_v2
            WHERE id_ba = ?
            ORDER BY created_at ASC, id ASC";

    $stmt = $koneksi->prepare($sql);
    if (!$stmt) {
        throw new Exception("Prepare ambil gambar lama gagal: " . $koneksi->error);
    }

    $stmt->bind_param("i", $id_ba);

    if (!$stmt->execute()) {
        $err = $stmt->error;
        $stmt->close();
        throw new Exception("Execute ambil gambar lama gagal: " . $err);
    }

    $res = $stmt->get_result();
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $images[] = array(
                'id' => isset($row['id']) ? (int)$row['id'] : 0,
                'file_path' => isset($row['file_path']) ? trim((string)$row['file_path']) : '',
                'keterangan' => isset($row['keterangan']) ? trim((string)$row['keterangan']) : ''
            );
        }
    }

    $stmt->close();
    return $images;
}

function hasAnyUploadedFile($files, $fieldName)
{
    if (
        !isset($files[$fieldName]) ||
        !isset($files[$fieldName]['name']) ||
        !is_array($files[$fieldName]['name'])
    ) {
        return false;
    }

    foreach ($files[$fieldName]['name'] as $idx => $name) {
        $name = trim((string)$name);
        $tmp_name = isset($files[$fieldName]['tmp_name'][$idx]) ? trim((string)$files[$fieldName]['tmp_name'][$idx]) : '';
        $error = isset($files[$fieldName]['error'][$idx]) ? (int)$files[$fieldName]['error'][$idx] : 4;

        if ($name !== '' && $tmp_name !== '' && $error === 0) {
            return true;
        }
    }

    return false;
}

function buildUploadTargetPath($upload_dir, $filename, $tag, $id_number)
{
    $safe_name = safeUploadFileName($filename);

    $ext = pathinfo($safe_name, PATHINFO_EXTENSION);
    $base = pathinfo($safe_name, PATHINFO_FILENAME);

    $base = preg_replace('/[^A-Za-z0-9\-_]/', '_', $base);
    $base = substr($base, 0, 40);

    if ($base === '') {
        $base = 'file';
    }

    $suffix = ($ext !== '') ? '.' . $ext : '';

    return $upload_dir . time() . '_' . mt_rand(1000, 9999) . '_' . $tag . '_' . (int)$id_number . '_' . $base . $suffix;
}

function deleteImageRowPengembalian($koneksi, $id_ba, $old_image)
{
    $id = isset($old_image['id']) ? (int)$old_image['id'] : 0;
    $old_file_path = isset($old_image['file_path']) ? trim((string)$old_image['file_path']) : '';

    if ($id > 0) {
        deletePrepared($koneksi, "DELETE FROM gambar_ba_pengembalian_v2 WHERE id = ? LIMIT 1", "i", array($id));
        return true;
    }

    if ($old_file_path === '') {
        throw new Exception("File path gambar lama kosong.");
    }

    deletePrepared(
        $koneksi,
        "DELETE FROM gambar_ba_pengembalian_v2 WHERE id_ba = ? AND file_path = ? LIMIT 1",
        "is",
        array((int)$id_ba, $old_file_path)
    );

    return true;
}

function updateImageRowPengembalian($koneksi, $id_ba, $old_image, $new_file_path, $new_keterangan)
{
    $id = isset($old_image['id']) ? (int)$old_image['id'] : 0;
    $old_file_path = isset($old_image['file_path']) ? trim((string)$old_image['file_path']) : '';

    if ($id > 0) {
        updateAssoc(
            $koneksi,
            'gambar_ba_pengembalian_v2',
            array(
                'file_path' => $new_file_path,
                'keterangan' => $new_keterangan
            ),
            "id = ?",
            "i",
            array($id)
        );
        return true;
    }

    if ($old_file_path === '') {
        throw new Exception("File path gambar lama kosong untuk update.");
    }

    updateAssoc(
        $koneksi,
        'gambar_ba_pengembalian_v2',
        array(
            'file_path' => $new_file_path,
            'keterangan' => $new_keterangan
        ),
        "id_ba = ? AND file_path = ?",
        "is",
        array((int)$id_ba, $old_file_path)
    );

    return true;
}

function hasImageChangeRequest($post, $files, $oldImages)
{
    if (isset($post['gambar_change_flag']) && trim((string)$post['gambar_change_flag']) === '1') {
        return true;
    }

    foreach ($oldImages as $idx => $oldImage) {
        $hapus_flag = isset($post['hapus_gambar'][$idx]) ? trim((string)$post['hapus_gambar'][$idx]) : '';
        if ($hapus_flag === 'hapus') {
            return true;
        }

        $upload_name = isset($files['gambar_lama_file']['name'][$idx]) ? trim((string)$files['gambar_lama_file']['name'][$idx]) : '';
        $upload_tmp_name = isset($files['gambar_lama_file']['tmp_name'][$idx]) ? trim((string)$files['gambar_lama_file']['tmp_name'][$idx]) : '';
        $upload_error = isset($files['gambar_lama_file']['error'][$idx]) ? (int)$files['gambar_lama_file']['error'][$idx] : 4;

        if ($upload_name !== '' && $upload_tmp_name !== '' && $upload_error === 0) {
            return true;
        }

        $old_keterangan = isset($oldImage['keterangan']) ? trim((string)$oldImage['keterangan']) : '';
        $new_keterangan = isset($post['keterangan_gambar_lama'][$idx]) ? trim((string)$post['keterangan_gambar_lama'][$idx]) : $old_keterangan;

        if ($old_keterangan !== $new_keterangan) {
            return true;
        }
    }

    if (hasAnyUploadedFile($files, 'gambar_baru')) {
        return true;
    }

    return false;
}

function normalizeBaHistorySnapshot($row)
{
    $maxApproval = 3;
    $i = 0;

    for ($i = 1; $i <= $maxApproval; $i++) {
        $approvalField = 'approval_' . $i;
        $autographField = 'autograph_' . $i;

        if (!isset($row[$approvalField]) || $row[$approvalField] === null || $row[$approvalField] === '') {
            $row[$approvalField] = 0;
        } else {
            $row[$approvalField] = (int)$row[$approvalField];
        }

        if (!isset($row[$autographField]) || $row[$autographField] === null) {
            $row[$autographField] = '';
        }
    }

    return $row;
}

/* =========================================================
| Mapping PT
========================================================= */
$pt_map = array(
    'PT.MSAL (HO)'    => 1,
    'PT.MSAL (PKS)'   => 2,
    'PT.MSAL (SITE)'  => 3,
    'PT.PSAM (PKS)'   => 4,
    'PT.PSAM (SITE)'  => 5,
    'PT.MAPA'         => 6,
    'PT.PEAK (PKS)'   => 7,
    'PT.PEAK (SITE)'  => 8,
    'RO PALANGKARAYA' => 9,
    'RO SAMPIT'       => 10,
    'PT.WCJU (SITE)'  => 11,
    'PT.WCJU (PKS)'   => 12
);

/* =========================================================
| Ambil input utama
========================================================= */
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$nomor_ba = isset($_POST['nomor_ba']) ? str_pad(trim((string)$_POST['nomor_ba']), 3, '0', STR_PAD_LEFT) : '';
$tanggal = isset($_POST['tanggal']) ? trim((string)$_POST['tanggal']) : '';
$pt = isset($_POST['pt']) ? trim((string)$_POST['pt']) : '';

$pengembali = isset($_POST['pengembali']) ? trim((string)$_POST['pengembali']) : '';
$jabatan_peminjam = isset($_POST['jabatan_peminjam']) ? trim((string)$_POST['jabatan_peminjam']) : '';
$jabatan_pengembali = isset($_POST['jabatan_pengembali']) ? normalizeString($_POST['jabatan_pengembali'], '-') : '-';

$penerima = isset($_POST['penerima']) ? trim((string)$_POST['penerima']) : '';
$jabatan_penerima = isset($_POST['jabatan_penerima']) ? normalizeString($_POST['jabatan_penerima'], '-') : '-';

$diketahui = isset($_POST['diketahui']) ? trim((string)$_POST['diketahui']) : '';
$jabatan_diketahui = isset($_POST['jabatan_diketahui']) ? normalizeString($_POST['jabatan_diketahui'], '-') : '-';

$is_ho = ($pt === 'PT.MSAL (HO)');

if (!$is_ho && $jabatan_peminjam !== '') {
    $jabatan_pengembali = normalizeString($jabatan_peminjam, '-');
}

$alasan_perubahan = isset($_POST['alasan_perubahan']) ? trim((string)$_POST['alasan_perubahan']) : '';
$nama_sesi = isset($_SESSION['nama']) ? trim((string)$_SESSION['nama']) : '';

if ($id <= 0) {
    $_SESSION['message'] = "ID tidak valid.";
    header("Location: ba_pengembalian.php?status=gagal");
    exit();
}

if (
    $nomor_ba === '' ||
    $tanggal === '' ||
    $pt === '' ||
    $pengembali === '' ||
    $penerima === '' ||
    $diketahui === ''
) {
    $_SESSION['message'] = "Data form utama belum lengkap.";
    header("Location: ba_pengembalian.php?status=gagal");
    exit();
}

if (strcasecmp(trim($pengembali), trim($penerima)) === 0) {
    $_SESSION['message'] = "Nama Peminjam dan Penerima tidak boleh sama.";
    header("Location: ba_pengembalian.php?status=gagal");
    exit();
}

$id_pt = 0;

if (isset($_POST['id_pt']) && (int)$_POST['id_pt'] > 0) {
    $id_pt = (int)$_POST['id_pt'];
} elseif (isset($pt_map[$pt])) {
    $id_pt = (int)$pt_map[$pt];
}

if ($id_pt <= 0) {
    $_SESSION['message'] = "PT tidak valid.";
    header("Location: ba_pengembalian.php?status=gagal");
    exit();
}

if (!$is_ho) {
    if ($jabatan_peminjam === '') {
        $_SESSION['message'] = "Jabatan peminjam wajib diisi untuk PT non HO.";
        header("Location: ba_pengembalian.php?status=gagal");
        exit();
    }

    $ktu_data = findKtuByPt($koneksi, $pt);
    if (!$ktu_data || $ktu_data['nama'] === '') {
        $_SESSION['message'] = "Data KTU untuk PT terpilih tidak ditemukan.";
        header("Location: ba_pengembalian.php?status=gagal");
        exit();
    }

    $diketahui = $ktu_data['nama'];
    $jabatan_diketahui = normalizeString($ktu_data['posisi'], 'KTU');
}

$jabatan_pengembali = normalizeString($jabatan_pengembali, '-');
$jabatan_penerima   = normalizeString($jabatan_penerima, '-');
$jabatan_diketahui  = normalizeString($jabatan_diketahui, '-');

/* =========================================================
| Ambil data lama
========================================================= */
try {
    $stmtOld = $koneksi->prepare("SELECT * FROM berita_acara_pengembalian_v2 WHERE id = ? LIMIT 1");
    if (!$stmtOld) {
        throw new Exception("Prepare ambil data lama gagal: " . $koneksi->error);
    }

    $stmtOld->bind_param("i", $id);

    if (!$stmtOld->execute()) {
        $err = $stmtOld->error;
        $stmtOld->close();
        throw new Exception("Execute ambil data lama gagal: " . $err);
    }

    $resOld = $stmtOld->get_result();
    if (!$resOld || $resOld->num_rows === 0) {
        $stmtOld->close();
        throw new Exception("Data BA Pengembalian tidak ditemukan.");
    }

    $old_data = $resOld->fetch_assoc();
    $stmtOld->close();

    $old_barang_list = fetchOldBarangPengembalian($koneksi, $id);
    $old_images = fetchOldImagesPengembalian($koneksi, $id);

    $new_barang_list = buildPostedBarangList($_POST, $pt_map);
    if (count($new_barang_list) <= 0) {
        throw new Exception("Minimal 1 data barang harus dipilih.");
    }

    /* =========================================================
    | Bangun data baru
    ========================================================= */
    $full_new_ba = $old_data;
    $full_new_ba['tanggal'] = $tanggal;
    $full_new_ba['nomor_ba'] = $nomor_ba;
    $full_new_ba['pt'] = $pt;
    $full_new_ba['id_pt'] = $id_pt;
    $full_new_ba['pengembali'] = $pengembali;
    $full_new_ba['jabatan_pengembali'] = $jabatan_pengembali;
    $full_new_ba['penerima'] = $penerima;
    $full_new_ba['jabatan_penerima'] = $jabatan_penerima;
    $full_new_ba['diketahui'] = $diketahui;
    $full_new_ba['jabatan_diketahui'] = $jabatan_diketahui;

    $pengembali_berubah = (
        trim((string)(isset($old_data['pengembali']) ? $old_data['pengembali'] : '')) !== trim((string)$pengembali) ||
        trim((string)(isset($old_data['jabatan_pengembali']) ? $old_data['jabatan_pengembali'] : '')) !== trim((string)$jabatan_pengembali)
    );

    $penerima_berubah = (
        trim((string)(isset($old_data['penerima']) ? $old_data['penerima'] : '')) !== trim((string)$penerima) ||
        trim((string)(isset($old_data['jabatan_penerima']) ? $old_data['jabatan_penerima'] : '')) !== trim((string)$jabatan_penerima)
    );

    $diketahui_berubah = (
        trim((string)(isset($old_data['diketahui']) ? $old_data['diketahui'] : '')) !== trim((string)$diketahui) ||
        trim((string)(isset($old_data['jabatan_diketahui']) ? $old_data['jabatan_diketahui'] : '')) !== trim((string)$jabatan_diketahui)
    );

    if ($pengembali_berubah) {
        $full_new_ba['approval_1'] = 0;
        $full_new_ba['autograph_1'] = '';
        $full_new_ba['tanggal_approve_1'] = null;
    }

    if ($penerima_berubah) {
        $full_new_ba['approval_2'] = 0;
        $full_new_ba['autograph_2'] = '';
        $full_new_ba['tanggal_approve_2'] = null;
    }

    if ($diketahui_berubah) {
        $full_new_ba['approval_3'] = 0;
        $full_new_ba['autograph_3'] = '';
        $full_new_ba['tanggal_approve_3'] = null;
    }

    $main_update_data = array(
        'tanggal' => $full_new_ba['tanggal'],
        'nomor_ba' => $full_new_ba['nomor_ba'],
        'pt' => $full_new_ba['pt'],
        'id_pt' => (int)$full_new_ba['id_pt'],
        'pengembali' => $full_new_ba['pengembali'],
        'jabatan_pengembali' => $full_new_ba['jabatan_pengembali'],
        'penerima' => $full_new_ba['penerima'],
        'jabatan_penerima' => $full_new_ba['jabatan_penerima'],
        'diketahui' => $full_new_ba['diketahui'],
        'jabatan_diketahui' => $full_new_ba['jabatan_diketahui'],
        'approval_1' => isset($full_new_ba['approval_1']) ? (int)$full_new_ba['approval_1'] : 0,
        'approval_2' => isset($full_new_ba['approval_2']) ? (int)$full_new_ba['approval_2'] : 0,
        'approval_3' => isset($full_new_ba['approval_3']) ? (int)$full_new_ba['approval_3'] : 0,
        'autograph_1' => isset($full_new_ba['autograph_1']) ? $full_new_ba['autograph_1'] : '',
        'autograph_2' => isset($full_new_ba['autograph_2']) ? $full_new_ba['autograph_2'] : '',
        'autograph_3' => isset($full_new_ba['autograph_3']) ? $full_new_ba['autograph_3'] : '',
        'tanggal_approve_1' => isset($full_new_ba['tanggal_approve_1']) ? $full_new_ba['tanggal_approve_1'] : null,
        'tanggal_approve_2' => isset($full_new_ba['tanggal_approve_2']) ? $full_new_ba['tanggal_approve_2'] : null,
        'tanggal_approve_3' => isset($full_new_ba['tanggal_approve_3']) ? $full_new_ba['tanggal_approve_3'] : null
    );

    /* =========================================================
    | Cek perubahan
    ========================================================= */
    $perubahan = array();

    compareMainField(isset($old_data['tanggal']) ? $old_data['tanggal'] : '', $full_new_ba['tanggal'], 'Tanggal', $perubahan);
    compareMainField(isset($old_data['nomor_ba']) ? $old_data['nomor_ba'] : '', $full_new_ba['nomor_ba'], 'Nomor BA', $perubahan);
    compareMainField(isset($old_data['pt']) ? $old_data['pt'] : '', $full_new_ba['pt'], 'PT', $perubahan);

    compareMainField(isset($old_data['pengembali']) ? $old_data['pengembali'] : '', $full_new_ba['pengembali'], 'Peminjam', $perubahan);
    compareMainField(isset($old_data['jabatan_pengembali']) ? $old_data['jabatan_pengembali'] : '', $full_new_ba['jabatan_pengembali'], 'Jabatan Peminjam', $perubahan);

    compareMainField(isset($old_data['penerima']) ? $old_data['penerima'] : '', $full_new_ba['penerima'], 'Penerima', $perubahan);
    compareMainField(isset($old_data['jabatan_penerima']) ? $old_data['jabatan_penerima'] : '', $full_new_ba['jabatan_penerima'], 'Jabatan Penerima', $perubahan);

    compareMainField(isset($old_data['diketahui']) ? $old_data['diketahui'] : '', $full_new_ba['diketahui'], 'Diketahui', $perubahan);
    compareMainField(isset($old_data['jabatan_diketahui']) ? $old_data['jabatan_diketahui'] : '', $full_new_ba['jabatan_diketahui'], 'Jabatan Diketahui', $perubahan);

    $barang_berubah = barangListsDifferent($old_barang_list, $new_barang_list);
    if ($barang_berubah) {
        $barang_lama_text = buildBarangMerkHistoryText($old_barang_list);
        $barang_baru_text = buildBarangMerkHistoryText($new_barang_list);
        $perubahan[] = "Barang : " . $barang_lama_text . " diubah ke " . $barang_baru_text;
    }

    $gambar_berubah = hasImageChangeRequest($_POST, $_FILES, $old_images);

    /* =========================================================
    | Pisahkan perubahan BA+barang dengan gambar
    ========================================================= */
    $ba_barang_berubah = (count($perubahan) > 0);

    if ($ba_barang_berubah && $alasan_perubahan === '') {
        $_SESSION['message'] = "Alasan perubahan wajib diisi.";
        header("Location: ba_pengembalian.php?status=gagal");
        exit();
    }

    if (!$ba_barang_berubah && !$gambar_berubah) {
        $_SESSION['message'] = "Tidak ada perubahan data.";
        header("Location: ba_pengembalian.php?status=gagal");
        exit();
    }

    $histori_text = implode("; ", $perubahan);

    /* =========================================================
    | Mode pending / direct
    ========================================================= */
    $ada_approval = false;
    $pending_status = 0;
    $pending_approver = '';

    /* =========================================================
    | Transaksi
    ========================================================= */
    $koneksi->autocommit(false);

    if ($ba_barang_berubah) {
        $ada_approval = hasAnyApproval($old_data, 3);
        $pending_status = $ada_approval ? 1 : 0;
        $pending_approver = $ada_approval ? resolvePendingApproverPengembalian($koneksi, $pt, $nama_sesi) : '';

        /* ---------------------------------------------------------
        | Bersihkan pending lama jika ada
        --------------------------------------------------------- */
        safeDeleteIfSchemaReady(
            $koneksi,
            'historikal_edit_ba',
            array('id_ba', 'nama_ba', 'pending_status'),
            "DELETE FROM historikal_edit_ba WHERE nama_ba = 'pengembalian' AND id_ba = ? AND pending_status = 1",
            "i",
            array($id)
        );

        safeDeleteIfSchemaReady(
            $koneksi,
            'history_n_temp_ba_pengembalian_v2',
            array('id_ba', 'pending_status', 'status'),
            "DELETE FROM history_n_temp_ba_pengembalian_v2 WHERE id_ba = ? AND pending_status = 1 AND (status = 0 OR status = 1)",
            "i",
            array($id)
        );

        safeDeleteIfSchemaReady(
            $koneksi,
            'history_n_temp_barang_pengembalian_v2',
            array('id_ba', 'pending_status', 'status'),
            "DELETE FROM history_n_temp_barang_pengembalian_v2 WHERE id_ba = ? AND pending_status = 1 AND (status = 0 OR status = 1)",
            "i",
            array($id)
        );

        /* ---------------------------------------------------------
        | Simpan histori ringkas
        --------------------------------------------------------- */
        $pengedit = isset($_SESSION['nama']) ? trim((string)$_SESSION['nama']) : '-';

        $historikal_data = array(
            'id_ba' => $id,
            'nama_ba' => 'pengembalian',
            'pt' => $pt,
            'histori_edit' => $histori_text,
            'pengedit' => $pengedit,
            'tanggal_edit' => 'NOW()',
            'pending_status' => $pending_status
        );

        insertAssoc($koneksi, 'historikal_edit_ba', $historikal_data);

        /* ---------------------------------------------------------
        | Simpan snapshot BA
        --------------------------------------------------------- */
        $file_created_ba = isset($old_data['created_at']) && trim((string)$old_data['created_at']) !== ''
            ? trim((string)$old_data['created_at'])
            : 'NOW()';

        $history_old_ba = $old_data;
        unset($history_old_ba['id']);
        unset($history_old_ba['created_at']);

        $history_old_ba['id_ba'] = $id;
        $history_old_ba['status'] = 0;
        $history_old_ba['pending_status'] = $pending_status;
        $history_old_ba['pending_approver'] = $pending_approver;
        $history_old_ba['alasan_edit'] = $alasan_perubahan;
        $history_old_ba['file_created'] = $file_created_ba;
        $history_old_ba['created_at'] = 'NOW()';
        $history_old_ba = normalizeBaHistorySnapshot($history_old_ba);

        insertAssoc($koneksi, 'history_n_temp_ba_pengembalian_v2', $history_old_ba);

        if ($ada_approval) {
            $history_new_ba = $full_new_ba;
            unset($history_new_ba['id']);
            unset($history_new_ba['created_at']);

            $history_new_ba['id_ba'] = $id;
            $history_new_ba['status'] = 1;
            $history_new_ba['pending_status'] = $pending_status;
            $history_new_ba['pending_approver'] = $pending_approver;
            $history_new_ba['alasan_edit'] = $alasan_perubahan;
            $history_new_ba['file_created'] = $file_created_ba;
            $history_new_ba['created_at'] = 'NOW()';
            $history_new_ba = normalizeBaHistorySnapshot($history_new_ba);

            insertAssoc($koneksi, 'history_n_temp_ba_pengembalian_v2', $history_new_ba);
        }

        /* ---------------------------------------------------------
        | Simpan snapshot barang
        --------------------------------------------------------- */
        $old_barang_count = count($old_barang_list);
        for ($i = 0; $i < $old_barang_count; $i++) {
            $row = $old_barang_list[$i];

            $history_old_barang = array(
                'id_ba' => $id,
                'status' => 0,
                'pending_status' => $pending_status,
                'pending_approver' => $pending_approver,
                'pt' => isset($row['pt']) ? $row['pt'] : '-',
                'id_pt' => isset($row['id_pt']) ? (int)$row['id_pt'] : 0,
                'po' => isset($row['po']) ? $row['po'] : '-',
                'coa' => isset($row['coa']) ? $row['coa'] : '-',
                'kode_assets' => isset($row['kode_assets']) ? $row['kode_assets'] : '-',
                'merk' => isset($row['merk']) ? $row['merk'] : '-',
                'sn' => isset($row['sn']) ? $row['sn'] : '-',
                'user' => isset($row['user']) ? $row['user'] : '-',
                'tahun_perolehan' => isset($row['tahun_perolehan']) ? (int)$row['tahun_perolehan'] : 0,
                'harga_beli' => isset($row['harga_beli']) ? (int)$row['harga_beli'] : 0,
                'kondisi' => isset($row['kondisi']) ? $row['kondisi'] : '',
                'keterangan' => isset($row['keterangan']) ? $row['keterangan'] : '',
                'dihapus' => 0,
                'pending_hapus' => 0,
                'pending_hapus_approver' => '',
                'file_created' => 'NOW()',
                'created_at' => 'NOW()'
            );

            insertAssoc($koneksi, 'history_n_temp_barang_pengembalian_v2', $history_old_barang);
        }

        $new_barang_count = count($new_barang_list);

        if ($ada_approval) {
            for ($i = 0; $i < $new_barang_count; $i++) {
                $row = $new_barang_list[$i];

                $history_new_barang = array(
                    'id_ba' => $id,
                    'status' => 1,
                    'pending_status' => $pending_status,
                    'pending_approver' => $pending_approver,
                    'pt' => isset($row['pt']) ? $row['pt'] : '-',
                    'id_pt' => isset($row['id_pt']) ? (int)$row['id_pt'] : 0,
                    'po' => isset($row['po']) ? $row['po'] : '-',
                    'coa' => isset($row['coa']) ? $row['coa'] : '-',
                    'kode_assets' => isset($row['kode_assets']) ? $row['kode_assets'] : '-',
                    'merk' => isset($row['merk']) ? $row['merk'] : '-',
                    'sn' => isset($row['sn']) ? $row['sn'] : '-',
                    'user' => isset($row['user']) ? $row['user'] : '-',
                    'tahun_perolehan' => isset($row['tahun_perolehan']) ? (int)$row['tahun_perolehan'] : 0,
                    'harga_beli' => isset($row['harga_beli']) ? (int)$row['harga_beli'] : 0,
                    'kondisi' => isset($row['kondisi']) ? $row['kondisi'] : '',
                    'keterangan' => isset($row['keterangan']) ? $row['keterangan'] : '',
                    'dihapus' => 0,
                    'pending_hapus' => 0,
                    'pending_hapus_approver' => '',
                    'file_created' => 'NOW()',
                    'created_at' => 'NOW()'
                );

                insertAssoc($koneksi, 'history_n_temp_barang_pengembalian_v2', $history_new_barang);
            }
        }

        /* ---------------------------------------------------------
        | Jika tidak ada approval, update data utama + barang
        --------------------------------------------------------- */
        if (!$ada_approval) {
            updateAssoc($koneksi, 'berita_acara_pengembalian_v2', $main_update_data, "id = ?", "i", array($id));

            deletePrepared($koneksi, "DELETE FROM barang_pengembalian_v2 WHERE id_ba = ?", "i", array($id));

            for ($i = 0; $i < $new_barang_count; $i++) {
                $row = $new_barang_list[$i];

                $insert_barang = array(
                    'id_ba' => $id,
                    'pt' => isset($row['pt']) ? $row['pt'] : '-',
                    'id_pt' => isset($row['id_pt']) ? (int)$row['id_pt'] : 0,
                    'po' => isset($row['po']) ? $row['po'] : '-',
                    'coa' => isset($row['coa']) ? $row['coa'] : '-',
                    'kode_assets' => isset($row['kode_assets']) ? $row['kode_assets'] : '-',
                    'merk' => isset($row['merk']) ? $row['merk'] : '-',
                    'sn' => isset($row['sn']) ? $row['sn'] : '-',
                    'user' => isset($row['user']) ? $row['user'] : '-',
                    'tahun_perolehan' => isset($row['tahun_perolehan']) ? (int)$row['tahun_perolehan'] : 0,
                    'harga_beli' => isset($row['harga_beli']) ? (int)$row['harga_beli'] : 0,
                    'kondisi' => isset($row['kondisi']) ? $row['kondisi'] : '',
                    'keterangan' => isset($row['keterangan']) ? $row['keterangan'] : ''
                );

                insertAssoc($koneksi, 'barang_pengembalian_v2', $insert_barang);
            }
        }
    }

    /* ---------------------------------------------------------
    | Proses gambar_ba_pengembalian_v2
    | Gambar diproses langsung
    --------------------------------------------------------- */
    $upload_dir = '../assets/database-gambar/';
    ensureDirExists($upload_dir);

    foreach ($old_images as $idx => $old_image) {
        $hapus_flag = isset($_POST['hapus_gambar'][$idx]) ? trim((string)$_POST['hapus_gambar'][$idx]) : '';

        if ($hapus_flag === 'hapus') {
            deleteImageRowPengembalian($koneksi, $id, $old_image);

            $old_file_path = isset($old_image['file_path']) ? trim((string)$old_image['file_path']) : '';
            if ($old_file_path !== '' && file_exists($old_file_path) && is_file($old_file_path)) {
                @unlink($old_file_path);
            }

            continue;
        }

        $old_keterangan = isset($old_image['keterangan']) ? trim((string)$old_image['keterangan']) : '';
        $old_file_path = isset($old_image['file_path']) ? trim((string)$old_image['file_path']) : '';

        $new_keterangan = isset($_POST['keterangan_gambar_lama'][$idx])
            ? trim((string)$_POST['keterangan_gambar_lama'][$idx])
            : $old_keterangan;

        $new_file_path = $old_file_path;

        $upload_name = isset($_FILES['gambar_lama_file']['name'][$idx])
            ? trim((string)$_FILES['gambar_lama_file']['name'][$idx])
            : '';

        $upload_tmp_name = isset($_FILES['gambar_lama_file']['tmp_name'][$idx])
            ? trim((string)$_FILES['gambar_lama_file']['tmp_name'][$idx])
            : '';

        $upload_error = isset($_FILES['gambar_lama_file']['error'][$idx])
            ? (int)$_FILES['gambar_lama_file']['error'][$idx]
            : 4;

        if ($upload_name !== '' && $upload_tmp_name !== '' && $upload_error === 0) {
            $target_path = buildUploadTargetPath($upload_dir, $upload_name, 'lama', $idx);

            if (!move_uploaded_file($upload_tmp_name, $target_path)) {
                throw new Exception("Gagal upload pengganti gambar lama.");
            }

            $new_file_path = $target_path;
        }

        if ($new_file_path !== $old_file_path || $new_keterangan !== $old_keterangan) {
            updateImageRowPengembalian($koneksi, $id, $old_image, $new_file_path, $new_keterangan);

            if ($new_file_path !== $old_file_path) {
                if ($old_file_path !== '' && file_exists($old_file_path) && is_file($old_file_path)) {
                    @unlink($old_file_path);
                }
            }
        }
    }

    if (isset($_FILES['gambar_baru']['name']) && is_array($_FILES['gambar_baru']['name'])) {
        $ket_baru = (isset($_POST['keterangan_gambar_baru']) && is_array($_POST['keterangan_gambar_baru']))
            ? $_POST['keterangan_gambar_baru']
            : array();

        foreach ($_FILES['gambar_baru']['name'] as $idx => $filename) {
            $filename = trim((string)$filename);
            $tmp_name = isset($_FILES['gambar_baru']['tmp_name'][$idx]) ? trim((string)$_FILES['gambar_baru']['tmp_name'][$idx]) : '';
            $error = isset($_FILES['gambar_baru']['error'][$idx]) ? (int)$_FILES['gambar_baru']['error'][$idx] : 4;

            if ($filename === '' || $tmp_name === '' || $error !== 0) {
                continue;
            }

            $target_path = buildUploadTargetPath($upload_dir, $filename, 'baru', $idx);

            if (!move_uploaded_file($tmp_name, $target_path)) {
                throw new Exception("Gagal upload gambar baru.");
            }

            $insert_gambar = array(
                'id_ba' => $id,
                'file_path' => $target_path,
                'keterangan' => isset($ket_baru[$idx]) ? trim((string)$ket_baru[$idx]) : '',
                'created_at' => 'NOW()'
            );

            insertAssoc($koneksi, 'gambar_ba_pengembalian_v2', $insert_gambar);
        }
    }

    /* ---------------------------------------------------------
    | Commit
    --------------------------------------------------------- */
    $koneksi->commit();
    $koneksi->autocommit(true);
    $koneksi->close();

    if ($ba_barang_berubah) {
        if ($ada_approval) {
            if ($gambar_berubah) {
                $_SESSION['message'] = "Perubahan disimpan sebagai pending edit dan menunggu approval. Gambar berhasil diperbarui";
            } else {
                $_SESSION['message'] = "Perubahan disimpan sebagai pending edit dan menunggu approval.";
            }
        } else {
            if ($gambar_berubah) {
                $_SESSION['message'] = "Data berhasil diperbarui ke database. Gambar berhasil diperbarui";
            } else {
                $_SESSION['message'] = "Data berhasil diperbarui ke database.";
            }
        }
    } else {
        $_SESSION['message'] = "Gambar berhasil diperbarui";
    }

    header("Location: ba_pengembalian.php?status=sukses");
    exit();
} catch (Exception $e) {
    if ($koneksi) {
        $koneksi->rollback();
        $koneksi->autocommit(true);
        $koneksi->close();
    }

    error_log("Gagal edit BA Pengembalian: " . $e->getMessage());
    $_SESSION['message'] = "Gagal menyimpan perubahan: " . $e->getMessage();
    header("Location: ba_pengembalian.php?status=gagal");
    exit();
}