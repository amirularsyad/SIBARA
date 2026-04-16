<?php
require_once "../koneksi.php";

$id_ba = isset($_GET['id_ba']) ? intval($_GET['id_ba']) : 0;

if ($id_ba <= 0) {
    echo json_encode([]);
    exit;
}

$query = "
    SELECT 
        pt_asal,
        po,
        coa,
        kode_assets,
        merk,
        sn,
        user,
        created_at
    FROM barang_mutasi
    WHERE id_ba = ?
    ORDER BY id ASC
";

$stmt = $koneksi->prepare($query);
$stmt->bind_param("i", $id_ba);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

header('Content-Type: application/json');
echo json_encode($data);
?>
