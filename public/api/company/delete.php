<?php
declare(strict_types=1);

session_start();

if (!function_exists('setFlashMessage')) {
    function setFlashMessage(string $type, string $message): void
    {
        $map = [
            'success' => 'flash_success',
            'error'   => 'flash_error',
            'warning' => 'flash_warning',
        ];

        if (!isset($map[$type])) {
            return;
        }

        foreach ($map as $sessionKey) {
            if ($sessionKey !== $map[$type]) {
                unset($_SESSION[$sessionKey]);
            }
        }

        $_SESSION[$map[$type]] = $message;
    }
}

header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: same-origin');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    setFlashMessage('error', 'Geçersiz istek yöntemi.');
    echo json_encode([
        'status' => 'error',
        'message' => 'Geçersiz istek yöntemi.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    setFlashMessage('error', 'Oturum açmanız gerekiyor.');
    echo json_encode([
        'status' => 'error',
        'message' => 'Oturum açmanız gerekiyor.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$originHeader = $_SERVER['HTTP_ORIGIN'] ?? null;
if ($originHeader !== null) {
    $originParts = parse_url($originHeader);
    $hostHeader  = (string) ($_SERVER['HTTP_HOST'] ?? '');
    $hostParts   = explode(':', $hostHeader);
    $hostName    = strtolower($hostParts[0] ?? '');
    $originHost  = is_array($originParts) && isset($originParts['host']) ? strtolower((string) $originParts['host']) : '';

    if ($originHost === '' || $hostName === '' || !hash_equals($hostName, $originHost)) {
        http_response_code(403);
        setFlashMessage('error', 'Kaynak doğrulaması başarısız.');
        echo json_encode([
            'status' => 'error',
            'message' => 'Kaynak doğrulaması başarısız.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrfFromPost = (string) ($_POST['csrf_token'] ?? '');
if (!hash_equals($_SESSION['csrf_token'], $csrfFromPost)) {
    http_response_code(400);
    setFlashMessage('error', 'CSRF doğrulaması başarısız.');
    echo json_encode([
        'status' => 'error',
        'message' => 'CSRF doğrulaması başarısız.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$rawId = trim((string) ($_POST['id'] ?? ''));
if ($rawId === '' || !ctype_digit($rawId)) {
    http_response_code(422);
    setFlashMessage('error', 'Geçersiz şirket kimliği.');
    echo json_encode([
        'status' => 'error',
        'message' => 'Geçersiz şirket kimliği.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$companyId = (int) $rawId;

$isPrivileged = false;

$adminFlags = [
    'is_admin',
    'user_is_admin',
    'is_super_admin',
    'isSuperAdmin',
];

foreach ($adminFlags as $flag) {
    if (isset($_SESSION[$flag]) && filter_var($_SESSION[$flag], FILTER_VALIDATE_BOOLEAN)) {
        $isPrivileged = true;
        break;
    }
}

if (!$isPrivileged && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
    $isPrivileged = true;
}

if (!$isPrivileged && isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    $isPrivileged = true;
}

if (!$isPrivileged && isset($_SESSION['permissions']) && is_array($_SESSION['permissions'])) {
    $permissions = array_map('strval', $_SESSION['permissions']);
    if (in_array('company.delete', $permissions, true) || in_array('company:*', $permissions, true) || in_array('*', $permissions, true)) {
        $isPrivileged = true;
    }
}

if (!$isPrivileged) {
    http_response_code(403);
    setFlashMessage('error', 'Bu işlem için yetkiniz yok.');
    echo json_encode([
        'status' => 'error',
        'message' => 'Bu işlem için yetkiniz yok.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once __DIR__ . '/../../../config.php';

try {
    $pdo->beginTransaction();

    $checkStmt = $pdo->prepare('SELECT id FROM companies WHERE id = :id LIMIT 1');
    $checkStmt->execute([':id' => $companyId]);
    if ($checkStmt->fetchColumn() === false) {
        $pdo->rollBack();
        http_response_code(404);
        setFlashMessage('error', 'Şirket kaydı bulunamadı.');
        echo json_encode([
            'status' => 'error',
            'message' => 'Şirket kaydı bulunamadı.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $deleteStmt = $pdo->prepare('DELETE FROM companies WHERE id = :id LIMIT 1');
    $deleteStmt->execute([':id' => $companyId]);

    $pdo->commit();

    setFlashMessage('success', 'Şirket kaydı silindi.');

    echo json_encode([
        'status' => 'success',
        'message' => 'Şirket kaydı silindi.'
    ], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('Company delete failed: ' . $e->getMessage());
    setFlashMessage('error', 'Şirket kaydı silinemedi.');

    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Şirket kaydı silinemedi.'
    ], JSON_UNESCAPED_UNICODE);
}
