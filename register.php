<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
require "./config/database.php";
require "./config/mailer.php";
require "./config/crm.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $first = trim($_POST["first_name"]);
    $last = trim($_POST["last_name"]);
    if (isset($_GET['debug_bytes'])) {
        die(json_encode([
            'raw_first_hex' => bin2hex($first),
            'default_charset_ini' => ini_get('default_charset'),
            'mbstring_http_input' => ini_get('mbstring.http_input'),
            'mbstring_encoding_translation' => ini_get('mbstring.encoding_translation'),
            'mbstring_internal_encoding' => ini_get('mbstring.internal_encoding'),
            'mbstring_func_overload' => ini_get('mbstring.func_overload'),
            'input_encoding' => ini_get('input_encoding'),
            'content_type_header' => $_SERVER['CONTENT_TYPE'] ?? null,
        ]));
    }
    $username = trim($_POST["username"]);
    $phone = trim($_POST["phone"]);
    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $code = rand(100000,999999);


    
    $check = $conn->prepare("SELECT * FROM users WHERE email=? OR username=? OR phone=?");
    $check->execute([$email, $username, $phone]);

    if ($check->rowCount() > 0) {
        $message = "❌ Email / Username / Phone ekziston!";
    } else {

        $insert = $conn->prepare(
            "INSERT INTO users (first_name, last_name, username, phone, email, password, verification_code)
             VALUES (?,?,?,?,?,?,?)"
        );
        $insert->execute([$first, $last, $username, $phone, $email, $hashed, $code]);

        sendLeadToCRM($first, $last, $email, $phone);

        $emailSent = sendVerificationEmail($email, $code);

        $_SESSION['pending_email'] = $email;
        if (!$emailSent) {
            $_SESSION['verify_send_failed'] = true;
        }
        header("Location: verify.php");
        exit();
    }
}
?>

<?php include "./includes/header.php"; ?>

<div class="register-container">

    <div class="register-box">

        <h1 class="insta-title">EagleDrop</h1>

        <p class="text-danger text-center"><?= $message ?></p>

        <form method="POST">

            <input type="text" name="first_name" class="reg-input" placeholder="First Name" required>

            <input type="text" name="last_name" class="reg-input" placeholder="Last Name" required>

            <input type="text" name="username" class="reg-input" placeholder="Username" required>

            <input type="text" name="phone" class="reg-input" placeholder="Phone Number" required>

            <input type="text" name="email" class="reg-input" placeholder="Email" required>

            <input type="password" name="password" class="reg-input" placeholder="Password" required>

            <button class="reg-btn">Sign up</button>
        </form>

        <p class="reg-policy">
            By signing up, you agree to our <b>Terms, Data Policy</b> and <b>Cookies Policy</b>.
        </p>

    </div>

    <div class="login-redirect">
        Have an account? <a href="login.php">Log in</a>
    </div>

</div>

<?php include "./includes/footer.php"; ?>
