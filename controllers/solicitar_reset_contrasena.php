<?php
date_default_timezone_set("America/Mexico_City");
include(__DIR__ . "/../data/env.php");
include(__DIR__ . "/../data/conexion.php");
require_once(__DIR__ . "/../views/helpers/urlhelper.php");

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require(__DIR__."/../PHPMailer/src/PHPMailer.php");
require(__DIR__."/../PHPMailer/src/Exception.php");
require(__DIR__."/../PHPMailer/src/SMTP.php");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ./../views/olvide_contrasena.php?error=Método no permitido");
    exit();
}

$email = trim($_POST['email'] ?? '');

if (empty($email)) {
    header("Location: ./../views/olvide_contrasena.php?error=El correo es requerido");
    exit();
}

// Validar que sea un email válido
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: ./../views/olvide_contrasena.php?error=Correo inválido");
    exit();
}

// Buscar en ambas tablas
$tipo_usuario = null;
$usuario = null;

// Buscar en usuarios (admin)
$stmt = $con->prepare("SELECT id_u, nombre, correo FROM usuarios WHERE correo = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $tipo_usuario = 'admin';
    $usuario = $result->fetch_assoc();
}

// Si no encontró en usuarios, buscar en lectores
if (!$usuario) {
    $stmt = $con->prepare("SELECT id, nombre, correo FROM lectores WHERE correo = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $tipo_usuario = 'lector';
        $usuario = $result->fetch_assoc();
    }
}

// Si no encontró el usuario, mostrar mensaje genérico por seguridad
if (!$usuario) {
    header("Location: ./../views/olvide_contrasena.php?mensaje=Si el correo existe en nuestro sistema, recibirás un enlace de recuperación");
    exit();
}

// Generar token único
$token = bin2hex(random_bytes(32));
$expira = date("Y-m-d H:i:s", strtotime("+24 hours"));

// Guardar token en BD
$stmt = $con->prepare("INSERT INTO password_reset_tokens (email, token, tipo_usuario, expira) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss", $email, $token, $tipo_usuario, $expira);
$stmt->execute();

// Enviar correo con enlace de reset
try {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = env('SMTP_HOST');
    $mail->SMTPAuth = true;
    $mail->Username = env('SMTP_USERNAME');
    $mail->Password = env('SMTP_PASSWORD');
    $mail->SMTPSecure = env('SMTP_SECURE', 'tls');
    $mail->Port = (int) env('SMTP_PORT', 587);

    $mail->setFrom(env('SMTP_FROM_EMAIL'), env('SMTP_FROM_NAME'));
    $mail->addAddress($email, $usuario['nombre']);

    $resetUrl = siteUrl() . "/reset_contrasena?token=" . urlencode($token);

    $mail->isHTML(true);
    $mail->Subject = "Recuperar tu contraseña en CatInk";

    $htmlBody = "
    <div style='font-family: Arial, sans-serif; max-width:600px; margin:auto; background:#f9f9f9; padding:20px; border-radius:10px;'>
        <h2 style='color:#EF3363;'>Recuperar Contraseña</h2>
        <p>Hola <strong>{$usuario['nombre']}</strong>,</p>
        <p>Recibimos una solicitud para recuperar tu contraseña. Haz clic en el botón de abajo para establecer una nueva contraseña.</p>
        <p style='text-align:center; margin:30px 0;'>
            <a href='{$resetUrl}' style='display:inline-block; padding:12px 30px; background:#EF3363; color:#fff; text-decoration:none; border-radius:5px; font-weight:bold;'>
                Recuperar Contraseña
            </a>
        </p>
        <p style='color:#666; font-size:12px;'>Este enlace expira en 24 horas.</p>
        <p style='color:#666; font-size:12px;'>Si no solicitaste esta recuperación, ignora este correo.</p>
        <hr style='border:none; border-top:1px solid #ddd; margin:20px 0;'>
        <p style='color:#999; font-size:11px; text-align:center;'>© 2026 CatInk. Todos los derechos reservados.</p>
    </div>";

    $mail->Body = $htmlBody;
    $mail->send();

    header("Location: ./../views/olvide_contrasena.php?mensaje=Se ha enviado un enlace de recuperación a tu correo");
    exit();

} catch (Exception $e) {
    error_log("Error enviando correo de reset: " . $e->getMessage());
    header("Location: ./../views/olvide_contrasena.php?error=Error al enviar el correo. Intenta más tarde");
    exit();
}
?>
