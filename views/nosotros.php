<?php
include("./../layout/header.php");
include("./../data/conexion.php");

$row  = $con->query("SELECT contenido_pag FROM paginas WHERE nombre_pag='nosotros'")->fetch_assoc();
$logos = $con->query("SELECT * FROM logos_marcas WHERE activo=1 ORDER BY creado ASC")->fetch_all(MYSQLI_ASSOC);

// Configuración de 4 filas horizontales
// Cada fila lleva todos los logos (duplicados para loop) con stagger de posición
$card_w   = 150; // ancho fijo por tarjeta (px)
$card_gap = 12;  // gap entre tarjetas (px)
$px_per_s = 78;  // velocidad moderada horizontal

$n_logos   = count($logos);
$set_width = $n_logos * ($card_w + $card_gap);
$duration  = max(10, round($set_width / $px_per_s, 1));
$stagger   = round($duration / 4, 1);

// Cada fila: dirección + delay negativo para offset visual
$rows_cfg = [
    ['dir' => 'row-left',  'delay' => 0],
    ['dir' => 'row-right', 'delay' => -$stagger],
    ['dir' => 'row-left',  'delay' => -$stagger * 2],
];
?>

<!-- ═══ HERO ═══════════════════════════════════════════════════════ -->
<section class="nos-hero">
    <div class="nos-hero-inner">
        <h1 class="nos-hero-title">Sobre Nosotros</h1>
        <div class="nos-hero-line"></div>
    </div>
</section>

<!-- ═══ CONTENIDO ══════════════════════════════════════════════════ -->
<section class="nos-body">
    <div class="nos-body-inner">
        <div class="post-content">
            <div class="ql-editor">
                <?php echo $row['contenido_pag'] ?? ''; ?>
            </div>
        </div>
    </div>
</section>

<!-- ═══ MARCAS ═════════════════════════════════════════════════════ -->
<?php if (!empty($logos)): ?>
<section class="nos-brands">
    <p class="nos-brands-label">Marcas con las que colaboramos</p>

    <div class="nos-brands-rows">
        <?php foreach ($rows_cfg as $row_cfg): ?>
        <div class="nos-row-wrap">
            <div class="nos-row-track <?= $row_cfg['dir'] ?>"
                 style="animation-duration:<?= $duration ?>s; animation-delay:<?= $row_cfg['delay'] ?>s">
                <?php for ($r = 0; $r < 2; $r++): foreach ($logos as $logo): ?>
                <div class="nos-logo-card" <?= $logo['nombre'] ? 'title="'.htmlspecialchars($logo['nombre']).'"' : '' ?>>
                    <img src="<?= imageUrl($logo['imagen']) ?>"
                         alt="<?= htmlspecialchars($logo['nombre']) ?>"
                         loading="lazy">
                </div>
                <?php endforeach; endfor; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<style>
/* ─── Hero ─────────────────────────────────────────────────────── */
.nos-hero {
    padding: 52px 32px 36px;
    border-bottom: 1px solid var(--border);
}
.nos-hero-inner {
    max-width: var(--cw, 1040px);
    margin: 0 auto;
}
.nos-hero-tag {
    display: inline-block;
    font-size: 0.68rem;
    font-weight: 700;
    letter-spacing: .16em;
    text-transform: uppercase;
    color: var(--accent);
    margin-bottom: 10px;
}
.nos-hero-title {
    font-size: clamp(2rem, 5vw, 3rem);
    font-weight: 800;
    line-height: 1.1;
    color: var(--text);
    margin: 0 0 18px;
}
.nos-hero-line {
    width: 52px;
    height: 4px;
    background: var(--accent);
    border-radius: 2px;
}

/* ─── Cuerpo de texto ──────────────────────────────────────────── */
.nos-body {
    padding: 40px 32px 56px;
}
.nos-body-inner {
    max-width: var(--cw, 1040px);
    margin: 0 auto;
}

/* ─── Sección de marcas ────────────────────────────────────────── */
.nos-brands {
    border-top: 1px solid var(--border);
    padding: 52px 0 68px;
    overflow: hidden;
}
.nos-brands-label {
    text-align: center;
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: .16em;
    text-transform: uppercase;
    color: var(--muted);
    margin-bottom: 36px;
    padding: 0 24px;
}

/* ─── Filas horizontales ───────────────────────────────────────── */
.nos-brands-rows {
    display: flex;
    flex-direction: column;
    gap: 30px;
    overflow: hidden;
    /* Fade izquierda/derecha */
    mask-image: linear-gradient(
        to right,
        transparent 0%,
        #000 6%,
        #000 94%,
        transparent 100%
    );
    -webkit-mask-image: linear-gradient(
        to right,
        transparent 0%,
        #000 6%,
        #000 94%,
        transparent 100%
    );
}

/* ─── Fila individual ──────────────────────────────────────────── */
.nos-row-wrap {
    overflow: hidden;
}
.nos-row-track {
    display: flex;
    flex-direction: row;
    flex-wrap: nowrap;
    gap: 12px;
    width: fit-content;
    will-change: transform;
}
.nos-row-track.row-left {
    animation: nos-left linear infinite;
}
.nos-row-track.row-right {
    animation: nos-right linear infinite;
}
@keyframes nos-left {
    from { transform: translateX(0); }
    to   { transform: translateX(-50%); }
}
@keyframes nos-right {
    from { transform: translateX(-50%); }
    to   { transform: translateX(0); }
}

/* Pausar al pasar el cursor sobre una fila */
.nos-row-wrap:hover .nos-row-track {
    animation-play-state: paused;
}

/* ─── Tarjeta de logo ──────────────────────────────────────────── */
.nos-logo-card {
    background: var(--card-bg);
    border: 1px solid var(--border);
    border-radius: 10px;
    width: 200px;
    height: 100px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 12px 16px;
    flex-shrink: 0;
    transition: border-color .4s, transform .4s;
}
.nos-logo-card:hover {
    border-color: var(--accent);
    transform: scale(1.04);
}
.nos-logo-card img {
    max-height: 70px;
    max-width: 118px;
    object-fit: contain;
    display: block;
    filter: grayscale(15%);
    transition: filter .2s;
}
.nos-logo-card:hover img {
    filter: none;
}

/* ─── Responsive ───────────────────────────────────────────────── */
@media (max-width: 900px) {
    /* En móvil mostrar solo 2 filas */
    .nos-brands-rows .nos-row-wrap:nth-child(3),
    .nos-brands-rows .nos-row-wrap:nth-child(4) {
        display: none;
    }
    .nos-logo-card { width: 130px; height: 68px; }
}
@media (max-width: 600px) {
    .nos-hero  { padding: 36px 20px 28px; }
    .nos-body  { padding: 28px 20px 40px; }
    .nos-logo-card { width: 110px; height: 62px; }
}
</style>

<?php include("./../layout/footer.php"); ?>
