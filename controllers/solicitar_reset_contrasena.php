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
    $mail->Timeout = 10;

    $mail->Host       = env('SMTP_HOST');
    $mail->SMTPAuth   = true;
    $mail->Username   = env('SMTP_AUTH_USERNAME', env('SMTP_USERNAME'));
    $mail->Password   = env('SMTP_AUTH_PASSWORD', env('SMTP_PASSWORD'));
    $mail->SMTPSecure = env('SMTP_SECURE', 'tls');
    $mail->Port       = (int) env('SMTP_PORT', 587);

    // Ignorar errores de certificado SSL en servidor de produccion
    $mail->SMTPOptions = array(
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        )
    );

    $fromEmail = env('SMTP_AUTH_FROM_EMAIL', env('SMTP_NOREPLY_FROM_EMAIL', 'no-reply@catink.com.mx'));
    $fromName  = env('SMTP_AUTH_FROM_NAME', env('SMTP_NOREPLY_FROM_NAME', 'CatInk'));
    $mail->setFrom($fromEmail, $fromName);
    $mail->addAddress($email, $usuario['nombre']);

    require_once(__DIR__ . "/../views/helpers/emailhelper.php");

    $resetUrl = siteUrl() . "/reset_contrasena?token=" . urlencode($token);

    $mail->isHTML(true);
    $mail->Subject = "Recuperar tu contraseña en CatInk";

    $nombreUsuario = htmlspecialchars($usuario['nombre']);
    $content = "
        <p>Hola <strong>{$nombreUsuario}</strong>,</p>
        <p style='color:#334155; line-height:1.7;'>Recibimos una solicitud para restablecer la contraseña de tu cuenta en CatInk. Haz clic en el botón a continuación para definir una nueva contraseña:</p>
        <p style='color:#718096; font-size:12px; margin-top:20px;'>* Este enlace de recuperación expira en 24 horas por razones de seguridad.<br>Si no solicitaste este cambio, puedes ignorar este correo de forma segura.</p>
    ";

    $mail->Body = renderCatInkEmail([
        'title'     => 'Recuperación de Contraseña',
        'badge'     => 'Seguridad de Cuenta',
        'content'   => $content,
        'cta_url'   => $resetUrl,
        'cta_text'  => 'Restablecer mi Contraseña'
    ]);
    $mail->send();

    header("Location: ./../views/olvide_contrasena.php?mensaje=Se ha enviado un enlace de recuperación a tu correo");
    exit();

} catch (\Throwable $e) {
    error_log("Error enviando correo de reset: " . $e->getMessage());
    $msg = "Error al enviar correo: " . $e->getMessage();
    header("Location: ./../views/olvide_contrasena.php?error=" . urlencode($msg));
    exit();
}
?>
