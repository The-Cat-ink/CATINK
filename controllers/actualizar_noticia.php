<?php
session_start();
include("./aclcontroller.php");
proteger('noticias','editar');
include("../data/conexion.php");
require_once("../views/helpers/urlhelper.php");
require_once("../views/helpers/activity_log.php");

// ============================
// FUNCION GUARDAR IMAGEN BASE64
// ============================
function guardarImagenBase64WebpConId($base64, $noticiaId, $crop, $calidad = 100) {
    if (empty($base64)) return null;

    if (!preg_match('/^data:image\/(jpeg|jpg|png|webp|gif);base64,/', $base64, $matches)) {
        error_log("CATINK IMG UPDATE: regex no coincide para crop=$crop, inicio=" . substr($base64, 0, 50));
        return null;
    }

    $tipo = $matches[1];
    $binario = base64_decode(preg_replace('/^data:image\/\w+;base64,/', '', $base64));
    if ($binario === false || empty($binario)) return null;

    // ============================
    // CREAR CARPETA SI NO EXISTE
    // ============================
    $dirFisica = __DIR__ . "/../img/noticias/";
    if (!is_dir($dirFisica)) {
        if (!mkdir($dirFisica, 0755, true)) {
            error_log("CATINK IMG UPDATE: No se pudo crear carpeta $dirFisica");
            return null;
        }
    }

    // ============================
    // VALIDAR PERMISOS DE ESCRITURA
    // ============================
    if (!is_writable($dirFisica)) {
        error_log("CATINK IMG UPDATE: Carpeta $dirFisica no tiene permisos de escritura");
        return null;
    }

    $timestamp = time();
    
    // ============================
    // GUARDAR EN CARPETA PERSISTENTE (FUERA de public_html)
    // ============================
    // En producción: /home/usuario/uploads/noticias/
    // En local: /uploads/noticias/
    // Esto evita que Hostinger borre las imágenes en despliegues
    $isLocal = strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false || strpos($_SERVER['HTTP_HOST'] ?? '', 'catink.test') !== false;
    $dirUploads = ($isLocal ? dirname(__DIR__) : dirname(dirname(__DIR__))) . "/uploads/noticias/";
    
    // Si no existe, crear carpeta
    if (!is_dir($dirUploads)) {
        if (!mkdir($dirUploads, 0755, true)) {
            error_log("CATINK IMG UPDATE: No se pudo crear carpeta $dirUploads");
            return null;
        }
    }
    
    // Validar permisos
    if (!is_writable($dirUploads)) {
        error_log("CATINK IMG UPDATE: Carpeta $dirUploads no tiene permisos de escritura");
        return null;
    }
    
    // Mantener el formato original para preservar calidad
    $extension = strtolower($tipo);
    if ($extension === 'jpg') $extension = 'jpeg';
    
    $nombre = "noticia_{$noticiaId}_{$crop}_{$timestamp}.{$extension}";
    $rutaFisica = $dirUploads . $nombre;

    // Guardar directamente sin conversión ni compresión
    $bytesEscritos = file_put_contents($rutaFisica, $binario);
    if ($bytesEscritos === false) {
        error_log("CATINK IMG UPDATE: Error escribiendo archivo $rutaFisica");
        return null;
    }

    // ============================
    // VALIDAR QUE EL ARCHIVO SE GUARDÓ CORRECTAMENTE
    // ============================
    if (!file_exists($rutaFisica)) {
        error_log("CATINK IMG UPDATE: Archivo no existe después de guardarlo: $rutaFisica");
        return null;
    }

    if (filesize($rutaFisica) === 0) {
        error_log("CATINK IMG UPDATE: Archivo vacío: $rutaFisica");
        unlink($rutaFisica);
        return null;
    }

    return "uploads/noticias/" . $nombre;
}
// ============================
// DATOS
// ============================
$id = intval($_POST['id'] ?? 0);
$titulo = $_POST['titulo'] ?? '';
$descripcion = $_POST['descripcion'] ?? '';
$categorias = $_POST['categoria'] ?? []; // IDs ahora
$contenido = $_POST['contenido'] ?? '';
$slug = generateSlug($titulo);
// sanitize as above
$contenido = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/i', '', $contenido);
$contenido = preg_replace_callback(
    '/<blockquote[^>]*>.*?<a[^>]+href="([^"]+)"[^>]*><\/a>.*?<\/blockquote>/is',
    function($m){ return '<div class="social-embed" data-url="'.htmlspecialchars($m[1]).'"\></div>'; },
    $contenido
);

