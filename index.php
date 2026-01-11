<?php
session_start();
require_once 'db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $mysqli->prepare("
        SELECT id, full_name, email, password_hash, role, status 
        FROM users 
        WHERE email = ? LIMIT 1
    ");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $res  = $stmt->get_result();
    $user = $res->fetch_assoc();
    $stmt->close();

    if (!$user) {
        $error = "❌ Email not found";
    }
    elseif ($user['status'] !== 'active') {
        $error = "❌ Account disabled — contact admin";
    }
    else {

        $stored = $user['password_hash'];
        $valid  = false;

        // ✔ bcrypt check
        if (password_verify($password, $stored)) {
            $valid = true;
        }
        // ✔ SHA-256 check (your DB uses this)
        elseif (hash('sha256', $password) === $stored) {
            $valid = true;

            // OPTIONAL: auto-upgrade old SHA passwords into bcrypt
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            $upd = $mysqli->prepare("UPDATE users SET password_hash=? WHERE id=?");
            $upd->bind_param("si", $newHash, $user['id']);
            $upd->execute();
            $upd->close();
        }

        if (!$valid) {
            $error = "❌ Incorrect password";
        }
    }

    if (!$error) {

        session_regenerate_id(true);
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['email']     = $user['email'];

        // Update last login
        $stmt2 = $mysqli->prepare("UPDATE users SET last_login = NOW() WHERE id=?");
        $stmt2->bind_param("i", $user['id']);
        $stmt2->execute();
        $stmt2->close();

        // Role-based redirect
        switch ($user['role']) {
            case 'admin':
                header("Location: dashboard.php");
                break;

            case 'cashier':
                header("Location: payments.php");
                break;

            default:
                header("Location: 4readings.php");
        }
        exit;
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>UMS Login</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container d-flex justify-content-center align-items-center" style="min-height:100vh;">
  <div class="card shadow-lg border-0" style="max-width:420px;width:100%;">
    <div class="card-body">

      <h3 class="card-title mb-3 text-center fw-bold">Utility Management System</h3>
      <p class="text-muted small text-center mb-3">Login with your credentials</p>

      <?php if ($error): ?>
        <div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="post">
        <div class="mb-3">
          <label class="form-label fw-bold">Email</label>
          <input type="email" name="email" class="form-control" required placeholder="Enter email">
        </div>

        <div class="mb-3">
          <label class="form-label fw-bold">Password</label>
          <input type="password" name="password" class="form-control" required placeholder="Enter password">
        </div>

        <button class="btn btn-primary w-100 fw-bold">Login</button>
      </form>

      <div class="text-center mt-2">
        <a href="forgot_password.php">Forgot Password?</a>
      </div>

    </div>
  </div>
</div>

</body>
</html>
