<?php
ob_start();
session_start();
require 'config/database.php';

ob_clean(); 

header('Content-Type: application/json; charset=utf-8');



header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["error" => "Duhet te jeni te loguar."]);
    exit;
}

if (!isset($_POST['product_id'])) {
    echo json_encode(["error" => "Mungon ID e produktit."]);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$product_id = (int)$_POST['product_id'];

$stmt = $conn->prepare("SELECT id FROM products WHERE id = ?");
$stmt->execute([$product_id]);

if (!$stmt->fetch()) {
    echo json_encode(["error" => "Produkti nuk ekziston."]);
    exit;
}

$stmt = $conn->prepare("SELECT id FROM cart_items WHERE user_id = ? AND product_id = ?");
$stmt->execute([$user_id, $product_id]);
$existing = $stmt->fetch(PDO::FETCH_ASSOC);

if ($existing) {
    $stmt = $conn->prepare("UPDATE cart_items SET quantity = quantity + 1 WHERE id = ?");
    $stmt->execute([$existing['id']]);
} else {
    $stmt = $conn->prepare("INSERT INTO cart_items (user_id, product_id, quantity) VALUES (?, ?, 1)");
    $stmt->execute([$user_id, $product_id]);
}

$stmt = $conn->prepare("SELECT SUM(quantity) FROM cart_items WHERE user_id = ?");
$stmt->execute([$user_id]);
$total = (int)$stmt->fetchColumn();

$_SESSION['cart_count'] = $total;

echo json_encode(["success" => true, "cart_count" => $total]);

exit;

