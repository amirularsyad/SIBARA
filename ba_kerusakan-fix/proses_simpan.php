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

function getNamaKaryawanTest($koneksi, $posisi, $pt) {
    $nama = '-';

    $posisi = trim((string)$posisi);
    $pt     = trim((string)$pt);
    if ($pt === '' || $pt === '-') return $nama;

    // Normalisasi: "; " -> "," ; ";" -> "," ; ", " -> ","
    // Jadi list PT bisa dicari pakai FIND_IN_SET()
    $sql = "SELECT nama
            FROM data_karyawan_test
            WHERE posisi = ?
              AND (
                    TRIM(pt) = ? 
                    OR FIND_IN_SET(?, REPLACE(REPLACE(REPLACE(pt,'; ',','), ';', ','), ', ', ',')) > 0
                  )
            LIMIT 1";

    $stmt = $koneksi->prepare($sql);
    if (!$stmt) {
        error_log("Prepare gagal (getNamaKaryawanTest): " . $koneksi->error);
        return $nama;
    }

    // pt dipakai 2x: untuk TRIM(pt)=? dan untuk FIND_IN_SET(?, ...)
    $stmt->bind_param("sss", $posisi, $pt, $pt);

    if (!$stmt->execute()) {
        error_log("Execute gagal (getNamaKaryawanTest): " . $stmt->error);
        $stmt->close();
        return $nama;
    }

    $namaDb = '';
    $stmt->bind_result($namaDb);

    if ($stmt->fetch()) {
        $namaDb = trim((string)$namaDb);
        if ($namaDb !== '') $nama = $namaDb;
    }

    $stmt->close();
    return $nama;
}

function getJabatanAktor($koneksi, $nama, $pt) {
    $jabatan = '-';

    $nama = trim((string)$nama);
    $pt   = trim((string)$pt);

    if ($nama === '' || $nama === '-') return $jabatan;

    // HO -> ambil dari data_karyawan (gabung jabatan + departemen)
    if ($pt === 'PT.MSAL (HO)') {
        $sql = "SELECT jabatan, departemen
                FROM data_karyawan
                WHERE nama = ?
                LIMIT 1";

        $stmt = $koneksi->prepare($sql);
        if (!$stmt) {
            error_log("Prepare gagal (getJabatanAktor HO): " . $koneksi->error);
            return $jabatan;
        }

        $stmt->bind_param("s", $nama);

        if (!$stmt->execute()) {
            error_log("Execute gagal (getJabatanAktor HO): " . $stmt->error);
            $stmt->close();
            return $jabatan;
        }

        $jab = '';
        $dep = '';
        $stmt->bind_result($jab, $dep);

        if ($stmt->fetch()) {
            $jab = trim((string)$jab);
            $dep = trim((string)$dep);

            if ($jab !== '' && $dep !== '') {
                $jabatan = $jab . ' ' . $dep; // gabungan jabatan + departemen
            } elseif ($jab !== '') {
                $jabatan = $jab;
            } elseif ($dep !== '') {
                $jabatan = $dep;
            }
        }

        $stmt->close();
        return $jabatan;
    }

    // Non HO -> ambil dari data_karyawan_test.posisi, disesuaikan nama + PT
    $sql = "SELECT posisi
            FROM data_karyawan_test
            WHERE nama = ?
              AND (
                    TRIM(pt) = ?
                    OR FIND_IN_SET(?, REPLACE(REPLACE(REPLACE(pt,'; ',','), ';', ','), ', ', ',')) > 0
                  )
            LIMIT 1";

    $stmt = $koneksi->prepare($sql);
    if (!$stmt) {
        error_log("Prepare gagal (getJabatanAktor non-HO): " . $koneksi->error);
        return $jabatan;
    }

    $stmt->bind_param("sss", $nama, $pt, $pt);

    if (!$stmt->execute()) {
        error_log("Execute gagal (getJabatanAktor non-HO): " . $stmt->error);
        $stmt->close();
        return $jabatan;
    }

    $posisi = '';
    $stmt->bind_result($posisi);

    if ($stmt->fetch()) {
        $posisi = trim((string)$posisi);
        if ($posisi !== '') $jabatan = $posisi;
    }

    $stmt->close();
    return $jabatan;
}

