<?php
ob_start();
header('Content-Type: application/json');
session_start();
include("./aclcontroller.php");
proteger('noticias','editar');

$noticia_id = isset($_POST['noticia_id']) ? intval($_POST['noticia_id']) : 0;

if ($noticia_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'ID de noticia no válido']);
    exit;
}

include("./../data/conexion.php");

try {
    // 1. Verificar si la noticia existe y está publicada
    $stmtCheck = $con->prepare("SELECT id FROM noticias WHERE id = ? AND eliminado_en IS NULL AND fecha_publicacion <= NOW()");
    $stmtCheck->bind_param("i", $noticia_id);
    $stmtCheck->execute();
    if ($stmtCheck->get_result()->num_rows === 0) {
        echo json_encode(['success' => false, 'error' => 'La noticia no existe o aún no está publicada']);
        exit;
    }

    // 2. Verificar si ya está en esperamos
    $stmtCheckRec = $con->prepare("SELECT id FROM esperamos WHERE noticia_id = ?");
    $stmtCheckRec->bind_param("i", $noticia_id);
    $stmtCheckRec->execute();
    if ($stmtCheckRec->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'error' => 'La noticia ya está en la lista de esperados']);
        exit;
    }

    // 3. Verificar límite de 10
    $countRes = $con->query("SELECT COUNT(*) AS total FROM esperamos");
    $countRow = $countRes->fetch_assoc();
    if (intval($countRow['total']) >= 10) {
        echo json_encode(['success' => false, 'error' => 'Se ha alcanzado el límite máximo de 10 esperados']);
        exit;
    }

    // 4. Obtener orden máximo actual
    $ordenRes = $con->query("SELECT COALESCE(MAX(orden), 0) AS max_orden FROM esperamos");
    $ordenRow = $ordenRes->fetch_assoc();
    $nuevoOrden = intval($ordenRow['max_orden']) + 1;

    // 5. Insertar
    $stmtInsert = $con->prepare("INSERT INTO esperamos (noticia_id, orden) VALUES (?, ?)");
    $stmtInsert->bind_param("ii", $noticia_id, $nuevoOrden);
    if ($stmtInsert->execute()) {
        echo json_encode(['success' => true, 'id' => $con->insert_id]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Error al agregar el artículo esperado']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
exit;
?>
