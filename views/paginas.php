<?php
    include("./../layout/headerAdmin.php");
    include("./../data/conexion.php");
    
    if (empty($ACL['leer'])) {
        header("Location: admin.php");
        exit();
    }
    require_once("./../views/helpers/urlhelper.php");

    // Logos de marcas
    $logos = $con->query("SELECT * FROM logos_marcas ORDER BY orden ASC, creado ASC")->fetch_all(MYSQLI_ASSOC);

    $sql = "SELECT * FROM paginas";
    $result = $con->query($sql);

    $paginas = [];
    while($row = $result->fetch_assoc()){
        $paginas[] = $row;
    }
    $totalPaginas = count($paginas);
?>
<div class="container-fluid">

    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'actualizado'): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert" style="background:rgba(16,185,129,0.1); color:#10b981; border:1px solid rgba(16,185,129,0.2); border-radius:8px; margin-bottom:16px;">
            <i class="bi bi-check-circle-fill"></i> Página actualizada correctamente.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- ── Cuadrícula principal: tabla izq / subir logo der ── -->
    <div class="paginas-layout-grid">

        <!-- ── Columna izquierda: Páginas legales ──────────── -->
        <div style="display:flex;flex-direction:column;">
            <div class="mb-2">
                <h2 style="margin:0 0 2px;font-size:2rem;font-weight:800;">Gestión de Páginas Legales</h2>
            </div>
            <div class="contenidos-toolbar" style="margin-bottom:10px;">
                <div class="contenidos-tabs">
                    <span>Todas las páginas</span>
                </div>
            </div>
            <div class="card shadow-sm" style="flex:1;">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="contenidos-table paginas-table-compact">
                            <thead>
                                <tr>
                                    <th>Nombre de la Sección</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($paginas as $row): ?>
                                    <tr>
                                        <td>
                                            <strong class="table-title" style="text-transform: capitalize;">
                                                <i class="bi bi-file-text text-muted" style="margin-right:5px;"></i> <?= htmlspecialchars($row['nombre_pag']) ?>
                                            </strong>
                                        </td>
                                        <td>
                                            <div class="noticias-actions" style="border-top:none; padding:0; justify-content:flex-start;">
                                                <button
                                                    class="btn btn-edit btnEditar"
                                                    data-id="<?= $row['id_pag'] ?>"
                                                    data-nombre="<?= htmlspecialchars($row['nombre_pag']) ?>"
                                                    data-contenido="<?= base64_encode($row['contenido_pag']) ?>" title="Editar Contenido">
                                                    <i class="bi bi-pencil-square"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Columna derecha: Subir logo ─────────────────── -->
        <div>
            <div class="mb-2">
                <h2 style="margin:0 0 2px;font-size:2rem;font-weight:800;">Logos de Marcas Colaboradoras</h2>
                <p style="color:var(--muted);margin:0;font-size:13px;">Se muestran en "Sobre nosotros" debajo del contenido.</p>
            </div>
            <div class="card shadow-sm" style="margin-top:10px;">
                <div class="card-body">
                    <h5 class="card-title" style="margin-top:0;font-size:0.95rem;">Subir Nuevo Logo</h5>
                    <label class="file-drop-zone" id="logoDropZone">
                        <input type="file" id="logoFile" accept="image/*" hidden>
                        <div class="file-drop-icon"><i class="bi bi-cloud-arrow-up"></i></div>
                        <div class="file-drop-text">Arrastra una imagen o <span>haz clic aquí</span></div>
                        <div class="file-drop-hint">PNG, JPG, — Mejor si es fondo transparente</div>
                        <img id="logoPreview" class="file-drop-preview" style="display:none;max-height:80px;object-fit:contain;">
                    </label>
                    <div class="cn-field" style="margin-top:12px;">
                        <label for="logoNombre" style="font-size:13px;font-weight:600;">Nombre de la marca <span style="color:var(--muted);font-weight:400;">(opcional)</span></label>
                        <input type="text" id="logoNombre" class="cn-input">
                    </div>
                    <div class="cn-field" style="margin-top:12px;">
                        <label style="font-size:13px;font-weight:600;display:block;margin-bottom:6px;">Visible hasta <span style="color:var(--muted);font-weight:400;">(opcional — se retira automáticamente al vencer)</span></label>
                        <div style="display:flex;align-items:center;gap:8px;">
                            <div class="logo-exp-fields" style="flex:1;min-width:0;">
                                <div class="cn-date-input">
                                    <i class="bi bi-calendar3"></i>
                                    <input type="date" id="logoExpFecha" min="<?= date('Y-m-d', strtotime('+1 day')) ?>">
                                </div>
                                <div class="cn-date-input">
                                    <i class="bi bi-clock"></i>
                                    <input type="time" id="logoExpHora" value="23:59">
                                </div>
                            </div>
                            <button type="button" id="btnSubirLogo" class="btn btn-accent" style="flex-shrink:0;white-space:nowrap;align-self:center;"><i class="bi bi-upload"></i> Subir Logo</button>
                        </div>
                    </div>
                    <p id="logoMsg" style="margin-top:8px;font-size:0.85rem;font-weight:600;text-align:right;min-height:1.2em;"></p>
                </div>
            </div>
        </div>

    </div><!-- /.paginas-layout-grid -->

    <!-- ── Galería de logos: ancho completo ──────────────────── -->
    <div class="card shadow-sm">
        <div class="card-body p-3">
            <div class="logos-grid" id="logosGrid">
                <?php if (empty($logos)): ?>
                    <p style="color:var(--muted);text-align:center;grid-column:1/-1;padding:20px;" id="logosEmpty">No hay logos. Sube el primero.</p>
                <?php endif; ?>
                <?php foreach ($logos as $i => $logo):
                    $exp       = $logo['fecha_expiracion'] ?? null;
                    $vencido   = $exp && strtotime($exp) < time();
                    $expBadge  = '';
                    if ($exp) {
                        if ($vencido) {
                            $expBadge = '<div class="logo-exp-badge logo-exp-vencido"><i class="bi bi-clock-history"></i> Vencido</div>';
                        } else {
                            $diff     = strtotime($exp) - time();
                            $dias     = (int) floor($diff / 86400);
                            $horas    = (int) floor(($diff % 86400) / 3600);
                            $label    = $dias > 0 ? "{$dias}d {$horas}h restantes" : "{$horas}h restantes";
                            $expBadge = '<div class="logo-exp-badge logo-exp-activo"><i class="bi bi-calendar-check"></i> ' . $label . '</div>';
                        }
                    }
                ?>
                    <div class="logo-card <?= $vencido ? 'logo-card-vencida' : '' ?>" id="logo-<?= $logo['id_logo'] ?>" draggable="true" data-id="<?= $logo['id_logo'] ?>">
                        <div class="logo-num"><?= $i + 1 ?></div>
                        <div class="logo-img-wrap">
                            <img src="<?= imageUrl($logo['imagen']) ?>" alt="<?= htmlspecialchars($logo['nombre']) ?>" loading="lazy">
                        </div>
                        <?php if ($logo['nombre']): ?>
                            <div class="logo-nombre"><?= htmlspecialchars($logo['nombre']) ?></div>
                        <?php endif; ?>
                        <?= $expBadge ?>
                        <div class="logo-actions">
                            <button class="btn-edit-logo"
                                    data-id="<?= $logo['id_logo'] ?>"
                                    data-nombre="<?= htmlspecialchars($logo['nombre'] ?? '') ?>"
                                    data-exp="<?= htmlspecialchars($logo['fecha_expiracion'] ?? '') ?>"
                                    data-imagen="<?= htmlspecialchars($logo['imagen']) ?>"
                                    title="Editar">
                                <i class="bi bi-pencil-fill"></i>
                            </button>
                            <button class="btn-delete-logo" data-id="<?= $logo['id_logo'] ?>" title="Eliminar">
                                <i class="bi bi-trash-fill"></i>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

