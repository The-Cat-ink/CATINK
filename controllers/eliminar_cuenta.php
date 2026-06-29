<?php
/**
 * Eliminar cuenta de lector o usuario admin.
 * Requiere confirmación de contraseña.
 * Solo se ejecuta desde el formulario de perfil.
 */
session_start();
include("../data/conexion.php");
include("../views/helpers/urlhelper.php");

// Solo usuarios autenticados
if(!isset($_SESSION['usuario'])){
    header('Location: ' . basePath() . '/login');
    exit;
}

// Solo POST
if($_SERVER['REQUEST_METHOD'] !== 'POST'){
    header('Location: ' . basePath() . '/perfil');
    exit;
}

$usuario = $_SESSION['usuario'];
$tipo    = $_SESSION['tipo'] ?? 'lector';
$pass    = $_POST['pass_confirmar'] ?? '';

if(empty($pass)){
    header('Location: ' . basePath() . '/perfil?error=5');
    exit;
}

// ==========================
// VERIFICAR CONTRASEÑA
// ==========================
if($tipo === 'admin'){
    $stmt = $con->prepare("SELECT id_u AS id, pass AS hash FROM usuarios WHERE usuario = ?");
} else {
    $stmt = $con->prepare("SELECT id AS id, password_hash AS hash FROM lectores WHERE usuario = ?");
}
$stmt->bind_param("s", $usuario);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

if(!$row){
    header('Location: ' . basePath() . '/perfil?error=5');
    exit;
}

$passValida = ($pass === $row['hash']) || password_verify($pass, $row['hash']);
if(!$passValida){
    header('Location: ' . basePath() . '/perfil?error=5');
    exit;
}

$id = (int)$row['id'];

// ==========================
// ELIMINAR DATOS ASOCIADOS
// ==========================
if($tipo === 'lector'){
    // Likes de comentarios hechos por el lector
    $con->prepare("DELETE FROM likes_comentarios WHERE lector_id = ?")->execute() || true;
    $stmtLk = $con->prepare("DELETE FROM likes_comentarios WHERE lector_id = ?");
    $stmtLk->bind_param("i", $id);
    $stmtLk->execute();

    // Likes de noticias hechos por el lector (identificados por IP no hay FK, se dejan)

    // Reportes de comentarios
    $stmtRp = $con->prepare("DELETE FROM reportes_comentarios WHERE lector_id = ?");
    $stmtRp->bind_param("i", $id);
    $stmtRp->execute();

    // Comentarios (poner en eliminado para preservar hilo si hay respuestas, o borrar directamente)
    $stmtCom = $con->prepare("DELETE FROM comentarios WHERE lector_id = ?");
    $stmtCom->bind_param("i", $id);
    $stmtCom->execute();

    // Suscripciones ligadas al correo del lector
    $stmtSub = $con->prepare("DELETE FROM suscripciones WHERE correo = (SELECT correo FROM lectores WHERE id = ?)");
    $stmtSub->bind_param("i", $id);
    $stmtSub->execute();

    // Eliminar la cuenta
    $stmtDel = $con->prepare("DELETE FROM lectores WHERE id = ?");
    $stmtDel->bind_param("i", $id);
    $ok = $stmtDel->execute();
} else {
    // Para admins: solo desautenticamos, no eliminamos la cuenta para evitar
    // dejar noticias sin autor ni romper integridad referencial.
    // Redirige con error informativo.
    header('Location: ' . basePath() . '/perfil?error=6');
    exit;
}

if(!$ok){
    header('Location: ' . basePath() . '/perfil?error=6');
    exit;
}

// ==========================
// CERRAR SESIÓN Y REDIRIGIR
// ==========================
session_destroy();
header('Location: ' . basePath() . '/?deleted=1');
exit;
