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

// Ambil data dari form dengan fallback kosong (PHP 5.6 compatible)
$nomor_ba           = isset($_POST['nomor_ba']) ? $_POST['nomor_ba'] : '-';
$tanggal            = isset($_POST['tanggal']) ? $_POST['tanggal'] : '-';
$sn                 = isset($_POST['sn']) ? $_POST['sn'] : '-';
$no_po              = isset($_POST['nomor_po']) ? $_POST['nomor_po'] : '-';
$merek              = isset($_POST['merek']) ? $_POST['merek'] : '-';
$type               = isset($_POST['type']) ? $_POST['type'] : '-';
$jenis_perangkat    = isset($_POST['jenis_perangkat']) ? $_POST['jenis_perangkat'] : '-';
$tanggal_pembelian  = isset($_POST['tanggal_pembelian']) ? $_POST['tanggal_pembelian'] : '-';
$satuan             = isset($_POST['satuan']) ? $_POST['satuan'] : '-';
$cpu                = isset($_POST['cpu']) ? $_POST['cpu'] : '-';
$os                 = isset($_POST['os']) ? $_POST['os'] : '-';
$ram                = isset($_POST['ram']) ? $_POST['ram'] : '-';
$storage            = isset($_POST['storage']) ? $_POST['storage'] : '-';
$gpu                = isset($_POST['gpu']) ? $_POST['gpu'] : '-';
$display            = isset($_POST['display']) ? $_POST['display'] : '-';
$lain               = isset($_POST['lain']) ? $_POST['lain'] : '-';
$merkmonitor        = isset($_POST['merkmonitor']) ? $_POST['merkmonitor'] : '-';
$snmonitor          = isset($_POST['snmonitor']) ? $_POST['snmonitor'] : '-';
$merkkeyboard       = isset($_POST['merkkeyboard']) ? $_POST['merkkeyboard'] : '-';
$snkeyboard         = isset($_POST['snkeyboard']) ? $_POST['snkeyboard'] : '-';
$merkmouse          = isset($_POST['merkmouse']) ? $_POST['merkmouse'] : '-';
$snmouse            = isset($_POST['snmouse']) ? $_POST['snmouse'] : '-';
$qtyid              = isset($_POST['qtyid']) ? $_POST['qtyid'] : '-';
$kode_asset         = isset($_POST['kode']) ? $_POST['kode'] : '-';
$user               = isset($_POST['user']) ? $_POST['user'] : '-';

$pt                 = isset($_POST['pt']) ? $_POST['pt'] : '-';
$lokasi             = isset($_POST['lokasi']) ? $_POST['lokasi'] : '-';
$peminjam           = isset($_POST['peminjam']) ? $_POST['peminjam'] : '-';
$atasan_peminjam    = isset($_POST['atasan_peminjam']) ? $_POST['atasan_peminjam'] : '-';
$alamat_peminjam    = isset($_POST['alamat_peminjam']) ? $_POST['alamat_peminjam'] : '-';

$dt = DateTime::createFromFormat('d-m-Y', $_POST['tanggal_pembelian']);
$tanggal_pembelian = $dt ? $dt->format('Y-m-d') : null;

// var_dump($tanggal_pembelian);
// echo '<pre>';
// echo "=== DEBUG POST ===\n";
// print_r($_POST);

// echo '</pre>';
// exit;

$pt_id = 0;
if ($pt === 'PT.MSAL (HO)'):
    $pt_id = 1;
elseif ($pt === 'PT.MSAL (SITE)'):
    $pt_id = 3;
else:
    $pt_id = 0;
endif;

// Ambil nama pembuat dari session
$nama_pembuat = isset($_SESSION['nama']) ? $_SESSION['nama'] : '-';
$pihak_pertama = '';

$q = $koneksi->query("SELECT nama FROM data_karyawan WHERE posisi = 'Direktur MIS' LIMIT 1");

if ($q && $row = $q->fetch_assoc()) {
    $pihak_pertama = $row['nama'];
}
$pihak_pertama = $pihak_pertama ?: '-';

// Set nama approver berdasarkan lokasi PT
if ($pt === 'PT.MSAL (HO)') {
    $saksi = '';
    $q = $koneksi->query("SELECT nama FROM data_karyawan WHERE posisi = 'MIS Dept. Head' LIMIT 1");

    if ($q && $row = $q->fetch_assoc()) {
        $saksi = $row['nama'];
    }
    $saksi = $saksi ?: '-';

    $diketahui = '';
    $q = $koneksi->query("SELECT nama FROM data_karyawan WHERE posisi = 'HRGA Dept. Head' LIMIT 1");

    if ($q && $row = $q->fetch_assoc()) {
        $diketahui = $row['nama'];
    }
    $diketahui = $diketahui ?: '-';
} else {

    $saksi = '';
    $q = $koneksi->query("SELECT nama FROM data_karyawan_test WHERE posisi = 'KTU' AND pt = '$pt' LIMIT 1");

    if ($q && $row = $q->fetch_assoc()) {
        $saksi = $row['nama'];
    }
    $saksi = $saksi ?: '-';

    $diketahui = '';
    $q = $koneksi->query("SELECT nama FROM data_karyawan_test WHERE posisi = 'Staf GA' AND pt = '$pt' LIMIT 1");

    if ($q && $row = $q->fetch_assoc()) {
        $diketahui = $row['nama'];
    }
    $diketahui = $diketahui ?: '-';
}

