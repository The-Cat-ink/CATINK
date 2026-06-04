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

$stmt = $con->prepare("DELETE FROM suscripciones WHERE id_suscripcion = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    header("Location: ./../views/suscripciones.php?success=1");
} else {
    header("Location: ./../views/suscripciones.php?error=db");
}
exit();
?>
