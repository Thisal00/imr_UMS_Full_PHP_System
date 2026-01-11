<?php
require_once '../db.php';
require_once '../auth.php';
require_login();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    die("Invalid bill ID");
}

$bill = $mysqli->query("SELECT * FROM bills WHERE id = {$id}")->fetch_assoc();
if (!$bill) {
    die("Bill not found");
}

include '../header.php';
?>
<h2 class="mb-3">
  <i class="bi bi-cash-stack"></i> Add Payment for Bill #<?= htmlspecialchars($bill['id']) ?>
</h2>

<div class="card shadow-sm">
  <div class="card-body">

    <p>
      <strong>Outstanding:</strong> 
      Rs. <?= number_format($bill['outstanding'], 2) ?>
    </p>

    <form method="post" action="save_payment.php" class="row g-3">

      <input type="hidden" name="bill_id" value="<?= $bill['id'] ?>">

      <div class="col-md-4">
        <label class="form-label">Amount</label>
        <input type="number" step="0.01" name="amount" class="form-control" required>
      </div>

      <div class="col-md-4">
        <label class="form-label">Method</label>
        <select name="method" class="form-select">
          <option>Cash</option>
          <option>Card</option>
          <option>Online</option>
        </select>
      </div>

      <div class="col-md-4">
        <label class="form-label">Reference No</label>
        <input type="text" name="ref_no" class="form-control">
      </div>

      <div class="col-12">
        <button class="btn btn-success">
          <i class="bi bi-check-circle"></i> Save Payment
        </button>
        <a href="view.php?id=<?= $bill['id'] ?>" class="btn btn-outline-secondary">
          Cancel
        </a>
      </div>

    </form>

  </div>
</div>

<?php include '../footer.php'; ?>
