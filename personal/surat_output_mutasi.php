<?php
session_start();

// Jika belum login, arahkan ke halaman login
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../login_registrasi.php");
    exit();
}

// Validasi ID dari query string
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID tidak valid.");
}

require_once "../koneksi.php"; // path koneksi biarkan sesuai sistem kamu

$id_ba = intval($_GET['id']);

// --- Ambil data utama berita acara ---
$query = "SELECT * FROM berita_acara_mutasi WHERE id = $id_ba";
$result = mysqli_query($koneksi, $query);

if (!$result || mysqli_num_rows($result) === 0) {
    die("Data tidak ditemukan untuk ID: $id_ba");
}

$data_ba = mysqli_fetch_assoc($result);

// --- Ambil data barang terkait ---
$query_barang = "SELECT * FROM barang_mutasi WHERE id_ba = $id_ba";
$result_barang = mysqli_query($koneksi, $query_barang);
$barang_list = [];
while ($row = mysqli_fetch_assoc($result_barang)) {
    $barang_list[] = $row;
}

// --- Ambil data gambar terkait ---
$query_gambar = "SELECT * FROM gambar_ba_mutasi WHERE id_ba = $id_ba";
$result_gambar = mysqli_query($koneksi, $query_gambar);
$gambar_list = [];
while ($row = mysqli_fetch_assoc($result_gambar)) {
    $gambar_list[] = $row;
}

// --- Fungsi ubah bulan ke romawi ---
function bulanRomawi($tanggal) {
    if (empty($tanggal)) return '';
    $bulan = date('n', strtotime($tanggal));
    $romawi = [
        1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV',
        5 => 'V', 6 => 'VI', 7 => 'VII', 8 => 'VIII',
        9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII'
    ];
    return isset($romawi[$bulan]) ? $romawi[$bulan] : '';

}

// --- Format nomor surat ---
$nomor_ba = htmlspecialchars($data_ba['nomor_ba']);
$tanggal_ba = $data_ba['tanggal'];
$bulan_romawi = bulanRomawi($tanggal_ba);
$tahun_ba = date('Y', strtotime($tanggal_ba));

$nomor_surat = "No : {$nomor_ba}/BAMTA/MIS/{$bulan_romawi}/{$tahun_ba}";

// === KONVERSI NAMA PT ===
function ubahNamaPT($kode) {
    $map = [
        "PT.MSAL (HO)"   => "PT MULIA SAWIT AGRO LESTARI HEAD OFFICE",
        'PT.MSAL (PKS)'  => "PT MULIA SAWIT AGRO LESTARI (PKS)",
        "PT.MSAL (SITE)" => "PT MULIA SAWIT AGRO LESTARI SITE",
        'PT.PSAM (PKS)'  => "PT PERSADA SEJAHTERA AGRO MAKMUR (PKS)",
        'PT.PSAM (SITE)' => "PT PERSADA SEJAHTERA AGRO MAKMUR SITE",
        "PT.MAPA"        => "PT MITRA AGRO PERSADA ABADI",
        "PT.PEAK (PKS)"  => "PT PERSADA ERA AGRO KENCANA (PKS)",
        "PT.PEAK (SITE)" => "PT PERSADA ERA AGRO KENCANA SITE",
        "RO PALANGKARAYA"=> "RO PALANGKARAYA",
        "RO SAMPIT"      => "RO SAMPIT",
        "PT.WCJU (SITE)" => "PT WANA CATUR JAYA UTAMA SITE",
        "PT.WCJU (PKS)"   => "PT WANA CATUR JAYA UTAMA (PKS)"
        
    ];
    return isset($map[$kode]) ? $map[$kode] : $kode;
}

$pt_asal = ubahNamaPT($data_ba['pt_asal']);
$pt_tujuan = ubahNamaPT($data_ba['pt_tujuan']);

// === KONVERSI HARI & TANGGAL INDONESIA ===
$hariIndo = [
    'Sunday'    => 'Minggu',
    'Monday'    => 'Senin',
    'Tuesday'   => 'Selasa',
    'Wednesday' => 'Rabu',
    'Thursday'  => 'Kamis',
    'Friday'    => 'Jumat',
    'Saturday'  => 'Sabtu'
];
$bulanIndo = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];

