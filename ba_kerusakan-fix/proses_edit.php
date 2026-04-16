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

    require_once '../koneksi.php';

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $_SESSION['message'] = "Invalid request method.";
        header("Location: ba_kerusakan.php?status=gagal");
        exit();
    }

    // Ambil nama pembuat dari session
    $nama_pembuat = isset($_SESSION['nama']) ? $_SESSION['nama'] : '-';

    // Ambil data dari form dengan fallback kosong
    $id                 = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $nomor_ba           = isset($_POST['nomor_ba']) ? $_POST['nomor_ba'] : '-'; 
    $nomor_ba           = str_pad($nomor_ba, 3, '0', STR_PAD_LEFT);
    $tanggal            = isset($_POST['tanggal']) ? $_POST['tanggal'] : '-';
    $jenis_perangkat    = isset($_POST['jenis_perangkat']) ? $_POST['jenis_perangkat'] : '-';
    $merek              = isset($_POST['merek']) ? $_POST['merek'] : '-';
    $no_po              = isset($_POST['nomor_po']) ? $_POST['nomor_po'] : '-';
    $id_pt              = isset($_POST['id_pt']) ? $_POST['id_pt'] : '-';

    // 1) TAMBAHKAN helper + mapping
    function getNamaKaryawanTest($koneksi, $posisi, $pt) {
        $nama = '-';

        $posisi = trim((string)$posisi);
        $pt     = trim((string)$pt);
        if ($pt === '' || $pt === '-') return $nama;

        // support pt single / multi-pt (list) di kolom pt
        $sql = "SELECT nama
                FROM data_karyawan_test
                WHERE posisi = ?
                AND (
                        TRIM(pt) = ?
                        OR FIND_IN_SET(?, REPLACE(REPLACE(REPLACE(pt,'; ',','), ';', ','), ', ', ',')) > 0
                    )
                LIMIT 1";

        $stmt = $koneksi->prepare($sql);
        if (!$stmt) return $nama;

        $stmt->bind_param("sss", $posisi, $pt, $pt);
        if (!$stmt->execute()) { $stmt->close(); return $nama; }

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
        $jabatanFinal = '-';

        $nama = trim((string)$nama);
        $pt   = trim((string)$pt);

        if ($nama === '' || $nama === '-') return $jabatanFinal;
        if ($pt === '' || $pt === '-') return $jabatanFinal;

        // HO -> data_karyawan (jabatan + departemen)
        if ($pt === 'PT.MSAL (HO)') {
            $sql = "SELECT jabatan, departemen
                    FROM data_karyawan
                    WHERE nama = ?
                    LIMIT 1";
            $stmt = $koneksi->prepare($sql);
            if (!$stmt) return $jabatanFinal;

            $stmt->bind_param("s", $nama);
            if (!$stmt->execute()) {
                $stmt->close();
                return $jabatanFinal;
            }

            $jab = '';
            $dep = '';
            $stmt->bind_result($jab, $dep);

            if ($stmt->fetch()) {
                $jab = trim((string)$jab);
                $dep = trim((string)$dep);

                if ($jab !== '' && $dep !== '') {
                    $jabatanFinal = $jab . ' ' . $dep;
                } elseif ($jab !== '') {
                    $jabatanFinal = $jab;
                } elseif ($dep !== '') {
                    $jabatanFinal = $dep;
                }
            }

            $stmt->close();
            return $jabatanFinal;
        }

        // Non-HO -> data_karyawan_test.posisi (support multi PT)
        $sql = "SELECT posisi
                FROM data_karyawan_test
                WHERE nama = ?
                AND (
                        TRIM(pt) = ?
                        OR FIND_IN_SET(?, REPLACE(REPLACE(REPLACE(pt,'; ',','), ';', ','), ', ', ',')) > 0
                    )
                LIMIT 1";
        $stmt = $koneksi->prepare($sql);
        if (!$stmt) return $jabatanFinal;

        $stmt->bind_param("sss", $nama, $pt, $pt);
        if (!$stmt->execute()) {
            $stmt->close();
            return $jabatanFinal;
        }

        $posisi = '';
        $stmt->bind_result($posisi);

        if ($stmt->fetch()) {
            $posisi = trim((string)$posisi);
            if ($posisi !== '') $jabatanFinal = $posisi;
        }

        $stmt->close();
        return $jabatanFinal;
    }

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

    // 2) BAGIAN PT + id_pt (GANTI yang lama)
    if (isset($_POST['pt']) && $_POST['pt'] !== '') {
        $pt = $_POST['pt'];
    } elseif ($nama_pembuat === 'Rizki Sunandar') {
        $pt = 'PT.MSAL (HO)';
    } else {
        $pt = '-';
    }
    $pt = trim((string)$pt);

    // id_pt untuk SEMUA PT
    $id_pt = isset($pt_map[$pt]) ? (int)$pt_map[$pt] : 0;

    $user_form          = isset($_POST['user']) ? $_POST['user'] : '-';
    $deskripsi          = isset($_POST['deskripsi']) ? $_POST['deskripsi'] : '-';
    $sn                 = isset($_POST['sn']) ? $_POST['sn'] : '-';
    $tahun_perolehan    = isset($_POST['tahun_perolehan']) ? $_POST['tahun_perolehan'] : '-';
    $penyebab_kerusakan = isset($_POST['penyebab_kerusakan']) ? $_POST['penyebab_kerusakan'] : '-';
    $rekomendasi_mis    = isset($_POST['rekomendasi_mis']) ? $_POST['rekomendasi_mis'] : '-';
    $kategori_kerusakan = isset($_POST['kategori_kerusakan']) && $_POST['kategori_kerusakan'] !== ''
        ? (int)$_POST['kategori_kerusakan']
        : NULL;
    $keterangan_dll     = isset($_POST['keterangan_dll']) ? trim($_POST['keterangan_dll']) : '-';

    $peminjam           = isset($_POST['peminjam']) ? $_POST['peminjam'] : '-';
    $lokasi_input       = isset($_POST['lokasi']) ? $_POST['lokasi'] : '';
    $atasan_peminjam    = isset($_POST['atasan_peminjam']) ? $_POST['atasan_peminjam'] : '-';

    // 3) BAGIAN HRD/GA (Staf GA) — HO tetap, selain HO ikut pola SITE (GANTI blok if/elseif ini)
    if ($pt === 'PT.MSAL (HO)'):
        $query              = $koneksi->query("SELECT nama FROM data_karyawan WHERE jabatan = 'Dept. Head' AND departemen = 'HRO' LIMIT 1");
        $data               = $query->fetch_assoc();
        $dept_head_HR       = $data ? $data['nama'] : '-';

    elseif ($pt !== '' && $pt !== '-'):
        // semua PT non-HO mengikuti pola SITE
        $dept_head_HR       = getNamaKaryawanTest($koneksi, 'Staf GA', $pt);
    else:
        $dept_head_HR       = '-';
    endif;

    $alasan_perubahan   = isset($_POST['alasan_perubahan']) ? $_POST['alasan_perubahan'] : '-';

    // 4) BAGIAN KTU & GM — HO tetap, selain HO mengikuti pola SITE (GANTI blok elseif SITE + elseif lainnya)
    if ($pt === 'PT.MSAL (HO)') {
        // jika dept head HR sebagai peminjam atau atasan_peminjam, kosongkan
        if ($dept_head_HR === $peminjam || $dept_head_HR === $atasan_peminjam) {
            $dept_head_HR = '-';
        }

        // --- Approval 1 (Rizki Sunandar) ---
        if ($peminjam === 'Rizki Sunandar') {
            $pembuat = "-";
        }

        if ($peminjam !== 'Rizki Sunandar') {
            $pembuat = "Rizki Sunandar";
        }

        // --- Approval 2 (Tedy Paronto) dari peminjam ---
        if ($peminjam === 'Tedy Paronto') {
            $penyetujui = "-";
            $atasan_peminjam = "-";
        }

        if ($peminjam !== 'Tedy Paronto') {
            $penyetujui = "Tedy Paronto";
        }

        // --- Approval 2 dari atasan ---
        if ($peminjam !== 'Tedy Paronto') {
            if ($atasan_peminjam === 'Tedy Paronto') {
                $penyetujui = "-";
            }

            if ($atasan_peminjam !== 'Tedy Paronto') {
                $penyetujui = "Tedy Paronto";
            }
        }
    } else {
        // semua PT non-HO mengikuti pola SITE
        $penyetujui = getNamaKaryawanTest($koneksi, 'KTU', $pt);
        $pembuat    = getNamaKaryawanTest($koneksi, 'GM',  $pt);
    }

    if ($lokasi_input !== '-' || $lokasi_input !== '') {
        // Format lokasi
        if (preg_match('/^LT\.(\d+)/i', $lokasi_input, $match)) {
            $lokasi = 'Lantai ' . $match[1];
        } else {
            $lokasi = $lokasi_input;
        }
    }

    // =====================================================
    // AMBIL JABATAN AKTOR BARU (berdasarkan nama final + PT)
    // =====================================================

    $jabatan_pembuat           = getJabatanAktor($koneksi, $pembuat, $pt);
    $jabatan_penyetujui        = getJabatanAktor($koneksi, $penyetujui, $pt);
    $jabatan_peminjam          = getJabatanAktor($koneksi, $peminjam, $pt);
    $jabatan_atasan_peminjam   = getJabatanAktor($koneksi, $atasan_peminjam, $pt);
    $jabatan_diketahui         = getJabatanAktor($koneksi, $dept_head_HR, $pt);

    // === DATA LAMA UNTUK HISTORI ===
    $old_stmt = $koneksi->prepare("SELECT 
        id, tanggal, nomor_ba, jenis_perangkat, merek, no_po, user, deskripsi, sn, tahun_perolehan,
        penyebab_kerusakan, kategori_kerusakan_id, keterangan_dll, rekomendasi_mis,
        pt, id_pt, lokasi, nama_pembuat,
        pembuat, jabatan_pembuat,
        penyetujui, jabatan_penyetujui,
        peminjam, jabatan_peminjam,
        atasan_peminjam, jabatan_atasan_peminjam,
        diketahui, jabatan_diketahui,
        approval_1, approval_2, approval_3, approval_4, approval_5,
        autograph_1, autograph_2, autograph_3, autograph_4, autograph_5,
        tanggal_approve_1, tanggal_approve_2, tanggal_approve_3, tanggal_approve_4, tanggal_approve_5,
        created_at
    FROM berita_acara_kerusakan WHERE id = ?");
    $old_stmt->bind_param("i", $id);
    $old_stmt->execute();
    $old_result = $old_stmt->get_result();
    $old_data = $old_result->fetch_assoc();
    $old_stmt->close();

    // --- simpan ke variabel _lama dari $old_data (aman: cek kedua kemungkinan key)
    $pembuat_lama = isset($old_data['pembuat']) ? trim($old_data['pembuat']) : (isset($old_data['pembuat']) ? trim($old_data['pembuat']) : '');
    $penyetujui_lama = isset($old_data['penyetujui']) ? trim($old_data['penyetujui']) : (isset($old_data['penyetujui']) ? trim($old_data['penyetujui']) : '');
    $peminjam_lama = isset($old_data['peminjam']) ? trim($old_data['peminjam']) : (isset($old_data['peminjam']) ? trim($old_data['peminjam']) : '');
    $atasan_peminjam_lama = isset($old_data['atasan_peminjam']) ? trim($old_data['atasan_peminjam']) : (isset($old_data['atasan_peminjam']) ? trim($old_data['atasan_peminjam']) : '');
    $diketahui_lama = isset($old_data['diketahui']) ? trim($old_data['diketahui']) : (isset($old_data['diketahui']) ? trim($old_data['diketahui']) : '');
    $lokasi_lama = isset($old_data['lokasi']) ? trim($old_data['lokasi']) : (isset($old_data['lokasi']) ? trim($old_data['lokasi']) : '');


    // =============================================
    // GATHER DATA LAMA DAN BARU
    // =============================================

    $old_data_array = [
        'id' => $old_data['id'],
        'tanggal' => $old_data['tanggal'],
        'nomor_ba' => $old_data['nomor_ba'],
        'jenis_perangkat' => $old_data['jenis_perangkat'],
        'merek' => $old_data['merek'],
        'no_po' => $old_data['no_po'],
        'user' => $old_data['user'],
        'deskripsi' => $old_data['deskripsi'],
        'sn' => $old_data['sn'],
        'tahun_perolehan' => $old_data['tahun_perolehan'],
        'penyebab_kerusakan' => $old_data['penyebab_kerusakan'],
        'kategori_kerusakan_id' => $old_data['kategori_kerusakan_id'],
        'keterangan_dll' => $old_data['keterangan_dll'],
        'rekomendasi_mis' => $old_data['rekomendasi_mis'],
        'pt' => $old_data['pt'],
        'id_pt' => $old_data['id_pt'],
        'lokasi' => $old_data['lokasi'],
        'nama_pembuat' => $old_data['nama_pembuat'],
        'pembuat' => $old_data['pembuat'],
        'jabatan_pembuat' => isset($old_data['jabatan_pembuat']) ? $old_data['jabatan_pembuat'] : '-',
        'penyetujui' => $old_data['penyetujui'],
        'jabatan_penyetujui' => isset($old_data['jabatan_penyetujui']) ? $old_data['jabatan_penyetujui'] : '-',
        'peminjam' => $old_data['peminjam'],
        'jabatan_peminjam' => isset($old_data['jabatan_peminjam']) ? $old_data['jabatan_peminjam'] : '-',
        'atasan_peminjam' => $old_data['atasan_peminjam'],
        'jabatan_atasan_peminjam' => isset($old_data['jabatan_atasan_peminjam']) ? $old_data['jabatan_atasan_peminjam'] : '-',
        'diketahui' => $old_data['diketahui'],
        'jabatan_diketahui' => isset($old_data['jabatan_diketahui']) ? $old_data['jabatan_diketahui'] : '-',
        'approval_1' => $old_data['approval_1'],
        'approval_2' => $old_data['approval_2'],
        'approval_3' => $old_data['approval_3'],
        'approval_4' => $old_data['approval_4'],
        'approval_5' => $old_data['approval_5'],
        'autograph_1' => $old_data['autograph_1'],
        'autograph_2' => $old_data['autograph_2'],
        'autograph_3' => $old_data['autograph_3'],
        'autograph_4' => $old_data['autograph_4'],
        'autograph_5' => $old_data['autograph_5'],
        'tanggal_approve_1' => $old_data['tanggal_approve_1'],
        'tanggal_approve_2' => $old_data['tanggal_approve_2'],
        'tanggal_approve_3' => $old_data['tanggal_approve_3'],
        'tanggal_approve_4' => $old_data['tanggal_approve_4'],
        'tanggal_approve_5' => $old_data['tanggal_approve_5'],
        'created_at' => $old_data['created_at']
    ];

    $new_data_array = [
        'id'                        => $id,
        'tanggal'                   => $tanggal,
        'nomor_ba'                  => $nomor_ba,
        'jenis_perangkat'           => $jenis_perangkat,
        'merek'                     => $merek,
        'no_po'                     => $no_po,
        'user'                      => $user_form,
        'deskripsi'                 => $deskripsi,
        'sn'                        => $sn,
        'tahun_perolehan'           => $tahun_perolehan,
        'penyebab_kerusakan'        => $penyebab_kerusakan,
        'kategori_kerusakan_id'     => $kategori_kerusakan,
        'keterangan_dll'            => $keterangan_dll,
        'rekomendasi_mis'           => $rekomendasi_mis,
        'pt'                        => $pt,
        'id_pt'                     => $id_pt,
        'lokasi'                    => $lokasi,
        'nama_pembuat'              => $old_data['nama_pembuat'],
        'pembuat'                   => $pembuat,
        'jabatan_pembuat'           => $jabatan_pembuat,
        'penyetujui'                => $penyetujui,
        'jabatan_penyetujui'        => $jabatan_penyetujui,
        'peminjam'                  => $peminjam,
        'jabatan_peminjam'          => $jabatan_peminjam,
        'atasan_peminjam'           => $atasan_peminjam,
        'jabatan_atasan_peminjam'   => $jabatan_atasan_peminjam,
        'diketahui'                 => $dept_head_HR,
        'jabatan_diketahui'         => $jabatan_diketahui,
        'approval_1'                => $old_data['approval_1'],
        'approval_2'                => $old_data['approval_2'],
        'approval_3'                => $old_data['approval_3'],
        'approval_4'                => $old_data['approval_4'],
        'approval_5'                => $old_data['approval_5'],
        'autograph_1'               => $old_data['autograph_1'],
        'autograph_2'               => $old_data['autograph_2'],
        'autograph_3'               => $old_data['autograph_3'],
        'autograph_4'               => $old_data['autograph_4'],
        'autograph_5'               => $old_data['autograph_5'],
        'tanggal_approve_1'         => $old_data['tanggal_approve_1'],
        'tanggal_approve_2'         => $old_data['tanggal_approve_2'],
        'tanggal_approve_3'         => $old_data['tanggal_approve_3'],
        'tanggal_approve_4'         => $old_data['tanggal_approve_4'],
        'tanggal_approve_5'         => $old_data['tanggal_approve_5'],
        'created_at'                => $old_data['created_at']
    ];

    // =============================================
    // CEK PERUBAHAN APPROVER DAN RESET OTOMATIS
    // =============================================

    // mapping kolom approval berdasarkan urutan
    $approval_map = [
        1 => 'pembuat',
        2 => 'penyetujui',
        3 => 'peminjam',
        4 => 'atasan_peminjam',
        5 => 'diketahui'
    ];

    foreach ($approval_map as $num => $field) {
        // ambil nama kolom terkait
        $approval_col = "approval_{$num}";
        $autograph_col = "autograph_{$num}";
        $tanggal_col = "tanggal_approve_{$num}";

        // bandingkan data lama dan baru
        if (isset($old_data[$field]) && isset($new_data_array[$field]) && $old_data[$field] !== $new_data_array[$field]) {
            // kalau beda → reset
            $new_data_array[$approval_col] = 0;
            $new_data_array[$autograph_col] = NULL;
            $new_data_array[$tanggal_col] = NULL;
        } else {
            // kalau sama → pertahankan dari data lama
            $new_data_array[$approval_col] = $old_data[$approval_col];
            $new_data_array[$autograph_col] = $old_data[$autograph_col];
            $new_data_array[$tanggal_col] = $old_data[$tanggal_col];
        }
    }

    // echo "<pre>";
    // print_r($old_data_array);
    // echo "</pre>";

    // echo "<pre>";
    // print_r($new_data_array);
    // echo "</pre>";

    // =================================================================
    // PROSES AMBIL DATA UNTUK HISTORI
    // array approver baru
    $approver_baru = [
        'pembuat' => trim($pembuat),
        'penyetujui' => trim($penyetujui),
        'peminjam' => trim($peminjam),
        'atasan_peminjam' => trim($atasan_peminjam),
        'diketahui' => trim($dept_head_HR),
        'pt' => trim($pt),
        'lokasi' => trim($lokasi)
    ];

    // array approver lama untuk loop
    $lama_map = [
        'pembuat'               => $pembuat_lama,
        'penyetujui'            => $penyetujui_lama,
        'peminjam'              => $peminjam_lama,
        'atasan_peminjam'       => $atasan_peminjam_lama,
        'diketahui'             => $diketahui_lama,
        'pt'                    => isset($old_data['pt']) ? $old_data['pt'] : '-',
        'lokasi'                => $lokasi_lama
    ];

    $perbedaan_approver = [];

    foreach ($approver_baru as $key => $val_baru) {
        $val_lama = isset($lama_map[$key]) ? $lama_map[$key] : '';
        if ($val_lama !== $val_baru) {
            $perbedaan_approver[$key] = [
                'lama' => ($val_lama === '' ? '(-)' : $val_lama),
                'baru' => ($val_baru === '' ? '(-)' : $val_baru)
            ];
        }
    }

    $new_data_full = [
        'nomor_ba'             => $nomor_ba,
        'tanggal'              => $tanggal,
        'jenis_perangkat'      => $jenis_perangkat,
        'merek'                => $merek,
        'no_po'                => $no_po,
        'user'                 => $user_form,
        'deskripsi'            => $deskripsi,
        'sn'                   => $sn,
        'tahun_perolehan'      => $tahun_perolehan,
        'penyebab_kerusakan'   => $penyebab_kerusakan,
        'rekomendasi_mis'      => $rekomendasi_mis,
        'kategori_kerusakan_id' => $kategori_kerusakan,
        'keterangan_dll'       => $keterangan_dll
    ];

    $old_data_full = [
        'nomor_ba'             => isset($old_data['nomor_ba']) ? $old_data['nomor_ba'] : '-',
        'tanggal'              => isset($old_data['tanggal']) ? $old_data['tanggal'] : '-',
        'jenis_perangkat'      => isset($old_data['jenis_perangkat']) ? $old_data['jenis_perangkat'] : '-',
        'merek'                => isset($old_data['merek']) ? $old_data['merek'] : '-',
        'no_po'                => isset($old_data['no_po']) ? $old_data['no_po'] : '-',
        'user'                 => isset($old_data['user']) ? $old_data['user'] : '-',
        'deskripsi'            => isset($old_data['deskripsi']) ? $old_data['deskripsi'] : '-',
        'sn'                   => isset($old_data['sn']) ? $old_data['sn'] : '-',
        'tahun_perolehan'      => isset($old_data['tahun_perolehan']) ? $old_data['tahun_perolehan'] : '-',
        'penyebab_kerusakan'   => isset($old_data['penyebab_kerusakan']) ? $old_data['penyebab_kerusakan'] : '-',
        'rekomendasi_mis'      => isset($old_data['rekomendasi_mis']) ? $old_data['rekomendasi_mis'] : '-',
        'kategori_kerusakan_id' => isset($old_data['kategori_kerusakan_id']) ? $old_data['kategori_kerusakan_id'] : '-',
        'keterangan_dll'       => isset($old_data['keterangan_dll']) ? $old_data['keterangan_dll'] : '-'
    ];

    $perbedaan_data = [];

    foreach ($new_data_full as $key => $val_baru) {
        $val_lama = isset($old_data_full[$key]) ? $old_data_full[$key] : '';
        if ($val_lama !== $val_baru) {
            $perbedaan_data[$key] = [
                'lama' => ($val_lama === '' ? '(-)' : $val_lama),
                'baru' => ($val_baru === '' ? '(-)' : $val_baru)
            ];
        }
    }


    // echo "<pre>";
    // print_r($id);
    // echo "</pre>";
    // echo "<pre>";
    // print_r($new_data_full);
    // echo "</pre>";
    // echo "<pre>";
    // print_r($old_data_full);
    // echo "</pre>";
    // echo "<pre>";
    // print_r($perbedaan_data);
    // echo "</pre>";

    // echo "<pre>=== DATA YANG BERBEDA (hanya tampilkan yang berubah) ===\n";
    // foreach ($perbedaan_data as $key => $data) {
    //     echo strtoupper($key) . ": {$data['lama']} => {$data['baru']}\n";
    // }
    // echo "</pre>";

    // echo "<pre>";
    // print_r($approver_baru);
    // echo "</pre>";
    // echo "<pre>";
    // print_r($lama_map);
    // echo "</pre>";
    // echo "<pre>";
    // print_r($perbedaan_approver);
    // echo "</pre>";

    // echo "<pre>=== DATA APPROVER YANG BERBEDA (hanya tampilkan yang berubah) ===\n";
    // foreach ($perbedaan_approver as $key => $data) {
    //     echo strtoupper($key) . ": {$data['lama']} => {$data['baru']}\n";
    // }
    // echo "</pre>";

    // =================================================================

    //}
    // =============================
    // CATAT HISTORI PERUBAHAN
    // =============================

    // Ambil nama kategori kerusakan dari tabel categories_broken
    $kategori_kerusakan_nama = '-';
    if (!is_null($kategori_kerusakan)) {
        $stmt_kat = $koneksi->prepare("SELECT nama FROM categories_broken WHERE id = ?");
        $stmt_kat->bind_param("i", $kategori_kerusakan);
        $stmt_kat->execute();
        $result_kat = $stmt_kat->get_result();
        if ($row_kat = $result_kat->fetch_assoc()) {
            $kategori_kerusakan_nama = $row_kat['nama'];
        }
        $stmt_kat->close();
    }

    // Ambil nama kategori lama untuk histori
    $old_kategori_kerusakan_nama = null;
    if (!empty($old_data['kategori_kerusakan_id'])) {
        $stmt_old_kat = $koneksi->prepare("SELECT nama FROM categories_broken WHERE id = ?");
        $stmt_old_kat->bind_param("i", $old_data['kategori_kerusakan_id']);
        $stmt_old_kat->execute();
        $res_old_kat = $stmt_old_kat->get_result();
        $row_old_kat = $res_old_kat->fetch_assoc();
        $old_kategori_kerusakan_nama = $row_old_kat ? $row_old_kat['nama'] : null;
        $stmt_old_kat->close();
    }

    $old_data['kategori_kerusakan'] = $old_kategori_kerusakan_nama;

    $new_data = [
        'nomor_ba' => $nomor_ba,
        'tanggal' => $tanggal,
        'jenis_perangkat' => $jenis_perangkat,
        'merek' => $merek,
        'no_po' => $no_po,
        'user' => $user_form,
        'deskripsi' => $deskripsi,
        'sn' => $sn,
        'tahun_perolehan'   => $tahun_perolehan,
        'penyebab_kerusakan'=> $penyebab_kerusakan,
        'rekomendasi_mis'   => $rekomendasi_mis,
        'kategori_kerusakan'=> $kategori_kerusakan_nama,
        'keterangan_dll'    => $keterangan_dll,
        'peminjam'          => $peminjam,
        'atasan_peminjam'   => $atasan_peminjam,
        'pembuat'           => $pembuat,
        'lokasi'            => $lokasi,
        'diketahui'         => $dept_head_HR,
        'penyetujui'        => $penyetujui
    ];

    $perubahan = [];

    foreach ($new_data as $field => $new_value) {
        $old_value = isset($old_data[$field]) ? $old_data[$field] : '-';
        if ($old_value != $new_value) {
            $perubahan[] = ucfirst(str_replace('_', ' ', $field)) . " : {$old_value} diubah ke {$new_value}";
        }
    }

    //==============================================================================
    // PENDING DATA EDIT KARENA DATA SUDAH ADA APPROVAL
    //==============================================================================
    $ada_approval = false;
    for ($i = 1; $i <= 5; $i++) {
        if (isset($old_data["approval_{$i}"]) && $old_data["approval_{$i}"] == 1) {
            $ada_approval = true;
            break;
        }
    }
    // echo "<pre>";
    // print_r($old_data['kategori_kerusakan']);
    // echo "</pre>";
    // echo "<pre>";
    // print_r($new_data['kategori_kerusakan']);
    // echo "</pre>";
    // echo "<pre>";
    // print_r($perubahan);
    // echo "</pre>";
    // echo "<pre>";
    // print_r($ada_approval);
    // echo "</pre>";

    if (!empty($perubahan) && $ada_approval) {
        $debug_1 = true;
        // echo "<pre>";
        // print_r($debug_1);
        // echo "</pre>";
        
        $histori_text = implode("; ", $perubahan);
        $nama_pembuat = isset($_SESSION['nama']) ? $_SESSION['nama'] : 'Tidak diketahui';
        // echo "<pre>";
        // print_r($histori_text);
        // echo "</pre>";

        // =============================
        // HAPUS DATA PENDING JIKA ADA
        // =============================
        $cek_pending = $koneksi->prepare("SELECT id FROM historikal_edit_ba 
            WHERE nama_ba = 'kerusakan' AND id_ba = ? AND pending_status = '1'");
        $cek_pending->bind_param("i", $id);
        $cek_pending->execute();
        $result_pending = $cek_pending->get_result();


        // echo "<pre>";
        // print_r($result_pending);
        // echo "</pre>";
        

        if ($result_pending->num_rows > 0) {
            $hapus_pending = $koneksi->prepare("DELETE FROM historikal_edit_ba 
                WHERE nama_ba = 'kerusakan' AND id_ba = ? AND pending_status = '1'");
            $hapus_pending->bind_param("i", $id);
            $hapus_pending->execute();
            $hapus_pending->close();
        }
        
        $cek_pending->close();
        

        // =============================
        // SIMPAN HISTORI BARU
        // =============================

        $insert_histori = $koneksi->prepare("INSERT INTO historikal_edit_ba 
            (id_ba, nama_ba, pt, histori_edit, pengedit, tanggal_edit, pending_status) 
            VALUES (?, 'kerusakan', ?, ?, ?, NOW(), 1)");
        $insert_histori->bind_param("isss", $id, $pt, $histori_text, $nama_pembuat);
        $insert_histori->execute();
        $insert_histori->close();

        // =============================
        // SIMPAN DATA LAMA & BARU KE history_n_temp_ba_kerusakan (ADA APPROVAL)
        // =============================

        $pending_approver = null;
        if ($pt == 'PT.MSAL (HO)') {
            $pending_approver = 'Tedy Paronto';
        } 

        else {
            $pending_approver = getNamaKaryawanTest($koneksi, 'KTU', $pt);
        }

        // echo "<pre>";
        // print_r($pending_approver);
        // echo "</pre>";
        

        // =============================
        // HAPUS DATA HISTORY YANG SAMA JIKA ADA
        // =============================

        $cek_history = $koneksi->prepare("
            SELECT id FROM history_n_temp_ba_kerusakan 
            WHERE id_ba = ? AND pending_status = 1 AND (status = 0 OR status = 1)
        ");
        $cek_history->bind_param("i", $id);
        $cek_history->execute();
        $result_history = $cek_history->get_result();

        // echo "<pre>";
        // print_r($result_history);
        // echo "</pre>";
        

        if ($result_history->num_rows > 0) {
            $hapus_history = $koneksi->prepare("
                DELETE FROM history_n_temp_ba_kerusakan 
                WHERE id_ba = ? AND pending_status = 1 AND (status = 0 OR status = 1)
            ");
            $hapus_history->bind_param("i", $id);
            $hapus_history->execute();
            $hapus_history->close();
        }

        $cek_history->close();



        // --- OLD DATA (status = 0, pending_status = 1) ---


        $insert_old = $koneksi->prepare("
            INSERT INTO history_n_temp_ba_kerusakan (
                id_ba, status, pending_status, pending_approver, alasan_edit, tanggal, nomor_ba, jenis_perangkat, merek, no_po, user, deskripsi, sn,
                tahun_perolehan, penyebab_kerusakan, kategori_kerusakan_id, keterangan_dll, rekomendasi_mis, pt, id_pt, lokasi,
                nama_pembuat,
                pembuat, jabatan_pembuat,
                penyetujui, jabatan_penyetujui,
                peminjam, jabatan_peminjam,
                atasan_peminjam, jabatan_atasan_peminjam,
                diketahui, jabatan_diketahui,
                approval_1, approval_2, approval_3, approval_4, approval_5,
                autograph_1, autograph_2, autograph_3, autograph_4, autograph_5,
                tanggal_approve_1, tanggal_approve_2, tanggal_approve_3, tanggal_approve_4, tanggal_approve_5,
                file_created
            ) VALUES (
                ?, 0, 1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
            )
        ");
        $insert_old->bind_param(
            "issssssssssssisssissssssssssssiiiiisssssssssss",
            $old_data_array['id'],
        $pending_approver,
        $alasan_perubahan,
        $old_data_array['tanggal'],
        $old_data_array['nomor_ba'],
        $old_data_array['jenis_perangkat'],
        $old_data_array['merek'],
        $old_data_array['no_po'],
        $old_data_array['user'],
        $old_data_array['deskripsi'],
        $old_data_array['sn'],
        $old_data_array['tahun_perolehan'],
        $old_data_array['penyebab_kerusakan'],
        $old_data_array['kategori_kerusakan_id'],
        $old_data_array['keterangan_dll'],
        $old_data_array['rekomendasi_mis'],
        $old_data_array['pt'],
        $old_data_array['id_pt'],
        $old_data_array['lokasi'],
        $old_data_array['nama_pembuat'],
        $old_data_array['pembuat'],
        $old_data_array['jabatan_pembuat'],
        $old_data_array['penyetujui'],
        $old_data_array['jabatan_penyetujui'],
        $old_data_array['peminjam'],
        $old_data_array['jabatan_peminjam'],
        $old_data_array['atasan_peminjam'],
        $old_data_array['jabatan_atasan_peminjam'],
        $old_data_array['diketahui'],
        $old_data_array['jabatan_diketahui'],
        $old_data_array['approval_1'],
        $old_data_array['approval_2'],
        $old_data_array['approval_3'],
        $old_data_array['approval_4'],
        $old_data_array['approval_5'],
        $old_data_array['autograph_1'],
        $old_data_array['autograph_2'],
        $old_data_array['autograph_3'],
        $old_data_array['autograph_4'],
        $old_data_array['autograph_5'],
        $old_data_array['tanggal_approve_1'],
        $old_data_array['tanggal_approve_2'],
        $old_data_array['tanggal_approve_3'],
        $old_data_array['tanggal_approve_4'],
        $old_data_array['tanggal_approve_5'],
        $old_data_array['created_at']
        );
        $insert_old->execute();
        $insert_old->close();

        // --- NEW DATA (status = 1, pending_status = 1) ---
        
        $insert_new = $koneksi->prepare("
            INSERT INTO history_n_temp_ba_kerusakan (
                id_ba, status, pending_status, pending_approver, alasan_edit, tanggal, nomor_ba, jenis_perangkat, merek, no_po, user, deskripsi, sn,
                tahun_perolehan, penyebab_kerusakan, kategori_kerusakan_id, keterangan_dll, rekomendasi_mis, pt, id_pt, lokasi,
                nama_pembuat,
                pembuat, jabatan_pembuat,
                penyetujui, jabatan_penyetujui,
                peminjam, jabatan_peminjam,
                atasan_peminjam, jabatan_atasan_peminjam,
                diketahui, jabatan_diketahui,
                approval_1, approval_2, approval_3, approval_4, approval_5,
                autograph_1, autograph_2, autograph_3, autograph_4, autograph_5,
                tanggal_approve_1, tanggal_approve_2, tanggal_approve_3, tanggal_approve_4, tanggal_approve_5,
                file_created
            ) VALUES (
                ?, 1, 1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
            )
        ");
        $insert_new->bind_param(
            "issssssssssssisssissssssssssssiiiiisssssssssss",
            $new_data_array['id'],
            $pending_approver,
            $alasan_perubahan,
            $new_data_array['tanggal'],
            $new_data_array['nomor_ba'],
            $new_data_array['jenis_perangkat'],
            $new_data_array['merek'],
            $new_data_array['no_po'],
            $new_data_array['user'],
            $new_data_array['deskripsi'],
            $new_data_array['sn'],
            $new_data_array['tahun_perolehan'],
            $new_data_array['penyebab_kerusakan'],
            $new_data_array['kategori_kerusakan_id'],
            $new_data_array['keterangan_dll'],
            $new_data_array['rekomendasi_mis'],
            $new_data_array['pt'],
            $new_data_array['id_pt'],
            $new_data_array['lokasi'],
            $new_data_array['nama_pembuat'],
            $new_data_array['pembuat'],
            $new_data_array['jabatan_pembuat'],
            $new_data_array['penyetujui'],
            $new_data_array['jabatan_penyetujui'],
            $new_data_array['peminjam'],
            $new_data_array['jabatan_peminjam'],
            $new_data_array['atasan_peminjam'],
            $new_data_array['jabatan_atasan_peminjam'],
            $new_data_array['diketahui'],
            $new_data_array['jabatan_diketahui'],
            $new_data_array['approval_1'],
            $new_data_array['approval_2'],
            $new_data_array['approval_3'],
            $new_data_array['approval_4'],
            $new_data_array['approval_5'],
            $new_data_array['autograph_1'],
            $new_data_array['autograph_2'],
            $new_data_array['autograph_3'],
            $new_data_array['autograph_4'],
            $new_data_array['autograph_5'],
            $new_data_array['tanggal_approve_1'],
            $new_data_array['tanggal_approve_2'],
            $new_data_array['tanggal_approve_3'],
            $new_data_array['tanggal_approve_4'],
            $new_data_array['tanggal_approve_5'],
            $new_data_array['created_at']
        );
        $insert_new->execute();
        $insert_new->close();
    }
    //==============================================================================
    // echo "<pre>";
    // print_r($debug_1);
    // echo "</pre>";
    // $debug_1 = false;


    // Cek apakah semua approval_x bernilai 0
    $semua_approval_nol = true;
    // echo "<pre>";
    // print_r($semua_approval_nol);
    // echo "</pre>";

    for ($i = 1; $i <= 5; $i++) {
        if (!isset($old_data["approval_{$i}"]) || $old_data["approval_{$i}"] != 0) {
            $semua_approval_nol = false;
            break;
        }
    }
    // echo "<pre>";
    // print_r($semua_approval_nol);
    // echo "</pre>";
    // exit;
    if (!empty($perubahan) && $semua_approval_nol) {
        $histori_text = implode("; ", $perubahan);
        $nama_pembuat = isset($_SESSION['nama']) ? $_SESSION['nama'] : 'Tidak diketahui';

        // =============================
        // SIMPAN HISTORI BARU (approval semua 0)
        // =============================
        $insert_histori = $koneksi->prepare("INSERT INTO historikal_edit_ba 
            (id_ba, nama_ba, pt, histori_edit, pengedit, tanggal_edit, pending_status) 
            VALUES (?, 'kerusakan', ?, ?, ?, NOW(), 0)");
        $insert_histori->bind_param("isss", $id, $pt, $histori_text, $nama_pembuat);
        $insert_histori->execute();
        $insert_histori->close();

        // =============================
        // SIMPAN DATA LAMA & BARU KE history_n_temp_ba_kerusakan
        // =============================

        $pending_approver = "";

        // --- OLD DATA (status = 0) ---
        $insert_old = $koneksi->prepare("
            INSERT INTO history_n_temp_ba_kerusakan (
                id_ba, status, pending_status, pending_approver, alasan_edit, tanggal, nomor_ba, jenis_perangkat, merek, no_po, user, deskripsi, sn,
                tahun_perolehan, penyebab_kerusakan, kategori_kerusakan_id, keterangan_dll, rekomendasi_mis, pt, id_pt, lokasi,
                nama_pembuat,
                pembuat, jabatan_pembuat,
                penyetujui, jabatan_penyetujui,
                peminjam, jabatan_peminjam,
                atasan_peminjam, jabatan_atasan_peminjam,
                diketahui, jabatan_diketahui,
                approval_1, approval_2, approval_3, approval_4, approval_5,
                autograph_1, autograph_2, autograph_3, autograph_4, autograph_5,
                tanggal_approve_1, tanggal_approve_2, tanggal_approve_3, tanggal_approve_4, tanggal_approve_5,
                file_created
            ) VALUES (
                ?, 0, 0, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
            )
        ");
        $insert_old->bind_param(
            "issssssssssssisssissssssssssssiiiiisssssssssss",
            $old_data_array['id'],
            $pending_approver,
            $alasan_perubahan,
            $old_data_array['tanggal'],
            $old_data_array['nomor_ba'],
            $old_data_array['jenis_perangkat'],
            $old_data_array['merek'],
            $old_data_array['no_po'],
            $old_data_array['user'],
            $old_data_array['deskripsi'],
            $old_data_array['sn'],
            $old_data_array['tahun_perolehan'],
            $old_data_array['penyebab_kerusakan'],
            $old_data_array['kategori_kerusakan_id'],
            $old_data_array['keterangan_dll'],
            $old_data_array['rekomendasi_mis'],
            $old_data_array['pt'],
            $old_data_array['id_pt'],
            $old_data_array['lokasi'],
            $old_data_array['nama_pembuat'],
            $old_data_array['pembuat'],
            $old_data_array['jabatan_pembuat'],
            $old_data_array['penyetujui'],
            $old_data_array['jabatan_penyetujui'],
            $old_data_array['peminjam'],
            $old_data_array['jabatan_peminjam'],
            $old_data_array['atasan_peminjam'],
            $old_data_array['jabatan_atasan_peminjam'],
            $old_data_array['diketahui'],
            $old_data_array['jabatan_diketahui'],
            $old_data_array['approval_1'],
            $old_data_array['approval_2'],
            $old_data_array['approval_3'],
            $old_data_array['approval_4'],
            $old_data_array['approval_5'],
            $old_data_array['autograph_1'],
            $old_data_array['autograph_2'],
            $old_data_array['autograph_3'],
            $old_data_array['autograph_4'],
            $old_data_array['autograph_5'],
            $old_data_array['tanggal_approve_1'],
            $old_data_array['tanggal_approve_2'],
            $old_data_array['tanggal_approve_3'],
            $old_data_array['tanggal_approve_4'],
            $old_data_array['tanggal_approve_5'],
            $old_data_array['created_at']
        );
        $insert_old->execute();
        $insert_old->close();

        // =============================
        // UPDATE DATA UTAMA (berita_acara_kerusakan)
        // =============================
        $update_real = $koneksi->prepare("
            UPDATE berita_acara_kerusakan SET
                tanggal = ?,
                nomor_ba = ?,
                jenis_perangkat = ?,
                merek = ?,
                no_po = ?,
                user = ?,
                deskripsi = ?,
                sn = ?,
                tahun_perolehan = ?,
                penyebab_kerusakan = ?,
                kategori_kerusakan_id = ?,
                keterangan_dll = ?,
                rekomendasi_mis = ?,
                pt = ?,
                id_pt = ?,
                lokasi = ?,
                nama_pembuat = ?,
                pembuat = ?,
                jabatan_pembuat = ?,
                penyetujui = ?,
                jabatan_penyetujui = ?,
                peminjam = ?,
                jabatan_peminjam = ?,
                atasan_peminjam = ?,
                jabatan_atasan_peminjam = ?,
                diketahui = ?,
                jabatan_diketahui = ?,
                approval_1 = ?,
                approval_2 = ?,
                approval_3 = ?,
                approval_4 = ?,
                approval_5 = ?,
                autograph_1 = ?,
                autograph_2 = ?,
                autograph_3 = ?,
                autograph_4 = ?,
                autograph_5 = ?,
                tanggal_approve_1 = ?,
                tanggal_approve_2 = ?,
                tanggal_approve_3 = ?,
                tanggal_approve_4 = ?,
                tanggal_approve_5 = ?
            WHERE id = ?
        ");
        $update_real->bind_param(
            "ssssssssssisssissssssssssssiiiiissssssssssi",
            $new_data_array['tanggal'],
            $new_data_array['nomor_ba'],
            $new_data_array['jenis_perangkat'],
            $new_data_array['merek'],
            $new_data_array['no_po'],
            $new_data_array['user'],
            $new_data_array['deskripsi'],
            $new_data_array['sn'],
            $new_data_array['tahun_perolehan'],
            $new_data_array['penyebab_kerusakan'],
            $new_data_array['kategori_kerusakan_id'],
            $new_data_array['keterangan_dll'],
            $new_data_array['rekomendasi_mis'],
            $new_data_array['pt'],
            $new_data_array['id_pt'],
            $new_data_array['lokasi'],
            $new_data_array['nama_pembuat'],
            $new_data_array['pembuat'],
            $new_data_array['jabatan_pembuat'],
            $new_data_array['penyetujui'],
            $new_data_array['jabatan_penyetujui'],
            $new_data_array['peminjam'],
            $new_data_array['jabatan_peminjam'],
            $new_data_array['atasan_peminjam'],
            $new_data_array['jabatan_atasan_peminjam'],
            $new_data_array['diketahui'],
            $new_data_array['jabatan_diketahui'],
            $new_data_array['approval_1'],
            $new_data_array['approval_2'],
            $new_data_array['approval_3'],
            $new_data_array['approval_4'],
            $new_data_array['approval_5'],
            $new_data_array['autograph_1'],
            $new_data_array['autograph_2'],
            $new_data_array['autograph_3'],
            $new_data_array['autograph_4'],
            $new_data_array['autograph_5'],
            $new_data_array['tanggal_approve_1'],
            $new_data_array['tanggal_approve_2'],
            $new_data_array['tanggal_approve_3'],
            $new_data_array['tanggal_approve_4'],
            $new_data_array['tanggal_approve_5'],
            $id
        );
        $update_real->execute();
        $update_real->close();
    }


    // Folder penyimpanan gambar
    $upload_dir = '../assets/database-gambar/';

    // Proses penghapusan gambar lama jika ditandai
    if (isset($_POST['hapus_gambar'])) {
        foreach ($_POST['hapus_gambar'] as $key => $value) {
            if ($value === 'hapus') {
                $gambar_id = intval($_POST['gambar_lama_id'][$key]);
                $get_path = $koneksi->prepare("SELECT file_path FROM gambar_ba_kerusakan WHERE id = ?");
                $get_path->bind_param("i", $gambar_id);
                $get_path->execute();
                $res = $get_path->get_result();
                if ($row = $res->fetch_assoc()) {
                    if (file_exists($row['file_path'])) {
                        unlink($row['file_path']);
                    }
                }
                $get_path->close();

                $del_stmt = $koneksi->prepare("DELETE FROM gambar_ba_kerusakan WHERE id = ?");
                $del_stmt->bind_param("i", $gambar_id);
                if (!$del_stmt->execute()) {
                    $_SESSION['message'] = "Gagal menghapus gambar lama: " . $del_stmt->error;
                    header("Location: ba_kerusakan.php?status=gagal");
                    exit();
                }
                $del_stmt->close();
            }
        }
    }

    // Proses penggantian file gambar lama
    if (!empty($_FILES['gambar_lama_file']['name'])) {
        foreach ($_FILES['gambar_lama_file']['name'] as $id_gambar => $filename) {
            if (!empty($filename)) {
                $tmp_name = $_FILES['gambar_lama_file']['tmp_name'][$id_gambar];
                $target_path = $upload_dir . time() . '_' . basename($filename);
                if (move_uploaded_file($tmp_name, $target_path)) {
                    $update_stmt = $koneksi->prepare("UPDATE gambar_ba_kerusakan SET file_path = ?, uploaded_at = NOW() WHERE id = ?");
                    $update_stmt->bind_param("si", $target_path, $id_gambar);
                    if (!$update_stmt->execute()) {
                        $_SESSION['message'] = "Gagal mengganti gambar lama: " . $update_stmt->error;
                        header("Location: ba_kerusakan.php?status=gagal");
                        exit();
                    }
                    $update_stmt->close();
                }
            }
        }
    }

    // Proses gambar baru
    if (isset($_FILES['gambar_baru'])) {
        foreach ($_FILES['gambar_baru']['tmp_name'] as $key => $tmp_name) {
            if (!empty($tmp_name)) {
                $filename = basename($_FILES['gambar_baru']['name'][$key]);
                $target_path = $upload_dir . time() . '_' . $filename;

                if (move_uploaded_file($tmp_name, $target_path)) {
                    $stmt_img = $koneksi->prepare("INSERT INTO gambar_ba_kerusakan (ba_kerusakan_id, file_path, uploaded_at) VALUES (?, ?, NOW())");
                    $stmt_img->bind_param("is", $id, $target_path);
                    if (!$stmt_img->execute()) {
                        $_SESSION['message'] = "Gagal menambahkan gambar baru: " . $stmt_img->error;
                        header("Location: ba_kerusakan.php?status=gagal");
                        exit();
                    }
                    $stmt_img->close();
                }
            }
        }
    }
    $koneksi->close();

    $_SESSION['message'] = "Data berhasil diperbarui ke database.";
    header("Location: ba_kerusakan.php?status=sukses");
    exit();
