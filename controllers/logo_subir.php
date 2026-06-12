<?php
include(__DIR__ . "/aclcontroller.php");
proteger('contenidos', 'crear');
include(__DIR__ . "/../data/conexion.php");
header('Content-Type: application/json');

if (!isset($_FILES['imagen']) || $_FILES['imagen']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['ok' => false, 'error' => 'No se recibió imagen.']);
    exit;
}

$file = $_FILES['imagen'];

if ($file['size'] > 5 * 1024 * 1024) {
    echo json_encode(['ok' => false, 'error' => 'Archivo demasiado grande (máximo 5MB).']);
    exit;
}

$mimeType = mime_content_type($file['tmp_name']);
if (!in_array($mimeType, ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'])) {
    echo json_encode(['ok' => false, 'error' => 'Formato no válido. Usa JPG, PNG, WEBP, GIF o SVG.']);
    exit;
}

$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'])) {
    echo json_encode(['ok' => false, 'error' => 'Extensión no válida.']);
    exit;
}

$nombre = trim($_POST['nombre'] ?? '');

$isLocal = strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false;
$dir = ($isLocal ? dirname(__DIR__) : dirname(dirname(__DIR__))) . '/uploads/logos/';
if (!is_dir($dir)) mkdir($dir, 0755, true);

$archivo = 'logo_' . time() . '_' . mt_rand(1000, 9999) . '.' . $ext;
$destino = $dir . $archivo;

if (move_uploaded_file($file['tmp_name'], $destino)) {
    $rutaRelativa = 'uploads/logos/' . $archivo;
    $stmt = $con->prepare("INSERT INTO logos_marcas (imagen, nombre) VALUES (?, ?)");
    $stmt->bind_param("ss", $rutaRelativa, $nombre);
    $stmt->execute();
    $id = $con->insert_id;
    echo json_encode(['ok' => true, 'id' => $id, 'imagen' => $rutaRelativa, 'nombre' => $nombre]);
} else {
    echo json_encode(['ok' => false, 'error' => 'Error al guardar el archivo.']);
}
