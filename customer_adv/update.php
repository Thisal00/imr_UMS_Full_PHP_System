<?php
require_once '../db.php';
require_once '../auth.php';
require_login();

header('Content-Type: application/json; charset=utf-8');

$id    = (int)($_POST['id'] ?? 0);
$code  = trim($_POST['customer_code'] ?? '');
$name  = trim($_POST['full_name'] ?? '');
$type  = trim($_POST['type'] ?? 'Household');
$phone = trim($_POST['phone'] ?? '');
$email = trim($_POST['email'] ?? '');

if ($id <= 0 || $code === '' || $name === '') {
    echo json_encode([
        "status"  => "error",
        "message" => "Customer code and name are required."
    ]);
    exit;
}

// Optional: simple email validation
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        "status"  => "error",
        "message" => "Invalid email format."
    ]);
    exit;
}

// Prevent duplicate code (except same record)
$stmt = $mysqli->prepare("SELECT id FROM customers WHERE customer_code = ? AND id != ? LIMIT 1");
$stmt->bind_param("si", $code, $id);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    echo json_encode([
        "status"  => "error",
        "message" => "Customer code already exists."
    ]);
    $stmt->close();
    exit;
}
$stmt->close();

$stmt = $mysqli->prepare("
    UPDATE customers
       SET customer_code = ?, full_name = ?, type = ?, phone = ?, email = ?
     WHERE id = ?
");
$stmt->bind_param("sssssi", $code, $name, $type, $phone, $email, $id);

if ($stmt->execute()) {
    echo json_encode([
        "status"  => "success",
        "message" => "Customer updated successfully."
    ]);
} else {
    echo json_encode([
        "status"  => "error",
        "message" => "Database error: " . $mysqli->error
    ]);
}
$stmt->close();
