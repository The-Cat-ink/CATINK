<?php
require_once("./../data/conexion.php");
require_once("./helpers/urlhelper.php");
require_once("./helpers/sidebarhelper.php");
require_once("./helpers/publicidadhelper.php");

// ==============================
// PARÁMETROS
// ==============================
$porPagina = 10;
$pagina = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($pagina < 1) $pagina = 1;
$offset = ($pagina - 1) * $porPagina;

$categoria = isset($_GET['cat']) ? trim($_GET['cat']) : '';
$q = isset($_GET['q']) ? trim($_GET['q']) : '';

// ==============================
// SEO DINÁMICO
// ==============================
$pageTitle = "Lo más popular - CatInk";
$pageDescription = "Las noticias más populares, vistas y comentadas de todos los tiempos en CatInk.";

if ($pagina > 1) {
    $pageTitle .= " - Página $pagina";
    $pageDescription .= " Página $pagina de resultados.";
}

// ==============================
// CANONICAL
// ==============================
$canonical = "https://www.catink.com.mx/popular";
if ($pagina > 1) {
    $canonical .= "?page=" . $pagina;
}

// ==============================
// HEADER
// ==============================
include("./../layout/header.php");

// ==============================
// CONSULTA PRINCIPAL
// ==============================
$stmt = $con->prepare("
    SELECT n.id, n.slug, n.titulo, n.descripcion, n.crop1, n.crop2, n.crop3, n.fecha_publicacion,
           GROUP_CONCAT(c.nombre ORDER BY nc.orden ASC SEPARATOR ',') AS categorias
    FROM noticias n
    LEFT JOIN noticia_categoria nc ON n.id = nc.noticia_id
    LEFT JOIN categorias c ON nc.categoria_id = c.id_c
    WHERE n.eliminado_en IS NULL AND n.fecha_publicacion <= NOW()
    GROUP BY n.id
    ORDER BY n.vistas DESC, n.likes DESC
    LIMIT ? OFFSET ?
");
$stmt->bind_param("ii", $porPagina, $offset);
$stmt->execute();
$result = $stmt->get_result();

// ==============================
// TOTAL
// ==============================
$stmtTotal = $con->prepare("
    SELECT COUNT(*) as total FROM noticias
    WHERE eliminado_en IS NULL AND fecha_publicacion <= NOW()
");
$stmtTotal->execute();
$totalNoticias = $stmtTotal->get_result()->fetch_assoc()['total'];
$totalpaginas = ceil($totalNoticias / $porPagina);

// ==============================
// SIDEBAR (WIDGET UNIFICADO)
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
        ],
        [
            "@type" => "ListItem",
            "position" => 2,
            "name" => "Lo más popular",
            "item" => $canonical
        ]
    ]
];
?>

<!-- SEO -->
<link rel="canonical" href="<?= $canonical ?>">
<script type="application/ld+json">
<?= json_encode($breadcrumbList, JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT) ?>
</script>

<div class="container my-5">
  <!-- ENCABEZADO TIPO HERO EN LÍNEA CON EL DISEÑO DE CATINK -->
  <div class="cat-header-hero mb-4 p-4" style="background: var(--card-bg); border: 1px solid var(--border); border-radius: 18px; box-shadow: 0 8px 24px rgba(0,0,0,0.04); position: relative; overflow: hidden;">
    <div style="position: absolute; top: -40px; right: -40px; width: 160px; height: 160px; background: var(--accent-fuchsia); opacity: 0.08; filter: blur(40px); border-radius: 50%; pointer-events: none;"></div>
    <div class="d-flex align-items-center gap-3">
      <div style="width: 52px; height: 52px; border-radius: 14px; background: rgba(239,51,99,0.12); color: var(--accent-fuchsia); display: flex; align-items: center; justify-content: center; font-size: 1.6rem; flex-shrink: 0; border: 1px solid rgba(239,51,99,0.25);">
        <i class="bi bi-fire"></i>
      </div>
      <div>
        <div style="font-size: 0.75rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.12em; color: var(--accent-fuchsia); margin-bottom: 2px;">
          NOTICIAS DESTACADAS
        </div>
        <h1 style="font-size: 1.75rem; font-weight: 900; color: var(--text); margin: 0; line-height: 1.2;">
          Lo más popular
        </h1>
        <p style="font-size: 0.88rem; color: var(--muted); margin: 4px 0 0 0;">
          Las publicaciones más vistas, compartidas y debatidas en CatInk.
        </p>
      </div>
    </div>
  </div>

  <div class="row g-4">
    <!-- SECCIÓN PRINCIPAL DE NOTICIAS -->
    <div class="col-lg-8 col-md-12">
      <?php if ($result->num_rows === 0): ?>
        <div class="p-5 text-center" style="background: var(--card-bg); border-radius: 16px; border: 1px solid var(--border);">
          <i class="bi bi-journal-x" style="font-size: 3rem; color: var(--muted);"></i>
          <p class="mt-3 text-muted" style="font-size: 1.1rem;">No se encontraron noticias populares.</p>
        </div>
      <?php else: ?>
        <div class="horizontal-cards-list">
          <?php while ($row = $result->fetch_assoc()): ?>
            <?php
              $cats = !empty($row['categorias']) ? explode(",", $row['categorias']) : [];
              $cats = array_map('trim', $cats);
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
      <?php endif; ?>

      <!-- PAGINACIÓN CON ESTILO MODERNO -->
      <?php if ($totalpaginas > 1): ?>
        <?php $baseUrl = popularUrl() . "?"; ?>
        <div class="pagination-wrapper mt-4">
          <ul class="pagination justify-content-center">
            <?php if ($pagina > 1): ?>
              <li class="page-item">
                <a class="page-link" href="<?= $baseUrl ?>page=<?= $pagina-1 ?>">« Anterior</a>
              </li>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $totalpaginas; $i++): ?>
              <li class="page-item <?= $i == $pagina ? 'active' : '' ?>">
                <a class="page-link" href="<?= $baseUrl ?>page=<?= $i ?>"><?= $i ?></a>
              </li>
            <?php endfor; ?>

            <?php if ($pagina < $totalpaginas): ?>
              <li class="page-item">
                <a class="page-link" href="<?= $baseUrl ?>page=<?= $pagina+1 ?>">Siguiente »</a>
              </li>
            <?php endif; ?>
          </ul>
        </div>
      <?php endif; ?>
    </div>

    <!-- SIDEBAR DE NOTICIAS & PUBLICIDAD -->
    <div class="col-lg-4 col-md-12">
      <?php renderSidebarNewsWidget($ultimas, $populares, $publicidadCuadro ?? null, $secciones ?? null); ?>
    </div>
  </div>
</div>

<?php include("./../layout/footer.php"); ?>