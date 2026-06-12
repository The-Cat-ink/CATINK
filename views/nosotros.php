<?php
include("./../layout/header.php");
include("./../data/conexion.php");

$row = $con->query("SELECT contenido_pag FROM paginas WHERE nombre_pag='nosotros'")->fetch_assoc();
$logos = $con->query("SELECT * FROM logos_marcas WHERE activo=1 ORDER BY creado ASC")->fetch_all(MYSQLI_ASSOC);
?>
<div class="container-fluid">
    <h1>Sobre nosotros</h1>
    <div class="post-content">
        <div class="ql-editor">
            <?php echo $row['contenido_pag'] ?? ''; ?>
        </div>
    </div>

    <?php if (!empty($logos)): ?>
    <div class="nosotros-logos-section">
        <h2 class="nosotros-logos-title">Marcas con las que colaboramos</h2>
        <div class="nosotros-logos-grid">
            <?php foreach ($logos as $logo): ?>
            <div class="nosotros-logo-item" <?= $logo['nombre'] ? 'title="' . htmlspecialchars($logo['nombre']) . '"' : '' ?>>
                <img src="<?= imageUrl($logo['imagen']) ?>" alt="<?= htmlspecialchars($logo['nombre']) ?>" loading="lazy">
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
.nosotros-logos-section {
    margin-top: 48px;
    padding-top: 32px;
    border-top: 1px solid var(--border);
}
.nosotros-logos-title {
    text-align: center;
    font-size: 1.15rem;
    font-weight: 700;
    color: var(--muted);
    letter-spacing: .04em;
    text-transform: uppercase;
    margin-bottom: 28px;
}
.nosotros-logos-grid {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    align-items: center;
    gap: 28px 36px;
}
.nosotros-logo-item {
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: .75;
    transition: opacity .2s, transform .2s;
}
.nosotros-logo-item:hover { opacity: 1; transform: scale(1.06); }
.nosotros-logo-item img {
    max-height: 52px;
    max-width: 140px;
    object-fit: contain;
    display: block;
    filter: grayscale(30%);
    transition: filter .2s;
}
.nosotros-logo-item:hover img { filter: none; }
</style>

<?php include("./../layout/footer.php"); ?>
