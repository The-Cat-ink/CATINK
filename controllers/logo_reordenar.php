<?php
include(__DIR__ . "/aclcontroller.php");
proteger('contenidos', 'editar');
include(__DIR__ . "/../data/conexion.php");
header('Content-Type: application/json');

$ids = json_decode(file_get_contents('php://input'), true) ?? [];

if (!is_array($ids) || empty($ids)) {
    echo json_encode(['ok' => false, 'error' => 'Datos inválidos.']);
    exit;
}

$stmt = $con->prepare("UPDATE logos_marcas SET orden = ? WHERE id_logo = ?");
foreach ($ids as $pos => $id) {
    $orden = (int)$pos + 1;
    $id    = (int)$id;
    $stmt->bind_param("ii", $orden, $id);
    $stmt->execute();
}

require_once(__DIR__ . "/../views/helpers/activity_log.php");
logActivity($con, 'editar', 'logos', 'Reordenó los logos de marcas');

echo json_encode(['ok' => true]);
