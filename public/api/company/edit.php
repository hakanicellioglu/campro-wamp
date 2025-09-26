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

$id = (string) ($_POST['id'] ?? '');
if ($id === '' || !ctype_digit($id)) {
    http_response_code(422);
    setFlashMessage('error', 'Geçersiz şirket kimliği.');
    echo json_encode([
        'status' => 'error',
        'message' => 'Geçersiz şirket kimliği.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$companyId = (int) $id;

require_once __DIR__ . '/../../../config.php';

try {
    $checkStmt = $pdo->prepare('SELECT id FROM companies WHERE id = :id LIMIT 1');
    $checkStmt->execute([':id' => $companyId]);
    if ($checkStmt->fetchColumn() === false) {
        http_response_code(404);
        setFlashMessage('error', 'Şirket kaydı bulunamadı.');
        echo json_encode([
            'status' => 'error',
            'message' => 'Şirket kaydı bulunamadı.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
} catch (PDOException $e) {
    error_log('Company edit lookup failed: ' . $e->getMessage());
    http_response_code(500);
    setFlashMessage('error', 'Şirket kaydı doğrulanamadı.');
    echo json_encode([
        'status' => 'error',
        'message' => 'Şirket kaydı doğrulanamadı.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$input = [
    'name'    => trim((string) ($_POST['name'] ?? '')),
    'address' => trim((string) ($_POST['address'] ?? '')),
    'phone'   => trim((string) ($_POST['phone'] ?? '')),
    'email'   => trim((string) ($_POST['email'] ?? '')),
    'website' => trim((string) ($_POST['website'] ?? '')),
    'fax'     => trim((string) ($_POST['fax'] ?? '')),
];

$errors = [];

if ($input['name'] === '') {
    $errors['name'] = 'Şirket adı zorunludur.';
} elseif (mb_strlen($input['name']) > 150) {
    $errors['name'] = 'Şirket adı 150 karakteri aşamaz.';
}

if ($input['email'] !== '' && !filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'E-posta adresi geçerli değil.';
}

if ($input['website'] !== '') {
    $url = filter_var($input['website'], FILTER_VALIDATE_URL);
    if ($url === false || !preg_match('/^https?:\/\//i', $input['website'])) {
        $errors['website'] = 'Web sitesi adresi http veya https ile başlamalıdır.';
    }
}

if (mb_strlen($input['phone']) > 30) {
    $errors['phone'] = 'Telefon 30 karakteri aşamaz.';
}

if (mb_strlen($input['fax']) > 30) {
    $errors['fax'] = 'Fax 30 karakteri aşamaz.';
}

if (!empty($input['address']) && mb_strlen($input['address']) > 5000) {
    $errors['address'] = 'Adres çok uzun.';
}

if ($errors !== []) {
    http_response_code(422);
    setFlashMessage('error', 'Form doğrulaması başarısız.');
    echo json_encode([
        'status' => 'error',
        'message' => 'Form doğrulaması başarısız.',
        'errors'  => $errors,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $stmt = $pdo->prepare(
        'UPDATE companies SET name = :name, address = :address, phone = :phone, email = :email, website = :website, fax = :fax WHERE id = :id'
    );
    $stmt->execute([
        ':name'    => $input['name'],
        ':address' => $input['address'] !== '' ? $input['address'] : null,
        ':phone'   => $input['phone'] !== '' ? $input['phone'] : null,
        ':email'   => $input['email'] !== '' ? $input['email'] : null,
        ':website' => $input['website'] !== '' ? $input['website'] : null,
        ':fax'     => $input['fax'] !== '' ? $input['fax'] : null,
        ':id'      => $companyId,
    ]);

    http_response_code(303);
    header('Location: ../company.php');
    setFlashMessage('success', 'Şirket bilgileri güncellendi.');

    echo json_encode([
        'status'   => 'success',
        'message'  => 'Şirket bilgileri güncellendi.',
        'redirect' => '../company.php',
    ], JSON_UNESCAPED_UNICODE);
    exit;
} catch (PDOException $e) {
    error_log('Company update failed: ' . $e->getMessage());
    setFlashMessage('error', 'Şirket bilgileri güncellenemedi.');

    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Şirket bilgileri güncellenemedi.'
    ], JSON_UNESCAPED_UNICODE);
}
