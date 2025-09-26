<?php
declare(strict_types=1);

session_start();

if (!function_exists('setFlashMessage')) {
    /**
     * Basit flash mesaj kurucu.
     */
    function setFlashMessage(string $type, string $message): void
    {
        $map = [
            'success' => 'flash_success',
            'error'   => 'flash_error',
            'warning' => 'flash_warning',
        ];

        if (!isset($map[$type])) {
            return;
        }

        foreach ($map as $sessionKey) {
            if ($sessionKey !== $map[$type]) {
                unset($_SESSION[$sessionKey]);
            }
        }

        $_SESSION[$map[$type]] = $message;
    }
}

if (empty($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit;
}

require_once __DIR__ . '/../config.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (!function_exists('str_starts_with')) {
    function str_starts_with(string $haystack, string $needle): bool
    {
        if ($needle === '') {
            return true;
        }

        return strpos($haystack, $needle) === 0;
    }
}

/**
 * Oturumun yönetici haklarına sahip olup olmadığını kontrol eder.
 */
function sessionHasAdminPrivileges(): bool
{
    $adminFlags = [
        'is_admin',
        'user_is_admin',
        'is_super_admin',
        'isSuperAdmin',
    ];

    foreach ($adminFlags as $flag) {
        if (isset($_SESSION[$flag]) && filter_var($_SESSION[$flag], FILTER_VALIDATE_BOOLEAN)) {
            return true;
        }
    }

    $roleCandidates = [];

    if (isset($_SESSION['role']) && is_string($_SESSION['role'])) {
        $candidate = strtolower(trim($_SESSION['role']));
        if ($candidate !== '') {
            $roleCandidates[] = $candidate;
        }
    }

    if (isset($_SESSION['user_role']) && is_string($_SESSION['user_role'])) {
        $candidate = strtolower(trim($_SESSION['user_role']));
        if ($candidate !== '') {
            $roleCandidates[] = $candidate;
        }
    }

    if (isset($_SESSION['roles']) && is_array($_SESSION['roles'])) {
        foreach ($_SESSION['roles'] as $roleValue) {
            $candidate = strtolower(trim((string) $roleValue));
            if ($candidate === '') {
                continue;
            }
            if (!in_array($candidate, $roleCandidates, true)) {
                $roleCandidates[] = $candidate;
            }
        }
    }

    if (in_array('admin', $roleCandidates, true) || in_array('superadmin', $roleCandidates, true)) {
        return true;
    }

    if (isset($_SESSION['permissions']) && is_array($_SESSION['permissions'])) {
        foreach ($_SESSION['permissions'] as $permission) {
            $normalized = strtolower(trim((string) $permission));
            if ($normalized === '' || $normalized === '0') {
                continue;
            }
            if ($normalized === '*' || $normalized === 'admin:*' || str_starts_with($normalized, 'admin.')) {
                return true;
            }
        }
    }

    return false;
}

function redirectWithFlash(string $type, string $message): void
{
    setFlashMessage($type, $message);
    header('Location: admin.php');
    exit;
}

if (!sessionHasAdminPrivileges()) {
    setFlashMessage('error', 'Yönetim paneline erişim yetkiniz bulunmuyor.');
    header('Location: dashboard.php');
    exit;
}

