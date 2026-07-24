<?php
    include("./../layout/headerAdmin.php");
    include("./../data/conexion.php");
    $ACL = $_SESSION['ACL']['correos'] ?? [
        "crear" => false,
        "leer" => false,
        "editar" => false,
        "eliminar" => false
    ];
    if(!$ACL['leer']) {
        header("Location: admin.php");
        exit();
    }
?>
<script>
    const ACL = <?= json_encode($ACL) ?>;
</script>
<?php
    $q = trim($_GET['q'] ?? '');
    if($q !== ''){
        $like = "%$q%";
        $sql = $con->prepare("SELECT * FROM correos_publicitarios WHERE titulo LIKE ? OR contenido LIKE ? ORDER BY creado DESC");
        $sql->bind_param("ss", $like, $like);
    } else {
        $sql = $con->prepare("SELECT * FROM correos_publicitarios ORDER BY creado DESC");
    }
    $sql->execute();
    $result = $sql->get_result();
    $correos = $result->fetch_all(MYSQLI_ASSOC);

    // Estadísticas
    $totalCorreos = count($correos);
    $enviados = count(array_filter($correos, fn($c) => isset($c['enviado']) && $c['enviado'] == 1));
    $pendientes = $totalCorreos - $enviados;
?>
<div class="container-fluid px-3 py-2">

    <!-- ── ENCABEZADO Y BÚSQUEDA ───────────────────────────────── -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <h1 style="font-weight:900; font-size:1.8rem; margin:0; color:var(--text); letter-spacing:-0.02em;">
                    Gestión de Correos Publicitarios
                </h1>
                <span class="badge" style="background:rgba(239,51,99,0.12); color:var(--accent); border:1px solid rgba(239,51,99,0.25); border-radius:20px; padding:4px 10px; font-weight:800; font-size:0.72rem;">
                    <?= $totalCorreos ?> Plantillas / Envíos
                </span>
            </div>
            <p class="text-muted m-0" style="font-size:0.88rem;">Crea y gestiona plantillas personalizadas de correo masivo para campañas o avisos.</p>
        </div>

        <form method="GET" class="d-flex align-items-center gap-2 m-0">
            <div style="position:relative; width:280px;">
                <i class="bi bi-search" style="position:absolute; left:14px; top:50%; transform:translateY(-50%); color:var(--muted); font-size:0.9rem;"></i>
                <input type="search" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Buscar por título..." class="cn-input" style="padding-left:38px; padding-right:<?= $q ? '36px' : '14px' ?>; border-radius:12px; font-size:0.88rem;">
                <?php if($q): ?>
                    <a href="./correos.php" style="position:absolute; right:12px; top:50%; transform:translateY(-50%); color:var(--muted); text-decoration:none; font-weight:bold; font-size:1.1rem;">&times;</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- ── TARJETAS DE ESTADÍSTICAS RÁPIDAS ────────────────────── -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-4">
            <div class="card border-0 shadow-sm p-3 h-100" style="background:var(--card-bg); border-radius:16px; border:1px solid var(--border)!important;">
                <div class="d-flex align-items-center gap-3">
                    <div style="width:44px; height:44px; border-radius:12px; background:rgba(99,102,241,0.12); color:#6366f1; display:flex; align-items:center; justify-content:center; font-size:1.3rem; flex-shrink:0;">
                        <i class="bi bi-envelope-fill"></i>
                    </div>
                    <div>
                        <div style="font-size:1.4rem; font-weight:900; color:var(--text); line-height:1;"><?= $totalCorreos ?></div>
                        <div style="font-size:0.75rem; font-weight:700; color:var(--muted); margin-top:4px; text-transform:uppercase; letter-spacing:0.04em;">Total Correos</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-4">
            <div class="card border-0 shadow-sm p-3 h-100" style="background:var(--card-bg); border-radius:16px; border:1px solid var(--border)!important;">
                <div class="d-flex align-items-center gap-3">
                    <div style="width:44px; height:44px; border-radius:12px; background:rgba(16,185,129,0.12); color:#10b981; display:flex; align-items:center; justify-content:center; font-size:1.3rem; flex-shrink:0;">
                        <i class="bi bi-send-check-fill"></i>
                    </div>
                    <div>
                        <div style="font-size:1.4rem; font-weight:900; color:var(--text); line-height:1;"><?= $enviados ?></div>
                        <div style="font-size:0.75rem; font-weight:700; color:var(--muted); margin-top:4px; text-transform:uppercase; letter-spacing:0.04em;">Enviados</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-4">
            <div class="card border-0 shadow-sm p-3 h-100" style="background:var(--card-bg); border-radius:16px; border:1px solid var(--border)!important;">
                <div class="d-flex align-items-center gap-3">
                    <div style="width:44px; height:44px; border-radius:12px; background:rgba(245,158,11,0.12); color:#f59e0b; display:flex; align-items:center; justify-content:center; font-size:1.3rem; flex-shrink:0;">
                        <i class="bi bi-hourglass-split"></i>
                    </div>
                    <div>
                        <div style="font-size:1.4rem; font-weight:900; color:var(--text); line-height:1;"><?= $pendientes ?></div>
                        <div style="font-size:0.75rem; font-weight:700; color:var(--muted); margin-top:4px; text-transform:uppercase; letter-spacing:0.04em;">Pendientes</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── TOOLBAR DE ACCIÓN ───────────────────────────────────── -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
        <h5 class="m-0 font-weight-bold" style="font-weight:800; font-size:1.05rem; color:var(--text);">
            Listado de Correos Registrados
        </h5>

        <?php if ($ACL['crear']): ?>
            <a href="correo_pub.php" class="btn btn-accent px-4 py-2" style="border-radius:12px; font-weight:800; font-size:0.9rem; box-shadow:0 4px 15px rgba(239,51,99,0.3);">
                <i class="bi bi-plus-lg me-1"></i> Nuevo Correo Publicitario
            </a>
        <?php endif; ?>
    </div>

    <!-- ── TABLA DE CORREOS ────────────────────────────────────── -->
    <div class="card border-0 shadow-sm mb-4" style="background:var(--card-bg); border-radius:18px; border:1px solid var(--border)!important; overflow:hidden;">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle m-0 correos-table" style="color:var(--text);">
                    <thead>
                        <tr>
                            <th>Título del Asunto</th>
                            <th>Vista Previa del Contenido</th>
                            <th style="width:140px;">Estado Envío</th>
                            <th style="width:170px;">Programado Para</th>
                            <?php if(!empty($ACL['editar']) || !empty($ACL['eliminar'])): ?>
                                <th style="width:110px; text-align:right;">Acciones</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($correos)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <i class="bi bi-envelope-paper" style="font-size:2.5rem; opacity:0.3; display:block; margin-bottom:8px;"></i>
                                    No se encontraron correos publicitarios registrados.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach($correos as $correo): ?>
                                <tr>
                                    <td>
                                        <strong style="font-weight:800; font-size:0.92rem; color:var(--text);">
                                            <?= htmlspecialchars($correo['titulo']) ?>
                                        </strong>
                                    </td>
                                    <td>
                                        <span class="text-muted" style="font-size:0.82rem;">
                                            <?= mb_strimwidth(htmlspecialchars(strip_tags($correo['contenido'])), 0, 60, '...') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if(($correo['enviado'] ?? 0) == 1): ?>
                                            <span class="badge" style="background:rgba(16,185,129,0.12); color:#10b981; border:1px solid rgba(16,185,129,0.25); font-size:0.78rem; font-weight:800; padding:6px 12px; border-radius:20px;">
                                                <i class="bi bi-check-circle-fill me-1"></i> Enviado
                                            </span>
                                        <?php else: ?>
                                            <span class="badge" style="background:rgba(245,158,11,0.12); color:#f59e0b; border:1px solid rgba(245,158,11,0.25); font-size:0.78rem; font-weight:800; padding:6px 12px; border-radius:20px;">
                                                <i class="bi bi-clock-fill me-1"></i> Pendiente
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span style="font-size:0.82rem; font-weight:600; color:var(--muted);">
                                            <i class="bi bi-calendar-event me-1"></i> <?= !empty($correo['envio']) ? date('d/m/Y H:i', strtotime($correo['envio'])) : '-' ?>
                                        </span>
                                    </td>
                                    <?php if(!empty($ACL['editar']) || !empty($ACL['eliminar'])): ?>
                                        <td style="text-align:right;">
                                            <div class="d-inline-flex gap-1">
                                                <?php if(!empty($ACL['editar'])): ?>
                                                    <a href="correo_pub_edit.php?id=<?= $correo['id_correo'] ?>" class="btn btn-sm btn-outline-secondary" style="border-radius:8px; width:32px; height:32px; padding:0; display:inline-flex; align-items:center; justify-content:center; background:var(--bg);" title="Editar">
                                                        <i class="bi bi-pencil-square"></i>
                                                    </a>
                                                <?php endif;?>
                                                <?php if(!empty($ACL['eliminar'])): ?>
                                                    <button type="button" class="btn btn-sm btn-outline-danger btnEliminarCorreo" data-id="<?= $correo['id_correo'] ?>" style="border-radius:8px; width:32px; height:32px; padding:0; display:inline-flex; align-items:center; justify-content:center; background:var(--bg);" title="Eliminar">
                                                        <i class="bi bi-trash-fill"></i>
                                                    </button>
                                                <?php endif;?>
                                            </div>
                                        </td>
                                    <?php endif; ?>
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
<div id="modalCorreo" class="modal-nativo" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; overflow:auto; background-color:rgba(0,0,0,0.6); backdrop-filter:blur(4px); justify-content:center; align-items:center;">
    <div class="modal-content-nativo" style="max-width:440px; border-radius:18px; background:var(--card-bg); overflow:hidden; margin:auto; border:1px solid var(--border);">
        <div class="modal-header-nativo" style="padding:16px 20px; border-bottom:1px solid var(--border); display:flex; justify-content:space-between; align-items:center;">
            <h5 class="m-0 font-weight-bold" style="font-weight:800; color:var(--text);"><i class="bi bi-trash-fill text-danger me-2"></i> Confirmar Eliminación</h5>
            <span id="btnCancel" style="font-size:24px; font-weight:bold; cursor:pointer; color:var(--muted);">&times;</span>
        </div>
        <div class="modal-body-nativo" style="padding:20px;">
            <p style="color:var(--text); font-size:0.9rem; margin-bottom:15px; font-weight:600;">
                ¿Estás seguro de que deseas eliminar este correo publicitario?
            </p>
            <p class="text-muted small mb-4">Esta acción no se puede deshacer y la plantilla junto con su imagen adjunta se eliminarán del servidor.</p>
            <form id="modalForm" action="./../controllers/eliminarCorreoPub.php" method="POST">
                <input type="hidden" name="id" id="modalIdCorreo">
                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-secondary px-3" id="btnCancelModal" style="border-radius:10px; font-weight:700;">Cancelar</button>
                    <button type="submit" class="btn btn-danger px-4" style="border-radius:10px; font-weight:800;"><i class="bi bi-trash-fill me-1"></i> Eliminar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.correos-table thead th {
    background: rgba(0, 0, 0, 0.03) !important;
    color: var(--text) !important;
    font-size: 0.75rem !important;
    font-weight: 800 !important;
    text-transform: uppercase !important;
    letter-spacing: 0.05em !important;
    border-bottom: 1px solid var(--border) !important;
    padding: 12px 16px !important;
}
[data-bs-theme="dark"] .correos-table thead th {
    background: rgba(255, 255, 255, 0.04) !important;
}
</style>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const modal = document.getElementById("modalCorreo");
    const modalIdCorreo = document.getElementById("modalIdCorreo");
    const cancelBtn = document.getElementById("btnCancel");
    const btnCancelModal = document.getElementById("btnCancelModal");

    document.querySelectorAll(".btnEliminarCorreo").forEach(btn => {
        btn.addEventListener("click", () => {
            const id = btn.dataset.id;
            modalIdCorreo.value = id;
            modal.style.display = "flex";
        });
    });

    if(cancelBtn) cancelBtn.addEventListener("click", () => modal.style.display = "none");
    if(btnCancelModal) btnCancelModal.addEventListener("click", () => modal.style.display = "none");

    window.addEventListener("click", (e) => {
        if(e.target === modal){
            modal.style.display = "none";
        }
    });

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
        if (val === '1' || val === 'creado') text = 'Correo publicitario programado correctamente';
        else if (val.length > 2) text = val;
        showToast(text, 'success');
    }
    if (urlParams.has('error')) {
        const val = urlParams.get('error');
        let text = 'Ocurrió un error al procesar el correo';
        if (val.length > 2 && val !== '1') text = val;
        showToast(text, 'error');
    }
});
</script>

<?php include("./../layout/footerAdmin.php") ?>