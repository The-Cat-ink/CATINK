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
<div class="container-fluid px-3 py-2">

    <!-- ── ENCABEZADO Y BÚSQUEDA ───────────────────────────────── -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <h1 style="font-weight:900; font-size:1.8rem; margin:0; color:var(--text); letter-spacing:-0.02em;">
                    Gestión de Suscripciones & Newsletter
                </h1>
                <span class="badge" style="background:rgba(239,51,99,0.12); color:var(--accent); border:1px solid rgba(239,51,99,0.25); border-radius:20px; padding:4px 10px; font-weight:800; font-size:0.72rem;">
                    <?= $totalSuscripciones ?> Suscriptores
                </span>
            </div>
            <p class="text-muted m-0" style="font-size:0.88rem;">Administra el padrón de lectores, envíos de boletines y la programación automática del sistema.</p>
        </div>

        <form method="GET" class="d-flex align-items-center gap-2 m-0">
            <div style="position:relative; width:280px;">
                <i class="bi bi-search" style="position:absolute; left:14px; top:50%; transform:translateY(-50%); color:var(--muted); font-size:0.9rem;"></i>
                <input type="search" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Buscar por nombre o email..." class="cn-input" style="padding-left:38px; padding-right:<?= $q ? '36px' : '14px' ?>; border-radius:12px; font-size:0.88rem;">
                <?php if($q): ?>
                    <a href="./suscripciones.php" style="position:absolute; right:12px; top:50%; transform:translateY(-50%); color:var(--muted); text-decoration:none; font-weight:bold; font-size:1.1rem;">&times;</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- ── TARJETA DE PROGRAMACIÓN AUTOMÁTICA (SUPERADMIN) ────── -->
    <?php if($superadmin): ?>
        <div class="card border-0 shadow-sm mb-4" style="background:var(--card-bg); border-radius:18px; border:1px solid var(--border)!important; overflow:hidden;">
            <div class="card-body p-3 p-md-4">
                <form action="./../controllers/actualizarcorreos.php" method="POST" class="m-0">
                    <input type="hidden" name="id" value="<?php echo $prog['id_programacion']; ?>">
                    
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                        <div class="d-flex align-items-center gap-3">
                            <div style="width:44px; height:44px; border-radius:14px; background:rgba(239,51,99,0.12); color:var(--accent); display:flex; align-items:center; justify-content:center; font-size:1.3rem; flex-shrink:0;">
                                <i class="bi bi-clock-history"></i>
                            </div>
                            <div>
                                <h5 class="m-0 font-weight-bold" style="font-weight:900; font-size:1rem; color:var(--text);">Programación de Correos Automática</h5>
                                <span class="text-muted" style="font-size:0.8rem;">Define el horario y estado para el envío automático del resumen diario a suscriptores.</span>
                            </div>
                        </div>

                        <?php if($prog['estado'] == 'activo'): ?>
                            <span class="badge" style="background:rgba(16,185,129,0.12); color:#10b981; border:1px solid rgba(16,185,129,0.25); font-size:0.78rem; font-weight:800; padding:6px 12px; border-radius:20px;">
                                <i class="bi bi-circle-fill" style="font-size:0.4rem; vertical-align:middle; margin-right:4px;"></i> Envío Automático Activo
                            </span>
                        <?php else: ?>
                            <span class="badge" style="background:rgba(239,51,99,0.12); color:var(--accent); border:1px solid rgba(239,51,99,0.25); font-size:0.78rem; font-weight:800; padding:6px 12px; border-radius:20px;">
                                <i class="bi bi-pause-circle-fill me-1"></i> Envío Automático Pausado
                            </span>
                        <?php endif; ?>
                    </div>

                    <div class="row g-3 align-items-end">
                        <div class="col-12 col-md-5">
                            <label style="display:block; font-size:0.82rem; font-weight:700; margin-bottom:6px; color:var(--text);">Hora de Envío Diario:</label>
                            <div style="position:relative;">
                                <i class="bi bi-alarm" style="position:absolute; left:14px; top:50%; transform:translateY(-50%); color:var(--muted); font-size:1rem;"></i>
                                <input type="time" name="hora" value="<?php echo $prog['hora']; ?>" required class="cn-input" style="padding-left:40px; border-radius:12px; font-weight:700;">
                            </div>
                        </div>

                        <div class="col-12 col-md-4">
                            <label style="display:block; font-size:0.82rem; font-weight:700; margin-bottom:6px; color:var(--text);">Estado del Programador:</label>
                            <select name="estado" id="estado" required class="cn-input" style="border-radius:12px; font-weight:700;">
                                <option value="activo" <?php if($prog['estado'] == 'activo') echo 'selected'; ?>>● Activo (Ejecución diaria)</option>
                                <option value="inactivo" <?php if($prog['estado'] == 'inactivo') echo 'selected'; ?>>○ Inactivo (Pausado)</option>
                            </select>
                        </div>

                        <div class="col-12 col-md-3 text-end">
                            <button type="submit" class="btn btn-accent px-4 py-2 w-100" name="actualizarProgramacion" style="border-radius:12px; font-weight:800; font-size:0.9rem; box-shadow:0 4px 15px rgba(239,51,99,0.3);">
                                <i class="bi bi-save me-1"></i> Guardar Programación
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <!-- ── TARJETAS DE ESTADÍSTICAS RÁPIDAS ────────────────────── -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm p-3 h-100" style="background:var(--card-bg); border-radius:16px; border:1px solid var(--border)!important;">
                <div class="d-flex align-items-center gap-3">
                    <div style="width:44px; height:44px; border-radius:12px; background:rgba(99,102,241,0.12); color:#6366f1; display:flex; align-items:center; justify-content:center; font-size:1.3rem; flex-shrink:0;">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <div>
                        <div style="font-size:1.4rem; font-weight:900; color:var(--text); line-height:1;"><?= $totalSuscripciones ?></div>
                        <div style="font-size:0.75rem; font-weight:700; color:var(--muted); margin-top:4px; text-transform:uppercase; letter-spacing:0.04em;">Total Suscriptores</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm p-3 h-100" style="background:var(--card-bg); border-radius:16px; border:1px solid var(--border)!important;">
                <div class="d-flex align-items-center gap-3">
                    <div style="width:44px; height:44px; border-radius:12px; background:rgba(59,130,246,0.12); color:#3b82f6; display:flex; align-items:center; justify-content:center; font-size:1.3rem; flex-shrink:0;">
                        <i class="bi bi-gender-male"></i>
                    </div>
                    <div>
                        <div style="font-size:1.4rem; font-weight:900; color:var(--text); line-height:1;"><?= $hombres ?></div>
                        <div style="font-size:0.75rem; font-weight:700; color:var(--muted); margin-top:4px; text-transform:uppercase; letter-spacing:0.04em;">Hombres</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm p-3 h-100" style="background:var(--card-bg); border-radius:16px; border:1px solid var(--border)!important;">
                <div class="d-flex align-items-center gap-3">
                    <div style="width:44px; height:44px; border-radius:12px; background:rgba(239,51,99,0.12); color:var(--accent); display:flex; align-items:center; justify-content:center; font-size:1.3rem; flex-shrink:0;">
                        <i class="bi bi-gender-female"></i>
                    </div>
                    <div>
                        <div style="font-size:1.4rem; font-weight:900; color:var(--text); line-height:1;"><?= $mujeres ?></div>
                        <div style="font-size:0.75rem; font-weight:700; color:var(--muted); margin-top:4px; text-transform:uppercase; letter-spacing:0.04em;">Mujeres</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm p-3 h-100" style="background:var(--card-bg); border-radius:16px; border:1px solid var(--border)!important;">
                <div class="d-flex align-items-center gap-3">
                    <div style="width:44px; height:44px; border-radius:12px; background:rgba(245,158,11,0.12); color:#f59e0b; display:flex; align-items:center; justify-content:center; font-size:1.3rem; flex-shrink:0;">
                        <i class="bi bi-gender-ambiguous"></i>
                    </div>
                    <div>
                        <div style="font-size:1.4rem; font-weight:900; color:var(--text); line-height:1;"><?= $otros ?></div>
                        <div style="font-size:0.75rem; font-weight:700; color:var(--muted); margin-top:4px; text-transform:uppercase; letter-spacing:0.04em;">Otro / N/A</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── TOOLBAR DE ACCIONES MASIVAS ──────────────────────────── -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
        <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="selectAll()" style="border-radius:10px; font-weight:700; font-size:0.82rem; background:var(--card-bg);">
                <i class="bi bi-check-all me-1"></i> Marcar todos
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="deselectAll()" style="border-radius:10px; font-weight:700; font-size:0.82rem; background:var(--card-bg);">
                <i class="bi bi-x-circle me-1"></i> Desmarcar
            </button>
        </div>

        <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn btn-outline-secondary px-3 py-2" onclick="sendToAll()" title="Enviar resumen de noticias ahora a todos los suscriptores" style="border-radius:12px; font-weight:800; font-size:0.88rem; background:var(--card-bg);">
                <i class="bi bi-send-fill text-accent me-1"></i> Enviar Resumen a Todos
            </button>
            <button type="button" class="btn btn-accent px-4 py-2" onclick="sendToSelected()" id="sendSelectedBtn" style="display:none; border-radius:12px; font-weight:800; font-size:0.88rem; box-shadow:0 4px 15px rgba(239,51,99,0.3);">
                <i class="bi bi-envelope-fill me-1"></i> Enviar a seleccionados
            </button>
        </div>
    </div>

    <!-- ── TABLA DE SUSCRIPTORES ───────────────────────────────── -->
    <div class="card border-0 shadow-sm mb-4" style="background:var(--card-bg); border-radius:18px; border:1px solid var(--border)!important; overflow:hidden;">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle m-0 sub-table" style="color:var(--text);">
                    <thead>
                        <tr>
                            <th style="width:44px; text-align:center;">
                                <input type="checkbox" id="selectAllCheckbox" onchange="toggleSelectAll()" style="accent-color:var(--accent); width:16px; height:16px; cursor:pointer;">
                            </th>
                            <th>Nombre Completo</th>
                            <th>Correo Electrónico</th>
                            <th style="width:140px;">Sexo</th>
                            <th style="width:160px;">Fecha de Registro</th>
                            <th style="width:110px; text-align:right;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($suscripciones)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="bi bi-envelope-open" style="font-size:2.5rem; opacity:0.3; display:block; margin-bottom:8px;"></i>
                                    No se encontraron suscriptores registrados.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach($suscripciones as $suscripcion): ?>
                            <tr>
                                <td style="text-align:center;">
                                    <input type="checkbox" class="subscriber-checkbox" value="<?php echo $suscripcion['id_sub']; ?>" onchange="updateSendButton()" style="accent-color:var(--accent); width:16px; height:16px; cursor:pointer;">
                                </td>
                                <td>
                                    <strong style="font-weight:800; font-size:0.92rem; color:var(--text);">
                                        <?php echo htmlspecialchars($suscripcion['nombre_completo']); ?>
                                    </strong>
                                </td>
                                <td>
                                    <span style="font-size:0.88rem; font-weight:600; color:var(--text);">
                                        <?php echo htmlspecialchars($suscripcion['correo']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge" style="background:var(--bg); color:var(--text); border:1px solid var(--border); font-size:0.75rem; font-weight:700; padding:5px 10px; border-radius:8px; text-transform:capitalize;">
                                        <?php echo htmlspecialchars($suscripcion['sexo']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span style="font-size:0.82rem; font-weight:600; color:var(--muted);">
                                        <i class="bi bi-calendar-event me-1"></i> <?php echo date('d/m/Y', strtotime($suscripcion['fecha'])); ?>
                                    </span>
                                </td>
                                <td style="text-align:right;">
                                    <div class="d-inline-flex gap-1">
                                        <form method="POST" action="./../controllers/enviarCorreoSuscriptor.php" class="d-inline m-0">
                                            <input type="hidden" name="id" value="<?php echo $suscripcion['id_sub']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-secondary" style="border-radius:8px; width:32px; height:32px; padding:0; display:inline-flex; align-items:center; justify-content:center; background:var(--bg);" title="Enviar correo a este suscriptor">
                                                <i class="bi bi-envelope-fill text-accent"></i>
                                            </button>
                                        </form>
                                        <?php if($ACL['eliminar']): ?>
                                            <button type="button" class="btn btn-sm btn-outline-danger btn-delete-suscriptor" data-id="<?php echo $suscripcion['id_sub']; ?>" style="border-radius:8px; width:32px; height:32px; padding:0; display:inline-flex; align-items:center; justify-content:center; background:var(--bg);" title="Eliminar">
                                                <i class="bi bi-trash-fill"></i>
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
<div id="modalOverlayS" class="modal-nativo" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; overflow:auto; background-color:rgba(0,0,0,0.6); backdrop-filter:blur(4px); justify-content:center; align-items:center;">
    <div class="modal-content-nativo" style="max-width:440px; border-radius:18px; background:var(--card-bg); overflow:hidden; margin:auto; border:1px solid var(--border);">
        <div class="modal-header-nativo" style="padding:16px 20px; border-bottom:1px solid var(--border); display:flex; justify-content:space-between; align-items:center;">
            <h5 class="m-0 font-weight-bold" style="font-weight:800; color:var(--text);"><i class="bi bi-trash-fill text-danger me-2"></i> Confirmar Eliminación</h5>
            <span class="btn-cancel" style="font-size:24px; font-weight:bold; cursor:pointer; color:var(--muted);">&times;</span>
        </div>
        <div class="modal-body-nativo" style="padding:20px;">
            <p style="color:var(--text); font-size:0.9rem; margin-bottom:15px; font-weight:600;">
                ¿Estás seguro de que deseas eliminar este suscriptor?
            </p>
            <p class="text-muted small mb-4">Esta acción no se puede deshacer y la cuenta del lector dejará de recibir correos del boletín.</p>
            <form id="modalFormS" action="./../controllers/eliminarSuscriptor.php" method="POST">
                <input type="hidden" name="id" id="modalIdS">
                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-secondary px-3 btn-cancel" style="border-radius:10px; font-weight:700;">Cancelar</button>
                    <button type="submit" class="btn btn-danger px-4" style="border-radius:10px; font-weight:800;"><i class="bi bi-trash-fill me-1"></i> Eliminar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.sub-table thead th {
    background: rgba(0, 0, 0, 0.03) !important;
    color: var(--text) !important;
    font-size: 0.75rem !important;
    font-weight: 800 !important;
    text-transform: uppercase !important;
    letter-spacing: 0.05em !important;
    border-bottom: 1px solid var(--border) !important;
    padding: 12px 16px !important;
}
[data-bs-theme="dark"] .sub-table thead th {
    background: rgba(255, 255, 255, 0.04) !important;
}
</style>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const modal = document.getElementById("modalOverlayS");
    const modalId = document.getElementById("modalIdS");
    
    document.querySelectorAll(".btn-delete-suscriptor").forEach(btn => {
        btn.addEventListener("click", (e) => {
            e.stopPropagation();
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

function toggleSelectAll() {
    const checkboxes = document.querySelectorAll('.subscriber-checkbox');
    const selectAllCheckbox = document.getElementById('selectAllCheckbox');
    const allChecked = selectAllCheckbox.checked;
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = allChecked;
    });
    
    updateSendButton();
}

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
        sendBtn.innerHTML = `<i class="bi bi-envelope-fill me-1"></i> Enviar a ${checkboxes.length} seleccionados`;
    } else {
        sendBtn.style.display = 'none';
    }
}

document.querySelectorAll('input[type="checkbox"]').forEach(c => {
    c.addEventListener('click', e => e.stopPropagation());
});
document.querySelectorAll('.sub-table tbody tr').forEach(row => {
    row.addEventListener('click', function(e) {
        if(e.target.tagName === 'BUTTON' || e.target.tagName === 'A' || e.target.tagName === 'I' || e.target.closest('form')) return;
        const cb = this.querySelector('.subscriber-checkbox');
        if(cb) {
            cb.checked = !cb.checked;
            updateSendButton();
        }
    });
    row.style.cursor = 'pointer';
});

function sendToAll() {
    if (!confirm("¿Deseas enviar el resumen diario de noticias inmediatamente a TODOS los suscriptores?")) return;
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = './../controllers/enviarCorreoMultiple.php';
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'ids[]';
    input.value = 'all';
    form.appendChild(input);
    document.body.appendChild(form);
    form.submit();
}

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

document.addEventListener("DOMContentLoaded", () => {
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