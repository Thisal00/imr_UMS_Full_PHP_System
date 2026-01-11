<?php
require 'db.php';

echo "Utilities table columns:\n";
$result = $mysqli->query("DESCRIBE utilities");
while($row = $result->fetch_assoc()) {
    echo "- " . $row['Field'] . "\n";
}
?>
