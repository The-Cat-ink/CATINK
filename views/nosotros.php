<?php
include_once(__DIR__ . "/../layout/header.php");
include_once(__DIR__ . "/../data/conexion.php");

$resPag = @$con->query("SELECT contenido_pag, meta_json FROM paginas WHERE nombre_pag='nosotros'");
if (!$resPag) {
    $resPag = @$con->query("SELECT contenido_pag FROM paginas WHERE nombre_pag='nosotros'");
}
$row  = ($resPag && $resPag !== true && method_exists($resPag, 'fetch_assoc')) ? $resPag->fetch_assoc() : [];
$meta = json_decode($row['meta_json'] ?? '', true) ?: [];

$resLogos = @$con->query("SELECT * FROM logos_marcas WHERE (fecha_expiracion IS NULL OR fecha_expiracion > NOW()) ORDER BY orden ASC, creado ASC");
$logos    = ($resLogos && $resLogos !== true && method_exists($resLogos, 'fetch_all')) ? $resLogos->fetch_all(MYSQLI_ASSOC) : [];

$card_w   = 200;
$card_gap = 12;
$px_per_s = 60;
$filas    = array_chunk($logos, 7);
$rows_cfg = [];
foreach ($filas as $i => $fila_logos) {
    $set_w = count($fila_logos) * ($card_w + $card_gap);
    if ($set_w > 0) {
        $copies   = max(4, (int)ceil(3600 / $set_w));
        if ($copies % 2 !== 0) $copies++;
        $duration = max(8, round(($copies / 2) * $set_w / $px_per_s, 1));
        $rows_cfg[] = [
            'logos'    => $fila_logos,
            'dir'      => $i % 2 === 0 ? 'row-left' : 'row-right',
            'duration' => $duration,
            'copies'   => $copies,
        ];
    }
}
?>

<style>
/* ─── Tokens y Reset ──────────────────────────────────────────── */
.nos-page { --cw: 1080px; }

/* ─── Hero ─────────────────────────────────────────────────────── */
.nos-hero {
    position: relative;
    padding: 90px 32px 80px;
    overflow: hidden;
    background: var(--card-bg);
    border-bottom: 1px solid var(--border);
}
.nos-hero::before {
    content: '';
    position: absolute;
    inset: 0;
    background: radial-gradient(ellipse 70% 80% at 80% 50%, rgba(239,51,99,0.12), transparent 70%),
                radial-gradient(ellipse 50% 60% at 10% 80%, rgba(239,51,99,0.07), transparent 60%);
    pointer-events: none;
}
.nos-hero-inner {
    max-width: var(--cw);
    margin: 0 auto;
    position: relative;
    z-index: 1;
}
.nos-hero-eyebrow {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(239,51,99,0.1);
    color: var(--accent);
    font-size: 0.72rem;
    font-weight: 800;
    letter-spacing: .18em;
    text-transform: uppercase;
    padding: 6px 14px;
    border-radius: 30px;
    margin-bottom: 24px;
    border: 1px solid rgba(239,51,99,0.2);
}
.nos-hero-title {
    font-size: clamp(2.4rem, 6vw, 4rem);
    font-weight: 900;
    line-height: 1.08;
    color: var(--text);
    margin: 0 0 20px;
    max-width: 640px;
}
.nos-hero-title span { color: var(--accent); }
.nos-hero-sub {
    font-size: 1.1rem;
    color: var(--muted);
    max-width: 540px;
    line-height: 1.65;
    margin: 0 0 36px;
}
.nos-hero-stats {
    display: flex;
    gap: 40px;
    flex-wrap: wrap;
}
.nos-stat-item { display: flex; flex-direction: column; gap: 2px; }
.nos-stat-num {
    font-size: 1.9rem;
    font-weight: 900;
    color: var(--accent);
    line-height: 1;
}
.nos-stat-label { font-size: 0.8rem; color: var(--muted); font-weight: 600; text-transform: uppercase; letter-spacing: .08em; }

