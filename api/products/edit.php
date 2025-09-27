<?php
require_once __DIR__ . '/../common.php';
ensure_post();
$data = json_input();
verify_csrf($data);

$productId = isset($data['id']) ? (int) $data['id'] : 0;
$categoryId = isset($data['category_id']) ? (int) $data['category_id'] : 0;
$systemTypeId = isset($data['system_type_id']) ? (int) $data['system_type_id'] : 0;
$name = sanitize_string($data['name'] ?? '');
$description = sanitize_string($data['description'] ?? '');
$isActive = isset($data['is_active']) ? (int) $data['is_active'] : 1;

if ($productId <= 0 || $categoryId <= 0 || $systemTypeId <= 0 || !$name) {
    send_json(['ok' => false, 'error' => 'Tüm zorunlu alanları doldurun.'], 422);
}

try {
    begin_transaction($pdo);

    $stmt = $pdo->prepare('UPDATE products SET category_id = :category_id, system_type_id = :system_type_id, name = :name, description = :description, is_active = :is_active WHERE id = :id');
    $stmt->execute([
        ':category_id' => $categoryId,
        ':system_type_id' => $systemTypeId,
        ':name' => $name,
        ':description' => $description,
        ':is_active' => $isActive > 0 ? 1 : 0,
        ':id' => $productId,
    ]);

    if ($stmt->rowCount() === 0) {
        rollback_transaction($pdo);
        send_json(['ok' => false, 'error' => 'Ürün bulunamadı.'], 404);
    }

    commit_transaction($pdo);
    send_json(['ok' => true]);
} catch (PDOException $e) {
    rollback_transaction($pdo);
    if ((int) $e->getCode() === 23000) {
        send_json(['ok' => false, 'error' => 'Bu ürün adı başka bir kayıtta mevcut.'], 422);
    }
    error_log('products/edit hata: ' . $e->getMessage());
    send_json(['ok' => false, 'error' => 'Ürün güncellenirken hata oluştu.'], 500);
} catch (Throwable $e) {
    rollback_transaction($pdo);
    error_log('products/edit bilinmeyen hata: ' . $e->getMessage());
    send_json(['ok' => false, 'error' => 'Ürün güncellenemedi.'], 500);
}
