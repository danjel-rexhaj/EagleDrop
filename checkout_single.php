<?php
session_start();
require 'vendor/autoload.php';
require 'config/database.php';
require 'config/env.php';

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

\Stripe\Stripe::setApiKey(env('STRIPE_SECRET_KEY'));
$_SESSION['payment_type'] = 'single';
$BASE_URL = env('APP_BASE_URL', 'https://stalagmitical-emma-unpoached.ngrok-free.dev/myplatform');

$session = \Stripe\Checkout\Session::create([
    'payment_method_types' => ['card'],
    'mode' => 'payment',
    'line_items' => [[
        'price_data' => [
            'currency' => 'eur',
            'product_data' => [
                'name' => $product['title'],
            ],
            'unit_amount' => $product['price'] * 100,
        ],
        'quantity' => 1
    ]],
    'success_url' => $BASE_URL . '/payment_success.php?session_id={CHECKOUT_SESSION_ID}',
    'cancel_url'  => $BASE_URL . '/product_details.php?id=' . $product_id
]);

header("Location: " . $session->url);
exit;
?>
