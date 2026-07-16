<?php
header('Content-Type: text/html; charset=utf-8');
require_once(__DIR__ . "/data/conexion.php");

echo "<h2>CatInk - Ejecutor Temporal de Migraciones</h2>";

$sql = file_get_contents(__DIR__ . "/migrations/011_add_new_permissions_columns.sql");

echo "<p>Ejecutando consultas de migración de permisos...</p>";

if ($con->multi_query($sql)) {
    do {
        // Consumir todos los resultados de multi_query
        if ($result = $con->store_result()) {
            $result->free();
        }
    } while ($con->next_result());
    
    echo "<h3 style='color: green;'>✅ Migración de nuevos permisos ejecutada con éxito. Se añadieron las columnas y se actualizaron los superadministradores.</h3>";
} else {
    echo "<h3 style='color: red;'>❌ Error al ejecutar la migración: (" . $con->errno . ") " . $con->error . "</h3>";
}

echo "<p style='color: red; font-weight: bold;'>⚠️ IMPORTANTE: Elimina este archivo ('run_migration.php') de producción inmediatamente después de ejecutarlo por motivos de seguridad.</p>";
?>
