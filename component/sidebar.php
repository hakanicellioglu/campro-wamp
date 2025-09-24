<?php
// component/sidebar.php — sabit sidebar ve toggle kontrollü slide animasyonu
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

        $html .= '<li class="nav-item">';
        $html .= '<a class="' . $linkClasses . '" href="' . $href . '">';
        if ($icon !== '') {
            $html .= '<i class="bi ' . $icon . ' nav-icon" aria-hidden="true"></i>';
        }
        $html .= '<span class="nav-text">' . $label . '</span>';
        if ($isActive) {
            $html .= '<span class="nav-indicator" aria-hidden="true"></span>';
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
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
    :root {
        --sbw: 280px;
        --sidebar-bg: #ffffff;
        --sidebar-border: #e5e7eb;
        --sidebar-shadow: 0 16px 48px rgba(15, 23, 42, 0.08);
        --ink: #111827;
        --ink-light: #6b7280;
        --accent: #667eea;
        --surface: #f8fafc;
        --radius: 14px;
    }

    body {
        font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', sans-serif;
        background: var(--surface);
        color: var(--ink);
    }

    .sidebar {
        position: fixed;
        top: 0;
        left: 0;
        width: var(--sbw);
        height: 100vh;
        background: var(--sidebar-bg);
        border-right: 1px solid var(--sidebar-border);
        box-shadow: var(--sidebar-shadow);
        display: flex;
        flex-direction: column;
        transform: translateX(0);
        transition: transform 280ms ease;
        z-index: 1030;
        overflow-y: auto;
        overflow-x: hidden;
        padding: 1.5rem 1.25rem;
        gap: 1.5rem;
    }

    .sidebar.is-collapsed {
        transform: translateX(calc(-1 * var(--sbw)));
    }

    .sidebar-header {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
        padding-right: 2.5rem;
    }

    .sidebar-logo {
        font-family: 'Monoton', cursive;
        font-size: 2rem;
        letter-spacing: 0.12em;
        background: linear-gradient(135deg, #667eea, #764ba2);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .sidebar-subtitle {
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.16em;
        color: var(--ink-light);
        font-weight: 600;
    }

    .sidebar-toggle {
        position: absolute;
        top: 1rem;
        left: calc(var(--sbw) - 44px);
        width: 44px;
        height: 44px;
        border-radius: 50%;
        border: 0;
        background: #111827;
        color: #f9fafb;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: left 280ms ease, transform 280ms ease, background 180ms ease;
        z-index: 1040;
    }

    .sidebar-toggle:hover {
        background: #1f2937;
    }

    .sidebar.is-collapsed .sidebar-toggle {
        left: 8px;
        transform: translateX(var(--sbw));
    }

    .nav-groups {
        display: flex;
        flex-direction: column;
        gap: 1.75rem;
    }

    .nav-group-title {
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.14em;
        color: var(--ink-light);
        margin-bottom: 0.75rem;
        padding-left: 0.5rem;
    }

    .nav {
        gap: 0.25rem;
    }

    .nav-link {
        border-radius: var(--radius);
        padding: 0.875rem 1rem;
        color: var(--ink-light);
        font-weight: 500;
        font-size: 0.925rem;
        transition: background 180ms ease, color 180ms ease, transform 180ms ease;
        border: 1px solid transparent;
    }

    .nav-link:hover {
        color: var(--ink);
        background: rgba(102, 126, 234, 0.08);
        transform: translateX(4px);
    }

    .nav-link.active {
        color: var(--ink);
        background: rgba(102, 126, 234, 0.16);
        border-color: rgba(102, 126, 234, 0.35);
        font-weight: 600;
    }

    .nav-icon {
        font-size: 1.15rem;
    }

    .nav-indicator {
        position: absolute;
        right: 1rem;
        top: 50%;
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea, #764ba2);
        transform: translateY(-50%);
    }

    .sidebar-footer {
        margin-top: auto;
        border-top: 1px solid var(--sidebar-border);
        padding-top: 1.5rem;
    }

    .btn-logout {
        width: 100%;
        background: linear-gradient(135deg, #ef4444, #dc2626);
        border: 0;
        color: #fff;
        border-radius: var(--radius);
        padding: 0.9rem 1rem;
        font-weight: 600;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        transition: transform 180ms ease, box-shadow 180ms ease;
    }

    .btn-logout:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 24px rgba(220, 38, 38, 0.35);
    }

    body.has-sidebar .content {
        margin-left: var(--sbw);
        transition: margin-left 280ms ease;
    }

    body.sidebar-collapsed .content {
        margin-left: 0;
    }

    @media (prefers-reduced-motion: reduce) {
        .sidebar,
        .sidebar-toggle,
        body.has-sidebar .content {
            transition: none !important;
        }
    }

    @media (max-width: 991.98px) {
        .sidebar {
            box-shadow: 0 24px 48px rgba(15, 23, 42, 0.16);
        }

        .sidebar-toggle {
            top: 0.75rem;
        }
    }
</style>

<aside id="sidebar" class="sidebar" aria-label="Ana menü">
    <button id="sidebarToggle" class="sidebar-toggle" type="button" aria-expanded="true" aria-controls="sidebar" aria-label="Menüyü kapat">
        <i class="bi bi-x-lg" aria-hidden="true"></i>
    </button>
    <header class="sidebar-header">
        <span class="sidebar-logo">NEXA</span>
        <span class="sidebar-subtitle">Panel</span>
    </header>

    <nav class="nav-groups" role="navigation">
        <?php foreach ($menu as $groupTitle => $items): ?>
            <section class="nav-group">
                <h2 class="nav-group-title"><?= htmlspecialchars($groupTitle, ENT_QUOTES, 'UTF-8'); ?></h2>
                <ul class="nav flex-column">
                    <?= $renderMenu($items, $active); ?>
                </ul>
            </section>
        <?php endforeach; ?>
    </nav>

    <footer class="sidebar-footer">
        <form action="../public/auth/logout.php" method="post">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
            <button type="submit" class="btn-logout">
                <i class="bi bi-box-arrow-right me-2"></i>
                Çıkış Yap
            </button>
        </form>
    </footer>
</aside>

<script>
    (function () {
        const sidebar = document.getElementById('sidebar');
        const toggleButton = document.getElementById('sidebarToggle');
        if (!sidebar || !toggleButton) {
            return;
        }

        const icon = toggleButton.querySelector('i');
        const storageKey = 'sidebar-collapsed';
        const mobileQuery = window.matchMedia('(max-width: 991.98px)');

        document.body.classList.add('has-sidebar');

        const setCollapsed = (collapsed, options = { store: true }) => {
            const shouldStore = options.store ?? true;
            sidebar.classList.toggle('is-collapsed', collapsed);
            document.body.classList.toggle('sidebar-collapsed', collapsed);
            toggleButton.setAttribute('aria-expanded', String(!collapsed));
            toggleButton.setAttribute('aria-label', collapsed ? 'Menüyü aç' : 'Menüyü kapat');
            if (icon) {
                icon.classList.remove('bi-list', 'bi-x-lg');
                icon.classList.add(collapsed ? 'bi-list' : 'bi-x-lg');
            }
            if (shouldStore) {
                try {
                    localStorage.setItem(storageKey, collapsed ? '1' : '0');
                } catch (err) {
                    // ignore storage errors
                }
            }
        };

        const getStoredState = () => {
            try {
                return localStorage.getItem(storageKey);
            } catch (err) {
                return null;
            }
        };

        const stored = getStoredState();
        const prefersCollapsed = stored === '1' || (stored === null && mobileQuery.matches);
        setCollapsed(prefersCollapsed, { store: stored !== null });

        toggleButton.addEventListener('click', () => {
            const nextState = !sidebar.classList.contains('is-collapsed');
            setCollapsed(nextState);
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !sidebar.classList.contains('is-collapsed')) {
                event.preventDefault();
                setCollapsed(true);
                toggleButton.focus({ preventScroll: true });
            }
        });

        mobileQuery.addEventListener('change', (event) => {
            if (event.matches) {
                setCollapsed(true, { store: false });
            } else {
                const cached = getStoredState();
                setCollapsed(cached === '1' ? true : false, { store: false });
            }
        });
    })();
</script>
