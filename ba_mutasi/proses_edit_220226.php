<?php
session_start();

// Jika belum login, arahkan ke halaman login
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../login_registrasi.php");
    exit();
}

// Jika login tapi bukan Admin, arahkan ke halaman approval
if (!isset($_SESSION['hak_akses']) || $_SESSION['hak_akses'] !== 'Admin') {
    header("Location: ../personal/approval.php");
    exit();
}

$error_message = '';

include '../koneksi.php'; // koneksi mysqli

// Pastikan ID berita acara ada
if (!isset($_POST['id']) || empty($_POST['id'])) {
    $_SESSION['message'] = "ID tidak ditemukan. Gagal memperbarui data.";
    header("Location: ba_mutasi.php?status=gagal");
    exit();
}

$id                 = $_POST['id'];
$tanggal            = isset($_POST['tanggal']) ? $_POST['tanggal'] : null;
$nomor_ba           = isset($_POST['nomor_ba']) ? $_POST['nomor_ba'] : null;
$lokasi_asal        = isset($_POST['lokasi_asal']) ? $_POST['lokasi_asal'] : null;
$lokasi_tujuan      = isset($_POST['lokasi_tujuan']) ? $_POST['lokasi_tujuan'] : null;
$nama_pengirim      = isset($_POST['nama_pengirim']) ? $_POST['nama_pengirim'] : null;
$nama_pengirim2     = isset($_POST['nama_pengirim2']) ? $_POST['nama_pengirim2'] : null;
$nama_penerima      = isset($_POST['nama_penerima']) ? $_POST['nama_penerima'] : null;
$nama_penerima2     = isset($_POST['nama_penerima2']) ? $_POST['nama_penerima2'] : null;
$keterangan         = isset($_POST['keterangan']) ? $_POST['keterangan'] : null;
$alasan_perubahan   = isset($_POST['alasan_perubahan']) ? $_POST['alasan_perubahan'] : null;
$pembuat            = $_SESSION['nama'];

// cek data kosong
$kosong = array();

if (empty($tanggal))                $kosong[] = 'Tanggal';
if (empty($nomor_ba))               $kosong[] = 'Nomor BA';
if (empty($lokasi_asal))            $kosong[] = 'Lokasi Asal';
if (empty($lokasi_tujuan))          $kosong[] = 'Lokasi Tujuan';
if (empty($nama_pengirim))          $kosong[] = 'Nama Pengirim';
if (empty($nama_pengirim2))         $kosong[] = 'Nama Pengirim 2';
if (empty($nama_penerima))          $kosong[] = 'Nama Penerima';
if (empty($nama_penerima2))         $kosong[] = 'Nama Penerima 2';
if (empty($keterangan))             $kosong[] = 'Keterangan';
if (empty($alasan_perubahan))       $kosong[] = 'alasan_perubahan';

if (!empty($kosong)) {
    $_SESSION['message'] = "Data Kosong: " . implode(', ', $kosong);
    header("Location: ba_mutasi.php?status=gagal");
    exit();
}

$hrd_ga_pengirim    = "";
$hrd_ga_penerima    = "";


// Jika PT Asal = PT.MSAL (HO), ambil data dari tabel data_karyawan
if ($lokasi_asal === "PT.MSAL (HO)") {
    // Ambil HRD GA Yang menyerahkan aset
    $query_hrd_ga_pengirim = mysqli_query($koneksi, "SELECT nama FROM data_karyawan WHERE posisi = 'Staf GA' AND departemen = 'HRD' LIMIT 1");
    if ($query_hrd_ga_pengirim && mysqli_num_rows($query_hrd_ga_pengirim) > 0) {
        $row_hrd_ga_pengirim = mysqli_fetch_assoc($query_hrd_ga_pengirim);
        $hrd_ga_pengirim = $row_hrd_ga_pengirim['nama'];
    }
} else {
    // Ambil HRD GA Yang menyerahkan aset
    $query_hrd_ga_pengirim = mysqli_query($koneksi, "SELECT nama FROM data_karyawan_test WHERE posisi = 'Staf GA' AND pt = '$lokasi_asal' LIMIT 1");
    if ($query_hrd_ga_pengirim && mysqli_num_rows($query_hrd_ga_pengirim) > 0) {
        $row_hrd_ga_pengirim = mysqli_fetch_assoc($query_hrd_ga_pengirim);
        $hrd_ga_pengirim = $row_hrd_ga_pengirim['nama'];
    }
}
if ($lokasi_tujuan === "PT.MSAL (HO)") {
    // Ambil HRD GA Yang menerima
    $query_hrd_ga_penerima = mysqli_query($koneksi, "SELECT nama FROM data_karyawan WHERE posisi = 'Staf GA' AND departemen = 'HRD' LIMIT 1");
    if ($query_hrd_ga_penerima && mysqli_num_rows($query_hrd_ga_penerima) > 0) {
        $row_hrd_ga_penerima = mysqli_fetch_assoc($query_hrd_ga_penerima);
        $hrd_ga_penerima = $row_hrd_ga_penerima['nama'];
    }
} else{
    // Ambil HRD GA Yang menerima
    $query_hrd_ga_penerima = mysqli_query($koneksi, "SELECT nama FROM data_karyawan_test WHERE posisi = 'Staf GA' AND pt = '$lokasi_tujuan' LIMIT 1");
    if ($query_hrd_ga_penerima && mysqli_num_rows($query_hrd_ga_penerima) > 0) {
        $row_hrd_ga_penerima = mysqli_fetch_assoc($query_hrd_ga_penerima);
        $hrd_ga_penerima = $row_hrd_ga_penerima['nama'];
    }
}

