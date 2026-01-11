<?php
session_start();
require_once 'db.php';

$error = '';
$success = '';

// Block direct access
if (!isset($_SESSION['reset_email']) || !isset($_SESSION['otp_verified'])) {
    header("Location: forgot_password.php");
    exit;
}

$email = $_SESSION['reset_email'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $password = trim($_POST['password'] ?? '');
    $confirm  = trim($_POST['confirm_password'] ?? '');

    // Strong password validation
    if (strlen($password) < 8) {
        $error = "Password must be at least 8 characters";
    }
    elseif (!preg_match("/[A-Z]/", $password)) {
        $error = "Password must contain at least 1 uppercase letter";
    }
    elseif (!preg_match("/[a-z]/", $password)) {
        $error = "Password must contain at least 1 lowercase letter";
    }
    elseif (!preg_match("/[0-9]/", $password)) {
        $error = "Password must contain at least 1 number";
    }
    elseif (!preg_match("/[@$!%*#?&]/", $password)) {
        $error = "Password must contain at least 1 special character (@,#,$,!)";
    }
    elseif ($password !== $confirm) {
        $error = "Passwords do not match";
    } 
    else {

        // Hash new password
        $hash = password_hash($password, PASSWORD_DEFAULT);

        // Update DB
        $stmt = $mysqli->prepare("
            UPDATE users 
            SET password_hash=?, otp=NULL, otp_expiry=NULL 
            WHERE email=?
        ");
        $stmt->bind_param("ss", $hash, $email);
        $stmt->execute();
        $stmt->close();

        // Clear session
        unset($_SESSION['otp_verified'], $_SESSION['reset_email']);

        $success = "✔ Password updated successfully!";
    }
}

?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Reset Password</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
    .password-box {
        font-size: 18px;
        padding: 12px;
        letter-spacing: 1px;
    }
</style>

</head>
<body class="bg-light">

<div class="container d-flex justify-content-center align-items-center" style="min-height:100vh;">

  <div class="card shadow-lg border-0" style="max-width:450px;width:100%;">
    <div class="card-body">

      <h3 class="text-center mb-3 fw-bold">Reset Your Password</h3>

      <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <?php if ($success): ?>
        <div class="alert alert-success text-center">
            <?= $success ?><br>
            <a href="index.php" class="btn btn-success btn-sm mt-2">Login Now</a>
        </div>
      <?php endif; ?>

      <?php if (!$success): ?>
      <form method="post">

        <div class="mb-3">
          <label class="form-label fw-bold">New Password</label>
          <input type="password" name="password" class="form-control password-box" required placeholder="Enter strong password">
        </div>

        <div class="mb-3">
          <label class="form-label fw-bold">Confirm Password</label>
          <input type="password" name="confirm_password" class="form-control password-box" required placeholder="Re-enter password">
        </div>

        <button class="btn btn-primary w-100 fw-bold">Update Password</button>

      </form>
      <?php endif; ?>

    </div>
  </div>

</div>

</body>
</html>
