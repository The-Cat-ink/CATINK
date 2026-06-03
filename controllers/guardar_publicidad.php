<?php
session_start();
include("./aclcontroller.php");
proteger('publicidad','crear');
// ============================
// FUNCION GUARDAR IMAGEN BASE64 WEBP
// ============================
function guardarPublicidadBase64Webp($base64, $publicidadId, $calidad = 95) {
    if (empty($base64)) return null;

    if (!preg_match('/^data:image\/(jpeg|jpg|png|webp|gif);base64,/', $base64, $matches)) {
        error_log("PUBLICIDAD: regex no coincide, inicio=" . substr($base64, 0, 50));
        return null;
    }
    
    $tipo = $matches[1];
    $binario = base64_decode(preg_replace('/^data:image\/\w+;base64,/', '', $base64));
    if ($binario === false || empty($binario)) {
        error_log("PUBLICIDAD: base64_decode falló");
        return null;
    }
    
    $dirUploads = dirname(dirname(__DIR__)) . "/uploads/publicidad/";
    
    if (!is_dir($dirUploads)) {
        if (!mkdir($dirUploads, 0755, true)) {
            error_log("PUBLICIDAD: No se pudo crear directorio: $dirUploads");
            return null;
        }
    }
    
    if (!is_writable($dirUploads)) {
        error_log("PUBLICIDAD: Directorio no escribible: $dirUploads");
        return null;
    }
    
    $timestamp = time();
    $extension = strtolower($tipo);
    if ($extension === 'jpg') $extension = 'jpeg';
    
    $nombre = "pub_{$publicidadId}_{$timestamp}.{$extension}";
    $rutaFisica = $dirUploads . $nombre;
    
    $bytesEscritos = file_put_contents($rutaFisica, $binario);
    if ($bytesEscritos === false) {
        error_log("PUBLICIDAD: Error escribiendo archivo: $rutaFisica");
        return null;
    }
    
    if (!file_exists($rutaFisica)) {
        error_log("PUBLICIDAD: Archivo no existe después de guardarlo: $rutaFisica");
        return null;
    }
    
    if (filesize($rutaFisica) === 0) {
        error_log("PUBLICIDAD: Archivo vacío: $rutaFisica");
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
$titulo = $_POST['Titulo'] ?? '';
$tipo = $_POST['tipo'] ?? 1; // 1 banner, 2 cuadro
$url = $_POST['url'] ?? '';
$estado = $_POST['estado'] ?? 1;
$fechaInicio = $_POST['fechaInicio'] ?? null;
$fechaFin = $_POST['fechaFin'] ?? null;
$categorias = $_POST['Categorias'] ?? [];
// ============================
// VALIDACION
// ============================
if (empty($titulo) || empty($url)) {
    die("Datos incompletos");
}
// ============================
// INSERTAR PUBLICIDAD
// ============================
$sql = "INSERT INTO publicidad (titulo, imagen, tipo, url, activo, fecha_inicio, fecha_fin)
        VALUES (?, '', ?, ?, ?, ?, ?)";
$stmt = $con->prepare($sql);
$stmt->bind_param("sisiss", $titulo, $tipo, $url, $estado, $fechaInicio, $fechaFin);
$stmt->execute();
$publicidadId = $con->insert_id;
// ============================
// GUARDAR IMAGEN CROP
// ============================
$imagenFinal = guardarPublicidadBase64Webp($_POST['imagenCrop'] ?? null, $publicidadId);
// ============================
// ACTUALIZAR IMAGEN EN BD
// ============================
if ($imagenFinal) {
    $update = $con->prepare("UPDATE publicidad SET imagen=? WHERE id_pub=?");
    $update->bind_param("si", $imagenFinal, $publicidadId);
    $update->execute();
}
// ============================
// INSERTAR CATEGORIAS
// ============================
if (!empty($categorias)) {
    $stmtCat = $con->prepare("INSERT INTO publicidad_categoria (publicidad_id, categoria_id) VALUES (?, ?)");

    foreach ($categorias as $cat) {
        $stmtCat->bind_param("ii", $publicidadId, $cat);
        $stmtCat->execute();
    }
}
// ============================
// REDIRECCION
// ============================
header("Location: ./../views/publicidad.php");
exit;