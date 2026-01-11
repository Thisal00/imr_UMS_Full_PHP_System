<?php
require_once '../db.php';
require_once '../auth.php';
require_login();

header('Content-Type: application/json; charset=utf-8');

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    echo json_encode(["status" => "error", "message" => "Invalid ID"]);
    exit;
}

// Check if customer has meters
$check = $mysqli->prepare("SELECT id FROM meters WHERE customer_id = ? LIMIT 1");
$check->bind_param("i", $id);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    echo json_encode([
        "status"  => "error",
        "message" => "Customer has meters. Cannot delete."
    ]);
    $check->close();
    exit;
}
$check->close();

$stmt = $mysqli->prepare("DELETE FROM customers WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    echo json_encode([
        "status"  => "success",
        "message" => "Customer deleted successfully."
    ]);
} else {
    echo json_encode([
        "status"  => "error",
        "message" => "Database error: " . $mysqli->error
    ]);
}
$stmt->close();
