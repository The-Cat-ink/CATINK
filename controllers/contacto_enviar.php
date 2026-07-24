<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once(__DIR__ . "/../data/env.php");
require_once(__DIR__ . "/../PHPMailer/src/PHPMailer.php");
require_once(__DIR__ . "/../PHPMailer/src/Exception.php");
require_once(__DIR__ . "/../PHPMailer/src/SMTP.php");

$tipo_contacto    = trim($_POST['tipo_contacto'] ?? 'persona');
$nombre           = trim($_POST['nombre']  ?? '');
$email            = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$asunto           = trim($_POST['asunto']  ?? 'Consulta general');
$mensaje          = trim($_POST['mensaje'] ?? '');

// Campos corporativos
$empresa          = trim($_POST['empresa'] ?? '');
$cargo            = trim($_POST['cargo'] ?? '');
$sitio_web        = trim($_POST['sitio_web'] ?? '');
$telefono         = trim($_POST['telefono'] ?? '');
$servicio_interes = trim($_POST['servicio_interes'] ?? '');

if (empty($nombre) || !$email || empty($mensaje)) {
    echo json_encode(['error' => 'Por favor completa todos los campos obligatorios.']);
    exit;
}

if ($tipo_contacto === 'empresa' && empty($empresa)) {
    echo json_encode(['error' => 'Por favor ingresa el nombre de la empresa o marca.']);
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
    $isEmpresa = ($tipo_contacto === 'empresa' || !empty($empresa));

    if ($isEmpresa) {
        $mail->Subject = "🏢 [EMPRESA] $empresa — Propuesta / Contacto: $asunto";
    } else {
        $mail->Subject = "Nuevo mensaje de contacto: $asunto — $nombre";
    }

    $nomEsc      = htmlspecialchars($nombre);
    $emailEsc    = htmlspecialchars($email);
    $asuntoEsc   = htmlspecialchars($asunto);
    $msgEsc      = nl2br(htmlspecialchars($mensaje));
    $empresaEsc  = htmlspecialchars($empresa ?: 'No especificada');
    $cargoEsc    = htmlspecialchars($cargo ?: 'No especificado');
    $webEsc      = htmlspecialchars($sitio_web ?: 'No especificado');
    $telEsc      = htmlspecialchars($telefono ?: 'No especificado');
    $servicioEsc = htmlspecialchars($servicio_interes ?: 'General');

    if ($isEmpresa) {
        $content = "
            <p style='color:#cbd5e0; font-size:15px;'>Se ha recibido una nueva propuesta corporativa de una <strong>empresa / marca</strong> a través de CatInk.</p>
            
            <table width='100%' cellpadding='0' cellspacing='0' style='background:#182234; border-radius:12px; padding:18px; margin:20px 0; border:1px solid rgba(239,51,99,0.3);'>
                <tr><td style='padding:6px 0; color:#EF3363; font-size:14px; font-weight:900;' colspan='2'>🏢 DATOS CORPORATIVOS DE LA EMPRESA</td></tr>
                <tr><td style='padding:6px 0; color:#718096; font-size:13px; font-weight:700; width:35%;'>Empresa / Marca:</td><td style='padding:6px 0; color:#ffffff; font-weight:800; font-size:15px;'>{$empresaEsc}</td></tr>
                <tr><td style='padding:6px 0; color:#718096; font-size:13px; font-weight:700;'>Persona de Contacto:</td><td style='padding:6px 0; color:#ffffff; font-weight:700;'>{$nomEsc} " . ($cargo ? "({$cargoEsc})" : "") . "</td></tr>
                <tr><td style='padding:6px 0; color:#718096; font-size:13px; font-weight:700;'>Correo Electrónico:</td><td style='padding:6px 0;'><a href='mailto:{$emailEsc}' style='color:#EF3363; font-weight:700;'>{$emailEsc}</a></td></tr>
                " . ($telefono ? "<tr><td style='padding:6px 0; color:#718096; font-size:13px; font-weight:700;'>Teléfono / WhatsApp:</td><td style='padding:6px 0; color:#ffffff;'>{$telEsc}</td></tr>" : "") . "
                " . ($sitio_web ? "<tr><td style='padding:6px 0; color:#718096; font-size:13px; font-weight:700;'>Sitio Web / Redes:</td><td style='padding:6px 0;'><a href='{$webEsc}' target='_blank' style='color:#818cf8;'>{$webEsc}</a></td></tr>" : "") . "
                <tr><td style='padding:6px 0; color:#718096; font-size:13px; font-weight:700;'>Interés / Servicio:</td><td style='padding:6px 0; color:#EF3363; font-weight:800;'>{$servicioEsc}</td></tr>
                <tr><td style='padding:6px 0; color:#718096; font-size:13px; font-weight:700;'>Asunto:</td><td style='padding:6px 0; color:#ffffff;'>{$asuntoEsc}</td></tr>
            </table>

            <div style='background:#162032; padding:18px; border-radius:12px; border-left:4px solid #EF3363; margin-top:16px;'>
                <strong style='color:#EF3363; font-size:13px; text-transform:uppercase; letter-spacing:0.05em;'>Propuesta / Mensaje:</strong>
                <p style='color:#e2e8f0; margin:10px 0 0; line-height:1.7; white-space:pre-wrap;'>{$msgEsc}</p>
            </div>
        ";
        $badgeText = 'Contacto Corporativo / Marca';
    } else {
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
        $badgeText = 'Formulario de Contacto';
    }

    $mail->Body = renderCatInkEmail([
        'title'     => $isEmpresa ? 'Nueva Propuesta Corporativa' : 'Nuevo Mensaje de Contacto',
        'badge'     => $badgeText,
        'content'   => $content,
        'cta_url'   => 'mailto:' . $email,
        'cta_text'  => 'Responder a ' . ($isEmpresa ? $empresa : $nombre)
    ]);
    $mail->AltBody = "De: $nombre <$email>\nEmpresa: $empresa\nAsunto: $asunto\n\n$mensaje";
    $mail->send();

    echo json_encode(['success' => true, 'message' => '¡Mensaje enviado con éxito! Te responderemos pronto.']);

} catch (Exception $e) {
    error_log("CATINK CONTACT MAIL ERROR: " . $e->getMessage());
    echo json_encode(['error' => 'Error al enviar el mensaje. Por favor intenta de nuevo o escríbenos directamente a contacto@catink.com.mx']);
}