$fecha_publicacion = $_POST['fecha_publicacion'] ?? date('Y-m-d H:i:s');

$tipo_publicacion = $_POST['tipo_publicacion'] ?? 'noticia';
$calificacion = null;
$pros = null;
$contras = null;

if ($tipo_publicacion === 'review') {
    $calificacion = isset($_POST['calificacion']) && $_POST['calificacion'] !== '' ? floatval($_POST['calificacion']) : null;
    $pros = isset($_POST['pros']) ? trim($_POST['pros']) : null;
    $contras = isset($_POST['contras']) ? trim($_POST['contras']) : null;
}

$es_estreno = isset($_POST['es_estreno']) ? intval($_POST['es_estreno']) : 0;
$seccion_estreno = ($es_estreno === 1 && !empty($_POST['seccion_estreno'])) ? $_POST['seccion_estreno'] : null;

// ============================
// VALIDACION
// ============================
if ($id <= 0 || empty($titulo) || empty($descripcion) || empty($contenido)) {
  header("Location: ./../views/contenidos.php");
  exit;
}
// ============================
// ACTUALIZAR NOTICIA (SIN CATEGORIA)
// ============================
$usuario_id = $_SESSION['id_u'] ?? null;

// Si creado_por es NULL (nota antigua), llenarla con el usuario actual
$checkCreado = $con->prepare("SELECT creado_por FROM noticias WHERE id = ?");
$checkCreado->bind_param("i", $id);
$checkCreado->execute();
$resCreado = $checkCreado->get_result()->fetch_assoc();
$creado_por = $resCreado['creado_por'] ?? $usuario_id;

// ============================
// GUARDAR HISTORIAL DE EDICION DE NOTICIA
// ============================
$stmtPrev = $con->prepare("SELECT titulo, descripcion, contenido FROM noticias WHERE id = ?");
$stmtPrev->bind_param("i", $id);
$stmtPrev->execute();
$prevData = $stmtPrev->get_result()->fetch_assoc();

