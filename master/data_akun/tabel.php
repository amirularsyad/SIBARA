<?php
session_start();

// Super Admin / pengecualian
$isSuper = (isset($_SESSION['hak_akses']) && $_SESSION['hak_akses'] === 'Super Admin')
    || (isset($_SESSION['nama']) && $_SESSION['nama'] === 'Rizki Sunandar');

//setup akses
include '../../koneksi.php';
$manajemen_akun_akses = 0;
if (isset($_SESSION['nama'])) {
    $namaLogin = $_SESSION['nama'];
    $sqlAkses = "SELECT manajemen_akun_akses, warna_menu FROM akun_akses WHERE nama = ? LIMIT 1";
    if ($stmt = $koneksi->prepare($sqlAkses)) {
        $stmt->bind_param("s", $namaLogin);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $rowAkses = $res->fetch_assoc()) {
            $manajemen_akun_akses = (int)$rowAkses['manajemen_akun_akses'];
            $warna_menu = $rowAkses['warna_menu'];
            $_SESSION['manajemen_akun_akses'] = $manajemen_akun_akses;
        }
        $stmt->close();
    }
}
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../../login_registrasi.php");
    exit();
}
if (!isset($_SESSION['hak_akses']) || $_SESSION['hak_akses'] !== 'Super Admin' && $manajemen_akun_akses != 1 && $manajemen_akun_akses != 2) {
    header("Location: ../../personal/approval.php");
    exit();
}

$showDataAkunMenu = false;

$showDataAkunMenu = $isSuper || ($manajemen_akun_akses === 1) || ($manajemen_akun_akses === 2);

$notShowDataAkunMenu = false;

if ($manajemen_akun_akses === 1) {
    $notShowDataAkunMenu = true;
}

$showDataAkunMenuEditable = false;

$showDataAkunMenuEditable = $isSuper || ($manajemen_akun_akses === 2);

//Warna Menu
if ($warna_menu == "0" || $warna_menu === "" || is_null($warna_menu)) {
    // default (gradient)
    $bgMenu = 'linear-gradient(to bottom right, #3e02be 0%, rgb(1, 64, 159) 50%, rgb(2, 59, 159) 100%)';
} else {
    $bgMenu = $warna_menu;
}

//Warna Navbar
if ($warna_menu == "0" || $warna_menu === "" || is_null($warna_menu)) {
    // default (gradient)
    $bgNav = 'linear-gradient(to bottom right, #3e02be 0%, rgb(1, 64, 159) 50%, rgb(2, 59, 159) 100%)';
} else {
    $bgNav = $warna_menu;
}

if ($warna_menu === "0" || $warna_menu === "" || is_null($warna_menu)) {
    // default pakai gradient
    $textColorStyle = 'font-size: 3rem;
      font-weight: bold;
      background: linear-gradient(to bottom right,
        #1702d5,
        #2100a5,
        #0012ce,
        #3262ff,
        #5e74ff
      );
      background-size: 300% 300%;
      animation: gradient-shift 4s ease infinite;
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      color: transparent;';
} else {

    $textColorStyle = 'font-size: 3rem;
      font-weight: bold;
      color: ' . $warna_menu . ';';
}

$jumlah_approval_notif = require '../../approval_notification_badge.php';

$ptSekarang = $_SESSION['pt'];
if (is_array($ptSekarang)) {
    $ptSekarang = reset($ptSekarang);
}
$ptSekarang = trim($ptSekarang);

?>

<?php
// =========================
// AJAX: ambil opsi jabatan HO berdasarkan departemen (hapus jabatan unik yang sudah terpakai)
// =========================
if (isset($_POST['ajax']) && $_POST['ajax'] === 'get_jabatan_ho') {
    header('Content-Type: text/html; charset=utf-8');

    $dept = isset($_POST['departemen']) ? trim($_POST['departemen']) : '';

    echo "<option value=''>-- Pilih Jabatan --</option>";

    if ($dept !== '') {
        $roles = getAvailableHoRoles($koneksi, $dept, 0);
        for ($i = 0; $i < count($roles); $i++) {
            $role = $roles[$i];
            echo "<option value='" . htmlspecialchars($role, ENT_QUOTES, 'UTF-8') . "'>" .
                htmlspecialchars($role, ENT_QUOTES, 'UTF-8') .
                "</option>";
        }
    }

    exit;
}

if (isset($_POST['ajax']) && $_POST['ajax'] === 'get_jabatan_ho_edit') {
    header('Content-Type: text/html; charset=utf-8');

    $dept = isset($_POST['departemen']) ? trim($_POST['departemen']) : '';
    $currentId = isset($_POST['current_id']) ? (int)$_POST['current_id'] : 0;

    echo "<option value=''>-- Pilih Jabatan --</option>";

    if ($dept !== '') {
        $roles = getAvailableHoRoles($koneksi, $dept, $currentId);
        for ($i = 0; $i < count($roles); $i++) {
            $role = $roles[$i];
            echo "<option value='" . htmlspecialchars($role, ENT_QUOTES, 'UTF-8') . "'>" .
                htmlspecialchars($role, ENT_QUOTES, 'UTF-8') .
                "</option>";
        }
    }

    exit;
}

if (isset($_POST['ajax']) && $_POST['ajax'] === 'get_departemen_ho_edit') {
    header('Content-Type: text/html; charset=utf-8');

    $currentId = isset($_POST['current_id']) ? (int)$_POST['current_id'] : 0;
    $currentDept = isset($_POST['current_departemen']) ? trim($_POST['current_departemen']) : '';

    echo renderHoDepartmentOptions($koneksi, $currentDept, $currentId, $currentDept);
    exit;
}



// =========================
// AJAX: cek posisi unik SITE berdasarkan PT terpilih
// (GM, KTU, Staf GA, Kepala Mill)
// =========================
if (isset($_POST['ajax']) && $_POST['ajax'] === 'get_site_unique_pos') {
    header('Content-Type: application/json; charset=utf-8');

    $ptArr = isset($_POST['pt']) ? $_POST['pt'] : array();
    if (!is_array($ptArr)) {
        $ptArr = array_map('trim', explode(',', (string)$ptArr));
    }

    // bersihkan PT
    $ptClean = array();
    for ($i = 0; $i < count($ptArr); $i++) {
        $v = trim((string)$ptArr[$i]);
        if ($v !== '') $ptClean[] = $v;
    }

    $used = array(
        'GM' => false,
        'KTU' => false,
        'Staf GA' => false,
        'Kepala Mill' => false
    );

    // kalau belum pilih PT, kembalikan semua false
    if (count($ptClean) === 0) {
        echo json_encode(array('used' => $used));
        exit;
    }

    // normalisasi string koma di kolom pt supaya FIND_IN_SET aman
    $ptNorm = "REPLACE(REPLACE(pt, ', ', ','), ' ,', ',')";

    $ors = array();
    for ($i = 0; $i < count($ptClean); $i++) {
        $pEsc = $koneksi->real_escape_string($ptClean[$i]);
        $ors[] = "FIND_IN_SET('{$pEsc}', {$ptNorm}) > 0";
    }
    $condPt = count($ors) ? "(" . implode(" OR ", $ors) . ")" : "0=1";

    $sql = "
        SELECT DISTINCT posisi
        FROM data_karyawan_test
        WHERE dihapus = 0
          AND posisi IN ('GM','KTU','Staf GA','Kepala Mill')
          AND {$condPt}
    ";

    $res = $koneksi->query($sql);
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $pos = isset($r['posisi']) ? trim((string)$r['posisi']) : '';
            if ($pos !== '' && array_key_exists($pos, $used)) {
                $used[$pos] = true;
            }
        }
    }

    echo json_encode(array('used' => $used));
    exit;
}

if (isset($_POST['ajax']) && $_POST['ajax'] === 'get_site_unique_pos_edit') {
    header('Content-Type: application/json; charset=utf-8');

    $ptArr = isset($_POST['pt']) ? $_POST['pt'] : array();
    if (!is_array($ptArr)) $ptArr = array_map('trim', explode(',', (string)$ptArr));

    $currentId = isset($_POST['current_id']) ? (int)$_POST['current_id'] : 0;

    $ptClean = array();
    for ($i = 0; $i < count($ptArr); $i++) {
        $v = trim((string)$ptArr[$i]);
        if ($v !== '') $ptClean[] = $v;
    }

    $used = array('GM' => false, 'KTU' => false, 'Staf GA' => false, 'Kepala Mill' => false);

    if (count($ptClean) === 0) {
        echo json_encode(array('used' => $used));
        exit;
    }

    $ptNorm = "REPLACE(REPLACE(pt, ', ', ','), ' ,', ',')";
    $ors = array();
    for ($i = 0; $i < count($ptClean); $i++) {
        $pEsc = $koneksi->real_escape_string($ptClean[$i]);
        $ors[] = "FIND_IN_SET('{$pEsc}', {$ptNorm}) > 0";
    }
    $condPt = count($ors) ? "(" . implode(" OR ", $ors) . ")" : "0=1";

    $sql = "
        SELECT DISTINCT posisi
        FROM data_karyawan_test
        WHERE dihapus = 0
          AND id <> {$currentId}
          AND posisi IN ('GM','KTU','Staf GA','Kepala Mill')
          AND {$condPt}
    ";

    $res = $koneksi->query($sql);
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $pos = isset($r['posisi']) ? trim((string)$r['posisi']) : '';
            if ($pos !== '' && array_key_exists($pos, $used)) $used[$pos] = true;
        }
    }

    echo json_encode(array('used' => $used));
    exit;
}

// =========================
// HELPER generate NIK
// =========================
function generateNikNext($koneksi, $tableName, $base)
{
    $maxNik = 0;
    $sql = "SELECT MAX(CAST(nik AS UNSIGNED)) AS max_nik FROM " . $tableName;
    $res = $koneksi->query($sql);
    if ($res && $row = $res->fetch_assoc()) {
        $maxNik = (int)$row['max_nik'];
    }
    if ($maxNik < (int)$base) $maxNik = (int)$base;
    return (string)($maxNik + 1);
}

// =========================
// HELPER RULE HO (MAINTAINABLE, PHP 5.6 SAFE)
// =========================

function getHoDefaultDepartments()
{
    return array(
        "ACCOUNTING",
        "AGRONOMI & DEVELOPMENT",
        "BOD",
        "BQC",
        "CEO",
        "COMMERCIALS",
        "ENGINEERING",
        "FINANCE",
        "HRD",
        "HRO",
        "INTERNAL AUDIT",
        "LEGAL",
        "MIS",
        "OPERATION",
        "PURCHASING",
        "R&D",
        "TEKNIK"
    );
}

function getHoDefaultRoles()
{
    return array("Magang", "Staf", "Sect. Head", "Dept. Head", "AVP", "Spv", "Div. Head", "Direktur");
}

function getHoDefaultUniqueRoles()
{
    return array("Dept. Head", "AVP", "Div. Head", "Direktur");
}

/*
|--------------------------------------------------------------------------
| RULE KHUSUS PER DEPARTEMEN
|--------------------------------------------------------------------------
*/
function getHoDepartmentRules()
{
    return array(
        "BOD" => array(
            "roles" => array("CEO", "VICE CEO"),
            "unique_roles" => array("CEO", "VICE CEO"),
            "cross_department_unique_roles" => array(
                "CEO" => array("BOD"),
                "VICE CEO" => array("BOD")
            ),
            "hide_department_when_full" => true
        ),

        "FINANCE" => array(
            "roles" => getHoDefaultRoles(),
            "unique_roles" => getHoDefaultUniqueRoles(),
            "cross_department_unique_roles" => array(
                "Direktur" => array("FINANCE", "ACCOUNTING")
            ),
            "hide_department_when_full" => false
        ),

        "ACCOUNTING" => array(
            "roles" => getHoDefaultRoles(),
            "unique_roles" => getHoDefaultUniqueRoles(),
            "cross_department_unique_roles" => array(
                "Direktur" => array("ACCOUNTING", "FINANCE")
            ),
            "hide_department_when_full" => false
        ),

        "HRD" => array(
            "roles" => getHoDefaultRoles(),
            "unique_roles" => getHoDefaultUniqueRoles(),
            "cross_department_unique_roles" => array(
                "Direktur" => array("HRD", "OPERATION")
            ),
            "hide_department_when_full" => false
        ),

        "HRO" => array(
            "roles" => array("Magang", "Staf", "Sect. Head", "Dept. Head", "AVP", "Spv", "Div. Head"),
            "unique_roles" => array("Dept. Head", "AVP", "Div. Head"),
            "cross_department_unique_roles" => array(),
            "hide_department_when_full" => false
        ),

        "OPERATION" => array(
            "roles" => array("Direktur"),
            "unique_roles" => array("Direktur"),
            "cross_department_unique_roles" => array(
                "Direktur" => array("OPERATION", "HRD")
            ),
            "hide_department_when_full" => true
        ),

        "TEKNIK" => array(
            "roles" => array("Magang", "Staf", "Sect. Head", "Dept. Head", "AVP", "Spv", "Div. Head"),
            "unique_roles" => array("Dept. Head", "AVP", "Div. Head"),
            "cross_department_unique_roles" => array(),
            "hide_department_when_full" => false
        ),

        "ENGINEERING" => array(
            "roles" => array("Magang", "Staf", "Sect. Head", "Dept. Head", "AVP", "Spv", "Div. Head"),
            "unique_roles" => array("Dept. Head", "AVP", "Div. Head"),
            "cross_department_unique_roles" => array(),
            "hide_department_when_full" => false
        ),

        "BQC" => array(
            "roles" => array("Magang", "Staf", "Sect. Head", "Dept. Head", "AVP", "Spv", "Div. Head"),
            "unique_roles" => array("Dept. Head", "AVP", "Div. Head"),
            "cross_department_unique_roles" => array(),
            "hide_department_when_full" => false
        ),

        "R&D" => array(
            "roles" => array("Magang", "Staf", "Sect. Head", "Dept. Head", "AVP", "Spv", "Div. Head"),
            "unique_roles" => array("Dept. Head", "AVP", "Div. Head"),
            "cross_department_unique_roles" => array(),
            "hide_department_when_full" => false
        ),

        "AGRONOMI & DEVELOPMENT" => array(
            "roles" => array("Magang", "Staf", "Sect. Head", "Dept. Head", "AVP", "Spv", "Div. Head"),
            "unique_roles" => array("Dept. Head", "AVP", "Div. Head"),
            "cross_department_unique_roles" => array(),
            "hide_department_when_full" => false
        )
    );
}

function getHoDepartmentRule($departemen)
{
    $rules = getHoDepartmentRules();
    if (isset($rules[$departemen])) {
        return $rules[$departemen];
    }

    return array(
        "roles" => getHoDefaultRoles(),
        "unique_roles" => getHoDefaultUniqueRoles(),
        "cross_department_unique_roles" => array(),
        "hide_department_when_full" => false
    );
}

function getHoCrossDepartmentUniqueRolesMap($departemen)
{
    $rule = getHoDepartmentRule($departemen);

    if (isset($rule['cross_department_unique_roles']) && is_array($rule['cross_department_unique_roles'])) {
        return $rule['cross_department_unique_roles'];
    }

    return array();
}

function getHoRoleScopeDepartments($departemen, $role)
{
    $map = getHoCrossDepartmentUniqueRolesMap($departemen);

    if (isset($map[$role]) && is_array($map[$role]) && count($map[$role]) > 0) {
        return $map[$role];
    }

    return array($departemen);
}

function getHoEquivalentUniqueRoleGroups($departemen)
{
    // key bebas, yang penting mapping antar role dalam grup sama
    return array(
        "dept_head_avp" => array("Dept. Head", "AVP")
    );
}

function getHoEquivalentUniqueRoles($departemen, $role)
{
    $groups = getHoEquivalentUniqueRoleGroups($departemen);

    foreach ($groups as $groupName => $roles) {
        if (in_array($role, $roles, true)) {
            return $roles;
        }
    }

    return array($role);
}

function getUsedHoUniqueRoles($koneksi, $departemen, $excludeId)
{
    $rule = getHoDepartmentRule($departemen);
    $uniqueRoles = isset($rule['unique_roles']) ? $rule['unique_roles'] : array();

    $used = array();

    if (count($uniqueRoles) === 0) {
        return $used;
    }

    for ($i = 0; $i < count($uniqueRoles); $i++) {
        $role = $uniqueRoles[$i];

        $scopeDepartments = getHoRoleScopeDepartments($departemen, $role);
        if (count($scopeDepartments) === 0) {
            $scopeDepartments = array($departemen);
        }

        // role yang dianggap setara unique
        $equivalentRoles = getHoEquivalentUniqueRoles($departemen, $role);
        if (count($equivalentRoles) === 0) {
            $equivalentRoles = array($role);
        }

        $deptSqlParts = array();
        for ($j = 0; $j < count($scopeDepartments); $j++) {
            $deptSqlParts[] = "'" . $koneksi->real_escape_string($scopeDepartments[$j]) . "'";
        }

        $roleSqlParts = array();
        for ($j = 0; $j < count($equivalentRoles); $j++) {
            $roleSqlParts[] = "'" . $koneksi->real_escape_string($equivalentRoles[$j]) . "'";
        }

        $sql = "SELECT 1
                FROM data_karyawan
                WHERE dihapus = 0
                  AND jabatan IN (" . implode(",", $roleSqlParts) . ")
                  AND departemen IN (" . implode(",", $deptSqlParts) . ")";

        if ((int)$excludeId > 0) {
            $sql .= " AND id <> " . (int)$excludeId;
        }

        $sql .= " LIMIT 1";

        if ($res = $koneksi->query($sql)) {
            if ($res->num_rows > 0) {
                $used[] = $role;
            }
        }
    }

    return $used;
}

function getAvailableHoRoles($koneksi, $departemen, $excludeId)
{
    $rule = getHoDepartmentRule($departemen);
    $allRoles = isset($rule['roles']) ? $rule['roles'] : array();
    $uniqueRoles = isset($rule['unique_roles']) ? $rule['unique_roles'] : array();

    $used = getUsedHoUniqueRoles($koneksi, $departemen, $excludeId);
    $available = array();

    for ($i = 0; $i < count($allRoles); $i++) {
        $role = $allRoles[$i];

        if (in_array($role, $uniqueRoles, true) && in_array($role, $used, true)) {
            continue;
        }

        $available[] = $role;
    }

    return $available;
}

function isHoDepartmentVisible($koneksi, $departemen, $excludeId, $currentDept)
{
    if ($currentDept === $departemen) {
        return true; // saat edit, departemen milik dirinya tetap boleh tampil
    }

    $rule = getHoDepartmentRule($departemen);
    $hideWhenFull = !empty($rule['hide_department_when_full']);

    if (!$hideWhenFull) {
        return true;
    }

    $availableRoles = getAvailableHoRoles($koneksi, $departemen, $excludeId);
    return count($availableRoles) > 0;
}

function renderHoDepartmentOptions($koneksi, $selectedDept, $excludeId, $currentDept)
{
    $departments = getHoDefaultDepartments();

    $html = "<option value=''>-- Pilih Departemen --</option>";

    for ($i = 0; $i < count($departments); $i++) {
        $dept = $departments[$i];

        if (!isHoDepartmentVisible($koneksi, $dept, $excludeId, $currentDept)) {
            continue;
        }

        $selected = ($selectedDept === $dept) ? " selected" : "";
        $html .= "<option value='" . htmlspecialchars($dept, ENT_QUOTES, 'UTF-8') . "'" . $selected . ">" .
            htmlspecialchars($dept, ENT_QUOTES, 'UTF-8') .
            "</option>";
    }

    return $html;
}

function isHoRoleAllowed($koneksi, $departemen, $jabatan, $excludeId)
{
    $availableRoles = getAvailableHoRoles($koneksi, $departemen, $excludeId);
    return in_array($jabatan, $availableRoles, true);
}

