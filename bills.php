<?php
require_once 'db.php';
require_once 'auth.php';
require_login();

/* ============ FILTER INPUTS ============ */
$month   = (int)($_GET['month'] ?? 0);
$year    = (int)($_GET['year'] ?? date('Y'));
$utility = (int)($_GET['utility'] ?? 0);
$status  = trim($_GET['status'] ?? '');
$search  = trim($_GET['search'] ?? '');

/* ============ WHERE CLAUSE ============ */
$where = "1=1";

if ($month > 0)  $where .= " AND b.billing_month = $month";
if ($year > 0)   $where .= " AND b.billing_year  = $year";
if ($utility > 0) $where .= " AND u.id = $utility";

if ($status !== '') {
    $safe = $mysqli->real_escape_string($status);
    $where .= " AND b.status = '$safe'";
}

if ($search !== '') {
    $safe = $mysqli->real_escape_string($search);
    $where .= " AND (c.customer_code LIKE '%$safe%' 
                  OR c.full_name LIKE '%$safe%' 
                  OR m.meter_number LIKE '%$safe%')";
}

/* ============ BILL QUERY ============ */
$sql = "
SELECT b.*, c.customer_code, c.full_name,
       m.meter_number,
       u.id AS utility_id, u.name AS utility_name,
       t.name AS tariff_name
FROM bills b
JOIN customers c ON c.id = b.customer_id
JOIN meters m    ON m.id = b.meter_id
JOIN utilities u ON u.id = m.utility_id
JOIN tariffs t   ON t.id = b.tariff_id
WHERE $where
ORDER BY b.billing_year DESC, b.billing_month DESC, b.id DESC
LIMIT 300
";
$res = $mysqli->query($sql);

/* Load utilities for filter */
$utilList = $mysqli->query("SELECT * FROM utilities ORDER BY name ASC");

include 'header.php';
?>

<style>
.page-header {
    margin-bottom: 2rem;
    padding: 1.5rem 0;
}
.page-header h2 {
    color: var(--card-text);
    font-size: 2rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 12px;
}
.page-header h2 i {
    color: var(--accent);
    font-size: 2.25rem;
}
.card-glass {
    border-radius: 20px;
    background: var(--card-bg);
    backdrop-filter: blur(25px) saturate(150%);
    border: 1px solid var(--card-border);
    box-shadow: var(--card-shadow);
    padding: 28px;
    transition: all 0.45s cubic-bezier(0.4, 0, 0.2, 1);
    margin-bottom: 2rem;
}
.btn-action {
    padding: 6px 12px;
    border-radius: 8px;
    transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
}
.btn-action:hover {
    transform: translateY(-3px) scale(1.1);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
}
.form-control, .form-select {
    border-radius: 12px;
    border: 2px solid var(--card-border);
    padding: 10px 16px;
    background: var(--card-bg);
    color: var(--card-text);
}
.table thead th {
    position: sticky;
    top: 0;
    background: var(--table-head-bg) !important;
    color: var(--table-head-text) !important;
    z-index: 10;
    font-weight: 700;
}
</style>

<div class="page-header">
    <h2>
        <i class="bi bi-receipt-cutoff"></i>
        <span>Bills Management</span>
    </h2>
</div>

<!-- FILTER CARD -->
<div class="card-glass">
  <div class="card-body">

    <form class="row gy-2 gx-3" method="get">

      <div class="col-md-2">
        <label class="form-label fw-bold">Month</label>
        <select name="month" class="form-select">
          <option value="0">All</option>
          <?php for ($m=1;$m<=12;$m++): ?>
          <option value="<?= $m ?>" <?= $month==$m?'selected':'' ?>><?= $m ?></option>
          <?php endfor; ?>
        </select>
      </div>

      <div class="col-md-2">
        <label class="form-label fw-bold">Year</label>
        <input type="number" name="year" class="form-control" value="<?= $year ?>">
      </div>

      <div class="col-md-2">
        <label class="form-label fw-bold">Utility</label>
        <select name="utility" class="form-select">
          <option value="0">All</option>
          <?php while($u=$utilList->fetch_assoc()): ?>
          <option value="<?= $u['id'] ?>" <?= $utility==$u['id']?'selected':'' ?>>
            <?= $u['name'] ?>
          </option>
          <?php endwhile; ?>
        </select>
      </div>

      <div class="col-md-2">
        <label class="form-label fw-bold">Status</label>
        <select name="status" class="form-select">
          <option value="">All</option>
          <option <?= $status=='Paid'?'selected':'' ?>>Paid</option>
          <option <?= $status=='Pending'?'selected':'' ?>>Pending</option>
          <option <?= $status=='Overdue'?'selected':'' ?>>Overdue</option>
          <option <?= $status=='Partially Paid'?'selected':'' ?>>Partially Paid</option>
        </select>
      </div>

      <div class="col-md-3">
        <label class="form-label fw-bold">Search</label>
        <input type="text" name="search" class="form-control"
               placeholder="Code / Name / Meter..."
               value="<?= htmlspecialchars($search) ?>">
      </div>

      <div class="col-md-1 d-flex align-items-end">
        <button class="btn btn-dark w-100" style="border-radius: 12px; padding: 10px; font-weight: 600;">
          <i class="bi bi-search me-1"></i>Search
        </button>
      </div>

    </form>

  </div>
