<?php
session_start();
require 'db.php';

echo "<!DOCTYPE html>";
echo "<html><head><meta charset='utf-8'><title>Debug</title></head><body style='padding:30px; font-family:arial;'>";

echo "<h2>🔍 Session Debug Info</h2>";
echo "<hr>";

echo "<h3>Your Session Variables:</h3>";
echo "<pre>";
echo "user_id: " . ($_SESSION['user_id'] ?? 'NOT SET') . "\n";
echo "user_name: " . ($_SESSION['user_name'] ?? 'NOT SET') . "\n";
echo "user_role: " . ($_SESSION['user_role'] ?? 'NOT SET') . "\n";
echo "email: " . ($_SESSION['email'] ?? 'NOT SET') . "\n";
echo "</pre>";

echo "<h3>Database Status:</h3>";

// Check tariffs table
$check = $mysqli->query("SHOW TABLES LIKE 'tariffs'");
if ($check && $check->num_rows > 0) {
    echo "✅ Tariffs table EXISTS<br>";
    $cnt = $mysqli->query("SELECT COUNT(*) as c FROM tariffs")->fetch_assoc();
    echo "   Records: " . $cnt['c'] . "<br>";
} else {
    echo "❌ Tariffs table MISSING<br>";
}

echo "<hr>";
echo "<h3>What To Do:</h3>";

if (!isset($_SESSION['user_id'])) {
    echo "❌ NOT LOGGED IN<br>";
    echo "<a href='index.php'>👉 GO TO LOGIN</a><br>";
} else if ($_SESSION['user_role'] !== 'admin') {
    echo "❌ NOT ADMIN (Your role: " . $_SESSION['user_role'] . ")<br>";
    echo "⚠️ Only admins can access Tariffs<br>";
} else {
    echo "✅ YOU ARE LOGGED IN AS ADMIN<br>";
    echo "<br>";
    echo "<a href='tariffs.php' style='padding:10px 20px; background:#007bff; color:white; text-decoration:none; border-radius:5px;'>👉 OPEN TARIFFS PAGE</a><br><br>";
}

echo "</body></html>";
?>
