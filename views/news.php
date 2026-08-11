<?php
if(session_status() == PHP_SESSION_NONE){
    session_start();
}
$condicionFecha = "AND n.fecha_publicacion <= NOW()";
if (isset($_SESSION['usuario'])) {
    // Los administradores/editores autenticados pueden previsualizar noticias futuras/programadas
    $condicionFecha = "";
}

require_once("./../data/conexion.php");
require_once("./helpers/urlhelper.php");
require_once("./helpers/sidebarhelper.php");
require_once("./helpers/moderacion.php");

// Soportar múltiples formas de acceso a noticias
if(isset($_GET['slug'])){
    $slug = $_GET['slug'];
    // Primero intentar buscar por slug
    $where_clause = "n.slug = ?";
    $param = $slug;
} elseif(isset($_GET['hash'])){
    // Formato anterior: hash codificado en base64
    $id = decodeId($_GET['hash']);
    $where_clause = "n.id = ?";
    $param = $id;
} elseif(isset($_GET['id'])){
    // Formato alternativo: ID numérico directo
    $id = intval($_GET['id']);
    $where_clause = "n.id = ?";
    $param = $id;
} else {
    // Fallback a ID 1 si no hay parámetro
    $where_clause = "n.id = ?";
    $param = 1;
}

// ==============================
// Obtener noticia con autor y categorías
// ==============================
$sql = "
    SELECT n.*, n.slug, u.nombre AS autor_nombre, u.id_u AS autor_id, u.foto_personal AS autor_foto, a.imagen AS autor_avatar,
           GROUP_CONCAT(c.nombre ORDER BY nc.orden ASC SEPARATOR ',') AS categorias
    FROM noticias n
    LEFT JOIN usuarios u ON n.autor = u.id_u
    LEFT JOIN avatares_perfil a ON u.avatar_id = a.id_avatar
    LEFT JOIN noticia_categoria nc ON n.id = nc.noticia_id
    LEFT JOIN categorias c ON nc.categoria_id = c.id_c
    WHERE $where_clause AND n.eliminado_en IS NULL $condicionFecha
    GROUP BY n.id
";
$stmt = $con->prepare($sql);
$stmt->bind_param("s", $param);
$stmt->execute();
$result = $stmt->get_result();
$noticia = $result->fetch_assoc();

// Si no se encontró por slug, intentar decodificar como ID y buscar por ID
if (!$noticia && isset($_GET['slug'])) {
    $decodedId = decodeId($_GET['slug']);
    if ($decodedId > 0) {
        $where_clause = "n.id = ?";
        $param = $decodedId;
        $sql = "
            SELECT n.*, n.slug, u.nombre AS autor_nombre, u.id_u AS autor_id, u.foto_personal AS autor_foto, a.imagen AS autor_avatar,
                   GROUP_CONCAT(c.nombre ORDER BY nc.orden ASC SEPARATOR ',') AS categorias
            FROM noticias n
            LEFT JOIN usuarios u ON n.autor = u.id_u
            LEFT JOIN avatares_perfil a ON u.avatar_id = a.id_avatar
            LEFT JOIN noticia_categoria nc ON n.id = nc.noticia_id
            LEFT JOIN categorias c ON nc.categoria_id = c.id_c
            WHERE $where_clause AND n.eliminado_en IS NULL $condicionFecha
            GROUP BY n.id
        ";
        $stmt = $con->prepare($sql);
        $stmt->bind_param("i", $param);
        $stmt->execute();
        $result = $stmt->get_result();
        $noticia = $result->fetch_assoc();
    }
}

if (!$noticia) die("Noticia no encontrada");

// Configurar variables SEO dinámicas antes de incluir el header
$pageTitle = $noticia['titulo'];
$pageDescription = $noticia['descripcion'];
$domain = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
$canonical = $domain . newsUrl($noticia['slug'] ?? $noticia['id']);
$ogImage = $domain . imageUrl($noticia['crop1'] ?? $noticia['crop2'] ?? $noticia['crop3'] ?? 'img/catink-og.png');

