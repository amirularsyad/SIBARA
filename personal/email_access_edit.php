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
} elseif ($jenis === "st_asset") {
    $jenis_ba2 = "Serah Terima Asset Inventaris";
} elseif ($jenis === "pengembalian") {
    $jenis_ba2 = "Pengembalian Inventaris";
} elseif ($jenis === 'pemutihan') {
    $jenis_ba2 = "Pemutihan Aset";
} else {
    $jenis_ba2 = $jenis;
}

$nomor_ba_final = "";
$processed = "";

if ($jenis === 'kerusakan') {

    // Ambil data dari database
    $stmt = $koneksi->prepare("
        SELECT nomor_ba, pt, tanggal 
        FROM history_n_temp_ba_kerusakan 
        WHERE id_ba = ?
        LIMIT 1
    ");
    $stmt->bind_param("i", $id_surat);
    $stmt->execute();
    $result = $stmt->get_result();
    $data_ba = $result->fetch_assoc();

    if ($data_ba) {

        // ----- Konversi bulan menjadi angka Romawi -----
        function bulanRomawi($bulan)
        {
            $romawi = [
                1 => "I",
                2 => "II",
                3 => "III",
                4 => "IV",
                5 => "V",
                6 => "VI",
                7 => "VII",
                8 => "VIII",
                9 => "IX",
                10 => "X",
                11 => "XI",
                12 => "XII"
            ];
            return $romawi[(int)$bulan];
        }

        // Parsing tanggal
        $tgl = date('Y-m-d', strtotime($data_ba['tanggal']));
        $bulan = bulanRomawi(date('n', strtotime($tgl)));
        $tahun = date('Y', strtotime($tgl));

        // Cek nilai PT
        $nilai_pt = ($data_ba['pt'] === "PT.MSAL (HO)") ? "MIS-HO" : "MIS-SITE";

        // Susun nomor BA final
        $nomor_ba_final = $data_ba['nomor_ba'] . "/" . $nilai_pt . "/" . $bulan . "/" . $tahun;

        $stmtCariPending = $koneksi->prepare("
        SELECT nomor_ba, pt, tanggal 
        FROM history_n_temp_ba_kerusakan 
        WHERE id_ba = ? AND status = 1 AND pending_status = 1
        LIMIT 1
    ");
        $stmtCariPending->bind_param("i", $id_surat);
        $stmtCariPending->execute();
        $resultCariPending = $stmtCariPending->get_result();
        $data_cari_pending = $resultCariPending->fetch_assoc();
        if (empty($data_cari_pending)) {
            $processed = "done";
        }
    } else {
        $nomor_ba_final = "Data tidak ditemukan";
    }
} elseif ($jenis === 'pengembalian') {

    // Ambil data dari database
    $stmt = $koneksi->prepare("
        SELECT nomor_ba, pt, tanggal
        FROM history_n_temp_ba_pengembalian_v2
        WHERE id_ba = ?
        LIMIT 1
    ");
    $stmt->bind_param("i", $id_surat);
    $stmt->execute();
    $result = $stmt->get_result();
    $data_ba = $result->fetch_assoc();

    if ($data_ba) {

        // ----- Konversi bulan menjadi angka Romawi -----
        function bulanRomawi($bulan)
        {
            $romawi = array(
                1 => "I",
                2 => "II",
                3 => "III",
                4 => "IV",
                5 => "V",
                6 => "VI",
                7 => "VII",
                8 => "VIII",
                9 => "IX",
                10 => "X",
                11 => "XI",
                12 => "XII"
            );
            return $romawi[(int)$bulan];
        }

        // Parsing tanggal
        $tgl = date('Y-m-d', strtotime($data_ba['tanggal']));
        $bulan = bulanRomawi(date('n', strtotime($tgl)));
        $tahun = date('Y', strtotime($tgl));

        // Aman: tampilkan nomor final tanpa asumsi format baru yang berisiko salah
        $nomor_ba_final = $data_ba['nomor_ba'];

        $stmtCariPending = $koneksi->prepare("
            SELECT nomor_ba, pt, tanggal
            FROM history_n_temp_ba_pengembalian_v2
            WHERE id_ba = ? AND status = 1 AND pending_status = 1
            LIMIT 1
        ");
        $stmtCariPending->bind_param("i", $id_surat);
        $stmtCariPending->execute();
        $resultCariPending = $stmtCariPending->get_result();
        $data_cari_pending = $resultCariPending->fetch_assoc();

        if (empty($data_cari_pending)) {
            $processed = "done";
        }
    } else {
        $nomor_ba_final = "Data tidak ditemukan";
    }
} elseif ($jenis === 'mutasi') {

    // Ambil data dari database
    $stmt = $koneksi->prepare("
        SELECT nomor_ba, pt_asal, tanggal 
        FROM history_n_temp_ba_mutasi 
        WHERE id_ba = ?
        LIMIT 1
    ");
    $stmt->bind_param("i", $id_surat);
    $stmt->execute();
    $result = $stmt->get_result();
    $data_ba = $result->fetch_assoc();

    if ($data_ba) {

        // ----- Konversi bulan menjadi angka Romawi -----
        function bulanRomawi($bulan)
        {
            $romawi = [
                1 => "I",
                2 => "II",
                3 => "III",
                4 => "IV",
                5 => "V",
                6 => "VI",
                7 => "VII",
                8 => "VIII",
                9 => "IX",
                10 => "X",
                11 => "XI",
                12 => "XII"
            ];
            return $romawi[(int)$bulan];
        }

        // Parsing tanggal
        $tgl = date('Y-m-d', strtotime($data_ba['tanggal']));
        $bulan = bulanRomawi(date('n', strtotime($tgl)));
        $tahun = date('Y', strtotime($tgl));

        // Cek nilai PT
        // $nilai_pt = ($data_ba['pt_asal'] === "PT.MSAL (HO)") ? "MIS" : $data_ba['pt_asal'];
        $nilai_pt = "MIS";

        // Susun nomor BA final
        $nomor_ba_final = $data_ba['nomor_ba'] . "/BAMTA/" . $nilai_pt . "/" . $bulan . "/" . $tahun;

        $stmtCariPending = $koneksi->prepare("
        SELECT nomor_ba, pt_asal, tanggal 
        FROM history_n_temp_ba_mutasi 
        WHERE id_ba = ? AND status = 1 AND pending_status = 1
        LIMIT 1
    ");
        $stmtCariPending->bind_param("i", $id_surat);
        $stmtCariPending->execute();
        $resultCariPending = $stmtCariPending->get_result();
        $data_cari_pending = $resultCariPending->fetch_assoc();
        if (empty($data_cari_pending)) {
            $processed = "done";
        }
    } else {
        $nomor_ba_final = "Data tidak ditemukan";
    }
} elseif ($jenis === 'st_asset') {

    // Ambil data dari database
    $stmt = $koneksi->prepare("
        SELECT nomor_ba, pt, tanggal 
        FROM history_n_temp_ba_serah_terima_asset 
        WHERE id_ba = ?
        LIMIT 1
    ");
    $stmt->bind_param("i", $id_surat);
    $stmt->execute();
    $result = $stmt->get_result();
    $data_ba = $result->fetch_assoc();

    if ($data_ba) {

        // ----- Konversi bulan menjadi angka Romawi -----
        function bulanRomawi($bulan)
        {
            $romawi = [
                1 => "I",
                2 => "II",
                3 => "III",
                4 => "IV",
                5 => "V",
                6 => "VI",
                7 => "VII",
                8 => "VIII",
                9 => "IX",
                10 => "X",
                11 => "XI",
                12 => "XII"
            ];
            return $romawi[(int)$bulan];
        }

        // Parsing tanggal
        $tgl = date('Y-m-d', strtotime($data_ba['tanggal']));
        $bulan = bulanRomawi(date('n', strtotime($tgl)));
        $tahun = date('Y', strtotime($tgl));

        // Cek nilai PT
        $nilai_pt = ($data_ba['pt'] === "PT.MSAL (HO)") ? "MSAL" : $data_ba['pt'];

        // Susun nomor BA final
        $nomor_ba_final = $data_ba['nomor_ba'] . "/" .  $nilai_pt . "-MIS/" . $bulan . "/" . $tahun;

        $stmtCariPending = $koneksi->prepare("
        SELECT nomor_ba, pt, tanggal 
        FROM history_n_temp_ba_serah_terima_asset 
        WHERE id_ba = ? AND status = 1 AND pending_status = 1
        LIMIT 1
    ");
        $stmtCariPending->bind_param("i", $id_surat);
        $stmtCariPending->execute();
        $resultCariPending = $stmtCariPending->get_result();
        $data_cari_pending = $resultCariPending->fetch_assoc();
        if (empty($data_cari_pending)) {
            $processed = "done";
        }
    } else {
        $nomor_ba_final = "Data tidak ditemukan";
    }
} elseif ($jenis === 'pemutihan') {

    $stmt = $koneksi->prepare("
        SELECT nomor_ba, pt, tanggal
        FROM history_n_temp_ba_pemutihan
        WHERE id_ba = ?
        LIMIT 1
    ");
    $stmt->bind_param("i", $id_surat);
    $stmt->execute();
    $result = $stmt->get_result();
    $data_ba = $result->fetch_assoc();

    if ($data_ba) {

        $romawi = array(
            1 => "I",
            2 => "II",
            3 => "III",
            4 => "IV",
            5 => "V",
            6 => "VI",
            7 => "VII",
            8 => "VIII",
            9 => "IX",
            10 => "X",
            11 => "XI",
            12 => "XII"
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

        $tgl = date('Y-m-d', strtotime($data_ba['tanggal']));
        $bulan_num = (int)date('n', strtotime($tgl));
        $bulan = isset($romawi[$bulan_num]) ? $romawi[$bulan_num] : '';
        $tahun = date('Y', strtotime($tgl));

        $pt = isset($data_ba['pt']) ? $data_ba['pt'] : '';
        $nilai_pt = isset($kode_pt[$pt]) ? $kode_pt[$pt] : '';

        $nomor_ba_final = $data_ba['nomor_ba'] . "/BAP/" . $nilai_pt . "/" . $bulan . "/" . $tahun;

        $stmtCariPending = $koneksi->prepare("
            SELECT nomor_ba, pt, tanggal
            FROM history_n_temp_ba_pemutihan
            WHERE id_ba = ? AND status = 1 AND pending_status = 1
            LIMIT 1
        ");
        $stmtCariPending->bind_param("i", $id_surat);
        $stmtCariPending->execute();
        $resultCariPending = $stmtCariPending->get_result();
        $data_cari_pending = $resultCariPending->fetch_assoc();

        if (empty($data_cari_pending)) {
            $processed = "done";
        }
    } else {
        $nomor_ba_final = "Data tidak ditemukan";
    }
}
?>

<!DOCTYPE html>

<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tanda Tangan Persetujuan <?php echo $jenis_ba2 ?> </title>

    <link rel="stylesheet" href="../assets/bootstrap-5.3.6-dist/css/bootstrap.min.css">
    <link
        rel="stylesheet"
        href="../assets/icons/icons-main/font/bootstrap-icons.min.css" />
    <link
        rel="icon" type="image/png"
        href="../assets/css/datatables.min.css" />

    <link
        rel="stylesheet"
        href="../assets/css/datatables.min.css" />

    <script src="../assets/js/jquery-3.7.1.min.js"></script>

    <style>
        body {
            background-color: rgb(2, 77, 207);
        }

        .a4-container {
            width: 210mm;
            height: 297mm;
            background: white;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
            margin: 30px auto;
            padding: 20px;
        }

        iframe {
            width: 100%;
            height: 100%;
            border: none;
        }

        .table-horizontal-container {
            overflow-x: auto;
            width: 100%;
        }

        .table-horizontal {
            border-collapse: collapse;
            width: max-content;
        }

        .table-horizontal th,
        .table-horizontal td {
            border: 1px solid #dee2e6;
            padding: 8px;
            min-width: 150px;
            text-align: center;
            vertical-align: top;
            white-space: pre-wrap;
        }

        .table-horizontal th {
            background: #f8f9fa;
        }
    </style>
    <style>
        .popup-bg {
            background: rgba(0, 0, 0, 0.5);
            top: 0;
            left: 0;
        }
    </style>

</head>

<body>

    <div class="container py-4 d-flex flex-column align-items-center">
        <?php if ($processed === "done"): ?>
            <h2 class="mb-3 text-center text-white">Anda sudah melakukan proses persetujuan Edit</h2>
        <?php else: ?>
            <h2 class="mb-3 text-center text-white">Halaman Approval Perubahan Surat</h2>
        <?php endif; ?>

        <div class="d-flex flex-column align-items-start ps-3 gap-3 mb-1 bg-light border rounded p-1" style="width: 210mm; margin-top:80px;">
            <?php if ($jenis === 'kerusakan'): ?>
                <h5 class="m-0 p-0">Berita Acara <?= htmlspecialchars($jenis_ba2) ?> <?= $nomor_ba_final ?></h5>
            <?php elseif ($jenis === 'pengembalian'): ?>
                <h5 class="m-0 p-0">Berita Acara <?= htmlspecialchars($jenis_ba2) ?> <?= $nomor_ba_final ?></h5>
            <?php elseif ($jenis === 'mutasi'): ?>
                <h5 class="m-0 p-0">Berita Acara <?= htmlspecialchars($jenis_ba2) ?> <?= $nomor_ba_final ?></h5>
            <?php elseif ($jenis === 'st_asset'): ?>
                <h5 class="m-0 p-0">Berita Acara <?= htmlspecialchars($jenis_ba2) ?> <?= $nomor_ba_final ?></h5>
            <?php elseif ($jenis === 'pemutihan'): ?>
                <h5 class="m-0 p-0">Berita Acara <?= htmlspecialchars($jenis_ba2) ?> <?= $nomor_ba_final ?></h5>
            <?php else: ?>
                <h4 class="m-0 p-0">Jenis BA Tidak Terdaftar</h4>
            <?php endif; ?>
        </div>
        <?php if ($processed === "done"): ?>

        <?php else: ?>
            <div class="d-flex flex-column align-items-center gap-1 mb-4 bg-light border rounded p-3 mt-1" style="width: 210mm;">
                <h4 class="text-dark">Perubahan Data BA <?= htmlspecialchars($jenis_ba2) ?></h4>

                <div class="table-horizontal-container">
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
                </div>

                <div class="d-flex m-0 p-0 mt-3 w-100">
                    <h6 class="fw-normal">Pemohon: <span class="fw-bold"><?= htmlspecialchars($nama_peminta) ?></span> </h6>
                </div>

                <div class="d-flex gap-1 m-0 mt-1 w-100">
                    <h6 class="p-0 m-0 fw-normal">Alasan Perubahan: <span class="fw-bold" id="alasanPerubahan"></span></h6>
                </div>

                <div class="d-flex gap-2 mt-2">
                    <button id="btnSetujuApprovalEdit" class="btn btn-success fw-bold">Setujui</button>
                    <button class="btn btn-danger fw-bold btnTolakApprovalEdit">Tolak</button>
                </div>

            </div>
        <?php endif; ?>
        <?php if ($jenis === 'kerusakan'): ?>
            <div class="w-100 d-flex flex-column align-items-center mb-4 d-none">
                <div class="bg-white rounded p-3 shadow">
                    <h4 class="p-0">Preview Perubahan di Surat</h4>
                    <div class="a4-container mb-5 mt-0">
                        <iframe src="surat_output_kerusakan.php?id=<?= urlencode($id_surat) ?>"></iframe>
                    </div>
                </div>
            </div>
        <?php elseif ($jenis === 'pengembalian'): ?>
            <div class="w-100 d-flex flex-column align-items-center mb-4 d-none">
                <div class="bg-white rounded p-3 shadow">
                    <h4 class="p-0">Preview Perubahan di Surat</h4>
                    <div class="a4-container mb-5 mt-0">
                        <iframe src="surat_output_pengembalian.php?id=<?= urlencode($id_surat) ?>"></iframe>
                    </div>
                </div>
            </div>
        <?php elseif ($jenis === 'mutasi'): ?>
            <div class="w-100 d-flex flex-column align-items-center mb-4 d-none">
                <div class="bg-white rounded p-3 shadow">
                    <h4 class="p-0">Preview Perubahan di Surat</h4>
                    <div class="a4-container mb-5 mt-0">
                        <iframe src="surat_output_mutasi.php?id=<?= urlencode($id_surat) ?>"></iframe>
                    </div>
                </div>
            </div>
        <?php elseif ($jenis === 'pemutihan'): ?>
            <div class="w-100 d-flex flex-column align-items-center mb-4 d-none">
                <div class="bg-white rounded p-3 shadow">
                    <h4 class="p-0">Preview Perubahan di Surat</h4>
                    <div class="a4-container mb-5 mt-0">
                        <iframe src="surat_output_pemutihan.php?id=<?= urlencode($id_surat) ?>"></iframe>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-warning text-center d-none">
                Jenis BA <strong><?= htmlspecialchars($jenis) ?></strong> belum didukung untuk pratinjau.
            </div>
        <?php endif; ?>


        <div class="d-flex flex-column align-items-center gap-1 mb-4 bg-light border rounded p-3 mt-1" style="width: 210mm;">
            <h4 class="text-dark">History perubahan</h4>
            <div class="w-100 p-1" style="height:max-content; min-width:300px;">
                <?php
                if ($jenis === 'kerusakan') :
                ?>
                    <table id="popupDetailTable" class="table table-bordered table-striped"
                        style="font-size:16px; width: 100%;">
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

                                // Ambil semua kategori dan simpan dalam array [id => nama]
                                $kategoriList = [];
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
                                    $color = "background-color:#fff3cd;"; // kuning
                                } elseif ($row['pending_status'] == 2) {
                                    $color = "background-color:#f8d7da;"; // merah
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
                elseif ($jenis === 'pengembalian') :
                ?>
                    <table id="popupDetailTable" class="table table-bordered table-striped"
                        style="font-size:16px; width: 100%;">
                        <thead>
                            <tr>
                                <th class="text-start">Tanggal Edit</th>
                                <th class="text-start">Status</th>
                                <th class="text-start">Alasan Edit</th>
                                <th class="text-start">Alasan Tolak</th>
                                <th class="text-start">Tanggal Surat</th>
                                <th class="text-start">Nomor Surat</th>
                                <th class="text-start">Pengembali</th>
                                <th class="text-start">Jabatan Pengembali</th>
                                <th class="text-start">Penerima</th>
                                <th class="text-start">Jabatan Penerima</th>
                                <th class="text-start">Diketahui</th>
                                <th class="text-start">Jabatan Diketahui</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $queryHist = $koneksi->prepare("
                    SELECT created_at, pending_status, alasan_edit, alasan_tolak, tanggal, nomor_ba,
                           pengembali, jabatan_pengembali, penerima, jabatan_penerima,
                           diketahui, jabatan_diketahui
                    FROM history_n_temp_ba_pengembalian_v2
                    WHERE id_ba = ?
                      AND NOT (pending_status = 1 AND status = 0)
                    ORDER BY created_at DESC
                ");
                            $queryHist->bind_param("i", $id_surat);
                            $queryHist->execute();
                            $resHist = $queryHist->get_result();

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
                                    <td class="text-start" style="<?= $color ?>"><?= htmlspecialchars($row['pengembali']) ?></td>
                                    <td class="text-start" style="<?= $color ?>"><?= htmlspecialchars($row['jabatan_pengembali']) ?></td>
                                    <td class="text-start" style="<?= $color ?>"><?= htmlspecialchars($row['penerima']) ?></td>
                                    <td class="text-start" style="<?= $color ?>"><?= htmlspecialchars($row['jabatan_penerima']) ?></td>
                                    <td class="text-start" style="<?= $color ?>"><?= htmlspecialchars($row['diketahui']) ?></td>
                                    <td class="text-start" style="<?= $color ?>"><?= htmlspecialchars($row['jabatan_diketahui']) ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php
                elseif ($jenis === 'mutasi') :
                ?>
                    <table id="popupDetailTable" class="table table-bordered table-striped"
                        style="font-size:16px; width: 100%;">
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
                                <!-- <th class="text-start">Barang</th> -->
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

                            // Ambil data BARANG mutasi (khusus history)
                            $listBarang = [];

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

                            // Gabungkan untuk ditampilkan
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
                                    $color = "background-color:#fff3cd;"; // kuning
                                } elseif ($row['pending_status'] == 2) {
                                    $color = "background-color:#f8d7da;"; // merah
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
                                    <!-- <td class="text-start" style="<?= $color ?>"><?= htmlspecialchars($barangText) ?></td> -->
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php
                elseif ($jenis === 'st_asset') :
                ?>
                    <table id="popupDetailTable" class="table table-bordered table-striped"
                        style="font-size:16px; width: 100%;">
                        <thead>
                            <tr>
                                <th class="text-start">Tanggal Edit</th>
                                <th class="text-start">Status</th>
                                <th class="text-start">Alasan Edit</th>
                                <th class="text-start">Alasan Tolak</th>
                                <th class="text-start">Tanggal Surat</th>
                                <th class="text-start">Nomor Surat</th>
                                <th class="text-start">Peminjam</th>
                                <th class="text-start">Jenis Perangkat</th>
                                <th class="text-start">Merek</th>
                                <th class="text-start">Nomor PO</th>
                                <th class="text-start">SN</th>
                                <th class="text-start">Tanggal Pembelian</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $queryHist = $koneksi->prepare("
                    SELECT created_at, pending_status, alasan_edit, alasan_tolak, tanggal, nomor_ba,
                            peminjam,
                            categories, merek, no_po, sn, tgl_pembelian
                    FROM history_n_temp_ba_serah_terima_asset
                    WHERE id_ba = ?
                    AND NOT (pending_status = 1 AND status = 0)
                    ORDER BY created_at DESC
                ");
                            $queryHist->bind_param("i", $id_surat);
                            $queryHist->execute();
                            $resHist = $queryHist->get_result();

                            while ($row = $resHist->fetch_assoc()):

                                $statusText = "History";
                                if ($row['pending_status'] == 1) {
                                    $statusText = "Menunggu";
                                } elseif ($row['pending_status'] == 2) {
                                    $statusText = "Ditolak";
                                }

                                $color = "";
                                if ($row['pending_status'] == 1) {
                                    $color = "background-color:#fff3cd;"; // kuning
                                } elseif ($row['pending_status'] == 2) {
                                    $color = "background-color:#f8d7da;"; // merah
                                }
                            ?>
                                <tr>
                                    <td class="text-start" style="<?= $color ?>"><?= htmlspecialchars($row['created_at']) ?></td>
                                    <td class="text-start" style="<?= $color ?>"><?= htmlspecialchars($statusText) ?></td>
                                    <td class="text-start" style="<?= $color ?>"><?= htmlspecialchars($row['alasan_edit']) ?></td>
                                    <td class="text-start" style="<?= $color ?>"><?= htmlspecialchars($row['alasan_tolak']) ?></td>
                                    <td class="text-start" style="<?= $color ?>"><?= htmlspecialchars($row['tanggal']) ?></td>
                                    <td class="text-start" style="<?= $color ?>"><?= htmlspecialchars($row['nomor_ba']) ?></td>
                                    <td class="text-start" style="<?= $color ?>"><?= htmlspecialchars($row['peminjam']) ?></td>
                                    <td class="text-start" style="<?= $color ?>"><?= htmlspecialchars($row['categories']) ?></td>
                                    <td class="text-start" style="<?= $color ?>"><?= htmlspecialchars($row['merek']) ?></td>
                                    <td class="text-start" style="<?= $color ?>"><?= htmlspecialchars($row['no_po']) ?></td>
                                    <td class="text-start" style="<?= $color ?>"><?= htmlspecialchars($row['sn']) ?></td>
                                    <td class="text-start" style="<?= $color ?>"><?= htmlspecialchars($row['tgl_pembelian']) ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php
                elseif ($jenis === 'pemutihan') :
                ?>
                    <table id="popupDetailTable" class="table table-bordered table-striped"
                        style="font-size:16px; width: 100%;">
                        <thead>
                            <tr>
                                <th class="text-start">Tanggal Edit</th>
                                <th class="text-start">Status</th>
                                <th class="text-start">Alasan Edit</th>
                                <th class="text-start">Alasan Tolak</th>
                                <th class="text-start">Tanggal Surat</th>
                                <th class="text-start">Nomor Surat</th>
                                <th class="text-start">PT</th>
                                <th class="text-start">Pembuat</th>
                                <th class="text-start">Pemeriksa</th>
                                <th class="text-start">Diketahui 1</th>
                                <th class="text-start">Diketahui 2</th>
                                <th class="text-start">Diketahui 3</th>
                                <th class="text-start">Dibukukan</th>
                                <th class="text-start">Disetujui 1</th>
                                <th class="text-start">Disetujui 2</th>
                                <th class="text-start">Disetujui 3</th>
                                <th class="text-start">Pembuat Site</th>
                                <th class="text-start">Pemeriksa Site</th>
                                <th class="text-start">Diketahui Site 1</th>
                                <th class="text-start">Disetujui Site 1</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $queryHist = $koneksi->prepare("
                                SELECT created_at, pending_status, alasan_edit, alasan_tolak, tanggal, nomor_ba,
                                    pt, pembuat, pemeriksa, diketahui1, diketahui2, diketahui3,
                                    dibukukan, disetujui1, disetujui2, disetujui3,
                                    pembuat_site, pemeriksa_site, diketahui1_site, disetujui1_site
                                FROM history_n_temp_ba_pemutihan
                                WHERE id_ba = ?
                                AND NOT (pending_status = 1 AND status = 0)
                                ORDER BY created_at DESC
                            ");
                            $queryHist->bind_param("i", $id_surat);
                            $queryHist->execute();
                            $resHist = $queryHist->get_result();

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
                                    <td class="text-start" style="<?= $color ?>"><?= htmlspecialchars($row['pt']) ?></td>
                                    <td class="text-start" style="<?= $color ?>"><?= htmlspecialchars($row['pembuat']) ?></td>
                                    <td class="text-start" style="<?= $color ?>"><?= htmlspecialchars($row['pemeriksa']) ?></td>
                                    <td class="text-start" style="<?= $color ?>"><?= htmlspecialchars($row['diketahui1']) ?></td>
                                    <td class="text-start" style="<?= $color ?>"><?= htmlspecialchars($row['diketahui2']) ?></td>
                                    <td class="text-start" style="<?= $color ?>"><?= htmlspecialchars($row['diketahui3']) ?></td>
                                    <td class="text-start" style="<?= $color ?>"><?= htmlspecialchars($row['dibukukan']) ?></td>
                                    <td class="text-start" style="<?= $color ?>"><?= htmlspecialchars($row['disetujui1']) ?></td>
                                    <td class="text-start" style="<?= $color ?>"><?= htmlspecialchars($row['disetujui2']) ?></td>
                                    <td class="text-start" style="<?= $color ?>"><?= htmlspecialchars($row['disetujui3']) ?></td>
                                    <td class="text-start" style="<?= $color ?>"><?= htmlspecialchars($row['pembuat_site']) ?></td>
                                    <td class="text-start" style="<?= $color ?>"><?= htmlspecialchars($row['pemeriksa_site']) ?></td>
                                    <td class="text-start" style="<?= $color ?>"><?= htmlspecialchars($row['diketahui1_site']) ?></td>
                                    <td class="text-start" style="<?= $color ?>"><?= htmlspecialchars($row['disetujui1_site']) ?></td>
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
        </div>

        <div class="d-flex justify-content-center mb-4 gap-2">
            <a class="btn btn-lg btn-primary mt-3" href="approval.php">Approval</a>
            <a class="btn btn-lg btn-danger mt-3" href="../logout.php">Keluar</a>
        </div>

        <!-- Popup Background -->
        <div id="popupBGTolakPendingEdit" class="popup-bg position-fixed w-100 h-100"
            style="z-index:8 !important; display:none;"></div>

        <!-- Popup Box -->
        <div id="popupBoxTolakPendingEdit"
            class="popup-box custom-popup position-absolute bg-white rounded-1 flex-column justify-content-start align-items-center p-2"
            style="height: max-content; align-self: center; z-index:9; width:210mm; min-width:400px; top:341px; display:none;">

            <div class="w-100 d-flex justify-content-between mb-2 p-0">
                <h4 class="m-0 p-0">Konfirmasi Penolakan</h4>
                <a id="closePopupTolakPendingEdit" class="btn btn-danger btn-sm" href="#"><i class="bi bi-x-lg"></i>
                </a>
            </div>

            <div class="w-100">
                <label class="fw-bold mb-1">Alasan Penolakan:</label>
                <div class="input-group mb-3">
                    <textarea id="alasanTolakInput" class="form-control" rows="2"></textarea>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <button id="btnBatalTolakPendingEdit" class="btn btn-secondary">Batal</button>
                    <button id="btnKonfirmasiTolakPendingEdit" class="btn btn-danger">Tolak</button>
                </div>
            </div>
        </div>

        <div id="info-id-ba" style="display:none;"><?= $id_surat ?></div>
        <div id="info-jenis-ba" style="display:none;"><?= $jenis ?></div>
        <div id="info-approver" style="display:none;"><?= isset($_SESSION['nama']) ? $_SESSION['nama'] : '' ?></div>


    </div>
    <script src="../assets/js/jquery-3.7.1.min.js"></script>
    <script src="../assets/js/datatables.min.js"></script>
    <script>
        //DataTables
        $(document).ready(function() {
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
        });
    </script>

    <script>
        const idBA = "<?= $id_surat ?>";
        const jenisBA = "<?= $jenis ?>";
        const approver = "<?= isset($_SESSION['nama']) ? $_SESSION['nama'] : '' ?>";


        function tambahKolom(namaField, lamaVal, baruVal) {
            const headerRow = document.getElementById("headerRow");
            const lamaRow = document.getElementById("lamaRow");
            const baruRow = document.getElementById("baruRow");

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

        function loadDataPerubahan() {
            fetch(`get_pending_history.php?id_ba=${idBA}&approver=${encodeURIComponent(approver)}&jenisBA=${jenisBA}`)
                .then(res => res.json())
                .then(data => {
                    const lama = data.lama || {};
                    const baru = data.baru || {};
                    const namaKolom = data.namaKolom || {};
                    const alasan = data.alasan_edit || "";

                    // Reset tabel
                    document.getElementById("headerRow").innerHTML = "<th>Data</th>";
                    document.getElementById("lamaRow").innerHTML = "<td>Data Lama</td>";
                    document.getElementById("baruRow").innerHTML = "<td>Data Baru</td>";

                    let adaPerubahan = false;

                    // Tambahkan kolom hanya untuk field yang berbeda
                    Object.keys(namaKolom).forEach(key => {
                        const oldVal = lama[key] ?? "";
                        const newVal = baru[key] ?? "";
                        if (oldVal !== newVal) {
                            tambahKolom(namaKolom[key], oldVal, newVal);
                            adaPerubahan = true;
                        }
                    });

                    if (!adaPerubahan) {
                        tambahKolom("Tidak ada perubahan", "-", "-");
                    }

                    document.getElementById("alasanPerubahan").textContent = alasan;
                })
                .catch(() => {
                    tambahKolom("Gagal Memuat", "-", "-");
                });
        }

        document.addEventListener("DOMContentLoaded", loadDataPerubahan);
    </script>

    <script>
        // === Tombol SETUJU kirim ke proses_approval_edit.php ===
        document.getElementById("btnSetujuApprovalEdit").addEventListener("click", function() {
            if (!confirm("Yakin ingin menyetujui perubahan ini?")) {
                return;
            }
            const idBA = "<?= $id_surat ?>";
            const jenisBA = "<?= $jenis ?>";
            const approver = "<?= isset($_SESSION['nama']) ? $_SESSION['nama'] : '' ?>";


            fetch("proses_approval_edit.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded"
                    },
                    body: "id_ba=" + encodeURIComponent(idBA) +
                        "&jenisBA=" + encodeURIComponent(jenisBA) +
                        "&approver=" + encodeURIComponent(approver) +
                        "&alasan_tolak=none" +
                        "&aksi=setuju"
                })
                .then(res => res.json())
                .then(res => {
                    if (res.success === true) {
                        window.location.reload();
                    } else {
                        alert("Gagal memproses persetujuan.");
                    }
                });

        });
    </script>

    <script>
        // Buka popup
        document.querySelector(".btnTolakApprovalEdit").addEventListener("click", function() {
            document.getElementById("popupBGTolakPendingEdit").style.display = "block";
            const pt = document.getElementById("popupBoxTolakPendingEdit");
            pt.style.display = "flex";

            setTimeout(() => document.getElementById("alasanTolakInput").focus(), 100);
        });

        const popupTolak = document.getElementById("popupBoxTolakPendingEdit");
        const bgTolak = document.getElementById("popupBGTolakPendingEdit");
        const btnCloseTolak = document.getElementById("closePopupTolakPendingEdit");
        const btnBatalTolak = document.getElementById("btnBatalTolakPendingEdit");
        const btnKonfTolak = document.getElementById("btnKonfirmasiTolakPendingEdit");

        // Tutup popup
        btnCloseTolak.addEventListener("click", function(ev) {
            ev.preventDefault();
            popupTolak.style.display = "none";
            bgTolak.style.display = "none";
        });

        btnBatalTolak.addEventListener("click", function(ev) {
            ev.preventDefault();
            popupTolak.style.display = "none";
            bgTolak.style.display = "none";
        });

        bgTolak.addEventListener("click", function() {
            popupTolak.style.display = "none";
            bgTolak.style.display = "none";
        });

        popupTolak.addEventListener("click", function(ev) {
            ev.stopPropagation();
        });

        // Tombol KONFIRMASI TOLAK
        btnKonfTolak.addEventListener("click", function() {

            const idBA = document.getElementById("info-id-ba").textContent;
            const jenisBA = document.getElementById("info-jenis-ba").textContent;
            const approver = document.getElementById("info-approver").textContent;
            const alasan = document.getElementById("alasanTolakInput").value;

            if (!confirm("Apakah Anda yakin ingin MENOLAK pengajuan ini?")) {
                return;
            }

            fetch("proses_approval_edit.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded"
                    },
                    body: "id_ba=" + encodeURIComponent(idBA) +
                        "&jenisBA=" + encodeURIComponent(jenisBA) +
                        "&approver=" + encodeURIComponent(approver) +
                        "&alasan_tolak=" + encodeURIComponent(alasan) +
                        "&aksi=tolak"
                })
                .then(res => res.json())
                .then(res => {
                    if (res.success === true) {
                        popupTolak.style.display = "none";
                        bgTolak.style.display = "none";
                        window.location.reload();
                    } else {
                        alert("Gagal memproses penolakan.");
                    }
                });

        });
    </script>

</body>

</html>