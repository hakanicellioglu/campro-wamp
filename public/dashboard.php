<?php
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

if (!empty($_GET['flash'])) {
    $_SESSION['flash_success'] = 'Toast test mesajı 🎉';
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
        :root {
            --nexa-primary: #2563eb;
            --nexa-primary-dark: #1d4ed8;
            --nexa-secondary: #64748b;
            --nexa-success: #10b981;
            --nexa-warning: #f59e0b;
            --nexa-danger: #ef4444;
            --nexa-info: #06b6d4;
            --nexa-bg-light: #f8fafc;
            --nexa-bg-white: #ffffff;
            --nexa-border: #e2e8f0;
            --nexa-text-primary: #0f172a;
            --nexa-text-secondary: #64748b;
            --nexa-shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --nexa-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --nexa-shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
            --nexa-shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', Roboto, Arial, sans-serif;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            min-height: 100vh;
            color: var(--nexa-text-primary);
        }

        main.main-with-sidebar {
            min-height: 100vh;
            background: transparent;
        }

        /* Welcome Section */
        .welcome-section {
            background: linear-gradient(135deg, var(--nexa-primary) 0%, var(--nexa-primary-dark) 100%);
            border-radius: 20px;
            color: white;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            position: relative;
            overflow: hidden;
        }

        .welcome-section::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 160px;
            height: 160px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            transform: translate(50%, -50%);
        }

        .welcome-section::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 120px;
            height: 120px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 50%;
            transform: translate(-30%, 30%);
        }

        .welcome-content {
            position: relative;
            z-index: 2;
        }

        .welcome-section h1 {
            font-size: 1.6rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .welcome-section p {
            font-size: 1rem;
            opacity: 0.9;
            margin-bottom: 0;
        }

        /* Enhanced Stat Cards */
        .stat-card {
            background: var(--nexa-bg-white);
            border: 1px solid var(--nexa-border);
            border-radius: 18px;
            padding: 1.5rem;
            height: 100%;
            position: relative;
            overflow: hidden;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: var(--nexa-shadow-sm);
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--nexa-shadow-xl);
            border-color: var(--nexa-primary);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--stat-color), var(--stat-color-dark));
            border-radius: 20px 20px 0 0;
        }

        .stat-card.orders-total {
            --stat-color: var(--nexa-primary);
            --stat-color-dark: var(--nexa-primary-dark);
        }

        .stat-card.orders-pending {
            --stat-color: var(--nexa-warning);
            --stat-color-dark: #d97706;
        }

        .stat-card.ship-today {
            --stat-color: var(--nexa-info);
            --stat-color-dark: #0891b2;
        }

        .stat-card.price-catalogue {
            --stat-color: var(--nexa-success);
            --stat-color-dark: #059669;
        }

        .stat-card .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            background: linear-gradient(135deg, var(--stat-color), var(--stat-color-dark));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            color: white;
            box-shadow: var(--nexa-shadow);
            margin-bottom: 0.75rem;
        }

        .stat-card .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--nexa-text-primary);
            line-height: 1;
            margin-bottom: 0.4rem;
        }

        .stat-card .stat-label {
            color: var(--nexa-text-secondary);
            font-weight: 600;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.2rem;
        }

        .stat-card .stat-description {
            color: var(--nexa-text-secondary);
            font-size: 0.8rem;
            line-height: 1.3;
        }

        /* Enhanced Table Card */
        .table-card {
            background: var(--nexa-bg-white);
            border: 1px solid var(--nexa-border);
            border-radius: 18px;
            overflow: hidden;
            box-shadow: var(--nexa-shadow-sm);
        }

        .table-card-header {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            padding: 1.1rem 1.5rem;
            border-bottom: 1px solid var(--nexa-border);
        }

        .table-card-header h5 {
            font-weight: 700;
            font-size: 1.1rem;
            color: var(--nexa-text-primary);
            margin: 0;
        }

        .table-card .table {
            margin-bottom: 0;
        }

        .table thead th {
            color: var(--nexa-text-secondary);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.7rem;
            letter-spacing: 0.05em;
            border-bottom: 2px solid var(--nexa-border);
            padding: 0.75rem;
            background: #f8fafc;
        }

        .table tbody td {
            padding: 0.75rem;
            vertical-align: middle;
            border-bottom: 1px solid #f1f5f9;
        }

        .table tbody tr:hover {
            background: #f8fafc;
        }

        /* Enhanced Aside Cards */
        .aside-card {
            background: var(--nexa-bg-white);
            border: 1px solid var(--nexa-border);
            border-radius: 18px;
            box-shadow: var(--nexa-shadow-sm);
            overflow: hidden;
        }

        .aside-card-header {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            padding: 1.1rem;
            border-bottom: 1px solid var(--nexa-border);
        }

        .aside-card-header h6 {
            color: var(--nexa-text-secondary);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.7rem;
            letter-spacing: 0.05em;
            margin: 0;
        }

        .activity-list .list-group-item {
            border: none;
            border-bottom: 1px solid #f1f5f9;
            padding: 0.75rem 1.1rem;
            transition: background-color 0.2s ease;
        }

        .activity-list .list-group-item:hover {
            background: #f8fafc;
        }

        .activity-list .list-group-item:last-child {
            border-bottom: none;
        }

        /* Enhanced Quick Actions */
        .quick-actions {
            padding: 1.1rem;
        }

        .quick-actions .btn {
            border-radius: 12px;
            font-weight: 600;
            padding: 0.6rem 1.2rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .quick-actions .btn-primary {
            background: linear-gradient(135deg, var(--nexa-primary), var(--nexa-primary-dark));
            border: none;
            box-shadow: var(--nexa-shadow);
        }

        .quick-actions .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: var(--nexa-shadow-lg);
        }

        .quick-actions .btn-outline-primary {
            border-color: var(--nexa-primary);
            color: var(--nexa-primary);
        }

        .quick-actions .btn-outline-primary:hover {
            background: var(--nexa-primary);
            transform: translateY(-2px);
            box-shadow: var(--nexa-shadow);
        }

        .quick-actions .btn-outline-secondary {
            border-color: var(--nexa-secondary);
            color: var(--nexa-secondary);
        }

        .quick-actions .btn-outline-secondary:hover {
            background: var(--nexa-secondary);
            transform: translateY(-2px);
            box-shadow: var(--nexa-shadow);
        }

        /* Enhanced Badges */
        .badge {
            padding: 0.35rem 0.75rem;
            border-radius: 40px;
            font-weight: 500;
            font-size: 0.7rem;
        }

        .badge.text-bg-light {
            background: #f1f5f9 !important;
            color: var(--nexa-text-secondary) !important;
            border: 1px solid var(--nexa-border);
        }

        /* Enhanced Buttons */
        .btn {
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .btn-outline-primary {
            border-color: var(--nexa-primary);
            color: var(--nexa-primary);
        }

        .btn-outline-primary:hover {
            background: var(--nexa-primary);
            border-color: var(--nexa-primary);
            transform: translateY(-1px);
        }

        .btn-outline-secondary:hover {
            transform: translateY(-1px);
        }

        /* Responsive Design */
        @media (min-width: 992px) {
            main.main-with-sidebar {
                margin-left: 280px;
            }
        }

        @media (max-width: 768px) {
            .welcome-section {
                padding: 1.25rem;
            }

            .welcome-section h1 {
                font-size: 1.4rem;
            }

            .stat-card {
                padding: 1.25rem;
            }

            .stat-card .stat-number {
                font-size: 1.75rem;
            }
        }

        /* Animation for stats */
        @keyframes countUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .stat-card {
            animation: countUp 0.6s ease-out forwards;
        }

        .stat-card:nth-child(1) { animation-delay: 0.1s; }
        .stat-card:nth-child(2) { animation-delay: 0.2s; }
        .stat-card:nth-child(3) { animation-delay: 0.3s; }
        .stat-card:nth-child(4) { animation-delay: 0.4s; }
    </style>
</head>
<body>
    <?php
    // Flash mesajlar için toast bileşeni (partials/flash.php dahil edildi)
    require_once __DIR__ . '/../partials/flash.php';
    ?>
    <div class="d-flex">
        <?php require_once __DIR__ . '/../component/sidebar.php'; ?>
        <main class="main-with-sidebar flex-grow-1 p-4">
            <div class="container-fluid">
                <!-- Welcome Section -->
                <div class="welcome-section">
                    <div class="welcome-content">
                        <h1>Merhaba, <?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?> 👋</h1>
                        <p>Operasyonunuzun güncel durumunu aşağıdan takip edin.</p>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="row g-4">
                    <div class="col-12 col-md-6 col-xl-3">
                        <div class="stat-card orders-total" data-powered-by="Claude Code">
                            <div class="stat-icon">
                                <i class="bi bi-collection"></i>
                            </div>
                            <div class="stat-label">Toplam Sipariş</div>
                            <div class="stat-number"><?= htmlspecialchars((string) $stats['orders_total'], ENT_QUOTES, 'UTF-8'); ?></div>
                            <div class="stat-description">Tüm zamanların sipariş adedi</div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-xl-3">
                        <div class="stat-card orders-pending" data-powered-by="Claude Code">
                            <div class="stat-icon">
                                <i class="bi bi-hourglass-split"></i>
                            </div>
                            <div class="stat-label">Bekleyen Sipariş</div>
                            <div class="stat-number"><?= htmlspecialchars((string) $stats['orders_pending'], ENT_QUOTES, 'UTF-8'); ?></div>
                            <div class="stat-description">Onay bekleyen siparişler</div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-xl-3">
                        <div class="stat-card ship-today" data-powered-by="Claude Code">
                            <div class="stat-icon">
                                <i class="bi bi-truck"></i>
                            </div>
                            <div class="stat-label">Bugün Sevk</div>
                            <div class="stat-number"><?= htmlspecialchars((string) $stats['ship_today'], ENT_QUOTES, 'UTF-8'); ?></div>
                            <div class="stat-description">Bugün planlanan sevkiyatlar</div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-xl-3">
                        <div class="stat-card price-catalogue" data-powered-by="Claude Code">
                            <div class="stat-icon">
                                <i class="bi bi-tags"></i>
                            </div>
                            <div class="stat-label">Güncel Fiyat Kalemi</div>
                            <div class="stat-number"><?= htmlspecialchars((string) $stats['price_catalogue'], ENT_QUOTES, 'UTF-8'); ?></div>
                            <div class="stat-description">Aktif fiyat kalem sayısı</div>
                        </div>
                    </div>
                </div>

                <!-- Main Content -->
                <div class="row g-4 mt-1">
                    <div class="col-12 col-xl-8">
                        <div class="table-card" data-powered-by="Claude Code">
                            <div class="table-card-header">
                                <div class="d-flex align-items-center justify-content-between">
                                    <h5>Son Siparişler</h5>
                                    <a class="btn btn-sm btn-outline-primary" href="orders.php">
                                        <i class="bi bi-arrow-right me-1"></i>Tümü
                                    </a>
                                </div>
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
                                                    <td><strong><?= $customerName; ?></strong></td>
                                                    <td><?= formatOrderDate($order['order_date'] ?? null); ?></td>
                                                    <td>
                                                        <span class="badge text-bg-light border"><?= $status; ?></span>
                                                    </td>
                                                    <td class="text-end"><strong><?= htmlspecialchars((string) $amount, ENT_QUOTES, 'UTF-8'); ?></strong></td>
                                                    <td class="text-end">
                                                        <a class="btn btn-sm btn-outline-secondary" href="orders/view.php?id=<?= htmlspecialchars((string) intval($orderId), ENT_QUOTES, 'UTF-8'); ?>">
                                                            <i class="bi bi-eye me-1"></i>Görüntüle
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="6" class="text-center text-muted py-4">
                                                    <i class="bi bi-inbox fs-1 d-block mb-2 opacity-50"></i>
                                                    Kayıt yok
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-xl-4">
                        <!-- Activity Feed -->
                        <div class="aside-card mb-4" data-powered-by="Claude Code">
                            <div class="aside-card-header">
                                <h6>Aktivite Akışı</h6>
                            </div>
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
                                    <div class="list-group-item text-center text-muted py-4">
                                        <i class="bi bi-activity fs-1 d-block mb-2 opacity-50"></i>
                                        <small>Henüz aktivite yok</small>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Quick Actions -->
                        <div class="aside-card quick-actions" data-powered-by="Claude Code">
                            <div class="aside-card-header">
                                <h6>Hızlı İşlemler</h6>
                            </div>
                            <div class="d-grid gap-2">
                                <a class="btn btn-primary" href="orders/add.php">
                                    <i class="bi bi-plus-circle me-2"></i>Yeni Sipariş Oluştur
                                </a>
                                <a class="btn btn-outline-primary" href="price/add.php">
                                    <i class="bi bi-tag me-2"></i>Fiyat Ekle
                                </a>
                                <a class="btn btn-outline-secondary" href="supplier/add.php">
                                    <i class="bi bi-building me-2"></i>Tedarikçi Ekle
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <?php require_once __DIR__ . '/../component/footer.php'; ?>
</body>
</html>