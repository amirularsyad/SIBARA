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
            ORDER BY id ASC";

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
            $images[(int)$row['id']] = array(
                'id' => (int)$row['id'],
                'file_path' => isset($row['file_path']) ? $row['file_path'] : '',
                'keterangan' => isset($row['keterangan']) ? $row['keterangan'] : ''
            );
        }
    }

    $stmt->close();
    return $images;
}

function hasImageChangeRequest($post, $files, $oldImages)
{
    if (isset($post['hapus_gambar']) && is_array($post['hapus_gambar'])) {
        foreach ($post['hapus_gambar'] as $v) {
            if ((string)$v === 'hapus') {
                return true;
            }
        }
    }

    if (isset($files['gambar_lama_file']['name']) && is_array($files['gambar_lama_file']['name'])) {
        foreach ($files['gambar_lama_file']['name'] as $name) {
            if (trim((string)$name) !== '') {
                return true;
            }
        }
    }

    if (isset($files['gambar_baru']['name']) && is_array($files['gambar_baru']['name'])) {
        foreach ($files['gambar_baru']['name'] as $name) {
            if (trim((string)$name) !== '') {
                return true;
            }
        }
    }

    if (isset($post['gambar_lama_id']) && is_array($post['gambar_lama_id'])) {
        foreach ($post['gambar_lama_id'] as $gambar_id_raw) {
            $gambar_id = (int)$gambar_id_raw;

            if ($gambar_id <= 0 || !isset($oldImages[$gambar_id])) {
                continue;
            }

            $oldKet = isset($oldImages[$gambar_id]['keterangan']) ? trim((string)$oldImages[$gambar_id]['keterangan']) : '';
            $newKet = isset($post['keterangan_gambar_lama'][$gambar_id]) ? trim((string)$post['keterangan_gambar_lama'][$gambar_id]) : $oldKet;

            if ($oldKet !== $newKet) {
                return true;
            }
        }
    }

    return false;
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
$pembuat = isset($_POST['pembuat']) ? trim((string)$_POST['pembuat']) : '';
$pemeriksa = isset($_POST['pemeriksa']) ? trim((string)$_POST['pemeriksa']) : '';
$jabatan_pembuat_hidden = isset($_POST['jabatan_pembuat']) ? trim((string)$_POST['jabatan_pembuat']) : '';
$jabatan_pemeriksa_hidden = isset($_POST['jabatan_pemeriksa']) ? trim((string)$_POST['jabatan_pemeriksa']) : '';

/* input tambahan non-HO dari form edit */
$diketahui1_site_input = isset($_POST['diketahui1_site']) ? trim((string)$_POST['diketahui1_site']) : '';
$disetujui1_site_input = isset($_POST['disetujui1_site']) ? trim((string)$_POST['disetujui1_site']) : '';
$jabatan_diketahui1_site_hidden = isset($_POST['jabatan_diketahui1_site']) ? trim((string)$_POST['jabatan_diketahui1_site']) : '';

$alasan_perubahan = isset($_POST['alasan_perubahan']) ? trim((string)$_POST['alasan_perubahan']) : '';
$nama_sesi = isset($_SESSION['nama']) ? trim((string)$_SESSION['nama']) : '';

if ($id <= 0) {
    $_SESSION['message'] = "ID tidak valid.";
    header("Location: ba_pemutihan.php?status=gagal");
    exit();
}

if ($nomor_ba === '' || $tanggal === '' || $pt === '' || $pembuat === '' || $pemeriksa === '') {
    $_SESSION['message'] = "Data form utama belum lengkap.";
    header("Location: ba_pemutihan.php?status=gagal");
    exit();
}

if ($alasan_perubahan === '') {
    $_SESSION['message'] = "Alasan perubahan wajib diisi.";
    header("Location: ba_pemutihan.php?status=gagal");
    exit();
}

if ($pt !== 'PT.MSAL (HO)') {
    if ($diketahui1_site_input === '' || $disetujui1_site_input === '') {
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
        $jabatan_pembuat = getJabatanPembuatPemeriksa($koneksi, $pembuat, $pt, $jabatan_pembuat_hidden);
        $jabatan_pemeriksa = getJabatanPembuatPemeriksa($koneksi, $pemeriksa, $pt, $jabatan_pemeriksa_hidden);

        $full_new_ba['pembuat'] = $pembuat;
        $full_new_ba['jabatan_pembuat'] = $jabatan_pembuat;
        $full_new_ba['pemeriksa'] = $pemeriksa;
        $full_new_ba['jabatan_pemeriksa'] = $jabatan_pemeriksa;

        $pembuat_lama = isset($old_data['pembuat']) ? trim((string)$old_data['pembuat']) : '';
        $pemeriksa_lama = isset($old_data['pemeriksa']) ? trim((string)$old_data['pemeriksa']) : '';

        $pembuat_berubah = ($pembuat_lama !== trim((string)$pembuat));
        $pemeriksa_berubah = ($pemeriksa_lama !== trim((string)$pemeriksa));

        if ($pembuat_berubah) {
            $full_new_ba['approval_1'] = 0;
            $full_new_ba['autograph_1'] = '';
            $full_new_ba['tanggal_approve_1'] = null;
        }

        if ($pemeriksa_berubah) {
            $full_new_ba['approval_2'] = 0;
            $full_new_ba['autograph_2'] = '';
            $full_new_ba['tanggal_approve_2'] = null;
        }

        $main_update_data = array(
            'tanggal' => $full_new_ba['tanggal'],
            'nomor_ba' => $full_new_ba['nomor_ba'],
            'pt' => $full_new_ba['pt'],
            'id_pt' => (int)$full_new_ba['id_pt'],
            'pembuat' => $full_new_ba['pembuat'],
            'jabatan_pembuat' => $full_new_ba['jabatan_pembuat'],
            'pemeriksa' => $full_new_ba['pemeriksa'],
            'jabatan_pemeriksa' => $full_new_ba['jabatan_pemeriksa'],
            'approval_1' => isset($full_new_ba['approval_1']) ? (int)$full_new_ba['approval_1'] : 0,
            'approval_2' => isset($full_new_ba['approval_2']) ? (int)$full_new_ba['approval_2'] : 0,
            'autograph_1' => isset($full_new_ba['autograph_1']) ? $full_new_ba['autograph_1'] : '',
            'autograph_2' => isset($full_new_ba['autograph_2']) ? $full_new_ba['autograph_2'] : '',
            'tanggal_approve_1' => isset($full_new_ba['tanggal_approve_1']) ? $full_new_ba['tanggal_approve_1'] : null,
            'tanggal_approve_2' => isset($full_new_ba['tanggal_approve_2']) ? $full_new_ba['tanggal_approve_2'] : null
        );
    } else {
        $jabatan_pembuat_site = getJabatanPembuatPemeriksa($koneksi, $pembuat, $pt, $jabatan_pembuat_hidden);
        $jabatan_pemeriksa_site = getJabatanPembuatPemeriksa($koneksi, $pemeriksa, $pt, $jabatan_pemeriksa_hidden);

        $jabatan_diketahui1_site = getPosisiKaryawanTest($koneksi, $diketahui1_site_input, $pt);
        if ($jabatan_diketahui1_site === '-' && $jabatan_diketahui1_site_hidden !== '') {
            $jabatan_diketahui1_site = $jabatan_diketahui1_site_hidden;
        }

        $jabatan_disetujui1_site = 'Kepala Project';

        $full_new_ba['pembuat'] = '';
        $full_new_ba['jabatan_pembuat'] = '';
        $full_new_ba['pembuat_site'] = $pembuat;
        $full_new_ba['jabatan_pembuat_site'] = $jabatan_pembuat_site;

        $full_new_ba['pemeriksa'] = '';
        $full_new_ba['jabatan_pemeriksa'] = '';
        $full_new_ba['pemeriksa_site'] = $pemeriksa;
        $full_new_ba['jabatan_pemeriksa_site'] = $jabatan_pemeriksa_site;

        $full_new_ba['diketahui1'] = '';
        $full_new_ba['jabatan_diketahui1'] = '';
        $full_new_ba['diketahui1_site'] = $diketahui1_site_input;
        $full_new_ba['jabatan_diketahui1_site'] = $jabatan_diketahui1_site;

        $full_new_ba['diketahui2'] = '';
        $full_new_ba['jabatan_diketahui2'] = '';
        $full_new_ba['disetujui1_site'] = $disetujui1_site_input;
        $full_new_ba['jabatan_disetujui1_site'] = $jabatan_disetujui1_site;

        $pembuat_site_lama = isset($old_data['pembuat_site']) ? trim((string)$old_data['pembuat_site']) : '';
        $pemeriksa_site_lama = isset($old_data['pemeriksa_site']) ? trim((string)$old_data['pemeriksa_site']) : '';
        $diketahui1_site_lama = isset($old_data['diketahui1_site']) ? trim((string)$old_data['diketahui1_site']) : '';
        $disetujui1_site_lama = isset($old_data['disetujui1_site']) ? trim((string)$old_data['disetujui1_site']) : '';

        if ($pembuat_site_lama !== trim((string)$pembuat)) {
            $full_new_ba['approval_1'] = 0;
            $full_new_ba['autograph_1'] = '';
            $full_new_ba['tanggal_approve_1'] = null;
        }

        if ($pemeriksa_site_lama !== trim((string)$pemeriksa)) {
            $full_new_ba['approval_2'] = 0;
            $full_new_ba['autograph_2'] = '';
            $full_new_ba['tanggal_approve_2'] = null;
        }

        if ($diketahui1_site_lama !== trim((string)$diketahui1_site_input)) {
            $full_new_ba['approval_3'] = 0;
            $full_new_ba['autograph_3'] = '';
            $full_new_ba['tanggal_approve_3'] = null;
        }

        if ($disetujui1_site_lama !== trim((string)$disetujui1_site_input)) {
            $full_new_ba['approval_4'] = 0;
            $full_new_ba['autograph_4'] = '';
            $full_new_ba['tanggal_approve_4'] = null;
        }

        $main_update_data = array(
            'tanggal' => $full_new_ba['tanggal'],
            'nomor_ba' => $full_new_ba['nomor_ba'],
            'pt' => $full_new_ba['pt'],
            'id_pt' => (int)$full_new_ba['id_pt'],

            'pembuat' => '',
            'jabatan_pembuat' => '',
            'pembuat_site' => $full_new_ba['pembuat_site'],
            'jabatan_pembuat_site' => $full_new_ba['jabatan_pembuat_site'],

            'pemeriksa' => '',
            'jabatan_pemeriksa' => '',
            'pemeriksa_site' => $full_new_ba['pemeriksa_site'],
            'jabatan_pemeriksa_site' => $full_new_ba['jabatan_pemeriksa_site'],

            'diketahui1' => '',
            'jabatan_diketahui1' => '',
            'diketahui1_site' => $full_new_ba['diketahui1_site'],
            'jabatan_diketahui1_site' => $full_new_ba['jabatan_diketahui1_site'],

            'diketahui2' => '',
            'jabatan_diketahui2' => '',
            'disetujui1_site' => $full_new_ba['disetujui1_site'],
            'jabatan_disetujui1_site' => $full_new_ba['jabatan_disetujui1_site'],

            'approval_1' => isset($full_new_ba['approval_1']) ? (int)$full_new_ba['approval_1'] : 0,
            'approval_2' => isset($full_new_ba['approval_2']) ? (int)$full_new_ba['approval_2'] : 0,
            'approval_3' => isset($full_new_ba['approval_3']) ? (int)$full_new_ba['approval_3'] : 0,
            'approval_4' => isset($full_new_ba['approval_4']) ? (int)$full_new_ba['approval_4'] : 0,

            'autograph_1' => isset($full_new_ba['autograph_1']) ? $full_new_ba['autograph_1'] : '',
            'autograph_2' => isset($full_new_ba['autograph_2']) ? $full_new_ba['autograph_2'] : '',
            'autograph_3' => isset($full_new_ba['autograph_3']) ? $full_new_ba['autograph_3'] : '',
            'autograph_4' => isset($full_new_ba['autograph_4']) ? $full_new_ba['autograph_4'] : '',

            'tanggal_approve_1' => isset($full_new_ba['tanggal_approve_1']) ? $full_new_ba['tanggal_approve_1'] : null,
            'tanggal_approve_2' => isset($full_new_ba['tanggal_approve_2']) ? $full_new_ba['tanggal_approve_2'] : null,
            'tanggal_approve_3' => isset($full_new_ba['tanggal_approve_3']) ? $full_new_ba['tanggal_approve_3'] : null,
            'tanggal_approve_4' => isset($full_new_ba['tanggal_approve_4']) ? $full_new_ba['tanggal_approve_4'] : null
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
        compareMainField(isset($old_data['pembuat']) ? $old_data['pembuat'] : '', $full_new_ba['pembuat'], 'Pembuat', $perubahan);
        compareMainField(isset($old_data['jabatan_pembuat']) ? $old_data['jabatan_pembuat'] : '', $full_new_ba['jabatan_pembuat'], 'Jabatan Pembuat', $perubahan);
        compareMainField(isset($old_data['pemeriksa']) ? $old_data['pemeriksa'] : '', $full_new_ba['pemeriksa'], 'Pemeriksa', $perubahan);
        compareMainField(isset($old_data['jabatan_pemeriksa']) ? $old_data['jabatan_pemeriksa'] : '', $full_new_ba['jabatan_pemeriksa'], 'Jabatan Pemeriksa', $perubahan);
    } else {
        compareMainField(isset($old_data['pembuat_site']) ? $old_data['pembuat_site'] : '', $full_new_ba['pembuat_site'], 'Pembuat Site', $perubahan);
        compareMainField(isset($old_data['jabatan_pembuat_site']) ? $old_data['jabatan_pembuat_site'] : '', $full_new_ba['jabatan_pembuat_site'], 'Jabatan Pembuat Site', $perubahan);

        compareMainField(isset($old_data['pemeriksa_site']) ? $old_data['pemeriksa_site'] : '', $full_new_ba['pemeriksa_site'], 'Pemeriksa Site', $perubahan);
        compareMainField(isset($old_data['jabatan_pemeriksa_site']) ? $old_data['jabatan_pemeriksa_site'] : '', $full_new_ba['jabatan_pemeriksa_site'], 'Jabatan Pemeriksa Site', $perubahan);

        compareMainField(isset($old_data['diketahui1_site']) ? $old_data['diketahui1_site'] : '', $full_new_ba['diketahui1_site'], 'Diketahui Site', $perubahan);
        compareMainField(isset($old_data['jabatan_diketahui1_site']) ? $old_data['jabatan_diketahui1_site'] : '', $full_new_ba['jabatan_diketahui1_site'], 'Jabatan Diketahui Site', $perubahan);

        compareMainField(isset($old_data['disetujui1_site']) ? $old_data['disetujui1_site'] : '', $full_new_ba['disetujui1_site'], 'Disetujui Site', $perubahan);
        compareMainField(isset($old_data['jabatan_disetujui1_site']) ? $old_data['jabatan_disetujui1_site'] : '', $full_new_ba['jabatan_disetujui1_site'], 'Jabatan Disetujui Site', $perubahan);
    }

    $barang_berubah = barangListsDifferent($old_barang_list, $new_barang_list);
    if ($barang_berubah) {
        $barang_lama_text = buildBarangMerkHistoryText($old_barang_list);
        $barang_baru_text = buildBarangMerkHistoryText($new_barang_list);

        $perubahan[] = "Barang : " . $barang_lama_text . " diubah ke " . $barang_baru_text;
    }

    $gambar_berubah = hasImageChangeRequest($_POST, $_FILES, $old_images);
    if ($gambar_berubah) {
        $perubahan[] = "Gambar BA diperbarui.";
    }

    if (empty($perubahan)) {
        $_SESSION['message'] = "Tidak ada perubahan data.";
        header("Location: ba_pemutihan.php?status=gagal");
        exit();
    }

    $histori_text = implode("; ", $perubahan);

    /* =========================================================
    | Mode pending / direct
    ========================================================= */
    $ada_approval = hasAnyApproval($old_data, 11);
    $pending_status = $ada_approval ? 1 : 0;
    $pending_approver = $ada_approval ? resolvePendingApproverPemutihan($koneksi, $pt, $nama_sesi) : '';

    /* =========================================================
    | Transaksi
    ========================================================= */
    $koneksi->autocommit(false);

    /* ---------------------------------------------------------
    | Bersihkan pending lama jika ada
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
| - Jika pending edit  : simpan data lama + data baru
| - Jika belum approval: simpan data lama saja
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

        insertAssoc($koneksi, 'history_n_temp_ba_pemutihan', $history_new_ba);
    }

    /* ---------------------------------------------------------
| Simpan snapshot barang
| - Jika pending edit  : simpan barang lama + barang baru
| - Jika belum approval: simpan barang lama saja
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

    /* ---------------------------------------------------------
    | Proses gambar_ba_pemutihan
    | Mengikuti pola BA Kerusakan: gambar diproses langsung
    --------------------------------------------------------- */
    $upload_dir = '../assets/database-gambar/';
    ensureDirExists($upload_dir);

    $deletedImageIds = array();

    if (isset($_POST['gambar_lama_id']) && is_array($_POST['gambar_lama_id'])) {
        foreach ($_POST['gambar_lama_id'] as $idx => $gambar_id_raw) {
            $gambar_id = (int)$gambar_id_raw;

            if ($gambar_id <= 0 || !isset($old_images[$gambar_id])) {
                continue;
            }

            $hapus_flag = isset($_POST['hapus_gambar'][$idx]) ? trim((string)$_POST['hapus_gambar'][$idx]) : '';

            if ($hapus_flag === 'hapus') {
                if (!empty($old_images[$gambar_id]['file_path']) && file_exists($old_images[$gambar_id]['file_path'])) {
                    @unlink($old_images[$gambar_id]['file_path']);
                }

                deletePrepared($koneksi, "DELETE FROM gambar_ba_pemutihan WHERE id = ?", "i", array($gambar_id));
                $deletedImageIds[$gambar_id] = true;
                continue;
            }

            $new_keterangan = isset($_POST['keterangan_gambar_lama'][$gambar_id])
                ? trim((string)$_POST['keterangan_gambar_lama'][$gambar_id])
                : $old_images[$gambar_id]['keterangan'];

            $new_file_path = $old_images[$gambar_id]['file_path'];

            if (
                isset($_FILES['gambar_lama_file']['name'][$gambar_id]) &&
                trim((string)$_FILES['gambar_lama_file']['name'][$gambar_id]) !== '' &&
                isset($_FILES['gambar_lama_file']['tmp_name'][$gambar_id]) &&
                $_FILES['gambar_lama_file']['tmp_name'][$gambar_id] !== ''
            ) {
                $tmp_name = $_FILES['gambar_lama_file']['tmp_name'][$gambar_id];
                $nama_asli = safeUploadFileName($_FILES['gambar_lama_file']['name'][$gambar_id]);
                $target_path = $upload_dir . time() . '_' . $gambar_id . '_' . $nama_asli;

                if (!move_uploaded_file($tmp_name, $target_path)) {
                    throw new Exception("Gagal upload pengganti gambar lama.");
                }

                if (!empty($old_images[$gambar_id]['file_path']) && file_exists($old_images[$gambar_id]['file_path'])) {
                    @unlink($old_images[$gambar_id]['file_path']);
                }

                $new_file_path = $target_path;
            }

            updateAssoc(
                $koneksi,
                'gambar_ba_pemutihan',
                array(
                    'file_path' => $new_file_path,
                    'keterangan' => $new_keterangan
                ),
                "id = ?",
                "i",
                array($gambar_id)
            );
        }
    }

    if (isset($_FILES['gambar_baru']['name']) && is_array($_FILES['gambar_baru']['name'])) {
        $ket_baru = (isset($_POST['keterangan_gambar_baru']) && is_array($_POST['keterangan_gambar_baru'])) ? $_POST['keterangan_gambar_baru'] : array();

        foreach ($_FILES['gambar_baru']['name'] as $idx => $filename) {
            $filename = trim((string)$filename);
            if ($filename === '') {
                continue;
            }

            $tmp_name = isset($_FILES['gambar_baru']['tmp_name'][$idx]) ? $_FILES['gambar_baru']['tmp_name'][$idx] : '';
            if ($tmp_name === '') {
                continue;
            }

            $nama_asli = safeUploadFileName($filename);
            $target_path = $upload_dir . time() . '_baru_' . $idx . '_' . $nama_asli;

            if (!move_uploaded_file($tmp_name, $target_path)) {
                throw new Exception("Gagal upload gambar baru.");
            }

            $insert_gambar = array(
                'id_ba' => $id,
                'file_path' => $target_path,
                'keterangan' => isset($ket_baru[$idx]) ? trim((string)$ket_baru[$idx]) : '',
                'uploaded_at' => 'NOW()'
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

    if ($ada_approval) {
        $_SESSION['message'] = "Perubahan disimpan sebagai pending edit dan menunggu approval.";
    } else {
        $_SESSION['message'] = "Data berhasil diperbarui ke database.";
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
