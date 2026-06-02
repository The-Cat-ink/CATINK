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
// En producción: /home/usuario/public_html/CATINK/uploads/
$uploadsDir = __DIR__ . '/uploads/';
$realPath = $uploadsDir . $file;

// Validar que el archivo existe
if (!file_exists($realPath)) {
    http_response_code(404);
    exit('File not found: ' . $realPath);
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
