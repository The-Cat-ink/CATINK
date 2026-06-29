<?php
include("./../layout/header.php");
require_once("./../data/conexion.php");
require_once("./helpers/urlhelper.php");
require_once("./helpers/helper.php");
require_once("./helpers/moderacion.php");

// ==============================
// Obtener ID del editor
// ==============================
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: " . basePath() . "/");
    exit;
}

// ==============================
// Obtener datos del editor
// ==============================
$stmt = $con->prepare("
    SELECT id_u, nombre, usuario, biografia, foto_personal, link_twitter, link_instagram, registro,
           perm_categorias, perm_noticias, perm_publicidad, perm_suscripciones, perm_usuarios, perm_correos, perm_videos
    FROM usuarios
    WHERE id_u = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$editor = $stmt->get_result()->fetch_assoc();

if (!$editor) {
    echo '<div class="container" style="text-align:center; padding:80px 20px;"><h1>Editor no encontrado</h1><p style="color:var(--muted);">El perfil que buscas no existe.</p><a href="' . basePath() . '/" class="btn btn-accent" style="margin-top:20px;">Volver al inicio</a></div>';
    include("./../layout/footer.php");
    exit;
}

// ==============================
// Obtener artículos del editor
// ==============================
$stmtNews = $con->prepare("
    SELECT n.id, n.slug, n.titulo, n.descripcion, n.crop1, n.crop3, n.fecha_publicacion,
           GROUP_CONCAT(c.nombre ORDER BY nc.orden ASC SEPARATOR ',') AS categorias
    FROM noticias n
    LEFT JOIN noticia_categoria nc ON n.id = nc.noticia_id
    LEFT JOIN categorias c ON nc.categoria_id = c.id_c
    WHERE n.autor = ? AND n.fecha_publicacion <= NOW()
    GROUP BY n.id
    ORDER BY n.fecha_publicacion DESC
    LIMIT 20
");
$stmtNews->bind_param("i", $id);
$stmtNews->execute();
$articulos = $stmtNews->get_result()->fetch_all(MYSQLI_ASSOC);

// ==============================
// Contadores
// ==============================
$stmtCount = $con->prepare("SELECT COUNT(*) as total FROM noticias WHERE autor = ? AND fecha_publicacion <= NOW()");
$stmtCount->bind_param("i", $id);
$stmtCount->execute();
$totalArticulos = $stmtCount->get_result()->fetch_assoc()['total'];

$fechaRegistro = date('d M Y', strtotime($editor['registro']));

// Foto con fallback
$fotoEditor = !empty($editor['foto_personal'])
    ? imageUrl($editor['foto_personal'])
    : null;

// Iniciales para fallback
$palabras = explode(' ', $editor['nombre']);
$iniciales = strtoupper(substr($palabras[0], 0, 1) . (isset($palabras[1]) ? substr($palabras[1], 0, 1) : ''));

// Verificar si es el editor actual
$esEditorActual = false;
if (isset($_SESSION['tipo']) && $_SESSION['tipo'] === 'admin' && isset($_SESSION['usuario'])) {
    $stmtCheck = $con->prepare("SELECT id_u FROM usuarios WHERE usuario = ?");
    $stmtCheck->bind_param("s", $_SESSION['usuario']);
    $stmtCheck->execute();
    $resCheck = $stmtCheck->get_result()->fetch_assoc();
    $esEditorActual = ($resCheck && $resCheck['id_u'] == $id);
}
?>

<div class="container" style="max-width: 900px; margin: 0 auto; padding: 30px 16px;">

    <!-- PERFIL DEL EDITOR -->
    <div class="autor-header">
        <div class="autor-foto-wrap">
            <?php if ($fotoEditor): ?>
                <img src="<?= $fotoEditor ?>" alt="<?= htmlspecialchars($editor['nombre']) ?>" class="autor-foto">
            <?php else: ?>
                <div class="autor-foto-fallback"><?= $iniciales ?></div>
            <?php endif; ?>
        </div>
        <div class="autor-info">
            <h1 class="autor-nombre"><?= htmlspecialchars($editor['nombre']) ?></h1>
            <p class="autor-username">@<?= htmlspecialchars($editor['usuario']) ?></p>
            <div class="autor-stats">
                <span><strong><?= $totalArticulos ?></strong> artículos</span>
                <span>Miembro desde <?= $fechaRegistro ?></span>
            </div>
            <?php if (!empty($editor['biografia'])): ?>
                <p class="autor-bio"><?= nl2br(htmlspecialchars($editor['biografia'])) ?></p>
            <?php endif; ?>
            <div class="autor-social">
                <?php if (!empty($editor['link_twitter'])): ?>
                    <a href="<?= htmlspecialchars($editor['link_twitter']) ?>" target="_blank" rel="noopener noreferrer" class="autor-social-link autor-social-twitter">
                        <i class="bi bi-twitter-x"></i> Twitter / X
                    </a>
                <?php endif; ?>
                <?php if (!empty($editor['link_instagram'])): ?>
                    <a href="<?= htmlspecialchars($editor['link_instagram']) ?>" target="_blank" rel="noopener noreferrer" class="autor-social-link autor-social-instagram">
                        <i class="bi bi-instagram"></i> Instagram
                    </a>
                <?php endif; ?>
                <?php if ($esEditorActual): ?>
                    <a href="<?= basePath() ?>/perfil" class="autor-social-link autor-social-edit" style="background: var(--accent); color: #fff; border-radius: 6px; padding: 8px 16px; font-weight: 600;">
                        <i class="bi bi-pencil-square"></i> Editar Perfil
                    </a>
                <?php endif; ?>
            </div>
            <?php
                // Panel de moderación (solo superadmin, nunca sobre uno mismo ni sobre otro superadmin)
                if (!$esEditorActual && !esSuperAdmin($editor)) {
                    $modTipo = 'admin';
                    $modUserId = $editor['id_u'];
                    $modBanRow = obtenerBaneo($con, 'admin', $editor['id_u']) ?: [];
                    include(__DIR__ . '/helpers/mod_panel.php');
                }
            ?>
        </div>
    </div>

    <!-- ARTÍCULOS DEL EDITOR -->
    <div class="autor-articles">
        <h2 style="margin-bottom: 20px;"><i class="bi bi-newspaper"></i> Artículos de <?= htmlspecialchars($editor['nombre']) ?></h2>
        <?php if (empty($articulos)): ?>
            <p style="color: var(--muted); text-align: center; padding: 40px 0;">Este editor aún no tiene artículos publicados.</p>
        <?php else: ?>
            <div class="autor-articles-grid">
                <?php foreach ($articulos as $art): ?>
                    <?php
                        $imgArt = !empty($art['crop3']) ? $art['crop3'] : (!empty($art['crop1']) ? $art['crop1'] : 'img/placeholder.svg');
                        $cats = !empty($art['categorias']) ? array_map('trim', explode(',', $art['categorias'])) : [];
                    ?>
                    <a href="<?= newsUrlFromRow($art) ?>" class="autor-article-card">
                        <img src="<?= imageUrl($imgArt) ?>" alt="" class="autor-article-img" loading="lazy" decoding="async">
                        <div class="autor-article-body">
                            <div class="autor-article-tags">
                                <?php foreach (array_slice($cats, 0, 2) as $cat): ?>
                                    <span class="tag-news"><?= htmlspecialchars($cat) ?></span>
                                <?php endforeach; ?>
                            </div>
                            <h3 class="autor-article-title"><?= htmlspecialchars($art['titulo']) ?></h3>
                            <p class="autor-article-desc"><?= htmlspecialchars($art['descripcion']) ?></p>
                            <span class="autor-article-date"><?= date('d M Y', strtotime($art['fecha_publicacion'])) ?></span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include("./../layout/footer.php"); ?>
