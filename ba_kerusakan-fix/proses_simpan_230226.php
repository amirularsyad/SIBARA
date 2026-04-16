<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../login_registrasi.php");
    exit();
}

if (!isset($_SESSION['hak_akses']) || $_SESSION['hak_akses'] !== 'Admin') {
    header("Location: ../personal/approval.php");
    exit();
}

include '../koneksi.php';

// Ambil data dari form dengan fallback kosong (PHP 5.6 compatible)
$nomor_ba           = isset($_POST['nomor_ba']) ? $_POST['nomor_ba'] : '-';
$tanggal            = isset($_POST['tanggal']) ? $_POST['tanggal'] : '-';
$jenis_perangkat    = isset($_POST['jenis_perangkat']) ? $_POST['jenis_perangkat'] : '-';
$merek              = isset($_POST['merek']) ? $_POST['merek'] : '-';
$no_po              = isset($_POST['nomor_po']) ? $_POST['nomor_po'] : '-';
$user_form          = isset($_POST['user']) ? $_POST['user'] : '-';
$tahun_perolehan    = isset($_POST['tahun_perolehan']) ? $_POST['tahun_perolehan'] : '-';
$deskripsi          = isset($_POST['deskripsi']) ? $_POST['deskripsi'] : '-';
$sn                 = isset($_POST['sn']) ? $_POST['sn'] : '-';
$penyebab_kerusakan = isset($_POST['penyebab_kerusakan']) ? $_POST['penyebab_kerusakan'] : '-';
$rekomendasi_mis    = isset($_POST['rekomendasi_mis']) ? $_POST['rekomendasi_mis'] : '-';
$kategori_kerusakan = isset($_POST['kategori_kerusakan']) && $_POST['kategori_kerusakan'] !== ''
    ? (int)$_POST['kategori_kerusakan']
    : NULL;
$keterangan_dll     = isset($_POST['keterangan_dll']) ? trim($_POST['keterangan_dll']) : '-';

$pt                 = isset($_POST['pt']) ? $_POST['pt'] : '-';
$peminjam           = isset($_POST['peminjam']) ? $_POST['peminjam'] : '-';
$lokasi_input       = isset($_POST['lokasi']) ? $_POST['lokasi'] : '-';
$atasan_peminjam    = isset($_POST['atasan_peminjam']) ? $_POST['atasan_peminjam'] : '-';
$dept_head_HR_or_HRD_SITE = '-';

if ($pt === 'PT.MSAL (HO)'):
    $query              = $koneksi->query("SELECT nama FROM data_karyawan WHERE jabatan = 'Dept. Head' AND departemen = 'HRO' LIMIT 1");

    $data               = $query->fetch_assoc();
    $dept_head_HR_or_HRD_SITE       = $data ? $data['nama'] : '-';

elseif ($pt === 'PT.MSAL (SITE)'):
    $query              = $koneksi->query("SELECT nama FROM data_karyawan_test WHERE posisi = 'Staf GA' AND pt = 'PT.MSAL (SITE)' LIMIT 1");

    $data               = $query->fetch_assoc();
    $dept_head_HR_or_HRD_SITE       = $data ? $data['nama'] : '-';

elseif ($pt !== ''):
    $query              = $koneksi->query("SELECT nama FROM data_karyawan_test WHERE posisi = 'Staf GA' AND pt = $pt LIMIT 1");

    $data               = $query->fetch_assoc();
    $dept_head_HR_or_HRD_SITE       = $data ? $data['nama'] : '-';
endif;

$pt_id = 0;
if ($pt === 'PT.MSAL (HO)'):
    $pt_id = 1;
elseif ($pt === 'PT.MSAL (SITE)'):
    $pt_id = 3;
else:
    $pt_id = 0;
endif;

// Ambil nama pembuat dari session
$nama_pembuat = isset($_SESSION['nama']) ? $_SESSION['nama'] : '-';

// Ambil PT dengan fallback ke nama_pembuat
if (isset($_POST['pt']) && $_POST['pt'] !== '') {
    $pt = $_POST['pt'];
} elseif ($nama_pembuat === 'Rizki Sunandar') {
    $pt = 'PT.MSAL (HO)';
} else {
    $pt = '';
}

// Set nama approver berdasarkan lokasi PT
if ($pt === 'PT.MSAL (HO)') {
    $pembuat = 'Rizki Sunandar';
    $penyetujui = 'Tedy Paronto';
} else {

    $query2 = $koneksi->query("SELECT nama FROM data_karyawan_test WHERE posisi = 'KTU' AND pt = '$pt' LIMIT 1");
    $data2               = $query2->fetch_assoc();
    $KTU       = $data2 ? $data2['nama'] : '-';

    $query2 = $koneksi->query("SELECT nama FROM data_karyawan_test WHERE posisi = 'GM' AND pt = '$pt' LIMIT 1");
    $data2               = $query2->fetch_assoc();
    $GM       = $data2 ? $data2['nama'] : '-';

    $pembuat = $GM;
    if ($pt === 'PT.MSAL (SITE)') {
        $penyetujui = $KTU;
    }
    elseif ($pt !== ''){
        $penyetujui = $KTU;
    } 
    else {
        $penyetujui = '-';
    }
}

