<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
  header("Location: ../login_registrasi.php");
  exit();
}
require_once '../koneksi.php';

// Ambil ID dari URL
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Ambil data berita acara
$sql = "SELECT * FROM berita_acara_pengembalian_v2 WHERE id = $id";
$result = $koneksi->query($sql);
if (!$result || $result->num_rows === 0) {
  die("Data tidak ditemukan.");
}

$data = $result->fetch_assoc();

$pt_code_map = array(
  'PT.MSAL (HO)'    => 'MSAL',
  'PT.MSAL (PKS)'   => 'MSAL',
  'PT.MSAL (SITE)'  => 'MSAL',
  'PT.PSAM (PKS)'   => 'PSAM',
  'PT.PSAM (SITE)'  => 'PSAM',
  'PT.MAPA'         => 'MAPA',
  'PT.PEAK (PKS)'   => 'PEAK',
  'PT.PEAK (SITE)'  => 'PEAK',
  'RO PALANGKARAYA' => 'RO',
  'RO SAMPIT'       => 'RO',
  'PT.WCJU (SITE)'  => 'WCJU',
  'PT.WCJU (PKS)'   => 'WCJU'
);

$pt_value = isset($data['pt']) ? trim($data['pt']) : '';
$lokasiKode = isset($pt_code_map[$pt_value]) ? $pt_code_map[$pt_value] : $pt_value;
$tanggal = isset($data['tanggal']) ? $data['tanggal'] : '';

$nomor_surat_title = 'Surat Berita Acara Pengembalian Inventaris';
if (!empty($data['nomor_ba']) && !empty($lokasiKode) && !empty($tanggal)) {
  $bulan_romawi = '';
  $bulan = (int) date('n', strtotime($tanggal));
  $romawi = array('I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII');
  if ($bulan >= 1 && $bulan <= 12) {
    $bulan_romawi = $romawi[$bulan - 1];
  }

  $nomor_surat_title .= ' - No. ' . $data['nomor_ba'] . '/' . $lokasiKode . '-MIS/' . $bulan_romawi . '/' . date('Y', strtotime($tanggal));
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <title><?php echo htmlspecialchars($nomor_surat_title, ENT_QUOTES, 'UTF-8'); ?></title>

  <!-- Bootstrap 5 -->
  <link
    rel="stylesheet"
    href="../assets/bootstrap-5.3.6-dist/css/bootstrap.min.css" />

  <!-- Bootstrap Icons -->
  <link
    rel="stylesheet"
    href="../assets/icons/icons-main/font/bootstrap-icons.min.css" />

  <!-- AdminLTE -->
  <link
    rel="stylesheet"
    href="../assets/adminlte/css/adminlte.css" />

  <!-- OverlayScrollbars -->
  <link
    rel="stylesheet"
    href="../assets/css/overlayscrollbars.min.css" />

  <!-- Favicon -->
  <link
    rel="icon" type="image/png"
    href="../assets/img/logo.png" />
  <style>
    body {
      margin: 0px;
      font-size: 16px;
    }

    .header {
      position: absolute;
      left: 60px;
      top: 0px;
      width: fit-content;
    }

    .header img {
      float: left;
      width: 70px;
      margin-right: 15px;
    }

    .barang th {
      border: 1px solid #000 !important;
    }

    .barang td {
      border-left: 1px solid #000 !important;
      border-right: 1px solid #000 !important;
      border-bottom: none !important;
    }

    .barang tbody tr:first-child td {
      border-bottom: none !important;
    }

    .barang tbody tr:last-child td {
      border-top: none !important;
      border-bottom: #000 solid 1px !important;
    }

    .section-title {
      border-bottom: 2px solid #000;
      font-weight: bold;
      margin-top: 30px;
      margin-bottom: 20px;
      text-transform: uppercase;
    }

    .ttd {
      margin-top: 40px;
    }

    .ttd .col {
      text-align: center;
    }

    .ttd .name {
      margin-top: 0px;
      font-weight: bold;
    }

    .pertama th {
      width: 120px;
    }

    .gambar-pengembalian {
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      gap: 20px;
    }

    .gambar-pengembalian img {
      max-width: 300px;
      max-height: 39vh;
      height: auto;
      width: auto;
      display: inline-block;
      margin: 10px;
      object-fit: contain;
      image-rendering: auto;
    }

    @media print {
      .page-break {
        page-break-before: always;
        /* atau page-break-after: always; */
        break-before: page;
      }
    }
  </style>
  <style>
    @media print {
      .page-break {
        page-break-before: always;
      }
    }
  </style>

</head>
<?php

function renderAutograph($blob, $alt)
{
  if (empty($blob)) {
    return '';
  }

  $mime = 'image/png';

  if (function_exists('finfo_open')) {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    if ($finfo) {
      $detectedMime = finfo_buffer($finfo, $blob);
      if ($detectedMime && strpos($detectedMime, 'image/') === 0) {
        $mime = $detectedMime;
      }
      finfo_close($finfo);
    }
  }

  $base64 = base64_encode($blob);

  return '<img class="position-absolute z-n1" src="data:' . $mime . ';base64,' . $base64 . '" alt="' . htmlspecialchars($alt, ENT_QUOTES, 'UTF-8') . '" style="height:110px; object-fit:contain;">';
}


// Fungsi bantu
function namaHari($tanggal)
{
  $hari = date('N', strtotime($tanggal));
  $namaHari = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
  return $namaHari[$hari - 1];
}
function namaBulanIndonesia($tanggal)
{
  $bulan = date('n', strtotime($tanggal));
  $namaBulan = [
    1 => 'Januari',
    'Februari',
    'Maret',
    'April',
    'Mei',
    'Juni',
    'Juli',
    'Agustus',
    'September',
    'Oktober',
    'November',
    'Desember'
  ];
  return $namaBulan[$bulan];
}
function bulanRomawi($tanggal)
{
  $bulan = date('n', strtotime($tanggal));
  $romawi = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];
  return $romawi[$bulan - 1];
}

