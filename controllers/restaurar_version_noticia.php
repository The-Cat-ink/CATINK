<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

include(__DIR__ . "/aclcontroller.php");
proteger('noticias','editar');
require_once(__DIR__ . "/../data/conexion.php");
require_once(__DIR__ . "/../views/helpers/urlhelper.php");
require_once(__DIR__ . "/../views/helpers/activity_log.php");

$data = json_decode(file_get_contents('php://input'), true);
$version_id = intval($data['version_id'] ?? $_POST['version_id'] ?? 0);

if ($version_id <= 0) {
    echo json_encode(['error' => 'ID de versión no válido']);
    exit;
}

// Obtener versión a restaurar
$stmtV = $con->prepare("SELECT * FROM historial_ediciones_noticias WHERE id = ?");
$stmtV->bind_param("i", $version_id);
$stmtV->execute();
$version = $stmtV->get_result()->fetch_assoc();

if (!$version) {
    echo json_encode(['error' => 'La versión solicitada no existe']);
    exit;
}

$noticia_id = (int)$version['noticia_id'];
$usuario_id = intval($_SESSION['id_u'] ?? 0);

// Guardar versión actual como entrada del historial antes de restaurar
$stmtCurrent = $con->prepare("SELECT titulo, descripcion, contenido FROM noticias WHERE id = ?");
$stmtCurrent->bind_param("i", $noticia_id);
$stmtCurrent->execute();
$current = $stmtCurrent->get_result()->fetch_assoc();

if ($current) {
    $motivo = "Estado previo a restauración de versión del " . date('d/m/Y H:i', strtotime($version['fecha_edicion']));
    $stmtHist = $con->prepare("INSERT INTO historial_ediciones_noticias (noticia_id, usuario_id, titulo, descripcion, contenido, motivo_cambio, fecha_edicion) VALUES (?, ?, ?, ?, ?, ?, NOW())");
    $stmtHist->bind_param("iissss", $noticia_id, $usuario_id, $current['titulo'], $current['descripcion'], $current['contenido'], $motivo);
    $stmtHist->execute();
}

// Aplicar restauración en la tabla noticias
$newSlug = generateSlug($version['titulo']);
$stmtUpd = $con->prepare("UPDATE noticias SET titulo = ?, slug = ?, descripcion = ?, contenido = ?, editado_por = ? WHERE id = ?");
$stmtUpd->bind_param("ssssii", $version['titulo'], $newSlug, $version['descripcion'], $version['contenido'], $usuario_id, $noticia_id);

if ($stmtUpd->execute()) {
    logActivity($con, 'editar', 'noticias', 'Restauró la versión #' . $version_id . ' en la noticia ID ' . $noticia_id);
    echo json_encode([
        'success' => true,
        'message' => 'Versión restaurada con éxito',
        'titulo' => $version['titulo'],
        'descripcion' => $version['descripcion'],
        'contenido' => $version['contenido']
    ]);
} else {
    echo json_encode(['error' => 'Error al restaurar en base de datos: ' . $con->error]);
}
