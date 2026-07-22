<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario']) || !isset($_SESSION['tipo'])) {
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

require_once(__DIR__ . "/../data/conexion.php");

$noticia_id = intval($_GET['id'] ?? 0);

if ($noticia_id <= 0) {
    echo json_encode(['error' => 'ID de noticia inválido']);
    exit;
}

// Obtener estado actual de la noticia para comparar
$stmtCurrent = $con->prepare("SELECT titulo, descripcion, contenido FROM noticias WHERE id = ?");
$stmtCurrent->bind_param("i", $noticia_id);
$stmtCurrent->execute();
$actual = $stmtCurrent->get_result()->fetch_assoc() ?: [
    'titulo' => '(Publicación actual)',
    'descripcion' => '(Publicación actual)',
    'contenido' => '(Publicación actual)'
];

$stmt = $con->prepare("
    SELECT h.*, u.nombre AS usuario_nombre, u.usuario AS usuario_username
    FROM historial_ediciones_noticias h
    LEFT JOIN usuarios u ON h.usuario_id = u.id_u
    WHERE h.noticia_id = ?
    ORDER BY h.fecha_edicion DESC
");
$stmt->bind_param("i", $noticia_id);
$stmt->execute();
$result = $stmt->get_result();

$historial = [];
while ($row = $result->fetch_assoc()) {
    $historial[] = [
        'id' => (int)$row['id'],
        'noticia_id' => (int)$row['noticia_id'],
        'titulo' => $row['titulo'],
        'descripcion' => $row['descripcion'],
        'contenido' => $row['contenido'],
        'motivo_cambio' => $row['motivo_cambio'] ?? 'Edición',
        'usuario_nombre' => $row['usuario_nombre'] ?? $row['usuario_username'] ?? 'Sistema',
        'fecha_edicion' => date('d/m/Y H:i:s', strtotime($row['fecha_edicion']))
    ];
}

echo json_encode([
    'success' => true,
    'actual' => $actual,
    'historial' => $historial
]);
