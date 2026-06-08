<?php
include("./../layout/headerAdmin.php");
include("./../data/conexion.php");
// Obtener todas las categorías y conteo de noticias
$q = trim($_GET['q'] ?? '');
if($q !== ''){
    $like = $con->real_escape_string("%$q%");
    $sql = "
    SELECT c.id_c, c.nombre, c.orden, COUNT(DISTINCT nc.noticia_id) AS total_noticias
    FROM categorias c
    LEFT JOIN noticia_categoria nc ON c.id_c = nc.categoria_id
    WHERE c.nombre LIKE '$like'
    GROUP BY c.id_c, c.nombre, c.orden
    ORDER BY c.orden ASC, c.nombre ASC
    ";
} else {
    $sql = "
    SELECT c.id_c, c.nombre, c.orden, COUNT(DISTINCT nc.noticia_id) AS total_noticias
    FROM categorias c
    LEFT JOIN noticia_categoria nc ON c.id_c = nc.categoria_id
    GROUP BY c.id_c, c.nombre, c.orden
    ORDER BY c.orden ASC, c.nombre ASC
    ";
}
$result = $con->query($sql);

$categorias = [];
while($row = $result->fetch_assoc()){
    $categorias[] = $row;
}
$totalCategorias = count($categorias);
$totalNoticiasAsignadas = array_sum(array_column($categorias, 'total_noticias'));
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4" style="flex-wrap: wrap; gap: 12px;">
        <h1 style="margin:0;">Gestión de Categorías</h1>
        <form method="GET" class="admin-search-form" style="display:flex; align-items:center; gap:8px;">
            <i class="bi bi-search admin-search-icon"></i>
            <input type="search" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Buscar categoría..." class="admin-search-input">
            <?php if($q): ?><a href="./cats.php" class="admin-search-clear">&times;</a><?php endif; ?>
        </form>
    </div>

    <!-- Estadísticas rápidas -->
    <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));">
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(99,102,241,0.1); color: #6366f1;"><i class="bi bi-grid-fill"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?= $totalCategorias ?></span>
                <span class="stat-label">Categorías Existentes</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(16,185,129,0.1); color: #10b981;"><i class="bi bi-newspaper"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?= $totalNoticiasAsignadas ?></span>
                <span class="stat-label">Noticias Asignadas</span>
            </div>
        </div>
    </div>

    <!-- Toolbar -->
    <div class="contenidos-toolbar">
        <div class="contenidos-tabs">
            <span class="tab-btn active">Todas las categorías</span>
        </div>
        <div class="contenidos-actions">
            <?php if($ACL['crear']): ?>
                <button id="btnCrear" class="btn btn-accent"><i class="bi bi-plus-lg"></i> Crear categoría</button>
            <?php endif; ?>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="contenidos-table" id="categoriasTable">
                    <thead>
                        <tr>
                            <th style="width: 40px; text-align:center;"><i class="bi bi-arrows-move"></i></th>
                            <th>Nombre</th>
                            <th>Total Noticias</th>
                            <?php if($ACL['editar'] || $ACL['eliminar']): ?>
                                <th>Acciones</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody id="categoriasBody">
                        <?php if(empty($categorias)): ?>
                            <tr><td colspan="4" style="text-align:center; padding:30px; color:var(--muted);">No se encontraron categorías.</td></tr>
                        <?php else: ?>
                            <?php foreach($categorias as $row): ?>
                            <tr data-id="<?= $row['id_c'] ?>" class="categoria-row">
                                <td style="cursor: grab; text-align: center; color:var(--muted);"><i class="bi bi-grip-vertical"></i></td>
                                <td><strong class="table-title"><?= htmlspecialchars($row['nombre']) ?></strong></td>
                                <td><span class="estado-badge estado-publicado" style="background:rgba(16,185,129,0.1); color:#10b981;"><?= $row['total_noticias'] ?> noticias</span></td>
                                <?php if($ACL['editar'] || $ACL['eliminar']): ?>
                                    <td>
                                        <div class="noticias-actions" style="border-top:none; padding:0; justify-content:flex-start;">
                                            <?php if($ACL['editar']): ?>
                                                <button class="btn btn-edit btn-editar" 
                                                    data-id="<?= $row['id_c'] ?>" 
                                                    data-nombre="<?= htmlspecialchars($row['nombre']) ?>" title="Editar"><i class="bi bi-pencil-square"></i></button>
                                            <?php endif; ?>
                                            <?php if($ACL['eliminar']): ?>
                                                <button class="btn btn-delete btn-eliminar"
                                                    data-id="<?= $row['id_c'] ?>" 
                                                    data-nombre="<?= htmlspecialchars($row['nombre']) ?>" title="Eliminar"><i class="bi bi-trash"></i></button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                <?php endif; ?>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- MODAL CROP/NATIVO -->
<div id="modal" class="crop-modal" style="display: none;">
    <div class="card" style="max-width: 400px; width:100%;">
        <div class="crop-modal-content">
            <h3 id="modalTitle"></h3>
            <p id="modalConfirmText" style="display:none; color:var(--muted); font-size:0.9rem; margin-bottom:15px;"></p>
            <form id="modalForm">
                <input type="hidden" name="id_c" id="modalId">
                <div style="margin-bottom: 16px;" id="modalNombreWrapper">
                    <label for="modalNombre" style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text);">Nombre de la categoría</label>
                    <input type="text" id="modalNombre" name="nombre" required style="width: 100%; padding: 10px 14px; border: 1px solid var(--border); border-radius: 8px; font-size: 0.9rem; background: var(--card-bg); color: var(--text);">
                </div>
                <div class="crop-actions" style="display:flex; justify-content:flex-end; gap:8px;">
                    <button type="button" id="modalClose" class="btn btn-secondary">Cancelar</button>
                    <button type="submit" id="modalSubmit" class="btn btn-accent"></button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- SortableJS para Drag and Drop fluido -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>

