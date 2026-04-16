<?php
session_start();
require_once '../koneksi.php';

$jenis = isset($_GET['jenis']) ? $_GET['jenis'] : '';
$id_surat = isset($_GET['id']) ? $_GET['id'] : '';
$nama_peminta = isset($_GET['nama_peminta']) ? $_GET['nama_peminta'] : '';

if (empty($jenis) || empty($id_surat)) {
    die("Parameter tidak lengkap.");
}

$jenis_ba2 = "";

if ($jenis === "notebook") {
    $jenis_ba2 = "Serah terima Notebook Inventaris";
} elseif ($jenis === "kerusakan") {
    $jenis_ba2 = "Kerusakan";
} elseif ($jenis === "mutasi") {
    $jenis_ba2 = "Mutasi Aset Internal";
} elseif ($jenis === "pengembalian") {
    $jenis_ba2 = "Pengembalian Inventaris";
} elseif ($jenis === "st_asset") {
    $jenis_ba2 = "Serah Terima Penggunaan Asset Inventaris";
} elseif ($jenis === "pemutihan") {
    $jenis_ba2 = "Pemutihan Aset";
} else {
    $jenis_ba2 = $jenis;
}

$nomor_ba_final = "";
$processed = "";
$data_ba = array();

$boxWidth = ($jenis === 'pemutihan') ? '297mm' : '210mm';

