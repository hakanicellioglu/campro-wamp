<?php
require_once __DIR__ . '/../common.php';
ensure_get();

try {
    $stmt = $pdo->query('SELECT id, material_code, name, base_kind, thickness_mm, color, surface_finish, is_tempered, is_laminated, edge_finish, notes FROM materials ORDER BY base_kind, thickness_mm, name');
    $materials = $stmt->fetchAll();
    send_json(['ok' => true, 'materials' => $materials]);
} catch (Throwable $e) {
    error_log('materials/list hata: ' . $e->getMessage());
    send_json(['ok' => false, 'error' => 'Malzeme listesi alınamadı.'], 500);
}
