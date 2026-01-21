<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: /myplatform/access_denied.php");
    exit;
}

require "../includes/auth.php";
require "../config/database.php";




$stmt = $conn->query("
    SELECT 
        l.id,
        l.type,
        l.message,
        l.created_at,
        l.user_id,
        l.email,
        l.ip_address,
        l.user_agent,
        COALESCE(la.attempts, 0) AS attempts
    FROM logs l
    LEFT JOIN login_attempts la ON la.user_id = l.user_id
    ORDER BY l.created_at DESC
    LIMIT 500
");
?>

<?php include "../includes/admin_header.php"; ?>

<div class="container mt-4">
    <h2>📄 Logimet ne Sistem</h2>
    <div class="container mt-3">
    <button class="back-btn" onclick="goBack()">
        <span class="arrow">←</span>
        <span>Kthehu</span>
    </button>
</div>

<script>
function goBack() {
    if (document.referrer) {
        window.history.back();
    } else {
        window.location.href = 'index.php';
    }
}
</script>
    <div class="d-flex justify-content-end mb-3">
    <a href="admin_login_attempts.php" class="btn btn-outline-warning">
        🔐 Shiko Login Attempts →
    </a>
</div>

    <div class="table-responsive">
        <table class="table table-bordered table-hover mt-3">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Type</th>
                    <th>User ID</th>
                    <th>Email</th>
                    <th>IP</th>
                    <th>Message</th>
                    <th>Date</th>
                </tr>
            </thead>

            <tbody>
<?php
$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));
$currentGroup = null;

while ($l = $stmt->fetch(PDO::FETCH_ASSOC)):

    $logDate = date('Y-m-d', strtotime($l['created_at']));
    $logTime = date('H:i', strtotime($l['created_at']));

    if ($logDate === $today) {
        $groupTitle = "📅 Sot";
        $dateDisplay = $logTime;
    } elseif ($logDate === $yesterday) {
        $groupTitle = "📅 Dje";
        $dateDisplay = $logTime;
    } else {
        $groupTitle = "Date: " . date('d.m.Y', strtotime($l['created_at']));
        $dateDisplay = date('d.m.Y H:i', strtotime($l['created_at']));
    }

    // nese ndryshon grupi → shfaq header
    if ($groupTitle !== $currentGroup):
        $currentGroup = $groupTitle;
?>
    <tr class="table-secondary">
        <td colspan="7" class="fw-bold fs-5 py-3">
            <?= $groupTitle ?>
        </td>
    </tr>
<?php endif; ?>

    <tr>
        <td><?= (int)$l['id'] ?></td>

        <td>
            <span class="badge bg-<?= 
                $l['type'] === 'login' ? 'success' :
                ($l['type'] === 'failed_login' ? 'danger' :
                ($l['type'] === 'blocked_login' ? 'warning' : 'secondary'))
            ?>">
                <?= htmlspecialchars($l['type']) ?>
            </span>
        </td>

        <td><?= $l['user_id'] ?? '-' ?></td>
        <td><?= htmlspecialchars($l['email'] ?? '-') ?></td>
        <td><?= htmlspecialchars($l['ip_address'] ?? '-') ?></td>
        <td><?= htmlspecialchars($l['message']) ?></td>
        <td>
            <strong><?= $dateDisplay ?></strong>
        </td>
    </tr>

<?php endwhile; ?>
</tbody>

        </table>
    </div>
</div>

<?php include "../includes/footer.php"; ?>
