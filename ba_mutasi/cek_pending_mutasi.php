<?php
require_once '../koneksi.php';

$id_ba = isset($_POST['id_ba']) ? (int) $_POST['id_ba'] : 0;

$result = [
    'pending_edit' => false,
    'approval_exist' => false
];

if ($id_ba > 0) {

    // ===============================
    // CEK 1 : pending edit (PRIORITAS)
    // ===============================
    $qPending = mysqli_query($koneksi, "
        SELECT 1
        FROM history_n_temp_ba_mutasi
        WHERE id_ba = '$id_ba'
          AND pending_status = 1
          AND status = 1
        LIMIT 1
    ");

    if ($qPending && mysqli_num_rows($qPending) > 0) {
        $result['pending_edit'] = true;

        // KARENA PRIORITAS, LANGSUNG KEMBALIKAN
        echo json_encode($result);
        exit;
    }

    // ==========================================
    // CEK 2 : approval sudah berjalan / ada 1
    // ==========================================
    $qApproval = mysqli_query($koneksi, "
        SELECT 1
        FROM berita_acara_mutasi
        WHERE id = '$id_ba'
          AND (
                approval_1 = 1 OR approval_2 = 1 OR approval_3 = 1
             OR approval_4 = 1 OR approval_5 = 1 OR approval_6 = 1
             OR approval_7 = 1 OR approval_8 = 1 OR approval_9 = 1
             OR approval_10 = 1 OR approval_11 = 1
          )
        LIMIT 1
    ");

    if ($qApproval && mysqli_num_rows($qApproval) > 0) {
        $result['approval_exist'] = true;
    }
}

echo json_encode($result);
exit;
