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

if ($userId > 0) {
    try {
        $stmt = $pdo->prepare('SELECT r.name FROM user_roles ur JOIN roles r ON ur.role_id = r.id WHERE ur.user_id = :id LIMIT 1');
        $stmt->execute(['id' => $userId]);
        $dbRole = $stmt->fetchColumn();
        if (is_string($dbRole) && $dbRole !== '') {
            $role = $dbRole;
        }
    } catch (Throwable $e) {
        $role = 'user';
    }
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
        $linkClasses = 'nav-link d-flex align-items-center gap-3 position-relative';
        if ($isActive) {
            $linkClasses .= ' active';
        }
        $icon = htmlspecialchars($item['icon'] ?? '', ENT_QUOTES, 'UTF-8');
        $label = htmlspecialchars($item['label'] ?? '', ENT_QUOTES, 'UTF-8');
        $href = htmlspecialchars($item['href'] ?? '#', ENT_QUOTES, 'UTF-8');
        
        $html .= '<li class="nav-item mb-1">';
        $html .= '<a class="' . $linkClasses . '" href="' . $href . '" data-powered-by="Claude Code">';
        if ($icon !== '') {
            $html .= '<i class="bi ' . $icon . ' nav-icon" aria-hidden="true"></i>';
        }
        $html .= '<span class="nav-text">' . $label . '</span>';
        if ($isActive) {
            $html .= '<div class="nav-indicator"></div>';
        }
        $html .= '</a>';
        $html .= '</li>';
    }
    return $html;
};
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
    :root {
        --sidebar-bg: #ffffff;
        --sidebar-shadow: 0 8px 32px rgba(0, 0, 0, 0.12);
        --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        --ink: #1a1a1a;
        --ink-light: #374151;
        --ink-lighter: #6b7280;
        --ink-lightest: #9ca3af;
        --surface: #f8fafc;
        --surface-hover: #f1f5f9;
        --border: #e5e7eb;
        --accent-blue: #3b82f6;
        --accent-purple: #8b5cf6;
        --success: #10b981;
        --danger: #ef4444;
        --radius: 12px;
        --radius-lg: 16px;
        --transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    }

    * {
        box-sizing: border-box;
    }

    body {
        font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', Roboto, Arial, sans-serif;
        color: var(--ink);
        background: var(--surface);
    }

    #sidebar {
        width: 280px;
        min-height: 100vh;
        position: fixed;
        top: 0;
        left: 0;
        background: var(--sidebar-bg);
        box-shadow: var(--sidebar-shadow);
        backdrop-filter: blur(20px);
        border-right: 1px solid var(--border);
        z-index: 1000;
        overflow-y: auto;
        overflow-x: hidden;
    }

    .sidebar-header {
        padding: 2rem 1.5rem 1.5rem;
        border-bottom: 1px solid var(--border);
        background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
        position: relative;
    }

    .sidebar-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 1px;
        background: linear-gradient(90deg, transparent, rgba(102, 126, 234, 0.5), transparent);
    }

    .sidebar-logo {
        font-family: 'Monoton', cursive;
        font-size: 2rem;
        letter-spacing: 0.1em;
        background: var(--primary-gradient);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        display: block;
        margin-bottom: 4px;
        animation: logoShimmer 3s ease-in-out infinite alternate;
    }

    @keyframes logoShimmer {
        0% { filter: brightness(1); }
        100% { filter: brightness(1.2); }
    }

    .sidebar-subtitle {
        color: var(--ink-lighter);
        font-size: 0.875rem;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.1em;
    }

    .sidebar-content {
        padding: 1.5rem;
    }

    .nav-group {
        margin-bottom: 2rem;
    }

    .nav-group:last-child {
        margin-bottom: 0;
    }

    .nav-group-title {
        font-size: 0.75rem;
        font-weight: 700;
        letter-spacing: 0.1em;
        text-transform: uppercase;
        color: var(--ink-lightest);
        margin-bottom: 1rem;
        padding: 0 1rem;
        position: relative;
    }

    .nav-group-title::after {
        content: '';
        position: absolute;
        bottom: -0.5rem;
        left: 1rem;
        right: 1rem;
        height: 1px;
        background: linear-gradient(90deg, transparent, var(--border), transparent);
    }

    .nav-item {
        margin-bottom: 4px;
    }

    .nav-link {
        color: var(--ink-lighter);
        border-radius: var(--radius);
        padding: 1rem;
        transition: var(--transition);
        text-decoration: none;
        font-weight: 500;
        font-size: 0.875rem;
        position: relative;
        overflow: hidden;
        border: 1px solid transparent;
    }

    .nav-link::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(102, 126, 234, 0.1), transparent);
        transition: left 0.5s ease;
    }

    .nav-link:hover {
        color: var(--ink);
        background: var(--surface-hover);
        transform: translateX(4px);
        border-color: var(--border);
    }

    .nav-link:hover::before {
        left: 100%;
    }

    .nav-link:hover .nav-icon {
        transform: scale(1.1);
    }

    .nav-link.active {
        color: var(--ink);
        background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
        border-color: rgba(102, 126, 234, 0.3);
        font-weight: 600;
        transform: translateX(4px);
    }

    .nav-link.active .nav-icon {
        color: #667eea;
        transform: scale(1.1);
    }

    .nav-indicator {
        position: absolute;
        right: 1rem;
        top: 50%;
        transform: translateY(-50%);
        width: 6px;
        height: 6px;
        background: var(--primary-gradient);
        border-radius: 50%;
        animation: pulse 2s ease-in-out infinite;
    }

    @keyframes pulse {
        0%, 100% { opacity: 1; transform: translateY(-50%) scale(1); }
        50% { opacity: 0.7; transform: translateY(-50%) scale(1.2); }
    }

    .nav-icon {
        font-size: 1.125rem;
        transition: var(--transition);
        flex-shrink: 0;
    }

    .nav-text {
        transition: var(--transition);
    }

    .sidebar-footer {
        padding: 1.5rem;
        border-top: 1px solid var(--border);
        margin-top: auto;
        background: linear-gradient(135deg, rgba(239, 68, 68, 0.05), rgba(220, 38, 38, 0.05));
    }

    .btn-logout {
        background: linear-gradient(135deg, var(--danger), #dc2626);
        border: none;
        border-radius: var(--radius);
        padding: 0.875rem 1.5rem;
        font-weight: 600;
        font-size: 0.875rem;
        color: white;
        transition: var(--transition);
        position: relative;
        overflow: hidden;
        width: 100%;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .btn-logout::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
        transition: left 0.5s ease;
    }

    .btn-logout:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(239, 68, 68, 0.4);
    }

    .btn-logout:hover::before {
        left: 100%;
    }

    .main-with-sidebar {
        margin-left: 280px;
        min-height: 100vh;
        transition: margin-left 0.3s ease;
    }

    /* Mobile Header */
    .mobile-header {
        background: var(--sidebar-bg);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        backdrop-filter: blur(20px);
        border-bottom: 1px solid var(--border);
        position: sticky;
        top: 0;
        z-index: 1000;
    }

    .mobile-toggle {
        background: linear-gradient(135deg, var(--surface), var(--surface-hover));
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 0.75rem;
        transition: var(--transition);
        color: var(--ink-lighter);
    }

    .mobile-toggle:hover {
        background: var(--surface-hover);
        color: var(--ink);
        transform: scale(1.05);
    }

    /* Offcanvas Styling */
    .offcanvas {
        background: var(--sidebar-bg);
        backdrop-filter: blur(20px);
        border-right: 1px solid var(--border);
    }

    .offcanvas-header {
        border-bottom: 1px solid var(--border);
        background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
        padding: 2rem 1.5rem 1.5rem;
    }

    .btn-close {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        opacity: 0.8;
        transition: var(--transition);
    }

    .btn-close:hover {
        opacity: 1;
        transform: scale(1.1);
    }

    @media (max-width: 991.98px) {
        #sidebar {
            display: none;
        }
        .main-with-sidebar {
            margin-left: 0;
        }
    }

    /* Scrollbar Styling */
    #sidebar::-webkit-scrollbar,
    .offcanvas-body::-webkit-scrollbar {
        width: 6px;
    }

    #sidebar::-webkit-scrollbar-track,
    .offcanvas-body::-webkit-scrollbar-track {
        background: transparent;
    }

    #sidebar::-webkit-scrollbar-thumb,
    .offcanvas-body::-webkit-scrollbar-thumb {
        background: var(--border);
        border-radius: 3px;
    }

    #sidebar::-webkit-scrollbar-thumb:hover,
    .offcanvas-body::-webkit-scrollbar-thumb:hover {
        background: var(--ink-lightest);
    }