<?php include("./../layout/footerAdmin.php"); ?>
<style>
.sortable-ghost {
    opacity: 0.4;
    background-color: var(--surface-muted);
}
.sortable-drag {
    background-color: var(--card-bg);
    box-shadow: 0 5px 15px rgba(0,0,0,0.15);
    cursor: grabbing !important;
}
</style>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('modal');
    const modalTitle = document.getElementById('modalTitle');
    const modalForm = document.getElementById('modalForm');
    const modalId = document.getElementById('modalId');
    const modalNombre = document.getElementById('modalNombre');
    const modalSubmit = document.getElementById('modalSubmit');
    const modalClose = document.getElementById('modalClose');
    const modalNombreWrapper = document.getElementById('modalNombreWrapper');
    const btnCrear = document.getElementById('btnCrear');
    if(btnCrear && ACL.crear){
        // Abrir modal de crear
        document.getElementById('btnCrear').addEventListener('click', () => {
            modalTitle.innerHTML = '<i class="bi bi-folder-plus"></i> Crear Categoría';
            modalSubmit.textContent = "Crear";
            modalForm.dataset.action = "crear";
            modalId.value = "";
            modalNombre.value = "";
            modalConfirmText.style.display = "none";
            modalNombreWrapper.style.display = "block"; // reset
            modal.style.display = "flex";
        });
    }
    if(ACL.editar){
        // Abrir modal de editar
        document.querySelectorAll('.btn-editar').forEach(btn => {
            btn.addEventListener('click', () => {
                modalTitle.innerHTML = '<i class="bi bi-pencil-square"></i> Editar Categoría';
                modalSubmit.textContent = "Actualizar";
                modalForm.dataset.action = "editar";
                modalId.value = btn.dataset.id;
                modalNombre.value = btn.dataset.nombre;
                modalConfirmText.style.display = "none";
                modalNombreWrapper.style.display = "block"; // reset
                modal.style.display = "flex";
                });
        });
    }
    if(ACL.eliminar){
        document.querySelectorAll('.btn-eliminar').forEach(btn => {
            btn.addEventListener('click', () => {
                modalTitle.innerHTML = '<i class="bi bi-trash"></i> Eliminar Categoría';
                modalSubmit.textContent = "Eliminar";
                modalForm.dataset.action = "eliminar";
                modalId.value = btn.dataset.id;
                modalConfirmText.style.display = "block";
                modalConfirmText.textContent =
                    `¿Estás seguro de eliminar la categoría "${btn.dataset.nombre}"?`;
                modalNombre.required = false; // CLAVE
                modalNombreWrapper.style.display = "none";
                modal.style.display = "flex";
            });
        });
    }
    // Cerrar modal
    modalClose.addEventListener('click', () => {
        modal.style.display = "none";
        modalNombreWrapper.style.display = "block"; // reset
        modalConfirmText.style.display = "none";
    });
    // Enviar formulario
    modalForm.addEventListener('submit', (e) => {
        const action = modalForm.dataset.action;
        if(!ACL[action]){
            alert("No tienes permisos para realizar esta acción");
            return;
        }
        e.preventDefault();
        const formData = new FormData(modalForm);
        let url = "";
        if(action === "crear") url = "./../controllers/crearc.php";
        if(action === "editar") url = "./../controllers/editarc.php";
        if(action === "eliminar") url = "./../controllers/eliminarc.php";
        fetch(url, { method: "POST", body: formData })
            .then(r => {
                if (!r.ok) throw new Error("Error HTTP");
                return r.json();
            })
            .then(d => {
                console.log(d); // 👈 clave
                if (d.success) location.reload();
                else alert(d.error || "Ocurrió un error");
            })
            .catch(err => {
                console.error(err);
                alert("Error en la petición");
            });
    });
    // Cerrar modal al hacer click fuera del contenido
    modal.addEventListener('click', (e) => {
        if(e.target === modal) {
            modal.style.display = "none";
            modalNombreWrapper.style.display = "block";
            modalConfirmText.style.display = "none";
        }
    });

    // DRAG AND DROP PARA REORDENAR CATEGORÍAS (Usando SortableJS)
    const tbody = document.getElementById('categoriasBody');
    if (tbody) {
        new Sortable(tbody, {
            animation: 150, // Animación fluida al reordenar (ms)
            handle: 'td:first-child', // Restringir el agarre al ícono de mover
            ghostClass: 'sortable-ghost', // Clase para el elemento fantasma
            dragClass: 'sortable-drag', // Clase para el elemento arrastrado
            forceFallback: true, // Desactiva el drag nativo de HTML5 para dar un control total
            fallbackOnBody: true, // El clon sigue al mouse perfectamente
            axis: 'y', // BLOQUEA el arrastre para que SOLO sea vertical (no se sale a los lados)
            onEnd: function () {
                guardarOrden();
            }
        });
    }

    function guardarOrden() {
        const rows = document.querySelectorAll('.categoria-row');
        const orden = [];
        rows.forEach((row, index) => {
            orden.push({
                id_c: row.dataset.id,
                orden: index
            });
        });

        fetch('./../controllers/reordenar_categorias.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ categorias: orden })
        })
        .then(r => r.json())
        .then(d => {
            if (!d.success) {
                console.error('Error al guardar orden:', d.error);
                alert('Error al guardar el orden');
            }
        })
        .catch(err => {
            console.error('Error:', err);
            alert('Error en la petición');
        });
    }
});
</script>
<?php include("./../layout/footerAdmin.php"); ?>
