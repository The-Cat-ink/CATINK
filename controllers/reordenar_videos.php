<?php
session_start();
include(__DIR__ . "/../data/conexion.php");
include(__DIR__ . "/../views/helpers/helper.php");
include(__DIR__ . "/../views/helpers/acl.php");

// Verificar que el usuario está autenticado
if (!isset($_SESSION['usuario'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit();
}

// Verificar permisos
$ACL = cargarACL('videos');
if (!$ACL['editar']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Sin permisos']);
    exit();
}

// Obtener datos JSON
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['videos']) || !is_array($data['videos'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Datos inválidos']);
    exit();
}

try {
    // Actualizar orden de cada video
    foreach ($data['videos'] as $item) {
        $id_v = intval($item['id_v']);
        $orden = intval($item['orden']);
        
        $stmt = $con->prepare("UPDATE videos SET orden = ? WHERE id_v = ?");
        $stmt->bind_param("ii", $orden, $id_v);
        $stmt->execute();
    }

    echo json_encode(['success' => true, 'message' => 'Orden actualizado']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