</div><!-- /.container-fluid -->

<!-- ══ Modal editar página ════════════════════════════════════════ -->
<div id="modalPagina" class="crop-modal" style="display: none;">
    <div class="crop-modal-content" style="max-width: 800px; width:95%;">
        <h3><i class="bi bi-pencil-square"></i> Editar Página</h3>

        <form id="formPagina" action="./../controllers/pagina.php" method="POST">
            <input type="hidden" name="id" id="pagina_id">

            <div style="margin-bottom: 16px;">
                <label for="nombre" style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text);">Sección</label>
                <select name="nombre" id="nombre" style="width: 100%; padding: 10px 14px; border: 1px solid var(--border); border-radius: 8px; font-size: 0.9rem; background: var(--bg); color: var(--text);">
                    <option value="nosotros">Nosotros</option>
                    <option value="terminos">Términos y condiciones</option>
                    <option value="privacidad">Aviso de privacidad</option>
                    <option value="cookies">Política de cookies</option>
                </select>
            </div>

            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text);">Contenido</label>
                <div class="document-editor">
                    <div class="document-editor__toolbar" id="toolbarpag"></div>
                    <div class="document-editor__editable-container" style="height: 350px; overflow-y: auto;">
                        <div id="editorpag" class="editor-content"></div>
                    </div>
                </div>
                <input type="hidden" name="contenido" id="contenido">
            </div>

            <div class="crop-actions">
                <button type="button" class="btn btn-secondary" id="modalClosePag">Cancelar</button>
                <button type="submit" class="btn btn-accent"><i class="bi bi-save"></i> Guardar Cambios</button>
            </div>
        </form>
    </div>
