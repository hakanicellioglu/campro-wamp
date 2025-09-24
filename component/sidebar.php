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
            'label'    => 'Sipariş Yönetimi',
            'icon'     => 'bi-receipt',
            'match'    => [$orderMatch, 'sale.php'],
            'children' => [
                [
                    'label' => 'Siparişler',
                    'icon'  => 'bi-dot',
                    'href'  => $orderPath,
                    'match' => $orderMatch,
                ],
                [
                    'label' => 'Satışlar',
                    'icon'  => 'bi-graph-up',
                    'href'  => '../public/sale.php',
                    'match' => 'sale.php',
                ],
            ],
        ],
        [
            'label'    => 'Ürün Yönetimi',
            'icon'     => 'bi-box-seam',
            'match'    => ['product.php', 'price.php'],
            'children' => [
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
        ],
    ],
    'Tedarik' => [
        [
            'label'    => 'Tedarik Operasyonları',
            'icon'     => 'bi-people',
            'match'    => ['supplier.php', 'supplier-contact.php', 'vehicle.php'],
            'children' => [
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

$matchActive = static function (string $active, $match): bool {
    if (is_array($match)) {
        return in_array($active, $match, true);
    }

    return is_string($match) && $match !== '' && $active === $match;
};

$detectActive = static function (array $item, string $active) use (&$detectActive, $matchActive): bool {
    if ($matchActive($active, $item['match'] ?? null)) {
        return true;
    }

    $children = $item['children'] ?? [];
    if (!is_array($children) || $children === []) {
        return false;
    }

    foreach ($children as $child) {
        if (is_array($child) && $detectActive($child, $active)) {
            return true;
        }
    }

    return false;
};

$renderMenu = static function (array $menuItems, string $active, int $depth = 0) use (&$renderMenu, $matchActive, $detectActive): string {
    $html = '';

    foreach ($menuItems as $index => $item) {
        if (!is_array($item)) {
            continue;
        }

        $icon = htmlspecialchars($item['icon'] ?? '', ENT_QUOTES, 'UTF-8');
        $label = htmlspecialchars($item['label'] ?? '', ENT_QUOTES, 'UTF-8');
        $href = isset($item['href']) ? htmlspecialchars((string)$item['href'], ENT_QUOTES, 'UTF-8') : '#';
        $hasChildren = !empty($item['children']) && is_array($item['children']);
        $isActive = $detectActive($item, $active);
        $isCurrent = $matchActive($active, $item['match'] ?? null);

        $itemClasses = 'nav-item';
        if ($hasChildren) {
            $itemClasses .= ' has-children';
        }
        if ($isActive) {
            $itemClasses .= ' is-open';
        }

        $html .= '<li class="' . $itemClasses . '">';

        if ($hasChildren) {
            $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $item['label'] ?? 'submenu'), '-'));
            $submenuId = 'submenu-' . $depth . '-' . $index . '-' . $slug;
            $toggleClasses = 'nav-link submenu-toggle d-flex align-items-center justify-content-between gap-3 position-relative w-100 text-start';
            if ($isActive) {
                $toggleClasses .= ' active';
            }

            $html .= '<button type="button" class="' . $toggleClasses . '" aria-expanded="' . ($isActive ? 'true' : 'false') . '" aria-controls="' . $submenuId . '">';
            $html .= '<span class="d-flex align-items-center gap-3">';
            if ($icon !== '') {
                $html .= '<i class="bi ' . $icon . ' nav-icon" aria-hidden="true"></i>';
            }
            $html .= '<span class="nav-text">' . $label . '</span>';
            $html .= '</span>';
            $html .= '<i class="bi bi-chevron-down submenu-caret" aria-hidden="true"></i>';
            $html .= '</button>';

            $submenuHtml = $renderMenu($item['children'], $active, $depth + 1);
            $html .= '<ul class="submenu nav flex-column" id="' . $submenuId . '" aria-hidden="' . ($isActive ? 'false' : 'true') . '">';
            $html .= $submenuHtml;
            $html .= '</ul>';
        } else {
            $linkClasses = 'nav-link d-flex align-items-center gap-3 position-relative';
            if ($isCurrent) {
                $linkClasses .= ' active';
            }

            $html .= '<a class="' . $linkClasses . '" href="' . $href . '">';
            if ($icon !== '') {
                $html .= '<i class="bi ' . $icon . ' nav-icon" aria-hidden="true"></i>';
            }
            $html .= '<span class="nav-text">' . $label . '</span>';
            if ($isCurrent) {
                $html .= '<span class="nav-indicator" aria-hidden="true"></span>';
            }
            $html .= '</a>';
        }

        $html .= '</li>';
    }

    return $html;
};
?>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
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

    .sidebar-wrapper {
        position: relative;
    }

    .sidebar {
        position: fixed;
        top: 0;
        left: 0;
        height: 100vh;
        width: var(--sbw);
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
        position: fixed;
        top: 1rem;
        left: calc(var(--sbw) - 44px);
        width: 44px;
        height: 44px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        z-index: 1040;
        transition: left 280ms ease, transform 280ms ease, background-color 200ms ease, color 200ms ease;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.18);
    }

    .sidebar-toggle .bi {
        font-size: 1.15rem;
    }

    .sidebar.is-collapsed ~ .sidebar-toggle {
        left: 8px;
        transform: translateX(0);
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

    .nav.flex-column {
        flex-direction: column;
    }

    .nav-link,
    .submenu-toggle {
        border-radius: var(--radius);
        padding: 0.875rem 1rem;
        color: var(--ink-light);
        font-weight: 500;
        font-size: 0.925rem;
        transition: background 180ms ease, color 180ms ease, transform 180ms ease;
        border: 1px solid transparent;
        background: transparent;
        width: 100%;
    }

    .submenu-toggle {
        text-align: left;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        cursor: pointer;
    }

    .nav-link:hover,
    .submenu-toggle:hover {
        color: var(--ink);
        background: rgba(102, 126, 234, 0.08);
        transform: translateX(4px);
    }

    .nav-link.active,
    .submenu-toggle.active {
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

    .submenu {
        margin: 0.35rem 0 0.5rem;
        padding-left: 1.75rem;
        gap: 0.35rem;
        overflow: hidden;
        max-height: 0;
        opacity: 0;
        transition: max-height 260ms ease, opacity 200ms ease;
    }

    .submenu .nav-link {
        font-size: 0.875rem;
        padding: 0.75rem 0.85rem;
    }

    .submenu .nav-icon {
        font-size: 1rem;
        opacity: 0.8;
    }

    .nav-item.has-children.is-open > .submenu {
        opacity: 1;
    }

    .submenu-caret {
        transition: transform 200ms ease;
    }

    .nav-item.has-children.is-open > .submenu-toggle .submenu-caret {
        transform: rotate(180deg);
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

    .content {
        transition: margin-left 280ms ease;
    }

    body.has-sidebar .content {
        margin-left: var(--sbw);
    }

    body.sidebar-collapsed .content {
        margin-left: 0;
    }

    .sidebar.is-collapsed ~ .sidebar-toggle + .content {
        margin-left: 0;
    }

    @media (prefers-reduced-motion: reduce) {
        .sidebar,
        .sidebar-toggle,
        .content,
        .submenu,
        .submenu-toggle,
        .nav-link {
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

<div class="sidebar-wrapper" data-powered-by="Claude Code">
    <aside id="sidebar" class="sidebar" aria-label="Ana menü">
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
    <button
        id="sidebarToggle"
        class="sidebar-toggle btn btn-dark"
        type="button"
        aria-label="Menüyü aç/kapat"
        aria-controls="sidebar"
        aria-expanded="true"
    >
        <i class="bi bi-x-lg" aria-hidden="true"></i>
    </button>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
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

        const submenuItems = sidebar.querySelectorAll('.nav-item.has-children');
        const trackedSubmenus = [];

        submenuItems.forEach((item) => {
            const toggle = item.querySelector('.submenu-toggle');
            const submenu = item.querySelector('.submenu');
            if (!toggle || !submenu) {
                return;
            }

            const collapse = () => {
                const height = submenu.scrollHeight;
                submenu.style.maxHeight = height > 0 ? `${height}px` : '0px';
                requestAnimationFrame(() => {
                    submenu.style.maxHeight = '0px';
                });
            };

            const expand = () => {
                submenu.style.maxHeight = `${submenu.scrollHeight}px`;
            };

            const setExpanded = (expanded) => {
                item.classList.toggle('is-open', expanded);
                toggle.classList.toggle('active', expanded);
                toggle.setAttribute('aria-expanded', String(expanded));
                submenu.setAttribute('aria-hidden', String(!expanded));
                if (expanded) {
                    expand();
                } else {
                    collapse();
                }
            };

            submenu.addEventListener('transitionend', (event) => {
                if (event.propertyName !== 'max-height') {
                    return;
                }
                if (item.classList.contains('is-open')) {
                    submenu.style.maxHeight = `${submenu.scrollHeight}px`;
                }
            });

            const initialState = item.classList.contains('is-open');
            submenu.style.maxHeight = initialState ? `${submenu.scrollHeight}px` : '0px';
            toggle.setAttribute('aria-expanded', String(initialState));
            submenu.setAttribute('aria-hidden', String(!initialState));

            toggle.addEventListener('click', () => {
                const next = !item.classList.contains('is-open');
                setExpanded(next);
            });

            toggle.addEventListener('keydown', (event) => {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    toggle.click();
                }
            });

            trackedSubmenus.push({ item, submenu });
        });

        const handleResize = () => {
            trackedSubmenus.forEach(({ item, submenu }) => {
                if (item.classList.contains('is-open')) {
                    submenu.style.maxHeight = `${submenu.scrollHeight}px`;
                }
            });
        };

        window.addEventListener('resize', handleResize);
    });
</script>
