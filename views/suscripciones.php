<?php 
include("./../layout/headerAdmin.php");
include("./../data/conexion.php");
$ACL = $_SESSION['ACL']['suscripciones']??[
    "crear" => false,
    "leer" => false,
    "editar" => false,
    "eliminar" => false
];
if (!$ACL['leer']) {
    header("Location: admin.php");
    exit();
}
$q = trim($_GET['q'] ?? '');
if($q !== ''){
    $like = "%$q%";
    $sql = "SELECT * FROM suscripciones WHERE nombre_completo LIKE ? OR correo LIKE ? ORDER BY fecha DESC";
    $stmt = $con->prepare($sql);
    $stmt->bind_param("ss", $like, $like);
    $stmt->execute();
    $suscripciones = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
    $sql = "SELECT * FROM suscripciones ORDER BY fecha DESC";
    $resultado = mysqli_query($con, $sql);
    $suscripciones = mysqli_fetch_all($resultado, MYSQLI_ASSOC);
}
$sql2= "SELECT * FROM programacion_correos";
$resultado2 = mysqli_query($con, $sql2);
$programacion = mysqli_fetch_all($resultado2, MYSQLI_ASSOC);
$prog = $programacion[0] ?? ['id_programacion' => '', 'hora' => '', 'estado' => 'inactivo'];
?>
<div class="container-fluid">
    <?php if($superadmin): ?>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Programacion de correos</h1>
        </div>
        <form action="./../controllers/actualizarcorreos.php" method="POST">
            <div class="form-card card">
                <input type="hidden" name="id" value="<?php echo $prog['id_programacion']; ?>">
                <div class="row">
                    <div class="col">
                        <div class="form-group">
                            <label for="hora">Hora de envio:</label>
                            <input type="time" name="hora" value="<?php echo $prog['hora']; ?>" required>
                        </div>
                    </div>
                    <div class="col">
                        <div class="form-group">
                            <label for="estado">Estado:</label>
                            <select name="estado" id="estado" required>
                                <option value="activo" <?php if($prog['estado'] == 'activo') echo 'selected'; ?>>Activo</option>
                                <option value="inactivo" <?php if($prog['estado'] == 'inactivo') echo 'selected'; ?>>Inactivo</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-accent" name="actualizarProgramacion">
                        Actualizar programación
                    </button>
                </div>
            </div>
        </form>
        <hr>
    <?php endif; ?>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Gestión de Suscripciones</h1>
        <form method="GET" class="admin-search-form">
            <i class="bi bi-search admin-search-icon"></i>
            <input type="search" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Buscar por nombre o email..." class="admin-search-input">
            <?php if($q): ?><a href="./suscripciones.php" class="admin-search-clear">&times;</a><?php endif; ?>
        </form>
    </div>
    <div class="mb-3">
        <button type="button" class="btn btn-sm btn-secondary" onclick="toggleSelectAll()">
            <i class="bi bi-check-all"></i> Seleccionar todos
        </button>
        <button type="button" class="btn btn-sm btn-warning" onclick="deselectAll()">
            <i class="bi bi-x-circle"></i> Deseleccionar
        </button>
        <button type="button" class="btn btn-sm btn-primary" onclick="sendToSelected()" id="sendSelectedBtn" style="display:none;">
            <i class="bi bi-envelope"></i> Enviar a seleccionados
        </button>
    </div>
    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th style="width: 40px;"><input type="checkbox" id="selectAllCheckbox" onchange="toggleSelectAll()"></th>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Sexo</th>
                    <th>Fecha de alta</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($suscripciones as $suscripcion): ?>
                <tr>
                    <td><input type="checkbox" class="subscriber-checkbox" value="<?php echo $suscripcion['id_sub']; ?>" onchange="updateSendButton()"></td>
                    <td><?php echo $suscripcion['nombre_completo']; ?></td>
                    <td><?php echo $suscripcion['correo']; ?></td>
                    <td><?php echo $suscripcion['sexo']; ?></td>
                    <td><?php echo $suscripcion['fecha']; ?></td>
                    <td>
                        <form method="POST" action="./../controllers/enviarCorreoSuscriptor.php" style="display:inline;">
                            <input type="hidden" name="id" value="<?php echo $suscripcion['id_sub']; ?>">
                            <button type="submit" class="btn btn-sm btn-primary" title="Enviar correo a este suscriptor">
                                <i class="bi bi-envelope"></i> Enviar
                            </button>
                        </form>
                        <form method="POST" action="./../controllers/eliminarSuscriptor.php" style="display:inline;">
                            <input type="hidden" name="id" value="<?php echo $suscripcion['id_sub']; ?>">
                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar este suscriptor?');">
                                <i class="bi bi-trash"></i> Eliminar
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function toggleSelectAll() {
    const checkboxes = document.querySelectorAll('.subscriber-checkbox');
    const selectAllCheckbox = document.getElementById('selectAllCheckbox');
    const allChecked = selectAllCheckbox.checked;
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = allChecked;
    });
    
    updateSendButton();
}

function deselectAll() {
    const checkboxes = document.querySelectorAll('.subscriber-checkbox');
    const selectAllCheckbox = document.getElementById('selectAllCheckbox');
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = false;
    });
    selectAllCheckbox.checked = false;
    
    updateSendButton();
}

function updateSendButton() {
    const checkboxes = document.querySelectorAll('.subscriber-checkbox:checked');
    const sendBtn = document.getElementById('sendSelectedBtn');
    
    if (checkboxes.length > 0) {
        sendBtn.style.display = 'inline-block';
    } else {
        sendBtn.style.display = 'none';
    }
}

function sendToSelected() {
    const checkboxes = document.querySelectorAll('.subscriber-checkbox:checked');
    
    if (checkboxes.length === 0) {
        alert('Selecciona al menos un suscriptor');
        return;
    }
    
    const ids = Array.from(checkboxes).map(cb => cb.value);
    
    // Crear un formulario oculto para enviar múltiples IDs
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = './../controllers/enviarCorreoMultiple.php';
    
    ids.forEach(id => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'ids[]';
        input.value = id;
        form.appendChild(input);
    });
    
    document.body.appendChild(form);
    form.submit();
}
</script>

<?php include("./../layout/footerAdmin.php"); ?>