<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once(__DIR__ . "/../PHPMailer/src/PHPMailer.php");
require_once(__DIR__ . "/../PHPMailer/src/Exception.php");
require_once(__DIR__ . "/../PHPMailer/src/SMTP.php");

include("../data/conexion.php");
include("../views/helpers/urlhelper.php");

// ============================
// PROTECCIÓN CONTRA DOBLE ENVÍO (servidor)
// ============================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$formToken = $_POST['form_token'] ?? '';
if (empty($formToken) || !isset($_SESSION['reg_form_token']) || $formToken !== $_SESSION['reg_form_token']) {
    // Token inválido o ya usado — probable doble envío
    // Si el registro ya fue exitoso la primera vez, redirigir al éxito
    if (isset($_SESSION['last_reg_email'])) {
        $lastEmail = $_SESSION['last_reg_email'];
        unset($_SESSION['last_reg_email']);
        unset($_SESSION['temp_registro']);
        header('Location: ' . basePath() . '/login?registro=verificar&email=' . urlencode($lastEmail));
        exit;
    }
    header('Location: ' . basePath() . '/login?modo=registro');
    exit;
}
// Invalidar el token inmediatamente para que no se pueda reusar
unset($_SESSION['reg_form_token']);

$nombre  = trim($_POST['nombre'] ?? '');
$usuario = trim($_POST['usuario'] ?? '');
$correo  = trim($_POST['correo'] ?? '');
$pass    = $_POST['pass'] ?? '';
$pass2   = $_POST['pass2'] ?? '';
$fecha_nacimiento = trim($_POST['fecha_nacimiento'] ?? '');
$sexo    = trim($_POST['sexo'] ?? '');
$entidad = trim($_POST['entidad'] ?? '');
$terminos_condiciones = $_POST['terminos_condiciones'] ?? '';

// Guardar datos temporales para rellenar el formulario en caso de error
$_SESSION['temp_registro'] = [
    'nombre' => $nombre,
    'usuario' => $usuario,
    'correo' => $correo,
    'fecha_nacimiento' => $fecha_nacimiento,
    'sexo' => $sexo,
    'entidad' => $entidad
];

// ============================
// VALIDACIONES
// ============================
if (empty($nombre) || empty($usuario) || empty($correo) || empty($pass) || empty($pass2) || empty($fecha_nacimiento) || empty($sexo) || empty($entidad)) {
    header('Location: ' . basePath() . '/login?modo=registro&reg_error=1');
    exit;
}
if (empty($terminos_condiciones)) {
    header('Location: ' . basePath() . '/login?modo=registro&reg_error=6');
    exit;
}
if ($pass !== $pass2) {
    header('Location: ' . basePath() . '/login?modo=registro&reg_error=2');
    exit;
}

// Validar complejidad de contraseña (Mínimo 8 caracteres, 1 mayúscula, 1 minúscula, 1 número)
if (strlen($pass) < 8 || !preg_match('/[A-Z]/', $pass) || !preg_match('/[a-z]/', $pass) || !preg_match('/\d/', $pass)) {
    header('Location: ' . basePath() . '/login?modo=registro&reg_error=7');
    exit;
}

// ============================
// VERIFICAR DUPLICADOS EN LECTORES Y USUARIOS (ADMINS)
// ============================
$stmt = $con->prepare("SELECT id FROM lectores WHERE usuario = ?");
$stmt->bind_param("s", $usuario);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    header('Location: ' . basePath() . '/login?modo=registro&reg_error=3');
    exit;
}

$stmt = $con->prepare("SELECT id_u FROM usuarios WHERE usuario = ?");
$stmt->bind_param("s", $usuario);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    header('Location: ' . basePath() . '/login?modo=registro&reg_error=3');
    exit;
}

$stmt = $con->prepare("SELECT id FROM lectores WHERE correo = ?");
$stmt->bind_param("s", $correo);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    header('Location: ' . basePath() . '/login?modo=registro&reg_error=4');
    exit;
}

$stmt = $con->prepare("SELECT id_u FROM usuarios WHERE correo = ?");
$stmt->bind_param("s", $correo);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    header('Location: ' . basePath() . '/login?modo=registro&reg_error=4');
    exit;
}

// ============================
// INSERTAR LECTOR (tabla lectores)
// ============================
$passHash = password_hash($pass, PASSWORD_BCRYPT);
$token = bin2hex(random_bytes(32));

