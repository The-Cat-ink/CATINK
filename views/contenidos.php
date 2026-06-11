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
    $sql = "SELECT n.id, n.titulo, n.descripcion, n.fecha_publicacion, n.vistas, n.likes, n.crop3, 
            u.nombre as autor_nombre, u.foto_personal as autor_foto, u.id_u as autor_id
            FROM noticias n
            LEFT JOIN usuarios u ON n.autor = u.id_u
            WHERE n.titulo LIKE ?
            ORDER BY n.fecha_publicacion DESC
            LIMIT 50";
    $stmt = $con->prepare($sql);
    $stmt->bind_param("s", $like);
} elseif ($q !== '') {
    $like = "%$q%";
    $sql = "SELECT n.id, n.titulo, n.descripcion, n.fecha_publicacion, n.vistas, n.likes, n.crop3,
            u.nombre as autor_nombre, u.foto_personal as autor_foto, u.id_u as autor_id
            FROM noticias n
            LEFT JOIN usuarios u ON n.autor = u.id_u
            WHERE n.fecha_publicacion BETWEEN ? AND ? AND n.titulo LIKE ?
            ORDER BY n.fecha_publicacion ASC";
    $stmt = $con->prepare($sql);
    $stmt->bind_param("sss", $fechaInicio, $fechaFin, $like);
} else {
    $sql = "SELECT n.id, n.titulo, n.descripcion, n.fecha_publicacion, n.vistas, n.likes, n.crop3,
            u.nombre as autor_nombre, u.foto_personal as autor_foto, u.id_u as autor_id
            FROM noticias n
            LEFT JOIN usuarios u ON n.autor = u.id_u
            WHERE n.fecha_publicacion BETWEEN ? AND ?
            ORDER BY n.fecha_publicacion ASC";
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
// Agrupar autores por día y noticias por autor y fecha
// ============================
$authorsByDate = [];
$newsByAuthorAndDate = [];

foreach ($allNews as $news) {
    $date = date('Y-m-d', strtotime($news['fecha_publicacion']));
    $autorId = $news['autor_id'] ?? 0;
    $autorNombre = $news['autor_nombre'] ?? 'Desconocido';
    $autorFoto = $news['autor_foto'] ?? null;
    
    // Agrupar autores por día
    if (!isset($authorsByDate[$date])) {
        $authorsByDate[$date] = [];
    }
    if (!isset($authorsByDate[$date][$autorId])) {
        $authorsByDate[$date][$autorId] = [
            'nombre' => $autorNombre,
            'foto' => $autorFoto,
            'count' => 0
        ];
    }
    $authorsByDate[$date][$autorId]['count']++;
    
    // Agrupar noticias por autor y fecha
    if (!isset($newsByAuthorAndDate[$autorId])) {
        $newsByAuthorAndDate[$autorId] = [];
    }
    if (!isset($newsByAuthorAndDate[$autorId][$date])) {
        $newsByAuthorAndDate[$autorId][$date] = [
            'nombre' => $autorNombre,
            'foto' => $autorFoto,
            'news' => []
        ];
    }
    $newsByAuthorAndDate[$autorId][$date]['news'][] = $news;
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

// ============================
// Vista MES: calcular datos del mes
// ============================
$monthOffset = isset($_GET['month']) ? (int)$_GET['month'] : 0;
$mesRef = new DateTime();
$mesRef->modify("$monthOffset months");
$mesRef->modify('first day of this month');
$mesAnio = $mesRef->format('Y');
$mesNum = $mesRef->format('m');
$mesesEsp = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
$mesNombre = $mesesEsp[(int)$mesNum - 1];
$diasEnMes = (int)$mesRef->format('t');
$primerDiaSemana = (int)$mesRef->format('N'); // 1=lun

$newsByMonth = [];
if ($vista === 'mes') {
    $mesInicio = "$mesAnio-$mesNum-01 00:00:00";
    $mesFin = "$mesAnio-$mesNum-$diasEnMes 23:59:59";
    $sqlMes = "SELECT n.id, n.titulo, n.fecha_publicacion, n.vistas, n.likes, n.crop3,
               u.nombre as autor_nombre, u.foto_personal as autor_foto, u.id_u as autor_id
               FROM noticias n
               LEFT JOIN usuarios u ON n.autor = u.id_u
               WHERE n.fecha_publicacion BETWEEN ? AND ?
               ORDER BY n.fecha_publicacion ASC";
    $stmtMes = $con->prepare($sqlMes);
    $stmtMes->bind_param("ss", $mesInicio, $mesFin);
    $stmtMes->execute();
    $resMes = $stmtMes->get_result();
    while ($r = $resMes->fetch_assoc()) {
        $fp = new DateTime($r['fecha_publicacion']);
        if ($fp < $ahora) {
            $r['_estado'] = 'publicado';
        } elseif ($fp->format('Y-m-d') === $hoy) {
            $r['_estado'] = 'por_publicar';
        } else {
            $r['_estado'] = 'programado';
        }
        $dia = (int)date('j', strtotime($r['fecha_publicacion']));
        $newsByMonth[$dia][] = $r;
    }
    // Filtro por estado en mes
    if ($filtro !== 'todos') {
        foreach ($newsByMonth as &$dayList) {
            $dayList = array_filter($dayList, fn($n) => $n['_estado'] === $filtro);
        }
        unset($dayList);
    }
}
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
                <a href="?week=<?= $weekOffset ?>&vista=calendario&filtro=<?= $filtro ?><?= $q ? '&q='.urlencode($q) : '' ?><?= $busquedaGlobal ? '&global=1' : '' ?>" class="vista-btn <?= $vista === 'calendario' ? 'active' : '' ?>" title="Vista semana"><i class="bi bi-calendar3"></i></a>
                <a href="?week=<?= $weekOffset ?>&vista=mes&filtro=<?= $filtro ?><?= $q ? '&q='.urlencode($q) : '' ?><?= $busquedaGlobal ? '&global=1' : '' ?>" class="vista-btn <?= $vista === 'mes' ? 'active' : '' ?>" title="Vista mes"><i class="bi bi-calendar3-week"></i></a>
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
            <div class="week-nav-top">
                <span class="week-nav-top-slot">
                    <?php if ($weekOffset !== 0): ?>
                        <a href="?week=0&vista=<?= $vista ?>&filtro=<?= $filtro ?>" class="week-nav-today">Hoy</a>
                    <?php endif; ?>
                </span>
            </div>
            <div class="week-nav" style="border-top:none; border-bottom:1px solid var(--border);">
                <a href="?week=<?= $weekOffset - 1 ?>&vista=<?= $vista ?>&filtro=<?= $filtro ?>" class="week-nav-btn" id="prevWeek" title="Semana anterior (←)"><i class="bi bi-chevron-left"></i></a>
                <div class="week-nav-center">
                    <span class="week-nav-label">Semana del <?= $startOfWeek->format('d/m') ?> al <?= $endOfWeek->format('d/m/Y') ?></span>
                    <input type="date" class="week-nav-picker" id="weekPicker" value="<?= $startOfWeek->format('Y-m-d') ?>" title="Saltar a fecha">
                </div>
                <a href="?week=<?= $weekOffset + 1 ?>&vista=<?= $vista ?>&filtro=<?= $filtro ?>" class="week-nav-btn" id="nextWeek" title="Semana siguiente (→)"><i class="bi bi-chevron-right"></i></a>
            </div>
            <div class="calendar-days">
                <?php foreach ($newsByDate as $date => $newsList):
                    $esHoy = ($date === $hoy);
                    $esPasado = ($date < $hoy);
                    $diaSemana = $diasSemana[date('D', strtotime($date))] ?? '';
                    $count = count($newsList);
                ?>
                    <div class="day-column <?= $esHoy ? 'day-today' : '' ?> <?= $esPasado ? 'day-past' : '' ?>" data-date="<?= $date ?>" <?= $esPasado ? '' : 'droppable="true"' ?> data-past="<?= $esPasado ? '1' : '0' ?>" data-authors="<?= htmlspecialchars(json_encode($authorsByDate[$date] ?? [])) ?>">
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
                                    $img = imageUrl($row['crop3'] ?? 'img/placeholder.svg');
                                    $fechaPublicacion = new DateTime($row['fecha_publicacion']);
                                    $estadoClass = $row['_estado'];
                                ?>
                                <?php $puedeMover = ($estadoClass !== 'publicado' && !empty($ACL['editar'])); ?>
                                <div class="noticias-card estado-<?= $estadoClass ?>" <?= $puedeMover ? 'draggable="true"' : '' ?> data-id="<?= $row['id'] ?>">
                                    <div class="card-status-bar"></div>
                                    <div class="card-header d-flex justify-content-between">
                                        <span class="estado-badge estado-<?= $estadoClass ?>">
                                            <?php if ($estadoClass === 'publicado'): ?>
                                                <i class="bi bi-check-circle-fill"></i><span>Publicado</span>
                                            <?php elseif ($estadoClass === 'por_publicar'): ?>
                                                <i class="bi bi-clock-fill"></i><span>Hoy</span>
                                            <?php else: ?>
                                                <i class="bi bi-calendar-event-fill"></i><span>Programado</span>
                                            <?php endif; ?>
                                        </span>
                                        <span class="card-time"><i class="bi bi-clock"></i> <?= $fechaPublicacion->format('H:i') ?></span>
                                    </div>
                                    <img src="<?= $img ?>" alt="" class="card-img-top" loading="lazy" decoding="async">
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
        </div>
    </div>
    <?php endif; ?>

    <!-- ==================== -->
    <!-- VISTA MES -->
    <!-- ==================== -->
    <?php if ($vista === 'mes' && !$busquedaGlobal): ?>
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="week-nav-top">
                <span class="week-nav-top-slot">
                    <?php if ($monthOffset !== 0): ?>
                        <a href="?month=0&vista=mes&filtro=<?= $filtro ?>" class="week-nav-today">Hoy</a>
                    <?php endif; ?>
                </span>
            </div>
            <div class="week-nav" style="border-top:none; border-bottom:1px solid var(--border);">
                <a href="?month=<?= $monthOffset - 1 ?>&vista=mes&filtro=<?= $filtro ?>" class="week-nav-btn" title="Mes anterior"><i class="bi bi-chevron-left"></i></a>
                <div class="week-nav-center">
                    <span class="week-nav-label"><?= $mesNombre ?> <?= $mesAnio ?></span>
                </div>
                <a href="?month=<?= $monthOffset + 1 ?>&vista=mes&filtro=<?= $filtro ?>" class="week-nav-btn" title="Mes siguiente"><i class="bi bi-chevron-right"></i></a>
            </div>
            <div class="month-grid">
                <div class="month-header-row">
                    <?php foreach (['Lun','Mar','Mié','Jue','Vie','Sáb','Dom'] as $d): ?>
                        <div class="month-day-name"><?= $d ?></div>
                    <?php endforeach; ?>
                </div>
                <div class="month-body">
                    <?php
                    // Celdas vacías antes del primer día
                    for ($i = 1; $i < $primerDiaSemana; $i++): ?>
                        <div class="month-cell month-cell-empty"></div>
                    <?php endfor; ?>
                    <?php for ($dia = 1; $dia <= $diasEnMes; $dia++):
                        $fechaDia = sprintf('%s-%s-%02d', $mesAnio, $mesNum, $dia);
                        $esHoyMes = ($fechaDia === $hoy);
                        $noticiasDelDia = $newsByMonth[$dia] ?? [];
                    ?>
                        <?php $esPasadoMes = ($fechaDia < $hoy); ?>
                        <div class="month-cell <?= $esHoyMes ? 'month-cell-today' : '' ?> <?= $esPasadoMes ? 'month-cell-past' : '' ?>" data-date="<?= $fechaDia ?>" <?= $esPasadoMes ? '' : 'droppable="true"' ?> data-past="<?= $esPasadoMes ? '1' : '0' ?>">
                            <span class="month-cell-num <?= $esHoyMes ? 'today-num' : '' ?>"><?= $dia ?></span>
                            <?php if (!empty($noticiasDelDia)): ?>
                                <div class="month-cell-news">
                                    <?php foreach (array_slice($noticiasDelDia, 0, 3) as $n): ?>
                                        <div class="month-news-item estado-<?= $n['_estado'] ?>" draggable="true" data-id="<?= $n['id'] ?>" title="<?= htmlspecialchars($n['titulo']) ?>">
                                            <span class="month-news-dot"></span>
                                            <span class="month-news-title"><?= htmlspecialchars(mb_strimwidth($n['titulo'], 0, 22, '...')) ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                    <?php if (count($noticiasDelDia) > 3): ?>
                                        <span class="month-news-more">+<?= count($noticiasDelDia) - 3 ?> más</span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endfor; ?>
                </div>
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
                                $img = imageUrl($row['crop3'] ?? 'img/placeholder.svg');
                                $fechaPublicacion = new DateTime($row['fecha_publicacion']);
                                $estadoClass = $row['_estado'];
                            ?>
                            <tr>
                                <td><img src="<?= $img ?>" alt="" class="table-thumb" loading="lazy" decoding="async"></td>
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
            <div class="week-nav-top">
                <span class="week-nav-top-slot">
                    <?php if ($weekOffset !== 0): ?>
                        <a href="?week=0&vista=<?= $vista ?>&filtro=<?= $filtro ?>" class="week-nav-today">Hoy</a>
                    <?php endif; ?>
                </span>
            </div>
            <div class="week-nav">
                <a href="?week=<?= $weekOffset - 1 ?>&vista=<?= $vista ?>&filtro=<?= $filtro ?>" class="week-nav-btn" title="Semana anterior (←)"><i class="bi bi-chevron-left"></i></a>
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

<!-- Navegación con teclado, date picker y drag & drop -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    const vista = '<?= $vista ?>';
    const filtro = '<?= $filtro ?>';
    const weekOffset = <?= $weekOffset ?>;
    const monthOffset = <?= $monthOffset ?>;
    const baseUrl = window.location.pathname;
    const canEdit = <?= !empty($ACL['editar']) ? 'true' : 'false' ?>;

    function goToWeek(offset) {
        window.location.href = `${baseUrl}?week=${offset}&vista=${vista}&filtro=${filtro}`;
    }
    function goToMonth(offset) {
        window.location.href = `${baseUrl}?month=${offset}&vista=mes&filtro=${filtro}`;
    }

    // Atajos de teclado: ← → para semanas/meses
    document.addEventListener('keydown', function(e) {
        if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.tagName === 'SELECT') return;
        if (e.key === 'ArrowLeft') {
            e.preventDefault();
            vista === 'mes' ? goToMonth(monthOffset - 1) : goToWeek(weekOffset - 1);
        } else if (e.key === 'ArrowRight') {
            e.preventDefault();
            vista === 'mes' ? goToMonth(monthOffset + 1) : goToWeek(weekOffset + 1);
        } else if (e.key === 'Home' || (e.key === 't' && !e.ctrlKey && !e.metaKey)) {
            e.preventDefault();
            vista === 'mes' ? goToMonth(0) : goToWeek(0);
        }
    });

    // Obtener el lunes de una fecha (ISO)
    function getMonday(d) {
        const date = new Date(d);
        const day = date.getDay();
        const diff = day === 0 ? -6 : 1 - day;
        date.setDate(date.getDate() + diff);
        date.setHours(0, 0, 0, 0);
        return date;
    }

    // Date pickers
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

    // ============================
    // DRAG & DROP
    // ============================
    if (canEdit) {
        const dateTimeModal = document.getElementById('dateTimeModal');
        const dateTimeForm = document.getElementById('dateTimeForm');
        const dateTimeCancel = document.getElementById('dateTimeCancel');
        const reprogIdInput = document.getElementById('reprogId');
        const reprogDateInput = document.getElementById('reprogDate');
        const reprogTimeInput = document.getElementById('reprogTime');

        if (!dateTimeModal || !dateTimeForm || !dateTimeCancel || !reprogIdInput || !reprogDateInput || !reprogTimeInput) {
            console.warn('Modal de reprogramación no disponible en el DOM.');
            return;
        }

        let draggedEl = null;
        let draggedId = null;
        let pendingDropZone = null;
        let pendingCard = null;

        // Drag start
        document.addEventListener('dragstart', function(e) {
            const card = e.target.closest('[draggable="true"][data-id]');
            if (!card) return;
            draggedEl = card;
            draggedId = card.dataset.id;
            card.classList.add('dragging');
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', draggedId);
        });

        document.addEventListener('dragend', function(e) {
            if (draggedEl) draggedEl.classList.remove('dragging');
            document.querySelectorAll('.drag-over').forEach(el => el.classList.remove('drag-over'));
            draggedEl = null;
            draggedId = null;
        });

        // Drop zones: day-column y month-cell
        document.addEventListener('dragover', function(e) {
            const zone = e.target.closest('[data-date]');
            if (!zone || !draggedId) return;

            // Verificar si es fecha pasada
            if (zone.dataset.past === '1') {
                e.dataTransfer.dropEffect = 'none';
                zone.style.cursor = 'not-allowed';
                return;
            }

            if (!zone.hasAttribute('droppable')) return;

            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            zone.style.cursor = '';
            document.querySelectorAll('.drag-over').forEach(el => el.classList.remove('drag-over'));
            zone.classList.add('drag-over');
        });

        document.addEventListener('dragleave', function(e) {
            const zone = e.target.closest('[data-date][droppable]');
            if (zone) zone.classList.remove('drag-over');
        });

        document.addEventListener('drop', function(e) {
            // Buscar zona drop
            const zone = e.target.closest('[data-date]');
            if (!zone || !draggedId) return;

            e.preventDefault();
            zone.classList.remove('drag-over');

            // Verificar que tenga droppable
            if (!zone.hasAttribute('droppable')) {
                return;
            }

            // Verificar que no sea fecha pasada
            if (zone.dataset.past === '1') {
                alert('No se pueden mover noticias a fechas pasadas (anteriores a hoy)');
                return;
            }

            const newDate = zone.dataset.date;
            if (!newDate) return;

            // Mover visualmente temporalmente
            if (draggedEl) {
                draggedEl.classList.add('card-moving');
                const newsContainer = zone.querySelector('.day-news, .month-cell-news');
                if (newsContainer) {
                    newsContainer.appendChild(draggedEl);
                } else if (zone.classList.contains('month-cell')) {
                    let container = zone.querySelector('.month-cell-news');
                    if (!container) {
                        container = document.createElement('div');
                        container.className = 'month-cell-news';
                        zone.appendChild(container);
                    }
                    container.appendChild(draggedEl);
                } else {
                    const emptyMsg = zone.querySelector('.text-muted');
                    if (emptyMsg) emptyMsg.remove();
                    let container = zone.querySelector('.day-news');
                    if (!container) {
                        container = document.createElement('div');
                        container.className = 'day-news';
                        zone.appendChild(container);
                    }
                    container.appendChild(draggedEl);
                }
                setTimeout(() => draggedEl.classList.remove('card-moving'), 300);
            }

            // Mostrar modal de fecha/hora
            reprogIdInput.value = draggedId;
            reprogDateInput.value = newDate;
            // Hora por defecto: mantener la original o 09:00
            const timeMatch = draggedEl?.querySelector('.card-time')?.textContent?.match(/(\d{2}:\d{2})/);
            reprogTimeInput.value = timeMatch ? timeMatch[1] : '09:00';
            pendingDropZone = zone;
            pendingCard = draggedEl;
            dateTimeModal.style.display = 'flex';
        });

        dateTimeCancel.addEventListener('click', function() {
            dateTimeModal.style.display = 'none';
            pendingDropZone = null;
            pendingCard = null;
            location.reload(); // Recargar para restaurar posición original
        });

        dateTimeForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const newDate = reprogDateInput.value;
            const newTime = reprogTimeInput.value;
            const noticiaId = reprogIdInput.value;

            const formData = new FormData();
            formData.append('id', noticiaId);
            formData.append('fecha', newDate);
            formData.append('hora', newTime);

            fetch('../controllers/reprogramar_noticia.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.error) {
                    alert('Error: ' + data.error);
                    location.reload();
                } else {
                    dateTimeModal.style.display = 'none';
                    // Actualizar UI sin recargar
                    const timeSpan = pendingCard?.querySelector('.card-time');
                    if (timeSpan) {
                        timeSpan.innerHTML = '<i class="bi bi-clock"></i> ' + newTime.substring(0, 5);
                    }
                    pendingDropZone = null;
                    pendingCard = null;
                }
            })
            .catch(() => {
                alert('Error de conexión al reprogramar');
                location.reload();
            });
        });
    }
});

