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

    require_once(__DIR__ . "/../views/helpers/emailhelper.php");

    $mail->isHTML(true);
    $mail->Subject = "Nuevo mensaje de contacto: $asunto — $nombre";

    $nomEsc = htmlspecialchars($nombre);
    $emailEsc = htmlspecialchars($email);
    $asuntoEsc = htmlspecialchars($asunto);
    $msgEsc = nl2br(htmlspecialchars($mensaje));

    $content = "
        <p style='color:#cbd5e0; font-size:15px;'>Se ha recibido un nuevo mensaje a través del formulario de contacto del sitio web.</p>
        
        <table width='100%' cellpadding='0' cellspacing='0' style='background:#182234; border-radius:12px; padding:18px; margin:20px 0; border:1px solid rgba(255,255,255,0.06);'>
            <tr><td style='padding:6px 0; color:#718096; font-size:13px; font-weight:700;'>De:</td><td style='padding:6px 0; color:#ffffff; font-weight:700;'>{$nomEsc}</td></tr>
            <tr><td style='padding:6px 0; color:#718096; font-size:13px; font-weight:700;'>Correo:</td><td style='padding:6px 0;'><a href='mailto:{$emailEsc}' style='color:#EF3363;'>{$emailEsc}</a></td></tr>
            <tr><td style='padding:6px 0; color:#718096; font-size:13px; font-weight:700;'>Asunto:</td><td style='padding:6px 0; color:#EF3363; font-weight:800;'>{$asuntoEsc}</td></tr>
        </table>

        <div style='background:#162032; padding:18px; border-radius:12px; border-left:4px solid #EF3363; margin-top:16px;'>
            <strong style='color:#EF3363; font-size:13px; text-transform:uppercase; letter-spacing:0.05em;'>Mensaje:</strong>
            <p style='color:#e2e8f0; margin:10px 0 0; line-height:1.7; white-space:pre-wrap;'>{$msgEsc}</p>
        </div>
    ";

    $mail->Body = renderCatInkEmail([
        'title'     => 'Nuevo Mensaje de Contacto',
        'badge'     => 'Formulario de Contacto',
        'content'   => $content,
        'cta_url'   => 'mailto:' . $email,
        'cta_text'  => 'Responder a ' . $nombre
    ]);
    $mail->AltBody = "De: $nombre <$email>\nAsunto: $asunto\n\n$mensaje";
    $mail->send();

    echo json_encode(['success' => true, 'message' => '¡Mensaje enviado con éxito! Te responderemos pronto.']);

} catch (Exception $e) {
    error_log("CATINK CONTACT MAIL ERROR: " . $e->getMessage());
    echo json_encode(['error' => 'Error al enviar el mensaje. Por favor intenta de nuevo o escríbenos directamente a contacto@catink.com.mx']);
}
