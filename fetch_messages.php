<?php
require "./includes/auth.php";
require "./config/database.php";

$me = $_SESSION['user_id'];
$conversation_id = (int)$_GET['conversation_id'];
$last_id = (int)$_GET['last_id'];

$stmt = $conn->prepare("
    SELECT 
        m.id,
        m.message,
        m.sender_id,
        m.created_at,
        u.username
    FROM messages m
    JOIN users u ON u.id = m.sender_id
    WHERE m.conversation_id = ?
    AND m.id > ?
    ORDER BY m.id ASC
");
$stmt->execute([$conversation_id, $last_id]);

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
