<?php
include("./../controllers/aclcontroller.php");
proteger('usuarios', 'eliminar');
include('../data/conexion.php');
require_once('../views/helpers/activity_log.php');
header('Content-Type: application/json');


$id = intval($_POST['id'] ?? 0);
if (!$id) {
    echo json_encode(['error' => 'ID inválido']);
    exit;
}

$stmtComm = $con->prepare("UPDATE comentarios SET estado = 'eliminado' WHERE usuario_id = ?");
$stmtComm->bind_param("i", $id);
$stmtComm->execute();

$stmt = $con->prepare("DELETE FROM usuarios WHERE id_u = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    logActivity($con, 'eliminar', 'usuarios', 'Eliminó usuario administrador ID ' . $id);
    echo json_encode(['success' => 'Usuario eliminado correctamente']);
} else {
    echo json_encode(['error' => 'Error al eliminar el usuario']);
}
