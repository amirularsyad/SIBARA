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
<?php
require_once "../koneksi.php";
session_start();
if (isset($_SESSION['success'])) {
    $id           = isset($_POST['id']) ? $_POST['id'] : '';
    $jenis        = isset($_POST['jenis']) ? $_POST['jenis'] : '';
    // echo '<pre>';
    // echo "==== \$_POST ====\n";
    // print_r($_POST);
    // echo '</pre>';

    // exit;
    // Hapus flag success supaya tidak looping
    unset($_SESSION['success']);

    // Redirect kembali ke halaman asal
    $redirect = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'approval.php';

    echo "
        <script>
            alert('Berhasil menyimpan tanda tangan');
            window.location.href = 'email_access_approval.php?jenis=$jenis&id=$id';
        </script>
    ";
    exit;
}

// Validasi PNG agar SELALU 500x200 TANPA extension (tanpa GD/Imagick)
// Flow data tetap sama: binaryData tidak diubah, hanya dicek.
function getPngDimensionsFromBinary($binaryData) {
    if (empty($binaryData) || strlen($binaryData) < 24) {
        return false;
    }

    // Signature PNG 8-byte
    $pngSignature = "\x89PNG\r\n\x1a\n";
    if (substr($binaryData, 0, 8) !== $pngSignature) {
        return false; // bukan PNG
    }

    // Setelah signature, harus ada chunk IHDR
    // Offset:
    // 0-7   = PNG signature
    // 8-11  = length IHDR (biasanya 13)
    // 12-15 = chunk type "IHDR"
    // 16-19 = width  (big-endian)
    // 20-23 = height (big-endian)
    $chunkType = substr($binaryData, 12, 4);
    if ($chunkType !== 'IHDR') {
        return false;
    }

    $widthData  = substr($binaryData, 16, 4);
    $heightData = substr($binaryData, 20, 4);

    if (strlen($widthData) !== 4 || strlen($heightData) !== 4) {
        return false;
    }

    $width  = unpack('N', $widthData)[1];
    $height = unpack('N', $heightData)[1];

    if ($width <= 0 || $height <= 0) {
        return false;
    }

    return ['width' => $width, 'height' => $height];
}

