<?php
header('Content-Type: text/html; charset=utf-8');
require_once(__DIR__ . "/data/conexion.php");

echo "<h2>CatInk - Ejecutor Temporal de Migraciones</h2>";

// Consulta de migración de hoy
$sql = "ALTER TABLE `noticias` ADD FULLTEXT INDEX `idx_busqueda_rapida` (`titulo`, `descripcion`, `contenido`)";

echo "<p>Ejecutando: <code>$sql</code>...</p>";

if ($con->query($sql)) {
    echo "<h3 style='color: green;'>✅ Migración ejecutada con éxito. El índice FULLTEXT se ha creado correctamente.</h3>";
} else {
    // Si ya existe, es probable que retorne un error de duplicado (código de error 1061 o similar)
    if ($con->errno == 1061) {
        echo "<h3 style='color: orange;'>⚠️ El índice FULLTEXT ya existe en la base de datos de producción. No es necesario volver a crearlo.</h3>";
    } else {
        echo "<h3 style='color: red;'>❌ Error al ejecutar la migración: (" . $con->errno . ") " . $con->error . "</h3>";
    }
}

echo "<p style='color: red; font-weight: bold;'>⚠️ IMPORTANTE: Elimina este archivo ('run_migration.php') de producción inmediatamente después de ejecutarlo por motivos de seguridad.</p>";
?>
