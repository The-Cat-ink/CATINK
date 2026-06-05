<?php
date_default_timezone_set("America/Mexico_City");
include(__DIR__ . "/../data/conexion.php");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ./../views/olvide_contrasena.php?error=Método no permitido");
    exit();
}

$token = trim($_POST['token'] ?? '');
$password = $_POST['password'] ?? '';
$password_confirm = $_POST['password_confirm'] ?? '';

// Validaciones
if (empty($token)) {
    header("Location: ./../views/olvide_contrasena.php?error=Token no proporcionado");
    exit();
}

if (empty($password) || empty($password_confirm)) {
    header("Location: ./../views/reset_contrasena.php?token=" . urlencode($token) . "&error=Las contraseñas son requeridas");
    exit();
}

if ($password !== $password_confirm) {
    header("Location: ./../views/reset_contrasena.php?token=" . urlencode($token) . "&error=Las contraseñas no coinciden");
    exit();
}

if (strlen($password) < 8) {
    header("Location: ./../views/reset_contrasena.php?token=" . urlencode($token) . "&error=La contraseña debe tener al menos 8 caracteres");
    exit();
}

// Validar token
$stmt = $con->prepare("SELECT id, email, tipo_usuario FROM password_reset_tokens WHERE token = ? AND usado = 0 AND expira > NOW()");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: ./../views/olvide_contrasena.php?error=Token inválido o expirado");
    exit();
}

$token_data = $result->fetch_assoc();
$email = $token_data['email'];
$tipo_usuario = $token_data['tipo_usuario'];

// Hashear contraseña
$password_hash = password_hash($password, PASSWORD_BCRYPT);

try {
    // Actualizar contraseña según tipo de usuario
    if ($tipo_usuario === 'admin') {
        $stmt = $con->prepare("UPDATE usuarios SET pass = ? WHERE correo = ?");
        $stmt->bind_param("ss", $password_hash, $email);
        $stmt->execute();
    } else {
        $stmt = $con->prepare("UPDATE lectores SET password_hash = ? WHERE correo = ?");
        $stmt->bind_param("ss", $password_hash, $email);
        $stmt->execute();
    }

    // Marcar token como usado
    $stmt = $con->prepare("UPDATE password_reset_tokens SET usado = 1 WHERE token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();

    // Redirigir a login con mensaje de éxito
    header("Location: ./../views/login.php?mensaje=Contraseña actualizada correctamente. Inicia sesión con tu nueva contraseña");
    exit();

} catch (Exception $e) {
    error_log("Error actualizando contraseña: " . $e->getMessage());
    header("Location: ./../views/reset_contrasena.php?token=" . urlencode($token) . "&error=Error al actualizar la contraseña");
    exit();
}
?>
