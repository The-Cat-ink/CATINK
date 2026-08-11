<?php
// Alterna si una categoría aparece o no en el menú (hamburguesa y desplegable
// de escritorio). No borra nada: la categoría sigue existiendo, sus noticias
// siguen asignadas y su página /categoria/x sigue siendo accesible.
include(__DIR__ . "/aclcontroller.php");
proteger('categorias', 'editar');
include(__DIR__ . "/../data/conexion.php");
require_once(__DIR__ . "/../views/helpers/activity_log.php");
header('Content-Type: application/json');

$id = intval($_POST['id'] ?? 0);
if ($id <= 0) {
    echo json_encode(['ok' => false, 'error' => 'ID de categoría no válido']);
    exit;
}

$stmt = $con->prepare("SELECT id_c, nombre, visible_menu FROM categorias WHERE id_c = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$cat = $stmt->get_result()->fetch_assoc();

if (!$cat) {
    echo json_encode(['ok' => false, 'error' => 'Categoría no encontrada']);
    exit;
}

$nuevoVisible = intval($cat['visible_menu']) === 1 ? 0 : 1;

$update = $con->prepare("UPDATE categorias SET visible_menu = ? WHERE id_c = ?");
$update->bind_param("ii", $nuevoVisible, $id);
if (!$update->execute()) {
    echo json_encode(['ok' => false, 'error' => 'No se pudo actualizar la visibilidad']);
    exit;
}

delete_cache('header_categorias');

logActivity(
    $con,
    'editar',
    'categorias',
    ($nuevoVisible ? 'Mostró' : 'Ocultó') . ' la categoría «' . $cat['nombre'] . '» en el menú'
);

echo json_encode([
    'ok' => true,
    'visible_menu' => $nuevoVisible,
    'nombre' => $cat['nombre']
]);
