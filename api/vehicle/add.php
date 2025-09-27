<?php
/**
 * api/vehicle/add.php
 *
 * Yeni araç kaydı oluşturur.
 */
declare(strict_types=1);

session_start();

$redirectTarget = '/public/vehicle.php';

$redirect = static function (string $location) {
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
    $redirectWithMessage($redirectTarget, 'error', 'Oturumunuz sona ermiş olabilir. Lütfen yeniden giriş yapın.');
}

if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], (string)($_POST['csrf_token'] ?? ''))) {
    $redirectWithMessage($redirectTarget, 'error', 'Güvenlik doğrulaması başarısız oldu.');
}

require_once __DIR__ . '/../../config.php';

$plateNumber = strtoupper(trim((string)($_POST['plate_number'] ?? '')));
$type = trim((string)($_POST['type'] ?? ''));
$brand = trim((string)($_POST['brand'] ?? ''));
$model = trim((string)($_POST['model'] ?? ''));
$productionYear = filter_input(INPUT_POST, 'production_year', FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1900, 'max_range' => (int)date('Y') + 1],
]);
$capacityWeight = filter_input(INPUT_POST, 'capacity_weight', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
$capacityVolume = filter_input(INPUT_POST, 'capacity_volume', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
$status = strtolower(trim((string)($_POST['status'] ?? 'active')));
$lastServiceAt = trim((string)($_POST['last_service_at'] ?? ''));
$nextServiceAt = trim((string)($_POST['next_service_at'] ?? ''));
$inspectionExpiry = trim((string)($_POST['inspection_expiry'] ?? ''));
$insuranceExpiry = trim((string)($_POST['insurance_expiry'] ?? ''));
$notes = trim((string)($_POST['notes'] ?? ''));

if ($plateNumber === '' || $type === '') {
    $redirectWithMessage($redirectTarget, 'error', 'Araç plaka numarası ve türü zorunludur.');
}

$allowedStatuses = ['active', 'maintenance', 'passive', 'retired'];
if (!in_array($status, $allowedStatuses, true)) {
    $status = 'active';
}

$normalizeDecimal = static function ($value): ?string {
    if ($value === null || $value === '') {
        return null;
    }
    $normalized = str_replace(',', '.', (string)$value);
    if (!is_numeric($normalized)) {
        return null;
    }
    return number_format((float)$normalized, 2, '.', '');
};

$capacityWeight = $normalizeDecimal($capacityWeight);
$capacityVolume = $normalizeDecimal($capacityVolume);

$validateDate = static function (string $date): ?string {
    if ($date === '') {
        return null;
    }
    try {
        $dt = new DateTimeImmutable($date);
        return $dt->format('Y-m-d');
    } catch (Throwable $e) {
        return null;
    }
};

$lastServiceAt = $validateDate($lastServiceAt);
$nextServiceAt = $validateDate($nextServiceAt);
$inspectionExpiry = $validateDate($inspectionExpiry);
$insuranceExpiry = $validateDate($insuranceExpiry);

if ($productionYear === false) {
    $productionYear = null;
}

try {
    $stmt = $pdo->prepare(
        'INSERT INTO vehicles (
            plate_number, type, brand, model, production_year, capacity_weight, capacity_volume,
            status, last_service_at, next_service_at, inspection_expiry, insurance_expiry, notes
        ) VALUES (
            :plate_number, :type, :brand, :model, :production_year, :capacity_weight, :capacity_volume,
            :status, :last_service_at, :next_service_at, :inspection_expiry, :insurance_expiry, :notes
        )'
    );

    $stmt->execute([
        'plate_number' => $plateNumber,
        'type' => $type,
        'brand' => $brand !== '' ? $brand : null,
        'model' => $model !== '' ? $model : null,
        'production_year' => $productionYear,
        'capacity_weight' => $capacityWeight,
        'capacity_volume' => $capacityVolume,
        'status' => $status,
        'last_service_at' => $lastServiceAt,
        'next_service_at' => $nextServiceAt,
        'inspection_expiry' => $inspectionExpiry,
        'insurance_expiry' => $insuranceExpiry,
        'notes' => $notes !== '' ? $notes : null,
    ]);

    $redirectWithMessage($redirectTarget, 'success', 'Araç kaydı başarıyla oluşturuldu.');
} catch (PDOException $e) {
    error_log('Vehicle add failed: ' . $e->getMessage());

    $message = 'Araç kaydı oluşturulurken bir hata oluştu.';
    if ((int)$e->getCode() === 23000) {
        $message = 'Bu plaka numarasıyla kayıtlı bir araç zaten mevcut.';
    }

    $redirectWithMessage($redirectTarget, 'error', $message);
}
