<?php
session_start();

// $errors = [
//     'login' => $_SESSION['login_error'] ?? '',
//     'registrasi'=> $_SESSION['registrasi_error'] ?? ''
// ];

// $success = [
//     'registrasi' => $_SESSION['registrasi_success'] ?? ''
// ];

// $formAktif = $_SESSION['active_form'] ?? 'login';

$errors = [
    'login'      => isset($_SESSION['login_error']) ? $_SESSION['login_error'] : '',
    'registrasi' => isset($_SESSION['registrasi_error']) ? $_SESSION['registrasi_error'] : ''
];

$success = [
    'registrasi' => isset($_SESSION['registrasi_success']) ? $_SESSION['registrasi_success'] : ''
];

$formAktif = isset($_SESSION['active_form']) ? $_SESSION['active_form'] : 'login';


session_unset();

function showError($error){
    return !empty($error) ? "<p class='error-message' id='alert-message'>$error</p>" : '';
}

function showSuccess($message){
    return !empty($message) ? "<p class='success-message' id='alert-message'>$message</p>" : '';
}

function isActiveForm($formName, $formAktif){
    return $formName === $formAktif ? 'active' : '';
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Aplikasi SIBARA</title>
    <!-- Bootstrap 5 -->
    <link 
        rel="stylesheet" 
        href="assets/bootstrap-5.3.6-dist/css/bootstrap.min.css"
    />

    <!-- Bootstrap Icons -->
    <link
        rel="stylesheet"
        href="assets/icons/icons-main/font/bootstrap-icons.min.css"
    />
    <!-- Favicon -->
    <link 
        rel="icon" type="image/png" 
        href="assets/img/logo.png"
    />
<style> /* gradient-bg 24s */
.background-gradasi-biru-hijau{
    background: linear-gradient(to bottom right,
    #070049,   
    #001283,   
    #0012ce,   
    #32b4ff,   
    #5effa7    
    );
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
    .wrapper{
        width: 100%;
        margin-inline: auto;
        position: absolute;
        height: 81px;
        margin-bottom: 2rem;
        overflow: hidden;
        z-index: -1;
        mask-image: linear-gradient(
            to right,
            rgba(0,0,0,0),
            rgba(0,0,0,1) 20%,
            rgba(0,0,0,1) 80%,
            rgba(0,0,0,0),
        );
    }

@keyframes scrollCard {
    to{
        left: -300px;
    }
}

    .item{
        width: 300px;
        height: 80px;
        background-color: transparent;
        border-radius: 6px;
        position: absolute;
        left: max(calc(300px * 5),100%);
        animation: scrollCard 60s linear infinite;
        display: flex;
        justify-content: center;
        align-items: end;
    }

    .item1{
        animation-delay: calc(60s / 5 * (5 - 1) * -1);
    }

    .item2{
        animation-delay: calc(60s / 5 * (5 - 2) * -1);
    }

    .item3{
        animation-delay: calc(60s / 5 * (5 - 3) * -1);
        
    }

    .item4{
        animation-delay: calc(60s / 5 * (5 - 4) * -1);
    }

    .item5{
        animation-delay: calc(60s / 5 * (5 - 5) * -1);
    }
    .form-switchs{
        display: none;
    }
    .active{
        display: flex;
    }

    .error-message{
        padding: 12px;
        background-color: #f8d7da;
        border-radius: 6px;
        font-size: 16px;
        color: red;
        text-align: center;
    }
    .success-message {
        padding: 12px;
        background-color: #d1e7dd;
        border-radius: 6px;
        font-size: 16px;
        color: #0f5132;
        text-align: center;
    }
    .error-message,
    .success-message {
        transition: opacity 0.5s ease;
    }

</style>

<style>
    @media (max-width: 450px) {
        #login-form{
            width: 100vw !important;
        }
        #login-form form{
            padding: 0 15px !important;
        }
    }
</style>

<style>/* Animista*/
.fade-in {
	-webkit-animation: fade-in .5s cubic-bezier(0.390, 0.575, 0.565, 1.000) both;
	        animation: fade-in .5s cubic-bezier(0.390, 0.575, 0.565, 1.000) both;
}

@-webkit-keyframes fade-in {
0% {
    opacity: 0;
}
100% {
    opacity: 1;
}
}
@keyframes fade-in {
0% {
    opacity: 0;
}
100% {
    opacity: 1;
}
}


</style>

