<?php
include(__DIR__ . "/aclcontroller.php");
proteger('usuarios', 'eliminar');
include(__DIR__ . "/../data/conexion.php");
header('Content-Type: application/json');

$id = intval($_POST['id'] ?? 0);
if($id <= 0){
    echo json_encode(['ok'=>false]);
    exit;
}

// Obtener nombre de imagen para borrar archivo
$stmt = $con->prepare("SELECT imagen FROM avatares_perfil WHERE id_avatar = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

if($row){
    $archivo = __DIR__ . '/../img/avatares/' . $row['imagen'];
    if(file_exists($archivo)) unlink($archivo);

    $stmt = $con->prepare("DELETE FROM avatares_perfil WHERE id_avatar = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    // Resetear usuarios que tenían este avatar
    $stmt = $con->prepare("UPDATE usuarios SET avatar_id = NULL WHERE avatar_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
}

echo json_encode(['ok'=>true]);
