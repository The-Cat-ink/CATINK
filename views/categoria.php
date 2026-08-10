<?php
require_once("./../data/conexion.php");
require_once("./helpers/urlhelper.php");
require_once("./helpers/sidebarhelper.php");

// ==============================
// PAGINACIÓN
// ==============================
$porPagina = 10;
$pagina = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($pagina < 1) $pagina = 1;
$offset = ($pagina - 1) * $porPagina;

// ==============================
// PARÁMETROS
// ==============================
$q         = trim(urldecode($_GET['q'] ?? $_GET['query'] ?? $_GET['search'] ?? ''));
$categoria = trim(urldecode($_GET['cat'] ?? $_GET['category'] ?? $_GET['categoria'] ?? ''));

$suggestionActive = false;
$originalQuery = $q;
$displayCorrectedQuery = '';
$correctedQuery = '';

// ==============================
// SEO DINÁMICO
// ==============================
if ($q !== '') {
    $pageTitle = "Resultados para: $q";
    $pageDescription = "Busca noticias de anime y manga sobre $q en CatInk.";
} elseif ($categoria !== '') {
    $pageTitle = "Noticias de $categoria";
    $pageDescription = "Últimas noticias de $categoria en CatInk.";
} else {
    $pageTitle = "Últimas noticias de anime y manga";
    $pageDescription = "Descubre las últimas noticias del mundo del anime y manga.";
}

if ($pagina > 1) {
    $pageTitle .= " - Página $pagina";
    $pageDescription .= " Página $pagina de resultados.";
}

// ==============================
// CANONICAL
// ==============================
$canonical = "https://www.catink.com.mx";

if ($categoria !== '') {
    $canonical .= "/categoria/" . urlencode($categoria);
} elseif ($q !== '') {
    $canonical .= "/buscar/" . urlencode($q);
}

if ($pagina > 1) {
    $canonical .= "?page=" . $pagina;
}

// ==============================
// HEADER (IMPORTANTE ARRIBA)
// ==============================
include("./../layout/header.php");

