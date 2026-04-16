<?php
header('Content-Type: application/json');
require_once '../koneksi.php';

if (!isset($_GET['lokasi'])) {
    echo json_encode(['error' => 'Parameter lokasi tidak ditemukan']);
    exit;
}

$lokasi = trim($_GET['lokasi']); // penting: buang spasi ujung
$role   = isset($_GET['role']) ? trim($_GET['role']) : '';
$data   = [];

if ($lokasi === 'PT.MSAL (HO)') {
    // HO tidak diubah
    $sql = "SELECT nama, posisi, departemen
            FROM data_karyawan
            WHERE posisi != 'Staf GA'
              AND departemen = 'MIS'
            ORDER BY nama ASC";

    $result = $koneksi->query($sql);
    if (!$result) {
        echo json_encode(['error' => 'Query gagal: ' . $koneksi->error]);
        exit;
    }

} else {
    // Normalisasi: trim ujung, hilangkan spasi sebelum/after koma
    // (tanpa menghapus spasi di dalam nama lokasi, mis: "RO PALANGKARAYA")
    $pt_norm = "REPLACE(REPLACE(TRIM(pt), ', ', ','), ' ,', ',')";

    $sql = "SELECT nama, posisi, departemen
            FROM data_karyawan_test
            WHERE FIND_IN_SET(?, $pt_norm) > 0
              AND departemen != 'HRD'";

    // filter role
    $params = [$lokasi];
    $types  = "s";

    if ($role === "ktu") {
        $sql .= " AND posisi = ?";
        $params[] = "KTU";
        $types .= "s";
    } elseif ($role === "gm") {
        $sql .= " AND posisi = ?";
        $params[] = "GM";
        $types .= "s";
    }

    $sql .= " ORDER BY nama ASC";

    $stmt = $koneksi->prepare($sql);
    if (!$stmt) {
        echo json_encode(['error' => 'Prepare gagal: ' . $koneksi->error]);
        exit;
    }

    $stmt->bind_param($types, ...$params);

    if (!$stmt->execute()) {
        echo json_encode(['error' => 'Execute gagal: ' . $stmt->error]);
        exit;
    }

    $result = $stmt->get_result();
    if (!$result) {
        echo json_encode(['error' => 'Get result gagal: ' . $stmt->error]);
        exit;
    }
}

while ($row = $result->fetch_assoc()) {
    $data[] = [
        'nama' => $row['nama'],
        'posisi' => $row['posisi'],
        'departemen' => $row['departemen']
    ];
}

echo json_encode($data);
