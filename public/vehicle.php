<?php
declare(strict_types=1);

session_start();

if (empty($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once __DIR__ . '/../config.php';

$validViews = ['vehicles', 'routes', 'maintenance'];
$view = strtolower(trim((string)($_GET['view'] ?? 'vehicles')));
if (!in_array($view, $validViews, true)) {
    $view = 'vehicles';
}

function e(?string $value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

$csrfToken = $_SESSION['csrf_token'];

$vehicleStatuses = [
    'active' => 'Aktif',
    'maintenance' => 'Bakımda',
    'passive' => 'Pasif',
    'retired' => 'Emekli',
];

$vehicles = [];
$editVehicle = null;
$routes = [];
$maintenances = [];

if ($view === 'vehicles') {
    try {
        $stmt = $pdo->query(
            'SELECT id, plate_number, type, brand, model, production_year, status, capacity_weight, capacity_volume
             FROM vehicles
             ORDER BY created_at DESC'
        );
        $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (PDOException $e) {
        error_log('Vehicle list failed: ' . $e->getMessage());
        $vehicles = [];
    }

    $vehicleIdToEdit = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($vehicleIdToEdit) {
        try {
            $editStmt = $pdo->prepare(
                'SELECT id, plate_number, type, brand, model, production_year, capacity_weight, capacity_volume,
                        status, last_service_at, next_service_at, inspection_expiry, insurance_expiry, notes
                 FROM vehicles WHERE id = :id LIMIT 1'
            );
            $editStmt->execute(['id' => $vehicleIdToEdit]);
            $editVehicle = $editStmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            error_log('Vehicle edit fetch failed: ' . $e->getMessage());
            $editVehicle = null;
        }
    }
} elseif ($view === 'routes') {
    try {
        $stmt = $pdo->query(
            'SELECT vr.id, vr.route_date, vr.origin, vr.destination, vr.departure_time, vr.arrival_time,
                    vr.status, v.plate_number
             FROM vehicle_routes vr
             INNER JOIN vehicles v ON v.id = vr.vehicle_id
             ORDER BY vr.route_date DESC, vr.departure_time DESC'
        );
        $routes = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (PDOException $e) {
        error_log('Vehicle routes fetch failed: ' . $e->getMessage());
        $routes = [];
    }
} else { // maintenance
    try {
        $stmt = $pdo->query(
            'SELECT vm.id, vm.maintenance_date, vm.maintenance_type, vm.status, vm.next_due_date,
                    vm.service_center, vm.cost, v.plate_number
             FROM vehicle_maintenance vm
             INNER JOIN vehicles v ON v.id = vm.vehicle_id
             ORDER BY vm.maintenance_date DESC, vm.id DESC'
        );
        $maintenances = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (PDOException $e) {
        error_log('Vehicle maintenance fetch failed: ' . $e->getMessage());
        $maintenances = [];
    }
}

$routeStatusLabels = [
    'planned' => 'Planlandı',
    'in_transit' => 'Yolda',
    'completed' => 'Tamamlandı',
    'cancelled' => 'İptal Edildi',
];

$maintenanceStatusLabels = [
    'planned' => 'Planlandı',
    'in_progress' => 'Devam Ediyor',
    'completed' => 'Tamamlandı',
];

$formatDate = static function (?string $value): string {
    if ($value === null || $value === '') {
        return '—';
    }
    try {
        return (new DateTimeImmutable($value))->format('d.m.Y');
    } catch (Throwable $e) {
        return (string)$value;
    }
};

$formatTime = static function (?string $value): string {
    if ($value === null || $value === '') {
        return '—';
    }
    try {
        return (new DateTimeImmutable($value))->format('H:i');
    } catch (Throwable $e) {
        return (string)$value;
    }
};

?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Araç Yönetimi</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8fafc;
        }
        main.main-with-sidebar {
            min-height: 100vh;
        }
        .table thead {
            background-color: #e2e8f0;
        }
    </style>
</head>
<body>
<?php require_once __DIR__ . '/../partials/flash.php'; ?>
<div class="d-flex">
    <?php require_once __DIR__ . '/../component/sidebar.php'; ?>
    <main class="main-with-sidebar flex-grow-1 p-4">
        <div class="container-fluid">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
                <div>
                    <h1 class="h4 mb-1">Araç Yönetimi</h1>
                    <p class="text-muted mb-0">Araç envanterinizi, güzergah planlarını ve bakım kayıtlarını yönetin.</p>
                </div>
                <?php if ($view === 'vehicles'): ?>
                    <button class="btn btn-primary" type="button" data-bs-toggle="collapse" data-bs-target="#addVehicleForm" aria-expanded="false" aria-controls="addVehicleForm">
                        <i class="bi bi-plus-lg"></i> Araç Ekle
                    </button>
                <?php endif; ?>
            </div>

            <ul class="nav nav-tabs mb-4">
                <li class="nav-item">
                    <a class="nav-link<?= $view === 'vehicles' ? ' active' : ''; ?>" href="vehicle.php?view=vehicles">Araçlar</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link<?= $view === 'routes' ? ' active' : ''; ?>" href="vehicle.php?view=routes">Güzergahlar</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link<?= $view === 'maintenance' ? ' active' : ''; ?>" href="vehicle.php?view=maintenance">Bakım</a>
                </li>
            </ul>

            <?php if ($view === 'vehicles'): ?>
                <div class="collapse mb-4" id="addVehicleForm">
                    <div class="card card-body">
                        <h2 class="h5 mb-3">Yeni Araç</h2>
                        <form action="../api/vehicle/add.php" method="post" class="row g-3">
                            <input type="hidden" name="csrf_token" value="<?= e($csrfToken); ?>">
                            <div class="col-md-4">
                                <label for="plate_number" class="form-label">Plaka *</label>
                                <input type="text" class="form-control" id="plate_number" name="plate_number" maxlength="20" required>
                            </div>
                            <div class="col-md-4">
                                <label for="type" class="form-label">Tür *</label>
                                <input type="text" class="form-control" id="type" name="type" maxlength="60" required>
                            </div>
                            <div class="col-md-4">
                                <label for="status" class="form-label">Durum</label>
                                <select class="form-select" id="status" name="status">
                                    <?php foreach ($vehicleStatuses as $key => $label): ?>
                                        <option value="<?= e($key); ?>"<?= $key === 'active' ? ' selected' : ''; ?>><?= e($label); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="brand" class="form-label">Marka</label>
                                <input type="text" class="form-control" id="brand" name="brand" maxlength="80">
                            </div>
                            <div class="col-md-4">
                                <label for="model" class="form-label">Model</label>
                                <input type="text" class="form-control" id="model" name="model" maxlength="80">
                            </div>
                            <div class="col-md-4">
                                <label for="production_year" class="form-label">Model Yılı</label>
                                <input type="number" class="form-control" id="production_year" name="production_year" min="1900" max="<?= date('Y') + 1; ?>">
                            </div>
                            <div class="col-md-4">
                                <label for="capacity_weight" class="form-label">Kapasite (kg)</label>
                                <input type="number" step="0.01" min="0" class="form-control" id="capacity_weight" name="capacity_weight">
                            </div>
                            <div class="col-md-4">
                                <label for="capacity_volume" class="form-label">Hacim (m³)</label>
                                <input type="number" step="0.01" min="0" class="form-control" id="capacity_volume" name="capacity_volume">
                            </div>
                            <div class="col-md-4">
                                <label for="last_service_at" class="form-label">Son Servis</label>
                                <input type="date" class="form-control" id="last_service_at" name="last_service_at">
                            </div>
                            <div class="col-md-4">
                                <label for="next_service_at" class="form-label">Sonraki Servis</label>
                                <input type="date" class="form-control" id="next_service_at" name="next_service_at">
                            </div>
                            <div class="col-md-4">
                                <label for="inspection_expiry" class="form-label">Muayene Bitiş</label>
                                <input type="date" class="form-control" id="inspection_expiry" name="inspection_expiry">
                            </div>
                            <div class="col-md-4">
                                <label for="insurance_expiry" class="form-label">Sigorta Bitiş</label>
                                <input type="date" class="form-control" id="insurance_expiry" name="insurance_expiry">
                            </div>
                            <div class="col-12">
                                <label for="notes" class="form-label">Notlar</label>
                                <textarea class="form-control" id="notes" name="notes" rows="3" maxlength="500"></textarea>
                            </div>
                            <div class="col-12 text-end">
                                <button type="submit" class="btn btn-primary">Kaydet</button>
                            </div>
                        </form>
                    </div>
                </div>

                <?php if ($editVehicle): ?>
                    <div class="card mb-4" id="editVehicleForm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h2 class="h5 mb-0">Araç Düzenle</h2>
                                <a class="btn btn-sm btn-outline-secondary" href="vehicle.php?view=vehicles">İptal</a>
                            </div>
                            <form action="../api/vehicle/edit.php" method="post" class="row g-3">
                                <input type="hidden" name="csrf_token" value="<?= e($csrfToken); ?>">
                                <input type="hidden" name="id" value="<?= (int)$editVehicle['id']; ?>">
                                <div class="col-md-4">
                                    <label for="edit_plate_number" class="form-label">Plaka *</label>
                                    <input type="text" class="form-control" id="edit_plate_number" name="plate_number" maxlength="20" required value="<?= e((string)$editVehicle['plate_number']); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label for="edit_type" class="form-label">Tür *</label>
                                    <input type="text" class="form-control" id="edit_type" name="type" maxlength="60" required value="<?= e((string)$editVehicle['type']); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label for="edit_status" class="form-label">Durum</label>
                                    <select class="form-select" id="edit_status" name="status">
                                        <?php foreach ($vehicleStatuses as $key => $label): ?>
                                            <option value="<?= e($key); ?>"<?= $key === ($editVehicle['status'] ?? '') ? ' selected' : ''; ?>><?= e($label); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="edit_brand" class="form-label">Marka</label>
                                    <input type="text" class="form-control" id="edit_brand" name="brand" maxlength="80" value="<?= e((string)($editVehicle['brand'] ?? '')); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label for="edit_model" class="form-label">Model</label>
                                    <input type="text" class="form-control" id="edit_model" name="model" maxlength="80" value="<?= e((string)($editVehicle['model'] ?? '')); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label for="edit_production_year" class="form-label">Model Yılı</label>
                                    <input type="number" class="form-control" id="edit_production_year" name="production_year" min="1900" max="<?= date('Y') + 1; ?>" value="<?= e((string)($editVehicle['production_year'] ?? '')); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label for="edit_capacity_weight" class="form-label">Kapasite (kg)</label>
                                    <input type="number" step="0.01" min="0" class="form-control" id="edit_capacity_weight" name="capacity_weight" value="<?= e((string)($editVehicle['capacity_weight'] ?? '')); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label for="edit_capacity_volume" class="form-label">Hacim (m³)</label>
                                    <input type="number" step="0.01" min="0" class="form-control" id="edit_capacity_volume" name="capacity_volume" value="<?= e((string)($editVehicle['capacity_volume'] ?? '')); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label for="edit_last_service_at" class="form-label">Son Servis</label>
                                    <input type="date" class="form-control" id="edit_last_service_at" name="last_service_at" value="<?= e((string)($editVehicle['last_service_at'] ?? '')); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label for="edit_next_service_at" class="form-label">Sonraki Servis</label>
                                    <input type="date" class="form-control" id="edit_next_service_at" name="next_service_at" value="<?= e((string)($editVehicle['next_service_at'] ?? '')); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label for="edit_inspection_expiry" class="form-label">Muayene Bitiş</label>
                                    <input type="date" class="form-control" id="edit_inspection_expiry" name="inspection_expiry" value="<?= e((string)($editVehicle['inspection_expiry'] ?? '')); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label for="edit_insurance_expiry" class="form-label">Sigorta Bitiş</label>
                                    <input type="date" class="form-control" id="edit_insurance_expiry" name="insurance_expiry" value="<?= e((string)($editVehicle['insurance_expiry'] ?? '')); ?>">
                                </div>
                                <div class="col-12">
                                    <label for="edit_notes" class="form-label">Notlar</label>
                                    <textarea class="form-control" id="edit_notes" name="notes" rows="3" maxlength="500"><?= e((string)($editVehicle['notes'] ?? '')); ?></textarea>
                                </div>
                                <div class="col-12 text-end">
                                    <button type="submit" class="btn btn-primary">Güncelle</button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th scope="col">Plaka</th>
                                        <th scope="col">Tür</th>
                                        <th scope="col">Marka / Model</th>
                                        <th scope="col">Model Yılı</th>
                                        <th scope="col">Durum</th>
                                        <th scope="col" class="text-end">İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($vehicles === []): ?>
                                        <tr>
                                            <td colspan="6" class="text-center text-muted">Kayıtlı araç bulunmuyor.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($vehicles as $vehicle): ?>
                                            <tr>
                                                <td><?= e((string)$vehicle['plate_number']); ?></td>
                                                <td><?= e((string)$vehicle['type']); ?></td>
                                                <td><?= e(trim(((string)($vehicle['brand'] ?? '')) . ' ' . ((string)($vehicle['model'] ?? '')))); ?></td>
                                                <td><?= e($vehicle['production_year'] !== null ? (string)$vehicle['production_year'] : '—'); ?></td>
                                                <td>
                                                    <?php $statusKey = strtolower((string)($vehicle['status'] ?? 'active')); ?>
                                                    <span class="badge bg-light text-dark border">
                                                        <?= e($vehicleStatuses[$statusKey] ?? 'Bilinmiyor'); ?>
                                                    </span>
                                                </td>
                                                <td class="text-end">
                                                    <div class="d-inline-flex gap-2">
                                                        <a class="btn btn-sm btn-outline-primary" href="vehicle.php?view=vehicles&amp;edit=<?= (int)$vehicle['id']; ?>#editVehicleForm">Düzenle</a>
                                                        <form action="../api/vehicle/delete.php" method="post" onsubmit="return confirm('Bu aracı silmek istediğinize emin misiniz?');">
                                                            <input type="hidden" name="csrf_token" value="<?= e($csrfToken); ?>">
                                                            <input type="hidden" name="id" value="<?= (int)$vehicle['id']; ?>">
                                                            <button type="submit" class="btn btn-sm btn-outline-danger">Sil</button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php elseif ($view === 'routes'): ?>
                <div class="card">
                    <div class="card-body">
                        <h2 class="h5 mb-3">Planlanan Güzergahlar</h2>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th scope="col">Tarih</th>
                                        <th scope="col">Araç</th>
                                        <th scope="col">Çıkış</th>
                                        <th scope="col">Varış</th>
                                        <th scope="col">Kalkış</th>
                                        <th scope="col">Varış Saati</th>
                                        <th scope="col">Durum</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($routes === []): ?>
                                        <tr>
                                            <td colspan="7" class="text-center text-muted">Henüz güzergah planı bulunmuyor.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($routes as $route): ?>
                                            <tr>
                                                <td><?= e($formatDate((string)($route['route_date'] ?? ''))); ?></td>
                                                <td><?= e((string)$route['plate_number']); ?></td>
                                                <td><?= e((string)$route['origin']); ?></td>
                                                <td><?= e((string)$route['destination']); ?></td>
                                                <td><?= e($formatTime((string)($route['departure_time'] ?? ''))); ?></td>
                                                <td><?= e($formatTime((string)($route['arrival_time'] ?? ''))); ?></td>
                                                <?php $routeStatus = strtolower((string)($route['status'] ?? 'planned')); ?>
                                                <td><span class="badge bg-secondary-subtle text-dark border"><?= e($routeStatusLabels[$routeStatus] ?? 'Bilinmiyor'); ?></span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="card">
                    <div class="card-body">
                        <h2 class="h5 mb-3">Bakım Kayıtları</h2>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th scope="col">Tarih</th>
                                        <th scope="col">Araç</th>
                                        <th scope="col">Tür</th>
                                        <th scope="col">Servis</th>
                                        <th scope="col">Sonraki Tarih</th>
                                        <th scope="col">Durum</th>
                                        <th scope="col" class="text-end">Maliyet</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($maintenances === []): ?>
                                        <tr>
                                            <td colspan="7" class="text-center text-muted">Kayıtlı bakım bulunmuyor.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($maintenances as $maintenance): ?>
                                            <?php $maintenanceStatus = strtolower((string)($maintenance['status'] ?? 'planned')); ?>
                                            <tr>
                                                <td><?= e($formatDate((string)($maintenance['maintenance_date'] ?? ''))); ?></td>
                                                <td><?= e((string)$maintenance['plate_number']); ?></td>
                                                <td><?= e((string)$maintenance['maintenance_type']); ?></td>
                                                <td><?= e((string)($maintenance['service_center'] ?? '—')); ?></td>
                                                <td><?= e($formatDate((string)($maintenance['next_due_date'] ?? ''))); ?></td>
                                                <td><span class="badge bg-secondary-subtle text-dark border"><?= e($maintenanceStatusLabels[$maintenanceStatus] ?? 'Bilinmiyor'); ?></span></td>
                                                <td class="text-end">
                                                    <?php $cost = $maintenance['cost'] ?? null; ?>
                                                    <?= $cost !== null && $cost !== '' ? e(number_format((float)$cost, 2, ',', '.')) . ' ₺' : '—'; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
