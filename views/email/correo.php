<?php
date_default_timezone_set("America/Mexico_City");
echo "Cron ejecutado: " . date("Y-m-d H:i:s") . "\n";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require(__DIR__."/../../PHPMailer/src/PHPMailer.php");
require(__DIR__."/../../PHPMailer/src/Exception.php");
require(__DIR__."/../../PHPMailer/src/SMTP.php");
include(__DIR__."/../../data/env.php");
include(__DIR__."/../../data/conexion.php");

// Obtenemos la hora programada, el estado y el ID de programación
$sqlProg = "SELECT id_programacion, hora, estado FROM programacion_correos LIMIT 1";
$stmtProg = $con->prepare($sqlProg);
$stmtProg->execute();
$resultProg = $stmtProg->get_result();
$rowProg = $resultProg->fetch_assoc();

if (!$rowProg) {
    die("No se encontró la configuración de programación de correos.\n");
}

$horaProgramada = $rowProg['hora'];
$estado = $rowProg['estado'] ?? 'inactivo';

// Solo ejecutar si el estado es activo
if ($estado !== 'activo') {
    die("La programación de correos está inactiva.\n");
}

// Solo ejecutar si estamos dentro del rango de +-60s de la hora programada
if (abs(strtotime(date("H:i:s")) - strtotime($horaProgramada)) <= 60) {

    $hoy = date("Y-m-d H:i:s");
    $ayerMismoHorario = date("Y-m-d H:i:s", strtotime("-24 hours"));

    // Seleccionamos solo noticias del último día
    $sql = "SELECT * FROM noticias WHERE fecha_publicacion BETWEEN ? AND ?";
    $stmt = $con->prepare($sql);
    $stmt->bind_param("ss", $ayerMismoHorario, $hoy);
    $stmt->execute();
    $resultado = $stmt->get_result();

    $noticias = [];
    while ($row = $resultado->fetch_assoc()) {
        $noticias[] = $row;
    }

    if (empty($noticias)) {
        die("No se encontraron noticias para el día $hoy\n");
    } else {
        echo "Noticias encontradas: " . count($noticias) . "\n";
    }

    // Preparar PHPMailer
    $mail = new PHPMailer(true);
    $contenidoNoticias = '';

    // Embed banner image
    $bannerPath = __DIR__ . '/logo_alt.png';
    if (file_exists($bannerPath)) {
        $mail->addEmbeddedImage($bannerPath, 'banner', 'logo_alt.png');
    }

    foreach ($noticias as $index => $noticia) {
        $descripcion = strip_tags($noticia['descripcion']);
        $descripcion = mb_strimwidth($descripcion, 0, 100, '...');

        // Buscar la ruta local del archivo
        $localPath = null;
        $candidates = [
            __DIR__ . '/../../' . $noticia['crop3'],
            __DIR__ . '/../../../' . $noticia['crop3'],
            dirname(dirname(__DIR__)) . '/' . $noticia['crop3'],
            dirname(dirname(dirname(__DIR__))) . '/' . $noticia['crop3']
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

    // Plantilla de correo
    $plantillaPath = __DIR__ . "/diarias.html";
    if (!file_exists($plantillaPath)) {
        die("Error: plantilla no encontrada en $plantillaPath\n");
    }
    $plantilla = file_get_contents($plantillaPath);
    $plantilla = str_replace("{{noticias}}", $contenidoNoticias, $plantilla);

    try {
        $mail->isSMTP();
        $mail->Host = env('SMTP_HOST');
        $mail->SMTPAuth = true;
        $mail->Username = env('SMTP_USERNAME');
        $mail->Password = env('SMTP_PASSWORD');
        $mail->SMTPSecure = env('SMTP_SECURE');
        $mail->Port = env('SMTP_PORT');

        $mail->setFrom(env('SMTP_FROM_EMAIL'), env('SMTP_FROM_NAME'));

        // Destinatarios
        $sqlUsuarios = "SELECT correo, nombre_completo FROM suscripciones";
        $resUsuarios = $con->query($sqlUsuarios);

        $mail->isHTML(true);
        $mail->Subject = 'Resumen diario de noticias';

        while($user = $resUsuarios->fetch_assoc()){
            $mail->clearAddresses();
            $mail->addAddress($user['correo'], $user['nombre_completo']);

            $unsubscribeUrl = 'https://www.catink.com.mx/views/email/unsubscribe.php?email=' . urlencode($user['correo']);
            $body = str_replace('{{unsubscribe_url}}', $unsubscribeUrl, $plantilla);

            $mail->Body = $body;
            $mail->send();
        }

        // Registrar última ejecución exitosa
        $idProg = $rowProg['id_programacion'];
        $sqlUltima = "UPDATE programacion_correos SET ultima_ejecucion = NOW() WHERE id_programacion = ?";
        $stmtUltima = $con->prepare($sqlUltima);
        $stmtUltima->bind_param("i", $idProg);
        $stmtUltima->execute();

        echo "Correo enviado correctamente a todos los usuarios suscritos.\n";

    } catch (Exception $e) {
        echo "Error al enviar: {$mail->ErrorInfo}\n";
    }
}