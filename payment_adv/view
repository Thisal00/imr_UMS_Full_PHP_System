<?php
require_once '../db.php';
require_once '../auth.php';
require_login();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    die("Invalid payment ID");
}

$sql = "
SELECT p.*, 
       b.id AS bill_id, b.billing_year, b.billing_month, b.total_amount,
       c.customer_code, c.full_name, c.address, c.phone,
       m.meter_number,
       u.name AS utility_name
FROM payments p
JOIN bills b     ON b.id = p.bill_id
JOIN customers c ON c.id = b.customer_id
JOIN meters m    ON m.id = b.meter_id
JOIN utilities u ON u.id = m.utility_id
WHERE p.id = {$id}
LIMIT 1
";

$res = $mysqli->query($sql);
$pay = $res ? $res->fetch_assoc() : null;

if (!$pay) {
    die("Payment not found");
}

include '../header.php';
?>

<h2 class="mb-3">
  <i class="bi bi-receipt"></i> Payment Receipt
</h2>

<div class="card shadow-sm mb-4 border-0">
  <div class="card-header bg-dark text-white d-flex justify-content-between">
    <span><i class="bi bi-cash-coin"></i> Receipt #<?= htmlspecialchars($pay['id']) ?></span>
    <span class="badge bg-primary"><?= htmlspecialchars($pay['utility_name']) ?></span>
  </div>

  <div class="card-body">

    <div class="row mb-3">
      <div class="col-md-6">
        <h5>Customer</h5>
        <p class="mb-1">
          <strong><?= htmlspecialchars($pay['customer_code'].' - '.$pay['full_name']) ?></strong><br>
          <?= nl2br(htmlspecialchars($pay['address'] ?? '')) ?><br>
          <?= htmlspecialchars($pay['phone'] ?? '') ?>
        </p>
      </div>
      <div class="col-md-6">
        <h5>Bill &amp; Meter</h5>
        <p class="mb-1">
          Bill #: <strong><?= htmlspecialchars($pay['bill_id']) ?></strong><br>
          Billing Month: <?= htmlspecialchars($pay['billing_year'].'-'.$pay['billing_month']) ?><br>
          Utility: <?= htmlspecialchars($pay['utility_name']) ?><br>
          Meter: <?= htmlspecialchars($pay['meter_number']) ?><br>
          Bill Total: Rs. <?= number_format($pay['total_amount'], 2) ?>
        </p>
      </div>
    </div>

    <hr>

    <h5>Payment Details</h5>
    <table class="table table-bordered w-auto">
      <tr>
        <th>Receipt No</th>
        <td>#<?= htmlspecialchars($pay['id']) ?></td>
      </tr>
      <tr>
        <th>Payment Date</th>
        <td><?= htmlspecialchars($pay['payment_date']) ?></td>
      </tr>
      <tr>
        <th>Amount</th>
        <td><span class="fw-bold text-success">Rs. <?= number_format($pay['amount'],2) ?></span></td>
      </tr>
      <tr>
        <th>Method</th>
        <td><?= htmlspecialchars($pay['method']) ?></td>
      </tr>
      <tr>
        <th>Reference No</th>
        <td><?= htmlspecialchars($pay['reference_no'] ?: '-') ?></td>
      </tr>
    </table>

    <p class="text-muted mt-3 mb-0">
      This is a system generated receipt from UMS. No signature is required.
    </p>

  </div>

  <div class="card-footer d-flex justify-content-between">
    <div>
      <a href="../payments.php" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left-circle"></i> Back to Payments
      </a>
      <a href="receipt.php?id=<?= $pay['id'] ?>" class="btn btn-secondary">
        <i class="bi bi-printer-fill"></i> Print / PDF
      </a>
    </div>
    <button class="btn btn-danger paymentDeleteBtn" data-id="<?= $pay['id'] ?>">
      <i class="bi bi-trash-fill"></i> Delete Payment
    </button>
  </div>
</div>

<script src="js_handlers.js"></script>

<?php include '../footer.php'; ?>

