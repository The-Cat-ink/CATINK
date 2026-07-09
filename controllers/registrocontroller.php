<?php
include("../data/conexion.php");
include("../views/helpers/urlhelper.php");

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
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
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

$stmt = $con->prepare("
    INSERT INTO lectores 
    (nombre, usuario, correo, password_hash, fecha_nacimiento, sexo, entidad)
    VALUES (?, ?, ?, ?, ?, ?, ?)
");
$stmt->bind_param("sssssss", $nombre, $usuario, $correo, $passHash, $fecha_nacimiento, $sexo, $entidad);

if ($stmt->execute()) {
    $lector_id = $stmt->insert_id;
    $stmt->close();

    // Si aceptó recibir correos, registrar en suscripciones
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
            $geoJson = @file_get_contents("http://ip-api.com/json/" . urlencode($ip));
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

    // Inicio de sesión automático
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    session_regenerate_id(true);
    $_SESSION['usuario'] = $usuario;
    $_SESSION['tipo'] = 'lector';
    $_SESSION['id_lector'] = $lector_id;
    $_SESSION['nombre_completo'] = $nombre;
    $_SESSION['superadmin'] = false;
    $_SESSION['ACL'] = [];

    // Limpiar datos temporales de registro
    unset($_SESSION['temp_registro']);

    // Redirección al perfil
    header('Location: ' . basePath() . '/perfil');
    exit;
} else {
    $stmt->close();
    header('Location: ' . basePath() . '/login?modo=registro&reg_error=5');
    exit;
}
