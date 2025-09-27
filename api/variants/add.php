<?php
require_once __DIR__ . '/../common.php';
ensure_post();
$data = json_input();
verify_csrf($data);

$productId = isset($data['product_id']) ? (int) $data['product_id'] : 0;
$sku = sanitize_string($data['sku'] ?? '');
$variantName = sanitize_string($data['variant_name'] ?? '');
$basePrice = isset($data['base_price']) ? filter_var($data['base_price'], FILTER_VALIDATE_FLOAT) : 0;
$currency = strtoupper(substr(sanitize_string($data['currency'] ?? 'TRY'), 0, 3));
$layers = $data['layers'] ?? [];

if ($productId <= 0 || !$sku || !$variantName || $basePrice === false || $basePrice < 0 || !is_array($layers) || count($layers) === 0) {
    send_json(['ok' => false, 'error' => 'Ürün, SKU, varyant adı, fiyat ve katman bilgileri zorunludur.'], 422);
}

try {
    begin_transaction($pdo);

    $productCheck = $pdo->prepare('SELECT id FROM products WHERE id = :id');
    $productCheck->execute([':id' => $productId]);
    if (!$productCheck->fetchColumn()) {
        rollback_transaction($pdo);
        send_json(['ok' => false, 'error' => 'Seçilen ürün bulunamadı.'], 422);
    }

    $layerTypeRows = $pdo->query('SELECT id, code FROM layer_types')->fetchAll();
    $layerTypeMap = [];
    foreach ($layerTypeRows as $row) {
        $layerTypeMap[$row['code']] = (int) $row['id'];
    }

    $materialIds = [];
    foreach ($layers as $layer) {
        if (($layer['layer_type_code'] ?? '') !== 'air_gap' && !empty($layer['material_id'])) {
            $materialIds[] = (int) $layer['material_id'];
        }
    }
    $materialMap = [];
    if ($materialIds) {
        $placeholders = implode(',', array_fill(0, count($materialIds), '?'));
        $materialStmt = $pdo->prepare("SELECT id, base_kind FROM materials WHERE id IN ({$placeholders})");
        $materialStmt->execute($materialIds);
        foreach ($materialStmt->fetchAll() as $row) {
            $materialMap[$row['id']] = $row;
        }
    }

    $variantStmt = $pdo->prepare('INSERT INTO product_variants (product_id, sku, variant_name, base_price, currency) VALUES (:product_id, :sku, :variant_name, :base_price, :currency)');
    $variantStmt->execute([
        ':product_id' => $productId,
        ':sku' => $sku,
        ':variant_name' => $variantName,
        ':base_price' => $basePrice,
        ':currency' => $currency ?: 'TRY',
    ]);
    $variantId = (int) $pdo->lastInsertId();

    $layerInsert = $pdo->prepare('INSERT INTO variant_layers (variant_id, sequence_no, layer_type_id, material_id, air_gap_mm, notes) VALUES (:variant_id, :sequence_no, :layer_type_id, :material_id, :air_gap_mm, :notes)');

    foreach ($layers as $layer) {
        $sequence = (int) ($layer['sequence_no'] ?? 0);
        $layerCode = $layer['layer_type_code'] ?? '';
        if ($sequence <= 0 || !$layerCode || !isset($layerTypeMap[$layerCode])) {
            rollback_transaction($pdo);
            send_json(['ok' => false, 'error' => 'Katman bilgileri eksik veya hatalı.'], 422);
        }
        $layerTypeId = $layerTypeMap[$layerCode];
        $note = sanitize_string($layer['notes'] ?? null);

        $materialId = null;
        $airGap = null;

        if ($layerCode === 'air_gap') {
            $airGap = isset($layer['air_gap_mm']) ? filter_var($layer['air_gap_mm'], FILTER_VALIDATE_FLOAT) : null;
            if ($airGap === false || $airGap === null) {
                rollback_transaction($pdo);
                send_json(['ok' => false, 'error' => 'Hava boşluğu katmanlarında mm değeri zorunludur.'], 422);
            }
        } else {
            $materialId = isset($layer['material_id']) ? (int) $layer['material_id'] : 0;
            if ($materialId <= 0 || !isset($materialMap[$materialId])) {
                rollback_transaction($pdo);
                send_json(['ok' => false, 'error' => 'Katman için seçilen malzeme geçersiz.'], 422);
            }
            if ($layerCode === 'film' && $materialMap[$materialId]['base_kind'] !== 'film') {
                rollback_transaction($pdo);
                send_json(['ok' => false, 'error' => 'Film katmanı için film tabanlı malzeme seçilmelidir.'], 422);
            }
            if ($layerCode === 'glass' && $materialMap[$materialId]['base_kind'] !== 'glass') {
                rollback_transaction($pdo);
                send_json(['ok' => false, 'error' => 'Cam katmanı için cam tabanlı malzeme seçilmelidir.'], 422);
            }
        }

        $layerInsert->execute([
            ':variant_id' => $variantId,
            ':sequence_no' => $sequence,
            ':layer_type_id' => $layerTypeId,
            ':material_id' => $materialId ?: null,
            ':air_gap_mm' => $airGap,
            ':notes' => $note,
        ]);
    }

    commit_transaction($pdo);
    send_json(['ok' => true, 'variant_id' => $variantId]);
} catch (PDOException $e) {
    rollback_transaction($pdo);
    if ((int) $e->getCode() === 23000) {
        send_json(['ok' => false, 'error' => 'Bu SKU veya varyant adı zaten kayıtlı.'], 422);
    }
    error_log('variants/add hata: ' . $e->getMessage());
    send_json(['ok' => false, 'error' => 'Varyant kaydedilirken hata oluştu.'], 500);
} catch (Throwable $e) {
    rollback_transaction($pdo);
    error_log('variants/add bilinmeyen hata: ' . $e->getMessage());
    send_json(['ok' => false, 'error' => 'Varyant oluşturulamadı.'], 500);
}
