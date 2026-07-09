<?php
if(session_status() === PHP_SESSION_NONE){
    session_start();
}
require_once(__DIR__ . "/../data/conexion.php");
require_once(__DIR__ . "/../views/helpers/moderacion.php");

header('Content-Type: application/json');

// 1. Verificar sesión de lector
if (($_SESSION['tipo'] ?? null) !== 'lector' || !isset($_SESSION['id_lector'])) {
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$lectorId = (int)$_SESSION['id_lector'];

// 2. Obtener datos actualizados del lector
$stmt = $con->prepare("SELECT * FROM lectores WHERE id = ?");
$stmt->bind_param("i", $lectorId);
$stmt->execute();
$lector = $stmt->get_result()->fetch_assoc();

if (!$lector) {
    echo json_encode(['error' => 'Usuario no encontrado']);
    exit;
}

// 3. Verificar si realmente está suspendido
if (!estaBaneado($lector)) {
    echo json_encode(['error' => 'Tu cuenta no está suspendida']);
    exit;
}

// 4. Verificar que no haya apelado anteriormente
if ((int)($lector['apelado'] ?? 0) !== 0) {
    echo json_encode(['error' => 'Ya has utilizado tu oportunidad de apelación única']);
    exit;
}

// 5. Proceder a desbanear e incrementar apelado
$stmtUpd = $con->prepare("
    UPDATE lectores 
    SET baneado_hasta = NULL, 
        baneado_permanente = 0, 
        baneado_motivo = NULL, 
        apelado = 1 
    WHERE id = ?
");
$stmtUpd->bind_param("i", $lectorId);

if ($stmtUpd->execute()) {
    echo json_encode(['success' => 'Apelación aceptada. Tu suspensión ha sido removida por única ocasión.']);
} else {
    echo json_encode(['error' => 'Error al procesar la apelación']);
}
