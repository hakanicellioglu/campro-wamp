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

$maintenances = [];
$upcoming = [];
$statusCounts = [
    'planned' => 0,
    'in_progress' => 0,
    'completed' => 0,
];

try {
    $stmt = $pdo->query(
        'SELECT vm.id, vm.vehicle_id, vm.maintenance_date, vm.maintenance_type, vm.description,
                vm.mileage, vm.cost, vm.service_center, vm.next_due_date, vm.status,
                vm.created_at, vm.updated_at,
                v.plate_number, v.type AS vehicle_type
         FROM vehicle_maintenance vm
         INNER JOIN vehicles v ON v.id = vm.vehicle_id
         ORDER BY vm.maintenance_date DESC, vm.id DESC'
    );
    $maintenances = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $today = new DateTimeImmutable('today');

    foreach ($maintenances as $maintenance) {
        $status = strtolower((string)($maintenance['status'] ?? 'planned'));
        if (isset($statusCounts[$status])) {
            $statusCounts[$status]++;
        }

        $nextDue = $maintenance['next_due_date'] ?? null;
        if ($nextDue) {
            try {
                $date = new DateTimeImmutable((string) $nextDue);
                $diff = (int) $today->diff($date)->format('%r%a');
                if ($diff >= 0 && $diff <= 30) {
                    $upcoming[] = [
                        'maintenance' => $maintenance,
                        'days' => $diff,
                    ];
                }
            } catch (Throwable $e) {
                // tarih hatalı
            }
        }
    }
} catch (PDOException $e) {
    error_log('Maintenance fetch failed: ' . $e->getMessage());
    $maintenances = [];
}

usort($upcoming, static function (array $a, array $b): int {
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

$formatMoney = static function ($value): string {
    if ($value === null || $value === '') {
        return '—';
    }
    return number_format((float) $value, 2, ',', '.') . ' ₺';
};

$statusLabels = [
    'planned' => 'Planlandı',
    'in_progress' => 'Devam Ediyor',
    'completed' => 'Tamamlandı',
];

?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Araç Bakım Takvimi — Nexa</title>
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

        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            padding: .4rem .8rem;
            border-radius: 999px;
            font-size: .75rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .status-pill.planned { background: rgba(59, 130, 246, 0.15); color: #1d4ed8; }
        .status-pill.in_progress { background: rgba(249, 115, 22, 0.15); color: #c2410c; }
        .status-pill.completed { background: rgba(34, 197, 94, 0.15); color: #15803d; }

        .table thead {
            background: rgba(15, 23, 42, 0.02);
            text-transform: uppercase;
            font-size: .75rem;
            letter-spacing: 0.04em;
        }

        .timeline-item {
            border-left: 3px solid rgba(37, 99, 235, 0.3);
            padding-left: 1rem;
            margin-left: .5rem;
            margin-bottom: 1.5rem;
            position: relative;
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: -8px;
            top: .3rem;
            width: 12px;
            height: 12px;
            background: #2563eb;
            border-radius: 50%;
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
                    <h1 class="h3 mb-1">Araç Bakım Takvimi</h1>
                    <p class="text-muted mb-0">Tüm bakım aktivitelerini planlayın ve takip edin.</p>
                </div>
                <div class="btn-group" role="group">
                    <a href="vehicle.php" class="btn btn-outline-primary"><i class="bi bi-truck"></i> Araçlar</a>
                    <a href="vehicle_routes.php" class="btn btn-outline-primary"><i class="bi bi-signpost"></i> Güzergahlar</a>
                    <a href="vehicle_maintenance.php" class="btn btn-outline-primary active"><i class="bi bi-tools"></i> Bakım</a>
                    <a href="shipments.php" class="btn btn-outline-primary"><i class="bi bi-box-seam"></i> Sevkiyatlar</a>
                </div>
            </header>

            <div class="row g-4">
                <div class="col-12 col-lg-4">
                    <section class="card h-100">
                        <div class="card-body">
                            <h2 class="h5">Durum Özeti</h2>
                            <p class="text-muted small mb-4">Bakım çalışmalarının güncel durum dağılımı.</p>
                            <div class="d-flex flex-column gap-3">
                                <?php foreach ($statusCounts as $key => $count): ?>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="status-pill <?= e($key); ?>">
                                            <?php if ($key === 'planned'): ?><i class="bi bi-calendar-week"></i><?php endif; ?>
                                            <?php if ($key === 'in_progress'): ?><i class="bi bi-arrow-repeat"></i><?php endif; ?>
                                            <?php if ($key === 'completed'): ?><i class="bi bi-check-circle"></i><?php endif; ?>
                                            <?= e($statusLabels[$key]); ?>
                                        </span>
                                        <strong><?= $count; ?></strong>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </section>

                    <?php if ($upcoming !== []): ?>
                        <section class="card mt-4">
                            <div class="card-body">
                                <h2 class="h5">Yaklaşan Bakımlar</h2>
                                <p class="text-muted small">Önümüzdeki 30 gün içinde planlanan bakımlar.</p>
                                <?php foreach ($upcoming as $item): ?>
                                    <?php $maintenance = $item['maintenance']; ?>
                                    <div class="timeline-item">
                                        <div class="fw-semibold text-primary"><?= e($maintenance['plate_number']); ?> · <?= $formatDate($maintenance['next_due_date'] ?? null); ?></div>
                                        <div class="small text-muted"><?= e($maintenance['maintenance_type']); ?></div>
                                        <div class="small">Kalan süre: <?= $item['days']; ?> gün</div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    <?php endif; ?>
                </div>

                <div class="col-12 col-lg-8">
                    <section class="card">
                        <div class="card-header bg-white">
                            <h2 class="h5 mb-0">Bakım Kayıtları</h2>
                        </div>
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead>
                                <tr>
                                    <th>Tarih</th>
                                    <th>Araç</th>
                                    <th>Detay</th>
                                    <th>Maliyet</th>
                                    <th>Sonraki</th>
                                    <th>Durum</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php if ($maintenances === []): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">Bakım kaydı bulunamadı.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($maintenances as $maintenance): ?>
                                        <?php $statusKey = strtolower((string)($maintenance['status'] ?? 'planned')); ?>
                                        <tr>
                                            <td>
                                                <div class="fw-semibold"><?= $formatDate($maintenance['maintenance_date'] ?? null); ?></div>
                                                <div class="small text-muted">KM: <?= e($maintenance['mileage'] !== null ? number_format((int)$maintenance['mileage'], 0, ',', '.') : '—'); ?></div>
                                            </td>
                                            <td>
                                                <div class="fw-semibold"><?= e($maintenance['plate_number']); ?></div>
                                                <div class="small text-muted"><?= e($maintenance['vehicle_type']); ?></div>
                                            </td>
                                            <td class="small">
                                                <div class="fw-semibold mb-1"><?= e($maintenance['maintenance_type']); ?></div>
                                                <div class="text-muted">Servis: <?= e($maintenance['service_center'] ?? '—'); ?></div>
                                                <?php if (!empty($maintenance['description'])): ?>
                                                    <div class="text-secondary fst-italic mt-1"><?= e($maintenance['description']); ?></div>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= $formatMoney($maintenance['cost']); ?></td>
                                            <td><?= $formatDate($maintenance['next_due_date'] ?? null); ?></td>
                                            <td>
                                                <span class="status-pill <?= e($statusKey); ?>"><?= e($statusLabels[$statusKey] ?? ucfirst($statusKey)); ?></span>
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
