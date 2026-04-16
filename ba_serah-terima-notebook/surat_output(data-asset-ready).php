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
  <title>Surat Serah Terima Penggunaan Notebook</title>
    <style>
    body {
        font-family: "Times New Roman", Times, serif;
        margin: 40px 20px 0 40px;
        line-height: 1.15;
        text-align: justify;
        word-spacing: .5px;
    }
    .header {
        text-align: center;
        position: relative;
    }
    .logo {
        position: absolute;
        top: -10px;
        left: 0;
        width: 65px;
    }
    .title {
        font-weight: bold;
        text-align: center;
        margin-top: 10px;
    }
    .subtitle {
        font-weight: bold;
        text-align: center;
        margin-bottom: 30px;
    }
    .section-title {
        text-align: center;
        margin: 30px 0 20px;
        font-weight: bold;
    }
    .dashed {
        text-align: center;
        margin: 0 0 20px 0;
    }
    table {
        margin-top: 10px;
    }
    td {
        vertical-align: top;
        padding: 0px 8px;
    }

    .data-barang{
        margin-left: 45px;
    }

    .data-barang td{
        padding: 0;
    }

    .underline {
        text-decoration: underline;
    }
    .bold {
        font-weight: bold;
    }
    .italic {
        font-style: italic;
    }
    .pasal2,.pasal3{
        list-style-position: outside; /* pastikan nomor di luar */
        padding-left: 15px;
    }

    .pasal2 li,.pasal3 li{
        margin-bottom: 20px;
        padding-left: .5em;
        text-align: justify;
    }
    .page {
        page-break-inside: avoid;
    }
    
    .rata-nomor {
    list-style-position: outside; /* pastikan nomor di luar */
    padding-left: 2.5em; /* jarak antara nomor dan teks */
    }

    .rata-nomor li {
    text-align: justify;
      /* mengatur baris pertama kembali ke kiri */
    padding-left: .5em;  /* baris kedua dan seterusnya menjorok */
    }
    </style>
</head>
<body>

<?php
include '../koneksi.php';

$id = $_GET['id'] ?? 0;

// Ambil data utama
$query = mysqli_query($koneksi, "SELECT * FROM ba_serah_terima_notebook WHERE id = '$id'");
$data = mysqli_fetch_assoc($query);

// Ambil data notebook berdasarkan SN
$sn = $data['sn'];
$query_notebook = mysqli_query($koneksi, "SELECT * FROM barang_notebook_laptop WHERE serial_number = '$sn'");
$notebook = mysqli_fetch_assoc($query_notebook);

// Ambil data karyawan berdasarkan nama_peminjam
$nama_peminjam = $data['nama_peminjam'];
$query_karyawan = mysqli_query($koneksi, "SELECT * FROM data_karyawan WHERE nama = '$nama_peminjam'");
$karyawan = mysqli_fetch_assoc($query_karyawan);

// Ambil data karyawan berdasarkan saksi
$nama_saksi = $data['saksi'];
$query_saksi = mysqli_query($koneksi, "SELECT * FROM data_karyawan WHERE nama = '$nama_saksi'");
$saksi = mysqli_fetch_assoc($query_saksi);

// Fungsi bantu: ubah angka bulan ke romawi
function bulanRomawi($bulan) {
    $romawi = ['', 'I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII'];
    return $romawi[(int)$bulan];
}

// Fungsi bantu: ubah hari ke Bahasa Indonesia
function hariIndo($tanggal) {
    $hariInggris = date('l', strtotime($tanggal));
    $map = [
        'Sunday' => 'Minggu',
        'Monday' => 'Senin',
        'Tuesday' => 'Selasa',
        'Wednesday' => 'Rabu',
        'Thursday' => 'Kamis',
        'Friday' => 'Jumat',
        'Saturday' => 'Sabtu'
    ];
    return $map[$hariInggris];
}

