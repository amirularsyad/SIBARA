<?php
include '../koneksi.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Ambil dan filter data dari form
    $merk         = $_POST['merk'] ?? '';
    $serialNumber = $_POST['serial_number'] ?? '';
    $processor    = $_POST['processor'] ?? '';
    $penyimpanan  = $_POST['penyimpanan'] ?? '';
    $monitor      = $_POST['monitor'] ?? '';
    $baterai      = $_POST['baterai'] ?? '';
    $vga          = $_POST['vga'] ?? '';
    $ram          = $_POST['ram'] ?? '';
    $tgl_beli     = $_POST['tgl_beli'] ?? '';
    $status       = $_POST['status'] ?? '';

    // Validasi
    if (empty($merk) || empty($serialNumber) || empty($processor)) {
        die("Merk dan Serial Number wajib diisi.");
    }

    // Siapkan dan jalankan prepared statement untuk insert ke barang_notebook_laptop
    $stmt = $koneksi->prepare("INSERT INTO barang_notebook_laptop 
        (merk, serial_number, processor, penyimpanan, monitor, baterai, vga, ram, tgl_beli, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    if ($stmt) {
        $stmt->bind_param(
            "ssssssssss",
            $merk,
            $serialNumber,
            $processor,
            $penyimpanan,
            $monitor,
            $baterai,
            $vga,
            $ram,
            $tgl_beli,
            $status
        );

        if ($stmt->execute()) {
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