</div>

<!-- ══ Modal editar logo ══════════════════════════════════════════ -->
<div id="modalEditLogo" class="crop-modal" style="display:none;">
  <div class="crop-modal-content" style="max-width:440px;width:95%;">
    <h3 style="margin-top:0;"><i class="bi bi-pencil-square"></i> Editar Logo</h3>

    <div class="cn-field" style="margin-bottom:16px;">
      <label style="font-size:13px;font-weight:600;display:block;margin-bottom:6px;">
        Imagen <span style="color:var(--muted);font-weight:400;">(dejar sin cambios para conservar la actual)</span>
      </label>
      <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
        <img id="editLogoImgActual" style="max-height:56px;max-width:90px;object-fit:contain;border:1px solid var(--border);border-radius:8px;padding:4px;background:var(--bg);">
        <label style="cursor:pointer;display:inline-flex;align-items:center;gap:6px;padding:7px 14px;border:1px solid var(--border);border-radius:8px;font-size:13px;font-weight:600;color:var(--text);background:var(--bg);">
          <i class="bi bi-image"></i> Cambiar imagen
          <input type="file" id="editLogoFile" accept="image/*" hidden>
        </label>
        <img id="editLogoNewPreview" style="display:none;max-height:56px;max-width:90px;object-fit:contain;border:2px solid var(--accent);border-radius:8px;padding:4px;background:var(--bg);">
      </div>
    </div>

    <div class="cn-field" style="margin-bottom:16px;">
      <label style="font-size:13px;font-weight:600;display:block;margin-bottom:6px;">
        Nombre de la marca <span style="color:var(--muted);font-weight:400;">(opcional)</span>
      </label>
      <input type="text" id="editLogoNombre" class="cn-input" placeholder="Ej: Disney+">
    </div>

    <div class="cn-field" style="margin-bottom:24px;">
      <label style="font-size:13px;font-weight:600;display:block;margin-bottom:6px;">
        Visible hasta <span style="color:var(--muted);font-weight:400;">(dejar en blanco = sin vencimiento)</span>
      </label>
      <div class="logo-exp-fields">
        <div class="cn-date-input">
          <i class="bi bi-calendar3"></i>
          <!-- min = hoy: evita elegir una fecha ya pasada, que dejaría el logo vencido -->
          <input type="date" id="editLogoFecha" min="<?= date('Y-m-d') ?>">
        </div>
        <div class="cn-date-input">
          <i class="bi bi-clock"></i>
          <input type="time" id="editLogoHora">
        </div>
      </div>
    </div>

    <div class="crop-actions">
      <button type="button" class="btn btn-secondary" id="closeEditLogo">Cancelar</button>
      <button type="button" class="btn btn-accent" id="btnGuardarEditLogo"><i class="bi bi-save"></i> Guardar</button>
    </div>
    <p id="editLogoMsg" style="margin-top:10px;font-size:0.85rem;font-weight:600;text-align:right;min-height:1.2em;"></p>
  </div>
</div>

<style>
/* ── Layout de dos columnas ──────────────────────────────────── */
.paginas-layout-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
    align-items: stretch;
    margin-bottom: 20px;
}
@media (max-width: 900px) {
    .paginas-layout-grid { grid-template-columns: 1fr; }
}

/* Compactar filas de la tabla de páginas */
.paginas-table-compact td,
.paginas-table-compact th {
    padding: 10px 14px;
}

