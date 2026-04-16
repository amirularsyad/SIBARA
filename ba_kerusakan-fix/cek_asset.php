<?php
header('Content-Type: application/json');

// Koneksi database
$host = "localhost";
$user = "root";
$pass = "";
$db   = "db_surat_ba";

$conn = mysqli_connect($host, $user, $pass, $db);

if (mysqli_connect_errno()) {
    echo json_encode([
        "success" => false,
        "message" => "Koneksi DB gagal: " . mysqli_connect_error()
    ]);
    exit;
}

$assetId = isset($_POST['asset_id']) ? trim($_POST['asset_id']) : '';

if ($assetId === '') {
    echo json_encode(["success" => false, "message" => "asset_id kosong"]);
    exit;
}

$assetIdSafe = mysqli_real_escape_string($conn, $assetId);

// $sql = "SELECT id, name, serial 
//         FROM assets 
//         WHERE id = '$assetIdSafe' LIMIT 1";

$sql = "SELECT 
            a.id AS id,
            a.name AS name,
            a.serial AS serial,
            m.name AS model_name,
            m.model_number AS model_number,
            manu.name AS manufacturer_name,
            cat.name AS categori_name,
            CONCAT('20', SUBSTRING_INDEX(SUBSTRING_INDEX(a.order_number, '/', 5), '/', -1)) AS tahun
        FROM assets a
        LEFT JOIN models m ON a.model_id = m.id
        LEFT JOIN manufacturers manu ON m.manufacturer_id = manu.id
        LEFT JOIN categories cat ON m.category_id = cat.id
        WHERE a.id = '$assetIdSafe' AND a.name != 'NULL' LIMIT 1";;

$result = mysqli_query($conn, $sql);

if ($result && mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
    echo json_encode([
        "success" => true,
        "data" => $row
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Aset tidak ditemukan"
    ]);
}

mysqli_close($conn);
