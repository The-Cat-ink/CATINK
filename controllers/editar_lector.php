<?php
require_once(__DIR__ . '/aclcontroller.php');
proteger('usuarios', 'editar', true);
require_once(__DIR__ . '/../data/conexion.php');

header('Content-Type: application/json');

$id = (int)($_POST['id'] ?? 0);
$nombre = trim($_POST['nombre'] ?? '');
$usuario = trim($_POST['usuario'] ?? '');
$correo = trim($_POST['correo'] ?? '');

if ($id <= 0 || empty($nombre) || empty($usuario) || empty($correo)) {
    echo json_encode(['error' => 'Todos los campos son obligatorios']);
    exit;
}

// 1. Verificar duplicidad de usuario en lectores
$stmt = $con->prepare("SELECT id FROM lectores WHERE usuario = ? AND id != ?");
$stmt->bind_param("si", $usuario, $id);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    echo json_encode(['error' => 'El nombre de usuario ya está en uso por otro lector']);
    exit;
}

// 2. Verificar duplicidad de correo en lectores
$stmt = $con->prepare("SELECT id FROM lectores WHERE correo = ? AND id != ?");
$stmt->bind_param("si", $correo, $id);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    echo json_encode(['error' => 'El correo electrónico ya está en uso por otro lector']);
    exit;
}

// 3. Verificar duplicidad de usuario o correo en usuarios (admins)
$stmt = $con->prepare("SELECT id_u FROM usuarios WHERE usuario = ? OR correo = ?");
$stmt->bind_param("ss", $usuario, $correo);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    echo json_encode(['error' => 'El nombre de usuario o correo electrónico ya está registrado como administrador']);
    exit;
}

// 4. Actualizar
$stmt = $con->prepare("UPDATE lectores SET nombre = ?, usuario = ?, correo = ? WHERE id = ?");
$stmt->bind_param("sssi", $nombre, $usuario, $correo, $id);

if ($stmt->execute()) {
    require_once(__DIR__ . '/../views/helpers/activity_log.php');
    logActivity($con, 'editar', 'lectores', 'Actualizó datos del lector ID ' . $id . ' («' . $usuario . '»)');
    echo json_encode(['success' => 'Datos del lector actualizados correctamente']);
} else {
    echo json_encode(['error' => 'Error al actualizar los datos del lector']);
}
