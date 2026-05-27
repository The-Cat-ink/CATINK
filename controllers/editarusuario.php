<?php
include("./../controllers/aclcontroller.php");
proteger('usuarios', 'editar');
include("./../data/conexion.php");

// ========================
// DATOS
// ========================
$id      = intval($_POST['id']);
$nombre  = $_POST['nombre'];
$usuario = $_POST['usuario'];
$email   = $_POST['email'];
$password = $_POST['password'] ?? "";
$biografia = trim($_POST['biografia'] ?? '');
$link_twitter = trim($_POST['link_twitter'] ?? '');
$link_instagram = trim($_POST['link_instagram'] ?? '');

// ========================
// FOTO PERSONAL (WebP)
// ========================
$foto_personal = null;
if (isset($_FILES['foto_personal']) && $_FILES['foto_personal']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['foto_personal'];
    $mime = mime_content_type($file['tmp_name']);
    $permitidos = ['image/jpeg', 'image/png', 'image/webp'];
    if (in_array($mime, $permitidos)) {
        $imagen = imagecreatefromstring(file_get_contents($file['tmp_name']));
        if ($imagen) {
            $dir = __DIR__ . '/../img/editores/';
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            // Borrar foto anterior si existe
            $stmtFoto = $con->prepare("SELECT foto_personal FROM usuarios WHERE id_u = ?");
            $stmtFoto->bind_param("i", $id);
            $stmtFoto->execute();
            $fotoAnterior = $stmtFoto->get_result()->fetch_assoc();
            if (!empty($fotoAnterior['foto_personal']) && file_exists(__DIR__ . '/../' . $fotoAnterior['foto_personal'])) {
                unlink(__DIR__ . '/../' . $fotoAnterior['foto_personal']);
            }
            $nombreArchivo = 'editor_' . $id . '_' . time() . '.webp';
            imagewebp($imagen, $dir . $nombreArchivo, 92);
            imagedestroy($imagen);
            $foto_personal = 'img/editores/' . $nombreArchivo;
        }
    }
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
// ACTUALIZAR PERFIL PÚBLICO
// ========================
$stmtPerfil = $con->prepare("UPDATE usuarios SET biografia=?, link_twitter=?, link_instagram=? WHERE id_u=?");
$stmtPerfil->bind_param("sssi", $biografia, $link_twitter, $link_instagram, $id);
$stmtPerfil->execute();

if ($foto_personal) {
    $stmtFotoUp = $con->prepare("UPDATE usuarios SET foto_personal=? WHERE id_u=?");
    $stmtFotoUp->bind_param("si", $foto_personal, $id);
    $stmtFotoUp->execute();
}
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
if($stmt->execute()){
    header("Location: ./../views/usuarios.php?update=ok");
} else {
    die("Error al actualizar: " . $stmt->error);
}