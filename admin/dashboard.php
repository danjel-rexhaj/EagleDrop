<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: /myplatform/access_denied.php");
    exit;
}

require "../includes/auth.php";
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
<link rel="stylesheet" href="./assets/css/admin.css">

<div class="container mt-5">
    <h2 class="mb-4">🛠️ Paneli i Administratorit</h2>

    <div class="row g-4">


        <div class="col-md-4">
            <div class="card shadow-sm border-0 bg-primary text-white">
                <div class="card-body text-center">
                    <i class="fas fa-users fa-2x mb-2"></i>
                    <h3><?= $totalUsers ?></h3>
                    <p class="mb-0">Perdorues gjithsej</p>
                </div>
            </div>
        </div>


        <div class="col-md-4">
            <div class="card shadow-sm border-0 bg-success text-white">
                <div class="card-body text-center">
                    <i class="fas fa-user-check fa-2x mb-2"></i>
                    <h3><?= $verifiedUsers ?></h3>
                    <p class="mb-0">Perdorues te verifikuar</p>
                </div>
            </div>
        </div>


        <div class="col-md-4">
            <div class="card shadow-sm border-0 bg-danger text-white">
                <div class="card-body text-center">
                    <i class="fas fa-user-lock fa-2x mb-2"></i>
                    <h3><?= $blockedUsers ?></h3>
                    <p class="mb-0">Perdorues te bllokuar</p>
                </div>
            </div>
        </div>

    </div>

<div class="mt-5 d-flex flex-wrap gap-3">

    <a href="users.php" class="btn btn-dark">
        <i class="fas fa-users-cog"></i> Menaxho Perdoruesit
    </a>

    <a href="payments.php" class="btn btn-outline-primary">
        <i class="fas fa-credit-card"></i> Pagesat
    </a>

    <a href="admin_logs.php" class="btn btn-outline-secondary">
        <i class="fas fa-file-alt"></i> System Logs
    </a>

    <a href="admin_login_attempts.php" class="btn btn-outline-danger">
        <i class="fas fa-shield-alt"></i> Login Attempts
    </a>

</div>

</div>

<?php include "../includes/footer.php"; ?>
