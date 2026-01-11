<?php
require_once '../db.php';
require_once '../auth.php';
require_login();

$msg = '';
$error = '';

function generate_customer_code($mysqli) {
    $res = $mysqli->query("SELECT MAX(id) AS max_id FROM customers");
    $row = $res->fetch_assoc();
    $next = (int)($row['max_id'] ?? 0) + 1;
    return 'CUST' . str_pad($next, 3, '0', STR_PAD_LEFT);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code  = trim($_POST['customer_code'] ?? '');
    $name  = trim($_POST['full_name'] ?? '');
    $type  = trim($_POST['type'] ?? 'Household');
    $addr  = trim($_POST['address'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if ($code === '' || $name === '') {
        $error = "Customer code and name are required.";
    } else {
        // check duplicate code
        $stmt = $mysqli->prepare("SELECT id FROM customers WHERE customer_code = ? LIMIT 1");
        $stmt->bind_param("s", $code);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $error = "Customer code already exists.";
        }
        $stmt->close();

        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid email format.";
        }

        if ($error === '') {
            $stmt = $mysqli->prepare("
                INSERT INTO customers (customer_code, full_name, type, address, phone, email)
                VALUES (?,?,?,?,?,?)
            ");
            $stmt->bind_param("ssssss", $code, $name, $type, $addr, $phone, $email);
            if ($stmt->execute()) {
                $msg = "Customer added successfully.";
                // clear form
                $code = generate_customer_code($mysqli);
                $name = $type = $addr = $phone = $email = '';
            } else {
                $error = "Database error: " . $mysqli->error;
            }
            $stmt->close();
        }
    }
} else {
    $code = generate_customer_code($mysqli);
    $name = $type = $addr = $phone = $email = '';
}

include '../header.php';
?>

<h2 class="mb-3"><i class="bi bi-person-plus-fill me-1"></i> Add New Customer</h2>

<?php if ($error): ?>
<div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if ($msg): ?>
<div class="alert alert-success py-2"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<div class="card shadow-sm">
  <div class="card-body">
    <form method="post">

      <div class="mb-3">
        <label class="form-label">Customer Code</label>
        <input type="text" name="customer_code" class="form-control"
               value="<?= htmlspecialchars($code) ?>" required>
        <div class="form-text">Auto-generated, you can change if needed.</div>
      </div>

      <div class="mb-3">
        <label class="form-label">Full Name</label>
        <input type="text" name="full_name" class="form-control"
               value="<?= htmlspecialchars($name) ?>" required>
      </div>

      <div class="mb-3">
        <label class="form-label">Type</label>
        <select name="type" class="form-select">
          <option <?= $type=='Household'?'selected':'' ?>>Household</option>
          <option <?= $type=='Business'?'selected':'' ?>>Business</option>
          <option <?= $type=='Government'?'selected':'' ?>>Government</option>
        </select>
      </div>

      <div class="mb-3">
        <label class="form-label">Address</label>
        <textarea name="address" class="form-control"
                  rows="3"><?= htmlspecialchars($addr) ?></textarea>
      </div>

      <div class="mb-3">
        <label class="form-label">Phone</label>
        <input type="text" name="phone" class="form-control"
               value="<?= htmlspecialchars($phone) ?>">
      </div>

      <div class="mb-3">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control"
               value="<?= htmlspecialchars($email) ?>">
      </div>

      <button class="btn btn-success">
        <i class="bi bi-check2-circle me-1"></i> Save Customer
      </button>
      <a href="../customers.php" class="btn btn-secondary ms-2">
        <i class="bi bi-arrow-left me-1"></i> Back to List
      </a>

    </form>
  </div>
</div>

<?php include '../footer.php'; ?>
