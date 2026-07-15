<?php
require_once(__DIR__ . '/aclcontroller.php');
proteger('usuarios', 'eliminar', true);
require_once(__DIR__ . '/../data/conexion.php');

header('Content-Type: application/json');

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    echo json_encode(['error' => 'ID de lector no válido']);
    exit;
}

// Obtener datos del correo para borrar reset tokens
$stmt = $con->prepare("SELECT correo FROM lectores WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$lector = $stmt->get_result()->fetch_assoc();

if (!$lector) {
    echo json_encode(['error' => 'Lector no encontrado']);
    exit;
}

$con->begin_transaction();
try {
    // 1. Desvincular comentarios (establecer lector_id en NULL)
    $updComm = $con->prepare("UPDATE comentarios SET lector_id = NULL WHERE lector_id = ?");
    $updComm->bind_param("i", $id);
    $updComm->execute();

    // 2. Eliminar likes y reportes
    $delLikes = $con->prepare("DELETE FROM likes_comentarios WHERE lector_id = ?");
    $delLikes->bind_param("i", $id);
    $delLikes->execute();

    $delRep = $con->prepare("DELETE FROM reportes_comentarios WHERE lector_id = ?");
    $delRep->bind_param("i", $id);
    $delRep->execute();

    // 3. Eliminar notificaciones
    $delNotif = $con->prepare("DELETE FROM notificaciones WHERE tipo_usuario = 'lector' AND user_id = ?");
    $delNotif->bind_param("i", $id);
    $delNotif->execute();

    // 4. Eliminar reset tokens
    $delTokens = $con->prepare("DELETE FROM password_reset_tokens WHERE tipo_usuario = 'lector' AND email = ?");
    $delTokens->bind_param("s", $lector['correo']);
    $delTokens->execute();

    // 5. Eliminar lector
    $delLec = $con->prepare("DELETE FROM lectores WHERE id = ?");
    $delLec->bind_param("i", $id);
    $delLec->execute();

    $con->commit();
    require_once(__DIR__ . '/../views/helpers/activity_log.php');
    logActivity($con, 'eliminar', 'lectores', 'Eliminó al lector ID ' . $id . ' («' . $lector['correo'] . '»)');
    echo json_encode(['success' => 'Lector eliminado correctamente']);
} catch (Exception $e) {
    $con->rollback();
    echo json_encode(['error' => 'Error al eliminar lector: ' . $e->getMessage()]);
}
