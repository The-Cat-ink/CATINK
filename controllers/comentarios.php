<?php
session_start();
header('Content-Type: application/json');
require_once(__DIR__ . '/../data/conexion.php');
require_once(__DIR__ . '/../views/helpers/filtrohelper.php');
require_once(__DIR__ . '/../views/helpers/moderacion.php');
require_once(__DIR__ . '/../views/helpers/moderacionhelper.php');

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
// BLOQUEAR USUARIOS SUSPENDIDOS (no aplica a 'eliminar')
// ============================
if (in_array($action, ['crear', 'editar'], true)) {
    // 1. Bloqueo global de comentarios (botón de pánico)
    $resGlobalCfg = $con->query("SELECT estado FROM secciones WHERE nombre = 'comentarios' LIMIT 1");
    $globalCfg = $resGlobalCfg->fetch_assoc();
    if ($globalCfg && $globalCfg['estado'] == 0) {
        echo json_encode(['ok' => false, 'msg' => 'Los comentarios están desactivados temporalmente en todo el sitio.']);
        exit;
    }

    $banTipo = $tipoUsuario === 'lector' ? 'lector' : 'admin';
    $banId   = $tipoUsuario === 'lector' ? $lectorId : $usuarioId;
    $banRow  = obtenerBaneo($con, $banTipo, $banId);
    if ($banRow && estaBaneado($banRow)) {
        echo json_encode([
            'ok' => false,
            'persist' => true,
            'msg' => 'Tu cuenta está suspendida temporalmente. ' . (textoBaneo($banRow) ?? '') . ' Podrás volver a participar cuando termine la suspensión. ¡Te esperamos de vuelta con buena onda!'
        ]);
        exit;
    }
}

// ============================
// FILTRO DE PALABRAS PROHIBIDAS
// ============================
// Función movida a views/helpers/filtrohelper.php

