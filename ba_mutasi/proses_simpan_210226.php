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

include '../koneksi.php'; // koneksi pakai mysqli

// Ambil data dari form
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

// Cek data kosong
$kosong = array();

if (empty($tanggal))        $kosong[] = 'Tanggal';
if (empty($nomor_ba))       $kosong[] = 'Nomor BA';
if (empty($lokasi_asal))    $kosong[] = 'Lokasi Asal';
if (empty($lokasi_tujuan))  $kosong[] = 'Lokasi Tujuan';
if (empty($nama_pengirim))  $kosong[] = 'Nama Pengirim';
if (empty($nama_pengirim2))  $kosong[] = 'Nama Pengirim 2';
if (empty($nama_penerima))  $kosong[] = 'Nama Penerima';
if (empty($nama_penerima2)) $kosong[] = 'Nama Penerima 2';

if (!empty($kosong)) {
    $_SESSION['message'] = "Data Kosong: " . implode(', ', $kosong);
    header("Location: ba_mutasi.php?status=gagal");
    exit();
}

// Default nilai
$diketahui          = "";
$hrd_ga_pengirim    = "";
$hrd_ga_penerima    = "";
$pemeriksa1         = "";
$pemeriksa2         = "";
$penyetujui1        = "";
$penyetujui2        = "";
$ptNorm = "REPLACE(REPLACE(TRIM(pt), ', ', ','), ' ,', ',')";

// Ambil Dept HRD GA
$query_head_hrd = mysqli_query($koneksi, "SELECT nama FROM data_karyawan WHERE jabatan = 'Dept. Head' AND departemen = 'HRO' LIMIT 1");
if ($query_head_hrd && mysqli_num_rows($query_head_hrd) > 0) {
    $row_head_hrd = mysqli_fetch_assoc($query_head_hrd);
    $pemeriksa1 = $row_head_hrd['nama'];
}

// Ambil Div Accounting
$query_fa = mysqli_query($koneksi, "SELECT nama FROM data_karyawan WHERE jabatan = 'Dept. Head' AND departemen = 'ACCOUNTING' LIMIT 1");
if ($query_fa && mysqli_num_rows($query_fa) > 0) {
    $row_fa = mysqli_fetch_assoc($query_fa);
    $pemeriksa2 = $row_fa['nama'];
}

//Ambil Direktur HRD GA
$query_dir_hrd = mysqli_query($koneksi, "SELECT nama FROM data_karyawan WHERE jabatan = 'Direktur' AND departemen = 'HRD' LIMIT 1");
if ($query_dir_hrd && mysqli_num_rows($query_dir_hrd) > 0) {
    $row_dir_hrd = mysqli_fetch_assoc($query_dir_hrd);
    $penyetujui1 = $row_dir_hrd['nama'];
}

//Ambil Direktur FA
$query_dir_fa = mysqli_query($koneksi, "SELECT nama FROM data_karyawan WHERE posisi = 'Direktur Finance' LIMIT 1");
if ($query_dir_fa && mysqli_num_rows($query_dir_fa) > 0) {
    $row_dir_fa = mysqli_fetch_assoc($query_dir_fa);
    $penyetujui2 = $row_dir_fa['nama'];
}

// Ambil Dept.Head MIS
$query_mis = mysqli_query($koneksi, "SELECT nama FROM data_karyawan WHERE jabatan = 'Dept. Head' AND departemen = 'MIS' LIMIT 1");
if ($query_mis && mysqli_num_rows($query_mis) > 0) {
    $row_mis = mysqli_fetch_assoc($query_mis);
    $diketahui = $row_mis['nama'];
}

// Jika PT Asal = PT.MSAL (HO), ambil data dari tabel data_karyawan
if ($lokasi_asal === "PT.MSAL (HO)") {
    // Ambil HRD GA Yang menyerahkan aset
    $query_hrd_ga_pengirim = mysqli_query($koneksi, "SELECT nama FROM data_karyawan WHERE posisi = 'Staf GA' AND departemen = 'HRD' LIMIT 1");
    if ($query_hrd_ga_pengirim && mysqli_num_rows($query_hrd_ga_pengirim) > 0) {
        $row_hrd_ga_pengirim = mysqli_fetch_assoc($query_hrd_ga_pengirim);
        $hrd_ga_pengirim = $row_hrd_ga_pengirim['nama'];
    }
} else{
    // Ambil HRD GA Yang menyerahkan aset
    $query_hrd_ga_pengirim = mysqli_query($koneksi, "SELECT nama FROM data_karyawan_test WHERE posisi = 'Staf GA' AND FIND_IN_SET('$lokasi_asal', $ptNorm) > 0 LIMIT 1");
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
}else{
    // Ambil HRD GA Yang menerima
    $query_hrd_ga_penerima = mysqli_query($koneksi, "SELECT nama FROM data_karyawan_test WHERE posisi = 'Staf GA'AND FIND_IN_SET('$lokasi_tujuan', $ptNorm) > 0 LIMIT 1");
    if ($query_hrd_ga_penerima && mysqli_num_rows($query_hrd_ga_penerima) > 0) {
        $row_hrd_ga_penerima = mysqli_fetch_assoc($query_hrd_ga_penerima);
        $hrd_ga_penerima = $row_hrd_ga_penerima['nama'];
    }
}
var_dump($hrd_ga_pengirim);
var_dump($nama_penerima);
var_dump($nama_penerima2);
var_dump($hrd_ga_penerima);
exit;

