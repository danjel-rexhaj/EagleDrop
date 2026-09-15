<?php
require "./includes/auth.php";
require "./config/database.php";
require "./includes/support_conversation.php";

header('Content-Type: application/json');

$me = $_SESSION['user_id'];
$role = $_SESSION['role'];

if ($role !== 'user') {
    http_response_code(403);
    echo json_encode(['error' => 'Not a client account']);
    exit;
}

try {
    $conversation_id = getOrCreateSupportConversation($conn, $me);

    $stmt = $conn->prepare("SELECT status, staff_id FROM conversations WHERE id=?");
    $stmt->execute([$conversation_id]);
    $conv = $stmt->fetch(PDO::FETCH_ASSOC);

    $staffName = null;
    if ($conv['staff_id']) {
        $stmt = $conn->prepare("SELECT username FROM users WHERE id=?");
        $stmt->execute([$conv['staff_id']]);
        $staffName = $stmt->fetchColumn() ?: null;
    }

    $stmt = $conn->prepare("
        SELECT m.id, m.message, m.sender_id, m.created_at, u.username, u.role
        FROM messages m
        JOIN users u ON u.id = m.sender_id
        WHERE m.conversation_id = ?
        ORDER BY m.created_at ASC
    ");
    $stmt->execute([$conversation_id]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $conn->prepare("
        UPDATE notifications
        SET is_read = 1
        WHERE user_id = ? AND conversation_id = ? AND is_read = 0
    ")->execute([$me, $conversation_id]);

    echo json_encode([
        'conversation_id' => $conversation_id,
        'status' => $conv['status'],
        'staff_name' => $staffName,
        'messages' => $messages,
        'me' => $me,
    ]);
} catch (Throwable $e) {
    error_log("widget_init.php error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
