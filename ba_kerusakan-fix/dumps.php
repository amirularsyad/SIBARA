<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>BA Kerusakan</title>

  <!-- Bootstrap 5 -->
    <link 
      rel="stylesheet" 
      href="../assets/bootstrap-5.3.6-dist/css/bootstrap.min.css"
    />

  <!-- Bootstrap Icons -->
    <link
        rel="stylesheet"
        href="../assets/icons/icons-main/font/bootstrap-icons.min.css"
    />

  <!-- AdminLTE -->
    <link 
        rel="stylesheet" 
        href="../assets/adminlte/css/adminlte.css" 
    />

  <!-- OverlayScrollbars -->
    <link
        rel="stylesheet"
        href="../assets/css/overlayscrollbars.min.css"
    />

  <!-- Favicon -->
    <link 
        rel="icon" type="image/png" 
        href="../assets/img/logo.png"
    />

    <link 
        rel="icon" type="image/png" 
        href="../assets/css/datatables.min.css"
    />

    <link 
        rel="stylesheet" 
        href="../assets/css/datatables.min.css"
    />

<style> /* Main Styles */
    
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background: #f9f9f9;
    }

    .app-wrapper{
        position: relative;
    }

    #date{
        margin-right: 10px;
    }

    #clock {
        font-size: 16px;
        color: white;
        margin-right: 20px;
    }

    .akun-info{
    right:-300px;
    opacity: 0;
    }

    .aktif{
    right: 0;
    opacity: 1;
    transition: all .3s ease-in-out;
    }

    .aktifLT{
        display: flex;
    }

    .app-sidebar{
            background: linear-gradient(to bottom right, #3e02be 0%,rgb(1, 64, 159) 50%, rgb(2, 59, 159) 100%) !important;
    }

    .navbar{
            background: linear-gradient(to right, rgb(1, 64, 159) 0%, rgb(2, 77, 190) 60%, rgb(2, 77, 207) 100%) !important;
    }

    h2, h3 {
        color: #2c3e50;
        text-align: center;
        margin-bottom: 25px;
    }

    .app-main{
        display: flex;
        align-items: center;
        margin-top: 40px;
    }

    /* style table */

    .table-wrapper{
        width: 97%;
        height: auto;
        overflow-x: auto;
        margin: 20px 0;
        border-radius: 10px;
        padding: 10px;
    }

    th,td,table tbody tr td .btn-sm{
        font-size: .7rem;
    }

    th, td{
        text-align: center !important;
    }

    th:nth-child(1), td:nth-child(1) { width: 4%; text-align: center; } /* No */
    th:nth-child(2), td:nth-child(2) { width: 6%; }  /* Tanggal */
    th:nth-child(3), td:nth-child(3) { width: 6%; }  /* Tanggal */
    th:nth-child(4), td:nth-child(4) { width: 10%; }  /* Jenis Perangkat */
    th:nth-child(5), td:nth-child(5) { width: 220px; }  /* Merek */
    th:nth-child(6), td:nth-child(6) { width: 220px; }  /* User */
    th:nth-child(7), td:nth-child(7) { width: 200px; }  /* Lokasi */
    th:nth-child(8), td:nth-child(8) { width: 350px; }  /* Jenis Kerusakan */
    /*th:nth-child(9), td:nth-child(9) { width: 50px; }   Status Approval 1 */
    /*th:nth-child(10), td:nth-child(10) { width: 50px; }   Status Approval 2 */
    th:nth-child(11), td:nth-child(11) { width: 50px; height:100% !important; text-align: center; }   /* Actions */

    .popupInput, .popupEdit {
        
        width: 100%;
        padding: 25px 30px;
        border-radius: 10px;
    }

    input[type="submit"] {
      background: #2980b9;
      color: white;
      padding: 10px 20px;
      border: none;
      font-size: 16px;
      cursor: pointer;
      border-radius: 5px;
      margin-top: 20px;
      
    }

    input[type="submit"]:hover {
      background: #1c5980;
    }

    .popup-box{
        display: none;
    }

    .popup-bg{
        display:none;
    }

    .aktifPopup{
        display:flex;
    }

    .table-approval th,.table-approval td{
        border: none;
        padding: 5px;
    }

    @media (max-width: 1670px) {
        .btn{
        margin-bottom: 5px;
    }
    }

    .bi-list, .bi-arrows-fullscreen, .bi-fullscreen-exit {
            color: #fff !important;
    }
</style>

<style>/*animista.net*/ 

</style>

<style> /* scroll styling */
    .scroll-container {
      height: 100vh;          /* tinggi penuh layar */
      overflow-y: scroll;     /* scroll tetap aktif */
      -ms-overflow-style: none;  /* IE dan Edge */
      scrollbar-width: none;     /* Firefox */
    }

    .scroll-container::-webkit-scrollbar {
      display: none;            /* Chrome, Safari, Opera */
    }
</style>



<style>/*animista.net*/ 
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
</style>

</head>
<body  class="layout-fixed sidebar-expand-lg bg-body-tertiary overflow-x-hidden">
<form class="popupEdit d-flex flex-column" method="post" action="proses_edit.php" onsubmit="return confirm('Simpan perubahan?')" enctype="multipart/form-data">
            <input type="hidden" name="id" value="${escapeHtml(data.id)}">
            <div class="form-section">
            <div class="row position-relative">
                <div class="col-8">
                <h3>Data Berita Acara</h3>

                <div class="row">
                    <div class="col-3">
                    <div class="input-group" style="width:220px;">
                        <span class="input-group-text">Tanggal</span>
                        <input class="form-control" type="date" name="tanggal" max="${new Date().toISOString().slice(0,10)}" value="${escapeHtml(data.tanggal||'')}" required>
                    </div>
                    </div>
                    <div class="col-4">
                    <div class="input-group" style="width:180px;">
                        <span class="input-group-text">Nomor BA</span>
                        <input type="number" class="form-control" name="nomor_ba" value="${escapeHtml(data.nomor_ba||'')}" required>
                    </div>
                    </div>
                </div>

                <div class="row mt-3 border border-1 p-1 rounded-2 me-1">
                    <div class="row"><h5>Data barang</h5></div>
                    <div class="col-6">
                        <div class="input-group">
                        <span class="input-group-text" style="padding-right:37px;">SN</span>
                        <select name="sn" id="edit-sn" class="form-select" required>
                            <option value="">-- Pilih SN --</option>
                            <option value="MSALHO12345" >MSALHO12345</option>
                        </select>
                        </div>
                    </div>
                    <div class="input-group w-50">
                        <span class="input-group-text" style="padding-right:52px;">Jenis Perangkat</span>
                        <input class="form-control" type="text" name="jenis_perangkat" value="" readonly>
                    </div>
                    <div class="col-6 mt-3">
                    <div class="input-group">
                        <span class="input-group-text">Merek</span>
                        <input class="form-control" type="text" name="merek" value="" readonly>
                    </div>
                    </div>
                    <div class="col-6 mt-3">
                    <div class="input-group">
                        <span class="input-group-text" style="padding-right:45px;">Tahun Perolehan</span>
                        <input type="text" class="form-control" name="tahun_perolehan" value="" readonly>
                    </div>
                    </div>
                </div>
                
                <!-- <div class="row mt-3 border border-1 p-1 rounded-2 me-1">
                    <div class="row"><h5>Data barang</h5>

                    <div class="col-4">
                        <div class="input-group">
                        <span class="input-group-text">SN</span>
                        <select name="sn" id="edit-sn" class="form-select" required>
                            <option value="">-- Pilih SN --</option>
                            <option value="MSALHO12345" >MSALHO12345</option>
                        </select>
                        </div>
                    </div>

                    </div>
                    <div class="row m-0 p-0 mt-1 d-flex flex-column">
                        <div class="d-flex m-0 p-0 ms-3">
                            <div class="m-0 p-0" style="width: max-content;">Jenis Perangkat</div>
                            <div class="p-0" style="width: max-content; margin-left:20px;">:</div>
                        </div>

                        <div class="d-flex m-0 p-0 ms-3">
                            <div class="m-0 p-0" style="width: max-content;">Merek</div>
                            <div class="p-0" style="width: max-content;margin-left:84px;">:</div>
                        </div>

                        <div class="d-flex m-0 p-0 ms-3">
                            <div class="m-0 p-0" style="width: max-content;">Tahun Perolehan</div>
                            <div class="p-0" style="width: max-content;margin-left:13px;">:</div>
                        </div>
                    </div>
                </div> -->
                

                <div class="row mt-3 border border-1 p-1 rounded-2 me-1">
                    <div class="row"><h5>Data Pengguna</h5></div>
                    <div class="row">
                    <div class="col-4">
                        <div class="input-group">
                        <span class="input-group-text">Lokasi</span>
                        <select name="pt" id="edit-pt" class="form-select" required>
                            <option value="">-- Pilih Lokasi --</option>
                            <option value="PT.MSAL (HO)" ${data.pt === 'PT.MSAL (HO)' ? 'selected' : ''}>PT.MSAL (HO)</option>
                        </select>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="input-group">
                        <span class="input-group-text">Lantai</span>
                        <select name="lokasi" id="edit-lokasi" class="form-select" ${data.pt !== 'PT.MSAL (HO)' ? 'disabled' : ''} required>
                            <option value="">-- Pilih Lantai --</option>
                        </select>
                        </div>
                    </div>
                    </div>

                    <div class="row mt-3 pe-0">
                    <div class="col-6">
                        <div class="input-group">
                        <span class="input-group-text">Pengguna</span>
                        <select name="user" id="edit-user" class="form-select" required>
                            <option value="">-- Pilih Pengguna --</option>
                        </select>
                        </div>
                    </div>
                    <div class="col-6 pe-0">
                        <div class="input-group">
                        <span class="input-group-text">Atasan Peminjam</span>
                        <select name="atasan_peminjam" id="edit-atasan" class="form-select">
                            <option value="">-- Pilih Atasan Peminjam --</option>
                        </select>
                        </div>
                    </div>
                    </div>
                </div>

                <div class="row mt-3 border border-1 p-1 rounded-2 me-1">
                    <div class="row"><h5>Laporan Kerusakan</h5></div>
                    <div class="row pe-0">
                    <div class="col-6">
                        <div class="input-group">
                        <span class="input-group-text">Jenis Kerusakan</span>
                        <textarea name="deskripsi" class="form-control" style="font-size:small;" rows="3" required>${escapeHtml(data.deskripsi||'')}</textarea>
                        </div>
                    </div>
                    <div class="col-6 pe-0">
                        <div class="input-group">
                        <span class="input-group-text">Penyebab Kerusakan</span>
                        <textarea name="penyebab_kerusakan" class="form-control" style="font-size:small;" rows="3" required>${escapeHtml(data.penyebab_kerusakan||'')}</textarea>
                        </div>
                    </div>
                    </div>
                    <div class="row mt-3 pe-0">
                    <div class="col-12 pe-0">
                        <div class="input-group">
                        <span class="input-group-text">Rekomendasi MIS</span>
                        <textarea name="rekomendasi_mis" class="form-control" style="font-size:small;" rows="2" required>${escapeHtml(data.rekomendasi_mis||'')}</textarea>
                        </div>
                    </div>
                    </div>
                </div>
                </div>

                <div class="col-4">
                <h3>Gambar Kerusakan</h3>
                <div class="border border-2 rounded-3 p-1" style="height:485px; overflow-y:auto;">
                    <div class="d-flex flex-column">
                    <div id="edit-gambar-container" class="d-flex flex-column gap-2">
                        ${gambarHTML}
                    </div>
                    <button type="button" class="btn btn-primary w-50 align-self-center mb-1" onclick="tambahGambarEdit()">+ Tambah Gambar Laporan</button>
                    </div>
                </div>
                </div>

            </div>
            </div>

            <input class="w-25 align-self-end btn btn-success mt-3" type="submit" value="Simpan">
        </form>
        </body>
        </html>