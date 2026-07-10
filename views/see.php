<?php
include("./../layout/headerAdmin.php");
include("./../data/conexion.php");
$ACL = $_SESSION['ACL']['noticias'] ?? [
    "crear" => false, "leer" => false, "editar" => false, "eliminar" => false
];
if (empty($ACL['leer'])) { header("Location: admin.php"); exit(); }

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) { header("Location: ./../views/contenidos.php"); exit; }

$stmt = $con->prepare("
    SELECT n.*,
        (SELECT COUNT(*) FROM noticias_stats WHERE noticia_id = n.id) AS vistas,
        (SELECT COUNT(*) FROM noticia_likes WHERE noticia_id = n.id) AS likes,
        (SELECT COALESCE(SUM(tiempo_segundos),0) FROM noticias_stats WHERE noticia_id = n.id) AS tiempo_total
    FROM noticias n WHERE n.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
if (!$row) { header("Location: ./../views/contenidos.php"); exit; }

$noticiaId = $row['id'];
$ahora = new DateTime();
$fp    = new DateTime($row['fecha_publicacion']);
$hoy   = date('Y-m-d');

if ($fp < $ahora)                          { $ec = 'publicado';   $en = 'Publicado';    $ico = 'bi-check-circle-fill'; }
elseif ($fp->format('Y-m-d') === $hoy)    { $ec = 'por_publicar'; $en = 'Por publicar'; $ico = 'bi-clock-fill'; }
else                                        { $ec = 'programado';  $en = 'Programado';   $ico = 'bi-calendar-event-fill'; }

$sec = $row['tiempo_total'];
$timeStr = $sec >= 3600 ? round($sec/3600,1).' hrs' : ($sec >= 60 ? round($sec/60,1).' min' : $sec.' seg');
$avg = $row['vistas'] > 0 ? round($row['tiempo_total'] / $row['vistas'], 1) : 0;

function quillPreview($html, $limit = 300) {
    $text = trim(strip_tags(html_entity_decode($html)));
    return mb_strlen($text) > $limit ? mb_substr($text, 0, $limit).'…' : $text;
}

$bannerUrl = htmlspecialchars(imageUrl($row['crop2'] ?? $row['crop1'] ?? ''));
$thumbUrl  = htmlspecialchars(imageUrl($row['crop3'] ?? $row['crop1'] ?? ''));
$fechaFmt  = (new DateTime($row['fecha_publicacion']))->format('d M Y · H:i');
?>
<script>const ACL = <?= json_encode($ACL) ?>;</script>

<!-- ══════════════════════════  HERO HEADER  ══════════════════════════ -->
<div class="see-hero" style="background-image: url('<?= $bannerUrl ?>');">
    <div class="see-hero-overlay"></div>
    <div class="see-hero-content container-fluid px-md-4">
        <!-- Separador vertical -->
        <div style="flex: 1; min-height: 24px;"></div>

        <!-- Título + acciones abajo -->
        <div class="d-flex justify-content-between align-items-end flex-wrap gap-3">
            <div>
                <span class="see-estado-pill see-estado-<?= $ec ?> mb-3 d-inline-flex align-items-center gap-1">
                    <i class="bi <?= $ico ?>"></i> <?= $en ?>
                </span>
                <h1 class="see-hero-title"><?= htmlspecialchars($row['titulo']) ?></h1>
                <p class="see-hero-meta">
                    <i class="bi bi-calendar3 me-1"></i><?= $fechaFmt ?>
                    &nbsp;·&nbsp; <i class="bi bi-hash me-1"></i>ID <?= $row['id'] ?>
                </p>
            </div>
            <div class="d-flex gap-2 flex-wrap pb-1" style="--bs-btn-padding-y: 0.25rem; --bs-btn-padding-x: 0.75rem; --bs-btn-font-size: 0.78rem;">
                <?php if($ACL['editar']): ?>
                <a href="editar.php?id=<?= $row['id'] ?>" class="btn see-btn-ghost" style="padding:4px 11px; font-size:0.78rem;"><i class="bi bi-pencil-square"></i> Editar</a>
                <?php endif; ?>
                <a href="<?= newsUrlFromRow($row) ?>" target="_blank" class="btn see-btn-ghost" style="padding:4px 11px; font-size:0.78rem;"><i class="bi bi-eye"></i> Ver Noticia</a>
                <a href="javascript:history.back()" class="btn see-btn-back" style="padding:4px 13px; font-size:0.78rem;"><i class="bi bi-arrow-left"></i> Volver</a>
            </div>
        </div>
    </div>
</div>

<!-- ══════════════════════════  KPI STRIP  ══════════════════════════ -->
<div class="container-fluid px-md-4">
    <div class="see-kpi-strip">
        <div class="see-kpi-item">
            <div class="see-kpi-icon" style="background:rgba(99,102,241,.12);color:#6366f1"><i class="bi bi-eye-fill"></i></div>
            <div>
                <div class="see-kpi-val" id="kpi-views"><?= number_format($row['vistas']) ?></div>
                <div class="see-kpi-label">Vistas totales</div>
            </div>
        </div>
        <div class="see-kpi-divider"></div>
        <div class="see-kpi-item">
            <div class="see-kpi-icon" style="background:rgba(239,68,68,.12);color:#ef4444"><i class="bi bi-heart-fill"></i></div>
            <div>
                <div class="see-kpi-val" id="kpi-likes"><?= number_format($row['likes']) ?></div>
                <div class="see-kpi-label">Likes</div>
            </div>
        </div>
        <div class="see-kpi-divider"></div>
        <div class="see-kpi-item">
            <div class="see-kpi-icon" style="background:rgba(245,158,11,.12);color:#f59e0b"><i class="bi bi-clock-fill"></i></div>
            <div>
                <div class="see-kpi-val" id="kpi-time-total"><?= $timeStr ?></div>
                <div class="see-kpi-label">Permanencia total</div>
            </div>
        </div>
        <div class="see-kpi-divider"></div>
        <div class="see-kpi-item">
            <div class="see-kpi-icon" style="background:rgba(16,185,129,.12);color:#10b981"><i class="bi bi-hourglass-split"></i></div>
            <div>
                <div class="see-kpi-val" id="kpi-time-avg"><?= $avg ?> seg</div>
                <div class="see-kpi-label">Promedio por visita</div>
            </div>
        </div>
    </div>

    <!-- ══════════════════════════  BODY  ══════════════════════════ -->
    <div class="row g-4 mt-0">
        <div class="col-12">

            <!-- Filtro de fechas -->
            <div class="see-filter-bar mb-4">
                <div class="see-filter-group">
                    <label class="see-filter-label"><i class="bi bi-calendar-event"></i> Desde</label>
                    <input type="date" id="filterFechaInicio" class="see-filter-input" value="<?= date('Y-m-d', strtotime('-30 days')) ?>">
                </div>
                <div class="see-filter-sep">→</div>
                <div class="see-filter-group">
                    <label class="see-filter-label"><i class="bi bi-calendar-check"></i> Hasta</label>
                    <input type="date" id="filterFechaFin" class="see-filter-input" value="<?= date('Y-m-d') ?>">
                </div>
                <button class="see-filter-btn" onclick="loadGlobalStats(); loadLikesStats();">
                    <i class="bi bi-arrow-repeat"></i> Aplicar
                </button>
            </div>

            <!-- Grid de gráficas -->
            <div class="row g-3">
                <div class="col-sm-6">
                    <div class="see-chart-card">
                        <div class="see-chart-header">
                            <span class="see-chart-dot" style="background:#6366f1"></span>
                            <h6>Tráfico de Vistas</h6>
                        </div>
                        <div class="see-chart-body">
                            <canvas id="chartVistas"></canvas>
                            <div class="see-chart-fallback" style="display:none"></div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="see-chart-card">
                        <div class="see-chart-header">
                            <span class="see-chart-dot" style="background:#f59e0b"></span>
                            <h6>Permanencia Promedio</h6>
                        </div>
                        <div class="see-chart-body">
                            <canvas id="chartTiempo"></canvas>
                            <div class="see-chart-fallback" style="display:none"></div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="see-chart-card">
                        <div class="see-chart-header">
                            <span class="see-chart-dot" style="background:#ef4444"></span>
                            <h6>Likes del Período</h6>
                        </div>
                        <div class="see-chart-body">
                            <canvas id="chartLikes"></canvas>
                            <div class="see-chart-fallback" style="display:none"></div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="see-chart-card">
                        <div class="see-chart-header">
                            <span class="see-chart-dot" style="background:#06b6d4"></span>
                            <h6>Likes por Región</h6>
                        </div>
                        <div class="see-chart-body">
                            <canvas id="chartLikesRegion"></canvas>
                            <div class="see-chart-fallback" style="display:none"></div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- ══  Bottom spacing  ══ -->
<div style="height: 40px;"></div>

<script>
const charts = {};
const colorPalette = {
    views:  { line:'rgba(99,102,241,1)',  g0:'rgba(99,102,241,.20)', g1:'rgba(99,102,241,0)' },
    time:   { line:'rgba(245,158,11,1)', g0:'rgba(245,158,11,.20)', g1:'rgba(245,158,11,0)' },
    likes:  { line:'rgba(239,68,68,1)',  g0:'rgba(239,68,68,.20)',  g1:'rgba(239,68,68,0)'  },
    geo:    { line:'rgba(6,182,212,1)',   g0:'rgba(6,182,212,.30)',  g1:'rgba(6,182,212,.05)'}
};
const chartDefaults = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
        tooltip: {
            padding: 10, cornerRadius: 8, displayColors: false,
            backgroundColor:'rgba(15,23,42,.95)', titleColor:'#fff', bodyColor:'#e2e8f0',
            titleFont:{size:11,weight:'700'}, bodyFont:{size:12}
        }
    },
    scales: {
        x: { grid:{display:false}, ticks:{font:{size:9},color:'#94a3b8',maxRotation:45} },
        y: { grid:{color:'rgba(226,232,240,.6)'}, ticks:{font:{size:9},color:'#94a3b8',precision:0} }
    }
};

