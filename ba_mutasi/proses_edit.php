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

include '../koneksi.php'; // koneksi mysqli

// =========================
// Helper
// =========================
function esc($koneksi, $v) {
    return mysqli_real_escape_string($koneksi, (string)$v);
}

// HO: jabatan + departemen (berdasarkan nama)
function getJabatanHO($koneksi, $nama) {
    $nama = trim((string)$nama);
    if ($nama === '') return '-';
    $nama_esc = esc($koneksi, $nama);

    $q = mysqli_query($koneksi, "
        SELECT jabatan, departemen
        FROM data_karyawan
        WHERE nama = '$nama_esc' AND dihapus = 0
        LIMIT 1
    ");
    if (!$q || mysqli_num_rows($q) === 0) return '-';

    $r = mysqli_fetch_assoc($q);
    $jab = trim((string)$r['jabatan']);
    $dep = trim((string)$r['departemen']);

    if ($jab === '' && $dep === '') return '-';
    if ($dep === '') return $jab;
    if ($jab === '') return $dep;

    return $jab . " - " . $dep;
}

// Non-HO: posisi (berdasarkan nama)
function getPosisiNonHO($koneksi, $nama) {
    $nama = trim((string)$nama);
    if ($nama === '') return '-';
    $nama_esc = esc($koneksi, $nama);

    $q = mysqli_query($koneksi, "
        SELECT posisi
        FROM data_karyawan_test
        WHERE nama = '$nama_esc' AND dihapus = 0
        LIMIT 1
    ");
    if (!$q || mysqli_num_rows($q) === 0) return '-';

    $r = mysqli_fetch_assoc($q);
    $pos = trim((string)$r['posisi']);
    return ($pos === '') ? '-' : $pos;
}

function getJabatanByLokasi($koneksi, $lokasi, $nama) {
    if ($lokasi === "PT.MSAL (HO)") {
        return getJabatanHO($koneksi, $nama);
    }
    return getPosisiNonHO($koneksi, $nama);
}

// =========================
// Validasi ID
// =========================
if (!isset($_POST['id']) || empty($_POST['id'])) {
    $_SESSION['message'] = "ID tidak ditemukan. Gagal memperbarui data.";
    header("Location: ba_mutasi.php?status=gagal");
    exit();
}

$id = intval($_POST['id']);

// =========================
// Ambil data dari form
// =========================
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
$pembuat            = isset($_SESSION['nama']) ? $_SESSION['nama'] : '';

// =========================
// Validasi kosong
// =========================
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

if ($lokasi_asal === $lokasi_tujuan) {
    $_SESSION['message'] = "Lokasi Asal dan Lokasi Tujuan tidak boleh sama.";
    header("Location: ba_mutasi.php?status=gagal");
    exit();
}

// Escape input utama (dipakai di query string)
$tanggal_esc          = esc($koneksi, $tanggal);
$nomor_ba_esc         = esc($koneksi, $nomor_ba);
$lokasi_asal_esc      = esc($koneksi, $lokasi_asal);
$lokasi_tujuan_esc    = esc($koneksi, $lokasi_tujuan);
$nama_pengirim_esc    = esc($koneksi, $nama_pengirim);
$nama_pengirim2_esc   = esc($koneksi, $nama_pengirim2);
$nama_penerima_esc    = esc($koneksi, $nama_penerima);
$nama_penerima2_esc   = esc($koneksi, $nama_penerima2);
$keterangan_esc       = esc($koneksi, $keterangan);
$alasan_perubahan_esc = esc($koneksi, $alasan_perubahan);

// =========================
// Mapping PT -> ID
// =========================
$map_pt = array(
    'PT.MSAL (HO)'    => 1,
    'PT.MSAL (PKS)'   => 2,
    'PT.MSAL (SITE)'  => 3,
    'PT.PSAM (PKS)'   => 4,
    'PT.PSAM (SITE)'  => 5,
    'PT.MAPA'         => 6,
    'PT.PEAK (PKS)'   => 7,
    'PT.PEAK (SITE)'  => 8,
    'RO PALANGKARAYA' => 9,
    'RO SAMPIT'       => 10,
    'PT.WCJU (SITE)'  => 11,
    'PT.WCJU (PKS)'   => 12
);

$lokasi_asal_norm   = trim($lokasi_asal);
$lokasi_tujuan_norm = trim($lokasi_tujuan);

$id_pt_asal   = isset($map_pt[$lokasi_asal_norm]) ? (int)$map_pt[$lokasi_asal_norm] : 0;
$id_pt_tujuan = isset($map_pt[$lokasi_tujuan_norm]) ? (int)$map_pt[$lokasi_tujuan_norm] : 0;

if ($id_pt_asal === 0 || $id_pt_tujuan === 0) {
    $_SESSION['message'] = "PT Asal/Tujuan tidak valid atau belum ada mapping ID.";
    header("Location: ba_mutasi.php?status=gagal");
    exit();
}

// =========================
// Ambil data lama full (dibutuhkan banyak bagian)
// =========================
$query_old_full_required = mysqli_query($koneksi, "
    SELECT *
    FROM berita_acara_mutasi
    WHERE id = $id
");
$old_data_to_history_temp_required = $query_old_full_required ? mysqli_fetch_assoc($query_old_full_required) : null;

if (!$old_data_to_history_temp_required) {
    $_SESSION['message'] = "Data BA tidak ditemukan. Gagal edit.";
    header("Location: ba_mutasi.php?status=gagal");
    exit();
}

// =========================
// Ambil HRD/GA pengirim & penerima (support multi-PT non-HO)
// =========================
$hrd_ga_pengirim = "";
$hrd_ga_penerima = "";

// Normalisasi CSV pt untuk FIND_IN_SET
$ptNorm = "REPLACE(REPLACE(TRIM(pt), ', ', ','), ' ,', ',')";

// Pengirim mengikuti lokasi_asal
if ($lokasi_asal === "PT.MSAL (HO)") {
    $q = mysqli_query($koneksi, "
        SELECT nama
        FROM data_karyawan
        WHERE posisi = 'Staf GA' AND departemen = 'HRD' AND dihapus = 0
        LIMIT 1
    ");
    if ($q && mysqli_num_rows($q) > 0) $hrd_ga_pengirim = mysqli_fetch_assoc($q)['nama'];
} else {
    $q = mysqli_query($koneksi, "
        SELECT nama
        FROM data_karyawan_test
        WHERE posisi = 'Staf GA'
          AND dihapus = 0
          AND FIND_IN_SET('$lokasi_asal_esc', $ptNorm) > 0
        LIMIT 1
    ");
    if ($q && mysqli_num_rows($q) > 0) $hrd_ga_pengirim = mysqli_fetch_assoc($q)['nama'];
}

// Penerima mengikuti lokasi_tujuan
if ($lokasi_tujuan === "PT.MSAL (HO)") {
    $q = mysqli_query($koneksi, "
        SELECT nama
        FROM data_karyawan
        WHERE posisi = 'Staf GA' AND departemen = 'HRD' AND dihapus = 0
        LIMIT 1
    ");
    if ($q && mysqli_num_rows($q) > 0) $hrd_ga_penerima = mysqli_fetch_assoc($q)['nama'];
} else {
    $q = mysqli_query($koneksi, "
        SELECT nama
        FROM data_karyawan_test
        WHERE posisi = 'Staf GA'
          AND dihapus = 0
          AND FIND_IN_SET('$lokasi_tujuan_esc', $ptNorm) > 0
        LIMIT 1
    ");
    if ($q && mysqli_num_rows($q) > 0) $hrd_ga_penerima = mysqli_fetch_assoc($q)['nama'];
}

$hrd_ga_pengirim_esc = esc($koneksi, $hrd_ga_pengirim);
$hrd_ga_penerima_esc = esc($koneksi, $hrd_ga_penerima);

// =========================
// Hitung jabatan_* sesuai aturan
// =========================
// Pengirim mengikuti lokasi_asal
$jabatan_pengirim1       = getJabatanByLokasi($koneksi, $lokasi_asal, $nama_pengirim);
$jabatan_pengirim2       = getJabatanByLokasi($koneksi, $lokasi_asal, $nama_pengirim2);
$jabatan_hrd_ga_pengirim = getJabatanByLokasi($koneksi, $lokasi_asal, $hrd_ga_pengirim);

// Penerima mengikuti lokasi_tujuan
$jabatan_penerima1       = getJabatanByLokasi($koneksi, $lokasi_tujuan, $nama_penerima);
$jabatan_penerima2       = getJabatanByLokasi($koneksi, $lokasi_tujuan, $nama_penerima2);
$jabatan_hrd_ga_penerima = getJabatanByLokasi($koneksi, $lokasi_tujuan, $hrd_ga_penerima);

// Sisanya tetap HO (pakai nama dari data lama)
$diketahui   = $old_data_to_history_temp_required['diketahui'];
$pemeriksa1  = $old_data_to_history_temp_required['pemeriksa1'];
$pemeriksa2  = $old_data_to_history_temp_required['pemeriksa2'];
$penyetujui1 = $old_data_to_history_temp_required['penyetujui1'];
$penyetujui2 = $old_data_to_history_temp_required['penyetujui2'];

$jabatan_diketahui   = getJabatanHO($koneksi, $diketahui);
$jabatan_pemeriksa1  = getJabatanHO($koneksi, $pemeriksa1);
$jabatan_pemeriksa2  = getJabatanHO($koneksi, $pemeriksa2);
$jabatan_penyetujui1 = getJabatanHO($koneksi, $penyetujui1);
$jabatan_penyetujui2 = getJabatanHO($koneksi, $penyetujui2);

// Escape jabatan untuk query string
$jab_pengirim1_esc       = esc($koneksi, $jabatan_pengirim1);
$jab_pengirim2_esc       = esc($koneksi, $jabatan_pengirim2);
$jab_hrd_ga_pengirim_esc = esc($koneksi, $jabatan_hrd_ga_pengirim);

$jab_penerima1_esc       = esc($koneksi, $jabatan_penerima1);
$jab_penerima2_esc       = esc($koneksi, $jabatan_penerima2);
$jab_hrd_ga_penerima_esc = esc($koneksi, $jabatan_hrd_ga_penerima);

$jab_diketahui_esc   = esc($koneksi, $jabatan_diketahui);
$jab_pemeriksa1_esc  = esc($koneksi, $jabatan_pemeriksa1);
$jab_pemeriksa2_esc  = esc($koneksi, $jabatan_pemeriksa2);
$jab_penyetujui1_esc = esc($koneksi, $jabatan_penyetujui1);
$jab_penyetujui2_esc = esc($koneksi, $jabatan_penyetujui2);

// =======================================================================================================
// CEK PERUBAHAN MASTER + BARANG (logika kamu tetap, tapi hrd_ga_* pakai yang baru)
// =======================================================================================================

// ---------- DATA LAMA (MASTER) ----------
$data_lama_master = array(
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
);

// ---------- DATA BARU (MASTER) ----------
$data_baru_master = array(
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
);

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

// ---------- DATA BARANG LAMA ----------
$query_old_barang_required = mysqli_query($koneksi, "
    SELECT *
    FROM barang_mutasi
    WHERE id_ba = $id
");
$old_data_barang_to_history_temp_required = array();
while ($row2 = mysqli_fetch_assoc($query_old_barang_required)) {
    $old_data_barang_to_history_temp_required[] = $row2;
}

$barang_lama_norm = array();
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

// ---------- DATA BARANG BARU ----------
$pt_asal_list_required = isset($_POST['pt_asal']) ? $_POST['pt_asal'] : array();
$po_list_required      = isset($_POST['po']) ? $_POST['po'] : array();
$coa_list_required     = isset($_POST['coa']) ? $_POST['coa'] : array();
$kode_list_required    = isset($_POST['kode']) ? $_POST['kode'] : array();
$merk_list_required    = isset($_POST['merk']) ? $_POST['merk'] : array();
$sn_list_required      = isset($_POST['sn']) ? $_POST['sn'] : array();
$user_list_required    = isset($_POST['user']) ? $_POST['user'] : array();

$barang_data = array();
$total = max(count($po_list_required), count($merk_list_required), count($sn_list_required));

for ($i = 0; $i < $total; $i++) {
    $po_required = isset($po_list_required[$i]) ? trim($po_list_required[$i]) : '';
    $merk = isset($merk_list_required[$i]) ? trim($merk_list_required[$i]) : '';
    $sn   = isset($sn_list_required[$i]) ? trim($sn_list_required[$i]) : '';
    $user = isset($user_list_required[$i]) ? trim($user_list_required[$i]) : '';
    $coa  = isset($coa_list_required[$i]) ? trim($coa_list_required[$i]) : '';
    $kode = isset($kode_list_required[$i]) ? trim($kode_list_required[$i]) : '';
    $pt   = isset($pt_asal_list_required[$i]) ? trim($pt_asal_list_required[$i]) : '';

    if (empty($merk) && empty($po_required) && empty($sn)) continue;

    $key = strtolower($po_required . '|' . $merk . '|' . $sn);

    if (!isset($barang_data[$key])) {
        $barang_data[$key] = array(
            'pt_asal' => esc($koneksi, $pt),
            'po'      => esc($koneksi, $po_required),
            'coa'     => esc($koneksi, $coa),
            'kode'    => esc($koneksi, $kode),
            'merk'    => esc($koneksi, $merk),
            'sn'      => esc($koneksi, $sn),
            'user'    => esc($koneksi, $user)
        );
    }
}

$barang_baru_norm = array();
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

sort($barang_lama_norm);
sort($barang_baru_norm);

if (!$ada_perubahan && $barang_lama_norm !== $barang_baru_norm) {
    $ada_perubahan = true;
}

// =======================================================================================================
// Update Gambar Mutasi (kode kamu, saya biarkan, cuma minor aman)
// =======================================================================================================
$image_success = true;
$image_errors  = array();
$upload_dir = '../assets/database-gambar/';

// 1) Hapus gambar yang dipilih user
if (isset($_POST['hapus_gambar']) && is_array($_POST['hapus_gambar'])) {
    foreach ($_POST['hapus_gambar'] as $hapus_id) {
        $hapus_id = intval($hapus_id);

        $q_select = mysqli_query($koneksi, "
            SELECT file_path FROM gambar_ba_mutasi 
            WHERE id = $hapus_id AND id_ba = $id
        ");
        if ($q_select && mysqli_num_rows($q_select) > 0) {
            $r = mysqli_fetch_assoc($q_select);
            if (!empty($r['file_path']) && file_exists($r['file_path'])) {
                @unlink($r['file_path']);
            }

            if (!mysqli_query($koneksi, "
                DELETE FROM gambar_ba_mutasi 
                WHERE id = $hapus_id AND id_ba = $id
            ")) {
                $image_success = false;
                $image_errors[] = "Gagal menghapus data gambar (ID: $hapus_id)";
            }
        }
    }
}

// 2) Update gambar lama yang diganti file baru
if (!empty($_FILES['gambar_lama_file']['name'])) {
    foreach ($_FILES['gambar_lama_file']['tmp_name'] as $id_gambar => $tmp_name) {
        $id_gambar = intval($id_gambar);

        if (!empty($tmp_name) && is_uploaded_file($tmp_name)) {
            $filename = basename($_FILES['gambar_lama_file']['name'][$id_gambar]);
            $safe_filename = preg_replace("/[^a-zA-Z0-9._-]/", "_", $filename);
            $target = $upload_dir . time() . '_' . $safe_filename;

            $old = mysqli_query($koneksi, "
                SELECT file_path FROM gambar_ba_mutasi 
                WHERE id = $id_gambar AND id_ba = $id
            ");
            if ($old && mysqli_num_rows($old) > 0) {
                $old_path = mysqli_fetch_assoc($old)['file_path'];
                if (file_exists($old_path)) @unlink($old_path);
            }

            if (move_uploaded_file($tmp_name, $target)) {
                $target_esc = esc($koneksi, $target);
                if (!mysqli_query($koneksi, "
                    UPDATE gambar_ba_mutasi 
                    SET file_path = '$target_esc', uploaded_at = NOW()
                    WHERE id = $id_gambar AND id_ba = $id
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

// 3) Tambah gambar baru
if (!empty($_FILES['gambar_edit']['tmp_name'])) {
    foreach ($_FILES['gambar_edit']['tmp_name'] as $key => $tmp_name) {
        if (!empty($tmp_name) && is_uploaded_file($tmp_name)) {
            $filename = basename($_FILES['gambar_edit']['name'][$key]);
            $safe_filename = preg_replace("/[^a-zA-Z0-9._-]/", "_", $filename);
            $target = $upload_dir . time() . '_' . $safe_filename;

            if (move_uploaded_file($tmp_name, $target)) {
                $target_esc = esc($koneksi, $target);
                if (!mysqli_query($koneksi, "
                    INSERT INTO gambar_ba_mutasi (id_ba, file_path, uploaded_at)
                    VALUES ($id, '$target_esc', NOW())
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

// 4) Tambah gambar base64 (kamera) - jika ada
if (isset($_POST['gambar_edit_base64']) && is_array($_POST['gambar_edit_base64'])) {
    foreach ($_POST['gambar_edit_base64'] as $index => $dataURI) {
        if (!empty($dataURI)) {
            $img_data = explode(',', $dataURI);
            if (count($img_data) == 2) {
                $decoded = base64_decode($img_data[1]);
                $filename = $upload_dir . 'camera_' . time() . "_$index.png";

                if (file_put_contents($filename, $decoded) !== false) {
                    $filename_esc = esc($koneksi, $filename);
                    if (!mysqli_query($koneksi, "
                        INSERT INTO gambar_ba_mutasi (id_ba, file_path, uploaded_at)
                        VALUES ($id, '$filename_esc', NOW())
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
// Jika ada perubahan master/barang -> jalankan sistem edit utama
// =========================================================================================
if ($ada_perubahan) {

    // Ambil data lama ringkas (untuk historikal_edit_ba)
    $query_old = mysqli_query($koneksi, "
        SELECT pt_asal AS lokasi_asal, 
               pt_tujuan AS lokasi_tujuan, 
               pengirim1, pengirim2, penerima1, penerima2, 
               hrd_ga_pengirim, hrd_ga_penerima,
               keterangan
        FROM berita_acara_mutasi
        WHERE id = $id
    ");
    $old_data = $query_old ? mysqli_fetch_assoc($query_old) : array();

    // Ambil full data lama (untuk history)
    $query_old_full = mysqli_query($koneksi, "
        SELECT *
        FROM berita_acara_mutasi
        WHERE id = $id
    ");
    $old_data_to_history_temp = $query_old_full ? mysqli_fetch_assoc($query_old_full) : null;

    if (!$old_data_to_history_temp) {
        $_SESSION['message'] = "Gagal mengambil data lama untuk histori.";
        header("Location: ba_mutasi.php?status=gagal");
        exit();
    }

    // Cek apakah ada approval != 0
    $ada_approval = false;
    for ($i = 1; $i <= 11; $i++) {
        if (!empty($old_data_to_history_temp["approval_$i"]) && $old_data_to_history_temp["approval_$i"] != "0") {
            $ada_approval = true;
            break;
        }
    }

    // Tentukan status pending
    $status = 0;
    $pending_status = 0;
    $pending_approver = "";

    if ($ada_approval) {
        $pending_status = 1;
        if ($lokasi_asal === "PT.MSAL (HO)") {
            $pending_approver = "Tedy Paronto";
        } else {
            // Approver KTU dari PT asal (support multi PT)
            $q = mysqli_query($koneksi, "
                SELECT nama
                FROM data_karyawan_test
                WHERE posisi = 'KTU'
                  AND dihapus = 0
                  AND FIND_IN_SET('$lokasi_asal_esc', $ptNorm) > 0
                LIMIT 1
            ");
            $data = $q ? mysqli_fetch_assoc($q) : null;
            $pending_approver = $data ? $data['nama'] : '-';
        }
    }

    $pending_approver_esc = esc($koneksi, $pending_approver);

    // Hapus pending lama (history temp & historikal edit) biar tidak numpuk
    mysqli_query($koneksi, "DELETE FROM history_n_temp_ba_mutasi WHERE id_ba = $id AND pending_status = 1");
    mysqli_query($koneksi, "DELETE FROM historikal_edit_ba WHERE id_ba = $id AND nama_ba = 'mutasi' AND pending_status = 1");

    // =============================
    // INSERT HISTORY: DATA LAMA
    // =============================
    $escaped = array();
    foreach ($old_data_to_history_temp as $key => $val) {
        $escaped[$key] = esc($koneksi, $val);
    }

    // (optional) fallback kalau jabatan lama kosong -> hitung ulang
    if (!isset($escaped['jabatan_pengirim1']) || trim($escaped['jabatan_pengirim1']) === '') {
        $escaped['jabatan_pengirim1'] = esc($koneksi, getJabatanByLokasi($koneksi, $old_data_to_history_temp['pt_asal'], $old_data_to_history_temp['pengirim1']));
    }
    if (!isset($escaped['jabatan_pengirim2']) || trim($escaped['jabatan_pengirim2']) === '') {
        $escaped['jabatan_pengirim2'] = esc($koneksi, getJabatanByLokasi($koneksi, $old_data_to_history_temp['pt_asal'], $old_data_to_history_temp['pengirim2']));
    }
    if (!isset($escaped['jabatan_hrd_ga_pengirim']) || trim($escaped['jabatan_hrd_ga_pengirim']) === '') {
        $escaped['jabatan_hrd_ga_pengirim'] = esc($koneksi, getJabatanByLokasi($koneksi, $old_data_to_history_temp['pt_asal'], $old_data_to_history_temp['hrd_ga_pengirim']));
    }
    if (!isset($escaped['jabatan_penerima1']) || trim($escaped['jabatan_penerima1']) === '') {
        $escaped['jabatan_penerima1'] = esc($koneksi, getJabatanByLokasi($koneksi, $old_data_to_history_temp['pt_tujuan'], $old_data_to_history_temp['penerima1']));
    }
    if (!isset($escaped['jabatan_penerima2']) || trim($escaped['jabatan_penerima2']) === '') {
        $escaped['jabatan_penerima2'] = esc($koneksi, getJabatanByLokasi($koneksi, $old_data_to_history_temp['pt_tujuan'], $old_data_to_history_temp['penerima2']));
    }
    if (!isset($escaped['jabatan_hrd_ga_penerima']) || trim($escaped['jabatan_hrd_ga_penerima']) === '') {
        $escaped['jabatan_hrd_ga_penerima'] = esc($koneksi, getJabatanByLokasi($koneksi, $old_data_to_history_temp['pt_tujuan'], $old_data_to_history_temp['hrd_ga_penerima']));
    }
    if (!isset($escaped['jabatan_diketahui']) || trim($escaped['jabatan_diketahui']) === '') {
        $escaped['jabatan_diketahui'] = esc($koneksi, getJabatanHO($koneksi, $old_data_to_history_temp['diketahui']));
    }
    if (!isset($escaped['jabatan_pemeriksa1']) || trim($escaped['jabatan_pemeriksa1']) === '') {
        $escaped['jabatan_pemeriksa1'] = esc($koneksi, getJabatanHO($koneksi, $old_data_to_history_temp['pemeriksa1']));
    }
    if (!isset($escaped['jabatan_pemeriksa2']) || trim($escaped['jabatan_pemeriksa2']) === '') {
        $escaped['jabatan_pemeriksa2'] = esc($koneksi, getJabatanHO($koneksi, $old_data_to_history_temp['pemeriksa2']));
    }
    if (!isset($escaped['jabatan_penyetujui1']) || trim($escaped['jabatan_penyetujui1']) === '') {
        $escaped['jabatan_penyetujui1'] = esc($koneksi, getJabatanHO($koneksi, $old_data_to_history_temp['penyetujui1']));
    }
    if (!isset($escaped['jabatan_penyetujui2']) || trim($escaped['jabatan_penyetujui2']) === '') {
        $escaped['jabatan_penyetujui2'] = esc($koneksi, getJabatanHO($koneksi, $old_data_to_history_temp['penyetujui2']));
    }

    // Insert data lama ke history
    $query_insert_history = "
        INSERT INTO history_n_temp_ba_mutasi (
            id_ba, file_created, tanggal,
            nomor_ba, pembuat, alasan_edit,

            pt_asal, id_pt_asal,
            pt_tujuan, id_pt_tujuan,

            keterangan,

            pengirim1, jabatan_pengirim1,
            pengirim2, jabatan_pengirim2,
            hrd_ga_pengirim, jabatan_hrd_ga_pengirim,

            penerima1, jabatan_penerima1,
            penerima2, jabatan_penerima2,
            hrd_ga_penerima, jabatan_hrd_ga_penerima,

            diketahui, jabatan_diketahui,
            pemeriksa1, jabatan_pemeriksa1,
            pemeriksa2, jabatan_pemeriksa2,
            penyetujui1, jabatan_penyetujui1,
            penyetujui2, jabatan_penyetujui2,

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
            '{$escaped['nomor_ba']}', '{$escaped['pembuat']}', '$alasan_perubahan_esc',

            '{$escaped['pt_asal']}', '{$escaped['id_pt_asal']}',
            '{$escaped['pt_tujuan']}', '{$escaped['id_pt_tujuan']}',

            '{$escaped['keterangan']}',

            '{$escaped['pengirim1']}', '{$escaped['jabatan_pengirim1']}',
            '{$escaped['pengirim2']}', '{$escaped['jabatan_pengirim2']}',
            '{$escaped['hrd_ga_pengirim']}', '{$escaped['jabatan_hrd_ga_pengirim']}',

            '{$escaped['penerima1']}', '{$escaped['jabatan_penerima1']}',
            '{$escaped['penerima2']}', '{$escaped['jabatan_penerima2']}',
            '{$escaped['hrd_ga_penerima']}', '{$escaped['jabatan_hrd_ga_penerima']}',

            '{$escaped['diketahui']}', '{$escaped['jabatan_diketahui']}',
            '{$escaped['pemeriksa1']}', '{$escaped['jabatan_pemeriksa1']}',
            '{$escaped['pemeriksa2']}', '{$escaped['jabatan_pemeriksa2']}',
            '{$escaped['penyetujui1']}', '{$escaped['jabatan_penyetujui1']}',
            '{$escaped['penyetujui2']}', '{$escaped['jabatan_penyetujui2']}',

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
            '$status', '$pending_status', '$pending_approver_esc'
        )
    ";
    mysqli_query($koneksi, $query_insert_history);

    // =============================
    // Reset approval tertentu jika aktor berubah
    // =============================
    $aktor_map = array(
        1 => array('post' => $nama_pengirim,   'old' => $old_data_to_history_temp['pengirim1']),
        2 => array('post' => $nama_pengirim2,  'old' => $old_data_to_history_temp['pengirim2']),
        3 => array('post' => $hrd_ga_pengirim, 'old' => $old_data_to_history_temp['hrd_ga_pengirim']),
        4 => array('post' => $nama_penerima,   'old' => $old_data_to_history_temp['penerima1']),
        5 => array('post' => $nama_penerima2,  'old' => $old_data_to_history_temp['penerima2']),
        6 => array('post' => $hrd_ga_penerima, 'old' => $old_data_to_history_temp['hrd_ga_penerima']),
    );

    foreach ($aktor_map as $i => $pair) {
        if ((string)$pair['post'] !== (string)$pair['old']) {
            $escaped["approval_$i"] = '0';
            $escaped["autograph_$i"] = '';
            $escaped["tanggal_approve_$i"] = '0000-00-00 00:00:00';
        }
    }

    // =============================
    // Tentukan $query_update
    // =============================
    if (!$ada_approval) {
        // UPDATE langsung ke berita_acara_mutasi + field baru
        $query_update = "
            UPDATE berita_acara_mutasi
            SET
                tanggal = '$tanggal_esc',
                nomor_ba = '$nomor_ba_esc',

                pt_asal = '$lokasi_asal_esc',
                id_pt_asal = $id_pt_asal,

                pt_tujuan = '$lokasi_tujuan_esc',
                id_pt_tujuan = $id_pt_tujuan,

                pengirim1 = '$nama_pengirim_esc',
                jabatan_pengirim1 = '$jab_pengirim1_esc',

                pengirim2 = '$nama_pengirim2_esc',
                jabatan_pengirim2 = '$jab_pengirim2_esc',

                hrd_ga_pengirim = '$hrd_ga_pengirim_esc',
                jabatan_hrd_ga_pengirim = '$jab_hrd_ga_pengirim_esc',

                penerima1 = '$nama_penerima_esc',
                jabatan_penerima1 = '$jab_penerima1_esc',

                penerima2 = '$nama_penerima2_esc',
                jabatan_penerima2 = '$jab_penerima2_esc',

                hrd_ga_penerima = '$hrd_ga_penerima_esc',
                jabatan_hrd_ga_penerima = '$jab_hrd_ga_penerima_esc',

                diketahui = '" . esc($koneksi, $diketahui) . "',
                jabatan_diketahui = '$jab_diketahui_esc',

                pemeriksa1 = '" . esc($koneksi, $pemeriksa1) . "',
                jabatan_pemeriksa1 = '$jab_pemeriksa1_esc',

                pemeriksa2 = '" . esc($koneksi, $pemeriksa2) . "',
                jabatan_pemeriksa2 = '$jab_pemeriksa2_esc',

                penyetujui1 = '" . esc($koneksi, $penyetujui1) . "',
                jabatan_penyetujui1 = '$jab_penyetujui1_esc',

                penyetujui2 = '" . esc($koneksi, $penyetujui2) . "',
                jabatan_penyetujui2 = '$jab_penyetujui2_esc',

                keterangan = '$keterangan_esc'
            WHERE id = $id
        ";
    } else {
        // INSERT data baru ke history_n_temp_ba_mutasi + field baru + approval reset sesuai $escaped
        $query_update = "
            INSERT INTO history_n_temp_ba_mutasi (
                id_ba, file_created, tanggal,
                nomor_ba, pembuat, alasan_edit,

                pt_asal, id_pt_asal,
                pt_tujuan, id_pt_tujuan,

                keterangan,

                pengirim1, jabatan_pengirim1,
                pengirim2, jabatan_pengirim2,
                hrd_ga_pengirim, jabatan_hrd_ga_pengirim,

                penerima1, jabatan_penerima1,
                penerima2, jabatan_penerima2,
                hrd_ga_penerima, jabatan_hrd_ga_penerima,

                diketahui, jabatan_diketahui,
                pemeriksa1, jabatan_pemeriksa1,
                pemeriksa2, jabatan_pemeriksa2,
                penyetujui1, jabatan_penyetujui1,
                penyetujui2, jabatan_penyetujui2,

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
                '{$escaped['id']}', NOW(), '$tanggal_esc',
                '$nomor_ba_esc', '{$escaped['pembuat']}', '$alasan_perubahan_esc',

                '$lokasi_asal_esc', '$id_pt_asal',
                '$lokasi_tujuan_esc', '$id_pt_tujuan',

                '$keterangan_esc',

                '$nama_pengirim_esc', '$jab_pengirim1_esc',
                '$nama_pengirim2_esc', '$jab_pengirim2_esc',
                '$hrd_ga_pengirim_esc', '$jab_hrd_ga_pengirim_esc',

                '$nama_penerima_esc', '$jab_penerima1_esc',
                '$nama_penerima2_esc', '$jab_penerima2_esc',
                '$hrd_ga_penerima_esc', '$jab_hrd_ga_penerima_esc',

                '{$escaped['diketahui']}', '$jab_diketahui_esc',
                '{$escaped['pemeriksa1']}', '$jab_pemeriksa1_esc',
                '{$escaped['pemeriksa2']}', '$jab_pemeriksa2_esc',
                '{$escaped['penyetujui1']}', '$jab_penyetujui1_esc',
                '{$escaped['penyetujui2']}', '$jab_penyetujui2_esc',

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
                1, 1, '$pending_approver_esc'
            )
        ";
    }

    // ===========================================
    // EKSEKUSI $query_update SEKALI SAJA (fix bug double execute)
    // ===========================================
    $okUpdate = mysqli_query($koneksi, $query_update);

    if ($okUpdate) {

        // ===========================================
        // PROSES BARANG (pakai logika kamu)
        // ===========================================
        $query_old_barang = mysqli_query($koneksi, "
            SELECT * FROM barang_mutasi
            WHERE id_ba = $id
        ");

        $old_data_barang_to_history_temp = array();
        while ($row = mysqli_fetch_assoc($query_old_barang)) {
            $old_data_barang_to_history_temp[] = $row;
        }

        // delete data barang lama sesuai mode
        if (!$ada_approval) {
            mysqli_query($koneksi, "DELETE FROM barang_mutasi WHERE id_ba = $id");
        } else {
            mysqli_query($koneksi, "DELETE FROM history_n_temp_barang_mutasi WHERE id_ba = $id AND pending_status = '1'");
        }

        // insert history barang lama
        foreach ($old_data_barang_to_history_temp as $ob) {
            $pt_asal_h = esc($koneksi, $ob['pt_asal']);
            $po_h      = esc($koneksi, $ob['po']);
            $coa_h     = esc($koneksi, $ob['coa']);
            $kode_h    = esc($koneksi, $ob['kode_assets']);
            $merk_h    = esc($koneksi, $ob['merk']);
            $sn_h      = esc($koneksi, $ob['sn']);
            $user_h    = esc($koneksi, $ob['user']);

            mysqli_query($koneksi, "
                INSERT INTO history_n_temp_barang_mutasi
                (id_ba, pt_asal, po, coa, kode_assets, merk, sn, user, created_at, status, pending_status)
                VALUES ($id, '$pt_asal_h', '$po_h', '$coa_h', '$kode_h', '$merk_h', '$sn_h', '$user_h', NOW(), 0, '$pending_status')
            ");
        }

        // insert barang baru
        if (!$ada_approval) {
            foreach ($barang_data as $b) {
                mysqli_query($koneksi, "
                    INSERT INTO barang_mutasi (id_ba, pt_asal, po, coa, kode_assets, merk, sn, user, created_at)
                    VALUES ($id, '{$b['pt_asal']}', '{$b['po']}', '{$b['coa']}', '{$b['kode']}', '{$b['merk']}', '{$b['sn']}', '{$b['user']}', NOW())
                ");
            }
        } else {
            foreach ($barang_data as $b) {
                mysqli_query($koneksi, "
                    INSERT INTO history_n_temp_barang_mutasi
                    (id_ba, pt_asal, po, coa, kode_assets, merk, sn, user, created_at, status, pending_status)
                    VALUES ($id, '{$b['pt_asal']}', '{$b['po']}', '{$b['coa']}', '{$b['kode']}', '{$b['merk']}', '{$b['sn']}', '{$b['user']}', NOW(), 1, '$pending_status')
                ");
            }
        }

        // ===========================================
        // Catat riwayat edit (historikal_edit_ba)
        // ===========================================
        $new_data = array(
            'lokasi_asal'     => $lokasi_asal,
            'lokasi_tujuan'   => $lokasi_tujuan,
            'pengirim1'       => $nama_pengirim,
            'pengirim2'       => $nama_pengirim2,
            'penerima1'       => $nama_penerima,
            'penerima2'       => $nama_penerima2,
            'hrd_ga_pengirim' => $hrd_ga_pengirim,
            'hrd_ga_penerima' => $hrd_ga_penerima,
            'keterangan'      => $keterangan
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

        // histori barang (merk)
        $barang_lama = array();
        foreach ($old_data_barang_to_history_temp as $ob) {
            if (!empty($ob['merk'])) $barang_lama[] = trim($ob['merk']);
        }

        $barang_baru = array();
        foreach ($barang_data as $b) {
            if (!empty($b['merk'])) $barang_baru[] = trim($b['merk']);
        }

        sort($barang_lama);
        sort($barang_baru);

        $lama_str = implode(', ', $barang_lama);
        $baru_str = implode(', ', $barang_baru);

        if ($lama_str !== $baru_str) {
            $histori_perubahan[] = "Barang : $lama_str diubah ke $baru_str";
        }

        if (count($histori_perubahan) > 0) {
            $histori_text = esc($koneksi, implode(" | ", $histori_perubahan));
            $pengedit = esc($koneksi, $_SESSION['nama']);
            $pt_lama = esc($koneksi, isset($old_data['lokasi_asal']) ? $old_data['lokasi_asal'] : '');

            mysqli_query($koneksi, "
                INSERT INTO historikal_edit_ba (id_ba, nama_ba, pt, histori_edit, pengedit, pending_status, pending_approver, tanggal_edit)
                VALUES ($id, 'mutasi', '$pt_lama', '$histori_text', '$pengedit', '$pending_status', '', NOW())
            ");
        }

        // ===========================
        // MESSAGE
        // ===========================
        if ($ada_approval) {
            $_SESSION['message'] = "Data berita acara dan barang menunggu persetujuan,";
        } else {
            $_SESSION['message'] = "Data berita acara, barang";
        }

        if ($image_success) {
            $_SESSION['message'] .= " dan gambar berhasil diperbarui.";
        } else {
            $_SESSION['message'] .= " berhasil diperbarui. Terdapat masalah pada proses gambar: " . implode(', ', $image_errors);
        }

        header("Location: ba_mutasi.php?status=sukses");
        exit();

    } else {
        $error_message = mysqli_error($koneksi);
        $_SESSION['message'] = "Gagal memperbarui data: " . $error_message;

        if ($image_success) {
            $_SESSION['message'] .= " Data gambar berhasil diperbarui.";
        } else {
            $_SESSION['message'] .= " Terdapat masalah pada proses gambar: " . implode(', ', $image_errors);
        }

        header("Location: ba_mutasi.php?status=gagal");
        exit();
    }
}

// =========================================================================================
// Kalau TIDAK ADA perubahan master/barang, tapi ada aksi gambar
// =========================================================================================
if ($image_success) {
    $_SESSION['message'] = "Perubahan gambar berhasil disimpan.";
    header("Location: ba_mutasi.php?status=sukses");
    exit();
} else {
    $_SESSION['message'] = "Terdapat masalah pada proses gambar: " . implode(', ', $image_errors);
    header("Location: ba_mutasi.php?status=gagal");
    exit();
}
?>