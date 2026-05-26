<?php
include("../data/conexion.php");
include("../views/helpers/urlhelper.php");

$nombre  = trim($_POST['nombre'] ?? '');
$usuario = trim($_POST['usuario'] ?? '');
$correo  = trim($_POST['correo'] ?? '');
$pass    = $_POST['pass'] ?? '';
$pass2   = $_POST['pass2'] ?? '';
$fecha_nacimiento = trim($_POST['fecha_nacimiento'] ?? '');
$sexo    = trim($_POST['sexo'] ?? '');
$entidad = trim($_POST['entidad'] ?? '');

// ============================
// VALIDACIONES
// ============================
if (empty($nombre) || empty($usuario) || empty($correo) || empty($pass) || empty($pass2) || empty($fecha_nacimiento) || empty($sexo) || empty($entidad)) {
    header('Location: ' . basePath() . '/login?modo=registro&reg_error=1');
    exit;
}
if ($pass !== $pass2) {
    header('Location: ' . basePath() . '/login?modo=registro&reg_error=2');
    exit;
}

// ============================
// VERIFICAR DUPLICADOS
// ============================
$stmt = $con->prepare("SELECT id_u FROM usuarios WHERE usuario = ?");
$stmt->bind_param("s", $usuario);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    header('Location: ' . basePath() . '/login?modo=registro&reg_error=3');
    exit;
}

$stmt = $con->prepare("SELECT id_u FROM usuarios WHERE correo = ?");
$stmt->bind_param("s", $correo);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    header('Location: ' . basePath() . '/login?modo=registro&reg_error=4');
    exit;
}

// ============================
// INSERTAR USUARIO PÚBLICO (perm_* = 0)
// ============================
$passHash = password_hash($pass, PASSWORD_BCRYPT);

$stmt = $con->prepare("
    INSERT INTO usuarios 
    (nombre, usuario, correo, pass, fecha_nacimiento, sexo, entidad, perm_categorias, perm_noticias, perm_publicidad, perm_suscripciones, perm_usuarios, perm_correos, perm_videos)
    VALUES (?, ?, ?, ?, ?, ?, ?, 0, 0, 0, 0, 0, 0, 0)
");
$stmt->bind_param("sssssss", $nombre, $usuario, $correo, $passHash, $fecha_nacimiento, $sexo, $entidad);

if ($stmt->execute()) {
    header('Location: ' . basePath() . '/login?registro=ok');
    exit;
} else {
    header('Location: ' . basePath() . '/login?modo=registro&reg_error=5');
    exit;
}
