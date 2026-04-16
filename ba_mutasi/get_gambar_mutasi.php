<?php
require_once "../koneksi.php";

$id_ba = isset($_GET['id_ba']) ? intval($_GET['id_ba']) : 0;

if ($id_ba <= 0) {
    echo json_encode([]);
    exit;
}

$query = "SELECT id, file_path, uploaded_at FROM gambar_ba_mutasi WHERE id_ba = ?";
$stmt = $koneksi->prepare($query);
$stmt->bind_param("i", $id_ba);
$stmt->execute();
$result = $stmt->get_result();

$gambar = [];
while ($row = $result->fetch_assoc()) {
    $gambar[] = $row;
}

header('Content-Type: application/json');
echo json_encode($gambar);
