<?php
session_start();
include("./../controllers/aclcontroller.php");
proteger('categorias','editar');
include("./../data/conexion.php");
require_once("./../views/helpers/activity_log.php");
header('Content-Type: application/json');

$id_c = intval($_POST['id_c'] ?? 0);
if($id_c <= 0){
    echo json_encode(['error'=>'ID de categoría inválido']);
    exit();
}
$nombre = trim($_POST['nombre'] ?? '');
if($nombre === ''){
    echo json_encode(['error'=>'El nombre es obligatorio']);
    exit();
}
$stmt = $con->prepare("SELECT id_c FROM categorias WHERE nombre=? AND id_c<>?");
$stmt->bind_param("si",$nombre,$id_c);
$stmt->execute();
$res = $stmt->get_result();
if($res->num_rows > 0){
    echo json_encode(['error'=>'Ya existe otra categoría con ese nombre']);
    exit();
}
$stmt = $con->prepare("UPDATE categorias SET nombre=? WHERE id_c=?");
$stmt->bind_param("si",$nombre,$id_c);
if($stmt->execute()){
    logActivity($con, 'editar', 'categorias', 'Renombró categoría ID ' . $id_c . ' a «' . $nombre . '»');
    echo json_encode(['success'=>true]);
}else{
    echo json_encode(['error'=>'No se pudo actualizar la categoría']);
}