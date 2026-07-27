<?php
include (__DIR__ . "/../layout/headerAdmin.php");
$ACL = $_SESSION['ACL']['esperamos'] ?? [
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
include(__DIR__ . "/../data/conexion.php");
require_once(__DIR__ . "/helpers/urlhelper.php");

// Auto-migración: Asegurar que la columna 'url' exista en producción
@$con->query("ALTER TABLE esperamos ADD COLUMN url VARCHAR(500) NULL AFTER imagen");

// Obtener los esperados actuales ordenados
$esperados = [];
$stmt = $con->prepare("
    SELECT e.id, e.noticia_id, e.orden, e.url,
           COALESCE(e.titulo, n.titulo) AS titulo,
           COALESCE(e.imagen, n.crop3) AS crop3,
           n.fecha_publicacion
    FROM esperamos e
    LEFT JOIN noticias n ON e.noticia_id = n.id
    ORDER BY e.orden ASC
");
if ($stmt) {
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res) {
        $esperados = $res->fetch_all(MYSQLI_ASSOC);
    }
}
$totalEsperados = count($esperados);
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4" style="flex-wrap: wrap; gap: 12px;">
        <h1 style="margin:0;">Lo más Esperado</h1>
    </div>

    <!-- Estadísticas rápidas -->
    <div class="stats-grid mb-4">
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(239,51,99,0.1); color: #EF3363;"><i class="bi bi-star-fill"></i></div>
            <div class="stat-info">
                <span class="stat-value" id="countBadge"><?= $totalEsperados ?> / 10</span>
                <span class="stat-label">Artículos Esperados</span>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Columna Izquierda: Lista de esperados (Drag & Drop) -->
        <div class="col-md-7 mb-4">
            <div class="card shadow-sm" style="background: var(--card-bg); border: 1px solid var(--border); border-radius: 12px; height: 100%;">
                <div class="card-header" style="background: transparent; border-bottom: 1px solid var(--border); padding: 16px;">
                    <h3 class="card-title" style="margin: 0; font-size: 1.1rem; font-weight: 700; color: var(--text);">
                        <i class="bi bi-grip-vertical" style="color: var(--muted); margin-right: 4px;"></i> Lista de Esperados (Arrastra para ordenar)
                    </h3>
                </div>
                <div class="card-body" style="padding: 16px;">
                    <div id="esperadosList" class="d-flex flex-column gap-3">
                        <?php if (empty($esperados)): ?>
                            <div id="emptyEsperados" class="text-center py-4" style="color: var(--muted); font-style: italic;">
                                No hay artículos en esta lista. Agrega noticias desde el buscador de la derecha.
                            </div>
                        <?php else: ?>
                            <?php foreach ($esperados as $index => $esp): ?>
                                <div class="esperado-item d-flex align-items-center gap-3 p-3" data-id="<?= $esp['id'] ?>" style="background: var(--bg); border: 1px solid var(--border); border-radius: 8px; cursor: grab;">
                                    <?php if ($ACL['editar']): ?>
                                        <div class="drag-handle" style="color: var(--muted); cursor: grab; font-size: 1.2rem; padding: 0 4px;">
                                            <i class="bi bi-grip-vertical"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div style="font-weight: 800; font-size: 1.1rem; color: var(--accent-fuchsia); width: 24px; text-align: center;" class="ranking-num-label">
                                        <?= $index + 1 ?>
                                    </div>
                                    <div style="width: 80px; aspect-ratio: 16/9; border-radius: 4px; overflow: hidden; background: var(--border); flex-shrink: 0;">
                                        <img src="<?= imageUrl($esp['crop3']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                    </div>
                                    <div style="flex-grow: 1; min-width: 0;">
                                        <h4 style="margin: 0 0 4px; font-size: 0.92rem; font-weight: 700; color: var(--text); overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                            <?= htmlspecialchars($esp['titulo']) ?>
                                        </h4>
                                        <span style="font-size: 0.75rem; color: var(--muted);">
                                            <?php if (!empty($esp['fecha_publicacion'])): ?>
                                                Publicado: <?= date('d/m/Y H:i', strtotime($esp['fecha_publicacion'])) ?>
                                            <?php else: ?>
                                                Personalizado <?= !empty($esp['url']) ? '<span style="color:var(--accent); font-weight:700;">• <i class="bi bi-link-45deg"></i> ' . htmlspecialchars($esp['url']) . '</span>' : '' ?>
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                    <?php if ($ACL['editar']): ?>
                                        <button class="btn btn-delete btn-quitar" data-id="<?= $esp['id'] ?>" style="padding: 6px 10px; font-size: 0.82rem; border-radius: 6px; border: 1px solid #e53e3e; background: transparent; color: #e53e3e;" title="Quitar de la lista">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Columna Derecha: Buscador de noticias & Agregar Personalizado -->
        <div class="col-md-5 mb-4 d-flex flex-column gap-4">
            <!-- Card 1: Buscar Noticias -->
            <div class="card shadow-sm" style="background: var(--card-bg); border: 1px solid var(--border); border-radius: 12px;">
                <div class="card-header" style="background: transparent; border-bottom: 1px solid var(--border); padding: 16px;">
                    <h3 class="card-title" style="margin: 0; font-size: 1.1rem; font-weight: 700; color: var(--text);">
                        <i class="bi bi-search" style="color: var(--muted); margin-right: 4px;"></i> Buscar Noticias para Agregar
                    </h3>
                </div>
                <div class="card-body" style="padding: 16px; display: flex; flex-direction: column; gap: 16px;">
                    <div style="position: relative;">
                        <input type="text" id="buscarNoticiaInput" class="form-control admin-search-input" placeholder="Buscar por título..." style="width: 100%; padding: 10px 14px 10px 36px; border: 1px solid var(--border); border-radius: 8px; background: var(--bg); color: var(--text); font-size: 0.9rem;">
                        <i class="bi bi-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--muted); font-size: 0.95rem;"></i>
                    </div>
                    <div id="buscarResultados" class="d-flex flex-column gap-3" style="max-height: 480px; overflow-y: auto; padding-right: 4px;">
                        <!-- Loader / Resultados AJAX -->
                        <div class="text-center py-4 text-muted" style="font-style: italic;">
                            Escribe en el buscador para buscar noticias.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card 2: Agregar Personalizado -->
            <div class="card shadow-sm" style="background: var(--card-bg); border: 1px solid var(--border); border-radius: 12px;">
                <div class="card-header" style="background: transparent; border-bottom: 1px solid var(--border); padding: 16px;">
                    <h3 class="card-title" style="margin: 0; font-size: 1.1rem; font-weight: 700; color: var(--text);">
                        <i class="bi bi-plus-circle" style="color: var(--muted); margin-right: 4px;"></i> Agregar Item Personalizado
                    </h3>
                </div>
                <div class="card-body" style="padding: 16px;">
                    <form id="customItemForm" enctype="multipart/form-data" class="d-flex flex-column gap-3">
                        <input type="hidden" name="imagenCrop" id="customImageCrop" value="">
                        <div>
                            <label for="customTitle" class="form-label" style="font-size: 0.88rem; font-weight: 600; color: var(--text); display: block; margin-bottom: 6px;">Título *</label>
                            <input type="text" id="customTitle" name="titulo" class="form-control" placeholder="Escribe el título..." style="width: 100%; padding: 10px 12px; border: 1px solid var(--border); border-radius: 8px; background: var(--bg); color: var(--text); font-size: 0.9rem;" required>
                        </div>
                        <div>
                            <label for="customUrl" class="form-label" style="font-size: 0.88rem; font-weight: 600; color: var(--text); display: block; margin-bottom: 6px;">Enlace URL de Redirección (Opcional)</label>
                            <div style="position: relative;">
                                <input type="url" id="customUrl" name="url" class="form-control" placeholder="Ej: https://catink.com.mx/estreno" style="width: 100%; padding: 10px 12px 10px 36px; border: 1px solid var(--border); border-radius: 8px; background: var(--bg); color: var(--text); font-size: 0.9rem;">
                                <i class="bi bi-link-45deg" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--muted); font-size: 1.1rem;"></i>
                            </div>
                        </div>
                        <div>
                            <label class="form-label" style="font-size: 0.88rem; font-weight: 600; color: var(--text); display: block; margin-bottom: 6px;">Imagen (JPG, PNG, WEBP)</label>
                            <!-- Styled click upload zone -->
                            <div class="upload-zone" id="uploadZone" onclick="document.getElementById('customImage').click()" style="border: 2px dashed var(--border); border-radius: 10px; background: var(--bg); display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 5px; cursor: pointer; color: var(--muted); font-size: 0.82rem; text-align: center; padding: 24px 12px; transition: border-color .2s, background .2s;">
                                <i class="bi bi-cloud-arrow-up" style="font-size: 24px;"></i>
                                <span>Haz clic para seleccionar imagen</span>
                                <span style="font-size:11px">PNG, JPG, WEBP</span>
                            </div>
                            <input type="file" id="customImage" name="imagen" accept="image/*" style="display:none;">
                        </div>

                        <!-- Cropper inline container -->
                        <div class="crop-inline-wrap" id="cropperContainer" style="margin-top: 14px; border: 1px solid var(--border); border-radius: 10px; overflow: hidden; display: none;">
                            <div class="crop-inline-head" style="display: flex; align-items: center; justify-content: space-between; padding: 10px 14px; border-bottom: 1px solid var(--border); font-size: 13px; font-weight: 600; color: var(--text);">
                                <span><i class="bi bi-crop"></i> Ajustar recorte (8:3)</span>
                            </div>
                            <div class="crop-inline-body" style="padding: 12px; background: var(--bg);">
                                <img id="cropImg" style="max-width: 100%; display: block; max-height: 260px; margin: 0 auto;" draggable="false">
                            </div>
                            <div class="crop-inline-foot" style="display: flex; gap: 8px; padding: 10px 14px; border-top: 1px solid var(--border);">
                                <button type="button" id="cropConfirmBtn" class="btn btn-accent" style="padding: 6px 12px; font-size: 0.82rem; border-radius: 6px;"><i class="bi bi-check-lg"></i> Confirmar</button>
                                <button type="button" id="cropCancelBtn" class="btn btn-outline-secondary" style="padding: 6px 12px; font-size: 0.82rem; border-radius: 6px; border: 1px solid var(--border);">Cancelar</button>
                            </div>
                        </div>

                        <!-- Preview container -->
                        <div class="cp-preview-wrap" id="previewContainer" style="display: none; margin-top: 14px;">
                            <p class="cp-preview-label" style="font-size: 11px; font-weight: 600; color: var(--muted); text-transform: uppercase; margin-bottom: 6px;">Vista previa</p>
                            <img id="previewImg" class="cp-preview-img" style="width: 100%; border-radius: 10px; border: 1px solid var(--border); display: block;">
                            <button type="button" class="btn btn-outline-secondary" style="margin-top:10px; width:100%; padding: 8px; font-size: 0.82rem;" onclick="document.getElementById('customImage').click()">
                                <i class="bi bi-arrow-repeat"></i> Cambiar imagen
                            </button>
                        </div>

                        <button type="submit" class="btn btn-accent w-100" style="padding: 10px; font-size: 0.9rem; font-weight: 600; border-radius: 8px;">
                            <i class="bi bi-check-lg"></i> Agregar a la Lista
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.sortable-ghost {
    opacity: 0.4;
    background-color: rgba(239, 51, 99, 0.05) !important;
    border-style: dashed !important;
}
.sortable-drag {
    background-color: var(--card-bg) !important;
    box-shadow: 0 5px 15px rgba(0,0,0,0.15) !important;
    cursor: grabbing !important;
}
.esperado-item {
    transition: background-color 0.2s;
}
.esperado-item:hover {
    background-color: rgba(239, 51, 99, 0.02) !important;
}
.btn-quitar:hover {
    background: #e53e3e !important;
    color: #fff !important;
}
</style>

