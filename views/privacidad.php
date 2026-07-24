<?php
include("./../layout/header.php");
include("./../data/conexion.php");

$sql = "SELECT contenido_pag, meta_json FROM paginas WHERE nombre_pag='privacidad'";
$result = $con->query($sql);
$row = $result && $result->num_rows > 0 ? $result->fetch_assoc() : null;
$meta = json_decode($row['meta_json'] ?? '', true) ?: [];
?>

<style>
/* Reutiliza exactamente los estilos de terminos.php — solo varía el color del hero */
.legal-page { --cw: 860px; }
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
    background: radial-gradient(ellipse 60% 80% at 90% 40%, rgba(99,102,241,0.08), transparent 65%);
    pointer-events: none;
}
.legal-hero-inner {
    max-width: 1080px;
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
    background: rgba(99,102,241,0.1);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.8rem; color: #6366f1; margin-bottom: 20px;
    border: 1px solid rgba(99,102,241,0.2);
}
.legal-hero-eyebrow { font-size:0.7rem; font-weight:800; letter-spacing:.2em; text-transform:uppercase; color:#6366f1; margin-bottom:10px; }
.legal-hero-title { font-size:clamp(1.9rem,4vw,2.8rem); font-weight:900; color:var(--text); margin:0 0 12px; line-height:1.1; }
.legal-hero-meta { font-size:0.85rem; color:var(--muted); display:flex; align-items:center; gap:8px; }
.legal-hero-badge { background:rgba(99,102,241,0.1); color:#6366f1; font-size:0.72rem; font-weight:700; padding:4px 10px; border-radius:20px; border:1px solid rgba(99,102,241,0.2); }
.legal-body { padding:64px 32px 96px; background:var(--bg); }
.legal-body-inner { max-width:1080px; margin:0 auto; display:grid; grid-template-columns:220px 1fr; gap:64px; align-items:start; }
.legal-sidebar { position:sticky; top:90px; }
.legal-sidebar-label { font-size:0.68rem; font-weight:800; text-transform:uppercase; letter-spacing:.18em; color:var(--muted); margin-bottom:14px; }
.legal-nav-list { list-style:none; padding:0; margin:0; display:flex; flex-direction:column; gap:4px; }
.legal-nav-list a { display:block; font-size:0.85rem; color:var(--muted); text-decoration:none; padding:7px 12px; border-radius:8px; border-left:2px solid transparent; transition:all 0.2s ease; font-weight:600; }
.legal-nav-list a:hover { color:#6366f1; border-left-color:#6366f1; background:rgba(99,102,241,0.06); }
.legal-content { background:var(--card-bg); border:1px solid var(--border); border-radius:20px; padding:48px 44px; }
.legal-content .ql-editor { padding:0; font-size:1rem; line-height:1.8; color:var(--text); }
.legal-content .ql-editor h2 { font-size:1.2rem; font-weight:800; margin-top:2.2em; margin-bottom:0.6em; padding-bottom:8px; border-bottom:2px solid var(--border); }
.legal-content .ql-editor p { margin-bottom:1em; }
.legal-content .ql-editor ul,.legal-content .ql-editor ol { padding-left:1.4em; margin-bottom:1em; }
.legal-content .ql-editor a { color:#6366f1; }
.legal-cta { margin-top:48px; padding:28px 32px; background:rgba(99,102,241,0.07); border-radius:16px; border:1px solid rgba(99,102,241,0.15); display:flex; align-items:center; gap:16px; }
.legal-cta i { font-size:1.4rem; color:#6366f1; flex-shrink:0; }
.legal-cta p { margin:0; font-size:0.88rem; color:var(--muted); line-height:1.5; }
.legal-cta a { color:var(--accent); font-weight:700; text-decoration:none; }
@media (max-width:860px) { .legal-body-inner{grid-template-columns:1fr;gap:32px;} .legal-sidebar{position:static;} .legal-content{padding:28px 22px;} }
@media (max-width:600px) { .legal-hero{padding:48px 20px 36px;} .legal-body{padding:40px 20px 64px;} }
</style>

<div class="legal-page">

<!-- ═══ HERO ════════════════════════════════════════════════════════ -->
<section class="legal-hero">
    <div class="legal-hero-inner">
        <div>
            <div class="legal-hero-icon"><i class="bi bi-shield-lock-fill"></i></div>
            <p class="legal-hero-eyebrow">Privacidad & Datos</p>
            <h1 class="legal-hero-title"><?= htmlspecialchars($meta['hero_title'] ?? 'Aviso de Privacidad') ?></h1>
            <div class="legal-hero-meta">
                <i class="bi bi-calendar3"></i>
                <span>Última actualización: <?= htmlspecialchars($meta['fecha_actualizacion'] ?? 'Julio 2025') ?></span>
                <span class="legal-hero-badge">Versión vigente</span>
            </div>
        </div>
        <div style="text-align:right; flex-shrink:0;">
            <a href="/terminos" style="display:inline-flex; align-items:center; gap:8px; font-size:0.85rem; font-weight:700; color:var(--muted); text-decoration:none; border:1px solid var(--border); padding:10px 18px; border-radius:10px; transition:all 0.2s ease;" onmouseover="this.style.borderColor='#6366f1';this.style.color='#6366f1'" onmouseout="this.style.borderColor='var(--border)';this.style.color='var(--muted)'">
                <i class="bi bi-file-earmark-text"></i> Términos y Condiciones
            </a>
        </div>
    </div>
</section>

<!-- ═══ CONTENIDO ════════════════════════════════════════════════════ -->
<section class="legal-body">
    <div class="legal-body-inner">

        <aside class="legal-sidebar">
            <p class="legal-sidebar-label">En este documento</p>
            <ul class="legal-nav-list">
                <li><a href="#"><i class="bi bi-dot"></i> Responsable</a></li>
                <li><a href="#"><i class="bi bi-dot"></i> Datos recabados</a></li>
                <li><a href="#"><i class="bi bi-dot"></i> Finalidad</a></li>
                <li><a href="#"><i class="bi bi-dot"></i> Transferencias</a></li>
                <li><a href="#"><i class="bi bi-dot"></i> Derechos ARCO</a></li>
                <li><a href="#"><i class="bi bi-dot"></i> Cookies</a></li>
                <li><a href="#"><i class="bi bi-dot"></i> Cambios</a></li>
            </ul>
        </aside>

        <div>
            <div class="legal-content">
                <div class="post-content">
                    <div class="ql-editor">
                        <?php if ($row): echo $row['contenido_pag']; else: ?>
                        <h2>Aviso de Privacidad</h2>
                        <p>El contenido del Aviso de Privacidad no ha sido configurado aún. Puedes editarlo desde el panel de administración en <strong>Gestión → Páginas</strong>.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="legal-cta">
                <i class="bi bi-shield-check"></i>
                <p>¿Deseas ejercer tus derechos ARCO o tienes preguntas sobre el uso de tus datos? <a href="/contactanos">Contáctanos</a> y te atendemos a la brevedad.</p>
            </div>
        </div>

    </div>
</section>

</div>

<?php include("./../layout/footer.php"); ?>