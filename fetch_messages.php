<?php
require "./includes/auth.php";
require "./config/database.php";

header('Content-Type: application/json');

$me = $_SESSION['user_id'];
$role = $_SESSION['role'];
$conversation_id = (int)$_GET['conversation_id'];
$last_id = (int)$_GET['last_id'];

$stmt = $conn->prepare("SELECT client_id, staff_id, type, status FROM conversations WHERE id=?");
$stmt->execute([$conversation_id]);
$conv = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$conv) {
    http_response_code(404);
    echo json_encode(['error' => 'Conversation not found']);
    exit;
}

$allowed = ($conv['type'] === 'support')
    ? ((int)$me === (int)$conv['client_id'] || (int)$me === (int)$conv['staff_id'] || $role === 'admin')
    : ((int)$me === (int)$conv['client_id'] || (int)$me === (int)$conv['staff_id']);

if (!$allowed) {
    http_response_code(403);
    echo json_encode(['error' => 'Not authorized for this conversation']);
    exit;
}

$stmt = $conn->prepare("
    SELECT
        m.id,
        m.message,
        m.sender_id,
        m.created_at,
        u.username,
        u.role
    FROM messages m
    JOIN users u ON u.id = m.sender_id
    WHERE m.conversation_id = ?
    AND m.id > ?
    ORDER BY m.id ASC
");
$stmt->execute([$conversation_id, $last_id]);

echo json_encode([
    'status' => $conv['status'],
    'messages' => $stmt->fetchAll(PDO::FETCH_ASSOC),
]);