/* ── Galería de logos ────────────────────────────────────────── */
.logos-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
    gap: 14px;
}
.logo-card {
    position: relative;
    border-radius: 12px;
    border: 2px solid var(--border);
    background: var(--bg);
    overflow: hidden;
    transition: transform .2s, box-shadow .2s;
}
.logo-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,.1); }
.logo-img-wrap {
    padding: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 90px;
}
.logo-img-wrap img {
    max-width: 100%;
    max-height: 70px;
    object-fit: contain;
    display: block;
}
.logo-nombre {
    text-align: center;
    font-size: 11px;
    color: var(--muted);
    padding: 0 8px 8px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.logo-actions {
    position: absolute;
    top: 6px;
    right: 6px;
    display: none;
    gap: 4px;
}
.logo-card:hover .logo-actions { display: flex; }
.btn-delete-logo,
.btn-edit-logo {
    border: none;
    color: #fff;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: .85rem;
    cursor: pointer;
    backdrop-filter: blur(4px);
    transition: background .2s;
}
.btn-delete-logo { background: rgba(239,51,99,.85); }
.btn-delete-logo:hover { background: #d42a55; }
.btn-edit-logo { background: rgba(99,102,241,.85); }
.btn-edit-logo:hover { background: #4f46e5; }

/* Inputs fecha + hora de expiración */
.logo-exp-fields { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.cn-date-input {
    display: flex; align-items: center; gap: 8px;
    padding: 9px 12px; border: 1px solid var(--border);
    border-radius: 8px; background: var(--bg); color: var(--text); font-size: 13px;
}
.cn-date-input i { color: var(--muted); font-size: 15px; flex-shrink: 0; }
.cn-date-input input[type="date"],
.cn-date-input input[type="time"] {
    border: none; background: none; color: var(--text);
    font-size: 13px; padding: 0; outline: none; width: 100%; font-family: inherit;
}

/* Badge de expiración */
.logo-exp-badge {
    font-size: 10px;
    font-weight: 700;
    text-align: center;
    padding: 3px 0;
    letter-spacing: .02em;
}
.logo-exp-activo {
    color: #10b981;
}
.logo-exp-vencido {
    color: #fff;
    background: rgba(239,51,99,.85);
    padding: 4px 0;
}
.logo-card-vencida {
    opacity: .55;
    border-color: rgba(239,51,99,.4);
}

/* Badge numérico de orden */
.logo-num {
    position: absolute;
    top: 6px;
    left: 6px;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: var(--accent);
    color: #fff;
    font-size: 11px;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 2;
    pointer-events: none;
}

/* Drag & drop */
.logo-card { cursor: grab; }
.logo-card:active { cursor: grabbing; }
.logo-card.dragging { opacity: .35; box-shadow: none; }
.logo-card.drag-over { border: 2px dashed var(--accent); background: rgba(239,51,99,.08); }
</style>

<script>
const BASE_PATH = '<?= basePath() ?>';
document.addEventListener('DOMContentLoaded', () => {
    // ── Logo upload ──────────────────────────────
    const logoFile     = document.getElementById('logoFile');
    const logoDropZone = document.getElementById('logoDropZone');
    const logoPreview  = document.getElementById('logoPreview');
    const logoNombre   = document.getElementById('logoNombre');
    const logoExpFecha = document.getElementById('logoExpFecha');
    const logoExpHora  = document.getElementById('logoExpHora');
    const btnSubirLogo = document.getElementById('btnSubirLogo');
    const logoMsg      = document.getElementById('logoMsg');
    const logosGrid    = document.getElementById('logosGrid');

    logoFile.addEventListener('change', () => {
        const f = logoFile.files[0];
        if (!f) return;
        logoPreview.src = URL.createObjectURL(f);
        logoPreview.style.display = 'block';
        logoDropZone.querySelector('.file-drop-icon').style.display = 'none';
        logoDropZone.querySelector('.file-drop-text').style.display = 'none';
        logoDropZone.querySelector('.file-drop-hint').style.display = 'none';
    });

    // Drag & drop
    logoDropZone.addEventListener('dragover', e => { e.preventDefault(); logoDropZone.classList.add('dragover'); });
    logoDropZone.addEventListener('dragleave', () => logoDropZone.classList.remove('dragover'));
    logoDropZone.addEventListener('drop', e => {
        e.preventDefault();
        logoDropZone.classList.remove('dragover');
        if (e.dataTransfer.files[0]) {
            const dt = new DataTransfer();
            dt.items.add(e.dataTransfer.files[0]);
            logoFile.files = dt.files;
            logoFile.dispatchEvent(new Event('change'));
        }
    });

    btnSubirLogo.addEventListener('click', () => {
        if (!logoFile.files[0]) { logoMsg.style.color = '#e74c3c'; logoMsg.textContent = 'Selecciona una imagen primero.'; return; }
        const fd = new FormData();
        fd.append('imagen', logoFile.files[0]);
        fd.append('nombre', logoNombre.value.trim());
        const expFecha = logoExpFecha.value.trim();
        const expHora  = logoExpHora.value.trim() || '23:59';
        fd.append('fecha_expiracion', expFecha ? `${expFecha} ${expHora}:00` : '');
        btnSubirLogo.disabled = true;
        logoMsg.style.color = 'var(--muted)';
        logoMsg.textContent = 'Subiendo…';
        fetch(BASE_PATH + '/controllers/logo_subir.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                if (data.ok) {
                    logoMsg.style.color = '#10b981';
                    logoMsg.textContent = 'Logo subido correctamente.';
                    // Remove empty message
                    const empty = document.getElementById('logosEmpty');
                    if (empty) empty.remove();
                    // Prepend new card
                    const card = buildLogoCard(data.id, data.imagen, data.nombre, data.fecha_expiracion, data.imagen);
                    logosGrid.insertAdjacentHTML('afterbegin', card);
                    const newCard = logosGrid.firstElementChild;
                    attachDeleteBtn(newCard.querySelector('.btn-delete-logo'));
                    attachEditBtn(newCard.querySelector('.btn-edit-logo'));
                    setupLogoDrag(newCard);
                    renumberLogos();
                    saveLogoOrder();
                    // Reset form
                    logoFile.value = '';
                    logoNombre.value = '';
                    logoExpFecha.value = '';
                    logoExpHora.value  = '23:59';
                    logoPreview.style.display = 'none';
                    logoDropZone.querySelector('.file-drop-icon').style.display = '';
                    logoDropZone.querySelector('.file-drop-text').style.display = '';
                    logoDropZone.querySelector('.file-drop-hint').style.display = '';
                } else {
                    logoMsg.style.color = '#e74c3c';
                    logoMsg.textContent = data.error || 'Error al subir.';
                }
            })
            .catch(() => { logoMsg.style.color = '#e74c3c'; logoMsg.textContent = 'Error de conexión.'; })
            .finally(() => { btnSubirLogo.disabled = false; });
    });

    function buildLogoCard(id, imagen, nombre, fechaExp, imagenPath) {
        const imgSrc     = BASE_PATH + '/serve-image.php?file=' + encodeURIComponent(imagen);
        const nombreHtml = nombre ? `<div class="logo-nombre">${escapeHtml(nombre)}</div>` : '';
        const badge    = badgeExpiracion(fechaExp);
        const expBadge = badge ? `<div class="${badge.cls}">${badge.html}</div>` : '';
        const num = logosGrid.querySelectorAll('.logo-card').length + 1;
        return `<div class="logo-card${badge && badge.vencido ? ' logo-card-vencida' : ''}" id="logo-${id}" draggable="true" data-id="${id}">
            <div class="logo-num">${num}</div>
            <div class="logo-img-wrap"><img src="${imgSrc}" alt="${escapeHtml(nombre)}" loading="lazy"></div>
            ${nombreHtml}
            ${expBadge}
            <div class="logo-actions">
                <button class="btn-edit-logo" data-id="${id}" data-nombre="${escapeHtml(nombre)}" data-exp="${escapeHtml(fechaExp||'')}" data-imagen="${escapeHtml(imagen)}" title="Editar"><i class="bi bi-pencil-fill"></i></button>
                <button class="btn-delete-logo" data-id="${id}" title="Eliminar"><i class="bi bi-trash-fill"></i></button>
            </div>
        </div>`;
    }

    function escapeHtml(str) {
        return String(str || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    // MySQL devuelve "YYYY-MM-DD HH:MM:SS"; ese formato con espacio no es
    // estándar en JS (algunos navegadores dan Invalid Date). Se normaliza a
    // ISO local para que el cálculo de "restantes/vencido" nunca dé NaN.
    function parseFechaLocal(str) {
        if (!str) return null;
        const d = new Date(String(str).trim().replace(' ', 'T'));
        return isNaN(d.getTime()) ? null : d;
    }

    // HTML del badge de expiración a partir de la fecha guardada.
    // Devuelve { html, cls, vencido } o null si el logo no expira.
    function badgeExpiracion(fechaExp) {
        const d = parseFechaLocal(fechaExp);
        if (!d) return null;
        const ms = d - new Date();
        if (ms <= 0) {
            return { html: '<i class="bi bi-clock-history"></i> Vencido',
                     cls: 'logo-exp-badge logo-exp-vencido', vencido: true };
        }
        const dias  = Math.floor(ms / 86400000);
        const horas = Math.floor((ms % 86400000) / 3600000);
        const label = dias > 0 ? `${dias}d ${horas}h restantes` : `${horas}h restantes`;
        return { html: `<i class="bi bi-calendar-check"></i> ${label}`,
                 cls: 'logo-exp-badge logo-exp-activo', vencido: false };
    }

    function attachDeleteBtn(btn) {
        btn.addEventListener('click', function() {
            if (!confirm('¿Eliminar este logo?')) return;
            const id = this.dataset.id;
            fetch(BASE_PATH + '/controllers/logo_eliminar.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'id=' + id
            })
            .then(r => r.json())
            .then(data => {
                if (data.ok) {
                    const card = document.getElementById('logo-' + id);
                    if (card) card.remove();
                    if (!logosGrid.querySelector('.logo-card')) {
                        logosGrid.innerHTML = '<p style="color:var(--muted);text-align:center;grid-column:1/-1;padding:20px;" id="logosEmpty">No hay logos. Sube el primero.</p>';
                    } else {
                        renumberLogos();
                        saveLogoOrder();
                    }
                }
            });
        });
    }

    // ── Edit logo ────────────────────────────────────────────────
    const modalEditLogo      = document.getElementById('modalEditLogo');
    const editLogoNombre     = document.getElementById('editLogoNombre');
    const editLogoFecha      = document.getElementById('editLogoFecha');
    const editLogoHora       = document.getElementById('editLogoHora');
    const editLogoMsg        = document.getElementById('editLogoMsg');
    const btnGuardar         = document.getElementById('btnGuardarEditLogo');
    const editLogoFile       = document.getElementById('editLogoFile');
    const editLogoImgActual  = document.getElementById('editLogoImgActual');
    const editLogoNewPreview = document.getElementById('editLogoNewPreview');
    let   editingLogoId      = null;

    editLogoFile.addEventListener('change', () => {
        const f = editLogoFile.files[0];
        if (!f) { editLogoNewPreview.style.display = 'none'; return; }
        editLogoNewPreview.src = URL.createObjectURL(f);
        editLogoNewPreview.style.display = 'block';
    });

    function openEditModal(id, nombre, fechaExp, imagen) {
        editingLogoId        = id;
        editLogoNombre.value = nombre || '';
        editLogoMsg.textContent = '';
        editLogoFile.value = '';
        editLogoNewPreview.style.display = 'none';
        editLogoImgActual.src = imagen
            ? BASE_PATH + '/serve-image.php?file=' + encodeURIComponent(imagen)
            : '';
        const d = parseFechaLocal(fechaExp);
        if (d && d > new Date()) {
            // Vigente: se precarga para poder ajustarla.
            const parts       = fechaExp.split(' ');
            editLogoFecha.value = parts[0] || '';
            editLogoHora.value  = (parts[1] || '23:59:00').slice(0, 5);
        } else {
            // Sin fecha, o ya vencida: no se precarga la fecha pasada, porque el
            // calendario abriría en el mes vencido e invitaría a elegir otro día
            // igual de pasado (el logo seguiría sin publicarse).
            editLogoFecha.value = '';
            editLogoHora.value  = '23:59';
            if (d) {
                editLogoMsg.style.color = 'var(--muted)';
                editLogoMsg.textContent = 'Venció el ' + d.toLocaleDateString('es-MX',
                    { day: '2-digit', month: 'short', year: 'numeric' }) + '. Elige una fecha futura.';
            }
        }
        modalEditLogo.style.display = 'flex';
    }

    function attachEditBtn(btn) {
        btn.addEventListener('click', function() {
            openEditModal(this.dataset.id, this.dataset.nombre, this.dataset.exp, this.dataset.imagen);
        });
    }

    document.getElementById('closeEditLogo').addEventListener('click', () => {
        modalEditLogo.style.display = 'none';
    });
    modalEditLogo.addEventListener('click', e => {
        if (e.target === modalEditLogo) modalEditLogo.style.display = 'none';
    });

    btnGuardar.addEventListener('click', () => {
        if (!editingLogoId) return;
        const nombre   = editLogoNombre.value.trim();
        const fecha    = editLogoFecha.value.trim();
        const hora     = editLogoHora.value.trim() || '23:59';
        const fechaExp = fecha ? `${fecha} ${hora}:00` : '';

        btnGuardar.disabled = true;
        editLogoMsg.style.color = 'var(--muted)';
        editLogoMsg.textContent = 'Guardando…';

        const fd = new FormData();
        fd.append('id',               editingLogoId);
        fd.append('nombre',           nombre);
        fd.append('fecha_expiracion', fechaExp);
        if (editLogoFile.files[0]) fd.append('imagen', editLogoFile.files[0]);

        fetch(BASE_PATH + '/controllers/logo_editar.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                if (data.ok) {
                    // Guardar una fecha ya pasada es válido, pero deja el logo
                    // fuera del sitio: hay que decirlo en vez de un "Guardado" seco.
                    const guardado = badgeExpiracion(data.fecha_expiracion);
                    const sigueVencido = guardado && guardado.vencido;
                    editLogoMsg.style.color = sigueVencido ? '#e0a800' : '#10b981';
                    editLogoMsg.textContent = sigueVencido
                        ? 'Guardado, pero esa fecha ya pasó: el logo sigue vencido y no aparece en el sitio.'
                        : 'Guardado correctamente.';

                    // Actualizar DOM de la tarjeta
                    const card     = document.getElementById('logo-' + editingLogoId);
                    if (card) {
                        // Imagen
                        if (data.imagen) {
                            const imgEl = card.querySelector('.logo-img-wrap img');
                            if (imgEl) imgEl.src = BASE_PATH + '/serve-image.php?file=' + encodeURIComponent(data.imagen);
                        }
                        // Nombre
                        let nombreEl = card.querySelector('.logo-nombre');
                        if (data.nombre) {
                            if (!nombreEl) { nombreEl = document.createElement('div'); nombreEl.className = 'logo-nombre'; card.querySelector('.logo-img-wrap').after(nombreEl); }
                            nombreEl.textContent = data.nombre;
                        } else if (nombreEl) {
                            nombreEl.remove();
                        }
                        // Badge expiración + estado vencido de la tarjeta
                        let badgeEl = card.querySelector('.logo-exp-badge');
                        const badge = badgeExpiracion(data.fecha_expiracion);
                        if (badge) {
                            if (!badgeEl) { badgeEl = document.createElement('div'); card.querySelector('.logo-actions').before(badgeEl); }
                            badgeEl.className   = badge.cls;
                            badgeEl.innerHTML   = badge.html;
                            card.classList.toggle('logo-card-vencida', badge.vencido);
                        } else {
                            if (badgeEl) badgeEl.remove();
                            card.classList.remove('logo-card-vencida');
                        }
                        // Actualizar data-attributes del botón editar
                        const editBtn = card.querySelector('.btn-edit-logo');
                        if (editBtn) {
                            editBtn.dataset.nombre = data.nombre || '';
                            editBtn.dataset.exp    = data.fecha_expiracion || '';
                            if (data.imagen) editBtn.dataset.imagen = data.imagen;
                        }
                    }
                    // Si quedó vencido se deja el modal abierto para que se vea el aviso.
                    if (!sigueVencido) setTimeout(() => { modalEditLogo.style.display = 'none'; }, 800);
                } else {
                    editLogoMsg.style.color = '#e74c3c';
                    editLogoMsg.textContent = data.error || 'Error al guardar.';
                }
            })
            .catch(() => { editLogoMsg.style.color = '#e74c3c'; editLogoMsg.textContent = 'Error de conexión.'; })
            .finally(() => { btnGuardar.disabled = false; });
    });

    // Attach delete to existing logos
    document.querySelectorAll('.btn-delete-logo').forEach(attachDeleteBtn);
    document.querySelectorAll('.btn-edit-logo').forEach(attachEditBtn);

    // ── Drag & drop para reordenar logos ────────────────────────
    let dragSrc = null;

    function renumberLogos() {
        logosGrid.querySelectorAll('.logo-card').forEach((card, i) => {
            const num = card.querySelector('.logo-num');
            if (num) num.textContent = i + 1;
        });
    }

    function saveLogoOrder() {
        const ids = [...logosGrid.querySelectorAll('.logo-card')].map(c => c.dataset.id);
        fetch(BASE_PATH + '/controllers/logo_reordenar.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(ids)
        });
    }

    function setupLogoDrag(card) {
        card.addEventListener('dragstart', e => {
            dragSrc = card;
            e.dataTransfer.effectAllowed = 'move';
            setTimeout(() => card.classList.add('dragging'), 0);
        });
        card.addEventListener('dragend', () => {
            card.classList.remove('dragging');
            dragSrc = null;
        });
        card.addEventListener('dragover', e => {
            e.preventDefault();
            if (dragSrc && dragSrc !== card) card.classList.add('drag-over');
        });
        card.addEventListener('dragleave', () => card.classList.remove('drag-over'));
        card.addEventListener('drop', e => {
            e.preventDefault();
            card.classList.remove('drag-over');
            if (!dragSrc || dragSrc === card) return;
            const all = [...logosGrid.querySelectorAll('.logo-card')];
            if (all.indexOf(dragSrc) < all.indexOf(card)) card.after(dragSrc);
            else card.before(dragSrc);
            renumberLogos();
            saveLogoOrder();
        });
    }

    document.querySelectorAll('.logo-card').forEach(setupLogoDrag);

    // ── File drop zone CSS (inline for self-containment) ─────────────────
    const dropStyle = document.createElement('style');
    dropStyle.textContent = `
    .file-drop-zone { display:flex; flex-direction:column; align-items:center; justify-content:center; gap:6px; min-height:120px; border:2px dashed var(--border); border-radius:12px; cursor:pointer; padding:20px; transition:border-color .2s,background .2s; text-align:center; }
    .file-drop-zone:hover, .file-drop-zone.dragover { border-color:var(--accent); background:rgba(239,51,99,.04); }
    .file-drop-icon { font-size:2rem; color:var(--accent); }
    .file-drop-text { font-size:.9rem; color:var(--text); }
    .file-drop-text span { color:var(--accent); font-weight:600; }
    .file-drop-hint { font-size:.75rem; color:var(--muted); }
    `;
    document.head.appendChild(dropStyle);

    // ── CKEditor 5 (page editor) ──────────────────────────────────────────
    let editorpag = null;
    DecoupledEditor
        .create(document.querySelector('#editorpag'), {
            placeholder: 'Escribe el contenido aquí...',
            language: 'es'
        })
        .then(editor => {
            editorpag = editor;
            const toolbarContainer = document.querySelector('#toolbarpag');
            if (toolbarContainer) {
                toolbarContainer.appendChild(editor.ui.view.toolbar.element);
            }
        })
        .catch(error => {
            console.error('Error inicializando CKEditor para páginas:', error);
        });

    const modalPagina = document.getElementById("modalPagina");
    const modalClosePag = document.getElementById("modalClosePag");

    document.querySelectorAll(".btnEditar").forEach(btn => {
        btn.addEventListener("click", function(){
            document.getElementById("pagina_id").value = this.dataset.id;

            // Asignar select option
            const selectNombre = document.getElementById("nombre");
            for(let i=0; i<selectNombre.options.length; i++){
                if(selectNombre.options[i].value === this.dataset.nombre) {
                    selectNombre.selectedIndex = i;
                    break;
                }
            }

            let contenido = decodeURIComponent(escape(atob(this.dataset.contenido)));
            modalPagina.style.display = "flex";

            setTimeout(() => {
                if (editorpag) {
                    editorpag.setData(contenido);
                } else {
                    document.getElementById('editorpag').innerHTML = contenido;
                }
            }, 100);
        });
    });

    document.getElementById("formPagina").addEventListener("submit", function(){
        if (editorpag) {
            document.getElementById("contenido").value = editorpag.getData();
        }
    });

    modalClosePag.addEventListener('click', () => {
        modalPagina.style.display = "none";
    });

    window.addEventListener('click', (e) => {
        if(e.target === modalPagina) {
            modalPagina.style.display = "none";
        }
    });
});
</script>

<?php include("./../layout/footerAdmin.php"); ?>
