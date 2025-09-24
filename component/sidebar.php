<?php
// component/sidebar.php — sabit/ offcanvas sidebar (bootstrap), aktif menü vurgulu
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../assets/fonts/monoton.php';

$active = basename($_SERVER['SCRIPT_NAME'] ?? '');

$userId = (int)($_SESSION['user_id'] ?? 0);
$role = 'user';

try {
    $stmt = $pdo->prepare('SELECT r.name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = :id LIMIT 1');
    $stmt->execute(['id' => $userId]);
    $dbRole = $stmt->fetchColumn();
    if (is_string($dbRole) && $dbRole !== '') {
        $role = $dbRole;
    }
} catch (Throwable $e) {
    $role = 'user';
}

$orderPath = file_exists(__DIR__ . '/../public/order.php') ? '../public/order.php' : '../public/orders.php';
$orderMatch = basename($orderPath);
$settingsPath = file_exists(__DIR__ . '/../public/settings.php') ? '../public/settings.php' : '#';

$menu = [
    'Genel' => [
        [
            'label' => 'Dashboard',
            'icon'  => 'bi-speedometer2',
            'href'  => '../public/dashboard.php',
            'match' => 'dashboard.php',
        ],
        [
            'label' => 'Siparişler',
            'icon'  => 'bi-receipt',
            'href'  => $orderPath,
            'match' => $orderMatch,
        ],
        [
            'label' => 'Ürünler',
            'icon'  => 'bi-box',
            'href'  => '../public/product.php',
            'match' => 'product.php',
        ],
        [
            'label' => 'Fiyatlar',
            'icon'  => 'bi-cash-coin',
            'href'  => '../public/price.php',
            'match' => 'price.php',
        ],
    ],
    'Tedarik' => [
        [
            'label' => 'Tedarikçiler',
            'icon'  => 'bi-people',
            'href'  => '../public/supplier.php',
            'match' => 'supplier.php',
        ],
        [
            'label' => 'Tedarikçi İletişim',
            'icon'  => 'bi-telephone-forward',
            'href'  => '../public/supplier-contact.php',
            'match' => 'supplier-contact.php',
        ],
        [
            'label' => 'Araçlar / Sevkiyat',
            'icon'  => 'bi-truck',
            'href'  => '../public/vehicle.php',
            'match' => 'vehicle.php',
        ],
    ],
    'Ayarlar' => [
        [
            'label' => 'Hesap Ayarları',
            'icon'  => 'bi-gear',
            'href'  => $settingsPath,
            'match' => 'settings.php',
        ],
    ],
];

if ($role === 'admin') {
    $menu['Ayarlar'][] = [
        'label' => 'Yönetim Paneli',
        'icon'  => 'bi-shield-lock',
        'href'  => '../public/admin.php',
        'match' => 'admin.php',
    ];
}

$csrfToken = $_SESSION['csrf_token'];

