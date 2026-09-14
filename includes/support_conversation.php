<?php
// How a message sender's name should read to a client: staff/admin get a
// "· Support" suffix so it's obvious who's replying, without renaming the account.
function supportDisplayName(string $username, string $role): string {
    return in_array($role, ['staff', 'admin'], true) ? "$username · Support" : $username;
}

// Finds the client's existing support conversation, or creates one and
// assigns it to whichever staff/admin was assigned longest ago (round-robin).
function getOrCreateSupportConversation(PDO $conn, int $clientId): int {

    $stmt = $conn->prepare("
        SELECT id
        FROM conversations
        WHERE client_id=? AND type='support'
    ");
    $stmt->execute([$clientId]);
    $conv = $stmt->fetch();

    if ($conv) {
        return (int)$conv['id'];
    }

    $conn->beginTransaction();

    // Admins oversee support but never take clients themselves -- only
    // 'staff' accounts are eligible for round-robin assignment.
    $stmt = $conn->query("
        SELECT id
        FROM users
        WHERE role = 'staff'
        ORDER BY last_assigned_at IS NULL DESC, last_assigned_at ASC
        LIMIT 1
        FOR UPDATE
    ");
    $staffId = $stmt->fetchColumn();

    if (!$staffId) {
        $conn->rollBack();
        throw new RuntimeException('No staff available');
    }

    $conn->prepare("
        INSERT INTO conversations (client_id, staff_id, type)
        VALUES (?, ?, 'support')
    ")->execute([$clientId, $staffId]);

    $conversationId = (int)$conn->lastInsertId();

    $conn->prepare("
        UPDATE users SET last_assigned_at = NOW() WHERE id = ?
    ")->execute([$staffId]);

    $conn->prepare("
        INSERT INTO messages (conversation_id, sender_id, message)
        VALUES (?, ?, 'Përshëndetje! Unë do t\'ju ndihmoj me çdo gjë që ju nevojitet. Si mund t\'ju ndihmoj sot?')
    ")->execute([$conversationId, $staffId]);

    $conn->commit();

    return $conversationId;
}
