<?php
include(__DIR__ . "/aclcontroller.php");
proteger('contenidos', 'editar');
include(__DIR__ . "/../data/conexion.php");
header('Content-Type: application/json');

$id     = (int) ($_POST['id'] ?? 0);
$nombre = trim($_POST['nombre'] ?? '');

if (!$id) {
    echo json_encode(['ok' => false, 'error' => 'ID inválido.']);
    exit;
}

// Vacío = sin vencimiento. Si viene con contenido pero no es una fecha válida
// se responde con error: guardar NULL en silencio haría creer que se aplicó
// un vencimiento que en realidad se descartó.
$fechaRaw = trim($_POST['fecha_expiracion'] ?? '');
$fechaExp = null;
if ($fechaRaw !== '') {
    if (!preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $fechaRaw) || !strtotime($fechaRaw)) {
        echo json_encode(['ok' => false, 'error' => 'Fecha de vencimiento inválida.']);
        exit;
    }
    $fechaExp = $fechaRaw;
}

$nuevaImagen = null;

if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
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

    $isLocal = strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false;
    $dir = ($isLocal ? dirname(__DIR__) : dirname(dirname(__DIR__))) . '/uploads/logos/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    $archivo = 'logo_' . time() . '_' . mt_rand(1000, 9999) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $dir . $archivo)) {
        echo json_encode(['ok' => false, 'error' => 'Error al guardar la imagen.']);
        exit;
    }

    $nuevaImagen = 'uploads/logos/' . $archivo;
    $stmt = $con->prepare("UPDATE logos_marcas SET nombre = ?, fecha_expiracion = ?, imagen = ? WHERE id_logo = ?");
    $stmt->bind_param("sssi", $nombre, $fechaExp, $nuevaImagen, $id);
} else {
    $stmt = $con->prepare("UPDATE logos_marcas SET nombre = ?, fecha_expiracion = ? WHERE id_logo = ?");
    $stmt->bind_param("ssi", $nombre, $fechaExp, $id);
}

if ($stmt->execute()) {
    require_once(__DIR__ . "/../views/helpers/activity_log.php");
    logActivity($con, 'editar', 'logos', 'Actualizó logo ID ' . $id . ' («' . $nombre . '»)');
    echo json_encode(['ok' => true, 'nombre' => $nombre, 'fecha_expiracion' => $fechaExp, 'imagen' => $nuevaImagen]);
} else {
    echo json_encode(['ok' => false, 'error' => 'Error al guardar.']);
}
