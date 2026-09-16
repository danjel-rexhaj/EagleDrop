<?php
// Shared by auth.php (protected pages) and header.php (public pages) so the
// "remember me" cookie restores the session everywhere consistently -- not
// just on pages that happen to require auth.php. Without this, the navbar
// on a public page (index.php, products.php, ...) would show a logged-out
// state after the session was lost (idle timeout, or the server restarting
// and wiping in-memory sessions) even though a valid remember_token exists.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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
