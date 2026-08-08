<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
session_start();
include("./aclcontroller.php");
proteger('noticias','crear');
date_default_timezone_set('America/Mexico_City');
require_once(__DIR__ . '/../views/helpers/activity_log.php');

// ============================
// GUARDAR IMAGEN BASE64
// ============================
function guardarImagenBase64WebpConId($base64, $noticiaId, $crop, $calidad = 100) {
    if (empty($base64)) return null;

    if (!preg_match('/^data:image\/(jpeg|jpg|png|webp|gif);base64,/', $base64, $matches)) {
        error_log("CATINK IMG: regex no coincide para crop=$crop, inicio=" . substr($base64, 0, 50));
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
            error_log("CATINK IMG: No se pudo crear carpeta $dirFisica");
            return null;
        }
    }

    // ============================
    // VALIDAR PERMISOS DE ESCRITURA
    // ============================
    if (!is_writable($dirFisica)) {
        error_log("CATINK IMG: Carpeta $dirFisica no tiene permisos de escritura");
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
            error_log("CATINK IMG: No se pudo crear carpeta $dirUploads");
            return null;
        }
    }
    
    // Validar permisos
    if (!is_writable($dirUploads)) {
        error_log("CATINK IMG: Carpeta $dirUploads no tiene permisos de escritura");
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
        error_log("CATINK IMG: Error escribiendo archivo $rutaFisica");
        return null;
    }

    // ============================
    // VALIDAR QUE EL ARCHIVO SE GUARDÓ CORRECTAMENTE
    // ============================
    if (!file_exists($rutaFisica)) {
        error_log("CATINK IMG: Archivo no existe después de guardarlo: $rutaFisica");
        return null;
    }

    if (filesize($rutaFisica) === 0) {
        error_log("CATINK IMG: Archivo vacío: $rutaFisica");
        unlink($rutaFisica);
        return null;
    }

    return "uploads/noticias/" . $nombre;
}
// ============================
// CONEXION
// ============================
include("../data/conexion.php");
require_once("../views/helpers/urlhelper.php");
// ============================
// DATOS FORMULARIO
// ============================
$titulo = $_POST['titulo'] ?? '';
$descripcion = $_POST['descripcion'] ?? '';
$categorias = $_POST['categoria'] ?? [];
$autor = $_POST['autor'] ?? '';
$contenido = $_POST['contenido'] ?? '';
$slug = generateSlug($titulo);
// limpiar posibles scripts y conservar solo el placeholder
$contenido = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/i', '', $contenido);
// convertir posibles embed html a div.social-embed (blockquote generados por Quill/JS)
$contenido = preg_replace_callback(
    '/<blockquote[^>]*>.*?<a[^>]+href="([^"]+)"[^>]*><\/a>.*?<\/blockquote>/is',
    function($m){ return '<div class="social-embed" data-url="'.htmlspecialchars($m[1]).'"></div>'; },
    $contenido
);
// ¿Se está guardando como borrador? Un borrador no tiene fecha de publicación
// (queda NULL) para que NO aparezca en el sitio ni se publique solo.
$esBorrador = intval($_POST['borrador'] ?? 0) === 1;

