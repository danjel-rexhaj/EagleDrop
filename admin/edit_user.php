<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: /myplatform/access_denied.php");
    exit;
}


require "../includes/auth.php";
require "../config/database.php";


if (!isset($_GET['id'])) {
    header("Location: users.php");
    exit();
}

$id = $_GET['id'];


$stmt = $conn->prepare("SELECT * FROM users WHERE id=?");
$stmt->execute([$id]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);

$message = "";
if (isset($_GET['success'])) {
    $message = "Perdoruesi u perditesua me sukses!";
}


if (isset($_POST['update_user'])) {

    $first_name = $_POST['first_name'];
    $last_name  = $_POST['last_name'];
    $phone      = $_POST['phone'];
    $email      = $_POST['email'];
    $role       = $_POST['role'];
    $verified   = isset($_POST['is_verified']) ? 1 : 0;

    $update = $conn->prepare(
        "UPDATE users 
         SET first_name=?, last_name=?, phone=?, email=?, role=?, is_verified=? 
         WHERE id=?"
    );
    $update->execute([
        $first_name,
        $last_name,
        $phone,
        $email,
        $role,
        $verified,
        $id
    ]);

    header("Location: edit_user.php?id=$id&success=1");
    exit;
}

?>



<?php include "../includes/admin_header.php"; ?>
<link rel="stylesheet" href="assets/css/admin.css">

<div class="container mt-5">
    <div class="container mt-3">
    <button class="back-btn" onclick="goBack()">
        <span class="arrow">←</span>
        <span>Kthehu</span>
    </button>
</div>

<script>
function goBack() {
    if (document.referrer) {
        window.location.href = 'users.php';
    }
}
</script>


    <div class="row justify-content-center">
        <div class="col-md-7">
        
            <div class="admin-card">
                <h2 class="mb-4">✏️ Editimi i Perdoruesit</h2>
                
                <?php if ($message): ?>
                    <div class="alert alert-success" id="successMsg">
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>

                <form method="POST">

                    <div class="mb-3">
                        <label class="form-label">Emri</label>
                        <input type="text" name="first_name" class="form-control"
                               value="<?= $user['first_name'] ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Mbiemri</label>
                        <input type="text" name="last_name" class="form-control"
                               value="<?= $user['last_name'] ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Phone number</label>
                        <input type="text" name="phone" class="form-control"
                               value="<?= $user['phone'] ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control"
                               value="<?= $user['email'] ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Roli</label>
                        <select name="role" class="form-select">
                            <option value="user" <?= $user['role']=='user'?'selected':'' ?>>User</option>
                            <option value="admin" <?= $user['role']=='admin'?'selected':'' ?>>Admin</option>
                            <option value="staff" <?= $user['role']=='staff'?'selected':'' ?>>Staff</option>
                        </select>
                    </div>

                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox"
                               name="is_verified" id="verified"
                               <?= $user['is_verified'] ? 'checked' : '' ?>>
                        <label class="form-check-label" for="verified">
                            Email i verifikuar
                        </label>
                    </div>

                    <button type="submit" name="update_user" class="btn btn-primary w-100">
                        Ruaj ndryshimet
                    </button>

                </form>
            </div>

        </div>
    </div>
</div>


<script>
  setTimeout(() => {
    const msg = document.getElementById('successMsg');
    if (msg) {
      msg.style.transition = 'opacity 0.5s ease';
      msg.style.opacity = '0';
      setTimeout(() => msg.remove(), 500);
    }
  }, 2000); 
</script>


<?php include "../includes/footer.php"; ?>
