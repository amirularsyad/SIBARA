
<?php
session_start();
require '../koneksi.php';

// Debug test : Fill
// ======================================================
// $_POST['id_ba'] = 38;
// $_POST['jenisBA'] = 'mutasi';
// $_POST['alasan_tolak'] = 'none';
// $_POST['approver'] = 'Tedy Paronto';
// $_POST['aksi'] = 'setuju';
// ======================================================

$id_ba    = intval($_POST['id_ba']);
$jenisBA  = $koneksi->real_escape_string($_POST['jenisBA']);
$approver = $koneksi->real_escape_string($_POST['approver']);
$alasan = $koneksi->real_escape_string($_POST['alasan_tolak']);
$aksi     = $koneksi->real_escape_string($_POST['aksi']); // "setuju" atau "tolak"

$response = ["status" => false];

        // Debug test : View $_POST
        // ======================================================
        // echo '<pre>';
        // echo "==== \$_POST ====\n";
        // print_r($_POST);
        // echo '<br>';
        // echo '</pre>';
        // ======================================================
        
// ======================================================
// === AKSI : TOLAK ======================================
// ======================================================

if ($aksi === "tolak") {

    $all_ok = true;

    if ($jenisBA === "kerusakan") {

        // Debug test : View response
        // echo 'Kerusakan Tolak';
        // exit;


        // 1. DELETE data lama
        if (!$koneksi->query("
            DELETE FROM history_n_temp_ba_kerusakan
            WHERE id_ba = $id_ba
              AND pending_approver = '$approver'
              AND pending_status = 1
              AND status = 0
        ")) { $all_ok = false; }

        // 2. UPDATE data baru
        if (!$koneksi->query("
            UPDATE history_n_temp_ba_kerusakan 
            SET pending_status = 2,
                status = 2,
                alasan_tolak = '$alasan'
            WHERE id_ba = $id_ba
                AND pending_approver = '$approver'
                AND pending_status = 1
                AND status = 1
        ")) { $all_ok = false; }

        // 3. UPDATE historikal
        if (!$koneksi->query("
            UPDATE historikal_edit_ba
            SET pending_status = 2
            WHERE id_ba = $id_ba
                AND nama_ba = '$jenisBA'
                AND pending_status = 1
        ")) { $all_ok = false; }
    }

    if ($jenisBA === "mutasi"){

        // Debug test : View response
        // echo 'Mutasi Tolak';
        // exit;


        // 1. DELETE data lama
        if (!$koneksi->query("
            DELETE FROM history_n_temp_ba_mutasi
            WHERE id_ba = $id_ba
                AND pending_approver = '$approver'
                AND pending_status = 1
                AND status = 0
        ")) { $all_ok = false; }
        // 2. UPDATE data baru
        if (!$koneksi->query("
            UPDATE history_n_temp_ba_mutasi 
            SET pending_status = 2,
                status = 2,
                alasan_tolak = '$alasan'
            WHERE id_ba = $id_ba
                AND pending_approver = '$approver'
                AND pending_status = 1
                AND status = 1
        ")) { $all_ok = false; }
        // 3. UPDATE historikal
        if (!$koneksi->query("
            UPDATE historikal_edit_ba
            SET pending_status = 2
            WHERE id_ba = $id_ba
                AND nama_ba = '$jenisBA'
                AND pending_status = 1
        ")) { $all_ok = false; }
        // 4. DELETE data lama barang
        if (!$koneksi->query("
            DELETE FROM history_n_temp_barang_mutasi
            WHERE id_ba = $id_ba
                AND pending_status = 1
                AND status = 0
        ")) { $all_ok = false; }
        // 5. UPDATE data baru barang
        if (!$koneksi->query("
            UPDATE history_n_temp_barang_mutasi 
            SET pending_status = 2,
                status = 2
            WHERE id_ba = $id_ba
                AND pending_status = 1
                AND status = 1
        ")) { $all_ok = false; }
    }

    if ($jenisBA === "st_asset") {

        // 1. DELETE data lama
        if (!$koneksi->query("
            DELETE FROM history_n_temp_ba_serah_terima_asset
            WHERE id_ba = $id_ba
              AND pending_approver = '$approver'
              AND pending_status = 1
              AND status = 0
        ")) { $all_ok = false; }

        // 2. UPDATE data baru
        if (!$koneksi->query("
            UPDATE history_n_temp_ba_serah_terima_asset 
            SET pending_status = 2,
                status = 2,
                alasan_tolak = '$alasan'
            WHERE id_ba = $id_ba
                AND pending_approver = '$approver'
                AND pending_status = 1
                AND status = 1
        ")) { $all_ok = false; }

        // 3. UPDATE historikal
        if (!$koneksi->query("
            UPDATE historikal_edit_ba
            SET pending_status = 2
            WHERE id_ba = $id_ba
                AND nama_ba = '$jenisBA'
                AND pending_status = 1
        ")) { $all_ok = false; }
    }

    if ($jenisBA !== "kerusakan" && $jenisBA !== "mutasi" && $jenisBA !== "st_asset"){
        $_SESSION['success'] = false;
        $_SESSION['message'] = 'Tidak ada BA yang valid pada proses tolak';
        exit;
    }

    if ($all_ok) {
        $_SESSION['success'] = true;
        $_SESSION['message'] = "Perubahan berhasil ditolak.";
        $response["success"] = true;
    } 
    else {
        $_SESSION['success'] = false;
        $_SESSION['message'] = "Gagal memproses penolakan.";
        $response['success'] = false;
    }

    echo json_encode($response);
    exit();

}



// ======================================================
// === AKSI : SETUJU ====================================
// ======================================================

if ($aksi === "setuju") {

    $all_ok = true;

    if ($jenisBA === "kerusakan") {

        // Debug test : View response
        // echo 'Kerusakan Setuju';
        // exit;

        
        // 1. UPDATE DATA LAMA: pending_status = 0
        $upd_lama = "
            UPDATE history_n_temp_ba_kerusakan
            SET pending_status = 0
            WHERE id_ba = $id_ba
              AND pending_approver = '".addslashes($approver)."'
              AND pending_status = 1
              AND status = 0
        ";
        if (!$koneksi->query($upd_lama)) $all_ok = false;

        // 2. AMBIL DATA PERUBAHAN (status = 1)
        $sql = "
            SELECT * FROM history_n_temp_ba_kerusakan
            WHERE id_ba = $id_ba
              AND pending_approver = '".addslashes($approver)."'
              AND pending_status = 1
              AND status = 1
            LIMIT 1
        ";
        $hasil = $koneksi->query($sql);

        if ($hasil && $hasil->num_rows > 0) {

            $data = $hasil->fetch_assoc();

            // 3. Escape semua data sebelum dimasukkan ke query
            $fields = [
                'tanggal','nomor_ba','jenis_perangkat','merek', 'no_po', 'user','deskripsi','sn','tahun_perolehan',
                'penyebab_kerusakan','kategori_kerusakan_id','keterangan_dll','rekomendasi_mis','pt','lokasi',
                'nama_pembuat',
                'pembuat','jabatan_pembuat',
                'penyetujui','jabatan_penyetujui',
                'peminjam','jabatan_peminjam',
                'atasan_peminjam','jabatan_atasan_peminjam',
                'diketahui','jabatan_diketahui',
                'approval_1','approval_2','approval_3','approval_4','approval_5',
                'autograph_1','autograph_2','autograph_3','autograph_4','autograph_5',
                'tanggal_approve_1','tanggal_approve_2','tanggal_approve_3','tanggal_approve_4','tanggal_approve_5'
            ];

            $escaped = [];
            foreach ($fields as $f) {
                if (strpos($f, 'approval') !== false || strpos($f, 'kategori') !== false) {
                    $escaped[$f] = (int)$data[$f]; // integer
                } else {
                    $escaped[$f] = "'".addslashes($data[$f])."'"; // string
                }
            }

            // 4. Buat query UPDATE
            $update = "
                UPDATE berita_acara_kerusakan SET
                    tanggal = {$escaped['tanggal']},
                    nomor_ba = {$escaped['nomor_ba']},
                    jenis_perangkat = {$escaped['jenis_perangkat']},
                    merek = {$escaped['merek']},
                    no_po = {$escaped['no_po']},
                    user = {$escaped['user']},
                    deskripsi = {$escaped['deskripsi']},
                    sn = {$escaped['sn']},
                    tahun_perolehan = {$escaped['tahun_perolehan']},
                    penyebab_kerusakan = {$escaped['penyebab_kerusakan']},
                    kategori_kerusakan_id = {$escaped['kategori_kerusakan_id']},
                    keterangan_dll = {$escaped['keterangan_dll']},
                    rekomendasi_mis = {$escaped['rekomendasi_mis']},
                    pt = {$escaped['pt']},
                    lokasi = {$escaped['lokasi']},
                    nama_pembuat = {$escaped['nama_pembuat']},
                    pembuat = {$escaped['pembuat']},
                    jabatan_pembuat = {$escaped['jabatan_pembuat']},
                    penyetujui = {$escaped['penyetujui']},
                    jabatan_penyetujui = {$escaped['jabatan_penyetujui']},
                    peminjam = {$escaped['peminjam']},
                    jabatan_peminjam = {$escaped['jabatan_peminjam']},
                    atasan_peminjam = {$escaped['atasan_peminjam']},
                    jabatan_atasan_peminjam = {$escaped['jabatan_atasan_peminjam']},
                    diketahui = {$escaped['diketahui']},
                    jabatan_diketahui = {$escaped['jabatan_diketahui']},
                    approval_1 = {$escaped['approval_1']},
                    approval_2 = {$escaped['approval_2']},
                    approval_3 = {$escaped['approval_3']},
                    approval_4 = {$escaped['approval_4']},
                    approval_5 = {$escaped['approval_5']},
                    autograph_1 = {$escaped['autograph_1']},
                    autograph_2 = {$escaped['autograph_2']},
                    autograph_3 = {$escaped['autograph_3']},
                    autograph_4 = {$escaped['autograph_4']},
                    autograph_5 = {$escaped['autograph_5']},
                    tanggal_approve_1 = {$escaped['tanggal_approve_1']},
                    tanggal_approve_2 = {$escaped['tanggal_approve_2']},
                    tanggal_approve_3 = {$escaped['tanggal_approve_3']},
                    tanggal_approve_4 = {$escaped['tanggal_approve_4']},
                    tanggal_approve_5 = {$escaped['tanggal_approve_5']}
                WHERE id = $id_ba
            ";

            if (!$koneksi->query($update)) $all_ok = false;

        } else {
            $all_ok = false;
        }

        // 5. HAPUS HISTORY SUDAH DITERAPKAN
        $del = "
            DELETE FROM history_n_temp_ba_kerusakan
            WHERE id_ba = $id_ba
              AND pending_approver = '".addslashes($approver)."'
              AND pending_status = 1
              AND status = 1
        ";
        if (!$koneksi->query($del)) $all_ok = false;

        // 6. UPDATE historikal_edit_ba
        $upd_hist = "
            UPDATE historikal_edit_ba
            SET pending_status = 0
            WHERE id_ba = $id_ba
              AND nama_ba = '".addslashes($jenisBA)."'
              AND pending_status = 1
        ";
        if (!$koneksi->query($upd_hist)) $all_ok = false;
    }

    if ($jenisBA === "mutasi") {

        // 1. UPDATE DATA LAMA: pending_status = 0
        $upd_lama = "
            UPDATE history_n_temp_ba_mutasi
            SET pending_status = 0
            WHERE id_ba = $id_ba
              AND pending_approver = '".addslashes($approver)."'
              AND pending_status = 1
              AND status = 0
        ";
        if (!$koneksi->query($upd_lama)) $all_ok = false;

        // 2. AMBIL DATA PERUBAHAN (status = 1)
        $sql = "
            SELECT * FROM history_n_temp_ba_mutasi
            WHERE id_ba = $id_ba
              AND pending_approver = '".addslashes($approver)."'
              AND pending_status = 1
              AND status = 1
            LIMIT 1
        ";
        $hasil = $koneksi->query($sql);

        if ($hasil && $hasil->num_rows > 0) {

            $data = $hasil->fetch_assoc();

            // 3. Escape semua data sebelum dimasukkan ke query
            $fields = [
                'tanggal','nomor_ba','pembuat','pt_asal','id_pt_asal','pt_tujuan','id_pt_tujuan','keterangan',
                'pengirim1','jabatan_pengirim1',
                'pengirim2','jabatan_pengirim2',
                'hrd_ga_pengirim','jabatan_hrd_ga_pengirim',
                'penerima1','jabatan_penerima1',
                'penerima2','jabatan_penerima2',
                'hrd_ga_penerima','jabatan_hrd_ga_penerima',
                'diketahui','jabatan_diketahui',
                'pemeriksa1','jabatan_pemeriksa1',
                'pemeriksa2','jabatan_pemeriksa2',
                'penyetujui1','jabatan_penyetujui1',
                'penyetujui2','jabatan_penyetujui2',
                'approval_1','approval_2','approval_3','approval_4','approval_5',
                'approval_6','approval_7','approval_8','approval_9','approval_10',
                'approval_11',
                'autograph_1','autograph_2','autograph_3','autograph_4','autograph_5',
                'autograph_6','autograph_7','autograph_8','autograph_9','autograph_10',
                'autograph_11',
                'tanggal_approve_1','tanggal_approve_2','tanggal_approve_3','tanggal_approve_4','tanggal_approve_5',
                'tanggal_approve_6','tanggal_approve_7','tanggal_approve_8','tanggal_approve_9','tanggal_approve_10',
                'tanggal_approve_11'
            ];

            $escaped = [];
            foreach ($fields as $f) {
                if (strpos($f, 'approval') !== false || strpos($f, 'kategori') !== false || strpos($f, 'id_pt') !== false) {
                    $escaped[$f] = (int)$data[$f]; // integer

                } else {
                    $escaped[$f] = "'".addslashes($data[$f])."'"; // string

                }
            }

            // 4. Buat query UPDATE
            $update = "
                UPDATE berita_acara_mutasi SET
                    tanggal              = {$escaped['tanggal']},
                    nomor_ba             = {$escaped['nomor_ba']},
                    pembuat              = {$escaped['pembuat']},

                    pt_asal              = {$escaped['pt_asal']},
                    id_pt_asal           = {$escaped['id_pt_asal']},
                    pt_tujuan            = {$escaped['pt_tujuan']},
                    id_pt_tujuan         = {$escaped['id_pt_tujuan']},

                    keterangan           = {$escaped['keterangan']},

                    pengirim1            = {$escaped['pengirim1']},
                    jabatan_pengirim1    = {$escaped['jabatan_pengirim1']},
                    pengirim2            = {$escaped['pengirim2']},
                    jabatan_pengirim2    = {$escaped['jabatan_pengirim2']},
                    hrd_ga_pengirim      = {$escaped['hrd_ga_pengirim']},
                    jabatan_hrd_ga_pengirim = {$escaped['jabatan_hrd_ga_pengirim']},

                    penerima1            = {$escaped['penerima1']},
                    jabatan_penerima1    = {$escaped['jabatan_penerima1']},
                    penerima2            = {$escaped['penerima2']},
                    jabatan_penerima2    = {$escaped['jabatan_penerima2']},
                    hrd_ga_penerima      = {$escaped['hrd_ga_penerima']},
                    jabatan_hrd_ga_penerima = {$escaped['jabatan_hrd_ga_penerima']},

                    diketahui            = {$escaped['diketahui']},
                    jabatan_diketahui    = {$escaped['jabatan_diketahui']},
                    pemeriksa1           = {$escaped['pemeriksa1']},
                    jabatan_pemeriksa1   = {$escaped['jabatan_pemeriksa1']},
                    pemeriksa2           = {$escaped['pemeriksa2']},
                    jabatan_pemeriksa2   = {$escaped['jabatan_pemeriksa2']},
                    penyetujui1          = {$escaped['penyetujui1']},
                    jabatan_penyetujui1  = {$escaped['jabatan_penyetujui1']},
                    penyetujui2          = {$escaped['penyetujui2']},
                    jabatan_penyetujui2  = {$escaped['jabatan_penyetujui2']},

                    approval_1           = {$escaped['approval_1']},
                    approval_2           = {$escaped['approval_2']},
                    approval_3           = {$escaped['approval_3']},
                    approval_4           = {$escaped['approval_4']},
                    approval_5           = {$escaped['approval_5']},
                    approval_6           = {$escaped['approval_6']},
                    approval_7           = {$escaped['approval_7']},
                    approval_8           = {$escaped['approval_8']},
                    approval_9           = {$escaped['approval_9']},
                    approval_10          = {$escaped['approval_10']},
                    approval_11          = {$escaped['approval_11']},

                    autograph_1          = {$escaped['autograph_1']},
                    autograph_2          = {$escaped['autograph_2']},
                    autograph_3          = {$escaped['autograph_3']},
                    autograph_4          = {$escaped['autograph_4']},
                    autograph_5          = {$escaped['autograph_5']},
                    autograph_6          = {$escaped['autograph_6']},
                    autograph_7          = {$escaped['autograph_7']},
                    autograph_8          = {$escaped['autograph_8']},
                    autograph_9          = {$escaped['autograph_9']},
                    autograph_10         = {$escaped['autograph_10']},
                    autograph_11         = {$escaped['autograph_11']},

                    tanggal_approve_1    = {$escaped['tanggal_approve_1']},
                    tanggal_approve_2    = {$escaped['tanggal_approve_2']},
                    tanggal_approve_3    = {$escaped['tanggal_approve_3']},
                    tanggal_approve_4    = {$escaped['tanggal_approve_4']},
                    tanggal_approve_5    = {$escaped['tanggal_approve_5']},
                    tanggal_approve_6    = {$escaped['tanggal_approve_6']},
                    tanggal_approve_7    = {$escaped['tanggal_approve_7']},
                    tanggal_approve_8    = {$escaped['tanggal_approve_8']},
                    tanggal_approve_9    = {$escaped['tanggal_approve_9']},
                    tanggal_approve_10   = {$escaped['tanggal_approve_10']},
                    tanggal_approve_11   = {$escaped['tanggal_approve_11']}
                WHERE id = $id_ba
            ";

            if (!$koneksi->query($update)) $all_ok = false;
        }
        else{
            $all_ok = false;
        }

                // 5. HAPUS HISTORY SUDAH DITERAPKAN
        $del = "
            DELETE FROM history_n_temp_ba_mutasi
            WHERE id_ba = $id_ba
              AND pending_approver = '".addslashes($approver)."'
              AND pending_status = 1
              AND status = 1
        ";
        if (!$koneksi->query($del)) $all_ok = false;

        // 6. UPDATE historikal_edit_ba
        $upd_hist = "
            UPDATE historikal_edit_ba
            SET pending_status = 0
            WHERE id_ba = $id_ba
              AND nama_ba = '".addslashes($jenisBA)."'
              AND pending_status = 1
        ";
        if (!$koneksi->query($upd_hist)) $all_ok = false;


        // ==============================================================

        // 1. UPDATE DATA BARANG LAMA: pending_status = 0
        $upd_lama_2 = "
            UPDATE history_n_temp_barang_mutasi
            SET pending_status = 0
            WHERE id_ba = $id_ba
              AND pending_status = 1
              AND status = 0
        ";
        if (!$koneksi->query($upd_lama_2)) $all_ok = false;

        // 2. HAPUS DATA BARANG ASLI
        $delete = "
            DELETE FROM barang_mutasi
            WHERE id_ba = $id_ba
        ";

        if (!$koneksi->query($delete)) {
            $all_ok = false;
        }

        if ($all_ok) {
        // 3. AMBIL DATA PERUBAHAN BARANG(status = 1)
        $sql_barang = "
            SELECT * FROM history_n_temp_barang_mutasi
            WHERE id_ba = $id_ba
              AND pending_status = 1
              AND status = 1
        ";
        $hasil_barang = $koneksi->query($sql_barang);

        if ($hasil_barang && $hasil_barang->num_rows > 0) :

            // 4. Escape semua data sebelum dimasukkan ke query
            $fields_barang = [
                'id_ba','pt_asal','po','coa',
                'kode_assets','merk','sn','user','created_at'
            ];

            // LOOP
            while ($data_barang = $hasil_barang->fetch_assoc()) {

                $escaped_barang = [];

                foreach ($fields_barang as $f) {
                    if ($f === 'id_ba') {
                        $escaped_barang[$f] = (int)$data_barang[$f];
                    } else {
                        $escaped_barang[$f] = "'".addslashes($data_barang[$f])."'";
                    }
                }

                $insert = "
                    INSERT INTO barang_mutasi (
                        id_ba, pt_asal, po, coa,
                        kode_assets, merk, sn, user, created_at
                    ) VALUES (
                        {$escaped_barang['id_ba']},
                        {$escaped_barang['pt_asal']},
                        {$escaped_barang['po']},
                        {$escaped_barang['coa']},
                        {$escaped_barang['kode_assets']},
                        {$escaped_barang['merk']},
                        {$escaped_barang['sn']},
                        {$escaped_barang['user']},
                        {$escaped_barang['created_at']}
                    )
                ";

                if (!$koneksi->query($insert)) $all_ok = false;
            }

        else :
            $all_ok = false;
        endif;
    }
    }

    if ($jenisBA === "st_asset") {

        
        // 1. UPDATE DATA LAMA: pending_status = 0
        $upd_lama = "
            UPDATE history_n_temp_ba_serah_terima_asset
            SET pending_status = 0
            WHERE id_ba = $id_ba
              AND pending_approver = '".addslashes($approver)."'
              AND pending_status = 1
              AND status = 0
        ";
        if (!$koneksi->query($upd_lama)) $all_ok = false;

        // 2. AMBIL DATA PERUBAHAN (status = 1)
        $sql = "
            SELECT * FROM history_n_temp_ba_serah_terima_asset
            WHERE id_ba = $id_ba
              AND pending_approver = '".addslashes($approver)."'
              AND pending_status = 1
              AND status = 1
            LIMIT 1
        ";
        $hasil = $koneksi->query($sql);

        if ($hasil && $hasil->num_rows > 0) {

            $data = $hasil->fetch_assoc();

            // 3. Escape semua data sebelum dimasukkan ke query
            $fields = [
                'tanggal','nomor_ba','pt','id_pt', 'lokasi', 'nama_pembuat', 'atasan_peminjam', 'alamat_peminjam',
                'sn', 'merek', 'type', 'satuan', 'cpu', 'os', 'ram', 'storage', 'gpu', 'display', 'lain', 'merk_monitor',
                'sn_monitor', 'merk_keyboard', 'sn_keyboard', 'merk_mouse', 'sn_mouse', 'categories', 'qty_id', 'kode_assets', 
                'no_po', 'tgl_pembelian', 'user', 
                'peminjam', 'saksi', 'diketahui', 'pihak_pertama',
                'approval_1','approval_2','approval_3','approval_4',
                'autograph_1','autograph_2','autograph_3','autograph_4',
                'tanggal_approve_1','tanggal_approve_2','tanggal_approve_3','tanggal_approve_4'
            ];

            $intFields = [
                'id_pt','qty_id',
                'approval_1','approval_2','approval_3','approval_4'
            ];

            foreach ($fields as $f) {
                if (in_array($f, $intFields)) {
                    $escaped[$f] = (int)$data[$f];
                } else {
                    $escaped[$f] = "'".$koneksi->real_escape_string($data[$f])."'";
                }
            }


            // 4. Buat query UPDATE
            $update = "
                UPDATE ba_serah_terima_asset SET
                    tanggal = {$escaped['tanggal']},
                    nomor_ba = {$escaped['nomor_ba']},
                    pt = {$escaped['pt']},
                    id_pt = {$escaped['id_pt']}, 
                    lokasi = {$escaped['lokasi']}, 
                    nama_pembuat = {$escaped['nama_pembuat']}, 
                    atasan_peminjam = {$escaped['atasan_peminjam']}, 
                    alamat_peminjam = {$escaped['alamat_peminjam']},
                    sn = {$escaped['sn']}, 
                    merek = {$escaped['merek']}, 
                    type = {$escaped['type']}, 
                    satuan = {$escaped['satuan']}, 
                    cpu = {$escaped['cpu']}, 
                    os = {$escaped['os']}, 
                    ram = {$escaped['ram']}, 
                    storage = {$escaped['storage']}, 
                    gpu = {$escaped['gpu']}, 
                    display = {$escaped['display']}, 
                    lain = {$escaped['lain']}, 
                    merk_monitor = {$escaped['merk_monitor']},
                    sn_monitor = {$escaped['sn_monitor']},
                    merk_keyboard = {$escaped['merk_keyboard']},
                    sn_keyboard = {$escaped['sn_keyboard']},
                    merk_mouse = {$escaped['merk_mouse']},
                    sn_mouse = {$escaped['sn_mouse']},
                    categories = {$escaped['categories']},
                    qty_id = {$escaped['qty_id']},
                    kode_assets = {$escaped['kode_assets']},
                    no_po = {$escaped['no_po']},
                    tgl_pembelian = {$escaped['tgl_pembelian']},
                    user = {$escaped['user']},
                    peminjam = {$escaped['peminjam']},
                    saksi = {$escaped['saksi']},
                    diketahui = {$escaped['diketahui']},
                    pihak_pertama = {$escaped['pihak_pertama']},
                    approval_1 = {$escaped['approval_1']},
                    approval_2 = {$escaped['approval_2']},
                    approval_3 = {$escaped['approval_3']},
                    approval_4 = {$escaped['approval_4']},
                    autograph_1 = {$escaped['autograph_1']},
                    autograph_2 = {$escaped['autograph_2']},
                    autograph_3 = {$escaped['autograph_3']},
                    autograph_4 = {$escaped['autograph_4']},
                    tanggal_approve_1 = {$escaped['tanggal_approve_1']},
                    tanggal_approve_2 = {$escaped['tanggal_approve_2']},
                    tanggal_approve_3 = {$escaped['tanggal_approve_3']},
                    tanggal_approve_4 = {$escaped['tanggal_approve_4']}
                WHERE id = $id_ba
            ";

            if (!$koneksi->query($update)) $all_ok = false;

        } else {
            $all_ok = false;
        }

        // 5. HAPUS HISTORY SUDAH DITERAPKAN
        $del = "
            DELETE FROM history_n_temp_ba_serah_terima_asset
            WHERE id_ba = $id_ba
              AND pending_approver = '".addslashes($approver)."'
              AND pending_status = 1
              AND status = 1
        ";
        if (!$koneksi->query($del)) $all_ok = false;

        // 6. UPDATE historikal_edit_ba
        $upd_hist = "
            UPDATE historikal_edit_ba
            SET pending_status = 0
            WHERE id_ba = $id_ba
                AND nama_ba = '".addslashes($jenisBA)."'
                AND pending_status = 1
        ";
        if (!$koneksi->query($upd_hist)) $all_ok = false;
    }

    if ($jenisBA !== "kerusakan" && $jenisBA !== "mutasi" && $jenisBA !== "st_asset"){
        $_SESSION['success'] = false;
        $_SESSION['message'] = 'Tidak ada BA yang valid pada proses approval';
        $response['success'] = false;
        exit;
    }

    $response = [];
    if ($all_ok) {
        $_SESSION['success'] = true;
        $_SESSION['message'] = "Perubahan berhasil disetujui.";
        $response['success'] = true;
    } else {
        $_SESSION['success'] = false;
        $_SESSION['message'] = "Gagal memproses persetujuan.";
        $response['success'] = false;
    }

    echo json_encode($response);
    exit();
}

if($aksi !== "setuju" && $aksi !== "tolak"){
    $_SESSION['success'] = false;
    $_SESSION['message'] = 'tidak ada aksi yang valid';
    $response['success'] = false;
    exit;
}


echo json_encode($response);
?>
