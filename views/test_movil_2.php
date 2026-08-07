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
$topSemanal = array_slice($noticias, 1, 5);
$recientes = array_slice($noticias, 6, 12);
?>

<!-- ESTILOS EXCLUSIVOS OPCIÓN 2: GEEK MAGAZINE COMPACTA -->
<style>
  .test-option-banner {
    background: linear-gradient(135deg, #FF8A00 0%, #EF3363 100%);
    color: white;
    text-align: center;
    padding: 10px 15px;
    font-weight: 700;
    font-size: 13px;
  }
  .test-option-banner a { color: #fff; text-decoration: underline; margin: 0 4px; }

  /* Hero Full Bleed */
  .m2-hero {
    position: relative;
    width: 100%;
    aspect-ratio: 16/11;
    overflow: hidden;
  }
  .m2-hero img {
    width: 100%;
    height: 100%;
    object-fit: cover;
  }
  .m2-hero-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(to top, rgba(15, 23, 42, 0.98) 0%, rgba(15, 23, 42, 0.4) 50%, rgba(0,0,0,0) 80%);
    padding: 20px 16px 16px 16px;
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
  }
  .m2-hero-badge {
    background: var(--accent-fuchsia);
    color: #fff;
    font-size: 10px;
    font-weight: 900;
    padding: 4px 10px;
    border-radius: 4px;
    letter-spacing: 1px;
    align-self: flex-start;
    margin-bottom: 8px;
    text-transform: uppercase;
  }
  .m2-hero-title {
    font-size: 1.35rem;
    font-weight: 900;
    color: #fff;
    line-height: 1.3;
    margin-bottom: 8px;
  }

  /* Ranking Lista 01 - 05 */
  .m2-ranking-box {
    background: var(--card-bg);
    border-radius: 16px;
    padding: 16px;
    margin: 16px;
    border: 1px solid var(--border);
    box-shadow: 0 4px 16px rgba(0,0,0,0.05);
  }
  .m2-ranking-item {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 12px 0;
    border-bottom: 1px solid var(--border);
    text-decoration: none;
  }
  .m2-ranking-item:last-child { border-bottom: none; }
  
  .m2-num {
    font-size: 1.6rem;
    font-weight: 900;
    color: var(--accent-fuchsia);
    min-width: 32px;
    text-align: center;
    font-style: italic;
  }
  .m2-thumb {
    width: 68px;
    height: 68px;
    border-radius: 10px;
    object-fit: cover;
    flex-shrink: 0;
  }
  .m2-ranking-title {
    font-size: 0.95rem;
    font-weight: 800;
    color: var(--text);
    line-height: 1.35;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
  }

  /* Feed Escaneo Rápido (Thumbnail a la izquierda + texto a la derecha) */
  .m2-list-container {
    padding: 0 16px 40px 16px;
    display: flex;
    flex-direction: column;
    gap: 14px;
  }
  .m2-compact-card {
    display: flex;
    gap: 14px;
    background: var(--card-bg);
    padding: 12px;
    border-radius: 14px;
    border: 1px solid var(--border);
    text-decoration: none;
  }
  .m2-compact-img {
    width: 95px;
    height: 95px;
    border-radius: 10px;
    object-fit: cover;
    flex-shrink: 0;
  }
  .m2-compact-content {
    display: flex;
    flex-direction: column;
    justify-content: center;
  }
  .m2-compact-tag {
    font-size: 10px;
    font-weight: 800;
    color: var(--accent-fuchsia);
    text-transform: uppercase;
    margin-bottom: 4px;
  }
  .m2-compact-title {
    font-size: 0.96rem;
    font-weight: 800;
    color: var(--text);
    line-height: 1.35;
    margin-bottom: 6px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
  }
</style>

<!-- NAVEGACIÓN -->
<div class="test-option-banner">
  🎮 <strong>TEST MÓVIL — OPCIÓN 2: Geek Magazine (Escaneo Rápido)</strong>
  <br>
  Ir a: 
  <a href="<?= basePath() ?>/views/test_movil_1.php">[1. App Feed]</a> | 
  <a href="<?= basePath() ?>/views/test_movil_2.php" style="font-weight:800;">[2. Geek Magazine]</a> | 
  <a href="<?= basePath() ?>/views/test_movil_3.php">[3. Trending Grid]</a>
</div>

<!-- 1. HERO MAGAZINE FULL BLEED -->
<?php if ($hero): 
  $heroUrl = newsUrlFromRow($hero);
  $cat = explode(',', $hero['categorias'] ?? 'Geek')[0];
?>
<a href="<?= $heroUrl ?>" class="m2-hero text-decoration-none">
  <img src="<?= img([$hero['crop3'], $hero['crop1']]) ?>" alt="<?= htmlspecialchars($hero['titulo']) ?>">
  <div class="m2-hero-overlay">
    <span class="m2-hero-badge"><?= htmlspecialchars($cat) ?></span>
    <h1 class="m2-hero-title"><?= htmlspecialchars($hero['titulo']) ?></h1>
    <div style="font-size: 11px; color: rgba(255,255,255,0.75); font-weight: 600;">
      <span>Por <?= htmlspecialchars($hero['nombre_u']) ?></span> • <span><?= tiempoRelativo($hero['fecha']) ?></span>
    </div>
  </div>
</a>
<?php endif; ?>

<!-- 2. RANKING DE LO MÁS LEÍDO (LISTA 01 - 05) -->
<div class="m2-ranking-box">
  <div class="d-flex justify-content-between align-items-center mb-2">
    <h6 style="font-weight: 900; font-size: 0.95rem; margin: 0; color: var(--text); text-transform: uppercase;">
      🔥 Ranking Semanal
    </h6>
    <span class="badge bg-danger" style="font-size: 10px;">TOP 5</span>
  </div>

  <?php foreach($topSemanal as $idx => $item): 
    $url = newsUrlFromRow($item);
  ?>
    <a href="<?= $url ?>" class="m2-ranking-item">
      <span class="m2-num">0<?= $idx + 1 ?></span>
      <img src="<?= img([$item['crop3'], $item['crop1']]) ?>" class="m2-thumb" alt="">
      <div style="flex: 1;">
        <h4 class="m2-ranking-title"><?= htmlspecialchars($item['titulo']) ?></h4>
        <small style="font-size: 11px; color: var(--muted); font-weight: 600;">
          👁️ <?= number_format($item['vistas'] ?? 100) ?> lecturas
        </small>
      </div>
    </a>
  <?php endforeach; ?>
</div>

<!-- 3. FEED DE ESCANEO RÁPIDO (LISTA COMPACTA) -->
<div class="px-3 mt-4 mb-2">
  <h6 style="font-weight: 900; font-size: 1rem; margin: 0; color: var(--text); text-transform: uppercase;">
    ⚡ Lo Último en CatInk
  </h6>
</div>

<div class="m2-list-container">
  <?php foreach($recientes as $item): 
    $url = newsUrlFromRow($item);
    $cat = explode(',', $item['categorias'] ?? 'Noticias')[0];
  ?>
    <a href="<?= $url ?>" class="m2-compact-card">
      <img src="<?= img([$item['crop3'], $item['crop1']]) ?>" class="m2-compact-img" alt="">
      <div class="m2-compact-content">
        <span class="m2-compact-tag"><?= htmlspecialchars($cat) ?></span>
        <h3 class="m2-compact-title"><?= htmlspecialchars($item['titulo']) ?></h3>
        <small style="font-size: 11px; color: var(--muted); font-weight: 600;">
          <?= tiempoRelativo($item['fecha']) ?>
        </small>
      </div>
    </a>
  <?php endforeach; ?>
</div>

<?php include(__DIR__ . "/../layout/footer.php"); ?>
