<?php
// ============================================================
// AUTOGUARDADO DE BORRADOR (servidor)
// Crea o actualiza una noticia con borrador = 1 mientras el editor
// escribe en crear.php. Devuelve el id del borrador para que las
// siguientes llamadas actualicen la misma fila. No maneja imágenes
// (se agregan al completar la nota en el editor).
// ============================================================
session_start();
include("./aclcontroller.php");
proteger('noticias', 'crear');
date_default_timezone_set('America/Mexico_City');
include("../data/conexion.php");
require_once("../views/helpers/urlhelper.php");

header('Content-Type: application/json');

// Guarda una imagen base64 como archivo (misma lógica que noticiascontroller).
function guardarImagenBase64WebpConId($base64, $noticiaId, $crop) {
    if (empty($base64)) return null;
    if (!preg_match('/^data:image\/(jpeg|jpg|png|webp|gif);base64,/', $base64, $matches)) return null;
    $tipo = $matches[1];
    $binario = base64_decode(preg_replace('/^data:image\/\w+;base64,/', '', $base64));
    if ($binario === false || empty($binario)) return null;

    $isLocal = strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false || strpos($_SERVER['HTTP_HOST'] ?? '', 'catink.test') !== false;
    $dirUploads = ($isLocal ? dirname(__DIR__) : dirname(dirname(__DIR__))) . "/uploads/noticias/";
    if (!is_dir($dirUploads) && !mkdir($dirUploads, 0755, true)) return null;
    if (!is_writable($dirUploads)) return null;

    $extension = strtolower($tipo);
    if ($extension === 'jpg') $extension = 'jpeg';
    $nombre = "noticia_{$noticiaId}_{$crop}_" . time() . ".{$extension}";
    $rutaFisica = $dirUploads . $nombre;
    if (file_put_contents($rutaFisica, $binario) === false) return null;
    if (!file_exists($rutaFisica) || filesize($rutaFisica) === 0) { @unlink($rutaFisica); return null; }
    return "uploads/noticias/" . $nombre;
}

$draftId = intval($_POST['draft_id'] ?? 0);
$titulo  = trim($_POST['titulo'] ?? '');
$descripcion = $_POST['descripcion'] ?? '';
$contenido   = $_POST['contenido'] ?? '';
// Sanitizar igual que en la creación normal (quitar <script>)
$contenido = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/i', '', $contenido);

$tipo_publicacion = $_POST['tipo_publicacion'] ?? 'noticia';
if (!in_array($tipo_publicacion, ['noticia', 'review'], true)) {
    $tipo_publicacion = 'noticia';
}

$calificacion = null;
$pros = null;
$contras = null;
if ($tipo_publicacion === 'review') {
    $calificacion = (isset($_POST['calificacion']) && $_POST['calificacion'] !== '') ? floatval($_POST['calificacion']) : null;
    $pros    = ($_POST['pros'] ?? '')    !== '' ? trim($_POST['pros'])    : null;
    $contras = ($_POST['contras'] ?? '') !== '' ? trim($_POST['contras']) : null;
}

$es_estreno = isset($_POST['es_estreno']) ? intval($_POST['es_estreno']) : 0;
$seccion_estreno = ($es_estreno === 1 && !empty($_POST['seccion_estreno'])) ? $_POST['seccion_estreno'] : null;
$categorias = $_POST['categoria'] ?? [];
$slug = generateSlug($titulo);
$usuario_id = intval($_SESSION['id_u'] ?? 0);

// No guardar borradores completamente vacíos (sin texto ni imagen)
$tieneImagen = false;
foreach (['crop1', 'crop2', 'crop3', 'crop4'] as $ck) {
    if (!empty($_POST[$ck]) && strpos($_POST[$ck], 'data:image/') === 0) { $tieneImagen = true; break; }
}
if ($titulo === '' && trim(strip_tags($contenido)) === '' && !$tieneImagen) {
    echo json_encode(['ok' => false, 'msg' => 'vacio']);
    exit;
}

