<?php
session_start();

// Kullanıcı giriş yaptıysa dashboard'a yönlendir
if (!empty($_SESSION['user_id'])) {
    header("Location: public/dashboard.php");
    exit;
}
?>
<!doctype html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nexa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Monoton&display=swap" rel="stylesheet">
    <style>
        body {
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            font-family: 'Inter', sans-serif;
        }

        .row.w-100 {
            max-width: 800px;
            background: #f8f9fa;
            margin: 0 auto;
        }

        .left-box {
            background: #f8f9fa;
            color: #212529;
            padding: 40px 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
        }

        .left-box .logo {
            font-family: "Monoton", sans-serif;
            font-size: 4rem;
            letter-spacing: 5px;
            color: #212529;
        }

        .right-box {
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: #f8f9fa;
            /* Sağ kutu da arka plana karışsın */
        }

        .right-box h5 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: .5rem;
            color: #212529;
        }

        .right-box p.text-muted {
            margin-bottom: 1.5rem;
        }

        .btn-custom {
            border-radius: 50px;
            font-weight: 600;
            font-size: .875rem;
            padding: 12px;
        }

        .btn-dark {
            background-color: #212529;
            border: none;
        }

        .btn-outline-dark {
            border-width: 2px;
        }

        .terms {
            font-size: 0.8rem;
            margin-top: 20px;
            color: #6c757d;
        }

        .terms a {
            color: #495057;
            text-decoration: underline;
        }
    </style>
</head>

<body>

    <div class="row w-100">
        <!-- Sol kutu -->
        <div class="col-12 col-md-5 left-box text-center">
            <span class="logo">NEXA</span>
        </div>

        <!-- Sağ kutu -->
        <div class="col-12 col-md-7 right-box">
            <p class="text-muted fs-3">Cam dünyasında profesyonel çözümler</p>

            <div class="d-grid gap-3 mb-3">
                <a href="public/auth/login.php" class="btn btn-dark btn-lg btn-custom">Oturum Aç</a>
                <a href="public/auth/register.php" class="btn btn-outline-dark btn-lg btn-custom">Kayıt Ol</a>
            </div>

            <p class="terms">
                Kaydolduğunuzda, Çerez Kullanımı da dahil olmak üzere
                <a href="terms.php">Hizmet Şartları</a> ve
                <a href="privacy.php">Gizlilik Politikası</a>'nı kabul etmiş olursunuz.
            </p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>