<?php
include("./../layout/header.php");
require_once("./../data/conexion.php");
include("./helpers/videoEmbed.php");
include("./helpers/socialEmbed.php");
require_once("./helpers/urlhelper.php");
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
    SELECT n.*, n.slug, u.nombre AS autor_nombre, u.id_u AS autor_id, u.foto_personal AS autor_foto,
           GROUP_CONCAT(c.nombre ORDER BY nc.orden ASC SEPARATOR ',') AS categorias
    FROM noticias n
    LEFT JOIN usuarios u ON n.autor = u.id_u
    LEFT JOIN noticia_categoria nc ON n.id = nc.noticia_id
    LEFT JOIN categorias c ON nc.categoria_id = c.id_c
    WHERE $where_clause AND n.fecha_publicacion <= NOW()
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
            SELECT n.*, n.slug, u.nombre AS autor_nombre, u.id_u AS autor_id, u.foto_personal AS autor_foto,
                   GROUP_CONCAT(c.nombre ORDER BY nc.orden ASC SEPARATOR ',') AS categorias
            FROM noticias n
            LEFT JOIN usuarios u ON n.autor = u.id_u
            LEFT JOIN noticia_categoria nc ON n.id = nc.noticia_id
            LEFT JOIN categorias c ON nc.categoria_id = c.id_c
            WHERE $where_clause AND n.fecha_publicacion <= NOW()
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
    WHERE fecha_publicacion <= NOW()
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
    WHERE fecha_publicacion <= NOW()
    ORDER BY fecha_publicacion DESC
    LIMIT 3
