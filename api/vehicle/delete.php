<?php
/**
 * api/vehicle/delete.php
 *
 * Bir araç kaydını siler.
 */
declare(strict_types=1);

session_start();

$redirectTarget = '/public/vehicle.php';

$redirect = static function (string $location): void {
    header('Location: ' . $location);
    exit;
};

$redirectWithMessage = static function (string $location, string $type, string $message) use ($redirect): void {
    $flashKey = 'flash_' . $type;
    $_SESSION[$flashKey] = $message;
    $redirect($location);
};

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    $redirect($redirectTarget);
}

if (!isset($_SESSION['user_id'])) {
    $redirectWithMessage($redirectTarget, 'error', 'Oturum doğrulaması başarısız. Lütfen yeniden giriş yapın.');
}

if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], (string)($_POST['csrf_token'] ?? ''))) {
    $redirectWithMessage($redirectTarget, 'error', 'Güvenlik doğrulaması başarısız oldu.');
}

require_once __DIR__ . '/../../config.php';

$vehicleId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$vehicleId) {
    $redirectWithMessage($redirectTarget, 'error', 'Geçersiz araç kaydı.');
}

try {
    $stmt = $pdo->prepare('DELETE FROM vehicles WHERE id = :id');
    $stmt->execute(['id' => $vehicleId]);

    if ($stmt->rowCount() === 0) {
        $redirectWithMessage($redirectTarget, 'warning', 'Araç kaydı bulunamadı.');
    }

    $redirectWithMessage($redirectTarget, 'success', 'Araç kaydı silindi.');
} catch (PDOException $e) {
    error_log('Vehicle delete failed: ' . $e->getMessage());

    $message = 'Araç silme işlemi sırasında bir hata oluştu.';
    if ((int)$e->getCode() === 23000) {
        $message = 'Araç bağlı kayıtlar nedeniyle silinemedi.';
    }

    $redirectWithMessage($redirectTarget, 'error', $message);
}
