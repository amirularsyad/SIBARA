<?php
session_start();

// Jika belum login, arahkan ke halaman login
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../login_registrasi.php");
    exit();
}

// Jika login tapi bukan Admin, arahkan ke halaman approval
if (!isset($_SESSION['hak_akses']) || $_SESSION['hak_akses'] !== 'Admin') {
    header("Location: ../personal/approval.php");
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

$nomor_surat = "No: {$nomor_ba}/BAMTIA/MIS/{$bulan_romawi}/{$tahun_ba}";
// === KONVERSI NAMA PT ===
function ubahNamaPT($kode) {
    $map = [
        "PT.MSAL (HO)"   => "PT MULIA SAWIT AGRO LESTARI HEAD OFFICE",
        "PT.MSAL (SITE)" => "PT MULIA SAWIT AGRO LESTARI SITE",
        "PT.WCJU"        => "PT WANA CATUR JAYA UTAMA",
        "PT.MAPA"        => "PT MITRA AGRO PERSADA ABADI",
        "PT.PSAM"        => "PT PERSADA SEJAHTERA AGRO MAKMUR",
        "PT.PEAK"        => "PT PERSADA ERA AGRO KENCANA",
        "PT.KPP"         => "PT KERENG PANGI PERDANA"
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

// === TEKS DINAMIS ===
$paragraf_mutasi = "
<p class='medium'>
    Pada hari ini {$hari_tanggal}, Tanggal {$tgl_angka} {$bulan_tulisan} {$tahun_tulisan}, 
    telah dilaksanakan mutasi aset dari unit penanggung jawab barang milik {$pt_asal} 
    kepada {$pt_tujuan}.
</p>";
?>

<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Berita Acara Mutasi Aset - <?= htmlspecialchars($nomor_surat) ?></title>
  <style>
    @page {
      size: A4 landscape;
      margin: 20mm;
    }
    body {
      box-sizing: border-box;
      font-family: Arial, Helvetica, sans-serif;
      font-size: 12pt;
      color: #000;
      width: 297mm;
      height: 210mm;
      margin: 0 auto;
      padding: 20mm;
      background: #fff;
    }
    .container { width: 100%; }
    .header { text-align: center; margin-bottom: 24px; }
    .title { font-weight: bold; font-size: 16pt; text-transform: uppercase; margin-bottom: 4px; }
    .doc-number { margin-bottom: 16px; font-style: italic; }
    p { margin: 6px 0; }
    table.items { width: 100%; border-collapse: collapse; margin: 16px 0; }
    table.items th, table.items td { border: 1px solid #000; padding: 6px; vertical-align: top; font-size: 8.5pt; text-align: center; }
    table.items th { background: #ffffffff; font-weight: 500; }
    .small { font-size: 10pt; }
    .medium { font-size: 11pt; }
    table.signatures { width: 100%; border-collapse: collapse; margin: 16px 0; }
    table.signatures th, table.signatures td { border: 1px solid #000; vertical-align: top; text-align: center; }
    table.signatures th { background: #ffffffff; font-weight: 500; font-size: 11pt; }
    table.signatures tbody tr:first-child td { height: 70px; border-bottom: none; }
    table.signatures tbody tr:last-child td { height: max-content; font-size: 10pt; border-top: none;}
    .sign-block { width: 30%; text-align: center; }
    .sign-space { height: 80px; }
    .footer-note { margin-top: 24px; font-size: 10pt; }
    .italic { font-style: italic; }
    .center-text{ text-align: center; }
    @media print {
      body { padding: 0; margin: 0; width: 100%; height: 100%; }
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <div class="title">Berita Acara Mutasi Aset Internal</div>
      <div class="doc-number"><?= htmlspecialchars($nomor_surat) ?></div>
    </div>

    <?= $paragraf_mutasi ?>
    <p class="medium">Adapun barang yang dimaksud adalah sebagai berikut :</p>

    <table class="items">
      <thead>
        <tr>
          <th style="width:4%">No</th>
          <th style="width:13%">Kode Barang/COA</th>
          <th style="width:12%">No PO</th>
          <th style="width:14%">Nama Barang</th>
          <th style="width:15%;">SN</th>
          <th style="width:6%">Jumlah</th>
          <th style="width:6%">Satuan</th>
          <th style="width:10%">Lokasi Asal</th>
          <th style="width:10%">Lokasi Tujuan</th>
          <th style="width:10%">Keterangan</th>
        </tr>
      </thead>
      <tbody>
      <?php
      if (!empty($barang_list)) {
          // grupkan berdasarkan PO
          $grouped = [];
          foreach ($barang_list as $b) {
              $po = $b['po'];
              $coa = $b['coa'];
              $merk = $b['merk'];
              $sn = trim($b['sn']);

              // grupkan SN berdasarkan merk dalam satu PO
              $grouped[$po]['coa'][$coa] = $coa;
              $grouped[$po]['merk'][$merk]['sn'][] = $sn;
          }

          // fungsi ubah PT
          function ubahPT($pt) {
              $map = [
                  "PT.MSAL (HO)"   => "PT MSAL HO",
                  "PT.MSAL (SITE)" => "PT MSAL SITE",
                  "PT.WCJU"        => "PT WCJU",
                  "PT.MAPA"        => "PT MAPA",
                  "PT.PSAM"        => "PT PSAM",
                  "PT.PEAK"        => "PT PEAK",
                  "PT.KPP"         => "PT KPP"
              ];
              return isset($map[$pt]) ? $map[$pt] : htmlspecialchars($pt);
          }

          $no = 1;
          foreach ($grouped as $po => $data) {
              $jumlah = 0;
              foreach ($data['merk'] as $m) {
                  $jumlah += count($m['sn']);
              }

              $rowspan = count($data['merk']);
              $pt_asal = ubahPT($data_ba['pt_asal']);
              $pt_tujuan = ubahPT($data_ba['pt_tujuan']);
              $keterangan = htmlspecialchars($data_ba['keterangan'] ?: '-');

              $rowIndex = 0;
              foreach ($data['merk'] as $merk => $mData) {
                  echo "<tr>";

                  // kolom No + COA + PO (sekali di awal PO)
                  if ($rowIndex === 0) {
                      echo "<td rowspan='{$rowspan}'>{$no}</td>";
                      echo "<td rowspan='{$rowspan}'>" . implode('<br>', $data['coa']) . "</td>";
                      echo "<td rowspan='{$rowspan}'>" . htmlspecialchars($po) . "</td>";
                  }

                  // Nama Barang
                  echo "<td>" . htmlspecialchars($merk) . "</td>";

                  // SN (bisa banyak)
                  echo "<td><span>" . implode('<br>', array_map('htmlspecialchars', $mData['sn'])) . "</span></td>";

                  // Jumlah + Satuan + Lokasi Asal + Tujuan + Ket (sekali per PO)
                  if ($rowIndex === 0) {
                      echo "<td rowspan='{$rowspan}'>{$jumlah}</td>";
                      echo "<td rowspan='{$rowspan}'>Unit</td>";
                      echo "<td rowspan='{$rowspan}'>" . htmlspecialchars($pt_asal) . "</td>";
                      echo "<td rowspan='{$rowspan}'>" . htmlspecialchars($pt_tujuan) . "</td>";
                      echo "<td rowspan='{$rowspan}'>" . nl2br($keterangan) . "</td>";
                  }

                  echo "</tr>";
                  $rowIndex++;
              }

              $no++;
          }
      } else {
          echo '<tr><td colspan="10" class="text-center">Tidak ada data barang.</td></tr>';
      }
      ?>
      </tbody>
    </table>

    <p class="medium">*) Sebutkan kondisi barang yang diterima</p>

    <p class="medium">Demikian Berita Acara ini dilaksanakan.</p>

    <table class="signatures">
      <thead>
        <tr>
          <th style="width:20%">Yang Menyerahkan Aset:</th>
          <th colspan="2" style="width:40%">Diketahui:</th>
          <th colspan="2" style="width:40%">Yang Menerima:</th>
        </tr>
      </thead>
      <tbody>
      <?php
      // fungsi ambil jabatan berdasarkan lokasi
      function getJabatan($nama, $lokasi, $koneksi) {
          if (empty($nama)) return '-';

          if ($lokasi === 'PT.MSAL (HO)') {
              $sql = "SELECT CONCAT(jabatan, ' ', departemen) AS jabatan FROM data_karyawan WHERE nama = ?";
          } else {
              $sql = "SELECT posisi AS jabatan FROM data_karyawan_test WHERE nama = ?";
          }

          $stmt = mysqli_prepare($koneksi, $sql);
          mysqli_stmt_bind_param($stmt, 's', $nama);
          mysqli_stmt_execute($stmt);
          $result = mysqli_stmt_get_result($stmt);
          $row = mysqli_fetch_assoc($result);

          return isset($row['jabatan']) ? $row['jabatan'] : '-';

      }

      $pt_asal    = $data_ba['pt_asal'];
      $pt_tujuan  = $data_ba['pt_tujuan'];

      $pengirim    = $data_ba['pengirim'];
      $diketahui1  = $data_ba['diketahui1'];
      $diketahui2  = $data_ba['diketahui2'];
      $penerima1   = $data_ba['penerima1'];
      $penerima2   = $data_ba['penerima2'];

      $jab_pengirim   = getJabatan($pengirim, $pt_asal, $koneksi);
      $jab_diketahui1 = getJabatan($diketahui1, $pt_asal, $koneksi);
      $jab_diketahui2 = getJabatan($diketahui2, $pt_asal, $koneksi);
      $jab_penerima1  = getJabatan($penerima1, $pt_tujuan, $koneksi);
      $jab_penerima2  = getJabatan($penerima2, $pt_tujuan, $koneksi);
      ?>

      <tr>
        <?php
        // Ambil tanda tangan & tanggal approve dari data BA Mutasi
        for ($i = 1; $i <= 5; $i++) {
            $approval = $data_ba['approval_' . $i];
            $autograph = $data_ba['autograph_' . $i];
            $tanggal_approve = $data_ba['tanggal_approve_' . $i];

            echo '<td style="width:20%; text-align:center; vertical-align:bottom; position:relative;">';

            if ($approval == 1) {
                if (!empty($autograph)) {
                    // Tambahkan digital sign di pojok kiri atas
                    echo '<div style="position:absolute; top:2px; left:4px; color:#555; font-size:10px;">Digital Sign</div>';

                    // tampilkan gambar tanda tangan dari blob
                    $imgData = base64_encode($autograph);
                    echo '<img src="data:image/png;base64,' . $imgData . '" alt="Tanda Tangan ' . $i . '" style="width:150px; height:auto; margin-bottom:5px;">';
                }

                if (!empty($tanggal_approve)) {
                    $bulanIndo = [
                        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
                    ];
                    $tglObj = new DateTime($tanggal_approve);
                    $formatted_date = $tglObj->format('d') . ' ' . $bulanIndo[(int)$tglObj->format('m')] . ' ' . $tglObj->format('Y');
                    echo '<div style="font-size:10px; color:#555; position:absolute; bottom:0; right:4px;">' . $formatted_date . '</div>';

                }
            } else {
                // Placeholder jika belum approve
                echo '<div style="height:80px;"></div>';
            }

            echo '</td>';
        }
        ?>
      </tr>

      <tr>
        <td><?= htmlspecialchars($pengirim) ?><br>
        (<?= htmlspecialchars($jab_pengirim) ?>)
        </td>
        <td><?= htmlspecialchars($diketahui1) ?><br>
        (<?= htmlspecialchars($jab_diketahui1) ?>)
        </td>
        <td><?= htmlspecialchars($diketahui2) ?><br>
        (<?= htmlspecialchars($jab_diketahui2) ?>)
        </td>
        <td><?= htmlspecialchars($penerima1) ?><br>
        (<?= htmlspecialchars($jab_penerima1) ?>)
        </td>
        <td><?= htmlspecialchars($penerima2) ?><br>
        (<?= htmlspecialchars($jab_penerima2) ?>)
        </td>
      </tr>
      </tbody>
    </table>

    <div style="page-break-before: always;"></div>
    <p class="center-text">LAMPIRAN<br><span class="italic"><?= htmlspecialchars($nomor_surat) ?></span></p>
    <?php if (!empty($gambar_list)): ?>
      <div style="display: flex; flex-wrap: wrap; justify-content: center; gap: 10px; margin-top: 20px;">
        <?php 
        $count = 0;
        foreach ($gambar_list as $g) {
            $path = htmlspecialchars($g['file_path']);
            echo '<div style="width: 30%; text-align: center; margin-bottom: 15px;">';
            echo '<img src="' . $path . '" alt="Lampiran ' . ($count+1) . '" style="width:100%; max-height:200px; object-fit:contain; border:1px solid #ccc;">';
            echo '</div>';
            $count++;
        }
        ?>
      </div>
    <?php else: ?>
      <p class="medium" style="text-align:center; margin-top:30px;">Tidak ada gambar lampiran untuk berita acara ini.</p>
    <?php endif; ?>
  </div>
</body>
</html>
