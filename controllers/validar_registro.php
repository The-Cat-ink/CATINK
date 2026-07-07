<?php
include(__DIR__ . "/../data/conexion.php");
header('Content-Type: application/json');

$usuario = trim($_GET['usuario'] ?? '');
$correo  = trim($_GET['correo'] ?? '');

if ($usuario !== '') {
    // Verificar en lectores
    $stmt = $con->prepare("SELECT id FROM lectores WHERE usuario = ?");
    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        echo json_encode(['exists' => true, 'message' => 'El nombre de usuario ya está en uso.']);
        exit;
    }
    // Verificar en usuarios (admins)
    $stmt = $con->prepare("SELECT id_u FROM usuarios WHERE usuario = ?");
    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        echo json_encode(['exists' => true, 'message' => 'El nombre de usuario ya está en uso.']);
        exit;
    }
    echo json_encode(['exists' => false]);
    exit;
}

if ($correo !== '') {
    // Verificar en lectores
    $stmt = $con->prepare("SELECT id FROM lectores WHERE correo = ?");
    $stmt->bind_param("s", $correo);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        echo json_encode(['exists' => true, 'message' => 'El correo electrónico ya está registrado.']);
        exit;
    }
    // Verificar en usuarios (admins)
    $stmt = $con->prepare("SELECT id_u FROM usuarios WHERE correo = ?");
    $stmt->bind_param("s", $correo);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        echo json_encode(['exists' => true, 'message' => 'El correo electrónico ya está registrado.']);
        exit;
    }
    echo json_encode(['exists' => false]);
    exit;
}

echo json_encode(['error' => 'Parámetros inválidos']);
