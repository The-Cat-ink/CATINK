<?php
    include("./../layout/headerAdmin.php");
    include("./../controllers/aclcontroller.php");
    $ACL = $_SESSION['ACL']['usuarios']??[
        'crear' => 1,
        'leer' => 2,
        'editar' => 4,
        'eliminar' => 8,
    ];
    if (!($ACL['leer'])) {
        header("Location: admin.php");
        exit();
    }
    include("./../data/conexion.php");
    $id = (int)($_GET['id'] ?? 0);
    $stmt = $con->prepare("SELECT * FROM usuarios WHERE id_u = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $usuario = $stmt->get_result()->fetch_assoc();
    if (!$usuario) {
        header("Location: usuarios.php");
        exit();
    }

    $modulosIconos = [
        'publicidad' => ['nombre' => 'Publicidad', 'icon' => 'bi-megaphone-fill', 'color' => '#8b5cf6'],
        'noticias' => ['nombre' => 'Noticias', 'icon' => 'bi-newspaper', 'color' => '#ec4899'],
        'categorias' => ['nombre' => 'Categorías', 'icon' => 'bi-tags-fill', 'color' => '#3b82f6'],
        'correos' => ['nombre' => 'Correos Publicitarios', 'icon' => 'bi-envelope-paper-fill', 'color' => '#f59e0b'],
        'suscripciones' => ['nombre' => 'Suscripciones', 'icon' => 'bi-people-fill', 'color' => '#10b981'],
        'usuarios' => ['nombre' => 'Administradores / Editores', 'icon' => 'bi-person-gear', 'color' => '#ef3363'],
        'videos' => ['nombre' => 'Videos', 'icon' => 'bi-play-btn-fill', 'color' => '#dc2626'],
        'lectores' => ['nombre' => 'Lectores / Comunidad', 'icon' => 'bi-person-badge-fill', 'color' => '#06b6d4'],
        'recomendados' => ['nombre' => 'Recomendados', 'icon' => 'bi-star-fill', 'color' => '#f59e0b'],
        'esperamos' => ['nombre' => 'Próximos Estrenos', 'icon' => 'bi-hourglass-split', 'color' => '#6366f1'],
        'paginas' => ['nombre' => 'Páginas y Logos', 'icon' => 'bi-file-earmark-code-fill', 'color' => '#64748b'],
        'actividad' => ['nombre' => 'Bitácora de Actividades', 'icon' => 'bi-journal-text', 'color' => '#059669'],
        'papelera' => ['nombre' => 'Papelera de Noticias', 'icon' => 'bi-trash-fill', 'color' => '#dc2626'],
        'avatares' => ['nombre' => 'Fotos de Perfil / Avatares', 'icon' => 'bi-person-circle', 'color' => '#8b5cf6']
    ];
    $acciones = [2 => 'Ver', 1 => 'Crear', 4 => 'Editar', 8 => 'Eliminar'];
?>

<style>
/* ── ESTILOS VISTA DE USUARIO ── */
.user-profile-card {
    border: 1px solid var(--border);
    border-radius: 14px;
    background: var(--card-bg);
    padding: 24px;
    margin-bottom: 24px;
    box-shadow: 0 4px 18px rgba(0, 0, 0, 0.03);
}

.user-profile-header {
    display: flex;
    align-items: center;
    gap: 20px;
    margin-bottom: 20px;
    padding-bottom: 20px;
    border-bottom: 1px solid var(--border);
}

.user-avatar-badge {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    background: linear-gradient(135deg, #EF3363 0%, #ff527b 100%);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
    font-weight: 700;
    box-shadow: 0 4px 14px rgba(239, 51, 99, 0.3);
}

.user-info-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
}

@media (max-width: 768px) {
    .user-info-grid {
        grid-template-columns: 1fr;
    }
}

.user-info-item {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.user-info-item label {
    font-size: 0.78rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: var(--muted);
}

.user-info-item span {
    font-size: 0.95rem;
    font-weight: 600;
    color: var(--text);
}

/* ── TABLA MATRIZ DE PERMISOS READONLY ── */
.perm-matrix-card {
    border: 1px solid var(--border);
    border-radius: 14px;
    background: var(--card-bg);
    overflow: hidden;
    margin-bottom: 24px;
    box-shadow: 0 4px 18px rgba(0,0,0,0.03);
}

.perm-matrix-table {
    width: 100%;
    border-collapse: collapse;
}

.perm-matrix-table th {
    background: rgba(0, 0, 0, 0.02);
    padding: 14px 16px;
    font-size: 0.82rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: var(--muted);
    border-bottom: 1px solid var(--border);
}

.perm-matrix-table td {
    padding: 12px 16px;
    border-bottom: 1px solid var(--border);
    vertical-align: middle;
}

.perm-matrix-table tr:last-child td {
    border-bottom: none;
}

.perm-matrix-table tr:hover td {
    background: rgba(239, 51, 99, 0.02);
}

.mod-icon-box {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 15px;
    flex-shrink: 0;
}

.perm-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 10px;
    border-radius: 999px;
    font-size: 0.78rem;
    font-weight: 600;
}

.perm-badge.active {
    background: rgba(16, 185, 129, 0.12);
    color: #10b981;
    border: 1px solid rgba(16, 185, 129, 0.25);
}

.perm-badge.inactive {
    background: rgba(108, 117, 125, 0.06);
    color: var(--muted);
    border: 1px solid var(--border);
}

.status-tag {
    display: inline-flex;
    align-items: center;
    padding: 4px 10px;
    border-radius: 999px;
    font-size: 0.78rem;
    font-weight: 700;
}

.status-tag.full {
    background: rgba(239, 51, 99, 0.1);
    color: var(--accent);
}

.status-tag.partial {
    background: rgba(245, 158, 11, 0.1);
    color: #d97706;
}

.status-tag.none {
    background: rgba(108, 117, 125, 0.08);
    color: var(--muted);
}
</style>

<div class="container-fluid">
    <!-- CABECERA -->
    <div class="d-flex justify-content-between align-items-center mb-4" style="flex-wrap: wrap; gap: 12px;">
        <div>
            <h1 style="margin:0; font-weight:700;">Perfil de Usuario: <?= htmlspecialchars($usuario['nombre']) ?></h1>
            <p class="text-muted mb-0" style="font-size:0.88rem;">Detalles del administrador y resumen de su matriz de accesos.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="./../views/usuarios.php" class="btn btn-secondary" style="border-radius:999px; padding:8px 18px; font-weight:600;"><i class="bi bi-arrow-left"></i> Volver</a>
            
            <?php if ($ACL['editar']) : ?>
                <a href="./../views/editaru.php?id=<?= $usuario['id_u'] ?>" class="btn btn-accent" style="border-radius:999px; padding:8px 18px; font-weight:600;"><i class="bi bi-pencil-square"></i> Editar Usuario</a>
            <?php endif; ?>

            <?php if ($ACL['eliminar']) : ?>
                <button type="button" class="btn btn-danger btn-delete-usuario" data-id="<?= $usuario['id_u'] ?>" data-nombre="<?= htmlspecialchars($usuario['nombre']) ?>" style="border-radius:999px; padding:8px 18px; font-weight:600;"><i class="bi bi-trash"></i> Eliminar</button>
            <?php endif; ?>
        </div>
    </div>

    <!-- TARJETA INFORMACIÓN GENERAL -->
    <div class="user-profile-card">
        <div class="user-profile-header">
            <div class="user-avatar-badge">
                <?= strtoupper(substr($usuario['nombre'], 0, 1)) ?>
            </div>
            <div>
                <h4 style="margin:0; font-weight:700; color:var(--text);"><?= htmlspecialchars($usuario['nombre']) ?></h4>
                <p class="text-muted mb-0" style="font-size:0.9rem;">@<?= htmlspecialchars($usuario['usuario']) ?></p>
            </div>
        </div>

        <div class="user-info-grid">
            <div class="user-info-item">
                <label>ID de Usuario</label>
                <span>#<?= htmlspecialchars($usuario['id_u']) ?></span>
            </div>
            <div class="user-info-item">
                <label>Nombre de Usuario</label>
                <span><?= htmlspecialchars($usuario['usuario']) ?></span>
            </div>
            <div class="user-info-item">
                <label>Correo Electrónico</label>
                <span><?= htmlspecialchars($usuario['correo']) ?></span>
            </div>
        </div>
    </div>

    <!-- MATRIZ DE PERMISOS READONLY -->
    <div class="perm-matrix-card">
        <div class="d-flex justify-content-between align-items-center" style="padding: 16px 20px; border-bottom: 1px solid var(--border); background: rgba(0,0,0,0.02);">
            <h5 class="mb-0" style="font-weight: 700; font-size: 1.05rem; display: flex; align-items: center; gap: 8px;">
                <i class="bi bi-shield-check text-accent"></i> Matriz de Accesos Asignados
            </h5>
            <span class="text-muted" style="font-size: 0.8rem;">Visualización de lectura</span>
        </div>

        <div class="table-responsive">
            <table class="perm-matrix-table">
                <thead>
                    <tr>
                        <th style="padding-left: 20px;">Módulo / Sección</th>
                        <th class="text-center"><span style="color:#0284c7;"><i class="bi bi-eye-fill"></i> Ver</span></th>
                        <th class="text-center"><span style="color:#16a34a;"><i class="bi bi-plus-circle-fill"></i> Crear</span></th>
                        <th class="text-center"><span style="color:#d97706;"><i class="bi bi-pencil-fill"></i> Editar</span></th>
                        <th class="text-center"><span style="color:#dc2626;"><i class="bi bi-trash-fill"></i> Eliminar</span></th>
                        <th class="text-center" style="padding-right: 20px;">Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    foreach ($modulosIconos as $slug => $mInfo):
                        $permActual = (int)($usuario['perm_' . $slug] ?? 0);
                    ?>
                    <tr>
                        <td style="padding-left: 20px;">
                            <div class="d-flex align-items-center gap-2">
                                <div class="mod-icon-box" style="background: <?= $mInfo['color'] ?>15; color: <?= $mInfo['color'] ?>;">
                                    <i class="bi <?= $mInfo['icon'] ?>"></i>
                                </div>
                                <span class="fw-semibold text-color" style="font-size: 0.9rem;"><?= $mInfo['nombre'] ?></span>
                            </div>
                        </td>

                        <?php foreach ($acciones as $bit => $etiqueta):
                            $hasPerm = ($permActual & $bit);
                        ?>
                        <td class="text-center">
                            <?php if ($hasPerm): ?>
                                <span class="perm-badge active"><i class="bi bi-check-lg"></i> Permitido</span>
                            <?php else: ?>
                                <span class="perm-badge inactive"><i class="bi bi-x-lg"></i> Sin acceso</span>
                            <?php endif; ?>
                        </td>
                        <?php endforeach; ?>

                        <td class="text-center" style="padding-right: 20px;">
                            <?php if ($permActual === 15): ?>
                                <span class="status-tag full"><i class="bi bi-shield-fill-check me-1"></i> Acceso Total</span>
                            <?php elseif ($permActual > 0): ?>
                                <span class="status-tag partial"><i class="bi bi-shield-half me-1"></i> Parcial</span>
                            <?php else: ?>
                                <span class="status-tag none"><i class="bi bi-shield-x me-1"></i> Sin Acceso</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal eliminación usuario -->
<div id="modalOverlayU" class="crop-modal" style="display: none;">
    <div class="crop-modal-content">
        <h3 id="modalTitleU">Confirmar eliminación</h3>
        <p>¿Estás seguro de que deseas eliminar este usuario? Esta acción no se puede deshacer.</p>
        <form id="modalFormU" action="./../controllers/eliminarusuario.php?id=<?= $usuario['id_u'] ?>" method="POST">
            <input type="hidden" name="id" id="modalIdU">
            <div class="crop-actions">
                <button type="button" class="btn btn-secondary btn-cancel">Cancelar</button>
                <button type="submit" class="btn btn-danger">Eliminar</button>
            </div>
        </form>
    </div>
</div>

<?php include("./../layout/footerAdmin.php"); ?>