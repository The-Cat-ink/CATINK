<?php
session_start();
include("../data/conexion.php");
require_once("../views/helpers/activity_log.php");


// Solo el superadmin puede eliminar definitivamente
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

$ph = implode(',', array_fill(0, count($ids), '?'));
$types = str_repeat('i', count($ids));

// 1. Obtener imágenes de las noticias que ya están en la papelera
$check = $con->prepare("SELECT crop1, crop2, crop3 FROM noticias WHERE eliminado_en IS NOT NULL AND id IN ($ph)");
$check->bind_param($types, ...$ids);
$check->execute();
$res = $check->get_result();

$encontradas = 0;
while ($imgs = $res->fetch_assoc()) {
    $encontradas++;
    foreach (['crop1', 'crop2', 'crop3'] as $c) {
        if (!empty($imgs[$c]) && file_exists(__DIR__ . "/../" . $imgs[$c])) {
            unlink(__DIR__ . "/../" . $imgs[$c]);
        }
    }
}
$check->close();

if ($encontradas === 0) {
    header("Location: ../views/papelera.php?error=no_encontrada");
    exit();
}

// 2. Eliminar noticias (CASCADE borra stats, likes y categoría)
$stmt = $con->prepare("DELETE FROM noticias WHERE eliminado_en IS NOT NULL AND id IN ($ph)");
$stmt->bind_param($types, ...$ids);
if ($stmt->execute()) {
    logActivity($con, 'eliminar', 'noticias', 'Eliminó definitivamente ' . count($ids) . ' noticia(s) (IDs: ' . implode(', ', $ids) . ')');
    header("Location: ../views/papelera.php?msg=eliminada_def");
} else {
    header("Location: ../views/papelera.php?error=no_eliminada");
}
$stmt->close();
?>
