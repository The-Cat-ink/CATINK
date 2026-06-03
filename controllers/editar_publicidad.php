<?php
session_start();
include("./aclcontroller.php");
proteger('publicidad','editar');
// ============================
// FUNCION GUARDAR IMAGEN BASE64 WEBP
// ============================
function guardarPublicidadBase64Webp($base64, $publicidadId, $calidad = 95) {
    if (empty($base64)) return null;

    if (!preg_match('/^data:image\/(jpeg|jpg|png|webp|gif);base64,/', $base64, $matches)) {
        error_log("PUBLICIDAD UPDATE: regex no coincide, inicio=" . substr($base64, 0, 50));
        return null;
    }
    
    $tipo = $matches[1];
    $binario = base64_decode(preg_replace('/^data:image\/\w+;base64,/', '', $base64));
    if ($binario === false || empty($binario)) {
        error_log("PUBLICIDAD UPDATE: base64_decode falló");
        return null;
    }
    
    $dirUploads = dirname(dirname(__DIR__)) . "/uploads/publicidad/";
    
    if (!is_dir($dirUploads)) {
        if (!mkdir($dirUploads, 0755, true)) {
            error_log("PUBLICIDAD UPDATE: No se pudo crear directorio: $dirUploads");
            return null;
        }
    }
    
    if (!is_writable($dirUploads)) {
        error_log("PUBLICIDAD UPDATE: Directorio no escribible: $dirUploads");
        return null;
    }
    
    $timestamp = time();
    $extension = strtolower($tipo);
    if ($extension === 'jpg') $extension = 'jpeg';
    
    $nombre = "pub_{$publicidadId}_{$timestamp}.{$extension}";
    $rutaFisica = $dirUploads . $nombre;
    
    $bytesEscritos = file_put_contents($rutaFisica, $binario);
    if ($bytesEscritos === false) {
        error_log("PUBLICIDAD UPDATE: Error escribiendo archivo: $rutaFisica");
        return null;
    }
    
    if (!file_exists($rutaFisica)) {
        error_log("PUBLICIDAD UPDATE: Archivo no existe después de guardarlo: $rutaFisica");
        return null;
    }
    
    if (filesize($rutaFisica) === 0) {
        error_log("PUBLICIDAD UPDATE: Archivo vacío: $rutaFisica");
        unlink($rutaFisica);
        return null;
    }
    
    return "uploads/publicidad/" . $nombre;
}

// ============================
// CONEXION
// ============================
include("../data/conexion.php");

// ============================
// DATOS FORMULARIO
// ============================
$id_pub = $_POST['id_pub'] ?? null;
$titulo = $_POST['Titulo'] ?? '';
$tipo = $_POST['tipo'] ?? 1; // 1 banner, 2 cuadro
$url = $_POST['url'] ?? '';
$estado = $_POST['estado'] ?? 1;
$fechaInicio = $_POST['fechaInicio'] ?? null;
$fechaFin = $_POST['fechaFin'] ?? null;
$categorias = $_POST['Categorias'] ?? [];
$imagenCrop = $_POST['imagenCrop'] ?? null;

// ============================
// VALIDACION
// ============================
if (empty($id_pub) || empty($titulo) || empty($url)) {
    die("Datos incompletos");
}

// ============================
// ACTUALIZAR DATOS TEXTO
// ============================
$sql = "UPDATE publicidad SET titulo=?, tipo=?, url=?, activo=?, fecha_inicio=?, fecha_fin=? WHERE id_pub=?";
$stmt = $con->prepare($sql);
$stmt->bind_param("sisissi", $titulo, $tipo, $url, $estado, $fechaInicio, $fechaFin, $id_pub);
$stmt->execute();

// ============================
// ACTUALIZAR IMAGEN (SI SE ENVIO UNA NUEVA)
// ============================
$imagenFinal = null;

// Si hay base64 del crop, usarlo
if (!empty($imagenCrop)) {
    $imagenFinal = guardarPublicidadBase64Webp($imagenCrop, $id_pub);
}
// Si no hay crop pero hay archivo, guardar el archivo directamente
elseif (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
    $dirUploads = dirname(dirname(__DIR__)) . "/uploads/publicidad/";
    
    if (!is_dir($dirUploads)) {
        mkdir($dirUploads, 0755, true);
    }
    
    $timestamp = time();
    $extension = pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION);
    $nombre = "pub_{$id_pub}_{$timestamp}." . strtolower($extension);
    $rutaFisica = $dirUploads . $nombre;
    
    if (move_uploaded_file($_FILES['imagen']['tmp_name'], $rutaFisica)) {
        $imagenFinal = "uploads/publicidad/" . $nombre;
    }
}

if ($imagenFinal) {
    // Primero obtenemos la imagen anterior para borrarla (opcional, buena práctica)
    $stmtImg = $con->prepare("SELECT imagen FROM publicidad WHERE id_pub = ?");
    $stmtImg->bind_param("i", $id_pub);
    $stmtImg->execute();
    $resImg = $stmtImg->get_result();
    $rowImg = $resImg->fetch_assoc();
    
    // Actualizar en BD
    $update = $con->prepare("UPDATE publicidad SET imagen=? WHERE id_pub=?");
    $update->bind_param("si", $imagenFinal, $id_pub);
    $update->execute();
    
    // Borrar imagen vieja del servidor si existe y es diferente
    if ($rowImg && !empty($rowImg['imagen'])) {
        $rutaVieja = dirname(dirname(__DIR__)) . "/" . $rowImg['imagen'];
        if (file_exists($rutaVieja)) {
            unlink($rutaVieja);
        }
    }
}

// ============================
// ACTUALIZAR CATEGORIAS
// ============================
// Primero eliminamos las relaciones existentes
$stmtDel = $con->prepare("DELETE FROM publicidad_categoria WHERE publicidad_id = ?");
$stmtDel->bind_param("i", $id_pub);
$stmtDel->execute();

// Luego insertamos las seleccionadas
if (!empty($categorias)) {
    $stmtCat = $con->prepare("INSERT INTO publicidad_categoria (publicidad_id, categoria_id) VALUES (?, ?)");
    foreach ($categorias as $cat) {
        $stmtCat->bind_param("ii", $id_pub, $cat);
        $stmtCat->execute();
    }
}

// ============================
// REDIRECCION
// ============================
header("Location: ./../views/publicidad.php");
exit;
