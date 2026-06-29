<?php
/**
 * MIGRACIÓN ÚNICA: convierte las imágenes base64 incrustadas en el contenido
 * de las noticias a archivos físicos en uploads/noticias/, reemplazando el
 * data:URI por una URL servible. Así dejan de pesar varios MB y la edición
 * deja de congelarse.
 *
 * Ejecutar desde el navegador (en producción) estando logueado como superadmin:
 *   https://catink.com.mx/controllers/migrar_imagenes_contenido.php
 *
 * Es idempotente: una nota ya migrada (sin base64) se omite. Puede ejecutarse
 * varias veces sin duplicar archivos de las notas ya procesadas.
 */
include(__DIR__ . "/aclcontroller.php");
include(__DIR__ . "/../data/conexion.php");
require_once(__DIR__ . "/../views/helpers/urlhelper.php");

if (!($_SESSION['superadmin'] ?? false)) {
    http_response_code(403);
    exit("Acceso denegado: se requiere superadmin.");
}

header("Content-Type: text/plain; charset=utf-8");

// Carpeta destino (misma lógica que actualizar_noticia.php / subir_imagen_contenido.php)
$isLocal = strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false
        || strpos($_SERVER['HTTP_HOST'] ?? '', 'catink.test') !== false;
$dirUploads = ($isLocal ? dirname(__DIR__) : dirname(dirname(__DIR__))) . "/uploads/noticias/";

if (!is_dir($dirUploads) && !mkdir($dirUploads, 0755, true)) {
    exit("ERROR: no se pudo crear la carpeta $dirUploads");
}
if (!is_writable($dirUploads)) {
    exit("ERROR: la carpeta $dirUploads no tiene permisos de escritura");
}

$extPorMime = [
    'jpeg' => 'jpg', 'jpg' => 'jpg', 'png' => 'png', 'webp' => 'webp', 'gif' => 'gif',
];

$res = $con->query("SELECT id, contenido FROM noticias WHERE contenido LIKE '%data:image/%base64,%'");
if (!$res) {
    exit("ERROR consultando noticias: " . $con->error);
}

echo "Notas con imágenes base64 a migrar: " . $res->num_rows . "\n\n";
$totalImgs = 0;
$totalNotas = 0;

while ($fila = $res->fetch_assoc()) {
    $id = (int)$fila['id'];
    $contenido = $fila['contenido'];
    $contadorNota = 0;

    $nuevoContenido = preg_replace_callback(
        '/data:image\/(jpeg|jpg|png|webp|gif);base64,([A-Za-z0-9+\/=\s]+?)(?=["\')\s])/',
        function ($m) use (&$contadorNota, &$totalImgs, $id, $dirUploads, $extPorMime) {
            $tipo = strtolower($m[1]);
            $binario = base64_decode(preg_replace('/\s+/', '', $m[2]), true);
            if ($binario === false || $binario === '') {
                return $m[0]; // dejar intacto si no decodifica
            }
            $ext = $extPorMime[$tipo] ?? 'png';
            $nombre = "contenido_{$id}_" . time() . "_" . bin2hex(random_bytes(4)) . "." . $ext;
            if (file_put_contents($dirUploads . $nombre, $binario) === false) {
                return $m[0];
            }
            $contadorNota++;
            $totalImgs++;
            return imageUrl("uploads/noticias/" . $nombre);
        },
        $contenido
    );

    if ($contadorNota > 0 && $nuevoContenido !== null && $nuevoContenido !== $contenido) {
        $upd = $con->prepare("UPDATE noticias SET contenido = ? WHERE id = ?");
        $upd->bind_param("si", $nuevoContenido, $id);
        if ($upd->execute()) {
            $totalNotas++;
            echo "Nota #$id: $contadorNota imágenes migradas.\n";
        } else {
            echo "Nota #$id: ERROR al actualizar: " . $con->error . "\n";
        }
    }
}

echo "\nLISTO. Notas actualizadas: $totalNotas | Imágenes migradas: $totalImgs\n";
