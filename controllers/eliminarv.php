<?php
ob_start();
header('Content-Type: application/json');
session_start();
include("./aclcontroller.php");
proteger('videos','eliminar');
$id = $_POST['id_v'] ?? null;

if(!$id){
    echo json_encode(['success' => false, 'error' => 'ID no proporcionado']);
    exit;
}
include("./../data/conexion.php");
require_once("./../views/helpers/activity_log.php");

$stmt = $con->prepare("DELETE FROM videos WHERE id_v = ?");
$stmt->bind_param("i", $id);
if($stmt->execute()){
    logActivity($con, 'eliminar', 'videos', 'Eliminó video ID ' . $id);
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Error al eliminar el video']);
}
exit;