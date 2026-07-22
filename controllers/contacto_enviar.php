<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once(__DIR__ . "/../data/env.php");
require_once(__DIR__ . "/../PHPMailer/src/PHPMailer.php");
require_once(__DIR__ . "/../PHPMailer/src/Exception.php");
require_once(__DIR__ . "/../PHPMailer/src/SMTP.php");

$nombre  = trim($_POST['nombre']  ?? '');
$email   = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$asunto  = trim($_POST['asunto']  ?? 'Consulta general');
$mensaje = trim($_POST['mensaje'] ?? '');

if (empty($nombre) || !$email || empty($mensaje)) {
    echo json_encode(['error' => 'Por favor completa todos los campos obligatorios.']);
    exit;
}

try {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = env('SMTP_HOST', 'smtp.hostinger.com');
    $mail->SMTPAuth   = true;
    $mail->Username   = env('SMTP_USERNAME', env('SMTP_FROM_EMAIL'));
    $mail->Password   = env('SMTP_PASSWORD', '');
    $mail->SMTPSecure = env('SMTP_SECURE', 'ssl');
    $mail->Port       = intval(env('SMTP_PORT', 465));
    $mail->CharSet    = 'UTF-8';

    $fromEmail = env('SMTP_FROM_EMAIL', 'no-reply@catink.com.mx');
    $fromName  = env('SMTP_FROM_NAME',  'CatInk');

    $mail->setFrom($fromEmail, $fromName);
    $mail->addAddress('contacto@catink.com.mx', 'CatInk Contacto');
    $mail->addReplyTo($email, $nombre);

    $mail->isHTML(true);
    $mail->Subject = "Nuevo mensaje de contacto: $asunto — $nombre";

    $bodyHtml = "
    <div style='font-family: Arial, sans-serif; max-width:600px; margin:auto; background:#121216; color:#ffffff; padding:24px; border-radius:12px; border:1px solid #222;'>
        <h2 style='color:#EF3363; margin-top:0;'>Nuevo mensaje de contacto</h2>
        <p style='color:#ddd;'>Se recibió un nuevo mensaje a través del formulario de contacto del sitio web.</p>
        <hr style='border-color:#333; margin:16px 0;'>
        <p style='color:#fff;'><strong>Nombre:</strong> " . htmlspecialchars($nombre) . "</p>
        <p style='color:#fff;'><strong>Correo:</strong> <a href='mailto:$email' style='color:#EF3363;'>$email</a></p>
        <p style='color:#fff;'><strong>Asunto:</strong> " . htmlspecialchars($asunto) . "</p>
        <div style='background:#1a1a20; padding:16px; border-radius:8px; margin-top:14px; border-left:4px solid #EF3363;'>
            <strong style='color:#EF3363;'>Mensaje:</strong><br>
            <p style='color:#ddd; margin-top:8px; white-space:pre-wrap;'>" . nl2br(htmlspecialchars($mensaje)) . "</p>
        </div>
        <p style='color:#666; font-size:0.82rem; margin-top:20px;'>Enviado desde catink.com.mx</p>
    </div>
    ";

    $mail->Body = $bodyHtml;
    $mail->AltBody = "De: $nombre <$email>\nAsunto: $asunto\n\n$mensaje";
    $mail->send();

    echo json_encode(['success' => true, 'message' => '¡Mensaje enviado con éxito! Te responderemos pronto.']);

} catch (Exception $e) {
    error_log("CATINK CONTACT MAIL ERROR: " . $e->getMessage());
    echo json_encode(['error' => 'Error al enviar el mensaje. Por favor intenta de nuevo o escríbenos directamente a contacto@catink.com.mx']);
}
