<?php
session_start();
include("../data/conexion.php");
include("../views/helpers/urlhelper.php");

if(!isset($_SESSION['usuario'])){
    header('Location: ' . basePath() . '/login');
    exit;
}

$usuario = $_SESSION['usuario'];
$tipo = $_SESSION['tipo'] ?? 'lector';
$correo = trim($_POST['correo'] ?? '');
$pass_actual = $_POST['pass_actual'] ?? '';
$pass_nueva = $_POST['pass_nueva'] ?? '';
$sexo = $_POST['sexo'] ?? null;
$nacimiento = $_POST['nacimiento'] ?? null;
$entidad = $_POST['entidad'] ?? null;
$biografia = trim($_POST['biografia'] ?? '');
$link_twitter = trim($_POST['link_twitter'] ?? '');
$link_instagram = trim($_POST['link_instagram'] ?? '');

// ============================
// VERIFICAR CONTRASEÑA ACTUAL
// ============================
if(!empty($pass_actual)){
    if($tipo === 'admin'){
        $stmt = $con->prepare("SELECT pass FROM usuarios WHERE usuario = ?");
    } else {
        $stmt = $con->prepare("SELECT password_hash AS pass FROM lectores WHERE usuario = ?");
    }
    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    $passValida = ($pass_actual === $row['pass']) || password_verify($pass_actual, $row['pass']);
    if(!$passValida){
        header('Location: ' . basePath() . '/perfil?error=1');
        exit;
    }
}

// ============================
// CONSTRUIR UPDATE DINÁMICO
// ============================
// ============================
// FOTO PERSONAL (solo admin/editor)
// ============================
$foto_personal = null;
if($tipo === 'admin' && isset($_FILES['foto_personal']) && $_FILES['foto_personal']['error'] === UPLOAD_ERR_OK){
    $file = $_FILES['foto_personal'];
    $mime = mime_content_type($file['tmp_name']);
    $permitidos = ['image/jpeg', 'image/png', 'image/webp'];
    if(in_array($mime, $permitidos)){
        $imagen = imagecreatefromstring(file_get_contents($file['tmp_name']));
        if($imagen){
            $dir = __DIR__ . '/../img/editores/';
            if(!is_dir($dir)) mkdir($dir, 0755, true);
            // Obtener id_u para nombre de archivo
            $stmtId = $con->prepare("SELECT id_u, foto_personal FROM usuarios WHERE usuario = ?");
            $stmtId->bind_param("s", $usuario);
            $stmtId->execute();
            $rowId = $stmtId->get_result()->fetch_assoc();
            // Borrar foto anterior
            if(!empty($rowId['foto_personal']) && file_exists(__DIR__ . '/../' . $rowId['foto_personal'])){
                unlink(__DIR__ . '/../' . $rowId['foto_personal']);
            }
            $nombreArchivo = 'editor_' . $rowId['id_u'] . '_' . time() . '.webp';
            imagewebp($imagen, $dir . $nombreArchivo, 92);
            imagedestroy($imagen);
            $foto_personal = 'img/editores/' . $nombreArchivo;
        }
    }
}

if($tipo === 'admin'){
    // Admin → tabla usuarios
    $campos = "correo=?, sexo=?, fecha_nacimiento=?, entidad=?, biografia=?, link_twitter=?, link_instagram=?";
    $tipos = "sssssss";
    $valores = [$correo, $sexo ?: null, $nacimiento ?: null, $entidad ?: null, $biografia ?: null, $link_twitter ?: null, $link_instagram ?: null];
    if($foto_personal){
        $campos .= ", foto_personal=?";
        $tipos .= "s";
        $valores[] = $foto_personal;
    }
    if(!empty($pass_nueva) && !empty($pass_actual)){
        $campos .= ", pass=?";
        $tipos .= "s";
        $valores[] = password_hash($pass_nueva, PASSWORD_BCRYPT);
    }
    $tipos .= "s";
    $valores[] = $usuario;
    $stmt = $con->prepare("UPDATE usuarios SET $campos WHERE usuario=?");
} else {
    // Lector → tabla lectores
    $campos = "correo=?, sexo=?, fecha_nacimiento=?, entidad=?";
    $tipos = "ssss";
    $valores = [$correo, $sexo ?: null, $nacimiento ?: null, $entidad ?: null];
    if(!empty($pass_nueva) && !empty($pass_actual)){
        $campos .= ", password_hash=?";
        $tipos .= "s";
        $valores[] = password_hash($pass_nueva, PASSWORD_BCRYPT);
    }
    $tipos .= "s";
    $valores[] = $usuario;
    $stmt = $con->prepare("UPDATE lectores SET $campos WHERE usuario=?");
}
$stmt->bind_param($tipos, ...$valores);

if($stmt->execute()){
    header('Location: ' . basePath() . '/perfil?ok=1');
} else {
    header('Location: ' . basePath() . '/perfil?error=2');
}
exit;
