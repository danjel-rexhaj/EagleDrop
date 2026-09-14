<?php
require "./includes/auth.php";
require "./config/database.php";

$me = $_SESSION['user_id'];
$role = $_SESSION['role'];
$conversation_id = (int)$_POST['conversation_id'];
$msg = trim($_POST['message']);

if ($msg === '') {
    echo json_encode(['error' => 'Empty message']);
    exit;
}

$stmt = $conn->prepare("SELECT client_id, staff_id, type, status FROM conversations WHERE id=?");
$stmt->execute([$conversation_id]);
$conv = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$conv) {
    http_response_code(404);
    echo json_encode(['error' => 'Conversation not found']);
    exit;
}

// A client conversation only accepts messages from its own client, the staff
// member it's assigned to, or an admin -- nobody else can jump into it.
// A staff-to-staff conversation only accepts messages from its two participants.
$allowed = ($conv['type'] === 'support')
    ? ((int)$me === (int)$conv['client_id'] || (int)$me === (int)$conv['staff_id'] || $role === 'admin')
    : ((int)$me === (int)$conv['client_id'] || (int)$me === (int)$conv['staff_id']);

if (!$allowed) {
    http_response_code(403);
    echo json_encode(['error' => 'Not authorized for this conversation']);
    exit;
}

// Writing into a closed support conversation reopens it -- staff or client
// picking it back up is what "closed" is for, not a dead end.
if ($conv['type'] === 'support' && $conv['status'] === 'closed') {
    $conn->prepare("
        UPDATE conversations SET status='open', closed_at=NULL WHERE id=?
    ")->execute([$conversation_id]);
}

$conn->prepare("
    INSERT INTO messages (conversation_id, sender_id, message)
    VALUES (?, ?, ?)
")->execute([$conversation_id, $me, $msg]);

$message_id = $conn->lastInsertId();


$stmt = $conn->prepare("
    SELECT m.id, m.message, m.sender_id, m.created_at, u.username, u.role
    FROM messages m
    JOIN users u ON u.id = m.sender_id
    WHERE m.id = ?
");
$stmt->execute([$message_id]);
$message = $stmt->fetch(PDO::FETCH_ASSOC);


$targets = [];

if ($me == $conv['client_id'] && !empty($conv['staff_id'])) {

    $targets[] = $conv['staff_id'];
}

if ($me == $conv['staff_id']) {

    $targets[] = $conv['client_id'];
}

foreach ($targets as $uid) {
    if (!$uid) continue;

    $conn->prepare("
        INSERT INTO notifications (user_id, conversation_id, title, message, type, is_read)
        VALUES (?, ?, 'New chat message', 'You have a new support message', 'chat', 0)
    ")->execute([$uid, $conversation_id]);
}

$message['status'] = 'open';
echo json_encode($message);
