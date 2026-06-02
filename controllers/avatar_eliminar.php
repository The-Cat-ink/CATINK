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
    // Manejo de rutas: si es solo el nombre, agregar ruta; si es ruta completa, usar como está
    $rutaImagen = $row['imagen'];
    if(strpos($rutaImagen, '/') === false){
        $rutaImagen = 'img/avatares/' . $rutaImagen;
    }
    $archivo = __DIR__ . '/../' . $rutaImagen;
    if(file_exists($archivo)) unlink($archivo);

    $stmt = $con->prepare("DELETE FROM avatares_perfil WHERE id_avatar = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    // Resetear avatar en ambas tablas
    $stmt = $con->prepare("UPDATE usuarios SET avatar_id = NULL WHERE avatar_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    // Lectores (safe: tabla puede no existir aún)
    $check = $con->query("SHOW TABLES LIKE 'lectores'");
    if($check && $check->num_rows > 0){
        $stmt = $con->prepare("UPDATE lectores SET avatar_id = NULL WHERE avatar_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
    }
}

echo json_encode(['ok'=>true]);
