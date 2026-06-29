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

if ($comentarioId <= 0) {
    echo json_encode(['ok' => false, 'msg' => 'Datos incompletos.']);
    exit;
}

// Verificar si ya dio like
$stmt = $con->prepare("SELECT id_like FROM likes_comentarios WHERE comentario_id = ? AND lector_id = ?");
$stmt->bind_param("ii", $comentarioId, $lectorId);
$stmt->execute();
$existe = $stmt->get_result()->fetch_assoc();

if ($existe) {
    // Quitar like
    $stmtDel = $con->prepare("DELETE FROM likes_comentarios WHERE id_like = ?");
    $stmtDel->bind_param("i", $existe['id_like']);
    $stmtDel->execute();
    $liked = false;
} else {
    // Dar like
    $stmtIns = $con->prepare("INSERT INTO likes_comentarios (comentario_id, lector_id) VALUES (?, ?)");
    $stmtIns->bind_param("ii", $comentarioId, $lectorId);
    $stmtIns->execute();
    $liked = true;
}

// Contar likes totales
$stmtCount = $con->prepare("SELECT COUNT(*) AS total FROM likes_comentarios WHERE comentario_id = ?");
$stmtCount->bind_param("i", $comentarioId);
$stmtCount->execute();
$total = $stmtCount->get_result()->fetch_assoc()['total'];

echo json_encode(['ok' => true, 'liked' => $liked, 'total' => (int)$total]);
