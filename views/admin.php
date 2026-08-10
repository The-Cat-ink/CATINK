<?php 
if(session_status() == PHP_SESSION_NONE){
    session_start();
}
include(__DIR__ . "/../data/conexion.php");

// Procesar botón de pánico de comentarios
if (isset($_POST['toggle_panico']) && isset($_SESSION['superadmin']) && $_SESSION['superadmin'] === true) {
    $nuevoEstado = (int)$_POST['estado_panico'];
    $stmt = $con->prepare("UPDATE secciones SET estado = ? WHERE nombre = 'comentarios'");
    $stmt->bind_param("i", $nuevoEstado);
    $stmt->execute();
    
    // Si no se actualizó ninguna fila, insertamos si no existe
    if ($stmt->affected_rows === 0) {
        $check = $con->query("SELECT id_s FROM secciones WHERE nombre = 'comentarios'");
        if ($check->num_rows === 0) {
            $stmtInsert = $con->prepare("INSERT INTO secciones (nombre, estado) VALUES ('comentarios', ?)");
            $stmtInsert->bind_param("i", $nuevoEstado);
            $stmtInsert->execute();
        }
    }
    
    header("Location: admin.php");
    exit();
}

// Procesar actualización de estado de secciones
if(isset($_POST['actualizarEstado']) && isset($_POST['secciones'])){
    foreach($_POST['secciones'] as $id => $datos){
        $estado = $datos['estado'] == '1' ? 1 : 0;
        $valor = isset($datos['valor']) ? trim($datos['valor']) : null;
        $stmt = $con->prepare("UPDATE secciones SET estado = ?, valor = ? WHERE id_s = ?");
        $stmt->bind_param("isi", $estado, $valor, $id);
        $stmt->execute();
        
        // Si es la sección de videos, limpiar la caché de YouTube para aplicar los cambios de inmediato
        if ($id == 2) {
            $cacheDir = __DIR__ . '/../cache/youtube';
            if (file_exists($cacheDir)) {
                $files = glob($cacheDir . '/*');
                foreach($files as $file){
                    if(is_file($file)) unlink($file);
                }
            }
        }
    }
    header("Location: ".$_SERVER['PHP_SELF']);
    exit();
}

// Procesar actualización de lista de reproducción (YouTube)
if(isset($_POST['actualizarPlaylist']) && isset($_POST['youtube_playlist'])){
    $valor = trim($_POST['youtube_playlist']);
    $stmt = $con->prepare("UPDATE secciones SET valor = ? WHERE id_s = 2");
    $stmt->bind_param("s", $valor);
    $stmt->execute();
    
    // Limpiar caché de YouTube
    $cacheDir = __DIR__ . '/../cache/youtube';
    if (file_exists($cacheDir)) {
        $files = glob($cacheDir . '/*');
        foreach($files as $file){
            if(is_file($file)) unlink($file);
        }
    }
    header("Location: ".$_SERVER['PHP_SELF']);
    exit();
}

include(__DIR__ . "/../layout/headerAdmin.php");
// ====================================
// ACL GLOBAL
// ====================================
// Obtener permisos específicos del módulo "noticias"
$ACLNoticias = $_SESSION['ACL']['noticias'] ?? [
    'crear' => false,
    'leer' => false,
    'editar' => false,
    'eliminar' => false
];

