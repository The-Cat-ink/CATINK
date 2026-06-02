<?php
include(__DIR__ . "/aclcontroller.php");
proteger('usuarios', 'crear');
include(__DIR__ . "/../data/conexion.php");
header('Content-Type: application/json');

if(!isset($_FILES['imagen']) || $_FILES['imagen']['error'] !== UPLOAD_ERR_OK){
    echo json_encode(['ok'=>false, 'error'=>'No se recibió imagen.']);
    exit;
}

$file = $_FILES['imagen'];

// ============================
// VALIDAR TAMAÑO (máximo 5MB)
// ============================
$maxSize = 5 * 1024 * 1024; // 5MB
if($file['size'] > $maxSize){
    echo json_encode(['ok'=>false, 'error'=>'Archivo demasiado grande (máximo 5MB).']);
    exit;
}

// ============================
// VALIDAR MIME TYPE (no solo extensión)
// ============================
$mimeType = mime_content_type($file['tmp_name']);
$mimePermitidos = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

if(!in_array($mimeType, $mimePermitidos)){
    echo json_encode(['ok'=>false, 'error'=>'Formato no válido. Usa JPG, PNG, GIF o WEBP.']);
    exit;
}

// ============================
// VALIDAR EXTENSIÓN (como segunda capa)
// ============================
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$extPermitidas = ['jpg','jpeg','png','gif','webp'];

if(!in_array($ext, $extPermitidas)){
    echo json_encode(['ok'=>false, 'error'=>'Extensión no válida.']);
    exit;
}

// ============================
// VALIDAR DIMENSIONES DE IMAGEN
// ============================
$imageInfo = getimagesize($file['tmp_name']);
if($imageInfo === false){
    echo json_encode(['ok'=>false, 'error'=>'No es una imagen válida.']);
    exit;
}

$maxWidth = 2000;
$maxHeight = 2000;
if($imageInfo[0] > $maxWidth || $imageInfo[1] > $maxHeight){
    echo json_encode(['ok'=>false, 'error'=>'Imagen demasiado grande (máximo 2000x2000 píxeles).']);
    exit;
}

// Crear carpeta si no existe
$dir = __DIR__ . '/../img/avatares/';
if(!is_dir($dir)) mkdir($dir, 0755, true);

// Nombre único
$nombre = 'avatar_' . time() . '_' . mt_rand(1000,9999) . '.' . $ext;
$destino = $dir . $nombre;

if(move_uploaded_file($file['tmp_name'], $destino)){
    // Guardar ruta relativa consistente
    $rutaRelativa = 'img/avatares/' . $nombre;
    $stmt = $con->prepare("INSERT INTO avatares_perfil (imagen) VALUES (?)");
    $stmt->bind_param("s", $rutaRelativa);
    $stmt->execute();
    echo json_encode(['ok'=>true, 'imagen'=>$rutaRelativa]);
} else {
    echo json_encode(['ok'=>false, 'error'=>'Error al mover archivo.']);
}
