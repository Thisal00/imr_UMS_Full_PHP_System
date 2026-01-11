<?php
require_once 'db.php';
require_once 'auth.php';
require_login();

/* ============================
   KPI CARDS
============================ */
$total_customers = $mysqli->query("SELECT COUNT(*) c FROM customers")
    ->fetch_assoc()['c'] ?? 0;

$total_meters = $mysqli->query("SELECT COUNT(*) c FROM meters")
    ->fetch_assoc()['c'] ?? 0;

$unpaid_bills = $mysqli->query("SELECT COUNT(*) c FROM bills WHERE outstanding > 0")
    ->fetch_assoc()['c'] ?? 0;

$total_revenue = $mysqli->query("SELECT COALESCE(SUM(amount_paid),0) s FROM bills")
    ->fetch_assoc()['s'] ?? 0;

/* ============================
   MONTHLY REVENUE (VIEW)
============================ */
$revRes = $mysqli->query("
    SELECT billing_year, billing_month, total_collected
    FROM v_monthly_revenue
    ORDER BY billing_year, billing_month
");
$rev_labels = [];
$rev_data   = [];
while ($r = $revRes->fetch_assoc()) {
    $rev_labels[] = $r['billing_year'] . '-' . str_pad($r['billing_month'], 2, '0', STR_PAD_LEFT);
    $rev_data[]   = (float)$r['total_collected'];
}

/* ============================
   MONTHLY UNITS (FROM bills)
============================ */
$unitRes = $mysqli->query("
    SELECT billing_year, billing_month, SUM(units) AS total_units
    FROM bills
    GROUP BY billing_year, billing_month
    ORDER BY billing_year, billing_month
");
$unit_labels = [];
$unit_data   = [];
while ($u = $unitRes->fetch_assoc()) {
    $unit_labels[] = $u['billing_year'] . '-' . str_pad($u['billing_month'], 2, '0', STR_PAD_LEFT);
    $unit_data[]   = (float)$u['total_units'];
}

/* ============================
   DAILY COLLECTION (THIS MONTH)
============================ */
$curYear  = (int)date('Y');
$curMonth = (int)date('m');

$dayRes = $mysqli->query("
    SELECT DATE(payment_date) d, SUM(amount) s
    FROM payments
    WHERE YEAR(payment_date) = {$curYear}
      AND MONTH(payment_date) = {$curMonth}
    GROUP BY DATE(payment_date)
    ORDER BY d
");
$day_labels = [];
$day_data   = [];
while ($d = $dayRes->fetch_assoc()) {
    $day_labels[] = $d['d'];
    $day_data[]   = (float)$d['s'];
}

/* ============================
   CUSTOMER GROWTH (BY DATE)
============================ */
$custRes = $mysqli->query("
    SELECT DATE(created_at) d, COUNT(*) c
    FROM customers
    GROUP BY DATE(created_at)
    ORDER BY d
");
$cust_labels = [];
$cust_data   = [];
while ($c = $custRes->fetch_assoc()) {
    $cust_labels[] = $c['d'];
    $cust_data[]   = (int)$c['c'];
}

/* ============================
   CUSTOMER TYPE DISTRIBUTION
============================ */
$typeRes = $mysqli->query("
    SELECT type, COUNT(*) c
    FROM customers
    GROUP BY type
");
$type_labels = [];
$type_data   = [];
while ($t = $typeRes->fetch_assoc()) {
    $type_labels[] = $t['type'] ?: 'Unknown';
    $type_data[]   = (int)$t['c'];
}

/* ============================
   UNPAID BILLS BY TARIFF
============================ */
$unpaidRes = $mysqli->query("
    SELECT tariff_id, COUNT(*) c
    FROM bills
    WHERE outstanding > 0
    GROUP BY tariff_id
");
$tariff_labels = [];
$tariff_data   = [];
while ($u = $unpaidRes->fetch_assoc()) {
    $tariff_labels[] = 'Tariff ' . $u['tariff_id'];
    $tariff_data[]   = (int)$u['c'];
}

/* ============================
   METER STATUS (ACTIVE / INACTIVE)
============================ */
$meterRes = $mysqli->query("
    SELECT status, COUNT(*) c
    FROM meters
    GROUP BY status
");
$meter_labels = [];
$meter_data   = [];
while ($m = $meterRes->fetch_assoc()) {
    $meter_labels[] = $m['status'] ?: 'Unknown';
    $meter_data[]   = (int)$m['c'];
}

/* ============================
   PAYMENT METHODS
============================ */
$payRes = $mysqli->query("
    SELECT method, COUNT(*) c
    FROM payments
    GROUP BY method
");
$pay_labels = [];
$pay_data   = [];
while ($p = $payRes->fetch_assoc()) {
    $pay_labels[] = $p['method'] ?: 'Other';
    $pay_data[]   = (int)$p['c'];
}

include 'header.php';
?>

<div class="glass-wrapper" style="padding-top: 20px; padding-bottom: 40px;">

  <h1 class="fw-bold mb-4 d-flex align-items-center gap-3" style="color: var(--card-text); font-size: 2.25rem;">
    <i class="bi bi-grid-fill" style="color: var(--accent); font-size: 2.5rem;"></i>
    <span>Dashboard Analytics</span>
  </h1>

  <!-- KPI ROW -->
  <div class="row g-4 mb-4">
    <div class="col-md-3">
      <div class="kpi-glass kpi-customers">
        <div class="kpi-label"><i class="bi bi-people-fill me-2"></i>Total Customers</div>
        <div class="kpi-value"><?= number_format($total_customers) ?></div>
        <i class="bi bi-people-fill"></i>
      </div>
    </div>
    <div class="col-md-3">
      <div class="kpi-glass kpi-meters">
        <div class="kpi-label"><i class="bi bi-speedometer2 me-2"></i>Active Meters</div>
        <div class="kpi-value"><?= number_format($total_meters) ?></div>
        <i class="bi bi-speedometer2"></i>
      </div>
    </div>
    <div class="col-md-3">
      <div class="kpi-glass kpi-unpaid">
        <div class="kpi-label"><i class="bi bi-exclamation-triangle-fill me-2"></i>Unpaid Bills</div>
        <div class="kpi-value"><?= number_format($unpaid_bills) ?></div>
        <i class="bi bi-exclamation-triangle-fill"></i>
      </div>
    </div>
    <div class="col-md-3">
      <div class="kpi-glass kpi-revenue">
        <div class="kpi-label"><i class="bi bi-currency-dollar me-2"></i>Total Revenue</div>
        <div class="kpi-value">Rs. <?= number_format($total_revenue, 0) ?></div>
        <i class="bi bi-currency-dollar"></i>
      </div>
    </div>
  </div>

  <!-- ROW 1: Revenue + Units -->
  <div class="row g-4 mb-2">
    <div class="col-md-6">
      <div class="card-glass">
        <h5><i class="bi bi-graph-up-arrow"></i> Monthly Revenue</h5>
        <div class="chart-wrap"><canvas id="revChart"></canvas></div>
      </div>
    </div>
    <div class="col-md-6">
      <div class="card-glass">
        <h5><i class="bi bi-lightning-charge-fill"></i> Monthly Units Consumed</h5>
        <div class="chart-wrap"><canvas id="unitChart"></canvas></div>
      </div>
    </div>
  </div>

  <!-- ROW 2: Daily collections + Payment methods -->
  <div class="row g-4 mb-2">
    <div class="col-md-8">
      <div class="card-glass">
        <h5><i class="bi bi-calendar-check-fill"></i> Daily Collections (This Month)</h5>
        <div class="chart-wrap"><canvas id="dailyChart"></canvas></div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card-glass">
        <h5><i class="bi bi-credit-card-fill"></i> Payment Methods</h5>
        <div class="chart-wrap"><canvas id="payMethodChart"></canvas></div>
      </div>
    </div>
  </div>

  <!-- ROW 3: Customer growth + type -->
  <div class="row g-4 mb-2">
    <div class="col-md-8">
      <div class="card-glass">
        <h5><i class="bi bi-graph-up"></i> Customer Growth Trend</h5>
        <div class="chart-wrap"><canvas id="custChart"></canvas></div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card-glass">
        <h5><i class="bi bi-diagram-3-fill"></i> Customer Distribution</h5>
        <div class="chart-wrap"><canvas id="custTypeChart"></canvas></div>
      </div>
    </div>
  </div>

  <!-- ROW 4: Tariff unpaid + Meter status -->
  <div class="row g-4 mb-2">
    <div class="col-md-6">
      <div class="card-glass">
        <h5><i class="bi bi-pie-chart-fill"></i> Unpaid Bills by Tariff</h5>
        <div class="chart-wrap"><canvas id="donutChart"></canvas></div>
      </div>
    </div>
    <div class="col-md-6">
      <div class="card-glass">
        <h5><i class="bi bi-toggle-on"></i> Meter Status Overview</h5>
        <div class="chart-wrap"><canvas id="meterChart"></canvas></div>
      </div>
    </div>
  </div>

</div>

<script>
// ===== DATA FROM PHP =====
const revLabels     = <?= json_encode($rev_labels) ?>;
const revData       = <?= json_encode($rev_data) ?>;
const unitLabels    = <?= json_encode($unit_labels) ?>;
const unitData      = <?= json_encode($unit_data) ?>;
const dayLabels     = <?= json_encode($day_labels) ?>;
const dayData       = <?= json_encode($day_data) ?>;
const custLabels    = <?= json_encode($cust_labels) ?>;
const custData      = <?= json_encode($cust_data) ?>;
const typeLabels    = <?= json_encode($type_labels) ?>;
const typeData      = <?= json_encode($type_data) ?>;
const tariffLbls    = <?= json_encode($tariff_labels) ?>;
const tariffData    = <?= json_encode($tariff_data) ?>;
const meterLabels   = <?= json_encode($meter_labels) ?>;
const meterData     = <?= json_encode($meter_data) ?>;
const payLabels     = <?= json_encode($pay_labels) ?>;
const payData       = <?= json_encode($pay_data) ?>;

// Monthly Revenue
if (revLabels.length) {
  new Chart(document.getElementById('revChart'), {
    type: 'line',
    data: { labels: revLabels, datasets: [{
      label: 'Revenue (LKR)',
      data: revData,
      borderWidth: 3,
      borderColor: '#2563eb',
      backgroundColor: 'rgba(37,99,235,0.15)',
      tension: 0.35,
      pointRadius: 3
    }]},
    options: { maintainAspectRatio:false, scales:{ y:{beginAtZero:true} } }
  });
}

// Monthly Units
if (unitLabels.length) {
  new Chart(document.getElementById('unitChart'), {
    type: 'line',
    data: { labels: unitLabels, datasets: [{
      label: 'Units',
      data: unitData,
      borderWidth: 3,
      borderColor: '#16a34a',
      backgroundColor: 'rgba(22,163,74,0.15)',
      tension: 0.35,
      pointRadius: 3
    }]},
    options: { maintainAspectRatio:false, scales:{ y:{beginAtZero:true} } }
  });
}

// Daily Collections
if (dayLabels.length) {
  new Chart(document.getElementById('dailyChart'), {
    type: 'bar',
    data: { labels: dayLabels, datasets: [{
      label: 'Amount (LKR)',
      data: dayData,
      backgroundColor: 'rgba(249,115,22,0.85)'
    }]},
    options: { maintainAspectRatio:false, scales:{ y:{beginAtZero:true} } }
  });
}

// Customer Growth
if (custLabels.length) {
  new Chart(document.getElementById('custChart'), {
    type: 'bar',
    data: { labels: custLabels, datasets: [{
      label: 'New Customers',
      data: custData,
      backgroundColor: 'rgba(34,197,94,0.85)'
    }]},
    options: { maintainAspectRatio:false, scales:{ y:{beginAtZero:true} } }
  });
}

// Customer Types
if (typeLabels.length) {
  new Chart(document.getElementById('custTypeChart'), {
    type: 'doughnut',
    data: { labels: typeLabels, datasets:[{ data: typeData }]},
    options: { maintainAspectRatio:false, plugins:{ legend:{position:'bottom'} } }
  });
}

// Unpaid Bills by Tariff
if (tariffLbls.length) {
  new Chart(document.getElementById('donutChart'), {
    type: 'doughnut',
    data: { labels: tariffLbls, datasets:[{ data: tariffData }]},
    options: { maintainAspectRatio:false, plugins:{ legend:{position:'bottom'} } }
  });
}

// Meter Status
if (meterLabels.length) {
  new Chart(document.getElementById('meterChart'), {
    type: 'doughnut',
    data: { labels: meterLabels, datasets:[{ data: meterData }]},
    options: { maintainAspectRatio:false, plugins:{ legend:{position:'bottom'} } }
  });
}

// Payment Methods
if (payLabels.length) {
  new Chart(document.getElementById('payMethodChart'), {
    type: 'doughnut',
    data: { labels: payLabels, datasets:[{ data: payData }]},
    options: { maintainAspectRatio:false, plugins:{ legend:{position:'bottom'} } }
  });
}

</script>

<?php include 'footer.php'; ?>
