<?php
session_start();
header('Content-Type: application/json');

require_once(__DIR__ . "/../data/conexion.php");
require_once(__DIR__ . "/../views/helpers/urlhelper.php");

$email = trim($_GET['email'] ?? $_POST['email'] ?? '');

if (empty($email)) {
    echo json_encode(['verificado' => false, 'error' => 'Email no especificado']);
    exit;
}

$stmt = $con->prepare("SELECT id, usuario, nombre, verificado FROM lectores WHERE correo = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $lector = $result->fetch_assoc();
    
    if ((int)$lector['verificado'] === 1) {
        // Iniciar sesión automáticamente en este navegador
        $_SESSION['usuario'] = $lector['usuario'];
        $_SESSION['tipo'] = 'lector';
        $_SESSION['id_lector'] = $lector['id'];
        $_SESSION['nombre_completo'] = $lector['nombre'];
        $_SESSION['superadmin'] = false;
        $_SESSION['ACL'] = [];
        
        echo json_encode([
            'verificado' => true,
            'redirect' => basePath() . '/views/perfil.php?registro=verificado'
        ]);
        exit;
    }
}

echo json_encode(['verificado' => false]);
exit;
