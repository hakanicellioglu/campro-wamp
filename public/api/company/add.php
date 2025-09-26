<?php
declare(strict_types=1);

session_start();

header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: same-origin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Geçersiz istek yöntemi.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
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
    echo json_encode([
        'status' => 'error',
        'message' => 'CSRF doğrulaması başarısız.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once __DIR__ . '/../../../config.php';

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
    echo json_encode([
        'status' => 'error',
        'message' => 'Form doğrulaması başarısız.',
        'errors'  => $errors,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $stmt = $pdo->prepare(
        'INSERT INTO companies (name, address, phone, email, website, fax) VALUES (:name, :address, :phone, :email, :website, :fax)'
    );
    $stmt->execute([
        ':name'    => $input['name'],
        ':address' => $input['address'] !== '' ? $input['address'] : null,
        ':phone'   => $input['phone'] !== '' ? $input['phone'] : null,
        ':email'   => $input['email'] !== '' ? $input['email'] : null,
        ':website' => $input['website'] !== '' ? $input['website'] : null,
        ':fax'     => $input['fax'] !== '' ? $input['fax'] : null,
    ]);

    $newId = (int) $pdo->lastInsertId();

    http_response_code(303);
    header('Location: ../company.php');

    echo json_encode([
        'status' => 'success',
        'message' => 'Şirket kaydı oluşturuldu.',
        'id'      => $newId,
        'redirect' => '../company.php',
    ], JSON_UNESCAPED_UNICODE);
    exit;
} catch (PDOException $e) {
    error_log('Company add failed: ' . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Şirket kaydı oluşturulamadı.'
    ], JSON_UNESCAPED_UNICODE);
}
