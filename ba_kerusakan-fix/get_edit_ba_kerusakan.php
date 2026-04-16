<?php
session_start();
require_once '../koneksi.php';
header('Content-Type: application/json; charset=utf-8');


if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../login_registrasi.php");
    exit();
}
if (!isset($_SESSION['hak_akses']) || $_SESSION['hak_akses'] !== 'Admin') {
    header("Location: ../personal/approval.php");
    exit();
}

if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'ID tidak ditemukan']);
    exit;
}

try {
    if (!isset($_GET['id'])) {
        echo json_encode(['error' => 'ID tidak ditemukan']); exit;
    }

    $id = intval($_GET['id']);

    // data utama
    $stmt = $koneksi->prepare("SELECT * FROM berita_acara_kerusakan WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows === 0) {
        echo json_encode(['error' => 'Data tidak ditemukan']); exit;
    }
    $data = $res->fetch_assoc();

    // gambar
    $stmtG = $koneksi->prepare("SELECT id, file_path FROM gambar_ba_kerusakan WHERE ba_kerusakan_id = ?");
    $stmtG->bind_param("i", $id);
    $stmtG->execute();
    $resG = $stmtG->get_result();
    $gambar = [];
    while ($row = $resG->fetch_assoc()) { $gambar[] = $row; }

    // atasan (Dept. Head)
    $qA = $koneksi->query("SELECT nama, posisi, departemen FROM data_karyawan WHERE jabatan IN ('Dept. Head', 'AVP Head') ORDER BY nama ASC");
    $atasan = [];
    while ($r = $qA->fetch_assoc()) { $atasan[] = $r; }

    // karyawan
    $qK = $koneksi->query("SELECT nama, posisi, departemen, lantai, jabatan FROM data_karyawan ORDER BY nama ASC");
    $karyawan = [];
    while ($r = $qK->fetch_assoc()) { $karyawan[] = $r; }

    // jika user match, pastikan lokasi (lantai) ikut terisi
    if ($data['pt']==='PT.MSAL (HO)'){
        if (!empty($data['peminjam'])) {
            $found = false;
            foreach ($karyawan as $k) {
                if (trim($k['nama']) === trim($data['peminjam'])) {
                    $data['lokasi'] = $k['lantai'];
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $data['lokasi'] = ''; // fallback biar JS tidak error
            }
        } else {
            $data['lokasi'] = ''; // kalau peminjam kosong
        }
    }
// =============================
// CEK PENDING EDIT DI historikal_edit_ba
// =============================
$pending_edit = 0;

$stmtP = $koneksi->prepare("SELECT id FROM historikal_edit_ba WHERE id_ba = ? AND pending_status = 1 LIMIT 1");
$stmtP->bind_param("i", $id);
$stmtP->execute();
$resP = $stmtP->get_result();

if ($resP->num_rows > 0) {
    $pending_edit = 1;
}

$stmtP->close();



// ===============================================
// AMBIL DATA HISTORY STATUS 0 & 1
// ===============================================
$cols = "tanggal, nomor_ba, jenis_perangkat, merek, no_po, user, deskripsi, sn, tahun_perolehan,
        penyebab_kerusakan, kategori_kerusakan_id, keterangan_dll, rekomendasi_mis,
        pt, lokasi, nama_pembuat, pembuat, penyetujui, peminjam, atasan_peminjam, diketahui";

$stmtH = $koneksi->prepare("
    SELECT $cols, status 
    FROM history_n_temp_ba_kerusakan
    WHERE id_ba = ? AND pending_status = 1 AND status IN (0,1)
    ORDER BY status ASC
");
$stmtH->bind_param("i", $id);
$stmtH->execute();
$resH = $stmtH->get_result();

$oldRow = null; 
$newRow = null;

while ($r = $resH->fetch_assoc()) {
    if ($r['status'] == 0) $oldRow = $r;
    if ($r['status'] == 1) $newRow = $r;
}

$stmtH->close();

// ===============================================
// BANDINKAN FIELD — SIMPAN HANYA YANG BERBEDA
// ===============================================
$data_edit_lama = [];
$data_edit_baru = [];
$header_edit = [];

// mapping nama header
$headerMap = [
    'tanggal' => 'Tanggal',
    'nomor_ba' => 'Nomor BA',
    'jenis_perangkat' => 'Jenis Perangkat',
    'merek' => 'Merek',
    'no_po' => 'Nomor PO',
    'user' => 'User',
    'deskripsi' => 'Jenis Kerusakan',
    'sn' => 'SN',
    'tahun_perolehan' => 'Tahun Perolehan',
    'penyebab_kerusakan' => 'Penyebab Kerusakan',
    'kategori_kerusakan_id' => 'Kategori Kerusakan',
    'keterangan_dll' => 'Keterangan Tambahan',
    'rekomendasi_mis' => 'Rekomendasi MIS',
    'pt' => 'PT',
    'lokasi' => 'Lokasi',
    'nama_pembuat' => 'Pembuat Surat',
    'pembuat' => 'Pembuat',
    'penyetujui' => 'Penyetujui',
    'peminjam' => 'Peminjam',
    'atasan_peminjam' => 'Atasan Peminjam',
    'diketahui' => 'Diketahui'
];

if ($oldRow && $newRow) {
    // siapkan prepared statement untuk ambil nama kategori (gunakan kolom 'nama')
    $stmtCat = $koneksi->prepare("SELECT nama FROM categories_broken WHERE id = ?");
    $cat_id = 0;

    foreach ($oldRow as $key => $val) {
        if ($key === 'status') continue;
        if ($oldRow[$key] != $newRow[$key]) {
            // header (nama kolom yang lebih manusiawi)
            $header_edit[] = isset($headerMap[$key]) ? $headerMap[$key] : $key;

            // jika field adalah kategori_kerusakan_id, ubah id -> nama kategori
            if ($key === 'kategori_kerusakan_id') {
                // old
                $old_cat_name = '';
                $cat_id = (int)$oldRow[$key];
                if ($cat_id > 0) {
                    $stmtCat->bind_param("i", $cat_id);
                    $stmtCat->execute();
                    $resCat = $stmtCat->get_result();
                    $rCat = $resCat->fetch_assoc();
                    $old_cat_name = $rCat ? $rCat['nama'] : '';
                }

                // new
                $new_cat_name = '';
                $cat_id = (int)$newRow[$key];
                if ($cat_id > 0) {
                    $stmtCat->bind_param("i", $cat_id);
                    $stmtCat->execute();
                    $resCat = $stmtCat->get_result();
                    $rCat = $resCat->fetch_assoc();
                    $new_cat_name = $rCat ? $rCat['nama'] : '';
                }

                $data_edit_lama[] = $old_cat_name;
                $data_edit_baru[] = $new_cat_name;
            } else {
                // default: masukkan apa adanya
                $data_edit_lama[] = $oldRow[$key];
                $data_edit_baru[] = $newRow[$key];
            }
        }
    }

    $stmtCat->close();
}


// masukkan ke data agar ikut terencode JSON
$data['pending_edit'] = $pending_edit;
$data['header_edit'] = $header_edit;
$data['data_edit_lama'] = $data_edit_lama;
$data['data_edit_baru'] = $data_edit_baru;

// normalisasi field tanggal_approve_X
foreach (['tanggal_approve_1','tanggal_approve_2','tanggal_approve_3','tanggal_approve_4','tanggal_approve_5'] as $f) {
    if (!isset($data[$f]) || $data[$f] === '0000-00-00 00:00:00') {
        $data[$f] = null;
    }
}

// pastikan string UTF-8 supaya json_encode aman
array_walk_recursive($data, function(&$v){
    if(is_string($v)) $v = utf8_encode($v);
});
array_walk_recursive($gambar, function(&$v){
    if(is_string($v)) $v = utf8_encode($v);
});
array_walk_recursive($atasan, function(&$v){
    if(is_string($v)) $v = utf8_encode($v);
});
array_walk_recursive($karyawan, function(&$v){
    if(is_string($v)) $v = utf8_encode($v);
});

// tambahkan pengecekan error json_encode
$raw_json = json_encode([
    'data' => $data,
    'gambar' => $gambar,
    'atasan' => $atasan,
    'karyawan' => $karyawan
]);

if ($raw_json === false) {
    $json_error = json_last_error_msg();
    error_log("JSON ENCODE ERROR for BA id=$id: $json_error");
    echo json_encode(['error' => 'Data tidak bisa dikodekan. Cek log server']);
    exit;
}
// // =============================
// // DEBUG DATA FINAL
// // =============================
// echo '<pre style="background:#111;color:#0f0;padding:15px;font-size:13px;">';
// echo "=== DEBUG \$data FINAL ===\n";
// print_r($data);
// echo "\n\n=== DEBUG \$gambar ===\n";
// print_r($gambar);
// echo "\n\n=== DEBUG \$atasan ===\n";
// print_r($atasan);
// echo "\n\n=== DEBUG \$karyawan ===\n";
// print_r($karyawan);
// echo '</pre>';
// exit;
echo $raw_json;


} catch (Throwable $e) {
    echo json_encode(['error' => 'Terjadi kesalahan: '.$e->getMessage()]);
}