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

require_once '../koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['message'] = "Invalid request method.";
    header("Location: ba_serah-terima-asset.php?status=gagal");
    exit();
}

// Ambil nama pembuat dari session
$nama_pembuat = isset($_SESSION['nama']) ? $_SESSION['nama'] : '-';

// Ambil data dari form dengan fallback kosong
$id                 = isset($_POST['id']) ? intval($_POST['id']) : 0;
$nomor_ba           = isset($_POST['nomor_ba']) ? $_POST['nomor_ba'] : '-';
$nomor_ba           = str_pad($nomor_ba, 3, '0', STR_PAD_LEFT);
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
$id_pt              = isset($_POST['id_pt']) ? $_POST['id_pt'] : '-';

$peminjam           = isset($_POST['peminjam']) ? $_POST['peminjam'] : '-';
$lokasi_input       = isset($_POST['lokasi']) ? $_POST['lokasi'] : '';
$atasan_peminjam    = isset($_POST['atasan_peminjam']) ? $_POST['atasan_peminjam'] : '-';
$alamat_peminjam    = isset($_POST['alamat_peminjam']) ? $_POST['alamat_peminjam'] : '-';

$alasan_perubahan   = isset($_POST['alasan_perubahan']) ? $_POST['alasan_perubahan'] : '-';

$dt = DateTime::createFromFormat('d-m-Y', $_POST['tanggal_pembelian']);
$tanggal_pembelian = $dt ? $dt->format('Y-m-d') : null;

// var_dump($tanggal_pembelian);
// echo '<br>';
// var_dump($id);
// echo '<pre>';
// echo "=== DEBUG POST ===\n";
// print_r($_POST);

// echo '</pre>';


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

// Set kondisi apabila ada nama approver yang sama
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

if ($lokasi_input !== '-' || $lokasi_input !== '') {
    // Format lokasi
    if (preg_match('/^LT\.(\d+)/i', $lokasi_input, $match)) {
        $lokasi = 'Lantai ' . $match[1];
    } else {
        $lokasi = $lokasi_input;
    }
}

