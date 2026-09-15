<?php
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Session lost (expired, or the server restarted and wiped in-memory
    // sessions) but a remember-me cookie is still valid -- restore login
    // from the DB-backed token instead of forcing a fresh sign-in.
    if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_token'])) {
        require_once __DIR__ . '/../config/database.php';

        $stmt = $conn->prepare("
            SELECT u.id, u.role, u.name
            FROM remember_tokens t
            JOIN users u ON u.id = t.user_id
            WHERE t.token = ? AND t.expires_at > NOW()
        ");
        $stmt->execute([$_COOKIE['remember_token']]);
        $remembered = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($remembered) {
            $_SESSION['user_id'] = $remembered['id'];
            $_SESSION['role'] = $remembered['role'];
            $_SESSION['name'] = $remembered['name'];
        } else {
            setcookie('remember_token', '', time() - 3600, '/');
        }
    }

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