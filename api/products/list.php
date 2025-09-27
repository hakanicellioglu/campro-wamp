<?php
require_once __DIR__ . '/../common.php';
ensure_get();

$q = isset($_GET['q']) ? sanitize_string($_GET['q']) : null;
$categoryId = isset($_GET['category_id']) ? (int) $_GET['category_id'] : null;
$systemTypeId = isset($_GET['system_type_id']) ? (int) $_GET['system_type_id'] : null;
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = min(50, max(5, (int) ($_GET['per_page'] ?? 10)));
$offset = ($page - 1) * $perPage;

$where = ['1=1'];
$params = [];

if ($q) {
    $where[] = '(p.name LIKE :q OR p.description LIKE :q)';
    $params[':q'] = "%{$q}%";
}
if ($categoryId) {
    $where[] = 'p.category_id = :category_id';
    $params[':category_id'] = $categoryId;
}
if ($systemTypeId) {
    $where[] = 'p.system_type_id = :system_type_id';
    $params[':system_type_id'] = $systemTypeId;
}

$whereSql = implode(' AND ', $where);

try {
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM products p WHERE {$whereSql}");
    $countStmt->execute($params);
    $total = (int) $countStmt->fetchColumn();

    $sql = "SELECT p.id, p.name, p.description, p.created_at, p.updated_at,
                   gc.name AS category_name, st.name AS system_type_name
            FROM products p
            JOIN glass_categories gc ON gc.id = p.category_id
            JOIN system_types st ON st.id = p.system_type_id
            WHERE {$whereSql}
            ORDER BY p.updated_at DESC
            LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $items = $stmt->fetchAll();

    send_json([
        'ok' => true,
        'total' => $total,
        'page' => $page,
        'per_page' => $perPage,
        'items' => $items,
    ]);
} catch (Throwable $e) {
    error_log('products/list hata: ' . $e->getMessage());
    send_json(['ok' => false, 'error' => 'Ürünler listelenirken hata oluştu.'], 500);
}
