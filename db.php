<?php
// Simple MySQLi connection
$host = 'localhost';
$user = 'root';
$pass = ''; // XAMPP default
$db   = 'ums_db';

$mysqli = new mysqli($host, $user, $pass, $db);
if ($mysqli->connect_error) {
    die('DB connection failed: ' . $mysqli->connect_error);
}
$mysqli->set_charset('utf8mb4');
?>
