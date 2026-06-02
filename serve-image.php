<?php
/**
 * Script para servir imágenes desde carpeta fuera de public_html
 * Uso: /serve-image.php?file=uploads/noticias/noticia_1_crop1_1234567890.png
 */

// Obtener archivo solicitado
$file = isset($_GET['file']) ? $_GET['file'] : '';

// Validar que no sea path traversal
if (empty($file) || strpos($file, '..') !== false || strpos($file, '/') === 0) {
    http_response_code(400);
    exit('Invalid file');
}

// Construir ruta real
// En local: c:\xampp\htdocs\CATINK\uploads\
// En producción Hostinger: /home/usuario/uploads/ (fuera de public_html)
// Detectar si estamos en producción o local
$isProduction = strpos($_SERVER['HTTP_HOST'], 'localhost') === false;

$uploadsDir = null;

if ($isProduction) {
    // En producción Hostinger con estructura:
    // /home/u780114275/domains/catink.com.mx/public_html (aquí está serve-image.php)
    // /home/u780114275/domains/catink.com.mx/uploads/ (aquí están las imágenes)
    // Intentar múltiples rutas en orden de probabilidad
    $option1 = dirname(__DIR__) . '/uploads/';  // /home/u780114275/domains/catink.com.mx/uploads/
    $option2 = dirname(dirname(__DIR__)) . '/uploads/';  // /home/u780114275/domains/uploads/
    $option3 = __DIR__ . '/uploads/';  // /home/u780114275/domains/catink.com.mx/public_html/uploads/
    
    if (is_dir($option1) && count(scandir($option1)) > 2) {
        $uploadsDir = $option1;
    } elseif (is_dir($option2) && count(scandir($option2)) > 2) {
        $uploadsDir = $option2;
    } elseif (is_dir($option3) && count(scandir($option3)) > 2) {
        $uploadsDir = $option3;
    } else {
        // Si ninguna tiene archivos, usar la opción 1 (más probable)
        $uploadsDir = $option1;
    }
} else {
    // En local, está dentro del proyecto
    $uploadsDir = __DIR__ . '/uploads/';
}

$realPath = $uploadsDir . $file;

// Validar que el archivo existe
if (!file_exists($realPath)) {
    http_response_code(404);
    // Log para debugging
    $debugInfo = "Image not found\n";
    $debugInfo .= "Requested file: " . $file . "\n";
    $debugInfo .= "Real path: " . $realPath . "\n";
    $debugInfo .= "Uploads dir: " . $uploadsDir . "\n";
    $debugInfo .= "Is production: " . ($isProduction ? 'yes' : 'no') . "\n";
    $debugInfo .= "__DIR__: " . __DIR__ . "\n";
    $debugInfo .= "dirname(__DIR__): " . dirname(__DIR__) . "\n";
    error_log($debugInfo);
    exit('File not found');
}

// Validar que está dentro de la carpeta uploads
if (strpos(realpath($realPath), realpath($uploadsDir)) !== 0) {
    http_response_code(403);
    exit('Access denied');
}

// Obtener tipo MIME por extensión
$ext = strtolower(pathinfo($realPath, PATHINFO_EXTENSION));
$mimeTypes = [
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'gif' => 'image/gif',
    'webp' => 'image/webp',
    'svg' => 'image/svg+xml',
    'ico' => 'image/x-icon'
];
$mimeType = $mimeTypes[$ext] ?? 'application/octet-stream';

// Servir archivo
header('Content-Type: ' . $mimeType);
header('Content-Length: ' . filesize($realPath));
header('Cache-Control: public, max-age=31536000'); // Cache 1 año
readfile($realPath);
exit;
?>
