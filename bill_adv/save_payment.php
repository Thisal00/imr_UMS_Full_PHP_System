<?php
require_once '../db.php';
require_once '../auth.php';
require_login();

$bill_id = (int)($_POST['bill_id'] ?? 0);
$amount  = (float)($_POST['amount'] ?? 0);
$method  = trim($_POST['method'] ?? 'Cash');
$ref_no  = trim($_POST['ref_no'] ?? '');

if ($bill_id <= 0 || $amount <= 0) {
    die("Invalid payment data");
}

// Insert payment
$stmt = $mysqli->prepare("
    INSERT INTO payments (bill_id, amount, method, ref_no, paid_date)
    VALUES (?,?,?,?, NOW())
");
$stmt->bind_param("idss", $bill_id, $amount, $method, $ref_no);
$stmt->execute();
$stmt->close();

// Update bill totals (you can also use a stored procedure here)
$mysqli->query("
    UPDATE bills b
    SET amount_paid = (
            SELECT COALESCE(SUM(p.amount),0)
            FROM payments p
            WHERE p.bill_id = b.id
        ),
        outstanding = GREATEST(total_amount - (
            SELECT COALESCE(SUM(p.amount),0)
            FROM payments p
            WHERE p.bill_id = b.id
        ), 0)
    WHERE b.id = {$bill_id}
");

// Update status
$bill = $mysqli->query("SELECT total_amount, amount_paid, due_date FROM bills WHERE id = {$bill_id}")->fetch_assoc();
$status = 'Pending';

if ($bill['amount_paid'] >= $bill['total_amount']) {
    $status = 'Paid';
} elseif ($bill['amount_paid'] > 0 && $bill['amount_paid'] < $bill['total_amount']) {
    $status = 'Partially Paid';
}

$today = date('Y-m-d');
if ($status !== 'Paid' && !empty($bill['due_date']) && $today > $bill['due_date']) {
    $status = 'Overdue';
}

$st = $mysqli->prepare("UPDATE bills SET status = ? WHERE id = ?");
$st->bind_param("si", $status, $bill_id);
$st->execute();
$st->close();

header("Location: view.php?id={$bill_id}");
exit;
