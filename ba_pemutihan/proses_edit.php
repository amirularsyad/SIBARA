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
    header("Location: ba_pemutihan.php?status=gagal");
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

function getPosisiKaryawanTest($koneksi, $nama, $pt)
{
    $hasil = '-';

    $nama = trim((string)$nama);
    $pt   = trim((string)$pt);

    if ($nama === '' || $nama === '-' || $pt === '' || $pt === '-') {
        return $hasil;
    }

    $sql = "SELECT posisi
            FROM data_karyawan_test
            WHERE nama = ?
              AND (
                    TRIM(pt) = ?
                    OR FIND_IN_SET(?, REPLACE(REPLACE(REPLACE(pt,'; ',','), ';', ','), ', ', ',')) > 0
                  )
            LIMIT 1";

    $stmt = $koneksi->prepare($sql);
    if (!$stmt) {
        return $hasil;
    }

    $stmt->bind_param("sss", $nama, $pt, $pt);

    if (!$stmt->execute()) {
        $stmt->close();
        return $hasil;
    }

    $posisi = '';
    $stmt->bind_result($posisi);

    if ($stmt->fetch()) {
        $posisi = trim((string)$posisi);
        if ($posisi !== '') {
            $hasil = $posisi;
        }
    }

    $stmt->close();
    return $hasil;
}

function getJabatanLengkapDataKaryawan($koneksi, $nama)
{
    $hasil = '-';
    $nama = trim((string)$nama);

    if ($nama === '' || $nama === '-') {
        return $hasil;
    }

    $jabatan = '';
    $posisi = '';
    $departemen = '';

    $sql = "SELECT jabatan, posisi, departemen
            FROM data_karyawan
            WHERE nama = ?
            LIMIT 1";

    $stmt = $koneksi->prepare($sql);
    if (!$stmt) {
        return $hasil;
    }

    $stmt->bind_param("s", $nama);

    if (!$stmt->execute()) {
        $stmt->close();
        return $hasil;
    }

    $stmt->bind_result($jabatan, $posisi, $departemen);

    if ($stmt->fetch()) {
        $jabatan    = trim((string)$jabatan);
        $posisi     = trim((string)$posisi);
        $departemen = trim((string)$departemen);

        $hasil = '';

        if ($jabatan !== '') {
            $hasil .= $jabatan;
        }

        if ($departemen !== '') {
            if ($hasil !== '') {
                $hasil .= ' ';
            }
            $hasil .= $departemen;
        }

        if ($posisi !== '') {
            if ($hasil !== '') {
                $hasil .= ' ';
            }
            $hasil .= '(' . $posisi . ')';
        }

        if (trim($hasil) === '') {
            $hasil = '-';
        }
    }

    $stmt->close();
    return $hasil;
}

function getNamaKaryawanByJabatanDepartemen($koneksi, $jabatan, $departemen)
{
    $nama = '-';
    $sql = "SELECT nama
            FROM data_karyawan
            WHERE jabatan = ?
              AND departemen = ?
            LIMIT 1";

    $stmt = $koneksi->prepare($sql);
    if (!$stmt) {
        return $nama;
    }

    $stmt->bind_param("ss", $jabatan, $departemen);

    if (!$stmt->execute()) {
        $stmt->close();
        return $nama;
    }

    $namaDb = '';
    $stmt->bind_result($namaDb);

    if ($stmt->fetch()) {
        $namaDb = trim((string)$namaDb);
        if ($namaDb !== '') {
            $nama = $namaDb;
        }
    }

    $stmt->close();
    return $nama;
}

function getNamaKaryawanTestByPosisi($koneksi, $posisiCari, $pt)
{
    $hasil = '-';

    $posisiCari = trim((string)$posisiCari);
    $pt         = trim((string)$pt);

    if ($posisiCari === '' || $pt === '') {
        return $hasil;
    }

    $sql = "SELECT nama
            FROM data_karyawan_test
            WHERE posisi = ?
              AND (
                    TRIM(pt) = ?
                    OR FIND_IN_SET(?, REPLACE(REPLACE(REPLACE(pt,'; ',','), ';', ','), ', ', ',')) > 0
                    OR FIND_IN_SET(?, REPLACE(pt, ' ', '')) > 0
                  )
            LIMIT 1";

    $stmt = $koneksi->prepare($sql);
    if (!$stmt) {
        return $hasil;
    }

    $ptNoSpace = str_replace(' ', '', $pt);
    $stmt->bind_param("ssss", $posisiCari, $pt, $pt, $ptNoSpace);

    if (!$stmt->execute()) {
        $stmt->close();
        return $hasil;
    }

    $nama = '';
    $stmt->bind_result($nama);

    if ($stmt->fetch()) {
        $nama = trim((string)$nama);
        if ($nama !== '') {
            $hasil = $nama;
        }
    }

    $stmt->close();
    return $hasil;
}

function resetApprovalActorFields(&$row, $number)
{
    $approvalField = 'approval_' . (int)$number;
    $autographField = 'autograph_' . (int)$number;
    $tanggalField = 'tanggal_approve_' . (int)$number;

    $row[$approvalField] = 0;
    $row[$autographField] = '';
    $row[$tanggalField] = null;
}

function detectHoDepartemen($value)
{
    $value = strtoupper(trim((string)$value));

    if ($value === '') {
        return '';
    }

    if (strpos($value, 'HRO') !== false) {
        return 'HRO';
    }

    if (strpos($value, 'MIS') !== false) {
        return 'MIS';
    }

    return '';
}

function getJabatanPembuatPemeriksa($koneksi, $nama, $pt, $fallback)
{
    $nama = trim((string)$nama);
    $pt   = trim((string)$pt);

    if ($nama === '' || $nama === '-') {
        return normalizeString($fallback, '-');
    }

    if ($pt === 'PT.MSAL (HO)') {
        $jabatan = getJabatanLengkapDataKaryawan($koneksi, $nama);
        if ($jabatan !== '-') {
            return $jabatan;
        }
    } else {
        $jabatan = getPosisiKaryawanTest($koneksi, $nama, $pt);
        if ($jabatan !== '-') {
            return $jabatan;
        }
    }

    return normalizeString($fallback, '-');
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

function fetchOldBarangPemutihan($koneksi, $id_ba)
{
    $list = array();

    $sql = "SELECT *
            FROM barang_pemutihan
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
                'id'                  => isset($row['id']) ? (int)$row['id'] : 0,
                'id_ba'               => isset($row['id_ba']) ? (int)$row['id_ba'] : 0,
                'id_pt'               => isset($row['id_pt']) ? (int)$row['id_pt'] : 0,
                'pt'                  => isset($row['pt']) ? normalizeString($row['pt'], '-') : '-',
                'po'                  => isset($row['po']) ? normalizeString($row['po'], '-') : '-',
                'coa'                 => isset($row['coa']) ? normalizeString($row['coa'], '-') : '-',
                'kode_assets'         => isset($row['kode_assets']) ? normalizeString($row['kode_assets'], '-') : '-',
                'merk'                => isset($row['merk']) ? normalizeString($row['merk'], '-') : '-',
                'sn'                  => isset($row['sn']) ? normalizeString($row['sn'], '-') : '-',
                'user'                => isset($row['user']) ? normalizeString($row['user'], '-') : '-',
                'harga_beli'          => isset($row['harga_beli']) ? (int)$row['harga_beli'] : 0,
                'tahun_perolehan'     => isset($row['tahun_perolehan']) ? (int)$row['tahun_perolehan'] : 0,
                'alasan_penghapusan'  => isset($row['alasan_penghapusan']) ? normalizeText($row['alasan_penghapusan'], '') : '',
                'kondisi'             => isset($row['kondisi']) ? normalizeText($row['kondisi'], '') : ''
            );
        }
    }

    $stmt->close();
    return $list;
}

