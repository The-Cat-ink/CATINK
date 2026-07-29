<?php
session_start();
include("./../data/conexion.php");
require_once("./../views/helpers/urlhelper.php");
require_once("./../views/helpers/emailhelper.php");

// Verificar si es administrador logueado
$id_u = $_SESSION['id_u'] ?? 0;
if ($id_u <= 0) {
    header("Location: " . basePath() . "/views/login.php");
    exit();
}

$tipo  = strtolower($_GET['tipo'] ?? 'boletin');
$theme = strtolower($_GET['theme'] ?? 'light');

$baseUrl = siteUrl();

// Generar contenido de prueba según tipo
$title     = "Notificaciones CatInk";
$preheader = "Previsualización de correo electrónico institucional CatInk.";
$badge     = "Previsualización";
$ctaText   = "";
$ctaUrl    = "";
$content   = "";

switch ($tipo) {
    case 'promocional':
        $title     = "¡Oferta Exclusiva Geek!";
        $preheader = "Aprovecha descuentos únicos en merchandising y juegos.";
        $badge     = "Anuncio Patrocinado";
        $ctaText   = "Ver Promoción Exclusiva";
        $ctaUrl    = $baseUrl;
        $content   = "
            <h2 style='margin:0 0 12px; font-size:20px; font-weight:800; color:" . ($theme === 'light' ? '#0f172a' : '#ffffff') . ";'>Gran Venta Especial de Aniversario CatInk</h2>
            <p style='margin:0 0 16px; line-height:1.6;'>Querida comunidad geek, traemos para ti alianzas exclusivas con las mejores tiendas de cómics, manga y figuras coleccionables de México.</p>
            <div style='background:" . ($theme === 'light' ? '#f8fafc' : '#1a2234') . "; border:1px solid " . ($theme === 'light' ? '#e2e8f0' : 'rgba(255,255,255,0.1)') . "; border-radius:12px; padding:16px; margin:20px 0; text-align:center;'>
                <span style='font-size:24px; font-weight:900; color:#EF3363;'>25% DE DESCUENTO</span>
                <p style='margin:4px 0 0; font-size:13px; color:" . ($theme === 'light' ? '#64748b' : '#94a3b8') . ";'>Código: <strong>CATINK2026</strong> en tus compras mayores a $500 MXN.</p>
            </div>";
        break;

    case 'registro':
        $title     = "¡Bienvenido a la comunidad CatInk!";
        $preheader = "Por favor confirma tu correo electrónico para completar tu registro.";
        $badge     = "Verificación de Cuenta";
        $ctaText   = "Confirmar Mi Cuenta";
        $ctaUrl    = $baseUrl . "/controllers/confirmar.php?token=demo123456";
        $content   = "
            <h2 style='margin:0 0 12px; font-size:20px; font-weight:800; color:" . ($theme === 'light' ? '#0f172a' : '#ffffff') . ";'>¡Hola, Lector Geek!</h2>
            <p style='margin:0 0 16px; line-height:1.6;'>Gracias por unirte a <strong>CatInk News</strong>. Estamos muy emocionados de tenerte con nosotros para compartir las mejores reseñas, noticias y análisis del mundo geek.</p>
            <p style='margin:0 0 16px; line-height:1.6;'>Para comenzar a personalizar tu feed, guardar lecturas offline y comentar en los artículos, haz clic en el siguiente botón para verificar tu correo:</p>";
        break;

    case 'reset':
        $title     = "Solicitud de Restablecimiento de Contraseña";
        $preheader = "Recibimos una solicitud para cambiar la contraseña de tu cuenta CatInk.";
        $badge     = "Seguridad de la Cuenta";
        $ctaText   = "Restablecer Contraseña";
        $ctaUrl    = $baseUrl . "/views/reset_password.php?token=demo_token_reset";
        $content   = "
            <h2 style='margin:0 0 12px; font-size:20px; font-weight:800; color:" . ($theme === 'light' ? '#0f172a' : '#ffffff') . ";'>¿Olvidaste tu contraseña?</h2>
            <p style='margin:0 0 16px; line-height:1.6;'>Recibimos una petición para restablecer la contraseña asociada a tu cuenta. Si fuiste tú, haz clic en el botón de abajo para elegir una nueva clave:</p>
            <p style='margin:0 0 16px; font-size:13px; color:" . ($theme === 'light' ? '#64748b' : '#94a3b8') . ";'>Este enlace expirará en 60 minutos por razones de seguridad. Si no realizaste esta solicitud, puedes ignorar este correo de forma segura.</p>";
        break;

    case 'vacante':
        $title     = "Nueva Postulación: Redactor de Contenidos Anime";
        $preheader = "Un candidato ha enviado su solicitud para la vacante de Redactor.";
        $badge     = "CatInk Vacantes";
        $ctaText   = "Ver Solicitudes en Admin";
        $ctaUrl    = $baseUrl . "/views/admin.php";
        $content   = "
            <h2 style='margin:0 0 16px; font-size:20px; font-weight:800; color:" . ($theme === 'light' ? '#0f172a' : '#ffffff') . ";'>Detalles de la Postulación</h2>
            <table style='width:100%; border-collapse:collapse; background:" . ($theme === 'light' ? '#f8fafc' : '#161f30') . "; border-radius:12px; overflow:hidden; border:1px solid " . ($theme === 'light' ? '#e2e8f0' : 'rgba(255,255,255,0.08)') . ";'>
                <tr><td style='padding:10px 14px; font-weight:800; font-size:13px; color:" . ($theme === 'light' ? '#64748b' : '#94a3b8') . "; border-bottom:1px solid " . ($theme === 'light' ? '#e2e8f0' : 'rgba(255,255,255,0.08)') . "; width:130px;'>Candidato:</td><td style='padding:10px 14px; font-weight:700; font-size:13px; color:" . ($theme === 'light' ? '#0f172a' : '#ffffff') . "; border-bottom:1px solid " . ($theme === 'light' ? '#e2e8f0' : 'rgba(255,255,255,0.08)') . ";'>Carlos Mendoza</td></tr>
                <tr><td style='padding:10px 14px; font-weight:800; font-size:13px; color:" . ($theme === 'light' ? '#64748b' : '#94a3b8') . "; border-bottom:1px solid " . ($theme === 'light' ? '#e2e8f0' : 'rgba(255,255,255,0.08)') . ";'>Correo:</td><td style='padding:10px 14px; font-weight:700; font-size:13px; color:#EF3363; border-bottom:1px solid " . ($theme === 'light' ? '#e2e8f0' : 'rgba(255,255,255,0.08)') . ";'>carlos.mendoza@example.com</td></tr>
                <tr><td style='padding:10px 14px; font-weight:800; font-size:13px; color:" . ($theme === 'light' ? '#64748b' : '#94a3b8') . "; border-bottom:1px solid " . ($theme === 'light' ? '#e2e8f0' : 'rgba(255,255,255,0.08)') . ";'>Teléfono:</td><td style='padding:10px 14px; font-weight:700; font-size:13px; color:" . ($theme === 'light' ? '#0f172a' : '#ffffff') . "; border-bottom:1px solid " . ($theme === 'light' ? '#e2e8f0' : 'rgba(255,255,255,0.08)') . ";'>55 9876 5432</td></tr>
                <tr><td style='padding:10px 14px; font-weight:800; font-size:13px; color:" . ($theme === 'light' ? '#64748b' : '#94a3b8') . ";'>Mensaje:</td><td style='padding:10px 14px; font-size:13px; color:" . ($theme === 'light' ? '#334155' : '#e2e8f0') . ";'>Hola equipo de CatInk, me encantaría colaborar redactando noticias de la temporada de Anime. Adjunto mi CV.</td></tr>
            </table>";
        break;

    case 'contacto':
        $title     = "Nuevo Mensaje de Contacto - Empresa / Marca";
        $preheader = "Has recibido una nueva propuesta comercial desde la página de contacto.";
        $badge     = "Formulario de Contacto";
        $ctaText   = "Responder al Cliente";
        $ctaUrl    = "mailto:contacto.empresa@brand.com";
        $content   = "
            <h2 style='margin:0 0 16px; font-size:20px; font-weight:800; color:" . ($theme === 'light' ? '#0f172a' : '#ffffff') . ";'>Datos de la Empresa / Marca</h2>
            <table style='width:100%; border-collapse:collapse; background:" . ($theme === 'light' ? '#f8fafc' : '#161f30') . "; border-radius:12px; overflow:hidden; border:1px solid " . ($theme === 'light' ? '#e2e8f0' : 'rgba(255,255,255,0.08)') . "; margin-bottom:16px;'>
                <tr><td style='padding:10px 14px; font-weight:800; font-size:13px; color:" . ($theme === 'light' ? '#64748b' : '#94a3b8') . "; border-bottom:1px solid " . ($theme === 'light' ? '#e2e8f0' : 'rgba(255,255,255,0.08)') . "; width:140px;'>Empresa / Marca:</td><td style='padding:10px 14px; font-weight:800; font-size:13px; color:#EF3363; border-bottom:1px solid " . ($theme === 'light' ? '#e2e8f0' : 'rgba(255,255,255,0.08)') . ";'>Gaming Studio MX</td></tr>
                <tr><td style='padding:10px 14px; font-weight:800; font-size:13px; color:" . ($theme === 'light' ? '#64748b' : '#94a3b8') . "; border-bottom:1px solid " . ($theme === 'light' ? '#e2e8f0' : 'rgba(255,255,255,0.08)') . ";'>Contacto:</td><td style='padding:10px 14px; font-weight:700; font-size:13px; color:" . ($theme === 'light' ? '#0f172a' : '#ffffff') . "; border-bottom:1px solid " . ($theme === 'light' ? '#e2e8f0' : 'rgba(255,255,255,0.08)') . ";'>Mariana Ríos (PR Manager)</td></tr>
                <tr><td style='padding:10px 14px; font-weight:800; font-size:13px; color:" . ($theme === 'light' ? '#64748b' : '#94a3b8') . "; border-bottom:1px solid " . ($theme === 'light' ? '#e2e8f0' : 'rgba(255,255,255,0.08)') . ";'>Interés:</td><td style='padding:10px 14px; font-weight:700; font-size:13px; color:" . ($theme === 'light' ? '#0f172a' : '#ffffff') . "; border-bottom:1px solid " . ($theme === 'light' ? '#e2e8f0' : 'rgba(255,255,255,0.08)') . ";'>Campaña Publicitaria Patrocinada</td></tr>
                <tr><td style='padding:10px 14px; font-weight:800; font-size:13px; color:" . ($theme === 'light' ? '#64748b' : '#94a3b8') . ";'>Mensaje:</td><td style='padding:10px 14px; font-size:13px; color:" . ($theme === 'light' ? '#334155' : '#e2e8f0') . ";'>Estamos interesados en patrocinar un banner publicitario de 4:1 en la portada durante el próximo mes.</td></tr>
            </table>";
        break;

    case 'boletin':
    default:
        $title     = "Boletín Diario de Noticias - CatInk News";
        $preheader = "Las 3 noticias más leídas de la jornada en CatInk.";
        $badge     = "Resumen Diario";
        $ctaText   = "Ver Más Noticias en CatInk";
        $ctaUrl    = $baseUrl;
        $content   = "
            <h2 style='margin:0 0 16px; font-size:20px; font-weight:800; color:" . ($theme === 'light' ? '#0f172a' : '#ffffff') . ";'>Noticias Destacadas de Hoy</h2>
            
            <div style='background:" . ($theme === 'light' ? '#f8fafc' : '#161f30') . "; border-radius:12px; padding:14px; margin-bottom:12px; border:1px solid " . ($theme === 'light' ? '#e2e8f0' : 'rgba(255,255,255,0.08)') . ";'>
                <span style='background:rgba(239,51,99,0.12); color:#EF3363; font-size:10px; font-weight:800; text-transform:uppercase; padding:3px 8px; border-radius:10px;'>ANIME</span>
                <h3 style='margin:6px 0 4px; font-size:15px; font-weight:800; color:" . ($theme === 'light' ? '#0f172a' : '#ffffff') . ";'>Se confirma la nueva temporada de Kimetsu no Yaiba para 2026</h3>
                <p style='margin:0; font-size:13px; color:" . ($theme === 'light' ? '#64748b' : '#94a3b8') . ";'>Ufotable reveló el primer avance oficial durante el evento especial en Tokio...</p>
            </div>

            <div style='background:" . ($theme === 'light' ? '#f8fafc' : '#161f30') . "; border-radius:12px; padding:14px; margin-bottom:12px; border:1px solid " . ($theme === 'light' ? '#e2e8f0' : 'rgba(255,255,255,0.08)') . ";'>
                <span style='background:rgba(59,130,246,0.12); color:#3b82f6; font-size:10px; font-weight:800; text-transform:uppercase; padding:3px 8px; border-radius:10px;'>VIDEOJUEGOS</span>
                <h3 style='margin:6px 0 4px; font-size:15px; font-weight:800; color:" . ($theme === 'light' ? '#0f172a' : '#ffffff') . ";'>Análisis de la nueva consola de Nintendo y sus títulos de lanzamiento</h3>
                <p style='margin:0; font-size:13px; color:" . ($theme === 'light' ? '#64748b' : '#94a3b8') . ";'>Probamos en exclusiva los primeros juegos optimizados para la plataforma...</p>
            </div>";
        break;
}

