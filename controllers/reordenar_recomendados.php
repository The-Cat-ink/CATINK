<?php
ob_start();
header('Content-Type: application/json');
session_start();
include("./aclcontroller.php");
proteger('noticias','editar');

include("./../data/conexion.php");

// Obtener datos JSON
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['recomendados']) || !is_array($data['recomendados'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Datos inválidos']);
    exit();
}

try {
    // Actualizar orden de cada recomendado
    foreach ($data['recomendados'] as $item) {
        $id = intval($item['id']);
        $orden = intval($item['orden']);
        
        $stmt = $con->prepare("UPDATE recomendados SET orden = ? WHERE id = ?");
        $stmt->bind_param("ii", $orden, $id);
        $stmt->execute();
    }

    echo json_encode(['success' => true, 'message' => 'Orden actualizado']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
exit;
?>
