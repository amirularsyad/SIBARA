<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <style>
    body {
      background-color: rgb(2, 77, 207);
    }
    </style>
</head>
<body>
    
</body>
</html>
<?php
session_start();
error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT);

// ========================================
// proses_kirim_email.php
// ========================================
require_once '../koneksi.php'; // koneksi utama

// --- pastikan koneksi aktif ---
if (!isset($koneksi) || !$koneksi) {
    // fallback jika koneksi gagal
    $host = getenv('DB_HOST') ?: 'localhost';
    $user2 = getenv('DB_USER') ?: 'root';
    $user = getenv('DB_USER') ?: 'bojongsari';
    $pass2 = getenv('DB_PASS') ?: '';
    $pass = getenv('DB_PASS') ?: 'semprul123!@';
    $db   = getenv('DB_NAME') ?: 'db_surat_ba';
    // $koneksi = new mysqli($host, $user, $pass, $db);
    $koneksi = new mysqli($host, $user2, $pass2, $db);
    if ($koneksi->connect_error) {
        die("Gagal koneksi database: " . $koneksi->connect_error);
    }
}

require_once '../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// ===============================
// KONFIGURASI EMAIL GMAIL
// ===============================
define('MAIL_USERNAME', 'mis_sibara@msalgroup.com');
// define('MAIL_USERNAME', 'sharkingfisher12@gmail.com');
define('MAIL_APP_PASSWORD', 'Msaljkt@88'); 
// define('MAIL_APP_PASSWORD', 'apvqbmzfhrcnabmv');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo "Method not allowed.";
    exit;
}
//Redirect halaman
$redirect = isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER'])
                ? $_SERVER['HTTP_REFERER']
                : '';

// ===============================
// AMBIL DATA POST
// ===============================
$row_id       = isset($_POST['row_id_email']) ? $_POST['row_id_email'] : '';
$aktorListStr = isset($_POST['aktorEmailHidden']) ? $_POST['aktorEmailHidden'] : '';
$nomor_ba     = isset($_POST['data_nomor']) ? $_POST['data_nomor'] : '';
$tanggal      = isset($_POST['data_tanggal']) ? $_POST['data_tanggal'] : '';
$bulanRomawi  = isset($_POST['data_bulan_romawi']) ? $_POST['data_bulan_romawi'] : '';
$tahun        = isset($_POST['data_tahun']) ? $_POST['data_tahun'] : '';
$jenis_ba     = isset($_POST['data_jenis_ba']) ? $_POST['data_jenis_ba'] : '';
$permintaan   = isset($_POST['data_permintaan']) ? $_POST['data_permintaan'] : '';
$namaPeminta   = isset($_POST['data_nama_peminta']) ? $_POST['data_nama_peminta'] : '';

$jenis_ba2 = ""; // inisialisasi default

if ($jenis_ba === "notebook") {
    $jenis_ba2 = "Serah terima Notebook Inventaris";
} elseif ($jenis_ba === "kerusakan") {
    $jenis_ba2 = "Kerusakan";
} elseif ($jenis_ba === "mutasi") {
    $jenis_ba2 = "Mutasi Asset Inventaris";
} elseif ($jenis_ba === "pengembalian") {
    $jenis_ba2 = "Pengembalian Asset Inventaris";
} elseif ($jenis_ba === "st_asset") {
    $jenis_ba2 = "Serah Terima Asset Inventaris";
} elseif ($jenis_ba === "pemutihan") {
    $jenis_ba2 = "Pemutihan Asset";
} else {
    $jenis_ba2 = $jenis_ba; // kalau bukan notebook, tetap isi sesuai nilai aslinya
}