$hari_tanggal = $hariIndo[date('l', strtotime($tanggal_ba))];
$tgl_angka = date('j', strtotime($tanggal_ba));
$bulan_tulisan = $bulanIndo[date('n', strtotime($tanggal_ba))];
$tahun_tulisan = date('Y', strtotime($tanggal_ba));
$created_at = $data_ba["created_at"];
$jam = date('H:i',strtotime($created_at));

// === TEKS DINAMIS ===
$paragraf_mutasi = "
<p style='padding-left:1px'>
Pada Hari ini {$hari_tanggal}, Tanggal {$tgl_angka} bulan {$bulan_tulisan} Tahun {$tahun_tulisan} 
Pukul {$jam} telah dilaksanakan mutasi Aset dari unit penanggung jawab Aset milik {$pt_asal} 
kepada {$pt_tujuan}.
</p>";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Berita Acara Mutasi Aset - <?= htmlspecialchars($nomor_surat) ?></title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 8pt;
            margin: 25px 40px;
        }
        .header {
            font-size: 12pt;
            text-align: center;
            font-weight: bold;
        }
        .header-left {
            text-align: left;
            font-weight: bold;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        .no-border td {
            border: none;
            vertical-align: top;
        }
        .main-table, .main-table th, .main-table td {
            border: none;
        }
        .main-table th, .main-table td {
            padding: 4px;
            text-align: center;
            border: 1px solid black;
        }
        .main-table th:first-child, .main-table td:first-child {
            border-left: none;
        }
        .main-table th:last-child, .main-table td:last-child{
            border-right: none;
        }
        .signature-table td {
            vertical-align: top;
            text-align: center;
            height: 80px;
        }
        .signature-label {
            font-weight: normal;
        }
        .stempel {
            font-style: italic;
            font-size: 11pt;
        }
        .foot-table{
          border-top: 1px solid black;border-bottom: none;
        }
        .foot-table, .foot-table th, .foot-table td {
            border-collapse: collapse;
            text-align: center;
            padding: 4px;
        }
        .foot-table th {
            border: 1px solid black;
        }
        .foot-table tr:first-child th{
          font-size: 12pt;
          margin: 0;
          padding: 0;
        }
        .foot-table tr:first-child th, 
        .foot-table tr:nth-child(2) th:first-child,
        .foot-table tr:nth-child(3) td:first-child, 
        .foot-table tr:last-child td:first-child
        {
            border-left: none;
        }
        .foot-table tr:first-child th, 
        .foot-table tr:nth-child(2) th:last-child,
        .foot-table tr:nth-child(3) td:last-child, 
        .foot-table tr:last-child td:last-child
        {
            border-right: none;
        }
        .foot-table tr:nth-child(3) td, .foot-table tr:last-child td{
            border: 1px solid black;
        }
        .foot-table tr:last-child td{
          border-bottom: none;
        }
        .small-note {
            font-size: 8pt;
            font-style: italic;
            margin-top: 0;
        }
        hr {
            border: none;
            border-top: 1px solid black;
        }
        #printBtn {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            padding: 10px 18px;
            background: #b31a1a;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
        }

        #printBtn:hover {
            background: #8a2929;
        }

        /* Hilangkan tombol saat print */
        @media print {
            #printBtn {
                display: none !important;
            }
        }
    </style>
    <style>
        /*Media Print*/
        @media print {
            .page-break {
                page-break-before: always;
            }
        }

        /* Jaga skala surat tetap proporsional di resolusi kecil */
        @media screen and (max-width: 1919px) {
            body {
                transform: scale(0.9);
                transform-origin: top center;
            }
        }

        /* Tambahan jika layar di bawah 1366px */
        @media screen and (max-width: 1366px) {
            body {
                transform: scale(0.8);
                transform-origin: top center;
            }
        }

        /* Untuk tampilan print: tetap ukuran normal */
        @media print {
            body {
                transform: none !important;
            }
        }
        @media print {
            @page {
                size: A4 landscape;
                margin: 10mm;
            }
        }

    </style>
