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
$requestUri = $_SERVER['REQUEST_URI'] ?? '';

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

$userFullname = 'Kullanıcı';
$userUsername = 'kullanici';
$userInitials = 'K';

if ($userId > 0) {
    try {
        $stmt = $pdo->prepare('SELECT firstname, surname, username FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $userId]);
        $userRow = $stmt->fetch(PDO::FETCH_ASSOC);

        if (is_array($userRow)) {
            $firstName = trim((string)($userRow['firstname'] ?? ''));
            $surname = trim((string)($userRow['surname'] ?? ''));
            $usernameValue = trim((string)($userRow['username'] ?? ''));

            if ($usernameValue !== '') {
                $userUsername = $usernameValue;
            }

            $nameParts = array_filter([$firstName, $surname], static function (string $part): bool {
                return $part !== '';
            });

            if ($nameParts !== []) {
                $userFullname = implode(' ', $nameParts);
            } elseif ($userUsername !== '') {
                $userFullname = $userUsername;
            }

            $initialsParts = [];
            if ($firstName !== '') {
                $initialsParts[] = function_exists('mb_substr') ? mb_substr($firstName, 0, 1, 'UTF-8') : substr($firstName, 0, 1);
            }
            if ($surname !== '') {
                $initialsParts[] = function_exists('mb_substr') ? mb_substr($surname, 0, 1, 'UTF-8') : substr($surname, 0, 1);
            }

            $initials = implode('', $initialsParts);

            if ($initials === '' && $userUsername !== '') {
                $initials = function_exists('mb_substr') ? mb_substr($userUsername, 0, 1, 'UTF-8') : substr($userUsername, 0, 1);
            }

            if ($initials !== '') {
                $userInitials = function_exists('mb_strtoupper') ? mb_strtoupper($initials, 'UTF-8') : strtoupper($initials);
            }
        }
    } catch (Throwable $e) {
        // Kullanıcı bilgileri alınamadı, varsayılan değerler kullanılacak.
    }
}

$userUsername = $userUsername !== '' ? $userUsername : 'kullanici';
$userFullname = $userFullname !== '' ? $userFullname : 'Kullanıcı';
$userInitials = $userInitials !== '' ? $userInitials : 'K';

// Bildirim özet bilgileri
$notifCount = 0;
$notifications = [];

if ($userId > 0) {
    try {
        $countStmt = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = :id AND is_read = 0');
        $countStmt->execute(['id' => $userId]);
        $notifCount = (int) $countStmt->fetchColumn();

        $listStmt = $pdo->prepare(
            'SELECT title, message, created_at FROM notifications WHERE user_id = :id ORDER BY created_at DESC LIMIT 5'
        );
        $listStmt->execute(['id' => $userId]);
        $notificationRows = $listStmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($notificationRows as $row) {
            $title = trim((string) ($row['title'] ?? ''));
            $message = trim((string) ($row['message'] ?? ''));
            $text = $title !== '' ? $title : ($message !== '' ? $message : 'Yeni bildirim');
            $time = '';

            if (!empty($row['created_at'])) {
                try {
                    $createdAt = new DateTimeImmutable($row['created_at']);
                    $time = $createdAt->format('d.m.Y H:i');
                } catch (Throwable $e) {
                    $time = (string) $row['created_at'];
                }
            }

            $notifications[] = [
                'text' => $text,
                'detail' => $title !== '' && $message !== '' ? $message : '',
                'time' => $time,
            ];
        }
    } catch (Throwable $e) {
        $notifCount = 0;
        $notifications = [];
    }
}

$orderPath = file_exists(__DIR__ . '/../public/order.php') ? '../public/order.php' : '../public/orders.php';
$orderMatch = basename($orderPath);
$settingsPath = file_exists(__DIR__ . '/../public/settings.php') ? '../public/settings.php' : '#';

$vehicleExists = file_exists(__DIR__ . '/../public/vehicle.php');
$vehicleHref = $vehicleExists ? '../public/vehicle.php' : '#';

$shipmentFile = null;
foreach (['shipment.php', 'shipments.php', 'sevkiyat.php'] as $candidate) {
    if (file_exists(__DIR__ . '/../public/' . $candidate)) {
        $shipmentFile = $candidate;
        break;
    }
}

$projectFile = null;
foreach (['projects.php', 'project.php', 'proje.php'] as $candidate) {
    if (file_exists(__DIR__ . '/../public/' . $candidate)) {
        $projectFile = $candidate;
        break;
    }
}

$menuGroups = [
    [
        'title' => 'Genel',
        'items' => [
            [
                'label' => 'Dashboard',
                'icon'  => 'bi-speedometer2',
                'href'  => '../public/dashboard.php',
                'match' => 'dashboard.php',
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
    ],
    [
        'title' => 'Operasyon',
        'items' => [
            [
                'label'     => 'Araçlar',
                'icon'      => 'bi-truck',
                'href'      => $vehicleHref,
                'match'     => $vehicleExists ? 'vehicle.php' : '',
                'match_uri' => $vehicleExists ? 'vehicle.php' : '',
                'children'  => $vehicleExists ? [
                    [
                        'label' => 'Planlanan Güzergah',
                        'href'  => '../public/vehicle.php?view=routes',
                        'match_uri' => 'view=routes',
                    ],
                    [
                        'label' => 'Bakım Takvimi',
                        'href'  => '../public/vehicle.php?view=maintenance',
                        'match_uri' => 'view=maintenance',
                    ],
                ] : [],
            ],
            [
                'label'     => 'Sevkiyatlar',
                'icon'      => 'bi-box-arrow-up-right',
                'href'      => $shipmentFile !== null ? '../public/' . $shipmentFile : ($vehicleExists ? '../public/vehicle.php?view=shipments' : '#'),
                'match'     => $shipmentFile !== null ? $shipmentFile : '',
                'match_uri' => $shipmentFile !== null ? $shipmentFile : ($vehicleExists ? 'view=shipments' : ''),
            ],
            [
                'label' => 'Projeler',
                'icon'  => 'bi-kanban',
                'href'  => $projectFile !== null ? '../public/' . $projectFile : '#',
                'match' => $projectFile !== null ? $projectFile : '',
            ],
        ],
    ],
    [
        'title' => 'Tedarik',
        'items' => [
            [
                'label' => 'Tedarikçi',
                'icon'  => 'bi-people',
                'href'  => '../public/supplier.php',
                'match' => 'supplier.php',
            ],
            [
                'label' => 'Tedarikçi Yetkilisi',
                'icon'  => 'bi-person-lines-fill',
                'href'  => '../public/supplier-contact.php',
                'match' => 'supplier-contact.php',
            ],
        ],
    ],
    [
        'title' => 'Sipariş Yönetimi',
        'items' => [
            [
                'label'     => 'Siparişler',
                'icon'      => 'bi-receipt',
                'href'      => $orderPath,
                'match'     => $orderMatch,
                'match_uri' => $orderMatch,
                'children'  => [
                    [
                        'label' => 'Aktif Siparişler',
                        'href'  => $orderPath . '?view=active',
                        'match_uri' => 'view=active',
                    ],
                    [
                        'label' => 'Arşiv',
                        'href'  => $orderPath . '?view=archived',
                        'match_uri' => 'view=archived',
                    ],
                ],
            ],
        ],
    ],
    [
        'title' => 'Kurumsal',
        'items' => [
            [
                'label' => 'Şirket (company.cs)',
                'icon'  => 'bi-building',
                'href'  => '../public/company.php',
                'match' => 'company.php',
            ],
        ],
    ],
];

if ($role === 'admin') {
    $menuGroups[] = [
        'title' => 'Yönetim',
        'items' => [
            [
                'label' => 'Yönetim Paneli',
                'icon'  => 'bi-shield-lock',
                'href'  => '../public/admin.php',
                'match' => 'admin.php',
            ],
        ],
    ];
}

$csrfToken = $_SESSION['csrf_token'];

$renderMenuItems = static function (array $menuItems, string $active, string $requestUri): string {
    $html = '';
    foreach ($menuItems as $item) {
        $isActive = $active === ($item['match'] ?? '');
        $children = $item['children'] ?? [];
        $hasChildren = is_array($children) && $children !== [];
        $isChildActive = false;
        $matchUri = (string)($item['match_uri'] ?? '');

        if (!$isActive && $matchUri !== '' && $requestUri !== '' && strpos($requestUri, $matchUri) !== false) {
            $isActive = true;
        }

        if ($hasChildren) {
            foreach ($children as $child) {
                $childActive = $active === ($child['match'] ?? '');
                $childMatchUri = (string)($child['match_uri'] ?? '');
                if (!$childActive && $childMatchUri !== '' && $requestUri !== '' && strpos($requestUri, $childMatchUri) !== false) {
                    $childActive = true;
                }
                if ($childActive) {
                    $isChildActive = true;
                }
            }
        }

        if ($isChildActive) {
            $isActive = true;
        }

        $linkClasses = 'nav-link d-flex align-items-center gap-3 position-relative';
        if ($isActive) {
            $linkClasses .= ' active';
        }
        if ($hasChildren) {
            $linkClasses .= ' has-children';
        }

        $icon = htmlspecialchars($item['icon'] ?? '', ENT_QUOTES, 'UTF-8');
        $label = htmlspecialchars($item['label'] ?? '', ENT_QUOTES, 'UTF-8');
        $href = htmlspecialchars($item['href'] ?? '#', ENT_QUOTES, 'UTF-8');

        $html .= '<li class="nav-item mb-1' . ($hasChildren ? ' has-children' : '') . ($isActive ? ' is-active' : '') . '">';
        $html .= '<a class="' . $linkClasses . '" href="' . $href . '" data-powered-by="Claude Code">';
        if ($icon !== '') {
            $html .= '<i class="bi ' . $icon . ' nav-icon" aria-hidden="true"></i>';
        }
        $html .= '<span class="nav-text">' . $label . '</span>';
        if ($hasChildren) {
            $html .= '<button class="submenu-toggle" type="button" aria-label="Alt menüyü aç" aria-expanded="' . (($isActive || $isChildActive) ? 'true' : 'false') . '">';
            $html .= '<i class="bi bi-chevron-down" aria-hidden="true"></i>';
            $html .= '</button>';
        }
        if ($isActive) {
            $html .= '<div class="nav-indicator"></div>';
        }
        $html .= '</a>';

        if ($hasChildren) {
            $html .= '<ul class="submenu-list' . (($isActive || $isChildActive) ? ' open' : '') . '">';
            foreach ($children as $child) {
                $childLabel = htmlspecialchars($child['label'] ?? '', ENT_QUOTES, 'UTF-8');
                $childHref = htmlspecialchars($child['href'] ?? '#', ENT_QUOTES, 'UTF-8');
                $childActive = $active === ($child['match'] ?? '');
                $childMatchUri = (string)($child['match_uri'] ?? '');
                if (!$childActive && $childMatchUri !== '' && $requestUri !== '' && strpos($requestUri, $childMatchUri) !== false) {
                    $childActive = true;
                }
                $childClass = 'submenu-link';
                if ($childActive) {
                    $childClass .= ' active';
                }
                $html .= '<li>';
                $html .= '<a class="' . $childClass . '" href="' . $childHref . '">';
                $html .= '<span>' . $childLabel . '</span>';
                if ($childActive) {
                    $html .= '<i class="bi bi-dot submenu-indicator" aria-hidden="true"></i>';
                }
                $html .= '</a>';
                $html .= '</li>';
            }
            $html .= '</ul>';
        }

        $html .= '</li>';
    }
    return $html;
};

$renderMenu = static function (array $menuGroups, string $active, string $requestUri) use ($renderMenuItems): string {
    $html = '';

    foreach ($menuGroups as $group) {
        $items = $group['items'] ?? [];
        if (!is_array($items) || $items === []) {
            continue;
        }

        $title = trim((string)($group['title'] ?? ''));

        $html .= '<div class="nav-group">';
        if ($title !== '') {
            $html .= '<div class="nav-group-title">' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</div>';
        }
        $html .= '<ul class="nav flex-column nav-group-list">';
        $html .= $renderMenuItems($items, $active, $requestUri);
        $html .= '</ul>';
        $html .= '</div>';
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

    .sidebar {
        overflow-y: auto;
        scrollbar-width: none;
        -ms-overflow-style: none;
    }

    .sidebar::-webkit-scrollbar {
        display: none;
    }

    #sidebar {
        width: 260px;
        height: calc(100vh - 32px);
        position: fixed;
        top: 16px;
        left: 16px;
        background: var(--sidebar-bg);
        box-shadow: var(--sidebar-shadow);
        backdrop-filter: blur(20px);
        border: 1px solid var(--border);
        border-radius: var(--radius-lg);
        z-index: 1000;
        overflow-x: hidden;
        padding-bottom: 0.75rem;
        transform: translateX(0);
        opacity: 1;
        transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1),
                    opacity 0.35s cubic-bezier(0.4, 0, 0.2, 1),
                    width 0.3s ease,
                    padding 0.3s ease;
    }

    .sidebar-header {
        padding: 0.5rem 0.75rem;
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
        font-size: 1.2rem;
        letter-spacing: 0.1em;
        background: var(--primary-gradient);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        display: block;
        margin-bottom: 0.75rem;
        animation: logoShimmer 3s ease-in-out infinite alternate;
    }

    @keyframes logoShimmer {
        0% { filter: brightness(1); }
        100% { filter: brightness(1.2); }
    }

    .sidebar-subtitle {
        color: var(--ink-lighter);
        font-size: 0.75rem;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.1em;
    }

    .sidebar-content {
        padding: 0.75rem;
    }

    .nav-group {
        margin: 0.5rem 0;
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
        margin-bottom: 0.5rem;
        padding: 0 0.5rem;
        position: relative;
    }

    .nav-group-title::after {
        content: '';
        position: absolute;
        bottom: -0.5rem;
        left: 0.5rem;
        right: 0.5rem;
        height: 1px;
        background: linear-gradient(90deg, transparent, var(--border), transparent);
    }

    .nav-group-list {
        margin: 0;
        padding: 0;
    }

    .nav-item {
        margin-bottom: 4px;
    }

    .sidebar .nav {
        margin-bottom: 0.5rem;
    }

    .sidebar .nav-link {
        color: var(--ink-lighter);
        border-radius: var(--radius);
        padding: 0.35rem 0.75rem;
        transition: var(--transition);
        text-decoration: none;
        font-weight: 500;
        font-size: 0.9rem;
        line-height: 1.2;
        position: relative;
        overflow: hidden;
        border: 1px solid transparent;
    }

    .sidebar .nav-link::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(102, 126, 234, 0.1), transparent);
        transition: left 0.5s ease;
    }

    .sidebar .nav-link:hover {
        color: var(--ink);
        background: var(--surface-hover);
        transform: translateX(4px);
        border-color: var(--border);
    }

    .sidebar .nav-link:hover::before {
        left: 100%;
    }

    .sidebar .nav-link:hover .nav-icon {
        transform: scale(1.1);
    }

    .sidebar .nav-link.has-children {
        padding-right: 2.5rem;
    }

    .nav-item.has-children .submenu-toggle {
        position: absolute;
        right: 0.75rem;
        top: 50%;
        transform: translateY(-50%);
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 999px;
        width: 28px;
        height: 28px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: var(--ink-lighter);
        transition: var(--transition);
        cursor: pointer;
    }

    .nav-item.has-children .submenu-toggle:hover {
        color: var(--ink);
        background: var(--surface-hover);
    }

    .nav-item.has-children.is-active .submenu-toggle {
        color: var(--accent-purple);
        border-color: rgba(102, 126, 234, 0.3);
        background: rgba(102, 126, 234, 0.08);
    }

    .nav-item.has-children .submenu-toggle .bi {
        transition: transform 0.3s ease;
        font-size: 0.85rem;
    }

    .nav-item.has-children.submenu-open .submenu-toggle .bi {
        transform: rotate(180deg);
    }

    .submenu-list {
        list-style: none;
        padding: 0.35rem 0 0.35rem 3rem;
        margin: 0;
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.3s ease;
    }

    .submenu-list.open {
        max-height: 480px;
    }

    .submenu-link {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.5rem 0.75rem;
        border-radius: var(--radius);
        color: var(--ink-lighter);
        text-decoration: none;
        font-size: 0.8125rem;
        transition: var(--transition);
        background: transparent;
    }

    .submenu-link:hover {
        background: var(--surface-hover);
        color: var(--ink);
    }

    .submenu-link.active {
        background: rgba(102, 126, 234, 0.12);
        color: var(--ink);
        font-weight: 600;
    }

    .submenu-indicator {
        font-size: 1.25rem;
        color: var(--accent-purple);
    }

    .sidebar .nav-link.active {
        color: var(--ink);
        background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
        border-color: rgba(102, 126, 234, 0.3);
        font-weight: 600;
        transform: translateX(4px);
    }

    .sidebar .nav-link.active .nav-icon {
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
        padding: 0.75rem;
        border-top: 1px solid var(--border);
        margin-top: auto;
        background: var(--surface);
    }

    .account-dropdown .btn {
        color: var(--ink);
        font-weight: 500;
        font-size: 0.9rem;
        display: inline-flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.5rem 0.7rem;
    }

    .account-dropdown .avatar-circle {
        width: 40px;
        height: 40px;
        border-radius: 999px;
        background: rgba(99, 102, 241, 0.12);
        color: rgba(79, 70, 229, 1);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.02em;
    }

    .account-dropdown .meta .name {
        font-weight: 600;
        color: var(--ink);
        line-height: 1.1;
    }

    .account-dropdown .meta .username {
        font-size: 0.75rem;
        color: var(--muted, #64748b);
    }

    .account-dropdown .dropdown-menu {
        border-radius: var(--radius);
        border: 1px solid var(--border);
        box-shadow: 0 12px 32px rgba(15, 23, 42, 0.12);
        padding: 0.35rem 0;
    }

    .account-dropdown .dropdown-item {
        font-size: 0.8rem;
    }

    .account-dropdown .dropdown-item.text-danger {
        color: #ef4444 !important;
    }

    .account-dropdown .dropdown-divider {
        margin: 0.4rem 0;
    }

    .account-dropdown .chev {
        transition: transform 0.2s ease;
    }

    .account-dropdown .btn[aria-expanded='true'] .chev {
        transform: rotate(-180deg);
    }

    .main-with-sidebar {
        margin-left: 292px;
        min-height: 100vh;
        transition: margin-left 0.35s ease;
    }

    #sidebar.collapsed {
        transform: translateX(calc(-100% - 32px));
        opacity: 0;
        pointer-events: none;
    }

    #sidebar.collapsed .submenu-list {
        max-height: 0 !important;
        padding: 0;
        margin: 0;
    }

    body.sidebar-collapsed .main-with-sidebar {
        margin-left: 72px;
    }

    .sidebar-collapse-toggle {
        position: fixed;
        top: 18px;
        left: calc(16px + 260px - 18px);
        z-index: 1100;
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 999px;
        width: 36px;
        height: 36px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: var(--ink-lighter);
        transition: var(--transition);
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
    }

    .sidebar-collapse-toggle:hover {
        color: var(--ink);
        background: var(--surface-hover);
        transform: translateY(-1px);
    }

    body.sidebar-collapsed .sidebar-collapse-toggle {
        left: 16px;
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
            margin-left: 0 !important;
        }
        .sidebar-collapse-toggle {
            display: none;
        }
        .sidebar .nav-link {
            font-size: 0.85rem;
        }
        .sidebar .nav-group-title {
            font-size: 0.7rem;
        }
    }

    /* Scrollbars are hidden via the Claude Code compact sidebar spec */

    /* Bildirim butonu */
    .header-actions {
        position: absolute;
        right: .5rem;
        top: .5rem;
        display: inline-flex;
        gap: .4rem;
    }

    .btn-icon {
        position: relative;
        width: 36px;
        height: 36px;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: var(--surface);
        border: 1px solid var(--border);
        color: var(--ink-lighter);
        transition: var(--transition);
    }

    .btn-icon:hover {
        background: var(--surface-hover);
        color: var(--ink);
        transform: translateY(-1px);
    }

    .badge-dot {
        position: absolute;
        top: 6px;
        right: 6px;
        width: 8px;
        height: 8px;
        border-radius: 999px;
        background: #ef4444;
        box-shadow: 0 0 0 2px #fff;
        display: none;
    }

    .btn-icon.has-unread .badge-dot {
        display: block;
    }

    .dropdown-menu.notif-menu {
        min-width: 280px;
        border-radius: var(--radius);
        border: 1px solid var(--border);
        padding: .35rem;
    }

    .notif-item {
        display: flex;
        flex-direction: column;
        gap: 2px;
        font-size: .9rem;
    }

    .notif-item small {
        color: var(--ink-lighter);
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

<script>
document.addEventListener('DOMContentLoaded', function () {
    const sidebar = document.getElementById('sidebar');
    const collapseToggle = document.querySelector('.sidebar-collapse-toggle');
    const submenuItems = document.querySelectorAll('#sidebar .nav-item.has-children');

    const refreshSubmenus = function () {
        if (!sidebar || sidebar.classList.contains('collapsed')) {
            return;
        }
        submenuItems.forEach(function (item) {
            const list = item.querySelector('.submenu-list');
            if (!list || !list.classList.contains('open')) {
                return;
            }
            list.style.maxHeight = list.scrollHeight + 'px';
            item.classList.add('submenu-open');
        });
    };

    const setSidebarCollapsed = function (collapsed) {
        if (!sidebar) {
            return;
        }
        sidebar.classList.toggle('collapsed', collapsed);
        document.body.classList.toggle('sidebar-collapsed', collapsed);
        sidebar.setAttribute('aria-hidden', collapsed ? 'true' : 'false');

        if (collapseToggle) {
            collapseToggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
            const icon = collapseToggle.querySelector('i');
            const label = collapseToggle.querySelector('.visually-hidden');
            if (icon) {
                icon.classList.toggle('bi-chevron-left', !collapsed);
                icon.classList.toggle('bi-chevron-right', collapsed);
            }
            if (label) {
                label.textContent = collapsed ? 'Menüyü genişlet' : 'Menüyü daralt';
            }
        }

        if (collapsed) {
            submenuItems.forEach(function (item) {
                const list = item.querySelector('.submenu-list');
                if (!list) {
                    return;
                }
                list.style.maxHeight = '0px';
                item.classList.remove('submenu-open');
            });
        } else {
            refreshSubmenus();
        }
    };

    submenuItems.forEach(function (item) {
        const toggle = item.querySelector('.submenu-toggle');
        const list = item.querySelector('.submenu-list');
        if (!toggle || !list) {
            return;
        }

        const isInitiallyOpen = list.classList.contains('open');
        if (isInitiallyOpen) {
            list.style.maxHeight = list.scrollHeight + 'px';
            item.classList.add('submenu-open');
            toggle.setAttribute('aria-expanded', 'true');
        } else {
            list.style.maxHeight = '0px';
            toggle.setAttribute('aria-expanded', 'false');
        }

        toggle.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();
            const shouldOpen = !list.classList.contains('open');
            if (shouldOpen) {
                list.classList.add('open');
                list.style.maxHeight = list.scrollHeight + 'px';
                item.classList.add('submenu-open');
                toggle.setAttribute('aria-expanded', 'true');
            } else {
                list.classList.remove('open');
                list.style.maxHeight = '0px';
                item.classList.remove('submenu-open');
                toggle.setAttribute('aria-expanded', 'false');
            }
        });
    });

    if (collapseToggle && sidebar) {
        collapseToggle.addEventListener('click', function (event) {
            event.preventDefault();
            const collapsed = sidebar.classList.contains('collapsed');
            setSidebarCollapsed(!collapsed);
        });
    }

    window.addEventListener('resize', function () {
        refreshSubmenus();
    });

    const initialCollapsed = (sidebar && sidebar.classList.contains('collapsed')) || document.body.classList.contains('sidebar-collapsed');
    setSidebarCollapsed(Boolean(initialCollapsed));
    refreshSubmenus();
});
</script>

