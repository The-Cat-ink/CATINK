<?php
session_start();
include("../data/conexion.php");
header('Content-Type: application/json');

if(!isset($_SESSION['usuario']) || $_SESSION['tipo'] !== 'admin'){
    echo json_encode(['ok'=>false, 'error'=>'No autorizado']);
    exit;
}

if(!isset($_FILES['foto_personal']) || $_FILES['foto_personal']['error'] !== UPLOAD_ERR_OK){
    echo json_encode(['ok'=>false, 'error'=>'No se recibió el archivo de imagen']);
    exit;
}

$file = $_FILES['foto_personal'];

// Validar tamaño (máximo 5MB)
$maxSize = 5 * 1024 * 1024;
if($file['size'] > $maxSize){
    echo json_encode(['ok'=>false, 'error'=>'El archivo es demasiado grande (máximo 5MB)']);
    exit;
}

$mime = mime_content_type($file['tmp_name']);
$permitidos = ['image/jpeg', 'image/png', 'image/webp'];
if(!in_array($mime, $permitidos)){
    echo json_encode(['ok'=>false, 'error'=>'Formato no permitido. Usa JPG, PNG o WEBP.']);
    exit;
}

$usuario = $_SESSION['usuario'];

// Obtener id_u
$stmtId = $con->prepare("SELECT id_u, foto_personal FROM usuarios WHERE usuario = ?");
$stmtId->bind_param("s", $usuario);
$stmtId->execute();
$rowId = $stmtId->get_result()->fetch_assoc();

if(!$rowId){
    echo json_encode(['ok'=>false, 'error'=>'Usuario no encontrado']);
    exit;
}

// Determinar ruta del directorio uploads/editores/
$baseDir = dirname(__DIR__);
$dir = $baseDir . '/uploads/editores/';
if(!is_dir($dir)){
    @mkdir($dir, 0755, true);
}

// Borrar foto anterior si existe
if(!empty($rowId['foto_personal'])){
    $fotoPath = $rowId['foto_personal'];
    if(strpos($fotoPath, '..') === false){
        $fullPath = $baseDir . '/' . $fotoPath;
        if(file_exists($fullPath)){
            @unlink($fullPath);
        }
    }
}

// Guardar nueva foto
$nombreArchivo = 'editor_' . $rowId['id_u'] . '_' . time() . '.webp';
$fileContent = file_get_contents($file['tmp_name']);
$imagen = @imagecreatefromstring($fileContent);

if($imagen){
    $saved = @imagewebp($imagen, $dir . $nombreArchivo, 92);
    imagedestroy($imagen);

    if ($saved && file_exists($dir . $nombreArchivo)) {
        $foto_personal = 'uploads/editores/' . $nombreArchivo;
        
        // Actualizar en BD y limpiar avatar_id
        $stmt = $con->prepare("UPDATE usuarios SET foto_personal = ?, avatar_id = NULL WHERE usuario = ?");
        $stmt->bind_param("ss", $foto_personal, $usuario);
        $stmt->execute();
        
        echo json_encode(['ok'=>true, 'imagen'=>$foto_personal, 'message'=>'Foto de perfil actualizada']);
    } else {
        echo json_encode(['ok'=>false, 'error'=>'No se pudo escribir la imagen en el servidor']);
    }
} else {
    // Si GD no pudo procesar, mover el archivo WEBP directamente
    $foto_personal = 'uploads/editores/' . $nombreArchivo;
    if (@move_uploaded_file($file['tmp_name'], $dir . $nombreArchivo)) {
        $stmt = $con->prepare("UPDATE usuarios SET foto_personal = ?, avatar_id = NULL WHERE usuario = ?");
        $stmt->bind_param("ss", $foto_personal, $usuario);
        $stmt->execute();
        
        echo json_encode(['ok'=>true, 'imagen'=>$foto_personal, 'message'=>'Foto de perfil actualizada']);
    } else {
        echo json_encode(['ok'=>false, 'error'=>'Error al procesar la imagen subida']);
    }
}
