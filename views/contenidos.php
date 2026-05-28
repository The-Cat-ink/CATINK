<?php
include("./../layout/headerAdmin.php");
include("./../data/conexion.php");
if (empty($ACL['leer'])) {
    header("Location: admin.php");
    exit();
}
// ============================
// Parámetros
// ============================
$weekOffset = isset($_GET['week']) ? (int)$_GET['week'] : 0;
$q = trim($_GET['q'] ?? '');
$filtro = $_GET['filtro'] ?? 'todos';
$vista = $_GET['vista'] ?? 'calendario';
$busquedaGlobal = isset($_GET['global']) && $_GET['global'] === '1';

// ============================
// Cálculo de semanas (ISO: lunes a domingo)
// ============================
$ref = new DateTime();
// Obtener el lunes de la semana actual de forma confiable
$dayOfWeek = (int)$ref->format('N'); // 1=lun, 7=dom
$ref->modify('-' . ($dayOfWeek - 1) . ' days'); // ahora es lunes
$startOfWeek = clone $ref;
$startOfWeek->modify(($weekOffset * 7) . ' days');
$endOfWeek = clone $startOfWeek;
$endOfWeek->modify('+6 days');
$fechaInicio = $startOfWeek->format('Y-m-d 00:00:00');
$fechaFin = $endOfWeek->format('Y-m-d 23:59:59');
$hoy = (new DateTime())->format('Y-m-d');

// Inicializamos array por día
$newsByDate = [];
$period = new DatePeriod(
    $startOfWeek,
    new DateInterval('P1D'),
    (clone $endOfWeek)->modify('+1 day')
);
foreach ($period as $day) {
    $newsByDate[$day->format('Y-m-d')] = [];
}

// ============================
// Query principal
// ============================
$allNews = [];
if ($busquedaGlobal && $q !== '') {
    $like = "%$q%";
    $sql = "SELECT id, titulo, descripcion, fecha_publicacion, vistas, likes, crop3 
            FROM noticias 
            WHERE titulo LIKE ?
            ORDER BY fecha_publicacion DESC
            LIMIT 50";
    $stmt = $con->prepare($sql);
    $stmt->bind_param("s", $like);
} elseif ($q !== '') {
    $like = "%$q%";
    $sql = "SELECT id, titulo, descripcion, fecha_publicacion, vistas, likes, crop3 
            FROM noticias 
            WHERE fecha_publicacion BETWEEN ? AND ? AND titulo LIKE ?
            ORDER BY fecha_publicacion ASC";
    $stmt = $con->prepare($sql);
    $stmt->bind_param("sss", $fechaInicio, $fechaFin, $like);
} else {
    $sql = "SELECT id, titulo, descripcion, fecha_publicacion, vistas, likes, crop3 
            FROM noticias 
            WHERE fecha_publicacion BETWEEN ? AND ?
            ORDER BY fecha_publicacion ASC";
    $stmt = $con->prepare($sql);
    $stmt->bind_param("ss", $fechaInicio, $fechaFin);
}
$stmt->execute();
$result = $stmt->get_result();
$ahora = new DateTime();

while ($row = $result->fetch_assoc()) {
    $fp = new DateTime($row['fecha_publicacion']);
    if ($fp < $ahora) {
        $row['_estado'] = 'publicado';
    } elseif ($fp->format('Y-m-d') === $hoy) {
        $row['_estado'] = 'por_publicar';
    } else {
        $row['_estado'] = 'programado';
    }
    $allNews[] = $row;
    if (!$busquedaGlobal) {
        $newsByDate[date('Y-m-d', strtotime($row['fecha_publicacion']))][] = $row;
    }
}

// Filtro por estado
if ($filtro !== 'todos') {
    $allNews = array_filter($allNews, fn($n) => $n['_estado'] === $filtro);
    if (!$busquedaGlobal) {
        foreach ($newsByDate as &$dayList) {
            $dayList = array_filter($dayList, fn($n) => $n['_estado'] === $filtro);
        }
        unset($dayList);
    }
}

