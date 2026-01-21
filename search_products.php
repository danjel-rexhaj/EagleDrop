<?php
require __DIR__ . '/config/database.php';

$q = trim($_GET['q'] ?? '');

if (strlen($q) < 2) {
  echo json_encode([]);
  exit;
}

$stmt = $conn->prepare("
  SELECT id, title, price
  FROM products
  WHERE title LIKE ?
  ORDER BY title
  LIMIT 8
");

$stmt->execute(["%$q%"]);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