// ============================
// Tooltips interactivos
// ============================
const newsByAuthorAndDate = <?= json_encode($newsByAuthorAndDate) ?>;

// Limpiar tooltips existentes antes de crear nuevos
function clearExistingTooltips() {
    document.querySelectorAll('.authors-tooltip').forEach(t => t.remove());
    document.querySelectorAll('.news-tooltip').forEach(t => t.remove());
    document.querySelectorAll('.day-column').forEach(d => d._tooltip = null);
    document.querySelectorAll('.tooltip-author').forEach(a => a._newsTooltip = null);
}

// Tooltip para días - mostrar autores
document.querySelectorAll('.day-column[data-authors]').forEach(dayColumn => {
    let hideTimeout = null;
    
    dayColumn.addEventListener('mouseenter', function(e) {
        if (hideTimeout) {
            clearTimeout(hideTimeout);
            hideTimeout = null;
        }
        
        // Limpiar tooltips existentes
        clearExistingTooltips();
        
        const authors = JSON.parse(this.dataset.authors || '{}');
        if (Object.keys(authors).length === 0) return;
        
        const tooltip = document.createElement('div');
        tooltip.className = 'authors-tooltip';
        
        let html = '<div class="tooltip-header">Autores del día</div>';
        html += '<div class="tooltip-authors">';
        const currentDate = this.dataset.date;
        for (const [autorId, autor] of Object.entries(authors)) {
            const foto = autor.foto ? `<?= basePath() ?>/serve-image.php?file=${encodeURIComponent(autor.foto)}` : null;
            html += `
                <div class="tooltip-author" data-author-id="${autorId}" data-date="${currentDate}">
                    ${foto ? `<img src="${foto}" alt="${autor.nombre}" class="author-avatar">` : `<div class="author-avatar-placeholder">${autor.nombre.charAt(0).toUpperCase()}</div>`}
                    <span class="author-name">${autor.nombre}</span>
                    <span class="author-count">${autor.count} noticias</span>
                </div>
            `;
        }
        html += '</div>';
        
        tooltip.innerHTML = html;
        document.body.appendChild(tooltip);
        
        const rect = this.getBoundingClientRect();
        tooltip.style.left = rect.left + 'px';
        tooltip.style.top = (rect.top - tooltip.offsetHeight - 5) + 'px';
        
        this._tooltip = tooltip;
        
        // Activar animación después de un pequeño delay
        requestAnimationFrame(() => {
            tooltip.classList.add('visible');
        });
        
        // Prevenir que el tooltip desaparezca cuando el cursor está sobre él
        tooltip.addEventListener('mouseenter', function() {
            if (hideTimeout) {
                clearTimeout(hideTimeout);
                hideTimeout = null;
            }
        });
        
        tooltip.addEventListener('mouseleave', function() {
            hideTooltip(dayColumn, tooltip, 500);
        });
    });
    
    dayColumn.addEventListener('mouseleave', function() {
        if (this._tooltip) {
            hideTooltip(this, this._tooltip, 500);
        }
    });
});

