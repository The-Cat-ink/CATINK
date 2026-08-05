<?php
require_once("./../data/conexion.php");
require_once("./helpers/urlhelper.php");
require_once("./helpers/sidebarhelper.php");

// ==============================
// FILTROS (no aplican en esta vista)
// ==============================
// Este ranking es fijo: no filtra por categoría ni por búsqueda. Se declaran
// vacías porque el marcado compartido con categoria.php las consulta.
$categoria = '';
$q         = '';

// ==============================
// PAGINACIÓN
// ==============================
$porPagina = 10;
$pagina = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($pagina < 1) $pagina = 1;
$offset = ($pagina - 1) * $porPagina;

// ==============================
// SEO DINÁMICO
// ==============================
$pageTitle = "Noticias Recientes";
$pageDescription = "Explora todo el contenido de CatInk ordenado desde lo más nuevo a lo más antiguo.";

if ($pagina > 1) {
    $pageTitle .= " - Página $pagina";
    $pageDescription .= " Página $pagina de resultados.";
}

// ==============================
// CANONICAL
// ==============================
$canonical = "https://www.catink.com.mx/recientes";

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

      <!-- Encabezado de resultados -->
      <div class="mb-4">
          <h1 style="font-weight: 800; font-size: 2rem;">Lo más reciente</h1>
          <p class="text-muted" style="font-size: 1.1rem;">Todo el contenido publicado de manera cronológica</p>
      </div>
    <div class="row">

      <!-- MAIN -->
      <div class="col-md-9">

        <?php if ($result->num_rows === 0): ?>
          <p>No se encontraron resultados.</p>
        <?php endif; ?>

        <?php while ($row = $result->fetch_assoc()): ?>
          <?php
            $cats = !empty($row['categorias']) ? explode(",", $row['categorias']) : [];
            $cats = array_map('trim', $cats);

            if ($categoria !== '' && in_array($categoria, $cats)) {
                $cats = array_diff($cats, [$categoria]);
                array_unshift($cats, $categoria);
            }

            $img = imageUrl($row['crop3']);
          ?>

          <div class="card mb-3" data-url="<?= newsUrlFromRow($row) ?>">
            <div class="row row-no-gap">

              <div class="col-md-4">
                <img src="<?= $img ?>" class="card-img-left" loading="lazy" decoding="async">
              </div>

              <div class="col-md-8">
                <div class="card-body">

                  <?php foreach ($cats as $cat): ?>
                    <a href="<?= categoryUrl($cat) ?>" class="news-tag"><?= htmlspecialchars($cat) ?></a>
                  <?php endforeach; ?>

                  <h5 class="card-title">
                    <a href="<?= newsUrlFromRow($row) ?>" class="news-link">
                      <?= htmlspecialchars($row['titulo']) ?>
                    </a>
                  </h5>

                  <p><?= htmlspecialchars($row['descripcion']) ?></p>

                  <small><?= date('d M Y', strtotime($row['fecha_publicacion'])) ?></small>

                  <?php 
                    // Mostrar botón de editar si es editor/admin
                    if (isset($_SESSION['usuario']) && (isset($_SESSION['perm_noticias']) && $_SESSION['perm_noticias'] == 1)): 
                  ?>
                    <div style="margin-top: 10px;">
                      <a href="<?= basePath() ?>/editar/<?= $row['id'] ?>" class="btn btn-sm btn-primary" style="font-size: 0.85rem;">
                        <i class="bi bi-pencil"></i> Editar
                      </a>
                    </div>
                  <?php endif; ?>

                </div>
              </div>

            </div>
          </div>
        <?php endwhile; ?>

          <?php
            // Calcular páginas para el enlace base (sin query string)
            $baseUrl = recientesUrl() . "?";
          ?>
        <!-- PAGINACIÓN -->
        <div class="pagination-wrapper">
            <ul class="pagination">
                <?php if ($pagina > 1): ?>
                    <li>
                        <a href="<?= $baseUrl ?>page=<?= $pagina-1 ?>">
                            « Anterior
                        </a>
                    </li>
                <?php endif; ?>
                <?php for ($i = 1; $i <= $totalpaginas; $i++): ?>
                    <li class="<?= $i == $pagina ? 'active' : '' ?>">
                        <a href="<?= $baseUrl ?>page=<?= $i ?>">
                            <?= $i ?>
                        </a>
                    </li>
                <?php endfor; ?>
                <?php if ($pagina < $totalpaginas): ?>
                    <li>
                        <a href="<?= $baseUrl ?>page=<?= $pagina+1 ?>">
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