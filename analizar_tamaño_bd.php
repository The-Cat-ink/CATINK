<?php
/**
 * Script para analizar el tamaño de datos en la BD
 * Accede a: /analizar_tamaño_bd.php
 */

require_once(__DIR__ . '/data/conexion.php');

echo "<h2>Análisis de Tamaño en Base de Datos</h2>";

// ============================
// TAMAÑO TOTAL DE LA BD
// ============================
echo "<h3>Tamaño total de la base de datos:</h3>";
$dbname = 'u780114275_cat_ink';
$stmt = $con->prepare("
    SELECT 
        ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS tamaño_mb
    FROM information_schema.TABLES
    WHERE table_schema = ?
");
$stmt->bind_param("s", $dbname);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
echo "<p><strong>Tamaño total:</strong> " . $result['tamaño_mb'] . " MB</p>";

// ============================
// TAMAÑO POR TABLA
// ============================
echo "<h3>Tamaño por tabla:</h3>";
$stmt = $con->prepare("
    SELECT 
        table_name,
        ROUND(data_length / 1024 / 1024, 2) AS data_mb,
        ROUND(index_length / 1024 / 1024, 2) AS index_mb,
        ROUND((data_length + index_length) / 1024 / 1024, 2) AS total_mb,
        table_rows
    FROM information_schema.TABLES
    WHERE table_schema = ?
    ORDER BY (data_length + index_length) DESC
");
$stmt->bind_param("s", $dbname);
$stmt->execute();
$result = $stmt->get_result();

echo "<table border='1' cellpadding='10'>";
echo "<tr><th>Tabla</th><th>Datos (MB)</th><th>Índices (MB)</th><th>Total (MB)</th><th>Filas</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $row['table_name'] . "</td>";
    echo "<td>" . $row['data_mb'] . "</td>";
    echo "<td>" . $row['index_mb'] . "</td>";
    echo "<td><strong>" . $row['total_mb'] . "</strong></td>";
    echo "<td>" . $row['table_rows'] . "</td>";
    echo "</tr>";
}
echo "</table>";

// ============================
// ANÁLISIS DE NOTICIAS
// ============================
echo "<h3>Análisis de noticias:</h3>";

// Tamaño promedio por noticia
$stmt = $con->prepare("
    SELECT 
        COUNT(*) as total_noticias,
        ROUND(AVG(CHAR_LENGTH(titulo)), 2) as avg_titulo_bytes,
        ROUND(AVG(CHAR_LENGTH(descripcion)), 2) as avg_descripcion_bytes,
        ROUND(AVG(CHAR_LENGTH(contenido)), 2) as avg_contenido_bytes,
        ROUND(AVG(CHAR_LENGTH(crop1)), 2) as avg_crop1_bytes,
        ROUND(AVG(CHAR_LENGTH(crop2)), 2) as avg_crop2_bytes,
        ROUND(AVG(CHAR_LENGTH(crop3)), 2) as avg_crop3_bytes
    FROM noticias
");
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

echo "<table border='1' cellpadding='10'>";
echo "<tr><th>Campo</th><th>Tamaño Promedio (bytes)</th></tr>";
echo "<tr><td>Título</td><td>" . $result['avg_titulo_bytes'] . "</td></tr>";
echo "<tr><td>Descripción</td><td>" . $result['avg_descripcion_bytes'] . "</td></tr>";
echo "<tr><td>Contenido</td><td>" . $result['avg_contenido_bytes'] . "</td></tr>";
echo "<tr><td>Crop1 (ruta)</td><td>" . $result['avg_crop1_bytes'] . "</td></tr>";
echo "<tr><td>Crop2 (ruta)</td><td>" . $result['avg_crop2_bytes'] . "</td></tr>";
echo "<tr><td>Crop3 (ruta)</td><td>" . $result['avg_crop3_bytes'] . "</td></tr>";
echo "</table>";

$total_avg = $result['avg_titulo_bytes'] + $result['avg_descripcion_bytes'] + 
             $result['avg_contenido_bytes'] + $result['avg_crop1_bytes'] + 
             $result['avg_crop2_bytes'] + $result['avg_crop3_bytes'];

echo "<p><strong>Tamaño promedio por noticia en BD:</strong> " . round($total_avg / 1024, 2) . " KB</p>";
echo "<p><strong>Total noticias:</strong> " . $result['total_noticias'] . "</p>";
echo "<p><strong>Espacio total en noticias:</strong> " . round(($total_avg * $result['total_noticias']) / 1024 / 1024, 2) . " MB</p>";

// ============================
// ANÁLISIS DE COMENTARIOS
// ============================
echo "<h3>Análisis de comentarios:</h3>";

$stmt = $con->prepare("
    SELECT 
        COUNT(*) as total_comentarios,
        ROUND(AVG(CHAR_LENGTH(contenido)), 2) as avg_contenido_bytes
    FROM comentarios
");
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

echo "<p><strong>Total comentarios:</strong> " . $result['total_comentarios'] . "</p>";
echo "<p><strong>Tamaño promedio por comentario:</strong> " . round($result['avg_contenido_bytes'] / 1024, 2) . " KB</p>";
echo "<p><strong>Espacio total en comentarios:</strong> " . round(($result['avg_contenido_bytes'] * $result['total_comentarios']) / 1024 / 1024, 2) . " MB</p>";

?>
