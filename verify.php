<meta name="viewport" content="width=device-width, initial-scale=1.0">

<?php
session_start();
require "./config/database.php";

if (!isset($_SESSION['pending_email'])) {
    header("Location: login.php");
    exit();
}

$email = $_SESSION['pending_email'];
$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $code = $_POST["code"];

    $query = $conn->prepare("SELECT verification_code FROM users WHERE email = ?");
    $query->execute([$email]);
    $row = $query->fetch(PDO::FETCH_ASSOC);

    if ($row && $row['verification_code'] == $code) {
        $update = $conn->prepare("UPDATE users SET is_verified = 1 WHERE email = ?");
        $update->execute([$email]);

        unset($_SESSION['pending_email']);
        header("Location: login.php?verified=1");
        exit();
    } else {
        $message = "Kodi eshte i pasakte!";
    }
}
?>

<?php include "./includes/header.php"; ?>

<style>
.verify-card {
    max-width: 420px;
    margin: auto;
    border-radius: 16px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.15);
    animation: fadeInUp 0.6s ease;
}

.verify-icon {
    font-size: 48px;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.code-input {
    text-align: center;
    font-size: 24px;
    letter-spacing: 6px;
}
</style>

<div class="container min-vh-100 d-flex align-items-center justify-content-center">
    <div class="card verify-card p-4 text-center">

        <div class="verify-icon mb-3">📧</div>

        <h4 class="mb-2">Verifikimi i Email-it</h4>
        <p class="text-muted small mb-4">
            Kemi derguar nje kod 6-shifror ne<br>
            <b><?= htmlspecialchars($email) ?></b>
        </p>

        <?php if ($message): ?>
            <div class="alert alert-danger py-2">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <input
                type="text"
                name="code"
                maxlength="6"
                class="form-control code-input mb-3"
                placeholder="••••••"
                required
            >

            <button class="btn btn-primary w-100 py-2">
                ✅ Verifiko Email-in
            </button>
        </form>
<script>
document.getElementById('codeInput').addEventListener('paste', function (e) {
    e.preventDefault();

    let paste = (e.clipboardData || window.clipboardData)
        .getData('text')
        .replace(/\D/g, '')   
        .slice(0, 6);         

    this.value = paste;
});
</script>

        <div class="mt-3 small text-muted">
            Nuk erdhi kodi? Kontrollo Spam 📬
        </div>

    </div>
</div>

<?php include "./includes/footer.php"; ?>
