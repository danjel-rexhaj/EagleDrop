<?php
require '../config/database.php';

header('Content-Type: application/json');

$maker     = trim($_GET['maker']  ?? '');
$model     = trim($_GET['model']  ?? '');
$engine    = trim($_GET['engine'] ?? '');
$category_id = intval($_GET['category'] ?? 0);
$offset    = max(0, intval($_GET['offset'] ?? 0));
$limit     = 12;

$query = "SELECT * FROM products WHERE 1";
$params = [];

if ($category_id) {
    $query .= " AND category_id = ?";
    $params[] = $category_id;
}

if ($maker !== '') {
    $query .= " AND fit_maker LIKE ?";
    $params[] = "%$maker%";
}

if ($model !== '') {
    $cleanModel = trim(strtok($model, '('));
    $query .= " AND fit_model LIKE ?";
    $params[] = "%$cleanModel%";
}

if ($engine !== '') {
    $cleanEngine = trim(strtok($engine, '('));
    $query .= " AND fit_engine LIKE ?";
    $params[] = "%$cleanEngine%";
}

$query .= " ORDER BY created_at DESC LIMIT $limit OFFSET $offset";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'products' => $products,
    'has_more' => count($products) === $limit,
]);
