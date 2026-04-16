<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../koneksi.php';

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID tidak ditemukan.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$id = (int)$_GET['id'];

/* Data utama */
$stmt = $koneksi->prepare("SELECT * FROM berita_acara_pengembalian WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
$data = $res->fetch_assoc();
$stmt->close();

if (!$data) {
    http_response_code(404);
    echo json_encode(['error' => 'Data tidak ditemukan.'], JSON_UNESCAPED_UNICODE);
    exit;
}

/* Data barang */
$barang_list = [];
$barang_result = $koneksi->query("SELECT * FROM barang_pengembalian WHERE ba_pengembalian_id = {$id}");
while ($row = $barang_result->fetch_assoc()) {
    $barang_list[] = [
        'jenis_barang' => $row['jenis_barang'],
        'jumlah'       => (int)$row['jumlah'],
        'kondisi'      => $row['kondisi'],
        'keterangan' => isset($row['keterangan']) ? $row['keterangan'] : ''

    ];
}

/* Gambar lama */
$gambar_list = [];
$gambarQuery = $koneksi->prepare("SELECT id, file_path FROM gambar_ba_pengembalian WHERE ba_pengembalian_id = ?");
$gambarQuery->bind_param("i", $data['id']);
$gambarQuery->execute();
$gambarResult = $gambarQuery->get_result();
while ($row = $gambarResult->fetch_assoc()) {
    $gambar_list[] = [
        'id'        => (int)$row['id'],
        'file_path' => $row['file_path']
    ];
}
$gambarQuery->close();

/* Data atasan */
$data_atasan = [];
$qAtasan = $koneksi->query("SELECT nama, posisi, departemen FROM data_karyawan WHERE jabatan = 'Dept. Head' ORDER BY nama ASC");
while ($row = $qAtasan->fetch_assoc()) {
    $data_atasan[] = $row;
}

/* Data karyawan */
$data_karyawan = [];
$qKaryawan = $koneksi->query("SELECT nama, posisi, departemen, lantai FROM data_karyawan ORDER BY nama ASC");
while ($row = $qKaryawan->fetch_assoc()) {
    $data_karyawan[] = $row;
}

/* Lantai prefill (mengikuti logika file asli) */
$lantai_pengembali = '';
$lantai_penerima   = '';
foreach ($data_karyawan as $k) {
    if ($k['nama'] === $data['nama_pengembali']) {
        $lantai_pengembali = $k['lantai'];
    }
    if ($k['nama'] === $data['nama_penerima']) {
        $lantai_penerima = $k['lantai'];
    }
}

/* Payload JSON */
echo json_encode([
    'data' => [
        'id'                => (int)$data['id'],
        'tanggal'           => $data['tanggal'],
        'nomor_ba'          => $data['nomor_ba'],
        'lokasi_pengembali' => $data['lokasi_pengembali'],
        'lokasi_penerima'   => $data['lokasi_penerima'],
        'nama_pengembali'   => $data['nama_pengembali'],
        'nama_penerima'     => $data['nama_penerima'],
        'atasan_pengembali' => $data['atasan_pengembali'],
        'atasan_penerima'   => $data['atasan_penerima'],
    ],
    'barang_list'       => $barang_list,
    'gambar_list'       => $gambar_list,
    'data_atasan'       => $data_atasan,
    'data_karyawan'     => $data_karyawan,
    'lantai_pengembali' => $lantai_pengembali,
    'lantai_penerima'   => $lantai_penerima
], JSON_UNESCAPED_UNICODE);
