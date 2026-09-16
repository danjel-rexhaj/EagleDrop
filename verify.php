<?php
session_start();
require "./config/database.php";
require "./config/mailer.php";

if (!isset($_SESSION['pending_email'])) {
    header("Location: login.php");
    exit();
}

$email = $_SESSION['pending_email'];
$message = "";
$info = "";

if (!empty($_SESSION['verify_send_failed'])) {
    $message = "Llogaria u krijua, por dergimi i email-it me kodin deshtoi. Kliko 'Ridergo kodin' per te provuar perseri.";
    unset($_SESSION['verify_send_failed']);
}

$resendCooldown = 45;

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $action = $_POST['action'] ?? 'verify'; 

    if ($action === 'resend') {

        $lastSent = $_SESSION['verify_last_sent'] ?? 0;
        $wait = $resendCooldown - (time() - $lastSent);

        if ($wait > 0) {
            $message = "Prit edhe {$wait} sekonda para se te kerkosh nje kod te ri.";
        } else {
            $newCode = rand(100000, 999999);
            $update = $conn->prepare("UPDATE users SET verification_code = ? WHERE email = ?");
            $update->execute([$newCode, $email]);

            if (sendVerificationEmail($email, $newCode)) {
                $_SESSION['verify_last_sent'] = time();
                $info = "Nje kod i ri u dergua ne " . htmlspecialchars($email) . ".";
            } else {
                $message = "Dergimi i email-it deshtoi. Provo perseri me vone ose kontakto suportin.";
            }
        }

    } elseif ($action === 'change_email') {

        $newEmail = trim($_POST['new_email'] ?? '');

        if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            $message = "Email-i i ri nuk eshte valid.";
        } else {
            $check = $conn->prepare("SELECT id FROM users WHERE email = ? AND email <> ?");
            $check->execute([$newEmail, $email]);

            if ($check->fetch()) {
                $message = "Ky email eshte i zene nga nje llogari tjeter.";
            } else {
                $newCode = rand(100000, 999999);
                $update = $conn->prepare("UPDATE users SET email = ?, verification_code = ? WHERE email = ?");
                $update->execute([$newEmail, $newCode, $email]);

                $email = $newEmail;
                $_SESSION['pending_email'] = $newEmail;

                if (sendVerificationEmail($newEmail, $newCode)) {
                    $_SESSION['verify_last_sent'] = time();
                    $info = "Email-i u ndryshua. Kodi i ri u dergua ne " . htmlspecialchars($newEmail) . ".";
                } else {
                    $message = "Email-i u ndryshua, por dergimi i kodit deshtoi. Provo 'Ridergo kodin'.";
                }
            }
        }

    } else {
        $code = $_POST["code"] ?? '';

        $query = $conn->prepare("SELECT verification_code FROM users WHERE email = ?");
        $query->execute([$email]);
        $row = $query->fetch(PDO::FETCH_ASSOC);

        if ($row && $row['verification_code'] == $code) {
            $update = $conn->prepare("UPDATE users SET is_verified = 1 WHERE email = ?");
            $update->execute([$email]);

            unset($_SESSION['pending_email'], $_SESSION['verify_last_sent']);
            header("Location: login.php?verified=1");
            exit();
        } else {
            $message = "Kodi eshte i pasakte!";
        }
    }
}
?>

<?php include "./includes/header.php"; ?>

<style>
.verify-card {
    max-width: 420px;
    margin: auto;
    border-radius: 16px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.15);
    animation: fadeInUp 0.6s ease;
}

.verify-icon {
    font-size: 48px;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.code-input {
    text-align: center;
    font-size: 24px;
    letter-spacing: 6px;
}

.verify-actions {
    display: flex;
    justify-content: center;
    gap: 18px;
    flex-wrap: wrap;
}

.verify-actions button {
    background: none;
    border: none;
    padding: 0;
    font-size: 0.85rem;
    color: #0d6efd;
    text-decoration: underline;
    cursor: pointer;
}

.verify-actions button:disabled {
    color: #adb5bd;
    cursor: not-allowed;
    text-decoration: none;
}

.change-email-box {
    text-align: left;
}
</style>

<div class="container min-vh-100 d-flex align-items-center justify-content-center">
    <div class="card verify-card p-4 text-center">

        <div class="verify-icon mb-3">📧</div>

        <h4 class="mb-2">Verifikimi i Email-it</h4>
        <p class="text-muted small mb-4">
            Kemi derguar nje kod 6-shifror ne<br>
            <b id="pendingEmailLabel"><?= htmlspecialchars($email) ?></b>
        </p>

        <?php if ($info): ?>
            <div class="alert alert-success py-2"><?= $info ?></div>
        <?php endif; ?>

        <?php if ($message): ?>
            <div class="alert alert-danger py-2">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="action" value="verify">
            <input
                id="codeInput"
                type="text"
                name="code"
                maxlength="6"
                class="form-control code-input mb-3"
                placeholder="••••••"
                required
            >

            <button class="btn btn-primary w-100 py-2">
                ✅ Verifiko Email-in
            </button>
        </form>

        <div class="mt-3 mb-2 small text-muted">
            Nuk erdhi kodi? Kontrollo Spam 📬
        </div>

        <div class="verify-actions">
            <form method="POST" id="resendForm">
                <input type="hidden" name="action" value="resend">
                <button type="submit" id="resendBtn">🔁 Ridergo kodin</button>
            </form>

            <button type="button" onclick="toggleChangeEmail()">✏️ Ndrysho email-in</button>
        </div>

        <div class="change-email-box mt-3" id="changeEmailBox" hidden>
            <form method="POST" class="d-flex gap-2">
                <input type="hidden" name="action" value="change_email">
                <input
                    type="email"
                    name="new_email"
                    class="form-control form-control-sm"
                    placeholder="Email i ri"
                    required
                >
                <button class="btn btn-outline-primary btn-sm text-nowrap">Ruaj</button>
            </form>
        </div>

    </div>
</div>

<script>
document.getElementById('codeInput').addEventListener('paste', function (e) {
    e.preventDefault();

    let paste = (e.clipboardData || window.clipboardData)
        .getData('text')
        .replace(/\D/g, '')
        .slice(0, 6);

    this.value = paste;
});

function toggleChangeEmail() {
    const box = document.getElementById('changeEmailBox');
    box.hidden = !box.hidden;
}

<?php if ($info): ?>
startResendCooldown(<?= $resendCooldown ?>);
<?php endif; ?>

function startResendCooldown(seconds) {
    const btn = document.getElementById('resendBtn');
    if (!btn) return;

    let remaining = seconds;
    btn.disabled = true;

    const original = '🔁 Ridergo kodin';
    const tick = () => {
        if (remaining <= 0) {
            btn.disabled = false;
            btn.textContent = original;
            return;
        }
        btn.textContent = `🔁 Ridergo kodin (${remaining}s)`;
        remaining--;
        setTimeout(tick, 1000);
    };
    tick();
}
</script>

<?php include "./includes/footer.php"; ?>
