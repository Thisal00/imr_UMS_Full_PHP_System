<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['reset_email'])) {
    header("Location: forgot_password.php");
    exit;
}

$email = $_SESSION['reset_email'];
$error = "";
$success = "";

// Handle verify
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $otp_input = trim($_POST['otp'] ?? '');

    if (!preg_match('/^[0-9]{6}$/', $otp_input)) {
        $error = "❌ Invalid OTP format. Please enter 6 digits.";
    } else {

        $stmt = $mysqli->prepare("
            SELECT id, otp, otp_expiry 
            FROM users 
            WHERE email = ? 
            LIMIT 1
        ");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res  = $stmt->get_result();
        $user = $res->fetch_assoc();
        $stmt->close();

        if ($user) {
            $dbOTP = $user['otp'];
            $expiry = strtotime($user['otp_expiry']);
            $now = time();

            if ($dbOTP == $otp_input && $expiry > $now) {
                // OTP verified
                $_SESSION['otp_verified'] = true;
                $success = "✔ OTP verified! Redirecting...";
                header("refresh:1.5; url=reset_password.php");
            } 
            else {
                $error = "❌ Incorrect or expired OTP.";
            }
        } 
        else {
            $error = "❌ User not found.";
        }
    }
}

?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Verify OTP</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
    .otp-box {
        letter-spacing: 5px;
        font-size: 22px;
        text-align: center;
        font-weight: bold;
    }
</style>

</head>
<body class="bg-light">

<div class="container d-flex justify-content-center align-items-center" style="min-height:100vh;">

  <div class="card shadow-lg border-0" style="max-width:420px;width:100%;">
    <div class="card-body">

      <h3 class="text-center fw-bold mb-3">Verify OTP</h3>
      <p class="text-center text-muted small">
        We sent a 6-digit code to <strong><?= htmlspecialchars($email); ?></strong>
      </p>

      <?php if ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
      <?php endif; ?>

      <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
      <?php endif; ?>

      <form method="post">

        <div class="mb-3">
          <label class="form-label fw-bold">Enter OTP</label>
          <input type="text" name="otp" maxlength="6" class="form-control otp-box"
                 placeholder="------" required>
        </div>

        <button class="btn btn-primary w-100 fw-bold">Verify OTP</button>

      </form>

      <div class="text-center mt-3">
        <a href="forgot_password.php" class="small">Resend OTP</a>
      </div>

    </div>
  </div>

</div>

</body>
</html>
