<meta name="viewport" content="width=device-width, initial-scale=1.0">

<?php
session_start();


if (isset($_COOKIE['remember_token'])) {
    setcookie("remember_token", "", time() - 3600, "/");
}

session_destroy();

header("Location: login.php");
exit();
?>
