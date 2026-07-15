<?php
include("./../controllers/aclcontroller.php");
proteger('usuarios', 'crear');
include("./../data/conexion.php");
require_once("./../views/helpers/activity_log.php");
header('Content-Type: application/json');


// ========================
// Validar campos obligatorios
// ========================
if (!isset($_POST['nombre'], $_POST['usuario'], $_POST['email'], $_POST['password'], $_POST['confirm_password'])) {
    echo json_encode(['error' => 'Faltan datos obligatorios']);
    exit;
}
$nombre   = trim($_POST['nombre']);
$usuario  = trim($_POST['usuario']);
$email    = trim($_POST['email']);
$password = $_POST['password'];
$passConfirm = $_POST['confirm_password'];

// ========================
// Validar contraseñas
// ========================
if ($password !== $passConfirm) {
    echo json_encode(['error' => 'Las contraseñas no coinciden']);
    exit;
}

// ========================
// Validar usuario o correo existente en usuarios
// ========================
$stmt = $con->prepare("SELECT id_u FROM usuarios WHERE usuario = ? OR correo = ?");
$stmt->bind_param("ss", $usuario, $email);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    echo json_encode(['error' => 'El nombre de usuario o correo electrónico ya está registrado como administrador/editor']);
    exit;
}

// ========================
// Validar usuario o correo existente en lectores
// ========================
$stmt = $con->prepare("SELECT id FROM lectores WHERE usuario = ? OR correo = ?");
$stmt->bind_param("ss", $usuario, $email);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    echo json_encode(['error' => 'El nombre de usuario o correo electrónico ya está registrado por un lector']);
    exit;
}

// ========================
// FUNCIÓN BITMASK LINUX
// ========================
function calcPerm($arr){
    $perm = 0;
    if(isset($arr)){
        foreach($arr as $v){
            $perm += (int)$v;
        }
    }
    return $perm;
}

// ========================
// Obtener permisos
// ========================
$perm_publicidad    = calcPerm($_POST['publicidad'] ?? []);
$perm_noticias      = calcPerm($_POST['noticias'] ?? []);
$perm_categorias    = calcPerm($_POST['categorias'] ?? []);
$perm_suscripciones = calcPerm($_POST['suscripciones'] ?? []);
$perm_usuarios      = calcPerm($_POST['usuarios'] ?? []);
$perm_correos = calcPerm($_POST['correos'] ?? []);
$perm_videos = calcPerm($_POST['videos'] ?? []);

// ========================
// Hash seguro de contraseña
// ========================
$passHash = password_hash($password, PASSWORD_BCRYPT);

// ========================
// Insertar usuario
// ========================
$alt = $con->prepare("
INSERT INTO usuarios 
(nombre, usuario, correo, pass, perm_publicidad, perm_noticias, perm_categorias, perm_suscripciones, perm_usuarios, perm_correos, perm_videos)
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$alt->bind_param(
    "ssssiiiiiii",
    $nombre,
    $usuario,
    $email,
    $passHash,
    $perm_publicidad,
    $perm_noticias,
    $perm_categorias,
    $perm_suscripciones,
    $perm_usuarios,
    $perm_correos,
    $perm_videos
);

if ($alt->execute()) {
    logActivity($con, 'crear', 'usuarios', 'Creó usuario administrador «' . $usuario . '» (' . $nombre . ')');
    echo json_encode(['success' => 'Usuario creado correctamente']);
} else {
    echo json_encode(['error' => 'Error al registrar usuario']);
}
