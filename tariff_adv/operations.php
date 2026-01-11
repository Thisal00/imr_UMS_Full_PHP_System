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

$action = $_POST['action'] ?? '';

if ($action == 'add') {
    $utility_id = intval($_POST['utility_id'] ?? 0);
    $name = $_POST['name'] ?? '';
    $price_per_unit = floatval($_POST['price_per_unit'] ?? 0);
    $fixed_charge = floatval($_POST['fixed_charge'] ?? 0);
    $is_active = isset($_POST['is_active']) && $_POST['is_active'] == '1' ? 1 : 0;

    if (!$utility_id || !$name) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }

    $stmt = $mysqli->prepare("INSERT INTO tariffs (utility_id, name, price_per_unit, fixed_charge, is_active, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("isddi", $utility_id, $name, $price_per_unit, $fixed_charge, $is_active);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Tariff added successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => $stmt->error]);
    }
    $stmt->close();
}

elseif ($action == 'edit') {
    $id = intval($_POST['id'] ?? 0);
    $utility_id = intval($_POST['utility_id'] ?? 0);
    $name = $_POST['name'] ?? '';
    $price_per_unit = floatval($_POST['price_per_unit'] ?? 0);
    $fixed_charge = floatval($_POST['fixed_charge'] ?? 0);
    $is_active = isset($_POST['is_active']) && $_POST['is_active'] == '1' ? 1 : 0;

    if (!$id || !$utility_id || !$name) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }

    $stmt = $mysqli->prepare("UPDATE tariffs SET utility_id = ?, name = ?, price_per_unit = ?, fixed_charge = ?, is_active = ? WHERE id = ?");
    $stmt->bind_param("isddii", $utility_id, $name, $price_per_unit, $fixed_charge, $is_active, $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Tariff updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => $stmt->error]);
    }
    $stmt->close();
}

elseif ($action == 'copy') {
    $id = intval($_POST['id'] ?? 0);
    $new_name = $_POST['name'] ?? '';

    if (!$id || !$new_name) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }

    // Get the original tariff
    $result = $mysqli->query("SELECT * FROM tariffs WHERE id = $id");
    if (!$result || $result->num_rows == 0) {
        echo json_encode(['success' => false, 'message' => 'Tariff not found']);
        exit;
    }

    $tariff = $result->fetch_assoc();

    // Insert copy
    $stmt = $mysqli->prepare("INSERT INTO tariffs (utility_id, name, price_per_unit, fixed_charge, is_active, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    $utility_id = $tariff['utility_id'];
    $price_per_unit = $tariff['price_per_unit'];
    $fixed_charge = $tariff['fixed_charge'];
    $is_active = $tariff['is_active'];
    
    $stmt->bind_param("isddi", $utility_id, $new_name, $price_per_unit, $fixed_charge, $is_active);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Tariff copied successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => $stmt->error]);
    }
    $stmt->close();
}

elseif ($action == 'delete') {
    $id = intval($_POST['id'] ?? 0);

    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'Invalid ID']);
        exit;
    }

    $stmt = $mysqli->prepare("DELETE FROM tariffs WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Tariff deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => $stmt->error]);
    }
    $stmt->close();
}

else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

$mysqli->close();
?>
