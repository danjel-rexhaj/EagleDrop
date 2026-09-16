<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: /access_denied.php");
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
$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

function paymentBadgeClass($status) {
    $status = strtolower((string) $status);
    if (in_array($status, ['success', 'completed', 'paid'], true)) return 'badge-success';
    if (in_array($status, ['failed', 'declined', 'canceled', 'cancelled'], true)) return 'badge-danger';
    return 'badge-warning';
}
?>

<?php include "../includes/admin_header.php"; ?>
<link rel="stylesheet" href="/assets/css/admin.css">

<div class="container mt-3">
    <button class="back-btn" onclick="goBack()">
        <span class="arrow">←</span>
        <span>Kthehu</span>
    </button>
</div>

<script>
function goBack() {
    if (window.history.length > 1) {
        window.history.back();
    } else {
        window.location.href = 'index.php';
    }
}
</script>

<div class="container mt-4 mb-5">
    <h2 class="mb-1"><i class="bi bi-credit-card"></i> Monitorim i Pagesave</h2>
    <p class="text-muted mb-4"><?= count($payments) ?> pagesa gjithsej</p>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Përdoruesi</th>
                    <th>Shuma</th>
                    <th>Statusi</th>
                    <th>Provider</th>
                    <th>Transaksioni</th>
                    <th>Data</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($payments)): ?>
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">S'ka ende asnjë pagesë.</td>
                </tr>
            <?php endif; ?>
            <?php foreach ($payments as $p): ?>
                <tr>
                    <td>#<?= (int) $p['id'] ?></td>
                    <td><?= htmlspecialchars($p['user_name'] ?? 'Përdorues i fshirë') ?></td>
                    <td class="fw-semibold">€<?= number_format($p['amount'], 2) ?></td>
                    <td><span class="badge <?= paymentBadgeClass($p['status']) ?>"><?= htmlspecialchars($p['status'] ?? '-') ?></span></td>
                    <td class="text-capitalize"><?= htmlspecialchars($p['provider'] ?? '-') ?></td>
                    <td><code class="small"><?= htmlspecialchars($p['transaction_id'] ?? '-') ?></code></td>
                    <td class="text-nowrap"><?= $p['created_at'] ? date('d.m.Y H:i', strtotime($p['created_at'])) : '-' ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include "../includes/footer.php"; ?>
