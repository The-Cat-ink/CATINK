<?php
// Sube una imagen para usarla como icono de categoría en el menú.
//
// Devuelve solo la ruta: quien la guarda en la BD es crearc.php / editarc.php al
// enviar el modal. Eso permite subir la imagen antes de que la categoría exista
// (modal de crear) y que cancelar el modal no deje datos a medias.
//
// La imagen se reescala a 128x128 como máximo. En el menú se dibuja a ~20px, así
// que 128 da margen de sobra incluso en pantallas 3x, y evita guardar un JPG de
// 2000px para pintarlo diminuto.
include(__DIR__ . "/aclcontroller.php");
proteger('categorias', 'editar');
include(__DIR__ . "/../data/conexion.php");
header('Content-Type: application/json');

const ICONO_LADO_MAX = 128;

if (!isset($_FILES['imagen']) || $_FILES['imagen']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['ok' => false, 'error' => 'No se recibió imagen.']);
    exit;
}

$file = $_FILES['imagen'];

// Tamaño máximo del archivo original (5MB, igual que los avatares)
if ($file['size'] > 5 * 1024 * 1024) {
    echo json_encode(['ok' => false, 'error' => 'Archivo demasiado grande (máximo 5MB).']);
    exit;
}

// MIME real, no solo la extensión. Se deja fuera SVG a propósito: GD no lo
// procesa y un SVG puede traer scripts dentro.
$mimeType = mime_content_type($file['tmp_name']);
$mimePermitidos = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
if (!in_array($mimeType, $mimePermitidos, true)) {
    echo json_encode(['ok' => false, 'error' => 'Formato no válido. Usa JPG, PNG, GIF o WEBP.']);
    exit;
}

// Segunda capa: extensión
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
    echo json_encode(['ok' => false, 'error' => 'Extensión no válida.']);
    exit;
}

$info = getimagesize($file['tmp_name']);
if ($info === false) {
    echo json_encode(['ok' => false, 'error' => 'No es una imagen válida.']);
    exit;
}
[$anchoOrig, $altoOrig] = $info;
if ($anchoOrig < 1 || $altoOrig < 1) {
    echo json_encode(['ok' => false, 'error' => 'No es una imagen válida.']);
    exit;
}
if ($anchoOrig > 4000 || $altoOrig > 4000) {
    echo json_encode(['ok' => false, 'error' => 'Imagen demasiado grande (máximo 4000x4000 píxeles).']);
    exit;
}

// Cargar según el tipo real
switch ($mimeType) {
    case 'image/jpeg': $src = @imagecreatefromjpeg($file['tmp_name']); break;
    case 'image/png':  $src = @imagecreatefrompng($file['tmp_name']);  break;
    case 'image/gif':  $src = @imagecreatefromgif($file['tmp_name']);  break;
    case 'image/webp': $src = @imagecreatefromwebp($file['tmp_name']); break;
    default:           $src = false;
}
if (!$src) {
    echo json_encode(['ok' => false, 'error' => 'No se pudo procesar la imagen.']);
    exit;
}

// Reducir manteniendo la proporción. Si ya es más chica que el máximo se deja igual
// (ampliarla solo la haría borrosa).
$escala = min(ICONO_LADO_MAX / $anchoOrig, ICONO_LADO_MAX / $altoOrig, 1);
$ancho = max(1, (int)round($anchoOrig * $escala));
$alto  = max(1, (int)round($altoOrig  * $escala));

$dst = imagecreatetruecolor($ancho, $alto);
// Fondo transparente: muchos iconos son logos en PNG con alfa y sobre el fondo
// oscuro del menú un relleno blanco se vería como un recuadro.
imagealphablending($dst, false);
imagesavealpha($dst, true);
imagefill($dst, 0, 0, imagecolorallocatealpha($dst, 0, 0, 0, 127));
imagealphablending($dst, true);
imagecopyresampled($dst, $src, 0, 0, 0, 0, $ancho, $alto, $anchoOrig, $altoOrig);
imagedestroy($src);

// Carpeta persistente, fuera de public_html en producción (igual que noticias y
// avatares) para que un despliegue no borre los iconos subidos.
$isLocal = strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false
        || strpos($_SERVER['HTTP_HOST'] ?? '', 'catink.test') !== false;
$dir = ($isLocal ? dirname(__DIR__) : dirname(dirname(__DIR__))) . '/uploads/iconos/';
if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
    imagedestroy($dst);
    echo json_encode(['ok' => false, 'error' => 'No se pudo crear la carpeta de iconos.']);
    exit;
}

// Siempre PNG: conserva la transparencia venga de donde venga el original.
$nombre  = 'icono_' . time() . '_' . mt_rand(1000, 9999) . '.png';
$destino = $dir . $nombre;
$guardado = imagepng($dst, $destino, 9);
imagedestroy($dst);

if (!$guardado || !file_exists($destino) || filesize($destino) === 0) {
    @unlink($destino);
    echo json_encode(['ok' => false, 'error' => 'No se pudo guardar la imagen.']);
    exit;
}

echo json_encode([
    'ok'     => true,
    'imagen' => 'uploads/iconos/' . $nombre,
    'ancho'  => $ancho,
    'alto'   => $alto
]);
