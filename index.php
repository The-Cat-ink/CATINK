<?php
include(__DIR__ . "/layout/header.php");
require_once(__DIR__ . "/data/conexion.php");
include(__DIR__ . "/views/helpers/videoEmbed.php");
require_once(__DIR__ . "/views/helpers/urlhelper.php");

// ==============================
// PAGINACIÓN PARA EL FEED GENERAL
// ==============================
$pagina = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($pagina < 1) $pagina = 1;

if ($pagina === 1) {
    $porPagina = 2;
    $offset = 0;
} else {
    $porPagina = 10;
    $offset = 2 + ($pagina - 2) * 10;
}

// Contar total para paginación
$totalResult = $con->query("SELECT COUNT(*) as total FROM noticias WHERE fecha_publicacion <= NOW()");
$totalNoticias = $totalResult->fetch_assoc()['total'];

if ($totalNoticias <= 2) {
    $totalpaginas = 1;
} else {
    $totalpaginas = 1 + ceil(($totalNoticias - 2) / 10);
}

// Obtener feed general de noticias (recientes paginadas)
$stmtFeed = $con->prepare("
    SELECT n.id, n.slug, n.titulo, n.descripcion, n.crop1, n.crop2, n.crop3, n.fecha_publicacion AS fecha,
           n.likes, n.vistas, u.nombre AS nombre_u, GROUP_CONCAT(c.nombre ORDER BY nc.orden ASC SEPARATOR ',') AS categorias
    FROM noticias n
    INNER JOIN usuarios u ON n.autor = u.id_u
    LEFT JOIN noticia_categoria nc ON n.id = nc.noticia_id
    LEFT JOIN categorias c ON nc.categoria_id = c.id_c
    WHERE n.fecha_publicacion <= NOW()
    GROUP BY n.id
    ORDER BY n.fecha_publicacion DESC
    LIMIT ? OFFSET ?;
");
$stmtFeed->bind_param("ii", $porPagina, $offset);
$stmtFeed->execute();
$feedNoticias = $stmtFeed->get_result()->fetch_all(MYSQLI_ASSOC);

// ==============================
// SECCIONES DESTACADAS
// ==============================
$stmtGlobal = $con->prepare("
    SELECT n.id, n.slug, n.titulo, n.descripcion, n.crop1, n.crop2, n.crop3, n.crop4, n.fecha_publicacion AS fecha,
           n.likes, n.vistas, n.tipo_publicacion, n.calificacion, u.nombre AS nombre_u,
           GROUP_CONCAT(c.nombre ORDER BY nc.orden ASC SEPARATOR ',') AS categorias,
           n.es_estreno, n.seccion_estreno
    FROM noticias n
    INNER JOIN usuarios u ON n.autor = u.id_u
    LEFT JOIN noticia_categoria nc ON n.id = nc.noticia_id
    LEFT JOIN categorias c ON nc.categoria_id = c.id_c
    WHERE n.fecha_publicacion <= NOW()
    GROUP BY n.id
    ORDER BY n.fecha_publicacion DESC
    LIMIT 50;
");
$stmtGlobal->execute();
$noticiasGlobales = $stmtGlobal->get_result()->fetch_all(MYSQLI_ASSOC);

// 1. Slider / Hero
$slider = array_slice($noticiasGlobales, 0, 5);

// 2. Top Semanal
$topSemanal = $noticiasGlobales;
usort($topSemanal, fn($a, $b) => $b['vistas'] - $a['vistas']);
$topSemanal = array_slice($topSemanal, 0, 5);

// Helper para clasificar reseñas
function esReview($n) {
    if (isset($n['tipo_publicacion']) && $n['tipo_publicacion'] === 'review') {
        return true;
    }
    if (!empty($n['categorias'])) {
        $cats = strtolower($n['categorias']);
        if (strpos($cats, 'review') !== false || strpos($cats, 'reseña') !== false) {
            return true;
        }
    }
    return false;
}

// 3. Nuestras Reviews — query directa para siempre mostrar las más recientes
$stmtReviews = $con->prepare("
    SELECT n.id, n.slug, n.titulo, n.descripcion, n.crop1, n.crop2, n.crop3,
           n.fecha_publicacion AS fecha, n.likes, n.vistas, n.tipo_publicacion,
           n.calificacion, u.nombre AS nombre_u,
           GROUP_CONCAT(c.nombre SEPARATOR ',') AS categorias
    FROM noticias n
    INNER JOIN usuarios u ON n.autor = u.id_u
    LEFT JOIN noticia_categoria nc ON n.id = nc.noticia_id
    LEFT JOIN categorias c ON nc.categoria_id = c.id_c
    WHERE n.fecha_publicacion <= NOW()
      AND n.tipo_publicacion = 'review'
    GROUP BY n.id
    ORDER BY n.fecha_publicacion DESC
    LIMIT 4;
");
$stmtReviews->execute();
$reviews = $stmtReviews->get_result()->fetch_all(MYSQLI_ASSOC);

// 4. Lo que más te Recomendamos (Curados manualmente por el administrador)
$stmtRec = $con->prepare("
    SELECT r.id AS recomendado_id, r.noticia_id,
           COALESCE(r.titulo, n.titulo) AS titulo,
           n.slug, n.descripcion,
           COALESCE(r.imagen, n.crop1) AS crop1,
           COALESCE(r.imagen, n.crop2) AS crop2,
           COALESCE(r.imagen, n.crop3) AS crop3,
           COALESCE(r.imagen, n.crop4) AS crop4,
           n.fecha_publicacion AS fecha,
           n.likes, n.vistas, u.nombre AS nombre_u, GROUP_CONCAT(c.nombre SEPARATOR ',') AS categorias,
           n.calificacion
    FROM recomendados r
    LEFT JOIN noticias n ON r.noticia_id = n.id
    LEFT JOIN usuarios u ON n.autor = u.id_u
    LEFT JOIN noticia_categoria nc ON n.id = nc.noticia_id
    LEFT JOIN categorias c ON nc.categoria_id = c.id_c
    WHERE (r.noticia_id IS NULL OR n.fecha_publicacion <= NOW())
    GROUP BY r.id
    ORDER BY r.orden ASC
    LIMIT 10;
");
$stmtRec->execute();
$recomendamos = $stmtRec->get_result()->fetch_all(MYSQLI_ASSOC);

// 5. Próximos Estrenos
$estrenosPelis = null;
$estrenosJuegos = null;
$estrenosAnime = null;
foreach ($noticiasGlobales as $n) {
    if (intval($n['es_estreno'] ?? 0) !== 1) {
        continue;
    }
    $sec = $n['seccion_estreno'] ?? '';
    if (!$estrenosPelis && $sec === 'peliculas') {
        $estrenosPelis = $n;
    } elseif (!$estrenosJuegos && $sec === 'videojuegos') {
        $estrenosJuegos = $n;
    } elseif (!$estrenosAnime && $sec === 'anime') {
        $estrenosAnime = $n;
    }
}

// 6. Lo que más esperamos (Curados manualmente por el administrador)
$stmtEsp = $con->prepare("
    SELECT e.id AS esperado_id, e.noticia_id,
           COALESCE(e.titulo, n.titulo) AS titulo,
           n.slug, n.descripcion,
           COALESCE(e.imagen, n.crop1) AS crop1,
           COALESCE(e.imagen, n.crop2) AS crop2,
           COALESCE(e.imagen, n.crop3) AS crop3,
           COALESCE(e.imagen, n.crop4) AS crop4,
           n.fecha_publicacion AS fecha,
           n.likes, n.vistas, n.calificacion, u.nombre AS nombre_u, GROUP_CONCAT(c.nombre SEPARATOR ',') AS categorias
    FROM esperamos e
    LEFT JOIN noticias n ON e.noticia_id = n.id
    LEFT JOIN usuarios u ON n.autor = u.id_u
    LEFT JOIN noticia_categoria nc ON n.id = nc.noticia_id
    LEFT JOIN categorias c ON nc.categoria_id = c.id_c
    WHERE (e.noticia_id IS NULL OR n.fecha_publicacion <= NOW())
    GROUP BY e.id
    ORDER BY e.orden ASC
    LIMIT 10;
");
$stmtEsp->execute();
$esperamos = $stmtEsp->get_result()->fetch_all(MYSQLI_ASSOC);

// 7. Lo más debatido
$stmtCom = $con->prepare("
    SELECT n.id, n.slug, n.titulo, n.descripcion, n.crop1, n.crop2, n.crop3, n.crop4, n.fecha_publicacion AS fecha,
           u.nombre AS nombre_u, GROUP_CONCAT(c2.nombre ORDER BY nc.orden ASC SEPARATOR ',') AS categorias, COUNT(c.id_comentario) as total_comentarios
    FROM noticias n
    INNER JOIN usuarios u ON n.autor = u.id_u
    LEFT JOIN comentarios c ON c.noticia_id = n.id AND c.estado = 'activo'
    LEFT JOIN noticia_categoria nc ON n.id = nc.noticia_id
    LEFT JOIN categorias c2 ON nc.categoria_id = c2.id_c
    WHERE n.fecha_publicacion <= NOW()
    GROUP BY n.id
    ORDER BY total_comentarios DESC, n.fecha_publicacion DESC
    LIMIT 8
");
$stmtCom->execute();
$debatidas = $stmtCom->get_result()->fetch_all(MYSQLI_ASSOC);
$debatidasLeft = array_slice($debatidas, 0, 3);
$debatidasRight = array_slice($debatidas, 3, 5);

// 8. Lo más Random (Priorizar noticias de la categoría 'Random')
$stmtRandom = $con->prepare("
    SELECT n.id, n.slug, n.titulo, n.descripcion, n.crop1, n.crop2, n.crop3, n.crop4, n.fecha_publicacion AS fecha,
           n.likes, n.vistas, n.tipo_publicacion, n.calificacion, u.nombre AS nombre_u,
           GROUP_CONCAT(c.nombre ORDER BY nc.orden ASC SEPARATOR ',') AS categorias,
           n.es_estreno, n.seccion_estreno
    FROM noticias n
    INNER JOIN usuarios u ON n.autor = u.id_u
    INNER JOIN noticia_categoria nc ON n.id = nc.noticia_id
    INNER JOIN categorias c ON nc.categoria_id = c.id_c
    WHERE n.fecha_publicacion <= NOW() AND n.id IN (
        SELECT sub_nc.noticia_id 
        FROM noticia_categoria sub_nc 
        INNER JOIN categorias sub_c ON sub_nc.categoria_id = sub_c.id_c
        WHERE sub_c.nombre = 'Random'
    )
    GROUP BY n.id
    ORDER BY RAND()
    LIMIT 4;
");
$stmtRandom->execute();
$randomNoticias = $stmtRandom->get_result()->fetch_all(MYSQLI_ASSOC);

// Respaldo: si hay menos de 4 noticias en 'Random', rellenar con recientes al azar
if (count($randomNoticias) < 4) {
    $necesarias = 4 - count($randomNoticias);
    $excluirIds = array_map(function($n) { return $n['id']; }, $randomNoticias);
    
    // Excluir slider principal para evitar repeticiones visuales inmediatas
    foreach (array_slice($noticiasGlobales, 0, 5) as $sn) {
        $excluirIds[] = $sn['id'];
    }
    
    $fallbacks = [];
    foreach ($noticiasGlobales as $gn) {
        if (!in_array($gn['id'], $excluirIds)) {
            $fallbacks[] = $gn;
        }
    }
    shuffle($fallbacks);
    $adicionales = array_slice($fallbacks, 0, $necesarias);
    $randomNoticias = array_merge($randomNoticias, $adicionales);
}


// 9. Videos para "No te lo pierdas" (Verticales / TikToks)
$stmtVid = $con->prepare("SELECT * FROM videos WHERE activo = 1 ORDER BY orden ASC, id_v DESC LIMIT 4");
$stmtVid->execute();
$vidVerticales = $stmtVid->get_result()->fetch_all(MYSQLI_ASSOC);

// Obtener publicidad
$stmtPub = $con->prepare("
    (SELECT *, 'banner' as tipo_pub FROM publicidad WHERE activo = 1 AND tipo = 1 AND fecha_fin >= NOW() ORDER BY RAND() LIMIT 1)
    UNION ALL
    (SELECT *, 'cuadro' as tipo_pub FROM publicidad WHERE activo = 1 AND tipo = 2 AND fecha_fin >= NOW() ORDER BY RAND() LIMIT 1)
");
$stmtPub->execute();
$publicidadResult = $stmtPub->get_result();
$publicidad = null;
$publicidadCuadro = null;
while ($row = $publicidadResult->fetch_assoc()) {
    if ($row['tipo_pub'] === 'banner') $publicidad = $row;
    elseif ($row['tipo_pub'] === 'cuadro') $publicidadCuadro = $row;
}

// Secciones activas
$seccionesResult = $con->query("SELECT nombre, estado FROM secciones");
$secciones = [];
if ($seccionesResult) {
    while ($row = $seccionesResult->fetch_assoc()) {
        $secciones[$row['nombre']] = $row;
    }
}

// Helper de imágenes
function img($fields, $placeholder = 'img/placeholder.svg') {
    foreach ($fields as $f) {
        if (!empty($f)) {
            return imageUrl($f);
        }
    }
    return imageUrl($placeholder);
}

// Helper: calcula tiempo relativo
function tiempoRelativo($fecha) {
    $fecha_pub = strtotime($fecha);
    $ahora = time();
    $diff = $ahora - $fecha_pub;
    if ($diff < 3600) {
        return "Hace " . max(1, floor($diff / 60)) . " min";
    } elseif ($diff < 86400) {
        return "Hace " . floor($diff / 3600) . " hrs";
    } elseif ($diff < 172800) {
        return "Ayer";
    } else {
        return date("d M Y", $fecha_pub);
    }
}
?>

<?php if ($pagina === 1): ?>
<!-- ===================== -->
<!-- SLIDER PRINCIPAL -->
<!-- ===================== -->
<div class="carousel-wrapper">
<div id="carouselExampleCaptions" class="carousel slide" data-bs-ride="carousel" data-bs-interval="7000">
    <div class="carousel-inner">
        <?php foreach($slider as $i => $row): ?>
            <div class="carousel-item <?= $i==0?'active':'' ?>" data-url="<?= newsUrlFromRow($row) ?>">
                <picture>
                    <!-- MÓVIL: crop 16:9; cubre hasta 1024px para móviles con zoom reducido -->
                    <source
                        media="(max-width:1024px)"
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

<!-- ============================================= -->
<!-- 3. TOP SEMANAL -->
<!-- ============================================= -->
<div class="container mt-5">
    <div class="section-separator-pill">
        <span class="pill-label"><i class="bi bi-fire"></i> Top Semanal</span>
        <div class="pill-separator-line"></div>
    </div>

    <!-- Rejilla Top Semanal -->
    <div class="top-semanal-grid mt-4">
        <!-- Fila 1 -->
        <div class="top-row-one">
            <!-- 2/3 Tarjeta Ancha -->
            <?php if (isset($topSemanal[0])): 
                $r = $topSemanal[0];
                $url = newsUrlFromRow($r);
                $cats = array_filter(array_map('trim', explode(',', $r['categorias'] ?? '')));
            ?>
                <div class="top-card card-width-2-3" data-url="<?= $url ?>">
                    <div class="top-card-img-wrapper">
                        <img src="<?= img([$r['crop4'], $r['crop2'], $r['crop1']]) ?>" alt="">
                    </div>
                    <div class="top-card-overlay">
                        <div class="top-card-tags">
                            <?php foreach ($cats as $c): ?>
                                <span class="news-tag-pill"><?= htmlspecialchars($c) ?></span>
                            <?php endforeach; ?>
                        </div>
                        <h3 class="top-card-title"><a href="<?= $url ?>"><?= htmlspecialchars($r['titulo']) ?></a></h3>
                        <p class="top-card-desc"><?= htmlspecialchars($r['descripcion']) ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- 1/3 Tarjeta Estrecha -->
            <?php if (isset($topSemanal[1])): 
                $r = $topSemanal[1];
                $url = newsUrlFromRow($r);
                $cats = array_filter(array_map('trim', explode(',', $r['categorias'] ?? '')));
            ?>
                <div class="top-card card-width-1-3" data-url="<?= $url ?>">
                    <div class="top-card-img-wrapper">
                        <img src="<?= img([$r['crop3'], $r['crop1']]) ?>" alt="">
                    </div>
                    <div class="top-card-overlay">
                        <div class="top-card-tags">
                            <?php foreach ($cats as $c): ?>
                                <span class="news-tag-pill"><?= htmlspecialchars($c) ?></span>
                            <?php endforeach; ?>
                        </div>
                        <h3 class="top-card-title"><a href="<?= $url ?>"><?= htmlspecialchars($r['titulo']) ?></a></h3>
                        <p class="top-card-desc"><?= htmlspecialchars($r['descripcion']) ?></p>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Fila 2 (3 columnas de 1/3) -->
        <div class="top-row-three mt-3">
            <?php for ($i = 2; $i <= 4; $i++): 
                if (isset($topSemanal[$i])):
                    $r = $topSemanal[$i];
                    $url = newsUrlFromRow($r);
                    $cats = array_filter(array_map('trim', explode(',', $r['categorias'] ?? '')));
            ?>
                <div class="top-card card-width-1-3" data-url="<?= $url ?>">
                    <div class="top-card-img-wrapper">
                        <img src="<?= img([$r['crop3'], $r['crop1']]) ?>" alt="">
                    </div>
                    <div class="top-card-overlay">
                        <div class="top-card-tags">
                            <?php foreach ($cats as $c): ?>
                                <span class="news-tag-pill"><?= htmlspecialchars($c) ?></span>
                            <?php endforeach; ?>
                        </div>
                        <h3 class="top-card-title"><a href="<?= $url ?>"><?= htmlspecialchars($r['titulo']) ?></a></h3>
                        <p class="top-card-desc"><?= htmlspecialchars($r['descripcion']) ?></p>
                    </div>
                </div>
            <?php endif; 
            endfor; ?>
        </div>
    </div>
</div>

<!-- ============================================= -->
<!-- 4. NUESTRAS REVIEWS -->
<!-- ============================================= -->
<div class="container mt-5">
    <div class="section-separator-pill">
        <span class="pill-label"><i class="bi bi-star-fill"></i> Nuestras Reviews</span>
        <div class="pill-separator-line"></div>
    </div>

    <div class="row mt-4">
        <!-- Columna Izquierda: Listado Reseñas (2/3) -->
        <div class="col-md-8 feed-reviews-column">
            <div class="horizontal-cards-list">
                <?php foreach ($reviews as $r): 
                    $url = newsUrlFromRow($r);
                    $cats = array_filter(array_map('trim', explode(',', $r['categorias'] ?? '')));
                ?>
                    <div class="horizontal-news-card" data-url="<?= $url ?>">
                        <div class="h-card-img-wrapper">
                            <img src="<?= img([$r['crop3'], $r['crop1']]) ?>" alt="">
                            <!-- Badge de calificación si existe -->
                            <?php if (isset($r['calificacion']) && $r['calificacion'] !== null): ?>
                                <div class="review-rating-badge"><?= number_format($r['calificacion'], 1) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="h-card-body">
                            <div class="h-card-tags">
                                <?php foreach ($cats as $c): ?>
                                    <span class="category-pill-solid"><?= htmlspecialchars($c) ?></span>
                                <?php endforeach; ?>
                            </div>
                            <h3 class="h-card-title"><a href="<?= $url ?>"><?= htmlspecialchars($r['titulo']) ?></a></h3>
                            <p class="h-card-desc"><?= htmlspecialchars($r['descripcion']) ?></p>
                            <div class="h-card-meta">
                                <span>Publicado: <?= tiempoRelativo($r['fecha']) ?></span>
                                <span>Por: <?= htmlspecialchars($r['nombre_u'] ?? 'Redacción') ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Columna Derecha: Sidebar Recomendaciones (1/3) -->
        <div class="col-md-4">
            <div class="sidebar-reviews-section">
                <h3 class="sidebar-title-pink">Lo que más te Recomendamos</h3>
                <div class="sidebar-ranking-list-container">
                    <div class="sidebar-ranking-list">
                        <?php foreach ($recomendamos as $index => $r): 
                            $url = empty($r['noticia_id']) ? '#' : newsUrlFromRow($r);
                            $score = (!empty($r['calificacion']) && floatval($r['calificacion']) > 0) ? floatval($r['calificacion']) : null;
                        ?>
                            <?php if ($index === 0): ?>
                                <!-- Puesto 1: Card Grande con Borde Fuchsia -->
                                <div class="ranking-item-hero" data-url="<?= $url ?>">
                                    <img src="<?= img([$r['crop4'], $r['crop2'], $r['crop1']]) ?>" alt="" class="ranking-hero-img">
                                    <div class="ranking-number-overlay">1</div>
                                    <div class="ranking-hero-overlay">
                                        <h4 class="ranking-hero-title"><a href="<?= $url ?>"><?= htmlspecialchars($r['titulo']) ?></a></h4>
                                    </div>
                                    <?php if ($score !== null): ?>
                                        <div class="ranking-score-circle"><?= number_format($score, 1) ?></div>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <!-- Puestos 2-5: Card con Imagen Completa y Número Gigante -->
                                <div class="ranking-item-sub" data-url="<?= $url ?>">
                                    <div class="ranking-sub-card">
                                        <img src="<?= img([$r['crop4'], $r['crop2'], $r['crop1']]) ?>" alt="" class="ranking-sub-img">
                                        <div class="ranking-number-overlay"><?= $index + 1 ?></div>
                                        <div class="ranking-sub-overlay">
                                            <h4 class="ranking-sub-title"><a href="<?= $url ?>"><?= htmlspecialchars($r['titulo']) ?></a></h4>
                                        </div>
                                        <?php if ($score !== null): ?>
                                            <div class="ranking-score-circle"><?= number_format($score, 1) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================= -->
<!-- 5. PRÓXIMOS ESTRENOS -->
<!-- ============================================= -->
<div class="container mt-5">
    <div class="section-separator-pill">
        <span class="pill-label"><i class="bi bi-play-circle-fill"></i> Próximos Estrenos</span>
        <div class="pill-separator-line"></div>
    </div>

    <div class="row mt-4">
        <!-- Columna Izquierda: Películas, Juegos, Anime (2/3) -->
        <div class="col-md-8">
            <div class="estrenos-left-layout">
                <!-- Nota Grande (Películas y Series) -->
                <?php if ($estrenosPelis): 
                    $r = $estrenosPelis;
                    $url = newsUrlFromRow($r);
                    $cats = array_filter(array_map('trim', explode(',', $r['categorias'] ?? '')));
                ?>
                    <div class="estreno-pelis-block">
                        <h4 class="estreno-column-header">Películas y Series</h4>
                        <div class="top-card card-width-2-3" data-url="<?= $url ?>">
                            <div class="top-card-img-wrapper">
                                <img src="<?= img([$r['crop4'], $r['crop2'], $r['crop1']]) ?>" alt="">
                            </div>
                            <div class="top-card-overlay">
                                <div class="top-card-tags">
                                    <?php foreach ($cats as $c): ?>
                                        <span class="news-tag-pill"><?= htmlspecialchars($c) ?></span>
                                    <?php endforeach; ?>
                                </div>
                                <h3 class="top-card-title"><a href="<?= $url ?>"><?= htmlspecialchars($r['titulo']) ?></a></h3>
                                <p class="top-card-desc"><?= htmlspecialchars($r['descripcion']) ?></p>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="estreno-large-card" style="display: flex; flex-direction: column; min-height: 250px;">
                        <h4 class="estreno-column-header">Películas y Series</h4>
                        <div style="flex: 1; display: flex; align-items: center; justify-content: center; border: 1px dashed var(--border); border-radius: 8px; color: var(--muted); font-size: 13px; font-style: italic; margin-top: 10px;">
                            Sin estrenos programados en esta sección
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Dos Columnas Abajo (Videojuegos y Anime) -->
                <div class="estrenos-bottom-columns mt-4">
                    <!-- Videojuegos -->
                    <div class="estreno-sub-column">
                        <h4 class="estreno-column-header">Videojuegos</h4>
                        <?php if ($estrenosJuegos): 
                            $r = $estrenosJuegos;
                            $url = newsUrlFromRow($r);
                            $cats = array_filter(array_map('trim', explode(',', $r['categorias'] ?? '')));
                        ?>
                            <div class="estreno-small-card" data-url="<?= $url ?>">
                                <div class="estreno-small-img-wrapper">
                                    <img src="<?= img([$r['crop3'], $r['crop1']]) ?>" alt="">
                                </div>
                                <div class="estreno-small-body">
                                    <div class="estreno-tags">
                                        <?php foreach ($cats as $c): ?>
                                            <span class="category-pill-solid-micro"><?= htmlspecialchars($c) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                    <h3 class="estreno-small-title"><a href="<?= $url ?>"><?= htmlspecialchars($r['titulo']) ?></a></h3>
                                    <p class="estreno-small-desc title-limit-2"><?= htmlspecialchars($r['descripcion']) ?></p>
                                    <div class="estreno-small-meta">Publicado por: <?= htmlspecialchars($r['nombre_u'] ?? 'Redacción') ?></div>
                                </div>
                            </div>
                        <?php else: ?>
                            <div style="flex: 1; min-height: 120px; display: flex; align-items: center; justify-content: center; border: 1px dashed var(--border); border-radius: 8px; color: var(--muted); font-size: 13px; font-style: italic; text-align: center; padding: 15px;">
                                Sin estrenos programados
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Anime -->
                    <div class="estreno-sub-column">
                        <h4 class="estreno-column-header">Anime</h4>
                        <?php if ($estrenosAnime): 
                            $r = $estrenosAnime;
                            $url = newsUrlFromRow($r);
                            $cats = array_filter(array_map('trim', explode(',', $r['categorias'] ?? '')));
                        ?>
                            <div class="estreno-small-card" data-url="<?= $url ?>">
                                <div class="estreno-small-img-wrapper">
                                    <img src="<?= img([$r['crop3'], $r['crop1']]) ?>" alt="">
                                </div>
                                <div class="estreno-small-body">
                                    <div class="estreno-tags">
                                        <?php foreach ($cats as $c): ?>
                                            <span class="category-pill-solid-micro"><?= htmlspecialchars($c) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                    <h3 class="estreno-small-title"><a href="<?= $url ?>"><?= htmlspecialchars($r['titulo']) ?></a></h3>
                                    <p class="estreno-small-desc title-limit-2"><?= htmlspecialchars($r['descripcion']) ?></p>
                                    <div class="estreno-small-meta">Publicado por: <?= htmlspecialchars($r['nombre_u'] ?? 'Redacción') ?></div>
                                </div>
                            </div>
                        <?php else: ?>
                            <div style="flex: 1; min-height: 120px; display: flex; align-items: center; justify-content: center; border: 1px dashed var(--border); border-radius: 8px; color: var(--muted); font-size: 13px; font-style: italic; text-align: center; padding: 15px;">
                                Sin estrenos programados
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Columna Derecha: Sidebar Lo que más esperamos (1/3) -->
        <div class="col-md-4">
            <div class="sidebar-reviews-section">
                <h3 class="sidebar-title-pink">Lo que más esperamos</h3>
                <div class="sidebar-ranking-list-container">
                    <div class="sidebar-ranking-list">
                        <?php foreach ($esperamos as $index => $r):
                            $url = empty($r['noticia_id']) ? '#' : newsUrlFromRow($r);
                            $score = (!empty($r['calificacion']) && floatval($r['calificacion']) > 0) ? floatval($r['calificacion']) : null;
                        ?>
                            <?php if ($index === 0): ?>
                                <!-- Puesto 1: Card Grande con Borde Fuchsia -->
                                <div class="ranking-item-hero" data-url="<?= $url ?>">
                                    <img src="<?= img([$r['crop4'], $r['crop2'], $r['crop1']]) ?>" alt="" class="ranking-hero-img">
                                    <div class="ranking-number-overlay">1</div>
                                    <div class="ranking-hero-overlay">
                                        <h4 class="ranking-hero-title"><a href="<?= $url ?>"><?= htmlspecialchars($r['titulo']) ?></a></h4>
                                    </div>
                                    <?php if ($score !== null): ?>
                                        <div class="ranking-score-circle"><?= number_format($score, 1) ?></div>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <!-- Puestos 2-5: Card con Imagen Completa y Número Gigante -->
                                <div class="ranking-item-sub" data-url="<?= $url ?>">
                                    <div class="ranking-sub-card">
                                        <img src="<?= img([$r['crop4'], $r['crop2'], $r['crop1']]) ?>" alt="" class="ranking-sub-img">
                                        <div class="ranking-number-overlay"><?= $index + 1 ?></div>
                                        <div class="ranking-sub-overlay">
                                            <h4 class="ranking-sub-title"><a href="<?= $url ?>"><?= htmlspecialchars($r['titulo']) ?></a></h4>
                                        </div>
                                        <?php if ($score !== null): ?>
                                            <div class="ranking-score-circle"><?= number_format($score, 1) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================= -->
<!-- 6. NO TE LO PIERDAS (VIDEOS VERTICALES) -->
<!-- ============================================= -->
<div class="full-width-dark-section bleed-section mt-4" id="no-te-lo-pierdas-section">
    <div class="container">
        <div class="section-separator-pill dark-theme-pill">
            <span class="pill-label"><i class="bi bi-tiktok"></i> No te lo pierdas</span>
            <div class="pill-separator-line"></div>
        </div>

        <div class="vertical-videos-grid mt-4">
            <?php foreach ($vidVerticales as $v): ?>
                <?= bloquearEmbeds(renderizarVideo($v['url_v'])) ?>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- ============================================= -->
<!-- 7. LO MÁS DEBATIDO -->
<!-- ============================================= -->
<?php if (!empty($debatidas)): ?>
<div class="full-width-dark-section bleed-section" id="lo-mas-debatido-section">
    <div class="container">
        <div class="section-separator-pill dark-theme-pill">
            <span class="pill-label"><i class="bi bi-chat-left-text-fill"></i> Lo más debatido</span>
            <div class="pill-separator-line"></div>
        </div>
        
        <div class="debatido-layout mt-4">
            <!-- Columna Izquierda: Tarjetas invertidas (2 pequeñas arriba, 1 grande abajo) -->
            <div class="debatido-cards-column">
                <div class="debatido-cards-row">
                    <?php for ($i = 1; $i <= 2; $i++): 
                        if (isset($debatidasLeft[$i])): 
                            $row = $debatidasLeft[$i];
                            $url = newsUrlFromRow($row);
                            $img = img([$row['crop3'], $row['crop1']]);
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

                <?php if (isset($debatidasLeft[0])): 
                    $row = $debatidasLeft[0];
                    $url = newsUrlFromRow($row);
                    $img = img([$row['crop4'], $row['crop2'], $row['crop1']]);
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

<!-- ============================================= -->
<!-- 8. LO MÁS RANDOM -->
<!-- ============================================= -->
<div class="container mt-5">
    <div class="section-separator-pill">
        <span class="pill-label"><i class="bi bi-shuffle"></i> Lo más Random</span>
        <div class="pill-separator-line"></div>
    </div>

    <div class="random-cards-row mt-4">
        <?php foreach ($randomNoticias as $r): 
            $url = newsUrlFromRow($r);
        ?>
            <div class="random-vertical-card" data-url="<?= $url ?>">
                <div class="random-card-img-wrapper">
                    <img src="<?= img([$r['crop3'], $r['crop1']]) ?>" alt="" loading="lazy">
                </div>
                <div class="random-card-body">
                    <h4 class="random-card-title"><a href="<?= $url ?>"><?= htmlspecialchars($r['titulo']) ?></a></h4>
                    <p class="random-card-text title-limit-3"><?= htmlspecialchars($r['descripcion']) ?></p>
                    <div class="random-card-meta"><?= tiempoRelativo($r['fecha']) ?> &middot; Por: <?= htmlspecialchars($r['nombre_u'] ?? 'Redacción') ?></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- ============================================= -->
<!-- 9. REPRODUCTOR INTERACTIVO YOUTUBE  -->
<!-- ============================================= -->
<?php if (($secciones['videos']['estado'] ?? 0) == 1) : ?>
<div class="youtube-section-wrapper mt-4 bleed-section full-width-dark-section">
    <div class="container youtube-section-container">
        <div class="section-separator-pill dark-theme-pill">
            <span class="pill-label"><i class="bi bi-youtube"></i> Videos recomendados</span>
            <div class="pill-separator-line"></div>
        </div>
        
        <div class="youtube-layout mt-4">
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

<?php endif; // Fin if ($pagina === 1) ?>

<!-- ============================================= -->
<!-- 10. FEED GENERAL DE NOTICIAS + PAGINACIÓN -->
<!-- ============================================= -->
<div class="container mt-5">
    <div class="section-separator-pill">
        <span class="pill-label"><i class="bi bi-lightning-fill"></i> Noticias recientes</span>
        <div class="pill-separator-line"></div>
    </div>

    <div class="row mt-4">
        <!-- Feed Noticias horizontales (Ancho completo, sin sidebar) -->
        <div class="col-12">
            <div class="horizontal-cards-list feed-news-list">
                <?php foreach ($feedNoticias as $r): 
                    $url = newsUrlFromRow($r);
                    $cats = array_filter(array_map('trim', explode(',', $r['categorias'] ?? '')));
                ?>
                    <div class="horizontal-news-card" data-url="<?= $url ?>">
                        <div class="h-card-img-wrapper">
                            <img src="<?= img([$r['crop3'], $r['crop1']]) ?>" alt="" loading="lazy">
                        </div>
                        <div class="h-card-body">
                            <div class="h-card-tags">
                                <?php foreach ($cats as $c): ?>
                                    <span class="category-pill-solid"><?= htmlspecialchars($c) ?></span>
                                <?php endforeach; ?>
                            </div>
                            <h3 class="h-card-title"><a href="<?= $url ?>"><?= htmlspecialchars($r['titulo']) ?></a></h3>
                            <p class="h-card-desc"><?= htmlspecialchars($r['descripcion']) ?></p>
                            <div class="h-card-meta">
                                <span>Publicado: <?= tiempoRelativo($r['fecha']) ?></span>
                                <span>Por: <?= htmlspecialchars($r['nombre_u'] ?? 'Redacción') ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Paginación Circular Rosa -->
            <?php
            // Calcular páginas para el enlace base
            $baseUrl = "?";
            ?>
            <div class="pagination-wrapper-pink mt-5">
                <ul class="pagination-pink">
                    <?php if ($pagina > 1): ?>
                        <li class="page-arrow">
                            <a href="<?= $baseUrl ?>page=<?= $pagina-1 ?>">
                                <i class="bi bi-chevron-left"></i>
                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <?php 
                    // Limitar números de página mostrados si son muchos
                    $rango = 3;
                    $start = max(1, $pagina - $rango);
                    $end = min($totalpaginas, $pagina + $rango);
                    
                    if ($start > 1) {
                        echo '<li><a href="'.$baseUrl.'page=1">1</a></li>';
                        if ($start > 2) echo '<li class="page-dots">...</li>';
                    }
                    
                    for ($i = $start; $i <= $end; $i++): ?>
                        <li class="<?= $i == $pagina ? 'active' : '' ?>">
                            <a href="<?= $baseUrl ?>page=<?= $i ?>">
                                <?= $i ?>
                            </a>
                        </li>
                    <?php endfor;
                    
                    if ($end < $totalpaginas) {
                        if ($end < $totalpaginas - 1) echo '<li class="page-dots">...</li>';
                        echo '<li><a href="'.$baseUrl.'page='.$totalpaginas.'">'.$totalpaginas.'</a></li>';
                    }
                    ?>
                    
                    <?php if ($pagina < $totalpaginas): ?>
                        <li class="page-arrow">
                            <a href="<?= $baseUrl ?>page=<?= $pagina+1 ?>">
                                <i class="bi bi-chevron-right"></i>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- ============================================= -->
<!-- MODALES Y SCRIPTS -->
<!-- ============================================= -->

<!-- Modal de Video Vertical (TikTok) -->
<div class="vertical-video-modal-overlay" id="vVideoModal">
    <div class="vertical-video-modal-container">
        <button class="v-video-modal-close" onclick="closeVideoModal()">✕</button>
        <div class="v-video-iframe-wrapper">
            <iframe id="vVideoPlayer" src="" frameborder="0" allowfullscreen allow="autoplay; encrypted-media"></iframe>
        </div>
    </div>
</div>

<!-- Conteo de clicks en publicidad -->
<script>
    document.querySelectorAll(".banner-button").forEach(banner => {
        banner.addEventListener("click", function(e) {
            e.preventDefault();
            let url = this.href;
            let publicidadId = this.dataset.pub;
            let data = new FormData();
            data.append("publicidad_id", publicidadId);
            fetch("./controllers/publicidad_click.php", {
                method: "POST",
                body: data
            }).finally(()=>{
                window.location.href = url;
            });
        });
    });
</script>

<!-- Conteo de tiempo y visualizaciones en publicidad -->
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

<!-- Script para modal de videos verticales -->
<script>
    const vVideoModal = document.getElementById('vVideoModal');
    const vVideoPlayer = document.getElementById('vVideoPlayer');

    document.querySelectorAll('.vertical-video-card').forEach(card => {
        card.addEventListener('click', function() {
            const videoSrc = this.dataset.videoSrc;
            if (videoSrc) {
                // Agregar autoplay si es posible
                let playSrc = videoSrc;
                if (playSrc.indexOf('youtube.com') !== -1) {
                    playSrc += (playSrc.indexOf('?') !== -1 ? '&' : '?') + 'autoplay=1';
                }
                vVideoPlayer.src = playSrc;
                vVideoModal.classList.add('active');
            }
        });
    });

    function closeVideoModal() {
        vVideoModal.classList.remove('active');
        vVideoPlayer.src = '';
    }

    vVideoModal?.addEventListener('click', function(e) {
        if (e.target === vVideoModal) {
            closeVideoModal();
        }
    });
</script>

<!-- Full-bleed/Bleed adjustments calculation -->
<script>
    function fixBleedSections() {
        const bleedSections = document.querySelectorAll('.bleed-section');
        bleedSections.forEach(section => {
            const parent = section.parentElement;
            if (!parent) return;
            const parentRect = parent.getBoundingClientRect();
            const leftOffset = -parentRect.left;
            const rightOffset = -(window.innerWidth - parentRect.right);
            section.style.setProperty('--bleed-left', leftOffset + 'px');
            section.style.setProperty('--bleed-right', rightOffset + 'px');
        });
    }
    document.addEventListener('DOMContentLoaded', fixBleedSections);
    window.addEventListener('resize', fixBleedSections);
    fixBleedSections();
</script>

<!-- YouTube Interactive Section Logic -->
<script>
    // 2. YouTube Data API & IFrame player logic
    let ytPlayer;
    let currentVideoIndex = 0;
    let playlistVideos = [];

    document.addEventListener('DOMContentLoaded', () => {
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
        listEl.innerHTML = '';
        
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

<script>
    // Tarjetas clickeables
    document.querySelectorAll('[data-url]').forEach(card => {
        card.addEventListener('click', function(e) {
            // Evitar redirigir si se hace clic en enlaces dentro del card
            if (e.target.closest('a') || e.target.closest('button')) return;
            window.location.href = this.dataset.url;
        });
    });
</script>

<?php include(__DIR__ . "/layout/footer.php"); ?>
