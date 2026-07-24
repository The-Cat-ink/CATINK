<?php
session_start();
include("./../controllers/aclcontroller.php");
proteger('categorias','eliminar');
include("./../data/conexion.php");
require_once("./../views/helpers/activity_log.php");
require_once("./../views/helpers/iconos_categorias.php");
header('Content-Type: application/json');

$id_c = intval($_POST['id_c'] ?? 0);
if($id_c <= 0){
    echo json_encode(['error'=>'ID de categoría inválido']);
    exit();
}
// Recuperar la imagen del icono (si tenía) para borrarla del disco tras eliminar.
$prev = $con->prepare("SELECT icono_img FROM categorias WHERE id_c=?");
$prev->bind_param("i",$id_c);
$prev->execute();
$iconoImgAnterior = $prev->get_result()->fetch_assoc()['icono_img'] ?? null;

$stmt = $con->prepare("DELETE FROM noticia_categoria WHERE categoria_id=?");
$stmt->bind_param("i",$id_c);
$stmt->execute();
$stmt = $con->prepare("DELETE FROM categorias WHERE id_c=?");
$stmt->bind_param("i",$id_c);
if($stmt->execute()){
    if(!empty($iconoImgAnterior)){
        $ruta = rutaFisicaIconoCategoria($iconoImgAnterior);
        if($ruta !== null && is_file($ruta)){
            @unlink($ruta);
        }
    }
    logActivity($con, 'eliminar', 'categorias', 'Eliminó categoría ID ' . $id_c);
    echo json_encode(['success'=>true]);
}else{
    echo json_encode(['error'=>'No se pudo eliminar la categoría']);
}