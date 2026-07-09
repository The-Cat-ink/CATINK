<?php
ob_start();
header('Content-Type: application/json');
session_start();
include("./aclcontroller.php");
proteger('noticias','leer');

$q = isset($_GET['q']) ? trim($_GET['q']) : '';

include("./../data/conexion.php");
require_once("./../views/helpers/urlhelper.php");

try {
    if (empty($q)) {
        $stmt = $con->prepare("
            SELECT id, titulo, crop3, fecha_publicacion
            FROM noticias
            WHERE eliminado_en IS NULL AND fecha_publicacion <= NOW()
              AND id NOT IN (SELECT noticia_id FROM recomendados)
            ORDER BY fecha_publicacion DESC
            LIMIT 10
        ");
    } else {
        $like = "%$q%";
        $stmt = $con->prepare("
            SELECT id, titulo, crop3, fecha_publicacion
            FROM noticias
            WHERE titulo LIKE ?
              AND eliminado_en IS NULL
              AND fecha_publicacion <= NOW()
              AND id NOT IN (SELECT noticia_id FROM recomendados)
            ORDER BY fecha_publicacion DESC
            LIMIT 10
        ");
        $stmt->bind_param("s", $like);
    }

    if ($stmt->execute()) {
        $result = $stmt->get_result();
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = [
                "id" => $row['id'],
                "titulo" => $row['titulo'],
                "imagen" => imageUrl($row['crop3']),
                "fecha" => date('d/m/Y H:i', strtotime($row['fecha_publicacion']))
            ];
        }
        echo json_encode(['success' => true, 'noticias' => $data]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Error al buscar noticias']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
exit;
?>
