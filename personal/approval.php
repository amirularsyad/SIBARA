<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['nama'])) {
    header("Location: ../login_registrasi.php");
    exit;
}

$manajemen_akun_akses = 0;
if (isset($_SESSION['nama'])) {
    $namaLogin = $_SESSION['nama'];
    $sqlAkses = "SELECT manajemen_akun_akses, warna_menu FROM akun_akses WHERE nama = ? LIMIT 1";
    if ($stmt = $koneksi->prepare($sqlAkses)) {
        $stmt->bind_param("s", $namaLogin);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $rowAkses = $res->fetch_assoc()) {
            $manajemen_akun_akses = (int)$rowAkses['manajemen_akun_akses'];
            $warna_menu = $rowAkses['warna_menu'];
            $_SESSION['manajemen_akun_akses'] = $manajemen_akun_akses;
        }
        $stmt->close();
    }
}

$showDataAkunMenu = false;

if ($_SESSION['hak_akses'] === 'Super Admin') {
    $showDataAkunMenu = true;
} else {
    if ($manajemen_akun_akses === 1) {
        $showDataAkunMenu = true;
    } elseif ($manajemen_akun_akses === 2) {
        $showDataAkunMenu = true;
    }
}

//Warna Menu
if ($warna_menu == "0" || $warna_menu === "" || is_null($warna_menu)) {
    // default (gradient)
    $bgMenu = 'linear-gradient(to bottom right, #3e02be 0%, rgb(1, 64, 159) 50%, rgb(2, 59, 159) 100%)';
} else {
    $bgMenu = $warna_menu;
}

//Warna Navbar
if ($warna_menu == "0" || $warna_menu === "" || is_null($warna_menu)) {
    // default (gradient)
    $bgNav = 'linear-gradient(to bottom right, #3e02be 0%, rgb(1, 64, 159) 50%, rgb(2, 59, 159) 100%)';
} else {
    $bgNav = $warna_menu;
}

if ($warna_menu === "0" || $warna_menu === "" || is_null($warna_menu)) {
    // default pakai gradient
    $textColorStyle = 'font-size: 3rem;
      font-weight: bold;
      background: linear-gradient(to bottom right,
        #1702d5,
        #2100a5,
        #0012ce,
        #3262ff,
        #5e74ff
      );
      background-size: 300% 300%;
      animation: gradient-shift 4s ease infinite;
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      color: transparent;';
} else {

    $textColorStyle = 'font-size: 3rem;
      font-weight: bold;
      color: ' . $warna_menu . ';';
}


$ptSekarang = $_SESSION['pt'];
if (is_array($ptSekarang)) {
    $ptSekarang = reset($ptSekarang);
}
$ptSekarang = trim($ptSekarang);


$namaUser = $_SESSION['nama'];

// $ptPertama  = '';
// if (isset($_SESSION['pt']) && is_array($_SESSION['pt']) && !empty($_SESSION['pt'])) {
//     $ptPertama = $_SESSION['pt'][0];
// }

// $filterPT   = '';
// $filterPT   = $ptPertama;
// if (!empty($_GET['pt'])) {
//     $filterPT = $_GET['pt'];
// }

// ==============================
// NORMALISASI PT USER (support multi PT)
// ==============================
$userPTs = array();

if (isset($_SESSION['pt'])) {
    if (is_array($_SESSION['pt'])) {
        $userPTs = $_SESSION['pt'];
    } else {
        $userPTs = array($_SESSION['pt']);
    }
}

$tmpPTs = array();
foreach ($userPTs as $p) {
    $p = trim($p);
    if ($p !== '') $tmpPTs[] = $p;
}
$userPTs = array_values(array_unique($tmpPTs));

if (count($userPTs) === 0) {
    $userPTs = array($ptSekarang);
}

$ptPertama = $userPTs[0];

$filterPT = isset($_GET['pt']) ? trim($_GET['pt']) : 'ALL';
if ($filterPT === '') $filterPT = 'ALL';

if ($filterPT !== 'ALL' && !in_array($filterPT, $userPTs, true)) {
    $filterPT = 'ALL';
}

$isAllPT = ($filterPT === 'ALL');

$hasHO = in_array('PT.MSAL (HO)', $userPTs, true);
$isAdmin = (isset($_SESSION['hak_akses']) && $_SESSION['hak_akses'] === 'Admin');

$sessionNama = isset($_SESSION['nama']) ? $_SESSION['nama'] : '';
$sessionNamaEsc = $koneksi->real_escape_string($sessionNama);

// Admin HO boleh lihat semua BA Pemutihan lintas PT
$adminHOPemutihanAll = ($hasHO && $isAdmin) ? "1=1" : "0=1";

// User TERLIBAT (aktor) boleh lihat BA Pemutihan meski PT surat bukan PT-nya
$pemutihanInvolved = "(
    bapem.pembuat = '$sessionNamaEsc'
    OR bapem.pemeriksa = '$sessionNamaEsc'
    OR bapem.diketahui1 = '$sessionNamaEsc'
    OR bapem.diketahui2 = '$sessionNamaEsc'
    OR bapem.diketahui3 = '$sessionNamaEsc'
    OR bapem.dibukukan = '$sessionNamaEsc'
    OR bapem.disetujui1 = '$sessionNamaEsc'
    OR bapem.disetujui2 = '$sessionNamaEsc'
    OR bapem.disetujui3 = '$sessionNamaEsc'

    OR bapem.pembuat_site = '$sessionNamaEsc'
    OR bapem.pemeriksa_site = '$sessionNamaEsc'
    OR bapem.diketahui1_site = '$sessionNamaEsc'
    OR bapem.disetujui1_site = '$sessionNamaEsc'
    OR bapem.diketahui2_site = '$sessionNamaEsc'
    OR bapem.diperiksa_site = '$sessionNamaEsc'
    OR bapem.mengetahui_site = '$sessionNamaEsc'
)";

// Admin HO boleh lihat semua mutasi lintas PT
$adminHOAll = ($hasHO && $isAdmin) ? "1=1" : "0=1";

// User TERLIBAT (aktor) boleh lihat meski PT asal/tujuan bukan PT-nya
$mutasiInvolved = "(
    bam.pengirim1 = '$sessionNamaEsc'
    OR bam.pengirim2 = '$sessionNamaEsc'
    OR bam.hrd_ga_pengirim = '$sessionNamaEsc'
    OR bam.penerima1 = '$sessionNamaEsc'
    OR bam.penerima2 = '$sessionNamaEsc'
    OR bam.hrd_ga_penerima = '$sessionNamaEsc'
    OR bam.diketahui = '$sessionNamaEsc'
    OR bam.pemeriksa1 = '$sessionNamaEsc'
    OR bam.pemeriksa2 = '$sessionNamaEsc'
    OR bam.penyetujui1 = '$sessionNamaEsc'
    OR bam.penyetujui2 = '$sessionNamaEsc'
)";

if ($isAllPT) {
    $inPT = sql_in_list($koneksi, $userPTs);
    $ptCondMutasi = "(bam.pt_asal IN ($inPT) OR bam.pt_tujuan IN ($inPT))";
} else {
    $pt = $koneksi->real_escape_string($filterPT);
    $ptCondMutasi = "(bam.pt_asal = '$pt' OR bam.pt_tujuan = '$pt')";
}

// $filterPT      = isset($_GET['pt']) ? $_GET['pt'] : '';
$filterJenisBA = isset($_GET['jenis_ba']) ? $_GET['jenis_ba'] : 'kerusakan';
$filterTahun   = isset($_GET['tahun']) ? $_GET['tahun'] : '';
$filterBulan   = isset($_GET['bulan']) ? $_GET['bulan'] : '';


$filtersKerusakan = [];
$filtersPengembalian = [];
$filtersNotebook = [];
$filtersMutasi = [];
$filtersSTAsset = [];
$filtersPemutihan = [];

function sql_in_list($mysqli, $arr)
{
    $out = array();
    foreach ($arr as $v) {
        $v = trim($v);
        if ($v === '') continue;
        $out[] = "'" . $mysqli->real_escape_string($v) . "'";
    }
    return implode(",", $out);
}

if ($isAllPT) {
    // ALL PT yang dimiliki user
    $inPT = sql_in_list($koneksi, $userPTs);

    // pakai alias tabel biar aman
    $filtersKerusakan[]    = "bak.pt IN ($inPT)";
    $filtersPengembalian[] = "bap.pt IN ($inPT)";
    $filtersNotebook[]     = "ban.pt IN ($inPT)";

    // Admin HO boleh lihat semua mutasi saat ALL
    $filtersMutasi[] = "(
        (bam.pt_asal IN ($inPT) OR bam.pt_tujuan IN ($inPT))
        OR $mutasiInvolved
        OR $adminHOAll
    )";

    $filtersSTAsset[]      = "basta.pt IN ($inPT)";
    $filtersPemutihan[] = "(
        bapem.pt IN ($inPT)
        OR $pemutihanInvolved
        OR $adminHOPemutihanAll
    )";

} else {
    // PT spesifik yang dipilih
    $pt = $koneksi->real_escape_string($filterPT);

    $filtersKerusakan[]    = "bak.pt = '$pt'";
    $filtersPengembalian[] = "bap.pt = '$pt'";
    $filtersNotebook[]     = "ban.pt = '$pt'";
    $filtersMutasi[] = "(
        (bam.pt_asal = '$pt' OR bam.pt_tujuan = '$pt')
        OR $mutasiInvolved
        OR $adminHOAll
    )";
    $filtersSTAsset[]      = "basta.pt = '$pt'";
    $filtersPemutihan[] = "(
        bapem.pt = '$pt'
        OR $pemutihanInvolved
        OR $adminHOPemutihanAll
    )";
}

if (!empty($filterTahun)) {
    $filtersKerusakan[]     = "YEAR(bak.tanggal) = " . intval($filterTahun);
    $filtersPengembalian[]  = "YEAR(bap.tanggal) = " . intval($filterTahun);
    $filtersNotebook[]      = "YEAR(ban.tanggal) = " . intval($filterTahun);
    $filtersMutasi[]        = "YEAR(bam.tanggal) = " . intval($filterTahun);
    $filtersSTAsset[]       = "YEAR(basta.tanggal) = " . intval($filterTahun);
    $filtersPemutihan[]     = "YEAR(bapem.tanggal) = " . intval($filterTahun);
}

if (!empty($filterBulan)) {
    $filtersKerusakan[]     = "MONTH(bak.tanggal) = " . intval($filterBulan);
    $filtersPengembalian[]  = "MONTH(bap.tanggal) = " . intval($filterBulan);
    $filtersNotebook[]      = "MONTH(ban.tanggal) = " . intval($filterBulan);
    $filtersMutasi[]        = "MONTH(bam.tanggal) = " . intval($filterBulan);
    $filtersSTAsset[]       = "MONTH(basta.tanggal) = " . intval($filterBulan);
    $filtersPemutihan[]     = "MONTH(bapem.tanggal) = " . intval($filterBulan);
}

$filtersKerusakan[] = "bak.dihapus = 0";
$filtersPengembalian[] = "bap.dihapus = 0";
$filtersMutasi[] = "bam.dihapus = 0";
$filtersSTAsset[] = "basta.dihapus = 0";
$filtersPemutihan[] = "bapem.dihapus = 0";

$whereKerusakan    = $filtersKerusakan ? " WHERE " . implode(" AND ", $filtersKerusakan) : "";
$wherePengembalian = $filtersPengembalian ? "WHERE " . implode(" AND ", $filtersPengembalian) : "";
$whereNotebook = $filtersNotebook ? "WHERE " . implode(" AND ", $filtersNotebook) : "";
$whereMutasi = $filtersMutasi ? "WHERE " . implode(" AND ", $filtersMutasi) : "";
$whereSTAsset = $filtersSTAsset ? "WHERE " . implode(" AND ", $filtersSTAsset) : "";
$wherePemutihan = $filtersPemutihan ? "WHERE " . implode(" AND ", $filtersPemutihan) : "";

$isApproverDeleteKerusakan = '';

// cek apakah user adalah approver hapus
$cekApproverDelete = $koneksi->query("
    SELECT pending_hapus_approver
    FROM berita_acara_kerusakan
    WHERE pending_hapus = 1
        AND pending_hapus_approver = '" . $koneksi->real_escape_string($_SESSION['nama']) . "'
    LIMIT 1
");

if ($cekApproverDelete && $cekApproverDelete->num_rows > 0) {
    $isApproverDeleteKerusakan = 'bak.pending_hapus';
}

$isApproverEditKerusakan = '';

$isApproverEditKerusakan = "
CASE 
    WHEN EXISTS (
        SELECT 1
        FROM history_n_temp_ba_kerusakan h
        WHERE h.id_ba = bak.id
          AND h.status = 1
          AND h.pending_approver = '" . $koneksi->real_escape_string($_SESSION['nama']) . "'
    ) THEN 1
    ELSE 0
END
";


// Ambil data Berita Acara Kerusakan
$baseQueryKerusakan = "
    SELECT 
        bak.id, 
        bak.tanggal, 
        bak.nomor_ba, 
        bak.pt,
        bak.approval_1, 
        bak.approval_2,
        bak.approval_3,
        bak.approval_4,
        bak.approval_5,
        bak.autograph_1,
        bak.autograph_2,
        bak.autograph_3,
        bak.autograph_4,
        bak.autograph_5,
        bak.pembuat,
        bak.peminjam,
        bak.atasan_peminjam,
        bak.penyetujui,
        bak.diketahui,
        bak.pending_hapus,
        bak.alasan_hapus,
        bak.pending_hapus_approver,
        k1.jabatan AS jabatan_aprv1,
        k1.departemen AS departemen_aprv1,
        k2.jabatan AS jabatan_aprv2,
        k2.departemen AS departemen_aprv2,
        k3.jabatan AS jabatan_aprv3,
        k3.departemen AS departemen_aprv3,
        k4.jabatan AS jabatan_aprv4,
        k4.departemen AS departemen_aprv4,
        k5.jabatan AS jabatan_aprv5,
        k5.departemen AS departemen_aprv5
    FROM berita_acara_kerusakan bak
    LEFT JOIN data_karyawan k1 
        ON bak.pembuat = k1.nama
    LEFT JOIN data_karyawan k2 
        ON bak.penyetujui = k2.nama
    LEFT JOIN data_karyawan k3
        ON bak.peminjam = k3.nama
    LEFT JOIN data_karyawan k4
        ON bak.atasan_peminjam = k4.nama
    LEFT JOIN data_karyawan k5
        ON bak.diketahui = k5.nama
    
";
$queryKerusakan = $baseQueryKerusakan . "
    $whereKerusakan
    ORDER BY 
    " . (!empty($isApproverDeleteKerusakan) ? "$isApproverDeleteKerusakan DESC," : "") . "
    " . (!empty($isApproverEditKerusakan) ? "$isApproverEditKerusakan DESC," : "") . "
    bak.tanggal DESC, bak.nomor_ba DESC 
";
$resultKerusakan = $koneksi->query($queryKerusakan);

$whereUserKerusakan = $whereKerusakan
    . (!empty($whereKerusakan) ? " AND " : " WHERE ") . "
        (
            bak.pembuat = '" . $koneksi->real_escape_string($namaUser) . "'
            OR bak.peminjam = '" . $koneksi->real_escape_string($namaUser) . "'
            OR bak.atasan_peminjam = '" . $koneksi->real_escape_string($namaUser) . "'
            OR bak.penyetujui = '" . $koneksi->real_escape_string($namaUser) . "'
            OR bak.diketahui = '" . $koneksi->real_escape_string($namaUser) . "'
        )";
$queryUserKerusakan = $baseQueryKerusakan . "
    $whereUserKerusakan
    ORDER BY 
    " . (!empty($isApproverDeleteKerusakan) ? "$isApproverDeleteKerusakan DESC," : "") . " 
    " . (!empty($isApproverEditKerusakan) ? "$isApproverEditKerusakan DESC," : "") . "
    bak.tanggal DESC, bak.nomor_ba DESC
";
$resultUserKerusakan = $koneksi->query($queryUserKerusakan);

// Query dasar 
$isApproverDeletePengembalian = '';

// cek apakah user adalah approver hapus
$cekApproverDeletePengembalian = $koneksi->query("
    SELECT pending_hapus_approver
    FROM berita_acara_pengembalian_v2
    WHERE pending_hapus = 1
      AND pending_hapus_approver = '" . $koneksi->real_escape_string($_SESSION['nama']) . "'
    LIMIT 1
");

if ($cekApproverDeletePengembalian && $cekApproverDeletePengembalian->num_rows > 0) {
    $isApproverDeletePengembalian = 'bap.pending_hapus';
}

$isApproverEditPengembalian = "
CASE 
    WHEN EXISTS (
        SELECT 1
        FROM history_n_temp_ba_pengembalian_v2 h
        WHERE h.id_ba = bap.id
          AND h.status = 1
          AND h.pending_approver = '" . $koneksi->real_escape_string($_SESSION['nama']) . "'
    ) THEN 1
    ELSE 0
END
";

$baseQueryPengembalian = "
    SELECT 
        bap.id,
        bap.tanggal,
        bap.nomor_ba,
        bap.pt,
        bap.approval_1,
        bap.approval_2,
        bap.approval_3,
        bap.autograph_1,
        bap.autograph_2,
        bap.autograph_3,
        bap.tanggal_approve_1,
        bap.tanggal_approve_2,
        bap.tanggal_approve_3,
        bap.pengembali,
        bap.jabatan_pengembali,
        bap.penerima,
        bap.jabatan_penerima,
        bap.diketahui,
        bap.jabatan_diketahui,
        bap.pending_hapus,
        bap.alasan_hapus,
        bap.pending_hapus_approver
    FROM berita_acara_pengembalian_v2 bap
";

$queryPengembalian = $baseQueryPengembalian . "
    $wherePengembalian
    ORDER BY
    " . (!empty($isApproverDeletePengembalian) ? "$isApproverDeletePengembalian DESC," : "") . "
    " . (!empty($isApproverEditPengembalian) ? "$isApproverEditPengembalian DESC," : "") . "
    bap.tanggal DESC, bap.nomor_ba DESC
";
$resultPengembalian = $koneksi->query($queryPengembalian);

$whereUserPengembalian = $wherePengembalian
    . (!empty($wherePengembalian) ? " AND " : " WHERE ") . "
    (
        bap.pengembali = '" . $koneksi->real_escape_string($namaUser) . "'
        OR bap.penerima = '" . $koneksi->real_escape_string($namaUser) . "'
        OR bap.diketahui = '" . $koneksi->real_escape_string($namaUser) . "'
    )";

$queryUserPengembalian = $baseQueryPengembalian . "
    $whereUserPengembalian
    ORDER BY
    " . (!empty($isApproverDeletePengembalian) ? "$isApproverDeletePengembalian DESC," : "") . "
    " . (!empty($isApproverEditPengembalian) ? "$isApproverEditPengembalian DESC," : "") . "
    bap.tanggal DESC, bap.nomor_ba DESC
";
$resultUserPengembalian = $koneksi->query($queryUserPengembalian);

$baseQueryNotebook = "
    SELECT 
            ban.id,
            ban.pertama,
            ban.nama_peminjam, 
            ban.saksi,
            ban.diketahui,
            ban.tanggal,
            ban.nomor_ba,
            ban.pt,
            ban.approval_1,
            ban.approval_2,
            ban.approval_3,
            ban.approval_4,
            ban.autograph_1,
            ban.autograph_2,
            ban.autograph_3,
            ban.autograph_4,
            ban.jabatan_pertama,
            k2.jabatan AS jabatan_aprv2,
            k2.departemen AS departemen_aprv2,
            k3.jabatan AS jabatan_aprv3,
            k3.departemen AS departemen_aprv3,
            k4.jabatan AS jabatan_aprv4,
            k4.departemen AS departemen_aprv4
        FROM ba_serah_terima_notebook ban
        LEFT JOIN data_karyawan k2 
            ON ban.nama_peminjam = k2.nama
        LEFT JOIN data_karyawan k3 
            ON ban.saksi = k3.nama
        LEFT JOIN data_karyawan k4 
            ON ban.diketahui = k4.nama    
";
// Query data pengembalian
$queryNotebook = $baseQueryNotebook . "
    $whereNotebook
    ORDER BY ban.tanggal DESC, ban.nomor_ba DESC
";
$resultNotebook = $koneksi->query($queryNotebook);

// Query identifikasi (gabungan filter + identifikasi user)
$whereUserNotebook = $whereNotebook
    . (!empty($whereNotebook) ? " AND " : " WHERE ") . "
    (
        ban.pertama = '" . $koneksi->real_escape_string($namaUser) . "'
        OR ban.nama_peminjam = '" . $koneksi->real_escape_string($namaUser) . "'
        OR ban.saksi = '" . $koneksi->real_escape_string($namaUser) . "'
        OR ban.diketahui = '" . $koneksi->real_escape_string($namaUser) . "'
    )";

$queryUserNotebook = $baseQueryNotebook . "
    $whereUserNotebook
    ORDER BY ban.tanggal DESC, ban.nomor_ba DESC
";
$resultUserNotebook = $koneksi->query($queryUserNotebook);

//---------------------------------------------------------------------------------------------



// Asal (pengirim, diketahui1, diketahui2)
$asalTable = "data_karyawan";
$asalJabatan = "jabatan";
$asalDepartemen = "departemen";

// Tujuan (penerima1, penerima2)
$tujuanTable = "data_karyawan";
$tujuanJabatan = "jabatan";
$tujuanDepartemen = "departemen";

// Karena setiap BA Mutasi bisa beda PT, cek dulu salah satu record untuk tahu asal dan tujuan
$cekPT = $koneksi->query("SELECT pt_asal, pt_tujuan FROM berita_acara_mutasi LIMIT 1");
if ($cekPT && $cekPT->num_rows > 0) {
    $rowPT = $cekPT->fetch_assoc();

    if ($rowPT['pt_asal'] !== "PT.MSAL (HO)") {
        $asalTable = "data_karyawan_test";
        $asalJabatan = "posisi";
        $asalDepartemen = "''"; // tidak ada kolom departemen
    }

    if ($rowPT['pt_tujuan'] !== "PT.MSAL (HO)") {
        $tujuanTable = "data_karyawan_test";
        $tujuanJabatan = "posisi";
        $tujuanDepartemen = "''";
    }
}

$isApproverDeleteMutasi = '';

// cek apakah user adalah approver hapus
$cekApproverDelete = $koneksi->query("
    SELECT pending_hapus_approver
    FROM berita_acara_mutasi
    WHERE pending_hapus = 1
      AND pending_hapus_approver = '" . $koneksi->real_escape_string($_SESSION['nama']) . "'
    LIMIT 1
");

if ($cekApproverDelete && $cekApproverDelete->num_rows > 0) {
    $isApproverDeleteMutasi = 'bam.pending_hapus';
}

$isApproverEditMutasi = '';

// cek apakah user adalah pending approver edit mutasi
$cekApproverEditMutasi = $koneksi->query("
    SELECT 1
    FROM history_n_temp_ba_mutasi
    WHERE pending_approver = '" . $koneksi->real_escape_string($_SESSION['nama']) . "'
        AND status = 1
    LIMIT 1
");

if ($cekApproverEditMutasi && $cekApproverEditMutasi->num_rows > 0) {
    $isApproverEditMutasi = "
    CASE 
        WHEN EXISTS (
            SELECT 1
            FROM history_n_temp_ba_mutasi h
            WHERE h.id_ba = bam.id
                AND h.status = 1
        ) THEN 1
        ELSE 0
    END
    ";
}

$baseQueryMutasi = "
SELECT DISTINCT
    bam.id,
    bam.tanggal,
    bam.nomor_ba,
    bam.pt_asal,
    bam.pt_tujuan,
    bam.approval_1,
    bam.approval_2,
    bam.approval_3,
    bam.approval_4,
    bam.approval_5,
    bam.approval_6,
    bam.approval_7,
    bam.approval_8,
    bam.approval_9,
    bam.approval_10,
    bam.approval_11,
    bam.autograph_1,
    bam.autograph_2,
    bam.autograph_3,
    bam.autograph_4,
    bam.autograph_5,
    bam.autograph_6,
    bam.autograph_7,
    bam.autograph_8,
    bam.autograph_9,
    bam.autograph_10,
    bam.autograph_11,
    bam.pengirim1,
    bam.pengirim2,
    bam.hrd_ga_pengirim,
    bam.penerima1,
    bam.penerima2,
    bam.hrd_ga_penerima,
    bam.diketahui,
    bam.pemeriksa1,
    bam.pemeriksa2,
    bam.penyetujui1,
    bam.penyetujui2,
    bam.pending_hapus,
    bam.pending_hapus_approver,
    bam.alasan_hapus,
    k1.$asalJabatan AS jabatan_aprv1,
    " . ($asalDepartemen !== "''" ? "k1.$asalDepartemen" : "''") . " AS departemen_aprv1,
    k2.$asalJabatan AS jabatan_aprv2,
    " . ($asalDepartemen !== "''" ? "k2.$asalDepartemen" : "''") . " AS departemen_aprv2,
    k3.$asalJabatan AS jabatan_aprv3,
    " . ($asalDepartemen !== "''" ? "k3.$asalDepartemen" : "''") . " AS departemen_aprv3,
    k4.$tujuanJabatan AS jabatan_aprv4,
    " . ($tujuanDepartemen !== "''" ? "k4.$tujuanDepartemen" : "''") . " AS departemen_aprv4,
    k5.$tujuanJabatan AS jabatan_aprv5,
    " . ($tujuanDepartemen !== "''" ? "k5.$tujuanDepartemen" : "''") . " AS departemen_aprv5,
    k6.$tujuanJabatan AS jabatan_aprv6,
    " . ($tujuanDepartemen !== "''" ? "k6.$tujuanDepartemen" : "''") . " AS departemen_aprv6,
    k7.$asalJabatan AS jabatan_aprv7,
    " . ($asalDepartemen !== "''" ? "k7.$asalDepartemen" : "''") . " AS departemen_aprv7,
    k8.jabatan AS jabatan_aprv8,
    k8.departemen AS departemen_aprv8,
    k9.jabatan AS jabatan_aprv9,
    k9.departemen AS departemen_aprv9,
    k10.jabatan AS jabatan_aprv10,
    k10.departemen AS departemen_aprv10,
    k11.jabatan AS jabatan_aprv11,
    k11.departemen AS departemen_aprv11
FROM berita_acara_mutasi bam
LEFT JOIN $asalTable k1 ON bam.pengirim1 = k1.nama
LEFT JOIN $asalTable k2 ON bam.pengirim2 = k2.nama
LEFT JOIN $asalTable k3 ON bam.hrd_ga_pengirim = k3.nama
LEFT JOIN $tujuanTable k4 ON bam.penerima1 = k4.nama
LEFT JOIN $tujuanTable k5 ON bam.penerima2 = k5.nama
LEFT JOIN $tujuanTable k6 ON bam.hrd_ga_penerima = k6.nama
LEFT JOIN $asalTable k7 ON bam.diketahui = k7.nama
LEFT JOIN data_karyawan k8 ON bam.pemeriksa1 = k8.nama
LEFT JOIN data_karyawan k9 ON bam.pemeriksa2 = k9.nama
LEFT JOIN data_karyawan k10 ON bam.penyetujui1 = k10.nama
LEFT JOIN data_karyawan k11 ON bam.penyetujui2 = k11.nama
";

$queryMutasi = $baseQueryMutasi . "
    $whereMutasi
    ORDER BY 
    " . (!empty($isApproverDeleteMutasi) ? "$isApproverDeleteMutasi DESC," : "") . "
    " . (!empty($isApproverEditMutasi) ? "$isApproverEditMutasi DESC," : "") . "
    bam.tanggal DESC, bam.nomor_ba DESC
";
$resultMutasi = $koneksi->query($queryMutasi);

$whereUserMutasi = $whereMutasi
    . (!empty($whereMutasi) ? " AND " : " WHERE ") . "
    (
        bam.pengirim1 = '" . $koneksi->real_escape_string($namaUser) . "'
        OR bam.pengirim2 = '" . $koneksi->real_escape_string($namaUser) . "'
        OR bam.hrd_ga_pengirim = '" . $koneksi->real_escape_string($namaUser) . "'
        OR bam.penerima1 = '" . $koneksi->real_escape_string($namaUser) . "'
        OR bam.penerima2 = '" . $koneksi->real_escape_string($namaUser) . "'
        OR bam.hrd_ga_penerima = '" . $koneksi->real_escape_string($namaUser) . "'
        OR bam.diketahui = '" . $koneksi->real_escape_string($namaUser) . "'
        OR bam.pemeriksa1 = '" . $koneksi->real_escape_string($namaUser) . "'
        OR bam.pemeriksa2 = '" . $koneksi->real_escape_string($namaUser) . "'
        OR bam.penyetujui1 = '" . $koneksi->real_escape_string($namaUser) . "'
        OR bam.penyetujui2 = '" . $koneksi->real_escape_string($namaUser) . "'
    )";
$queryUserMutasi = $baseQueryMutasi . "
    $whereUserMutasi
    ORDER BY 
    " . (!empty($isApproverDeleteMutasi) ? "$isApproverDeleteMutasi DESC," : "") . " 
    " . (!empty($isApproverEditMutasi) ? "$isApproverEditMutasi DESC," : "") . "
    bam.tanggal DESC, bam.nomor_ba DESC
";
$resultUserMutasi = $koneksi->query($queryUserMutasi);

//--------------------------------------------------------------------------------------------------

// Asal (pengirim, diketahui1, diketahui2)
$STAKaryawanTable = "data_karyawan";
$STAJabatan = "jabatan";
$STADepartemen = "departemen";

// Karena setiap BA Mutasi bisa beda PT, cek dulu salah satu record untuk tahu asal dan tujuan
$cekPT = $koneksi->query("SELECT pt FROM ba_serah_terima_asset LIMIT 1");
if ($cekPT && $cekPT->num_rows > 0) {
    $rowPT = $cekPT->fetch_assoc();

    if ($rowPT['pt'] !== "PT.MSAL (HO)") {
        $STAKaryawanTable = "data_karyawan_test";
        $STAJabatan = "posisi";
        $STADepartemen = "''"; // tidak ada kolom departemen
    }
}

$isApproverDeleteSTAsset = '';

// cek apakah user adalah approver hapus
$cekApproverDelete = $koneksi->query("
    SELECT pending_hapus_approver
    FROM ba_serah_terima_asset
    WHERE pending_hapus = 1
        AND pending_hapus_approver = '" . $koneksi->real_escape_string($_SESSION['nama']) . "'
    LIMIT 1
");

if ($cekApproverDelete && $cekApproverDelete->num_rows > 0) {
    $isApproverDeleteSTAsset = 'basta.pending_hapus';
}

$isApproverEditSTAsset = '';

$isApproverEditSTAsset = "
CASE 
    WHEN EXISTS (
        SELECT 1
        FROM history_n_temp_ba_serah_terima_asset h
        WHERE h.id_ba = basta.id
          AND h.status = 1
          AND h.pending_approver = '" . $koneksi->real_escape_string($_SESSION['nama']) . "'
    ) THEN 1
    ELSE 0
END
";


// Ambil data Berita Acara Serah Terima Peminjaman Asset Inventaris
$baseQuerySTAsset = "
    SELECT 
        basta.id, 
        basta.tanggal, 
        basta.nomor_ba, 
        basta.pt,
        basta.approval_1, 
        basta.approval_2,
        basta.approval_3,
        basta.approval_4,
        basta.autograph_1,
        basta.autograph_2,
        basta.autograph_3,
        basta.autograph_4,
        basta.peminjam,
        basta.saksi,
        basta.diketahui,
        basta.pihak_pertama,
        basta.pending_hapus,
        basta.alasan_hapus,
        basta.pending_hapus_approver,
        k1.$STAJabatan AS jabatan_aprv1,
        " . ($STADepartemen !== "''" ? "k1.$STADepartemen" : "''") . " AS departemen_aprv1,
        k2.$STAJabatan AS jabatan_aprv2,
        " . ($STADepartemen !== "''" ? "k2.$STADepartemen" : "''") . " AS departemen_aprv2,
        k3.$STAJabatan AS jabatan_aprv3,
        " . ($STADepartemen !== "''" ? "k3.$STADepartemen" : "''") . " AS departemen_aprv3,
        k4.jabatan AS jabatan_aprv4,
        k4.departemen AS departemen_aprv4
    FROM ba_serah_terima_asset basta
    LEFT JOIN $STAKaryawanTable k1 
        ON basta.peminjam = k1.nama
    LEFT JOIN $STAKaryawanTable k2 
        ON basta.saksi = k2.nama
    LEFT JOIN $STAKaryawanTable k3 
        ON basta.diketahui = k3.nama
    LEFT JOIN data_karyawan k4
        ON basta.atasan_peminjam = k4.nama
    
";
$querySTAsset = $baseQuerySTAsset . "
    $whereSTAsset
    ORDER BY 
    " . (!empty($isApproverDeleteSTAsset) ? "$isApproverDeleteSTAsset DESC," : "") . "
    " . (!empty($isApproverEditSTAsset) ? "$isApproverEditSTAsset DESC," : "") . "
    basta.tanggal DESC, basta.nomor_ba DESC 
";
$resultSTAsset = $koneksi->query($querySTAsset);

$whereUserSTAsset = $whereSTAsset
    . (!empty($whereSTAsset) ? " AND " : " WHERE ") . "
        (
            basta.peminjam = '" . $koneksi->real_escape_string($namaUser) . "'
            OR basta.saksi = '" . $koneksi->real_escape_string($namaUser) . "'
            OR basta.diketahui = '" . $koneksi->real_escape_string($namaUser) . "'
            OR basta.pihak_pertama = '" . $koneksi->real_escape_string($namaUser) . "'
        )";
$queryUserSTAsset = $baseQuerySTAsset . "
    $whereUserSTAsset
    ORDER BY 
    " . (!empty($isApproverDeleteSTAsset) ? "$isApproverDeleteSTAsset DESC," : "") . " 
    " . (!empty($isApproverEditSTAsset) ? "$isApproverEditSTAsset DESC," : "") . "
    basta.tanggal DESC, basta.nomor_ba DESC
";
$resultUserSTAsset = $koneksi->query($queryUserSTAsset);
// $resultSTAsset = $koneksi->query($querySTAsset);

// if (!$resultSTAsset) {
//     echo '<pre style="background:#300;color:#fff;padding:15px;">';
//     echo "QUERY ERROR:\n";
//     echo $koneksi->error;
//     echo "\n\nSQL:\n";
//     echo $querySTAsset;
//     echo '</pre>';
// }

// echo '<pre style="background:#111;color:#0ff;padding:15px;font-size:13px;">';
// echo "NUM ROWS: " . $resultSTAsset->num_rows . "\n\n";

// while ($row = $resultSTAsset->fetch_assoc()) {
//     print_r($row);
// }
// echo '</pre>';

//--------------------------------------------------------------------------------------------------
// BA Pemutihan
//--------------------------------------------------------------------------------------------------

$isApproverDeletePemutihan = '';

// cek apakah user adalah approver hapus
$cekApproverDeletePemutihan = $koneksi->query("
    SELECT pending_hapus_approver
    FROM berita_acara_pemutihan
    WHERE pending_hapus = 1
      AND pending_hapus_approver = '" . $koneksi->real_escape_string($_SESSION['nama']) . "'
    LIMIT 1
");

if ($cekApproverDeletePemutihan && $cekApproverDeletePemutihan->num_rows > 0) {
    $isApproverDeletePemutihan = 'bapem.pending_hapus';
}

$isApproverEditPemutihan = "
CASE 
    WHEN EXISTS (
        SELECT 1
        FROM history_n_temp_ba_pemutihan h
        WHERE h.id_ba = bapem.id
            AND h.status = 1
            AND h.pending_approver = '" . $koneksi->real_escape_string($_SESSION['nama']) . "'
    ) THEN 1
    ELSE 0
END
";

$baseQueryPemutihan = "
    SELECT
        bapem.id,
        bapem.tanggal,
        bapem.nomor_ba,
        bapem.pt,

        bapem.approval_1,
        bapem.approval_2,
        bapem.approval_3,
        bapem.approval_4,
        bapem.approval_5,
        bapem.approval_6,
        bapem.approval_7,
        bapem.approval_8,
        bapem.approval_9,
        bapem.approval_10,
        bapem.approval_11,

        bapem.autograph_1,
        bapem.autograph_2,
        bapem.autograph_3,
        bapem.autograph_4,
        bapem.autograph_5,
        bapem.autograph_6,
        bapem.autograph_7,
        bapem.autograph_8,
        bapem.autograph_9,
        bapem.autograph_10,
        bapem.autograph_11,

        bapem.pembuat,
        bapem.pemeriksa,
        bapem.diketahui1,
        bapem.diketahui2,
        bapem.diketahui3,
        bapem.dibukukan,
        bapem.disetujui1,
        bapem.disetujui2,
        bapem.disetujui3,

        bapem.pembuat_site,
        bapem.pemeriksa_site,
        bapem.diketahui1_site,
        bapem.disetujui1_site,
        bapem.diketahui2_site,
        bapem.diperiksa_site,
        bapem.mengetahui_site,

        bapem.pending_hapus,
        bapem.pending_hapus_approver,
        bapem.alasan_hapus
    FROM berita_acara_pemutihan bapem
";

$queryPemutihan = $baseQueryPemutihan . "
    $wherePemutihan
    ORDER BY
    " . (!empty($isApproverDeletePemutihan) ? "$isApproverDeletePemutihan DESC," : "") . "
    " . (!empty($isApproverEditPemutihan) ? "$isApproverEditPemutihan DESC," : "") . "
    bapem.tanggal DESC, bapem.nomor_ba DESC
";
$resultPemutihan = $koneksi->query($queryPemutihan);

$whereUserPemutihan = $wherePemutihan
    . (!empty($wherePemutihan) ? " AND " : " WHERE ") . "
    (
        bapem.pembuat = '" . $koneksi->real_escape_string($namaUser) . "'
        OR bapem.pemeriksa = '" . $koneksi->real_escape_string($namaUser) . "'
        OR bapem.diketahui1 = '" . $koneksi->real_escape_string($namaUser) . "'
        OR bapem.diketahui2 = '" . $koneksi->real_escape_string($namaUser) . "'
        OR bapem.diketahui3 = '" . $koneksi->real_escape_string($namaUser) . "'
        OR bapem.dibukukan = '" . $koneksi->real_escape_string($namaUser) . "'
        OR bapem.disetujui1 = '" . $koneksi->real_escape_string($namaUser) . "'
        OR bapem.disetujui2 = '" . $koneksi->real_escape_string($namaUser) . "'
        OR bapem.disetujui3 = '" . $koneksi->real_escape_string($namaUser) . "'

        OR bapem.pembuat_site = '" . $koneksi->real_escape_string($namaUser) . "'
        OR bapem.pemeriksa_site = '" . $koneksi->real_escape_string($namaUser) . "'
        OR bapem.diketahui1_site = '" . $koneksi->real_escape_string($namaUser) . "'
        OR bapem.disetujui1_site = '" . $koneksi->real_escape_string($namaUser) . "'
        OR bapem.diketahui2_site = '" . $koneksi->real_escape_string($namaUser) . "'
        OR bapem.diperiksa_site = '" . $koneksi->real_escape_string($namaUser) . "'
        OR bapem.mengetahui_site = '" . $koneksi->real_escape_string($namaUser) . "'
    )
";

$queryUserPemutihan = $baseQueryPemutihan . "
    $whereUserPemutihan
    ORDER BY
    " . (!empty($isApproverDeletePemutihan) ? "$isApproverDeletePemutihan DESC," : "") . "
    " . (!empty($isApproverEditPemutihan) ? "$isApproverEditPemutihan DESC," : "") . "
    bapem.tanggal DESC, bapem.nomor_ba DESC
";
$resultUserPemutihan = $koneksi->query($queryUserPemutihan);


// Helper badge status
function statusBadge($approval, $row, $colApproval, $colAutograph, $nama, $peran, $jenisBA, $pictAutograph, $ptOtoritas)
{

    global $koneksi;
    $query = $koneksi->prepare("SELECT autograph FROM akun_akses WHERE nama = ?");
    $query->bind_param("s", $nama);
    $query->execute();
    $result = $query->get_result();
    $autographUser = ($result && $result->num_rows > 0) ? $result->fetch_assoc()['autograph'] : '';

    $setKosong = isset($nama) ? trim($nama) : '';
    if ($setKosong === '' || $setKosong === '-') {
        return '-';
    }
    if ($approval == 1) {
        return "<i class='bi bi-check-square-fill text-success fs-6'></i>";
    } elseif ($approval == 2) {
        return "<i class='bi bi-x-square-fill text-danger fs-6'></i>";
    } elseif ($approval == 0) {
        if ($jenisBA === 'kerusakan' || $jenisBA === 'pemutihan') {
            if (
                isset($ptOtoritas) && isset($_SESSION['pt']) && is_array($_SESSION['pt']) && in_array($ptOtoritas, $_SESSION['pt'], true)
            ) {
                return "
            <i class='bi bi-hourglass text-warning fs-6'></i>
            " . (((isset($_SESSION['nama']) && $_SESSION['nama'] === $nama)
                    || (isset($_SESSION['hak_akses']) && $_SESSION['hak_akses'] === 'Admin'))
                    ? "
            <div 
                class='tombolAutographPopup custom-btn-action btn btn-warning btn-sm' 
                style='float: right;' 
                data-jenis='{$jenisBA}'
                data-id='{$row['id']}'
                data-tanggal='{$row['tanggal']}'
                data-nomor='{$row['nomor_ba']}'
                data-approval-col='{$colApproval}'
                data-autograph-col='{$colAutograph}'
                data-nama='{$nama}'
                data-peran='{$peran}'
                data-picture='{$pictAutograph}'
                data-user-picture='" . base64_encode($autographUser) . "'
            >
                <i class='bi bi-pencil-square'></i>
            </div>
            " : "") . "
            " .
                    "";
            } else {
                return "
                <i class='bi bi-hourglass text-warning fs-6'></i>";
            }
        } else {
            return "
            <i class='bi bi-hourglass text-warning fs-6'></i>
            " . (((isset($_SESSION['nama']) && $_SESSION['nama'] === $nama)
                || (isset($_SESSION['hak_akses']) && $_SESSION['hak_akses'] === 'Admin'))
                ? "
            <div 
                class='tombolAutographPopup custom-btn-action btn btn-warning btn-sm' 
                style='float: right;' 
                data-jenis='{$jenisBA}'
                data-id='{$row['id']}'
                data-tanggal='{$row['tanggal']}'
                data-nomor='{$row['nomor_ba']}'
                data-approval-col='{$colApproval}'
                data-autograph-col='{$colAutograph}'
                data-nama='{$nama}'
                data-peran='{$peran}'
                data-picture='{$pictAutograph}'
                data-user-picture='" . base64_encode($autographUser) . "'
            >
                <i class='bi bi-pencil-square'></i>
            </div>
            " : "") . "
            " .
                "";
        }
    } else {
        return "<i class='bi bi-question-circle text-info fs-6'></i>";
    }
}

function statusIconOnly($approval)
{
    $approval = (int)$approval;

    if ($approval === 1) {
        return "<i class='bi bi-check-square-fill text-success fs-6'></i>";
    } elseif ($approval === 2) {
        return "<i class='bi bi-x-square-fill text-danger fs-6'></i>";
    } elseif ($approval === 0) {
        return "<i class='bi bi-hourglass text-warning fs-6'></i>";
    }

    return "<i class='bi bi-question-circle text-info fs-6'></i>";
}

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Approve BA</title>

    <!-- Bootstrap 5 -->
    <link
        rel="stylesheet"
        href="../assets/bootstrap-5.3.6-dist/css/bootstrap.min.css" />

    <!-- Bootstrap Icons -->
    <link
        rel="stylesheet"
        href="../assets/icons/icons-main/font/bootstrap-icons.min.css" />

    <!-- AdminLTE -->
    <link
        rel="stylesheet"
        href="../assets/adminlte/css/adminlte.css" />

    <!-- OverlayScrollbars -->
    <link
        rel="stylesheet"
        href="../assets/css/overlayscrollbars.min.css" />

    <!-- Favicon -->
    <link
        rel="icon" type="image/png"
        href="../assets/img/logo.png" />

    <link
        rel="icon" type="image/png"
        href="../assets/css/datatables.min.css" />

    <link
        rel="stylesheet"
        href="../assets/css/datatables.min.css" />

    <style>
        /* Main Styles */

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f9f9f9;
        }

        .app-wrapper {
            position: relative;
        }

        .custom-main {
            overflow-y: hidden !important;
        }

        .button-navigation-bar {
            background-color: transparent;
            color: white;
            border-radius: 5px;
            border: #f9f9f9 1px solid;
            padding: 8px 12px;
            text-decoration: none;
        }

        .button-navigation-bar:hover {
            background-color: green;
            color: white;
            border: #f9f9f9 1px solid;
        }

        #date {
            margin-right: 10px;
        }

        #clock {
            font-size: 16px;
            color: white;
            margin-right: 20px;
        }

        /* .personalia-menu{
        background:linear-gradient(135deg,#515bd4,#dd2a7b,#F58529);
        transition: all .3s ease;
    } */

        .akun-info {
            right: -300px;
            opacity: 0;
        }

        .aktif {
            right: 0;
            opacity: 1;
            transition: all .3s ease-in-out;
        }

        .display-state {
            display: none;
        }

        .app-sidebar {
            background: <?php echo $bgMenu; ?> !important;
        }

        .navbar {
            background: <?php echo $bgNav; ?> !important;
        }

        h2,
        h3 {
            color: #2c3e50;
            text-align: center;
            margin-bottom: 25px;
        }

        .app-main {
            display: flex;
            align-items: center;
            margin-top: 40px;
        }

        /* style table */

        .table-wrapper {
            width: 97%;
            height: auto;
            overflow-x: auto;
            margin: 20px 0;
            border-radius: 10px;
            padding: 10px;
        }

        th,
        td,
        table tbody tr td .btn-sm {
            font-size: .9rem;
        }

        th,
        td {
            text-align: center !important;
        }

        .highlight-row td {
            background-color: rgba(255, 234, 0, 0.5) !important;
        }

        .highlight-row-d td {
            background-color: rgba(255, 72, 0, 0.5) !important;
        }

        /* td:first-child { width: 5%; text-align: center; } 
    td:nth-child(2) { width: 10%; }  
    td:nth-child(3) { width: 10%; }  
    td:nth-child(4) { width: 20%; }  
    td:nth-child(5) { width: 14%; }  
    td:nth-child(6) { width: 14%; }  
    td:nth-child(7) { width: 14%; }  
    td:last-child { width: 13%; }   */



        .popup-box {
            display: none;
        }

        .popup-bg {
            display: none;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 5;
        }

        .aktifPopup {
            display: flex;
        }

        .custom-popup-autograph {
            height: max-content;
            align-self: center;
            z-index: 999;
            width: max-content;
            min-width: 500px;
            left: 35.5%;
            top: 30vh;
        }


        .custom-footer {
            background-color: white;
        }

        .bi-list,
        .bi-arrows-fullscreen,
        .bi-fullscreen-exit {
            color: #fff !important;
        }
    </style>

    <style>
        /* Placeholder Skeleton */
        .skeleton {
            height: 16px;
            width: 100%;
            background: linear-gradient(90deg,
                    #e0e0e0 25%,
                    #f5f5f5 37%,
                    #e0e0e0 63%);
            background-size: 400% 100%;
            animation: skeleton-loading 1.4s ease infinite;
            border-radius: 4px;
        }

        .skeleton-header {
            height: 20px;
        }

        @keyframes skeleton-loading {
            0% {
                background-position: 100% 0;
            }

            100% {
                background-position: -100% 0;
            }
        }
    </style>

    <style>
        /* Responsive */

        @media (max-width: 1670px) {
            .btn {
                margin-bottom: 5px;
            }
        }

        @media (max-width: 1440px) {
            .custom-font-form {
                font-size: 12px;
            }

        }

        @media (max-width: 1024px) {
            #res-fullscreen {
                display: none;
            }

            .custom-footer {
                position: absolute !important;
                bottom: 0;
                width: 100vw;
            }

            .custom-main {
                padding-bottom: 100px;
                height: max-content;
                padding-top: 10px;
            }

            .custom-btn-action {
                padding: 6px 12px !important;
                font-size: 1rem !important;
            }

            .custom-popup,
            .custom-popup-autograph,
            .custom-popup-approve-pending-edit,
            .custom-popup-tolak-pending-edit {
                top: 35vh !important;
                left: 30vw !important;
                position: fixed !important;
            }

            .custom-popup2 {
                top: 35vh !important;
                left: 30vw !important;
                position: fixed !important;
            }

            .custom-popup3 {
                top: 35vh !important;
                left: 30vw !important;
                position: fixed !important;
            }

            .dt-orderable-none {
                max-width: 80px;
            }

            /* Font */
            .custom-font {
                font-size: small;
            }
        }

        @media (max-width: 450px) {

            #date,
            #clock {
                display: none;
            }

            .custom-main {
                width: 100%;
            }

            /* Filter Utama */
            /* =================================== */
            .custom-form-select-pt,
            .custom-form-select-jba {
                width: 100%;
            }

            .custom-form-select-t,
            .custom-form-select-b {
                width: 48%;
            }

            /* =================================== */

            /* Custom Popup */
            /* =================================== */
            .custom-popup-autograph,
            .custom-popup3,
            .custom-popup-approve-pending-edit,
            .custom-popup-tolak-pending-edit {
                left: 0 !important;
                width: 100vw !important;
                min-width: 0 !important;
            }

            .custom-popup-autograph canvas {
                width: 100% !important;
            }

            .autograph-container {
                padding: 3px !important;
            }

            #tombolClosePopupEmail {
                height: max-content !important;
            }

            /* =================================== */
            .custom-footer p {
                font-size: 10px;
            }
        }
    </style>

    <style>
        /*animista.net*/
        .scale-in-center {
            animation: scale-in-center .3s cubic-bezier(0.250, 0.460, 0.450, 0.940) both;
        }

        @keyframes scale-in-center {
            0% {
                transform: scale(0);
                opacity: 1;
            }

            100% {
                transform: scale(1);
                opacity: 1;
            }
        }

        .fade-in {
            animation: fade-in .3s cubic-bezier(0.390, 0.575, 0.565, 1.000) both;
        }

        @keyframes fade-in {
            0% {
                opacity: 0;
            }

            100% {
                opacity: 1;
            }
        }

        .scale-out-center {
            animation: scale-out-center .3s cubic-bezier(0.550, 0.085, 0.680, 0.530) both;
        }

        @keyframes scale-out-center {
            0% {
                transform: scale(1);
                opacity: 1;
            }

            100% {
                transform: scale(0);
                opacity: 1;
            }
        }

        .fade-out {
            animation: fade-out .3s ease-out both;
        }

        @keyframes fade-out {
            0% {
                opacity: 1;
            }

            100% {
                opacity: 0;
            }
        }

        .slide-in-right {
            animation: slide-in-right 0.5s cubic-bezier(0.250, 0.460, 0.450, 0.940) both;
        }

        @keyframes slide-in-right {
            0% {
                transform: translateX(1000px);
                opacity: 0;
            }

            100% {
                transform: translateX(0);
                opacity: 1;
            }
        }

        .slide-out-right {
            animation: slide-out-right 0.5s cubic-bezier(0.550, 0.085, 0.680, 0.530) both;
        }

        @keyframes slide-out-right {
            0% {
                transform: translateX(0);
                opacity: 1;
            }

            100% {
                transform: translateX(1000px);
                opacity: 0;
            }
        }
    </style>

    <style>
        /* scroll styling */
        .scroll-container {
            height: 100vh;
            /* tinggi penuh layar */
            overflow-y: scroll;
            /* scroll tetap aktif */
            -ms-overflow-style: none;
            /* IE dan Edge */
            scrollbar-width: none;
            /* Firefox */
        }

        .scroll-container::-webkit-scrollbar {
            display: none;
            /* Chrome, Safari, Opera */
        }
    </style>


