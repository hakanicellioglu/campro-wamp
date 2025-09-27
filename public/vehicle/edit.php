<?php
declare(strict_types=1);

session_start();

if (empty($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

require_once __DIR__ . '/../../config.php';

$statuses = ['active', 'maintenance', 'passive', 'retired'];
$errors = [];
$vehicleId = isset($_GET['id']) ? (int) $_GET['id'] : (int) ($_POST['id'] ?? 0);

if ($vehicleId <= 0) {
    http_response_code(400);
    echo 'Geçersiz araç ID bilgisi.';
    exit;
}

function normalizeDate(?string $value): ?string
{
    if ($value === null) {
        return null;
    }

    $trimmed = trim($value);
    if ($trimmed === '') {
        return null;
    }

    $date = DateTime::createFromFormat('Y-m-d', $trimmed);
    return $date instanceof DateTime ? $date->format('Y-m-d') : null;
}

try {
    $stmt = $pdo->prepare('SELECT * FROM vehicles WHERE id = :id');
    $stmt->execute([':id' => $vehicleId]);
    $vehicle = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $exception) {
    error_log('Vehicle fetch failed: ' . $exception->getMessage());
    $vehicle = false;
}

if ($vehicle === false) {
    http_response_code(404);
    echo 'Araç kaydı bulunamadı.';
    exit;
}

$values = [
    'plate_number'      => (string) ($vehicle['plate_number'] ?? ''),
    'type'              => (string) ($vehicle['type'] ?? ''),
    'brand'             => (string) ($vehicle['brand'] ?? ''),
    'model'             => (string) ($vehicle['model'] ?? ''),
    'production_year'   => $vehicle['production_year'] !== null ? (string) $vehicle['production_year'] : '',
    'capacity_weight'   => $vehicle['capacity_weight'] !== null ? (string) $vehicle['capacity_weight'] : '',
    'capacity_volume'   => $vehicle['capacity_volume'] !== null ? (string) $vehicle['capacity_volume'] : '',
    'status'            => (string) ($vehicle['status'] ?? 'active'),
    'last_service_at'   => $vehicle['last_service_at'] ?? '',
    'next_service_at'   => $vehicle['next_service_at'] ?? '',
    'inspection_expiry' => $vehicle['inspection_expiry'] ?? '',
    'insurance_expiry'  => $vehicle['insurance_expiry'] ?? '',
    'notes'             => (string) ($vehicle['notes'] ?? ''),
];

$validationMessages = [
    'required_plate_number' => 'Plaka numarası zorunludur.',
    'required_type'         => 'Araç tipi zorunludur.',
    'invalid_status'        => 'Geçersiz durum seçimi.',
    'invalid_year'          => 'Üretim yılı geçerli bir sayı olmalıdır.',
    'invalid_weight'        => 'Taşıma kapasitesi (kg) sayısal olmalıdır.',
    'invalid_volume'        => 'Hacim kapasitesi (m³) sayısal olmalıdır.',
    'invalid_date'          => 'Tarih değerleri YYYY-AA-GG formatında olmalıdır.',
    'update_failed'         => 'Araç güncellenirken bir hata oluştu. Lütfen tekrar deneyin.',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (array_keys($values) as $field) {
        $values[$field] = isset($_POST[$field]) ? trim((string) $_POST[$field]) : '';
    }

    if ($values['plate_number'] === '') {
        $errors['plate_number'] = $validationMessages['required_plate_number'];
    }

    if ($values['type'] === '') {
        $errors['type'] = $validationMessages['required_type'];
    }

    if (!in_array($values['status'], $statuses, true)) {
        $errors['status'] = $validationMessages['invalid_status'];
    }

    $productionYear = null;
    if ($values['production_year'] !== '') {
        if (ctype_digit($values['production_year'])) {
            $productionYear = (int) $values['production_year'];
        } else {
            $errors['production_year'] = $validationMessages['invalid_year'];
        }
    }

    $capacityWeight = null;
    if ($values['capacity_weight'] !== '') {
        if (is_numeric($values['capacity_weight'])) {
            $capacityWeight = (float) $values['capacity_weight'];
        } else {
            $errors['capacity_weight'] = $validationMessages['invalid_weight'];
        }
    }

    $capacityVolume = null;
    if ($values['capacity_volume'] !== '') {
        if (is_numeric($values['capacity_volume'])) {
            $capacityVolume = (float) $values['capacity_volume'];
        } else {
            $errors['capacity_volume'] = $validationMessages['invalid_volume'];
        }
    }

    $dates = [
        'last_service_at',
        'next_service_at',
        'inspection_expiry',
        'insurance_expiry',
    ];

    $normalizedDates = [];
    foreach ($dates as $dateField) {
        $normalized = normalizeDate($values[$dateField]);
        if ($values[$dateField] !== '' && $normalized === null) {
            $errors[$dateField] = $validationMessages['invalid_date'];
        }
        $normalizedDates[$dateField] = $normalized;
    }

    if ($errors === []) {
        try {
            $stmt = $pdo->prepare(
                'UPDATE vehicles SET
                    plate_number = :plate_number,
                    type = :type,
                    brand = :brand,
                    model = :model,
                    production_year = :production_year,
                    capacity_weight = :capacity_weight,
                    capacity_volume = :capacity_volume,
                    status = :status,
                    last_service_at = :last_service_at,
                    next_service_at = :next_service_at,
                    inspection_expiry = :inspection_expiry,
                    insurance_expiry = :insurance_expiry,
                    notes = :notes
                WHERE id = :id'
            );

            $stmt->execute([
                ':plate_number'      => $values['plate_number'],
                ':type'              => $values['type'],
                ':brand'             => $values['brand'] !== '' ? $values['brand'] : null,
                ':model'             => $values['model'] !== '' ? $values['model'] : null,
                ':production_year'   => $productionYear,
                ':capacity_weight'   => $capacityWeight,
                ':capacity_volume'   => $capacityVolume,
                ':status'            => $values['status'],
                ':last_service_at'   => $normalizedDates['last_service_at'],
                ':next_service_at'   => $normalizedDates['next_service_at'],
                ':inspection_expiry' => $normalizedDates['inspection_expiry'],
                ':insurance_expiry'  => $normalizedDates['insurance_expiry'],
                ':notes'             => $values['notes'] !== '' ? $values['notes'] : null,
                ':id'                => $vehicleId,
            ]);

            $_SESSION['flash_success'] = 'Araç bilgileri güncellendi.';
            header('Location: ../vehicle.php');
            exit;
        } catch (PDOException $exception) {
            error_log('Vehicle update failed: ' . $exception->getMessage());
            $errors['general'] = $validationMessages['update_failed'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Araç Güncelle</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include __DIR__ . '/../../partials/flash.php'; ?>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h1 class="h5 mb-0">Araç Bilgilerini Güncelle</h1>
                    <a class="btn btn-sm btn-light" href="../vehicle.php">Araca Geri Dön</a>
                </div>
                <div class="card-body">
                    <?php if (isset($errors['general'])): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($errors['general'], ENT_QUOTES, 'UTF-8'); ?></div>
                    <?php endif; ?>
                    <form method="post" novalidate>
                        <input type="hidden" name="id" value="<?= htmlspecialchars((string) $vehicleId, ENT_QUOTES, 'UTF-8'); ?>">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="plate_number">Plaka *</label>
                                <input type="text" class="form-control<?= isset($errors['plate_number']) ? ' is-invalid' : ''; ?>" name="plate_number" id="plate_number" value="<?= htmlspecialchars($values['plate_number'], ENT_QUOTES, 'UTF-8'); ?>" required>
                                <?php if (isset($errors['plate_number'])): ?>
                                    <div class="invalid-feedback"><?= htmlspecialchars($errors['plate_number'], ENT_QUOTES, 'UTF-8'); ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="type">Araç Tipi *</label>
                                <input type="text" class="form-control<?= isset($errors['type']) ? ' is-invalid' : ''; ?>" name="type" id="type" value="<?= htmlspecialchars($values['type'], ENT_QUOTES, 'UTF-8'); ?>" required>
                                <?php if (isset($errors['type'])): ?>
                                    <div class="invalid-feedback"><?= htmlspecialchars($errors['type'], ENT_QUOTES, 'UTF-8'); ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="brand">Marka</label>
                                <input type="text" class="form-control" name="brand" id="brand" value="<?= htmlspecialchars($values['brand'], ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="model">Model</label>
                                <input type="text" class="form-control" name="model" id="model" value="<?= htmlspecialchars($values['model'], ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="production_year">Üretim Yılı</label>
                                <input type="number" class="form-control<?= isset($errors['production_year']) ? ' is-invalid' : ''; ?>" name="production_year" id="production_year" value="<?= htmlspecialchars($values['production_year'], ENT_QUOTES, 'UTF-8'); ?>" min="1950" max="2100">
                                <?php if (isset($errors['production_year'])): ?>
                                    <div class="invalid-feedback"><?= htmlspecialchars($errors['production_year'], ENT_QUOTES, 'UTF-8'); ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="capacity_weight">Kapasite (kg)</label>
                                <input type="number" step="0.01" class="form-control<?= isset($errors['capacity_weight']) ? ' is-invalid' : ''; ?>" name="capacity_weight" id="capacity_weight" value="<?= htmlspecialchars($values['capacity_weight'], ENT_QUOTES, 'UTF-8'); ?>">
                                <?php if (isset($errors['capacity_weight'])): ?>
                                    <div class="invalid-feedback"><?= htmlspecialchars($errors['capacity_weight'], ENT_QUOTES, 'UTF-8'); ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="capacity_volume">Hacim (m³)</label>
                                <input type="number" step="0.01" class="form-control<?= isset($errors['capacity_volume']) ? ' is-invalid' : ''; ?>" name="capacity_volume" id="capacity_volume" value="<?= htmlspecialchars($values['capacity_volume'], ENT_QUOTES, 'UTF-8'); ?>">
                                <?php if (isset($errors['capacity_volume'])): ?>
                                    <div class="invalid-feedback"><?= htmlspecialchars($errors['capacity_volume'], ENT_QUOTES, 'UTF-8'); ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="status">Durum *</label>
                                <select class="form-select<?= isset($errors['status']) ? ' is-invalid' : ''; ?>" name="status" id="status" required>
                                    <?php foreach ($statuses as $status): ?>
                                        <option value="<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8'); ?>" <?= $values['status'] === $status ? 'selected' : ''; ?>><?= htmlspecialchars(ucfirst($status), ENT_QUOTES, 'UTF-8'); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (isset($errors['status'])): ?>
                                    <div class="invalid-feedback"><?= htmlspecialchars($errors['status'], ENT_QUOTES, 'UTF-8'); ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="last_service_at">Son Bakım Tarihi</label>
                                <input type="date" class="form-control<?= isset($errors['last_service_at']) ? ' is-invalid' : ''; ?>" name="last_service_at" id="last_service_at" value="<?= htmlspecialchars($values['last_service_at'], ENT_QUOTES, 'UTF-8'); ?>">
                                <?php if (isset($errors['last_service_at'])): ?>
                                    <div class="invalid-feedback"><?= htmlspecialchars($errors['last_service_at'], ENT_QUOTES, 'UTF-8'); ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="next_service_at">Sonraki Bakım Tarihi</label>
                                <input type="date" class="form-control<?= isset($errors['next_service_at']) ? ' is-invalid' : ''; ?>" name="next_service_at" id="next_service_at" value="<?= htmlspecialchars($values['next_service_at'], ENT_QUOTES, 'UTF-8'); ?>">
                                <?php if (isset($errors['next_service_at'])): ?>
                                    <div class="invalid-feedback"><?= htmlspecialchars($errors['next_service_at'], ENT_QUOTES, 'UTF-8'); ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="inspection_expiry">Muayene Bitiş Tarihi</label>
                                <input type="date" class="form-control<?= isset($errors['inspection_expiry']) ? ' is-invalid' : ''; ?>" name="inspection_expiry" id="inspection_expiry" value="<?= htmlspecialchars($values['inspection_expiry'], ENT_QUOTES, 'UTF-8'); ?>">
                                <?php if (isset($errors['inspection_expiry'])): ?>
                                    <div class="invalid-feedback"><?= htmlspecialchars($errors['inspection_expiry'], ENT_QUOTES, 'UTF-8'); ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="insurance_expiry">Sigorta Bitiş Tarihi</label>
                                <input type="date" class="form-control<?= isset($errors['insurance_expiry']) ? ' is-invalid' : ''; ?>" name="insurance_expiry" id="insurance_expiry" value="<?= htmlspecialchars($values['insurance_expiry'], ENT_QUOTES, 'UTF-8'); ?>">
                                <?php if (isset($errors['insurance_expiry'])): ?>
                                    <div class="invalid-feedback"><?= htmlspecialchars($errors['insurance_expiry'], ENT_QUOTES, 'UTF-8'); ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="notes">Notlar</label>
                                <textarea class="form-control" name="notes" id="notes" rows="4"><?= htmlspecialchars($values['notes'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-4">
                            <span class="text-muted small">* zorunlu alanları ifade eder.</span>
                            <button type="submit" class="btn btn-primary">Değişiklikleri Kaydet</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
