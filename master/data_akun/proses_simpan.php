<?php
session_start();
require_once "../../koneksi.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo "Akses tidak valid.";
    exit;
}

function normalize_pt_string($ptString) {
    $ptString = (string)$ptString;
    $arr = explode(',', $ptString);
    $out = array();
    foreach ($arr as $p) {
        $p = trim($p);
        if ($p !== '' && !in_array($p, $out, true)) $out[] = $p;
    }
    return $out;
}

// =======================
// 1) Ambil data dari form
// =======================
$selectedPT = isset($_POST['pt']) ? trim($_POST['pt']) : '';   // hanya 1 PT (filter)
$nikPost    = isset($_POST['nik']) ? trim($_POST['nik']) : ''; // NIK dari option data-nik
$namaPost   = isset($_POST['nama']) ? trim($_POST['nama']) : '';

$username  = isset($_POST['username']) ? trim($_POST['username']) : '';
$password  = isset($_POST['password']) ? trim($_POST['password']) : '';
$email     = isset($_POST['email']) ? trim($_POST['email']) : '';
$hak_akses = isset($_POST['hakakses']) ? trim($_POST['hakakses']) : '';
$signature_data = isset($_POST['signature_data']) ? $_POST['signature_data'] : '';

// =======================
// 2) Validasi wajib isi
// =======================
if ($selectedPT === '' || $nikPost === '' || $namaPost === '' || $username === '' || $password === '' || $email === '' || $hak_akses === '' || $signature_data === '') {
    $_SESSION['message'] = 'Semua field wajib diisi! (PT/Nama/NIK/Username/Password/Email/Peran/TTD)';
    $_SESSION['success'] = false;
    header("Location: tabel.php");
    exit;
}

$nik = (int)$nikPost;
if ($nik <= 0) {
    $_SESSION['message'] = 'NIK tidak valid.';
    $_SESSION['success'] = false;
    header("Location: tabel.php");
    exit;
}

// =======================
// 3) Decode TTD
// =======================
$autograph = null;
$raw = preg_replace('#^data:image/\w+;base64,#i', '', $signature_data);
$bin = base64_decode($raw, true);
if ($bin !== false && $bin !== '') $autograph = $bin;

if ($autograph === null) {
    $_SESSION['message'] = 'Tanda tangan tidak valid / kosong.';
    $_SESSION['success'] = false;
    header("Location: tabel.php");
    exit;
}

// =======================
// 4) Validasi akses server-side
// =======================
$isSuper = (isset($_SESSION['hak_akses']) && $_SESSION['hak_akses'] === 'Super Admin')
        || (isset($_SESSION['nama']) && $_SESSION['nama'] === 'Rizki Sunandar');

$manajemenAkses = isset($_SESSION['manajemen_akun_akses']) ? (int)$_SESSION['manajemen_akun_akses'] : 0;

if (!$isSuper && $manajemenAkses !== 2) {
    $_SESSION['message'] = 'Anda tidak punya akses untuk membuat akun.';
    $_SESSION['success'] = false;
    header("Location: tabel.php");
    exit;
}

// PT user list dari session (bisa array / string)
$ptUserList = isset($_SESSION['pt']) ? $_SESSION['pt'] : array();
if (!is_array($ptUserList)) {
    $ptUserList = array_map('trim', explode(',', (string)$ptUserList));
}
$tmp = array();
foreach ($ptUserList as $p) {
    $p = trim($p);
    if ($p !== '' && !in_array($p, $tmp, true)) $tmp[] = $p;
}
$ptUserList = $tmp;

// minimal: PT yang dipilih harus ada di PT user (kalau bukan super)
if (!$isSuper) {
    if (!in_array($selectedPT, $ptUserList, true)) {
        $_SESSION['message'] = 'Gagal buat akun: PT "' . $selectedPT . '" tidak termasuk akses anda.';
        $_SESSION['success'] = false;
        header("Location: tabel.php");
        exit;
    }
}

// =======================
// 5) Ambil data karyawan by NIK + tentukan PT yang disimpan
// =======================
$ptStored = '';
$ptStoredList = array();
$namaFinal = $namaPost; // nanti akan kita ambil dari DB biar anti-manipulasi

$normPT = "REPLACE(REPLACE(pt, ', ', ','), ' ,', ',')";

