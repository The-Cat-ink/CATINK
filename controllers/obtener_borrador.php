<?php
// ============================================================
// OBTENER BORRADOR (para reanudar en crear.php)
// Devuelve los datos de un borrador (borrador = 1) para repoblar
// el formulario y continuar donde se quedó.
// ============================================================
session_start();
include("./aclcontroller.php");
proteger('noticias', 'crear');
include("../data/conexion.php");
require_once("../views/helpers/urlhelper.php");

header('Content-Type: application/json');

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    echo json_encode(['ok' => false]);
    exit;
}

$stmt = $con->prepare("
    SELECT id, titulo, descripcion, contenido, tipo_publicacion, calificacion, pros, contras,
           es_estreno, seccion_estreno, crop1, crop2, crop3, crop4
    FROM noticias
    WHERE id = ? AND borrador = 1 AND eliminado_en IS NULL
");
$stmt->bind_param("i", $id);
$stmt->execute();
$n = $stmt->get_result()->fetch_assoc();
if (!$n) {
    echo json_encode(['ok' => false]);
    exit;
}

// Categorías en su orden
$cats = [];
$rc = $con->prepare("SELECT categoria_id FROM noticia_categoria WHERE noticia_id = ? ORDER BY orden ASC");
$rc->bind_param("i", $id);
$rc->execute();
$res = $rc->get_result();
while ($row = $res->fetch_assoc()) {
    $cats[] = (int)$row['categoria_id'];
}

// URLs públicas de las imágenes guardadas
$crops = [];
foreach (['crop1', 'crop2', 'crop3', 'crop4'] as $ck) {
    $crops[$ck] = !empty($n[$ck]) ? imageUrl($n[$ck]) : null;
}

echo json_encode(['ok' => true, 'draft' => [
    'id'               => (int)$n['id'],
    'titulo'           => $n['titulo'],
    'descripcion'      => $n['descripcion'],
    'contenido'        => $n['contenido'],
    'tipo_publicacion' => $n['tipo_publicacion'],
    'calificacion'     => $n['calificacion'],
    'pros'             => $n['pros'],
    'contras'          => $n['contras'],
    'es_estreno'       => (int)$n['es_estreno'],
    'seccion_estreno'  => $n['seccion_estreno'],
    'categorias'       => $cats,
    'crops'            => $crops,
]]);
