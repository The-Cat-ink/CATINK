<?php
    include("./../layout/headerAdmin.php");
    include("./../data/conexion.php");
    $ACL = $_SESSION['ACL']['publicidad'] ?? [
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
<?php
    $q = trim($_GET['q'] ?? '');
    if($q !== ''){
        $like = "%$q%";
        $sql = "SELECT * FROM publicidad WHERE titulo LIKE ? ORDER BY fecha_inicio DESC";
        $result = $con->prepare($sql);
        $result->bind_param("s", $like);
    } else {
        $sql = "SELECT * FROM publicidad ORDER BY fecha_inicio DESC";
        $result = $con->prepare($sql);
    }
    $result->execute();
    $res = $result->get_result();
    $publicidades = $res->fetch_all(MYSQLI_ASSOC);

    $hoy = date('Y-m-d');
    $totalPublicidad = count($publicidades);
    $activas = count(array_filter($publicidades, function($p) use ($hoy) {
        return $p['fecha_inicio'] <= $hoy && $p['fecha_fin'] >= $hoy;
    }));
    $programadas = count(array_filter($publicidades, function($p) use ($hoy) {
        return $p['fecha_inicio'] > $hoy;
    }));
    $vencidas = $totalPublicidad - $activas - $programadas;
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4" style="flex-wrap: wrap; gap: 12px;">
        <h1 style="margin:0;">Gestión de Publicidad</h1>
        <form method="GET" class="admin-search-form" style="display:flex; align-items:center; gap:8px;">
            <i class="bi bi-search admin-search-icon"></i>
            <input type="search" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Buscar publicidad..." class="admin-search-input">
            <?php if($q): ?><a href="./publicidad.php" class="admin-search-clear">&times;</a><?php endif; ?>
        </form>
    </div>

    <!-- Estadísticas rápidas -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(99,102,241,0.1); color: #6366f1;"><i class="bi bi-megaphone"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?= $totalPublicidad ?></span>
                <span class="stat-label">Total Campañas</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(16,185,129,0.1); color: #10b981;"><i class="bi bi-play-circle"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?= $activas ?></span>
                <span class="stat-label">Activas</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(245,158,11,0.1); color: #f59e0b;"><i class="bi bi-calendar-event"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?= $programadas ?></span>
                <span class="stat-label">Programadas</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(239,51,99,0.1); color: #EF3363;"><i class="bi bi-stop-circle"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?= $vencidas ?></span>
                <span class="stat-label">Vencidas</span>
            </div>
        </div>
    </div>

    <!-- Toolbar -->
    <div class="contenidos-toolbar">
        <div class="contenidos-tabs">
            <span class="tab-btn active">Todas las campañas</span>
        </div>
        <div class="contenidos-actions">
            <?php if($ACL['crear']): ?>
                <a href="crearp.php" class="btn btn-accent"><i class="bi bi-plus-lg"></i> Nueva Publicidad</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="contenidos-table">
                    <thead>
                        <tr>
                            <th>Imagen</th>
                            <th>Título</th>
                            <th>Tipo</th>
                            <th>Estado</th>
                            <th>Fechas</th>
                            <?php if(!empty($ACL['editar']) || !empty($ACL['eliminar']) || !empty($ACL['leer'])): ?>
                                <th>Acciones</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($publicidades)): ?>
                            <tr><td colspan="6" style="text-align:center; padding:30px; color:var(--muted);">No se encontraron campañas de publicidad.</td></tr>
                        <?php else: ?>
                            <?php foreach($publicidades as $pub):
                                $estado = 'vencida';
                                if ($pub['fecha_inicio'] > $hoy) $estado = 'programada';
                                else if ($pub['fecha_fin'] >= $hoy) $estado = 'activa';
                            ?>
                                <tr>
                                    <td><img src="<?= imageUrl($pub['imagen']) ?>" alt="Publicidad" class="table-thumb" style="object-fit:contain;" loading="lazy" decoding="async"></td>
                                    <td><strong class="table-title"><?= htmlspecialchars($pub['titulo']) ?></strong><div style="font-size:0.75rem; color:var(--muted);"><a href="<?= $pub['url'] ?>" target="_blank" style="color:inherit;">Enlace <i class="bi bi-box-arrow-up-right"></i></a></div></td>
                                    <td><span style="font-size:0.85rem; color:var(--muted);"><?= $pub['tipo'] == 1 ? 'Banner Largo' : 'Banner Cuadrado' ?></span></td>
                                    <td>
                                        <?php if($estado === 'activa'): ?>
                                            <span class="estado-badge estado-publicado"><i class="bi bi-play-circle-fill"></i> Activa</span>
                                        <?php elseif($estado === 'programada'): ?>
                                            <span class="estado-badge estado-programado"><i class="bi bi-calendar-event-fill"></i> Programada</span>
                                        <?php else: ?>
                                            <span class="estado-badge estado-por_publicar" style="background:rgba(239,51,99,0.1); color:#EF3363;"><i class="bi bi-stop-circle-fill"></i> Vencida</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="table-date">
                                        Inicio: <?= date("d/m/Y", strtotime($pub['fecha_inicio'])) ?><br>
                                        Fin: <?= date("d/m/Y", strtotime($pub['fecha_fin'])) ?>
                                    </td>
                                    <?php if(!empty($ACL['editar']) || !empty($ACL['eliminar']) || !empty($ACL['leer'])): ?>
                                        <td>
                                            <div class="noticias-actions" style="border-top:none; padding:0; justify-content:flex-start;">
                                                <?php if($ACL['editar']): ?>
                                                    <a href="editarp.php?id=<?= $pub['id_pub'] ?>" class="btn btn-edit" title="Editar"><i class="bi bi-pencil-square"></i></a>
                                                <?php endif; ?>
                                                <?php if($ACL['leer']): ?>
                                                    <a href="verp.php?id=<?= $pub['id_pub'] ?>" class="btn btn-view" title="Ver Estadísticas"><i class="bi bi-bar-chart"></i></a> 
                                                <?php endif; ?>
                                                <?php if($ACL['eliminar']): ?>
                                                    <button type="button" class="btn btn-delete btn-delete-publicidad" data-id="<?= $pub['id_pub'] ?>" data-titulo="<?= htmlspecialchars($pub['titulo']) ?>" title="Eliminar"><i class="bi bi-trash"></i></button>
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

<!-- Modal de Confirmación para Eliminar -->
<div id="modalOverlayP" class="crop-modal" style="display: none;">
    <div class="crop-modal-content">
        <h3 id="modalTitleP"><i class="bi bi-trash"></i> Confirmar eliminación</h3>
        <p style="color:var(--muted); font-size:0.9rem; margin-bottom:15px; margin-top:5px;">¿Estás seguro de que deseas eliminar esta publicidad? Esta acción no se puede deshacer y su imagen adjunta también se perderá.</p>
        <form id="modalFormP" action="../controllers/eliminarp.php" method="POST">
            <input type="hidden" name="id" id="modalIdP">
            <div class="crop-actions" style="display:flex; justify-content:flex-end; gap:8px;">
                <button type="button" class="btn btn-secondary btn-cancel">Cancelar</button>
                <button type="submit" class="btn btn-accent">Eliminar</button>
            </div>
        </form>
    </div>
</div>
<?php
    include("./../layout/footerAdmin.php");
?>