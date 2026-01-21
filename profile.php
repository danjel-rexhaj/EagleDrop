<?php
require "./includes/auth.php";
require "./config/database.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user_id'])) {
    header("Location: /myplatform/login.php");
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

        <div class="row justify-content-center">


            <div class="col-md-4 text-center">
                <?php
                $photoFile = !empty($currentUser['profile_image'])
                    ? $currentUser['profile_image']
                    : 'default_user.png';

                $photo = "/myplatform/assets/uploads/" . $photoFile;
                ?>
                <img src="<?= htmlspecialchars($photo) ?>" class="profile-photo mb-3">

                <form method="POST" enctype="multipart/form-data">
                    <input type="file" name="photo" class="form-control mb-2">
                    <button name="upload_photo" class="btn btn-primary w-100">
                        Ngarko Foto
                    </button>
                </form>
            </div>


            <div class="col-md-6">

                <form method="POST">
                    <input name="username" class="form-control mb-2"
                           value="<?= htmlspecialchars($currentUser['username']) ?>" required>

                    <input name="first_name" class="form-control mb-2"
                           value="<?= htmlspecialchars($currentUser['first_name']) ?>" required>

                    <input name="last_name" class="form-control mb-2"
                           value="<?= htmlspecialchars($currentUser['last_name']) ?>" required>

                    <input name="phone" class="form-control mb-3"
                           value="<?= htmlspecialchars($currentUser['phone']) ?>">
                           <input type="email" class="form-control mb-3"
                            value="<?= htmlspecialchars($currentUser['email']) ?>"
                            readonly>


                    <button name="update_info" class="btn btn-success w-100">
                        Perditeso Profilin
                    </button>
                </form>

                <hr>


                <form method="POST">
                    <input type="password" name="current_password" class="form-control mb-2"
                           placeholder="Password aktual" required>

                    <input type="password" name="new_password" class="form-control mb-2"
                           placeholder="Password i ri" required>

                    <input type="password" name="confirm_password" class="form-control mb-3"
                           placeholder="Konfirmo password" required>

                    <button name="change_password" class="btn btn-warning w-100">
                        Ndrysho Password
                    </button>
                </form>

                <hr>


                <form method="POST">
                    <input type="email" name="new_email" class="form-control mb-2"
                           placeholder="Email i ri" required>

                    <input type="password" name="email_password" class="form-control mb-3"
                           placeholder="Password aktual" required>

                    <button name="change_email" class="btn btn-info w-100">
                        Ndrysho Email
                    </button>
                </form>

            </div>
        </div>
    </div>
</div>

<?php include "./includes/footer.php"; ?>
