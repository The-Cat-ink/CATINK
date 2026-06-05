<?php
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

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-lg">
                <div class="card-body p-5">
                    <h2 class="text-center mb-4">Establecer Nueva Contraseña</h2>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger" role="alert">
                            <strong>Error:</strong> <?= htmlspecialchars($error) ?>
                        </div>
                        <div class="text-center">
                            <a href="./olvide_contrasena.php" class="btn btn-primary">Solicitar Nuevo Enlace</a>
                        </div>
                    <?php elseif ($token_valido): ?>
                        <form method="POST" action="./../controllers/actualizar_contrasena.php" id="formReset">
                            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">Nueva Contraseña</label>
                                <input type="password" class="form-control" id="password" name="password" required minlength="8" placeholder="Mínimo 8 caracteres">
                                <small class="text-muted">Debe contener al menos 8 caracteres</small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password_confirm" class="form-label">Confirmar Contraseña</label>
                                <input type="password" class="form-control" id="password_confirm" name="password_confirm" required minlength="8" placeholder="Repite tu contraseña">
                            </div>
                            
                            <div id="passwordError" class="alert alert-danger d-none" role="alert">
                                Las contraseñas no coinciden
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100">Actualizar Contraseña</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('formReset')?.addEventListener('submit', function(e) {
    const password = document.getElementById('password').value;
    const passwordConfirm = document.getElementById('password_confirm').value;
    const errorDiv = document.getElementById('passwordError');
    
    if (password !== passwordConfirm) {
        e.preventDefault();
        errorDiv.classList.remove('d-none');
    } else {
        errorDiv.classList.add('d-none');
    }
});
</script>

<?php include("./../layout/footer.php"); ?>
