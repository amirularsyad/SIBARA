<?php
include __DIR__ . "/../../koneksi.php";

$selectedPT = isset($_POST['pt']) ? trim($_POST['pt']) : '';

echo '<option value="">-- Pilih Nama --</option>';
if ($selectedPT === '') exit;

$allowedPT = array(
    "PT.MSAL (HO)",
    "PT.MSAL (PKS)",
    "PT.MSAL (SITE)",
    "PT.PSAM (PKS)",
    "PT.PSAM (SITE)",
    "PT.MAPA",
    "PT.PEAK (PKS)",
    "PT.PEAK (SITE)",
    "RO PALANGKARAYA",
    "RO SAMPIT",
    "PT.WCJU (SITE)",
    "PT.WCJU (PKS)"
);

if (!in_array($selectedPT, $allowedPT, true)) {
    echo '<option value="">(PT tidak valid)</option>';
    exit;
}

$normAkunPT     = "REPLACE(REPLACE(akun_akses.pt, ', ', ','), ' ,', ',')";
$normKaryawanPT = "REPLACE(REPLACE(dk.pt, ', ', ','), ' ,', ',')";

if ($selectedPT === "PT.MSAL (HO)") {
    $sql = "
        SELECT DISTINCT dk.nama, dk.nik
        FROM data_karyawan dk
        WHERE dk.nama IS NOT NULL
          AND dk.nama <> ''
          AND dk.nama NOT IN (
              SELECT nama
              FROM akun_akses
              WHERE deleted = 0
                AND FIND_IN_SET(?, {$normAkunPT}) > 0
          )
        ORDER BY dk.nama ASC
    ";
    $stmt = $koneksi->prepare($sql);
    $stmt->bind_param("s", $selectedPT);

} else {
    $sql = "
        SELECT DISTINCT dk.nama, dk.nik
        FROM data_karyawan_test dk
        WHERE FIND_IN_SET(?, {$normKaryawanPT}) > 0
          AND dk.nama IS NOT NULL
          AND dk.nama <> ''
          AND dk.nama NOT IN (
              SELECT nama
              FROM akun_akses
              WHERE deleted = 0
                AND FIND_IN_SET(?, {$normAkunPT}) > 0
          )
        ORDER BY dk.nama ASC
    ";
    $stmt = $koneksi->prepare($sql);
    $stmt->bind_param("ss", $selectedPT, $selectedPT);
}

$stmt->execute();
$res = $stmt->get_result();

if ($res && $res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) {
        $nama = htmlspecialchars($row['nama'], ENT_QUOTES, 'UTF-8');
        $nik  = (int)$row['nik'];
        echo "<option value=\"{$nama}\" data-nik=\"{$nik}\">{$nama}</option>";
    }
} else {
    echo '<option value="">(Tidak ada data tersedia)</option>';
}

$stmt->close();
