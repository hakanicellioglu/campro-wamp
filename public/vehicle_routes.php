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

$view = 'routes';

$routes = [];
$upcomingRoutes = [];
$completedCount = 0;
$plannedCount = 0;
$inTransitCount = 0;

try {
    $stmt = $pdo->query(
        'SELECT vr.id, vr.vehicle_id, vr.route_date, vr.origin, vr.destination, vr.departure_time, vr.arrival_time,
                vr.cargo_summary, vr.status, vr.notes, vr.created_at, vr.updated_at,
                v.plate_number, v.type AS vehicle_type
         FROM vehicle_routes vr
         INNER JOIN vehicles v ON v.id = vr.vehicle_id
         ORDER BY vr.route_date DESC, vr.departure_time DESC'
    );
    $routes = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $today = new DateTimeImmutable('today');

    foreach ($routes as $route) {
        $status = strtolower((string)($route['status'] ?? 'planned'));
        switch ($status) {
            case 'completed':
                $completedCount++;
                break;
            case 'in_transit':
                $inTransitCount++;
                break;
            default:
                $plannedCount++;
                break;
        }

        $routeDate = $route['route_date'] ?? null;
        if ($routeDate) {
            try {
                $date = new DateTimeImmutable((string) $routeDate);
                $diff = (int) $today->diff($date)->format('%r%a');
                if ($diff >= 0 && $diff <= 7) {
                    $upcomingRoutes[] = [
                        'route' => $route,
                        'days' => $diff,
                    ];
                }
            } catch (Throwable $e) {
                // geçersiz tarih, yoksay
            }
        }
    }
} catch (PDOException $e) {
    error_log('Vehicle routes fetch failed: ' . $e->getMessage());
    $routes = [];
}

usort($upcomingRoutes, static function (array $a, array $b): int {
    return $a['days'] <=> $b['days'];
});

