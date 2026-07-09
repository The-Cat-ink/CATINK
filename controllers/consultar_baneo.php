<?php
if(session_status() === PHP_SESSION_NONE){
    session_start();
}
require_once(__DIR__ . "/../data/conexion.php");
require_once(__DIR__ . "/../views/helpers/moderacion.php");

header('Content-Type: application/json');

if (($_SESSION['tipo'] ?? null) !== 'lector' || !isset($_SESSION['id_lector'])) {
    echo json_encode(['banned' => false]);
    exit;
}

$lectorId = (int)$_SESSION['id_lector'];
$stmt = $con->prepare("SELECT * FROM lectores WHERE id = ?");
$stmt->bind_param("i", $lectorId);
$stmt->execute();
$lector = $stmt->get_result()->fetch_assoc();

if ($lector && estaBaneado($lector)) {
    echo json_encode([
        'banned' => true,
        'ban_text' => textoBaneo($lector),
        'motivo' => $lector['baneado_motivo'] ?? '',
        'apelado' => (int)($lector['apelado'] ?? 0)
    ]);
} else {
    echo json_encode(['banned' => false]);
}