include("./../layout/header.php");
include("./helpers/videoEmbed.php");
include("./helpers/socialEmbed.php");
// Asegurar que $id siempre tenga el ID numérico de la noticia
$id = $noticia['id'];
// Parsear categorías
$cats = !empty($noticia['categorias']) ? explode(',', $noticia['categorias']) : [];
$cats = array_map('trim', $cats);
// ==============================
// NOTICIAS RECOMENDADAS
// ==============================
$recomendadas = [];
if(!empty($cats)){
    $placeholders = implode(',', array_fill(0, count($cats), '?'));
    $sqlRec = "
        SELECT DISTINCT n.id, n.slug, n.titulo, n.descripcion, n.crop1, n.crop2, n.crop3, n.fecha_publicacion
        FROM noticias n
        JOIN noticia_categoria nc ON n.id = nc.noticia_id
        JOIN categorias c ON nc.categoria_id = c.id_c
        WHERE c.nombre IN ($placeholders)
        AND n.id != ?
        AND n.eliminado_en IS NULL
        AND n.fecha_publicacion <= NOW()
        ORDER BY n.fecha_publicacion DESC
        LIMIT 3
    ";
    $stmtRec = $con->prepare($sqlRec);
    $types = str_repeat("s", count($cats)) . "i";
    $params = array_merge($cats, [$id]);
    $stmtRec->bind_param($types, ...$params);
    $stmtRec->execute();
    $recomendadas = $stmtRec->get_result();
}
// ==============================
// NOTICIAS RECIENTES
// ==============================
$stmtRecientes = $con->prepare("
    SELECT id, slug, titulo, descripcion, crop1, crop2, crop3, fecha_publicacion
    FROM noticias
    WHERE eliminado_en IS NULL AND fecha_publicacion <= NOW()
    AND id != ?
    ORDER BY fecha_publicacion DESC
    LIMIT 4
");
$stmtRecientes->bind_param("i", $id);
$stmtRecientes->execute();
$recientes = $stmtRecientes->get_result();
// ==============================
// Últimas y Populares
// ==============================
$stmtUltimas = $con->prepare("
    SELECT id, slug, titulo, crop1, crop2, crop3
    FROM noticias
    WHERE eliminado_en IS NULL AND fecha_publicacion <= NOW()
    ORDER BY fecha_publicacion DESC
    LIMIT 3
");
$stmtUltimas->execute();
$ultimas = $stmtUltimas->get_result();
$stmtPopulares = $con->prepare("
    SELECT id, slug, titulo, crop1, crop2, crop3
    FROM noticias
    WHERE eliminado_en IS NULL AND fecha_publicacion <= NOW()
    ORDER BY vistas DESC, likes DESC
    LIMIT 3
");
$stmtPopulares->execute();
$populares = $stmtPopulares->get_result();
// Publicidad por posición (ver views/helpers/publicidadhelper.php). Cada hueco
// elige su propio anuncio: si el anuncio no tiene posiciones asignadas entra al
// pool random; si las tiene, solo aparece en las suyas. Los laterales SOLO usan
// anuncios cuadrados (tipo 2); inicio/medio/final usan banners largos (tipo 1).
$pubActiva  = !empty($secciones['publicidad']['estado']);

$pubInicio  = $pubActiva ? obtenerPublicidad($con, 'pub_inicio', 1) : null;
$excludeBanners = [];
if ($pubInicio) {
    $excludeBanners[] = (int)$pubInicio['id_pub'];
}

$pubMedio   = $pubActiva ? obtenerPublicidad($con, 'pub_medio',  1, $excludeBanners) : null;
if (empty($pubMedio) && $pubActiva) {
    $pubMedio = obtenerPublicidad($con, 'pub_medio', 1);
}
if ($pubMedio) {
    $excludeBanners[] = (int)$pubMedio['id_pub'];
}

$pubFinal   = $pubActiva ? obtenerPublicidad($con, 'pub_final',  1, $excludeBanners) : null;
if (empty($pubFinal) && $pubActiva) {
    $pubFinal = obtenerPublicidad($con, 'pub_final', 1);
}

$pubLateralTop = $pubActiva ? obtenerPublicidad($con, 'lateral',    2) : null;
$pubLateralBottom = null;
if ($pubActiva && $pubLateralTop) {
    $pubLateralBottom = obtenerPublicidad($con, 'lateral', 2, [$pubLateralTop['id_pub']]);
    if (empty($pubLateralBottom)) {
        $pubLateralBottom = $pubLateralTop;
    }
}
if (!function_exists('adBannerHtml')) {
    function adBannerHtml($pub, $clase = 'ad-strip') {
        if (empty($pub)) return '';
        return '<div class="showcase-box '.$clase.'">'
             . '<a href="'.htmlspecialchars($pub['url']).'" class="promo-link" data-pub="'.(int)$pub['id_pub'].'" target="_blank" rel="noopener noreferrer" data-turbo="false">'
             . '<img src="'.htmlspecialchars(imageUrl($pub['imagen'])).'" alt="" class="promo-media" loading="lazy">'
             . '</a><span class="partner-tag">ADS</span></div>';
    }
}
// ==============================
// COMENTARIOS
// ==============================
$stmtComentarios = $con->prepare("
    SELECT c.*,
           COALESCE(u.nombre, l.nombre) AS nombre,
           COALESCE(u.usuario, l.usuario) AS usuario,
           COALESCE(u.foto_personal, ua.imagen, la.imagen) AS avatar_img,
           IF(c.usuario_id IS NOT NULL, 1, 0) AS es_editor,
           (SELECT COUNT(*) FROM likes_comentarios lc WHERE lc.comentario_id = c.id_comentario) AS total_likes
    FROM comentarios c
    LEFT JOIN lectores l ON c.lector_id = l.id
    LEFT JOIN avatares_perfil la ON l.avatar_id = la.id_avatar
    LEFT JOIN usuarios u ON c.usuario_id = u.id_u
    LEFT JOIN avatares_perfil ua ON u.avatar_id = ua.id_avatar
    WHERE c.noticia_id = ? AND c.estado = 'activo' AND (l.id IS NOT NULL OR u.id_u IS NOT NULL)
    ORDER BY c.fecha_publicacion DESC
");
$stmtComentarios->bind_param("i", $id);
$stmtComentarios->execute();
$comentarios = $stmtComentarios->get_result();

// Organizar en hilos (modelo de un nivel): raíces + respuestas por parent_id
$raices = [];
$respuestas = [];
while ($c = $comentarios->fetch_assoc()) {
    if (!empty($c['parent_id'])) {
        $respuestas[$c['parent_id']][] = $c;
    } else {
        $raices[] = $c;
    }
}
// Respuestas huérfanas (padre eliminado/oculto): promoverlas a primer nivel
$rootIdSet = array_flip(array_column($raices, 'id_comentario'));
foreach ($respuestas as $pid => $lista) {
    if (!isset($rootIdSet[$pid])) {
        foreach ($lista as $huerfano) $raices[] = $huerfano;
        unset($respuestas[$pid]);
    }
}
// Raíces más recientes primero; respuestas dentro del hilo en orden cronológico
usort($raices, fn($a, $b) => strcmp($b['fecha_publicacion'], $a['fecha_publicacion']));
foreach ($respuestas as $pid => $lista) {
    usort($respuestas[$pid], fn($a, $b) => strcmp($a['fecha_publicacion'], $b['fecha_publicacion']));
}
$totalComentarios = count($raices) + array_sum(array_map('count', $respuestas));

// Config de comentarios para esta noticia
$stmtCfgCom = $con->prepare("SELECT permitir_comentarios, moderacion_previa FROM config_comentarios WHERE noticia_id = ?");
$stmtCfgCom->bind_param("i", $id);
$stmtCfgCom->execute();
$cfgCom = $stmtCfgCom->get_result()->fetch_assoc();
$comentariosHabilitados = ($cfgCom === null || $cfgCom['permitir_comentarios'] == 1);

// Likes del lector actual
$misLikes = [];
if (isset($_SESSION['tipo']) && $_SESSION['tipo'] === 'lector') {
    $stmtMisLikes = $con->prepare("SELECT comentario_id FROM likes_comentarios WHERE lector_id = ?");
    $stmtMisLikes->bind_param("i", $_SESSION['id_lector']);
    $stmtMisLikes->execute();
    $resLikes = $stmtMisLikes->get_result();
    while ($lk = $resLikes->fetch_assoc()) {
        $misLikes[$lk['comentario_id']] = true;
    }
}

// Verificar si el usuario actual es el autor de la noticia o es admin
$esAutor = false;
$esAdmin = false;
if (isset($_SESSION['tipo']) && $_SESSION['tipo'] === 'admin' && isset($_SESSION['usuario'])) {
    $esAdmin = true;
    $stmtAutor = $con->prepare("SELECT id_u FROM usuarios WHERE usuario = ?");
    $stmtAutor->bind_param("s", $_SESSION['usuario']);
    $stmtAutor->execute();
    $resAutor = $stmtAutor->get_result()->fetch_assoc();
    $esAutor = ($resAutor && $resAutor['id_u'] == $noticia['autor']);
}

// Consultar si la noticia tiene historial de modificaciones
$stmtLastEdit = $con->prepare("SELECT fecha_edicion FROM historial_ediciones_noticias WHERE noticia_id = ? ORDER BY fecha_edicion DESC LIMIT 1");
$stmtLastEdit->bind_param("i", $id);
$stmtLastEdit->execute();
$lastEdit = $stmtLastEdit->get_result()->fetch_assoc();
?>
<style>
  @media (max-width: 768px) {
    .noticias > .container {
      padding-left: 0 !important;
      padding-right: 0 !important;
    }
    .container > .container-fluid {
      padding-left: 0 !important;
      padding-right: 0 !important;
    }
    .container-noticia {
      padding-left: 16px !important;
      padding-right: 16px !important;
    }
  }
  .back-to-top-btn {
    background: var(--card-bg, #1e1e24);
    color: var(--text, #fff);
    border: 1px solid var(--border, rgba(255,255,255,0.1));
    padding: 10px 24px;
    font-size: 0.9rem;
    font-weight: 700;
    border-radius: 30px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s ease;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
  }
  .back-to-top-btn:hover {
    background: var(--accent, #EF3363);
    color: #fff;
    border-color: var(--accent, #EF3363);
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(239, 51, 99, 0.3);
  }
  .back-to-top-btn i {
    font-size: 1.1rem;
  }
</style>
<style>
.news-rating-block {
  display: flex;
  align-items: center;
  gap: 10px;
  margin: 24px 0 18px;
  padding: 14px 18px;
  background: rgba(245,166,35,.07);
  border: 1px solid rgba(245,166,35,.3);
  border-radius: 10px;
}
.news-rating-label {
  font-size: 13px;
  font-weight: 700;
  color: var(--muted, #888);
  white-space: nowrap;
}
.news-rating-stars {
  display: flex;
  gap: 4px;
  font-size: 22px;
  color: var(--accent, #EF3363);
}
.news-rating-stars .paw-empty {
  color: #ddd;
}
.news-rating-text {
  font-size: 13px;
  color: var(--muted, #888);
  white-space: nowrap;
}
</style>
<div class="reading-progress-container">
    <div id="readingProgressBar" class="reading-progress-bar"></div>
</div>
<div class="noticias">
  <div class="container">
    <div class="container-fluid">
      <div class="row">
        <!-- COLUMNA PRINCIPAL -->
        <div class="col-md-9">
          <div class="container-noticia" id="noticia-top">
            <?php
              $imgSrc = $noticia['crop3'] ?? $noticia['crop2'] ?? $noticia['crop1'] ?? null;
              $img = imageUrl($imgSrc);
            ?>
            <img src="<?= htmlspecialchars($img) ?>" alt="" class="img-titular" style="view-transition-name: article-img-<?= $noticia['id'] ?>;">
            
            <!-- Categorías -->
            <?php foreach ($cats as $cat): ?>
              <a href="<?= categoryUrl($cat) ?>" class="news-tag"><?= htmlspecialchars($cat) ?></a>
            <?php endforeach; ?>
            <h1><?= htmlspecialchars($noticia['titulo']) ?></h1>

            <p class="descripcion"><?= nl2br(htmlspecialchars($noticia['descripcion'])) ?></p>
            <div class="meta-autor">
              <?php
                $autorFoto = $noticia['autor_foto'] ?? $noticia['autor_avatar'] ?? null;
                $autorNombre = $noticia['autor_nombre'] ?? 'Desconocido';
                $autorIniciales = strtoupper(substr($autorNombre, 0, 1));
              ?>
              <?php if($autorFoto): ?>
                <img src="<?= imageUrl($autorFoto) ?>" alt="<?= htmlspecialchars($autorNombre) ?>" class="meta-autor-avatar">
              <?php else: ?>
                <span class="meta-autor-avatar meta-autor-avatar--iniciales"><?= $autorIniciales ?></span>
              <?php endif; ?>
              <span class="meta-autor-info">
                Por <a href="<?= authorUrl($noticia['autor_id'] ?? 0) ?>" class="meta-autor-link"><?= htmlspecialchars($autorNombre) ?></a>
                <span class="meta-autor-fecha"><?= date("d/m/Y · H:i", strtotime($noticia['fecha_publicacion'])) ?></span>
                <?php if (!empty($lastEdit)): ?>
                  <span class="meta-autor-fecha" style="margin-left: 6px; opacity: 0.85;" title="Noticia actualizada el <?= date("d/m/Y H:i", strtotime($lastEdit['fecha_edicion'])) ?>">
                    &bull; <i class="bi bi-pencil-fill" style="font-size: 0.75rem;"></i> Actualizado: <?= date("d/m/Y · H:i", strtotime($lastEdit['fecha_edicion'])) ?>
                  </span>
                <?php endif; ?>
              </span>
            </div>
            <div style="display: flex; gap: 10px; align-items: center; margin-bottom: 20px; flex-wrap: wrap;">
              <button id="likeBtn" class="like-btn" data-id="<?= $id ?>">
                <i class="bi bi-heart-fill" style="color: red;"></i> Like <span id="likeCount"><?= $noticia['likes'] ?></span>
              </button>
              <button id="btnOfflineSave" class="like-btn btn-offline-save" data-id="<?= $id ?>" data-title="<?= htmlspecialchars($noticia['titulo']) ?>" data-slug="<?= htmlspecialchars($noticia['slug'] ?? '') ?>" data-img="<?= htmlspecialchars($img) ?>" data-author="<?= htmlspecialchars($autorNombre) ?>" data-date="<?= date("d/m/Y · H:i", strtotime($noticia['fecha_publicacion'])) ?>" style="background: rgba(255,255,255,0.06); color: var(--text); border: 1px solid var(--border); display: inline-flex; align-items: center; gap: 6px; cursor: pointer; transition: all 0.2s;">
                <i class="bi bi-bookmark-plus"></i> <span id="offlineSaveText">Guardar sin conexión</span>
              </button>
              <?php if ($esAutor || $esAdmin): ?>
                <a href="<?= basePath() ?>/views/editar.php?id=<?= $id ?>" class="like-btn" style="background: var(--accent); color: #fff; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                  <i class="bi bi-pencil-square"></i> Editar
                </a>
              <?php endif; ?>
              <?php if (!empty($_SESSION['superadmin'])): ?>
                <form action="<?= basePath() ?>/controllers/eliminar_noticia.php" method="POST" style="margin:0; display:inline-flex;">
                  <input type="hidden" name="id" value="<?= $id ?>">
                  <input type="hidden" name="from" value="publica">
                  <button type="submit" id="btnEliminarNoticia" class="like-btn" style="background: transparent; color: var(--accent); border: 1px solid var(--accent); display: inline-flex; align-items: center; gap: 6px; cursor: pointer;">
                    <i class="bi bi-trash"></i> Eliminar
                  </button>
                </form>
              <?php endif; ?>
            </div>

            <?php if (($noticia['tipo_publicacion'] ?? '') === 'review'): ?>
              <!-- Bloque del Veredicto / Review Card -->
              <?php
                $scoreVal = floatval($noticia['calificacion'] ?? 0.0);
                if ($scoreVal >= 7.0) {
                    $statusClass = 'status-green';
                    if ($scoreVal >= 9.0) {
                        $label = 'Excelente';
                    } elseif ($scoreVal >= 8.0) {
                        $label = 'Muy Bueno';
                    } else {
                        $label = 'Bueno';
                    }
                } elseif ($scoreVal >= 5.0) {
                    $label = 'Regular';
                    $statusClass = 'status-yellow';
                } else {
                    $label = 'Malo';
                    $statusClass = 'status-red';
                }
              ?>
              <div class="review-verdict-card">
                <div class="review-score-section">
                  <div class="review-score-circle-lg <?= $statusClass ?>">
                    <span class="review-score-num"><?= number_format($scoreVal, 1, '.', '') ?></span>
                    <span class="review-score-max">de 10</span>
                  </div>
                  <div class="review-verdict-label <?= $statusClass ?>-text"><?= $label ?></div>
                </div>

                <div class="review-details-section">
                  <!-- PROS -->
                  <div class="review-list-box">
                    <div class="review-list-title pros-title">
                      <i class="bi bi-plus-circle-fill"></i> Lo que nos gustó
                    </div>
                    <ul class="review-items-list">
                      <?php
                        $prosText = trim($noticia['pros'] ?? '');
                        if (!empty($prosText)):
                          $prosLines = explode("\n", $prosText);
                          foreach ($prosLines as $line):
                            $line = trim($line);
                            if (empty($line)) continue;
                      ?>
                            <li class="review-item-li pro-item">
                              <i class="bi bi-check-circle-fill"></i>
                              <span><?= htmlspecialchars($line) ?></span>
                            </li>
                      <?php
                          endforeach;
                        else:
                      ?>
                          <li class="review-item-li" style="color: var(--muted); font-style: italic;">Sin puntos positivos destacados</li>
                      <?php endif; ?>
                    </ul>
                  </div>

                  <!-- CONTRAS -->
                  <div class="review-list-box">
                    <div class="review-list-title contras-title">
                      <i class="bi bi-dash-circle-fill"></i> Lo que no nos gustó
                    </div>
                    <ul class="review-items-list">
                      <?php
                        $contrasText = trim($noticia['contras'] ?? '');
                        if (!empty($contrasText)):
                          $contrasLines = explode("\n", $contrasText);
                          foreach ($contrasLines as $line):
                            $line = trim($line);
                            if (empty($line)) continue;
                      ?>
                            <li class="review-item-li contra-item">
                              <i class="bi bi-x-circle-fill"></i>
                              <span><?= htmlspecialchars($line) ?></span>
                            </li>
                      <?php
                          endforeach;
                        else:
                      ?>
                          <li class="review-item-li" style="color: var(--muted); font-style: italic;">Sin puntos negativos destacados</li>
                      <?php endif; ?>
                    </ul>
                  </div>
                </div>
              </div>
            <?php endif; ?>
            <!-- BANNER PUBLICITARIO (inicio de la publicación) -->
            <?php echo adBannerHtml($pubInicio); ?>

            <!-- Contenido completo de la noticia -->
            <div class="post-content">
              <?php
                $contenido=$noticia['contenido'];
                $contenido=procesarEmbedsSociales($contenido);
                $contenido=bloquearEmbeds($contenido);
                // Anuncio a la mitad del contenido: se inserta tras el párrafo
                // central (dividiendo por </p> para no romper etiquetas).
                if (!empty($pubMedio)) {
                    $paras = explode('</p>', $contenido);
                    $n = count($paras);
                    if ($n >= 3) {
                        $mid = intdiv($n, 2);
                        $adMedio = adBannerHtml($pubMedio);
                        $out = '';
                        foreach ($paras as $i => $p) {
                            $out .= $p;
                            if ($i < $n - 1) $out .= '</p>';
                            if ($i === $mid) $out .= $adMedio;
                        }
                        echo $out;
                    } else {
                        echo $contenido;
                    }
                } else {
                    echo $contenido;
                }
              ?>
            </div>

            <!-- Botón para volver al inicio de la nota -->
            <div class="back-to-top-container" style="text-align: center; margin: 30px 0 10px;">
                <button onclick="scrollToNoticiaTop()" class="back-to-top-btn">
                    <i class="bi bi-arrow-up-circle-fill"></i> Volver al inicio de la nota
                </button>
            </div>
            <hr>
            <h2 align="center"><i class="bi bi-share-fill"></i> Compartir</h2>
            <div class="share-bar">
                <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode("https://www.catink.com.mx/".newsUrl($noticia['slug'])) ?>" target="_blank" class="share-btn facebook">
                    <i class="bi bi-facebook"></i>
                </a>
                <!--<a href="#" onclick="copyLink()" class="share-btn instagram">
                    <i class="bi bi-instagram"></i>
                </a>-->
                <a href="https://wa.me/?text=<?= urlencode("https://www.catink.com.mx/".newsUrl($noticia['slug'])) ?>" target="_blank" class="share-btn whatsapp">
                    <i class="bi bi-whatsapp"></i>
                </a>
                <a href="https://twitter.com/intent/tweet?text=<?= urlencode("https://www.catink.com.mx/".newsUrl($noticia['slug'])) ?>" target="_blank" class="share-btn twitter">
                    <i class="bi bi-twitter-x"></i>
                </a>
                <!--<a href="https://www.linkedin.com/sharing/share-offsite/?url=<?= urlencode("https://www.catink.com.mx/".newsUrl($noticia['slug'])) ?>" target="_blank" class="share-btn linkedin">
                    <i class="bi bi-linkedin"></i>
                </a>-->
                <a href="#" onclick="shareMessenger()" class="share-btn messenger">
                    <i class="bi bi-messenger"></i>
                </a>
            </div>
            <!-- BANNER PUBLICITARIO (final de la publicación) -->
            <?php echo adBannerHtml($pubFinal); ?>
            <!-- ===================== -->
            <!-- SECCIÓN DE COMENTARIOS -->
            <!-- ===================== -->
            <?php
            $comentariosGlobalesHabilitados = isset($secciones['comentarios']) ? ($secciones['comentarios']['estado'] == 1) : true;
            if ($comentariosGlobalesHabilitados):
              if ($comentariosHabilitados):
            ?>
            <div class="comentarios-section" id="comentarios">
              <h2 class="comentarios-titulo">Comentarios</h2>
              <!-- Formulario -->
              <?php
                $puedeComentarr = isset($_SESSION['tipo']) && ($_SESSION['tipo'] === 'lector' || $_SESSION['tipo'] === 'admin');
                $avatarComentario = null;
                if (isset($_SESSION['tipo']) && $_SESSION['tipo'] === 'lector' && !empty($_SESSION['id_lector'])) {
                    $stmtAv = $con->prepare("SELECT a.imagen FROM lectores l LEFT JOIN avatares_perfil a ON l.avatar_id = a.id_avatar WHERE l.id = ?");
                    $stmtAv->bind_param("i", $_SESSION['id_lector']);
                    $stmtAv->execute();
                    $avRes = $stmtAv->get_result()->fetch_assoc();
                    $avatarComentario = $avRes['imagen'] ?? null;
                } elseif (isset($_SESSION['tipo']) && $_SESSION['tipo'] === 'admin' && isset($_SESSION['usuario'])) {
                    $stmtAv = $con->prepare("SELECT a.imagen FROM usuarios u LEFT JOIN avatares_perfil a ON u.avatar_id = a.id_avatar WHERE u.usuario = ?");
                    $stmtAv->bind_param("s", $_SESSION['usuario']);
                    $stmtAv->execute();
                    $avRes = $stmtAv->get_result()->fetch_assoc();
                    $avatarComentario = $avRes['imagen'] ?? null;
                }
              ?>
              <div class="comentario-form">
                <div class="comentario-form-avatar">
                  <?php if ($avatarComentario): ?>
                    <img src="<?= imageUrl($avatarComentario) ?>" alt="" class="comentario-avatar" loading="lazy" decoding="async">
                  <?php else: ?>
                    <div class="comentario-avatar-placeholder"><i class="bi bi-person"></i></div>
                  <?php endif; ?>
                </div>
                <div class="comentario-form-body">
                  <textarea id="comentarioTexto" placeholder="Comparte tu opinión con nosotros..." maxlength="1000" rows="4" <?= $puedeComentarr ? '' : 'disabled' ?>></textarea>
                  <div class="comentario-form-footer">
                    <span class="comentario-chars"><span id="charCount">0</span>/1000</span>
                    <button type="button" id="btnComentar" class="btn-publicar" <?= $puedeComentarr ? '' : 'disabled' ?>>Publicar</button>
                  </div>
                </div>
              </div>
              <?php if (!$puedeComentarr): ?>
                <p class="comentario-login-msg">Inicia sesión desde el menú superior para dejar un comentario.</p>
              <?php endif; ?>
              <!-- Lista de comentarios -->
              <div class="comentarios-lista" id="comentariosLista">
                <?php if ($totalComentarios === 0): ?>
                  <p class="comentarios-vacio" id="comentariosVacio">Sé el primero en comentar.</p>
                <?php endif; ?>
                <?php
                // Renderiza un comentario (raíz o respuesta). Reutilizada para
                // ambos niveles para no duplicar el marcado.
                if (!function_exists('renderComentarioItem')):
                function renderComentarioItem($com, $con, $misLikes, $puedeResponder, $esRespuesta = false) {
                    $perfilUrl = commentAuthorUrl($com);
                    $yaLiked = isset($misLikes[$com['id_comentario']]);
                    $esMiComentario = false;
                    if (isset($_SESSION['tipo'])) {
                        if ($_SESSION['tipo'] === 'lector' && isset($_SESSION['id_lector']) && $com['lector_id'] == $_SESSION['id_lector']) $esMiComentario = true;
                        if ($_SESSION['tipo'] === 'admin' && $com['usuario_id']) {
                            $stmtMyId = $con->prepare("SELECT id_u FROM usuarios WHERE usuario = ?");
                            $stmtMyId->bind_param("s", $_SESSION['usuario']);
                            $stmtMyId->execute();
                            $myId = $stmtMyId->get_result()->fetch_assoc();
                            if ($myId && $myId['id_u'] == $com['usuario_id']) $esMiComentario = true;
                        }
                    }
                    $autorResp = $com['usuario'] ?? $com['nombre'];
                    ?>
                    <div class="comentario-item<?= $esRespuesta ? ' comentario-respuesta' : '' ?>" data-id="<?= $com['id_comentario'] ?>">
                      <div class="comentario-avatar-col">
                        <?php if ($perfilUrl): ?><a href="<?= $perfilUrl ?>" class="comentario-perfil-link" title="Ver perfil"><?php endif; ?>
                        <?php if (!empty($com['avatar_img'])): ?>
                          <img src="<?= imageUrl($com['avatar_img']) ?>" alt="" class="comentario-avatar" loading="lazy" decoding="async">
                        <?php else: ?>
                          <div class="comentario-avatar-placeholder"><?= strtoupper(mb_substr($com['nombre'], 0, 1)) ?></div>
                        <?php endif; ?>
                        <?php if ($perfilUrl): ?></a><?php endif; ?>
                      </div>
                      <div class="comentario-body">
                        <div class="comentario-header">
                          <?php if ($perfilUrl): ?>
                            <a href="<?= $perfilUrl ?>" class="comentario-autor comentario-perfil-link"><?= htmlspecialchars($com['nombre']) ?></a>
                          <?php else: ?>
                            <strong class="comentario-autor"><?= htmlspecialchars($com['nombre']) ?></strong>
                          <?php endif; ?>
                          <?php if ($com['es_editor']): ?>
                            <span class="badge-editor">Editor</span>
                          <?php endif; ?>
                          <span class="comentario-fecha"><?= date('d M Y, H:i', strtotime($com['fecha_publicacion'])) ?></span>
                        </div>
                        <p class="comentario-texto"><?= nl2br(htmlspecialchars($com['contenido'])) ?></p>
                        <div class="comentario-acciones">
                          <button class="btn-like-com <?= $yaLiked ? 'liked' : '' ?>" data-id="<?= $com['id_comentario'] ?>">
                            <i class="bi <?= $yaLiked ? 'bi-heart-fill' : 'bi-heart' ?>"></i>
                            <span class="like-count"><?= (int)$com['total_likes'] ?></span>
                          </button>
                          <?php if ($puedeResponder): ?>
                            <button class="btn-responder-com" data-id="<?= $com['id_comentario'] ?>" data-respuesta="<?= $esRespuesta ? '1' : '0' ?>" data-autor="<?= htmlspecialchars($autorResp, ENT_QUOTES) ?>"><i class="bi bi-reply"></i> Responder</button>
                          <?php endif; ?>
                          <?php if ($esMiComentario): ?>
                            <button class="btn-editar-com" data-id="<?= $com['id_comentario'] ?>"><i class="bi bi-pencil"></i> Editar</button>
                            <button class="btn-eliminar-com" data-id="<?= $com['id_comentario'] ?>"><i class="bi bi-trash"></i> Eliminar</button>
                          <?php else: ?>
                            <?php if (isset($_SESSION['tipo']) && empty($_SESSION['superadmin'])): ?>
                              <button class="btn-reportar-com" data-id="<?= $com['id_comentario'] ?>"><i class="bi bi-flag"></i> Reportar</button>
                            <?php endif; ?>
                            <?php if (!empty($_SESSION['superadmin'])):
                                $modTipoCom    = $com['usuario_id'] ? 'admin' : 'lector';
                                $modUserIdCom  = (int)($com['usuario_id'] ?: $com['lector_id']);
                                $modNombreCom  = htmlspecialchars($com['nombre'], ENT_QUOTES);
                                $modBaneadoCom = $modUserIdCom > 0 ? estaBaneado(obtenerBaneo($con, $modTipoCom, $modUserIdCom)) : false;
                            ?>
                              <div class="com-kebab">
                                <button class="btn-kebab-com" title="Opciones de moderación" aria-label="Opciones de moderación"><i class="bi bi-three-dots-vertical"></i></button>
                                <div class="com-kebab-menu" hidden>
                                  <button class="btn-eliminar-com btn-mod-eliminar" data-id="<?= $com['id_comentario'] ?>"><i class="bi bi-trash"></i> Eliminar</button>
                                  <?php if ($modUserIdCom > 0): ?>
                                    <?php if ($modBaneadoCom): ?>
                                      <button class="btn-quitar-com" data-tipo="<?= $modTipoCom ?>" data-userid="<?= $modUserIdCom ?>" data-nombre="<?= $modNombreCom ?>"><i class="bi bi-check-circle"></i> Quitar suspensión</button>
                                    <?php else: ?>
                                      <button class="btn-suspender-com" data-tipo="<?= $modTipoCom ?>" data-userid="<?= $modUserIdCom ?>" data-nombre="<?= $modNombreCom ?>"><i class="bi bi-slash-circle"></i> Suspender</button>
                                    <?php endif; ?>
                                  <?php endif; ?>
                                </div>
                              </div>
                            <?php endif; ?>
                          <?php endif; ?>
                        </div>
                      </div>
                    </div>
                    <?php
                }
                endif;
                ?>
                <?php foreach ($raices as $com): ?>
                  <div class="comentario-hilo" data-root="<?= $com['id_comentario'] ?>">
                    <?php renderComentarioItem($com, $con, $misLikes, $puedeComentarr, false); ?>
                    <div class="comentario-respuestas">
                      <?php foreach (($respuestas[$com['id_comentario']] ?? []) as $hijo): ?>
                        <?php renderComentarioItem($hijo, $con, $misLikes, $puedeComentarr, true); ?>
                      <?php endforeach; ?>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
            <?php endif; ?>
            <?php else: ?>
                <!-- Botón de pánico activo: comentarios desactivados globalmente -->
                <div class="comentarios-section" id="comentarios">
                    <h2 class="comentarios-titulo">Comentarios</h2>
                    <div class="alert alert-warning text-center" style="background: rgba(239, 51, 99, 0.05); border: 1px solid rgba(239, 51, 99, 0.2); color: var(--accent); border-radius: 10px; padding: 20px; font-weight: 600; margin-top: 15px;">
                        <i class="bi bi-exclamation-triangle-fill" style="font-size: 1.5rem; display: block; margin-bottom: 8px;"></i>
                        Los comentarios han sido desactivados temporalmente por la administración.
                    </div>
                </div>
            <?php endif; ?>
            <!-- Modal de reporte -->
            <div class="modal-reporte" id="modalReporte" style="display:none;">
              <div class="modal-reporte-content">
                <h3><i class="bi bi-flag"></i> Reportar comentario</h3>
                <select id="reporteMotivo">
                  <option value="">Selecciona un motivo...</option>
                  <option value="Contenido ofensivo">Contenido ofensivo</option>
                  <option value="Spam">Spam</option>
                  <option value="Información falsa">Información falsa</option>
                  <option value="Acoso">Acoso</option>
                  <option value="Otro">Otro</option>
                </select>
                <div class="modal-reporte-btns">
                  <button id="btnEnviarReporte" class="btn-comentar"><i class="bi bi-send"></i> Enviar</button>
                  <button id="btnCerrarReporte" class="btn-cancelar">Cancelar</button>
                </div>
              </div>
            </div>
            
            <!-- Modal Confirmación Eliminar Comentario Custom (CatInk style) -->
            <style>
            .custom-confirm-modal {
              position: fixed;
              top: 0; left: 0; width: 100%; height: 100%;
              background: rgba(0, 0, 0, 0.7);
              backdrop-filter: blur(5px);
              display: flex;
              align-items: center;
              justify-content: center;
              z-index: 10000;
              animation: fadeIn 0.2s ease;
            }
            .custom-confirm-content {
              background: var(--card-bg, #1a1a1a);
              border: 1px solid var(--border, #2d2d2d);
              border-radius: 12px;
              padding: 24px;
              width: 90%;
              max-width: 380px;
              text-align: center;
              box-shadow: 0 10px 30px rgba(0,0,0,0.5);
              animation: scaleUp 0.25s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            }
            .custom-confirm-content h3 {
              margin-top: 0;
              margin-bottom: 12px;
              font-size: 1.25rem;
              font-weight: 700;
              color: var(--accent, #EF3363);
              display: flex;
              align-items: center;
              justify-content: center;
              gap: 8px;
            }
            .custom-confirm-content p {
              color: var(--text, #fff);
              font-size: 0.92rem;
              margin-bottom: 24px;
              line-height: 1.45;
              opacity: 0.85;
            }
            .custom-confirm-buttons {
              display: flex;
              gap: 12px;
              justify-content: center;
            }
            .custom-confirm-buttons button {
              padding: 8px 18px;
              font-size: 0.88rem;
              font-weight: 600;
              border-radius: 6px;
              cursor: pointer;
              transition: all 0.2s ease;
              border: none;
            }
            .custom-confirm-buttons #btnConfirmDeleteNo {
              background: var(--border, #2d2d2d);
              color: var(--text, #fff);
            }
            .custom-confirm-buttons #btnConfirmDeleteNo:hover {
              background: var(--muted, #444);
            }
            .custom-confirm-buttons #btnConfirmDeleteYes {
              background: var(--accent, #EF3363);
              color: #fff;
              box-shadow: 0 4px 12px rgba(239, 51, 99, 0.25);
            }
            .custom-confirm-buttons #btnConfirmDeleteYes:hover {
              background: #bd2148;
              box-shadow: 0 6px 16px rgba(239, 51, 99, 0.35);
              transform: translateY(-1px);
            }
            </style>
            <div id="confirmDeleteModal" class="custom-confirm-modal" style="display:none;">
              <div class="custom-confirm-content">
                <h3><i class="bi bi-trash-fill"></i> ¿Eliminar comentario?</h3>
                <p>Esta acción no se puede deshacer. El comentario será borrado permanentemente de la noticia.</p>
                <div class="custom-confirm-buttons">
                  <button id="btnConfirmDeleteNo">Cancelar</button>
                  <button id="btnConfirmDeleteYes">Eliminar</button>
                </div>
              </div>
            </div>
            <?php if (!empty($_SESSION['superadmin'])): ?>
            <!-- Modal de suspensión (moderación) -->
            <div class="modal-reporte" id="modalSuspender" style="display:none;">
              <div class="modal-reporte-content">
                <h3><i class="bi bi-slash-circle"></i> Suspender usuario</h3>
                <p class="modal-suspender-user" id="suspenderUserNombre"></p>
                <label class="mod-label">Duración de la suspensión</label>
                <select id="suspenderDuracion">
                  <?php foreach (duracionesBaneo() as $key => $d): ?>
                    <option value="<?= $key ?>"><?= htmlspecialchars($d['label']) ?></option>
                  <?php endforeach; ?>
                </select>
                <input type="text" id="suspenderMotivo" maxlength="255" placeholder="Motivo (opcional)">
                <div class="modal-reporte-btns">
                  <button id="btnEnviarSuspension" class="btn-comentar"><i class="bi bi-slash-circle"></i> Suspender</button>
                  <button id="btnCerrarSuspension" class="btn-cancelar">Cancelar</button>
                </div>
              </div>
            </div>
            <style>
              .com-kebab { position: relative; display: inline-block; }
              .btn-kebab-com {
                background: none; border: none; color: var(--muted); cursor: pointer;
                padding: 4px 9px; border-radius: 6px; font-size: 1.05rem; line-height: 1;
              }
              .btn-kebab-com:hover { background: rgba(239,51,99,0.10); color: var(--accent); }
              .com-kebab-menu {
                position: absolute; right: 0; top: calc(100% + 4px); z-index: 30;
                background: var(--card-bg); border: 1px solid var(--border);
                border-radius: 10px; padding: 6px; min-width: 165px;
                flex-direction: column; gap: 2px;
                box-shadow: 0 8px 24px rgba(0,0,0,0.35);
                display: flex;
              }
              .com-kebab-menu[hidden] { display: none; }
              .com-kebab-menu button {
                background: none; border: none; text-align: left; width: 100%;
                padding: 8px 10px; border-radius: 6px; cursor: pointer;
                color: var(--text); font-size: 0.88rem;
                display: flex; align-items: center; gap: 8px;
              }
              .com-kebab-menu button:hover { background: rgba(239,51,99,0.12); color: var(--accent); }
              .com-kebab-menu .btn-suspender-com:hover { background: rgba(239,51,51,0.14); color: #ef3333; }
              .com-kebab-menu .btn-quitar-com:hover { background: rgba(40,167,69,0.14); color: #28a745; }
              .modal-suspender-user { font-weight: 600; margin: 0 0 6px; color: var(--accent); }
              #modalSuspender select, #modalSuspender input {
                width: 100%; margin-bottom: 10px; padding: 9px 12px;
                border-radius: 8px; border: 1px solid var(--border);
                background: var(--bg); color: var(--text);
              }
            </style>
            <?php endif; ?>
          </div>
        </div>
        <!-- SIDEBAR -->
        <div class="col-md-3">
          <?php renderSidebarNewsWidget($ultimas, $populares, $pubLateralTop ?? null, ['publicidad' => ['estado' => $pubActiva ?? false]]); ?>
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
      <div class="row">
        <div class="container">
          <h3>Recomendados para ti</h3>
          <br>
          <div class="row">
            <?php while($r = $recomendadas->fetch_assoc()): 
                $imgSrc = $r['crop3'] ?? $r['crop2'] ?? $r['crop1'] ?? null;
                $img = imageUrl($imgSrc ?? 'img/placeholder.svg');
            ?>
              <div class="col">
                  <div class="card h-100" data-url="<?= newsUrlFromRow($r) ?>">
                      <img src="<?= $img ?>" class="card-img-top" loading="lazy" decoding="async">
                      <div class="card-body">
                          <a href="<?= newsUrlFromRow($r) ?>" class="news-link title-limit-2">
                              <?= htmlspecialchars($r['titulo']) ?>
                          </a>
                          <small class="desc-limit-3">
                            <?= htmlspecialchars($r['descripcion']) ?>
                          </small>
                          <br>
                          <small class="text-muted">
                              <?= date('d M Y', strtotime($r['fecha_publicacion'])) ?>
                          </small>
                      </div>
                  </div>
              </div>
            <?php endwhile; ?>
          </div>
        </div>
      </div>
      <br>
      <div class="row">
        <div class="container">
          <h3>Noticias recientes</h3>
          <br>
          <div class="row">
            <?php while($r = $recientes->fetch_assoc()):
                $imgSrc = $r['crop3'] ?? $r['crop2'] ?? $r['crop1'] ?? null;
                $img = imageUrl($imgSrc ?? 'img/placeholder.svg');
            ?>
              <div class="col">
                  <div class="card h-100" data-url="<?= newsUrlFromRow($r) ?>">
                      <img src="<?= $img ?>" class="card-img-top" loading="lazy" decoding="async">
                      <div class="card-body">
                          <a href="<?= newsUrlFromRow($r) ?>" class="news-link title-limit-2">
                              <?= htmlspecialchars($r['titulo']) ?>
                          </a>
                          <small class="desc-limit-3">
                            <?= htmlspecialchars($r['descripcion']) ?>
                          </small>
                          <br>
                          <small class="text-muted">
                              <?= date('d M Y', strtotime($r['fecha_publicacion'])) ?>
                          </small>
                      </div>
                  </div>
              </div>
            <?php endwhile; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<!-- Scripts de interacción -->
<script>
  // Toast notifications
  // persist = true → es un aviso de moderación: se muestra en una card central
  // (estilo tarjeta de sanción) que el usuario cierra con el botón "Entiendo".
  function showToast(msg, type = '', persist = false) {
    if (persist) { showModAviso(msg); return; }
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
    setTimeout(() => toast.remove(), 3000);
  }

  // Card central de aviso de moderación (estilo tarjeta de sanción).
  // Se queda en pantalla hasta que el usuario pulsa "Entiendo".
  function showModAviso(msg) {
    let overlay = document.getElementById('modAvisoOverlay');
    if (!overlay) {
      overlay = document.createElement('div');
      overlay.id = 'modAvisoOverlay';
      overlay.className = 'mod-aviso-overlay';
      overlay.innerHTML = `
        <div class="mod-aviso-card" role="alertdialog" aria-modal="true" aria-labelledby="modAvisoTitulo" aria-describedby="modAvisoTexto">
          <div class="mod-aviso-top"></div>
          <h2 class="mod-aviso-titulo" id="modAvisoTitulo"></h2>
          <div class="mod-aviso-sep"></div>
          <p class="mod-aviso-texto" id="modAvisoTexto"></p>
          <p class="mod-aviso-nota">El respeto y la buena convivencia son la base de nuestra comunidad. Buscamos que CATINK sea un espacio sano donde todas y todos puedan compartir y opinar con libertad. ¡Gracias por ayudarnos a cuidarla!</p>
          <button type="button" class="mod-aviso-btn">Entiendo</button>
        </div>`;
      document.body.appendChild(overlay);
      overlay.querySelector('.mod-aviso-btn').addEventListener('click', () => {
        overlay.classList.remove('is-open');
      });
    }
    const esSuspension = /suspend/i.test(msg);
    overlay.querySelector('.mod-aviso-titulo').textContent = esSuspension ? 'Cuenta suspendida' : 'Cuidemos la comunidad';
    overlay.querySelector('.mod-aviso-texto').textContent = msg;
    overlay.classList.add('is-open');
    overlay.querySelector('.mod-aviso-btn').focus();
  }
  // Sumar vistas
  fetch("<?= basePath() ?>/controllers/sumarvistas.php", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: "noticia_id=<?= $id ?>"
  })
  .then(res => res.json())
  .then(data => console.log("Vistas actualizadas:", data))
  .catch(err => console.error(err));
  // Enviar tiempo de lectura
  let inicio = Date.now();
  let enviado = false;
  function enviarTiempo() {
    if (enviado) return;
    enviado = true;
    let tiempo = Math.floor((Date.now() - inicio) / 1000);
    navigator.sendBeacon("<?= basePath() ?>/controllers/guardartiempo.php",
      new URLSearchParams({ noticia_id: "<?= $id ?>", tiempo: tiempo })
    );
  }
  window.addEventListener("beforeunload", enviarTiempo);
  document.addEventListener("visibilitychange", () => {
    if (document.visibilityState === "hidden") enviarTiempo();
  });
  // Botón de Like
  document.getElementById('likeBtn').addEventListener('click', async function() {
    const id = this.dataset.id;
    const res = await fetch('<?= basePath() ?>/controllers/like.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: `noticia_id=${id}`
    });
    const data = await res.json();
    if (data.ok) {
      const count = document.getElementById('likeCount');
      count.textContent = parseInt(count.textContent) + 1;
      this.disabled = true;
    } else {
      showToast(data.msg, 'error');
      this.disabled = true;
    }
  });

  // Botón Guardar sin Conexión (Offline Support)
  function syncOfflineButtonState() {
    const btnOffline = document.getElementById('btnOfflineSave');
    const offlineText = document.getElementById('offlineSaveText');

    if (!btnOffline || !window.CatInkOffline) return;
    const articleId = btnOffline.dataset.id;
    if (!articleId) return;

    // Verificar si ya está guardado offline
    CatInkOffline.isArticleSaved(articleId).then(isSaved => {
      if (isSaved) {
        btnOffline.classList.add('active');
        btnOffline.style.background = 'var(--accent, #EF3363)';
        btnOffline.style.color = '#ffffff';
        btnOffline.style.borderColor = 'var(--accent, #EF3363)';
        const icon = btnOffline.querySelector('i');
        if (icon) icon.className = 'bi bi-bookmark-check-fill';
        if (offlineText) offlineText.textContent = 'Guardado offline';
      } else {
        btnOffline.classList.remove('active');
        btnOffline.style.background = 'rgba(255,255,255,0.06)';
        btnOffline.style.color = 'var(--text)';
        btnOffline.style.borderColor = 'var(--border)';
        const icon = btnOffline.querySelector('i');
        if (icon) icon.className = 'bi bi-bookmark-plus';
        if (offlineText) offlineText.textContent = 'Guardar sin conexión';
      }
    });
  }

  // Delegación global de clics para el botón Guardar sin conexión
  if (!window._newsOfflineDelegated) {
    window._newsOfflineDelegated = true;

    document.addEventListener('click', async function(e) {
      const btnOffline = e.target.closest('#btnOfflineSave, .btn-offline-save');
      if (!btnOffline || !window.CatInkOffline) return;
      e.preventDefault();

      const offlineText = btnOffline.querySelector('#offlineSaveText') || document.getElementById('offlineSaveText');
      const articleId = btnOffline.dataset.id;
      if (!articleId) return;

      const isSaved = await CatInkOffline.isArticleSaved(articleId);

      if (isSaved) {
        // Eliminar de lecturas offline
        await CatInkOffline.removeArticle(articleId);
        btnOffline.classList.remove('active');
        btnOffline.style.background = 'rgba(255,255,255,0.06)';
        btnOffline.style.color = 'var(--text)';
        btnOffline.style.borderColor = 'var(--border)';
        const icon = btnOffline.querySelector('i');
        if (icon) icon.className = 'bi bi-bookmark-plus';
        if (offlineText) offlineText.textContent = 'Guardar sin conexión';
        CatInkOffline.showStatusToast('Noticia eliminada de tus lecturas offline', false);
      } else {
        // Extraer contenido para offline
        const container = document.querySelector('.container-noticia');
        let htmlBody = '';
        if (container) {
          const clone = container.cloneNode(true);
          // Remover elementos no deseados del clon guardado
          clone.querySelectorAll('#likeBtn, #btnOfflineSave, .btn-offline-save, .back-to-top-container, form, .news-comments-section').forEach(el => el.remove());
          htmlBody = clone.innerHTML;
        }

        await CatInkOffline.saveArticle({
          id: articleId,
          slug: btnOffline.dataset.slug || '',
          titulo: btnOffline.dataset.title || document.querySelector('h1')?.textContent || '',
          descripcion: document.querySelector('.descripcion')?.textContent || '',
          contenido: htmlBody,
          cover_image: btnOffline.dataset.img || '',
          autor_nombre: btnOffline.dataset.author || 'CatInk',
          categorias: [<?= json_encode($cats[0] ?? 'Noticia') ?>],
          fecha_publicacion: btnOffline.dataset.date || ''
        });

        btnOffline.classList.add('active');
        btnOffline.style.background = 'var(--accent, #EF3363)';
        btnOffline.style.color = '#ffffff';
        btnOffline.style.borderColor = 'var(--accent, #EF3363)';
        const icon = btnOffline.querySelector('i');
        if (icon) icon.className = 'bi bi-bookmark-check-fill';
        if (offlineText) offlineText.textContent = 'Guardado offline';
        CatInkOffline.showStatusToast('¡Noticia guardada para leer sin conexión!', false);
      }
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', syncOfflineButtonState);
  } else {
    syncOfflineButtonState();
  }
  document.addEventListener('turbo:load', syncOfflineButtonState);
  document.addEventListener('turbo:render', syncOfflineButtonState);
</script>
<script>
  // ============================
  // SISTEMA DE COMENTARIOS
  // ============================
  const noticiaId = <?= $id ?>;
  const comBase = '<?= basePath() ?>/controllers/';

  // Intercambia el botón del menú ⋮ entre "Suspender" y "Quitar suspensión"
  // para todos los comentarios del mismo usuario, sin recargar la página.
  function actualizarBotonMod(tipo, userId, baneado, nombre) {
    document.querySelectorAll('.com-kebab-menu .btn-suspender-com, .com-kebab-menu .btn-quitar-com').forEach(b => {
      if (b.dataset.tipo !== tipo || b.dataset.userid !== String(userId)) return;
      const nom = (nombre ?? b.dataset.nombre ?? '').replace(/"/g, '&quot;');
      b.outerHTML = baneado
        ? `<button class="btn-quitar-com" data-tipo="${tipo}" data-userid="${userId}" data-nombre="${nom}"><i class="bi bi-check-circle"></i> Quitar suspensión</button>`
        : `<button class="btn-suspender-com" data-tipo="${tipo}" data-userid="${userId}" data-nombre="${nom}"><i class="bi bi-slash-circle"></i> Suspender</button>`;
    });
  }

  // Contador de caracteres
  const textarea = document.getElementById('comentarioTexto');
  const charCount = document.getElementById('charCount');
  if (textarea && charCount) {
    textarea.addEventListener('input', () => {
      charCount.textContent = textarea.value.length;
    });
  }

  // Construye el marcado de un comentario (raíz o respuesta). Reutilizada por
  // el alta de comentarios y de respuestas. Como solo un usuario con sesión
  // puede llegar aquí, siempre incluye "Responder" y las acciones del autor.
  function buildComentarioHtml(c, esRespuesta = false) {
    const perfilUrl = c.usuario_id
      ? `<?= basePath() ?>/autor/${c.usuario_id}`
      : (c.lector_id ? `<?= basePath() ?>/usuario/${c.lector_id}` : null);
    const innerAvatar = c.avatar_img
      ? `<img src="<?= basePath() ?>/serve-image.php?file=${encodeURIComponent(c.avatar_img)}" alt="" class="comentario-avatar" loading="lazy" decoding="async">`
      : `<div class="comentario-avatar-placeholder">${c.nombre.charAt(0).toUpperCase()}</div>`;
    const avatarHtml = perfilUrl
      ? `<a href="${perfilUrl}" class="comentario-perfil-link" title="Ver perfil">${innerAvatar}</a>`
      : innerAvatar;
    const autorHtml = perfilUrl
      ? `<a href="${perfilUrl}" class="comentario-autor comentario-perfil-link">${c.nombre}</a>`
      : `<strong class="comentario-autor">${c.nombre}</strong>`;
    const fecha = new Date(c.fecha_publicacion).toLocaleDateString('es-MX', {day:'2-digit',month:'short',year:'numeric',hour:'2-digit',minute:'2-digit'});
    const badgeHtml = c.es_editor == 1 ? '<span class="badge-editor">Editor</span>' : '';
    const autorAttr = (c.usuario || c.nombre || '').replace(/"/g, '&quot;');
    return `
      <div class="comentario-item${esRespuesta ? ' comentario-respuesta' : ''}" data-id="${c.id_comentario}">
        <div class="comentario-avatar-col">${avatarHtml}</div>
        <div class="comentario-body">
          <div class="comentario-header">
            ${autorHtml}
            ${badgeHtml}
            <span class="comentario-fecha">${fecha}</span>
          </div>
          <p class="comentario-texto">${c.contenido.replace(/\n/g, '<br>')}</p>
          <div class="comentario-acciones">
            <button class="btn-like-com" data-id="${c.id_comentario}"><i class="bi bi-heart"></i> <span class="like-count">0</span></button>
            <button class="btn-responder-com" data-id="${c.id_comentario}" data-respuesta="${esRespuesta ? '1' : '0'}" data-autor="${autorAttr}"><i class="bi bi-reply"></i> Responder</button>
            <button class="btn-editar-com" data-id="${c.id_comentario}"><i class="bi bi-pencil"></i> Editar</button>
            <button class="btn-eliminar-com" data-id="${c.id_comentario}"><i class="bi bi-trash"></i> Eliminar</button>
          </div>
        </div>
      </div>`;
  }

  // Suma 1 al contador de comentarios de la cabecera de la sección.
  function incrementarContadorComentarios() {
    const countEl = document.querySelector('.comentarios-count');
    if (countEl) {
      const num = parseInt(countEl.textContent.replace(/\D/g, '')) + 1;
      countEl.textContent = `(${num})`;
    }
  }

  // CREAR COMENTARIO
  const btnComentar = document.getElementById('btnComentar');
  if (btnComentar) {
    btnComentar.addEventListener('click', async () => {
      const contenido = textarea.value.trim();
      if (!contenido) return;
      btnComentar.disabled = true;

      // Generar e insertar elemento esqueleto temporal
      const lista = document.getElementById('comentariosLista');
      const vacio = document.getElementById('comentariosVacio');
      const skeletonId = 'skeleton_' + Date.now();
      const skeletonHtml = `
        <div class="comentario-hilo comentario-skeleton-temp" id="${skeletonId}">
          <div class="comentario-item">
            <div class="comentario-avatar-col">
              <div class="skeleton-shimmer skeleton-shimmer-avatar"></div>
            </div>
            <div class="comentario-body">
              <div class="comentario-header">
                <div class="skeleton-shimmer skeleton-shimmer-name"></div>
              </div>
              <div class="comentario-texto">
                <div class="skeleton-shimmer skeleton-shimmer-line"></div>
                <div class="skeleton-shimmer skeleton-shimmer-line shorter"></div>
              </div>
            </div>
          </div>
        </div>
      `;
      if (vacio) vacio.style.display = 'none';
      lista.insertAdjacentHTML('afterbegin', skeletonHtml);
      const skeletonEl = document.getElementById(skeletonId);

      try {
        const res = await fetch(comBase + 'comentarios.php', {
          method: 'POST',
          headers: {'Content-Type': 'application/x-www-form-urlencoded'},
          body: `action=crear&noticia_id=${noticiaId}&contenido=${encodeURIComponent(contenido)}`
        });
        const data = await res.json();
        if (data.ok && data.comentario) {
          if (vacio) vacio.remove();
          const hilo = `<div class="comentario-hilo" data-root="${data.comentario.id_comentario}">${buildComentarioHtml(data.comentario, false)}<div class="comentario-respuestas"></div></div>`;
          if (skeletonEl) {
            skeletonEl.outerHTML = hilo;
          } else {
            lista.insertAdjacentHTML('afterbegin', hilo);
          }
          textarea.value = '';
          charCount.textContent = '0';
          incrementarContadorComentarios();
        } else {
          if (skeletonEl) skeletonEl.remove();
          if (vacio) vacio.style.display = 'block';
          showToast(data.msg || 'Error al enviar el comentario.', 'error', data.persist);
        }
      } catch (e) {
        console.error(e);
        if (skeletonEl) skeletonEl.remove();
        if (vacio) vacio.style.display = 'block';
        showToast('Error de red al procesar el comentario.', 'error');
      }
      btnComentar.disabled = false;
    });
  }

  // DELEGACIÓN DE EVENTOS para likes, editar, eliminar, reportar
  document.addEventListener('click', async (e) => {
    const btn = e.target.closest('button');
    if (!btn) return;

    // KEBAB (menú de moderación) — abrir/cerrar
    if (btn.classList.contains('btn-kebab-com')) {
      const menu = btn.nextElementSibling;
      const abierto = menu && !menu.hidden;
      document.querySelectorAll('.com-kebab-menu').forEach(m => m.hidden = true);
      if (menu) menu.hidden = abierto;
      return;
    }

    // SUSPENDER — abrir modal
    if (btn.classList.contains('btn-suspender-com')) {
      const modal = document.getElementById('modalSuspender');
      modal.style.display = 'flex';
      modal.dataset.tipo = btn.dataset.tipo;
      modal.dataset.userid = btn.dataset.userid;
      document.getElementById('suspenderUserNombre').textContent = btn.dataset.nombre || '';
      document.getElementById('suspenderMotivo').value = '';
      document.querySelectorAll('.com-kebab-menu').forEach(m => m.hidden = true);
      return;
    }

    // ENVIAR SUSPENSIÓN
    if (btn.id === 'btnEnviarSuspension') {
      const modal = document.getElementById('modalSuspender');
      const dur = document.getElementById('suspenderDuracion');
      const durTxt = dur.options[dur.selectedIndex].text;
      if (!confirm(`¿Suspender a este usuario por: ${durTxt}?`)) return;
      btn.disabled = true;
      try {
        const res = await fetch(comBase + 'moderar_usuario.php', {
          method: 'POST',
          headers: {'Content-Type': 'application/x-www-form-urlencoded'},
          body: `action=ban&tipo=${modal.dataset.tipo}&user_id=${modal.dataset.userid}&duracion=${encodeURIComponent(dur.value)}&motivo=${encodeURIComponent(document.getElementById('suspenderMotivo').value)}`
        });
        const data = await res.json();
        showToast(data.msg, data.ok ? 'success' : 'error');
        if (data.ok) {
          modal.style.display = 'none';
          actualizarBotonMod(modal.dataset.tipo, modal.dataset.userid, true);
        }
      } catch (err) { showToast('Error de red.', 'error'); }
      btn.disabled = false;
      return;
    }

    // QUITAR SUSPENSIÓN
    if (btn.classList.contains('btn-quitar-com')) {
      if (!confirm('¿Quitar la suspensión a este usuario?')) return;
      const tipo = btn.dataset.tipo, userId = btn.dataset.userid, nombre = btn.dataset.nombre || '';
      btn.disabled = true;
      try {
        const res = await fetch(comBase + 'moderar_usuario.php', {
          method: 'POST',
          headers: {'Content-Type': 'application/x-www-form-urlencoded'},
          body: `action=unban&tipo=${tipo}&user_id=${userId}`
        });
        const data = await res.json();
        showToast(data.msg, data.ok ? 'success' : 'error');
        if (data.ok) actualizarBotonMod(tipo, userId, false, nombre);
      } catch (err) { showToast('Error de red.', 'error'); }
      document.querySelectorAll('.com-kebab-menu').forEach(m => m.hidden = true);
      return;
    }

    // CERRAR MODAL SUSPENSIÓN
    if (btn.id === 'btnCerrarSuspension') {
      document.getElementById('modalSuspender').style.display = 'none';
      return;
    }

    // LIKE
    if (btn.classList.contains('btn-like-com')) {
      const cid = btn.dataset.id;
      try {
        const res = await fetch(comBase + 'like_comentario.php', {
          method: 'POST',
          headers: {'Content-Type': 'application/x-www-form-urlencoded'},
          body: `comentario_id=${cid}`
        });
        const data = await res.json();
        if (data.ok) {
          btn.querySelector('.like-count').textContent = data.total;
          const icon = btn.querySelector('i');
          if (data.liked) {
            btn.classList.add('liked');
            icon.className = 'bi bi-heart-fill';
          } else {
            btn.classList.remove('liked');
            icon.className = 'bi bi-heart';
          }
        } else {
          showToast(data.msg, 'error', data.persist);
        }
      } catch (e) { console.error(e); }
    }

    // ELIMINAR
    if (btn.classList.contains('btn-eliminar-com')) {
      const cid = btn.dataset.id;
      const modal = document.getElementById('confirmDeleteModal');
      if (modal) {
        modal.style.display = 'flex';
        
        // Remove existing event listeners to avoid double-triggers
        const confirmYes = document.getElementById('btnConfirmDeleteYes');
        const confirmNo = document.getElementById('btnConfirmDeleteNo');
        
        // Clone buttons to clear all listeners
        const newYes = confirmYes.cloneNode(true);
        const newNo = confirmNo.cloneNode(true);
        confirmYes.parentNode.replaceChild(newYes, confirmYes);
        confirmNo.parentNode.replaceChild(newNo, confirmNo);
        
        newNo.addEventListener('click', () => {
          modal.style.display = 'none';
        });
        
        newYes.addEventListener('click', async () => {
          modal.style.display = 'none';
          try {
            const res = await fetch(comBase + 'comentarios.php', {
              method: 'POST',
              headers: {'Content-Type': 'application/x-www-form-urlencoded'},
              body: `action=eliminar&comentario_id=${cid}`
            });
            const data = await res.json();
            if (data.ok) {
              const item = document.querySelector(`.comentario-item[data-id="${cid}"]`);
              if (item) {
                let removedCount = 1;
                const hilo = item.closest('.comentario-hilo');
                if (hilo && hilo.getAttribute('data-root') == cid) {
                  removedCount = hilo.querySelectorAll('.comentario-item').length;
                  hilo.remove();
                } else {
                  item.remove();
                }
                const countEl = document.querySelector('.comentarios-count');
                if (countEl) {
                  const num = Math.max(0, parseInt(countEl.textContent.replace(/\D/g, '')) - removedCount);
                  countEl.textContent = `(${num})`;
                }
              }
            }
            showToast(data.msg, data.ok ? 'success' : 'error');
          } catch (e) { console.error(e); }
        });
      }
    }

    // EDITAR
    if (btn.classList.contains('btn-editar-com')) {
      const cid = btn.dataset.id;
      const item = document.querySelector(`.comentario-item[data-id="${cid}"]`);
      const textoEl = item.querySelector('.comentario-texto');
      const textoActual = textoEl.innerText;
      textoEl.innerHTML = `<textarea class="edit-textarea" maxlength="1000">${textoActual}</textarea>
        <div class="comentario-form-footer">
          <button class="btn-comentar btn-guardar-edit" data-id="${cid}"><i class="bi bi-check"></i> Guardar</button>
          <button class="btn-cancelar btn-cancelar-edit">Cancelar</button>
        </div>`;
    }

    // GUARDAR EDICIÓN
    if (btn.classList.contains('btn-guardar-edit')) {
      const cid = btn.dataset.id;
      const item = document.querySelector(`.comentario-item[data-id="${cid}"]`);
      const editArea = item.querySelector('.edit-textarea');
      const contenido = editArea.value.trim();
      if (!contenido) return;
      try {
        const res = await fetch(comBase + 'comentarios.php', {
          method: 'POST',
          headers: {'Content-Type': 'application/x-www-form-urlencoded'},
          body: `action=editar&comentario_id=${cid}&contenido=${encodeURIComponent(contenido)}`
        });
        const data = await res.json();
        if (data.ok) {
          item.querySelector('.comentario-texto').innerHTML = data.contenido.replace(/\n/g, '<br>');
        } else {
          showToast(data.msg, 'error', data.persist);
        }
      } catch (e) { console.error(e); }
    }

    // CANCELAR EDICIÓN
    if (btn.classList.contains('btn-cancelar-edit')) {
      const item = btn.closest('.comentario-item');
      const editArea = item.querySelector('.edit-textarea');
      const textoOriginal = editArea.value;
      item.querySelector('.comentario-texto').innerHTML = textoOriginal.replace(/\n/g, '<br>');
    }

    // RESPONDER - abrir formulario inline
    if (btn.classList.contains('btn-responder-com')) {
      const cid = btn.dataset.id;
      const esResp = btn.dataset.respuesta === '1';
      const item = btn.closest('.comentario-item');
      if (!item) return;
      // Un solo formulario de respuesta abierto a la vez
      document.querySelectorAll('.comentario-reply-form').forEach(f => f.remove());
      // Si se responde a una respuesta, prellenar la mención para dar contexto
      const mention = (esResp && btn.dataset.autor) ? `@${btn.dataset.autor} ` : '';
      const form = document.createElement('div');
      form.className = 'comentario-reply-form';
      form.dataset.parent = cid;
      form.innerHTML = `
        <textarea class="reply-textarea" maxlength="1000" placeholder="Escribe una respuesta..."></textarea>
        <div class="comentario-form-footer">
          <button class="btn-comentar btn-enviar-reply" data-parent="${cid}"><i class="bi bi-reply"></i> Responder</button>
          <button class="btn-cancelar btn-cancelar-reply">Cancelar</button>
        </div>`;
      item.querySelector('.comentario-body').appendChild(form);
      const ta = form.querySelector('.reply-textarea');
      ta.value = mention;
      ta.focus();
      ta.setSelectionRange(ta.value.length, ta.value.length);
      return;
    }

    // CANCELAR RESPUESTA
    if (btn.classList.contains('btn-cancelar-reply')) {
      const form = btn.closest('.comentario-reply-form');
      if (form) form.remove();
      return;
    }

    // ENVIAR RESPUESTA
    if (btn.classList.contains('btn-enviar-reply')) {
      const form = btn.closest('.comentario-reply-form');
      const parentId = btn.dataset.parent;
      const ta = form.querySelector('.reply-textarea');
      const contenido = ta.value.trim();
      if (!contenido) return;
      btn.disabled = true;

      // Generar e insertar elemento esqueleto temporal de respuesta
      const hilo = form.closest('.comentario-hilo');
      const cont = hilo ? hilo.querySelector('.comentario-respuestas') : null;
      if (!cont) return;
      
      const skeletonId = 'skeleton_' + Date.now();
      const skeletonHtml = `
        <div class="comentario-skeleton-temp" id="${skeletonId}">
          <div class="comentario-item comentario-respuesta">
            <div class="comentario-avatar-col">
              <div class="skeleton-shimmer skeleton-shimmer-avatar"></div>
            </div>
            <div class="comentario-body">
              <div class="comentario-header">
                <div class="skeleton-shimmer skeleton-shimmer-name"></div>
              </div>
              <div class="comentario-texto">
                <div class="skeleton-shimmer skeleton-shimmer-line"></div>
                <div class="skeleton-shimmer skeleton-shimmer-line shorter"></div>
              </div>
            </div>
          </div>
        </div>
      `;
      cont.insertAdjacentHTML('beforeend', skeletonHtml);
      const skeletonEl = document.getElementById(skeletonId);
      form.style.display = 'none';

      try {
        const res = await fetch(comBase + 'comentarios.php', {
          method: 'POST',
          headers: {'Content-Type': 'application/x-www-form-urlencoded'},
          body: `action=crear&noticia_id=${noticiaId}&parent_id=${parentId}&contenido=${encodeURIComponent(contenido)}`
        });
        const data = await res.json();
        if (data.ok && data.comentario) {
          if (skeletonEl) {
            skeletonEl.outerHTML = buildComentarioHtml(data.comentario, true);
          } else {
            cont.insertAdjacentHTML('beforeend', buildComentarioHtml(data.comentario, true));
          }
          form.remove();
          incrementarContadorComentarios();
        } else {
          if (skeletonEl) skeletonEl.remove();
          form.style.display = 'block';
          showToast(data.msg || 'Error al enviar la respuesta.', 'error', data.persist);
          btn.disabled = false;
        }
      } catch (e) {
        console.error(e);
        if (skeletonEl) skeletonEl.remove();
        form.style.display = 'block';
        showToast('Error de red al procesar la respuesta.', 'error');
        btn.disabled = false;
      }
      return;
    }

    // REPORTAR - abrir modal
    if (btn.classList.contains('btn-reportar-com')) {
      const modal = document.getElementById('modalReporte');
      modal.style.display = 'flex';
      modal.dataset.comentarioId = btn.dataset.id;
      document.getElementById('reporteMotivo').value = '';
    }

    // ENVIAR REPORTE
    if (btn.id === 'btnEnviarReporte') {
      const modal = document.getElementById('modalReporte');
      const motivo = document.getElementById('reporteMotivo').value;
      const cid = modal.dataset.comentarioId;
      if (!motivo) { showToast('Selecciona un motivo.', 'error'); return; }
      try {
        const res = await fetch(comBase + 'reportar_comentario.php', {
          method: 'POST',
          headers: {'Content-Type': 'application/x-www-form-urlencoded'},
          body: `comentario_id=${cid}&motivo=${encodeURIComponent(motivo)}`
        });
        const data = await res.json();
        showToast(data.msg, data.ok ? 'success' : 'error', data.persist);
        modal.style.display = 'none';
      } catch (e) { console.error(e); }
    }

    // CERRAR MODAL REPORTE
    if (btn.id === 'btnCerrarReporte') {
      document.getElementById('modalReporte').style.display = 'none';
    }
  });

  // Cerrar el menú de moderación al hacer clic fuera de él
  document.addEventListener('click', (e) => {
    if (!e.target.closest('.com-kebab')) {
      document.querySelectorAll('.com-kebab-menu').forEach(m => m.hidden = true);
    }
  });
</script>
<script>
  function scrollToNoticiaTop() {
    const target = document.getElementById('noticia-top');
    if (target) {
      target.scrollIntoView({ behavior: 'smooth', block: 'start' });
    } else {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }
  }
  function copyLink(){
    const link = "https://www.catink.com.mx/<?= newsUrl($noticia['slug']) ?>";
    navigator.clipboard.writeText(link);
    window.open("https://www.instagram.com/direct/inbox/", "_blank");
  }
  function shareMessenger() {
    const url = "https://www.catink.com.mx/<?= newsUrl($noticia['slug']) ?>";
    const encoded = encodeURIComponent(url);
    const isMobile = /Android|iPhone|iPad|iPod/i.test(navigator.userAgent);
    if(isMobile){
        // abre messenger en móvil
        window.open("fb-messenger://share/?link=" + encoded, "_blank");
    }else{
        // abre diálogo web en PC
        window.open(
            "https://www.facebook.com/dialog/send?link=" + encoded + "&app_id=TU_APP_ID&redirect_uri=" + encoded,
            "_blank"
        );
    }
}
</script>
<?php include("./../layout/footer.php"); ?>