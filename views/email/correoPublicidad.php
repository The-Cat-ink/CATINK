<?php
date_default_timezone_set("America/Mexico_City");

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once(__DIR__."/../../PHPMailer/src/PHPMailer.php");
require_once(__DIR__."/../../PHPMailer/src/Exception.php");
require_once(__DIR__."/../../PHPMailer/src/SMTP.php");
include(__DIR__."/../../data/env.php");
include(__DIR__."/../../data/conexion.php");

// Asegurar que la columna 'enviado' y 'fecha_enviado' existen
$checkCol = $con->query("SHOW COLUMNS FROM correos_publicitarios LIKE 'enviado'");
if ($checkCol && $checkCol->num_rows === 0) {
    @$con->query("ALTER TABLE correos_publicitarios ADD enviado TINYINT(1) NOT NULL DEFAULT 0");
}
$checkFecha = $con->query("SHOW COLUMNS FROM correos_publicitarios LIKE 'fecha_enviado'");
if ($checkFecha && $checkFecha->num_rows === 0) {
    @$con->query("ALTER TABLE correos_publicitarios ADD fecha_enviado DATETIME DEFAULT NULL");
}

$hoy = date("Y-m-d H:i:s");

// Consulta para obtener todos los correos publicitarios pendientes cuya fecha de envío ya llegó
$sql = "SELECT * FROM correos_publicitarios WHERE (enviado = 0 OR enviado IS NULL) AND envio <= ?";
$stmt = $con->prepare($sql);
$stmt->bind_param('s', $hoy);
$stmt->execute();
$resultado = $stmt->get_result();

$correosPendientes = [];
while ($row = $resultado->fetch_assoc()) {
    $correosPendientes[] = $row;
}

if (empty($correosPendientes)) {
    echo "No hay correos publicitarios pendientes por enviar.\n";
    return;
}

echo "Correos publicitarios pendientes encontrados: " . count($correosPendientes) . "\n";

foreach ($correosPendientes as $correo) {
    $idCorreo   = $correo['id_correo'];
    $titulo     = $correo['titulo'];
    $contenido  = $correo['contenido'];
    $webpPath   = $correo['imagen'];
    $urlBoton   = $correo['url_c'];

    $tmpPng = __DIR__ . "/temp_" . uniqid() . ".png";
    $hasEmbeddedImg = false;

    // Buscar imagen WebP local o remota
    $localWebp = __DIR__ . "/../../img/correo/" . $webpPath;
    if (file_exists($localWebp) && is_file($localWebp)) {
        $image = @imagecreatefromwebp($localWebp);
    } else {
        $image = @imagecreatefromwebp("https://www.catink.com.mx/img/correo/" . $webpPath);
    }

    if ($image) {
        imagepng($image, $tmpPng);
        imagedestroy($image);
        $hasEmbeddedImg = true;
    }

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Timeout = 15;
        $mail->Host       = env('SMTP_HOST');
        $mail->SMTPAuth   = true;
        $mail->Username   = env('SMTP_USERNAME');
        $mail->Password   = env('SMTP_PASSWORD');
        $mail->SMTPSecure = env('SMTP_SECURE', 'ssl');
        $mail->Port       = env('SMTP_PORT', 465);

        $fromEmail = env('SMTP_NEWS_FROM_EMAIL', env('SMTP_FROM_EMAIL', 'news@catink.com.mx'));
        $fromName  = env('SMTP_NEWS_FROM_NAME', env('SMTP_FROM_NAME', 'CatInk News'));
        $mail->setFrom($fromEmail, $fromName);

        if ($hasEmbeddedImg && file_exists($tmpPng)) {
            $mail->addEmbeddedImage($tmpPng, 'imagenNoticia', 'imagen.png');
            $imgHtml = "<img src='cid:imagenNoticia' style='width:100%; max-width:500px; border-radius:10px; margin:15px 0;' />";
        } else if (!empty($webpPath)) {
            $imgUrl = "https://www.catink.com.mx/img/correo/" . urlencode($webpPath);
            $imgHtml = "<img src='{$imgUrl}' style='width:100%; max-width:500px; border-radius:10px; margin:15px 0;' />";
        } else {
            $imgHtml = "";
        }

        $mail->isHTML(true);
        $mail->Subject = $titulo;

        $sqlUsuarios = "SELECT correo, nombre_completo FROM suscripciones";
        $resUsuarios = $con->query($sqlUsuarios);

        require_once(__DIR__ . "/../helpers/emailhelper.php");

        while ($user = $resUsuarios->fetch_assoc()) {
            $mail->clearAddresses();
            $mail->addAddress($user['correo'], $user['nombre_completo']);

            $unsubscribeUrl = 'https://www.catink.com.mx/views/email/unsubscribe.php?email=' . urlencode($user['correo']);

            $content = "
                <div style='color:#e2e8f0; font-size:15px; line-height:1.7;'>
                    " . nl2br($contenido) . "
                </div>
                " . (!empty($imgHtml) ? "<div style='text-align:center; margin:20px 0;'>{$imgHtml}</div>" : "") . "
            ";

            $mail->Body = renderCatInkEmail([
                'title'           => $titulo,
                'badge'           => 'Anuncio / Promoción',
                'content'         => $content,
                'cta_url'         => $urlBoton,
                'cta_text'        => 'Ver promoción',
                'unsubscribe_url' => $unsubscribeUrl
            ]);
            $mail->send();
        }

        // Marcar correo como enviado en la BD
        $stmtUp = $con->prepare("UPDATE correos_publicitarios SET enviado = 1, fecha_enviado = NOW() WHERE id_correo = ?");
        $stmtUp->bind_param("i", $idCorreo);
        $stmtUp->execute();

        echo "Correo publicitario ID {$idCorreo} ('{$titulo}') enviado correctamente.\n";

    } catch (Exception $e) {
        echo "Error al enviar correo publicitario ID {$idCorreo}: {$mail->ErrorInfo}\n";
    } finally {
        if (file_exists($tmpPng)) {
            @unlink($tmpPng);
        }
    }
}