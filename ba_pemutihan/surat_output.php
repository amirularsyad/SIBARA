<?php

/**
 * surat_output.php
 * Dinamis dari:
 * - berita_acara_pemutihan
 * - barang_pemutihan
 * - gambar_pemutihan
 *
 * Catatan:
 * - Support PHP 5.6
 * - Layout template dipertahankan
 * - Aktor memakai hardcode label template, bukan jabatan_...
 * - Jika aktor = "" atau "-" maka td dihilangkan
 */

error_reporting(E_ALL & ~E_NOTICE);

/**
 * SESUAIKAN include koneksi ini dengan sistem Anda.
 * Diasumsikan menghasilkan variabel $koneksi (mysqli)
 */
include '../koneksi.php';

$id_ba = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id_ba <= 0) {
    die('ID BA tidak valid.');
}

function e($string)
{
    return htmlspecialchars((string) $string, ENT_QUOTES, 'UTF-8');
}

function isFilledActor($value)
{
    $value = trim((string) $value);
    return ($value !== '' && $value !== '-');
}
function formatPTHeader($id_pt)
{
    $id_pt_map = array(
        1 => 'PT MULIA SAWIT AGRO LESTARI',
        2 => 'PT MULIA SAWIT AGRO LESTARI PKS',
        3 => 'PT MULIA SAWIT AGRO LESTARI SITE',
        4 => 'PT PERSADA SEJAHTERA AGRO MAKMUR PKS',
        5 => 'PT PERSADA SEJAHTERA AGRO MAKMUR SITE',
        6 => 'PT MITRA AGRO PERSADA ABADI',
        7 => 'PT PERSADA ERA AGRO KENCANA PKS',
        8 => 'PT PERSADA ERA AGRO KENCANA SITE',
        11 => 'PT WANA CATUR JAYA UTAMA SITE',
        12 => 'PT WANA CATUR JAYA UTAMA PKS'
    );
    return $id_pt_map[$id_pt];
}

function formatTanggalIndo($date)
{
    if (empty($date) || $date === '0000-00-00') {
        return '-';
    }

    $bulan = array(
        1  => 'Jan',
        2  => 'Feb',
        3  => 'Mar',
        4  => 'Apr',
        5  => 'Mei',
        6  => 'Jun',
        7  => 'Jul',
        8  => 'Agu',
        9  => 'Sep',
        10 => 'Okt',
        11 => 'Nov',
        12 => 'Des'
    );

    $timestamp = strtotime($date);
    if ($timestamp === false) {
        return $date;
    }

    $hari  = date('d', $timestamp);
    $bulanIndex = (int) date('n', $timestamp);
    $tahun = date('Y', $timestamp);

    return $hari . '-' . $bulan[$bulanIndex] . '-' . $tahun;
}
function formatKodeSurat($nomor, $id_pt, $date)
{
    if (empty($date) || $date === '0000-00-00') {
        return '-';
    }

    $id_pt_map = array(
        1 => 'MSALHO',
        2 => 'MSALPKS',
        3 => 'MSALSITE',
        4 => 'PSAMPKS',
        5 => 'PSAMSITE',
        6 => 'MAPA',
        7 => 'PEAKPKS',
        8 => 'PEAKSITE',
        11 => 'WCJUSITE',
        12 => 'WCJUPKS'
    );

    $bulan = array(
        1  => "01",
        2  => "02",
        3  => "03",
        4  => "04",
        5  => "05",
        6  => "06",
        7  => "07",
        8  => "08",
        9  => "09",
        10 => "10",
        11 => "11",
        12 => "12"
    );



    $timestamp = strtotime($date);
    if ($timestamp === false) {
        return $date;
    }

    $hari  = date('d', $timestamp);
    $bulanIndex = (int) date('n', $timestamp);
    $tahun = date('y', $timestamp);

    return $nomor . '/PPA/' . $id_pt_map[$id_pt] . '/' . $bulan[$bulanIndex] . '/' . $tahun;
}


function formatRupiah($angka)
{
    if ($angka === '' || $angka === null) {
        return '';
    }

    return "Rp " . number_format((float) $angka, 0, ',', '.');
}

function formatLokasiAsset($id_pt)
{
    return $id_pt;
}

