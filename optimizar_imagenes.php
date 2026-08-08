<?php
/**
 * Script de Optimización de Imágenes para CatInk.
 * Busca imágenes en uploads/noticias/ mayores a 150 KB,
 * las redimensiona si son demasiado grandes y las convierte a WebP (Calidad 90).
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);

$directorio = __DIR__ . '/uploads/noticias/';
if (!is_dir($directorio)) {
    die("Directorio no encontrado.");
}

$archivos = glob($directorio . '*.{jpg,jpeg,png}', GLOB_BRACE);
$totalOptimizados = 0;
$ahorroTotal = 0;

echo "<h1>Optimizando Imágenes</h1><ul>";

foreach ($archivos as $archivo) {
    $pesoOriginal = filesize($archivo);
    
    // Solo optimizamos archivos mayores a 150 KB para no perder tiempo en los que ya son pequeños
    if ($pesoOriginal < 150 * 1024) {
        continue;
    }
    
    $info = getimagesize($archivo);
    if ($info === false) continue;
    
    $anchoOriginal = $info[0];
    $altoOriginal = $info[1];
    $tipo = $info[2];
    
    // Determinar dimensiones máximas lógicas
    // crop1 (Cuadrada) -> Max 600px
    // crop2 (Banner horizontal ancho) -> Max 1200px
    // crop3 (Horizontal estándar) -> Max 800px
    $anchoMaximo = 1200;
    if (strpos($archivo, 'crop1') !== false) {
        $anchoMaximo = 600;
    } elseif (strpos($archivo, 'crop3') !== false) {
        $anchoMaximo = 800;
    }
    
    // Calcular nueva resolución
    if ($anchoOriginal > $anchoMaximo) {
        $nuevoAncho = $anchoMaximo;
        $nuevoAlto = intval(($altoOriginal / $anchoOriginal) * $nuevoAncho);
    } else {
        $nuevoAncho = $anchoOriginal;
        $nuevoAlto = $altoOriginal;
    }
    
    // Cargar imagen a memoria
    $imagenOriginal = null;
    if ($tipo == IMAGETYPE_JPEG) {
        $imagenOriginal = imagecreatefromjpeg($archivo);
    } elseif ($tipo == IMAGETYPE_PNG) {
        $imagenOriginal = imagecreatefrompng($archivo);
    }
    
    if (!$imagenOriginal) continue;
    
    // Crear lienzo redimensionado
    $imagenRedimensionada = imagecreatetruecolor($nuevoAncho, $nuevoAlto);
    
    // Mantener transparencia si es PNG
    if ($tipo == IMAGETYPE_PNG) {
        imagealphablending($imagenRedimensionada, false);
        imagesavealpha($imagenRedimensionada, true);
        $transparente = imagecolorallocatealpha($imagenRedimensionada, 255, 255, 255, 127);
        imagefilledrectangle($imagenRedimensionada, 0, 0, $nuevoAncho, $nuevoAlto, $transparente);
    }
    
    // Remuestrear (Resize con interpolación de calidad)
    imagecopyresampled($imagenRedimensionada, $imagenOriginal, 0, 0, 0, 0, $nuevoAncho, $nuevoAlto, $anchoOriginal, $altoOriginal);
    
    // Nombre del nuevo archivo WebP
    $nuevoArchivo = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $archivo);
    
    // Guardar en WebP a 90 de calidad (Excelente calidad, sin pérdida visible)
    imagewebp($imagenRedimensionada, $nuevoArchivo, 90);
    
    imagedestroy($imagenOriginal);
    imagedestroy($imagenRedimensionada);
    
    $pesoNuevo = filesize($nuevoArchivo);
    
    // Si la versión WebP redimensionada realmente es más pequeña, eliminamos el original
    if ($pesoNuevo < $pesoOriginal) {
        unlink($archivo);
        $ahorroTotal += ($pesoOriginal - $pesoNuevo);
        $totalOptimizados++;
        echo "<li>Optimizada: " . basename($archivo) . " | Antes: " . round($pesoOriginal/1024) . "KB | Ahora: " . round($pesoNuevo/1024) . "KB</li>\n";
    } else {
        // En un caso rarísimo donde el WebP pese más, eliminamos el WebP generado y conservamos el original.
        unlink($nuevoArchivo);
    }
}

echo "</ul>";
echo "<h2>Proceso terminado. Se optimizaron $totalOptimizados imágenes.</h2>";
echo "<h3>Ahorro total de espacio: " . round($ahorroTotal / 1024 / 1024, 2) . " MB</h3>";
?>
