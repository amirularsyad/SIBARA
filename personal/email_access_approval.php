<?php
session_start();
require_once '../koneksi.php';

$jenis = isset($_GET['jenis']) ? $_GET['jenis'] : '';
$id_surat = isset($_GET['id']) ? $_GET['id'] : '';

if (empty($jenis) || empty($id_surat)) {
    die("Parameter tidak lengkap.");
}


$approval_col = '';
$autograph_col = '';
$tanggal_col = '';
$approval_val = 0;
$autograph_val = '';
$nama_selanjutnya = '-';
$autograph_base64 = '';
$status_user = '';

$row_id = '';
$aktorListStr = '';
$nomor_ba = '';
$tanggal = '';
$bulanRomawi = '';
$tahun = '';
$jenis_ba = '';
$permintaan = '';
$namaPeminta = '';

$jenis_ba2 = "";

if ($jenis === "notebook") {
    $jenis_ba2 = "Serah terima Notebook Inventaris";
} elseif ($jenis === "kerusakan") {
    $jenis_ba2 = "Kerusakan";
} elseif ($jenis === "mutasi") {
    $jenis_ba2 = "Mutasi Aset Inventaris";
} elseif ($jenis === "st_asset") {
    $jenis_ba2 = "Serah Terima Asset Inventaris";
} elseif ($jenis === "pemutihan") {
    $jenis_ba2 = "Pemutihan Aset";
} elseif ($jenis === "pengembalian") {
    $jenis_ba2 = "Pengembalian Asset Inventaris";
} else {
    $jenis_ba2 = $jenis;
}