function buildLampiranRows($lampiranList)
{
    $html = '';
    $total = count($lampiranList);

    if ($total <= 0) {
        return $html;
    }

    $chunks = array_chunk($lampiranList, 3);

    foreach ($chunks as $chunk) {
        $html .= '<tr>';
        foreach ($chunk as $item) {
            $html .= '<td><img src="' . e($item['file_path']) . '" alt="lampiran"></td>';
        }
        $html .= '</tr>';

        $html .= '<tr>';
        foreach ($chunk as $item) {
            $html .= '<td><p>' . e($item['keterangan']) . '</p></td>';
        }
        $html .= '</tr>';
    }

    return $html;
}

/**
 * Ambil data BA
 */
$sqlBa = "SELECT * FROM berita_acara_pemutihan WHERE id = " . $id_ba . " LIMIT 1";
$queryBa = mysqli_query($koneksi, $sqlBa);

if (!$queryBa || mysqli_num_rows($queryBa) <= 0) {
    die('Data berita acara pemutihan tidak ditemukan.');
}

$ba = mysqli_fetch_assoc($queryBa);

/**
 * Ambil data barang
 */
$barangList = array();
$sqlBarang = "SELECT * FROM barang_pemutihan WHERE id_ba = " . $id_ba . " ORDER BY id ASC";
$queryBarang = mysqli_query($koneksi, $sqlBarang);

if ($queryBarang) {
    while ($rowBarang = mysqli_fetch_assoc($queryBarang)) {
        $barangList[] = $rowBarang;
    }
}

/**
 * Ambil data lampiran
 * Diasumsikan relasi tabel gambar_pemutihan menggunakan id_ba
 */
$lampiranList = array();
$sqlLampiran = "SELECT file_path, keterangan FROM gambar_pemutihan WHERE id_ba = " . $id_ba . " ORDER BY id ASC";
$queryLampiran = mysqli_query($koneksi, $sqlLampiran);

if ($queryLampiran) {
    while ($rowLampiran = mysqli_fetch_assoc($queryLampiran)) {
        $lampiranList[] = $rowLampiran;
    }
}

/**
 * Mapping aktor 1-16
 * Aktor dipakai hanya untuk cek tampil / hilang
 * Label tetap hardcode template
 */
$actorMap = array(
    1  => array('field' => 'dept_pengguna',         'label' => 'Dept Pengguna'),
    2  => array('field' => 'asisten_pga',           'label' => 'Asisten PGA'),
    3  => array('field' => 'kepala_mill',           'label' => 'Kepala Mill'),
    4  => array('field' => 'ktu',                   'label' => 'KTU'),
    5  => array('field' => 'area_mill_controller',  'label' => 'AMC'),
    6  => array('field' => 'group_manager',         'label' => 'Group Manager'),
    7  => array('field' => 'vice_president',        'label' => 'Vice President'),
    8  => array('field' => 'dept_avp_engineering',  'label' => 'AVP Engineering'),
    9  => array('field' => 'dept_hrops',            'label' => 'Dept HR Ops'),
    10 => array('field' => 'dept_hrd',              'label' => 'Dept HRD'),
    11 => array('field' => 'dept_accounting',       'label' => 'Dept Accounting'),
    12 => array('field' => 'dir_operation',         'label' => 'Dir Operation'),
    13 => array('field' => 'dir_finance',           'label' => 'Dir Finance'),
    14 => array('field' => 'dir_hr',                'label' => 'Dir HR'),
    15 => array('field' => 'vice_ceo',              'label' => 'Vice CEO'),
    16 => array('field' => 'ceo',                   'label' => 'CEO')
);

/**
 * Susun data signature agar mudah dipakai
 */
$signatureData = array();

for ($i = 1; $i <= 16; $i++) {
    $actorField = $actorMap[$i]['field'];
    $signatureData[$i] = array(
        'actor'           => isset($ba[$actorField]) ? $ba[$actorField] : '',
        'label'           => $actorMap[$i]['label'],
        'approval'        => isset($ba['approval_' . $i]) ? $ba['approval_' . $i] : '',
        'autograph'       => isset($ba['autograph_' . $i]) ? $ba['autograph_' . $i] : '',
        'tanggal_approve' => isset($ba['tanggal_approve_' . $i]) ? $ba['tanggal_approve_' . $i] : ''
    );
}

/**
 * Paraf kiri: approval 1-2
 */
$parafIndexes = array(1, 2);

/**
 * TTD bawah: approval 3-16
 */
$pabrikIndexes = array(3, 4, 5, 6, 7);
$hoIndexes     = array(8, 9, 10, 11, 12, 13, 14, 15, 16);

/**
 * Hitung yang tampil
 */
$visiblePabrik = array();
$visibleHo     = array();

