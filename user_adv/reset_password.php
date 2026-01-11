<?php
require_once "../includes/auth.php";
require_role('admin');
require_once "../db.php";

$id = (int)($_GET['id'] ?? 0);
$msg = "";

if ($id <= 0) {
    die("Invalid user ID");
}

// PREVENT SELF RESET BLOCK (OPTIONAL)
if ($id == $_SESSION['user_id']) {
    // You can remove this if you want admin to reset own password
}

// RESET PASSWORD
if (isset($_POST['reset_pass'])) {

    $new_pass = trim($_POST['password']);
    $hash = hash("sha256", $new_pass);

    $stmt = $mysqli->prepare("UPDATE users SET password_hash=? WHERE id=?");
    $stmt->bind_param("si", $hash, $id);

    if ($stmt->execute()) {
        $msg = "✔ Password reset successfully";
    } else {
        $msg = "❌ Error: " . $mysqli->error;
    }

    $stmt->close();
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Reset Password</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-4">

<h2 class="fw-bold">Reset User Password</h2>

<?php if ($msg): ?>
<div class="alert alert-info"><?= $msg ?></div>
<?php endif; ?>

<div class="card p-4 shadow-sm">

<form method="POST" class="row g-3">

    <div class="col-md-12">
        <label class="form-label fw-bold">New Password</label>
        <input type="password" name="password" class="form-control" required>
    </div>

    <div class="col-12">
        <button name="reset_pass" class="btn btn-warning w-100 fw-bold">
            Reset Password
        </button>
    </div>

</form>

<a href="../users.php" class="btn btn-secondary mt-3">Back</a>

</div>
</div>

</body>
</html>
