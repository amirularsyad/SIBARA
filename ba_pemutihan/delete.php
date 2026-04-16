<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../login_registrasi.php");
    exit();
}

if (!isset($_SESSION['hak_akses']) || ($_SESSION['hak_akses'] !== 'Admin' && $_SESSION['hak_akses'] !== 'Super Admin')) {
    header("Location: ../personal/approval.php");
    exit();
}

require_once '../koneksi.php';

/* =========================================================
| Validasi input
========================================================= */
if (!isset($_POST['id']) || !is_numeric($_POST['id']) || (int)$_POST['id'] <= 0) {
    $_SESSION['message'] = "ID data tidak valid.";
    header("Location: ba_pemutihan.php?status=gagal");
    exit();
}

$id = (int) $_POST['id'];
$pending = isset($_POST['pending']) ? (int) $_POST['pending'] : 0;
$alasan_hapus = isset($_POST['alasan_hapus']) ? trim($_POST['alasan_hapus']) : '';

/* =========================================================
| Ambil data BA Pemutihan
========================================================= */
$sqlData = "SELECT
                id,
                nama_pembuat,
                pt,
                approval_1,
                approval_2,
                approval_3,
                approval_4,
                approval_5,
                approval_6,
                approval_7,
                approval_8,
                approval_9
            FROM berita_acara_pemutihan
            WHERE id = " . $id . "
            LIMIT 1";

$resData = $koneksi->query($sqlData);

if (!$resData) {
    $_SESSION['message'] = "Gagal mengambil data BA Pemutihan.";
    header("Location: ba_pemutihan.php?status=gagal");
    exit();
}

if ($resData->num_rows === 0) {
    $resData->free();
    $_SESSION['message'] = "Data BA Pemutihan tidak ditemukan.";
    header("Location: ba_pemutihan.php?status=gagal");
    exit();
}

$data = $resData->fetch_assoc();
$resData->free();

/* =========================================================
| Validasi pembuat / super admin
========================================================= */
$nama_pembuat = isset($data['nama_pembuat']) ? trim($data['nama_pembuat']) : '';
$nama_sesi = isset($_SESSION['nama']) ? trim($_SESSION['nama']) : '';
$hak_akses = isset($_SESSION['hak_akses']) ? trim($_SESSION['hak_akses']) : '';

if ($nama_sesi !== $nama_pembuat && $hak_akses !== 'Super Admin') {
    $_SESSION['message'] = "Anda bukan pembuat data ini atau tidak memiliki izin.";
    header("Location: ba_pemutihan.php?status=gagal");
    exit();
}

/* =========================================================
| Cek approval
========================================================= */
$ada_approval = false;

for ($i = 1; $i <= 9; $i++) {
    $field = 'approval_' . $i;
    if (isset($data[$field]) && (int) $data[$field] === 1) {
        $ada_approval = true;
        break;
    }
}

if ($pending === 1 && !$ada_approval) {
    $pending = 0;
}

/* =========================================================
| Tentukan approver pending delete
========================================================= */
$ptSurat = isset($data['pt']) ? trim($data['pt']) : '';
$namaApprover = ' ';

if ($pending === 1) {
    if ($alasan_hapus === '') {
        $_SESSION['message'] = "Alasan hapus wajib diisi untuk pending delete.";
        header("Location: ba_pemutihan.php?status=gagal");
        exit();
    }

    if ($ptSurat === 'PT.MSAL (HO)') {
        $sqlApprover = "SELECT nama
                        FROM data_karyawan
                        WHERE jabatan = 'Dept. Head'
                          AND departemen = 'MIS'
                        LIMIT 1";

        $resApprover = $koneksi->query($sqlApprover);
        if ($resApprover && $resApprover->num_rows > 0) {
            $rowApprover = $resApprover->fetch_assoc();
            if (isset($rowApprover['nama']) && trim($rowApprover['nama']) !== '') {
                $namaApprover = trim($rowApprover['nama']);
            }
            $resApprover->free();
        }
    } else {
        $ptEsc = mysqli_real_escape_string($koneksi, $ptSurat);

        $sqlApprover = "SELECT nama
                        FROM data_karyawan_test
                        WHERE posisi = 'KTU'
                          AND (
                                TRIM(pt) = '" . $ptEsc . "'
                                OR FIND_IN_SET('" . $ptEsc . "', REPLACE(REPLACE(REPLACE(pt,'; ',','), ';', ','), ', ', ',')) > 0
                              )
                        LIMIT 1";

        $resApprover = $koneksi->query($sqlApprover);
        if ($resApprover && $resApprover->num_rows > 0) {
            $rowApprover = $resApprover->fetch_assoc();
            if (isset($rowApprover['nama']) && trim($rowApprover['nama']) !== '') {
                $namaApprover = trim($rowApprover['nama']);
            }
            $resApprover->free();
        }
    }

    if ($namaApprover === '') {
        $namaApprover = ($nama_sesi !== '') ? $nama_sesi : ' ';
    }
}

