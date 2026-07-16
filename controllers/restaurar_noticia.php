<?php
session_start();
include(__DIR__ . "/../data/conexion.php");
require_once(__DIR__ . "/../views/helpers/activity_log.php");


// Solo el superadmin puede acceder a la papelera
if (!($_SESSION['superadmin'] ?? false)) {
    header("Location: ../views/contenidos.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../views/papelera.php");
    exit();
}

// Aceptar un solo id o varios (ids[])
$ids = [];
if (isset($_POST['ids']) && is_array($_POST['ids'])) {
    $ids = array_map('intval', $_POST['ids']);
} elseif (isset($_POST['id'])) {
    $ids = [intval($_POST['id'])];
}
$ids = array_values(array_filter($ids, fn($v) => $v > 0));

if (empty($ids)) {
    header("Location: ../views/papelera.php");
    exit();
}

// Restaurar: quitar la marca de eliminación
$ph = implode(',', array_fill(0, count($ids), '?'));
$types = str_repeat('i', count($ids));
$stmt = $con->prepare("UPDATE noticias SET eliminado_en = NULL, eliminado_por = NULL WHERE eliminado_en IS NOT NULL AND id IN ($ph)");
$stmt->bind_param($types, ...$ids);

if ($stmt->execute() && $stmt->affected_rows > 0) {
    logActivity($con, 'restaurar', 'noticias', 'Restauró ' . count($ids) . ' noticia(s) de la papelera (IDs: ' . implode(', ', $ids) . ')');
    header("Location: ../views/papelera.php?msg=restaurada");
} else {
    header("Location: ../views/papelera.php?error=no_restaurada");
}
$stmt->close();
?>
