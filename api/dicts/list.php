<?php
require_once __DIR__ . '/../common.php';
ensure_get();

try {
    $categories = $pdo->query('SELECT id, name FROM glass_categories ORDER BY name')->fetchAll();
    $systems = $pdo->query('SELECT id, name FROM system_types ORDER BY name')->fetchAll();
    $layerTypes = $pdo->query('SELECT id, code, label FROM layer_types ORDER BY id')->fetchAll();
    $materialsStmt = $pdo->query('SELECT id, material_code, name, base_kind, thickness_mm, color, surface_finish, is_tempered, is_laminated, edge_finish FROM materials ORDER BY thickness_mm, name');
    $materials = $materialsStmt->fetchAll();

    send_json([
        'ok' => true,
        'categories' => $categories,
        'systems' => $systems,
        'layer_types' => $layerTypes,
        'materials' => $materials,
    ]);
} catch (Throwable $e) {
    error_log('dicts/list hata: ' . $e->getMessage());
    send_json(['ok' => false, 'error' => 'Sözlükler yüklenirken hata oluştu.'], 500);
}
