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
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Surat Berita Acara Pengembalian Inventaris</title>

  <!-- Bootstrap 5 -->
    <link 
      rel="stylesheet" 
      href="../assets/bootstrap-5.3.6-dist/css/bootstrap.min.css"
    />

  <!-- Bootstrap Icons -->
    <link
        rel="stylesheet"
        href="../assets/icons/icons-main/font/bootstrap-icons.min.css"
    />

  <!-- AdminLTE -->
    <link 
        rel="stylesheet" 
        href="../assets/adminlte/css/adminlte.css" 
    />

  <!-- OverlayScrollbars -->
    <link
        rel="stylesheet"
        href="../assets/css/overlayscrollbars.min.css"
    />

  <!-- Favicon -->
    <link 
        rel="icon" type="image/png" 
        href="../assets/img/logo.png"
    />
<style>
    body {
      margin: 0px;
      font-size: 16px;
    }

    .header{
      position: absolute;
      left: 60px;
      top: 0px;
      width:fit-content;
    }

    .header img {
    float: left;
    width: 70px;
    margin-right: 15px;
    }

    .barang th{
      border: 1px solid #000 !important;
    }
    .barang td{
      border-left: 1px solid #000 !important;
      border-right: 1px solid #000 !important;
      border-bottom: none !important;
    }
    .barang tbody tr:first-child td{
      border-bottom: none !important;
    }

    .barang tbody tr:last-child td{
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

    .pertama th{
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
        page-break-before: always; /* atau page-break-after: always; */
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

require_once '../koneksi.php';

// Ambil ID dari URL
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
// Ambil data berita acara
$sql = "SELECT * FROM berita_acara_pengembalian WHERE id = $id";
$result = $koneksi->query($sql);
if (!$result || $result->num_rows === 0) die("Data tidak ditemukan.");

$data = $result->fetch_assoc();

// Ambil semua karyawan
$karyawan = [];
$res = $koneksi->query("SELECT * FROM data_karyawan");
while ($row = $res->fetch_assoc()) {
  $karyawan[$row['nama']] = $row;
}

// Fungsi bantu
function namaHari($tanggal) {
  $hari = date('N', strtotime($tanggal));
  $namaHari = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
  return $namaHari[$hari - 1];
}
function namaBulanIndonesia($tanggal) {
  $bulan = date('n', strtotime($tanggal));
  $namaBulan = [1=>'Januari','Februari','Maret','April','Mei','Juni',
                'Juli','Agustus','September','Oktober','November','Desember'];
  return $namaBulan[$bulan];
}
function bulanRomawi($tanggal) {
  $bulan = date('n', strtotime($tanggal));
  $romawi = ['I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII'];
  return $romawi[$bulan - 1];
}

// Ambil info karyawan
// $pengembali = $karyawan[$data['nama_pengembali']] ?? ['jabatan'=>'-', 'departemen'=>'-'];
// $penerima = $karyawan[$data['nama_penerima']] ?? ['jabatan'=>'-', 'departemen'=>'-'];
$pengembali = isset($karyawan[$data['nama_pengembali']]) ? $karyawan[$data['nama_pengembali']] : array('jabatan' => '-', 'departemen' => '-');
$penerima   = isset($karyawan[$data['nama_penerima']]) ? $karyawan[$data['nama_penerima']] : array('jabatan' => '-', 'departemen' => '-');

// Proses data 'diketahui' agar ambil nama saja
$diketahui_nama = $data['diketahui'];
// Tangani kasus khusus
if ($diketahui_nama === 'Tedy Paronto - Dept. Head (MIS)') {
    $diketahui_nama = 'Tedy Paronto';
} elseif ($diketahui_nama === 'M. Diecy Firmansyah - Staf (MIS)') {
    $diketahui_nama = 'M. Diecy Firmansyah';
}else{$diketahui_nama = 'Tedy Paronto';}
// Ambil data karyawan berdasarkan nama yang sudah disesuaikan
// $yang_mengetahui = $karyawan[$diketahui_nama] ?? ['jabatan'=>'-', 'departemen'=>'-'];
$yang_mengetahui = isset($karyawan[$diketahui_nama]) ? $karyawan[$diketahui_nama] : array('jabatan' => '-', 'departemen' => '-');


$lokasiKode = $data['lokasi_pengembali'] === 'PT.MSAL (HO)' ? 'MSAL' : $data['lokasi_pengembali'];
$tanggal = $data['tanggal'];

// Ambil daftar barang berdasarkan ID berita acara
$barang = [];
$sqlBarang = "SELECT * FROM barang_pengembalian WHERE ba_pengembalian_id = $id";
$resultBarang = $koneksi->query($sqlBarang);
if ($resultBarang) {
  while ($row = $resultBarang->fetch_assoc()) {
    $barang[] = $row;
  }
}

// Ambil gambar-gambar terkait
$gambar_query = $koneksi->prepare("SELECT file_path FROM gambar_ba_pengembalian WHERE ba_pengembalian_id = ?");
$gambar_query->bind_param("i", $id);
$gambar_query->execute();
$gambar_result = $gambar_query->get_result();
$gambar_paths = [];
while ($row = $gambar_result->fetch_assoc()) {
    $gambar_paths[] = $row['file_path'];
}
?>


<body>

  <div class="header">
        <img src="../assets/img/logo.png" alt="Logo MSAL">
    </div>

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
                <td>: <?= $data['nama_pengembali'] ?></td>
            </tr>
            <tr>
                <th>Jabatan</th>
                <td>: <?= $pengembali['jabatan'] . ' ' . $pengembali['departemen'] ?></td>
            </tr>
            <tr>
                <th>Lokasi</th>
                <td>: <?= $data['lokasi_pengembali'] ?></td>
            </tr>
        </tbody>
    </table>
    <p class="mb-3">Selanjutnya disebut PIHAK PERTAMA</p>

    <table class="mb-0 pertama">
        <tbody>
            <tr>
                <th>Nama</th>
                <td>: <?= $data['nama_penerima'] ?></td>
            </tr>
            <tr>
                <th>Jabatan</th>
                <td>: <?= $penerima['jabatan'] . ' ' . $penerima['departemen'] ?></td>
            </tr>
            <tr>
                <th>Lokasi</th>
                <td>: <?= $data['lokasi_penerima'] ?></td>
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
            <?= htmlspecialchars($b['jenis_barang']) ?>
          </td>
          <td style="font-size: 12px;"><?= htmlspecialchars($b['jumlah']) ?> Unit</td>
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
      <p>Diserahkan Oleh,</p>
      <div class="" style="width:100%; height: 70px; display:flex; align-items:center; justify-content:center;">
        <?php if ($data['approval_1'] == 1): ?>
            <div class="" style="width:110px; height: 50px; display:flex; justify-content:center; align-items:center; border:1px solid green; border-radius:5px; color:green;">APPROVED</div>
        <?php endif; ?>
      </div>
      <p class="name"><?= $data['nama_pengembali'] ?></p>
      <p><?= $pengembali['jabatan'] . ' ' . $pengembali['departemen'] ?></p>
    </div>
    <div class="col d-flex flex-column justify-content-end">
      <p>Diketahui,</p>
      <div class="" style="width:100%; height: 70px; display:flex; align-items:center; justify-content:center;">
        <?php if ($data['approval_3'] == 1): ?>
            <div class="" style="width:110px; height: 50px; display:flex; justify-content:center; align-items:center; border:1px solid green; border-radius:5px; color:green;">APPROVED</div>
        <?php endif; ?>
      </div>
      <p class="name"><?= $diketahui_nama ?></p>
      <p><?= $yang_mengetahui['jabatan'] . ' ' . $yang_mengetahui['departemen'] ?></p>
    </div>
    <div class="col d-flex flex-column justify-content-end">
      <p>Diterima Oleh,</p>
      <div class="" style="width:100%; height: 70px; display:flex; align-items:center; justify-content:center;">
        <?php if ($data['approval_2'] == 1): ?>
            <div class="" style="width:110px; height: 50px; display:flex; justify-content:center; align-items:center; border:1px solid green; border-radius:5px; color:green;">APPROVED</div>
        <?php endif; ?>
      </div>
      <p class="name"><?= $data['nama_penerima'] ?></p>
      <p><?= $penerima['jabatan'] . ' ' . $penerima['departemen'] ?></p>
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