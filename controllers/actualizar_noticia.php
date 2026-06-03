<?php
session_start();
include("./aclcontroller.php");
proteger('noticias','editar');
include("../data/conexion.php");
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
    $dirUploads = dirname(dirname(__DIR__)) . "/uploads/noticias/";
    
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
// sanitize as above
$contenido = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/i', '', $contenido);
$contenido = preg_replace_callback(
    '/<blockquote[^>]*>.*?<a[^>]+href="([^"]+)"[^>]*><\/a>.*?<\/blockquote>/is',
    function($m){ return '<div class="social-embed" data-url="'.htmlspecialchars($m[1]).'"\></div>'; },
    $contenido
);

$fecha_publicacion = $_POST['fecha_publicacion'] ?? date('Y-m-d H:i:s');
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
error_log("DEBUG ACTUALIZAR: usuario_id = " . ($usuario_id ?? "NULL"));

// Si creado_por es NULL (nota antigua), llenarla con el usuario actual
$checkCreado = $con->prepare("SELECT creado_por FROM noticias WHERE id = ?");
$checkCreado->bind_param("i", $id);
$checkCreado->execute();
$resCreado = $checkCreado->get_result()->fetch_assoc();
$creado_por = $resCreado['creado_por'] ?? $usuario_id;
error_log("DEBUG ACTUALIZAR: creado_por = " . ($creado_por ?? "NULL"));

$update = $con->prepare("
  UPDATE noticias
  SET titulo = ?, descripcion = ?, contenido = ?, fecha_publicacion = ?, creado_por = ?, editado_por = ?
  WHERE id = ?
");
$update->bind_param("sssssii", $titulo, $descripcion, $contenido, $fecha_publicacion, $creado_por, $usuario_id, $id);
$result = $update->execute();
error_log("DEBUG ACTUALIZAR: UPDATE ejecutado, resultado = " . ($result ? "OK" : "FAIL"));
// ============================
// OBTENER IMAGENES ACTUALES
// ============================
$stmt = $con->prepare("SELECT crop1, crop2, crop3 FROM noticias WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
$actual = $res->fetch_assoc();
$c1 = $actual['crop1'] ?? null;
$c2 = $actual['crop2'] ?? null;
$c3 = $actual['crop3'] ?? null;
// ============================
// NUEVAS IMAGENES
// ============================
$new1 = guardarImagenBase64WebpConId($_POST['crop1'] ?? null, $id, 'crop1');
$new2 = guardarImagenBase64WebpConId($_POST['crop2'] ?? null, $id, 'crop2');
$new3 = guardarImagenBase64WebpConId($_POST['crop3'] ?? null, $id, 'crop3');
if ($new1 || $new2 || $new3) {
  // Eliminar imágenes antiguas si se suben nuevas
  if ($new1 && $c1 && $c1 !== $new1) {
    $rutaVieja = dirname(dirname(__DIR__)) . "/" . $c1;
    if (file_exists($rutaVieja)) {
      unlink($rutaVieja);
    }
  }
  if ($new2 && $c2 && $c2 !== $new2) {
    $rutaVieja = dirname(dirname(__DIR__)) . "/" . $c2;
    if (file_exists($rutaVieja)) {
      unlink($rutaVieja);
    }
  }
  if ($new3 && $c3 && $c3 !== $new3) {
    $rutaVieja = dirname(dirname(__DIR__)) . "/" . $c3;
    if (file_exists($rutaVieja)) {
      unlink($rutaVieja);
    }
  }
  
  $c1 = $new1 ?: $c1;
  $c2 = $new2 ?: $c2;
  $c3 = $new3 ?: $c3;
  $updImgs = $con->prepare("UPDATE noticias SET crop1=?, crop2=?, crop3=? WHERE id=?");
  $updImgs->bind_param("sssi", $c1, $c2, $c3, $id);
  $updImgs->execute();
}
// ============================
// ACTUALIZAR CATEGORIAS (SYNC)
// ============================
// borrar anteriores
$con->query("DELETE FROM noticia_categoria WHERE noticia_id = $id");
// insertar nuevas
if (!empty($categorias)) {
  $stmtCat = $con->prepare("INSERT INTO noticia_categoria (noticia_id, categoria_id) VALUES (?, ?)");
  foreach ($categorias as $cat_id) {
    $stmtCat->bind_param("ii", $id, $cat_id);
    $stmtCat->execute();
  }
}
// ============================
// REDIRECCION
// ============================
header("Location: ./../views/editar.php?id=$id&msg=actualizado");
exit;
?>