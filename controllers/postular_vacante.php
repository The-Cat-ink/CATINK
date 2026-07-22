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
    $mail->Username   = env('SMTP_USERNAME', env('SMTP_FROM_EMAIL'));
    $mail->Password   = env('SMTP_PASSWORD', '');
    $mail->SMTPSecure = env('SMTP_SECURE', 'ssl');
    $mail->Port       = intval(env('SMTP_PORT', 465));
    $mail->CharSet    = 'UTF-8';

    $fromEmail = env('SMTP_FROM_EMAIL', 'no-reply@catink.com.mx');
    $fromName  = env('SMTP_FROM_NAME',  'CatInk');

    $mail->setFrom($fromEmail, $fromName . ' Careers');
    $mail->addAddress('contacto@catink.com.mx', 'CatInk Equipo');
    $mail->addReplyTo($email, $nombre);

    $mail->isHTML(true);
    $mail->Subject = "Nueva Postulación de $nombre - Puesto: $tituloVacante";

    if (file_exists($destPath)) {
        $mail->addAttachment($destPath, $fileName);
    }

    $bodyHtml = "
    <div style='font-family: Arial, sans-serif; max-width:600px; margin:auto; background:#121216; color:#ffffff; padding:24px; border-radius:12px; border:1px solid #222;'>
        <h2 style='color:#EF3363; margin-top:0;'>Nueva Solicitud de Empleo en CatInk</h2>
        <p style='color:#ddd; font-size:1.05rem;'>Se ha recibido una nueva candidatura a través del portal de reclutamiento.</p>
        <hr style='border-color:#333; margin:16px 0;'>
        <p style='color:#ffffff;'><strong>Puesto Solicitado:</strong> <span style='color:#EF3363; font-weight:bold;'>$tituloVacante</span></p>
        <p style='color:#ffffff;'><strong>Nombre del Postulante:</strong> $nombre</p>
        <p style='color:#ffffff;'><strong>Correo Electrónico:</strong> <a href='mailto:$email' style='color:#EF3363;'>$email</a></p>
        <p style='color:#ffffff;'><strong>Teléfono:</strong> " . ($telefono ?: 'No especificado') . "</p>
        <div style='background:#1a1a20; padding:14px; border-radius:8px; margin-top:14px; border-left:4px solid #EF3363;'>
            <strong style='color:#EF3363;'>Motivación / Presentación:</strong><br>
            <p style='color:#ddd; margin-top:6px; white-space:pre-wrap;'>" . nl2br(htmlspecialchars($razon)) . "</p>
        </div>
        <p style='color:#888; font-size:0.85rem; margin-top:20px;'>* Se adjunta a este correo el archivo CV recibido ($fileName).</p>
    </div>
    ";

    $mail->Body = $bodyHtml;
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