if ($jenis === 'kerusakan') {

    // Ambil data dari database
    $stmt = $koneksi->prepare("
        SELECT nomor_ba, pt, tanggal, alasan_hapus
        FROM berita_acara_kerusakan 
        WHERE id = ?
        LIMIT 1
    ");
    $stmt->bind_param("i", $id_surat);
    $stmt->execute();
    $result = $stmt->get_result();
    $data_ba = $result->fetch_assoc();

    if ($data_ba) {

        // ----- Konversi bulan menjadi angka Romawi -----
        function bulanRomawi($bulan) {
            $romawi = array(
                1 => "I", 2 => "II", 3 => "III", 4 => "IV",
                5 => "V", 6 => "VI", 7 => "VII", 8 => "VIII",
                9 => "IX", 10 => "X", 11 => "XI", 12 => "XII"
            );
            return $romawi[(int)$bulan];
        }

        // Parsing tanggal
        $tgl = date('Y-m-d', strtotime($data_ba['tanggal']));
        $bulan = bulanRomawi(date('n', strtotime($tgl)));
        $tahun = date('Y', strtotime($tgl));

        // Cek nilai PT
        $nilai_pt = ($data_ba['pt'] === "PT.MSAL (HO)") ? "MIS-HO" : "MIS/BAK";

        // Susun nomor BA final
        $nomor_ba_final = $data_ba['nomor_ba'] . "/" . $nilai_pt . "/" . $bulan . "/" . $tahun;

        $stmtCariPending = $koneksi->prepare("
            SELECT nomor_ba, pt, tanggal 
            FROM berita_acara_kerusakan 
            WHERE id = ? AND pending_hapus = 1
            LIMIT 1
        ");
        $stmtCariPending->bind_param("i", $id_surat);
        $stmtCariPending->execute();
        $resultCariPending = $stmtCariPending->get_result();
        $data_cari_pending = $resultCariPending->fetch_assoc();
        if (empty($data_cari_pending)) {
            $processed = "done";
        }
        $stmtCariPending->close();

    } else {
        $nomor_ba_final = "Data tidak ditemukan";
    }

    $stmt->close();
}
elseif ($jenis === 'pengembalian') {

    $stmt = $koneksi->prepare("
        SELECT nomor_ba, pt, tanggal, alasan_hapus, pending_hapus
        FROM berita_acara_pengembalian_v2
        WHERE id = ?
        LIMIT 1
    ");
    $stmt->bind_param("i", $id_surat);
    $stmt->execute();
    $result = $stmt->get_result();
    $data_ba = $result->fetch_assoc();

    if ($data_ba) {

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

        $pt_value = isset($data_ba['pt']) ? trim($data_ba['pt']) : '';
        $lokasiKode = isset($pt_code_map[$pt_value]) ? $pt_code_map[$pt_value] : $pt_value;
        $tanggal = isset($data_ba['tanggal']) ? $data_ba['tanggal'] : '';

        if (!empty($data_ba['nomor_ba']) && !empty($lokasiKode) && !empty($tanggal)) {
            $bulan_romawi = '';
            $bulan = (int) date('n', strtotime($tanggal));
            $romawi = array('I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII');

            if ($bulan >= 1 && $bulan <= 12) {
                $bulan_romawi = $romawi[$bulan - 1];
            }

            $nomor_ba_final = $data_ba['nomor_ba'] . '/' . $lokasiKode . '-MIS/' . $bulan_romawi . '/' . date('Y', strtotime($tanggal));
        } else {
            $nomor_ba_final = "Data tidak ditemukan";
        }

        if ((int)$data_ba['pending_hapus'] === 0) {
            $processed = "done";
        }

    } else {
        $nomor_ba_final = "Data tidak ditemukan";
        $processed = "done";
    }

    $stmt->close();
}
elseif ($jenis === 'mutasi') {

    $processed = ''; // default

    // Ambil data TANPA filter pending_hapus
    $stmt = $koneksi->prepare("
        SELECT 
            nomor_ba,
            pt_asal,
            tanggal,
            pending_hapus
        FROM berita_acara_mutasi 
        WHERE id = ?
        LIMIT 1
    ");
    $stmt->bind_param("i", $id_surat);
    $stmt->execute();
    $result = $stmt->get_result();
    $data_ba = $result->fetch_assoc();

    if ($data_ba) {

        // ===== Tentukan status proses =====
        if ((int)$data_ba['pending_hapus'] === 0) {
            $processed = "done";
        }

        // ===== Fungsi bulan romawi =====
        function bulanRomawi($bulan) {
            $romawi = array(
                1 => "I", 2 => "II", 3 => "III", 4 => "IV",
                5 => "V", 6 => "VI", 7 => "VII", 8 => "VIII",
                9 => "IX", 10 => "X", 11 => "XI", 12 => "XII"
            );
            return $romawi[(int)$bulan];
        }

        // ===== Parsing tanggal =====
        $tgl   = date('Y-m-d', strtotime($data_ba['tanggal']));
        $bulan = bulanRomawi(date('n', strtotime($tgl)));
        $tahun = date('Y', strtotime($tgl));

        // ===== Nilai PT =====
        $nilai_pt = "MIS";

        // ===== Nomor BA final =====
        $nomor_ba_final = $data_ba['nomor_ba'] . "/BAMTA/" . $nilai_pt . "/" . $bulan . "/" . $tahun;

    } else {
        $nomor_ba_final = "Data tidak ditemukan";
        $processed = "done"; // aman: data sudah tidak ada
    }

    $stmt->close();
}
elseif ($jenis === 'st_asset') {

    // Ambil data dari database
    $stmt = $koneksi->prepare("
        SELECT nomor_ba, pt, tanggal, alasan_hapus
        FROM ba_serah_terima_asset 
        WHERE id = ?
        LIMIT 1
    ");
    $stmt->bind_param("i", $id_surat);
    $stmt->execute();
    $result = $stmt->get_result();
    $data_ba = $result->fetch_assoc();

    if ($data_ba) {

        // ----- Konversi bulan menjadi angka Romawi -----
        function bulanRomawi($bulan) {
            $romawi = array(
                1 => "I", 2 => "II", 3 => "III", 4 => "IV",
                5 => "V", 6 => "VI", 7 => "VII", 8 => "VIII",
                9 => "IX", 10 => "X", 11 => "XI", 12 => "XII"
            );
            return $romawi[(int)$bulan];
        }

        // Parsing tanggal
        $tgl = date('Y-m-d', strtotime($data_ba['tanggal']));
        $bulan = bulanRomawi(date('n', strtotime($tgl)));
        $tahun = date('Y', strtotime($tgl));

        // Cek nilai PT
        $nilai_pt = ($data_ba['pt'] === "PT.MSAL (HO)") ? "MSAL-MIS" : $data_ba['pt'];

        // Susun nomor BA final
        $nomor_ba_final = $data_ba['nomor_ba'] . "/" . $nilai_pt . "/" . $bulan . "/" . $tahun;

        $stmtCariPending = $koneksi->prepare("
            SELECT nomor_ba, pt, tanggal 
            FROM ba_serah_terima_asset 
            WHERE id = ? AND pending_hapus = 1
            LIMIT 1
        ");
        $stmtCariPending->bind_param("i", $id_surat);
        $stmtCariPending->execute();
        $resultCariPending = $stmtCariPending->get_result();
        $data_cari_pending = $resultCariPending->fetch_assoc();
        if (empty($data_cari_pending)) {
            $processed = "done";
        }
        $stmtCariPending->close();

    } else {
        $nomor_ba_final = "Data tidak ditemukan";
    }

    $stmt->close();
}
elseif ($jenis === 'pemutihan') {

    // Ambil data dari database
    $stmt = $koneksi->prepare("
        SELECT nomor_ba, pt, tanggal, alasan_hapus, pending_hapus
        FROM berita_acara_pemutihan
        WHERE id = ?
        LIMIT 1
    ");
    $stmt->bind_param("i", $id_surat);
    $stmt->execute();
    $result = $stmt->get_result();
    $data_ba = $result->fetch_assoc();

    if ($data_ba) {

        // Untuk BA Pemutihan, pakai nomor_ba yang tersimpan agar aman
        $nomor_ba_final = $data_ba['nomor_ba'] . "/BAP" . "/MIS" . "" . "";

        if ((int)$data_ba['pending_hapus'] === 0) {
            $processed = "done";
        }

    } else {
        $nomor_ba_final = "Data tidak ditemukan";
        $processed = "done";
    }

    $stmt->close();
}

$alasan_hapus_tampil = isset($data_ba['alasan_hapus']) ? $data_ba['alasan_hapus'] : '';
?>

<!DOCTYPE html>

<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Tanda Tangan Persetujuan</title>

<link rel="stylesheet" href="../assets/bootstrap-5.3.6-dist/css/bootstrap.min.css">
<link
    rel="stylesheet"
    href="../assets/icons/icons-main/font/bootstrap-icons.min.css"
/>
<link 
    rel="icon" type="image/png" 
    href="../assets/css/datatables.min.css"
/>

<link 
    rel="stylesheet" 
    href="../assets/css/datatables.min.css"
/>

<script src="../assets/js/jquery-3.7.1.min.js"></script>

<style>
body { background-color: rgb(2, 77, 207); }
.a4-container { width:210mm; height:297mm; background:white; box-shadow:0 0 10px rgba(0,0,0,0.2); margin:30px auto; padding:20px; }
.a4-container-landscape { width:297mm; height:210mm; background:white; box-shadow:0 0 10px rgba(0,0,0,0.2); margin:30px auto; padding:20px; }
iframe { width:100%; height:100%; border:none; }
.table-horizontal-container { overflow-x:auto; width:100%; }
.table-horizontal { border-collapse: collapse; width: max-content; }
.table-horizontal th, .table-horizontal td { border: 1px solid #dee2e6; padding: 8px; min-width: 150px; text-align: center; vertical-align: top; white-space: pre-wrap; }
.table-horizontal th { background: #f8f9fa; }
</style>

<style>
.popup-bg {
    background: rgba(0,0,0,0.5);
    top: 0;
    left: 0;
}
</style>

</head>
<body>

<div class="container py-4 d-flex flex-column align-items-center">
  <?php if ($processed === "done"): ?>
    <h2 class="mb-3 text-center text-white">Anda sudah melakukan proses persetujuan Hapus</h2>
  <?php else: ?>
    <h2 class="mb-3 text-center text-white">Halaman Approval Hapus Surat</h2>
  <?php endif; ?>

    <div class="d-flex flex-column align-items-start ps-3 gap-3 mb-1 bg-light border rounded p-1" style="width: <?= $boxWidth ?>; margin-top:80px;">
    <?php if ($jenis === 'kerusakan'): ?>
        <h5 class="m-0 p-0">Berita Acara <?= htmlspecialchars($jenis_ba2) ?> <?= htmlspecialchars($nomor_ba_final) ?></h5>
    <?php elseif ($jenis === 'pengembalian'): ?>
        <h5 class="m-0 p-0">Berita Acara <?= htmlspecialchars($jenis_ba2) ?> <?= htmlspecialchars($nomor_ba_final) ?></h5>
    <?php elseif ($jenis === 'mutasi'): ?>
        <h5 class="m-0 p-0">Berita Acara <?= htmlspecialchars($jenis_ba2) ?> <?= htmlspecialchars($nomor_ba_final) ?></h5>
    <?php elseif ($jenis === 'st_asset'): ?>
        <h5 class="m-0 p-0">Berita Acara <?= htmlspecialchars($jenis_ba2) ?> <?= htmlspecialchars($nomor_ba_final) ?></h5>
    <?php elseif ($jenis === 'pemutihan'): ?>
        <h5 class="m-0 p-0">Berita Acara <?= htmlspecialchars($jenis_ba2) ?> <?= htmlspecialchars($nomor_ba_final) ?></h5>
      <?php else: ?>
        <h4 class="m-0 p-0">Jenis BA Tidak Terdaftar</h4>
      <?php endif; ?>
    </div>

  <?php if ($processed === "done"): ?>
    <div class="d-flex justify-content-center mb-4 gap-2">
        <a class="btn btn-lg btn-primary mt-3" href="approval.php">Approval</a>
        <a class="btn btn-lg btn-danger mt-3" href="../logout.php">Keluar</a>
    </div>
  <?php else: ?>
    <div class="d-flex flex-column align-items-center gap-1 mb-4 bg-light border rounded p-3 mt-1" style="width: <?= $boxWidth ?>;">
      <h4 class="text-dark">Hapus Data BA <?= htmlspecialchars($jenis_ba2) ?></h4>

      <!-- <div class="table-horizontal-container">
        <table id="myTable" class="table-horizontal table table-striped">
          <thead>
            <tr id="headerRow">
              <th>Nama Field</th>
            </tr>
          </thead>
          <tbody>
            <tr id="lamaRow">
              <td>Data Lama</td>
            </tr>
            <tr id="baruRow">
              <td>Data Baru</td>
            </tr>
          </tbody>
        </table>
      </div> -->

      <div class="d-flex m-0 p-0 mt-3 w-100">
        <h6 class="fw-normal">Pemohon: <span class="fw-bold"><?= htmlspecialchars($nama_peminta) ?></span> </h6>
      </div>

      <div class="d-flex gap-1 m-0 mt-1 w-100">
        <h6 class="p-0 m-0 fw-normal">Alasan Perubahan: <span class="fw-bold"><?= htmlspecialchars($alasan_hapus_tampil) ?></span></h6>
      </div>

      <div class="d-flex gap-2 mt-2">
        <button id="btnSetujuApprovalEdit" class="btn btn-success fw-bold">Setujui</button>
        <button id="btnTolakApprovalEdit" class="btn btn-danger fw-bold">Tolak</button>
      </div>

    </div>
  <?php endif; ?>

    <?php if ($jenis === 'kerusakan'): ?>
    <div class="w-100 d-flex flex-column align-items-center mb-4">
      <div class="bg-white rounded p-3 shadow">
        <h4 class="p-0">Preview Surat</h4>
        <div class="a4-container mb-5 mt-0">
          <iframe src="surat_output_kerusakan.php?id=<?= urlencode($id_surat) ?>"></iframe>
        </div>
      </div>
    </div>
    <?php elseif ($jenis === 'pengembalian'): ?>
    <div class="w-100 d-flex flex-column align-items-center mb-4">
      <div class="bg-white rounded p-3 shadow">
        <h4 class="p-0">Preview Surat</h4>
        <div class="a4-container mb-5 mt-0">
          <iframe src="surat_output_pengembalian.php?id=<?= urlencode($id_surat) ?>"></iframe>
        </div>
      </div>
    </div>
    <?php elseif ($jenis === 'mutasi'): ?>
    <div class="w-100 d-flex flex-column align-items-center mb-4">
      <div class="bg-white rounded p-3 shadow">
        <h4 class="p-0">Preview Surat</h4>
        <div class="a4-container mb-5 mt-0">
          <iframe src="surat_output_mutasi.php?id=<?= urlencode($id_surat) ?>"></iframe>
        </div>
      </div>
    </div>
    <?php elseif ($jenis === 'st_asset'): ?>
    <div class="w-100 d-flex flex-column align-items-center mb-4">
      <div class="bg-white rounded p-3 shadow">
        <h4 class="p-0">Preview Surat</h4>
        <div class="a4-container mb-5 mt-0">
          <iframe src="surat_output_serah_terima_asset.php?id=<?= urlencode($id_surat) ?>"></iframe>
        </div>
      </div>
    </div>
    <?php elseif ($jenis === 'pemutihan'): ?>
    <div class="w-100 d-flex flex-column align-items-center mb-4">
      <div class="bg-white rounded p-3 shadow">
        <h4 class="p-0">Preview Surat</h4>
        <div class="a4-container-landscape mb-5 mt-0">
          <iframe src="surat_output_pemutihan.php?id=<?= urlencode($id_surat) ?>"></iframe>
        </div>
      </div>
    </div>
    <?php else: ?>
    <div class="alert alert-warning text-center d-none">
      Jenis BA <strong><?= htmlspecialchars($jenis) ?></strong> belum didukung untuk pratinjau.
    </div>
    <?php endif; ?>

  <!-- <div class="d-flex flex-column align-items-center gap-1 mb-4 bg-light border rounded p-3 mt-1" style="width: 210mm;">
      <h4 class="text-dark">History perubahan</h4>
        <div class="w-100 p-1" style="height:max-content; min-width:300px;">
            <?php 
            if ($jenis === 'kerusakan') :
            ?>
            <table id="popupDetailTable" class="table table-bordered table-striped" 
            style="font-size:16px; width: 100%;"
            >
                <thead>
                    <tr>
                        <th class="text-start">Tanggal Edit</th>
                        <th class="text-start">Status</th>
                        <th class="text-start">Alasan Edit</th>
                        <th class="text-start">Alasan Tolak</th>
                        <th class="text-start">Tanggal Surat</th>
                        <th class="text-start">Nomor Surat</th>
                        <th class="text-start">Jenis Perangkat</th>
                        <th class="text-start">Merek</th>
                        <th class="text-start">SN</th>
                        <th class="text-start">Tahun Perolehan</th>
                        <th class="text-start">Kategori Rusak</th>
                        <th class="text-start">Jenis Kerusakan</th>
                        <th class="text-start">Penyebab Kerusakan</th>
                        <th class="text-start">Rekomendasi MIS</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $queryHist = $koneksi->prepare("
                    SELECT created_at, pending_status, alasan_edit, alasan_tolak, tanggal, nomor_ba,
                        jenis_perangkat, merek, sn, tahun_perolehan, kategori_kerusakan_id,
                        deskripsi, penyebab_kerusakan, rekomendasi_mis
                    FROM history_n_temp_ba_kerusakan
                    WHERE id_ba = ?
                    AND NOT (pending_status = 1 AND status = 0)
                    ORDER BY created_at DESC
                ");
                $queryHist->bind_param("i", $id_surat);
                $queryHist->execute();
                $resHist = $queryHist->get_result();

                while ($row = $resHist->fetch_assoc()):

                $kategoriList = array();
                $resKat = $koneksi->query("SELECT id, nama FROM categories_broken");
                while ($k = $resKat->fetch_assoc()) {
                    $kategoriList[$k['id']] = $k['nama'];
                }

                if (isset($kategoriList[$row['kategori_kerusakan_id']])) {
                    $kategoriNama = $kategoriList[$row['kategori_kerusakan_id']];
                } else {
                    $kategoriNama = "Tidak Diketahui";
                }

                $statusText = "History";
                if ($row['pending_status'] == 1) {
                    $statusText = "Menunggu";
                } elseif ($row['pending_status'] == 2) {
                    $statusText = "Ditolak";
                }

                $color = "";
                if ($row['pending_status'] == 1) {
                    $color = "background-color:#fff3cd;";
                } elseif ($row['pending_status'] == 2) {
                    $color = "background-color:#f8d7da;";
                }
                ?>
                    <tr>
                        <td class="text-start" style="<?= $color ?>"><?= htmlspecialchars($row['created_at']) ?></td>
                        <td class="text-start" style="<?= $color ?>"><?= htmlspecialchars($statusText) ?></td>
                        <td class="text-start" style="<?= $color ?>"><?= htmlspecialchars($row['alasan_edit']) ?></td>
                        <td class="text-start" style="<?= $color ?>"><?= htmlspecialchars($row['alasan_tolak']) ?></td>
                        <td class="text-start" style="<?= $color ?>"><?= htmlspecialchars($row['tanggal']) ?></td>
                        <td class="text-start" style="<?= $color ?>"><?= htmlspecialchars($row['nomor_ba']) ?></td>
                        <td class="text-start" style="<?= $color ?>"><?= htmlspecialchars($row['jenis_perangkat']) ?></td>
                        <td class="text-start" style="<?= $color ?>"><?= htmlspecialchars($row['merek']) ?></td>
                        <td class="text-start" style="<?= $color ?>"><?= htmlspecialchars($row['sn']) ?></td>
                        <td class="text-start" style="<?= $color ?>"><?= htmlspecialchars($row['tahun_perolehan']) ?></td>
                        <td class="text-start" style="<?= $color ?>"><?= htmlspecialchars($kategoriNama) ?></td>
                        <td class="text-start" style="<?= $color ?>"><?= htmlspecialchars($row['deskripsi']) ?></td>
                        <td class="text-start" style="<?= $color ?>"><?= htmlspecialchars($row['penyebab_kerusakan']) ?></td>
                        <td class="text-start" style="<?= $color ?>"><?= htmlspecialchars($row['rekomendasi_mis']) ?></td>
                    </tr>
                <?php endwhile; ?>
                </tbody>

            </table>
            <?php 
            elseif ($jenis === 'mutasi') :
            ?>
            <table id="popupDetailTable" class="table table-bordered table-striped" 
            style="font-size:16px; width: 100%;"
            >
                <thead>
                    <tr>
                        <th class="text-start">Tanggal Edit</th>
                        <th class="text-start">Status</th>
                        <th class="text-start">Alasan Edit</th>
                        <th class="text-start">Alasan Tolak</th>
                        <th class="text-start">Tanggal Surat</th>
                        <th class="text-start">Nomor Surat</th>
                        <th class="text-start">Pengirim</th>
                        <th class="text-start">Pengirim 2</th>
                        <th class="text-start">HRGA Pengirim</th>
                        <th class="text-start">Penerima</th>
                        <th class="text-start">Penerima 2</th>
                        <th class="text-start">HRGA Penerima</th>
                        <th class="text-start">Diketahui</th>
                        <th class="text-start">Pemeriksa</th>
                        <th class="text-start">Pemeriksa 2</th>
                        <th class="text-start">Penyetujui</th>
                        <th class="text-start">Penyetujui 2</th>
                        <th class="text-start">Barang</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $queryHist = $koneksi->prepare("
                    SELECT created_at, pending_status, alasan_edit, alasan_tolak, tanggal, nomor_ba,
                            pengirim1, pengirim2, hrd_ga_pengirim, penerima1, penerima2, hrd_ga_penerima,
                            diketahui, pemeriksa1, pemeriksa2, penyetujui1, penyetujui2
                    FROM history_n_temp_ba_mutasi
                    WHERE id_ba = ?
                    AND NOT (pending_status = 1 AND status = 0)
                    ORDER BY created_at DESC
                ");
                $queryHist->bind_param("i", $id_surat);
                $queryHist->execute();
                $resHist = $queryHist->get_result();

                $listBarang = array();

                $qBarang = $koneksi->prepare("
                    SELECT merk
                    FROM history_n_temp_barang_mutasi
                    WHERE id_ba = ?
                    AND NOT (pending_status = 1 AND status = 0)
                    ORDER BY id ASC
                ");

                $qBarang->bind_param("i", $id_surat);
                $qBarang->execute();
                $resBarang = $qBarang->get_result();

                while ($b = $resBarang->fetch_assoc()) {
                    if (!empty(trim($b['merk']))) {
                        $listBarang[] = $b['merk'];
                    }
                }

                $qBarang->close();

                $barangText = implode(", ", $listBarang);

                while ($row = $resHist->fetch_assoc()):

                $statusText = "History";
                if ($row['pending_status'] == 1) {
                    $statusText = "Menunggu";
                } elseif ($row['pending_status'] == 2) {
                    $statusText = "Ditolak";
                }

                $color = "";
                if ($row['pending_status'] == 1) {
                    $color = "background-color:#fff3cd;";
                } elseif ($row['pending_status'] == 2) {
                    $color = "background-color:#f8d7da;";
                }
                ?>
                    <tr>
                        <td class="text-start" style="<?= $color ?>"><?= htmlspecialchars($row['created_at']) ?></td>
                        <td class="text-start" style="<?= $color ?>"><?= htmlspecialchars($statusText) ?></td>
                        <td class="text-start" style="<?= $color ?>"><?= htmlspecialchars($row['alasan_edit']) ?></td>
                        <td class="text-start" style="<?= $color ?>"><?= htmlspecialchars($row['alasan_tolak']) ?></td>
                        <td class="text-start" style="<?= $color ?>"><?= htmlspecialchars($row['tanggal']) ?></td>
                        <td class="text-start" style="<?= $color ?>"><?= htmlspecialchars($row['nomor_ba']) ?></td>
                        <td class="text-start" style="<?= $color ?>"><?= htmlspecialchars($row['pengirim1']) ?></td>
                        <td class="text-start" style="<?= $color ?>"><?= htmlspecialchars($row['pengirim2']) ?></td>
                        <td class="text-start" style="<?= $color ?>"><?= htmlspecialchars($row['hrd_ga_pengirim']) ?></td>
                        <td class="text-start" style="<?= $color ?>"><?= htmlspecialchars($row['penerima1']) ?></td>
                        <td class="text-start" style="<?= $color ?>"><?= htmlspecialchars($row['penerima2']) ?></td>
                        <td class="text-start" style="<?= $color ?>"><?= htmlspecialchars($row['hrd_ga_penerima']) ?></td>
                        <td class="text-start" style="<?= $color ?>"><?= htmlspecialchars($row['diketahui']) ?></td>
                        <td class="text-start" style="<?= $color ?>"><?= htmlspecialchars($row['pemeriksa1']) ?></td>
                        <td class="text-start" style="<?= $color ?>"><?= htmlspecialchars($row['pemeriksa2']) ?></td>
                        <td class="text-start" style="<?= $color ?>"><?= htmlspecialchars($row['penyetujui1']) ?></td>
                        <td class="text-start" style="<?= $color ?>"><?= htmlspecialchars($row['penyetujui2']) ?></td>
                        <td class="text-start" style="<?= $color ?>"><?= htmlspecialchars($barangText) ?></td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
            <?php
            else :
            ?>
            <p>Berita Acara Tidak dikenali</p>
            <?php 
            endif;
            ?>
        </div>
  </div> -->

    <?php if ($processed !== "done"): ?>
    <div class="d-flex justify-content-center mb-4 gap-2">
        <a class="btn btn-lg btn-primary mt-3" href="approval.php">Approval</a>
        <a class="btn btn-lg btn-danger mt-3" href="../logout.php">Keluar</a>
    </div>
    <?php endif; ?>

<div id="info-id-ba" style="display:none;"><?= htmlspecialchars($id_surat) ?></div>
<div id="info-jenis-ba" style="display:none;"><?= htmlspecialchars($jenis) ?></div>
<div id="info-approver" style="display:none;"><?= isset($_SESSION['nama']) ? htmlspecialchars($_SESSION['nama']) : '' ?></div>

</div>

<script src="../assets/js/jquery-3.7.1.min.js"></script>
<script src="../assets/js/datatables.min.js"></script>

<script>
$(document).ready(function () {
    if ($('#popupDetailTable').length) {
        $('#popupDetailTable').DataTable({
            paging: false,
            searching: false,
            info: false,
            ordering: false,
            scrollY: "230px",
            scrollCollapse: true,
            autoWidth: true,
            language: {
                url: "../assets/json/id.json"
            }
        });
    }
});
</script>

<script>
const idBA = "<?= htmlspecialchars($id_surat, ENT_QUOTES) ?>";
const jenisBA = "<?= htmlspecialchars($jenis, ENT_QUOTES) ?>";
const approver = "<?= isset($_SESSION['nama']) ? htmlspecialchars($_SESSION['nama'], ENT_QUOTES) : '' ?>";

function tambahKolom(namaField, lamaVal, baruVal) {
    const headerRow = document.getElementById("headerRow");
    const lamaRow = document.getElementById("lamaRow");
    const baruRow = document.getElementById("baruRow");

    if (!headerRow || !lamaRow || !baruRow) {
        return;
    }

    const th = document.createElement("th");
    th.textContent = namaField;
    headerRow.appendChild(th);

    const tdLama = document.createElement("td");
    tdLama.textContent = lamaVal || "-";
    lamaRow.appendChild(tdLama);

    const tdBaru = document.createElement("td");
    tdBaru.textContent = baruVal || "-";
    baruRow.appendChild(tdBaru);
}
</script>

<script>
var btnSetujuApprovalEdit = document.getElementById("btnSetujuApprovalEdit");
if (btnSetujuApprovalEdit) {
    btnSetujuApprovalEdit.addEventListener("click", function () {
        if (!confirm("Yakin ingin menghapus data ini?")) {
            return;
        }

        const idBA    = "<?= htmlspecialchars($id_surat, ENT_QUOTES) ?>";
        const jenisBA = "<?= htmlspecialchars($jenis, ENT_QUOTES) ?>";
        const approver = "<?= isset($_SESSION['nama']) ? htmlspecialchars($_SESSION['nama'], ENT_QUOTES) : '' ?>";

        fetch("proses_approval_delete.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body:
                "id_ba=" + encodeURIComponent(idBA) +
                "&jenisBA=" + encodeURIComponent(jenisBA) +
                "&approver=" + encodeURIComponent(approver) +
                "&aksi=setuju"
        })
        .then(function(res) {
            return res.json();
        })
        .then(function(res) {
            if (res.success === true) {
                window.location.reload();
            } else {
                alert(res.message ? res.message : "Gagal memproses persetujuan.");
            }
        })
        .catch(function() {
            alert("Gagal memproses persetujuan.");
        });
    });
}
</script>

<script>
var btnTolakApprovalEdit = document.getElementById("btnTolakApprovalEdit");
if (btnTolakApprovalEdit) {
    btnTolakApprovalEdit.addEventListener("click", function () {

        if (!confirm("Apakah Anda yakin ingin MENOLAK pengajuan ini?")) {
            return;
        }

        const idBA     = "<?= htmlspecialchars($id_surat, ENT_QUOTES) ?>";
        const jenisBA  = "<?= htmlspecialchars($jenis, ENT_QUOTES) ?>";
        const approver = "<?= isset($_SESSION['nama']) ? htmlspecialchars($_SESSION['nama'], ENT_QUOTES) : '' ?>";

        fetch("proses_approval_delete.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body:
                "id_ba=" + encodeURIComponent(idBA) +
                "&jenisBA=" + encodeURIComponent(jenisBA) +
                "&approver=" + encodeURIComponent(approver) +
                "&aksi=tolak"
        })
        .then(function(res) {
            return res.json();
        })
        .then(function(res) {
            if (res.success === true) {
                window.location.reload();
            } else {
                alert(res.message ? res.message : "Gagal memproses penolakan.");
            }
        })
        .catch(function() {
            alert("Gagal memproses penolakan.");
        });
    });
}
</script>

</body>
</html>