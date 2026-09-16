<?php
    require_once __DIR__ . '/session_bootstrap.php';

    if (!isset($_SESSION['user_id'])) {
        header("Location: /login.php");
        exit();
    }

    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 900)) {
        session_unset();
        session_destroy();
        header("Location: /login.php?timeout=1");
        exit();
    }

    

    $_SESSION['last_activity'] = time();
?>