<?php
/**
 * CatInk Master Email Layout Generator
 * Genera correos en formato HTML ultra-premium, responsivos y compatibles con clientes de correo.
 * Soporta temas 'dark' (predeterminado) y 'light'.
 */
if (!function_exists('renderCatInkEmail')) {
    function renderCatInkEmail($options = []) {
        $title          = htmlspecialchars($options['title'] ?? 'CatInk');
        $preheader      = htmlspecialchars($options['preheader'] ?? '');
        $badge          = htmlspecialchars($options['badge'] ?? '');
        $bodyContent    = $options['content'] ?? '';
        $ctaUrl         = htmlspecialchars($options['cta_url'] ?? '');
        $ctaText        = htmlspecialchars($options['cta_text'] ?? '');
        $unsubscribeUrl = htmlspecialchars($options['unsubscribe_url'] ?? '');
        $theme          = strtolower($options['theme'] ?? 'dark');
        $year           = date('Y');

        $isLight = ($theme === 'light');

        // Paleta de colores según tema
        $bgColor        = $isLight ? '#f1f5f9' : '#0b1220';
        $cardBg         = $isLight ? '#ffffff' : '#121927';
        $headerBg       = 'linear-gradient(135deg, #EF3363 0%, #d81b4d 100%)'; // Cabecera rosa acento CatInk
        $headerBorder   = '#EF3363';
        $cardBorder     = $isLight ? '#e2e8f0' : 'rgba(255,255,255,0.08)';
        $titleColor     = $isLight ? '#0f172a' : '#ffffff';
        $textColor      = $isLight ? '#334155' : '#e2e8f0';
        $footerBg       = $isLight ? '#f8fafc' : '#0c1320';
        $footerBorder   = $isLight ? '#e2e8f0' : 'rgba(255,255,255,0.06)';
        $footerText     = $isLight ? '#64748b' : '#718096';
        $footerSub      = $isLight ? '#94a3b8' : '#a0aec0';
        $footerCopy     = $isLight ? '#94a3b8' : '#4a5568';
        $badgeBg        = $isLight ? 'rgba(239,51,99,0.08)' : 'rgba(239,51,99,0.15)';
        $badgeBorder    = $isLight ? 'rgba(239,51,99,0.25)' : 'rgba(239,51,99,0.3)';

        $badgeHtml = '';
        if (!empty($badge)) {
            $badgeHtml = "<div style='display:inline-block; background:{$badgeBg}; color:#EF3363; border:1px solid {$badgeBorder}; font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:0.15em; padding:5px 12px; border-radius:20px; margin-bottom:16px;'>{$badge}</div>";
        }

        $ctaHtml = '';
        if (!empty($ctaUrl) && !empty($ctaText)) {
            $ctaHtml = "
            <div style='text-align:center; margin:32px 0 24px;'>
                <a href='{$ctaUrl}' target='_blank' style='display:inline-block; padding:14px 32px; background:#EF3363; color:#ffffff; font-family:Arial, sans-serif; font-size:15px; font-weight:800; text-decoration:none; border-radius:10px; box-shadow:0 6px 20px rgba(239,51,99,0.35); text-transform:uppercase; letter-spacing:0.05em;'>
                    {$ctaText}
                </a>
            </div>";
        }

        $unsubscribeHtml = '';
        if (!empty($unsubscribeUrl)) {
            $unsubscribeHtml = " | <a href='{$unsubscribeUrl}' style='color:{$footerSub}; text-decoration:underline;'>Cancelar suscripción</a>";
        }

        return "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='utf-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>{$title}</title>
    <!--[if mso]>
    <noscript>
    <xml>
    <o:OfficeDocumentSettings>
    <o:PixelsPerInch>96</o:PixelsPerInch>
    </o:OfficeDocumentSettings>
    </xml>
    </noscript>
    <![endif]-->
    <style>
        @media only screen and (max-width: 600px) {
            .email-container { width: 100% !important; border-radius: 0 !important; }
            .content-padding { padding: 24px 18px !important; }
            .stack-col { display: block !important; width: 100% !important; }
            .stack-img { width: 100% !important; max-width: 100% !important; height: auto !important; margin-bottom: 12px !important; }
        }
    </style>
</head>
<body style='margin:0; padding:0; background-color:{$bgColor}; font-family:Arial, Helvetica, sans-serif; -webkit-font-smoothing:antialiased;'>
    " . (!empty($preheader) ? "<div style='display:none;font-size:1px;color:{$bgColor};line-height:1px;max-height:0px;max-width:0px;opacity:0;overflow:hidden;'>{$preheader}</div>" : "") . "

    <table width='100%' cellpadding='0' cellspacing='0' border='0' style='background-color:{$bgColor}; padding:24px 8px;'>
        <tr>
            <td align='center'>
                <!-- Contenedor Principal (Max 600px) -->
                <table width='600' cellpadding='0' cellspacing='0' border='0' class='email-container' style='width:100%; max-width:600px; background:{$cardBg}; border:1px solid {$cardBorder}; border-radius:18px; overflow:hidden;'>
                    
                    <!-- HEADER CON LOGO -->
                    <tr>
                        <td align='center' style='padding:28px 24px 20px; background:{$headerBg}; border-bottom:1px solid {$headerBorder};'>
                            <a href='https://catink.com.mx' target='_blank' style='text-decoration:none;'>
                                <img src='https://catink.com.mx/img/logo_alt.png' alt='CatInk' width='150' style='width:150px; max-width:150px; height:auto; display:block; border:0; margin:0 auto;'>
                            </a>
                        </td>
                    </tr>

                    <!-- CUERPO DE CONTENIDO -->
                    <tr>
                        <td class='content-padding' style='padding:32px 28px 24px; color:{$textColor}; font-size:15px; line-height:1.7;'>
                            {$badgeHtml}
                            <h1 style='margin:0 0 16px; font-size:22px; font-weight:900; color:{$titleColor}; line-height:1.25;'>{$title}</h1>
                            {$bodyContent}
                            {$ctaHtml}
                        </td>
                    </tr>

                    <!-- FOOTER -->
                    <tr>
                        <td style='padding:24px 24px; background:{$footerBg}; border-top:1px solid {$footerBorder}; color:{$footerText}; font-size:12px; line-height:1.6; text-align:center;'>
                            <div style='margin-bottom:14px;'>
                                <a href='https://www.facebook.com/catink.mx' target='_blank' style='display:inline-block; margin:0 8px; color:{$textColor}; font-weight:700; text-decoration:none; font-size:12px;'>Facebook</a>
                                <a href='https://www.instagram.com/catink.mx' target='_blank' style='display:inline-block; margin:0 8px; color:{$textColor}; font-weight:700; text-decoration:none; font-size:12px;'>Instagram</a>
                                <a href='https://www.tiktok.com/@catink.mx' target='_blank' style='display:inline-block; margin:0 8px; color:{$textColor}; font-weight:700; text-decoration:none; font-size:12px;'>TikTok</a>
                                <a href='https://www.youtube.com/@catink' target='_blank' style='display:inline-block; margin:0 8px; color:{$textColor}; font-weight:700; text-decoration:none; font-size:12px;'>YouTube</a>
                            </div>

                            <p style='margin:0 0 10px; color:{$footerSub};'>
                                <a href='https://catink.com.mx/terminos' style='color:{$footerSub}; text-decoration:underline;'>Términos y condiciones</a> | 
                                <a href='https://catink.com.mx/privacidad' style='color:{$footerSub}; text-decoration:underline;'>Aviso de Privacidad</a>
                                {$unsubscribeHtml}
                            </p>

                            <p style='margin:0; color:{$footerCopy}; font-size:11px;'>
                                © {$year} CatInk — El medio geek de México. Todos los derechos reservados.<br>
                                Soporte y contacto: <a href='mailto:contacto@catink.com.mx' style='color:#EF3363; text-decoration:none;'>contacto@catink.com.mx</a>
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>";
    }
}
