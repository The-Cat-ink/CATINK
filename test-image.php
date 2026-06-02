<?php
/**
 * Test para verificar si serve-image.php encuentra las imágenes
 */

$isProduction = strpos($_SERVER['HTTP_HOST'], 'localhost') === false;

if ($isProduction) {
    $uploadsDir = dirname(__DIR__) . '/uploads/';
} else {
    $uploadsDir = __DIR__ . '/uploads/';
}

echo "<h2>Test de Imágenes</h2>";
echo "<pre>";

echo "Uploads Dir: $uploadsDir\n";
echo "Existe: " . (is_dir($uploadsDir) ? 'SÍ' : 'NO') . "\n\n";

// Listar archivos en noticias
$noticiasDir = $uploadsDir . 'noticias/';
echo "Noticias Dir: $noticiasDir\n";
echo "Existe: " . (is_dir($noticiasDir) ? 'SÍ' : 'NO') . "\n";

if (is_dir($noticiasDir)) {
    $files = scandir($noticiasDir);
    echo "Archivos encontrados: " . (count($files) - 2) . "\n";
    echo "Primeros 5 archivos:\n";
    $count = 0;
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            $fullPath = $noticiasDir . $file;
            $size = filesize($fullPath);
            echo "  - $file (" . number_format($size) . " bytes)\n";
            $count++;
            if ($count >= 5) break;
        }
    }
}

echo "\n=== TEST DE SERVE-IMAGE.PHP ===\n";

// Probar con el primer archivo encontrado
if (is_dir($noticiasDir)) {
    $files = scandir($noticiasDir);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            $testFile = 'uploads/noticias/' . $file;
            $testUrl = '/serve-image.php?file=' . urlencode($testFile);
            echo "Archivo de prueba: $file\n";
            echo "URL: $testUrl\n";
            echo "Ruta real: " . $uploadsDir . $testFile . "\n";
            echo "Existe: " . (file_exists($uploadsDir . $testFile) ? 'SÍ' : 'NO') . "\n";
            break;
        }
    }
}

echo "</pre>";
?>
