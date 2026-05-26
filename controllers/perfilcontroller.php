<?php
session_start();
include("../data/conexion.php");
include("../views/helpers/urlhelper.php");

if(!isset($_SESSION['usuario'])){
    header('Location: ' . basePath() . '/login');
    exit;
}

$usuario = $_SESSION['usuario'];
$correo = trim($_POST['correo'] ?? '');
$pass_actual = $_POST['pass_actual'] ?? '';
$pass_nueva = $_POST['pass_nueva'] ?? '';
$sexo = $_POST['sexo'] ?? null;
$nacimiento = $_POST['nacimiento'] ?? null;
$entidad = $_POST['entidad'] ?? null;

// ============================
// VERIFICAR CONTRASEÑA ACTUAL
// ============================
if(!empty($pass_actual)){
    $stmt = $con->prepare("SELECT pass FROM usuarios WHERE usuario = ?");
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
$campos = "correo=?, sexo=?, fecha_nacimiento=?, entidad=?";
$tipos = "ssss";
$valores = [$correo, $sexo ?: null, $nacimiento ?: null, $entidad ?: null];

// Si quiere cambiar contraseña
if(!empty($pass_nueva) && !empty($pass_actual)){
    $campos .= ", pass=?";
    $tipos .= "s";
    $valores[] = password_hash($pass_nueva, PASSWORD_BCRYPT);
}

$tipos .= "s";
$valores[] = $usuario;

$stmt = $con->prepare("UPDATE usuarios SET $campos WHERE usuario=?");
$stmt->bind_param($tipos, ...$valores);

if($stmt->execute()){
    header('Location: ' . basePath() . '/perfil?ok=1');
} else {
    header('Location: ' . basePath() . '/perfil?error=2');
}
exit;
