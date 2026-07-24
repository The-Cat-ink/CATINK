<?php
date_default_timezone_set("America/Mexico_City");

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require("./../../PHPMailer/src/PHPMailer.php");
require("./../../PHPMailer/src/Exception.php");
require("./../../PHPMailer/src/SMTP.php");
include("./../../data/env.php");
include("./../../data/conexion.php");

$nombre = $_POST['name'] ?? '';
$email = $_POST['email'] ?? '';
$mensaje = $_POST['message'] ?? '';

try {
    // === 1. Correo al administrador ===
    $mailAdmin = new PHPMailer(true);
    $mailAdmin->isSMTP();
    $mailAdmin->Host       = 'smtp.gmail.com';
    $mailAdmin->SMTPAuth   = true;
    $mailAdmin->Username   = env('GMAIL_USERNAME');
    $mailAdmin->Password   = env('GMAIL_APP_PASSWORD');
    $mailAdmin->SMTPSecure = 'tls';
    $mailAdmin->Port       = 587;

    $mailAdmin->setFrom(env('GMAIL_FROM_EMAIL'), env('GMAIL_FROM_NAME'));
    $mailAdmin->addAddress(env('GMAIL_REPLY_TO'), 'ING Arturo Matínez Hernández');

    $mailAdmin->isHTML(true);
    $mailAdmin->Subject = "Solicitud de asesoramiento por parte de CatInk";

    require_once(__DIR__ . "/../helpers/emailhelper.php");

    $nomEsc = htmlspecialchars($nombre);
    $emailEsc = htmlspecialchars($email);
    $msgEsc = nl2br(htmlspecialchars($mensaje));

    $contentAdmin = "
        <p style='color:#cbd5e0;'>Se ha recibido una nueva solicitud de asesoramiento.</p>
        <table width='100%' cellpadding='0' cellspacing='0' style='background:#182234; border-radius:12px; padding:16px; margin:16px 0; border:1px solid rgba(255,255,255,0.06);'>
            <tr><td style='padding:6px 0; color:#718096; font-size:13px; font-weight:700;'>Nombre:</td><td style='padding:6px 0; color:#ffffff; font-weight:700;'>{$nomEsc}</td></tr>
            <tr><td style='padding:6px 0; color:#718096; font-size:13px; font-weight:700;'>Correo:</td><td style='padding:6px 0;'><a href='mailto:{$emailEsc}' style='color:#EF3363;'>{$emailEsc}</a></td></tr>
        </table>
        <div style='background:#162032; padding:16px; border-radius:12px; border-left:4px solid #EF3363; margin-top:14px;'>
            <strong style='color:#EF3363; font-size:13px; text-transform:uppercase; letter-spacing:0.05em;'>Mensaje:</strong>
            <p style='color:#e2e8f0; margin:8px 0 0; line-height:1.7;'>{$msgEsc}</p>
        </div>
    ";

    $mailAdmin->Body = renderCatInkEmail([
        'title'     => 'Solicitud de Asesoramiento',
        'badge'     => 'Contacto CatInk',
        'content'   => $contentAdmin,
        'cta_url'   => 'mailto:' . $email,
        'cta_text'  => 'Responder a ' . $nombre
    ]);
    $mailAdmin->send();

    // === 2. Correo de confirmación al usuario ===
    $mailUser = new PHPMailer(true);
    $mailUser->isSMTP();
    $mailUser->Host       = env('SMTP_HOST', 'smtp.gmail.com');
    $mailUser->SMTPAuth   = true;
    $mailUser->Username   = env('SMTP_USERNAME');
    $mailUser->Password   = env('SMTP_PASSWORD');
    $mailUser->SMTPSecure = env('SMTP_SECURE', 'tls');
    $mailUser->Port       = env('SMTP_PORT', 587);

    $mailUser->setFrom(env('SMTP_FROM_EMAIL', 'contacto@catink.com.mx'), env('SMTP_FROM_NAME', 'CatInk'));
    $mailUser->addAddress($email, $nombre);

    $mailUser->isHTML(true);
    $mailUser->Subject = "Confirmación de recepción - CatInk";

    $contentUser = "
        <p>Hola <strong style='color:#ffffff;'>{$nomEsc}</strong>,</p>
        <p style='color:#cbd5e0; line-height:1.7;'>Hemos recibido tu mensaje correctamente. Nuestro equipo revisará tu solicitud y se pondrá en contacto contigo a la brevedad posible.</p>
        <p style='color:#718096; font-size:13px; margin-top:20px;'>Gracias por comunicarte con CatInk.</p>
    ";

    $mailUser->Body = renderCatInkEmail([
        'title'     => '¡Hemos recibido tu mensaje!',
        'badge'     => 'Contacto Confirmado',
        'content'   => $contentUser,
        'cta_url'   => 'https://catink.com.mx',
        'cta_text'  => 'Ir a CatInk'
    ]);
    $mailUser->send();

    header("Location: ./../contactanos.php?success=1");
    exit();

} catch (Exception $e) {
    echo "Error al enviar: {$e->getMessage()}";
}