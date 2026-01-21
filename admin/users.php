<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: /myplatform/access_denied.php");
    exit;
}

require "../includes/auth.php";
require "../config/database.php";


if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];

    if ($id !== (int) $_SESSION['user_id']) {
        $delete = $conn->prepare("DELETE FROM users WHERE id = ?");
        $delete->execute([$id]);
    }

    header("Location: users.php");
    exit();
}

$users = $conn->query("SELECT * FROM users ORDER BY id DESC");
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
        window.location.href = 'dashboard.php';
    }
}
</script>

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>👥 Menaxhimi i Perdoruesve</h2>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Emri</th>
                    <th>Mbiemri</th>
                    <th>Telefon</th>
                    <th>Email</th>
                    <th>Roli</th>
                    <th>Status</th>
                    <th class="text-center">Veprime</th>
                </tr>
            </thead>

            <tbody>
            <?php while($u = $users->fetch(PDO::FETCH_ASSOC)): ?>
                <tr>
                    <td><?= $u['id'] ?></td>

                    <td>
                    <?= htmlspecialchars($u['first_name']) ?>
                    </td>

                    <td>
                    <?= htmlspecialchars($u['last_name']) ?>    
                    </td>
                    
                    <td><?= htmlspecialchars($u['phone']) ?></td>

                    <td><?= htmlspecialchars($u['email']) ?></td>

                    <td>
                        <span class="badge 
                            <?= $u['role'] === 'admin' ? 'bg-dark' : ($u['role'] === 'staff' ? 'bg-info' : 'bg-secondary') ?>">
                            <?= ucfirst($u['role']) ?>
                        </span>
                    </td>

                    <td>
                        <span class="badge <?= $u['is_verified'] ? 'bg-success' : 'bg-warning text-dark' ?>">
                            <?= $u['is_verified'] ? 'Verifikuar' : 'Jo verifikuar' ?>
                        </span>
                    </td>

                    <td class="text-center">
                        <a href="edit_user.php?id=<?= $u['id'] ?>"
                           class="btn btn-sm btn-outline-primary">
                           ✏️ Edito
                        </a>

                        <?php if ($u['id'] != $_SESSION['user_id']): ?>
                            <a href="users.php?delete=<?= $u['id'] ?>"
                               class="btn btn-sm btn-outline-danger"
                               onclick="return confirm('A je i sigurt qe do ta fshish kete perdorues?');">
                               🗑️ Fshi
                            </a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>

        </table>
    </div>
</div>

<?php include "../includes/footer.php"; ?>
