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

// Pastikan ID berita acara ada
if (!isset($_POST['id']) || empty($_POST['id'])) {
    $_SESSION['message'] = "ID tidak ditemukan. Gagal memperbarui data.";
    header("Location: ba_mutasi.php?status=gagal");
    exit();
}

$id             = $_POST['id'];
$tanggal        = isset($_POST['tanggal']) ? $_POST['tanggal'] : null;
$nomor_ba       = isset($_POST['nomor_ba']) ? $_POST['nomor_ba'] : null;
$lokasi_asal    = isset($_POST['lokasi_asal']) ? $_POST['lokasi_asal'] : null;
$lokasi_tujuan  = isset($_POST['lokasi_tujuan']) ? $_POST['lokasi_tujuan'] : null;
$nama_pengirim  = isset($_POST['nama_pengirim']) ? $_POST['nama_pengirim'] : null;
$nama_pengirim2 = isset($_POST['nama_pengirim2']) ? $_POST['nama_pengirim2'] : null;
$nama_penerima  = isset($_POST['nama_penerima']) ? $_POST['nama_penerima'] : null;
$nama_penerima2 = isset($_POST['nama_penerima2']) ? $_POST['nama_penerima2'] : null;
$keterangan     = isset($_POST['keterangan']) ? $_POST['keterangan'] : null;
$pembuat        = $_SESSION['nama'];

// cek data kosong
$kosong = array();

if (empty($tanggal))        $kosong[] = 'Tanggal';
if (empty($nomor_ba))       $kosong[] = 'Nomor BA';
if (empty($lokasi_asal))    $kosong[] = 'Lokasi Asal';
if (empty($lokasi_tujuan))  $kosong[] = 'Lokasi Tujuan';
if (empty($nama_pengirim))  $kosong[] = 'Nama Pengirim';
if (empty($nama_pengirim2)) $kosong[] = 'Nama Pengirim 2';
if (empty($nama_penerima))  $kosong[] = 'Nama Penerima';
if (empty($nama_penerima2)) $kosong[] = 'Nama Penerima 2';
if (empty($keterangan))     $kosong[] = 'Keterangan';

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

