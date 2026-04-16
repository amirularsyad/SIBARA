<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../login_registrasi.php");
    exit();
}

if (!isset($_SESSION['hak_akses']) || $_SESSION['hak_akses'] !== 'Admin') {
    header("Location: ../personal/approval.php");
    exit();
}

include '../koneksi.php';

function fetchOneRow($koneksi, $sql, $types, $params) {
    $stmt = $koneksi->prepare($sql);
    if (!$stmt) return null;

    if ($types !== '' && !empty($params)) {
        // bind_param butuh reference pada PHP 5.6
        $bind_names = array();
        $bind_names[] = $types;
        for ($i = 0; $i < count($params); $i++) {
            $bind_name = 'p' . $i;
            $$bind_name = $params[$i];
            $bind_names[] = &$$bind_name;
        }
        call_user_func_array(array($stmt, 'bind_param'), $bind_names);
    }

    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();
    return $row;
}

// HO: ambil jabatan + departemen (format "jabatan - departemen")
function getJabatanHO($koneksi, $nama) {
    if ($nama === null || trim($nama) === '') return '-';
    $row = fetchOneRow(
        $koneksi,
        "SELECT jabatan, departemen FROM data_karyawan WHERE nama = ? AND dihapus = 0 LIMIT 1",
        "s",
        array($nama)
    );
    if (!$row) return '-';
    $jab = isset($row['jabatan']) ? trim($row['jabatan']) : '';
    $dep = isset($row['departemen']) ? trim($row['departemen']) : '';
    if ($jab === '' && $dep === '') return '-';
    if ($dep === '') return $jab;
    if ($jab === '') return $dep;
    return $jab . " - " . $dep;
}

// Non-HO: ambil posisi dari data_karyawan_test
function getPosisiNonHO($koneksi, $nama) {
    if ($nama === null || trim($nama) === '') return '-';
    $row = fetchOneRow(
        $koneksi,
        "SELECT posisi FROM data_karyawan_test WHERE nama = ? AND dihapus = 0 LIMIT 1",
        "s",
        array($nama)
    );
    if (!$row) return '-';
    $pos = isset($row['posisi']) ? trim($row['posisi']) : '';
    return ($pos === '') ? '-' : $pos;
}

function getJabatanByLokasi($koneksi, $lokasi, $nama) {
    if ($lokasi === "PT.MSAL (HO)") {
        return getJabatanHO($koneksi, $nama);
    }
    return getPosisiNonHO($koneksi, $nama);
}

// ===== Ambil data form =====
$tanggal        = isset($_POST['tanggal']) ? $_POST['tanggal'] : null;
$nomor_ba       = isset($_POST['nomor_ba']) ? $_POST['nomor_ba'] : null;
$lokasi_asal    = isset($_POST['lokasi_asal']) ? $_POST['lokasi_asal'] : null;
$lokasi_tujuan  = isset($_POST['lokasi_tujuan']) ? $_POST['lokasi_tujuan'] : null;

$nama_pengirim  = isset($_POST['nama_pengirim']) ? $_POST['nama_pengirim'] : null;
$nama_pengirim2 = isset($_POST['nama_pengirim2']) ? $_POST['nama_pengirim2'] : null;

$nama_penerima  = isset($_POST['nama_penerima']) ? $_POST['nama_penerima'] : null;
$nama_penerima2 = isset($_POST['nama_penerima2']) ? $_POST['nama_penerima2'] : null;

$keterangan     = isset($_POST['keterangan']) ? $_POST['keterangan'] : '';
$pembuat        = isset($_SESSION['nama']) ? $_SESSION['nama'] : '';

// validasi kosong
$kosong = array();
if (empty($tanggal))        $kosong[] = 'Tanggal';
if (empty($nomor_ba))       $kosong[] = 'Nomor BA';
if (empty($lokasi_asal))    $kosong[] = 'Lokasi Asal';
if (empty($lokasi_tujuan))  $kosong[] = 'Lokasi Tujuan';
if (empty($nama_pengirim))  $kosong[] = 'Nama Pengirim';
if (empty($nama_pengirim2)) $kosong[] = 'Nama Pengirim 2';
if (empty($nama_penerima))  $kosong[] = 'Nama Penerima';
if (empty($nama_penerima2)) $kosong[] = 'Nama Penerima 2';