// Función para ocultar tooltip con animación
function hideTooltip(element, tooltip, delay) {
    tooltip.classList.remove('visible');
    setTimeout(() => {
        if (tooltip.parentNode) {
            tooltip.remove();
        }
        if (element._tooltip === tooltip) {
            element._tooltip = null;
        }
    }, delay + 200); // Esperar animación + delay
}

// Tooltip para autores - mostrar noticias
document.addEventListener('mouseover', function(e) {
    const authorEl = e.target.closest('.tooltip-author');
    if (!authorEl) return;
    
    // Si ya existe un tooltip, no crear otro
    if (authorEl._newsTooltip) return;
    
    const autorId = authorEl.dataset.authorId;
    const date = authorEl.dataset.date;
    const autorData = newsByAuthorAndDate[autorId]?.[date];
    if (!autorData || !autorData.news || autorData.news.length === 0) return;
    
    const tooltip = document.createElement('div');
    tooltip.className = 'news-tooltip';
    
    let html = `<div class="tooltip-header">Noticias de ${autorData.nombre}</div>`;
    html += '<div class="tooltip-news">';
    autorData.news.forEach(news => {
        const img = news.crop3 ? `<?= basePath() ?>/serve-image.php?file=${encodeURIComponent(news.crop3)}` : '<?= basePath() ?>/img/placeholder.svg';
        html += `
            <div class="tooltip-news-item">
                <img src="${img}" alt="${news.titulo}" class="news-thumb">
                <span class="news-title">${news.titulo}</span>
            </div>
        `;
    });
    html += '</div>';
    
    tooltip.innerHTML = html;
    document.body.appendChild(tooltip);
    
    const rect = authorEl.getBoundingClientRect();
    tooltip.style.left = (rect.right + 5) + 'px';
    tooltip.style.top = rect.top + 'px';
    
    authorEl._newsTooltip = tooltip;
    authorEl._hideTimeout = null;
    
    // Activar animación
    requestAnimationFrame(() => {
        tooltip.classList.add('visible');
    });
    
    // Cuando el cursor entra al tooltip, cancelar el timeout
    tooltip.addEventListener('mouseenter', function() {
        if (authorEl._hideTimeout) {
            clearTimeout(authorEl._hideTimeout);
            authorEl._hideTimeout = null;
        }
    });
    
    // Cuando el cursor sale del tooltip, ocultar con animación
    tooltip.addEventListener('mouseleave', function() {
        hideTooltip(authorEl, tooltip, 500);
    });
});

