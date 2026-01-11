<?php
require_once '../db.php';
require_once '../auth.php';
require_login();

$user_id = $_SESSION['user_id'] ?? 0;

// Load user data
$stmt = $mysqli->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) die("User not found");

// Device Info
$ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
$browser = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

// Profile Image
$img = !empty($user['profile_image'])
    ? "/UMS_Full_PHP_System/uploads/profiles/" . $user['profile_image']
    : "/UMS_Full_PHP_System/assets/default_avatar.png";
?>
<!DOCTYPE html>
<html>
<head>
<title>My Profile</title>
<link rel="stylesheet" href="/UMS_Full_PHP_System/assets/css/bootstrap.min.css">
<link rel="stylesheet" href="/UMS_Full_PHP_System/assets/css/profile.css">
</head>

<body>

<div class="profile-box">

<h3 class="fw-bold mb-4 text-center">My Profile</h3>

<div class="text-center mb-4">
    <img src="<?= $img ?>" class="profile-img"><br><br>

    <a href="profile_edit.php" class="btn btn-primary btn-custom">Edit Profile</a>
    <a href="profile_password.php" class="btn btn-warning btn-custom">Change Password</a>
</div>

<div class="section-title">Account Details</div>

<table class="table-custom">
<tr><th>Full Name</th><td><?= htmlspecialchars($user['full_name']) ?></td></tr>
<tr><th>Username</th><td><?= htmlspecialchars($user['username']) ?></td></tr>
<tr><th>Email</th><td><?= htmlspecialchars($user['email']) ?></td></tr>
<tr><th>Phone</th><td><?= htmlspecialchars($user['phone']) ?></td></tr>
<tr><th>Role</th><td><?= htmlspecialchars($user['role']) ?></td></tr>
<tr><th>Status</th><td><?= htmlspecialchars($user['status']) ?></td></tr>
<tr><th>Last Login</th><td><?= htmlspecialchars($user['last_login']) ?></td></tr>
<tr><th>Created At</th><td><?= htmlspecialchars($user['created_at']) ?></td></tr>
<tr><th>Updated At</th><td><?= htmlspecialchars($user['updated_at']) ?></td></tr>
</table>

<div class="section-title">Device Information</div>

<table class="table-custom">
<tr><th>IP Address</th><td><?= $ip ?></td></tr>
<tr><th>Browser</th><td><?= $browser ?></td></tr>
</table>

</div>

</body>
</html>