</div>

<!-- BILL TABLE -->
<div class="card-glass" style="padding: 0; overflow: hidden;">
  <div class="card-header" style="background: var(--accent); color: #fff; padding: 16px 24px; font-weight: 700; font-size: 1.1rem;">
    <i class="bi bi-files"></i> Bills List
  </div>

  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover table-bordered mb-0 small align-middle">
        <thead>
          <tr>
            <th>ID</th>
            <th>Customer</th>
            <th>Meter</th>
            <th>Utility</th>
            <th>Tariff</th>
            <th>Month</th>
            <th>Units</th>
            <th>Total</th>
            <th>Paid</th>
            <th>Due</th>
            <th>Status</th>
            <th style="width:180px">Actions</th>
          </tr>
        </thead>

        <tbody>
          <?php while ($b = $res->fetch_assoc()): ?>
          <tr>

            <td><span class="badge bg-secondary"><?= $b['id'] ?></span></td>

            <td><strong><?= $b['customer_code'] ?></strong><br><?= $b['full_name'] ?></td>
            <td><?= $b['meter_number'] ?></td>
            <td><span class="badge bg-primary"><?= $b['utility_name'] ?></span></td>

            <td><?= $b['tariff_name'] ?></td>
            <td><?= $b['billing_year'].'-'.$b['billing_month'] ?></td>
            <td><?= $b['units'] ?></td>

            <td><strong class="text-dark">Rs. <?= number_format($b['total_amount'],2) ?></strong></td>
            <td>Rs. <?= number_format($b['amount_paid'],2) ?></td>

            <td class="<?= $b['outstanding']>0?'text-danger fw-bold':'' ?>">
                Rs. <?= number_format($b['outstanding'],2) ?>
            </td>

            <td>
              <?php if ($b['status']=='Paid'): ?>
                <span class="badge bg-success">Paid</span>
              <?php elseif ($b['status']=='Overdue'): ?>
                <span class="badge bg-danger">Overdue</span>
              <?php elseif ($b['status']=='Partially Paid'): ?>
                <span class="badge bg-warning text-dark">Partial</span>
              <?php else: ?>
                <span class="badge bg-secondary">Pending</span>
              <?php endif; ?>
            </td>

            <!-- ACTION BUTTONS -->
            <td class="text-center">

              <!-- View -->
              <a href="bill_adv/view.php?id=<?= $b['id'] ?>" 
                 class="btn btn-sm btn-info text-white btn-action" title="View Bill Details">
                <i class="bi bi-eye-fill"></i>
              </a>

              <!-- PDF -->
              <a href="bill_adv/pdf.php?id=<?= $b['id'] ?>" 
                 class="btn btn-sm btn-secondary btn-action" title="Download PDF">
                <i class="bi bi-file-pdf-fill"></i>
              </a>

              <!-- Payment -->
              <a href="bill_adv/pay.php?id=<?= $b['id'] ?>" 
                 class="btn btn-sm btn-success btn-action" title="Add Payment">
                <i class="bi bi-cash-stack"></i>
              </a>

              <!-- Delete -->
              <button class="btn btn-sm btn-danger deleteBillBtn btn-action"
                      data-id="<?= $b['id'] ?>" title="Delete Bill">
                <i class="bi bi-trash-fill"></i>
              </button>

            </td>

          </tr>
          <?php endwhile; ?>
        </tbody>

      </table>
    </div>
  </div>
</div>

<script>
// DELETE CONFIRM
document.addEventListener("click", function(e){
    if(e.target.closest(".deleteBillBtn")){
        let id = e.target.closest(".deleteBillBtn").dataset.id;
        if(confirm("Are you sure you want to delete this bill?")){
            window.location = "bill_adv/delete.php?id=" + id;
        }
    }
});
</script>

<?php include 'footer.php'; ?>
