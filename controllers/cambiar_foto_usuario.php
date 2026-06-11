<?php
include("./../controllers/aclcontroller.php");
proteger('usuarios', 'editar');
include("./../data/conexion.php");
header('Content-Type: application/json');

$id = intval($_POST['id_u'] ?? 0);
if (!$id) {
    echo json_encode(['error' => 'ID inválido']);
    exit;
}

$stmt = $con->prepare("SELECT id_u, foto_personal FROM usuarios WHERE id_u = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
if (!$user) {
    echo json_encode(['error' => 'Usuario no encontrado']);
    exit;
}

$isLocal = strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false;
$base    = $isLocal ? dirname(__DIR__) : '/home/u780114275';

// ── Eliminar foto ──────────────────────────────────────────────
if (!empty($_POST['eliminar'])) {
    if (!empty($user['foto_personal'])) {
        $full = $base . '/' . $user['foto_personal'];
        if (file_exists($full)) unlink($full);
    }
    $stmt = $con->prepare("UPDATE usuarios SET foto_personal = NULL WHERE id_u = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    echo json_encode(['success' => 'Foto eliminada']);
    exit;
}

// ── Subir nueva foto ───────────────────────────────────────────
if (!isset($_FILES['foto']) || $_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['error' => 'No se recibió archivo']);
    exit;
}

$file = $_FILES['foto'];
if ($file['size'] > 5 * 1024 * 1024) {
    echo json_encode(['error' => 'Archivo demasiado grande (máx 5 MB)']);
    exit;
}

$mime = mime_content_type($file['tmp_name']);
if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp', 'image/gif'])) {
    echo json_encode(['error' => 'Formato no permitido (jpg, png, webp, gif)']);
    exit;
}

$dir = $base . '/uploads/editores/';
if (!is_dir($dir)) mkdir($dir, 0755, true);

// Borrar foto anterior
if (!empty($user['foto_personal'])) {
    $old = $base . '/' . $user['foto_personal'];
    if (file_exists($old)) unlink($old);
}

$nombre = 'editor_' . $id . '_' . time() . '.webp';
$imagen = imagecreatefromstring(file_get_contents($file['tmp_name']));
if (!$imagen) {
    echo json_encode(['error' => 'Error al procesar la imagen']);
    exit;
}

imagewebp($imagen, $dir . $nombre, 92);
imagedestroy($imagen);

$ruta = 'uploads/editores/' . $nombre;
$stmt = $con->prepare("UPDATE usuarios SET foto_personal = ? WHERE id_u = ?");
$stmt->bind_param("si", $ruta, $id);
$stmt->execute();

echo json_encode(['success' => 'Foto actualizada', 'imagen' => $ruta]);