// Si nos pasan un id, verificar que siga siendo un borrador válido.
if ($draftId > 0) {
    $chk = $con->prepare("SELECT id FROM noticias WHERE id = ? AND borrador = 1 AND eliminado_en IS NULL");
    $chk->bind_param("i", $draftId);
    $chk->execute();
    if ($chk->get_result()->num_rows === 0) {
        $draftId = 0; // ya no existe o se publicó: crear uno nuevo
    }
}

if ($draftId > 0) {
    // ── Actualizar el borrador existente ──
    $sql = "UPDATE noticias
            SET titulo = ?, slug = ?, descripcion = ?, contenido = ?, tipo_publicacion = ?,
                calificacion = ?, pros = ?, contras = ?, es_estreno = ?, seccion_estreno = ?, editado_por = ?
            WHERE id = ? AND borrador = 1";
    $stmt = $con->prepare($sql);
    $stmt->bind_param(
        "ssssssssisii",
        $titulo, $slug, $descripcion, $contenido, $tipo_publicacion,
        $calificacion, $pros, $contras, $es_estreno, $seccion_estreno, $usuario_id, $draftId
    );
    $stmt->execute();
} else {
    // ── Crear un borrador nuevo (fecha NULL) ──
    $fecha = null;
    $sql = "INSERT INTO noticias
            (titulo, slug, descripcion, autor, contenido, fecha_publicacion, creado_por, editado_por,
             tipo_publicacion, calificacion, pros, contras, es_estreno, seccion_estreno, borrador)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)";
    $stmt = $con->prepare($sql);
    $stmt->bind_param(
        "sssissiissssis",
        $titulo, $slug, $descripcion, $usuario_id, $contenido, $fecha, $usuario_id, $usuario_id,
        $tipo_publicacion, $calificacion, $pros, $contras, $es_estreno, $seccion_estreno
    );
    if (!$stmt->execute()) {
        echo json_encode(['ok' => false, 'msg' => 'error_insert']);
        exit;
    }
    $draftId = $con->insert_id;
}

// ── Guardar imágenes que hayan cambiado (llegan como base64) ──
// El cliente solo manda un crop cuando cambió, así no re-subimos en cada guardado.
if ($draftId > 0) {
    $cropCols = [];
    $cropVals = [];
    foreach (['crop1', 'crop2', 'crop3', 'crop4'] as $ck) {
        if (!empty($_POST[$ck]) && strpos($_POST[$ck], 'data:image/') === 0) {
            $ruta = guardarImagenBase64WebpConId($_POST[$ck], $draftId, $ck);
            if ($ruta) { $cropCols[] = "$ck = ?"; $cropVals[] = $ruta; }
        }
    }
    if ($cropCols) {
        $sqlImg = "UPDATE noticias SET " . implode(', ', $cropCols) . " WHERE id = ?";
        $stmtImg = $con->prepare($sqlImg);
        $types = str_repeat('s', count($cropVals)) . 'i';
        $bindParams = array_merge($cropVals, [$draftId]);
        $stmtImg->bind_param($types, ...$bindParams);
        $stmtImg->execute();
    }
}

// ── Sincronizar categorías del borrador ──
if ($draftId > 0) {
    $con->query("DELETE FROM noticia_categoria WHERE noticia_id = " . intval($draftId));
    if (!empty($categorias)) {
        $stmtCat = $con->prepare("INSERT INTO noticia_categoria (noticia_id, categoria_id, orden) VALUES (?, ?, ?)");
        foreach ($categorias as $i => $cat) {
            $orden = $i + 1;
            $cid = intval($cat);
            $stmtCat->bind_param("iii", $draftId, $cid, $orden);
            $stmtCat->execute();
        }
    }
}

echo json_encode(['ok' => true, 'id' => $draftId]);
