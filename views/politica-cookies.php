<?php
include_once(__DIR__ . "/../layout/header.php");
include_once(__DIR__ . "/../data/conexion.php");

$result = @$con->query("SELECT contenido_pag, meta_json FROM paginas WHERE nombre_pag='cookies'");
if (!$result) {
    $result = @$con->query("SELECT contenido_pag FROM paginas WHERE nombre_pag='cookies'");
}
$row  = ($result && $result !== true && method_exists($result, 'fetch_assoc')) ? $result->fetch_assoc() : [];
$meta = json_decode($row['meta_json'] ?? '', true) ?: [];

$cookieState = $_COOKIE['cookies_decision'] ?? 'sin_configurar';
?>

<style>
/* ─── PÁGINA POLÍTICA DE COOKIES ─────────────────────────────────── */
.legal-page { --cw: 1080px; }
.legal-hero {
    padding: 72px 32px 56px;
    background: var(--card-bg);
    border-bottom: 1px solid var(--border);
    position: relative;
    overflow: hidden;
}
.legal-hero::before {
    content: '';
    position: absolute;
    inset: 0;
    background: radial-gradient(ellipse 60% 80% at 90% 40%, rgba(245,158,11,0.08), transparent 65%);
    pointer-events: none;
}
.legal-hero-inner {
    max-width: var(--cw);
    margin: 0 auto;
    position: relative;
    z-index: 1;
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 40px;
    flex-wrap: wrap;
}
.legal-hero-icon {
    width: 64px; height: 64px; border-radius: 18px;
    background: rgba(245,158,11,0.12);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.9rem; color: #f59e0b; margin-bottom: 20px;
    border: 1px solid rgba(245,158,11,0.25);
    box-shadow: 0 8px 24px rgba(245,158,11,0.15);
}
.legal-hero-eyebrow { font-size:0.7rem; font-weight:800; letter-spacing:.2em; text-transform:uppercase; color:#f59e0b; margin-bottom:10px; }
.legal-hero-title { font-size:clamp(1.9rem,4vw,2.8rem); font-weight:900; color:var(--text); margin:0 0 12px; line-height:1.1; }
.legal-hero-meta { font-size:0.85rem; color:var(--muted); display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
.legal-hero-badge { background:rgba(245,158,11,0.12); color:#f59e0b; font-size:0.72rem; font-weight:700; padding:4px 10px; border-radius:20px; border:1px solid rgba(245,158,11,0.25); }

/* Cuerpo y Layout */
.legal-body { padding:64px 32px 96px; background:var(--bg); }
.legal-body-inner { max-width:var(--cw); margin:0 auto; display:grid; grid-template-columns:220px 1fr; gap:48px; align-items:start; }
.legal-sidebar { position:sticky; top:100px; max-height:calc(100vh - 130px); overflow-y:auto; padding-right:6px; }
.legal-sidebar::-webkit-scrollbar { width:4px; }
.legal-sidebar::-webkit-scrollbar-thumb { background:var(--border); border-radius:4px; }
.legal-sidebar-label { font-size:0.68rem; font-weight:800; text-transform:uppercase; letter-spacing:.18em; color:var(--muted); margin-bottom:14px; }
.legal-nav-list { list-style:none; padding:0; margin:0; display:flex; flex-direction:column; gap:4px; }
.legal-nav-list a { display:block; font-size:0.85rem; color:var(--muted); text-decoration:none; padding:8px 12px; border-radius:10px; border-left:2px solid transparent; transition:all 0.2s ease; font-weight:600; }
.legal-nav-list a:hover { color:#f59e0b; border-left-color:#f59e0b; background:rgba(245,158,11,0.06); }

.legal-content { display:flex; flex-direction:column; gap:32px; }

/* Tarjetas Informativas */
.ck-card {
    background: var(--card-bg);
    border: 1px solid var(--border);
    border-radius: 20px;
    padding: 32px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.03);
}
.ck-card-header {
    display: flex;
    align-items: center;
    gap: 14px;
    margin-bottom: 20px;
    padding-bottom: 14px;
    border-bottom: 1px solid var(--border);
}
.ck-card-icon {
    width: 42px; height: 42px; border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.3rem; flex-shrink: 0;
}
.ck-card-title {
    margin: 0; font-size: 1.25rem; font-weight: 800; color: var(--text);
}

/* Widget Interactivo de Preferencias */
.ck-widget-card {
    background: var(--card-bg);
    border: 1.5px solid rgba(245,158,11,0.3);
    border-radius: 20px;
    padding: 32px;
    box-shadow: 0 10px 30px rgba(245,158,11,0.08);
}
.ck-widget-status {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 800;
    margin-bottom: 16px;
}
.ck-status-accepted { background: rgba(16,185,129,0.12); color: #10b981; border: 1px solid rgba(16,185,129,0.25); }
.ck-status-denied   { background: rgba(239,68,68,0.12); color: #ef4444; border: 1px solid rgba(239,68,68,0.25); }
.ck-status-unset    { background: rgba(245,158,11,0.12); color: #f59e0b; border: 1px solid rgba(245,158,11,0.25); }

.ck-grid-types {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 16px;
    margin: 20px 0;
}
.ck-type-box {
    background: var(--bg);
    border: 1px solid var(--border);
    border-radius: 14px;
    padding: 18px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    gap: 12px;
}

/* Tabla de cookies */
.ck-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    margin-top: 16px;
}
.ck-table th {
    background: var(--bg);
    color: var(--muted);
    font-size: 0.75rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .06em;
    padding: 12px 16px;
    border-bottom: 1px solid var(--border);
}
.ck-table td {
    padding: 14px 16px;
    border-bottom: 1px solid var(--border);
    font-size: 0.88rem;
    color: var(--text);
    vertical-align: middle;
}
.ck-table tr:last-child td { border-bottom: none; }

/* Grid de Navegadores */
.browser-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 12px;
    margin-top: 16px;
}
.browser-card {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 16px;
    background: var(--bg);
    border: 1px solid var(--border);
    border-radius: 12px;
    color: var(--text);
    text-decoration: none;
    font-weight: 700;
    font-size: 0.88rem;
    transition: all 0.2s ease;
}
.browser-card:hover {
    border-color: #f59e0b;
    color: #f59e0b;
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(245,158,11,0.12);
}

.legal-cta {
    padding: 28px 32px;
    background: rgba(245,158,11,0.06);
    border-radius: 18px;
    border: 1px solid rgba(245,158,11,0.2);
    display: flex;
    align-items: center;
    gap: 20px;
}

@media (max-width:860px) {
    .legal-body-inner { grid-template-columns:1fr; gap:32px; }
    .legal-sidebar { position:static; max-height:none; }
    .ck-card, .ck-widget-card { padding: 24px 18px; }
}
</style>

<div class="legal-page">

<!-- ═══ HERO ════════════════════════════════════════════════════════ -->
<section class="legal-hero">
    <div class="legal-hero-inner">
        <div>
            <div class="legal-hero-icon"><i class="bi bi-cookie"></i></div>
            <p class="legal-hero-eyebrow">CatInk Transparencia</p>
            <h1 class="legal-hero-title">Política de Cookies</h1>
            <div class="legal-hero-meta">
                <i class="bi bi-calendar3"></i>
                <span>Última actualización: <?= htmlspecialchars($meta['fecha_actualizacion'] ?? 'Julio 2026') ?></span>
                <span class="legal-hero-badge">Versión Oficial</span>
            </div>
        </div>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <a href="/privacidad" style="display:inline-flex; align-items:center; gap:8px; font-size:0.85rem; font-weight:700; color:var(--muted); text-decoration:none; border:1px solid var(--border); padding:10px 18px; border-radius:10px; background:var(--card-bg); transition:all 0.2s ease;">
                <i class="bi bi-shield-lock"></i> Aviso de Privacidad
            </a>
            <a href="/terminos" style="display:inline-flex; align-items:center; gap:8px; font-size:0.85rem; font-weight:700; color:var(--muted); text-decoration:none; border:1px solid var(--border); padding:10px 18px; border-radius:10px; background:var(--card-bg); transition:all 0.2s ease;">
                <i class="bi bi-file-earmark-text"></i> Términos y Condiciones
            </a>
        </div>
    </div>
</section>

<!-- ═══ CONTENIDO DE POLÍTICA Y CONTROL ═════════════════════════════ -->
<section class="legal-body">
    <div class="legal-body-inner">
        
        <!-- Sidebar navegable -->
        <aside class="legal-sidebar">
            <div class="legal-sidebar-label">Navegación</div>
            <ul class="legal-nav-list">
                <li><a href="#control-preferencias"><i class="bi bi-sliders me-1"></i> Tus Preferencias</a></li>
                <li><a href="#que-son-cookies"><i class="bi bi-info-circle me-1"></i> ¿Qué son?</a></li>
                <li><a href="#tipos-cookies"><i class="bi bi-grid-fill me-1"></i> Tipos de Cookies</a></li>
                <li><a href="#terceros"><i class="bi bi-pie-chart-fill me-1"></i> Servicios Terceros</a></li>
                <li><a href="#desactivacion"><i class="bi bi-gear-fill me-1"></i> Desactivación</a></li>
                <li><a href="#contacto"><i class="bi bi-envelope-fill me-1"></i> Contacto</a></li>
            </ul>
        </aside>

        <main class="legal-content">

            <!-- 1. Widget Interactivo de Control de Cookies -->
            <div class="ck-widget-card" id="control-preferencias">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                    <h3 style="margin:0; font-size:1.3rem; font-weight:900; color:var(--text);">
                        <i class="bi bi-sliders me-2 text-warning"></i> Centro de Control de Cookies
                    </h3>
                    
                    <?php if ($cookieState === 'aceptadas'): ?>
                        <span class="ck-widget-status ck-status-accepted">
                            <i class="bi bi-check-circle-fill"></i> Cookies Aceptadas
                        </span>
                    <?php elseif ($cookieState === 'negadas'): ?>
                        <span class="ck-widget-status ck-status-denied">
                            <i class="bi bi-dash-circle-fill"></i> Solo Esenciales
                        </span>
                    <?php else: ?>
                        <span class="ck-widget-status ck-status-unset">
                            <i class="bi bi-question-circle-fill"></i> Sin Configurar
                        </span>
                    <?php endif; ?>
                </div>

                <p style="font-size:0.88rem; color:var(--muted); margin-bottom:20px;">
                    Aquí puedes verificar y ajustar tu consentimiento en tiempo real. Los cambios se aplican inmediatamente en tu navegador.
                </p>

                <div class="ck-grid-types">
                    <div class="ck-type-box">
                        <div>
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <strong style="font-size:0.9rem; color:var(--text);"><i class="bi bi-shield-check text-success me-1"></i> Esenciales</strong>
                                <span class="badge" style="background:rgba(16,185,129,0.15); color:#10b981; font-size:0.68rem; font-weight:800; padding:3px 8px; border-radius:10px;">Obligatoria</span>
                            </div>
                            <p style="font-size:0.8rem; color:var(--muted); margin:0; line-height:1.4;">
                                Requeridas para iniciar sesión, guardar lecturas sin conexión y mantener la seguridad de tu navegación.
                            </p>
                        </div>
                    </div>

                    <div class="ck-type-box">
                        <div>
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <strong style="font-size:0.9rem; color:var(--text);"><i class="bi bi-bar-chart-line-fill text-primary me-1"></i> Analíticas</strong>
                                <span class="badge" style="background:rgba(59,130,246,0.15); color:#3b82f6; font-size:0.68rem; font-weight:800; padding:3px 8px; border-radius:10px;">Opcional</span>
                            </div>
                            <p style="font-size:0.8rem; color:var(--muted); margin:0; line-height:1.4;">
                                Nos permiten contabilizar visitas, detectar notas populares y optimizar la velocidad del sitio.
                            </p>
                        </div>
                    </div>

                    <div class="ck-type-box">
                        <div>
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <strong style="font-size:0.9rem; color:var(--text);"><i class="bi bi-badge-ad-fill text-accent me-1"></i> Publicidad & Embebidos</strong>
                                <span class="badge" style="background:rgba(239,51,99,0.15); color:var(--accent); font-size:0.68rem; font-weight:800; padding:3px 8px; border-radius:10px;">Opcional</span>
                            </div>
                            <p style="font-size:0.8rem; color:var(--muted); margin:0; line-height:1.4;">
                                Habilita la reproducción de videos de YouTube/X y anuncios patrocinados relevantes.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="d-flex align-items-center justify-content-end gap-2 flex-wrap mt-3">
                    <button type="button" class="btn btn-outline-secondary px-3 py-2" onclick="negarCookies()" style="border-radius:12px; font-weight:700; font-size:0.85rem;">
                        Aceptar Solo Esenciales
                    </button>
                    <button type="button" class="btn btn-accent px-4 py-2" onclick="aceptarCookies()" style="border-radius:12px; font-weight:800; font-size:0.85rem; box-shadow:0 4px 15px rgba(239,51,99,0.3);">
                        <i class="bi bi-check2-circle me-1"></i> Aceptar Todas las Cookies
                    </button>
                </div>
            </div>

            <!-- 2. ¿Qué son las cookies? -->
            <div class="ck-card" id="que-son-cookies">
                <div class="ck-card-header">
                    <div class="ck-card-icon" style="background:rgba(245,158,11,0.12); color:#f59e0b;">
                        <i class="bi bi-question-lg"></i>
                    </div>
                    <div>
                        <h3 class="ck-card-title">1. ¿Qué son las Cookies?</h3>
                        <span style="font-size:0.8rem; color:var(--muted);">Concepto y almacenamiento local</span>
                    </div>
                </div>
                <p style="font-size:0.92rem; color:var(--text); line-height:1.7; margin-bottom:14px;">
                    Las <strong>cookies</strong> son pequeños archivos de texto que los sitios web almacenan en tu dispositivo (ordenador, teléfono móvil o tableta) cuando los visitas. Permiten que la página recuerde tus acciones y preferencias (como el inicio de sesión, el tema visual o la configuración de idioma) durante un período de tiempo.
                </p>
                <p style="font-size:0.92rem; color:var(--text); line-height:1.7; margin:0;">
                    En <strong>CatInk</strong> también utilizamos tecnologías similares como <code>LocalStorage</code> e <code>IndexedDB</code> para permitir que guardes noticias y continúes leyéndolas incluso cuando no tengas conexión a internet.
                </p>
            </div>

            <!-- 3. Tipos de Cookies -->
            <div class="ck-card" id="tipos-cookies">
                <div class="ck-card-header">
                    <div class="ck-card-icon" style="background:rgba(99,102,241,0.12); color:#6366f1;">
                        <i class="bi bi-grid-3x3-gap-fill"></i>
                    </div>
                    <div>
                        <h3 class="ck-card-title">2. Clasificación de Cookies en CatInk</h3>
                        <span style="font-size:0.8rem; color:var(--muted);">Finalidades y permanencia</span>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="ck-table">
                        <thead>
                            <tr>
                                <th>Categoría</th>
                                <th>Nombre / Clave</th>
                                <th>Propósito</th>
                                <th>Duración</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong style="color:#10b981;">Técnica / Esencial</strong></td>
                                <td><code>cookies_decision</code></td>
                                <td>Almacena si el usuario aceptó o denegó las cookies.</td>
                                <td>1 año</td>
                            </tr>
                            <tr>
                                <td><strong style="color:#10b981;">Técnica / Esencial</strong></td>
                                <td><code>PHPSESSID</code></td>
                                <td>Mantiene activa la sesión de usuario y lector en la plataforma.</td>
                                <td>Sesión</td>
                            </tr>
                            <tr>
                                <td><strong style="color:#3b82f6;">Analítica</strong></td>
                                <td><code>_ga</code> / <code>_gid</code></td>
                                <td>Métricas anónimas de visitas de Google Analytics.</td>
                                <td>2 años</td>
                            </tr>
                            <tr>
                                <td><strong style="color:var(--accent);">Publicidad / Contenido</strong></td>
                                <td><code>gads</code> / <code>gac</code></td>
                                <td>Banners publicitarios adaptados mediante Google AdSense.</td>
                                <td>13 meses</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 4. Terceros -->
            <div class="ck-card" id="terceros">
                <div class="ck-card-header">
                    <div class="ck-card-icon" style="background:rgba(16,185,129,0.12); color:#10b981;">
                        <i class="bi bi-share-fill"></i>
                    </div>
                    <div>
                        <h3 class="ck-card-title">3. Proveedores y Servicios de Terceros</h3>
                        <span style="font-size:0.8rem; color:var(--muted);">Integraciones externas</span>
                    </div>
                </div>
                <p style="font-size:0.9rem; color:var(--text); line-height:1.6;">
                    CatInk integra servicios de terceros confiables para enriquecer las notas informativas:
                </p>
                <ul style="font-size:0.88rem; color:var(--text); line-height:1.8; padding-left:20px; margin:0;">
                    <li><strong>Google Analytics & AdSense:</strong> Análisis de tráfico y despliegue de publicidad relevante.</li>
                    <li><strong>YouTube & Twitter / X:</strong> Embebidos interactivos de videos y publicaciones oficiales.</li>
                    <li><strong>Hostinger Infrastructure:</strong> Garantía de conexión segura mediante HTTPS / TLS.</li>
                </ul>
            </div>

            <!-- 5. Contenido CMS Adicional -->
            <?php if (!empty($row['contenido_pag'])): ?>
                <div class="ck-card">
                    <div class="post-content">
                        <div class="ql-editor">
                            <?php echo $row['contenido_pag']; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- 6. Desactivación en Navegador -->
            <div class="ck-card" id="desactivacion">
                <div class="ck-card-header">
                    <div class="ck-card-icon" style="background:rgba(239,51,99,0.12); color:var(--accent);">
                        <i class="bi bi-gear-wide-connected"></i>
                    </div>
                    <div>
                        <h3 class="ck-card-title">4. Desactivación desde tu Navegador</h3>
                        <span style="font-size:0.8rem; color:var(--muted);">Cómo bloquear o borrar cookies manualmente</span>
                    </div>
                </div>
                <p style="font-size:0.9rem; color:var(--text); line-height:1.6;">
                    Puedes restringir, bloquear o borrar las cookies de CatInk o cualquier otro sitio web configurando las opciones de tu navegador:
                </p>
                <div class="browser-grid">
                    <a href="https://support.google.com/chrome/answer/95647" target="_blank" class="browser-card">
                        <i class="bi bi-browser-chrome" style="font-size:1.3rem; color:#4285F4;"></i> Google Chrome
                    </a>
                    <a href="https://support.apple.com/es-es/guide/safari/sfri11471/mac" target="_blank" class="browser-card">
                        <i class="bi bi-compass" style="font-size:1.3rem; color:#0066CC;"></i> Apple Safari
                    </a>
                    <a href="https://support.mozilla.org/es/kb/habilitar-y-deshabilitar-cookies-sitios-web-rastrear-preferencias" target="_blank" class="browser-card">
                        <i class="bi bi-browser-firefox" style="font-size:1.3rem; color:#FF7139;"></i> Mozilla Firefox
                    </a>
                    <a href="https://support.microsoft.com/es-es/microsoft-edge/eliminar-las-cookies-en-microsoft-edge-63947406-400c-4fa6-a3b0-977835102a19" target="_blank" class="browser-card">
                        <i class="bi bi-browser-edge" style="font-size:1.3rem; color:#0078D7;"></i> Microsoft Edge
                    </a>
                </div>
            </div>

            <!-- 7. Contacto -->
            <div class="legal-cta" id="contacto">
                <div style="width:48px; height:48px; border-radius:14px; background:rgba(245,158,11,0.15); color:#f59e0b; display:flex; align-items:center; justify-content:center; font-size:1.4rem; flex-shrink:0;">
                    <i class="bi bi-headset"></i>
                </div>
                <div class="flex-fill">
                    <strong style="font-size:0.95rem; color:var(--text); display:block; margin-bottom:2px;">¿Tienes dudas sobre nuestra política de cookies?</strong>
                    <p style="margin:0; font-size:0.85rem; color:var(--muted);">Estamos disponibles para resolver cualquier inquietud sobre tus datos.</p>
                </div>
                <a href="/contactanos" class="btn btn-accent px-4 py-2" style="border-radius:12px; font-weight:800; font-size:0.85rem; white-space:nowrap; text-decoration:none;">
                    Contáctanos <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>

        </main>

    </div>
</section>

</div>

<?php
include("./../layout/footer.php");
?>