// Fungsi bantu: ubah bulan ke nama Indonesia
function bulanIndo($tanggal) {
    $bulan = date('n', strtotime($tanggal));
    $nama = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    ];
    return $nama[$bulan];
}
?>

  <div class="header">
    <img src="../assets/img/logo.png" alt="Logo MSAL" class="logo">
    <div class="title" style="margin-bottom: 20px;">SURAT PERJANJIAN PENGGUNAAN NOTEBOOK INVENTARIS <br>PT. MULIA SAWIT AGRO LESTARI</div>
    <div class="subtitle">
        NO. <?php echo $data['nomor_ba']; ?>/MSAL-MIS/<?php echo bulanRomawi(date('m', strtotime($data['tanggal']))); ?>/<?php echo date('Y', strtotime($data['tanggal'])); ?>
    </div>
  </div>

  <p>Yang bertanda tangan di bawah ini :</p>

  <ol class="rata-nomor">
    <li>
      <span class="bold"><?php echo $data['pertama']; ?></span> selaku <span class="bold">Direktur MIS</span> dalam kedudukannya mewakili
      <span class="bold">PT. Mulia Sawit Agro Lestari</span> yang berkedudukan di Jl. Radio Dalam Raya No. 87A, Gandaria Utara,
      Kebayoran Baru, Jakarta Selatan 12140; (Untuk selanjutnya disebut sebagai PIHAK PERTAMA).
    </li>
    <li>
      <span class="bold"><?php echo $data['nama_peminjam']; ?></span>, karyawan PT. Mulia Sawit Agro Lestari, yang pada saat penandatanganan surat
      perjanjian ini menduduki jabatan sebagai <span class="bold"><?php echo $karyawan['jabatan'] . ' ' . $karyawan['departemen']; ?></span>, bertempat tinggal di 
      <?php echo $data['alamat_peminjam']; ?>, yang dalam surat perjanjian ini bertindak untuk dan atas nama dirinya sendiri,
      selanjutnya disebut sebagai PIHAK KEDUA.
    </li>
  </ol>

    <p style="margin-bottom: 5px;">Selanjutnya PIHAK PERTAMA dan PIHAK KEDUA secara bersama-sama disebut sebagai “Para Pihak”.</p>

    <div class="dashed">--------------------------Para Pihak Menerangkan Terlebih Dahulu--------------------------</div>

    <ol class="rata-nomor">
        <li style="margin-bottom: 20px;">
        Bahwa sesuai dengan SK MSAL Group <span class="bold">No. 115/MSAL-DIR/IX/2015</span> tanggal 15 September
        2015 tentang Perubahan Klasifikasi Perlengkapan Kerja Karyawan, maka PIHAK PERTAMA memfasilitasi PIHAK KEDUA dengan
        cara meminjamkan Notebook Inventaris Perusahaan.
        </li>
        <li>
        Bahwa PIHAK KEDUA, adalah karyawan PIHAK PERTAMA yang dipinjamkan Notebook Inventaris oleh PIHAK PERTAMA guna
        mendukung kegiatan pekerjaannya.
        </li>
    </ol>

    <p style="margin-bottom: 0;">
        Sehubungan dengan hal tersebut, maka pada hari ini <span class="bold"><?php echo hariIndo($data['tanggal']) . ', ' . date('d', strtotime($data['tanggal'])) . ' ' . bulanIndo($data['tanggal']) . ' ' . date('Y', strtotime($data['tanggal'])); ?>
    </span>, kedua belah pihak
        bersepakat untuk membuat dan menandatangani surat perjanjian penggunaan Notebook Inventaris yang dipinjamkan oleh
        PIHAK PERTAMA dengan syarat-syarat dan ketentuan-ketentuan sebagai berikut:
    </p>

    <div class="section-title" style="margin-top:5px;"><h1 style="font-size: 16px; margin-bottom:0;">Pasal 1</h1><br>Spesifikasi Notebook Inventaris</div>

    <p>Spesifikasi Notebook Inventaris seperti yang diperjanjikan, dijelaskan dengan rinci sebagai berikut:</p>

    <table class="data-barang" style="line-height: 1;">
        <tr><td>Merk</td><td>: <?php echo $notebook['merk']; ?></td></tr>
        <tr><td>Serial Number</td><td>: <?php echo $notebook['serial_number']; ?></td></tr>
        <tr><td>Prosesor</td><td>: <?php echo $notebook['processor']; ?></td></tr>
        <tr><td>Hard Disk</td><td>: <?php echo $notebook['penyimpanan']; ?></td></tr>
        <tr><td>Monitor</td><td>: <?php echo $notebook['monitor']; ?></td></tr>
        <tr><td>Baterai</td><td>: <?php echo $notebook['baterai']; ?></td></tr>
        <tr><td>VGA Card</td><td>: <?php echo $notebook['vga']; ?></td></tr>
        <tr><td>RAM</span></td><td>: <?php echo $notebook['ram']; ?></td></tr>
        <tr><td>Tgl. Pembelian</td><td>: <?php echo date('d', strtotime($notebook['tgl_beli'])) . ' ' . bulanIndo($notebook['tgl_beli']) . ' ' . date('Y', strtotime($notebook['tgl_beli'])); ?></td></tr>
    </table>
