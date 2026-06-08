<?php
include(__DIR__ . "/conexion.php");
$con->query("ALTER TABLE videos ADD COLUMN orden INT DEFAULT 0;");
echo "Columna orden añadida\n";
