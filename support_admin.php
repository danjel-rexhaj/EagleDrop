<?php
require "./includes/auth.php";
require "./config/database.php";

$me = $_SESSION['user_id'];
$role = $_SESSION['role'];

if (!in_array($role, ['staff','admin'])) die("Access denied");


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


// Admin sees every client conversation (with who it's assigned to); regular
// staff only sees the conversations assigned to them -- nobody else's clients
// show up in their list, so they can't wander into someone else's chat.
if ($role === 'admin') {
    $stmt = $conn->prepare("
        SELECT
            c.id,
            c.status,
            u.username,
            s.username AS staff_name,
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
        LEFT JOIN users s ON s.id = c.staff_id
        LEFT JOIN notifications n
            ON n.conversation_id = c.id
            AND n.user_id = ?
            AND n.is_read = 0
        WHERE c.type = 'support'
        GROUP BY c.id
        ORDER BY c.created_at DESC
    ");
    $stmt->execute([$me]);
} else {
    $stmt = $conn->prepare("
        SELECT
            c.id,
            c.status,
            u.username,
            NULL AS staff_name,
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
        WHERE c.type = 'support' AND c.staff_id = ?
        GROUP BY c.id
        ORDER BY c.created_at DESC
    ");
    $stmt->execute([$me, $me]);
}
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


// Authorization: a conversation only opens for people allowed in it -- the
// assigned staff member (or an admin) for a client conversation, and the two
// participants for a staff DM. Guessing another conversation's ?c=id redirects away.
$convType = null;
$convStatus = null;

if ($conversation_id) {
    $stmt = $conn->prepare("SELECT client_id, staff_id, type, status FROM conversations WHERE id=?");
    $stmt->execute([$conversation_id]);
    $conv = $stmt->fetch(PDO::FETCH_ASSOC);

    $allowed = $conv && (
        ($conv['type'] === 'support' && ($role === 'admin' || (int)$conv['staff_id'] === (int)$me))
        || ($conv['type'] === 'staff' && ((int)$conv['client_id'] === (int)$me || (int)$conv['staff_id'] === (int)$me))
    );

    if (!$allowed) {
        header("Location: support_admin.php");
        exit;
    }

    $convType = $conv['type'];
    $convStatus = $conv['status'];
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



<link rel="stylesheet" href="./assets/css/chat.css?v=<?= filemtime(__DIR__ . '/assets/css/chat.css') ?>">

<audio id="notifSound" src="./assets/sounds/notify.mp3" preload="auto"></audio>

<div class="chat-container role-staff">
<div class="chat-header">🧑‍💼 Support Panel</div>

<div class="chat-admin-body">
<div class="chat-users">

<?php foreach($staffUsers as $s): ?>
<a href="?staff=<?= $s['id'] ?>"
   class="<?= ($is_staff_private && $staff_to==$s['id'])?'active':'' ?>">

👤 <?= htmlspecialchars($s['username']) ?>

<?php if ($s['unread'] > 0): ?>
    <span class="badge">
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
        <?php if ($c['status'] === 'closed'): ?>
            <span class="status-tag">mbyllur</span>
        <?php endif; ?>

        <?php if ($c['unread'] > 0): ?>
            <span class="badge">
                <?= $c['unread'] ?>
            </span>
        <?php endif; ?>
    </div>

    <?php if ($role === 'admin'): ?>
        <div class="assigned-tag">
            👤 Caktuar te: <strong><?= $c['staff_name'] ? htmlspecialchars($c['staff_name']) : '—' ?></strong>
        </div>
    <?php endif; ?>

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
        <?php if ($convType === 'support'): ?>
        <div class="chat-sub-header">
            <span id="convStatusTag" class="status-tag <?= $convStatus === 'closed' ? '' : 'hidden' ?>">
                bisedë e mbyllur
            </span>
            <?php if ($convStatus === 'open'): ?>
                <button id="closeConvBtn" class="close-conv-btn" onclick="closeConversation()">🔒 Mbyll bisedën</button>
            <?php endif; ?>
        </div>
        <?php endif; ?>
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


<form class="chat-input" onsubmit="return false;">
    <textarea id="chatTextarea" placeholder="Write…" required></textarea>
    <button type="button" onclick="sendMessage()">➤</button>
</form>

<?php else: ?>
<div class="chat-empty">Zgjidh nje chat 👈</div>
<?php endif; ?>
</div>

</div>
</div>

<script>
const notifSound = document.getElementById('notifSound');
let lastTotalUnread = null;

function refreshUnread() {
    fetch('check_unread.php')
        .then(r => r.json())
        .then(data => {
            if (lastTotalUnread !== null && data.total > lastTotalUnread && notifSound) {
                notifSound.play().catch(() => {});
            }
            lastTotalUnread = data.total;

            document.querySelectorAll('.chat-item .badge').forEach(b => b.remove());

            data.conversations.forEach(c => {
                if (c.conversation_id == <?= (int)($conversation_id ?? 0) ?>) return;

                const link = document.querySelector(`a.chat-item[href="?c=${c.conversation_id}"]`);
                if (link) {
                    const nameDiv = link.querySelector('.chat-name');
                    if (nameDiv) {
                        const badge = document.createElement('span');
                        badge.className = 'badge';
                        badge.textContent = c.unread;
                        nameDiv.appendChild(badge);
                    }
                }
            });
        });
}

refreshUnread();
setInterval(refreshUnread, 4000);


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
            if (m.error) return;

            updateConvStatusUI(m.status);

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
            .then(data => {
                updateConvStatusUI(data.status);

                data.messages.forEach(m => {
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

                if (data.messages.length) scrollBottom();
            });
    }, 2000);

}

function updateConvStatusUI(status) {
    const tag = document.getElementById('convStatusTag');
    const btn = document.getElementById('closeConvBtn');
    if (!tag) return;

    tag.classList.toggle('hidden', status !== 'closed');
    if (btn) btn.classList.toggle('hidden', status === 'closed');
}

function closeConversation() {
    fetch('close_conversation.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: `conversation_id=<?= (int)($conversation_id ?? 0) ?>`
    })
    .then(r => r.json())
    .then(data => {
        if (data.ok) updateConvStatusUI('closed');
    });
}
</script>




<?php require "./includes/footer.php"; ?>
