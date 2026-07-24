<?php
date_default_timezone_set("America/Mexico_City");
echo "Cron ejecutado: " . date("Y-m-d H:i:s") . "\n";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once(__DIR__."/../../PHPMailer/src/PHPMailer.php");
require_once(__DIR__."/../../PHPMailer/src/Exception.php");
require_once(__DIR__."/../../PHPMailer/src/SMTP.php");
include(__DIR__."/../../data/env.php");
include(__DIR__."/../../data/conexion.php");

// Obtenemos la hora programada, el estado, última ejecución y el ID de programación
$sqlProg = "SELECT id_programacion, hora, estado, ultima_ejecucion FROM programacion_correos LIMIT 1";
$stmtProg = $con->prepare($sqlProg);
$stmtProg->execute();
$resultProg = $stmtProg->get_result();
$rowProg = $resultProg->fetch_assoc();

if (!$rowProg) {
    echo "No se encontró la configuración de programación de correos.\n";
    return;
}

$horaProgramada = $rowProg['hora'];
$estado = $rowProg['estado'] ?? 'inactivo';
$ultimaEjecucion = $rowProg['ultima_ejecucion'];

// Solo ejecutar si el estado es activo
if ($estado !== 'activo') {
    echo "La programación de correos está inactiva.\n";
    return;
}

// Validar que no se haya ejecutado ya el día de hoy
if (!empty($ultimaEjecucion) && date('Y-m-d', strtotime($ultimaEjecucion)) === date('Y-m-d')) {
    echo "El resumen diario ya fue enviado el día de hoy (" . date('Y-m-d', strtotime($ultimaEjecucion)) . ").\n";
    return;
}

// Validar que la hora actual ya sea mayor o igual a la hora programada
$horaActual = date("H:i:s");
if ($horaActual < $horaProgramada) {
    echo "Aún no es la hora programada ($horaProgramada). Hora actual: $horaActual.\n";
    return;
}

$hoy = date("Y-m-d H:i:s");
    $ayerMismoHorario = date("Y-m-d H:i:s", strtotime("-24 hours"));

    // Seleccionamos solo noticias del último día
    $sql = "SELECT * FROM noticias WHERE eliminado_en IS NULL AND fecha_publicacion BETWEEN ? AND ?";
    $stmt = $con->prepare($sql);
    $stmt->bind_param("ss", $ayerMismoHorario, $hoy);
    $stmt->execute();
    $resultado = $stmt->get_result();

    $noticias = [];
    while ($row = $resultado->fetch_assoc()) {
        $noticias[] = $row;
    }

    if (empty($noticias)) {
        echo "No se encontraron noticias publicadas en las últimas 24 horas ($hoy).\n";
        return;
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

        $tituloEsc = htmlspecialchars($noticia['titulo']);
        $urlNoticia = "https://catink.com.mx/views/news.php?id={$noticia['id']}";

        $contenidoNoticias .= "
        <table width='100%' cellpadding='0' cellspacing='0' border='0' 
            style='background:#182234;margin-bottom:16px;border-radius:14px;overflow:hidden;border:1px solid rgba(255,255,255,0.06);'>
        <tr>
        <td width='180' valign='middle' class='stack-col' style='padding:12px;'>
            <a href='{$urlNoticia}' target='_blank'>
                <img src='{$imgSrc}' width='180' class='stack-img' 
                    style='width:100%;max-width:180px;height:auto;display:block;border-radius:10px;border:0;margin:0;'>
            </a>
        </td>
        <td valign='middle' class='stack-col' style='padding:14px 16px 14px 4px;font-family:Arial,sans-serif;'>
            <a href='{$urlNoticia}' target='_blank' 
               style='display:block;text-decoration:none;color:#ffffff;'>
                <h3 style='margin:0 0 8px;font-family:Arial,sans-serif;color:#ffffff;font-size:16px;font-weight:800;line-height:1.3;'>{$tituloEsc}</h3>
            </a>
            <p style='margin:0 0 12px;color:#a0aec0;font-size:13px;line-height:1.5;'>{$descripcion}</p>
            <a href='{$urlNoticia}' target='_blank' 
               style='display:inline-block;color:#EF3363;font-size:12px;font-weight:800;text-decoration:none;text-transform:uppercase;letter-spacing:0.05em;'>
                Leer noticia completa →
            </a>
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
        $mail->Timeout = 15;
        $mail->getSMTPInstance()->Timelimit = 15; // sin esto, un SMTP colgado bloquea hasta 300s

        $mail->Host = env('SMTP_HOST');
        $mail->SMTPAuth = true;
        $mail->Username = env('SMTP_USERNAME');
        $mail->Password = env('SMTP_PASSWORD');
        $mail->SMTPSecure = env('SMTP_SECURE', 'tls');
        $mail->Port = (int) env('SMTP_PORT', 587);

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