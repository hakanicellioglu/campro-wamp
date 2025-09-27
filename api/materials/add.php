<?php
require_once __DIR__ . '/../common.php';
ensure_post();
$data = json_input();
verify_csrf($data);

$materialCode = sanitize_string($data['material_code'] ?? '');
$name = sanitize_string($data['name'] ?? '');
$baseKind = $data['base_kind'] ?? '';
$thickness = isset($data['thickness_mm']) ? filter_var($data['thickness_mm'], FILTER_VALIDATE_FLOAT) : null;
$color = sanitize_string($data['color'] ?? null);
$surface = sanitize_string($data['surface_finish'] ?? null);
$isTempered = !empty($data['is_tempered']) ? 1 : 0;
$isLaminated = !empty($data['is_laminated']) ? 1 : 0;
$edge = sanitize_string($data['edge_finish'] ?? null);
$notes = sanitize_string($data['notes'] ?? null);

if (!$materialCode || !$name || !in_array($baseKind, ['glass', 'film'], true) || $thickness === false || $thickness <= 0) {
    send_json(['ok' => false, 'error' => 'Malzeme kodu, ad, temel tür ve kalınlık zorunludur.'], 422);
}

try {
    begin_transaction($pdo);
    $stmt = $pdo->prepare('INSERT INTO materials (material_code, name, base_kind, thickness_mm, color, surface_finish, is_tempered, is_laminated, edge_finish, notes) VALUES (:material_code, :name, :base_kind, :thickness_mm, :color, :surface_finish, :is_tempered, :is_laminated, :edge_finish, :notes)');
    $stmt->execute([
        ':material_code' => $materialCode,
        ':name' => $name,
        ':base_kind' => $baseKind,
        ':thickness_mm' => $thickness,
        ':color' => $color,
        ':surface_finish' => $surface,
        ':is_tempered' => $isTempered,
        ':is_laminated' => $isLaminated,
        ':edge_finish' => $edge,
        ':notes' => $notes,
    ]);
    $materialId = (int) $pdo->lastInsertId();
    commit_transaction($pdo);
    send_json(['ok' => true, 'material_id' => $materialId]);
} catch (PDOException $e) {
    rollback_transaction($pdo);
    if ((int) $e->getCode() === 23000) {
        send_json(['ok' => false, 'error' => 'Bu malzeme kodu zaten mevcut.'], 422);
    }
    error_log('materials/add hata: ' . $e->getMessage());
    send_json(['ok' => false, 'error' => 'Malzeme kaydedilirken hata oluştu.'], 500);
} catch (Throwable $e) {
    rollback_transaction($pdo);
    error_log('materials/add bilinmeyen hata: ' . $e->getMessage());
    send_json(['ok' => false, 'error' => 'Malzeme kaydedilemedi.'], 500);
}
