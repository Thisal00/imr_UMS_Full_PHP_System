<?php
require_once '../db.php';
require_once '../auth.php';
require_login();

$id = (int)($_GET['id'] ?? 0);

$q = $mysqli->query("
    SELECT r.*, 
           m.meter_number, 
           c.full_name, 
           c.customer_code,
           u.name AS utility_name
    FROM meter_readings r
    JOIN meters m ON m.id = r.meter_id
    JOIN customers c ON c.id = m.customer_id
    JOIN utilities u ON u.id = m.utility_id
    WHERE r.id = {$id}
    LIMIT 1
");

$rd = $q ? $q->fetch_assoc() : null;

if (!$rd) {
    die('Invalid Reading ID');
}

include '../header.php';
?>
<div class="row">
  <div class="col-md-8 offset-md-2">
    <div class="card shadow-sm mt-4">
      <div class="card-header bg-info text-white">
        <i class="bi bi-eye-fill"></i> Reading Details
      </div>
      <div class="card-body">
        <dl class="row mb-0">
          <dt class="col-sm-4">Meter</dt>
          <dd class="col-sm-8">
            <?= htmlspecialchars($rd['meter_number']) ?> 
            (<?= htmlspecialchars($rd['utility_name']) ?>)
          </dd>

          <dt class="col-sm-4">Customer</dt>
          <dd class="col-sm-8">
            <?= htmlspecialchars($rd['customer_code'].' - '.$rd['full_name']) ?>
          </dd>

          <dt class="col-sm-4">Reading Date</dt>
          <dd class="col-sm-8"><?= htmlspecialchars($rd['reading_date']) ?></dd>

          <dt class="col-sm-4">Previous Reading</dt>
          <dd class="col-sm-8"><?= htmlspecialchars($rd['previous_reading']) ?></dd>

          <dt class="col-sm-4">Current Reading</dt>
          <dd class="col-sm-8"><?= htmlspecialchars($rd['current_reading']) ?></dd>

          <dt class="col-sm-4">Units Used</dt>
          <dd class="col-sm-8"><span class="badge bg-secondary"><?= htmlspecialchars($rd['units_used']) ?></span></dd>

          <dt class="col-sm-4">Billing Month</dt>
          <dd class="col-sm-8">
            <?= htmlspecialchars($rd['billing_month']) ?>/<?= htmlspecialchars($rd['billing_year']) ?>
          </dd>

          <dt class="col-sm-4">Created At</dt>
          <dd class="col-sm-8"><?= htmlspecialchars($rd['created_at'] ?? '') ?></dd>
        </dl>
      </div>
      <div class="card-footer text-end">
        <a href="../readings.php" class="btn btn-dark">
          <i class="bi bi-arrow-left-circle"></i> Back to Readings
        </a>
      </div>
    </div>
  </div>
</div>
<?php include '../footer.php'; ?>