// Set nilai approval
$approval_1 = 0;
$approval_2 = 0;
$approval_3 = 0;
$approval_4 = 0;
$approval_5 = 0;

if ($pt === 'PT.MSAL (HO)') {
    // jika dept head HR sebagai peminjam atau atasan_peminjam, kosongkan
    if ($dept_head_HR_or_HRD_SITE === $peminjam || $dept_head_HR_or_HRD_SITE === $atasan_peminjam) {
        $dept_head_HR_or_HRD_SITE = '-';
    }

    // Logika tambahan aktor & approval
    if ($peminjam === 'Rizki Sunandar') {
        $pembuat = "-";      // override
    }

    if ($peminjam === 'Tedy Paronto') {
        $penyetujui = "-";      // override
        $atasan_peminjam = "-"; // override
    }

    if ($atasan_peminjam === 'Tedy Paronto') {
        $penyetujui = "-";      // override
    }

    // if ($atasan_peminjam === '-') {
    //     $approval_4 = 0;        // auto approve
    // }
}
// elseif ($pt === 'PT.MSAL (SITE)') {

// }

if ($lokasi_input !== '-' || $lokasi_input !== '') {
    // Format lokasi
    if (preg_match('/^LT\.(\d+)/i', $lokasi_input, $match)) {
        $lokasi = 'Lantai ' . $match[1];
    } else {
        $lokasi = $lokasi_input;
    }
}

// Simpan ke database
$sql = "INSERT INTO berita_acara_kerusakan 
(nomor_ba, tanggal, jenis_perangkat, no_po, merek, pt, id_pt, lokasi, user, peminjam, deskripsi, sn, tahun_perolehan, penyebab_kerusakan, rekomendasi_mis, kategori_kerusakan_id, keterangan_dll, atasan_peminjam, nama_pembuat, pembuat, penyetujui, diketahui, approval_1, approval_2, approval_3, approval_4, approval_5) 
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $koneksi->prepare($sql);

if ($pt === 'PT.MSAL (HO)'){
$stmt->bind_param(
    "ssssssissssssssissssssiiiii",
    $nomor_ba,
    $tanggal,
    $jenis_perangkat,
    $no_po,
    $merek,
    $pt,
    $pt_id,
    $lokasi,
    $user_form,
    $peminjam,
    $deskripsi,
    $sn,
    $tahun_perolehan,
    $penyebab_kerusakan,
    $rekomendasi_mis,
    $kategori_kerusakan,
    $keterangan_dll,
    $atasan_peminjam,
    $nama_pembuat,
    $pembuat,
    $penyetujui,
    $dept_head_HR_or_HRD_SITE,
    $approval_1,
    $approval_2,
    $approval_3,
    $approval_4,
    $approval_5
);
}
// elseif ($pt === 'PT.MSAL (SITE)' || $pt !== 'PT.MSAL (HO)'){
elseif ($pt !== 'PT.MSAL (HO)'){
$stmt->bind_param(
    "ssssssissssssssissssssiiiii",
    $nomor_ba,
    $tanggal,
    $jenis_perangkat,
    $no_po,
    $merek,
    $pt,
    $pt_id,
    $lokasi,
    $user_form,
    $peminjam,
    $deskripsi,
    $sn,
    $tahun_perolehan,
    $penyebab_kerusakan,
    $rekomendasi_mis,
    $kategori_kerusakan,
    $keterangan_dll,
    $atasan_peminjam,
    $nama_pembuat,
    $pembuat,
    $penyetujui,
    $dept_head_HR_or_HRD_SITE,
    $approval_1,
    $approval_2,
    $approval_3,
    $approval_4,
    $approval_5
);
}

if ($stmt->execute()) {
    $ba_kerusakan_id = $stmt->insert_id;

    // Proses Upload Gambar
    $upload_dir = '../assets/database-gambar/';
    if (!empty($_FILES['gambar']['name'][0])) {
        foreach ($_FILES['gambar']['tmp_name'] as $key => $tmp_name) {
            if (!empty($tmp_name)) {
                $filename = basename($_FILES['gambar']['name'][$key]);
                $target_path = $upload_dir . time() . '_' . $filename;

                if (move_uploaded_file($tmp_name, $target_path)) {
                    $stmt_img = $koneksi->prepare("INSERT INTO gambar_ba_kerusakan (ba_kerusakan_id, file_path) VALUES (?, ?)");
                    $stmt_img->bind_param("is", $ba_kerusakan_id, $target_path);
                    $stmt_img->execute();
                    $stmt_img->close();
                }
            }
        }
    }

    $stmt->close();
    $koneksi->close();

    $_SESSION['message'] = "Data berhasil disimpan ke database.";
    header("Location: ba_kerusakan.php?status=sukses");
    exit();
} else {
    $_SESSION['message'] = "Gagal menyimpan data.";
    header("Location: ba_kerusakan.php?status=gagal");
    exit();
}
