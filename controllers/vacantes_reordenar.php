<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once(__DIR__ . "/aclcontroller.php");
proteger('noticias', 'editar');
require_once(__DIR__ . "/../data/conexion.php");

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['vacantes']) || !is_array($data['vacantes'])) {
    echo json_encode(['error' => 'Datos de ordenamiento inválidos']);
    exit;
}

$stmt = $con->prepare("UPDATE vacantes_equipo SET orden = ? WHERE id = ?");

foreach ($data['vacantes'] as $item) {
    $id = intval($item['id'] ?? 0);
    $orden = intval($item['orden'] ?? 0);
    if ($id > 0) {
        $stmt->bind_param("ii", $orden, $id);
        $stmt->execute();
    }
}

echo json_encode(['success' => true, 'message' => 'Orden de vacantes actualizado con éxito']);