// =========================
// SUBMIT INPUT KARYAWAN
// =========================
if (isset($_POST['submit_karyawan'])) {

    $tipe = isset($_POST['lokasi_tipe']) ? $_POST['lokasi_tipe'] : '';

    // ✅ bersihkan flash lama (jangan set jadi string kosong)
    unset($_SESSION['message'], $_SESSION['success']);

    if ($tipe === 'HO') {

        $nama = isset($_POST['ho_nama']) ? trim($_POST['ho_nama']) : '';
        $lantai = isset($_POST['ho_lantai']) ? trim($_POST['ho_lantai']) : '';
        $departemen = isset($_POST['ho_departemen']) ? trim($_POST['ho_departemen']) : '';
        $jabatan = isset($_POST['ho_jabatan']) ? trim($_POST['ho_jabatan']) : '';
        $posisi = isset($_POST['ho_posisi']) ? trim($_POST['ho_posisi']) : '';

        if ($nama === '' || $lantai === '' || $departemen === '' || $jabatan === '' || $posisi === '') {
            $_SESSION['message'] = 'Data Head Office belum lengkap.';
            $_SESSION['success'] = false;
            session_write_close();
            header("Location: tabel.php");
            exit;
        }

        // validasi jabatan HO berdasarkan rule departemen
        if (!isHoRoleAllowed($koneksi, $departemen, $jabatan, 0)) {
            $_SESSION['message'] = 'Jabatan "' . $jabatan . '" tidak tersedia / sudah digunakan untuk departemen ' . $departemen . '.';
            $_SESSION['success'] = false;
            session_write_close();
            header("Location: tabel.php");
            exit;
        }

        $nik = generateNikNext($koneksi, "data_karyawan", 10000);

        $sqlIns = "INSERT INTO data_karyawan (nama, lantai, jabatan, posisi, departemen, nik, dihapus)
                   VALUES (?, ?, ?, ?, ?, ?, 0)";
        if ($stmt = $koneksi->prepare($sqlIns)) {
            $stmt->bind_param("ssssss", $nama, $lantai, $jabatan, $posisi, $departemen, $nik);

            if ($stmt->execute()) {
                $_SESSION['message'] = 'Karyawan HO berhasil ditambahkan.';
                $_SESSION['success'] = true;
            } else {
                $_SESSION['message'] = 'Gagal simpan karyawan HO: ' . $stmt->error;
                $_SESSION['success'] = false;
            }

            $stmt->close();
        } else {
            $_SESSION['message'] = 'Gagal prepare query HO: ' . $koneksi->error;
            $_SESSION['success'] = false;
        }

        session_write_close();
        header("Location: tabel.php");
        exit;
    } elseif ($tipe === 'SITE') {

        $nama = isset($_POST['site_nama']) ? trim($_POST['site_nama']) : '';
        $ptArr = isset($_POST['site_pt']) && is_array($_POST['site_pt']) ? $_POST['site_pt'] : array();
        $departemen = isset($_POST['site_departemen']) ? trim($_POST['site_departemen']) : '';
        $lokasi = isset($_POST['site_lokasi']) ? trim($_POST['site_lokasi']) : '';
        $posisi = isset($_POST['site_posisi']) ? trim($_POST['site_posisi']) : '';

        // rapikan PT
        $ptClean = array();
        for ($i = 0; $i < count($ptArr); $i++) {
            $v = trim($ptArr[$i]);
            if ($v !== '') $ptClean[] = $v;
        }

        if ($nama === '' || count($ptClean) === 0 || $departemen === '' || $lokasi === '') {
            $_SESSION['message'] = 'Data Site belum lengkap.';
            $_SESSION['success'] = false;
            session_write_close();
            header("Location: tabel.php");
            exit;
        }

        // aturan MILL PKS hanya jika PT dipilih 1 dan PT termasuk daftar PKS tertentu
        $pkspOnly = array("PT.PEAK (PKS)", "PT.MSAL (PKS)", "PT.PSAM (PKS)", "PT.WCJU (PKS)");
        if ($departemen === 'MILL PKS') {
            if (!(count($ptClean) === 1 && in_array($ptClean[0], $pkspOnly, true))) {
                $_SESSION['message'] = 'Departemen MILL PKS hanya untuk 1 PT PKS tertentu.';
                $_SESSION['success'] = false;
                session_write_close();
                header("Location: tabel.php");
                exit;
            }
        }

        // posisi menyesuaikan departemen
        if ($departemen === 'IT') {
            $allowed = array("IT Support", "KTU");
            if (!in_array($posisi, $allowed, true)) $posisi = '';
        } elseif ($departemen === 'HRD') {
            $posisi = "Staf GA";
        } elseif ($departemen === 'GM') {
            $posisi = "GM";
        } elseif ($departemen === 'MILL PKS') {
            $posisi = "Kepala Mill";
        }

        if ($posisi === '') {
            $_SESSION['message'] = 'Posisi Site tidak valid / belum dipilih.';
            $_SESSION['success'] = false;
            session_write_close();
            header("Location: tabel.php");
            exit;
        }

        // VALIDASI POSISI UNIK SITE PER PT TERPILIH
        $uniquePos = '';
        if ($departemen === 'GM') {
            $uniquePos = 'GM';
        } elseif ($departemen === 'HRD') {
            $uniquePos = 'Staf GA';
        } elseif ($departemen === 'MILL PKS') {
            $uniquePos = 'Kepala Mill';
        } elseif ($departemen === 'IT' && $posisi === 'KTU') {
            $uniquePos = 'KTU';
        }

        if ($uniquePos !== '') {
            $ptNormCheck = "REPLACE(REPLACE(pt, ', ', ','), ' ,', ',')";
            $orsCheck = array();
            for ($i = 0; $i < count($ptClean); $i++) {
                $pEsc = $koneksi->real_escape_string($ptClean[$i]);
                $orsCheck[] = "FIND_IN_SET('{$pEsc}', {$ptNormCheck}) > 0";
            }
            $condPtCheck = count($orsCheck) ? "(" . implode(" OR ", $orsCheck) . ")" : "0=1";

            $sqlCekPos = "SELECT COUNT(*) AS jml
                          FROM data_karyawan_test
                          WHERE dihapus = 0 AND posisi = ? AND {$condPtCheck}";
            $jmlPos = 0;

            if ($stmt = $koneksi->prepare($sqlCekPos)) {
                $stmt->bind_param("s", $uniquePos);
                $stmt->execute();
                $res = $stmt->get_result();
                if ($res && $r = $res->fetch_assoc()) {
                    $jmlPos = (int)$r['jml'];
                }
                $stmt->close();
            } else {
                $_SESSION['message'] = 'Gagal prepare cek posisi unik Site: ' . $koneksi->error;
                $_SESSION['success'] = false;
                session_write_close();
                header("Location: tabel.php");
                exit;
            }

            if ($jmlPos > 0) {
                $_SESSION['message'] = 'Posisi "' . $uniquePos . '" sudah ada di salah satu PT yang dipilih.';
                $_SESSION['success'] = false;
                session_write_close();
                header("Location: tabel.php");
                exit;
            }
        }

        $nik = generateNikNext($koneksi, "data_karyawan_test", 20000);
        $ptString = implode(",", $ptClean);

        $jabatan = $departemen;

        $sqlIns = "INSERT INTO data_karyawan_test (nama, lokasi, jabatan, posisi, departemen, nik, pt, dihapus)
                   VALUES (?, ?, ?, ?, ?, ?, ?, 0)";
        if ($stmt = $koneksi->prepare($sqlIns)) {
            $stmt->bind_param("sssssss", $nama, $lokasi, $jabatan, $posisi, $departemen, $nik, $ptString);

            if ($stmt->execute()) {
                $_SESSION['message'] = 'Karyawan Site berhasil ditambahkan. (NIK: ' . $nik . ')';
                $_SESSION['success'] = true;
            } else {
                $_SESSION['message'] = 'Gagal simpan karyawan Site: ' . $stmt->error;
                $_SESSION['success'] = false;
            }

            $stmt->close();
        } else {
            $_SESSION['message'] = 'Gagal prepare query Site: ' . $koneksi->error;
            $_SESSION['success'] = false;
        }

        session_write_close();
        header("Location: tabel.php");
        exit;
    } else {
        $_SESSION['message'] = 'Pilih lokasi (Head Office / Site) terlebih dahulu.';
        $_SESSION['success'] = false;
        session_write_close();
        header("Location: tabel.php");
        exit;
    }
}

if (isset($_POST['update_karyawan'])) {

    unset($_SESSION['message'], $_SESSION['success']);

    $id = isset($_POST['karyawan_id']) ? (int)$_POST['karyawan_id'] : 0;
    $sumber = isset($_POST['karyawan_sumber']) ? trim($_POST['karyawan_sumber']) : '';

    if ($id <= 0 || ($sumber !== 'HO' && $sumber !== 'SITE')) {
        $_SESSION['message'] = 'Data update karyawan tidak valid.';
        $_SESSION['success'] = false;
        session_write_close();
        header("Location: tabel.php");
        exit;
    }

    if ($sumber === 'HO') {

        $nama = isset($_POST['ho_nama_edit']) ? trim($_POST['ho_nama_edit']) : '';
        $lantai = isset($_POST['ho_lantai_edit']) ? trim($_POST['ho_lantai_edit']) : '';
        $departemen = isset($_POST['ho_departemen_edit']) ? trim($_POST['ho_departemen_edit']) : '';
        $jabatan = isset($_POST['ho_jabatan_edit']) ? trim($_POST['ho_jabatan_edit']) : '';
        $posisi = isset($_POST['ho_posisi_edit']) ? trim($_POST['ho_posisi_edit']) : '';

        if ($nama === '' || $lantai === '' || $departemen === '' || $jabatan === '' || $posisi === '') {
            $_SESSION['message'] = 'Data Head Office belum lengkap.';
            $_SESSION['success'] = false;
            session_write_close();
            header("Location: tabel.php");
            exit;
        }

        // validasi jabatan HO berdasarkan rule departemen (exclude id sendiri)
        if (!isHoRoleAllowed($koneksi, $departemen, $jabatan, $id)) {
            $_SESSION['message'] = 'Jabatan "' . $jabatan . '" tidak tersedia / sudah digunakan untuk departemen ' . $departemen . '.';
            $_SESSION['success'] = false;
            session_write_close();
            header("Location: tabel.php");
            exit;
        }

        $sqlUp = "UPDATE data_karyawan
                  SET nama = ?, lantai = ?, departemen = ?, jabatan = ?, posisi = ?
                  WHERE id = ? LIMIT 1";
        if ($stmt = $koneksi->prepare($sqlUp)) {
            $stmt->bind_param("sssssi", $nama, $lantai, $departemen, $jabatan, $posisi, $id);
            if ($stmt->execute()) {
                $_SESSION['message'] = 'Data karyawan HO berhasil diupdate.';
                $_SESSION['success'] = true;
            } else {
                $_SESSION['message'] = 'Gagal update karyawan HO: ' . $stmt->error;
                $_SESSION['success'] = false;
            }
            $stmt->close();
        } else {
            $_SESSION['message'] = 'Gagal prepare update HO: ' . $koneksi->error;
            $_SESSION['success'] = false;
        }

        session_write_close();
        header("Location: tabel.php");
        exit;
    } else { // SITE

        $nama = isset($_POST['site_nama_edit']) ? trim($_POST['site_nama_edit']) : '';
        $ptArr = isset($_POST['site_pt_edit']) && is_array($_POST['site_pt_edit']) ? $_POST['site_pt_edit'] : array();
        $departemen = isset($_POST['site_departemen_edit']) ? trim($_POST['site_departemen_edit']) : '';
        $lokasi = isset($_POST['site_lokasi_edit']) ? trim($_POST['site_lokasi_edit']) : '';
        $posisi = isset($_POST['site_posisi_edit']) ? trim($_POST['site_posisi_edit']) : '';

        $ptClean = array();
        for ($i = 0; $i < count($ptArr); $i++) {
            $v = trim((string)$ptArr[$i]);
            if ($v !== '') $ptClean[] = $v;
        }

        if ($nama === '' || count($ptClean) === 0 || $departemen === '' || $lokasi === '') {
            $_SESSION['message'] = 'Data Site belum lengkap.';
            $_SESSION['success'] = false;
            session_write_close();
            header("Location: tabel.php");
            exit;
        }

        // rule MILL PKS
        $pkspOnly = array("PT.PEAK (PKS)", "PT.MSAL (PKS)", "PT.PSAM (PKS)", "PT.WCJU (PKS)");
        if ($departemen === 'MILL PKS') {
            if (!(count($ptClean) === 1 && in_array($ptClean[0], $pkspOnly, true))) {
                $_SESSION['message'] = 'Departemen MILL PKS hanya untuk 1 PT PKS tertentu.';
                $_SESSION['success'] = false;
                session_write_close();
                header("Location: tabel.php");
                exit;
            }
        }

        // posisi menyesuaikan departemen (paksa dari server)
        if ($departemen === 'IT') {
            $allowed = array("IT Support", "KTU");
            if (!in_array($posisi, $allowed, true)) $posisi = '';
        } elseif ($departemen === 'HRD') {
            $posisi = "Staf GA";
        } elseif ($departemen === 'GM') {
            $posisi = "GM";
        } elseif ($departemen === 'MILL PKS') {
            $posisi = "Kepala Mill";
        }

        if ($posisi === '') {
            $_SESSION['message'] = 'Posisi Site tidak valid / belum dipilih.';
            $_SESSION['success'] = false;
            session_write_close();
            header("Location: tabel.php");
            exit;
        }

        // validasi posisi unik exclude id sendiri
        $uniquePos = '';
        if ($departemen === 'GM') $uniquePos = 'GM';
        elseif ($departemen === 'HRD') $uniquePos = 'Staf GA';
        elseif ($departemen === 'MILL PKS') $uniquePos = 'Kepala Mill';
        elseif ($departemen === 'IT' && $posisi === 'KTU') $uniquePos = 'KTU';

        if ($uniquePos !== '') {
            $ptNormCheck = "REPLACE(REPLACE(pt, ', ', ','), ' ,', ',')";
            $orsCheck = array();
            for ($i = 0; $i < count($ptClean); $i++) {
                $pEsc = $koneksi->real_escape_string($ptClean[$i]);
                $orsCheck[] = "FIND_IN_SET('{$pEsc}', {$ptNormCheck}) > 0";
            }
            $condPtCheck = count($orsCheck) ? "(" . implode(" OR ", $orsCheck) . ")" : "0=1";

            $sqlCekPos = "SELECT COUNT(*) AS jml
                          FROM data_karyawan_test
                          WHERE dihapus = 0 AND posisi = ? AND id <> ? AND {$condPtCheck}";
            $jmlPos = 0;

            if ($stmt = $koneksi->prepare($sqlCekPos)) {
                $stmt->bind_param("si", $uniquePos, $id);
                $stmt->execute();
                $res = $stmt->get_result();
                if ($res && $r = $res->fetch_assoc()) $jmlPos = (int)$r['jml'];
                $stmt->close();
            }

            if ($jmlPos > 0) {
                $_SESSION['message'] = 'Posisi "' . $uniquePos . '" sudah ada di salah satu PT yang dipilih.';
                $_SESSION['success'] = false;
                session_write_close();
                header("Location: tabel.php");
                exit;
            }
        }

        $ptString = implode(",", $ptClean);
        $jabatan = $departemen;

        $sqlUp = "UPDATE data_karyawan_test
                  SET nama = ?, pt = ?, departemen = ?, lokasi = ?, posisi = ?, jabatan = ?
                  WHERE id = ? LIMIT 1";
        if ($stmt = $koneksi->prepare($sqlUp)) {
            $stmt->bind_param("ssssssi", $nama, $ptString, $departemen, $lokasi, $posisi, $jabatan, $id);
            if ($stmt->execute()) {
                $_SESSION['message'] = 'Data karyawan Site berhasil diupdate.';
                $_SESSION['success'] = true;
            } else {
                $_SESSION['message'] = 'Gagal update karyawan Site: ' . $stmt->error;
                $_SESSION['success'] = false;
            }
            $stmt->close();
        } else {
            $_SESSION['message'] = 'Gagal prepare update Site: ' . $koneksi->error;
            $_SESSION['success'] = false;
        }

        session_write_close();
        header("Location: tabel.php");
        exit;
    }
}
?>

<?php
// ===== PT LIST USER (support multi PT) =====
$ptUserList = isset($_SESSION['pt']) ? $_SESSION['pt'] : array();

// kalau session pt masih string, amankan:
if (!is_array($ptUserList)) {
    $ptUserList = array_map('trim', explode(',', $ptUserList));
}

// bersihkan spasi & elemen kosong
$ptUserList = array_values(array_filter(array_map('trim', $ptUserList), 'strlen'));

// PT pertama (kalau masih butuh)
$pt_pertama = isset($ptUserList[0]) ? $ptUserList[0] : '';

// helper: user punya PT tertentu?
function userHasPT($ptUserList, $pt)
{
    return in_array($pt, $ptUserList, true);
}

// helper: PT record (string koma) overlap dengan PT user?
function ptIntersectsUser($ptUserList, $ptString)
{
    $arr = array_map('trim', explode(',', (string)$ptString));
    foreach ($arr as $p) {
        if ($p !== '' && in_array($p, $ptUserList, true)) return true;
    }
    return false;
}


?>

