<?php
/**
 * Sube una imagen del CONTENIDO del editor (CKEditor) como archivo físico
 * y devuelve su URL. Reemplaza el guardado base64 inline que hacía el HTML
 * de la nota pesar varios MB y congelaba la edición.
 *
 * Responde JSON: { "url": "..." }  o  { "error": "..." }
 */
header("Content-Type: application/json");

include(__DIR__ . "/aclcontroller.php");
require_once(__DIR__ . "/../views/helpers/urlhelper.php");

// ── Permisos: debe poder crear o editar noticias ──
$autorizado = ($_SESSION['superadmin'] ?? false)
    || tienePermiso('noticias', 'crear')
    || tienePermiso('noticias', 'editar');
if (!$autorizado) {
    http_response_code(403);
    echo json_encode(["error" => "Acceso denegado"]);
    exit;
}

// Soltar el candado de la sesión en cuanto sabemos que hay permiso. PHP guarda
// la sesión en un archivo con bloqueo exclusivo, así que mientras esta petición
// no lo suelte, CUALQUIER otra del mismo usuario se queda esperando. CKEditor
// dispara una subida por imagen en paralelo: al arrastrar 100 imágenes, las 100
// se formaban en fila de una en una y las últimas caducaban por timeout. Ya no
// escribimos nada en $_SESSION de aquí en adelante, así que cerrarla es seguro.
session_write_close();

// ── Validar archivo recibido ──
if (!isset($_FILES['imagen']) || $_FILES['imagen']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(["error" => "No se recibió ninguna imagen válida"]);
    exit;
}

$file = $_FILES['imagen'];

// Límite de tamaño (8 MB)
if ($file['size'] > 8 * 1024 * 1024) {
    http_response_code(413);
    echo json_encode(["error" => "La imagen supera el tamaño máximo de 8 MB"]);
    exit;
}

// Validar tipo real por contenido
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime  = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

$extensionesValidas = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
    'image/gif'  => 'gif',
];
if (!isset($extensionesValidas[$mime])) {
    http_response_code(415);
    echo json_encode(["error" => "Formato de imagen no permitido"]);
    exit;
}
$extension = $extensionesValidas[$mime];

// ── Carpeta de destino (misma lógica que actualizar_noticia.php) ──
$isLocal = strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false
        || strpos($_SERVER['HTTP_HOST'] ?? '', 'catink.test') !== false;
$dirUploads = ($isLocal ? dirname(__DIR__) : dirname(dirname(__DIR__))) . "/uploads/noticias/";

if (!is_dir($dirUploads)) {
    if (!mkdir($dirUploads, 0755, true)) {
        http_response_code(500);
        echo json_encode(["error" => "No se pudo crear la carpeta de destino"]);
        exit;
    }
}
if (!is_writable($dirUploads)) {
    http_response_code(500);
    echo json_encode(["error" => "La carpeta de destino no tiene permisos de escritura"]);
    exit;
}

// ── Guardar archivo con nombre único ──
$nombre = "contenido_" . time() . "_" . bin2hex(random_bytes(4)) . "." . $extension;
$rutaFisica = $dirUploads . $nombre;

if (!move_uploaded_file($file['tmp_name'], $rutaFisica)) {
    http_response_code(500);
    echo json_encode(["error" => "No se pudo guardar la imagen"]);
    exit;
}

// ── Devolver URL servible ──
$rutaBD = "uploads/noticias/" . $nombre;
echo json_encode(["url" => imageUrl($rutaBD)]);
exit;
