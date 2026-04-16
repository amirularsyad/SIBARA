<?php
require_once "../koneksi.php";

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    echo json_encode(['success' => false]);
    exit;
}

// Cek apakah file ada di server
$stmt = $koneksi->prepare("SELECT file_path FROM gambar_ba_mutasi WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$file = $result->fetch_assoc();

if ($file) {
    $file_path = $file['file_path'];
    if (file_exists($file_path)) {
        unlink($file_path);
    }

    $stmtDel = $koneksi->prepare("DELETE FROM gambar_ba_mutasi WHERE id = ?");
    $stmtDel->bind_param("i", $id);
    $stmtDel->execute();
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}
