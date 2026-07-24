<?php
include("./../layout/headerAdmin.php");
include("./../data/conexion.php");
require_once("./../views/helpers/iconos_categorias.php");

// Auto-migración defensiva: mismas columnas que crea layout/header.php. Se repite
// aquí porque el panel usa headerAdmin.php y podría abrirse antes de que alguien
// visite el sitio público (o antes de correr migrations.sql en producción).
$checkIconoCol = @$con->query("SHOW COLUMNS FROM categorias LIKE 'icono'");
if ($checkIconoCol && $checkIconoCol->num_rows === 0) {
    @$con->query("ALTER TABLE categorias ADD icono VARCHAR(64) NOT NULL DEFAULT 'bi-tag-fill' AFTER nombre");
}
$checkVisibleCol = @$con->query("SHOW COLUMNS FROM categorias LIKE 'visible_menu'");
if ($checkVisibleCol && $checkVisibleCol->num_rows === 0) {
    @$con->query("ALTER TABLE categorias ADD visible_menu TINYINT(1) NOT NULL DEFAULT 1 AFTER icono");
}
$checkIconoImgCol = @$con->query("SHOW COLUMNS FROM categorias LIKE 'icono_img'");
if ($checkIconoImgCol && $checkIconoImgCol->num_rows === 0) {
    @$con->query("ALTER TABLE categorias ADD icono_img VARCHAR(255) NULL DEFAULT NULL AFTER icono");
}

// Interruptores globales de dónde se ven los iconos (filas de `secciones`).
$cfgIconos = ['iconos_menu_movil' => 1, 'iconos_menu_escritorio' => 0];
$resCfg = @$con->query("SELECT nombre, estado FROM secciones WHERE nombre IN ('iconos_menu_movil','iconos_menu_escritorio')");
if ($resCfg) {
    while ($f = $resCfg->fetch_assoc()) {
        $cfgIconos[$f['nombre']] = intval($f['estado']);
    }
}

