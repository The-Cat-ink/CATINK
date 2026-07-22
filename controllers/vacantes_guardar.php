<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once(__DIR__ . "/aclcontroller.php");
proteger('noticias', 'editar'); // Requiere permiso de edicion de noticias o superadmin
require_once(__DIR__ . "/../data/conexion.php");

$data = json_decode(file_get_contents('php://input'), true) ?: $_POST;

$id = intval($data['id'] ?? 0);
$orden = intval($data['orden'] ?? 1);
$tag = trim($data['tag'] ?? '');
$titulo = trim($data['titulo'] ?? '');
$subtitulo_italic = trim($data['subtitulo_italic'] ?? '');
$descripcion = trim($data['descripcion'] ?? '');
$modalidad = trim($data['modalidad'] ?? '100% Remoto · Tiempo completo');
$estado = isset($data['estado']) ? intval($data['estado']) : 1;

if (empty($tag) || empty($titulo) || empty($descripcion)) {
    echo json_encode(['error' => 'Por favor completa la etiqueta (tag), título y descripción de la vacante.']);
    exit;
}

if ($id > 0) {
    // Actualizar vacante existente
    $stmt = $con->prepare("UPDATE vacantes_equipo SET orden = ?, tag = ?, titulo = ?, subtitulo_italic = ?, descripcion = ?, modalidad = ?, estado = ? WHERE id = ?");
    $stmt->bind_param("isssssii", $orden, $tag, $titulo, $subtitulo_italic, $descripcion, $modalidad, $estado, $id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Vacante actualizada con éxito']);
    } else {
        echo json_encode(['error' => 'Error en BD: ' . $con->error]);
    }
} else {
    // Crear nueva vacante
    $stmt = $con->prepare("INSERT INTO vacantes_equipo (orden, tag, titulo, subtitulo_italic, descripcion, modalidad, estado, creado_en) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("isssssi", $orden, $tag, $titulo, $subtitulo_italic, $descripcion, $modalidad, $estado);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Vacante creada con éxito', 'id' => $con->insert_id]);
    } else {
        echo json_encode(['error' => 'Error en BD: ' . $con->error]);
    }
}
