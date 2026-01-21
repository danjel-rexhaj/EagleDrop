<?php
require "./includes/auth.php";
require "./config/database.php";

$me = $_SESSION['user_id'];
$conversation_id = (int)$_POST['conversation_id'];
$msg = trim($_POST['message']);

if ($msg === '') {
    echo json_encode(['error' => 'Empty message']);
    exit;
}


$conn->prepare("
    INSERT INTO messages (conversation_id, sender_id, message)
    VALUES (?, ?, ?)
")->execute([$conversation_id, $me, $msg]);

$message_id = $conn->lastInsertId();


$stmt = $conn->prepare("
    SELECT m.id, m.message, m.sender_id, m.created_at, u.username
    FROM messages m
    JOIN users u ON u.id = m.sender_id
    WHERE m.id = ?
");
$stmt->execute([$message_id]);
$message = $stmt->fetch(PDO::FETCH_ASSOC);


$stmt = $conn->prepare("
    SELECT client_id, staff_id
    FROM conversations
    WHERE id = ?
");
$stmt->execute([$conversation_id]);
$c = $stmt->fetch();



$targets = [];

if ($me == $c['client_id'] && !empty($c['staff_id'])) {

    $targets[] = $c['staff_id'];
}

if ($me == $c['staff_id']) {

    $targets[] = $c['client_id'];
}

foreach ($targets as $uid) {
    if (!$uid) continue;

    $conn->prepare("
        INSERT INTO notifications (user_id, conversation_id, title, message, type, is_read)
        VALUES (?, ?, 'New chat message', 'You have a new support message', 'chat', 0)
    ")->execute([$uid, $conversation_id]);
}

echo json_encode($message);
