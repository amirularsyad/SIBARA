<?php
require '../koneksi.php';

// $_GET['id_ba'] = 37;
// $_GET['approver'] = 'Tedy Paronto';
// $_GET['jenisBA'] = 'mutasi';

$id_ba    = intval($_GET['id_ba']);
$approver = $koneksi->real_escape_string($_GET['approver']);
$jenisBA  = $koneksi->real_escape_string($_GET['jenisBA']);

if ($jenisBA === 'st_asset') {
    $jenisBA = 'serah_terima_asset';
}

/*
|--------------------------------------------------------------------------
| Normalisasi nama tabel history
| Khusus BA Pengembalian memakai suffix _v2
|--------------------------------------------------------------------------
*/
$historyTable = "history_n_temp_ba_" . $jenisBA;

if ($jenisBA === 'pengembalian') {
    $historyTable = "history_n_temp_ba_pengembalian_v2";
}

$sql = "
    SELECT *
    FROM $historyTable
    WHERE id_ba = $id_ba
        AND pending_status = 1
        AND pending_approver = '$approver'
        AND status IN (0,1)
";

$result = $koneksi->query($sql);

$dataLama = [];
$dataBaru = [];

/*=== khusus BA kerusakan ============*/
$kolomKhususKerusakan = [
    "tanggal",
    "nomor_ba",
    "jenis_perangkat",
    "merek",
    "no_po",
    "deskripsi",
    "sn",
    "penyebab_kerusakan",
    "kategori_kerusakan_id",
    "keterangan_dll",
    "rekomendasi_mis",
    "pembuat",
    "penyetujui",
    "peminjam",
    "atasan_peminjam",
    "diketahui"
];

/*=== khusus BA mutasi ============*/
$kolomKhususMutasi = [
    "tanggal",
    "nomor_ba",
    "pt_asal",
    "pt_tujuan",
    "keterangan",
    "pengirim1",
    "pengirim2",
    "hrd_ga_pengirim",
    "penerima1",
    "penerima2",
    "hrd_ga_penerima",
    "diketahui",
    "pemeriksa1",
    "pemeriksa2",
    "penyetujui1",
    "penyetujui2"
];
/*=== khusus BA st_asset ============*/
$kolomKhususSTAsset = [
    "tanggal",
    "nomor_ba",
    "pt",
    "merek",
    "sn",
    "peminjam",
    "saksi",
    "diketahui",
    "pihak_pertama",
    "alamat_peminjam"
];

/*=== khusus BA pengembalian ============*/
$kolomKhususPengembalian = [
    "tanggal",
    "nomor_ba",
    "pt",
    "pengembali",
    "jabatan_pengembali",
    "penerima",
    "jabatan_penerima",
    "diketahui",
    "jabatan_diketahui"
];

/*=== khusus BA pemutihan ============*/
$kolomKhususPemutihanHO = [
    "tanggal",
    "nomor_ba",
    "pt",
    "pembuat",
    "jabatan_pembuat",
    "pemeriksa",
    "jabatan_pemeriksa"
];

$kolomKhususPemutihanSite = [
    "tanggal",
    "nomor_ba",
    "pt",
    "pembuat_site",
    "jabatan_pembuat_site",
    "pemeriksa_site",
    "jabatan_pemeriksa_site",
    "diketahui1_site",
    "jabatan_diketahui1_site",
    "disetujui1_site",
    "jabatan_disetujui1_site"
];

// ===============================================
// AMBIL ALASAN EDIT (STATUS = 1, pending_status = 1)
// ===============================================
$alasan_edit = "";

