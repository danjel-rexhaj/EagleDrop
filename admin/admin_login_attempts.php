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
        la.user_id, 
        la.attempts, 
        la.last_attempt, 
        u.username,
        u.email
    FROM login_attempts la
    LEFT JOIN users u ON la.user_id = u.id
    ORDER BY la.last_attempt DESC
");
?>

<?php include "../includes/admin_header.php"; ?>

<div class="container mt-4">
    <h2>🔐 Login Attempts</h2>

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

    <table class="table table-bordered mt-3">
        <thead class="table-dark">
            <tr>
                <th>User ID</th>
                <th>User</th>
                <th>Email</th>
                <th>Attempts</th>
                <th>Last Attempt</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>

        <?php while ($a = $stmt->fetch(PDO::FETCH_ASSOC)): ?>

            <?php

            $blockTime = 1800; // 30 minuta
            $lastAttempt = strtotime($a['last_attempt']);
            $elapsed = time() - $lastAttempt;
            $remaining = $blockTime - $elapsed;

            if ($a['attempts'] >= 7 && $remaining > 0) {
                $minutesLeft = ceil($remaining / 60);
                $statusText = "🔒 Bllokuar ($minutesLeft min)";
                $statusClass = "bg-danger";
                $isBlocked = true;
            } else {
                $statusText = "✅ Aktiv";
                $statusClass = "bg-success";
                $isBlocked = false;
            }
            ?>

            <tr>
                <td><?= (int)$a['user_id'] ?></td>
                <td><?= htmlspecialchars($a['username'] ?? 'Unknown') ?></td>
                <td><?= htmlspecialchars($a['email'] ?? 'Unknown') ?></td>
                <td><?= (int)$a['attempts'] ?></td>
                <td><?= htmlspecialchars($a['last_attempt']) ?></td>

                <td>
                    <span class="badge <?= $statusClass ?>">
                        <?= $statusText ?>
                    </span>
                </td>

                <td>
                    <?php if ($isBlocked): ?>
                        <button class="btn btn-sm btn-danger" disabled>
                            I bllokuar
                        </button>
                    <?php else: ?>
                        <button class="btn btn-sm btn-success" disabled>
                            Aktiv
                        </button>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endwhile; ?>

        
        </tbody>
    </table>
</div>

<?php include "../includes/footer.php"; ?>
