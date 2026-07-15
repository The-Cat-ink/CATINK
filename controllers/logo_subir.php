<?php
include(__DIR__ . "/aclcontroller.php");
proteger('contenidos', 'crear');
include(__DIR__ . "/../data/conexion.php");
require_once(__DIR__ . "/../views/helpers/activity_log.php");
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

// Fecha+hora de expiración (opcional) — se espera "YYYY-MM-DD HH:MM:SS" desde el JS
$fechaRaw = trim($_POST['fecha_expiracion'] ?? '');
$fechaExp = null;
if ($fechaRaw !== '' && preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $fechaRaw)) {
    $ts = strtotime($fechaRaw);
    if ($ts && $ts > time()) {
        $fechaExp = $fechaRaw;
    }
}

$isLocal = strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false;
$dir = ($isLocal ? dirname(__DIR__) : dirname(dirname(__DIR__))) . '/uploads/logos/';
if (!is_dir($dir)) mkdir($dir, 0755, true);

$archivo = 'logo_' . time() . '_' . mt_rand(1000, 9999) . '.' . $ext;
$destino = $dir . $archivo;

if (move_uploaded_file($file['tmp_name'], $destino)) {
    $rutaRelativa = 'uploads/logos/' . $archivo;
    $nextOrden = (int) $con->query("SELECT COALESCE(MAX(orden), 0) + 1 AS n FROM logos_marcas")->fetch_assoc()['n'];
    $stmt = $con->prepare("INSERT INTO logos_marcas (imagen, nombre, fecha_expiracion, orden) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sssi", $rutaRelativa, $nombre, $fechaExp, $nextOrden);
    $stmt->execute();
    $id = $con->insert_id;
    logActivity($con, 'crear', 'logos', 'Subió logo «' . $nombre . '» (ID ' . $id . ')');
    echo json_encode(['ok' => true, 'id' => $id, 'imagen' => $rutaRelativa, 'nombre' => $nombre, 'fecha_expiracion' => $fechaExp, 'orden' => $nextOrden]);
} else {
    echo json_encode(['ok' => false, 'error' => 'Error al guardar el archivo.']);
}
