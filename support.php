<?php
require "./includes/auth.php";
require "./config/database.php";
require "./includes/support_conversation.php";

$me = $_SESSION['user_id'];
$role = $_SESSION['role'];

if ($role !== 'user') {
    header("Location: support_admin.php");
    exit;
}

$conversation_id = getOrCreateSupportConversation($conn, $me);



$stmt = $conn->prepare("
    SELECT
        m.id,
        m.message,
        m.sender_id,
        m.created_at,
        u.username,
        u.role
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

<link rel="stylesheet" href="./assets/css/chat.css?v=<?= filemtime(__DIR__ . '/assets/css/chat.css') ?>">

<div class="chat-container role-user">
    <div class="chat-header">💬 Support</div>

    <div class="chat-messages" id="chatMessages">
        <?php foreach($messages as $m): ?>
            <div class="message <?= $m['sender_id']==$me?'me':'other' ?>" data-id="<?= $m['id'] ?>">
                <div class="meta">
                    <span class="name"><?= htmlspecialchars(supportDisplayName($m['username'], $m['role'])) ?></span>
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

function displayName(username, role) {
    return (role === 'staff' || role === 'admin') ? `${username} · Support` : username;
}

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

            const div = document.createElement('div');
            div.className = 'message me';
            div.dataset.id = m.id;

            div.innerHTML = `
                <div class="meta">
                    <span class="name">${displayName(m.username, m.role)}</span>
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
                data.messages.forEach(m => {
                    const div = document.createElement('div');
                    div.className = 'message ' + (m.sender_id == <?= $me ?> ? 'me' : 'other');
                    div.dataset.id = m.id;

                    div.innerHTML = `
                        <div class="meta">
                            <span class="name">${displayName(m.username, m.role)}</span>
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
    }, 3000);

}
</script>


<?php require "./includes/footer.php"; ?>
