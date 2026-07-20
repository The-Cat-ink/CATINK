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

// Stats
$totalSuscripciones = count($suscripciones);
$hombres = count(array_filter($suscripciones, fn($s) => strtolower($s['sexo']) == 'masculino'));
$mujeres = count(array_filter($suscripciones, fn($s) => strtolower($s['sexo']) == 'femenino'));
$otros = $totalSuscripciones - $hombres - $mujeres;
?>
<div class="container-fluid">
    <?php if($superadmin): ?>
        <div class="card shadow-sm mb-4" style="border: 1px solid var(--border); border-radius: 12px; background: var(--card-bg);">
            <div class="card-body" style="padding: 20px;">
                <h2 style="font-size: 1.2rem; margin-top:0; margin-bottom: 15px;"><i class="bi bi-clock-history text-accent"></i> Programación de correos automática</h2>
                <form action="./../controllers/actualizarcorreos.php" method="POST">
                    <input type="hidden" name="id" value="<?php echo $prog['id_programacion']; ?>">
                    <div class="row" style="display:flex; gap:16px; flex-wrap:wrap;">
                        <div style="flex:1; min-width: 200px;">
                            <label style="display:block; font-size:0.85rem; font-weight:600; margin-bottom:6px; color:var(--text);">Hora de envío:</label>
                            <input type="time" name="hora" value="<?php echo $prog['hora']; ?>" required style="width:100%; padding:10px 14px; border:1px solid var(--border); border-radius:8px; background:var(--bg); color:var(--text); font-size:0.9rem;">
                        </div>
                        <div style="flex:1; min-width: 200px;">
                            <label style="display:block; font-size:0.85rem; font-weight:600; margin-bottom:6px; color:var(--text);">Estado:</label>
                            <select name="estado" id="estado" required style="width:100%; padding:10px 14px; border:1px solid var(--border); border-radius:8px; background:var(--bg); color:var(--text); font-size:0.9rem;">
                                <option value="activo" <?php if($prog['estado'] == 'activo') echo 'selected'; ?>>Activo</option>
                                <option value="inactivo" <?php if($prog['estado'] == 'inactivo') echo 'selected'; ?>>Inactivo</option>
                            </select>
                        </div>
                    </div>
                    <div style="margin-top: 20px; display:flex; justify-content:flex-end;">
                        <button type="submit" class="btn btn-accent" name="actualizarProgramacion">
                            <i class="bi bi-save"></i> Guardar Programación
                        </button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-4" style="flex-wrap: wrap; gap: 12px;">
        <h1 style="margin:0;">Gestión de Suscripciones</h1>
        <form method="GET" class="admin-search-form" style="display:flex; align-items:center; gap:8px;">
            <i class="bi bi-search admin-search-icon"></i>
            <input type="search" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Buscar por nombre o email..." class="admin-search-input">
            <?php if($q): ?><a href="./suscripciones.php" class="admin-search-clear">&times;</a><?php endif; ?>
        </form>
    </div>

    <!-- Estadísticas rápidas -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(99,102,241,0.1); color: #6366f1;"><i class="bi bi-people"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?= $totalSuscripciones ?></span>
                <span class="stat-label">Total Suscriptores</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(16,185,129,0.1); color: #10b981;"><i class="bi bi-gender-male"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?= $hombres ?></span>
                <span class="stat-label">Hombres</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(239,51,99,0.1); color: #EF3363;"><i class="bi bi-gender-female"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?= $mujeres ?></span>
                <span class="stat-label">Mujeres</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(245,158,11,0.1); color: #f59e0b;"><i class="bi bi-gender-ambiguous"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?= $otros ?></span>
                <span class="stat-label">Otro / N/A</span>
            </div>
        </div>
    </div>

    <!-- Toolbar -->
    <div class="contenidos-toolbar">
        <div class="contenidos-tabs" style="display:flex; align-items:center; gap:8px;">
            <button type="button" class="btn btn-outline-secondary" onclick="selectAll()" style="padding:6px 12px; font-size:0.85rem; border-radius:8px;">
                <i class="bi bi-check-all"></i> Marcar todos
            </button>
            <button type="button" class="btn btn-outline-secondary" onclick="deselectAll()" style="padding:6px 12px; font-size:0.85rem; border-radius:8px;">
                <i class="bi bi-x-circle"></i> Desmarcar
            </button>
        </div>
        <div class="contenidos-actions">
            <button type="button" class="btn btn-accent" onclick="sendToSelected()" id="sendSelectedBtn" style="display:none;">
                <i class="bi bi-envelope"></i> Enviar a seleccionados
            </button>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="contenidos-table">
                    <thead>
                        <tr>
                            <th style="width: 40px; text-align:center;"><input type="checkbox" id="selectAllCheckbox" onchange="toggleSelectAll()" style="accent-color:var(--accent); width:16px; height:16px; cursor:pointer;"></th>
                            <th>Nombre</th>
                            <th>Email</th>
                            <th>Sexo</th>
                            <th>Fecha de alta</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($suscripciones)): ?>
                            <tr><td colspan="6" style="text-align:center; padding:30px; color:var(--muted);">No se encontraron suscriptores.</td></tr>
                        <?php else: ?>
                            <?php foreach($suscripciones as $suscripcion): ?>
                            <tr>
                                <td style="text-align:center;"><input type="checkbox" class="subscriber-checkbox" value="<?php echo $suscripcion['id_sub']; ?>" onchange="updateSendButton()" style="accent-color:var(--accent); width:16px; height:16px; cursor:pointer;"></td>
                                <td><strong class="table-title"><?php echo htmlspecialchars($suscripcion['nombre_completo']); ?></strong></td>
                                <td><span style="font-size:0.9rem; color:var(--text);"><?php echo htmlspecialchars($suscripcion['correo']); ?></span></td>
                                <td><span style="font-size:0.85rem; color:var(--muted); text-transform:capitalize;"><?php echo htmlspecialchars($suscripcion['sexo']); ?></span></td>
                                <td class="table-date"><?php echo date('d/m/Y', strtotime($suscripcion['fecha'])); ?></td>
                                <td>
                                    <div class="noticias-actions" style="border-top:none; padding:0; justify-content:flex-start;">
                                        <form method="POST" action="./../controllers/enviarCorreoSuscriptor.php" style="display:inline;">
                                            <input type="hidden" name="id" value="<?php echo $suscripcion['id_sub']; ?>">
                                            <button type="submit" class="btn btn-view" title="Enviar correo a este suscriptor">
                                                <i class="bi bi-envelope"></i>
                                            </button>
                                        </form>
                                        <?php if($ACL['eliminar']): ?>
                                            <button type="button" class="btn btn-delete btn-delete-suscriptor" data-id="<?php echo $suscripcion['id_sub']; ?>" title="Eliminar">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Confirmación para Eliminar -->
