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
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4" style="flex-wrap: wrap; gap: 12px;">
        <h1 style="margin:0;">Gestión de Correos Publicitarios</h1>
        <form method="GET" class="admin-search-form" style="display:flex; align-items:center; gap:8px;">
            <i class="bi bi-search admin-search-icon"></i>
            <input type="search" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Buscar por título o contenido..." class="admin-search-input">
            <?php if($q): ?><a href="./correos.php" class="admin-search-clear">&times;</a><?php endif; ?>
        </form>
    </div>

    <!-- Estadísticas rápidas -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(99,102,241,0.1); color: #6366f1;"><i class="bi bi-envelope"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?= $totalCorreos ?></span>
                <span class="stat-label">Total Correos</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(16,185,129,0.1); color: #10b981;"><i class="bi bi-send-check"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?= $enviados ?></span>
                <span class="stat-label">Enviados</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(245,158,11,0.1); color: #f59e0b;"><i class="bi bi-hourglass-split"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?= $pendientes ?></span>
                <span class="stat-label">Pendientes</span>
            </div>
        </div>
    </div>

    <!-- Toolbar -->
    <div class="contenidos-toolbar">
        <div class="contenidos-tabs">
            <span class="tab-btn active">Todos los correos</span>
        </div>
        <div class="contenidos-actions">
            <?php if ($ACL['crear']): ?>
                <a href="correo_pub.php" class="btn btn-accent"><i class="bi bi-plus-lg"></i> Nuevo Correo</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="contenidos-table">
                    <thead>
                        <tr>
                            <th>Título</th>
                            <th>Contenido Corto</th>
                            <th>Estado Envío</th>
                            <th>Programado Para</th>
                            <?php if(!empty($ACL['editar']) || !empty($ACL['eliminar'])): ?>
                                <th>Acciones</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($correos)): ?>
                            <tr><td colspan="5" style="text-align:center; padding:30px; color:var(--muted);">No se encontraron correos publicitarios.</td></tr>
                        <?php else: ?>
                            <?php foreach($correos as $correo): ?>
                                <tr>
                                    <td><strong class="table-title"><?= htmlspecialchars($correo['titulo']) ?></strong></td>
                                    <td><span style="font-size:0.85rem; color:var(--muted);"><?= mb_strimwidth(htmlspecialchars(strip_tags($correo['contenido'])), 0, 50, '...') ?></span></td>
                                    <td>
                                        <?php if(($correo['enviado'] ?? 0) == 1): ?>
                                            <span class="estado-badge estado-publicado"><i class="bi bi-check-circle-fill"></i> Enviado</span>
                                        <?php else: ?>
                                            <span class="estado-badge estado-por_publicar"><i class="bi bi-clock-fill"></i> Pendiente</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="table-date"><?= !empty($correo['envio']) ? date('d/m/Y H:i', strtotime($correo['envio'])) : '-' ?></td>
                                    <?php if(!empty($ACL['editar']) || !empty($ACL['eliminar'])): ?>
                                        <td>
                                            <div class="noticias-actions" style="border-top:none; padding:0; justify-content:flex-start;">
                                                <?php if(!empty($ACL['editar'])): ?>
                                                    <a href="correo_pub_edit.php?id=<?= $correo['id_correo'] ?>" class="btn btn-edit" title="Editar"><i class="bi bi-pencil-square"></i></a>
                                                <?php endif;?>
                                                <?php if(!empty($ACL['eliminar'])): ?>
                                                    <button type="button" class="btn btn-delete btnEliminarCorreo" data-id="<?= $correo['id_correo'] ?>" title="Eliminar"><i class="bi bi-trash"></i></button>
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

<div id="modalCorreo" class="crop-modal" style="display:none;">
    <div class="crop-modal-content">
        <h3><i class="bi bi-trash"></i> Confirmar eliminación</h3>
        <p style="color:var(--muted); font-size:0.9rem; margin-bottom:15px; margin-top:5px;">
            ¿Estás seguro de que deseas eliminar este correo publicitario?
            Esta acción eliminará también su imagen adjunta si existe.
        </p>

        <form id="modalForm" action="./../controllers/eliminarCorreoPub.php" method="POST">
            <input type="hidden" name="id" id="modalIdCorreo">

            <div class="crop-actions" style="display:flex; justify-content:flex-end; gap:8px;">
                <button type="button" class="btn btn-secondary" id="btnCancel">Cancelar</button>
                <button type="submit" class="btn btn-accent">Eliminar</button>
            </div>
        </form>
    </div>
</div>
<script>
document.addEventListener("DOMContentLoaded", () => {
    const modal = document.getElementById("modalCorreo");
    const modalIdCorreo = document.getElementById("modalIdCorreo");
    const cancelBtn = document.getElementById("btnCancel");
    document.querySelectorAll(".btnEliminarCorreo").forEach(btn => {
        btn.addEventListener("click", () => {
            const id = btn.dataset.id;
            modalIdCorreo.value = id;
            modal.style.display = "flex";
        });
    });
    cancelBtn.addEventListener("click", () => {
        modal.style.display = "none";
    });
    window.addEventListener("click", (e) => {
        if(e.target === modal){
            modal.style.display = "none";
        }
    });

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