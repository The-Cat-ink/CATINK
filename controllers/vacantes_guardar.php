<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once(__DIR__ . "/aclcontroller.php");
proteger('noticias', 'editar');
require_once(__DIR__ . "/../data/conexion.php");

$data = json_decode(file_get_contents('php://input'), true) ?: $_POST;

$id = intval($data['id'] ?? 0);
$tag = trim($data['tag'] ?? '');
$titulo = trim($data['titulo'] ?? '');
$subtitulo_italic = trim($data['subtitulo_italic'] ?? '');
$descripcion = trim($data['descripcion'] ?? '');
$modalidad = trim($data['modalidad'] ?? '100% Remoto · Tiempo completo');
$estado = isset($data['estado']) ? intval($data['estado']) : 1;

if (empty($tag) || empty($titulo) || empty($descripcion)) {
    echo json_encode(['error' => 'Por favor completa la etiqueta (tag), título y descripción de la vacante.']);
    exit;
}

if ($id > 0) {
    // Actualizar vacante existente (mantener su orden actual)
    $stmt = $con->prepare("UPDATE vacantes_equipo SET tag = ?, titulo = ?, subtitulo_italic = ?, descripcion = ?, modalidad = ?, estado = ? WHERE id = ?");
    $stmt->bind_param("sssssii", $tag, $titulo, $subtitulo_italic, $descripcion, $modalidad, $estado, $id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Vacante actualizada con éxito']);
    } else {
        echo json_encode(['error' => 'Error en BD: ' . $con->error]);
    }
} else {
    // Crear nueva vacante: asignar el orden consecutivo más alto (MAX + 1)
    $resMax = $con->query("SELECT COALESCE(MAX(orden), 0) + 1 AS next_orden FROM vacantes_equipo");
    $nextOrden = 1;
    if ($resMax && $rowMax = $resMax->fetch_assoc()) {
        $nextOrden = intval($rowMax['next_orden']);
    }

    $stmt = $con->prepare("INSERT INTO vacantes_equipo (orden, tag, titulo, subtitulo_italic, descripcion, modalidad, estado, creado_en) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("isssssi", $nextOrden, $tag, $titulo, $subtitulo_italic, $descripcion, $modalidad, $estado);
    
    if ($stmt->execute()) {
        $newId = $con->insert_id;

        // Normalizar secuencia de orden (1, 2, 3...)
        $resAll = $con->query("SELECT id FROM vacantes_equipo ORDER BY orden ASC, id ASC");
        if ($resAll) {
            $seq = 1;
            $updStmt = $con->prepare("UPDATE vacantes_equipo SET orden = ? WHERE id = ?");
            while ($r = $resAll->fetch_assoc()) {
                $updStmt->bind_param("ii", $seq, $r['id']);
                $updStmt->execute();
                $seq++;
            }
        }

        echo json_encode(['success' => true, 'message' => 'Vacante creada con éxito', 'id' => $newId]);
    } else {
        echo json_encode(['error' => 'Error en BD: ' . $con->error]);
    }
}