$namaApproverEsc = mysqli_real_escape_string($koneksi, $namaApprover);
$alasanEsc = mysqli_real_escape_string($koneksi, $alasan_hapus);

/* =========================================================
| Transaksi
========================================================= */
$koneksi->autocommit(false);

try {
    /* =====================================================
    | 1. UPDATE berita_acara_pemutihan
    ===================================================== */
    if ($pending === 1) {
        $sqlMain = "UPDATE berita_acara_pemutihan
                    SET pending_hapus = 1,
                        pending_hapus_approver = '" . $namaApproverEsc . "',
                        alasan_hapus = '" . $alasanEsc . "'
                    WHERE id = " . $id;
    } else {
        $sqlMain = "UPDATE berita_acara_pemutihan
                    SET dihapus = 1,
                        pending_hapus = 0,
                        pending_hapus_approver = ' ',
                        alasan_hapus = '" . $alasanEsc . "'
                    WHERE id = " . $id;
    }

    if (!$koneksi->query($sqlMain)) {
        throw new Exception("Gagal update berita_acara_pemutihan: " . $koneksi->error);
    }

    /* =====================================================
    | 2. UPDATE history_n_temp_ba_pemutihan / histori_n_temp_ba_pemutihan
    | update semua row terkait, TANPA LIMIT 1
    ===================================================== */
    $historyTempTable = '';

    $cekHistory1 = $koneksi->query("SHOW TABLES LIKE 'history_n_temp_ba_pemutihan'");
    if ($cekHistory1 && $cekHistory1->num_rows > 0) {
        $historyTempTable = 'history_n_temp_ba_pemutihan';
    }
    if ($cekHistory1) {
        $cekHistory1->free();
    }

    if ($historyTempTable === '') {
        $cekHistory2 = $koneksi->query("SHOW TABLES LIKE 'histori_n_temp_ba_pemutihan'");
        if ($cekHistory2 && $cekHistory2->num_rows > 0) {
            $historyTempTable = 'histori_n_temp_ba_pemutihan';
        }
        if ($cekHistory2) {
            $cekHistory2->free();
        }
    }

    if ($historyTempTable !== '') {
        $adaColPendingHapus = false;
        $adaColPendingHapusApprover = false;
        $adaColAlasanHapus = false;
        $adaColDihapus = false;

        $cekCol1 = $koneksi->query("SHOW COLUMNS FROM `" . $historyTempTable . "` LIKE 'pending_hapus'");
        if ($cekCol1 && $cekCol1->num_rows > 0) {
            $adaColPendingHapus = true;
        }
        if ($cekCol1) {
            $cekCol1->free();
        }

        $cekCol2 = $koneksi->query("SHOW COLUMNS FROM `" . $historyTempTable . "` LIKE 'pending_hapus_approver'");
        if ($cekCol2 && $cekCol2->num_rows > 0) {
            $adaColPendingHapusApprover = true;
        }
        if ($cekCol2) {
            $cekCol2->free();
        }

        $cekCol3 = $koneksi->query("SHOW COLUMNS FROM `" . $historyTempTable . "` LIKE 'alasan_hapus'");
        if ($cekCol3 && $cekCol3->num_rows > 0) {
            $adaColAlasanHapus = true;
        }
        if ($cekCol3) {
            $cekCol3->free();
        }

        $cekCol4 = $koneksi->query("SHOW COLUMNS FROM `" . $historyTempTable . "` LIKE 'dihapus'");
        if ($cekCol4 && $cekCol4->num_rows > 0) {
            $adaColDihapus = true;
        }
        if ($cekCol4) {
            $cekCol4->free();
        }

        $setHistory = array();

        if ($pending === 1) {
            if ($adaColPendingHapus) {
                $setHistory[] = "pending_hapus = 1";
            }
            if ($adaColPendingHapusApprover) {
                $setHistory[] = "pending_hapus_approver = '" . $namaApproverEsc . "'";
            }
            if ($adaColAlasanHapus) {
                $setHistory[] = "alasan_hapus = '" . $alasanEsc . "'";
            }
            if ($adaColDihapus) {
                $setHistory[] = "dihapus = 0";
            }
        } else {
            if ($adaColDihapus) {
                $setHistory[] = "dihapus = 1";
            }
            if ($adaColPendingHapus) {
                $setHistory[] = "pending_hapus = 0";
            }
            if ($adaColPendingHapusApprover) {
                $setHistory[] = "pending_hapus_approver = ' '";
            }
            if ($adaColAlasanHapus) {
                $setHistory[] = "alasan_hapus = '" . $alasanEsc . "'";
            }
        }

        if (!empty($setHistory)) {
            $sqlHistory = "UPDATE `" . $historyTempTable . "`
                           SET " . implode(', ', $setHistory) . "
                           WHERE id_ba = " . $id;

            if (!$koneksi->query($sqlHistory)) {
                throw new Exception("Gagal update " . $historyTempTable . ": " . $koneksi->error);
            }
        }
    }

    /* =====================================================
    | 3. UPDATE historikal_edit_ba
    | update semua row terkait, TANPA LIMIT 1
    ===================================================== */
    $cekHistorikalTable = $koneksi->query("SHOW TABLES LIKE 'historikal_edit_ba'");
    $adaHistorikalTable = false;

    if ($cekHistorikalTable && $cekHistorikalTable->num_rows > 0) {
        $adaHistorikalTable = true;
    }
    if ($cekHistorikalTable) {
        $cekHistorikalTable->free();
    }

    if ($adaHistorikalTable) {
        $adaColPendingHapus = false;
        $adaColPendingHapusApprover = false;
        $adaColAlasanHapus = false;
        $adaColDihapus = false;

        $cekCol1 = $koneksi->query("SHOW COLUMNS FROM `historikal_edit_ba` LIKE 'pending_hapus'");
        if ($cekCol1 && $cekCol1->num_rows > 0) {
            $adaColPendingHapus = true;
        }
        if ($cekCol1) {
            $cekCol1->free();
        }

        $cekCol2 = $koneksi->query("SHOW COLUMNS FROM `historikal_edit_ba` LIKE 'pending_hapus_approver'");
        if ($cekCol2 && $cekCol2->num_rows > 0) {
            $adaColPendingHapusApprover = true;
        }
        if ($cekCol2) {
            $cekCol2->free();
        }

        $cekCol3 = $koneksi->query("SHOW COLUMNS FROM `historikal_edit_ba` LIKE 'alasan_hapus'");
        if ($cekCol3 && $cekCol3->num_rows > 0) {
            $adaColAlasanHapus = true;
        }
        if ($cekCol3) {
            $cekCol3->free();
        }

        $cekCol4 = $koneksi->query("SHOW COLUMNS FROM `historikal_edit_ba` LIKE 'dihapus'");
        if ($cekCol4 && $cekCol4->num_rows > 0) {
            $adaColDihapus = true;
        }
        if ($cekCol4) {
            $cekCol4->free();
        }

        $setHistorikal = array();

        if ($pending === 1) {
            if ($adaColPendingHapus) {
                $setHistorikal[] = "pending_hapus = 1";
            }
            if ($adaColPendingHapusApprover) {
                $setHistorikal[] = "pending_hapus_approver = '" . $namaApproverEsc . "'";
            }
            if ($adaColAlasanHapus) {
                $setHistorikal[] = "alasan_hapus = '" . $alasanEsc . "'";
            }
            if ($adaColDihapus) {
                $setHistorikal[] = "dihapus = 0";
            }
        } else {
            if ($adaColDihapus) {
                $setHistorikal[] = "dihapus = 1";
            }
            if ($adaColPendingHapus) {
                $setHistorikal[] = "pending_hapus = 0";
            }
            if ($adaColPendingHapusApprover) {
                $setHistorikal[] = "pending_hapus_approver = ' '";
            }
            if ($adaColAlasanHapus) {
                $setHistorikal[] = "alasan_hapus = '" . $alasanEsc . "'";
            }
        }

        if (!empty($setHistorikal)) {
            $sqlHistorikal = "UPDATE historikal_edit_ba
                              SET " . implode(', ', $setHistorikal) . "
                              WHERE id_ba = " . $id . "
                                AND LOWER(TRIM(nama_ba)) = 'pemutihan'";

            if (!$koneksi->query($sqlHistorikal)) {
                throw new Exception("Gagal update historikal_edit_ba: " . $koneksi->error);
            }
        }
    }

    $koneksi->commit();
    $koneksi->autocommit(true);

    $_SESSION['message'] = ($pending === 1)
        ? "Permintaan penghapusan berhasil dikirim untuk approval."
        : "Data BA Pemutihan berhasil dihapus.";

    header("Location: ba_pemutihan.php?status=sukses");
    exit();

} catch (Exception $e) {
    $koneksi->rollback();
    $koneksi->autocommit(true);

    error_log("Delete BA Pemutihan gagal (ID: " . $id . "): " . $e->getMessage());

    $_SESSION['message'] = "Proses penghapusan gagal: " . $e->getMessage();
    header("Location: ba_pemutihan.php?status=gagal");
    exit();
}
?>