<?php
session_start();
require_once '../koneksi.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}
if (
    !isset($_SESSION['hak_akses']) ||
    ($_SESSION['hak_akses'] !== 'Admin' && $_SESSION['hak_akses'] !== 'Super Admin')
) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'ID tidak ditemukan']);
    exit();
}

$id = intval($_GET['id']);

// 🔹 Ambil detail data utama
$sql = "
    SELECT * FROM ba_serah_terima_asset
    WHERE id = ?
";
$stmt = $koneksi->prepare($sql);
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['error' => 'Data tidak ditemukan']);
    exit;
}

$data = $result->fetch_assoc();
$stmt->close();

// 🔹 Encode kolom autograph agar tidak rusak JSON
for ($i = 1; $i <= 4; $i++) {
    $col = "autograph_$i";
    if (isset($data[$col]) && !empty($data[$col])) {
        // Asumsi file TTD disimpan dalam format PNG
        $data[$col] = 'data:image/png;base64,' . base64_encode($data[$col]);
    } else {
        $data[$col] = null;
    }
}
if ($data['pt'] === 'PT.MSAL (HO)') {
    // 🔹 Ambil data peran
    $querySTAsset = "
    SELECT 
        basta.id, basta.peminjam, basta.saksi, basta.diketahui, basta.pihak_pertama,
        basta.approval_1, basta.approval_2, basta.approval_3, basta.approval_4,
        k1.jabatan AS jabatan_aprv1, k1.departemen AS departemen_aprv1,
        k2.jabatan AS jabatan_aprv2, k2.departemen AS departemen_aprv2,
        k3.jabatan AS jabatan_aprv3, k3.departemen AS departemen_aprv3,
        k4.jabatan AS jabatan_aprv4, k4.departemen AS departemen_aprv4
    FROM ba_serah_terima_asset basta
    LEFT JOIN data_karyawan k1 ON basta.peminjam = k1.nama
    LEFT JOIN data_karyawan k2 ON basta.saksi = k2.nama
    LEFT JOIN data_karyawan k3 ON basta.diketahui = k3.nama
    LEFT JOIN data_karyawan k4 ON basta.pihak_pertama = k4.nama
    WHERE basta.id = ?
    LIMIT 1
";
// } elseif ($data['pt'] === 'PT.MSAL (SITE)') {
} elseif ($data['pt'] !== 'PT.MSAL (HO)') {
    $querySTAsset = "
    SELECT 
        basta.id, basta.peminjam, basta.saksi, basta.diketahui, basta.pihak_pertama,
        basta.approval_1, basta.approval_2, basta.approval_3, basta.approval_4,
        k1.posisi AS posisi1,
        k2.posisi AS posisi2,
        k3.posisi AS posisi3,
        k4.jabatan AS jabatan_aprv4, k4.departemen AS departemen_aprv4
    FROM ba_serah_terima_asset basta
    LEFT JOIN data_karyawan_test k1 ON basta.peminjam = k1.nama
    LEFT JOIN data_karyawan_test k2 ON basta.saksi = k2.nama
    LEFT JOIN data_karyawan_test k3 ON basta.diketahui = k3.nama
    LEFT JOIN data_karyawan k4 ON basta.pihak_pertama = k4.nama
    WHERE basta.id = ?
    LIMIT 1
";
}
$stmtSTAsset = $koneksi->prepare($querySTAsset);
$stmtSTAsset->bind_param("i", $id);
$stmtSTAsset->execute();
$resultSTAsset = $stmtSTAsset->get_result();
$peran = $resultSTAsset->fetch_assoc();
$stmtSTAsset->close();

// Ambil semua history terkait id_ba
$sqlHistory = "SELECT id, id_ba, created_at, pending_status, status, alasan_edit, alasan_tolak, 
nomor_ba, tanggal, pt, id_pt, lokasi, nama_pembuat, peminjam, atasan_peminjam, alamat_peminjam, sn, merek, type, satuan, cpu, os, ram, storage, gpu, display, lain, merk_monitor,
sn_monitor, merk_keyboard, sn_keyboard, merk_mouse, sn_mouse, categories, qty_id, kode_assets, no_po, tgl_pembelian, user, pihak_pertama, saksi, diketahui
FROM history_n_temp_ba_serah_terima_asset WHERE id_ba = ?";
$stmtHistory = $koneksi->prepare($sqlHistory);
$stmtHistory->bind_param("i", $id);
$stmtHistory->execute();
$resultHistory = $stmtHistory->get_result();
$data_history = [];
while ($row = $resultHistory->fetch_assoc()) {
    $data_history[] = $row;
}
$stmtHistory->close();

// Cek jika ada lebih dari 1 record dengan pending_status = 1
$pending1 = array_filter($data_history, function ($h) {
    return intval($h['pending_status']) === 1;
});

if (count($pending1) > 1) {

    $found = array_filter($pending1, function ($h) {
        return isset($h['status']) && intval($h['status']) === 1;
    });

    if (!empty($found)) {
        $chosen_id = array_values($found)[0]['id'];
    } else {
        $chosen_id = array_values($pending1)[0]['id'];
    }

    foreach ($data_history as &$h) {
        $h['take_for_pending'] = ($h['id'] == $chosen_id) ? true : false;
    }
} else {

    foreach ($data_history as &$h) {
        $h['take_for_pending'] = ($h['pending_status'] == 1) ? true : false;
    }
}


// Tambahkan pending_status_nama
foreach ($data_history as &$h) {
    switch (intval($h['pending_status'])) {
        case 1:
            $h['pending_status_nama'] = 'Pending Edit';
            break;
        case 2:
            $h['pending_status_nama'] = 'Edit Ditolak';
            break;
        case 0:
        default:
            $h['pending_status_nama'] = 'History';
            break;
    }
}

// 🔹 Encode final JSON dengan error checking
$response = [
    'data' => $data,
    'peran' => $peran,
    'data_history' => $data_history
];

$json = json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
if ($json === false) {
    echo json_encode(['error' => 'json_encode_error', 'message' => json_last_error_msg()]);
    exit;
}

// echo '<pre style="background:#111;color:#0f0;padding:15px;font-size:13px;">';
// echo "===== DEBUG RESPONSE FINAL =====\n\n";

// echo "--- DATA UTAMA ---\n";
// print_r($data);

// echo "\n\n--- PERAN ---\n";
// print_r($peran);


// echo "\n\n--- DATA HISTORY ---\n";
// print_r($data_history);

// echo '</pre>';
// exit;

echo $json;
