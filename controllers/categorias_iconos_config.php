<?php
// Interruptores globales: si los iconos de categoría se muestran en el menú
// móvil (hamburguesa) y/o en el menú de escritorio (barra horizontal).
//
// Se guardan como filas de la tabla `secciones`, igual que publicidad, videos y
// comentarios, para no inventar una tabla de configuración nueva.
include(__DIR__ . "/aclcontroller.php");
proteger('categorias', 'editar');
include(__DIR__ . "/../data/conexion.php");
require_once(__DIR__ . "/../views/helpers/activity_log.php");
header('Content-Type: application/json');

$CLAVES = [
    'iconos_menu_movil'      => 'móvil',
    'iconos_menu_escritorio' => 'escritorio',
];

$clave = trim($_POST['clave'] ?? '');
if (!isset($CLAVES[$clave])) {
    echo json_encode(['ok' => false, 'error' => 'Ajuste no válido']);
    exit;
}

$stmt = $con->prepare("SELECT id_s, estado FROM secciones WHERE nombre = ? LIMIT 1");
$stmt->bind_param("s", $clave);
$stmt->execute();
$fila = $stmt->get_result()->fetch_assoc();

// Si la fila no existe todavía (producción sin migrar), se crea con el valor
// contrario al que tendría por defecto, que es justo lo que pidió el usuario.
if (!$fila) {
    $inicial = $clave === 'iconos_menu_movil' ? 0 : 1;
    $ins = $con->prepare("INSERT INTO secciones (nombre, estado) VALUES (?, ?)");
    $ins->bind_param("si", $clave, $inicial);
    if (!$ins->execute()) {
        echo json_encode(['ok' => false, 'error' => 'No se pudo guardar el ajuste']);
        exit;
    }
    $nuevoEstado = $inicial;
} else {
    $nuevoEstado = intval($fila['estado']) === 1 ? 0 : 1;
    $upd = $con->prepare("UPDATE secciones SET estado = ? WHERE id_s = ?");
    $upd->bind_param("ii", $nuevoEstado, $fila['id_s']);
    if (!$upd->execute()) {
        echo json_encode(['ok' => false, 'error' => 'No se pudo guardar el ajuste']);
        exit;
    }
}

logActivity(
    $con,
    'editar',
    'categorias',
    ($nuevoEstado ? 'Activó' : 'Desactivó') . ' los iconos de categoría en el menú de ' . $CLAVES[$clave]
);

echo json_encode(['ok' => true, 'clave' => $clave, 'estado' => $nuevoEstado]);
