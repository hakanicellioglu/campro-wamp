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

$shipments = [];
$statusCounts = [
    'planned' => 0,
    'in_transit' => 0,
    'delayed' => 0,
    'delivered' => 0,
    'cancelled' => 0,
];
$upcoming = [];

try {
    $stmt = $pdo->query(
        'SELECT s.id, s.shipment_code, s.ship_date, s.origin, s.destination, s.status,
                s.cargo_description, s.vehicle_id, s.assigned_driver, s.notes,
                s.created_at, s.updated_at,
                v.plate_number AS vehicle_plate, v.type AS vehicle_type
         FROM shipments s
         LEFT JOIN vehicles v ON v.id = s.vehicle_id
         ORDER BY s.ship_date DESC, s.created_at DESC'
    );
    $shipments = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $today = new DateTimeImmutable('today');

    foreach ($shipments as $shipment) {
        $status = strtolower((string)($shipment['status'] ?? 'planned'));
        if (isset($statusCounts[$status])) {
            $statusCounts[$status]++;
        }

        $shipDate = $shipment['ship_date'] ?? null;
        if ($shipDate) {
            try {
                $date = new DateTimeImmutable((string) $shipDate);
                $diff = (int) $today->diff($date)->format('%r%a');
                if ($diff >= 0 && $diff <= 14) {
                    $upcoming[] = [
                        'shipment' => $shipment,
                        'days' => $diff,
                    ];
                }
            } catch (Throwable $e) {
                // geçersiz tarih
            }
        }
    }
} catch (PDOException $e) {
    error_log('Shipments fetch failed: ' . $e->getMessage());
    $shipments = [];
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

$statusLabels = [
    'planned' => 'Planlandı',
    'in_transit' => 'Yolda',
    'delayed' => 'Gecikti',
    'delivered' => 'Teslim Edildi',
    'cancelled' => 'İptal',
];

?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Sevkiyat Yönetimi — Nexa</title>
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

        .status-tag {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .35rem .75rem;
            border-radius: 999px;
            font-size: .75rem;
            text-transform: uppercase;
        }

        .status-tag.planned { background: rgba(59, 130, 246, 0.16); color: #2563eb; }
        .status-tag.in_transit { background: rgba(14, 165, 233, 0.16); color: #0284c7; }
        .status-tag.delayed { background: rgba(250, 204, 21, 0.2); color: #b45309; }
        .status-tag.delivered { background: rgba(34, 197, 94, 0.16); color: #15803d; }
        .status-tag.cancelled { background: rgba(239, 68, 68, 0.16); color: #dc2626; }

        .table thead {
            background: rgba(15, 23, 42, 0.02);
            font-size: .75rem;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .upcoming-card {
            border-left: 4px solid #6366f1;
            border-radius: .75rem;
            background: white;
            padding: 1rem;
            box-shadow: 0 12px 24px rgba(99, 102, 241, 0.12);
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
                    <h1 class="h3 mb-1">Sevkiyat Yönetimi</h1>
                    <p class="text-muted mb-0">Operasyonel sevkiyat süreçlerini uçtan uca takip edin.</p>
                </div>
                <div class="btn-group" role="group">
                    <a href="vehicle.php" class="btn btn-outline-primary"><i class="bi bi-truck"></i> Araçlar</a>
                    <a href="vehicle_routes.php" class="btn btn-outline-primary"><i class="bi bi-signpost"></i> Güzergahlar</a>
                    <a href="vehicle_maintenance.php" class="btn btn-outline-primary"><i class="bi bi-tools"></i> Bakım</a>
                    <a href="shipments.php" class="btn btn-outline-primary active"><i class="bi bi-box-seam"></i> Sevkiyatlar</a>
                </div>
            </header>

            <div class="row g-4">
                <div class="col-12 col-xl-4">
                    <section class="card h-100">
                        <div class="card-body">
                            <h2 class="h5">Durum Özeti</h2>
                            <p class="text-muted small mb-4">Sevkiyat durumlarının dağılımı.</p>
                            <div class="d-flex flex-column gap-3">
                                <?php foreach ($statusCounts as $key => $count): ?>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="status-tag <?= e($key); ?>">
                                            <?php if ($key === 'planned'): ?><i class="bi bi-calendar-event"></i><?php endif; ?>
                                            <?php if ($key === 'in_transit'): ?><i class="bi bi-truck"></i><?php endif; ?>
                                            <?php if ($key === 'delayed'): ?><i class="bi bi-exclamation-triangle"></i><?php endif; ?>
                                            <?php if ($key === 'delivered'): ?><i class="bi bi-check-circle"></i><?php endif; ?>
                                            <?php if ($key === 'cancelled'): ?><i class="bi bi-x-circle"></i><?php endif; ?>
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
                                <h2 class="h5">Yaklaşan Sevkiyatlar</h2>
                                <p class="text-muted small">Önümüzdeki 14 gün içindeki sevkiyat planları.</p>
                                <div class="d-flex flex-column gap-3">
                                    <?php foreach ($upcoming as $item): ?>
                                        <?php $shipment = $item['shipment']; ?>
                                        <div class="upcoming-card">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="fw-semibold text-primary"><?= e($shipment['shipment_code']); ?></span>
                                                <span class="badge bg-light text-dark"><?= $item['days']; ?> gün</span>
                                            </div>
                                            <div class="small text-muted mt-1"><?= $formatDate($shipment['ship_date'] ?? null); ?> · <?= e($shipment['origin']); ?> → <?= e($shipment['destination']); ?></div>
                                            <?php if (!empty($shipment['vehicle_plate'])): ?>
                                                <div class="small mt-2"><i class="bi bi-truck-front me-2"></i><?= e($shipment['vehicle_plate']); ?> · <?= e($shipment['vehicle_type']); ?></div>
                                            <?php endif; ?>
                                            <?php if (!empty($shipment['assigned_driver'])): ?>
                                                <div class="small text-secondary mt-1"><i class="bi bi-person"></i> <?= e($shipment['assigned_driver']); ?></div>
                                            <?php endif; ?>
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
                            <h2 class="h5 mb-0">Sevkiyat Listesi</h2>
                        </div>
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead>
                                <tr>
                                    <th>Kod</th>
                                    <th>Tarih</th>
                                    <th>Güzergah</th>
                                    <th>Araç / Sürücü</th>
                                    <th>Durum</th>
                                    <th>Detay</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php if ($shipments === []): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">Sevkiyat kaydı bulunamadı.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($shipments as $shipment): ?>
                                        <?php $statusKey = strtolower((string)($shipment['status'] ?? 'planned')); ?>
                                        <tr>
                                            <td class="fw-semibold">
                                                <?= e($shipment['shipment_code']); ?><br>
                                                <span class="small text-muted">Oluşturma: <?= $formatDate($shipment['created_at'] ?? null); ?></span>
                                            </td>
                                            <td>
                                                <div><?= $formatDate($shipment['ship_date'] ?? null); ?></div>
                                            </td>
                                            <td class="small text-muted">
                                                <?= e($shipment['origin']); ?> → <?= e($shipment['destination']); ?>
                                            </td>
                                            <td class="small text-muted">
                                                <?php if (!empty($shipment['vehicle_plate'])): ?>
                                                    <div><i class="bi bi-truck-front me-2"></i><?= e($shipment['vehicle_plate']); ?> · <?= e($shipment['vehicle_type']); ?></div>
                                                <?php else: ?>
                                                    <div class="text-muted">Araç atanmamış</div>
                                                <?php endif; ?>
                                                <?php if (!empty($shipment['assigned_driver'])): ?>
                                                    <div><i class="bi bi-person me-2"></i><?= e($shipment['assigned_driver']); ?></div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="status-tag <?= e($statusKey); ?>"><?= e($statusLabels[$statusKey] ?? ucfirst($statusKey)); ?></span>
                                            </td>
                                            <td class="small text-muted">
                                                <?= e($shipment['cargo_description'] ?? '—'); ?>
                                                <?php if (!empty($shipment['notes'])): ?>
                                                    <div class="text-secondary fst-italic mt-1"><?= e($shipment['notes']); ?></div>
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