<?php
// =========================
// SOFT DELETE KARYAWAN + AKUN AKTIF (deleted=0)
// URL: tabel.php?aksi=hapus_karyawan&sumber=HO|SITE&id=123
// =========================
if (isset($_GET['aksi']) && $_GET['aksi'] === 'hapus_karyawan') {

    unset($_SESSION['message'], $_SESSION['success']);

    // hanya yang editable (atau super) yang boleh delete
    if (!$isSuper && (int)$manajemen_akun_akses !== 2) {
        $_SESSION['message'] = 'Anda tidak punya akses untuk menghapus karyawan.';
        $_SESSION['success'] = false;
        session_write_close();
        header("Location: tabel.php");
        exit;
    }

    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $sumber = isset($_GET['sumber']) ? trim($_GET['sumber']) : '';

    // simpan filter agar balik ke tampilan semula
    $qsBack = array();
    if (isset($_GET['pt']) && trim($_GET['pt']) !== '') $qsBack['pt'] = trim($_GET['pt']);
    if (isset($_GET['lantai']) && trim($_GET['lantai']) !== '') $qsBack['lantai'] = trim($_GET['lantai']);
    $backUrl = "tabel.php" . (count($qsBack) ? ("?" . http_build_query($qsBack)) : "");

    if ($id <= 0 || ($sumber !== 'HO' && $sumber !== 'SITE')) {
        $_SESSION['message'] = 'Permintaan hapus karyawan tidak valid.';
        $_SESSION['success'] = false;
        session_write_close();
        header("Location: " . $backUrl);
        exit;
    }

    // ambil data karyawan (untuk nama + validasi hak akses berbasis PT)
    $namaKaryawan = '';
    $ptKaryawan = '';

    if ($sumber === 'HO') {

        // validasi PT user (jika bukan super)
        if (!$isSuper && !in_array("PT.MSAL (HO)", $ptUserList, true)) {
            $_SESSION['message'] = 'Anda tidak punya akses untuk menghapus karyawan HO.';
            $_SESSION['success'] = false;
            session_write_close();
            header("Location: " . $backUrl);
            exit;
        }

        $sqlSel = "SELECT nama FROM data_karyawan WHERE id = ? AND dihapus = 0 LIMIT 1";
        if ($stmt = $koneksi->prepare($sqlSel)) {
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res && ($r = $res->fetch_assoc())) {
                $namaKaryawan = (string)$r['nama'];
                $ptKaryawan = "PT.MSAL (HO)";
            }
            $stmt->close();
        } else {
            $_SESSION['message'] = 'Gagal prepare select karyawan HO: ' . $koneksi->error;
            $_SESSION['success'] = false;
            session_write_close();
            header("Location: " . $backUrl);
            exit;
        }

    } else { // SITE

        $sqlSel = "SELECT nama, pt FROM data_karyawan_test WHERE id = ? AND dihapus = 0 LIMIT 1";
        if ($stmt = $koneksi->prepare($sqlSel)) {
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res && ($r = $res->fetch_assoc())) {
                $namaKaryawan = (string)$r['nama'];
                $ptKaryawan = (string)$r['pt']; // bisa multi PT: "A,B,C"
            }
            $stmt->close();
        } else {
            $_SESSION['message'] = 'Gagal prepare select karyawan SITE: ' . $koneksi->error;
            $_SESSION['success'] = false;
            session_write_close();
            header("Location: " . $backUrl);
            exit;
        }

        // validasi PT user (jika bukan super) harus overlap
        if (!$isSuper && !ptIntersectsUser($ptUserList, $ptKaryawan)) {
            $_SESSION['message'] = 'Anda tidak punya akses untuk menghapus karyawan SITE pada PT tersebut.';
            $_SESSION['success'] = false;
            session_write_close();
            header("Location: " . $backUrl);
            exit;
        }
    }

    if (trim($namaKaryawan) === '') {
        $_SESSION['message'] = 'Data karyawan tidak ditemukan / sudah dihapus.';
        $_SESSION['success'] = false;
        session_write_close();
        header("Location: " . $backUrl);
        exit;
    }

    // proses soft delete + delete akun aktif (transaction-safe)
    $ok = true;

    $koneksi->autocommit(false);

    // 1) soft delete karyawan
    if ($sumber === 'HO') {
        $sqlUpK = "UPDATE data_karyawan SET dihapus = 1 WHERE id = ? AND dihapus = 0 LIMIT 1";
    } else {
        $sqlUpK = "UPDATE data_karyawan_test SET dihapus = 1 WHERE id = ? AND dihapus = 0 LIMIT 1";
    }

    if ($stmt = $koneksi->prepare($sqlUpK)) {
        $stmt->bind_param("i", $id);
        if (!$stmt->execute()) $ok = false;
        if ($stmt->affected_rows < 1) $ok = false; // pastikan benar-benar terupdate
        $stmt->close();
    } else {
        $ok = false;
    }

    // 2) soft delete akun AKTIF saja (deleted=0) berdasarkan nama
    //    jika ada multi akun aktif dengan nama sama, semuanya ikut deleted=1 (sesuai requirement “aktif saja”)
    if ($ok) {
        $sqlUpA = "UPDATE akun_akses SET deleted = 1 WHERE nama = ? AND deleted = 0";
        if ($stmt = $koneksi->prepare($sqlUpA)) {
            $stmt->bind_param("s", $namaKaryawan);
            if (!$stmt->execute()) $ok = false;
            $stmt->close();
        } else {
            $ok = false;
        }
    }

    if ($ok) {
        $koneksi->commit();
        $_SESSION['message'] = 'Berhasil menghapus.';
        $_SESSION['success'] = true;
    } else {
        $koneksi->rollback();
        $_SESSION['message'] = 'Gagal menghapus karyawan. Silakan coba lagi. (' . $koneksi->error . ')';
        $_SESSION['success'] = false;
    }

    $koneksi->autocommit(true);

    session_write_close();
    header("Location: " . $backUrl);
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Data Akun</title>

    <!-- Bootstrap 5 -->
    <link
        rel="stylesheet"
        href="../../assets/bootstrap-5.3.6-dist/css/bootstrap.min.css" />

    <!-- Bootstrap Icons -->
    <link
        rel="stylesheet"
        href="../../assets/icons/icons-main/font/bootstrap-icons.min.css" />

    <!-- AdminLTE -->
    <link
        rel="stylesheet"
        href="../../assets/adminlte/css/adminlte.css" />

    <!-- OverlayScrollbars -->
    <link
        rel="stylesheet"
        href="../../assets/css/overlayscrollbars.min.css" />

    <!-- Favicon -->
    <link
        rel="icon" type="image/png"
        href="../../assets/img/logo.png" />

    <link
        rel="icon" type="image/png"
        href="../../assets/css/datatables.min.css" />

    <link
        rel="stylesheet"
        href="../../assets/css/datatables.min.css" />

    <link
        rel="stylesheet"
        href="../../assets/css/select2.min.css" />

    <style>
        /* Main Styles */

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f9f9f9;
        }

        @media (min-width: 580px) {
            .custom-body {
                overflow-y: hidden;
            }
        }


        .app-wrapper {
            position: relative;
        }

        .button-navigation-bar {
            background-color: transparent;
            color: white;
            border-radius: 5px;
            border: #f9f9f9 1px solid;
            padding: 8px 12px;
            text-decoration: none;
        }

        .button-navigation-bar:hover {
            background-color: green;
            color: white;
            border: #f9f9f9 1px solid;
        }

        #date {
            margin-right: 10px;
        }

        #clock {
            font-size: 16px;
            color: white;
            margin-right: 20px;
        }

        /* .personalia-menu{
        background:linear-gradient(135deg,#515bd4,#dd2a7b,#F58529);
        transition: all .3s ease;
    } */

        .akun-info {
            right: -300px;
            opacity: 0;
        }

        .aktif {
            right: 0;
            opacity: 1;
            transition: all .3s ease-in-out;
        }

        .display-state {
            display: none;
        }

        .app-sidebar {
            background: <?php echo $bgMenu; ?> !important;
        }

        .navbar {
            background: <?php echo $bgNav; ?> !important;
        }

        h2,
        h3 {
            color: #2c3e50;
            text-align: center;
            margin-bottom: 25px;
        }

        .app-main {
            display: flex;
            align-items: center;
            margin-top: 40px;
        }

        /* .custom-main {
            overflow-y: hidden !important;
        } */

        /* style table */

        .table-wrapper {
            width: 97%;
            height: auto;
            overflow-x: auto;
            margin: 20px 0;
            border-radius: 10px;
            padding: 10px;
        }

        th,
        td,
        table tbody tr td .btn-sm {
            font-size: .9rem;
        }

        th,
        td {
            text-align: center !important;
        }

        .popup-box {
            display: none;
        }

        .popup-bg {
            display: none;
        }

        .aktifPopup {
            display: flex;
        }

        .popupInput {
            width: 100%;
            padding: 25px 30px;
            border-radius: 10px;
        }

        .custom-popup-input {
            height: max-content;
            align-self: center;
            z-index: 999;
            width: max-content;
            min-width: 500px;
            left: 35.5%;
            top: 15vh;
        }

        .custom-popup-detail {
            height: max-content;
            align-self: center;
            z-index: 999;
            width: max-content;
            min-width: 500px;
            left: 20.5%;
            top: 15vh;
        }

        /* Biar select2 mirip bootstrap */
        .select2-container .select2-selection--single {
            height: calc(2.25rem + 2px) !important;
            /* sama kayak .form-select */
            padding: 0.375rem 0.75rem;
            font-size: 1rem;
            line-height: 1.5;
            border: 1px solid #ced4da;
            border-radius: 0 0.375rem 0.375rem 0;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: calc(2.25rem + 2px);
            right: 0.75rem;
        }

        /* Custom input-group untuk multiple select */
        .custom-multi-group {
            display: flex;
            width: 100%;
        }

        .custom-multi-group .multi-label {
            background-color: #e9ecef;
            border: 1px solid #ced4da;
            border-right: none;
            padding: 0.375rem 0.75rem;
            display: flex;
            align-items: flex-start;
            min-width: 97px;
            border-top-left-radius: 0.375rem;
            border-bottom-left-radius: 0.375rem;
            font-size: 0.875rem;
        }

        .custom-multi-group .multi-select {
            border-top-left-radius: 0;
            border-bottom-left-radius: 0;
            min-height: 120px;
        }

        /* .tabel-judul th:first-child{ width: 4%; text-align: center; } 
        .tabel-judul th:nth-child(2) { width: 6%; }  
        .tabel-judul th:nth-child(3) { width: 10%; }  
        .tabel-judul th:nth-child(4) { width: 20%; }
        .tabel-judul th:nth-child(5) { width: 35%; }
        .tabel-judul th:last-child{ width: 10%; height:100% !important; text-align: center; }   

        .tabel-judul2 th:first-child, .tabel-judul2 th:last-child {
            width: 50%;
        }
        td:first-child {width:4%;}
        td:nth-child(2) {width:6%;}
        td:nth-child(3) {width:10%;}
        td:nth-child(4) {width:20%;}
        td:nth-child(5) {width:35%;}
        td:last-child {width:10%; height:100% !important; text-align: center;} */
        @media (max-width: 1670px) {
            .btn {
                margin-bottom: 5px;
            }
        }

        .bi-list,
        .bi-arrows-fullscreen,
        .bi-fullscreen-exit {
            color: #fff !important;
        }
    </style>
    <style>
        /* Responsive */
        @media (min-width: 1440px) {

            .custom-popup-detail {
                top: 20vh !important;
                left: 30vw !important;
                position: fixed !important;
            }
        }

        @media (min-width: 1025px) {
            .custom-main {
                height: calc(100vh - 130px);
            }
        }

        @media (max-width: 1024px) {
            #res-fullscreen {
                display: none;
            }

            .custom-footer {
                position: absolute !important;
                bottom: 0;
                width: 100vw;
            }

            .custom-main {
                padding-bottom: 100px;
                height: max-content;
                padding-top: 10px;
            }

            /* .dt-orderable-none{
                min-width: 100px;
            } */
            .custom-popup {
                left: 14vw !important;
            }

            .custom-popup-input,
            .custom-popup-detail {
                top: 35vh !important;
                left: 30vw !important;
                position: fixed !important;
            }

            .custom-data-akun {
                display: flex;
                flex-direction: column;
            }
        }

        @media (max-width: 450px) {
            .custom-main {
                width: 100%;
            }

            .custom-popup-input,
            .custom-popup-detail {
                left: 0 !important;
                width: 100vw !important;
                min-width: 0 !important;
            }

            .custom-popup-input canvas {
                width: 100% !important;
            }

        }
    </style>

    <style>
        /* Placeholder Skeleton */
        .skeleton {
            height: 16px;
            width: 100%;
            background: linear-gradient(90deg,
                    #e0e0e0 25%,
                    #f5f5f5 37%,
                    #e0e0e0 63%);
            background-size: 400% 100%;
            animation: skeleton-loading 1.4s ease infinite;
            border-radius: 4px;
        }

        .skeleton-header {
            height: 20px;
        }

        @keyframes skeleton-loading {
            0% {
                background-position: 100% 0;
            }

            100% {
                background-position: -100% 0;
            }
        }
    </style>

    <style>
        /*animista.net*/
        .scale-in-center {
            animation: scale-in-center .3s cubic-bezier(0.250, 0.460, 0.450, 0.940) both;
        }

        @keyframes scale-in-center {
            0% {
                transform: scale(0);
                opacity: 1;
            }

            100% {
                transform: scale(1);
                opacity: 1;
            }
        }

        .fade-in {
            animation: fade-in .3s cubic-bezier(0.390, 0.575, 0.565, 1.000) both;
        }

        @keyframes fade-in {
            0% {
                opacity: 0;
            }

            100% {
                opacity: 1;
            }
        }

        .scale-out-center {
            animation: scale-out-center .3s cubic-bezier(0.550, 0.085, 0.680, 0.530) both;
        }

        @keyframes scale-out-center {
            0% {
                transform: scale(1);
                opacity: 1;
            }

            100% {
                transform: scale(0);
                opacity: 1;
            }
        }

        .fade-out {
            animation: fade-out .3s ease-out both;
        }

        @keyframes fade-out {
            0% {
                opacity: 1;
            }

            100% {
                opacity: 0;
            }
        }

        .slide-in-right {
            animation: slide-in-right 0.5s cubic-bezier(0.250, 0.460, 0.450, 0.940) both;
        }

        @keyframes slide-in-right {
            0% {
                transform: translateX(1000px);
                opacity: 0;
            }

            100% {
                transform: translateX(0);
                opacity: 1;
            }
        }

        .slide-out-right {
            animation: slide-out-right 0.5s cubic-bezier(0.550, 0.085, 0.680, 0.530) both;
        }

        @keyframes slide-out-right {
            0% {
                transform: translateX(0);
                opacity: 1;
            }

            100% {
                transform: translateX(1000px);
                opacity: 0;
            }
        }
    </style>

    <style>
        /* gradient-bg 24s */
        .background-gradasi-biru-ungu {
            background: linear-gradient(to bottom right,
                    #1702d5,
                    #3953f9,
                    #0012ce,
                    #3262ff,
                    #5e74ff);
            background-size: 300% 300%;
            animation: gradient-shift 24s ease infinite;
        }

        @keyframes gradient-shift {
            0% {
                background-position: 0% 50%;

            }

            25% {
                background-position: 100% 50%;
            }

            50% {
                background-position: 100% 0%;
            }

            75% {
                background-position: 50% 0%;
            }

            100% {
                background-position: 0% 50%;
            }
        }
    </style>

    <style>
        /* Scroll */
        .scroll-container {
            height: 100vh;
            /* tinggi penuh layar */
            overflow-y: scroll;
            /* scroll tetap aktif */
            -ms-overflow-style: none;
            /* IE dan Edge */
            scrollbar-width: none;
            /* Firefox */
        }

        .scroll-container::-webkit-scrollbar {
            display: none;
            /* Chrome, Safari, Opera */
        }
    </style>

    <style>
        /* gradient-text 4s */
        .gradient-text {
            font-size: 3rem;
            font-weight: bold;
            background: linear-gradient(to bottom right,
                    #1702d5,
                    #2100a5,
                    #0012ce,
                    #3262ff,
                    #5e74ff);
            background-size: 300% 300%;
            animation: gradient-shift 4s ease infinite;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            /* Untuk Firefox */
            background-clip: text;
            color: transparent;
        }

        @keyframes gradient-shift {
            0% {
                background-position: 0% 50%;
            }

            25% {
                background-position: 100% 50%;
            }

            50% {
                background-position: 100% 0%;
            }

            75% {
                background-position: 50% 0%;
            }

            100% {
                background-position: 0% 50%;
            }
        }
    </style>
    <style>
        /* ===== PATCH RESPONSIVE POPUP (tanpa ubah struktur/variabel) ===== */

        /* overlay selalu full layar */
        #popupBG {
            position: fixed !important;
            inset: 0 !important;
            width: 100vw !important;
            height: 100vh !important;
        }

        /* =========================
   TABLET & HP (<= 1024px)
   ========================= */
        @media (max-width: 1024px) {

            /* popup input & detail: center layar + tidak ikut layout parent */
            .custom-popup-input,
            .custom-popup-detail {
                position: fixed !important;
                left: 50% !important;
                top: 50% !important;
                transform: translate(-50%, -50%) !important;

                width: calc(100vw - 24px) !important;
                min-width: 0 !important;
                max-width: calc(100vw - 24px) !important;
                max-height: calc(100vh - 24px) !important;

                margin: 0 !important;
                z-index: 1000 !important;
            }

            /* container isi popup bisa scroll internal */
            #popupBoxInput>div,
            #popupBoxDetail>div,
            #popupBoxKaryawanInput>div {
                width: 100% !important;
                max-width: 100% !important;
                max-height: calc(100vh - 24px) !important;
                overflow-y: auto !important;
                overflow-x: hidden !important;
                box-sizing: border-box !important;
            }

            #popupBoxInput>div,
            #popupBoxKaryawanInput>div {
                max-width: 620px !important;
            }

            /* popup detail: paksa ikut layar */
            #popupBoxDetail>div {
                width: 100% !important;
            }

            /* wrapper isi detail jangan max-content */
            .custom-data-akun {
                width: 100% !important;
                display: flex !important;
                flex-direction: column !important;
                gap: 10px !important;
                box-sizing: border-box !important;
            }

            /* kolom kiri info + kolom edit jadi full width */
            .custom-data-akun>.col-6,
            #colEditAkun {
                width: 100% !important;
                max-width: 100% !important;
                flex: 0 0 100% !important;
                box-sizing: border-box !important;
            }

            /* di dalam form edit, kolom2 jadi stack */
            #colEditAkun .row>.col-6 {
                width: 100% !important;
                max-width: 100% !important;
                flex: 0 0 100% !important;
            }

            /* kurangi padding form biar muat */
            .popupInput {
                padding: 14px !important;
            }

            #colEditAkun .popupInput {
                padding: 12px 0 !important;
            }

            #popupBoxInput .autograph-container,
            #popupBoxDetail .col-12.d-flex.flex-column.align-items-start {
                width: 100% !important;
            }

            #popupBoxInput input[type="file"],
            #popupBoxDetail input[type="file"] {
                width: 100% !important;
                box-sizing: border-box !important;
            }

            /* teks detail yang panjang tidak bikin layout melebar */
            #detailUsername,
            #detailPassword,
            #detailEmail,
            #detailPT,
            #detailNama,
            #detailNIK,
            #detailHakAkses {
                word-break: break-word !important;
                white-space: normal !important;
            }
        }

        /* =========================
   HP kecil (<= 576px)
   ========================= */
        @media (max-width: 576px) {

            .custom-popup-input,
            .custom-popup-detail {
                width: calc(100vw - 12px) !important;
                max-width: calc(100vw - 12px) !important;
                max-height: calc(100vh - 50px) !important;
            }

            #popupBoxInput>div,
            #popupBoxDetail>div {
                padding: 8px !important;
                border-radius: 10px !important;
                max-height: calc(100vh - 100px) !important;
            }

            .popupInput {
                padding: 10px !important;
            }

            .custom-data-akun {
                padding-left: 4px !important;
                padding-right: 4px !important;
                gap: 8px !important;
            }

            .custom-data-akun h6,
            .custom-data-akun p {
                font-size: .9rem !important;
            }

            /* baris detail kiri biar wrap rapi */
            .custom-data-akun .d-flex.justify-content-start {
                flex-wrap: wrap !important;
                align-items: flex-start !important;
                gap: 2px !important;
            }

            /* tombol aksi popup tetap enak disentuh */
            #tombolClosePopup,
            #tombolClosePopup2 {
                min-width: 34px;
                min-height: 34px;
            }
        }

        /* ===== FIX FINAL SIGNATURE PAD SIZE (ONLY SIGNATURE) ===== */
        #signature,
        #signature_edit {
            width: 500px !important;
            height: 200px !important;
            min-width: 500px !important;
            max-width: 500px !important;
            min-height: 200px !important;
            max-height: 200px !important;
            display: block !important;
            touch-action: none !important;
            background: #fff !important;
            box-sizing: border-box !important;
        }

        /* HP */
        @media (max-width: 576px) {

            #signature,
            #signature_edit {
                width: 300px !important;
                height: 200px !important;
                min-width: 300px !important;
                max-width: 300px !important;
                min-height: 200px !important;
                max-height: 200px !important;
            }
        }
    </style>
</head>

<body class="layout-fixed sidebar-expand-lg bg-body-tertiary custom-body">

    <script src="../../assets/js/jquery-3.7.1.min.js"></script>
    <script src="../../assets/js/datatables.min.js"></script>

    <div class="app-wrapper">

        <!--begin::Header-->
        <nav class="app-header navbar navbar-expand bg-body sticky-top" style="margin-bottom: 0; z-index: 5;">
            <!--begin::Container-->
            <div class="container-fluid">
                <!--begin::Start Navbar Links-->
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button">
                            <i class="bi bi-list"></i>
                        </a>
                    </li>

                </ul>
                <!--end::Start Navbar Links-->
                <!--begin::End Navbar Links-->
                <ul class="navbar-nav ms-auto">
                    <!--begin::Fullscreen Toggle-->
                    <li class="nav-item">
                        <a id="res-fullscreen" class="nav-link" href="#" data-lte-toggle="fullscreen">
                            <i data-lte-icon="maximize" class="bi bi-arrows-fullscreen"></i>
                            <i data-lte-icon="minimize" class="bi bi-fullscreen-exit" style="display: none"></i>
                        </a>
                    </li>
                    <!--end::Fullscreen Toggle-->
                    <!--begin::Clock-->
                    <li class="nav-item pt-2">
                        <span id="date" class="text-white fw-bold" style="min-width: 120px; text-align: right;"></span>
                        <span id="clock" class="text-white fw-bold" style="min-width: 75px; text-align: right;"></span>
                    </li>
                    <!--end::Clock-->

                    <li class="personalia-menu nav-item me-3 rounded">
                        <i id="personaliaBtn" class="bi bi-brush-fill btn fw-bold text-white" style="box-shadow:none;"></i>
                    </li>

                    <div class="ms-auto me-2 position-relative">
                        <i id="tombolAkun" class="bi bi-person-circle btn fw-bold text-white border border-white"></i>
                        <div id="akunInfo" class="akun-info card position-absolute bg-white p-2 display-state" style="width:300px;height:160px;top:50px;right:0;transition:all .2s ease-in-out">
                            <div class=" d-flex p-3 align-items-center justify-content-around border-bottom">
                                <i class="bi bi-person-circle text-primary" style="font-size:44px"></i>
                                <div class="">
                                    <h6><?= htmlspecialchars($_SESSION['nama']) ?></h6>
                                    <h6 class="" style="color:gray"><?= htmlspecialchars($_SESSION['hak_akses']) ?></h6>
                                </div>
                            </div>
                            <a href="../../logout.php" id="logoutTombol" class="btn btn-outline-danger fw-bold ps-3 gap-2 mt-2 d-flex" onclick="return confirm('Yakin ingin logout?')" title="Logout">
                                <i class="bi bi-box-arrow-right fw-bolder"></i>
                                <p class="m-0">Logout</p>
                            </a>
                        </div>
                    </div>

                </ul>
                <!--end::End Navbar Links-->
            </div>
            <!--end::Container-->
        </nav>
        <!--end::Header-->

        <!--Awal::Sidebar-->
        <aside class="app-sidebar shadow" data-bs-theme="dark">
            <div class="sidebar-brand" style="border:none;">
                <a href="" class="brand-link">
                    <img
                        src="../../assets/img/logo.png"
                        alt="MSAL Logo"
                        class="brand-image opacity-75 shadow" />
                    <span class="brand-text fw-bold">SIBARA</span>
                </a>
            </div>
            <div class="sidebar-wrapper">
                <nav class="mt-2">
                    <ul
                        class="nav sidebar-menu flex-column"
                        data-lte-toggle="treeview"
                        role="menu"
                        data-accordion="false">
                        <?php if ($_SESSION['hak_akses'] === 'Admin'): ?>
                            <li class="nav-item">
                                <a href="../../index.php" class="nav-link" aria-disabled="true">
                                    <i class="bi bi-house-fill"></i>
                                    <p>
                                        Dashboard
                                    </p>
                                </a>
                            </li>

                            <li class="nav-header">
                                LIST BERITA ACARA
                            </li>
                            <!-- List BA Kerusakan -->
                            <li class="nav-item">
                                <a href="../../ba_kerusakan-fix/ba_kerusakan.php" class="nav-link">
                                    <i class="nav-icon bi bi-newspaper"></i>
                                    <p>
                                        BA Kerusakan
                                    </p>
                                </a>
                            </li>
                            <?php if (userHasPT($ptUserList, "PT.MSAL (HO)")) { ?>

                                <!-- List BA Pengembalian -->
                                <!-- <li class="nav-item">
                                    <a href="../../ba_pengembalian/ba_pengembalian.php" class="nav-link">
                                    <i class="nav-icon bi bi-newspaper"></i>
                                    <p>
                                        BA Pengembalian
                                    </p>
                                    </a>
                                </li> -->
                            <?php } ?>
                            <li class="nav-item">
                                <a href="../../ba_pemutihan/ba_pemutihan.php" class="nav-link" aria-disabled="true">
                                    <i class="nav-icon bi bi-newspaper"></i>
                                    <p>BA Pemutihan</p>
                                </a>
                            </li>

                            <li class="nav-item">
                                <a href="../../ba_pengembalian/ba_pengembalian.php" class="nav-link" aria-disabled="true">
                                    <i class="nav-icon bi bi-newspaper"></i>
                                    <p>BA Pengembalian</p>
                                </a>
                            </li>

                            <?php if (userHasPT($ptUserList, "PT.MSAL (HO)")) { ?>
                                <!-- List BA Serah Terima -->
                                <li class="nav-item">
                                    <a href="../../ba_serah-terima-asset/ba_serah-terima-asset.php" class="nav-link">
                                        <i class="nav-icon bi bi-newspaper"></i>
                                        <p>
                                            BA Serah Terima Asset Inventaris
                                        </p>
                                    </a>
                                </li>
                            <?php } ?>
                            <?php
                            // if (userHasPT($ptUserList, "PT.MSAL (HO)") || userHasPT($ptUserList, "PT.MSAL (SITE)")){ 
                            ?>
                            <li class="nav-item">
                                <a href="../../ba_mutasi/ba_mutasi.php" class="nav-link">
                                    <i class="nav-icon bi bi-newspaper"></i>
                                    <p>
                                        BA Mutasi
                                    </p>
                                </a>
                            </li>
                            <?php
                            //} 
                            ?>
                            <!-- <li class="nav-item">
                <a href="#" class="nav-link">
                <i class="nav-icon bi bi-newspaper"></i>
                <p>
                    BA Peminjaman
                </p>
                </a>
            </li>
            <li class="nav-item">
                <a href="#" class="nav-link">
                <i class="nav-icon bi bi-newspaper"></i>
                <p>
                    BA Mutasi
                </p>
                </a>
            </li> -->
                            <!-- <li class="nav-header">
                LAIN LAIN
            </li>
            <li class="nav-item">
                <a href="#" class="nav-link">
                <i class="nav-icon bi bi-newspaper"></i>
                <p>
                    List Lainnya
                    <i class="nav-arrow bi bi-chevron-right"></i>
                </p>
                </a>
                <ul class="nav nav-treeview">
                    <li class="nav-item">
                        <a href="#" class="nav-link">
                        <i class="nav-icon bi bi-pc-display"></i>
                        <p>
                            Job Order
                        </p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#" class="nav-link">
                        <i class="nav-icon bi bi-pc-display"></i>
                        <p>
                            Work Order
                        </p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#" class="nav-link">
                        <i class="nav-icon bi bi-file-earmark-text-fill"></i>
                        <p>
                            Pengajuan Dokumen
                        </p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#" class="nav-link">
                        <i class="nav-icon bi bi-stickies-fill"></i>
                        <p>
                            Notulensi
                        </p>
                        </a>
                    </li>
                </ul>
            </li> -->


                        <?php endif; ?>
                        <?php if ($_SESSION['hak_akses'] === 'Admin' || $_SESSION['hak_akses'] === 'User'): ?>
                            <li class="nav-header">
                                USER
                            </li>
                            <!-- <?php if ($_SESSION['hak_akses'] === 'Admin'): ?>
            <li class="nav-item">
                <a href="../../personal/status.php" class="nav-link">
                <i class="nav-icon bi bi-clipboard2-fill"></i>
                <p>
                    Status Approval BA
                </p>
                </a>
            </li>
            <?php endif; ?> -->
                            <li class="nav-item position-relative">
                                <a href="../../personal/approval.php" class="nav-link">
                                    <i class="nav-icon bi bi-clipboard2-check"></i>
                                    <p>
                                        Approve BA

                                    </p>
                                    <?php if ($jumlah_approval_notif > 0): ?>
                                        <span class="position-absolute translate-middle badge rounded-pill bg-danger" style="right: 0;top:20px">
                                            <?= $jumlah_approval_notif ?>
                                        </span>
                                    <?php endif; ?>
                                </a>
                            </li>
                            <!-- <li class="nav-item">
                <a href="../../personal/riwayat.php" class="nav-link">
                <i class="nav-icon bi bi-clipboard2-data"></i>
                <p>
                    Riwayat Approval
                </p>
                </a>
            </li> -->
                        <?php endif; ?>
                        <?php if ($showDataAkunMenu): ?>
                            <li class="nav-header">
                                MASTER
                            </li>
                            <li class="nav-item">
                                <a href="#" class="nav-link">
                                    <i class="nav-icon bi bi-person-circle text-white"></i>
                                    <p class="text-white">
                                        Data Akun
                                    </p>
                                </a>
                            </li>
                        <?php endif; ?>

                    </ul>

                    </ul>

                </nav>
            </div>
        </aside>
        <!--Akhir::Sidebar-->

        <?php
        include '../../koneksi.php';

        $filterPT = isset($_GET['pt']) ? trim($_GET['pt']) : '';

        if (!$isSuper) {
            if ($filterPT !== '' && !in_array($filterPT, $ptUserList, true)) {
                $filterPT = '';
            }
        }
        $filterLantai = isset($_GET['lantai']) ? $_GET['lantai'] : '';

        $filtersMSALHO  = [];
        $filtersMSALPKS  = [];
        $filtersMSALSITE = [];
        $filtersPSAMPKS  = [];
        $filtersPSAMSITE = [];
        $filtersMAPA = [];
        $filtersPEAKPKS = [];
        $filtersPEAKSITE = [];
        $filtersROPALANGKARAYA = [];
        $filtersROSAMPIT = [];
        $filtersWCJUSITE = [];
        $filtersWCJUPKS = [];

        if (!empty($filterPT)) {
            $pt = $koneksi->real_escape_string($filterPT);

            if ($pt === "PT.MSAL (HO)") {
                $filtersMSALHO[] = "aamsalho.pt = 'PT.MSAL (HO)'";
            } elseif ($pt === "PT.MSAL (PKS)") {
                $filtersMSALPKS[] = "aamsalho.pt = 'PT.MSAL (PKS)'";
            } elseif ($pt === "PT.MSAL (SITE)") {
                $filtersMSALSITE[] = "aamsalho.pt = 'PT.MSAL (SITE)'";
            } elseif ($pt === "PT.PSAM (PKS)") {
                $filtersPSAMPKS[] = "aamsalho.pt = 'PT.PSAM (PKS)'";
            } elseif ($pt === "PT.PSAM (SITE)") {
                $filtersPSAMSITE[] = "aamsalho.pt = 'PT.PSAM (SITE)'";
            } elseif ($pt === "PT.MAPA") {
                $filtersMAPA[] = "aamsalho.pt = 'PT.MAPA'";
            } elseif ($pt === "PT.PEAK (PKS)") {
                $filtersPEAKPKS[] = "aamsalho.pt = 'PT.PEAK (PKS)'";
            } elseif ($pt === "PT.PEAK (SITE)") {
                $filtersPEAKSITE[] = "aamsalho.pt = 'PT.PEAK (SITE)'";
            } elseif ($pt === "RO PALANGKARAYA") {
                $filtersROPALANGKARAYA[] = "aamsalho.pt = 'RO PALANGKARAYA'";
            } elseif ($pt === "RO SAMPIT") {
                $filtersROSAMPIT[] = "aamsalho.pt = 'RO SAMPIT'";
            } elseif ($pt === "PT.WCJU (SITE)") {
                $filtersWCJUSITE[] = "aamsalho.pt = 'PT.WCJU (SITE)'";
            } elseif ($pt === "PT.WCJU (PKS)") {
                $filtersWCJUPKS[] = "aamsalho.pt = 'PT.WCJU (PKS)'";
            }
        }

        // if(!empty($filterLantai)){
        //     $filtersMSALHO[]= "dk.lantai = '" . $koneksi->real_escape_string($filterLantai) . "'";
        // }

        $filtersMSALHO[]            = "aamsalho.hak_akses != 'Super Admin'";
        $filtersMSALPKS[]           = "aamsalho.hak_akses != 'Super Admin'";
        $filtersMSALSITE[]          = "aamsalho.hak_akses != 'Super Admin'";
        $filtersPSAMPKS[]          = "aamsalho.hak_akses != 'Super Admin'";
        $filtersPSAMSITE[]          = "aamsalho.hak_akses != 'Super Admin'";
        $filtersMAPA[]              = "aamsalho.hak_akses != 'Super Admin'";
        $filtersPEAKPKS[]           = "aamsalho.hak_akses != 'Super Admin'";
        $filtersPEAKSITE[]          = "aamsalho.hak_akses != 'Super Admin'";
        $filtersROPALANGKARAYA[]    = "aamsalho.hak_akses != 'Super Admin'";
        $filtersROSAMPIT[]          = "aamsalho.hak_akses != 'Super Admin'";
        $filtersWCJUSITE[]          = "aamsalho.hak_akses != 'Super Admin'";
        $filtersWCJUPKS[]           = "aamsalho.hak_akses != 'Super Admin'";


        $whereMSALHO            = $filtersMSALHO ? "WHERE " . implode(" AND ", $filtersMSALHO) . "AND aamsalho.deleted = 0" : "";
        $whereMSALPKS           = $filtersMSALPKS ? "WHERE " . implode(" AND ", $filtersMSALPKS) . "AND aamsalho.deleted = 0" : "";
        $whereMSALSITE          = $filtersMSALSITE ? "WHERE " . implode(" AND ", $filtersMSALSITE) . "AND aamsalho.deleted = 0" : "";
        $wherePSAMPKS           = $filtersPSAMPKS ? "WHERE " . implode(" AND ", $filtersPSAMPKS) . "AND aamsalho.deleted = 0" : "";
        $wherePSAMSITE          = $filtersPSAMSITE ? "WHERE " . implode(" AND ", $filtersPSAMSITE) . "AND aamsalho.deleted = 0" : "";
        $whereMAPA              = $filtersMAPA ? "WHERE " . implode(" AND ", $filtersMAPA) . "AND aamsalho.deleted = 0" : "";
        $wherePEAKPKS           = $filtersPEAKPKS ? "WHERE " . implode(" AND ", $filtersPEAKPKS) . "AND aamsalho.deleted = 0" : "";
        $wherePEAKSITE          = $filtersPEAKSITE ? "WHERE " . implode(" AND ", $filtersPEAKSITE) . "AND aamsalho.deleted = 0" : "";
        $whereROPALANGKARAYA    = $filtersROPALANGKARAYA ? "WHERE " . implode(" AND ", $filtersROPALANGKARAYA) . "AND aamsalho.deleted = 0" : "";
        $whereROSAMPIT          = $filtersROSAMPIT ? "WHERE " . implode(" AND ", $filtersROSAMPIT) . "AND aamsalho.deleted = 0" : "";
        $whereWCJUSITE          = $filtersWCJUSITE ? "WHERE " . implode(" AND ", $filtersWCJUSITE) . "AND aamsalho.deleted = 0" : "";
        $whereWCJUPKS           = $filtersWCJUPKS ? "WHERE " . implode(" AND ", $filtersWCJUPKS) . "AND aamsalho.deleted = 0" : "";

        $baseQuery = "
    SELECT
        aamsalho.id,
        aamsalho.username,
        aamsalho.password,
        aamsalho.email,
        aamsalho.nik,
        aamsalho.pt,
        aamsalho.nama,
        aamsalho.hak_akses,
        aamsalho.autograph,
        aamsalho.deleted,
        aamsalho.manajemen_akun_akses,
        dk.jabatan AS jabatan_karyawan,
        dk.posisi AS posisi_karyawan, 
        dk.departemen AS departemen_karyawan,
        dk.lantai AS lantai
    FROM akun_akses aamsalho
    LEFT JOIN data_karyawan dk
        ON aamsalho.nama = dk.nama
