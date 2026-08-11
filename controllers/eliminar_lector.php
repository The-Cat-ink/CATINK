<?php
require_once(__DIR__ . '/aclcontroller.php');
proteger('lectores', 'eliminar', true);
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
    // 1. Marcar comentarios del lector como eliminados
    try {
        $updComm = $con->prepare("UPDATE comentarios SET estado = 'eliminado' WHERE lector_id = ?");
        $updComm->bind_param("i", $id);
        $updComm->execute();
    } catch (\Throwable $e) {
        error_log("No se pudieron eliminar comentarios del lector: " . $e->getMessage());
    }

    // 2. Eliminar likes y reportes
    try {
        $delLikes = $con->prepare("DELETE FROM likes_comentarios WHERE lector_id = ?");
        $delLikes->bind_param("i", $id);
        $delLikes->execute();
    } catch (\Throwable $e) {}

    try {
        $delRep = $con->prepare("DELETE FROM reportes_comentarios WHERE lector_id = ?");
        $delRep->bind_param("i", $id);
        $delRep->execute();
    } catch (\Throwable $e) {}

    // 3. Eliminar notificaciones
    try {
        $delNotif = $con->prepare("DELETE FROM notificaciones WHERE tipo_usuario = 'lector' AND user_id = ?");
        $delNotif->bind_param("i", $id);
        $delNotif->execute();
    } catch (\Throwable $e) {}

    // 4. Eliminar reset tokens
    try {
        $delTokens = $con->prepare("DELETE FROM password_reset_tokens WHERE tipo_usuario = 'lector' AND email = ?");
        $delTokens->bind_param("s", $lector['correo']);
        $delTokens->execute();
    } catch (\Throwable $e) {}

    // 5. Eliminar lector
    $delLec = $con->prepare("DELETE FROM lectores WHERE id = ?");
    $delLec->bind_param("i", $id);
    $delLec->execute();

    $con->commit();
    require_once(__DIR__ . '/../views/helpers/activity_log.php');
    logActivity($con, 'eliminar', 'lectores', 'Eliminó al lector ID ' . $id . ' («' . $lector['correo'] . '»)');
    echo json_encode(['success' => 'Lector eliminado correctamente']);
} catch (\Throwable $e) {
    @$con->rollback();
    echo json_encode(['error' => 'Error al eliminar lector: ' . $e->getMessage()]);
}
