<?php
include(__DIR__ . "/../layout/headerAdmin.php");
include(__DIR__."/../controllers/aclcontroller.php");
$ACL = $_SESSION['ACL']['noticias'] ?? [
    "crear" => false,
    "leer"  => false,
    "editar"=> false,
    "eliminar" => false
];
proteger('noticias','crear');
include(__DIR__ . "/../data/conexion.php");
if (empty($ACL['crear'])) {
    header("Location: admin.php");
    exit();
}
?>
<script>const ACL = <?= json_encode($ACL) ?>;</script>
<?php
$categoriasResult = $con->query("SELECT id_c, nombre FROM categorias ORDER BY nombre ASC");
$categorias = [];
while($row = $categoriasResult->fetch_assoc()) $categorias[] = $row;
?>

<style>
/* =============================================
   CREAR NOTICIA — NUEVO LAYOUT
   ============================================= */

/* Breadcrumb */
.cn-breadcrumb {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 13px;
  color: var(--muted);
  margin-bottom: 20px;
}
.cn-breadcrumb a { color: var(--muted); text-decoration: none; }
.cn-breadcrumb a:hover { color: var(--accent); }
.cn-breadcrumb span { color: var(--accent); }

.cn-page-title {
  font-size: 1.6rem;
  font-weight: 700;
  margin: 0 0 24px;
  color: var(--text);
}

/* Grid principal */
.cn-grid {
  display: grid;
  grid-template-columns: 1fr 320px;
  gap: 20px;
  align-items: start;
}
@media (max-width: 980px) { .cn-grid { grid-template-columns: 1fr; } }

/* Sección card */
.cn-section {
  background: var(--card-bg);
  border: 1px solid var(--border);
  border-radius: 14px;
  padding: 20px;
  margin-bottom: 20px;
}
.cn-section-header {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 16px;
  padding-bottom: 12px;
  border-bottom: 1px solid var(--border);
}
.cn-section-icon {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  background: rgba(239,51,99,0.12);
  color: var(--accent);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 16px;
  flex-shrink: 0;
}
.cn-section-title { font-size: 0.95rem; font-weight: 700; color: var(--text); margin: 0; }
.cn-section-sub   { font-size: 11px; color: var(--muted); margin: 0; }

/* Campos */
.cn-field { margin-bottom: 16px; }
.cn-field label { display: block; font-size: 13px; font-weight: 600; color: var(--text); margin-bottom: 6px; }
.cn-field .cn-hint { font-size: 11px; color: var(--muted); text-align: right; margin-top: 4px; }
.cn-input {
  width: 100%;
  padding: 10px 12px;
  border-radius: 8px;
  border: 1px solid var(--border);
  background: var(--bg);
  color: var(--text);
  font-size: 14px;
  transition: border-color .2s;
}
.cn-input:focus { outline: none; border-color: var(--accent); }
textarea.cn-input { resize: vertical; min-height: 80px; }