<div id="modalOverlayS" class="crop-modal" style="display: none;">
    <div class="crop-modal-content">
        <h3 id="modalTitleS"><i class="bi bi-trash"></i> Confirmar eliminación</h3>
        <p style="color:var(--muted); font-size:0.9rem; margin-bottom:15px; margin-top:5px;">¿Estás seguro de que deseas eliminar este suscriptor? Esta acción no se puede deshacer.</p>
        <form id="modalFormS" action="./../controllers/eliminarSuscriptor.php" method="POST">
            <input type="hidden" name="id" id="modalIdS">
            <div class="crop-actions" style="display:flex; justify-content:flex-end; gap:8px;">
                <button type="button" class="btn btn-secondary btn-cancel">Cancelar</button>
                <button type="submit" class="btn btn-accent">Eliminar</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const modal = document.getElementById("modalOverlayS");
    const modalId = document.getElementById("modalIdS");
    
    document.querySelectorAll(".btn-delete-suscriptor").forEach(btn => {
        btn.addEventListener("click", (e) => {
            e.stopPropagation(); // Evitar que seleccione la fila
            modalId.value = btn.dataset.id;
            modal.style.display = "flex";
        });
    });
    
    document.querySelectorAll(".btn-cancel").forEach(btn => {
        btn.addEventListener("click", () => {
            modal.style.display = "none";
        });
    });
    
    window.addEventListener("click", (e) => {
        if(e.target === modal) modal.style.display = "none";
    });
});

