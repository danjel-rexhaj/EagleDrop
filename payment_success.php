<?php
require "./includes/auth.php";
require "./config/database.php";
require "./vendor/autoload.php";

//change this


if (!isset($_GET['session_id']) || empty($_GET['session_id'])) {

    include "./includes/header.php"; ?>
    
    <div class="container mt-5 text-center">
        <h2 class="text-danger mb-3">Gabim gjate pageses</h2>
        <p>Kjo faqe mund te hapet vetem pasi te kryhet nje pagese me Stripe.</p>
        <a href="index.php" class="btn btn-primary mt-3">Kthehu ne faqen kryesore</a>
    </div>

    <?php
    include "./includes/footer.php";
    exit;
}

$sessionId = $_GET['session_id'];

try {

    $session = \Stripe\Checkout\Session::retrieve($sessionId);
} catch (\Exception $e) {
    include "./includes/header.php"; ?>
    
    <div class="container mt-5 text-center">
        <h2 class="text-danger mb-3">Gabim gjate verifikimit te pageses</h2>
        <p><?= htmlspecialchars($e->getMessage()) ?></p>
        <a href="index.php" class="btn btn-primary mt-3">Kthehu ne faqen kryesore</a>
    </div>

    <?php
    include "./includes/footer.php";
    exit;
}


if ($session->payment_status !== 'paid') {
    include "./includes/header.php"; ?>
    
    <div class="container mt-5 text-center">
        <h2 class="text-warning mb-3">Pagesa nuk eshte konfirmuar</h2>
        <p>Statusi aktual i pageses: <b><?= htmlspecialchars($session->payment_status) ?></b></p>
        <a href="cart.php" class="btn btn-secondary mt-3">Kthehu te shporta</a>
        <a href="index.php" class="btn btn-primary mt-3 ms-2">Faqja kryesore</a>
    </div>

    <?php
    include "./includes/footer.php";
    exit;
}


$userId        = $_SESSION['user_id'];
$amount        = $session->amount_total / 100;   
$transactionId = $session->payment_intent;


$check = $conn->prepare("SELECT id FROM payments WHERE transaction_id = ?");
$check->execute([$transactionId]);
$exists = $check->fetch();

if (!$exists) {
    $insert = $conn->prepare("
        INSERT INTO payments (user_id, amount, status, provider, transaction_id)
        VALUES (?, ?, ?, ?, ?)
    ");
    $insert->execute([
        $userId,
        $amount,
        "success",
        "stripe",
        $transactionId
    ]);

    if (
        isset($_SESSION['payment_type']) &&
        $_SESSION['payment_type'] === 'cart'
    ) {

        $clearCart = $conn->prepare("DELETE FROM cart_items WHERE user_id = ?");
        $clearCart->execute([$userId]);
    }


    unset($_SESSION['payment_type']);

    }

include "./includes/header.php";
?>

<div class="container mt-5 text-center">
    <h2 class="text-success mb-3">✅ Pagesa u krye me sukses!</h2>
    <p>Shuma e paguar: <b><?= number_format($amount, 2) ?> €</b></p>
    <p>ID e transaksionit: <b><?= htmlspecialchars($transactionId) ?></b></p>

    <div class="mt-4 d-flex justify-content-center gap-3">
        <a class="btn btn-primary" href="index.php">Kthehu ne faqen kryesore</a>
        <a class="btn btn-outline-light" href="payment_history.php">Shiko historikun e pagesave</a>
    </div>
</div>

<?php include "./includes/footer.php"; ?>
