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
$perm_correos       = calcPerm($_POST['correos'] ?? []);
$perm_videos        = calcPerm($_POST['videos'] ?? []);
$perm_lectores      = calcPerm($_POST['lectores'] ?? []);
$perm_recomendados  = calcPerm($_POST['recomendados'] ?? []);
$perm_esperamos     = calcPerm($_POST['esperamos'] ?? []);
$perm_paginas       = calcPerm($_POST['paginas'] ?? []);
$perm_actividad     = calcPerm($_POST['actividad'] ?? []);
$perm_papelera      = calcPerm($_POST['papelera'] ?? []);
$perm_avatares      = calcPerm($_POST['avatares'] ?? []);

// ========================
// Hash seguro de contraseña
// ========================
$passHash = password_hash($password, PASSWORD_BCRYPT);

// ========================
// Insertar usuario
// ========================
// mysqli lanza excepción si la consulta no se puede preparar (por ejemplo,
// si a la tabla le faltan las columnas de permisos de la migración 011).
// Sin capturarla, el error sale como un 500 mudo que no dice nada.
try {
    $alt = $con->prepare("
    INSERT INTO usuarios
    (nombre, usuario, correo, pass, perm_publicidad, perm_noticias, perm_categorias, perm_suscripciones, perm_usuarios, perm_correos, perm_videos, perm_lectores, perm_recomendados, perm_esperamos, perm_paginas, perm_actividad, perm_papelera, perm_avatares)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $alt->bind_param(
        "ssssiiiiiiiiiiiiii",
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
        $perm_videos,
        $perm_lectores,
        $perm_recomendados,
        $perm_esperamos,
        $perm_paginas,
        $perm_actividad,
        $perm_papelera,
        $perm_avatares
    );

    $alt->execute();
    logActivity($con, 'crear', 'usuarios', 'Creó usuario administrador «' . $usuario . '» (' . $nombre . ')');
    echo json_encode(['success' => 'Usuario creado correctamente']);

} catch (\Throwable $e) {
    error_log('Error al crear usuario: ' . $e->getMessage());
    $detalle = 'Error al registrar usuario.';
    // El detalle técnico solo para el superadmin.
    if (!empty($_SESSION['superadmin'])) {
        $detalle .= ' ' . $e->getMessage();
    }
    echo json_encode(['error' => $detalle]);
}
