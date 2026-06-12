<?php
    include("./../layout/headerAdmin.php");
    include("./../data/conexion.php");
    require_once("./../views/helpers/urlhelper.php");

    // Logos de marcas
    $logos = $con->query("SELECT * FROM logos_marcas ORDER BY creado DESC")->fetch_all(MYSQLI_ASSOC);

    $sql = "SELECT * FROM paginas";
    $result = $con->query($sql);
    
    $paginas = [];
    while($row = $result->fetch_assoc()){
        $paginas[] = $row;
    }
    $totalPaginas = count($paginas);
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4" style="flex-wrap: wrap; gap: 12px;">
        <h1 style="margin:0;">Gestión de Páginas Legales</h1>
    </div>

    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'actualizado'): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert" style="background:rgba(16,185,129,0.1); color:#10b981; border:1px solid rgba(16,185,129,0.2); border-radius:8px;">
            <i class="bi bi-check-circle-fill"></i> Página actualizada correctamente.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Estadísticas rápidas -->
    <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));">
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(99,102,241,0.1); color: #6366f1;"><i class="bi bi-file-earmark-text"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?= $totalPaginas ?></span>
                <span class="stat-label">Páginas de Sistema</span>
            </div>
        </div>
    </div>

    <!-- Toolbar -->
    <div class="contenidos-toolbar">
        <div class="contenidos-tabs">
            <span class="tab-btn active">Todas las páginas</span>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="contenidos-table">
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
                <!-- TOOLBAR QUILL -->
                <div class="editor-toolbar ql-toolbar ql-snow" style="border-radius: 8px 8px 0 0; background: var(--card-bg);">
                    <!-- Fuente -->
                    <select class="ql-font" title="Fuente">
                        <option value="arial" selected>Arial</option>
                        <option value="times">Times New Roman</option>
                        <option value="roboto">Roboto</option>
                        <option value="courier">Courier</option>
                    </select>
                    <!-- Tamaño -->
                    <select class="ql-size" title="Tamaño">
                        <option value="small">Pequeño</option>
                        <option selected>Normal</option>
                        <option value="large">Grande</option>
                        <option value="huge">Muy grande</option>
                    </select>
                    <!-- Estilos -->
                    <button class="ql-bold" title="Negritas"></button>
                    <button class="ql-italic" title="Cursiva"></button>
                    <button class="ql-underline" title="Subrayado"></button>
                    <button class="ql-strike" title="Tachado"></button>

                    <!-- Color -->
                    <select class="ql-color" title="Color"></select>
                    <select class="ql-background" title="Fondo"></select>

                    <!-- Alineación -->
                    <select class="ql-align" title="Alineación"></select>
                    <!-- Listas -->
                    <button class="ql-list" value="ordered" title="Lista ordenada"></button>
                    <button class="ql-list" value="bullet" title="Lista desordenada"></button>

                    <!-- Sangría -->
                    <button class="ql-indent" value="-1" title="Reducir sangría"></button>
                    <button class="ql-indent" value="+1" title="Aumentar sangría"></button>

                    <!-- Limpiar formato -->
                    <button class="ql-clean" title="Limpiar formato"></button>
                </div>
                <div id="editorpag" class="editor-content" style="border-radius: 0 0 8px 8px; border: 1px solid var(--border); border-top: none;"></div>
                <!-- AQUI SE GUARDA QUILL -->
                <input type="hidden" name="contenido" id="contenido">
            </div>

            <div class="crop-actions">
                <button type="button" class="btn btn-secondary" id="modalClosePag">Cancelar</button>
                <button type="submit" class="btn btn-accent"><i class="bi bi-save"></i> Guardar Cambios</button>
            </div>
        </form>
    </div>
</div>

<!-- ══════════════════════════════════════════
     SECCIÓN: LOGOS DE MARCAS COLABORADORAS