<!-- Desktop Sidebar -->
<aside id="sidebar" class="sidebar d-flex flex-column" data-powered-by="Claude Code">
    <div class="sidebar-header">
        <span class="sidebar-logo">NEXA</span>
        <div class="sidebar-subtitle">Panel</div>

        <!-- SAĞ ÜST AKSİYONLAR -->
        <div class="header-actions">
          <!-- Bildirim butonu -->
          <div class="dropdown">
            <button class="btn-icon <?= ($notifCount>0?'has-unread':'') ?>" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Bildirimler">
              <i class="bi bi-bell"></i>
              <span class="badge-dot" aria-hidden="true"></span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end notif-menu">
              <li class="px-2 py-1">
                <strong>Bildirimler</strong>
                <?php if ($notifCount>0): ?>
                  <span class="badge text-bg-danger ms-2"><?= (int)$notifCount ?></span>
                <?php endif; ?>
              </li>
              <li><hr class="dropdown-divider"></li>

              <?php if (!empty($notifications)): ?>
                <?php foreach ($notifications as $n): ?>
                  <li>
                    <div class="dropdown-item notif-item">
                      <span><?= htmlspecialchars($n['text'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                      <?php if (!empty($n['detail'])): ?>
                        <small class="text-muted"><?= htmlspecialchars($n['detail'], ENT_QUOTES, 'UTF-8') ?></small>
                      <?php endif; ?>
                      <?php if (!empty($n['time'])): ?>
                        <small class="text-muted"><?= htmlspecialchars($n['time'], ENT_QUOTES, 'UTF-8') ?></small>
                      <?php endif; ?>
                    </div>
                  </li>
                <?php endforeach; ?>
              <?php else: ?>
                <li><span class="dropdown-item text-muted">Yeni bildiriminiz yok</span></li>
              <?php endif; ?>

              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item" href="../public/notifications.php">Tümünü görüntüle</a></li>
            </ul>
          </div>
        </div>
    </div>
    
    <div class="sidebar-content flex-grow-1">
        <?= $renderMenu($menuGroups, $active, $requestUri); ?>
    </div>
    
    <div class="sidebar-footer">
        <div class="dropdown w-100 account-dropdown">
            <button class="btn w-100 d-flex align-items-center justify-content-between"
                    type="button" data-bs-toggle="dropdown" aria-expanded="false"
                    style="background:transparent;border:1px solid var(--border);border-radius:var(--radius);padding:.4rem .6rem;">
              <span class="d-inline-flex align-items-center gap-2 text-start">
                <span class="avatar-circle"><?= htmlspecialchars($userInitials, ENT_QUOTES, 'UTF-8') ?></span>
                <span class="meta d-inline-flex flex-column">
                  <span class="name"><?= htmlspecialchars($userFullname, ENT_QUOTES, 'UTF-8') ?></span>
                  <span class="username">@<?= htmlspecialchars($userUsername, ENT_QUOTES, 'UTF-8') ?></span>
                </span>
              </span>
              <i class="bi bi-chevron-down chev"></i>
            </button>

            <ul class="dropdown-menu account-menu w-100">
              <li>
                <a class="dropdown-item small d-flex align-items-center gap-2" href="<?= htmlspecialchars($settingsPath, ENT_QUOTES, 'UTF-8') ?>">
                  <i class="bi bi-gear"></i> Hesap Ayarları
                </a>
              </li>
              <?php if ($role === 'admin'): ?>
              <li>
                <a class="dropdown-item small d-flex align-items-center gap-2" href="../public/admin.php">
                  <i class="bi bi-shield-lock"></i> Yönetim Paneli
                </a>
              </li>
              <?php endif; ?>
              <li><hr class="dropdown-divider"></li>
              <li>
                <form action="../public/auth/logout.php" method="post" class="px-2">
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                  <button type="submit" class="dropdown-item small d-flex align-items-center gap-2 text-danger">
                    <i class="bi bi-box-arrow-right"></i> Çıkış Yap
                  </button>
                </form>
              </li>
            </ul>
          </div>
    </div>
</aside>

<button class="sidebar-collapse-toggle d-none d-lg-inline-flex" type="button" aria-controls="sidebar" aria-expanded="true">
    <i class="bi bi-chevron-left"></i>
    <span class="visually-hidden">Menüyü daralt</span>
</button>

<!-- Mobile Offcanvas -->
<div class="offcanvas offcanvas-start" tabindex="-1" id="sidebarOffcanvas" aria-labelledby="sidebarOffcanvasLabel" data-powered-by="Claude Code">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title visually-hidden" id="sidebarOffcanvasLabel">Menü</h5>
        <div>
            <span class="sidebar-logo">NEXA</span>
            <div class="sidebar-subtitle">Panel</div>
        </div>

        <div class="d-inline-flex align-items-center gap-2">
            <!-- Bildirim butonu (mobil) -->
            <div class="dropdown">
              <button class="btn-icon <?= ($notifCount>0?'has-unread':'') ?>" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Bildirimler">
                <i class="bi bi-bell"></i>
                <span class="badge-dot" aria-hidden="true"></span>
              </button>
              <ul class="dropdown-menu dropdown-menu-end notif-menu">
                <!-- Aynı içerik -->
                <li class="px-2 py-1">
                  <strong>Bildirimler</strong>
                  <?php if ($notifCount>0): ?>
                    <span class="badge text-bg-danger ms-2"><?= (int)$notifCount ?></span>
                  <?php endif; ?>
                </li>
                <li><hr class="dropdown-divider"></li>
        <?php if (!empty($notifications)): ?>
          <?php foreach ($notifications as $n): ?>
            <li>
              <div class="dropdown-item notif-item">
                <span><?= htmlspecialchars($n['text'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                <?php if (!empty($n['detail'])): ?><small class="text-muted"><?= htmlspecialchars($n['detail'], ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?>
                <?php if (!empty($n['time'])): ?><small class="text-muted"><?= htmlspecialchars($n['time'], ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?>
              </div>
            </li>
          <?php endforeach; ?>
        <?php else: ?>
                  <li><span class="dropdown-item text-muted">Yeni bildiriminiz yok</span></li>
                <?php endif; ?>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="../public/notifications.php">Tümünü görüntüle</a></li>
              </ul>
            </div>

            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Kapat"></button>
        </div>
    </div>
    <div class="offcanvas-body sidebar d-flex flex-column">
        <div class="flex-grow-1">
            <?= $renderMenu($menuGroups, $active, $requestUri); ?>
        </div>
        <div class="sidebar-footer">
          <div class="dropdown w-100 account-dropdown">
            <button class="btn w-100 d-flex align-items-center justify-content-between"
                    type="button" data-bs-toggle="dropdown" aria-expanded="false"
                    style="background:transparent;border:1px solid var(--border);border-radius:var(--radius);padding:.5rem .7rem;">
              <span class="d-inline-flex align-items-center gap-2 text-start">
                <span class="avatar-circle"><?= htmlspecialchars($userInitials, ENT_QUOTES, 'UTF-8') ?></span>
                <span class="meta d-inline-flex flex-column">
                  <span class="name"><?= htmlspecialchars($userFullname, ENT_QUOTES, 'UTF-8') ?></span>
                  <span class="username">@<?= htmlspecialchars($userUsername, ENT_QUOTES, 'UTF-8') ?></span>
                </span>
              </span>
              <i class="bi bi-chevron-down chev"></i>
            </button>

            <ul class="dropdown-menu account-menu w-100">
              <li>
                <a class="dropdown-item small d-flex align-items-center gap-2" href="<?= htmlspecialchars($settingsPath, ENT_QUOTES, 'UTF-8') ?>">
                  <i class="bi bi-gear"></i> Hesap Ayarları
                </a>
              </li>
              <?php if ($role === 'admin'): ?>
              <li>
                <a class="dropdown-item small d-flex align-items-center gap-2" href="../public/admin.php">
                  <i class="bi bi-shield-lock"></i> Yönetim Paneli
                </a>
              </li>
              <?php endif; ?>
              <li><hr class="dropdown-divider"></li>
              <li>
                <form action="../public/auth/logout.php" method="post" class="px-2">
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                  <button type="submit" class="dropdown-item small d-flex align-items-center gap-2 text-danger">
                    <i class="bi bi-box-arrow-right"></i> Çıkış Yap
                  </button>
                </form>
              </li>
            </ul>
          </div>
        </div>
    </div>
</div>