// Ambil info karyawan
$nama_pengembali = isset($data['pengembali']) && trim($data['pengembali']) !== ''
  ? $data['pengembali']
  : '-';
$jabatan_pengembali = isset($data['jabatan_pengembali']) && trim($data['jabatan_pengembali']) !== ''
  ? $data['jabatan_pengembali']
  : '-';

$nama_penerima = isset($data['penerima']) && trim($data['penerima']) !== ''
  ? $data['penerima']
  : '-';
$jabatan_penerima = isset($data['jabatan_penerima']) && trim($data['jabatan_penerima']) !== ''
  ? $data['jabatan_penerima']
  : '-';

$nama_diketahui = isset($data['diketahui']) && trim($data['diketahui']) !== ''
  ? $data['diketahui']
  : '-';
$jabatan_diketahui = isset($data['jabatan_diketahui']) && trim($data['jabatan_diketahui']) !== ''
  ? $data['jabatan_diketahui']
  : '-';

$pt_logo_map = array(
  'PT.MSAL (HO)'   => '../assets/img/logo.png',
  'PT.MSAL (PKS)'  => '../assets/img/logo.png',
  'PT.MSAL (SITE)' => '../assets/img/logo.png',
  'PT.PSAM (PKS)'  => '../assets/img/psam.jpg',
  'PT.PSAM (SITE)' => '../assets/img/psam.jpg',
  'PT.MAPA'        => '../assets/img/mapa.jpg',
  'PT.PEAK (PKS)'  => '../assets/img/peak.jpg',
  'PT.PEAK (SITE)' => '../assets/img/peak.jpg',
  'PT.WCJU (SITE)' => '../assets/img/wcju.jpg',
  'PT.WCJU (PKS)'  => '../assets/img/wcju.jpg'
);

$logo_path = isset($pt_logo_map[$pt_value]) ? $pt_logo_map[$pt_value] : '';

$tanggal = $data['tanggal'];

// Ambil daftar barang berdasarkan ID berita acara
$barang = [];
$sqlBarang = "SELECT * FROM barang_pengembalian_v2 WHERE id_ba = $id";
$resultBarang = $koneksi->query($sqlBarang);
if ($resultBarang) {
  while ($row = $resultBarang->fetch_assoc()) {
    $barang[] = $row;
  }
}

