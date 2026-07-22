<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once(__DIR__ . "/aclcontroller.php");
proteger('noticias', 'editar');
require_once(__DIR__ . "/../data/conexion.php");

$data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$id = intval($data['id'] ?? 0);
$action = $data['action'] ?? 'delete';

if ($id <= 0) {
    echo json_encode(['error' => 'ID de vacante inválido']);
    exit;
}

if ($action === 'toggle') {
    $stmtToggle = $con->prepare("UPDATE vacantes_equipo SET estado = IF(estado = 1, 0, 1) WHERE id = ?");
    $stmtToggle->bind_param("i", $id);
    if ($stmtToggle->execute()) {
        echo json_encode(['success' => true, 'message' => 'Estado de la vacante actualizado']);
    } else {
        echo json_encode(['error' => 'Error al cambiar estado: ' . $con->error]);
    }
} else {
    $stmt = $con->prepare("DELETE FROM vacantes_equipo WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Vacante eliminada']);
    } else {
        echo json_encode(['error' => 'Error al eliminar vacante: ' . $con->error]);
    }
}
