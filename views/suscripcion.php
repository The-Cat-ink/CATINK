<?php
include_once(__DIR__ . "/../layout/header.php");
include_once(__DIR__ . "/../data/conexion.php");

$resPag = @$con->query("SELECT contenido_pag, meta_json FROM paginas WHERE nombre_pag='suscripcion'");
if (!$resPag) {
    $resPag = @$con->query("SELECT contenido_pag FROM paginas WHERE nombre_pag='suscripcion'");
}
$row  = ($resPag && $resPag !== true && method_exists($resPag, 'fetch_assoc')) ? $resPag->fetch_assoc() : [];
$meta = json_decode($row['meta_json'] ?? '', true) ?: [];

$beneficios = $meta['beneficios'] ?? [
    ['icono' => 'bi-lightning-charge-fill', 'titulo' => 'Noticias antes que nadie', 'desc' => 'Sé el primero en enterarte de anuncios, trailers y lanzamientos del mundo geek.'],
    ['icono' => 'bi-gift-fill', 'titulo' => 'Contenido exclusivo', 'desc' => 'Artículos, análisis y reseñas especiales solo para suscriptores del newsletter.'],
    ['icono' => 'bi-slash-circle', 'titulo' => 'Cero spam, siempre', 'desc' => 'Nos importa tu bandeja. Solo enviamos lo que vale la pena leer, una vez por semana.'],
    ['icono' => 'bi-door-open-fill', 'titulo' => '100% gratuito', 'desc' => 'Sin planes premium, sin tarjetas. Solo tu correo y tu pasión por la cultura pop.']
];
?>

<style>
.sus-page { --cw: 1080px; }

/* ─── Hero compacto ─────────────────────────────────────────────── */
.sus-hero {
    padding: 72px 32px 0;
    background: var(--bg);
    text-align: center;
}
.sus-hero-eyebrow {
    display: inline-flex; align-items: center; gap: 8px;
    background: rgba(239,51,99,0.1); color: var(--accent);
    font-size: 0.7rem; font-weight: 800; letter-spacing: .18em;
    text-transform: uppercase; padding: 6px 14px; border-radius: 30px;
    border: 1px solid rgba(239,51,99,0.2); margin-bottom: 20px;
}
.sus-hero-title {
    font-size: clamp(2rem, 5vw, 3.2rem);
    font-weight: 900; color: var(--text);
    margin: 0 0 14px; line-height: 1.1;
}
.sus-hero-title span { color: var(--accent); }
.sus-hero-sub {
    font-size: 1.05rem; color: var(--muted);
    max-width: 500px; margin: 0 auto; line-height: 1.65;
}

/* ─── Layout Split ──────────────────────────────────────────────── */
.sus-main {
    padding: 56px 32px 96px;
    background: var(--bg);
}
.sus-inner {
    max-width: var(--cw);
    margin: 0 auto;
    display: grid;
    grid-template-columns: 1fr 440px;
    gap: 64px;
    align-items: start;
}

