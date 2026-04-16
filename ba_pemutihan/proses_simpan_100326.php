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

include '../koneksi.php';

/*
|--------------------------------------------------------------------------
| Helper
|--------------------------------------------------------------------------
*/
function normalizeString($value, $default)
{
    $value = trim((string)$value);
    return ($value === '') ? $default : $value;
}

function getNamaKaryawanByJabatanDepartemen($koneksi, $jabatan, $departemen)
{
    $nama = '-';
    $namaDb = '';

    $sql = "SELECT nama
            FROM data_karyawan
            WHERE jabatan = ?
              AND departemen = ?
            LIMIT 1";

    $stmt = $koneksi->prepare($sql);
    if (!$stmt) {
        error_log("Prepare gagal getNamaKaryawanByJabatanDepartemen: " . $koneksi->error);
        return $nama;
    }

    $stmt->bind_param("ss", $jabatan, $departemen);

    if (!$stmt->execute()) {
        error_log("Execute gagal getNamaKaryawanByJabatanDepartemen: " . $stmt->error);
        $stmt->close();
        return $nama;
    }

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

function getNamaKaryawanByPosisiDepartemen($koneksi, $posisiCari, $departemen)
{
    $nama = '-';
    $namaDb = '';

    $sql = "SELECT nama
            FROM data_karyawan
            WHERE posisi = ?
              AND departemen = ?
            LIMIT 1";

    $stmt = $koneksi->prepare($sql);
    if (!$stmt) {
        error_log("Prepare gagal getNamaKaryawanByPosisiDepartemen: " . $koneksi->error);
        return $nama;
    }

    $stmt->bind_param("ss", $posisiCari, $departemen);

    if (!$stmt->execute()) {
        error_log("Execute gagal getNamaKaryawanByPosisiDepartemen: " . $stmt->error);
        $stmt->close();
        return $nama;
    }

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
        error_log("Prepare gagal getJabatanLengkapDataKaryawan: " . $koneksi->error);
        return $hasil;
    }

    $stmt->bind_param("s", $nama);

    if (!$stmt->execute()) {
        error_log("Execute gagal getJabatanLengkapDataKaryawan: " . $stmt->error);
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

function getPosisiKaryawanTest($koneksi, $nama, $pt)
{
    $hasil = '-';

    $nama = trim((string)$nama);
    $pt   = trim((string)$pt);

    if ($nama === '' || $nama === '-' || $pt === '' || $pt === '-') {
        return $hasil;
    }

    $posisi = '';

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
        error_log("Prepare gagal getPosisiKaryawanTest: " . $koneksi->error);
        return $hasil;
    }

    $stmt->bind_param("sss", $nama, $pt, $pt);

    if (!$stmt->execute()) {
        error_log("Execute gagal getPosisiKaryawanTest: " . $stmt->error);
        $stmt->close();
        return $hasil;
    }

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

/*
|--------------------------------------------------------------------------
| Mapping PT
|--------------------------------------------------------------------------
*/
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

/*
|--------------------------------------------------------------------------
| Ambil data form input utama
|--------------------------------------------------------------------------
*/
$nomor_ba     = isset($_POST['nomor_ba']) ? trim($_POST['nomor_ba']) : '';
$tanggal      = isset($_POST['tanggal']) ? trim($_POST['tanggal']) : '';
$pt           = isset($_POST['pt']) ? trim($_POST['pt']) : '';
$pembuat      = isset($_POST['pembuat']) ? trim($_POST['pembuat']) : '';
$pemeriksa    = isset($_POST['pemeriksa']) ? trim($_POST['pemeriksa']) : '';
$nama_pembuat = isset($_SESSION['nama']) ? trim($_SESSION['nama']) : '-';

if ($nomor_ba === '' || $tanggal === '' || $pt === '' || $pembuat === '' || $pemeriksa === '') {
    $_SESSION['message'] = "Data form utama belum lengkap.";
    header("Location: ba_pemutihan.php?status=gagal");
    exit();
}

$id_pt = isset($pt_map[$pt]) ? (int)$pt_map[$pt] : 0;
if ($id_pt <= 0) {
    $_SESSION['message'] = "PT tidak valid.";
    header("Location: ba_pemutihan.php?status=gagal");
    exit();
}

/*
|--------------------------------------------------------------------------
| Aktor pemutihan
|--------------------------------------------------------------------------
*/
$diketahui1 = getNamaKaryawanByJabatanDepartemen($koneksi, 'Dept. Head', 'MIS');
$diketahui2 = getNamaKaryawanByJabatanDepartemen($koneksi, 'Dept. Head', 'HRO');
$diketahui3 = getNamaKaryawanByJabatanDepartemen($koneksi, 'Dept. Head', 'HRD');
$dibukukan  = getNamaKaryawanByJabatanDepartemen($koneksi, 'Direktur', 'FINANCE');
$disetujui1 = getNamaKaryawanByPosisiDepartemen($koneksi, 'Direktur MIS', 'BOD');
$disetujui2 = getNamaKaryawanByJabatanDepartemen($koneksi, 'Direktur', 'HRD');
$disetujui3 = getNamaKaryawanByPosisiDepartemen($koneksi, 'Vice CEO', 'BOD');

if ($pt === 'PT.MSAL (HO)') {
    $jabatan_pembuat   = getJabatanLengkapDataKaryawan($koneksi, $pembuat);
    $jabatan_pemeriksa = getJabatanLengkapDataKaryawan($koneksi, $pemeriksa);
} else {
    $jabatan_pembuat   = getPosisiKaryawanTest($koneksi, $pembuat, $pt);
    $jabatan_pemeriksa = getPosisiKaryawanTest($koneksi, $pemeriksa, $pt);
}

/* fallback dari hidden form */
if ($jabatan_pembuat === '-' && isset($_POST['jabatan_pembuat']) && trim($_POST['jabatan_pembuat']) !== '') {
    $jabatan_pembuat = trim($_POST['jabatan_pembuat']);
}
if ($jabatan_pemeriksa === '-' && isset($_POST['jabatan_pemeriksa']) && trim($_POST['jabatan_pemeriksa']) !== '') {
    $jabatan_pemeriksa = trim($_POST['jabatan_pemeriksa']);
}

$jabatan_diketahui1 = getJabatanLengkapDataKaryawan($koneksi, $diketahui1);
$jabatan_diketahui2 = getJabatanLengkapDataKaryawan($koneksi, $diketahui2);
$jabatan_diketahui3 = getJabatanLengkapDataKaryawan($koneksi, $diketahui3);
$jabatan_dibukukan  = getJabatanLengkapDataKaryawan($koneksi, $dibukukan);
$jabatan_disetujui1 = getJabatanLengkapDataKaryawan($koneksi, $disetujui1);
$jabatan_disetujui2 = getJabatanLengkapDataKaryawan($koneksi, $disetujui2);
$jabatan_disetujui3 = getJabatanLengkapDataKaryawan($koneksi, $disetujui3);

/*
|--------------------------------------------------------------------------
| Data barang multi dari form input ba_pemutihan.php
|--------------------------------------------------------------------------
*/
$barang_id_pt   = (isset($_POST['barang_id_pt']) && is_array($_POST['barang_id_pt'])) ? $_POST['barang_id_pt'] : array();
$barang_pt_asal            = (isset($_POST['barang_pt_asal']) && is_array($_POST['barang_pt_asal'])) ? $_POST['barang_pt_asal'] : array();
$barang_po                 = (isset($_POST['barang_po']) && is_array($_POST['barang_po'])) ? $_POST['barang_po'] : array();
$barang_coa                = (isset($_POST['barang_coa']) && is_array($_POST['barang_coa'])) ? $_POST['barang_coa'] : array();
$barang_kode               = (isset($_POST['barang_kode_assets']) && is_array($_POST['barang_kode_assets'])) ? $_POST['barang_kode_assets'] : array();
$barang_merk               = (isset($_POST['barang_merk']) && is_array($_POST['barang_merk'])) ? $_POST['barang_merk'] : array();
$barang_sn                 = (isset($_POST['barang_sn']) && is_array($_POST['barang_sn'])) ? $_POST['barang_sn'] : array();
$barang_user               = (isset($_POST['barang_user']) && is_array($_POST['barang_user'])) ? $_POST['barang_user'] : array();
$barang_harga_beli         = (isset($_POST['barang_harga_beli']) && is_array($_POST['barang_harga_beli'])) ? $_POST['barang_harga_beli'] : array();
$barang_tahun_perolehan    = (isset($_POST['barang_tahun_perolehan']) && is_array($_POST['barang_tahun_perolehan'])) ? $_POST['barang_tahun_perolehan'] : array();
$barang_alasan_penghapusan = (isset($_POST['barang_alasan_penghapusan']) && is_array($_POST['barang_alasan_penghapusan'])) ? $_POST['barang_alasan_penghapusan'] : array();
$barang_kondisi            = (isset($_POST['barang_kondisi']) && is_array($_POST['barang_kondisi'])) ? $_POST['barang_kondisi'] : array();

$barangCount = max(
    count($barang_id_pt),
    count($barang_pt_asal),
    count($barang_po),
    count($barang_coa),
    count($barang_kode),
    count($barang_merk),
    count($barang_sn),
    count($barang_user),
    count($barang_harga_beli),
    count($barang_tahun_perolehan),
    count($barang_alasan_penghapusan),
    count($barang_kondisi)
);

if ($barangCount <= 0) {
    $_SESSION['message'] = "Minimal 1 data barang harus dipilih.";
    header("Location: ba_pemutihan.php?status=gagal");
    exit();
}

/*
|--------------------------------------------------------------------------
| Proses simpan
|--------------------------------------------------------------------------
*/
$koneksi->autocommit(false);

try {
    $sql = "INSERT INTO berita_acara_pemutihan (
            tanggal,
            nomor_ba,
            nama_pembuat,
            pt,
            id_pt,
            pembuat,
            jabatan_pembuat,
            pemeriksa,
            jabatan_pemeriksa,
            diketahui1,
            jabatan_diketahui1,
            diketahui2,
            jabatan_diketahui2,
            diketahui3,
            jabatan_diketahui3,
            dibukukan,
            jabatan_dibukukan,
            disetujui1,
            jabatan_disetujui1,
            disetujui2,
            jabatan_disetujui2,
            disetujui3,
            jabatan_disetujui3
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
        )";

    $stmt = $koneksi->prepare($sql);
    if (!$stmt) {
        throw new Exception("Prepare insert berita_acara_pemutihan gagal: " . $koneksi->error);
    }

    $stmt->bind_param(
        "ssssissssssssssssssssss",
        $tanggal,
        $nomor_ba,
        $nama_pembuat,
        $pt,
        $id_pt,
        $pembuat,
        $jabatan_pembuat,
        $pemeriksa,
        $jabatan_pemeriksa,
        $diketahui1,
        $jabatan_diketahui1,
        $diketahui2,
        $jabatan_diketahui2,
        $diketahui3,
        $jabatan_diketahui3,
        $dibukukan,
        $jabatan_dibukukan,
        $disetujui1,
        $jabatan_disetujui1,
        $disetujui2,
        $jabatan_disetujui2,
        $disetujui3,
        $jabatan_disetujui3
    );

    if (!$stmt->execute()) {
        throw new Exception("Execute insert berita_acara_pemutihan gagal: " . $stmt->error);
    }

    $id_ba = $stmt->insert_id;
    $stmt->close();

    /*
    |--------------------------------------------------------------------------
    | Simpan multi barang
    |--------------------------------------------------------------------------
    | Tabel: barang_pemutihan
    | Kolom: pt, po, coa, kode_assets, merk, sn, user
    |--------------------------------------------------------------------------
    */
    $sqlBarang = "INSERT INTO barang_pemutihan
                (id_ba, id_pt, pt, po, coa, kode_assets, merk, sn, user, harga_beli, tahun_perolehan, alasan_penghapusan, kondisi)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmtBarang = $koneksi->prepare($sqlBarang);

    if (!$stmtBarang) {
        throw new Exception("Prepare insert barang_pemutihan gagal: " . $koneksi->error);
    }

    for ($i = 0; $i < $barangCount; $i++) {
        $id_pt_barang = isset($barang_id_pt[$i]) ? (int)$barang_id_pt[$i] : 0;
        $pt_barang    = isset($barang_pt_asal[$i]) ? normalizeString($barang_pt_asal[$i], '-') : '-';
        $po_barang    = isset($barang_po[$i]) ? normalizeString($barang_po[$i], '-') : '-';
        $coa_barang   = isset($barang_coa[$i]) ? normalizeString($barang_coa[$i], '-') : '-';
        $kode_barang  = isset($barang_kode[$i]) ? normalizeString($barang_kode[$i], '-') : '-';
        $merk_barang  = isset($barang_merk[$i]) ? normalizeString($barang_merk[$i], '-') : '-';
        $sn_barang    = isset($barang_sn[$i]) ? normalizeString($barang_sn[$i], '-') : '-';
        $user_barang  = isset($barang_user[$i]) ? normalizeString($barang_user[$i], '-') : '-';

        $harga_barang = isset($barang_harga_beli[$i]) ? (int)preg_replace('/[^0-9]/', '', $barang_harga_beli[$i]) : 0;
        $tahun_barang = isset($barang_tahun_perolehan[$i]) ? (int)preg_replace('/[^0-9]/', '', $barang_tahun_perolehan[$i]) : 0;

        $alasan_barang  = isset($barang_alasan_penghapusan[$i]) ? trim((string)$barang_alasan_penghapusan[$i]) : '';
        $kondisi_barang = isset($barang_kondisi[$i]) ? trim((string)$barang_kondisi[$i]) : '';

        if (
            $id_pt_barang === 0 &&
            $pt_barang === '-' &&
            $po_barang === '-' &&
            $coa_barang === '-' &&
            $kode_barang === '-' &&
            $merk_barang === '-' &&
            $sn_barang === '-' &&
            $user_barang === '-'
        ) {
            continue;
        }

        if ($id_pt_barang <= 0 && isset($pt_map[$pt_barang])) {
            $id_pt_barang = (int)$pt_map[$pt_barang];
        }

        if ($alasan_barang === '' || $kondisi_barang === '') {
            throw new Exception("Alasan penghapusan dan kondisi wajib diisi untuk setiap barang.");
        }

        $stmtBarang->bind_param(
            "iisssssssiiss",
            $id_ba,
            $id_pt_barang,
            $pt_barang,
            $po_barang,
            $coa_barang,
            $kode_barang,
            $merk_barang,
            $sn_barang,
            $user_barang,
            $harga_barang,
            $tahun_barang,
            $alasan_barang,
            $kondisi_barang
        );

        if (!$stmtBarang->execute()) {
            throw new Exception("Execute insert barang_pemutihan gagal: " . $stmtBarang->error);
        }
    }

    $stmtBarang->close();

    /*
    |--------------------------------------------------------------------------
    | Upload multi gambar
    |--------------------------------------------------------------------------
    */
    $upload_dir = '../assets/database-gambar/';
    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0777, true)) {
            throw new Exception("Folder upload gambar tidak bisa dibuat.");
        }
    }

    if (isset($_FILES['gambar']) && isset($_FILES['gambar']['name']) && is_array($_FILES['gambar']['name'])) {
        $ket_gambar = (isset($_POST['keterangan_gambar']) && is_array($_POST['keterangan_gambar'])) ? $_POST['keterangan_gambar'] : array();

        $sqlGambar = "INSERT INTO gambar_ba_pemutihan (id_ba, file_path, keterangan)
                      VALUES (?, ?, ?)";
        $stmtGambar = $koneksi->prepare($sqlGambar);

        if (!$stmtGambar) {
            throw new Exception("Prepare insert gambar_ba_pemutihan gagal: " . $koneksi->error);
        }

        foreach ($_FILES['gambar']['tmp_name'] as $key => $tmp_name) {
            if (!isset($_FILES['gambar']['error'][$key]) || $_FILES['gambar']['error'][$key] !== 0) {
                continue;
            }

            if (empty($tmp_name)) {
                continue;
            }

            $namaAsli = basename($_FILES['gambar']['name'][$key]);
            $namaAsli = preg_replace('/[^A-Za-z0-9\.\-_]/', '_', $namaAsli);
            $targetPath = $upload_dir . time() . '_' . $key . '_' . $namaAsli;
            $keterangan = isset($ket_gambar[$key]) ? trim((string)$ket_gambar[$key]) : '';

            if (!move_uploaded_file($tmp_name, $targetPath)) {
                throw new Exception("Gagal upload gambar: " . $namaAsli);
            }

            $stmtGambar->bind_param("iss", $id_ba, $targetPath, $keterangan);

            if (!$stmtGambar->execute()) {
                throw new Exception("Execute insert gambar_ba_pemutihan gagal: " . $stmtGambar->error);
            }
        }

        $stmtGambar->close();
    }

    $koneksi->commit();
    $koneksi->autocommit(true);
    $koneksi->close();

    $_SESSION['message'] = "Data berhasil disimpan ke database.";
    header("Location: ba_pemutihan.php?status=sukses");
    exit();
} catch (Exception $e) {
    $koneksi->rollback();
    $koneksi->autocommit(true);
    error_log("Gagal simpan BA Pemutihan: " . $e->getMessage());

    $_SESSION['message'] = "Gagal menyimpan data.";
    header("Location: ba_pemutihan.php?status=gagal");
    exit();
}
