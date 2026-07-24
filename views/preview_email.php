<?php
/**
 * Previsualizador Web Interactivo de Plantillas de Correo Electrónico CatInk
 */
include_once(__DIR__ . "/../data/conexion.php");
include_once(__DIR__ . "/../views/helpers/emailhelper.php");

$tipo = $_GET['tipo'] ?? 'boletin';
$html = '';

switch ($tipo) {
    case 'verificacion':
        $content = "
            <p>Hola <strong style='color:#ffffff;'>Alex Developer</strong>,</p>
            <p style='color:#cbd5e0; line-height:1.7;'>¡Te damos la bienvenida a CatInk! Gracias por registrarte en nuestra plataforma de noticias y comunidad. Para activar tu cuenta y comenzar a participar, por favor confirma tu correo electrónico:</p>
            <p style='color:#718096; font-size:12px; margin-top:24px; word-break:break-all;'>Si tienes problemas con el botón, copia este enlace en tu navegador:<br><a href='https://catink.com.mx/verificar.php?token=demo123' style='color:#EF3363;'>https://catink.com.mx/verificar.php?token=demo123</a></p>
        ";
        $html = renderCatInkEmail([
            'title'     => '¡Te damos la bienvenida a CatInk!',
            'badge'     => 'Verificación de Cuenta',
            'content'   => $content,
            'cta_url'   => 'https://catink.com.mx/verificar.php?token=demo123',
            'cta_text'  => 'Verificar mi cuenta'
        ]);
        break;

    case 'reset':
        $content = "
            <p>Hola <strong style='color:#ffffff;'>Alex Developer</strong>,</p>
            <p style='color:#cbd5e0; line-height:1.7;'>Recibimos una solicitud para restablecer la contraseña de tu cuenta en CatInk. Haz clic en el botón a continuación para definir una nueva contraseña:</p>
            <p style='color:#718096; font-size:12px; margin-top:20px;'>* Este enlace de recuperación expira en 24 horas por razones de seguridad.<br>Si no solicitaste este cambio, puedes ignorar este correo de forma segura.</p>
        ";
        $html = renderCatInkEmail([
            'title'     => 'Recuperación de Contraseña',
            'badge'     => 'Seguridad de Cuenta',
            'content'   => $content,
            'cta_url'   => 'https://catink.com.mx/reset_contrasena?token=demo123',
            'cta_text'  => 'Restablecer mi Contraseña'
        ]);
        break;

    case 'vacante':
        $content = "
            <p style='color:#cbd5e0; font-size:15px;'>Se ha recibido una nueva candidatura a través del portal de reclutamiento.</p>
            
            <table width='100%' cellpadding='0' cellspacing='0' style='background:#182234; border-radius:12px; padding:18px; margin:20px 0; border:1px solid rgba(255,255,255,0.06);'>
                <tr><td style='padding:6px 0; color:#718096; font-size:13px; font-weight:700;'>Puesto Solicitado:</td><td style='padding:6px 0; color:#EF3363; font-weight:800; font-size:15px;'>Redactor Senior de Anime & Manga</td></tr>
                <tr><td style='padding:6px 0; color:#718096; font-size:13px; font-weight:700;'>Postulante:</td><td style='padding:6px 0; color:#ffffff; font-weight:700;'>Carlos Mendoza</td></tr>
                <tr><td style='padding:6px 0; color:#718096; font-size:13px; font-weight:700;'>Correo:</td><td style='padding:6px 0;'><a href='mailto:carlos@ejemplo.com' style='color:#EF3363;'>carlos@ejemplo.com</a></td></tr>
                <tr><td style='padding:6px 0; color:#718096; font-size:13px; font-weight:700;'>Teléfono:</td><td style='padding:6px 0; color:#e2e8f0;'>+52 722 123 4567</td></tr>
            </table>

            <div style='background:#162032; padding:18px; border-radius:12px; border-left:4px solid #EF3363; margin-top:16px;'>
                <strong style='color:#EF3363; font-size:13px; text-transform:uppercase; letter-spacing:0.05em;'>Motivación / Presentación:</strong>
                <p style='color:#e2e8f0; margin:10px 0 0; line-height:1.7; white-space:pre-wrap;'>Hola equipo de CatInk, me apasiona el mundo del anime y cuento con 4 años de experiencia en redacción digital y cobertura de estrenos. Me encantaría unirme al equipo.</p>
            </div>

            <p style='color:#718096; font-size:12px; margin-top:24px;'>* Se adjunta a este correo el archivo de CV/Portafolio recibido (CV_Carlos_Mendoza.pdf).</p>
        ";
        $html = renderCatInkEmail([
            'title'     => 'Nueva Solicitud de Empleo',
            'badge'     => 'Reclutamiento CatInk',
            'content'   => $content,
            'cta_url'   => 'mailto:carlos@ejemplo.com',
            'cta_text'  => 'Responder Candidato'
        ]);
        break;

    case 'contacto':
        $content = "
            <p style='color:#cbd5e0; font-size:15px;'>Se ha recibido un nuevo mensaje a través del formulario de contacto del sitio web.</p>
            
            <table width='100%' cellpadding='0' cellspacing='0' style='background:#182234; border-radius:12px; padding:18px; margin:20px 0; border:1px solid rgba(255,255,255,0.06);'>
                <tr><td style='padding:6px 0; color:#718096; font-size:13px; font-weight:700;'>De:</td><td style='padding:6px 0; color:#ffffff; font-weight:700;'>Mariana López</td></tr>
                <tr><td style='padding:6px 0; color:#718096; font-size:13px; font-weight:700;'>Correo:</td><td style='padding:6px 0;'><a href='mailto:mariana@empresa.com' style='color:#EF3363;'>mariana@empresa.com</a></td></tr>
                <tr><td style='padding:6px 0; color:#718096; font-size:13px; font-weight:700;'>Asunto:</td><td style='padding:6px 0; color:#EF3363; font-weight:800;'>Propuesta de Alianza Comercial / Patrocinio</td></tr>
            </table>

            <div style='background:#162032; padding:18px; border-radius:12px; border-left:4px solid #EF3363; margin-top:16px;'>
                <strong style='color:#EF3363; font-size:13px; text-transform:uppercase; letter-spacing:0.05em;'>Mensaje:</strong>
                <p style='color:#e2e8f0; margin:10px 0 0; line-height:1.7;'>Hola equipo de CatInk, nos gustaría coordinar una campaña de patrocinio para el lanzamiento de nuestra nueva línea de figuras coleccionables. ¿Podríamos agendar una llamada?</p>
            </div>
        ";
        $html = renderCatInkEmail([
            'title'     => 'Nuevo Mensaje de Contacto',
            'badge'     => 'Formulario de Contacto',
            'content'   => $content,
            'cta_url'   => 'mailto:mariana@empresa.com',
            'cta_text'  => 'Responder a Mariana'
        ]);
        break;

    case 'publicidad':
        $content = "
            <div style='color:#e2e8f0; font-size:15px; line-height:1.7;'>
                <p>¡Hola fan de la cultura geek!</p>
                <p>Tenemos noticias espectaculares: ¡El mayor evento de cultura pop del año ya está aquí y queremos que vivas la experiencia antes que nadie!</p>
            </div>
            <div style='text-align:center; margin:20px 0;'>
                <img src='https://catink.com.mx/img/logo_alt.png' style='width:100%; max-width:480px; border-radius:12px;'>
            </div>
        ";
        $html = renderCatInkEmail([
            'title'           => '¡Gran Lanzamiento CatInk Exclusivo!',
            'badge'           => 'Anuncio / Promoción',
            'content'         => $content,
            'cta_url'         => 'https://catink.com.mx',
            'cta_text'        => 'Descubrir Promoción',
            'unsubscribe_url' => 'https://catink.com.mx/unsubscribe'
        ]);
        break;

    case 'boletin':
    default:
        $noticiasRes = $con->query("SELECT * FROM noticias WHERE eliminado_en IS NULL ORDER BY id DESC LIMIT 3");
        $noticiasItems = '';
        if ($noticiasRes && $noticiasRes->num_rows > 0) {
            while ($n = $noticiasRes->fetch_assoc()) {
                $desc = mb_strimwidth(strip_tags($n['descripcion']), 0, 100, '...');
                $img = !empty($n['crop3']) ? 'https://catink.com.mx/serve-image.php?file=' . urlencode($n['crop3']) : 'https://catink.com.mx/img/logo_alt.png';
                $tituloEsc = htmlspecialchars($n['titulo']);
                $urlNoticia = "https://catink.com.mx/views/news.php?id={$n['id']}";
                $noticiasItems .= "
                <table width='100%' cellpadding='0' cellspacing='0' border='0' style='background:#182234;margin-bottom:16px;border-radius:14px;overflow:hidden;border:1px solid rgba(255,255,255,0.06);'>
                <tr>
                <td width='180' valign='middle' class='stack-col' style='padding:12px;'>
                    <a href='{$urlNoticia}' target='_blank'>
                        <img src='{$img}' width='180' class='stack-img' style='width:100%;max-width:180px;height:auto;display:block;border-radius:10px;border:0;margin:0;'>
                    </a>
                </td>
                <td valign='middle' class='stack-col' style='padding:14px 16px 14px 4px;font-family:Arial,sans-serif;'>
                    <a href='{$urlNoticia}' target='_blank' style='display:block;text-decoration:none;color:#ffffff;'>
                        <h3 style='margin:0 0 8px;font-family:Arial,sans-serif;color:#ffffff;font-size:16px;font-weight:800;line-height:1.3;'>{$tituloEsc}</h3>
                    </a>
                    <p style='margin:0 0 12px;color:#a0aec0;font-size:13px;line-height:1.5;'>{$desc}</p>
                    <a href='{$urlNoticia}' target='_blank' style='display:inline-block;color:#EF3363;font-size:12px;font-weight:800;text-decoration:none;text-transform:uppercase;letter-spacing:0.05em;'>
                        Leer noticia completa →
                    </a>
                </td>
                </tr>
                </table>";
            }
        } else {
            $noticiasItems = "<p style='color:#a0aec0; text-align:center;'>No hay noticias cargadas en la BD.</p>";
        }

        $plantilla = file_get_contents(__DIR__ . "/email/diarias.html");
        $html = str_replace(['{{noticias}}', '{{unsubscribe_url}}'], [$noticiasItems, 'https://catink.com.mx/unsubscribe'], $plantilla);
        break;
}

