<?php
session_start();
require 'vendor/autoload.php';
require 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    die("Duhet te jeni te loguar.");
}

if (!isset($_POST['product_id'])) {
    die("Produkti mungon.");
}

$product_id = $_POST['product_id'];


$stmt = $conn->prepare("SELECT title, price FROM products WHERE id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    die("Produkti nuk u gjet.");
}

//change it
$_SESSION['payment_type'] = 'single';

$session = \Stripe\Checkout\Session::create([
    'payment_method_types' => ['card'],
    'mode' => 'payment',
    'line_items' => [[
        'price_data' => [
            'currency' => 'eur',
            'product_data' => [
                'name' => $product['title'],
            ],
            'unit_amount' => $product['price'] * 100 + 1000, 
        ],
        'quantity' => 1
    ]],
    'success_url' => 'https://stalagmitical-emma-unpoached.ngrok-free.dev/myplatform/payment_success.php?session_id={CHECKOUT_SESSION_ID}',// changre those
    'cancel_url'  => 'https://stalagmitical-emma-unpoached.ngrok-free.dev/myplatform/product_details.php?id=' . $product_id
]);

header("Location: " . $session->url);
exit;
?>
