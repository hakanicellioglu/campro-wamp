<?php
require_once __DIR__ . '/../common.php';
ensure_post();
$data = json_input();
verify_csrf($data);

$categoryId = isset($data['category_id']) ? (int) $data['category_id'] : 0;
$systemTypeId = isset($data['system_type_id']) ? (int) $data['system_type_id'] : 0;
$name = sanitize_string($data['name'] ?? '');
$description = sanitize_string($data['description'] ?? '');

if ($categoryId <= 0 || $systemTypeId <= 0 || !$name) {
    send_json(['ok' => false, 'error' => 'Kategori, sistem tipi ve ürün adı zorunludur.'], 422);
}

try {
    begin_transaction($pdo);

    $checkCategory = $pdo->prepare('SELECT COUNT(*) FROM glass_categories WHERE id = :id');
    $checkCategory->execute([':id' => $categoryId]);
    if ((int) $checkCategory->fetchColumn() === 0) {
        rollback_transaction($pdo);
        send_json(['ok' => false, 'error' => 'Geçersiz kategori seçimi.'], 422);
    }

    $checkSystem = $pdo->prepare('SELECT COUNT(*) FROM system_types WHERE id = :id');
    $checkSystem->execute([':id' => $systemTypeId]);
    if ((int) $checkSystem->fetchColumn() === 0) {
        rollback_transaction($pdo);
        send_json(['ok' => false, 'error' => 'Geçersiz sistem tipi seçimi.'], 422);
    }

    $stmt = $pdo->prepare('INSERT INTO products (category_id, system_type_id, name, description) VALUES (:category_id, :system_type_id, :name, :description)');
    $stmt->execute([
        ':category_id' => $categoryId,
        ':system_type_id' => $systemTypeId,
        ':name' => $name,
        ':description' => $description,
    ]);
    $productId = (int) $pdo->lastInsertId();

    commit_transaction($pdo);
    send_json(['ok' => true, 'product_id' => $productId]);
} catch (PDOException $e) {
    rollback_transaction($pdo);
    if ((int) $e->getCode() === 23000) {
        send_json(['ok' => false, 'error' => 'Aynı kategoride ve sistem tipinde bu ürün adı zaten kullanılıyor.'], 422);
    }
    error_log('products/add hata: ' . $e->getMessage());
    send_json(['ok' => false, 'error' => 'Ürün kaydedilirken beklenmedik bir hata oluştu.'], 500);
} catch (Throwable $e) {
    rollback_transaction($pdo);
    error_log('products/add bilinmeyen hata: ' . $e->getMessage());
    send_json(['ok' => false, 'error' => 'Ürün kaydedilemedi.'], 500);
}
