<?php
include("./../layout/headerAdmin.php");
include("./../data/conexion.php");

if (empty($ACL['leer'])) {
    header("Location: admin.php");
    exit();
}

$q = trim($_GET['q'] ?? '');
if ($q !== '') {
    $like = "%$q%";
    $stmt = $con->prepare("SELECT * FROM usuarios WHERE nombre LIKE ? OR usuario LIKE ? OR correo LIKE ? ORDER BY registro DESC");
    $stmt->bind_param("sss", $like, $like, $like);
} else {
    $stmt = $con->prepare("SELECT * FROM usuarios ORDER BY registro DESC");
}
$stmt->execute();
$usuarios = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$totalUsuarios = count($usuarios);
$ultimos7dias  = count(array_filter($usuarios, fn($u) => strtotime($u['registro']) >= strtotime('-7 days')));
$superadmins   = count(array_filter($usuarios, fn($u) => $u['id_u'] == 1 || !empty($u['superadmin'])));
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4" style="flex-wrap: wrap; gap: 12px;">
        <h1 style="margin:0;">Gestión de Usuarios</h1>
        <form method="GET" class="admin-search-form" style="display:flex; align-items:center; gap:8px;">
            <i class="bi bi-search admin-search-icon"></i>
            <input type="search" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Buscar por nombre, usuario o email..." class="admin-search-input">
            <?php if ($q): ?><a href="./usuarios.php" class="admin-search-clear">&times;</a><?php endif; ?>
        </form>
    </div>

    <!-- Estadísticas rápidas -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(99,102,241,0.1); color: #6366f1;"><i class="bi bi-people-fill"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?= $totalUsuarios ?></span>
                <span class="stat-label">Total Usuarios</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(16,185,129,0.1); color: #10b981;"><i class="bi bi-person-plus-fill"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?= $ultimos7dias ?></span>
                <span class="stat-label">Nuevos (7 días)</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(245,158,11,0.1); color: #f59e0b;"><i class="bi bi-shield-lock-fill"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?= $superadmins ?></span>
                <span class="stat-label">Admins</span>
            </div>
        </div>
    </div>

    <!-- Toolbar -->
    <div class="contenidos-toolbar">
        <div class="contenidos-tabs">
            <span class="tab-btn active">Todos los usuarios</span>
        </div>
        <div class="contenidos-actions">
            <?php if ($ACL['crear']): ?>
                <a href="./crearu.php" class="btn btn-accent"><i class="bi bi-plus-lg"></i> Crear Usuario</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="contenidos-table">
                    <thead>
                        <tr>
                            <th>Avatar</th>
                            <th>Nombre</th>
                            <th>Usuario</th>
                            <th>Email</th>
                            <th>Fecha Registro</th>
                            <?php if ($ACL['editar'] || $ACL['eliminar'] || $ACL['leer']): ?>
                                <th>Acciones</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($usuarios)): ?>
                            <tr><td colspan="6" style="text-align:center; padding:30px; color:var(--muted);">No se encontraron usuarios.</td></tr>
                        <?php else: ?>
                            <?php foreach ($usuarios as $u): ?>
                            <tr>
                                <td>
                                    <?php if (!empty($u['foto_personal'])): ?>
                                        <img src="<?= imageUrl($u['foto_personal']) ?>" alt="Foto de perfil" style="width:40px; height:40px; border-radius:50%; object-fit:cover;">
                                    <?php else: ?>
                                        <div style="width:40px; height:40px; border-radius:50%; background:var(--accent); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:bold;">
                                            <?= strtoupper(mb_substr($u['nombre'], 0, 1)) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td><strong class="table-title"><?= htmlspecialchars($u['nombre']) ?></strong></td>
                                <td><span class="estado-badge estado-programado" style="background:rgba(99,102,241,0.1); color:#6366f1;">@<?= htmlspecialchars($u['usuario']) ?></span></td>
                                <td><span style="font-size:0.9rem; color:var(--text);"><?= htmlspecialchars($u['correo']) ?></span></td>
                                <td class="table-date"><?= date('d/m/Y', strtotime($u['registro'])) ?></td>
                                <?php if ($ACL['editar'] || $ACL['eliminar'] || $ACL['leer']): ?>
                                    <td>
                                        <div class="noticias-actions" style="border-top:none; padding:0; justify-content:flex-start;">
                                            <?php if ($ACL['editar']): ?>
                                                <a href="./editaru.php?id=<?= $u['id_u'] ?>" class="btn btn-edit" title="Editar Usuario"><i class="bi bi-pencil-square"></i></a>
                                            <?php endif; ?>
                                            <?php if ($ACL['leer']): ?>
                                                <a href="./veru.php?id=<?= $u['id_u'] ?>" class="btn btn-view" title="Ver Usuario"><i class="bi bi-eye"></i></a>
                                            <?php endif; ?>
                                            <?php if ($ACL['eliminar'] && $u['id_u'] != 1): ?>
                                                <button type="button" class="btn btn-delete btn-delete-usuario"
                                                    data-id="<?= $u['id_u'] ?>"
                                                    data-nombre="<?= htmlspecialchars($u['nombre'], ENT_QUOTES) ?>"
                                                    title="Eliminar Usuario"><i class="bi bi-trash"></i></button>
                                            <?php endif; ?>
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

<!-- Modal eliminación usuario -->
<div id="modalOverlayU" class="crop-modal" style="display: none;">
    <div class="crop-modal-content">
        <h3 id="modalTitleU"><i class="bi bi-trash"></i> Confirmar eliminación</h3>
        <p style="color:var(--muted); font-size:0.9rem; margin-bottom:15px; margin-top:5px;">¿Estás seguro de que deseas eliminar este usuario? Esta acción no se puede deshacer.</p>
        <input type="hidden" id="modalIdU">
        <div class="crop-actions" style="display:flex; justify-content:flex-end; gap:8px;">
            <button type="button" class="btn btn-secondary btn-cancel-u">Cancelar</button>
            <button type="button" id="btnConfirmDelete" class="btn btn-accent">Eliminar</button>
        </div>
    </div>
</div>

<?php include("./../layout/footerAdmin.php"); ?>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('modalOverlayU');

    document.querySelectorAll('.btn-delete-usuario').forEach(btn => {
        btn.addEventListener('click', () => {
            document.getElementById('modalIdU').value = btn.dataset.id;
            document.getElementById('modalTitleU').innerHTML =
                `<i class="bi bi-trash"></i> Eliminar "${btn.dataset.nombre}"`;
            modal.style.display = 'flex';
        });
    });

    document.querySelector('.btn-cancel-u').addEventListener('click', () => {
        modal.style.display = 'none';
    });
    modal.addEventListener('click', e => { if (e.target === modal) modal.style.display = 'none'; });

    document.getElementById('btnConfirmDelete').addEventListener('click', () => {
        const id = document.getElementById('modalIdU').value;
        const fd = new FormData();
        fd.append('id', id);
        fetch('./../controllers/eliminarusuario.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(d => {
                if (d.success) { showToast(d.success, 'success'); setTimeout(() => location.reload(), 1000); }
                else showToast(d.error || 'Error al eliminar', 'error');
            })
            .catch(() => showToast('Error en la petición', 'error'));
        modal.style.display = 'none';
    });
});
</script>
