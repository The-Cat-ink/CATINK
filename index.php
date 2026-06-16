<?php
include(__DIR__ . "/layout/header.php");
require_once(__DIR__ . "/data/conexion.php");
include(__DIR__ . "/views/helpers/videoEmbed.php");
require_once(__DIR__ . "/views/helpers/urlhelper.php");
// =====================
// Obtener todas las noticias con sus categorías
// =====================
$stmt = $con->prepare("
    SELECT
        n.id,
        n.slug,
        n.titulo,
        n.descripcion,
        n.crop1,
        n.crop2,
        n.crop3,
        n.fecha_publicacion AS fecha,
        n.likes,
        n.vistas,
        u.nombre AS nombre_u,
        GROUP_CONCAT(c.nombre SEPARATOR ',') AS categorias
    FROM noticias n
    INNER JOIN usuarios u ON n.autor = u.id_u
    LEFT JOIN noticia_categoria nc ON n.id = nc.noticia_id
    LEFT JOIN categorias c ON nc.categoria_id = c.id_c
    WHERE n.fecha_publicacion <= NOW()
    GROUP BY n.id
    ORDER BY n.fecha_publicacion DESC
    LIMIT 50;
");
$stmt->execute();
$result = $stmt->get_result();
$noticias = $result->fetch_all(MYSQLI_ASSOC);
if(empty($noticias)){
    echo '<div class="container mt-5 text-center"><h2>No hay noticias publicadas aún.</h2><p style="color:var(--muted);">Crea tu primera noticia desde el panel de administración.</p></div>';
    include(__DIR__ . "/layout/footer.php");
    exit;
}
// Últimas 3 noticias para sidebar
$ultimasNoticiasSidebar = array_slice($noticias, 0, 3);
// Noticias más populares (por vistas)
$popularesNoticiasSidebar = $noticias;
usort($popularesNoticiasSidebar, fn($a,$b)=>$b['vistas']-$a['vistas']);
$popularesNoticiasSidebar = array_slice($popularesNoticiasSidebar, 0, 5);
// Noticias principales para slider y últimas
$slider = array_slice($noticias, 0, 5);

// 1. Obtener noticias de la semana
$dias_desde_lunes = date('N') - 1;
$inicio_semana = strtotime("-$dias_desde_lunes days midnight");
$noticias_semana = array_filter($noticias, function($n) use ($inicio_semana) {
    return strtotime($n['fecha']) >= $inicio_semana;
});

// 2. Determinar si necesitamos rellenar
$count_semana = count($noticias_semana);
$topTitulo = "Top Semanal";

if ($count_semana < 7) {
    // Necesitamos rellenar con noticias del mes
    $inicio_mes = strtotime('first day of this month midnight');
    
    // Obtener noticias del mes (que no sean de esta semana)
    $noticias_mes = array_filter($noticias, function($n) use ($inicio_mes, $inicio_semana) {
        $fecha_n = strtotime($n['fecha']);
        return $fecha_n >= $inicio_mes && $fecha_n < $inicio_semana;
    });
    
    // Ordenar las del mes por vistas para agarrar las más visitadas
    usort($noticias_mes, fn($a,$b) => $b['vistas'] - $a['vistas']);
    
    // Tomar solo las necesarias para completar 7
    $faltantes = 7 - $count_semana;
    $relleno = array_slice($noticias_mes, 0, $faltantes);
    
    // Combinar semana y relleno
    $ultimasNoticias = array_merge($noticias_semana, $relleno);
} else {
    $ultimasNoticias = $noticias_semana;
}

// 3. Ordenar el resultado final (sean puras de la semana o combinadas) por vistas
usort($ultimasNoticias, fn($a,$b) => $b['vistas'] - $a['vistas']);
$ultimasNoticias = array_slice($ultimasNoticias, 0, 7);
$noticiasMasRecientes = array_slice($noticias, 0, 6);
$noticiasMasRecientes2 = array_slice($noticias, 7, 5);
$noticiasMasRecientes3 = array_slice($noticias, 12, 17);
//Obtener publicidad (banner, cuadro e inferior) en una sola consulta
$stmt = $con->prepare("
    (SELECT *, 'banner' as tipo_pub FROM publicidad WHERE activo = 1 AND tipo = 1 AND fecha_fin >= NOW() ORDER BY RAND() LIMIT 1)
    UNION ALL
    (SELECT *, 'cuadro' as tipo_pub FROM publicidad WHERE activo = 1 AND tipo = 2 AND fecha_fin >= NOW() ORDER BY RAND() LIMIT 1)
    UNION ALL
    (SELECT *, 'inferior' as tipo_pub FROM publicidad WHERE activo = 1 AND tipo = 1 ORDER BY RAND() LIMIT 1)
");
$stmt->execute();
$publicidadResult = $stmt->get_result();

$publicidad = null;
$publicidadCuadro = null;
$publicidadInferior = null;

while ($row = $publicidadResult->fetch_assoc()) {
    if ($row['tipo_pub'] === 'banner') {
        $publicidad = $row;
    } elseif ($row['tipo_pub'] === 'cuadro') {
        $publicidadCuadro = $row;
    } elseif ($row['tipo_pub'] === 'inferior') {
        $publicidadInferior = $row;
    }
}
// obtener videos
$stmt = $con->prepare("SELECT * FROM videos WHERE activo = 1 ORDER BY id_v DESC LIMIT 6");
$stmt->execute();
$vid = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
// ==============================
// NOTICIAS RECOMENDADAS
// ==============================
$recomendadas = [];
$sqlRec = "
    SELECT id, slug, titulo, descripcion, crop1, crop2, crop3, fecha_publicacion, nombre
    FROM noticias, usuarios
    WHERE noticias.autor = usuarios.id_u and fecha_publicacion <= NOW()
    ORDER BY likes DESC, vistas DESC, fecha_publicacion DESC
    LIMIT 3
";
$stmtRec = $con->prepare($sqlRec);
$stmtRec->execute();
$recomendadas = $stmtRec->get_result();
// ==============================
// NOTICIAS MAS COMENTADAS
// ==============================
$masComentadas = [];
$sqlCom = "
    SELECT n.id, n.slug, n.titulo, n.descripcion, n.crop1, n.crop2, n.crop3, n.fecha_publicacion, u.nombre, COUNT(c.id_comentario) as total_comentarios
    FROM noticias n
    JOIN usuarios u ON n.autor = u.id_u
    LEFT JOIN comentarios c ON c.noticia_id = n.id AND c.estado = 'activo'
    WHERE n.fecha_publicacion <= NOW()
    GROUP BY n.id
    ORDER BY total_comentarios DESC, n.fecha_publicacion DESC
    LIMIT 8
";
$stmtCom = $con->prepare($sqlCom);
$stmtCom->execute();
$masComentadas = $stmtCom->get_result();

$debatidas = [];
while ($row = $masComentadas->fetch_assoc()) {
    $debatidas[] = $row;
}
$debatidasLeft = array_slice($debatidas, 0, 3);
$debatidasRight = array_slice($debatidas, 3, 5);

// ==============================
// NOTICIAS RECIENTES
// ==============================
$stmtRecientes = $con->prepare("
    SELECT id, slug, titulo, descripcion, crop1, crop2, crop3, fecha_publicacion, nombre
    FROM noticias, usuarios
    WHERE noticias.autor = usuarios.id_u and fecha_publicacion <= NOW()
    ORDER BY fecha_publicacion DESC
    LIMIT 4
");
$stmtRecientes->execute();
$recientes = $stmtRecientes->get_result();

// Helper: devuelve la primera imagen no vacía, o placeholder
function img($fields, $placeholder = 'img/placeholder.svg') {
    foreach ($fields as $f) {
        if (!empty($f)) {
            return imageUrl($f);
        }
    }
    return imageUrl($placeholder);
}
// Helper: genera atributos de imagen con lazy loading
function imgAttrs($fields, $extra = '', $placeholder = 'img/placeholder.svg') {
    $src = img($fields, $placeholder);
    return "src=\"$src\" loading=\"lazy\" decoding=\"async\" $extra";
}
?>
<!-- ===================== -->
<!-- SLIDER PRINCIPAL -->
<!-- ===================== -->
<div class="carousel-wrapper">
<div id="carouselExampleCaptions" class="carousel slide" data-bs-ride="carousel" data-bs-interval="7000">
    <div class="carousel-inner">
        <?php foreach($slider as $i => $row): ?>
            <div class="carousel-item <?= $i==0?'active':'' ?>" data-url="<?= newsUrlFromRow($row) ?>">
                <picture>
                    <!-- MÓVIL: crop 16:9; fallback a original (no al 21:6 que causa zoom extremo) -->
                    <source
                        media="(max-width:768px)"
                        srcset="<?= img([$row['crop3'], $row['crop1']]) ?>">
                    <!-- DESKTOP: banner (21:6) -->
                    <img
                        src="<?= img([$row['crop2'], $row['crop1']]) ?>"
                        class="carousel-img"
                        alt="<?= htmlspecialchars($row['titulo']) ?>">
                </picture>
                <div class="carousel-caption caption-md">
                    <?php foreach(array_filter(array_map('trim', explode(',', $row['categorias'] ?? ''))) as $cat): ?>
                        <a href="<?= categoryUrl($cat) ?>" class="carousel-tag"><?= htmlspecialchars($cat) ?></a>
                    <?php endforeach; ?>
                    <h5><a href="<?= newsUrlFromRow($row) ?>" class="carousel-link"><?= htmlspecialchars($row['titulo']) ?></a></h5>
                    <p><?= htmlspecialchars($row['descripcion']) ?></p>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<div class="carousel-indicators custom-indicators" id="carouselIndicatorsOuter">
    <?php foreach($slider as $i => $row): ?>
        <button type="button" data-bs-target="#carouselExampleCaptions" data-bs-slide-to="<?= $i ?>" class="<?= $i==0?'active':'' ?>">
            <div class="indicator-avatar">
                <img src="<?= img([$row['crop3'], $row['crop2'], $row['crop1']]) ?>" alt="<?= htmlspecialchars($row['titulo']) ?>">
                <svg viewBox="0 0 36 36"><circle cx="18" cy="18" r="16"></circle></svg>
            </div>
        </button>
    <?php endforeach; ?>
</div>
</div>
<!-- ===================== -->
<!-- TOP NOTICIAS -->
<!-- ===================== -->
<?php
// Macro local para renderizar una news-card con overlay
function topCard($r, $type = 'thumb') {
    $isBanner = $type === 'banner';
    $url  = newsUrlFromRow($r);
    $cats = array_filter(array_map('trim', explode(',', $r['categorias'] ?? '')));
    $tagsHtml = '';
    foreach ($cats as $c) {
        $tagsHtml .= '<a href="' . categoryUrl($c) . '" class="news-tag">' . htmlspecialchars($c) . '</a>';
    }
    $overlay = '<div class="news-overlay">
        <div class="news-tags">' . $tagsHtml . '</div>
        <div class="news-content">
            <a href="' . $url . '" class="news-link-card">
                <h3 class="title-limit-2">' . htmlspecialchars($r['titulo']) . '</h3>
            </a>
            <p class="desc-limit-1">' . htmlspecialchars($r['descripcion']) . '</p>
        </div>
    </div>';
    if ($isBanner) {
        $srcMobile  = img([$r['crop3'], $r['crop2']]);
        $srcDesktop = img([$r['crop2'], $r['crop1']]);
        echo '<div class="news-card card-banner" data-url="' . $url . '">
            <picture>
                <source media="(max-width: 767px)" srcset="' . $srcMobile . '">
                <img src="' . $srcDesktop . '" alt="" loading="lazy" decoding="async">
            </picture>' . $overlay . '</div>';
    } else {
        $src = img([$r['crop3'], $r['crop1']]);
        echo '<div class="news-card card-thumb" data-url="' . $url . '">
            <img src="' . $src . '" alt="" loading="lazy" decoding="async">' . $overlay . '</div>';
    }
}

// Macro local para renderizar una tarjeta vertical premium (1 grande, 2 pequeñas abajo)
function verticalCard($r, $type = 'small') {
    $url = newsUrlFromRow($r);
    $isLarge = ($type === 'large');
    $imgSize = $isLarge ? [$r['crop2'], $r['crop1'], $r['crop3']] : [$r['crop3'], $r['crop1'], $r['crop2']];
    $imgSrc = img($imgSize);
    
    $cats = array_filter(array_map('trim', explode(',', $r['categorias'] ?? '')));
    $tagsHtml = '';
    foreach ($cats as $cat) {
        $tagsHtml .= '<a href="' . categoryUrl($cat) . '" class="tag-news">' . htmlspecialchars($cat) . '</a> ';
    }
    
    $fecha_pub = strtotime($r['fecha'] ?? $r['fecha_publicacion']);
    $ahora = time();
    $diff = $ahora - $fecha_pub;
    if ($diff < 3600) {
        $tiempo = "Hace " . floor($diff / 60) . " min";
    } elseif ($diff < 86400) {
        $tiempo = "Hace " . floor($diff / 3600) . " hrs";
    } elseif ($diff < 172800) {
        $tiempo = "Ayer";
    } else {
        $tiempo = date("M d", $fecha_pub);
    }
    
    $author = htmlspecialchars($r['nombre_u'] ?? $r['nombre'] ?? 'Redacción');
    
    echo '
    <div class="card-vertical ' . $type . '" data-url="' . $url . '">
        <div class="card-vertical-img-wrapper">
            <img src="' . $imgSrc . '" alt="' . htmlspecialchars($r['titulo']) . '" loading="lazy" decoding="async">
        </div>
        <div class="card-vertical-body">
            <div class="card-vertical-tags">' . $tagsHtml . '</div>
            <h4 class="card-vertical-title">
                <a href="' . $url . '">' . htmlspecialchars($r['titulo']) . '</a>
            </h4>
            <p class="card-vertical-text title-limit-2">' . htmlspecialchars($r['descripcion']) . '</p>
            <div class="card-vertical-meta">
                <span><i class="bi bi-clock"></i> ' . $tiempo . '</span>
                <span>Por: ' . $author . '</span>
            </div>
        </div>
    </div>';
}
?>
<div class="container mt-5">
    <div>
        <div class="section-separator">
            <a href="<?= topUrl() ?>" class="section-separator-label">
                <i class="bi bi-award"></i> <?= $topTitulo ?>
            </a>
            <div class="section-separator-line"></div>
        </div>
        <?php
        // Fila 1 — banner grande + thumb pequeño
        $f1 = array_values(array_filter([
            $ultimasNoticias[0] ?? null,
            $ultimasNoticias[1] ?? null,
        ]));
        if ($f1):
        ?>
        <div class="row">
            <div class="<?= isset($f1[1]) ? 'col-md-8' : 'col-12' ?>">
                <?php topCard($f1[0], 'banner'); ?>
            </div>
            <?php if (isset($f1[1])): ?>
            <div class="col-md-4"><?php topCard($f1[1]); ?></div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php
        // Fila 2 — hasta 3 thumbs; la clase de columna se adapta a los disponibles
        $f2 = array_values(array_filter([
            $ultimasNoticias[2] ?? null,
            $ultimasNoticias[3] ?? null,
            $ultimasNoticias[4] ?? null,
        ]));
        if ($f2):
            $col2 = match(count($f2)) { 1 => 'col-12', 2 => 'col-12 col-md-6', default => 'col-12 col-md-4' };
        ?>
        <div class="row">
            <?php foreach ($f2 as $r): ?>
            <div class="<?= $col2 ?>"><?php topCard($r); ?></div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php
        // Fila 3 — thumb pequeño + banner grande; se adapta si solo hay 1
        $f3 = array_values(array_filter([
            $ultimasNoticias[5] ?? null,
            $ultimasNoticias[6] ?? null,
        ]));
        if ($f3):
        ?>
        <div class="row">
            <div class="<?= isset($f3[1]) ? 'col-md-4' : 'col-12' ?>">
                <?php topCard($f3[0]); ?>
            </div>
            <?php if (isset($f3[1])): ?>
            <div class="col-md-8"><?php topCard($f3[1], 'banner'); ?></div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        <!-- ===================== -->
        <!-- SIDEBAR -->
        <!-- ===================== -->
        <div class="section-separator">
            <a href="<?= recientesUrl() ?>" class="section-separator-label">
                <i class="bi bi-alarm"></i> Lo más recientes
            </a>
            <div class="section-separator-line"></div>
        </div>
        <div class="row mt-4">
            <div class="col-md-9">
                <div class="news-block-row-three">
                    <?php if (isset($noticiasMasRecientes[0])) verticalCard($noticiasMasRecientes[0], 'small'); ?>
                    <?php if (isset($noticiasMasRecientes[1])) verticalCard($noticiasMasRecientes[1], 'small'); ?>
                    <?php if (isset($noticiasMasRecientes[2])) verticalCard($noticiasMasRecientes[2], 'small'); ?>
                </div>

                <?php if(($secciones['publicidad']['estado'] ?? 0) == 1 && $publicidad) : ?>
                    <div class="ad-container" style="margin: 25px 0 35px 0;">
                        <a href="<?php echo htmlspecialchars($publicidad['url']); ?>" class="banner-button" data-pub="<?php echo htmlspecialchars($publicidad['id_pub']); ?>">
                            <img src="<?= imageUrl($publicidad['imagen']) ?>" alt="" class="banner" loading="lazy" decoding="async">
                        </a>
                        <span class="ads-label">ADS</span>
                    </div>
                <?php endif; ?>

                <div class="news-block-container" style="margin-top: 25px;">
                    <?php if (isset($noticiasMasRecientes[3])): ?>
                        <!-- Bloque 2: Grande arriba, 2 pequeños abajo -->
                        <?php verticalCard($noticiasMasRecientes[3], 'large'); ?>
                        
                        <div class="news-block-row-small">
                            <?php if (isset($noticiasMasRecientes[4])) verticalCard($noticiasMasRecientes[4], 'small'); ?>
                            <?php if (isset($noticiasMasRecientes[5])) verticalCard($noticiasMasRecientes[5], 'small'); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div><!-- /col-md-9 (top) -->

            <div class="col-md-3">
                <div class="sidebar-wrapper">
                    <div class="card sidebar-card">
                        <div class="card-h">
                            <?php if(($secciones['publicidad']['estado'] ?? 0) == 1 && $publicidadCuadro) : ?>
                                <div class="ad-container">
                                    <a href="<?php echo htmlspecialchars($publicidadCuadro['url']); ?>" class="banner-button" data-pub="<?php echo htmlspecialchars($publicidadCuadro['id_pub']); ?>">
                                        <img src="<?= imageUrl($publicidadCuadro['imagen']) ?>" class="banner-card-img-top" loading="lazy" decoding="async">
                                    </a>
                                    <span class="ads-label">ADS</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <h3>
                                <a href="<?= recientesUrl() ?>" style="text-decoration: none; color: inherit;">
                                    <i class="bi bi-alarm"></i> Lo más nuevo
                                </a>
                            </h3>
                            <br>
                            <div class="sidebar-news-list">
                                <?php foreach($ultimasNoticiasSidebar as $row): ?>
                                    <div class="cardSpecial row row-no-gap">
                                        <div class="col-md-4">
                                            <img src="<?= img([$row['crop3'], $row['crop2'], $row['crop1']]) ?>" alt="" class="imgCard card-img-left-rounded" loading="lazy" decoding="async">
                                        </div>
                                        <div class="col-md-8">
                                            <div class="card-body">
                                                <a href="<?= newsUrlFromRow($row) ?>" class="linkCard news-link title-limit-2"><?= htmlspecialchars($row['titulo']) ?></a>
                                            </div>
                                        </div>
                                    </div>
                                    <br>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div><!-- /col-md-3 (top sidebar) -->
        </div><!-- /row (top) -->

        <?php if($secciones['videos']['estado'] == 1) : ?>
            <div class="videos-destacados-container">
                <h2 class="videos-destacados-titulo" id="videos-destacados"><i class="bi bi-camera-video"></i> Videos Destacados</h2>
                <div class="video-carousel <?php echo count($vid) == 1? 'single-video':''; ?>">
                <?php foreach($vid as $video): ?>
                    <div class="video-slide">
                        <?php echo bloquearEmbeds(renderizarVideo($video['url_v'])); ?>
                    </div>
                <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="row mt-4">
            <div class="col-md-9">
                <div class="news-block-container">
                    <?php if (isset($noticiasMasRecientes2[0])): ?>
                        <!-- Bloque 3: Grande arriba, 2 pequeños abajo -->
                        <?php verticalCard($noticiasMasRecientes2[0], 'large'); ?>
                        
                        <div class="news-block-row-small">
                            <?php if (isset($noticiasMasRecientes2[1])) verticalCard($noticiasMasRecientes2[1], 'small'); ?>
                            <?php if (isset($noticiasMasRecientes2[2])) verticalCard($noticiasMasRecientes2[2], 'small'); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="news-block-container" style="margin-top: 25px;">
                    <div class="news-block-row-small">
                        <?php if (isset($noticiasMasRecientes2[3])) verticalCard($noticiasMasRecientes2[3], 'small'); ?>
                        <?php if (isset($noticiasMasRecientes2[4])) verticalCard($noticiasMasRecientes2[4], 'small'); ?>
                    </div>
                </div>
            </div><!-- /col-md-9 (bottom) -->

            <div class="col-md-3">
                <div class="sidebar-wrapper">
                    <div class="card sidebar-card">
                        <div class="card-body">
                            <h3>
                                <a href="<?= popularUrl() ?>" style="text-decoration: none; color: inherit;">
                                    Lo más popular
                                </a>
                            </h3>
                            <br>
                            <div class="sidebar-card-overlay-list">
                                <?php foreach($popularesNoticiasSidebar as $row): 
                                    $url = newsUrlFromRow($row);
                                    $img = img([$row['crop3'], $row['crop2'], $row['crop1']]);
                                ?>
                                    <div class="sidebar-card-overlay-item" data-url="<?= $url ?>">
                                        <img src="<?= $img ?>" alt="" loading="lazy" decoding="async">
                                        <div class="sidebar-card-text-overlay">
                                            <h4 class="title-limit-2"><?= htmlspecialchars($row['titulo']) ?></h4>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="card-footer">
                            <h3>Siguenos</h3>
                            <br>
                            <div class="social-links">
                                <a href="https://www.facebook.com/TheCatink?locale=es_LA" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
                                <a href="https://x.com/The_Catink/" aria-label="Twitter / X"><i class="bi bi-twitter-x"></i></a>
                                <a href="https://www.instagram.com/the.catink/" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
                                <a href="https://www.youtube.com/@thecatink" aria-label="YouTube"><i class="bi bi-youtube"></i></a>
                                <a href="https://www.tiktok.com/@thecatink" aria-label="TikTok"><i class="bi bi-tiktok"></i></a>
                                <!--<a href="#" aria-label="Twitch"><i class="bi bi-twitch"></i></a>-->
                            </div>
                        </div>
                    </div>
                </div>
            </div><!-- /col-md-3 (bottom sidebar) -->
        </div><!-- /row mt-4 outer -->

        <!-- Sección Lo más debatido -->
        <?php if (!empty($debatidas)): ?>
        <div class="debatido-section-wrapper">
            <div class="container debatido-section-container">
                <div class="debatido-section-header">
                    <h2 class="debatido-section-title">
                        <span class="title-bar"></span>
                        <i class="bi bi-chat-left-text" style="margin-right: 10px;"></i> LO MÁS DEBATIDO
                    </h2>
                </div>
                
                <div class="debatido-layout">
                    <!-- Columna Izquierda: Tarjetas (1 grande, 2 pequeñas abajo) -->
                    <div class="debatido-cards-column">
                        <?php if (isset($debatidasLeft[0])): 
                            $row = $debatidasLeft[0];
                            $url = newsUrlFromRow($row);
                            $img = img([$row['crop2'], $row['crop1'], $row['crop3']]);
                        ?>
                            <!-- Tarjeta Grande -->
                            <div class="debatido-card-large" data-url="<?= $url ?>">
                                <img src="<?= $img ?>" alt="" loading="lazy" decoding="async">
                                <div class="debatido-card-overlay">
                                    <span class="debatido-card-tag"><i class="bi bi-chat-fill"></i> <?= $row['total_comentarios'] ?> comentarios</span>
                                    <h3 class="title-limit-2"><?= htmlspecialchars($row['titulo']) ?></h3>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="debatido-cards-row">
                            <?php for ($i = 1; $i <= 2; $i++): 
                                if (isset($debatidasLeft[$i])): 
                                    $row = $debatidasLeft[$i];
                                    $url = newsUrlFromRow($row);
                                    $img = img([$row['crop3'], $row['crop1'], $row['crop2']]);
                            ?>
                                <!-- Tarjeta Pequeña -->
                                <div class="debatido-card-small" data-url="<?= $url ?>">
                                    <img src="<?= $img ?>" alt="" loading="lazy" decoding="async">
                                    <div class="debatido-card-overlay">
                                        <span class="debatido-card-tag"><i class="bi bi-chat-fill"></i> <?= $row['total_comentarios'] ?></span>
                                        <h4 class="title-limit-2"><?= htmlspecialchars($row['titulo']) ?></h4>
                                    </div>
                                </div>
                            <?php endif; 
                            endfor; ?>
                        </div>
                    </div>
                    
                    <!-- Columna Derecha: Lista de 5 notas con badges circulares -->
                    <div class="debatido-list-column">
                        <div class="debatido-list-items">
                            <?php foreach ($debatidasRight as $row): 
                                $url = newsUrlFromRow($row);
                            ?>
                                <div class="debatido-list-item" data-url="<?= $url ?>">
                                    <div class="debatido-badge">
                                        <?= $row['total_comentarios'] ?>
                                    </div>
                                    <div class="debatido-item-title title-limit-2">
                                        <?= htmlspecialchars($row['titulo']) ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <br>
        <div class="section-separator" id="recomendados">
            <a href="#recomendados" class="section-separator-label">
                <i class="bi bi-stars"></i> Recomendados para ti
            </a>
            <div class="section-separator-line"></div>
        </div>
        <div class="row mt-4">
                <?php while($r = $recomendadas->fetch_assoc()):
                    $img = img([$r['crop3'], $r['crop2'], $r['crop1']]);
                ?>
                  <div class="col">
                      <div class="card h-100" data-url="<?= newsUrlFromRow($r) ?>">
                          <img src="<?= $img ?>" class="card-img-top">
                          <div class="card-body">
                              <a href="<?= newsUrlFromRow($r) ?>" class="news-link title-limit-1">
                                  <?= htmlspecialchars($r['titulo']) ?>
                              </a>
                              <small class="desc-limit-3">
                                <?= htmlspecialchars($r['descripcion']) ?>
                              </small>
                              <br>
                              <small class="text-muted">
                                  <?= date('d M Y', strtotime($r['fecha_publicacion'])) ?> - Por: <?= $r['nombre'] ?>
                              </small>
                          </div>
                      </div>
                  </div>
                <?php endwhile; ?>
              </div>
        <br>
                 <?php if (($secciones['videos']['estado'] ?? 0) == 1) : ?>
        <!-- Reproductor interactivo de Youtube estilo LevelUp -->
        <div class="youtube-section-wrapper">
            
            <div class="container youtube-section-container">
                <div class="youtube-section-header">
                    <h2 class="youtube-section-title"><span class="title-bar"></span> VIDEOS RECOMENDADOS</h2>
                </div>
                
                <div class="youtube-layout">
                    <!-- Columna Izquierda: Reproductor Principal -->
                    <div class="youtube-player-column">
                        <div class="youtube-player-wrapper">
                            <div id="youtube-player"></div>
                            <button class="youtube-sound-toggle" id="youtube-sound-btn">
                                <i class="bi bi-volume-mute-fill"></i> Activar sonido
                            </button>
                        </div>
                    </div>
                    
                    <!-- Columna Derecha: Lista de Reproducción -->
                    <div class="youtube-playlist-column">
                        <div class="youtube-playlist-header">
                            <span class="playlist-site">catink.com.mx</span>
                            <span class="playlist-count" id="playlist-count-label">Cargando...</span>
                        </div>
                        <div class="youtube-playlist-items" id="youtube-playlist-list">
                            <!-- Skeleton Loaders -->
                            <?php for($i=1; $i<=5; $i++): ?>
                                <div class="youtube-playlist-item-skeleton">
                                    <div class="skeleton-thumb"></div>
                                    <div class="skeleton-details">
                                        <div class="skeleton-line title"></div>
                                        <div class="skeleton-line meta"></div>
                                    </div>
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        <br>
        <div class="section-separator">
            <a href="<?= recientesUrl() ?>" class="section-separator-label">
                <i class="bi bi-lightning-fill"></i> Noticias recientes
            </a>
            <div class="section-separator-line"></div>
        </div>
        <div class="row mt-4">
                <?php while($r = $recientes->fetch_assoc()):
                    $img = img([$r['crop3'], $r['crop2'], $r['crop1']]);
                ?>
                  <div class="col">
                      <div class="card h-100" data-url="<?= newsUrlFromRow($r) ?>">
                          <img src="<?= $img ?>" class="card-img-top" loading="lazy" decoding="async">
                          <div class="card-body">
                              <a href="<?= newsUrlFromRow($r) ?>" class="news-link title-limit-1">
                                  <?= htmlspecialchars($r['titulo']) ?>
                              </a>
                              <small class="desc-limit-3">
                                <?= htmlspecialchars($r['descripcion']) ?>
                              </small>
                              <br>
                              <small class="text-muted">
                                  <?= date('d M Y', strtotime($r['fecha_publicacion'])) ?> - Por: <?= $r['nombre'] ?>
                              </small>
                          </div>
                      </div>
                  </div>
                <?php endwhile; ?>
              </div>
            </div>
        </div>

<!-- Conteo de clicks -->
<script>
    document.querySelectorAll(".banner-button").forEach(banner => {
        banner.addEventListener("click", function(e) {
            e.preventDefault();//pausar redireccionamiento
            let url = this.href;
            let publicidadId = this.dataset.pub;
            let data = new FormData();
            data.append("publicidad_id", publicidadId);
            fetch("./controllers/publicidad_click.php", {
                method: "POST",
                body: data
            }).finally(()=>{
                window.location.href = url;//redireccionar despues de registrar click
            });
        });
    });
</script>
<!-- Conteo de tiempo y visualizaciones -->
<script>
    document.querySelectorAll(".banner-button").forEach(banner => {
        let publicidadId = banner.dataset.pub;
        let startTime = null;
        let totalTime = 0;
        const observer = new IntersectionObserver(entries => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    startTime = Date.now();
                } else if (startTime) {
                    totalTime += (Date.now() - startTime) / 1000;
                    startTime = null;
                }
            });
        }, { threshold: 0.5 });
        observer.observe(banner);
        setInterval(()=>{
            if (totalTime > 1) {
                let data = new FormData();
                data.append("publicidad_id", publicidadId);
                data.append("tiempo", Math.round(totalTime));
                navigator.sendBeacon("./controllers/publicidad_view.php", data);
                totalTime = 0;  
            }
        }, 5000);
    });
