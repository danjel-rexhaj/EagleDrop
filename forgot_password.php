<?php
session_start();
require "./config/database.php";
require "./config/mailer.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = $_POST['email'];

    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        $_SESSION['reset_email'] = $email;
        sendResetPasswordEmail($email);
    }

    $message = "📧 Nese email-i ekziston, do te merrni nje link per resetimin e password-it.";
}
?>

<?php include "./includes/header.php"; ?>

<style>
.forgot-card {
    max-width: 420px;
    width: 100%;
    border-radius: 18px;
    box-shadow: 0 20px 45px rgba(0,0,0,.15);
    animation: fadeUp .6s ease;
}

.forgot-icon {
    font-size: 48px;
}

@keyframes fadeUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.form-control {
    padding: 12px;
}
</style>

<div class="container min-vh-100 d-flex align-items-center justify-content-center">
    <div class="card forgot-card p-4 text-center">

        <div class="forgot-icon mb-2">📧</div>

        <h4 class="mb-1">Forgot Password</h4>
        <p class="text-muted small mb-4">
            Shkruaj email-in per te marre linkun e resetimit
        </p>

        <?php if ($message): ?>
            <div class="alert alert-info py-2">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <form method="POST">

            <input
                type="email"
                name="email"
                class="form-control mb-3"
                placeholder="Email-i yt"
                required
            >

            <button class="btn btn-primary w-100 py-2">
                🔗 Dergo Linkun
            </button>

        </form>

        <div class="mt-3 small text-muted">
            Kontrollo edhe Spam 📬
        </div>

    </div>
</div>

<?php include "./includes/footer.php"; ?>
