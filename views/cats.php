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
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Gestión de Categorias</h1>
        <form method="GET" class="admin-search-form">
            <i class="bi bi-search admin-search-icon"></i>
            <input type="search" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Buscar categoría..." class="admin-search-input">
            <?php if($q): ?><a href="./cats.php" class="admin-search-clear">&times;</a><?php endif; ?>
        </form>
    </div>
    <?php if($ACL['crear']): ?>
        <button id="btnCrear" class="btn btn-accent"><i class="bi bi-plus-lg"></i> Crear categoría</button>
    <?php endif; ?>
    <div class="card">
        <div class="card-body">
            <h5 class="card-title">Lista de Categorías</h5>
            <div class="table-responsive">
                <table class="table table-striped table-bordered" id="categoriasTable">
                    <thead>
                        <tr>
                            <th style="width: 40px;"><i class="bi bi-arrows-move"></i></th>
                            <th>Nombre</th>
                            <th>Total Noticias</th>
                            <?php if($ACL['editar'] || $ACL['eliminar']): ?>
                                <th>Acciones</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody id="categoriasBody">
                        <?php while($row = $result->fetch_assoc()): ?>
                        <tr draggable="true" data-id="<?= $row['id_c'] ?>" class="categoria-row">
                            <td style="cursor: grab; text-align: center;"><i class="bi bi-arrows-move"></i></td>
                            <td><?= htmlspecialchars($row['nombre']) ?></td>
                            <td><?= $row['total_noticias'] ?></td>
                            <?php if($ACL['editar'] || $ACL['eliminar']): ?>
                                <td>
                                    <?php if($ACL['editar']): ?>
                                        <button class="btn btn-secondary btn-editar" 
                                            data-id="<?= $row['id_c'] ?>" 
                                            data-nombre="<?= htmlspecialchars($row['nombre']) ?>"><i class="bi bi-pencil"></i></button>
                                    <?php endif; ?>
                                    <?php if($ACL['eliminar']): ?>
                                        <button class="btn btn-delete btn-eliminar"
                                            data-id="<?= $row['id_c'] ?>" 
                                            data-nombre="<?= htmlspecialchars($row['nombre']) ?>"><i class="bi bi-trash3"></i></button>
                                    <?php endif; ?>
                                </td>
                            <?php endif; ?>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<!-- MODAL NATIVO -->
<div id="modal" class="modal">
    <div class="modal-content">
        <span id="modalClose" class="modal-close">&times;</span>
        <h3 id="modalTitle"></h3>
        <p id="modalConfirmText" style="display:none;"></p>
        <br>
        <form id="modalForm">
            <input type="hidden" name="id_c" id="modalId">
            <div class="mb-3">
                <label for="modalNombre">Nombre</label>
                <input type="text" id="modalNombre" name="nombre" required>
            </div>
            <button type="submit" id="modalSubmit" class="btn btn-accent"></button>
        </form>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('modal');
    const modalTitle = document.getElementById('modalTitle');
    const modalForm = document.getElementById('modalForm');
    const modalId = document.getElementById('modalId');
    const modalNombre = document.getElementById('modalNombre');
    const modalSubmit = document.getElementById('modalSubmit');
    const modalClose = document.getElementById('modalClose');
    const btnCrear = document.getElementById('btnCrear');
    if(btnCrear && ACL.crear){
        // Abrir modal de crear
        document.getElementById('btnCrear').addEventListener('click', () => {
            modalTitle.textContent = "Crear Categoría";
            modalSubmit.textContent = "Crear";
            modalForm.dataset.action = "crear";
            modalId.value = "";
            modalNombre.value = "";
            modalNombre.parentElement.style.display = "block"; // reset
            modal.style.display = "flex";
        });
    }
    if(ACL.editar){
        // Abrir modal de editar
        document.querySelectorAll('.btn-editar').forEach(btn => {
            btn.addEventListener('click', () => {
                modalTitle.textContent = "Editar Categoría";
                modalSubmit.textContent = "Actualizar";
                modalForm.dataset.action = "editar";
                modalId.value = btn.dataset.id;
                modalNombre.value = btn.dataset.nombre;
                modalNombre.parentElement.style.display = "block"; // reset
                modal.style.display = "flex";
                });
        });
    }
    if(ACL.eliminar){
        document.querySelectorAll('.btn-eliminar').forEach(btn => {
            btn.addEventListener('click', () => {
                modalTitle.textContent = "Eliminar Categoría";
                modalSubmit.textContent = "Eliminar";
                modalForm.dataset.action = "eliminar";
                modalId.value = btn.dataset.id;
                modalConfirmText.style.display = "block";
                modalConfirmText.textContent =
                    `¿Estás seguro de eliminar la categoría "${btn.dataset.nombre}"?`;
                modalNombre.required = false; // CLAVE
                modalNombre.parentElement.style.display = "none";
                modal.style.display = "flex";
            });
        });
    }
    // Cerrar modal
    modalClose.addEventListener('click', () => {
        modal.style.display = "none";
        modalNombre.parentElement.style.display = "block"; // reset
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
            modalNombre.parentElement.style.display = "block";
        }
    });

    // DRAG AND DROP PARA REORDENAR CATEGORÍAS
    let draggedRow = null;

    const rows = document.querySelectorAll('.categoria-row');
    rows.forEach(row => {
        row.addEventListener('dragstart', (e) => {
            draggedRow = row;
            row.style.opacity = '0.5';
            e.dataTransfer.effectAllowed = 'move';
        });

        row.addEventListener('dragend', (e) => {
            row.style.opacity = '1';
        });

        row.addEventListener('dragover', (e) => {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            if (row !== draggedRow) {
                row.style.borderTop = '3px solid #EF3363';
            }
        });

        row.addEventListener('dragleave', (e) => {
            row.style.borderTop = '';
        });

        row.addEventListener('drop', (e) => {
            e.preventDefault();
            row.style.borderTop = '';
            if (row !== draggedRow) {
                const tbody = document.getElementById('categoriasBody');
                if (draggedRow.offsetTop < row.offsetTop) {
                    row.parentNode.insertBefore(draggedRow, row.nextSibling);
                } else {
                    row.parentNode.insertBefore(draggedRow, row);
                }
                guardarOrden();
            }
        });
    });

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
