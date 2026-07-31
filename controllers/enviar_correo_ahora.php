<?php
session_start();
include("./aclcontroller.php");
proteger('correos','editar', false);
include("../data/conexion.php");

header('Content-Type: application/json; charset=utf-8');

$id = (int)($_POST['id'] ?? ($_GET['id'] ?? 0));
if ($id <= 0) {
    echo json_encode(['ok' => false, 'msg' => 'ID de correo inválido']);
    exit;
}

// 1. Marcar fecha de envío como la hora actual y asegurar estado enviado = 0
$stmt = $con->prepare("UPDATE correos_publicitarios SET envio = NOW(), enviado = 0 WHERE id_correo = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

// 2. Ejecutar de inmediato el procesador de envío de correo
ob_start();
require_once(__DIR__ . "/../views/email/correoPublicidad.php");
$output = ob_get_clean();

// 3. Verificar resultado en la BD
$stmtCheck = $con->prepare("SELECT enviado, fecha_enviado FROM correos_publicitarios WHERE id_correo = ?");
$stmtCheck->bind_param("i", $id);
$stmtCheck->execute();
$row = $stmtCheck->get_result()->fetch_assoc();

if (($row['enviado'] ?? 0) == 1) {
    echo json_encode(['ok' => true, 'msg' => '¡Correo enviado inmediatamente a todos los suscriptores!']);
} else {
    echo json_encode([
        'ok' => false, 
        'msg' => 'El correo fue programado de inmediato, pero revisa la configuración SMTP si hubo un retraso de red.',
        'log' => trim($output)
    ]);
}
