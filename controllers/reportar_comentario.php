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
    echo json_encode(['ok' => true, 'msg' => 'Reporte enviado. Será revisado por el equipo.']);
} else {
    echo json_encode(['ok' => false, 'msg' => 'Error al enviar el reporte.']);
}