// ============================
// LISTA NEGRA DE DOMINIOS ADULTOS
// ============================
function esURLAdulta($url) {
    $dominiosAdultos = [
        'pornhub.com', 'xvideos.com', 'xnxx.com', 'redtube.com',
        'youporn.com', 'tube8.com', 'spankbang.com', 'xhamster.com',
        'onlyfans.com', 'chaturbate.com', 'cam4.com', 'stripchat.com',
        'livejasmine.com', 'flirt4free.com', 'myfreecams.com',
        'sex.com', 'xxx.com', 'adult.com', 'porn.com',
        'sexo.com', 'porno.com', 'xxx.es', 'sexo.es'
    ];
    
    $urlParsed = parse_url($url);
    if (!isset($urlParsed['host'])) {
        return false;
    }
    
    $host = strtolower($urlParsed['host']);
    $host = preg_replace('/^www\./', '', $host);
    
    foreach ($dominiosAdultos as $dominio) {
        if (strpos($host, $dominio) !== false) {
            return true;
        }
    }
    
    return false;
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
        // COMENTARIO PADRE (respuesta)
        // ============================
        // Modelo de hilo de un nivel: si se responde a una respuesta, se ancla
        // al comentario raíz del hilo para no crear niveles más profundos.
        $parentId = (int)($_POST['parent_id'] ?? 0);
        if ($parentId > 0) {
            $stmtParent = $con->prepare("SELECT id_comentario, parent_id FROM comentarios WHERE id_comentario = ? AND noticia_id = ? AND estado = 'activo'");
            $stmtParent->bind_param("ii", $parentId, $noticiaId);
            $stmtParent->execute();
            $parentRow = $stmtParent->get_result()->fetch_assoc();
            if (!$parentRow) {
                echo json_encode(['ok' => false, 'msg' => 'El comentario al que respondes ya no está disponible.']);
                exit;
            }
            if (!empty($parentRow['parent_id'])) {
                $parentId = (int)$parentRow['parent_id'];
            }
        }

        // ============================
        // FILTRO DE CENSURA Y BANEO AUTOMÁTICO
        // ============================
        // Solo se bloquea/sanciona si hay palabras ofensivas CONCRETAS detectadas
        // (diccionario o patrones locales). Si no hay coincidencias no se banea:
        // así se evitan falsos positivos y siempre se puede decir qué palabras fueron.
        $palabrasDetectadas = palabrasOfensivasDetectadas($contenido, $con);
        if (!empty($palabrasDetectadas)) {
            $listaPalabras = textoPalabrasOfensivas($palabrasDetectadas);
            $detalle = " Detectamos: {$listaPalabras}.";

            if ($tipoUsuario === 'lector' && $lectorId) {
                // Registrar notificación de intento ofensivo
                crearNotificacion($con, 'lector', $lectorId, 'Tu comentario no se publicó', "En CatInk cuidamos que este sea un espacio sano y respetuoso para toda la comunidad.{$detalle} Te invitamos a reformular tu comentario sin lenguaje ofensivo. ¡Gracias por ayudarnos a mantener la buena onda!", 'comentario_ofensivo');

                // Incrementar intentos profanos
                $stmtInc = $con->prepare("UPDATE lectores SET intentos_profanos = intentos_profanos + 1 WHERE id = ?");
                $stmtInc->bind_param("i", $lectorId);
                $stmtInc->execute();

                // Consultar intentos actuales
                $stmtGet = $con->prepare("SELECT intentos_profanos FROM lectores WHERE id = ?");
                $stmtGet->bind_param("i", $lectorId);
                $stmtGet->execute();
                $lectorData = $stmtGet->get_result()->fetch_assoc();
                $intentos = $lectorData['intentos_profanos'] ?? 0;

                // Contar cuántas notificaciones de comentario ofensivo tiene
                $stmtNotifCount = $con->prepare("SELECT COUNT(*) as total FROM notificaciones WHERE user_id = ? AND tipo_usuario = 'lector' AND tipo = 'comentario_ofensivo'");
                $stmtNotifCount->bind_param("i", $lectorId);
                $stmtNotifCount->execute();
                $resNotif = $stmtNotifCount->get_result()->fetch_assoc();
                $totalNotifOfensivo = $resNotif ? (int)$resNotif['total'] : 0;

                if ($totalNotifOfensivo >= 5 || $intentos >= 3) {
                    // Suspender por 24 horas
                    $baneadoHasta = date('Y-m-d H:i:s', strtotime('+24 hours'));
                    $motivo = ($totalNotifOfensivo >= 5)
                        ? "Acumulación de 5 notificaciones de comentario ofensivo"
                        : "Intento reiterado de publicar contenido obsceno";

                    $stmtBan = $con->prepare("UPDATE lectores SET baneado_hasta = ?, baneado_permanente = 0, baneado_motivo = ?, intentos_profanos = 0 WHERE id = ?");
                    $stmtBan->bind_param("ssi", $baneadoHasta, $motivo, $lectorId);
                    $stmtBan->execute();

                    // Crear notificación interna de suspensión (tono de advertencia, no de castigo)
                    crearNotificacion($con, 'lector', $lectorId, 'Tu cuenta quedó suspendida temporalmente', "En CatInk queremos mantener un ambiente sano y respetuoso para toda la comunidad. Detectamos lenguaje ofensivo de forma reiterada{$detalle} por eso tu cuenta quedó suspendida por 24 horas. No es un castigo: es un recordatorio de que aquí buscamos que todos se sientan a gusto. ¡Te esperamos de vuelta con buena onda!", 'moderacion');

                    echo json_encode([
                        'ok' => false,
                        'persist' => true,
                        'msg' => "Tu cuenta quedó suspendida temporalmente por 24 horas. En CatInk queremos mantener un ambiente sano para toda la comunidad y detectamos lenguaje ofensivo de forma reiterada.{$detalle} Podrás volver a comentar cuando termine la suspensión. ¡Te esperamos de vuelta con buena onda!"
                    ]);
                    exit;
                } else {
                    $restantes = max(0, 5 - $totalNotifOfensivo);
                    echo json_encode([
                        'ok' => false,
                        'persist' => true,
                        'msg' => "En CatInk buscamos mantener un espacio sano y respetuoso, por eso tu comentario no se publicó.{$detalle} Te pedimos reformularlo sin lenguaje ofensivo. Aviso amistoso: te quedan {$restantes} antes de una suspensión temporal de la cuenta."
                    ]);
                    exit;
                }
            } else {
                // Si es admin, solo bloquear (tono de advertencia)
                echo json_encode([
                    'ok' => false,
                    'persist' => true,
                    'msg' => "En CatInk cuidamos que este sea un espacio sano y respetuoso, por eso tu comentario no se publicó.{$detalle} Te pedimos reformularlo sin lenguaje ofensivo."
                ]);
                exit;
            }
        } else {
            // Si el comentario es limpio, resetear contador de intentos del lector
            if ($tipoUsuario === 'lector' && $lectorId) {
                $stmtReset = $con->prepare("UPDATE lectores SET intentos_profanos = 0 WHERE id = ?");
                $stmtReset->bind_param("i", $lectorId);
                $stmtReset->execute();
            }
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
              AND (parent_id <=> ?)
        ");
        $dupParent = $parentId > 0 ? $parentId : null;
        $stmtDuplicate->bind_param("iisi", $lectorId, $noticiaId, $contenido, $dupParent);
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
            
            // 4. Validar que no sea URL de contenido adulto
            if (esURLAdulta($url)) {
                echo json_encode(['ok' => false, 'msg' => 'No se permiten enlaces a contenido adulto.']);
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

        $parentParam = $parentId > 0 ? $parentId : null;
        $stmt = $con->prepare("INSERT INTO comentarios (noticia_id, lector_id, usuario_id, parent_id, contenido, estado) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iiiiss", $noticiaId, $lectorId, $usuarioId, $parentParam, $contenido, $estado);

        if ($stmt->execute()) {
            $msg = ($estado === 'oculto') ? 'Tu comentario fue enviado y será revisado antes de publicarse.' : 'Comentario publicado.';
            $newId = $stmt->insert_id;
            $stmtNew = $con->prepare("
                SELECT c.*,
                       COALESCE(u.nombre, l.nombre) AS nombre,
                       COALESCE(u.usuario, l.usuario) AS usuario,
                       COALESCE(u.foto_personal, ua.imagen, la.imagen) AS avatar_img,
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

        // ============================
        // FILTRO DE CENSURA Y BANEO AUTOMÁTICO EN EDICIÓN
        // ============================
        // Solo se bloquea/sanciona si hay palabras ofensivas CONCRETAS detectadas
        // (diccionario o patrones locales). Si no hay coincidencias no se banea:
        // así se evitan falsos positivos y siempre se puede decir qué palabras fueron.
        $palabrasDetectadas = palabrasOfensivasDetectadas($contenido, $con);
        if (!empty($palabrasDetectadas)) {
            $listaPalabras = textoPalabrasOfensivas($palabrasDetectadas);
            $detalle = " Detectamos: {$listaPalabras}.";

            if ($tipoUsuario === 'lector' && $lectorId) {
                // Registrar notificación de intento ofensivo en edición
                crearNotificacion($con, 'lector', $lectorId, 'Tu comentario no se actualizó', "En CatInk cuidamos que este sea un espacio sano y respetuoso para toda la comunidad.{$detalle} Te invitamos a reformular tu comentario sin lenguaje ofensivo. ¡Gracias por ayudarnos a mantener la buena onda!", 'comentario_ofensivo');

                // Incrementar intentos profanos
                $stmtInc = $con->prepare("UPDATE lectores SET intentos_profanos = intentos_profanos + 1 WHERE id = ?");
                $stmtInc->bind_param("i", $lectorId);
                $stmtInc->execute();

                // Consultar intentos actuales
                $stmtGet = $con->prepare("SELECT intentos_profanos FROM lectores WHERE id = ?");
                $stmtGet->bind_param("i", $lectorId);
                $stmtGet->execute();
                $lectorData = $stmtGet->get_result()->fetch_assoc();
                $intentos = $lectorData['intentos_profanos'] ?? 0;

                // Contar cuántas notificaciones de comentario ofensivo tiene
                $stmtNotifCount = $con->prepare("SELECT COUNT(*) as total FROM notificaciones WHERE user_id = ? AND tipo_usuario = 'lector' AND tipo = 'comentario_ofensivo'");
                $stmtNotifCount->bind_param("i", $lectorId);
                $stmtNotifCount->execute();
                $resNotif = $stmtNotifCount->get_result()->fetch_assoc();
                $totalNotifOfensivo = $resNotif ? (int)$resNotif['total'] : 0;

                if ($totalNotifOfensivo >= 5 || $intentos >= 3) {
                    // Suspender por 24 horas
                    $baneadoHasta = date('Y-m-d H:i:s', strtotime('+24 hours'));
                    $motivo = ($totalNotifOfensivo >= 5)
                        ? "Acumulación de 5 notificaciones de comentario ofensivo en edición"
                        : "Intento reiterado de publicar contenido obsceno en edición";

                    $stmtBan = $con->prepare("UPDATE lectores SET baneado_hasta = ?, baneado_permanente = 0, baneado_motivo = ?, intentos_profanos = 0 WHERE id = ?");
                    $stmtBan->bind_param("ssi", $baneadoHasta, $motivo, $lectorId);
                    $stmtBan->execute();

                    // Crear notificación interna de suspensión (tono de advertencia, no de castigo)
                    crearNotificacion($con, 'lector', $lectorId, 'Tu cuenta quedó suspendida temporalmente', "En CatInk queremos mantener un ambiente sano y respetuoso para toda la comunidad. Detectamos lenguaje ofensivo de forma reiterada{$detalle} por eso tu cuenta quedó suspendida por 24 horas. No es un castigo: es un recordatorio de que aquí buscamos que todos se sientan a gusto. ¡Te esperamos de vuelta con buena onda!", 'moderacion');

                    echo json_encode([
                        'ok' => false,
                        'persist' => true,
                        'msg' => "Tu cuenta quedó suspendida temporalmente por 24 horas. En CatInk queremos mantener un ambiente sano para toda la comunidad y detectamos lenguaje ofensivo de forma reiterada.{$detalle} Podrás volver a comentar cuando termine la suspensión. ¡Te esperamos de vuelta con buena onda!"
                    ]);
                    exit;
                } else {
                    $restantes = max(0, 5 - $totalNotifOfensivo);
                    echo json_encode([
                        'ok' => false,
                        'persist' => true,
                        'msg' => "En CatInk buscamos mantener un espacio sano y respetuoso, por eso tu edición no se guardó.{$detalle} Te pedimos reformularla sin lenguaje ofensivo. Aviso amistoso: te quedan {$restantes} antes de una suspensión temporal de la cuenta."
                    ]);
                    exit;
                }
            } else {
                echo json_encode([
                    'ok' => false,
                    'persist' => true,
                    'msg' => "En CatInk cuidamos que este sea un espacio sano y respetuoso, por eso tu edición no se guardó.{$detalle} Te pedimos reformularla sin lenguaje ofensivo."
                ]);
                exit;
            }
        } else {
            // Si el comentario es limpio, resetear intentos del lector
            if ($tipoUsuario === 'lector' && $lectorId) {
                $stmtReset = $con->prepare("UPDATE lectores SET intentos_profanos = 0 WHERE id = ?");
                $stmtReset->bind_param("i", $lectorId);
                $stmtReset->execute();
            }
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

        // El superadmin puede eliminar cualquier comentario; los demás solo el propio.
        if (!esSuperAdminActual()) {
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
