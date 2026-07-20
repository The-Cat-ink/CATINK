<?php
if(session_status() == PHP_SESSION_NONE){
    session_start();
}
require_once(dirname(dirname(__FILE__)) . "/data/conexion.php");
require_once(dirname(dirname(__FILE__)) . "/views/helpers/helper.php");

function proteger($modulo, $accion, $json = true) {
    global $con;
    
    if (!isset($_SESSION['usuario'])) {
        if ($json) {
            header("Content-Type: application/json");
            echo json_encode(["success" => false, "error" => "Acceso denegado"]);
        } else {
            header("Location: ../index.php");
        }
        exit();
    }

    // Refrescar permisos desde la base de datos en tiempo real
    if (isset($con) && $con instanceof mysqli) {
        $stmt = $con->prepare("SELECT * FROM usuarios WHERE usuario = ?");
        if ($stmt) {
            $stmt->bind_param("s", $_SESSION['usuario']);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res && $u = $res->fetch_assoc()) {
                $_SESSION['ACL'] = mapearPermisos($u);
                $_SESSION['superadmin'] = esSuperAdmin($u);
            }
        }
    }

    if (!($_SESSION['superadmin'] ?? false)) {
        if (!tienePermiso($modulo, $accion)) {
            if ($json) {
                header("Content-Type: application/json");
                echo json_encode([
                    "success" => false,
                    "error" => "No tienes permisos suficientes para realizar esta acción (" . $modulo . " - " . $accion . ")"
                ]);
            } else {
                header("Location: admin.php");
            }
            exit();
        }
    }
}