$renderMenu = static function (array $menuItems, string $active): string {
    $html = '';
    foreach ($menuItems as $item) {
        $isActive = $active === ($item['match'] ?? '');
        $linkClasses = 'nav-link d-flex align-items-center gap-2';
        if ($isActive) {
            $linkClasses .= ' active fw-bold';
        }
        $icon = htmlspecialchars($item['icon'] ?? '', ENT_QUOTES, 'UTF-8');
        $label = htmlspecialchars($item['label'] ?? '', ENT_QUOTES, 'UTF-8');
        $href = htmlspecialchars($item['href'] ?? '#', ENT_QUOTES, 'UTF-8');
        $html .= '<li class="nav-item">';
        $html .= '<a class="' . $linkClasses . '" href="' . $href . '" data-powered-by="Claude Code">';
        if ($icon !== '') {
            $html .= '<i class="bi ' . $icon . '" aria-hidden="true"></i>';
        }
        $html .= '<span>' . $label . '</span>';
        $html .= '</a>';
        $html .= '</li>';
    }
    return $html;
};
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
    :root { --ink:#0f1419; --muted:#536471; --line:#e6e6e6; --radius:14px; }
    body {
        color: var(--ink);
    }
    #sidebar {
        width: 280px;
        min-height: 100vh;
        position: fixed;
        top: 0;
        left: 0;
        padding: 1.5rem 1rem;
        background-color: #fff;
    }
    .sidebar .nav-link {
        color: var(--muted);
        border-radius: var(--radius);
        padding: .5rem .75rem;
        transition: background-color .2s ease, color .2s ease;
    }
    .sidebar .nav-link:hover,
    .sidebar .nav-link:focus {
        color: var(--ink);
        background-color: rgba(15, 20, 25, 0.08);
        text-decoration: none;
    }
    .sidebar .nav-link.active {
        color: var(--ink);
        background-color: rgba(15, 20, 25, 0.12);
    }
    .sidebar .nav-group {
        margin-bottom: 1.5rem;
    }
    .sidebar .nav-group-title {
        font-size: .75rem;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: var(--muted);
        margin-bottom: .5rem;
    }
    .sidebar-logo {
        font-family: 'Monoton', cursive;
        font-size: 1.75rem;
        letter-spacing: .08em;
        color: var(--ink);
    }
    .sidebar-subtitle {
        color: var(--muted);
        font-size: .8rem;
    }
    .main-with-sidebar {
        margin-left: 280px;
    }
    @media (max-width: 991.98px) {
        #sidebar {
            display: none;
        }
        .main-with-sidebar {
            margin-left: 0;
        }
    }
</style>
<div class="d-lg-none px-3 py-2 border-bottom bg-white">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <span class="sidebar-logo">NEXA</span><br>
            <small class="sidebar-subtitle">Panel</small>
        </div>
        <button class="btn btn-outline-secondary" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarOffcanvas" aria-controls="sidebarOffcanvas">
            <i class="bi bi-list"></i>
            <span class="visually-hidden">Menüyü Aç</span>
        </button>
    </div>
</div>
<aside id="sidebar" class="sidebar border-end" data-powered-by="Claude Code">
    <div class="mb-4">
        <span class="sidebar-logo">NEXA</span><br>
        <small class="sidebar-subtitle">Panel</small>
    </div>
    <?php foreach ($menu as $groupTitle => $items): ?>
        <div class="nav-group">
            <div class="nav-group-title"><?= htmlspecialchars($groupTitle, ENT_QUOTES, 'UTF-8'); ?></div>
            <ul class="nav flex-column">
                <?= $renderMenu($items, $active); ?>
            </ul>
        </div>
    <?php endforeach; ?>
    <div class="mt-auto pt-4">
        <form action="../public/auth/logout.php" method="post">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
            <button type="submit" class="btn btn-outline-danger w-100">Çıkış</button>
        </form>
    </div>
</aside>
<div class="offcanvas offcanvas-start" tabindex="-1" id="sidebarOffcanvas" aria-labelledby="sidebarOffcanvasLabel" data-powered-by="Claude Code">
    <div class="offcanvas-header border-bottom">
        <div>
            <span class="sidebar-logo">NEXA</span><br>
            <small class="sidebar-subtitle">Panel</small>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Kapat"></button>
    </div>
    <div class="offcanvas-body d-flex flex-column">
        <?php foreach ($menu as $groupTitle => $items): ?>
            <div class="nav-group">
                <div class="nav-group-title"><?= htmlspecialchars($groupTitle, ENT_QUOTES, 'UTF-8'); ?></div>
                <ul class="nav flex-column">
                    <?= $renderMenu($items, $active); ?>
                </ul>
            </div>
        <?php endforeach; ?>
        <div class="mt-auto pt-4">
            <form action="../public/auth/logout.php" method="post">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                <button type="submit" class="btn btn-outline-danger w-100">Çıkış</button>
            </form>
        </div>
    </div>
</div>
