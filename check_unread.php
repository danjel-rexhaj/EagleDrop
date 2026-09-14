<?php
require "./includes/auth.php";
require "./config/database.php";

$me = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0");
$stmt->execute([$me]);
$total = (int)$stmt->fetchColumn();

$stmt = $conn->prepare("
    SELECT conversation_id, COUNT(*) AS unread
    FROM notifications
    WHERE user_id = ? AND is_read = 0
    GROUP BY conversation_id
");
$stmt->execute([$me]);
$perConversation = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode([
    'total' => $total,
    'conversations' => $perConversation,
]);
