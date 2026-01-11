<?php
require_once '../db.php';
require_once '../auth.php';
require_login();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    die("Invalid bill ID");
}

/* ================================
   LOAD BILL DETAILS
================================ */
$sql = "
SELECT b.*, 
       c.full_name, c.customer_code, c.address, c.phone, 
       m.meter_number,
       u.name AS utility_name,
       t.name AS tariff_name
FROM bills b
JOIN customers c ON c.id = b.customer_id
JOIN meters m    ON m.id = b.meter_id
JOIN utilities u ON u.id = m.utility_id
JOIN tariffs t   ON t.id = b.tariff_id
WHERE b.id = {$id}
LIMIT 1
";

$res  = $mysqli->query($sql);
$bill = $res ? $res->fetch_assoc() : null;

if (!$bill) {
    die("Bill not found");
}

/* ================================
   LOAD PAYMENT HISTORY
================================ */
$payRes = $mysqli->query("
    SELECT *
    FROM payments
    WHERE bill_id = {$id}
    ORDER BY payment_date ASC, id ASC
");

include '../header.php';
?>
<h2 class="mb-3">
    <i class="bi bi-file-earmark-text-fill"></i> Bill #<?= htmlspecialchars($bill['id']) ?>
</h2>

<div class="row">
  <div class="col-lg-8">

    <!-- CUSTOMER & METER CARD -->
    <div class="card shadow-sm mb-3">
      <div class="card-header bg-dark text-white d-flex justify-content-between">
        <span><i class="bi bi-person-vcard-fill"></i> Customer & Meter</span>
        <span class="badge bg-primary"><?= htmlspecialchars($bill['utility_name']) ?></span>
      </div>

      <div class="card-body">
        <div class="row mb-3">
          <div class="col-md-6">
            <h5>Customer</h5>
            <p class="mb-1">
              <strong><?= htmlspecialchars($bill['customer_code'].' - '.$bill['full_name']) ?></strong><br>
              <?= nl2br(htmlspecialchars($bill['address'] ?? '')) ?><br>
              <?= htmlspecialchars($bill['phone'] ?? '') ?>
            </p>
          </div>

          <div class="col-md-6">
            <h5>Meter</h5>
            <p>
              Meter No: <strong><?= htmlspecialchars($bill['meter_number']) ?></strong><br>
              Tariff: <?= htmlspecialchars($bill['tariff_name']) ?><br>
              Billing Month: <?= htmlspecialchars($bill['billing_year'].'-'.$bill['billing_month']) ?><br>
              Due Date: <?= htmlspecialchars($bill['due_date'] ?? '') ?>
            </p>
          </div>
        </div>

        <hr>

        <!-- BILL DETAILS -->
        <h5>Usage & Charges</h5>

        <table class="table table-bordered w-auto">
          <tr>
            <th>Units Used</th>
            <td><?= htmlspecialchars($bill['units']) ?></td>
          </tr>

          <tr>
            <th>Total Amount</th>
            <td>Rs. <?= number_format($bill['total_amount'], 2) ?></td>
          </tr>

          <tr>
            <th>Amount Paid</th>
            <td>Rs. <?= number_format($bill['amount_paid'], 2) ?></td>
          </tr>

          <tr>
            <th>Outstanding</th>
            <td class="<?= $bill['outstanding'] > 0 ? 'text-danger fw-bold' : 'text-success fw-bold' ?>">
                Rs. <?= number_format($bill['outstanding'], 2) ?>
            </td>
          </tr>

          <tr>
            <th>Status</th>
            <td>
              <?php if ($bill['status'] === 'Paid'): ?>
                <span class="badge bg-success">Paid</span>
              <?php elseif ($bill['status'] === 'Overdue'): ?>
                <span class="badge bg-danger">Overdue</span>
              <?php elseif ($bill['status'] === 'Partially Paid'): ?>
                <span class="badge bg-warning text-dark">Partially Paid</span>
              <?php else: ?>
                <span class="badge bg-secondary"><?= htmlspecialchars($bill['status']) ?></span>
              <?php endif; ?>
            </td>
          </tr>
        </table>
      </div>
    </div>

    <!-- PAYMENT HISTORY -->
    <div class="card shadow-sm mb-3">
      <div class="card-header bg-light">
        <i class="bi bi-cash-stack"></i> Payment History
      </div>

      <div class="card-body p-0">
        <table class="table table-sm mb-0">
          <thead class="table-secondary">
            <tr>
              <th>Date</th>
              <th>Amount</th>
              <th>Method</th>
              <th>Reference</th>
            </tr>
          </thead>

          <tbody>
            <?php if ($payRes && $payRes->num_rows > 0): ?>
              <?php while ($p = $payRes->fetch_assoc()): ?>
              <tr>
                <td><?= htmlspecialchars($p['payment_date']) ?></td>
                <td>Rs. <?= number_format($p['amount'], 2) ?></td>
                <td><?= htmlspecialchars($p['method']) ?></td>
                <td><?= htmlspecialchars($p['reference_no']) ?></td>
              </tr>
              <?php endwhile; ?>

            <?php else: ?>
              <tr>
                <td colspan="4" class="text-center text-muted py-2">
                  No payments recorded yet.
                </td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>

  <!-- RIGHT-SIDE ACTIONS -->
  <div class="col-lg-4">
    <div class="card shadow-sm mb-3">
      <div class="card-header bg-primary text-white">
        <i class="bi bi-gear-fill"></i> Actions
      </div>

      <div class="card-body d-grid gap-2">
        <a href="pay.php?id=<?= $bill['id'] ?>" class="btn btn-success">
          <i class="bi bi-cash-coin"></i> Add Payment
        </a>

        <a href="pdf.php?id=<?= $bill['id'] ?>" class="btn btn-secondary">
          <i class="bi bi-printer-fill"></i> Print / PDF
        </a>

        <a href="../bills.php" class="btn btn-outline-warning">
          <i class="bi bi-arrow-left-circle"></i> Back to Bills
        </a>

        <button class="btn btn-danger" id="deleteBillBtn" data-id="<?= $bill['id'] ?>">
          <i class="bi bi-trash-fill"></i> Delete Bill
        </button>
      </div>
    </div>
  </div>

</div>

<script src="js_handlers.js"></script>
<?php include '../footer.php'; ?>