// Ambil gambar-gambar terkait
$gambar_query = $koneksi->prepare("SELECT file_path FROM gambar_ba_pengembalian_v2 WHERE id_ba = ?");
$gambar_query->bind_param("i", $id);
$gambar_query->execute();
$gambar_result = $gambar_query->get_result();
$gambar_paths = [];
while ($row = $gambar_result->fetch_assoc()) {
  $gambar_paths[] = $row['file_path'];
}
?>


<body>

  <?php if ($logo_path !== ''): ?>
    <div class="header">
      <img src="<?php echo htmlspecialchars($logo_path, ENT_QUOTES, 'UTF-8'); ?>" alt="Logo">
    </div>
  <?php endif; ?>

  <div class="text-center mb-4">
    <h4 class="fw-bold text-uppercase">Berita Acara</h4>
    <h4 class="fw-bold text-uppercase border border-black border-top-0 border-end-0 border-start-0 border-5 pb-3">Pengembalian Asset MIS</h4>
    <p><strong>No. <?= $data['nomor_ba'] ?>/<?= $lokasiKode ?>-MIS/<?= bulanRomawi($tanggal) ?>/<?= date('Y', strtotime($tanggal)) ?></strong></p>
  </div>

  <p class="mb-0">Pada hari ini <strong><?= namaHari($tanggal) ?></strong>, tanggal <strong><?= date('d', strtotime($tanggal)) . ' ' . namaBulanIndonesia($tanggal) . ' ' . date('Y', strtotime($tanggal)) ?></strong>, kami yang bertandatangan di bawah ini:</p>

  <div class="mb-4">

    <table class="mb-0 pertama">
      <tbody>
        <tr>
          <th>Nama</th>
          <td>: <?= htmlspecialchars($nama_pengembali) ?></td>
        </tr>
        <tr>
          <th>Jabatan</th>
          <td>: <?= htmlspecialchars($jabatan_pengembali) ?></td>
        </tr>
        <tr>
          <th>Lokasi</th>
          <td>: <?= $data['pt'] ?></td>
        </tr>
      </tbody>
    </table>
    <p class="mb-3">Selanjutnya disebut PIHAK PERTAMA</p>

    <table class="mb-0 pertama">
      <tbody>
        <tr>
          <th>Nama</th>
          <td>: <?= htmlspecialchars($nama_penerima) ?></td>
        </tr>
        <tr>
          <th>Jabatan</th>
          <td>: <?= htmlspecialchars($jabatan_penerima) ?></td>
        </tr>
        <tr>
          <th>Lokasi</th>
          <td>: <?= $data['pt'] ?></td>
        </tr>
      </tbody>
    </table>
    <p class="mb-3">Selanjutnya disebut PIHAK KEDUA</p>

  </div>

  <div class="mb-4">
    <p><strong>PIHAK PERTAMA</strong> menyerahkan barang kepada <strong>PIHAK KEDUA</strong>, dan <strong>PIHAK KEDUA</strong> menyatakan telah menerima barang dari <strong>PIHAK PERTAMA</strong>. Berikut data terlampir:</p>

    <table class="table barang">
      <thead class="table-light">
        <tr class="text-center">
          <th style="width:5%;font-size: 12px;">No</th>
          <th style="font-size: 12px;">Jenis Barang</th>
          <th style="width:10%;font-size: 12px;">Jumlah</th>
          <th style="width:10%;font-size: 12px;">Kondisi</th>
          <th style="width:12%;font-size: 12px;">Keterangan</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($barang as $i => $b): ?>
          <tr>
            <td class="text-center" style="font-size: 12px;"><?= $i + 1 ?>.</td>
            <td style="font-size: 12px;">
              <?= htmlspecialchars($b['coa']) ?>, <?= htmlspecialchars($b['merk']) ?><br>
              SN: <?= htmlspecialchars($b['sn']) ?><br>
              PO: <?= htmlspecialchars($b['po']) ?>
            </td>
            <td style="font-size: 12px;">1 Unit</td>
            <td style="font-size: 12px;"><?= htmlspecialchars($b['kondisi']) ?></td>
            <td style="font-size: 12px;"><?= htmlspecialchars($b['keterangan']) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <div class="mb-4">
    <p>Demikian berita acara serah terima barang ini dibuat oleh kedua belah pihak. Adapun barang tersebut dengan kondisi <strong>keterangan diatas</strong>, sejak penandatangan berita acara ini, maka perjanjian sebelumnya tidak berlaku.</p>
    <p><strong>Notes:</strong> Penggantian bertujuan meningkatkan performa dan mobilitas pekerjaan.</p>
  </div>

  <p><strong>Jakarta, <?= date('d', strtotime($tanggal)) . ' ' . namaBulanIndonesia($tanggal) . ' ' . date('Y', strtotime($tanggal)) ?></strong></p>

  <?php if ($i > 2): ?>
    <div class="page-break"></div>
  <?php endif; ?>

  <div class="row ttd text-center">
    <div class="col d-flex flex-column justify-content-end">
      <p class="m-0">Diserahkan Oleh,</p>
      <div style="width:100%; height:21mm; display:flex; align-items:center; justify-content:center;">
        <?php echo renderAutograph(isset($data['autograph_1']) ? $data['autograph_1'] : null, 'TTD Diserahkan Oleh'); ?>
      </div>
      <p class="name"><?php echo htmlspecialchars($nama_pengembali, ENT_QUOTES, 'UTF-8'); ?></p>
      <p><?php echo htmlspecialchars($jabatan_pengembali, ENT_QUOTES, 'UTF-8'); ?></p>
    </div>

    <div class="col d-flex flex-column justify-content-end">
      <p class="m-0">Diketahui,</p>
      <div style="width:100%; height:21mm; display:flex; align-items:center; justify-content:center;">
        <?php echo renderAutograph(isset($data['autograph_3']) ? $data['autograph_3'] : null, 'TTD Diketahui'); ?>
      </div>
      <p class="name"><?php echo htmlspecialchars($nama_diketahui, ENT_QUOTES, 'UTF-8'); ?></p>
      <p><?php echo htmlspecialchars($jabatan_diketahui, ENT_QUOTES, 'UTF-8'); ?></p>
    </div>

    <div class="col d-flex flex-column justify-content-end">
      <p class="m-0">Diterima Oleh,</p>
      <div style="width:100%; height:21mm; display:flex; align-items:center; justify-content:center;">
        <?php echo renderAutograph(isset($data['autograph_2']) ? $data['autograph_2'] : null, 'TTD Diterima Oleh'); ?>
      </div>
      <p class="name"><?php echo htmlspecialchars($nama_penerima, ENT_QUOTES, 'UTF-8'); ?></p>
      <p><?php echo htmlspecialchars($jabatan_penerima, ENT_QUOTES, 'UTF-8'); ?></p>
    </div>
  </div>



  <?php
  $gambar_per_halaman = 4;
  $total_gambar = count($gambar_paths);
  for ($i = 0; $i < $total_gambar; $i += $gambar_per_halaman):
    $gambar_chunk = array_slice($gambar_paths, $i, $gambar_per_halaman);
  ?>
    <div class="page-break"></div>


    <div class="mt-3 d-flex flex-column align-items-center">
      <p><strong>No. <?= $data['nomor_ba'] ?>/<?= $lokasiKode ?>-MIS/<?= bulanRomawi($tanggal) ?>/<?= date('Y', strtotime($tanggal)) ?></strong></p>
      <p><strong>LAMPIRAN FOTO:</strong></p>
    </div>
    <div class="gambar-pengembalian">
      <?php foreach ($gambar_chunk as $gambar): ?>
        <img src="<?php echo $gambar; ?>" alt="Gambar Kerusakan">
      <?php endforeach; ?>
    </div>

  <?php endfor; ?>

  <!-- Bootstrap 5 -->
  <script src="../assets/bootstrap-5.3.6-dist/js/bootstrap.min.js"></script>
</body>

</html>