/* Categorías */
.cn-cat-dropdown { position: relative; }
.cn-cat-trigger {
  width: 100%;
  padding: 10px 12px;
  border-radius: 8px;
  border: 1px solid var(--border);
  background: var(--bg);
  color: var(--text);
  font-size: 14px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: space-between;
  user-select: none;
  transition: border-color .2s;
}
.cn-cat-trigger:hover { border-color: var(--accent); }
.cn-cat-trigger i { font-size: 14px; color: var(--muted); transition: transform .2s; }
.cn-cat-trigger.open i { transform: rotate(180deg); }
.cn-cat-menu {
  display: none;
  position: absolute;
  top: calc(100% + 6px);
  left: 0; right: 0;
  background: var(--card-bg);
  border: 1px solid var(--border);
  border-radius: 10px;
  z-index: 200;
  box-shadow: 0 8px 24px rgba(0,0,0,.15);
  max-height: 220px;
  overflow-y: auto;
  padding: 6px;
}
.cn-cat-menu.open { display: block; }
.cn-cat-option {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 10px;
  border-radius: 7px;
  cursor: pointer;
  font-size: 13px;
  color: var(--text);
  transition: background .15s;
}
.cn-cat-option:hover { background: rgba(239,51,99,.08); }
.cn-cat-option input[type="checkbox"] { accent-color: var(--accent); width: 15px; height: 15px; cursor: pointer; }
.cn-cat-option.selected { font-weight: 600; color: var(--accent); }
.cn-cat-chips { display: flex; flex-direction: column; gap: 6px; margin-top: 10px; min-height: 10px; }
.cn-chip {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 7px 10px;
  background: rgba(239,51,99,.1);
  border: 1px solid rgba(239,51,99,.25);
  border-radius: 8px;
  font-size: 13px;
  font-weight: 600;
  color: var(--text);
  cursor: grab;
  transition: background .15s, box-shadow .15s;
  user-select: none;
}
.cn-chip:active { cursor: grabbing; box-shadow: 0 4px 12px rgba(0,0,0,.2); }
.cn-chip.drag-over { border: 2px dashed var(--accent); background: rgba(239,51,99,.2); }
.cn-chip-num {
  width: 20px; height: 20px;
  border-radius: 50%;
  background: var(--accent);
  color: #fff;
  font-size: 11px; font-weight: 700;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.cn-chip-name { flex: 1; }
.cn-chip-remove {
  background: none; border: none;
  color: var(--muted); cursor: pointer;
  padding: 0; font-size: 14px; line-height: 1;
  display: flex; align-items: center;
}
.cn-chip-remove:hover { color: var(--accent); }
.cn-chip-drag { color: var(--muted); font-size: 14px; cursor: grab; }
.cn-cat-empty { font-size: 12px; color: var(--muted); text-align: center; padding: 8px 0; }

/* ── UPLOAD ZONES ── */
.cn-zone-label {
  font-size: 11px;
  font-weight: 600;
  color: var(--muted);
  text-align: center;
  margin-bottom: 6px;
}
.upload-zone {
  border: 2px dashed var(--border);
  border-radius: 10px;
  background: var(--bg);
  min-height: 110px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 6px;
  cursor: pointer;
  transition: border-color .2s, background .2s;
  position: relative;
  overflow: hidden;
  color: var(--muted);
  font-size: 0.82rem;
  text-align: center;
  padding: 12px;
}
.upload-zone:hover { border-color: var(--accent); background: rgba(239,51,99,.04); }
.upload-zone.has-image { border-style: solid; border-color: var(--accent); }
.upload-zone img.preview-img {
  position: absolute; inset: 0;
  width: 100%; height: 100%;
  object-fit: cover;
  border-radius: 8px;
  pointer-events: none;
}
.upload-zone .zone-overlay {
  position: absolute; inset: 0;
  background: rgba(0,0,0,.45);
  display: flex; flex-direction: column;
  align-items: center; justify-content: center;
  gap: 4px;
  color: #fff;
  opacity: 0;
  transition: opacity .2s;
  border-radius: 8px;
  font-size: 0.8rem;
  pointer-events: none;
}
.upload-zone.has-image:hover .zone-overlay { opacity: 1; }
.zone-ratio {
  font-size: 0.68rem;
  background: var(--border);
  border-radius: 4px;
  padding: 1px 6px;
  color: var(--muted);
}
.cn-zone-original { height: 160px; }
.cn-zone-banner   { height: 160px; }
.cn-zone-mini     { height: 120px; }
.cn-media-grid { display: grid; grid-template-columns: 1fr 2fr; gap: 14px; margin-bottom: 14px; }
@media (max-width: 768px) { .cn-media-grid { grid-template-columns: 1fr; } }

/* ── CROP MODAL ── */
.crop-modal-overlay {
  display: none;
  position: fixed; inset: 0;
  background: rgba(0,0,0,.75);
  z-index: 2000;
  align-items: center;
  justify-content: center;
}
.crop-modal-overlay.open { display: flex; }
.crop-modal-box {
  background: var(--card-bg);
  border-radius: 14px;
  width: min(92vw, 700px);
  overflow: hidden;
  box-shadow: 0 20px 60px rgba(0,0,0,.4);
}
.crop-modal-head {
  display: flex; align-items: center;
  padding: 14px 18px;
  border-bottom: 1px solid var(--border);
  font-weight: 700; gap: 10px;
}
.crop-modal-head button {
  margin-left: auto;
  background: none; border: none;
  color: var(--muted); cursor: pointer; font-size: 1.2rem;
}
.crop-modal-head button:hover { color: var(--accent); }
.crop-modal-body { padding: 16px; }
.crop-area {
  width: 100%;
  max-height: 400px;
  overflow: hidden;
  border-radius: 8px;
  background: #000;
}
.crop-area img { max-width: 100%; display: block; }
.crop-modal-foot {
  display: flex; gap: 10px;
  padding: 14px 18px;
  border-top: 1px solid var(--border);
  justify-content: flex-end;
}

/* ── PREVIEW CARDS ── */
.preview-section { margin-top: 16px; }
.preview-section-title {
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .05em;
  color: var(--muted);
  margin-bottom: 10px;
}
.preview-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.preview-card {
  border: 1px solid var(--border);
  border-radius: 10px;
  overflow: hidden;
  background: var(--bg);
}
.preview-card-label {
  font-size: 10px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .06em;
  color: var(--muted);
  padding: 6px 10px;
  border-bottom: 1px solid var(--border);
  display: flex; align-items: center; gap: 5px;
}
.preview-card-body { padding: 8px; display: flex; flex-direction: column; gap: 5px; }
.preview-img-box {
  width: 100%;
  background: var(--border);
  border-radius: 6px;
  overflow: hidden;
  display: flex; align-items: center; justify-content: center;
  color: var(--muted);
  font-size: 0.7rem;
}
.preview-img-box img { width: 100%; height: 100%; object-fit: cover; display: block; }
.preview-img-box.banner { height: 55px; }
.preview-img-box.thumb  { height: 80px; }
.preview-stub { height: 8px; background: var(--border); border-radius: 4px; margin: 2px 0; }
.preview-stub.short { width: 60%; }
.preview-title-stub { height: 10px; background: var(--border); border-radius: 4px; }

/* Editor Quill */
.cn-editor-wrap { border: 1px solid var(--border); border-radius: 10px; overflow: hidden; }
.cn-editor-wrap .editor-toolbar {
  border-radius: 0 !important;
  border-left: none !important;
  border-right: none !important;
  border-top: none !important;
  margin-bottom: 0 !important;
}
.cn-editor-wrap .editor-content { min-height: 260px; border-top: 1px solid var(--border); }

/* Programar */
.cn-schedule-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 14px;
}
.cn-schedule-info h4 { font-size: 14px; font-weight: 700; color: var(--text); margin: 0 0 2px; padding: 0; }
.cn-schedule-info p  { font-size: 12px; color: var(--muted); margin: 0; }
.cn-toggle-wrap { display: flex; align-items: center; }
.cn-toggle { position: relative; width: 44px; height: 24px; flex-shrink: 0; }
.cn-toggle input { opacity: 0; width: 0; height: 0; position: absolute; }
.cn-toggle-track {
  position: absolute; inset: 0;
  border-radius: 24px;
  background: var(--border);
  cursor: pointer;
  transition: background .2s;
}
.cn-toggle input:checked + .cn-toggle-track { background: var(--accent); }
.cn-toggle-thumb {
  position: absolute;
  width: 18px; height: 18px;
  background: #fff;
  border-radius: 50%;
  top: 3px; left: 3px;
  transition: left .2s;
  pointer-events: none;
  box-shadow: 0 1px 3px rgba(0,0,0,.3);
}
.cn-toggle input:checked ~ .cn-toggle-thumb { left: 23px; }
.cn-schedule-fields { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
@media (max-width: 600px) { .cn-schedule-fields { grid-template-columns: 1fr; } }
.cn-date-input {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 9px 12px;
  border: 1px solid var(--border);
  border-radius: 8px;
  background: var(--bg);
  color: var(--text);
  font-size: 13px;
  cursor: pointer;
}
.cn-date-input i { color: var(--muted); font-size: 16px; }
.cn-date-input input[type="date"],
.cn-date-input input[type="time"] {
  border: none; background: none;
  color: var(--text); font-size: 13px;
  padding: 0; outline: none; width: 100%;
}

/* Botón publicar */
.cn-publish-btn {
  width: 100%;
  padding: 13px;
  background: var(--accent);
  color: #fff;
  border: none;
  border-radius: 10px;
  font-size: 15px;
  font-weight: 700;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  transition: background .2s, transform .15s;
}
.cn-publish-btn:hover { background: #d42a55; transform: translateY(-2px); }
.cn-publish-btn:active { transform: translateY(0); }

.cn-sidebar-col { display: flex; flex-direction: column; gap: 20px; }
</style>

<div class="admin-container">

  <!-- Breadcrumb -->
  <div class="cn-breadcrumb">
    <a href="contenidos.php">Contenido</a>
    <i class="bi bi-chevron-right"></i>
    <span>Crear Noticia</span>
  </div>

  <h1 class="cn-page-title">Alta de noticia</h1>

  <form id="formPublicacion" action="./../controllers/noticiascontroller.php" method="POST" enctype="multipart/form-data">
    <input type="hidden" name="autor" value="<?= $fila['id_u'] ?? '' ?>">
    <input type="hidden" name="crop1" id="crop1">
    <input type="hidden" name="crop2" id="crop2">
    <input type="hidden" name="crop3" id="crop3">
    <input type="hidden" name="contenido" id="contenido">
    <input type="hidden" name="fecha_publicacion" id="fecha_publicacion_hidden">

    <div class="cn-grid">

      <!-- ==================== COLUMNA IZQUIERDA ==================== -->
      <div>

        <!-- INFORMACIÓN BÁSICA -->
        <div class="cn-section">
          <div class="cn-section-header">
            <div class="cn-section-icon"><i class="bi bi-info-circle"></i></div>
            <div>
              <p class="cn-section-title">Información Básica</p>
            </div>
          </div>
          <div class="cn-field">
            <label for="titulo">Título de la Noticia</label>
            <input class="cn-input" type="text" id="titulo" name="titulo" maxlength="50"
                   placeholder="Escribe un título impactante..." required>
            <div class="cn-hint"><span id="tituloCount">0</span>/50</div>
          </div>
          <div class="cn-field">
            <label for="descripcion">Descripción corta</label>
            <textarea class="cn-input" id="descripcion" name="descripcion" maxlength="150" rows="3"
                      placeholder="Resumen breve para redes sociales y buscadores..." required></textarea>
            <div class="cn-hint"><span id="descCount">0</span>/150</div>
          </div>
        </div>

        <!-- MULTIMEDIA -->
        <div class="cn-section">
          <div class="cn-section-header">
            <div class="cn-section-icon"><i class="bi bi-images"></i></div>
            <div>
              <p class="cn-section-title">Multimedia</p>
              <p class="cn-section-sub">Haz clic en cada zona para subir y recortar</p>
            </div>
          </div>

          <!-- Fila 1: Original + Banner -->
          <div class="cn-media-grid">
            <div>
              <p class="cn-zone-label">Imagen Original</p>
              <div class="upload-zone cn-zone-original" id="zone1" onclick="openCrop(1)">
                <div class="zone-overlay"><span>Cambiar imagen</span></div>
                <i class="bi bi-camera" style="font-size:22px;color:var(--muted)"></i>
                <span>Click para subir</span>
                <span class="zone-ratio">1 : 1</span>
              </div>
            </div>
            <div>
              <p class="cn-zone-label">Imagen Banner</p>
              <div class="upload-zone cn-zone-banner" id="zone2" onclick="openCrop(2)">
                <div class="zone-overlay"><span>Cambiar imagen</span></div>
                <i class="bi bi-aspect-ratio" style="font-size:22px;color:var(--muted)"></i>
                <span>Optimizado para cabeceras</span>
                <span class="zone-ratio">21 : 6</span>
              </div>
            </div>
          </div>

          <!-- Fila 2: Miniatura -->
          <div style="margin-bottom:14px;">
            <p class="cn-zone-label">Miniatura</p>
            <div class="upload-zone cn-zone-mini" id="zone3" onclick="openCrop(3)">
              <div class="zone-overlay"><span>Cambiar</span></div>
              <i class="bi bi-image" style="font-size:22px;color:var(--muted)"></i>
              <span>Vista previa en listados y carruseles</span>
              <span class="zone-ratio">16 : 9</span>
            </div>
          </div>

          <!-- Vista previa WEB / MÓVIL -->
          <div class="preview-section" id="previewSection" style="display:none;">
            <div class="preview-section-title">Vista previa</div>
            <div class="preview-grid">
              <div class="preview-card">
                <div class="preview-card-label">
                  <i class="bi bi-display"></i> Vista WEB
                </div>
                <div class="preview-card-body">
                  <div class="preview-img-box banner" id="pvWebBanner"><span>Banner</span></div>
                  <div class="preview-title-stub"></div>
                  <div class="preview-stub short"></div>
                </div>
              </div>
              <div class="preview-card">
                <div class="preview-card-label">
                  <i class="bi bi-phone"></i> Vista MÓVIL
                </div>
                <div class="preview-card-body">
                  <div class="preview-img-box thumb" id="pvMobThumb"><span>Miniatura</span></div>
                  <div class="preview-title-stub"></div>
                  <div class="preview-stub short"></div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- EDITOR CONTENIDO -->
        <div class="cn-section">
          <div class="cn-section-header">
            <div class="cn-section-icon"><i class="bi bi-pencil-square"></i></div>
            <div>
              <p class="cn-section-title">Contenido</p>
            </div>
          </div>
          <div class="cn-editor-wrap">
            <div class="editor-toolbar ql-toolbar ql-snow">
              <select class="ql-font" title="Fuente">
                <option value="arial" selected>Arial</option>
                <option value="times">Times New Roman</option>
                <option value="roboto">Roboto</option>
                <option value="courier">Courier</option>
              </select>
              <select class="ql-size" title="Tamaño">
                <option value="small">Pequeño</option>
                <option selected>Normal</option>
                <option value="large">Grande</option>
                <option value="huge">Muy grande</option>
              </select>
              <button class="ql-bold" title="Negritas"></button>
              <button class="ql-italic" title="Cursiva"></button>
              <button class="ql-underline" title="Subrayado"></button>
              <button class="ql-strike" title="Tachado"></button>
              <select class="ql-color" title="Color"></select>
              <select class="ql-background" title="Fondo"></select>
              <select class="ql-align" title="Alineación"></select>
              <select class="ql-lineheight" title="Interlineado">
                <option value="0">0</option>
                <option value="0.85">0.85</option>
                <option value="1">1</option>
                <option value="1.5">1.5</option>
                <option value="2">2</option>
                <option value="2.5">2.5</option>
                <option value="3">3</option>
              </select>
              <button class="ql-list" value="ordered" title="Lista ordenada"></button>
              <button class="ql-list" value="bullet" title="Lista desordenada"></button>
              <button class="ql-indent" value="-1" title="Reducir sangría"></button>
              <button class="ql-indent" value="+1" title="Aumentar sangría"></button>
              <button class="ql-link" title="Añadir link"></button>
              <button class="ql-image" title="Insertar imagen"></button>
              <button class="ql-video" title="Insertar video"></button>
              <button class="ql-clean" title="Limpiar formato"></button>
              <button class="ql-embed" title="Embebido"><i class="bi bi-boxes"></i></button>
            </div>
            <div id="editor" class="editor-content"></div>
          </div>
          <input type="file" id="imageInputEditor" accept="image/*" hidden>
        </div>

      </div><!-- /columna izquierda -->

      <!-- ==================== COLUMNA DERECHA ==================== -->
      <div class="cn-sidebar-col">

        <!-- CATEGORÍAS -->
        <div class="cn-section">
          <div class="cn-section-header">
            <div class="cn-section-icon"><i class="bi bi-tag"></i></div>
            <div>
              <p class="cn-section-title">Categoría</p>
              <p class="cn-section-sub">El orden define la importancia</p>
            </div>
          </div>
          <div class="cn-cat-dropdown">
            <div class="cn-cat-trigger" id="catTrigger">
              <span id="catTriggerLabel">Selecciona las categorías</span>
              <i class="bi bi-chevron-down"></i>
            </div>
            <div class="cn-cat-menu" id="catMenu">
              <?php foreach($categorias as $cat): ?>
              <label class="cn-cat-option" data-id="<?= $cat['id_c'] ?>" data-name="<?= htmlspecialchars($cat['nombre']) ?>">
                <input type="checkbox" value="<?= $cat['id_c'] ?>" data-name="<?= htmlspecialchars($cat['nombre']) ?>">
                <?= htmlspecialchars($cat['nombre']) ?>
              </label>
              <?php endforeach; ?>
            </div>
          </div>
          <div class="cn-cat-chips" id="catChips">
            <p class="cn-cat-empty" id="catEmpty">Ninguna categoría seleccionada</p>
          </div>
          <div id="catInputs"></div>
        </div>

      </div><!-- /columna derecha -->

    </div><!-- /cn-grid -->

    <!-- PROGRAMAR -->
    <div class="cn-section" style="margin-top:20px;">
      <div class="cn-section-header">
        <div class="cn-section-icon"><i class="bi bi-calendar-event"></i></div>
        <div>
          <p class="cn-section-title">Programar</p>
        </div>
      </div>
      <div class="cn-schedule-row">
        <div class="cn-schedule-info">
          <h4>Establecer fecha y hora</h4>
          <p>Programa tu publicación para cuando tu público esté más activo o selecciona una fecha y hora de forma manual.</p>
        </div>
        <div class="cn-toggle-wrap">
          <label class="cn-toggle">
            <input type="checkbox" id="scheduleToggle" checked>
            <div class="cn-toggle-track"></div>
            <div class="cn-toggle-thumb"></div>
          </label>
        </div>
      </div>
      <div id="scheduleFields">
        <div class="cn-schedule-fields">
          <div class="cn-date-input">
            <i class="bi bi-calendar3"></i>
            <input type="date" id="schedDate" required>
          </div>
          <div class="cn-date-input">
            <i class="bi bi-clock"></i>
            <input type="time" id="schedTime" required>
          </div>
        </div>
      </div>
    </div>

    <!-- BOTÓN PUBLICAR -->
    <?php if (!empty($ACL['crear'])): ?>
    <div class="form-actions">
      <button type="submit" class="cn-publish-btn" name="guardarNoticia" style="max-width:400px;">
        <i class="bi bi-send"></i> Publicar noticia
      </button>
    </div>
    <?php endif; ?>
  </form>
</div><!-- /admin-container -->

<!-- ==================== CROP MODAL ==================== -->
<div class="crop-modal-overlay" id="cropModal">
  <div class="crop-modal-box">
    <div class="crop-modal-head">
      <i class="bi bi-crop"></i>
      <span id="cropModalTitle">Recortar imagen</span>
      <button type="button" onclick="closeCrop()">✕</button>
    </div>
    <div class="crop-modal-body">
      <div class="crop-area">
        <img id="cropImg" src="">
      </div>
    </div>
    <div class="crop-modal-foot">
      <button type="button" class="btn btn-outline-secondary" onclick="closeCrop()">Cancelar</button>
      <button type="button" class="btn btn-accent" onclick="confirmCrop()">
        <i class="bi bi-check"></i> Confirmar
      </button>
    </div>
  </div>
</div>
<!-- Input oculto para selección de archivo -->
<input type="file" id="fileInput" accept="image/*" style="display:none;" onchange="onFileSelected(event)">

<!-- ==================== MODAL HORA INVÁLIDA ==================== -->
<div id="timeModalOverlay" class="crop-modal" style="display:none;">
  <div class="crop-modal-content">
    <h3>Hora no válida</h3>
    <p>La fecha y hora seleccionadas es menor a la actual.<br><br>¿Qué deseas hacer?</p>
    <div class="modal-actions">
      <button class="btn-accent" id="autoAdjustBtn" type="button">Ajustar automáticamente y guardar</button>
      <button class="btn-secondary" id="manualAdjustBtn" type="button">Volver a ajustar la hora</button>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {

  /* ── Contadores de caracteres ── */
  const titulo = document.getElementById('titulo');
  const tCount = document.getElementById('tituloCount');
  titulo?.addEventListener('input', () => { tCount.textContent = titulo.value.length; });

  const desc   = document.getElementById('descripcion');
  const dCount = document.getElementById('descCount');
  desc?.addEventListener('input', () => { dCount.textContent = desc.value.length; });

  /* ── Categorías ── */
  const catTrigger = document.getElementById('catTrigger');
  const catMenu    = document.getElementById('catMenu');
  const catChips   = document.getElementById('catChips');
  const catEmpty   = document.getElementById('catEmpty');
  const catInputs  = document.getElementById('catInputs');
  const catLabel   = document.getElementById('catTriggerLabel');

  catTrigger?.addEventListener('click', () => {
    catTrigger.classList.toggle('open');
    catMenu.classList.toggle('open');
  });
  document.addEventListener('click', e => {
    if (!catTrigger?.contains(e.target) && !catMenu?.contains(e.target)) {
      catTrigger?.classList.remove('open');
      catMenu?.classList.remove('open');
    }
  });
  catMenu?.querySelectorAll('input[type="checkbox"]').forEach(chk => {
    chk.addEventListener('change', () => {
      chk.closest('.cn-cat-option').classList.toggle('selected', chk.checked);
      if (chk.checked) addChip(chk.value, chk.dataset.name);
      else removeChip(chk.value);
      updateCatLabel();
    });
  });

  function addChip(id, name) {
    if (catChips.querySelector(`[data-id="${id}"]`)) return;
    catEmpty.style.display = 'none';
    const num  = catChips.querySelectorAll('.cn-chip').length + 1;
    const chip = document.createElement('div');
    chip.className = 'cn-chip';
    chip.dataset.id = id;
    chip.draggable  = true;
    chip.innerHTML  = `
      <i class="bi bi-grip-vertical cn-chip-drag"></i>
      <span class="cn-chip-num">${num}</span>
      <span class="cn-chip-name">${name}</span>
      <button type="button" class="cn-chip-remove" data-id="${id}"><i class="bi bi-x"></i></button>
    `;
    chip.querySelector('.cn-chip-remove').addEventListener('click', () => {
      removeChip(id);
      const chk = catMenu.querySelector(`input[value="${id}"]`);
      if (chk) { chk.checked = false; chk.closest('.cn-cat-option')?.classList.remove('selected'); }
      updateCatLabel();
    });
    setupDrag(chip);
    catChips.appendChild(chip);
    rebuildInputs();
  }

  function removeChip(id) {
    catChips.querySelector(`[data-id="${id}"]`)?.remove();
    renumberChips();
    rebuildInputs();
    if (!catChips.querySelector('.cn-chip')) catEmpty.style.display = '';
  }

  function renumberChips() {
    catChips.querySelectorAll('.cn-chip').forEach((c, i) => {
      c.querySelector('.cn-chip-num').textContent = i + 1;
    });
  }

  function rebuildInputs() {
    catInputs.innerHTML = '';
    catChips.querySelectorAll('.cn-chip').forEach(c => {
      const inp = document.createElement('input');
      inp.type = 'hidden'; inp.name = 'categoria[]'; inp.value = c.dataset.id;
      catInputs.appendChild(inp);
    });
  }

  function updateCatLabel() {
    const count = catChips.querySelectorAll('.cn-chip').length;
    catLabel.textContent = count === 0
      ? 'Selecciona las categorías'
      : `${count} categoría${count > 1 ? 's' : ''} seleccionada${count > 1 ? 's' : ''}`;
  }

  let dragSrc = null;
  function setupDrag(chip) {
    chip.addEventListener('dragstart', e => {
      dragSrc = chip; e.dataTransfer.effectAllowed = 'move';
      setTimeout(() => chip.style.opacity = '0.4', 0);
    });
    chip.addEventListener('dragend',  () => { chip.style.opacity = '1'; dragSrc = null; });
    chip.addEventListener('dragover', e => { e.preventDefault(); chip.classList.add('drag-over'); });
    chip.addEventListener('dragleave',() => chip.classList.remove('drag-over'));
    chip.addEventListener('drop', e => {
      e.preventDefault(); chip.classList.remove('drag-over');
      if (!dragSrc || dragSrc === chip) return;
      const all = [...catChips.querySelectorAll('.cn-chip')];
      if (all.indexOf(dragSrc) < all.indexOf(chip)) chip.after(dragSrc);
      else chip.before(dragSrc);
      renumberChips(); rebuildInputs();
    });
  }

  /* ── Programar toggle ── */
  const schedToggle = document.getElementById('scheduleToggle');
  const schedFields = document.getElementById('scheduleFields');
  const schedDate   = document.getElementById('schedDate');
  const schedTime   = document.getElementById('schedTime');

  function nowLocal() {
    const now    = new Date();
    const offset = now.getTimezoneOffset();
    const local  = new Date(now.getTime() - offset * 60000);
    return local.toISOString().slice(0,16);
  }
  const localNow = nowLocal();
  schedDate.value = localNow.slice(0,10);
  schedTime.value = localNow.slice(11,16);

  schedToggle?.addEventListener('change', () => {
    schedFields.style.display = schedToggle.checked ? '' : 'none';
  });

  /* ── Submit ── */
  const form         = document.getElementById('formPublicacion');
  const hiddenFecha  = document.getElementById('fecha_publicacion_hidden');
  const contenidoHid = document.getElementById('contenido');

  form?.addEventListener('submit', e => {
    if (window.quill) {
      let html = quill.root.innerHTML;
      html = html.replace(/<div class="social-embed"[^>]*data-url="([^"]+)"[^>]*>.*?<\/div>/gi,
                          '<div class="social-embed" data-url="$1"></div>');
      contenidoHid.value = html;
    }
    if (schedToggle?.checked) {
      hiddenFecha.value = (schedDate.value + 'T' + schedTime.value).replace('T',' ');
    } else {
      hiddenFecha.value = nowLocal().replace('T',' ');
    }
  });

  /* ── Modal hora inválida ── */
  const modalTime      = document.getElementById('timeModalOverlay');
  const autoAdjustBtn  = document.getElementById('autoAdjustBtn');
  const manualAdjustBtn= document.getElementById('manualAdjustBtn');

  document.getElementsByName('guardarNoticia')[0]?.addEventListener('click', e => {
    e.preventDefault();
    if (!schedToggle?.checked) { form.requestSubmit(); return; }
    const selected = schedDate.value + ' ' + schedTime.value;
    const now      = nowLocal().replace('T',' ');
    if (selected < now) { modalTime.style.display = 'flex'; }
    else { form.requestSubmit(); }
  });

  autoAdjustBtn?.addEventListener('click', () => {
    const ln = nowLocal();
    schedDate.value = ln.slice(0,10);
    schedTime.value = ln.slice(11,16);
    modalTime.style.display = 'none';
    form.requestSubmit();
  });
  manualAdjustBtn?.addEventListener('click', () => { modalTime.style.display = 'none'; });
  document.querySelector('#timeModalOverlay .crop-modal-content')
    ?.addEventListener('click', e => e.stopPropagation());

});