$csrfToken = $_SESSION['csrf_token'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedToken = (string) ($_POST['csrf_token'] ?? '');

    if ($postedToken === '' || !hash_equals($csrfToken, $postedToken)) {
        redirectWithFlash('error', 'Geçersiz güvenlik doğrulaması. Lütfen formu yeniden gönderin.');
    }

    $action = (string) ($_POST['action'] ?? '');

    switch ($action) {
        case 'create_role':
            $name = trim((string) ($_POST['role_name'] ?? ''));
            $description = trim((string) ($_POST['role_description'] ?? ''));

            if ($name === '') {
                redirectWithFlash('error', 'Rol adı boş bırakılamaz.');
            }

            try {
                $stmt = $pdo->prepare('INSERT INTO roles (name, description) VALUES (:name, :description)');
                $stmt->execute([
                    ':name' => $name,
                    ':description' => $description !== '' ? $description : null,
                ]);

                redirectWithFlash('success', sprintf('"%s" rolü başarıyla oluşturuldu.', $name));
            } catch (PDOException $e) {
                $errorInfo = $e->errorInfo[1] ?? null;

                if ($errorInfo === 1062) {
                    redirectWithFlash('error', 'Bu ada sahip bir rol zaten mevcut. Lütfen farklı bir ad seçin.');
                }

                error_log('Admin panel role create failed: ' . $e->getMessage());
                redirectWithFlash('error', 'Rol kaydedilirken bir hata oluştu. Lütfen daha sonra tekrar deneyin.');
            }

            break;

        case 'update_role':
            $roleId = filter_var($_POST['role_id'] ?? null, FILTER_VALIDATE_INT);

            if (!$roleId) {
                redirectWithFlash('error', 'Güncelleme için geçerli bir rol seçmelisiniz.');
            }

            try {
                $currentStmt = $pdo->prepare('SELECT name, description FROM roles WHERE id = :id');
                $currentStmt->execute([':id' => $roleId]);
                $currentRole = $currentStmt->fetch(PDO::FETCH_ASSOC);

                if ($currentRole === false) {
                    redirectWithFlash('error', 'Seçtiğiniz rol bulunamadı.');
                }

                $name = trim((string) ($_POST['role_name'] ?? ''));
                $description = trim((string) ($_POST['role_description'] ?? ''));

                if ($name === '') {
                    $name = (string) $currentRole['name'];
                }

                if ($description === '') {
                    $description = (string) ($currentRole['description'] ?? '');
                }

                $updateStmt = $pdo->prepare('UPDATE roles SET name = :name, description = :description WHERE id = :id');
                $updateStmt->execute([
                    ':name' => $name,
                    ':description' => $description !== '' ? $description : null,
                    ':id' => $roleId,
                ]);

                if ($updateStmt->rowCount() === 0) {
                    redirectWithFlash('success', 'Rol bilgilerinde değişiklik yapılmadı.');
                }

                redirectWithFlash('success', 'Rol bilgileri başarıyla güncellendi.');
            } catch (PDOException $e) {
                $errorInfo = $e->errorInfo[1] ?? null;

                if ($errorInfo === 1062) {
                    redirectWithFlash('error', 'Bu ada sahip başka bir rol bulunuyor. Lütfen farklı bir ad belirleyin.');
                }

                error_log('Admin panel role update failed: ' . $e->getMessage());
                redirectWithFlash('error', 'Rol güncellenirken bir hata oluştu.');
            }

            break;

        case 'delete_role':
            $roleId = filter_var($_POST['role_id'] ?? null, FILTER_VALIDATE_INT);

            if (!$roleId) {
                redirectWithFlash('error', 'Silme işlemi için geçerli bir rol seçmelisiniz.');
            }

            try {
                $pdo->beginTransaction();
                $deleteStmt = $pdo->prepare('DELETE FROM roles WHERE id = :id');
                $deleteStmt->execute([':id' => $roleId]);

                if ($deleteStmt->rowCount() === 0) {
                    $pdo->rollBack();
                    redirectWithFlash('error', 'Silmek istediğiniz rol bulunamadı.');
                }

                $pdo->commit();
                redirectWithFlash('success', 'Rol başarıyla silindi.');
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                error_log('Admin panel role delete failed: ' . $e->getMessage());
                redirectWithFlash('error', 'Rol silinirken bir hata oluştu.');
            }

            break;

        default:
            redirectWithFlash('error', 'Desteklenmeyen bir işlem istendi.');
    }
}

$stats = [
    'users_total'        => 0,
    'admins_total'       => 0,
    'roles_total'        => 0,
    'permissions_total'  => 0,
    'notifications_open' => 0,
];

