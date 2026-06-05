<?php
require_once(__DIR__ . "/../views/helpers/urlhelper.php");
if(session_status() === PHP_SESSION_NONE){
    session_start();
}
include("./../layout/header.php");
include("./../data/conexion.php");

$token = $_GET['token'] ?? '';
$error = '';
$token_valido = false;

if (empty($token)) {
    $error = "Token no proporcionado";
} else {
    // Validar token
    $stmt = $con->prepare("SELECT id, email, tipo_usuario, expira FROM password_reset_tokens WHERE token = ? AND usado = 0 AND expira > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $error = "Token inválido o expirado";
    } else {
        $token_valido = true;
        $token_data = $result->fetch_assoc();
    }
}
?>

<div style="display:flex; flex-direction:column; align-items:center; justify-content:center; min-height:80vh; padding:20px;">
    <a href="<?= basePath() ?>/">
      <img src="<?= imageUrl('img/logo.png') ?>" alt="CatInk" style="width:180px; margin-bottom:24px; border-radius:12px;" loading="lazy" decoding="async">
    </a>

    <div class="card" style="width:100%; max-width:400px; border-radius:12px; padding:24px; overflow:hidden; position:relative;">
        <h2 class="text-center mb-4" style="font-weight:600;">Establecer Nueva Contraseña</h2>
        
        <?php if ($error): ?>
            <p style="color:#EF3363; text-align:center; margin-bottom:12px;">
                <strong>Error:</strong> <?= htmlspecialchars($error) ?>
            </p>
            <div style="text-align:center;">
                <a href="<?= basePath() ?>/olvide_contrasena" class="btn-perfil-save" style="display:inline-block; text-decoration:none;">Solicitar Nuevo Enlace</a>
            </div>
        <?php elseif ($token_valido): ?>
            <form method="POST" action="./../controllers/actualizar_contrasena.php" id="formReset">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                
                <div class="form-group">
                    <label for="password">Nueva Contraseña</label>
                    <input type="password" class="input" id="password" name="password" required minlength="8" placeholder="Mínimo 8 caracteres">
                    <small style="color:var(--muted); font-size:0.85rem;">Debe contener al menos 8 caracteres</small>
                </div>
                
                <div class="form-group">
                    <label for="password_confirm">Confirmar Contraseña</label>
                    <input type="password" class="input" id="password_confirm" name="password_confirm" required minlength="8" placeholder="Repite tu contraseña">
                </div>
                
                <div id="passwordError" style="color:#EF3363; text-align:center; margin-bottom:12px; display:none;">
                    Las contraseñas no coinciden
                </div>
                
                <button type="submit" class="btn-perfil-save" style="margin-top:12px;">Actualizar Contraseña</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<script>
document.getElementById('formReset')?.addEventListener('submit', function(e) {
    const password = document.getElementById('password').value;
    const passwordConfirm = document.getElementById('password_confirm').value;
    const errorDiv = document.getElementById('passwordError');
    
    if (password !== passwordConfirm) {
        e.preventDefault();
        errorDiv.style.display = 'block';
    } else {
        errorDiv.style.display = 'none';
    }
});
</script>

<?php include("./../layout/footer.php"); ?>
