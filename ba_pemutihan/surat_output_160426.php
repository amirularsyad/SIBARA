<?php
require_once '../koneksi.php';

function h($string)
{
    return htmlspecialchars((string)$string, ENT_QUOTES, 'UTF-8');
}

function getHariIndonesia($tanggal)
{
    $timestamp = strtotime($tanggal);
    if ($timestamp === false) {
        return '';
    }

    $hari = array(
        1 => 'Senin',
        2 => 'Selasa',
        3 => 'Rabu',
        4 => 'Kamis',
        5 => 'Jumat',
        6 => 'Sabtu',
        7 => 'Minggu'
    );

    $index = (int)date('N', $timestamp);
    return isset($hari[$index]) ? $hari[$index] : '';
}

function getTanggalIndonesia($tanggal)
{
    $timestamp = strtotime($tanggal);
    if ($timestamp === false) {
        return '';
    }

    $bulan = array(
        1 => 'Januari',
        2 => 'Februari',
        3 => 'Maret',
        4 => 'April',
        5 => 'Mei',
        6 => 'Juni',
        7 => 'Juli',
        8 => 'Agustus',
        9 => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember'
    );

    $hari = (int)date('j', $timestamp);
    $bulanIndex = (int)date('n', $timestamp);
    $tahun = date('Y', $timestamp);

    return $hari . ' ' . (isset($bulan[$bulanIndex]) ? $bulan[$bulanIndex] : '') . ' ' . $tahun;
}

