<?php
require_once '../db.php';
require_once '../auth.php';
require_login();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) die("Invalid bill ID");

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

if (!$bill) die("Bill not found");

// Simple print-friendly HTML (user can use browser Print -> Save as PDF)
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Bill #<?= htmlspecialchars($bill['id']) ?> - Printable</title>
  <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
  <style>
    body { padding: 20px; }
    .bill-box {
        max-width: 800px;
        margin: auto;
        border: 1px solid #ddd;
        padding: 20px;
    }
  </style>
</head>
<body onload="window.print()">

<div class="bill-box">
  <h3>Utility Bill</h3>
  <hr>
  <p>
    <strong>Bill #:</strong> <?= htmlspecialchars($bill['id']) ?><br>
    <strong>Utility:</strong> <?= htmlspecialchars($bill['utility_name']) ?><br>
    <strong>Billing Month:</strong> <?= htmlspecialchars($bill['billing_year'].'-'.$bill['billing_month']) ?><br>
  </p>

  <h5>Customer</h5>
  <p>
    <?= htmlspecialchars($bill['customer_code'].' - '.$bill['full_name']) ?><br>
    <?= nl2br(htmlspecialchars($bill['address'] ?? '')) ?><br>
    <?= htmlspecialchars($bill['phone'] ?? '') ?>
  </p>

  <h5>Meter &amp; Usage</h5>
  <p>
    <strong>Meter:</strong> <?= htmlspecialchars($bill['meter_number']) ?><br>
    <strong>Tariff:</strong> <?= htmlspecialchars($bill['tariff_name']) ?><br>
    <strong>Units:</strong> <?= htmlspecialchars($bill['units']) ?><br>
  </p>

  <h5>Amounts</h5>
  <p>
    <strong>Total:</strong> Rs. <?= number_format($bill['total_amount'], 2) ?><br>
    <strong>Paid:</strong> Rs. <?= number_format($bill['amount_paid'], 2) ?><br>
    <strong>Outstanding:</strong> Rs. <?= number_format($bill['outstanding'], 2) ?><br>
  </p>

</div>

</body>
</html>
