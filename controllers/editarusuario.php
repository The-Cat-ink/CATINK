<?php
include("./../controllers/aclcontroller.php");
proteger('usuarios', 'editar');
include("./../data/conexion.php");
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
$perm_videos       = calcPerm($_POST['videos'] ?? []);
// ========================
// SI NO CAMBIA CONTRASEÑA
// ========================
if(empty($password)){
    $stmt = $con->prepare("
        UPDATE usuarios SET
        nombre=?, usuario=?, correo=?,
        perm_publicidad=?, perm_noticias=?, perm_categorias=?, perm_suscripciones=?, perm_usuarios=?, perm_correos=?, perm_videos=?
        WHERE id_u=?
    ");
    $stmt->bind_param(
        "sssiiiiiiii",
        $nombre, $usuario, $email,
        $perm_publicidad, $perm_noticias, $perm_categorias, $perm_suscripciones, $perm_usuarios, $perm_correos, $perm_videos,
        $id
    );
// ========================
// SI CAMBIA CONTRASEÑA
// ========================
} else {
    if($password !== $_POST['confirm_password']){
        header("Location: ./../views/editaru.php?id=$id&error=pass");
        exit;
    }
    $passHash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $con->prepare("
        UPDATE usuarios SET
        nombre=?, usuario=?, correo=?, pass=?,
        perm_publicidad=?, perm_noticias=?, perm_categorias=?, perm_suscripciones=?, perm_usuarios=?, perm_correos=?, perm_videos=?
        WHERE id_u=?
    ");
    $stmt->bind_param(
        "ssssiiiiiiii",
        $nombre, $usuario, $email, $passHash,
        $perm_publicidad, $perm_noticias, $perm_categorias, $perm_suscripciones, $perm_usuarios, $perm_correos, $perm_videos,
        $id
    );
}
// ========================
// EJECUTAR
// ========================
if ($stmt->execute()) {
    echo json_encode(['success' => 'Usuario actualizado correctamente']);
} else {
    echo json_encode(['error' => 'Error al actualizar: ' . $stmt->error]);
}