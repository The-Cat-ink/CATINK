<?php
include("./../controllers/aclcontroller.php");
proteger('usuarios', 'eliminar');
include('../data/conexion.php');
header('Content-Type: application/json');

$id = intval($_POST['id'] ?? 0);
if (!$id) {
    echo json_encode(['error' => 'ID inválido']);
    exit;
}

$stmt = $con->prepare("DELETE FROM usuarios WHERE id_u = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    echo json_encode(['success' => 'Usuario eliminado correctamente']);
} else {
    echo json_encode(['error' => 'Error al eliminar el usuario']);
}