// Mapping PT -> id_pt (sesuai skema)
$pt_map = array(
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

$pt              = isset($_POST['pt']) ? $_POST['pt'] : '-';
$peminjam        = isset($_POST['peminjam']) ? $_POST['peminjam'] : '-';
$lokasi_input    = isset($_POST['lokasi']) ? $_POST['lokasi'] : '-';
$atasan_peminjam = isset($_POST['atasan_peminjam']) ? $_POST['atasan_peminjam'] : '-';

// Ambil nama pembuat dari session
$nama_pembuat = isset($_SESSION['nama']) ? $_SESSION['nama'] : '-';

// Ambil PT dengan fallback ke nama_pembuat (tetap seperti sebelumnya)
if (isset($_POST['pt']) && $_POST['pt'] !== '') {
    $pt = $_POST['pt'];
} elseif ($nama_pembuat === 'Rizki Sunandar') {
    $pt = 'PT.MSAL (HO)';
} else {
    $pt = '';
}

$pt = trim((string)$pt);

// id_pt untuk SEMUA PT (sesuai mapping)
$pt_id = isset($pt_map[$pt]) ? (int)$pt_map[$pt] : 0;

// diketahui:
// - HO: Dept. Head HRO (data_karyawan)
// - selain HO: ikut aturan SITE (Staf GA di data_karyawan_test per PT)
$dept_head_HR_or_HRD_SITE = '-';
if ($pt === 'PT.MSAL (HO)') {
    $q = $koneksi->query("SELECT nama FROM data_karyawan WHERE jabatan = 'Dept. Head' AND departemen = 'HRO' LIMIT 1");
    if ($q) {
        $row = $q->fetch_assoc();
        $dept_head_HR_or_HRD_SITE = $row ? $row['nama'] : '-';
    }
} elseif ($pt !== '') {
    $dept_head_HR_or_HRD_SITE = getNamaKaryawanTest($koneksi, 'Staf GA', $pt);
}

// Set nama approver berdasarkan lokasi PT
if ($pt === 'PT.MSAL (HO)') {
    $pembuat = 'Rizki Sunandar';
    $penyetujui = 'Tedy Paronto';
} else {
    // semua PT non-HO ikut pola SITE (ambil dari data_karyawan_test per PT)
    $KTU = getNamaKaryawanTest($koneksi, 'KTU', $pt);
    $GM  = getNamaKaryawanTest($koneksi, 'GM', $pt);

    $pembuat   = $GM;
    $penyetujui = ($pt !== '') ? $KTU : '-';
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
}

// Ambil jabatan setiap aktor (berdasarkan PT)
$jabatan_peminjam         = getJabatanAktor($koneksi, $peminjam, $pt);
$jabatan_atasan_peminjam  = getJabatanAktor($koneksi, $atasan_peminjam, $pt);
$jabatan_pembuat          = getJabatanAktor($koneksi, $pembuat, $pt);
$jabatan_penyetujui       = getJabatanAktor($koneksi, $penyetujui, $pt);
$jabatan_diketahui        = getJabatanAktor($koneksi, $dept_head_HR_or_HRD_SITE, $pt);

// Format lokasi (tetap seperti sebelumnya)
$lokasi = $lokasi_input;
if ($lokasi_input !== '-' || $lokasi_input !== '') {
    if (preg_match('/^LT\.(\d+)/i', $lokasi_input, $match)) {
        $lokasi = 'Lantai ' . $match[1];
    } else {
        $lokasi = $lokasi_input;
    }
}

// Simpan ke database
$sql = "INSERT INTO berita_acara_kerusakan 
(nomor_ba, tanggal, jenis_perangkat, no_po, merek, pt, id_pt, lokasi, user, peminjam, jabatan_peminjam, deskripsi, sn, tahun_perolehan, penyebab_kerusakan, rekomendasi_mis, kategori_kerusakan_id, keterangan_dll, atasan_peminjam, jabatan_atasan_peminjam, nama_pembuat, pembuat, jabatan_pembuat, penyetujui, jabatan_penyetujui, diketahui, jabatan_diketahui, approval_1, approval_2, approval_3, approval_4, approval_5) 
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $koneksi->prepare($sql);

$stmt->bind_param(
    "ssssssisssssssssissssssssssiiiii",
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
    $jabatan_peminjam,
    $deskripsi,
    $sn,
    $tahun_perolehan,
    $penyebab_kerusakan,
    $rekomendasi_mis,
    $kategori_kerusakan,
    $keterangan_dll,
    $atasan_peminjam,
    $jabatan_atasan_peminjam,
    $nama_pembuat,
    $pembuat,
    $jabatan_pembuat,
    $penyetujui,
    $jabatan_penyetujui,
    $dept_head_HR_or_HRD_SITE,
    $jabatan_diketahui,
    $approval_1,
    $approval_2,
    $approval_3,
    $approval_4,
    $approval_5
);

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