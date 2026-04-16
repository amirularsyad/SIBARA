<?php
// support PHP 5.6
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(array());
    exit;
}

include '../koneksi.php'; // pastikan $koneksi2 tersedia

$pt = isset($_GET['pt']) ? trim($_GET['pt']) : '';
if ($pt === '') {
    echo json_encode(array());
    exit;
}

// ambil PT user dari session (support string/array)
$ptUserList = isset($_SESSION['pt']) ? $_SESSION['pt'] : array();
if (!is_array($ptUserList)) {
    $ptUserList = array_map('trim', explode(',', (string)$ptUserList));
}
$tmp = array();
foreach ($ptUserList as $p) {
    $p = trim($p);
    if ($p !== '') $tmp[] = $p;
}
$ptUserList = array_values(array_unique($tmp));

// validasi: PT yang diminta harus PT milik user (biar aman)
if (!in_array($pt, $ptUserList, true)) {
    echo json_encode(array());
    exit;
}

// mapping nama_pt -> id_pt (sesuai tb_pt)
$map_pt = array(
    'PT.MSAL (HO)'          => 1,
    'PT.MSAL (PKS)'         => 2,
    'PT.MSAL (SITE)'        => 3,
    'PT.PSAM (PKS)'         => 4,
    'PT.PSAM (SITE)'        => 5,
    'PT.MAPA'               => 6,
    'PT.PEAK (PKS)'         => 7,
    'PT.PEAK (SITE)'        => 8,
    'RO PALANGKARAYA'       => 9,
    'RO SAMPIT'             => 10,
    'PT.WCJU (SITE)'        => 11,
    'PT.WCJU (PKS)'         => 12
);

if (!isset($map_pt[$pt])) {
    echo json_encode(array());
    exit;
}

$id_pt = (int)$map_pt[$pt];

$sql = "
SELECT 
    a.id_assets,
    a.serial_number,
    q.category AS jenis_perangkat,
    a.merk,
    a.user,
    a.no_po,
    a.kode_assets,
    i.nama_pt AS pt
FROM tb_assets AS a
LEFT JOIN tb_qty_assets AS q ON a.qty_id = q.id_qty
LEFT JOIN tb_pt AS i ON a.id_pt = i.id_pt
WHERE a.id_pt = ?
ORDER BY q.category ASC
";

$stmt = $koneksi2->prepare($sql);
if (!$stmt) {
    echo json_encode(array());
    exit;
}

$stmt->bind_param('i', $id_pt);
$stmt->execute();
$res = $stmt->get_result();

$out = array();
while ($row = $res->fetch_assoc()) {
    $out[] = $row;
}

$stmt->close();
echo json_encode($out);