// Set nilai approval
$approval_1 = 0;
$approval_2 = 0;
$approval_3 = 0;
$approval_4 = 0;

if ($pt === 'PT.MSAL (HO)') {
    if ($diketahui === $peminjam) {
        $diketahui = '-';
    }
    // if ($diketahui === $atasan_peminjam) {
    //     $atasan_peminjam = '-';
    // }
    // if ($saksi === $atasan_peminjam) {
    //     $atasan_peminjam = '-';
    // }
    if ($saksi === $peminjam) {
        $q = $koneksi->query("SELECT nama FROM data_karyawan WHERE posisi = 'IT Support' LIMIT 1");

        if ($q && $row = $q->fetch_assoc()) {
            $saksi = $row['nama'];
        }
        $saksi = $saksi ?: '-';
    }
}
// elseif ($pt === 'PT.MSAL (SITE)') {

// }

if ($lokasi !== '-' || $lokasi !== '') {
    // Format lokasi
    if (preg_match('/^LT\.(\d+)/i', $lokasi, $match)) {
        $lokasi = 'Lantai ' . $match[1];
    } else {
        $lokasi = $lokasi;
    }
}

// echo '<pre>';
// var_dump($tanggal_pembelian);

// echo '</pre>';
// exit;
// Simpan ke database
$sql = "INSERT INTO ba_serah_terima_asset
(
nomor_ba, tanggal, pt, id_pt, lokasi, nama_pembuat, peminjam, atasan_peminjam, alamat_peminjam, sn, merek, type, satuan, cpu, os, ram, storage, gpu, display, lain, merk_monitor,
sn_monitor, merk_keyboard, sn_keyboard, merk_mouse, sn_mouse, categories, qty_id, kode_assets, no_po, tgl_pembelian, user, pihak_pertama, saksi, diketahui, approval_1,
approval_2, approval_3, approval_4
) 
VALUES (
?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 
?, ?, ?
)";

$stmt = $koneksi->prepare($sql);

if ($pt === 'PT.MSAL (HO)'){
$stmt->bind_param(
    "sssisssssssssssssssssssssssisssssssiiii",
    $nomor_ba, $tanggal, $pt, $pt_id, $lokasi, $nama_pembuat, $peminjam, $atasan_peminjam, $alamat_peminjam, $sn, $merek, $type, $satuan, $cpu, $os, $ram, $storage, $gpu, $display, $lain, $merkmonitor,
    $snmonitor, $merkkeyboard, $snkeyboard, $merkmouse, $snmouse, $jenis_perangkat, $qtyid, $kode_asset, $no_po, $tanggal_pembelian, $user, $pihak_pertama, $saksi, $diketahui, $approval_1,
    $approval_2, $approval_3, $approval_4
);
}
// elseif ($pt === 'PT.MSAL (SITE)' || $pt !== 'PT.MSAL (HO)'){
elseif ($pt !== 'PT.MSAL (HO)'){
$stmt->bind_param(
    "sssisssssssssssssssssssssssisssssssiiii",
    $nomor_ba, $tanggal, $pt, $pt_id, $lokasi, $nama_pembuat, $peminjam, $atasan_peminjam, $alamat_peminjam, $sn, $merek, $type, $satuan, $cpu, $os, $ram, $storage, $gpu, $display, $lain, $merkmonitor,
    $snmonitor, $merkkeyboard, $snkeyboard, $merkmouse, $snmouse, $jenis_perangkat, $qtyid, $kode_asset, $no_po, $tanggal_pembelian, $user, $pihak_pertama, $saksi, $diketahui, $approval_1,
    $approval_2, $approval_3, $approval_4
);
}

if ($stmt->execute()) {

    $stmt->close();
    $koneksi->close();

    $_SESSION['message'] = "Data berita acara berhasil dibuat.";
    header("Location: ba_serah-terima-asset.php?status=sukses");
    exit();
} else {

    $_SESSION['message'] = "Gagal menyimpan data berita acara: " . mysqli_error($koneksi);
    header("Location: ba_serah-terima-asset.php?status=gagal");
    exit();
}