// =======================================================================================================
$query_old_full_required = mysqli_query($koneksi, "
    SELECT *
    FROM berita_acara_mutasi
    WHERE id = '$id'
");

$old_data_to_history_temp_required = mysqli_fetch_assoc($query_old_full_required);



// ---------- DATA LAMA (MASTER) ----------
$data_lama_master = [
    'tanggal'           => $old_data_to_history_temp_required['tanggal'],
    'nomor_ba'          => $old_data_to_history_temp_required['nomor_ba'],
    'pt_asal'           => $old_data_to_history_temp_required['pt_asal'],
    'pt_tujuan'         => $old_data_to_history_temp_required['pt_tujuan'],
    'pengirim1'         => $old_data_to_history_temp_required['pengirim1'],
    'pengirim2'         => $old_data_to_history_temp_required['pengirim2'],
    'penerima1'         => $old_data_to_history_temp_required['penerima1'],
    'penerima2'         => $old_data_to_history_temp_required['penerima2'],
    'hrd_ga_pengirim'   => $old_data_to_history_temp_required['hrd_ga_pengirim'],
    'hrd_ga_penerima'   => $old_data_to_history_temp_required['hrd_ga_penerima'],
    'keterangan'        => $old_data_to_history_temp_required['keterangan']
];

// ---------- DATA BARU (MASTER) ----------
$data_baru_master = [
    'tanggal'           => $tanggal,
    'nomor_ba'          => $nomor_ba,
    'pt_asal'           => $lokasi_asal,
    'pt_tujuan'         => $lokasi_tujuan,
    'pengirim1'         => $nama_pengirim,
    'pengirim2'         => $nama_pengirim2,
    'penerima1'         => $nama_penerima,
    'penerima2'         => $nama_penerima2,
    'hrd_ga_pengirim'   => $hrd_ga_pengirim,
    'hrd_ga_penerima'   => $hrd_ga_penerima,
    'keterangan'        => $keterangan
];

// ---------- CEK PERUBAHAN MASTER ----------
$ada_perubahan = false;

foreach ($data_lama_master as $key => $val_lama) {
    $val_lama = trim((string)$val_lama);
    $val_baru = trim((string)$data_baru_master[$key]);
    if ($val_lama !== $val_baru) {
        $ada_perubahan = true;
        break;
    }
}

// echo "<pre>";
// print_r($val_lama);
// echo "<br>";
// print_r($val_baru);
// echo "<br>";
// var_export($ada_perubahan);

$query_old_barang_required = mysqli_query($koneksi, "
    SELECT *
    FROM barang_mutasi
    WHERE id_ba = '$id'
");

$old_data_barang_to_history_temp_required = [];

while ($row2 = mysqli_fetch_assoc($query_old_barang_required)) {
    $old_data_barang_to_history_temp_required[] = $row2;
}

// ---------- DATA BARANG LAMA ----------
$barang_lama_norm = [];
foreach ($old_data_barang_to_history_temp_required as $ob_required) {
    $barang_lama_norm[] = strtolower(
        trim($ob_required['pt_asal']) . '|' .
        trim($ob_required['po']) . '|' .
        trim($ob_required['coa']) . '|' .
        trim($ob_required['kode_assets']) . '|' .
        trim($ob_required['merk']) . '|' .
        trim($ob_required['sn']) . '|' .
        trim($ob_required['user'])
    );
}
// ---------- DATA BARANG LAMA ----------

// ---------- DATA BARANG BARU ----------
$barang_baru_norm = [];

$pt_asal_list_required = isset($_POST['pt_asal']) ? $_POST['pt_asal'] : [];
$po_list_required      = isset($_POST['po']) ? $_POST['po'] : [];
$coa_list_required     = isset($_POST['coa']) ? $_POST['coa'] : [];
$kode_list_required    = isset($_POST['kode']) ? $_POST['kode'] : [];
$merk_list_required    = isset($_POST['merk']) ? $_POST['merk'] : [];
$sn_list_required      = isset($_POST['sn']) ? $_POST['sn'] : [];
$user_list_required    = isset($_POST['user']) ? $_POST['user'] : [];

// ---------- DATA BARANG BARU ----------

$barang_data = [];
$total = max(count($po_list_required), count($merk_list_required), count($sn_list_required));
for ($i = 0; $i < $total; $i++) {
    $po_required   = isset($po_list_required[$i]) ? trim($po_list_required[$i]) : '';
    $merk = isset($merk_list_required[$i]) ? trim($merk_list_required[$i]) : '';
    $sn   = isset($sn_list_required[$i]) ? trim($sn_list_required[$i]) : '';
    $user = isset($user_list_required[$i]) ? trim($user_list_required[$i]) : '';
    $coa  = isset($coa_list_required[$i]) ? trim($coa_list_required[$i]) : '';
    $kode  = isset($kode_list_required[$i]) ? trim($kode_list_required[$i]) : '';
    $pt   = isset($pt_asal_list_required[$i]) ? trim($pt_asal_list_required[$i]) : '';


    // Lewati baris kosong
    if (empty($merk) && empty($po_required) && empty($sn)) continue;

    // Jika PO kosong, isi default
    if ($po_required == '') $po_required = '';

    // 🔸 Buat kunci unik berdasarkan kombinasi PO + Merk + SN
    $key = strtolower($po_required . '|' . $merk . '|' . $sn);

    // Simpan data unik (jika duplikat, abaikan)
    if (!isset($barang_data[$key])) {
        $barang_data[$key] = [
            'pt_asal' => mysqli_real_escape_string($koneksi, $pt),
            'po'      => mysqli_real_escape_string($koneksi, $po_required),
            'coa'     => mysqli_real_escape_string($koneksi, $coa),
            'kode'    => mysqli_real_escape_string($koneksi, $kode),
            'merk'    => mysqli_real_escape_string($koneksi, $merk),
            'sn'      => mysqli_real_escape_string($koneksi, $sn),
            'user'    => mysqli_real_escape_string($koneksi, $user)
        ];
    }
}
foreach ($barang_data as $b) {
    $barang_baru_norm[] = strtolower(
        trim($b['pt_asal']) . '|' .
        trim($b['po']) . '|' .
        trim($b['coa']) . '|' .
        trim($b['kode']) . '|' .
        trim($b['merk']) . '|' .
        trim($b['sn']) . '|' .
        trim($b['user'])
    );
}

// ---------- NORMALISASI ----------
sort($barang_lama_norm);
sort($barang_baru_norm);

// ---------- CEK PERUBAHAN BARANG ----------
if (!$ada_perubahan && $barang_lama_norm !== $barang_baru_norm) {
    $ada_perubahan = true;
}
// =======================================================================================================

// echo "\n\n=== COMPARISON BARANG ===\n";
// print_r($barang_lama_norm);
// print_r($barang_baru_norm);
// var_export($barang_lama_norm !== $barang_baru_norm);
// echo "</pre>";
// exit;




    // =======================
    // Update Gambar Mutasi (FIX VERSION)
    // =======================

    $image_success = true;
    $image_errors  = [];

    $upload_dir = '../assets/database-gambar/';

    // 1️⃣ Hapus hanya gambar yang benar-benar dipilih user untuk dihapus
    if (isset($_POST['hapus_gambar']) && is_array($_POST['hapus_gambar'])) {
        foreach ($_POST['hapus_gambar'] as $hapus_id) {
            $hapus_id = intval($hapus_id);

            $q_select = mysqli_query($koneksi, "
                SELECT file_path FROM gambar_ba_mutasi 
                WHERE id = '$hapus_id' AND id_ba = '$id'
            ");
            if ($q_select && mysqli_num_rows($q_select) > 0) {
                $r = mysqli_fetch_assoc($q_select);
                if (!empty($r['file_path']) && file_exists($r['file_path'])) {
                    unlink($r['file_path']); // hapus fisik file
                }

                if (!mysqli_query($koneksi, "
                    DELETE FROM gambar_ba_mutasi 
                    WHERE id = '$hapus_id' AND id_ba = '$id'
                ")) {
                    $image_success = false;
                    $image_errors[] = "Gagal menghapus data gambar (ID: $hapus_id)";
                }
            }
        }
    }

    // 2️⃣ Update gambar lama yang diganti dengan file baru
    if (!empty($_FILES['gambar_lama_file']['name'])) {
        foreach ($_FILES['gambar_lama_file']['tmp_name'] as $id_gambar => $tmp_name) {
            if (!empty($tmp_name) && is_uploaded_file($tmp_name)) {
                $filename = basename($_FILES['gambar_lama_file']['name'][$id_gambar]);
                $safe_filename = preg_replace("/[^a-zA-Z0-9._-]/", "_", $filename);
                $target = $upload_dir . time() . '_' . $safe_filename;

                // Hapus file lama sebelum update
                $old = mysqli_query($koneksi, "
                    SELECT file_path FROM gambar_ba_mutasi 
                    WHERE id = '$id_gambar' AND id_ba = '$id'
                ");
                if ($old && mysqli_num_rows($old) > 0) {
                    $old_path = mysqli_fetch_assoc($old)['file_path'];
                    if (file_exists($old_path)) unlink($old_path);
                }

                // Upload baru
                if (move_uploaded_file($tmp_name, $target)) {
                    if (!mysqli_query($koneksi, "
                        UPDATE gambar_ba_mutasi 
                        SET file_path = '$target', uploaded_at = NOW()
                        WHERE id = '$id_gambar' AND id_ba = '$id'
                    ")) {
                        $image_success = false;
                        $image_errors[] = "Gagal update gambar lama (ID: $id_gambar)";
                    }
                } else {
                    $image_success = false;
                    $image_errors[] = "Upload gambar lama gagal (ID: $id_gambar)";
                }

            }
        }
    }

    // 3️⃣ Tambah gambar baru dari upload form
    if (!empty($_FILES['gambar_edit']['tmp_name'])) {
        foreach ($_FILES['gambar_edit']['tmp_name'] as $key => $tmp_name) {
            if (!empty($tmp_name) && is_uploaded_file($tmp_name)) {
                $filename = basename($_FILES['gambar_edit']['name'][$key]);
                $safe_filename = preg_replace("/[^a-zA-Z0-9._-]/", "_", $filename);
                $target = $upload_dir . time() . '_' . $safe_filename;

                if (move_uploaded_file($tmp_name, $target)) {
                    if (!mysqli_query($koneksi, "
                        INSERT INTO gambar_ba_mutasi (id_ba, file_path, uploaded_at)
                        VALUES ('$id', '$target', NOW())
                    ")) {
                        $image_success = false;
                        $image_errors[] = "Gagal menyimpan gambar baru";
                    }
                } else {
                    $image_success = false;
                    $image_errors[] = "Upload gambar baru gagal";
                }

            }
        }
    }

    // 4️⃣ Tambah gambar baru dari kamera (base64)
    if (isset($_POST['gambar_edit_base64'])) {
        foreach ($_POST['gambar_edit_base64'] as $index => $dataURI) {
            if (!empty($dataURI)) {
                $img_data = explode(',', $dataURI);
                if (count($img_data) == 2) {
                    $decoded = base64_decode($img_data[1]);
                    $filename = $upload_dir . 'camera_' . time() . "_$index.png";
                    if (file_put_contents($filename, $decoded) !== false) {
                        if (!mysqli_query($koneksi, "
                            INSERT INTO gambar_ba_mutasi (id_ba, file_path, uploaded_at)
                            VALUES ('$id', '$filename', NOW())
                        ")) {
                            $image_success = false;
                            $image_errors[] = "Gagal menyimpan gambar kamera";
                        }
                    } else {
                        $image_success = false;
                        $image_errors[] = "Gagal menyimpan file gambar kamera";
                    }

                }
            }
        }
    }



// =========================================================================================
if ($ada_perubahan) {
// ===========================================
// Ambil data lama sebelum update (untuk historikal_edit_ba)
// ===========================================
$query_old = mysqli_query($koneksi, "
    SELECT pt_asal AS lokasi_asal, 
           pt_tujuan AS lokasi_tujuan, 
           pengirim1, 
           pengirim2, 
           penerima1, 
           penerima2, 
           hrd_ga_pengirim,
           hrd_ga_penerima,
           keterangan 
    FROM berita_acara_mutasi 
    WHERE id = '$id'
");
$old_data = mysqli_fetch_assoc($query_old);

// ===========================================
// Ambil SELURUH data lama dari berita_acara_mutasi (untuk history_n_temp_ba_mutasi)
// ===========================================
$query_old_full = mysqli_query($koneksi, "
    SELECT *
    FROM berita_acara_mutasi
    WHERE id = '$id'
");

$old_data_to_history_temp = mysqli_fetch_assoc($query_old_full);

// ===========================================
// CEK APPROVAL ADA YANG TIDAK 0?
// ===========================================
$ada_approval = false;
$pending_approver = "";

for ($i = 1; $i <= 11; $i++) {
    if (!empty($old_data_to_history_temp["approval_$i"]) && $old_data_to_history_temp["approval_$i"] != "0") {
        $ada_approval = true;
        break;
    }
}

// ===========================================
// TENTUKAN NILAI status, pending_status, pending_approver
// ===========================================
$status = 0;

if ($ada_approval) {
    $pending_status = 1;
    if ($lokasi_asal === "PT.MSAL (HO)") {
        $pending_approver = "Tedy Paronto";
    } else {
        $query              = $koneksi->query("SELECT nama FROM data_karyawan_test WHERE posisi = 'KTU' AND pt = '$lokasi_asal' LIMIT 1");
        $data               = $query->fetch_assoc();
        $pending_approver   = $data ? $data['nama'] : '-';
    }
} else {
    $pending_status = 0;
    $pending_approver = "";
}

// ===========================================
// DELETE DATA pending_status = 1
// ===========================================

// Hapus dari history_n_temp_ba_mutasi
mysqli_query($koneksi, "
    DELETE FROM history_n_temp_ba_mutasi
    WHERE id_ba = '$id' AND pending_status = 1
");

// Hapus dari historikal_edit_ba
mysqli_query($koneksi, "
    DELETE FROM historikal_edit_ba
    WHERE id_ba = '$id' AND nama_ba = 'mutasi' AND pending_status = 1
");

// ===========================================
// INSERT ke history_n_temp_ba_mutasi
// ===========================================

    $alasan_perubahan_db = mysqli_real_escape_string($koneksi, $alasan_perubahan);

// Pastikan data lama tersedia
if ($old_data_to_history_temp) {

    // Escape semua nilai agar aman
    $escaped = [];
    foreach ($old_data_to_history_temp as $key => $val) {
        $escaped[$key] = mysqli_real_escape_string($koneksi, $val);
    }

    $query_insert_history = "
        INSERT INTO history_n_temp_ba_mutasi (
            id_ba, file_created, tanggal,
            nomor_ba, pembuat, alasan_edit,
            pt_asal,
            pt_tujuan,
            keterangan,

            pengirim1, pengirim2, hrd_ga_pengirim,
            penerima1, penerima2, hrd_ga_penerima,
            diketahui,
            pemeriksa1, pemeriksa2,
            penyetujui1, penyetujui2,

            approval_1, approval_2, approval_3, approval_4,
            approval_5, approval_6, approval_7, approval_8,
            approval_9, approval_10, approval_11,

            autograph_1, autograph_2, autograph_3, autograph_4,
            autograph_5, autograph_6, autograph_7, autograph_8,
            autograph_9, autograph_10, autograph_11,

            tanggal_approve_1, tanggal_approve_2, tanggal_approve_3, tanggal_approve_4,
            tanggal_approve_5, tanggal_approve_6, tanggal_approve_7, tanggal_approve_8,
            tanggal_approve_9, tanggal_approve_10, tanggal_approve_11,

            dihapus,
            status, pending_status, pending_approver
        )
        VALUES (
            '{$escaped['id']}', '{$escaped['created_at']}', '{$escaped['tanggal']}',
            '{$escaped['nomor_ba']}', '{$escaped['pembuat']}', '{$alasan_perubahan_db}',
            '{$escaped['pt_asal']}',
            '{$escaped['pt_tujuan']}',
            '{$escaped['keterangan']}',

            '{$escaped['pengirim1']}', '{$escaped['pengirim2']}', '{$escaped['hrd_ga_pengirim']}',
            '{$escaped['penerima1']}', '{$escaped['penerima2']}', '{$escaped['hrd_ga_penerima']}',
            '{$escaped['diketahui']}',
            '{$escaped['pemeriksa1']}', '{$escaped['pemeriksa2']}',
            '{$escaped['penyetujui1']}', '{$escaped['penyetujui2']}',

            '{$escaped['approval_1']}', '{$escaped['approval_2']}', '{$escaped['approval_3']}', '{$escaped['approval_4']}',
            '{$escaped['approval_5']}', '{$escaped['approval_6']}', '{$escaped['approval_7']}', '{$escaped['approval_8']}',
            '{$escaped['approval_9']}', '{$escaped['approval_10']}', '{$escaped['approval_11']}',

            '{$escaped['autograph_1']}', '{$escaped['autograph_2']}', '{$escaped['autograph_3']}', '{$escaped['autograph_4']}',
            '{$escaped['autograph_5']}', '{$escaped['autograph_6']}', '{$escaped['autograph_7']}', '{$escaped['autograph_8']}', 
            '{$escaped['autograph_9']}', '{$escaped['autograph_10']}', '{$escaped['autograph_11']}', 

            '{$escaped['tanggal_approve_1']}', '{$escaped['tanggal_approve_2']}', '{$escaped['tanggal_approve_3']}', '{$escaped['tanggal_approve_4']}',
            '{$escaped['tanggal_approve_5']}', '{$escaped['tanggal_approve_6']}', '{$escaped['tanggal_approve_7']}', '{$escaped['tanggal_approve_8']}',
            '{$escaped['tanggal_approve_9']}', '{$escaped['tanggal_approve_10']}', '{$escaped['tanggal_approve_11']}',

            '{$escaped['dihapus']}',
            '{$status}', '{$pending_status}', '{$pending_approver}'
        )
    ";

    mysqli_query($koneksi, $query_insert_history);
}

// ===========================================
// JIKA ADA DATA AKTOR YANG BERBEDA → APPROVAL RESET
// ===========================================

// Mapping aktor ke index approval
$aktor_map = [
    1 => ['post' => $nama_pengirim,   'old' => $old_data_to_history_temp['pengirim1']],
    2 => ['post' => $nama_pengirim2,  'old' => $old_data_to_history_temp['pengirim2']],
    3 => ['post' => $hrd_ga_pengirim, 'old' => $old_data_to_history_temp['hrd_ga_pengirim']],
    4 => ['post' => $nama_penerima,   'old' => $old_data_to_history_temp['penerima1']],
    5 => ['post' => $nama_penerima2,  'old' => $old_data_to_history_temp['penerima2']],
    6 => ['post' => $hrd_ga_penerima, 'old' => $old_data_to_history_temp['hrd_ga_penerima']],
];

foreach ($aktor_map as $i => $pair) {
    if ($pair['post'] !== $pair['old']) {
        // Reset approval, autograph, tanggal approve
        $escaped["approval_$i"] = '';
        $escaped["autograph_$i"] = '';
        $escaped["tanggal_approve_$i"] = '';
    }
}

// ===========================================
// JIKA SEMUA APPROVAL = 0 → UPDATE BERITA_ACARA_MUTASI
// ===========================================
if (!$ada_approval) {
// ===========================================
// UPDATE data baru ke berita_acara_mutasi
// ===========================================

$query_update = "
    UPDATE berita_acara_mutasi
    SET
        tanggal = '$tanggal',
        nomor_ba = '$nomor_ba',
        pt_asal = '$lokasi_asal',
        pt_tujuan = '$lokasi_tujuan',
        pengirim1 = '$nama_pengirim',
        pengirim2 = '$nama_pengirim2',
        penerima1 = '$nama_penerima',
        penerima2 = '$nama_penerima2',
        hrd_ga_pengirim = '$hrd_ga_pengirim',
        hrd_ga_penerima = '$hrd_ga_penerima',
        keterangan = '$keterangan'
    WHERE id = '$id'
";
}
// ===========================================
// JIKA ADA APPROVAL ≠ 0 → INSERT DATA BARU KE history_n_temp_ba_mutasi
// ===========================================
else {

$query_update = "
    INSERT INTO history_n_temp_ba_mutasi (
        id_ba, file_created, tanggal,
        nomor_ba, pembuat, alasan_edit,
        pt_asal, pt_tujuan, keterangan,

        pengirim1, pengirim2, hrd_ga_pengirim,
        penerima1, penerima2, hrd_ga_penerima,
        diketahui, pemeriksa1, pemeriksa2,
        penyetujui1, penyetujui2,

        approval_1, approval_2, approval_3, approval_4,
        approval_5, approval_6, approval_7, approval_8,
        approval_9, approval_10, approval_11,

        autograph_1, autograph_2, autograph_3, autograph_4,
        autograph_5, autograph_6, autograph_7, autograph_8,
        autograph_9, autograph_10, autograph_11,

        tanggal_approve_1, tanggal_approve_2, tanggal_approve_3, tanggal_approve_4,
        tanggal_approve_5, tanggal_approve_6, tanggal_approve_7, tanggal_approve_8,
        tanggal_approve_9, tanggal_approve_10, tanggal_approve_11,

        dihapus,
        status, pending_status, pending_approver
    )
    VALUES (
        '{$escaped['id']}', NOW(), '{$tanggal}',
        '{$nomor_ba}', '{$escaped['pembuat']}', '{$alasan_perubahan_db}',
        '{$lokasi_asal}', '{$lokasi_tujuan}', '{$keterangan}',

        '{$nama_pengirim}', '{$nama_pengirim2}', '{$hrd_ga_pengirim}',
        '{$nama_penerima}', '{$nama_penerima2}', '{$hrd_ga_penerima}',
        '{$escaped['diketahui']}', '{$escaped['pemeriksa1']}', '{$escaped['pemeriksa2']}',
        '{$escaped['penyetujui1']}', '{$escaped['penyetujui2']}',

        '{$escaped['approval_1']}', '{$escaped['approval_2']}', '{$escaped['approval_3']}', '{$escaped['approval_4']}',
        '{$escaped['approval_5']}', '{$escaped['approval_6']}', '{$escaped['approval_7']}', '{$escaped['approval_8']}',
        '{$escaped['approval_9']}', '{$escaped['approval_10']}', '{$escaped['approval_11']}',

        '{$escaped['autograph_1']}', '{$escaped['autograph_2']}', '{$escaped['autograph_3']}', '{$escaped['autograph_4']}',
        '{$escaped['autograph_5']}', '{$escaped['autograph_6']}', '{$escaped['autograph_7']}', '{$escaped['autograph_8']}',
        '{$escaped['autograph_9']}', '{$escaped['autograph_10']}', '{$escaped['autograph_11']}',

        '{$escaped['tanggal_approve_1']}', '{$escaped['tanggal_approve_2']}', '{$escaped['tanggal_approve_3']}', '{$escaped['tanggal_approve_4']}',
        '{$escaped['tanggal_approve_5']}', '{$escaped['tanggal_approve_6']}', '{$escaped['tanggal_approve_7']}', '{$escaped['tanggal_approve_8']}',
        '{$escaped['tanggal_approve_9']}', '{$escaped['tanggal_approve_10']}', '{$escaped['tanggal_approve_11']}',

        '{$escaped['dihapus']}',
        1, 1, '{$pending_approver}'
    )
";

}

//Lakukan seluruh proses data barang setelah proses update data utama selesai
if (mysqli_query($koneksi, $query_update)) {

// ===========================================
// Ambil SELURUH data barang lama dari barang_mutasi (untuk history_n_temp_barang_mutasi)
// ===========================================
$query_old_barang = mysqli_query($koneksi, "
    SELECT *
    FROM barang_mutasi
    WHERE id_ba = '$id'
");

$old_data_barang_to_history_temp = [];

while ($row = mysqli_fetch_assoc($query_old_barang)) {
    $old_data_barang_to_history_temp[] = $row;
}

// ===========================================
// Delete SELURUH data barang lama dari barang_mutasi atau history_n_temp_barang_mutasi
// ===========================================
if (!$ada_approval) {
// Hapus dulu semua data lama
mysqli_query($koneksi, "DELETE FROM barang_mutasi WHERE id_ba = '$id'");
} 

else {
mysqli_query($koneksi, "DELETE FROM history_n_temp_barang_mutasi WHERE id_ba = '$id' AND pending_status = '1'");
}

// ===========================================
// Insert history lama barang → history_n_temp_barang_mutasi
// ===========================================
foreach ($old_data_barang_to_history_temp as $ob) {

    $pt_asal_h = mysqli_real_escape_string($koneksi, $ob['pt_asal']);
    $po_h      = mysqli_real_escape_string($koneksi, $ob['po']);
    $coa_h     = mysqli_real_escape_string($koneksi, $ob['coa']);
    $kode_h    = mysqli_real_escape_string($koneksi, $ob['kode_assets']);
    $merk_h    = mysqli_real_escape_string($koneksi, $ob['merk']);
    $sn_h      = mysqli_real_escape_string($koneksi, $ob['sn']);
    $user_h    = mysqli_real_escape_string($koneksi, $ob['user']);

    mysqli_query($koneksi, "
        INSERT INTO history_n_temp_barang_mutasi
        (id_ba, pt_asal, po, coa, kode_assets, merk, sn, user, created_at, status, pending_status)
        VALUES ('$id', '$pt_asal_h', '$po_h', '$coa_h', '$kode_h', '$merk_h', '$sn_h', '$user_h', NOW(), 0, '$pending_status')
    ");
}
// ===========================================
// Get data barang baru
// ===========================================
// Ambil array dari form
$pt_asal_list = isset($_POST['pt_asal']) ? $_POST['pt_asal'] : [];
$po_list      = isset($_POST['po']) ? $_POST['po'] : [];
$coa_list     = isset($_POST['coa']) ? $_POST['coa'] : [];
$kode_list    = isset($_POST['kode']) ? $_POST['kode'] : [];
$merk_list    = isset($_POST['merk']) ? $_POST['merk'] : [];
$sn_list      = isset($_POST['sn']) ? $_POST['sn'] : [];
$user_list    = isset($_POST['user']) ? $_POST['user'] : [];

// Gabungkan semua ke array asosiatif
$barang_data = [];
$total = max(count($po_list), count($merk_list), count($sn_list));

for ($i = 0; $i < $total; $i++) {
    $po   = isset($po_list[$i]) ? trim($po_list[$i]) : '';
    $merk = isset($merk_list[$i]) ? trim($merk_list[$i]) : '';
    $sn   = isset($sn_list[$i]) ? trim($sn_list[$i]) : '';
    $user = isset($user_list[$i]) ? trim($user_list[$i]) : '';
    $coa  = isset($coa_list[$i]) ? trim($coa_list[$i]) : '';
    $kode  = isset($kode_list[$i]) ? trim($kode_list[$i]) : '';
    $pt   = isset($pt_asal_list[$i]) ? trim($pt_asal_list[$i]) : '';


    // Lewati baris kosong
    if (empty($merk) && empty($po) && empty($sn)) continue;

    // Jika PO kosong, isi default
    if ($po == '') $po = '';

    // 🔸 Buat kunci unik berdasarkan kombinasi PO + Merk + SN
    $key = strtolower($po . '|' . $merk . '|' . $sn);

    // Simpan data unik (jika duplikat, abaikan)
    if (!isset($barang_data[$key])) {
        $barang_data[$key] = [
            'pt_asal' => mysqli_real_escape_string($koneksi, $pt),
            'po'      => mysqli_real_escape_string($koneksi, $po),
            'coa'     => mysqli_real_escape_string($koneksi, $coa),
            'kode'    => mysqli_real_escape_string($koneksi, $kode),
            'merk'    => mysqli_real_escape_string($koneksi, $merk),
            'sn'      => mysqli_real_escape_string($koneksi, $sn),
            'user'    => mysqli_real_escape_string($koneksi, $user)
        ];
    }
}

// ===========================================
// Insert data barang baru
// ===========================================
// JIKA TIDAK ADA APPROVAL → insert ke barang_mutasi
if (!$ada_approval) {

foreach ($barang_data as $b) {
    $query_barang = "
        INSERT INTO barang_mutasi (id_ba, pt_asal, po, coa, kode_assets, merk, sn, user, created_at)
        VALUES ('$id', '{$b['pt_asal']}', '{$b['po']}', '{$b['coa']}', '{$b['kode']}', '{$b['merk']}', '{$b['sn']}', '{$b['user']}', NOW())
    ";
    mysqli_query($koneksi, $query_barang);
}
}
// ADA APPROVAL → insert ke history_n_temp_barang_mutasi
else {

    foreach ($barang_data as $b) {

        mysqli_query($koneksi, "
            INSERT INTO history_n_temp_barang_mutasi
            (id_ba, pt_asal, po, coa, kode_assets, merk, sn, user, created_at, status, pending_status)
            VALUES ('$id', '{$b['pt_asal']}', '{$b['po']}', '{$b['coa']}', '{$b['kode']}', '{$b['merk']}', '{$b['sn']}', '{$b['user']}', NOW(), 1, '$pending_status')
        ");
    }
}

// ===========================================
// Catat Riwayat Edit ke tabel historikal_edit_ba
// ===========================================
$new_data = array(
    'lokasi_asal'    => $lokasi_asal,
    'lokasi_tujuan'  => $lokasi_tujuan,
    'pengirim1'      => $nama_pengirim,
    'pengirim2'      => $nama_pengirim2,
    'penerima1'      => $nama_penerima,
    'penerima2'      => $nama_penerima2,
    'hrd_ga_pengirim'=> $hrd_ga_pengirim,
    'hrd_ga_penerima'=> $hrd_ga_penerima,
    'keterangan'     => $keterangan
);

$histori_perubahan = array();

foreach ($new_data as $key => $baru) {
    $lama = isset($old_data[$key]) ? trim($old_data[$key]) : '';
    if ($lama != $baru) {
        $label = str_replace(
            array('lokasi_asal', 'lokasi_tujuan', 'pengirim1', 'pengirim2', 'penerima1', 'penerima2', 'hrd_ga_pengirim', 'hrd_ga_penerima', 'keterangan'),
            array('PT Asal', 'PT Tujuan', 'Nama Pengirim 1', 'Nama Pengirim 2', 'Nama Penerima 1', 'Nama Penerima 2', 'Staf GA Pengirim', 'Staf GA Penerima', 'Keterangan'),
            $key
        );
        $histori_perubahan[] = "$label : $lama diubah ke $baru";
    }
}

// ===========================================
// Catat Perubahan Data Barang (BERDASARKAN MERK)
// ===========================================

// Ambil merk barang lama
$barang_lama = [];
foreach ($old_data_barang_to_history_temp as $ob) {
    if (!empty($ob['merk'])) {
        $barang_lama[] = trim($ob['merk']);
    }
}

// Ambil merk barang baru
$barang_baru = [];
foreach ($barang_data as $b) {
    if (!empty($b['merk'])) {
        $barang_baru[] = trim($b['merk']);
    }
}

// Normalisasi & bandingkan
sort($barang_lama);
sort($barang_baru);

$lama_str = implode(', ', $barang_lama);
$baru_str = implode(', ', $barang_baru);

// Jika berbeda → catat histori
if ($lama_str !== $baru_str) {
    $histori_perubahan[] = "Barang : $lama_str diubah ke $baru_str";
}



if (count($histori_perubahan) > 0) {
    $histori_text = implode(" | ", $histori_perubahan);
    $pengedit = mysqli_real_escape_string($koneksi, $_SESSION['nama']);
    $histori_text = mysqli_real_escape_string($koneksi, $histori_text);
    $pt_lama = mysqli_real_escape_string( $koneksi, $old_data['lokasi_asal']);

    mysqli_query($koneksi, "
        INSERT INTO historikal_edit_ba (id_ba, nama_ba, pt, histori_edit, pengedit, pending_status, pending_approver, tanggal_edit)
        VALUES ('$id', 'mutasi', '$pt_lama', '$histori_text', '$pengedit', '$pending_status', '', NOW())
    ");
}

// echo "<pre>";
// print_r([
//     'pt_asal' => $pt_asal_list,
//     'po'      => $po_list,
//     'coa'     => $coa_list,
//     'kode'    => $kode_list,
//     'merk'    => $merk_list,
//     'sn'      => $sn_list,
//     'user'    => $user_list
// ]);
// print_r($barang_data);
// echo "</pre>";
// exit();
    if($ada_approval){
        $_SESSION['message'] = "Data berita acara dan barang menunggu persetujuan,";
    } else {
        $_SESSION['message'] = "Data berita acara, barang";
    }

    if ($image_success) {
        $_SESSION['message'] .= " dan gambar berhasil diperbarui.";
    }
    else if (!$image_success) {
        $_SESSION['message'] .= " berhasil diperbarui. Terdapat masalah pada proses gambar: " . implode(', ', $image_errors);
    }
        header("Location: ba_mutasi.php?status=sukses");
        exit();
}
else if (!mysqli_query($koneksi, $query_update)) {
    $error_message = mysqli_error($koneksi);
    $_SESSION['message'] = "Gagal memperbarui data: " . $error_message;
    if ($image_success) {
        $_SESSION['message'] .= " Data gambar berhasil diperbarui.";
    }
    if (!$image_success) {
        $_SESSION['message'] .= " Terdapat masalah pada proses gambar: " . implode(', ', $image_errors);
    }
    header("Location: ba_mutasi.php?status=gagal");
    exit();
}
else {
    $_SESSION['message'] = "Gagal memperbarui data: " . mysqli_error($koneksi);
    header("Location: ba_mutasi.php?status=gagal");
    exit();
}
}
// =========================================================================================
if ($image_success) {
    $_SESSION['message'] = "Perubahan gambar berhasil disimpan.";
    header("Location: ba_mutasi.php?status=sukses");
    exit();
}
if (!$image_success) {
    $_SESSION['message'] = "Terdapat masalah pada proses gambar: " . implode(', ', $image_errors);
    header("Location: ba_mutasi.php?status=gagal");
    exit();
}
exit();
?>
