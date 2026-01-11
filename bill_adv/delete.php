<?php
require_once '../db.php';
require_once '../auth.php';
require_login();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    die("Invalid bill ID");
}

// Optional: check if payments exist
$payRes = $mysqli->query("SELECT id FROM payments WHERE bill_id = {$id} LIMIT 1");
if ($payRes && $payRes->num_rows > 0) {
    die("Cannot delete bill with existing payments.");
}

$mysqli->query("DELETE FROM bills WHERE id = {$id} LIMIT 1");

header("Location: ../bills.php");
exit;