if ($prevData) {
    $cambios = [];
    if (trim($prevData['titulo']) !== trim($titulo)) {
        $cambios[] = 'Título';
    }
    if (trim($prevData['descripcion']) !== trim($descripcion)) {
        $cambios[] = 'Descripción';
    }
    if (trim($prevData['contenido']) !== trim($contenido)) {
        $cambios[] = 'Contenido';
    }
    if (!empty($_POST['crop1']) || !empty($_POST['crop2']) || !empty($_POST['crop3']) || !empty($_POST['crop4'])) {
        $cambios[] = 'Imágenes';
    }

    if (!empty($cambios)) {
        $motivoDetallado = 'Cambios en: ' . implode(', ', $cambios);
        if (!empty($_POST['motivo_cambio'])) {
            $motivoDetallado .= ' — ' . trim($_POST['motivo_cambio']);
        }
        $stmtHist = $con->prepare("INSERT INTO historial_ediciones_noticias (noticia_id, usuario_id, titulo, descripcion, contenido, motivo_cambio, fecha_edicion) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $stmtHist->bind_param("iissss", $id, $usuario_id, $prevData['titulo'], $prevData['descripcion'], $prevData['contenido'], $motivoDetallado);
        $stmtHist->execute();
    }
}

// Al guardar desde el editor, la noticia deja de ser borrador: se publica con
// la fecha indicada (borrador = 0). La validación de arriba ya garantiza que
// tiene título, descripción y contenido. `fecha_programada` era solo el apunte
// del borrador: la fecha ya vive en fecha_publicacion, así que se limpia.
$update = $con->prepare("
  UPDATE noticias
  SET titulo = ?, slug = ?, descripcion = ?, contenido = ?, fecha_publicacion = ?, creado_por = ?, editado_por = ?, tipo_publicacion = ?, calificacion = ?, pros = ?, contras = ?, es_estreno = ?, seccion_estreno = ?, borrador = 0, fecha_programada = NULL
  WHERE id = ?
");
// Asegurarse de que creado_por y usuario_id son integers
$creado_por = intval($creado_por);
$usuario_id = intval($usuario_id);
$update->bind_param("sssssiissssisi", $titulo, $slug, $descripcion, $contenido, $fecha_publicacion, $creado_por, $usuario_id, $tipo_publicacion, $calificacion, $pros, $contras, $es_estreno, $seccion_estreno, $id);
$result = $update->execute();
// ============================
// OBTENER IMAGENES ACTUALES
// ============================
$stmt = $con->prepare("SELECT crop1, crop2, crop3, crop4 FROM noticias WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
$actual = $res->fetch_assoc();
$c1 = $actual['crop1'] ?? null;
$c2 = $actual['crop2'] ?? null;
$c3 = $actual['crop3'] ?? null;
$c4 = $actual['crop4'] ?? null;
// ============================
// NUEVAS IMAGENES
// ============================
$new1 = guardarImagenBase64WebpConId($_POST['crop1'] ?? null, $id, 'crop1');
$new2 = guardarImagenBase64WebpConId($_POST['crop2'] ?? null, $id, 'crop2');
$new3 = guardarImagenBase64WebpConId($_POST['crop3'] ?? null, $id, 'crop3');
$new4 = guardarImagenBase64WebpConId($_POST['crop4'] ?? null, $id, 'crop4');
if ($new1 || $new2 || $new3 || $new4) {
  // Eliminar imágenes antiguas si se suben nuevas
  $isLocalEnv = strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false || strpos($_SERVER['HTTP_HOST'] ?? '', 'catink.test') !== false;
  foreach ([1 => [$new1, $c1], 2 => [$new2, $c2], 3 => [$new3, $c3], 4 => [$new4, $c4]] as [$newPath, $oldPath]) {
    if ($newPath && $oldPath && $oldPath !== $newPath) {
      $rutaVieja = ($isLocalEnv ? dirname(__DIR__) : dirname(dirname(__DIR__))) . "/" . $oldPath;
      if (file_exists($rutaVieja)) unlink($rutaVieja);
    }
  }
  $c1 = $new1 ?: $c1;
  $c2 = $new2 ?: $c2;
  $c3 = $new3 ?: $c3;
  $c4 = $new4 ?: $c4;
  $updImgs = $con->prepare("UPDATE noticias SET crop1=?, crop2=?, crop3=?, crop4=? WHERE id=?");
  $updImgs->bind_param("ssssi", $c1, $c2, $c3, $c4, $id);
  $updImgs->execute();
}
// ============================
// ACTUALIZAR CATEGORIAS (SYNC)
// ============================
// borrar anteriores
$con->query("DELETE FROM noticia_categoria WHERE noticia_id = $id");
// insertar nuevas
if (!empty($categorias)) {
  $stmtCat = $con->prepare("INSERT INTO noticia_categoria (noticia_id, categoria_id, orden) VALUES (?, ?, ?)");
  foreach ($categorias as $i => $cat_id) {
    $orden = $i + 1;
    $stmtCat->bind_param("iii", $id, $cat_id, $orden);
    $stmtCat->execute();
  }
}
// ============================
// REDIRECCION
// ============================
logActivity($con, 'editar', 'noticias', 'Actualizó la noticia ID ' . $id . ': «' . mb_substr($titulo ?? '', 0, 80) . '»');
header("Location: ./../views/editar.php?id=$id&msg=actualizado");
exit;
?>