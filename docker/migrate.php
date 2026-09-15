<?php
// Runs at container startup (see entrypoint.sh) to bring the live schema in
// line with what the app code expects. Safe to run on every boot: each
// change is guarded by an information_schema check, so an already-migrated
// database is a no-op.

require __DIR__ . '/../config/database.php';

function columnExists(PDO $conn, string $table, string $column): bool {
    $stmt = $conn->prepare("
        SELECT COUNT(*) FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?
    ");
    $stmt->execute([$table, $column]);
    return (bool) $stmt->fetchColumn();
}

// conversations.status / closed_at: used by close_conversation.php,
// send_message.php, fetch_messages.php and widget_init.php to track
// whether a support conversation is open or closed, but the columns were
// never added to the table itself.
if (!columnExists($conn, 'conversations', 'status')) {
    $conn->exec("ALTER TABLE conversations ADD COLUMN status ENUM('open','closed') NOT NULL DEFAULT 'open'");
    echo "migrate: added conversations.status\n";
}

if (!columnExists($conn, 'conversations', 'closed_at')) {
    $conn->exec("ALTER TABLE conversations ADD COLUMN closed_at DATETIME NULL DEFAULT NULL");
    echo "migrate: added conversations.closed_at\n";
}

echo "migrate: schema check complete\n";
