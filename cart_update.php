<?php
session_start();
require 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    die("Duhet te jeni te loguar.");
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = $_POST['product_id'];
    $action = $_POST['action'] ?? '';

    if ($action === 'updateQty' && isset($_POST['quantity'])) {
        $quantity = max(1, (int)$_POST['quantity']);
        $stmt = $conn->prepare("
            UPDATE cart_items
            SET quantity = ?
            WHERE user_id = ? AND product_id = ?
        ");
        $stmt->execute([$quantity, $user_id, $product_id]);
    }

    if ($action === 'remove') {
        $stmt = $conn->prepare("
            DELETE FROM cart_items
            WHERE user_id = ? AND product_id = ?
        ");
        $stmt->execute([$user_id, $product_id]);
    }
}

header("Location: cart.php");
exit;
