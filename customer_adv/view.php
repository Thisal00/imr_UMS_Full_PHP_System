<?php
require_once '../db.php';
require_once '../auth.php';
require_login();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    die("Invalid customer");
}

$c = $mysqli->query("SELECT * FROM customers WHERE id=$id")->fetch_assoc();
if (!$c) {
    die("Customer not found");
}

$meters = $mysqli->query("
    SELECT m.*, u.name AS utility_name
    FROM meters m 
    JOIN utilities u ON u.id = m.utility_id
    WHERE m.customer_id = $id
");

$bills = $mysqli->query("
    SELECT * FROM bills 
    WHERE customer_id = $id
    ORDER BY billing_year DESC, billing_month DESC
");

$payments = $mysqli->query("
    SELECT p.*, b.billing_year, b.billing_month
    FROM payments p
    JOIN bills b ON b.id = p.bill_id
    WHERE b.customer_id = $id
    ORDER BY p.payment_date DESC
");

include '../header.php';
?>

<h2 class="mb-3">
  <i class="bi bi-person-badge-fill me-1"></i>
  Customer Profile
</h2>

<div class="card mb-3 shadow-sm">
  <div class="card-body">
    <h4 class="card-title mb-2">
      <?= htmlspecialchars($c['full_name']) ?>
      <span class="badge bg-secondary ms-2"><?= $c['customer_code'] ?></span>
    </h4>
    <p class="mb-1">
      <strong>Type:</strong> <?= htmlspecialchars($c['type']) ?>
    </p>
    <p class="mb-1">
      <strong>Phone:</strong> <?= htmlspecialchars($c['phone']) ?><br>
      <strong>Email:</strong> <?= htmlspecialchars($c['email']) ?>
    </p>
    <p class="mb-0">
      <strong>Address:</strong><br>
      <?= nl2br(htmlspecialchars($c['address'])) ?>
    </p>
  </div>
</div>

<div class="row">
  <div class="col-md-6">
    <div class="card mb-3 shadow-sm">
      <div class="card-header">
        <i class="bi bi-speedometer2 me-1"></i> Meters
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-sm mb-0">
            <thead>
              <tr>
                <th>Meter No</th>
                <th>Utility</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($m = $meters->fetch_assoc()): ?>
              <tr>
                <td><?= htmlspecialchars($m['meter_number']) ?></td>
                <td><?= htmlspecialchars($m['utility_name']) ?></td>
                <td>
                  <?php if ($m['status'] == 'Active'): ?>
                    <span class="badge bg-success">Active</span>
                  <?php else: ?>
                    <span class="badge bg-secondary">Inactive</span>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endwhile; ?>
              <?php if ($meters->num_rows == 0): ?>
              <tr><td colspan="3" class="text-muted text-center">No meters</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <div class="col-md-6">
    <div class="card mb-3 shadow-sm">
      <div class="card-header">
        <i class="bi bi-receipt-cutoff me-1"></i> Bills
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-sm mb-0">
            <thead>
              <tr>
                <th>ID</th>
                <th>Month</th>
                <th>Total</th>
                <th>Paid</th>
                <th>Out</th>
              </tr>
            </thead>
            <tbody>
              <?php 
              $total_out = 0;
              while ($b = $bills->fetch_assoc()): 
                $total_out += $b['outstanding'];
              ?>
              <tr>
                <td><?= $b['id'] ?></td>
                <td><?= $b['billing_year'].'-'.$b['billing_month'] ?></td>
                <td><?= number_format($b['total_amount'],2) ?></td>
                <td><?= number_format($b['amount_paid'],2) ?></td>
                <td><?= number_format($b['outstanding'],2) ?></td>
              </tr>
              <?php endwhile; ?>
              <?php if ($total_out == 0): ?>
              <tr><td colspan="5" class="text-muted text-center">No bills</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
      <div class="card-footer">
        <strong>Total Outstanding: </strong>
        <?= number_format($total_out, 2) ?>
      </div>
    </div>
  </div>
</div>

<div class="card mb-3 shadow-sm">
  <div class="card-header">
    <i class="bi bi-cash-stack me-1"></i> Payments
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-sm mb-0">
        <thead>
          <tr>
            <th>Date</th>
            <th>Amount</th>
            <th>Month</th>
            <th>Method</th>
          </tr>
        </thead>
        <tbody>
          <?php 
          $paid_total = 0;
          while ($p = $payments->fetch_assoc()):
            $paid_total += $p['amount'];
          ?>
          <tr>
            <td><?= htmlspecialchars($p['payment_date']) ?></td>
            <td><?= number_format($p['amount'],2) ?></td>
            <td><?= $p['billing_year'].'-'.$p['billing_month'] ?></td>
            <td><?= htmlspecialchars($p['method']) ?></td>
          </tr>
          <?php endwhile; ?>
          <?php if ($paid_total == 0): ?>
          <tr><td colspan="4" class="text-muted text-center">No payments</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
  <div class="card-footer">
    <strong>Total Paid: </strong> <?= number_format($paid_total, 2) ?>
  </div>
</div>

<a href="../customers.php" class="btn btn-secondary">
  <i class="bi bi-arrow-left me-1"></i> Back to Customers
</a>

<?php include '../footer.php'; ?>
