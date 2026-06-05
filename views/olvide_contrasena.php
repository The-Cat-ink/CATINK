<?php
require_once(__DIR__ . "/../views/helpers/urlhelper.php");
if(session_status() === PHP_SESSION_NONE){
    session_start();
}
include("./../layout/header.php");
$mensaje = $_GET['mensaje'] ?? '';
$error = $_GET['error'] ?? '';
?>

<div style="display:flex; flex-direction:column; align-items:center; justify-content:center; min-height:80vh; padding:20px;">
    <a href="<?= basePath() ?>/">
      <img src="<?= imageUrl('img/logo.png') ?>" alt="CatInk" style="width:180px; margin-bottom:24px; border-radius:12px;" loading="lazy" decoding="async">
    </a>

    <div class="card" style="width:100%; max-width:400px; border-radius:12px; padding:24px; overflow:hidden; position:relative;">
        <h2 class="text-center mb-4" style="font-weight:600;">Recuperar Contraseña</h2>
        
        <?php if ($mensaje): ?>
            <p style="color:#28a745; text-align:center; margin-bottom:12px;">
                <?= htmlspecialchars($mensaje) ?>
            </p>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <p style="color:#EF3363; text-align:center; margin-bottom:12px;">
                <?= htmlspecialchars($error) ?>
            </p>
        <?php endif; ?>
        
        <form method="POST" action="./../controllers/solicitar_reset_contrasena.php">
            <div class="form-group">
                <label for="email">Correo Electrónico</label>
                <input type="email" class="input" id="email" name="email" required placeholder="tu@email.com">
                <small style="color:var(--muted); font-size:0.85rem;">Ingresa el correo asociado a tu cuenta</small>
            </div>
            
            <button type="submit" class="btn-perfil-save" style="margin-top:12px;">Enviar Enlace de Recuperación</button>
        </form>
        
        <p class="text-center" style="margin-top:16px; font-size:0.9rem;">
            ¿Recordaste tu contraseña? <a href="<?= basePath() ?>/login" style="color:var(--accent); text-decoration:none; font-weight:600;">Inicia sesión</a>
        </p>
    </div>
</div>

<?php include("./../layout/footer.php"); ?>
