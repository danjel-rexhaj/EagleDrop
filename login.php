<?php
session_start();
require "./config/database.php";
require "./includes/log.php";

$message = "";

if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_token'])) {
    $token = $_COOKIE['remember_token'];

    $query = $conn->prepare("SELECT user_id FROM remember_tokens WHERE token = ?");
    $query->execute([$token]);

    if ($query->rowCount() > 0) {
        $user_id = $query->fetch(PDO::FETCH_ASSOC)['user_id'];

        $user = $conn->prepare("SELECT * FROM users WHERE id = ?");
        $user->execute([$user_id]);
        $user = $user->fetch(PDO::FETCH_ASSOC);

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['last_activity'] = time();
        $_SESSION['name'] = $user['name'];



        header("Location: index.php");
        exit();
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["email"]);
    $password = $_POST["password"];
    $remember = isset($_POST["remember"]);

    $query = $conn->prepare(
        "SELECT * FROM users 
        WHERE email = ? 
            OR username = ? 
            OR phone = ?"
    );
    $query->execute([$email, $email, $email]); 


    if ($query->rowCount() == 0) {

        addLog(
            'failed_login',
            'Tentative login me email/username (user not found)',
            $email
        );

        $message = "Email i pasakte!";
    } else {
        $user = $query->fetch(PDO::FETCH_ASSOC);
        $user_id = $user['id'];

        $attempt = $conn->prepare("SELECT * FROM login_attempts WHERE user_id = ?");
        $attempt->execute([$user_id]);
        $data = $attempt->fetch(PDO::FETCH_ASSOC);

        $attempts = $data['attempts'] ?? 0;
        $last = strtotime($data['last_attempt'] ?? 0);

        if ($attempts >= 7) {

            $blockTime = 1800; 
            $elapsed = time() - $last;
            $remaining = $blockTime - $elapsed;

        if ($remaining > 0) {

            $minutes = ceil($remaining / 60);

            $message = "Llogaria eshte bllokuar. Provoni perseri pas $minutes minutash.";

            addLog(
                'blocked_login',
                'Tentative login ne llogari te bllokuar',
                $user['email']
            );

        } else {
           
            $reset = $conn->prepare("DELETE FROM login_attempts WHERE user_id = ?");
            $reset->execute([$user_id]);
        }

        } else if (!password_verify($password, $user['password'])) {
            addLog(
                'failed_login',
                'Tentative login me email/username',
                $email
            );


            
            if ($attempts == 0) {
                $insert = $conn->prepare("INSERT INTO login_attempts (user_id, attempts, last_attempt) VALUES (?,?,NOW())");
                $insert->execute([$user_id, 1]);
            } else {
                $update = $conn->prepare("UPDATE login_attempts SET attempts = attempts + 1, last_attempt = NOW() WHERE user_id = ?");
                $update->execute([$user_id]);
            }

            $message = "Fjalekalimi eshte i pasakte!";
        } 
        else {
            
            $reset = $conn->prepare("DELETE FROM login_attempts WHERE user_id = ?");
            $reset->execute([$user_id]);


            if ($user['is_verified'] == 0) {


            $newCode = rand(100000, 999999);


            $updateCode = $conn->prepare("UPDATE users SET verification_code = ? WHERE email = ?");
            $updateCode->execute([$newCode, $email]);


            require "./config/mailer.php";
            sendVerificationEmail($email, $newCode);


            $_SESSION['pending_email'] = $email;

            header("Location: verify.php");
            exit();
        }


            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['last_activity'] = time();
            $_SESSION['name'] = $user['name'];

            addLog(
                'login',
                'User u kyç ne platforme',
                $user['email']
            );



            if ($remember) {
                $token = bin2hex(random_bytes(32));

                setcookie("remember_token", $token, time() + (86400 * 30), "/");

                $conn->prepare("INSERT INTO remember_tokens (user_id, token, expires_at) VALUES (?,?,DATE_ADD(NOW(), INTERVAL 30 DAY))")
                     ->execute([$user_id, $token]);
            }


            if ($user['role'] === "admin") {
                header("Location: admin/dashboard.php");
                exit();
            }


            header("Location: profile.php");
            exit();
        }
    }
}
?>

<?php include "./includes/header.php"; ?>

<style>
    body {
        background-color: #0f1113;
        color: #fff;
    }

    .login-card {
        background-color: #1c1f22;
        padding: 35px;
        border-radius: 12px;
        width: 350px;
        box-shadow: 0 0 20px rgba(0,0,0,0.4);
    }

    .insta-title {
        font-family: 'Billabong', cursive;
        font-size: 50px;
        text-align: center;
        margin-bottom: 25px;
    }

    input.form-control {
        background-color: #121416;
        border: 1px solid #333;
        color: #fff;
    }

    input.form-control:focus {
        background-color: #1b1e21;
        color: #fff;
        border-color: #4c5fff;
        box-shadow: 0 0 5px #4c5fff;
    }

    .btn-login {
        background-color: #4267B2;
        width: 100%;
        color: #fff;
        font-weight: 600;
    }

    .btn-login:hover {
        background-color: #365899;
        color: #fff;
    }

    a {
        color: #4c5fff;
        text-decoration: none;
    }

    a:hover {
        text-decoration: underline;
    }
</style>

<div class="container d-flex justify-content-center align-items-center" style="height: 100vh;">
    <div class="login-card">

        <div class="insta-title">Eagle Drop</div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-danger text-center"><?= $message ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="email" name="email" class="form-control mb-3" placeholder="Email" required>
            <input type="password" name="password" class="form-control mb-3" placeholder="Password" required>

            <button class="btn btn-login mb-3">Log in</button>
        </form>

        <div class="text-center mb-2">
            <a href="forgot_password.php">Forgot your password?</a>
        </div>

        <div class="text-center">
            Don't have an account?
            <a href="register.php">Sign up</a>
        </div>

    </div>
</div>

<?php include "./includes/footer.php"; ?>

