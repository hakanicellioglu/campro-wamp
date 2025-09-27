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

$csrfToken = $_SESSION['csrf_token'];

$vehicles = [];
$statusBreakdown = [
    'active' => 0,
    'maintenance' => 0,
    'passive' => 0,
    'retired' => 0,
];
$upcomingMaintenance = [];
$editVehicle = null;

$view = strtolower(trim((string)($_GET['view'] ?? 'vehicles')));
if (!in_array($view, ['vehicles', 'routes', 'maintenance', 'shipments'], true)) {
    $view = 'vehicles';
}

try {
    $stmt = $pdo->query(
        'SELECT id, plate_number, type, brand, model, production_year,
                capacity_weight, capacity_volume, status,
                last_service_at, next_service_at, inspection_expiry, insurance_expiry,
                notes, created_at, updated_at
         FROM vehicles
         ORDER BY created_at DESC'
    );
    $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    foreach ($vehicles as $vehicle) {
        $status = strtolower((string)($vehicle['status'] ?? ''));
        if (isset($statusBreakdown[$status])) {
            $statusBreakdown[$status]++;
        }

        $nextService = $vehicle['next_service_at'] ?? null;
        if ($nextService) {
            try {
                $date = new DateTimeImmutable((string) $nextService);
                $now = new DateTimeImmutable('today');
                $diff = (int) $now->diff($date)->format('%r%a');
                if ($diff <= 30) {
                    $upcomingMaintenance[] = [
                        'vehicle' => $vehicle,
                        'days' => $diff,
                    ];
                }
            } catch (Throwable $e) {
                // Tarih parse edilemedi, görmezden gel
            }
        }
    }
} catch (PDOException $e) {
    error_log('Vehicle page fetch failed: ' . $e->getMessage());
    $vehicles = [];
}

