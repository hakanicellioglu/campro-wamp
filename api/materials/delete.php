<?php
require_once __DIR__ . '/../common.php';
ensure_post();
$data = json_input();
verify_csrf($data);

$materialId = isset($data['id']) ? (int) $data['id'] : 0;

if ($materialId <= 0) {
    send_json(['ok' => false, 'error' => 'Geçerli bir malzeme seçin.'], 422);
}

try {
    begin_transaction($pdo);
    $stmt = $pdo->prepare('DELETE FROM materials WHERE id = :id');
    $stmt->execute([':id' => $materialId]);

    if ($stmt->rowCount() === 0) {
        rollback_transaction($pdo);
        send_json(['ok' => false, 'error' => 'Silinecek malzeme bulunamadı.'], 404);
    }

    commit_transaction($pdo);
    send_json(['ok' => true]);
} catch (PDOException $e) {
    rollback_transaction($pdo);
    if ((int) $e->errorInfo[1] === 1451) {
        send_json(['ok' => false, 'error' => 'Malzeme varyant katmanlarında kullanıldığı için silinemedi.'], 409);
    }
    error_log('materials/delete hata: ' . $e->getMessage());
    send_json(['ok' => false, 'error' => 'Malzeme silinirken hata oluştu.'], 500);
} catch (Throwable $e) {
    rollback_transaction($pdo);
    error_log('materials/delete bilinmeyen hata: ' . $e->getMessage());
    send_json(['ok' => false, 'error' => 'Malzeme silinemedi.'], 500);
}