document.addEventListener('DOMContentLoaded', () => { loadGlobalStats(); loadLikesStats(); });

function loadGlobalStats() {
    const fi = filterFechaInicio.value, ff = filterFechaFin.value;
    fetch(`./../controllers/obtener_estadisticas.php?noticia_id=<?= $noticiaId ?>&fecha_inicio=${fi}&fecha_fin=${ff}`)
        .then(r => r.json())
        .then(d => {
            buildLine('chartVistas', d.labels, d.vistas, 'Vistas', colorPalette.views);
            buildLine('chartTiempo', d.labels, d.tiempoPromedio, 'Seg promedio', colorPalette.time);
            const tv = d.vistas.reduce((a,b)=>a+b,0);
            const tt = d.vistas.reduce((a,v,i)=>a+(v*d.tiempoPromedio[i]),0);
            setKPI('kpi-views', tv.toLocaleString());
            setKPI('kpi-time-total', fmtSec(tt));
            setKPI('kpi-time-avg', (tv>0?(tt/tv).toFixed(1):0)+' seg');
        })
        .catch(()=>{ showFallback('chartVistas'); showFallback('chartTiempo'); });
}

function loadLikesStats() {
    const fi = filterFechaInicio.value, ff = filterFechaFin.value;
    fetch(`./../controllers/obtener_likes.php?noticia_id=<?= $noticiaId ?>&fecha_inicio=${fi}&fecha_fin=${ff}`)
        .then(r => r.json())
        .then(d => {
            buildLine('chartLikes', d.labels, d.likes, 'Likes', colorPalette.likes);
            buildBar('chartLikesRegion', d.geo.paises.labels, d.geo.paises.values, 'Likes', colorPalette.geo);
            setKPI('kpi-likes', d.likes.reduce((a,b)=>a+b,0).toLocaleString());
        })
        .catch(()=>{ showFallback('chartLikes'); showFallback('chartLikesRegion'); });
}

