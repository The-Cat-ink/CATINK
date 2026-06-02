<?php
/**
 * Script para encontrar dónde están las imágenes
 * Acceder a: https://www.catink.com.mx/find-images.php
 */

echo "<h2>Búsqueda de Imágenes</h2>";
echo "<pre>";

$baseDir = dirname(__DIR__);
$publicHtmlDir = __DIR__;

echo "=== BUSCANDO CARPETAS uploads ===\n";

// Función recursiva para buscar carpetas
function findUploads($dir, $maxDepth = 3, $currentDepth = 0) {
    if ($currentDepth >= $maxDepth) return;
    
    try {
        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            
            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                if ($item === 'uploads') {
                    $files = scandir($path);
                    $fileCount = count($files) - 2; // Restar . y ..
                    echo "✓ Encontrada: $path\n";
                    echo "  Archivos: $fileCount\n";
                    if ($fileCount > 0) {
                        echo "  Contenido:\n";
                        foreach ($files as $f) {
                            if ($f !== '.' && $f !== '..') {
                                echo "    - $f\n";
                            }
                        }
                    }
                } else {
                    findUploads($path, $maxDepth, $currentDepth + 1);
                }
            }
        }
    } catch (Exception $e) {
        echo "Error en $dir: " . $e->getMessage() . "\n";
    }
}

findUploads($baseDir);

echo "\n=== VERIFICANDO RUTAS ESPECÍFICAS ===\n";

$paths = [
    '/home/u780114275/domains/catink.com.mx/uploads/',
    '/home/u780114275/domains/uploads/',
    '/home/u780114275/uploads/',
    dirname(__DIR__) . '/uploads/',
    __DIR__ . '/uploads/',
];

foreach ($paths as $path) {
    echo "\n$path\n";
    if (is_dir($path)) {
        echo "  ✓ Existe\n";
        $files = @scandir($path);
        if ($files) {
            $count = count($files) - 2;
            echo "  Archivos: $count\n";
            if ($count > 0 && $count <= 10) {
                foreach ($files as $f) {
                    if ($f !== '.' && $f !== '..') {
                        echo "    - $f\n";
                    }
                }
            }
        }
    } else {
        echo "  ✗ No existe\n";
    }
}

echo "\n=== INFORMACIÓN DE DIRECTORIOS ===\n";
echo "__DIR__: " . __DIR__ . "\n";
echo "dirname(__DIR__): " . dirname(__DIR__) . "\n";
echo "dirname(dirname(__DIR__)): " . dirname(dirname(__DIR__)) . "\n";

echo "</pre>";
?>