$adminUsers = [];
$roleMatrix = [];
$usersWithoutRole = [];
$recentNotifications = [];
$criticalActions = [
    [
        'label' => 'Şirket Kaydını Yönet',
        'description' => 'Şirket bilgilerini güncelleyin veya kayıtları silin.',
        'href' => 'company.php',
        'icon' => 'building-up'
    ],
    [
        'label' => 'Fiyat Kataloğu',
        'description' => 'Fiyat ve sözleşme kayıtlarını güncel tutun.',
        'href' => 'price.php',
        'icon' => 'tags'
    ],
    [
        'label' => 'Sipariş Yönetimi',
        'description' => 'Kritik sipariş ve sevkiyat adımlarını kontrol edin.',
        'href' => 'order.php',
        'icon' => 'clipboard-data'
    ],
];

try {
    $stats['users_total'] = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
} catch (PDOException $e) {
    error_log('Admin panel user count failed: ' . $e->getMessage());
}

try {
    $stats['roles_total'] = (int) $pdo->query('SELECT COUNT(*) FROM roles')->fetchColumn();
} catch (PDOException $e) {
    error_log('Admin panel role count failed: ' . $e->getMessage());
}

try {
    $stats['permissions_total'] = (int) $pdo->query('SELECT COUNT(*) FROM permissions')->fetchColumn();
} catch (PDOException $e) {
    error_log('Admin panel permission count failed: ' . $e->getMessage());
}

try {
    $stats['notifications_open'] = (int) $pdo->query('SELECT COUNT(*) FROM notifications WHERE is_read = 0')->fetchColumn();
} catch (PDOException $e) {
    error_log('Admin panel notifications count failed: ' . $e->getMessage());
}

try {
    $adminQuery = $pdo->query(
        'SELECT u.id, u.firstname, u.surname, u.username, u.email, GROUP_CONCAT(r.name ORDER BY r.name SEPARATOR ", ") AS roles
         FROM users u
         JOIN user_roles ur ON ur.user_id = u.id
         JOIN roles r ON r.id = ur.role_id
         GROUP BY u.id
         HAVING SUM(CASE WHEN LOWER(r.name) = "admin" THEN 1 ELSE 0 END) > 0
         ORDER BY u.firstname, u.surname'
    );
    $adminUsers = $adminQuery->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $stats['admins_total'] = count($adminUsers);
} catch (PDOException $e) {
    error_log('Admin panel admin users query failed: ' . $e->getMessage());
    $adminUsers = [];
}

