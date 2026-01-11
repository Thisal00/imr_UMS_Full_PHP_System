<?php
require_once '../db.php';
require_once '../auth.php';
require_login();

$customer_id = (int)($_GET['id'] ?? 0);
if ($customer_id <= 0) die("Invalid Customer");

// Load customer
$customer = $mysqli->query("
    SELECT * FROM customers WHERE id = $customer_id
")->fetch_assoc();

if (!$customer) die("Customer Not Found");

// Load meters assigned to customer
$meters = $mysqli->query("
    SELECT * FROM meters WHERE customer_id = $customer_id
");

$meter_ids = [];
while ($m = $meters->fetch_assoc()) {
    $meter_ids[] = $m['id'];
}

if (empty($meter_ids)) {
    die("<b>This customer has no meters assigned!</b>");
}

$meter_list = implode(",", $meter_ids);

// Load Bills
$bills = $mysqli->query("
    SELECT *
    FROM bills
    WHERE customer_id = $customer_id
       OR meter_id IN ($meter_list)
    ORDER BY id DESC
");

// Load Meter Readings
$readings = $mysqli->query("
    SELECT *
    FROM meter_readings
    WHERE meter_id IN ($meter_list)
    ORDER BY id DESC
");

// Load Payments
$billRows = $mysqli->query("
    SELECT id FROM bills 
    WHERE customer_id = $customer_id
       OR meter_id IN ($meter_list)
");

$bill_ids = [];
while ($b = $billRows->fetch_assoc()) {
    $bill_ids[] = $b['id'];
}

$payments = [];
if (!empty($bill_ids)) {
    $bill_list = implode(",", $bill_ids);
    $payments = $mysqli->query("
        SELECT *
        FROM payments
        WHERE bill_id IN ($bill_list)
        ORDER BY id DESC
    ");
}

include '../header.php';
?>

<div class="card p-4">

<!-- BILL SECTION -->
<h4 class="mt-3 mb-3 text-primary fw-bold">
    <i class="bi bi-receipt-cutoff me-2"></i> Bill History
</h4>

<table class="table table-striped table-hover table-bordered align-middle shadow-sm">
    <thead class="table-primary">
        <tr>
            <th>#</th>
            <th><i class="bi bi-calendar-event"></i> Month</th>
            <th><i class="bi bi-cash-stack"></i> Total</th>
            <th><i class="bi bi-wallet2"></i> Paid</th>
            <th><i class="bi bi-clipboard-x"></i> Balance</th>
            <th><i class="bi bi-check2-circle"></i> Status</th>
        </tr>
    </thead>
    <tbody>

    <?php while ($b = $bills->fetch_assoc()): ?>

        <?php
            $bill_id = $b['id'];
            $p = $mysqli->query("
                SELECT COALESCE(SUM(amount),0) AS total_paid
                FROM payments WHERE bill_id = $bill_id
            ")->fetch_assoc();

            $total_paid = $p['total_paid'];
            $balance = $b['total_amount'] - $total_paid;
        ?>

        <tr>
            <td><span class="badge bg-secondary"><?= $b['id'] ?></span></td>

            <td><i class="bi bi-calendar-check"></i> <?= $b['billing_month'] ?>/<?= $b['billing_year'] ?></td>

            <td class="text-primary fw-semibold">Rs. <?= number_format($b['total_amount'],2) ?></td>

            <td class="text-success fw-semibold"><?= number_format($total_paid,2) ?></td>

            <td class="text-danger fw-semibold"><?= number_format($balance,2) ?></td>

            <td>
                <?php if ($balance <= 0): ?>
                    <span class="badge bg-success">
                        <i class="bi bi-check-circle-fill"></i> Paid
                    </span>
                <?php else: ?>
                    <span class="badge bg-warning text-dark">
                        <i class="bi bi-exclamation-circle-fill"></i> Pending
                    </span>
                <?php endif; ?>
            </td>
        </tr>

    <?php endwhile; ?>

    </tbody>
</table>



<!-- PAYMENT SECTION -->
<h4 class="mt-4 mb-3 text-success fw-bold">
    <i class="bi bi-credit-card-2-back-fill me-2"></i> Payment History
</h4>

<table class="table table-striped table-hover table-bordered shadow-sm">
    <thead class="table-success">
        <tr>
            <th>#</th>
            <th><i class="bi bi-receipt"></i> Bill ID</th>
            <th><i class="bi bi-cash-coin"></i> Amount</th>
            <th><i class="bi bi-calendar2-date"></i> Date</th>
            <th><i class="bi bi-credit-card"></i> Method</th>
            <th><i class="bi bi-file-earmark-text"></i> Reference</th>
        </tr>
    </thead>
    <tbody>

    <?php if (!empty($bill_ids)): ?>
        <?php while ($p = $payments->fetch_assoc()): ?>
            <tr>
                <td><span class="badge bg-dark"><?= $p['id'] ?></span></td>
                <td><?= $p['bill_id'] ?></td>
                <td class="fw-semibold text-success">Rs. <?= number_format($p['amount'],2) ?></td>
                <td><?= $p['payment_date'] ?></td>
                <td><span class="badge bg-info text-dark"><?= $p['method'] ?></span></td>
                <td><?= $p['reference_no'] ?></td>
            </tr>
        <?php endwhile; ?>
    <?php endif; ?>

    </tbody>
</table>



<!-- READING SECTION -->
<h4 class="mt-4 mb-3 text-warning fw-bold">
    <i class="bi bi-speedometer2 me-2"></i> Meter Readings
</h4>

<table class="table table-striped table-hover table-bordered shadow-sm">
    <thead class="table-warning">
        <tr>
            <th>#</th>
            <th><i class="bi bi-cpu"></i> Meter</th>
            <th><i class="bi bi-arrow-bar-up"></i> Previous</th>
            <th><i class="bi bi-arrow-bar-down"></i> Current</th>
            <th><i class="bi bi-lightning-fill"></i> Units</th>
            <th><i class="bi bi-calendar-week"></i> Date</th>
        </tr>
    </thead>
    <tbody>

        <?php while ($r = $readings->fetch_assoc()): ?>
        <tr>
            <td><span class="badge bg-secondary"><?= $r['id'] ?></span></td>
            <td><?= $r['meter_id'] ?></td>
            <td><?= $r['previous_reading'] ?></td>
            <td><?= $r['current_reading'] ?></td>
            <td class="fw-semibold text-danger"><?= $r['units_used'] ?></td>
            <td><?= $r['reading_date'] ?></td>
        </tr>
        <?php endwhile; ?>

    </tbody>
</table>

</div>
</div>
</table>

</div>

<?php include '../footer.php'; ?>
