<?php
include("../data/conexion.php");
include("../views/helpers/helper.php");
include("../views/helpers/urlhelper.php");
// ============================
// OBTENER DATOS DEL FORMULARIO
// ============================
$usuario = $_POST['usuario'] ?? '';
$pass = $_POST['pass'] ?? '';
if (empty($usuario) || empty($pass)) {
    header('Location: ' . basePath() . '/login?error=1');
    exit();
}
// ============================
// 1. BUSCAR EN TABLA USUARIOS (administradores)
// ============================
$stmt = $con->prepare("SELECT * FROM usuarios WHERE usuario = ?");
$stmt->bind_param("s", $usuario);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows > 0) {
    $fila = $result->fetch_assoc();
    $passCorrecta = false;
    if ($pass === $fila['pass']) {
        $passCorrecta = true;
    } elseif (password_verify($pass, $fila['pass'])) {
        $passCorrecta = true;
    }
    if ($passCorrecta) {
        session_start();
        session_regenerate_id(true);
        $_SESSION['usuario'] = $fila['usuario'];
        $_SESSION['tipo'] = 'admin';
        $_SESSION['ACL'] = mapearPermisos($fila);
        if(esSuperAdmin($fila)){
            $_SESSION['superadmin'] = true;
            header('Location: ' . basePath() . '/views/admin.php');
            exit();
        }
        $modulo = primerModuloLectura($_SESSION['ACL']);
        if($modulo){
            $_SESSION['superadmin'] = false;
            header('Location: ' . basePath() . '/' . $modulo);
            exit();
        }
        // Admin sin módulos asignados
        $_SESSION['superadmin'] = false;
        header('Location: ' . basePath() . '/');
        exit();
    } else {
        header('Location: ' . basePath() . '/login?error=2');
        exit();
    }
}

// ============================
// 2. BUSCAR EN TABLA LECTORES (usuarios públicos)
// ============================
$stmt = $con->prepare("SELECT * FROM lectores WHERE usuario = ?");
$stmt->bind_param("s", $usuario);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows > 0) {
    $lector = $result->fetch_assoc();
    if (password_verify($pass, $lector['password_hash'])) {
        if ($lector['verificado'] == 0) {
            header('Location: ' . basePath() . '/login?error=3');
            exit();
        }
        session_start();
        session_regenerate_id(true);
        $_SESSION['usuario'] = $lector['usuario'];
        $_SESSION['tipo'] = 'lector';
        $_SESSION['id_lector'] = $lector['id'];
        $_SESSION['nombre_completo'] = $lector['nombre'];
        $_SESSION['superadmin'] = false;
        $_SESSION['ACL'] = [];
        header('Location: ' . basePath() . '/');
        exit();
    } else {
        header('Location: ' . basePath() . '/login?error=2');
        exit();
    }
}

// ============================
// 3. USUARIO NO ENCONTRADO EN NINGUNA TABLA
// ============================
header('Location: ' . basePath() . '/login?error=2');
exit();
?>