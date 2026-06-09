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
    ORDER BY n.fecha_publicacion DESC;
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
$popularesNoticiasSidebar = array_slice($popularesNoticiasSidebar, 0, 3);
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
//Obtener banner publicidad
$stmt = $con->prepare("SELECT * FROM publicidad WHERE activo = 1 AND tipo = 1 and fecha_fin >= NOW() ORDER BY RAND() LIMIT 1");
$stmt->execute();
$publicidad = $stmt->get_result()->fetch_assoc();
//Obtener cuadro publicitario
$stmt = $con->prepare("SELECT * FROM publicidad WHERE activo = 1 AND tipo = 2 and fecha_fin >= NOW() ORDER BY RAND() LIMIT 1");
$stmt->execute();
$publicidadCuadro = $stmt->get_result()->fetch_assoc();
//obtener publicidad inferior
$stmt = $con->prepare("SELECT * FROM publicidad WHERE activo = 1 AND tipo = 1 ORDER BY RAND() LIMIT 1");
$stmt->execute();
$publicidadInferior = $stmt->get_result()->fetch_assoc();
// obtener videos
$stmt = $con->prepare("SELECT * FROM videos WHERE activo = 1 ORDER BY id_v DESC LIMIT 6");
$stmt->execute();
$vid = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
// ==============================
// NOTICIAS RECOMENDADAS
// ==============================
$recomendadas = [];
$sqlRec = "
    SELECT id, titulo, descripcion, crop1, crop2, crop3, fecha_publicacion, nombre
    FROM noticias, usuarios
    WHERE noticias.autor = usuarios.id_u and fecha_publicacion <= NOW()
    ORDER BY likes DESC, vistas DESC, fecha_publicacion DESC
    LIMIT 3
