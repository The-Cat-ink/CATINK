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

// Construir ruta real (fuera de public_html)
$realPath = dirname(__DIR__) . '/' . $file;

// Validar que el archivo existe
if (!file_exists($realPath)) {
    http_response_code(404);
    exit('File not found');
}

// Validar que está dentro de la carpeta uploads
$uploadsDir = dirname(__DIR__) . '/uploads/';
if (strpos(realpath($realPath), realpath($uploadsDir)) !== 0) {
    http_response_code(403);
    exit('Access denied');
}

// Obtener tipo MIME
$mimeType = mime_content_type($realPath);
if (!$mimeType) {
    $mimeType = 'application/octet-stream';
}

// Servir archivo
header('Content-Type: ' . $mimeType);
header('Content-Length: ' . filesize($realPath));
header('Cache-Control: public, max-age=31536000'); // Cache 1 año
readfile($realPath);
exit;
?>
