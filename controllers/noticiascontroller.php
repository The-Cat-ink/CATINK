<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
session_start();
include("./aclcontroller.php");
proteger('noticias','crear');
date_default_timezone_set('America/Mexico_City');
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
$fecha_publicacion = $_POST['fecha_publicacion'] ?? date('Y-m-d H:i:s');
$fecha_publicacion = str_replace('T', ' ', $fecha_publicacion);
$calificacion = max(1, min(5, intval($_POST['calificacion'] ?? 1)));
// ============================
// VALIDACION
// ============================
if (empty($titulo) || empty($descripcion) || empty($contenido)) {
  header('Content-Type: application/json');
  echo json_encode(['success' => false, 'error' => 'Datos incompletos. Revisa título, descripción y contenido.']);
  exit;
}
// ============================
// INSERTAR NOTICIA (YA SIN CATEGORIA)
// ============================
$usuario_id = intval($_SESSION['id_u'] ?? 0);
$sql = "INSERT INTO noticias (titulo, slug, descripcion, autor, contenido, fecha_publicacion, creado_por, editado_por, calificacion)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $con->prepare($sql);
if (!$stmt) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Error de Base de Datos al preparar la noticia.']);
    exit;
}
$stmt->bind_param("sssissiii", $titulo, $slug, $descripcion, $autor, $contenido, $fecha_publicacion, $usuario_id, $usuario_id, $calificacion);
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
// REDIRECCION / AJAX RESPONSE
// ============================
header('Content-Type: application/json');
echo json_encode(['success' => true, 'id' => $noticiaId]);
exit;
?>