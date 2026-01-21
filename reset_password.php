<?php
session_start();
require "./config/database.php";

if (!isset($_SESSION['reset_email'])) {
    die("Akses i pavlefshem.");
}

$email = $_SESSION['reset_email'];
$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $password = $_POST['password'];
    $confirm  = $_POST['confirm'];

    if (strlen($password) < 6) {
        $error = "Password duhet te kete te pakten 6 karaktere.";
    } elseif ($password !== $confirm) {
        $error = "Password-et nuk perputhen.";
    } else {
        $hashed = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
        $stmt->execute([$hashed, $email]);

        unset($_SESSION['reset_email']);
        $success = "Password u ndryshua me sukses.";
    }
}
?>

<?php include "./includes/header.php"; ?>

<style>
.reset-card {
    max-width: 420px;
    width: 100%;
    border-radius: 18px;
    box-shadow: 0 20px 45px rgba(0,0,0,.15);
    animation: fadeUp .6s ease;
}

.reset-icon {
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

.input-group-text {
    background: transparent;
    border-right: 0;
}

.form-control {
    border-left: 0;
}

.form-control:focus {
    box-shadow: none;
}
</style>

<div class="container min-vh-100 d-flex align-items-center justify-content-center">
    <div class="card reset-card p-4 text-center">

        <div class="reset-icon mb-2">🔐</div>

        <h4 class="mb-1">Reset Password</h4>
        <p class="text-muted small mb-4">
            Vendos nje password te ri per llogarine tende
        </p>

        <?php if ($error): ?>
            <div class="alert alert-danger py-2">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($success) ?><br><br>
                <a href="login.php" class="btn btn-success w-100">
                    🔑 Shko te Login
                </a>
            </div>
        <?php else: ?>

            <form method="POST">

                <div class="input-group mb-3">
                    <span class="input-group-text">🔑</span>
                    <input type="password" name="password" class="form-control" placeholder="Password i ri" required > 
                </div>

                <div class="input-group mb-3">
                    <span class="input-group-text">🔁</span>
                    <input type="password" name="confirm" class="form-control" placeholder="Konfirmo password-in" required >
                </div>

                <button class="btn btn-primary w-100 py-2">
                    ✅ Ndrysho Password
                </button>
            </form>

        <?php endif; ?>

    </div>
</div>

<?php include "./includes/footer.php"; ?>