// ===============================
// PROSES LIST AKTOR
// ===============================
$aktorList = array_filter(array_map('trim', explode(',', $aktorListStr)));
if (empty($aktorList)) {
    $_SESSION['success'] = false;
    $_SESSION['message'] = "Tidak ada aktor untuk dikirimi email.";
    header("Location: $redirect");
    exit;
}
// ===============================
// Format tanggal Indonesia
// ===============================
if (!empty($tanggal)) {

    $bulanIndo = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];

    $timestamp = strtotime($tanggal);
    $day = date('d', $timestamp);
    $month = (int)date('m', $timestamp);
    $year = date('Y', $timestamp);

    // hasil akhir
    $tanggalIndo = $day . ' ' . $bulanIndo[$month] . ' ' . $year;
} else {
    $tanggalIndo = '';
}


// Ambil akun_akses berdasarkan nama
$escapedNames = array_map(function($n) use ($koneksi) {
    return "'" . $koneksi->real_escape_string($n) . "'";
}, $aktorList);
$inList = implode(',', $escapedNames);
$sql = "SELECT * FROM akun_akses WHERE nama IN ($inList)";
$result = $koneksi->query($sql);

if (!$result) {
    $_SESSION['success'] = false;
    $_SESSION['message'] = "Query gagal: " . $koneksi->error;
    header("Location: $redirect");
    exit;
}

$aktorEmailData = [];
while ($row = $result->fetch_assoc()) {
    $aktorEmailData[] = $row;
}
$result->free();

// ===============================
// CEK JIKA TIDAK ADA DATA AKUN DITEMUKAN
// ===============================
if (empty($aktorEmailData)) {
    // Buat daftar nama yang tidak ditemukan
    $notFoundList = implode(', ', $aktorList);

    $_SESSION['success'] = false;
    $_SESSION['message'] = "{$notFoundList} belum membuat akun di sistem.";
        if ($namaPeminta == "AutoMailer") {
            echo "
                <form id='autoForward' method='POST' action='proses_email_approval.php'>
                    <input type='hidden' name='jenis' value='{$jenis_ba}'>
                    <input type='hidden' name='id' value='{$row_id}'>
                </form>
                <script>
                    document.getElementById('autoForward').submit();
                </script>
            ";
            exit;
        }

        else if ($namaPeminta == "HomeMailer"){
            $_SESSION['success'] = true;
            // $_SESSION['message'] = "Berhasil menyimpan tanda tangan. (HM err)";
            $_SESSION['message'] = "Berhasil menyimpan tanda tangan.";
            header("Location: approval.php?jenis_ba=" . urlencode($jenis_ba));
            exit;
        }
    header("Location: $redirect");
    exit;
}
// ===============================
// CEK JIKA ADA PENGGUNA TANPA EMAIL
// ===============================
$aktorTanpaEmail = [];

foreach ($aktorEmailData as $aktor) {
    if (empty(trim(isset($aktor['email']) ? $aktor['email'] : ''))) {
        $aktorTanpaEmail[] = $aktor['nama'];
    }
}

if (!empty($aktorTanpaEmail)) {
    $noEmailList = implode(', ', $aktorTanpaEmail);

    $_SESSION['success'] = false;
    $_SESSION['message'] = "{$noEmailList} belum memiliki alamat email di sistem.";
        if ($namaPeminta == "AutoMailer") {
            echo "
                <form id='autoForward' method='POST' action='proses_email_approval.php'>
                    <input type='hidden' name='jenis' value='{$jenis_ba}'>
                    <input type='hidden' name='id' value='{$row_id}'>
                </form>
                <script>
                    document.getElementById('autoForward').submit();
                </script>
            ";
            exit;
        }

        else if ($namaPeminta == "HomeMailer"){
            $_SESSION['success'] = true;
            $_SESSION['message'] = "Berhasil menyimpan tanda tangan. (HM err)";
            header("Location: approval.php?jenis_ba=" . urlencode($jenis_ba));
            exit;
        }
    header("Location: $redirect");
    exit;
}

// ===============================
// KONFIGURASI ISI
// ===============================