function setKPI(id, v) { const el=document.getElementById(id); if(el) el.textContent=v; }

function fmtSec(s) {
    if(s>=3600) return (s/3600).toFixed(1)+' hrs';
    if(s>=60)   return (s/60).toFixed(1)+' min';
    return Math.round(s)+' seg';
}

function gradient(ctx, c) {
    const g = ctx.createLinearGradient(0,0,0,200);
    g.addColorStop(0, c.g0); g.addColorStop(1, c.g1); return g;
}

function buildLine(id, labels, data, label, c) {
    if (typeof Chart === 'undefined') { showFallback(id); return; }
    const canvas = document.getElementById(id); if(!canvas) return;
    canvas.style.display='block';
    hideFallback(id);
    if(charts[id]) charts[id].destroy();
    const ctx = canvas.getContext('2d');
    charts[id] = new Chart(canvas, {
        type:'line',
        data:{ labels, datasets:[{
            label, data,
            borderColor: c.line, backgroundColor: gradient(ctx,c),
            fill:true, tension:.35, borderWidth:2,
            pointRadius:2.5, pointHoverRadius:5,
            pointBackgroundColor:c.line, pointBorderColor:'#fff', pointBorderWidth:1.5
        }]},
        options: chartDefaults
    });
}

function buildBar(id, labels, data, label, c) {
    if (typeof Chart === 'undefined') { showFallback(id); return; }
    const canvas = document.getElementById(id); if(!canvas) return;
    canvas.style.display='block';
    hideFallback(id);
    if(charts[id]) charts[id].destroy();
    const ctx = canvas.getContext('2d');
    charts[id] = new Chart(canvas, {
        type:'bar',
        data:{ labels, datasets:[{ label, data,
            backgroundColor: gradient(ctx,c), borderRadius:6, borderWidth:0, maxBarThickness:30
        }]},
        options: chartDefaults
    });
}

function showFallback(id) {
    const canvas = document.getElementById(id); if(!canvas) return;
    canvas.style.display='none';
    const fb = canvas.parentElement.querySelector('.see-chart-fallback');
    if(fb) {
        fb.innerHTML=`<div class="see-fallback-inner"><i class="bi bi-bar-chart-line"></i><p>Sin datos en el rango</p></div>`;
        fb.style.display='flex';
    }
}
function hideFallback(id) {
    const canvas = document.getElementById(id); if(!canvas) return;
    const fb = canvas.parentElement.querySelector('.see-chart-fallback');
    if(fb) fb.style.display='none';
}
</script>

<?php include("./../layout/footerAdmin.php"); ?>