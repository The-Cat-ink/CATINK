<?php
include("./../layout/header.php");
$mensaje = $_GET['mensaje'] ?? '';
$error = $_GET['error'] ?? '';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-lg">
                <div class="card-body p-5">
                    <h2 class="text-center mb-4">Recuperar Contraseña</h2>
                    
                    <?php if ($mensaje): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?= htmlspecialchars($mensaje) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?= htmlspecialchars($error) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="./../controllers/solicitar_reset_contraseña.php">
                        <div class="mb-3">
                            <label for="email" class="form-label">Correo Electrónico</label>
                            <input type="email" class="form-control" id="email" name="email" required placeholder="tu@email.com">
                            <small class="text-muted">Ingresa el correo asociado a tu cuenta</small>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100">Enviar Enlace de Recuperación</button>
                    </form>
                    
                    <hr class="my-4">
                    
                    <div class="text-center">
                        <p class="mb-0">¿Recordaste tu contraseña? <a href="./login.php">Inicia sesión</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include("./../layout/footer.php"); ?>
