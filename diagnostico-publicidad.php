<?php
session_start();
include("./data/conexion.php");
include("./views/helpers/urlhelper.php");

echo "<h2>Diagnóstico de Rutas de Publicidad</h2>";
echo "<hr>";

// 1. Verificar carpetas
echo "<h3>1. Verificar Carpetas</h3>";
$baseDir = dirname(__FILE__);
$uploadDir = $baseDir . "/uploads/publicidad";
$imgDir = $baseDir . "/img/publicidad";

echo "<p><strong>Base Dir:</strong> " . htmlspecialchars($baseDir) . "</p>";
echo "<p><strong>Upload Dir (esperado):</strong> " . htmlspecialchars($uploadDir) . "</p>";
echo "<p><strong>Existe uploads/publicidad:</strong> " . (is_dir($uploadDir) ? "✅ SÍ" : "❌ NO") . "</p>";
echo "<p><strong>Permisos uploads/publicidad:</strong> " . (is_writable($uploadDir) ? "✅ Escribible" : "❌ No escribible") . "</p>";

echo "<p><strong>Img Dir (antiguo):</strong> " . htmlspecialchars($imgDir) . "</p>";
echo "<p><strong>Existe img/publicidad:</strong> " . (is_dir($imgDir) ? "✅ SÍ" : "❌ NO") . "</p>";

// 2. Verificar imágenes en BD
echo "<h3>2. Imágenes en Base de Datos</h3>";
$stmt = $con->prepare("SELECT id_pub, titulo, imagen FROM publicidad LIMIT 10");
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<p>❌ No hay publicidad en la BD</p>";
} else {
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>ID</th><th>Título</th><th>Ruta en BD</th><th>Existe archivo</th><th>URL generada</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        $rutaBD = $row['imagen'];
        $rutaFisica = $baseDir . "/" . $rutaBD;
        $existe = file_exists($rutaFisica) ? "✅ SÍ" : "❌ NO";
        $urlGenerada = imageUrl($rutaBD);
        
        echo "<tr>";
        echo "<td>" . $row['id_pub'] . "</td>";
        echo "<td>" . htmlspecialchars($row['titulo']) . "</td>";
        echo "<td>" . htmlspecialchars($rutaBD) . "</td>";
        echo "<td>" . $existe . "</td>";
        echo "<td><a href='" . htmlspecialchars($urlGenerada) . "' target='_blank'>" . htmlspecialchars($urlGenerada) . "</a></td>";
        echo "</tr>";
    }
    echo "</table>";
}

// 3. Listar archivos en uploads/publicidad
echo "<h3>3. Archivos en uploads/publicidad</h3>";
if (is_dir($uploadDir)) {
    $files = scandir($uploadDir);
    $files = array_filter($files, function($f) { return $f !== '.' && $f !== '..'; });
    
    if (empty($files)) {
        echo "<p>❌ La carpeta está vacía</p>";
    } else {
        echo "<p>✅ Encontrados " . count($files) . " archivos:</p>";
        echo "<ul>";
        foreach ($files as $file) {
            $fileSize = filesize($uploadDir . "/" . $file);
            echo "<li>" . htmlspecialchars($file) . " (" . number_format($fileSize / 1024, 2) . " KB)</li>";
        }
        echo "</ul>";
    }
} else {
    echo "<p>❌ La carpeta uploads/publicidad no existe</p>";
}

// 4. Listar archivos en img/publicidad (antiguo)
echo "<h3>4. Archivos en img/publicidad (antiguo)</h3>";
if (is_dir($imgDir)) {
    $files = scandir($imgDir);
    $files = array_filter($files, function($f) { return $f !== '.' && $f !== '..'; });
    
    if (empty($files)) {
        echo "<p>La carpeta está vacía</p>";
    } else {
        echo "<p>Encontrados " . count($files) . " archivos:</p>";
        echo "<ul>";
        foreach ($files as $file) {
            $fileSize = filesize($imgDir . "/" . $file);
            echo "<li>" . htmlspecialchars($file) . " (" . number_format($fileSize / 1024, 2) . " KB)</li>";
        }
        echo "</ul>";
    }
} else {
    echo "<p>La carpeta img/publicidad no existe (esto es normal si migraste todo)</p>";
}

// 5. Verificar serve-image.php
echo "<h3>5. Verificar serve-image.php</h3>";
$serveImagePath = $baseDir . "/serve-image.php";
echo "<p><strong>Existe serve-image.php:</strong> " . (file_exists($serveImagePath) ? "✅ SÍ" : "❌ NO") . "</p>";

// 6. Test de URL
echo "<h3>6. Test de URLs</h3>";
$testPaths = [
    "uploads/publicidad/test.webp",
    "img/publicidad/test.webp"
];

foreach ($testPaths as $path) {
    $url = imageUrl($path);
    echo "<p><strong>Path:</strong> " . htmlspecialchars($path) . "<br>";
    echo "<strong>URL:</strong> <a href='" . htmlspecialchars($url) . "' target='_blank'>" . htmlspecialchars($url) . "</a></p>";
}

echo "<hr>";
echo "<p><a href='javascript:history.back()'>Volver</a></p>";
?>
