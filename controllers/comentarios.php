<?php
session_start();
header('Content-Type: application/json');
require_once(__DIR__ . '/../data/conexion.php');

// Lectores o admins pueden comentar
$tipoUsuario = $_SESSION['tipo'] ?? null;
$lectorId = null;
$usuarioId = null;

if ($tipoUsuario === 'lector' && isset($_SESSION['id_lector'])) {
    $lectorId = (int)$_SESSION['id_lector'];
} elseif ($tipoUsuario === 'admin' && isset($_SESSION['usuario'])) {
    $stmtU = $con->prepare("SELECT id_u FROM usuarios WHERE usuario = ?");
    $stmtU->bind_param("s", $_SESSION['usuario']);
    $stmtU->execute();
    $resU = $stmtU->get_result()->fetch_assoc();
    $usuarioId = $resU ? (int)$resU['id_u'] : null;
}

if (!$lectorId && !$usuarioId) {
    echo json_encode(['ok' => false, 'msg' => 'Debes iniciar sesión para comentar.']);
    exit;
}

$esEditor = ($tipoUsuario === 'admin');
$action = $_POST['action'] ?? '';

// ============================
// FILTRO DE PALABRAS PROHIBIDAS
// ============================
function filtrarPalabras($con, $texto) {
    $stmt = $con->prepare("SELECT palabra_baneada, reemplazo FROM filtro_diccionario");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $pattern = '/\b' . preg_quote($row['palabra_baneada'], '/') . '\b/iu';
        $texto = preg_replace($pattern, $row['reemplazo'], $texto);
    }
    return $texto;
}

