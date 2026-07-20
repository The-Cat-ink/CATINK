<?php
require_once(__DIR__ . "/data/conexion.php");
require_once(__DIR__ . "/views/helpers/urlhelper.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$token = trim($_GET['token'] ?? '');
$error_msg = null;
$success = false;

if (empty($token)) {
    $error_msg = "El token de verificación no ha sido proporcionado.";
} else {
    // Buscar al lector con este token
    $stmt = $con->prepare("SELECT id, nombre, verificado FROM lectores WHERE token_verificacion = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $lector = $result->fetch_assoc();
        
        $stmtUpd = $con->prepare("UPDATE lectores SET verificado = 1, token_verificacion = NULL WHERE id = ?");
        $stmtUpd->bind_param("i", $lector['id']);
        if ($stmtUpd->execute()) {
            $success = true;
            // Iniciar sesión del lector
            $_SESSION['usuario'] = $lector['usuario'];
            $_SESSION['tipo'] = 'lector';
            $_SESSION['id_lector'] = $lector['id'];
            $_SESSION['nombre_completo'] = $lector['nombre'];
            $_SESSION['superadmin'] = false;
            $_SESSION['ACL'] = [];
            
            // Redirigir directamente a su perfil
            header('Location: ' . basePath() . '/views/perfil.php?registro=verificado');
            exit();
        } else {
            $error_msg = "Ocurrió un error al procesar la verificación. Por favor, intenta de nuevo.";
        }
    } else {
        $error_msg = "El enlace de verificación no es válido o ya ha sido utilizado.";
    }
}
?>
<!DOCTYPE html>
<html lang="es" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificación de Cuenta - CatInk</title>
    <script>document.documentElement.setAttribute('data-bs-theme', localStorage.getItem('theme') || 'light');</script>
    <link rel="stylesheet" href="<?= basePath() ?>/CSS/styles.css">
    <!-- Favicon -->
    <link rel="icon" href="<?= basePath() ?>/catink-icon.ico?v=2" type="image/x-icon">
    <link rel="icon" href="<?= basePath() ?>/img/catink-icon.png?v=2" type="image/png">
    <link rel="apple-touch-icon" href="<?= basePath() ?>/img/catink-icon.png?v=2">
</head>
<body style="display:flex; flex-direction:column; min-height:100vh; background: var(--bg);">
<div style="flex:1; display:flex; flex-direction:column; align-items:center; justify-content:center; padding:20px;">
    
    <div style="width: 100%; max-width: 480px; text-align: center; margin-bottom: 24px;">
        <a href="<?= basePath() ?>/" style="text-decoration:none;">
            <span style="font-family:'Outfit', sans-serif; font-size:2.8rem; font-weight:900; color:var(--text); letter-spacing:-1.5px; transition:color 0.3s ease;">
                Cat<span style="color:#EF3363;">Ink</span>
            </span>
        </a>
    </div>

    <div class="auth-panel" style="width: 100%; max-width: 480px; padding: 30px; background: var(--surface); border-radius: 12px; border: 1px solid var(--border); box-shadow: var(--shadow-lg);">
        <h2 style="color:#EF3363; text-align:center; font-family:'Outfit', sans-serif; font-size:1.8rem; font-weight:800; margin-bottom:20px;">
            Verificación de Cuenta
        </h2>
        
        <?php if ($error_msg): ?>
            <div style="color:#EF3363; background:rgba(239, 51, 99, 0.05); border: 1px solid rgba(239, 51, 99, 0.1); padding: 15px; border-radius: 8px; margin-bottom: 24px; font-size: 0.95rem; line-height: 1.5; text-align: center;">
                <span style="font-size: 1.5rem; display: block; margin-bottom: 8px;">⚠️</span>
                <?= htmlspecialchars($error_msg) ?>
            </div>
            
            <a href="<?= basePath() ?>/login" class="btn-perfil-save" style="display:block; text-align:center; text-decoration:none; margin-top:20px; line-height:38px; height:38px;">
                Ir al Inicio de Sesión
            </a>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
