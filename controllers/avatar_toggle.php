<?php
include(__DIR__ . "/aclcontroller.php");
proteger('avatares', 'editar');
include(__DIR__ . "/../data/conexion.php");
header('Content-Type: application/json');

$id = intval($_POST['id'] ?? 0);
if($id <= 0){
    echo json_encode(['ok'=>false]);
    exit;
}

$stmt = $con->prepare("UPDATE avatares_perfil SET activo = NOT activo WHERE id_avatar = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

echo json_encode(['ok'=>true]);
