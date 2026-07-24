<?php
session_start();
include("./../controllers/aclcontroller.php");
proteger('categorias','crear');
include("./../data/conexion.php");
require_once("./../views/helpers/activity_log.php");
require_once("./../views/helpers/iconos_categorias.php");
header('Content-Type: application/json');

$nombre = trim($_POST['nombre'] ?? '');
if($nombre === ''){
    echo json_encode(['error'=>'El nombre es obligatorio']);
    exit();
}
// Cualquier valor fuera del catálogo cae al icono por defecto: lo que se guarda
// aquí se imprime como clase CSS en <i class="bi ..."> del menú público.
$icono = sanearIconoCategoria($_POST['icono'] ?? null);
// Imagen propia (opcional). Si viene, gana sobre el icono del catálogo, que se
// conserva igualmente por si después se quita la imagen.
$iconoImg = sanearIconoImgCategoria($_POST['icono_img'] ?? null);
$stmt = $con->prepare("SELECT id_c FROM categorias WHERE nombre = ?");
$stmt->bind_param("s",$nombre);
$stmt->execute();
$res = $stmt->get_result();
if($res->num_rows > 0){
    echo json_encode(['error'=>'Ya existe una categoría con ese nombre']);
    exit();
}
$stmt = $con->prepare("INSERT INTO categorias(nombre, icono, icono_img) VALUES(?,?,?)");
$stmt->bind_param("sss",$nombre,$icono,$iconoImg);
if($stmt->execute()){
    logActivity($con, 'crear', 'categorias', 'Creó categoría «' . $nombre . '» con icono ' . ($iconoImg ?: $icono));
    echo json_encode(['success'=>true]);
}else{
    echo json_encode(['error'=>'No se pudo crear la categoría']);
}