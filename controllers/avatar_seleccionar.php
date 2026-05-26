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

if($tipo === 'admin'){
    $stmt = $con->prepare("UPDATE usuarios SET avatar_id = ? WHERE usuario = ?");
    $stmt->bind_param("is", $avatar_id, $usuario);
    $stmt->execute();
} else {
    // Intentar en lectores primero
    $stmt = $con->prepare("UPDATE lectores SET avatar_id = ? WHERE usuario = ?");
    $stmt->bind_param("is", $avatar_id, $usuario);
    $stmt->execute();
    // Fallback: si no se actualizó ninguna fila, buscar en usuarios (sesión legacy)
    if($stmt->affected_rows === 0){
        $stmt = $con->prepare("UPDATE usuarios SET avatar_id = ? WHERE usuario = ?");
        $stmt->bind_param("is", $avatar_id, $usuario);
        $stmt->execute();
    }
}

echo json_encode(['ok'=>true]);