if (!empty($kosong)) {
    $_SESSION['message'] = "Data Kosong: " . implode(', ', $kosong);
    header("Location: ba_mutasi.php?status=gagal");
    exit();
}

// validasi tambahan: asal != tujuan
if ($lokasi_asal === $lokasi_tujuan) {
    $_SESSION['message'] = "Lokasi Asal dan Lokasi Tujuan tidak boleh sama.";
    header("Location: ba_mutasi.php?status=gagal");
    exit();
}

// ===== Default aktor tetap dari HO =====
$diketahui   = '';
$pemeriksa1  = '';
$pemeriksa2  = '';
$penyetujui1 = '';
$penyetujui2 = '';

// Ambil Dept.Head MIS (HO)
$row = fetchOneRow($koneksi,
    "SELECT nama FROM data_karyawan WHERE jabatan='Dept. Head' AND departemen='MIS' AND dihapus=0 LIMIT 1",
    "", array()
);
if ($row) $diketahui = $row['nama'];

// Ambil pemeriksa1 (Dept.Head HRO?) -> cek konsistensi departemen kamu
$row = fetchOneRow($koneksi,
    "SELECT nama FROM data_karyawan WHERE jabatan='Dept. Head' AND departemen='HRO' AND dihapus=0 LIMIT 1",
    "", array()
);
if ($row) $pemeriksa1 = $row['nama'];

// Ambil pemeriksa2 (Dept.Head Accounting)
$row = fetchOneRow($koneksi,
    "SELECT nama FROM data_karyawan WHERE jabatan='Dept. Head' AND departemen='ACCOUNTING' AND dihapus=0 LIMIT 1",
    "", array()
);
if ($row) $pemeriksa2 = $row['nama'];

// Ambil penyetujui1 (Direktur HRD)
$row = fetchOneRow($koneksi,
    "SELECT nama FROM data_karyawan WHERE jabatan='Direktur' AND departemen='HRD' AND dihapus=0 LIMIT 1",
    "", array()
);
if ($row) $penyetujui1 = $row['nama'];

// Ambil penyetujui2 (Direktur Finance)
$row = fetchOneRow($koneksi,
    "SELECT nama FROM data_karyawan WHERE posisi='Direktur Finance' AND dihapus=0 LIMIT 1",
    "", array()
);
if ($row) $penyetujui2 = $row['nama'];

// ===== HRD/GA Pengirim & Penerima (mengikuti lokasi) =====
$hrd_ga_pengirim = '';
$hrd_ga_penerima = '';

$ptNorm = "REPLACE(REPLACE(TRIM(pt), ', ', ','), ' ,', ',')";

if ($lokasi_asal === "PT.MSAL (HO)") {
    $row = fetchOneRow($koneksi,
        "SELECT nama FROM data_karyawan WHERE posisi='Staf GA' AND departemen='HRD' AND dihapus=0 LIMIT 1",
        "", array()
    );
    if ($row) $hrd_ga_pengirim = $row['nama'];
} else {
    $row = fetchOneRow($koneksi,
        "SELECT nama FROM data_karyawan_test WHERE posisi='Staf GA' AND FIND_IN_SET(?, $ptNorm) > 0 AND dihapus=0 LIMIT 1",
        "s", array($lokasi_asal)
    );
    if ($row) $hrd_ga_pengirim = $row['nama'];
}

if ($lokasi_tujuan === "PT.MSAL (HO)") {
    $row = fetchOneRow($koneksi,
        "SELECT nama FROM data_karyawan WHERE posisi='Staf GA' AND departemen='HRD' AND dihapus=0 LIMIT 1",
        "", array()
    );
    if ($row) $hrd_ga_penerima = $row['nama'];
} else {
    $row = fetchOneRow($koneksi,
        "SELECT nama FROM data_karyawan_test WHERE posisi='Staf GA' AND FIND_IN_SET(?, $ptNorm) > 0 AND dihapus=0 LIMIT 1",
        "s", array($lokasi_tujuan)
    );
    if ($row) $hrd_ga_penerima = $row['nama'];
}