");
$stmtUltimas->execute();
$ultimas = $stmtUltimas->get_result();
$stmtPopulares = $con->prepare("
    SELECT id, slug, titulo, crop1, crop2, crop3
    FROM noticias
    ORDER BY vistas DESC, likes DESC
    LIMIT 3
");
$stmtPopulares->execute();
$populares = $stmtPopulares->get_result();
//Obtener banner publicidad
$stmt = $con->prepare("SELECT * FROM publicidad WHERE activo = 1 AND tipo = 1 ORDER BY RAND() LIMIT 1");
$stmt->execute();
$publicidad = $stmt->get_result()->fetch_assoc();
//Obtener cuadro publicitario
$stmt = $con->prepare("SELECT * FROM publicidad WHERE activo = 1 AND tipo = 2 ORDER BY RAND() LIMIT 1");
$stmt->execute();
$publicidadCuadro = $stmt->get_result()->fetch_assoc();
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
    WHERE c.noticia_id = ? AND c.estado = 'activo'
    ORDER BY c.fecha_publicacion DESC
");
$stmtComentarios->bind_param("i", $id);
$stmtComentarios->execute();
$comentarios = $stmtComentarios->get_result();
$totalComentarios = $comentarios->num_rows;

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
            <img src="<?= htmlspecialchars($img) ?>" alt="" class="img-titular">
            
            <!-- Categorías -->
            <?php foreach ($cats as $cat): ?>
              <a href="<?= categoryUrl($cat) ?>" class="news-tag"><?= htmlspecialchars($cat) ?></a>
            <?php endforeach; ?>
            <h1><?= htmlspecialchars($noticia['titulo']) ?></h1>

            <p class="descripcion"><?= nl2br(htmlspecialchars($noticia['descripcion'])) ?></p>
            <div class="meta-autor">
              <?php
                $autorFoto = $noticia['autor_foto'] ?? null;
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
              </span>
            </div>
            <div style="display: flex; gap: 10px; align-items: center; margin-bottom: 20px;">
              <button id="likeBtn" class="like-btn" data-id="<?= $id ?>">
                <i class="bi bi-heart-fill" style="color: red;"></i> Like <span id="likeCount"><?= $noticia['likes'] ?></span>
              </button>
              <?php if ($esAutor || $esAdmin): ?>
                <a href="<?= basePath() ?>/views/editar.php?id=<?= $id ?>" class="like-btn" style="background: var(--accent); color: #fff; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                  <i class="bi bi-pencil-square"></i> Editar
                </a>
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
            <!-- Contenido completo de la noticia -->
            <div class="post-content">
              <?php
                $contenido=$noticia['contenido'];
                $contenido=procesarEmbedsSociales($contenido);
                $contenido=bloquearEmbeds($contenido);
                echo $contenido
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
            <?php if ($secciones['publicidad']['estado'] == 1) : ?>
              <div class="ad-container">
                <a href="<?= $publicidad['url'] ?>" class="banner-button" data-pub="<?= $publicidad['id_pub'] ?>">
                  <img src="<?= imageUrl($publicidad['imagen']) ?>" alt="" class="banner" loading="lazy">
                </a>
                <span class="ads-label">ADS</span>
              </div>
            <?php endif; ?>
            <!-- ===================== -->
            <!-- SECCIÓN DE COMENTARIOS -->
            <!-- ===================== -->
            <?php if ($comentariosHabilitados): ?>
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
                <?php while ($com = $comentarios->fetch_assoc()): ?>
                  <?php $perfilUrl = commentAuthorUrl($com); ?>
                  <div class="comentario-item" data-id="<?= $com['id_comentario'] ?>">
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
                        <?php
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
                        ?>
                        <button class="btn-like-com <?= $yaLiked ? 'liked' : '' ?>" data-id="<?= $com['id_comentario'] ?>">
                          <i class="bi <?= $yaLiked ? 'bi-heart-fill' : 'bi-heart' ?>"></i>
                          <span class="like-count"><?= (int)$com['total_likes'] ?></span>
                        </button>
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
                <?php endwhile; ?>
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
          <div class="sidebar-wrapper">
            <div class="card sidebar-card">
              <?php if($secciones['publicidad']['estado'] == 1) : ?>
                <div class="ad-container">
                  <a href="<?= $publicidadCuadro['url'] ?>" class="banner-button" data-pub="<?= $publicidadCuadro['id_pub'] ?>">
                    <img src="<?= imageUrl($publicidadCuadro['imagen']) ?>" class="banner-card-img-top" loading="lazy">
                  </a>
                  <span class="ads-label">ADS</span>
                </div>
              <?php endif; ?>
              <div class="card-body">
                <h3>
                  <a href="<?= recientesUrl() ?>" style="text-decoration: none; color: inherit;">
                    <i class="bi bi-alarm"></i> Lo más nuevo
                  </a>
                </h3>
                <br>
                <ul class="list-group list-group-flush mb-3">
                  <?php while ($row = $ultimas->fetch_assoc()): ?>
                    <div class="cardSpecial row row-no-gap">
                        <div class="col-md-4">
                            <img src="<?= htmlspecialchars(imageUrl($row['crop3'] ?? $row['crop2'] ?? $row['crop1'])) ?>" class="imgCard card-img-left-rounded" loading="lazy">
                        </div>
                        <div class="col-md-8">
                          <div class="card-body">
                            <a href="<?= newsUrlFromRow($row) ?>" class="linkCard news-link title-limit-2"><?= htmlspecialchars($row['titulo']) ?></a>
                          </div>
                        </div>
                    </div>
                    <br>
                  <?php endwhile; ?>
                </ul>
                <h3>
                  <a href="<?= popularUrl() ?>" style="text-decoration: none; color: inherit;">
                    Lo más popular
                  </a>
                </h3>
                <br>
                <ul class="list-group list-group-flush">
                  <?php while ($row = $populares->fetch_assoc()): ?>
                    <div class="cardSpecial row row-no-gap">
                        <div class="col-md-4">
                            <img src="<?= htmlspecialchars(imageUrl($row['crop3'] ?? $row['crop2'] ?? $row['crop1'])) ?>" class="imgCard card-img-left-rounded" loading="lazy">
                        </div>
                        <div class="col-md-8">
                          <div class="card-body">
                            <a href="<?= newsUrlFromRow($row) ?>" class="linkCard news-link title-limit-2"><?= htmlspecialchars($row['titulo']) ?></a>
                          </div>
                        </div>
                    </div>
                    <br>
                  <?php endwhile; ?>
                </ul>
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
  function showToast(msg, type = '') {
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

  // CREAR COMENTARIO
  const btnComentar = document.getElementById('btnComentar');
  if (btnComentar) {
    btnComentar.addEventListener('click', async () => {
      const contenido = textarea.value.trim();
      if (!contenido) return;
      btnComentar.disabled = true;
      try {
        const res = await fetch(comBase + 'comentarios.php', {
          method: 'POST',
          headers: {'Content-Type': 'application/x-www-form-urlencoded'},
          body: `action=crear&noticia_id=${noticiaId}&contenido=${encodeURIComponent(contenido)}`
        });
        const data = await res.json();
        if (data.ok && data.comentario) {
          const c = data.comentario;
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
          const html = `
            <div class="comentario-item" data-id="${c.id_comentario}">
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
                  <button class="btn-editar-com" data-id="${c.id_comentario}"><i class="bi bi-pencil"></i> Editar</button>
                  <button class="btn-eliminar-com" data-id="${c.id_comentario}"><i class="bi bi-trash"></i> Eliminar</button>
                </div>
              </div>
            </div>`;
          const lista = document.getElementById('comentariosLista');
          const vacio = document.getElementById('comentariosVacio');
          if (vacio) vacio.remove();
          lista.insertAdjacentHTML('afterbegin', html);
          textarea.value = '';
          charCount.textContent = '0';
          // Actualizar contador
          const countEl = document.querySelector('.comentarios-count');
          if (countEl) {
            const num = parseInt(countEl.textContent.replace(/\D/g, '')) + 1;
            countEl.textContent = `(${num})`;
          }
        }
      } catch (e) { console.error(e); }
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
          showToast(data.msg, 'error');
        }
      } catch (e) { console.error(e); }
    }

    // ELIMINAR
    if (btn.classList.contains('btn-eliminar-com')) {
      if (!confirm('¿Eliminar este comentario?')) return;
      const cid = btn.dataset.id;
      try {
        const res = await fetch(comBase + 'comentarios.php', {
          method: 'POST',
          headers: {'Content-Type': 'application/x-www-form-urlencoded'},
          body: `action=eliminar&comentario_id=${cid}`
        });
        const data = await res.json();
        if (data.ok) {
          const item = document.querySelector(`.comentario-item[data-id="${cid}"]`);
          if (item) item.remove();
          const countEl = document.querySelector('.comentarios-count');
          if (countEl) {
            const num = Math.max(0, parseInt(countEl.textContent.replace(/\D/g, '')) - 1);
            countEl.textContent = `(${num})`;
          }
        }
        showToast(data.msg, data.ok ? 'success' : 'error');
      } catch (e) { console.error(e); }
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
          showToast(data.msg, 'error');
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
        showToast(data.msg, data.ok ? 'success' : 'error');
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