</script>
<!-- Full-bleed videos container offset calculation -->
<script>
    function fixVideoBleed() {
        const vc = document.querySelector('.videos-destacados-container');
        if (!vc) return;
        // Medir el padre (col-md-9) para saber qué tan lejos está del borde del viewport
        const parent = vc.parentElement;
        const parentRect = parent.getBoundingClientRect();
        // leftOffset: cuántos px hay que mover a la izquierda para llegar al borde del viewport
        const leftOffset = -parentRect.left;
        // rightOffset: cuántos px hay que extender a la derecha para llegar al borde derecho
        const rightOffset = -(window.innerWidth - parentRect.right);
        vc.style.setProperty('--video-bleed-left', leftOffset + 'px');
        vc.style.setProperty('--video-bleed-right', rightOffset + 'px');
    }
    // Ejecutar al cargar y en resize
    document.addEventListener('DOMContentLoaded', fixVideoBleed);
    window.addEventListener('resize', fixVideoBleed);
    // También al cargar inmediatamente por si el DOM ya está listo
    fixVideoBleed();
</script>

<!-- Full-bleed debatido section offset calculation -->
<script>
    function fixDebatidoBleed() {
        const wrapper = document.querySelector('.debatido-section-wrapper');
        if (!wrapper) return;
        const parent = wrapper.parentElement;
        const parentRect = parent.getBoundingClientRect();
        const leftOffset = -parentRect.left;
        const rightOffset = -(window.innerWidth - parentRect.right);
        wrapper.style.setProperty('--debatido-bleed-left', leftOffset + 'px');
        wrapper.style.setProperty('--debatido-bleed-right', rightOffset + 'px');
    }
    document.addEventListener('DOMContentLoaded', fixDebatidoBleed);
    window.addEventListener('resize', fixDebatidoBleed);
    fixDebatidoBleed();
