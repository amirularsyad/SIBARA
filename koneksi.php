<?php
// koneksi.php

// Konfigurasi database (langsung ditulis di file ini)
$host   = "localhost";   // ganti sesuai host DB
$host2   = "192.168.1.231";   //  ganti sesuai host DB
$user   = "root";        // ganti sesuai user DB
$user2   = "bojongsari";        //  ganti sesuai user DB
$pass   = "";            // ganti sesuai password DB
$pass2   = "semprul123!@";            //  ganti sesuai password DB
$dbname = "db_surat_ba";      // ganti sesuai nama database
$dbname2 = "dev_masis_sibara";      //  ganti sesuai nama database
$dbname3 = "tb";      //  ganti sesuai nama database 

$koneksi = new mysqli($host, $user, $pass, $dbname);
// $koneksi = new mysqli($host2, $user2, $pass2, $dbname);

$koneksi2 = new mysqli($host2, $user2, $pass2, $dbname2);
// $koneksi2 = new mysqli($host, $user, $pass, $dbname3);

// $koneksi3 = new mysqli($host, $user, $pass, $dbname3);

// Cek koneksi
if ($koneksi->connect_error) {
    die("Koneksi gagal: " . $koneksi->connect_error);
}
if ($koneksi2->connect_error) {
    die("Koneksi gagal: " . $koneksi2->connect_error);
}

?>
