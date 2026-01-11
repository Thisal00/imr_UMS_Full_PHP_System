<?php
header("Content-Type: application/json");
$dataFile = __DIR__ . "/users.json";

if (!file_exists($dataFile)) {
    echo json_encode([]);
    exit;
}

echo file_get_contents($dataFile);
?>