function isPng500x200($binaryData) {
    $dim = getPngDimensionsFromBinary($binaryData);
    if ($dim === false) {
        return false;
    }

    return ($dim['width'] === 500 && $dim['height'] === 200);
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // DEBUG: LIHAT SEMUA DATA YANG DIKIRIM DARI email_access_approval.php
    // echo '<pre>';
    // echo "==== \$_POST ====\n";
    // print_r($_POST);
    // echo '</pre>';

    // exit;

    // ambil halaman sebelumnya
    $redirect = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'approval.php';

    $mode = isset($_POST['mode']) ? $_POST['mode'] : 'signature';
    // Ambil semua data dari POST
    $id           = isset($_POST['id']) ? $_POST['id'] : '';
    $jenis        = isset($_POST['jenis']) ? $_POST['jenis'] : '';
    $approvalCol  = isset($_POST['approvalCol']) ? $_POST['approvalCol'] : '';
    $autographCol = isset($_POST['autographCol']) ? $_POST['autographCol'] : '';
    $tanggalCol   = isset($_POST['tanggalCol']) ? $_POST['tanggalCol'] : '';
    $nama_user    = isset($_SESSION['nama']) ? $_SESSION['nama'] : '';

    $row_id       = isset($_POST['row_id']) ? $_POST['row_id'] : '';
    $aktorListStr = isset($_POST['aktorListStr']) ? $_POST['aktorListStr'] : '';
    $nomor_ba     = isset($_POST['nomor_ba']) ? $_POST['nomor_ba'] : '';
    $tanggal      = isset($_POST['tanggal']) ? $_POST['tanggal'] : '';
    $bulanRomawi  = isset($_POST['bulanRomawi']) ? $_POST['bulanRomawi'] : '';
    $tahun        = isset($_POST['tahun']) ? $_POST['tahun'] : '';
    $jenis_ba     = isset($_POST['jenis_ba']) ? $_POST['jenis_ba'] : '';
    $permintaan   = isset($_POST['permintaan']) ? $_POST['permintaan'] : '';
    $namaPeminta  = isset($_POST['namaPeminta']) ? $_POST['namaPeminta'] : '';

    // Validasi dasar
    if (empty($id) || empty($jenis) || empty($autographCol) || empty($approvalCol) || empty($tanggalCol)) {
        echo "<script>alert('Data tidak lengkap.'); window.history.back();</script>";
        exit;
    }

    // Validasi file tanda tangan (hanya jika mode signature)
    if ($mode === 'signature') {
        if (empty($_POST['signature_base64'])) {
            echo "<script>alert('Tanda tangan tidak ditemukan.'); window.history.back();</script>";
            exit;
        }

        $base64 = $_POST['signature_base64'];

        // Hilangkan prefix "data:image/png;base64,"
        $base64 = preg_replace('#^data:image/\w+;base64,#i', '', $base64);

        $binaryData = base64_decode($base64);

        if (!$binaryData || strlen($binaryData) < 100) {
            echo "<script>alert('Tanda tangan tidak valid.'); window.history.back();</script>";
            exit;
        }

        // VALIDASI WAJIB (tanpa extension): harus PNG 500x200
        if (!isPng500x200($binaryData)) {
            echo "<script>alert('Ukuran tanda tangan harus PNG 500x200. Silakan ulangi tanda tangan.'); window.history.back();</script>";
            exit;
        }
    }



    // $binaryData = file_get_contents($_FILES['signature']['tmp_name']);
    // if (strlen($binaryData) < 100) {
    //     echo "<script>alert('Tanda tangan tidak valid.'); window.history.back();</script>";
    //     exit;
    // }

    // Tentukan tabel berdasarkan jenis BA
    $jenis_lc = strtolower(trim($jenis));
    switch ($jenis_lc) {
        case 'kerusakan':
            $table = 'berita_acara_kerusakan';
            break;
        case 'pengembalian':
            $table = 'berita_acara_pengembalian_v2';
            break;
        case 'notebook':
            $table = 'ba_serah_terima_notebook';
            break;
        case 'mutasi':
            $table = 'berita_acara_mutasi';
            break;
        case 'st_asset':
            $table = 'ba_serah_terima_asset';
            break;
        case 'pemutihan':
            $table = 'berita_acara_pemutihan';
            break;
        default:
            echo "<script>alert('Jenis BA tidak dikenal.'); window.history.back();</script>";
            exit;
    }

    // Validasi whitelist kolom agar nama kolom tidak bisa sembarang
    $allowedCols = array(
        'kerusakan' => array(
            'approval'  => array('approval_1', 'approval_2', 'approval_3', 'approval_4', 'approval_5'),
            'autograph' => array('autograph_1', 'autograph_2', 'autograph_3', 'autograph_4', 'autograph_5'),
            'tanggal'   => array('tanggal_approve_1', 'tanggal_approve_2', 'tanggal_approve_3', 'tanggal_approve_4', 'tanggal_approve_5')
        ),
        'pengembalian' => array(
            'approval'  => array('approval_1', 'approval_2', 'approval_3'),
            'autograph' => array('autograph_1', 'autograph_2', 'autograph_3'),
            'tanggal'   => array('tanggal_approve_1', 'tanggal_approve_2', 'tanggal_approve_3')
        ),
        'notebook' => array(
            'approval'  => array('approval_1', 'approval_2', 'approval_3', 'approval_4'),
            'autograph' => array('autograph_1', 'autograph_2', 'autograph_3', 'autograph_4'),
            'tanggal'   => array('tanggal_approve_1', 'tanggal_approve_2', 'tanggal_approve_3', 'tanggal_approve_4')
        ),
        'mutasi' => array(
            'approval'  => array('approval_1', 'approval_2', 'approval_3', 'approval_4', 'approval_5', 'approval_6', 'approval_7', 'approval_8', 'approval_9', 'approval_10', 'approval_11'),
            'autograph' => array('autograph_1', 'autograph_2', 'autograph_3', 'autograph_4', 'autograph_5', 'autograph_6', 'autograph_7', 'autograph_8', 'autograph_9', 'autograph_10', 'autograph_11'),
            'tanggal'   => array('tanggal_approve_1', 'tanggal_approve_2', 'tanggal_approve_3', 'tanggal_approve_4', 'tanggal_approve_5', 'tanggal_approve_6', 'tanggal_approve_7', 'tanggal_approve_8', 'tanggal_approve_9', 'tanggal_approve_10', 'tanggal_approve_11')
        ),
        'st_asset' => array(
            'approval'  => array('approval_1', 'approval_2', 'approval_3', 'approval_4'),
            'autograph' => array('autograph_1', 'autograph_2', 'autograph_3', 'autograph_4'),
            'tanggal'   => array('tanggal_approve_1', 'tanggal_approve_2', 'tanggal_approve_3', 'tanggal_approve_4')
        ),
        'pemutihan' => array(
            'approval'  => array('approval_1', 'approval_2', 'approval_3', 'approval_4', 'approval_5', 'approval_6', 'approval_7', 'approval_8', 'approval_9', 'approval_10', 'approval_11'),
            'autograph' => array('autograph_1', 'autograph_2', 'autograph_3', 'autograph_4', 'autograph_5', 'autograph_6', 'autograph_7', 'autograph_8', 'autograph_9', 'autograph_10', 'autograph_11'),
            'tanggal'   => array('tanggal_approve_1', 'tanggal_approve_2', 'tanggal_approve_3', 'tanggal_approve_4', 'tanggal_approve_5', 'tanggal_approve_6', 'tanggal_approve_7', 'tanggal_approve_8', 'tanggal_approve_9', 'tanggal_approve_10', 'tanggal_approve_11')
        )
    );

    if (
        !isset($allowedCols[$jenis_lc]) ||
        !in_array($approvalCol, $allowedCols[$jenis_lc]['approval'], true) ||
        !in_array($autographCol, $allowedCols[$jenis_lc]['autograph'], true) ||
        !in_array($tanggalCol, $allowedCols[$jenis_lc]['tanggal'], true)
    ) {
        echo "<script>alert('Kolom approval/autograph/tanggal tidak valid.'); window.history.back();</script>";
        exit;
    }

    if ($mode === 'signature') {
        // Mode dengan tanda tangan
        $sql = "UPDATE $table 
                SET $autographCol = ?, 
                    $approvalCol = 1, 
                    $tanggalCol = NOW()
                WHERE id = ?";

        $stmt = $koneksi->prepare($sql);
        if (!$stmt) {
            echo "<script>alert('Gagal prepare statement: " . $koneksi->error . "'); window.history.back();</script>";
            exit;
        }

        $null = NULL;
        $stmt->bind_param("bi", $null, $id);
        $stmt->send_long_data(0, $binaryData);
    } else {
        // Mode APPROVE INSTANT (tanpa tanda tangan, ambil autograph dari akun_akses)
        $sqlGetSign = "SELECT autograph FROM akun_akses WHERE nama = ? LIMIT 1";
        $stmtSign = $koneksi->prepare($sqlGetSign);
        $stmtSign->bind_param("s", $nama_user);
        $stmtSign->execute();
        $resultSign = $stmtSign->get_result();
        $rowSign = $resultSign->fetch_assoc();
        $stmtSign->close();

        if (!$rowSign || empty($rowSign['autograph'])) {
            echo "Tanda tangan tidak ditemukan pada akun Anda. Silakan tambahkan tanda tangan terlebih dahulu di menu manajemen akun.";
            exit;
        }

        $binaryData = $rowSign['autograph'];

        // VALIDASI WAJIB (tanpa extension): tanda tangan akun harus PNG 500x200
        if (!isPng500x200($binaryData)) {
            echo "<script>alert('Tanda tangan akun Anda belum berformat PNG 500x200. Silakan perbarui tanda tangan terlebih dahulu.'); window.history.back();</script>";
            exit;
        }

        $autographColDB = $autographCol; // tetap pakai nama kolom target dari tabel BA

        $sql = "UPDATE $table 
                SET $autographColDB = ?, 
                    $approvalCol = 1, 
                    $tanggalCol = NOW()
                WHERE id = ?";
        $stmt = $koneksi->prepare($sql);
        if (!$stmt) {
            echo "<script>alert('Gagal prepare statement: " . $koneksi->error . "'); window.history.back();</script>";
            exit;
        }

        $null = NULL;
        $stmt->bind_param("bi", $null, $id);
        $stmt->send_long_data(0, $binaryData);
    }

?>

    

<?php
    // Eksekusi dan redirect
    if ($stmt->execute()) {
        if ($aktorListStr != "-"):
        ?>

        <form id="forwardToKirimEmail" action="proses_kirim_email.php" method="POST">

            <input type="hidden" name="row_id_email" value="<?= htmlspecialchars($row_id) ?>">
            <input type="hidden" name="aktorEmailHidden" value="<?= htmlspecialchars($aktorListStr) ?>">

            <input type="hidden" name="data_nomor" value="<?= htmlspecialchars($nomor_ba) ?>">
            <input type="hidden" name="data_tanggal" value="<?= htmlspecialchars($tanggal) ?>">
            <input type="hidden" name="data_bulan_romawi" value="<?= htmlspecialchars($bulanRomawi) ?>">
            <input type="hidden" name="data_tahun" value="<?= htmlspecialchars($tahun) ?>">

            <input type="hidden" name="data_jenis_ba" value="<?= htmlspecialchars($jenis_ba) ?>">
            <input type="hidden" name="data_permintaan" value="<?= htmlspecialchars($permintaan) ?>">
            <input type="hidden" name="data_nama_peminta" value="<?= htmlspecialchars($namaPeminta) ?>">

        </form>

        <script>
            document.getElementById("forwardToKirimEmail").submit();
        </script>

        <?php
        else :
        echo "
            <script>
                alert('Berhasil menyimpan tanda tangan');
                window.location.href = '$redirect';
            </script>
        ";
        endif;
    } else {
        echo "
        <script>
            alert('Gagal menyimpan tanda tangan: " . $stmt->error . "'); 
            window.location.href = '$redirect';
        </script>";
    }

    $stmt->close();
    $koneksi->close();
}
?>
</body>
</html>