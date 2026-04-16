<?php
include "../../koneksi.php";

if (isset($_POST['username'])) {
    $username = $_POST['username'];

    $stmt = $koneksi->prepare("SELECT id FROM akun_akses WHERE username = ? LIMIT 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        echo "exists";
    } else {
        echo "available";
    }

    $stmt->close();
    $koneksi->close();
}
?>
