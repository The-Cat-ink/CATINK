<?php
require_once(__DIR__ . '/aclcontroller.php');
proteger('usuarios', 'editar', true);
require_once(__DIR__ . '/../data/conexion.php');

header('Content-Type: application/json');

// Validar entrada
$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    echo json_encode(['error' => 'ID de lector no válido']);
    exit;
}

// 1. Obtener datos del lector
$stmt = $con->prepare("SELECT * FROM lectores WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$lector = $stmt->get_result()->fetch_assoc();

if (!$lector) {
    echo json_encode(['error' => 'Lector no encontrado']);
    exit;
}

// 2. Validar que el usuario o correo no esté duplicado en usuarios (admins)
$stmt = $con->prepare("SELECT id_u FROM usuarios WHERE usuario = ? OR correo = ?");
$stmt->bind_param("ss", $lector['usuario'], $lector['correo']);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    echo json_encode(['error' => 'El nombre de usuario o correo ya existe como administrador']);
    exit;
}

// 3. Promover mediante transacción transaccional segura
$con->begin_transaction();
try {
    // Permisos por defecto:
    // perm_noticias = 15 (Lectura/Escritura total: 1 + 2 + 4 + 8)
    // perm_categorias = 2 (Lectura: 2)
    // Otros = 0
    $perm_noticias = 15;
    $perm_categorias = 2;
    $perm_publicidad = 0;
    $perm_suscripciones = 0;
    $perm_usuarios = 0;
    $perm_correos = 0;
    $perm_videos = 0;

    $ins = $con->prepare("
        INSERT INTO usuarios 
        (nombre, usuario, correo, pass, avatar_id, sexo, fecha_nacimiento, entidad, perm_publicidad, perm_noticias, perm_categorias, perm_suscripciones, perm_usuarios, perm_correos, perm_videos)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $ins->bind_param(
        "ssssisssiiiiiii",
        $lector['nombre'],
        $lector['usuario'],
        $lector['correo'],
        $lector['password_hash'],
        $lector['avatar_id'],
        $lector['sexo'],
        $lector['fecha_nacimiento'],
        $lector['entidad'],
        $perm_publicidad,
        $perm_noticias,
        $perm_categorias,
        $perm_suscripciones,
        $perm_usuarios,
        $perm_correos,
        $perm_videos
    );
    $ins->execute();
    $new_admin_id = $con->insert_id;

    // Migrar comentarios
    $updComm = $con->prepare("UPDATE comentarios SET usuario_id = ?, lector_id = NULL WHERE lector_id = ?");
    $updComm->bind_param("ii", $new_admin_id, $id);
    $updComm->execute();

    // Eliminar likes y reportes (ya que son exclusivos de la tabla lectores)
    $delLikes = $con->prepare("DELETE FROM likes_comentarios WHERE lector_id = ?");
    $delLikes->bind_param("i", $id);
    $delLikes->execute();

    $delRep = $con->prepare("DELETE FROM reportes_comentarios WHERE lector_id = ?");
    $delRep->bind_param("i", $id);
    $delRep->execute();

    // Migrar notificaciones
    $updNotif = $con->prepare("UPDATE notificaciones SET tipo_usuario = 'admin', user_id = ? WHERE tipo_usuario = 'lector' AND user_id = ?");
    $updNotif->bind_param("ii", $new_admin_id, $id);
    $updNotif->execute();

    // Eliminar tokens de contraseña para lectores
    $delTokens = $con->prepare("DELETE FROM password_reset_tokens WHERE tipo_usuario = 'lector' AND email = ?");
    $delTokens->bind_param("s", $lector['correo']);
    $delTokens->execute();

    // Eliminar el lector
    $delLec = $con->prepare("DELETE FROM lectores WHERE id = ?");
    $delLec->bind_param("i", $id);
    $delLec->execute();

    $con->commit();
    echo json_encode(['success' => 'Lector promovido a administrador correctamente']);
} catch (Exception $e) {
    $con->rollback();
    echo json_encode(['error' => 'Error al promover lector: ' . $e->getMessage()]);
}