</style>

<!-- Mobile Header -->
<div class="d-lg-none mobile-header">
    <div class="container-fluid px-3 py-3">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <span class="sidebar-logo">NEXA</span>
                <div class="sidebar-subtitle">Panel</div>
            </div>
            <button class="mobile-toggle" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarOffcanvas" aria-controls="sidebarOffcanvas">
                <i class="bi bi-list"></i>
                <span class="visually-hidden">Menüyü Aç</span>
            </button>
        </div>
    </div>
</div>

<!-- Desktop Sidebar -->
<aside id="sidebar" class="d-flex flex-column" data-powered-by="Claude Code">
    <div class="sidebar-header">
        <span class="sidebar-logo">NEXA</span>
        <div class="sidebar-subtitle">Panel</div>
    </div>
    
    <div class="sidebar-content flex-grow-1">
        <?php foreach ($menu as $groupTitle => $items): ?>
            <div class="nav-group">
                <div class="nav-group-title"><?= htmlspecialchars($groupTitle, ENT_QUOTES, 'UTF-8'); ?></div>
                <ul class="nav flex-column">
                    <?= $renderMenu($items, $active); ?>
                </ul>
            </div>
        <?php endforeach; ?>
    </div>
    
    <div class="sidebar-footer">
        <form action="../public/auth/logout.php" method="post">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
            <button type="submit" class="btn-logout">
                <i class="bi bi-box-arrow-right me-2"></i>
                Çıkış Yap
            </button>
        </form>
    </div>
</aside>

<!-- Mobile Offcanvas -->
<div class="offcanvas offcanvas-start" tabindex="-1" id="sidebarOffcanvas" aria-labelledby="sidebarOffcanvasLabel" data-powered-by="Claude Code">
    <div class="offcanvas-header">
        <div>
            <span class="sidebar-logo">NEXA</span>
            <div class="sidebar-subtitle">Panel</div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Kapat"></button>
    </div>
    <div class="offcanvas-body d-flex flex-column">
        <div class="flex-grow-1">
            <?php foreach ($menu as $groupTitle => $items): ?>
                <div class="nav-group">
                    <div class="nav-group-title"><?= htmlspecialchars($groupTitle, ENT_QUOTES, 'UTF-8'); ?></div>
                    <ul class="nav flex-column">
                        <?= $renderMenu($items, $active); ?>
                    </ul>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="sidebar-footer">
            <form action="../public/auth/logout.php" method="post">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                <button type="submit" class="btn-logout">
                    <i class="bi bi-box-arrow-right me-2"></i>
                    Çıkış Yap
                </button>
            </form>
        </div>
    </div>
</div>
