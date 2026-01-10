<?php
require_once '../db.php';

header('Content-Type: application/json');

$utility_id = (int)($_GET['utility_id'] ?? 0);

if ($utility_id === 0) {
    echo json_encode([]);
    exit;
}

$q = $mysqli->query("
    SELECT id,
           CONCAT(name, ' (Fixed: ', FORMAT(fixed_charge, 2), ')') AS tariff_name
    FROM tariffs
    WHERE utility_id = {$utility_id}
      AND is_active = 1
    ORDER BY name
");

$data = [];
if ($q) {
    while ($row = $q->fetch_assoc()) {
        $data[] = $row;
    }
}

echo json_encode($data);
