<?php
require_once '../db.php';
require_once '../auth.php';
require_login();

$user_id = $_SESSION['user_id'];

// Load current user info
$stmt = $mysqli->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $full_name = $mysqli->real_escape_string($_POST['full_name']);
    $email     = $mysqli->real_escape_string($_POST['email']);
    $phone     = $mysqli->real_escape_string($_POST['phone']);

    // PROFILE IMAGE UPLOAD
    $filename = $user['profile_image'];

    if (!empty($_FILES['profile_image']['name'])) {
        $ext = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
        $newName = "USER_" . $user_id . "_" . time() . "." . $ext;

        move_uploaded_file($_FILES['profile_image']['tmp_name'],
            "../uploads/profiles/" . $newName);

        $filename = $newName;
    }

    // Update user
    $update = $mysqli->prepare("
        UPDATE users 
        SET full_name=?, email=?, phone=?, profile_image=?, updated_at=NOW()
        WHERE id=?
    ");
    $update->bind_param("ssssi", $full_name, $email, $phone, $filename, $user_id);
    $update->execute();

    header("Location: profile.php?updated=1");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Edit Profile</title>
<link rel="stylesheet" href="/UMS_Full_PHP_System/assets/css/bootstrap.min.css">
<link rel="stylesheet" href="/UMS_Full_PHP_System/assets/css/profile.css">
</head>

<body>

<div class="edit-box">

<h3 class="fw-bold mb-4">Edit Profile</h3>

<form method="POST" enctype="multipart/form-data">

    <label>Full Name</label>
    <input name="full_name" class="form-control mb-3"
           value="<?= htmlspecialchars($user['full_name']) ?>" required>

    <label>Email</label>
    <input name="email" class="form-control mb-3"
           value="<?= htmlspecialchars($user['email']) ?>" required>

    <label>Phone</label>
    <input name="phone" class="form-control mb-3"
           value="<?= htmlspecialchars($user['phone']) ?>">

    <label>Profile Image</label>
    <input type="file" name="profile_image" class="form-control mb-4">

    <button class="btn btn-primary btn-custom">Save Changes</button>
    <a href="profile.php" class="btn btn-secondary">Cancel</a>

</form>

</div>
</body>
</html>