</script>

<!-- YouTube Interactive Section Logic -->
<script>
    // 1. YouTube Interactive Section Bleed Calculation
    function fixYoutubeBleed() {
        const wrapper = document.querySelector('.youtube-section-wrapper');
        if (!wrapper) return;
        const parent = wrapper.parentElement;
        const parentRect = parent.getBoundingClientRect();
        const leftOffset = -parentRect.left;
        const rightOffset = -(window.innerWidth - parentRect.right);
        wrapper.style.setProperty('--youtube-bleed-left', leftOffset + 'px');
        wrapper.style.setProperty('--youtube-bleed-right', rightOffset + 'px');
    }
    
    document.addEventListener('DOMContentLoaded', fixYoutubeBleed);
    window.addEventListener('resize', fixYoutubeBleed);
    fixYoutubeBleed();

    // 2. YouTube Data API & IFrame player logic
    let ytPlayer;
    let currentVideoIndex = 0;
    let playlistVideos = [];

    document.addEventListener('DOMContentLoaded', () => {
        // Fetch videos list from backend proxy
        fetch('./controllers/youtube_proxy.php')
            .then(r => r.json())
            .then(data => {
                if (data.error) {
                    console.error("YouTube Proxy Error:", data.error);
                    const listEl = document.getElementById('youtube-playlist-list');
                    if (listEl) {
                        listEl.innerHTML = `<div class="p-3 text-muted text-center" style="color: rgba(255,255,255,0.5) !important;">Configura una API Key y Playlist en el Panel de Administración.</div>`;
                    }
                    return;
                }
                
                playlistVideos = data.videos || [];
                if (playlistVideos.length === 0) {
                    const listEl = document.getElementById('youtube-playlist-list');
                    if (listEl) {
                        listEl.innerHTML = `<div class="p-3 text-muted text-center" style="color: rgba(255,255,255,0.5) !important;">La lista de reproducción está vacía.</div>`;
                    }
                    return;
                }
                
                renderPlaylist(playlistVideos);
                loadYoutubeIframeAPI();
            })
            .catch(err => {
                console.error("Failed to load playlist:", err);
            });
    });

    function renderPlaylist(videos) {
        const listEl = document.getElementById('youtube-playlist-list');
        const countEl = document.getElementById('playlist-count-label');
        if (!listEl) return;
        
        countEl.textContent = `1/${videos.length}`;
        listEl.innerHTML = ''; // Clear skeleton
        
        videos.forEach((video, index) => {
            const item = document.createElement('div');
            item.className = `youtube-playlist-item ${index === 0 ? 'active' : ''}`;
            item.dataset.videoId = video.id;
            item.dataset.index = index;
            
            const titleHtml = escapeHtml(video.title);
            const durationHtml = video.duration ? `<span class="playlist-item-duration">${video.duration}</span>` : '';
            
            item.innerHTML = `
                <div class="playlist-item-index">${index + 1}</div>
                <div class="playlist-item-thumb">
                    <img src="${video.thumbnail}" alt="Thumbnail" loading="lazy">
                    <span class="playlist-item-play-icon"><i class="bi bi-play-fill"></i></span>
                    ${durationHtml}
                </div>
                <div class="playlist-item-details">
                    <div class="playlist-item-title">${titleHtml}</div>
                    <div class="playlist-item-meta">catink.com.mx</div>
                </div>
            `;
            
            item.addEventListener('click', () => {
                playVideoAtIndex(index);
            });
            
            listEl.appendChild(item);
        });
    }

    function escapeHtml(text) {
        if (!text) return '';
        return text
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    function loadYoutubeIframeAPI() {
        if (window.YT && window.YT.Player) {
            initPlayer();
        } else {
            const tag = document.createElement('script');
            tag.src = "https://www.youtube.com/iframe_api";
            document.head.appendChild(tag);
        }
    }

    window.onYouTubeIframeAPIReady = function() {
        initPlayer();
    };

    function initPlayer() {
        if (playlistVideos.length === 0) return;
        const firstVideoId = playlistVideos[0].id;
        
        ytPlayer = new YT.Player('youtube-player', {
            height: '100%',
            width: '100%',
            videoId: firstVideoId,
            playerVars: {
                'playsinline': 1,
                'autoplay': 1,
                'mute': 1,
                'rel': 0,
                'modestbranding': 1
            },
            events: {
                'onReady': onPlayerReady,
                'onStateChange': onPlayerStateChange
            }
        });
    }

    function onPlayerReady(event) {
        const soundBtn = document.getElementById('youtube-sound-btn');
        if (soundBtn) {
            soundBtn.addEventListener('click', () => {
                if (ytPlayer.isMuted()) {
                    ytPlayer.unMute();
                    soundBtn.innerHTML = '<i class="bi bi-volume-up-fill"></i> Silenciar';
                } else {
                    ytPlayer.mute();
                    soundBtn.innerHTML = '<i class="bi bi-volume-mute-fill"></i> Activar sonido';
                }
            });
        }
    }

    function onPlayerStateChange(event) {
        if (event.data === YT.PlayerState.ENDED) {
            let nextIndex = currentVideoIndex + 1;
            if (nextIndex >= playlistVideos.length) {
                nextIndex = 0;
            }
            playVideoAtIndex(nextIndex);
        }
    }

    function playVideoAtIndex(index) {
        if (!ytPlayer || index < 0 || index >= playlistVideos.length) return;
        
        currentVideoIndex = index;
        const video = playlistVideos[index];
        
        const items = document.querySelectorAll('.youtube-playlist-item');
        items.forEach((item, idx) => {
            item.classList.toggle('active', idx === index);
        });
        
        document.getElementById('playlist-count-label').textContent = `${index + 1}/${playlistVideos.length}`;
        
        ytPlayer.loadVideoById({
            videoId: video.id,
            startSeconds: 0
        });
        
        const activeItem = document.querySelector('.youtube-playlist-item.active');
        if (activeItem) {
            activeItem.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    }
</script>

<?php include(__DIR__ . "/layout/footer.php"); ?>