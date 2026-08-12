<?php
session_start();
include(__DIR__ . "/../data/conexion.php");
include(__DIR__ . "/../views/helpers/helper.php");
include(__DIR__ . "/../views/helpers/acl.php");
require_once(__DIR__ . "/../views/helpers/cachehelper.php");

// Verificar que el usuario está autenticado
if (!isset($_SESSION['usuario'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit();
}

// Verificar permisos (SuperAdmin o permiso de editar categorías)
$esSuperAdmin = !empty($_SESSION['superadmin']);
$ACL = cargarACL('categorias');
if (!$esSuperAdmin && empty($ACL['editar'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Sin permisos']);
    exit();
}

// Obtener datos JSON
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['categorias']) || !is_array($data['categorias'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Datos inválidos']);
    exit();
}

try {
    // Actualizar orden de cada categoría
    foreach ($data['categorias'] as $item) {
        $id_c = intval($item['id_c']);
        $orden = intval($item['orden']);
        
        $stmt = $con->prepare("UPDATE categorias SET orden = ? WHERE id_c = ?");
        $stmt->bind_param("ii", $orden, $id_c);
        $stmt->execute();
    }

    require_once(__DIR__ . "/../views/helpers/activity_log.php");
    // Limpiar la caché del header
    delete_cache('header_categorias');

    logActivity($con, 'editar', 'categorias', 'Reordenó las categorías');

    echo json_encode(['success' => true, 'message' => 'Orden actualizado']);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
