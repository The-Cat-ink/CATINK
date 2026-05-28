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
function guardarImagenBase64WebpConId($base64, $noticiaId, $crop, $calidad = 95) {
    if (empty($base64)) return null;

    // Detectar el tipo real de imagen que manda el cropper
    if (!preg_match('/^data:image\/(jpeg|jpg|png|webp|gif);base64,/', $base64, $matches)) {
        error_log("CATINK IMG: regex no coincide para crop=$crop, inicio=" . substr($base64, 0, 50));
        return null;
    }

    $extension = $matches[1] === 'jpeg' ? 'jpg' : $matches[1];

    // Decodificar base64 sin usar GD (evita doble compresión)
    $binario = base64_decode(preg_replace('/^data:image\/\w+;base64,/', '', $base64));
    if ($binario === false || empty($binario)) return null;

    $timestamp = time();
    $nombre = "noticia_{$noticiaId}_{$crop}_{$timestamp}.{$extension}";
    $rutaFisica = __DIR__ . "/../img/noticias/" . $nombre;

    if (file_put_contents($rutaFisica, $binario) === false) return null;

    return "img/noticias/" . $nombre;
}
// ============================
// CONEXION
// ============================
include("../data/conexion.php");
// ============================
// DATOS FORMULARIO
// ============================
$titulo = $_POST['titulo'] ?? '';
$descripcion = $_POST['descripcion'] ?? '';
$categorias = $_POST['categoria'] ?? [];
$autor = $_POST['autor'] ?? '';
$contenido = $_POST['contenido'] ?? '';
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
// ============================
// VALIDACION
// ============================
if (empty($titulo) || empty($descripcion) || empty($contenido)) {
  die("Datos incompletos");
}
// ============================
// INSERTAR NOTICIA (YA SIN CATEGORIA)
// ============================
$sql = "INSERT INTO noticias (titulo, descripcion, autor, contenido, fecha_publicacion)
        VALUES (?, ?, ?, ?, ?)";
$stmt = $con->prepare($sql);
$stmt->bind_param("ssiss", $titulo, $descripcion, $autor, $contenido, $fecha_publicacion);
$stmt->execute();
$noticiaId = $con->insert_id;
// ============================
// GUARDAR IMAGENES
// ============================
$crop1 = guardarImagenBase64WebpConId($_POST['crop1'] ?? null, $noticiaId, 'crop1');
$crop2 = guardarImagenBase64WebpConId($_POST['crop2'] ?? null, $noticiaId, 'crop2');
$crop3 = guardarImagenBase64WebpConId($_POST['crop3'] ?? null, $noticiaId, 'crop3');
// ============================
// ACTUALIZAR RUTAS IMAGENES
// ============================
$update = $con->prepare("UPDATE noticias SET crop1=?, crop2=?, crop3=? WHERE id=?");
$update->bind_param("sssi", $crop1, $crop2, $crop3, $noticiaId);
$update->execute();
// ============================
// INSERTAR CATEGORIAS RELACIONADAS
// ============================
if (!empty($categorias)) {
  $stmtCat = $con->prepare("INSERT INTO noticia_categoria (noticia_id, categoria_id) VALUES (?, ?)");

  foreach ($categorias as $cat_id) {
    $stmtCat->bind_param("ii", $noticiaId, $cat_id);
    $stmtCat->execute();
  }
}
// ============================
// REDIRECCION
// ============================
header("Location: ./../views/contenidos.php");
exit;
?>