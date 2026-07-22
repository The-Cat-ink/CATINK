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
// Últimas noticias (TOP 5) SIN DUPLICAR TIEMPO
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
$configResult = $con->query("SELECT * FROM secciones");
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
<div class="container-fluid">
    <!-- SALUDO -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 style="font-weight: 800; font-size: 1.8rem; margin: 0; color: var(--text);">Bienvenido, <?= htmlspecialchars($fila['usuario']) ?></h1>
    </div>

    <!-- Alertas de Restablecimiento de Sistema -->
    <?php if (isset($_GET['restablecido'])): ?>
        <div class="alert alert-success alert-dismissible fade show mb-3" role="alert" style="background:rgba(16,185,129,0.1); color:#10b981; border:1px solid rgba(16,185,129,0.2); border-radius:10px; padding: 10px 16px;">
            <i class="bi bi-check-circle-fill"></i> ¡El restablecimiento se realizó con éxito! Módulos borrados: <strong><?= htmlspecialchars($_GET['modulos_borrados'] ?? '') ?></strong>.
        </div>
    <?php elseif (isset($_GET['restablecer_error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert" style="background:rgba(220,53,69,0.1); color:#dc3545; border:1px solid rgba(220,53,69,0.2); border-radius:10px; padding: 10px 16px;">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <?php 
                if ($_GET['restablecer_error'] == '1') {
                    echo 'Por favor, completa todos los campos obligatorios del formulario e ingresa la palabra clave exacta en mayúsculas.';
                } else {
                    echo 'Ocurrió un error interno en el servidor al intentar restablecer el sistema. Intenta de nuevo.';
                }
            ?>
        </div>
    <?php endif; ?>

    <!-- BOTONES QUICKS DE ADMINISTRADOR -->
    <style>
    .admin-quick-toolbar {
      display: flex;
      flex-wrap: wrap;
      gap: 12px;
      align-items: center;
      margin-bottom: 20px;
    }
    .admin-quick-toolbar .btn-accent {
      border-radius: 12px;
      padding: 10px 20px;
      font-weight: 700;
      font-size: 0.93rem;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      box-shadow: 0 4px 14px rgba(239, 51, 99, 0.22);
      transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .admin-quick-toolbar .btn-accent:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 18px rgba(239, 51, 99, 0.32);
    }
    .btn-panic-deactivate {
      background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
      color: #ffffff !important;
      border: none;
      padding: 10px 20px;
      font-size: 0.93rem;
      font-weight: 700;
      border-radius: 12px;
      cursor: pointer;
      transition: all 0.2s ease;
      box-shadow: 0 4px 14px rgba(239, 68, 68, 0.3);
      display: inline-flex;
      align-items: center;
      gap: 8px;
      text-decoration: none;
    }
    .btn-panic-deactivate:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 18px rgba(239, 68, 68, 0.45);
      color: #ffffff !important;
    }
    .btn-panic-activate {
      background: linear-gradient(135deg, #10b981 0%, #059669 100%);
      color: #ffffff !important;
      border: none;
      padding: 10px 20px;
      font-size: 0.93rem;
      font-weight: 700;
      border-radius: 12px;
      cursor: pointer;
      transition: all 0.2s ease;
      box-shadow: 0 4px 14px rgba(16, 185, 129, 0.3);
      display: inline-flex;
      align-items: center;
      gap: 8px;
      text-decoration: none;
    }
    .btn-panic-activate:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 18px rgba(16, 185, 129, 0.45);
      color: #ffffff !important;
    }
    </style>
    <div class="admin-quick-toolbar">
        <?php if($ACLNoticias['crear']): ?>
            <a href="crear.php" class="btn btn-accent"><i class="bi bi-plus-lg"></i> Nueva Noticia</a>
        <?php endif; ?> 
        <?php if($superadmin): ?>
            <a href="paginas.php" class="btn btn-accent"><i class="bi bi-card-text"></i> Editar Páginas Informativas</a>
            <button id="btnAbrirModal" class="btn btn-accent">
                <i class="bi bi-gear-fill"></i> Gestionar Estado de Secciones
            </button>
            <?php
            $comentariosSecRes = $con->query("SELECT estado FROM secciones WHERE nombre = 'comentarios' LIMIT 1");
            $comentariosSec = $comentariosSecRes->fetch_assoc();
            $comentariosActivos = $comentariosSec ? ($comentariosSec['estado'] == 1) : true;
            ?>
            <form action="" method="POST" style="display: inline-block; margin: 0;">
                <?php if ($comentariosActivos): ?>
                    <input type="hidden" name="estado_panico" value="0">
                    <button type="submit" name="toggle_panico" class="btn-panic-deactivate">
                        <i class="bi bi-exclamation-triangle-fill"></i> Desactivar Comentarios (PÁNICO)
                    </button>
                <?php else: ?>
                    <input type="hidden" name="estado_panico" value="1">
                    <button type="submit" name="toggle_panico" class="btn-panic-activate">
                        <i class="bi bi-check-circle-fill"></i> Activar Comentarios
                    </button>
                <?php endif; ?>
            </form>
        <?php endif; ?>
    </div>

    <!-- SECCIÓN RECONSTRUIDA DE YOUTUBE PLAYLIST (ALINEACIÓN PERFECTA DE ANCHO COMPLETO) -->
    <?php if($superadmin): ?>
        <?php 
            $playlistVal = '';
            foreach($config as $sec) {
                if($sec['nombre'] === 'videos') {
                    $playlistVal = $sec['valor'];
                }
            }
        ?>
        <div class="card shadow-sm mb-4" style="border-radius: 16px; border: 1px solid var(--border); overflow: hidden;">
            <div class="card-body" style="padding: 18px 24px;">
                <form action="" method="POST" style="margin: 0;">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <div style="width: 34px; height: 34px; border-radius: 10px; background: rgba(255, 0, 0, 0.1); color: #ff0000; display: flex; align-items: center; justify-content: center; font-size: 1.15rem; flex-shrink: 0;">
                            <i class="bi bi-youtube"></i>
                        </div>
                        <div>
                            <h6 class="mb-0" style="font-weight: 800; font-size: 0.98rem; color: var(--text);">Lista de Reproducción (YouTube)</h6>
                            <small class="text-muted" style="font-size: 0.78rem;">ID o enlace URL de la lista oficial de videos en la Home</small>
                        </div>
                    </div>

                    <div style="display: flex; gap: 12px; align-items: center; width: 100%;">
                        <div style="position: relative; flex: 1;">
                            <i class="bi bi-link-45deg" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--muted); font-size: 1.2rem;"></i>
                            <input type="text" id="youtube_playlist_id" name="youtube_playlist" value="<?= htmlspecialchars($playlistVal) ?>" placeholder="Ej: PLMC9KNincKvYin_USF1QeqG50KB1K1uD" style="width: 100%; padding: 11px 16px 11px 40px; border-radius: 12px; border: 1.5px solid var(--border); background: var(--bg-subtle, #f8fafc); color: var(--text); font-weight: 600; font-size: 0.92rem; outline: none; transition: border-color 0.2s;">
                        </div>
                        <button type="submit" class="btn btn-accent" name="actualizarPlaylist" style="padding: 11px 24px; border-radius: 12px; font-weight: 700; white-space: nowrap; font-size: 0.92rem; display: inline-flex; align-items: center; gap: 8px; flex-shrink: 0;">
                            <i class="bi bi-arrow-repeat"></i> Actualizar Playlist
                        </button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <!-- ÚLTIMAS NOTICIAS -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-light"><h5>Últimas Noticias</h5></div>
            <div class="card-body">
                <div class="row row-cols-1 row-cols-md-3 g-4">
                    <?php foreach($ultimasNoticias as $n):
                        $desc = mb_strimwidth($n['descripcion'] ?? '', 0, 80, '...');
                        $img = !empty($n['crop3']) ? imageUrl($n['crop3']) : imageUrl('img/placeholder.svg');
                    ?>
                    <div class="col">
                        <div class="card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <small><?= date('d/m/Y H:i', strtotime($n['fecha_publicacion'])) ?></small>
                                <?php if($ACLNoticias['editar']): ?>
                                    <a href="<?= basePath() ?>/views/editar.php?id=<?= $n['id'] ?>" class="btn btn-sm btn-primary py-0 px-2" title="Editar"><i class="bi bi-pencil"></i></a>
                                <?php endif; ?>
                            </div>
                            <a href="<?= newsUrlFromRow($n) ?>" target="_blank" style="display:block;">
                                <img src="<?= htmlspecialchars($img) ?>" class="card-img-top" style="object-fit:cover; height:200px;">
                            </a>
                            <div class="card-body">
                                <div class="news-tags mb-2">
                                    <?php foreach(array_filter(array_map('trim', explode(',', $n['categorias'] ?? ''))) as $cat): ?>
                                        <span class="news-tag"><?= htmlspecialchars($cat) ?></span>
                                    <?php endforeach; ?>
                                </div>
                                <h5 class="card-title text-truncate">
                                    <a href="<?= newsUrlFromRow($n) ?>" target="_blank" class="text-decoration-none text-dark">
                                        <?= htmlspecialchars($n['titulo']) ?>
                                    </a>
                                </h5>
                                <p class="small text-muted"><?= htmlspecialchars($desc) ?></p>
                            </div>
                            <div class="card-footer d-flex card-especial justify-content-between">
                                <span><i class="bi bi-eye"></i> <?= formatNumberShort($n['vistas']) ?> </span>
                                <span><i class="bi bi-clock"></i> <?= number_format($n['tiempo_total_stats']/60,0) ?>m </span>
                                <span><i class="bi bi-heart"></i> <?= formatNumberShort($n['likes']) ?> </span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

    <!-- KPIs -->
    <div class="kpi-section">
        <div class="kpi-section__header">
            <div class="kpi-section__title">
                <i class="bi bi-bar-chart-fill"></i>
                <div>
                    <h2>Panel de Métricas</h2>
                    <p>Resumen general del rendimiento de la plataforma</p>
                </div>
            </div>
            <!-- Filtros en línea -->
            <div class="kpi-filter-bar">
                <div class="kpi-filter-field">
                    <i class="bi bi-calendar-event"></i>
                    <input type="date" id="filterFechaInicio" value="<?= date('Y-m-d', strtotime('-30 days')) ?>">
                </div>
                <span class="kpi-filter-sep">→</span>
                <div class="kpi-filter-field">
                    <i class="bi bi-calendar-check"></i>
                    <input type="date" id="filterFechaFin" value="<?= date('Y-m-d') ?>">
                </div>
                <button class="kpi-filter-btn" onclick="loadGlobalStats(); loadLikesStats();">
                    <i class="bi bi-funnel-fill"></i> Aplicar
                </button>
            </div>
        </div>

        <div class="kpi-grid">
            <?php
                $kpiCards = [
                    ['label' => 'Total Noticias', 'value' => $kpis['total_noticias'], 'icon' => 'bi-newspaper', 'color' => '#6366f1', 'bg' => 'rgba(99,102,241,0.08)', 'trend' => null],
                    ['label' => 'Publicadas',     'value' => $kpis['publicadas'],      'icon' => 'bi-check-circle-fill', 'color' => '#10b981', 'bg' => 'rgba(16,185,129,0.08)', 'trend' => null],
                    ['label' => 'Programadas',    'value' => $kpis['programadas'],     'icon' => 'bi-clock-fill',        'color' => '#f59e0b', 'bg' => 'rgba(245,158,11,0.08)',  'trend' => null],
                    ['label' => 'Vistas',         'value' => number_format($kpis['total_vistas']), 'icon' => 'bi-eye-fill', 'color' => '#3b82f6', 'bg' => 'rgba(59,130,246,0.08)', 'trend' => null],
                    ['label' => 'Likes',          'value' => number_format($kpis['total_likes']),  'icon' => 'bi-heart-fill', 'color' => '#ef4444', 'bg' => 'rgba(239,68,68,0.08)',   'trend' => null],
                    ['label' => 'Tiempo (min)',   'value' => number_format($tiempoTotal/60),        'icon' => 'bi-stopwatch-fill', 'color' => '#8b5cf6', 'bg' => 'rgba(139,92,246,0.08)', 'trend' => null],
                ];
                foreach($kpiCards as $k):
            ?>
            <div class="kpi-card">
                <div class="kpi-card__icon" style="background:<?= $k['bg'] ?>; color:<?= $k['color'] ?>;">
                    <i class="bi <?= $k['icon'] ?>"></i>
                </div>
                <div class="kpi-card__body">
                    <div class="kpi-card__value"><?= $k['value'] ?></div>
                    <div class="kpi-card__label"><?= $k['label'] ?></div>
                </div>
                <div class="kpi-card__bar" style="background: linear-gradient(90deg, <?= $k['color'] ?>22 0%, transparent 100%);"></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <!-- GRÁFICOS -->
    <div class="charts-container">
        <h5 class="mb-3">Estadísticas Globales</h5>
        <div class="card mb-4">
            <div class="card-body">
                <div class="chart-wrapper">
                    <canvas id="globalChartVistas"></canvas>
                </div>
            </div>
        </div>
        <div class="card mb-4">
            <div class="card-body">
                <div class="chart-wrapper">
                    <canvas id="globalChartTiempo"></canvas>
                </div>
            </div>
        </div>
        <div class="card mb-4">
            <div class="card-body">
                <div class="chart-wrapper">
                    <canvas id="globalChartLikes"></canvas>
                </div>
            </div>
        </div>
        <div class="card mb-4">
            <div class="card-body">
                <div class="chart-wrapper">
                    <canvas id="globalChartLikesRegion"></canvas>
                </div>
            </div>
        </div>
    </div>

    <?php if(isset($_SESSION['usuario'])): ?>
        <!-- ZONA DE PELIGRO: RESTABLECIMIENTO GRANULAR -->
        <style>
        .danger-zone-card {
          background: var(--card-bg, #ffffff);
          border: 1.5px solid rgba(239, 68, 68, 0.4) !important;
          border-radius: 18px !important;
          overflow: hidden;
          box-shadow: 0 8px 30px rgba(239, 68, 68, 0.08);
          margin-top: 28px;
        }
        .danger-zone-header {
          background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%) !important;
          color: #ffffff;
          padding: 16px 24px;
          display: flex;
          align-items: center;
          gap: 12px;
        }
        .danger-zone-header h5 {
          margin: 0;
          font-weight: 800;
          font-size: 1.08rem;
          color: #ffffff;
        }
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
          font-size: 0.9rem;
          cursor: pointer;
          color: var(--text);
          margin: 0;
          display: flex;
          align-items: center;
          gap: 8px;
        }
        .btn-danger-submit {
          background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
          color: #ffffff !important;
          border: none;
          padding: 14px 28px;
          font-weight: 800;
          font-size: 1rem;
          border-radius: 14px;
          cursor: pointer;
          width: 100%;
          transition: all 0.2s ease;
          box-shadow: 0 4px 16px rgba(239, 68, 68, 0.35);
          display: flex;
          align-items: center;
          justify-content: center;
          gap: 10px;
        }
        .btn-danger-submit:hover {
          transform: translateY(-2px);
          box-shadow: 0 6px 22px rgba(239, 68, 68, 0.45);
        }
        </style>
        <div class="card shadow-sm mb-4 danger-zone-card">
            <div class="card-header danger-zone-header">
                <i class="bi bi-exclamation-octagon-fill fs-5"></i>
                <h5 class="mb-0">Zona de Peligro: Restablecer Información del Sistema</h5>
            </div>
            <div class="card-body" style="padding: 24px;">
                <p class="text-muted small mb-4" style="line-height: 1.6; font-size: 0.92rem;">
                  Esta sección permite eliminar de forma masiva y permanente los contenidos seleccionados en un rango de fechas especificado. Las cuentas de administradores, editores y suscriptores no se verán afectadas bajo ninguna circunstancia. Puedes decidir qué módulos específicos deseas limpiar.
                </p>
                <form action="<?= basePath() ?>/controllers/restablecer_granular.php" method="POST" id="formRestablecer">
                    <div class="row g-3 mb-4">
                        <div class="col-md-6 col-12">
                            <label for="rest_fecha_inicio" style="font-weight: 700; display: block; margin-bottom: 8px; color: var(--text);">Fecha de Inicio:</label>
                            <div style="position: relative;">
                              <input type="date" id="rest_fecha_inicio" name="fecha_inicio" required class="form-control" style="padding: 11px 14px; border-radius: 12px; border: 1.5px solid var(--border); background: var(--bg-subtle, #f8fafc); color: var(--text); font-weight: 600; outline: none;">
                            </div>
                        </div>
                        <div class="col-md-6 col-12">
                            <label for="rest_fecha_fin" style="font-weight: 700; display: block; margin-bottom: 8px; color: var(--text);">Fecha de Fin:</label>
                            <div style="position: relative;">
                              <input type="date" id="rest_fecha_fin" name="fecha_fin" required class="form-control" style="padding: 11px 14px; border-radius: 12px; border: 1.5px solid var(--border); background: var(--bg-subtle, #f8fafc); color: var(--text); font-weight: 600; outline: none;">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label style="font-weight: 700; display: block; margin-bottom: 14px; color: var(--text);">Selecciona qué información deseas borrar en el rango especificado:</label>
                        <div class="row row-cols-1 row-cols-md-3 g-3">
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

                    <button type="button" id="btnOpenRestModal" class="btn-danger-submit">
                        <i class="bi bi-trash3-fill"></i> Iniciar Restablecimiento
                    </button>

                    <!-- Modal de Confirmación Interno -->
                    <div id="modalRestConfirm" class="modal-nativo" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; overflow:auto; background-color:rgba(0,0,0,0.5); justify-content:center; align-items:center;">
                        <div class="modal-content-nativo" style="border: 2px solid #dc3545; max-width: 450px; border-radius: 12px; background: var(--card-bg); overflow: hidden; margin: 15% auto; box-shadow: 0 8px 30px rgba(0,0,0,0.2);">
                            <div class="modal-header-nativo" style="background: #dc3545; color: #fff; padding: 16px 20px; display: flex; justify-content: space-between; align-items: center;">
                                <h5 class="mb-0" style="color: #fff; font-weight: 600;"><i class="bi bi-exclamation-triangle-fill"></i> ¿Confirmar Restablecimiento?</h5>
                                <span id="closeRestModal" class="cerrar" style="color:#fff; font-size: 28px; font-weight: bold; cursor: pointer;">&times;</span>
                            </div>
                            <div class="modal-body-nativo" style="padding: 20px;">
                                <p style="font-weight:600; color:#dc3545; margin-bottom: 12px;">¡Atención! Esta acción borrará permanentemente los datos indicados.</p>
                                <p class="small mb-3" style="color: var(--text);">Se eliminarán los registros seleccionados desde el <strong id="lblFechaIni" style="color: var(--text);"></strong> hasta el <strong id="lblFechaFin" style="color: var(--text);"></strong>.</p>
                                <p class="small mb-3" style="font-weight: 500; color: var(--text);">Escribe la palabra <strong style="color:#dc3545;">RESTABLECER</strong> en mayúsculas para proceder:</p>
                                <input type="text" id="confirmTextRest" autocomplete="off" class="form-control mb-3" placeholder="Escribe aquí..." style="padding:10px 14px; border-radius:8px; border:1px solid #dc3545; width: 100%; background: var(--bg); color: var(--text);">
                                <div style="display:flex; justify-content:flex-end; gap:10px; margin-top: 15px;">
                                    <button type="button" id="cancelRestModal" class="btn btn-secondary" style="background:#6c757d; border:none; padding:8px 16px; border-radius:6px; color:#fff; cursor: pointer;">Cancelar</button>
                                    <button type="submit" id="submitRestBtn" class="btn btn-danger" disabled style="background:#dc3545; border:none; padding:8px 16px; border-radius:6px; color:#fff; font-weight:600; opacity: 0.5; cursor: not-allowed;">Confirmar y Eliminar</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <!-- Modal -->
    <div id="modalSecciones" class="modal-nativo">
        <div class="modal-content-nativo">
            <div class="modal-header-nativo">
                <h5>Estado de Secciones</h5>
                <span id="cerrarModal" class="cerrar">&times;</span>
            </div>
            <div class="modal-body-nativo">
                <form action="" method="POST">
                    <?php foreach($config as $sec): ?>
                        <div class="mb-3">
                            <label for="sec_<?= $sec['id_s'] ?>" style="font-weight: 600; display: block; margin-bottom: 4px;"><?= htmlspecialchars(ucfirst($sec['nombre'])) ?></label>
                            <input type="hidden" name="secciones[<?= $sec['id_s'] ?>][id]" value="<?= $sec['id_s'] ?>">
                            <div style="display: flex; gap: 10px; align-items: center;">
                                <select name="secciones[<?= $sec['id_s'] ?>][estado]" id="sec_<?= $sec['id_s'] ?>" style="padding: 6px 12px; border-radius: 6px; border: 1px solid var(--border); background: var(--card-bg); color: var(--text);">
                                    <option value="1" <?= $sec['estado'] == '1' ? 'selected' : '' ?>>Activo</option>
                                    <option value="0" <?= $sec['estado'] == '0' ? 'selected' : '' ?>>Inactivo</option>
                                </select>
                                <input type="hidden" name="secciones[<?= $sec['id_s'] ?>][valor]" value="<?= htmlspecialchars($sec['valor'] ?? '') ?>">
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <div class="mt-3" style="text-align:right;">
                        <button type="button" id="btnCancelarModal" class="btn btn-accent" style="background:#6c757d;">Cancelar</button>
                        <button type="submit" class="btn btn-accent" name="actualizarEstado">Actualizar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
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
    // PALETA CURADA
    // ================================
    const CHART_PALETTE = [
        { stroke: '#6366f1', fill: 'rgba(99,102,241,0.12)' },
        { stroke: '#f43f5e', fill: 'rgba(244,63,94,0.12)' },
        { stroke: '#10b981', fill: 'rgba(16,185,129,0.12)' },
        { stroke: '#f59e0b', fill: 'rgba(245,158,11,0.12)' },
        { stroke: '#3b82f6', fill: 'rgba(59,130,246,0.12)' },
        { stroke: '#8b5cf6', fill: 'rgba(139,92,246,0.12)' },
        { stroke: '#06b6d4', fill: 'rgba(6,182,212,0.12)' },
        { stroke: '#ec4899', fill: 'rgba(236,72,153,0.12)' },
        { stroke: '#84cc16', fill: 'rgba(132,204,22,0.12)' },
        { stroke: '#f97316', fill: 'rgba(249,115,22,0.12)' },
    ];

    function isDark() {
        return document.documentElement.getAttribute('data-bs-theme') === 'dark';
    }

    // ================================
    // OPCIONES BASE PREMIUM
    // ================================
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
                    display: true,
                    text: title,
                    color: titleColor,
                    font: { size: 14, weight: '600', family: "'Inter', sans-serif" },
                    padding: { bottom: 16 }
                },
                legend: {
                    position: mobile ? 'bottom' : 'top',
                    labels: {
                        color: textColor,
                        usePointStyle: true,
                        pointStyleWidth: 10,
                        boxHeight: 6,
                        padding: 16,
                        font: { size: 12, family: "'Inter', sans-serif" }
                    }
                },
                tooltip: {
                    backgroundColor: dark ? '#1e293b' : '#fff',
                    borderColor: dark ? 'rgba(255,255,255,0.08)' : 'rgba(0,0,0,0.08)',
                    borderWidth: 1,
                    titleColor: titleColor,
                    bodyColor: textColor,
                    padding: 12,
                    cornerRadius: 10,
                    boxPadding: 4,
                    titleFont: { weight: '700', family: "'Inter', sans-serif" },
                    bodyFont: { family: "'Inter', sans-serif" }
                }
            },
            scales: type === 'bar' ? {
                x: {
                    grid: { display: false },
                    ticks: { color: textColor, font: { size: 11 }, maxRotation: 30 },
                    border: { display: false }
                },
                y: {
                    beginAtZero: true,
                    grid: { color: gridColor, drawBorder: false },
                    ticks: { color: textColor, font: { size: 11 }, padding: 8 },
                    border: { display: false, dash: [4, 4] }
                }
            } : {
                x: {
                    grid: { color: gridColor, drawBorder: false },
                    ticks: {
                        color: textColor,
                        font: { size: 10 },
                        maxRotation: 30,
                        autoSkip: true,
                        maxTicksLimit: mobile ? 6 : 12
                    },
                    border: { display: false }
                },
                y: {
                    beginAtZero: true,
                    grid: { color: gridColor, drawBorder: false },
                    ticks: { color: textColor, font: { size: 11 }, padding: 8 },
                    border: { display: false, dash: [4, 4] }
                }
            }
        };
    }

    // ================================
    // CHART ÁREA (líneas + relleno suave)
    // ================================
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

    // ================================
    // CHART BAR GEO (barras horizontales)
    // ================================
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
</script>
<style>
.charts-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    width: 100%;
}