/* ─── Tarjetas Misión/Visión/Valores ───────────────────────────── */
.nos-cards-section {
    padding: 72px 32px;
    background: var(--bg);
}
.nos-cards-inner {
    max-width: var(--cw);
    margin: 0 auto;
}
.nos-section-label {
    font-size: 0.7rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .2em;
    color: var(--accent);
    margin-bottom: 8px;
}
.nos-section-title {
    font-size: clamp(1.6rem, 3vw, 2.2rem);
    font-weight: 900;
    color: var(--text);
    margin: 0 0 48px;
}
.nos-cards-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 24px;
}
.nos-card {
    background: var(--card-bg);
    border: 1px solid var(--border);
    border-radius: 20px;
    padding: 32px 28px;
    position: relative;
    overflow: hidden;
    transition: transform 0.25s ease, box-shadow 0.25s ease;
}
.nos-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 3px;
    background: var(--accent);
    transform: scaleX(0);
    transform-origin: left;
    transition: transform 0.3s ease;
}
.nos-card:hover { transform: translateY(-4px); box-shadow: 0 16px 48px rgba(239,51,99,0.12); }
.nos-card:hover::before { transform: scaleX(1); }
.nos-card-icon {
    width: 52px;
    height: 52px;
    border-radius: 14px;
    background: rgba(239,51,99,0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    margin-bottom: 20px;
    color: var(--accent);
}
.nos-card-title {
    font-size: 1.1rem;
    font-weight: 800;
    color: var(--text);
    margin: 0 0 10px;
    text-transform: uppercase;
    letter-spacing: .04em;
}
.nos-card-desc {
    font-size: 0.92rem;
    color: var(--muted);
    line-height: 1.7;
    margin: 0;
}

/* ─── Contenido Editor ──────────────────────────────────────────── */
.nos-content-section {
    padding: 60px 32px 72px;
    background: var(--card-bg);
    border-top: 1px solid var(--border);
    border-bottom: 1px solid var(--border);
}
.nos-content-inner {
    max-width: var(--cw);
    margin: 0 auto;
    display: grid;
    grid-template-columns: 200px 1fr;
    gap: 64px;
    align-items: start;
}
.nos-content-sidebar {
    position: sticky;
    top: 90px;
}
.nos-sidebar-tag {
    font-size: 0.7rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .2em;
    color: var(--accent);
    margin-bottom: 12px;
}
.nos-sidebar-title {
    font-size: 1.4rem;
    font-weight: 900;
    color: var(--text);
    line-height: 1.2;
    margin: 0 0 20px;
}
.nos-sidebar-line {
    width: 36px;
    height: 3px;
    background: var(--accent);
    border-radius: 2px;
}
.nos-content-body .ql-editor {
    padding: 0;
    font-size: 1rem;
    line-height: 1.75;
    color: var(--text);
}

/* ─── Marcas ────────────────────────────────────────────────────── */
.nos-brands {
    padding: 60px 32px 72px;
    overflow: hidden;
    background: var(--bg);
}
.nos-brands-header {
    max-width: var(--cw);
    margin: 0 auto 40px;
    text-align: center;
}
.nos-brands-header .nos-section-label { justify-content: center; display: block; }
.nos-brands-title {
    font-size: clamp(1.4rem, 2.5vw, 1.9rem);
    font-weight: 900;
    color: var(--text);
    margin: 6px 0 0;
}
.nos-sep-wrap { max-width: var(--cw); margin: 0 auto 24px; }
.nos-brands-empty { text-align:center; color:var(--muted); font-size:0.95rem; padding:0 24px 52px; margin:0; }
.nos-brands-outer { max-width: var(--cw); margin: 0 auto; overflow: hidden; }
.nos-brands-rows { display:flex; flex-direction:column; gap:24px; overflow:hidden; }
.nos-row-wrap { overflow:hidden; }
.nos-row-track { display:flex; flex-direction:row; flex-wrap:nowrap; gap:12px; width:fit-content; will-change:transform; }
.nos-row-track.row-left  { animation:nos-left  linear infinite; }
.nos-row-track.row-right { animation:nos-right linear infinite; }
@keyframes nos-left  { from{transform:translateX(0)}    to{transform:translateX(-50%)} }
@keyframes nos-right { from{transform:translateX(-50%)} to{transform:translateX(0)} }
.nos-row-wrap:hover .nos-row-track { animation-play-state:paused; }
.nos-logo-card {
    background:var(--card-bg); border:1px solid var(--border); border-radius:10px;
    width:200px; height:100px; display:flex; align-items:center; justify-content:center;
    padding:12px 16px; flex-shrink:0; transition:border-color .4s, transform .4s;
}
.nos-logo-card:hover { border-color:var(--accent); transform:scale(1.04); }
.nos-logo-card img { max-height:70px; max-width:118px; object-fit:contain; display:block; filter:grayscale(15%); transition:filter .2s; }
.nos-logo-card:hover img { filter:none; }

/* ─── Responsive ───────────────────────────────────────────────── */
@media (max-width: 900px) {
    .nos-cards-grid { grid-template-columns: 1fr; gap: 16px; }
    .nos-content-inner { grid-template-columns: 1fr; gap: 32px; }
    .nos-content-sidebar { position: static; }
    .nos-brands-rows .nos-row-wrap:nth-child(3),
    .nos-brands-rows .nos-row-wrap:nth-child(4) { display:none; }
    .nos-logo-card { width:130px; height:68px; }
}
@media (max-width: 600px) {
    .nos-hero { padding: 56px 20px 48px; }
    .nos-cards-section, .nos-content-section, .nos-brands { padding-left: 20px; padding-right: 20px; }
    .nos-logo-card { width:110px; height:62px; }
}
</style>

<div class="nos-page">

<!-- ═══ HERO ═══════════════════════════════════════════════════════ -->
<section class="nos-hero">
    <div class="nos-hero-inner">
        <div class="nos-hero-eyebrow">
            <i class="bi bi-stars"></i> <?= htmlspecialchars($meta['eyebrow'] ?? 'Quiénes Somos') ?>
        </div>
        <h1 class="nos-hero-title">
            <?= htmlspecialchars($meta['hero_title'] ?? 'El medio geek que México necesitaba') ?>
        </h1>
        <p class="nos-hero-sub">
            <?= htmlspecialchars($meta['hero_sub'] ?? 'Somos CatInk, un medio de comunicación digital y agencia creativa enfocada en el entretenimiento geek: Anime, Manga, Cine, Videojuegos y Cultura Pop.') ?>
        </p>
        <div class="nos-hero-stats">
            <?php 
            $estadisticas = $meta['estadisticas'] ?? [];
            if (empty($estadisticas)) {
                $estadisticas = [
                    ['num' => $meta['stat1_num'] ?? $meta['stat_1'] ?? '500K+', 'lbl' => $meta['stat1_lbl'] ?? $meta['stat_1_label'] ?? 'Lectores Mensuales'],
                    ['num' => $meta['stat2_num'] ?? $meta['stat_2'] ?? '10K+',  'lbl' => $meta['stat2_lbl'] ?? $meta['stat_2_label'] ?? 'Artículos Publicados'],
                    ['num' => $meta['stat3_num'] ?? $meta['stat_3'] ?? '100%',  'lbl' => $meta['stat3_lbl'] ?? $meta['stat_3_label'] ?? 'Pasión Geek']
                ];
            }
            foreach ($estadisticas as $st):
                if (empty($st['num']) && empty($st['lbl'])) continue;
            ?>
                <div class="nos-stat-item">
                    <span class="nos-stat-num"><?= htmlspecialchars($st['num']) ?></span>
                    <span class="nos-stat-label"><?= htmlspecialchars($st['lbl']) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ═══ MISIÓN / VISIÓN / VALORES ═══════════════════════════════════ -->
<section class="nos-cards-section">
    <div class="nos-cards-inner">
        <p class="nos-section-label">Nuestra Identidad</p>
        <h2 class="nos-section-title">Lo que nos mueve cada día</h2>
        <div class="nos-cards-grid">
            <div class="nos-card">
                <div class="nos-card-icon"><i class="bi bi-crosshair2"></i></div>
                <h3 class="nos-card-title">Misión</h3>
                <p class="nos-card-desc"><?= htmlspecialchars($meta['mision'] ?? $meta['card_mision'] ?? 'Crear contenido de calidad sobre cultura geek que informe, entretenga e inspire a la comunidad hispanohablante de manera auténtica y apasionada.') ?></p>
            </div>
            <div class="nos-card">
                <div class="nos-card-icon"><i class="bi bi-eye"></i></div>
                <h3 class="nos-card-title">Visión</h3>
                <p class="nos-card-desc"><?= htmlspecialchars($meta['vision'] ?? $meta['card_vision'] ?? 'Convertirnos en el referente digital líder de cultura pop y entretenimiento geek en México y Latinoamérica, conectando marcas con comunidades.') ?></p>
            </div>
            <div class="nos-card">
                <div class="nos-card-icon"><i class="bi bi-heart-fill"></i></div>
                <h3 class="nos-card-title">Valores</h3>
                <p class="nos-card-desc"><?= htmlspecialchars($meta['valores'] ?? $meta['card_valores'] ?? 'Autenticidad, pasión por el contenido, comunidad antes que clics, calidad editorial y respeto total a nuestra audiencia y colaboradores.') ?></p>
            </div>
        </div>
    </div>
</section>

<!-- ═══ CONTENIDO ════════════════════════════════════════════════════ -->
<?php if (!empty($row['contenido_pag'])): ?>
<section class="nos-content-section">
    <div class="nos-content-inner">
        <div class="nos-content-sidebar">
            <p class="nos-sidebar-tag">Nuestra historia</p>
            <h2 class="nos-sidebar-title">Más sobre nosotros</h2>
            <div class="nos-sidebar-line"></div>
        </div>
        <div class="nos-content-body">
            <div class="post-content">
                <div class="ql-editor">
                    <?php echo $row['contenido_pag']; ?>
                </div>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ═══ MARCAS ═══════════════════════════════════════════════════════ -->
<section class="nos-brands">
    <div class="nos-brands-header">
        <p class="nos-section-label">Alianzas</p>
        <h2 class="nos-brands-title">Marcas con las que colaboramos</h2>
    </div>

    <?php if (empty($logos)): ?>
        <p class="nos-brands-empty">Próximamente anunciaremos nuestras marcas colaboradoras.</p>
    <?php else: ?>
    <div class="nos-brands-outer">
    <div class="nos-brands-rows">
        <?php foreach ($rows_cfg as $row_cfg): ?>
        <div class="nos-row-wrap">
            <div class="nos-row-track <?= $row_cfg['dir'] ?>"
                 style="animation-duration:<?= $row_cfg['duration'] ?>s">
                <?php for ($r = 0; $r < $row_cfg['copies']; $r++): foreach ($row_cfg['logos'] as $logo): ?>
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
    </div>
    <?php endif; ?>
</section>

</div>

<?php include("./../layout/footer.php"); ?>
