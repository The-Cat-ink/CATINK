<?php
session_start();
include(__DIR__ . "/../data/conexion.php");

$ACL = $_SESSION['ACL']['suscripciones'] ?? ['eliminar' => false];

if (!$ACL['eliminar']) {
    header("Location: ./../views/suscripciones.php?error=permisos");
    exit();
}

if (!isset($_POST['id'])) {
    header("Location: ./../views/suscripciones.php?error=id");
    exit();
}

$id = intval($_POST['id']);

if (!$id) {
    header("Location: ./../views/suscripciones.php?error=id_invalido");
    exit();
}

$stmt = $con->prepare("DELETE FROM suscripciones WHERE id_suscripcion = ?");
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

header("Location: ./../views/suscripciones.php?success=1");
exit();
?>