// Simpan ke tabel berita_acara_mutasi
$query_ba = "
    INSERT INTO berita_acara_mutasi
    (tanggal, nomor_ba, pembuat, pt_asal, pt_tujuan,
    pengirim1, pengirim2, hrd_ga_pengirim, 
    penerima1, penerima2, hrd_ga_penerima, 
    diketahui, pemeriksa1, pemeriksa2, penyetujui1, penyetujui2, 
    keterangan, dihapus, created_at)
    VALUES (
        '$tanggal',
        '$nomor_ba',
        '$pembuat',
        '$lokasi_asal',
        '$lokasi_tujuan',
        '$nama_pengirim',
        '$nama_pengirim2',
        '$hrd_ga_pengirim',
        '$nama_penerima',
        '$nama_penerima2',
        '$hrd_ga_penerima',
        '$diketahui',
        '$pemeriksa1',
        '$pemeriksa2',
        '$penyetujui1',
        '$penyetujui2',
        '$keterangan',
        0,
        NOW()
    )
";

if (mysqli_query($koneksi, $query_ba)) {
    // Ambil ID berita acara yang baru disimpan
    $id_ba = mysqli_insert_id($koneksi);

    // Siapkan data barang
    $pt_asal_list = isset($_POST['pt_asal'])    ? $_POST['pt_asal']     : [];
    $po_list   = isset($_POST['po'])            ? $_POST['po']          : [];
    $coa_list  = isset($_POST['coa'])           ? $_POST['coa']         : [];
    $kode_list = isset($_POST['kode'])          ? $_POST['kode']        : [];
    $merk_list = isset($_POST['merk'])          ? $_POST['merk']        : [];
    $sn_list   = isset($_POST['sn'])            ? $_POST['sn']          : [];
    $user_list = isset($_POST['user'])          ? $_POST['user']        : [];

    // Pastikan semua array punya panjang sama
    $total = max(count($pt_asal_list), count($po_list), count($coa_list), count($merk_list), count($sn_list), count($user_list));

    if ($total > 0) {
        for ($i = 0; $i < $total; $i++) {
            $pt_asal    = !empty($pt_asal_list[$i]) ? mysqli_real_escape_string($koneksi, $pt_asal_list[$i]): null;
            $po         = !empty($po_list[$i])      ? mysqli_real_escape_string($koneksi, $po_list[$i])     : null;
            $coa        = !empty($coa_list[$i])     ? mysqli_real_escape_string($koneksi, $coa_list[$i])    : null;
            $kode       = !empty($kode_list[$i])    ? mysqli_real_escape_string($koneksi, $kode_list[$i])   : null;
            $merk       = !empty($merk_list[$i])    ? mysqli_real_escape_string($koneksi, $merk_list[$i])   : null;
            $sn         = !empty($sn_list[$i])      ? mysqli_real_escape_string($koneksi, $sn_list[$i])     : null;
            $user       = !empty($user_list[$i])    ? mysqli_real_escape_string($koneksi, $user_list[$i])   : null;

            $query_barang = "
                INSERT INTO barang_mutasi (id_ba, pt_asal, po, coa, kode_assets, merk, sn, user, created_at)
                VALUES ('$id_ba', '$pt_asal', '$po', '$coa', '$kode', '$merk', '$sn', '$user', NOW())
            ";
            mysqli_query($koneksi, $query_barang);
        }
    }

    // === PROSES UPLOAD GAMBAR MUTASI ===
    $upload_dir = '../assets/database-gambar/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    if (!empty($_FILES['gambar']['name'][0])) {
        foreach ($_FILES['gambar']['tmp_name'] as $key => $tmp_name) {
            if (!empty($tmp_name)) {
                $filename = basename($_FILES['gambar']['name'][$key]);
                $safe_filename = preg_replace("/[^a-zA-Z0-9\._-]/", "_", $filename);
                $target_path = $upload_dir . time() . '_' . $safe_filename;

                if (move_uploaded_file($tmp_name, $target_path)) {
                    $stmt_img = $koneksi->prepare("
                        INSERT INTO gambar_ba_mutasi (id_ba, file_path, uploaded_at)
                        VALUES (?, ?, NOW())
                    ");
                    $stmt_img->bind_param("is", $id_ba, $target_path);
                    $stmt_img->execute();
                    $stmt_img->close();
                }
            }
        }
    }

    $_SESSION['message'] = "Data berita acara berhasil dibuat.";
    header("Location: ba_mutasi.php?status=sukses");
    exit();

} else {
    $_SESSION['message'] = "Gagal menyimpan data berita acara: " . mysqli_error($koneksi);
    header("Location: ba_mutasi.php?status=gagal");
    exit();
}
?>
