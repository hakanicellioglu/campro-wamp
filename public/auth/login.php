<?php
// public/auth/login.php
declare(strict_types=1);

session_start();

if (!empty($_SESSION['user_id'])) {
    header('Location: ../dashboard.php');
    exit;
}

require_once __DIR__ . '/../../config.php';

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

ensureCsrfToken();

$errors     = [];
$identifier = '';
$remember   = false;
$flash      = $_SESSION['flash_success'] ?? null;
unset($_SESSION['flash_success']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors['csrf'] = 'Oturum doğrulaması başarısız. Lütfen sayfayı yenileyin.';
    }

    $identifier = trim($_POST['identifier'] ?? '');
    $password   = $_POST['password'] ?? '';
    $remember   = isset($_POST['remember']);

    if ($identifier === '') {
        $errors['identifier'] = 'Lütfen kullanıcı adı veya e-posta girin.';
    }
    if ($password === '') {
        $errors['password'] = 'Lütfen şifrenizi girin.';
    }

    if (!$errors) {
        $stmt = $pdo->prepare('SELECT id, password FROM users WHERE username = :identifier OR email = :identifier LIMIT 1');
        $stmt->execute([':identifier' => $identifier]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($password, (string) $user['password'])) {
            $errors['global'] = 'Kullanıcı adı/e-posta veya şifre hatalı.';
        } else {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) $user['id'];

            if ($remember) {
                setcookie('remember_user', (string) $user['id'], time() + (60 * 60 * 24 * 30), '/', '', false, true);
            }

            header('Location: ../dashboard.php');
            exit;
        }
    }
}
?>
<!doctype html>
<html lang="tr">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Nexa — Giriş Yap</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <?php require_once __DIR__ . '/../../assets/fonts/monoton.php'; ?>
    <style>
        :root {
            --ink: #0f1419;
            --muted: #536471;
            --line: #e6e6e6;
            --radius: 14px;
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', Roboto, Arial, sans-serif;
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

        .card {
            border: 1px solid var(--line);
            border-radius: var(--radius);
            max-width: 400px;
            margin: 0 auto;
        }

        .headline {
            font-weight: 800;
            letter-spacing: -.02em;
        }

        .muted {
            color: var(--muted);
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

        @media (max-width: 992px) {
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
                <h1 class="headline mb-2">Tekrar Hoş Geldiniz</h1>
                <p class="muted mb-4">Hesabınıza giriş yapın.</p>
                <?php if (!empty($flash)) : ?>
                    <div class="alert alert-success"><?= htmlspecialchars($flash, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>
                <?php if (!empty($errors['global'])) : ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($errors['global'], ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>
                <?php if (!empty($errors['csrf'])) : ?>
                    <div class="alert alert-warning"><?= htmlspecialchars($errors['csrf'], ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>
                <div class="card p-3 p-md-4">
                    <form method="post" novalidate>
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                        <div class="mb-3">
                            <label class="form-label">Kullanıcı adı veya e-posta</label>
                            <input type="text" name="identifier" class="form-control <?= isset($errors['identifier']) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars($identifier, ENT_QUOTES, 'UTF-8') ?>" required>
                            <?php if (isset($errors['identifier'])) : ?>
                                <div class="invalid-feedback"><?= htmlspecialchars($errors['identifier'], ENT_QUOTES, 'UTF-8') ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Şifre</label>
                            <input type="password" name="password" class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>" required>
                            <?php if (isset($errors['password'])) : ?>
                                <div class="invalid-feedback"><?= htmlspecialchars($errors['password'], ENT_QUOTES, 'UTF-8') ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="remember" id="remember" <?= $remember ? 'checked' : '' ?>>
                            <label class="form-check-label" for="remember">Beni hatırla</label>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-dark">Giriş Yap</button>
                        </div>
                    </form>
                </div>
                <p class="mt-3 muted">Hesabınız yok mu? <a href="register.php">Kayıt Ol</a></p>
            </div>
        </section>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
