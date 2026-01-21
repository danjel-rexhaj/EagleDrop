<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: /myplatform/access_denied.php");
    exit;
}


require "../includes/auth.php";
require "../config/database.php";



$stmt = $conn->query("
    SELECT payments.*, users.username AS user_name
    FROM payments
    LEFT JOIN users ON payments.user_id = users.id
    ORDER BY payments.id DESC
");
?>

<?php include "../includes/admin_header.php"; ?>

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

<div class="container mt-4">
    <h2>Monitorim i Pagesave (Admin)</h2>

    <table class="table table-bordered mt-3">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Perdoruesi</th>
                <th>Shuma</th>
                <th>Statusi</th>
                <th>Provider</th>
                <th>Transaksioni</th>
                <th>Data</th>
            </tr>
        </thead>
        <tbody>
        <?php while($p = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
            <tr>
                <td><?= $p['id'] ?></td>
                <td><?= htmlspecialchars($p['user_name'] ?? 'User i fshire') ?></td>
                <td>€<?= number_format($p['amount'], 2) ?></td>
                <td><?= htmlspecialchars($p['status']) ?></td>
                <td><?= htmlspecialchars($p['provider']) ?></td>
                <td><?= htmlspecialchars($p['transaction_id']) ?></td>
                <td><?= $p['created_at'] ?></td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php include "../includes/footer.php"; ?>
