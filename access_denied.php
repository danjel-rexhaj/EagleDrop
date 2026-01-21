<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="sq">
<head>
    <meta charset="UTF-8">
    <title>Access Denied</title>
    <link rel="stylesheet" href="/myplatform/assets/css/style.css">
    <style>
        body {
            background-color: #0f1113;
            color: #fff;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            font-family: sans-serif;
        }

        .denied-box {
            background: #1c1f22;
            padding: 40px;
            border-radius: 14px;
            text-align: center;
            box-shadow: 0 0 25px rgba(0,0,0,.5);
            max-width: 420px;
        }

        .denied-box h1 {
            color: #ff4d4d;
            margin-bottom: 15px;
        }

        .denied-box a {
            margin-top: 20px;
            display: inline-block;
            color: #4c5fff;
        }
    </style>
</head>
<body>

<div class="denied-box">
    <h1>⛔ Nuk keni akses</h1>
    <p>Kjo faqe eshte vetem per administratoret.</p>

    <a href="/myplatform/index.php">← Kthehu ne faqe kryesore</a>
</div>

</body>
</html>
