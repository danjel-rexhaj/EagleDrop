<link rel="stylesheet" href="/assets/css/chat.css?v=<?= filemtime(__DIR__ . '/../assets/css/chat.css') ?>">
<audio id="widgetNotifSound" src="/assets/sounds/notify.mp3" preload="auto"></audio>

<button id="widgetBubble" class="chat-widget-bubble" aria-label="Support">
    💬
    <span id="widgetBubbleBadge" class="chat-widget-badge hidden">0</span>
</button>

<div id="widgetPanel" class="chat-widget-panel hidden">
    <div class="chat-widget-header">
        <span id="widgetHeaderTitle">💬 Support</span>
        <button id="widgetCloseBtn" class="chat-widget-close" aria-label="Mbyll panelin">✕</button>
    </div>

    <div id="widgetStatusBanner" class="chat-widget-status-banner hidden">
        Kjo bisedë u mbyll nga stafi. Shkruaj një mesazh për ta rihapur.
    </div>

    <div id="widgetMessages" class="chat-widget-messages"></div>

    <form id="widgetForm" class="chat-input" onsubmit="return false;">
        <textarea id="widgetTextarea" placeholder="Shkruaj një mesazh…"></textarea>
        <button type="button" onclick="widgetSendMessage()">➤</button>
    </form>
</div>

<script>
(function () {
    const bubble = document.getElementById('widgetBubble');
    const badge = document.getElementById('widgetBubbleBadge');
    const panel = document.getElementById('widgetPanel');
    const closeBtn = document.getElementById('widgetCloseBtn');
    const messagesBox = document.getElementById('widgetMessages');
    const textarea = document.getElementById('widgetTextarea');
    const statusBanner = document.getElementById('widgetStatusBanner');
    const notifSound = document.getElementById('widgetNotifSound');
    const headerTitle = document.getElementById('widgetHeaderTitle');

    let conversationId = null;
    let me = null;
    let lastMessageId = 0;
    let lastTotalUnread = null;
    let pollTimer = null;

    function displayName(username, role) {
        return (role === 'staff' || role === 'admin') ? `${username} · Support` : username;
    }

    function renderMessage(m) {
        const div = document.createElement('div');
        div.className = 'message ' + (m.sender_id == me ? 'me' : 'other');
        div.dataset.id = m.id;
        div.innerHTML = `
            <div class="meta"><span class="name">${displayName(m.username, m.role)}</span></div>
            <div class="bubble">
                ${m.message.replace(/\n/g, '<br>')}
                <div class="msg-time">${(m.created_at || '').substr(11, 5)}</div>
            </div>
        `;
        messagesBox.appendChild(div);
        lastMessageId = Math.max(lastMessageId, m.id);
    }

    function scrollBottom() {
        messagesBox.scrollTop = messagesBox.scrollHeight;
    }

    function setStatus(status) {
        statusBanner.classList.toggle('hidden', status !== 'closed');
    }

    function openPanel() {
        panel.classList.remove('hidden');
        bubble.classList.add('hidden');

        if (conversationId === null) {
            messagesBox.innerHTML = '<div class="chat-widget-error">Duke ngarkuar…</div>';

            fetch('/widget_init.php')
                .then(r => r.text().then(text => ({ status: r.status, ok: r.ok, text })))
                .then(({ status, ok, text }) => {
                    let data;
                    try {
                        data = JSON.parse(text);
                    } catch (e) {
                        console.error(`widget_init.php returned non-JSON (HTTP ${status}):`, text.slice(0, 1000));
                        messagesBox.innerHTML = `<div class="chat-widget-error">Gabim serveri (HTTP ${status}) gjatë ngarkimit. Ekrani i konsolës (F12) ka detajet.</div>`;
                        return;
                    }
                    if (!ok || data.error) {
                        console.error('widget_init.php failed:', data.error);
                        messagesBox.innerHTML = `<div class="chat-widget-error">${data.error || 'Gabim gjatë ngarkimit të bisedës.'}</div>`;
                        return;
                    }
                    conversationId = data.conversation_id;
                    me = data.me;
                    if (data.staff_name) {
                        headerTitle.textContent = `💬 ${data.staff_name} · Support`;
                    }
                    messagesBox.innerHTML = '';
                    data.messages.forEach(renderMessage);
                    setStatus(data.status);
                    scrollBottom();
                    startPolling();
                })
                .catch(err => {
                    console.error('widget_init.php request failed:', err);
                    messagesBox.innerHTML = '<div class="chat-widget-error">S\'u lidhëm dot me serverin. Kontrollo internetin dhe provo përsëri.</div>';
                });
        } else {
            startPolling();
        }
    }

    function closePanel() {
        panel.classList.add('hidden');
        bubble.classList.remove('hidden');
        stopPolling();
    }

    function startPolling() {
        stopPolling();
        pollTimer = setInterval(() => {
            if (conversationId === null) return;
            fetch(`/fetch_messages.php?conversation_id=${conversationId}&last_id=${lastMessageId}`)
                .then(r => r.json())
                .then(data => {
                    if (data.error) return;
                    setStatus(data.status);
                    if (data.messages.length) {
                        data.messages.forEach(renderMessage);
                        scrollBottom();
                    }
                });
        }, 2500);
    }

    function stopPolling() {
        if (pollTimer) clearInterval(pollTimer);
        pollTimer = null;
    }

    window.widgetSendMessage = function () {
        const msg = textarea.value.trim();
        if (!msg) return;

        if (conversationId === null) {
            messagesBox.innerHTML = '<div class="chat-widget-error">Biseda s\'është ngarkuar ende. Mbyll dhe rihap panelin dhe provo sërish.</div>';
            return;
        }

        textarea.value = '';
        textarea.focus();

        fetch('/send_message.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `conversation_id=${conversationId}&message=${encodeURIComponent(msg)}`
        })
        .then(r => r.json().then(data => ({ ok: r.ok, data })))
        .then(({ ok, data: m }) => {
            if (!ok || m.error) {
                console.error('send_message.php failed:', m && m.error);
                textarea.value = msg;
                return;
            }
            setStatus(m.status);
            renderMessage(m);
            scrollBottom();
        })
        .catch(err => {
            console.error('send_message.php request failed:', err);
            textarea.value = msg;
        });
    };

    bubble.addEventListener('click', openPanel);
    closeBtn.addEventListener('click', closePanel);

    textarea.addEventListener('keydown', e => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            widgetSendMessage();
        }
    });

    // Badge + bell: checked on every page regardless of whether the panel is open.
    function refreshBadge() {
        fetch('/check_unread.php')
            .then(r => r.json())
            .then(data => {
                if (lastTotalUnread !== null && data.total > lastTotalUnread) {
                    notifSound.play().catch(() => {});
                }
                lastTotalUnread = data.total;

                if (data.total > 0) {
                    badge.textContent = data.total;
                    badge.classList.remove('hidden');
                } else {
                    badge.classList.add('hidden');
                }
            });
    }

    refreshBadge();
    setInterval(refreshBadge, 5000);

    window.openSupportWidget = openPanel;
})();
</script>
