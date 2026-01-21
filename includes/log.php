<?php
function addLog($type, $message, $email = null) {
    global $conn;

    $userId = $_SESSION['user_id'] ?? null;

    
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    } else {
        $ip = 'UNKNOWN';
    }

    
    $agent = $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN';

    $stmt = $conn->prepare("
        INSERT INTO logs (type, message, user_id, email, ip_address, user_agent)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $type,
        $message,
        $userId,
        $email,
        $ip,
        $agent
    ]);
}
