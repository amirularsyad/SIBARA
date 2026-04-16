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
<?php
include '../koneksi.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Ambil dan filter data dari form
    // $tanggal           = $_POST['tanggal'] ?? '';
    // $nomor_ba          = str_pad($_POST['nomor_ba'] ?? '', 3, '0', STR_PAD_LEFT);
    // $nama_peminjam     = $_POST['nama_peminjam'] ?? '';
    // $alamat_array      = $_POST['alamat'] ?? [];
    // $alamat_peminjam = is_array($alamat_array) ? implode(", ", $alamat_array) : $alamat_array;
    // $sn                = $_POST['sn'] ?? '';
    // $merek             = $_POST['merk'] ?? '';
    // $prosesor          = $_POST['prosesor'] ?? '';
    // $penyimpanan       = $_POST['penyimpanan'] ?? '';
    // $monitor           = $_POST['monitor'] ?? '';
    // $baterai           = $_POST['baterai'] ?? '';
    // $vga               = $_POST['vga'] ?? '';
    // $ram               = $_POST['ram'] ?? '';
    // $tanggal_pembelian = !empty($_POST['tanggal-pembelian']) ? $_POST['tanggal-pembelian'] : NULL;
    // $saksi             = $_POST['saksi'] ?? '';
    $tanggal           = isset($_POST['tanggal']) ? $_POST['tanggal'] : '';
    $nomor_ba          = str_pad(isset($_POST['nomor_ba']) ? $_POST['nomor_ba'] : '', 3, '0', STR_PAD_LEFT);
    $nama_peminjam     = isset($_POST['nama_peminjam']) ? $_POST['nama_peminjam'] : '';
    $alamat_array      = isset($_POST['alamat']) ? $_POST['alamat'] : array();
    $alamat_peminjam   = is_array($alamat_array) ? implode(", ", $alamat_array) : $alamat_array;
    $sn                = isset($_POST['sn']) ? $_POST['sn'] : '';
    $merek             = isset($_POST['merk']) ? $_POST['merk'] : '';
    $prosesor          = isset($_POST['prosesor']) ? $_POST['prosesor'] : '';
    $penyimpanan       = isset($_POST['penyimpanan']) ? $_POST['penyimpanan'] : '';
    $monitor           = isset($_POST['monitor']) ? $_POST['monitor'] : '';
    $baterai           = isset($_POST['baterai']) ? $_POST['baterai'] : '';
    $vga               = isset($_POST['vga']) ? $_POST['vga'] : '';
    $ram               = isset($_POST['ram']) ? $_POST['ram'] : '';
    $tanggal_pembelian = !empty($_POST['tanggal-pembelian']) ? $_POST['tanggal-pembelian'] : NULL;
    $saksi             = isset($_POST['saksi']) ? $_POST['saksi'] : '';

    $nama_pembuat      = $_SESSION['nama'];

    $pt                = "PT.MSAL (HO)";

    $pertama           = "Timotius Aucky Wusman";
    $jabatan_pertama   = "Direksi MIS";

    $diketahui         = "Heru Agus Susilo";
    $jabatan_diketahui = "Dept. Head HRGA";

    //approval
    $approval_1 = 0;
    $approval_2 = 0;
    $approval_3 = 0;
    $approval_4 = 0;

    // Jalankan query untuk ambil jabatan + departemen
    $jabatan_peminjam = '';
    if (!empty($nama_peminjam)) {
        $stmtJabatan = $koneksi->prepare("
            SELECT CONCAT(jabatan, ' ', departemen) AS jabatan_lengkap
            FROM data_karyawan
            WHERE nama = ?
            LIMIT 1
        ");
        $stmtJabatan->bind_param("s", $nama_peminjam);
        $stmtJabatan->execute();
        $resultJabatan = $stmtJabatan->get_result();
        if ($rowJabatan = $resultJabatan->fetch_assoc()) {
            $jabatan_peminjam = $rowJabatan['jabatan_lengkap'];
        }
        $stmtJabatan->close();
    }

    // Jalankan query untuk ambil jabatan + departemen
    $jabatan_saksi = '';
    if (!empty($saksi)) {
        $stmtSaksi = $koneksi->prepare("
            SELECT CONCAT(jabatan, ' ', departemen) AS jabatan_saksi
            FROM data_karyawan
            WHERE nama = ?
            LIMIT 1
        ");
        $stmtSaksi->bind_param("s", $saksi);
        $stmtSaksi->execute();
        $resultSaksi = $stmtSaksi->get_result();
        if ($rowSaksi = $resultSaksi->fetch_assoc()) {
            $jabatan_saksi = $rowSaksi['jabatan_saksi'];
        }
        $stmtSaksi->close();
    }

    // Kalau ternyata di form memang ada field jabatan_peminjam, bisa timpa hasil query
    if (isset($_POST['jabatan_peminjam']) && $_POST['jabatan_peminjam'] !== '') {
        $jabatan_peminjam = $_POST['jabatan_peminjam'];
    }

    // Validasi wajib isi
    // if (empty($tanggal) || empty($nomor_ba) || empty($nama_peminjam) || empty($alamat_peminjam) || empty($sn) || empty($saksi)) {
    //     die("Data wajib diisi tidak lengkap.");
    // }

    // Siapkan dan jalankan prepared statement
    $stmt = $koneksi->prepare("INSERT INTO ba_serah_terima_notebook 
        (tanggal, pt, nomor_ba, nama_peminjam, jabatan_peminjam, alamat_peminjam, sn, merek, prosesor, penyimpanan, monitor, baterai, vga, ram, tanggal_pembelian, saksi, jabatan_saksi, pertama, jabatan_pertama, diketahui, jabatan_diketahui, approval_1, approval_2, approval_3, approval_4,nama_pembuat) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    if ($stmt) {
        $stmt->bind_param(
            "sssssssssssssssssssssiiiis",
            $tanggal,
            $pt,
            $nomor_ba,
            $nama_peminjam,
            $jabatan_peminjam,
            $alamat_peminjam,
            $sn,
            $merek,
            $prosesor,
            $penyimpanan,
            $monitor,
            $baterai,
            $vga,
            $ram,
            $tanggal_pembelian,
            $saksi,
            $jabatan_saksi,
            $pertama,
            $jabatan_pertama,
            $diketahui,
            $jabatan_diketahui,
            $approval_1,
            $approval_2,
            $approval_3,
            $approval_4,
            $nama_pembuat
        );

        if ($stmt->execute()) {
            // Redirect ke form dengan notifikasi sukses
            header("Location: form_input.php?status=sukses");
            exit();
        } else {
            echo "Gagal menyimpan data: " . $stmt->error;
        }
        $stmt->close();
    } else {
        echo "Query error: " . $koneksi->error;
    }
} else {
    echo "Akses tidak valid.";
}
?>