<!-- PAGE BREAK -->
<div style="page-break-after: always;"></div>

<!-- Halaman Kedua -->
<div class="page" style="margin-top: 40px;">
    <h3 style="text-align:center;font-size:16px;">Pasal 2</h3>
    <h4 style="text-align:center;margin-bottom:0;">Kewajiban Pihak Pertama</h4>
    <ol class="pasal2" style="text-align: justify; word-spacing: 1px; margin-bottom:40px;">
        <li>PIHAK PERTAMA berkewajiban menyediakan Notebook Inventaris guna kelancaran kegiatan pekerjaan PIHAK KEDUA.</li>
        <li>PIHAK PERTAMA berkewajiban memberikan dukungan/support dalam hal teknis dan non teknis mengenai permasalahan yang terjadi seputar penggunaan Notebook Inventaris.</li>
        <li>PIHAK PERTAMA akan melakukan perbaikan apabila ada kerusakan wajar yang tidak disebabkan oleh kelalaian PIHAK KEDUA.</li>
        <li>PIHAK PERTAMA menjamin segala program dan aplikasi yang diinstal di Notebook Inventaris dapat berguna sebagai mana mestinya dalam mendukung aktifitas pekerjaan PIHAK KEDUA.</li>
        <li>PIHAK PERTAMA melakukan update antivirus ataupun program lainnya secara berkala jika dipandang perlu demi keamanan data-data dan kelancaran kegiatan pekerjaan PIHAK KEDUA.</li>
        <li>PIHAK PERTAMA akan melakukan audit secara berkala untuk memeriksa aplikasi/program yang diinstal Notebook Inventaris. Audit ini bertujuan untuk proses maintenance aset Perusahaan dan memastikan aplikasi/program yang diinstal hanya yang berhubungan dengan pekerjaan serta dapat mendukung secara penuh kegiatan pekerjaan yang dilakukan oleh PIHAK KEDUA.</li>
    </ol>

    <h3 style="text-align:center;font-size:16px;">Pasal 3</h3>
    <h4 style="text-align:center;margin-bottom:0;">Kewajiban Pihak Kedua</h4>
    <ol class="pasal3" style="text-align: justify; word-spacing: 1px;" start="1">
        <li>PIHAK KEDUA tidak diperkenankan untuk menginstall segala bentuk aplikasi/program yang tidak ada hubungannya dengan pekerjaan. Aplikasi/program yang akan diinstal di Notebook Inventaris harus atas rekomendasi dan dilakukan oleh Staff MIS.</li>
        <li>PIHAK KEDUA tidak diperkenankan menambahkan komponen untuk dipasang di Notebook Inventaris tanpa rekomendasi dan diketahui oleh Departemen MIS.</li>
        <li>PIHAK KEDUA tidak diperkenankan melakukan perbaikan sendiri/menyerahkan ke pihak lain apabila terjadi kerusakan, Notebook Inventaris yang rusak wajib diperbaiki oleh Departemen MIS.</li>
        <li>Segala bentuk data yang ada di file Notebook Inventaris adalah hak milik PIHAK PERTAMA. PIHAK KEDUA wajib menjaga kerahasiaannya dan tidak diperkenankan untuk menyebarluaskan keluar lingkungan pekerjaan.</li>
        <li>Dalam hal Notebook Inventaris terjadi kehilangan atau kerusakan total yang disebabkan karena kelalaian PIHAK KEDUA, sepenuhnya akan menjadi tanggung jawab PIHAK KEDUA.</li>
        <li>Mengembalikan kepada PIHAK PERTAMA apabila masa penggunaan Notebook Inventaris telah selesai dan/atau berakhirnya masa perjanjian.</li>
    </ol>
