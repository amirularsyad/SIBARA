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
    SELECT bak.*, cb.nama AS kategori_nama
    FROM berita_acara_kerusakan bak
    LEFT JOIN categories_broken cb ON bak.kategori_kerusakan_id = cb.id
    WHERE bak.id = ?
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
for ($i = 1; $i <= 5; $i++) {
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
    $queryKerusakan = "
    SELECT 
        bak.id, bak.pembuat, bak.penyetujui, bak.peminjam, bak.atasan_peminjam, bak.diketahui,
        bak.approval_1, bak.approval_2, bak.approval_3, bak.approval_4,
        bak.jabatan_pembuat AS jabatan_aprv1,
        bak.jabatan_penyetujui AS jabatan_aprv2,
        bak.jabatan_peminjam AS jabatan_aprv3,
        bak.jabatan_atasan_peminjam AS jabatan_aprv4,
        bak.jabatan_diketahui AS jabatan_aprv5
    FROM berita_acara_kerusakan bak
    WHERE bak.id = ?
    LIMIT 1
";
} elseif ($data['pt'] !== 'PT.MSAL (HO)') {
    $queryKerusakan = "
    SELECT 
        bak.id, bak.pembuat, bak.penyetujui, bak.peminjam, bak.atasan_peminjam, bak.diketahui,
        bak.approval_1, bak.approval_2, bak.approval_3, bak.approval_4,
        bak.jabatan_pembuat AS posisi1,
        bak.jabatan_penyetujui AS posisi2,
        bak.jabatan_peminjam AS posisi3,
        bak.jabatan_atasan_peminjam AS posisi4,
        bak.jabatan_diketahui AS posisi5
    FROM berita_acara_kerusakan bak
    WHERE bak.id = ?
    LIMIT 1
";
}
$stmtKerusakan = $koneksi->prepare($queryKerusakan);
$stmtKerusakan->bind_param("i", $id);
$stmtKerusakan->execute();
$resultKerusakan = $stmtKerusakan->get_result();
$peran = $resultKerusakan->fetch_assoc();
$stmtKerusakan->close();

// 🔹 Ambil daftar gambar
$sqlGambar = "SELECT file_path FROM gambar_ba_kerusakan WHERE ba_kerusakan_id = ?";
$stmtGambar = $koneksi->prepare($sqlGambar);
$stmtGambar->bind_param("i", $id);
$stmtGambar->execute();
$resultGambar = $stmtGambar->get_result();
$gambarList = [];
while ($rowGambar = $resultGambar->fetch_assoc()) {
    $gambarList[] = $rowGambar['file_path'];
}
$stmtGambar->close();

// Ambil semua history terkait id_ba
$sqlHistory = "SELECT id, id_ba, created_at, pending_status, status, alasan_edit, alasan_tolak, 
tanggal, nomor_ba, jenis_perangkat, merek, no_po, sn, tahun_perolehan, 
kategori_kerusakan_id, deskripsi, penyebab_kerusakan, rekomendasi_mis, pembuat, peminjam, atasan_peminjam, diketahui, penyetujui
FROM history_n_temp_ba_kerusakan WHERE id_ba = ?";
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
    'gambarList' => $gambarList,
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

// echo "\n\n--- GAMBAR LIST ---\n";
// print_r($gambarList);

// echo "\n\n--- DATA HISTORY ---\n";
// print_r($data_history);

// echo '</pre>';
// exit;

echo $json;