";
$stmtRec = $con->prepare($sqlRec);
$stmtRec->execute();
$recomendadas = $stmtRec->get_result();
// ==============================
// NOTICIAS RECIENTES
// ==============================
$stmtRecientes = $con->prepare("
    SELECT id, titulo, descripcion, crop1, crop2, crop3, fecha_publicacion, nombre
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
<div id="carouselExampleCaptions" class="carousel slide" data-bs-ride="carousel" data-bs-interval="10000">
    <div class="carousel-indicators custom-indicators">
        <?php foreach($slider as $i => $row): ?>
            <button type="button" data-bs-target="#carouselExampleCaptions" data-bs-slide-to="<?= $i ?>" class="<?= $i==0?'active':'' ?>">
                <div class="indicator-avatar">
                    <img src="<?= img([$row['crop3'], $row['crop2'], $row['crop1']]) ?>" alt="<?= htmlspecialchars($row['titulo']) ?>">
                    <svg viewBox="0 0 36 36"><circle cx="18" cy="18" r="16"></circle></svg>
                </div>
            </button>
        <?php endforeach; ?>
    </div>
    <div class="carousel-inner">
        <?php foreach($slider as $i => $row): ?>
            <div class="carousel-item <?= $i==0?'active':'' ?>" data-url="<?= newsUrl($row['id']) ?>">
                <picture>
                    <!-- MÓVIL: miniatura (16:9) con animación de paneo -->
                    <source
                        media="(max-width:768px)"
                        srcset="<?= img([$row['crop3'], $row['crop2']]) ?>">
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
                    <h5><a href="<?= newsUrl($row['id']) ?>" class="carousel-link"><?= htmlspecialchars($row['titulo']) ?></a></h5>
                    <p><?= htmlspecialchars($row['descripcion']) ?></p>
                </div>
            </div>
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
    $url  = newsUrl($r['id']);
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
?>
<div class="container mt-5">
    <div>
        <h2>
            <a href="<?= topUrl() ?>" style="text-decoration: none; color: inherit;">
                <i class="bi bi-award"></i> <?= $topTitulo ?>
            </a>
        </h2><br>
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
        <h2>
            <a href="<?= recientesUrl() ?>" style="text-decoration: none; color: inherit;">
                <i class="bi bi-alarm"></i> Lo más recientes
            </a>
        </h2>
        <div class="row mt-5">
            <div class="col-md-9">
                <?php if(($secciones['publicidad']['estado'] ?? 0) == 1 && $publicidad) : ?>
                    <div class="ad-container">
                        <a href="<?php echo htmlspecialchars($publicidad['url']); ?>" class="banner-button" data-pub="<?php echo htmlspecialchars($publicidad['id_pub']); ?>">
                            <img src="<?= imageUrl($publicidad['imagen']) ?>" alt="" class="banner" loading="lazy" decoding="async">
                        </a>
                        <span class="ads-label">ADS</span>
                    </div>
                <?php endif; ?>
                <?php foreach($noticiasMasRecientes as $row): ?>
                    <div class="card mb-3" data-url="<?= newsUrl($row['id']) ?>">
                        <div class="row row-no-gap">
                            <div class="col-md-4">
                                <img src="<?= img([$row['crop3'], $row['crop2'], $row['crop1']]) ?>" alt="" class="card-img-left" loading="lazy" decoding="async">
                            </div>
                            <div class="col-md-8">
                                <div class="card-body">
                                    <?php foreach(array_filter(array_map('trim', explode(',', $row['categorias'] ?? ''))) as $cat): ?>
                                        <a href="<?= categoryUrl($cat) ?>" class="tag-news"><?= htmlspecialchars($cat) ?></a>
                                    <?php endforeach; ?>
                                    <h4 class="card-title">
                                        <a href="<?= newsUrl($row['id']) ?>" class="news-link"><?= htmlspecialchars($row['titulo']) ?></a>
                                    </h4>
                                    <p class="card-text"><?= htmlspecialchars($row['descripcion']) ?></p>
                                    <span class="text-muted"> 
                                        <?php
                                            $fecha_pub = strtotime($row['fecha']); // convierte la fecha de la BD a timestamp
                                            $ahora = time();                        // timestamp actual
                                            $diff = $ahora - $fecha_pub;            // diferencia en segundos
                                            if ($diff < 3600) { // menos de 1 hora
                                                $minutos = floor($diff / 60);
                                                echo "Publicado hace " . $minutos . " min";
                                            } elseif ($diff < 86400) { // menos de 24 horas
                                                $horas = floor($diff / 3600);
                                                echo "Publicado hace " . $horas . " hrs";
                                            } elseif ($diff < 172800) { // entre 24 y 48 horas
                                                echo "Publicado ayer";
                                            } else { // más de 48 horas
                                                echo "Publicado: " . date("M d", $fecha_pub);
                                            }
                                        ?>, 
                                        Por: <?= $row['nombre_u'] ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if($secciones['videos']['estado'] == 1) : ?>
                    <h3><i class="bi bi-camera-video"></i> Videos Destacados</h3>
                    <div class="video-carousel <?php echo count($vid) == 1? 'single-video':''; ?>">
                        <?php foreach($vid as $video): ?>
                            <div class="video-slide">
                                <?php echo bloquearEmbeds(renderizarVideo($video['url_v'])); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <br>
                <?php foreach($noticiasMasRecientes2 as $row): ?>
                    <div class="card mb-3" data-url="<?= newsUrl($row['id']) ?>">
                        <div class="row row-no-gap">
                            <div class="col-md-4">
                                <img src="<?= img([$row['crop3'], $row['crop2'], $row['crop1']]) ?>" alt="" class="card-img-left" loading="lazy" decoding="async">
                            </div>
                            <div class="col-md-8">
                                <div class="card-body">
                                    <?php foreach(array_filter(array_map('trim', explode(',', $row['categorias'] ?? ''))) as $cat): ?>
                                        <a href="<?= categoryUrl($cat) ?>" class="tag-news"><?= htmlspecialchars($cat) ?></a>
                                    <?php endforeach; ?>
                                    <h4 class="card-title">
                                        <a href="<?= newsUrl($row['id']) ?>" class="news-link"><?= htmlspecialchars($row['titulo']) ?></a>
                                    </h4>
                                    <p class="card-text"><?= htmlspecialchars($row['descripcion']) ?></p>
                                    <span class="text-muted"> 
                                        <?php
                                            $fecha_pub = strtotime($row['fecha']); // convierte la fecha de la BD a timestamp
                                            $ahora = time();                        // timestamp actual
                                            $diff = $ahora - $fecha_pub;            // diferencia en segundos
                                            if ($diff < 3600) { // menos de 1 hora
                                                $minutos = floor($diff / 60);
                                                echo "Publicado hace " . $minutos . " min";
                                            } elseif ($diff < 86400) { // menos de 24 horas
                                                $horas = floor($diff / 3600);
                                                echo "Publicado hace " . $horas . " hrs";
                                            } elseif ($diff < 172800) { // entre 24 y 48 horas
                                                echo "Publicado ayer";
                                            } else { // más de 48 horas
                                                echo "Publicado: " . date("M d", $fecha_pub);
                                            }
                                        ?>, 
                                        Por: <?= $row['nombre_u'] ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                <div class="news-carousel">
                    <?php foreach($noticiasMasRecientes3 as $row): ?>
                        <div class="news-slide">
                            <div class="news-card card-thumb" data-url="<?= newsUrl($row['id']) ?>">
                                <img src="<?= img([$row['crop3'], $row['crop2'], $row['crop1']]) ?>" alt="" loading="lazy" decoding="async">
                                <div class="news-overlay">
                                    <div class="news-tags">
                                        <?php foreach(array_filter(array_map('trim', explode(',', $row['categorias'] ?? ''))) as $cat): ?>
                                            <a href="<?= categoryUrl($cat) ?>" class="tag-news"><?= htmlspecialchars($cat) ?></a>
                                        <?php endforeach; ?>
                                    </div>
                                    <div class="news-content">
                                        <a href="<?= newsUrl($row['id']) ?>" class="news-link-card"><?= htmlspecialchars($row['titulo']) ?></a>
                                        <span class="text-muted">
                                            <?php
                                                $fecha_pub = strtotime($row['fecha']); // convierte la fecha de la BD a timestamp
                                                $ahora = time();                        // timestamp actual
                                                $diff = $ahora - $fecha_pub;            // diferencia en segundos
                                                if ($diff < 3600) { // menos de 1 hora
                                                    $minutos = floor($diff / 60);
                                                    echo "Publicado hace " . $minutos . " min";
                                                } elseif ($diff < 86400) { // menos de 24 horas
                                                    $horas = floor($diff / 3600);
                                                    echo "Publicado hace " . $horas . " hrs";
                                                } elseif ($diff < 172800) { // entre 24 y 48 horas
                                                    echo "Publicado ayer";
                                                } else { // más de 48 horas
                                                    echo "Publicado: " . date("M d", $fecha_pub);
                                                }
                                            ?>, 
                                            Por: <?= $row['nombre_u'] ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <br>
                <?php if(($secciones['publicidad']['estado'] ?? 0) == 1 && $publicidadInferior) : ?>
                    <div class="ad-container">
                        <a href="<?php echo htmlspecialchars($publicidadInferior['url']); ?>" class="banner-button" data-pub="<?php echo htmlspecialchars($publicidadInferior['id_pub']); ?>">
                            <img src="<?= imageUrl($publicidadInferior['imagen']) ?>" alt="" class="banner" loading="lazy" decoding="async">
                        </a>
                        <span class="ads-label">ADS</span>
                    </div>
                <?php endif; ?>
            </div>
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
                                                    <a href="<?= newsUrl($row['id']) ?>" class="linkCard news-link title-limit-2"><?= htmlspecialchars($row['titulo']) ?></a>
                                                </div>
                                            </div>
                                        </div>
                                    <br>
                                <?php endforeach; ?>
                            </div>
                            <h3>
                                <a href="<?= popularUrl() ?>" style="text-decoration: none; color: inherit;">
                                    Lo más popular
                                </a>
                            </h3>
                            <br>
                            <div class="sidebar-news-list">
                                <?php foreach($popularesNoticiasSidebar as $row): ?>
                                        <div class="cardSpecial row row-no-gap">
                                            <div class="col-md-4">
                                                <img src="<?= img([$row['crop3'], $row['crop2'], $row['crop1']]) ?>" alt="" class="imgCard card-img-left-rounded" loading="lazy" decoding="async">
                                            </div>
                                            <div class="col-md-8">
                                                <div class="card-body">
                                                    <a href="<?= newsUrl($row['id']) ?>" class="linkCard news-link title-limit-2"><?= htmlspecialchars($row['titulo']) ?></a>
                                                </div>
                                            </div>
                                        </div>
                                    <br>
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
            </div>
        </div>
        <br>
        <div>
              <h3><i class="bi bi-stars"></i> Recomendados para ti</h3>
              <br>
              <div class="row">
                <?php while($r = $recomendadas->fetch_assoc()):
                    $img = img([$r['crop3'], $r['crop2'], $r['crop1']]);
                ?>
                  <div class="col">
                      <div class="card h-100" data-url="<?= newsUrl($r['id']) ?>">
                          <img src="<?= $img ?>" class="card-img-top">
                          <div class="card-body">
                              <a href="<?= newsUrl($r['id']) ?>" class="news-link title-limit-1">
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
        <br>
        <div>
              <h3><i class="bi bi-lightning-fill"></i> Noticias recientes</h3>
              <br>
              <div class="row">
                <?php while($r = $recientes->fetch_assoc()):
                    $img = img([$r['crop3'], $r['crop2'], $r['crop1']]);
                ?>
                  <div class="col">
                      <div class="card h-100" data-url="<?= newsUrl($r['id']) ?>">
                          <img src="<?= $img ?>" class="card-img-top" loading="lazy" decoding="async">
                          <div class="card-body">
                              <a href="<?= newsUrl($r['id']) ?>" class="news-link title-limit-1">
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
<?php include(__DIR__ . "/layout/footer.php"); ?>