<?php
ob_start();
header('Content-Type: application/json');
session_start();
include("./aclcontroller.php");
proteger('noticias','editar');

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;

if ($id <= 0) {
    echo json_encode(['success' => false, 'error' => 'ID no válido']);
    exit;
}

include("./../data/conexion.php");

try {
    $stmt = $con->prepare("DELETE FROM recomendados WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        require_once(__DIR__ . "/../views/helpers/activity_log.php");
        logActivity($con, 'eliminar', 'recomendados', 'Eliminó recomendación ID ' . $id);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Error al eliminar la recomendación']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
exit;
?>