/* ─── Columna izquierda ─────────────────────────────────────────── */
.sus-left-title {
    font-size: 1.5rem; font-weight: 900;
    color: var(--text); margin: 0 0 8px;
}
.sus-left-sub {
    font-size: 0.95rem; color: var(--muted);
    line-height: 1.65; margin: 0 0 36px;
}
.sus-benefits { list-style: none; padding: 0; margin: 0 0 40px; display: flex; flex-direction: column; gap: 18px; }
.sus-benefit-item {
    display: flex; align-items: flex-start; gap: 16px;
}
.sus-benefit-icon {
    width: 44px; height: 44px; border-radius: 12px;
    background: var(--card-bg); border: 1px solid var(--border);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.2rem; color: var(--accent); flex-shrink: 0;
}
.sus-benefit-text h4 { margin: 0 0 3px; font-size: 0.92rem; font-weight: 800; color: var(--text); }
.sus-benefit-text p { margin: 0; font-size: 0.84rem; color: var(--muted); line-height: 1.5; }
.sus-divider { height: 1px; background: var(--border); margin: 32px 0; }
.sus-social-label { font-size: 0.7rem; font-weight: 800; text-transform: uppercase; letter-spacing: .18em; color: var(--muted); margin-bottom: 14px; }
.sus-social-links { display: flex; gap: 10px; flex-wrap: wrap; }
.sus-social-btn {
    display: inline-flex; align-items: center; gap: 7px;
    padding: 9px 16px; border-radius: 10px; border: 1px solid var(--border);
    font-size: 0.82rem; font-weight: 700; text-decoration: none;
    color: var(--text); background: var(--card-bg);
    transition: all 0.2s ease;
}
.sus-social-btn:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(0,0,0,0.1); }
.sus-social-btn.fb:hover { border-color:#1877F2; color:#1877F2; }
.sus-social-btn.ig:hover { border-color:#E1306C; color:#E1306C; }
.sus-social-btn.tt:hover { border-color:#00f2ea; color:#00f2ea; }
.sus-social-btn.yt:hover { border-color:#FF0000; color:#FF0000; }
.sus-social-btn.x:hover  { border-color:var(--text); }

/* ─── Formulario (derecha) ──────────────────────────────────────── */
.sus-form-card {
    background: var(--card-bg);
    border: 1px solid var(--border);
    border-radius: 24px;
    padding: 40px 36px;
    position: sticky;
    top: 90px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.06);
}
.sus-form-title {
    font-size: 1.25rem; font-weight: 900;
    color: var(--text); margin: 0 0 6px;
}
.sus-form-sub { font-size: 0.85rem; color: var(--muted); margin: 0 0 28px; }
.sus-form-label {
    display: block; font-size: 0.75rem; font-weight: 800;
    text-transform: uppercase; letter-spacing: .12em;
    color: var(--muted); margin-bottom: 6px;
}
.sus-form-input, .sus-form-select {
    width: 100%; padding: 12px 14px;
    background: var(--bg); border: 1px solid var(--border);
    border-radius: 10px; color: var(--text);
    font-size: 0.95rem; outline: none;
    transition: border-color 0.2s ease;
    box-sizing: border-box;
    font-family: inherit;
}
.sus-form-input:focus, .sus-form-select:focus { border-color: var(--accent); }
.sus-form-group { margin-bottom: 18px; }
.sus-form-btn {
    width: 100%; padding: 14px;
    background: var(--accent); color: #fff;
    border: none; border-radius: 12px;
    font-size: 0.95rem; font-weight: 800;
    letter-spacing: .04em; cursor: pointer;
    display: flex; align-items: center; justify-content: center; gap: 8px;
    margin-top: 8px; transition: all 0.25s ease;
}
.sus-form-btn:hover { opacity: 0.9; transform: translateY(-2px); box-shadow: 0 8px 24px rgba(239,51,99,0.3); }
.sus-privacy-note {
    font-size: 0.76rem; color: var(--muted);
    text-align: center; margin-top: 14px; line-height: 1.5;
}
.sus-privacy-note a { color: var(--accent); text-decoration: none; }

/* ─── Responsive ─────────────────────────────────────────────── */
@media (max-width: 860px) { .sus-inner { grid-template-columns: 1fr; gap: 40px; } .sus-form-card { position: static; } }
@media (max-width: 600px) { .sus-hero { padding: 52px 20px 0; } .sus-main { padding: 40px 20px 64px; } .sus-form-card { padding: 28px 22px; } }
</style>

<div class="sus-page">

<!-- Hero -->
<section class="sus-hero">
    <div class="sus-hero-eyebrow"><i class="bi bi-envelope-heart-fill"></i> <?= htmlspecialchars($meta['eyebrow'] ?? 'Newsletter') ?></div>
    <h1 class="sus-hero-title"><?= htmlspecialchars($meta['hero_title'] ?? 'Únete a la comunidad CatInk') ?></h1>
    <p class="sus-hero-sub"><?= htmlspecialchars($meta['hero_sub'] ?? 'Recibe contenido exclusivo de Anime, Manga, Cine y Videojuegos directamente en tu correo. Sin spam, solo lo mejor.') ?></p>
</section>

<!-- Main Split -->
<section class="sus-main">
    <div class="sus-inner">

        <!-- Columna izquierda -->
        <div>
            <h2 class="sus-left-title">¿Por qué suscribirte?</h2>
            <p class="sus-left-sub">Miles de fans de la cultura geek ya reciben nuestro newsletter semanal. No te quedes fuera del loop.</p>

            <ul class="sus-benefits">
                <?php foreach($beneficios as $ben): 
                    $ic = trim($ben['icono'] ?? 'bi-check-circle-fill');
                    $isImg = (strpos($ic, 'http://') === 0 || strpos($ic, 'https://') === 0 || strpos($ic, 'data:image') === 0 || strpos($ic, '/') === 0 || strpos($ic, 'img/') === 0 || preg_match('/\.(png|jpg|jpeg|svg|webp)$/i', $ic));
                ?>
                <li class="sus-benefit-item">
                    <div class="sus-benefit-icon">
                        <?php if ($isImg): ?>
                            <img src="<?= htmlspecialchars($ic) ?>" alt="" style="width:24px; height:24px; object-fit:contain; border-radius:4px;">
                        <?php else: ?>
                            <i class="bi <?= htmlspecialchars($ic) ?>"></i>
                        <?php endif; ?>
                    </div>
                    <div class="sus-benefit-text">
                        <h4><?= htmlspecialchars($ben['titulo'] ?? '') ?></h4>
                        <p><?= htmlspecialchars($ben['desc'] ?? '') ?></p>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>

            <div class="sus-divider"></div>
            <p class="sus-social-label">Síguenos también en</p>
            <div class="sus-social-links">
                <a href="https://www.facebook.com/catink.mx" target="_blank" rel="noopener" class="sus-social-btn fb"><i class="bi bi-facebook"></i> Facebook</a>
                <a href="https://www.instagram.com/catink.mx" target="_blank" rel="noopener" class="sus-social-btn ig"><i class="bi bi-instagram"></i> Instagram</a>
                <a href="https://www.tiktok.com/@catink.mx" target="_blank" rel="noopener" class="sus-social-btn tt"><i class="bi bi-tiktok"></i> TikTok</a>
                <a href="https://www.youtube.com/@catink" target="_blank" rel="noopener" class="sus-social-btn yt"><i class="bi bi-youtube"></i> YouTube</a>
            </div>
        </div>

        <!-- Formulario -->
        <div>
            <div class="sus-form-card">
                <h3 class="sus-form-title"><?= htmlspecialchars($meta['form_title'] ?? 'Suscríbete gratis') ?></h3>
                <p class="sus-form-sub"><?= htmlspecialchars($meta['form_sub'] ?? 'Empieza a recibir el mejor contenido geek hoy mismo.') ?></p>

                <div id="susToast" style="display:none; padding:12px 16px; border-radius:10px; margin-bottom:20px; font-size:0.88rem; font-weight:600;"></div>

                <form id="formSuscripcion" action="./../controllers/suscribirse.php" method="POST">
                    <div class="sus-form-group">
                        <label class="sus-form-label" for="sus-nombre">Nombre *</label>
                        <input type="text" id="sus-nombre" name="nombre" class="sus-form-input" placeholder="Tu nombre" required>
                    </div>
                    <div class="sus-form-group">
                        <label class="sus-form-label" for="sus-email">Correo electrónico *</label>
                        <input type="email" id="sus-email" name="email" class="sus-form-input" placeholder="tucorreo@ejemplo.com" required>
                    </div>
                    <div class="sus-form-group">
                        <label class="sus-form-label" for="sus-sexo">Género</label>
                        <select id="sus-sexo" name="sexo" class="sus-form-select">
                            <option value="masculino">Masculino</option>
                            <option value="femenino">Femenino</option>
                            <option value="otro">Prefiero no decirlo</option>
                        </select>
                    </div>
                    <button type="submit" class="sus-form-btn" id="btnSuscribirse">
                        <i class="bi bi-envelope-check-fill"></i> Suscribirme ahora
                    </button>
                    <p class="sus-privacy-note">
                        Al suscribirte aceptas nuestra <a href="/privacidad">Política de Privacidad</a>. Puedes darte de baja en cualquier momento.
                    </p>
                </form>
            </div>
        </div>

    </div>
</section>

</div>

<script>
(function() {
    const form = document.getElementById('formSuscripcion');
    const toast = document.getElementById('susToast');
    const btn = document.getElementById('btnSuscribirse');

    <?php if(isset($_GET['success'])): ?>
    toast.style.display = 'block';
    toast.style.background = 'rgba(16,185,129,0.12)';
    toast.style.color = '#10b981';
    toast.style.border = '1px solid rgba(16,185,129,0.25)';
    toast.textContent = '✓ ¡Suscripción registrada con éxito! Bienvenido a la comunidad CatInk.';
    <?php elseif(isset($_GET['error'])): ?>
    toast.style.display = 'block';
    toast.style.background = 'rgba(239,51,99,0.1)';
    toast.style.color = 'var(--accent)';
    toast.style.border = '1px solid rgba(239,51,99,0.2)';
    toast.textContent = '✕ Hubo un error al procesar tu suscripción. Intenta de nuevo.';
    <?php endif; ?>
})();
</script>

<?php include("./../layout/footer.php"); ?>