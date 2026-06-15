<?php
include(__DIR__ . "/aclcontroller.php");
proteger('contenidos', 'editar');
include(__DIR__ . "/../data/conexion.php");
header('Content-Type: application/json');

$id     = (int) ($_POST['id'] ?? 0);
$nombre = trim($_POST['nombre'] ?? '');

if (!$id) {
    echo json_encode(['ok' => false, 'error' => 'ID inválido.']);
    exit;
}

// Fecha+hora de expiración ("YYYY-MM-DD HH:MM:SS" o vacío = sin vencimiento)
$fechaRaw = trim($_POST['fecha_expiracion'] ?? '');
$fechaExp = null;
if ($fechaRaw !== '' && preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $fechaRaw)) {
    $ts = strtotime($fechaRaw);
    if ($ts) $fechaExp = $fechaRaw;
}

$stmt = $con->prepare("UPDATE logos_marcas SET nombre = ?, fecha_expiracion = ? WHERE id_logo = ?");
$stmt->bind_param("ssi", $nombre, $fechaExp, $id);

if ($stmt->execute()) {
    echo json_encode(['ok' => true, 'nombre' => $nombre, 'fecha_expiracion' => $fechaExp]);
} else {
    echo json_encode(['ok' => false, 'error' => 'Error al guardar.']);
}
