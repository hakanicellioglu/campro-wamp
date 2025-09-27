<?php
require_once __DIR__ . '/../common.php';
ensure_post();
$data = json_input();
verify_csrf($data);

$variantId = isset($data['variant_id']) ? (int) $data['variant_id'] : 0;
$layers = $data['layers'] ?? [];

if ($variantId <= 0 || !is_array($layers) || count($layers) === 0) {
    send_json(['ok' => false, 'error' => 'Varyant ve katman bilgileri zorunlu.'], 422);
}

try {
    begin_transaction($pdo);

    $variantCheck = $pdo->prepare('SELECT id FROM product_variants WHERE id = :id');
    $variantCheck->execute([':id' => $variantId]);
    if (!$variantCheck->fetchColumn()) {
        rollback_transaction($pdo);
        send_json(['ok' => false, 'error' => 'Varyant bulunamadı.'], 404);
    }

    $layerTypeRows = $pdo->query('SELECT id, code FROM layer_types')->fetchAll();
    $layerTypeMap = [];
    foreach ($layerTypeRows as $row) {
        $layerTypeMap[$row['code']] = (int) $row['id'];
    }

    $existingStmt = $pdo->prepare('SELECT id FROM variant_layers WHERE variant_id = :variant_id');
    $existingStmt->execute([':variant_id' => $variantId]);
    $existingIds = $existingStmt->fetchAll(PDO::FETCH_COLUMN);
    $existingIds = array_map('intval', $existingIds);

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

    $seenSequences = [];
    $processedIds = [];

    $insertStmt = $pdo->prepare('INSERT INTO variant_layers (variant_id, sequence_no, layer_type_id, material_id, air_gap_mm, notes) VALUES (:variant_id, :sequence_no, :layer_type_id, :material_id, :air_gap_mm, :notes)');
    $updateStmt = $pdo->prepare('UPDATE variant_layers SET sequence_no = :sequence_no, layer_type_id = :layer_type_id, material_id = :material_id, air_gap_mm = :air_gap_mm, notes = :notes WHERE id = :id AND variant_id = :variant_id');

    foreach ($layers as $layer) {
        $sequence = (int) ($layer['sequence_no'] ?? 0);
        $layerCode = $layer['layer_type_code'] ?? '';
        $note = sanitize_string($layer['notes'] ?? null);

        if ($sequence <= 0 || !$layerCode || !isset($layerTypeMap[$layerCode])) {
            rollback_transaction($pdo);
            send_json(['ok' => false, 'error' => 'Katman bilgileri eksik veya hatalı.'], 422);
        }
        if (in_array($sequence, $seenSequences, true)) {
            rollback_transaction($pdo);
            send_json(['ok' => false, 'error' => 'Katman sırası tekrar edemez.'], 422);
        }
        $seenSequences[] = $sequence;
        $layerTypeId = $layerTypeMap[$layerCode];

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
                send_json(['ok' => false, 'error' => 'Katman için malzeme seçimi hatalı.'], 422);
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

        if (!empty($layer['id']) && in_array((int) $layer['id'], $existingIds, true)) {
            $updateStmt->execute([
                ':sequence_no' => $sequence,
                ':layer_type_id' => $layerTypeId,
                ':material_id' => $materialId ?: null,
                ':air_gap_mm' => $airGap,
                ':notes' => $note,
                ':id' => (int) $layer['id'],
                ':variant_id' => $variantId,
            ]);
            $processedIds[] = (int) $layer['id'];
        } else {
            $insertStmt->execute([
                ':variant_id' => $variantId,
                ':sequence_no' => $sequence,
                ':layer_type_id' => $layerTypeId,
                ':material_id' => $materialId ?: null,
                ':air_gap_mm' => $airGap,
                ':notes' => $note,
            ]);
            $processedIds[] = (int) $pdo->lastInsertId();
        }
    }

    $idsToDelete = array_diff($existingIds, $processedIds);
    if ($idsToDelete) {
        $placeholders = implode(',', array_fill(0, count($idsToDelete), '?'));
        $deleteStmt = $pdo->prepare("DELETE FROM variant_layers WHERE variant_id = ? AND id IN ({$placeholders})");
        $deleteStmt->execute(array_merge([$variantId], array_values($idsToDelete)));
    }

    commit_transaction($pdo);
    send_json(['ok' => true]);
} catch (Throwable $e) {
    rollback_transaction($pdo);
    error_log('variant-layers/bulk-upsert hata: ' . $e->getMessage());
    send_json(['ok' => false, 'error' => 'Katmanlar güncellenemedi.'], 500);
}
