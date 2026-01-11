<?php
require_once 'db.php';
require_once 'auth.php';
require_login();

/* ============================
      LOAD REPORT DATA
============================= */
$unpaid = $mysqli->query("
    SELECT *
    FROM v_unpaid_bills
    ORDER BY billing_year DESC, billing_month DESC
");

$revenue = $mysqli->query("
    SELECT *
    FROM v_monthly_revenue
    ORDER BY billing_year DESC, billing_month DESC
");

$kpi = $mysqli->query("
    SELECT 
        SUM(total_amount) AS total_billed,
        SUM(amount_paid)  AS total_paid,
        SUM(outstanding)  AS total_due
    FROM bills
")->fetch_assoc();

include 'header.php';
?>

<style>
.page-header {
    margin-bottom: 2.5rem;
    padding: 1.5rem 0;
}
.page-header h2 {
    color: var(--card-text);
    font-size: 2.25rem;
    font-weight: 800;
    display: flex;
    align-items: center;
    gap: 14px;
}
.page-header h2 i {
    color: var(--accent);
    font-size: 2.5rem;
}
.kpi-card {
    border-radius: 20px;
    padding: 28px 24px;
    color: #fff;
    position: relative;
    overflow: hidden;
    backdrop-filter: blur(25px) saturate(150%);
    border: 2px solid rgba(255,255,255,0.2);
    box-shadow: 0 20px 50px rgba(15,23,42,0.40), inset 0 1px 0 rgba(255,255,255,0.2);
    transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
}
.kpi-card:hover {
    transform: translateY(-8px) scale(1.03);
    box-shadow: 0 30px 70px rgba(15,23,42,0.50);
}
.kpi-card h6 {
    font-weight: 700;
    letter-spacing: 1px;
    margin-bottom: 12px;
}
.kpi-card h2 {
    font-size: 2.5rem;
    font-weight: 800;
    text-shadow: 0 4px 15px rgba(0,0,0,0.3);
    letter-spacing: -1px;
}
.kpi-icon {
    position: absolute;
    right: 20px;
    bottom: 20px;
    opacity: 0.2;
    font-size: 70px;
}
.card-glass {
    border-radius: 20px;
    background: var(--card-bg);
    backdrop-filter: blur(25px) saturate(150%);
    border: 1px solid var(--card-border);
    box-shadow: var(--card-shadow);
    padding: 0;
    transition: all 0.45s cubic-bezier(0.4, 0, 0.2, 1);
    overflow: hidden;
    margin-bottom: 2rem;
}
.card-glass:hover {
    transform: translateY(-5px);
    box-shadow: 0 28px 65px rgba(15,23,42,0.35);
}
.card-header {
    font-weight: 700;
    font-size: 1.15rem;
    padding: 18px 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.table thead th {
    font-weight: 700;
}
.btn-export {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    border-radius: 10px;
    font-weight: 600;
    transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
}
.btn-export:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(255, 255, 255, 0.3);
}
</style>

<div class="page-header">
    <h2>
        <i class="bi bi-graph-up-arrow"></i>
        <span>Reports & Analytics</span>
    </h2>
</div>

<!-- ============================
      KPI SECTION
============================= -->
<div class="row mb-4">

    <div class="col-md-4">
        <div class="kpi-card shadow-sm" style="background: linear-gradient(135deg, #1d4ed8, #2563eb);">
            <h6 class="text-uppercase">TOTAL BILLED</h6>
            <h2 class="fw-bold">Rs. <?= number_format($kpi['total_billed'],2) ?></h2>
            <i class="bi bi-receipt-cutoff kpi-icon"></i>
        </div>
    </div>

    <div class="col-md-4">
        <div class="kpi-card shadow-sm" style="background: linear-gradient(135deg, #059669, #10b981);">
            <h6 class="text-uppercase">TOTAL COLLECTED</h6>
            <h2 class="fw-bold">Rs. <?= number_format($kpi['total_paid'],2) ?></h2>
            <i class="bi bi-cash-stack kpi-icon"></i>
        </div>
    </div>

    <div class="col-md-4">
        <div class="kpi-card shadow-sm" style="background: linear-gradient(135deg, #dc2626, #ef4444);">
            <h6 class="text-uppercase">OUTSTANDING DUES</h6>
            <h2 class="fw-bold">Rs. <?= number_format($kpi['total_due'],2) ?></h2>
            <i class="bi bi-exclamation-diamond-fill kpi-icon"></i>
        </div>
    </div>

</div>

<!-- ============================
      REVENUE CHART
============================= -->
<div class="card-glass">
  <div class="card-header" style="background: var(--accent); color: #fff;">
    <div>
        <i class="bi bi-bar-chart-fill"></i> Monthly Revenue Analytics
    </div>
  </div>
  <div class="card-body" style="padding: 28px;">
    <canvas id="revenueChart" height="120"></canvas>
  </div>
</div>

<div class="row">

<!-- ============================
      UNPAID BILLS TABLE
============================= -->
  <div class="col-md-6">
    <div class="card-glass">
      <div class="card-header" style="background: linear-gradient(135deg, #dc2626, #ef4444); color: #fff;">
        <div>
            <i class="bi bi-exclamation-circle-fill"></i> Unpaid Bills
        </div>
        <a href="export_unpaid_csv.php" class="btn btn-sm btn-light btn-export">
            <i class="bi bi-download"></i> Export CSV
        </a>
      </div>

      <div class="card-body p-0">
        <div class="table-responsive" style="max-height:420px;">
          <table class="table table-striped table-bordered table-sm mb-0">
            <thead class="table-dark">
              <tr>
                <th>ID</th>
                <th>Customer</th>
                <th>Month</th>
                <th>Total</th>
                <th>Paid</th>
                <th>Due</th>
                <th>Due Date</th>
              </tr>
            </thead>

            <tbody>
              <?php while ($u = $unpaid->fetch_assoc()): ?>
              <tr>
                <td><?= $u['bill_id'] ?></td>
                <td><?= $u['customer_code'].' - '.$u['full_name'] ?></td>
                <td><?= $u['billing_year'].'-'.$u['billing_month'] ?></td>
                <td class="text-primary fw-semibold">Rs. <?= number_format($u['total_amount'],2) ?></td>
                <td class="text-success fw-semibold">Rs. <?= number_format($u['amount_paid'],2) ?></td>
                <td class="text-danger fw-bold">Rs. <?= number_format($u['outstanding'],2) ?></td>
                <td><?= $u['due_date'] ?></td>
              </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

<!-- ============================
      MONTHLY REVENUE TABLE
============================= -->
  <div class="col-md-6">
    <div class="card-glass">
      <div class="card-header" style="background: linear-gradient(135deg, #059669, #10b981); color: #fff;">
        <div>
            <i class="bi bi-graph-up-arrow"></i> Monthly Revenue Summary
        </div>
        <a href="export_revenue_csv.php" class="btn btn-sm btn-light btn-export">
            <i class="bi bi-download"></i> Export CSV
        </a>
      </div>

      <div class="card-body p-0">
        <div class="table-responsive" style="max-height:420px;">
          <table class="table table-striped table-bordered table-sm mb-0">
            <thead class="table-dark">
              <tr>
                <th>Year</th>
                <th>Month</th>
                <th>Collected</th>
              </tr>
            </thead>

            <tbody>
              <?php 
              $chart_labels = [];
              $chart_data = [];

              while ($r = $revenue->fetch_assoc()):
                  $chart_labels[] = $r['billing_year'].'-'.$r['billing_month'];
                  $chart_data[]   = $r['total_collected'];
              ?>
              <tr>
                <td><?= $r['billing_year'] ?></td>
                <td><?= $r['billing_month'] ?></td>
                <td class="fw-semibold">Rs. <?= number_format($r['total_collected'],2) ?></td>
              </tr>
              <?php endwhile; ?>
            </tbody>

          </table>
        </div>
      </div>
    </div>
  </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// ===============================
//      REVENUE CHART JS
// ===============================
const ctx = document.getElementById('revenueChart');

new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?= json_encode($chart_labels) ?>,
        datasets: [{
            label: "Revenue (LKR)",
            data: <?= json_encode($chart_data) ?>,
            borderWidth: 3,
            borderColor: "#2563eb",
            backgroundColor: "rgba(37, 99, 235, 0.18)",
            tension: 0.35,
            pointRadius: 4,
            fill: true
        }]
    },
    options: {
        scales: {
            y: { beginAtZero: true }
        }
    }
});
</script>

<?php include 'footer.php'; ?>
