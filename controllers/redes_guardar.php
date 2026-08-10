<?php
session_start();
include("./../data/conexion.php");

// Verificar permisos de administrador
$id_u = $_SESSION['id_u'] ?? 0;
if ($id_u <= 0) {
    header("Location: ./../views/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_red = intval($_POST['id_red'] ?? 0);
    $nombre = trim($_POST['nombre'] ?? '');
    $url    = trim($_POST['url'] ?? '');
    $icono  = trim($_POST['icono'] ?? 'bi-link-45deg');
    $color  = trim($_POST['color'] ?? '#EF3363');
    $orden  = intval($_POST['orden'] ?? 0);
    $activo = isset($_POST['activo']) ? 1 : 0;

    if (empty($nombre) || empty($url)) {
        header("Location: ./../views/paginas.php?err=campos_vacios");
        exit();
    }

    $icono_img = null;

    // Procesar archivo de imagen si se subió
    if (isset($_FILES['icono_archivo']) && $_FILES['icono_archivo']['error'] === UPLOAD_ERR_OK) {
        $fileTmp = $_FILES['icono_archivo']['tmp_name'];
        $fileName = $_FILES['icono_archivo']['name'];
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowed = ['png', 'jpg', 'jpeg', 'svg', 'webp'];

        if (in_array($ext, $allowed)) {
            $dir = "./../uploads/sociales/";
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            $newFileName = "social_" . time() . "_" . mt_rand(1000, 9999) . "." . $ext;
            $dest = $dir . $newFileName;
            if (move_uploaded_file($fileTmp, $dest)) {
                $icono_img = "uploads/sociales/" . $newFileName;
            }
        }
    } else if (!empty($_POST['icono_img_url'])) {
        $icono_img = trim($_POST['icono_img_url']);
    }

    if ($id_red > 0) {
        // Actualizar red existente
        if ($icono_img !== null) {
            $stmt = $con->prepare("UPDATE redes_sociales SET nombre=?, url=?, icono=?, icono_img=?, color=?, orden=?, activo=? WHERE id_red=?");
            $stmt->bind_param("sssssiii", $nombre, $url, $icono, $icono_img, $color, $orden, $activo, $id_red);
        } else {
            $stmt = $con->prepare("UPDATE redes_sociales SET nombre=?, url=?, icono=?, color=?, orden=?, activo=? WHERE id_red=?");
            $stmt->bind_param("ssssiii", $nombre, $url, $icono, $color, $orden, $activo, $id_red);
        }
        $stmt->execute();
    } else {
        // Insertar nueva red social
        $stmt = $con->prepare("INSERT INTO redes_sociales (nombre, url, icono, icono_img, color, orden, activo) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssii", $nombre, $url, $icono, $icono_img, $color, $orden, $activo);
        $stmt->execute();
    }

    header("Location: ./../views/paginas.php?msg=red_guardada");
    exit();
}
