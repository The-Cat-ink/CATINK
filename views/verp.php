<?php
include("./../layout/headerAdmin.php");
include("./../data/conexion.php");
$ACL = $_SESSION['ACL']['publicidad'] ?? [
    "crear" => false,
    "leer" => false,
    "editar" => false,
    "eliminar" => false
];
if (!$ACL['leer']) {
    header("Location: publicidad.php");
    exit;
}

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: ./../views/publicidad.php");
    exit;
}

$stmt = $con->prepare("
    SELECT 
        p.*,
        (SELECT COUNT(*) FROM publicidad_views WHERE publicidad_id = p.id_pub) AS vistas,
        (SELECT COALESCE(AVG(tiempo_segundos),0) FROM publicidad_views WHERE publicidad_id = p.id_pub) AS tiempo_promedio,
        (SELECT COALESCE(SUM(tiempo_segundos),0) FROM publicidad_views WHERE publicidad_id = p.id_pub) AS tiempo_total,
        (SELECT COUNT(*) FROM publicidad_clicks WHERE publicidad_id = p.id_pub) AS clicks
    FROM publicidad p
    WHERE p.id_pub = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
if (!$row) {
    header("Location: ./../views/publicidad.php");
    exit;
}

$pubId = $row['id_pub'];
$hoy = date('Y-m-d');
$ini = !empty($row['fecha_inicio']) ? date('Y-m-d', strtotime($row['fecha_inicio'])) : null;
$fin = !empty($row['fecha_fin'])    ? date('Y-m-d', strtotime($row['fecha_fin']))    : null;

$estadoStr = 'vencida';
if ($ini !== null && $ini > $hoy) $estadoStr = 'programada';
else if ($fin !== null && $fin >= $hoy) $estadoStr = 'activa';

$ctr = ($row['vistas'] > 0) ? round(($row['clicks'] / $row['vistas']) * 100, 2) : 0;
?>
<div class="container-fluid px-3 py-2">

    <!-- ── BREADCRUMB & VOLVER ──────────────────────────────────── -->
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <div class="cn-breadcrumb m-0">
            <a href="publicidad.php">Publicidad</a>
            <i class="bi bi-chevron-right"></i>
            <a href="publicidad.php">Campañas</a>
            <i class="bi bi-chevron-right"></i>
            <span>Estadísticas de Campaña</span>
        </div>

        <a href="publicidad.php" class="btn btn-sm btn-outline-secondary px-3" style="border-radius:10px; font-weight:700; font-size:0.82rem; background:var(--card-bg);">
            <i class="bi bi-arrow-left me-1"></i> Volver a Publicidad
        </a>
    </div>

    <!-- ── ENCABEZADO DE CAMPAÑA ────────────────────────────────── -->
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <h1 style="font-weight:900; font-size:1.7rem; margin:0; color:var(--text); letter-spacing:-0.02em;">
                    <?= htmlspecialchars($row['titulo']) ?>
                </h1>
                <?php if($estadoStr === 'activa'): ?>
                    <span class="badge" style="background:rgba(16,185,129,0.12); color:#10b981; border:1px solid rgba(16,185,129,0.25); font-size:0.78rem; font-weight:800; padding:5px 12px; border-radius:20px;">
                        <i class="bi bi-circle-fill" style="font-size:0.4rem; vertical-align:middle; margin-right:4px;"></i> Activa
                    </span>
                <?php elseif($estadoStr === 'programada'): ?>
                    <span class="badge" style="background:rgba(245,158,11,0.12); color:#f59e0b; border:1px solid rgba(245,158,11,0.25); font-size:0.78rem; font-weight:800; padding:5px 12px; border-radius:20px;">
                        <i class="bi bi-clock-fill me-1"></i> Programada
                    </span>
                <?php else: ?>
                    <span class="badge" style="background:rgba(239,51,99,0.12); color:var(--accent); border:1px solid rgba(239,51,99,0.25); font-size:0.78rem; font-weight:800; padding:5px 12px; border-radius:20px;">
                        <i class="bi bi-stop-circle-fill me-1"></i> Vencida
                    </span>
                <?php endif; ?>
            </div>
            <p class="text-muted m-0" style="font-size:0.88rem;">Panel analítico detallado de rendimiento, interacciones y retención.</p>
        </div>

        <?php if($ACL['editar']): ?>
            <a href="editarp.php?id=<?= $row['id_pub'] ?>" class="btn btn-accent px-4 py-2" style="border-radius:12px; font-weight:800; font-size:0.88rem; box-shadow:0 4px 15px rgba(239,51,99,0.3);">
                <i class="bi bi-pencil-square me-1"></i> Editar Campaña
            </a>
        <?php endif; ?>
    </div>

    <div class="row g-4">
        <!-- ── COLUMNA IZQUIERDA: DETALLES Y TARJETA ──────────────── -->
        <div class="col-12 col-lg-4">
            <div class="card border-0 shadow-sm h-100" style="background:var(--card-bg); border-radius:18px; border:1px solid var(--border)!important; overflow:hidden;">
                <div class="p-3" style="background:var(--bg); border-bottom:1px solid var(--border); position:relative; aspect-ratio:16/9; display:flex; align-items:center; justify-content:center;">
                    <img src="<?= imageUrl($row['imagen'] ?? 'img/placeholder.svg') ?>" alt="Campaña" style="max-width:100%; max-height:100%; object-fit:contain;" loading="lazy">
                </div>

                <div class="card-body p-3 p-md-4">
                    <h6 class="font-weight-bold mb-3" style="font-weight:800; color:var(--text); font-size:0.95rem;">
                        <i class="bi bi-info-circle-fill text-accent me-1"></i> Información General
                    </h6>

                    <div class="d-flex flex-column gap-3 mb-4">
                        <div class="d-flex align-items-center justify-content-between">
                            <span class="text-muted" style="font-size:0.82rem; font-weight:600;">Formato:</span>
                            <span class="badge" style="background:var(--bg); color:var(--text); border:1px solid var(--border); font-size:0.78rem; font-weight:700; padding:6px 10px; border-radius:8px;">
                                <i class="bi <?= $row['tipo'] == 1 ? 'bi-aspect-ratio-fill text-accent' : 'bi-square-fill text-warning' ?> me-1"></i>
                                <?= $row['tipo'] == 1 ? 'Banner Largo (4:1)' : 'Banner Cuadrado (1:1)' ?>
                            </span>
                        </div>

                        <div>
                            <span class="text-muted" style="font-size:0.82rem; font-weight:600; display:block; margin-bottom:4px;">Enlace de Destino:</span>
                            <?php if(!empty($row['url'])): ?>
                                <a href="<?= htmlspecialchars($row['url']) ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary w-100 text-start text-truncate" style="border-radius:10px; font-size:0.8rem; font-weight:700; background:var(--bg); color:var(--accent);">
                                    <i class="bi bi-box-arrow-up-right me-1"></i> <?= htmlspecialchars($row['url']) ?>
                                </a>
                            <?php else: ?>
                                <span class="text-muted small">Sin enlace asignado</span>
                            <?php endif; ?>
                        </div>

                        <div class="d-flex align-items-center justify-content-between pt-2 border-top">
                            <span class="text-muted" style="font-size:0.82rem; font-weight:600;">Inicio:</span>
                            <span style="font-size:0.85rem; font-weight:700; color:var(--text);"><i class="bi bi-calendar-event me-1 text-muted"></i> <?= date("d/m/Y", strtotime($row['fecha_inicio'])) ?></span>
                        </div>
                        <div class="d-flex align-items-center justify-content-between">
                            <span class="text-muted" style="font-size:0.82rem; font-weight:600;">Fin:</span>
                            <span style="font-size:0.85rem; font-weight:700; color:var(--text);"><i class="bi bi-calendar-check me-1 text-muted"></i> <?= date("d/m/Y", strtotime($row['fecha_fin'])) ?></span>
                        </div>
                    </div>

                    <h6 class="font-weight-bold mb-3 pt-3 border-top" style="font-weight:800; color:var(--text); font-size:0.95rem;">
                        <i class="bi bi-lightning-charge-fill text-accent me-1"></i> Métricas Acumuladas
                    </h6>

                    <div class="row g-2">
                        <div class="col-6">
                            <div class="p-3" style="background:var(--bg); border:1px solid var(--border); border-radius:12px;">
                                <div class="text-muted" style="font-size:0.72rem; font-weight:800; text-transform:uppercase;">Vistas Totales</div>
                                <div style="font-size:1.25rem; font-weight:900; color:var(--text); margin-top:2px;">
                                    <i class="bi bi-eye-fill text-primary me-1" style="font-size:1rem;"></i> <?= number_format($row['vistas']) ?>
                                </div>
                            </div>
                        </div>

                        <div class="col-6">
                            <div class="p-3" style="background:var(--bg); border:1px solid var(--border); border-radius:12px;">
                                <div class="text-muted" style="font-size:0.72rem; font-weight:800; text-transform:uppercase;">Clics Totales</div>
                                <div style="font-size:1.25rem; font-weight:900; color:var(--text); margin-top:2px;">
                                    <i class="bi bi-mouse-fill me-1 text-danger" style="font-size:1rem;"></i> <?= number_format($row['clicks']) ?>
                                </div>
                            </div>
                        </div>

                        <div class="col-6">
                            <div class="p-3" style="background:var(--bg); border:1px solid var(--border); border-radius:12px;">
                                <div class="text-muted" style="font-size:0.72rem; font-weight:800; text-transform:uppercase;">Tiempo Prom.</div>
                                <div style="font-size:1.25rem; font-weight:900; color:var(--text); margin-top:2px;">
                                    <i class="bi bi-stopwatch-fill me-1 text-warning" style="font-size:1rem;"></i> <?= number_format($row['tiempo_promedio'], 1) ?>s
                                </div>
                            </div>
                        </div>

                        <div class="col-6">
                            <div class="p-3" style="background:var(--bg); border:1px solid var(--border); border-radius:12px;">
                                <div class="text-muted" style="font-size:0.72rem; font-weight:800; text-transform:uppercase;">CTR Estimado</div>
                                <div style="font-size:1.25rem; font-weight:900; color:var(--accent); margin-top:2px;">
                                    <i class="bi bi-percent me-1" style="font-size:1rem;"></i> <?= $ctr ?>%
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── COLUMNA DERECHA: DASHBOARD DE GRÁFICOS ──────────────── -->
        <div class="col-12 col-lg-8">
            <div class="card border-0 shadow-sm" style="background:var(--card-bg); border-radius:18px; border:1px solid var(--border)!important; overflow:hidden;">
                <div class="card-header bg-transparent border-bottom p-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <h5 class="m-0 font-weight-bold" style="font-weight:800; font-size:1.05rem; color:var(--text);">
                        <i class="bi bi-bar-chart-line-fill me-2 text-accent"></i> Comportamiento en el Tiempo
                    </h5>

                    <!-- Filtros de fecha en línea -->
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <div class="cn-date-input" style="border-radius:10px; padding:6px 10px;">
                            <i class="bi bi-calendar-event"></i>
                            <input type="date" id="filterFechaInicio" value="<?= date('Y-m-d', strtotime('-30 days')) ?>" style="font-size:0.8rem;">
                        </div>
                        <span style="font-weight:800; color:var(--muted);">→</span>
                        <div class="cn-date-input" style="border-radius:10px; padding:6px 10px;">
                            <i class="bi bi-calendar-check"></i>
                            <input type="date" id="filterFechaFin" value="<?= date('Y-m-d') ?>" style="font-size:0.8rem;">
                        </div>
                        <button class="btn btn-accent px-3 py-1" onclick="loadViewsStats(); loadClicksStats();" style="border-radius:10px; font-weight:800; font-size:0.8rem;">
                            <i class="bi bi-funnel-fill me-1"></i> Aplicar
                        </button>
                    </div>
                </div>

                <div class="card-body p-3 p-md-4">
                    <div class="row g-4">
                        <div class="col-12 col-md-6">
                            <div class="p-3" style="background:var(--bg); border:1px solid var(--border); border-radius:14px;">
                                <h6 style="font-weight:800; font-size:0.88rem; color:var(--text); margin-bottom:12px;">
                                    <i class="bi bi-eye-fill text-primary me-1"></i> Vistas Diarias
                                </h6>
                                <div class="chart-wrapper-pub">
                                    <canvas id="chartVistas"></canvas>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <div class="p-3" style="background:var(--bg); border:1px solid var(--border); border-radius:14px;">
                                <h6 style="font-weight:800; font-size:0.88rem; color:var(--text); margin-bottom:12px;">
                                    <i class="bi bi-clock-history text-warning me-1"></i> Tiempo Promedio de Vista (Segundos)
                                </h6>
                                <div class="chart-wrapper-pub">
                                    <canvas id="chartTiempo"></canvas>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <div class="p-3" style="background:var(--bg); border:1px solid var(--border); border-radius:14px;">
                                <h6 style="font-weight:800; font-size:0.88rem; color:var(--text); margin-bottom:12px;">
                                    <i class="bi bi-mouse-fill text-danger me-1"></i> Clics Diarios
                                </h6>
                                <div class="chart-wrapper-pub">
                                    <canvas id="chartClicks"></canvas>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <div class="p-3" style="background:var(--bg); border:1px solid var(--border); border-radius:14px;">
                                <h6 style="font-weight:800; font-size:0.88rem; color:var(--text); margin-bottom:12px;">
                                    <i class="bi bi-geo-alt-fill text-accent me-1"></i> Clics por Estado / País
                                </h6>
                                <div class="chart-wrapper-pub">
                                    <canvas id="chartClicksRegion"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.chart-wrapper-pub {
    position: relative;
    width: 100%;
    height: 240px;
}
.chart-wrapper-pub canvas {
    width: 100% !important;
    height: 240px !important;
}
</style>

<script>
const charts = {};

document.addEventListener("DOMContentLoaded", () => {
    loadViewsStats();
    loadClicksStats();
});

function loadViewsStats() {
    const fi = document.getElementById('filterFechaInicio').value;
    const ff = document.getElementById('filterFechaFin').value;
    fetch(`./../controllers/obtener_views_pub.php?pub_id=<?= $pubId ?>&fecha_inicio=${fi}&fecha_fin=${ff}`)
        .then(r => r.json())
        .then(d => {
            renderAreaChart("chartVistas", d.labels, d.vistas, "Vistas", '#3b82f6', 'rgba(59,130,246,0.12)');
            renderAreaChart("chartTiempo", d.labels, d.tiempoPromedio, "Tiempo promedio (s)", '#f59e0b', 'rgba(245,158,11,0.12)');
        });
}

function loadClicksStats() {
    const fi = document.getElementById('filterFechaInicio').value;
    const ff = document.getElementById('filterFechaFin').value;
    fetch(`./../controllers/obtener_clicks_pub.php?pub_id=<?= $pubId ?>&fecha_inicio=${fi}&fecha_fin=${ff}`)
        .then(r => r.json())
        .then(d => {
            renderAreaChart("chartClicks", d.labels, d.clicks, "Clicks", '#EF3363', 'rgba(239,51,99,0.12)');
            if(d.geo && d.geo.paises) {
                renderBarChart("chartClicksRegion", d.geo.paises.labels, d.geo.paises.values, "Clicks por ubicación");
            }
        });
}

function isDark() {
    return document.documentElement.getAttribute('data-bs-theme') === 'dark';
}

function getChartOptions(type = 'line') {
    const dark = isDark();
    const gridColor  = dark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.06)';
    const textColor  = dark ? '#94a3b8' : '#64748b';

    return {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        animation: { duration: 500, easing: 'easeInOutQuart' },
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: dark ? '#1e293b' : '#fff',
                borderColor: dark ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.1)',
                borderWidth: 1,
                titleColor: dark ? '#f1f5f9' : '#1e293b',
                bodyColor: textColor,
                padding: 10,
                cornerRadius: 8
            }
        },
        scales: type === 'bar' ? {
            x: {
                grid: { display: false },
                ticks: { color: textColor, font: { size: 10 } },
                border: { display: false }
            },
            y: {
                beginAtZero: true,
                grid: { color: gridColor },
                ticks: { color: textColor, font: { size: 10 }, padding: 6 },
                border: { display: false, dash: [4, 4] }
            }
        } : {
            x: {
                grid: { color: gridColor },
                ticks: { color: textColor, font: { size: 10 }, autoSkip: true, maxTicksLimit: 6 },
                border: { display: false }
            },
            y: {
                beginAtZero: true,
                grid: { color: gridColor },
                ticks: { color: textColor, font: { size: 10 }, padding: 6 },
                border: { display: false, dash: [4, 4] }
            }
        }
    };
}