// === DATA LAMA UNTUK HISTORI ===
$old_stmt = $koneksi->prepare("SELECT 
    id, nomor_ba, tanggal, pt, id_pt, lokasi,  
    sn, merek, type, satuan, cpu, os, ram, storage, gpu, display, lain, 
    merk_monitor, sn_monitor, merk_keyboard, sn_keyboard, merk_mouse, sn_mouse, 
    categories, qty_id, kode_assets, no_po, tgl_pembelian, user, 
    nama_pembuat, peminjam, atasan_peminjam, alamat_peminjam, pihak_pertama, saksi, diketahui, 
    approval_1, approval_2, approval_3, approval_4,
    autograph_1, autograph_2, autograph_3, autograph_4,
    tanggal_approve_1, tanggal_approve_2, tanggal_approve_3, tanggal_approve_4,
    created_at
FROM ba_serah_terima_asset WHERE id = ?");
$old_stmt->bind_param("i", $id);
$old_stmt->execute();
$old_result = $old_stmt->get_result();
$old_data = $old_result->fetch_assoc();
$old_stmt->close();

// --- simpan ke variabel _lama dari $old_data (aman: cek kedua kemungkinan key)
$pihak_pertama_lama = isset($old_data['pihak_pertama']) ? trim($old_data['pihak_pertama']) : (isset($old_data['pihak_pertama']) ? trim($old_data['pihak_pertama']) : '');
$peminjam_lama = isset($old_data['peminjam']) ? trim($old_data['peminjam']) : (isset($old_data['peminjam']) ? trim($old_data['peminjam']) : '');
$atasan_peminjam_lama = isset($old_data['atasan_peminjam']) ? trim($old_data['atasan_peminjam']) : (isset($old_data['atasan_peminjam']) ? trim($old_data['atasan_peminjam']) : '');
$saksi_lama = isset($old_data['saksi']) ? trim($old_data['saksi']) : (isset($old_data['saksi']) ? trim($old_data['saksi']) : '');
$diketahui_lama = isset($old_data['diketahui']) ? trim($old_data['diketahui']) : (isset($old_data['diketahui']) ? trim($old_data['diketahui']) : '');
$lokasi_lama = isset($old_data['lokasi']) ? trim($old_data['lokasi']) : (isset($old_data['lokasi']) ? trim($old_data['lokasi']) : '');


// =============================================
// GATHER DATA LAMA DAN BARU
// =============================================

$old_data_array = [
    'id'                => $old_data['id'],
    'nomor_ba'          => $old_data['nomor_ba'],
    'tanggal'           => $old_data['tanggal'],
    'pt'                => $old_data['pt'],
    'id_pt'             => $old_data['id_pt'],
    'lokasi'            => $old_data['lokasi'],

    'sn'                => $old_data['sn'],
    'merek'             => $old_data['merek'],
    'type'              => $old_data['type'],
    'satuan'            => $old_data['satuan'],
    'cpu'               => $old_data['cpu'],
    'os'                => $old_data['os'],
    'ram'               => $old_data['ram'],
    'storage'           => $old_data['storage'],
    'gpu'               => $old_data['gpu'],
    'display'           => $old_data['display'],
    'lain'              => $old_data['lain'],

    'merk_monitor'      => $old_data['merk_monitor'],
    'sn_monitor'        => $old_data['sn_monitor'],
    'merk_keyboard'     => $old_data['merk_keyboard'],
    'sn_keyboard'       => $old_data['sn_keyboard'],
    'merk_mouse'        => $old_data['merk_mouse'],
    'sn_mouse'          => $old_data['sn_mouse'],
    
    'categories'        => $old_data['categories'],
    'qty_id'            => $old_data['qty_id'],
    'kode_assets'       => $old_data['kode_assets'],
    'no_po'             => $old_data['no_po'],
    'tgl_pembelian'     => $old_data['tgl_pembelian'],
    'user'              => $old_data['user'],

    'nama_pembuat'      => $old_data['nama_pembuat'],
    'peminjam'          => $old_data['peminjam'],
    'atasan_peminjam'   => $old_data['atasan_peminjam'],
    'alamat_peminjam'   => $old_data['alamat_peminjam'],
    'pihak_pertama'     => $old_data['pihak_pertama'],
    'saksi'             => $old_data['saksi'],
    'diketahui'         => $old_data['diketahui'],

    'approval_1'        => $old_data['approval_1'],
    'approval_2'        => $old_data['approval_2'],
    'approval_3'        => $old_data['approval_3'],
    'approval_4'        => $old_data['approval_4'],

    'autograph_1'       => $old_data['autograph_1'],
    'autograph_2'       => $old_data['autograph_2'],
    'autograph_3'       => $old_data['autograph_3'],
    'autograph_4'       => $old_data['autograph_4'],

    'tanggal_approve_1' => $old_data['tanggal_approve_1'],
    'tanggal_approve_2' => $old_data['tanggal_approve_2'],
    'tanggal_approve_3' => $old_data['tanggal_approve_3'],
    'tanggal_approve_4' => $old_data['tanggal_approve_4'],

    'created_at' => $old_data['created_at']
];


$new_data_array = [
    'id'                => $id,
    'nomor_ba'          => $nomor_ba,
    'tanggal'           => $tanggal,
    'pt'                => $pt,
    'id_pt'             => $id_pt,
    'lokasi'            => $lokasi,

    'sn'                => $sn,
    'merek'             => $merek,
    'type'              => $type,
    'satuan'            => $satuan,
    'cpu'               => $cpu,
    'os'                => $os,
    'ram'               => $ram,
    'storage'           => $storage,
    'gpu'               => $gpu,
    'display'           => $display,
    'lain'              => $lain,

    'merk_monitor'      => $merkmonitor,
    'sn_monitor'        => $snmonitor,
    'merk_keyboard'     => $merkkeyboard,
    'sn_keyboard'       => $snkeyboard,
    'merk_mouse'        => $merkmouse,
    'sn_mouse'          => $snmouse,

    'categories'        => $jenis_perangkat,
    'qty_id'            => $qtyid,
    'kode_assets'       => $kode_asset,
    'no_po'             => $no_po,
    'tgl_pembelian'     => $tanggal_pembelian,
    'user'              => $user,

    'nama_pembuat'      => $old_data['nama_pembuat'],
    'peminjam'          => $peminjam,
    'atasan_peminjam'   => $atasan_peminjam,
    'alamat_peminjam'   => $alamat_peminjam,
    'pihak_pertama'     => $old_data['pihak_pertama'],
    'saksi'             => $saksi,
    'diketahui'         => $diketahui,

    'approval_1'        => $old_data['approval_1'],
    'approval_2'        => $old_data['approval_2'],
    'approval_3'        => $old_data['approval_3'],
    'approval_4'        => $old_data['approval_4'],

    'autograph_1'       => $old_data['autograph_1'],
    'autograph_2'       => $old_data['autograph_2'],
    'autograph_3'       => $old_data['autograph_3'],
    'autograph_4'       => $old_data['autograph_4'],

    'tanggal_approve_1' => $old_data['tanggal_approve_1'],
    'tanggal_approve_2' => $old_data['tanggal_approve_2'],
    'tanggal_approve_3' => $old_data['tanggal_approve_3'],
    'tanggal_approve_4' => $old_data['tanggal_approve_4'],

    'created_at' => $old_data['created_at']
];

// =============================================
// CEK PERUBAHAN APPROVER DAN RESET OTOMATIS
// =============================================

// mapping kolom approval berdasarkan urutan
$approval_map = [
    1 => 'peminjam',
    2 => 'saksi',
    3 => 'diketahui',
    4 => 'pihak_pertama',
];

foreach ($approval_map as $num => $field) {
    // ambil nama kolom terkait
    $approval_col = "approval_{$num}";
    $autograph_col = "autograph_{$num}";
    $tanggal_col = "tanggal_approve_{$num}";

    // bandingkan data lama dan baru
    if (isset($old_data[$field]) && isset($new_data_array[$field]) && $old_data[$field] !== $new_data_array[$field]) {
        // kalau beda → reset
        $new_data_array[$approval_col] = 0;
        $new_data_array[$autograph_col] = NULL;
        $new_data_array[$tanggal_col] = NULL;
    } else {
        // kalau sama → pertahankan dari data lama
        $new_data_array[$approval_col] = $old_data[$approval_col];
        $new_data_array[$autograph_col] = $old_data[$autograph_col];
        $new_data_array[$tanggal_col] = $old_data[$tanggal_col];
    }
}

// echo "<pre>";
// print_r($old_data_array);
// echo "</pre>";

// echo "<pre>";
// print_r($new_data_array);
// echo "</pre>";

// =================================================================
// PROSES AMBIL DATA UNTUK HISTORI
// array approver baru
$approver_baru = [
    'peminjam' => trim($peminjam),
    'saksi' => trim($saksi),
    'diketahui' => trim($diketahui),
    // 'atasan_peminjam' => trim($atasan_peminjam),
    'pihak_pertama' => trim($pihak_pertama),
    'pt' => trim($pt),
    'lokasi' => trim($lokasi)
];

// array approver lama untuk loop
$lama_map = [
    'peminjam'              => $peminjam_lama,
    'saksi'                 => $saksi_lama,
    'diketahui'             => $diketahui_lama,
    // 'atasan_peminjam'       => $atasan_peminjam_lama,
    'pihak_pertama'         => $pihak_pertama_lama,
    'pt'                    => isset($old_data['pt']) ? $old_data['pt'] : '-',
    'lokasi'                => $lokasi_lama
];

$perbedaan_approver = [];

foreach ($approver_baru as $key => $val_baru) {
    $val_lama = isset($lama_map[$key]) ? $lama_map[$key] : '';
    if ($val_lama !== $val_baru) {
        $perbedaan_approver[$key] = [
            'lama' => ($val_lama === '' ? '(-)' : $val_lama),
            'baru' => ($val_baru === '' ? '(-)' : $val_baru)
        ];
    }
}

$new_data_full = [
    'nomor_ba'          => $nomor_ba,
    'tanggal'           => $tanggal,
    'pt'                => $pt,
    'id_pt'             => $id_pt,
    'lokasi'            => $lokasi,

    'sn'                => $sn,
    'merek'             => $merek,
    'type'              => $type,
    'satuan'            => $satuan,
    'cpu'               => $cpu,
    'os'                => $os,
    'ram'               => $ram,
    'storage'           => $storage,
    'gpu'               => $gpu,
    'display'           => $display,
    'lain'              => $lain,

    'merk_monitor'      => $merkmonitor,
    'sn_monitor'        => $snmonitor,
    'merk_keyboard'     => $merkkeyboard,
    'sn_keyboard'       => $snkeyboard,
    'merk_mouse'        => $merkmouse,
    'sn_mouse'          => $snmouse,

    'categories'        => $jenis_perangkat,
    'qty_id'            => $qtyid,
    'kode_assets'       => $kode_asset,
    'no_po'             => $no_po,
    'tgl_pembelian'     => $tanggal_pembelian,
    'user'              => $user,

    'nama_pembuat'      => $old_data['nama_pembuat'],
    'atasan_peminjam'   => $atasan_peminjam,
    'alamat_peminjam'   => $alamat_peminjam,
    
];

$old_data_full = [
    'nomor_ba'        => isset($old_data['nomor_ba']) ? $old_data['nomor_ba'] : '-',
    'tanggal'         => isset($old_data['tanggal']) ? $old_data['tanggal'] : '-',
    'pt'              => isset($old_data['pt']) ? $old_data['pt'] : '-',
    'id_pt'           => isset($old_data['id_pt']) ? $old_data['id_pt'] : '-',
    'lokasi'          => isset($old_data['lokasi']) ? $old_data['lokasi'] : '-',

    'sn'              => isset($old_data['sn']) ? $old_data['sn'] : '-',
    'merek'           => isset($old_data['merek']) ? $old_data['merek'] : '-',
    'type'            => isset($old_data['type']) ? $old_data['type'] : '-',
    'satuan'          => isset($old_data['satuan']) ? $old_data['satuan'] : '-',
    'cpu'             => isset($old_data['cpu']) ? $old_data['cpu'] : '-',
    'os'              => isset($old_data['os']) ? $old_data['os'] : '-',
    'ram'             => isset($old_data['ram']) ? $old_data['ram'] : '-',
    'storage'         => isset($old_data['storage']) ? $old_data['storage'] : '-',
    'gpu'             => isset($old_data['gpu']) ? $old_data['gpu'] : '-',
    'display'         => isset($old_data['display']) ? $old_data['display'] : '-',
    'lain'            => isset($old_data['lain']) ? $old_data['lain'] : '-',

    'merk_monitor'    => isset($old_data['merk_monitor']) ? $old_data['merk_monitor'] : '-',
    'sn_monitor'      => isset($old_data['sn_monitor']) ? $old_data['sn_monitor'] : '-',
    'merk_keyboard'   => isset($old_data['merk_keyboard']) ? $old_data['merk_keyboard'] : '-',
    'sn_keyboard'     => isset($old_data['sn_keyboard']) ? $old_data['sn_keyboard'] : '-',
    'merk_mouse'      => isset($old_data['merk_mouse']) ? $old_data['merk_mouse'] : '-',
    'sn_mouse'        => isset($old_data['sn_mouse']) ? $old_data['sn_mouse'] : '-',

    'categories'      => isset($old_data['categories']) ? $old_data['categories'] : '-',
    'qty_id'          => isset($old_data['qty_id']) ? $old_data['qty_id'] : '-',
    'kode_assets'     => isset($old_data['kode_assets']) ? $old_data['kode_assets'] : '-',
    'no_po'           => isset($old_data['no_po']) ? $old_data['no_po'] : '-',
    'tgl_pembelian'   => isset($old_data['tgl_pembelian']) ? $old_data['tgl_pembelian'] : '-',
    'user'            => isset($old_data['user']) ? $old_data['user'] : '-',

    'nama_pembuat'    => isset($old_data['nama_pembuat']) ? $old_data['nama_pembuat'] : '-',
    'atasan_peminjam' => isset($old_data['atasan_peminjam']) ? $old_data['atasan_peminjam'] : '-',
    'alamat_peminjam' => isset($old_data['alamat_peminjam']) ? $old_data['alamat_peminjam'] : '-',
];

$perbedaan_data = [];

foreach ($new_data_full as $key => $val_baru) {
    $val_lama = isset($old_data_full[$key]) ? $old_data_full[$key] : '';
    if ($val_lama != $val_baru) {
        $perbedaan_data[$key] = [
            'lama' => ($val_lama === '' ? '(-)' : $val_lama),
            'baru' => ($val_baru === '' ? '(-)' : $val_baru)
        ];
    }
}


// echo "<pre>";
// print_r($id);
// echo "</pre>";
// echo "<pre>";
// print_r($new_data_full);
// echo "</pre>";
// echo "<pre>";
// print_r($old_data_full);
// echo "</pre>";
// echo "<pre>";
// print_r($perbedaan_data);
// echo "</pre>";

// echo "<pre>=== DATA YANG BERBEDA (hanya tampilkan yang berubah) ===\n";
// foreach ($perbedaan_data as $key => $data) {
//     echo strtoupper($key) . ": {$data['lama']} => {$data['baru']}\n";
// }
// echo "</pre>";

// echo "<pre>";
// print_r($approver_baru);
// echo "</pre>";
// echo "<pre>";
// print_r($lama_map);
// echo "</pre>";
// echo "<pre>";
// print_r($perbedaan_approver);
// echo "</pre>";

// echo "<pre>=== DATA APPROVER YANG BERBEDA (hanya tampilkan yang berubah) ===\n";
// foreach ($perbedaan_approver as $key => $data) {
//     echo strtoupper($key) . ": {$data['lama']} => {$data['baru']}\n";
// }
// echo "</pre>";

// exit;

// =================================================================

//}
// =============================
// CATAT HISTORI PERUBAHAN
// =============================

$new_data = [
    'nomor_ba'          => $nomor_ba,
    'tanggal'           => $tanggal,
    'pt'                => $pt,
    'id_pt'             => $id_pt,
    'lokasi'            => $lokasi,

    'sn'                => $sn,
    'merek'             => $merek,
    'type'              => $type,
    'satuan'            => $satuan,
    'cpu'               => $cpu,
    'os'                => $os,
    'ram'               => $ram,
    'storage'           => $storage,
    'gpu'               => $gpu,
    'display'           => $display,
    'lain'              => $lain,

    'merk_monitor'      => $merkmonitor,
    'sn_monitor'        => $snmonitor,
    'merk_keyboard'     => $merkkeyboard,
    'sn_keyboard'       => $snkeyboard,
    'merk_mouse'        => $merkmouse,
    'sn_mouse'          => $snmouse,

    'categories'        => $jenis_perangkat,
    'qty_id'            => $qtyid,
    'kode_assets'       => $kode_asset,
    'no_po'             => $no_po,
    'tgl_pembelian'     => $tanggal_pembelian,
    'user'              => $user,

    'nama_pembuat'      => $old_data['nama_pembuat'],
    'atasan_peminjam'   => $atasan_peminjam,
    'alamat_peminjam'   => $alamat_peminjam,
    'peminjam'          => $peminjam,
    'saksi'             => $saksi,
    'diketahui'         => $diketahui,
    'pihak_pertama'     => $pihak_pertama,
];

$perubahan = [];

foreach ($new_data as $field => $new_value) {
    $old_value = isset($old_data[$field]) ? $old_data[$field] : '-';
    if ($old_value != $new_value) {
        $perubahan[] = ucfirst(str_replace('_', ' ', $field)) . " : {$old_value} diubah ke {$new_value}";
    }
}

//==============================================================================
// PENDING DATA EDIT KARENA DATA SUDAH ADA APPROVAL
//==============================================================================
$ada_approval = false;
for ($i = 1; $i <= 4; $i++) {
    if (isset($old_data["approval_{$i}"]) && $old_data["approval_{$i}"] == 1) {
        $ada_approval = true;
        break;
    }
}

echo "<pre>";
print_r($perubahan);
echo "</pre>";
echo "<pre>";
print_r($ada_approval);
var_dump($ada_approval);
echo "</pre>";


if (!empty($perubahan) && $ada_approval) {
    // $debug_1 = true;
    // echo "<pre>";
    // print_r($debug_1);
    // echo "</pre>";
    // exit;
    $histori_text = implode("; ", $perubahan);
    $nama_pembuat = isset($_SESSION['nama']) ? $_SESSION['nama'] : 'Tidak diketahui';
    // echo "<pre>";
    // print_r($histori_text);
    // echo "</pre>";

    // =============================
    // HAPUS DATA PENDING JIKA ADA
    // =============================
    $cek_pending = $koneksi->prepare("SELECT id FROM historikal_edit_ba 
        WHERE nama_ba = 'st_asset' AND id_ba = ? AND pending_status = '1'");
    $cek_pending->bind_param("i", $id);
    $cek_pending->execute();
    $result_pending = $cek_pending->get_result();


    // echo "<pre>";
    // print_r($result_pending);
    // echo "</pre>";
    

    if ($result_pending->num_rows > 0) {
        $hapus_pending = $koneksi->prepare("DELETE FROM historikal_edit_ba 
            WHERE nama_ba = 'st_asset' AND id_ba = ? AND pending_status = '1'");
        $hapus_pending->bind_param("i", $id);
        $hapus_pending->execute();
        $hapus_pending->close();
    }
    
    $cek_pending->close();
    

    // =============================
    // SIMPAN HISTORI BARU
    // =============================

    $insert_histori = $koneksi->prepare("INSERT INTO historikal_edit_ba 
        (id_ba, nama_ba, pt, histori_edit, pengedit, tanggal_edit, pending_status) 
        VALUES (?, 'st_asset', ?, ?, ?, NOW(), 1)");
    $insert_histori->bind_param("isss", $id, $pt, $histori_text, $nama_pembuat);
    $insert_histori->execute();
    $insert_histori->close();

    // =============================
    // SIMPAN DATA LAMA & BARU KE history_n_temp_ba_serah_terima_asset (ADA APPROVAL)
    // =============================

    $pending_approver = null;
    if ($pt == 'PT.MSAL (HO)') {
        $pending_approver = 'Tedy Paronto';
    } 
    // elseif ($pt == 'PT.MSAL (SITE)'){
    //     $pending_approver = 'Agustian';
    // } 
    else {
        $query              = $koneksi->query("SELECT nama FROM data_karyawan_test WHERE posisi = 'KTU' AND pt = $pt LIMIT 1");
        $data               = $query->fetch_assoc();
        $pending_approver   = $data ? $data['nama'] : '-';
    }

    // echo "<pre>";
    // print_r($pending_approver);
    // echo "</pre>";
    

    // =============================
    // HAPUS DATA HISTORY YANG SAMA JIKA ADA
    // =============================

    $cek_history = $koneksi->prepare("
        SELECT id FROM history_n_temp_ba_serah_terima_asset 
        WHERE id_ba = ? AND pending_status = 1 AND (status = 0 OR status = 1)
    ");
    $cek_history->bind_param("i", $id);
    $cek_history->execute();
    $result_history = $cek_history->get_result();

    // echo "<pre>";
    // print_r($result_history);
    // echo "</pre>";
    

    if ($result_history->num_rows > 0) {
        $hapus_history = $koneksi->prepare("
            DELETE FROM history_n_temp_ba_serah_terima_asset 
            WHERE id_ba = ? AND pending_status = 1 AND (status = 0 OR status = 1)
        ");
        $hapus_history->bind_param("i", $id);
        $hapus_history->execute();
        $hapus_history->close();
    }

    $cek_history->close();



    // --- OLD DATA (status = 0, pending_status = 1) ---


    $insert_old = $koneksi->prepare("
        INSERT INTO history_n_temp_ba_serah_terima_asset (
            id_ba, status, pending_status, pending_approver, alasan_edit, 
            nomor_ba, tanggal, pt, id_pt, lokasi, nama_pembuat, 
            peminjam, atasan_peminjam, alamat_peminjam, 
            sn, merek, type, satuan, cpu, os, ram, storage, gpu, display, lain, merk_monitor, sn_monitor, merk_keyboard, sn_keyboard, merk_mouse, sn_mouse, categories, qty_id, kode_assets, no_po, tgl_pembelian, user, 
            pihak_pertama, saksi, diketahui, 
            approval_1, approval_2, approval_3, approval_4,
            autograph_1, autograph_2, autograph_3, autograph_4,
            tanggal_approve_1, tanggal_approve_2, tanggal_approve_3, tanggal_approve_4, 
            file_created
        ) VALUES (
            ?, 0, 1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
        )
    ");
    $insert_old->bind_param(
        "isssssssssssssssssssssssssssssssssssssiiiisssssssss",
        $old_data_array['id'],
        $pending_approver,
        $alasan_perubahan,
        $old_data_array['nomor_ba'],            // s
        $old_data_array['tanggal'],             // s
        $old_data_array['pt'],                  // s
        $old_data_array['id_pt'],               // s
        $old_data_array['lokasi'],              // s
        $old_data_array['nama_pembuat'],        // s
        $old_data_array['peminjam'],            // s
        $old_data_array['atasan_peminjam'],     // s
        $old_data_array['alamat_peminjam'],     // s
        $old_data_array['sn'],                  // s
        $old_data_array['merek'],               // s
        $old_data_array['type'],                // s
        $old_data_array['satuan'],              // s
        $old_data_array['cpu'],                 // s
        $old_data_array['os'],                  // s
        $old_data_array['ram'],                 // s
        $old_data_array['storage'],             // s
        $old_data_array['gpu'],                 // s
        $old_data_array['display'],             // s
        $old_data_array['lain'],                // s
        $old_data_array['merk_monitor'],        // s
        $old_data_array['sn_monitor'],          // s
        $old_data_array['merk_keyboard'],       // s
        $old_data_array['sn_keyboard'],         // s
        $old_data_array['merk_mouse'],          // s
        $old_data_array['sn_mouse'],            // s
        $old_data_array['categories'],          // s
        $old_data_array['qty_id'],              // s
        $old_data_array['kode_assets'],         // s
        $old_data_array['no_po'],               // s
        $old_data_array['tgl_pembelian'],       // s
        $old_data_array['user'],                // s
        $old_data_array['pihak_pertama'],       // s
        $old_data_array['saksi'],               // s
        $old_data_array['diketahui'],           // s
        $old_data_array['approval_1'],          // i
        $old_data_array['approval_2'],          // i
        $old_data_array['approval_3'],          // i
        $old_data_array['approval_4'],          // i
        $old_data_array['autograph_1'],         // s
        $old_data_array['autograph_2'],         // s
        $old_data_array['autograph_3'],         // s
        $old_data_array['autograph_4'],         // s
        $old_data_array['tanggal_approve_1'],   // s
        $old_data_array['tanggal_approve_2'],   // s
        $old_data_array['tanggal_approve_3'],   // s
        $old_data_array['tanggal_approve_4'],   // s
        $old_data_array['created_at']           // s
    );
    $insert_old->execute();
    $insert_old->close();

    // --- NEW DATA (status = 1, pending_status = 1) ---
    
    $insert_new = $koneksi->prepare("
        INSERT INTO history_n_temp_ba_serah_terima_asset (
            id_ba, status, pending_status, pending_approver, alasan_edit, 
            nomor_ba, tanggal, pt, id_pt, lokasi, nama_pembuat, 
            peminjam, atasan_peminjam, alamat_peminjam, 
            sn, merek, type, satuan, cpu, os, ram, storage, gpu, display, lain, merk_monitor, sn_monitor, merk_keyboard, sn_keyboard, merk_mouse, sn_mouse, categories, qty_id, kode_assets, no_po, tgl_pembelian, user, 
            pihak_pertama, saksi, diketahui, 
            approval_1, approval_2, approval_3, approval_4,
            autograph_1, autograph_2, autograph_3, autograph_4,
            tanggal_approve_1, tanggal_approve_2, tanggal_approve_3, tanggal_approve_4, 
            file_created
        ) VALUES (
            ?, 1, 1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
        )
    ");
    $insert_new->bind_param(
        "isssssssssssssssssssssssssssssssssssssiiiisssssssss",
        $new_data_array['id'],
        $pending_approver,
        $alasan_perubahan,
        $new_data_array['nomor_ba'],            // s
        $new_data_array['tanggal'],             // s
        $new_data_array['pt'],                  // s
        $new_data_array['id_pt'],               // s
        $new_data_array['lokasi'],              // s
        $new_data_array['nama_pembuat'],        // s
        $new_data_array['peminjam'],            // s
        $new_data_array['atasan_peminjam'],     // s
        $new_data_array['alamat_peminjam'],     // s
        $new_data_array['sn'],                  // s
        $new_data_array['merek'],               // s
        $new_data_array['type'],                // s
        $new_data_array['satuan'],              // s
        $new_data_array['cpu'],                 // s
        $new_data_array['os'],                  // s
        $new_data_array['ram'],                 // s
        $new_data_array['storage'],             // s
        $new_data_array['gpu'],                 // s
        $new_data_array['display'],             // s
        $new_data_array['lain'],                // s
        $new_data_array['merk_monitor'],        // s
        $new_data_array['sn_monitor'],          // s
        $new_data_array['merk_keyboard'],       // s
        $new_data_array['sn_keyboard'],         // s
        $new_data_array['merk_mouse'],          // s
        $new_data_array['sn_mouse'],            // s
        $new_data_array['categories'],          // s
        $new_data_array['qty_id'],              // s
        $new_data_array['kode_assets'],         // s
        $new_data_array['no_po'],               // s
        $new_data_array['tgl_pembelian'],       // s
        $new_data_array['user'],                // s
        $new_data_array['pihak_pertama'],       // s
        $new_data_array['saksi'],               // s
        $new_data_array['diketahui'],           // s
        $new_data_array['approval_1'],          // i
        $new_data_array['approval_2'],          // i
        $new_data_array['approval_3'],          // i
        $new_data_array['approval_4'],          // i
        $new_data_array['autograph_1'],         // s
        $new_data_array['autograph_2'],         // s
        $new_data_array['autograph_3'],         // s
        $new_data_array['autograph_4'],         // s
        $new_data_array['tanggal_approve_1'],   // s
        $new_data_array['tanggal_approve_2'],   // s
        $new_data_array['tanggal_approve_3'],   // s
        $new_data_array['tanggal_approve_4'],   // s
        $new_data_array['created_at']           // s
    );
    $insert_new->execute();
    $insert_new->close();
}
//==============================================================================
// echo "<pre>";
// print_r($debug_1);
// echo "</pre>";
// $debug_1 = false;


// Cek apakah semua approval_x bernilai 0
$semua_approval_nol = true;
// echo "<pre>";
// print_r($semua_approval_nol);
// echo "</pre>";


for ($i = 1; $i <= 4; $i++) {
    if (!isset($old_data["approval_{$i}"]) || $old_data["approval_{$i}"] != 0) {
        $semua_approval_nol = false;
        break;
    }
}

// echo "<pre>";
// print_r($semua_approval_nol);
// echo "</pre>";


if (!empty($perubahan) && $semua_approval_nol) {
    $histori_text = implode("; ", $perubahan);
    $nama_pembuat = isset($_SESSION['nama']) ? $_SESSION['nama'] : 'Tidak diketahui';
    echo "<pre>";
    var_dump($histori_text);
    echo "</pre>";
    // exit;
    // =============================
    // SIMPAN HISTORI BARU (approval semua 0)
    // =============================
    $insert_histori = $koneksi->prepare("INSERT INTO historikal_edit_ba 
        (id_ba, nama_ba, pt, histori_edit, pengedit, tanggal_edit, pending_status) 
        VALUES (?, 'st_asset', ?, ?, ?, NOW(), 0)");
    $insert_histori->bind_param("isss", $id, $pt, $histori_text, $nama_pembuat);
    $insert_histori->execute();
    $insert_histori->close();

    // =============================
    // SIMPAN DATA LAMA & BARU KE history_n_temp_ba_serah_terima_asset
    // =============================

    $pending_approver = "";

    // --- OLD DATA (status = 0) ---
    $insert_old = $koneksi->prepare("
        INSERT INTO history_n_temp_ba_serah_terima_asset (
            id_ba, status, pending_status, pending_approver, alasan_edit, 
            nomor_ba, tanggal, pt, id_pt, lokasi, nama_pembuat, 
            peminjam, atasan_peminjam, alamat_peminjam, 
            sn, merek, `type`, satuan, cpu, os, ram, storage, gpu, display, lain, merk_monitor, sn_monitor, merk_keyboard, sn_keyboard, merk_mouse, sn_mouse, categories, qty_id, kode_assets, no_po, tgl_pembelian, user, 
            pihak_pertama, saksi, diketahui, 
            approval_1, approval_2, approval_3, approval_4,
            autograph_1, autograph_2, autograph_3, autograph_4,
            tanggal_approve_1, tanggal_approve_2, tanggal_approve_3, tanggal_approve_4, 
            file_created
        ) VALUES (
            ?, 0, 0, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
        )
    ");

    $insert_old->bind_param(
        "isssssssssssssssssssssssssssssssssssssiiiisssssssss",
        $old_data_array['id'],
        $pending_approver,
        $alasan_perubahan,
        $old_data_array['nomor_ba'],            // s
        $old_data_array['tanggal'],             // s
        $old_data_array['pt'],                  // s
        $old_data_array['id_pt'],               // s
        $old_data_array['lokasi'],              // s
        $old_data_array['nama_pembuat'],        // s
        $old_data_array['peminjam'],            // s
        $old_data_array['atasan_peminjam'],     // s
        $old_data_array['alamat_peminjam'],     // s
        $old_data_array['sn'],                  // s
        $old_data_array['merek'],               // s
        $old_data_array['type'],                // s
        $old_data_array['satuan'],              // s
        $old_data_array['cpu'],                 // s
        $old_data_array['os'],                  // s
        $old_data_array['ram'],                 // s
        $old_data_array['storage'],             // s
        $old_data_array['gpu'],                 // s
        $old_data_array['display'],             // s
        $old_data_array['lain'],                // s
        $old_data_array['merk_monitor'],        // s
        $old_data_array['sn_monitor'],          // s
        $old_data_array['merk_keyboard'],       // s
        $old_data_array['sn_keyboard'],         // s
        $old_data_array['merk_mouse'],          // s
        $old_data_array['sn_mouse'],            // s
        $old_data_array['categories'],          // s
        $old_data_array['qty_id'],              // s
        $old_data_array['kode_assets'],         // s
        $old_data_array['no_po'],               // s
        $old_data_array['tgl_pembelian'],       // s
        $old_data_array['user'],                // s
        $old_data_array['pihak_pertama'],       // s
        $old_data_array['saksi'],               // s
        $old_data_array['diketahui'],           // s
        $old_data_array['approval_1'],          // i
        $old_data_array['approval_2'],          // i
        $old_data_array['approval_3'],          // i
        $old_data_array['approval_4'],          // i
        $old_data_array['autograph_1'],         // s
        $old_data_array['autograph_2'],         // s
        $old_data_array['autograph_3'],         // s
        $old_data_array['autograph_4'],         // s
        $old_data_array['tanggal_approve_1'],   // s
        $old_data_array['tanggal_approve_2'],   // s
        $old_data_array['tanggal_approve_3'],   // s
        $old_data_array['tanggal_approve_4'],   // s
        $old_data_array['created_at']           // s
    );
    $insert_old->execute();
    $insert_old->close();

    // =============================
    // UPDATE DATA UTAMA (berita_acara_kerusakan)
    // =============================
    $update_real = $koneksi->prepare("
        UPDATE ba_serah_terima_asset SET

            nomor_ba          = ?,
            tanggal           = ?,
            pt                = ?,
            id_pt             = ?,
            lokasi            = ?,
            nama_pembuat      = ?,
            peminjam          = ?,
            atasan_peminjam   = ?,
            alamat_peminjam   = ?,
            sn                = ?,
            merek             = ?,
            type              = ?,
            satuan            = ?,
            cpu               = ?,
            os                = ?,
            ram               = ?,
            storage           = ?,
            gpu               = ?,
            display           = ?,
            lain              = ?,
            merk_monitor      = ?,
            sn_monitor        = ?,
            merk_keyboard     = ?,
            sn_keyboard       = ?,
            merk_mouse        = ?,
            sn_mouse          = ?,
            categories        = ?,
            qty_id            = ?,
            kode_assets       = ?,
            no_po             = ?,
            tgl_pembelian     = ?,
            user              = ?,
            pihak_pertama     = ?,
            saksi             = ?,
            diketahui         = ?,
            approval_1        = ?,
            approval_2        = ?,
            approval_3        = ?,
            approval_4        = ?,
            autograph_1       = ?,
            autograph_2       = ?,
            autograph_3       = ?,
            autograph_4       = ?,
            tanggal_approve_1 = ?,
            tanggal_approve_2 = ?,
            tanggal_approve_3 = ?,
            tanggal_approve_4 = ?

        WHERE id = ?
    ");
    $update_real->bind_param(
        "sssssssssssssssssssssssssssssssssssiiiissssssss" . "i",
        $new_data_array['nomor_ba'],            // s
        $new_data_array['tanggal'],             // s
        $new_data_array['pt'],                  // s
        $new_data_array['id_pt'],               // s
        $new_data_array['lokasi'],              // s
        $new_data_array['nama_pembuat'],        // s
        $new_data_array['peminjam'],            // s
        $new_data_array['atasan_peminjam'],     // s
        $new_data_array['alamat_peminjam'],     // s
        $new_data_array['sn'],                  // s
        $new_data_array['merek'],               // s
        $new_data_array['type'],                // s
        $new_data_array['satuan'],              // s
        $new_data_array['cpu'],                 // s
        $new_data_array['os'],                  // s
        $new_data_array['ram'],                 // s
        $new_data_array['storage'],             // s
        $new_data_array['gpu'],                 // s
        $new_data_array['display'],             // s
        $new_data_array['lain'],                // s
        $new_data_array['merk_monitor'],        // s
        $new_data_array['sn_monitor'],          // s
        $new_data_array['merk_keyboard'],       // s
        $new_data_array['sn_keyboard'],         // s
        $new_data_array['merk_mouse'],          // s
        $new_data_array['sn_mouse'],            // s
        $new_data_array['categories'],          // s
        $new_data_array['qty_id'],              // s
        $new_data_array['kode_assets'],         // s
        $new_data_array['no_po'],               // s
        $new_data_array['tgl_pembelian'],       // s
        $new_data_array['user'],                // s
        $new_data_array['pihak_pertama'],       // s
        $new_data_array['saksi'],               // s
        $new_data_array['diketahui'],           // s
        $new_data_array['approval_1'],          // i
        $new_data_array['approval_2'],          // i
        $new_data_array['approval_3'],          // i
        $new_data_array['approval_4'],          // i
        $new_data_array['autograph_1'],         // s
        $new_data_array['autograph_2'],         // s
        $new_data_array['autograph_3'],         // s
        $new_data_array['autograph_4'],         // s
        $new_data_array['tanggal_approve_1'],   // s
        $new_data_array['tanggal_approve_2'],   // s
        $new_data_array['tanggal_approve_3'],   // s
        $new_data_array['tanggal_approve_4'],   // s
        $id
    );
    $update_real->execute();
    $update_real->close();
}

$koneksi->close();

$_SESSION['message'] = "Data berhasil diperbarui ke database.";
header("Location: ba_serah-terima-asset.php?status=sukses");
exit();