$qAlasan = $koneksi->prepare("
    SELECT alasan_edit 
    FROM $historyTable
    WHERE id_ba = ?
      AND status = 1
      AND pending_status = 1
      AND pending_approver = ?
    LIMIT 1
");

$qAlasan->bind_param("is", $id_ba, $approver);
$qAlasan->execute();
$resAlasan = $qAlasan->get_result();

if ($resAlasan && $resAlasan->num_rows > 0) {
    $rowA = $resAlasan->fetch_assoc();
    $alasan_edit = $rowA["alasan_edit"];
}

$qAlasan->close();

$namaKolomMap = [];

/*
|--------------------------------------------------------------------------
| Helper khusus BA Pengembalian
| Aman untuk PHP 5.6
|--------------------------------------------------------------------------
*/
function pgv_safe_value($row, $key, $default)
{
    if (isset($row[$key])) {
        $val = trim((string)$row[$key]);
        if ($val !== '' && $val !== '-') {
            return $val;
        }
    }
    return $default;
}

function pgv_barang_identity_label($row, $fallbackNo)
{
    $parts = array();

    $merk       = pgv_safe_value($row, 'merk', '');
    $sn         = pgv_safe_value($row, 'sn', '');
    $po         = pgv_safe_value($row, 'po', '');

    if ($merk !== '')       $parts[] = 'MERK: ' . $merk;
    if ($sn !== '')         $parts[] = 'SN: ' . $sn;
    if ($po !== '')         $parts[] = 'PO: ' . $po;

    if (count($parts) === 0) {
        return 'Barang #' . $fallbackNo;
    }

    return implode(', ', $parts);
}

function pgv_barang_match_key($row, $fallbackNo)
{
    $kodeAssets = strtolower(pgv_safe_value($row, 'kode_assets', ''));
    $merk       = strtolower(pgv_safe_value($row, 'merk', ''));
    $sn         = strtolower(pgv_safe_value($row, 'sn', ''));
    $po         = strtolower(pgv_safe_value($row, 'po', ''));

    $raw = $kodeAssets . '|' . $merk . '|' . $sn . '|' . $po;

    if (trim(str_replace('|', '', $raw)) === '') {
        return 'fallback_' . $fallbackNo;
    }

    return $raw;
}

function pgv_barang_keterangan($row)
{
    $keterangan = pgv_safe_value($row, 'keterangan', '');
    if ($keterangan === '') {
        $keterangan = pgv_safe_value($row, 'deskripsi', '');
    }
    if ($keterangan === '') {
        $keterangan = 'kosong';
    }
    return $keterangan;
}

function pgv_barang_kondisi($row)
{
    $kondisi = pgv_safe_value($row, 'kondisi', '');
    if ($kondisi === '') {
        $kondisi = 'kosong';
    }
    return $kondisi;
}

function pgv_normalize_barang_rows($rows)
{
    $normalized = array();
    $counterPerKey = array();
    $no = 1;

    foreach ($rows as $row) {
        $baseKey = pgv_barang_match_key($row, $no);

        if (!isset($counterPerKey[$baseKey])) {
            $counterPerKey[$baseKey] = 0;
        }
        $counterPerKey[$baseKey]++;

        $occurrence = $counterPerKey[$baseKey];
        $finalKey = $baseKey . '__' . $occurrence;

        $normalized[$finalKey] = array(
            'label' => pgv_barang_identity_label($row, $no) . (isset($counterPerKey[$baseKey]) && $counterPerKey[$baseKey] > 1 ? ' (Item ' . $occurrence . ')' : ''),
            'keterangan' => pgv_barang_keterangan($row),
            'kondisi' => pgv_barang_kondisi($row)
        );

        $no++;
    }

    return $normalized;
}

while ($row = $result->fetch_assoc()) {

    if ($jenisBA === "kerusakan") {

        /* ====== Mapping Nama Kolom BA Kerusakan ====== */
        $namaKolomMap = [
            "tanggal"              => "Tanggal",
            "nomor_ba"             => "Nomor BA",
            "jenis_perangkat"      => "Jenis Perangkat",
            "merek"                => "Merek",
            "no_po"                => "Nomor PO",
            "deskripsi"            => "Jenis Kerusakan",
            "sn"                   => "SN",
            "penyebab_kerusakan"   => "Penyebab Kerusakan",
            "kategori_kerusakan_id" => "Kategori Kerusakan",
            "keterangan_dll"       => "Keterangan Tambahan",
            "rekomendasi_mis"      => "Rekomendasi MIS",
            "pembuat"              => "Pembuat",
            "penyetujui"           => "Penyetujui",
            "peminjam"             => "Pengguna",
            "atasan_peminjam"      => "Atasan Pengguna",
            "diketahui"            => "Diketahui"
        ];

        $dataFiltered = [];

        foreach ($kolomKhususKerusakan as $k) {
            $nilai = isset($row[$k]) ? $row[$k] : "";

            if ($nilai === "" || $nilai === "-") {
                $nilai = "kosong";
            }

            if ($k === "kategori_kerusakan_id") {
                $kategoriID = intval($row[$k]);

                $qKategori = $koneksi->query("
                    SELECT nama FROM categories_broken 
                    WHERE id = $kategoriID LIMIT 1
                ");

                if ($qKategori && $qKategori->num_rows > 0) {
                    $kat = $qKategori->fetch_assoc();
                    $nilai = $kat["nama"];
                } else {
                    $nilai = "kosong";
                }
            }

            $dataFiltered[$k] = $nilai;
        }

        if ($row["status"] == 0) {
            $dataLama = $dataFiltered;
        }
        if ($row["status"] == 1) {
            $dataBaru = $dataFiltered;
        }
    } else if ($jenisBA === "mutasi") {

        $sqlBarang = "
            SELECT merk, sn, status
            FROM history_n_temp_barang_mutasi
            WHERE id_ba = $id_ba
            AND pending_status = 1
            ORDER BY id ASC
        ";
        $resBarang = $koneksi->query($sqlBarang);

        $listLama = [];
        $listBaru = [];

        while ($b = $resBarang->fetch_assoc()) {

            $merk = trim($b["merk"]);
            $sn   = trim($b["sn"]);

            if ($merk === "" && $sn === "") continue;

            // $gabung = "{$merk} ({$sn})";
            $gabung = "{$merk}";

            if ($b["status"] == 0) {
                $listLama[] = $gabung;
            } else if ($b["status"] == 1) {
                $listBaru[] = $gabung;
            }
        }

        // Format akhir "merk(sn), merk(sn)"
        $data_barang_lama = implode(" ,\n ", $listLama);
        $data_barang_baru = implode(" ,\n ", $listBaru);

        /* ====== Mapping Nama Kolom BA mutasi ====== */
        $namaKolomMap = [
            "tanggal"              => "Tanggal",
            "nomor_ba"             => "Nomor BA",
            "pt_asal"              => "PT Asal",
            "pt_tujuan"            => "PT Tujuan",
            "keterangan"           => "Keterangan",
            "pengirim1"            => "Pengirim",
            "pengirim2"            => "Pengirim 2",
            "hrd_ga_pengirim"      => "Staf GA Pengirim",
            "penerima1"            => "Penerima",
            "penerima2"            => "Penerima 2",
            "hrd_ga_penerima"      => "Staf GA Penerima",
            "diketahui"            => "Diketahui",
            "pemeriksa"            => "Pemeriksa",
            "pemeriksa2"           => "Pemeriksa 2",
            "penyetujui"           => "Penyetujui",
            "penyetujui2"          => "Penyetujui 2",
            "Barang"               => "Barang"
        ];

        $dataFiltered = [];

        foreach ($kolomKhususMutasi as $k) {
            $nilai = isset($row[$k]) ? $row[$k] : "";

            if ($nilai === "" || $nilai === "-") {
                $nilai = "kosong";
            }

            $dataFiltered[$k] = $nilai;
        }

        if ($row["status"] == 0) {
            $dataFiltered["Barang"] = $data_barang_lama;
            $dataLama = $dataFiltered;
        }
        if ($row["status"] == 1) {
            $dataFiltered["Barang"] = $data_barang_baru;
            $dataBaru = $dataFiltered;
        }
    } elseif ($jenisBA === "pengembalian") {

        /* ====== Mapping Nama Kolom BA Pengembalian ====== */
        $namaKolomMap = array(
            "tanggal"             => "Tanggal",
            "nomor_ba"            => "Nomor BA",
            "pt"                  => "PT",
            "pengembali"          => "Yang Menyerahkan",
            "jabatan_pengembali"  => "Jabatan Yang Menyerahkan",
            "penerima"            => "Penerima",
            "jabatan_penerima"    => "Jabatan Penerima",
            "diketahui"           => "Yang Mengetahui",
            "jabatan_diketahui"   => "Jabatan Yang Mengetahui",
            "barang_jumlah"       => "Jumlah Barang"
        );

        $kolomKhususPengembalian = array(
            "tanggal",
            "nomor_ba",
            "pt",
            "pengembali",
            "jabatan_pengembali",
            "penerima",
            "jabatan_penerima",
            "diketahui",
            "jabatan_diketahui"
        );

        $dataFiltered = array();

        foreach ($kolomKhususPengembalian as $k) {
            $nilai = isset($row[$k]) ? $row[$k] : "";

            if ($nilai === "" || $nilai === "-") {
                $nilai = "kosong";
            }

            $dataFiltered[$k] = $nilai;
        }

        /*
        |--------------------------------------------------------------------------
        | Ambil histori barang BA Pengembalian
        | status=0 => lama
        | status=1 => baru
        |--------------------------------------------------------------------------
        */
        $sqlBarangPengembalian = "
            SELECT *
            FROM history_n_temp_barang_pengembalian_v2
            WHERE id_ba = $id_ba
              AND pending_status = 1
            ORDER BY status ASC, id ASC
        ";
        $resBarangPengembalian = $koneksi->query($sqlBarangPengembalian);

        $barangLamaRaw = array();
        $barangBaruRaw = array();

        if ($resBarangPengembalian) {
            while ($b = $resBarangPengembalian->fetch_assoc()) {
                if ((int)$b["status"] === 0) {
                    $barangLamaRaw[] = $b;
                } elseif ((int)$b["status"] === 1) {
                    $barangBaruRaw[] = $b;
                }
            }
        }

        $barangLamaNorm = pgv_normalize_barang_rows($barangLamaRaw);
        $barangBaruNorm = pgv_normalize_barang_rows($barangBaruRaw);

        $allBarangKeys = array_unique(
            array_merge(
                array_keys($barangLamaNorm),
                array_keys($barangBaruNorm)
            )
        );

        sort($allBarangKeys);

        $dataFiltered["barang_jumlah"] = "Jumlah lama: " . count($barangLamaRaw);

        foreach ($allBarangKeys as $idx => $barangKey) {
            $urutan = $idx + 1;

            $lamaItem = isset($barangLamaNorm[$barangKey]) ? $barangLamaNorm[$barangKey] : null;
            $baruItem = isset($barangBaruNorm[$barangKey]) ? $barangBaruNorm[$barangKey] : null;

            $labelBarang = "Barang #" . $urutan;
            if ($lamaItem && isset($lamaItem["label"])) {
                $labelBarang = $lamaItem["label"];
            } elseif ($baruItem && isset($baruItem["label"])) {
                $labelBarang = $baruItem["label"];
            }

            $fieldIdentitas  = "barang_identitas__" . $urutan;
            $fieldKeterangan = "barang_keterangan__" . $urutan;
            $fieldKondisi    = "barang_kondisi__" . $urutan;

            $namaKolomMap[$fieldIdentitas]  = "Identitas " . $labelBarang;
            $namaKolomMap[$fieldKeterangan] = "Keterangan " . $labelBarang;
            $namaKolomMap[$fieldKondisi]    = "Kondisi " . $labelBarang;

            $dataFiltered[$fieldIdentitas] = $lamaItem ? $lamaItem["label"] : "kosong";
            $dataFiltered[$fieldKeterangan] = $lamaItem ? $lamaItem["keterangan"] : "kosong";
            $dataFiltered[$fieldKondisi] = $lamaItem ? $lamaItem["kondisi"] : "kosong";
        }

        if ((int)$row["status"] === 0) {
            $dataLama = $dataFiltered;
        }

        if ((int)$row["status"] === 1) {
            $dataFilteredBaru = array();

            foreach ($kolomKhususPengembalian as $k) {
                $nilai = isset($row[$k]) ? $row[$k] : "";

                if ($nilai === "" || $nilai === "-") {
                    $nilai = "kosong";
                }

                $dataFilteredBaru[$k] = $nilai;
            }

            $dataFilteredBaru["barang_jumlah"] = "Jumlah baru: " . count($barangBaruRaw);

            foreach ($allBarangKeys as $idx => $barangKey) {
                $urutan = $idx + 1;

                $lamaItem = isset($barangLamaNorm[$barangKey]) ? $barangLamaNorm[$barangKey] : null;
                $baruItem = isset($barangBaruNorm[$barangKey]) ? $barangBaruNorm[$barangKey] : null;

                $fieldIdentitas  = "barang_identitas__" . $urutan;
                $fieldKeterangan = "barang_keterangan__" . $urutan;
                $fieldKondisi    = "barang_kondisi__" . $urutan;

                $dataFilteredBaru[$fieldIdentitas] = $baruItem ? $baruItem["label"] : "kosong";
                $dataFilteredBaru[$fieldKeterangan] = $baruItem ? $baruItem["keterangan"] : "kosong";
                $dataFilteredBaru[$fieldKondisi] = $baruItem ? $baruItem["kondisi"] : "kosong";
            }

            $dataBaru = $dataFilteredBaru;
        }

    } elseif ($jenisBA === "serah_terima_asset") {

        /* ====== Mapping Nama Kolom BA Kerusakan ====== */
        $namaKolomMap = [
            "tanggal"               => "Tanggal",
            "nomor_ba"              => "Nomor BA",
            "pt"                    => "PT",
            "merek"                 => "Merek",
            "sn"                    => "Serial Number",
            "peminjam"              => "Peminjam",
            "saksi"                 => "Saksi",
            "diketahui"             => "Diketahui",
            "pihak_pertama"         => "Direksi MIS",
            "alamat_peminjam"       => "Alamat Peminjam"
        ];

        $dataFiltered = [];

        foreach ($kolomKhususSTAsset as $k) {
            $nilai = isset($row[$k]) ? $row[$k] : "";

            if ($nilai === "" || $nilai === "-") {
                $nilai = "kosong";
            }

            $dataFiltered[$k] = $nilai;
        }

        if ($row["status"] == 0) {
            $dataLama = $dataFiltered;
        }
        if ($row["status"] == 1) {
            $dataBaru = $dataFiltered;
        }
    } elseif ($jenisBA === "pemutihan") {

        $ptPemutihan = isset($row["pt"]) ? trim($row["pt"]) : "";
        $isHOPemutihan = ($ptPemutihan === "PT.MSAL (HO)");

        /* ====== Mapping Nama Kolom BA Pemutihan ====== */
        if ($isHOPemutihan) {
            $namaKolomMap = [
                "tanggal"            => "Tanggal",
                "nomor_ba"           => "Nomor BA",
                "pt"                 => "PT",
                "pembuat"            => "Pembuat",
                "jabatan_pembuat"    => "Jabatan Pembuat",
                "pemeriksa"          => "Pemeriksa",
                "jabatan_pemeriksa"  => "Jabatan Pemeriksa",
                "Barang"             => "Barang"
            ];

            $kolomDipakai = $kolomKhususPemutihanHO;
        } else {
            $namaKolomMap = [
                "tanggal"                  => "Tanggal",
                "nomor_ba"                 => "Nomor BA",
                "pt"                       => "PT",
                "pembuat_site"             => "Pembuat Site",
                "jabatan_pembuat_site"     => "Jabatan Pembuat Site",
                "pemeriksa_site"           => "Pemeriksa Site",
                "jabatan_pemeriksa_site"   => "Jabatan Pemeriksa Site",
                "diketahui1_site"          => "Diketahui Site",
                "jabatan_diketahui1_site"  => "Jabatan Diketahui Site",
                "disetujui1_site"          => "Disetujui Site",
                "jabatan_disetujui1_site"  => "Jabatan Disetujui Site",
                "Barang"                   => "Barang"
            ];

            $kolomDipakai = $kolomKhususPemutihanSite;
        }

        /* ====== Ambil histori barang pemutihan ====== */
        $sqlBarangPemutihan = "
        SELECT 
            pt,
            po,
            coa,
            kode_assets,
            merk,
            sn,
            user,
            harga_beli,
            tahun_perolehan,
            alasan_penghapusan,
            kondisi,
            status
        FROM history_n_temp_barang_pemutihan
        WHERE id_ba = $id_ba
          AND pending_status = 1
        ORDER BY id ASC
    ";
        $resBarangPemutihan = $koneksi->query($sqlBarangPemutihan);

        $listBarangLama = [];
        $listBarangBaru = [];

        while ($b = $resBarangPemutihan->fetch_assoc()) {

            $poBarang      = isset($b["po"]) ? trim($b["po"]) : "";
            $merkBarang    = isset($b["merk"]) ? trim($b["merk"]) : "";
            $alasanBarang  = isset($b["alasan_penghapusan"]) ? trim($b["alasan_penghapusan"]) : "";
            $kondisiBarang = isset($b["kondisi"]) ? trim($b["kondisi"]) : "";

            if ($poBarang === "" || $poBarang === "-") $poBarang = "kosong";
            if ($merkBarang === "" || $merkBarang === "-") $merkBarang = "kosong";
            if ($alasanBarang === "" || $alasanBarang === "-") $alasanBarang = "kosong";
            if ($kondisiBarang === "" || $kondisiBarang === "-") $kondisiBarang = "kosong";

            $gabung =
                "PO: " . $poBarang . ", " .
                "MERK: " . $merkBarang . ", " .
                "ALASAN PENGHAPUSAN: " . $alasanBarang . ", " .
                "KONDISI: " . $kondisiBarang;

            if ((int)$b["status"] === 0) {
                $listBarangLama[] = $gabung;
            } elseif ((int)$b["status"] === 1) {
                $listBarangBaru[] = $gabung;
            }
        }

        $dataBarangLama = count($listBarangLama) > 0 ? implode("<br>", $listBarangLama) : "kosong";
        $dataBarangBaru = count($listBarangBaru) > 0 ? implode("<br>", $listBarangBaru) : "kosong";

        /* ====== Filter data BA Pemutihan ====== */
        $dataFiltered = [];

        foreach ($kolomDipakai as $k) {
            $nilai = isset($row[$k]) ? $row[$k] : "";

            if ($nilai === "" || $nilai === "-") {
                $nilai = "kosong";
            }

            $dataFiltered[$k] = $nilai;
        }

        if ((int)$row["status"] === 0) {
            $dataFiltered["Barang"] = $dataBarangLama;
            $dataLama = $dataFiltered;
        }

        if ((int)$row["status"] === 1) {
            $dataFiltered["Barang"] = $dataBarangBaru;
            $dataBaru = $dataFiltered;
        }
    } else {
        if ($row["status"] == 0) {
            $dataLama = $row;
        }
        if ($row["status"] == 1) {
            $dataBaru = $row;
        }
    }
}

echo json_encode([
    "lama" => $dataLama,
    "baru" => $dataBaru,
    "namaKolom" => $namaKolomMap,
    "alasan_edit" => $alasan_edit
]);
// echo "<pre>";
// print_r($dataLama);
// echo "<br>";
// print_r($dataBaru);
// echo "<br>";
// print_r($namaKolomMap);
// // echo "<br>";
// // var_export($ada_perubahan);
// // print_r([
// //     'pt_asal' => $pt_asal_list,
// //     'po'      => $po_list,
// //     'coa'     => $coa_list,
// //     'kode'    => $kode_list,
// //     'merk'    => $merk_list,
// //     'sn'      => $sn_list,
// //     'user'    => $user_list
// // ]);
// // print_r($barang_data);
// echo "</pre>";
// exit();
