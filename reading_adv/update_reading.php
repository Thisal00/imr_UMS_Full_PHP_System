<?php
require_once '../db.php';
header('Content-Type: application/json');

$id   = (int)($_POST['id'] ?? 0);
$curr = isset($_POST['current_reading']) ? (float)$_POST['current_reading'] : -1;

if ($id === 0 || $curr < 0) {
    echo json_encode(['status' => 'error', 'msg' => 'Invalid data']);
    exit;
}

$q = $mysqli->query("SELECT previous_reading FROM meter_readings WHERE id = {$id} LIMIT 1");
if (!$q || $q->num_rows === 0) {
    echo json_encode(['status' => 'error', 'msg' => 'Reading not found']);
    exit;
}

$row  = $q->fetch_assoc();
$prev = (float)$row['previous_reading'];

if ($curr < $prev) {
    echo json_encode(['status' => 'error', 'msg' => 'Current reading cannot be less than previous reading']);
    exit;
}

$units = $curr - $prev;

$stmt = $mysqli->prepare("
    UPDATE meter_readings
    SET current_reading = ?, units_used = ?
    WHERE id = ?
");
$stmt->bind_param("ddi", $curr, $units, $id);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success']);
} else {
    echo json_encode(['status' => 'error', 'msg' => $mysqli->error]);
}
$stmt->close();
