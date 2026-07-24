<?php
include("./../data/conexion.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id'] ?? 0);
    $nombre = trim($_POST['nombre'] ?? '');
    $contenido = $_POST['contenido'] ?? '';
    
    $meta_json = null;
    if (isset($_POST['meta_json_raw']) && $_POST['meta_json_raw'] !== '') {
        $meta_json = $_POST['meta_json_raw'];
    }

    if ($id > 0) {
        if ($meta_json !== null) {
            $stmt = $con->prepare("UPDATE paginas SET nombre_pag=?, contenido_pag=?, meta_json=? WHERE id_pag=?");
            $stmt->bind_param("sssi", $nombre, $contenido, $meta_json, $id);
        } else {
            $stmt = $con->prepare("UPDATE paginas SET nombre_pag=?, contenido_pag=? WHERE id_pag=?");
            $stmt->bind_param("ssi", $nombre, $contenido, $id);
        }
        if ($stmt->execute()) {
            header("Location: ./../views/paginas.php?msg=actualizado");
            exit();
        } else {
            echo "Error al actualizar: " . $stmt->error;
        }
    }
}