if ($esBorrador) {
    $fecha_publicacion = null;
} else {
    $fecha_publicacion = $_POST['fecha_publicacion'] ?? date('Y-m-d H:i:s');
    $fecha_publicacion = str_replace('T', ' ', $fecha_publicacion);
}

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
// Un borrador solo exige título (trabajo en progreso). Una publicación normal
// exige título, descripción y contenido.
if ($esBorrador) {
  if (empty($titulo)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'El borrador necesita al menos un título.']);
    exit;
  }
} elseif (empty($titulo) || empty($descripcion) || empty($contenido)) {
  header('Content-Type: application/json');
  echo json_encode(['success' => false, 'error' => 'Datos incompletos. Revisa título, descripción y contenido.']);
  exit;
}
// Red de seguridad: un <img> sin src es una imagen que no alcanzó a subir. Si
// la guardamos, queda un hueco en la nota que ya no se puede recuperar, porque
// el archivo original solo existía en el navegador. Un borrador sí puede
// guardarse a medias, pero una publicación no.
if (!$esBorrador) {
  $imgsSinSrc = preg_match_all('/<img\b(?![^>]*\bsrc\s*=)[^>]*>/i', $contenido);
  if ($imgsSinSrc > 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => "Hay $imgsSinSrc imagen(es) del contenido que no terminaron de subir. Espera a que carguen y vuelve a publicar."]);
    exit;
  }
}
// ============================
// INSERTAR NOTICIA (YA SIN CATEGORIA)
// ============================
$usuario_id = intval($_SESSION['id_u'] ?? 0);
$borrador = $esBorrador ? 1 : 0;
$sql = "INSERT INTO noticias (titulo, slug, descripcion, autor, contenido, fecha_publicacion, creado_por, editado_por, tipo_publicacion, calificacion, pros, contras, es_estreno, seccion_estreno, borrador)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $con->prepare($sql);
if (!$stmt) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Error de Base de Datos al preparar la noticia.']);
    exit;
}
$stmt->bind_param("sssissiissssisi", $titulo, $slug, $descripcion, $autor, $contenido, $fecha_publicacion, $usuario_id, $usuario_id, $tipo_publicacion, $calificacion, $pros, $contras, $es_estreno, $seccion_estreno, $borrador);
if (!$stmt->execute()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Error al guardar la noticia en Base de Datos.']);
    exit;
}
$noticiaId = $con->insert_id;
// ============================
// GUARDAR IMAGENES
// ============================
$crop1 = guardarImagenBase64WebpConId($_POST['crop1'] ?? null, $noticiaId, 'crop1');
$crop2 = guardarImagenBase64WebpConId($_POST['crop2'] ?? null, $noticiaId, 'crop2');
$crop3 = guardarImagenBase64WebpConId($_POST['crop3'] ?? null, $noticiaId, 'crop3');
$crop4 = guardarImagenBase64WebpConId($_POST['crop4'] ?? null, $noticiaId, 'crop4');
// ============================
// ACTUALIZAR RUTAS IMAGENES
// ============================
$update = $con->prepare("UPDATE noticias SET crop1=?, crop2=?, crop3=?, crop4=? WHERE id=?");
$update->bind_param("ssssi", $crop1, $crop2, $crop3, $crop4, $noticiaId);
$update->execute();
// ============================
// INSERTAR CATEGORIAS RELACIONADAS
// ============================
if (!empty($categorias)) {
  $stmtCat = $con->prepare("INSERT INTO noticia_categoria (noticia_id, categoria_id, orden) VALUES (?, ?, ?)");

  foreach ($categorias as $i => $cat_id) {
    $orden = $i + 1;
    $stmtCat->bind_param("iii", $noticiaId, $cat_id, $orden);
    $stmtCat->execute();
  }
}
// ============================
// LIMPIAR BORRADOR DE AUTOGUARDADO
// ============================
// Si esta publicación venía de un borrador autoguardado, ya se copió su
// contenido a la nota nueva: eliminamos el borrador para que no quede
// duplicado en el apartado "Borradores".
$draftId = intval($_POST['draft_id'] ?? 0);
if (!$esBorrador && $draftId > 0 && $draftId !== $noticiaId) {
    $con->query("DELETE FROM noticia_categoria WHERE noticia_id = " . $draftId);
    $delDraft = $con->prepare("DELETE FROM noticias WHERE id = ? AND borrador = 1");
    $delDraft->bind_param("i", $draftId);
    $delDraft->execute();
}
// ============================
// REDIRECCION / AJAX RESPONSE
// ============================
require_once(__DIR__ . "/../views/helpers/cachehelper.php");
clear_cache_by_prefix();

$esBorradorLabel = $esBorrador ? 'borrador' : ($fecha_publicacion > date('Y-m-d H:i:s') ? 'noticia programada' : 'noticia');
logActivity($con, $esBorrador ? 'borrador' : 'crear', 'noticias', 'Creó ' . $esBorradorLabel . ' «' . mb_substr($titulo, 0, 80) . '» (ID ' . $noticiaId . ')');
header('Content-Type: application/json');
echo json_encode(['success' => true, 'id' => $noticiaId]);
exit;
?>