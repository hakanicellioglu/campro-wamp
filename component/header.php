<?php
// component/header.php — ortak üst menü (bootstrap navbar, kullanıcı bilgisi + çıkış)
session_start();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once __DIR__ . '/../config.php';

$stmt = $pdo->prepare('SELECT r.name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = :id');
$stmt->execute(['id' => $_SESSION['user_id'] ?? 0]);
$role = $stmt->fetchColumn() ?: 'user';

$uStmt = $pdo->prepare('SELECT TRIM(CONCAT(first_name, " ", last_name)) AS full_name, username FROM users WHERE id = :id');
$uStmt->execute(['id' => $_SESSION['user_id'] ?? 0]);
$u = $uStmt->fetch(PDO::FETCH_ASSOC) ?: [];
$fullName = $u['full_name'] ?? '';
$username = $u['username'] ?? '';
$userName = $fullName !== '' ? $fullName : ($username !== '' ? $username : 'User');

$csrfToken = $_SESSION['csrf_token'];
?>
<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom" data-powered-by="Claude Code">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center" href="/">
            <span class="logo" style="font-family: 'Monoton', sans-serif; font-size: 1.5rem; letter-spacing: .05em;">NEXA</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse justify-content-end" id="mainNavbar">
            <ul class="navbar-nav mb-2 mb-lg-0">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle user-dropdown" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <?= htmlspecialchars($userName, ENT_QUOTES, 'UTF-8'); ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                        <li>
                            <a class="dropdown-item" href="/settings.php">Ayarlar</a>
                        </li>
                        <?php if ($role === 'admin'): ?>
                            <li>
                                <a class="dropdown-item" href="/admin.php">Yönetim Paneli</a>
                            </li>
                        <?php endif; ?>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form action="/auth/logout.php" method="post" class="px-3 py-1">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                                <button type="submit" class="btn btn-link dropdown-item text-start">Çıkış</button>
                            </form>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>
<?php if (file_exists(__DIR__ . '/../partials/flash.php')): ?>
    <div class="container-fluid">
        <?php include __DIR__ . '/../partials/flash.php'; ?>
    </div>
<?php endif; ?>