<!-- SortableJS para Drag and Drop fluido -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const esperadosList = document.getElementById('esperadosList');
    const countBadge = document.getElementById('countBadge');
    const buscarNoticiaInput = document.getElementById('buscarNoticiaInput');
    const buscarResultados = document.getElementById('buscarResultados');

    let sortableInstance = null;

    // 1. Inicializar SortableJS para arrastrar y ordenar
    const initSortable = () => {
        if (esperadosList.querySelector('.esperado-item') && ACL.editar) {
            sortableInstance = new Sortable(esperadosList, {
                animation: 150,
                handle: '.drag-handle',
                ghostClass: 'sortable-ghost',
                dragClass: 'sortable-drag',
                forceFallback: true,
                fallbackOnBody: true,
                onEnd: function () {
                    guardarOrdenEsperados();
                    renumerarRankings();
                }
            });
        }
    };

    initSortable();

    // 2. Renumerar los números de ranking visuales
    function renumerarRankings() {
        const items = esperadosList.querySelectorAll('.esperado-item');
        items.forEach((item, index) => {
            const label = item.querySelector('.ranking-num-label');
            if (label) {
                label.textContent = index + 1;
            }
        });
    }

    // 3. Obtener conteo actual
    function obtenerConteo() {
        return esperadosList.querySelectorAll('.esperado-item').length;
    }

    // 4. Actualizar el indicador superior
    function actualizarContador() {
        const total = obtenerConteo();
        countBadge.textContent = `${total} / 10`;
    }

    // 5. Guardar el orden en BD mediante AJAX
    function guardarOrdenEsperados() {
        const items = esperadosList.querySelectorAll('.esperado-item');
        const orden = [];
        items.forEach((item, index) => {
            orden.push({
                id: item.dataset.id,
                orden: index
            });
        });

        fetch('./../controllers/reordenar_esperados.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ esperados: orden })
        })
        .then(r => r.json())
        .then(d => {
            if (!d.success) {
                console.error('Error al guardar el orden:', d.error);
                alert('No se pudo guardar el orden de la lista');
            }
        })
        .catch(err => {
            console.error('Error:', err);
            alert('Error en la petición de ordenamiento');
        });
    }

    // 6. Eliminar artículo esperado
    esperadosList.addEventListener('click', e => {
        const btn = e.target.closest('.btn-quitar');
        if (!btn) return;
        
        if (!ACL.editar) {
            alert('No tienes permisos de edición');
            return;
        }

        const id = btn.dataset.id;
        const form = new FormData();
        form.append('id', id);

        fetch('./../controllers/eliminar_esperado.php', {
            method: 'POST',
            body: form
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                const item = btn.closest('.esperado-item');
                item.remove();
                actualizarContador();
                renumerarRankings();
                
                // Si la lista quedó vacía, mostrar el mensaje vacío
                if (obtenerConteo() === 0) {
                    if (sortableInstance) {
                        sortableInstance.destroy();
                        sortableInstance = null;
                    }
                    esperadosList.innerHTML = `
                        <div id="emptyEsperados" class="text-center py-4" style="color: var(--muted); font-style: italic;">
                            No hay artículos en esta lista. Agrega noticias desde el buscador de la derecha.
                        </div>
                    `;
                }

                // Refrescar resultados de búsqueda para que reaparezca la noticia eliminada
                buscarNoticias(buscarNoticiaInput.value);
            } else {
                alert(d.error || 'Error al eliminar artículo esperado');
            }
        })
        .catch(err => console.error(err));
    });

    // 7. Buscador en tiempo real de noticias (AJAX)
    let searchTimer = null;
    buscarNoticiaInput.addEventListener('input', () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => {
            buscarNoticias(buscarNoticiaInput.value);
        }, 300);
    });

    // Carga inicial de últimas noticias
    buscarNoticias('');

    function buscarNoticias(query) {
        fetch(`./../controllers/buscar_noticias_esperamos_admin.php?q=${encodeURIComponent(query)}`)
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                renderizarResultados(d.noticias);
            } else {
                console.error(d.error);
            }
        })
        .catch(err => console.error(err));
    }

    function renderizarResultados(noticias) {
        if (noticias.length === 0) {
            buscarResultados.innerHTML = `
                <div class="text-center py-4 text-muted" style="font-style: italic;">
                    No se encontraron noticias disponibles.
                </div>
            `;
            return;
        }

        buscarResultados.innerHTML = noticias.map(n => `
            <div class="d-flex align-items-center gap-3 p-2 border rounded" style="background: var(--bg); border-color: var(--border) !important;">
                <div style="width: 60px; aspect-ratio: 16/9; border-radius: 4px; overflow: hidden; background: var(--border); flex-shrink: 0;">
                    <img src="${n.imagen}" style="width: 100%; height: 100%; object-fit: cover;">
                </div>
                <div style="flex-grow: 1; min-width: 0;">
                    <h5 style="margin: 0 0 2px; font-size: 0.85rem; font-weight: 700; color: var(--text); overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                        ${escapeHtml(n.titulo)}
                    </h5>
                    <span style="font-size: 0.7rem; color: var(--muted);">${n.fecha}</span>
                </div>
                <button class="btn btn-accent btn-agregar" data-id="${n.id}" data-titulo="${escapeHtml(n.titulo)}" data-imagen="${n.imagen}" data-fecha="${n.fecha}" style="padding: 6px 12px; font-size: 0.8rem; border-radius: 6px;">
                    <i class="bi bi-plus-lg"></i> Agregar
                </button>
            </div>
        `).join('');

        // Verificar si la lista ya está llena para deshabilitar botones
        evaluarLimiteEsperados();
    }

    function evaluarLimiteEsperados() {
        const total = obtenerConteo();
        const botones = buscarResultados.querySelectorAll('.btn-agregar');
        botones.forEach(btn => {
            if (total >= 10) {
                btn.disabled = true;
                btn.title = 'Límite máximo de 10 esperados alcanzado';
                btn.style.opacity = '0.5';
            } else {
                btn.disabled = false;
                btn.title = '';
                btn.style.opacity = '1';
            }
        });
    }

    function escapeHtml(text) {
        return text
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    // 8. Agregar noticia a esperados
    buscarResultados.addEventListener('click', e => {
        const btn = e.target.closest('.btn-agregar');
        if (!btn) return;

        if (!ACL.editar) {
            alert('No tienes permisos de edición');
            return;
        }

        const total = obtenerConteo();
        if (total >= 10) {
            alert('Límite máximo de 10 esperados alcanzado');
            return;
        }

        const id = btn.dataset.id;
        const titulo = btn.dataset.titulo;
        const imagen = btn.dataset.imagen;
        const fecha = btn.dataset.fecha;

        const form = new FormData();
        form.append('noticia_id', id);

        fetch('./../controllers/agregar_esperado.php', {
            method: 'POST',
            body: form
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                // Eliminar placeholder vacío si existe
                const empty = document.getElementById('emptyEsperados');
                if (empty) empty.remove();

                // Crear elemento esperado en el DOM
                const nuevoIndex = obtenerConteo() + 1;
                const div = document.createElement('div');
                div.className = 'esperado-item d-flex align-items-center gap-3 p-3';
                div.dataset.id = d.id;
                div.style.cssText = 'background: var(--bg); border: 1px solid var(--border); border-radius: 8px; cursor: grab;';
                div.innerHTML = `
                    <div class="drag-handle" style="color: var(--muted); cursor: grab; font-size: 1.2rem; padding: 0 4px;">
                        <i class="bi bi-grip-vertical"></i>
                    </div>
                    <div style="font-weight: 800; font-size: 1.1rem; color: var(--accent-fuchsia); width: 24px; text-align: center;" class="ranking-num-label">
                        ${nuevoIndex}
                    </div>
                    <div style="width: 80px; aspect-ratio: 16/9; border-radius: 4px; overflow: hidden; background: var(--border); flex-shrink: 0;">
                        <img src="${imagen}" style="width: 100%; height: 100%; object-fit: cover;">
                    </div>
                    <div style="flex-grow: 1; min-width: 0;">
                        <h4 style="margin: 0 0 4px; font-size: 0.92rem; font-weight: 700; color: var(--text); overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                            ${escapeHtml(titulo)}
                        </h4>
                        <span style="font-size: 0.75rem; color: var(--muted);">Publicado: ${fecha}</span>
                    </div>
                    <button class="btn btn-delete btn-quitar" data-id="${d.id}" style="padding: 6px 10px; font-size: 0.82rem; border-radius: 6px; border: 1px solid #e53e3e; background: transparent; color: #e53e3e;" title="Quitar de la lista">
                        <i class="bi bi-trash"></i>
                    </button>
                `;

                esperadosList.appendChild(div);
                actualizarContador();
                
                // Si SortableJS no estaba inicializado (estaba vacío), inicializarlo
                if (!sortableInstance) {
                    initSortable();
                }

                // Remover el ítem agregado del panel de búsqueda
                btn.closest('.border').remove();
                
                // Recargar el buscador para mantener los datos sincronizados
                buscarNoticias(buscarNoticiaInput.value);
            } else {
                alert(d.error || 'Error al agregar artículo esperado');
            }
        })
        .catch(err => console.error(err));
    });

    // 9. Agregar esperado personalizado con recorte de imagen
    let customCropper = null;

    const customImage = document.getElementById('customImage');
    const uploadZone = document.getElementById('uploadZone');
    const cropperContainer = document.getElementById('cropperContainer');
    const cropImg = document.getElementById('cropImg');
    const cropConfirmBtn = document.getElementById('cropConfirmBtn');
    const cropCancelBtn = document.getElementById('cropCancelBtn');
    const previewContainer = document.getElementById('previewContainer');
    const previewImg = document.getElementById('previewImg');
    const customImageCrop = document.getElementById('customImageCrop');

    if (customImage) {
        customImage.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onload = function(event) {
                cropImg.src = event.target.result;
                cropperContainer.style.display = 'block';
                previewContainer.style.display = 'none';
                uploadZone.style.display = 'none';

                if (customCropper) customCropper.destroy();

                customCropper = new Cropper(cropImg, {
                    dragMode: 'none',
                    aspectRatio: 8/3,
                    autoCropArea: 1,
                    responsive: true,
                    restore: true,
                    guides: true,
                    center: true,
                    highlight: true,
                    cropBoxMovable: true,
                    cropBoxResizable: true,
                    toggleDragModeOnDblclick: false,
                });
            };
            reader.readAsDataURL(file);
        });
    }

    if (cropConfirmBtn) {
        cropConfirmBtn.addEventListener('click', function(e) {
            e.preventDefault();
            if (!customCropper) return;

            const canvas = customCropper.getCroppedCanvas({
                width: 800,
                height: 300,
                imageSmoothingEnabled: true,
                imageSmoothingQuality: 'high',
            });

            const data64 = canvas.toDataURL('image/png');
            customImageCrop.value = data64;

            previewImg.src = data64;
            previewContainer.style.display = 'block';
            cropperContainer.style.display = 'none';
            uploadZone.style.display = 'none';
            if (customCropper) { customCropper.destroy(); customCropper = null; }
        });
    }

    if (cropCancelBtn) {
        cropCancelBtn.addEventListener('click', function(e) {
            e.preventDefault();
            customImage.value = '';
            customImageCrop.value = '';
            cropperContainer.style.display = 'none';
            previewContainer.style.display = 'none';
            uploadZone.style.display = 'flex';
            if (customCropper) { customCropper.destroy(); customCropper = null; }
        });
    }

    const customItemForm = document.getElementById('customItemForm');
    if (customItemForm) {
        customItemForm.addEventListener('submit', e => {
            e.preventDefault();

            if (!ACL.editar) {
                alert('No tienes permisos de edición');
                return;
            }

            const total = obtenerConteo();
            if (total >= 10) {
                alert('Límite máximo de 10 esperados alcanzado');
                return;
            }

            const base64 = customImageCrop.value;
            if (!base64) {
                alert('Por favor selecciona una imagen y confirma el recorte.');
                return;
            }

            const formData = new FormData(customItemForm);

            fetch('./../controllers/agregar_esperado_personalizado.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(d => {
                if (d.success) {
                    // Eliminar placeholder vacío si existe
                    const empty = document.getElementById('emptyEsperados');
                    if (empty) empty.remove();

                    // Crear elemento esperado en el DOM
                    const nuevoIndex = obtenerConteo() + 1;
                    const div = document.createElement('div');
                    div.className = 'esperado-item d-flex align-items-center gap-3 p-3';
                    div.dataset.id = d.id;
                    div.style.cssText = 'background: var(--bg); border: 1px solid var(--border); border-radius: 8px; cursor: grab;';
                    div.innerHTML = `
                        <div class="drag-handle" style="color: var(--muted); cursor: grab; font-size: 1.2rem; padding: 0 4px;">
                            <i class="bi bi-grip-vertical"></i>
                        </div>
                        <div style="font-weight: 800; font-size: 1.1rem; color: var(--accent-fuchsia); width: 24px; text-align: center;" class="ranking-num-label">
                            ${nuevoIndex}
                        </div>
                        <div style="width: 80px; aspect-ratio: 16/9; border-radius: 4px; overflow: hidden; background: var(--border); flex-shrink: 0;">
                            <img src="./../${d.imagen}" style="width: 100%; height: 100%; object-fit: cover;">
                        </div>
                        <div style="flex-grow: 1; min-width: 0;">
                            <h4 style="margin: 0 0 4px; font-size: 0.92rem; font-weight: 700; color: var(--text); overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                ${escapeHtml(d.titulo)}
                            </h4>
                            <span style="font-size: 0.75rem; color: var(--muted);">
                                Personalizado ${d.url ? `<span style="color:var(--accent); font-weight:700;">• <i class="bi bi-link-45deg"></i> ${escapeHtml(d.url)}</span>` : ''}
                            </span>
                        </div>
                        <button class="btn btn-delete btn-quitar" data-id="${d.id}" style="padding: 6px 10px; font-size: 0.82rem; border-radius: 6px; border: 1px solid #e53e3e; background: transparent; color: #e53e3e;" title="Quitar de la lista">
                            <i class="bi bi-trash"></i>
                        </button>
                    `;

                    esperadosList.appendChild(div);
                    actualizarContador();
                    
                    // Si SortableJS no estaba inicializado (estaba vacío), inicializarlo
                    if (!sortableInstance) {
                        initSortable();
                    }

                    // Limpiar formulario y resetear zona de subida/recorte
                    customItemForm.reset();
                    customImageCrop.value = '';
                    previewContainer.style.display = 'none';
                    uploadZone.style.display = 'flex';
                    
                    // Actualizar botones del buscador por si se alcanzó el límite
                    buscarNoticias(buscarNoticiaInput.value);
                } else {
                    alert(d.error || 'Error al agregar esperado personalizado');
                }
            })
            .catch(err => {
                console.error(err);
                alert('Error al enviar el formulario');
            });
        });
    }
});
</script>
<?php
include (__DIR__ . "/../layout/footerAdmin.php");
?>
