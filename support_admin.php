<?php
require "./includes/auth.php";
require "./config/database.php";

$me = $_SESSION['user_id'];
$role = $_SESSION['role'];

if (!in_array($role, ['staff','admin'])) die("Access denied");


$stmt = $conn->query("SELECT id FROM conversations WHERE type='staff' LIMIT 1");
$staffRoomId = $stmt->fetchColumn();


$staffUsers = $conn->prepare("
    SELECT 
        u.id,
        u.username,
        (
            SELECT COUNT(*)
            FROM notifications n
            WHERE n.user_id = :me
            AND n.is_read = 0
            AND n.conversation_id = (
                SELECT c.id
                FROM conversations c
                WHERE c.type = 'staff'
                AND (
                    (c.client_id = :me AND c.staff_id = u.id)
                    OR
                    (c.client_id = u.id AND c.staff_id = :me)
                )
                LIMIT 1
            )
        ) AS unread
    FROM users u
    WHERE u.role IN ('staff','admin')
    AND u.id != :me
");
$staffUsers->execute(['me' => $me]);
$staffUsers = $staffUsers->fetchAll();


$stmt = $conn->prepare("
    SELECT 
        c.id,
        u.username,
        (
            SELECT m.message
            FROM messages m
            WHERE m.conversation_id = c.id
            ORDER BY m.created_at DESC
            LIMIT 1
        ) AS last_message,
        COUNT(n.id) AS unread
    FROM conversations c
    JOIN users u ON u.id = c.client_id
    LEFT JOIN notifications n
        ON n.conversation_id = c.id
        AND n.user_id = ?
        AND n.is_read = 0
    WHERE c.type = 'support'
    GROUP BY c.id
    ORDER BY c.created_at DESC
");
$stmt->execute([$me]);
$clientConvs = $stmt->fetchAll(PDO::FETCH_ASSOC);

$conversation_id = isset($_GET['c']) ? (int)$_GET['c'] : null;
$staff_to = isset($_GET['staff']) ? (int)$_GET['staff'] : null;
$is_staff_private = false;


if ($staff_to) {
    $a = min($me, $staff_to);
    $b = max($me, $staff_to);

    $stmt = $conn->prepare("
        SELECT id FROM conversations
        WHERE type='staff' AND client_id=? AND staff_id=?
        LIMIT 1
    ");
    $stmt->execute([$a, $b]);
    $conversation_id = $stmt->fetchColumn();

    if (!$conversation_id) {
        $stmt = $conn->prepare("
            INSERT INTO conversations (client_id, staff_id, type)
            VALUES (?, ?, 'staff')
        ");
        $stmt->execute([$a, $b]);
        $conversation_id = $conn->lastInsertId();
    }

    $is_staff_private = true;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && $conversation_id) {
    $msg = trim($_POST['message']);

    if ($msg !== '') {


        $conn->prepare("
            INSERT INTO messages (conversation_id, sender_id, message)
            VALUES (?,?,?)
        ")->execute([$conversation_id, $me, $msg]);

 
        $stmt = $conn->prepare("
            SELECT client_id, staff_id
            FROM conversations
            WHERE id=?
        ");
        $stmt->execute([$conversation_id]);
        $c = $stmt->fetch();

  


        if ($role === 'client') {


            if (!$c['staff_id']) {

                try {
                    $conn->beginTransaction();


                    $stmt = $conn->query("
                        SELECT id
                        FROM users
                        WHERE role IN ('staff','admin')
                        ORDER BY 
                            last_assigned_at IS NULL DESC,
                            last_assigned_at ASC
                        LIMIT 1
                        FOR UPDATE
                    ");
                    $staff_id = $stmt->fetchColumn();

                    if (!$staff_id) {
                        $conn->rollBack();
                        throw new Exception('No staff available');
                    }


                    $conn->prepare("
                        UPDATE conversations
                        SET staff_id = ?
                        WHERE id = ?
                    ")->execute([$staff_id, $conversation_id]);

                    $conn->prepare("
                        UPDATE users
                        SET last_assigned_at = NOW()
                        WHERE id = ?
                    ")->execute([$staff_id]);

                    $conn->commit();

                } catch (Exception $e) {
                    $conn->rollBack();
                    exit;
                }

            } else {
                $staff_id = $c['staff_id'];
            }

            $conn->prepare("
                INSERT INTO notifications (user_id, conversation_id, title, message)
                VALUES (?, ?, 'New support message', 'A client sent a message')
            ")->execute([$staff_id, $conversation_id]);
        }

        else {
            $conn->prepare("
                INSERT INTO notifications (user_id, conversation_id, title, message)
                VALUES (?, ?, 'Support reply', 'Staff replied to your message')
            ")->execute([$c['client_id'], $conversation_id]);
        }
    }

    header("Location: support_admin.php" . ($staff_to ? "?staff=$staff_to" : "?c=$conversation_id"));
    exit;
}



$messages = [];
if ($conversation_id) {
    $stmt = $conn->prepare("
        SELECT m.*, u.username
        FROM messages m
        JOIN users u ON u.id = m.sender_id
        WHERE m.conversation_id=?
        ORDER BY m.created_at ASC
    ");
    $stmt->execute([$conversation_id]);
    $messages = $stmt->fetchAll();


    $conn->prepare("
        UPDATE notifications
        SET is_read = 1
        WHERE user_id = ?
        AND conversation_id = ?
        AND is_read = 0
    ")->execute([$me, $conversation_id]);
}

require "./includes/header.php";
?>



<link rel="stylesheet" href="./assets/css/chat.css">

<div class="chat-container role-staff">
<div class="chat-header">🧑‍💼 Support Panel</div>

<div class="chat-admin-body">
<div class="chat-users">

<a class="pinned <?= $conversation_id==$staffRoomId?'active':'' ?>"
   href="?c=<?= $staffRoomId ?>">📌 Staff Chat</a>
<hr>

<?php foreach($staffUsers as $s): ?>
<a href="?staff=<?= $s['id'] ?>"
   class="<?= ($is_staff_private && $staff_to==$s['id'])?'active':'' ?>">

👤 <?= htmlspecialchars($s['username']) ?>

<?php if ($s['unread'] > 0): ?>
    <span class="badge bg-danger ms-1">
        <?= $s['unread'] ?>
    </span>
<?php endif; ?>

</a>
<?php endforeach; ?>


<hr>

<?php foreach($clientConvs as $c): ?>
<a href="?c=<?= $c['id'] ?>" class="chat-item <?= $conversation_id==$c['id']?'active':'' ?>">

    <div class="chat-name">
        <?= htmlspecialchars($c['username']) ?>

        <?php if ($c['unread'] > 0): ?>
            <span class="badge bg-danger ms-1">
                <?= $c['unread'] ?>
            </span>
        <?php endif; ?>
    </div>

    <?php if (!empty($c['last_message'])): ?>
        <div class="last-msg">
            <?= htmlspecialchars(mb_strimwidth($c['last_message'], 0, 30, '…')) ?>
        </div>
    <?php endif; ?>

</a>
<?php endforeach; ?>


</div>

<div class="chat-main">
    <?php if($conversation_id): ?>
        <div class="chat-messages" id="chatMessages">
            <?php foreach($messages as $m): ?>
                <div class="message <?= $m['sender_id']==$me?'me':'other' ?>" data-id="<?= $m['id'] ?>">
                    <div class="meta">
                        <span class="name"><?= htmlspecialchars($m['username']) ?></span>

                    </div>
                    <div class="bubble">
                        <?= nl2br(htmlspecialchars($m['message'])) ?>
                        <div class="time"><?= date('H:i', strtotime($m['created_at'])) ?></div>
                    </div>

                </div>
            <?php endforeach; ?>
</div>

    <div id="typingIndicator" class="typing-indicator" style="display:none;">
        typing…
    </div>


<form method="POST" class="chat-input">
    <?php if ($is_staff_private && $staff_to): ?>
        <input type="hidden" name="staff_to" value="<?= $staff_to ?>">
    <?php endif; ?>

    <textarea name="message" id="chatTextarea" placeholder="Write…" required></textarea>
    <button>➤</button>
</form>

<?php else: ?>
<div class="chat-empty">Zgjidh nje chat 👈</div>
<?php endif; ?>
</div>

</div>
</div>

<script>
const chatBox = document.getElementById('chatMessages');
const textarea = document.querySelector('.chat-input textarea');


if (!chatBox || !textarea) {
    console.log('No active chat – JS stopped');
} else {

    let lastMessageId = 0;

    document.querySelectorAll('#chatMessages .message').forEach(m => {
        const id = parseInt(m.dataset.id);
        if (id > lastMessageId) lastMessageId = id;
    });

    function scrollBottom() {
        chatBox.scrollTop = chatBox.scrollHeight;
    }

    scrollBottom();

    function sendMessage() {
        const msg = textarea.value.trim();
        if (!msg) return;

        textarea.value = '';
        textarea.focus();

        fetch('send_message.php', {
            method: 'POST',
            headers: {'Content-Type':'application/x-www-form-urlencoded'},
            body: `conversation_id=<?= $conversation_id ?>&message=${encodeURIComponent(msg)}`
        })
        .then(r => r.json())
        .then(m => {
            const div = document.createElement('div');
            div.className = 'message me';
            div.dataset.id = m.id;

            div.innerHTML = `
                <div class="meta">
                    <span class="name">${m.username}</span>
                </div>
                <div class="bubble">
                    ${m.message.replace(/\n/g,'<br>')}
                    <div class="msg-time">${m.created_at.substr(11,5)}</div>
                </div>
            `;

            chatBox.appendChild(div);
            lastMessageId = m.id;
            scrollBottom();
        });
    }


    textarea.addEventListener('keydown', e => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });

    setInterval(() => {
        fetch(`fetch_messages.php?conversation_id=<?= $conversation_id ?>&last_id=${lastMessageId}`)
            .then(r => r.json())
            .then(messages => {
                messages.forEach(m => {
                    const div = document.createElement('div');
                    div.className = 'message ' + (m.sender_id == <?= $me ?> ? 'me' : 'other');
                    div.dataset.id = m.id;

                    div.innerHTML = `
                        <div class="meta">
                            <span class="name">${m.username}</span>
                        </div>
                        <div class="bubble">
                            ${m.message.replace(/\n/g,'<br>')}
                            <div class="msg-time">${m.created_at.substr(11,5)}</div>
                        </div>
                    `;

                    chatBox.appendChild(div);
                    lastMessageId = m.id;
                });

                if (messages.length) scrollBottom();
            });
    }, 2000);

}

</script>





<?php require "./includes/footer.php"; ?>

