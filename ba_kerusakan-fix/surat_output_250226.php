<?php
session_start();

// Jika belum login, arahkan ke halaman login
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../login_registrasi.php");
    exit();
}

// Koneksi ke database
include '../koneksi.php';

// Ambil ID dari GET
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Ambil data berita acara
$query = $koneksi->prepare("
    SELECT bak.*, cb.nama AS kategori_nama
    FROM berita_acara_kerusakan bak
    LEFT JOIN categories_broken cb ON bak.kategori_kerusakan_id = cb.id
    WHERE bak.id = ?
");
$query->bind_param("i", $id);
$query->execute();
$result = $query->get_result();
$data = $result->fetch_assoc();

// Ambil gambar-gambar terkait
$gambar_query = $koneksi->prepare("SELECT file_path FROM gambar_ba_kerusakan WHERE ba_kerusakan_id = ?");
$gambar_query->bind_param("i", $id);
$gambar_query->execute();
$gambar_result = $gambar_query->get_result();
$gambar_paths = [];
while ($row = $gambar_result->fetch_assoc()) {
    $gambar_paths[] = $row['file_path'];
}
?>
<?php
function formatTanggalSurat($tanggal)
{
    $bulan = array(
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
    );
    $pecah = explode('-', $tanggal);
    return $pecah[2] . ' ' . $bulan[(int)$pecah[1]] . ' ' . $pecah[0];
}
?>
<?php
function formatTanggal($tanggal)
{

    $pecah = explode('-', $tanggal);
    return $pecah[2] . '/' . $pecah[1] . '/' . $pecah[0];
}
?>
<?php
function formatTanggalRomawi($tanggalromawi)
{
    $bulan = array(
        1 => 'I',
        'II',
        'III',
        'IV',
        'V',
        'VI',
        'VII',
        'VIII',
        'IX',
        'X',
        'XI',
        'XII'
    );
    $pecah = explode('-', $tanggalromawi);
    return $bulan[(int)$pecah[1]];
}

function getTahun($tanggal)
{
    return date('Y', strtotime($tanggal));
}
?>

<?php
// Sesuaikan $data jika di file Anda variabelnya bukan $data (mis. $row)
$is_penambahan = isset($data['kategori_kerusakan_id']) && (string)$data['kategori_kerusakan_id'] === '12';

$judul_ba = $is_penambahan ? 'BERITA ACARA PENAMBAHAN' : 'BERITA ACARA KERUSAKAN';
$label_kategori = $is_penambahan ? 'Kategori Penambahan' : 'Kategori Kerusakan';
$label_jenis = $is_penambahan ? 'Jenis Penambahan' : 'Jenis Kerusakan';
$label_penyebab = $is_penambahan ? 'Penyebab Penambahan' : 'Penyebab Kerusakan';

$teks_pemeriksaan = $is_penambahan
    ? 'telah dilakukan pemeriksaan oleh Dept. MIS :'
    : 'telah dilakukan pemeriksaan oleh Dept. MIS dimana telah ditemukan kerusakan sebagai berikut :';

$teks_penutup = $is_penambahan
    ? 'Demikian berita acara ini dibuat sebagai dasar untuk penambahan perangkat.'
    : 'Demikian berita acara ini dibuat sebagai dasar untuk perbaikan atau penggantian perangkat yang rusak.';
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo $judul_ba; ?></title>
    <style>
        .border-tes-htm {
            border: #000 1px solid !important;
        }

        .border-tes-orn {
            border: 1px solid darkorange !important;
        }

        .border-tes-mrh {
            border: 1px solid red !important;
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

        body {
            font-family: Arial, sans-serif;
            margin: 0px;
            color: #000;
        }

        .header img {
            float: left;
            width: 70px;
            margin-right: 15px;
        }

        .header h1 {
            font-size: 20px;
            margin: 0;
            padding-top: 10px;
        }

        .header h2 {
            font-size: 16px;
            font-weight: normal;
            margin: 0;
        }

        .center-title {
            text-align: center;
            font-weight: bold;
            margin-top: 30px;
        }

        .center-title .number {
            font-size: 16px;
            margin-top: 5px;
        }

        .content {
            margin: 20px 30px 0 30px;
            font-size: 14px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 14px;
        }

        table,
        td,
        th {
            border: 1px solid black;
            vertical-align: top;
        }

        td,
        th {
            padding: 8px;
        }

        .section-title {
            font-weight: bold;
            margin-top: 20px;
        }

        .note {
            font-size: 13px;
            margin-top: 10px;
        }

        .ttd {
            margin-top: 30px;
            font-size: 14px;
            font-weight: bold;
        }

        .ttd table {
            border: none;
        }

        .ttd tr:first-child {
            position: relative;
        }

        .ttd td {
            border: none;
            text-align: center;
        }

        /* .ttd tr:first-child td:first-child p:first-child{
        text-align: left;
        } */
        /* .ttd tr:first-child td:first-child p:last-child{
        text-align: left;
        padding-left: 20px;
        } */

        .ttd tr:first-child td:last-child {
            position: relative;
        }

        /* .ttd tr:first-child td:last-child div{
            transform: translateY(47px);
        } */

        .signature-row {
            height: 110px;
        }

        .signature {
            padding-top: 0;
            font-weight: bold;
        }

        /* .signature img{
            transform: translateX(20px)
        } */

        /* .signature:first-child{
            text-align: left;
            padding-left: 20px;
            width: 37%;
        } */
        /* .signature:last-child{
            transform: translateX(-12px);
        } */

        .gambar-kerusakan {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 20px;
        }

        .gambar-kerusakan img {
            max-width: 300px;
            max-height: 39vh;
            height: auto;
            width: auto;
            display: inline-block;
            margin: 10px;
            object-fit: contain;
            image-rendering: auto;
        }

        .custom-td-1 p {
            text-align: left;
        }

        .custom-td-1,
        .custom-td-1 p {
            margin: 0;
            padding: 0;
        }

        .custom-td-2 {
            width: 25%;
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
    </style>
</head>
<button id="printBtn" onclick="window.print()">Print / Save PDF</button>

<body>
    <div class="">

    </div>
    <div class="header">
        <?php if ($data['pt'] === 'PT.MSAL (HO)' || $data['pt'] === 'PT.MSAL (PKS)' || $data['pt'] === 'PT.MSAL (SITE)'){ ?>
        <img src="../assets/img/logo.png" alt="Logo MSAL">
        <h1 style="color: #1F3A66;">MULIA
        </h1>
        <h2 style="color: #A5C75C;">SAWIT AGRO LESTARI
        </h2>

        <?php } elseif ($data['pt'] === 'PT.PSAM (PKS)' || $data['pt'] === 'PT.PSAM (SITE)'){ ?>
        <img style="transform:translateY(3mm)" src="../assets/img/psam.jpg" alt="Logo PSAM">
        <h1 style="color: #1F3A66;">PERSADA
        </h1>
        <h2 style="color: #A5C75C;">SEJAHTERA AGRO MAKMUR
        </h2>
        <?php } elseif ($data['pt'] === 'PT.MAPA'){ ?>
        <img src="../assets/img/mapa.jpg" alt="Logo MAPA">
        <h1 style="color: #1F3A66;">MITRA
        </h1>
        <h2 style="color: #A5C75C;">AGRO PERSADA ABADI
        </h2>
        <?php } elseif ($data['pt'] === 'PT.PEAK (PKS)' || $data['pt'] === 'PT.PEAK (SITE)'){ ?>
        <img src="../assets/img/peak.jpg" alt="Logo PEAK">
        <h1 style="color: #1F3A66;">PERSADA
        </h1>
        <h2 style="color: #A5C75C;">ERA AGRO KENCANA
        </h2>
        <?php } elseif ($data['pt'] === 'RO PALANGKARAYA'){ ?>
        <h2 style="color: #A5C75C;">RO PALANGKARAYA
        </h2>
        <?php } elseif ($data['pt'] === 'RO SAMPIT'){ ?>
        <h2 style="color: #A5C75C;">RO SAMPIT
        </h2>
        <?php } elseif ($data['pt'] === 'PT.WCJU (PKS)' || $data['pt'] === 'PT.WCJU (SITE)'){ ?>
        <img src="../assets/img/wcju.jpg" alt="Logo WCJU">
        <h2 style="color: #A5C75C;">WANA CATUR JAYA UTAMA
        </h2>
        <?php } ?>
    </div>
    <div class="center-title">
        <?php echo $judul_ba; ?>

        <?php $sqlAll = "SELECT id FROM berita_acara_kerusakan ORDER BY tanggal ASC, id ASC";
        $resultAll = $koneksi->query($sqlAll);

        $no = 1;
        $found = false;
        while ($row = $resultAll->fetch_assoc()) {
            if ($row['id'] == $id) {
                $found = true;
                break;
            }
            $no++;
        }

        if (!$found) {
            echo "Data tidak ditemukan.";
            exit;
        }
        ?>

        <?php
        if ($data['pt'] === 'PT.MSAL (HO)') {
        ?>
            <div class="number">No:<?php echo $data['nomor_ba']; ?>/MIS-HO/<?php echo formatTanggalRomawi($data['tanggal']); ?>/<?php echo getTahun($data['tanggal']); ?></div>
        <?php
        } else {
        ?>
            <div class="number">No:<?php echo $data['nomor_ba']; ?>/MIS/BAK/
            <?php if ($data['pt'] === 'PT.MSAL (PKS)' || $data['pt'] === 'PT.MSAL (SITE)'){ ?>
            MSAL
            <?php } elseif ($data['pt'] === 'PT.PSAM (PKS)' || $data['pt'] === 'PT.PSAM (SITE)'){ ?>
            PSAM
            <?php } elseif ( $data['pt'] === 'PT.MAPA'){ ?>
            MAPA
            <?php } elseif ($data['pt'] === 'PT.PEAK (PKS)' || $data['pt'] === 'PT.PEAK (SITE)'){ ?>
            PEAK
            <?php } elseif ($data['pt'] === 'PT.WCJU (PKS)' || $data['pt'] === 'PT.WCJU (SITE)'){ ?>
            WCJU
            <?php } elseif ($data['pt'] === 'RO PALANGKARAYA'){ ?>
            RO
            <?php } elseif ($data['pt'] === 'RO SAMPIT'){ ?>
            RO
            <?php } ?>
            /<?php echo formatTanggalRomawi($data['tanggal']); ?>/<?php echo getTahun($data['tanggal']); ?></div>
        <?php
        }
        ?>
    </div>
    <div class="content">
        <p style="font-weight:bold;">Pada hari ini <?php echo formatTanggalSurat(date('Y-m-d', strtotime($data['tanggal']))); ?> <?php echo $teks_pemeriksaan; ?></p>

        <?php
        if ($data['pt'] === 'PT.MSAL (HO)') {

            // Ambil posisi dari tabel data_karyawan
            $nama_pengguna = $data['user'];
            $posisi = '';
            $query_posisi = $koneksi->prepare("SELECT posisi FROM data_karyawan WHERE nama = ?");
            $query_posisi->bind_param("s", $nama_pengguna);
            $query_posisi->execute();
            $result_posisi = $query_posisi->get_result();
            if ($row_posisi = $result_posisi->fetch_assoc()) {
                $posisi = $row_posisi['posisi'];
            }
            $query_posisi->close();

            // Ambil jabatan dan departemen dari tabel data_karyawan
            $nama_pengguna1 = $data['pembuat'];
            $posisi1 = '';
            $query_posisi1 = $koneksi->prepare("SELECT jabatan, departemen FROM data_karyawan WHERE nama = ?");
            $query_posisi1->bind_param("s", $nama_pengguna1);
            $query_posisi1->execute();
            $result_posisi1 = $query_posisi1->get_result();
            if ($row_posisi1 = $result_posisi1->fetch_assoc()) {
                $posisi1 = $row_posisi1['jabatan'] . ' ' . $row_posisi1['departemen'];
            }
            $query_posisi1->close();

            // Ambil jabatan dan departemen dari tabel data_karyawan
            $nama_pengguna2 = $data['penyetujui'];
            $posisi2 = '';
            $query_posisi2 = $koneksi->prepare("SELECT jabatan, departemen FROM data_karyawan WHERE nama = ?");
            $query_posisi2->bind_param("s", $nama_pengguna2);
            $query_posisi2->execute();
            $result_posisi2 = $query_posisi2->get_result();
            if ($row_posisi2 = $result_posisi2->fetch_assoc()) {
                $posisi2 = $row_posisi2['jabatan'] . ' ' . $row_posisi2['departemen'];
            }
            $query_posisi2->close();

            // Ambil jabatan dan departemen dari tabel data_karyawan
            // $nama_pengguna3 = $data['peminjam'];
            // $posisi3 = '';
            // $query_posisi3 = $koneksi->prepare("SELECT jabatan, departemen FROM data_karyawan WHERE nama = ?");
            // $query_posisi3->bind_param("s", $nama_pengguna3);
            // $query_posisi3->execute();
            // $result_posisi3 = $query_posisi3->get_result();
            // if ($row_posisi3 = $result_posisi3->fetch_assoc()) {
            //     $posisi3 = $row_posisi3['jabatan'] . ' ' . $row_posisi3['departemen'];
            // }
            // $query_posisi3->close();

            // Ambil jabatan dan departemen dari tabel data_karyawan
            $nama_pengguna4 = $data['atasan_peminjam'];
            $posisi4 = '';
            $query_posisi4 = $koneksi->prepare("SELECT jabatan, departemen FROM data_karyawan WHERE nama = ?");
            $query_posisi4->bind_param("s", $nama_pengguna4);
            $query_posisi4->execute();
            $result_posisi4 = $query_posisi4->get_result();
            if ($row_posisi4 = $result_posisi4->fetch_assoc()) {
                $posisi4 = $row_posisi4['jabatan'] . ' ' . $row_posisi4['departemen'];
            }
            $query_posisi4->close();

            $nama_pengguna5 = $data['diketahui'];
            $posisi5 = '';
            $query_posisi5 = $koneksi->prepare("SELECT jabatan, departemen FROM data_karyawan WHERE nama = ?");
            $query_posisi5->bind_param("s", $nama_pengguna5);
            $query_posisi5->execute();
            $result_posisi5 = $query_posisi5->get_result();
            if ($row_posisi5 = $result_posisi5->fetch_assoc()) {
                $posisi5 = $row_posisi5['jabatan'] . ' ' . $row_posisi5['departemen'];
            }
            $query_posisi5->close();
        } else {
            // Ambil posisi dari tabel data_karyawan
            $nama_pengguna = $data['user'];
            $posisi = '';
            $query_posisi = $koneksi->prepare("SELECT posisi FROM data_karyawan_test WHERE nama = ?");
            $query_posisi->bind_param("s", $nama_pengguna);
            $query_posisi->execute();
            $result_posisi = $query_posisi->get_result();
            if ($row_posisi = $result_posisi->fetch_assoc()) {
                $posisi = $row_posisi['posisi'];
            }
            $query_posisi->close();
            // Ambil jabatan dan departemen dari tabel data_karyawan
            $nama_pengguna1 = $data['pembuat'];
            $posisi1 = '';
            $query_posisi1 = $koneksi->prepare("SELECT posisi FROM data_karyawan_test WHERE nama = ?");
            $query_posisi1->bind_param("s", $nama_pengguna1);
            $query_posisi1->execute();
            $result_posisi1 = $query_posisi1->get_result();
            if ($row_posisi1 = $result_posisi1->fetch_assoc()) {
                $posisi1 = $row_posisi1['posisi'];
            }
            $query_posisi1->close();
            // Ambil jabatan dan departemen dari tabel data_karyawan
            $nama_pengguna2 = $data['penyetujui'];
            $posisi2 = '';
            $query_posisi2 = $koneksi->prepare("SELECT posisi FROM data_karyawan_test WHERE nama = ?");
            $query_posisi2->bind_param("s", $nama_pengguna2);
            $query_posisi2->execute();
            $result_posisi2 = $query_posisi2->get_result();
            if ($row_posisi2 = $result_posisi2->fetch_assoc()) {
                $posisi2 = $row_posisi2['posisi'];
            }
            $query_posisi2->close();

            // Ambil jabatan dan departemen dari tabel data_karyawan
            $nama_pengguna3 = $data['peminjam'];
            $posisi3 = '';
            $query_posisi3 = $koneksi->prepare("SELECT posisi FROM data_karyawan_test WHERE nama = ?");
            $query_posisi3->bind_param("s", $nama_pengguna3);
            $query_posisi3->execute();
            $result_posisi3 = $query_posisi3->get_result();
            if ($row_posisi3 = $result_posisi3->fetch_assoc()) {
                $posisi3 = $row_posisi3['posisi'];
            }
            $query_posisi3->close();

            // Ambil jabatan dan departemen dari tabel data_karyawan
            $nama_pengguna4 = $data['atasan_peminjam'];
            $posisi4 = '';
            $query_posisi4 = $koneksi->prepare("SELECT posisi FROM data_karyawan_test WHERE nama = ?");
            $query_posisi4->bind_param("s", $nama_pengguna4);
            $query_posisi4->execute();
            $result_posisi4 = $query_posisi4->get_result();
            if ($row_posisi4 = $result_posisi4->fetch_assoc()) {
                $posisi4 = $row_posisi4['posisi'];
            }
            $query_posisi4->close();

            $nama_pengguna5 = $data['diketahui'];
            $posisi5 = '';
            $query_posisi5 = $koneksi->prepare("SELECT posisi FROM data_karyawan_test WHERE nama = ?");
            $query_posisi5->bind_param("s", $nama_pengguna5);
            $query_posisi5->execute();
            $result_posisi5 = $query_posisi5->get_result();
            if ($row_posisi5 = $result_posisi5->fetch_assoc()) {
                $posisi5 = $row_posisi5['posisi'];
            }
            $query_posisi5->close();
        }
        ?>


        <table>
            <tr>
                <th>Jenis Perangkat</th>
                <th>Merek/ Th Perolehan</th>
                <th>Lokasi</th>
                <th>Pengguna</th>
            </tr>
            <tr>
                <td><?php echo $data['jenis_perangkat']; ?></td>
                <td><?php echo nl2br($data['merek']); ?><br /> SN: <?php echo $data['sn']; ?><br />Tahun: <?php echo $data['tahun_perolehan'] ?><br />Nomor PO: <?php echo $data['no_po'] ?></td>
                <td style="text-align: center;"><?php echo $data['lokasi']; ?></td>
                <?php
                if ($data['pt'] === 'PT.MSAL (HO)') {
                ?>
                    <td style="text-align: center;"><?php echo nl2br($data['peminjam'] . ' <br /> ' . $posisi); ?></td>
                <?php
                }
                if ($data['pt'] !== 'PT.MSAL (HO)') {
                ?>
                    <td style="text-align: center;"><?php echo nl2br($data['peminjam']); ?></td>
                <?php
                }
                ?>
            </tr>
        </table>

        <table>
            <tr>
                <td>
                    <p style="margin:0;font-weight:bold;"><?php echo $label_kategori; ?>:</p>
                </td>
            </tr>
            <tr>
                <td><?php
                    if (!empty($data['kategori_nama'])) {
                        echo $data['kategori_nama'];
                        if (strtoupper($data['kategori_nama']) === "DLL" && !empty($data['keterangan_dll'])) {
                            echo " (" . htmlspecialchars($data['keterangan_dll']) . ")";
                        }
                    } else {
                        echo "-";
                    }
                    ?>
                </td>
            </tr>
        </table>

        <table>
            <tr>
                <td>
                    <p style="margin:0;font-weight:bold;"><?php echo $label_jenis; ?>:</p>
                </td>
            </tr>
            <tr>
                <td><?php echo nl2br($data['deskripsi']); ?>.</td>
            </tr>
        </table>

        <table>
            <tr>
                <td>
                    <p style="margin:0;font-weight:bold;"><?php echo $label_penyebab; ?>:</p>
                </td>
            </tr>
            <tr>
                <td><?php echo nl2br($data['penyebab_kerusakan']); ?>.</td>
            </tr>
        </table>

        <table>
            <tr>
                <td>
                    <p style="margin:0;font-weight:bold;">Rekomendasi MIS:</p>
                </td>
            </tr>
            <tr>
                <td><?php echo nl2br($data['rekomendasi_mis']); ?>.</td>
            </tr>
        </table>

        <p class="note"><strong><?php echo $teks_penutup; ?></strong></p>
        <p class="note"><strong>Note: Tahun pembelian perangkat <?php echo $data['tahun_perolehan']; ?>.</strong></p>

        <div class="ttd">
            <?php
            // Buat array tanda tangan yang valid (bukan "-")
            $signatures = [];

            if ($data['pt'] === 'PT.MSAL (HO)') {

                if ($data['pembuat'] !== '-') {
                    $signatures[] = [
                        'label' => 'DIBUAT OLEH,',
                        'nama' => $data['pembuat'],
                        'posisi' => $posisi1,
                        'approval' => $data['approval_1'],
                        'autograph' => $data['autograph_1'],
                        'tanggal' => $data['tanggal_approve_1']
                    ];
                }
                if ($data['peminjam'] !== '-') {
                    $signatures[] = [
                        'label' => ' ',
                        'nama' => ' ',
                        'posisi' => ' ',
                        'approval' => $data['approval_3'],
                        'autograph' => $data['autograph_3'],
                        'tanggal' => ' '
                    ];
                }
                if ($data['atasan_peminjam'] !== '-') {
                    $signatures[] = [
                        'label' => 'ATASAN PENGGUNA,',
                        'nama' => $data['atasan_peminjam'],
                        'posisi' => $posisi4,
                        'approval' => $data['approval_4'],
                        'autograph' => $data['autograph_4'],
                        'tanggal' => $data['tanggal_approve_4']
                    ];
                }
                if ($data['diketahui'] !== '-') {
                    $signatures[] = [
                        'label' => 'DIKETAHUI OLEH,',
                        'nama' => $data['diketahui'],
                        'posisi' => $posisi5,
                        'approval' => $data['approval_5'],
                        'autograph' => $data['autograph_5'],
                        'tanggal' => $data['tanggal_approve_5']
                    ];
                }
                if ($data['penyetujui'] !== '-') {
                    $signatures[] = [
                        'label' => 'DISETUJUI OLEH,',
                        'nama' => $data['penyetujui'],
                        'posisi' => $posisi2,
                        'approval' => $data['approval_2'],
                        'autograph' => $data['autograph_2'],
                        'tanggal' => $data['tanggal_approve_2']
                    ];
                }
            } else {


                if ($data['peminjam'] !== '-') {
                    $signatures[] = [
                        'label' => 'PENGGUNA',
                        'nama' => $data['peminjam'],
                        'posisi' => ' ',
                        'approval' => $data['approval_3'],
                        'autograph' => $data['autograph_3'],
                        'tanggal' => $data['tanggal_approve_3']
                    ];
                }
                if ($data['atasan_peminjam'] !== '-') {
                    $signatures[] = [
                        'label' => 'ATASAN PENGGUNA,',
                        'nama' => $data['atasan_peminjam'],
                        'posisi' => $posisi4,
                        'approval' => $data['approval_4'],
                        'autograph' => $data['autograph_4'],
                        'tanggal' => $data['tanggal_approve_4']
                    ];
                }
                if ($data['diketahui'] !== '-') {
                    $signatures[] = [
                        'label' => 'DIKETAHUI,',
                        'nama' => $data['diketahui'],
                        'posisi' => $posisi5,
                        'approval' => $data['approval_5'],
                        'autograph' => $data['autograph_5'],
                        'tanggal' => $data['tanggal_approve_5']
                    ];
                }
                if ($data['penyetujui'] !== '-') {
                    $signatures[] = [
                        'label' => 'DISETUJUI,',
                        'nama' => $data['penyetujui'],
                        'posisi' => $posisi2,
                        'approval' => $data['approval_2'],
                        'autograph' => $data['autograph_2'],
                        'tanggal' => $data['tanggal_approve_2']
                    ];
                }

                if ($data['pembuat'] !== '-') {
                    $signatures[] = [
                        'label' => 'DIKETAHUI,',
                        'nama' => $data['pembuat'],
                        'posisi' => $posisi1,
                        'approval' => $data['approval_1'],
                        'autograph' => $data['autograph_1'],
                        'tanggal' => $data['tanggal_approve_1']
                    ];
                }
            }

            $count = count($signatures);
            if (!empty($data['peminjam']) && $data['pt'] === 'PT.MSAL (HO)'):
                $countFix = (100 / ($count - 1));
            else :
                $countFix = 100 / $count;
            endif;

            ?>
            <table style="width: 100%; text-align: center;">
                <tr>
                    <td class="custom-td-1" colspan="<?= $count ?>">
                        <p>Jakarta, <?= formatTanggalSurat(date('Y-m-d', strtotime($data['tanggal']))); ?></p>
                    </td>
                </tr>
                <tr>
                    <?php
                    if ($data['pt'] === 'PT.MSAL (HO)') {
                        $has_atasan = ($data['atasan_peminjam'] !== '-');
                        $has_diketahui = ($data['diketahui'] !== '-');
                        foreach ($signatures as $sig) {
                            // Gabungkan ATASAN + DIKETAHUI jadi satu kolom
                            if ($sig['label'] === 'ATASAN PENGGUNA,') {
                                if ($has_atasan && $has_diketahui) {
                                    echo '<td colspan="2"><p>DIKETAHUI,</p></td>';
                                } elseif ($has_atasan || $has_diketahui) {
                                    echo '<td><p>DIKETAHUI,</p></td>';
                                }
                            } elseif ($sig['label'] === 'DIKETAHUI OLEH,') {
                                // Lewati kolom ini karena sudah digabung
                                continue;
                            } else {
                                echo '<td><p>' . $sig['label'] . '</p></td>';
                            }
                        }
                    } else {
                        foreach ($signatures as $sig) {
                            echo '<td><p>' . $sig['label'] . '</p></td>';
                        }
                    }
                    ?>
                </tr>

                <tr class="signature-row">

                    <?php foreach ($signatures as $index => $sig): ?>
                        <?php if ($index === 1 && $data['pt'] === 'PT.MSAL (HO)'): ?>
                            <td class="signature" style="width: 5%;">
                            <?php else: ?>
                            <td class="signature" style="width: <?= $countFix ?>%;">
                            <?php endif; ?>
                            <div style="height: 110px; display:flex; flex-direction: column; align-items:center; justify-content:center;">

                                <?php if ($sig['label'] === 'ATASAN PENGGUNA,'): ?>
                                    <div style="display:flex; align-items:center; justify-content:center; gap:8px;position: relative;">

                                        <?php if (!empty($sig['autograph'])): ?>
                                            <?php
                                            $imgBase64 = base64_encode($sig['autograph']);
                                            $src = "data:image/png;base64," . $imgBase64;
                                            ?>
                                            <div style="display:flex; flex-direction: column;">
                                                <p style="font-size:8px; margin:0;align-self:flex-start;">digital sign</p>
                                                <img src="<?= $src; ?>" style="max-height:105px; max-width:120px; object-fit:contain;">
                                                <p style="margin:0; margin-top:10px;font-size:8px; align-self:flex-end;"><?= formatTanggal(date('y-m-d', strtotime($sig['tanggal']))); ?></p>
                                            </div>
                                        <?php elseif ($sig['approval'] == 1 && empty($sig['autograph'])): ?>
                                            <p style="font-size:12px; font-weight:bold; margin:0;">Approved</p>
                                        <?php endif; ?>

                                    </div>
                                <?php elseif ($index === 1): ?>

                                    <?php if (!empty($sig['autograph'])): ?>
                                        <?php
                                        $imgBase64 = base64_encode($sig['autograph']);
                                        $src = "data:image/png;base64," . $imgBase64;
                                        ?>
                                        <img src="<?= $src; ?>" style="max-height:30px;position:absolute;">
                                    <?php elseif ($sig['approval'] == 1): ?>
                                        <p style="font-size:12px;font-weight:bold;">Approved</p>
                                    <?php endif; ?>

                                <?php elseif (!empty($sig['autograph'])): ?>
                                    <?php
                                    $imgBase64 = base64_encode($sig['autograph']);
                                    $src = "data:image/png;base64," . $imgBase64;
                                    ?>
                                    <div style="width: 120px; display: flex;">
                                        <p style="font-size: 8px;">digital sign</p>
                                    </div>
                                    <img src="<?= $src; ?>" alt="Tanda Tangan" style="max-height: 105px; max-width: 120px; object-fit: contain;">
                                    <div style="width: 120px; display: flex; justify-content: end;">
                                        <p style="font-size: 8px; margin:0; padding:0;"><?= formatTanggal(date('y-m-d', strtotime($sig['tanggal']))); ?></p>
                                    </div>

                                <?php elseif ($sig['approval'] == 1 && empty($sig['autograph'])): ?>
                                    <p style="font-size:12px; font-weight:bold; margin:0;">Approved</p>

                                <?php endif; ?>
                            </div>
                            <?= htmlspecialchars($sig['nama']); ?><br>
                            <span class="subtext"><?= htmlspecialchars($sig['posisi']); ?></span>
                            </td>
                        <?php endforeach; ?>

                </tr>
            </table>
        </div>

    </div>

    <?php
    $gambar_per_halaman = 4;
    $total_gambar = count($gambar_paths);
    for ($i = 0; $i < $total_gambar; $i += $gambar_per_halaman):
        $gambar_chunk = array_slice($gambar_paths, $i, $gambar_per_halaman);
    ?>
        <div class="page-break"></div>
        <div class="header">
            <img src="../assets/img/logo.png" alt="Logo MSAL">
            <h1 style="color: #1F3A66;">MULIA</h1>
            <h2 style="color: #A5C75C;">SAWIT AGRO LESTARI</h2>
        </div>
        <div class="center-title">
            <?php echo $judul_ba; ?>
            <div class="number">No:<?= str_pad($no, 3, '0', STR_PAD_LEFT) ?>/MIS-HO/<?php echo formatTanggalRomawi($data['tanggal']); ?>/<?php echo getTahun($data['tanggal']); ?></div>
            <h4>LAMPIRAN</h4>
        </div>
        <div>
            <div class="gambar-kerusakan">
                <?php foreach ($gambar_chunk as $gambar): ?>
                    <img src="<?php echo $gambar; ?>" alt="Gambar Kerusakan">
                <?php endforeach; ?>
            </div>
        </div>
    <?php endfor; ?>

</body>

</html>