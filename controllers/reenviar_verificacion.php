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

    $fromEmail = env('SMTP_AUTH_FROM_EMAIL', 'no-reply@catink.com.mx');
    $fromName = env('SMTP_AUTH_FROM_NAME', 'CatInk');
    $mail->setFrom($fromEmail, $fromName);
    $mail->addAddress($email, $lector['nombre']);

    $proto = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
    $domain = $_SERVER['HTTP_HOST'] ?? 'catink.test';
    require_once(__DIR__ . "/../views/helpers/emailhelper.php");

    $verifyUrl = "{$proto}://{$domain}" . basePath() . "/verificar.php?token=" . urlencode($token);

    $mail->isHTML(true);
    $mail->Subject = "Verifica tu cuenta en CatInk";

    $nombreLector = htmlspecialchars($lector['nombre']);
    $content = "
        <p>Hola <strong style='color:#ffffff;'>{$nombreLector}</strong>,</p>
        <p style='color:#cbd5e0; line-height:1.7;'>Has solicitado reenviar el correo de verificación para activar tu cuenta. Por favor haz clic en el siguiente botón para confirmar tu correo:</p>
        <p style='color:#718096; font-size:12px; margin-top:24px; word-break:break-all;'>Si tienes problemas con el botón, copia este enlace en tu navegador:<br><a href='{$verifyUrl}' style='color:#EF3363;'>{$verifyUrl}</a></p>
    ";

    $mail->Body = renderCatInkEmail([
        'title'     => 'Verificación de Cuenta',
        'badge'     => 'Reenvío de Verificación',
        'content'   => $content,
        'cta_url'   => $verifyUrl,
        'cta_text'  => 'Verificar mi cuenta'
    ]);
    $mail->send();

    echo json_encode(['success' => true, 'message' => 'Correo de verificación reenviado con éxito.']);
    exit;

} catch (\Throwable $e) {
    error_log("Error reenviando correo de verificacion: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'No se pudo enviar el correo. Intenta de nuevo en unos momentos.']);
    exit;
}
