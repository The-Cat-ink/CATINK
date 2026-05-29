<?php
session_start();
include("./aclcontroller.php");
proteger('noticias','editar');
include("../data/conexion.php");

header('Content-Type: application/json');

$id = (int)($_POST['id'] ?? 0);
$nuevaFecha = $_POST['fecha'] ?? '';

if (!$id || !$nuevaFecha) {
    http_response_code(400);
    echo json_encode(['error' => 'Faltan parámetros']);
    exit;
}

// Validar formato de fecha
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $nuevaFecha)) {
    http_response_code(400);
    echo json_encode(['error' => 'Formato de fecha inválido']);
    exit;
}

// Validar que no sea fecha pasada
$hoy = date('Y-m-d');
if ($nuevaFecha < $hoy) {
    http_response_code(400);
    echo json_encode(['error' => 'No se pueden programar noticias en fechas pasadas']);
    exit;
}

// Obtener hora actual de la noticia para mantenerla
$stmt = $con->prepare("SELECT fecha_publicacion FROM noticias WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if (!$row) {
    http_response_code(404);
    echo json_encode(['error' => 'Noticia no encontrada']);
    exit;
}

// Mantener la hora, solo cambiar la fecha
$horaOriginal = date('H:i:s', strtotime($row['fecha_publicacion']));
$fechaCompleta = $nuevaFecha . ' ' . $horaOriginal;

$update = $con->prepare("UPDATE noticias SET fecha_publicacion = ? WHERE id = ?");
$update->bind_param("si", $fechaCompleta, $id);

if ($update->execute()) {
    echo json_encode(['success' => true, 'nueva_fecha' => $fechaCompleta]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Error al actualizar']);
}
