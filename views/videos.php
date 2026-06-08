<?php
include (__DIR__ . "/../layout/headerAdmin.php");
$ACL = $_SESSION['ACL']['videos'] ?? [
    'crear' => false,
    'leer' => false,
    'editar' => false,
    'eliminar' => false,
];
if (!$ACL['leer']) {
    header("Location: admin.php");
    exit();
}
?>
<script>
const ACL = <?= json_encode($ACL) ?>;
</script>
<?php
include(__DIR__ . "/helpers/videoEmbed.php");
include(__DIR__ . "/../data/conexion.php");
$q = trim($_GET['q'] ?? '');
if($q !== ''){
    $like = "%$q%";
    $stmt = $con->prepare("SELECT * FROM videos WHERE url_v LIKE ? ORDER BY orden ASC, id_v DESC");
    $stmt->bind_param("s", $like);
} else {
    $stmt = $con->prepare("SELECT * FROM videos ORDER BY orden ASC, id_v DESC");
}
$stmt->execute();
$videos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$totalVideos = count($videos);
$activos = count(array_filter($videos, fn($v) => $v['activo'] == 1));
$inactivos = $totalVideos - $activos;
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4" style="flex-wrap: wrap; gap: 12px;">
        <h1 style="margin:0;">Gestión de Videos</h1>
        <form method="GET" class="admin-search-form" style="display:flex; align-items:center; gap:8px;">
            <i class="bi bi-search admin-search-icon"></i>
            <input type="search" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Buscar por URL..." class="admin-search-input">
            <?php if($q): ?><a href="./videos.php" class="admin-search-clear">&times;</a><?php endif; ?>
        </form>
    </div>

    <!-- Estadísticas rápidas -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(99,102,241,0.1); color: #6366f1;"><i class="bi bi-play-btn-fill"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?= $totalVideos ?></span>
                <span class="stat-label">Total Videos</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(16,185,129,0.1); color: #10b981;"><i class="bi bi-check-circle-fill"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?= $activos ?></span>
                <span class="stat-label">Activos</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(239,51,99,0.1); color: #EF3363;"><i class="bi bi-x-circle-fill"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?= $inactivos ?></span>
                <span class="stat-label">Inactivos</span>
            </div>
        </div>
    </div>

    <!-- Toolbar -->
    <div class="contenidos-toolbar">
        <div class="contenidos-tabs">
            <span class="tab-btn active">Todos los videos</span>
        </div>
        <div class="contenidos-actions">
            <?php if($ACL['crear']): ?>
                <button id="btnCrear" class="btn btn-accent">
                    <i class="bi bi-plus-lg"></i> Añadir Video
                </button>
            <?php endif; ?>
        </div>
    </div>

    <?php if(empty($videos)): ?>
        <div class="card shadow-sm"><div class="card-body" style="text-align:center; padding:30px; color:var(--muted);">No se encontraron videos.</div></div>
    <?php else: ?>
        <div id="videosGrid" class="row" style="display:flex; flex-wrap:wrap; gap:16px;">
            <?php foreach($videos as $video): ?>
                <div class="video-item" data-id="<?= $video['id_v'] ?>" style="flex: 1 1 300px; max-width: 400px;">
                    <div class="noticias-card" style="height:100%;">
                        <div class="card-status-bar" style="background: <?= $video['activo']==1 ? '#10b981' : '#EF3363' ?>"></div>
                        <div class="card-header d-flex justify-content-between align-items-center" style="padding: 10px 12px; cursor: grab;">
                            <div class="d-flex align-items-center gap-2 drag-handle" style="color:var(--muted);">
                                <i class="bi bi-grip-vertical"></i>
                                <?php if($video['activo']==1): ?>
                                    <span class="estado-badge estado-publicado"><i class="bi bi-check-circle-fill"></i> Activo</span>
                                <?php else: ?>
                                    <span class="estado-badge estado-por_publicar" style="background:rgba(239,51,99,0.1); color:#EF3363;"><i class="bi bi-x-circle-fill"></i> Inactivo</span>
                                <?php endif; ?>
                            </div>
                            <span class="card-time" style="font-size:0.75rem;"><i class="bi bi-youtube"></i> ID: <?= $video['id_v'] ?></span>
                        </div>
                        <div style="width:100%; aspect-ratio:16/9; background:#000;">
                            <?= renderizarVideo($video['url_v']) ?>
                        </div>
                        
                        <?php if($ACL['editar'] || $ACL['eliminar']): ?>
                            <div class="noticias-actions" style="margin-top:auto;">
                                <?php if($ACL['editar']): ?>
                                    <button class="btn btn-edit btn-editar"
                                        data-id="<?= $video['id_v'] ?>"
                                        data-url="<?= htmlspecialchars($video['url_v']) ?>"
                                        data-activo="<?= $video['activo'] ?>" title="Editar">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                <?php endif; ?>
                                <?php if($ACL['eliminar']): ?>
                                    <button class="btn btn-delete btn-eliminar"
                                        data-id="<?= $video['id_v'] ?>" title="Eliminar">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- MODAL NATIVO (Adaptado) -->