if ($selectedPT === "PT.MSAL (HO)") {

    $sql = "SELECT nama, nik FROM data_karyawan WHERE nik = ? LIMIT 1";
    $stmt = $koneksi->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $nik);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && ($row = $res->fetch_assoc())) {
            $namaFinal = trim((string)$row['nama']);
        }
        $stmt->close();
    }

    if ($namaFinal === '') {
        $_SESSION['message'] = 'Gagal buat akun: NIK tidak ditemukan di data_karyawan (HO).';
        $_SESSION['success'] = false;
        header("Location: tabel.php");
        exit;
    }

    // HO tidak multi PT
    $ptStoredList = array("PT.MSAL (HO)");
    $ptStored = "PT.MSAL (HO)";

} else {

    // ambil pt lengkap dari data_karyawan_test by nik
    $sql = "SELECT nama, nik, pt FROM data_karyawan_test WHERE nik = ? LIMIT 1";
    $stmt = $koneksi->prepare($sql);
    $ptDb = '';
    if ($stmt) {
        $stmt->bind_param("i", $nik);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && ($row = $res->fetch_assoc())) {
            $namaFinal = trim((string)$row['nama']);
            $ptDb = (string)$row['pt'];
        }
        $stmt->close();
    }

    if ($namaFinal === '' || $ptDb === '') {
        $_SESSION['message'] = 'Gagal buat akun: NIK tidak ditemukan di data_karyawan_test.';
        $_SESSION['success'] = false;
        header("Location: tabel.php");
        exit;
    }

    // validasi: selectedPT harus ada di pt karyawan (menggunakan FIND_IN_SET dengan normalisasi)
    $sqlCheck = "SELECT nik FROM data_karyawan_test WHERE nik = ? AND FIND_IN_SET(?, {$normPT}) > 0 LIMIT 1";
    $stmtC = $koneksi->prepare($sqlCheck);
    $ok = false;
    if ($stmtC) {
        $stmtC->bind_param("is", $nik, $selectedPT);
        $stmtC->execute();
        $resC = $stmtC->get_result();
        if ($resC && $resC->fetch_assoc()) $ok = true;
        $stmtC->close();
    }
    if (!$ok) {
        $_SESSION['message'] = 'Gagal buat akun: NIK tidak terdaftar pada PT "' . $selectedPT . '" di data_karyawan_test.';
        $_SESSION['success'] = false;
        header("Location: tabel.php");
        exit;
    }

    $ptStoredList = normalize_pt_string($ptDb);
    $ptStored = implode(', ', $ptStoredList);
}

// kalau bukan super, pastikan semua PT yang akan disimpan masih dalam akses pembuat
if (!$isSuper) {
    $notAllowed = array();
    foreach ($ptStoredList as $p) {
        if (!in_array($p, $ptUserList, true)) $notAllowed[] = $p;
    }
    if (count($notAllowed) > 0) {
        $_SESSION['message'] = 'Gagal buat akun: PT berikut di luar akses anda: ' . implode(', ', $notAllowed);
        $_SESSION['success'] = false;
        header("Location: tabel.php");
        exit;
    }
}

// =======================
// 6) Cegah username duplicate
// =======================
$cek = $koneksi->prepare("SELECT id FROM akun_akses WHERE username = ? LIMIT 1");
if ($cek) {
    $cek->bind_param("s", $username);
    $cek->execute();
    $cekRes = $cek->get_result();
    if ($cekRes && $cekRes->fetch_assoc()) {
        $_SESSION['message'] = 'Username sudah digunakan.';
        $_SESSION['success'] = false;
        $cek->close();
        header("Location: tabel.php");
        exit;
    }
    $cek->close();
}

// =======================
// 7) INSERT akun_akses
// =======================
$sql = "INSERT INTO akun_akses (pt, nama, nik, username, password, email, hak_akses, autograph)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $koneksi->prepare($sql);

if (!$stmt) {
    $_SESSION['message'] = 'Gagal buat akun: prepare insert error.';
    $_SESSION['success'] = false;
    header("Location: tabel.php");
    exit;
}

$null = NULL;
$stmt->bind_param("ssissssb", $ptStored, $namaFinal, $nik, $username, $password, $email, $hak_akses, $null);
$stmt->send_long_data(7, $autograph);

if ($stmt->execute()) {
    $_SESSION['message'] = 'Sukses buat akun';
    $_SESSION['success'] = true;
} else {
    $_SESSION['message'] = 'Gagal buat akun: ' . $stmt->error;
    $_SESSION['success'] = false;
}

$stmt->close();
$koneksi->close();

header("Location: tabel.php");
exit;