</head>

<body class="layout-fixed sidebar-expand-lg bg-body-tertiary overflow-x-hidden">

    <script src="../assets/js/jquery-3.7.1.min.js"></script>
    <script src="../assets/js/datatables.min.js"></script>

    <div class="app-wrapper">

        <nav class="app-header navbar navbar-expand bg-body sticky-top" style="z-index: 10;"> <!-- Header -->
            <!--begin::Container-->
            <div class="container-fluid">
                <!--begin::Start Navbar Links-->
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button">
                            <i class="bi bi-list"></i>
                        </a>
                    </li>
                </ul>
                <!--end::Start Navbar Links-->
                <!--begin::End Navbar Links-->
                <ul class="navbar-nav ms-auto">
                    <!--begin::Fullscreen Toggle-->
                    <li class="nav-item">
                        <a id="res-fullscreen" class="nav-link" href="#" data-lte-toggle="fullscreen">
                            <i data-lte-icon="maximize" class="bi bi-arrows-fullscreen"></i>
                            <i data-lte-icon="minimize" class="bi bi-fullscreen-exit" style="display: none"></i>
                        </a>
                    </li>
                    <!--end::Fullscreen Toggle-->
                    <!--begin::Clock-->
                    <li class="nav-item pt-2">
                        <span id="date" class="text-white fw-bold" style="min-width: 120px; text-align: right;"></span>
                        <span id="clock" class="text-white fw-bold" style="min-width: 75px; text-align: right;"></span>
                    </li>
                    <!--end::Clock-->

                    <li class="personalia-menu nav-item me-3 rounded">
                        <i id="personaliaBtn" class="bi bi-brush-fill btn fw-bold text-white" style="box-shadow:none;"></i>
                    </li>

                    <div class="ms-auto me-2 position-relative">
                        <i id="tombolAkun" class="bi bi-person-circle btn fw-bold text-white border border-white"></i>
                        <div id="akunInfo" class="akun-info card position-absolute bg-white p-2 display-state" style="width:300px;height:160px;top:50px;right:0;transition:all .2s ease-in-out">
                            <div class=" d-flex p-3 align-items-center justify-content-around border-bottom">
                                <i class="bi bi-person-circle text-primary" style="font-size:44px"></i>
                                <div class="">
                                    <h6><?= htmlspecialchars($_SESSION['nama']) ?></h6>
                                    <h6 class="" style="color:gray"><?= htmlspecialchars($_SESSION['hak_akses']) ?></h6>
                                </div>
                            </div>
                            <a href="../logout.php" class="btn btn-outline-danger fw-bold d-flex ps-3 gap-2 mt-2 d-flex" onclick="return confirm('Yakin ingin logout?')" title="Logout">
                                <i class="bi bi-box-arrow-right fw-bolder"></i>
                                <p class="m-0">Logout</p>
                            </a>
                        </div>
                </ul>
                <!--end::End Navbar Links-->
            </div>
            <!--end::Container-->
        </nav>

        <aside class="app-sidebar shadow" data-bs-theme="dark"> <!-- Sidebar -->
            <div class="sidebar-brand" style="border:none;">
                <a href="
            <?php if ($_SESSION['hak_akses'] === 'Admin'): ?>../index.php
            <?php elseif ($_SESSION['hak_akses'] === 'User'): ?>#
            <?php endif; ?>
            " class="brand-link">
                    <img
                        src="../assets/img/logo.png"
                        alt="MSAL Logo"
                        class="brand-image opacity-75 shadow" />
                    <span class="brand-text fw-bold">SIBARA</span>
                </a>
            </div>
            <div class="sidebar-wrapper">
                <nav class="mt-2">

                    <ul
                        class="nav sidebar-menu flex-column"
                        data-lte-toggle="treeview"
                        role="menu"
                        data-accordion="false">
                        <?php if ($_SESSION['hak_akses'] === 'Admin'): ?>
                            <li class="nav-item">
                                <a href="../index.php" class="nav-link">
                                    <i class="bi bi-house-fill"></i>
                                    <p>
                                        Dashboard
                                    </p>
                                </a>
                            </li>

                            <li class="nav-header">
                                LIST BERITA ACARA
                            </li>
                            <!-- List BA Kerusakan -->
                            <li class="nav-item">
                                <a href="../ba_kerusakan-fix/ba_kerusakan.php" class="nav-link" aria-disabled="true">
                                    <i class="nav-icon bi bi-newspaper "></i>
                                    <p>
                                        BA Kerusakan
                                    </p>
                                </a>
                            </li>

                            <?php if (in_array("PT.MSAL (HO)", $userPTs, true)) { ?>
                                <!-- List BA Pengembalian -->
                                <!-- <li class="nav-item">
                                <a href="../ba_pengembalian/ba_pengembalian.php" class="nav-link">
                                    <i class="nav-icon bi bi-newspaper"></i>
                                    <p>
                                        BA Pengembalian
                                    </p>
                                </a>
                            </li> -->
                            <?php } ?>

                            <li class="nav-item">
                                <a href="../ba_pemutihan/ba_pemutihan.php" class="nav-link" aria-disabled="true">
                                    <i class="nav-icon bi bi-newspaper"></i>
                                    <p>BA Pemutihan</p>
                                </a>
                            </li>

                            <li class="nav-item">
                                <a href="../ba_pengembalian/ba_pengembalian.php" class="nav-link" aria-disabled="true">
                                    <i class="nav-icon bi bi-newspaper"></i>
                                    <p>BA Pengembalian</p>
                                </a>
                            </li>

                            <?php if (in_array("PT.MSAL (HO)", $userPTs, true)) { ?>

                                <li class="nav-item">
                                    <a href="../ba_serah-terima-asset/ba_serah-terima-asset.php" class="nav-link">
                                        <i class="nav-icon bi bi-newspaper"></i>
                                        <p>
                                            BA Serah Terima Asset Inventaris
                                        </p>
                                    </a>
                                </li>
                            <?php } ?>

                            <li class="nav-item">
                                <a href="../ba_mutasi/ba_mutasi.php" class="nav-link">
                                    <i class="nav-icon bi bi-newspaper"></i>
                                    <p>
                                        BA Mutasi
                                    </p>
                                </a>
                            </li>

                        <?php endif; ?>
                        <li class="nav-header">
                            USER
                        </li>

                        <li class="nav-item">
                            <a href="#" class="nav-link">
                                <i class="nav-icon bi bi-clipboard2-check text-white"></i>
                                <p class="text-white">
                                    Approve BA
                                </p>
                            </a>
                        </li>
                        <!-- <li class="nav-item">
                <a href="riwayat.php" class="nav-link">
                <i class="nav-icon bi bi-clipboard2-data"></i>
                <p>
                    Riwayat Approval
                </p>
                </a>
            </li> -->
                        <?php if ($showDataAkunMenu): ?>
                            <li class="nav-header">
                                MASTER
                            </li>
                            <li class="nav-item">
                                <a href="../master/data_akun/tabel.php" class="nav-link">
                                    <i class="nav-icon bi bi-person-circle"></i>
                                    <p>
                                        Data Akun
                                    </p>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>



                </nav>
            </div>
        </aside>

        <main class="custom-main app-main"><!-- Main Content -->
            <section class="table-wrapper bg-white position-relative overflow-visible">
                <?php if (isset($_SESSION['message'])): ?>
                    <?php if (!empty($_SESSION['success'])): ?>
                        <div class="w-100 d-flex justify-content-center position-absolute" style="height: max-content;">
                            <div class="d-flex p-0 alert alert-success border-0 text-center fw-bold mb-0 position-absolute fade-in infoin-approval" style="transition: opacity 0.5s ease;right:20px;width:max-content;height:max-content;">
                                <div class="d-flex justify-content-center align-items-center bg-success pe-2 ps-2 rounded-start text-white fw-bolder">
                                    <i class="bi bi-check-lg"></i>
                                </div>
                                <p class="p-2 m-0" style="font-weight: 500;"><?= htmlspecialchars($_SESSION['message']); ?></p>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="w-100 d-flex justify-content-center position-absolute" style="height: max-content;">
                            <div class="d-flex p-0 alert alert-danger border-0 text-center fw-bold mb-0 position-absolute fade-in infoin-approval" style="transition: opacity 0.5s ease;right:20px;width:max-content;height:max-content;">
                                <div class="d-flex justify-content-center align-items-center bg-danger pe-2 ps-2 rounded-start text-white fw-bolder">
                                    <i class="bi bi-x-lg"></i>
                                </div>
                                <p class="p-2 m-0" style="font-weight: 500;"><?= htmlspecialchars($_SESSION['message']); ?></p>
                            </div>
                        </div>
                    <?php endif; ?>
                    <?php unset($_SESSION['message'], $_SESSION['success']); ?>
                <?php endif; ?>

                <h2>Daftar Approval BA
                    <?php
                    if ($_SESSION['hak_akses'] === 'User') {
                        echo "Anda";
                    }
                    ?>
                </h2>

                <?php

                if ($_SESSION['hak_akses'] === 'Admin') {
                    if ($filterJenisBA === 'kerusakan') {
                        $resultAkses        = $resultKerusakan;
                        $isKerusakan        = true;
                        $isPengembalian     = false;
                        $isNotebook         = false;
                        $isMutasi           = false;
                        $isSTAsset          = false;
                    } elseif ($filterJenisBA === 'pengembalian') {
                        $resultAkses        = $resultPengembalian;
                        $isKerusakan        = false;
                        $isPengembalian     = true;
                        $isNotebook         = false;
                        $isMutasi           = false;
                        $isSTAsset          = false;
                    } elseif ($filterJenisBA === 'notebook') {
                        $resultAkses        = $resultNotebook;
                        $isKerusakan        = false;
                        $isPengembalian     = false;
                        $isNotebook         = true;
                        $isMutasi           = false;
                        $isSTAsset          = false;
                    } elseif ($filterJenisBA === 'mutasi') {
                        $resultAkses        = $resultMutasi;
                        $isKerusakan        = false;
                        $isPengembalian     = false;
                        $isNotebook         = false;
                        $isMutasi           = true;
                        $isSTAsset          = false;
                    } elseif ($filterJenisBA === 'st_asset') {
                        $resultAkses        = $resultSTAsset;
                        $isKerusakan        = false;
                        $isPengembalian     = false;
                        $isNotebook         = false;
                        $isMutasi           = false;
                        $isSTAsset          = true;
                    } elseif ($filterJenisBA === 'pemutihan') {
                        $resultAkses        = $resultPemutihan;
                        $isKerusakan        = false;
                        $isPengembalian     = false;
                        $isNotebook         = false;
                        $isMutasi           = false;
                        $isSTAsset          = false;
                        $isPemutihan        = true;
                    }
                } else {
                    if ($filterJenisBA === 'kerusakan') {
                        $resultAkses        = $resultUserKerusakan;
                        $isKerusakan        = true;
                        $isPengembalian     = false;
                        $isNotebook         = false;
                        $isMutasi           = false;
                        $isSTAsset          = false;
                    } elseif ($filterJenisBA === 'pengembalian') {
                        $resultAkses        = $resultUserPengembalian;
                        $isKerusakan        = false;
                        $isPengembalian     = true;
                        $isNotebook         = false;
                        $isMutasi           = false;
                        $isSTAsset          = false;
                    } elseif ($filterJenisBA === 'notebook') {
                        $resultAkses        = $resultUserNotebook;
                        $isKerusakan        = false;
                        $isPengembalian     = false;
                        $isNotebook         = true;
                        $isMutasi           = false;
                        $isSTAsset          = false;
                    } elseif ($filterJenisBA === 'mutasi') {
                        $resultAkses        = $resultUserMutasi;
                        $isKerusakan        = false;
                        $isPengembalian     = false;
                        $isNotebook         = false;
                        $isMutasi           = true;
                        $isSTAsset          = false;
                    } elseif ($filterJenisBA === 'st_asset') {
                        $resultAkses        = $resultUserSTAsset;
                        $isKerusakan        = false;
                        $isPengembalian     = false;
                        $isNotebook         = false;
                        $isMutasi           = false;
                        $isSTAsset          = true;
                    } elseif ($filterJenisBA === 'pemutihan') {
                        $resultAkses        = $resultUserPemutihan;
                        $isKerusakan        = false;
                        $isPengembalian     = false;
                        $isNotebook         = false;
                        $isMutasi           = false;
                        $isSTAsset          = false;
                        $isPemutihan        = true;
                    }
                }
                ?>

                <!-- Filter -->
                <form method="get" class="mb-3 d-flex gap-2 flex-wrap align-items-end">
                    <!-- Filter PT -->
                    <div class="custom-form-select-pt">
                        <label class="form-label">PT</label>
                        <select name="pt" class="form-select" onchange="this.form.submit()">
                            <?php

                            // opsi ALL
                            $selectedAll = ($filterPT === 'ALL') ? 'selected' : '';
                            echo "<option value=\"ALL\" $selectedAll>Semua PT</option>";

                            // daftar PT user
                            foreach ($userPTs as $ptOpt) {
                                $selected = ($filterPT === $ptOpt) ? 'selected' : '';
                                echo "<option value=\"" . htmlspecialchars($ptOpt, ENT_QUOTES) . "\" $selected>" . htmlspecialchars($ptOpt) . "</option>";
                            }

                            ?>

                        </select>
                    </div>

                    <!-- Filter Jenis BA -->
                    <div class="custom-form-select-jba">
                        <label class="form-label">Jenis BA</label>
                        <select name="jenis_ba" class="form-select" onchange="this.form.submit()">
                            <option value="kerusakan" <?= ($filterJenisBA === 'kerusakan') ? 'selected' : '' ?>>BA Kerusakan</option>
                            <option value="mutasi" <?= ($filterJenisBA === 'mutasi') ? 'selected' : '' ?>>BA Mutasi</option>
                            <option value="pemutihan" <?= ($filterJenisBA === 'pemutihan') ? 'selected' : '' ?>>BA Pemutihan</option>
                            <option value="pengembalian" <?= ($filterJenisBA === 'pengembalian') ? 'selected' : '' ?>>BA Pengembalian</option>
                            <?php if (in_array("PT.MSAL (HO)", $userPTs, true)) { ?>
                                <!-- <option value="notebook" <?= ($filterJenisBA === 'notebook') ? 'selected' : '' ?>>BA Serah Terima Inventaris</option> -->
                                <option value="st_asset" <?= ($filterJenisBA === 'st_asset') ? 'selected' : '' ?>>BA Serah Terima Peminjaman Asset Inventaris</option>
                            <?php } ?>
                        </select>
                    </div>

                    <!-- Filter Tahun -->
                    <div class="custom-form-select-t">
                        <label class="form-label">Tahun</label>
                        <select name="tahun" class="form-select" onchange="this.form.submit()">
                            <option value="">Semua</option>
                            <?php
                            if ($filterJenisBA === 'kerusakan') {
                                $tahunQuery = "SELECT DISTINCT YEAR(tanggal) as tahun FROM berita_acara_kerusakan ORDER BY tahun DESC";
                            } elseif ($filterJenisBA === 'pengembalian') {
                                $tahunQuery = "SELECT DISTINCT YEAR(tanggal) as tahun FROM berita_acara_pengembalian_v2 ORDER BY tahun DESC";
                            } elseif ($filterJenisBA === 'notebook') {
                                $tahunQuery = "SELECT DISTINCT YEAR(tanggal) as tahun FROM ba_serah_terima_notebook ORDER BY tahun DESC";
                            } elseif ($filterJenisBA === 'mutasi') {
                                $tahunQuery = "SELECT DISTINCT YEAR(tanggal) as tahun FROM berita_acara_mutasi ORDER BY tahun DESC";
                            } elseif ($filterJenisBA === 'pemutihan') {
                                $tahunQuery = "SELECT DISTINCT YEAR(tanggal) as tahun FROM berita_acara_pemutihan ORDER BY tahun DESC";
                            } elseif ($filterJenisBA === 'st_asset') {
                                $tahunQuery = "SELECT DISTINCT YEAR(tanggal) as tahun FROM ba_serah_terima_asset ORDER BY tahun DESC";
                            }
                            $tahunRes = $koneksi->query($tahunQuery);
                            while ($t = $tahunRes->fetch_assoc()) {
                                $sel = ($filterTahun == $t['tahun']) ? 'selected' : '';
                                echo "<option value='{$t['tahun']}' $sel>{$t['tahun']}</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <!-- Filter Bulan -->
                    <div class="custom-form-select-b">
                        <label class="form-label">Bulan</label>
                        <select name="bulan" class="form-select" onchange="this.form.submit()">
                            <option value="">Semua</option>
                            <?php
                            $bulanIndo = [
                                1 => 'Januari',
                                2 => 'Februari',
                                3 => 'Maret',
                                4 => 'April',
                                5 => 'Mei',
                                6 => 'Juni',
                                7 => 'Juli',
                                8 => 'Agustus',
                                9 => 'September',
                                10 => 'Oktober',
                                11 => 'November',
                                12 => 'Desember'
                            ];
                            foreach ($bulanIndo as $num => $nama) {
                                $sel = ($filterBulan == $num) ? 'selected' : '';
                                echo "<option value='$num' $sel>$nama</option>";
                            }
                            ?>
                        </select>
                    </div>

                </form>

                <div class="" style="width: 100%;">


                    <div id="tableSkeleton">

                        <table class="table table-borderless">
                            <thead>
                                <tr>
                                    <th>
                                        <div class="skeleton skeleton-header"></div>
                                    </th>
                                    <th>
                                        <div class="skeleton skeleton-header d-none"></div>
                                    </th>
                                    <th>
                                        <div class="skeleton skeleton-header d-none"></div>
                                    </th>
                                    <th>
                                        <div class="skeleton skeleton-header d-none"></div>
                                    </th>
                                    <th>
                                        <div class="skeleton skeleton-header"></div>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php for ($i = 0; $i < 8; $i++) { ?>
                                    <tr>
                                        <td style="border: #e0e0e0 1px solid;">
                                            <div class="skeleton"></div>
                                        </td>
                                        <td style="border: #e0e0e0 1px solid;">
                                            <div class="skeleton"></div>
                                        </td>
                                        <td style="border: #e0e0e0 1px solid;">
                                            <div class="skeleton"></div>
                                        </td>
                                        <td style="border: #e0e0e0 1px solid;">
                                            <div class="skeleton"></div>
                                        </td>
                                        <td style="border: #e0e0e0 1px solid;">
                                            <div class="skeleton"></div>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>

                    <div id="tabelUtama" style="display: none;">
                        <table id="myTable" class="table table-bordered table-striped text-center" style="text-align: center !important;">
                            <thead class="bg-secondary">
                                <tr class="tabel-judul">
                                    <th rowspan="2">No</th>
                                    <th class="p-3" rowspan="2">Tanggal</th>
                                    <th class="p-3" rowspan="2">Nomor BA</th>
                                    <th class="p-3" rowspan="2">Jenis BA</th>
                                    <?php if (!$isMutasi): ?>
                                        <th class="p-3" rowspan="2">PT</th>
                                    <?php elseif ($isMutasi): ?>
                                        <th class="p-3" rowspan="2">PT Asal</th>
                                        <th class="p-3" rowspan="2">PT Tujuan</th>
                                    <?php endif; ?>
                                    <?php if ($isKerusakan): ?>
                                        <th colspan="6">Status Approval</th>
                                    <?php elseif ($isPengembalian): ?>
                                        <th colspan="3">Status Approval</th>
                                    <?php elseif ($isNotebook): ?>
                                        <th colspan="4">Status Approval</th>
                                    <?php elseif ($isMutasi): ?>
                                        <th colspan="11">Status Approval</th>
                                    <?php elseif ($isSTAsset): ?>
                                        <th colspan="4">Status Approval</th>
                                    <?php elseif ($isPemutihan): ?>
                                        <th colspan="11">Status Approval</th>
                                    <?php else: ?>
                                        <th colspan="1">Status Approval</th>
                                    <?php endif; ?>
                                    <th class="p-3" rowspan="2">Actions</th>
                                </tr>
                                <tr class="tabel-judul2">
                                    <?php if ($isKerusakan): ?>
                                        <th>Pembuat</th>
                                        <th>Pengguna</th>
                                        <th>Atasan Pengguna</th>
                                        <th>Diketahui</th>
                                        <th>Disetujui</th>
                                        <th>Diketahui</th>
                                    <?php elseif ($isPengembalian): ?>
                                        <th>Yang Menyerahkan</th>
                                        <th>Penerima</th>
                                        <th>Yang Mengetahui</th>
                                    <?php elseif ($isNotebook): ?>
                                        <th>Pihak Pertama</th>
                                        <th>Pihak Kedua</th>
                                        <th>Saksi</th>
                                        <th>Yang Mengetahui</th>
                                    <?php elseif ($isMutasi): ?>
                                        <th>Pengirim</th>
                                        <th>Pengirim 2</th>
                                        <th>HRD GA Pengirim</th>
                                        <th>Penerima 1</th>
                                        <th>Penerima 2</th>
                                        <th>HRD GA Penerima</th>
                                        <th>Diketahui</th>
                                        <th>Dept HRD GA</th>
                                        <th>Div Accounting</th>
                                        <th>Direktur HRD GA</th>
                                        <th>Direktur FA</th>
                                    <?php elseif ($isSTAsset): ?>
                                        <th>Peminjam</th>
                                        <th>Saksi</th>
                                        <th>Diketahui</th>
                                        <th>Direksi MIS</th>
                                    <?php elseif ($isPemutihan): ?>
                                        <th>Approver 1</th>
                                        <th>Approver 2</th>
                                        <th>Approver 3</th>
                                        <th>Approver 4</th>
                                        <th>Approver 5</th>
                                        <th>Approver 6</th>
                                        <th>Approver 7</th>
                                        <th>Approver 8</th>
                                        <th>Approver 9</th>
                                        <th>Approver 10</th>
                                        <th>Approver 11</th>
                                    <?php else: ?>
                                        <th>Non Aktor</th>
                                    <?php endif; ?>
                                </tr>

                            </thead>
                            <tbody>
                                <?php
                                $no = 1;
                                if ($resultAkses) {

                                    if (!isset($isKerusakan)) $isKerusakan = ($filterJenisBA === 'kerusakan');
                                    if (!isset($isPengembalian)) $isPengembalian = ($filterJenisBA === 'pengembalian');
                                    if (!isset($isNotebook)) $isNotebook = ($filterJenisBA === 'notebook');
                                    if (!isset($isMutasi)) $isMutasi = ($filterJenisBA === 'mutasi');
                                    if (!isset($isSTAsset)) $isSTAsset = ($filterJenisBA === 'st_asset');
                                    if (!isset($isPemutihan)) $isPemutihan = ($filterJenisBA === 'pemutihan');

                                    while ($row = $resultAkses->fetch_assoc()) {

                                        $sessionUser = isset($_SESSION['nama']) ? $_SESSION['nama'] : '';

                                        $tanggal    = isset($row['tanggal']) ? $row['tanggal'] : '';
                                        $nomor_ba   = isset($row['nomor_ba']) ? $row['nomor_ba'] : '';
                                        $approval_1 = isset($row['approval_1']) ? intval($row['approval_1']) : 0;
                                        $approval_2 = isset($row['approval_2']) ? intval($row['approval_2']) : 0;
                                        $approval_3 = isset($row['approval_3']) ? intval($row['approval_3']) : 0;
                                        $approval_4 = isset($row['approval_4']) ? intval($row['approval_4']) : 0;
                                        $approval_5 = isset($row['approval_5']) ? intval($row['approval_5']) : 0;
                                        $approval_6 = isset($row['approval_6']) ? intval($row['approval_6']) : 0;
                                        $approval_7 = isset($row['approval_7']) ? intval($row['approval_7']) : 0;
                                        $approval_8 = isset($row['approval_8']) ? intval($row['approval_8']) : 0;
                                        $approval_9 = isset($row['approval_9']) ? intval($row['approval_9']) : 0;
                                        $approval_10 = isset($row['approval_10']) ? intval($row['approval_10']) : 0;
                                        $approval_11 = isset($row['approval_11']) ? intval($row['approval_11']) : 0;
                                        $autograph_1 = isset($row['autograph_1']) ? $row['autograph_1'] : '';
                                        $autograph_2 = isset($row['autograph_2']) ? $row['autograph_2'] : '';
                                        $autograph_3 = isset($row['autograph_3']) ? $row['autograph_3'] : '';
                                        $autograph_4 = isset($row['autograph_4']) ? $row['autograph_4'] : '';
                                        $autograph_5 = isset($row['autograph_5']) ? $row['autograph_5'] : '';
                                        $autograph_6 = isset($row['autograph_6']) ? $row['autograph_6'] : '';
                                        $autograph_7 = isset($row['autograph_7']) ? $row['autograph_7'] : '';
                                        $autograph_8 = isset($row['autograph_8']) ? $row['autograph_8'] : '';
                                        $autograph_9 = isset($row['autograph_9']) ? $row['autograph_9'] : '';
                                        $autograph_10 = isset($row['autograph_10']) ? $row['autograph_10'] : '';
                                        $autograph_11 = isset($row['autograph_11']) ? $row['autograph_11'] : '';

                                        $pendingDelete = isset($row['pending_hapus']) ? $row['pending_hapus'] : '';
                                        $pendingDeleteAprv = isset($row['pending_hapus_approver']) ? $row['pending_hapus_approver'] : '';
                                        $pendingDeleteAlasan = isset($row['alasan_hapus']) ? $row['alasan_hapus'] : '';
                                        $ids = isset($row['id']) ? intval($row['id']) : 0;

                                        // Update 15/11/25 : Deteksi Pending Edit Data
                                        // ======================================================================

                                        $pending         = false;
                                        $pendingApprover = '';

                                        if ($isKerusakan) {
                                            // Cek di history_n_temp_ba_kerusakan, ambil hanya status = 1 (data baru)
                                            $sqlPending = "
                                                SELECT pending_approver
                                                FROM history_n_temp_ba_kerusakan
                                                WHERE id_ba = " . intval($ids) . " 
                                                AND pending_status = 1
                                                AND status = 1
                                                LIMIT 1
                                            ";
                                            $resultPending = $koneksi->query($sqlPending);
                                            if ($resultPending && $resultPending->num_rows > 0) {
                                                $rowPending       = $resultPending->fetch_assoc();
                                                $pending          = true;
                                                $pendingApprover  = isset($rowPending['pending_approver']) ? $rowPending['pending_approver'] : '';
                                            }
                                        } elseif ($isPengembalian) {
                                            $sqlPendingPengembalian = "
                                                SELECT pending_approver
                                                FROM history_n_temp_ba_pengembalian_v2
                                                WHERE id_ba = " . intval($ids) . "
                                                AND pending_status = 1
                                                AND status = 1
                                                LIMIT 1
                                            ";
                                            $resultPendingPengembalian = $koneksi->query($sqlPendingPengembalian);
                                            if ($resultPendingPengembalian && $resultPendingPengembalian->num_rows > 0) {
                                                $rowPendingPengembalian = $resultPendingPengembalian->fetch_assoc();
                                                $pending = true;
                                                $pendingApprover = isset($rowPendingPengembalian['pending_approver']) ? $rowPendingPengembalian['pending_approver'] : '';
                                            }
                                        } elseif ($isNotebook) {
                                            // disiapkan untuk history_n_temp_ba_notebook

                                        } elseif ($isMutasi) {
                                            // Cek di history_n_temp_ba_mutasi, ambil hanya status = 1 (data baru)
                                            $sqlPendingMutasi = "
                                                SELECT pending_approver
                                                FROM history_n_temp_ba_mutasi
                                                WHERE id_ba = " . intval($ids) . " 
                                                AND pending_status = 1
                                                AND status = 1
                                                LIMIT 1
                                            ";
                                            $resultPendingMutasi = $koneksi->query($sqlPendingMutasi);
                                            if ($resultPendingMutasi && $resultPendingMutasi->num_rows > 0) {
                                                $rowPendingMutasi       = $resultPendingMutasi->fetch_assoc();
                                                $pending          = true;
                                                $pendingApprover  = isset($rowPendingMutasi['pending_approver']) ? $rowPendingMutasi['pending_approver'] : '';
                                            }
                                        } elseif ($isSTAsset) {
                                            // Cek di history_n_temp_ba_serah_terima_asset, ambil hanya status = 1 (data baru)
                                            $sqlPendingSTAsset = "
                                                SELECT pending_approver
                                                FROM history_n_temp_ba_serah_terima_asset
                                                WHERE id_ba = " . intval($ids) . " 
                                                AND pending_status = 1
                                                AND status = 1
                                                LIMIT 1
                                            ";
                                            $resultPendingSTAsset = $koneksi->query($sqlPendingSTAsset);
                                            if ($resultPendingSTAsset && $resultPendingSTAsset->num_rows > 0) {
                                                $rowPendingSTAsset       = $resultPendingSTAsset->fetch_assoc();
                                                $pending          = true;
                                                $pendingApprover  = isset($rowPendingSTAsset['pending_approver']) ? $rowPendingSTAsset['pending_approver'] : '';
                                            }
                                        } elseif ($isPemutihan) {
                                            // Cek di history_n_temp_ba_pemutihan, ambil hanya status = 1 (data baru)
                                            $sqlPendingPemutihan = "
                                                SELECT pending_approver
                                                FROM history_n_temp_ba_pemutihan
                                                WHERE id_ba = " . intval($ids) . "
                                                AND pending_status = 1
                                                AND status = 1
                                                LIMIT 1
                                            ";
                                            $resultPendingPemutihan = $koneksi->query($sqlPendingPemutihan);
                                            if ($resultPendingPemutihan && $resultPendingPemutihan->num_rows > 0) {
                                                $rowPendingPemutihan = $resultPendingPemutihan->fetch_assoc();
                                                $pending = true;
                                                $pendingApprover = isset($rowPendingPemutihan['pending_approver']) ? $rowPendingPemutihan['pending_approver'] : '';
                                            }
                                        }

                                        // Set class highlight

                                        $highlightClass = $pending ? 'highlight-row' : '';

                                        if ($pendingDelete == 1) {
                                            $highlightClass = 'highlight-row-d';
                                        }
                                        // ======================================================================

                                        echo "<tr class='$highlightClass'>";
                                        if ($pendingDelete == 1) {
                                            echo "<td class='pt-3 custom-font fw-bold' style='color: #850404;'>{$no}</td>";
                                            echo "<td class='pt-3 custom-font fw-bold' style='color: #850404;'>" . (!empty($tanggal) ? date('d-m-Y', strtotime($tanggal)) : '-') . "</td>";
                                            echo "<td class='pt-3 custom-font fw-bold' style='color: #850404;'>" . htmlspecialchars($nomor_ba, ENT_QUOTES) . "</td>";
                                        } else {
                                            if ($pending) {
                                                echo "<td class='pt-3 custom-font fw-bold' style='color: #856404;'>{$no}</td>";
                                                echo "<td class='pt-3 custom-font fw-bold' style='color: #856404;'>" . (!empty($tanggal) ? date('d-m-Y', strtotime($tanggal)) : '-') . "</td>";
                                                echo "<td class='pt-3 custom-font fw-bold' style='color: #856404;'>" . htmlspecialchars($nomor_ba, ENT_QUOTES) . "</td>";
                                            } else {
                                                echo "<td class='custom-font pt-3'>{$no}</td>";
                                                echo "<td class='custom-font pt-3'>" . (!empty($tanggal) ? date('d-m-Y', strtotime($tanggal)) : '-') . "</td>";
                                                echo "<td class='custom-font pt-3'>" . htmlspecialchars($nomor_ba, ENT_QUOTES) . "</td>";
                                            }
                                        }
                                        if ($pendingDelete == 1) {
                                            if ($isKerusakan) {
                                                echo "<td class='pt-3 custom-font fw-bold' style='color: #850404;'>Berita Acara Kerusakan</td>";
                                            } elseif ($isPengembalian) {
                                                echo "<td class='pt-3 custom-font fw-bold' style='color: #850404;'>Berita Acara Pengembalian Asset Inventaris</td>";
                                            } elseif ($isNotebook) {
                                                echo "<td class='pt-3 custom-font fw-bold' style='color: #850404;'>Berita Acara Serah Terima Inventaris</td>";
                                            } elseif ($isMutasi) {
                                                echo "<td class='pt-3 custom-font fw-bold' style='color: #850404;'>Berita Acara Mutasi Asset Inventaris</td>";
                                            } elseif ($isSTAsset) {
                                                echo "<td class='pt-3 custom-font fw-bold' style='color: #850404;'>BA Serah Terima Penggunaan Asset Inventaris</td>";
                                            } elseif ($isPemutihan) {
                                                echo "<td class='pt-3 custom-font fw-bold' style='color: #850404;'>Berita Acara Pemutihan</td>";
                                            } else {
                                                echo "<td class='pt-3 custom-font fw-bold' style='color: #850404;'>Jenis BA Lain</td>";
                                            }
                                        } else {
                                            if ($pending) {
                                                if ($isKerusakan) {
                                                    echo "<td class='pt-3 custom-font fw-bold' style='color: #856404;'>Berita Acara Kerusakan</td>";
                                                } elseif ($isPengembalian) {
                                                    echo "<td class='pt-3 custom-font fw-bold' style='color: #856404;'>Berita Acara Pengembalian Asset Inventaris</td>";
                                                } elseif ($isNotebook) {
                                                    echo "<td class='pt-3 custom-font fw-bold' style='color: #856404;'>Berita Acara Serah Terima Inventaris</td>";
                                                } elseif ($isMutasi) {
                                                    echo "<td class='pt-3 custom-font fw-bold' style='color: #856404;'>Berita Acara Mutasi Asset Inventaris</td>";
                                                } elseif ($isSTAsset) {
                                                    echo "<td class='pt-3 custom-font fw-bold' style='color: #856404;'>BA Serah Terima Penggunaan Asset Inventaris</td>";
                                                } elseif ($isPemutihan) {
                                                    echo "<td class='pt-3 custom-font fw-bold' style='color: #856404;'>Berita Acara Pemutihan</td>";
                                                } else {
                                                    echo "<td class='pt-3 custom-font fw-bold' style='color: #856404;'>Jenis BA Lain</td>";
                                                }
                                            } else {
                                                if ($isKerusakan) {
                                                    echo "<td class='custom-font pt-3'>Berita Acara Kerusakan</td>";
                                                } elseif ($isPengembalian) {
                                                    echo "<td class='custom-font pt-3'>Berita Acara Pengembalian Asset Inventaris</td>";
                                                } elseif ($isNotebook) {
                                                    echo "<td class='custom-font pt-3'>Berita Acara Serah Terima Inventaris</td>";
                                                } elseif ($isMutasi) {
                                                    echo "<td class='custom-font pt-3'>Berita Acara Mutasi Asset Inventaris</td>";
                                                } elseif ($isSTAsset) {
                                                    echo "<td class='custom-font pt-3'>BA Serah Terima Penggunaan Asset Inventaris</td>";
                                                } elseif ($isPemutihan) {
                                                    echo "<td class='custom-font pt-3'>Berita Acara Pemutihan</td>";
                                                } else {
                                                    echo "<td class='custom-font pt-3'>Jenis BA Lain</td>";
                                                }
                                            }
                                        }
                                        $ptValue = '-';
                                        $pt2Value = '-';
                                        if ($isKerusakan) {
                                            $ptValue = isset($row['pt']) ? $row['pt'] : '-';
                                        } elseif ($isPemutihan) {
                                            $ptValue = isset($row['pt']) ? $row['pt'] : '-';
                                        } elseif ($isPengembalian) {
                                            $ptValue = isset($row['pt']) ? $row['pt'] : '-';
                                        } elseif ($isNotebook) {
                                            $ptValue = isset($row['pt']) ? $row['pt'] : '-';
                                        } elseif ($isMutasi) {
                                            $ptValue = isset($row['pt_asal']) ? $row['pt_asal'] : '-';
                                            $pt2Value = isset($row['pt_tujuan']) ? $row['pt_tujuan'] : '-';
                                        } elseif ($isSTAsset) {
                                            $ptValue = isset($row['pt']) ? $row['pt'] : '-';
                                        }

                                        if ($pendingDelete == 1) {
                                            if (!$isMutasi):
                                                echo "<td class='pt-3 custom-font fw-bold' style='color: #850404;'>"
                                                    . htmlspecialchars($ptValue, ENT_QUOTES) .
                                                    "</td>";
                                            elseif ($isMutasi):
                                                echo "<td class='pt-3 custom-font fw-bold' style='color: #850404;'>"
                                                    . htmlspecialchars($ptValue, ENT_QUOTES) .
                                                    "</td>";
                                                echo "<td class='pt-3 custom-font fw-bold' style='color: #850404;'>"
                                                    . htmlspecialchars($pt2Value, ENT_QUOTES) .
                                                    "</td>";
                                            endif;
                                        } else {
                                            if ($pending) {
                                                if (!$isMutasi):
                                                    echo "<td class='pt-3 custom-font fw-bold' style='color: #856404;'>"
                                                        . htmlspecialchars($ptValue, ENT_QUOTES) .
                                                        "</td>";
                                                elseif ($isMutasi):
                                                    echo "<td class='pt-3 custom-font fw-bold' style='color: #856404;'>"
                                                        . htmlspecialchars($ptValue, ENT_QUOTES) .
                                                        "</td>";
                                                    echo "<td class='pt-3 custom-font fw-bold' style='color: #856404;'>"
                                                        . htmlspecialchars($pt2Value, ENT_QUOTES) .
                                                        "</td>";
                                                endif;
                                            } else {
                                                if (!$isMutasi):
                                                    echo "<td class='custom-font pt-3'>"
                                                        . htmlspecialchars($ptValue, ENT_QUOTES) .
                                                        "</td>";
                                                elseif ($isMutasi):
                                                    echo "<td class='custom-font pt-3'>"
                                                        . htmlspecialchars($ptValue, ENT_QUOTES) .
                                                        "</td>";
                                                    echo "<td class='custom-font pt-3'>"
                                                        . htmlspecialchars($pt2Value, ENT_QUOTES) .
                                                        "</td>";
                                                endif;
                                            }
                                        }

                                        if ($pending || $pendingDelete == 1) {
                                            // Tentukan jumlah kolom Status Approval per jenis BA
                                            $totalCols = 1;
                                            if ($isKerusakan) {
                                                $totalCols = 6;
                                            } elseif ($isPemutihan) {
                                                $totalCols = 11;
                                            } elseif ($isPengembalian) {
                                                $totalCols = 3;
                                            } elseif ($isNotebook) {
                                                $totalCols = 4;
                                            } elseif ($isMutasi) {
                                                $totalCols = 11;
                                            } elseif ($isSTAsset) {
                                                $totalCols = 4;
                                            }

                                            // Cetak kolom Status Approval berisi tanda "-"
                                            for ($i = 0; $i < $totalCols; $i++) {
                                                echo "<td class='text-center custom-font pt-3'>-</td>";
                                            }

                                            // Label user yang menyetujui pending edit
                                            $sessionNama = isset($_SESSION['nama']) ? $_SESSION['nama'] : '';

                                            if ($pendingApprover != '' && $sessionNama === $pendingApprover) {
                                                $pendingApproverLabel = 'Anda';
                                            } else {
                                                $pendingApproverLabel = $pendingApprover != ''
                                                    ? htmlspecialchars($pendingApprover, ENT_QUOTES)
                                                    : '-';
                                            }

                                            $pendingApproverAktor = $pendingApprover
                                                ? htmlspecialchars(json_encode([$pendingApprover]), ENT_QUOTES, 'UTF-8')
                                                : '[]';

                                            // Label user yang menyetujui pending delete
                                            if ($pendingDeleteAprv != '' && $sessionNama === $pendingDeleteAprv) {
                                                $pendingDeleteAprvLabel = 'Anda';
                                            } else {
                                                $pendingDeleteAprvLabel = $pendingDeleteAprv != ''
                                                    ? htmlspecialchars($pendingDeleteAprv, ENT_QUOTES)
                                                    : '-';
                                            }

                                            $pendingDeleteAprvAktor = $pendingDeleteAprv
                                                ? htmlspecialchars(json_encode([$pendingDeleteAprv]), ENT_QUOTES, 'UTF-8')
                                                : '[]';

                                            // Inisialisasi wadah tombol & link
                                            $viewLink     = '';
                                            $extraButtons = '';
                                        }

                                        if ($pendingDelete == 1) {
                                            if ($isKerusakan) {
                                                $ptRow = trim(isset($row['pt']) ? $row['pt'] : '');
                                                if (in_array($ptRow, $userPTs, true)) {
                                                    $bulanRomawiArr = [
                                                        1 => 'I',
                                                        2 => 'II',
                                                        3 => 'III',
                                                        4 => 'IV',
                                                        5 => 'V',
                                                        6 => 'VI',
                                                        7 => 'VII',
                                                        8 => 'VIII',
                                                        9 => 'IX',
                                                        10 => 'X',
                                                        11 => 'XI',
                                                        12 => 'XII'
                                                    ];

                                                    $bulanRomawi = '';
                                                    $tahun = '';

                                                    if (!empty($tanggal)) {
                                                        $bulanNum = (int)date('n', strtotime($tanggal));
                                                        $tahun = date('Y', strtotime($tanggal));
                                                        $bulanRomawi = isset($bulanRomawiArr[$bulanNum]) ? $bulanRomawiArr[$bulanNum] : '';
                                                    }

                                                    // Jika session login adalah Admin → tombol kirim email
                                                    if (isset($_SESSION['nama']) && $_SESSION['nama'] !== $pendingDeleteAprv) {
                                                        if (isset($_SESSION['hak_akses']) && $_SESSION['hak_akses'] === 'Admin') {
                                                            $extraButtons .= "<a class='custom-btn-action btn btn-dark btn-sm mt-1 tombolKirimEmailPending' 
                                                                    href='#' 
                                                                    data-id='{$ids}' 
                                                                    data-aktor='{$pendingDeleteAprvAktor}'
                                                                    data-nomor='" . htmlspecialchars(isset($nomor_ba) ? $nomor_ba : '', ENT_QUOTES) . "'
                                                                    data-tanggal='" . htmlspecialchars(isset($tanggal) ? $tanggal : '', ENT_QUOTES) . "'
                                                                    data-bulan-romawi='" . htmlspecialchars($bulanRomawi, ENT_QUOTES) . "'
                                                                    data-tahun='" . htmlspecialchars($tahun, ENT_QUOTES) . "'
                                                                    data-jenis-ba='kerusakan'
                                                                    data-jenis-permintaan='delete'
                                                                    data-nama-peminta='" . htmlspecialchars($namaUser, ENT_QUOTES) . "'
                                                                    >
                                                                    <i class='bi bi-envelope-at-fill'></i>
                                                                </a>";
                                                        }
                                                    }
                                                    // Jika session nama = pending_approver → tombol approval
                                                    if (isset($_SESSION['nama']) && $pendingDeleteAprv != '' && $_SESSION['nama'] === $pendingDeleteAprv) {
                                                        $extraButtons .= "<a class='custom-btn-action btn btn-danger btn-sm mt-1 tombolApprovePendingDeletePopup' 
                                                                href='#' 
                                                                data-id='" . intval($ids) . "'
                                                                data-jenis-ba='kerusakan'
                                                                data-nama-approver='" . $pendingDeleteAprv . "'
                                                                data-alasan-delete='" . $pendingDeleteAlasan . "'
                                                                >
                                                                <i class='bi bi-trash3'></i>
                                                            </a>";
                                                    }

                                                    $extraButtons .= "  <a class='custom-btn-action btn btn-secondary mt-1 btn-sm' href='detail_barang_kerusakan.php?id=" . intval(isset($row['id']) ? $row['id'] : 0) . "'><i class='bi bi-eye-fill'></i></a> 
                                                                    <a class='custom-btn-action btn btn-primary mt-1 btn-sm' href='surat_output_kerusakan.php?id=" . intval(isset($row['id']) ? $row['id'] : 0) . "' target='_blank'><i class='bi bi-file-earmark-text-fill'></i></a> ";
                                                }
                                            } elseif ($isPengembalian) {
                                                $ptRow = trim(isset($row['pt']) ? $row['pt'] : '');
                                                if (in_array($ptRow, $userPTs, true)) {
                                                    $bulanRomawiArr = array(
                                                        1 => 'I',
                                                        2 => 'II',
                                                        3 => 'III',
                                                        4 => 'IV',
                                                        5 => 'V',
                                                        6 => 'VI',
                                                        7 => 'VII',
                                                        8 => 'VIII',
                                                        9 => 'IX',
                                                        10 => 'X',
                                                        11 => 'XI',
                                                        12 => 'XII'
                                                    );

                                                    $bulanRomawi = '';
                                                    $tahun = '';

                                                    if (!empty($tanggal)) {
                                                        $bulanNum = (int)date('n', strtotime($tanggal));
                                                        $tahun = date('Y', strtotime($tanggal));
                                                        $bulanRomawi = isset($bulanRomawiArr[$bulanNum]) ? $bulanRomawiArr[$bulanNum] : '';
                                                    }

                                                    if (isset($_SESSION['nama']) && $_SESSION['nama'] !== $pendingDeleteAprv) {
                                                        if (isset($_SESSION['hak_akses']) && $_SESSION['hak_akses'] === 'Admin') {
                                                            $extraButtons .= "<a class='custom-btn-action btn btn-dark btn-sm mt-1 tombolKirimEmailPending' 
                                                                        href='#' 
                                                                        data-id='{$ids}' 
                                                                        data-aktor='{$pendingDeleteAprvAktor}'
                                                                        data-nomor='" . htmlspecialchars(isset($nomor_ba) ? $nomor_ba : '', ENT_QUOTES) . "'
                                                                        data-tanggal='" . htmlspecialchars(isset($tanggal) ? $tanggal : '', ENT_QUOTES) . "'
                                                                        data-bulan-romawi='" . htmlspecialchars($bulanRomawi, ENT_QUOTES) . "'
                                                                        data-tahun='" . htmlspecialchars($tahun, ENT_QUOTES) . "'
                                                                        data-jenis-ba='pengembalian'
                                                                        data-jenis-permintaan='delete'
                                                                        data-nama-peminta='" . htmlspecialchars($namaUser, ENT_QUOTES) . "'
                                                                        >
                                                                        <i class='bi bi-envelope-at-fill'></i>
                                                                    </a>";
                                                        }
                                                    }

                                                    if (isset($_SESSION['nama']) && $pendingDeleteAprv != '' && $_SESSION['nama'] === $pendingDeleteAprv) {
                                                        $extraButtons .= "<a class='custom-btn-action btn btn-danger btn-sm mt-1 tombolApprovePendingDeletePopup' 
                                                                    href='#' 
                                                                    data-id='" . intval($ids) . "'
                                                                    data-jenis-ba='pengembalian'
                                                                    data-nama-approver='" . htmlspecialchars($pendingDeleteAprv, ENT_QUOTES) . "'
                                                                    data-alasan-delete='" . htmlspecialchars($pendingDeleteAlasan, ENT_QUOTES) . "'
                                                                    >
                                                                    <i class='bi bi-trash3'></i>
                                                                </a>";
                                                    }

                                                    $extraButtons .= "  <a class='custom-btn-action btn btn-secondary mt-1 btn-sm' href='detail_barang_pengembalian.php?id=" . intval(isset($row['id']) ? $row['id'] : 0) . "'><i class='bi bi-eye-fill'></i></a> 
                                                                        <a class='custom-btn-action btn btn-primary mt-1 btn-sm' href='surat_output_pengembalian.php?id=" . intval(isset($row['id']) ? $row['id'] : 0) . "' target='_blank'><i class='bi bi-file-earmark-text-fill'></i></a> ";
                                                }
                                            } elseif ($isNotebook) {
                                            } elseif ($isMutasi) {
                                                if (
                                                    in_array(trim(isset($row['pt_asal']) ? $row['pt_asal'] : ''), $userPTs, true) ||
                                                    in_array(trim(isset($row['pt_tujuan']) ? $row['pt_tujuan'] : ''), $userPTs, true)
                                                ) {
                                                    $bulanRomawiArr = [
                                                        1 => 'I',
                                                        2 => 'II',
                                                        3 => 'III',
                                                        4 => 'IV',
                                                        5 => 'V',
                                                        6 => 'VI',
                                                        7 => 'VII',
                                                        8 => 'VIII',
                                                        9 => 'IX',
                                                        10 => 'X',
                                                        11 => 'XI',
                                                        12 => 'XII'
                                                    ];

                                                    $bulanRomawi = '';
                                                    $tahun = '';

                                                    if (!empty($tanggal)) {
                                                        $bulanNum = (int)date('n', strtotime($tanggal));
                                                        $tahun = date('Y', strtotime($tanggal));
                                                        $bulanRomawi = isset($bulanRomawiArr[$bulanNum]) ? $bulanRomawiArr[$bulanNum] : '';
                                                    }

                                                    // Jika session login adalah Admin → tombol kirim email
                                                    if (isset($_SESSION['nama']) && $_SESSION['nama'] !== $pendingDeleteAprv) {
                                                        if (isset($_SESSION['hak_akses']) && $_SESSION['hak_akses'] === 'Admin') {
                                                            $extraButtons .= "<a class='custom-btn-action btn btn-dark btn-sm mt-1 tombolKirimEmailPending' 
                                                                    href='#' 
                                                                    data-id='{$ids}' 
                                                                    data-aktor='{$pendingDeleteAprvAktor}'
                                                                    data-nomor='" . htmlspecialchars(isset($nomor_ba) ? $nomor_ba : '', ENT_QUOTES) . "'
                                                                    data-tanggal='" . htmlspecialchars(isset($tanggal) ? $tanggal : '', ENT_QUOTES) . "'
                                                                    data-bulan-romawi='" . htmlspecialchars($bulanRomawi, ENT_QUOTES) . "'
                                                                    data-tahun='" . htmlspecialchars($tahun, ENT_QUOTES) . "'
                                                                    data-jenis-ba='mutasi'
                                                                    data-jenis-permintaan='delete'
                                                                    data-nama-peminta='" . htmlspecialchars($namaUser, ENT_QUOTES) . "'
                                                                    >
                                                                    <i class='bi bi-envelope-at-fill'></i>
                                                                </a>";
                                                        }
                                                    }
                                                    // Jika session nama = pending_approver → tombol approval
                                                    if (isset($_SESSION['nama']) && $pendingDeleteAprv != '' && $_SESSION['nama'] === $pendingDeleteAprv) {
                                                        $extraButtons .= "<a class='custom-btn-action btn btn-danger btn-sm mt-1 tombolApprovePendingDeletePopup' 
                                                                href='#' 
                                                                data-id='" . intval($ids) . "'
                                                                data-jenis-ba='mutasi'
                                                                data-nama-approver='" . $pendingDeleteAprv . "'
                                                                data-alasan-delete='" . $pendingDeleteAlasan . "'
                                                                >
                                                                <i class='bi bi-trash3'></i>
                                                            </a>";
                                                    }
                                                    $extraButtons .= "  <a class='custom-btn-action btn btn-secondary mt-1 btn-sm' href='detail_barang_mutasi.php?id=" . intval(isset($row['id']) ? $row['id'] : 0) . "'><i class='bi bi-eye-fill'></i></a> 
                                                                    <a class='custom-btn-action btn btn-primary mt-1 btn-sm' href='surat_output_mutasi.php?id=" . intval(isset($row['id']) ? $row['id'] : 0) . "' target='_blank'><i class='bi bi-file-earmark-text-fill'></i></a> ";
                                                }
                                            } elseif ($isSTAsset) {
                                                $ptRow = trim(isset($row['pt']) ? $row['pt'] : '');
                                                if (in_array($ptRow, $userPTs, true)) {
                                                    $bulanRomawiArr = [
                                                        1 => 'I',
                                                        2 => 'II',
                                                        3 => 'III',
                                                        4 => 'IV',
                                                        5 => 'V',
                                                        6 => 'VI',
                                                        7 => 'VII',
                                                        8 => 'VIII',
                                                        9 => 'IX',
                                                        10 => 'X',
                                                        11 => 'XI',
                                                        12 => 'XII'
                                                    ];

                                                    $bulanRomawi = '';
                                                    $tahun = '';

                                                    if (!empty($tanggal)) {
                                                        $bulanNum = (int)date('n', strtotime($tanggal));
                                                        $tahun = date('Y', strtotime($tanggal));
                                                        $bulanRomawi = isset($bulanRomawiArr[$bulanNum]) ? $bulanRomawiArr[$bulanNum] : '';
                                                    }

                                                    // Jika session login adalah Admin → tombol kirim email
                                                    if (isset($_SESSION['nama']) && $_SESSION['nama'] !== $pendingDeleteAprv) {
                                                        if (isset($_SESSION['hak_akses']) && $_SESSION['hak_akses'] === 'Admin') {
                                                            $extraButtons .= "<a class='custom-btn-action btn btn-dark btn-sm mt-1 tombolKirimEmailPending' 
                                                                    href='#' 
                                                                    data-id='{$ids}' 
                                                                    data-aktor='{$pendingDeleteAprvAktor}'
                                                                    data-nomor='" . htmlspecialchars(isset($nomor_ba) ? $nomor_ba : '', ENT_QUOTES) . "'
                                                                    data-tanggal='" . htmlspecialchars(isset($tanggal) ? $tanggal : '', ENT_QUOTES) . "'
                                                                    data-bulan-romawi='" . htmlspecialchars($bulanRomawi, ENT_QUOTES) . "'
                                                                    data-tahun='" . htmlspecialchars($tahun, ENT_QUOTES) . "'
                                                                    data-jenis-ba='st_asset'
                                                                    data-jenis-permintaan='delete'
                                                                    data-nama-peminta='" . htmlspecialchars($namaUser, ENT_QUOTES) . "'
                                                                    >
                                                                    <i class='bi bi-envelope-at-fill'></i>
                                                                </a>";
                                                        }
                                                    }
                                                    // Jika session nama = pending_approver → tombol approval
                                                    if (isset($_SESSION['nama']) && $pendingDeleteAprv != '' && $_SESSION['nama'] === $pendingDeleteAprv) {
                                                        $extraButtons .= "<a class='custom-btn-action btn btn-danger btn-sm mt-1 tombolApprovePendingDeletePopup' 
                                                                href='#' 
                                                                data-id='" . intval($ids) . "'
                                                                data-jenis-ba='st_asset'
                                                                data-nama-approver='" . $pendingDeleteAprv . "'
                                                                data-alasan-delete='" . $pendingDeleteAlasan . "'
                                                                >
                                                                <i class='bi bi-trash3'></i>
                                                            </a>";
                                                    }
                                                    $extraButtons .= "  <a class='custom-btn-action btn btn-secondary mt-1 btn-sm' href='detail_barang_serah_terima_asset.php?id=" . intval(isset($row['id']) ? $row['id'] : 0) . "'><i class='bi bi-eye-fill'></i></a> 
                                                                    <a class='custom-btn-action btn btn-primary mt-1 btn-sm' href='surat_output_serah_terima_asset.php?id=" . intval(isset($row['id']) ? $row['id'] : 0) . "' target='_blank'><i class='bi bi-file-earmark-text-fill'></i></a> ";
                                                }
                                            } elseif ($isPemutihan) {
                                                $ptRow = trim(isset($row['pt']) ? $row['pt'] : '');
                                                if (in_array($ptRow, $userPTs, true)) {
                                                    $bulanRomawiArr = [
                                                        1 => 'I',
                                                        2 => 'II',
                                                        3 => 'III',
                                                        4 => 'IV',
                                                        5 => 'V',
                                                        6 => 'VI',
                                                        7 => 'VII',
                                                        8 => 'VIII',
                                                        9 => 'IX',
                                                        10 => 'X',
                                                        11 => 'XI',
                                                        12 => 'XII'
                                                    ];

                                                    $bulanRomawi = '';
                                                    $tahun = '';

                                                    if (!empty($tanggal)) {
                                                        $bulanNum = (int)date('n', strtotime($tanggal));
                                                        $tahun = date('Y', strtotime($tanggal));
                                                        $bulanRomawi = isset($bulanRomawiArr[$bulanNum]) ? $bulanRomawiArr[$bulanNum] : '';
                                                    }

                                                    if (isset($_SESSION['nama']) && $_SESSION['nama'] !== $pendingDeleteAprv) {
                                                        if (isset($_SESSION['hak_akses']) && $_SESSION['hak_akses'] === 'Admin') {
                                                            $extraButtons .= "<a class='custom-btn-action btn btn-dark btn-sm mt-1 tombolKirimEmailPending' 
                                                                        href='#' 
                                                                        data-id='{$ids}' 
                                                                        data-aktor='{$pendingDeleteAprvAktor}'
                                                                        data-nomor='" . htmlspecialchars(isset($nomor_ba) ? $nomor_ba : '', ENT_QUOTES) . "'
                                                                        data-tanggal='" . htmlspecialchars(isset($tanggal) ? $tanggal : '', ENT_QUOTES) . "'
                                                                        data-bulan-romawi='" . htmlspecialchars($bulanRomawi, ENT_QUOTES) . "'
                                                                        data-tahun='" . htmlspecialchars($tahun, ENT_QUOTES) . "'
                                                                        data-jenis-ba='pemutihan'
                                                                        data-jenis-permintaan='delete'
                                                                        data-nama-peminta='" . htmlspecialchars($namaUser, ENT_QUOTES) . "'
                                                                        >
                                                                        <i class='bi bi-envelope-at-fill'></i>
                                                                    </a>";
                                                        }
                                                    }

                                                    if (isset($_SESSION['nama']) && $pendingDeleteAprv != '' && $_SESSION['nama'] === $pendingDeleteAprv) {
                                                        $extraButtons .= "<a class='custom-btn-action btn btn-danger btn-sm mt-1 tombolApprovePendingDeletePopup' 
                                                                    href='#' 
                                                                    data-id='" . intval($ids) . "'
                                                                    data-jenis-ba='pemutihan'
                                                                    data-nama-approver='" . $pendingDeleteAprv . "'
                                                                    data-alasan-delete='" . $pendingDeleteAlasan . "'
                                                                    >
                                                                    <i class='bi bi-trash3'></i>
                                                                </a>";
                                                    }

                                                    $extraButtons .= "  <a class='custom-btn-action btn btn-secondary mt-1 btn-sm' href='detail_barang_pemutihan.php?id=" . intval(isset($row['id']) ? $row['id'] : 0) . "'><i class='bi bi-eye-fill'></i></a> 
                                                                        <a class='custom-btn-action btn btn-primary mt-1 btn-sm' href='surat_output_pemutihan.php?id=" . intval(isset($row['id']) ? $row['id'] : 0) . "' target='_blank'><i class='bi bi-file-earmark-text-fill'></i></a> ";
                                                }
                                            }

                                            echo "<td class='pt-3 custom-font fw-bold' style='color: #850404;'>
                                            Data menunggu persetujuan hapus <br>
                                            <span class='custom-font' style='font-weight: normal;'>User: <strong>{$pendingDeleteAprvLabel}</strong></span><br>
                                            {$viewLink}
                                            {$extraButtons}
                                            </td>";
                                        } else {
                                            if ($pending) {
                                                if ($isKerusakan) {
                                                    // Tombol view dokumen yang diedit (khusus BA Kerusakan)
                                                    // $viewLink = "<br><a class='btn btn-secondary btn-sm mt-2' href='detail_edit_kerusakan.php?id=" . intval($ids) . "'>
                                                    //                 <i class='bi bi-eye-fill'></i>
                                                    //              </a>";

                                                    // --- Ubah tanggal menjadi bulan romawi dan tahun ---
                                                    $bulanRomawiArr = [
                                                        1 => 'I',
                                                        2 => 'II',
                                                        3 => 'III',
                                                        4 => 'IV',
                                                        5 => 'V',
                                                        6 => 'VI',
                                                        7 => 'VII',
                                                        8 => 'VIII',
                                                        9 => 'IX',
                                                        10 => 'X',
                                                        11 => 'XI',
                                                        12 => 'XII'
                                                    ];

                                                    $bulanRomawi = '';
                                                    $tahun = '';

                                                    if (!empty($tanggal)) {
                                                        $bulanNum = (int)date('n', strtotime($tanggal));
                                                        $tahun = date('Y', strtotime($tanggal));
                                                        $bulanRomawi = isset($bulanRomawiArr[$bulanNum]) ? $bulanRomawiArr[$bulanNum] : '';
                                                    }

                                                    // Jika session login adalah Admin → tombol kirim email
                                                    if (isset($_SESSION['nama']) && $_SESSION['nama'] !== $pendingApprover) {
                                                        if (isset($_SESSION['hak_akses']) && $_SESSION['hak_akses'] === 'Admin') {
                                                            $extraButtons .= "<a class='custom-btn-action btn btn-dark btn-sm mt-1 tombolKirimEmailPending' 
                                                                    href='#' 
                                                                    data-id='{$ids}' 
                                                                    data-aktor='{$pendingApproverAktor}'
                                                                    data-nomor='" . htmlspecialchars(isset($nomor_ba) ? $nomor_ba : '', ENT_QUOTES) . "'
                                                                    data-tanggal='" . htmlspecialchars(isset($tanggal) ? $tanggal : '', ENT_QUOTES) . "'
                                                                    data-bulan-romawi='" . htmlspecialchars($bulanRomawi, ENT_QUOTES) . "'
                                                                    data-tahun='" . htmlspecialchars($tahun, ENT_QUOTES) . "'
                                                                    data-jenis-ba='kerusakan'
                                                                    data-jenis-permintaan='edit'
                                                                    data-nama-peminta='" . htmlspecialchars($namaUser, ENT_QUOTES) . "'
                                                                    >
                                                                    <i class='bi bi-envelope-at-fill'></i>
                                                                </a>";
                                                        }
                                                    }
                                                    // Jika session nama = pending_approver → tombol approval
                                                    if (isset($_SESSION['nama']) && $pendingApprover != '' && $_SESSION['nama'] === $pendingApprover) {
                                                        $extraButtons .= "<a class='custom-btn-action btn btn-success btn-sm mt-1 tombolApprovePendingEditPopup' 
                                                                href='#' 
                                                                data-id='" . intval($ids) . "'
                                                                data-jenis-ba='kerusakan'
                                                                data-nama-approver='" . $pendingApprover . "'>
                                                                <i class='bi bi-check-circle'></i>
                                                            </a>";
                                                    }
                                                } elseif ($isPengembalian) {

                                                    $bulanRomawiArr = array(
                                                        1 => 'I',
                                                        2 => 'II',
                                                        3 => 'III',
                                                        4 => 'IV',
                                                        5 => 'V',
                                                        6 => 'VI',
                                                        7 => 'VII',
                                                        8 => 'VIII',
                                                        9 => 'IX',
                                                        10 => 'X',
                                                        11 => 'XI',
                                                        12 => 'XII'
                                                    );

                                                    $bulanRomawi = '';
                                                    $tahun = '';

                                                    if (!empty($tanggal)) {
                                                        $bulanNum = (int)date('n', strtotime($tanggal));
                                                        $tahun = date('Y', strtotime($tanggal));
                                                        $bulanRomawi = isset($bulanRomawiArr[$bulanNum]) ? $bulanRomawiArr[$bulanNum] : '';
                                                    }

                                                    if (isset($_SESSION['nama']) && $_SESSION['nama'] !== $pendingApprover) {
                                                        if (isset($_SESSION['hak_akses']) && $_SESSION['hak_akses'] === 'Admin') {
                                                            $extraButtons .= "<a class='custom-btn-action btn btn-dark btn-sm mt-1 tombolKirimEmailPending' 
                                                                    href='#' 
                                                                    data-id='{$ids}' 
                                                                    data-aktor='{$pendingApproverAktor}'
                                                                    data-nomor='" . htmlspecialchars(isset($nomor_ba) ? $nomor_ba : '', ENT_QUOTES) . "'
                                                                    data-tanggal='" . htmlspecialchars(isset($tanggal) ? $tanggal : '', ENT_QUOTES) . "'
                                                                    data-bulan-romawi='" . htmlspecialchars($bulanRomawi, ENT_QUOTES) . "'
                                                                    data-tahun='" . htmlspecialchars($tahun, ENT_QUOTES) . "'
                                                                    data-jenis-ba='pengembalian'
                                                                    data-jenis-permintaan='edit'
                                                                    data-nama-peminta='" . htmlspecialchars($namaUser, ENT_QUOTES) . "'
                                                                    >
                                                                    <i class='bi bi-envelope-at-fill'></i>
                                                                </a>";
                                                        }
                                                    }

                                                    if (isset($_SESSION['nama']) && $pendingApprover != '' && $_SESSION['nama'] === $pendingApprover) {
                                                        $extraButtons .= "<a class='custom-btn-action btn btn-success btn-sm mt-1 tombolApprovePendingEditPopup' 
                                                                href='#' 
                                                                data-id='" . intval($ids) . "'
                                                                data-jenis-ba='pengembalian'
                                                                data-nama-approver='" . htmlspecialchars($pendingApprover, ENT_QUOTES) . "'>
                                                                <i class='bi bi-check-circle'></i>
                                                            </a>";
                                                    }

                                                    $extraButtons .= "  <a class='custom-btn-action btn btn-secondary mt-1 btn-sm' href='detail_barang_pengembalian.php?id=" . intval(isset($row['id']) ? $row['id'] : 0) . "'><i class='bi bi-eye-fill'></i></a> 
                                                                    <a class='custom-btn-action btn btn-primary mt-1 btn-sm' href='surat_output_pengembalian.php?id=" . intval(isset($row['id']) ? $row['id'] : 0) . "' target='_blank'><i class='bi bi-file-earmark-text-fill'></i></a> ";
                                                } elseif ($isNotebook) {
                                                    // wadah untuk tombol/aksi pending BA Notebook (belum diisi)
                                                } elseif ($isMutasi) {

                                                    // --- Ubah tanggal menjadi bulan romawi dan tahun ---
                                                    $bulanRomawiArr = [
                                                        1 => 'I',
                                                        2 => 'II',
                                                        3 => 'III',
                                                        4 => 'IV',
                                                        5 => 'V',
                                                        6 => 'VI',
                                                        7 => 'VII',
                                                        8 => 'VIII',
                                                        9 => 'IX',
                                                        10 => 'X',
                                                        11 => 'XI',
                                                        12 => 'XII'
                                                    ];

                                                    $bulanRomawi = '';
                                                    $tahun = '';

                                                    if (!empty($tanggal)) {
                                                        $bulanNum = (int)date('n', strtotime($tanggal));
                                                        $tahun = date('Y', strtotime($tanggal));
                                                        $bulanRomawi = isset($bulanRomawiArr[$bulanNum]) ? $bulanRomawiArr[$bulanNum] : '';
                                                    }

                                                    // Jika session login adalah Admin → tombol kirim email
                                                    if (isset($_SESSION['nama']) && $_SESSION['nama'] !== $pendingApprover) {
                                                        if (isset($_SESSION['hak_akses']) && $_SESSION['hak_akses'] === 'Admin') {
                                                            $extraButtons .= "<a class='custom-btn-action btn btn-dark btn-sm mt-1 tombolKirimEmailPending' 
                                                                    href='#' 
                                                                    data-id='{$ids}' 
                                                                    data-aktor='{$pendingApproverAktor}'
                                                                    data-nomor='" . htmlspecialchars(isset($nomor_ba) ? $nomor_ba : '', ENT_QUOTES) . "'
                                                                    data-tanggal='" . htmlspecialchars(isset($tanggal) ? $tanggal : '', ENT_QUOTES) . "'
                                                                    data-bulan-romawi='" . htmlspecialchars($bulanRomawi, ENT_QUOTES) . "'
                                                                    data-tahun='" . htmlspecialchars($tahun, ENT_QUOTES) . "'
                                                                    data-jenis-ba='mutasi'
                                                                    data-jenis-permintaan='edit'
                                                                    data-nama-peminta='" . htmlspecialchars($namaUser, ENT_QUOTES) . "'
                                                                    >
                                                                    <i class='bi bi-envelope-at-fill'></i>
                                                                </a>";
                                                        }
                                                    }
                                                    // Jika session nama = pending_approver → tombol approval
                                                    if (isset($_SESSION['nama']) && $pendingApprover != '' && $_SESSION['nama'] === $pendingApprover) {
                                                        $extraButtons .= "<a class='custom-btn-action btn btn-success btn-sm mt-1 tombolApprovePendingEditPopup' 
                                                                href='#' 
                                                                data-id='" . intval($ids) . "'
                                                                data-jenis-ba='mutasi'
                                                                data-nama-approver='" . $pendingApprover . "'>
                                                                <i class='bi bi-check-circle'></i>
                                                            </a>";
                                                    }
                                                } elseif ($isSTAsset) {

                                                    // --- Ubah tanggal menjadi bulan romawi dan tahun ---
                                                    $bulanRomawiArr = [
                                                        1 => 'I',
                                                        2 => 'II',
                                                        3 => 'III',
                                                        4 => 'IV',
                                                        5 => 'V',
                                                        6 => 'VI',
                                                        7 => 'VII',
                                                        8 => 'VIII',
                                                        9 => 'IX',
                                                        10 => 'X',
                                                        11 => 'XI',
                                                        12 => 'XII'
                                                    ];

                                                    $bulanRomawi = '';
                                                    $tahun = '';

                                                    if (!empty($tanggal)) {
                                                        $bulanNum = (int)date('n', strtotime($tanggal));
                                                        $tahun = date('Y', strtotime($tanggal));
                                                        $bulanRomawi = isset($bulanRomawiArr[$bulanNum]) ? $bulanRomawiArr[$bulanNum] : '';
                                                    }

                                                    // Jika session login adalah Admin → tombol kirim email
                                                    if (isset($_SESSION['nama']) && $_SESSION['nama'] !== $pendingApprover) {
                                                        if (isset($_SESSION['hak_akses']) && $_SESSION['hak_akses'] === 'Admin') {
                                                            $extraButtons .= "<a class='custom-btn-action btn btn-dark btn-sm mt-1 tombolKirimEmailPending' 
                                                                    href='#' 
                                                                    data-id='{$ids}' 
                                                                    data-aktor='{$pendingApproverAktor}'
                                                                    data-nomor='" . htmlspecialchars(isset($nomor_ba) ? $nomor_ba : '', ENT_QUOTES) . "'
                                                                    data-tanggal='" . htmlspecialchars(isset($tanggal) ? $tanggal : '', ENT_QUOTES) . "'
                                                                    data-bulan-romawi='" . htmlspecialchars($bulanRomawi, ENT_QUOTES) . "'
                                                                    data-tahun='" . htmlspecialchars($tahun, ENT_QUOTES) . "'
                                                                    data-jenis-ba='st_asset'
                                                                    data-jenis-permintaan='edit'
                                                                    data-nama-peminta='" . htmlspecialchars($namaUser, ENT_QUOTES) . "'
                                                                    >
                                                                    <i class='bi bi-envelope-at-fill'></i>
                                                                </a>";
                                                        }
                                                    }
                                                    // Jika session nama = pending_approver → tombol approval
                                                    if (isset($_SESSION['nama']) && $pendingApprover != '' && $_SESSION['nama'] === $pendingApprover) {
                                                        $extraButtons .= "<a class='custom-btn-action btn btn-success btn-sm mt-1 tombolApprovePendingEditPopup' 
                                                                href='#' 
                                                                data-id='" . intval($ids) . "'
                                                                data-jenis-ba='st_asset'
                                                                data-nama-approver='" . $pendingApprover . "'>
                                                                <i class='bi bi-check-circle'></i>
                                                            </a>";
                                                    }
                                                } elseif ($isPemutihan) {

                                                    $bulanRomawiArr = [
                                                        1 => 'I',
                                                        2 => 'II',
                                                        3 => 'III',
                                                        4 => 'IV',
                                                        5 => 'V',
                                                        6 => 'VI',
                                                        7 => 'VII',
                                                        8 => 'VIII',
                                                        9 => 'IX',
                                                        10 => 'X',
                                                        11 => 'XI',
                                                        12 => 'XII'
                                                    ];

                                                    $bulanRomawi = '';
                                                    $tahun = '';

                                                    if (!empty($tanggal)) {
                                                        $bulanNum = (int)date('n', strtotime($tanggal));
                                                        $tahun = date('Y', strtotime($tanggal));
                                                        $bulanRomawi = isset($bulanRomawiArr[$bulanNum]) ? $bulanRomawiArr[$bulanNum] : '';
                                                    }

                                                    if (isset($_SESSION['nama']) && $_SESSION['nama'] !== $pendingApprover) {
                                                        if (isset($_SESSION['hak_akses']) && $_SESSION['hak_akses'] === 'Admin') {
                                                            $extraButtons .= "<a class='custom-btn-action btn btn-dark btn-sm mt-1 tombolKirimEmailPending' 
                                                                    href='#' 
                                                                    data-id='{$ids}' 
                                                                    data-aktor='{$pendingApproverAktor}'
                                                                    data-nomor='" . htmlspecialchars(isset($nomor_ba) ? $nomor_ba : '', ENT_QUOTES) . "'
                                                                    data-tanggal='" . htmlspecialchars(isset($tanggal) ? $tanggal : '', ENT_QUOTES) . "'
                                                                    data-bulan-romawi='" . htmlspecialchars($bulanRomawi, ENT_QUOTES) . "'
                                                                    data-tahun='" . htmlspecialchars($tahun, ENT_QUOTES) . "'
                                                                    data-jenis-ba='pemutihan'
                                                                    data-jenis-permintaan='edit'
                                                                    data-nama-peminta='" . htmlspecialchars($namaUser, ENT_QUOTES) . "'
                                                                    >
                                                                    <i class='bi bi-envelope-at-fill'></i>
                                                                </a>";
                                                        }
                                                    }

                                                    if (isset($_SESSION['nama']) && $pendingApprover != '' && $_SESSION['nama'] === $pendingApprover) {
                                                        $extraButtons .= "<a class='custom-btn-action btn btn-success btn-sm mt-1 tombolApprovePendingEditPopup' 
                                                                href='#' 
                                                                data-id='" . intval($ids) . "'
                                                                data-jenis-ba='pemutihan'
                                                                data-nama-approver='" . htmlspecialchars($pendingApprover, ENT_QUOTES) . "'>
                                                                <i class='bi bi-check-circle'></i>
                                                            </a>";
                                                    }
                                                }



                                                echo "<td class='pt-3 custom-font fw-bold' style='color: #856404;'>
                                            Data menunggu persetujuan edit<br>
                                            <span class='custom-font' style='font-weight: normal;'>User: <strong>{$pendingApproverLabel}</strong></span><br>
                                            {$viewLink}
                                            {$extraButtons}
                                            </td>";
                                            } else {

                                                if ($isKerusakan) {
                                                    $pt_untuk_urutan = trim(isset($row['pt']) ? $row['pt'] : '');

                                                    //Start: Temporary Unused Logic
                                                    $jab1 = trim(
                                                        (isset($row['jabatan_aprv1']) ? $row['jabatan_aprv1'] : '') . ' ' .
                                                            (isset($row['departemen_aprv1']) ? $row['departemen_aprv1'] : '')
                                                    );

                                                    $jab2 = trim(
                                                        (isset($row['jabatan_aprv2']) ? $row['jabatan_aprv2'] : '') . ' ' .
                                                            (isset($row['departemen_aprv2']) ? $row['departemen_aprv2'] : '')
                                                    );
                                                    $jab3 = trim(
                                                        (isset($row['jabatan_aprv3']) ? $row['jabatan_aprv3'] : '') . ' ' .
                                                            (isset($row['departemen_aprv3']) ? $row['departemen_aprv3'] : '')
                                                    );
                                                    $jab4 = trim(
                                                        (isset($row['jabatan_aprv4']) ? $row['jabatan_aprv4'] : '') . ' ' .
                                                            (isset($row['departemen_aprv4']) ? $row['departemen_aprv4'] : '')
                                                    );
                                                    //End: Temporary Unused Logic

                                                    $namaPembuat   = trim(isset($row['pembuat']) ? $row['pembuat'] : '');
                                                    $peminjam      = trim(isset($row['peminjam']) ? $row['peminjam'] : '');
                                                    $atasanPeminjam = trim(isset($row['atasan_peminjam']) ? $row['atasan_peminjam'] : '');
                                                    $namaPenyetujui = trim(isset($row['penyetujui']) ? $row['penyetujui'] : '');
                                                    $namaDiketahui = trim(isset($row['diketahui']) ? $row['diketahui'] : '');


                                                    $isNamaPembuat = ($namaPembuat !== '' && $namaPembuat === $sessionUser);
                                                    $isPeminjam = ($peminjam !== '' && $peminjam === $sessionUser);
                                                    $isAtasanPeminjam = ($atasanPeminjam !== '' && $atasanPeminjam === $sessionUser);
                                                    $isNamaPenyetujui = ($namaPenyetujui !== '' && $namaPenyetujui === $sessionUser);
                                                    $isNamaDiketahui = ($namaDiketahui !== '' && $namaDiketahui === $sessionUser);

                                                    $labelAprv1                 = $isNamaPembuat ? "<p class='custom-font m-0 text-primary'>Anda</p>" : htmlspecialchars($namaPembuat ?: '-', ENT_QUOTES);
                                                    $labelAprvPeminjam          = $isPeminjam ? "<p class='custom-font m-0 text-primary'>Anda</p>" : htmlspecialchars($peminjam ?: '-', ENT_QUOTES);
                                                    $labelAprvAtasanPeminjam    = $isAtasanPeminjam ? "<p class='custom-font m-0 text-primary'>Anda</p>" : htmlspecialchars($atasanPeminjam ?: '-', ENT_QUOTES);
                                                    $labelAprv2                 = $isNamaPenyetujui ? "<p class='custom-font m-0 text-primary'>Anda</p>" : htmlspecialchars($namaPenyetujui ?: '-', ENT_QUOTES);
                                                    $labelAprvDiketahui         = $isNamaDiketahui ? "<p class='custom-font m-0 text-primary'>Anda</p>" : htmlspecialchars($namaDiketahui ?: '-', ENT_QUOTES);

                                                    if ($pt_untuk_urutan === 'PT.MSAL (HO)') {
                                                        echo "<td class='custom-font pt-3'>"
                                                            . statusBadge($approval_1, $row, 'approval_1', 'autograph_1', $namaPembuat, 'pembuat', 'kerusakan', $autograph_1, 'PT.MSAL (HO)') .
                                                            "<br><div class='custom-font m-0 p-0 mt-1'>{$labelAprv1}</div></td>";

                                                        if ($namaPembuat == "-" || $row['approval_1'] == "1") {
                                                            echo "<td class='custom-font pt-3'>"
                                                                . statusBadge($approval_3, $row, 'approval_3', 'autograph_3', $peminjam, 'peminjam', 'kerusakan', $autograph_3, 'PT.MSAL (HO)') .
                                                                "<br><div class='custom-font m-0 p-0 mt-1'>{$labelAprvPeminjam}</div></td>";
                                                        } else {
                                                            echo "<td class='custom-font pt-3'><i class='bi bi-hourglass text-warning fs-6'></i><br><div class='custom-font m-0 p-0 mt-1'>{$labelAprvPeminjam}</div></td>";
                                                        }

                                                        if ($row['approval_3'] == "1") {
                                                            echo "<td class='custom-font pt-3'>"
                                                                . statusBadge($approval_4, $row, 'approval_4', 'autograph_4', $atasanPeminjam, 'atasan_peminjam', 'kerusakan', $autograph_4, 'PT.MSAL (HO)') .
                                                                "<br><div class='custom-font m-0 p-0 mt-1'>{$labelAprvAtasanPeminjam}</div></td>";
                                                        } else {
                                                            echo "<td class='custom-font pt-3'><i class='bi bi-hourglass text-warning fs-6'></i><br><div class='custom-font m-0 p-0 mt-1'>{$labelAprvAtasanPeminjam}</div></td>";
                                                        }

                                                        if ($atasanPeminjam == "-" || $row['approval_4'] == "1") {
                                                            echo "<td class='custom-font pt-3'>"
                                                                . statusBadge($approval_5, $row, 'approval_5', 'autograph_5', $namaDiketahui, 'diketahui', 'kerusakan', $autograph_5, 'PT.MSAL (HO)') .
                                                                "<br><div class='custom-font m-0 p-0 mt-1'>{$labelAprvDiketahui}</div></td>";
                                                        } else {
                                                            echo "<td class='custom-font pt-3'><i class='bi bi-hourglass text-warning fs-6'></i><br><div class='custom-font m-0 p-0 mt-1'>{$labelAprvDiketahui}</div></td>";
                                                        }

                                                        if ($row['approval_5'] == "1") {
                                                            echo "<td class='custom-font pt-3'>"
                                                                . statusBadge($approval_2, $row, 'approval_2', 'autograph_2', $namaPenyetujui, 'penyetujui', 'kerusakan', $autograph_2, 'PT.MSAL (HO)') .
                                                                "<br><div class='custom-font m-0 p-0 mt-1'>{$labelAprv2}</div></td>";
                                                        } elseif ($namaPenyetujui != "-") {
                                                            echo "<td class='custom-font pt-3'><i class='bi bi-hourglass text-warning fs-6'></i><br><div class='custom-font m-0 p-0 mt-1'>{$labelAprv2}</div></td>";
                                                        } else {
                                                            echo "<td class='custom-font pt-3'>-<br><div class='custom-font m-0 p-0 mt-1'>{$labelAprv2}</div></td>";
                                                        }
                                                        echo "<td class='custom-font pt-3'>-</td>";
                                                    } else {
                                                        echo "<td class='custom-font pt-3'>-</td>";

                                                        echo "<td class='custom-font pt-3'>"
                                                            . statusBadge($approval_3, $row, 'approval_3', 'autograph_3', $peminjam, 'peminjam', 'kerusakan', $autograph_3, $pt_untuk_urutan) .
                                                            "<br><div class='custom-font m-0 p-0 mt-1'>{$labelAprvPeminjam}</div></td>";

                                                        if ($row['approval_3'] == "1") {
                                                            echo "<td class='custom-font pt-3'>"
                                                                . statusBadge($approval_4, $row, 'approval_4', 'autograph_4', $atasanPeminjam, 'atasan_peminjam', 'kerusakan', $autograph_4, $pt_untuk_urutan) .
                                                                "<br><div class='custom-font m-0 p-0 mt-1'>{$labelAprvAtasanPeminjam}</div></td>";
                                                        } elseif ($atasanPeminjam != "-") {
                                                            echo "<td class='custom-font pt-3'><i class='bi bi-hourglass text-warning fs-6'></i><br><div class='custom-font m-0 p-0 mt-1'>{$labelAprvAtasanPeminjam}</div></td>";
                                                        } else {
                                                            echo "<td class='custom-font pt-3'>-<br><div class='custom-font m-0 p-0 mt-1'>{$labelAprvAtasanPeminjam}</div></td>";
                                                        }

                                                        if ($atasanPeminjam == "-" || $row['approval_4'] == "1") {
                                                            echo "<td class='custom-font pt-3'>"
                                                                . statusBadge($approval_5, $row, 'approval_5', 'autograph_5', $namaDiketahui, 'diketahui', 'kerusakan', $autograph_5, $pt_untuk_urutan) .
                                                                "<br><div class='custom-font m-0 p-0 mt-1'>{$labelAprvDiketahui}</div></td>";
                                                        } elseif ($namaDiketahui != "-") {
                                                            echo "<td class='custom-font pt-3'><i class='bi bi-hourglass text-warning fs-6'></i><br><div class='custom-font m-0 p-0 mt-1'>{$labelAprvDiketahui}</div></td>";
                                                        } else {
                                                            echo "<td class='custom-font pt-3'>-<br><div class='custom-font m-0 p-0 mt-1'>{$labelAprvDiketahui}</div></td>";
                                                        }

                                                        if ($namaDiketahui == "-" || $row['approval_5'] == "1") {
                                                            echo "<td class='custom-font pt-3'>"
                                                                . statusBadge($approval_2, $row, 'approval_2', 'autograph_2', $namaPenyetujui, 'penyetujui', 'kerusakan', $autograph_2, $pt_untuk_urutan) .
                                                                "<br><div class='custom-font m-0 p-0 mt-1'>{$labelAprv2}</div></td>";
                                                        } elseif ($namaPenyetujui != "-") {
                                                            echo "<td class='custom-font pt-3'><i class='bi bi-hourglass text-warning fs-6'></i><br><div class='custom-font m-0 p-0 mt-1'>{$labelAprv2}</div></td>";
                                                        } else {
                                                            echo "<td class='custom-font pt-3'>-<br><div class='custom-font m-0 p-0 mt-1'>{$labelAprv2}</div></td>";
                                                        }

                                                        if ($namaPenyetujui == "-" || $row['approval_2'] == "1") {
                                                            echo "<td class='custom-font pt-3'>"
                                                                . statusBadge($approval_1, $row, 'approval_1', 'autograph_1', $namaPembuat, 'pembuat', 'kerusakan', $autograph_1, $pt_untuk_urutan) .
                                                                "<br><div class='custom-font m-0 p-0 mt-1'>{$labelAprv1}</div></td>";
                                                        } elseif ($namaPembuat != "-") {
                                                            echo "<td class='custom-font pt-3'><i class='bi bi-hourglass text-warning fs-6'></i><br><div class='custom-font m-0 p-0 mt-1'>{$labelAprv1}</div></td>";
                                                        } else {
                                                            echo "<td class='custom-font pt-3'>-<br><div class='custom-font m-0 p-0 mt-1'>{$labelAprv1}</div></td>";
                                                        }
                                                    }
                                                    echo "<td class='pt-3'>";

                                                    echo "<a class='custom-btn-action btn btn-secondary btn-sm' href='detail_barang_kerusakan.php?id=" . intval(isset($row['id']) ? $row['id'] : 0) . "'><i class='bi bi-eye-fill'></i></a> ";
                                                    echo "<a class='custom-btn-action btn btn-primary btn-sm' href='surat_output_kerusakan.php?id=" . intval(isset($row['id']) ? $row['id'] : 0) . "' target='_blank'><i class='bi bi-file-earmark-text-fill'></i></a> ";

                                                    // ======================================================================
                                                } elseif ($isMutasi) {

                                                    $pt_untuk_akses_ptasal = trim(isset($row['pt_asal']) ? $row['pt_asal'] : '');
                                                    $pt_untuk_akses_pttujuan = trim(isset($row['pt_tujuan']) ? $row['pt_tujuan'] : '');
                                                    $namaPengirim = trim(isset($row['pengirim1']) ? $row['pengirim1'] : '');
                                                    $namaPengirim2 = trim(isset($row['pengirim2']) ? $row['pengirim2'] : '');
                                                    $namaHRDGAPengirim = trim(isset($row['hrd_ga_pengirim']) ? $row['hrd_ga_pengirim'] : '');
                                                    $namaPenerima1 = trim(isset($row['penerima1']) ? $row['penerima1'] : '');
                                                    $namaPenerima2 = trim(isset($row['penerima2']) ? $row['penerima2'] : '');
                                                    $namaHRDGAPenerima = trim(isset($row['hrd_ga_penerima']) ? $row['hrd_ga_penerima'] : '');
                                                    $namaDiketahui = trim(isset($row['diketahui']) ? $row['diketahui'] : '');
                                                    $namaPemeriksa1 = trim(isset($row['pemeriksa1']) ? $row['pemeriksa1'] : '');
                                                    $namaPemeriksa2 = trim(isset($row['pemeriksa2']) ? $row['pemeriksa2'] : '');
                                                    $namaPenyetujui1 = trim(isset($row['penyetujui1']) ? $row['penyetujui1'] : '');
                                                    $namaPenyetujui2 = trim(isset($row['penyetujui2']) ? $row['penyetujui2'] : '');

                                                    $isPengirim = ($namaPengirim !== '' && $namaPengirim === $sessionUser);
                                                    $isPengirim2 = ($namaPengirim2 !== '' && $namaPengirim2 === $sessionUser);
                                                    $isHRDGAPengirim = ($namaHRDGAPengirim !== '' && $namaHRDGAPengirim === $sessionUser);
                                                    $isPenerima1 = ($namaPenerima1 !== '' && $namaPenerima1 === $sessionUser);
                                                    $isPenerima2 = ($namaPenerima2 !== '' && $namaPenerima2 === $sessionUser);
                                                    $isHRDGAPenerima = ($namaHRDGAPenerima !== '' && $namaHRDGAPenerima === $sessionUser);
                                                    $isDiketahui = ($namaDiketahui !== '' && $namaDiketahui === $sessionUser);
                                                    $isPemeriksa1 = ($namaPemeriksa1 !== '' && $namaPemeriksa1 === $sessionUser);
                                                    $isPemeriksa2 = ($namaPemeriksa2 !== '' && $namaPemeriksa2 === $sessionUser);
                                                    $isPenyetujui1 = ($namaPenyetujui1 !== '' && $namaPenyetujui1 === $sessionUser);
                                                    $isPenyetujui2 = ($namaPenyetujui2 !== '' && $namaPenyetujui2 === $sessionUser);

                                                    $labelAprv1 = $isPengirim ? "<p class='custom-font m-0 text-primary'>Anda</p>" : htmlspecialchars($namaPengirim ?: '-', ENT_QUOTES);
                                                    $labelAprv2 = $isPengirim2 ? "<p class='custom-font m-0 text-primary'>Anda</p>" : htmlspecialchars($namaPengirim2 ?: '-', ENT_QUOTES);
                                                    $labelAprv3 = $isHRDGAPengirim ? "<p class='custom-font m-0 text-primary'>Anda</p>" : htmlspecialchars($namaHRDGAPengirim ?: '-', ENT_QUOTES);
                                                    $labelAprv4 = $isPenerima1 ? "<p class='custom-font m-0 text-primary'>Anda</p>" : htmlspecialchars($namaPenerima1 ?: '-', ENT_QUOTES);
                                                    $labelAprv5 = $isPenerima2 ? "<p class='custom-font m-0 text-primary'>Anda</p>" : htmlspecialchars($namaPenerima2 ?: '-', ENT_QUOTES);
                                                    $labelAprv6 = $isHRDGAPenerima ? "<p class='custom-font m-0 text-primary'>Anda</p>" : htmlspecialchars($namaHRDGAPenerima ?: '-', ENT_QUOTES);
                                                    $labelAprv7 = $isDiketahui ? "<p class='custom-font m-0 text-primary'>Anda</p>" : htmlspecialchars($namaDiketahui ?: '-', ENT_QUOTES);
                                                    $labelAprv8 = $isPemeriksa1 ? "<p class='custom-font m-0 text-primary'>Anda</p>" : htmlspecialchars($namaPemeriksa1 ?: '-', ENT_QUOTES);
                                                    $labelAprv9 = $isPemeriksa2 ? "<p class='custom-font m-0 text-primary'>Anda</p>" : htmlspecialchars($namaPemeriksa2 ?: '-', ENT_QUOTES);
                                                    $labelAprv10 = $isPenyetujui1 ? "<p class='custom-font m-0 text-primary'>Anda</p>" : htmlspecialchars($namaPenyetujui1 ?: '-', ENT_QUOTES);
                                                    $labelAprv11 = $isPenyetujui2 ? "<p class='custom-font m-0 text-primary'>Anda</p>" : htmlspecialchars($namaPenyetujui2 ?: '-', ENT_QUOTES);

                                                    if (in_array($pt_untuk_akses_ptasal, $userPTs, true)) {
                                                        echo "<td class='custom-font pt-3'>"
                                                            . statusBadge($approval_1, $row, 'approval_1', 'autograph_1', $namaPengirim, 'pengirim', 'mutasi', $autograph_1, $pt_untuk_akses_ptasal) .
                                                            "<br><div>{$labelAprv1}</div></td>";
                                                    } else {
                                                        if ($approval_1 == '1') {
                                                            echo "<td class='custom-font pt-3'><i class='bi bi-check-square-fill text-success fs-6'></i><br><div class='custom-font m-0 p-0 mt-1'>{$labelAprv1}</div></td>";
                                                        } elseif ($approval_1 == '0') {
                                                            echo "<td class='custom-font pt-3'><i class='bi bi-hourglass text-warning fs-6'></i><br><div class='custom-font m-0 p-0 mt-1'>{$labelAprv1}</div></td>";
                                                        } else {
                                                            echo "<td class='custom-font pt-3'>Kondisi tidak valid<br><div class='custom-font m-0 p-0 mt-1'>{$labelAprv1}</div></td>";
                                                        }
                                                    }
                                                    if ($row['approval_1'] == "1" && in_array($pt_untuk_akses_ptasal, $userPTs, true)) {
                                                        echo "<td class='custom-font pt-3'>"
                                                            . statusBadge($approval_2, $row, 'approval_2', 'autograph_2', $namaPengirim2, 'pengirim2', 'mutasi', $autograph_2, $pt_untuk_akses_ptasal) .
                                                            "<br><div>{$labelAprv2}</div></td>";
                                                    } else {
                                                        if ($approval_2 == '1') {
                                                            echo "<td class='custom-font pt-3'><i class='bi bi-check-square-fill text-success fs-6'></i><br><div class='custom-font m-0 p-0 mt-1'>{$labelAprv2}</div></td>";
                                                        } elseif ($approval_2 == '0') {
                                                            echo "<td class='custom-font pt-3'><i class='bi bi-hourglass text-warning fs-6'></i><br><div class='custom-font m-0 p-0 mt-1'>{$labelAprv2}</div></td>";
                                                        } else {
                                                            echo "<td class='custom-font pt-3'>Kondisi tidak valid<br><div class='custom-font m-0 p-0 mt-1'>{$labelAprv2}</div></td>";
                                                        }
                                                    }

                                                    if ($row['approval_2'] == "1" && in_array($pt_untuk_akses_ptasal, $userPTs, true)) {
                                                        echo "<td class='custom-font pt-3'>"
                                                            . statusBadge($approval_3, $row, 'approval_3', 'autograph_3', $namaHRDGAPengirim, 'hrd_ga_pengirim', 'mutasi', $autograph_3, $pt_untuk_akses_ptasal) .
                                                            "<br><div>{$labelAprv3}</div></td>";
                                                    } else {
                                                        if ($approval_3 == '1') {
                                                            echo "<td class='custom-font pt-3'><i class='bi bi-check-square-fill text-success fs-6'></i><br><div class='custom-font m-0 p-0 mt-1'>{$labelAprv3}</div></td>";
                                                        } elseif ($approval_3 == '0') {
                                                            echo "<td class='custom-font pt-3'><i class='bi bi-hourglass text-warning fs-6'></i><br><div class='custom-font m-0 p-0 mt-1'>{$labelAprv3}</div></td>";
                                                        } else {
                                                            echo "<td class='custom-font pt-3'>Kondisi tidak valid<br><div class='custom-font m-0 p-0 mt-1'>{$labelAprv3}</div></td>";
                                                        }
                                                    }

                                                    if ($row['approval_3'] == "1" && in_array($pt_untuk_akses_pttujuan, $userPTs, true)) {
                                                        echo "<td class='custom-font pt-3'>"
                                                            . statusBadge($approval_4, $row, 'approval_4', 'autograph_4', $namaPenerima1, 'penerima1', 'mutasi', $autograph_4, $pt_untuk_akses_pttujuan) .
                                                            "<br><div>{$labelAprv4}</div></td>";
                                                    } else {
                                                        if ($approval_4 == '1') {
                                                            echo "<td class='custom-font pt-3'><i class='bi bi-check-square-fill text-success fs-6'></i><br><div class='custom-font m-0 p-0 mt-1'>{$labelAprv4}</div></td>";
                                                        } elseif ($approval_4 == '0') {
                                                            echo "<td class='custom-font pt-3'><i class='bi bi-hourglass text-warning fs-6'></i><br><div class='custom-font m-0 p-0 mt-1'>{$labelAprv4}</div></td>";
                                                        } else {
                                                            echo "<td class='custom-font pt-3'>Kondisi tidak valid<br><div class='custom-font m-0 p-0 mt-1'>{$labelAprv4}</div></td>";
                                                        }
                                                    }

                                                    if ($row['approval_4'] == "1" && in_array($pt_untuk_akses_pttujuan, $userPTs, true)) {
                                                        echo "<td class='custom-font pt-3'>"
                                                            . statusBadge($approval_5, $row, 'approval_5', 'autograph_5', $namaPenerima2, 'penerima2', 'mutasi', $autograph_5, $pt_untuk_akses_pttujuan) .
                                                            "<br><div>{$labelAprv5}</div></td>";
                                                    } else {
                                                        if ($approval_5 == '1') {
                                                            echo "<td class='custom-font pt-3'><i class='bi bi-check-square-fill text-success fs-6'></i><br><div class='custom-font m-0 p-0 mt-1'>{$labelAprv5}</div></td>";
                                                        } elseif ($approval_5 == '0') {
                                                            echo "<td class='custom-font pt-3'><i class='bi bi-hourglass text-warning fs-6'></i><br><div class='custom-font m-0 p-0 mt-1'>{$labelAprv5}</div></td>";
                                                        } else {
                                                            echo "<td class='custom-font pt-3'>Kondisi tidak valid<br><div class='custom-font m-0 p-0 mt-1'>{$labelAprv5}</div></td>";
                                                        }
                                                    }

                                                    if ($row['approval_5'] == "1" && in_array($pt_untuk_akses_pttujuan, $userPTs, true)) {
                                                        echo "<td class='custom-font pt-3'>"
                                                            . statusBadge($approval_6, $row, 'approval_6', 'autograph_6', $namaHRDGAPenerima, 'hrd_ga_penerima', 'mutasi', $autograph_6, $pt_untuk_akses_pttujuan) .
                                                            "<br><div>{$labelAprv6}</div></td>";
                                                    } else {
                                                        if ($approval_6 == '1') {
                                                            echo "<td class='custom-font pt-3'><i class='bi bi-check-square-fill text-success fs-6'></i><br><div class='custom-font m-0 p-0 mt-1'>{$labelAprv6}</div></td>";
                                                        } elseif ($approval_6 == '0') {
                                                            echo "<td class='custom-font pt-3'><i class='bi bi-hourglass text-warning fs-6'></i><br><div class='custom-font m-0 p-0 mt-1'>{$labelAprv6}</div></td>";
                                                        } else {
                                                            echo "<td class='custom-font pt-3'>Kondisi tidak valid<br><div class='custom-font m-0 p-0 mt-1'>{$labelAprv6}</div></td>";
                                                        }
                                                    }

                                                    if ($row['approval_6'] == "1" && in_array('PT.MSAL (HO)', $userPTs, true)) {
                                                        echo "<td class='custom-font pt-3'>"
                                                            . statusBadge($approval_7, $row, 'approval_7', 'autograph_7', $namaDiketahui, 'diketahui', 'mutasi', $autograph_7, 'PT.MSAL (HO)') .
                                                            "<br><div>{$labelAprv7}</div></td>";
                                                    } else {
                                                        if ($approval_7 == '1') {
                                                            echo "<td class='custom-font pt-3'><i class='bi bi-check-square-fill text-success fs-6'></i><br><div class='custom-font m-0 p-0 mt-1'>{$labelAprv7}</div></td>";
                                                        } elseif ($approval_7 == '0') {
                                                            echo "<td class='custom-font pt-3'><i class='bi bi-hourglass text-warning fs-6'></i><br><div class='custom-font m-0 p-0 mt-1'>{$labelAprv7}</div></td>";
                                                        } else {
                                                            echo "<td class='custom-font pt-3'>Kondisi tidak valid<br><div class='custom-font m-0 p-0 mt-1'>{$labelAprv7}</div></td>";
                                                        }
                                                    }

                                                    if ($row['approval_7'] == "1" && in_array('PT.MSAL (HO)', $userPTs, true)) {
                                                        echo "<td class='custom-font pt-3'>"
                                                            . statusBadge($approval_8, $row, 'approval_8', 'autograph_8', $namaPemeriksa1, 'pemeriksa1', 'mutasi', $autograph_8, 'PT.MSAL (HO)') .
                                                            "<br><div>{$labelAprv8}</div></td>";
                                                    } else {
                                                        if ($approval_8 == '1') {
                                                            echo "<td class='custom-font pt-3'><i class='bi bi-check-square-fill text-success fs-6'></i><br><div class='custom-font m-0 p-0 mt-1'>{$labelAprv8}</div></td>";
                                                        } elseif ($approval_8 == '0') {
                                                            echo "<td class='custom-font pt-3'><i class='bi bi-hourglass text-warning fs-6'></i><br><div class='custom-font m-0 p-0 mt-1'>{$labelAprv8}</div></td>";
                                                        } else {
                                                            echo "<td class='custom-font pt-3'>Kondisi tidak valid<br><div class='custom-font m-0 p-0 mt-1'>{$labelAprv8}</div></td>";
                                                        }
                                                    }

                                                    if ($row['approval_8'] == "1" && in_array('PT.MSAL (HO)', $userPTs, true)) {
                                                        echo "<td class='custom-font pt-3'>"
                                                            . statusBadge($approval_9, $row, 'approval_9', 'autograph_9', $namaPemeriksa2, 'pemeriksa2', 'mutasi', $autograph_9, 'PT.MSAL (HO)') .
                                                            "<br><div>{$labelAprv9}</div></td>";
                                                    } else {
                                                        if ($approval_9 == '1') {
                                                            echo "<td class='custom-font pt-3'><i class='bi bi-check-square-fill text-success fs-6'></i><br><div class='custom-font m-0 p-0 mt-1'>{$labelAprv9}</div></td>";
                                                        } elseif ($approval_9 == '0') {
                                                            echo "<td class='custom-font pt-3'><i class='bi bi-hourglass text-warning fs-6'></i><br><div class='custom-font m-0 p-0 mt-1'>{$labelAprv9}</div></td>";
                                                        } else {
                                                            echo "<td class='custom-font pt-3'>Kondisi tidak valid<br><div class='custom-font m-0 p-0 mt-1'>{$labelAprv9}</div></td>";
                                                        }
                                                    }

                                                    if ($row['approval_9'] == "1" && in_array('PT.MSAL (HO)', $userPTs, true)) {
                                                        echo "<td class='custom-font pt-3'>"
                                                            . statusBadge($approval_10, $row, 'approval_10', 'autograph_10', $namaPenyetujui1, 'penyetujui1', 'mutasi', $autograph_10, 'PT.MSAL (HO)') .
                                                            "<br><div>{$labelAprv10}</div></td>";
                                                    } else {
                                                        if ($approval_10 == '1') {
                                                            echo "<td class='custom-font pt-3'><i class='bi bi-check-square-fill text-success fs-6'></i><br><div class='custom-font m-0 p-0 mt-1'>{$labelAprv10}</div></td>";
                                                        } elseif ($approval_10 == '0') {
                                                            echo "<td class='custom-font pt-3'><i class='bi bi-hourglass text-warning fs-6'></i><br><div class='custom-font m-0 p-0 mt-1'>{$labelAprv10}</div></td>";
                                                        } else {
                                                            echo "<td class='custom-font pt-3'>Kondisi tidak valid<br><div class='custom-font m-0 p-0 mt-1'>{$labelAprv10}</div></td>";
                                                        }
                                                    }

                                                    if ($row['approval_10'] == "1" && in_array('PT.MSAL (HO)', $userPTs, true)) {
                                                        echo "<td class='custom-font pt-3'>"
                                                            . statusBadge($approval_11, $row, 'approval_11', 'autograph_11', $namaPenyetujui2, 'penyetujui2', 'mutasi', $autograph_11, 'PT.MSAL (HO)') .
                                                            "<br><div>{$labelAprv11}</div></td>";
                                                    } else {
                                                        if ($approval_11 == '1') {
                                                            echo "<td class='custom-font pt-3'><i class='bi bi-check-square-fill text-success fs-6'></i><br><div class='custom-font m-0 p-0 mt-1'>{$labelAprv11}</div></td>";
                                                        } elseif ($approval_11 == '0') {
                                                            echo "<td class='custom-font pt-3'><i class='bi bi-hourglass text-warning fs-6'></i><br><div class='custom-font m-0 p-0 mt-1'>{$labelAprv11}</div></td>";
                                                        } else {
                                                            echo "<td class='custom-font pt-3'>Kondisi tidak valid<br><div class='custom-font m-0 p-0 mt-1'>{$labelAprv11}</div></td>";
                                                        }
                                                    }

                                                    echo "<td class='pt-3'>";
                                                    echo "<a class='custom-btn-action btn btn-secondary btn-sm' href='detail_barang_mutasi.php?id=" . intval(isset($row['id']) ? $row['id'] : 0) . "'><i class='bi bi-eye-fill'></i></a> ";
                                                    echo "<a class='custom-btn-action btn btn-primary btn-sm' href='surat_output_mutasi.php?id=" . intval(isset($row['id']) ? $row['id'] : 0) . "' target='_blank'><i class='bi bi-file-earmark-text-fill'></i></a> ";
                                                } elseif ($isSTAsset) {

                                                    $pt_untuk_akses_STA = trim(isset($row['pt']) ? $row['pt'] : '');
                                                    $namaPeminjam = trim(isset($row['peminjam']) ? $row['peminjam'] : '');
                                                    $namaSaksi = trim(isset($row['saksi']) ? $row['saksi'] : '');
                                                    $namaDiketahui = trim(isset($row['diketahui']) ? $row['diketahui'] : '');
                                                    $namaPertama = trim(isset($row['pihak_pertama']) ? $row['pihak_pertama'] : '');

                                                    $isPeminjam = ($namaPeminjam !== '' && $namaPeminjam === $sessionUser);
                                                    $isSaksi = ($namaSaksi !== '' && $namaSaksi === $sessionUser);
                                                    $isDiketahui = ($namaDiketahui !== '' && $namaDiketahui === $sessionUser);
                                                    $isPertama = ($namaPertama !== '' && $namaPertama === $sessionUser);

                                                    $labelAprv1 = $isPeminjam ? "<p class='custom-font m-0 text-primary'>Anda</p>" : htmlspecialchars($namaPeminjam ?: '-', ENT_QUOTES);
                                                    $labelAprv2 = $isSaksi ? "<p class='custom-font m-0 text-primary'>Anda</p>" : htmlspecialchars($namaSaksi ?: '-', ENT_QUOTES);
                                                    $labelAprv3 = $isDiketahui ? "<p class='custom-font m-0 text-primary'>Anda</p>" : htmlspecialchars($namaDiketahui ?: '-', ENT_QUOTES);
                                                    $labelAprv4 = $isPertama ? "<p class='custom-font m-0 text-primary'>Anda</p>" : htmlspecialchars($namaPertama ?: '-', ENT_QUOTES);

                                                    if (in_array($pt_untuk_akses_STA, $userPTs, true)) {
                                                        echo "<td class='custom-font pt-3'>"
                                                            . statusBadge($approval_1, $row, 'approval_1', 'autograph_1', $namaPeminjam, 'peminjam', 'st_asset', $autograph_1, $pt_untuk_akses_STA) .
                                                            "<br><div>{$labelAprv1}</div></td>";
                                                    } else {
                                                        if ($approval_1 == '1') {
                                                            echo "<td class='custom-font pt-3'><i class='bi bi-check-square-fill text-success fs-6'></i><br><div class='custom-font m-0 p-0 mt-1'>{$labelAprv1}</div></td>";
                                                        } elseif ($approval_1 == '0') {
                                                            echo "<td class='custom-font pt-3'><i class='bi bi-hourglass text-warning fs-6'></i><br><div class='custom-font m-0 p-0 mt-1'>{$labelAprv1}</div></td>";
                                                        } else {
                                                            echo "<td class='custom-font pt-3'>Kondisi tidak valid<br><div class='custom-font m-0 p-0 mt-1'>{$labelAprv1}</div></td>";
                                                        }
                                                    }
                                                    if ($row['approval_1'] == "1" && in_array($pt_untuk_akses_STA, $userPTs, true)) {
                                                        echo "<td class='custom-font pt-3'>"
                                                            . statusBadge($approval_2, $row, 'approval_2', 'autograph_2', $namaSaksi, 'saksi', 'st_asset', $autograph_2, $pt_untuk_akses_STA) .
                                                            "<br><div>{$labelAprv2}</div></td>";
                                                    } else {
                                                        if ($approval_2 == '1') {
                                                            echo "<td class='custom-font pt-3'><i class='bi bi-check-square-fill text-success fs-6'></i><br><div class='custom-font m-0 p-0 mt-1'>{$labelAprv2}</div></td>";
                                                        } elseif ($approval_2 == '0') {
                                                            echo "<td class='custom-font pt-3'><i class='bi bi-hourglass text-warning fs-6'></i><br><div class='custom-font m-0 p-0 mt-1'>{$labelAprv2}</div></td>";
                                                        } else {
                                                            echo "<td class='custom-font pt-3'>Kondisi tidak valid<br><div class='custom-font m-0 p-0 mt-1'>{$labelAprv2}</div></td>";
                                                        }
                                                    }

                                                    if ($row['approval_2'] == "1" && in_array($pt_untuk_akses_STA, $userPTs, true)) {
                                                        echo "<td class='custom-font pt-3'>"
                                                            . statusBadge($approval_3, $row, 'approval_3', 'autograph_3', $namaDiketahui, 'diketahui', 'st_asset', $autograph_3, $pt_untuk_akses_STA) .
                                                            "<br><div>{$labelAprv3}</div></td>";
                                                    } else {
                                                        if ($approval_3 == '1') {
                                                            echo "<td class='custom-font pt-3'><i class='bi bi-check-square-fill text-success fs-6'></i><br><div class='custom-font m-0 p-0 mt-1'>{$labelAprv3}</div></td>";
                                                        } elseif ($approval_3 == '0') {
                                                            echo "<td class='custom-font pt-3'><i class='bi bi-hourglass text-warning fs-6'></i><br><div class='custom-font m-0 p-0 mt-1'>{$labelAprv3}</div></td>";
                                                        } else {
                                                            echo "<td class='custom-font pt-3'>Kondisi tidak valid<br><div class='custom-font m-0 p-0 mt-1'>{$labelAprv3}</div></td>";
                                                        }
                                                    }

                                                    if ($row['approval_3'] == "1" && in_array($pt_untuk_akses_STA, $userPTs, true)) {
                                                        echo "<td class='custom-font pt-3'>"
                                                            . statusBadge($approval_4, $row, 'approval_4', 'autograph_4', $namaPertama, 'pihak_pertama', 'st_asset', $autograph_4, $pt_untuk_akses_STA) .
                                                            "<br><div>{$labelAprv4}</div></td>";
                                                    } else {
                                                        if ($approval_4 == '1') {
                                                            echo "<td class='custom-font pt-3'><i class='bi bi-check-square-fill text-success fs-6'></i><br><div class='custom-font m-0 p-0 mt-1'>{$labelAprv4}</div></td>";
                                                        } elseif ($approval_4 == '0') {
                                                            echo "<td class='custom-font pt-3'><i class='bi bi-hourglass text-warning fs-6'></i><br><div class='custom-font m-0 p-0 mt-1'>{$labelAprv4}</div></td>";
                                                        } else {
                                                            echo "<td class='custom-font pt-3'>Kondisi tidak valid<br><div class='custom-font m-0 p-0 mt-1'>{$labelAprv4}</div></td>";
                                                        }
                                                    }

                                                    echo "<td class='pt-3'>";
                                                    echo "<a class='custom-btn-action btn btn-secondary btn-sm' href='detail_barang_serah_terima_asset.php?id=" . intval(isset($row['id']) ? $row['id'] : 0) . "'><i class='bi bi-eye-fill'></i></a> ";
                                                    echo "<a class='custom-btn-action btn btn-primary btn-sm' href='surat_output_serah_terima_asset.php?id=" . intval(isset($row['id']) ? $row['id'] : 0) . "' target='_blank'><i class='bi bi-file-earmark-text-fill'></i></a> ";
                                                } elseif ($isPemutihan) {

                                                    $ptPemutihan = trim(isset($row['pt']) ? $row['pt'] : '');
                                                    $isHOPemutihan = ($ptPemutihan === 'PT.MSAL (HO)');

                                                    if ($isHOPemutihan) {
                                                        $approverList = array(
                                                            array('nama' => trim(isset($row['pembuat']) ? $row['pembuat'] : ''),       'approval_col' => 'approval_1',  'approval_value' => $approval_1,  'autograph_col' => 'autograph_1',  'autograph_value' => $autograph_1,  'pt_otoritas' => 'PT.MSAL (HO)'),
                                                            array('nama' => trim(isset($row['pemeriksa']) ? $row['pemeriksa'] : ''),   'approval_col' => 'approval_2',  'approval_value' => $approval_2,  'autograph_col' => 'autograph_2',  'autograph_value' => $autograph_2,  'pt_otoritas' => 'PT.MSAL (HO)'),
                                                            array('nama' => trim(isset($row['diketahui1']) ? $row['diketahui1'] : ''), 'approval_col' => 'approval_3',  'approval_value' => $approval_3,  'autograph_col' => 'autograph_3',  'autograph_value' => $autograph_3,  'pt_otoritas' => 'PT.MSAL (HO)'),
                                                            array('nama' => trim(isset($row['diketahui2']) ? $row['diketahui2'] : ''), 'approval_col' => 'approval_4',  'approval_value' => $approval_4,  'autograph_col' => 'autograph_4',  'autograph_value' => $autograph_4,  'pt_otoritas' => 'PT.MSAL (HO)'),
                                                            array('nama' => trim(isset($row['diketahui3']) ? $row['diketahui3'] : ''), 'approval_col' => 'approval_5',  'approval_value' => $approval_5,  'autograph_col' => 'autograph_5',  'autograph_value' => $autograph_5,  'pt_otoritas' => 'PT.MSAL (HO)'),
                                                            array('nama' => trim(isset($row['dibukukan']) ? $row['dibukukan'] : ''),   'approval_col' => 'approval_6',  'approval_value' => $approval_6,  'autograph_col' => 'autograph_6',  'autograph_value' => $autograph_6,  'pt_otoritas' => 'PT.MSAL (HO)'),
                                                            array('nama' => trim(isset($row['disetujui1']) ? $row['disetujui1'] : ''), 'approval_col' => 'approval_7',  'approval_value' => $approval_7,  'autograph_col' => 'autograph_7',  'autograph_value' => $autograph_7,  'pt_otoritas' => 'PT.MSAL (HO)'),
                                                            array('nama' => trim(isset($row['disetujui2']) ? $row['disetujui2'] : ''), 'approval_col' => 'approval_8',  'approval_value' => $approval_8,  'autograph_col' => 'autograph_8',  'autograph_value' => $autograph_8,  'pt_otoritas' => 'PT.MSAL (HO)'),
                                                            array('nama' => trim(isset($row['disetujui3']) ? $row['disetujui3'] : ''), 'approval_col' => 'approval_9',  'approval_value' => $approval_9,  'autograph_col' => 'autograph_9',  'autograph_value' => $autograph_9,  'pt_otoritas' => 'PT.MSAL (HO)'),
                                                            array('nama' => '-', 'approval_col' => 'approval_10', 'approval_value' => $approval_10, 'autograph_col' => 'autograph_10', 'autograph_value' => $autograph_10, 'pt_otoritas' => 'PT.MSAL (HO)'),
                                                            array('nama' => '-', 'approval_col' => 'approval_11', 'approval_value' => $approval_11, 'autograph_col' => 'autograph_11', 'autograph_value' => $autograph_11, 'pt_otoritas' => 'PT.MSAL (HO)')
                                                        );
                                                    } else {
                                                        $approverList = array(
                                                            array('nama' => trim(isset($row['pembuat_site']) ? $row['pembuat_site'] : ''),       'approval_col' => 'approval_1',  'approval_value' => $approval_1,  'autograph_col' => 'autograph_1',  'autograph_value' => $autograph_1,  'pt_otoritas' => $ptPemutihan),
                                                            array('nama' => trim(isset($row['pemeriksa_site']) ? $row['pemeriksa_site'] : ''),   'approval_col' => 'approval_2',  'approval_value' => $approval_2,  'autograph_col' => 'autograph_2',  'autograph_value' => $autograph_2,  'pt_otoritas' => $ptPemutihan),
                                                            array('nama' => trim(isset($row['diketahui1_site']) ? $row['diketahui1_site'] : ''), 'approval_col' => 'approval_3',  'approval_value' => $approval_3,  'autograph_col' => 'autograph_3',  'autograph_value' => $autograph_3,  'pt_otoritas' => $ptPemutihan),
                                                            array('nama' => trim(isset($row['disetujui1_site']) ? $row['disetujui1_site'] : ''), 'approval_col' => 'approval_4',  'approval_value' => $approval_4,  'autograph_col' => 'autograph_4',  'autograph_value' => $autograph_4,  'pt_otoritas' => $ptPemutihan),
                                                            array('nama' => trim(isset($row['diketahui2_site']) ? $row['diketahui2_site'] : ''), 'approval_col' => 'approval_5',  'approval_value' => $approval_5,  'autograph_col' => 'autograph_5',  'autograph_value' => $autograph_5,  'pt_otoritas' => 'PT.MSAL (HO)'),
                                                            array('nama' => trim(isset($row['diperiksa_site']) ? $row['diperiksa_site'] : ''),   'approval_col' => 'approval_6',  'approval_value' => $approval_6,  'autograph_col' => 'autograph_6',  'autograph_value' => $autograph_6,  'pt_otoritas' => 'PT.MSAL (HO)'),
                                                            array('nama' => trim(isset($row['dibukukan']) ? $row['dibukukan'] : ''),             'approval_col' => 'approval_7',  'approval_value' => $approval_7,  'autograph_col' => 'autograph_7',  'autograph_value' => $autograph_7,  'pt_otoritas' => 'PT.MSAL (HO)'),
                                                            array('nama' => trim(isset($row['disetujui1']) ? $row['disetujui1'] : ''),           'approval_col' => 'approval_8',  'approval_value' => $approval_8,  'autograph_col' => 'autograph_8',  'autograph_value' => $autograph_8,  'pt_otoritas' => 'PT.MSAL (HO)'),
                                                            array('nama' => trim(isset($row['disetujui2']) ? $row['disetujui2'] : ''),           'approval_col' => 'approval_9',  'approval_value' => $approval_9,  'autograph_col' => 'autograph_9',  'autograph_value' => $autograph_9,  'pt_otoritas' => 'PT.MSAL (HO)'),
                                                            array('nama' => trim(isset($row['disetujui3']) ? $row['disetujui3'] : ''),           'approval_col' => 'approval_10', 'approval_value' => $approval_10, 'autograph_col' => 'autograph_10', 'autograph_value' => $autograph_10, 'pt_otoritas' => 'PT.MSAL (HO)'),
                                                            array('nama' => trim(isset($row['mengetahui_site']) ? $row['mengetahui_site'] : ''), 'approval_col' => 'approval_11', 'approval_value' => $approval_11, 'autograph_col' => 'autograph_11', 'autograph_value' => $autograph_11, 'pt_otoritas' => 'PT.MSAL (HO)')
                                                        );
                                                    }

                                                    $semuaSebelumnyaSelesai = true;
                                                    $nextApprover = null;

                                                    for ($idxPem = 0; $idxPem < count($approverList); $idxPem++) {
                                                        $apvItem = $approverList[$idxPem];
                                                        $namaAktor = trim(isset($apvItem['nama']) ? $apvItem['nama'] : '');

                                                        if ($namaAktor === '' || $namaAktor === '-') {
                                                            echo "<td class='custom-font pt-3'>-</td>";
                                                            continue;
                                                        }

                                                        $labelAktor = ($namaAktor === $sessionUser)
                                                            ? "<p class='custom-font m-0 text-primary'>Anda</p>"
                                                            : htmlspecialchars($namaAktor, ENT_QUOTES);

                                                        if ($semuaSebelumnyaSelesai) {
                                                            $punyaHakPTCell = in_array($apvItem['pt_otoritas'], $userPTs, true);

                                                            if ($punyaHakPTCell) {
                                                                $htmlStatus = statusBadge(
                                                                    $apvItem['approval_value'],
                                                                    $row,
                                                                    $apvItem['approval_col'],
                                                                    $apvItem['autograph_col'],
                                                                    $namaAktor,
                                                                    'approver_' . ($idxPem + 1),
                                                                    'pemutihan',
                                                                    $apvItem['autograph_value'],
                                                                    $apvItem['pt_otoritas']
                                                                );
                                                            } else {
                                                                $htmlStatus = statusIconOnly($apvItem['approval_value']);
                                                            }

                                                            echo "<td class='custom-font pt-3'>"
                                                                . $htmlStatus .
                                                                "<br><div class='custom-font m-0 p-0 mt-1'>{$labelAktor}</div></td>";

                                                            if ((int)$apvItem['approval_value'] === 0 && $nextApprover === null) {
                                                                $nextApprover = $apvItem;
                                                                $semuaSebelumnyaSelesai = false;
                                                            } elseif ((int)$apvItem['approval_value'] === 2) {
                                                                $semuaSebelumnyaSelesai = false;
                                                            }
                                                        } else {
                                                            echo "<td class='custom-font pt-3'><i class='bi bi-hourglass text-warning fs-6'></i><br><div class='custom-font m-0 p-0 mt-1'>{$labelAktor}</div></td>";
                                                        }
                                                    }

                                                    echo "<td class='pt-3'>";
                                                    echo "<a class='custom-btn-action btn btn-secondary btn-sm' href='detail_barang_pemutihan.php?id=" . intval(isset($row['id']) ? $row['id'] : 0) . "'><i class='bi bi-eye-fill'></i></a> ";
                                                    echo "<a class='custom-btn-action btn btn-primary btn-sm' href='surat_output_pemutihan.php?id=" . intval(isset($row['id']) ? $row['id'] : 0) . "' target='_blank'><i class='bi bi-file-earmark-text-fill'></i></a> ";

                                                    $bulanRomawiArr = array(
                                                        1 => 'I',
                                                        2 => 'II',
                                                        3 => 'III',
                                                        4 => 'IV',
                                                        5 => 'V',
                                                        6 => 'VI',
                                                        7 => 'VII',
                                                        8 => 'VIII',
                                                        9 => 'IX',
                                                        10 => 'X',
                                                        11 => 'XI',
                                                        12 => 'XII'
                                                    );

                                                    $bulanRomawi = '';
                                                    $tahun = '';

                                                    if (!empty($tanggal)) {
                                                        $bulanNum = (int)date('n', strtotime($tanggal));
                                                        $tahun = date('Y', strtotime($tanggal));
                                                        $bulanRomawi = isset($bulanRomawiArr[$bulanNum]) ? $bulanRomawiArr[$bulanNum] : '';
                                                    }

                                                    $targetNamaPemutihan = '';
                                                    $targetPTOtoritasPemutihan = '';
                                                    $targetHasAccountPemutihan = false;

                                                    if ($nextApprover && isset($nextApprover['nama'])) {
                                                        $targetNamaPemutihan = trim($nextApprover['nama']);
                                                        $targetPTOtoritasPemutihan = isset($nextApprover['pt_otoritas']) ? $nextApprover['pt_otoritas'] : '';

                                                        if ($targetNamaPemutihan !== '' && $targetNamaPemutihan !== '-') {
                                                            $stmtCekAkunPemutihan = $koneksi->prepare("
                                                            SELECT 1
                                                            FROM akun_akses
                                                            WHERE nama = ?
                                                            LIMIT 1
                                                        ");
                                                            if ($stmtCekAkunPemutihan) {
                                                                $stmtCekAkunPemutihan->bind_param("s", $targetNamaPemutihan);
                                                                $stmtCekAkunPemutihan->execute();
                                                                $stmtCekAkunPemutihan->store_result();
                                                                $targetHasAccountPemutihan = $stmtCekAkunPemutihan->num_rows > 0;
                                                                $stmtCekAkunPemutihan->close();
                                                            }
                                                        }
                                                    }

                                                    $dataAktorBelumApprovePemutihan = ($targetNamaPemutihan !== '' && $targetNamaPemutihan !== '-')
                                                        ? htmlspecialchars(json_encode(array($targetNamaPemutihan)), ENT_QUOTES, 'UTF-8')
                                                        : '[]';

                                                    $punyaHakPTEmailPemutihan = (
                                                        $targetPTOtoritasPemutihan !== ''
                                                        && in_array($targetPTOtoritasPemutihan, $userPTs, true)
                                                    );

                                                    $attrDisabledClassPemutihan = (
                                                        $targetNamaPemutihan !== ''
                                                        && isset($_SESSION['nama'])
                                                        && $_SESSION['nama'] === $targetNamaPemutihan
                                                    ) ? 'disabled' : '';

                                                    if (isset($_SESSION['hak_akses']) && $_SESSION['hak_akses'] === 'Admin' && $punyaHakPTEmailPemutihan) {
                                                        if ($targetNamaPemutihan !== '' && $targetNamaPemutihan !== '-' && $targetHasAccountPemutihan) {
                                                            echo "<a class='custom-btn-action btn btn-dark btn-sm tombolKirimEmail {$attrDisabledClassPemutihan}' 
                                                            href='#' 
                                                            data-id='{$ids}' 
                                                            data-aktor='{$dataAktorBelumApprovePemutihan}'
                                                            data-nomor='" . htmlspecialchars(isset($nomor_ba) ? $nomor_ba : '', ENT_QUOTES) . "'
                                                            data-tanggal='" . htmlspecialchars(isset($tanggal) ? $tanggal : '', ENT_QUOTES) . "'
                                                            data-bulan-romawi='" . htmlspecialchars($bulanRomawi, ENT_QUOTES) . "'
                                                            data-tahun='" . htmlspecialchars($tahun, ENT_QUOTES) . "'
                                                            data-jenis-ba='pemutihan'
                                                            data-jenis-permintaan='approval'
                                                            >
                                                            <i class='bi bi-envelope-at-fill'></i>
                                                        </a>";

                                                            echo "<div class='mt-1 text-muted small'>
                                                            <i class='bi bi-person-check'></i> User yang akan dikirimi email: <b>" . htmlspecialchars($targetNamaPemutihan, ENT_QUOTES) . "</b>
                                                        </div>";
                                                        } elseif ($targetNamaPemutihan !== '' && $targetNamaPemutihan !== '-' && !$targetHasAccountPemutihan) {
                                                            echo "<div class='mt-1 text-warning small'>
                                                            <i class='bi bi-exclamation-triangle'></i> <b>" . htmlspecialchars($targetNamaPemutihan, ENT_QUOTES) . "</b> tidak memiliki akun.
                                                        </div>";
                                                        } else {
                                                            echo "<div class='mt-1 text-success small'>
                                                            <i class='bi bi-check-circle'></i> Semua user sudah approve.
                                                        </div>";
                                                        }
                                                    }
                                                } elseif ($isPengembalian) {

                                                    $namaPengembali = trim(isset($row['pengembali']) ? $row['pengembali'] : '');
                                                    $namaPenerima   = trim(isset($row['penerima']) ? $row['penerima'] : '');
                                                    $namaDiketahui  = trim(isset($row['diketahui']) ? $row['diketahui'] : '');

                                                    $isPengembali = ($namaPengembali !== '' && $namaPengembali === $sessionUser);
                                                    $isPenerima   = ($namaPenerima !== '' && $namaPenerima === $sessionUser);
                                                    $isDiketahui  = ($namaDiketahui !== '' && $namaDiketahui === $sessionUser);

                                                    $labelAprv1 = $isPengembali ? "<p class='custom-font m-0 text-primary'>Anda</p>" : htmlspecialchars($namaPengembali ?: '-', ENT_QUOTES);
                                                    $labelAprv2 = $isPenerima ? "<p class='custom-font m-0 text-primary'>Anda</p>" : htmlspecialchars($namaPenerima ?: '-', ENT_QUOTES);
                                                    $labelAprv3 = $isDiketahui ? "<p class='custom-font m-0 text-primary'>Anda</p>" : htmlspecialchars($namaDiketahui ?: '-', ENT_QUOTES);

                                                    echo "<td class='custom-font pt-3'>"
                                                        . statusBadge($approval_1, $row, 'approval_1', 'autograph_1', $namaPengembali, 'pengembali', 'pengembalian', $autograph_1, $row['pt']) .
                                                        "<br><div class='custom-font m-0 p-0 mt-1'>{$labelAprv1}</div></td>";

                                                    if ($row['approval_1'] == "1") {
                                                        echo "<td class='custom-font pt-3'>"
                                                            . statusBadge($approval_2, $row, 'approval_2', 'autograph_2', $namaPenerima, 'penerima', 'pengembalian', $autograph_2, $row['pt']) .
                                                            "<br><div class='custom-font m-0 p-0 mt-1'>{$labelAprv2}</div></td>";
                                                    } elseif ($namaPenerima != "-") {
                                                        echo "<td class='custom-font pt-3'><i class='bi bi-hourglass text-warning fs-6'></i><br><div class='custom-font m-0 p-0 mt-1'>{$labelAprv2}</div></td>";
                                                    } else {
                                                        echo "<td class='custom-font pt-3'>-<br><div class='custom-font m-0 p-0 mt-1'>{$labelAprv2}</div></td>";
                                                    }

                                                    if ($row['approval_2'] == "1") {
                                                        echo "<td class='custom-font pt-3'>"
                                                            . statusBadge($approval_3, $row, 'approval_3', 'autograph_3', $namaDiketahui, 'diketahui', 'pengembalian', $autograph_3, $row['pt']) .
                                                            "<br><div class='custom-font m-0 p-0 mt-1'>{$labelAprv3}</div></td>";
                                                    } elseif ($namaDiketahui != "-") {
                                                        echo "<td class='custom-font pt-3'><i class='bi bi-hourglass text-warning fs-6'></i><br><div class='custom-font m-0 p-0 mt-1'>{$labelAprv3}</div></td>";
                                                    } else {
                                                        echo "<td class='custom-font pt-3'>-<br><div class='custom-font m-0 p-0 mt-1'>{$labelAprv3}</div></td>";
                                                    }

                                                    echo "<td class='pt-3'>";
                                                    echo "<a class='custom-btn-action btn btn-secondary btn-sm' href='detail_barang_pengembalian.php?id=" . intval(isset($row['id']) ? $row['id'] : 0) . "'><i class='bi bi-eye-fill'></i></a> ";
                                                    echo "<a class='custom-btn-action btn btn-primary btn-sm' href='surat_output_pengembalian.php?id=" . intval(isset($row['id']) ? $row['id'] : 0) . "' target='_blank'><i class='bi bi-file-earmark-text-fill'></i></a> ";
                                                } elseif ($isNotebook) {

                                                    $nama_pertama   = trim(isset($row['pertama']) ? $row['pertama'] : '');
                                                    $nama_kedua     = trim(isset($row['nama_peminjam']) ? $row['nama_peminjam'] : '');
                                                    $nama_saksi     = trim(isset($row['saksi']) ? $row['saksi'] : '');
                                                    $nama_diketahui = trim(isset($row['diketahui']) ? $row['diketahui'] : '');

                                                    $isPertama   = ($nama_pertama !== '' && $nama_pertama === $sessionUser);
                                                    $isKedua     = ($nama_kedua !== '' && $nama_kedua === $sessionUser);
                                                    $isSaksi     = ($nama_saksi !== '' && $nama_saksi === $sessionUser);
                                                    $isDiketahui = ($nama_diketahui !== '' && $nama_diketahui === $sessionUser);

                                                    $jab1 = isset($row['jabatan_pertama']) ? $row['jabatan_pertama'] : '';

                                                    $jab2 = trim(
                                                        (isset($row['jabatan_aprv2']) ? $row['jabatan_aprv2'] : '') . ' ' .
                                                            (isset($row['departemen_aprv2']) ? $row['departemen_aprv2'] : '')
                                                    );

                                                    $jab3 = trim(
                                                        (isset($row['jabatan_aprv3']) ? $row['jabatan_aprv3'] : '') . ' ' .
                                                            (isset($row['departemen_aprv3']) ? $row['departemen_aprv3'] : '')
                                                    );

                                                    $jab4 = trim(
                                                        (isset($row['jabatan_aprv4']) ? $row['jabatan_aprv4'] : '') . ' ' .
                                                            (isset($row['departemen_aprv4']) ? $row['departemen_aprv4'] : '')
                                                    );

                                                    $labelAprv1 = $isPertama ? "<p class='custom-font m-0 text-primary'>Anda</p>" : htmlspecialchars($jab1 ?: '-', ENT_QUOTES);
                                                    $labelAprv2 = $isKedua   ? "<p class='custom-font m-0 text-primary'>Anda</p>" : htmlspecialchars($jab2 ?: '-', ENT_QUOTES);
                                                    $labelAprv3 = $isSaksi  ? "<p class='custom-font m-0 text-primary'>Anda</p>" : htmlspecialchars($jab3 ?: '-', ENT_QUOTES);
                                                    $labelAprv4 = $isDiketahui  ? "<p class='custom-font m-0 text-primary'>Anda</p>" : htmlspecialchars($jab4 ?: '-', ENT_QUOTES);

                                                    echo "<td class='custom-font pt-3'>"
                                                        . statusBadge($approval_1, $row, 'approval_1', 'autograph_1', $nama_pertama, 'pertama', 'notebook', $autograph_1) .
                                                        "<br><div>{$labelAprv1}</div></td>";
                                                    echo "<td class='custom-font pt-3'>"
                                                        . statusBadge($approval_2, $row, 'approval_2', 'autograph_2', $nama_kedua, 'kedua', 'notebook', $autograph_2) .
                                                        "<br><div>{$labelAprv2}</div></td>";
                                                    echo "<td class='custom-font pt-3'>"
                                                        . statusBadge($approval_3, $row, 'approval_3', 'autograph_3', $nama_saksi, 'saksi', 'notebook', $autograph_3) .
                                                        "<br><div>{$labelAprv3}</div></td>";
                                                    echo "<td class='custom-font pt-3'>"
                                                        . statusBadge($approval_4, $row, 'approval_4', 'autograph_4', $nama_diketahui, 'diketahui', 'notebook', $autograph_4) .
                                                        "<br><div>{$labelAprv4}</div></td>";
                                                    echo "<td class='pt-3'>";

                                                    echo "<a class='custom-btn-action btn btn-secondary btn-sm' href='detail_barang_notebook.php?id={$ids}'><i class='bi bi-eye-fill'></i></a> ";
                                                    echo "<a class='custom-btn-action btn btn-primary btn-sm' href='surat_output_notebook.php?id={$ids}' target='_blank'><i class='bi bi-file-earmark-text-fill'></i></a> ";
                                                }

                                                if ($isPengembalian) {

                                                    $styleHide = "";

                                                    if (!$isPengembali && !$isPenerima && !$isDiketahui) {
                                                        $styleHide = "style='display:none;'";
                                                    }

                                                    if ($isPengembali && $approval_1 === 1) $styleHide = "style='display:none;'";
                                                    if ($isPenerima && $approval_2 === 1)   $styleHide = "style='display:none;'";
                                                    if ($isDiketahui && $approval_3 === 1)  $styleHide = "style='display:none;'";

                                                    echo "<a class='custom-btn-action btn btn-success btn-sm js-open-approve btn-disapear d-none' 
                                                    href='approval.php?id=" . intval(isset($row['id']) ? $row['id'] : 0) . "' 
                                                    data-id='" . intval(isset($row['id']) ? $row['id'] : 0) . "' 
                                                    data-nomor='" . htmlspecialchars($nomor_ba, ENT_QUOTES) . "' 
                                                    data-tanggal='" . htmlspecialchars($tanggal, ENT_QUOTES) . "' 
                                                    data-nama-pengembali='" . htmlspecialchars(isset($namaPengembali) ? $namaPengembali : '', ENT_QUOTES) . "' 
                                                    data-nama-penerima='" . htmlspecialchars(isset($namaPenerima) ? $namaPenerima : '', ENT_QUOTES) . "' 
                                                    data-diketahui='" . htmlspecialchars(isset($namaDiketahui) ? $namaDiketahui : '', ENT_QUOTES) . "' 
                                                    data-approval-1='{$approval_1}' 
                                                    data-approval-2='{$approval_2}' 
                                                    data-approval-3='{$approval_3}'
                                                    data-jenis-ba='pengembalian'
                                                    {$styleHide}><i class='bi bi-check-circle'></i></a>";
                                                }
                                                // -- PR -- belum ada pengecekan untuk atasan peminjam, peminjam, dan diketahui
                                                elseif ($isKerusakan) {
                                                    $styleHide = "";

                                                    if (!$isNamaPembuat && !$isNamaPenyetujui) {
                                                        $styleHide = "style='display:none;'";
                                                    }
                                                    if ($isNamaPembuat && $approval_1 === 1) $styleHide = "style='display:none;'";
                                                    if ($isNamaPenyetujui && $approval_2 === 1) $styleHide = "style='display:none;'";

                                                    // --- Ubah tanggal menjadi bulan romawi dan tahun ---
                                                    $bulanRomawiArr = [
                                                        1 => 'I',
                                                        2 => 'II',
                                                        3 => 'III',
                                                        4 => 'IV',
                                                        5 => 'V',
                                                        6 => 'VI',
                                                        7 => 'VII',
                                                        8 => 'VIII',
                                                        9 => 'IX',
                                                        10 => 'X',
                                                        11 => 'XI',
                                                        12 => 'XII'
                                                    ];

                                                    $bulanRomawi = '';
                                                    $tahun = '';

                                                    if (!empty($tanggal)) {
                                                        $bulanNum = (int)date('n', strtotime($tanggal));
                                                        $tahun = date('Y', strtotime($tanggal));
                                                        $bulanRomawi = isset($bulanRomawiArr[$bulanNum]) ? $bulanRomawiArr[$bulanNum] : '';
                                                    }

                                                    echo "<a class='custom-btn-action btn btn-success btn-sm js-open-approve btn-disapear d-none' 
                                                href='approval.php?id=" . (isset($row['id']) ? intval($row['id']) : 0) . "' 
                                                data-id='" . (isset($row['id']) ? intval($row['id']) : 0) . "' 
                                                data-nomor='" . htmlspecialchars(isset($nomor_ba) ? $nomor_ba : '', ENT_QUOTES) . "' 
                                                data-tanggal='" . htmlspecialchars(isset($tanggal) ? $tanggal : '', ENT_QUOTES) . "' 
                                                data-nama-aprv1='" . htmlspecialchars(isset($namaPembuat) ? $namaPembuat : '', ENT_QUOTES) . "' 
                                                data-nama-aprv2='" . htmlspecialchars(isset($namaPenyetujui) ? $namaPenyetujui : '', ENT_QUOTES) . "' 
                                                data-approval-1='{$approval_1}' 
                                                data-approval-2='{$approval_2}' 
                                                data-jenis-ba='kerusakan'
                                                {$styleHide}><i class='bi bi-check-circle'></i></a>";
                                                } elseif ($isNotebook) {
                                                    $styleHide = "";

                                                    if (!$isPertama && !$isKedua && !$isSaksi && !$isDiketahui) {
                                                        $styleHide = "style='display:none;'";
                                                    }

                                                    if ($isPertama && $approval_1 === 1) $styleHide = "style='display:none;'";
                                                    if ($isKedua && $approval_2 === 1) $styleHide = "style='display:none;'";
                                                    if ($isSaksi && $approval_3 === 1) $styleHide = "style='display:none;'";
                                                    if ($isDiketahui && $approval_4 === 1) $styleHide = "style='display:none;'";

                                                    // --- Ubah tanggal menjadi bulan romawi dan tahun ---
                                                    $bulanRomawiArr = [
                                                        1 => 'I',
                                                        2 => 'II',
                                                        3 => 'III',
                                                        4 => 'IV',
                                                        5 => 'V',
                                                        6 => 'VI',
                                                        7 => 'VII',
                                                        8 => 'VIII',
                                                        9 => 'IX',
                                                        10 => 'X',
                                                        11 => 'XI',
                                                        12 => 'XII'
                                                    ];

                                                    $bulanRomawi = '';
                                                    $tahun = '';

                                                    if (!empty($tanggal)) {
                                                        $bulanNum = (int)date('n', strtotime($tanggal));
                                                        $tahun = date('Y', strtotime($tanggal));

                                                        $bulanRomawi = isset($bulanRomawiArr[$bulanNum]) ? $bulanRomawiArr[$bulanNum] : '';
                                                    }

                                                    echo "<a class='custom-btn-action btn btn-success btn-sm js-open-approve btn-disapear d-none' 
                                                    href='approval.php?id=" . (isset($row['id']) ? intval($row['id']) : 0) . "' 
                                                    data-id='" . (isset($row['id']) ? intval($row['id']) : 0) . "' 
                                                    data-nomor='" . htmlspecialchars(isset($nomor_ba) ? $nomor_ba : '', ENT_QUOTES) . "' 
                                                    data-tanggal='" . htmlspecialchars(isset($tanggal) ? $tanggal : '', ENT_QUOTES) . "' 
                                                    data-nama-pertama='" . htmlspecialchars(isset($nama_pertama) ? $nama_pertama : '', ENT_QUOTES) . "' 
                                                    data-nama-kedua='" . htmlspecialchars(isset($nama_kedua) ? $nama_kedua : '', ENT_QUOTES) . "' 
                                                    data-nama-saksi='" . htmlspecialchars(isset($nama_saksi) ? $nama_saksi : '', ENT_QUOTES) . "' 
                                                    data-nama-diketahui='" . htmlspecialchars(isset($nama_diketahui) ? $nama_diketahui : '', ENT_QUOTES) . "' 
                                                    data-approval-1='{$approval_1}' 
                                                    data-approval-2='{$approval_2}' 
                                                    data-approval-3='{$approval_3}' 
                                                    data-approval-4='{$approval_4}' 
                                                    data-jenis-ba='notebook'
                                                    {$styleHide}><i class='bi bi-check-circle'></i></a>";
                                                } elseif ($isMutasi) {
                                                    $styleHide = "";

                                                    if (
                                                        !$isPengirim && !$isPengirim2 &&
                                                        !$isHRDGAPengirim && !$isPenerima1 &&
                                                        !$isPenerima2 && !$isHRDGAPenerima &&
                                                        !$isDiketahui && !$isPemeriksa1 &&
                                                        !$isPemeriksa2 && !$isPenyetujui1 &&
                                                        !$isPenyetujui2
                                                    ) {
                                                        $styleHide = "style='display:none;'";
                                                    }
                                                    if ($isPengirim && $approval_1 === 1) $styleHide = "style='display:none;'";
                                                    if ($isPengirim2 && $approval_2 === 1) $styleHide = "style='display:none;'";
                                                    if ($isHRDGAPengirim && $approval_3 === 1) $styleHide = "style='display:none;'";
                                                    if ($isPenerima1 && $approval_4 === 1) $styleHide = "style='display:none;'";
                                                    if ($isPenerima2 && $approval_5 === 1) $styleHide = "style='display:none;'";
                                                    if ($isHRDGAPenerima && $approval_6 === 1) $styleHide = "style='display:none;'";
                                                    if ($isDiketahui && $approval_7 === 1) $styleHide = "style='display:none;'";
                                                    if ($isPemeriksa1 && $approval_8 === 1) $styleHide = "style='display:none;'";
                                                    if ($isPemeriksa2 && $approval_9 === 1) $styleHide = "style='display:none;'";
                                                    if ($isPenyetujui1 && $approval_10 === 1) $styleHide = "style='display:none;'";
                                                    if ($isPenyetujui2 && $approval_11 === 1) $styleHide = "style='display:none;'";

                                                    // --- Ubah tanggal menjadi bulan romawi dan tahun ---
                                                    $bulanRomawiArr = [
                                                        1 => 'I',
                                                        2 => 'II',
                                                        3 => 'III',
                                                        4 => 'IV',
                                                        5 => 'V',
                                                        6 => 'VI',
                                                        7 => 'VII',
                                                        8 => 'VIII',
                                                        9 => 'IX',
                                                        10 => 'X',
                                                        11 => 'XI',
                                                        12 => 'XII'
                                                    ];

                                                    $bulanRomawi = '';
                                                    $tahun = '';

                                                    if (!empty($tanggal)) {
                                                        $bulanNum = (int)date('n', strtotime($tanggal));
                                                        $tahun = date('Y', strtotime($tanggal));

                                                        $bulanRomawi = isset($bulanRomawiArr[$bulanNum]) ? $bulanRomawiArr[$bulanNum] : '';
                                                    }

                                                    echo "<a class='custom-btn-action btn btn-success btn-sm js-open-approve btn-disapear d-none' 
                                                    href='approval.php?id=" . (isset($row['id']) ? intval($row['id']) : 0) . "' 
                                                    data-id='" . (isset($row['id']) ? intval($row['id']) : 0) . "' 
                                                    data-nomor='" . htmlspecialchars(isset($nomor_ba) ? $nomor_ba : '', ENT_QUOTES) . "' 
                                                    data-tanggal='" . htmlspecialchars(isset($tanggal) ? $tanggal : '', ENT_QUOTES) . "' 
                                                    data-nama-pengirim='" . htmlspecialchars(isset($namaPengirim) ? $namaPengirim : '', ENT_QUOTES) . "' 
                                                    data-nama-diketahui1='" . htmlspecialchars(isset($namaDiketahui1) ? $namaDiketahui1 : '', ENT_QUOTES) . "' 
                                                    data-nama-diketahui2='" . htmlspecialchars(isset($namaDiketahui2) ? $namaDiketahui2 : '', ENT_QUOTES) . "' 
                                                    data-nama-penerima1='" . htmlspecialchars(isset($namaPenerima1) ? $namaPenerima1 : '', ENT_QUOTES) . "' 
                                                    data-nama-penerima2='" . htmlspecialchars(isset($namaPenerima2) ? $namaPenerima2 : '', ENT_QUOTES) . "' 
                                                    data-approval-1='{$approval_1}' 
                                                    data-approval-2='{$approval_2}'
                                                    data-approval-3='{$approval_3}'
                                                    data-approval-4='{$approval_4}'
                                                    data-approval-5='{$approval_5}'
                                                    data-jenis-ba='mutasi'
                                                    {$styleHide}><i class='bi bi-check-circle'></i></a>";
                                                } elseif ($isSTAsset) {
                                                    $styleHide = "";

                                                    if (
                                                        !$isPeminjam && !$isSaksi &&
                                                        !$isDiketahui && !$isPertama
                                                    ) {
                                                        $styleHide = "style='display:none;'";
                                                    }
                                                    if ($isPeminjam && $approval_1 === 1) $styleHide = "style='display:none;'";
                                                    if ($isSaksi && $approval_2 === 1) $styleHide = "style='display:none;'";
                                                    if ($isDiketahui && $approval_3 === 1) $styleHide = "style='display:none;'";
                                                    if ($isPertama && $approval_4 === 1) $styleHide = "style='display:none;'";


                                                    // --- Ubah tanggal menjadi bulan romawi dan tahun ---
                                                    $bulanRomawiArr = [
                                                        1 => 'I',
                                                        2 => 'II',
                                                        3 => 'III',
                                                        4 => 'IV',
                                                        5 => 'V',
                                                        6 => 'VI',
                                                        7 => 'VII',
                                                        8 => 'VIII',
                                                        9 => 'IX',
                                                        10 => 'X',
                                                        11 => 'XI',
                                                        12 => 'XII'
                                                    ];

                                                    $bulanRomawi = '';
                                                    $tahun = '';

                                                    if (!empty($tanggal)) {
                                                        $bulanNum = (int)date('n', strtotime($tanggal));
                                                        $tahun = date('Y', strtotime($tanggal));

                                                        $bulanRomawi = isset($bulanRomawiArr[$bulanNum]) ? $bulanRomawiArr[$bulanNum] : '';
                                                    }

                                                    echo "<a class='custom-btn-action btn btn-success btn-sm js-open-approve btn-disapear d-none' 
                                                    href='approval.php?id=" . (isset($row['id']) ? intval($row['id']) : 0) . "' 
                                                    data-id='" . (isset($row['id']) ? intval($row['id']) : 0) . "' 
                                                    data-nomor='" . htmlspecialchars(isset($nomor_ba) ? $nomor_ba : '', ENT_QUOTES) . "' 
                                                    data-tanggal='" . htmlspecialchars(isset($tanggal) ? $tanggal : '', ENT_QUOTES) . "' 
                                                    data-nama-peminjam='" . htmlspecialchars(isset($namaPeminjam) ? $namaPeminjam : '', ENT_QUOTES) . "' 
                                                    data-nama-saksi='" . htmlspecialchars(isset($namaSaksi) ? $namaSaksi : '', ENT_QUOTES) . "' 
                                                    data-nama-diketahui='" . htmlspecialchars(isset($namaDiketahui) ? $namaDiketahui : '', ENT_QUOTES) . "' 
                                                    data-nama-pertama='" . htmlspecialchars(isset($namaPertama) ? $namaPertama : '', ENT_QUOTES) . "' 
                                                    data-approval-1='{$approval_1}' 
                                                    data-approval-2='{$approval_2}'
                                                    data-approval-3='{$approval_3}'
                                                    data-approval-4='{$approval_4}'
                                                    data-jenis-ba='st_asset'
                                                    {$styleHide}><i class='bi bi-check-circle'></i></a>";
                                                }

                                                if ($isKerusakan) {
                                                    if ($row['pt'] === 'PT.MSAL (HO)') {
                                                        $aktorApproval = [
                                                            ['nama' => $namaPembuat,    'approval' => $approval_1],
                                                            ['nama' => $peminjam,       'approval' => $approval_3],
                                                            ['nama' => $atasanPeminjam, 'approval' => $approval_4],
                                                            ['nama' => $namaDiketahui,  'approval' => $approval_5],
                                                            ['nama' => $namaPenyetujui, 'approval' => $approval_2],
                                                        ];
                                                    } else {
                                                        $aktorApproval = [
                                                            ['nama' => $peminjam,       'approval' => $approval_3],
                                                            ['nama' => $atasanPeminjam, 'approval' => $approval_4],
                                                            ['nama' => $namaDiketahui,  'approval' => $approval_5],
                                                            ['nama' => $namaPenyetujui, 'approval' => $approval_2],
                                                            ['nama' => $namaPembuat,    'approval' => $approval_1],
                                                        ];
                                                    }

                                                    // Aktor valid (tidak kosong / dash)
                                                    $aktorValid = array_filter($aktorApproval, function ($aktor) {
                                                        return !empty($aktor['nama']) && $aktor['nama'] !== '-';
                                                    });

                                                    // Aktor yang belum approve
                                                    $aktorBelumApprove = array_filter($aktorValid, function ($aktor) {
                                                        return (int)$aktor['approval'] === 0;
                                                    });

                                                    $sessionNama = isset($_SESSION['nama']) ? $_SESSION['nama'] : null;

                                                    $aktorBelumApprove = array_filter($aktorBelumApprove, function ($aktor) use ($sessionNama) {
                                                        if ($sessionNama === null) {
                                                            return true;
                                                        }
                                                        if (!isset($aktor['nama'])) {
                                                            return true;
                                                        }
                                                        return $aktor['nama'] !== $sessionNama;
                                                    });

                                                    // Ambil hanya satu aktor pertama dari urutan yang ditentukan di atas
                                                    $aktorPertama = reset($aktorBelumApprove); // Mengambil elemen pertama dari hasil filter

                                                    $aktorTerdaftar = false;

                                                    if ($aktorPertama && !empty($aktorPertama['nama'])) {
                                                        $stmtCekAkun = $koneksi->prepare("
                                                        SELECT 1 
                                                        FROM akun_akses 
                                                        WHERE nama = ? 
                                                        LIMIT 1
                                                    ");
                                                        $stmtCekAkun->bind_param("s", $aktorPertama['nama']);
                                                        $stmtCekAkun->execute();
                                                        $stmtCekAkun->store_result();

                                                        $aktorTerdaftar = $stmtCekAkun->num_rows > 0;

                                                        $stmtCekAkun->close();
                                                    }

                                                    $dataAktorBelumApprove = $aktorPertama
                                                        ? htmlspecialchars(json_encode([$aktorPertama['nama']]), ENT_QUOTES, 'UTF-8')
                                                        : '[]';

                                                    // $dataAktorBelumApprove = htmlspecialchars(
                                                    //     json_encode(array_column($aktorBelumApprove, 'nama')),
                                                    //     ENT_QUOTES, 'UTF-8'
                                                    // );

                                                    // Cari aktor pertama yang belum approve TANPA filter session
                                                    $aktorBelumApproveAsli = array_filter($aktorApproval, function ($aktor) {
                                                        return !empty($aktor['nama'])
                                                            && $aktor['nama'] !== '-'
                                                            && (int)$aktor['approval'] === 0;
                                                    });

                                                    $aktorPertamaAsli = reset($aktorBelumApproveAsli);

                                                    // Flag: apakah aktor pertama adalah user login
                                                    $aktorPertamaAdalahSession = (
                                                        $aktorPertamaAsli
                                                        && $sessionNama
                                                        && $aktorPertamaAsli['nama'] === $sessionNama
                                                    );

                                                    $attrDisabledClass = $aktorPertamaAdalahSession ? 'disabled' : '';

                                                    // Tombol muncul kalau masih ada aktor valid yang belum approve
                                                    $styleHide = (
                                                        count($aktorBelumApprove) === 0
                                                        || !$aktorPertama
                                                        || !$aktorTerdaftar
                                                    ) ? "style='display:none;'" : "";

                                                    $ptRow = trim(isset($row['pt']) ? $row['pt'] : '');
                                                    if ($_SESSION['hak_akses'] === 'Admin' && in_array($ptRow, $userPTs, true)):
                                                        echo "<a class='custom-btn-action btn btn-dark btn-sm tombolKirimEmail {$attrDisabledClass}' 
                                                        href='#' 
                                                        data-id='{$ids}' 
                                                        data-aktor='{$dataAktorBelumApprove}' {$styleHide}
                                                        data-nomor='" . htmlspecialchars(isset($nomor_ba) ? $nomor_ba : '', ENT_QUOTES) . "'
                                                        data-tanggal='" . htmlspecialchars(isset($tanggal) ? $tanggal : '', ENT_QUOTES) . "'
                                                        data-bulan-romawi='" . htmlspecialchars($bulanRomawi, ENT_QUOTES) . "'
                                                        data-tahun='" . htmlspecialchars($tahun, ENT_QUOTES) . "'
                                                        data-jenis-ba='kerusakan'
                                                        data-jenis-permintaan='approval'
                                                        >
                                                        <i class='bi bi-envelope-at-fill'></i>
                                                    </a>";
                                                        if ($aktorPertama && $aktorTerdaftar) :
                                                            echo "<div class='mt-1 text-muted small'>
                                                            <i class='bi bi-person-check'></i> User yang akan dikirimi email: <b>{$aktorPertama['nama']}</b>
                                                        </div>";
                                                        elseif ($aktorPertama && !$aktorTerdaftar) :
                                                            echo "<div class='mt-1 text-warning small'>
                                                            <i class='bi bi-exclamation-triangle'></i> <b>{$aktorPertama['nama']}</b> tidak memiliki akun.
                                                        </div>";
                                                        else :
                                                            echo "<div class='mt-1 text-success small'>
                                                            <i class='bi bi-check-circle'></i> Semua user sudah approve.
                                                        </div>";
                                                        endif;
                                                    endif;
                                                } elseif ($isNotebook) {

                                                    $aktorApproval = [
                                                        ['nama' => $nama_pertama,   'approval' => $approval_1],
                                                        ['nama' => $nama_kedua,     'approval' => $approval_2],
                                                        ['nama' => $nama_saksi,     'approval' => $approval_3],
                                                        ['nama' => $nama_diketahui, 'approval' => $approval_4],
                                                    ];

                                                    // Aktor valid (tidak kosong / dash)
                                                    $aktorValid = array_filter($aktorApproval, function ($aktor) {
                                                        return !empty($aktor['nama']) && $aktor['nama'] !== '-';
                                                    });

                                                    // Aktor yang belum approve
                                                    $aktorBelumApprove = array_filter($aktorValid, function ($aktor) {
                                                        return (int)$aktor['approval'] === 0;
                                                    });

                                                    $sessionNama = isset($_SESSION['nama']) ? $_SESSION['nama'] : null;

                                                    $aktorBelumApprove = array_filter($aktorBelumApprove, function ($aktor) use ($sessionNama) {
                                                        if ($sessionNama === null) {
                                                            return true;
                                                        }
                                                        if (!isset($aktor['nama'])) {
                                                            return true;
                                                        }
                                                        return $aktor['nama'] !== $sessionNama;
                                                    });

                                                    // Encode JSON untuk dikirim via atribut
                                                    $dataAktorBelumApprove = htmlspecialchars(
                                                        json_encode(array_column($aktorBelumApprove, 'nama')),
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    );

                                                    // Tombol muncul kalau masih ada aktor valid yang belum approve
                                                    $styleHide = (count($aktorBelumApprove) === 0) ? "style='display:none;'" : "";

                                                    if ($_SESSION['hak_akses'] === 'Admin'):
                                                        echo "<a class='custom-btn-action btn btn-dark btn-sm tombolKirimEmail' 
                                                        href='#' 
                                                        data-id='{$ids}' 
                                                        data-aktor='{$dataAktorBelumApprove}' {$styleHide}
                                                        data-nomor='" . htmlspecialchars(isset($nomor_ba) ? $nomor_ba : '', ENT_QUOTES) . "'
                                                        data-tanggal='" . htmlspecialchars(isset($tanggal) ? $tanggal : '', ENT_QUOTES) . "'
                                                        data-bulan-romawi='" . htmlspecialchars($bulanRomawi, ENT_QUOTES) . "'
                                                        data-tahun='" . htmlspecialchars($tahun, ENT_QUOTES) . "'
                                                        data-jenis-ba='notebook'
                                                        data-jenis-permintaan='approval'
                                                        >
                                                        <i class='bi bi-envelope-at-fill'></i>
                                                    </a>";
                                                    endif;
                                                } elseif ($isMutasi) {

                                                    // =========================
                                                    // 1) Data PT asal/tujuan row
                                                    // =========================
                                                    $ptAsalRow   = isset($row['pt_asal']) ? trim($row['pt_asal']) : '';
                                                    $ptTujuanRow = isset($row['pt_tujuan']) ? trim($row['pt_tujuan']) : '';

                                                    // =========================
                                                    // 2) Bulan Romawi + Tahun (buat atribut tombol email)
                                                    // =========================
                                                    $bulanRomawiArr = array(
                                                        1 => 'I',
                                                        2 => 'II',
                                                        3 => 'III',
                                                        4 => 'IV',
                                                        5 => 'V',
                                                        6 => 'VI',
                                                        7 => 'VII',
                                                        8 => 'VIII',
                                                        9 => 'IX',
                                                        10 => 'X',
                                                        11 => 'XI',
                                                        12 => 'XII'
                                                    );

                                                    $bulanRomawi = '';
                                                    $tahun = '';
                                                    if (!empty($tanggal)) {
                                                        $bulanNum = (int)date('n', strtotime($tanggal));
                                                        $tahun    = date('Y', strtotime($tanggal));
                                                        $bulanRomawi = isset($bulanRomawiArr[$bulanNum]) ? $bulanRomawiArr[$bulanNum] : '';
                                                    }

                                                    // =========================
                                                    // 3) Mapping aktor + idx approval (1..11)
                                                    // =========================
                                                    $aktorApproval = array(
                                                        array('idx' => 1,  'nama' => $namaPengirim,       'approval' => (int)$approval_1),
                                                        array('idx' => 2,  'nama' => $namaPengirim2,      'approval' => (int)$approval_2),
                                                        array('idx' => 3,  'nama' => $namaHRDGAPengirim,  'approval' => (int)$approval_3),
                                                        array('idx' => 4,  'nama' => $namaPenerima1,      'approval' => (int)$approval_4),
                                                        array('idx' => 5,  'nama' => $namaPenerima2,      'approval' => (int)$approval_5),
                                                        array('idx' => 6,  'nama' => $namaHRDGAPenerima,  'approval' => (int)$approval_6),
                                                        array('idx' => 7,  'nama' => $namaDiketahui,      'approval' => (int)$approval_7),
                                                        array('idx' => 8,  'nama' => $namaPemeriksa1,     'approval' => (int)$approval_8),
                                                        array('idx' => 9,  'nama' => $namaPemeriksa2,     'approval' => (int)$approval_9),
                                                        array('idx' => 10, 'nama' => $namaPenyetujui1,    'approval' => (int)$approval_10),
                                                        array('idx' => 11, 'nama' => $namaPenyetujui2,    'approval' => (int)$approval_11),
                                                    );

                                                    // =========================
                                                    // 4) Filter aktor valid (nama tidak kosong / bukan "-")
                                                    // =========================
                                                    $aktorValid = array();
                                                    foreach ($aktorApproval as $a) {
                                                        $nm = isset($a['nama']) ? trim($a['nama']) : '';
                                                        if ($nm !== '' && $nm !== '-') {
                                                            $aktorValid[] = $a;
                                                        }
                                                    }

                                                    // =========================
                                                    // 5) Cari aktor pending pertama (idx terkecil yang approval=0)
                                                    // =========================
                                                    $aktorPertamaAsli = null;
                                                    foreach ($aktorValid as $a) {
                                                        if ((int)$a['approval'] === 0) {
                                                            $aktorPertamaAsli = $a;
                                                            break;
                                                        }
                                                    }

                                                    // Kalau semua sudah approve / tidak ada aktor valid pending => tidak usah tampilkan tombol
                                                    $styleHide = ($aktorPertamaAsli === null) ? "style='display:none;'" : "";

                                                    // =========================
                                                    // 6) Tentukan otoritas PT berdasarkan idx pending
                                                    //    1-3  => PT ASAL
                                                    //    4-6  => PT TUJUAN
                                                    //    7-11 => HO (PT.MSAL (HO))
                                                    // =========================
                                                    $otoritasPT = '';
                                                    $nextIdx = ($aktorPertamaAsli !== null && isset($aktorPertamaAsli['idx'])) ? (int)$aktorPertamaAsli['idx'] : 0;

                                                    if ($nextIdx >= 1 && $nextIdx <= 3) {
                                                        $otoritasPT = $ptAsalRow;
                                                    } elseif ($nextIdx >= 4 && $nextIdx <= 6) {
                                                        $otoritasPT = $ptTujuanRow;
                                                    } elseif ($nextIdx >= 7 && $nextIdx <= 11) {
                                                        $otoritasPT = 'PT.MSAL (HO)';
                                                    }

                                                    // =========================
                                                    // 7) Tombol email hanya boleh muncul kalau:
                                                    //    - user Admin
                                                    //    - user punya PT otoritas (in_array di $userPTs)
                                                    // =========================
                                                    $isAdmin = (isset($_SESSION['hak_akses']) && $_SESSION['hak_akses'] === 'Admin');
                                                    $punyaHakPT = false;
                                                    if ($otoritasPT !== '' && isset($userPTs) && is_array($userPTs)) {
                                                        $punyaHakPT = in_array($otoritasPT, $userPTs, true);
                                                    }

                                                    // =========================
                                                    // 8) Siapkan target email (aktor pertama pending)
                                                    // =========================
                                                    $aktorTarget = $aktorPertamaAsli;
                                                    $aktorNamaTarget = ($aktorTarget !== null && isset($aktorTarget['nama'])) ? $aktorTarget['nama'] : '';

                                                    $dataAktorBelumApprove = ($aktorNamaTarget !== '')
                                                        ? htmlspecialchars(json_encode(array($aktorNamaTarget)), ENT_QUOTES, 'UTF-8')
                                                        : '[]';

                                                    // Disable kalau admin mau kirim ke dirinya sendiri (tapi tombol tetap ada)
                                                    $sessionNama = isset($_SESSION['nama']) ? $_SESSION['nama'] : '';
                                                    $attrDisabledClass = ($aktorNamaTarget !== '' && $sessionNama !== '' && $aktorNamaTarget === $sessionNama) ? 'disabled' : '';

                                                    // =========================
                                                    // 9) Render tombol email
                                                    // =========================
                                                    if ($isAdmin && $punyaHakPT) {

                                                        echo "<a class='custom-btn-action btn btn-dark btn-sm tombolKirimEmail {$attrDisabledClass}' 
                                                            href='#'
                                                            data-id='{$ids}'
                                                            data-aktor='{$dataAktorBelumApprove}' {$styleHide}
                                                            data-nomor='" . htmlspecialchars(isset($nomor_ba) ? $nomor_ba : '', ENT_QUOTES) . "'
                                                            data-tanggal='" . htmlspecialchars(isset($tanggal) ? $tanggal : '', ENT_QUOTES) . "'
                                                            data-bulan-romawi='" . htmlspecialchars($bulanRomawi, ENT_QUOTES) . "'
                                                            data-tahun='" . htmlspecialchars($tahun, ENT_QUOTES) . "'
                                                            data-jenis-ba='mutasi'
                                                            data-jenis-permintaan='approval'
                                                        >
                                                        <i class='bi bi-envelope-at-fill'></i>
                                                    </a>";

                                                        // Info target (biar jelas siapa yg akan dikirim)
                                                        if ($aktorNamaTarget !== '') {
                                                            echo "<div class='mt-1 text-muted small'>
                                                                <i class='bi bi-person-check'></i> User yang akan dikirimi email: <b>" . htmlspecialchars($aktorNamaTarget, ENT_QUOTES) . "</b>
                                                            </div>";
                                                        } else {
                                                            echo "<div class='mt-1 text-success small'>
                                                                <i class='bi bi-check-circle'></i> Semua user sudah approve.
                                                            </div>";
                                                        }
                                                    }
                                                } elseif ($isSTAsset) {

                                                    $aktorApproval = [
                                                        ['nama' => $namaPeminjam,   'approval' => $approval_1],
                                                        ['nama' => $namaSaksi, 'approval' => $approval_2],
                                                        ['nama' => $namaDiketahui, 'approval' => $approval_3],
                                                        ['nama' => $namaPertama,  'approval' => $approval_4]
                                                    ];


                                                    // Aktor valid (tidak kosong / dash)
                                                    $aktorValid = array_filter($aktorApproval, function ($aktor) {
                                                        return !empty($aktor['nama']) && $aktor['nama'] !== '-';
                                                    });

                                                    // Aktor yang belum approve
                                                    $aktorBelumApprove = array_filter($aktorValid, function ($aktor) {
                                                        return (int)$aktor['approval'] === 0;
                                                    });

                                                    $sessionNama = isset($_SESSION['nama']) ? $_SESSION['nama'] : null;

                                                    $aktorBelumApprove = array_filter($aktorBelumApprove, function ($aktor) use ($sessionNama) {
                                                        if ($sessionNama === null) {
                                                            return true;
                                                        }
                                                        if (!isset($aktor['nama'])) {
                                                            return true;
                                                        }
                                                        return $aktor['nama'] !== $sessionNama;
                                                    });

                                                    // Ambil hanya satu aktor pertama dari urutan yang ditentukan di atas
                                                    $aktorPertama = reset($aktorBelumApprove); // Mengambil elemen pertama dari hasil filter

                                                    $dataAktorBelumApprove = $aktorPertama
                                                        ? htmlspecialchars(json_encode([$aktorPertama['nama']]), ENT_QUOTES, 'UTF-8')
                                                        : '[]';

                                                    $styleHide = (count($aktorBelumApprove) === 0) ? "style='display:none;'" : "";

                                                    if ($_SESSION['hak_akses'] === 'Admin'):
                                                        echo "<a class='custom-btn-action btn btn-dark btn-sm tombolKirimEmail' 
                                                        href='#' 
                                                        data-id='{$ids}' 
                                                        data-aktor='{$dataAktorBelumApprove}' {$styleHide}
                                                        data-nomor='" . htmlspecialchars(isset($nomor_ba) ? $nomor_ba : '', ENT_QUOTES) . "'
                                                        data-tanggal='" . htmlspecialchars(isset($tanggal) ? $tanggal : '', ENT_QUOTES) . "'
                                                        data-bulan-romawi='" . htmlspecialchars($bulanRomawi, ENT_QUOTES) . "'
                                                        data-tahun='" . htmlspecialchars($tahun, ENT_QUOTES) . "'
                                                        data-jenis-ba='st_asset'
                                                        data-jenis-permintaan='approval'
                                                        >
                                                        <i class='bi bi-envelope-at-fill'></i>
                                                    </a>";
                                                        if ($aktorPertama) :
                                                            echo "<div class='mt-1 text-muted small'>
                                                    <i class='bi bi-person-check'></i> User yang akan dikirimi email: <b>{$aktorPertama['nama']}</b>
                                                </div>";
                                                        else :
                                                            echo "<div class='mt-1 text-success small'>
                                                    <i class='bi bi-check-circle'></i> Semua user sudah approve.
                                                </div>";
                                                        endif;
                                                    endif;
                                                } elseif ($isPengembalian) {

                                                    $aktorApproval = array(
                                                        array('nama' => $namaPengembali, 'approval' => $approval_1),
                                                        array('nama' => $namaPenerima,   'approval' => $approval_2),
                                                        array('nama' => $namaDiketahui,  'approval' => $approval_3)
                                                    );

                                                    $aktorValid = array_filter($aktorApproval, function ($aktor) {
                                                        return !empty($aktor['nama']) && $aktor['nama'] !== '-';
                                                    });

                                                    $aktorBelumApprove = array_filter($aktorValid, function ($aktor) {
                                                        return (int)$aktor['approval'] === 0;
                                                    });

                                                    $sessionNama = isset($_SESSION['nama']) ? $_SESSION['nama'] : null;

                                                    $aktorBelumApprove = array_filter($aktorBelumApprove, function ($aktor) use ($sessionNama) {
                                                        if ($sessionNama === null) {
                                                            return true;
                                                        }
                                                        if (!isset($aktor['nama'])) {
                                                            return true;
                                                        }
                                                        return $aktor['nama'] !== $sessionNama;
                                                    });

                                                    $aktorPertama = reset($aktorBelumApprove);

                                                    $aktorTerdaftar = false;
                                                    if ($aktorPertama && !empty($aktorPertama['nama'])) {
                                                        $stmtCekAkunPengembalian = $koneksi->prepare("
                                                        SELECT 1
                                                        FROM akun_akses
                                                        WHERE nama = ?
                                                        LIMIT 1
                                                    ");
                                                        if ($stmtCekAkunPengembalian) {
                                                            $stmtCekAkunPengembalian->bind_param("s", $aktorPertama['nama']);
                                                            $stmtCekAkunPengembalian->execute();
                                                            $stmtCekAkunPengembalian->store_result();
                                                            $aktorTerdaftar = $stmtCekAkunPengembalian->num_rows > 0;
                                                            $stmtCekAkunPengembalian->close();
                                                        }
                                                    }

                                                    $dataAktorBelumApprove = $aktorPertama
                                                        ? htmlspecialchars(json_encode(array($aktorPertama['nama'])), ENT_QUOTES, 'UTF-8')
                                                        : '[]';

                                                    $aktorBelumApproveAsli = array_filter($aktorApproval, function ($aktor) {
                                                        return !empty($aktor['nama'])
                                                            && $aktor['nama'] !== '-'
                                                            && (int)$aktor['approval'] === 0;
                                                    });

                                                    $aktorPertamaAsli = reset($aktorBelumApproveAsli);

                                                    $aktorPertamaAdalahSession = (
                                                        $aktorPertamaAsli
                                                        && $sessionNama
                                                        && $aktorPertamaAsli['nama'] === $sessionNama
                                                    );

                                                    $attrDisabledClass = $aktorPertamaAdalahSession ? 'disabled' : '';

                                                    $styleHide = (
                                                        count($aktorBelumApprove) === 0
                                                        || !$aktorPertama
                                                        || !$aktorTerdaftar
                                                    ) ? "style='display:none;'" : "";

                                                    $bulanRomawiArr = array(
                                                        1 => 'I',
                                                        2 => 'II',
                                                        3 => 'III',
                                                        4 => 'IV',
                                                        5 => 'V',
                                                        6 => 'VI',
                                                        7 => 'VII',
                                                        8 => 'VIII',
                                                        9 => 'IX',
                                                        10 => 'X',
                                                        11 => 'XI',
                                                        12 => 'XII'
                                                    );

                                                    $bulanRomawi = '';
                                                    $tahun = '';

                                                    if (!empty($tanggal)) {
                                                        $bulanNum = (int)date('n', strtotime($tanggal));
                                                        $tahun = date('Y', strtotime($tanggal));
                                                        $bulanRomawi = isset($bulanRomawiArr[$bulanNum]) ? $bulanRomawiArr[$bulanNum] : '';
                                                    }

                                                    $ptRow = trim(isset($row['pt']) ? $row['pt'] : '');
                                                    if ($_SESSION['hak_akses'] === 'Admin' && in_array($ptRow, $userPTs, true)) :
                                                        echo "<a class='custom-btn-action btn btn-dark btn-sm tombolKirimEmail {$attrDisabledClass}' 
                                                        href='#' 
                                                        data-id='{$ids}' 
                                                        data-aktor='{$dataAktorBelumApprove}' {$styleHide}
                                                        data-nomor='" . htmlspecialchars(isset($nomor_ba) ? $nomor_ba : '', ENT_QUOTES) . "'
                                                        data-tanggal='" . htmlspecialchars(isset($tanggal) ? $tanggal : '', ENT_QUOTES) . "'
                                                        data-bulan-romawi='" . htmlspecialchars($bulanRomawi, ENT_QUOTES) . "'
                                                        data-tahun='" . htmlspecialchars($tahun, ENT_QUOTES) . "'
                                                        data-jenis-ba='pengembalian'
                                                        data-jenis-permintaan='approval'
                                                        >
                                                        <i class='bi bi-envelope-at-fill'></i>
                                                    </a>";

                                                        if ($aktorPertama && $aktorTerdaftar) :
                                                            echo "<div class='mt-1 text-muted small'>
                                                            <i class='bi bi-person-check'></i> User yang akan dikirimi email: <b>{$aktorPertama['nama']}</b>
                                                        </div>";
                                                        elseif ($aktorPertama && !$aktorTerdaftar) :
                                                            echo "<div class='mt-1 text-warning small'>
                                                            <i class='bi bi-exclamation-triangle'></i> <b>{$aktorPertama['nama']}</b> tidak memiliki akun.
                                                        </div>";
                                                        else :
                                                            echo "<div class='mt-1 text-success small'>
                                                            <i class='bi bi-check-circle'></i> Semua user sudah approve.
                                                        </div>";
                                                        endif;
                                                    endif;
                                                }
                                            }
                                        }

                                        echo "</td>";
                                        echo "</tr>";
                                        $no++;
                                    }
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>

                </div>

                <div id="popupBoxAutograph" class="popup-box custom-popup-autograph position-absolute bg-white rounded-1 flex-column justify-content-start align-items-center p-2">
                    <div class="w-100 d-flex justify-content-between mb-2 p-0" style="height: max-content;">
                        <h4 class="m-0 p-0">Tanda tangan</h4>
                        <a id="tombolClosePopupAutograph" class='custom-btn-action btn btn-danger btn-sm' href='#'><i class="bi bi-x-lg"></i></a>
                    </div>
                    <div class="w-100 d-flex justify-content-start d-none">
                        <p class="m-0 p-0" style="font-weight: 500;">Anda akan menandatangani BA dengan informasi berikut :<br>
                            <span id="idBAAutograph">-</span> <span id="approvalBAAutograph">-</span> <span id="autographBAAutograph">-</span><br>
                            Nama: <span id="namaBAAutograph">-</span><br>
                            Peran: <span id="peranBAAutograph">-</span><br>
                            <!-- Data TTD: <span id="pictBAAutograph">-</span><br> -->
                            Data TTD: <img id="userBAAutograph" src="" alt="TTD User" style="max-width:150px; border:1px solid #ccc;">
                            <br>
                            Data BA: <span id="jenisBAAutograph">-</span>/<span id="nomorBAAutograph">-</span>/MIS/<span id="periodeBAAutograph">-</span>/<span id="tahunBAAutograph">-</span>
                        </p>
                    </div>
                    <input type="hidden" name="id" id="inputIdBAAutograph">
                    <input type="hidden" name="approvalCol" id="inputApprovalBAAutograph">
                    <input type="hidden" name="autographCol" id="inputAutographBAAutograph">
                    <input type="hidden" name="nama" id="inputNamaBAAutograph">
                    <input type="hidden" name="peran" id="inputPeranBAAutograph">
                    <input type="hidden" name="jenis" id="inputJenisBAAutograph">
                    <input type="hidden" name="nomor" id="inputNomorBAAutograph">
                    <input type="hidden" name="periode" id="inputPeriodeBAAutograph">
                    <input type="hidden" name="tahun" id="inputTahunBAAutograph">
                    <div class="autograph-container p-3 pb-1">
                        <canvas id="signature" width="500" height="200" style="border: 1px solid black; border-radius: 8px;"></canvas>
                        <div class="d-flex justify-content-between mt-2">
                            <button id="clear" class="btn btn-warning btn-sm">Bersihkan</button>
                            <!-- Sistem Load TTD -->
                            <!-- <button id="load" class="btn btn-primary btn-sm"><i class="bi bi-arrow-clockwise"></i></button> -->
                            <button id="save" class="btn btn-success btn-sm">Simpan</button>
                        </div>
                    </div>
                    <!-- <div id="instant" class="w-100 flex-column align-items-center mb-2 p-0" style="height: max-content;">
                        <p class="m-0 p-0" style="font-size: 12px;">Anda memiliki tanda tangan tersimpan</p>
                        <button id="setujui" class="btn btn-success btn-sm w-50"> Setujui instan </button>
                    </div> -->
                </div>

                <div id="popupBoxEmail"
                    class="popup-box custom-popup3 position-absolute bg-white rounded-1 flex-column justify-content-start align-items-center p-2"
                    style="height: max-content;align-self: center;z-index: 999;width:30%;min-width:500px;left:35.5%;top:30vh;">

                    <div class="w-100 d-flex justify-content-between mb-2">
                        <h4 class="m-0 p-0">Kirim Email Permintaan Persetujuan</h4>
                        <a id="tombolClosePopupEmail" class="custom-btn-action btn btn-danger btn-sm" href="#"><i class="bi bi-x-lg"></i></a>
                    </div>

                    <div id="popupContentEmail" class="w-100">
                        <!-- Info Berita Acara ditampilkan di sini -->
                        <p id="popupInfoBA" class="mb-2" style="font-weight: 500;"></p>
                        <p id="popupInfoBA2">Cek info:</p>

                        <!-- Hidden input untuk semua data yang akan dikirim -->
                        <form id="formKirimEmail" action="proses_kirim_email.php" method="POST">
                            <input type="hidden" id="popupRowIdEmail" name="row_id_email" value="">
                            <input type="hidden" id="aktorEmailHidden" name="aktorEmailHidden" value="">
                            <input type="hidden" id="dataNomor" name="data_nomor" value="">
                            <input type="hidden" id="dataTanggal" name="data_tanggal" value="">
                            <input type="hidden" id="dataBulanRomawi" name="data_bulan_romawi" value="">
                            <input type="hidden" id="dataTahun" name="data_tahun" value="">
                            <input type="hidden" id="dataJenisBA" name="data_jenis_ba" value="">
                            <input type="hidden" id="dataJenisPermintaan" name="data_permintaan" value="">
                            <input type="hidden" id="dataNamaPeminta" name="data_nama_peminta" value="">
                        </form>
                    </div>

                    <div class="w-100 d-flex justify-content-start">
                        <p class="m-0 p-0 mb-3" style="font-weight: 500;">Kirim email ke :</p>
                        <ul id="listAktorEmail" class="m-0 p-0 ms-4" style="list-style-type: disc;"></ul>
                    </div>

                    <!-- Progress bar (disembunyikan awalnya) -->
                    <div id="progressContainer" class="w-100 my-2" style="display:none;">
                        <div class="progress" style="height: 20px; background-color: #f0f0f0; border-radius: 10px;">
                            <div id="progressBar" class="progress-bar bg-success" role="progressbar"
                                style="width: 0%; border-radius: 10px; transition: width 0.3s;">
                                0%
                            </div>
                        </div>
                    </div>

                    <div class="w-100 d-flex justify-content-end">
                        <a href="#" class="btn btn-secondary" id="tombolClosePopupKirimEmail">Batal</a>
                        <!-- Tombol kirim akan submit form -->
                        <button type="submit" form="formKirimEmail" class="btn btn-primary ms-2">Kirim</button>
                    </div>

                </div>

                <div id="popupBoxAprv" class="popup-box custom-popup2 position-absolute bg-white rounded-1 flex-column justify-content-start align-items-center p-5"
                    style="height: max-content;align-self: center;z-index: 999;width:30%;min-width:500px;left:35.5%;top:30vh;">
                    <div class="w-100 d-flex justify-content-start mb-2" style="height: max-content;">
                        <h6 class="m-0 p-0">BA (Jenis BA) Nomor (Nomor BA) Periode (Bulan Romawi)</h6>

                    </div>
                    <div id="popupContent" class="w-100">
                        <p class="mb-2">ID Data: <span id="popupDataId"></span></p>
                        <input type="hidden" id="popupRowId" name="row_id" value="">
                    </div>
                    <div class="w-100 d-flex justify-content-start">
                        <p class="m-0 p-0 mb-3" style="font-weight: 500;">Peran anda : </p>
                        <p class="m-0 p-0 mb-3 ms-1 peran-text">(Peran)</p>
                    </div>
                    <div class="w-100 d-flex justify-content-between mt-2 position-relative">
                        <div class="w-100 d-flex justify-content-start">
                            <a class='custom-btn-action btn btn-secondary me-2' href='detail?id='><i class='bi bi-eye-fill'></i></a>
                            <a class='custom-btn-action btn btn-primary' href='surat?id=' target='_blank'><i class='bi bi-file-earmark-text-fill'></i></a>
                        </div>
                        <div class="w-100 d-flex justify-content-center">
                            <a href="#" class="btn btn-success me-2" id="tombolSetuju">Setujui</a>
                            <!-- <a href="#" class="btn btn-danger" id="tombolTolakAprv">Tolak</a> -->
                        </div>
                        <div class="w-100 d-flex justify-content-end">
                            <a href="#" class="btn btn-secondary" id="tombolClosePopup"> Batal</a>
                        </div>

                    </div>

                </div>

                <div id="popupBoxApprovePendingEdit"
                    class="popup-box custom-popup-approve-pending-edit position-fixed bg-white rounded-1 flex-column justify-content-start align-items-center p-2"
                    style="height: max-content; align-self: center; z-index: 7; width:600px; min-width:500px; left:43.5%; top:30vh;">

                    <div class="w-100 d-flex justify-content-between mb-2 p-0" style="height: max-content;">
                        <h4 class="m-0 p-0">Approval Pending Edit</h4>
                        <a id="tombolClosePopupApprovePendingEdit" class="custom-btn-action btn btn-danger btn-sm" href="#"><i class="bi bi-x-lg"></i></a>
                    </div>

                    <!-- Tempat isi konten nanti -->
                    <div class="w-100">

                        <div class="data-pending-info">
                            <p class="mb-1 d-none"><strong>ID BA :</strong> <span id="info-id-ba"></span></p>
                            <p class="mb-1 d-none"><strong>Jenis BA :</strong> <span id="info-jenis-ba"></span></p>
                            <p class="mb-1 d-none"><strong>Approver :</strong> <span id="info-approver"></span></p>
                        </div>
                        <div class="d-flex flex-column align-content-center" style="height: max-content;">
                            <div class="border rounded d-flex flex-column align-items-center p-1" style="height: max-content;">
                                <h5>Perubahan yang dilakukan</h5>

                                <!-- === UPDATE: tambahkan tabel kosong untuk datatables === -->
                                <div class="w-100" style="height: max-content;">
                                    <table id="tabelPerubahan" class="table table-bordered table-striped">
                                        <thead>
                                            <tr>
                                                <th>Kolom</th>
                                                <th>Data Lama</th>
                                                <th>Data Baru</th>
                                            </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>

                                <div id="alasanEditBox" class="mt-2 w-100 text-start">
                                    <strong>Alasan Edit:</strong>
                                    <p id="isiAlasanEdit" class="m-0"></p>
                                </div>

                            </div>

                            <div class="d-flex justify-content-center w-100">
                                <h6 class="m-0 p-0 mb-2">Setujui Perubahan?</h6>
                            </div>
                            <div class="d-flex justify-content-center gap-1">
                                <div class="btn btn-success btnSetujuApprovalEdit">Setujui</div>
                                <div class="btn btn-danger btnTolakApprovalEdit">Tolak</div>
                            </div>
                        </div>
                    </div>

                </div>

                <div id="popupBoxTolakPendingEdit"
                    class="popup-box custom-popup-tolak-pending-edit position-fixed bg-white rounded-1 flex-column justify-content-start align-items-center p-2"
                    style="height: max-content; align-self: center; z-index:9; width:500px; min-width:400px; left:46%; top:35vh; display:none;">

                    <div class="w-100 d-flex justify-content-between mb-2 p-0">
                        <h4 class="m-0 p-0">Konfirmasi Penolakan</h4>
                        <a id="closePopupTolakPendingEdit" class="custom-btn-action btn btn-danger btn-sm" href="#"><i class="bi bi-x-lg"></i></a>
                    </div>

                    <div class="w-100">
                        <label class="fw-bold mb-1">Alasan Penolakan:</label>
                        <div class="input-group mb-3">
                            <textarea id="alasanTolakInput" class="form-control" rows="2"></textarea>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <button id="btnBatalTolakPendingEdit" class="btn btn-secondary">Batal</button>
                            <button id="btnKonfirmasiTolakPendingEdit" class="btn btn-danger">Tolak</button>
                        </div>
                    </div>
                </div>

                <div id="popupBoxApprovePendingDelete"
                    class="popup-box custom-popup-approve-pending-edit position-fixed bg-white rounded-1 flex-column justify-content-start align-items-center p-2"
                    style="height: max-content; align-self: center; z-index: 7; width:600px; min-width:500px; left:43.5%; top:30vh;">

                    <div class="w-100 d-flex justify-content-between mb-2 p-0" style="height: max-content;">
                        <h4 class="m-0 p-0">Approval Pending Delete</h4>
                        <a id="tombolClosePopupApprovePendingDelete" class="custom-btn-action btn btn-danger btn-sm" href="#"><i class="bi bi-x-lg"></i></a>
                    </div>

                    <!-- Tempat isi konten nanti -->
                    <div class="w-100">

                        <div class="data-pending-info">
                            <p class="mb-1 d-none"><strong>ID BA :</strong> <span id="info-id-ba-delete"></span></p>
                            <p class="mb-1 d-none"><strong>Jenis BA :</strong><span id="info-jenis-ba-delete"></span></p>
                            <p class="mb-1 d-none"><strong>Approver :</strong> <span id="info-approver-delete"></span></p>
                        </div>
                        <div class="d-flex flex-column align-content-center" style="height: max-content;">

                            <div id="alasanDeleteBox" class="mt-2 w-100 text-start">
                                <strong>Alasan Hapus:</strong>
                                <p id="isiAlasanDelete" class="m-0"></p>
                            </div>

                            <div class="d-flex justify-content-center w-100">
                                <h6 class="m-0 p-0 mb-2">Setujui Hapus?</h6>
                            </div>
                            <div class="d-flex justify-content-between gap-1">
                                <div class=""></div>
                                <div class="d-flex justify-content-center gap-1">
                                    <div class="btn btn-success btnSetujuApprovalDelete">Setujui</div>
                                    <div class="btn btn-danger btnTolakApprovalDelete">Tolak</div>
                                </div>
                                <div class=""></div>
                            </div>
                        </div>
                    </div>

                </div>

                <div id="popupBoxTolakPendingDelete"
                    class="popup-box custom-popup-tolak-pending-edit position-fixed bg-white rounded-1 flex-column justify-content-start align-items-center p-2"
                    style="height: max-content; align-self: center; z-index:9; width:500px; min-width:400px; left:46%; top:35vh; display:none;">

                    <div class="w-100 d-flex justify-content-between mb-2 p-0">
                        <h4 class="m-0 p-0">Konfirmasi Penolakan</h4>
                        <a id="closePopupTolakPendingDelete" class="custom-btn-action btn btn-danger btn-sm" href="#"><i class="bi bi-x-lg"></i></a>
                    </div>

                    <div class="w-100">

                        <div class="d-flex justify-content-end gap-2">
                            <button id="btnBatalTolakPendingDelete" class="btn btn-secondary">Batal</button>
                            <button id="btnKonfirmasiTolakPendingDelete" class="btn btn-danger">Tolak</button>
                        </div>
                    </div>
                </div>

            </section>
        </main>

        <!--Awal::Footer Content-->
        <footer class="custom-footer d-flex position-relative p-2" style="border-top: whitesmoke solid 1px; box-shadow: 0px 7px 10px black; color: grey;">
            <p class="position-absolute" style="right: 15px;bottom:7px;color: grey;"><strong>Version </strong>1.1.0</p>
            <p class="pt-2 ps-1"><strong>Copyright &copy 2025</p></strong>
            <p class="pt-2 ps-1 fw-bold text-primary">MIS MSAL.</p>
            <p class="pt-2 ps-1"> All rights reserved</p>
        </footer>
        <!--Akhir::Footer Content-->

        <?php
        // Ambil data warna
        $sqlWarna = "SELECT nama, warna FROM personalia_menucolor ORDER BY nama ASC";
        $resultWarna = $koneksi->query($sqlWarna);
        ?>

        <div id="popupBoxPersonalia" class="popup-box position-fixed end-0" style="z-index: 15; top: 50px;">
            <div id="theme-panel" class="card position-relative bg-white p-2 m-2" style="width:200px; height:max-content; box-shadow: 0px 4px 8px rgba(0,0,0,0.1); ">
                <h5 class="card-title border-bottom pb-2 mb-0">Personalia</h5>
                <form action="../proses_simpan_personalia.php" method="post" class="p-0">
                    <div class="mb-2">
                        <label for="themeSelect" class="form-label mt-0">Warna Tema:</label>
                        <select id="themeSelect" name="warna_menu" class="form-select">
                            <option value="0" selected>Default</option>
                            <?php while ($row = $resultWarna->fetch_assoc()): ?>
                                <option value="<?= htmlspecialchars($row['warna']); ?>">
                                    <?= htmlspecialchars($row['nama']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm">Simpan</button>
                </form>
            </div>
        </div>

        <div id="popupBG" class="popup-bg position-absolute w-100 h-100"></div>
        <div id="popupBGKirimEmail" class="popup-bg position-absolute w-100 h-100"></div>
        <div id="popupBGApprovePendingEdit" class="popup-bg position-fixed w-100 h-100"></div>
        <div id="popupBGApprovePendingDelete" class="popup-bg position-fixed w-100 h-100"></div>
        <div id="popupBGTolakPendingEdit" class="popup-bg position-fixed w-100 h-100" style="z-index:8 !important;"></div>
        <div id="popupBGTolakPendingDelete" class="popup-bg position-fixed w-100 h-100" style="z-index:8 !important;"></div>
    </div>



    <!-- Bootstrap 5 -->
    <script src="../assets/bootstrap-5.3.6-dist/js/bootstrap.min.js"></script>

    <!-- popperjs Bootstrap 5 -->
    <script src="../assets/js/popper.min.js"></script>

    <!-- AdminLTE -->
    <script src="../assets/adminlte/js/adminlte.js"></script>

    <!-- OverlayScrollbars -->
    <script src="../assets/js/overlayscrollbars.browser.es6.min.js"></script>

    <!-- signaturPad -->
    <script src="../assets/js/signature_pad.umd.min.js"></script>
    <!-- <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script> -->

    <script>
        //Info Akun
        document.addEventListener('DOMContentLoaded', function() {
            const button = document.getElementById('tombolAkun');
            const box = document.getElementById('akunInfo');

            button.addEventListener('click', function() {
                if (box.classList.contains('display-state')) {
                    // Buka
                    box.classList.remove('display-state');
                    setTimeout(() => {
                        box.classList.add('aktif');
                    }, 200);
                } else {
                    // Tutup
                    box.classList.remove('aktif');
                    setTimeout(() => {
                        box.classList.add('display-state');
                    }, 200);
                }
            });
        });
    </script>

    <script>
        //DataTables
        $(document).ready(function() {
            $('#myTable').DataTable({
                responsive: true,
                autoWidth: true,
                language: {
                    url: "../assets/json/id.json"
                },
                scrollY: "450px", // batasi tinggi scroll 300px
                scrollCollapse: true, // tabel ikut mengecil jika data kurang
                paging: true,
                columnDefs: [{
                        targets: -1,
                        orderable: false
                    }, // Kolom Actions tidak bisa di-sort

                ],
                initComplete: function() {
                    // Sembunyikan skeleton
                    $('#tableSkeleton').fadeOut(200, function() {
                        $('#tabelUtama').fadeIn(200);
                    });
                }
            });
        });
    </script>

    <script>
        ///popup approval
        document.addEventListener('DOMContentLoaded', function() {
            const box = document.getElementById('popupBoxAprv');
            const background = document.getElementById('popupBG');
            const dataIdSpan = document.getElementById('popupDataId');
            const dataIdInput = document.getElementById('popupRowId');
            const popupTitle = document.querySelector('#popupBoxAprv h6');
            const linkDetail = document.querySelector('#popupBoxAprv a[href^="detail"]');
            const linkSurat = document.querySelector('#popupBoxAprv a[href^="surat"]');
            const peranText = document.querySelector('#popupBoxAprv .peran-text');
            const tombolSetuju = document.getElementById('tombolSetuju');
            const btnDisapear = document.getElementById('btn-disapear');

            const namaSession = "<?php echo $_SESSION['nama']; ?>";

            function bulanRomawi(tgl) {
                const bulan = new Date(tgl).getMonth() + 1;
                const romawi = ["I", "II", "III", "IV", "V", "VI", "VII", "VIII", "IX", "X", "XI", "XII"];
                return romawi[bulan - 1] || '';
            }

            function mapRoleToApprovalCols(role, jenisBa) {
                if (jenisBa === 'kerusakan') {
                    if (role === 'Pembuat') return ['approval_1'];
                    if (role === 'Penyetujui') return ['approval_2'];
                } else if (jenisBa === 'pengembalian') {
                    if (role === 'Pengembali') return ['approval_1'];
                    if (role === 'Penerima') return ['approval_2'];
                    if (role === 'Diketahui') return ['approval_3'];
                } else if (jenisBa === 'notebook') {
                    if (role === 'Pertama') return ['approval_1'];
                    if (role === 'Kedua') return ['approval_2'];
                    if (role === 'Saksi') return ['approval_3'];
                    if (role === 'Diketahui') return ['approval_4'];
                } else if (jenisBa === 'mutasi') {
                    if (role === 'Pengirim') return ['approval_1'];
                    if (role === 'Diketahui1') return ['approval_2'];
                    if (role === 'Diketahui2') return ['approval_3'];
                    if (role === 'Penerima1') return ['approval_4'];
                    if (role === 'Penerima2') return ['approval_5'];
                } else if (jenisBa === 'st_asset') {
                    if (role === 'Peminjam') return ['approval_1'];
                    if (role === 'Saksi') return ['approval_2'];
                    if (role === 'Diketahui') return ['approval_3'];
                    if (role === 'Pertama') return ['approval_4'];
                } else if (jenisBa === 'pemutihan') {
                    if (role === 'Approver 1') return ['approval_1'];
                    if (role === 'Approver 2') return ['approval_2'];
                    if (role === 'Approver 3') return ['approval_3'];
                    if (role === 'Approver 4') return ['approval_4'];
                    if (role === 'Approver 5') return ['approval_5'];
                    if (role === 'Approver 6') return ['approval_6'];
                    if (role === 'Approver 7') return ['approval_7'];
                    if (role === 'Approver 8') return ['approval_8'];
                    if (role === 'Approver 9') return ['approval_9'];
                    if (role === 'Approver 10') return ['approval_10'];
                    if (role === 'Approver 11') return ['approval_11'];
                }
                return [];
            }

            document.addEventListener('click', function(e) {
                const openBtn = e.target.closest('.js-open-approve');
                if (openBtn) {
                    e.preventDefault();

                    const id = openBtn.getAttribute('data-id') || '';
                    const nomor = openBtn.getAttribute('data-nomor') || '';
                    const tanggal = openBtn.getAttribute('data-tanggal') || '';

                    let jenisBa = openBtn.getAttribute('data-jenis-ba') || '';

                    if (openBtn.hasAttribute('data-nama-aprv1')) {
                        jenisBa = 'kerusakan';
                    } else if (openBtn.hasAttribute('data-nama-pengembali')) {
                        jenisBa = 'pengembalian';
                    } else if (openBtn.hasAttribute('data-nama-pertama')) {
                        jenisBa = 'notebook';
                    } else if (openBtn.hasAttribute('data-nama-pengirim')) {
                        jenisBa = 'mutasi';
                    } else if (openBtn.hasAttribute('data-nama-peminjam')) {
                        jenisBa = 'st_asset';
                    }

                    const appr1 = Number(openBtn.getAttribute('data-approval-1') || 0);
                    const appr2 = Number(openBtn.getAttribute('data-approval-2') || 0);
                    const appr3 = Number(openBtn.getAttribute('data-approval-3') || 0);
                    const appr4 = Number(openBtn.getAttribute('data-approval-4') || 0);
                    const appr5 = Number(openBtn.getAttribute('data-approval-5') || 0);

                    let roles = [];
                    if (jenisBa === 'kerusakan') {
                        const namaAprv1 = openBtn.getAttribute('data-nama-aprv1') || '';
                        const namaAprv2 = openBtn.getAttribute('data-nama-aprv2') || '';
                        if (namaAprv1 === namaSession) roles.push('Pembuat');
                        if (namaAprv2 === namaSession) roles.push('Penyetujui');
                    } else if (jenisBa === 'pengembalian') {
                        const namaPengembali = openBtn.getAttribute('data-nama-pengembali') || '';
                        const namaPenerima = openBtn.getAttribute('data-nama-penerima') || '';
                        const diketahui = openBtn.getAttribute('data-diketahui') || '';
                        if (namaPengembali === namaSession) roles.push('Pengembali');
                        if (namaPenerima === namaSession) roles.push('Penerima');
                        if (diketahui === namaSession) roles.push('Diketahui');
                    } else if (jenisBa === 'notebook') {
                        const namaPertama = openBtn.getAttribute('data-nama-pertama') || '';
                        const namaKedua = openBtn.getAttribute('data-nama-kedua') || '';
                        const namaSaksi = openBtn.getAttribute('data-nama-saksi') || '';
                        const namaDiketahui = openBtn.getAttribute('data-nama-diketahui') || '';
                        if (namaPertama === namaSession) roles.push('Pihak Pertama');
                        if (namaKedua === namaSession) roles.push('Pihak Kedua');
                        if (namaSaksi === namaSession) roles.push('Saksi');
                        if (namaDiketahui === namaSession) roles.push('Diketahui');
                    } else if (jenisBa === 'mutasi') {
                        const namaPengirim = openBtn.getAttribute('data-nama-pengirim') || '';
                        const namaDiketahui1 = openBtn.getAttribute('data-nama-diketahui1') || '';
                        const namaDiketahui2 = openBtn.getAttribute('data-nama-diketahui2') || '';
                        const namaPenerima1 = openBtn.getAttribute('data-nama-penerima1') || '';
                        const namaPenerima2 = openBtn.getAttribute('data-nama-penerima2') || '';
                        if (namaPengirim === namaSession) roles.push('Pengirim');
                        if (namaDiketahui1 === namaSession) roles.push('Diketahui1');
                        if (namaDiketahui2 === namaSession) roles.push('Diketahui2');
                        if (namaPenerima1 === namaSession) roles.push('Penerima1');
                        if (namaPenerima2 === namaSession) roles.push('Penerima2');
                    } else if (jenisBa === 'st_asset') {
                        const namaPengirim = openBtn.getAttribute('data-nama-peminjam') || '';
                        const namaDiketahui1 = openBtn.getAttribute('data-nama-saksi') || '';
                        const namaDiketahui2 = openBtn.getAttribute('data-nama-diketahui') || '';
                        const namaPenerima1 = openBtn.getAttribute('data-nama-pertama') || '';
                        if (namaPengirim === namaSession) roles.push('Peminjam');
                        if (namaDiketahui1 === namaSession) roles.push('Saksi');
                        if (namaDiketahui2 === namaSession) roles.push('Diketahui');
                        if (namaPenerima1 === namaSession) roles.push('Pertama');
                    }

                    if (peranText) peranText.textContent = roles.join(', ');
                    if (dataIdSpan) dataIdSpan.textContent = id;
                    if (dataIdInput) dataIdInput.value = id;

                    if (popupTitle) {
                        if (jenisBa === 'kerusakan') {
                            popupTitle.textContent = `BA Kerusakan Nomor ${nomor} Periode ${bulanRomawi(tanggal)}`;
                        } else if (jenisBa === 'pengembalian') {
                            popupTitle.textContent = `BA Pengembalian Nomor ${nomor} Periode ${bulanRomawi(tanggal)}`;
                        } else if (jenisBa === 'notebook') {
                            popupTitle.textContent = `BA Serah Terima Nomor ${nomor} Periode ${bulanRomawi(tanggal)}`;
                        } else if (jenisBa === 'mutasi') {
                            popupTitle.textContent = `BA Mutasi Nomor ${nomor} Periode ${bulanRomawi(tanggal)}`;
                        } else if (jenisBa === 'st_asset') {
                            popupTitle.textContent = `BA Serah Terima Penggunaan Asset Inventaris Nomor ${nomor} Periode ${bulanRomawi(tanggal)}`;
                        }
                    }

                    if (jenisBa === 'kerusakan') {
                        if (linkDetail) linkDetail.href = `detail_barang_kerusakan.php?id=${id}`;
                        if (linkSurat) linkSurat.href = `surat_output_kerusakan.php?id=${id}`;
                    } else if (jenisBa === 'pengembalian') {
                        if (linkDetail) linkDetail.href = `detail_barang_pengembalian.php?id=${id}`;
                        if (linkSurat) linkSurat.href = `surat_output_pengembalian.php?id=${id}`;
                    } else if (jenisBa === 'notebook') {
                        if (linkDetail) linkDetail.href = `detail_barang_notebook.php?id=${id}`;
                        if (linkSurat) linkSurat.href = `surat_output_notebook.php?id=${id}`;
                    } else if (jenisBa === 'mutasi') {
                        if (linkDetail) linkDetail.href = `detail_barang_mutasi.php?id=${id}`;
                        if (linkSurat) linkSurat.href = `surat_output_mutasi.php?id=${id}`;
                    } else if (jenisBa === 'st_asset') {
                        if (linkDetail) linkDetail.href = `detail_barang_serah_terima_asset.php?id=${id}`;
                        if (linkSurat) linkSurat.href = `surat_output_serah_terima_asset.php?id=${id}`;
                    }


                    if (tombolSetuju) {
                        let showApprove = roles.length > 0;

                        function approvalValueForRole(role) {
                            if (jenisBa === 'kerusakan') {
                                if (role === 'Pembuat') return appr1;
                                if (role === 'Penyetujui') return appr2;

                            } else if (jenisBa === 'pengembalian') {
                                if (role === 'Pengembali') return appr1;
                                if (role === 'Penerima') return appr2;
                                if (role === 'Diketahui') return appr3;
                            } else if (jenisBa === 'notebook') {
                                if (role === 'Pertama') return appr1;
                                if (role === 'Kedua') return appr2;
                                if (role === 'Saksi') return appr3;
                                if (role === 'Diketahui') return appr4;
                            } else if (jenisBa === 'mutasi') {
                                if (role === 'Pengirim') return appr1;
                                if (role === 'Diketahui1') return appr2;
                                if (role === 'Diketahui2') return appr3;
                                if (role === 'Penerima1') return appr4;
                                if (role === 'Penerima2') return appr5;
                            } else if (jenisBa === 'st_asset') {
                                if (role === 'Peminjam') return appr1;
                                if (role === 'Saksi') return appr2;
                                if (role === 'Diketahui') return appr3;
                                if (role === 'Pertama') return appr4;
                            }
                            return 0;
                        }


                        let alreadyApprovedAll = roles.length > 0 && roles.every(r => approvalValueForRole(r) === 1);

                        if (showApprove) {
                            tombolSetuju.style.display = 'inline-block';
                            if (!alreadyApprovedAll) {
                                tombolSetuju.textContent = 'Setujui';
                                tombolSetuju.classList.remove('btn-warning');
                                tombolSetuju.classList.add('custom-btn-action btn-success');
                                tombolSetuju.dataset.action = 'approve';
                            } else {

                                if (btnDisapear) btnDisapear.style.display = 'none';
                                tombolSetuju.style.display = 'none';

                                // tombolSetuju.textContent = 'Batal Setujui';
                                // tombolSetuju.classList.remove('btn-success');
                                // tombolSetuju.classList.add('btn-warning');
                                // tombolSetuju.dataset.action = 'cancel';
                            }
                        } else {
                            tombolSetuju.style.display = 'none';
                        }


                        tombolSetuju.dataset.jenisBa = jenisBa;
                    }

                    box.classList.add('aktifPopup');
                    box.classList.add('scale-in-center');
                    box.classList.remove('scale-out-center');
                    background.classList.add('aktifPopup');
                    background.classList.add('fade-in');
                    background.classList.remove('fade-out');
                    return;
                }

                if (e.target.closest('#tombolClosePopup')) {
                    e.preventDefault();
                    // box.classList.remove('aktifPopup');
                    box.classList.remove('scale-in-center');
                    box.classList.add('scale-out-center');
                    setTimeout(() => {
                        background.classList.remove('aktifPopup');
                    }, 300);
                    background.classList.remove('fade-in');
                    background.classList.add('fade-out');
                    return;
                }

                if (e.target.id === 'popupBG') {
                    // box.classList.remove('aktifPopup');
                    box.classList.remove('scale-in-center');
                    box.classList.add('scale-out-center');
                    setTimeout(() => {
                        background.classList.remove('aktifPopup');
                    }, 300);
                    background.classList.remove('fade-in');
                    background.classList.add('fade-out');
                }
            });


            if (tombolSetuju) {
                tombolSetuju.addEventListener('click', function(e) {
                    e.preventDefault();

                    const id = dataIdInput.value;
                    const roles = peranText.textContent.split(',').map(r => r.trim()).filter(Boolean);
                    if (!id || roles.length === 0) return;

                    const jenisBa = tombolSetuju.dataset.jenisBa || 'pengembalian';


                    let approvals = [];
                    roles.forEach(role => {
                        mapRoleToApprovalCols(role, jenisBa).forEach(col => {
                            if (!approvals.includes(col)) approvals.push(col);
                        });
                    });


                    let validCols = [];

                    if (jenisBa === 'kerusakan') {
                        validCols = ['approval_1', 'approval_2'];
                    } else if (jenisBa === 'pengembalian') {
                        validCols = ['approval_1', 'approval_2', 'approval_3'];
                    } else if (jenisBa === 'notebook') {
                        validCols = ['approval_1', 'approval_2', 'approval_3', 'approval_4'];
                    } else if (jenisBa === 'pemutihan') {
                        validCols = [
                            'approval_1', 'approval_2', 'approval_3', 'approval_4', 'approval_5',
                            'approval_6', 'approval_7', 'approval_8', 'approval_9', 'approval_10', 'approval_11'
                        ];
                    }

                    approvals = approvals.filter(a => validCols.includes(a));

                    if (approvals.length === 0) {
                        alert('Tidak ada kolom approval yang valid untuk peran Anda.');
                        console.log('Debug: approvals kosong, roles=', roles, 'jenisBa=', jenisBa);
                        return;
                    }

                    const action = tombolSetuju.dataset.action || 'approve';

                    const payload = {
                        id: id,
                        approvals: approvals,
                        action: action,
                        jenis_ba: jenisBa
                    };

                    // console.log('kirim payload approve ->', payload); //Pengecekan data approve

                    fetch('proses_approve.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify(payload)
                        })
                        .then(r => r.json())
                        .then(res => {
                            if (res.success) {
                                location.reload();
                            } else {
                                location.reload();
                            }
                        })
                        .catch(err => {
                            console.error(err);
                            alert('Gagal network / server. Cek console.');
                        });
                });
            }
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const buttons = document.querySelectorAll('.tombolKirimEmail');
            const buttonPending = document.querySelectorAll('.tombolKirimEmailPending');
            const box = document.getElementById('popupBoxEmail');
            const background = document.getElementById('popupBGKirimEmail');
            const close = document.getElementById('tombolClosePopupEmail');
            const closeKirim = document.getElementById('tombolClosePopupKirimEmail');
            const popupRowId = document.getElementById('popupRowIdEmail');
            const listEl = document.getElementById('listAktorEmail');
            const hiddenAktor = document.getElementById('aktorEmailHidden');
            const infoBA = document.getElementById('popupInfoBA');
            const infoBA2 = document.getElementById('popupInfoBA2');

            // 🔹 hidden input tambahan
            const dataNomor = document.getElementById('dataNomor');
            const dataTanggal = document.getElementById('dataTanggal');
            const dataBulanRomawi = document.getElementById('dataBulanRomawi');
            const dataTahun = document.getElementById('dataTahun');
            const dataJenisBA = document.getElementById('dataJenisBA');
            const dataJenisPermintaan = document.getElementById('dataJenisPermintaan');
            const dataNamaPeminta = document.getElementById('dataNamaPeminta')

            if (!box || !listEl || !hiddenAktor) return; // pastikan elemen wajib ada

            const hasBackground = Boolean(background);

            // === Fungsi umum buka/tutup popup ===
            function openPopup() {
                box.classList.add('aktifPopup', 'scale-in-center');
                box.classList.remove('scale-out-center');
                if (hasBackground) {
                    background.classList.add('aktifPopup', 'fade-in');
                    background.classList.remove('fade-out');
                }
            }

            function closePopup() {
                box.classList.remove('scale-in-center');
                box.classList.add('scale-out-center');
                if (hasBackground) {
                    background.classList.remove('fade-in');
                    background.classList.add('fade-out');
                }
                setTimeout(() => {
                    if (hasBackground) background.classList.remove('aktifPopup');
                    box.classList.remove('aktifPopup');
                }, 300);
            }

            // === Listener tombol kirim email ===
            buttons.forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();

                    // Ambil semua data-* dari tombol
                    const rowId = btn.dataset.id || '';
                    const aktorJson = btn.dataset.aktor || '[]';
                    const nomorBA = btn.dataset.nomor || '';
                    const tanggal = btn.dataset.tanggal || '';
                    const jenisBA = btn.dataset.jenisBa || '';
                    const bulanRomawi = btn.dataset.bulanRomawi || '';
                    const tahun = btn.dataset.tahun || '';
                    const jenisPermintaan = btn.dataset.jenisPermintaan || '';

                    // Parse data aktor (array JSON)
                    let aktorList = [];
                    try {
                        aktorList = JSON.parse(aktorJson);
                    } catch (err) {}

                    // 🔹 Isi semua input hidden
                    popupRowId.value = rowId;
                    hiddenAktor.value = aktorList.join(',');
                    dataNomor.value = nomorBA;
                    dataTanggal.value = tanggal;
                    dataBulanRomawi.value = bulanRomawi;
                    dataTahun.value = tahun;
                    dataJenisBA.value = jenisBA;
                    dataJenisPermintaan.value = jenisPermintaan;

                    // 🔹 Tampilkan daftar aktor
                    listEl.innerHTML = '';
                    aktorList.forEach(nama => {
                        const li = document.createElement('li');
                        li.textContent = nama;
                        listEl.appendChild(li);
                    });

                    console.log("Data Aktor:", aktorList);

                    // 🔹 Tampilkan info berita acara
                    if (infoBA) {
                        infoBA.textContent = `Berita Acara ${jenisBA} ${nomorBA} Periode ${bulanRomawi}/${tahun}`;
                    }
                    if (infoBA2) {
                        //infoBA2.textContent = `${jenisPermintaan}`;
                        infoBA2.textContent = ``;
                    }

                    // 🔹 Buka popup
                    openPopup();
                });
            });
            buttonPending.forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();

                    // Ambil semua data-* dari tombol
                    const rowId = btn.dataset.id || '';
                    const aktorJson = btn.dataset.aktor || '[]';
                    const nomorBA = btn.dataset.nomor || '';
                    const tanggal = btn.dataset.tanggal || '';
                    const jenisBA = btn.dataset.jenisBa || '';
                    const bulanRomawi = btn.dataset.bulanRomawi || '';
                    const tahun = btn.dataset.tahun || '';
                    const jenisPermintaan = btn.dataset.jenisPermintaan || '';
                    const namaPeminta = btn.dataset.namaPeminta || '';

                    // Parse data aktor (array JSON)
                    let aktorList = [];
                    try {
                        aktorList = JSON.parse(aktorJson);
                    } catch (err) {
                        console.error('Aktor JSON invalid:', aktorJson);
                    }

                    // 🔹 Isi semua input hidden
                    popupRowId.value = rowId;
                    hiddenAktor.value = aktorList.join(',');
                    dataNomor.value = nomorBA;
                    dataTanggal.value = tanggal;
                    dataBulanRomawi.value = bulanRomawi;
                    dataTahun.value = tahun;
                    dataJenisBA.value = jenisBA;
                    dataJenisPermintaan.value = jenisPermintaan;
                    dataNamaPeminta.value = namaPeminta;

                    // 🔹 Tampilkan daftar aktor
                    listEl.innerHTML = '';
                    aktorList.forEach(nama => {
                        const li = document.createElement('li');
                        li.textContent = nama;
                        listEl.appendChild(li);
                    });

                    console.log("Data Aktor:", aktorList);

                    // 🔹 Tampilkan info berita acara
                    if (infoBA) {
                        infoBA.textContent = `Berita Acara ${jenisBA} ${nomorBA} Periode ${bulanRomawi}/${tahun}`;
                    }
                    if (infoBA2) {
                        infoBA2.textContent = ` `;
                        //infoBA2.textContent = `${jenisBA}`;
                    }


                    // 🔹 Buka popup
                    openPopup();
                });
            });

            // === Listener tombol close ===
            if (close) close.addEventListener('click', e => {
                e.preventDefault();
                closePopup();
            });
            if (closeKirim) closeKirim.addEventListener('click', e => {
                e.preventDefault();
                closePopup();
            });
            if (hasBackground) background.addEventListener('click', closePopup);
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // =========================
            // Popup elements
            // =========================
            const close = document.getElementById('tombolClosePopupAutograph');
            const box = document.getElementById('popupBoxAutograph');
            const background = document.getElementById('popupBG');

            // =========================
            // Signature elements
            // =========================
            const canvas = document.getElementById("signature");
            const clearBtn = document.getElementById("clear");
            const loadBtn = document.getElementById("load");
            const saveBtn = document.getElementById("save");

            if (!canvas) return;

            // =========================
            // SignaturePad instance
            // =========================
            const signaturePad = new SignaturePad(canvas, {
                backgroundColor: "white",
                penColor: "black"
            });

            // state render
            let currentLogicalWidth = 500;
            let currentLogicalHeight = 200;
            let resizeTimer = null;
            let renderToken = 0;

            // source gambar TTD user (ORIGINAL), bukan snapshot canvas
            let baseSignatureImageDataUrl = "";

            // =========================
            // Helpers: canvas sizing (DPR aware)
            // =========================
            function getDPR() {
                return Math.max(window.devicePixelRatio || 1, 1);
            }

            function getCanvasDisplaySize() {
                // Desktop/tablet = 500x200
                // Mobile <= 450px mengikuti lebar tampilan canvas/popup
                if (window.innerWidth <= 450) {
                    const rectW = Math.round(canvas.getBoundingClientRect().width || 0);
                    const cssW = Math.round(canvas.clientWidth || canvas.offsetWidth || 0);
                    const width = Math.max(rectW || cssW || 300, 300);
                    return {
                        width,
                        height: 200
                    };
                }
                return {
                    width: 500,
                    height: 200
                };
            }

            function setupCanvasBitmap() {
                const {
                    width,
                    height
                } = getCanvasDisplaySize();
                const dpr = getDPR();
                const ctx = canvas.getContext("2d");

                currentLogicalWidth = width;
                currentLogicalHeight = height;

                // ukuran tampilan (CSS)
                canvas.style.width = width + "px";
                canvas.style.height = height + "px";

                // ukuran bitmap internal (DPR aware)
                canvas.width = Math.round(width * dpr);
                canvas.height = Math.round(height * dpr);

                // reset transform lalu scale sekali
                ctx.setTransform(1, 0, 0, 1, 0, 0);
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                ctx.scale(dpr, dpr);

                // clear SignaturePad state
                signaturePad.clear();
            }

            function drawImageFit(ctx, img, logicalW, logicalH) {
                const scale = Math.min(logicalW / img.width, logicalH / img.height);
                const drawW = img.width * scale;
                const drawH = img.height * scale;
                const x = (logicalW - drawW) / 2;
                const y = (logicalH - drawH) / 2;
                ctx.drawImage(img, x, y, drawW, drawH);
            }

            function renderSignatureCanvas(options = {}) {
                const preserveStrokes = options.preserveStrokes === true;

                // simpan stroke user sebagai vector (anti rusak saat resize)
                let strokeData = [];
                if (preserveStrokes && !signaturePad.isEmpty()) {
                    try {
                        strokeData = signaturePad.toData();
                    } catch (err) {
                        strokeData = [];
                    }
                }

                const token = ++renderToken;
                setupCanvasBitmap();

                const ctx = canvas.getContext("2d");
                const logicalW = currentLogicalWidth;
                const logicalH = currentLogicalHeight;

                if (baseSignatureImageDataUrl) {
                    const img = new Image();
                    img.onload = function() {
                        if (token !== renderToken) return; // cegah race condition
                        drawImageFit(ctx, img, logicalW, logicalH);

                        if (strokeData.length) {
                            try {
                                signaturePad.fromData(strokeData);
                            } catch (e) {
                                console.warn("Gagal restore stroke data:", e);
                            }
                        }
                    };
                    img.onerror = function() {
                        if (token !== renderToken) return;
                        console.error("Gagal load base signature image");

                        if (strokeData.length) {
                            try {
                                signaturePad.fromData(strokeData);
                            } catch (e) {
                                console.warn("Gagal restore stroke data:", e);
                            }
                        }
                    };
                    img.src = baseSignatureImageDataUrl;
                } else {
                    if (strokeData.length) {
                        try {
                            signaturePad.fromData(strokeData);
                        } catch (e) {
                            console.warn("Gagal restore stroke data:", e);
                        }
                    }
                }
            }

            function resetToBlankCanvas() {
                baseSignatureImageDataUrl = "";
                renderSignatureCanvas({
                    preserveStrokes: false
                });
            }

            function loadBaseSignatureFromDataUrl(dataUrl, preserveStrokes = false) {
                if (!dataUrl || typeof dataUrl !== "string" || !dataUrl.startsWith("data:image")) {
                    if (!preserveStrokes) resetToBlankCanvas();
                    return;
                }
                baseSignatureImageDataUrl = dataUrl;
                renderSignatureCanvas({
                    preserveStrokes
                });
            }

            function hasSomethingToSave() {
                // valid kalau ada coretan manual ATAU ada base image (ttd tersimpan)
                return (!signaturePad.isEmpty()) || !!baseSignatureImageDataUrl;
            }

            async function dataUrlToBlob(dataUrl) {
                const res = await fetch(dataUrl);
                return await res.blob();
            }

            // init baseline
            setupCanvasBitmap();

            // =========================
            // Popup open (delegation)
            // =========================
            document.addEventListener('click', function(e) {
                const btn = e.target.closest('.tombolAutographPopup');
                if (!btn) return;

                e.preventDefault();

                // Ambil data dari tombol
                const id = btn.dataset.id || "";
                const approval = btn.dataset.approvalCol || "";
                const autograph = btn.dataset.autographCol || "";
                const nama = btn.dataset.nama || "";
                const peran = btn.dataset.peran || "";
                const jenis = btn.dataset.jenis || "";
                const nomor = btn.dataset.nomor || "";
                const tanggal = btn.dataset.tanggal || "";
                const userAutographBase64 = btn.dataset.userPicture || "";

                // Source asli TTD user (kalau ada)
                const userAutographDataUrl = (userAutographBase64 && userAutographBase64.trim() !== "") ?
                    ("data:image/png;base64," + userAutographBase64) :
                    "";

                // Format tanggal → romawi + tahun
                let romawi = "-";
                let tahun = "-";
                if (tanggal) {
                    const d = new Date(tanggal);
                    if (!isNaN(d.getTime())) {
                        const bulan = d.getMonth() + 1;
                        tahun = d.getFullYear();
                        romawi = ["I", "II", "III", "IV", "V", "VI", "VII", "VIII", "IX", "X", "XI", "XII"][bulan - 1] || "-";
                    }
                }

                // Isi span
                document.getElementById("idBAAutograph").innerText = id;
                document.getElementById("approvalBAAutograph").innerText = approval;
                document.getElementById("autographBAAutograph").innerText = autograph;
                document.getElementById("namaBAAutograph").innerText = nama;
                document.getElementById("peranBAAutograph").innerText = peran;
                document.getElementById("jenisBAAutograph").innerText = jenis;
                document.getElementById("nomorBAAutograph").innerText = nomor;
                document.getElementById("periodeBAAutograph").innerText = romawi;
                document.getElementById("tahunBAAutograph").innerText = tahun;

                // Preview image user
                document.getElementById("userBAAutograph").src = userAutographDataUrl || "";

                // Isi hidden input
                document.getElementById("inputIdBAAutograph").value = id;
                document.getElementById("inputApprovalBAAutograph").value = approval;
                document.getElementById("inputAutographBAAutograph").value = autograph;
                document.getElementById("inputNamaBAAutograph").value = nama;
                document.getElementById("inputPeranBAAutograph").value = peran;
                document.getElementById("inputJenisBAAutograph").value = jenis;
                document.getElementById("inputNomorBAAutograph").value = nomor;
                document.getElementById("inputPeriodeBAAutograph").value = romawi;
                document.getElementById("inputTahunBAAutograph").value = tahun;

                // Tampilkan popup
                box.classList.add('aktifPopup', 'scale-in-center');
                box.classList.remove('scale-out-center');
                background.classList.add('aktifPopup', 'fade-in');
                background.classList.remove('fade-out');

                // Setelah popup tampil, render canvas sesuai ukuran aktual layar
                requestAnimationFrame(() => {
                    requestAnimationFrame(() => {
                    /* Sistem Load TTD */
                    /*
                    if (userAutographDataUrl) {
                        loadBaseSignatureFromDataUrl(userAutographDataUrl, false);
                    } else {
                        resetToBlankCanvas();
                    }
                    */

                    });
                });
            });

            // =========================
            // Popup close handlers
            // =========================
            if (close) {
                close.addEventListener('click', function(e) {
                    e.preventDefault();

                    setTimeout(() => {
                        background.classList.remove('aktifPopup');
                        box.classList.remove('aktifPopup');
                    }, 300);

                    box.classList.remove('scale-in-center');
                    box.classList.add('scale-out-center');
                    background.classList.remove('fade-in');
                    background.classList.add('fade-out');
                });
            }

            if (background) {
                background.addEventListener('click', function() {
                    if (!box.classList.contains('aktifPopup')) return;

                    setTimeout(() => {
                        background.classList.remove('aktifPopup');
                        box.classList.remove('aktifPopup');
                    }, 300);

                    box.classList.remove('scale-in-center');
                    box.classList.add('scale-out-center');
                    background.classList.remove('fade-in');
                    background.classList.add('fade-out');
                });
            }

            // =========================
            // Signature buttons
            // =========================
            if (clearBtn) {
                clearBtn.addEventListener("click", function(e) {
                    e.preventDefault();
                    // Clear = kosong total (base image + stroke)
                    resetToBlankCanvas();
                });
            }

            /* Sistem Load TTD */
            /*
            if (loadBtn) {
                loadBtn.addEventListener("click", function(e) {
                    e.preventDefault();

                    const userAutographSrc = document.getElementById("userBAAutograph").src || "";

                    if (!userAutographSrc || !userAutographSrc.startsWith("data:image")) {
                        window.alert("Tidak ada data TTD sebelumnya untuk dimuat ulang.");
                        return;
                    }

                    loadBaseSignatureFromDataUrl(userAutographSrc, false);
                });
            }
            */

            if (saveBtn) {
                saveBtn.addEventListener("click", async function(e) {
                    e.preventDefault();

                    if (!hasSomethingToSave()) {
                        window.alert("Silakan buat tanda tangan terlebih dahulu sebelum menyimpan!");
                        return;
                    }

                    try {
                        /*
                         * FIX UTAMA:
                         * - Jika hanya pakai TTD tersimpan (base image) TANPA coretan baru,
                         *   simpan gambar ASLI -> tidak re-render canvas HP -> tidak mengecil lagi.
                         * - Jika ada coretan manual, simpan hasil gabungan canvas.
                         */
                        const onlyUsingStoredSignature = !!baseSignatureImageDataUrl && signaturePad.isEmpty();
                        const finalDataUrl = onlyUsingStoredSignature ?
                            baseSignatureImageDataUrl :
                            canvas.toDataURL("image/png");

                        const blob = await dataUrlToBlob(finalDataUrl);

                        const form = document.createElement("form");
                        form.method = "POST";
                        form.action = "proses_autograph.php";
                        form.enctype = "multipart/form-data";

                        function addField(name, value) {
                            const input = document.createElement("input");
                            input.type = "hidden";
                            input.name = name;
                            input.value = value ?? "";
                            form.appendChild(input);
                        }

                        addField("id", document.getElementById("inputIdBAAutograph").value);
                        addField("approvalCol", document.getElementById("inputApprovalBAAutograph").value);
                        addField("autographCol", document.getElementById("inputAutographBAAutograph").value);
                        addField("nama", document.getElementById("inputNamaBAAutograph").value);
                        addField("peran", document.getElementById("inputPeranBAAutograph").value);
                        addField("jenis", document.getElementById("inputJenisBAAutograph").value);
                        addField("nomor", document.getElementById("inputNomorBAAutograph").value);
                        addField("periode", document.getElementById("inputPeriodeBAAutograph").value);
                        addField("tahun", document.getElementById("inputTahunBAAutograph").value);

                        // signature sebagai FILE (sesuai $_FILES['signature'])
                        const fileInput = document.createElement("input");
                        fileInput.type = "file";
                        fileInput.name = "signature";

                        const dt = new DataTransfer();
                        dt.items.add(new File([blob], "signature.png", {
                            type: "image/png"
                        }));
                        fileInput.files = dt.files;

                        form.appendChild(fileInput);
                        document.body.appendChild(form);
                        form.submit();
                    } catch (err) {
                        console.error(err);
                        window.alert("Gagal menyimpan tanda tangan. Silakan coba lagi.");
                    }
                });
            }

            // =========================
            // Window resize handling
            // =========================
            window.addEventListener("resize", function() {
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(function() {
                    // hanya rerender saat popup aktif
                    if (!box.classList.contains("aktifPopup")) return;

                    // preserve stroke user + redraw base image dari source asli
                    renderSignatureCanvas({
                        preserveStrokes: true
                    });
                }, 100);
            });
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const open = document.getElementById('personaliaBtn');
            const box = document.getElementById('popupBoxPersonalia');
            const background = document.getElementById('popupBG');

            open.addEventListener('click', function() {
                box.classList.add('aktifPopup');
                background.classList.add('aktifPopup');
                box.classList.add('scale-in-center');
                box.classList.remove('scale-out-center');
                background.classList.add('fade-in');
                background.classList.remove('fade-out');
            });

            background.addEventListener('click', function() {
                // box.classList.remove('aktifPopup');
                // background.classList.remove('aktifPopup');
                setTimeout(() => {
                    background.classList.remove('aktifPopup');
                    box.classList.remove('aktifPopup');
                }, 300);
                box.classList.remove('scale-in-center');
                box.classList.add('scale-out-center');
                background.classList.remove('fade-in');
                background.classList.add('fade-out');
            });
        });
    </script>

    <script>
        // === Progress bar saat submit form ===
        const formKirimEmail = document.getElementById('formKirimEmail');
        const progressContainer = document.getElementById('progressContainer');
        const progressBar = document.getElementById('progressBar');

        if (formKirimEmail && progressContainer && progressBar) {
            formKirimEmail.addEventListener('submit', function(e) {
                // Tampilkan progress bar
                progressContainer.style.display = 'block';
                progressBar.style.width = '0%';
                progressBar.textContent = '0%';

                // Simulasi loading (misalnya kirim email)
                let progress = 0;
                const interval = setInterval(() => {
                    progress += Math.floor(Math.random() * 10) + 5; // naik acak 5–15%
                    if (progress >= 100) {
                        progress = 100;
                        clearInterval(interval);
                    }
                    progressBar.style.width = progress + '%';
                    progressBar.textContent = progress + '%';
                }, 300);

                // Setelah form dikirim (halaman reload)
                // biarkan browser handle redirect normal
            });
        }
    </script>

    <script>
        document.addEventListener("DOMContentLoaded", function() {

            const box = document.getElementById("popupBoxApprovePendingEdit");
            const close = document.getElementById("tombolClosePopupApprovePendingEdit");
            const background = document.getElementById("popupBGApprovePendingEdit");

            document.addEventListener("click", function(e) {
                const btn = e.target.closest(".tombolApprovePendingEditPopup");
                if (btn) {

                    const idBA = btn.dataset.id || "-";
                    const jenisBA = btn.dataset.jenisBa || "-";
                    const approver = btn.dataset.namaApprover || "-";

                    document.getElementById("info-id-ba").textContent = idBA;
                    document.getElementById("info-jenis-ba").textContent = jenisBA;
                    document.getElementById("info-approver").textContent = approver;

                    // ================================================================
                    // === UPDATE : Ambil Histori & Bandingkan Data ===================
                    // ================================================================
                    fetch(
                            "get_pending_history.php?id_ba=" + idBA +
                            "&approver=" + encodeURIComponent(approver) +
                            "&jenisBA=" + encodeURIComponent(jenisBA)
                        )
                        .then(res => res.json())
                        .then(data => {

                            let hasilPerbedaan = [];
                            let namaKolom = (data.namaKolom ? data.namaKolom : {});
                            for (const key in data.lama) {
                                if (data.lama[key] != data.baru[key]) {
                                    let namaKolomTampil = namaKolom[key] ? namaKolom[key] : key;
                                    hasilPerbedaan.push({
                                        kolom_raw: key,
                                        kolom: namaKolomTampil,
                                        lama: data.lama[key],
                                        baru: data.baru[key]
                                    });
                                }
                            }

                            // === UPDATE: tampilkan hasil pada DataTables ===
                            let table = $("#tabelPerubahan").DataTable({
                                destroy: true,
                                paging: false,
                                searching: false,
                                info: false,
                                scrollY: "150px", // batasi tinggi scroll 300px
                                scrollCollapse: true, // tabel ikut mengecil jika data kurang
                            });

                            // kosongkan dulu
                            table.clear();

                            // masukkan data baru
                            hasilPerbedaan.forEach(item => {
                                table.row.add([
                                    item.kolom,
                                    item.lama,
                                    item.baru
                                ]);
                            });

                            table.draw();

                            document.getElementById("isiAlasanEdit").textContent =
                                data.alasan_edit && data.alasan_edit !== "" ? data.alasan_edit : "Tidak ada alasan";

                        });
                    // ================================================================

                    // ======================================================
                    // === Tombol TOLAK kirim ke proses_approval_edit.php ===
                    // ======================================================
                    document.querySelector(".btnTolakApprovalEdit").addEventListener("click", function() {
                        // tampilkan overlay & popup tolak yang INDEPENDEN
                        document.getElementById("popupBGTolakPendingEdit").style.display = "block";
                        const pt = document.getElementById("popupBoxTolakPendingEdit");
                        pt.style.display = "flex";
                        // fokus textarea
                        setTimeout(() => document.getElementById("alasanTolakInput").focus(), 100);
                    });

                    const popupTolak = document.getElementById("popupBoxTolakPendingEdit");
                    const bgTolak = document.getElementById("popupBGTolakPendingEdit");
                    const btnCloseTolak = document.getElementById("closePopupTolakPendingEdit");
                    const btnBatalTolak = document.getElementById("btnBatalTolakPendingEdit");
                    const btnKonfTolak = document.getElementById("btnKonfirmasiTolakPendingEdit");

                    // Tutup ketika klik tombol close (X)
                    btnCloseTolak.addEventListener("click", function(ev) {
                        ev.preventDefault();
                        popupTolak.style.display = "none";
                        bgTolak.style.display = "none";
                    });

                    // Tombol batal
                    btnBatalTolak.addEventListener("click", function(ev) {
                        ev.preventDefault();
                        popupTolak.style.display = "none";
                        bgTolak.style.display = "none";
                    });

                    // Tutup ketika klik overlay bgTolak
                    bgTolak.addEventListener("click", function() {
                        popupTolak.style.display = "none";
                        bgTolak.style.display = "none";
                    });

                    // Pastikan klik di dalam popup tidak meneruskan ke overlay
                    popupTolak.addEventListener("click", function(ev) {
                        ev.stopPropagation();
                    });

                    // Tombol konfirmasi tolak -> kirim alasan_tolak bersama payload
                    btnKonfTolak.addEventListener("click", function() {
                        const idBA = document.getElementById("info-id-ba").textContent;
                        const jenisBA = document.getElementById("info-jenis-ba").textContent;
                        const approver = document.getElementById("info-approver").textContent;
                        const alasan = document.getElementById("alasanTolakInput").value;

                        if (!confirm("Apakah Anda yakin ingin MENOLAK pengajuan ini?")) {
                            return; // batalkan proses
                        }

                        // kirim data via AJAX (sama seperti proses tolak lama, tapi dengan alasan_tolak)
                        fetch("proses_approval_edit.php", {
                                method: "POST",
                                headers: {
                                    "Content-Type": "application/x-www-form-urlencoded"
                                },
                                body: "id_ba=" + encodeURIComponent(idBA) +
                                    "&jenisBA=" + encodeURIComponent(jenisBA) +
                                    "&approver=" + encodeURIComponent(approver) +
                                    "&alasan_tolak=" + encodeURIComponent(alasan) +
                                    "&aksi=tolak"
                            })
                            .then(res => res.json())
                            .then(res => {
                                if (res.success === true) {
                                    // tutup popup tolak independen lalu reload
                                    popupTolak.style.display = "none";
                                    bgTolak.style.display = "none";
                                    window.location.reload();
                                } else {
                                    alert("Gagal memproses penolakan.");
                                }
                            });
                    });

                    // ======================================================
                    // === Tombol SETUJU kirim ke proses_approval_edit.php ===
                    // ======================================================
                    document.querySelector(".btnSetujuApprovalEdit").addEventListener("click", function() {

                        const idBA = document.getElementById("info-id-ba").textContent;
                        const jenisBA = document.getElementById("info-jenis-ba").textContent;
                        const approver = document.getElementById("info-approver").textContent;

                        // kirim data via AJAX
                        fetch("proses_approval_edit.php", {
                                method: "POST",
                                headers: {
                                    "Content-Type": "application/x-www-form-urlencoded"
                                },
                                body: "id_ba=" + encodeURIComponent(idBA) +
                                    "&jenisBA=" + encodeURIComponent(jenisBA) +
                                    "&approver=" + encodeURIComponent(approver) +
                                    "&alasan_tolak=none" +
                                    "&aksi=setuju"
                            })
                            .then(res => res.json())
                            .then(res => {
                                if (res.success === true) {
                                    window.location.reload();
                                } else {
                                    alert("Gagal memproses penolakan.");
                                }
                            });

                    });


                    box.classList.add("aktifPopup", "scale-in-center");
                    box.classList.remove("scale-out-center");
                    background.classList.add("aktifPopup", "fade-in");
                    background.classList.remove("fade-out");
                }
            });

            // === TUTUP POPUP (TOMBOL CLOSE) ===
            close.addEventListener("click", function() {
                setTimeout(() => {
                    background.classList.remove("aktifPopup");
                    box.classList.remove("aktifPopup");
                }, 300);
                box.classList.remove("scale-in-center");
                box.classList.add("scale-out-center");
                background.classList.remove("fade-in");
                background.classList.add("fade-out");
            });

            // === TUTUP POPUP (KLIK BACKGROUND) ===
            background.addEventListener("click", function() {
                setTimeout(() => {
                    background.classList.remove("aktifPopup");
                    box.classList.remove("aktifPopup");
                }, 300);
                box.classList.remove("scale-in-center");
                box.classList.add("scale-out-center");
                background.classList.remove("fade-in");
                background.classList.add("fade-out");
            });

        });
    </script>

    <script>
        document.addEventListener("DOMContentLoaded", function() {

            const box = document.getElementById("popupBoxApprovePendingDelete");
            const close = document.getElementById("tombolClosePopupApprovePendingDelete");
            const background = document.getElementById("popupBGApprovePendingDelete");

            const popupTolak = document.getElementById("popupBoxTolakPendingDelete");
            const bgTolak = document.getElementById("popupBGTolakPendingDelete");

            const btnTolak = document.querySelector(".btnTolakApprovalDelete");
            const btnSetuju = document.querySelector(".btnSetujuApprovalDelete");
            const btnCloseTolak = document.getElementById("closePopupTolakPendingDelete");
            const btnBatalTolak = document.getElementById("btnBatalTolakPendingDelete");
            const btnKonfTolak = document.getElementById("btnKonfirmasiTolakPendingDelete");

            // ======================================================
            // === OPEN POPUP APPROVAL DELETE (EVENT DELEGATION) ===
            // ======================================================
            document.addEventListener("click", function(e) {
                const btn = e.target.closest(".tombolApprovePendingDeletePopup");
                if (!btn) return;

                const idBA = btn.dataset.id || "-";
                const jenisBA = btn.dataset.jenisBa || "-";
                const approver = btn.dataset.namaApprover || "-";
                const alasanDelete = btn.dataset.alasanDelete || "-";
                const alasanBox = document.getElementById("alasanDeleteBox");

                document.getElementById("info-id-ba-delete").textContent = idBA;
                document.getElementById("info-jenis-ba-delete").textContent = jenisBA;
                document.getElementById("info-approver-delete").textContent = approver;
                document.getElementById("isiAlasanDelete").textContent = alasanDelete;

                alasanBox.style.display = alasanDelete.trim() !== "" ? "block" : "none";

                box.classList.add("aktifPopup", "scale-in-center");
                box.classList.remove("scale-out-center");
                background.classList.add("aktifPopup", "fade-in");
                background.classList.remove("fade-out");
            });

            // ======================================================
            // === TOMBOL TOLAK (BUKA POPUP TOLAK) ===
            // ======================================================
            btnTolak.addEventListener("click", function() {
                bgTolak.style.display = "block";
                popupTolak.style.display = "flex";
            });

            // ======================================================
            // === KONFIRMASI TOLAK ===
            // ======================================================
            btnKonfTolak.addEventListener("click", function() {

                const idBA = document.getElementById("info-id-ba-delete").textContent;
                const jenisBA = document.getElementById("info-jenis-ba-delete").textContent;
                const approver = document.getElementById("info-approver-delete").textContent;

                // if (!confirm("Apakah Anda yakin ingin MENOLAK pengajuan ini?")) return;

                fetch("proses_approval_delete.php", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/x-www-form-urlencoded"
                        },
                        body: "id_ba=" + encodeURIComponent(idBA) +
                            "&jenisBA=" + encodeURIComponent(jenisBA) +
                            "&approver=" + encodeURIComponent(approver) +
                            "&aksi=tolak"
                    })
                    .then(res => res.json())
                    .then(res => {
                        if (res.success === true) {
                            popupTolak.style.display = "none";
                            bgTolak.style.display = "none";
                            window.location.reload();
                        } else {
                            window.alert(res.message || "Gagal memproses penolakan.");
                        }
                    });
            });

            // ======================================================
            // === TOMBOL SETUJU ===
            // ======================================================
            btnSetuju.addEventListener("click", function() {

                const idBA = document.getElementById("info-id-ba-delete").textContent;
                const jenisBA = document.getElementById("info-jenis-ba-delete").textContent;
                const approver = document.getElementById("info-approver-delete").textContent;

                fetch("proses_approval_delete.php", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/x-www-form-urlencoded"
                        },
                        body: "id_ba=" + encodeURIComponent(idBA) +
                            "&jenisBA=" + encodeURIComponent(jenisBA) +
                            "&approver=" + encodeURIComponent(approver) +
                            "&aksi=setuju"
                    })
                    .then(res => res.json())
                    .then(res => {
                        if (res.success === true) {
                            window.location.reload();
                        } else {
                            window.alert(res.message || "Gagal memproses persetujuan.");
                        }
                    });
            });

            // ======================================================
            // === TUTUP POPUP TOLAK ===
            // ======================================================
            btnCloseTolak.addEventListener("click", function(e) {
                e.preventDefault();
                popupTolak.style.display = "none";
                bgTolak.style.display = "none";
            });

            btnBatalTolak.addEventListener("click", function(e) {
                e.preventDefault();
                popupTolak.style.display = "none";
                bgTolak.style.display = "none";
            });

            bgTolak.addEventListener("click", function() {
                popupTolak.style.display = "none";
                bgTolak.style.display = "none";
            });

            popupTolak.addEventListener("click", function(e) {
                e.stopPropagation();
            });

            // ======================================================
            // === TUTUP POPUP APPROVAL DELETE ===
            // ======================================================
            close.addEventListener("click", function() {
                setTimeout(() => {
                    background.classList.remove("aktifPopup");
                    box.classList.remove("aktifPopup");
                }, 300);

                box.classList.remove("scale-in-center");
                box.classList.add("scale-out-center");
                background.classList.remove("fade-in");
                background.classList.add("fade-out");
            });

            background.addEventListener("click", function() {
                setTimeout(() => {
                    background.classList.remove("aktifPopup");
                    box.classList.remove("aktifPopup");
                }, 300);

                box.classList.remove("scale-in-center");
                box.classList.add("scale-out-center");
                background.classList.remove("fade-in");
                background.classList.add("fade-out");
            });

        });
    </script>

    <script>
        //Konfigurasi OverlayScrollbars

        //-----------------------------------------------------------------------------------
        const SELECTOR_SIDEBAR_WRAPPER = '.sidebar-wrapper';
        const Default = {
            scrollbarTheme: 'os-theme-light',
            scrollbarAutoHide: 'leave',
            scrollbarClickScroll: true,
        };
        document.addEventListener('DOMContentLoaded', function() {
            const sidebarWrapper = document.querySelector(SELECTOR_SIDEBAR_WRAPPER);
            if (sidebarWrapper && typeof OverlayScrollbarsGlobal?.OverlayScrollbars !== 'undefined') {
                OverlayScrollbarsGlobal.OverlayScrollbars(sidebarWrapper, {
                    scrollbars: {
                        theme: Default.scrollbarTheme,
                        autoHide: Default.scrollbarAutoHide,
                        clickScroll: Default.scrollbarClickScroll,
                    },
                });
            }
        });
        //-----------------------------------------------------------------------------------
    </script>

    <script>
        const alert = document.querySelector('.infoin-approval');
        setTimeout(() => {
            alert.classList.add('fade-out');
            alert.classList.remove('fade-in');
        }, 3000);
        setTimeout(() => {
            alert.style.display = 'none';
        }, 3500);
    </script>

    <script>
        //Sidebar

        //-----------------------------------------------------------------------------------
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('show');

            // Event listener satu kali untuk klik luar
            if (sidebar.classList.contains('show')) {
                document.addEventListener('click', handleClickOutsideSidebar);
            } else {
                document.removeEventListener('click', handleClickOutsideSidebar);
            }
        }

        function handleClickOutsideSidebar(event) {
            const sidebar = document.getElementById('sidebar');
            const toggleButton = event.target.closest("button[onclick='toggleSidebar()']");

            if (!sidebar.contains(event.target) && !toggleButton) {
                sidebar.classList.remove('show');
                document.removeEventListener('click', handleClickOutsideSidebar);
            }
        }
        //-----------------------------------------------------------------------------------
    </script>

    <script>
        //Tanggal

        //-----------------------------------------------------------------------------------
        function updateDate() {
            const now = new Date();
            const options = {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            };
            const formattedDate = now.toLocaleDateString('id-ID', options);
            document.getElementById('date').textContent = formattedDate;
        }
        setInterval(updateDate, 1000); // Update setiap detik
        updateDate(); // Panggil langsung saat halaman load
        //-----------------------------------------------------------------------------------
    </script>

    <script>
        // Jam Digital
        //-----------------------------------------------------------------------------------

        function updateClock() {
            const now = new Date();
            const jam = String(now.getHours()).padStart(2, '0');
            const menit = String(now.getMinutes()).padStart(2, '0');
            const detik = String(now.getSeconds()).padStart(2, '0');
            document.getElementById('clock').textContent = `${jam}:${menit}:${detik}`;
        }

        setInterval(updateClock, 1000);
        updateClock(); // Panggil langsung saat halaman load
        //-----------------------------------------------------------------------------------
    </script>

</body>

</html>