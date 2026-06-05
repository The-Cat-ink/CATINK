<?php
date_default_timezone_set("America/Mexico_City");
session_start();
include(__DIR__ . "/../data/env.php");
include(__DIR__ . "/../data/conexion.php");

$superadmin = $_SESSION['superadmin'] ?? false;
$tienePermiso = $superadmin || ($_SESSION['ACL']['suscripciones']['editar'] ?? false);

if (!$tienePermiso) {
    header("Location: ./../views/suscripciones.php?error=permisos");
    exit();
}

if (!isset($_POST['id'])) {
    header("Location: ./../views/suscripciones.php?error=id");
    exit();
}

$id = intval($_POST['id']);

if ($id <= 0) {
    header("Location: ./../views/suscripciones.php?error=id_invalido");
    exit();
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require(__DIR__."/../PHPMailer/src/PHPMailer.php");
require(__DIR__."/../PHPMailer/src/Exception.php");
require(__DIR__."/../PHPMailer/src/SMTP.php");

// Obtener suscriptor
$stmt = $con->prepare("SELECT correo, nombre_completo FROM suscripciones WHERE id_sub = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$suscriptor = $result->fetch_assoc();

if (!$suscriptor) {
    header("Location: ./../views/suscripciones.php?error=no_encontrado");
    exit();
}

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

error_log("Noticias encontradas: " . count($noticias));

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

        // Construir URL correcta de la imagen (usar directamente, sin descargar)
        $imagenUrl = 'https://www.catink.com.mx/serve-image.php?file=' . urlencode($noticia['crop3']);

        $contenidoNoticias .= "
        <table width='100%' cellpadding='0' cellspacing='0' border='0' 
            style='background:#ffffff;margin-bottom:15px;border-radius:10px;overflow:hidden;'>
        <tr class='stack-column'>
        <td width='240' valign='top' class='card-padding' style='padding:14px;'>
            <img src='{$imagenUrl}' width='220' class='stack-img' 
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

try {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = env('SMTP_HOST');
    $mail->SMTPAuth = true;
    $mail->Username = env('SMTP_USERNAME');
    $mail->Password = env('SMTP_PASSWORD');
    $mail->SMTPSecure = env('SMTP_SECURE');
    $mail->Port = env('SMTP_PORT');

    $mail->setFrom(env('SMTP_FROM_EMAIL'), env('SMTP_FROM_NAME'));
    $mail->addAddress($suscriptor['correo'], $suscriptor['nombre_completo']);

    error_log("Enviando correo a: " . $suscriptor['correo']);

    $mail->isHTML(true);
    $mail->Subject = 'Resumen diario de noticias';

    $unsubscribeUrl = 'https://www.catink.com.mx/views/email/unsubscribe.php?email=' . urlencode($suscriptor['correo']);
    $body = str_replace('{{unsubscribe_url}}', $unsubscribeUrl, $plantilla);

    $mail->Body = $body;
    
    if ($mail->send()) {
        error_log("Correo enviado exitosamente a: " . $suscriptor['correo']);
    } else {
        error_log("Error al enviar correo: " . $mail->ErrorInfo);
    }


    header("Location: ./../views/suscripciones.php?success=correo_enviado");
    exit();

} catch (Exception $e) {
    error_log("Excepción enviando correo: " . $e->getMessage());
    header("Location: ./../views/suscripciones.php?error=envio");
    exit();
}
?>
