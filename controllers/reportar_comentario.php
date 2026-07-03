<?php
session_start();
header('Content-Type: application/json');
require_once(__DIR__ . '/../data/conexion.php');
require_once(__DIR__ . '/../views/helpers/moderacion.php');

if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'lector' || !isset($_SESSION['id_lector'])) {
    echo json_encode(['ok' => false, 'msg' => 'Debes iniciar sesión.']);
    exit;
}

$banTxt = baneoLectorActual($con);
if ($banTxt) {
    echo json_encode(['ok' => false, 'msg' => 'Tu cuenta está suspendida. ' . $banTxt]);
    exit;
}

$lectorId = (int)$_SESSION['id_lector'];
$comentarioId = (int)($_POST['comentario_id'] ?? 0);
$motivo = trim($_POST['motivo'] ?? '');

if ($comentarioId <= 0 || empty($motivo)) {
    echo json_encode(['ok' => false, 'msg' => 'Datos incompletos.']);
    exit;
}

// Verificar que no haya reportado ya este comentario
$stmtCheck = $con->prepare("SELECT id_reporte FROM reportes_comentarios WHERE comentario_id = ? AND lector_id = ?");
$stmtCheck->bind_param("ii", $comentarioId, $lectorId);
$stmtCheck->execute();
if ($stmtCheck->get_result()->num_rows > 0) {
    echo json_encode(['ok' => false, 'msg' => 'Ya reportaste este comentario.']);
    exit;
}

$stmt = $con->prepare("INSERT INTO reportes_comentarios (comentario_id, lector_id, motivo) VALUES (?, ?, ?)");
$stmt->bind_param("iis", $comentarioId, $lectorId, $motivo);

if ($stmt->execute()) {
    // Buscar al autor del comentario reportado para ver si acumuló más de 5 reportes
    $stmtAuthor = $con->prepare("SELECT lector_id FROM comentarios WHERE id_comentario = ?");
    $stmtAuthor->bind_param("i", $comentarioId);
    $stmtAuthor->execute();
    $resAuthor = $stmtAuthor->get_result()->fetch_assoc();
    $autorLectorId = $resAuthor ? $resAuthor['lector_id'] : null;

    if ($autorLectorId) {
        $stmtCount = $con->prepare("
            SELECT COUNT(r.id_reporte) AS total
            FROM reportes_comentarios r
            INNER JOIN comentarios c ON r.comentario_id = c.id_comentario
            WHERE c.lector_id = ?
        ");
        $stmtCount->bind_param("i", $autorLectorId);
        $stmtCount->execute();
        $resCount = $stmtCount->get_result()->fetch_assoc();
        $totalReportes = $resCount ? (int)$resCount['total'] : 0;

        if ($totalReportes > 5) {
            // Suspender cuenta por 24 horas
            $baneadoHasta = date('Y-m-d H:i:s', strtotime('+24 hours'));
            $motivoBan = "Baneo automático: cuenta acumuló más de 5 reportes en sus comentarios";
            
            $stmtBan = $con->prepare("UPDATE lectores SET baneado_hasta = ?, baneado_permanente = 0, baneado_motivo = ? WHERE id = ?");
            $stmtBan->bind_param("ssi", $baneadoHasta, $motivoBan, $autorLectorId);
            $stmtBan->execute();

            // Registrar notificación de suspensión
            crearNotificacion($con, 'lector', $autorLectorId, 'Cuenta Suspendida', 'Tu cuenta ha sido suspendida automáticamente por 24 horas debido a que tus comentarios han acumulado más de 5 reportes por parte de la comunidad.', 'moderacion');

            // Ocultar todos sus comentarios activos de forma preventiva
            $stmtHide = $con->prepare("UPDATE comentarios SET estado = 'oculto' WHERE lector_id = ? AND estado = 'activo'");
            $stmtHide->bind_param("i", $autorLectorId);
            $stmtHide->execute();
        }
    }

    echo json_encode(['ok' => true, 'msg' => 'Reporte enviado. Será revisado por el equipo.']);
} else {
    echo json_encode(['ok' => false, 'msg' => 'Error al enviar el reporte.']);
}
