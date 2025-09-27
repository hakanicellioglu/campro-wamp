<?php
declare(strict_types=1);

session_start();

if (empty($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

require_once __DIR__ . '/../../config.php';

$vehicleId = isset($_GET['id']) ? (int) $_GET['id'] : (int) ($_POST['id'] ?? 0);

if ($vehicleId <= 0) {
    http_response_code(400);
    echo 'Geçersiz araç ID bilgisi.';
    exit;
}

try {
    $stmt = $pdo->prepare('SELECT id, plate_number, type, brand, model FROM vehicles WHERE id = :id');
    $stmt->execute([':id' => $vehicleId]);
    $vehicle = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $exception) {
    error_log('Vehicle fetch failed: ' . $exception->getMessage());
    $vehicle = false;
}

if ($vehicle === false) {
    http_response_code(404);
    echo 'Araç kaydı bulunamadı.';
    exit;
}

try {
    $assignmentStmt = $pdo->prepare('SELECT COUNT(*) FROM shipments WHERE vehicle_id = :id');
    $assignmentStmt->execute([':id' => $vehicleId]);
    $assignedShipmentCount = (int) $assignmentStmt->fetchColumn();
} catch (PDOException $exception) {
    error_log('Vehicle shipment count failed: ' . $exception->getMessage());
    $assignedShipmentCount = 0;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $confirm = isset($_POST['confirm']) ? (string) $_POST['confirm'] : '';

    if ($confirm === 'yes') {
        try {
            $stmt = $pdo->prepare('DELETE FROM vehicles WHERE id = :id');
            $stmt->execute([':id' => $vehicleId]);

            $_SESSION['flash_success'] = 'Araç kaydı silindi.';
            header('Location: ../vehicle.php');
            exit;
        } catch (PDOException $exception) {
            error_log('Vehicle delete failed: ' . $exception->getMessage());
            $_SESSION['flash_error'] = 'Araç silinirken bir hata oluştu. Lütfen tekrar deneyin.';
            header('Location: ../vehicle.php');
            exit;
        }
    } else {
        $_SESSION['flash_warning'] = 'Araç silme işlemi iptal edildi.';
        header('Location: ../vehicle.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Araç Sil</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include __DIR__ . '/../../partials/flash.php'; ?>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card shadow-sm border-danger">
                <div class="card-header bg-danger text-white">
                    <h1 class="h5 mb-0">Araç Kaydını Sil</h1>
                </div>
                <div class="card-body">
                    <p class="mb-4">Aşağıdaki araç kaydını silmek üzeresiniz. Bu işlem geri alınamaz.</p>
                    <ul class="list-group mb-4">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span class="fw-semibold">Plaka</span>
                            <span><?= htmlspecialchars((string) ($vehicle['plate_number'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span class="fw-semibold">Tip</span>
                            <span><?= htmlspecialchars((string) ($vehicle['type'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span class="fw-semibold">Model</span>
                            <span><?= htmlspecialchars(trim(((string) ($vehicle['brand'] ?? '')) . ' ' . ((string) ($vehicle['model'] ?? ''))), ENT_QUOTES, 'UTF-8'); ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span class="fw-semibold">Atanmış Sevkiyat</span>
                            <span><?= $assignedShipmentCount > 0 ? htmlspecialchars((string) $assignedShipmentCount, ENT_QUOTES, 'UTF-8') : '0'; ?></span>
                        </li>
                    </ul>
                    <?php if ($assignedShipmentCount > 0): ?>
                        <div class="alert alert-warning">Bu araç <?= htmlspecialchars((string) $assignedShipmentCount, ENT_QUOTES, 'UTF-8'); ?> sevkiyat ile ilişkilidir. Silme işleminden sonra ilgili sevkiyatlarda araç bilgisi temizlenecektir.</div>
                    <?php endif; ?>
                    <form method="post" class="d-flex gap-2">
                        <input type="hidden" name="id" value="<?= htmlspecialchars((string) $vehicleId, ENT_QUOTES, 'UTF-8'); ?>">
                        <button type="submit" name="confirm" value="yes" class="btn btn-danger">Evet, Sil</button>
                        <a href="../vehicle.php" class="btn btn-outline-secondary">Vazgeç</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