// ===========================================
// 🔹 Ambil data lama sebelum update (untuk histori)
// ===========================================
$query_old = mysqli_query($koneksi, "
    SELECT pt_asal AS lokasi_asal, 
           pt_tujuan AS lokasi_tujuan, 
           pengirim1, 
           pengirim2, 
           penerima1, 
           penerima2, 
           keterangan 
    FROM berita_acara_mutasi 
    WHERE id = '$id'
");
$old_data = mysqli_fetch_assoc($query_old);

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
        keterangan = '$keterangan',

        -- 🔹 Reset approval & tanda tangan
        approval_1 = 0, approval_2 = 0, approval_3 = 0, approval_4 = 0,
        approval_5 = 0, approval_6 = 0, approval_7 = 0, approval_8 = 0,
        approval_9 = 0, approval_10 = 0, approval_11 = 0,

        autograph_1 = NULL, autograph_2 = NULL, autograph_3 = NULL, autograph_4 = NULL,
        autograph_5 = NULL, autograph_6 = NULL, autograph_7 = NULL, autograph_8 = NULL,
        autograph_9 = NULL, autograph_10 = NULL, autograph_11 = NULL,

        tanggal_approve_1 = NULL, tanggal_approve_2 = NULL, tanggal_approve_3 = NULL, tanggal_approve_4 = NULL,
        tanggal_approve_5 = NULL, tanggal_approve_6 = NULL, tanggal_approve_7 = NULL, tanggal_approve_8 = NULL,
        tanggal_approve_9 = NULL, tanggal_approve_10 = NULL, tanggal_approve_11 = NULL

    WHERE id = '$id'
";


if (mysqli_query($koneksi, $query_update)) {

// 🔹 Hapus dulu semua data lama
mysqli_query($koneksi, "DELETE FROM barang_mutasi WHERE id_ba = '$id'");

// 🔹 Ambil array dari form
$pt_asal_list = isset($_POST['pt_asal']) ? $_POST['pt_asal'] : [];
$po_list      = isset($_POST['po']) ? $_POST['po'] : [];
$coa_list     = isset($_POST['coa']) ? $_POST['coa'] : [];
$kode_list    = isset($_POST['kode']) ? $_POST['kode'] : [];
$merk_list    = isset($_POST['merk']) ? $_POST['merk'] : [];
$sn_list      = isset($_POST['sn']) ? $_POST['sn'] : [];
$user_list    = isset($_POST['user']) ? $_POST['user'] : [];

// 🔹 Gabungkan semua ke array asosiatif
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

// 🔹 Insert ulang data unik ke DB
foreach ($barang_data as $b) {
    $query_barang = "
        INSERT INTO barang_mutasi (id_ba, pt_asal, po, coa, kode_assets, merk, sn, user, created_at)
        VALUES ('$id', '{$b['pt_asal']}', '{$b['po']}', '{$b['coa']}', '{$b['kode']}', '{$b['merk']}', '{$b['sn']}', '{$b['user']}', NOW())
    ";
    mysqli_query($koneksi, $query_barang);
}

    // =======================
    // 🔹 Update Gambar Mutasi (FIX VERSION)
    // =======================
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

                mysqli_query($koneksi, "
                    DELETE FROM gambar_ba_mutasi 
                    WHERE id = '$hapus_id' AND id_ba = '$id'
                ");
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
                    mysqli_query($koneksi, "
                        UPDATE gambar_ba_mutasi 
                        SET file_path = '$target', uploaded_at = NOW()
                        WHERE id = '$id_gambar' AND id_ba = '$id'
                    ");
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
                    mysqli_query($koneksi, "
                        INSERT INTO gambar_ba_mutasi (id_ba, file_path, uploaded_at)
                        VALUES ('$id', '$target', NOW())
                    ");
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
                    file_put_contents($filename, $decoded);

                    mysqli_query($koneksi, "
                        INSERT INTO gambar_ba_mutasi (id_ba, file_path, uploaded_at)
                        VALUES ('$id', '$filename', NOW())
                    ");
                }
            }
        }
    }

// ===========================================
// 🔹 Catat Riwayat Edit ke tabel historikal_edit_ba
// ===========================================
$new_data = array(
    'lokasi_asal'    => $lokasi_asal,
    'lokasi_tujuan'  => $lokasi_tujuan,
    'pengirim1'      => $nama_pengirim,
    'pengirim2'      => $nama_pengirim2,
    'penerima1'      => $nama_penerima,
    'penerima2'      => $nama_penerima2,
    'keterangan'     => $keterangan
);

$histori_perubahan = array();

foreach ($new_data as $key => $baru) {
    $lama = isset($old_data[$key]) ? trim($old_data[$key]) : '';
    if ($lama != $baru) {
        $label = str_replace(
            array('lokasi_asal', 'lokasi_tujuan', 'pengirim1', 'pengirim2', 'penerima1', 'penerima2', 'keterangan'),
            array('PT Asal', 'PT Tujuan', 'Nama Pengirim 1', 'Nama Pengirim 2', 'Nama Penerima 1', 'Nama Penerima 2', 'Keterangan'),
            $key
        );
        $histori_perubahan[] = "$label : $lama diubah ke $baru";
    }
}

if (count($histori_perubahan) > 0) {
    $histori_text = implode(" | ", $histori_perubahan);
    $pengedit = mysqli_real_escape_string($koneksi, $_SESSION['nama']);
    $histori_text = mysqli_real_escape_string($koneksi, $histori_text);

    mysqli_query($koneksi, "
        INSERT INTO historikal_edit_ba (id_ba, nama_ba, histori_edit, pengedit, tanggal_edit)
        VALUES ('$id', 'mutasi', '$histori_text', '$pengedit', NOW())
    ");
}



    $_SESSION['message'] = "Data berita acara dan barang berhasil diperbarui.";
    header("Location: ba_mutasi.php?status=sukses");
    exit();

} else {
    $_SESSION['message'] = "Gagal memperbarui data: " . mysqli_error($koneksi);
    header("Location: ba_mutasi.php?status=gagal");
    exit();
}
?>
