<?php
/**
 * Script de diagnóstico para problemas de imágenes
 * Accede a: /diagnostico_imagenes.php
 */

// Verificar permisos
echo "<h2>Diagnóstico de Imágenes</h2>";

$carpetas = [
    'img/avatares/',
    'img/editores/',
    'img/noticias/',
    'img/'
];

echo "<h3>Estado de carpetas:</h3>";
foreach ($carpetas as $carpeta) {
    $ruta = __DIR__ . '/' . $carpeta;
    $existe = is_dir($ruta);
    $escribible = is_writable($ruta);
    $permisos = substr(sprintf('%o', fileperms($ruta)), -4);
    
    echo "<p>";
    echo "📁 <strong>$carpeta</strong><br>";
    echo "Existe: " . ($existe ? "✅ Sí" : "❌ No") . "<br>";
    echo "Escribible: " . ($escribible ? "✅ Sí" : "❌ No") . "<br>";
    echo "Permisos: $permisos<br>";
    echo "Ruta absoluta: $ruta<br>";
    echo "</p>";
}

// Verificar archivos de ejemplo
echo "<h3>Archivos en img/noticias/:</h3>";
$dir = __DIR__ . '/img/noticias/';
if (is_dir($dir)) {
    $files = scandir($dir);
    echo "<ul>";
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            $size = filesize($dir . $file);
            echo "<li>$file (${size} bytes)</li>";
        }
    }
    echo "</ul>";
} else {
    echo "❌ Carpeta no existe";
}

echo "<h3>Archivos en img/avatares/:</h3>";
$dir = __DIR__ . '/img/avatares/';
if (is_dir($dir)) {
    $files = scandir($dir);
    echo "<ul>";
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            $size = filesize($dir . $file);
            echo "<li>$file (${size} bytes)</li>";
        }
    }
    echo "</ul>";
} else {
    echo "❌ Carpeta no existe";
}

// Verificar BD
echo "<h3>Imágenes en BD:</h3>";
require_once(__DIR__ . '/data/conexion.php');

$stmt = $con->prepare("SELECT id, crop1, crop2, crop3 FROM noticias LIMIT 5");
$stmt->execute();
$result = $stmt->get_result();

echo "<table border='1' cellpadding='10'>";
echo "<tr><th>ID</th><th>Crop1</th><th>Crop2</th><th>Crop3</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $row['id'] . "</td>";
    echo "<td>" . ($row['crop1'] ? "✅ " . htmlspecialchars($row['crop1']) : "❌ Vacío") . "</td>";
    echo "<td>" . ($row['crop2'] ? "✅ " . htmlspecialchars($row['crop2']) : "❌ Vacío") . "</td>";
    echo "<td>" . ($row['crop3'] ? "✅ " . htmlspecialchars($row['crop3']) : "❌ Vacío") . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h3>Avatares en BD:</h3>";
$stmt = $con->prepare("SELECT id_avatar, imagen FROM avatares_perfil LIMIT 5");
$stmt->execute();
$result = $stmt->get_result();

echo "<table border='1' cellpadding='10'>";
echo "<tr><th>ID</th><th>Imagen</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $row['id_avatar'] . "</td>";
    echo "<td>" . ($row['imagen'] ? "✅ " . htmlspecialchars($row['imagen']) : "❌ Vacío") . "</td>";
    echo "</tr>";
}
echo "</table>";

?>
