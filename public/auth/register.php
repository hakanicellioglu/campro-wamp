<?php
// public/auth/register.php
declare(strict_types=1);
session_start();

// Eğer girişliyse dashboard'a
if (!empty($_SESSION['user_id'])) {
    header('Location: ../dashboard.php');
    exit;
}

// PDO'yu içeri al
require_once __DIR__ . '/../../config.php';

// CSRF helper
function ensureCsrfToken(): void
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}
function verifyCsrfToken(string $tokenFromPost): bool
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $tokenFromPost);
}

$errors = [];
$old    = ['firstname' => '', 'surname' => '', 'email' => '', 'username' => ''];

ensureCsrfToken();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors['csrf'] = 'Oturum doğrulaması başarısız. Lütfen sayfayı yenileyin.';
    }

    // Form verileri
    $firstname = trim($_POST['firstname'] ?? '');
    $surname   = trim($_POST['surname'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $username  = trim($_POST['username'] ?? '');
    $password  = $_POST['password'] ?? '';
    $confirm   = $_POST['confirm'] ?? '';
    $terms     = isset($_POST['terms']);

    $old = compact('firstname', 'surname', 'email', 'username');

    // Doğrulamalar
    if ($firstname === '' || mb_strlen($firstname) < 2) {
        $errors['firstname'] = 'Ad en az 2 karakter olmalı.';
    }
    if ($surname === '' || mb_strlen($surname) < 2) {
        $errors['surname'] = 'Soyad en az 2 karakter olmalı.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Geçerli bir e-posta girin.';
    }
    if ($username === '' || !preg_match('/^[a-zA-Z0-9._-]{3,20}$/', $username)) {
        $errors['username'] = 'Kullanıcı adı 3-20 karakter, harf/rakam/._- içerebilir.';
    }
    if (strlen($password) < 8) {
        $errors['password'] = 'Şifre en az 8 karakter olmalı.';
    }
    if ($password !== $confirm) {
        $errors['confirm'] = 'Şifreler uyuşmuyor.';
    }
    if (!$terms) {
        $errors['terms'] = 'Devam etmek için şartları kabul etmelisiniz.';
    }

    // E-posta / kullanıcı adı benzersiz mi?
    if (!$errors) {
        $stmt = $pdo->prepare('SELECT 1 FROM users WHERE email = :email OR username = :username LIMIT 1');
        $stmt->execute([':email' => $email, ':username' => $username]);
        if ($stmt->fetch()) {
            $chk = $pdo->prepare('SELECT email, username FROM users WHERE email = :email OR username = :username LIMIT 1');
            $chk->execute([':email' => $email, ':username' => $username]);
            $row = $chk->fetch(PDO::FETCH_ASSOC) ?: [];
            if (!empty($row['email']) && strcasecmp($row['email'], $email) === 0) {
                $errors['email'] = 'Bu e-posta zaten kayıtlı.';
            }
            if (!empty($row['username']) && strcasecmp($row['username'], $username) === 0) {
                $errors['username'] = 'Bu kullanıcı adı zaten alınmış.';
            }
        }
    }

    // Kayıt
    if (!$errors) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $ins = $pdo->prepare('INSERT INTO users (firstname, surname, email, username, password) 
                              VALUES (:firstname, :surname, :email, :username, :password)');
        try {
            $ins->execute([
                ':firstname' => $firstname,
                ':surname'   => $surname,
                ':email'     => $email,
                ':username'  => $username,
                ':password'  => $hash,
            ]);
            $_SESSION['flash_success'] = 'Kayıt tamamlandı. Şimdi giriş yapabilirsiniz.';
            header('Location: login.php');
            exit;
        } catch (PDOException $e) {
            if (isset($e->errorInfo[1]) && (int)$e->errorInfo[1] === 1062) {
                $errors['global'] = 'E-posta veya kullanıcı adı zaten kayıtlı.';
            } else {
                $errors['global'] = 'Bir hata oluştu. Lütfen tekrar deneyin.';
            }
        }
    }
}
?>
<!doctype html>
<html lang="tr">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Nexa — Kayıt Ol</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <?php require_once __DIR__ . '/../../assets/fonts/monoton.php'; ?>
    <style>
        :root {
            --ink: #0f1419;
            --muted: #536471;
            --line: #e6e6e6;
            --radius: 14px;
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
            background: #fff;
            color: var(--ink);
        }

        .page {
            max-width: 960px;
            margin: 0 auto;
            padding: 24px;
        }

        .shell {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            min-height: calc(100vh - 48px);
            align-items: center;
        }

        .hero {
            display: grid;
            place-items: center;
        }

        .hero .logo {
            font-family: "Monoton", sans-serif;
            font-size: 3rem;
            letter-spacing: 0.05em;
        }

        .panel {
            max-width: 520px;
            margin-left: auto;
        }

        .headline {
            font-weight: 800;
            letter-spacing: -.02em;
        }

        .muted {
            color: var(--muted);
        }

        .card {
            border: 1px solid var(--line);
            border-radius: var(--radius);
        }

        .form-control,
        .form-check-input {
            border-radius: var(--radius);
        }

        .btn-dark {
            border-radius: var(--radius);
            padding: .75rem 1rem;
            font-weight: 600;
        }

        @media (max-width:992px) {
            .shell {
                grid-template-columns: 1fr;
            }

            .panel {
                margin: 0 auto;
            }

            .hero {
                order: -1;
            }
        }
    </style>
</head>

<body>
    <main class="page">
        <section class="shell">
            <div class="hero"><span class="logo">NEXA</span></div>
            <div class="panel">
                <h1 class="headline mb-2">Hemen katıl.</h1>
                <p class="muted mb-4">Hesabını oluştur, Nexa’yı kullanmaya başla.</p>
                <?php if (!empty($errors['global'])): ?><div class="alert alert-danger"><?= htmlspecialchars($errors['global'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
                <?php if (!empty($errors['csrf'])): ?><div class="alert alert-warning"><?= htmlspecialchars($errors['csrf'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
                <div class="card p-3 p-md-4">
                    <form method="post" novalidate>
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Ad</label>
                                <input name="firstname" type="text" class="form-control <?= isset($errors['firstname']) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars($old['firstname'], ENT_QUOTES, 'UTF-8') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Soyad</label>
                                <input name="surname" type="text" class="form-control <?= isset($errors['surname']) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars($old['surname'], ENT_QUOTES, 'UTF-8') ?>" required>
                            </div>
                        </div>
                        <div class="mt-3">
                            <label class="form-label">E-posta</label>
                            <input name="email" type="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars($old['email'], ENT_QUOTES, 'UTF-8') ?>" required>
                        </div>
                        <div class="mt-3">
                            <label class="form-label">Kullanıcı adı</label>
                            <input name="username" type="text" class="form-control <?= isset($errors['username']) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars($old['username'], ENT_QUOTES, 'UTF-8') ?>" required>
                        </div>
                        <div class="row g-3 mt-1">
                            <div class="col-md-6">
                                <label class="form-label">Şifre</label>
                                <input name="password" type="password" class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>" required minlength="8">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Şifre (tekrar)</label>
                                <input name="confirm" type="password" class="form-control <?= isset($errors['confirm']) ? 'is-invalid' : '' ?>" required minlength="8">
                            </div>
                        </div>
                        <div class="form-check mt-3">
                            <input class="form-check-input <?= isset($errors['terms']) ? 'is-invalid' : '' ?>" type="checkbox" name="terms" id="terms" value="1" <?= isset($_POST['terms']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="terms"><a href="../terms.php">Hizmet Şartları</a> ve <a href="../privacy.php">Gizlilik Politikası</a>'nı okudum, kabul ediyorum.</label>
                        </div>
                        <div class="d-grid mt-4">
                            <button class="btn btn-dark" type="submit">Kayıt Ol</button>
                        </div>
                    </form>
                </div>
                <p class="mt-3 muted">Zaten hesabın var mı? <a href="login.php">Giriş yap</a></p>
            </div>
        </section>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>