<?php
session_start();
header('Content-Type: application/json');
require_once(__DIR__ . '/../data/conexion.php');
require_once(__DIR__ . '/../views/helpers/helper.php');
require_once(__DIR__ . '/../views/helpers/moderacion.php');

// ============================
// Solo superadmin
// ============================
if (!esSuperAdminActual()) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'msg' => 'No tienes permiso para esta acción.']);
    exit;
}

$action = $_POST['action'] ?? '';
$tipo   = $_POST['tipo'] ?? '';            // 'lector' | 'admin'
$userId = (int)($_POST['user_id'] ?? 0);

if ($userId <= 0 || !in_array($tipo, ['lector', 'admin'], true)) {
    echo json_encode(['ok' => false, 'msg' => 'Datos incompletos.']);
    exit;
}

// Tabla y PK según el tipo
$tabla = $tipo === 'lector' ? 'lectores' : 'usuarios';
$pk    = $tipo === 'lector' ? 'id' : 'id_u';

// ============================
// Verificar que el objetivo existe y no es superadmin
// ============================
if ($tipo === 'admin') {
    $stmt = $con->prepare("SELECT * FROM usuarios WHERE id_u = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $target = $stmt->get_result()->fetch_assoc();
    if (!$target) {
        echo json_encode(['ok' => false, 'msg' => 'Usuario no encontrado.']);
        exit;
    }
    if (esSuperAdmin($target)) {
        echo json_encode(['ok' => false, 'msg' => 'No se puede banear a un superadministrador.']);
        exit;
    }
    // Evitar auto-baneo
    if (isset($_SESSION['usuario']) && $target['usuario'] === $_SESSION['usuario']) {
        echo json_encode(['ok' => false, 'msg' => 'No puedes banearte a ti mismo.']);
        exit;
    }
} else {
    $stmt = $con->prepare("SELECT id FROM lectores WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        echo json_encode(['ok' => false, 'msg' => 'Usuario no encontrado.']);
        exit;
    }
}

switch ($action) {
    // ============================
    // BANEAR
    // ============================
    case 'ban':
        $duracion = $_POST['duracion'] ?? '';
        $motivo   = trim($_POST['motivo'] ?? '');
        if (mb_strlen($motivo) > 255) {
            $motivo = mb_substr($motivo, 0, 255);
        }
        $motivo = $motivo === '' ? null : $motivo;

        $duraciones = duracionesBaneo();
        if (!isset($duraciones[$duracion])) {
            echo json_encode(['ok' => false, 'msg' => 'Duración inválida.']);
            exit;
        }

        if ($duracion === 'perm') {
            $sql = "UPDATE $tabla SET baneado_permanente = 1, baneado_hasta = NULL, baneado_motivo = ? WHERE $pk = ?";
            $stmt = $con->prepare($sql);
            $stmt->bind_param("si", $motivo, $userId);
            $duracionTxt = 'de forma permanente';
        } else {
            $hasta = (new DateTime())->modify($duraciones[$duracion]['modify'])->format('Y-m-d H:i:s');
            $sql = "UPDATE $tabla SET baneado_permanente = 0, baneado_hasta = ?, baneado_motivo = ? WHERE $pk = ?";
            $stmt = $con->prepare($sql);
            $stmt->bind_param("ssi", $hasta, $motivo, $userId);
            $duracionTxt = 'por ' . $duraciones[$duracion]['label'] . ' (hasta el ' . date('d M Y, H:i', strtotime($hasta)) . ')';
        }

        if ($stmt->execute()) {
            $estado = obtenerBaneo($con, $tipo, $userId);
            // Notificar al usuario suspendido en su perfil
            $notifMsg = "Tu cuenta ha sido suspendida $duracionTxt. "
                      . 'Motivo: ' . ($motivo !== null ? $motivo : 'No se especificó un motivo.')
                      . ' Durante la suspensión no podrás comentar, dar me gusta ni reportar.';
            crearNotificacion($con, $tipo, $userId, 'Tu cuenta ha sido suspendida', $notifMsg, 'suspension');
            echo json_encode([
                'ok'       => true,
                'msg'      => 'Usuario suspendido (' . $duraciones[$duracion]['label'] . ').',
                'baneado'  => true,
                'estado'   => textoBaneo($estado),
            ]);
        } else {
            echo json_encode(['ok' => false, 'msg' => 'Error al aplicar la suspensión.']);
        }
        break;

    // ============================
    // DESBANEAR
    // ============================
    case 'unban':
        $sql = "UPDATE $tabla SET baneado_permanente = 0, baneado_hasta = NULL, baneado_motivo = NULL WHERE $pk = ?";
        $stmt = $con->prepare($sql);
        $stmt->bind_param("i", $userId);
        if ($stmt->execute()) {
            // Notificar al usuario la reactivación de su cuenta
            crearNotificacion($con, $tipo, $userId, 'Tu suspensión ha sido retirada',
                'Un moderador retiró la suspensión de tu cuenta. Ya puedes volver a comentar, dar me gusta y reportar con normalidad.',
                'reactivacion');
            echo json_encode(['ok' => true, 'msg' => 'Suspensión retirada.', 'baneado' => false]);
        } else {
            echo json_encode(['ok' => false, 'msg' => 'Error al retirar la suspensión.']);
        }
        break;

    default:
        echo json_encode(['ok' => false, 'msg' => 'Acción no válida.']);
}
