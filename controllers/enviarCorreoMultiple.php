<?php
date_default_timezone_set("America/Mexico_City");
session_start();
include(__DIR__ . "/../data/env.php");
include(__DIR__ . "/../data/conexion.php");

// Log file para debug
$logFile = __DIR__ . "/../logs/email_debug.log";
@mkdir(__DIR__ . "/../logs", 0755, true);

function logDebug($message) {
    global $logFile;
    $timestamp = date("Y-m-d H:i:s");
    @file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
    error_log($message);
}

// Escribir log de inicio
file_put_contents($logFile, "\n=== Iniciando envío de correos múltiples a las " . date("Y-m-d H:i:s") . " ===\n", FILE_APPEND);

$superadmin = $_SESSION['superadmin'] ?? false;
$tienePermiso = $superadmin || ($_SESSION['ACL']['suscripciones']['editar'] ?? false);

if (!$tienePermiso) {
    header("Location: ./../views/suscripciones.php?error=permisos");
    exit();
}

if (!isset($_POST['ids']) || !is_array($_POST['ids'])) {
    header("Location: ./../views/suscripciones.php?error=id");
    exit();
}

$ids = array_map('intval', $_POST['ids']);
$ids = array_filter($ids, function($id) { return $id > 0; });

if (empty($ids)) {
    header("Location: ./../views/suscripciones.php?error=id_invalido");
    exit();
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require(__DIR__."/../PHPMailer/src/PHPMailer.php");
require(__DIR__."/../PHPMailer/src/Exception.php");
require(__DIR__."/../PHPMailer/src/SMTP.php");

// Obtener noticias del último día
$hoy = date("Y-m-d H:i:s");
$ayerMismoHorario = date("Y-m-d H:i:s", strtotime("-24 hours"));

$sql = "SELECT * FROM noticias WHERE fecha_publicacion BETWEEN ? AND ?";
$stmt = $con->prepare($sql);
$stmt->bind_param("ss", $ayerMismoHorario, $hoy);
$stmt->execute();
$resultado = $stmt->get_result();

$noticias = [];
while ($row = $resultado->fetch_assoc()) {
    $noticias[] = $row;
}

logDebug("Noticias encontradas: " . count($noticias));

// Preparar PHPMailer
$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host = env('SMTP_HOST');
    $mail->SMTPAuth = true;
    $mail->Username = env('SMTP_USERNAME');
    $mail->Password = env('SMTP_PASSWORD');
    $mail->SMTPSecure = env('SMTP_SECURE');
    $mail->Port = env('SMTP_PORT');

    $mail->setFrom(env('SMTP_FROM_EMAIL'), env('SMTP_FROM_NAME'));
} catch (Exception $e) {
    logDebug("Error al inicializar SMTP: " . $e->getMessage());
}

// Embed the banner
$bannerPath = __DIR__ . '/../views/email/logo_alt.png';
if (file_exists($bannerPath)) {
    $mail->addEmbeddedImage($bannerPath, 'banner', 'logo_alt.png');
}

// Preparar contenido de noticias
$contenidoNoticias = '';