// Tentukan judul email berdasarkan jenis permintaan
if ($permintaan === "approval") {
    $emailTitle = "Permintaan Persetujuan Surat BA";
} elseif ($permintaan === "edit") {
    $emailTitle = "Permintaan Persetujuan Edit BA";
} elseif ($permintaan === "delete") {
    $emailTitle = "Permintaan Persetujuan Hapus BA";
} else {
    $emailTitle = "Notifikasi Surat BA";
}

// PREHEADER TEXT berdasarkan jenis permintaan
if ($permintaan === "approval") {
    $preheaderText = "Notifikasi permohonan persetujuan berita acara {$jenis_ba2}";
} elseif ($permintaan === "edit") {
    $preheaderText = "Notifikasi permohonan persetujuan edit berita acara {$jenis_ba2}";
} elseif ($permintaan === "delete") {
    $preheaderText = "Notifikasi permohonan persetujuan hapus berita acara {$jenis_ba2}";
} else {
    $preheaderText = "";
}


if ($permintaan === "approval") {
    $kataPembuka = "Ada Berita Acara yang menunggu persetujuan Anda";
} elseif ($permintaan === "edit") {
    $kataPembuka = "Ada permintaan edit Berita Acara yang menunggu persetujuan Anda";
} elseif ($permintaan === "delete") {
    $kataPembuka = "Ada permintaan hapus Berita Acara yang menunggu persetujuan Anda";
} else {
    $kataPembuka = "";
}

if ($permintaan === "approval") {
    $subjectEmail = "Persetujuan Berita Acara {$jenis_ba2}";
} elseif ($permintaan === "edit") {
    $subjectEmail = "Persetujuan Edit Berita Acara {$jenis_ba2}";
} elseif ($permintaan === "delete") {
    $subjectEmail = "Persetujuan Hapus Berita Acara {$jenis_ba2}";
} else {
    $subjectEmail = "";
}

// ===============================
// KONFIGURASI EMAIL
// ===============================
$mail = new PHPMailer(true);
$successList = [];
$failedList  = [];

