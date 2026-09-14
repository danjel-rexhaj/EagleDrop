<?php
require "./includes/auth.php";
require "./config/database.php";

header('Content-Type: application/json');

$me = $_SESSION['user_id'];
$role = $_SESSION['role'];

if (!in_array($role, ['staff', 'admin'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Not authorized']);
    exit;
}

$conversation_id = (int)($_POST['conversation_id'] ?? 0);

$stmt = $conn->prepare("SELECT client_id, staff_id, type FROM conversations WHERE id=?");
$stmt->execute([$conversation_id]);
$conv = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$conv || $conv['type'] !== 'support') {
    http_response_code(404);
    echo json_encode(['error' => 'Conversation not found']);
    exit;
}

$allowed = ($role === 'admin' || (int)$conv['staff_id'] === (int)$me);

if (!$allowed) {
    http_response_code(403);
    echo json_encode(['error' => 'Not authorized for this conversation']);
    exit;
}

$conn->prepare("
    UPDATE conversations SET status='closed', closed_at=NOW() WHERE id=?
")->execute([$conversation_id]);

$conn->prepare("
    INSERT INTO notifications (user_id, conversation_id, title, message, type, is_read)
    VALUES (?, ?, 'Support', 'Biseda u mbyll nga stafi', 'chat', 0)
")->execute([$conv['client_id'], $conversation_id]);

echo json_encode(['ok' => true, 'status' => 'closed']);
