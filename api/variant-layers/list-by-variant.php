<?php
require_once __DIR__ . '/../common.php';
ensure_get();

$variantId = isset($_GET['variant_id']) ? (int) $_GET['variant_id'] : 0;
if ($variantId <= 0) {
    send_json(['ok' => false, 'error' => 'Varyant kimliği zorunlu.'], 422);
}

try {
    $stmt = $pdo->prepare('SELECT vl.id, vl.sequence_no, lt.code AS layer_type_code, lt.label AS layer_type_label, vl.material_id, m.material_code, m.name AS material_name, vl.air_gap_mm, vl.notes FROM variant_layers vl JOIN layer_types lt ON lt.id = vl.layer_type_id LEFT JOIN materials m ON m.id = vl.material_id WHERE vl.variant_id = :variant_id ORDER BY vl.sequence_no');
    $stmt->execute([':variant_id' => $variantId]);
    $layers = $stmt->fetchAll();
    send_json(['ok' => true, 'layers' => $layers]);
} catch (Throwable $e) {
    error_log('variant-layers/list hata: ' . $e->getMessage());
    send_json(['ok' => false, 'error' => 'Katmanlar yüklenemedi.'], 500);
}
