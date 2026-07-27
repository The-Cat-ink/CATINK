<?php
ob_start();
header('Content-Type: application/json');
session_start();
include("./aclcontroller.php");
proteger('noticias','editar');

$titulo = isset($_POST['titulo']) ? trim($_POST['titulo']) : '';

if ($titulo === '') {
    echo json_encode(['success' => false, 'error' => 'El título es obligatorio']);
    exit;
}

$base64 = isset($_POST['imagenCrop']) ? $_POST['imagenCrop'] : '';

if ($base64 === '') {
    echo json_encode(['success' => false, 'error' => 'La imagen recortada es obligatoria. Por favor selecciona y confirma el recorte de tu imagen.']);
    exit;
}

function guardarBase64Image($base64, $prefix = 'esp_custom') {
    if (empty($base64)) return null;

    if (!preg_match('/^data:image\/(jpeg|jpg|png|webp|gif);base64,/', $base64, $matches)) {
        return null;
    }
    
    $tipo = $matches[1];
    $binario = base64_decode(preg_replace('/^data:image\/\w+;base64,/', '', $base64));
    if ($binario === false || empty($binario)) {
        return null;
    }
    
    $isLocal = strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false || strpos($_SERVER['HTTP_HOST'] ?? '', 'catink.test') !== false;
    $dirUploads = ($isLocal ? dirname(__DIR__) : dirname(dirname(__DIR__))) . "/uploads/custom_items/";
    
    if (!is_dir($dirUploads)) {
        mkdir($dirUploads, 0755, true);
    }
    
    $timestamp = time();
    $extension = strtolower($tipo);
    if ($extension === 'jpg') $extension = 'jpeg';
    
    $nombre = "{$prefix}_{$timestamp}_" . mt_rand(1000, 9999) . ".{$extension}";
    $rutaFisica = $dirUploads . $nombre;
    
    if (file_put_contents($rutaFisica, $binario) !== false) {
        return "uploads/custom_items/" . $nombre;
    }
    
    return null;
}

$url = isset($_POST['url']) ? trim($_POST['url']) : '';

include("./../data/conexion.php");

try {

    // Verificar límite de 10
    $countRes = $con->query("SELECT COUNT(*) AS total FROM esperamos");
    $countRow = $countRes->fetch_assoc();
    if (intval($countRow['total']) >= 10) {
        echo json_encode(['success' => false, 'error' => 'Se ha alcanzado el límite máximo de 10 esperados']);
        exit;
    }

    // Guardar imagen
    $rutaRelativa = guardarBase64Image($base64);
    if (!$rutaRelativa) {
        echo json_encode(['success' => false, 'error' => 'Error al guardar la imagen recortada']);
        exit;
    }

    // Obtener orden máximo actual
    $ordenRes = $con->query("SELECT COALESCE(MAX(orden), 0) AS max_orden FROM esperamos");
    $ordenRow = $ordenRes->fetch_assoc();
    $nuevoOrden = intval($ordenRow['max_orden']) + 1;

    // Insertar
    $stmtInsert = $con->prepare("INSERT INTO esperamos (noticia_id, titulo, imagen, url, orden) VALUES (NULL, ?, ?, ?, ?)");
    $stmtInsert->bind_param("sssi", $titulo, $rutaRelativa, $url, $nuevoOrden);
    
    if ($stmtInsert->execute()) {
        require_once(__DIR__ . "/../views/helpers/activity_log.php");
        logActivity($con, 'crear', 'esperados', 'Agregó esperado personalizado «' . mb_substr($titulo, 0, 80) . '»');
        echo json_encode([
            'success' => true,
            'id' => $con->insert_id,
            'titulo' => $titulo,
            'imagen' => $rutaRelativa,
            'url' => $url
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Error al guardar en la base de datos']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
exit;
?>