</head>
<button id="printBtn" onclick="window.print()">Print / Save PDF</button>
<body>

    <div class="header-left"><?= htmlspecialchars($pt_asal) ?></div>
    <div class="header">
        BERITA ACARA MUTASI ASET<br>
        <span style="font-weight: normal;font-size:10pt;"><?= htmlspecialchars($nomor_surat) ?></span>
    </div>
    <div style="border:1px solid black;">
    <table style="border:none;">
        <tr>
            <td><?= $paragraf_mutasi ?></td>
        </tr>
        <tr>
            <td style='padding-left:1px'>Adapun Aset yang dimaksud adalah sebagai berikut :</td>
        </tr>
    </table>

    <table class="main-table">
      <thead>
        <tr>
            <th style="width:4%">No</th>
            <th style="width:10%">Kode Aset</th>
            <th style="width:12%">Nama Barang</th>
            <th style="width:8%">No. SN</th>
            <th style="width:9%">No. PO</th>
            <th style="width:8%">Satuan</th>
            <th style="width:8%">Jumlah</th>
            <th style="width:10%">Lokasi Asal</th>
            <th style="width:10%">Lokasi Tujuan</th>
            <th style="width:10%">No. BKB *</th>
            <th style="width:11%">Keterangan **</th>
        </tr>
      </thead>