// Obtener todas las categorías y conteo de noticias
$q = trim($_GET['q'] ?? '');
if($q !== ''){
    $like = $con->real_escape_string("%$q%");
    $sql = "
    SELECT c.id_c, c.nombre, c.icono, c.visible_menu, c.orden, COUNT(DISTINCT nc.noticia_id) AS total_noticias
    FROM categorias c
    LEFT JOIN noticia_categoria nc ON c.id_c = nc.categoria_id
    WHERE c.nombre LIKE '$like'
    GROUP BY c.id_c, c.nombre, c.icono, c.visible_menu, c.orden
    ORDER BY c.orden ASC, c.nombre ASC
    ";
} else {
    $sql = "
    SELECT c.id_c, c.nombre, c.icono, c.visible_menu, c.orden, COUNT(DISTINCT nc.noticia_id) AS total_noticias
    FROM categorias c
    LEFT JOIN noticia_categoria nc ON c.id_c = nc.categoria_id
    GROUP BY c.id_c, c.nombre, c.icono, c.visible_menu, c.orden
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
$totalOcultas = count(array_filter($categorias, fn($c) => intval($c['visible_menu']) === 0));
$gruposIconos = iconosCategoriaPorGrupo();
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
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(148,163,184,0.12); color: #94a3b8;"><i class="bi bi-eye-slash-fill"></i></div>
            <div class="stat-info">
                <span class="stat-value" id="statOcultas"><?= $totalOcultas ?></span>
                <span class="stat-label">Ocultas del Menú</span>
            </div>
        </div>
    </div>

    <!-- Dónde se muestran los iconos de categoría en el menú público.
         Ajuste global: aplica a todas las categorías por igual. -->
    <div class="card shadow-sm" style="margin-bottom:18px;">
        <div class="card-body" style="display:flex; flex-wrap:wrap; align-items:center; gap:18px 32px;">
            <div style="flex:1; min-width:230px;">
                <strong style="display:block; font-size:0.95rem;">Iconos en el menú</strong>
                <span style="font-size:0.82rem; color:var(--muted);">
                    Elige en qué versión del sitio se muestran los iconos junto al nombre de cada categoría.
                </span>
            </div>
            <label class="icono-switch <?= $ACL['editar'] ? '' : 'is-disabled' ?>">
                <input type="checkbox" class="chk-iconos-cfg" data-clave="iconos_menu_movil"
                       <?= $cfgIconos['iconos_menu_movil'] ? 'checked' : '' ?>
                       <?= $ACL['editar'] ? '' : 'disabled' ?>>
                <span class="icono-switch-track"></span>
                <span><i class="bi bi-phone"></i> Versión móvil</span>
            </label>
            <label class="icono-switch <?= $ACL['editar'] ? '' : 'is-disabled' ?>">
                <input type="checkbox" class="chk-iconos-cfg" data-clave="iconos_menu_escritorio"
                       <?= $cfgIconos['iconos_menu_escritorio'] ? 'checked' : '' ?>
                       <?= $ACL['editar'] ? '' : 'disabled' ?>>
                <span class="icono-switch-track"></span>
                <span><i class="bi bi-display"></i> Versión escritorio</span>
            </label>
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
                            <th style="width: 60px; text-align:center;">Icono</th>
                            <th>Nombre</th>
                            <th>Total Noticias</th>
                            <th>Menú</th>
                            <?php if($ACL['editar'] || $ACL['eliminar']): ?>
                                <th>Acciones</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody id="categoriasBody">
                        <?php if(empty($categorias)): ?>
                            <tr><td colspan="6" style="text-align:center; padding:30px; color:var(--muted);">No se encontraron categorías.</td></tr>
                        <?php else: ?>
                            <?php foreach($categorias as $row):
                                $icono    = sanearIconoCategoria($row['icono'] ?? null);
                                $iconoImg = sanearIconoImgCategoria($row['icono_img'] ?? null);
                                $visible  = intval($row['visible_menu']) === 1;
                            ?>
                            <tr data-id="<?= $row['id_c'] ?>" class="categoria-row">
                                <td style="cursor: grab; text-align: center; color:var(--muted);"><i class="bi bi-grip-vertical"></i></td>
                                <td style="text-align:center;">
                                    <span class="cat-icon-preview">
                                        <?php if($iconoImg): ?>
                                            <img src="<?= htmlspecialchars(imageUrl($iconoImg)) ?>" alt="">
                                        <?php else: ?>
                                            <i class="bi <?= htmlspecialchars($icono) ?>"></i>
                                        <?php endif; ?>
                                    </span>
                                </td>
                                <td><strong class="table-title"><?= htmlspecialchars($row['nombre']) ?></strong></td>
                                <td><span class="estado-badge estado-publicado" style="background:rgba(16,185,129,0.1); color:#10b981;"><?= $row['total_noticias'] ?> noticias</span></td>
                                <td>
                                    <?php if($ACL['editar']): ?>
                                        <button type="button" class="btn btn-link p-0 text-decoration-none btn-toggle-menu"
                                            data-id="<?= $row['id_c'] ?>"
                                            title="Clic para mostrar u ocultar esta categoría en el menú"
                                            style="border:none; background:none;">
                                    <?php endif; ?>
                                        <span class="badge badge-menu <?= $visible ? 'is-visible' : 'is-oculta' ?>">
                                            <i class="bi <?= $visible ? 'bi-eye-fill' : 'bi-eye-slash-fill' ?>"></i>
                                            <?= $visible ? 'Visible' : 'Oculta' ?>
                                        </span>
                                    <?php if($ACL['editar']): ?>
                                        </button>
                                    <?php endif; ?>
                                </td>
                                <?php if($ACL['editar'] || $ACL['eliminar']): ?>
                                    <td>
                                        <div class="noticias-actions" style="border-top:none; padding:0; justify-content:flex-start;">
                                            <?php if($ACL['editar']): ?>
                                                <button class="btn btn-edit btn-editar"
                                                    data-id="<?= $row['id_c'] ?>"
                                                    data-icono="<?= htmlspecialchars($icono) ?>"
                                                    data-icono-img="<?= htmlspecialchars($iconoImg ? imageUrl($iconoImg) : '') ?>"
                                                    data-icono-img-raw="<?= htmlspecialchars($iconoImg ?? '') ?>"
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
    <div class="card" style="max-width: 480px; width:100%;">
        <div class="crop-modal-content">
            <h3 id="modalTitle"></h3>
            <p id="modalConfirmText" style="display:none; color:var(--muted); font-size:0.9rem; margin-bottom:15px;"></p>
            <form id="modalForm">
                <input type="hidden" name="id_c" id="modalId">
                <div style="margin-bottom: 16px;" id="modalNombreWrapper">
                    <label for="modalNombre" style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text);">Nombre de la categoría</label>
                    <input type="text" id="modalNombre" name="nombre" required style="width: 100%; padding: 10px 14px; border: 1px solid var(--border); border-radius: 8px; font-size: 0.9rem; background: var(--card-bg); color: var(--text);">
                </div>
                <!-- Selector de icono: el valor real viaja en el input oculto; los
                     botones solo lo actualizan. El catálogo sale de
                     views/helpers/iconos_categorias.php, el mismo que valida el POST. -->
                <div style="margin-bottom: 16px;" id="modalIconoWrapper">
                    <label style="display:block; font-size:0.85rem; font-weight:600; margin-bottom:6px; color:var(--text);">
                        Icono en el menú
                        <span id="modalIconoActual" class="cat-icon-preview" style="margin-left:6px; vertical-align:middle;"><i class="bi bi-tag-fill"></i></span>
                    </label>
                    <!-- Valor real que viaja por POST. icono = glifo del catálogo;
                         icono_img = ruta de la imagen subida (gana si tiene valor). -->
                    <input type="hidden" name="icono" id="modalIcono" value="<?= ICONO_CATEGORIA_DEFAULT ?>">
                    <input type="hidden" name="icono_img" id="modalIconoImg" value="">

                    <!-- Subir imagen propia -->
                    <div class="icono-upload">
                        <div class="icono-upload-preview" id="iconoUploadPreview" hidden>
                            <img src="" alt="" id="iconoUploadImg">
                        </div>
                        <div class="icono-upload-controls">
                            <button type="button" class="btn btn-secondary btn-sm" id="btnSubirIcono">
                                <i class="bi bi-upload"></i> Subir imagen
                            </button>
                            <button type="button" class="btn btn-secondary btn-sm" id="btnQuitarIcono" hidden>
                                <i class="bi bi-x-lg"></i> Quitar imagen
                            </button>
                            <input type="file" id="iconoFile" accept="image/png,image/jpeg,image/gif,image/webp" hidden>
                        </div>
                        <span class="icono-upload-hint">PNG, JPG, GIF o WEBP. Se recorta a un cuadro pequeño; ideal con fondo transparente.</span>
                    </div>

                    <div class="icon-picker-sep">o elige un icono</div>
                    <div class="icon-picker">
                        <?php foreach($gruposIconos as $grupo => $iconos): ?>
                            <div class="icon-picker-group"><?= htmlspecialchars($grupo) ?></div>
                            <div class="icon-picker-grid">
                                <?php foreach($iconos as $ic): ?>
                                    <button type="button" class="icon-option" data-icono="<?= htmlspecialchars($ic) ?>" title="<?= htmlspecialchars($ic) ?>">
                                        <i class="bi <?= htmlspecialchars($ic) ?>"></i>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
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
/* Icono de la categoria en la tabla y en la etiqueta del modal */
.cat-icon-preview {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 34px;
    height: 34px;
    border-radius: 8px;
    background: rgba(239, 51, 99, 0.12);
    color: var(--accent);
    font-size: 1rem;
    overflow: hidden;
}
.cat-icon-preview img {
    width: 100%;
    height: 100%;
    object-fit: contain;
}
/* Interruptores globales de iconos (parte superior) */
.icono-switch {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    font-size: 0.88rem;
    font-weight: 600;
    color: var(--text);
    cursor: pointer;
    user-select: none;
}
.icono-switch.is-disabled { cursor: default; opacity: 0.6; }
.icono-switch input { display: none; }
.icono-switch-track {
    position: relative;
    width: 42px;
    height: 24px;
    border-radius: 999px;
    background: var(--border);
    transition: background 0.2s ease;
    flex-shrink: 0;
}
.icono-switch-track::after {
    content: "";
    position: absolute;
    top: 3px;
    left: 3px;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    background: #fff;
    transition: transform 0.2s ease;
}
.icono-switch input:checked + .icono-switch-track { background: var(--accent); }
.icono-switch input:checked + .icono-switch-track::after { transform: translateX(18px); }
/* Subida de imagen en el modal */
.icono-upload {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 10px 14px;
    margin-bottom: 12px;
}
.icono-upload-preview {
    width: 48px;
    height: 48px;
    border-radius: 8px;
    border: 1px solid var(--border);
    background: rgba(0,0,0,0.15);
    overflow: hidden;
    flex-shrink: 0;
}
.icono-upload-preview img { width: 100%; height: 100%; object-fit: contain; }
.icono-upload-controls { display: flex; gap: 8px; flex-wrap: wrap; }
.icono-upload-hint {
    flex-basis: 100%;
    font-size: 0.75rem;
    color: var(--muted);
}
.icon-picker-sep {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 0.75rem;
    color: var(--muted);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin: 6px 0 10px;
}
.icon-picker-sep::before,
.icon-picker-sep::after {
    content: "";
    flex: 1;
    height: 1px;
    background: var(--border);
}
/* Badge de visibilidad en el menu */
.badge-menu {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 0.78rem;
    font-weight: 800;
    padding: 6px 12px;
    border-radius: 20px;
    white-space: nowrap;
    cursor: pointer;
}
.badge-menu.is-visible {
    background: rgba(16, 185, 129, 0.12);
    color: #10b981;
    border: 1px solid rgba(16, 185, 129, 0.25);
}
.badge-menu.is-oculta {
    background: rgba(148, 163, 184, 0.12);
    color: #94a3b8;
    border: 1px solid rgba(148, 163, 184, 0.25);
}
/* Selector de iconos */
.icon-picker {
    max-height: 240px;
    overflow-y: auto;
    border: 1px solid var(--border);
    border-radius: 8px;
    padding: 10px 12px;
    background: var(--card-bg);
    scrollbar-width: thin;
}
.icon-picker-group {
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--muted);
    margin: 10px 0 6px;
}
.icon-picker-group:first-child { margin-top: 0; }
.icon-picker-grid {
    display: grid;
    grid-template-columns: repeat(8, 1fr);
    gap: 6px;
}
@media (max-width: 520px) {
    .icon-picker-grid { grid-template-columns: repeat(6, 1fr); }
}
.icon-option {
    display: flex;
    align-items: center;
    justify-content: center;
    aspect-ratio: 1;
    border: 1px solid var(--border);
    border-radius: 8px;
    background: transparent;
    color: var(--text);
    font-size: 1rem;
    cursor: pointer;
    transition: background 0.15s ease, border-color 0.15s ease, color 0.15s ease;
}
.icon-option:hover {
    border-color: var(--accent);
    color: var(--accent);
}
.icon-option.selected {
    background: var(--accent);
    border-color: var(--accent);
    color: #fff;
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
    const modalIconoWrapper = document.getElementById('modalIconoWrapper');
    const modalIcono = document.getElementById('modalIcono');
    const modalIconoImg = document.getElementById('modalIconoImg');
    const modalIconoActual = document.getElementById('modalIconoActual');
    const iconoUploadPreview = document.getElementById('iconoUploadPreview');
    const iconoUploadImg = document.getElementById('iconoUploadImg');
    const btnSubirIcono = document.getElementById('btnSubirIcono');
    const btnQuitarIcono = document.getElementById('btnQuitarIcono');
    const iconoFile = document.getElementById('iconoFile');
    const btnCrear = document.getElementById('btnCrear');
    const ICONO_DEFAULT = <?= json_encode(ICONO_CATEGORIA_DEFAULT) ?>;

    // Marca el icono elegido en la cuadrícula, actualiza la vista previa de la
    // etiqueta y deja el valor listo en el input oculto que se envía por POST.
    // Elegir un glifo del catálogo descarta cualquier imagen subida.
    function seleccionarIcono(icono) {
        const valido = document.querySelector(`.icon-option[data-icono="${icono}"]`) ? icono : ICONO_DEFAULT;
        modalIcono.value = valido;
        quitarImagen();               // glifo e imagen son excluyentes
        modalIconoActual.innerHTML = `<i class="bi ${valido}"></i>`;
        document.querySelectorAll('.icon-option').forEach(opt => {
            opt.classList.toggle('selected', opt.dataset.icono === valido);
        });
    }

    // Refleja en el modal que hay una imagen activa: preview, boton de quitar y
    // la etiqueta superior. rawPath es la ruta que se guarda (uploads/iconos/..),
    // displayUrl es la URL servible para el <img>.
    function usarImagen(rawPath, displayUrl) {
        modalIconoImg.value = rawPath;
        iconoUploadImg.src = displayUrl;
        iconoUploadPreview.hidden = false;
        btnQuitarIcono.hidden = false;
        modalIconoActual.innerHTML = `<img src="${displayUrl}" alt="" style="width:100%;height:100%;object-fit:contain;">`;
        document.querySelectorAll('.icon-option').forEach(opt => opt.classList.remove('selected'));
    }

    function quitarImagen() {
        modalIconoImg.value = '';
        iconoUploadImg.src = '';
        iconoUploadPreview.hidden = true;
        btnQuitarIcono.hidden = true;
        iconoFile.value = '';
    }

    document.querySelectorAll('.icon-option').forEach(opt => {
        opt.addEventListener('click', () => seleccionarIcono(opt.dataset.icono));
    });

    btnSubirIcono.addEventListener('click', () => iconoFile.click());
    btnQuitarIcono.addEventListener('click', () => {
        quitarImagen();
        // Al quitar la imagen vuelve a mandar el glifo que estuviera elegido.
        seleccionarIcono(modalIcono.value || ICONO_DEFAULT);
    });

    iconoFile.addEventListener('change', async () => {
        const file = iconoFile.files[0];
        if (!file) return;
        const fd = new FormData();
        fd.append('imagen', file);
        btnSubirIcono.disabled = true;
        const original = btnSubirIcono.innerHTML;
        btnSubirIcono.innerHTML = '<i class="bi bi-hourglass-split"></i> Subiendo...';
        try {
            const res = await fetch('./../controllers/categoria_icono_subir.php', { method: 'POST', body: fd });
            const d = await res.json();
            if (d.ok) {
                usarImagen(d.imagen, `./../${d.imagen}`);
                showToast('Imagen lista como icono', 'success');
            } else {
                showToast(d.error || 'No se pudo subir la imagen', 'error');
            }
        } catch (err) {
            console.error(err);
            showToast('Error al subir la imagen', 'error');
        } finally {
            btnSubirIcono.disabled = false;
            btnSubirIcono.innerHTML = original;
            iconoFile.value = '';
        }
    });

    if(btnCrear && ACL.crear){
        // Abrir modal de crear
        document.getElementById('btnCrear').addEventListener('click', () => {
            modalTitle.innerHTML = '<i class="bi bi-folder-plus"></i> Crear Categoría';
            modalSubmit.textContent = "Crear";
            modalForm.dataset.action = "crear";
            modalId.value = "";
            modalNombre.value = "";
            modalNombre.required = true;
            modalConfirmText.style.display = "none";
            modalNombreWrapper.style.display = "block"; // reset
            modalIconoWrapper.style.display = "block";
            quitarImagen();
            seleccionarIcono(ICONO_DEFAULT);
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
                modalNombre.required = true;
                modalConfirmText.style.display = "none";
                modalNombreWrapper.style.display = "block"; // reset
                modalIconoWrapper.style.display = "block";
                // El glifo queda cargado como respaldo; si además había imagen,
                // esta gana y se muestra en la preview.
                seleccionarIcono(btn.dataset.icono || ICONO_DEFAULT);
                if (btn.dataset.iconoImgRaw) {
                    usarImagen(btn.dataset.iconoImgRaw, btn.dataset.iconoImg);
                }
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
                modalIconoWrapper.style.display = "none";
                modal.style.display = "flex";
            });
        });
    }
    // Cerrar modal
    modalClose.addEventListener('click', () => {
        modal.style.display = "none";
        modalNombreWrapper.style.display = "block"; // reset
        modalIconoWrapper.style.display = "block";
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
            modalIconoWrapper.style.display = "block";
            modalConfirmText.style.display = "none";
        }
    });

    // Mostrar / ocultar la categoría en el menú al hacer clic en el badge.
    // Solo cambia visible_menu: la categoría y sus noticias no se tocan.
    document.querySelectorAll('.btn-toggle-menu').forEach(btn => {
        btn.addEventListener('click', async function(e) {
            e.preventDefault();
            const id = this.dataset.id;
            const badge = this.querySelector('.badge-menu');
            try {
                const res = await fetch('./../controllers/categoria_toggle_menu.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `id=${encodeURIComponent(id)}`
                });
                const d = await res.json();
                if (!d.ok) {
                    showToast(d.error || 'No se pudo cambiar la visibilidad', 'error');
                    return;
                }
                const visible = d.visible_menu === 1;
                badge.classList.toggle('is-visible', visible);
                badge.classList.toggle('is-oculta', !visible);
                badge.innerHTML = `<i class="bi ${visible ? 'bi-eye-fill' : 'bi-eye-slash-fill'}"></i> ${visible ? 'Visible' : 'Oculta'}`;
                showToast(
                    visible
                        ? `«${d.nombre}» ya aparece en el menú`
                        : `«${d.nombre}» se ocultó del menú`,
                    visible ? 'success' : 'info'
                );
                actualizarContadorOcultas();
            } catch (err) {
                console.error(err);
                showToast('Error en la petición', 'error');
            }
        });
    });

    // Mantener al día la tarjeta "Ocultas del Menú" sin recargar la página
    function actualizarContadorOcultas() {
        const card = document.getElementById('statOcultas');
        if (card) card.textContent = document.querySelectorAll('.badge-menu.is-oculta').length;
    }

    // Interruptores globales: iconos en móvil / escritorio. Optimista: se revierte
    // el checkbox si el servidor falla.
    document.querySelectorAll('.chk-iconos-cfg').forEach(chk => {
        chk.addEventListener('change', async function() {
            const clave = this.dataset.clave;
            const deseado = this.checked;
            try {
                const res = await fetch('./../controllers/categorias_iconos_config.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `clave=${encodeURIComponent(clave)}`
                });
                const d = await res.json();
                if (!d.ok) {
                    this.checked = !deseado;
                    showToast(d.error || 'No se pudo guardar el ajuste', 'error');
                    return;
                }
                this.checked = d.estado === 1;
                const donde = clave === 'iconos_menu_movil' ? 'móvil' : 'escritorio';
                showToast(
                    d.estado ? `Iconos activados en ${donde}` : `Iconos desactivados en ${donde}`,
                    d.estado ? 'success' : 'info'
                );
            } catch (err) {
                console.error(err);
                this.checked = !deseado;
                showToast('Error en la petición', 'error');
            }
        });
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
