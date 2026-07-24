<?php
include(__DIR__ . "/aclcontroller.php");
proteger('publicidad', 'editar');
include(__DIR__ . "/../data/conexion.php");
header('Content-Type: application/json');

$id = intval($_POST['id'] ?? 0);
if ($id <= 0) {
    echo json_encode(['ok' => false, 'error' => 'ID de campaña no válido']);
    exit;
}

// Obtener campaña actual
$stmt = $con->prepare("SELECT id_pub, titulo, activo, fecha_inicio, fecha_fin FROM publicidad WHERE id_pub = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$pub = $stmt->get_result()->fetch_assoc();

if (!$pub) {
    echo json_encode(['ok' => false, 'error' => 'Campaña no encontrada']);
    exit;
}

$nuevoActivo = (isset($pub['activo']) && intval($pub['activo']) === 1) ? 0 : 1;

$update = $con->prepare("UPDATE publicidad SET activo = ? WHERE id_pub = ?");
$update->bind_param("ii", $nuevoActivo, $id);
$update->execute();

$hoy = date('Y-m-d');
$ini = !empty($pub['fecha_inicio']) ? date('Y-m-d', strtotime($pub['fecha_inicio'])) : null;
$fin = !empty($pub['fecha_fin'])    ? date('Y-m-d', strtotime($pub['fecha_fin']))    : null;

$estadoStr = 'inactiva';
if ($nuevoActivo === 1) {
    if ($ini !== null && $ini > $hoy) $estadoStr = 'programada';
    else if ($fin !== null && $fin >= $hoy) $estadoStr = 'activa';
    else $estadoStr = 'vencida';
}

logActivity($con, 'editar', 'publicidad', 'Cambió estado de publicidad ID ' . $id . ' a ' . ($nuevoActivo ? 'activo' : 'inactivo'));

echo json_encode([
    'ok' => true,
    'activo' => $nuevoActivo,
    'estado' => $estadoStr,
    'titulo' => $pub['titulo']
]);
