<?php
header("Content-Type: application/json");

$dataFile = __DIR__ . "/users.json";

if (!file_exists($dataFile)) {
    die(json_encode(["success" => false, "message" => "JSON not found"]));
}

$users = json_decode(file_get_contents($dataFile), true);
$deleteID = $_POST['id'] ?? '';

if ($deleteID === '') {
    die(json_encode(["success" => false, "message" => "Missing ID"]));
}

$users = array_filter($users, function($u) use ($deleteID) {
    return $u['id'] != $deleteID;
});

file_put_contents($dataFile, json_encode(array_values($users), JSON_PRETTY_PRINT));

echo json_encode(["success" => true, "message" => "User deleted"]);
?>