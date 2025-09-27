<?php
require_once __DIR__ . '/../common.php';
ensure_get();

$productId = isset($_GET['product_id']) ? (int) $_GET['product_id'] : 0;
if ($productId <= 0) {
    send_json(['ok' => false, 'error' => 'Ürün kimliği zorunlu.'], 422);
}

try {
    $stmt = $pdo->prepare('SELECT pv.id, pv.sku, pv.variant_name, pv.base_price, pv.currency, v.recipe FROM product_variants pv LEFT JOIN v_variant_recipe v ON v.variant_id = pv.id WHERE pv.product_id = :product_id ORDER BY pv.created_at DESC');
    $stmt->execute([':product_id' => $productId]);
    $variants = $stmt->fetchAll();
    send_json(['ok' => true, 'variants' => $variants]);
} catch (Throwable $e) {
    error_log('variants/list hata: ' . $e->getMessage());
    send_json(['ok' => false, 'error' => 'Varyantlar listelenemedi.'], 500);
}
