<?php
date_default_timezone_set("America/Mexico_City");

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require("./../../PHPMailer/src/PHPMailer.php");
require("./../../PHPMailer/src/Exception.php");
require("./../../PHPMailer/src/SMTP.php");
include("./../../data/env.php");
include("./../../data/conexion.php");

$nombre = $_POST['nombre'];
$email = $_POST['email'];
$mensaje = $_POST['razon'];
$cv = $_FILES['cv'];
// Crear objeto PHPMailer
$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = env('GMAIL_USERNAME');
    $mail->Password   = env('GMAIL_APP_PASSWORD');
    $mail->SMTPSecure = 'tls';
    $mail->Port       = 587;

    $mail->setFrom('catink.oficial@gmail.com', 'CatInk News');

    // Destinatarios (puedes poner dinámicos desde base de datos)
    $mail->addAddress('arturo_mtz_h@hotmail.com', 'ING Arturo Matínez Hernández');

    $mail->isHTML(true);
    $mail->Subject = "Solicitud de $nombre para unirse a CatInk";

    // Adjuntar el archivo CV
    if (isset($_FILES['cv']) && $_FILES['cv']['error'] == 0) {
        $mail->addAttachment(
            $_FILES['cv']['tmp_name'],
            $_FILES['cv']['name']
        );
    }

    require_once(__DIR__ . "/../helpers/emailhelper.php");

    $nomEsc = htmlspecialchars($nombre);
    $emailEsc = htmlspecialchars($email);
    $msgEsc = nl2br(htmlspecialchars($mensaje));

    $content = "
        <p style='color:#cbd5e0;'>Se ha recibido una nueva solicitud para unirse al equipo de CatInk.</p>
        <table width='100%' cellpadding='0' cellspacing='0' style='background:#182234; border-radius:12px; padding:16px; margin:16px 0; border:1px solid rgba(255,255,255,0.06);'>
            <tr><td style='padding:6px 0; color:#718096; font-size:13px; font-weight:700;'>Nombre:</td><td style='padding:6px 0; color:#ffffff; font-weight:700;'>{$nomEsc}</td></tr>
            <tr><td style='padding:6px 0; color:#718096; font-size:13px; font-weight:700;'>Correo:</td><td style='padding:6px 0;'><a href='mailto:{$emailEsc}' style='color:#EF3363;'>{$emailEsc}</a></td></tr>
        </table>
        <div style='background:#162032; padding:16px; border-radius:12px; border-left:4px solid #EF3363; margin-top:14px;'>
            <strong style='color:#EF3363; font-size:13px; text-transform:uppercase; letter-spacing:0.05em;'>Motivo / Presentación:</strong>
            <p style='color:#e2e8f0; margin:8px 0 0; line-height:1.7;'>{$msgEsc}</p>
        </div>
    ";

    $mail->Body = renderCatInkEmail([
        'title'     => 'Solicitud de Unión',
        'badge'     => 'Reclutamiento CatInk',
        'content'   => $content,
        'cta_url'   => 'mailto:' . $email,
        'cta_text'  => 'Responder a ' . $nombre
    ]);

    $mail->send();

    echo "Correo enviado correctamente.";
    header("Location: ./../unete.php?success=1");
    exit();

} catch (Exception $e) {
    // Eliminar temporal incluso si falla
    if(file_exists($tmpPng)) unlink($tmpPng);
    echo "Error al enviar: {$mail->ErrorInfo}";
}