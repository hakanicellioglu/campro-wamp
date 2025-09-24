<?php
// dashboard.php — header.php + sidebar.php dahil edilerek yapılandırıldı
declare(strict_types=1);

session_start();

if (empty($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit;
}

require_once __DIR__ . '/../config.php';

$userId = (int) ($_SESSION['user_id'] ?? 0);
$username = 'Kullanıcı';

try {
    $userStmt = $pdo->prepare('SELECT username FROM users WHERE id = :id LIMIT 1');
    $userStmt->execute([':id' => $userId]);
    $userRow = $userStmt->fetch(PDO::FETCH_ASSOC);
    if ($userRow && isset($userRow['username'])) {
        $username = (string) $userRow['username'];
    }
} catch (PDOException $e) {
    error_log('Dashboard username fetch failed: ' . $e->getMessage());
}

$stats = [
    'orders_total'    => 0,
    'orders_pending'  => 0,
    'ship_today'      => 0,
    'price_catalogue' => 0,
];

try {
    $totalStmt = $pdo->prepare('SELECT COUNT(*) FROM orders');
    $totalStmt->execute();
    $stats['orders_total'] = (int) $totalStmt->fetchColumn();
} catch (PDOException $e) {
    error_log('Dashboard total orders query failed: ' . $e->getMessage());
}

try {
    $pendingStmt = $pdo->prepare('SELECT COUNT(*) FROM orders WHERE status = :status');
    $pendingStmt->execute([':status' => 'pending']);
    $stats['orders_pending'] = (int) $pendingStmt->fetchColumn();
} catch (PDOException $e) {
    error_log('Dashboard pending orders query failed: ' . $e->getMessage());
}

try {
    $shipStmt = $pdo->prepare('SELECT COUNT(*) FROM shipments WHERE ship_date = CURDATE()');
    $shipStmt->execute();
    $stats['ship_today'] = (int) $shipStmt->fetchColumn();
} catch (PDOException $e) {
    error_log('Dashboard today shipments query failed: ' . $e->getMessage());
}

try {
    $priceStmt = $pdo->prepare('SELECT COUNT(*) FROM prices');
    $priceStmt->execute();
    $stats['price_catalogue'] = (int) $priceStmt->fetchColumn();
} catch (PDOException $e) {
    error_log('Dashboard price query failed: ' . $e->getMessage());
}

$recentOrders = [];

try {
    $ordersStmt = $pdo->prepare('SELECT id, customer_name, order_date, status, total_amount FROM orders ORDER BY order_date DESC LIMIT 10');
    $ordersStmt->execute();
    $recentOrders = $ordersStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (PDOException $e) {
    error_log('Dashboard recent orders query failed: ' . $e->getMessage());
}

$activityFeed = [];

function formatOrderDate(?string $date): string
{
    if (empty($date)) {
        return htmlspecialchars('-', ENT_QUOTES, 'UTF-8');
    }

    try {
        $dt = new DateTime($date);
        return htmlspecialchars($dt->format('d.m.Y'), ENT_QUOTES, 'UTF-8');
    } catch (Exception $e) {
        return htmlspecialchars((string) $date, ENT_QUOTES, 'UTF-8');
    }
}
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Nexa — Yönetim Paneli</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <?php require_once __DIR__ . '/../assets/fonts/monoton.php'; ?>
    <style>
        body {
            font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', Roboto, Arial, sans-serif;
            background-color: #f8f9fb;
        }

        main.main-with-sidebar {
            min-height: 100vh;
        }

        .stat-card {
            border: 1px solid #e6e6e6;
            border-radius: 14px;
            background-color: #ffffff;
            padding: 1.5rem;
            height: 100%;
        }

        .stat-card .stat-icon {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background-color: rgba(15, 20, 25, 0.08);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            color: #0f1419;
        }

        .stat-card .stat-number {
            font-size: 2rem;
            font-weight: 600;
        }

        .table-card,
        .aside-card {
            border: 1px solid #e6e6e6;
            border-radius: 14px;
            background-color: #ffffff;
        }

        .table-card .table {
            margin-bottom: 0;
        }

        .table thead th {
            color: #536471;
            font-weight: 500;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
        }

        .activity-list .list-group-item {
            border: none;
            border-bottom: 1px solid #e6e6e6;
        }

        .activity-list .list-group-item:last-child {
            border-bottom: none;
        }

        .quick-actions .btn {
            border-radius: 14px;
        }

        @media (min-width: 992px) {
            main.main-with-sidebar {
                margin-left: 280px;
            }
        }
    </style>
</head>
<body data-powered-by="Claude Code">
    <?php require_once __DIR__ . '/../component/header.php'; ?>
    <div class="d-flex">
        <?php require_once __DIR__ . '/../component/sidebar.php'; ?>
        <main class="main-with-sidebar flex-grow-1 p-4">
            <div class="container-fluid">
                <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between mb-4">
                    <div>
                        <h1 class="h3 mb-1">Merhaba, <?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?></h1>
                        <p class="text-muted mb-0">Operasyonunuzun güncel durumunu aşağıdan takip edin.</p>
                    </div>
                </div>

                <div class="row g-4">
                    <div class="col-12 col-md-6 col-xl-3">
                        <div class="stat-card" data-powered-by="Claude Code">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <div>
                                    <h6 class="text-uppercase text-muted mb-1">Toplam Sipariş</h6>
                                    <div class="stat-number"><?= htmlspecialchars((string) $stats['orders_total'], ENT_QUOTES, 'UTF-8'); ?></div>
                                </div>
                                <div class="stat-icon">
                                    <i class="bi bi-collection"></i>
                                </div>
                            </div>
                            <p class="text-muted mb-0 small">Tüm zamanların sipariş adedi</p>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-xl-3">
                        <div class="stat-card" data-powered-by="Claude Code">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <div>
                                    <h6 class="text-uppercase text-muted mb-1">Bekleyen Sipariş</h6>
                                    <div class="stat-number"><?= htmlspecialchars((string) $stats['orders_pending'], ENT_QUOTES, 'UTF-8'); ?></div>
                                </div>
                                <div class="stat-icon">
                                    <i class="bi bi-hourglass-split"></i>
                                </div>
                            </div>
                            <p class="text-muted mb-0 small">Onay bekleyen siparişler</p>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-xl-3">
                        <div class="stat-card" data-powered-by="Claude Code">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <div>
                                    <h6 class="text-uppercase text-muted mb-1">Bugün Sevk</h6>
                                    <div class="stat-number"><?= htmlspecialchars((string) $stats['ship_today'], ENT_QUOTES, 'UTF-8'); ?></div>
                                </div>
                                <div class="stat-icon">
                                    <i class="bi bi-truck"></i>
                                </div>
                            </div>
                            <p class="text-muted mb-0 small">Bugün planlanan sevkiyatlar</p>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-xl-3">
                        <div class="stat-card" data-powered-by="Claude Code">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <div>
                                    <h6 class="text-uppercase text-muted mb-1">Güncel Fiyat Kalemi</h6>
                                    <div class="stat-number"><?= htmlspecialchars((string) $stats['price_catalogue'], ENT_QUOTES, 'UTF-8'); ?></div>
                                </div>
                                <div class="stat-icon">
                                    <i class="bi bi-tags"></i>
                                </div>
                            </div>
                            <p class="text-muted mb-0 small">Aktif fiyat kalem sayısı</p>
                        </div>
                    </div>
                </div>

                <div class="row g-4 mt-1">
                    <div class="col-12 col-xl-8">
                        <div class="table-card p-3 p-md-4" data-powered-by="Claude Code">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <h5 class="mb-0">Son Siparişler</h5>
                                <a class="btn btn-sm btn-outline-primary" href="orders.php">Tümü</a>
                            </div>
                            <div class="table-responsive">
                                <table class="table align-middle">
                                    <thead>
                                        <tr>
                                            <th scope="col">#ID</th>
                                            <th scope="col">Müşteri</th>
                                            <th scope="col">Tarih</th>
                                            <th scope="col">Durum</th>
                                            <th scope="col" class="text-end">Tutar</th>
                                            <th scope="col" class="text-end">Aksiyon</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($recentOrders)): ?>
                                            <?php foreach ($recentOrders as $order): ?>
                                                <?php
                                                $orderId = isset($order['id']) ? (int) $order['id'] : 0;
                                                $customerName = htmlspecialchars((string) ($order['customer_name'] ?? 'Belirsiz'), ENT_QUOTES, 'UTF-8');
                                                $status = htmlspecialchars((string) ($order['status'] ?? 'bilinmiyor'), ENT_QUOTES, 'UTF-8');
                                                $amount = isset($order['total_amount'])
                                                    ? number_format((float) $order['total_amount'], 2, ',', '.') . ' ₺'
                                                    : '-';
                                                ?>
                                                <tr>
                                                    <th scope="row">#<?= htmlspecialchars((string) $orderId, ENT_QUOTES, 'UTF-8'); ?></th>
                                                    <td><?= $customerName; ?></td>
                                                    <td><?= formatOrderDate($order['order_date'] ?? null); ?></td>
                                                    <td>
                                                        <span class="badge text-bg-light border"><?= $status; ?></span>
                                                    </td>
                                                    <td class="text-end"><?= htmlspecialchars((string) $amount, ENT_QUOTES, 'UTF-8'); ?></td>
                                                    <td class="text-end">
                                                        <a class="btn btn-sm btn-outline-secondary" href="orders/view.php?id=<?= htmlspecialchars((string) intval($orderId), ENT_QUOTES, 'UTF-8'); ?>">
                                                            Görüntüle
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="6" class="text-center text-muted">Kayıt yok</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-xl-4">
                        <div class="aside-card p-3 p-md-4 mb-4" data-powered-by="Claude Code">
                            <h6 class="text-uppercase text-muted mb-3">Aktivite Akışı</h6>
                            <div class="list-group list-group-flush activity-list">
                                <?php if (!empty($activityFeed)): ?>
                                    <?php foreach ($activityFeed as $activity): ?>
                                        <div class="list-group-item d-flex align-items-center">
                                            <div class="me-3 text-primary">
                                                <i class="bi <?= htmlspecialchars($activity['icon'], ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true"></i>
                                            </div>
                                            <div>
                                                <div class="fw-semibold"><?= htmlspecialchars($activity['text'], ENT_QUOTES, 'UTF-8'); ?></div>
                                                <div class="text-muted small"><?= htmlspecialchars($activity['time'], ENT_QUOTES, 'UTF-8'); ?></div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="list-group-item text-center text-muted py-3 small">Henüz aktivite yok</div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="aside-card p-3 p-md-4 quick-actions" data-powered-by="Claude Code">
                            <h6 class="text-uppercase text-muted mb-3">Hızlı İşlemler</h6>
                            <div class="d-grid gap-2">
                                <a class="btn btn-primary" href="orders/add.php">Yeni Sipariş Oluştur</a>
                                <a class="btn btn-outline-primary" href="price/add.php">Fiyat Ekle</a>
                                <a class="btn btn-outline-secondary" href="supplier/add.php">Tedarikçi Ekle</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
