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
| Ambil data form utama BA Pengembalian
|--------------------------------------------------------------------------
*/
$nomor_ba           = isset($_POST['nomor_ba']) ? trim($_POST['nomor_ba']) : '';
$tanggal            = isset($_POST['tanggal']) ? trim($_POST['tanggal']) : '';
$pt                 = isset($_POST['pt']) ? trim($_POST['pt']) : '';
$id_pt              = isset($_POST['id_pt']) ? (int)$_POST['id_pt'] : 0;
$nama_pembuat       = isset($_POST['nama_pembuat']) && trim($_POST['nama_pembuat']) !== ''
                    ? trim($_POST['nama_pembuat'])
                    : (isset($_SESSION['nama']) ? trim($_SESSION['nama']) : '-');

$pengembali         = isset($_POST['pengembali']) ? trim($_POST['pengembali']) : '';
$jabatan_peminjam   = isset($_POST['jabatan_peminjam']) ? trim($_POST['jabatan_peminjam']) : '';
$jabatan_pengembali = isset($_POST['jabatan_pengembali']) ? trim($_POST['jabatan_pengembali']) : '';
$penerima           = isset($_POST['penerima']) ? trim($_POST['penerima']) : '';
$jabatan_penerima   = isset($_POST['jabatan_penerima']) ? trim($_POST['jabatan_penerima']) : '';
$diketahui          = isset($_POST['diketahui']) ? trim($_POST['diketahui']) : '';
$jabatan_diketahui  = isset($_POST['jabatan_diketahui']) ? trim($_POST['jabatan_diketahui']) : '';

$is_ho = ($pt === 'PT.MSAL (HO)');

