<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once(__DIR__ . "/aclcontroller.php");
proteger('noticias', 'editar');
require_once(__DIR__ . "/../data/conexion.php");

$data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$id = intval($data['id'] ?? 0);
$estado = $data['estado'] ?? 'pendiente';

$estadosValidos = ['pendiente', 'revisado', 'aceptado', 'rechazado'];
if ($id <= 0 || !in_array($estado, $estadosValidos)) {
    echo json_encode(['error' => 'Parámetros inválidos']);
    exit;
}

$stmt = $con->prepare("UPDATE solicitudes_vacantes SET estado = ? WHERE id = ?");
$stmt->bind_param("si", $estado, $id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Estado del postulante actualizado']);
} else {
    echo json_encode(['error' => 'Error al actualizar estado: ' . $con->error]);
}