</head>
<body class="background-gradasi-biru-hijau">
    
    <!-- <div class="w-100 position-absolute d-flex justify-content-center text-white" style="bottom: 100px;">
        <h6>Mendukung untuk pembuatan:</h6>
        <div class="wrapper" style="color: rgba(255, 255, 255, .8);">
            <div class="item item1"><h4>BA Kerusakan Aset</h4></div>
            <div class="item item2"><h4>BA Pengembalian Aset</h4></div>
            <div class="item item3"><h4>BA Serah Terima Aset</h4></div>
            <div class="item item4"><h4>BA Peminjaman</h4></div>
            <div class="item item5"><h4>BA Mutasi</h4></div>
        </div>
    </div> -->

    <div class="container d-flex flex-column justify-content-center align-items-center"  style="height: 100vh;">

        <div id="login-form" class="form-switchs card bg-white flex-column justify-content-center align-items-center shadow position-relative <?= isActiveForm('login', $formAktif); ?>" style="width: 500px; height: 600px;">
            <div class="d-flex flex-column align-items-center">
                <img src="assets/img/logo.png" style="width: 100px;" alt="" srcset="">
                <h2>SIBARA</h2>
                <?= showError($errors['login']); ?>
                <?= showSuccess($success['registrasi']); ?>
            </div>
            
            <form action="proses_login_registrasi.php" method="post" class="w-100 p-5 pt-2">
            <div class="mb-3">
                <label for="username_l" class="form-label">Username</label>
                <input type="text" name="username" autocomplete="on" class="form-control" id="username_l" placeholder="Username" required>
            </div>
            <div class="mb-3">
                <label for="passwords_l" class="form-label">Password</label>
                <input type="password" name="passwords" autocomplete="on" class="form-control" id="passwords_l" placeholder="Password" required>
            </div>
            <button type="submit" name="login" class="btn btn-primary w-100 fw-bold fs-5 " style="height: 50px;">Login</button>
                <!-- <h6 class="d-flex justify-content-center mt-3">Belum punya akun?<a href="#" onclick="showForm('regist-form')" class="text-decoration-none ps-1">Registrasi</a></h6> -->
            </form>
            <div class="w-100 position-absolute bottom-0 pe-3 text-secondary d-flex justify-content-end" style="height: 30px;">
                <h6>v.1.1.0</h6>
            </div>
        </div>


        <div id="regist-form" class="form-switchs  card bg-white flex-column justify-content-center align-items-center shadow position-relative <?= isActiveForm('registrasi', $formAktif); ?>" style="width: 500px; height: 680px;">
            <div class="d-flex flex-column align-items-center">
                <img src="assets/img/logo.png" style="width: 100px;" alt="" srcset="">
                <h2>SIBARA</h2>
                <?= showError($errors['registrasi']); ?>
            </div>
            <form action="proses_login_registrasi.php" method="post" class="w-100 p-5 pt-2 pb-2">
            <div class="mb-3">
                <label for="pt" class="form-label">Lokasi</label>
                <select name="pt" id="pt" autocomplete="off" class="form-select" required>
                    <option value="">-- Pilih Lokasi --</option>
                    <option value="PT.MSAL (HO)">PT.MSAL (HO)</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="nik" class="form-label">NIK</label>
                <select name="nik" id="nik" autocomplete="off" class="form-select" required>
                    <option value="">-- Pilih NIK --</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="username_r" class="form-label">Username</label>
                <input type="text" autocomplete="off" name="username" class="form-control" id="username_r" placeholder="Username" required>
            </div>
            <div class="mb-3">
                <label for="passwords_r" class="form-label">Password</label>
                <input type="password" autocomplete="off" name="passwords" class="form-control" id="passwords_r" placeholder="Password" required>
            </div>
            <button type="submit" name="registrasi" class="btn btn-primary w-100 fw-bold fs-5 " style="height: 50px;">Registrasi</button>
                <h6 class="d-flex justify-content-center mt-3">Sudah ada akun?<a href="#" onclick="showForm('login-form')" class="text-decoration-none ps-1">Login</a></h6>
            </form>
            <div class="w-100 position-absolute bottom-0 pe-3 text-secondary d-flex justify-content-end" style="height: 30px;">
                <h6>v.1.1.0</h6>
            </div>
        </div>

    </div>

    <script>
        // Menghilangkan alert setelah 5 detik
        setTimeout(() => {
            const alert = document.getElementById('alert-message');
            if (alert) {
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500); // Hapus elemen setelah efek transisi
            }
        }, 3000);
    </script>


    <script>
        document.getElementById('pt').addEventListener('change', function () {
            const pt = this.value;
            const nikSelect = document.getElementById('nik');

            // Reset dropdown
            nikSelect.innerHTML = '<option value="">-- Pilih NIK --</option>';

            if (pt === '') return;

            fetch(`get_nik_by_pt.php?pt=${encodeURIComponent(pt)}`)
            .then(response => response.json())
            .then(data => {
                data.forEach(item => {
                const option = document.createElement('option');
                option.value = item.nik;
                option.textContent = item.label;
                nikSelect.appendChild(option);
                });
            })
            .catch(error => {
                console.error('Gagal mengambil data NIK:', error);
            });
        });
    </script>

    <script>
        function showForm(formId){
            document.querySelectorAll(".form-switchs").forEach(form => {
                form.classList.remove("active");
                form.classList.remove("fade-in");
            });
            document.getElementById(formId).classList.add("active");
            document.getElementById(formId).classList.add("fade-in");
        }
    </script>

    <script src="assets/js/jquery-3.7.1.min.js"></script>
    <!-- Bootstrap 5 -->
    <script src="assets/bootstrap-5.3.6-dist/js/bootstrap.min.js"></script>

    <!-- popperjs Bootstrap 5 -->
    <script src="assets/js/popper.min.js"></script>
</body>
</html>