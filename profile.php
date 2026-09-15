<?php
require "./includes/auth.php";
require "./config/database.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = "";


ini_set('display_errors', 1);
error_reporting(E_ALL);


if (isset($_POST['update_info'])) {
    $first    = trim($_POST['first_name']);
    $last     = trim($_POST['last_name']);
    $username = trim($_POST['username']);
    $phone    = trim($_POST['phone']);

    $update = $conn->prepare(
        "UPDATE users 
         SET first_name=?, last_name=?, username=?, phone=?
         WHERE id=?"
    );
    $update->execute([$first, $last, $username, $phone, $user_id]);

    $message = "Te dhenat u perditesuan me sukses!";
}


if (isset($_POST['upload_photo'])) {
    if (!empty($_FILES['photo']['name'])) {

        $fileName   = time() . "_" . basename($_FILES["photo"]["name"]);
        $targetPath = __DIR__ . "/assets/uploads/" . $fileName;

        $allowed = ["jpg", "jpeg", "png"];
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if (in_array($fileExt, $allowed)) {
            if (move_uploaded_file($_FILES["photo"]["tmp_name"], $targetPath)) {

                $update = $conn->prepare(
                    "UPDATE users SET profile_image=? WHERE id=?"
                );
                $update->execute([$fileName, $user_id]);

                $message = "Fotoja u ngarkua me sukses!";
            } else {
                $message = "Gabim gjate ngarkimit!";
            }
        } else {
            $message = "Lejohen vetem jpg, jpeg, png!";
        }
    }
}


if (isset($_POST['change_password'])) {

    $current = $_POST['current_password'];
    $new     = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];

    if ($new !== $confirm) {
        $message = "Password-et nuk perputhen!";
    } else {
        $stmt = $conn->prepare("SELECT password FROM users WHERE id=?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!password_verify($current, $user['password'])) {
            $message = "Password-i aktual eshte i gabuar!";
        } else {
            $hashed = password_hash($new, PASSWORD_DEFAULT);

            $update = $conn->prepare(
                "UPDATE users SET password=? WHERE id=?"
            );
            $update->execute([$hashed, $user_id]);

            $message = "Password u ndryshua me sukses!";
        }
    }
}


if (isset($_POST['change_email'])) {

    $new_email = trim($_POST['new_email']);
    $password  = $_POST['email_password'];


    $check = $conn->prepare(
        "SELECT id FROM users WHERE email=? AND id!=?"
    );
    $check->execute([$new_email, $user_id]);

    if ($check->rowCount() > 0) {
        $message = "Ky email ekziston!";
    } else {
        $stmt = $conn->prepare("SELECT password FROM users WHERE id=?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!password_verify($password, $user['password'])) {
            $message = "Password i gabuar!";
        } else {
            $update = $conn->prepare(
                "UPDATE users SET email=? WHERE id=?"
            );
            $update->execute([$new_email, $user_id]);

            $message = "Email u ndryshua me sukses!";
        }
    }
}


$stmt = $conn->prepare("SELECT * FROM users WHERE id=?");
$stmt->execute([$user_id]);
$currentUser = $stmt->fetch(PDO::FETCH_ASSOC);

include "./includes/header.php";
?>