// ===== Ambil jabatan sesuai aturan =====
// Pengirim mengikuti lokasi_asal
$jab_pengirim1       = getJabatanByLokasi($koneksi, $lokasi_asal, $nama_pengirim);
$jab_pengirim2       = getJabatanByLokasi($koneksi, $lokasi_asal, $nama_pengirim2);
$jab_hrd_ga_pengirim = getJabatanByLokasi($koneksi, $lokasi_asal, $hrd_ga_pengirim);

// Penerima mengikuti lokasi_tujuan
$jab_penerima1       = getJabatanByLokasi($koneksi, $lokasi_tujuan, $nama_penerima);
$jab_penerima2       = getJabatanByLokasi($koneksi, $lokasi_tujuan, $nama_penerima2);
$jab_hrd_ga_penerima = getJabatanByLokasi($koneksi, $lokasi_tujuan, $hrd_ga_penerima);

// Sisanya selalu HO
$jab_diketahui        = getJabatanHO($koneksi, $diketahui);
$jab_pemeriksa1       = getJabatanHO($koneksi, $pemeriksa1);
$jab_pemeriksa2       = getJabatanHO($koneksi, $pemeriksa2);
$jab_penyetujui1      = getJabatanHO($koneksi, $penyetujui1);
$jab_penyetujui2      = getJabatanHO($koneksi, $penyetujui2);

// ===== TRANSAKSI =====
mysqli_autocommit($koneksi, false);