<div id="modal" class="crop-modal" style="display: none;">
    <div class="crop-modal-content">
        <h3 id="modalTitle"></h3>
        <p id="modalConfirmText" style="display:none; color:var(--muted); font-size:0.9rem; margin-bottom:15px;"></p>
        <form id="modalForm">
            <input type="hidden" name="id_v" id="modalId">
            <div id="modalInputsWrapper">
                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text);">URL del Video (Youtube, Vimeo, etc)</label>
                    <input type="text" name="url_v" id="modalUrl" required style="width: 100%; padding: 10px 14px; border: 1px solid var(--border); border-radius: 8px; font-size: 0.9rem; background: var(--card-bg); color: var(--text);">
                </div>
                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text);">Estado</label>
                    <select name="activo" id="modalActivo" style="width: 100%; padding: 10px 14px; border: 1px solid var(--border); border-radius: 8px; font-size: 0.9rem; background: var(--card-bg); color: var(--text);">
                        <option value="1">Activo</option>
                        <option value="0">Inactivo</option>
                    </select>
                </div>
            </div>
            <div class="crop-actions" style="display:flex; justify-content:flex-end; gap:8px;">
                <button type="button" id="modalClose" class="btn btn-secondary">Cancelar</button>
                <button type="submit" id="modalSubmit" class="btn btn-accent"></button>
            </div>
        </form>
    </div>
</div>

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
.drag-handle {
    cursor: grab;
}
.drag-handle:active {
    cursor: grabbing;
}
</style>

<!-- SortableJS para Drag and Drop fluido -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('modal');
    const modalTitle = document.getElementById('modalTitle');
    const modalForm = document.getElementById('modalForm');
    const modalId = document.getElementById('modalId');
    const modalUrl = document.getElementById('modalUrl');
    const modalActivo = document.getElementById('modalActivo');
    const modalSubmit = document.getElementById('modalSubmit');
    const modalClose = document.getElementById('modalClose');
    const modalConfirmText = document.getElementById('modalConfirmText');
    const modalInputsWrapper = document.getElementById('modalInputsWrapper');
    const btnCrear = document.getElementById('btnCrear');
    if(btnCrear && ACL.crear){
        btnCrear.addEventListener('click', () => {
            modalTitle.innerHTML = '<i class="bi bi-plus-circle"></i> Añadir Video';
            modalSubmit.textContent = "Guardar";
            modalForm.dataset.action = "crear";
            modalId.value = "";
            modalUrl.value = "";
            modalActivo.value = "1";
            modalConfirmText.style.display = "none";
            modalInputsWrapper.style.display = "block";
            modal.style.display = "flex";
        });
    }
    if(ACL.editar){
        document.querySelectorAll('.btn-editar').forEach(btn => {
            btn.addEventListener('click', () => {
                modalTitle.innerHTML = '<i class="bi bi-pencil-square"></i> Editar Video';
                modalSubmit.textContent = "Actualizar";
                modalForm.dataset.action = "editar";
                modalId.value = btn.dataset.id;
                modalUrl.value = btn.dataset.url;
                modalActivo.value = btn.dataset.activo;
                modalConfirmText.style.display = "none";
                modalInputsWrapper.style.display = "block";
                modal.style.display = "flex";
            });
        });
    }
    if(ACL.eliminar){
        document.querySelectorAll('.btn-eliminar').forEach(btn => {
            btn.addEventListener('click', () => {
                modalTitle.innerHTML = '<i class="bi bi-trash"></i> Eliminar Video';
                modalSubmit.textContent = "Eliminar";
                modalForm.dataset.action = "eliminar";
                modalId.value = btn.dataset.id;
                modalConfirmText.style.display = "block";
                modalConfirmText.textContent = "¿Seguro que deseas eliminar este video?";
                modalUrl.required = false;
                modalActivo.required = false;
                modalInputsWrapper.style.display = "none";
                modal.style.display = "flex";
            });
        });
    }
    modalClose.addEventListener('click', () => {
        modal.style.display = "none";
        modalInputsWrapper.style.display = "block";
    });
    modal.addEventListener('click', (e) => {
        if(e.target === modal){
            modal.style.display = "none";
            modalInputsWrapper.style.display = "block";
        }
    });
    modalForm.addEventListener('submit', (e) => {
        e.preventDefault();
        const action = modalForm.dataset.action;
        if(!ACL[action]){
            alert("No tienes permisos");
            return;
        }
        const formData = new FormData(modalForm);
        let url = "";
        if(action === "crear") url = "./../controllers/crearv.php";
        if(action === "editar") url = "./../controllers/editarv.php";
        if(action === "eliminar") url = "./../controllers/eliminarv.php";
        fetch(url, {
            method: "POST",
            body: formData
        })
        .then(async r => {

            const text = await r.text();   // LEER COMO TEXTO
            console.log("RESPUESTA CRUDA:", text);

            try{
                const data = JSON.parse(text);
                if(data.success) location.reload();
                else alert(data.error);
            }catch(e){
                alert("El servidor NO devolvió JSON válido");
                console.error(text);       // AQUÍ VERÁS EL ERROR PHP REAL
            }
        })
        .catch(err => console.error(err));
    });

    // DRAG AND DROP PARA REORDENAR VIDEOS (Usando SortableJS)
    const videosGrid = document.getElementById('videosGrid');
    if (videosGrid && ACL.editar) {
        new Sortable(videosGrid, {
            animation: 150, 
            handle: '.drag-handle', // Agarradera
            ghostClass: 'sortable-ghost',
            dragClass: 'sortable-drag',
            forceFallback: true, 
            fallbackOnBody: true, 
            onEnd: function () {
                guardarOrdenVideos();
            }
        });
    }

    function guardarOrdenVideos() {
        const items = document.querySelectorAll('.video-item');
        const orden = [];
        items.forEach((item, index) => {
            orden.push({
                id_v: item.dataset.id,
                orden: index
            });
        });

        fetch('./../controllers/reordenar_videos.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ videos: orden })
        })
        .then(r => r.json())
        .then(d => {
            if (!d.success) {
                console.error('Error al guardar orden:', d.error);
                alert('Error al guardar el orden del video');
            }
        })
        .catch(err => {
            console.error('Error:', err);
            alert('Error en la petición de ordenamiento');
        });
    }
});
</script>
<?php
include (__DIR__ . "/../layout/footerAdmin.php");
?>