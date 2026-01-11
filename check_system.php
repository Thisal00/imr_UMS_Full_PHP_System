<?php
session_start();
require 'db.php';

// Check database connection
echo "<h2>UMS Tariffs System - Diagnostics</h2>";
echo "<hr>";

// 1. Check DB connection
echo "<h3>1. Database Connection</h3>";
if ($mysqli) {
    echo " Database connected successfully<br>";
} else {
    echo " Database connection failed<br>";
    exit;
}

// 2. Check if tariffs table exists
echo "<h3>2. Tariffs Table Status</h3>";
$result = $mysqli->query("SHOW TABLES LIKE 'tariffs'");
if ($result && $result->num_rows > 0) {
    echo " Tariffs table EXISTS<br>";
    
    // Get table info
    $countResult = $mysqli->query("SELECT COUNT(*) as cnt FROM tariffs");
    $count = $countResult->fetch_assoc()['cnt'];
    echo "   Records: $count<br>";
} else {
    echo " Tariffs table NOT FOUND<br>";
    echo "<p><strong>To create it, visit:</strong> <a href='/UMS_Full_PHP_System/setup_tariffs.php'>/setup_tariffs.php</a></p>";
}

// 3. Check session
echo "<h3>3. Session Status</h3>";
if (isset($_SESSION['user_id'])) {
    echo " User logged in: " . htmlspecialchars($_SESSION['user_name'] ?? 'Unknown') . "<br>";
    echo "   Role: " . htmlspecialchars($_SESSION['user_role'] ?? 'Unknown') . "<br>";
    
    if ($_SESSION['user_role'] === 'admin') {
        echo " Admin role verified<br>";
        echo "<p><a href='/UMS_Full_PHP_System/tariffs.php' class='btn btn-primary'> Go to Tariffs Page</a></p>";
    } else {
        echo " Not admin. Only admins can access tariffs.<br>";
    }
} else {
    echo " Not logged in<br>";
    echo "<p><a href='/UMS_Full_PHP_System/index.php' class='btn btn-primary'> Go to Login</a></p>";
}

// 4. Check utilities table
echo "<h3>4. Utilities Table</h3>";
$utResult = $mysqli->query("SELECT COUNT(*) as cnt FROM utilities");
$utCount = $utResult->fetch_assoc()['cnt'];
echo " Utilities count: $utCount<br>";

// 5. Check header.php for tariffs link
echo "<h3>5. Navigation Menu</h3>";
$headerContent = file_get_contents('header.php');
if (strpos($headerContent, 'tariffs.php') !== false) {
    echo " Tariffs link found in header.php<br>";
} else {
    echo " Tariffs link NOT in header.php<br>";
}

echo "<hr>";
echo "<p><strong>Next Steps:</strong></p>";
echo "<ol>";
echo "<li>Make sure you are logged in as ADMIN</li>";
echo "<li>If tariffs table doesn't exist, visit /setup_tariffs.php</li>";
echo "<li>Click 'Tariffs' in navigation menu to access</li>";
echo "</ol>";
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>UMS Diagnostics</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { margin: 40px; background: #f5f5f5; }
        h2, h3 { color: #333; }
        .btn { padding: 10px 20px; }
    </style>
</head>
<body style="padding: 40px;">
</body>
</html>