function buildPostedBarangList($post, $pt_map)
{
    $barang_id_pt   = (isset($post['barang_id_pt']) && is_array($post['barang_id_pt'])) ? $post['barang_id_pt'] : array();
    $barang_pt      = (isset($post['barang_pt_asal']) && is_array($post['barang_pt_asal'])) ? $post['barang_pt_asal'] : array();
    $barang_po      = (isset($post['barang_po']) && is_array($post['barang_po'])) ? $post['barang_po'] : array();
    $barang_coa     = (isset($post['barang_coa']) && is_array($post['barang_coa'])) ? $post['barang_coa'] : array();
    $barang_kode    = (isset($post['barang_kode_assets']) && is_array($post['barang_kode_assets'])) ? $post['barang_kode_assets'] : array();
    $barang_merk    = (isset($post['barang_merk']) && is_array($post['barang_merk'])) ? $post['barang_merk'] : array();
    $barang_sn      = (isset($post['barang_sn']) && is_array($post['barang_sn'])) ? $post['barang_sn'] : array();
    $barang_user    = (isset($post['barang_user']) && is_array($post['barang_user'])) ? $post['barang_user'] : array();
    $barang_harga   = (isset($post['barang_harga_beli']) && is_array($post['barang_harga_beli'])) ? $post['barang_harga_beli'] : array();
    $barang_tahun   = (isset($post['barang_tahun_perolehan']) && is_array($post['barang_tahun_perolehan'])) ? $post['barang_tahun_perolehan'] : array();
    $barang_alasan  = (isset($post['barang_alasan_penghapusan']) && is_array($post['barang_alasan_penghapusan'])) ? $post['barang_alasan_penghapusan'] : array();
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
        count($barang_alasan),
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
            'id_pt'              => $id_pt,
            'pt'                 => $pt,
            'po'                 => isset($barang_po[$i]) ? normalizeString($barang_po[$i], '-') : '-',
            'coa'                => isset($barang_coa[$i]) ? normalizeString($barang_coa[$i], '-') : '-',
            'kode_assets'        => isset($barang_kode[$i]) ? normalizeString($barang_kode[$i], '-') : '-',
            'merk'               => isset($barang_merk[$i]) ? normalizeString($barang_merk[$i], '-') : '-',
            'sn'                 => isset($barang_sn[$i]) ? normalizeString($barang_sn[$i], '-') : '-',
            'user'               => isset($barang_user[$i]) ? normalizeString($barang_user[$i], '-') : '-',
            'harga_beli'         => isset($barang_harga[$i]) ? normalizeInt($barang_harga[$i], 0) : 0,
            'tahun_perolehan'    => isset($barang_tahun[$i]) ? normalizeInt($barang_tahun[$i], 0) : 0,
            'alasan_penghapusan' => isset($barang_alasan[$i]) ? normalizeText($barang_alasan[$i], '') : '',
            'kondisi'            => isset($barang_kondisi[$i]) ? normalizeText($barang_kondisi[$i], '') : ''
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

        if ($item['alasan_penghapusan'] === '' || $item['kondisi'] === '') {
            throw new Exception("Alasan penghapusan dan kondisi wajib diisi untuk setiap barang.");
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
        isset($item['harga_beli']) ? (string)(int)$item['harga_beli'] : '0',
        isset($item['tahun_perolehan']) ? (string)(int)$item['tahun_perolehan'] : '0',
        isset($item['alasan_penghapusan']) ? trim((string)$item['alasan_penghapusan']) : '',
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

function resolvePendingApproverPemutihan($koneksi, $ptSurat, $namaSession)
{
    $ptSurat = trim((string)$ptSurat);
    $namaSession = trim((string)$namaSession);

    if ($ptSurat === 'PT.MSAL (HO)') {
        $sql = "SELECT nama
                FROM data_karyawan
                WHERE jabatan = 'Dept. Head'
                  AND departemen = 'MIS'
                LIMIT 1";

        $res = $koneksi->query($sql);
        if ($res && $row = $res->fetch_assoc()) {
            $nama = isset($row['nama']) ? trim((string)$row['nama']) : '';
            if ($nama !== '') {
                return $nama;
            }
        }
    } else {
        $sql = "SELECT nama
                FROM data_karyawan_test
                WHERE posisi = 'KTU'
                  AND (
                        TRIM(pt) = ?
                        OR FIND_IN_SET(?, REPLACE(REPLACE(REPLACE(pt,'; ',','), ';', ','), ', ', ',')) > 0
                      )
                LIMIT 1";

        $stmt = $koneksi->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("ss", $ptSurat, $ptSurat);

            if ($stmt->execute()) {
                $res = $stmt->get_result();
                if ($res && $row = $res->fetch_assoc()) {
                    $nama = isset($row['nama']) ? trim((string)$row['nama']) : '';
                    if ($nama !== '') {
                        $stmt->close();
                        return $nama;
                    }
                }
            }

            $stmt->close();
        }
    }

    return ($namaSession !== '') ? $namaSession : ' ';
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

function fetchOldImagesPemutihan($koneksi, $id_ba)
{
    $images = array();

    $sql = "SELECT id, file_path, keterangan
            FROM gambar_ba_pemutihan
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

function deleteImageRowPemutihan($koneksi, $id_ba, $old_image)
{
    $id = isset($old_image['id']) ? (int)$old_image['id'] : 0;
    $old_file_path = isset($old_image['file_path']) ? trim((string)$old_image['file_path']) : '';

    if ($id > 0) {
        deletePrepared($koneksi, "DELETE FROM gambar_ba_pemutihan WHERE id = ? LIMIT 1", "i", array($id));
        return true;
    }

    if ($old_file_path === '') {
        throw new Exception("File path gambar lama kosong.");
    }

    deletePrepared(
        $koneksi,
        "DELETE FROM gambar_ba_pemutihan WHERE id_ba = ? AND file_path = ? LIMIT 1",
        "is",
        array((int)$id_ba, $old_file_path)
    );

    return true;
}

function updateImageRowPemutihan($koneksi, $id_ba, $old_image, $new_file_path, $new_keterangan)
{
    $id = isset($old_image['id']) ? (int)$old_image['id'] : 0;
    $old_file_path = isset($old_image['file_path']) ? trim((string)$old_image['file_path']) : '';

    if ($id > 0) {
        updateAssoc(
            $koneksi,
            'gambar_ba_pemutihan',
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
        'gambar_ba_pemutihan',
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
    $maxApproval = 16;
    $i = 0;

    for ($i = 1; $i <= $maxApproval; $i++) {
        $approvalField = 'approval_' . $i;
        $autographField = 'autograph_' . $i;
        $tanggalField = 'tanggal_approve_' . $i;

        if (!isset($row[$approvalField]) || $row[$approvalField] === null || $row[$approvalField] === '') {
            $row[$approvalField] = 0;
        } else {
            $row[$approvalField] = (int)$row[$approvalField];
        }

        if (!isset($row[$autographField]) || $row[$autographField] === null) {
            $row[$autographField] = '';
        }

        if (!isset($row[$tanggalField])) {
            $row[$tanggalField] = null;
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

$pt_site_list = array(
    'PT.MSAL (SITE)',
    'PT.PSAM (SITE)',
    'PT.MAPA',
    'PT.PEAK (SITE)',
    'PT.WCJU (SITE)'
);

$pt_pks_list = array(
    'PT.MSAL (PKS)',
    'PT.PSAM (PKS)',
    'PT.PEAK (PKS)',
    'PT.WCJU (PKS)'
);

$kebun_1_list = array(
    'PT.MSAL (HO)',
    'PT.MSAL (PKS)',
    'PT.MSAL (SITE)',
    'PT.PSAM (PKS)',
    'PT.PSAM (SITE)',
    'PT.MAPA'
);

/* =========================================================
| Ambil input utama
========================================================= */
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$nomor_ba = isset($_POST['nomor_ba']) ? str_pad(trim((string)$_POST['nomor_ba']), 3, '0', STR_PAD_LEFT) : '';
$tanggal = isset($_POST['tanggal']) ? trim((string)$_POST['tanggal']) : '';
$pt = isset($_POST['pt']) ? trim((string)$_POST['pt']) : '';

$pembuat = isset($_POST['pembuat']) ? trim((string)$_POST['pembuat']) : '';
$pemeriksa = isset($_POST['pemeriksa']) ? trim((string)$_POST['pemeriksa']) : '';
$jabatan_pembuat_hidden = isset($_POST['jabatan_pembuat']) ? trim((string)$_POST['jabatan_pembuat']) : '';
$jabatan_pemeriksa_hidden = isset($_POST['jabatan_pemeriksa']) ? trim((string)$_POST['jabatan_pemeriksa']) : '';

/* input khusus HO dan non-HO terbaru */
$departemen_pengguna = isset($_POST['departemen_pengguna']) ? trim((string)$_POST['departemen_pengguna']) : '';
$dept_pengguna_post = isset($_POST['dept_pengguna']) ? trim((string)$_POST['dept_pengguna']) : '';
$jabatan_dept_pengguna_post = isset($_POST['jabatan_dept_pengguna']) ? trim((string)$_POST['jabatan_dept_pengguna']) : '';

$alasan_perubahan = isset($_POST['alasan_perubahan']) ? trim((string)$_POST['alasan_perubahan']) : '';
$nama_sesi = isset($_SESSION['nama']) ? trim((string)$_SESSION['nama']) : '';

if ($id <= 0) {
    $_SESSION['message'] = "ID tidak valid.";
    header("Location: ba_pemutihan.php?status=gagal");
    exit();
}

if ($nomor_ba === '' || $tanggal === '' || $pt === '') {
    $_SESSION['message'] = "Data form utama belum lengkap.";
    header("Location: ba_pemutihan.php?status=gagal");
    exit();
}

// var_dump($departemen_pengguna);
// var_dump($dept_pengguna_post);
// var_dump($jabatan_dept_pengguna_post);
// exit;

if ($pt === 'PT.MSAL (HO)') {
    if ($departemen_pengguna === '' || $dept_pengguna_post === '' || $jabatan_dept_pengguna_post === '') {
        $_SESSION['message'] = "Data pengguna HO belum lengkap.";
        header("Location: ba_pemutihan.php?status=gagal");
        exit();
    }
} else {
    if ($dept_pengguna_post === '' || $jabatan_dept_pengguna_post === '') {
        $_SESSION['message'] = "Data pengguna non HO belum lengkap.";
        header("Location: ba_pemutihan.php?status=gagal");
        exit();
    }
}

$id_pt = 0;

if (isset($_POST['id_pt']) && (int)$_POST['id_pt'] > 0) {
    $id_pt = (int)$_POST['id_pt'];
} elseif (isset($pt_map[$pt])) {
    $id_pt = (int)$pt_map[$pt];
}

if ($id_pt <= 0) {
    $_SESSION['message'] = "PT tidak valid.";
    header("Location: ba_pemutihan.php?status=gagal");
    exit();
}

/* =========================================================
| Ambil data lama
========================================================= */
try {
    $stmtOld = $koneksi->prepare("SELECT * FROM berita_acara_pemutihan WHERE id = ? LIMIT 1");
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
        throw new Exception("Data BA Pemutihan tidak ditemukan.");
    }

    $old_data = $resOld->fetch_assoc();
    $stmtOld->close();

    $old_barang_list = fetchOldBarangPemutihan($koneksi, $id);
    $old_images = fetchOldImagesPemutihan($koneksi, $id);

    $old_is_site_mode = (isset($old_data['pt']) && trim((string)$old_data['pt']) !== 'PT.MSAL (HO)');
    $new_is_site_mode = ($pt !== 'PT.MSAL (HO)');

    if ($old_is_site_mode !== $new_is_site_mode) {
        throw new Exception("Perpindahan mode PT HO dan non HO tidak diizinkan.");
    }

    $new_barang_list = buildPostedBarangList($_POST, $pt_map);
    if (count($new_barang_list) <= 0) {
        throw new Exception("Minimal 1 data barang harus dipilih.");
    }

    /* =========================================================
    | Bangun data baru
    ========================================================= */
    $is_site_mode = ($pt !== 'PT.MSAL (HO)');

    $full_new_ba = $old_data;
    $full_new_ba['tanggal'] = $tanggal;
    $full_new_ba['nomor_ba'] = $nomor_ba;
    $full_new_ba['pt'] = $pt;
    $full_new_ba['id_pt'] = $id_pt;

    if (!$is_site_mode) {
        $departemen_pengguna = strtoupper(trim((string)$departemen_pengguna));

        $dept_pengguna_baru = getNamaKaryawanByJabatanDepartemen($koneksi, 'Dept. Head', $departemen_pengguna);
        $jabatan_dept_pengguna_baru = getJabatanLengkapDataKaryawan($koneksi, $dept_pengguna_baru);

        if ($dept_pengguna_baru === '-' && $dept_pengguna_post !== '') {
            $dept_pengguna_baru = $dept_pengguna_post;
        }
        if ($jabatan_dept_pengguna_baru === '-' && $jabatan_dept_pengguna_post !== '') {
            $jabatan_dept_pengguna_baru = $jabatan_dept_pengguna_post;
        }

        $dept_hrops_baru = getNamaKaryawanByJabatanDepartemen($koneksi, 'Dept. Head', 'HRO');
        $jabatan_dept_hrops_baru = getJabatanLengkapDataKaryawan($koneksi, $dept_hrops_baru);

        if ($departemen_pengguna === 'HRO') {
            $dept_hrops_baru = '-';
            $jabatan_dept_hrops_baru = '-';
        }

        $full_new_ba['dept_pengguna'] = $dept_pengguna_baru;
        $full_new_ba['jabatan_dept_pengguna'] = $jabatan_dept_pengguna_baru;
        $full_new_ba['dept_hrops'] = $dept_hrops_baru;
        $full_new_ba['jabatan_dept_hrops'] = $jabatan_dept_hrops_baru;

        $dept_pengguna_lama = isset($old_data['dept_pengguna']) ? trim((string)$old_data['dept_pengguna']) : '';
        $jabatan_dept_pengguna_lama = isset($old_data['jabatan_dept_pengguna']) ? trim((string)$old_data['jabatan_dept_pengguna']) : '';
        $dept_hrops_lama = isset($old_data['dept_hrops']) ? trim((string)$old_data['dept_hrops']) : '';
        $jabatan_dept_hrops_lama = isset($old_data['jabatan_dept_hrops']) ? trim((string)$old_data['jabatan_dept_hrops']) : '';

        $departemen_lama = detectHoDepartemen($jabatan_dept_pengguna_lama);
        $departemen_baru = $departemen_pengguna;

        $aktor_ho_berubah =
            ($dept_pengguna_lama !== $dept_pengguna_baru) ||
            ($jabatan_dept_pengguna_lama !== $jabatan_dept_pengguna_baru) ||
            ($dept_hrops_lama !== $dept_hrops_baru) ||
            ($jabatan_dept_hrops_lama !== $jabatan_dept_hrops_baru);

        $sudah_ada_approve = hasAnyApproval($old_data, 16);

        if ($aktor_ho_berubah && $sudah_ada_approve) {
            if ($departemen_lama !== 'HRO' && $departemen_baru === 'HRO') {
                $full_new_ba['approval_1'] = 0;
                $full_new_ba['autograph_1'] = '';
                $full_new_ba['tanggal_approve_1'] = null;

                $full_new_ba['approval_9'] = 0;
                $full_new_ba['autograph_9'] = '';
                $full_new_ba['tanggal_approve_9'] = null;
            } elseif ($departemen_lama === 'HRO' && $departemen_baru !== 'HRO') {
                $full_new_ba['approval_1'] = 0;
                $full_new_ba['autograph_1'] = '';
                $full_new_ba['tanggal_approve_1'] = null;
            } elseif ($departemen_lama !== 'HRO' && $departemen_baru !== 'HRO') {
                $full_new_ba['approval_1'] = 0;
                $full_new_ba['autograph_1'] = '';
                $full_new_ba['tanggal_approve_1'] = null;
            }
        }

        $main_update_data = array(
            'tanggal' => $full_new_ba['tanggal'],
            'nomor_ba' => $full_new_ba['nomor_ba'],
            'pt' => $full_new_ba['pt'],
            'id_pt' => (int)$full_new_ba['id_pt'],
            'dept_pengguna' => $full_new_ba['dept_pengguna'],
            'jabatan_dept_pengguna' => $full_new_ba['jabatan_dept_pengguna'],
            'dept_hrops' => $full_new_ba['dept_hrops'],
            'jabatan_dept_hrops' => $full_new_ba['jabatan_dept_hrops'],
            'approval_1' => isset($full_new_ba['approval_1']) ? (int)$full_new_ba['approval_1'] : 0,
            'approval_9' => isset($full_new_ba['approval_9']) ? (int)$full_new_ba['approval_9'] : 0,
            'autograph_1' => isset($full_new_ba['autograph_1']) ? $full_new_ba['autograph_1'] : '',
            'autograph_9' => isset($full_new_ba['autograph_9']) ? $full_new_ba['autograph_9'] : '',
            'tanggal_approve_1' => isset($full_new_ba['tanggal_approve_1']) ? $full_new_ba['tanggal_approve_1'] : null,
            'tanggal_approve_9' => isset($full_new_ba['tanggal_approve_9']) ? $full_new_ba['tanggal_approve_9'] : null
        );

    } else {
        $old_pt = isset($old_data['pt']) ? trim((string)$old_data['pt']) : '';
        $new_pt = $pt;

        $old_is_pks = in_array($old_pt, $pt_pks_list, true);
        $new_is_pks = in_array($new_pt, $pt_pks_list, true);

        $old_is_kebun_1 = in_array($old_pt, $kebun_1_list, true);
        $new_is_kebun_1 = in_array($new_pt, $kebun_1_list, true);

        /* aktor dasar non-HO mengikuti HO */
        $full_new_ba['dept_pengguna'] = $dept_pengguna_post;
        $full_new_ba['jabatan_dept_pengguna'] = $jabatan_dept_pengguna_post;

        $dept_hrops = getNamaKaryawanByJabatanDepartemen($koneksi, 'Dept. Head', 'HRO');
        $jabatan_dept_hrops = getJabatanLengkapDataKaryawan($koneksi, $dept_hrops);

        $dept_hrd = getNamaKaryawanByJabatanDepartemen($koneksi, 'Dept. Head', 'HRD');
        $jabatan_dept_hrd = getJabatanLengkapDataKaryawan($koneksi, $dept_hrd);

        $dept_accounting = getNamaKaryawanByJabatanDepartemen($koneksi, 'Dept. Head', 'ACCOUNTING');
        $jabatan_dept_accounting = getJabatanLengkapDataKaryawan($koneksi, $dept_accounting);

        $dir_operation = getNamaKaryawanByJabatanDepartemen($koneksi, 'Direktur', 'HRD');
        $jabatan_dir_operation = getJabatanLengkapDataKaryawan($koneksi, $dir_operation);

        $dir_finance = getNamaKaryawanByJabatanDepartemen($koneksi, 'Direktur', 'FINANCE');
        $jabatan_dir_finance = getJabatanLengkapDataKaryawan($koneksi, $dir_finance);

        $dir_hr = getNamaKaryawanByJabatanDepartemen($koneksi, 'Direktur', 'HRD');
        $jabatan_dir_hr = getJabatanLengkapDataKaryawan($koneksi, $dir_hr);

        $vice_ceo = getNamaKaryawanByJabatanDepartemen($koneksi, 'VICE CEO', 'BOD');
        if ($vice_ceo === '-') {
            $vice_ceo = getNamaKaryawanByJabatanDepartemen($koneksi, 'Vice CEO', 'BOD');
        }
        if ($vice_ceo === '-') {
            $vice_ceo = getNamaKaryawanByJabatanDepartemen($koneksi, 'VICE CEO', '');
        }
        $jabatan_vice_ceo = getJabatanLengkapDataKaryawan($koneksi, $vice_ceo);

        $ceo = getNamaKaryawanByJabatanDepartemen($koneksi, 'CEO', 'BOD');

        $jabatan_ceo = getJabatanLengkapDataKaryawan($koneksi, $ceo);
        if ($jabatan_ceo === '-') {
            $jabatan_ceo = 'CEO';
        }

        $full_new_ba['dept_hrops'] = $dept_hrops;
        $full_new_ba['jabatan_dept_hrops'] = $jabatan_dept_hrops;

        $full_new_ba['dept_hrd'] = $dept_hrd;
        $full_new_ba['jabatan_dept_hrd'] = $jabatan_dept_hrd;

        $full_new_ba['dept_accounting'] = $dept_accounting;
        $full_new_ba['jabatan_dept_accounting'] = $jabatan_dept_accounting;

        $full_new_ba['dir_operation'] = $dir_operation;
        $full_new_ba['jabatan_dir_operation'] = $jabatan_dir_operation;

        $full_new_ba['dir_finance'] = $dir_finance;
        $full_new_ba['jabatan_dir_finance'] = $jabatan_dir_finance;

        $full_new_ba['dir_hr'] = $dir_hr;
        $full_new_ba['jabatan_dir_hr'] = $jabatan_dir_hr;

        $full_new_ba['vice_ceo'] = $vice_ceo;
        $full_new_ba['jabatan_vice_ceo'] = $jabatan_vice_ceo;

        $full_new_ba['ceo'] = $ceo;
        $full_new_ba['jabatan_ceo'] = $jabatan_ceo;

        /* non-HO: HRO/HROPS tidak pakai logika khusus HRO */
        /* jadi tidak ada detectHoDepartemen / departemen_pengguna HRO */

        /* default tambahan aktor */
        $full_new_ba['asisten_pga'] = '-';
        $full_new_ba['jabatan_asisten_pga'] = '-';
        $full_new_ba['ktu'] = '-';
        $full_new_ba['jabatan_ktu'] = '-';
        $full_new_ba['group_manager'] = '-';
        $full_new_ba['jabatan_group_manager'] = '-';

        $full_new_ba['kepala_mill'] = '-';
        $full_new_ba['jabatan_kepala_mill'] = '-';
        $full_new_ba['area_mill_controller'] = '-';
        $full_new_ba['jabatan_area_mill_controller'] = '-';
        $full_new_ba['dept_avp_engineering'] = '-';
        $full_new_ba['jabatan_dept_avp_engineering'] = '-';

        $full_new_ba['vice_president'] = '-';
        $full_new_ba['jabatan_vice_president'] = '-';

        /* SITE + PKS */
        if (in_array($new_pt, $pt_site_list, true) || in_array($new_pt, $pt_pks_list, true)) {
            $full_new_ba['asisten_pga'] = getNamaKaryawanTestByPosisi($koneksi, 'Staf GA', $new_pt);
            $full_new_ba['jabatan_asisten_pga'] = 'Asisten PGA';

            $full_new_ba['ktu'] = getNamaKaryawanTestByPosisi($koneksi, 'KTU', $new_pt);
            $full_new_ba['jabatan_ktu'] = 'KTU';

            $full_new_ba['group_manager'] = getNamaKaryawanTestByPosisi($koneksi, 'GM', $new_pt);
            $full_new_ba['jabatan_group_manager'] = 'GM';
        }

        /* khusus PKS */
        if ($new_is_pks) {
            $full_new_ba['kepala_mill'] = getNamaKaryawanTestByPosisi($koneksi, 'Kepala Mill', $new_pt);
            $full_new_ba['jabatan_kepala_mill'] = 'Kepala Mill';

            $full_new_ba['area_mill_controller'] = getNamaKaryawanTestByPosisi($koneksi, 'AMC', $new_pt);
            $full_new_ba['jabatan_area_mill_controller'] = 'AMC';

            $full_new_ba['dept_avp_engineering'] = getNamaKaryawanByJabatanDepartemen($koneksi, 'AVP', 'ENGINEERING');
            $full_new_ba['jabatan_dept_avp_engineering'] = 'AVP Engineering';
        }

        /* khusus kebun 1 */
        if ($new_is_kebun_1) {
            $full_new_ba['vice_president'] = getNamaKaryawanTestByPosisi($koneksi, 'Vice President', $new_pt);
            $full_new_ba['jabatan_vice_president'] = 'Vice President';
        }

        /* reset approval_1 jika dept_pengguna non-HO berubah */
        $dept_pengguna_lama = isset($old_data['dept_pengguna']) ? trim((string)$old_data['dept_pengguna']) : '';
        $dept_pengguna_baru = isset($full_new_ba['dept_pengguna']) ? trim((string)$full_new_ba['dept_pengguna']) : '';

        if ($dept_pengguna_lama !== $dept_pengguna_baru) {
            resetApprovalActorFields($full_new_ba, 1);
        }

        /* reset approval jika kategori aktor berubah */

        /* dari PKS ke bukan PKS -> aktor PKS hilang */
        if ($old_is_pks && !$new_is_pks) {
            $full_new_ba['kepala_mill'] = '-';
            $full_new_ba['jabatan_kepala_mill'] = '-';
            $full_new_ba['area_mill_controller'] = '-';
            $full_new_ba['jabatan_area_mill_controller'] = '-';
            $full_new_ba['dept_avp_engineering'] = '-';
            $full_new_ba['jabatan_dept_avp_engineering'] = '-';

            resetApprovalActorFields($full_new_ba, 3);
            resetApprovalActorFields($full_new_ba, 5);
            resetApprovalActorFields($full_new_ba, 8);
        }

        /* dari bukan PKS ke PKS -> aktor PKS baru muncul */
        if (!$old_is_pks && $new_is_pks) {
            resetApprovalActorFields($full_new_ba, 3);
            resetApprovalActorFields($full_new_ba, 5);
            resetApprovalActorFields($full_new_ba, 6);
        }

        /* dari kebun 1 ke non kebun 1 -> VP hilang */
        if ($old_is_kebun_1 && !$new_is_kebun_1) {
            $full_new_ba['vice_president'] = '-';
            $full_new_ba['jabatan_vice_president'] = '-';

            resetApprovalActorFields($full_new_ba, 7);
        }

        if (!$old_is_kebun_1 && $new_is_kebun_1) {
            resetApprovalActorFields($full_new_ba, 7);
        }

        $main_update_data = array(
            'tanggal' => $full_new_ba['tanggal'],
            'nomor_ba' => $full_new_ba['nomor_ba'],
            'pt' => $full_new_ba['pt'],
            'id_pt' => (int)$full_new_ba['id_pt'],

            'dept_pengguna' => $full_new_ba['dept_pengguna'],
            'jabatan_dept_pengguna' => $full_new_ba['jabatan_dept_pengguna'],

            'dept_hrops' => $full_new_ba['dept_hrops'],
            'jabatan_dept_hrops' => $full_new_ba['jabatan_dept_hrops'],

            'dept_hrd' => $full_new_ba['dept_hrd'],
            'jabatan_dept_hrd' => $full_new_ba['jabatan_dept_hrd'],

            'dept_accounting' => $full_new_ba['dept_accounting'],
            'jabatan_dept_accounting' => $full_new_ba['jabatan_dept_accounting'],

            'dir_operation' => $full_new_ba['dir_operation'],
            'jabatan_dir_operation' => $full_new_ba['jabatan_dir_operation'],

            'dir_finance' => $full_new_ba['dir_finance'],
            'jabatan_dir_finance' => $full_new_ba['jabatan_dir_finance'],

            'dir_hr' => $full_new_ba['dir_hr'],
            'jabatan_dir_hr' => $full_new_ba['jabatan_dir_hr'],

            'vice_ceo' => $full_new_ba['vice_ceo'],
            'jabatan_vice_ceo' => $full_new_ba['jabatan_vice_ceo'],

            'ceo' => $full_new_ba['ceo'],
            'jabatan_ceo' => $full_new_ba['jabatan_ceo'],

            'asisten_pga' => $full_new_ba['asisten_pga'],
            'jabatan_asisten_pga' => $full_new_ba['jabatan_asisten_pga'],

            'ktu' => $full_new_ba['ktu'],
            'jabatan_ktu' => $full_new_ba['jabatan_ktu'],

            'group_manager' => $full_new_ba['group_manager'],
            'jabatan_group_manager' => $full_new_ba['jabatan_group_manager'],

            'kepala_mill' => $full_new_ba['kepala_mill'],
            'jabatan_kepala_mill' => $full_new_ba['jabatan_kepala_mill'],

            'area_mill_controller' => $full_new_ba['area_mill_controller'],
            'jabatan_area_mill_controller' => $full_new_ba['jabatan_area_mill_controller'],

            'dept_avp_engineering' => $full_new_ba['dept_avp_engineering'],
            'jabatan_dept_avp_engineering' => $full_new_ba['jabatan_dept_avp_engineering'],

            'vice_president' => $full_new_ba['vice_president'],
            'jabatan_vice_president' => $full_new_ba['jabatan_vice_president'],

            'approval_1' => isset($full_new_ba['approval_1']) ? (int)$full_new_ba['approval_1'] : 0,
            'approval_2' => isset($full_new_ba['approval_2']) ? (int)$full_new_ba['approval_2'] : 0,
            'approval_3' => isset($full_new_ba['approval_3']) ? (int)$full_new_ba['approval_3'] : 0,
            'approval_4' => isset($full_new_ba['approval_4']) ? (int)$full_new_ba['approval_4'] : 0,
            'approval_5' => isset($full_new_ba['approval_5']) ? (int)$full_new_ba['approval_5'] : 0,
            'approval_6' => isset($full_new_ba['approval_6']) ? (int)$full_new_ba['approval_6'] : 0,
            'approval_7' => isset($full_new_ba['approval_7']) ? (int)$full_new_ba['approval_7'] : 0,
            'approval_8' => isset($full_new_ba['approval_8']) ? (int)$full_new_ba['approval_8'] : 0,
            'approval_9' => isset($full_new_ba['approval_9']) ? (int)$full_new_ba['approval_9'] : 0,
            'approval_10' => isset($full_new_ba['approval_10']) ? (int)$full_new_ba['approval_10'] : 0,
            'approval_11' => isset($full_new_ba['approval_11']) ? (int)$full_new_ba['approval_11'] : 0,
            'approval_12' => isset($full_new_ba['approval_12']) ? (int)$full_new_ba['approval_12'] : 0,
            'approval_13' => isset($full_new_ba['approval_13']) ? (int)$full_new_ba['approval_13'] : 0,
            'approval_14' => isset($full_new_ba['approval_14']) ? (int)$full_new_ba['approval_14'] : 0,
            'approval_15' => isset($full_new_ba['approval_15']) ? (int)$full_new_ba['approval_15'] : 0,
            'approval_16' => isset($full_new_ba['approval_16']) ? (int)$full_new_ba['approval_16'] : 0,

            'autograph_1' => isset($full_new_ba['autograph_1']) ? $full_new_ba['autograph_1'] : '',
            'autograph_2' => isset($full_new_ba['autograph_2']) ? $full_new_ba['autograph_2'] : '',
            'autograph_3' => isset($full_new_ba['autograph_3']) ? $full_new_ba['autograph_3'] : '',
            'autograph_4' => isset($full_new_ba['autograph_4']) ? $full_new_ba['autograph_4'] : '',
            'autograph_5' => isset($full_new_ba['autograph_5']) ? $full_new_ba['autograph_5'] : '',
            'autograph_6' => isset($full_new_ba['autograph_6']) ? $full_new_ba['autograph_6'] : '',
            'autograph_7' => isset($full_new_ba['autograph_7']) ? $full_new_ba['autograph_7'] : '',
            'autograph_8' => isset($full_new_ba['autograph_8']) ? $full_new_ba['autograph_8'] : '',
            'autograph_9' => isset($full_new_ba['autograph_9']) ? $full_new_ba['autograph_9'] : '',
            'autograph_10' => isset($full_new_ba['autograph_10']) ? $full_new_ba['autograph_10'] : '',
            'autograph_11' => isset($full_new_ba['autograph_11']) ? $full_new_ba['autograph_11'] : '',
            'autograph_12' => isset($full_new_ba['autograph_12']) ? $full_new_ba['autograph_12'] : '',
            'autograph_13' => isset($full_new_ba['autograph_13']) ? $full_new_ba['autograph_13'] : '',
            'autograph_14' => isset($full_new_ba['autograph_14']) ? $full_new_ba['autograph_14'] : '',
            'autograph_15' => isset($full_new_ba['autograph_15']) ? $full_new_ba['autograph_15'] : '',
            'autograph_16' => isset($full_new_ba['autograph_16']) ? $full_new_ba['autograph_16'] : '',

            'tanggal_approve_1' => isset($full_new_ba['tanggal_approve_1']) ? $full_new_ba['tanggal_approve_1'] : null,
            'tanggal_approve_2' => isset($full_new_ba['tanggal_approve_2']) ? $full_new_ba['tanggal_approve_2'] : null,
            'tanggal_approve_3' => isset($full_new_ba['tanggal_approve_3']) ? $full_new_ba['tanggal_approve_3'] : null,
            'tanggal_approve_4' => isset($full_new_ba['tanggal_approve_4']) ? $full_new_ba['tanggal_approve_4'] : null,
            'tanggal_approve_5' => isset($full_new_ba['tanggal_approve_5']) ? $full_new_ba['tanggal_approve_5'] : null,
            'tanggal_approve_6' => isset($full_new_ba['tanggal_approve_6']) ? $full_new_ba['tanggal_approve_6'] : null,
            'tanggal_approve_7' => isset($full_new_ba['tanggal_approve_7']) ? $full_new_ba['tanggal_approve_7'] : null,
            'tanggal_approve_8' => isset($full_new_ba['tanggal_approve_8']) ? $full_new_ba['tanggal_approve_8'] : null,
            'tanggal_approve_9' => isset($full_new_ba['tanggal_approve_9']) ? $full_new_ba['tanggal_approve_9'] : null,
            'tanggal_approve_10' => isset($full_new_ba['tanggal_approve_10']) ? $full_new_ba['tanggal_approve_10'] : null,
            'tanggal_approve_11' => isset($full_new_ba['tanggal_approve_11']) ? $full_new_ba['tanggal_approve_11'] : null,
            'tanggal_approve_12' => isset($full_new_ba['tanggal_approve_12']) ? $full_new_ba['tanggal_approve_12'] : null,
            'tanggal_approve_13' => isset($full_new_ba['tanggal_approve_13']) ? $full_new_ba['tanggal_approve_13'] : null,
            'tanggal_approve_14' => isset($full_new_ba['tanggal_approve_14']) ? $full_new_ba['tanggal_approve_14'] : null,
            'tanggal_approve_15' => isset($full_new_ba['tanggal_approve_15']) ? $full_new_ba['tanggal_approve_15'] : null,
            'tanggal_approve_16' => isset($full_new_ba['tanggal_approve_16']) ? $full_new_ba['tanggal_approve_16'] : null
        );
    }

    /* =========================================================
    | Cek perubahan
    ========================================================= */
    $perubahan = array();

    compareMainField(isset($old_data['tanggal']) ? $old_data['tanggal'] : '', $full_new_ba['tanggal'], 'Tanggal', $perubahan);
    compareMainField(isset($old_data['nomor_ba']) ? $old_data['nomor_ba'] : '', $full_new_ba['nomor_ba'], 'Nomor BA', $perubahan);
    compareMainField(isset($old_data['pt']) ? $old_data['pt'] : '', $full_new_ba['pt'], 'PT', $perubahan);

    if (!$is_site_mode) {
        compareMainField(isset($old_data['dept_pengguna']) ? $old_data['dept_pengguna'] : '', $full_new_ba['dept_pengguna'], 'Dept Pengguna', $perubahan);
        compareMainField(isset($old_data['jabatan_dept_pengguna']) ? $old_data['jabatan_dept_pengguna'] : '', $full_new_ba['jabatan_dept_pengguna'], 'Jabatan Dept Pengguna', $perubahan);
        compareMainField(isset($old_data['dept_hrops']) ? $old_data['dept_hrops'] : '', $full_new_ba['dept_hrops'], 'Dept HROPS', $perubahan);
        compareMainField(isset($old_data['jabatan_dept_hrops']) ? $old_data['jabatan_dept_hrops'] : '', $full_new_ba['jabatan_dept_hrops'], 'Jabatan Dept HROPS', $perubahan);

    } else {
        compareMainField(isset($old_data['dept_pengguna']) ? $old_data['dept_pengguna'] : '', isset($full_new_ba['dept_pengguna']) ? $full_new_ba['dept_pengguna'] : '', 'Dept Pengguna', $perubahan);
        compareMainField(isset($old_data['jabatan_dept_pengguna']) ? $old_data['jabatan_dept_pengguna'] : '', isset($full_new_ba['jabatan_dept_pengguna']) ? $full_new_ba['jabatan_dept_pengguna'] : '', 'Jabatan Dept Pengguna', $perubahan);
    }

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
        header("Location: ba_pemutihan.php?status=gagal");
        exit();
    }

    if (!$ba_barang_berubah && !$gambar_berubah) {
        $_SESSION['message'] = "Tidak ada perubahan data.";
        header("Location: ba_pemutihan.php?status=gagal");
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
        $ada_approval = hasAnyApproval($old_data, 16);
        $pending_status = $ada_approval ? 1 : 0;
        $pending_approver = $ada_approval ? resolvePendingApproverPemutihan($koneksi, $pt, $nama_sesi) : '';

        /* ---------------------------------------------------------
        | Bersihkan pending lama jika ada
        | Hanya jika BA + barang berubah
        --------------------------------------------------------- */
        safeDeleteIfSchemaReady(
            $koneksi,
            'historikal_edit_ba',
            array('id_ba', 'nama_ba', 'pending_status'),
            "DELETE FROM historikal_edit_ba WHERE nama_ba = 'pemutihan' AND id_ba = ? AND pending_status = 1",
            "i",
            array($id)
        );

        safeDeleteIfSchemaReady(
            $koneksi,
            'history_n_temp_ba_pemutihan',
            array('id_ba', 'pending_status', 'status'),
            "DELETE FROM history_n_temp_ba_pemutihan WHERE id_ba = ? AND pending_status = 1 AND (status = 0 OR status = 1)",
            "i",
            array($id)
        );

        safeDeleteIfSchemaReady(
            $koneksi,
            'history_n_temp_barang_pemutihan',
            array('id_ba', 'pending_status', 'status'),
            "DELETE FROM history_n_temp_barang_pemutihan WHERE id_ba = ? AND pending_status = 1 AND (status = 0 OR status = 1)",
            "i",
            array($id)
        );

        /* ---------------------------------------------------------
        | Simpan histori ringkas
        | Hanya untuk BA + barang, gambar tidak ikut histori
        --------------------------------------------------------- */
        $pengedit = isset($_SESSION['nama']) ? trim((string)$_SESSION['nama']) : '-';

        $historikal_data = array(
            'id_ba' => $id,
            'nama_ba' => 'pemutihan',
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
        unset($history_old_ba['updated_at']);

        $history_old_ba['id_ba'] = $id;
        $history_old_ba['status'] = 0;
        $history_old_ba['pending_status'] = $pending_status;
        $history_old_ba['pending_approver'] = $pending_approver;
        $history_old_ba['alasan_edit'] = $alasan_perubahan;
        $history_old_ba['file_created'] = $file_created_ba;
        $history_old_ba['created_at'] = 'NOW()';
        $history_old_ba['updated_at'] = 'NOW()';
        $history_old_ba = normalizeBaHistorySnapshot($history_old_ba);

        insertAssoc($koneksi, 'history_n_temp_ba_pemutihan', $history_old_ba);

        if ($ada_approval) {
            $history_new_ba = $full_new_ba;
            unset($history_new_ba['id']);
            unset($history_new_ba['created_at']);
            unset($history_new_ba['updated_at']);

            $history_new_ba['id_ba'] = $id;
            $history_new_ba['status'] = 1;
            $history_new_ba['pending_status'] = $pending_status;
            $history_new_ba['pending_approver'] = $pending_approver;
            $history_new_ba['alasan_edit'] = $alasan_perubahan;
            $history_new_ba['file_created'] = $file_created_ba;
            $history_new_ba['created_at'] = 'NOW()';
            $history_new_ba['updated_at'] = 'NOW()';
            $history_new_ba = normalizeBaHistorySnapshot($history_new_ba);

            insertAssoc($koneksi, 'history_n_temp_ba_pemutihan', $history_new_ba);
        }

        /* ---------------------------------------------------------
        | Simpan snapshot barang
        --------------------------------------------------------- */
        $old_barang_count = count($old_barang_list);
        for ($i = 0; $i < $old_barang_count; $i++) {
            $row = $old_barang_list[$i];

            $history_old_barang = array(
                'id_ba' => $id,
                'id_barang' => isset($row['id']) ? (int)$row['id'] : 0,
                'status' => 0,
                'pending_status' => $pending_status,
                'pending_approver' => $pending_approver,
                'alasan_edit' => $alasan_perubahan,
                'id_pt' => isset($row['id_pt']) ? (int)$row['id_pt'] : 0,
                'pt' => isset($row['pt']) ? $row['pt'] : '-',
                'po' => isset($row['po']) ? $row['po'] : '-',
                'coa' => isset($row['coa']) ? $row['coa'] : '-',
                'kode_assets' => isset($row['kode_assets']) ? $row['kode_assets'] : '-',
                'merk' => isset($row['merk']) ? $row['merk'] : '-',
                'sn' => isset($row['sn']) ? $row['sn'] : '-',
                'user' => isset($row['user']) ? $row['user'] : '-',
                'harga_beli' => isset($row['harga_beli']) ? (int)$row['harga_beli'] : 0,
                'tahun_perolehan' => isset($row['tahun_perolehan']) ? (int)$row['tahun_perolehan'] : 0,
                'alasan_penghapusan' => isset($row['alasan_penghapusan']) ? $row['alasan_penghapusan'] : '',
                'kondisi' => isset($row['kondisi']) ? $row['kondisi'] : '',
                'file_created' => 'NOW()',
                'created_at' => 'NOW()',
                'updated_at' => 'NOW()'
            );

            insertAssoc($koneksi, 'history_n_temp_barang_pemutihan', $history_old_barang);
        }

        $new_barang_count = count($new_barang_list);

        if ($ada_approval) {
            for ($i = 0; $i < $new_barang_count; $i++) {
                $row = $new_barang_list[$i];

                $history_new_barang = array(
                    'id_ba' => $id,
                    'id_barang' => 0,
                    'status' => 1,
                    'pending_status' => $pending_status,
                    'pending_approver' => $pending_approver,
                    'alasan_edit' => $alasan_perubahan,
                    'id_pt' => isset($row['id_pt']) ? (int)$row['id_pt'] : 0,
                    'pt' => isset($row['pt']) ? $row['pt'] : '-',
                    'po' => isset($row['po']) ? $row['po'] : '-',
                    'coa' => isset($row['coa']) ? $row['coa'] : '-',
                    'kode_assets' => isset($row['kode_assets']) ? $row['kode_assets'] : '-',
                    'merk' => isset($row['merk']) ? $row['merk'] : '-',
                    'sn' => isset($row['sn']) ? $row['sn'] : '-',
                    'user' => isset($row['user']) ? $row['user'] : '-',
                    'harga_beli' => isset($row['harga_beli']) ? (int)$row['harga_beli'] : 0,
                    'tahun_perolehan' => isset($row['tahun_perolehan']) ? (int)$row['tahun_perolehan'] : 0,
                    'alasan_penghapusan' => isset($row['alasan_penghapusan']) ? $row['alasan_penghapusan'] : '',
                    'kondisi' => isset($row['kondisi']) ? $row['kondisi'] : '',
                    'file_created' => 'NOW()',
                    'created_at' => 'NOW()',
                    'updated_at' => 'NOW()'
                );

                insertAssoc($koneksi, 'history_n_temp_barang_pemutihan', $history_new_barang);
            }
        }

        /* ---------------------------------------------------------
        | Jika tidak ada approval, update data utama + barang
        --------------------------------------------------------- */
        if (!$ada_approval) {
            updateAssoc($koneksi, 'berita_acara_pemutihan', $main_update_data, "id = ?", "i", array($id));

            deletePrepared($koneksi, "DELETE FROM barang_pemutihan WHERE id_ba = ?", "i", array($id));

            for ($i = 0; $i < $new_barang_count; $i++) {
                $row = $new_barang_list[$i];

                $insert_barang = array(
                    'id_ba' => $id,
                    'id_pt' => isset($row['id_pt']) ? (int)$row['id_pt'] : 0,
                    'pt' => isset($row['pt']) ? $row['pt'] : '-',
                    'po' => isset($row['po']) ? $row['po'] : '-',
                    'coa' => isset($row['coa']) ? $row['coa'] : '-',
                    'kode_assets' => isset($row['kode_assets']) ? $row['kode_assets'] : '-',
                    'merk' => isset($row['merk']) ? $row['merk'] : '-',
                    'sn' => isset($row['sn']) ? $row['sn'] : '-',
                    'user' => isset($row['user']) ? $row['user'] : '-',
                    'harga_beli' => isset($row['harga_beli']) ? (int)$row['harga_beli'] : 0,
                    'tahun_perolehan' => isset($row['tahun_perolehan']) ? (int)$row['tahun_perolehan'] : 0,
                    'alasan_penghapusan' => isset($row['alasan_penghapusan']) ? $row['alasan_penghapusan'] : '',
                    'kondisi' => isset($row['kondisi']) ? $row['kondisi'] : ''
                );

                insertAssoc($koneksi, 'barang_pemutihan', $insert_barang);
            }
        }
    }

    /* ---------------------------------------------------------
    | Proses gambar_ba_pemutihan
    | Mengikuti pola BA Kerusakan: gambar diproses langsung
    --------------------------------------------------------- */
    $upload_dir = '../assets/database-gambar/';
    ensureDirExists($upload_dir);

    foreach ($old_images as $idx => $old_image) {
        $hapus_flag = isset($_POST['hapus_gambar'][$idx]) ? trim((string)$_POST['hapus_gambar'][$idx]) : '';

        if ($hapus_flag === 'hapus') {
            deleteImageRowPemutihan($koneksi, $id, $old_image);

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
            updateImageRowPemutihan($koneksi, $id, $old_image, $new_file_path, $new_keterangan);

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

            insertAssoc($koneksi, 'gambar_ba_pemutihan', $insert_gambar);
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

    header("Location: ba_pemutihan.php?status=sukses");
    exit();
} catch (Exception $e) {
    if ($koneksi) {
        $koneksi->rollback();
        $koneksi->autocommit(true);
        $koneksi->close();
    }

    error_log("Gagal edit BA Pemutihan: " . $e->getMessage());
    $_SESSION['message'] = "Gagal menyimpan perubahan: " . $e->getMessage();
    header("Location: ba_pemutihan.php?status=gagal");
    exit();
}
