<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include("./../layout/headerAdmin.php");
include("./../data/conexion.php");

$ACL = $_SESSION['ACL']['noticias'] ?? [
    "crear" => false, "leer" => false, "editar" => false, "eliminar" => false
];
if (empty($ACL['leer'])) {
    header("Location: admin.php");
    exit();
}

// ============================
// Borradores (borrador = 1, no eliminados)
// ============================
$sql = "SELECT n.id, n.titulo, n.crop3, n.tipo_publicacion, n.fecha_creacion,
               autor.nombre AS autor_nombre
        FROM noticias n
        LEFT JOIN usuarios autor ON n.autor = autor.id_u
        WHERE n.borrador = 1 AND n.eliminado_en IS NULL
        ORDER BY n.fecha_creacion DESC";
$result = $con->query($sql);
$borradores = [];
while ($row = $result->fetch_assoc()) {
    $borradores[] = $row;
}
$total = count($borradores);
?>
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4" style="flex-wrap: wrap; gap: 12px;">
        <div>
            <h1 style="margin:0;"><i class="bi bi-journal-text"></i> Borradores</h1>
            <p style="margin:4px 0 0; color:var(--muted); font-size:.9rem;">
                Publicaciones sin publicar. Ábrelas para completarlas y publicarlas.
            </p>
        </div>
        <a href="contenidos.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Volver a Contenidos</a>
    </div>

    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'creado'): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert" style="margin-top:12px;">
            Borrador guardado correctamente.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'eliminado'): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert" style="margin-top:12px;">
            Borrador enviado a la papelera.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php elseif (isset($_GET['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert" style="margin-top:12px;">
            No se pudo completar la acción. Intenta nuevamente.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="contenidos-table papelera-table">
                    <thead>
                        <tr>
                            <th>Imagen</th>
                            <th>Título</th>
                            <th>Tipo</th>
                            <th>Autor</th>
                            <th>Creado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($borradores)): ?>
                            <tr><td colspan="6" style="text-align:center; padding:40px; color:var(--muted);">
                                <i class="bi bi-journal-text" style="font-size:1.6rem; display:block; margin-bottom:8px; opacity:0.6;"></i>
                                No tienes borradores guardados
                            </td></tr>
                        <?php else: ?>
                            <?php foreach ($borradores as $row):
                                $img = imageUrl($row['crop3'] ?? 'img/placeholder.svg');
                                $fechaCreada = new DateTime($row['fecha_creacion']);
                                $tipo = $row['tipo_publicacion'] === 'review' ? 'Reseña' : 'Noticia';
                            ?>
                            <tr>
                                <td><img src="<?= $img ?>" alt="" class="table-thumb" loading="lazy" decoding="async"></td>
                                <td><strong class="table-title"><?= htmlspecialchars($row['titulo']) ?></strong></td>
                                <td><span class="borrador-tipo"><?= $tipo ?></span></td>
                                <td><?= htmlspecialchars($row['autor_nombre'] ?? 'Desconocido') ?></td>
                                <td class="table-date"><i class="bi bi-clock-history"></i> <?= $fechaCreada->format('d/m/Y H:i') ?></td>
                                <td>
                                    <div class="noticias-actions papelera-actions">
                                        <?php if (!empty($ACL['editar'])): ?>
                                        <a href="editar.php?id=<?= $row['id'] ?>" class="btn btn-edit" title="Continuar edición y publicar">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <?php endif; ?>
                                        <?php if (!empty($ACL['eliminar'])): ?>
                                        <button type="button" class="btn btn-danger btn-del-borrador"
                                                data-id="<?= $row['id'] ?>"
                                                data-titulo="<?= htmlspecialchars($row['titulo']) ?>"
                                                title="Enviar a papelera">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
.papelera-table th:first-child,
.papelera-table td:first-child,
.papelera-table th:last-child,
.papelera-table td:last-child { text-align: center; }
.papelera-actions {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 8px;
}
.papelera-actions form { margin: 0; display: inline-flex; }
.borrador-tipo {
    display: inline-block;
    font-size: .78rem; font-weight: 600;
    padding: 2px 9px; border-radius: 20px;
    background: rgba(239,51,99,.12); color: var(--accent);
}
</style>

<!-- Modal de Confirmación para enviar a papelera -->
<div id="modalOverlay" class="crop-modal" style="display: none;">
    <div class="card">
        <div class="crop-modal-content">
            <h3>Enviar a la papelera</h3>
            <p>El borrador <strong id="modalTitulo"></strong> se moverá a la papelera. Podrás restaurarlo desde ahí.</p>
            <form id="modalForm" action="../controllers/eliminar_noticia.php" method="POST">
                <input type="hidden" name="id" id="modalId">
                <input type="hidden" name="from" value="borradores">
                <div class="crop-actions">
                    <button type="button" class="btn btn-secondary btn-cancel">Cancelar</button>
                    <button type="submit" class="btn btn-danger">Enviar a papelera</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function () {
    function wire() {
        const modal = document.getElementById('modalOverlay');
        const modalId = document.getElementById('modalId');
        const modalTitulo = document.getElementById('modalTitulo');
        if (!modal) return;

        document.querySelectorAll('.btn-del-borrador').forEach(btn => {
            btn.onclick = function () {
                modalId.value = this.dataset.id;
                modalTitulo.textContent = '“' + (this.dataset.titulo || '') + '”';
                modal.style.display = 'flex';
            };
        });
        const cancel = modal.querySelector('.btn-cancel');
        if (cancel) cancel.onclick = () => { modal.style.display = 'none'; };
        // Evitar que un click dentro del contenido cierre el modal
        const content = modal.querySelector('.crop-modal-content');
        if (content) content.onclick = e => e.stopPropagation();
        modal.onclick = () => { modal.style.display = 'none'; };
    }
    wire();
    if (!window.__borradoresTurboWired) {
        window.__borradoresTurboWired = true;
        document.addEventListener('turbo:load', wire);
    }
})();
</script>

<?php include("./../layout/footerAdmin.php"); ?>
