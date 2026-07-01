<?php
session_start();
include("../data/conexion.php");
header('Content-Type: application/json');

if(!isset($_SESSION['usuario'])){
    echo json_encode(['ok'=>false, 'error'=>'No autenticado']);
    exit;
}

$avatar_id = intval($_POST['avatar_id'] ?? 0);
if($avatar_id <= 0){
    echo json_encode(['ok'=>false, 'error'=>'ID inválido']);
    exit;
}

// Verificar que el avatar existe y está activo
$stmt = $con->prepare("SELECT id_avatar FROM avatares_perfil WHERE id_avatar = ? AND activo = 1");
$stmt->bind_param("i", $avatar_id);
$stmt->execute();
if($stmt->get_result()->num_rows === 0){
    echo json_encode(['ok'=>false, 'error'=>'Avatar no disponible']);
    exit;
}

// Actualizar avatar según tipo de usuario
$tipo = $_SESSION['tipo'] ?? 'lector';
$usuario = $_SESSION['usuario'];

error_log("DEBUG avatar_seleccionar: tipo=$tipo, usuario=$usuario, avatar_id=$avatar_id");

if($tipo === 'admin'){
    // Obtener foto anterior para borrar el archivo físico
    $stmtPrev = $con->prepare("SELECT foto_personal FROM usuarios WHERE usuario = ?");
    $stmtPrev->bind_param("s", $usuario);
    $stmtPrev->execute();
    $rowPrev = $stmtPrev->get_result()->fetch_assoc();
    if($rowPrev && !empty($rowPrev['foto_personal'])){
        $isLocal = strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false || strpos($_SERVER['HTTP_HOST'] ?? '', 'catink.test') !== false;
        $fotoPath = $rowPrev['foto_personal'];
        if(strpos($fotoPath, '..') === false && strpos($fotoPath, '/') !== 0){
            $fullPath = ($isLocal ? dirname(__DIR__) : '/home/u780114275') . '/' . $fotoPath;
            if(file_exists($fullPath)){
                unlink($fullPath);
            }
        }
    }

    $stmt = $con->prepare("UPDATE usuarios SET avatar_id = ?, foto_personal = NULL WHERE usuario = ?");
    $stmt->bind_param("is", $avatar_id, $usuario);
    $stmt->execute();
    error_log("DEBUG: Updated usuarios, affected_rows=" . $stmt->affected_rows);
} else {
    // Intentar en lectores primero
    $stmt = $con->prepare("UPDATE lectores SET avatar_id = ? WHERE usuario = ?");
    $stmt->bind_param("is", $avatar_id, $usuario);
    $stmt->execute();
    error_log("DEBUG: Updated lectores, affected_rows=" . $stmt->affected_rows);
    // Fallback: si no se actualizó ninguna fila, buscar en usuarios (sesión legacy)
    if($stmt->affected_rows === 0){
        $stmt = $con->prepare("UPDATE usuarios SET avatar_id = ? WHERE usuario = ?");
        $stmt->bind_param("is", $avatar_id, $usuario);
        $stmt->execute();
        error_log("DEBUG: Fallback to usuarios, affected_rows=" . $stmt->affected_rows);
    }
}

echo json_encode(['ok'=>true]);