/* ════════════════════════════════════════════════
   CROPPER — sistema propio, independiente de admin.js
════════════════════════════════════════════════ */
const CROP_RATIOS = { 1: 1/1, 2: 21/6, 3: 16/9 };
const CROP_TITLES = {
  1: 'Recortar — Imagen Original (1:1)',
  2: 'Recortar — Banner (21:6)',
  3: 'Recortar — Miniatura (16:9)'
};
let activeCrop      = null;
let cropperInstance = null;

function openCrop(num) {
  activeCrop = num;
  document.getElementById('fileInput').click();
}

function onFileSelected(e) {
  const file = e.target.files[0];
  if (!file) return;
  const reader = new FileReader();
  reader.onload = ev => {
    const cropImg = document.getElementById('cropImg');
    cropImg.src   = ev.target.result;
    document.getElementById('cropModalTitle').textContent = CROP_TITLES[activeCrop];
    document.getElementById('cropModal').classList.add('open');
    if (cropperInstance) { cropperInstance.destroy(); cropperInstance = null; }
    // Esperar a que la imagen cargue antes de inicializar el cropper
    cropImg.onload = () => {
      cropperInstance = new Cropper(cropImg, {
        aspectRatio:      CROP_RATIOS[activeCrop],
        viewMode:         0,
        autoCropArea:     0.9,
        movable:          true,
        zoomable:         true,
        cropBoxResizable: true,
        dragMode:         'move',
        responsive:       true,
        guides:           true,
        background:       false
      });
    };
  };
  reader.readAsDataURL(file);
  e.target.value = ''; // reset para permitir re-seleccionar el mismo archivo
}