try {
    $matrixStmt = $pdo->query(
        'SELECT r.id, r.name, r.description,
                SUM(CASE WHEN rp.granted = 1 THEN 1 ELSE 0 END) AS granted_count,
                COUNT(rp.permission_id) AS relation_count
         FROM roles r
         LEFT JOIN role_permissions rp ON rp.role_id = r.id
         GROUP BY r.id
         ORDER BY r.name ASC'
    );
    $roleMatrix = $matrixStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (PDOException $e) {
    error_log('Admin panel role matrix query failed: ' . $e->getMessage());
    $roleMatrix = [];
}

try {
    $userWithoutRoleStmt = $pdo->query(
        'SELECT u.id, u.firstname, u.surname, u.username, u.email
         FROM users u
         LEFT JOIN user_roles ur ON ur.user_id = u.id
         WHERE ur.role_id IS NULL
         ORDER BY u.id ASC
         LIMIT 10'
    );
    $usersWithoutRole = $userWithoutRoleStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (PDOException $e) {
    error_log('Admin panel users without role query failed: ' . $e->getMessage());
    $usersWithoutRole = [];
}

try {
    $notificationsStmt = $pdo->prepare(
        'SELECT n.title, n.message, n.created_at, u.username
         FROM notifications n
         LEFT JOIN users u ON u.id = n.user_id
         ORDER BY n.created_at DESC
         LIMIT 5'
    );
    $notificationsStmt->execute();
    $recentNotifications = $notificationsStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (PDOException $e) {
    error_log('Admin panel recent notifications query failed: ' . $e->getMessage());
    $recentNotifications = [];
}

$alerts = [];

if ($stats['roles_total'] === 0) {
    $alerts[] = 'Henüz tanımlanmış bir rol bulunmuyor. Yetki yönetimi için rol ekleyin.';
}

if ($stats['permissions_total'] === 0) {
    $alerts[] = 'İzin tablosu boş görünüyor. Kritik aksiyonları sınırlandırmak için izinleri tanımlayın.';
}

if ($stats['admins_total'] === 0) {
    $alerts[] = 'Herhangi bir kullanıcıya "admin" rolü atanmamış. En az bir yönetici belirleyin.';
}

if ($stats['notifications_open'] > 0) {
    $alerts[] = sprintf('Okunmamış %d bildirim bulunuyor.', $stats['notifications_open']);
}

function esc(?string $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function formatFullname(array $row): string
{
    $first = trim((string) ($row['firstname'] ?? ''));
    $last  = trim((string) ($row['surname'] ?? ''));

    $parts = array_filter([$first, $last], static fn ($part) => $part !== '');

    if ($parts !== []) {
        return implode(' ', $parts);
    }

    $username = trim((string) ($row['username'] ?? ''));
    return $username !== '' ? $username : 'Kullanıcı';
}

function formatDate(?string $value): string
{
    if ($value === null || trim($value) === '') {
        return '—';
    }

    try {
        $date = new DateTimeImmutable($value);
        return $date->format('d.m.Y H:i');
    } catch (Exception $e) {
        return esc($value);
    }
}

?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Yönetim Paneli — Nexa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', Roboto, Arial, sans-serif;
            background: #f3f4f6;
            color: #0f172a;
        }

        main.main-with-sidebar {
            min-height: 100vh;
            padding-bottom: 4rem;
        }

        .stat-card {
            border-radius: 16px;
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.08), rgba(37, 99, 235, 0.02));
            border: 1px solid rgba(37, 99, 235, 0.1);
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 16px 35px rgba(15, 23, 42, 0.12);
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(37, 99, 235, 0.12);
            color: #2563eb;
            font-size: 1.6rem;
        }

        .table thead th {
            font-weight: 600;
            color: #475569;
            border-bottom: 2px solid rgba(15, 23, 42, 0.08);
        }

        .table tbody td {
            vertical-align: middle;
            color: #1f2937;
        }

        .role-pill {
            background: rgba(37, 99, 235, 0.08);
            color: #2563eb;
            border-radius: 999px;
            padding: 0.25rem 0.75rem;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .alert-card {
            border-radius: 14px;
            background: #fff7ed;
            border: 1px solid rgba(234, 88, 12, 0.2);
        }

        .quick-action-card {
            border-radius: 16px;
            background: #ffffff;
            border: 1px solid rgba(148, 163, 184, 0.15);
            transition: transform 0.2s ease, border-color 0.2s ease;
        }

        .quick-action-card:hover {
            transform: translateY(-4px);
            border-color: rgba(37, 99, 235, 0.3);
        }

        .timeline-item {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            position: relative;
        }

        .timeline-item + .timeline-item {
            margin-top: 1rem;
        }

        .timeline-icon {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #2563eb;
            margin-top: 0.4rem;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.12);
        }

        .timeline-content {
            flex: 1;
        }

        .timeline-meta {
            font-size: 0.8rem;
            color: #64748b;
        }
    </style>
</head>
<body>
<?php require_once __DIR__ . '/../partials/flash.php'; ?>
<div class="d-flex">
    <?php require_once __DIR__ . '/../component/sidebar.php'; ?>
    <main class="main-with-sidebar flex-grow-1 p-4">
        <div class="container-fluid">
            <header class="mb-4">
                <h1 class="h3 mb-1">Yönetim Paneli</h1>
                <p class="text-muted mb-0">Yetki matrisini, kritik aksiyonları ve yönetici hesaplarını tek ekrandan takip edin.</p>
            </header>

            <?php if ($alerts !== []): ?>
                <section class="alert-card p-4 mb-4">
                    <div class="d-flex align-items-start">
                        <div class="me-3 text-warning fs-3"><i class="bi bi-shield-exclamation"></i></div>
                        <div>
                            <h2 class="h5 mb-2">Dikkat edilmesi gerekenler</h2>
                            <ul class="mb-0 ps-3">
                                <?php foreach ($alerts as $alert): ?>
                                    <li class="mb-1"><?= esc($alert); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </section>
            <?php endif; ?>

            <section class="row g-3 mb-4">
                <div class="col-12 col-md-6 col-xl-3">
                    <div class="stat-card p-4 h-100">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="stat-icon"><i class="bi bi-people"></i></div>
                            <span class="badge bg-primary-subtle text-primary">Toplam</span>
                        </div>
                        <h2 class="display-6 fw-semibold mb-0 mt-3"><?= number_format($stats['users_total']); ?></h2>
                        <p class="text-muted mb-0">Kayıtlı kullanıcı</p>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-3">
                    <div class="stat-card p-4 h-100">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="stat-icon"><i class="bi bi-shield-lock"></i></div>
                            <span class="badge bg-success-subtle text-success">Yetkili</span>
                        </div>
                        <h2 class="display-6 fw-semibold mb-0 mt-3"><?= number_format($stats['admins_total']); ?></h2>
                        <p class="text-muted mb-0">Admin rolüne sahip kullanıcı</p>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-3">
                    <div class="stat-card p-4 h-100">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="stat-icon"><i class="bi bi-diagram-3"></i></div>
                            <span class="badge bg-info-subtle text-info">Roller</span>
                        </div>
                        <h2 class="display-6 fw-semibold mb-0 mt-3"><?= number_format($stats['roles_total']); ?></h2>
                        <p class="text-muted mb-0">Tanımlı rol kaydı</p>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-3">
                    <div class="stat-card p-4 h-100">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="stat-icon"><i class="bi bi-key"></i></div>
                            <span class="badge bg-warning-subtle text-warning">Güvenlik</span>
                        </div>
                        <h2 class="display-6 fw-semibold mb-0 mt-3"><?= number_format($stats['permissions_total']); ?></h2>
                        <p class="text-muted mb-0">Tanımlı izin</p>
                    </div>
                </div>
            </section>

            <section class="row g-4 mb-4">
                <div class="col-12 col-xl-7">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-header bg-white d-flex align-items-center justify-content-between">
                            <div>
                                <h2 class="h5 mb-0">Yönetici Hesapları</h2>
                                <small class="text-muted">Admin rolüne sahip kullanıcılar ve rol özetleri</small>
                            </div>
                            <span class="badge bg-primary-subtle text-primary"><?= number_format($stats['admins_total']); ?> kullanıcı</span>
                        </div>
                        <div class="card-body p-0">
                            <?php if ($adminUsers === []): ?>
                                <div class="p-4 text-center text-muted">Henüz admin rolü atanmış bir kullanıcı bulunmuyor.</div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table align-middle mb-0">
                                        <thead>
                                            <tr>
                                                <th scope="col">Kullanıcı</th>
                                                <th scope="col">E-posta</th>
                                                <th scope="col">Roller</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($adminUsers as $admin): ?>
                                                <tr>
                                                    <td>
                                                        <div class="fw-semibold"><?= esc(formatFullname($admin)); ?></div>
                                                        <div class="text-muted">@<?= esc((string) ($admin['username'] ?? '')); ?></div>
                                                    </td>
                                                    <td><?= esc((string) ($admin['email'] ?? '—')); ?></td>
                                                    <td>
                                                        <?php $roleNames = explode(', ', (string) ($admin['roles'] ?? '')); ?>
                                                        <div class="d-flex flex-wrap gap-2">
                                                            <?php foreach ($roleNames as $roleName): ?>
                                                                <?php $trimmed = trim($roleName); if ($trimmed === '') { continue; } ?>
                                                                <span class="role-pill"><?= esc(ucfirst($trimmed)); ?></span>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-xl-5">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-header bg-white">
                            <h2 class="h5 mb-0">Hızlı Aksiyonlar</h2>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <?php foreach ($criticalActions as $action): ?>
                                    <div class="col-12">
                                        <a class="text-decoration-none text-reset" href="<?= esc($action['href']); ?>">
                                            <div class="quick-action-card p-3 h-100">
                                                <div class="d-flex align-items-center justify-content-between">
                                                    <div>
                                                        <h3 class="h6 mb-1"><?= esc($action['label']); ?></h3>
                                                        <p class="text-muted mb-0 small"><?= esc($action['description']); ?></p>
                                                    </div>
                                                    <div class="fs-3 text-primary"><i class="bi bi-<?= esc($action['icon']); ?>"></i></div>
                                                </div>
                                            </div>
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="row g-4">
                <div class="col-12 col-xl-7">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white d-flex align-items-center justify-content-between">
                            <div>
                                <h2 class="h5 mb-0">Rol ve İzin Matrisi</h2>
                                <small class="text-muted">Rollerin sahip olduğu izin sayıları</small>
                            </div>
                            <span class="badge bg-info-subtle text-info"><?= number_format($stats['roles_total']); ?> rol</span>
                        </div>
                        <div class="card-body p-0">
                            <?php if ($roleMatrix === []): ?>
                                <div class="p-4 text-center text-muted">Rol kaydı bulunamadı. Yeni roller ekleyerek yetkileri yönetin.</div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table align-middle mb-0">
                                        <thead>
                                            <tr>
                                                <th scope="col">Rol</th>
                                                <th scope="col">Açıklama</th>
                                                <th scope="col" class="text-center">Atanan İzin</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($roleMatrix as $role): ?>
                                                <tr>
                                                    <td class="fw-semibold"><?= esc((string) ($role['name'] ?? 'Rol')); ?></td>
                                                    <td><?= esc((string) ($role['description'] ?? '—')); ?></td>
                                                    <td class="text-center">
                                                        <span class="badge bg-primary-subtle text-primary">
                                                            <?= number_format((int) ($role['granted_count'] ?? 0)); ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card shadow-sm border-0 mt-4">
                        <div class="card-header bg-white">
                            <h2 class="h5 mb-0">Rol Yönetimi İşlemleri</h2>
                        </div>
                        <div class="card-body">
                            <div class="row g-4">
                                <div class="col-12">
                                    <h3 class="h6 text-uppercase text-muted mb-2">Rol Ekle</h3>
                                    <form method="post" class="row g-2 align-items-end">
                                        <input type="hidden" name="csrf_token" value="<?= esc($csrfToken); ?>">
                                        <input type="hidden" name="action" value="create_role">
                                        <div class="col-12 col-md-4">
                                            <label class="form-label small text-muted" for="role_name_create">Rol Adı</label>
                                            <input type="text" name="role_name" id="role_name_create" class="form-control form-control-sm" placeholder="Örn. editor" required>
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <label class="form-label small text-muted" for="role_desc_create">Açıklama</label>
                                            <input type="text" name="role_description" id="role_desc_create" class="form-control form-control-sm" placeholder="Rolün amacı (isteğe bağlı)">
                                        </div>
                                        <div class="col-12 col-md-2 d-grid">
                                            <button type="submit" class="btn btn-sm btn-primary">Ekle</button>
                                        </div>
                                    </form>
                                </div>
                                <div class="col-12">
                                    <h3 class="h6 text-uppercase text-muted mb-2">Rol Güncelle</h3>
                                    <?php if ($roleMatrix === []): ?>
                                        <p class="text-muted small mb-0">Önce en az bir rol oluşturmalısınız.</p>
                                    <?php else: ?>
                                        <form method="post" class="row g-2 align-items-end">
                                            <input type="hidden" name="csrf_token" value="<?= esc($csrfToken); ?>">
                                            <input type="hidden" name="action" value="update_role">
                                            <div class="col-12 col-md-4">
                                                <label class="form-label small text-muted" for="role_id_update">Rol Seçin</label>
                                                <select class="form-select form-select-sm" id="role_id_update" name="role_id" required>
                                                    <option value="" selected disabled>Rol seçin</option>
                                                    <?php foreach ($roleMatrix as $role): ?>
                                                        <option value="<?= (int) $role['id']; ?>"><?= esc((string) ($role['name'] ?? 'Rol')); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-12 col-md-4">
                                                <label class="form-label small text-muted" for="role_name_update">Yeni Rol Adı</label>
                                                <input type="text" name="role_name" id="role_name_update" class="form-control form-control-sm" placeholder="Değiştirmek istemezseniz boş bırakın">
                                            </div>
                                            <div class="col-12 col-md-3">
                                                <label class="form-label small text-muted" for="role_desc_update">Yeni Açıklama</label>
                                                <input type="text" name="role_description" id="role_desc_update" class="form-control form-control-sm" placeholder="Boş bırakılabilir">
                                            </div>
                                            <div class="col-12 col-md-1 d-grid">
                                                <button type="submit" class="btn btn-sm btn-outline-primary">Kaydet</button>
                                            </div>
                                        </form>
                                        <p class="text-muted small mb-0">Boş bıraktığınız alanlar mevcut değerler ile korunur.</p>
                                    <?php endif; ?>
                                </div>
                                <div class="col-12">
                                    <h3 class="h6 text-uppercase text-muted mb-2">Rol Sil</h3>
                                    <?php if ($roleMatrix === []): ?>
                                        <p class="text-muted small mb-0">Silinecek herhangi bir rol bulunmuyor.</p>
                                    <?php else: ?>
                                        <form method="post" class="row g-2 align-items-end">
                                            <input type="hidden" name="csrf_token" value="<?= esc($csrfToken); ?>">
                                            <input type="hidden" name="action" value="delete_role">
                                            <div class="col-12 col-md-6">
                                                <label class="form-label small text-muted" for="role_id_delete">Rol Seçin</label>
                                                <select class="form-select form-select-sm" id="role_id_delete" name="role_id" required>
                                                    <option value="" selected disabled>Rol seçin</option>
                                                    <?php foreach ($roleMatrix as $role): ?>
                                                        <option value="<?= (int) $role['id']; ?>"><?= esc((string) ($role['name'] ?? 'Rol')); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-12 col-md-3">
                                                <span class="small text-muted">Silinen role bağlı izin ve atamalar da kaldırılacaktır.</span>
                                            </div>
                                            <div class="col-12 col-md-3 d-grid">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Seçilen rolü silmek istediğinize emin misiniz?');">Rolü Sil</button>
                                            </div>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-xl-5">
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-white">
                            <h2 class="h5 mb-0">Rol Ataması Bekleyen Kullanıcılar</h2>
                        </div>
                        <div class="card-body">
                            <?php if ($usersWithoutRole === []): ?>
                                <p class="text-muted mb-0">Tüm kullanıcılara en az bir rol atanmış görünüyor.</p>
                            <?php else: ?>
                                <ul class="list-unstyled mb-0">
                                    <?php foreach ($usersWithoutRole as $user): ?>
                                        <li class="mb-3">
                                            <div class="fw-semibold"><?= esc(formatFullname($user)); ?></div>
                                            <div class="text-muted small">@<?= esc((string) ($user['username'] ?? '')); ?> — <?= esc((string) ($user['email'] ?? '')); ?></div>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white d-flex align-items-center justify-content-between">
                            <h2 class="h5 mb-0">Son Sistem Kayıtları</h2>
                            <span class="badge bg-secondary-subtle text-secondary">5 kayıt</span>
                        </div>
                        <div class="card-body">
                            <?php if ($recentNotifications === []): ?>
                                <p class="text-muted mb-0">Henüz bildirilen bir etkinlik yok.</p>
                            <?php else: ?>
                                <div class="d-flex flex-column">
                                    <?php foreach ($recentNotifications as $note): ?>
                                        <div class="timeline-item">
                                            <div class="timeline-icon"></div>
                                            <div class="timeline-content">
                                                <div class="fw-semibold"><?= esc((string) ($note['title'] ?: ($note['message'] ?? 'Bildiriminiz var'))); ?></div>
                                                <?php if (!empty($note['message']) && (!isset($note['title']) || $note['title'] === '')): ?>
                                                    <p class="text-muted small mb-1"><?= esc((string) $note['message']); ?></p>
                                                <?php elseif (!empty($note['message'])): ?>
                                                    <p class="text-muted small mb-1"><?= esc((string) $note['message']); ?></p>
                                                <?php endif; ?>
                                                <div class="timeline-meta">
                                                    <?= esc('@' . ($note['username'] ?? 'sistem')); ?> — <?= formatDate($note['created_at'] ?? null); ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </main>
</div>
</body>
</html>