if (!$is_ho && $jabatan_peminjam !== '') {
    $jabatan_pengembali = $jabatan_peminjam;
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

/*
|--------------------------------------------------------------------------
| Validasi & tentukan id_pt
|--------------------------------------------------------------------------
*/
if ($id_pt <= 0 && isset($pt_map[$pt])) {
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
    $jabatan_diketahui = $ktu_data['posisi'];
}

$jabatan_pengembali = normalizeString($jabatan_pengembali, '-');
$jabatan_penerima   = normalizeString($jabatan_penerima, '-');
$jabatan_diketahui  = normalizeString($jabatan_diketahui, '-');

/*
|--------------------------------------------------------------------------
| Default field wajib tabel berita_acara_pengembalian_v2
|--------------------------------------------------------------------------
*/
$approval_1 = 0;
$approval_2 = 0;
$approval_3 = 0;

$dihapus = 0;
$pending_hapus = 0;
$pending_hapus_approver = '';
$alasan_hapus = '';

/*
|--------------------------------------------------------------------------
| Data barang multi dari form input ba_pengembalian.php
|--------------------------------------------------------------------------
*/
$barang_id_pt            = (isset($_POST['barang_id_pt']) && is_array($_POST['barang_id_pt'])) ? $_POST['barang_id_pt'] : array();
$barang_pt               = (isset($_POST['barang_pt']) && is_array($_POST['barang_pt'])) ? $_POST['barang_pt'] : array();
$barang_po               = (isset($_POST['barang_po']) && is_array($_POST['barang_po'])) ? $_POST['barang_po'] : array();
$barang_coa              = (isset($_POST['barang_coa']) && is_array($_POST['barang_coa'])) ? $_POST['barang_coa'] : array();
$barang_kode             = (isset($_POST['barang_kode_assets']) && is_array($_POST['barang_kode_assets'])) ? $_POST['barang_kode_assets'] : array();
$barang_merk             = (isset($_POST['barang_merk']) && is_array($_POST['barang_merk'])) ? $_POST['barang_merk'] : array();
$barang_sn               = (isset($_POST['barang_sn']) && is_array($_POST['barang_sn'])) ? $_POST['barang_sn'] : array();
$barang_user             = (isset($_POST['barang_user']) && is_array($_POST['barang_user'])) ? $_POST['barang_user'] : array();
$barang_harga_beli       = (isset($_POST['barang_harga_beli']) && is_array($_POST['barang_harga_beli'])) ? $_POST['barang_harga_beli'] : array();
$barang_tahun_perolehan  = (isset($_POST['barang_tahun_perolehan']) && is_array($_POST['barang_tahun_perolehan'])) ? $_POST['barang_tahun_perolehan'] : array();
$barang_keterangan       = (isset($_POST['barang_keterangan']) && is_array($_POST['barang_keterangan'])) ? $_POST['barang_keterangan'] : array();
$barang_kondisi          = (isset($_POST['barang_kondisi']) && is_array($_POST['barang_kondisi'])) ? $_POST['barang_kondisi'] : array();

$barangCount = max(
    count($barang_id_pt),
    count($barang_pt),
    count($barang_po),
    count($barang_coa),
    count($barang_kode),
    count($barang_merk),
    count($barang_sn),
    count($barang_user),
    count($barang_harga_beli),
    count($barang_tahun_perolehan),
    count($barang_keterangan),
    count($barang_kondisi)
);

if ($barangCount <= 0) {
    $_SESSION['message'] = "Minimal 1 data barang harus dipilih.";
    header("Location: ba_pengembalian.php?status=gagal");
    exit();
}

/*
|--------------------------------------------------------------------------
| Proses simpan
|--------------------------------------------------------------------------
*/
$koneksi->autocommit(false);

try {
    /*
    |----------------------------------------------------------------------
    | Simpan header BA Pengembalian
    |----------------------------------------------------------------------
    */
    $sql = "INSERT INTO berita_acara_pengembalian_v2 (
                nomor_ba,
                tanggal,
                nama_pembuat,
                pt,
                id_pt,
                pengembali,
                jabatan_pengembali,
                penerima,
                jabatan_penerima,
                diketahui,
                jabatan_diketahui,
                approval_1,
                approval_2,
                approval_3,
                dihapus,
                pending_hapus,
                pending_hapus_approver,
                alasan_hapus
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
            )";

    $stmt = $koneksi->prepare($sql);
    if (!$stmt) {
        throw new Exception("Prepare insert berita_acara_pengembalian_v2 gagal: " . $koneksi->error);
    }

    $stmt->bind_param(
        "ssssissssssiiiisss",
        $nomor_ba,
        $tanggal,
        $nama_pembuat,
        $pt,
        $id_pt,
        $pengembali,
        $jabatan_pengembali,
        $penerima,
        $jabatan_penerima,
        $diketahui,
        $jabatan_diketahui,
        $approval_1,
        $approval_2,
        $approval_3,
        $dihapus,
        $pending_hapus,
        $pending_hapus_approver,
        $alasan_hapus
    );

    if (!$stmt->execute()) {
        throw new Exception("Execute insert berita_acara_pengembalian_v2 gagal: " . $stmt->error);
    }

    $id_ba = $stmt->insert_id;
    $stmt->close();

    /*
    |----------------------------------------------------------------------
    | Simpan multi barang
    | Tabel: barang_pengembalian_v2
    |----------------------------------------------------------------------
    */
    $sqlBarang = "INSERT INTO barang_pengembalian_v2
                (id_ba, pt, id_pt, po, coa, kode_assets, merk, sn, user, tahun_perolehan, harga_beli, kondisi, keterangan)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmtBarang = $koneksi->prepare($sqlBarang);

    if (!$stmtBarang) {
        throw new Exception("Prepare insert barang_pengembalian_v2 gagal: " . $koneksi->error);
    }

    for ($i = 0; $i < $barangCount; $i++) {
        $id_pt_barang = isset($barang_id_pt[$i]) ? (int)$barang_id_pt[$i] : 0;
        $pt_barang    = isset($barang_pt[$i]) ? normalizeString($barang_pt[$i], '-') : '-';
        $po_barang    = isset($barang_po[$i]) ? normalizeString($barang_po[$i], '-') : '-';
        $coa_barang   = isset($barang_coa[$i]) ? normalizeString($barang_coa[$i], '-') : '-';
        $kode_barang  = isset($barang_kode[$i]) ? normalizeString($barang_kode[$i], '-') : '-';
        $merk_barang  = isset($barang_merk[$i]) ? normalizeString($barang_merk[$i], '-') : '-';
        $sn_barang    = isset($barang_sn[$i]) ? normalizeString($barang_sn[$i], '-') : '-';
        $user_barang  = isset($barang_user[$i]) ? normalizeString($barang_user[$i], '-') : '-';

        $tahun_barang = isset($barang_tahun_perolehan[$i]) ? (int)preg_replace('/[^0-9]/', '', $barang_tahun_perolehan[$i]) : 0;
        $harga_barang = isset($barang_harga_beli[$i]) ? (int)preg_replace('/[^0-9]/', '', $barang_harga_beli[$i]) : 0;

        $keterangan_barang = isset($barang_keterangan[$i]) ? trim((string)$barang_keterangan[$i]) : '';
        $kondisi_barang    = isset($barang_kondisi[$i]) ? trim((string)$barang_kondisi[$i]) : '';

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

        if ($keterangan_barang === '' || $kondisi_barang === '') {
            throw new Exception("Keterangan dan kondisi wajib diisi untuk setiap barang.");
        }

        $kondisi_barang = trim((string)$kondisi_barang);

        if ($kondisi_barang !== 'Baik' && $kondisi_barang !== 'Rusak') {
            throw new Exception("Nilai kondisi barang tidak valid.");
        }

        $stmtBarang->bind_param(
            "isissssssiiss",
            $id_ba,
            $pt_barang,
            $id_pt_barang,
            $po_barang,
            $coa_barang,
            $kode_barang,
            $merk_barang,
            $sn_barang,
            $user_barang,
            $tahun_barang,
            $harga_barang,
            $kondisi_barang,
            $keterangan_barang
        );

        if (!$stmtBarang->execute()) {
            throw new Exception("Execute insert barang_pengembalian_v2 gagal: " . $stmtBarang->error);
        }
    }

    $stmtBarang->close();

    /*
    |----------------------------------------------------------------------
    | Upload multi gambar
    | Tabel: gambar_ba_pengembalian_v2
    |----------------------------------------------------------------------
    */
    $upload_dir = '../assets/database-gambar/';
    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0777, true)) {
            throw new Exception("Folder upload gambar tidak bisa dibuat.");
        }
    }

    if (isset($_FILES['gambar']) && isset($_FILES['gambar']['name']) && is_array($_FILES['gambar']['name'])) {
        $ket_gambar = (isset($_POST['keterangan_gambar']) && is_array($_POST['keterangan_gambar'])) ? $_POST['keterangan_gambar'] : array();

        $sqlGambar = "INSERT INTO gambar_ba_pengembalian_v2 (id_ba, file_path, keterangan)
                      VALUES (?, ?, ?)";
        $stmtGambar = $koneksi->prepare($sqlGambar);

        if (!$stmtGambar) {
            throw new Exception("Prepare insert gambar_ba_pengembalian_v2 gagal: " . $koneksi->error);
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
                throw new Exception("Execute insert gambar_ba_pengembalian_v2 gagal: " . $stmtGambar->error);
            }
        }

        $stmtGambar->close();
    }

    $koneksi->commit();
    $koneksi->autocommit(true);
    $koneksi->close();

    $_SESSION['message'] = "Data berhasil disimpan ke database.";
    header("Location: ba_pengembalian.php?status=sukses");
    exit();
} catch (Exception $e) {
    $koneksi->rollback();
    $koneksi->autocommit(true);
    error_log("Gagal simpan BA Pengembalian: " . $e->getMessage());

    $_SESSION['message'] = "Gagal menyimpan data: " . $e->getMessage();
    header("Location: ba_pengembalian.php?status=gagal");
    exit();
}