if ($jenis === 'kerusakan') {
    $nama_user = isset($_SESSION['nama']) ? $_SESSION['nama'] : '';
    $autograph_base64 = '';
    $status_user = '';

    if (!empty($nama_user)) {
        $stmt = $koneksi->prepare("SELECT autograph FROM akun_akses WHERE nama = ? LIMIT 1");
        $stmt->bind_param("s", $nama_user);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        if ($row && !empty($row['autograph'])) {
            $autograph_base64 = 'data:image/png;base64,' . base64_encode($row['autograph']);
        }

        $stmt->close();
    }

    if (!empty($nama_user)) {
        $stmt = $koneksi->prepare("
            SELECT nomor_ba, tanggal, pembuat, penyetujui, peminjam, atasan_peminjam, diketahui,
                   approval_1, approval_2, approval_3, approval_4, approval_5,
                   autograph_1, autograph_2, autograph_3, autograph_4, autograph_5,
                   tanggal_approve_1, tanggal_approve_2, tanggal_approve_3, tanggal_approve_4, tanggal_approve_5
            FROM berita_acara_kerusakan
            WHERE id = ?
        ");
        $stmt->bind_param("i", $id_surat);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();

        if ($data) {
            $nama_selanjutnya = '';

            if ($nama_user === $data['pembuat']) {
                $approval_col = 'approval_1';
                $autograph_col = 'autograph_1';
                $tanggal_col = 'tanggal_approve_1';
                $approval_val = $data['approval_1'];
                $autograph_val = $data['autograph_1'];
                foreach (['peminjam', 'atasan_peminjam', 'diketahui', 'penyetujui'] as $next) {
                    if (!empty($data[$next]) && $data[$next] !== '-') {
                        $nama_selanjutnya = $data[$next];
                        break;
                    }
                }
                if ($nama_selanjutnya === '') {
                    $nama_selanjutnya = '-';
                }
            } elseif ($nama_user === $data['penyetujui']) {
                $approval_col = 'approval_2';
                $autograph_col = 'autograph_2';
                $tanggal_col = 'tanggal_approve_2';
                $approval_val = $data['approval_2'];
                $autograph_val = $data['autograph_2'];
                $nama_selanjutnya = '-';
            } elseif ($nama_user === $data['peminjam']) {
                $approval_col = 'approval_3';
                $autograph_col = 'autograph_3';
                $tanggal_col = 'tanggal_approve_3';
                $approval_val = $data['approval_3'];
                $autograph_val = $data['autograph_3'];
                foreach (['atasan_peminjam', 'diketahui', 'penyetujui'] as $next) {
                    if (!empty($data[$next]) && $data[$next] !== '-') {
                        $nama_selanjutnya = $data[$next];
                        break;
                    }
                }
                if ($nama_selanjutnya === '') {
                    $nama_selanjutnya = '-';
                }
            } elseif ($nama_user === $data['atasan_peminjam']) {
                $approval_col = 'approval_4';
                $autograph_col = 'autograph_4';
                $tanggal_col = 'tanggal_approve_4';
                $approval_val = $data['approval_4'];
                $autograph_val = $data['autograph_4'];
                foreach (['diketahui', 'penyetujui'] as $next) {
                    if (!empty($data[$next]) && $data[$next] !== '-') {
                        $nama_selanjutnya = $data[$next];
                        break;
                    }
                }
                if ($nama_selanjutnya === '') {
                    $nama_selanjutnya = '-';
                }
            } elseif ($nama_user === $data['diketahui']) {
                $approval_col = 'approval_5';
                $autograph_col = 'autograph_5';
                $tanggal_col = 'tanggal_approve_5';
                $approval_val = $data['approval_5'];
                $autograph_val = $data['autograph_5'];
                foreach (['penyetujui'] as $next) {
                    if (!empty($data[$next]) && $data[$next] !== '-') {
                        $nama_selanjutnya = $data[$next];
                        break;
                    }
                }
                if ($nama_selanjutnya === '') {
                    $nama_selanjutnya = '-';
                }
            }

            if (!empty($approval_val) && empty($autograph_val)) {
                $status_user = 'approved_only';
            } elseif (!empty($approval_val) && !empty($autograph_val)) {
                $status_user = 'signed';
            }

            // ===============================
            // AMBIL DATA
            // ===============================
            $bulanRomawiArr = [
                1 => 'I',
                2 => 'II',
                3 => 'III',
                4 => 'IV',
                5 => 'V',
                6 => 'VI',
                7 => 'VII',
                8 => 'VIII',
                9 => 'IX',
                10 => 'X',
                11 => 'XI',
                12 => 'XII'
            ];

            $tanggalFix       = $data['tanggal'];
            $bulanRomawiFix   = '';
            $tahunFix         = '';

            if (!empty($tanggalFix)) {
                $bulanNum = (int)date('n', strtotime($tanggalFix));
                $tahunFix = date('Y', strtotime($tanggalFix));
                $bulanRomawiFix = isset($bulanRomawiArr[$bulanNum]) ? $bulanRomawiArr[$bulanNum] : '';
            }

            $row_id         = $id_surat;
            $aktorListStr   = $nama_selanjutnya;
            $nomor_ba       = $data['nomor_ba'];
            $tanggal        = $tanggalFix;
            $bulanRomawi    = $bulanRomawiFix;
            $tahun          = $tahunFix;
            $jenis_ba       = $jenis;
            $permintaan     = 'approval';
            $namaPeminta    = 'AutoMailer';
        }

        $stmt->close();
    }

    if (isset($_GET['status'])) {
        $status_user = $_GET['status'];
    }
} elseif ($jenis === 'mutasi') {
    $nama_user = isset($_SESSION['nama']) ? $_SESSION['nama'] : '';
    $autograph_base64 = '';
    $status_user = '';

    if (!empty($nama_user)) {
        $stmt = $koneksi->prepare("SELECT autograph FROM akun_akses WHERE nama = ? LIMIT 1");
        $stmt->bind_param("s", $nama_user);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        if ($row && !empty($row['autograph'])) {
            $autograph_base64 = 'data:image/png;base64,' . base64_encode($row['autograph']);
        }

        $stmt->close();
    }

    if (!empty($nama_user)) {
        $stmtApprove = $koneksi->prepare("
            SELECT nomor_ba, tanggal, 
            pengirim1, pengirim2, hrd_ga_pengirim, penerima1, penerima2, hrd_ga_penerima, diketahui, pemeriksa1, pemeriksa2, penyetujui1, penyetujui2,
            approval_1, approval_2, approval_3, approval_4, approval_5, approval_6, approval_7, approval_8, approval_9, approval_10, approval_11,
            autograph_1, autograph_2, autograph_3, autograph_4, autograph_5, autograph_6, autograph_7, autograph_8, autograph_9, autograph_10, autograph_11,
            tanggal_approve_1, tanggal_approve_2, tanggal_approve_3, tanggal_approve_4, tanggal_approve_5, tanggal_approve_6, tanggal_approve_7, tanggal_approve_8, tanggal_approve_9, tanggal_approve_10, tanggal_approve_11
            FROM berita_acara_mutasi
            WHERE id = ?
        ");
        $stmtApprove->bind_param("i", $id_surat);
        $stmtApprove->execute();
        $result = $stmtApprove->get_result();
        $data = $result->fetch_assoc();

        if ($data) {
            if ($nama_user === $data['pengirim1']) {
                $approval_col = 'approval_1';
                $autograph_col = 'autograph_1';
                $tanggal_col = 'tanggal_approve_1';
                $approval_val = $data['approval_1'];
                $autograph_val = $data['autograph_1'];
                foreach (['pengirim2', 'hrd_ga_pengirim', 'penerima1', 'penerima2', 'hrd_ga_penerima', 'diketahui', 'pemeriksa1', 'pemeriksa2', 'penyetujui1', 'penyetujui2'] as $next) {
                    if (!empty($data[$next]) && $data[$next] !== '-') {
                        $nama_selanjutnya = $data[$next];
                        break;
                    }
                }
                if ($nama_selanjutnya === '') {
                    $nama_selanjutnya = '-';
                }
            } elseif ($nama_user === $data['pengirim2']) {
                $approval_col = 'approval_2';
                $autograph_col = 'autograph_2';
                $tanggal_col = 'tanggal_approve_2';
                $approval_val = $data['approval_2'];
                $autograph_val = $data['autograph_2'];
                foreach (['hrd_ga_pengirim', 'penerima1', 'penerima2', 'hrd_ga_penerima', 'diketahui', 'pemeriksa1', 'pemeriksa2', 'penyetujui1', 'penyetujui2'] as $next) {
                    if (!empty($data[$next]) && $data[$next] !== '-') {
                        $nama_selanjutnya = $data[$next];
                        break;
                    }
                }
                if ($nama_selanjutnya === '') {
                    $nama_selanjutnya = '-';
                }
            } elseif ($nama_user === $data['hrd_ga_pengirim']) {
                $approval_col = 'approval_3';
                $autograph_col = 'autograph_3';
                $tanggal_col = 'tanggal_approve_3';
                $approval_val = $data['approval_3'];
                $autograph_val = $data['autograph_3'];
                foreach (['penerima1', 'penerima2', 'hrd_ga_penerima', 'diketahui', 'pemeriksa1', 'pemeriksa2', 'penyetujui1', 'penyetujui2'] as $next) {
                    if (!empty($data[$next]) && $data[$next] !== '-') {
                        $nama_selanjutnya = $data[$next];
                        break;
                    }
                }
                if ($nama_selanjutnya === '') {
                    $nama_selanjutnya = '-';
                }
            } elseif ($nama_user === $data['penerima1']) {
                $approval_col = 'approval_4';
                $autograph_col = 'autograph_4';
                $tanggal_col = 'tanggal_approve_4';
                $approval_val = $data['approval_4'];
                $autograph_val = $data['autograph_4'];
                foreach (['penerima2', 'hrd_ga_penerima', 'diketahui', 'pemeriksa1', 'pemeriksa2', 'penyetujui1', 'penyetujui2'] as $next) {
                    if (!empty($data[$next]) && $data[$next] !== '-') {
                        $nama_selanjutnya = $data[$next];
                        break;
                    }
                }
                if ($nama_selanjutnya === '') {
                    $nama_selanjutnya = '-';
                }
            } elseif ($nama_user === $data['penerima2']) {
                $approval_col = 'approval_5';
                $autograph_col = 'autograph_5';
                $tanggal_col = 'tanggal_approve_5';
                $approval_val = $data['approval_5'];
                $autograph_val = $data['autograph_5'];
                foreach (['hrd_ga_penerima', 'diketahui', 'pemeriksa1', 'pemeriksa2', 'penyetujui1', 'penyetujui2'] as $next) {
                    if (!empty($data[$next]) && $data[$next] !== '-') {
                        $nama_selanjutnya = $data[$next];
                        break;
                    }
                }
                if ($nama_selanjutnya === '') {
                    $nama_selanjutnya = '-';
                }
            } elseif ($nama_user === $data['hrd_ga_penerima']) {
                $approval_col = 'approval_6';
                $autograph_col = 'autograph_6';
                $tanggal_col = 'tanggal_approve_6';
                $approval_val = $data['approval_6'];
                $autograph_val = $data['autograph_6'];
                foreach (['diketahui', 'pemeriksa1', 'pemeriksa2', 'penyetujui1', 'penyetujui2'] as $next) {
                    if (!empty($data[$next]) && $data[$next] !== '-') {
                        $nama_selanjutnya = $data[$next];
                        break;
                    }
                }
                if ($nama_selanjutnya === '') {
                    $nama_selanjutnya = '-';
                }
            } elseif ($nama_user === $data['diketahui']) {
                $approval_col = 'approval_7';
                $autograph_col = 'autograph_7';
                $tanggal_col = 'tanggal_approve_7';
                $approval_val = $data['approval_7'];
                $autograph_val = $data['autograph_7'];
                foreach (['pemeriksa1', 'pemeriksa2', 'penyetujui1', 'penyetujui2'] as $next) {
                    if (!empty($data[$next]) && $data[$next] !== '-') {
                        $nama_selanjutnya = $data[$next];
                        break;
                    }
                }
                if ($nama_selanjutnya === '') {
                    $nama_selanjutnya = '-';
                }
            } elseif ($nama_user === $data['pemeriksa1']) {
                $approval_col = 'approval_8';
                $autograph_col = 'autograph_8';
                $tanggal_col = 'tanggal_approve_8';
                $approval_val = $data['approval_8'];
                $autograph_val = $data['autograph_8'];
                foreach (['pemeriksa2', 'penyetujui1', 'penyetujui2'] as $next) {
                    if (!empty($data[$next]) && $data[$next] !== '-') {
                        $nama_selanjutnya = $data[$next];
                        break;
                    }
                }
                if ($nama_selanjutnya === '') {
                    $nama_selanjutnya = '-';
                }
            } elseif ($nama_user === $data['pemeriksa2']) {
                $approval_col = 'approval_9';
                $autograph_col = 'autograph_9';
                $tanggal_col = 'tanggal_approve_9';
                $approval_val = $data['approval_9'];
                $autograph_val = $data['autograph_9'];
                foreach (['penyetujui1', 'penyetujui2'] as $next) {
                    if (!empty($data[$next]) && $data[$next] !== '-') {
                        $nama_selanjutnya = $data[$next];
                        break;
                    }
                }
                if ($nama_selanjutnya === '') {
                    $nama_selanjutnya = '-';
                }
            } elseif ($nama_user === $data['penyetujui1']) {
                $approval_col = 'approval_10';
                $autograph_col = 'autograph_10';
                $tanggal_col = 'tanggal_approve_10';
                $approval_val = $data['approval_10'];
                $autograph_val = $data['autograph_10'];
                foreach (['penyetujui2'] as $next) {
                    if (!empty($data[$next]) && $data[$next] !== '-') {
                        $nama_selanjutnya = $data[$next];
                        break;
                    }
                }
                if ($nama_selanjutnya === '') {
                    $nama_selanjutnya = '-';
                }
            } elseif ($nama_user === $data['penyetujui2']) {
                $approval_col = 'approval_11';
                $autograph_col = 'autograph_11';
                $tanggal_col = 'tanggal_approve_11';
                $approval_val = $data['approval_11'];
                $autograph_val = $data['autograph_11'];
                $nama_selanjutnya = '-';
            }

            if (!empty($approval_val) && empty($autograph_val)) {
                $status_user = 'approved_only';
            } elseif (!empty($approval_val) && !empty($autograph_val)) {
                $status_user = 'signed';
            }

            // ===============================
            // AMBIL DATA
            // ===============================
            $bulanRomawiArr = [
                1 => 'I',
                2 => 'II',
                3 => 'III',
                4 => 'IV',
                5 => 'V',
                6 => 'VI',
                7 => 'VII',
                8 => 'VIII',
                9 => 'IX',
                10 => 'X',
                11 => 'XI',
                12 => 'XII'
            ];

            $tanggalFix       = $data['tanggal'];
            $bulanRomawiFix   = '';
            $tahunFix         = '';

            if (!empty($tanggalFix)) {
                $bulanNum = (int)date('n', strtotime($tanggalFix));
                $tahunFix = date('Y', strtotime($tanggalFix));
                $bulanRomawiFix = isset($bulanRomawiArr[$bulanNum]) ? $bulanRomawiArr[$bulanNum] : '';
            }

            $row_id         = $id_surat;
            $aktorListStr   = $nama_selanjutnya;
            $nomor_ba       = $data['nomor_ba'];
            $tanggal        = $tanggalFix;
            $bulanRomawi    = $bulanRomawiFix;
            $tahun          = $tahunFix;
            $jenis_ba       = $jenis;
            $permintaan     = 'approval';
            $namaPeminta    = 'AutoMailer';
        }

        $stmtApprove->close();
    }
    if (isset($_GET['status'])) {
        $status_user = $_GET['status'];
    }
} elseif ($jenis === 'st_asset') {
    $nama_user = isset($_SESSION['nama']) ? $_SESSION['nama'] : '';
    $autograph_base64 = '';
    $status_user = '';

    if (!empty($nama_user)) {
        $stmt = $koneksi->prepare("SELECT autograph FROM akun_akses WHERE nama = ? LIMIT 1");
        $stmt->bind_param("s", $nama_user);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        if ($row && !empty($row['autograph'])) {
            $autograph_base64 = 'data:image/png;base64,' . base64_encode($row['autograph']);
        }

        $stmt->close();
    }

    if (!empty($nama_user)) {
        $stmtApprove = $koneksi->prepare("
            SELECT nomor_ba, tanggal, 
            peminjam, saksi, diketahui, pihak_pertama,
            approval_1, approval_2, approval_3, approval_4,
            autograph_1, autograph_2, autograph_3, autograph_4,
            tanggal_approve_1, tanggal_approve_2, tanggal_approve_3, tanggal_approve_4
            FROM ba_serah_terima_asset
            WHERE id = ?
        ");
        $stmtApprove->bind_param("i", $id_surat);
        $stmtApprove->execute();
        $result = $stmtApprove->get_result();
        $data = $result->fetch_assoc();

        if ($data) {
            if ($nama_user === $data['peminjam']) {
                $approval_col = 'approval_1';
                $autograph_col = 'autograph_1';
                $tanggal_col = 'tanggal_approve_1';
                $approval_val = $data['approval_1'];
                $autograph_val = $data['autograph_1'];
                foreach (['saksi', 'diketahui', 'pihak_pertama'] as $next) {
                    if (!empty($data[$next]) && $data[$next] !== '-') {
                        $nama_selanjutnya = $data[$next];
                        break;
                    }
                }
                if ($nama_selanjutnya === '') {
                    $nama_selanjutnya = '-';
                }
            } elseif ($nama_user === $data['saksi']) {
                $approval_col = 'approval_2';
                $autograph_col = 'autograph_2';
                $tanggal_col = 'tanggal_approve_2';
                $approval_val = $data['approval_2'];
                $autograph_val = $data['autograph_2'];
                foreach (['diketahui', 'pihak_pertama'] as $next) {
                    if (!empty($data[$next]) && $data[$next] !== '-') {
                        $nama_selanjutnya = $data[$next];
                        break;
                    }
                }
                if ($nama_selanjutnya === '') {
                    $nama_selanjutnya = '-';
                }
            } elseif ($nama_user === $data['diketahui']) {
                $approval_col = 'approval_3';
                $autograph_col = 'autograph_3';
                $tanggal_col = 'tanggal_approve_3';
                $approval_val = $data['approval_3'];
                $autograph_val = $data['autograph_3'];
                foreach (['pihak_pertama'] as $next) {
                    if (!empty($data[$next]) && $data[$next] !== '-') {
                        $nama_selanjutnya = $data[$next];
                        break;
                    }
                }
                if ($nama_selanjutnya === '') {
                    $nama_selanjutnya = '-';
                }
            } elseif ($nama_user === $data['pihak_pertama']) {
                $approval_col = 'approval_4';
                $autograph_col = 'autograph_4';
                $tanggal_col = 'tanggal_approve_4';
                $approval_val = $data['approval_4'];
                $autograph_val = $data['autograph_4'];
                $nama_selanjutnya = '-';
            }

            if (!empty($approval_val) && empty($autograph_val)) {
                $status_user = 'approved_only';
            } elseif (!empty($approval_val) && !empty($autograph_val)) {
                $status_user = 'signed';
            }

            // ===============================
            // AMBIL DATA
            // ===============================
            $bulanRomawiArr = [
                1 => 'I',
                2 => 'II',
                3 => 'III',
                4 => 'IV',
                5 => 'V',
                6 => 'VI',
                7 => 'VII',
                8 => 'VIII',
                9 => 'IX',
                10 => 'X',
                11 => 'XI',
                12 => 'XII'
            ];

            $tanggalFix       = $data['tanggal'];
            $bulanRomawiFix   = '';
            $tahunFix         = '';

            if (!empty($tanggalFix)) {
                $bulanNum = (int)date('n', strtotime($tanggalFix));
                $tahunFix = date('Y', strtotime($tanggalFix));
                $bulanRomawiFix = isset($bulanRomawiArr[$bulanNum]) ? $bulanRomawiArr[$bulanNum] : '';
            }

            $row_id         = $id_surat;
            $aktorListStr   = $nama_selanjutnya;
            $nomor_ba       = $data['nomor_ba'];
            $tanggal        = $tanggalFix;
            $bulanRomawi    = $bulanRomawiFix;
            $tahun          = $tahunFix;
            $jenis_ba       = $jenis;
            $permintaan     = 'approval';
            $namaPeminta    = 'AutoMailer';
        }

        $stmtApprove->close();
    }
    if (isset($_GET['status'])) {
        $status_user = $_GET['status'];
    }
} elseif ($jenis === 'pemutihan') {
    $nama_user = isset($_SESSION['nama']) ? $_SESSION['nama'] : '';
    $autograph_base64 = '';
    $status_user = '';

    if (!empty($nama_user)) {
        $stmt = $koneksi->prepare("SELECT autograph FROM akun_akses WHERE nama = ? LIMIT 1");
        $stmt->bind_param("s", $nama_user);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        if ($row && !empty($row['autograph'])) {
            $autograph_base64 = 'data:image/png;base64,' . base64_encode($row['autograph']);
        }

        $stmt->close();
    }

    if (!empty($nama_user)) {
        $stmtApprove = $koneksi->prepare("
            SELECT 
                nomor_ba, tanggal, pt,

                pembuat, pemeriksa, diketahui1, diketahui2, diketahui3, dibukukan, disetujui1, disetujui2, disetujui3,
                pembuat_site, pemeriksa_site, diketahui1_site, disetujui1_site, diketahui2_site, diperiksa_site, mengetahui_site,

                approval_1, approval_2, approval_3, approval_4, approval_5, approval_6, approval_7, approval_8, approval_9, approval_10, approval_11,
                autograph_1, autograph_2, autograph_3, autograph_4, autograph_5, autograph_6, autograph_7, autograph_8, autograph_9, autograph_10, autograph_11,
                tanggal_approve_1, tanggal_approve_2, tanggal_approve_3, tanggal_approve_4, tanggal_approve_5, tanggal_approve_6, tanggal_approve_7, tanggal_approve_8, tanggal_approve_9, tanggal_approve_10, tanggal_approve_11
            FROM berita_acara_pemutihan
            WHERE id = ?
        ");
        $stmtApprove->bind_param("i", $id_surat);
        $stmtApprove->execute();
        $result = $stmtApprove->get_result();
        $data = $result->fetch_assoc();

        if ($data) {
            $ptPemutihan = isset($data['pt']) ? trim($data['pt']) : '';
            $isHOPemutihan = ($ptPemutihan === 'PT.MSAL (HO)');
            $nama_selanjutnya = '-';

            if ($isHOPemutihan) {
                if ($nama_user === $data['pembuat']) {
                    $approval_col = 'approval_1';
                    $autograph_col = 'autograph_1';
                    $tanggal_col = 'tanggal_approve_1';
                    $approval_val = (int)$data['approval_1'];
                    $autograph_val = $data['autograph_1'];

                    foreach (array('pemeriksa', 'diketahui1', 'diketahui2', 'diketahui3', 'dibukukan', 'disetujui1', 'disetujui2', 'disetujui3') as $next) {
                        if (!empty($data[$next]) && $data[$next] !== '-') {
                            $nama_selanjutnya = $data[$next];
                            break;
                        }
                    }
                } elseif ($nama_user === $data['pemeriksa']) {
                    $approval_col = 'approval_2';
                    $autograph_col = 'autograph_2';
                    $tanggal_col = 'tanggal_approve_2';
                    $approval_val = (int)$data['approval_2'];
                    $autograph_val = $data['autograph_2'];

                    foreach (array('diketahui1', 'diketahui2', 'diketahui3', 'dibukukan', 'disetujui1', 'disetujui2', 'disetujui3') as $next) {
                        if (!empty($data[$next]) && $data[$next] !== '-') {
                            $nama_selanjutnya = $data[$next];
                            break;
                        }
                    }
                } elseif ($nama_user === $data['diketahui1']) {
                    $approval_col = 'approval_3';
                    $autograph_col = 'autograph_3';
                    $tanggal_col = 'tanggal_approve_3';
                    $approval_val = (int)$data['approval_3'];
                    $autograph_val = $data['autograph_3'];

                    foreach (array('diketahui2', 'diketahui3', 'dibukukan', 'disetujui1', 'disetujui2', 'disetujui3') as $next) {
                        if (!empty($data[$next]) && $data[$next] !== '-') {
                            $nama_selanjutnya = $data[$next];
                            break;
                        }
                    }
                } elseif ($nama_user === $data['diketahui2']) {
                    $approval_col = 'approval_4';
                    $autograph_col = 'autograph_4';
                    $tanggal_col = 'tanggal_approve_4';
                    $approval_val = (int)$data['approval_4'];
                    $autograph_val = $data['autograph_4'];

                    foreach (array('diketahui3', 'dibukukan', 'disetujui1', 'disetujui2', 'disetujui3') as $next) {
                        if (!empty($data[$next]) && $data[$next] !== '-') {
                            $nama_selanjutnya = $data[$next];
                            break;
                        }
                    }
                } elseif ($nama_user === $data['diketahui3']) {
                    $approval_col = 'approval_5';
                    $autograph_col = 'autograph_5';
                    $tanggal_col = 'tanggal_approve_5';
                    $approval_val = (int)$data['approval_5'];
                    $autograph_val = $data['autograph_5'];

                    foreach (array('dibukukan', 'disetujui1', 'disetujui2', 'disetujui3') as $next) {
                        if (!empty($data[$next]) && $data[$next] !== '-') {
                            $nama_selanjutnya = $data[$next];
                            break;
                        }
                    }
                } elseif ($nama_user === $data['dibukukan']) {
                    $approval_col = 'approval_6';
                    $autograph_col = 'autograph_6';
                    $tanggal_col = 'tanggal_approve_6';
                    $approval_val = (int)$data['approval_6'];
                    $autograph_val = $data['autograph_6'];

                    foreach (array('disetujui1', 'disetujui2', 'disetujui3') as $next) {
                        if (!empty($data[$next]) && $data[$next] !== '-') {
                            $nama_selanjutnya = $data[$next];
                            break;
                        }
                    }
                } elseif ($nama_user === $data['disetujui1']) {
                    $approval_col = 'approval_7';
                    $autograph_col = 'autograph_7';
                    $tanggal_col = 'tanggal_approve_7';
                    $approval_val = (int)$data['approval_7'];
                    $autograph_val = $data['autograph_7'];

                    foreach (array('disetujui2', 'disetujui3') as $next) {
                        if (!empty($data[$next]) && $data[$next] !== '-') {
                            $nama_selanjutnya = $data[$next];
                            break;
                        }
                    }
                } elseif ($nama_user === $data['disetujui2']) {
                    $approval_col = 'approval_8';
                    $autograph_col = 'autograph_8';
                    $tanggal_col = 'tanggal_approve_8';
                    $approval_val = (int)$data['approval_8'];
                    $autograph_val = $data['autograph_8'];

                    foreach (array('disetujui3') as $next) {
                        if (!empty($data[$next]) && $data[$next] !== '-') {
                            $nama_selanjutnya = $data[$next];
                            break;
                        }
                    }
                } elseif ($nama_user === $data['disetujui3']) {
                    $approval_col = 'approval_9';
                    $autograph_col = 'autograph_9';
                    $tanggal_col = 'tanggal_approve_9';
                    $approval_val = (int)$data['approval_9'];
                    $autograph_val = $data['autograph_9'];
                    $nama_selanjutnya = '-';
                }
            } else {
                if ($nama_user === $data['pembuat_site']) {
                    $approval_col = 'approval_1';
                    $autograph_col = 'autograph_1';
                    $tanggal_col = 'tanggal_approve_1';
                    $approval_val = (int)$data['approval_1'];
                    $autograph_val = $data['autograph_1'];

                    foreach (array('pemeriksa_site', 'diketahui1_site', 'disetujui1_site', 'diketahui2_site', 'diperiksa_site', 'dibukukan', 'disetujui1', 'disetujui2', 'disetujui3', 'mengetahui_site') as $next) {
                        if (!empty($data[$next]) && $data[$next] !== '-') {
                            $nama_selanjutnya = $data[$next];
                            break;
                        }
                    }
                } elseif ($nama_user === $data['pemeriksa_site']) {
                    $approval_col = 'approval_2';
                    $autograph_col = 'autograph_2';
                    $tanggal_col = 'tanggal_approve_2';
                    $approval_val = (int)$data['approval_2'];
                    $autograph_val = $data['autograph_2'];

                    foreach (array('diketahui1_site', 'disetujui1_site', 'diketahui2_site', 'diperiksa_site', 'dibukukan', 'disetujui1', 'disetujui2', 'disetujui3', 'mengetahui_site') as $next) {
                        if (!empty($data[$next]) && $data[$next] !== '-') {
                            $nama_selanjutnya = $data[$next];
                            break;
                        }
                    }
                } elseif ($nama_user === $data['diketahui1_site']) {
                    $approval_col = 'approval_3';
                    $autograph_col = 'autograph_3';
                    $tanggal_col = 'tanggal_approve_3';
                    $approval_val = (int)$data['approval_3'];
                    $autograph_val = $data['autograph_3'];

                    foreach (array('disetujui1_site', 'diketahui2_site', 'diperiksa_site', 'dibukukan', 'disetujui1', 'disetujui2', 'disetujui3', 'mengetahui_site') as $next) {
                        if (!empty($data[$next]) && $data[$next] !== '-') {
                            $nama_selanjutnya = $data[$next];
                            break;
                        }
                    }
                } elseif ($nama_user === $data['disetujui1_site']) {
                    $approval_col = 'approval_4';
                    $autograph_col = 'autograph_4';
                    $tanggal_col = 'tanggal_approve_4';
                    $approval_val = (int)$data['approval_4'];
                    $autograph_val = $data['autograph_4'];

                    foreach (array('diketahui2_site', 'diperiksa_site', 'dibukukan', 'disetujui1', 'disetujui2', 'disetujui3', 'mengetahui_site') as $next) {
                        if (!empty($data[$next]) && $data[$next] !== '-') {
                            $nama_selanjutnya = $data[$next];
                            break;
                        }
                    }
                } elseif ($nama_user === $data['diketahui2_site']) {
                    $approval_col = 'approval_5';
                    $autograph_col = 'autograph_5';
                    $tanggal_col = 'tanggal_approve_5';
                    $approval_val = (int)$data['approval_5'];
                    $autograph_val = $data['autograph_5'];

                    foreach (array('diperiksa_site', 'dibukukan', 'disetujui1', 'disetujui2', 'disetujui3', 'mengetahui_site') as $next) {
                        if (!empty($data[$next]) && $data[$next] !== '-') {
                            $nama_selanjutnya = $data[$next];
                            break;
                        }
                    }
                } elseif ($nama_user === $data['diperiksa_site']) {
                    $approval_col = 'approval_6';
                    $autograph_col = 'autograph_6';
                    $tanggal_col = 'tanggal_approve_6';
                    $approval_val = (int)$data['approval_6'];
                    $autograph_val = $data['autograph_6'];

                    foreach (array('dibukukan', 'disetujui1', 'disetujui2', 'disetujui3', 'mengetahui_site') as $next) {
                        if (!empty($data[$next]) && $data[$next] !== '-') {
                            $nama_selanjutnya = $data[$next];
                            break;
                        }
                    }
                } elseif ($nama_user === $data['dibukukan']) {
                    $approval_col = 'approval_7';
                    $autograph_col = 'autograph_7';
                    $tanggal_col = 'tanggal_approve_7';
                    $approval_val = (int)$data['approval_7'];
                    $autograph_val = $data['autograph_7'];

                    foreach (array('disetujui1', 'disetujui2', 'disetujui3', 'mengetahui_site') as $next) {
                        if (!empty($data[$next]) && $data[$next] !== '-') {
                            $nama_selanjutnya = $data[$next];
                            break;
                        }
                    }
                } elseif ($nama_user === $data['disetujui1']) {
                    $approval_col = 'approval_8';
                    $autograph_col = 'autograph_8';
                    $tanggal_col = 'tanggal_approve_8';
                    $approval_val = (int)$data['approval_8'];
                    $autograph_val = $data['autograph_8'];

                    foreach (array('disetujui2', 'disetujui3', 'mengetahui_site') as $next) {
                        if (!empty($data[$next]) && $data[$next] !== '-') {
                            $nama_selanjutnya = $data[$next];
                            break;
                        }
                    }
                } elseif ($nama_user === $data['disetujui2']) {
                    $approval_col = 'approval_9';
                    $autograph_col = 'autograph_9';
                    $tanggal_col = 'tanggal_approve_9';
                    $approval_val = (int)$data['approval_9'];
                    $autograph_val = $data['autograph_9'];

                    foreach (array('disetujui3', 'mengetahui_site') as $next) {
                        if (!empty($data[$next]) && $data[$next] !== '-') {
                            $nama_selanjutnya = $data[$next];
                            break;
                        }
                    }
                } elseif ($nama_user === $data['disetujui3']) {
                    $approval_col = 'approval_10';
                    $autograph_col = 'autograph_10';
                    $tanggal_col = 'tanggal_approve_10';
                    $approval_val = (int)$data['approval_10'];
                    $autograph_val = $data['autograph_10'];

                    foreach (array('mengetahui_site') as $next) {
                        if (!empty($data[$next]) && $data[$next] !== '-') {
                            $nama_selanjutnya = $data[$next];
                            break;
                        }
                    }
                } elseif ($nama_user === $data['mengetahui_site']) {
                    $approval_col = 'approval_11';
                    $autograph_col = 'autograph_11';
                    $tanggal_col = 'tanggal_approve_11';
                    $approval_val = (int)$data['approval_11'];
                    $autograph_val = $data['autograph_11'];
                    $nama_selanjutnya = '-';
                }
            }

            if (!empty($approval_val) && empty($autograph_val)) {
                $status_user = 'approved_only';
            } elseif (!empty($approval_val) && !empty($autograph_val)) {
                $status_user = 'signed';
            }

            $bulanRomawiArr = array(
                1 => 'I',
                2 => 'II',
                3 => 'III',
                4 => 'IV',
                5 => 'V',
                6 => 'VI',
                7 => 'VII',
                8 => 'VIII',
                9 => 'IX',
                10 => 'X',
                11 => 'XI',
                12 => 'XII'
            );

            $tanggalFix = $data['tanggal'];
            $bulanRomawiFix = '';
            $tahunFix = '';

            if (!empty($tanggalFix)) {
                $bulanNum = (int)date('n', strtotime($tanggalFix));
                $tahunFix = date('Y', strtotime($tanggalFix));
                $bulanRomawiFix = isset($bulanRomawiArr[$bulanNum]) ? $bulanRomawiArr[$bulanNum] : '';
            }

            $row_id       = $id_surat;
            $aktorListStr = $nama_selanjutnya;
            $nomor_ba     = $data['nomor_ba'];
            $tanggal      = $tanggalFix;
            $bulanRomawi  = $bulanRomawiFix;
            $tahun        = $tahunFix;
            $jenis_ba     = $jenis;
            $permintaan   = 'approval';
            $namaPeminta  = 'AutoMailer';
        }

        $stmtApprove->close();
    }

    if (isset($_GET['status'])) {
        $status_user = $_GET['status'];
    }
} elseif ($jenis === 'pengembalian') {
    $nama_user = isset($_SESSION['nama']) ? $_SESSION['nama'] : '';
    $autograph_base64 = '';
    $status_user = '';

    if (!empty($nama_user)) {
        $stmt = $koneksi->prepare("SELECT autograph FROM akun_akses WHERE nama = ? LIMIT 1");
        $stmt->bind_param("s", $nama_user);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        if ($row && !empty($row['autograph'])) {
            $autograph_base64 = 'data:image/png;base64,' . base64_encode($row['autograph']);
        }

        $stmt->close();
    }

    if (!empty($nama_user)) {
        $stmtApprove = $koneksi->prepare("
            SELECT nomor_ba, tanggal,
                   pengembali, penerima, diketahui,
                   approval_1, approval_2, approval_3,
                   autograph_1, autograph_2, autograph_3,
                   tanggal_approve_1, tanggal_approve_2, tanggal_approve_3
            FROM berita_acara_pengembalian_v2
            WHERE id = ?
        ");
        $stmtApprove->bind_param("i", $id_surat);
        $stmtApprove->execute();
        $result = $stmtApprove->get_result();
        $data = $result->fetch_assoc();

        if ($data) {
            $nama_selanjutnya = '-';

            if ($nama_user === $data['pengembali']) {
                $approval_col = 'approval_1';
                $autograph_col = 'autograph_1';
                $tanggal_col = 'tanggal_approve_1';
                $approval_val = $data['approval_1'];
                $autograph_val = $data['autograph_1'];

                foreach (array('penerima', 'diketahui') as $next) {
                    if (!empty($data[$next]) && $data[$next] !== '-') {
                        $nama_selanjutnya = $data[$next];
                        break;
                    }
                }
            } elseif ($nama_user === $data['penerima']) {
                $approval_col = 'approval_2';
                $autograph_col = 'autograph_2';
                $tanggal_col = 'tanggal_approve_2';
                $approval_val = $data['approval_2'];
                $autograph_val = $data['autograph_2'];

                foreach (array('diketahui') as $next) {
                    if (!empty($data[$next]) && $data[$next] !== '-') {
                        $nama_selanjutnya = $data[$next];
                        break;
                    }
                }
            } elseif ($nama_user === $data['diketahui']) {
                $approval_col = 'approval_3';
                $autograph_col = 'autograph_3';
                $tanggal_col = 'tanggal_approve_3';
                $approval_val = $data['approval_3'];
                $autograph_val = $data['autograph_3'];
                $nama_selanjutnya = '-';
            }

            if (!empty($approval_val) && empty($autograph_val)) {
                $status_user = 'approved_only';
            } elseif (!empty($approval_val) && !empty($autograph_val)) {
                $status_user = 'signed';
            }

            $bulanRomawiArr = array(
                1 => 'I',
                2 => 'II',
                3 => 'III',
                4 => 'IV',
                5 => 'V',
                6 => 'VI',
                7 => 'VII',
                8 => 'VIII',
                9 => 'IX',
                10 => 'X',
                11 => 'XI',
                12 => 'XII'
            );

            $tanggalFix = $data['tanggal'];
            $bulanRomawiFix = '';
            $tahunFix = '';

            if (!empty($tanggalFix)) {
                $bulanNum = (int)date('n', strtotime($tanggalFix));
                $tahunFix = date('Y', strtotime($tanggalFix));
                $bulanRomawiFix = isset($bulanRomawiArr[$bulanNum]) ? $bulanRomawiArr[$bulanNum] : '';
            }

            $row_id       = $id_surat;
            $aktorListStr = $nama_selanjutnya;
            $nomor_ba     = $data['nomor_ba'];
            $tanggal      = $tanggalFix;
            $bulanRomawi  = $bulanRomawiFix;
            $tahun        = $tahunFix;
            $jenis_ba     = $jenis;
            $permintaan   = 'approval';
            $namaPeminta  = 'AutoMailer';
        }

        $stmtApprove->close();
    }

    if (isset($_GET['status'])) {
        $status_user = $_GET['status'];
    }
}

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tanda Tangan Persetujuan <?php echo $jenis_ba2 ?></title>

    <link rel="stylesheet" href="../assets/bootstrap-5.3.6-dist/css/bootstrap.min.css">
    <script src="../assets/bootstrap-5.3.6-dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/signature_pad.umd.min.js"></script>

    <style>
        body {
            background-color: rgb(2, 77, 207);
        }

        .a4-container {
            width: 210mm;
            height: 297mm;
            background: white;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
            margin: 30px auto;
            padding: 20px;
        }

        .a4-container-landcape {
            width: 297mm;
            height: 210mm;
            background: white;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
            margin: 30px auto;
            padding: 20px;
        }

        @media (max-width: 576px) {
            .custom-surat-wrapper{
                width: 100%;
                overflow: auto;
            }
        }

        iframe {
            width: 100%;
            height: 100%;
            border: none;
        }

        .signature-pad {
            border: 2px solid #ccc;
            border-radius: 10px;
            width: 500px;
            /* PC/Tablet */
            height: 200px;
            max-width: 100%;
            display: block;
            margin: 0 auto;
            background: #fff;
            touch-action: none;
        }

        @media (max-width: 576px) {
            .signature-pad {
                width: 300px;
                /* HP */
                height: 200px;
                max-width: 300px;
            }
        }
    </style>
</head>

<body>
    <div class="container py-4">
        <h2 class="mb-3 text-center text-white">Halaman Approval Surat</h2>
        <!-- <p class="text-white">approval col: <?= htmlspecialchars($approval_col ?: '-') ?></p>
    <p class="text-white">autograph col: <?= htmlspecialchars($autograph_col ?: '-') ?></p>
    <p class="text-white">tanggal col: <?= htmlspecialchars($tanggal_col ?: '-') ?></p>
    <p><?= htmlspecialchars($nama_selanjutnya)  ?></p>
    <p><?= htmlspecialchars($row_id)  ?></p>
    <p><?= htmlspecialchars($aktorListStr)  ?></p>
    <p><?= htmlspecialchars($nomor_ba)  ?></p>
    <p><?= htmlspecialchars($tanggal)  ?></p>
    <p><?= htmlspecialchars($bulanRomawi)  ?></p>
    <p><?= htmlspecialchars($tahun)  ?></p> -->
        <?php if ($status_user === 'signed'): ?>
            <div class="text-center text-white my-5">
                <h2>Anda telah menandatangani surat ini, Terima kasih.</h2>
                <div class="d-flex justify-content-center mt-3 gap-2">
                    <a class="btn btn-lg btn-primary" href="approval.php">Approval</a>
                    <a href="../logout.php" class="btn btn-lg btn-danger">Keluar</a>
                </div>
            </div>

        <?php elseif ($status_user === 'approved_only'): ?>
            <div class="text-center text-white my-5">
                <h2>Anda telah melakukan approval surat ini, Terima kasih.</h2>

                <div class="d-flex justify-content-center mt-3 gap-2">
                    <a class="btn btn-lg btn-primary" href="approval.php">Approval</a>
                    <a href="../logout.php" class="btn btn-lg btn-danger">Keluar</a>
                </div>

                <!-- card tanda tangan -->
                <div class="d-flex flex-column align-items-center gap-3 my-4">
                    <h4 class="text-warning">Atau tanda tangani surat</h4>
                    <div class="card shadow p-4" style="max-width: 600px; width: 100%;">
                        <h5 class="mb-3 text-center">Tanda Tangan Anda</h5>
                        <canvas id="signaturePad" class="signature-pad mb-3"></canvas>
                        <div class="d-flex justify-content-between">
                            <div class="d-flex gap-1">
                                <button id="clearBtn" class="btn btn-warning">Bersihkan</button>
                                <button id="loadBtn" class="btn btn-primary btn-sm">Reset</button>
                            </div>
                            <button id="submitBtn" class="btn btn-success">Kirim Persetujuan</button>
                        </div>
                    </div>
                </div>
            </div>
        <?php elseif (($status_user !== 'approved_only') && ($status_user !== 'signed')): ?>
            <!-- card tanda tangan -->
            <div class="d-flex flex-column align-items-center gap-3 mb-4">
                <h4 class="text-warning">Tanda tangan surat</h4>
                <div class="card shadow p-4" style="max-width: 600px; width: 100%;">
                    <h5 class="mb-3 text-center">Tanda Tangan Anda</h5>
                    <canvas id="signaturePad" class="signature-pad mb-3"></canvas>
                    <div class="d-flex justify-content-between">
                        <div class="d-flex btn-group">
                            <button id="clearBtn" class="btn btn-warning">Bersihkan</button>
                            <!-- Load TTD
                            <button id="loadBtn" class="btn btn-primary">Reset</button>
                            -->
                        </div>
                        <button id="submitBtn" class="btn btn-success">Kirim Persetujuan</button>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- tampilkan surat dalam ukuran A4 -->
        <?php if ($jenis === 'kerusakan'): ?>
            <div class="w-100 d-flex flex-column align-items-center mb-4">
                <div class="bg-white rounded p-3 shadow custom-surat-wrapper">
                    <h4 class="p-0">Preview Surat</h4>
                    <div class="a4-container mb-5 mt-0">
                        <iframe src="surat_output_kerusakan.php?id=<?= urlencode($id_surat) ?>"></iframe>
                    </div>
                </div>
            </div>
        <?php elseif ($jenis === 'mutasi'): ?>
            <div class="w-100 d-flex flex-column align-items-center mb-4">
                <div class="bg-white rounded p-3 shadow custom-surat-wrapper">
                    <h4 class="p-0">Preview Surat</h4>
                    <div class="a4-container-landcape mb-5 mt-0">
                        <iframe src="surat_output_mutasi.php?id=<?= urlencode($id_surat) ?>"></iframe>
                    </div>
                </div>
            </div>
        <?php elseif ($jenis === 'st_asset'): ?>
            <div class="w-100 d-flex flex-column align-items-center mb-4">
                <div class="bg-white rounded p-3 shadow custom-surat-wrapper">
                    <h4 class="p-0">Preview Surat</h4>
                    <div class="a4-container mb-5 mt-0">
                        <iframe src="surat_output_serah_terima_asset.php?id=<?= urlencode($id_surat) ?>"></iframe>
                    </div>
                </div>
            </div>
        <?php elseif ($jenis === 'pemutihan'): ?>
            <div class="w-100 d-flex flex-column align-items-center mb-4">
                <div class="bg-white rounded p-3 shadow custom-surat-wrapper">
                    <h4 class="p-0">Preview Surat</h4>
                    <div class="a4-container-landcape mb-5 mt-0">
                        <iframe src="surat_output_pemutihan.php?id=<?= urlencode($id_surat) ?>"></iframe>
                    </div>
                </div>
            </div>
        <?php elseif ($jenis === 'pengembalian'): ?>
            <div class="w-100 d-flex flex-column align-items-center mb-4">
                <div class="bg-white rounded p-3 shadow custom-surat-wrapper">
                    <h4 class="p-0">Preview Surat</h4>
                    <div class="a4-container mb-5 mt-0">
                        <iframe src="surat_output_pengembalian.php?id=<?= urlencode($id_surat) ?>"></iframe>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-warning text-center">
                Jenis BA <strong><?= htmlspecialchars($jenis_ba2) ?></strong> belum didukung untuk pratinjau.
            </div>
        <?php endif; ?>

        <?php if ($status_user !== 'approved_only' && $status_user !== 'signed'): ?>
            <div class="d-flex justify-content-center mb-4 gap-2">
                <a class="btn btn-lg btn-primary mt-3" href="approval.php">Approval</a>
                <a class="btn btn-lg btn-danger mt-3" href="../logout.php">Keluar</a>
            </div>
        <?php endif; ?>

    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const canvas = document.getElementById('signaturePad');
            const clearBtn = document.getElementById('clearBtn');
            const loadBtn = document.getElementById('loadBtn');
            const submitBtn = document.getElementById('submitBtn');

            // if (!canvas || !clearBtn || !loadBtn || !submitBtn) return; Load TTD
            if (!canvas || !clearBtn || !submitBtn) return;

            const DEFAULT_SIGNATURE = "<?= $autograph_base64 ?>";
            const CANONICAL_SAVE_WIDTH = 500; // simpan stabil (agar tidak shrink loop)
            const CANONICAL_SAVE_HEIGHT = 200;

            let signaturePad = null;
            let logicalWidth = 500;
            let logicalHeight = 200;
            let resizeTimer = null;
            let drawToken = 0;

            // =========================
            // Helpers ukuran canvas (DPR aware)
            // =========================
            function getDPR() {
                return Math.max(window.devicePixelRatio || 1, 1);
            }

            function getDisplaySize() {
                // HP: 300x200, selain itu 500x200
                if (window.innerWidth <= 576) {
                    return {
                        width: 300,
                        height: 200
                    };
                }
                return {
                    width: 500,
                    height: 200
                };
            }

            function resetCanvas(preserveWhiteBg = false) {
                const size = getDisplaySize();
                const dpr = getDPR();
                const ctx = canvas.getContext('2d');

                logicalWidth = size.width;
                logicalHeight = size.height;

                // Paksa ukuran tampilan (CSS pixel)
                canvas.style.width = size.width + 'px';
                canvas.style.height = size.height + 'px';

                // Ukuran internal bitmap (device pixel)
                canvas.width = Math.round(size.width * dpr);
                canvas.height = Math.round(size.height * dpr);

                // WAJIB reset transform dulu (biar scale tidak numpuk)
                ctx.setTransform(1, 0, 0, 1, 0, 0);
                ctx.clearRect(0, 0, canvas.width, canvas.height);

                // Scale ke logical px
                ctx.scale(dpr, dpr);

                if (preserveWhiteBg) {
                    ctx.fillStyle = '#ffffff';
                    ctx.fillRect(0, 0, logicalWidth, logicalHeight);
                }

                if (signaturePad) {
                    signaturePad.clear();
                }
            }

            function isCanvasEmptyReal() {
                // cek isi canvas nyata (bukan signaturePad.isEmpty, karena gambar manual via ctx)
                const ctx = canvas.getContext('2d', {
                    willReadFrequently: true
                });
                const w = canvas.width;
                const h = canvas.height;
                if (!w || !h) return true;

                const img = ctx.getImageData(0, 0, w, h).data;

                // kosong jika semua alpha = 0
                for (let i = 3; i < img.length; i += 4) {
                    if (img[i] !== 0) return false;
                }
                return true;
            }

            function snapshotCanvas() {
                if (isCanvasEmptyReal()) return '';
                return canvas.toDataURL('image/png');
            }

            function drawDataUrlFit(dataUrl) {
                const myToken = ++drawToken;

                if (!dataUrl || typeof dataUrl !== 'string') {
                    resetCanvas(false);
                    return;
                }

                const img = new Image();
                img.onload = function() {
                    if (myToken !== drawToken) return;

                    resetCanvas(false);

                    const ctx = canvas.getContext('2d');

                    // IMPORTANT:
                    // Jangan "fit/contain" karena rasio HP (300x200) != rasio simpan (500x200)
                    // Pakai stretch penuh agar tidak terjadi shrink loop saat save berulang.
                    ctx.drawImage(img, 0, 0, logicalWidth, logicalHeight);
                };
                img.onerror = function() {
                    if (myToken !== drawToken) return;
                    console.error('Gagal memuat gambar tanda tangan default');
                    resetCanvas(false);
                };
                img.src = dataUrl;
            }

            function resizePreserveCanvasContent() {
                const shot = snapshotCanvas(); // snapshot sebelum resize
                resetCanvas(false);
                if (shot) {
                    drawDataUrlFit(shot);
                }
            }

            // =========================
            // Export stabil (anti shrink berulang)
            // =========================
            function exportSignatureStableDataURL() {
                const off = document.createElement('canvas');
                off.width = CANONICAL_SAVE_WIDTH; // 500
                off.height = CANONICAL_SAVE_HEIGHT; // 200

                const ctx = off.getContext('2d');

                // background putih agar konsisten disimpan
                ctx.fillStyle = '#ffffff';
                ctx.fillRect(0, 0, off.width, off.height);

                const currentData = canvas.toDataURL('image/png');
                const img = new Image();

                return new Promise((resolve, reject) => {
                    img.onload = function() {
                        // IMPORTANT:
                        // Jangan fit/contain di export, karena itu sumber shrink loop.
                        // Langsung stretch full canvas visible -> canonical 500x200.
                        ctx.drawImage(img, 0, 0, off.width, off.height);
                        resolve(off.toDataURL('image/png'));
                    };
                    img.onerror = reject;
                    img.src = currentData;
                });
            }

            // =========================
            // Init SignaturePad (setelah canvas di-size)
            // =========================
            resetCanvas(false);

            signaturePad = new SignaturePad(canvas, {
                backgroundColor: 'rgba(0,0,0,0)', // transparan di canvas visible
                penColor: 'black'
            });

            // =========================
            // Load tanda tangan DB
            // =========================
            // function loadDefaultSignature() {
            //     if (!DEFAULT_SIGNATURE) {
            //         resetCanvas(false);
            //         return;
            //     }
            //     drawDataUrlFit(DEFAULT_SIGNATURE);
            // }

            // load awal
            // loadDefaultSignature();

            // =========================
            // Buttons
            // =========================
            clearBtn.addEventListener('click', function(e) {
                e.preventDefault();
                resetCanvas(false);
            });

            /*
            loadBtn.addEventListener('click', function(e) {
                e.preventDefault();
                loadDefaultSignature();
            });
            */

            submitBtn.addEventListener('click', async function(e) {
                e.preventDefault();

                if (isCanvasEmptyReal()) {
                    alert('Silakan tanda tangani terlebih dahulu.');
                    return;
                }

                let signatureData = '';
                try {
                    // simpan stabil 500x200 agar tidak shrink setiap siklus
                    signatureData = await exportSignatureStableDataURL();
                } catch (err) {
                    console.error(err);
                    alert('Gagal memproses tanda tangan.');
                    return;
                }

                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'proses_email_approval.php';

                function addField(name, value) {
                    let input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = name;
                    input.value = value ?? '';
                    form.appendChild(input);
                }

                addField('signature_base64', signatureData);
                addField('jenis', '<?= $jenis ?>');
                addField('id', '<?= $id_surat ?>');
                addField('approvalCol', '<?= $approval_col ?>');
                addField('autographCol', '<?= $autograph_col ?>');
                addField('tanggalCol', '<?= $tanggal_col ?>');

                addField('row_id', '<?= isset($row_id) ? $row_id : '' ?>');
                addField('aktorListStr', '<?= isset($aktorListStr) ? $aktorListStr : '' ?>');
                addField('nomor_ba', '<?= isset($nomor_ba) ? $nomor_ba : '' ?>');
                addField('tanggal', '<?= isset($tanggal) ? $tanggal : '' ?>');
                addField('bulanRomawi', '<?= isset($bulanRomawi) ? $bulanRomawi : '' ?>');
                addField('tahun', '<?= isset($tahun) ? $tahun : '' ?>');
                addField('jenis_ba', '<?= isset($jenis_ba) ? $jenis_ba : '' ?>');
                addField('permintaan', '<?= isset($permintaan) ? $permintaan : '' ?>');
                addField('namaPeminta', '<?= isset($namaPeminta) ? $namaPeminta : '' ?>');

                document.body.appendChild(form);
                form.submit();
            });

            // =========================
            // Resize window (preserve isi canvas)
            // =========================
            window.addEventListener('resize', function() {
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(function() {
                    resizePreserveCanvasContent();
                }, 120);
            });
        });
    </script>

</body>

</html>