══════════════════════════════════════════ -->
<div style="margin-top:32px;">
  <div class="d-flex justify-content-between align-items-center mb-3" style="flex-wrap:wrap;gap:12px;">
    <div>
      <h2 style="margin:0;font-size:1.3rem;">Logos de Marcas Colaboradoras</h2>
      <p style="color:var(--muted);margin-top:4px;font-size:13px;">Se muestran en la página "Sobre nosotros" debajo del contenido.</p>
    </div>
  </div>

  <!-- Subir logo -->
  <div class="card shadow-sm mb-4" style="max-width:520px;">
    <div class="card-body">
      <h5 class="card-title" style="margin-top:0;">Subir Nuevo Logo</h5>
      <label class="file-drop-zone" id="logoDropZone">
        <input type="file" id="logoFile" accept="image/*" hidden>
        <div class="file-drop-icon"><i class="bi bi-cloud-arrow-up"></i></div>
        <div class="file-drop-text">Arrastra una imagen o <span>haz clic aquí</span></div>
        <div class="file-drop-hint">PNG, JPG, WEBP o SVG — fondo transparente recomendado</div>
        <img id="logoPreview" class="file-drop-preview" style="display:none;max-height:80px;object-fit:contain;">
      </label>
      <div class="cn-field" style="margin-top:12px;">
        <label for="logoNombre" style="font-size:13px;font-weight:600;">Nombre de la marca <span style="color:var(--muted);font-weight:400;">(opcional)</span></label>
        <input type="text" id="logoNombre" class="cn-input" placeholder="Ej: Google, Netflix...">
      </div>
      <div style="display:flex;justify-content:flex-end;">
        <button type="button" id="btnSubirLogo" class="btn btn-accent"><i class="bi bi-upload"></i> Subir Logo</button>
      </div>
      <p id="logoMsg" style="margin-top:10px;font-size:0.85rem;font-weight:600;text-align:right;"></p>
    </div>
  </div>

  <!-- Galería de logos -->
  <div class="card shadow-sm">
    <div class="card-body p-4">
      <div class="logos-grid" id="logosGrid">
        <?php if (empty($logos)): ?>
          <p style="color:var(--muted);text-align:center;grid-column:1/-1;padding:20px;" id="logosEmpty">No hay logos. Sube el primero.</p>
        <?php endif; ?>
        <?php foreach ($logos as $logo): ?>
          <div class="logo-card" id="logo-<?= $logo['id_logo'] ?>">
            <div class="logo-img-wrap">
              <img src="<?= imageUrl($logo['imagen']) ?>" alt="<?= htmlspecialchars($logo['nombre']) ?>" loading="lazy">
            </div>
            <?php if ($logo['nombre']): ?>
              <div class="logo-nombre"><?= htmlspecialchars($logo['nombre']) ?></div>
            <?php endif; ?>
            <div class="logo-actions">
              <button class="btn-delete-logo" data-id="<?= $logo['id_logo'] ?>" title="Eliminar">
                <i class="bi bi-trash-fill"></i>
              </button>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>

<style>
.logos-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
  gap: 16px;
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
}
.logo-card:hover .logo-actions { display: flex; }
.btn-delete-logo {
  background: rgba(239,51,99,.85);
  border: none;
  color: #fff;
  width: 28px;
  height: 28px;
  border-radius: 50%;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: .95rem;
  cursor: pointer;
  backdrop-filter: blur(4px);
  transition: background .2s;
}
.btn-delete-logo:hover { background: #d42a55; }
</style>

<script>
const BASE_PATH = '<?= basePath() ?>';
document.addEventListener('DOMContentLoaded', () => {
    // ── Logo upload ──────────────────────────────
    const logoFile     = document.getElementById('logoFile');
    const logoDropZone = document.getElementById('logoDropZone');
    const logoPreview  = document.getElementById('logoPreview');
    const logoNombre   = document.getElementById('logoNombre');
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
                    const card = buildLogoCard(data.id, data.imagen, data.nombre);
                    logosGrid.insertAdjacentHTML('afterbegin', card);
                    attachDeleteBtn(logosGrid.firstElementChild.querySelector('.btn-delete-logo'));
                    // Reset form
                    logoFile.value = '';
                    logoNombre.value = '';
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

    function buildLogoCard(id, imagen, nombre) {
        const imgSrc = BASE_PATH + '/serve-image.php?file=' + encodeURIComponent(imagen);
        const nombreHtml = nombre ? `<div class="logo-nombre">${escapeHtml(nombre)}</div>` : '';
        return `<div class="logo-card" id="logo-${id}">
            <div class="logo-img-wrap"><img src="${imgSrc}" alt="${escapeHtml(nombre)}" loading="lazy"></div>
            ${nombreHtml}
            <div class="logo-actions">
                <button class="btn-delete-logo" data-id="${id}" title="Eliminar"><i class="bi bi-trash-fill"></i></button>
            </div>
        </div>`;
    }

    function escapeHtml(str) {
        return String(str || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
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
                    }
                }
            });
        });
    }

    // Attach delete to existing logos
    document.querySelectorAll('.btn-delete-logo').forEach(attachDeleteBtn);

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

    // ── Quill (page editor) ──────────────────────────────────────────────
    var quillpag = new Quill('#editorpag', {
        theme: 'snow',
        placeholder: 'Escribe el contenido aquí...',
        modules: {
            toolbar: {
                container: '.editor-toolbar'
            }
        }
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
            modalPagina.style.display = "flex"; // Usa flex en el nuevo crop-modal
            
            setTimeout(() => {
                quillpag.setContents([]);
                quillpag.clipboard.dangerouslyPasteHTML(contenido);
            }, 100);
        });
    });

    document.getElementById("formPagina").addEventListener("submit", function(){
        document.getElementById("contenido").value = quillpag.root.innerHTML;
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