$emailHtml = renderCatInkEmail([
    'title'          => $title,
    'preheader'      => $preheader,
    'badge'          => $badge,
    'content'        => $content,
    'cta_text'       => $ctaText,
    'cta_url'        => $ctaUrl,
    'unsubscribe_url'=> $baseUrl . '/suscripcion',
    'theme'          => $theme
]);

// Si es solicitud iframe/raw
if (isset($_GET['raw']) && $_GET['raw'] === '1') {
    echo $emailHtml;
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Previsualizador de Correos - CatInk</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --bg: #0b111e;
            --card: #151d2a;
            --border: #232d3f;
            --text: #f1f5f9;
            --muted: #94a3b8;
            --accent: #EF3363;
        }
        body {
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: var(--bg);
            color: var(--text);
            height: 100vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        .toolbar {
            background: var(--card);
            border-bottom: 1px solid var(--border);
            padding: 12px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
            z-index: 100;
        }
        .toolbar-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 900;
            font-size: 1.05rem;
            color: var(--text);
            text-decoration: none;
        }
        .toolbar-brand i { color: var(--accent); font-size: 1.3rem; }
        .toolbar-controls {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }
        .toolbar-select, .toolbar-btn {
            background: var(--bg);
            color: var(--text);
            border: 1px solid var(--border);
            padding: 8px 14px;
            border-radius: 10px;
            font-size: 0.85rem;
            font-weight: 700;
            outline: none;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .toolbar-select:focus, .toolbar-btn:hover {
            border-color: var(--accent);
        }
        .toolbar-btn-accent {
            background: var(--accent);
            color: #ffffff;
            border: none;
            box-shadow: 0 4px 14px rgba(239, 51, 99, 0.4);
        }
        .toolbar-btn-accent:hover {
            opacity: 0.9;
        }
        .preview-container {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            background: #070b13;
            position: relative;
        }
        iframe {
            width: 100%;
            max-width: 680px;
            height: 100%;
            border: 1px solid var(--border);
            border-radius: 16px;
            background: #ffffff;
            box-shadow: 0 20px 50px rgba(0,0,0,0.5);
            transition: max-width 0.3s ease;
        }
        .viewport-mobile iframe { max-width: 380px; }
    </style>
</head>
<body>

    <header class="toolbar">
        <a href="<?= basePath() ?>/views/correos.php" class="toolbar-brand">
            <i class="bi bi-envelope-paper-heart-fill"></i>
            <span>CatInk Email Previewer</span>
        </a>

        <div class="toolbar-controls">
            <!-- Selector de Tipo -->
            <div>
                <label style="font-size:0.75rem; color:var(--muted); display:block; margin-bottom:2px; font-weight:700;">FORMATO DE CORREO</label>
                <select class="toolbar-select" id="selTipo" onchange="updatePreview()">
                    <option value="boletin" <?= $tipo === 'boletin' ? 'selected' : '' ?>>📬 Boletín Diario de Noticias</option>
                    <option value="promocional" <?= $tipo === 'promocional' ? 'selected' : '' ?>>📢 Anuncio Promocional Patrocinado</option>
                    <option value="registro" <?= $tipo === 'registro' ? 'selected' : '' ?>>🎉 Bienvenida / Verificación de Cuenta</option>
                    <option value="reset" <?= $tipo === 'reset' ? 'selected' : '' ?>>🔐 Restablecer Contraseña</option>
                    <option value="vacante" <?= $tipo === 'vacante' ? 'selected' : '' ?>>💼 Postulación a Vacantes</option>
                    <option value="contacto" <?= $tipo === 'contacto' ? 'selected' : '' ?>>🏢 Formulario de Contacto Corporativo</option>
                </select>
            </div>

            <!-- Selector de Tema -->
            <div>
                <label style="font-size:0.75rem; color:var(--muted); display:block; margin-bottom:2px; font-weight:700;">TEMA DE COLOR</label>
                <select class="toolbar-select" id="selTheme" onchange="updatePreview()">
                    <option value="light" <?= $theme === 'light' ? 'selected' : '' ?>>☀️ Tema Claro (Light Default)</option>
                    <option value="dark" <?= $theme === 'dark' ? 'selected' : '' ?>>🌙 Tema Oscuro (Dark Mode)</option>
                </select>
            </div>

            <!-- Selector de Dispositivo -->
            <div>
                <label style="font-size:0.75rem; color:var(--muted); display:block; margin-bottom:2px; font-weight:700;">DISPOSITIVO</label>
                <button type="button" class="toolbar-btn" id="btnDesktop" onclick="setViewport('desktop')" title="Vista Desktop">
                    <i class="bi bi-display me-1"></i> Desktop
                </button>
                <button type="button" class="toolbar-btn" id="btnMobile" onclick="setViewport('mobile')" title="Vista Móvil">
                    <i class="bi bi-phone me-1"></i> Móvil
                </button>
            </div>

            <!-- Botón Volver -->
            <div style="align-self:flex-end;">
                <a href="<?= basePath() ?>/views/correos.php" class="toolbar-btn toolbar-btn-accent" style="text-decoration:none;">
                    <i class="bi bi-arrow-left me-1"></i> Volver a Correos
                </a>
            </div>
        </div>
    </header>

    <main class="preview-container" id="previewWrapper">
        <iframe src="preview_email.php?tipo=<?= urlencode($tipo) ?>&theme=<?= urlencode($theme) ?>&raw=1" id="emailIframe"></iframe>
    </main>

    <script>
        function updatePreview() {
            const tipo = document.getElementById('selTipo').value;
            const theme = document.getElementById('selTheme').value;
            const iframe = document.getElementById('emailIframe');
            iframe.src = `preview_email.php?tipo=${tipo}&theme=${theme}&raw=1`;
            
            // Actualizar URL sin recargar la página
            const newUrl = window.location.pathname + `?tipo=${tipo}&theme=${theme}`;
            window.history.pushState({ path: newUrl }, '', newUrl);
        }

        function setViewport(mode) {
            const wrapper = document.getElementById('previewWrapper');
            const btnD = document.getElementById('btnDesktop');
            const btnM = document.getElementById('btnMobile');

            if (mode === 'mobile') {
                wrapper.classList.add('viewport-mobile');
                btnM.style.borderColor = 'var(--accent)';
                btnD.style.borderColor = 'var(--border)';
            } else {
                wrapper.classList.remove('viewport-mobile');
                btnD.style.borderColor = 'var(--accent)';
                btnM.style.borderColor = 'var(--border)';
            }
        }
    </script>
</body>
</html>
