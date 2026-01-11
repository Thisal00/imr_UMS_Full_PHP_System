<?php
session_start();
require_once 'db.php';
require_once 'send_mail.php'; // PHPMailer sender

$message = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "❌ Please enter a valid email.";
    } 
    else {

        // Check user exists
        $stmt = $mysqli->prepare("SELECT id, full_name FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res  = $stmt->get_result();
        $user = $res->fetch_assoc();
        $stmt->close();

        if (!$user) {
            $message = "❌ Email not found in system!";
        } 
        else {

            // Generate OTP
            $otp = rand(100000, 999999);
            $expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));

            // Save OTP in DB
            $stmt = $mysqli->prepare("UPDATE users SET otp=?, otp_expiry=? WHERE id=?");
            $stmt->bind_param("ssi", $otp, $expiry, $user['id']);
            $stmt->execute();
            $stmt->close();

            // Send email
            $subject = "Your UMS Password Reset OTP";
            $body = "
                Hello <strong>{$user['full_name']}</strong>,<br><br>
                Your OTP for password reset is:<br>
                <h2 style='color:#007bff;'>$otp</h2>
                This code is valid for <strong>10 minutes</strong>.<br><br>
                If you did not request this, please ignore the email.
                <br><br>UMS Support Team
            ";

            if (sendMail($email, $subject, $body)) {

                $_SESSION['reset_email'] = $email;

                $success = "✔ OTP sent to your email!";
                header("refresh:1.5; url=verify_otp.php");
            }
            else {
                $message = "❌ Failed to send email. Check SMTP settings!";
            }
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Forgot Password</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container d-flex justify-content-center align-items-center" style="min-height:100vh;">

  <div class="card shadow-lg border-0" style="max-width:420px; width:100%;">
    <div class="card-body">

      <h3 class="text-center fw-bold mb-3">Forgot Password</h3>
      <p class="text-center text-muted small">Enter your email to receive an OTP</p>

      <?php if ($message): ?>
        <div class="alert alert-danger"><?= $message ?></div>
      <?php endif; ?>

      <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
      <?php endif; ?>

      <form method="post">

        <div class="mb-3">
          <label class="form-label fw-bold">Email Address</label>
          <input type="email" name="email" class="form-control" placeholder="Enter your email" required>
        </div>

        <button class="btn btn-primary w-100 fw-bold">Send OTP</button>

      </form>

      <div class="text-center mt-3">
        <a href="index.php">Back to Login</a>
      </div>

    </div>
  </div>

</div>

</body>
</html>
