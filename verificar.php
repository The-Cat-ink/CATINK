<?php
require_once(__DIR__ . "/data/conexion.php");
require_once(__DIR__ . "/views/helpers/urlhelper.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$token = trim($_GET['token'] ?? '');
$error_msg = null;
$already_verified = false;
$success = false;

if (empty($token)) {
    $error_msg = "El token de verificación no ha sido proporcionado.";
} else {
    // Buscar al lector con este token
    $stmt = $con->prepare("SELECT id, nombre, usuario, verificado FROM lectores WHERE token_verificacion = ?");
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
        } else {
            $error_msg = "Ocurrió un error al procesar la verificación. Por favor, intenta de nuevo.";
        }
    } else {
        $already_verified = true;
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Favicon -->
    <link rel="icon" href="<?= basePath() ?>/catink-icon.ico?v=2" type="image/x-icon">
    <link rel="icon" href="<?= basePath() ?>/img/catink-icon.png?v=2" type="image/png">
    <link rel="apple-touch-icon" href="<?= basePath() ?>/img/catink-icon.png?v=2">
</head>
<body style="display:flex; flex-direction:column; min-height:100vh; background: var(--bg); font-family: 'Inter', sans-serif;">
<div style="flex:1; display:flex; flex-direction:column; align-items:center; justify-content:center; padding:20px;">
    
    <div style="width: 100%; max-width: 500px; text-align: center; margin-bottom: 24px;">
        <a href="<?= basePath() ?>/" style="text-decoration:none;">
            <span style="font-family:'Outfit', sans-serif; font-size:2.8rem; font-weight:900; color:var(--text); letter-spacing:-1.5px;">
                Cat<span style="color:#EF3363;">Ink</span>
            </span>
        </a>
    </div>

    <div class="auth-panel" style="width: 100%; max-width: 500px; padding: 36px 28px; background: var(--card-bg); border-radius: 20px; border: 1px solid var(--border); box-shadow: 0 16px 40px rgba(0,0,0,0.08); text-align:center;">
        
        <?php if ($success): ?>
            <div style="width:80px; height:80px; border-radius:50%; background:rgba(16,185,129,0.12); color:#10b981; display:flex; align-items:center; justify-content:center; font-size:2.5rem; margin:0 auto 20px;">
                <i class="bi bi-check-circle-fill"></i>
            </div>
            <h2 style="color:var(--text); font-family:'Outfit', sans-serif; font-size:1.8rem; font-weight:800; margin-bottom:12px;">
                ¡Cuenta Verificada con Éxito!
            </h2>
            <p style="color:var(--muted); font-size:0.95rem; line-height:1.6; margin-bottom:24px;">
                Tu identidad ha sido confirmada correctamente. Redirigiendo a la personalización de tu perfil...
            </p>
        <?php elseif ($already_verified): ?>
            <div style="width:80px; height:80px; border-radius:50%; background:rgba(239,51,99,0.12); color:var(--accent); display:flex; align-items:center; justify-content:center; font-size:2.5rem; margin:0 auto 20px;">
                <i class="bi bi-patch-check-fill"></i>
            </div>
            <h2 style="color:var(--text); font-family:'Outfit', sans-serif; font-size:1.8rem; font-weight:800; margin-bottom:12px;">
                ¡Tu Cuenta ya está Verificada!
            </h2>
            <p style="color:var(--muted); font-size:0.95rem; line-height:1.6; margin-bottom:24px;">
                Tu cuenta ya fue confirmada previamente. Puedes ingresar directamente a personalizar tu perfil o continuar navegando.
            </p>
        <?php else: ?>
            <div style="width:80px; height:80px; border-radius:50%; background:rgba(239,68,68,0.12); color:#ef4444; display:flex; align-items:center; justify-content:center; font-size:2.5rem; margin:0 auto 20px;">
                <i class="bi bi-exclamation-triangle-fill"></i>
            </div>
            <h2 style="color:var(--text); font-family:'Outfit', sans-serif; font-size:1.8rem; font-weight:800; margin-bottom:12px;">
                Aviso de Verificación
            </h2>
            <p style="color:var(--muted); font-size:0.95rem; line-height:1.6; margin-bottom:24px;">
                <?= htmlspecialchars($error_msg) ?>
            </p>
        <?php endif; ?>

        <!-- Acciones principales -->
        <div style="display:flex; flex-direction:column; gap:12px;">
            <a href="<?= basePath() ?>/views/perfil.php?registro=verificado" class="btn-perfil-save" style="width:100%; border:none; height:48px; border-radius:12px; font-weight:800; font-size:0.95rem; background:var(--accent); color:#fff; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:8px; box-shadow:0 4px 14px rgba(239,51,99,0.35); text-decoration:none;">
                <i class="bi bi-person-circle" style="font-size:1.2rem;"></i> Personalizar mi Perfil
            </a>

            <button type="button" onclick="regresarPaginaAnterior()" style="width:100%; border:1px solid var(--border); height:44px; border-radius:12px; font-weight:700; font-size:0.88rem; background:transparent; color:var(--text); cursor:pointer; display:flex; align-items:center; justify-content:center; gap:8px;">
                <i class="bi bi-arrow-left-circle" style="font-size:1.05rem;"></i> Regresar a la página anterior
            </button>

            <a href="<?= basePath() ?>/" style="color:var(--muted); font-size:0.85rem; font-weight:600; text-decoration:none; display:inline-block; padding:4px;">
                <i class="bi bi-house-door me-1"></i> Ir al Inicio de CatInk
            </a>
        </div>
    </div>
</div>

<script>
function regresarPaginaAnterior() {
    if (window.history.length > 1 && document.referrer && !document.referrer.includes('verificar.php')) {
        window.history.back();
    } else {
        window.location.href = '<?= basePath() ?>/';
    }
}

<?php if ($success): ?>
setTimeout(() => {
    window.location.href = '<?= basePath() ?>/views/perfil.php?registro=verificado';
}, 1500);
<?php endif; ?>
</script>
</body>
</html>
