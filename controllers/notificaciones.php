<?php
session_start();
header('Content-Type: application/json');
require_once(__DIR__ . '/../data/conexion.php');

// Debe haber una sesión activa
if (!isset($_SESSION['usuario']) || !isset($_SESSION['tipo'])) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'msg' => 'No autorizado.']);
    exit;
}

$action = $_POST['action'] ?? '';
$tipo   = $_SESSION['tipo'] === 'admin' ? 'admin' : 'lector';

// Resolver el id del usuario de la sesión
if ($tipo === 'lector') {
    $userId = (int)($_SESSION['id_lector'] ?? 0);
} else {
    $stmt = $con->prepare("SELECT id_u FROM usuarios WHERE usuario = ?");
    $stmt->bind_param("s", $_SESSION['usuario']);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $userId = (int)($row['id_u'] ?? 0);
}

if ($userId <= 0) {
    echo json_encode(['ok' => false, 'msg' => 'Usuario no válido.']);
    exit;
}

switch ($action) {
    // Marcar todas las notificaciones del usuario como leídas
    case 'marcar_leidas':
        $stmt = @$con->prepare("UPDATE notificaciones SET leida = 1 WHERE tipo_usuario = ? AND user_id = ? AND leida = 0");
        if (!$stmt) {
            echo json_encode(['ok' => false, 'msg' => 'No disponible.']);
            exit;
        }
        $stmt->bind_param("si", $tipo, $userId);
        if ($stmt->execute()) {
            echo json_encode(['ok' => true, 'msg' => 'Notificaciones marcadas como leídas.']);
        } else {
            echo json_encode(['ok' => false, 'msg' => 'Error al actualizar.']);
        }
        break;

    default:
        echo json_encode(['ok' => false, 'msg' => 'Acción no válida.']);
}
