<?php
include("../data/conexion.php"); // tu conexión PDO o mysqli
// =========================
// OBTENER DATOS DEL FORM
// =========================
$nombre = $_POST['nombre'] ?? '';
$correo = $_POST['email'] ?? '';
$sexo   = $_POST['sexo'] ?? '';
// Validación básica
if(empty($nombre) || empty($correo)){
    header("Location: ../suscribirse.php?error=1");
    exit();
}
function getUserIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) return $_SERVER['HTTP_CLIENT_IP'];
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    return $_SERVER['REMOTE_ADDR'];
}
$ip = getUserIP();
$pais = 'Desconocido';
$estado = 'Desconocido';
try {
    $geoJson = @file_get_contents("http://ip-api.com/json/$ip");
    if ($geoJson !== false) {
        $geo = json_decode($geoJson, true);
        $pais = $geo['country'] ?? 'Desconocido';
        $estado = $geo['regionName'] ?? 'Desconocido';
    }
} catch (Exception $e) {
    // Ignorar errores de geolocalización
}
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once(__DIR__."/../PHPMailer/src/PHPMailer.php");
require_once(__DIR__."/../PHPMailer/src/Exception.php");
require_once(__DIR__."/../PHPMailer/src/SMTP.php");
require_once(__DIR__."/../data/env.php");
require_once(__DIR__."/../views/helpers/emailhelper.php");

// =========================
// INSERTAR EN BD Y ENVIAR BIENVENIDA
// =========================
$stmt = $con->prepare("INSERT INTO suscripciones 
(nombre_completo, correo, sexo, ip, pais, estado)
VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssssss", $nombre, $correo, $sexo, $ip, $pais, $estado);

if($stmt->execute()){
    // Enviar correo automático de bienvenida al nuevo suscriptor
    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Timeout    = 8;
        $mail->Host       = env('SMTP_HOST');
        $mail->SMTPAuth   = true;
        $mail->Username   = env('SMTP_USERNAME');
        $mail->Password   = env('SMTP_PASSWORD');
        $mail->SMTPSecure = env('SMTP_SECURE', 'ssl');
        $mail->Port       = env('SMTP_PORT', 465);

        $fromEmail = env('SMTP_NEWS_FROM_EMAIL', env('SMTP_FROM_EMAIL', 'news@catink.com.mx'));
        $fromName  = env('SMTP_NEWS_FROM_NAME', env('SMTP_FROM_NAME', 'CatInk News'));
        $mail->setFrom($fromEmail, $fromName);
        $bienvenida = obtenerBienvenida($sexo);
        $mail->addAddress($correo, $nombre);
        $mail->isHTML(true);
        $mail->Subject = "¡{$bienvenida} a CatInk News, " . htmlspecialchars($nombre) . "! 🐾";

        $unsubscribeUrl = 'https://www.catink.com.mx/views/email/unsubscribe.php?email=' . urlencode($correo);
        $content = "
            <div style='color:#334155; font-size:15px; line-height:1.7;'>
                <p>¡Hola <strong>" . htmlspecialchars($nombre) . "</strong>!</p>
                <p>Muchas gracias por suscribirte a <strong>CatInk News</strong>. A partir de este momento recibirás en tu correo electrónico las noticias más importantes, lanzamientos y análisis del mundo del anime, los videojuegos, el cine y la cultura geek.</p>
                <p>Estamos muy felices de tenerte en nuestra comunidad.</p>
            </div>
        ";

        $mail->Body = renderCatInkEmail([
            'title'           => "¡{$bienvenida} a CatInk News!",
            'badge'           => '🎉 ¡Suscripción Confirmada!',
            'content'         => $content,
            'cta_url'         => 'https://www.catink.com.mx',
            'cta_text'        => 'Explorar CatInk News',
            'unsubscribe_url' => $unsubscribeUrl,
            'theme'           => 'light'
        ]);

        $mail->send();
    } catch (\Throwable $e) {
        error_log("Error al enviar bienvenida de suscripción a {$correo}: " . $e->getMessage());
    }

    header("Location: ./../views/suscripcion.php?success=1");
} else {
    header("Location:./../views/suscripcion.php?error=2");
}
