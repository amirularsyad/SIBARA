<?php
require_once "../koneksi.php";
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id           = isset($_POST['id']) ? $_POST['id'] : '';
    $approvalCol  = isset($_POST['approvalCol']) ? $_POST['approvalCol'] : '';
    $autographCol = isset($_POST['autographCol']) ? $_POST['autographCol'] : '';
    $nama         = isset($_POST['nama']) ? $_POST['nama'] : '';
    $peran        = isset($_POST['peran']) ? $_POST['peran'] : '';
    $jenis        = isset($_POST['jenis']) ? $_POST['jenis'] : '';
    $nomor        = isset($_POST['nomor']) ? $_POST['nomor'] : '';
    $periode      = isset($_POST['periode']) ? $_POST['periode'] : '';
    $tahun        = isset($_POST['tahun']) ? $_POST['tahun'] : '';

    $approval_col   = '';
    $autograph_col  = '';
    $tanggal_col    = '';
    $approval_val   = '';
    $autograph_val  = '';
    $nama_selanjutnya = '-';
    $row_id         = $id;
    $aktorListStr   = '-';
    $nomor_ba       = '';
    $tanggal        = '';
    $bulanRomawi    = '';
    $tahun          = '';
    $jenis_ba       = $jenis;
    $permintaan     = 'approval';
    $namaPeminta    = 'HomeMailer';
    $status_user    = '';

    // print_r($_POST);
    // exit;
    if ($jenis === 'kerusakan') {
        $nama_user = $nama;
        $status_user = '';

        if (!empty($nama_user)) {
            $stmtApprove = $koneksi->prepare("
            SELECT nomor_ba, tanggal, pt, pembuat, penyetujui, peminjam, atasan_peminjam, diketahui,
                   approval_1, approval_2, approval_3, approval_4, approval_5,
                   autograph_1, autograph_2, autograph_3, autograph_4, autograph_5,
                   tanggal_approve_1, tanggal_approve_2, tanggal_approve_3, tanggal_approve_4, tanggal_approve_5
            FROM berita_acara_kerusakan
            WHERE id = ?
        ");
            $stmtApprove->bind_param("i", $id);
            $stmtApprove->execute();
            $result = $stmtApprove->get_result();
            $data = $result->fetch_assoc();

            if ($data) {

                $nama_selanjutnya = '-';

                if ($data['pt'] === "PT.MSAL (HO)") {
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
                } else {
                    if ($nama_user === $data['peminjam']) {
                        $approval_col = 'approval_3';
                        $autograph_col = 'autograph_3';
                        $tanggal_col = 'tanggal_approve_3';
                        $approval_val = $data['approval_3'];
                        $autograph_val = $data['autograph_3'];
                        foreach (['atasan_peminjam', 'diketahui', 'penyetujui', 'pembuat'] as $next) {
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
                        foreach (['diketahui', 'penyetujui', 'pembuat'] as $next) {
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
                        foreach (['penyetujui', 'pembuat'] as $next) {
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
                        foreach (['pembuat'] as $next) {
                            if (!empty($data[$next]) && $data[$next] !== '-') {
                                $nama_selanjutnya = $data[$next];
                                break;
                            }
                        }
                        if ($nama_selanjutnya === '') {
                            $nama_selanjutnya = '-';
                        }
                    } elseif ($nama_user === $data['pembuat']) {
                        $approval_col = 'approval_1';
                        $autograph_col = 'autograph_1';
                        $tanggal_col = 'tanggal_approve_1';
                        $approval_val = $data['approval_1'];
                        $autograph_val = $data['autograph_1'];
                        $nama_selanjutnya = '-';
                    }
                }
                if ($nama_selanjutnya === '') $nama_selanjutnya = '-';

                // if (!empty($approval_val) && empty($autograph_val)) {
                //     $status_user = 'approved_only';
                // } elseif (!empty($approval_val) && !empty($autograph_val)) {
                //     $status_user = 'signed';
                // }

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

                $row_id         = $id;
                $aktorListStr   = $nama_selanjutnya;
                $nomor_ba       = $data['nomor_ba'];
                $tanggal        = $tanggalFix;
                $bulanRomawi    = $bulanRomawiFix;
                $tahun          = $tahunFix;
                $jenis_ba       = $jenis;
                $permintaan     = 'approval';
                $namaPeminta    = 'HomeMailer';
            }

            $stmtApprove->close();
        }

        if (isset($_GET['status'])) {
            $status_user = $_GET['status'];
        }
    } elseif ($jenis === 'pengembalian') {
        $nama_user = $nama;
        $status_user = '';

        if (!empty($nama_user)) {
            $stmtApprove = $koneksi->prepare("
            SELECT 
                nomor_ba, tanggal, pt,
                pengembali, penerima, diketahui,
                approval_1, approval_2, approval_3,
                autograph_1, autograph_2, autograph_3,
                tanggal_approve_1, tanggal_approve_2, tanggal_approve_3
            FROM berita_acara_pengembalian_v2
            WHERE id = ?
        ");
            $stmtApprove->bind_param("i", $id);
            $stmtApprove->execute();
            $result = $stmtApprove->get_result();
            $data = $result->fetch_assoc();

            if ($data) {

                $nama_selanjutnya = '-';

                if ($nama_user === $data['pengembali']) {
                    $approval_col  = 'approval_1';
                    $autograph_col = 'autograph_1';
                    $tanggal_col   = 'tanggal_approve_1';
                    $approval_val  = $data['approval_1'];
                    $autograph_val = $data['autograph_1'];

                    foreach (array('penerima', 'diketahui') as $next) {
                        if (!empty($data[$next]) && $data[$next] !== '-') {
                            $nama_selanjutnya = $data[$next];
                            break;
                        }
                    }

                    if ($nama_selanjutnya === '') {
                        $nama_selanjutnya = '-';
                    }
                } elseif ($nama_user === $data['penerima']) {
                    $approval_col  = 'approval_2';
                    $autograph_col = 'autograph_2';
                    $tanggal_col   = 'tanggal_approve_2';
                    $approval_val  = $data['approval_2'];
                    $autograph_val = $data['autograph_2'];

                    foreach (array('diketahui') as $next) {
                        if (!empty($data[$next]) && $data[$next] !== '-') {
                            $nama_selanjutnya = $data[$next];
                            break;
                        }
                    }

                    if ($nama_selanjutnya === '') {
                        $nama_selanjutnya = '-';
                    }
                } elseif ($nama_user === $data['diketahui']) {
                    $approval_col  = 'approval_3';
                    $autograph_col = 'autograph_3';
                    $tanggal_col   = 'tanggal_approve_3';
                    $approval_val  = $data['approval_3'];
                    $autograph_val = $data['autograph_3'];
                    $nama_selanjutnya = '-';
                }

                // ===============================
                // AMBIL DATA
                // ===============================
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

                $row_id       = $id;
                $aktorListStr = $nama_selanjutnya;
                $nomor_ba     = $data['nomor_ba'];
                $tanggal      = $tanggalFix;
                $bulanRomawi  = $bulanRomawiFix;
                $tahun        = $tahunFix;
                $jenis_ba     = $jenis;
                $permintaan   = 'approval';
                $namaPeminta  = 'HomeMailer';
            }

            $stmtApprove->close();
        }

        if (isset($_GET['status'])) {
            $status_user = $_GET['status'];
        }
    } elseif ($jenis === 'mutasi') {
        //$nama_user = $_SESSION['nama'] ?? '';
        $nama_user = $nama;

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
            $stmtApprove->bind_param("i", $id);
            $stmtApprove->execute();
            $result = $stmtApprove->get_result();
            $data = $result->fetch_assoc();

            if ($data) {

                $nama_selanjutnya = '-';

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

                $row_id         = $id;
                $aktorListStr   = $nama_selanjutnya;
                $nomor_ba       = $data['nomor_ba'];
                $tanggal        = $tanggalFix;
                $bulanRomawi    = $bulanRomawiFix;
                $tahun          = $tahunFix;
                $jenis_ba       = $jenis;
                $permintaan     = 'approval';
                $namaPeminta    = 'HomeMailer';
            }

            $stmtApprove->close();
        }
        if (isset($_GET['status'])) {
            $status_user = $_GET['status'];
        }
    } elseif ($jenis === 'pemutihan') {
        $nama_user = $nama;

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
            $stmtApprove->bind_param("i", $id);
            $stmtApprove->execute();
            $result = $stmtApprove->get_result();
            $data = $result->fetch_assoc();

            if ($data) {
                $nama_selanjutnya = '-';
                $isHO = (isset($data['pt']) && trim($data['pt']) === 'PT.MSAL (HO)');

                if ($isHO) {
                    if ($nama_user === $data['pembuat']) {
                        $approval_col = 'approval_1';
                        $autograph_col = 'autograph_1';
                        $tanggal_col = 'tanggal_approve_1';
                        $approval_val = $data['approval_1'];
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
                        $approval_val = $data['approval_2'];
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
                        $approval_val = $data['approval_3'];
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
                        $approval_val = $data['approval_4'];
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
                        $approval_val = $data['approval_5'];
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
                        $approval_val = $data['approval_6'];
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
                        $approval_val = $data['approval_7'];
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
                        $approval_val = $data['approval_8'];
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
                        $approval_val = $data['approval_9'];
                        $autograph_val = $data['autograph_9'];
                        $nama_selanjutnya = '-';
                    }
                } else {
                    if ($nama_user === $data['pembuat_site']) {
                        $approval_col = 'approval_1';
                        $autograph_col = 'autograph_1';
                        $tanggal_col = 'tanggal_approve_1';
                        $approval_val = $data['approval_1'];
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
                        $approval_val = $data['approval_2'];
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
                        $approval_val = $data['approval_3'];
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
                        $approval_val = $data['approval_4'];
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
                        $approval_val = $data['approval_5'];
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
                        $approval_val = $data['approval_6'];
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
                        $approval_val = $data['approval_7'];
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
                        $approval_val = $data['approval_8'];
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
                        $approval_val = $data['approval_9'];
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
                        $approval_val = $data['approval_10'];
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
                        $approval_val = $data['approval_11'];
                        $autograph_val = $data['autograph_11'];
                        $nama_selanjutnya = '-';
                    }
                }

                if ($nama_selanjutnya === '') {
                    $nama_selanjutnya = '-';
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

                $row_id       = $id;
                $aktorListStr = $nama_selanjutnya;
                $nomor_ba     = $data['nomor_ba'];
                $tanggal      = $tanggalFix;
                $bulanRomawi  = $bulanRomawiFix;
                $tahun        = $tahunFix;
                $jenis_ba     = $jenis;
                $permintaan   = 'approval';
                $namaPeminta  = 'HomeMailer';
            }

            $stmtApprove->close();
        }

        if (isset($_GET['status'])) {
            $status_user = $_GET['status'];
        }
    } elseif ($jenis === 'st_asset') {
        //$nama_user = $_SESSION['nama'] ?? '';
        $nama_user = $nama;

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
            $stmtApprove->bind_param("i", $id);
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

                $row_id         = $id;
                $aktorListStr   = $nama_selanjutnya;
                $nomor_ba       = $data['nomor_ba'];
                $tanggal        = $tanggalFix;
                $bulanRomawi    = $bulanRomawiFix;
                $tahun          = $tahunFix;
                $jenis_ba       = $jenis;
                $permintaan     = 'approval';
                $namaPeminta    = 'HomeMailer';
            }

            $stmtApprove->close();
        }
        if (isset($_GET['status'])) {
            $status_user = $_GET['status'];
        }
    }

    // echo '<pre>';
    // echo "==== \$_POST ====\n";
    // print_r($_POST);
    // echo "==== \$_FILES ====\n";
    // print_r($_FILES);
    // print_r($nama_user);
    // echo '<br>';
    // print_r($approval_col);
    // echo '<br>';
    // print_r($autograph_col);
    // echo '<br>';
    // print_r($tanggal_col);
    // echo '<br>';
    // print_r($nama_selanjutnya);
    // echo '<br>';
    // echo '<br>';
    // print_r($row_id);
    // echo '<br>';
    // print_r($aktorListStr);
    // echo '<br>';
    // print_r($nomor_ba);
    // echo '<br>';
    // print_r($tanggal);
    // echo '<br>';
    // print_r($bulanRomawi);
    // echo '<br>';
    // print_r($tahun);
    // echo '<br>';
    // print_r($jenis_ba);
    // echo '<br>';
    // print_r($permintaan);
    // echo '<br>';
    // print_r($namaPeminta);
    // echo '<br>';
    // echo '</pre>';
    // exit;

    // Ambil data binary dari upload file (karena dikirim via FormData)
    $binaryData = '';
    if (isset($_FILES['signature']['tmp_name']) && is_uploaded_file($_FILES['signature']['tmp_name'])) {
        $binaryData = file_get_contents($_FILES['signature']['tmp_name']);
    }

    if (
        empty($id) || empty($approvalCol) || empty($autographCol) || empty($jenis) ||
        !$binaryData || strlen($binaryData) < 100
    ) {
        $_SESSION['success'] = false;
        $_SESSION['message'] = "Data tidak lengkap atau tanda tangan tidak valid.";
        // echo json_encode(["success" => false]);
        header("Location: approval.php?jenis_ba=" . urlencode($jenis));
        exit;
    }

    // Tentukan tabel berdasarkan jenis BA
    $jenis_lc = strtolower($jenis);
    if ($jenis_lc === 'kerusakan') {
        $table = 'berita_acara_kerusakan';
    } elseif ($jenis_lc === 'pengembalian') {
        $table = 'berita_acara_pengembalian_v2';
    } elseif ($jenis_lc === 'notebook') {
        $table = 'ba_serah_terima_notebook';
    } elseif ($jenis_lc === 'mutasi') {
        $table = 'berita_acara_mutasi';
    } elseif ($jenis_lc === 'st_asset') {
        $table = 'ba_serah_terima_asset';
    } elseif ($jenis_lc === 'pemutihan') {
        $table = 'berita_acara_pemutihan';
    } else {
        $_SESSION['success'] = false;
        $_SESSION['message'] = "Jenis BA tidak dikenal.";
        header("Location: approval.php?jenis_ba=" . urlencode($jenis));
        exit;
    }

    // Nama kolom tanggal berdasarkan kolom approval
    $tanggalCol = str_replace("approval", "tanggal_approve", $approvalCol);

    // Simpan BLOB langsung ke database
    $sql = "UPDATE $table 
            SET $autographCol = ?, $approvalCol = 1, $tanggalCol = NOW()
            WHERE id = ?";
    $stmt = $koneksi->prepare($sql);

    if (!$stmt) {
        $_SESSION['success'] = false;
        $_SESSION['message'] = "Gagal prepare statement: " . $koneksi->error;
        // echo json_encode(["success" => false]);
        header("Location: approval.php?jenis_ba=" . urlencode($jenis));
        exit;
    }

    $null = NULL;
    $stmt->bind_param("bi", $null, $id);
    $stmt->send_long_data(0, $binaryData);

    // =========================
    // TAMBAHAN UPDATE AKUN_AKSES
    // =========================
    $sqlUser = "UPDATE akun_akses SET autograph = ? WHERE nama = ?";
    $stmtUser = $koneksi->prepare($sqlUser);

    $null2 = NULL;
    $stmtUser->bind_param("bs", $null2, $nama);
    $stmtUser->send_long_data(0, $binaryData);

    $stmtUser->execute();
    $stmtUser->close();
    // exit;

    if ($stmt->execute()) {
        if ($aktorListStr != "-"):
?>
            <!DOCTYPE html>
            <html lang="en">

            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Document</title>
                <style>
                    @keyframes spin {
                        from {
                            transform: rotate(0deg);
                        }

                        to {
                            transform: rotate(360deg);
                        }
                    }
                </style>
            </head>

            <body style="background-color: rgb(2, 77, 190);">
                <!-- SPINNER LINGKARAN -->
                <div style="
                position: fixed;
                top: 0; left: 0;
                width: 100vw; height: 100vh;
                background: rgba(2, 77, 190, 1);
                display: flex;
                justify-content: center;
                align-items: center;
                z-index: 9999;
            ">
                    <div style="
                    width: 70px;
                    height: 70px;
                    border: 8px solid #ffffff50;
                    border-top-color: #fff;
                    border-radius: 50%;
                    animation: spin 1s linear infinite;
                "></div>
                </div>

                <form id="forwardToKirimEmail" action="proses_kirim_email.php" method="POST">

                    <input type="hidden" name="row_id_email" value="<?= htmlspecialchars($row_id) ?>">
                    <input type="hidden" name="aktorEmailHidden" value="<?= htmlspecialchars($aktorListStr) ?>">

                    <input type="hidden" name="data_nomor" value="<?= htmlspecialchars($nomor_ba) ?>">
                    <input type="hidden" name="data_tanggal" value="<?= htmlspecialchars($tanggal) ?>">
                    <input type="hidden" name="data_bulan_romawi" value="<?= htmlspecialchars($bulanRomawi) ?>">
                    <input type="hidden" name="data_tahun" value="<?= htmlspecialchars($tahun) ?>">

                    <input type="hidden" name="data_jenis_ba" value="<?= htmlspecialchars($jenis_ba) ?>">
                    <input type="hidden" name="data_permintaan" value="<?= htmlspecialchars($permintaan) ?>">
                    <input type="hidden" name="data_nama_peminta" value="<?= htmlspecialchars($namaPeminta) ?>">

                </form>

                <script>
                    let bar = document.getElementById("progressBar");
                    let width = 0;

                    let loader = setInterval(() => {
                        if (width >= 90) {
                            clearInterval(loader);
                        } else {
                            width += 5;
                            bar.style.width = width + "%";
                        }
                    }, 100);

                    document.getElementById("forwardToKirimEmail").submit();
                </script>
            </body>

            </html>



<?php
        else :
            $_SESSION['success'] = true;
            $_SESSION['message'] = "Berhasil menyimpan tanda tangan.";
        endif;
    } else {
        $_SESSION['success'] = false;
        $_SESSION['message'] = "Gagal menyimpan: " . $stmt->error;
    }

    $stmt->close();
    $koneksi->close();
    if ($aktorListStr == "-" || $aktorListStr == ""):
        header("Location: approval.php?jenis_ba=" . urlencode($jenis));
    endif;
    exit;
}
?>