<div class="container mt-5">
    <div class="profile-card mx-auto p-4">

        <h2 class="text-center mb-4">Profili im</h2>

        <?php if (!empty($message)): ?>
            <div class="alert alert-info text-center">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <?php
        $photoFile = $currentUser['profile_image'] ?? null;
        if ($photoFile === '' || $photoFile === 'default.png' || $photoFile === 'default_user.png') {
            $photoFile = null;
        }
        $photo = $photoFile ? "/assets/uploads/" . $photoFile : null;
        ?>

        <div class="profile-photo-header">
            <?php if ($photo): ?>
                <img src="<?= htmlspecialchars($photo) ?>" class="profile-photo mb-3">
            <?php else: ?>
                <div class="profile-photo profile-photo-placeholder mb-3">
                    <i class="bi bi-person-fill"></i>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="profile-photo-form" id="photoForm">
                <input type="hidden" name="upload_photo" value="1">
                <label class="btn btn-primary profile-upload-btn">
                    📷 Ngarko Foto
                    <input type="file" name="photo" accept=".jpg,.jpeg,.png" id="photoInput" hidden>
                </label>
            </form>
        </div>

        <div class="profile-accordion mx-auto">

            <div class="profile-accordion-item">
                <button type="button" class="profile-accordion-header" onclick="toggleProfileSection(this)">
                    <span>📝 Te dhenat personale</span>
                    <span class="profile-accordion-arrow">▾</span>
                </button>
                <div class="profile-accordion-body" hidden>
                    <form method="POST">
                        <label class="profile-label">Username</label>
                        <input name="username" class="form-control mb-3"
                               value="<?= htmlspecialchars($currentUser['username']) ?>" required>

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="profile-label">Emri</label>
                                <input name="first_name" class="form-control"
                                       value="<?= htmlspecialchars($currentUser['first_name']) ?>" required>
                            </div>
                            <div class="col-6">
                                <label class="profile-label">Mbiemri</label>
                                <input name="last_name" class="form-control"
                                       value="<?= htmlspecialchars($currentUser['last_name']) ?>" required>
                            </div>
                        </div>

                        <label class="profile-label">Telefoni</label>
                        <input name="phone" class="form-control mb-3"
                               value="<?= htmlspecialchars($currentUser['phone']) ?>">

                        <label class="profile-label">Email <span class="profile-label-hint">(ndrysho me poshte)</span></label>
                        <input type="email" class="form-control mb-3"
                               value="<?= htmlspecialchars($currentUser['email']) ?>"
                               readonly>

                        <button name="update_info" class="btn btn-success w-100">
                            Perditeso Profilin
                        </button>
                    </form>
                </div>
            </div>

            <div class="profile-accordion-item">
                <button type="button" class="profile-accordion-header" onclick="toggleProfileSection(this)">
                    <span>🔒 Ndrysho Password-in</span>
                    <span class="profile-accordion-arrow">▾</span>
                </button>
                <div class="profile-accordion-body" hidden>
                    <form method="POST">
                        <label class="profile-label">Password aktual</label>
                        <input type="password" name="current_password" class="form-control mb-3"
                               placeholder="••••••••" required>

                        <label class="profile-label">Password i ri</label>
                        <input type="password" name="new_password" class="form-control mb-3"
                               placeholder="••••••••" required>

                        <label class="profile-label">Konfirmo password-in</label>
                        <input type="password" name="confirm_password" class="form-control mb-3"
                               placeholder="••••••••" required>

                        <button name="change_password" class="btn btn-warning w-100">
                            Ndrysho Password
                        </button>
                    </form>
                </div>
            </div>

            <div class="profile-accordion-item">
                <button type="button" class="profile-accordion-header" onclick="toggleProfileSection(this)">
                    <span>📧 Ndrysho Email-in</span>
                    <span class="profile-accordion-arrow">▾</span>
                </button>
                <div class="profile-accordion-body" hidden>
                    <form method="POST">
                        <label class="profile-label">Email i ri</label>
                        <input type="email" name="new_email" class="form-control mb-3"
                               placeholder="emri@shembull.com" required>

                        <label class="profile-label">Password aktual</label>
                        <input type="password" name="email_password" class="form-control mb-3"
                               placeholder="••••••••" required>

                        <button name="change_email" class="btn btn-info w-100">
                            Ndrysho Email
                        </button>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
function toggleProfileSection(headerBtn) {
    const item = headerBtn.closest('.profile-accordion-item');
    const body = item.querySelector('.profile-accordion-body');
    const isOpen = !body.hidden;

    document.querySelectorAll('.profile-accordion-item').forEach(other => {
        other.querySelector('.profile-accordion-body').hidden = true;
        other.classList.remove('open');
    });

    if (!isOpen) {
        body.hidden = false;
        item.classList.add('open');
    }
}

document.getElementById('photoInput').addEventListener('change', function () {
    if (this.files.length) {
        document.getElementById('photoForm').submit();
    }
});
</script>

<?php include "./includes/footer.php"; ?>
