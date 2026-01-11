<?php
header("Content-Type: application/json");

$dataFile = __DIR__ . "/users.json";

if (!file_exists($dataFile)) {
    file_put_contents($dataFile, "[]");
}

$users = json_decode(file_get_contents($dataFile), true);

$newUser = [
    "id"     => $_POST['id'] ?? '',
    "name"   => $_POST['name'] ?? '',
    "email"  => $_POST['email'] ?? '',
    "role"   => $_POST['role'] ?? '',
    "status" => $_POST['status'] ?? '',
    "updated_at" => date("Y-m-d H:i:s")
];

$users = array_filter($users, function($u) use ($newUser) {
    return $u['id'] != $newUser['id'];
});

$users[] = $newUser;

file_put_contents($dataFile, json_encode(array_values($users), JSON_PRETTY_PRINT));

echo json_encode(["success" => true, "message" => "User saved"]);
?>