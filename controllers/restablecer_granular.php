<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once(__DIR__ . '/../data/conexion.php');
require_once(__DIR__ . '/../views/helpers/activity_log.php');

// 1. Validar que el usuario sea Administrador
$tipo = $_SESSION['tipo'] ?? '';
if ($tipo !== 'admin') {
    header('Location: ../views/admin.php');
    exit;
}

// 2. Obtener y validar parámetros
$fecha_inicio = trim($_POST['fecha_inicio'] ?? '');
$fecha_fin = trim($_POST['fecha_fin'] ?? '');
$confirmacion = trim($_POST['confirmacion'] ?? '');
$modulos = $_POST['modulos'] ?? [];

if (empty($fecha_inicio) || empty($fecha_fin) || $confirmacion !== 'RESTABLECER' || empty($modulos)) {
    header('Location: ../views/admin.php?restablecer_error=1');
    exit;
}

// Formatear fechas para cubrir el día entero
$startDateTime = $fecha_inicio . ' 00:00:00';
$endDateTime = $fecha_fin . ' 23:59:59';

$con->begin_transaction();

try {
    // Desactivar llaves foráneas temporalmente
    $con->query("SET FOREIGN_KEY_CHECKS = 0");

    $modulosBorrados = [];

    // MÓDULO: Noticias
    if (in_array('noticias', $modulos)) {
        // Likes de comentarios asociados a noticias en el rango
        $stmt = $con->prepare("DELETE FROM likes_comentarios WHERE comentario_id IN (SELECT id_comentario FROM comentarios WHERE noticia_id IN (SELECT id FROM noticias WHERE fecha_publicacion BETWEEN ? AND ?))");
        $stmt->bind_param("ss", $startDateTime, $endDateTime);
        $stmt->execute();
        $stmt->close();

        // Reportes de comentarios asociados a noticias en el rango
        $stmt = $con->prepare("DELETE FROM reportes_comentarios WHERE comentario_id IN (SELECT id_comentario FROM comentarios WHERE noticia_id IN (SELECT id FROM noticias WHERE fecha_publicacion BETWEEN ? AND ?))");
        $stmt->bind_param("ss", $startDateTime, $endDateTime);
        $stmt->execute();
        $stmt->close();

        // Historial de ediciones de comentarios asociados a noticias en el rango
        $stmt = $con->prepare("DELETE FROM historial_ediciones WHERE comentario_id IN (SELECT id_comentario FROM comentarios WHERE noticia_id IN (SELECT id FROM noticias WHERE fecha_publicacion BETWEEN ? AND ?))");
        $stmt->bind_param("ss", $startDateTime, $endDateTime);
        $stmt->execute();
        $stmt->close();

        // Comentarios asociados a noticias en el rango
        $stmt = $con->prepare("DELETE FROM comentarios WHERE noticia_id IN (SELECT id FROM noticias WHERE fecha_publicacion BETWEEN ? AND ?)");
        $stmt->bind_param("ss", $startDateTime, $endDateTime);
        $stmt->execute();
        $stmt->close();

        // Configuración de comentarios de noticias en el rango
        $stmt = $con->prepare("DELETE FROM config_comentarios WHERE noticia_id IN (SELECT id FROM noticias WHERE fecha_publicacion BETWEEN ? AND ?)");
        $stmt->bind_param("ss", $startDateTime, $endDateTime);
        $stmt->execute();
        $stmt->close();

        // Likes de noticias en el rango
        $stmt = $con->prepare("DELETE FROM noticia_likes WHERE noticia_id IN (SELECT id FROM noticias WHERE fecha_publicacion BETWEEN ? AND ?)");
        $stmt->bind_param("ss", $startDateTime, $endDateTime);
        $stmt->execute();
        $stmt->close();

        // Estadísticas de noticias en el rango
        $stmt = $con->prepare("DELETE FROM noticias_stats WHERE noticia_id IN (SELECT id FROM noticias WHERE fecha_publicacion BETWEEN ? AND ?)");
        $stmt->bind_param("ss", $startDateTime, $endDateTime);
        $stmt->execute();
        $stmt->close();

        // Relaciones de categorías de noticias en el rango
        $stmt = $con->prepare("DELETE FROM noticia_categoria WHERE noticia_id IN (SELECT id FROM noticias WHERE fecha_publicacion BETWEEN ? AND ?)");
        $stmt->bind_param("ss", $startDateTime, $endDateTime);
        $stmt->execute();
        $stmt->close();

        // Recomendaciones de noticias en el rango
        $stmt = $con->prepare("DELETE FROM recomendados WHERE noticia_id IN (SELECT id FROM noticias WHERE fecha_publicacion BETWEEN ? AND ?)");
        $stmt->bind_param("ss", $startDateTime, $endDateTime);
        $stmt->execute();
        $stmt->close();

        // Finalmente, noticias en el rango
        $stmt = $con->prepare("DELETE FROM noticias WHERE fecha_publicacion BETWEEN ? AND ?");
        $stmt->bind_param("ss", $startDateTime, $endDateTime);
        $stmt->execute();
        $stmt->close();

        $modulosBorrados[] = 'Noticias';
    }

    // MÓDULO: Comentarios
    if (in_array('comentarios', $modulos)) {
        // Likes de comentarios en el rango
        $stmt = $con->prepare("DELETE FROM likes_comentarios WHERE comentario_id IN (SELECT id_comentario FROM comentarios WHERE fecha_publicacion BETWEEN ? AND ?)");
        $stmt->bind_param("ss", $startDateTime, $endDateTime);
        $stmt->execute();
        $stmt->close();

        // Reportes de comentarios en el rango
        $stmt = $con->prepare("DELETE FROM reportes_comentarios WHERE comentario_id IN (SELECT id_comentario FROM comentarios WHERE fecha_publicacion BETWEEN ? AND ?)");
        $stmt->bind_param("ss", $startDateTime, $endDateTime);
        $stmt->execute();
        $stmt->close();

        // Historial de ediciones de comentarios en el rango
        $stmt = $con->prepare("DELETE FROM historial_ediciones WHERE comentario_id IN (SELECT id_comentario FROM comentarios WHERE fecha_publicacion BETWEEN ? AND ?)");
        $stmt->bind_param("ss", $startDateTime, $endDateTime);
        $stmt->execute();
        $stmt->close();

        // Comentarios en el rango
        $stmt = $con->prepare("DELETE FROM comentarios WHERE fecha_publicacion BETWEEN ? AND ?");
        $stmt->bind_param("ss", $startDateTime, $endDateTime);
        $stmt->execute();
        $stmt->close();

        $modulosBorrados[] = 'Comentarios';
    }

    // MÓDULO: Suscripciones
    if (in_array('suscripciones', $modulos)) {
        $stmt = $con->prepare("DELETE FROM suscripciones WHERE fecha BETWEEN ? AND ?");
        $stmt->bind_param("ss", $startDateTime, $endDateTime);
        $stmt->execute();
        $stmt->close();

        $modulosBorrados[] = 'Suscripciones';
    }

    // MÓDULO: Lectores
    if (in_array('lectores', $modulos)) {
        // Notificaciones asociadas a lectores creados en el rango
        $stmt = $con->prepare("DELETE FROM notificaciones WHERE tipo_usuario = 'lector' AND user_id IN (SELECT id FROM lectores WHERE creado BETWEEN ? AND ?)");
        $stmt->bind_param("ss", $startDateTime, $endDateTime);
        $stmt->execute();
        $stmt->close();

        // Tokens de password reset asociados a lectores creados en el rango
        $stmt = $con->prepare("DELETE FROM password_reset_tokens WHERE tipo_usuario = 'lector' AND email IN (SELECT correo FROM lectores WHERE creado BETWEEN ? AND ?)");
        $stmt->bind_param("ss", $startDateTime, $endDateTime);
        $stmt->execute();
        $stmt->close();

        // Likes de comentarios asociados a lectores creados en el rango
        $stmt = $con->prepare("DELETE FROM likes_comentarios WHERE lector_id IN (SELECT id FROM lectores WHERE creado BETWEEN ? AND ?)");
        $stmt->bind_param("ss", $startDateTime, $endDateTime);
        $stmt->execute();
        $stmt->close();

        // Reportes de comentarios asociados a lectores creados en el rango
        $stmt = $con->prepare("DELETE FROM reportes_comentarios WHERE lector_id IN (SELECT id FROM lectores WHERE creado BETWEEN ? AND ?)");
        $stmt->bind_param("ss", $startDateTime, $endDateTime);
        $stmt->execute();
        $stmt->close();

        // Comentarios hechos por lectores creados en el rango
        $stmt = $con->prepare("DELETE FROM comentarios WHERE lector_id IN (SELECT id FROM lectores WHERE creado BETWEEN ? AND ?)");
        $stmt->bind_param("ss", $startDateTime, $endDateTime);
        $stmt->execute();
        $stmt->close();

        // Finalmente, lectores registrados en el rango
        $stmt = $con->prepare("DELETE FROM lectores WHERE creado BETWEEN ? AND ?");
        $stmt->bind_param("ss", $startDateTime, $endDateTime);
        $stmt->execute();
        $stmt->close();

        $modulosBorrados[] = 'Lectores';
    }

    // MÓDULO: Notificaciones
    if (in_array('notificaciones', $modulos)) {
        $stmt = $con->prepare("DELETE FROM notificaciones WHERE creada BETWEEN ? AND ?");
        $stmt->bind_param("ss", $startDateTime, $endDateTime);
        $stmt->execute();
        $stmt->close();

        $modulosBorrados[] = 'Notificaciones';
    }

    // MÓDULO: Bitácora de Actividades (activity_log)
    if (in_array('actividades', $modulos)) {
        $stmt = $con->prepare("DELETE FROM activity_log WHERE created_at BETWEEN ? AND ?");
        $stmt->bind_param("ss", $startDateTime, $endDateTime);
        $stmt->execute();
        $stmt->close();

        $modulosBorrados[] = 'Bitácora';
    }

    // Reactivar llaves foráneas
    $con->query("SET FOREIGN_KEY_CHECKS = 1");

    $con->commit();

    // Loguear la actividad en la bitácora si no se borró la bitácora entera o se seleccionó
    $descripcionLog = 'Restablecimiento granular. Rango: ' . $fecha_inicio . ' a ' . $fecha_fin . '. Módulos: ' . implode(', ', $modulosBorrados);
    logActivity($con, 'restablecer', 'sistema', $descripcionLog);

    header('Location: ../views/admin.php?restablecido=1&modulos_borrados=' . urlencode(implode(', ', $modulosBorrados)));
    exit;

} catch (\Throwable $e) {
    $con->query("SET FOREIGN_KEY_CHECKS = 1");
    $con->rollback();
    error_log("Error en restablecimiento granular: " . $e->getMessage());
    header('Location: ../views/admin.php?restablecer_error=2');
    exit;
}