// mysqli lanza excepción si a la tabla lectores le faltan las columnas de
// verificación (verificado / token_verificacion). Sin capturarla, el registro
// muere con un 500 mudo justo al pulsar el botón.
try {
    $stmt = $con->prepare("
        INSERT INTO lectores
        (nombre, usuario, correo, password_hash, fecha_nacimiento, sexo, entidad, verificado, token_verificacion)
        VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?)
    ");
    $stmt->bind_param("ssssssss", $nombre, $usuario, $correo, $passHash, $fecha_nacimiento, $sexo, $entidad, $token);
} catch (\Throwable $e) {
    error_log('Error al preparar registro de lector: ' . $e->getMessage());
    header('Location: ' . basePath() . '/login?modo=registro&reg_error=5');
    exit;
}

try {
    $ejecutado = $stmt->execute();
} catch (\Throwable $e) {
    error_log('Error al registrar lector: ' . $e->getMessage());
    $stmt->close();
    header('Location: ' . basePath() . '/login?modo=registro&reg_error=5');
    exit;
}

if ($ejecutado) {
    $lector_id = $stmt->insert_id;
    $stmt->close();

    // Guardar el correo por si hay un doble envío posterior
    $_SESSION['last_reg_email'] = $correo;

    // Limpiar datos temporales de registro
    unset($_SESSION['temp_registro']);

    // ============================
    // 1. SUSCRIPCIONES Y GEOLOCALIZACIÓN (SÍNCRONO - Max 2s)
    // ============================
    if (isset($_POST['recibir_correos'])) {
        $ip = 'Desconocido';
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        $pais = 'Desconocido';
        $estado = 'Desconocido';

        // Fail-soft: la suscripción al boletín es secundaria y nunca debe
        // tumbar el registro. El caso típico es un correo que ya estaba
        // suscrito desde antes (suscripciones.correo es UNIQUE): el duplicado
        // lanzaba excepción y el lector veía un 500 con la cuenta ya creada.
        try {
            $stmtSub = $con->prepare("INSERT IGNORE INTO suscripciones (nombre_completo, correo, sexo, ip, pais, estado) VALUES (?, ?, ?, ?, ?, ?)");
            $stmtSub->bind_param("ssssss", $nombre, $correo, $sexo, $ip, $pais, $estado);
            $stmtSub->execute();
            $stmtSub->close();
        } catch (\Throwable $e) {
            error_log('Registro: no se pudo suscribir al boletín: ' . $e->getMessage());
        }
    }

    // ============================
    // 2. ENVIAR CORREO DE VERIFICACIÓN (SÍNCRONO - Max 5s)
    // ============================
    $smtpHost = env('SMTP_HOST');
    if (!empty($smtpHost)) {
        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Timeout = 5; // Timeout muy bajo de 5 segundos para que nunca se cuelgue la UI
            // Timeout solo cubre la conexion; las lecturas usan Timelimit (300s
            // por defecto). Un SMTP que acepta y calla dejaba el boton en
            // "Registrando..." hasta 5 minutos.
            $mail->getSMTPInstance()->Timelimit = 5;
            $mail->SMTPKeepAlive = false;
            $mail->Host = $smtpHost;
            $mail->SMTPAuth = true;
            $mail->Username = env('SMTP_USERNAME');
            $mail->Password = env('SMTP_PASSWORD');
            $mail->SMTPSecure = env('SMTP_SECURE', 'tls');
            $mail->Port = (int) env('SMTP_PORT', 587);

            // Ignorar errores de certificado SSL (común en servidores de correo locales/cPanel)
            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );

            $mail->setFrom(env('SMTP_FROM_EMAIL'), env('SMTP_FROM_NAME'));
            $mail->addAddress($correo, $nombre);

            $proto = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
            $domain = $_SERVER['HTTP_HOST'] ?? 'catink.test';
            $verifyUrl = "{$proto}://{$domain}" . basePath() . "/verificar.php?token=" . urlencode($token);

            $mail->isHTML(true);
            $mail->Subject = "Verifica tu cuenta en CatInk";

            $htmlBody = "
            <div style='font-family: Arial, sans-serif; max-width:600px; margin:auto; background:#f9f9f9; padding:20px; border-radius:10px; border: 1px solid #eee;'>
                <h2 style='color:#EF3363; text-align:center;'>¡Te damos la bienvenida a CatInk!</h2>
                <p>Hola <strong>{$nombre}</strong>,</p>
                <p>Gracias por registrarte en nuestra plataforma de noticias y comunidad. Para activar tu cuenta y empezar a participar, por favor confirma tu dirección de correo haciendo clic en el siguiente botón:</p>
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

        } catch (\Throwable $e) {
            error_log("Error enviando correo de verificacion: " . $e->getMessage());
        }
    }

    // ============================
    // 3. REDIRIGIR AL USUARIO
    // ============================
    header('Location: ' . basePath() . '/login?registro=verificar&email=' . urlencode($correo));
    exit;
} else {
    $stmt->close();
    header('Location: ' . basePath() . '/login?modo=registro&reg_error=5');
    exit;
}