if (isset($_GET['raw']) && $_GET['raw'] == '1') {
    echo $html;
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Previsualizador de Correos — CatInk</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { margin:0; padding:0; background:#080d16; font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif; color:#fff; height:100vh; display:flex; flex-direction:column; overflow:hidden; }
        .toolbar { background:#121927; border-bottom:1px solid rgba(255,255,255,0.08); padding:12px 20px; display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap; }
        .toolbar-title { font-size:1.1rem; font-weight:800; display:flex; align-items:center; gap:8px; color:#fff; }
        .toolbar-title i { color:#EF3363; font-size:1.3rem; }
        .tabs { display:flex; gap:8px; flex-wrap:wrap; }
        .tab-btn { background:rgba(255,255,255,0.05); color:#a0aec0; border:1px solid rgba(255,255,255,0.08); padding:8px 14px; border-radius:10px; font-size:0.85rem; font-weight:700; text-decoration:none; transition:all 0.2s ease; display:inline-flex; align-items:center; gap:6px; }
        .tab-btn:hover, .tab-btn.active { background:#EF3363; color:#fff; border-color:#EF3363; box-shadow:0 4px 12px rgba(239,51,99,0.3); }
        .iframe-container { flex:1; width:100%; background:#0b1220; position:relative; }
        iframe { width:100%; height:100%; border:none; display:block; }
    </style>
</head>
<body>
    <div class="toolbar">
        <div class="toolbar-title">
            <i class="bi bi-envelope-paper-heart-fill"></i> Previsualizador de Correos CatInk
        </div>
        <div class="tabs">
            <a href="?tipo=boletin" class="tab-btn <?= $tipo==='boletin'?'active':'' ?>"><i class="bi bi-newspaper"></i> Resumen Diario</a>
            <a href="?tipo=verificacion" class="tab-btn <?= $tipo==='verificacion'?'active':'' ?>"><i class="bi bi-check-circle-fill"></i> Bienvenida / Verificación</a>
            <a href="?tipo=reset" class="tab-btn <?= $tipo==='reset'?'active':'' ?>"><i class="bi bi-key-fill"></i> Reset Contraseña</a>
            <a href="?tipo=vacante" class="tab-btn <?= $tipo==='vacante'?'active':'' ?>"><i class="bi bi-briefcase-fill"></i> Postulación Vacante</a>
            <a href="?tipo=contacto" class="tab-btn <?= $tipo==='tab-btn'?'active':'' ?> <?= $tipo==='contacto'?'active':'' ?>"><i class="bi bi-chat-dots-fill"></i> Formulario Contacto</a>
            <a href="?tipo=publicidad" class="tab-btn <?= $tipo==='publicidad'?'active':'' ?>"><i class="bi bi-megaphone-fill"></i> Anuncio / Promoción</a>
        </div>
        <div>
            <a href="/views/paginas.php" class="tab-btn" style="background:transparent;"><i class="bi bi-arrow-left"></i> Volver a Admin</a>
        </div>
    </div>
    <div class="iframe-container">
        <iframe src="?tipo=<?= urlencode($tipo) ?>&raw=1"></iframe>
    </div>
</body>
</html>