// Verificar si el usuario puede ver admin.php
$puedeVerAdmin = false;
foreach($_SESSION['ACL'] as $mod){
    if($mod['leer']){
        $puedeVerAdmin = true;
        break;
    }
}
if(!$superadmin && !$puedeVerAdmin){
    session_destroy();
    header("Location: ./../index.php");
    exit();
}
// ====================================
// KPIs
// ====================================
$kpis = $con->query("
    SELECT
        (SELECT COUNT(*) FROM noticias WHERE eliminado_en IS NULL) AS total_noticias,
        (SELECT COUNT(*) FROM noticias WHERE eliminado_en IS NULL AND fecha_publicacion <= NOW()) AS publicadas,
        (SELECT COUNT(*) FROM noticias WHERE eliminado_en IS NULL AND fecha_publicacion > NOW()) AS programadas,
        (SELECT COUNT(*) FROM noticias_stats) AS total_vistas,
        (SELECT COUNT(*) FROM noticia_likes) AS total_likes
")->fetch_assoc();

// Tiempo total de lectura
$tiempoTotal = $con->query("
    SELECT COALESCE(SUM(tiempo_segundos),0) AS tiempo_total
    FROM noticias_stats
")->fetch_assoc()['tiempo_total'];

// ====================================
// Últimas noticias (TOP 5)
// ====================================
$resultNoticias = $con->query("
    SELECT
        n.id,
        n.slug,
        n.titulo,
        n.descripcion,
        n.crop3,
        n.vistas,
        n.likes,
        n.fecha_publicacion,
        GROUP_CONCAT(DISTINCT c.nombre ORDER BY nc.orden ASC SEPARATOR ',') AS categorias,
        COALESCE((SELECT SUM(tiempo_segundos) FROM noticias_stats WHERE noticia_id = n.id), 0) AS tiempo_total_stats
    FROM noticias n
    LEFT JOIN noticia_categoria nc ON n.id = nc.noticia_id
    LEFT JOIN categorias c ON nc.categoria_id = c.id_c
    WHERE n.eliminado_en IS NULL
    GROUP BY n.id
    ORDER BY n.fecha_publicacion DESC
    LIMIT 5
");
$ultimasNoticias = [];
while($row = $resultNoticias->fetch_assoc()){
    $ultimasNoticias[] = $row;
}       
$configResult = $con->query("SELECT * FROM secciones GROUP BY nombre");
$config = [];
while($row = $configResult->fetch_assoc()){
    $config[] = $row;
}
function formatNumberShort($num){
    if ($num >= 1000000) {
        return round($num / 1000000, 1) . 'M';
    } elseif ($num >= 1000) {
        return round($num / 1000, 1) . 'K';
    }
    return $num;
}
?>
<div class="container-fluid px-3 py-2">

    <!-- ── SALUDO Y ENCABEZADO ─────────────────────────────────── -->
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <h1 style="font-weight:900; font-size:1.8rem; margin:0; color:var(--text); letter-spacing:-0.02em;">
                    <?= obtenerBienvenida($fila['sexo'] ?? '') ?>, <?= htmlspecialchars($fila['nombre'] ?? $fila['usuario']) ?>
                </h1>
                <span class="badge" style="background:rgba(16,185,129,0.12); color:#10b981; border:1px solid rgba(16,185,129,0.25); border-radius:20px; padding:4px 10px; font-weight:800; font-size:0.72rem;">
                    <i class="bi bi-circle-fill" style="font-size:0.4rem; vertical-align:middle; margin-right:3px;"></i> Sistema Activo
                </span>
            </div>
            <p class="text-muted m-0" style="font-size:0.88rem;">Panel principal de administración, métricas en tiempo real y contenidos.</p>
        </div>

        <!-- Toolbar de Acciones Rápidas -->
        <div class="admin-quick-toolbar m-0">
            <?php if($ACLNoticias['crear']): ?>
                <a href="crear.php" class="btn btn-accent px-3 py-2" style="border-radius:12px; font-weight:800; font-size:0.88rem; box-shadow:0 4px 15px rgba(239,51,99,0.3);">
                    <i class="bi bi-plus-lg"></i> Nueva Noticia
                </a>
            <?php endif; ?> 
            <?php if($superadmin): ?>
                <a href="paginas.php" class="btn btn-outline-secondary px-3 py-2" style="border-radius:12px; font-weight:700; font-size:0.88rem; background:var(--card-bg);">
                    <i class="bi bi-file-earmark-code-fill me-1 text-accent"></i> Páginas CMS
                </a>
                <button id="btnAbrirModal" class="btn btn-outline-secondary px-3 py-2" style="border-radius:12px; font-weight:700; font-size:0.88rem; background:var(--card-bg);">
                    <i class="bi bi-sliders me-1 text-accent"></i> Estado de Secciones
                </button>
                <?php
                $comentariosSecRes = $con->query("SELECT estado FROM secciones WHERE nombre = 'comentarios' LIMIT 1");
                $comentariosSec = $comentariosSecRes->fetch_assoc();
                $comentariosActivos = $comentariosSec ? ($comentariosSec['estado'] == 1) : true;
                ?>
                <form action="" method="POST" class="d-inline-block m-0">
                    <?php if ($comentariosActivos): ?>
                        <input type="hidden" name="estado_panico" value="0">
                        <button type="submit" name="toggle_panico" class="btn btn-danger px-3 py-2" style="border-radius:12px; font-weight:800; font-size:0.88rem; box-shadow:0 4px 14px rgba(239,68,68,0.3);">
                            <i class="bi bi-exclamation-triangle-fill me-1"></i> Desactivar Comentarios
                        </button>
                    <?php else: ?>
                        <input type="hidden" name="estado_panico" value="1">
                        <button type="submit" name="toggle_panico" class="btn btn-success px-3 py-2" style="border-radius:12px; font-weight:800; font-size:0.88rem; box-shadow:0 4px 14px rgba(16,185,129,0.3);">
                            <i class="bi bi-check-circle-fill me-1"></i> Activar Comentarios
                        </button>
                    <?php endif; ?>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Alertas de Restablecimiento de Sistema -->
    <?php if (isset($_GET['restablecido'])): ?>
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert" style="background:rgba(16,185,129,0.12); color:#10b981; border:1px solid rgba(16,185,129,0.25); border-radius:14px; font-weight:600;">
            <i class="bi bi-check-circle-fill me-2"></i> ¡El restablecimiento se realizó con éxito! Módulos borrados: <strong><?= htmlspecialchars($_GET['modulos_borrados'] ?? '') ?></strong>.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php elseif (isset($_GET['restablecer_error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert" style="background:rgba(239,68,68,0.12); color:#ef4444; border:1px solid rgba(239,68,68,0.25); border-radius:14px; font-weight:600;">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <?php 
                if ($_GET['restablecer_error'] == '1') {
                    echo 'Por favor, completa todos los campos obligatorios del formulario e ingresa la palabra clave exacta en mayúsculas.';
                } else {
                    echo 'Ocurrió un error interno en el servidor al intentar restablecer el sistema. Intenta de nuevo.';
                }
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- ── YOUTUBE PLAYLIST BANNER ─────────────────────────────── -->
    <?php if($superadmin): ?>
        <?php 
            $playlistVal = '';
            foreach($config as $sec) {
                if($sec['nombre'] === 'videos') {
                    $playlistVal = $sec['valor'];
                }
            }
        ?>
        <div class="card border-0 shadow-sm mb-4" style="background:var(--card-bg); border-radius:18px; border:1px solid var(--border)!important; overflow:hidden;">
            <div class="card-body p-3 p-md-4">
                <form action="" method="POST" class="m-0">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div style="width:44px; height:44px; border-radius:14px; background:rgba(255,0,0,0.1); color:#ff0000; display:flex; align-items:center; justify-content:center; font-size:1.3rem; flex-shrink:0;">
                            <i class="bi bi-youtube"></i>
                        </div>
                        <div>
                            <h5 class="m-0 font-weight-bold" style="font-weight:800; font-size:1rem; color:var(--text);">Lista de Reproducción en Portada (YouTube)</h5>
                            <span class="text-muted" style="font-size:0.8rem;">ID o URL oficial de la lista de videos reproducida en el home.</span>
                        </div>
                    </div>

                    <div class="d-flex gap-2 align-items-center flex-wrap flex-md-nowrap">
                        <div style="position:relative; flex:1; min-width:240px;">
                            <i class="bi bi-link-45deg" style="position:absolute; left:14px; top:50%; transform:translateY(-50%); color:var(--muted); font-size:1.2rem;"></i>
                            <input type="text" id="youtube_playlist_id" name="youtube_playlist" value="<?= htmlspecialchars($playlistVal) ?>" placeholder="Ej: PLMC9KNincKvYin_USF1QeqG50KB1K1uD" class="cn-input" style="padding-left:42px; border-radius:12px;">
                        </div>
                        <button type="submit" class="btn btn-accent px-4 py-2" name="actualizarPlaylist" style="border-radius:12px; font-weight:800; font-size:0.88rem; white-space:nowrap; flex-shrink:0;">
                            <i class="bi bi-arrow-repeat me-1"></i> Actualizar Playlist
                        </button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <!-- ── ÚLTIMAS NOTICIAS (FEED MODERNO) ─────────────────────── -->
    <div class="card border-0 shadow-sm mb-4" style="background:var(--card-bg); border-radius:18px; border:1px solid var(--border)!important; overflow:hidden;">
        <div class="card-header bg-transparent border-bottom p-3 d-flex align-items-center justify-content-between">
            <h5 class="m-0 font-weight-bold" style="font-weight:800; font-size:1.05rem; color:var(--text);">
                <i class="bi bi-newspaper me-2 text-accent" style="color:var(--accent);"></i> Últimas Noticias Publicadas
            </h5>
            <a href="contenidos.php" class="btn btn-sm btn-outline-secondary" style="border-radius:10px; font-weight:700; font-size:0.78rem;">
                Ver todas <i class="bi bi-arrow-right me-1"></i>
            </a>
        </div>
        <div class="card-body p-3">
            <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-5 g-3">
                <?php foreach($ultimasNoticias as $n):
                    $desc = mb_strimwidth($n['descripcion'] ?? '', 0, 75, '...');
                    $img = !empty($n['crop3']) ? imageUrl($n['crop3']) : imageUrl('img/placeholder.svg');
                ?>
                <div class="col">
                    <div class="card h-100 border-0 shadow-sm news-admin-card" style="background:var(--bg); border-radius:14px; border:1px solid var(--border)!important; overflow:hidden;">
                        <div style="position:relative; aspect-ratio:16/9; overflow:hidden; background:rgba(0,0,0,0.05);">
                            <a href="<?= newsUrlFromRow($n) ?>" target="_blank">
                                <img src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($n['titulo']) ?>" style="width:100%; height:100%; object-fit:cover; transition:transform 0.3s ease;">
                            </a>
                            <span class="badge" style="position:absolute; top:8px; left:8px; background:rgba(0,0,0,0.7); backdrop-filter:blur(4px); color:#fff; font-size:0.68rem; font-weight:700; border-radius:6px;">
                                <?= date('d/m H:i', strtotime($n['fecha_publicacion'])) ?>
                            </span>
                            <?php if($ACLNoticias['editar']): ?>
                                <a href="<?= basePath() ?>/views/editar.php?id=<?= $n['id'] ?>" class="btn btn-sm btn-accent" style="position:absolute; top:8px; right:8px; width:28px; height:28px; border-radius:50%; padding:0; display:flex; align-items:center; justify-content:center; font-size:0.75rem;" title="Editar">
                                    <i class="bi bi-pencil-fill"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                        <div class="card-body p-3 d-flex flex-column justify-content-between">
                            <div>
                                <div class="mb-1" style="display:flex; gap:4px; flex-wrap:wrap;">
                                    <?php foreach(array_filter(array_map('trim', explode(',', $n['categorias'] ?? ''))) as $cat): ?>
                                        <span class="badge" style="background:rgba(239,51,99,0.12); color:var(--accent); font-size:0.65rem; font-weight:800; border-radius:4px; text-transform:uppercase;"><?= htmlspecialchars($cat) ?></span>
                                    <?php endforeach; ?>
                                </div>
                                <h6 style="font-weight:800; font-size:0.88rem; color:var(--text); line-height:1.3; margin:6px 0 4px; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;">
                                    <a href="<?= newsUrlFromRow($n) ?>" target="_blank" style="color:var(--text); text-decoration:none;">
                                        <?= htmlspecialchars($n['titulo']) ?>
                                    </a>
                                </h6>
                                <p class="text-muted m-0" style="font-size:0.75rem; line-height:1.4; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;"><?= htmlspecialchars($desc) ?></p>
                            </div>
                            <div class="d-flex align-items-center justify-content-between pt-2 mt-2 border-top" style="font-size:0.72rem; color:var(--muted); font-weight:700;">
                                <span><i class="bi bi-eye-fill me-1"></i> <?= formatNumberShort($n['vistas']) ?></span>
                                <span><i class="bi bi-clock-fill me-1"></i> <?= number_format($n['tiempo_total_stats']/60,0) ?>m</span>
                                <span><i class="bi bi-heart-fill me-1 text-danger"></i> <?= formatNumberShort($n['likes']) ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- ── PANEL DE MÉTRICAS Y KPIS ────────────────────────────── -->
    <div class="card border-0 shadow-sm mb-4" style="background:var(--card-bg); border-radius:18px; border:1px solid var(--border)!important; overflow:hidden;">
        <div class="card-header bg-transparent border-bottom p-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
            <h5 class="m-0 font-weight-bold" style="font-weight:800; font-size:1.05rem; color:var(--text);">
                <i class="bi bi-bar-chart-line-fill me-2 text-accent" style="color:var(--accent);"></i> Métricas & Rendimiento General
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
                <button class="btn btn-accent px-3 py-1" onclick="loadGlobalStats(); loadLikesStats();" style="border-radius:10px; font-weight:800; font-size:0.8rem;">
                    <i class="bi bi-funnel-fill me-1"></i> Aplicar
                </button>
            </div>
        </div>

        <div class="card-body p-3">
            <div class="row g-3">
                <?php
                    $kpiCards = [
                        ['label' => 'Total Noticias', 'value' => $kpis['total_noticias'], 'icon' => 'bi-newspaper',           'color' => '#6366f1', 'bg' => 'rgba(99,102,241,0.12)'],
                        ['label' => 'Publicadas',     'value' => $kpis['publicadas'],      'icon' => 'bi-check-circle-fill',   'color' => '#10b981', 'bg' => 'rgba(16,185,129,0.12)'],
                        ['label' => 'Programadas',    'value' => $kpis['programadas'],     'icon' => 'bi-clock-fill',          'color' => '#f59e0b', 'bg' => 'rgba(245,158,11,0.12)'],
                        ['label' => 'Vistas Totales', 'value' => number_format($kpis['total_vistas']), 'icon' => 'bi-eye-fill', 'color' => '#3b82f6', 'bg' => 'rgba(59,130,246,0.12)'],
                        ['label' => 'Likes Totales',  'value' => number_format($kpis['total_likes']),  'icon' => 'bi-heart-fill', 'color' => '#ef4444', 'bg' => 'rgba(239,68,68,0.12)'],
                        ['label' => 'Tiempo Lectura', 'value' => number_format($tiempoTotal/60) . ' min', 'icon' => 'bi-stopwatch-fill', 'color' => '#8b5cf6', 'bg' => 'rgba(139,92,246,0.12)'],
                    ];
                    foreach($kpiCards as $k):
                ?>
                <div class="col-6 col-md-4 col-lg-2">
                    <div class="card h-100 border-0 shadow-sm p-3" style="background:var(--bg); border-radius:14px; border:1px solid var(--border)!important; position:relative; overflow:hidden;">
                        <div class="d-flex align-items-center gap-3">
                            <div style="width:42px; height:42px; border-radius:12px; background:<?= $k['bg'] ?>; color:<?= $k['color'] ?>; display:flex; align-items:center; justify-content:center; font-size:1.25rem; flex-shrink:0;">
                                <i class="bi <?= $k['icon'] ?>"></i>
                            </div>
                            <div>
                                <div style="font-size:1.3rem; font-weight:900; color:var(--text); line-height:1;"><?= $k['value'] ?></div>
                                <div style="font-size:0.75rem; font-weight:700; color:var(--muted); margin-top:4px; text-transform:uppercase; letter-spacing:0.04em;"><?= $k['label'] ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- ── GRÁFICOS DE RENDIMIENTO ─────────────────────────────── -->
    <div class="row g-4 mb-4">
        <div class="col-12 col-lg-6">
            <div class="card border-0 shadow-sm h-100" style="background:var(--card-bg); border-radius:18px; border:1px solid var(--border)!important; overflow:hidden;">
                <div class="card-header bg-transparent border-bottom p-3">
                    <h6 class="m-0 font-weight-bold" style="font-weight:800; font-size:0.95rem; color:var(--text);">
                        <i class="bi bi-graph-up-arrow me-2 text-accent" style="color:var(--accent);"></i> Vistas por Categoría
                    </h6>
                </div>
                <div class="card-body p-3">
                    <div class="chart-wrapper">
                        <canvas id="globalChartVistas"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-6">
            <div class="card border-0 shadow-sm h-100" style="background:var(--card-bg); border-radius:18px; border:1px solid var(--border)!important; overflow:hidden;">
                <div class="card-header bg-transparent border-bottom p-3">
                    <h6 class="m-0 font-weight-bold" style="font-weight:800; font-size:0.95rem; color:var(--text);">
                        <i class="bi bi-hourglass-split me-2 text-accent" style="color:var(--accent);"></i> Tiempo de Lectura por Categoría (Minutos)
                    </h6>
                </div>
                <div class="card-body p-3">
                    <div class="chart-wrapper">
                        <canvas id="globalChartTiempo"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-6">
            <div class="card border-0 shadow-sm h-100" style="background:var(--card-bg); border-radius:18px; border:1px solid var(--border)!important; overflow:hidden;">
                <div class="card-header bg-transparent border-bottom p-3">
                    <h6 class="m-0 font-weight-bold" style="font-weight:800; font-size:0.95rem; color:var(--text);">
                        <i class="bi bi-heart-fill me-2 text-danger"></i> Likes por Categoría
                    </h6>
                </div>
                <div class="card-body p-3">
                    <div class="chart-wrapper">
                        <canvas id="globalChartLikes"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-6">
            <div class="card border-0 shadow-sm h-100" style="background:var(--card-bg); border-radius:18px; border:1px solid var(--border)!important; overflow:hidden;">
                <div class="card-header bg-transparent border-bottom p-3">
                    <h6 class="m-0 font-weight-bold" style="font-weight:800; font-size:0.95rem; color:var(--text);">
                        <i class="bi bi-geo-alt-fill me-2 text-accent" style="color:var(--accent);"></i> Likes por Estado / Región
                    </h6>
                </div>
                <div class="card-body p-3">
                    <div class="chart-wrapper">
                        <canvas id="globalChartLikesRegion"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── ZONA DE PELIGRO: RESTABLECIMIENTO GRANULAR ───────────── -->
    <?php if(isset($_SESSION['usuario'])): ?>
        <div class="card border-0 shadow-sm mb-4" style="background:var(--card-bg); border:1.5px solid rgba(239,68,68,0.35)!important; border-radius:18px; overflow:hidden;">
            <div class="card-header border-bottom p-3 d-flex align-items-center gap-2" style="background:linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color:#fff;">
                <i class="bi bi-exclamation-octagon-fill fs-5"></i>
                <h5 class="m-0 font-weight-bold" style="font-weight:900; font-size:1.05rem; color:#fff;">Zona de Peligro: Restablecer Información del Sistema</h5>
            </div>
            <div class="card-body p-3 p-md-4">
                <p class="text-muted small mb-4" style="line-height:1.6; font-size:0.88rem;">
                    Esta sección permite eliminar de forma masiva y permanente los contenidos seleccionados en un rango de fechas especificado. Las cuentas de administradores, editores y suscriptores no se verán afectadas bajo ninguna circunstancia.
                </p>
                <form action="<?= basePath() ?>/controllers/restablecer_granular.php" method="POST" id="formRestablecer">
                    <div class="row g-3 mb-4">
                        <div class="col-md-6 col-12">
                            <label for="rest_fecha_inicio" style="font-weight:700; font-size:0.85rem; display:block; margin-bottom:6px; color:var(--text);">Fecha de Inicio:</label>
                            <input type="date" id="rest_fecha_inicio" name="fecha_inicio" required class="cn-input" style="border-radius:12px;">
                        </div>
                        <div class="col-md-6 col-12">
                            <label for="rest_fecha_fin" style="font-weight:700; font-size:0.85rem; display:block; margin-bottom:6px; color:var(--text);">Fecha de Fin:</label>
                            <input type="date" id="rest_fecha_fin" name="fecha_fin" required class="cn-input" style="border-radius:12px;">
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label style="font-weight:800; font-size:0.85rem; display:block; margin-bottom:12px; color:var(--text);">Selecciona qué información deseas borrar en el rango especificado:</label>
                        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 g-3">
                            <div class="col">
                                <label class="danger-check-card" for="chkNoticias">
                                    <input class="mod-check" type="checkbox" name="modulos[]" value="noticias" id="chkNoticias" checked>
                                    <span class="danger-check-label"><i class="bi bi-newspaper text-danger"></i> Noticias y Estadísticas</span>
                                </label>
                            </div>
                            <div class="col">
                                <label class="danger-check-card" for="chkComentarios">
                                    <input class="mod-check" type="checkbox" name="modulos[]" value="comentarios" id="chkComentarios" checked>
                                    <span class="danger-check-label"><i class="bi bi-chat-square-text text-danger"></i> Comentarios y Reacciones</span>
                                </label>
                            </div>
                            <div class="col">
                                <label class="danger-check-card" for="chkSuscripciones">
                                    <input class="mod-check" type="checkbox" name="modulos[]" value="suscripciones" id="chkSuscripciones">
                                    <span class="danger-check-label"><i class="bi bi-bell text-danger"></i> Suscripciones</span>
                                </label>
                            </div>
                            <div class="col">
                                <label class="danger-check-card" for="chkLectores">
                                    <input class="mod-check" type="checkbox" name="modulos[]" value="lectores" id="chkLectores">
                                    <span class="danger-check-label"><i class="bi bi-people text-danger"></i> Cuentas de Lectores</span>
                                </label>
                            </div>
                            <div class="col">
                                <label class="danger-check-card" for="chkNotificaciones">
                                    <input class="mod-check" type="checkbox" name="modulos[]" value="notificaciones" id="chkNotificaciones" checked>
                                    <span class="danger-check-label"><i class="bi bi-broadcast text-danger"></i> Notificaciones de Sistema</span>
                                </label>
                            </div>
                            <div class="col">
                                <label class="danger-check-card" for="chkActividades">
                                    <input class="mod-check" type="checkbox" name="modulos[]" value="actividades" id="chkActividades">
                                    <span class="danger-check-label"><i class="bi bi-journal-text text-danger"></i> Bitácora de Actividad</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <button type="button" id="btnOpenRestModal" class="btn btn-danger px-4 py-3" style="width:100%; border-radius:14px; font-weight:800; font-size:0.95rem; box-shadow:0 4px 16px rgba(239,68,68,0.35);">
                        <i class="bi bi-trash3-fill me-2"></i> Iniciar Restablecimiento
                    </button>

                    <!-- Modal de Confirmación Interno -->
                    <div id="modalRestConfirm" class="modal-nativo" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; overflow:auto; background-color:rgba(0,0,0,0.6); backdrop-filter:blur(4px); justify-content:center; align-items:center;">
                        <div class="modal-content-nativo" style="border: 2px solid #ef4444; max-width: 460px; border-radius: 18px; background: var(--card-bg); overflow: hidden; margin: auto; box-shadow: 0 12px 40px rgba(0,0,0,0.3);">
                            <div class="modal-header-nativo" style="background: #ef4444; color: #fff; padding: 16px 20px; display: flex; justify-content: space-between; align-items: center;">
                                <h5 class="mb-0" style="color: #fff; font-weight: 800;"><i class="bi bi-exclamation-triangle-fill me-2"></i> ¿Confirmar Restablecimiento?</h5>
                                <span id="closeRestModal" class="cerrar" style="color:#fff; font-size: 24px; font-weight: bold; cursor: pointer;">&times;</span>
                            </div>
                            <div class="modal-body-nativo" style="padding: 24px;">
                                <p style="font-weight:800; color:#ef4444; margin-bottom: 12px;">¡Atención! Esta acción borrará permanentemente los datos indicados.</p>
                                <p class="small mb-3" style="color: var(--text);">Se eliminarán los registros seleccionados desde el <strong id="lblFechaIni" style="color: var(--text);"></strong> hasta el <strong id="lblFechaFin" style="color: var(--text);"></strong>.</p>
                                <p class="small mb-3" style="font-weight: 700; color: var(--text);">Escribe la palabra <strong style="color:#ef4444;">RESTABLECER</strong> en mayúsculas para proceder:</p>
                                <input type="text" id="confirmTextRest" name="confirmacion" autocomplete="off" class="cn-input mb-3" placeholder="Escribe RESTABLECER aquí..." style="border:1.5px solid #ef4444; border-radius:10px;">
                                <div class="d-flex justify-content-end gap-2">
                                    <button type="button" id="cancelRestModal" class="btn btn-secondary px-3" style="border-radius:10px; font-weight:700;">Cancelar</button>
                                    <button type="submit" id="submitRestBtn" class="btn btn-danger px-4" disabled style="border-radius:10px; font-weight:800; opacity: 0.5; cursor: not-allowed;">Confirmar y Eliminar</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <!-- Modal Estado de Secciones -->
    <div id="modalSecciones" class="modal-nativo" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; overflow:auto; background-color:rgba(0,0,0,0.6); backdrop-filter:blur(4px); justify-content:center; align-items:center;">
        <div class="modal-content-nativo" style="max-width:500px; border-radius:18px; background:var(--card-bg); overflow:hidden; margin:auto; border:1px solid var(--border);">
            <div class="modal-header-nativo" style="padding:16px 20px; border-bottom:1px solid var(--border); display:flex; justify-content:space-between; align-items:center;">
                <h5 class="m-0 font-weight-bold" style="font-weight:800; color:var(--text);"><i class="bi bi-sliders text-accent me-2"></i> Estado de Secciones</h5>
                <span id="cerrarModal" class="cerrar" style="font-size:24px; font-weight:bold; cursor:pointer; color:var(--muted);">&times;</span>
            </div>
            <div class="modal-body-nativo" style="padding:20px;">
                <form action="" method="POST">
                    <?php foreach($config as $sec): ?>
                        <div class="mb-3">
                            <label for="sec_<?= $sec['id_s'] ?>" style="font-weight: 700; font-size:0.88rem; display: block; margin-bottom: 6px; color:var(--text); text-transform:capitalize;"><?= htmlspecialchars($sec['nombre']) ?></label>
                            <input type="hidden" name="secciones[<?= $sec['id_s'] ?>][id]" value="<?= $sec['id_s'] ?>">
                            <div style="display: flex; gap: 10px; align-items: center;">
                                <select name="secciones[<?= $sec['id_s'] ?>][estado]" id="sec_<?= $sec['id_s'] ?>" class="cn-input" style="border-radius:10px; font-weight:700;">
                                    <option value="1" <?= $sec['estado'] == '1' ? 'selected' : '' ?>>Activo</option>
                                    <option value="0" <?= $sec['estado'] == '0' ? 'selected' : '' ?>>Inactivo</option>
                                </select>
                                <input type="hidden" name="secciones[<?= $sec['id_s'] ?>][valor]" value="<?= htmlspecialchars($sec['valor'] ?? '') ?>">
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <div class="mt-4 text-end">
                        <button type="button" id="btnCancelarModal" class="btn btn-secondary px-3" style="border-radius:10px; font-weight:700;">Cancelar</button>
                        <button type="submit" class="btn btn-accent px-4" name="actualizarEstado" style="border-radius:10px; font-weight:800;"><i class="bi bi-save me-1"></i> Actualizar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div><!-- /.container-fluid -->

<style>
/* Estilos adicionales para Zona de Peligro */
.danger-check-card {
    background: var(--bg-subtle, #f8fafc);
    border: 1.5px solid var(--border, #cbd5e1);
    border-radius: 12px;
    padding: 14px 16px;
    display: flex;
    align-items: center;
    gap: 12px;
    cursor: pointer;
    transition: all 0.2s ease;
    user-select: none;
    height: 100%;
}
[data-bs-theme="dark"] .danger-check-card {
    background: rgba(255, 255, 255, 0.03);
    border-color: rgba(255, 255, 255, 0.12);
}
.danger-check-card:hover {
    border-color: #ef4444;
    background: rgba(239, 68, 68, 0.05);
    transform: translateY(-1px);
}
.danger-check-card input[type="checkbox"] {
    width: 18px;
    height: 18px;
    accent-color: #ef4444;
    cursor: pointer;
    flex-shrink: 0;
}
.danger-check-label {
    font-weight: 700;
    font-size: 0.88rem;
    cursor: pointer;
    color: var(--text);
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;
}
.news-admin-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.12);
}
.news-admin-card img:hover {
    transform: scale(1.05);
}
.chart-wrapper {
    position: relative;
    width: 100%;
    height: 320px;
}
.chart-wrapper canvas {
    width: 100% !important;
    height: 320px !important;
}
@media (max-width: 768px){
    .chart-wrapper { height: 260px; }
    .chart-wrapper canvas { height: 260px !important; }
}
</style>

<script>
    const charts = {};
    document.addEventListener("DOMContentLoaded", ()=>{ 
        loadGlobalStats(); 
        loadLikesStats();
        // Event listeners para filtros
        const filterInicio = document.getElementById('filterFechaInicio');
        const filterFin = document.getElementById('filterFechaFin');
        if (filterInicio) filterInicio.addEventListener('change', () => { loadGlobalStats(); loadLikesStats(); });
        if (filterFin) filterFin.addEventListener('change', () => { loadGlobalStats(); loadLikesStats(); });
    });

    // ================================
    // GLOBAL STATS
    // ================================
    function loadGlobalStats(){
        const f1=document.getElementById('filterFechaInicio').value;
        const f2=document.getElementById('filterFechaFin').value;
        fetch(`./../controllers/obtener_estadisticas_globales.php?fecha_inicio=${f1}&fecha_fin=${f2}`)
            .then(r=>r.json())
            .then(d=>{
                if (d.error) {
                    console.error('Error del servidor:', d.error);
                    return;
                }
                renderAreaChart('globalChartVistas', d, 'vistas', 'Vistas por categoría');
                renderAreaChart('globalChartTiempo', d, 'tiempo', 'Tiempo de lectura por categoría (Min)');
            })
            .catch(err => console.error('Error fetching global stats:', err));
    }

    // ================================
    // LIKES STATS
    // ================================
    function loadLikesStats(){
        const f1=document.getElementById('filterFechaInicio').value;
        const f2=document.getElementById('filterFechaFin').value;
        fetch(`./../controllers/obtener_estadisticas_likes.php?fecha_inicio=${f1}&fecha_fin=${f2}`)
            .then(r=>r.json())
            .then(d=>{
                if (d.error) {
                    console.error('Error del servidor:', d.error);
                    return;
                }
                renderAreaChart('globalChartLikes', d, 'likes', 'Likes por categoría');
                if (d.geo && d.geo.estados) {
                    renderBarChart('globalChartLikesRegion', d.geo.estados, 'Likes por Estado');
                }
            })
            .catch(err => console.error('Error fetching likes stats:', err));
    }

    // ================================
    // PALETA CURADA CATINK
    // ================================
    const CHART_PALETTE = [
        { stroke: '#EF3363', fill: 'rgba(239,51,99,0.12)' },
        { stroke: '#8b5cf6', fill: 'rgba(139,92,246,0.12)' },
        { stroke: '#10b981', fill: 'rgba(16,185,129,0.12)' },
        { stroke: '#f59e0b', fill: 'rgba(245,158,11,0.12)' },
        { stroke: '#3b82f6', fill: 'rgba(59,130,246,0.12)' },
        { stroke: '#06b6d4', fill: 'rgba(6,182,212,0.12)' },
        { stroke: '#ec4899', fill: 'rgba(236,72,153,0.12)' },
        { stroke: '#84cc16', fill: 'rgba(132,204,22,0.12)' },
        { stroke: '#f97316', fill: 'rgba(249,115,22,0.12)' },
    ];

    function isDark() {
        return document.documentElement.getAttribute('data-bs-theme') === 'dark';
    }

    function getChartOptions(title, type = 'line') {
        const dark = isDark();
        const gridColor  = dark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.06)';
        const textColor  = dark ? '#94a3b8' : '#64748b';
        const titleColor = dark ? '#f1f5f9' : '#1e293b';
        const mobile = window.matchMedia('(max-width: 768px)').matches;

        return {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            animation: { duration: 600, easing: 'easeInOutQuart' },
            plugins: {
                title: {
                    display: false
                },
                legend: {
                    position: mobile ? 'bottom' : 'top',
                    labels: {
                        color: textColor,
                        usePointStyle: true,
                        pointStyleWidth: 8,
                        boxHeight: 6,
                        padding: 14,
                        font: { size: 11, weight: '700', family: "'Inter', sans-serif" }
                    }
                },
                tooltip: {
                    backgroundColor: dark ? '#1e293b' : '#fff',
                    borderColor: dark ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.1)',
                    borderWidth: 1,
                    titleColor: titleColor,
                    bodyColor: textColor,
                    padding: 12,
                    cornerRadius: 10,
                    boxPadding: 4,
                    titleFont: { weight: '800', family: "'Inter', sans-serif" },
                    bodyFont: { family: "'Inter', sans-serif" }
                }
            },
            scales: type === 'bar' ? {
                x: {
                    grid: { display: false },
                    ticks: { color: textColor, font: { size: 10, weight: '600' }, maxRotation: 30 },
                    border: { display: false }
                },
                y: {
                    beginAtZero: true,
                    grid: { color: gridColor },
                    ticks: { color: textColor, font: { size: 10 }, padding: 8 },
                    border: { display: false, dash: [4, 4] }
                }
            } : {
                x: {
                    grid: { color: gridColor },
                    ticks: {
                        color: textColor,
                        font: { size: 10, weight: '600' },
                        maxRotation: 30,
                        autoSkip: true,
                        maxTicksLimit: mobile ? 6 : 12
                    },
                    border: { display: false }
                },
                y: {
                    beginAtZero: true,
                    grid: { color: gridColor },
                    ticks: { color: textColor, font: { size: 10 }, padding: 8 },
                    border: { display: false, dash: [4, 4] }
                }
            }
        };
    }

    function renderAreaChart(id, data, metric, title) {
        if (!data || !data.labels || data.labels.length === 0) return;
        const ctx = document.getElementById(id);
        if (!ctx) return;
        if (charts[id]) charts[id].destroy();

        const entries = data.categorias && Object.keys(data.categorias).length > 0
            ? Object.entries(data.categorias)
            : null;

        const datasets = entries
            ? entries.map(([cat, val], i) => {
                const c = CHART_PALETTE[i % CHART_PALETTE.length];
                return {
                    label: cat,
                    data: val[metric] || [],
                    fill: true,
                    tension: 0.45,
                    borderWidth: 2,
                    pointRadius: 0,
                    pointHoverRadius: 5,
                    pointHoverBorderWidth: 2,
                    pointHoverBackgroundColor: '#fff',
                    backgroundColor: c.fill,
                    borderColor: c.stroke
                };
            })
            : [{ label: 'Sin datos', data: Array(data.labels.length).fill(0), borderColor: '#94a3b8', backgroundColor: 'rgba(148,163,184,0.08)', fill: true, tension: 0.4, borderWidth: 2, pointRadius: 0 }];

        charts[id] = new Chart(ctx, {
            type: 'line',
            data: { labels: data.labels, datasets },
            options: getChartOptions(title, 'line')
        });
    }

    function renderBarChart(id, geo, title) {
        if (!geo || !geo.labels || geo.labels.length === 0) return;
        const ctx = document.getElementById(id);
        if (!ctx) return;
        if (charts[id]) charts[id].destroy();

        const limitedLabels = geo.labels.slice(0, 12);
        const limitedValues = geo.values.slice(0, 12);

        const barColors = limitedLabels.map((_, i) => CHART_PALETTE[i % CHART_PALETTE.length].stroke);
        const barFills  = limitedLabels.map((_, i) => CHART_PALETTE[i % CHART_PALETTE.length].fill.replace('0.12', '0.8'));

        charts[id] = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: limitedLabels,
                datasets: [{
                    label: title,
                    data: limitedValues,
                    backgroundColor: barFills,
                    borderColor: barColors,
                    borderWidth: 1.5,
                    borderRadius: 6,
                    borderSkipped: false
                }]
            },
            options: getChartOptions(title, 'bar')
        });
    }

    window.addEventListener('resize', () => {
        loadGlobalStats();
        loadLikesStats();
    });

    // ── Modal Estado de Secciones ─────────────────
    const modal = document.getElementById('modalSecciones');
    const btnAbrir = document.getElementById('btnAbrirModal');
    const btnCerrar = document.getElementById('cerrarModal');
    const btnCancelar = document.getElementById('btnCancelarModal');

    if(btnAbrir) btnAbrir.addEventListener('click', ()=> modal.style.display = 'flex');
    if(btnCerrar) btnCerrar.addEventListener('click', ()=> modal.style.display = 'none');
    if(btnCancelar) btnCancelar.addEventListener('click', ()=> modal.style.display = 'none');

    window.addEventListener('click', e => {
        if(e.target === modal) modal.style.display = 'none';
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
        }, 2700);
    }

    // ── Lógica Zona de Peligro ───────────────────
    const btnOpenRestModal = document.getElementById('btnOpenRestModal');
    const modalRestConfirm = document.getElementById('modalRestConfirm');
    const closeRestModal = document.getElementById('closeRestModal');
    const cancelRestModal = document.getElementById('cancelRestModal');
    const confirmTextRest = document.getElementById('confirmTextRest');
    const submitRestBtn = document.getElementById('submitRestBtn');
    const restFechaInicio = document.getElementById('rest_fecha_inicio');
    const restFechaFin = document.getElementById('rest_fecha_fin');
    const lblFechaIni = document.getElementById('lblFechaIni');
    const lblFechaFin = document.getElementById('lblFechaFin');

    if (btnOpenRestModal) {
        btnOpenRestModal.addEventListener('click', () => {
            const checkedCount = document.querySelectorAll('.mod-check:checked').length;
            if (checkedCount === 0) {
                showToast('Por favor, selecciona al menos un tipo de datos para restablecer.', 'error');
                return;
            }

            if (!restFechaInicio.value || !restFechaFin.value) {
                showToast('Por favor, ingresa el rango de fechas de inicio y fin.', 'error');
                return;
            }

            lblFechaIni.textContent = restFechaInicio.value;
            lblFechaFin.textContent = restFechaFin.value;

            confirmTextRest.value = '';
            submitRestBtn.disabled = true;
            submitRestBtn.style.opacity = '0.5';
            submitRestBtn.style.cursor = 'not-allowed';

            modalRestConfirm.style.display = 'flex';
        });
    }

    const hideRestModal = () => {
        if (modalRestConfirm) modalRestConfirm.style.display = 'none';
    };

    if (closeRestModal) closeRestModal.addEventListener('click', hideRestModal);
    if (cancelRestModal) cancelRestModal.addEventListener('click', hideRestModal);

    if (confirmTextRest) {
        confirmTextRest.addEventListener('input', () => {
            if (confirmTextRest.value === 'RESTABLECER') {
                submitRestBtn.disabled = false;
                submitRestBtn.style.opacity = '1';
                submitRestBtn.style.cursor = 'pointer';
            } else {
                submitRestBtn.disabled = true;
                submitRestBtn.style.opacity = '0.5';
                submitRestBtn.style.cursor = 'not-allowed';
            }
        });
    }
</script>

<?php include(__DIR__ . "/../layout/footerAdmin.php"); ?>