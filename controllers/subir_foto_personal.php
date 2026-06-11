<?php
session_start();
include("../data/conexion.php");
header('Content-Type: application/json');

if(!isset($_SESSION['usuario']) || $_SESSION['tipo'] !== 'admin'){
    echo json_encode(['ok'=>false, 'error'=>'No autorizado']);
    exit;
}

if(!isset($_FILES['foto_personal']) || $_FILES['foto_personal']['error'] !== UPLOAD_ERR_OK){
    echo json_encode(['ok'=>false, 'error'=>'No se recibió archivo']);
    exit;
}

$file = $_FILES['foto_personal'];

// Validar tamaño (máximo 5MB)
$maxSize = 5 * 1024 * 1024;
if($file['size'] > $maxSize){
    echo json_encode(['ok'=>false, 'error'=>'Archivo demasiado grande']);
    exit;
}

$mime = mime_content_type($file['tmp_name']);
$permitidos = ['image/jpeg', 'image/png', 'image/webp'];
if(!in_array($mime, $permitidos)){
    echo json_encode(['ok'=>false, 'error'=>'Formato no permitido']);
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

// Determinar ruta
$isLocal = strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false;
$dir = ($isLocal ? dirname(__DIR__) : '/home/u780114275') . '/uploads/editores/';
if(!is_dir($dir)) mkdir($dir, 0755, true);

// Borrar foto anterior
if(!empty($rowId['foto_personal'])){
    $fotoPath = $rowId['foto_personal'];
    if(strpos($fotoPath, '..') === false && strpos($fotoPath, '/') !== 0){
        $fullPath = ($isLocal ? dirname(__DIR__) : '/home/u780114275') . '/' . $fotoPath;
        if(file_exists($fullPath) && strpos(realpath($fullPath), realpath(($isLocal ? dirname(__DIR__) : '/home/u780114275') . '/uploads/editores/')) === 0){
            unlink($fullPath);
        }
    }
}

// Guardar nueva foto
$nombreArchivo = 'editor_' . $rowId['id_u'] . '_' . time() . '.webp';
$imagen = imagecreatefromstring(file_get_contents($file['tmp_name']));
if($imagen){
    imagewebp($imagen, $dir . $nombreArchivo, 92);
    imagedestroy($imagen);
    $foto_personal = 'uploads/editores/' . $nombreArchivo;
    
    // Actualizar en BD
    $stmt = $con->prepare("UPDATE usuarios SET foto_personal = ? WHERE usuario = ?");
    $stmt->bind_param("ss", $foto_personal, $usuario);
    $stmt->execute();
    
    echo json_encode(['ok'=>true, 'imagen'=>$foto_personal]);
} else {
    echo json_encode(['ok'=>false, 'error'=>'Error al procesar imagen']);
}
