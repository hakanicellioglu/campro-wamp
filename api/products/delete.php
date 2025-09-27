<?php
require_once __DIR__ . '/../common.php';
ensure_post();
$data = json_input();
verify_csrf($data);

$productId = isset($data['id']) ? (int) $data['id'] : 0;

if ($productId <= 0) {
    send_json(['ok' => false, 'error' => 'Geçerli bir ürün seçin.'], 422);
}

try {
    begin_transaction($pdo);
    $stmt = $pdo->prepare('DELETE FROM products WHERE id = :id');
    $stmt->execute([':id' => $productId]);

    if ($stmt->rowCount() === 0) {
        rollback_transaction($pdo);
        send_json(['ok' => false, 'error' => 'Silinecek ürün bulunamadı.'], 404);
    }

    commit_transaction($pdo);
    send_json(['ok' => true]);
} catch (Throwable $e) {
    rollback_transaction($pdo);
    error_log('products/delete hata: ' . $e->getMessage());
    send_json(['ok' => false, 'error' => 'Ürün silinirken hata oluştu.'], 500);
}
