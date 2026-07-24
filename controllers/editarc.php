<?php
session_start();
include("./../controllers/aclcontroller.php");
proteger('categorias','editar');
include("./../data/conexion.php");
require_once("./../views/helpers/activity_log.php");
require_once("./../views/helpers/iconos_categorias.php");
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
// Cualquier valor fuera del catálogo cae al icono por defecto: lo que se guarda
// aquí se imprime como clase CSS en <i class="bi ..."> del menú público.
$icono = sanearIconoCategoria($_POST['icono'] ?? null);
// Imagen propia (opcional). Si viene, gana sobre el icono del catálogo, que se
// conserva igualmente por si después se quita la imagen.
$iconoImg = sanearIconoImgCategoria($_POST['icono_img'] ?? null);

// Imagen anterior: si cambió o se quitó, se borra del disco para no acumular
// archivos huérfanos cada vez que se reemplaza el icono.
$prev = $con->prepare("SELECT icono_img FROM categorias WHERE id_c=?");
$prev->bind_param("i",$id_c);
$prev->execute();
$iconoImgAnterior = $prev->get_result()->fetch_assoc()['icono_img'] ?? null;

$stmt = $con->prepare("UPDATE categorias SET nombre=?, icono=?, icono_img=? WHERE id_c=?");
$stmt->bind_param("sssi",$nombre,$icono,$iconoImg,$id_c);
if($stmt->execute()){
    if(!empty($iconoImgAnterior) && $iconoImgAnterior !== $iconoImg){
        $rutaVieja = rutaFisicaIconoCategoria($iconoImgAnterior);
        if($rutaVieja !== null && is_file($rutaVieja)){
            @unlink($rutaVieja);
        }
    }
    logActivity($con, 'editar', 'categorias', 'Actualizó categoría ID ' . $id_c . ': «' . $nombre . '» / icono ' . ($iconoImg ?: $icono));
    echo json_encode(['success'=>true]);
}else{
    echo json_encode(['error'=>'No se pudo actualizar la categoría']);
}