switch ($action) {
    // ============================
    // CREAR COMENTARIO
    // ============================
    case 'crear':
        $noticiaId = (int)($_POST['noticia_id'] ?? 0);
        $contenido = trim($_POST['contenido'] ?? '');

        if ($noticiaId <= 0 || empty($contenido)) {
            echo json_encode(['ok' => false, 'msg' => 'Datos incompletos.']);
            exit;
        }
        if (mb_strlen($contenido) > 1000) {
            echo json_encode(['ok' => false, 'msg' => 'El comentario es demasiado largo (máx. 1000 caracteres).']);
            exit;
        }

        // ============================
        // RATE LIMITING: máx 5 comentarios por hora
        // ============================
        $stmtRateLimit = $con->prepare("
            SELECT COUNT(*) as count FROM comentarios 
            WHERE lector_id = ? AND fecha_publicacion > DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ");
        $stmtRateLimit->bind_param("i", $lectorId);
        $stmtRateLimit->execute();
        $rateCheck = $stmtRateLimit->get_result()->fetch_assoc();
        
        if ($rateCheck['count'] >= 5) {
            echo json_encode(['ok' => false, 'msg' => 'Has alcanzado el límite de comentarios. Intenta más tarde.']);
            exit;
        }

        // ============================
        // DETECCIÓN DE DUPLICADOS
        // ============================
        $stmtDuplicate = $con->prepare("
            SELECT COUNT(*) as count FROM comentarios 
            WHERE lector_id = ? AND noticia_id = ? AND contenido = ?
        ");
        $stmtDuplicate->bind_param("iis", $lectorId, $noticiaId, $contenido);
        $stmtDuplicate->execute();
        $dupCheck = $stmtDuplicate->get_result()->fetch_assoc();
        
        if ($dupCheck['count'] > 0) {
            echo json_encode(['ok' => false, 'msg' => 'Ya has publicado este comentario.']);
            exit;
        }

        // ============================
        // VALIDACIÓN DE URLS (máximo 2)
        // ============================
        preg_match_all('/https?:\/\/[^\s]+/', $contenido, $urls);
        if (count($urls[0]) > 2) {
            echo json_encode(['ok' => false, 'msg' => 'Máximo 2 enlaces por comentario.']);
            exit;
        }
        
        // Validar cada URL
        foreach ($urls[0] as $url) {
            // 1. Validar longitud (máximo 2048 caracteres)
            if (strlen($url) > 2048) {
                echo json_encode(['ok' => false, 'msg' => 'URL demasiado larga.']);
                exit;
            }
            
            // 2. Validar formato con filter_var
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                echo json_encode(['ok' => false, 'msg' => 'URL inválida.']);
                exit;
            }
            
            // 3. Validar que no contenga caracteres peligrosos
            if (preg_match('/[<>"\'{};]/', $url)) {
                echo json_encode(['ok' => false, 'msg' => 'URL contiene caracteres no permitidos.']);
                exit;
            }
        }

        // Verificar si los comentarios están habilitados para esta noticia
        $stmtCfg = $con->prepare("SELECT permitir_comentarios, moderacion_previa FROM config_comentarios WHERE noticia_id = ?");
        $stmtCfg->bind_param("i", $noticiaId);
        $stmtCfg->execute();
        $cfg = $stmtCfg->get_result()->fetch_assoc();

        if ($cfg && $cfg['permitir_comentarios'] == 0) {
            echo json_encode(['ok' => false, 'msg' => 'Los comentarios están desactivados para esta noticia.']);
            exit;
        }

        // Filtrar palabras prohibidas
        $contenido = filtrarPalabras($con, $contenido);

        // Estado: admins publican directo, lectores dependen de config
        $estado = $esEditor ? 'activo' : (($cfg && $cfg['moderacion_previa'] == 1) ? 'oculto' : 'activo');

        $stmt = $con->prepare("INSERT INTO comentarios (noticia_id, lector_id, usuario_id, contenido, estado) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iiiss", $noticiaId, $lectorId, $usuarioId, $contenido, $estado);

        if ($stmt->execute()) {
            $msg = ($estado === 'oculto') ? 'Tu comentario fue enviado y será revisado antes de publicarse.' : 'Comentario publicado.';
            $newId = $stmt->insert_id;
            $stmtNew = $con->prepare("
                SELECT c.*,
                       COALESCE(u.nombre, l.nombre) AS nombre,
                       COALESCE(u.usuario, l.usuario) AS usuario,
                       COALESCE(ua.imagen, la.imagen) AS avatar_img,
                       IF(c.usuario_id IS NOT NULL, 1, 0) AS es_editor
                FROM comentarios c
                LEFT JOIN lectores l ON c.lector_id = l.id
                LEFT JOIN avatares_perfil la ON l.avatar_id = la.id_avatar
                LEFT JOIN usuarios u ON c.usuario_id = u.id_u
                LEFT JOIN avatares_perfil ua ON u.avatar_id = ua.id_avatar
                WHERE c.id_comentario = ?
            ");
            $stmtNew->bind_param("i", $newId);
            $stmtNew->execute();
            $comentario = $stmtNew->get_result()->fetch_assoc();

            echo json_encode(['ok' => true, 'msg' => $msg, 'comentario' => $comentario]);
        } else {
            echo json_encode(['ok' => false, 'msg' => 'Error al guardar el comentario.']);
        }
        break;

    // ============================
    // EDITAR COMENTARIO
    // ============================
    case 'editar':
        $comentarioId = (int)($_POST['comentario_id'] ?? 0);
        $contenido = trim($_POST['contenido'] ?? '');

        if ($comentarioId <= 0 || empty($contenido)) {
            echo json_encode(['ok' => false, 'msg' => 'Datos incompletos.']);
            exit;
        }
        if (mb_strlen($contenido) > 1000) {
            echo json_encode(['ok' => false, 'msg' => 'El comentario es demasiado largo (máx. 1000 caracteres).']);
            exit;
        }

        // Verificar propiedad (lector o admin)
        if ($lectorId) {
            $stmtCheck = $con->prepare("SELECT id_comentario, contenido FROM comentarios WHERE id_comentario = ? AND lector_id = ?");
            $stmtCheck->bind_param("ii", $comentarioId, $lectorId);
        } else {
            $stmtCheck = $con->prepare("SELECT id_comentario, contenido FROM comentarios WHERE id_comentario = ? AND usuario_id = ?");
            $stmtCheck->bind_param("ii", $comentarioId, $usuarioId);
        }
        $stmtCheck->execute();
        $original = $stmtCheck->get_result()->fetch_assoc();

        if (!$original) {
            echo json_encode(['ok' => false, 'msg' => 'No puedes editar este comentario.']);
            exit;
        }

        // Guardar historial de edición
        $stmtHist = $con->prepare("INSERT INTO historial_ediciones (comentario_id, contenido_anterior) VALUES (?, ?)");
        $stmtHist->bind_param("is", $comentarioId, $original['contenido']);
        $stmtHist->execute();

        // Filtrar y actualizar
        $contenido = filtrarPalabras($con, $contenido);
        $stmtUpd = $con->prepare("UPDATE comentarios SET contenido = ? WHERE id_comentario = ?");
        $stmtUpd->bind_param("si", $contenido, $comentarioId);

        if ($stmtUpd->execute()) {
            echo json_encode(['ok' => true, 'msg' => 'Comentario editado.', 'contenido' => htmlspecialchars($contenido)]);
        } else {
            echo json_encode(['ok' => false, 'msg' => 'Error al editar.']);
        }
        break;

    // ============================
    // ELIMINAR COMENTARIO
    // ============================
    case 'eliminar':
        $comentarioId = (int)($_POST['comentario_id'] ?? 0);

        if ($comentarioId <= 0) {
            echo json_encode(['ok' => false, 'msg' => 'Datos incompletos.']);
            exit;
        }

        // Verificar propiedad (lector o admin)
        if ($lectorId) {
            $stmtCheck = $con->prepare("SELECT id_comentario FROM comentarios WHERE id_comentario = ? AND lector_id = ?");
            $stmtCheck->bind_param("ii", $comentarioId, $lectorId);
        } else {
            $stmtCheck = $con->prepare("SELECT id_comentario FROM comentarios WHERE id_comentario = ? AND usuario_id = ?");
            $stmtCheck->bind_param("ii", $comentarioId, $usuarioId);
        }
        $stmtCheck->execute();

        if ($stmtCheck->get_result()->num_rows === 0) {
            echo json_encode(['ok' => false, 'msg' => 'No puedes eliminar este comentario.']);
            exit;
        }

        $stmtDel = $con->prepare("UPDATE comentarios SET estado = 'eliminado' WHERE id_comentario = ?");
        $stmtDel->bind_param("i", $comentarioId);

        if ($stmtDel->execute()) {
            echo json_encode(['ok' => true, 'msg' => 'Comentario eliminado.']);
        } else {
            echo json_encode(['ok' => false, 'msg' => 'Error al eliminar.']);
        }
        break;

    default:
        echo json_encode(['ok' => false, 'msg' => 'Acción no válida.']);
}
