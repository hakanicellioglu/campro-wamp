<?php
require_once __DIR__ . '/../common.php';
ensure_post();
$data = json_input();
verify_csrf($data);

$variantId = isset($data['variant_id']) ? (int) $data['variant_id'] : 0;
$order = $data['order'] ?? null;
$layers = $data['layers'] ?? null;

if ($variantId <= 0) {
    send_json(['ok' => false, 'error' => 'Varyant kimliği gerekli.'], 422);
}

if ((!is_array($order) || !$order) && (!is_array($layers) || !$layers)) {
    send_json(['ok' => false, 'error' => 'Yeni sıralama bilgisi gönderilmedi.'], 422);
}

try {
    begin_transaction($pdo);
    $check = $pdo->prepare('SELECT id FROM product_variants WHERE id = :id');
    $check->execute([':id' => $variantId]);
    if (!$check->fetchColumn()) {
        rollback_transaction($pdo);
        send_json(['ok' => false, 'error' => 'Varyant bulunamadı.'], 404);
    }

    $layersStmt = $pdo->prepare('SELECT id FROM variant_layers WHERE variant_id = :variant_id');
    $layersStmt->execute([':variant_id' => $variantId]);
    $existingIds = $layersStmt->fetchAll(PDO::FETCH_COLUMN);
    $existingIds = array_map('intval', $existingIds);

    $sequenceUpdates = [];
    if (is_array($order) && $order) {
        $seq = 1;
        foreach ($order as $layerId) {
            $layerId = (int) $layerId;
            if (!in_array($layerId, $existingIds, true)) {
                rollback_transaction($pdo);
                send_json(['ok' => false, 'error' => 'Geçersiz katman ID değeri gönderildi.'], 422);
            }
            $sequenceUpdates[$layerId] = $seq++;
        }
    } else {
        foreach ($layers as $layer) {
            $layerId = isset($layer['id']) ? (int) $layer['id'] : 0;
            $sequence = isset($layer['sequence_no']) ? (int) $layer['sequence_no'] : 0;
            if ($layerId <= 0 || $sequence <= 0 || !in_array($layerId, $existingIds, true)) {
                rollback_transaction($pdo);
                send_json(['ok' => false, 'error' => 'Katman sıralama bilgisi hatalı.'], 422);
            }
            $sequenceUpdates[$layerId] = $sequence;
        }
    }

    $updateStmt = $pdo->prepare('UPDATE variant_layers SET sequence_no = :sequence_no WHERE id = :id AND variant_id = :variant_id');
    foreach ($sequenceUpdates as $id => $seq) {
        $updateStmt->execute([
            ':sequence_no' => $seq,
            ':id' => $id,
            ':variant_id' => $variantId,
        ]);
    }

    commit_transaction($pdo);
    send_json(['ok' => true]);
} catch (Throwable $e) {
    rollback_transaction($pdo);
    error_log('variant-layers/reorder hata: ' . $e->getMessage());
    send_json(['ok' => false, 'error' => 'Katman sıralaması güncellenemedi.'], 500);
}
