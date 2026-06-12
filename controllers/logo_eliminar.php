<?php
include(__DIR__ . "/aclcontroller.php");
proteger('contenidos', 'eliminar');
include(__DIR__ . "/../data/conexion.php");
header('Content-Type: application/json');

$id = intval($_POST['id'] ?? 0);
if ($id <= 0) {
    echo json_encode(['ok' => false]);
    exit;
}

$stmt = $con->prepare("SELECT imagen FROM logos_marcas WHERE id_logo = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

if ($row) {
    $isLocal = strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false;
    $archivo = ($isLocal ? dirname(__DIR__) : dirname(dirname(__DIR__))) . '/' . $row['imagen'];
    if (file_exists($archivo)) unlink($archivo);

    $stmt = $con->prepare("DELETE FROM logos_marcas WHERE id_logo = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
}

echo json_encode(['ok' => true]);