// Cuando el cursor sale del autor, ocultar con animación
document.addEventListener('mouseout', function(e) {
    const authorEl = e.target.closest('.tooltip-author');
    if (!authorEl || !authorEl._newsTooltip) return;
    
    hideTooltip(authorEl, authorEl._newsTooltip, 500);
});

// Cerrar tooltips al hacer click fuera
document.addEventListener('click', function(e) {
    // Cerrar tooltip de autores del día
    document.querySelectorAll('.day-column').forEach(dayColumn => {
        if (dayColumn._tooltip) {
            const tooltip = dayColumn._tooltip;
            if (!tooltip.contains(e.target) && !dayColumn.contains(e.target)) {
                hideTooltip(dayColumn, tooltip, 0);
            }
        }
    });
    
    // Cerrar tooltip de noticias del autor
    document.querySelectorAll('.tooltip-author').forEach(authorEl => {
        if (authorEl._newsTooltip) {
            const tooltip = authorEl._newsTooltip;
            if (!tooltip.contains(e.target) && !authorEl.contains(e.target)) {
                hideTooltip(authorEl, tooltip, 0);
            }
        }
    });
});
</script>

<!-- Modal para reprogramar fecha y hora -->
<div id="dateTimeModal" class="crop-modal" style="display: none;">
    <div class="card" style="max-width: 400px;">
        <div class="crop-modal-content">
            <h3><i class="bi bi-calendar-plus"></i> Reprogramar noticia</h3>
            <p>Selecciona la nueva fecha y hora de publicación:</p>
            <form id="dateTimeForm">
                <input type="hidden" id="reprogId">
                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text);">Fecha</label>
                    <input type="date" id="reprogDate" required style="width: 100%; padding: 10px 14px; border: 1px solid var(--border); border-radius: 8px; font-size: 0.9rem; background: var(--card-bg); color: var(--text);">
                </div>
                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text);">Hora</label>
                    <input type="time" id="reprogTime" required style="width: 100%; padding: 10px 14px; border: 1px solid var(--border); border-radius: 8px; font-size: 0.9rem; background: var(--card-bg); color: var(--text);">
                </div>
                <div class="crop-actions">
                    <button type="button" class="btn btn-secondary" id="dateTimeCancel">Cancelar</button>
                    <button type="submit" class="btn btn-accent">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

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