foreach ($pabrikIndexes as $idx) {
    if (isFilledActor($signatureData[$idx]['actor'])) {
        $visiblePabrik[] = $idx;
    }
}

foreach ($hoIndexes as $idx) {
    if (isFilledActor($signatureData[$idx]['actor'])) {
        $visibleHo[] = $idx;
    }
}

$pabrikDibuat      = 0;
$pabrikDiperiksa   = 0;
$pabrikDisetujui   = 0;
$hoDiperiksa       = 0;
$hoDisetujui       = 0;

foreach ($visiblePabrik as $idx) {
    if ($idx == 3) {
        $pabrikDibuat++;
    } elseif (in_array($idx, array(4, 5, 6))) {
        $pabrikDiperiksa++;
    } elseif ($idx == 7) {
        $pabrikDisetujui++;
    }
}

foreach ($visibleHo as $idx) {
    if (in_array($idx, array(8, 9, 10, 11))) {
        $hoDiperiksa++;
    } elseif (in_array($idx, array(12, 13, 14, 15, 16))) {
        $hoDisetujui++;
    }
}

$totalPabrik = count($visiblePabrik);
$totalHo     = count($visibleHo);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Surat Output - Permohonan Penghapusan Aset</title>
    <link rel="stylesheet" href="../assets/bootstrap-5.3.6-dist/css/bootstrap.min.css" />
    <style>
        .wrapper {
            width: 100%;
            height: max-content;
            margin: 0;
            padding: 0;
        }

        .wrapper-2 {
            border: 1px solid black;
            width: 100%;
            height: max-content;
            margin: 0;
            padding: 0;
        }

        .wrapper-2 td,
        .wrapper-2 tr {
            border: 1px solid black;
        }

        .custom-fs {
            font-size: 8pt;
        }

        .custom-fs-10 {
            font-size: 10pt;
        }

        .wrapper-2 td h1 {
            font-size: 6pt;
        }

        .wrapper-2 tbody td {
            text-align: center;
        }

        .wrapper-2 tbody td p {
            font-size: 6pt;
            margin: 0;
            padding: 0;
        }

        .custom-gap {
            width: 82%;
        }

        .custom-gap-2 {
            width: 7%;
        }

        .custom-gap-3 {
            width: 11%;
        }

        .wrapper-ttd {
            width: 90%;
            height: max-content;
            margin: 0;
            padding: 0;
        }

        .wrapper-ttd tr td {
            border: 1px solid black;
            width: 100px;
            position: relative;
        }

        .wrapper-ttd p {
            font-size: 6pt;
            z-index: 1;
            position: absolute;
        }

        .wrapper-ttd tr td img {
            width: 42pt;
        }

        .wrapper-ttd thead td h1,
        .wrapper-ttd tbody td h1 {
            font-size: 6pt;
            margin: 0;
            padding: 0;
            font-weight: bold;
        }

        .wrapper-paraf {
            width: 10%;
            height: max-content;
            margin: 0;
            padding: 0;
        }

        .wrapper-paraf tr td {
            display: flex;
            justify-content: end;
        }

        .wrapper-paraf tr td img {
            width: 38pt;
        }

        .wrapper-img {
            width: 100%;
            height: max-content;
            margin: 0;
            padding: 0;
            text-align: center;
        }

        .wrapper-img tr td img {
            max-width: 120pt;
        }

        .wrapper-img tr td p {
            margin: 0;
            padding: 0;
            font-size: 8pt;
        }
    </style>
</head>