try {
    $mail->isSMTP();
    $mail->Host       = 'mail.msalgroup.com';
    // $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = MAIL_USERNAME;
    $mail->Password   = MAIL_APP_PASSWORD;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;
    $mail->setFrom(MAIL_USERNAME, 'SIBARA Notification');
    $mail->isHTML(true);

    foreach ($aktorEmailData as $aktor) {
        $namaAktor     = isset($aktor['nama']) ? $aktor['nama'] : '';
        $emailAktor    = isset($aktor['email']) ? $aktor['email'] : '';
        $usernameAktor = isset($aktor['username']) ? $aktor['username'] : '';


        if (empty($emailAktor)) {
            $failedList[] = "{$namaAktor} (tanpa email)";
            continue;
        }

        // buat token unik (berlaku 14 hari)
        $token = bin2hex(openssl_random_pseudo_bytes(32));

        //PHP 5.6
        // if (function_exists('openssl_random_pseudo_bytes')) {
        //     $token = bin2hex(openssl_random_pseudo_bytes(32));
        // } else {
        //     $token = bin2hex(mcrypt_create_iv(32, MCRYPT_DEV_URANDOM));
        // }

        $expireAt = date('Y-m-d H:i:s', strtotime('+3 days'));

        // hapus token lama jika ada yang sama (username + jenis_ba + id_ba)
        $stmtDel = $koneksi->prepare("
            DELETE FROM login_tokens 
            WHERE username = ? AND jenis_ba = ? AND id_ba = ?
        ");
        $stmtDel->bind_param('sss', $usernameAktor, $jenis_ba, $row_id);
        $stmtDel->execute();
        $stmtDel->close();

        // simpan token baru ke DB
        $stmt = $koneksi->prepare("
            INSERT INTO login_tokens (username, token, expire_at, jenis_ba, id_ba, used)
            VALUES (?, ?, ?, ?, ?, 0 )
        ");
        $stmt->bind_param('sssss', $usernameAktor, $token, $expireAt, $jenis_ba, $row_id);
        $stmt->execute();
        $stmt->close();


        // buat link auto login (multi-use)
        if ($permintaan === "approval") {
        $linkLogin1 = "http://192.168.1.231/sibara/auto_login.php?token={$token}&id={$row_id}&jenis_ba={$jenis_ba}&permintaan={$permintaan}";
        $linkLogin2 = "http://localhost/Programming/sibara/auto_login.php?token={$token}&id={$row_id}&jenis_ba={$jenis_ba}&permintaan={$permintaan}";
        } elseif ($permintaan === "edit") {
        $linkLogin1 = "http://192.168.1.231/sibara/auto_login.php?token={$token}&id={$row_id}&jenis_ba={$jenis_ba}&permintaan={$permintaan}&nama_peminta={$namaPeminta}";
        $linkLogin2 = "http://localhost/Programming/sibara/auto_login.php?token={$token}&id={$row_id}&jenis_ba={$jenis_ba}&permintaan={$permintaan}&nama_peminta={$namaPeminta}";
        } elseif ($permintaan === "delete") {
        $linkLogin1 = "http://192.168.1.231/sibara/auto_login.php?token={$token}&id={$row_id}&jenis_ba={$jenis_ba}&permintaan={$permintaan}&nama_peminta={$namaPeminta}";
        $linkLogin2 = "http://localhost/Programming/sibara/auto_login.php?token={$token}&id={$row_id}&jenis_ba={$jenis_ba}&permintaan={$permintaan}&nama_peminta={$namaPeminta}";
        } else {
        $linkLogin1 = "http://192.168.1.231/sibara/login_registrasi.php";
        $linkLogin2 = "http://localhost/Programming/sibara/login_registrasi.php";
        }
        // $linkLogin = $linkLogin1;
        $linkLogin = $linkLogin2;
        // isi email
        $mail->clearAddresses();
        $mail->addAddress($emailAktor, $namaAktor);
        $mail->Subject = "{$subjectEmail}";
$mail->Body = <<<HTML
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>{$emailTitle}</title>

<style>
    /* ---------- Email-safe reset ---------- */
    html,body {
        margin:0;
        padding:0;
        height:100% !important;
        width:100% !important;
    }
    img {
        border:0;
        outline:none;
        text-decoration:none;
        display:block;
    }
    a {
        color:inherit;
        text-decoration: none;
    }
    table {
        border-collapse: collapse;
        mso-table-lspace:0pt;
        mso-table-rspace:0pt;
    }
    td {
        font-family: 'Helvetica Neue', Arial, sans-serif;
    }

    /* ---------- Responsive ---------- */
    @media only screen and (max-width:600px) {
    .container {
        width:100% !important;
    }
    .stack-column, .stack-column-center {
        display:block !important;
        width:100% !important;
        max-width:100% !important;
    }
    .stack-column-center {
        text-align:center !important;
    }
    .mobile-hidden {
        display:none !important;
    }
    .mobile-center {
        text-align:center !important;
    }
    .fluid-img {
        width:100% !important;
        height:auto !important;
        max-width:100% !important;
    }
    }
    @media (max-width: 450px) {
    .font-custom{
        font-size: 10px !important;
    }
    }
</style>

</head>
<body style="margin:0; padding:0; background-color:#f4f4f5;">

  <!-- PREHEADER (short preview text) -->
  <div style="display:none; max-height:0px; overflow:hidden; color:#ffffff; line-height:1px; font-size:1px;">
    {$preheaderText}
  </div>

  <!-- MAIN WRAPPER -->
<table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background-color:#f4f4f5;">
    <tr>
        <td align="center">

        <!-- CENTERED CONTAINER (max-width) -->
        <table role="presentation" cellpadding="0" cellspacing="0" width="600" class="container" style="width:600px; max-width:600px;">
            <!-- SPACING TOP -->
            <tr>
                <td height="28" style="font-size:0; line-height:0;">&nbsp;</td>
            </tr>

        <!-- HEADER -->
            <tr>
                <td align="center" style="padding: 0 16px;">
                    <table role="presentation" cellpadding="0" cellspacing="0" width="100%">
                        <tr>
                            <td align="right" class="mobile-hidden" style="vertical-align:middle; color:#888888; font-size:13px;">
                                <span style="font-family:Arial, sans-serif;">Tanggal: {$tanggal}</span>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>

          <!-- HERO / CONTENT CARD -->
          <tr>
            <td align="center" style="padding: 20px 16px;">
              <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background-color:#ffffff; border-radius:8px; overflow:hidden;">
                <tr>
                  <td>

                    <!-- Title -->
                    <div class="" style="background-color:#1d4ed8; padding: 24px;">
                        <h1 class="fallback-font" style="margin:0; font-size:22px; line-height:28px; font-weight:700; color:white; font-family:'Helvetica Neue',Arial,sans-serif;">
                        Notifikasi SIBARA
                        </h1>
                    </div>


                    <!-- Lead paragraph -->
                    <p style="padding:18px 24px; margin:0; font-size:15px; line-height:22px; color:#555555;">
                        Dear {$namaAktor}, <br>{$kataPembuka}
                    </p>
                    <table style="margin:24px;">
                        <tbody>
                            <tr>
                                <td>
                                    <p style="padding:0; margin:0; margin-bottom: 10px; font-size:14px; font-weight: 700; line-height:20px; color:#555555;">
                                        Jenis Dokumen
                                    </p>
                                </td>
                                <td>
                                    <p style="padding:0; margin:0; margin-bottom: 10px; font-size:14px; font-weight: 700; line-height:20px; color:#555555;">
                                        : Berita Acara {$jenis_ba2}
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <p style="padding:0; margin:0; margin-bottom: 10px; font-size:14px; font-weight: 700; line-height:20px; color:#555555;">
                                        Nomor Dokumen
                                    </p>
                                </td>
                                <td>
                                    <p style="padding:0; margin:0; margin-bottom: 10px; font-size:14px; font-weight: 700; line-height:20px; color:#555555;">
                                        : {$nomor_ba} Periode {$bulanRomawi}/{$tahun}
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <p style="padding:0; margin:0; font-size:14px; font-weight: 700; line-height:20px; color:#555555;">
                                        Tanggal Dokumen
                                    </p>
                                </td>
                                <td>
                                    <p style="padding:0; margin:0; font-size:14px; font-weight: 700; line-height:20px; color:#555555;">
                                        : {$tanggalIndo}
                                    </p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <!-- CTA Button (Outlook-ready VML + fallback) -->
                    <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom:18px;">
                      <tr>
                        <td align="center">
                            <a href="{$linkLogin}"
                                style="display:inline-block; padding:12px 22px; background-color:#1d4ed8; color:#ffffff; 
                                text-decoration:none; font-weight:600; border-radius:6px; font-size:16px; 
                                font-family:'Helvetica Neue',Arial,sans-serif;">
                            Buka Halaman Approval
                            </a>
                        </td>
                      </tr>
                    </table>

                    <!-- Two-column section (stacks on mobile) -->
                    <table role="presentation" cellpadding="0" cellspacing="0" width="100%" >
                        <tr>
                            <!-- left column -->
                            <td style="padding:0 24px 16px 24px;">
                                <p style="margin:0; font-size:14px; color:#555555;">Terima kasih,</p>
                                <p style="margin:0; font-size:14px; color:#555555;">MIS</p>
                            </td>
                        </tr>
                    </table>

                    <!-- Divider -->
                    <hr style="border:none; border-top:1px solid #eeeeee; margin:0;" />

                    <!-- Footer note inside card -->
                    <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="padding:8px 24px;">
                    <tr>
                        <td align="left" style="padding: 18px 24px;font-size:14px; color:#888888; line-height:18px; font-weight:600;">
                            <span class="font-custom">Copyright © 2025 </span>
                            <span class="font-custom" style="color:#1d4ed8;">MIS MSAL.</span>
                            <span class="font-custom"> All rights reserved.</span>
                        </td>
                    </tr>
                    </table>



                    </td>
                    </tr>
                </table>
                </td>
            </tr>

        </table> <!-- end centered container -->

        </td>
    </tr>
</table>
</body>
</html>
HTML;
;

        try {
            $mail->send();
            $successList[] = "{$namaAktor} ({$emailAktor})";
        } catch (Exception $e) {
            $failedList[] = "{$namaAktor} ({$emailAktor}) - {$mail->ErrorInfo}";
        }
    }

    // ===============================
    // LAPORAN HASIL
    // ===============================
    if (!empty($successList) && empty($failedList)) {
        $_SESSION['success'] = true;
        $_SESSION['message'] = "Sukses mengirim email.";

        if ($namaPeminta == "AutoMailer") {
            echo "
                <form id='autoForward' method='POST' action='proses_email_approval.php'>
                    <input type='hidden' name='jenis' value='{$jenis_ba}'>
                    <input type='hidden' name='id' value='{$row_id}'>
                </form>
                <script>
                    document.getElementById('autoForward').submit();
                </script>
            ";
            exit;
        }
        
        else if ($namaPeminta == "HomeMailer"){
            $_SESSION['success'] = true;
            $_SESSION['message'] = "Berhasil menyimpan tanda tangan.";
            header("Location: approval.php?jenis_ba=" . urlencode($jenis_ba));
            exit;
        }
    } elseif (!empty($successList)) {
        $_SESSION['success'] = false;
        $_SESSION['message'] = "Sebagian berhasil dikirim:<br>" .
            implode('<br>', $successList) .
            "<br><br>Gagal ke:<br>" .
            implode('<br>', $failedList);
        if ($namaPeminta == "AutoMailer") {
            echo "
                <form id='autoForward' method='POST' action='proses_email_approval.php'>
                    <input type='hidden' name='jenis' value='{$jenis_ba}'>
                    <input type='hidden' name='id' value='{$row_id}'>
                </form>
                <script>
                    document.getElementById('autoForward').submit();
                </script>
            ";
            exit;
        }

        else if ($namaPeminta == "HomeMailer"){
            $_SESSION['success'] = true;
            $_SESSION['message'] = "Berhasil menyimpan tanda tangan. (HM err)";
            header("Location: approval.php?jenis_ba=" . urlencode($jenis_ba));
            exit;
        }
    } else {
        $_SESSION['success'] = false;
        $_SESSION['message'] = "Gagal mengirim email ke penerima.";
        if ($namaPeminta == "AutoMailer") {
            echo "
                <form id='autoForward' method='POST' action='proses_email_approval.php'>
                    <input type='hidden' name='jenis' value='{$jenis_ba}'>
                    <input type='hidden' name='id' value='{$row_id}'>
                </form>
                <script>
                    document.getElementById('autoForward').submit();
                </script>
            ";
            exit;
        }
        else if ($namaPeminta == "HomeMailer"){
            $_SESSION['success'] = true;
            $_SESSION['message'] = "Berhasil menyimpan tanda tangan. (HM err)";
            header("Location: approval.php?jenis_ba=" . urlencode($jenis_ba));
            exit;
        }
    }

} catch (Exception $e) {
    $_SESSION['success'] = false;
    $_SESSION['message'] = "Mailer error: " . $e->getMessage();
}

// ===============================
// REDIRECT KEMBALI
// ===============================
header("Location: $redirect");
exit;
?>