if (empty($noticias)) {
    $contenidoNoticias = "
    <div style='background:#ffffff;padding:20px;border-radius:10px;text-align:center;'>
        <p style='font-family:Arial,sans-serif;color:#666;'>No hay noticias nuevas en las últimas 24 horas.</p>
    </div>";
} else {
    foreach ($noticias as $index => $noticia) {
        $descripcion = strip_tags($noticia['descripcion']);
        $descripcion = mb_strimwidth($descripcion, 0, 100, '...');

        // Buscar la ruta local del archivo
        $localPath = null;
        $candidates = [
            __DIR__ . '/../' . $noticia['crop3'],
            __DIR__ . '/../../' . $noticia['crop3'],
            dirname(__DIR__) . '/' . $noticia['crop3'],
            dirname(dirname(__DIR__)) . '/' . $noticia['crop3']
        ];
        foreach ($candidates as $c) {
            if (!empty($noticia['crop3']) && file_exists($c) && is_file($c)) {
                $localPath = realpath($c);
                break;
            }
        }

        $imgSrc = '';
        if ($localPath) {
            $ext = strtolower(pathinfo($localPath, PATHINFO_EXTENSION));
            $mimeTypes = [
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'gif' => 'image/gif',
                'webp' => 'image/webp'
            ];
            $mimeType = $mimeTypes[$ext] ?? 'image/jpeg';
            $mail->addEmbeddedImage($localPath, "logo{$index}", basename($localPath), 'base64', $mimeType);
            $imgSrc = "cid:logo{$index}";
        } else {
            // Fallback a URL pública
            $imgSrc = 'https://www.catink.com.mx/serve-image.php?file=' . urlencode($noticia['crop3']);
        }

        $contenidoNoticias .= "
        <table width='100%' cellpadding='0' cellspacing='0' border='0' 
            style='background:#ffffff;margin-bottom:15px;border-radius:10px;overflow:hidden;'>
        <tr class='stack-column'>
        <td width='240' valign='top' class='card-padding' style='padding:14px;'>
            <img src='{$imgSrc}' width='220' class='stack-img' 
                style='width:100%;max-width:220px;height:auto;display:block;border-radius:10px;border:0;margin:0;'>
        </td>
        <td valign='top' class='card-padding' style='padding:14px;font-family:Arial,sans-serif;'>
            <a href='https://www.catink.com.mx/views/news.php?id={$noticia['id']}' 
               style='display:block;margin:14px;text-decoration:none;color:#EF3363;'>
                <h3 style='margin:0;font-family:Arial,sans-serif;color:#EF3363;'>{$noticia['titulo']}</h3>
            </a>
            <p style='margin:14px;'>{$descripcion}</p>
        </td>
        </tr>
        </table>";
    }
}

// Cargar plantilla
$plantillaPath = __DIR__ . "/../views/email/diarias.html";
if (!file_exists($plantillaPath)) {
    header("Location: ./../views/suscripciones.php?error=plantilla");
    exit();
}

$plantilla = file_get_contents($plantillaPath);
$plantilla = str_replace("{{noticias}}", $contenidoNoticias, $plantilla);

$enviados = 0;
$errores = 0;

try {
    // Obtener suscriptores
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $con->prepare("SELECT correo, nombre_completo FROM suscripciones WHERE id_sub IN ($placeholders)");
    $stmt->bind_param(str_repeat('i', count($ids)), ...$ids);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $mail->isHTML(true);
    $mail->Subject = 'Resumen diario de noticias';

    while ($suscriptor = $result->fetch_assoc()) {
        try {
            $mail->clearAddresses();
            $mail->addAddress($suscriptor['correo'], $suscriptor['nombre_completo']);

            logDebug("Enviando correo a: " . $suscriptor['correo']);

            $unsubscribeUrl = 'https://www.catink.com.mx/views/email/unsubscribe.php?email=' . urlencode($suscriptor['correo']);
            $body = str_replace('{{unsubscribe_url}}', $unsubscribeUrl, $plantilla);

            $mail->Body = $body;
            
            if ($mail->send()) {
                logDebug("Correo enviado exitosamente a: " . $suscriptor['correo']);
                $enviados++;
            } else {
                logDebug("Error al enviar correo a " . $suscriptor['correo'] . ": " . $mail->ErrorInfo);
                $errores++;
            }
        } catch (Exception $e) {
            logDebug("Excepción enviando correo a " . $suscriptor['correo'] . ": " . $e->getMessage());
            $errores++;
        }
    }

    if ($enviados > 0) {
        header("Location: ./../views/suscripciones.php?success=correos_enviados&count=$enviados");
    } else {
        header("Location: ./../views/suscripciones.php?error=envio");
    }
    exit();

} catch (Exception $e) {
    error_log("Excepción enviando correos: " . $e->getMessage());
    header("Location: ./../views/suscripciones.php?error=envio");
    exit();
}
?>