<body class="p-1">

    <table class="wrapper">
        <tr>
            <td>
                <h1 class="m-0 p-0 fw-bold custom-fs"><?php echo e(formatPTHeader($ba['id_pt'])); ?></h1>
            </td>
        </tr>
        <tr>
            <td class="custom-gap"></td>
            <td class="custom-gap-2">
                <h1 class="m-0 p-0 custom-fs">Tanggal</h1>
            </td>
            <td class="custom-gap-3">
                <h1 class="m-0 p-0 custom-fs"><?php echo e(formatTanggalIndo($ba['tanggal'])); ?></h1>
            </td>
        </tr>
        <tr>
            <td class="custom-gap"></td>
            <td class="custom-gap-2">
                <h1 class="m-0 p-0 custom-fs">Nomor</h1>
            </td>
            <td class="custom-gap-3">
                <h1 class="m-0 p-0 custom-fs"><?php echo e(formatKodeSurat($ba['nomor_ba'], $ba['id_pt'], $ba['tanggal'])); ?></h1>
            </td>
        </tr>
        <tr>
            <td colspan="3" class="text-center">
                <h1 class="m-0 p-0 fw-bold custom-fs-10">PERMOHONAN PENGHAPUSAN ASET</h1>
            </td>
        </tr>
        <tr>
            <td colspan="3" class="text-start">
                <h1 class="m-0 p-0 custom-fs">Bersama ini kami mengajukan permohonan penghapusan aset dengan rincian sebagai berikut :</h1>
            </td>
        </tr>
    </table>

    <table class="wrapper-2">
        <thead>
            <tr>
                <td class="text-center" rowspan="2">
                    <h1 class="m-0 p-0 fw-bold">No</h1>
                </td>
                <td class="text-center" colspan="8">
                    <h1 class="m-0 p-0 fw-bold">SPESIFIKASI BARANG INVENTARIS</h1>
                </td>
                <td rowspan="2" class="text-center">
                    <h1 class="m-0 p-0 fw-bold">LOKASI ASET/PT</h1>
                </td>
                <td rowspan="2" class="text-center">
                    <h1 class="m-0 p-0 fw-bold">NAMA PENGGUNA</h1>
                </td>
                <td rowspan="2" class="text-center">
                    <h1 class="m-0 p-0 fw-bold">KETERANGAN</h1>
                </td>
            </tr>
            <tr>
                <td class="text-center">
                    <h1 class="m-0 p-0 fw-bold">NAMA BARANG</h1>
                </td>
                <td class="text-center">
                    <h1 class="m-0 p-0 fw-bold">JUMLAH BARANG</h1>
                </td>
                <td class="text-center">
                    <h1 class="m-0 p-0 fw-bold">MERK/TYPE</h1>
                </td>
                <td class="text-center">
                    <h1 class="m-0 p-0 fw-bold">SERIAL NUMBER</h1>
                </td>
                <td class="text-center">
                    <h1 class="m-0 p-0 fw-bold">NOMOR PO</h1>
                </td>
                <td class="text-center">
                    <h1 class="m-0 p-0 fw-bold">TAHUN PEROLEHAN</h1>
                </td>
                <td class="text-center">
                    <h1 class="m-0 p-0 fw-bold">HARGA</h1>
                </td>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($barangList)) { ?>
                <?php $no = 1; ?>
                <?php foreach ($barangList as $barang) { ?>
                    <tr>
                        <td class="text-center">
                            <p><?php echo $no; ?></p>
                        </td>
                        <td>
                            <p><?php echo e($barang['coa']); ?></p>
                        </td>
                        <td class="text-center">
                            <p>1</p>
                        </td>
                        <td>
                            <p><?php echo e($barang['merk']); ?></p>
                        </td>
                        <td>
                            <p><?php echo e($barang['sn']); ?></p>
                        </td>
                        <td>
                            <p><?php echo e($barang['po']); ?></p>
                        </td>
                        <td class="text-center">
                            <p><?php echo e($barang['tahun_perolehan']); ?></p>
                        </td>
                        <td>
                            <p><?php echo e(formatRupiah($barang['harga_beli'])); ?></p>
                        </td>
                        <td colspan="2">
                            <p><?php echo e($barang['pt']); ?></p>
                        </td>
                        <td>
                            <p><?php echo e($barang['user']); ?></p>
                        </td>
                        <td>
                            <p><?php echo e($barang['kondisi']); ?></p>
                        </td>
                    </tr>
                    <?php $no++; ?>
                <?php } ?>
            <?php } else { ?>
                <tr>
                    <td class="text-center">
                        <p>1</p>
                    </td>
                    <td>
                        <p></p>
                    </td>
                    <td>
                        <p></p>
                    </td>
                    <td>
                        <p></p>
                    </td>
                    <td>
                        <p></p>
                    </td>
                    <td>
                        <p></p>
                    </td>
                    <td>
                        <p></p>
                    </td>
                    <td>
                        <p></p>
                    </td>
                    <td colspan="2">
                        <p></p>
                    </td>
                    <td>
                        <p></p>
                    </td>
                    <td>
                        <p></p>
                    </td>
                </tr>
            <?php } ?>
        </tbody>
    </table>

    <table class="wrapper">
        <tr>
            <td class="custom-fs fst-italic">Lampiran : Foto kondisi aset</td>
        </tr>
        <tr style="height: 8pt;"></tr>
    </table>

    <div class="w-100 d-flex justify-content-end">

        <table class="wrapper-paraf">
            <?php foreach ($parafIndexes as $idx) { ?>
                <?php if (isFilledActor($signatureData[$idx]['actor'])) { ?>
                    <tr>
                        <td>
                            <?php if (!empty($signatureData[$idx]['autograph']) && $signatureData[$idx]['autograph'] !== '-') { ?>
                                <img src="<?php echo e($signatureData[$idx]['autograph']); ?>" alt="ttd-approver-<?php echo $idx; ?>">
                            <?php } ?>
                        </td>
                    </tr>
                <?php } ?>
            <?php } ?>
        </table>

        <table class="wrapper-ttd text-center">
            <thead>
                <tr>
                    <?php if ($totalPabrik > 0) { ?>
                        <td colspan="<?php echo $totalPabrik; ?>">
                            <h1>Pabrik (Pemohon)</h1>
                        </td>
                    <?php } ?>
                    <?php if ($totalHo > 0) { ?>
                        <td colspan="<?php echo $totalHo; ?>">
                            <h1>Head Office (HO)</h1>
                        </td>
                    <?php } ?>
                </tr>
                <tr>
                    <?php if ($pabrikDibuat > 0) { ?>
                        <td colspan="<?php echo $pabrikDibuat; ?>">
                            <h1>Dibuat Oleh</h1>
                        </td>
                    <?php } ?>
                    <?php if ($pabrikDiperiksa > 0) { ?>
                        <td colspan="<?php echo $pabrikDiperiksa; ?>">
                            <h1>Diperiksa Oleh</h1>
                        </td>
                    <?php } ?>
                    <?php if ($pabrikDisetujui > 0) { ?>
                        <td colspan="<?php echo $pabrikDisetujui; ?>">
                            <h1>Disetujui Oleh</h1>
                        </td>
                    <?php } ?>
                    <?php if ($hoDiperiksa > 0) { ?>
                        <td colspan="<?php echo $hoDiperiksa; ?>">
                            <h1>Diperiksa Oleh</h1>
                        </td>
                    <?php } ?>
                    <?php if ($hoDisetujui > 0) { ?>
                        <td colspan="<?php echo $hoDisetujui; ?>">
                            <h1>Disetujui Oleh</h1>
                        </td>
                    <?php } ?>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <?php foreach ($pabrikIndexes as $idx) { ?>
                        <?php if (isFilledActor($signatureData[$idx]['actor'])) { ?>
                            <td>
                                <?php if (!empty($signatureData[$idx]['autograph']) && $signatureData[$idx]['autograph'] !== '-') { ?>
                                    <p class="text-muted">Digital sign</p>
                                    <img src="<?php echo e($signatureData[$idx]['autograph']); ?>" alt="ttd-approver-<?php echo $idx; ?>">
                                <?php } ?>
                            </td>
                        <?php } ?>
                    <?php } ?>

                    <?php foreach ($hoIndexes as $idx) { ?>
                        <?php if (isFilledActor($signatureData[$idx]['actor'])) { ?>
                            <td>
                                <?php if (!empty($signatureData[$idx]['autograph']) && $signatureData[$idx]['autograph'] !== '-') { ?>
                                    <p class="text-muted">Digital sign</p>
                                    <img src="<?php echo e($signatureData[$idx]['autograph']); ?>" alt="ttd-approver-<?php echo $idx; ?>">
                                <?php } ?>
                            </td>
                        <?php } ?>
                    <?php } ?>
                </tr>
                <tr>
                    <?php foreach ($pabrikIndexes as $idx) { ?>
                        <?php if (isFilledActor($signatureData[$idx]['actor'])) { ?>
                            <td>
                                <h1><?php echo e($signatureData[$idx]['label']); ?></h1>
                            </td>
                        <?php } ?>
                    <?php } ?>

                    <?php foreach ($hoIndexes as $idx) { ?>
                        <?php if (isFilledActor($signatureData[$idx]['actor'])) { ?>
                            <td>
                                <h1><?php echo e($signatureData[$idx]['label']); ?></h1>
                            </td>
                        <?php } ?>
                    <?php } ?>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="mt-3 d-flex flex-column align-items-center">
        <h1 class="custom-fs-10 fw-bold">LAMPIRAN</h1>
        <table class="wrapper-img">
            <?php echo buildLampiranRows($lampiranList); ?>
        </table>
    </div>

    <script src="../assets/bootstrap-5.3.6-dist/js/bootstrap.min.js"></script>
</body>

</html>