// ============================
// Estadísticas de la semana
// ============================
$totalNoticias = count($allNews);
$totalVistas = array_sum(array_column($allNews, 'vistas'));
$totalLikes = array_sum(array_column($allNews, 'likes'));
$totalProgramadas = count(array_filter($allNews, fn($n) => $n['_estado'] !== 'publicado'));

// Días de la semana en español
$diasSemana = ['Mon' => 'Lun', 'Tue' => 'Mar', 'Wed' => 'Mié', 'Thu' => 'Jue', 'Fri' => 'Vie', 'Sat' => 'Sáb', 'Sun' => 'Dom'];
?>
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4" style="flex-wrap: wrap; gap: 12px;">
        <h1 style="margin:0;">Gestión de Contenidos</h1>
        <form method="GET" class="admin-search-form" style="display:flex; align-items:center; gap:8px;">
            <i class="bi bi-search admin-search-icon"></i>
            <input type="search" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Buscar por título..." class="admin-search-input">
            <label style="font-size:0.8rem; display:flex; align-items:center; gap:4px; cursor:pointer; white-space:nowrap;">
                <input type="checkbox" name="global" value="1" <?= $busquedaGlobal ? 'checked' : '' ?> style="accent-color:var(--accent);"> Global
            </label>
            <?php if($q): ?><a href="?week=<?= $weekOffset ?>" class="admin-search-clear">&times;</a><?php endif; ?>
            <input type="hidden" name="week" value="<?= $weekOffset ?>">
            <input type="hidden" name="vista" value="<?= $vista ?>">
            <input type="hidden" name="filtro" value="<?= $filtro ?>">
        </form>
    </div>

    <!-- Estadísticas rápidas -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(99,102,241,0.1); color: #6366f1;"><i class="bi bi-newspaper"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?= $totalNoticias ?></span>
                <span class="stat-label">Noticias</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(16,185,129,0.1); color: #10b981;"><i class="bi bi-eye"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?= number_format($totalVistas) ?></span>
                <span class="stat-label">Vistas</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(239,51,99,0.1); color: #EF3363;"><i class="bi bi-heart"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?= number_format($totalLikes) ?></span>
                <span class="stat-label">Likes</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(245,158,11,0.1); color: #f59e0b;"><i class="bi bi-clock-history"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?= $totalProgramadas ?></span>
                <span class="stat-label">Programadas</span>
            </div>
        </div>
    </div>

    <!-- Toolbar: filtros + vista + crear -->
    <div class="contenidos-toolbar">
        <div class="contenidos-tabs">
            <a href="?week=<?= $weekOffset ?>&vista=<?= $vista ?>&filtro=todos<?= $q ? '&q='.urlencode($q) : '' ?><?= $busquedaGlobal ? '&global=1' : '' ?>" class="tab-btn <?= $filtro === 'todos' ? 'active' : '' ?>">Todos</a>
            <a href="?week=<?= $weekOffset ?>&vista=<?= $vista ?>&filtro=publicado<?= $q ? '&q='.urlencode($q) : '' ?><?= $busquedaGlobal ? '&global=1' : '' ?>" class="tab-btn <?= $filtro === 'publicado' ? 'active' : '' ?>"><i class="bi bi-check-circle"></i> Publicados</a>
            <a href="?week=<?= $weekOffset ?>&vista=<?= $vista ?>&filtro=por_publicar<?= $q ? '&q='.urlencode($q) : '' ?><?= $busquedaGlobal ? '&global=1' : '' ?>" class="tab-btn <?= $filtro === 'por_publicar' ? 'active' : '' ?>"><i class="bi bi-clock"></i> Por publicar</a>
            <a href="?week=<?= $weekOffset ?>&vista=<?= $vista ?>&filtro=programado<?= $q ? '&q='.urlencode($q) : '' ?><?= $busquedaGlobal ? '&global=1' : '' ?>" class="tab-btn <?= $filtro === 'programado' ? 'active' : '' ?>"><i class="bi bi-calendar-event"></i> Programados</a>
        </div>
        <div class="contenidos-actions">
            <div class="vista-toggle">
                <a href="?week=<?= $weekOffset ?>&vista=calendario&filtro=<?= $filtro ?><?= $q ? '&q='.urlencode($q) : '' ?><?= $busquedaGlobal ? '&global=1' : '' ?>" class="vista-btn <?= $vista === 'calendario' ? 'active' : '' ?>" title="Vista calendario"><i class="bi bi-calendar3"></i></a>
                <a href="?week=<?= $weekOffset ?>&vista=tabla&filtro=<?= $filtro ?><?= $q ? '&q='.urlencode($q) : '' ?><?= $busquedaGlobal ? '&global=1' : '' ?>" class="vista-btn <?= $vista === 'tabla' ? 'active' : '' ?>" title="Vista tabla"><i class="bi bi-list-ul"></i></a>
            </div>
            <?php if (!empty($ACL['crear'])): ?>
                <a href="crear.php" class="btn btn-accent"><i class="bi bi-plus-lg"></i> Nueva Noticia</a>
            <?php endif; ?>
        </div>
    </div>

    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'eliminado'): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert" style="margin-top:12px;">
            Noticia eliminada correctamente.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if ($busquedaGlobal && $q): ?>
        <p style="margin:12px 0; color:var(--muted); font-size:0.9rem;">Mostrando resultados globales para "<strong><?= htmlspecialchars($q) ?></strong>" (máx. 50)</p>
    <?php endif; ?>

    <!-- ==================== -->
    <!-- VISTA CALENDARIO -->
    <!-- ==================== -->
    <?php if ($vista === 'calendario' && !$busquedaGlobal): ?>
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="calendar-days">
                <?php foreach ($newsByDate as $date => $newsList):
                    $esHoy = ($date === $hoy);
                    $diaSemana = $diasSemana[date('D', strtotime($date))] ?? '';
                    $count = count($newsList);
                ?>
                    <div class="day-column <?= $esHoy ? 'day-today' : '' ?>">
                        <div class="day-header">
                            <span class="day-name"><?= $diaSemana ?></span>
                            <span class="day-date"><?= date("d/m", strtotime($date)) ?></span>
                            <?php if ($count > 0): ?>
                                <span class="day-count"><?= $count ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if (empty($newsList)): ?>
                            <div class="text-muted text-center py-4" style="font-size:0.85rem;">Sin publicaciones</div>
                        <?php else: ?>
                            <div class="day-news">
                                <?php foreach ($newsList as $row): 
                                    $img = !empty($row['crop3']) ? basePath()."/".$row['crop3'] : basePath()."/img/placeholder.jpg";
                                    $fechaPublicacion = new DateTime($row['fecha_publicacion']);
                                    $estadoClass = $row['_estado'];
                                ?>
                                <div class="noticias-card estado-<?= $estadoClass ?>">
                                    <div class="card-status-bar"></div>
                                    <div class="card-header d-flex justify-content-between">
                                        <span class="estado-badge estado-<?= $estadoClass ?>">
                                            <?php if ($estadoClass === 'publicado'): ?>
                                                <i class="bi bi-check-circle-fill"></i> Publicado
                                            <?php elseif ($estadoClass === 'por_publicar'): ?>
                                                <i class="bi bi-clock-fill"></i> Hoy
                                            <?php else: ?>
                                                <i class="bi bi-calendar-event-fill"></i> Programado
                                            <?php endif; ?>
                                        </span>
                                        <span class="card-time"><i class="bi bi-clock"></i> <?= $fechaPublicacion->format('H:i') ?></span>
                                    </div>
                                    <img src="<?= htmlspecialchars($img) ?>" alt="" class="card-img-top" loading="lazy" decoding="async">
                                    <h6><?= htmlspecialchars($row['titulo']) ?></h6>
                                    <div class="card-metrics">
                                        <span><i class="bi bi-eye"></i> <?= number_format($row['vistas']) ?></span>
                                        <span><i class="bi bi-heart"></i> <?= number_format($row['likes']) ?></span>
                                    </div>
                                    <?php if (!empty($ACL['editar']) || !empty($ACL['eliminar']) || !empty($ACL['leer'])): ?>
                                    <div class="noticias-actions">
                                        <?php if (!empty($ACL['editar'])): ?>
                                            <a href="editar.php?id=<?= $row['id'] ?>" class="btn btn-edit" title="Editar"><i class="bi bi-pencil-square"></i></a>
                                        <?php endif; ?>
                                        <?php if (!empty($ACL['leer'])): ?>
                                            <a href="see.php?id=<?= $row['id'] ?>" class="btn btn-view" title="Ver Estadísticas"><i class="bi bi-bar-chart"></i></a>
                                        <?php endif; ?>
                                        <?php if (!empty($ACL['eliminar'])): ?>
                                            <button type="button" class="btn btn-delete" data-id="<?= $row['id'] ?>" data-titulo="<?= htmlspecialchars($row['titulo']) ?>" title="Eliminar"><i class="bi bi-trash"></i></button>
                                        <?php endif; ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="week-nav">
                <a href="?week=<?= $weekOffset - 1 ?>&vista=<?= $vista ?>&filtro=<?= $filtro ?>" class="week-nav-btn" id="prevWeek" title="Semana anterior (←)"><i class="bi bi-chevron-left"></i></a>
                <?php if ($weekOffset !== 0): ?>
                    <a href="?week=0&vista=<?= $vista ?>&filtro=<?= $filtro ?>" class="week-nav-today">Hoy</a>
                <?php endif; ?>
                <div class="week-nav-center">
                    <span class="week-nav-label">Semana del <?= $startOfWeek->format('d/m') ?> al <?= $endOfWeek->format('d/m/Y') ?></span>
                    <input type="date" class="week-nav-picker" id="weekPicker" value="<?= $startOfWeek->format('Y-m-d') ?>" title="Saltar a fecha">
                </div>
                <a href="?week=<?= $weekOffset + 1 ?>&vista=<?= $vista ?>&filtro=<?= $filtro ?>" class="week-nav-btn" id="nextWeek" title="Semana siguiente (→)"><i class="bi bi-chevron-right"></i></a>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ==================== -->
    <!-- VISTA TABLA -->
    <!-- ==================== -->
    <?php if ($vista === 'tabla' || $busquedaGlobal): ?>
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="contenidos-table">
                    <thead>
                        <tr>
                            <th>Imagen</th>
                            <th>Título</th>
                            <th>Estado</th>
                            <th>Fecha</th>
                            <th>Vistas</th>
                            <th>Likes</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($allNews)): ?>
                            <tr><td colspan="7" style="text-align:center; padding:30px; color:var(--muted);">No se encontraron noticias</td></tr>
                        <?php else: ?>
                            <?php foreach ($allNews as $row):
                                $img = !empty($row['crop3']) ? basePath()."/".$row['crop3'] : basePath()."/img/placeholder.jpg";
                                $fechaPublicacion = new DateTime($row['fecha_publicacion']);
                                $estadoClass = $row['_estado'];
                            ?>
                            <tr>
                                <td><img src="<?= htmlspecialchars($img) ?>" alt="" class="table-thumb" loading="lazy" decoding="async"></td>
                                <td><strong class="table-title"><?= htmlspecialchars($row['titulo']) ?></strong></td>
                                <td>
                                    <span class="estado-badge estado-<?= $estadoClass ?>">
                                        <?php if ($estadoClass === 'publicado'): ?>
                                            <i class="bi bi-check-circle-fill"></i> Publicado
                                        <?php elseif ($estadoClass === 'por_publicar'): ?>
                                            <i class="bi bi-clock-fill"></i> Hoy
                                        <?php else: ?>
                                            <i class="bi bi-calendar-event-fill"></i> Programado
                                        <?php endif; ?>
                                    </span>
                                </td>
                                <td class="table-date"><?= $fechaPublicacion->format('d/m/Y H:i') ?></td>
                                <td><?= number_format($row['vistas']) ?></td>
                                <td><?= number_format($row['likes']) ?></td>
                                <td>
                                    <div class="noticias-actions">
                                        <?php if (!empty($ACL['editar'])): ?>
                                            <a href="editar.php?id=<?= $row['id'] ?>" class="btn btn-edit" title="Editar"><i class="bi bi-pencil-square"></i></a>
                                        <?php endif; ?>
                                        <?php if (!empty($ACL['leer'])): ?>
                                            <a href="see.php?id=<?= $row['id'] ?>" class="btn btn-view" title="Ver"><i class="bi bi-bar-chart"></i></a>
                                        <?php endif; ?>
                                        <?php if (!empty($ACL['eliminar'])): ?>
                                            <button type="button" class="btn btn-delete" data-id="<?= $row['id'] ?>" data-titulo="<?= htmlspecialchars($row['titulo']) ?>" title="Eliminar"><i class="bi bi-trash"></i></button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php if (!$busquedaGlobal): ?>
            <div class="week-nav">
                <a href="?week=<?= $weekOffset - 1 ?>&vista=<?= $vista ?>&filtro=<?= $filtro ?>" class="week-nav-btn" title="Semana anterior (←)"><i class="bi bi-chevron-left"></i></a>
                <?php if ($weekOffset !== 0): ?>
                    <a href="?week=0&vista=<?= $vista ?>&filtro=<?= $filtro ?>" class="week-nav-today">Hoy</a>
                <?php endif; ?>
                <div class="week-nav-center">
                    <span class="week-nav-label">Semana del <?= $startOfWeek->format('d/m') ?> al <?= $endOfWeek->format('d/m/Y') ?></span>
                    <input type="date" class="week-nav-picker" value="<?= $startOfWeek->format('Y-m-d') ?>" title="Saltar a fecha">
                </div>
                <a href="?week=<?= $weekOffset + 1 ?>&vista=<?= $vista ?>&filtro=<?= $filtro ?>" class="week-nav-btn" title="Semana siguiente (→)"><i class="bi bi-chevron-right"></i></a>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Navegación con teclado y date picker -->
