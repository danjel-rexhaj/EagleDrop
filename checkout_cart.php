<?php
session_start();
require 'vendor/autoload.php';
require 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    die("Duhet te jeni te loguar.");
}

$user_id = $_SESSION['user_id'];


//change it 


$stmt = $conn->prepare("
    SELECT cart_items.product_id, cart_items.quantity,
           products.title, products.price
    FROM cart_items
    JOIN products ON products.id = cart_items.product_id
    WHERE cart_items.user_id = ?
");
$stmt->execute([$user_id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);


if (!$items || count($items) == 0) {
    die("Shporta eshte bosh.");
}


$line_items = [];

foreach ($items as $item) {

    $line_items[] = [
        'price_data' => [
            'currency' => 'eur',
            'product_data' => [
                'name' => $item['title'],
            ],
            'unit_amount' => $item['price'] * 100 + 1000, 
        ],
        'quantity' => $item['quantity'],
    ];
}


$BASE_URL = "https://stalagmitical-emma-unpoached.ngrok-free.dev/myplatform";
$_SESSION['payment_type'] = 'cart';

$session = \Stripe\Checkout\Session::create([
    'payment_method_types' => ['card'],
    'mode' => 'payment',
    'line_items' => $line_items,

    'success_url' => $BASE_URL . "/payment_success.php?session_id={CHECKOUT_SESSION_ID}",
    'cancel_url'  => $BASE_URL . "/index.php",
]);



header("Location: " . $session->url);
exit;
?>
