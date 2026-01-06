<?php
require_once '../db.php';
require_once '../auth.php';
require_login();

header('Content-Type: application/json; charset=utf-8');

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    echo json_encode(["status"=>"error", "message"=>"Invalid meter ID"]);
    exit;
}

// prevent delete if readings exist
$chk = $mysqli->prepare("SELECT id FROM readings WHERE meter_id = ? LIMIT 1");
$chk->bind_param("i", $id);
$chk->execute();
$chk->store_result();
if ($chk->num_rows > 0) {
    echo json_encode([
        "status"=>"error",
        "message"=>"Meter has readings. Cannot delete."
    ]);
    $chk->close();
    exit;
}
$chk->close();

$stmt = $mysqli->prepare("DELETE FROM meters WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    echo json_encode([
        "status"=>"success",
        "message"=>"Meter deleted successfully."
    ]);
} else {
    echo json_encode([
        "status"=>"error",
        "message"=>"Database error: ".$mysqli->error
    ]);
}
$stmt->close();