<script>
(function(){
    const vista = '<?= $vista ?>';
    const filtro = '<?= $filtro ?>';
    const weekOffset = <?= $weekOffset ?>;
    const baseUrl = window.location.pathname;

    function goToWeek(offset) {
        window.location.href = `${baseUrl}?week=${offset}&vista=${vista}&filtro=${filtro}`;
    }

    // Atajos de teclado: ← → para semanas
    document.addEventListener('keydown', function(e) {
        // No activar si está enfocado un input
        if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.tagName === 'SELECT') return;
        if (e.key === 'ArrowLeft') {
            e.preventDefault();
            goToWeek(weekOffset - 1);
        } else if (e.key === 'ArrowRight') {
            e.preventDefault();
            goToWeek(weekOffset + 1);
        } else if (e.key === 'Home' || (e.key === 't' && !e.ctrlKey && !e.metaKey)) {
            e.preventDefault();
            goToWeek(0);
        }
    });

    // Obtener el lunes de una fecha (ISO: lun=1, dom=7)
    function getMonday(d) {
        const date = new Date(d);
        const day = date.getDay(); // 0=dom, 1=lun...
        const diff = day === 0 ? -6 : 1 - day;
        date.setDate(date.getDate() + diff);
        date.setHours(0, 0, 0, 0);
        return date;
    }

    // Date pickers: calcular offset de semana al seleccionar fecha
    document.querySelectorAll('.week-nav-picker').forEach(picker => {
        picker.addEventListener('change', function() {
            const selected = new Date(this.value + 'T12:00:00');
            const mondaySelected = getMonday(selected);
            const mondayToday = getMonday(new Date());
            const diffMs = mondaySelected - mondayToday;
            const newOffset = Math.round(diffMs / (7 * 24 * 60 * 60 * 1000));
            goToWeek(newOffset);
        });
    });
})();
</script>

<!-- Modal de Confirmación para Eliminar -->
<div id="modalOverlay" class="crop-modal" style="display: none;">
    <div class="card">
        <div class="crop-modal-content">
            <h3 id="modalTitle">Confirmar eliminación</h3>
            <p>¿Estás seguro de que deseas eliminar esta noticia? Esta acción no se puede deshacer.</p>
            <form id="modalForm" action="../controllers/eliminar_noticia.php" method="POST">
                <input type="hidden" name="id" id="modalId">
                <div class="crop-actions">
                    <button type="button" class="btn btn-secondary btn-cancel">Cancelar</button>
                    <button type="submit" class="btn btn-danger">Eliminar</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php include("./../layout/footerAdmin.php"); ?>