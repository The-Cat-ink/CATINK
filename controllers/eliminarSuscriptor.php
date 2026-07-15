<?php
session_start();
include(__DIR__ . "/../data/conexion.php");

$superadmin = $_SESSION['superadmin'] ?? false;
$tienePermiso = $superadmin || ($_SESSION['ACL']['suscripciones']['eliminar'] ?? false);

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
    error_log("ID inválido recibido: " . $_POST['id']);
    header("Location: ./../views/suscripciones.php?error=id_invalido");
    exit();
}

$stmt = $con->prepare("DELETE FROM suscripciones WHERE id_sub = ?");
if (!$stmt) {
    error_log("Error preparando statement: " . $con->error);
    header("Location: ./../views/suscripciones.php?error=db");
    exit();
}

$stmt->bind_param("i", $id);
if (!$stmt->execute()) {
    error_log("Error ejecutando delete: " . $stmt->error);
    header("Location: ./../views/suscripciones.php?error=db");
    exit();
}

require_once(__DIR__ . "/../views/helpers/activity_log.php");
logActivity($con, 'eliminar', 'suscripciones', 'Eliminó suscriptor ID ' . $id);

header("Location: ./../views/suscripciones.php?success=1");
exit();
?>
