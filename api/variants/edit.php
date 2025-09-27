<?php
require_once __DIR__ . '/../common.php';
ensure_post();
$data = json_input();
verify_csrf($data);

$variantId = isset($data['id']) ? (int) $data['id'] : 0;
$sku = sanitize_string($data['sku'] ?? '');
$variantName = sanitize_string($data['variant_name'] ?? '');
$basePrice = isset($data['base_price']) ? filter_var($data['base_price'], FILTER_VALIDATE_FLOAT) : 0;
$currency = strtoupper(substr(sanitize_string($data['currency'] ?? 'TRY'), 0, 3));
$isActive = isset($data['is_active']) ? (int) $data['is_active'] : 1;

if ($variantId <= 0 || !$sku || !$variantName || $basePrice === false || $basePrice < 0) {
    send_json(['ok' => false, 'error' => 'Varyant bilgileri eksik.'], 422);
}

try {
    begin_transaction($pdo);
    $stmt = $pdo->prepare('UPDATE product_variants SET sku = :sku, variant_name = :variant_name, base_price = :base_price, currency = :currency, is_active = :is_active WHERE id = :id');
    $stmt->execute([
        ':sku' => $sku,
        ':variant_name' => $variantName,
        ':base_price' => $basePrice,
        ':currency' => $currency ?: 'TRY',
        ':is_active' => $isActive > 0 ? 1 : 0,
        ':id' => $variantId,
    ]);

    if ($stmt->rowCount() === 0) {
        rollback_transaction($pdo);
        send_json(['ok' => false, 'error' => 'Varyant bulunamadı.'], 404);
    }

    commit_transaction($pdo);
    send_json(['ok' => true]);
} catch (PDOException $e) {
    rollback_transaction($pdo);
    if ((int) $e->getCode() === 23000) {
        send_json(['ok' => false, 'error' => 'SKU veya varyant adı benzersiz olmalıdır.'], 422);
    }
    error_log('variants/edit hata: ' . $e->getMessage());
    send_json(['ok' => false, 'error' => 'Varyant güncellenirken hata oluştu.'], 500);
} catch (Throwable $e) {
    rollback_transaction($pdo);
    error_log('variants/edit bilinmeyen hata: ' . $e->getMessage());
    send_json(['ok' => false, 'error' => 'Varyant güncellenemedi.'], 500);
}