$vehicleIdToEdit = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($vehicleIdToEdit) {
    try {
        $editStmt = $pdo->prepare(
            'SELECT id, plate_number, type, brand, model, production_year,
                    capacity_weight, capacity_volume, status,
                    last_service_at, next_service_at, inspection_expiry, insurance_expiry, notes
             FROM vehicles WHERE id = :id LIMIT 1'
        );
        $editStmt->execute(['id' => $vehicleIdToEdit]);
        $editVehicle = $editStmt->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (PDOException $e) {
        error_log('Vehicle edit fetch failed: ' . $e->getMessage());
        $editVehicle = null;
    }
}

$formatDate = static function (?string $date): string {
    if ($date === null || $date === '') {
        return '—';
    }
    try {
        $dt = new DateTimeImmutable($date);
        return $dt->format('d.m.Y');
    } catch (Throwable $e) {
        return (string) $date;
    }
};

$formatNumber = static function ($value): string {
    if ($value === null || $value === '') {
        return '—';
    }
    return number_format((float) $value, 2, ',', '.');
};

function e(?string $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

$vehicleStatuses = [
    'active' => 'Aktif',
    'maintenance' => 'Bakımda',
    'passive' => 'Pasif',
    'retired' => 'Emekli',
];

$totalVehicles = count($vehicles);
$totalUpcoming = count($upcomingMaintenance);

usort($upcomingMaintenance, static function (array $a, array $b): int {
    return $a['days'] <=> $b['days'];
});

?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Araç Yönetimi — Nexa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: #f8fafc;
            font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', Roboto, Arial, sans-serif;
        }

        main.main-with-sidebar {
            min-height: 100vh;
        }

        .card {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
        }

        .card-header {
            background: white;
            border-bottom: 1px solid rgba(226, 232, 240, 0.8);
        }

        .badge-status {
            font-size: 0.75rem;
            padding: 0.45rem 0.75rem;
            border-radius: 999px;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .badge-status.active { background: rgba(34, 197, 94, 0.16); color: #16a34a; }
        .badge-status.maintenance { background: rgba(245, 158, 11, 0.16); color: #d97706; }
        .badge-status.passive { background: rgba(148, 163, 184, 0.16); color: #475569; }
        .badge-status.retired { background: rgba(239, 68, 68, 0.16); color: #dc2626; }

        .table thead {
            background: rgba(15, 23, 42, 0.02);
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .table tbody tr {
            vertical-align: middle;
        }

        .stat-card {
            background: linear-gradient(135deg, #6366f1, #4338ca);
            color: white;
        }

        .stat-card.secondary {
            background: linear-gradient(135deg, #f97316, #ea580c);
        }

        .stat-card.neutral {
            background: linear-gradient(135deg, #0ea5e9, #0369a1);
        }

        .form-section {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 15px 35px rgba(15, 23, 42, 0.08);
        }

        .section-title {
            font-weight: 600;
            color: #1f2937;
            margin-bottom: .5rem;
        }
    </style>
</head>
<body>
<?php require_once __DIR__ . '/../partials/flash.php'; ?>
<div class="d-flex">
    <?php require_once __DIR__ . '/../component/sidebar.php'; ?>
    <main class="main-with-sidebar flex-grow-1 p-4">
        <div class="container-fluid">
            <div class="row g-4 align-items-stretch mb-4">
                <div class="col-12">
                    <header class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                        <div>
                            <h1 class="h3 mb-1">Araç Yönetimi</h1>
                            <p class="text-muted mb-0">Filo envanterinizi, bakım planlarınızı ve sevkiyat eşleşmelerini yönetin.</p>
                        </div>
                        <div class="btn-group" role="group" aria-label="Görünüm seçimleri">
                            <a href="vehicle.php" class="btn btn-outline-primary<?= $view === 'vehicles' ? ' active' : ''; ?>">
                                <i class="bi bi-truck"></i> Araçlar
                            </a>
                            <a href="vehicle_routes.php" class="btn btn-outline-primary<?= $view === 'routes' ? ' active' : ''; ?>">
                                <i class="bi bi-signpost"></i> Güzergahlar
                            </a>
                            <a href="vehicle_maintenance.php" class="btn btn-outline-primary<?= $view === 'maintenance' ? ' active' : ''; ?>">
                                <i class="bi bi-tools"></i> Bakım
                            </a>
                            <a href="shipments.php" class="btn btn-outline-primary<?= $view === 'shipments' ? ' active' : ''; ?>">
                                <i class="bi bi-box-seam"></i> Sevkiyatlar
                            </a>
                        </div>
                    </header>
                </div>

                <div class="col-12 col-lg-4">
                    <div class="card stat-card h-100">
                        <div class="card-body">
                            <p class="text-uppercase text-white-50 mb-1">Toplam Araç</p>
                            <h2 class="display-5 fw-semibold mb-3"><?= $totalVehicles; ?></h2>
                            <div class="d-flex flex-wrap gap-2">
                                <?php foreach ($statusBreakdown as $statusKey => $count): ?>
                                    <span class="badge badge-status <?= e($statusKey); ?>"><?= e($vehicleStatuses[$statusKey]); ?> · <?= $count; ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-lg-4">
                    <div class="card stat-card secondary h-100">
                        <div class="card-body">
                            <p class="text-uppercase text-white-50 mb-1">30 Gün İçinde Bakım</p>
                            <h2 class="display-6 fw-semibold mb-3"><?= $totalUpcoming; ?></h2>
                            <p class="mb-0 text-white-75 small">Planlanan bakımları takip ederek arıza riskini azaltın.</p>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-lg-4">
                    <div class="card stat-card neutral h-100">
                        <div class="card-body">
                            <p class="text-uppercase text-white-50 mb-1">Hızlı Aksiyon</p>
                            <ul class="list-unstyled mb-0 small">
                                <li class="mb-2"><i class="bi bi-shield-check me-2"></i>Muayene tarihi yaklaşan araçları kontrol edin.</li>
                                <li class="mb-2"><i class="bi bi-file-earmark-text me-2"></i>Sigorta bitişlerini güncel tutun.</li>
                                <li><i class="bi bi-geo-alt me-2"></i>Sevkiyat planlarıyla araçları eşleştirin.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-12 col-xl-5">
                    <section class="p-4 form-section">
                        <h2 class="section-title">Yeni Araç Ekle</h2>
                        <p class="text-muted small">Filonuza yeni araç eklerken temel bilgileri eksiksiz girin.</p>
                        <form action="../api/vehicle/add.php" method="post" class="row g-3" novalidate>
                            <input type="hidden" name="csrf_token" value="<?= e($csrfToken); ?>">
                            <div class="col-md-6">
                                <label for="plate_number" class="form-label">Plaka</label>
                                <input type="text" class="form-control" id="plate_number" name="plate_number" maxlength="20" required>
                            </div>
                            <div class="col-md-6">
                                <label for="type" class="form-label">Tür</label>
                                <input type="text" class="form-control" id="type" name="type" maxlength="60" required>
                            </div>
                            <div class="col-md-6">
                                <label for="brand" class="form-label">Marka</label>
                                <input type="text" class="form-control" id="brand" name="brand" maxlength="80">
                            </div>
                            <div class="col-md-6">
                                <label for="model" class="form-label">Model</label>
                                <input type="text" class="form-control" id="model" name="model" maxlength="80">
                            </div>
                            <div class="col-md-6">
                                <label for="production_year" class="form-label">Model Yılı</label>
                                <input type="number" class="form-control" id="production_year" name="production_year" min="1950" max="<?= date('Y') + 1; ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="status" class="form-label">Durum</label>
                                <select class="form-select" id="status" name="status">
                                    <?php foreach ($vehicleStatuses as $key => $label): ?>
                                        <option value="<?= e($key); ?>"<?= $key === 'active' ? ' selected' : ''; ?>><?= e($label); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="capacity_weight" class="form-label">Kapasite (kg)</label>
                                <input type="number" step="0.01" class="form-control" id="capacity_weight" name="capacity_weight" min="0">
                            </div>
                            <div class="col-md-6">
                                <label for="capacity_volume" class="form-label">Hacim (m³)</label>
                                <input type="number" step="0.01" class="form-control" id="capacity_volume" name="capacity_volume" min="0">
                            </div>
                            <div class="col-md-6">
                                <label for="last_service_at" class="form-label">Son Servis</label>
                                <input type="date" class="form-control" id="last_service_at" name="last_service_at">
                            </div>
                            <div class="col-md-6">
                                <label for="next_service_at" class="form-label">Sonraki Servis</label>
                                <input type="date" class="form-control" id="next_service_at" name="next_service_at">
                            </div>
                            <div class="col-md-6">
                                <label for="inspection_expiry" class="form-label">Muayene Bitişi</label>
                                <input type="date" class="form-control" id="inspection_expiry" name="inspection_expiry">
                            </div>
                            <div class="col-md-6">
                                <label for="insurance_expiry" class="form-label">Sigorta Bitişi</label>
                                <input type="date" class="form-control" id="insurance_expiry" name="insurance_expiry">
                            </div>
                            <div class="col-12">
                                <label for="notes" class="form-label">Notlar</label>
                                <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary w-100">Araç Ekle</button>
                            </div>
                        </form>
                    </section>
                </div>

                <div class="col-12 col-xl-7">
                    <section class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <h2 class="h5 mb-0">Araç Envanteri</h2>
                                <small class="text-muted">Filo kayıtlarını filtreleyin, güncelleyin ve hızlı aksiyon alın.</small>
                            </div>
                            <a href="vehicle.php" class="btn btn-sm btn-outline-secondary">Filtreleri Temizle</a>
                        </div>
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead>
                                <tr>
                                    <th>Plaka</th>
                                    <th>Bilgiler</th>
                                    <th>Durum</th>
                                    <th>Bakım</th>
                                    <th>Sözleşmeler</th>
                                    <th class="text-end">İşlemler</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php if ($vehicles === []): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">Kayıtlı araç bulunamadı.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($vehicles as $vehicle): ?>
                                        <tr>
                                            <td class="fw-semibold">
                                                <?= e($vehicle['plate_number']); ?><br>
                                                <span class="text-muted small"><?= e($vehicle['type']); ?></span>
                                            </td>
                                            <td>
                                                <div class="small text-muted">
                                                    <div><?= e($vehicle['brand'] ?? '—'); ?> <?= e($vehicle['model'] ?? ''); ?></div>
                                                    <div>Yıl: <?= e($vehicle['production_year'] ? (string)$vehicle['production_year'] : '—'); ?></div>
                                                    <div>Kapasite: <?= $formatNumber($vehicle['capacity_weight']); ?> kg / <?= $formatNumber($vehicle['capacity_volume']); ?> m³</div>
                                                </div>
                                            </td>
                                            <td>
                                                <?php $statusKey = strtolower((string)($vehicle['status'] ?? 'active')); ?>
                                                <span class="badge-status <?= e($statusKey); ?>">
                                                    <?= e($vehicleStatuses[$statusKey] ?? ucfirst($statusKey)); ?>
                                                </span>
                                            </td>
                                            <td class="small text-muted">
                                                <div>Son: <?= $formatDate($vehicle['last_service_at'] ?? null); ?></div>
                                                <div>Sonraki: <?= $formatDate($vehicle['next_service_at'] ?? null); ?></div>
                                            </td>
                                            <td class="small text-muted">
                                                <div>Muayene: <?= $formatDate($vehicle['inspection_expiry'] ?? null); ?></div>
                                                <div>Sigorta: <?= $formatDate($vehicle['insurance_expiry'] ?? null); ?></div>
                                            </td>
                                            <td class="text-end">
                                                <div class="btn-group" role="group">
                                                    <a href="vehicle.php?edit=<?= (int)$vehicle['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-pencil-square"></i>
                                                    </a>
                                                    <form action="../api/vehicle/delete.php" method="post" class="d-inline" onsubmit="return confirm('Bu aracı silmek istediğinize emin misiniz?');">
                                                        <input type="hidden" name="csrf_token" value="<?= e($csrfToken); ?>">
                                                        <input type="hidden" name="id" value="<?= (int)$vehicle['id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <?php if ($editVehicle !== null): ?>
                        <section class="p-4 form-section mt-4">
                            <h2 class="section-title">Araç Bilgilerini Güncelle</h2>
                            <p class="text-muted small mb-4">Seçili araç kaydını güncelleyin. Plaka ve tür alanları zorunludur.</p>
                            <form action="../api/vehicle/edit.php" method="post" class="row g-3">
                                <input type="hidden" name="csrf_token" value="<?= e($csrfToken); ?>">
                                <input type="hidden" name="id" value="<?= (int)$editVehicle['id']; ?>">
                                <div class="col-md-6">
                                    <label for="edit_plate_number" class="form-label">Plaka</label>
                                    <input type="text" class="form-control" id="edit_plate_number" name="plate_number" value="<?= e($editVehicle['plate_number']); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="edit_type" class="form-label">Tür</label>
                                    <input type="text" class="form-control" id="edit_type" name="type" value="<?= e($editVehicle['type']); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="edit_brand" class="form-label">Marka</label>
                                    <input type="text" class="form-control" id="edit_brand" name="brand" value="<?= e($editVehicle['brand'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="edit_model" class="form-label">Model</label>
                                    <input type="text" class="form-control" id="edit_model" name="model" value="<?= e($editVehicle['model'] ?? ''); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label for="edit_production_year" class="form-label">Model Yılı</label>
                                    <input type="number" class="form-control" id="edit_production_year" name="production_year" min="1950" max="<?= date('Y') + 1; ?>" value="<?= e($editVehicle['production_year'] ? (string)$editVehicle['production_year'] : ''); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label for="edit_capacity_weight" class="form-label">Kapasite (kg)</label>
                                    <input type="number" step="0.01" class="form-control" id="edit_capacity_weight" name="capacity_weight" value="<?= e($editVehicle['capacity_weight'] !== null ? (string)$editVehicle['capacity_weight'] : ''); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label for="edit_capacity_volume" class="form-label">Hacim (m³)</label>
                                    <input type="number" step="0.01" class="form-control" id="edit_capacity_volume" name="capacity_volume" value="<?= e($editVehicle['capacity_volume'] !== null ? (string)$editVehicle['capacity_volume'] : ''); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label for="edit_status" class="form-label">Durum</label>
                                    <select class="form-select" id="edit_status" name="status">
                                        <?php foreach ($vehicleStatuses as $key => $label): ?>
                                            <option value="<?= e($key); ?>"<?= strtolower((string)$editVehicle['status']) === $key ? ' selected' : ''; ?>><?= e($label); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="edit_last_service_at" class="form-label">Son Servis</label>
                                    <input type="date" class="form-control" id="edit_last_service_at" name="last_service_at" value="<?= e($editVehicle['last_service_at'] ?? ''); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label for="edit_next_service_at" class="form-label">Sonraki Servis</label>
                                    <input type="date" class="form-control" id="edit_next_service_at" name="next_service_at" value="<?= e($editVehicle['next_service_at'] ?? ''); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label for="edit_inspection_expiry" class="form-label">Muayene Bitişi</label>
                                    <input type="date" class="form-control" id="edit_inspection_expiry" name="inspection_expiry" value="<?= e($editVehicle['inspection_expiry'] ?? ''); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label for="edit_insurance_expiry" class="form-label">Sigorta Bitişi</label>
                                    <input type="date" class="form-control" id="edit_insurance_expiry" name="insurance_expiry" value="<?= e($editVehicle['insurance_expiry'] ?? ''); ?>">
                                </div>
                                <div class="col-12">
                                    <label for="edit_notes" class="form-label">Notlar</label>
                                    <textarea class="form-control" id="edit_notes" name="notes" rows="3"><?= e($editVehicle['notes'] ?? ''); ?></textarea>
                                </div>
                                <div class="col-12 d-flex gap-2">
                                    <button type="submit" class="btn btn-primary flex-grow-1">Değişiklikleri Kaydet</button>
                                    <a href="vehicle.php" class="btn btn-outline-secondary">İptal</a>
                                </div>
                            </form>
                        </section>
                    <?php endif; ?>

                    <?php if ($upcomingMaintenance !== []): ?>
                        <section class="card mt-4">
                            <div class="card-header">
                                <h2 class="h5 mb-0">Yaklaşan Bakımlar</h2>
                                <small class="text-muted">Önümüzdeki 30 gün içerisinde servise girmesi gereken araçlar.</small>
                            </div>
                            <div class="list-group list-group-flush">
                                <?php foreach ($upcomingMaintenance as $item): ?>
                                    <?php $vehicle = $item['vehicle']; ?>
                                    <div class="list-group-item d-flex justify-content-between align-items-start">
                                        <div>
                                            <div class="fw-semibold"><?= e($vehicle['plate_number']); ?> · <?= e($vehicle['type']); ?></div>
                                            <div class="small text-muted">Sonraki servis: <?= $formatDate($vehicle['next_service_at'] ?? null); ?></div>
                                        </div>
                                        <span class="badge bg-warning text-dark">
                                            <?= $item['days'] >= 0 ? $item['days'] . ' gün' : abs($item['days']) . ' gün gecikti'; ?>
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
