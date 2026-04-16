<?php
session_start();
header('Content-Type: application/json');
require_once '../koneksi.php'; // pakai $koneksi sesuai instruksi Anda

$data = json_decode(file_get_contents("php://input"), true);

if (!$data || !isset($data['id'], $data['approvals'], $data['action'], $data['jenis_ba'])) {
    echo json_encode(["success" => false, "message" => "Data tidak lengkap"]);
    exit;
}

$id        = intval($data['id']);
$approvals = $data['approvals']; // array: ["approval_1", "approval_2", ...]
$action    = $data['action'];    // approve | cancel
$jenis_ba  = $data['jenis_ba'];  // pengembalian | kerusakan

if (!in_array($action, ['approve', 'cancel'])) {
    $_SESSION['success'] = false;
    echo json_encode(["success" => false, "message" => "Aksi tidak valid"]);
    exit;
}

if (!in_array($jenis_ba, ['pengembalian', 'kerusakan', 'notebook', 'mutasi'])) {
    $_SESSION['success'] = false;
    echo json_encode(["success" => false, "message" => "Jenis BA tidak valid"]);
    exit;
}

$nilai = ($action === 'approve') ? 1 : 0;

// tentukan tabel & kolom approval yang valid
if ($jenis_ba === 'pengembalian') {
    $table = "berita_acara_pengembalian";
    $validCols = ['approval_1', 'approval_2', 'approval_3'];
} elseif ($jenis_ba === 'kerusakan') {
    $table = "berita_acara_kerusakan";
    $validCols = ['approval_1', 'approval_2'];
} elseif ($jenis_ba === 'notebook'){
    $table = "ba_serah_terima_notebook";
    $validCols = ['approval_1', 'approval_2', 'approval_3', 'approval_4'];
} elseif ($jenis_ba === 'mutasi'){
    $table = "berita_acara_mutasi";
    $validCols = ['approval_1', 'approval_2', 'approval_3', 'approval_4', 'approval_5'];
} else {
    $_SESSION['success'] = false;
    echo json_encode(["success" => false, "message" => "Jenis BA tidak dikenal"]);
    exit;
}

// bangun query update dinamis
$sets = [];
$params = [];
$types = '';

foreach ($approvals as $appr) {
    if (in_array($appr, $validCols)) {
        $sets[] = "$appr = ?";
        $params[] = $nilai;
        $types .= 'i';
    }
}

if (empty($sets)) {
    $_SESSION['success'] = false;
    echo json_encode(["success" => false, "message" => "Tidak ada kolom approval valid"]);
    exit;
}

$sql = "UPDATE $table SET " . implode(", ", $sets) . " WHERE id = ?";
$params[] = $id;
$types .= 'i';

$stmt = $koneksi->prepare($sql);
if ($stmt === false) {
    $_SESSION['success'] = false;
    echo json_encode(["success" => false, "message" => "Gagal prepare statement"]);
    exit;
}

$stmt->bind_param($types, ...$params);

if ($stmt->execute()) {
    $_SESSION['success'] = true;
    $_SESSION['message'] = ($action === 'approve' ? "Berhasil menyetujui" : "Berhasil membatalkan persetujuan");
    echo json_encode(["success" => true]);
} else {
    $_SESSION['success'] = false;
    $_SESSION['message'] = "Gagal update: " . $stmt->error;
    echo json_encode(["success" => false]);
}

$stmt->close();
$koneksi->close();