function e(?string $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

$formatDate = static function (?string $date): string {
    if (!$date) {
        return '—';
    }
    try {
        return (new DateTimeImmutable($date))->format('d.m.Y');
    } catch (Throwable $e) {
        return (string) $date;
    }
};

$formatTime = static function (?string $time): string {
    if (!$time) {
        return '—';
    }
    try {
        return (new DateTimeImmutable($time))->format('H:i');
    } catch (Throwable $e) {
        return (string) $time;
    }
};

$statusLabels = [
    'planned' => 'Planlandı',
    'in_transit' => 'Yolda',
    'completed' => 'Tamamlandı',
    'cancelled' => 'İptal Edildi',
];

?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Araç Güzergahları — Nexa</title>
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
            box-shadow: 0 12px 32px rgba(15, 23, 42, 0.08);
        }

        .badge-status {
            border-radius: 999px;
            font-size: 0.75rem;
            padding: 0.4rem 0.75rem;
        }

        .badge-status.planned { background: rgba(59, 130, 246, 0.16); color: #2563eb; }
        .badge-status.in_transit { background: rgba(14, 165, 233, 0.16); color: #0284c7; }
        .badge-status.completed { background: rgba(34, 197, 94, 0.16); color: #16a34a; }
        .badge-status.cancelled { background: rgba(239, 68, 68, 0.16); color: #dc2626; }

        .timeline {
            position: relative;
            padding-left: 1.5rem;
        }

        .timeline::before {
            content: '';
            position: absolute;
            left: .5rem;
            top: 0;
            bottom: 0;
            width: 2px;
            background: linear-gradient(180deg, rgba(99,102,241,0.4), rgba(14,165,233,0.4));
        }

        .timeline-item {
            position: relative;
            margin-bottom: 1.5rem;
            padding-left: 1.5rem;
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: -.15rem;
            top: .35rem;
            width: 10px;
            height: 10px;
            background: #6366f1;
            border-radius: 50%;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.2);
        }

        .table thead {
            background: rgba(15, 23, 42, 0.02);
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.04em;
        }
    </style>
</head>
<body>
<?php require_once __DIR__ . '/../partials/flash.php'; ?>
<div class="d-flex">
    <?php require_once __DIR__ . '/../component/sidebar.php'; ?>
    <main class="main-with-sidebar flex-grow-1 p-4">
        <div class="container-fluid">
            <header class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
                <div>
                    <h1 class="h3 mb-1">Araç Güzergahları</h1>
                    <p class="text-muted mb-0">Planlanan ve gerçekleşmiş tüm güzergahları takip edin.</p>
                </div>
                <div class="btn-group" role="group">
                    <a href="vehicle.php" class="btn btn-outline-primary"><i class="bi bi-truck"></i> Araçlar</a>
                    <a href="vehicle_routes.php" class="btn btn-outline-primary active"><i class="bi bi-signpost"></i> Güzergahlar</a>
                    <a href="vehicle_maintenance.php" class="btn btn-outline-primary"><i class="bi bi-tools"></i> Bakım</a>
                    <a href="shipments.php" class="btn btn-outline-primary"><i class="bi bi-box-seam"></i> Sevkiyatlar</a>
                </div>
            </header>

            <div class="row g-4">
                <div class="col-12 col-xl-4">
                    <section class="card h-100">
                        <div class="card-body">
                            <h2 class="h5">Durum Özeti</h2>
                            <p class="text-muted small mb-4">Filonuzun güzergah durumlarını inceleyin.</p>
                            <div class="d-flex flex-column gap-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="badge-status planned">Planlandı</span>
                                    <strong><?= $plannedCount; ?></strong>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="badge-status in_transit">Yolda</span>
                                    <strong><?= $inTransitCount; ?></strong>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="badge-status completed">Tamamlandı</span>
                                    <strong><?= $completedCount; ?></strong>
                                </div>
                            </div>
                        </div>
                    </section>

                    <?php if ($upcomingRoutes !== []): ?>
                        <section class="card mt-4">
                            <div class="card-body">
                                <h2 class="h5">Yaklaşan Güzergahlar</h2>
                                <p class="text-muted small">Önümüzdeki 7 gün içindeki planlar.</p>
                                <div class="timeline">
                                    <?php foreach ($upcomingRoutes as $item): ?>
                                        <?php $route = $item['route']; ?>
                                        <div class="timeline-item">
                                            <div class="fw-semibold text-primary"><?= $formatDate($route['route_date'] ?? null); ?> · <?= e($route['plate_number']); ?></div>
                                            <div class="small text-muted"><?= e($route['origin']); ?> → <?= e($route['destination']); ?></div>
                                            <div class="small">Kalan süre: <?= $item['days']; ?> gün</div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </section>
                    <?php endif; ?>
                </div>

                <div class="col-12 col-xl-8">
                    <section class="card">
                        <div class="card-header bg-white">
                            <h2 class="h5 mb-0">Güzergah Listesi</h2>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                <tr>
                                    <th>Tarih</th>
                                    <th>Araç</th>
                                    <th>Güzergah</th>
                                    <th>Süre</th>
                                    <th>Durum</th>
                                    <th>Yük</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php if ($routes === []): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">Kayıtlı güzergah bulunamadı.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($routes as $route): ?>
                                        <?php $statusKey = strtolower((string)($route['status'] ?? 'planned')); ?>
                                        <tr>
                                            <td>
                                                <div class="fw-semibold"><?= $formatDate($route['route_date'] ?? null); ?></div>
                                                <div class="small text-muted">Çıkış: <?= $formatTime($route['departure_time'] ?? null); ?> · Varış: <?= $formatTime($route['arrival_time'] ?? null); ?></div>
                                            </td>
                                            <td>
                                                <div class="fw-semibold"><?= e($route['plate_number']); ?></div>
                                                <div class="small text-muted"><?= e($route['vehicle_type']); ?></div>
                                            </td>
                                            <td>
                                                <div><?= e($route['origin']); ?> → <?= e($route['destination']); ?></div>
                                            </td>
                                            <td class="small text-muted">
                                                Oluşturma: <?= $formatDate($route['created_at'] ?? null); ?><br>
                                                Güncelleme: <?= $formatDate($route['updated_at'] ?? null); ?>
                                            </td>
                                            <td>
                                                <span class="badge-status <?= e($statusKey); ?>"><?= e($statusLabels[$statusKey] ?? ucfirst($statusKey)); ?></span>
                                            </td>
                                            <td class="small text-muted">
                                                <?= e($route['cargo_summary'] ?? '—'); ?>
                                                <?php if (!empty($route['notes'])): ?>
                                                    <div class="text-secondary fst-italic"><?= e($route['notes']); ?></div>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