";

        // normalisasi string koma untuk FIND_IN_SET
        $ptNorm = "REPLACE(REPLACE(aamsalho.pt, ', ', ','), ' ,', ',')";

        $conds = array();
        $conds[] = "aamsalho.deleted = 0";
        $conds[] = "aamsalho.hak_akses != 'Super Admin'";

        // jika pilih 1 PT tertentu
        if ($filterPT !== '') {
            $ptEsc = $koneksi->real_escape_string($filterPT);
            $conds[] = "FIND_IN_SET('{$ptEsc}', {$ptNorm}) > 0";
        } else {
            // kalau user biasa & filter kosong => tampilkan semua PT milik user
            if (!$isSuper) {
                $ors = array();
                foreach ($ptUserList as $p) {
                    $pEsc = $koneksi->real_escape_string($p);
                    $ors[] = "FIND_IN_SET('{$pEsc}', {$ptNorm}) > 0";
                }
                $conds[] = count($ors) ? "(" . implode(" OR ", $ors) . ")" : "0=1";
            }
        }

        $where = "WHERE " . implode(" AND ", $conds);

        $query = $baseQuery . " " . $where . " ORDER BY aamsalho.nama ASC";
        $resultHasil = $koneksi->query($query);

        // =========================
        // DATA KARYAWAN (gabungan dari data_karyawan + data_karyawan_test)
        // Support PHP 5.6
        // =========================

        // Kondisi untuk data_karyawan (HO)
        $condsKaryawanHO = array();
        $condsKaryawanHO[] = "dkho.dihapus = 0";

        // data_karyawan PT nya selalu HO
        if ($filterPT !== '' && $filterPT !== 'PT.MSAL (HO)') {
            $condsKaryawanHO[] = "0=1";
        }

        // jika bukan super admin, batasi hanya PT yg user punya
        if (!$isSuper && !in_array("PT.MSAL (HO)", $ptUserList, true)) {
            $condsKaryawanHO[] = "0=1";
        }

        $whereKaryawanHO = "WHERE " . implode(" AND ", $condsKaryawanHO);

        // Kondisi untuk data_karyawan_test (multi PT)
        $condsKaryawanTest = array();
        $condsKaryawanTest[] = "dkt.dihapus = 0";

        // normalisasi koma+spasi supaya FIND_IN_SET aman
        $ptNormTest = "REPLACE(REPLACE(dkt.pt, ', ', ','), ' ,', ',')";

        if ($filterPT !== '') {
            $ptEsc = $koneksi->real_escape_string($filterPT);
            $condsKaryawanTest[] = "FIND_IN_SET('{$ptEsc}', {$ptNormTest}) > 0";
        } else {
            // kalau user biasa & filter kosong => tampilkan semua PT milik user
            if (!$isSuper) {
                $ors = array();
                for ($i = 0; $i < count($ptUserList); $i++) {
                    $pEsc = $koneksi->real_escape_string($ptUserList[$i]);
                    $ors[] = "FIND_IN_SET('{$pEsc}', {$ptNormTest}) > 0";
                }
                $condsKaryawanTest[] = count($ors) ? "(" . implode(" OR ", $ors) . ")" : "0=1";
            }
        }

        $whereKaryawanTest = "WHERE " . implode(" AND ", $condsKaryawanTest);

        // UNION gabungan
        $queryKaryawan = "
            (SELECT
                dkho.id,
                dkho.nama,
                dkho.nik,
                'PT.MSAL (HO)' AS pt,
                dkho.lantai AS lantai,
                dkho.jabatan AS jabatan,
                dkho.departemen AS departemen,
                dkho.posisi AS posisi,
                '' AS lokasi,
                CONCAT(dkho.jabatan, ' - ', dkho.departemen) AS jabatan_tampil,
                'HO' AS sumber
            FROM data_karyawan dkho
            {$whereKaryawanHO})
            UNION ALL
            (SELECT
                dkt.id,
                dkt.nama,
                dkt.nik,
                dkt.pt AS pt,
                '' AS lantai,
                '' AS jabatan,
                dkt.departemen AS departemen,
                dkt.posisi AS posisi,
                dkt.lokasi AS lokasi,
                CONCAT(dkt.posisi, ' (', dkt.departemen, ')') AS jabatan_tampil,
                'SITE' AS sumber
            FROM data_karyawan_test dkt
            {$whereKaryawanTest})
            ORDER BY nama ASC
        ";

        $resultKaryawan = $koneksi->query($queryKaryawan);

        // $selectedPT = $_POST['pt'] ?? '';
        $selectedPT = isset($_POST['pt']) ? $_POST['pt'] : '';

        $namaOptions = [];
        if ($selectedPT === "PT.MSAL (HO)") {
            $query = "
        SELECT dk.nama 
        FROM data_karyawan dk
        WHERE dk.nama NOT IN (SELECT nama FROM akun_akses WHERE pt = 'PT.MSAL (HO)')
        ORDER BY dk.nama ASC
    ";
            $res = $koneksi->query($query);
            while ($row = $res->fetch_assoc()) {
                $namaOptions[] = $row['nama'];
            }
        }

        function statusBadge($hakAkses)
        {
            $role = strtolower(trim((string)$hakAkses));
            if ($role === 'user') {
                return "<span class='border fw-bold bg-success-subtle border-success-subtle text-success' style='border-radius:6px; padding:6px 12px;'>User</span>";
            } elseif ($role === 'admin') {
                return "<span class='border fw-bold bg-success border-success text-white' style='border-radius:6px; padding:6px 12px;'>Admin</span>";
            } else {
                return "<span class='border fw-bold bg-info border-success text-white' style='border-radius:6px; padding:6px 12px;'><i class='bi bi-question-circle fs-6'></span>";
            }
        }

        ?>

        <!--Awal::Main Content-->
        <main id="custom-main" class="app-main custom-main">
            <section class="table-wrapper bg-white position-relative overflow-visible" style="width: 97%; margin: 20px 0; padding: 10px;">
                <?php
                // =========================
                // NORMALISASI FLASH MESSAGE (PHP 5.6 SAFE)
                // =========================

                // 1) Kalau ada login_success tapi belum ada message/success -> pindahkan ke message/success
                if (!isset($_SESSION['message']) && isset($_SESSION['login_success'])) {
                    $_SESSION['message'] = $_SESSION['login_success'];
                    $_SESSION['success'] = true;
                    unset($_SESSION['login_success']);
                }

                // (opsional) kalau kamu punya login_error di file login
                if (!isset($_SESSION['message']) && isset($_SESSION['login_error'])) {
                    $_SESSION['message'] = $_SESSION['login_error'];
                    $_SESSION['success'] = false;
                    unset($_SESSION['login_error']);
                }

                // 2) Kalau kamu nanti pakai flash_message/flash_success dari file lain -> dukung juga
                if (!isset($_SESSION['message']) && isset($_SESSION['flash_message'])) {
                    $_SESSION['message'] = $_SESSION['flash_message'];
                    $_SESSION['success'] = isset($_SESSION['flash_success']) ? (bool)$_SESSION['flash_success'] : false;
                    unset($_SESSION['flash_message'], $_SESSION['flash_success']);
                }
                ?>

                <?php if (isset($_SESSION['message'])): ?>
                    <?php if (!empty($_SESSION['success'])): ?>
                        <div class="w-100 d-flex justify-content-center position-absolute" style="height: max-content;">
                            <div class="d-flex p-0 alert alert-success border-0 text-center fw-bold mb-0 position-absolute fade-in infoin-approval" style="transition: opacity 0.5s ease;right:20px;width:max-content;height:max-content; z-index:9999;">
                                <div class="d-flex justify-content-center align-items-center bg-success pe-2 ps-2 rounded-start text-white fw-bolder">
                                    <i class="bi bi-check-lg"></i>
                                </div>
                                <p class="p-2 m-0" style="font-weight: 500;"><?= htmlspecialchars($_SESSION['message']); ?></p>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="w-100 d-flex justify-content-center position-absolute" style="height: max-content;">
                            <div class="d-flex p-0 alert alert-danger border-0 text-center fw-bold mb-0 position-absolute fade-in infoin-approval" style="transition: opacity 0.5s ease;right:20px;width:max-content;height:max-content; z-index:9999;">
                                <div class="d-flex justify-content-center align-items-center bg-danger pe-2 ps-2 rounded-start text-white fw-bolder">
                                    <i class="bi bi-x-lg"></i>
                                </div>
                                <p class="p-2 m-0" style="font-weight: 500;"><?= htmlspecialchars($_SESSION['message']); ?></p>
                            </div>
                        </div>
                    <?php endif; ?>
                    <?php unset($_SESSION['message'], $_SESSION['success']); ?>
                <?php endif; ?>
                <div class="row g-3">

                    <!-- =======================
                KOLOM KIRI: DATA AKUN
                ======================= -->
                    <div class="col-12 col-xl-12">
                        <div class="card border-1 shadow-sm">
                            <div class="card-body">

                                <div class="d-flex justify-content-start align-items-center flex-wrap gap-2 mb-2">
                                    <h5 class="m-0">Data Akun</h5>

                                    <?php if (!$notShowDataAkunMenu): ?>
                                        <a href="#" id="tombolInputPopup" class="btn btn-success btn-md rounded-2">
                                            <i class="bi bi-plus-lg"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>

                                <div id="tableSkeleton">
                                    <div class="table-responsive">
                                        <table class="table table-borderless mb-0">
                                            <thead>
                                                <tr>
                                                    <th>
                                                        <div class="skeleton skeleton-header"></div>
                                                    </th>
                                                    <th>
                                                        <div class="skeleton skeleton-header"></div>
                                                    </th>
                                                    <th>
                                                        <div class="skeleton skeleton-header"></div>
                                                    </th>
                                                    <th>
                                                        <div class="skeleton skeleton-header"></div>
                                                    </th>
                                                    <th>
                                                        <div class="skeleton skeleton-header"></div>
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php for ($i = 0; $i < 8; $i++) { ?>
                                                    <tr>
                                                        <td style="border: #e0e0e0 1px solid;">
                                                            <div class="skeleton"></div>
                                                        </td>
                                                        <td style="border: #e0e0e0 1px solid;">
                                                            <div class="skeleton"></div>
                                                        </td>
                                                        <td style="border: #e0e0e0 1px solid;">
                                                            <div class="skeleton"></div>
                                                        </td>
                                                        <td style="border: #e0e0e0 1px solid;">
                                                            <div class="skeleton"></div>
                                                        </td>
                                                        <td style="border: #e0e0e0 1px solid;">
                                                            <div class="skeleton"></div>
                                                        </td>
                                                    </tr>
                                                <?php } ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <div id="tabelUtama" style="display:none;">
                                    <div class="table-responsive">
                                        <table id="myTable" class="table table-bordered table-striped text-center mb-0" style="text-align:center !important;">
                                            <thead class="bg-secondary">
                                                <tr class="tabel-judul">
                                                    <th class="p-3">No</th>
                                                    <th class="p-3">Nama</th>
                                                    <th class="p-3">PT</th>
                                                    <th class="p-3">Hak Akses</th>
                                                    <th class="p-3">Akses Tambahan</th>
                                                    <th class="p-3">Action</th>
                                                </tr>
                                            </thead>

                                            <tbody>
                                                <?php
                                                // (opsional) tetap sama seperti versi awal kamu
                                                $ptWithAdmin = array();
                                                $checkAdminQuery = "SELECT DISTINCT pt FROM akun_akses WHERE hak_akses = 'Admin' AND deleted = 0";
                                                $resAdmin = $koneksi->query($checkAdminQuery);
                                                if ($resAdmin) {
                                                    while ($rowAdmin = $resAdmin->fetch_assoc()) {
                                                        $ptWithAdmin[] = $rowAdmin['pt'];
                                                    }
                                                }

                                                $no = 1;
                                                while ($row = $resultHasil->fetch_assoc()) {

                                                    $hakAksesRow = isset($row['hak_akses']) ? trim((string)$row['hak_akses']) : 'User';

                                                    echo "<tr>";
                                                    echo "<td class='p-3'>{$no}</td>";
                                                    echo "<td class='p-3'>" . htmlspecialchars($row['nama']) . "</td>";
                                                    echo "<td class='p-3'>" . htmlspecialchars($row['pt']) . "</td>";
                                                    echo "<td class='p-3'>" . statusBadge($hakAksesRow) . "</td>";

                                                    // Akses Tambahan
                                                    echo "<td class='p-3'>";
                                                    if ((int)$row['manajemen_akun_akses'] === 1) {
                                                        echo "<span class='badge bg-primary me-1'>Manajemen Akun</span>";
                                                    } elseif ((int)$row['manajemen_akun_akses'] === 2) {
                                                        echo "<span class='badge bg-success me-1'>Manajemen Akun</span>";
                                                    } else {
                                                        echo "-";
                                                    }
                                                    echo "</td>";

                                                    echo "<td class='p-3'>";

                                                    // tombol ubah role (fitur lama)
                                                    if ($showDataAkunMenuEditable && $row['nama'] != $_SESSION['nama']) {

                                                        if ($isSuper || (isset($_SESSION['hak_akses']) && $_SESSION['hak_akses'] == 'Admin')) {

                                                            if ($hakAksesRow === 'Admin') {
                                                                echo "<a class='btn btn-success bg-success-subtle text-success-emphasis btn-sm me-1'
                                                                        href='update_hak_akses.php?id=" . (int)$row['id'] . "&role=User'
                                                                        onclick='return confirm(\"Apakah Anda yakin ingin jadikan admin ini user?\")' title='Jadikan User'>
                                                                        User
                                                                    </a>";
                                                            } else {
                                                                echo "<a class='btn btn-success btn-sm me-1'
                                                                        href='update_hak_akses.php?id=" . (int)$row['id'] . "&role=Admin'
                                                                        onclick='return confirm(\"Apakah Anda yakin ingin jadikan user ini admin?\")' title='Jadikan Admin'>
                                                                        Admin
                                                                    </a>";
                                                            }
                                                        }
                                                    }

                                                    // tombol view (fitur lama)
                                                    echo "<a class='btn btn-secondary btn-sm me-1 tombolDataPopup'
                                                        href='#'
                                                        data-id='" . (int)$row['id'] . "'
                                                        data-username='" . htmlspecialchars($row['username'], ENT_QUOTES, 'UTF-8') . "'
                                                        data-password='" . htmlspecialchars($row['password'], ENT_QUOTES, 'UTF-8') . "'
                                                        data-email='" . htmlspecialchars($row['email'], ENT_QUOTES, 'UTF-8') . "'
                                                        data-pt='" . htmlspecialchars($row['pt'], ENT_QUOTES, 'UTF-8') . "'
                                                        data-nama='" . htmlspecialchars($row['nama'], ENT_QUOTES, 'UTF-8') . "'
                                                        data-nik='" . htmlspecialchars($row['nik'], ENT_QUOTES, 'UTF-8') . "'
                                                        data-hakakses='" . htmlspecialchars($hakAksesRow, ENT_QUOTES, 'UTF-8') . "'
                                                        data-manajemen_akun_akses='" . htmlspecialchars($row['manajemen_akun_akses'], ENT_QUOTES, 'UTF-8') . "'
                                                        data-signature='" . base64_encode($row['autograph']) . "'
                                                        title='Detail Akun'>
                                                        <i class='bi bi-eye-fill'></i>
                                                        </a>";

                                                    // tombol delete (soft delete) (fitur lama, tapi tetap soft delete)
                                                    if (
                                                        $isSuper ||
                                                        (
                                                            $showDataAkunMenuEditable &&
                                                            $row['nama'] != $_SESSION['nama'] &&
                                                            ptIntersectsUser($ptUserList, $row['pt'])
                                                        )
                                                    ) {
                                                        echo "<a class='btn btn-danger btn-sm'
                                                                href='delete.php?id=" . (int)$row['id'] . "'
                                                                onclick='return confirm(\"Apakah Anda yakin ingin menghapus akun ini?\")' title='Hapus Akun'>
                                                                <i class='bi bi-x-lg'></i>
                                                            </a>";
                                                    }

                                                    echo "</td>";
                                                    echo "</tr>";
                                                    $no++;
                                                }
                                                ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>


                    <!-- =======================
                KOLOM KANAN: DATA KARYAWAN
                ======================= -->
                    <div class="col-12 col-xl-12">
                        <div class="card border-1 shadow-sm">
                            <div class="card-body">

                                <div class="d-flex justify-content-start align-items-center flex-wrap gap-2 mb-2">
                                    <h5 class="m-0">Data Karyawan</h5>
                                    <?php if (!$notShowDataAkunMenu): ?>
                                        <!-- tombol tambah karyawan -->
                                        <a href="#" id="tombolInputKaryawanPopup" class="btn btn-success btn-md">
                                            <i class="bi bi-plus-lg"></i>
                                        </a>
                                    <?php endif;     ?>
                                </div>

                                <div class="table-responsive">
                                    <table id="myTableKaryawan" class="table table-bordered table-striped text-center mb-0">
                                        <thead class="bg-secondary">
                                            <tr>
                                                <th class="p-3">No</th>
                                                <th class="p-3">Nama</th>
                                                <th class="p-3">PT</th>
                                                <th class="p-3">Jabatan</th>
                                                <th class="px-5 py-3">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $noK = 1;
                                            if ($resultKaryawan) {
                                                while ($k = $resultKaryawan->fetch_assoc()) {

                                                    $namaK = isset($k['nama']) ? $k['nama'] : '';
                                                    $ptK   = isset($k['pt']) ? $k['pt'] : '';
                                                    $jabK  = isset($k['jabatan_tampil']) ? $k['jabatan_tampil'] : '-';
                                                    if (trim($jabK) === '' || $jabK === ' - ') $jabK = '-';

                                                    echo "<tr>";
                                                    echo "<td class='p-3'>{$noK}</td>";
                                                    echo "<td class='p-3'>" . htmlspecialchars($namaK) . "</td>";
                                                    echo "<td class='p-3'>" . htmlspecialchars($ptK) . "</td>";
                                                    echo "<td class='p-3'>" . htmlspecialchars($jabK) . "</td>";

                                                    // tombol action placeholder
                                                    $sumberK = isset($k['sumber']) ? $k['sumber'] : '';
                                                    $ptK     = isset($k['pt']) ? $k['pt'] : '';
                                                    $namaK   = isset($k['nama']) ? $k['nama'] : '';
                                                    $nikK    = isset($k['nik']) ? $k['nik'] : '';
                                                    $lantaiK = isset($k['lantai']) ? $k['lantai'] : '';
                                                    $deptK   = isset($k['departemen']) ? $k['departemen'] : '';
                                                    $jabKraw = isset($k['jabatan']) ? $k['jabatan'] : '';
                                                    $posK    = isset($k['posisi']) ? $k['posisi'] : '';
                                                    $lokK    = isset($k['lokasi']) ? $k['lokasi'] : '';

                                                    $canDeleteKaryawan = ($isSuper || ((int)$manajemen_akun_akses === 2));

                                                    $hrefDel = "tabel.php?aksi=hapus_karyawan&sumber=" . urlencode($sumberK) . "&id=" . (int)$k['id'];
                                                    if (!empty($filterPT)) {
                                                        $hrefDel .= "&pt=" . urlencode($filterPT);
                                                    }
                                                    if (!empty($filterLantai)) {
                                                        $hrefDel .= "&lantai=" . urlencode($filterLantai);
                                                    }

                                                    echo "<td class='p-3'>
                                                        <a href='#'
                                                        class='btn btn-secondary btn-sm me-1 tombolKaryawanPopup'
                                                        data-id='" . (int)$k['id'] . "'
                                                        data-sumber='" . htmlspecialchars($sumberK, ENT_QUOTES, 'UTF-8') . "'
                                                        data-nama='" . htmlspecialchars($namaK, ENT_QUOTES, 'UTF-8') . "'
                                                        data-nik='" . htmlspecialchars($nikK, ENT_QUOTES, 'UTF-8') . "'
                                                        data-pt='" . htmlspecialchars($ptK, ENT_QUOTES, 'UTF-8') . "'
                                                        data-lantai='" . htmlspecialchars($lantaiK, ENT_QUOTES, 'UTF-8') . "'
                                                        data-departemen='" . htmlspecialchars($deptK, ENT_QUOTES, 'UTF-8') . "'
                                                        data-jabatan='" . htmlspecialchars($jabKraw, ENT_QUOTES, 'UTF-8') . "'
                                                        data-posisi='" . htmlspecialchars($posK, ENT_QUOTES, 'UTF-8') . "'
                                                        data-lokasi='" . htmlspecialchars($lokK, ENT_QUOTES, 'UTF-8') . "'
                                                        title='Detail Karyawan'>
                                                        <i class='bi bi-eye-fill'></i>
                                                        </a>";

                                                        if ($canDeleteKaryawan) {
                                                        echo "<a href='" . $hrefDel . "' class='btn btn-danger btn-sm'
                                                            onclick=\"return confirm('Yakin ingin menghapus karyawan ini? Akun aktifnya juga akan dihapus.');\"
                                                            title='Hapus Karyawan'>
                                                            <i class='bi bi-x-lg'></i>
                                                        </a>";
                                                    }
                                                    echo "</td>";

                                                    echo "</tr>";
                                                    $noK++;
                                                }
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>

                            </div>
                        </div>
                    </div>

                </div>
            </section>

            <div id="popupBoxInput" class="custom-popup-input popup-box justify-content-center position-absolute ">

                <div class="d-flex bg-white rounded-1 flex-column justify-content-start align-items-center p-2" style="height: max-content;align-self: center;z-index: 9;max-width: 600px;">

                    <div class="w-100 d-flex justify-content-between mb-2" style="height: max-content;">
                        <h4 class="m-0 p-0">Input Akun Baru</h4>
                        <a id="tombolClosePopup" class='btn btn-danger btn-sm' href='#'><i class="bi bi-x-lg"></i></a>
                    </div>
                    <form method="post" class="popupInput d-flex p-0 gap-2 flex-wrap align-items-end w-100 " action="proses_simpan.php">
                        <input type="hidden" name="nik" id="inputNik" value="">
                        <div class="">
                            <div class="row w-100">

                                <div class="col-6">
                                    <div class="input-group">
                                        <span class="input-group-text" style="width: 97px;">PT</span>
                                        <select name="pt" id="selectPT" class="form-select" required>
                                            <option value="">-- Pilih PT --</option>

                                            <?php if ($isSuper): ?>
                                                <option value="PT.MSAL (HO)">PT.MSAL (HO)</option>
                                                <option value="PT.MSAL (PKS)">PT.MSAL (PKS)</option>
                                                <option value="PT.MSAL (SITE)">PT.MSAL (SITE)</option>
                                                <option value="PT.PSAM (PKS)">PT.PSAM (PKS)</option>
                                                <option value="PT.PSAM (SITE)">PT.PSAM (SITE)</option>
                                                <option value="PT.MAPA">PT.MAPA</option>
                                                <option value="PT.PEAK (PKS)">PT.PEAK (PKS)</option>
                                                <option value="PT.PEAK (SITE)">PT.PEAK (SITE)</option>
                                                <option value="RO PALANGKARAYA">RO PALANGKARAYA</option>
                                                <option value="RO SAMPIT">RO SAMPIT</option>
                                                <option value="PT.WCJU (SITE)">PT.WCJU (SITE)</option>
                                                <option value="PT.WCJU (PKS)">PT.WCJU (PKS)</option>
                                            <?php else: ?>
                                                <?php foreach ($ptUserList as $ptOpt): ?>
                                                    <option value="<?= htmlspecialchars($ptOpt, ENT_QUOTES, 'UTF-8') ?>">
                                                        <?= htmlspecialchars($ptOpt, ENT_QUOTES, 'UTF-8') ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </select>

                                    </div>
                                </div>

                                <div class="col-6">
                                    <div class="input-group d-flex">
                                        <span class="input-group-text" style="width: 97px;">Nama</span>
                                        <select name="nama" id="selectNama" class="form-select" required>
                                            <option value="">-- Pilih Nama --</option>

                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row w-100">

                            </div>
                            <div class="row w-100 pt-2">
                                <div class="col-6">
                                    <div class="input-group">
                                        <span class="input-group-text">Username</span>
                                        <input type="text" class="form-control usernameInput" placeholder="username" name="username" id="" aria-label="username" required>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="input-group">
                                        <span class="input-group-text" style="width: 97px;">Password</span>
                                        <input type="text" class="form-control" placeholder="password" name="password" aria-label="password" required>
                                    </div>
                                </div>
                            </div>


                            <div class="row w-100 pt-2">
                                <div class="col-6">
                                    <div class="input-group">
                                        <span class="input-group-text" style="width: 97px;">Peran</span>
                                        <select name="hakakses" id="selectPeran" class="form-select" required>
                                            <option value="">-- Pilih Peran --</option>
                                            <option value="Admin">Admin</option>
                                            <option value="User">User</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-6">
                                    <div class="input-group">
                                        <span class="input-group-text" style="width: 97px;">Email</span>
                                        <input type="email" class="form-control" placeholder="email@email" name="email" aria-label="email" required>
                                    </div>
                                </div>

                            </div>
                            <div class="autograph-container pt-2">
                                <input type="file" id="signature_file" accept="image/*" class="form-control mb-2">
                                <canvas id="signature" width="500" height="200" style="border: 1px solid gray; border-radius: 8px;"></canvas>
                                <input type="hidden" name="signature_data" id="signature_data">
                                <div class="d-flex justify-content-between mt-2">
                                    <button id="clear" class="btn btn-warning btn-sm">Bersihkan</button>
                                </div>
                            </div>
                            <div class="row w-100 pt-2">
                                <div class="col-6">
                                    <input class="w-100 align-self-end btn btn-primary" style="background-color:#2980b9;" type="submit" value="Simpan">
                                </div>
                            </div>
                        </div>

                    </form>

                </div>

            </div>

            <div id="popupBoxDetail" class="custom-popup-detail popup-box justify-content-center position-absolute">

                <div class="d-flex bg-white rounded-1 flex-column justify-content-start align-items-center p-2" style="height: max-content;align-self: center;z-index: 9;width: max-content;">
                    <div class="w-100 d-flex justify-content-between mb-2" style="height: max-content;">
                        <h4 class="m-0 p-0">Data Akun</h4>
                        <a id="tombolClosePopup2" class='btn btn-danger btn-sm' href='#'><i class="bi bi-x-lg"></i></a>
                    </div>
                    <div class="custom-data-akun row ps-3 pe-3" style="width:max-content;">
                        <div class="col-6 d-flex flex-column p-0 m-0" style="width: 300px;">
                            <div class="d-flex justify-content-start p-0 m-0">
                                <h6>Username</h6>
                                <h6 style="margin-left:50px;margin-right:5px;">:</h6>
                                <h6 style="overflow-x:auto;overflow-y:hidden;scrollbar-width: thin;" id="detailUsername"></h6>
                            </div>
                            <div class="d-flex justify-content-start p-0 m-0">
                                <h6>Password</h6>
                                <h6 style="margin-left:55px;margin-right:5px;">:</h6>
                                <h6 style="overflow-x:auto;overflow-y:hidden;scrollbar-width: thin;" id="detailPassword"></h6>
                            </div>
                            <div class="d-flex justify-content-start p-0 m-0">
                                <h6>Email</h6>
                                <h6 style="margin-left:84px;margin-right:5px;">:</h6>
                                <h6 style="overflow-x:auto;overflow-y:hidden;scrollbar-width: thin;" id="detailEmail"></h6>
                            </div>
                            <div class="d-flex justify-content-start p-0 m-0">
                                <h6>PT</h6>
                                <h6 style="margin-left:105px;margin-right:5px;">:</h6>
                                <h6 style="overflow-x:auto;overflow-y:hidden;scrollbar-width: thin;" id="detailPT"></h6>
                            </div>
                            <div class="d-flex justify-content-start p-0 m-0">
                                <h6>Nama</h6>
                                <h6 style="margin-left:80px;margin-right:5px;">:</h6>
                                <h6 style="overflow-x:auto;overflow-y:hidden;scrollbar-width: thin;" id="detailNama"></h6>
                            </div>
                            <div class="d-flex justify-content-start p-0 m-0">
                                <h6>NIK</h6>
                                <h6 style="margin-left:96px;margin-right:5px;">:</h6>
                                <h6 style="overflow-x:auto;overflow-y:hidden;scrollbar-width: thin;" id="detailNIK"></h6>
                            </div>
                            <div class="d-flex justify-content-start p-0 m-0">
                                <h6>Hak Akses</h6>
                                <h6 style="margin-left:48px;margin-right:5px;">:</h6>
                                <h6 style="overflow-x:auto;overflow-y:hidden;scrollbar-width: thin;" id="detailHakAkses"></h6>
                            </div>
                            <div class="d-flex justify-content-start p-0 m-0">
                                <h6>Akses Tambahan</h6>
                                <h6 style="margin-left:2px;margin-right:5px;">:</h6>
                                <p class="m-0" id="detailManajemenAkun"></p>
                            </div>
                        </div>


                        <div id="colEditAkun" class="col-6 border rounded-1" style=" width: 600px;">
                            <div class="row pt-2">
                                <h6>Edit Akun</h6>
                            </div>

                            <form method="post" class="popupInput d-flex p-0 gap-2 flex-wrap align-items-end w-100 " action="proses_update.php">
                                <input type="hidden" name="id" id="detailId">

                                <div class="row w-100">
                                    <div class="col-6">
                                        <div class="row w-100 pt-1">
                                            <div class="col-12">
                                                <div class="input-group">
                                                    <span class="input-group-text">Username</span>
                                                    <input type="text" class="form-control usernameInput" placeholder="username" name="username" id="detailUsernameInput" aria-label="username" required>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row w-100 pt-1">
                                            <div class="col-12">
                                                <div class="input-group">
                                                    <span class="input-group-text" style="width: 97px;">Password</span>
                                                    <input type="text" class="form-control" placeholder="password" name="password" id="detailPasswordInput" aria-label="password" required>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row w-100 pt-1">
                                            <div class="col-12">
                                                <div class="input-group">
                                                    <span class="input-group-text" style="width:97px;">Email</span>
                                                    <input type="email" class="form-control" placeholder="email@email" name="email" id="detailEmailInput" aria-label="email">
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-6" id="colAksesTambahan">
                                        <div class="row pt-2">
                                            <h6>Akses Tambahan</h6>
                                        </div>

                                        <div class="row w-100 pt-1">
                                            <div class="col-12">
                                                <div class="accordion" id="aksesTambahanAccordion" style="border:none;box-shadow:none;">


                                                    <div class="accordion-item" id="accordionManajemenAkun" style="border:none;">
                                                        <h2 class="accordion-header m-0 p-0" id="headingManajemenAkun">
                                                            <button class="accordion-button collapsed m-0 p-0 bg-transparent shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#collapseManajemenAkun" aria-expanded="false" aria-controls="collapseManajemenAkun" style="padding:0;">
                                                                <span>Manajemen Akun</span>
                                                            </button>
                                                        </h2>
                                                        <div id="collapseManajemenAkun" class="accordion-collapse collapse" aria-labelledby="headingManajemenAkun" data-bs-parent="#aksesTambahanAccordion">
                                                            <div class="accordion-body m-0 p-0">
                                                                <div class="form-check">
                                                                    <input class="form-check-input" type="radio" name="manajemen_akun_akses" id="manajemenNone" value="0">
                                                                    <label class="form-check-label" for="manajemenNone">None</label>
                                                                </div>
                                                                <div class="form-check">
                                                                    <input class="form-check-input" type="radio" name="manajemen_akun_akses" id="manajemenView" value="1">
                                                                    <label class="form-check-label" for="manajemenView">View Only</label>
                                                                </div>
                                                                <div class="form-check">
                                                                    <input class="form-check-input" type="radio" name="manajemen_akun_akses" id="manajemenEdit" value="2">
                                                                    <label class="form-check-label" for="manajemenEdit">Editable</label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                </div>

                                            </div>
                                        </div>
                                    </div>



                                </div>

                                <div class="row w-100 pt-1">
                                    <div class="col-12 d-flex flex-column align-items-start">
                                        <label class="fw-semibold">Tanda Tangan</label>
                                        <input type="file" id="signature_file_edit" accept="image/*" class="form-control mb-2">
                                        <canvas id="signature_edit" width="500" height="200" style="border:1px solid #ccc;border-radius:5px;"></canvas>
                                        <div class="d-flex gap-2 mt-1">
                                            <button id="clear_edit" class="btn btn-warning btn-sm">Bersihkan</button>
                                        </div>
                                        <input type="hidden" name="signature_data_edit" id="signature_data_edit">
                                    </div>
                                </div>

                                <div class="row w-100 pt-1 pb-1">
                                    <div class="col-6">
                                        <input class="w-100 align-self-end btn btn-primary" style="background-color:#2980b9;" type="submit" value="Simpan">
                                    </div>
                                </div>
                            </form>
                        </div>

                    </div>
                </div>

            </div>

            <div id="popupBoxKaryawanInput" class="custom-popup-input popup-box justify-content-center position-absolute">
                <div class="d-flex bg-white rounded-1 flex-column justify-content-start align-items-center p-2"
                    style="height:max-content;align-self:center;z-index:9;max-width:600px; width:100%;">

                    <div class="w-100 d-flex justify-content-between mb-2" style="height:max-content;">
                        <h4 class="m-0 p-0">Input Karyawan Baru</h4>
                        <a id="tombolClosePopupKaryawan" class="btn btn-danger btn-sm" href="#">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    </div>

                    <!-- form submit ke file ini sendiri -->
                    <form method="post" action="" id="formInputKaryawan"
                        class="popupInput d-flex p-0 gap-2 flex-wrap align-items-end w-100">

                        <!-- =========================
                        PILIH LOKASI (WAJIB PERTAMA)
                        ========================= -->
                        <div class="row w-100">
                            <div class="col-12">
                                <label class="fw-semibold mb-1">Lokasi</label>
                                <div class="d-flex gap-2 flex-wrap">
                                    <input class="btn-check" type="radio" name="lokasi_tipe" id="lokasiHO" value="HO" autocomplete="off">
                                    <label class="btn btn-outline-primary" for="lokasiHO">Head Office</label>

                                    <input class="btn-check" type="radio" name="lokasi_tipe" id="lokasiSITE" value="SITE" autocomplete="off">
                                    <label class="btn btn-outline-primary" for="lokasiSITE">Site</label>
                                </div>
                            </div>
                        </div>

                        <div class="row w-100 pt-2" id="karyawanInfoAwal">
                            <div class="col-12">
                                <div class="alert alert-info m-0 p-2">
                                    Silakan pilih <b>Lokasi</b> terlebih dahulu.
                                </div>
                            </div>
                        </div>

                        <!-- =========================
                        FORM HEAD OFFICE
                        ========================= -->
                        <div id="karyawanFormHO" style="display:none; width:100%;">

                            <div class="row w-100 pt-2">
                                <div class="col-12">
                                    <div class="input-group">
                                        <span class="input-group-text" style="width:97px;">Nama</span>
                                        <input type="text" name="ho_nama" id="ho_nama" class="form-control" placeholder="Nama karyawan">
                                    </div>
                                </div>
                            </div>

                            <div class="row w-100 pt-2">
                                <div class="col-12 col-md-6">
                                    <div class="input-group">
                                        <span class="input-group-text" style="width:97px;">Lantai</span>
                                        <select name="ho_lantai" id="ho_lantai" class="form-select">
                                            <option value="">-- Pilih Lantai --</option>
                                            <option value="LT.1">Lantai 1</option>
                                            <option value="LT.2">Lantai 2</option>
                                            <option value="LT.3">Lantai 3</option>
                                            <option value="LT.4">Lantai 4</option>
                                        </select>
                                    </div>
                                </div>
                            
                                <div class="col-12 col-md-6 pt-2 pt-xl-0">
                                    <div class="input-group">
                                        <span class="input-group-text" style="width:97px;">Jabatan</span>
                                        <select name="ho_jabatan" id="ho_jabatan" class="form-select" disabled>
                                            <option value="">-- Pilih Jabatan --</option>
                                        </select>
                                    </div>
                                </div>

                            </div>

                            <div class="row w-100 pt-2">

                                <div class="col-12 col-md-6">
                                    <div class="input-group">
                                        <span class="input-group-text" style="width:97px;">Dept</span>
                                        <select name="ho_departemen" id="ho_departemen" class="form-select">
                                            <?php echo renderHoDepartmentOptions($koneksi, '', 0, ''); ?>
                                        </select>
                                    </div>
                                </div>

                                <!-- <div class="col-12 col-md-6">
                                    <small class="text-muted">Jabatan tertentu hanya boleh 1 orang per departemen.</small>
                                </div> -->

                            </div>

                            <div class="row w-100 pt-2">
                                <div class="col-12">
                                    <div class="input-group">
                                        <span class="input-group-text" style="width:97px;">Posisi</span>
                                        <input type="text" name="ho_posisi" id="ho_posisi" class="form-control" placeholder="Posisi">
                                    </div>
                                </div>
                            </div>

                        </div>

                        <!-- =========================
                        FORM SITE
                        ========================= -->
                        <div id="karyawanFormSITE" style="display:none; width:100%;">

                            <div class="row w-100 pt-2">
                                <div class="col-12">
                                    <div class="input-group">
                                        <span class="input-group-text" style="width:97px;">Nama</span>
                                        <input type="text" name="site_nama" id="site_nama" class="form-control" placeholder="Nama karyawan">
                                    </div>
                                </div>
                            </div>

                            <div class="row w-100 pt-2">

                                <div class="col-12 col-md-6">
                                    <div class="custom-multi-group">
                                        <div class="multi-label">
                                            PT
                                        </div>

                                        <select name="site_pt[]"
                                            id="site_pt"
                                            class="form-select multi-select"
                                            multiple>
                                            <option value="PT.MSAL (SITE)">PT.MSAL (SITE)</option>
                                            <option value="PT.MSAL (PKS)">PT.MSAL (PKS)</option>
                                            <option value="PT.MAPA">PT.MAPA</option>
                                            <option value="PT.PSAM (SITE)">PT.PSAM (SITE)</option>
                                            <option value="PT.PSAM (PKS)">PT.PSAM (PKS)</option>
                                            <option value="PT.PEAK (SITE)">PT.PEAK (SITE)</option>
                                            <option value="PT.PEAK (PKS)">PT.PEAK (PKS)</option>
                                            <option value="PT.WCJU (SITE)">PT.WCJU (SITE)</option>
                                            <option value="PT.WCJU (PKS)">PT.WCJU (PKS)</option>
                                            <option value="RO PALANGKARAYA">RO PALANGKARAYA</option>
                                            <option value="RO SAMPIT">RO SAMPIT</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="col-12 col-md-6 pt-2 pt-xl-0">
                                    <div class="input-group">
                                        <span class="input-group-text" style="width:97px;">Dept</span>
                                        <select name="site_departemen" id="site_departemen" class="form-select">
                                            <option value="">-- Pilih Departemen --</option>
                                            <option value="IT">IT</option>
                                            <option value="HRD">HRD</option>
                                            <option value="GM">GM</option>
                                            <!-- MILL PKS akan dimunculkan via JS jika PT memenuhi -->
                                            <option value="MILL PKS" id="optMillPKS" style="display:none;">MILL PKS</option>
                                        </select>
                                    </div>
                                    
                                </div>

                            </div>

                            <div class="row w-100 pt-2">

                                <div class="col-12 col-md-6">
                                    <small class="text-muted">Dept. MILL PKS hanya muncul jika PT yang dipilih hanya PKS.</small>
                                </div>

                                <div class="col-12 col-md-6">
                                    <div class="input-group">
                                        <span class="input-group-text" style="width:97px;">Posisi</span>
                                        <select name="site_posisi" id="site_posisi" class="form-select" disabled>
                                            <option value="">-- Pilih Posisi --</option>
                                        </select>
                                    </div>
                                    
                                </div>

                            </div>

                            <div class="row w-100 pt-2">
                                <div class="col-12">
                                    <div class="input-group">
                                        <span class="input-group-text" style="width:97px;">Lokasi</span>
                                        <input type="text" name="site_lokasi" id="site_lokasi" class="form-control" placeholder="cth: Kantor Kebun">
                                    </div>
                                </div>
                            </div>

                        </div>

                        <div class="row w-100 pt-2">
                            <div class="col-12">
                                <div class="alert alert-warning m-0 p-2" id="karyawanWarning" style="display:none;">
                                    NIK otomatis dibuat saat simpan.
                                </div>
                            </div>
                        </div>

                        <div class="row w-100 pt-2">
                            <div class="col-6">
                                <button type="submit" name="submit_karyawan" value="1"
                                    class="w-100 align-self-end btn btn-primary"
                                    style="background-color:#2980b9; display:none;"
                                    id="btnSimpanKaryawan">
                                    Simpan
                                </button>
                            </div>
                        </div>

                    </form>

                </div>
            </div>

            <div id="popupBoxDetailKaryawan" class="custom-popup-detail popup-box justify-content-center position-absolute">
                <div class="d-flex bg-white rounded-1 flex-column justify-content-start align-items-center p-2"
                    style="height:max-content;align-self:center;z-index:9;width:max-content;">

                    <div class="w-100 d-flex justify-content-between mb-2" style="height:max-content;">
                        <h4 class="m-0 p-0">Data Karyawan</h4>
                        <a id="tombolClosePopupKaryawanDetail" class="btn btn-danger btn-sm" href="#">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    </div>

                    <div class="custom-data-akun row ps-3 pe-3" style="width:max-content;">

                        <!-- VIEW -->
                        <div class="col-6 d-flex flex-column p-0 m-0" style="width:300px;">
                            <div class="d-flex justify-content-start p-0 m-0">
                                <h6>Nama</h6>
                                <h6 style="margin-left:80px;margin-right:5px;">:</h6>
                                <h6 id="detailKaryawanNama"></h6>
                            </div>

                            <div class="d-flex justify-content-start p-0 m-0">
                                <h6>NIK</h6>
                                <h6 style="margin-left:96px;margin-right:5px;">:</h6>
                                <h6 id="detailKaryawanNIK"></h6>
                            </div>

                            <div class="d-flex justify-content-start p-0 m-0">
                                <h6>PT</h6>
                                <h6 style="margin-left:105px;margin-right:5px;">:</h6>
                                <h6 id="detailKaryawanPT" style="word-break:break-word;white-space:normal;"></h6>
                            </div>

                            <div class="d-flex justify-content-start p-0 m-0" id="rowDetailKaryawanLantai" style="display:none !important;">
                                <h6>Lantai</h6>
                                <h6 style="margin-left:78px;margin-right:5px;">:</h6>
                                <h6 id="detailKaryawanLantai"></h6>
                            </div>

                            <div class="d-flex justify-content-start p-0 m-0" id="rowDetailKaryawanLokasi" style="display:none !important;">
                                <h6>Lokasi</h6>
                                <h6 style="margin-left:72px;margin-right:5px;">:</h6>
                                <h6 id="detailKaryawanLokasi"></h6>
                            </div>

                            <div class="d-flex justify-content-start p-0 m-0">
                                <h6>Jabatan</h6>
                                <h6 style="margin-left:61px;margin-right:5px;">:</h6>
                                <h6 id="detailKaryawanJabatan"></h6>
                            </div>

                            <div class="d-flex justify-content-start p-0 m-0" id="rowDetailKaryawanPosisi" style="display:none !important;">
                                <h6>Posisi</h6>
                                <h6 style="margin-left:73px;margin-right:5px;">:</h6>
                                <h6 id="detailKaryawanPosisi"></h6>
                            </div>
                        </div>

                        <!-- EDIT -->
                        <div id="colEditKaryawan" class="col-6 border rounded-1" style="width:600px;">
                            <div class="row pt-2">
                                <h6>Edit Karyawan</h6>
                            </div>

                            <form method="post" action="" class="popupInput d-flex p-0 gap-2 flex-wrap align-items-end w-100" id="formEditKaryawan">
                                <input type="hidden" name="update_karyawan" value="1">
                                <input type="hidden" name="karyawan_id" id="editKaryawanId">
                                <input type="hidden" name="karyawan_sumber" id="editKaryawanSumber">

                                <!-- FORM HO (tanpa pilihan lokasi) -->
                                <div id="editFormHO" style="display:none; width:100%;">
                                    <div class="row w-100 pt-2">
                                        <div class="col-12">
                                            <div class="input-group">
                                                <span class="input-group-text" style="width:97px;">Nama</span>
                                                <input type="text" name="ho_nama_edit" id="ho_nama_edit" class="form-control" required disabled>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row w-100 pt-2">

                                        <div class="col-12 col-md-6">
                                            <div class="input-group">
                                                <span class="input-group-text" style="width:97px;">Lantai</span>
                                                <select name="ho_lantai_edit" id="ho_lantai_edit" class="form-select" required disabled>
                                                    <option value="">-- Pilih Lantai --</option>
                                                    <option value="LT.1">Lantai 1</option>
                                                    <option value="LT.2">Lantai 2</option>
                                                    <option value="LT.3">Lantai 3</option>
                                                    <option value="LT.4">Lantai 4</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-12 col-md-6 pt-2">
                                            <div class="input-group">
                                                <span class="input-group-text" style="width:97px;">Jabatan</span>
                                                <select name="ho_jabatan_edit" id="ho_jabatan_edit" class="form-select" required disabled>
                                                    <option value="">-- Pilih Jabatan --</option>
                                                </select>
                                            </div>
                                        </div>

                                    </div>

                                    <div class="row w-100 pt-2">
                                        <div class="col-12 col-md-6">
                                            <div class="input-group">
                                                <span class="input-group-text" style="width:97px;">Dept</span>
                                                <select name="ho_departemen_edit" id="ho_departemen_edit" class="form-select" required disabled>
                                                    <?php echo renderHoDepartmentOptions($koneksi, '', 0, ''); ?>
                                                </select>
                                            </div>
                                        </div>

                                        <!-- <div class="col-12 col-md-6">
                                            <small class="text-muted">Jabatan tertentu hanya boleh 1 orang per departemen.</small>
                                        </div> -->

                                    </div>

                                    <div class="row w-100 pt-2">
                                        <div class="col-12">
                                            <div class="input-group">
                                                <span class="input-group-text" style="width:97px;">Posisi</span>
                                                <input type="text" name="ho_posisi_edit" id="ho_posisi_edit" class="form-control" required disabled>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- FORM SITE (tanpa pilihan lokasi) -->
                                <div id="editFormSITE" style="display:none; width:100%;">
                                    <div class="row w-100 pt-2">
                                        <div class="col-12 col-md-6">
                                            <div class="input-group">
                                                <span class="input-group-text" style="width:97px;">Nama</span>
                                                <input type="text" name="site_nama_edit" id="site_nama_edit" class="form-control" required disabled>
                                            </div>
                                        </div>

                                        <div class="col-12 col-md-6">
                                            <div class="custom-multi-group">
                                                <div class="multi-label">PT</div>
                                                <select name="site_pt_edit[]" id="site_pt_edit" class="form-select multi-select" multiple required disabled>
                                                    <option value="PT.MSAL (SITE)">PT.MSAL (SITE)</option>
                                                    <option value="PT.MSAL (PKS)">PT.MSAL (PKS)</option>
                                                    <option value="PT.MAPA">PT.MAPA</option>
                                                    <option value="PT.PSAM (SITE)">PT.PSAM (SITE)</option>
                                                    <option value="PT.PSAM (PKS)">PT.PSAM (PKS)</option>
                                                    <option value="PT.PEAK (SITE)">PT.PEAK (SITE)</option>
                                                    <option value="PT.PEAK (PKS)">PT.PEAK (PKS)</option>
                                                    <option value="PT.WCJU (SITE)">PT.WCJU (SITE)</option>
                                                    <option value="PT.WCJU (PKS)">PT.WCJU (PKS)</option>
                                                    <option value="RO PALANGKARAYA">RO PALANGKARAYA</option>
                                                    <option value="RO SAMPIT">RO SAMPIT</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row w-100 pt-2">
                                        <div class="col-12 col-md-6">
                                            <div class="input-group">
                                                <span class="input-group-text" style="width:97px;">Dept</span>
                                                <select name="site_departemen_edit" id="site_departemen_edit" class="form-select" required disabled>
                                                    <option value="">-- Pilih Departemen --</option>
                                                    <option value="IT">IT</option>
                                                    <option value="HRD">HRD</option>
                                                    <option value="GM">GM</option>
                                                    <option value="MILL PKS" id="optMillPKS_edit" style="display:none;">MILL PKS</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-12 col-md-6">
                                            <div class="input-group">
                                                <span class="input-group-text" style="width:97px;">Lokasi</span>
                                                <input type="text" name="site_lokasi_edit" id="site_lokasi_edit" class="form-control" required disabled>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row w-100 pt-2">
                                        <div class="col-12">
                                            <div class="input-group">
                                                <span class="input-group-text" style="width:97px;">Posisi</span>
                                                <select name="site_posisi_edit" id="site_posisi_edit" class="form-select" required disabled>
                                                    <option value="">-- Pilih Posisi --</option>
                                                </select>
                                            </div>
                                            <small class="text-muted">Posisi akan menyesuaikan departemen.</small>
                                        </div>
                                    </div>
                                </div>

                                <div class="row w-100 pt-2 pb-2">
                                    <div class="col-6">
                                        <button type="submit" class="w-100 btn btn-primary" style="background-color:#2980b9;">
                                            Simpan
                                        </button>
                                    </div>
                                </div>
                            </form>

                        </div>
                    </div>
                </div>
            </div>
        </main>



        <div id="popupBG" class="popup-bg position-absolute w-100 h-100" style="background-color: rgba(0,0,0,0.5); z-index:3;"></div>
        <!--Akhir::Main Content-->

        <!--Awal::Footer Content-->
        <footer class="custom-footer d-flex position-relative p-2" style="border-top: whitesmoke solid 1px; box-shadow: 0px 7px 10px black; color: grey;">
            <p class="position-absolute" style="right: 15px;bottom:7px;color: grey;"><strong>Version </strong>1.1.0</p>
            <p class="pt-2 ps-1"><strong>Copyright &copy 2025</p></strong>
            <p class="pt-2 ps-1 fw-bold text-primary">MIS MSAL.</p>
            <p class="pt-2 ps-1"> All rights reserved</p>
        </footer>
        <!--Akhir::Footer Content-->

        <?php
        // Ambil data warna
        $sqlWarna = "SELECT nama, warna FROM personalia_menucolor ORDER BY nama ASC";
        $resultWarna = $koneksi->query($sqlWarna);
        ?>

        <div id="popupBoxPersonalia" class="popup-box position-fixed end-0" style="z-index: 15; top: 50px;">
            <div id="theme-panel" class="card position-relative bg-white p-2 m-2" style="width:200px; height:max-content; box-shadow: 0px 4px 8px rgba(0,0,0,0.1); ">
                <h5 class="card-title border-bottom pb-2 mb-0">Personalia</h5>
                <form action="../../proses_simpan_personalia.php" method="post" class="p-0">
                    <div class="mb-2">
                        <label for="themeSelect" class="form-label mt-0">Warna Tema:</label>
                        <select id="themeSelect" name="warna_menu" class="form-select">
                            <option value="0" selected>Default</option>
                            <?php while ($row = $resultWarna->fetch_assoc()): ?>
                                <option value="<?= htmlspecialchars($row['warna']); ?>">
                                    <?= htmlspecialchars($row['nama']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm">Simpan</button>
                </form>
            </div>
        </div>

    </div>

    <!-- Bootstrap 5 -->
    <script src="../../assets/bootstrap-5.3.6-dist/js/bootstrap.min.js"></script>

    <script src="../../assets/js/select2.min.js"></script>

    <!-- popperjs Bootstrap 5 -->
    <script src="../../assets/js/popper.min.js"></script>

    <!-- AdminLTE -->
    <script src="../../assets/adminlte/js/adminlte.js"></script>

    <!-- OverlayScrollbars -->
    <script src="../../assets/js/overlayscrollbars.browser.es6.min.js"></script>

    <!-- signaturPad -->
    <script src="../../assets/js/signature_pad.umd.min.js"></script>

    <script>
        //Info Akun
        document.addEventListener('DOMContentLoaded', function() {
            const button = document.getElementById('tombolAkun');
            const box = document.getElementById('akunInfo');

            button.addEventListener('click', function() {
                if (box.classList.contains('display-state')) {
                    // Buka
                    box.classList.remove('display-state');
                    setTimeout(() => {
                        box.classList.add('aktif');
                    }, 200);
                } else {
                    // Tutup
                    box.classList.remove('aktif');
                    setTimeout(() => {
                        box.classList.add('display-state');
                    }, 200);
                }
            });
        });
    </script>

    <!-- <script>//TTD
document.addEventListener("DOMContentLoaded", function () {
    // popup input
    const canvas = document.getElementById("signature");
    if (canvas) {
        const signaturePad = new SignaturePad(canvas);
        const clearButton = document.getElementById("clear");
        const inputHidden = document.getElementById("signature_data");
        const form = document.querySelector("#popupBoxInput .popupInput");

        clearButton.addEventListener("click", function (e) {
            e.preventDefault();
            signaturePad.clear();
        });

        form.addEventListener("submit", function (e) {
            inputHidden.value = signaturePad.isEmpty() ? "" : signaturePad.toDataURL();
        });
    }

    // popup detail/edit
const canvasEdit = document.getElementById("signature_edit");
if (canvasEdit) {
    const signaturePadEdit = new SignaturePad(canvasEdit);
    const clearButtonEdit = document.getElementById("clear_edit");
    const inputHiddenEdit = document.getElementById("signature_data_edit");
    const formEdit = document.querySelector("#popupBoxDetail .popupInput");

    // tampilkan tanda tangan lama saat buka popup detail
    document.querySelectorAll('.tombolDataPopup').forEach(btn => {
        btn.addEventListener('click', function () {
            const base64Data = this.getAttribute('data-signature') || "";

            signaturePadEdit.clear();
            if (base64Data) {
                const dataURL = "data:image/png;base64," + base64Data;
                signaturePadEdit.fromDataURL(dataURL);
                inputHiddenEdit.value = dataURL; // tetap dianggap sudah isi tanda tangan
            } else {
                inputHiddenEdit.value = "";
            }
        });
    });
    
    // tombol bersihkan
    clearButtonEdit.addEventListener("click", function (e) {
        e.preventDefault();
        signaturePadEdit.clear();
        inputHiddenEdit.value = "";
    });

    // submit form — pastikan tidak kehilangan data tanda tangan lama
    formEdit.addEventListener("submit", function (e) {
        if (!signaturePadEdit.isEmpty()) {
            inputHiddenEdit.value = signaturePadEdit.toDataURL();
        }
        // jika kosong tapi inputHiddenEdit sudah berisi tanda tangan lama, biarkan saja
    });
}

});
</script> -->

    <script>
        //TTD (FIX TOTAL FINAL - ONLY SIGNATURE PAD)
        (function() {
            "use strict";

            function getSignatureDisplaySize() {
                // Desktop + Tablet = 500x200, HP = 300x200
                return (window.innerWidth <= 576) ? {
                    width: 300,
                    height: 200
                } : {
                    width: 500,
                    height: 200
                };
            }

            function getDpr() {
                return Math.max(window.devicePixelRatio || 1, 1);
            }

            // Paksa ukuran CSS inline dengan !important supaya menang dari CSS responsive popup
            function setCanvasCssSizeImportant(canvasEl, w, h) {
                if (!canvasEl) return;
                canvasEl.style.setProperty("width", w + "px", "important");
                canvasEl.style.setProperty("height", h + "px", "important");
                canvasEl.style.setProperty("min-width", w + "px", "important");
                canvasEl.style.setProperty("max-width", w + "px", "important");
                canvasEl.style.setProperty("min-height", h + "px", "important");
                canvasEl.style.setProperty("max-height", h + "px", "important");
                canvasEl.style.setProperty("display", "block", "important");
                canvasEl.style.setProperty("touch-action", "none", "important");
                canvasEl.style.setProperty("background", "#fff", "important");
            }

            // Reset canvas + scale DPR yang BENAR (ini penting supaya setelah clear tidak rusak)
            function resetCanvasDpr(canvasEl) {
                const dpr = getDpr();
                const size = getSignatureDisplaySize();
                const ctx = canvasEl.getContext("2d");

                setCanvasCssSizeImportant(canvasEl, size.width, size.height);

                // internal bitmap
                canvasEl.width = Math.round(size.width * dpr);
                canvasEl.height = Math.round(size.height * dpr);

                // reset transform lalu apply DPR lagi
                ctx.setTransform(1, 0, 0, 1, 0, 0);
                ctx.clearRect(0, 0, canvasEl.width, canvasEl.height);
                ctx.scale(dpr, dpr);

                return {
                    dpr: dpr,
                    logicalW: size.width,
                    logicalH: size.height
                };
            }

            function applyCanvasSize(canvasEl, signaturePadObj) {
                resetCanvasDpr(canvasEl);
                if (signaturePadObj) {
                    signaturePadObj.clear(); // reset internal stroke data
                }
            }

            function clearPadProperly(canvasEl, signaturePadObj, hiddenInputEl, fileInputEl) {
                if (hiddenInputEl) hiddenInputEl.value = "";
                if (fileInputEl) fileInputEl.value = "";
                applyCanvasSize(canvasEl, signaturePadObj); // <-- re-init DPR scale (anti rusak setelah clear)
            }

            // Draw image fit-center ke canvas (DPR-safe)
            function drawDataUrlFitToCanvas(signaturePadObj, canvasEl, hiddenInputEl, dataURL, normalizeHidden) {
                if (!canvasEl || !signaturePadObj) return;

                if (!dataURL) {
                    clearPadProperly(canvasEl, signaturePadObj, hiddenInputEl, null);
                    return;
                }

                const img = new Image();
                img.onload = function() {
                    const meta = resetCanvasDpr(canvasEl); // always rebuild DPR transform
                    const ctx = canvasEl.getContext("2d");

                    // signature pad internal state kosong (image bukan stroke vector)
                    signaturePadObj.clear();

                    const logicalW = meta.logicalW;
                    const logicalH = meta.logicalH;

                    const scale = Math.min(logicalW / img.width, logicalH / img.height);
                    const drawW = img.width * scale;
                    const drawH = img.height * scale;
                    const x = (logicalW - drawW) / 2;
                    const y = (logicalH - drawH) / 2;

                    ctx.drawImage(img, x, y, drawW, drawH);

                    if (hiddenInputEl) {
                        if (normalizeHidden) {
                            hiddenInputEl.value = canvasEl.toDataURL("image/png");
                        } else if (!hiddenInputEl.value) {
                            hiddenInputEl.value = dataURL;
                        }
                    }
                };
                img.src = dataURL;
            }

            // Global function dipakai popup open/resize script Anda
            window.resizeSignatureCanvas = function(canvasEl, signaturePadObj, hiddenInputEl) {
                if (!canvasEl || !signaturePadObj) return;

                const savedDataUrl = (hiddenInputEl && hiddenInputEl.value) ? hiddenInputEl.value : "";
                let strokeData = null;

                // Kalau user sedang menulis dan belum jadi hidden image
                if (!savedDataUrl && !signaturePadObj.isEmpty()) {
                    try {
                        strokeData = signaturePadObj.toData();
                    } catch (e) {
                        strokeData = null;
                    }
                }

                applyCanvasSize(canvasEl, signaturePadObj);

                if (savedDataUrl) {
                    drawDataUrlFitToCanvas(signaturePadObj, canvasEl, hiddenInputEl, savedDataUrl, false);
                } else if (strokeData && strokeData.length) {
                    try {
                        signaturePadObj.fromData(strokeData);
                    } catch (e) {
                        signaturePadObj.clear();
                        resetCanvasDpr(canvasEl);
                    }
                }
            };

            // compatibility helper
            window.drawDataUrlToPad = function(signaturePadObj, canvasEl, hiddenInputEl, dataURL) {
                drawDataUrlFitToCanvas(signaturePadObj, canvasEl, hiddenInputEl, dataURL, false);
            };

            document.addEventListener("DOMContentLoaded", function() {
                function loadImageToSignatureCanvas(file, canvasEl, signaturePadObj, hiddenInputEl) {
                    if (!file) return;
                    if (!file.type || !file.type.startsWith("image/")) {
                        alert("File harus berupa gambar.");
                        return;
                    }

                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const dataURL = e.target && e.target.result ? e.target.result : "";
                        if (!dataURL) return;
                        drawDataUrlFitToCanvas(signaturePadObj, canvasEl, hiddenInputEl, dataURL, true);
                    };
                    reader.readAsDataURL(file);
                }

                // =========================
                // POPUP INPUT
                // =========================
                const canvasInput = document.getElementById("signature");
                if (canvasInput) {
                    const signaturePadInput = new SignaturePad(canvasInput);
                    const clearBtnInput = document.getElementById("clear");
                    const hiddenInput = document.getElementById("signature_data");
                    const formInput = document.querySelector("#popupBoxInput .popupInput");
                    const fileInput = document.getElementById("signature_file");

                    window.signatureCanvasInputRef = canvasInput;
                    window.signaturePadInputRef = signaturePadInput;
                    window.signatureHiddenInputRef = hiddenInput;

                    applyCanvasSize(canvasInput, signaturePadInput);

                    if (clearBtnInput) {
                        clearBtnInput.addEventListener("click", function(e) {
                            e.preventDefault();
                            clearPadProperly(canvasInput, signaturePadInput, hiddenInput, fileInput);
                        });
                    }

                    if (fileInput) {
                        fileInput.addEventListener("change", function() {
                            const file = this.files && this.files[0] ? this.files[0] : null;
                            if (!file) return;
                            loadImageToSignatureCanvas(file, canvasInput, signaturePadInput, hiddenInput);
                        });
                    }

                    if (formInput) {
                        formInput.addEventListener("submit", function() {
                            // Kalau user menggambar manual (stroke ada), simpan canvas terbaru
                            if (!signaturePadInput.isEmpty()) {
                                hiddenInput.value = canvasInput.toDataURL("image/png");
                            }
                            // kalau image upload lama ada di hidden dan pad kosong, biarkan
                        });
                    }
                }

                // =========================
                // POPUP DETAIL / EDIT
                // =========================
                const canvasEdit = document.getElementById("signature_edit");
                if (canvasEdit) {
                    const signaturePadEdit = new SignaturePad(canvasEdit);
                    const clearBtnEdit = document.getElementById("clear_edit");
                    const hiddenEdit = document.getElementById("signature_data_edit");
                    const formEdit = document.querySelector("#popupBoxDetail .popupInput");
                    const fileInputEdit = document.getElementById("signature_file_edit");

                    window.signatureCanvasEditRef = canvasEdit;
                    window.signaturePadEditRef = signaturePadEdit;
                    window.signatureHiddenEditRef = hiddenEdit;

                    applyCanvasSize(canvasEdit, signaturePadEdit);

                    // Simpan data signature lama ke hidden dulu, render setelah popup tampil (via resizeSignatureCanvas)
                    document.querySelectorAll(".tombolDataPopup").forEach(function(btn) {
                        btn.addEventListener("click", function() {
                            const base64Data = this.getAttribute("data-signature") || "";
                            if (base64Data) {
                                hiddenEdit.value = "data:image/png;base64," + base64Data;
                            } else {
                                hiddenEdit.value = "";
                            }
                            if (fileInputEdit) fileInputEdit.value = "";

                            // jangan gambar di sini (popup masih animasi), cukup clear state dulu
                            applyCanvasSize(canvasEdit, signaturePadEdit);
                        });
                    });

                    if (fileInputEdit) {
                        fileInputEdit.addEventListener("change", function() {
                            const file = this.files && this.files[0] ? this.files[0] : null;
                            if (!file) return;
                            loadImageToSignatureCanvas(file, canvasEdit, signaturePadEdit, hiddenEdit);
                        });
                    }

                    if (clearBtnEdit) {
                        clearBtnEdit.addEventListener("click", function(e) {
                            e.preventDefault();
                            clearPadProperly(canvasEdit, signaturePadEdit, hiddenEdit, fileInputEdit);
                        });
                    }

                    if (formEdit) {
                        formEdit.addEventListener("submit", function() {
                            if (!signaturePadEdit.isEmpty()) {
                                hiddenEdit.value = canvasEdit.toDataURL("image/png");
                            }
                            // kalau kosong tapi hidden masih ada (ttd lama/upload), biarkan
                        });
                    }
                }

                // =========================
                // RESIZE / ORIENTATION
                // =========================
                let resizeTimer = null;
                window.addEventListener("resize", function() {
                    clearTimeout(resizeTimer);
                    resizeTimer = setTimeout(function() {
                        const popupInput = document.getElementById("popupBoxInput");
                        const popupDetail = document.getElementById("popupBoxDetail");

                        if (popupInput && popupInput.classList.contains("aktifPopup") && window.signaturePadInputRef) {
                            window.resizeSignatureCanvas(
                                window.signatureCanvasInputRef,
                                window.signaturePadInputRef,
                                window.signatureHiddenInputRef
                            );
                        }

                        if (popupDetail && popupDetail.classList.contains("aktifPopup") && window.signaturePadEditRef) {
                            window.resizeSignatureCanvas(
                                window.signatureCanvasEditRef,
                                window.signaturePadEditRef,
                                window.signatureHiddenEditRef
                            );
                        }
                    }, 100);
                });
            });
        })();
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            var openKaryawan = document.getElementById('tombolInputKaryawanPopup');
            var closeKaryawan = document.getElementById('tombolClosePopupKaryawan');
            var boxKaryawan = document.getElementById('popupBoxKaryawanInput');
            var background = document.getElementById('popupBG');

            var infoAwal = document.getElementById('karyawanInfoAwal');
            var formHO = document.getElementById('karyawanFormHO');
            var formSITE = document.getElementById('karyawanFormSITE');
            var btnSave = document.getElementById('btnSimpanKaryawan');
            var warnNik = document.getElementById('karyawanWarning');

            // HO fields
            var hoNama = document.getElementById('ho_nama');
            var hoLantai = document.getElementById('ho_lantai');
            var hoDept = document.getElementById('ho_departemen');
            var hoJabatan = document.getElementById('ho_jabatan');
            var hoPosisi = document.getElementById('ho_posisi');

            // SITE fields
            var siteNama = document.getElementById('site_nama');
            var sitePt = document.getElementById('site_pt');
            var siteDept = document.getElementById('site_departemen');
            var siteLokasi = document.getElementById('site_lokasi');
            var sitePosisi = document.getElementById('site_posisi');
            var optMill = document.getElementById('optMillPKS');

            // posisi unik yang sudah terpakai pada PT terpilih
            var siteUsedPos = {
                'GM': false,
                'KTU': false,
                'Staf GA': false,
                'Kepala Mill': false
            };

            if (!openKaryawan || !closeKaryawan || !boxKaryawan || !background) return;

            function closePopupKaryawan() {
                setTimeout(function() {
                    background.classList.remove('aktifPopup');
                    boxKaryawan.classList.remove('aktifPopup');
                }, 300);

                boxKaryawan.classList.remove('scale-in-center');
                boxKaryawan.classList.add('scale-out-center');

                background.classList.remove('fade-in');
                background.classList.add('fade-out');
            }

            function setRequired(el, yes) {
                if (!el) return;
                if (yes) el.setAttribute('required', 'required');
                else el.removeAttribute('required');
            }

            function resetFormKaryawan() {
                // reset radio
                var r1 = document.getElementById('lokasiHO');
                var r2 = document.getElementById('lokasiSITE');
                if (r1) r1.checked = false;
                if (r2) r2.checked = false;

                // hide forms
                if (infoAwal) infoAwal.style.display = '';
                if (formHO) formHO.style.display = 'none';
                if (formSITE) formSITE.style.display = 'none';
                if (btnSave) btnSave.style.display = 'none';
                if (warnNik) warnNik.style.display = 'none';

                // clear values
                if (hoNama) hoNama.value = '';
                if (hoLantai) hoLantai.value = '';
                if (hoDept) hoDept.value = '';
                if (hoJabatan) {
                    hoJabatan.innerHTML = "<option value=''>-- Pilih Jabatan --</option>";
                    hoJabatan.disabled = true;
                }
                if (hoPosisi) hoPosisi.value = '';

                if (siteNama) siteNama.value = '';
                if (siteDept) siteDept.value = '';
                if (siteLokasi) siteLokasi.value = '';
                if (sitePosisi) {
                    sitePosisi.innerHTML = "<option value=''>-- Pilih Posisi --</option>";
                    sitePosisi.disabled = true;
                }

                // clear select2 PT
                if (window.jQuery && sitePt) {
                    try {
                        jQuery('#site_pt').val(null).trigger('change');
                    } catch (e) {}
                }

                // remove required all
                setRequired(hoNama, false);
                setRequired(hoLantai, false);
                setRequired(hoDept, false);
                setRequired(hoJabatan, false);
                setRequired(hoPosisi, false);
                setRequired(siteNama, false);
                setRequired(siteDept, false);
                setRequired(siteLokasi, false);
                setRequired(sitePosisi, false);
                // select multiple required handled via JS check (HTML required on multiple sering tricky)
                siteUsedPos = {
                    'GM': false,
                    'KTU': false,
                    'Staf GA': false,
                    'Kepala Mill': false
                };
            }

            function showHO() {
                if (infoAwal) infoAwal.style.display = 'none';
                if (formHO) formHO.style.display = '';
                if (formSITE) formSITE.style.display = 'none';
                if (btnSave) btnSave.style.display = '';
                if (warnNik) warnNik.style.display = '';

                setRequired(hoNama, true);
                setRequired(hoLantai, true);
                setRequired(hoDept, true);
                setRequired(hoJabatan, true);
                setRequired(hoPosisi, true);

                // disable SITE required
                setRequired(siteNama, false);
                setRequired(siteDept, false);
                setRequired(siteLokasi, false);
                setRequired(sitePosisi, false);
            }

            function showSITE() {
                if (infoAwal) infoAwal.style.display = 'none';
                if (formHO) formHO.style.display = 'none';
                if (formSITE) formSITE.style.display = '';
                if (btnSave) btnSave.style.display = '';
                if (warnNik) warnNik.style.display = '';

                setRequired(siteNama, true);
                setRequired(siteDept, true);
                setRequired(siteLokasi, true);
                setRequired(sitePosisi, true);

                // disable HO required
                setRequired(hoNama, false);
                setRequired(hoLantai, false);
                setRequired(hoDept, false);
                setRequired(hoJabatan, false);
                setRequired(hoPosisi, false);
            }

            // OPEN popup
            openKaryawan.addEventListener('click', function(e) {
                e.preventDefault();

                resetFormKaryawan();

                boxKaryawan.classList.add('aktifPopup', 'scale-in-center');
                boxKaryawan.classList.remove('scale-out-center');

                background.classList.add('aktifPopup', 'fade-in');
                background.classList.remove('fade-out');

                // init select2 multi PT (site)
                if (window.jQuery && sitePt) {
                    try {
                        jQuery('#site_pt').select2({
                            placeholder: "-- Pilih PT --",
                            allowClear: true,
                            width: '100%'
                        });
                    } catch (e) {}
                }
                // refresh status posisi unik (awal buka popup)
                if (window.jQuery && sitePt) {
                    fetchSiteUsedPositions();
                    updateDeptOptionsUnique();
                }
            });

            // CLOSE popup
            closeKaryawan.addEventListener('click', function(e) {
                e.preventDefault();
                closePopupKaryawan();
            });

            // background close (hanya jika popup ini aktif)
            background.addEventListener('click', function() {
                if (boxKaryawan.classList.contains('aktifPopup')) closePopupKaryawan();
            });

            // =========================
            // Toggle Lokasi
            // =========================
            var rHO = document.getElementById('lokasiHO');
            var rSITE = document.getElementById('lokasiSITE');

            if (rHO) rHO.addEventListener('change', function() {
                if (this.checked) showHO();
            });
            if (rSITE) rSITE.addEventListener('change', function() {
                if (this.checked) showSITE();
            });

            // =========================
            // HO: Departemen -> ambil jabatan via AJAX ke tabel.php
            // =========================
            if (window.jQuery && hoDept && hoJabatan) {
                jQuery('#ho_departemen').on('change', function() {
                    var dept = jQuery(this).val();

                    hoJabatan.disabled = true;
                    jQuery('#ho_jabatan').html("<option value=''>-- Pilih Jabatan --</option>");

                    if (!dept) return;

                    jQuery.ajax({
                        url: 'tabel.php',
                        type: 'POST',
                        data: {
                            ajax: 'get_jabatan_ho',
                            departemen: dept
                        },
                        success: function(html) {
                            jQuery('#ho_jabatan').html(html);
                            hoJabatan.disabled = false;
                        },
                        error: function() {
                            jQuery('#ho_jabatan').html("<option value=''>-- Pilih Jabatan --</option>");
                            hoJabatan.disabled = true;
                        }
                    });
                });
            }

            function updateDeptOptionsUnique() {
                if (!siteDept) return;

                var optGM = siteDept.querySelector('option[value="GM"]');
                var optHRD = siteDept.querySelector('option[value="HRD"]');
                // optMillPKS sudah ada variabel optMill

                var usedGM = !!(siteUsedPos && siteUsedPos['GM']);
                var usedHRD = !!(siteUsedPos && siteUsedPos['Staf GA']);
                var usedMill = !!(siteUsedPos && siteUsedPos['Kepala Mill']);

                if (optGM) optGM.style.display = usedGM ? 'none' : '';
                if (optHRD) optHRD.style.display = usedHRD ? 'none' : '';

                // MILL PKS display di-handle juga oleh updateMillOption, tapi kalau sedang terpilih dan ternyata used, reset.
                if ((usedGM && siteDept.value === 'GM') ||
                    (usedHRD && siteDept.value === 'HRD') ||
                    (usedMill && siteDept.value === 'MILL PKS')) {
                    siteDept.value = '';
                    if (sitePosisi) {
                        sitePosisi.innerHTML = "<option value=''>-- Pilih Posisi --</option>";
                        sitePosisi.disabled = true;
                    }
                }
            }

            function fetchSiteUsedPositions() {
                if (!window.jQuery) return;

                var selected = jQuery('#site_pt').val() || [];

                jQuery.ajax({
                    url: 'tabel.php',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        ajax: 'get_site_unique_pos',
                        pt: selected
                    },
                    success: function(resp) {
                        if (resp && resp.used) {
                            siteUsedPos = resp.used;
                        } else {
                            siteUsedPos = {
                                'GM': false,
                                'KTU': false,
                                'Staf GA': false,
                                'Kepala Mill': false
                            };
                        }

                        // update tampilan Dept & Posisi sesuai hasil cek
                        updateDeptOptionsUnique();
                        updateMillOption(); // supaya MILL PKS ikut hilang jika Kepala Mill sudah ada
                        updatePosisiSite(); // supaya KTU ikut hilang di IT
                    },
                    error: function() {
                        // kalau error ajax, jangan blok user (fallback)
                        siteUsedPos = {
                            'GM': false,
                            'KTU': false,
                            'Staf GA': false,
                            'Kepala Mill': false
                        };
                        updateDeptOptionsUnique();
                        updateMillOption();
                        updatePosisiSite();
                    }
                });
            }

            // =========================
            // SITE: aturan MILL PKS muncul hanya jika PT dipilih 1 dan PT termasuk PKS tertentu
            // =========================
            function updateMillOption() {
                if (!optMill || !window.jQuery) return;

                var selected = jQuery('#site_pt').val() || [];
                var pkspOnly = ["PT.PEAK (PKS)", "PT.MSAL (PKS)", "PT.PSAM (PKS)", "PT.WCJU (PKS)"];

                var showByPT = (selected.length === 1 && pkspOnly.indexOf(selected[0]) !== -1);
                var usedKepalaMill = !!(siteUsedPos && siteUsedPos['Kepala Mill']);

                // harus memenuhi rule PT, dan posisi Kepala Mill belum ada di salah satu PT terpilih
                var show = showByPT && !usedKepalaMill;

                optMill.style.display = show ? '' : 'none';

                // kalau sedang pilih MILL PKS tapi syarat tidak terpenuhi, reset departemen + posisi
                if (!show && siteDept && siteDept.value === 'MILL PKS') {
                    siteDept.value = '';
                    if (sitePosisi) {
                        sitePosisi.innerHTML = "<option value=''>-- Pilih Posisi --</option>";
                        sitePosisi.disabled = true;
                    }
                }
            }

            if (window.jQuery && sitePt) {
                jQuery('#site_pt').on('change', function() {
                    updateMillOption();
                    fetchSiteUsedPositions();
                });
            }

            // =========================
            // SITE: departemen -> posisi menyesuaikan
            // =========================
            function updatePosisiSite() {
                if (!siteDept || !sitePosisi) return;

                var dept = siteDept.value;

                var usedGM = !!(siteUsedPos && siteUsedPos['GM']);
                var usedHRD = !!(siteUsedPos && siteUsedPos['Staf GA']);
                var usedMill = !!(siteUsedPos && siteUsedPos['Kepala Mill']);
                var usedKTU = !!(siteUsedPos && siteUsedPos['KTU']);

                // kalau user somehow pilih dept yang harusnya hilang, reset
                if ((dept === 'GM' && usedGM) ||
                    (dept === 'HRD' && usedHRD) ||
                    (dept === 'MILL PKS' && usedMill)) {
                    siteDept.value = '';
                    sitePosisi.innerHTML = "<option value=''>-- Pilih Posisi --</option>";
                    sitePosisi.disabled = true;
                    return;
                }

                sitePosisi.disabled = false;

                if (!dept) {
                    sitePosisi.innerHTML = "<option value=''>-- Pilih Posisi --</option>";
                    sitePosisi.disabled = true;
                    return;
                }

                if (dept === 'IT') {
                    var opt = "<option value=''>-- Pilih Posisi --</option>" +
                        "<option value='IT Support'>IT Support</option>";
                    if (!usedKTU) {
                        opt += "<option value='KTU'>KTU</option>";
                    }
                    sitePosisi.innerHTML = opt;
                    sitePosisi.disabled = false;

                } else if (dept === 'HRD') {
                    // posisi fixed, tapi dept akan hilang kalau sudah dipakai
                    sitePosisi.innerHTML = "<option value='Staf GA'>Staf GA</option>";
                    sitePosisi.value = "Staf GA";
                    sitePosisi.disabled = true;

                } else if (dept === 'GM') {
                    sitePosisi.innerHTML = "<option value='GM'>GM</option>";
                    sitePosisi.value = "GM";
                    sitePosisi.disabled = true;

                } else if (dept === 'MILL PKS') {
                    sitePosisi.innerHTML = "<option value='Kepala Mill'>Kepala Mill</option>";
                    sitePosisi.value = "Kepala Mill";
                    sitePosisi.disabled = true;

                } else {
                    sitePosisi.innerHTML = "<option value=''>-- Pilih Posisi --</option>";
                    sitePosisi.disabled = true;
                }
            }

            if (siteDept) siteDept.addEventListener('change', updatePosisiSite);

        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {

            const openInput = document.getElementById('tombolInputPopup');
            const closeInput = document.getElementById('tombolClosePopup');
            const boxInput = document.getElementById('popupBoxInput');
            const background = document.getElementById('popupBG');
            const tabel = document.getElementById('custom-main');

            if (!openInput || !closeInput || !boxInput || !background) return;

            // buka popup input
            openInput.addEventListener('click', function() {
                boxInput.classList.add('aktifPopup', 'scale-in-center');
                boxInput.classList.remove('scale-out-center');
                background.classList.add('aktifPopup', 'fade-in');
                background.classList.remove('fade-out');
                requestAnimationFrame(() => {
                    if (window.signaturePadInputRef) {
                        resizeSignatureCanvas(
                            window.signatureCanvasInputRef,
                            window.signaturePadInputRef,
                            window.signatureHiddenInputRef
                        );
                    }
                });
            });

            // tutup popup input
            closeInput.addEventListener('click', function() {
                setTimeout(() => {
                    background.classList.remove('aktifPopup');
                    boxInput.classList.remove('aktifPopup');
                }, 300);
                boxInput.classList.remove('scale-in-center');
                boxInput.classList.add('scale-out-center');
                background.classList.remove('fade-in');
                background.classList.add('fade-out');
            });

            // klik background → tutup popup input SAJA
            background.addEventListener('click', function() {
                setTimeout(() => {
                    background.classList.remove('aktifPopup');
                    boxInput.classList.remove('aktifPopup');
                }, 300);
                boxInput.classList.remove('scale-in-center');
                boxInput.classList.add('scale-out-center');
                background.classList.remove('fade-in');
                background.classList.add('fade-out');
            });

        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {

            const openDataBtns = document.querySelectorAll('.tombolDataPopup');
            const closeData = document.getElementById('tombolClosePopup2');
            const boxData = document.getElementById('popupBoxDetail');
            const background = document.getElementById('popupBG');

            const sessionNama = <?php echo isset($_SESSION['nama']) ? json_encode($_SESSION['nama']) : 'null'; ?>;
            const sessionHakAkses = <?php echo isset($_SESSION['hak_akses']) ? json_encode($_SESSION['hak_akses']) : 'null'; ?>;
            const sessionIsSuper = <?php echo json_encode($isSuper); ?>;
            const sessionPTList = <?php echo json_encode($ptUserList); ?>;
            const showData = <?php echo json_encode($showDataAkunMenuEditable); ?>;

            const colAkses = document.getElementById('colAksesTambahan');
            const colEdit = document.getElementById('colEditAkun');

            if (!boxData || !background) return;

            function ptIntersect(detailPT, sessionPTList) {
                // detailPT bisa "RO PALANGKARAYA,RO SAMPIT"
                const pts = String(detailPT || '').split(',').map(s => s.trim()).filter(Boolean);
                for (let i = 0; i < pts.length; i++) {
                    if (sessionPTList.indexOf(pts[i]) !== -1) return true;
                }
                return false;
            }

            openDataBtns.forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();

                    const namaDetail = btn.dataset.nama;
                    const detailPT = btn.dataset.pt;

                    // ---- kontrol kolom edit akun
                    if (sessionIsSuper) {
                        colEdit.style.display = '';
                    } else {
                        colEdit.style.display = (showData == 1 && ptIntersect(detailPT, sessionPTList)) ? '' : 'none';
                    }

                    // ---- kontrol akses tambahan
                    if (colAkses) {
                        colAkses.style.display = sessionIsSuper ?
                            '' :
                            'none';
                    }

                    // ---- isi detail tampilan
                    document.getElementById('detailUsername').textContent = btn.dataset.username;
                    document.getElementById('detailPassword').textContent = btn.dataset.password;
                    document.getElementById('detailEmail').textContent = btn.dataset.email;
                    document.getElementById('detailPT').textContent = btn.dataset.pt;
                    document.getElementById('detailNama').textContent = btn.dataset.nama;
                    document.getElementById('detailNIK').textContent = btn.dataset.nik;
                    document.getElementById('detailHakAkses').textContent = btn.dataset.hakakses;

                    // ---- akses tambahan badge
                    const manajemenVal = btn.dataset.manajemen_akun_akses;
                    let badgeHTML = '';
                    if (manajemenVal == 1) badgeHTML = '<span class="badge bg-primary">Manajemen Akun</span>';
                    if (manajemenVal == 2) badgeHTML = '<span class="badge bg-success">Manajemen Akun</span>';
                    document.getElementById('detailManajemenAkun').innerHTML = badgeHTML;

                    // ---- isi form edit
                    document.getElementById('detailId').value = btn.dataset.id;
                    document.getElementById('detailUsernameInput').value = btn.dataset.username;
                    document.getElementById('detailPasswordInput').value = btn.dataset.password;
                    document.getElementById('detailEmailInput').value = btn.dataset.email;

                    document.querySelectorAll('input[name="manajemen_akun_akses"]').forEach(r => {
                        r.checked = (r.value === manajemenVal);
                    });

                    // ---- tampilkan popup detail
                    boxData.classList.add('aktifPopup', 'scale-in-center');
                    boxData.classList.remove('scale-out-center');
                    background.classList.add('aktifPopup', 'fade-in');
                    background.classList.remove('fade-out');
                    requestAnimationFrame(() => {
                        if (window.signaturePadEditRef) {
                            resizeSignatureCanvas(
                                window.signatureCanvasEditRef,
                                window.signaturePadEditRef,
                                window.signatureHiddenEditRef
                            );
                        }
                    });
                });
            });

            // tombol close popup detail
            closeData.addEventListener('click', function() {
                setTimeout(() => {
                    background.classList.remove('aktifPopup');
                    boxData.classList.remove('aktifPopup');
                }, 300);
                boxData.classList.remove('scale-in-center');
                boxData.classList.add('scale-out-center');
                background.classList.remove('fade-in');
                background.classList.add('fade-out');
            });

            // klik background → tutup popup detail SAJA
            background.addEventListener('click', function() {
                setTimeout(() => {
                    background.classList.remove('aktifPopup');
                    boxData.classList.remove('aktifPopup');
                }, 300);
                boxData.classList.remove('scale-in-center');
                boxData.classList.add('scale-out-center');
                background.classList.remove('fade-in');
                background.classList.add('fade-out');
            });

        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {

            var box = document.getElementById('popupBoxDetailKaryawan');
            var bg = document.getElementById('popupBG');
            var closeBtn = document.getElementById('tombolClosePopupKaryawanDetail');

            if (!box || !bg || !closeBtn) return;

            // init select2 untuk PT edit SITE (sekali)
            if (window.jQuery) {
                try {
                    jQuery('#site_pt_edit').select2({
                        placeholder: "-- Pilih PT --",
                        allowClear: true,
                        width: '100%'
                    });
                } catch (e) {}
            }

            function enableGroup(selector, enabled) {
                var els = box.querySelectorAll(selector);
                for (var i = 0; i < els.length; i++) {
                    els[i].disabled = !enabled;
                }
            }

            function openPopup() {
                box.classList.add('aktifPopup', 'scale-in-center');
                box.classList.remove('scale-out-center');
                bg.classList.add('aktifPopup', 'fade-in');
                bg.classList.remove('fade-out');
            }

            function closePopup() {
                setTimeout(function() {
                    bg.classList.remove('aktifPopup');
                    box.classList.remove('aktifPopup');
                }, 300);
                box.classList.remove('scale-in-center');
                box.classList.add('scale-out-center');
                bg.classList.remove('fade-in');
                bg.classList.add('fade-out');
            }

            closeBtn.addEventListener('click', function(e) {
                e.preventDefault();
                closePopup();
            });

            bg.addEventListener('click', function() {
                if (box.classList.contains('aktifPopup')) closePopup();
            });

            // ====== SITE edit helpers (mirip input, tapi khusus edit) ======
            var siteUsedPosEdit = {
                'GM': false,
                'KTU': false,
                'Staf GA': false,
                'Kepala Mill': false
            };

            function fetchSiteUsedPositionsEdit(currentId) {
                if (!window.jQuery) return;

                var selected = jQuery('#site_pt_edit').val() || [];

                jQuery.ajax({
                    url: 'tabel.php',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        ajax: 'get_site_unique_pos_edit',
                        pt: selected,
                        current_id: currentId
                    },
                    success: function(resp) {
                        siteUsedPosEdit = (resp && resp.used) ? resp.used : {
                            'GM': false,
                            'KTU': false,
                            'Staf GA': false,
                            'Kepala Mill': false
                        };
                        updateDeptOptionsEdit();
                        updateMillOptionEdit();
                        updatePosisiSiteEdit();
                    },
                    error: function() {
                        siteUsedPosEdit = {
                            'GM': false,
                            'KTU': false,
                            'Staf GA': false,
                            'Kepala Mill': false
                        };
                        updateDeptOptionsEdit();
                        updateMillOptionEdit();
                        updatePosisiSiteEdit();
                    }
                });
            }

            function updateDeptOptionsEdit() {
                var siteDept = document.getElementById('site_departemen_edit');
                if (!siteDept) return;

                var optGM = siteDept.querySelector('option[value="GM"]');
                var optHRD = siteDept.querySelector('option[value="HRD"]');
                var usedGM = !!siteUsedPosEdit['GM'];
                var usedHRD = !!siteUsedPosEdit['Staf GA'];

                if (optGM) optGM.style.display = usedGM ? 'none' : '';
                if (optHRD) optHRD.style.display = usedHRD ? 'none' : '';
            }

            function updateMillOptionEdit() {
                var optMill = document.getElementById('optMillPKS_edit');
                var siteDept = document.getElementById('site_departemen_edit');
                if (!optMill || !window.jQuery) return;

                var selected = jQuery('#site_pt_edit').val() || [];
                var pkspOnly = ["PT.PEAK (PKS)", "PT.MSAL (PKS)", "PT.PSAM (PKS)", "PT.WCJU (PKS)"];

                var showByPT = (selected.length === 1 && pkspOnly.indexOf(selected[0]) !== -1);
                var usedKepalaMill = !!siteUsedPosEdit['Kepala Mill'];
                var show = showByPT && !usedKepalaMill;

                optMill.style.display = show ? '' : 'none';

                if (!show && siteDept && siteDept.value === 'MILL PKS') siteDept.value = '';
            }

            function updatePosisiSiteEdit() {
                var siteDept = document.getElementById('site_departemen_edit');
                var sitePos = document.getElementById('site_posisi_edit');
                if (!siteDept || !sitePos) return;

                var dept = siteDept.value;
                var usedKTU = !!siteUsedPosEdit['KTU'];

                if (!dept) {
                    sitePos.innerHTML = "<option value=''>-- Pilih Posisi --</option>";
                    sitePos.disabled = true;
                    return;
                }

                sitePos.disabled = false;

                if (dept === 'IT') {
                    var opt = "<option value=''>-- Pilih Posisi --</option><option value='IT Support'>IT Support</option>";
                    if (!usedKTU) opt += "<option value='KTU'>KTU</option>";
                    sitePos.innerHTML = opt;
                    sitePos.disabled = false;
                } else if (dept === 'HRD') {
                    sitePos.innerHTML = "<option value='Staf GA'>Staf GA</option>";
                    sitePos.value = "Staf GA";
                    sitePos.disabled = true;
                } else if (dept === 'GM') {
                    sitePos.innerHTML = "<option value='GM'>GM</option>";
                    sitePos.value = "GM";
                    sitePos.disabled = true;
                } else if (dept === 'MILL PKS') {
                    sitePos.innerHTML = "<option value='Kepala Mill'>Kepala Mill</option>";
                    sitePos.value = "Kepala Mill";
                    sitePos.disabled = true;
                } else {
                    sitePos.innerHTML = "<option value=''>-- Pilih Posisi --</option>";
                    sitePos.disabled = true;
                }
            }

            // event change edit site
            var siteDeptEdit = document.getElementById('site_departemen_edit');
            if (siteDeptEdit) siteDeptEdit.addEventListener('change', updatePosisiSiteEdit);

            if (window.jQuery) {
                jQuery('#site_pt_edit').on('change', function() {
                    var currentId = parseInt(document.getElementById('editKaryawanId').value || '0', 10);
                    updateMillOptionEdit();
                    fetchSiteUsedPositionsEdit(currentId);
                });
            }

            // ====== tombol open popup ======
            var btns = document.querySelectorAll('.tombolKaryawanPopup');
            for (var i = 0; i < btns.length; i++) {
                btns[i].addEventListener('click', function(e) {
                    e.preventDefault();

                    var sumber = this.getAttribute('data-sumber') || '';
                    var id = this.getAttribute('data-id') || '';
                    var nama = this.getAttribute('data-nama') || '';
                    var nik = this.getAttribute('data-nik') || '';
                    var pt = this.getAttribute('data-pt') || '';
                    var lantai = this.getAttribute('data-lantai') || '';
                    var dept = this.getAttribute('data-departemen') || '';
                    var jab = this.getAttribute('data-jabatan') || '';
                    var pos = this.getAttribute('data-posisi') || '';
                    var lok = this.getAttribute('data-lokasi') || '';

                    // isi VIEW
                    document.getElementById('detailKaryawanNama').textContent = nama;
                    document.getElementById('detailKaryawanNIK').textContent  = nik;
                    document.getElementById('detailKaryawanPT').textContent   = pt;

                    // ambil row-row optional
                    var rowLantai = document.getElementById('rowDetailKaryawanLantai');
                    var rowLokasi = document.getElementById('rowDetailKaryawanLokasi');
                    var rowPosisi = document.getElementById('rowDetailKaryawanPosisi');

                    function hideRow(el) {
                        if (!el) return;
                        el.style.setProperty('display', 'none', 'important');
                    }
                    function showRow(el) {
                        if (!el) return;
                        el.style.removeProperty('display');
                    }

                    // reset tampilan (default: sembunyikan semua baris optional)
                    hideRow(rowLantai);
                    hideRow(rowLokasi);
                    hideRow(rowPosisi);

                    // reset isi teks (anti “kebawa”)
                    document.getElementById('detailKaryawanLantai').textContent = '';
                    document.getElementById('detailKaryawanLokasi').textContent = '';
                    document.getElementById('detailKaryawanPosisi').textContent = '';
                    document.getElementById('detailKaryawanJabatan').textContent = '';

                    if (sumber === 'HO') {
                        // HO: tampilkan Lantai + Posisi, Jabatan sudah include departemen
                        showRow(rowLantai);
                        showRow(rowPosisi);

                        document.getElementById('detailKaryawanLantai').textContent = lantai;
                        document.getElementById('detailKaryawanJabatan').textContent = jab + " - " + dept;
                        document.getElementById('detailKaryawanPosisi').textContent = pos;

                    } else {
                        // SITE: tampilkan Lokasi, Jabatan = posisi(dept)
                        showRow(rowLokasi);

                        document.getElementById('detailKaryawanLokasi').textContent = lok;
                        document.getElementById('detailKaryawanJabatan').textContent = pos + " (" + dept + ")";
                        // rowLantai & rowPosisi tetap hidden
                    }

                    // isi hidden edit
                    document.getElementById('editKaryawanId').value = id;
                    document.getElementById('editKaryawanSumber').value = sumber;

                    // toggle form edit HO/SITE
                    var formHO = document.getElementById('editFormHO');
                    var formSI = document.getElementById('editFormSITE');

                    if (sumber === 'HO') {
                        if (formHO) formHO.style.display = '';
                        if (formSI) formSI.style.display = 'none';

                        enableGroup('#editFormHO input, #editFormHO select', true);
                        enableGroup('#editFormSITE input, #editFormSITE select', false);

                        // set value HO
                        document.getElementById('ho_nama_edit').value = nama;
                        document.getElementById('ho_lantai_edit').value = lantai;
                        document.getElementById('ho_posisi_edit').value = pos;

                        if (window.jQuery) {
                            // refresh dropdown departemen edit
                            jQuery.ajax({
                                url: 'tabel.php',
                                type: 'POST',
                                data: {
                                    ajax: 'get_departemen_ho_edit',
                                    current_id: id,
                                    current_departemen: dept
                                },
                                success: function(htmlDept) {
                                    jQuery('#ho_departemen_edit').html(htmlDept).val(dept);
                                },
                                error: function() {
                                    // biarkan existing option kalau gagal
                                    jQuery('#ho_departemen_edit').val(dept);
                                }
                            });

                            // refresh jabatan edit
                            jQuery('#ho_jabatan_edit').prop('disabled', true).html("<option value=''>-- Pilih Jabatan --</option>");
                            jQuery.ajax({
                                url: 'tabel.php',
                                type: 'POST',
                                data: {
                                    ajax: 'get_jabatan_ho_edit',
                                    departemen: dept,
                                    current_id: id
                                },
                                success: function(html) {
                                    jQuery('#ho_jabatan_edit').html(html).prop('disabled', false).val(jab);
                                },
                                error: function() {
                                    jQuery('#ho_jabatan_edit').html("<option value=''>-- Pilih Jabatan --</option>").prop('disabled', true);
                                }
                            });
                        }

                        if (window.jQuery) {
                            jQuery('#ho_departemen_edit').on('change', function() {
                                var dept = jQuery(this).val();
                                var currentId = jQuery('#editKaryawanId').val() || 0;

                                jQuery('#ho_jabatan_edit')
                                    .prop('disabled', true)
                                    .html("<option value=''>-- Pilih Jabatan --</option>");

                                if (!dept) return;

                                jQuery.ajax({
                                    url: 'tabel.php',
                                    type: 'POST',
                                    data: {
                                        ajax: 'get_jabatan_ho_edit',
                                        departemen: dept,
                                        current_id: currentId
                                    },
                                    success: function(html) {
                                        jQuery('#ho_jabatan_edit').html(html).prop('disabled', false);
                                    },
                                    error: function() {
                                        jQuery('#ho_jabatan_edit')
                                            .html("<option value=''>-- Pilih Jabatan --</option>")
                                            .prop('disabled', true);
                                    }
                                });
                            });
                        }

                    } else { // SITE
                        if (formHO) formHO.style.display = 'none';
                        if (formSI) formSI.style.display = '';

                        enableGroup('#editFormHO input, #editFormHO select', false);
                        enableGroup('#editFormSITE input, #editFormSITE select', true);

                        // set value SITE
                        document.getElementById('site_nama_edit').value = nama;
                        document.getElementById('site_lokasi_edit').value = lok;

                        // set PT multi dari string "A,B,C"
                        var pts = String(pt || '').split(',');
                        for (var p = 0; p < pts.length; p++) pts[p] = pts[p].trim();
                        if (window.jQuery) {
                            jQuery('#site_pt_edit').val(pts).trigger('change');
                        }

                        document.getElementById('site_departemen_edit').value = dept;
                        updatePosisiSiteEdit(); // build opsi posisi sesuai dept

                        // posisi terpilih (kalau dept IT)
                        var sitePos = document.getElementById('site_posisi_edit');
                        if (sitePos && !sitePos.disabled) sitePos.value = pos;

                        fetchSiteUsedPositionsEdit(parseInt(id, 10));
                    }

                    openPopup();
                });
            }

        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const open = document.getElementById('personaliaBtn');
            const box = document.getElementById('popupBoxPersonalia');
            const background = document.getElementById('popupBG');

            open.addEventListener('click', function() {
                box.classList.add('aktifPopup');
                background.classList.add('aktifPopup');
                box.classList.add('scale-in-center');
                box.classList.remove('scale-out-center');
                background.classList.add('fade-in');
                background.classList.remove('fade-out');
            });

            background.addEventListener('click', function() {
                // box.classList.remove('aktifPopup');
                // background.classList.remove('aktifPopup');
                setTimeout(() => {
                    background.classList.remove('aktifPopup');
                    box.classList.remove('aktifPopup');
                }, 300);
                box.classList.remove('scale-in-center');
                box.classList.add('scale-out-center');
                background.classList.remove('fade-in');
                background.classList.add('fade-out');
            });
        });
    </script>

    <script>
        //Validasi Username
        $(document).ready(function() {
            $(".usernameInput").on("input", function() {
                var username = $(this).val().trim();
                var input = this;

                if (username.length === 0) {
                    input.setCustomValidity("Harap isi username.");
                    return;
                }

                $.ajax({
                    url: "cek_username.php", // file PHP untuk cek username
                    type: "POST",
                    data: {
                        username: username
                    },
                    success: function(response) {
                        if (response === "exists") {
                            input.setCustomValidity("Username sudah digunakan.");
                        } else {
                            input.setCustomValidity("");
                        }
                    },
                    error: function() {
                        input.setCustomValidity("Gagal memeriksa username.");
                    }
                });
            });
        });
    </script>

    <script>
        $(document).ready(function() {
            $('#selectNama').select2({
                placeholder: "-- Pilih Nama --",
                allowClear: true,
                width: '64%'
            });

            function setNikFromSelected() {
                var nik = $('#selectNama').find(':selected').data('nik') || '';
                $('#inputNik').val(nik);
            }

            // saat nama dipilih / berubah
            $('#selectNama').on('change', setNikFromSelected);

            // saat select2 di-clear (kadang change tidak kepanggil tergantung versi)
            $('#selectNama').on('select2:clear', function() {
                $('#inputNik').val('');
            });

            // awal halaman
            $('#inputNik').val('');

            $('#selectPT').on('change', function() {
                var pt = $(this).val();

                // ✅ selalu reset nik ketika PT ganti
                $('#inputNik').val('');

                if (pt === "") {
                    $('#selectNama').html('<option value="">-- Pilih Nama --</option>');
                    $('#selectNama').val(null).trigger('change');
                    $('#selectPeran').html('<option value="">-- Pilih Peran --</option>');
                    return;
                }

                $.ajax({
                    url: "get_nama.php",
                    type: "POST",
                    data: {
                        pt: pt
                    },
                    success: function(data) {
                        $('#selectNama').html(data);
                        $('#selectNama').val(null).trigger('change'); // ini akan bikin nik tetap kosong
                    },
                    error: function() {
                        alert("Terjadi kesalahan saat mengambil data nama.");
                    }
                });

                $.ajax({
                    url: "get_peran.php",
                    type: "POST",
                    data: {
                        pt: pt
                    },
                    success: function(data) {
                        $('#selectPeran').html(data);
                    }
                });
            });
        });
    </script>



    <script>
        $(document).ready(function() {
            $('#myTable').DataTable({
                responsive: true,
                autoWidth: true,
                language: {
                    url: "../../assets/json/id.json"
                },
                scrollY: "210px",
                scrollCollapse: true,
                paging: true,
                columnDefs: [{
                        targets: -1,
                        orderable: false
                    },

                ],
                initComplete: function() {
                    // Sembunyikan skeleton
                    $('#tableSkeleton').fadeOut(200, function() {
                        $('#tabelUtama').fadeIn(200);
                    });
                }
            });
        });
        $(document).ready(function() {
            $('#myTableKaryawan').DataTable({
                responsive: true,
                autoWidth: true,
                language: {
                    url: "../../assets/json/id.json"
                },
                scrollY: "210px",
                scrollCollapse: true,
                paging: true,
                columnDefs: [{
                    targets: -1,
                    orderable: false
                }]
            });
        });
        $(document).ready(function() {
            $('#myTable2').DataTable({
                responsive: true,
                autoWidth: false,
                language: {
                    url: "../../assets/json/id.json"
                },
                scrollY: "150px",
                scrollCollapse: true,
                paging: false
            });
        });
        $(document).ready(function() {
            $('#myTable3').DataTable({
                responsive: true,
                autoWidth: false,
                language: {
                    url: "../../assets/json/id.json"
                },
                scrollY: "150px",
                scrollCollapse: true,
                paging: false
            });
        });
    </script>

    <script>
        //Konfigurasi OverlayScrollbars

        //-----------------------------------------------------------------------------------
        const SELECTOR_SIDEBAR_WRAPPER = '.sidebar-wrapper';
        const Default = {
            scrollbarTheme: 'os-theme-light',
            scrollbarAutoHide: 'leave',
            scrollbarClickScroll: true,
        };
        document.addEventListener('DOMContentLoaded', function() {
            const sidebarWrapper = document.querySelector(SELECTOR_SIDEBAR_WRAPPER);
            if (sidebarWrapper && typeof OverlayScrollbarsGlobal?.OverlayScrollbars !== 'undefined') {
                OverlayScrollbarsGlobal.OverlayScrollbars(sidebarWrapper, {
                    scrollbars: {
                        theme: Default.scrollbarTheme,
                        autoHide: Default.scrollbarAutoHide,
                        clickScroll: Default.scrollbarClickScroll,
                    },
                });
            }
        });
        //-----------------------------------------------------------------------------------
    </script>

    <script>
        const alert = document.querySelector('.infoin-approval');
        setTimeout(() => {
            alert.classList.add('fade-out');
            alert.classList.remove('fade-in');
        }, 3000);
        setTimeout(() => {
            alert.style.display = 'none';
        }, 3500);
    </script>

    <script>
        //Sidebar

        //-----------------------------------------------------------------------------------
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('show');

            // Event listener satu kali untuk klik luar
            if (sidebar.classList.contains('show')) {
                document.addEventListener('click', handleClickOutsideSidebar);
            } else {
                document.removeEventListener('click', handleClickOutsideSidebar);
            }
        }

        function handleClickOutsideSidebar(event) {
            const sidebar = document.getElementById('sidebar');
            const toggleButton = event.target.closest("button[onclick='toggleSidebar()']");

            if (!sidebar.contains(event.target) && !toggleButton) {
                sidebar.classList.remove('show');
                document.removeEventListener('click', handleClickOutsideSidebar);
            }
        }
        //-----------------------------------------------------------------------------------
    </script>

    <script>
        //Tanggal

        //-----------------------------------------------------------------------------------
        function updateDate() {
            const now = new Date();
            const options = {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            };
            const formattedDate = now.toLocaleDateString('id-ID', options);
            document.getElementById('date').textContent = formattedDate;
        }
        setInterval(updateDate, 1000); // Update setiap detik
        updateDate(); // Panggil langsung saat halaman load
        //-----------------------------------------------------------------------------------
    </script>

    <script>
        // Jam Digital
        //-----------------------------------------------------------------------------------

        function updateClock() {
            const now = new Date();
            const jam = String(now.getHours()).padStart(2, '0');
            const menit = String(now.getMinutes()).padStart(2, '0');
            const detik = String(now.getSeconds()).padStart(2, '0');
            document.getElementById('clock').textContent = `${jam}:${menit}:${detik}`;
        }

        setInterval(updateClock, 1000);
        updateClock(); // Panggil langsung saat halaman load
        //-----------------------------------------------------------------------------------
    </script>

</body>

</html>
<?php

?>