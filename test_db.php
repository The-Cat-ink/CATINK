<?php
include("data/conexion.php");
echo "DB Name in env: " . env('DB_NAME') . "\n";
echo "DB Name in connection: " . $dbname . "\n";
$res = $con->query("SELECT DATABASE()");
$row = $res->fetch_row();
echo "Actual DB in use: " . $row[0] . "\n";
?>
