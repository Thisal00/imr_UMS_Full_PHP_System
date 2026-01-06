<?php
require_once '../db.php';
require_once '../auth.php';
require_login();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    die("Invalid payment ID");
}

// find bill_id
$pay = $mysqli->query("SELECT bill_id FROM payments WHERE id=$id LIMIT 1")->fetch_assoc();
if (!$pay) {
    die("Payment not found");
}
$bill_id = (int)$pay['bill_id'];

// delete payment
$mysqli->query("DELETE FROM payments WHERE id=$id LIMIT 1");

// recalc bill totals
$mysqli->query("
    UPDATE bills b
    SET amount_paid = (
        SELECT COALESCE(SUM(p.amount),0)
        FROM payments p
        WHERE p.bill_id = b.id
    ),
    outstanding = GREATEST(
        b.total_amount - (
            SELECT COALESCE(SUM(p.amount),0)
            FROM payments p
            WHERE p.bill_id = b.id
        ), 0
    )
    WHERE b.id = {$bill_id}
");

// update status
$bill = $mysqli->query("SELECT total_amount, amount_paid, due_date FROM bills WHERE id=$bill_id")->fetch_assoc();

$status = 'Pending';
if ($bill['amount_paid'] >= $bill['total_amount']) {
    $status = 'Paid';
} elseif ($bill['amount_paid'] > 0) {
    $status = 'Partially Paid';
}

$today = date('Y-m-d');
if ($status !== 'Paid' && !empty($bill['due_date']) && $today > $bill['due_date']) {
    $status = 'Overdue';
}

$mysqli->query("UPDATE bills SET status='$status' WHERE id=$bill_id");

header("Location: ../payments.php");
exit;
