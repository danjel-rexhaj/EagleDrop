<?php
require "../includes/auth.php";

if (($_SESSION['role'] ?? null) !== 'admin') {
    header("Location: /access_denied.php");
    exit;
}

require "../config/database.php";




$totalUsers = $conn->query("SELECT COUNT(*) FROM users")->fetchColumn();

$verifiedUsers = $conn->query("
    SELECT COUNT(*) 
    FROM users 
    WHERE is_verified = 1
")->fetchColumn();

$blockedUsers = $conn->query("
    SELECT COUNT(*) 
    FROM login_attempts 
    WHERE attempts >= 7 
")->fetchColumn();
?>

<?php include "../includes/admin_header.php"; ?>
<link rel="stylesheet" href="/assets/css/admin.css">

<div class="admin-dashboard">
    <div class="dashboard-wrapper">

        <h2 class="mb-2"><i class="bi bi-tools"></i> Paneli i Administratorit</h2>
        <p class="text-muted mb-0">Përmbledhje e shpejtë e platformës dhe qasje te menaxhimi.</p>

        <div class="dashboard-cards">

            <div class="dashboard-card bg-blue">
                <i class="bi bi-people-fill fs-2 mb-2 d-block"></i>
                <h3><?= $totalUsers ?></h3>
                <p>Përdorues gjithsej</p>
            </div>

            <div class="dashboard-card bg-green">
                <i class="bi bi-patch-check-fill fs-2 mb-2 d-block"></i>
                <h3><?= $verifiedUsers ?></h3>
                <p>Përdorues të verifikuar</p>
            </div>

            <div class="dashboard-card bg-red">
                <i class="bi bi-shield-lock-fill fs-2 mb-2 d-block"></i>
                <h3><?= $blockedUsers ?></h3>
                <p>Përdorues të bllokuar</p>
            </div>

        </div>

        <div class="dashboard-actions">

            <a href="users.php" class="btn btn-dark">
                <i class="bi bi-people"></i> Menaxho Përdoruesit
            </a>

            <a href="payments.php" class="btn btn-outline-primary">
                <i class="bi bi-credit-card"></i> Pagesat
            </a>

            <a href="admin_logs.php" class="btn btn-outline-secondary">
                <i class="bi bi-file-earmark-text"></i> System Logs
            </a>

            <a href="admin_login_attempts.php" class="btn btn-outline-danger">
                <i class="bi bi-shield-exclamation"></i> Login Attempts
            </a>

        </div>

    </div>
</div>

<?php include "../includes/footer.php"; ?>