</div>

<!-- PAGE BREAK -->
<div style="page-break-after: always;"></div>

<!-- Halaman Ketiga -->
<div class="page">
    <h3 style="text-align:center;font-size:16px;">Pasal 4</h3>
    <h4 style="text-align:center;margin-bottom:0;">Berakhirnya Perjanjian</h4>
    <p style="text-align: justify;margin:0;margin-top:16px;">
        Perjanjian ini berakhir pada kondisi-kondisi sebagai berikut:
    </p>

    <ol class="pasal2" style="margin-top:0;">
        <li style="margin-bottom:0;">
            Berakhirnya masa penggunaan Notebook Inventaris.
        </li>
        <li>
            Hubungan kekaryawanan PIHAK KEDUA dengan PIHAK PERTAMA berakhir.
        </li>
    </ol>

    <p style="text-align: justify;">
        Demikian Surat Perjanjian ini dibuat di atas materai cukup dalam rangkap 2 (dua) dengan asli pertama
        dipegang oleh PIHAK KEDUA, dan asli kedua dipegang oleh PIHAK PERTAMA.
    </p>

    <br>
    <table style="width: 100%; text-align:start; margin-top: 20px;font-family: Arial, Helvetica, sans-serif;">
        <tr>
        <td style="padding: 0;padding-bottom:20px;">Jakarta, <?php echo date('d-m-Y', strtotime($data['tanggal'])); ?></td>
        </tr>
        <tr>
        <td style="width: 50%;font-family: Arial, Helvetica, sans-serif;">PIHAK PERTAMA<br>PT. Mulia Sawit Agro Lestari</td>
        <td style="width: 50%;padding-left:15%;">PIHAK KEDUA</td>
        </tr>
        <tr style="height: 120px;">
        <td></td>
        <td></td>
        </tr>
        <tr>
        <td><strong class="underline"><?php echo $data['pertama']; ?></strong><br>Direksi MIS</td>
        <td style="padding-left:15%;"><strong class="underline"><?php echo $data['nama_peminjam']; ?></strong><br><?php echo $karyawan['jabatan'] . ' ' . $karyawan['departemen']; ?></td>
        </tr>
    </table>

    <br><br>

    <div style="text-align: start; margin-top: 40px;margin-left:30px;font-family: Arial, Helvetica, sans-serif;">
        SAKSI
    </div>

    <table style="width: 100%; text-align: start; margin-top: 120px;font-family: Arial, Helvetica, sans-serif;">
        <tr>
        <td style="width: 25%;"><strong class="underline"><?php echo $saksi['nama']; ?></strong><br><?php echo $saksi['jabatan'] . " " . $saksi['departemen']; ?></td>
        
        </tr>
        <tr style="height: 30px;">
            <td></td><td style="width: 50%;padding-left:30px;">DIKETAHUI,</td>
        </tr>
        <tr style="height: 120px;">
        <td></td>
        <td></td>
        </tr>
        <tr>
        <td></td>
        <td><strong class="underline"><?php echo $data['diketahui']; ?></strong><br>Dept. Head HRGA</td>
        </tr>
    </table>
</div>

</body>
</html>