<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require '../db.php';
require '../auth.php';

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$id = intval($_GET['id'] ?? 0);

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'Invalid ID']);
    exit;
}

$result = $mysqli->query("SELECT * FROM tariffs WHERE id = $id");

if (!$result || $result->num_rows == 0) {
    echo json_encode(['success' => false, 'message' => 'Tariff not found']);
    exit;
}

$tariff = $result->fetch_assoc();

echo json_encode([
    'success' => true,
    'data' => $tariff
]);

$mysqli->close();
?>