<tbody>
<?php
if (!empty($barang_list)) {
    // === 1. Gabungkan barang dengan nama/merk yang sama ===
    $barang_gabungan = [];
    foreach ($barang_list as $b) {
        $coa = trim($b['kode_assets']);
        $merk = trim($b['merk']);
        $satuan = !empty($b['satuan']) ? $b['satuan'] : 'Unit';
        $po = trim($b['po']);
        $sn = trim($b['sn']);
        // $jumlah = intval($b['jumlah'] ?? 1);
        $jumlah = isset($b['jumlah']) ? intval($b['jumlah']) : 1;

        // Gunakan kombinasi COA + Merk sebagai kunci unik
        $key = $coa . '|' . $merk;

        if (!isset($barang_gabungan[$key])) {
            $barang_gabungan[$key] = [
                'coa' => $coa,
                'merk' => $merk,
                'satuan' => $satuan,
                'po' => $po,
                'sn' => $sn,
                'jumlah' => $jumlah
            ];
        } else {
            // Jika sudah ada, tambahkan jumlah
            $barang_gabungan[$key]['jumlah'] += $jumlah;
        }
    }

    // === 2. Hitung total baris hasil penggabungan ===
    $total_rows = count($barang_gabungan);
    $rowIndex = 0;
    $no = 1;

    // === 3. Tampilkan data ===
    foreach ($barang_gabungan as $item) {
        echo "<tr>";

        // Nomor urut
        echo "<td>{$no}</td>";

        // Kode Barang / COA
        echo "<td>" . htmlspecialchars($item['coa']) . "</td>";

        // Nama Barang
        echo "<td>" . htmlspecialchars($item['merk']) . "</td>";

        echo "<td>" . htmlspecialchars($item['sn']) . "</td>";

        echo "<td>" . htmlspecialchars($item['po']) . "</td>";

        // Satuan
        echo "<td>" . htmlspecialchars($item['satuan']) . "</td>";

        // Jumlah
        echo "<td>" . htmlspecialchars($item['jumlah']) . "</td>";

        // PT Asal dan PT Tujuan hanya muncul sekali
        if ($rowIndex === 0) {
            $pt_asal = htmlspecialchars($data_ba['pt_asal']);
            $pt_tujuan = htmlspecialchars($data_ba['pt_tujuan']);
            echo "<td rowspan='{$total_rows}'>{$pt_asal}</td>";
            echo "<td rowspan='{$total_rows}'>{$pt_tujuan}</td>";
        

            // No. BKB
            echo "<td rowspan='{$total_rows}'></td>";
        }
        // Keterangan hanya sekali juga
        if ($rowIndex === 0) {
            $keterangan = htmlspecialchars($data_ba['keterangan'] ?: '-');
            echo "<td rowspan='{$total_rows}'>{$keterangan}</td>";
        }

        echo "</tr>";
        $no++;
        $rowIndex++;
    }
} else {
    echo '<tr><td colspan="9" class="text-center"></td></tr>';
}
?>
</tbody>

    </table>

    <p class="small-note" style="padding-left:1px">*) Jika Barang dari gudang diisi No. BKB<br>
    **) Sebutkan kondisi Aset yang diterima.</p>

    <p style="padding-left:1px">Demikian Berita Acara ini dilaksanakan.</p>

    <?php
    // Fungsi ubah tanggal ke format Indonesia (PHP 5.6 compatible)
    function formatTanggalIndo($tanggal) {
        if (!$tanggal) return '';
        $bulan = array(
            1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
        );
        $pecah = explode('-', $tanggal);
        if (count($pecah) !== 3) return '';
        return intval($pecah[2]) . ' ' . $bulan[intval($pecah[1])] . ' ' . $pecah[0];
    }
    ?>

    <table class="no-border" style="margin-top:10px;">
        <tr style="font-weight: 600;">
            <td style="width:33%; text-align:left;">Yang menyerahkan Aset :</td>
            <td style="width:33%; text-align:center;"></td>
            <td style="width:33%; text-align:left;">Yang menerima :</td>
        </tr>

        <tr>
            <td style="z-index:-1; height:45px; text-align:left; position: relative; display: flex; justify-content: center;">
                <div style="width: 150px;">
                <?php
                $blob = isset($data_ba['autograph_3']) ? $data_ba['autograph_3'] : null;
                $tgl  = isset($data_ba['tanggal_approve_3']) ? $data_ba['tanggal_approve_3'] : null;
                if (!empty($blob)) {
                    echo '<div style="position: relative; width:100%; height:100%;">';
                    echo '<div style="position:absolute;top:0;left:0;font-size:8px;color:#444;">Digital Sign</div>';
                    echo '<img src="data:image/png;base64,' . base64_encode($blob) . '" style="max-height:45px; display:block; margin:0 auto;">';
                    if (!empty($tgl)) {
                        echo '<div style="position:absolute; bottom:-5px; right:5px; font-size:8px;color:#444;">' . formatTanggalIndo($tgl) . '</div>';
                    }
                    echo '</div>';
                }
                ?>
                </div>
            </td>
            <td style="height:45px; text-align:center;"></td>
            <td style="z-index:-1; height:45px; text-align:left; position: relative; display: flex; justify-content: center;">
                <div style="width: 150px;">
                <?php
                $blob = isset($data_ba['autograph_6']) ? $data_ba['autograph_6'] : null;
                $tgl  = isset($data_ba['tanggal_approve_6']) ? $data_ba['tanggal_approve_6'] : null;
                if (!empty($blob)) {
                    echo '<div style="position: relative; width:100%; height:100%;">';
                    echo '<div style="position:absolute;top:0;left:0;font-size:8px;color:#444;">Digital Sign</div>';
                    echo '<img src="data:image/png;base64,' . base64_encode($blob) . '" style="max-height:45px; display:block; margin:0 auto;">';
                    if (!empty($tgl)) {
                        echo '<div style="position:absolute; bottom:-5px; right:5px; font-size:8px;color:#444;">' . formatTanggalIndo($tgl) . '</div>';
                    }
                    echo '</div>';
                }
                ?>
                </div>
            </td>
        </tr>
        <tr>
            <td style="text-align:left;"><div style="width: 100%; text-align: center;"><?= htmlspecialchars($data_ba['hrd_ga_pengirim'] ?: '') ?></div></td>
            <td style="text-align:center;"></td>
            <td style="text-align:left;"><div style="width: 100%; text-align: center;"><?= htmlspecialchars($data_ba['hrd_ga_penerima'] ?: '') ?></div></td>
        </tr>
        <tr>
            <td><div style="margin-left:37%; width: 25%; text-align: center;border-top:1px solid black;"></div></td>
            <td style="text-align:center;"></td>
            <td><div style="margin-left:37%; width: 25%; text-align: center;border-top:1px solid black;"></div></td>
        </tr>
        <tr style="font-weight: 600;">
            <?php if($data_ba['pt_asal'] === 'PT.MSAL (HO)'){ ?>
            <td style="text-align:left;"><div style="width: 100%; text-align: center;">Staff GA</div></td>
            <?php } else { ?>
            <td style="text-align:left;"><div style="width: 100%; text-align: center;">HRD</div></td>
            <?php } ?>

            <td style="text-align:center;"></td>


            <?php if($data_ba['pt_tujuan'] === 'PT.MSAL (HO)'){ ?>
            <td style="text-align:left;"><div style="width: 100%; text-align: center;">Staff GA</div></td>
            <?php } else { ?>
            <td style="text-align:left;"><div style="width: 100%; text-align: center;">HRD</div></td>
            <?php } ?>
        </tr>

        <tr>
            <td style="text-align:left;"></td>
            <td style="text-align:center;"><div style="width: 100%; text-align: center;font-weight: 600;">Diperiksa :</div></td>
            <td style="text-align:left;"></td>
        </tr>

        <tr>
            <td style="z-index:-1; height:45px; text-align:left; position: relative; display: flex; justify-content: center;">
                <div style="width: 150px;">
                <?php
                $blob = isset($data_ba['autograph_1']) ? $data_ba['autograph_1'] : null;
                $tgl  = isset($data_ba['tanggal_approve_1']) ? $data_ba['tanggal_approve_1'] : null;
                if (!empty($blob)) {
                    echo '<div style="position: relative; width:100%; height:100%;">';
                    echo '<div style="position:absolute;top:0;left:0;font-size:8px;color:#444;">Digital Sign</div>';
                    echo '<img src="data:image/png;base64,' . base64_encode($blob) . '" style="max-height:45px; display:block; margin:0 auto;">';
                    if (!empty($tgl)) {
                        echo '<div style="position:absolute; bottom:-5px; right:5px; font-size:8px;color:#444;">' . formatTanggalIndo($tgl) . '</div>';
                    }
                    echo '</div>';
                }
                ?>
                </div>
            </td>
            <td style="height:45px;"></td>
            <td style="z-index:-1; height:45px; text-align:left; position: relative; display: flex; justify-content: center;">
                <div style="width: 150px;">
                <?php
                $blob = isset($data_ba['autograph_4']) ? $data_ba['autograph_4'] : null;
                $tgl  = isset($data_ba['tanggal_approve_4']) ? $data_ba['tanggal_approve_4'] : null;
                if (!empty($blob)) {
                    echo '<div style="position: relative; width:100%; height:100%;">';
                    echo '<div style="position:absolute;top:0;left:0;font-size:8px;color:#444;">Digital Sign</div>';
                    echo '<img src="data:image/png;base64,' . base64_encode($blob) . '" style="max-height:45px; display:block; margin:0 auto;">';
                    if (!empty($tgl)) {
                        echo '<div style="position:absolute; bottom:-5px; right:5px; font-size:8px;color:#444;">' . formatTanggalIndo($tgl) . '</div>';
                    }
                    echo '</div>';
                }
                ?>
                </div>
            </td>
        </tr>
        <tr>
            <td style="text-align:left;"><div style="width: 100%; text-align: center;"><?= htmlspecialchars($data_ba['pengirim1'] ?: '') ?></div></td>
            <td style="text-align:center;"></td>
            <td style="text-align:left;"><div style="width: 100%; text-align: center;"><?= htmlspecialchars($data_ba['penerima1'] ?: '') ?></div></td>
        </tr>

        <tr>
            <td><div style="margin-left:37%; width: 25%; text-align: center;border-top:1px solid black;"></div></td>
            <td style="text-align:center;"></td>
            <td><div style="margin-left:37%; width: 25%; text-align: center;border-top:1px solid black;"></div></td>
        </tr>

        <tr style="font-weight: 600;">
            <?php if($data_ba['pt_asal'] === 'PT.MSAL (HO)'){ ?>
            <td style="text-align:left;"><div style="width: 100%; text-align: center;">Staff MIS</div></td>
            <?php } else { ?>
            <td style="text-align:left;"><div style="width: 100%; text-align: center;">KTU</div></td>
            <?php } ?>

            <td style="text-align:center;"></td>

            <?php if($data_ba['pt_tujuan'] === 'PT.MSAL (HO)'){ ?>
            <td style="text-align:left;"><div style="width: 100%; text-align: center;">Staff MIS</div></td>
            <?php } else { ?>
            <td style="text-align:left;"><div style="width: 100%; text-align: center;">KTU</div></td>
            <?php } ?>
        </tr>

        <tr>
            <td style="text-align:left;"></td>
            <td style="text-align:center;"><div style="width: 100%; text-align: center; font-weight: 600;">Diketahui :</div></td>
            <td style="text-align:left;"></td>
        </tr>
        <tr>
            <td style="z-index:-1; height:45px; text-align:left; position: relative; display: flex; justify-content: center;">
                <div style="width: 150px;">
                <?php
                $blob = isset($data_ba['autograph_2']) ? $data_ba['autograph_2'] : null;
                $tgl  = isset($data_ba['tanggal_approve_2']) ? $data_ba['tanggal_approve_2'] : null;
                if (!empty($blob)) {
                    echo '<div style="position: relative; width:100%; height:100%;">';
                    echo '<div style="position:absolute;top:0;left:0;font-size:8px;color:#444;">Digital Sign</div>';
                    echo '<img src="data:image/png;base64,' . base64_encode($blob) . '" style="max-height:45px; display:block; margin:0 auto;">';
                    if (!empty($tgl)) {
                        echo '<div style="position:absolute; bottom:-5px; right:5px; font-size:8px;color:#444;">' . formatTanggalIndo($tgl) . '</div>';
                    }
                    echo '</div>';
                }
                ?>
                </div>

            </td>
            <td style="height:45px;"></td>
            <td style="z-index:-1; height:45px; text-align:left; position: relative; display: flex; justify-content: center;">
                <div style="width: 150px;">
                <?php
                $blob = isset($data_ba['autograph_5']) ? $data_ba['autograph_5'] : null;
                $tgl  = isset($data_ba['tanggal_approve_5']) ? $data_ba['tanggal_approve_5'] : null;
                if (!empty($blob)) {
                    echo '<div style="position: relative; width:100%; height:100%;">';
                    echo '<div style="position:absolute;top:0;left:0;font-size:8px;color:#444;">Digital Sign</div>';
                    echo '<img src="data:image/png;base64,' . base64_encode($blob) . '" style="max-height:45px; display:block; margin:0 auto;">';
                    if (!empty($tgl)) {
                        echo '<div style="position:absolute; bottom:-5px; right:5px; font-size:8px;color:#444;">' . formatTanggalIndo($tgl) . '</div>';
                    }
                    echo '</div>';
                }
                ?>
                </div>
            </td>
        </tr>
        <tr>
            <td style="text-align:left;"><div style="width: 100%; text-align: center;"><?= htmlspecialchars($data_ba['pengirim2'] ?: '') ?></div></td>
            <td style="text-align:center;"></td>
            <td style="text-align:left;"><div style="width: 100%; text-align: center;"><?= htmlspecialchars($data_ba['penerima2'] ?: '') ?></div></td>
        </tr>
        
        <tr>
            <td><div style="margin-left:37%; width: 25%; text-align: center;border-top:1px solid black;"></div></td>
            <td style="text-align:center;"></td>
            <td><div style="margin-left:37%; width: 25%; text-align: center;border-top:1px solid black;"></div></td>
        </tr>
        <tr style="font-weight: 600;">

            <?php if($data_ba['pt_asal'] === 'PT.MSAL (HO)'){ ?>
            <td style="text-align:left;"><div style="width: 100%; text-align: center;">Staff MIS</div></td>
            <?php } else { ?>
            <td style="text-align:left;"><div style="width: 100%; text-align: center;">GM</div></td>
            <?php } ?>

            <td style="text-align:center;"></td>

            <?php if($data_ba['pt_tujuan'] === 'PT.MSAL (HO)'){ ?>
            <td style="text-align:left;"><div style="width: 100%; text-align: center;">Staff MIS</div></td>
            <?php } else { ?>
            <td style="text-align:left;"><div style="width: 100%; text-align: center;">GM</div></td>
            <?php } ?>
            
        </tr>
    </table>

    <br>

    <?php
    // Cek apakah jumlah baris barang > 5
    $page_break = ($total_rows > 5) ? 'style="page-break-before: always;"' : '';
    ?>

    <table class="foot-table"<?= $page_break ?>>
        <tr style="font-weight: 600;">
            <th colspan="6">Head Office</th>
        </tr>
        <tr style="font-weight: 600;">
            <th>Diketahui Oleh,</th>
            <th colspan="2">Diperiksa Oleh,</th>
            <th colspan="2">Disetujui Oleh,</th>
        </tr>
    <tr>
        <?php
        // Ambil data blob dan tanggal tanda tangan
        $autographs = [];
        for ($i = 7; $i <= 11; $i++) {
            // $blob = $data_ba["autograph_$i"] ?? null;
            // $tgl = $data_ba["tanggal_approve_$i"] ?? null;
            $blob   = isset($data_ba["autograph_$i"]) ? $data_ba["autograph_$i"] : null;
            $tgl    = isset($data_ba["tanggal_approve_$i"]) ? $data_ba["tanggal_approve_$i"] : null;
            $autographs[$i] = [
                'img' => $blob ? 'data:image/png;base64,' . base64_encode($blob) : null,
                'date' => $tgl ? date('d F Y', strtotime($tgl)) : null,
            ];
        }

        // Daftar kolom tanda tangan
        foreach ($autographs as $a) {
            echo '<td style="height:80px;width:20%;position:relative;text-align:center;vertical-align:bottom;">';
            if (!empty($a['img'])) {
                echo '<div style="position:relative;display:inline-block;">';
                echo '<img src="' . $a['img'] . '" alt="Tanda Tangan" style="max-width:40mm;">';
                // Tulisan Digital Sign pojok kiri atas
                echo '<span style="position:absolute;top:0;left:0;font-size:9px;color:#444;">Digital Sign</span>';
                // Tanggal pojok kanan bawah
                if (!empty($a['date'])) {
                    // Format tanggal ke Bahasa Indonesia
                    $bulan = [
                        'January' => 'Januari', 'February' => 'Februari', 'March' => 'Maret',
                        'April' => 'April', 'May' => 'Mei', 'June' => 'Juni',
                        'July' => 'Juli', 'August' => 'Agustus', 'September' => 'September',
                        'October' => 'Oktober', 'November' => 'November', 'December' => 'Desember'
                    ];
                    $tanggal = strtr($a['date'], $bulan);
                    echo '<span style="position:absolute;bottom:0;right:0;font-size:9px;color:#444;">' . $tanggal . '</span>';
                }
                echo '</div>';
            }
            echo '</td>';
        }
        ?>
    </tr>
        <tr style="font-weight: 600;">
            <!-- <?php
            // Cek nilai PT Asal
            $dept_label = ($data_ba['pt_asal'] === 'PT.MSAL (HO)') ? 'MIS' : 'IT';
            ?>
            <td >Dept. <?= htmlspecialchars($dept_label) ?></td> -->
            <td >Dept. MIS</td>
            <td>Dept HR Ops</td>
            <td>Div Accounting</td>
            <td>Dir HR</td>
            <td>Dir FA</td>
        </tr>
    </table>
    </div>
    <?php
    // Logika page break untuk bagian Lampiran
    $page_break_lampiran = ($total_rows <= 5) ? 'page-break-before: always;' : '';
    ?>
    <p style="<?= $page_break_lampiran ?> text-align: center;font-size:12pt; font-weight: 600;">LAMPIRAN<br><span style="font-size:10pt;font-weight: 500;"><?= htmlspecialchars($nomor_surat) ?></span></p>
    <?php if (!empty($gambar_list)): ?>
        <div style="display: flex; flex-wrap: wrap; justify-content: center; gap: 10px; margin-top: 20px;">
        <?php 
        $count = 0;
        foreach ($gambar_list as $g) {
            $path = htmlspecialchars($g['file_path']);
            echo '<div style="width: 30%; text-align: center; margin-bottom: 15px;">';
            echo '<img src="' . $path . '" alt="Lampiran ' . ($count+1) . '" style="width:100%; max-height:200px; object-fit:contain;">';
            echo '</div>';
            $count++;
        }
        ?>
        </div>
    <?php else: ?>
        <p class="medium" style="text-align:center; margin-top:30px;">Tidak ada gambar lampiran untuk berita acara ini.</p>
    <?php endif; ?>
</body>
</html>
