<?php
declare(strict_types=1);

session_start();

if (empty($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit;
}

require_once __DIR__ . '/../config.php';

$view = strtolower((string) ($_GET['view'] ?? 'overview'));
$allowedViews = ['overview', 'routes', 'maintenance', 'shipments'];
if (!in_array($view, $allowedViews, true)) {
    $view = 'overview';
}

function e(?string $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function formatDate(?string $value): ?string
{
    if ($value === null) {
        return null;
    }

    $trimmed = trim($value);
    if ($trimmed === '') {
        return null;
    }

    try {
        $date = new DateTimeImmutable($trimmed);
        return $date->format('d.m.Y');
    } catch (Throwable $exception) {
        if (!preg_match('/^0{4}-0{2}-0{2}/', $trimmed)) {
            error_log('Vehicle date format failed: ' . $exception->getMessage());
        }
        return null;
    }
}

function formatTime(?string $value): ?string
{
    if ($value === null) {
        return null;
    }

    $trimmed = trim($value);
    if ($trimmed === '') {
        return null;
    }

    $time = DateTime::createFromFormat('H:i:s', $trimmed) ?: DateTime::createFromFormat('H:i', $trimmed);
    if ($time instanceof DateTime) {
        return $time->format('H:i');
    }

    return null;
}

$stats = [
    'totalVehicles'   => 0,
    'activeVehicles'  => 0,
    'maintenanceDue'  => 0,
    'routesToday'     => 0,
];

try {
    $stats['totalVehicles'] = (int) $pdo->query('SELECT COUNT(*) FROM vehicles')->fetchColumn();
} catch (PDOException $e) {
    error_log('Vehicle stats (total) failed: ' . $e->getMessage());
}

try {
    $stats['activeVehicles'] = (int) $pdo->query("SELECT COUNT(*) FROM vehicles WHERE status = 'active'")->fetchColumn();
} catch (PDOException $e) {
    error_log('Vehicle stats (active) failed: ' . $e->getMessage());
}

try {
    $dueStmt = $pdo->query('SELECT COUNT(*) FROM vehicles WHERE next_service_at IS NOT NULL AND next_service_at <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)');
    $stats['maintenanceDue'] = (int) $dueStmt->fetchColumn();
} catch (PDOException $e) {
    error_log('Vehicle stats (maintenance) failed: ' . $e->getMessage());
}

try {
    $routesStmt = $pdo->query('SELECT COUNT(*) FROM vehicle_routes WHERE route_date = CURDATE()');
    $stats['routesToday'] = (int) $routesStmt->fetchColumn();
} catch (PDOException $e) {
    error_log('Vehicle stats (routes) failed: ' . $e->getMessage());
}

$vehicleList = [];
$recentMaintenance = [];
$routeList = [];
$shipments = [];
$shipmentsError = false;

if ($view === 'overview') {
    try {
        $vehicleQuery = $pdo->query(
            'SELECT 
                v.id,
                v.plate_number,
                v.type,
                v.brand,
                v.model,
                v.production_year,
                v.capacity_weight,
                v.capacity_volume,
                v.status,
                v.last_service_at,
                v.next_service_at,
                v.inspection_expiry,
                v.insurance_expiry,
                (SELECT route_date FROM vehicle_routes WHERE vehicle_id = v.id ORDER BY route_date DESC, id DESC LIMIT 1) AS last_route_date,
                (SELECT status FROM vehicle_routes WHERE vehicle_id = v.id ORDER BY route_date DESC, id DESC LIMIT 1) AS last_route_status
            FROM vehicles v
            ORDER BY v.plate_number ASC'
        );
        $vehicleList = $vehicleQuery->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (PDOException $e) {
        error_log('Vehicle overview fetch failed: ' . $e->getMessage());
        $vehicleList = [];
    }

    try {
        $maintenanceStmt = $pdo->query(
            'SELECT 
                m.id,
                m.vehicle_id,
                m.maintenance_date,
                m.maintenance_type,
                m.status,
                m.next_due_date,
                m.cost,
                v.plate_number
            FROM vehicle_maintenance m
            INNER JOIN vehicles v ON v.id = m.vehicle_id
            ORDER BY m.maintenance_date DESC
            LIMIT 5'
        );
        $recentMaintenance = $maintenanceStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (PDOException $e) {
        error_log('Vehicle overview maintenance fetch failed: ' . $e->getMessage());
        $recentMaintenance = [];
    }
}

if ($view === 'routes') {
    $routeStatuses = ['planned', 'in_transit', 'completed', 'cancelled'];
    $filters = [
        'date'   => trim((string) ($_GET['date'] ?? '')),
        'status' => strtolower(trim((string) ($_GET['status'] ?? ''))),
    ];

    if ($filters['status'] !== '' && !in_array($filters['status'], $routeStatuses, true)) {
        $filters['status'] = '';
    }

    $conditions = [];
    $params = [];

    if ($filters['date'] !== '') {
        $conditions[] = 'r.route_date = :route_date';
        $params[':route_date'] = $filters['date'];
    }

    if ($filters['status'] !== '') {
        $conditions[] = 'r.status = :status';
        $params[':status'] = $filters['status'];
    }

    $sql = 'SELECT 
                r.id,
                r.route_date,
                r.origin,
                r.destination,
                r.departure_time,
                r.arrival_time,
                r.cargo_summary,
                r.status,
                v.plate_number,
                v.type
            FROM vehicle_routes r
            INNER JOIN vehicles v ON v.id = r.vehicle_id';

    if ($conditions !== []) {
        $sql .= ' WHERE ' . implode(' AND ', $conditions);
    }

    $sql .= ' ORDER BY r.route_date DESC, r.departure_time ASC LIMIT 150';

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $routeList = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (PDOException $e) {
        error_log('Vehicle routes fetch failed: ' . $e->getMessage());
        $routeList = [];
    }
}

if ($view === 'maintenance') {
    $maintenanceStatuses = ['planned', 'in_progress', 'completed'];
    $filters = [
        'status' => strtolower(trim((string) ($_GET['status'] ?? ''))),
    ];

    if ($filters['status'] !== '' && !in_array($filters['status'], $maintenanceStatuses, true)) {
        $filters['status'] = '';
    }

    $conditions = [];
    $params = [];

    if ($filters['status'] !== '') {
        $conditions[] = 'm.status = :status';
        $params[':status'] = $filters['status'];
    }

    $sql = 'SELECT 
                m.id,
                m.vehicle_id,
                m.maintenance_date,
                m.maintenance_type,
                m.description,
                m.status,
                m.next_due_date,
                m.cost,
                m.service_center,
                v.plate_number,
                v.brand,
                v.model
            FROM vehicle_maintenance m
            INNER JOIN vehicles v ON v.id = m.vehicle_id';

    if ($conditions !== []) {
        $sql .= ' WHERE ' . implode(' AND ', $conditions);
    }

    $sql .= ' ORDER BY m.maintenance_date DESC, m.id DESC LIMIT 200';

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $recentMaintenance = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (PDOException $e) {
        error_log('Vehicle maintenance fetch failed: ' . $e->getMessage());
        $recentMaintenance = [];
    }
}

if ($view === 'shipments') {
    try {
        $sql = 'SELECT 
                    s.id,
                    s.shipment_code,
                    s.ship_date,
                    s.status,
                    s.destination,
                    s.origin,
                    s.cargo_description,
                    s.vehicle_id,
                    v.plate_number
                FROM shipments s
                LEFT JOIN vehicles v ON v.id = s.vehicle_id
                ORDER BY s.ship_date DESC, s.id DESC
                LIMIT 150';
        $stmt = $pdo->query($sql);
        $shipments = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (PDOException $e) {
        $shipmentsError = true;
        error_log('Vehicle shipments fetch failed: ' . $e->getMessage());
        $shipments = [];
    }
}

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
        :root {
            --nexa-surface: #ffffff;
            --nexa-border: #e2e8f0;
            --nexa-muted: #64748b;
            --nexa-strong: #0f172a;
            --nexa-accent: #2563eb;
            --nexa-accent-soft: rgba(37, 99, 235, 0.1);
            --nexa-success: #16a34a;
            --nexa-warning: #f59e0b;
            --nexa-danger: #dc2626;
            --nexa-bg: #f8fafc;
        }

        body {
            background: var(--nexa-bg);
            font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', Roboto, Arial, sans-serif;
            color: var(--nexa-strong);
        }

        main.main-with-sidebar {
            min-height: 100vh;
            background: transparent;
        }

        .page-header {
            display: flex;
            flex-direction: column;
            gap: .5rem;
        }

        .metric-card {
            border: 1px solid var(--nexa-border);
            border-radius: 18px;
            background: var(--nexa-surface);
            padding: 1.25rem;
            box-shadow: 0 20px 25px -15px rgba(15, 23, 42, 0.25);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .metric-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 20px 35px -20px rgba(15, 23, 42, 0.35);
        }

        .metric-label {
            font-size: .875rem;
            color: var(--nexa-muted);
            font-weight: 500;
        }

        .metric-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--nexa-strong);
        }

        .status-badge {
            border-radius: 999px;
            font-size: .75rem;
            text-transform: uppercase;
            letter-spacing: .04em;
        }

        .status-active {
            background: rgba(22, 163, 74, 0.15);
            color: var(--nexa-success);
        }

        .status-maintenance {
            background: rgba(245, 158, 11, 0.15);
            color: var(--nexa-warning);
        }

        .status-passive,
        .status-cancelled {
            background: rgba(100, 116, 139, 0.15);
            color: var(--nexa-muted);
        }

        .status-in_transit {
            background: rgba(37, 99, 235, 0.15);
            color: var(--nexa-accent);
        }

        .status-completed {
            background: rgba(22, 163, 74, 0.12);
            color: var(--nexa-success);
        }

        .status-planned {
            background: rgba(37, 99, 235, 0.12);
            color: var(--nexa-accent);
        }

        .status-in_progress {
            background: rgba(245, 158, 11, 0.15);
            color: var(--nexa-warning);
        }

        .table thead th {
            font-size: .75rem;
            letter-spacing: .03em;
            text-transform: uppercase;
            color: var(--nexa-muted);
            border-bottom: 1px solid var(--nexa-border);
        }

        .table tbody td {
            vertical-align: middle;
        }

        .nav-views .nav-link {
            border-radius: 999px;
        }

        .nav-views .nav-link.active {
            background-color: var(--nexa-accent);
            color: #fff;
        }

        .card-shadow {
            border-radius: 18px;
            border: 1px solid var(--nexa-border);
            background: var(--nexa-surface);
            box-shadow: 0 24px 45px -25px rgba(15, 23, 42, 0.4);
        }

        .empty-state {
            padding: 2rem;
            text-align: center;
            color: var(--nexa-muted);
        }

        .filters-form .form-control,
        .filters-form .form-select {
            border-radius: 12px;
        }
    </style>
</head>
<body>
<?php require_once __DIR__ . '/../partials/flash.php'; ?>
<div class="d-flex">
    <?php require_once __DIR__ . '/../component/sidebar.php'; ?>
    <main class="main-with-sidebar flex-grow-1 p-4">
        <div class="container-fluid">
            <div class="row justify-content-center">
                <div class="col-12 col-xxl-10">
                    <header class="page-header mb-4">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                            <div>
                                <h1 class="h3 mb-1">Araç Yönetimi</h1>
                                <p class="text-muted mb-0">Filonuzun durumunu izleyin, sevkiyat rotalarını planlayın ve bakım süreçlerini yönetin.</p>
                            </div>
                            <div class="d-flex flex-wrap gap-2">
                                <a href="vehicle.php?view=maintenance" class="btn btn-primary d-inline-flex align-items-center gap-2">
                                    <i class="bi bi-wrench"></i>
                                    Bakım Planla
                                </a>
                                <a href="vehicle/add.php" class="btn btn-outline-primary d-inline-flex align-items-center gap-2">
                                    <i class="bi bi-plus-lg"></i>
                                    Yeni Araç
                                </a>
                            </div>
                        </div>
                        <nav class="nav nav-pills nav-views mt-3">
                            <a class="nav-link <?= $view === 'overview' ? 'active' : ''; ?>" href="vehicle.php">Genel Bakış</a>
                            <a class="nav-link <?= $view === 'routes' ? 'active' : ''; ?>" href="vehicle.php?view=routes">Güzergahlar</a>
                            <a class="nav-link <?= $view === 'maintenance' ? 'active' : ''; ?>" href="vehicle.php?view=maintenance">Bakım</a>
                            <a class="nav-link <?= $view === 'shipments' ? 'active' : ''; ?>" href="vehicle.php?view=shipments">Sevkiyatlar</a>
                        </nav>
                    </header>

                    <section class="row g-3 mb-4">
                        <div class="col-12 col-sm-6 col-xl-3">
                            <div class="metric-card h-100">
                                <span class="metric-label">Toplam Araç</span>
                                <span class="metric-value"><?= number_format($stats['totalVehicles']); ?></span>
                                <small class="text-muted">Filonuzdaki kayıtlı araç sayısı</small>
                            </div>
                        </div>
                        <div class="col-12 col-sm-6 col-xl-3">
                            <div class="metric-card h-100">
                                <span class="metric-label">Aktif Kullanım</span>
                                <span class="metric-value"><?= number_format($stats['activeVehicles']); ?></span>
                                <small class="text-muted">Şu an sahada çalışan araçlar</small>
                            </div>
                        </div>
                        <div class="col-12 col-sm-6 col-xl-3">
                            <div class="metric-card h-100">
                                <span class="metric-label">Bakım Yaklaşan</span>
                                <span class="metric-value"><?= number_format($stats['maintenanceDue']); ?></span>
                                <small class="text-muted">7 gün içinde bakım planı</small>
                            </div>
                        </div>
                        <div class="col-12 col-sm-6 col-xl-3">
                            <div class="metric-card h-100">
                                <span class="metric-label">Bugünkü Güzergah</span>
                                <span class="metric-value"><?= number_format($stats['routesToday']); ?></span>
                                <small class="text-muted">Bugün planlı sevkiyat rotaları</small>
                            </div>
                        </div>
                    </section>

                    <?php if ($view === 'overview'): ?>
                        <section class="card-shadow p-0 mb-4">
                            <div class="p-4 border-bottom">
                                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                                    <div>
                                        <h2 class="h5 mb-1">Araç Envanteri</h2>
                                        <p class="text-muted mb-0">Filonuzdaki tüm araçların güncel durumu</p>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge text-bg-light"><?= count($vehicleList); ?> kayıt</span>
                                        <a href="vehicle/add.php" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-2">
                                            <i class="bi bi-plus-lg"></i>
                                            Yeni Araç Ekle
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>Plaka</th>
                                            <th>Tip</th>
                                            <th>Marka / Model</th>
                                            <th>Kapasite</th>
                                            <th>Durum</th>
                                            <th>Son Rota</th>
                                            <th>Bakım</th>
                                            <th class="text-end">İşlemler</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php if ($vehicleList === []): ?>
                                        <tr>
                                            <td colspan="8" class="empty-state">
                                                Henüz araç kaydı bulunmuyor. Filonuzu oluşturmak için
                                                <a class="link-primary" href="vehicle/add.php">yeni kayıt ekleyin</a>.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($vehicleList as $vehicle): ?>
                                            <?php
                                            $statusClass = 'status-badge status-' . str_replace(' ', '_', strtolower((string) $vehicle['status']));
                                            $capacity = [];
                                            if (!empty($vehicle['capacity_weight'])) {
                                                $capacity[] = number_format((float) $vehicle['capacity_weight'], 0, ',', '.') . ' kg';
                                            }
                                            if (!empty($vehicle['capacity_volume'])) {
                                                $capacity[] = number_format((float) $vehicle['capacity_volume'], 1, ',', '.') . ' m³';
                                            }
                                            $capacityText = $capacity !== [] ? implode(' • ', $capacity) : '<span class="text-muted">-</span>';

                                            $maintenanceInfo = [];
                                            $lastService = formatDate($vehicle['last_service_at'] ?? null);
                                            $nextService = formatDate($vehicle['next_service_at'] ?? null);

                                            if ($lastService !== null) {
                                                $maintenanceInfo[] = 'Son: ' . e($lastService);
                                            }

                                            if ($nextService !== null) {
                                                $maintenanceInfo[] = 'Sıradaki: ' . e($nextService);
                                            }
                                            $maintenanceText = $maintenanceInfo !== [] ? implode('<br>', $maintenanceInfo) : '<span class="text-muted">Planlanmadı</span>';

                                            $lastRoute = null;
                                            $lastRouteDate = formatDate($vehicle['last_route_date'] ?? null);
                                            if ($lastRouteDate !== null) {
                                                $lastRoute = $lastRouteDate;
                                                if (!empty($vehicle['last_route_status'])) {
                                                    $lastRoute .= ' · ' . e(ucfirst(str_replace('_', ' ', (string) $vehicle['last_route_status'])));
                                                }
                                            }

                                            ?>
                                            <tr>
                                                <td class="fw-semibold"><?= e((string) $vehicle['plate_number']); ?></td>
                                                <td><?= e((string) $vehicle['type']); ?></td>
                                                <td><?= e(trim(($vehicle['brand'] ?? '') . ' ' . ($vehicle['model'] ?? ''))); ?></td>
                                                <td><?= $capacityText; ?></td>
                                                <td>
                                                    <span class="status-badge <?= e($statusClass); ?>">
                                                        <?= e(ucfirst(str_replace('_', ' ', (string) $vehicle['status']))); ?>
                                                    </span>
                                                </td>
                                                <td><?= $lastRoute !== null ? e($lastRoute) : '<span class="text-muted">Veri yok</span>'; ?></td>
                                                <td><?= $maintenanceText; ?></td>
                                                <td class="text-end">
                                                    <div class="btn-group btn-group-sm" role="group" aria-label="Araç işlemleri">
                                                        <a class="btn btn-outline-primary" href="vehicle/edit.php?id=<?= urlencode((string) $vehicle['id']); ?>">Düzenle</a>
                                                        <a class="btn btn-outline-danger" href="vehicle/delete.php?id=<?= urlencode((string) $vehicle['id']); ?>">Sil</a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </section>

                        <section class="card-shadow p-0">
                            <div class="p-4 border-bottom">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h2 class="h5 mb-1">Son Bakım Kayıtları</h2>
                                        <p class="text-muted mb-0">Filonuzdaki kritik bakım hareketlerini takip edin</p>
                                    </div>
                                    <a href="vehicle.php?view=maintenance" class="btn btn-sm btn-outline-primary">Tümünü Gör</a>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>Plaka</th>
                                            <th>Bakım Türü</th>
                                            <th>Tarih</th>
                                            <th>Durum</th>
                                            <th>Sonraki Tarih</th>
                                            <th>Maliyet</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php if ($recentMaintenance === []): ?>
                                        <tr>
                                            <td colspan="6" class="empty-state">Henüz bakım kaydı bulunmuyor.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($recentMaintenance as $maintenance): ?>
                                            <?php
                                                $maintenanceDate = formatDate($maintenance['maintenance_date'] ?? null);
                                                $maintenanceNext = formatDate($maintenance['next_due_date'] ?? null);
                                            ?>
                                            <tr>
                                                <td class="fw-semibold"><?= e((string) $maintenance['plate_number']); ?></td>
                                                <td><?= e((string) $maintenance['maintenance_type']); ?></td>
                                                <td><?= $maintenanceDate !== null ? e($maintenanceDate) : '<span class="text-muted">-</span>'; ?></td>
                                                <td><span class="status-badge status-<?= e(str_replace(' ', '_', strtolower((string) $maintenance['status']))); ?>"><?= e(ucfirst(str_replace('_', ' ', (string) $maintenance['status']))); ?></span></td>
                                                <td><?= $maintenanceNext !== null ? e($maintenanceNext) : '<span class="text-muted">Belirsiz</span>'; ?></td>
                                                <td><?= !empty($maintenance['cost']) ? number_format((float) $maintenance['cost'], 2, ',', '.') . ' ₺' : '<span class="text-muted">-</span>'; ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </section>
                    <?php elseif ($view === 'routes'): ?>
                        <section class="card-shadow p-0">
                            <div class="p-4 border-bottom">
                                <div class="d-flex flex-column flex-lg-row justify-content-between gap-3">
                                    <div>
                                        <h2 class="h5 mb-1">Planlanan Güzergahlar</h2>
                                        <p class="text-muted mb-0">Araçlar için tanımlanan sevkiyat güzergahlarını inceleyin.</p>
                                    </div>
                                    <form method="get" class="row g-2 align-items-end filters-form">
                                        <input type="hidden" name="view" value="routes">
                                        <div class="col-12 col-sm-6 col-lg-auto">
                                            <label for="route-date" class="form-label">Tarih</label>
                                            <input type="date" class="form-control" id="route-date" name="date" value="<?= e((string) ($_GET['date'] ?? '')); ?>">
                                        </div>
                                        <div class="col-12 col-sm-6 col-lg-auto">
                                            <label for="route-status" class="form-label">Durum</label>
                                            <select class="form-select" id="route-status" name="status">
                                                <option value="">Hepsi</option>
                                                <?php foreach (['planned' => 'Planlandı', 'in_transit' => 'Yolda', 'completed' => 'Tamamlandı', 'cancelled' => 'İptal'] as $value => $label): ?>
                                                    <option value="<?= e($value); ?>" <?= isset($_GET['status']) && $_GET['status'] === $value ? 'selected' : ''; ?>><?= e($label); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-12 col-lg-auto">
                                            <button type="submit" class="btn btn-primary w-100">Filtrele</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>Tarih</th>
                                            <th>Plaka</th>
                                            <th>Başlangıç</th>
                                            <th>Varış</th>
                                            <th>Çıkış</th>
                                            <th>Varış</th>
                                            <th>Durum</th>
                                            <th>Yük Özeti</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php if ($routeList === []): ?>
                                        <tr>
                                            <td colspan="8" class="empty-state">Seçilen kriterlere uygun rota kaydı bulunamadı.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($routeList as $route): ?>
                                            <?php
                                                $routeDate = formatDate($route['route_date'] ?? null);
                                                $departureTime = formatTime($route['departure_time'] ?? null);
                                                $arrivalTime = formatTime($route['arrival_time'] ?? null);
                                            ?>
                                            <tr>
                                                <td><?= $routeDate !== null ? e($routeDate) : '<span class="text-muted">-</span>'; ?></td>
                                                <td class="fw-semibold"><?= e((string) $route['plate_number']); ?></td>
                                                <td><?= e((string) $route['origin']); ?></td>
                                                <td><?= e((string) $route['destination']); ?></td>
                                                <td><?= $departureTime !== null ? e($departureTime) : '<span class="text-muted">-</span>'; ?></td>
                                                <td><?= $arrivalTime !== null ? e($arrivalTime) : '<span class="text-muted">-</span>'; ?></td>
                                                <td><span class="status-badge status-<?= e(str_replace(' ', '_', strtolower((string) $route['status']))); ?>"><?= e(ucfirst(str_replace('_', ' ', (string) $route['status']))); ?></span></td>
                                                <td><?= !empty($route['cargo_summary']) ? e((string) $route['cargo_summary']) : '<span class="text-muted">Belirtilmemiş</span>'; ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </section>
                    <?php elseif ($view === 'maintenance'): ?>
                        <section class="card-shadow p-0">
                            <div class="p-4 border-bottom">
                                <div class="d-flex flex-column flex-lg-row justify-content-between gap-3">
                                    <div>
                                        <h2 class="h5 mb-1">Bakım Planı</h2>
                                        <p class="text-muted mb-0">Araç bakım kayıtlarını ve yaklaşan servisleri görüntüleyin.</p>
                                    </div>
                                    <form method="get" class="row g-2 align-items-end filters-form">
                                        <input type="hidden" name="view" value="maintenance">
                                        <div class="col-12 col-sm-6 col-lg-auto">
                                            <label for="maintenance-status" class="form-label">Durum</label>
                                            <select class="form-select" id="maintenance-status" name="status">
                                                <option value="">Hepsi</option>
                                                <?php foreach (['planned' => 'Planlandı', 'in_progress' => 'Devam Ediyor', 'completed' => 'Tamamlandı'] as $value => $label): ?>
                                                    <option value="<?= e($value); ?>" <?= isset($_GET['status']) && $_GET['status'] === $value ? 'selected' : ''; ?>><?= e($label); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-12 col-lg-auto">
                                            <button type="submit" class="btn btn-primary w-100">Filtrele</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>Plaka</th>
                                            <th>Bakım Türü</th>
                                            <th>Tarih</th>
                                            <th>Durum</th>
                                            <th>Servis</th>
                                            <th>Maliyet</th>
                                            <th>Sonraki Tarih</th>
                                            <th>Açıklama</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php if ($recentMaintenance === []): ?>
                                        <tr>
                                            <td colspan="8" class="empty-state">Seçilen kriterlere uygun bakım kaydı bulunamadı.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($recentMaintenance as $maintenance): ?>
                                            <?php
                                                $maintenanceDate = formatDate($maintenance['maintenance_date'] ?? null);
                                                $maintenanceNext = formatDate($maintenance['next_due_date'] ?? null);
                                            ?>
                                            <tr>
                                                <td class="fw-semibold"><?= e((string) $maintenance['plate_number']); ?></td>
                                                <td><?= e((string) $maintenance['maintenance_type']); ?></td>
                                                <td><?= $maintenanceDate !== null ? e($maintenanceDate) : '<span class="text-muted">-</span>'; ?></td>
                                                <td><span class="status-badge status-<?= e(str_replace(' ', '_', strtolower((string) $maintenance['status']))); ?>"><?= e(ucfirst(str_replace('_', ' ', (string) $maintenance['status']))); ?></span></td>
                                                <td><?= !empty($maintenance['service_center']) ? e((string) $maintenance['service_center']) : '<span class="text-muted">-</span>'; ?></td>
                                                <td><?= !empty($maintenance['cost']) ? number_format((float) $maintenance['cost'], 2, ',', '.') . ' ₺' : '<span class="text-muted">-</span>'; ?></td>
                                                <td><?= $maintenanceNext !== null ? e($maintenanceNext) : '<span class="text-muted">Belirsiz</span>'; ?></td>
                                                <td><?= !empty($maintenance['description']) ? e((string) $maintenance['description']) : '<span class="text-muted">Açıklama yok</span>'; ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </section>
                    <?php elseif ($view === 'shipments'): ?>
                        <section class="card-shadow p-0">
                            <div class="p-4 border-bottom">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h2 class="h5 mb-1">Sevkiyat Atamaları</h2>
                                        <p class="text-muted mb-0">Araçlarınıza atanmış sevkiyatları görüntüleyin.</p>
                                    </div>
                                    <a href="vehicle.php?view=routes" class="btn btn-sm btn-outline-primary">Rotaları Aç</a>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>Kod</th>
                                            <th>Çıkış</th>
                                            <th>Varış</th>
                                            <th>Tarih</th>
                                            <th>Araç</th>
                                            <th>Durum</th>
                                            <th>Yük</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php if ($shipmentsError): ?>
                                        <tr>
                                            <td colspan="7" class="empty-state">
                                                Sevkiyat verisi alınamadı. Lütfen <code>shipments</code> tablosunun mevcut olduğundan emin olun.
                                            </td>
                                        </tr>
                                    <?php elseif ($shipments === []): ?>
                                        <tr>
                                            <td colspan="7" class="empty-state">Henüz araçlara atanmış sevkiyat kaydı bulunmuyor.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($shipments as $shipment): ?>
                                            <tr>
                                                <td class="fw-semibold">#<?= e((string) ($shipment['shipment_code'] ?? $shipment['id'])); ?></td>
                                                <td><?= !empty($shipment['origin']) ? e((string) $shipment['origin']) : '<span class="text-muted">-</span>'; ?></td>
                                                <td><?= !empty($shipment['destination']) ? e((string) $shipment['destination']) : '<span class="text-muted">-</span>'; ?></td>
                                                <td><?php $shipDate = formatDate($shipment['ship_date'] ?? null); ?><?= $shipDate !== null ? e($shipDate) : '<span class="text-muted">-</span>'; ?></td>
                                                <td><?= !empty($shipment['plate_number']) ? e((string) $shipment['plate_number']) : '<span class="text-muted">Atanmamış</span>'; ?></td>
                                                <td><span class="status-badge status-<?= e(str_replace(' ', '_', strtolower((string) ($shipment['status'] ?? 'planned')))); ?>"><?= e(ucfirst(str_replace('_', ' ', (string) ($shipment['status'] ?? 'planned')))); ?></span></td>
                                                <td><?= !empty($shipment['cargo_description']) ? e((string) $shipment['cargo_description']) : '<span class="text-muted">Belirtilmemiş</span>'; ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                    </tbody>
                                </table>
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
