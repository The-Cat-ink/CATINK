<?php
include(__DIR__ . "/../layout/header.php");
require_once(__DIR__ . "/../data/conexion.php");
include(__DIR__ . "/../views/helpers/videoEmbed.php");
require_once(__DIR__ . "/../views/helpers/urlhelper.php");

// Cargar noticias globales
$stmtGlobal = $con->prepare("
    SELECT n.id, n.slug, n.titulo, n.descripcion, n.crop1, n.crop2, n.crop3, n.crop4, n.fecha_publicacion AS fecha,
           n.likes, n.vistas, n.tipo_publicacion, n.calificacion, u.nombre AS nombre_u,
           GROUP_CONCAT(c.nombre ORDER BY nc.orden ASC SEPARATOR ',') AS categorias
    FROM noticias n
    INNER JOIN usuarios u ON n.autor = u.id_u
    LEFT JOIN noticia_categoria nc ON n.id = nc.noticia_id
    LEFT JOIN categorias c ON nc.categoria_id = c.id_c
    WHERE n.eliminado_en IS NULL AND n.fecha_publicacion <= NOW()
    GROUP BY n.id
    ORDER BY n.fecha_publicacion DESC
    LIMIT 30;
");
$stmtGlobal->execute();
$noticias = $stmtGlobal->get_result()->fetch_all(MYSQLI_ASSOC);

$hero = $noticias[0] ?? null;
$gridItems = array_slice($noticias, 1, 6);
$recientes = array_slice($noticias, 7, 10);
?>

<!-- ESTILOS EXCLUSIVOS OPCIÓN 3: TRENDING GRID 2 COLUMNAS -->
<style>
  .test-option-banner {
    background: linear-gradient(135deg, #7000FF 0%, #00F0FF 100%);
    color: white;
    text-align: center;
    padding: 10px 15px;
    font-weight: 700;
    font-size: 13px;
  }
  .test-option-banner a { color: #fff; text-decoration: underline; margin: 0 4px; }

  /* Chips de Filtro Flotante */
  .m3-chips-bar {
    display: flex;
    gap: 8px;
    overflow-x: auto;
    padding: 12px 16px;
    white-space: nowrap;
    scrollbar-width: none;
  }
  .m3-chips-bar::-webkit-scrollbar { display: none; }
  
  .m3-chip {
    background: var(--card-bg);
    border: 1.5px solid var(--border);
    color: var(--text);
    font-size: 12px;
    font-weight: 700;
    padding: 6px 14px;
    border-radius: 30px;
    cursor: pointer;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
  }
  .m3-chip.active {
    background: var(--accent-fuchsia);
    border-color: var(--accent-fuchsia);
    color: #fff;
    box-shadow: 0 4px 12px rgba(239, 51, 99, 0.3);
  }

  /* Hero Card Limpia */
  .m3-hero-card {
    margin: 0 16px 16px 16px;
    border-radius: 18px;
    overflow: hidden;
    background: var(--card-bg);
    border: 1px solid var(--border);
    box-shadow: 0 6px 20px rgba(0,0,0,0.08);
    text-decoration: none;
    display: block;
  }
  .m3-hero-img {
    width: 100%;
    aspect-ratio: 16/9;
    object-fit: cover;
  }
  .m3-hero-body {
    padding: 16px;
  }
  .m3-hero-tag {
    font-size: 10px;
    font-weight: 800;
    color: var(--accent-fuchsia);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 4px;
    display: block;
  }
  .m3-hero-title {
    font-size: 1.2rem;
    font-weight: 900;
    color: var(--text);
    line-height: 1.35;
    margin-bottom: 6px;
  }

  /* Grid 2 Columnas (Lado a Lado) */
  .m3-grid-2col {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    padding: 0 16px 20px 16px;
  }
  .m3-grid-card {
    background: var(--card-bg);
    border-radius: 14px;
    overflow: hidden;
    border: 1px solid var(--border);
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    text-decoration: none;
    display: flex;
    flex-direction: column;
  }
  .m3-grid-img {
    width: 100%;
    aspect-ratio: 4/3;
    object-fit: cover;
  }
  .m3-grid-body {
    padding: 10px;
    display: flex;
    flex-direction: column;
    flex-grow: 1;
    justify-content: space-between;
  }
  .m3-grid-tag {
    font-size: 9px;
    font-weight: 800;
    color: var(--accent-fuchsia);
    text-transform: uppercase;
    margin-bottom: 3px;
  }
  .m3-grid-title {
    font-size: 0.88rem;
    font-weight: 800;
    color: var(--text);
    line-height: 1.3;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
  }
</style>

<!-- NAVEGACIÓN -->
<div class="test-option-banner">
  🔥 <strong>TEST MÓVIL — OPCIÓN 3: Trending Grid (2 Columnas Lado a Lado)</strong>
  <br>
  Ir a: 
  <a href="<?= basePath() ?>/views/test_movil_1.php">[1. App Feed]</a> | 
  <a href="<?= basePath() ?>/views/test_movil_2.php">[2. Geek Magazine]</a> | 
  <a href="<?= basePath() ?>/views/test_movil_3.php" style="font-weight:800;">[3. Trending Grid]</a>
</div>

<!-- 1. CHIPS DE FILTRO DE CATEGORÍAS -->
<div class="m3-chips-bar">
  <span class="m3-chip active">🔥 Tendencias</span>
  <span class="m3-chip">🎬 Cine y Series</span>
  <span class="m3-chip">🎌 Anime</span>
  <span class="m3-chip">🎮 Videojuegos</span>
  <span class="m3-chip">⭐ Reviews</span>
</div>

<!-- 2. HERO PRINCIPAL DESTACADO -->
<?php if ($hero): 
  $heroUrl = newsUrlFromRow($hero);
  $cat = explode(',', $hero['categorias'] ?? 'Noticias')[0];
?>
<a href="<?= $heroUrl ?>" class="m3-hero-card">
  <img src="<?= img([$hero['crop3'], $hero['crop1']]) ?>" class="m3-hero-img" alt="">
  <div class="m3-hero-body">
    <span class="m3-hero-tag">🌟 DESTACADO • <?= htmlspecialchars($cat) ?></span>
    <h2 class="m3-hero-title"><?= htmlspecialchars($hero['titulo']) ?></h2>
    <small style="font-size: 11px; color: var(--muted); font-weight: 600;">
      Por <?= htmlspecialchars($hero['nombre_u']) ?> • <?= tiempoRelativo($hero['fecha']) ?>
    </small>
  </div>
</a>
<?php endif; ?>

<!-- 3. GRID 2 COLUMNAS LADO A LADO -->
<div class="px-3 mb-2 d-flex justify-content-between align-items-center">
  <h6 style="font-weight: 900; font-size: 0.95rem; margin: 0; color: var(--text);">
    ⚡ Explorar en Cuadrícula
  </h6>
  <small style="color: var(--muted); font-size: 11px;">Vista doble</small>
</div>

<div class="m3-grid-2col">
  <?php foreach($gridItems as $item): 
    $url = newsUrlFromRow($item);
    $cat = explode(',', $item['categorias'] ?? 'Geek')[0];
  ?>
    <a href="<?= $url ?>" class="m3-grid-card">
      <img src="<?= img([$item['crop3'], $item['crop1']]) ?>" class="m3-grid-img" alt="">
      <div class="m3-grid-body">
        <div>
          <span class="m3-grid-tag"><?= htmlspecialchars($cat) ?></span>
          <h4 class="m3-grid-title"><?= htmlspecialchars($item['titulo']) ?></h4>
        </div>
        <div style="font-size: 10px; color: var(--muted); font-weight: 600; margin-top: 6px;">
          <?= tiempoRelativo($item['fecha']) ?>
        </div>
      </div>
    </a>
  <?php endforeach; ?>
</div>

<?php include(__DIR__ . "/../layout/footer.php"); ?>
