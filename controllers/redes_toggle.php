<?php
session_start();
header('Content-Type: application/json');
include("./../data/conexion.php");

$id_u = $_SESSION['id_u'] ?? 0;
if ($id_u <= 0) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_red = intval($_POST['id_red'] ?? 0);
    if ($id_red <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID de red inválido']);
        exit();
    }

    $stmt = $con->prepare("SELECT activo FROM redes_sociales WHERE id_red=?");
    $stmt->bind_param("i", $id_red);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;

    if (!$row) {
        echo json_encode(['success' => false, 'message' => 'Red no encontrada']);
        exit();
    }

    $nuevoEstado = intval($row['activo']) === 1 ? 0 : 1;
    $upStmt = $con->prepare("UPDATE redes_sociales SET activo=? WHERE id_red=?");
    $upStmt->bind_param("ii", $nuevoEstado, $id_red);

    if ($upStmt->execute()) {
        echo json_encode([
            'success' => true,
            'activo'  => $nuevoEstado,
            'label'   => $nuevoEstado === 1 ? '● Activo' : '○ Inactivo'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar estado']);
    }
    exit();
}
