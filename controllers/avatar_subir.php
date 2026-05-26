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
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$permitidos = ['jpg','jpeg','png','gif','webp'];

if(!in_array($ext, $permitidos)){
    echo json_encode(['ok'=>false, 'error'=>'Formato no válido. Usa JPG, PNG, GIF o WEBP.']);
    exit;
}

// Crear carpeta si no existe
$dir = __DIR__ . '/../img/avatares/';
if(!is_dir($dir)) mkdir($dir, 0755, true);

// Nombre único
$nombre = 'avatar_' . time() . '_' . mt_rand(1000,9999) . '.' . $ext;
$destino = $dir . $nombre;

if(move_uploaded_file($file['tmp_name'], $destino)){
    $stmt = $con->prepare("INSERT INTO avatares_perfil (imagen) VALUES (?)");
    $stmt->bind_param("s", $nombre);
    $stmt->execute();
    echo json_encode(['ok'=>true, 'imagen'=>$nombre]);
} else {
    echo json_encode(['ok'=>false, 'error'=>'Error al mover archivo.']);
}
