<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once(__DIR__ . "/../data/conexion.php");
require_once(__DIR__ . "/../data/env.php");
require_once(__DIR__ . "/../PHPMailer/src/PHPMailer.php");
require_once(__DIR__ . "/../PHPMailer/src/Exception.php");
require_once(__DIR__ . "/../PHPMailer/src/SMTP.php");

$nombre = trim($_POST['nombre'] ?? '');
$email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$telefono = trim($_POST['telefono'] ?? '');
$razon = trim($_POST['razon'] ?? '');
$vacante_id = intval($_POST['vacante_id'] ?? 0);

if (empty($nombre) || !$email || empty($razon)) {
    echo json_encode(['error' => 'Por favor completa todos los campos requeridos correctamente.']);
    exit;
}

if (!isset($_FILES['cv']) || $_FILES['cv']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['error' => 'Por favor adjunta tu CV en formato PDF o Word.']);
    exit;
}

// Validar extensión de CV
$fileName = $_FILES['cv']['name'];
$fileTmp = $_FILES['cv']['tmp_name'];
$ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

if (!in_array($ext, ['pdf', 'doc', 'docx', 'png', 'jpg', 'jpeg', 'webp', 'gif'])) {
    echo json_encode(['error' => 'Formato no permitido. Por favor sube tu CV o Portafolio en formato PDF, Word o Imagen (JPG, PNG, WEBP).']);
    exit;
}

// Crear directorio de subida de CVs si no existe
$uploadDir = __DIR__ . "/../uploads/cv/";
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$newFileName = "cv_" . time() . "_" . preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $fileName);
$destPath = $uploadDir . $newFileName;
$relativeDbPath = "uploads/cv/" . $newFileName;

if (!move_uploaded_file($fileTmp, $destPath)) {
    echo json_encode(['error' => 'No se pudo guardar el archivo de CV en el servidor.']);
    exit;
}

// Obtener título de la vacante
$tituloVacante = "General";
if ($vacante_id > 0) {
    $stmtV = $con->prepare("SELECT titulo, tag FROM vacantes_equipo WHERE id = ?");
    $stmtV->bind_param("i", $vacante_id);
    $stmtV->execute();
    $resV = $stmtV->get_result()->fetch_assoc();
    if ($resV) {
        $tituloVacante = $resV['titulo'] . " (" . $resV['tag'] . ")";
    }
}

// Guardar en base de datos solicitudes_vacantes
$stmtIns = $con->prepare("INSERT INTO solicitudes_vacantes (vacante_id, nombre, email, telefono, razon, cv_archivo, fecha_solicitud) VALUES (?, ?, ?, ?, ?, ?, NOW())");
$vacIdParam = $vacante_id > 0 ? $vacante_id : null;
$stmtIns->bind_param("isssss", $vacIdParam, $nombre, $email, $telefono, $razon, $relativeDbPath);
$dbSaved = $stmtIns->execute();

// Enviar correo de notificación a catink.oficial@gmail.com
$emailSent = false;
$emailErrorMsg = "";

try {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = env('SMTP_HOST', 'smtp.hostinger.com');
    $mail->SMTPAuth   = true;
    $mail->Username   = env('SMTP_AUTH_USERNAME', env('SMTP_USERNAME', env('SMTP_FROM_EMAIL')));
    $mail->Password   = env('SMTP_AUTH_PASSWORD', env('SMTP_PASSWORD', ''));
    $mail->SMTPSecure = env('SMTP_SECURE', 'ssl');
    $mail->Port       = intval(env('SMTP_PORT', 465));
    $mail->CharSet    = 'UTF-8';

    $fromEmail = env('SMTP_AUTH_FROM_EMAIL', env('SMTP_FROM_EMAIL', 'no-reply@catink.com.mx'));
    $fromName  = env('SMTP_AUTH_FROM_NAME',  env('SMTP_FROM_NAME',  'CatInk'));

    require_once(__DIR__ . "/../views/helpers/emailhelper.php");

    $mail->setFrom($fromEmail, $fromName . ' Vacantes');
    $mail->addAddress('contacto@catink.com.mx', 'CatInk Equipo');
    $mail->addReplyTo($email, $nombre);

    $mail->isHTML(true);
    $mail->Subject = "Nueva Postulación de $nombre - Puesto: $tituloVacante";

    if (file_exists($destPath)) {
        $mail->addAttachment($destPath, $fileName);
    }

    $nomEsc = htmlspecialchars($nombre);
    $emailEsc = htmlspecialchars($email);
    $telEsc = htmlspecialchars($telefono ?: 'No especificado');
    $puestoEsc = htmlspecialchars($tituloVacante);
    $fileEsc = htmlspecialchars($fileName);
    $razonEsc = nl2br(htmlspecialchars($razon));

    $content = "
        <p style='color:#475569; font-size:15px;'>Se ha recibido una nueva candidatura a través del portal de reclutamiento.</p>
        
        <table width='100%' cellpadding='0' cellspacing='0' style='background:#f8fafc; border-radius:12px; padding:18px; margin:20px 0; border:1px solid #e2e8f0;'>
            <tr><td style='padding:6px 0; color:#64748b; font-size:13px; font-weight:700;'>Puesto Solicitado:</td><td style='padding:6px 0; color:#EF3363; font-weight:800; font-size:15px;'>{$puestoEsc}</td></tr>
            <tr><td style='padding:6px 0; color:#64748b; font-size:13px; font-weight:700;'>Postulante:</td><td style='padding:6px 0; color:#0f172a; font-weight:700;'>{$nomEsc}</td></tr>
            <tr><td style='padding:6px 0; color:#64748b; font-size:13px; font-weight:700;'>Correo:</td><td style='padding:6px 0;'><a href='mailto:{$emailEsc}' style='color:#EF3363;'>{$emailEsc}</a></td></tr>
            <tr><td style='padding:6px 0; color:#64748b; font-size:13px; font-weight:700;'>Teléfono:</td><td style='padding:6px 0; color:#334155;'>{$telEsc}</td></tr>
        </table>

        <div style='background:#f1f5f9; padding:18px; border-radius:12px; border-left:4px solid #EF3363; margin-top:16px;'>
            <strong style='color:#EF3363; font-size:13px; text-transform:uppercase; letter-spacing:0.05em;'>Motivación / Presentación:</strong>
            <p style='color:#334155; margin:10px 0 0; line-height:1.7; white-space:pre-wrap;'>{$razonEsc}</p>
        </div>

        <p style='color:#64748b; font-size:12px; margin-top:24px;'>* Se adjunta a este correo el archivo de CV/Portafolio recibido ({$fileEsc}).</p>
    ";

    $mail->Body = renderCatInkEmail([
        'title'     => 'Nueva Solicitud de Empleo',
        'badge'     => 'Reclutamiento CatInk',
        'content'   => $content,
        'cta_url'   => 'mailto:' . $email,
        'cta_text'  => 'Responder Candidato'
    ]);

    $mail->send();
    $emailSent = true;
} catch (Exception $e) {
    error_log("CATINK MAIL RECRUITMENT ERROR: " . $e->getMessage());
    $emailErrorMsg = $e->getMessage();
}

if ($dbSaved) {
    echo json_encode([
        'success' => true,
        'message' => '¡Tu solicitud ha sido enviada con éxito! Revisaremos tu perfil y nos pondremos en contacto contigo pronto.'
    ]);
} else {
    echo json_encode(['error' => 'Error al guardar la solicitud en la base de datos.']);
}
