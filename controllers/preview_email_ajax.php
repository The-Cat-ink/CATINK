<?php
session_start();
require_once(__DIR__ . "/../data/conexion.php");
require_once(__DIR__ . "/../views/helpers/urlhelper.php");
require_once(__DIR__ . "/../views/helpers/emailhelper.php");

// Solo accesible por usuarios autenticados
if (!isset($_SESSION['usuario'])) {
    http_response_code(403);
    die("Acceso no autorizado");
}

header('Content-Type: text/html; charset=utf-8');

$title          = trim($_POST['title'] ?? $_POST['titulo'] ?? 'Título del Correo');
$badge          = trim($_POST['badge'] ?? 'Anuncio / Promoción');
$preheader      = trim($_POST['preheader'] ?? '');
$contentRaw     = $_POST['content'] ?? $_POST['contenido'] ?? '';
$ctaUrl         = trim($_POST['cta_url'] ?? $_POST['url'] ?? '');
$ctaText        = trim($_POST['cta_text'] ?? 'Ver promoción');
$theme          = strtolower($_POST['theme'] ?? 'light');
$imgUrl         = trim($_POST['img_url'] ?? '');

if (empty($title)) {
    $title = 'Título del Correo';
}

// Convertir saltos de línea si es texto plano sin etiquetas HTML
if (strip_tags($contentRaw) === $contentRaw) {
    $bodyHtml = "<p style='margin:0 0 16px; line-height:1.7;'>" . nl2br(htmlspecialchars($contentRaw)) . "</p>";
} else {
    $bodyHtml = $contentRaw;
}

// Agregar imagen al cuerpo si existe
if (!empty($imgUrl)) {
    $bodyHtml .= "<div style='text-align:center; margin:24px 0;'><img src='{$imgUrl}' style='width:100%; max-width:540px; border-radius:12px; height:auto; display:inline-block; border:1px solid rgba(255,255,255,0.08); shadow:0 8px 24px rgba(0,0,0,0.15);' alt='Imagen Adjunta'></div>";
}

$unsubscribeUrl = siteUrl() . '/suscripcion';

echo renderCatInkEmail([
    'title'           => $title,
    'preheader'       => $preheader,
    'badge'           => $badge,
    'content'         => $bodyHtml,
    'cta_url'         => $ctaUrl,
    'cta_text'        => $ctaText,
    'unsubscribe_url' => $unsubscribeUrl,
    'theme'           => $theme
]);
