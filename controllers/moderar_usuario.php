<?php
session_start();
header('Content-Type: application/json');
require_once(__DIR__ . '/../data/conexion.php');
require_once(__DIR__ . '/../views/helpers/helper.php');
require_once(__DIR__ . '/../views/helpers/urlhelper.php');
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

// ============================
// LISTAR USUARIOS ACTIVOS (búsqueda server-side + límite)
// Se resuelve antes de validar tipo/user_id porque no los necesita.
// Nunca carga toda la tabla: filtra por texto y limita por tipo, así el
// panel sigue siendo rápido aunque haya miles de lectores.
// ============================
if ($action === 'listar_activos') {
    $q      = trim($_POST['q'] ?? '');
    $like   = '%' . $q . '%';
    $limite = 25; // por tipo
    // Condición de "activo" (no baneado); se califica con el alias de cada consulta
    $activo = fn($t) => "($t.baneado_permanente = 0 AND ($t.baneado_hasta IS NULL OR $t.baneado_hasta <= NOW()))";
    $out    = [];

    // --- Lectores activos (con su avatar del catálogo) ---
    $sqlL = "SELECT l.id, l.nombre, l.usuario, a.imagen AS avatar_img
             FROM lectores l
             LEFT JOIN avatares_perfil a ON a.id_avatar = l.avatar_id
             WHERE {$activo('l')}";
    if ($q !== '') $sqlL .= " AND (l.nombre LIKE ? OR l.usuario LIKE ? OR l.correo LIKE ?)";
    $sqlL .= " ORDER BY l.id DESC LIMIT $limite";
    $stmt = @$con->prepare($sqlL);
    if ($stmt) {
        if ($q !== '') $stmt->bind_param("sss", $like, $like, $like);
        if ($stmt->execute()) {
            $res = $stmt->get_result();
            while ($r = $res->fetch_assoc()) {
                $out[] = [
                    'tipo'    => 'lector',
                    'id'      => (int)$r['id'],
                    'nombre'  => $r['nombre'],
                    'usuario' => $r['usuario'],
                    'avatar'  => !empty($r['avatar_img']) ? imageUrl($r['avatar_img']) : null,
                ];
            }
        }
    }

    // --- Admins/editores activos (excluye superadmins y a uno mismo) ---
    $sqlA = "SELECT u.*, COALESCE(NULLIF(u.foto_personal, ''), a.imagen) AS avatar_img
             FROM usuarios u
             LEFT JOIN avatares_perfil a ON a.id_avatar = u.avatar_id
             WHERE {$activo('u')}";
    if ($q !== '') $sqlA .= " AND (u.nombre LIKE ? OR u.usuario LIKE ? OR u.correo LIKE ?)";
    $sqlA .= " ORDER BY u.id_u DESC LIMIT $limite";
    $stmt = @$con->prepare($sqlA);
    if ($stmt) {
        if ($q !== '') $stmt->bind_param("sss", $like, $like, $like);
        if ($stmt->execute()) {
            $res = $stmt->get_result();
            while ($r = $res->fetch_assoc()) {
                if (esSuperAdmin($r)) continue;
                if (isset($_SESSION['usuario']) && $r['usuario'] === $_SESSION['usuario']) continue;
                $out[] = [
                    'tipo'    => 'admin',
                    'id'      => (int)$r['id_u'],
                    'nombre'  => $r['nombre'],
                    'usuario' => $r['usuario'],
                    'avatar'  => !empty($r['avatar_img']) ? imageUrl($r['avatar_img']) : null,
                ];
            }
        }
    }

    echo json_encode(['ok' => true, 'usuarios' => $out]);
    exit;
}

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
            // Al retirar la suspensión de un lector, restaurar sus oportunidades:
            // reiniciar el contador de intentos y limpiar las notificaciones de
            // intento ofensivo (que alimentan el conteo acumulado de advertencias),
            // para que arranque de nuevo con la cuenta de oportunidades completa.
            if ($tipo === 'lector') {
                if ($stmtReset = @$con->prepare("UPDATE lectores SET intentos_profanos = 0 WHERE id = ?")) {
                    $stmtReset->bind_param("i", $userId);
                    $stmtReset->execute();
                }
                if ($stmtDelNotif = @$con->prepare("DELETE FROM notificaciones WHERE tipo_usuario = 'lector' AND user_id = ? AND tipo = 'comentario_ofensivo'")) {
                    $stmtDelNotif->bind_param("i", $userId);
                    $stmtDelNotif->execute();
                }
            }
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