// ==============================
// CONSULTA PRINCIPAL
// ==============================
if ($q !== '') {
    $words = array_filter(explode(' ', $q));
    $searchTerms = [];
    foreach ($words as $word) {
        $wordClean = preg_replace('/[+\-><()~*\"@]+/', '', $word);
        if (strlen($wordClean) >= 2) {
            $searchTerms[] = "+{$wordClean}*";
        }
    }
    if (empty($searchTerms)) {
        $firstWord = preg_replace('/[+\-><()~*\"@]+/', '', reset($words));
        if (strlen($firstWord) > 0) {
            $searchTerms[] = "+{$firstWord}*";
        }
    }
    $searchQuery = implode(' ', $searchTerms);

    $stmt = $con->prepare("
        SELECT n.id, n.slug, n.titulo, n.descripcion, n.crop3, n.fecha_publicacion,
               GROUP_CONCAT(c.nombre ORDER BY nc.orden ASC SEPARATOR ',') AS categorias
        FROM noticias n
        LEFT JOIN noticia_categoria nc ON n.id = nc.noticia_id
        LEFT JOIN categorias c ON nc.categoria_id = c.id_c
        WHERE n.eliminado_en IS NULL AND n.fecha_publicacion <= NOW()
          AND MATCH(n.titulo, n.descripcion, n.contenido) AGAINST(? IN BOOLEAN MODE)
        GROUP BY n.id
        ORDER BY n.fecha_publicacion DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->bind_param("sii", $searchQuery, $porPagina, $offset);

} elseif ($categoria !== '') {
    $stmt = $con->prepare("
        SELECT n.id, n.slug, n.titulo, n.descripcion, n.crop3, n.fecha_publicacion,
               GROUP_CONCAT(c.nombre ORDER BY nc.orden ASC SEPARATOR ',') AS categorias
        FROM noticias n
        INNER JOIN noticia_categoria nc_filter ON n.id = nc_filter.noticia_id
        INNER JOIN categorias c_filter ON nc_filter.categoria_id = c_filter.id_c AND c_filter.nombre = ?
        LEFT JOIN noticia_categoria nc ON n.id = nc.noticia_id
        LEFT JOIN categorias c ON nc.categoria_id = c.id_c
        WHERE n.eliminado_en IS NULL AND n.fecha_publicacion <= NOW()
        GROUP BY n.id
        ORDER BY n.fecha_publicacion DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->bind_param("sii", $categoria, $porPagina, $offset);

} else {
    $stmt = $con->prepare("
        SELECT n.id, n.slug, n.titulo, n.descripcion, n.crop3, n.fecha_publicacion,
               GROUP_CONCAT(c.nombre ORDER BY nc.orden ASC SEPARATOR ',') AS categorias
        FROM noticias n
        LEFT JOIN noticia_categoria nc ON n.id = nc.noticia_id
        LEFT JOIN categorias c ON nc.categoria_id = c.id_c
        WHERE n.eliminado_en IS NULL AND n.fecha_publicacion <= NOW()
        GROUP BY n.id
        ORDER BY n.fecha_publicacion DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->bind_param("ii", $porPagina, $offset);
}

$stmt->execute();
$result = $stmt->get_result();

// ==============================
// TOTAL
// ==============================
if ($q !== '') {
    $words = array_filter(explode(' ', $q));
    $searchTerms = [];
    foreach ($words as $word) {
        $wordClean = preg_replace('/[+\-><()~*\"@]+/', '', $word);
        if (strlen($wordClean) >= 2) {
            $searchTerms[] = "+{$wordClean}*";
        }
    }
    if (empty($searchTerms)) {
        $firstWord = preg_replace('/[+\-><()~*\"@]+/', '', reset($words));
        if (strlen($firstWord) > 0) {
            $searchTerms[] = "+{$firstWord}*";
        }
    }
    $searchQuery = implode(' ', $searchTerms);

    $stmtTotal = $con->prepare("
        SELECT COUNT(DISTINCT n.id) as total
        FROM noticias n
        LEFT JOIN noticia_categoria nc ON n.id = nc.noticia_id
        LEFT JOIN categorias c ON nc.categoria_id = c.id_c
        WHERE n.eliminado_en IS NULL AND n.fecha_publicacion <= NOW()
          AND MATCH(n.titulo, n.descripcion, n.contenido) AGAINST(? IN BOOLEAN MODE)
    ");
    $stmtTotal->bind_param("s", $searchQuery);

} elseif ($categoria !== '') {
    $stmtTotal = $con->prepare("
        SELECT COUNT(DISTINCT n.id) as total
        FROM noticias n
        INNER JOIN noticia_categoria nc ON n.id = nc.noticia_id
        INNER JOIN categorias c ON nc.categoria_id = c.id_c
        WHERE n.eliminado_en IS NULL AND n.fecha_publicacion <= NOW()
        AND c.nombre = ?
    ");
    $stmtTotal->bind_param("s", $categoria);

} else {
    $stmtTotal = $con->prepare("
        SELECT COUNT(*) as total FROM noticias
        WHERE eliminado_en IS NULL AND fecha_publicacion <= NOW()
    ");
}

$stmtTotal->execute();
$totalNoticias = $stmtTotal->get_result()->fetch_assoc()['total'];
$totalpaginas = ceil($totalNoticias / $porPagina);

// ==============================
// CORRECTOR ORTOGRÁFICO (SUGGESTION / DID YOU MEAN)
// ==============================
if ($q !== '' && $totalNoticias == 0 && !isset($_GET['force'])) {
    // 1. Obtener todas las palabras únicas de los títulos en la BD
    $resWords = $con->query("SELECT DISTINCT titulo FROM noticias WHERE eliminado_en IS NULL AND fecha_publicacion <= NOW()");
    $allDbWords = [];
    if ($resWords) {
        while ($rowWord = $resWords->fetch_assoc()) {
            $wordsInTitle = preg_split('/[\s,\.\-\?\!\'\"\(\)¿¡]+/u', mb_strtolower($rowWord['titulo'], 'UTF-8'));
            foreach ($wordsInTitle as $w) {
                $w = trim($w);
                if (mb_strlen($w, 'UTF-8') > 2) {
                    $allDbWords[$w] = true;
                }
            }
        }
    }
    $uniqueDbWords = array_keys($allDbWords);

    // 2. Analizar cada palabra de la búsqueda del usuario
    $queryWords = array_filter(explode(' ', $q));
    $correctedWords = [];
    $displayCorrected = [];
    $hasCorrection = false;

    foreach ($queryWords as $userWord) {
        $userWordLower = mb_strtolower($userWord, 'UTF-8');
        if (isset($allDbWords[$userWordLower])) {
            $correctedWords[] = $userWord;
            $displayCorrected[] = htmlspecialchars($userWord);
            continue;
        }

        $bestMatch = null;
        $shortestDist = -1;
        foreach ($uniqueDbWords as $dbWord) {
            $dist = levenshtein($userWordLower, $dbWord);
            // Umbral de tolerancia de error (diferencia de 1 o 2 caracteres)
            if ($dist >= 1 && $dist <= 2) {
                if ($shortestDist === -1 || $dist < $shortestDist) {
                    $shortestDist = $dist;
                    $bestMatch = $dbWord;
                }
            }
        }

        if ($bestMatch !== null) {
            // Mantener mayúscula si el usuario la usó
            if (ctype_upper($userWord[0])) {
                $bestMatchFormatted = mb_convert_case($bestMatch, MB_CASE_TITLE, "UTF-8");
            } else {
                $bestMatchFormatted = $bestMatch;
            }
            $correctedWords[] = $bestMatchFormatted;
            $displayCorrected[] = '<span style="font-weight: bold; font-style: italic;">' . htmlspecialchars($bestMatchFormatted) . '</span>';
            $hasCorrection = true;
        } else {
            $correctedWords[] = $userWord;
            $displayCorrected[] = htmlspecialchars($userWord);
        }
    }

    if ($hasCorrection) {
        $correctedQuery = implode(' ', $correctedWords);
        $displayCorrectedQuery = implode(' ', $displayCorrected);
        
        // Ejecutar la consulta de nuevo con la query corregida
        $suggestionActive = true;
        $q = $correctedQuery; // Cambiar $q temporalmente para la consulta principal y conteo
        
        // Re-ejecutar consulta principal con $q corregida
        $words = array_filter(explode(' ', $q));
        $stmt = $con->prepare("
            SELECT n.id, n.slug, n.titulo, n.descripcion, n.crop3, n.fecha_publicacion,
                   GROUP_CONCAT(c.nombre ORDER BY nc.orden ASC SEPARATOR ',') AS categorias
            FROM noticias n
            LEFT JOIN noticia_categoria nc ON n.id = nc.noticia_id
            LEFT JOIN categorias c ON nc.categoria_id = c.id_c
            WHERE n.eliminado_en IS NULL AND n.fecha_publicacion <= NOW()
              AND MATCH(n.titulo, n.descripcion, n.contenido) AGAINST(? IN BOOLEAN MODE)
            GROUP BY n.id
            ORDER BY n.fecha_publicacion DESC
            LIMIT ? OFFSET ?
        ");
        
        $searchTerms = [];
        foreach ($words as $word) {
            $wordClean = preg_replace('/[+\-><()~*\"@]+/', '', $word);
            if (strlen($wordClean) >= 2) {
                $searchTerms[] = "+{$wordClean}*";
            }
        }
        if (empty($searchTerms)) {
            $firstWord = preg_replace('/[+\-><()~*\"@]+/', '', reset($words));
            if (strlen($firstWord) > 0) {
                $searchTerms[] = "+{$firstWord}*";
            }
        }
        $searchQuery = implode(' ', $searchTerms);
        
        $stmt->bind_param("sii", $searchQuery, $porPagina, $offset);
        $stmt->execute();
        $result = $stmt->get_result();

        // Re-calcular total
        $stmtTotal = $con->prepare("
            SELECT COUNT(DISTINCT n.id) as total
            FROM noticias n
            LEFT JOIN noticia_categoria nc ON n.id = nc.noticia_id
            LEFT JOIN categorias c ON nc.categoria_id = c.id_c
            WHERE n.eliminado_en IS NULL AND n.fecha_publicacion <= NOW()
              AND MATCH(n.titulo, n.descripcion, n.contenido) AGAINST(? IN BOOLEAN MODE)
        ");
        $stmtTotal->bind_param("s", $searchQuery);
        $stmtTotal->execute();
        $totalNoticias = $stmtTotal->get_result()->fetch_assoc()['total'];
        $totalpaginas = ceil($totalNoticias / $porPagina);
    }
}

// ==============================
// SIDEBAR
// ==============================
$stmtUltimas = $con->prepare("
    SELECT id, slug, titulo, crop3
    FROM noticias
    WHERE eliminado_en IS NULL AND fecha_publicacion <= NOW()
    ORDER BY fecha_publicacion DESC
    LIMIT 3
");
$stmtUltimas->execute();
$ultimas = $stmtUltimas->get_result();

$stmtPopulares = $con->prepare("
    SELECT id, slug, titulo, crop3
    FROM noticias
    WHERE eliminado_en IS NULL AND fecha_publicacion <= NOW()
    ORDER BY vistas DESC, likes DESC
    LIMIT 3
");
$stmtPopulares->execute();
$populares = $stmtPopulares->get_result();

$publicidadCuadro = obtenerPublicidad($con, 'lateral', 2);

// ==============================
// BREADCRUMBS JSON-LD
// ==============================
$breadcrumbList = [
    "@context" => "https://schema.org",
    "@type" => "BreadcrumbList",
    "itemListElement" => [
        [
            "@type" => "ListItem",
            "position" => 1,
            "name" => "Inicio",
            "item" => "https://www.catink.com.mx/"
        ]
    ]
];

$position = 2;

if ($categoria !== '') {
    $breadcrumbList['itemListElement'][] = [
        "@type" => "ListItem",
        "position" => $position++,
        "name" => $categoria,
        "item" => categoryUrl($categoria)
    ];
}

if ($q !== '') {
    $breadcrumbList['itemListElement'][] = [
        "@type" => "ListItem",
        "position" => $position++,
        "name" => "Resultados para: $q",
        "item" => $canonical
    ];
}
?>

<!-- SEO -->
<link rel="canonical" href="<?= $canonical ?>">
<?php if ($q !== ''): ?>
<meta name="robots" content="noindex, follow">
<?php endif; ?>

<script type="application/ld+json">
<?= json_encode($breadcrumbList, JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT) ?>
</script>

<div class="container mt-5">
  <div class="container-fluid">

    <!-- TITULO CONTEXTUAL -->
    <?php if ($suggestionActive): ?>
      <div class="search-suggestion" style="margin-bottom: 25px; font-family: inherit; font-size: 1.05rem; line-height: 1.6; color: var(--text);">
          <div style="margin-bottom: 4px;">
              Se muestran resultados de <a href="?q=<?= urlencode($q) ?>" style="color: var(--accent); text-decoration: none; font-weight: bold;"><?= $displayCorrectedQuery ?></a>
          </div>
          <div style="font-size: 0.95rem; color: var(--muted, #888);">
              Buscar, en cambio, <a href="?q=<?= urlencode($originalQuery) ?>&force=1" style="color: var(--accent); text-decoration: underline;"><?= htmlspecialchars($originalQuery) ?></a>
          </div>
      </div>
    <?php elseif ($q !== ''): ?>
      <div class="cat-header-hero mb-4 p-4" style="background: var(--card-bg); border: 1px solid var(--border); border-radius: 18px; box-shadow: 0 8px 24px rgba(0,0,0,0.04); position: relative; overflow: hidden;">
        <div style="position: absolute; top: -40px; right: -40px; width: 160px; height: 160px; background: var(--accent); opacity: 0.08; filter: blur(40px); border-radius: 50%; pointer-events: none;"></div>
        <div class="d-flex align-items-center gap-3">
          <div style="width: 52px; height: 52px; border-radius: 14px; background: rgba(239,51,99,0.12); color: var(--accent); display: flex; align-items: center; justify-content: center; font-size: 1.6rem; flex-shrink: 0; border: 1px solid rgba(239,51,99,0.25);">
            <i class="bi bi-search"></i>
          </div>
          <div>
            <div style="font-size: 0.75rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.12em; color: var(--accent); margin-bottom: 2px;">
              RESULTADOS DE BÚSQUEDA
            </div>
            <h1 style="font-size: 1.75rem; font-weight: 900; color: var(--text); margin: 0; line-height: 1.2;">
              <?= htmlspecialchars($q) ?>
            </h1>
            <p style="font-size: 0.88rem; color: var(--muted); margin: 4px 0 0 0;">
              Noticias y publicaciones encontradas sobre "<?= htmlspecialchars($q) ?>".
            </p>
          </div>
        </div>
      </div>
    <?php elseif ($categoria !== ''): ?>
      <div class="cat-header-hero mb-4 p-4" style="background: var(--card-bg); border: 1px solid var(--border); border-radius: 18px; box-shadow: 0 8px 24px rgba(0,0,0,0.04); position: relative; overflow: hidden;">
        <div style="position: absolute; top: -40px; right: -40px; width: 160px; height: 160px; background: var(--accent); opacity: 0.08; filter: blur(40px); border-radius: 50%; pointer-events: none;"></div>
        <div class="d-flex align-items-center gap-3">
          <div style="width: 52px; height: 52px; border-radius: 14px; background: rgba(239,51,99,0.12); color: var(--accent); display: flex; align-items: center; justify-content: center; font-size: 1.6rem; flex-shrink: 0; border: 1px solid rgba(239,51,99,0.25);">
            <i class="bi bi-folder-fill"></i>
          </div>
          <div>
            <div style="font-size: 0.75rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.12em; color: var(--accent); margin-bottom: 2px;">
              EXPLORANDO CATEGORÍA
            </div>
            <h1 style="font-size: 1.75rem; font-weight: 900; color: var(--text); margin: 0; line-height: 1.2;">
              <?= htmlspecialchars($categoria) ?>
            </h1>
            <p style="font-size: 0.88rem; color: var(--muted); margin: 4px 0 0 0;">
              Explora los últimos artículos, análisis y novedades publicadas sobre <?= htmlspecialchars($categoria) ?>.
            </p>
          </div>
        </div>
      </div>
    <?php endif; ?>
    <br>
    <div class="row">

      <!-- MAIN -->
      <div class="col-md-9">

        <?php if ($result->num_rows === 0): ?>
          <p>No se encontraron resultados.</p>
        <?php endif; ?>

        <div class="horizontal-cards-list">
          <?php while ($row = $result->fetch_assoc()): ?>
            <?php
              $cats = !empty($row['categorias']) ? explode(",", $row['categorias']) : [];
              $cats = array_map('trim', $cats);
              if ($categoria !== '' && in_array($categoria, $cats)) {
                  $cats = array_diff($cats, [$categoria]);
                  array_unshift($cats, $categoria);
              }
              $img = imageUrl($row['crop3'] ?? $row['crop2'] ?? $row['crop1']);
              $newsUrl = newsUrlFromRow($row);
            ?>
            <article class="horizontal-news-card" onclick="window.location.href='<?= $newsUrl ?>'" data-url="<?= $newsUrl ?>" data-article-id="<?= $row['id'] ?>">
              <div class="h-card-img-wrapper">
                <img src="<?= $img ?>" alt="<?= htmlspecialchars($row['titulo']) ?>" loading="lazy" decoding="async">
              </div>
              <div class="h-card-body">
                <div class="h-card-tags">
                  <?php foreach ($cats as $cat): ?>
                    <a href="<?= categoryUrl($cat) ?>" class="category-pill-solid" onclick="event.stopPropagation();"><?= htmlspecialchars($cat) ?></a>
                  <?php endforeach; ?>
                </div>
                <h2 class="h-card-title">
                  <a href="<?= $newsUrl ?>"><?= htmlspecialchars($row['titulo']) ?></a>
                </h2>
                <p class="h-card-desc"><?= htmlspecialchars($row['descripcion']) ?></p>
                <div class="h-card-meta">
                  <span><i class="bi bi-clock"></i> <?= date('d/M/y', strtotime($row['fecha_publicacion'])) ?></span>
                  <?php if (isset($_SESSION['usuario']) && (isset($_SESSION['perm_noticias']) && $_SESSION['perm_noticias'] == 1)): ?>
                    <a href="<?= basePath() ?>/editar/<?= $row['id'] ?>" class="ms-auto btn btn-sm btn-outline-primary py-0" style="font-size: 0.75rem;" onclick="event.stopPropagation();">
                      <i class="bi bi-pencil"></i> Editar
                    </a>
                  <?php endif; ?>
                </div>
              </div>
            </article>
          <?php endwhile; ?>
        </div>

        <!-- PAGINACIÓN -->
        <div class="pagination-wrapper">
            <ul class="pagination">
                <?php if ($pagina > 1): ?>
                    <li>
                        <a href="?page=<?= $pagina-1 ?>&q=<?= urlencode($q) ?>&cat=<?= urlencode($categoria) ?>">
                            « Anterior
                        </a>
                    </li>
                <?php endif; ?>
                <?php for ($i = 1; $i <= $totalpaginas; $i++): ?>
                    <li class="<?= $i == $pagina ? 'active' : '' ?>">
                        <a href="?page=<?= $i ?>&q=<?= urlencode($q) ?>&cat=<?= urlencode($categoria) ?>">
                            <?= $i ?>
                        </a>
                    </li>
                <?php endfor; ?>
                <?php if ($pagina < $totalpaginas): ?>
                    <li>
                        <a href="?page=<?= $pagina+1 ?>&q=<?= urlencode($q) ?>&cat=<?= urlencode($categoria) ?>">
                            Siguiente »
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
      </div>

      <!-- SIDEBAR -->
      <div class="col-md-3">
        <?php renderSidebarNewsWidget($ultimas, $populares, $publicidadCuadro ?? null, $secciones ?? null); ?>
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
  </div>
</div>
<?php include("./../layout/footer.php"); ?>