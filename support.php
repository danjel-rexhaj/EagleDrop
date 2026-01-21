<?php
require "./includes/auth.php";
require "./config/database.php";

$me = $_SESSION['user_id'];
$role = $_SESSION['role'];

if ($role !== 'user') {
    header("Location: support_admin.php");
    exit;
}


$stmt = $conn->prepare("
    SELECT id 
    FROM users 
    WHERE role IN ('staff','admin')
    ORDER BY id ASC
    LIMIT 1
");
$stmt->execute();
$staff = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$staff) {
    die('No staff available');
}

$defaultStaffId = $staff['id'];


$stmt = $conn->prepare("
    SELECT id 
    FROM conversations 
    WHERE client_id=? AND type='support'
");
$stmt->execute([$me]);
$conv = $stmt->fetch();

if (!$conv) {
    $conn->prepare("
        INSERT INTO conversations (client_id, staff_id, type)
        VALUES (?, ?, 'support')
    ")->execute([$me, $defaultStaffId]);

    $conversation_id = $conn->lastInsertId();
} else {
    $conversation_id = $conv['id'];
}



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
")->execute([
    $me,
    $conversation_id
]);



require "./includes/header.php";
?>

<link rel="stylesheet" href="./assets/css/chat.css">

<div class="chat-container role-user">
    <div class="chat-header">💬 Support</div>

    <div class="chat-messages" id="chatMessages">
        <?php foreach($messages as $m): ?>
            <div class="message <?= $m['sender_id']==$me?'me':'other' ?>" data-id="<?= $m['id'] ?>">
                <div class="meta">
                    <span class="name"><?= htmlspecialchars($m['username']) ?></span>
                </div>
                <div class="bubble">
                    <?= nl2br(htmlspecialchars($m['message'])) ?>
                    <div class="msg-time"><?= date('H:i', strtotime($m['created_at'])) ?></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <form class="chat-input" onsubmit="return false;">
        <textarea id="chatTextarea" placeholder="Write to support…" required></textarea>
        <button type="button" onclick="sendMessage()">➤</button>
    </form>
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
    fetch('check_unread_clients.php')
        .then(r => r.json())
        .then(data => {

            document.querySelectorAll('.chat-item .badge').forEach(b => b.remove());

            data.forEach(c => {
                if (c.unread > 0) {
                    const chatLink = document.querySelector(`a[href="?c=${c.conversation_id}"]`);
                    if (chatLink) {
                        const nameDiv = chatLink.querySelector('.chat-name');
                        if (nameDiv) {
                            const badge = document.createElement('span');
                            badge.className = 'badge bg-danger ms-1';
                            badge.textContent = c.unread;
                            nameDiv.appendChild(badge);
                        }
                    }
                }
            });
        });
}, 3000);

}
</script>


<?php require "./includes/footer.php"; ?>
