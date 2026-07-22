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
$eliminar_imagen = !empty($data['eliminar_imagen']);

if (empty($tag) || empty($titulo) || empty($descripcion)) {
    echo json_encode(['error' => 'Por favor completa la etiqueta (tag), título y descripción de la vacante.']);
    exit;
}

$imagen_ruta = null;
if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
    $fileTmp = $_FILES['imagen']['tmp_name'];
    $fileName = $_FILES['imagen']['name'];
    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'];
    
    if (in_array($ext, $allowed)) {
        $uploadDir = dirname(__DIR__) . '/uploads/vacantes/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $newFileName = 'vacante_' . time() . '_' . uniqid() . '.' . $ext;
        $destPath = $uploadDir . $newFileName;
        if (move_uploaded_file($fileTmp, $destPath)) {
            $imagen_ruta = 'uploads/vacantes/' . $newFileName;
        }
    }
}

if ($id > 0) {
    // Actualizar vacante existente
    if ($imagen_ruta !== null) {
        $stmt = $con->prepare("UPDATE vacantes_equipo SET tag = ?, titulo = ?, subtitulo_italic = ?, descripcion = ?, modalidad = ?, estado = ?, imagen = ? WHERE id = ?");
        $stmt->bind_param("sssssisi", $tag, $titulo, $subtitulo_italic, $descripcion, $modalidad, $estado, $imagen_ruta, $id);
    } elseif ($eliminar_imagen) {
        $stmt = $con->prepare("UPDATE vacantes_equipo SET tag = ?, titulo = ?, subtitulo_italic = ?, descripcion = ?, modalidad = ?, estado = ?, imagen = NULL WHERE id = ?");
        $stmt->bind_param("sssssii", $tag, $titulo, $subtitulo_italic, $descripcion, $modalidad, $estado, $id);
    } else {
        $stmt = $con->prepare("UPDATE vacantes_equipo SET tag = ?, titulo = ?, subtitulo_italic = ?, descripcion = ?, modalidad = ?, estado = ? WHERE id = ?");
        $stmt->bind_param("sssssii", $tag, $titulo, $subtitulo_italic, $descripcion, $modalidad, $estado, $id);
    }
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Vacante actualizada con éxito', 'imagen' => $imagen_ruta]);
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

    $stmt = $con->prepare("INSERT INTO vacantes_equipo (orden, tag, titulo, subtitulo_italic, descripcion, modalidad, estado, imagen, creado_en) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("isssssis", $nextOrden, $tag, $titulo, $subtitulo_italic, $descripcion, $modalidad, $estado, $imagen_ruta);
    
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

        echo json_encode(['success' => true, 'message' => 'Vacante creada con éxito', 'id' => $newId, 'imagen' => $imagen_ruta]);
    } else {
        echo json_encode(['error' => 'Error en BD: ' . $con->error]);
    }
}
