<?php
session_start();
include("./aclcontroller.php");
proteger('noticias', 'eliminar');
include("../data/conexion.php");
require_once("../views/helpers/activity_log.php");

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id'])) {
    header("Location: ../views/borradores.php");
    exit();
}

$id = intval($_POST['id']);

if ($id <= 0) {
    header("Location: ../views/borradores.php?error=no_eliminado");
    exit();
}

// ==========================
// ELIMINACIÓN DIRECTA (sin papelera)
// Solo aplica a borradores no eliminados. Se borran las imágenes del disco
// y la fila (CASCADE limpia stats, likes y categoría).
// ==========================
$titulo = '';
$imgs = null;
$check = $con->prepare("SELECT titulo, crop1, crop2, crop3 FROM noticias WHERE id = ? AND borrador = 1 AND eliminado_en IS NULL");
$check->bind_param("i", $id);
$check->execute();
$res = $check->get_result();
$imgs = $res->fetch_assoc();
$check->close();

if (!$imgs) {
    header("Location: ../views/borradores.php?error=no_eliminado");
    exit();
}

$titulo = $imgs['titulo'] ?? '';

// Borrar imágenes del disco
foreach (['crop1', 'crop2', 'crop3'] as $c) {
    if (!empty($imgs[$c]) && file_exists(__DIR__ . "/../" . $imgs[$c])) {
        unlink(__DIR__ . "/../" . $imgs[$c]);
    }
}

// Borrar la fila
$stmt = $con->prepare("DELETE FROM noticias WHERE id = ? AND borrador = 1 AND eliminado_en IS NULL");
$stmt->bind_param("i", $id);
$ok = $stmt->execute();
if ($ok) {
    logActivity($con, 'eliminar', 'noticias', 'Eliminó permanentemente el borrador "' . $titulo . '" (ID ' . $id . ')');
}
$stmt->close();

header("Location: ../views/borradores.php?" . ($ok ? "msg=eliminado" : "error=no_eliminado"));
exit();
?>