function getBulanRomawi($tanggal)
{
    $timestamp = strtotime($tanggal);
    if ($timestamp === false) {
        return '';
    }

    $bulanRomawi = array(
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

    $bulan = (int)date('n', $timestamp);
    return isset($bulanRomawi[$bulan]) ? $bulanRomawi[$bulan] : '';
}

function upperText($text)
{
    $text = ($text !== null) ? (string)$text : '';
    if (function_exists('mb_strtoupper')) {
        return mb_strtoupper($text, 'UTF-8');
    }
    return strtoupper($text);
}

function formatRupiah($angka)
{
    $nilai = (int)$angka;
    if ($nilai <= 0) {
        return 'Rp 0';
    }
    return 'Rp ' . number_format($nilai, 0, ',', '.');
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    die('ID berita acara tidak valid.');
}

$stmt = $koneksi->prepare("
    SELECT
        id,
        tanggal,
        nomor_ba,
        pt,

        pembuat,
        jabatan_pembuat,
        pembuat_site,
        jabatan_pembuat_site,

        pemeriksa,
        jabatan_pemeriksa,
        pemeriksa_site,
        jabatan_pemeriksa_site,

        diketahui1,
        jabatan_diketahui1,
        diketahui1_site,
        jabatan_diketahui1_site,

        diketahui2,
        jabatan_diketahui2,
        disetujui1_site,
        jabatan_disetujui1_site,

        diketahui3,
        jabatan_diketahui3,
        diketahui2_site,
        jabatan_diketahui2_site,

        dibukukan,
        jabatan_dibukukan,
        diperiksa_site,
        jabatan_diperiksa_site,

        disetujui1,
        jabatan_disetujui1,
        disetujui2,
        jabatan_disetujui2,
        disetujui3,
        jabatan_disetujui3,

        mengetahui_site,
        jabatan_mengetahui_site,

        approval_1, approval_2, approval_3, approval_4, approval_5,
        approval_6, approval_7, approval_8, approval_9, approval_10, approval_11,

        autograph_1, autograph_2, autograph_3, autograph_4, autograph_5,
        autograph_6, autograph_7, autograph_8, autograph_9, autograph_10, autograph_11,

        tanggal_approve_1, tanggal_approve_2, tanggal_approve_3, tanggal_approve_4, tanggal_approve_5,
        tanggal_approve_6, tanggal_approve_7, tanggal_approve_8, tanggal_approve_9, tanggal_approve_10, tanggal_approve_11

    FROM berita_acara_pemutihan
    WHERE id = ? AND dihapus = 0
    LIMIT 1
");

if (!$stmt) {
    die('Prepare query gagal.');
}

$id_ba = 0;
$tanggal_ba = '';
$nomor_ba = '';
$pt_ba = '';

$pembuat = '';
$jabatan_pembuat = '';
$pembuat_site = '';
$jabatan_pembuat_site = '';

$pemeriksa = '';
$jabatan_pemeriksa = '';
$pemeriksa_site = '';
$jabatan_pemeriksa_site = '';

$diketahui1 = '';
$jabatan_diketahui1 = '';
$diketahui1_site = '';
$jabatan_diketahui1_site = '';

$diketahui2 = '';
$jabatan_diketahui2 = '';
$disetujui1_site = '';
$jabatan_disetujui1_site = '';

$diketahui3 = '';
$jabatan_diketahui3 = '';
$diketahui2_site = '';
$jabatan_diketahui2_site = '';

$dibukukan = '';
$jabatan_dibukukan = '';
$diperiksa_site = '';
$jabatan_diperiksa_site = '';

$disetujui1 = '';
$jabatan_disetujui1 = '';
$disetujui2 = '';
$jabatan_disetujui2 = '';
$disetujui3 = '';
$jabatan_disetujui3 = '';

$mengetahui_site = '';
$jabatan_mengetahui_site = '';

$approval_1 = 0;
$approval_2 = 0;
$approval_3 = 0;
$approval_4 = 0;
$approval_5 = 0;
$approval_6 = 0;
$approval_7 = 0;
$approval_8 = 0;
$approval_9 = 0;
$approval_10 = 0;
$approval_11 = 0;

$autograph_1 = null;
$autograph_2 = null;
$autograph_3 = null;
$autograph_4 = null;
$autograph_5 = null;
$autograph_6 = null;
$autograph_7 = null;
$autograph_8 = null;
$autograph_9 = null;
$autograph_10 = null;
$autograph_11 = null;

$tanggal_approve_1 = null;
$tanggal_approve_2 = null;
$tanggal_approve_3 = null;
$tanggal_approve_4 = null;
$tanggal_approve_5 = null;
$tanggal_approve_6 = null;
$tanggal_approve_7 = null;
$tanggal_approve_8 = null;
$tanggal_approve_9 = null;
$tanggal_approve_10 = null;
$tanggal_approve_11 = null;

$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->bind_result(
    $id_ba,
    $tanggal_ba,
    $nomor_ba,
    $pt_ba,

    $pembuat,
    $jabatan_pembuat,
    $pembuat_site,
    $jabatan_pembuat_site,

    $pemeriksa,
    $jabatan_pemeriksa,
    $pemeriksa_site,
    $jabatan_pemeriksa_site,

    $diketahui1,
    $jabatan_diketahui1,
    $diketahui1_site,
    $jabatan_diketahui1_site,

    $diketahui2,
    $jabatan_diketahui2,
    $disetujui1_site,
    $jabatan_disetujui1_site,

    $diketahui3,
    $jabatan_diketahui3,
    $diketahui2_site,
    $jabatan_diketahui2_site,

    $dibukukan,
    $jabatan_dibukukan,
    $diperiksa_site,
    $jabatan_diperiksa_site,

    $disetujui1,
    $jabatan_disetujui1,
    $disetujui2,
    $jabatan_disetujui2,
    $disetujui3,
    $jabatan_disetujui3,

    $mengetahui_site,
    $jabatan_mengetahui_site,

    $approval_1,
    $approval_2,
    $approval_3,
    $approval_4,
    $approval_5,
    $approval_6,
    $approval_7,
    $approval_8,
    $approval_9,
    $approval_10,
    $approval_11,

    $autograph_1,
    $autograph_2,
    $autograph_3,
    $autograph_4,
    $autograph_5,
    $autograph_6,
    $autograph_7,
    $autograph_8,
    $autograph_9,
    $autograph_10,
    $autograph_11,

    $tanggal_approve_1,
    $tanggal_approve_2,
    $tanggal_approve_3,
    $tanggal_approve_4,
    $tanggal_approve_5,
    $tanggal_approve_6,
    $tanggal_approve_7,
    $tanggal_approve_8,
    $tanggal_approve_9,
    $tanggal_approve_10,
    $tanggal_approve_11
);

if (!$stmt->fetch()) {
    $stmt->close();
    die('Data berita acara pemutihan tidak ditemukan.');
}
$stmt->close();

$id_ba = (int)$id_ba;
$tanggal_ba = ($tanggal_ba !== null) ? (string)$tanggal_ba : '';
$nomor_ba = ($nomor_ba !== null) ? (string)$nomor_ba : '';
$pt_ba = ($pt_ba !== null) ? (string)$pt_ba : '';

if ($tanggal_ba === '' || $nomor_ba === '' || $pt_ba === '') {
    die('Data berita acara pemutihan tidak lengkap.');
}

$data_ba = array(
    'id' => (int)$id_ba,
    'tanggal' => ($tanggal_ba !== null) ? (string)$tanggal_ba : '',
    'nomor_ba' => ($nomor_ba !== null) ? (string)$nomor_ba : '',
    'pt' => ($pt_ba !== null) ? (string)$pt_ba : '',

    'pembuat' => ($pembuat !== null) ? (string)$pembuat : '',
    'jabatan_pembuat' => ($jabatan_pembuat !== null) ? (string)$jabatan_pembuat : '',
    'pembuat_site' => ($pembuat_site !== null) ? (string)$pembuat_site : '',
    'jabatan_pembuat_site' => ($jabatan_pembuat_site !== null) ? (string)$jabatan_pembuat_site : '',

    'pemeriksa' => ($pemeriksa !== null) ? (string)$pemeriksa : '',
    'jabatan_pemeriksa' => ($jabatan_pemeriksa !== null) ? (string)$jabatan_pemeriksa : '',
    'pemeriksa_site' => ($pemeriksa_site !== null) ? (string)$pemeriksa_site : '',
    'jabatan_pemeriksa_site' => ($jabatan_pemeriksa_site !== null) ? (string)$jabatan_pemeriksa_site : '',

    'diketahui1' => ($diketahui1 !== null) ? (string)$diketahui1 : '',
    'jabatan_diketahui1' => ($jabatan_diketahui1 !== null) ? (string)$jabatan_diketahui1 : '',
    'diketahui1_site' => ($diketahui1_site !== null) ? (string)$diketahui1_site : '',
    'jabatan_diketahui1_site' => ($jabatan_diketahui1_site !== null) ? (string)$jabatan_diketahui1_site : '',

    'diketahui2' => ($diketahui2 !== null) ? (string)$diketahui2 : '',
    'jabatan_diketahui2' => ($jabatan_diketahui2 !== null) ? (string)$jabatan_diketahui2 : '',
    'disetujui1_site' => ($disetujui1_site !== null) ? (string)$disetujui1_site : '',
    'jabatan_disetujui1_site' => ($jabatan_disetujui1_site !== null) ? (string)$jabatan_disetujui1_site : '',

    'diketahui3' => ($diketahui3 !== null) ? (string)$diketahui3 : '',
    'jabatan_diketahui3' => ($jabatan_diketahui3 !== null) ? (string)$jabatan_diketahui3 : '',
    'diketahui2_site' => ($diketahui2_site !== null) ? (string)$diketahui2_site : '',
    'jabatan_diketahui2_site' => ($jabatan_diketahui2_site !== null) ? (string)$jabatan_diketahui2_site : '',

    'dibukukan' => ($dibukukan !== null) ? (string)$dibukukan : '',
    'jabatan_dibukukan' => ($jabatan_dibukukan !== null) ? (string)$jabatan_dibukukan : '',
    'diperiksa_site' => ($diperiksa_site !== null) ? (string)$diperiksa_site : '',
    'jabatan_diperiksa_site' => ($jabatan_diperiksa_site !== null) ? (string)$jabatan_diperiksa_site : '',

    'disetujui1' => ($disetujui1 !== null) ? (string)$disetujui1 : '',
    'jabatan_disetujui1' => ($jabatan_disetujui1 !== null) ? (string)$jabatan_disetujui1 : '',
    'disetujui2' => ($disetujui2 !== null) ? (string)$disetujui2 : '',
    'jabatan_disetujui2' => ($jabatan_disetujui2 !== null) ? (string)$jabatan_disetujui2 : '',
    'disetujui3' => ($disetujui3 !== null) ? (string)$disetujui3 : '',
    'jabatan_disetujui3' => ($jabatan_disetujui3 !== null) ? (string)$jabatan_disetujui3 : '',

    'mengetahui_site' => ($mengetahui_site !== null) ? (string)$mengetahui_site : '',
    'jabatan_mengetahui_site' => ($jabatan_mengetahui_site !== null) ? (string)$jabatan_mengetahui_site : '',

    'approval_1' => (int)$approval_1,
    'approval_2' => (int)$approval_2,
    'approval_3' => (int)$approval_3,
    'approval_4' => (int)$approval_4,
    'approval_5' => (int)$approval_5,
    'approval_6' => (int)$approval_6,
    'approval_7' => (int)$approval_7,
    'approval_8' => (int)$approval_8,
    'approval_9' => (int)$approval_9,
    'approval_10' => (int)$approval_10,
    'approval_11' => (int)$approval_11,

    'autograph_1' => $autograph_1,
    'autograph_2' => $autograph_2,
    'autograph_3' => $autograph_3,
    'autograph_4' => $autograph_4,
    'autograph_5' => $autograph_5,
    'autograph_6' => $autograph_6,
    'autograph_7' => $autograph_7,
    'autograph_8' => $autograph_8,
    'autograph_9' => $autograph_9,
    'autograph_10' => $autograph_10,
    'autograph_11' => $autograph_11,

    'tanggal_approve_1' => ($tanggal_approve_1 !== null) ? (string)$tanggal_approve_1 : '',
    'tanggal_approve_2' => ($tanggal_approve_2 !== null) ? (string)$tanggal_approve_2 : '',
    'tanggal_approve_3' => ($tanggal_approve_3 !== null) ? (string)$tanggal_approve_3 : '',
    'tanggal_approve_4' => ($tanggal_approve_4 !== null) ? (string)$tanggal_approve_4 : '',
    'tanggal_approve_5' => ($tanggal_approve_5 !== null) ? (string)$tanggal_approve_5 : '',
    'tanggal_approve_6' => ($tanggal_approve_6 !== null) ? (string)$tanggal_approve_6 : '',
    'tanggal_approve_7' => ($tanggal_approve_7 !== null) ? (string)$tanggal_approve_7 : '',
    'tanggal_approve_8' => ($tanggal_approve_8 !== null) ? (string)$tanggal_approve_8 : '',
    'tanggal_approve_9' => ($tanggal_approve_9 !== null) ? (string)$tanggal_approve_9 : '',
    'tanggal_approve_10' => ($tanggal_approve_10 !== null) ? (string)$tanggal_approve_10 : '',
    'tanggal_approve_11' => ($tanggal_approve_11 !== null) ? (string)$tanggal_approve_11 : ''
);

function isHoPemutihan($pt)
{
    return trim((string)$pt) === 'PT.MSAL (HO)';
}

function formatTanggalApproveTtd($tanggal)
{
    $tanggal = ($tanggal !== null) ? trim((string)$tanggal) : '';
    if ($tanggal === '') {
        return '';
    }

    $timestamp = strtotime($tanggal);
    if ($timestamp === false) {
        return '';
    }

    return date('d/m/Y', $timestamp);
}

function buildAutographSrc($blob)
{
    if ($blob === null) {
        return '';
    }

    if (!is_string($blob)) {
        return '';
    }

    if ($blob === '') {
        return '';
    }

    if (strpos($blob, 'data:image/') === 0) {
        return $blob;
    }

    if (preg_match('/^[A-Za-z0-9+\/=\r\n]+$/', $blob)) {
        $decoded = base64_decode($blob, true);
        if ($decoded !== false && $decoded !== '') {
            $info = @getimagesizefromstring($decoded);
            if ($info !== false && isset($info['mime'])) {
                return 'data:' . $info['mime'] . ';base64,' . base64_encode($decoded);
            }
        }
    }

    $info = @getimagesizefromstring($blob);
    if ($info !== false && isset($info['mime'])) {
        return 'data:' . $info['mime'] . ';base64,' . base64_encode($blob);
    }

    return '';
}

function getSignatureJabatanList($is_ho)
{
    if ($is_ho) {
        return array(
            1 => 'Staff MIS',
            2 => 'Staff MIS',
            3 => 'Dept. Head MIS',
            4 => 'Dept. Head HRO',
            5 => 'Dept. Head HRD',
            6 => 'Direktur Finance',
            7 => 'Direktur MIS',
            8 => 'Direktur HRD & Umum',
            9 => 'Vice CEO'
        );
    }

    return array(
        1 => 'Asisten IT',
        2 => 'Asisten PGA',
        3 => 'KTU',
        4 => 'Kepala Project',
        5 => 'Dept. Head MIS',
        6 => 'Dept. Head HRO',
        7 => 'Dir. Finance',
        8 => 'Dir. HRD & GA',
        9 => 'Dir. MIS',
        10 => 'Vice CEO',
        11 => 'CEO'
    );
}

function buildSignatureColumns($data_ba)
{
    $is_ho = isHoPemutihan(isset($data_ba['pt']) ? $data_ba['pt'] : '');
    $jabatan_list = getSignatureJabatanList($is_ho);

    if ($is_ho) {
        return array(
            array(
                'header' => 'Dibuat oleh,',
                'nama' => isset($data_ba['pembuat']) ? $data_ba['pembuat'] : '',
                'jabatan' => isset($jabatan_list[1]) ? $jabatan_list[1] : '',
                'approval' => isset($data_ba['approval_1']) ? (int)$data_ba['approval_1'] : 0,
                'autograph' => isset($data_ba['autograph_1']) ? $data_ba['autograph_1'] : null,
                'tanggal' => isset($data_ba['tanggal_approve_1']) ? $data_ba['tanggal_approve_1'] : ''
            ),
            array(
                'header' => 'Diperiksa oleh,',
                'nama' => isset($data_ba['pemeriksa']) ? $data_ba['pemeriksa'] : '',
                'jabatan' => isset($jabatan_list[2]) ? $jabatan_list[2] : '',
                'approval' => isset($data_ba['approval_2']) ? (int)$data_ba['approval_2'] : 0,
                'autograph' => isset($data_ba['autograph_2']) ? $data_ba['autograph_2'] : null,
                'tanggal' => isset($data_ba['tanggal_approve_2']) ? $data_ba['tanggal_approve_2'] : ''
            ),
            array(
                'header' => 'Diketahui oleh,',
                'nama' => isset($data_ba['diketahui1']) ? $data_ba['diketahui1'] : '',
                'jabatan' => isset($jabatan_list[3]) ? $jabatan_list[3] : '',
                'approval' => isset($data_ba['approval_3']) ? (int)$data_ba['approval_3'] : 0,
                'autograph' => isset($data_ba['autograph_3']) ? $data_ba['autograph_3'] : null,
                'tanggal' => isset($data_ba['tanggal_approve_3']) ? $data_ba['tanggal_approve_3'] : ''
            ),
            array(
                'header' => 'Diketahui oleh,',
                'nama' => isset($data_ba['diketahui2']) ? $data_ba['diketahui2'] : '',
                'jabatan' => isset($jabatan_list[4]) ? $jabatan_list[4] : '',
                'approval' => isset($data_ba['approval_4']) ? (int)$data_ba['approval_4'] : 0,
                'autograph' => isset($data_ba['autograph_4']) ? $data_ba['autograph_4'] : null,
                'tanggal' => isset($data_ba['tanggal_approve_4']) ? $data_ba['tanggal_approve_4'] : ''
            ),
            array(
                'header' => 'Diketahui oleh,',
                'nama' => isset($data_ba['diketahui3']) ? $data_ba['diketahui3'] : '',
                'jabatan' => isset($jabatan_list[5]) ? $jabatan_list[5] : '',
                'approval' => isset($data_ba['approval_5']) ? (int)$data_ba['approval_5'] : 0,
                'autograph' => isset($data_ba['autograph_5']) ? $data_ba['autograph_5'] : null,
                'tanggal' => isset($data_ba['tanggal_approve_5']) ? $data_ba['tanggal_approve_5'] : ''
            ),
            array(
                'header' => 'Dibukukan oleh,',
                'nama' => isset($data_ba['dibukukan']) ? $data_ba['dibukukan'] : '',
                'jabatan' => isset($jabatan_list[6]) ? $jabatan_list[6] : '',
                'approval' => isset($data_ba['approval_6']) ? (int)$data_ba['approval_6'] : 0,
                'autograph' => isset($data_ba['autograph_6']) ? $data_ba['autograph_6'] : null,
                'tanggal' => isset($data_ba['tanggal_approve_6']) ? $data_ba['tanggal_approve_6'] : ''
            ),
            array(
                'header' => 'Disetujui oleh,',
                'nama' => isset($data_ba['disetujui1']) ? $data_ba['disetujui1'] : '',
                'jabatan' => isset($jabatan_list[7]) ? $jabatan_list[7] : '',
                'approval' => isset($data_ba['approval_7']) ? (int)$data_ba['approval_7'] : 0,
                'autograph' => isset($data_ba['autograph_7']) ? $data_ba['autograph_7'] : null,
                'tanggal' => isset($data_ba['tanggal_approve_7']) ? $data_ba['tanggal_approve_7'] : ''
            ),
            array(
                'header' => 'Disetujui oleh,',
                'nama' => isset($data_ba['disetujui2']) ? $data_ba['disetujui2'] : '',
                'jabatan' => isset($jabatan_list[8]) ? $jabatan_list[8] : '',
                'approval' => isset($data_ba['approval_8']) ? (int)$data_ba['approval_8'] : 0,
                'autograph' => isset($data_ba['autograph_8']) ? $data_ba['autograph_8'] : null,
                'tanggal' => isset($data_ba['tanggal_approve_8']) ? $data_ba['tanggal_approve_8'] : ''
            ),
            array(
                'header' => 'Disetujui oleh,',
                'nama' => isset($data_ba['disetujui3']) ? $data_ba['disetujui3'] : '',
                'jabatan' => isset($jabatan_list[9]) ? $jabatan_list[9] : '',
                'approval' => isset($data_ba['approval_9']) ? (int)$data_ba['approval_9'] : 0,
                'autograph' => isset($data_ba['autograph_9']) ? $data_ba['autograph_9'] : null,
                'tanggal' => isset($data_ba['tanggal_approve_9']) ? $data_ba['tanggal_approve_9'] : ''
            )
        );
    }

    return array(
        array(
            'header' => 'Dibuat oleh,',
            'nama' => isset($data_ba['pembuat_site']) ? $data_ba['pembuat_site'] : '',
            'jabatan' => isset($jabatan_list[1]) ? $jabatan_list[1] : '',
            'approval' => isset($data_ba['approval_1']) ? (int)$data_ba['approval_1'] : 0,
            'autograph' => isset($data_ba['autograph_1']) ? $data_ba['autograph_1'] : null,
            'tanggal' => isset($data_ba['tanggal_approve_1']) ? $data_ba['tanggal_approve_1'] : ''
        ),
        array(
            'header' => 'Diperiksa oleh,',
            'nama' => isset($data_ba['pemeriksa_site']) ? $data_ba['pemeriksa_site'] : '',
            'jabatan' => isset($jabatan_list[2]) ? $jabatan_list[2] : '',
            'approval' => isset($data_ba['approval_2']) ? (int)$data_ba['approval_2'] : 0,
            'autograph' => isset($data_ba['autograph_2']) ? $data_ba['autograph_2'] : null,
            'tanggal' => isset($data_ba['tanggal_approve_2']) ? $data_ba['tanggal_approve_2'] : ''
        ),
        array(
            'header' => 'Diketahui oleh,',
            'nama' => isset($data_ba['diketahui1_site']) ? $data_ba['diketahui1_site'] : '',
            'jabatan' => isset($jabatan_list[3]) ? $jabatan_list[3] : '',
            'approval' => isset($data_ba['approval_3']) ? (int)$data_ba['approval_3'] : 0,
            'autograph' => isset($data_ba['autograph_3']) ? $data_ba['autograph_3'] : null,
            'tanggal' => isset($data_ba['tanggal_approve_3']) ? $data_ba['tanggal_approve_3'] : ''
        ),
        array(
            'header' => 'Disetujui oleh,',
            'nama' => isset($data_ba['disetujui1_site']) ? $data_ba['disetujui1_site'] : '',
            'jabatan' => isset($jabatan_list[4]) ? $jabatan_list[4] : '',
            'approval' => isset($data_ba['approval_4']) ? (int)$data_ba['approval_4'] : 0,
            'autograph' => isset($data_ba['autograph_4']) ? $data_ba['autograph_4'] : null,
            'tanggal' => isset($data_ba['tanggal_approve_4']) ? $data_ba['tanggal_approve_4'] : ''
        ),
        array(
            'header' => 'Diketahui oleh,',
            'nama' => isset($data_ba['diketahui2_site']) ? $data_ba['diketahui2_site'] : '',
            'jabatan' => isset($jabatan_list[5]) ? $jabatan_list[5] : '',
            'approval' => isset($data_ba['approval_5']) ? (int)$data_ba['approval_5'] : 0,
            'autograph' => isset($data_ba['autograph_5']) ? $data_ba['autograph_5'] : null,
            'tanggal' => isset($data_ba['tanggal_approve_5']) ? $data_ba['tanggal_approve_5'] : ''
        ),
        array(
            'header' => 'Diperiksa oleh,',
            'nama' => isset($data_ba['diperiksa_site']) ? $data_ba['diperiksa_site'] : '',
            'jabatan' => isset($jabatan_list[6]) ? $jabatan_list[6] : '',
            'approval' => isset($data_ba['approval_6']) ? (int)$data_ba['approval_6'] : 0,
            'autograph' => isset($data_ba['autograph_6']) ? $data_ba['autograph_6'] : null,
            'tanggal' => isset($data_ba['tanggal_approve_6']) ? $data_ba['tanggal_approve_6'] : ''
        ),
        array(
            'header' => 'Dibukukan oleh,',
            'nama' => isset($data_ba['dibukukan']) ? $data_ba['dibukukan'] : '',
            'jabatan' => isset($jabatan_list[7]) ? $jabatan_list[7] : '',
            'approval' => isset($data_ba['approval_7']) ? (int)$data_ba['approval_7'] : 0,
            'autograph' => isset($data_ba['autograph_7']) ? $data_ba['autograph_7'] : null,
            'tanggal' => isset($data_ba['tanggal_approve_7']) ? $data_ba['tanggal_approve_7'] : ''
        ),
        array(
            'header' => 'Disetujui oleh,',
            'nama' => isset($data_ba['disetujui1']) ? $data_ba['disetujui1'] : '',
            'jabatan' => isset($jabatan_list[8]) ? $jabatan_list[8] : '',
            'approval' => isset($data_ba['approval_8']) ? (int)$data_ba['approval_8'] : 0,
            'autograph' => isset($data_ba['autograph_8']) ? $data_ba['autograph_8'] : null,
            'tanggal' => isset($data_ba['tanggal_approve_8']) ? $data_ba['tanggal_approve_8'] : ''
        ),
        array(
            'header' => 'Disetujui oleh,',
            'nama' => isset($data_ba['disetujui2']) ? $data_ba['disetujui2'] : '',
            'jabatan' => isset($jabatan_list[9]) ? $jabatan_list[9] : '',
            'approval' => isset($data_ba['approval_9']) ? (int)$data_ba['approval_9'] : 0,
            'autograph' => isset($data_ba['autograph_9']) ? $data_ba['autograph_9'] : null,
            'tanggal' => isset($data_ba['tanggal_approve_9']) ? $data_ba['tanggal_approve_9'] : ''
        ),
        array(
            'header' => 'Disetujui oleh,',
            'nama' => isset($data_ba['disetujui3']) ? $data_ba['disetujui3'] : '',
            'jabatan' => isset($jabatan_list[10]) ? $jabatan_list[10] : '',
            'approval' => isset($data_ba['approval_10']) ? (int)$data_ba['approval_10'] : 0,
            'autograph' => isset($data_ba['autograph_10']) ? $data_ba['autograph_10'] : null,
            'tanggal' => isset($data_ba['tanggal_approve_10']) ? $data_ba['tanggal_approve_10'] : ''
        ),
        array(
            'header' => 'Mengetahui,',
            'nama' => isset($data_ba['mengetahui_site']) ? $data_ba['mengetahui_site'] : '',
            'jabatan' => isset($jabatan_list[11]) ? $jabatan_list[11] : '',
            'approval' => isset($data_ba['approval_11']) ? (int)$data_ba['approval_11'] : 0,
            'autograph' => isset($data_ba['autograph_11']) ? $data_ba['autograph_11'] : null,
            'tanggal' => isset($data_ba['tanggal_approve_11']) ? $data_ba['tanggal_approve_11'] : ''
        )
    );
}

function buildSignatureHeaderGroups($columns)
{
    $groups = array();
    $count = count($columns);
    $i = 0;

    while ($i < $count) {
        $header = isset($columns[$i]['header']) ? $columns[$i]['header'] : '';
        $colspan = 1;
        $j = $i + 1;

        while ($j < $count && isset($columns[$j]['header']) && $columns[$j]['header'] === $header) {
            $colspan++;
            $j++;
        }

        $groups[] = array(
            'header' => $header,
            'colspan' => $colspan
        );

        $i = $j;
    }

    return $groups;
}

$pt_map = array(
    'PT.MSAL (HO)'    => 'PT. MULIA SAWIT AGRO LESTARI',
    'PT.MSAL (PKS)'   => 'PT. MULIA SAWIT AGRO LESTARI',
    'PT.MSAL (SITE)'  => 'PT. MULIA SAWIT AGRO LESTARI',
    'PT.PSAM (PKS)'   => 'PT. PERSADA SEJAHTERA AGRO MAKMUR',
    'PT.PSAM (SITE)'  => 'PT. PERSADA SEJAHTERA AGRO MAKMUR',
    'PT.MAPA'         => 'PT. MITRA AGRO PERSADA ABADI',
    'PT.PEAK (PKS)'   => 'PT. PERSADA ERA AGRO KENCANA',
    'PT.PEAK (SITE)'  => 'PT. PERSADA ERA AGRO KENCANA',
    'RO PALANGKARAYA' => 'RO PALANGKARAYA',
    'RO SAMPIT'       => 'RO SAMPIT',
    'PT.WCJU (SITE)'  => 'PT. WANA CATUR JAYA UTAMA',
    'PT.WCJU (PKS)'   => 'PT. WANA CATUR JAYA UTAMA'
);

$kode_pt = array(
    'PT.MSAL (HO)'    => 'MSALHO',
    'PT.MSAL (PKS)'   => 'MSALPKS',
    'PT.MSAL (SITE)'  => 'MSALSITE',
    'PT.PSAM (PKS)'   => 'PSAMPKS',
    'PT.PSAM (SITE)'  => 'PSAMSITE',
    'PT.MAPA'         => 'MAPA',
    'PT.PEAK (PKS)'   => 'PEAKPKS',
    'PT.PEAK (SITE)'  => 'PEAKSITE',
    'RO PALANGKARAYA' => 'ROPKY',
    'RO SAMPIT'       => 'RO',
    'PT.WCJU (SITE)'  => 'WCJUSITE',
    'PT.WCJU (PKS)'   => 'WCJUPKS'
);

$pt_map2 = array(
    'PT.MSAL (HO)'    => 'PT Mulia Sawit Agro Lestari (Head Office)',
    'PT.MSAL (PKS)'   => 'PT Mulia Sawit Agro Lestari (PKS)',
    'PT.MSAL (SITE)'  => 'PT Mulia Sawit Agro Lestari (SITE)',
    'PT.PSAM (PKS)'   => 'PT. Persada Sejahtera Agro Makmur (PKS)',
    'PT.PSAM (SITE)'  => 'PT. Persada Sejahtera Agro Makmur (SITE)',
    'PT.MAPA'         => 'PT. Mitra Agro Persada Abadi',
    'PT.PEAK (PKS)'   => 'PT. Persada Era Agro Kencana (PKS)',
    'PT.PEAK (SITE)'  => 'PT. Persada Era Agro Kencana (SITE)',
    'RO PALANGKARAYA' => 'RO Palangkaraya',
    'RO SAMPIT'       => 'RO Sampit',
    'PT.WCJU (SITE)'  => 'PT. Wana Catur Jaya Utama (SITE)',
    'PT.WCJU (PKS)'   => 'PT. Wana Catur Jaya Utama (PKS)'
);

$nama_pt_header = isset($pt_map[$data_ba['pt']]) ? $pt_map[$data_ba['pt']] : $data_ba['pt'];
$nama_pt_lokasi = isset($pt_map2[$data_ba['pt']]) ? $pt_map2[$data_ba['pt']] : $data_ba['pt'];
$kode_pt_surat = isset($kode_pt[$data_ba['pt']]) ? $kode_pt[$data_ba['pt']] : preg_replace('/[^A-Z0-9]/', '', strtoupper($data_ba['pt']));

$hari_indonesia = getHariIndonesia($data_ba['tanggal']);
$tanggal_indonesia = getTanggalIndonesia($data_ba['tanggal']);
$bulan_romawi = getBulanRomawi($data_ba['tanggal']);
$tahun_surat = date('Y', strtotime($data_ba['tanggal']));

$nomor_surat_lengkap = trim($data_ba['nomor_ba']) . '/BAP/' . $kode_pt_surat . '/' . $bulan_romawi . '/' . $tahun_surat;

$alamat_ho = 'Jl. Radio Dalam Raya No.48, Gandaria Utara, Kec. Kebayoran Baru, Kota Jakarta Selatan, Daerah Khusus Ibukota Jakarta 12140.';
$show_alamat = ($data_ba['pt'] === 'PT.MSAL (HO)');

$barang_list = array();
$total_qty_barang = 0;
$total_harga_barang = 0;

$stmt_barang = $koneksi->prepare("
    SELECT 
        id,
        pt,
        coa,
        merk,
        sn,
        tahun_perolehan,
        harga_beli,
        `user`,
        alasan_penghapusan,
        kondisi
    FROM barang_pemutihan
    WHERE id_ba = ?
    ORDER BY id ASC
");

if (!$stmt_barang) {
    die('Prepare query barang gagal.');
}

$id_barang = 0;
$barang_pt = '';
$barang_coa = '';
$barang_merk = '';
$barang_sn = '';
$barang_tahun_perolehan = 0;
$barang_harga_beli = 0;
$barang_user = '';
$barang_alasan_penghapusan = '';
$barang_kondisi = '';

$stmt_barang->bind_param("i", $data_ba['id']);
$stmt_barang->execute();
$stmt_barang->bind_result(
    $id_barang,
    $barang_pt,
    $barang_coa,
    $barang_merk,
    $barang_sn,
    $barang_tahun_perolehan,
    $barang_harga_beli,
    $barang_user,
    $barang_alasan_penghapusan,
    $barang_kondisi
);

while ($stmt_barang->fetch()) {
    $row_barang = array(
        'id' => (int)$id_barang,
        'pt' => ($barang_pt !== null) ? (string)$barang_pt : '',
        'coa' => ($barang_coa !== null) ? (string)$barang_coa : '',
        'merk' => ($barang_merk !== null) ? (string)$barang_merk : '',
        'sn' => ($barang_sn !== null) ? (string)$barang_sn : '',
        'tahun_perolehan' => ($barang_tahun_perolehan !== null) ? (string)$barang_tahun_perolehan : '',
        'harga_beli' => ($barang_harga_beli !== null) ? (int)$barang_harga_beli : 0,
        'user' => ($barang_user !== null) ? (string)$barang_user : '',
        'alasan_penghapusan' => ($barang_alasan_penghapusan !== null) ? (string)$barang_alasan_penghapusan : '',
        'kondisi' => ($barang_kondisi !== null) ? (string)$barang_kondisi : ''
    );

    $barang_list[] = $row_barang;
    $total_qty_barang += 1;
    $total_harga_barang += (int)$row_barang['harga_beli'];
}
$stmt_barang->close();

$signature_columns = buildSignatureColumns($data_ba);
$signature_header_groups = buildSignatureHeaderGroups($signature_columns);
$signature_col_count = count($signature_columns);
$signature_col_width = ($signature_col_count > 0) ? round(100 / $signature_col_count, 4) : 100;
$signature_city = isHoPemutihan($data_ba['pt']) ? 'Jakarta Selatan' : $nama_pt_lokasi;
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surat Output - BA Pemutihan</title>
    <style>
        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            font-family: "Times New Roman", Times, serif;
            color: #000;
            background: #fff;
            font-size: 12px;
            line-height: 1.35;
        }

        body {
            padding: 10px 0px;
            display: block;
        }

        /* Halaman HTML dibuat lebar penuh */
        .page {
            width: 100%;
            min-height: auto;
            background: #fff;
            margin: 0;
            display: block;
            padding-top: 8mm;
            padding-bottom: 8mm;
        }

        /* Isi surat di layar dibuat penuh */
        .page-inner {
            width: 100%;
            min-height: auto;
            max-width: none;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .title {
            font-size: 17px;
            font-weight: bold;
            text-transform: uppercase;
            margin: 0 0 2px 0;
            text-align: center;
        }

        .subtitle {
            font-size: 13px;
            font-weight: bold;
            margin: 0 0 14px 0;
            text-align: center;
        }

        p {
            margin: 0 0 8px 0;
            text-align: justify;
            font-size: 12px;
        }

        .section-line {
            margin: 10px 0 4px 0;
            font-size: 12px;
            font-weight: bold;
        }

        .section-no {
            display: inline-block;
            width: 22px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .items-table {
            margin-top: 4px;
        }

        .items-table th,
        .items-table td {
            border: 1px solid #000;
            padding: 3px 5px;
            vertical-align: top;
            font-size: 11px;
        }

        .items-table th {
            text-align: center;
            font-weight: bold;
        }

        .items-table td.center {
            text-align: center;
        }

        .items-table td.right {
            text-align: right;
        }

        .items-table tfoot td {
            font-weight: bold;
        }

        ol {
            margin: 0 0 8px 20px;
            padding: 0;
        }

        ol li {
            margin-bottom: 3px;
            text-align: justify;
            font-size: 12px;
        }

        .summary-list {
            margin: 0 0 8px 20px;
            padding: 0;
        }

        .summary-list li {
            margin-bottom: 3px;
            font-size: 12px;
        }

        .signature-wrap {
            margin-top: 14px;
        }

        .signature-page-break {
            display: none;
        }

        .signature-print-page {
            width: 100%;
        }

        .signature-date {
            text-align: right;
            margin-bottom: 4px;
            font-size: 11px;
        }

        .signature-grid {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            font-size: 11px;
        }

        .signature-grid td {
            border: 1px solid #000;
            text-align: center;
            vertical-align: top;
            padding: 2px 4px;
        }

        .signature-grid .head {
            font-weight: normal;
            height: 18px;
        }

        .signature-grid .spacer {
            height: 62px;
        }

        .signature-grid .name {
            height: 22px;
            vertical-align: bottom;
            white-space: nowrap;
        }

        .signature-grid .jabatan {
            height: 18px;
            white-space: nowrap;
        }

        .header-address {
            text-align: center;
            font-size: 11px;
            border-bottom: .1mm solid black;
        }

        .signature-grid .spacer {
            height: 72px;
            padding: 2px;
            vertical-align: middle;
        }

        .signature-grid .sign-box {
            width: 100%;
            height: 66px;
            display: block;
            position: relative;
            overflow: hidden;
        }

        .signature-grid .sign-image {
            max-width: 100%;
            max-height: 48px;
            display: block;
            margin: 0 auto 2px auto;
        }

        .signature-grid .sign-date-small {
            font-size: 9px;
            line-height: 1.1;
            text-align: center;
            white-space: nowrap;
        }

        .signature-grid .sign-empty {
            width: 100%;
            height: 48px;
            display: block;
        }

        .signature-grid .name {
            height: 24px;
            vertical-align: bottom;
            font-weight: bold;
            word-break: break-word;
            white-space: normal;
        }

        .signature-grid .jabatan {
            height: 24px;
            white-space: normal;
            word-break: break-word;
        }

        @page {
            size: A4 landscape;
            margin: 0;
            margin-top: 1mm;
            margin-right: 4mm;
            margin-bottom: 14mm;
            margin-left: 4mm;
        }

        @media print {

            html,
            body {
                width: 297mm;
                height: auto;
                margin: 0;
                padding: 0;
                background: #fff;
            }

            body {
                display: block;
            }

            .page {
                width: 297mm;
                min-height: auto;
                margin: 0 auto;
                padding-top: 8mm;
                padding-bottom: 14mm;
                display: block;
            }

            .page-inner {
                width: 289mm;
                min-height: auto;
                max-width: 289mm;
            }

            .signature-page-break {
                display: block;
                page-break-before: always;
                break-before: page;
                height: 0;
            }

            .signature-print-page {
                width: 100%;
                page-break-inside: avoid;
                break-inside: avoid;
            }

            .signature-wrap {
                margin-top: 0;
                page-break-inside: avoid;
                break-inside: avoid;
            }

            .signature-grid {
                page-break-inside: avoid;
                break-inside: avoid;
            }

            .signature-grid tr,
            .signature-grid td {
                page-break-inside: avoid;
                break-inside: avoid;
            }
        }
    </style>
</head>

<body>
    <div class="page">
        <div class="page-inner">
            <div class="header-address">
                <strong><?php echo h($nama_pt_header); ?></strong>
                <?php if ($show_alamat): ?>
                    <br>
                    <?php echo h($alamat_ho); ?>
                <?php endif; ?>
                <div style="height: 1mm;"></div>
            </div>

            <div class="title">BERITA ACARA PEMUTIHAN BARANG ASET</div>
            <div class="subtitle">Nomor: <?php echo h($nomor_surat_lengkap); ?></div>

            <p>
                Pada hari ini <?php echo h($hari_indonesia); ?>, <?php echo h($tanggal_indonesia); ?>, bertempat di <?php echo h($nama_pt_lokasi); ?>,
                telah dilakukan verifikasi dan pemutihan atas barang-barang aset yang tidak lagi layak digunakan atau tidak terdata dengan baik.
                Adapun hasil dari pemutihan ini adalah sebagai berikut:
            </p>

            <div class="section-line">
                <span class="section-no">I.</span> Identitas Barang yang Diperiksa
            </div>

            <table class="items-table">
                <thead>
                    <tr>
                        <th style="width:4%;">NO</th>
                        <th style="width:10%;">KATEGORI</th>
                        <th style="width:18%;">NAMA BARANG</th>
                        <th style="width:5%;">QTY</th>
                        <th style="width:12%;">SERIAL NUMBER</th>
                        <th style="width:9%;">TAHUN BELI /<br> PEROLEHAN</th>
                        <th style="width:12%;">HARGA BELI /<br> PEROLEHAN</th>
                        <th style="width:8%;">LOKASI<br>ASET / PT</th>
                        <th style="width:9%;">NAMA<br>PENGGUNA</th>
                        <th style="width:8%;">ALASAN<br>PENGHAPUSAN</th>
                        <th style="width:8%;">KONDISI</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($barang_list)): ?>
                        <?php $no_barang = 1; ?>
                        <?php foreach ($barang_list as $barang): ?>
                            <tr>
                                <td class="center"><?php echo $no_barang; ?></td>
                                <td><?php echo h(upperText($barang['coa'])); ?></td>
                                <td><?php echo h(upperText($barang['merk'])); ?></td>
                                <td class="center">1</td>
                                <td class="center"><?php echo h(upperText($barang['sn'])); ?></td>
                                <td class="center"><?php echo h(upperText($barang['tahun_perolehan'])); ?></td>
                                <td class="right"><?php echo h(formatRupiah($barang['harga_beli'])); ?></td>
                                <td class="center"><?php echo h(upperText($barang['pt'])); ?></td>
                                <td class="center"><?php echo h(upperText($barang['user'])); ?></td>
                                <td class="center"><?php echo h(upperText($barang['alasan_penghapusan'])); ?></td>
                                <td class="center"><?php echo h(upperText($barang['kondisi'])); ?></td>
                            </tr>
                            <?php $no_barang++; ?>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="11" class="center">TIDAK ADA DATA BARANG</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" class="center">TOTAL</td>
                        <td class="center"><?php echo $total_qty_barang; ?></td>
                        <td colspan="2" class="center">TOTAL</td>
                        <td class="right"><?php echo h(formatRupiah($total_harga_barang)); ?></td>
                        <td colspan="4"></td>
                    </tr>
                </tfoot>
            </table>

            <div class="section-line">
                <span class="section-no">II.</span> Hasil Pemutihan
            </div>
            <p style="margin-left: 7mm;">
                Setelah dilakukan pemeriksaan dan verifikasi terhadap barang-barang aset tersebut, didapati bahwa:
            </p>
            <ol>
                <li>Beberapa barang sudah tidak terpakai dan/atau rusak sehingga perlu dikeluarkan dari daftar aset.</li>
                <li>Barang yang tercatat namun tidak ditemukan di lokasi yang telah ditentukan akan dianggap hilang dan dilakukan pemutihan sesuai prosedur.</li>
                <li>Barang yang tidak sesuai dengan catatan akan diperbaiki atau diganti dengan yang sesuai, jika memungkinkan.</li>
            </ol>

            <div class="section-line">
                <span class="section-no">III.</span> Keputusan Pemutihan
            </div>
            <p style="margin-left: 7mm;">
                Berdasarkan hasil pemutihan, maka barang-barang yang tercantum dalam daftar di atas dianggap tidak layak digunakan
                dan akan dihapus dari daftar aset perusahaan/instansi, dengan rincian sebagai berikut:
            </p>
            <ul class="summary-list">
                <li>Jumlah Barang yang Diputihkan: <?php echo h($total_qty_barang); ?> Unit</li>
                <li>Total Nilai Barang yang Diputihkan: <?php echo h(formatRupiah($total_harga_barang)); ?></li>
            </ul>

            <div class="section-line">
                <span class="section-no">IV.</span> Tindakan Lanjutan
            </div>
            <p style="margin-left: 7mm;">
                Berdasarkan hasil pemutihan, tindakan lebih lanjut yang perlu dilakukan adalah sebagai berikut:
            </p>
            <ol>
                <li>Penghapusan data barang pada sistem pengelolaan aset.</li>
                <li>Penyusutan atau penghapusan nilai barang pada laporan keuangan sesuai dengan ketentuan yang berlaku.</li>
                <li>Proses pelepasan barang dengan cara dilelang atau sesuai dengan prosedur yang telah ditetapkan.</li>
            </ol>

            <div class="section-line">
                <span class="section-no">V.</span> Penutupan
            </div>
            <p style="margin-left: 7mm;">
                Demikian berita acara pemutihan barang aset ini dibuat dengan sebenarnya, dan ditandatangani oleh pihak-pihak
                yang terkait untuk keperluan administrasi lebih lanjut.
            </p>
            <div class="signature-page-break"></div>

            <div class="signature-print-page">
                <div class="signature-wrap">
                    <div class="signature-date"><?php echo h($signature_city); ?>, <?php echo h($tanggal_indonesia); ?></div>

                    <table class="signature-grid">
                        <colgroup>
                            <?php foreach ($signature_columns as $signature_col): ?>
                                <col style="width:<?php echo h($signature_col_width); ?>%;">
                            <?php endforeach; ?>
                        </colgroup>

                        <tr>
                            <?php foreach ($signature_header_groups as $group): ?>
                                <td class="head" <?php echo ($group['colspan'] > 1) ? ' colspan="' . (int)$group['colspan'] . '"' : ''; ?>>
                                    <?php echo h($group['header']); ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>

                        <tr>
                            <?php foreach ($signature_columns as $col): ?>
                                <?php
                                $approval = isset($col['approval']) ? (int)$col['approval'] : 0;
                                $autograph_src = buildAutographSrc(isset($col['autograph']) ? $col['autograph'] : null);
                                $tanggal_sign = formatTanggalApproveTtd(isset($col['tanggal']) ? $col['tanggal'] : '');
                                ?>
                                <td class="spacer">
                                    <div class="sign-box">
                                        <?php if ($approval === 1 && $autograph_src !== ''): ?>
                                            <img src="<?php echo h($autograph_src); ?>" alt="Tanda Tangan" class="sign-image">
                                        <?php else: ?>
                                            <span class="sign-empty"></span>
                                        <?php endif; ?>

                                        <?php if ($approval === 1 && $tanggal_sign !== ''): ?>
                                            <div class="sign-date-small"><?php echo h($tanggal_sign); ?></div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            <?php endforeach; ?>
                        </tr>

                        <tr>
                            <?php foreach ($signature_columns as $col): ?>
                                <td class="name"><?php echo h(isset($col['nama']) ? $col['nama'] : ''); ?></td>
                            <?php endforeach; ?>
                        </tr>

                        <tr>
                            <?php foreach ($signature_columns as $col): ?>
                                <td class="jabatan"><?php echo h(isset($col['jabatan']) ? $col['jabatan'] : ''); ?></td>
                            <?php endforeach; ?>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>

</html>