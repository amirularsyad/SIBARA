<?php
require_once 'koneksi.php';

if (!isset($_GET['pt']) || empty($_GET['pt'])) {
    echo json_encode([]);
    exit;
}

$pt = $_GET['pt'];

// Ambil data dari data_karyawan
$stmt = $koneksi->prepare("SELECT nik, nama, jabatan, departemen FROM data_karyawan WHERE nik IS NOT NULL ORDER BY nama ASC");
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = [
        'nik' => $row['nik'],
        'label' => "{$row['nik']} ({$row['nama']} - {$row['jabatan']} {$row['departemen']})"
    ];
}

echo json_encode($data);
?>
