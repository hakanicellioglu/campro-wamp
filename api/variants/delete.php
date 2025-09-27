<?php
require_once __DIR__ . '/../common.php';
ensure_post();
$data = json_input();
verify_csrf($data);

$variantId = isset($data['id']) ? (int) $data['id'] : 0;

if ($variantId <= 0) {
    send_json(['ok' => false, 'error' => 'Silinecek varyant seçilmedi.'], 422);
}

try {
    begin_transaction($pdo);
    $stmt = $pdo->prepare('DELETE FROM product_variants WHERE id = :id');
    $stmt->execute([':id' => $variantId]);
    if ($stmt->rowCount() === 0) {
        rollback_transaction($pdo);
        send_json(['ok' => false, 'error' => 'Varyant bulunamadı.'], 404);
    }
    commit_transaction($pdo);
    send_json(['ok' => true]);
} catch (Throwable $e) {
    rollback_transaction($pdo);
    error_log('variants/delete hata: ' . $e->getMessage());
    send_json(['ok' => false, 'error' => 'Varyant silinemedi.'], 500);
}