try {

    $id_pt_asal   = 0;
    $id_pt_tujuan = 0;
    // ===== Mapping PT -> ID =====
    $map_pt = array(
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

    // normalisasi string PT (biar aman dari spasi)
    $lokasi_asal_norm   = trim($lokasi_asal);
    $lokasi_tujuan_norm = trim($lokasi_tujuan);

    // ambil id pt sesuai mapping
    $id_pt_asal   = isset($map_pt[$lokasi_asal_norm])   ? (int)$map_pt[$lokasi_asal_norm]   : 0;
    $id_pt_tujuan = isset($map_pt[$lokasi_tujuan_norm]) ? (int)$map_pt[$lokasi_tujuan_norm] : 0;

    // kalau PT tidak ditemukan di mapping -> tolak (biar data konsisten)
    if ($id_pt_asal === 0 || $id_pt_tujuan === 0) {
        $_SESSION['message'] = "PT Asal/Tujuan tidak valid atau belum ada mapping ID-nya.";
        header("Location: ba_mutasi.php?status=gagal");
        exit();
    }

    $sqlInsertBA = "
        INSERT INTO berita_acara_mutasi
        (tanggal, nomor_ba, pembuat,
            pt_asal, id_pt_asal, pt_tujuan, id_pt_tujuan,
            keterangan,
            pengirim1, jabatan_pengirim1,
            pengirim2, jabatan_pengirim2,
            hrd_ga_pengirim, jabatan_hrd_ga_pengirim,
            penerima1, jabatan_penerima1,
            penerima2, jabatan_penerima2,
            hrd_ga_penerima, jabatan_hrd_ga_penerima,
            diketahui, jabatan_diketahui,
            pemeriksa1, jabatan_pemeriksa1,
            pemeriksa2, jabatan_pemeriksa2,
            penyetujui1, jabatan_penyetujui1,
            penyetujui2, jabatan_penyetujui2,
            dihapus, created_at
        )
        VALUES (?,?,?,?,?,?,?,?,
                ?,?, ?,?, ?,?,
                ?,?, ?,?, ?,?,
                ?,?, ?,?, ?,?,
                ?,?, ?,?,
                0, NOW())
    ";

    $stmtBA = $koneksi->prepare($sqlInsertBA);
    if (!$stmtBA) throw new Exception("Prepare insert BA gagal: " . $koneksi->error);

    $types = "ssssisis" . str_repeat("s", 22);

    $stmtBA->bind_param(
        $types,
        $tanggal, $nomor_ba, $pembuat,
        $lokasi_asal, $id_pt_asal, $lokasi_tujuan, $id_pt_tujuan,
        $keterangan,
        $nama_pengirim,  $jab_pengirim1,
        $nama_pengirim2, $jab_pengirim2,
        $hrd_ga_pengirim, $jab_hrd_ga_pengirim,
        $nama_penerima,  $jab_penerima1,
        $nama_penerima2, $jab_penerima2,
        $hrd_ga_penerima, $jab_hrd_ga_penerima,
        $diketahui,  $jab_diketahui,
        $pemeriksa1, $jab_pemeriksa1,
        $pemeriksa2, $jab_pemeriksa2,
        $penyetujui1, $jab_penyetujui1,
        $penyetujui2, $jab_penyetujui2
    );

    if (!$stmtBA->execute()) throw new Exception("Execute insert BA gagal: " . $stmtBA->error);
    $id_ba = $stmtBA->insert_id;
    $stmtBA->close();

    // ===== INSERT BARANG (prepared sekali, execute berkali-kali) =====
    $pt_asal_list = isset($_POST['pt_asal']) ? $_POST['pt_asal'] : array();
    $po_list      = isset($_POST['po'])     ? $_POST['po']      : array();
    $coa_list     = isset($_POST['coa'])    ? $_POST['coa']     : array();
    $kode_list    = isset($_POST['kode'])   ? $_POST['kode']    : array();
    $merk_list    = isset($_POST['merk'])   ? $_POST['merk']    : array();
    $sn_list      = isset($_POST['sn'])     ? $_POST['sn']      : array();
    $user_list    = isset($_POST['user'])   ? $_POST['user']    : array();

    $total = count($sn_list); // biasanya SN jadi patokan
    if ($total > 0) {
        $stmtBarang = $koneksi->prepare("
            INSERT INTO barang_mutasi (id_ba, pt_asal, po, coa, kode_assets, merk, sn, user, created_at)
            VALUES (?,?,?,?,?,?,?,?, NOW())
        ");
        if (!$stmtBarang) throw new Exception("Prepare insert barang gagal: " . $koneksi->error);

        for ($i = 0; $i < $total; $i++) {
            $pt_asal = isset($pt_asal_list[$i]) ? $pt_asal_list[$i] : '';
            $po      = isset($po_list[$i])      ? $po_list[$i]      : '';
            $coa     = isset($coa_list[$i])     ? $coa_list[$i]     : '';
            $kode    = isset($kode_list[$i])    ? $kode_list[$i]    : '';
            $merk    = isset($merk_list[$i])    ? $merk_list[$i]    : '';
            $sn      = isset($sn_list[$i])      ? $sn_list[$i]      : '';
            $user    = isset($user_list[$i])    ? $user_list[$i]    : '';

            $stmtBarang->bind_param("isssssss", $id_ba, $pt_asal, $po, $coa, $kode, $merk, $sn, $user);
            if (!$stmtBarang->execute()) throw new Exception("Insert barang gagal: " . $stmtBarang->error);
        }
        $stmtBarang->close();
    }

    // ===== UPLOAD GAMBAR =====
    $upload_dir = '../assets/database-gambar/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    if (!empty($_FILES['gambar']['name'][0])) {
        $stmtImg = $koneksi->prepare("
            INSERT INTO gambar_ba_mutasi (id_ba, file_path, uploaded_at)
            VALUES (?, ?, NOW())
        ");
        if (!$stmtImg) throw new Exception("Prepare insert gambar gagal: " . $koneksi->error);

        foreach ($_FILES['gambar']['tmp_name'] as $key => $tmp_name) {
            if (empty($tmp_name)) continue;

            $filename = basename($_FILES['gambar']['name'][$key]);
            $safe_filename = preg_replace("/[^a-zA-Z0-9\._-]/", "_", $filename);

            // pakai uniqid biar tidak tabrakan
            $target_path = $upload_dir . uniqid('bam_', true) . '_' . $safe_filename;

            if (move_uploaded_file($tmp_name, $target_path)) {
                $stmtImg->bind_param("is", $id_ba, $target_path);
                if (!$stmtImg->execute()) throw new Exception("Insert gambar gagal: " . $stmtImg->error);
            }
        }
        $stmtImg->close();
    }

    // commit transaksi
    mysqli_commit($koneksi);
    mysqli_autocommit($koneksi, true);

    $_SESSION['message'] = "Data berita acara berhasil dibuat.";
    header("Location: ba_mutasi.php?status=sukses");
    exit();

} catch (Exception $e) {
    mysqli_rollback($koneksi);
    mysqli_autocommit($koneksi, true);

    $_SESSION['message'] = "Gagal menyimpan: " . $e->getMessage();
    header("Location: ba_mutasi.php?status=gagal");
    exit();
}
?>