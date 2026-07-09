<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// La papelera es exclusiva del superadmin
if (!($_SESSION['superadmin'] ?? false)) {
    header("Location: admin.php");
    exit();
}
include("./../layout/headerAdmin.php");
include("./../data/conexion.php");

// ============================
// Noticias en la papelera
// ============================
$sql = "SELECT n.id, n.titulo, n.crop3, n.fecha_publicacion, n.eliminado_en,
               autor.nombre AS autor_nombre,
               elim.nombre  AS eliminado_nombre
        FROM noticias n
        LEFT JOIN usuarios autor ON n.autor = autor.id_u
        LEFT JOIN usuarios elim  ON n.eliminado_por = elim.id_u
        WHERE n.eliminado_en IS NOT NULL
        ORDER BY n.eliminado_en DESC";
$result = $con->query($sql);
$eliminadas = [];
while ($row = $result->fetch_assoc()) {
    $eliminadas[] = $row;
}
$total = count($eliminadas);
?>
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4" style="flex-wrap: wrap; gap: 12px;">
        <div>
            <h1 style="margin:0;"><i class="bi bi-trash3"></i> Papelera</h1>
        </div>
        <a href="contenidos.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Volver a Contenidos</a>
    </div>

    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'restaurada'): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert" style="margin-top:12px;">
            Publicación(es) restaurada(s) correctamente.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'eliminada_def'): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert" style="margin-top:12px;">
            Publicación(es) eliminada(s) definitivamente.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php elseif (isset($_GET['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert" style="margin-top:12px;">
            No se pudo completar la acción. Intenta nuevamente.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Barra de acciones masivas -->
    <div class="papelera-bulkbar" id="bulkBar" hidden>
        <span class="bulk-count"><i class="bi bi-check2-square"></i> <strong id="bulkCount">0</strong> seleccionada(s)</span>
        <div class="bulk-actions">
            <button type="button" class="btn btn-secondary" id="bulkClear">Deseleccionar</button>
            <button type="button" class="btn btn-edit" id="bulkRestore"><i class="bi bi-arrow-counterclockwise"></i> Restaurar</button>
            <button type="button" class="btn btn-danger" id="bulkDelete"><i class="bi bi-trash"></i> Eliminar</button>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="contenidos-table papelera-table">
                    <thead>
                        <tr>
                            <th style="width:40px;"><input type="checkbox" id="selectAll" title="Seleccionar todo" <?= empty($eliminadas) ? 'disabled' : '' ?>></th>
                            <th>Imagen</th>
                            <th>Título</th>
                            <th>Autor</th>
                            <th>Publicación</th>
                            <th>Eliminada por</th>
                            <th>Fecha de eliminación</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($eliminadas)): ?>
                            <tr><td colspan="8" style="text-align:center; padding:40px; color:var(--muted);">
                                <i class="bi bi-trash3" style="font-size:1.6rem; display:block; margin-bottom:8px; opacity:0.6;"></i>
                                La papelera está vacía
                            </td></tr>
                        <?php else: ?>
                            <?php foreach ($eliminadas as $row):
                                $img = imageUrl($row['crop3'] ?? 'img/placeholder.svg');
                                $fechaPub = new DateTime($row['fecha_publicacion']);
                                $fechaElim = new DateTime($row['eliminado_en']);
                            ?>
                            <tr>
                                <td><input type="checkbox" class="papelera-check" value="<?= $row['id'] ?>"></td>
                                <td><img src="<?= $img ?>" alt="" class="table-thumb" loading="lazy" decoding="async"></td>
                                <td><strong class="table-title"><?= htmlspecialchars($row['titulo']) ?></strong></td>
                                <td><?= htmlspecialchars($row['autor_nombre'] ?? 'Desconocido') ?></td>
                                <td class="table-date"><?= $fechaPub->format('d/m/Y H:i') ?></td>
                                <td>
                                    <span class="papelera-user">
                                        <i class="bi bi-person-fill"></i>
                                        <?= htmlspecialchars($row['eliminado_nombre'] ?? 'Desconocido') ?>
                                    </span>
                                </td>
                                <td class="table-date">
                                    <i class="bi bi-clock-history"></i>
                                    <?= $fechaElim->format('d/m/Y H:i') ?>
                                </td>
                                <td>
                                    <div class="noticias-actions papelera-actions">
                                        <form action="../controllers/restaurar_noticia.php" method="POST">
                                            <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                            <button type="submit" class="btn btn-edit" title="Restaurar"><i class="bi bi-arrow-counterclockwise"></i></button>
                                        </form>
                                        <button type="button" class="btn btn-delete-def" data-id="<?= $row['id'] ?>" data-titulo="<?= htmlspecialchars($row['titulo']) ?>" title="Eliminar definitivamente"><i class="bi bi-trash"></i></button>
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

<!-- Formularios ocultos para acciones masivas -->
<form id="bulkRestoreForm" action="../controllers/restaurar_noticia.php" method="POST" style="display:none;"></form>
<form id="bulkDeleteForm" action="../controllers/eliminar_noticia_definitivo.php" method="POST" style="display:none;"></form>

<style>
.papelera-user {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 0.85rem;
}
.papelera-user i { color: var(--accent); }
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
.papelera-check, #selectAll {
    width: 16px;
    height: 16px;
    cursor: pointer;
    accent-color: var(--accent);
}
.papelera-bulkbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
    background: var(--card-bg, #1E2530);
    border: 1px solid var(--border);
    border-radius: 10px;
    padding: 12px 16px;
    margin: 12px 0;
}
.papelera-bulkbar[hidden] { display: none; }
.papelera-bulkbar .bulk-count { font-size: 0.9rem; }
.papelera-bulkbar .bulk-actions { display: flex; gap: 8px; flex-wrap: wrap; }
</style>

<!-- Modal de Confirmación para Eliminar Definitivamente (individual) -->
<div id="modalOverlay" class="crop-modal" style="display: none;">
    <div class="card">
        <div class="crop-modal-content">
            <h3 id="modalTitle">Eliminar definitivamente</h3>
            <p>Se borrará permanentemente la publicación. <strong>Esta acción no se puede deshacer.</strong></p>
            <form id="modalForm" action="../controllers/eliminar_noticia_definitivo.php" method="POST">
                <input type="hidden" name="id" id="modalId">
                <div class="crop-actions">
                    <button type="button" class="btn btn-secondary btn-cancel">Cancelar</button>
                    <button type="submit" class="btn btn-danger">Eliminar definitivamente</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal de Confirmación para Eliminar Definitivamente (masivo) -->
<div id="bulkDeleteModal" class="crop-modal" style="display: none;">
    <div class="card">
        <div class="crop-modal-content">
            <h3>Eliminar definitivamente</h3>
            <p>Vas a eliminar <strong id="bulkDeleteCount">0</strong> publicación(es) de forma permanente. <strong>Esta acción no se puede deshacer.</strong></p>
            <div class="crop-actions">
                <button type="button" class="btn btn-secondary" id="bulkDeleteCancel">Cancelar</button>
                <button type="button" class="btn btn-danger" id="bulkDeleteConfirm">Eliminar definitivamente</button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    function allChecks() { return document.querySelectorAll('.papelera-check'); }
    function selectedIds() {
        return Array.from(document.querySelectorAll('.papelera-check:checked')).map(c => c.value);
    }
    function updateBulkUI() {
        const total = allChecks().length;
        const selected = document.querySelectorAll('.papelera-check:checked').length;
        const bar = document.getElementById('bulkBar');
        const countEl = document.getElementById('bulkCount');
        const selectAll = document.getElementById('selectAll');
        if (countEl) countEl.textContent = selected;
        if (bar) bar.hidden = (selected === 0);
        if (selectAll) {
            selectAll.checked = (selected > 0 && selected === total);
            selectAll.indeterminate = (selected > 0 && selected < total);
        }
    }
    function submitBulk(formId, ids) {
        const form = document.getElementById(formId);
        if (!form || !ids.length) return;
        form.innerHTML = '';
        ids.forEach(id => {
            const i = document.createElement('input');
            i.type = 'hidden';
            i.name = 'ids[]';
            i.value = id;
            form.appendChild(i);
        });
        form.submit();
    }

    // ------------------------------------------------------------------
    // 1) Delegación en document (una sola vez): checkboxes y botones de la
    //    barra masiva, que están FUERA del modal (no bloqueados).
    // ------------------------------------------------------------------
    if (!window.__papeleraBulkDelegated) {
        window.__papeleraBulkDelegated = true;

        document.addEventListener('change', function (e) {
            if (e.target && e.target.id === 'selectAll') {
                const on = e.target.checked;
                document.querySelectorAll('.papelera-check').forEach(c => { c.checked = on; });
                updateBulkUI();
            } else if (e.target && e.target.classList && e.target.classList.contains('papelera-check')) {
                updateBulkUI();
            }
        });

        document.addEventListener('click', function (e) {
            const btn = e.target.closest('#bulkClear, #bulkRestore, #bulkDelete');
            if (!btn) return;
            if (btn.id === 'bulkClear') {
                document.querySelectorAll('.papelera-check').forEach(c => { c.checked = false; });
                updateBulkUI();
            } else if (btn.id === 'bulkRestore') {
                submitBulk('bulkRestoreForm', selectedIds());
            } else if (btn.id === 'bulkDelete') {
                const ids = selectedIds();
                if (!ids.length) return;
                const c = document.getElementById('bulkDeleteCount');
                if (c) c.textContent = ids.length;
                const modal = document.getElementById('bulkDeleteModal');
                if (modal) modal.style.display = 'flex';
            }
        });
    }

    // ------------------------------------------------------------------
    // 2) Botones DENTRO del modal: admin.js detiene la propagación en
    //    .crop-modal-content, así que se enlazan directamente al elemento
    //    (se disparan antes de que el padre corte la propagación).
    //    Se re-enlazan en cada carga (idempotente con onclick).
    // ------------------------------------------------------------------
    function wireModalButtons() {
        const modal = document.getElementById('bulkDeleteModal');
        const confirmBtn = document.getElementById('bulkDeleteConfirm');
        const cancelBtn = document.getElementById('bulkDeleteCancel');
        if (confirmBtn) confirmBtn.onclick = function () { submitBulk('bulkDeleteForm', selectedIds()); };
        if (cancelBtn) cancelBtn.onclick = function () { if (modal) modal.style.display = 'none'; };
        updateBulkUI();
    }

    if (!window.__papeleraTurboWired) {
        window.__papeleraTurboWired = true;
        document.addEventListener('turbo:load', wireModalButtons);
    }
    wireModalButtons();
})();
</script>

<?php include("./../layout/footerAdmin.php"); ?>