.charts-container .card {
    width: 100%;
    max-width: calc(100% - 2rem);
}

.chart-wrapper {
    position: relative;
    width: 100%;
    height: 400px;
}

.chart-wrapper canvas {
    width: 100% !important;
    height: 400px !important;
}

@media (max-width: 768px){
    .kpi-row-mobile-fix{
        flex-wrap: wrap;
        display: flex;
    }
    .kpi-col-mobile-fix{
        flex: 1 1 60px;
    }
    .kpi-col-mobile-fix .card-body{
        padding: 0.75rem;
    }
    .kpi-col-mobile-fix h4{
        font-size: 1.05rem;
        padding: 0;
    }
    .chart-wrapper {
        height: 300px;
    }
    .chart-wrapper canvas {
        height: 300px !important;
    }
    #filterFechaInicio,
    #filterFechaFin{
        min-height: 42px;
    }
    #btnAbrirModal,
    a.btn.btn-accent{
        width: 100%;
        margin-bottom: 0.5rem;
    }
}
</style>
<script>
const modal = document.getElementById('modalSecciones');
const btnAbrir = document.getElementById('btnAbrirModal');
const btnCerrar = document.getElementById('cerrarModal');
const btnCancelar = document.getElementById('btnCancelarModal');
// Abrir modal
if(btnAbrir){
    btnAbrir.addEventListener('click', ()=> modal.style.display = 'block');
}
// Cerrar modal
if(btnCerrar){
    btnCerrar.addEventListener('click', ()=> modal.style.display = 'none');
}
if(btnCancelar){
    btnCancelar.addEventListener('click', ()=> modal.style.display = 'none');
}
// Cerrar si se da click fuera del contenido
window.addEventListener('click', e => {
    if(e.target === modal) modal.style.display = 'none';
});

// Función auxiliar para notificaciones premium de tipo Toast
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

// Lógica de Zona de Peligro (Restablecer)
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
        // Validar que se seleccionó al menos un módulo
        const checkedCount = document.querySelectorAll('.mod-check:checked').length;
        if (checkedCount === 0) {
            showToast('Por favor, selecciona al menos un tipo de datos para restablecer.', 'error');
            return;
        }

        // Validar que se ingresaron las fechas
        if (!restFechaInicio.value || !restFechaFin.value) {
            showToast('Por favor, ingresa el rango de fechas de inicio y fin.', 'error');
            return;
        }

        // Cargar las fechas en las etiquetas del modal
        lblFechaIni.textContent = restFechaInicio.value;
        lblFechaFin.textContent = restFechaFin.value;

        // Resetear input de confirmación
        confirmTextRest.value = '';
        submitRestBtn.disabled = true;
        submitRestBtn.style.opacity = '0.5';
        submitRestBtn.style.cursor = 'not-allowed';

        // Mostrar modal
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