// Llamado por el checkbox del header: alterna según el estado del propio checkbox
function toggleSelectAll() {
    const checkboxes = document.querySelectorAll('.subscriber-checkbox');
    const selectAllCheckbox = document.getElementById('selectAllCheckbox');
    const allChecked = selectAllCheckbox.checked;
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = allChecked;
    });
    
    updateSendButton();
}

// Llamado por el botón "Marcar todos": siempre selecciona todo
function selectAll() {
    const checkboxes = document.querySelectorAll('.subscriber-checkbox');
    const selectAllCheckbox = document.getElementById('selectAllCheckbox');
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = true;
    });
    selectAllCheckbox.checked = true;
    
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
        sendBtn.style.display = 'inline-flex';
        sendBtn.innerHTML = `<i class="bi bi-envelope"></i> Enviar a ${checkboxes.length} seleccionados`;
    } else {
        sendBtn.style.display = 'none';
    }
}

// Inicializar el evento por si el usuario presiona el checkbox general pero haciendo click en el td entero
document.querySelectorAll('input[type="checkbox"]').forEach(c => {
    c.addEventListener('click', e => e.stopPropagation());
});
document.querySelectorAll('.contenidos-table tbody tr').forEach(row => {
    row.addEventListener('click', function(e) {
        if(e.target.tagName === 'BUTTON' || e.target.tagName === 'A' || e.target.tagName === 'I' || e.target.closest('.noticias-actions')) return;
        const cb = this.querySelector('.subscriber-checkbox');
        if(cb) {
            cb.checked = !cb.checked;
            updateSendButton();
        }
    });
    row.style.cursor = 'pointer';
});

    function sendToSelected() {
        const checkboxes = document.querySelectorAll('.subscriber-checkbox:checked');
        
        if (checkboxes.length === 0) {
            showToast('Selecciona al menos un suscriptor', 'error');
            return;
        }
        
        const ids = Array.from(checkboxes).map(cb => cb.value);
        
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

    // ── NOTIFICACIONES TOAST EN TIEMPO REAL ──
    function showToast(msg, type = '') {
        let container = document.querySelector('.toast-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'toast-container';
            document.body.appendChild(container);
        }
        const toast = document.createElement('div');
        toast.className = 'toast-msg' + (type ? ' toast-' + type : '');
        toast.textContent = msg;
        container.appendChild(toast);
        setTimeout(() => {
            toast.style.transition = 'opacity 0.3s ease';
            toast.style.opacity = '0';
            setTimeout(() => toast.remove(), 300);
        }, 3200);
    }

    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('success')) {
        const val = urlParams.get('success');
        let text = 'Operación realizada con éxito';
        if (val === 'programacion_actualizada' || val === 'programacion_guardada') text = 'Programación de correo automático guardada correctamente';
        else if (val === 'correo_enviado') text = 'Resumen de noticias enviado exitosamente';
        else if (val === 'correos_enviados') text = 'Correos masivos enviados exitosamente';
        else if (val === 'suscriptor_eliminado' || val === '1') text = 'Suscriptor eliminado correctamente';
        showToast(text, 'success');
    }
    if (urlParams.has('error')) {
        const val = urlParams.get('error');
        let text = 'Ocurrió un error al procesar la solicitud';
        if (val === 'permisos') text = 'No tienes permisos para realizar esta acción';
        else if (val === 'envio') text = 'Error al intentar enviar el correo SMTP';
        else if (val === 'no_encontrado') text = 'Suscriptor no encontrado';
        showToast(text, 'error');
    }
});
</script>

<?php include("./../layout/footerAdmin.php"); ?>