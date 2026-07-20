<?php
session_start();
header('Content-Type: application/json');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once(__DIR__ . "/../PHPMailer/src/PHPMailer.php");
require_once(__DIR__ . "/../PHPMailer/src/Exception.php");
require_once(__DIR__ . "/../PHPMailer/src/SMTP.php");

require_once(__DIR__ . "/../data/env.php");
require_once(__DIR__ . "/../data/conexion.php");
require_once(__DIR__ . "/../views/helpers/urlhelper.php");

$email = trim($_POST['email'] ?? $_GET['email'] ?? '');

if (empty($email)) {
    echo json_encode(['success' => false, 'error' => 'Email no proporcionado.']);
    exit;
}

// 1. Buscar al lector por correo
$stmt = $con->prepare("SELECT id, nombre, verificado, token_verificacion FROM lectores WHERE correo = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if (!$result || $result->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'No se encontró ninguna cuenta registrada con este correo.']);
    exit;
}

$lector = $result->fetch_assoc();

if ((int)$lector['verificado'] === 1) {
    echo json_encode([
        'success' => true,
        'ya_verificado' => true,
        'message' => 'Tu cuenta ya está verificada. Redirigiendo...',
        'redirect' => basePath() . '/views/perfil.php?registro=verificado'
    ]);
    exit;
}

// Generar nuevo token si no existe
$token = $lector['token_verificacion'];
if (empty($token)) {
    $token = bin2hex(random_bytes(32));
    $upd = $con->prepare("UPDATE lectores SET token_verificacion = ? WHERE id = ?");
    $upd->bind_param("si", $token, $lector['id']);
    $upd->execute();
}

// 2. Enviar correo de verificación mediante PHPMailer
$smtpHost = env('SMTP_HOST');
if (empty($smtpHost)) {
    echo json_encode(['success' => false, 'error' => 'El servidor de correo no está configurado.']);
    exit;
}

try {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Timeout = 15;
    $mail->getSMTPInstance()->Timelimit = 15;
    $mail->SMTPKeepAlive = false;
    $mail->Host = $smtpHost;
    $mail->SMTPAuth = true;
    $mail->Username = env('SMTP_USERNAME');
    $mail->Password = env('SMTP_PASSWORD');
    $mail->SMTPSecure = env('SMTP_SECURE', 'tls');
    $mail->Port = (int) env('SMTP_PORT', 587);

    $mail->SMTPOptions = array(
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        )
    );

    $fromEmail = env('SMTP_FROM_EMAIL', 'no-reply@catink.com.mx');
    $fromName = env('SMTP_FROM_NAME', 'CatInk');
    $mail->setFrom($fromEmail, $fromName);
    $mail->addAddress($email, $lector['nombre']);

    $proto = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
    $domain = $_SERVER['HTTP_HOST'] ?? 'catink.test';
    $verifyUrl = "{$proto}://{$domain}" . basePath() . "/verificar.php?token=" . urlencode($token);

    $mail->isHTML(true);
    $mail->Subject = "Verifica tu cuenta en CatInk";

    $htmlBody = "
    <div style='font-family: Arial, sans-serif; max-width:600px; margin:auto; background:#f9f9f9; padding:20px; border-radius:10px; border: 1px solid #eee;'>
        <h2 style='color:#EF3363; text-align:center;'>¡Te damos la bienvenida a CatInk!</h2>
        <p>Hola <strong>" . htmlspecialchars($lector['nombre']) . "</strong>,</p>
        <p>Has solicitado reenviar el correo de verificación para activar tu cuenta. Por favor haz clic en el siguiente botón:</p>
        <p style='text-align:center; margin:30px 0;'>
            <a href='{$verifyUrl}' style='display:inline-block; padding:12px 30px; background:#EF3363; color:#fff; text-decoration:none; border-radius:5px; font-weight:bold; box-shadow: 0 4px 6px rgba(239, 51, 99, 0.2);'>
                Verificar mi cuenta
            </a>
        </p>
        <p style='color:#666; font-size:12px;'>Si no puedes hacer clic en el botón, copia y pega el siguiente enlace en tu navegador:</p>
        <p style='color:#EF3363; font-size:12px; word-break:break-all;'>{$verifyUrl}</p>
        <hr style='border:none; border-top:1px solid #ddd; margin:20px 0;'>
        <p style='color:#999; font-size:11px; text-align:center;'>© 2026 CatInk. Todos los derechos reservados.</p>
    </div>";

    $mail->Body = $htmlBody;
    $mail->send();

    echo json_encode(['success' => true, 'message' => 'Correo de verificación reenviado con éxito.']);
    exit;

} catch (\Throwable $e) {
    error_log("Error reenviando correo de verificacion: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'No se pudo enviar el correo. Intenta de nuevo en unos momentos.']);
    exit;
}
