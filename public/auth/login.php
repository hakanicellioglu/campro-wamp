<?php

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
        $stmt = $pdo->prepare('SELECT id, password FROM users WHERE username = :username OR email = :email LIMIT 1');
        $stmt->execute([
            ':username' => $identifier,
            ':email'    => $identifier,
        ]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($password, (string) $user['password'])) {
            $_SESSION['flash_error'] = 'Kullanıcı adı veya şifre hatalı';
            header('Location: login.php');
            exit;
        } else {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) $user['id'];

            $roles       = [];
            $permissions = [];
            try {
                $roleStmt = $pdo->prepare(
                    'SELECT r.name AS role_name, p.name AS permission_key
                    FROM user_roles ur
                    JOIN roles r ON ur.role_id = r.id
                    LEFT JOIN role_permissions rp ON rp.role_id = r.id AND rp.granted = 1
                    LEFT JOIN permissions p ON p.id = rp.permission_id
                    WHERE ur.user_id = :user_id
                    ORDER BY r.id ASC, p.id ASC'
                );
                $roleStmt->execute([
                    ':user_id' => $user['id'],
                ]);
                $roleRows = $roleStmt->fetchAll(PDO::FETCH_ASSOC);

                foreach ($roleRows as $row) {
                    $roleName = trim((string) ($row['role_name'] ?? ''));
                    if ($roleName !== '' && !in_array($roleName, $roles, true)) {
                        $roles[] = $roleName;
                    }

                    $permissionKey = trim((string) ($row['permission_key'] ?? ''));
                    if ($permissionKey !== '' && !in_array($permissionKey, $permissions, true)) {
                        $permissions[] = $permissionKey;
                    }
                }
            } catch (Throwable $e) {
                $roles = [];
                $permissions = [];
            }

            if ($roles === []) {
                $roles[] = 'user';
            }

            $primaryRole = $roles[0];
            $normalizedRoles = array_map(static function ($value): string {
                return strtolower((string) $value);
            }, $roles);

            $_SESSION['roles']       = array_values($roles);
            $_SESSION['role']        = $primaryRole;
            $_SESSION['permissions'] = array_values($permissions);
            $_SESSION['is_admin']    = in_array('admin', $normalizedRoles, true);
            $_SESSION['user_role']   = $primaryRole;

            if ($remember) {
                setcookie('remember_user', (string) $user['id'], time() + (60 * 60 * 24 * 30), '/', '', false, true);
            }

            $_SESSION['flash_success'] = 'Giriş başarılı!';
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <?php require_once __DIR__ . '/../../assets/fonts/monoton.php'; ?>
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --surface: #ffffff;
            --surface-elevated: #ffffff;
            --ink: #1a1a1a;
            --ink-secondary: #6b7280;
            --border: rgba(255, 255, 255, 0.1);
            --border-light: #e5e7eb;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            --radius: 16px;
            --radius-lg: 24px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', Roboto, Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            background-attachment: fixed;
            color: var(--ink);
            min-height: 100vh;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
        }

        /* Animated background elements */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background:
                radial-gradient(circle at 20% 80%, rgba(120, 119, 198, 0.3) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(255, 119, 198, 0.3) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(120, 219, 255, 0.3) 0%, transparent 50%);
            animation: float 20s ease-in-out infinite;
            z-index: -1;
        }

        @keyframes float {

            0%,
            100% {
                transform: translate(0, 0) rotate(0deg);
            }

            33% {
                transform: translate(30px, -30px) rotate(120deg);
            }

            66% {
                transform: translate(-20px, 20px) rotate(240deg);
            }
        }

        .page {
            max-width: 1200px;
            margin: 0 auto;
            padding: 32px 24px;
            min-height: 100vh;
            display: flex;
            align-items: center;
        }

        .shell {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 64px;
            width: 100%;
            align-items: center;
        }

        .hero {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: flex-start;
            gap: 24px;
        }

        .hero .logo {
            font-family: "Monoton", sans-serif;
            font-size: 4.5rem;
            letter-spacing: 0.05em;
            background: linear-gradient(135deg, #ffffff, #f8fafc);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.1));
            animation: logoGlow 3s ease-in-out infinite alternate;
        }

        @keyframes logoGlow {
            0% {
                filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.1));
            }

            100% {
                filter: drop-shadow(0 4px 8px rgba(255, 255, 255, 0.2));
            }
        }

        .hero-text {
            color: rgba(255, 255, 255, 0.9);
            font-size: 1.125rem;
            font-weight: 400;
            line-height: 1.6;
            max-width: 400px;
        }

        .panel {
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-xl);
            max-width: 440px;
            width: 100%;
            padding: 48px 40px;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.8), transparent);
        }

        .card:hover {
            transform: translateY(-8px);
            box-shadow: 0 32px 64px -12px rgba(0, 0, 0, 0.25);
        }

        .headline {
            font-weight: 700;
            font-size: 2rem;
            letter-spacing: -0.025em;
            margin-bottom: 8px;
            background: linear-gradient(135deg, var(--ink), #374151);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .subtitle {
            color: var(--ink-secondary);
            font-size: 1rem;
            margin-bottom: 32px;
            font-weight: 400;
        }

        .alert {
            border: none;
            border-radius: 12px;
            padding: 16px 20px;
            margin-bottom: 24px;
            font-weight: 500;
            animation: slideIn 0.3s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-success {
            background: linear-gradient(135deg, #d1fae5, #a7f3d0);
            color: #065f46;
            border-left: 4px solid #10b981;
        }

        .alert-danger {
            background: linear-gradient(135deg, #fee2e2, #fca5a5);
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }

        .alert-warning {
            background: linear-gradient(135deg, #fef3c7, #fcd34d);
            color: #92400e;
            border-left: 4px solid #f59e0b;
        }

        .form-label {
            font-weight: 600;
            color: var(--ink);
            margin-bottom: 8px;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .form-control {
            border: 2px solid var(--border-light);
            border-radius: 12px;
            padding: 16px 20px;
            font-size: 1rem;
            transition: all 0.2s ease;
            background: var(--surface);
            font-weight: 400;
        }

        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            transform: translateY(-2px);
        }

        .form-control.is-invalid {
            border-color: #ef4444;
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
        }

        .invalid-feedback {
            font-size: 0.875rem;
            font-weight: 500;
            margin-top: 8px;
        }

        .form-check {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .form-check-input {
            width: 20px;
            height: 20px;
            border: 2px solid var(--border-light);
            border-radius: 6px;
            margin: 0;
            transition: all 0.2s ease;
        }

        .form-check-input:checked {
            background: var(--primary-gradient);
            border-color: #667eea;
        }

        .form-check-label {
            font-weight: 500;
            color: var(--ink-secondary);
        }

        .btn-primary {
            background: var(--primary-gradient);
            border: none;
            border-radius: 12px;
            padding: 16px 32px;
            font-weight: 600;
            font-size: 1rem;
            color: white;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .btn-primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }

        .btn-primary:hover::before {
            left: 100%;
        }

        .btn-primary:focus {
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.3);
        }

        .login-link {
            text-align: center;
            margin-top: 32px;
            color: var(--ink-secondary);
            font-weight: 400;
        }

        .login-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .login-link a:hover {
            color: #5a67d8;
            text-decoration: underline;
        }

        /* Mobile responsiveness */
        @media (max-width: 992px) {
            .shell {
                grid-template-columns: 1fr;
                gap: 48px;
                text-align: center;
            }

            .hero {
                align-items: center;
                text-align: center;
            }

            .hero .logo {
                font-size: 3.5rem;
            }

            .card {
                padding: 40px 32px;
            }
        }

        @media (max-width: 768px) {
            .page {
                padding: 24px 16px;
            }

            .hero .logo {
                font-size: 3rem;
            }

            .card {
                padding: 32px 24px;
            }

            .headline {
                font-size: 1.75rem;
            }
        }
    </style>
</head>

<body>
    <?php
    // Flash mesajlar için toast bileşeni (partials/flash.php dahil edildi)
    require_once __DIR__ . '/../../partials/flash.php';
    ?>
    <main class="page">
        <section class="shell">
            <div class="hero">
                <span class="logo">NEXA</span>
                <p class="hero-text">Modern ve güçlü platform deneyimini keşfedin. Nexa ile dijital dünyanızı yeniden şekillendirin.</p>
            </div>
            <div class="panel">
                <div class="card">
                    <h1 class="headline">Tekrar Hoş Geldiniz</h1>
                    <p class="subtitle">Hesabınıza giriş yapın.</p>

                    <?php if (!empty($errors['csrf'])) : ?>
                        <div class="alert alert-warning"><?= htmlspecialchars($errors['csrf'], ENT_QUOTES, 'UTF-8') ?></div>
                    <?php endif; ?>

                    <form method="post" novalidate>
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

                        <div class="mb-4">
                            <label class="form-label">Kullanıcı adı veya e-posta</label>
                            <input type="text" name="identifier" class="form-control <?= isset($errors['identifier']) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars($identifier, ENT_QUOTES, 'UTF-8') ?>" required>
                            <?php if (isset($errors['identifier'])) : ?>
                                <div class="invalid-feedback"><?= htmlspecialchars($errors['identifier'], ENT_QUOTES, 'UTF-8') ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Şifre</label>
                            <input type="password" name="password" class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>" required>
                            <?php if (isset($errors['password'])) : ?>
                                <div class="invalid-feedback"><?= htmlspecialchars($errors['password'], ENT_QUOTES, 'UTF-8') ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="form-check mb-4">
                            <input class="form-check-input" type="checkbox" name="remember" id="remember" <?= $remember ? 'checked' : '' ?>>
                            <label class="form-check-label" for="remember">Beni hatırla</label>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Giriş Yap</button>
                        </div>
                    </form>

                    <p class="login-link">Hesabınız yok mu? <a href="register.php">Kayıt Ol</a></p>
                </div>
            </div>
        </section>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>