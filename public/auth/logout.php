<?php

declare(strict_types=1);

session_start();

header('X-Frame-Options: DENY');

// Claude Code secure logout implementation
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

/**
 * Checks whether a redirect target is safe (same-origin, relative URL).
 */
function resolveRedirectTarget(?string $candidate): string
{
    $default = 'login.php';

    if ($candidate === null) {
        return $default;
    }

    $trimmed = trim($candidate);
    if ($trimmed === '' || str_starts_with($trimmed, '//')) {
        return $default;
    }

    $parsed = parse_url($trimmed);
    if ($parsed === false) {
        return $default;
    }

    if (isset($parsed['scheme']) || isset($parsed['host'])) {
        return $default;
    }

    if (isset($parsed['path']) && str_contains($parsed['path'], '..')) {
        return $default;
    }

    return $trimmed;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ensureCsrfToken();

    http_response_code(303);

    $fallbackTarget = isset($_SESSION['user_id']) ? '../dashboard.php' : 'login.php';
    header('Location: ' . $fallbackTarget);
    exit;
}

$csrfTokenFromPost = $_POST['csrf_token'] ?? '';
if (!verifyCsrfToken((string) $csrfTokenFromPost)) {
    http_response_code(400);
    exit('Geçersiz CSRF doğrulaması.');
}

unset($_SESSION['user_id'], $_SESSION['username'], $_SESSION['remember_token']);

session_unset();
session_destroy();

if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/', '', false, true);
}

if (isset($_COOKIE['remember_user'])) {
    setcookie('remember_user', '', time() - 3600, '/', '', false, true);
}

session_start();
session_regenerate_id(true);

$_SESSION['flash_success'] = 'Güvenli çıkış yapıldı.';

$redirectTarget = resolveRedirectTarget($_POST['redirect'] ?? ($_GET['redirect'] ?? null));

header('Location: ' . $redirectTarget, true, 303);
?>
<!doctype html>
<html lang="tr">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Çıkış Yapılıyor…</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light d-flex align-items-center justify-content-center min-vh-100">
    <main class="text-center">
        <div class="spinner-border text-primary mb-3" role="status" aria-hidden="true"></div>
        <p class="lead mb-0">Çıkış yapılıyor…</p>
        <noscript>
            <p class="mt-3">
                Tarayıcınız yönlendirmediyse
                <a href="login.php" class="link-primary">giriş sayfasına dönmek için tıklayın</a>.
            </p>
        </noscript>
    </main>
    <?php require_once __DIR__ . '/../component/footer.php'; ?>
</body>

</html>