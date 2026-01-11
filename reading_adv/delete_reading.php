<?php
require_once "../db.php";
require_once "../auth.php";
require_login();

header("Content-Type: application/json");

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    echo json_encode(["status" => "error", "message" => "Invalid ID"]);
    exit;
}

try {
    // Delete bill first (to avoid FK errors)
    $mysqli->query("DELETE FROM bills WHERE reading_id = $id");

    // Delete reading
    $mysqli->query("DELETE FROM meter_readings WHERE id = $id");

    echo json_encode(["status" => "success"]);
} 
catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
