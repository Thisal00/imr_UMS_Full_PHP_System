<?php
require_once '../db.php';
require_once '../auth.php';
require_login();

$customer_id = (int)($_GET['id'] ?? 0);
if ($customer_id <= 0) die("Invalid Customer");

// Load customer
$customer = $mysqli->query("
    SELECT * FROM customers WHERE id=$customer_id
")->fetch_assoc();

if (!$customer) die("Customer Not Found");

// Load meters
$meters_rs = $mysqli->query("
    SELECT id FROM meters WHERE customer_id=$customer_id
");

$meter_ids = [];
while ($m = $meters_rs->fetch_assoc()) $meter_ids[] = $m['id'];
if (empty($meter_ids)) die("No meters assigned.");
$meter_list = implode(",", $meter_ids);

// Load bills
$bills = $mysqli->query("
    SELECT * FROM bills 
    WHERE customer_id=$customer_id OR meter_id IN ($meter_list)
    ORDER BY id DESC
");

// Load meter readings
$readings = $mysqli->query("
    SELECT * FROM meter_readings 
    WHERE meter_id IN ($meter_list)
    ORDER BY id DESC
");

// Load payments
$bill_ids = [];
foreach ($bills as $b) $bill_ids[] = $b['id'];

$payments = [];
if (!empty($bill_ids)) {
    $list = implode(",", $bill_ids);
    $payments = $mysqli->query("
        SELECT * FROM payments 
        WHERE bill_id IN ($list)
        ORDER BY id DESC
    ");
}

?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Customer History - <?= htmlspecialchars($customer['full_name']) ?></title>
<link rel="stylesheet" href="../assets/css/bootstrap.min.css">

<style>
body { background:#f5f6f7; padding:25px; font-family: 'Segoe UI', Tahoma, sans-serif; }
.report-box {
    max-width: 900px;
    background:#fff;
    margin:auto;
    padding:30px 35px;
    border-radius:8px;
    box-shadow:0 2px 8px rgba(0,0,0,0.08);
}
.header-line {
    border-bottom:2px solid #000;
    padding-bottom:12px;
    margin-bottom:20px;
}
.section-title {
    font-size:17px;
    font-weight:600;
    border-bottom:1px solid #ccc;
    padding-bottom:6px;
    margin-top:30px;
}
table th {
    background:#f8f9fa;
    font-weight:600;
}
.footer-note {
    text-align:center;
    font-size:12px;
    margin-top:25px;
    color:#555;
}
</style>
</head>

<body onload="window.print()">

<div class="report-box">

    <div class="header-line">
        <h3 class="fw-bold">Customer Utility History Report</h3>
        <div class="text-end">
            <strong>Date:</strong> <?= date("Y-m-d H:i") ?>
        </div>
    </div>

    <!-- CUSTOMER INFO -->
    <div class="section-title">Customer Information</div>
    <p>
        <strong><?= htmlspecialchars($customer['customer_code'].' - '.$customer['full_name']) ?></strong><br>
        <?= nl2br(htmlspecialchars($customer['address'] ?? '')) ?><br>
        Phone: <?= htmlspecialchars($customer['phone']) ?>
    </p>

    <!-- BILL HISTORY -->
    <div class="section-title">Bill History</div>
    <table class="table table-bordered table-sm">
        <tr>
            <th>ID</th>
            <th>Month</th>
            <th>Total</th>
            <th>Paid</th>
            <th>Balance</th>
            <th>Status</th>
        </tr>

        <?php foreach ($bills as $b): 
            $bill_id = $b['id'];
            $pay = $mysqli->query("SELECT COALESCE(SUM(amount),0) AS paid FROM payments WHERE bill_id=$bill_id")->fetch_assoc();
            $paid = $pay['paid'];
            $balance = $b['total_amount'] - $paid;
        ?>
        <tr>
            <td><?= $b['id'] ?></td>
            <td><?= $b['billing_year'].'-'.$b['billing_month'] ?></td>
            <td>Rs. <?= number_format($b['total_amount'],2) ?></td>
            <td>Rs. <?= number_format($paid,2) ?></td>
            <td>Rs. <?= number_format($balance,2) ?></td>
            <td>
                <?= $balance <= 0 ? '<span class="text-success fw-bold">Paid</span>' 
                                  : '<span class="text-warning fw-bold">Pending</span>' ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>

    <!-- PAYMENT HISTORY -->
    <div class="section-title">Payment History</div>
    <table class="table table-bordered table-sm">
        <tr>
            <th>ID</th>
            <th>Bill</th>
            <th>Amount</th>
            <th>Date</th>
            <th>Method</th>
            <th>Ref No</th>
        </tr>

        <?php foreach ($payments as $p): ?>
        <tr>
            <td><?= $p['id'] ?></td>
            <td><?= $p['bill_id'] ?></td>
            <td>Rs. <?= number_format($p['amount'],2) ?></td>
            <td><?= $p['payment_date'] ?></td>
            <td><?= $p['method'] ?></td>
            <td><?= $p['reference_no'] ?></td>
        </tr>
        <?php endforeach; ?>
    </table>

    <!-- METER READINGS -->
    <div class="section-title">Meter Readings</div>
    <table class="table table-bordered table-sm">
        <tr>
            <th>ID</th>
            <th>Meter</th>
            <th>Previous</th>
            <th>Current</th>
            <th>Units</th>
            <th>Date</th>
        </tr>

        <?php foreach ($readings as $r): ?>
        <tr>
            <td><?= $r['id'] ?></td>
            <td><?= $r['meter_id'] ?></td>
            <td><?= $r['previous_reading'] ?></td>
            <td><?= $r['current_reading'] ?></td>
            <td><?= $r['units_used'] ?></td>
            <td><?= $r['reading_date'] ?></td>
        </tr>
        <?php endforeach; ?>
    </table>

    <p class="footer-note">
        This report is generated by the <strong>Utility Management System</strong>.  
        You can save this page as a PDF using the browser print dialog.
    </p>

</div>

</body>
</html>
