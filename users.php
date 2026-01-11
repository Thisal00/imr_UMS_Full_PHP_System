<?php
require_once __DIR__ . "/includes/auth.php";
require_role('admin');
require_once __DIR__ . "/db.php";

$msg = "";

/* ===========================
   ADD NEW USER
=========================== */
if (isset($_POST['add_user'])) {

    $full_name = trim($_POST['full_name']);
    $email     = trim($_POST['email']);
    $role      = trim($_POST['role']);
    $status    = trim($_POST['status']);
    $password  = trim($_POST['password']);

    // Secure hash
    $hash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $mysqli->prepare("
        INSERT INTO users (full_name, email, password_hash, role, status, created_at)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");

    $stmt->bind_param("sssss", $full_name, $email, $hash, $role, $status);

    if ($stmt->execute()) {
        $msg = "✔ User added successfully!";
    } else {
        $msg = "❌ Error: " . $mysqli->error;
    }

    $stmt->close();
}

$users = $mysqli->query("SELECT * FROM users ORDER BY id DESC");
?>
<?php include __DIR__ . "/header.php"; ?>

<div class="container container-padded mb-6">

<h2 class="fw-bold mb-4">👥 User Management</h2>

<?php if ($msg): ?>
<div class="alert alert-success"><?= $msg ?></div>
<?php endif; ?>

<!-- ADD USER PANEL -->
<div class="card card-glass mb-4">

<h4 class="mb-3"><i class="bi bi-person-plus text-primary"></i> Add New User</h4>

<form method="POST" class="row g-3">

    <div class="col-md-6">
        <label class="form-label">Full Name</label>
        <input type="text" name="full_name" class="form-control" required>
    </div>

    <div class="col-md-6">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" required>
    </div>

    <div class="col-md-4">
        <label class="form-label">Role</label>
        <select name="role" class="form-select">
            <option value="admin">Admin</option>
            <option value="cashier">Cashier</option>
            <option value="reader">Meter Reader</option>
        </select>
    </div>

    <div class="col-md-4">
        <label class="form-label">Status</label>
        <select name="status" class="form-select">
            <option value="active">Active</option>
            <option value="disabled">Disabled</option>
        </select>
    </div>

    <div class="col-md-4">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-control" required>
    </div>

    <div class="col-12">
        <button name="add_user" class="btn btn-primary w-100">
            <i class="bi bi-save"></i> Save User
        </button>
    </div>

</form>
</div>

<!-- USERS TABLE -->
<div class="card card-glass">

<h4 class="mb-3"><i class="bi bi-people"></i> All Users</h4>

<table class="table table-bordered table-hover table-users">
<thead>
<tr>
    <th>ID</th>
    <th>User</th>
    <th>Email</th>
    <th>Role</th>
    <th>Status</th>
    <th style="width:160px;">Actions</th>
</tr>
</thead>

<tbody>
<?php while ($u = $users->fetch_assoc()): ?>

<?php
$BASE_URL = "/UMS_Full_PHP_System";
$avatar = !empty($u['profile_image'])
    ? $BASE_URL . "/uploads/profiles/" . $u['profile_image']
    : $BASE_URL . "/assets/default_avatar.png";
?>

<tr>
    <td><?= $u['id'] ?></td>

    <td>
        <img src="<?= $avatar ?>" class="user-avatar me-2">
        <?= $u['full_name'] ?>
    </td>

    <td><?= $u['email'] ?></td>

    <td>
        <span class="badge bg-secondary"><?= $u['role'] ?></span>
    </td>

    <td>
        <span class="badge <?= $u['status']=='active'?'bg-success':'bg-danger' ?>">
            <?= $u['status'] ?>
        </span>
    </td>

    <td>
        <a href="user_adv/edit.php?id=<?= $u['id'] ?>" class="btn btn-warning btn-sm btn-action">
            <i class="bi bi-pencil"></i>
        </a>

        <a href="user_adv/reset_password.php?id=<?= $u['id'] ?>" class="btn btn-info btn-sm text-white btn-action">
            <i class="bi bi-key"></i>
        </a>

        <a onclick="return confirm('Delete user?')" 
           href="user_adv/delete.php?id=<?= $u['id'] ?>" 
           class="btn btn-danger btn-sm btn-action">
            <i class="bi bi-trash"></i>
        </a>
    </td>
</tr>

<?php endwhile; ?>
</tbody>
</table>

</div>

<?php include __DIR__ . "/footer.php"; ?>
