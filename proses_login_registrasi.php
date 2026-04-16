<?php
session_start();
require_once 'koneksi.php';

// if (isset($_POST['registrasi'])) {
//     $username = $_POST['username'];
//     $password = $_POST['passwords'];
//     $nik      = $_POST['nik'];
//     $pt       = $_POST['pt'];

//     // Ambil nama berdasarkan nik dari data_karyawan
//     $stmt = $koneksi->prepare("SELECT nama FROM data_karyawan WHERE nik = ?");
//     $stmt->bind_param("i", $nik);
//     $stmt->execute();
//     $resultNama = $stmt->get_result();

//     if ($resultNama->num_rows === 0) {
//         $_SESSION['registrasi_error'] = 'NIK tidak ditemukan dalam data karyawan!';
//         $_SESSION['active_form'] = 'registrasi';
//         header("Location: login_registrasi.php");
//         exit();
//     }

//     $row = $resultNama->fetch_assoc();
//     $nama_karyawan = $row['nama'];

//     // Cek apakah username sudah digunakan
//     $checkUsername = $koneksi->prepare("SELECT id FROM akun_akses WHERE username = ?");
//     $checkUsername->bind_param("s", $username);
//     $checkUsername->execute();
//     $resultUsername = $checkUsername->get_result();

//     // Cek apakah NIK sudah digunakan
//     $checkNik = $koneksi->prepare("SELECT id FROM akun_akses WHERE nik = ?");
//     $checkNik->bind_param("i", $nik);
//     $checkNik->execute();
//     $resultNik = $checkNik->get_result();

//     if ($resultUsername->num_rows > 0) {
//         $_SESSION['registrasi_error'] = 'Username sudah digunakan!';
//         $_SESSION['active_form'] = 'registrasi';
//     } elseif ($resultNik->num_rows > 0) {
//         $_SESSION['registrasi_error'] = 'NIK sudah terdaftar untuk akun lain!';
//         $_SESSION['active_form'] = 'registrasi';
//     } else {
//         $hak_akses = 'User';

//         // Simpan akun baru
//         $insert = $koneksi->prepare("INSERT INTO akun_akses (username, password, nik, pt, nama, hak_akses) VALUES (?, ?, ?, ?, ?, ?)");
//         $insert->bind_param("ssisss", $username, $password, $nik, $pt, $nama_karyawan, $hak_akses);
//         $insert->execute();

//         $_SESSION['registrasi_success'] = 'Akun berhasil dibuat. Silakan login.';
//     }

//     header("Location: login_registrasi.php");
//     exit();
// }

// ================= LOGIN =====================
if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['passwords'];

    require_once 'koneksi.php';

    $stmt = $koneksi->prepare("SELECT * FROM akun_akses WHERE username = ? AND password = ?");
    $stmt->bind_param("ss", $username, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // Simpan data penting ke session
        $_SESSION['logged_in'] = true;
        $_SESSION['username'] = $user['username'];
        $_SESSION['nama'] = $user['nama'];
        $_SESSION['hak_akses'] = $user['hak_akses'];
        $_SESSION['nik'] = $user['nik'];
        $ptList = array_map('trim', explode(',', $user['pt']));
        $_SESSION['pt'] = $ptList;
        $_SESSION['manajemen_akun_akses'] = $user['manajemen_akun_akses'];
        $_SESSION['warna_menu'] = $user['warna_menu'];
        // $_SESSION['berita_acara_kerusakan_akses'] = $user['berita_acara_kerusakan_akses'];

        $_SESSION['login_success'] = 'Login berhasil. Selamat datang, ' . $user['nama'] . '!';
        // Redirect berdasarkan hak akses
        if ($user['hak_akses'] === 'User') {
            header("Location: personal/approval.php");
        } elseif ($user['hak_akses'] === 'Admin') {
            header("Location: index.php");
        } elseif ($user['hak_akses'] === 'Super Admin') {
            header("Location: master/data_akun/tabel.php");
        } else {
            // Jika hak akses tidak dikenali, kembalikan ke login
            $_SESSION['login_error'] = 'Hak akses tidak dikenali.';
            $_SESSION['active_form'] = 'login';
            header("Location: login_registrasi.php");
        }
        exit();
    } else {
        $_SESSION['login_error'] = 'Username atau password salah!';
        $_SESSION['active_form'] = 'login';
        header("Location: login_registrasi.php");
        exit();
    }
}

?>
