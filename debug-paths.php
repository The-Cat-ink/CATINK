<?php
/**
 * Script de diagnóstico para verificar rutas de imágenes
 * Acceder a: https://www.catink.com.mx/debug-paths.php
 */

echo "<h2>Diagnóstico de Rutas</h2>";
echo "<pre>";

echo "=== INFORMACIÓN DEL SERVIDOR ===\n";
echo "HTTP_HOST: " . $_SERVER['HTTP_HOST'] . "\n";
echo "__DIR__: " . __DIR__ . "\n";
echo "dirname(__DIR__): " . dirname(__DIR__) . "\n";
echo "dirname(dirname(__DIR__)): " . dirname(dirname(__DIR__)) . "\n";

echo "\n=== DETECCIÓN DE ENTORNO ===\n";
$isProduction = strpos($_SERVER['HTTP_HOST'], 'localhost') === false;
echo "Is Production: " . ($isProduction ? 'YES' : 'NO') . "\n";

echo "\n=== RUTAS DE UPLOADS ===\n";
$option1 = dirname(dirname(__DIR__)) . '/uploads/';
$option2 = dirname(__DIR__) . '/uploads/';
$option3 = __DIR__ . '/uploads/';

echo "Opción 1 (un nivel arriba): " . $option1 . "\n";
echo "  Existe: " . (is_dir($option1) ? 'SÍ' : 'NO') . "\n";

echo "Opción 2 (mismo nivel): " . $option2 . "\n";
echo "  Existe: " . (is_dir($option2) ? 'SÍ' : 'NO') . "\n";

echo "Opción 3 (dentro del proyecto): " . $option3 . "\n";
echo "  Existe: " . (is_dir($option3) ? 'SÍ' : 'NO') . "\n";

echo "\n=== ARCHIVOS DE PRUEBA ===\n";
if (is_dir($option1)) {
    echo "Contenido de $option1:\n";
    $files = scandir($option1);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            echo "  - $file\n";
        }
    }
}

echo "\n=== UBICACIÓN DE serve-image.php ===\n";
echo "Ruta: " . __FILE__ . "\n";
echo "Existe: " . (file_exists(__FILE__) ? 'SÍ' : 'NO') . "\n";

echo "</pre>";
?>
