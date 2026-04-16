<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../login_registrasi.php");
    exit();
}
if (!isset($_SESSION['hak_akses']) || $_SESSION['hak_akses'] !== 'Admin') {
    header("Location: ../personal/approval.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Berita Acara Kerusakan</title>
<style>
    body {
    font-family: Arial, sans-serif;
    margin: 0px;
    color: #000;
    }

    .header img {
    float: left;
    width: 70px;
    margin-right: 15px;
    }

    .header h1 {
    font-size: 20px;
    margin: 0;
    padding-top: 10px;
    }

    .header h2 {
    font-size: 16px;
    font-weight: normal;
    margin: 0;
    }

    .center-title {
    text-align: center;
    font-weight: bold;
    margin-top: 30px;
    }

    .center-title .number {
    font-size: 16px;
    margin-top: 5px;
    }

    .content {
    margin: 20px 30px 0 30px;
    font-size: 14px;
    }

    table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
    font-size: 14px;
    }

    table, td, th {
    border: 1px solid black;
    vertical-align: top;
    }

    td, th {
    padding: 8px;
    }
    
    .section-title {
    font-weight: bold;
    margin-top: 20px;
    }

    .note {
    font-size: 13px;
    margin-top: 10px;
    }

    .ttd {
    margin-top: 30px;
    font-size: 14px;
    font-weight: bold;
    }

    .ttd table {
    border: none;
    }

    .ttd tr:first-child{
        position: relative;
    }

    .ttd td {
    border: none;
    text-align: center;
    }

    .ttd tr:first-child td:first-child p:first-child{
    text-align: left;
    }
    .ttd tr:first-child td:first-child p:last-child{
    text-align: left;
    padding-left: 20px;
    }

    .ttd tr:first-child td:last-child{
        position: relative;
    }

    .ttd tr:first-child td:last-child div{
        transform: translateY(47px);
    }

    .signature-row {
    height: 100px;
    }

    .signature {
    padding-top: 110px;
    font-weight: bold;
    }

    .signature:first-child{
        text-align: left;
        padding-left: 20px;
    }
    .signature:nth-child(2){
        transform: translateX(-12px);
    }

    .gambar-kerusakan {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 20px;
    }

    .gambar-kerusakan img {
    max-width: 300px;
    height: auto;
    display: inline-block;
    margin: 20px;
    object-fit: contain;
    }

</style>

<style>/*Media Print*/
    @media print{
        .page-break {
        page-break-before: always;
        }
    }
</style>
</head>
<body>
    <div class="header">
        <img src="../assets/img/logo.png" alt="Logo MSAL">
        <h1>MULIA</h1>
        <h2>SAWIT AGRO LESTARI</h2>
    </div>

    <div class="center-title">
        BERITA ACARA KERUSAKAN
        <div class="number">No:15/MIS-HO/V/2025</div>
    </div>

    <div class="content">
        <p style="font-weight:bold;">Pada hari ini selasa, 13 mei 2025 telah dilakukan pemeriksaan oleh Dept. MIS dimana telah ditemukan kerusakan sebagai berikut :</p>

        <table>
        <tr>
            <th>Jenis Perangkat</th>
            <th>Merek/ Th Perolehan</th>
            <th>Lokasi</th>
            <th>Pengguna</th>
        </tr>
        <tr>
            <td>Monitor</td>
            <td>
            AOC <br />
            SN : AQLKC1A000960 <br />
            Tahun : 2021
            </td>
            <td>Lantai 1</td>
            <td>nabila<br />HRD<br />Staf</td>
        </tr>
        </table>

        <table>
        <tr>
            <td>
            <p style="margin:0;font-weight:bold;">Jenis kerusakan:</p>
            </td>
        </tr>
        <tr>
            <td>Layar di monitor menjadi putih<br />Garis garis di excel tidak terlihat jelas</td>
        </tr>
        </table>

        <table>
        <tr>
            <td>
            <p style="margin:0;font-weight:bold;">Penyebab Kerusakan:</p>
            </td>
        </tr>
        <tr>
            <td>
            1. Panas Berlebih<br />
            2. Dipakai terus menerus dengan durasi penggunaan yang tinggi dan terus menerus<br />
            3.<br />
            4.
            </td>
        </tr>
        </table>

        
        <table>
        <tr>
            <td>
            <p style="margin:0;font-weight:bold;">Rekomendasi MIS:</p>
            </td>
        </tr>
        <tr>
            <td>
            Sudah dilakukan pengecekan, dan ketika di lakukan penggantian monitor tampilan excel menjadi normal<br />
            Rekomendasi :<br />
            1. Ganti Monitor (sementara ditukar dengan aset MIS digital 4 SN ET9BB02152019)
            </td>
        </tr>
        </table>

        <p style="font-weight:bold;" class="note">Demikian berita acara ini dibuat sebagai dasar untuk perbaikan atau penggantian perangkat yang rusak.</p>
        <p style="font-weight:bold;" class="note">Note : tahun pembelian 2021</p>

        

        <div class="ttd">
        <table style="width: 100%;">
            <tr>
            <td><p>Jakarta, 13 mei 2025</p><p>DIBUAT OLEH,</p></td>
            <td></td>
            <td><div>DIKETAHUI OLEH,</div></td>
            </tr>
            <tr class="signature-row">
            <td class="signature">RIZKY SUNANDAR</td>
            <td class="signature">Saiful Huda<br /><span class="subtext">SPV MIS</span></td>
            <td class="signature">Tedy Paronto<br /><span class="subtext">Dept. Head MIS</span></td>
            </tr>
        </table>
        </div>
    </div>

    <div class="page-break"></div>

    <div class="header">
        <img src="../assets/img/logo.png" alt="Logo MSAL">
        <h1>MULIA</h1>
        <h2>SAWIT AGRO LESTARI</h2>
    </div>

    <div class="center-title">
        BERITA ACARA KERUSAKAN
        <div class="number">No:15/MIS-HO/V/2025</div>
    </div>

    <div>
        <div class="gambar-kerusakan">
            <img src="../assets/database-gambar/1750127741_43441639-upload-to-cloud-storage-line-art-icon-for-apps-and-websites.jpg" alt="Gambar Kerusakan">
            <img src="../assets/database-gambar/1750127741_43441640-upload-to-cloud-storage-line-art-icon-for-apps-and-websites.jpg" alt="Gambar Kerusakan">
        </div>
    </div>
</body>
</html>