function closeCrop() {
  document.getElementById('cropModal').classList.remove('open');
  if (cropperInstance) { cropperInstance.destroy(); cropperInstance = null; }
}

function confirmCrop() {
  if (!cropperInstance) return;
  const canvas = cropperInstance.getCroppedCanvas({ maxWidth: 1920, maxHeight: 1920 });
  const data64 = canvas.toDataURL('image/jpeg', 0.85);

  // Guardar en input hidden
  document.getElementById('crop' + activeCrop).value = data64;

  // Actualizar zona visual
  const zone = document.getElementById('zone' + activeCrop);
  let img = zone.querySelector('.preview-img');
  if (!img) {
    img = document.createElement('img');
    img.className = 'preview-img';
    zone.appendChild(img);
  }
  img.src = data64;
  zone.classList.add('has-image');

  // Actualizar vistas previas
  if (activeCrop === 2) {
    const pv = document.getElementById('pvWebBanner');
    pv.innerHTML = `<img src="${data64}">`;
  }
  if (activeCrop === 3) {
    const pv = document.getElementById('pvMobThumb');
    pv.innerHTML = `<img src="${data64}">`;
  }

  // Mostrar sección preview si hay al menos crop2 o crop3
  const c2 = document.getElementById('crop2').value;
  const c3 = document.getElementById('crop3').value;
  document.getElementById('previewSection').style.display = (c2 || c3) ? 'block' : 'none';

  closeCrop();
}
</script>

<?php include(__DIR__ . "/../layout/footerAdmin.php"); ?>