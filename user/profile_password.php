<?php
require_once '../db.php';
require_once '../auth.php';
require_login();

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $old = $_POST['old_password'];
    $new = $_POST['new_password'];

    $stmt = $mysqli->prepare("SELECT password_hash FROM users WHERE id=?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if (!password_verify($old, $user['password_hash'])) {
        $error = "Old password is incorrect!";
    } else {
        $hash = password_hash($new, PASSWORD_DEFAULT);

        $update = $mysqli->prepare("
            UPDATE users SET password_hash=?, updated_at=NOW() WHERE id=?
        ");
        $update->bind_param("si", $hash, $user_id);
        $update->execute();

        header("Location: profile.php?password_changed=1");
        exit;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Change Password</title>
<link rel="stylesheet" href="/UMS_Full_PHP_System/assets/css/bootstrap.min.css">
<link rel="stylesheet" href="/UMS_Full_PHP_System/assets/css/profile.css">
</head>

<body>

<div class="password-box">

<h3 class="fw-bold mb-4">Change Password</h3>

<?php if (!empty($error)): ?>
<div class="alert alert-danger"><?= $error ?></div>
<?php endif; ?>

<form method="POST">

    <label>Old Password</label>
    <input type="password" name="old_password" class="form-control mb-3" required>

    <label>New Password</label>
    <input type="password" name="new_password" class="form-control mb-3" required>

    <button class="btn btn-warning btn-custom">Update Password</button>
    <a href="profile.php" class="btn btn-secondary">Cancel</a>

</form>

</div>

</body>
</html>
