<?php
include("./../layout/header.php");
require_once("./../data/conexion.php");
require_once("./helpers/urlhelper.php");
require_once("./helpers/moderacion.php");

// ==============================
// Obtener ID del lector
// ==============================
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: " . basePath() . "/");
    exit;
}

// ==============================
// Datos del lector
// ==============================
$stmt = $con->prepare("
    SELECT l.id, l.nombre, l.usuario, l.entidad, l.creado, av.imagen AS avatar_img
    FROM lectores l
    LEFT JOIN avatares_perfil av ON l.avatar_id = av.id_avatar
    WHERE l.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$lector = $stmt->get_result()->fetch_assoc();

if (!$lector) {
    echo '<div class="container" style="text-align:center; padding:80px 20px;"><h1>Usuario no encontrado</h1><p style="color:var(--muted);">El perfil que buscas no existe.</p><a href="' . basePath() . '/" class="btn btn-accent" style="margin-top:20px;">Volver al inicio</a></div>';
    include("./../layout/footer.php");
    exit;
}

// ==============================
// Comentarios públicos del lector
// ==============================
$stmtCom = $con->prepare("
    SELECT c.id_comentario, c.contenido, c.fecha_publicacion,
           n.id AS noticia_id, n.slug AS noticia_slug, n.titulo AS noticia_titulo,
           (SELECT COUNT(*) FROM likes_comentarios lc WHERE lc.comentario_id = c.id_comentario) AS total_likes
    FROM comentarios c
    INNER JOIN noticias n ON c.noticia_id = n.id
    WHERE c.lector_id = ? AND c.estado = 'activo' AND n.fecha_publicacion <= NOW()
    ORDER BY c.fecha_publicacion DESC
    LIMIT 30
");
$stmtCom->bind_param("i", $id);
$stmtCom->execute();
$comentarios = $stmtCom->get_result()->fetch_all(MYSQLI_ASSOC);

// Total de comentarios activos
$stmtCount = $con->prepare("SELECT COUNT(*) AS total FROM comentarios WHERE lector_id = ? AND estado = 'activo'");
$stmtCount->bind_param("i", $id);
$stmtCount->execute();
$totalComentarios = $stmtCount->get_result()->fetch_assoc()['total'];

$fechaRegistro = date('d M Y', strtotime($lector['creado']));

// Foto con fallback a iniciales
$fotoLector = !empty($lector['avatar_img']) ? imageUrl($lector['avatar_img']) : null;
$palabras = explode(' ', trim($lector['nombre']));
$iniciales = strtoupper(substr($palabras[0], 0, 1) . (isset($palabras[1]) ? substr($palabras[1], 0, 1) : ''));

// Nombre de la entidad federativa
$entidades = [
    'AGU'=>'Aguascalientes','BCN'=>'Baja California','BCS'=>'Baja California Sur','CAM'=>'Campeche',
    'CHP'=>'Chiapas','CHH'=>'Chihuahua','CMX'=>'Ciudad de México','COA'=>'Coahuila','COL'=>'Colima',
    'DUR'=>'Durango','GUA'=>'Guanajuato','GRO'=>'Guerrero','HID'=>'Hidalgo','JAL'=>'Jalisco',
    'MEX'=>'Estado de México','MIC'=>'Michoacán','MOR'=>'Morelos','NAY'=>'Nayarit','NLE'=>'Nuevo León',
    'OAX'=>'Oaxaca','PUE'=>'Puebla','QUE'=>'Querétaro','ROO'=>'Quintana Roo','SLP'=>'San Luis Potosí',
    'SIN'=>'Sinaloa','SON'=>'Sonora','TAB'=>'Tabasco','TAM'=>'Tamaulipas','TLA'=>'Tlaxcala',
    'VER'=>'Veracruz','YUC'=>'Yucatán','ZAC'=>'Zacatecas',
];
$entidadNombre = $entidades[$lector['entidad'] ?? ''] ?? null;

// ¿Es el propio lector viendo su perfil?
$esMiPerfil = (isset($_SESSION['tipo'], $_SESSION['id_lector']) && $_SESSION['tipo'] === 'lector' && (int)$_SESSION['id_lector'] === $id);
?>

<div class="container" style="max-width: 900px; margin: 0 auto; padding: 30px 16px;">

    <!-- PERFIL DEL LECTOR -->
    <div class="autor-header">
        <div class="autor-foto-wrap">
            <?php if ($fotoLector): ?>
                <img src="<?= $fotoLector ?>" alt="<?= htmlspecialchars($lector['nombre']) ?>" class="autor-foto">
            <?php else: ?>
                <div class="autor-foto-fallback"><?= $iniciales ?></div>
            <?php endif; ?>
        </div>
        <div class="autor-info">
            <h1 class="autor-nombre"><?= htmlspecialchars($lector['nombre']) ?></h1>
            <p class="autor-username">@<?= htmlspecialchars($lector['usuario']) ?></p>
            <div class="autor-stats">
                <span><strong><?= $totalComentarios ?></strong> comentario<?= $totalComentarios == 1 ? '' : 's' ?></span>
                <?php if ($entidadNombre): ?>
                    <span><i class="bi bi-geo-alt-fill"></i> <?= htmlspecialchars($entidadNombre) ?></span>
                <?php endif; ?>
                <span>Miembro desde <?= $fechaRegistro ?></span>
            </div>
            <?php if ($esMiPerfil): ?>
                <div class="autor-social">
                    <a href="<?= basePath() ?>/perfil" class="autor-social-link autor-social-edit" style="background: var(--accent); color: #fff; border-radius: 6px; padding: 8px 16px; font-weight: 600;">
                        <i class="bi bi-pencil-square"></i> Editar Perfil
                    </a>
                </div>
            <?php endif; ?>
            <?php
                // Panel de moderación (solo superadmin)
                $modTipo = 'lector';
                $modUserId = $lector['id'];
                $modBanRow = obtenerBaneo($con, 'lector', $lector['id']) ?: [];
                include(__DIR__ . '/helpers/mod_panel.php');
            ?>
        </div>
    </div>

    <!-- COMENTARIOS DEL LECTOR -->
    <div class="autor-articles">
        <h2 style="margin-bottom: 20px;"><i class="bi bi-chat-left-text-fill"></i> Comentarios de <?= htmlspecialchars($lector['nombre']) ?></h2>
        <?php if (empty($comentarios)): ?>
            <p style="color: var(--muted); text-align: center; padding: 40px 0;">Este usuario aún no ha publicado comentarios.</p>
        <?php else: ?>
            <div class="usuario-comentarios-lista">
                <?php foreach ($comentarios as $com): ?>
                    <?php $url = newsUrl($com['noticia_slug'] ?: $com['noticia_id']); ?>
                    <div class="usuario-comentario-card">
                        <p class="usuario-comentario-texto"><?= nl2br(htmlspecialchars($com['contenido'])) ?></p>
                        <div class="usuario-comentario-meta">
                            <span><i class="bi bi-heart-fill"></i> <?= (int)$com['total_likes'] ?></span>
                            <span><i class="bi bi-clock"></i> <?= date('d M Y, H:i', strtotime($com['fecha_publicacion'])) ?></span>
                        </div>
                        <a href="<?= $url ?>" class="usuario-comentario-noticia">
                            <i class="bi bi-link-45deg"></i> En: <?= htmlspecialchars($com['noticia_titulo']) ?>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.usuario-comentarios-lista { display: flex; flex-direction: column; gap: 14px; }
.usuario-comentario-card {
    background: var(--card-bg, rgba(255,255,255,0.03));
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 16px 18px;
}
.usuario-comentario-texto { margin: 0 0 10px; color: var(--text); line-height: 1.5; }
.usuario-comentario-meta {
    display: flex; gap: 16px; flex-wrap: wrap;
    color: var(--muted); font-size: 0.82rem; margin-bottom: 8px;
}
.usuario-comentario-noticia {
    display: inline-flex; align-items: center; gap: 4px;
    color: var(--accent); text-decoration: none; font-size: 0.88rem; font-weight: 600;
}
.usuario-comentario-noticia:hover { text-decoration: underline; }
</style>

<?php include("./../layout/footer.php"); ?>