function renderAreaChart(id, labels, data, label, strokeColor, fillColor) {
    const ctx = document.getElementById(id);
    if (!ctx) return;
    if (charts[id]) charts[id].destroy();

    charts[id] = new Chart(ctx, {
        type: "line",
        data: {
            labels: labels || [],
            datasets: [{
                label: label,
                data: data || [],
                fill: true,
                tension: 0.4,
                borderWidth: 2,
                borderColor: strokeColor,
                backgroundColor: fillColor,
                pointRadius: 0,
                pointHoverRadius: 4
            }]
        },
        options: getChartOptions('line')
    });
}

function renderBarChart(id, labels, data, label) {
    const ctx = document.getElementById(id);
    if (!ctx) return;
    if (charts[id]) charts[id].destroy();

    charts[id] = new Chart(ctx, {
        type: "bar",
        data: {
            labels: labels || [],
            datasets: [{
                label: label,
                data: data || [],
                backgroundColor: 'rgba(239,51,99,0.7)',
                borderColor: '#EF3363',
                borderWidth: 1,
                borderRadius: 6
            }]
        },
        options: getChartOptions('bar')
    });
}

window.addEventListener('resize', () => {
    loadViewsStats();
    loadClicksStats();
});
</script>

<?php include("./../layout/footerAdmin.php"); ?>
