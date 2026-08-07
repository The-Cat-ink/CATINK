<?php
include(__DIR__ . "/../layout/header.php");
require_once(__DIR__ . "/../data/conexion.php");
include(__DIR__ . "/../views/helpers/videoEmbed.php");
require_once(__DIR__ . "/../views/helpers/urlhelper.php");

// Cargar noticias globales para la prueba
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

$slider = array_slice($noticias, 0, 4);
$topSemanal = array_slice($noticias, 4, 5);
$recientes = array_slice($noticias, 9, 10);
?>

<!-- ESTILOS EXCLUSIVOS OPCIÓN 1: APP FEED MINIMALISTA -->
<style>
  .test-option-banner {
    background: linear-gradient(135deg, #EF3363 0%, #7000FF 100%);
    color: white;
    text-align: center;
    padding: 10px 15px;
    font-weight: 700;
    font-size: 13px;
    letter-spacing: 0.5px;
  }
  .test-option-banner a {
    color: #fff;
    text-decoration: underline;
    margin: 0 4px;
  }

  /* Stories / Categorías superiores */
  .m1-stories-bar {
    display: flex;
    gap: 14px;
    overflow-x: auto;
    padding: 15px 16px;
    scroll-snap-type: x mandatory;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: none;
  }
  .m1-stories-bar::-webkit-scrollbar { display: none; }
  
  .m1-story-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
    flex-shrink: 0;
    cursor: pointer;
    text-decoration: none;
  }
  .m1-story-ring {
    width: 62px;
    height: 62px;
    border-radius: 50%;
    padding: 2.5px;
    background: linear-gradient(45deg, #EF3363, #FF8A00, #7000FF);
    display: flex;
    align-items: center;
    justify-content: center;
  }
  .m1-story-ring img {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid var(--card-bg, #fff);
  }
  .m1-story-name {
    font-size: 11px;
    font-weight: 700;
    color: var(--text);
    max-width: 68px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  /* Carrusel de Tarjetas Top Semanal Swipe */
  .m1-top-carousel {
    display: flex;
    gap: 16px;
    overflow-x: auto;
    padding: 0 16px 15px 16px;
    scroll-snap-type: x mandatory;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: none;
  }
  .m1-top-carousel::-webkit-scrollbar { display: none; }

  .m1-top-card {
    flex: 0 0 84%; /* 84% de ancho para asomar la siguiente tarjeta */
    scroll-snap-align: center;
    border-radius: 18px;
    overflow: hidden;
    background: var(--card-bg);
    box-shadow: 0 8px 24px rgba(0,0,0,0.12);
    position: relative;
    aspect-ratio: 16/11;
  }
  .m1-top-card img {
    width: 100%;
    height: 100%;
    object-fit: cover;
  }
  .m1-top-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(to top, rgba(0,0,0,0.92) 0%, rgba(0,0,0,0.4) 50%, rgba(0,0,0,0) 80%);
    padding: 16px;
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
  }
  .m1-top-title {
    font-size: 1.15rem;
    font-weight: 800;
    color: #ffffff;
    line-height: 1.35;
    margin-bottom: 6px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
  }
  .m1-meta-row {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 11px;
    color: rgba(255,255,255,0.8);
    font-weight: 600;
  }

  /* Feed Estilo App (Tarjetas individuales respiradas) */
  .m1-feed-container {
    padding: 0 16px 40px 16px;
    display: flex;
    flex-direction: column;
    gap: 20px;
  }
  .m1-feed-card {
    background: var(--card-bg);
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 4px 16px rgba(0,0,0,0.06);
    border: 1px solid var(--border);
    transition: transform 0.2s;
  }
  .m1-feed-img {
    width: 100%;
    aspect-ratio: 16/9;
    object-fit: cover;
  }
  .m1-feed-body {
    padding: 16px;
  }
  .m1-feed-tag {
    display: inline-block;
    background: rgba(239, 51, 99, 0.12);
    color: var(--accent-fuchsia);
    font-size: 11px;
    font-weight: 800;
    padding: 3px 10px;
    border-radius: 20px;
    margin-bottom: 8px;
    text-transform: uppercase;
  }
  .m1-feed-title {
    font-size: 1.1rem;
    font-weight: 800;
    color: var(--text);
    line-height: 1.4;
    margin-bottom: 8px;
  }
  .m1-feed-desc {
    font-size: 0.88rem;
    color: var(--muted);
    line-height: 1.5;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
  }
</style>

<!-- BARRA NAVEGACIÓN ENTRE TESTS -->
<div class="test-option-banner">
  📱 <strong>TEST MÓVIL — OPCIÓN 1: App Feed Minimalista</strong>
  <br>
  Ir a: 
  <a href="<?= basePath() ?>/views/test_movil_1.php" style="font-weight:800;">[1. App Feed]</a> | 
  <a href="<?= basePath() ?>/views/test_movil_2.php">[2. Geek Magazine]</a> | 
  <a href="<?= basePath() ?>/views/test_movil_3.php">[3. Trending Grid]</a>
</div>

<!-- 1. HISTORIAS / CATEGORÍAS EN CÍRCULOS GRADIENTE -->
<div class="m1-stories-bar">
  <?php 
  $catsMock = [
    ['name' => 'Todo', 'img' => 'https://images.unsplash.com/photo-1578632767115-351597cf2477?w=150'],
    ['name' => 'Anime', 'img' => 'https://images.unsplash.com/photo-1607604276583-eef5d076aa5f?w=150'],
    ['name' => 'Películas', 'img' => 'https://images.unsplash.com/photo-1489599849927-2ee91cede3ba?w=150'],
    ['name' => 'Gaming', 'img' => 'https://images.unsplash.com/photo-1538481199705-c710c4e965fc?w=150'],
    ['name' => 'Reviews', 'img' => 'https://images.unsplash.com/photo-1511512578047-dfb367046420?w=150']
  ];
  foreach($catsMock as $c): 
  ?>
    <div class="m1-story-item">
      <div class="m1-story-ring">
        <img src="<?= $c['img'] ?>" alt="<?= $c['name'] ?>">
      </div>
      <span class="m1-story-name"><?= $c['name'] ?></span>
    </div>
  <?php endforeach; ?>
</div>

<!-- 2. CARRUSEL TARJETAS SWIPE TOP SEMANAL -->
<div class="px-3 mb-2 d-flex justify-content-between align-items-center">
  <h6 style="font-weight: 800; font-size: 0.95rem; margin: 0; color: var(--text);">
    🔥 Lo Más Visto de la Semana
  </h6>
  <small style="color: var(--accent); font-weight: 700;">Desliza 👉</small>
</div>

<div class="m1-top-carousel">
  <?php foreach($topSemanal as $item): 
    $url = newsUrlFromRow($item);
    $cat = explode(',', $item['categorias'] ?? 'Geek')[0];
  ?>
    <a href="<?= $url ?>" class="m1-top-card text-decoration-none">
      <img src="<?= img([$item['crop3'], $item['crop1']]) ?>" alt="<?= htmlspecialchars($item['titulo']) ?>">
      <div class="m1-top-overlay">
        <span class="m1-feed-tag" style="align-self: flex-start; background: var(--accent-fuchsia); color: #fff; border-radius: 4px; padding: 2px 8px; font-size: 10px;">
          <?= htmlspecialchars($cat) ?>
        </span>
        <h4 class="m1-top-title"><?= htmlspecialchars($item['titulo']) ?></h4>
        <div class="m1-meta-row">
          <span>⏱️ 3 min</span>
          <span>👁️ <?= number_format($item['vistas'] ?? 100) ?></span>
          <span>❤️ <?= number_format($item['likes'] ?? 12) ?></span>
        </div>
      </div>
    </a>
  <?php endforeach; ?>
</div>

<!-- 3. FEED PRINCIPAL (TARJETAS INDIVIDUALES LIMPIAS) -->
<div class="px-3 mt-3 mb-3">
  <h6 style="font-weight: 800; font-size: 1rem; margin: 0; color: var(--text);">
    ⚡ Últimas Noticias
  </h6>
</div>

<div class="m1-feed-container">
  <?php foreach($recientes as $item): 
    $url = newsUrlFromRow($item);
    $cat = explode(',', $item['categorias'] ?? 'Geek')[0];
  ?>
    <a href="<?= $url ?>" class="m1-feed-card text-decoration-none">
      <img src="<?= img([$item['crop3'], $item['crop1']]) ?>" class="m1-feed-img" alt="<?= htmlspecialchars($item['titulo']) ?>">
      <div class="m1-feed-body">
        <span class="m1-feed-tag"><?= htmlspecialchars($cat) ?></span>
        <h3 class="m1-feed-title"><?= htmlspecialchars($item['titulo']) ?></h3>
        <p class="m1-feed-desc"><?= htmlspecialchars($item['descripcion']) ?></p>
        <div style="font-size: 11px; color: var(--muted); font-weight: 600; margin-top: 8px;" class="d-flex justify-content-between align-items-center">
          <span>Por <?= htmlspecialchars($item['nombre_u']) ?></span>
          <span><?= tiempoRelativo($item['fecha']) ?></span>
        </div>
      </div>
    </a>
  <?php endforeach; ?>
</div>

<?php include(__DIR__ . "/../layout/footer.php"); ?>
