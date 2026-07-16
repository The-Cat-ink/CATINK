<?php
include("./../controllers/aclcontroller.php");
proteger('usuarios', 'editar');
include("./../data/conexion.php");
require_once("./../views/helpers/activity_log.php");
header('Content-Type: application/json');


// ========================
// DATOS
// ========================
$id      = intval($_POST['id']);
$nombre  = $_POST['nombre'];
$usuario = $_POST['usuario'];
$email   = $_POST['email'];
$password = $_POST['password'] ?? "";

// ========================
// Validar duplicados en usuarios
// ========================
$stmtCheck = $con->prepare("SELECT id_u FROM usuarios WHERE (usuario = ? OR correo = ?) AND id_u != ?");
$stmtCheck->bind_param("ssi", $usuario, $email, $id);
$stmtCheck->execute();
if ($stmtCheck->get_result()->num_rows > 0) {
    echo json_encode(['error' => 'El nombre de usuario o correo electrónico ya está registrado por otro administrador/editor']);
    exit;
}

// ========================
// Validar duplicados en lectores
// ========================
$stmtCheckL = $con->prepare("SELECT id FROM lectores WHERE usuario = ? OR correo = ?");
$stmtCheckL->bind_param("ss", $usuario, $email);
$stmtCheckL->execute();
if ($stmtCheckL->get_result()->num_rows > 0) {
    echo json_encode(['error' => 'El nombre de usuario o correo electrónico ya está registrado por un lector']);
    exit;
}

// ========================
// FUNCIÓN PERMISOS BITMASK
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
// SI NO CAMBIA CONTRASEÑA
// ========================
// Todo el bloque va dentro del try: mysqli lanza excepción ya en prepare() si
// a la tabla le faltan las columnas de permisos de la migración 011, y sin
// capturarla el fallo sale como un 500 mudo.
try {
if(empty($password)){
    $stmt = $con->prepare("
        UPDATE usuarios SET
        nombre=?, usuario=?, correo=?,
        perm_publicidad=?, perm_noticias=?, perm_categorias=?, perm_suscripciones=?, perm_usuarios=?, perm_correos=?, perm_videos=?, perm_lectores=?, perm_recomendados=?, perm_esperamos=?, perm_paginas=?, perm_actividad=?, perm_papelera=?, perm_avatares=?
        WHERE id_u=?
    ");
    $stmt->bind_param(
        "sssiiiiiiiiiiiiiii",
        $nombre, $usuario, $email,
        $perm_publicidad, $perm_noticias, $perm_categorias, $perm_suscripciones, $perm_usuarios, $perm_correos, $perm_videos,
        $perm_lectores, $perm_recomendados, $perm_esperamos, $perm_paginas, $perm_actividad, $perm_papelera, $perm_avatares,
        $id
    );
// ========================
// SI CAMBIA CONTRASEÑA
// ========================
} else {
    if($password !== $_POST['confirm_password']){
        echo json_encode(['error' => 'Las contraseñas no coinciden']);
        exit;
    }
    $passHash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $con->prepare("
        UPDATE usuarios SET
        nombre=?, usuario=?, correo=?, pass=?,
        perm_publicidad=?, perm_noticias=?, perm_categorias=?, perm_suscripciones=?, perm_usuarios=?, perm_correos=?, perm_videos=?, perm_lectores=?, perm_recomendados=?, perm_esperamos=?, perm_paginas=?, perm_actividad=?, perm_papelera=?, perm_avatares=?
        WHERE id_u=?
    ");
    $stmt->bind_param(
        "ssssiiiiiiiiiiiiiii",
        $nombre, $usuario, $email, $passHash,
        $perm_publicidad, $perm_noticias, $perm_categorias, $perm_suscripciones, $perm_usuarios, $perm_correos, $perm_videos,
        $perm_lectores, $perm_recomendados, $perm_esperamos, $perm_paginas, $perm_actividad, $perm_papelera, $perm_avatares,
        $id
    );
}
// ========================
// EJECUTAR
// ========================
    $stmt->execute();
    logActivity($con, 'editar', 'usuarios', 'Actualizó usuario administrador ID ' . $id . ' («' . $usuario . '»)');
    echo json_encode(['success' => 'Usuario actualizado correctamente']);
} catch (\Throwable $e) {
    error_log('Error al actualizar usuario: ' . $e->getMessage());
    $detalle = 'Error al actualizar el usuario.';
    if (!empty($_SESSION['superadmin'])) {
        $detalle .= ' ' . $e->getMessage();
    }
    echo json_encode(['error' => $detalle]);
}