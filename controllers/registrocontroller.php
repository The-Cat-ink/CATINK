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

$stmt = $con->prepare("
    INSERT INTO lectores 
    (nombre, usuario, correo, password_hash, fecha_nacimiento, sexo, entidad, verificado, token_verificacion)
    VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?)
");
$stmt->bind_param("ssssssss", $nombre, $usuario, $correo, $passHash, $fecha_nacimiento, $sexo, $entidad, $token);

if ($stmt->execute()) {
    $lector_id = $stmt->insert_id;
    $stmt->close();

    // Guardar el correo por si hay un doble envío posterior
    $_SESSION['last_reg_email'] = $correo;

    // Limpiar datos temporales de registro
    unset($_SESSION['temp_registro']);

    // ============================
    // REDIRIGIR AL USUARIO INMEDIATAMENTE
    // ============================
    header('Location: ' . basePath() . '/login?registro=verificar&email=' . urlencode($correo));

    // Cerrar la conexión con el navegador para que no espere las tareas lentas
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }
    ignore_user_abort(true);
    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    } else {
        if (ob_get_level() > 0) ob_end_flush();
        flush();
    }

    // ============================
    // TAREAS EN SEGUNDO PLANO (el usuario ya fue redirigido)
    // ============================

    // Suscripciones + geolocalización
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
        try {
            $ctx = stream_context_create(['http' => ['timeout' => 3]]);
            $geoJson = @file_get_contents("http://ip-api.com/json/" . urlencode($ip), false, $ctx);
            if ($geoJson !== false) {
                $geo = json_decode($geoJson, true);
                $pais = $geo['country'] ?? 'Desconocido';
                $estado = $geo['regionName'] ?? 'Desconocido';
            }
        } catch (Exception $e) {
            // Ignorar errores de geolocalización
        }

        $stmtSub = $con->prepare("INSERT INTO suscripciones (nombre_completo, correo, sexo, ip, pais, estado) VALUES (?, ?, ?, ?, ?, ?)");
        $stmtSub->bind_param("ssssss", $nombre, $correo, $sexo, $ip, $pais, $estado);
        $stmtSub->execute();
        $stmtSub->close();
    }

    // Enviar correo de verificación
    $smtpHost = env('SMTP_HOST');
    if (!empty($smtpHost)) {
        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Timeout = 10;
            $mail->SMTPKeepAlive = false;
            $mail->Host = $smtpHost;
            $mail->SMTPAuth = true;
            $mail->Username = env('SMTP_USERNAME');
            $mail->Password = env('SMTP_PASSWORD');
            $mail->SMTPSecure = env('SMTP_SECURE');
            $mail->Port = env('SMTP_PORT');

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

        } catch (Exception $e) {
            error_log("Error enviando correo de verificacion: " . $e->getMessage());
        }
    }

    exit;
} else {
    $stmt->close();
    header('Location: ' . basePath() . '/login?modo=registro&reg_error=5');
    exit;
}
