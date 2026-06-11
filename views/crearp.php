<?php
    include("./../layout/headerAdmin.php");
    include("./../controllers/aclcontroller.php");
    proteger('publicidad','crear');
    include("./../data/conexion.php");
    $categoriasResult = $con->query("SELECT id_c, nombre FROM categorias ORDER BY nombre ASC");
    $categorias = [];
    while($row = $categoriasResult->fetch_assoc()){
        $categorias[] = $row;
    }
?>

<style>
/* ── LAYOUT ── */
.cn-breadcrumb {
  display: flex; align-items: center; gap: 6px;
  font-size: 13px; color: var(--muted); margin-bottom: 16px;
}
.cn-breadcrumb a { color: var(--muted); text-decoration: none; }
.cn-breadcrumb a:hover { color: var(--accent); }
.cn-breadcrumb span { color: var(--accent); }
.cn-page-title { font-size: 1.5rem; font-weight: 700; margin: 0 0 20px; color: var(--text); text-align: center; }
.admin-container { max-width: none !important; padding: 0 !important; }

.cn-wrap {
  width: 100%; margin: 0;
  display: grid;
  grid-template-columns: minmax(0, 2fr) minmax(0, 3fr);
  gap: 16px; align-items: start;
}
.cn-left-col { display: flex; flex-direction: column; gap: 16px; }
@media (max-width: 860px) {
  .cn-wrap { grid-template-columns: 1fr; }
}

/* ── SECTION CARD ── */
.cn-section {
  background: var(--card-bg); border: 1px solid var(--border);
  border-radius: 14px; overflow: hidden; transition: box-shadow .2s;
}
.cn-section-header {
  display: flex; align-items: center; gap: 10px;
  padding: 14px 18px; cursor: pointer; user-select: none;
  border-bottom: 1px solid var(--border); transition: background .15s;
}
.cn-section-header:hover { background: rgba(239,51,99,.04); }
.cn-section-icon {
  width: 30px; height: 30px; border-radius: 8px;
  background: rgba(239,51,99,0.12); color: var(--accent);
  display: flex; align-items: center; justify-content: center;
  font-size: 15px; flex-shrink: 0;
}
.cn-section-title { font-size: 0.92rem; font-weight: 700; color: var(--text); margin: 0; }
.cn-section-sub   { font-size: 11px; color: var(--muted); margin: 0; }
.cn-section-toggle {
  margin-left: auto; color: var(--muted);
  font-size: 13px; flex-shrink: 0; transition: transform .25s;
}
.cn-section.collapsed .cn-section-toggle { transform: rotate(-90deg); }
.cn-section-body {
  overflow: hidden; max-height: 3000px; opacity: 1;
  transition: max-height .35s ease, opacity .25s ease;
  padding: 18px;
}
.cn-section.collapsed .cn-section-body { max-height: 0; opacity: 0; padding-top: 0; padding-bottom: 0; }

/* ── FORM FIELDS ── */
.cn-field { margin-bottom: 14px; }
.cn-field:last-child { margin-bottom: 0; }
.cn-field label { display: block; font-size: 13px; font-weight: 600; color: var(--text); margin-bottom: 5px; }
.cn-field .cn-hint-text { font-size: 11px; color: var(--muted); margin-top: 3px; }
.cn-input {
  width: 100%; padding: 9px 12px; border-radius: 8px;
  border: 1px solid var(--border); background: var(--bg); color: var(--text);
  font-size: 14px; transition: border-color .2s; font-family: inherit;
}
.cn-input:focus { outline: none; border-color: var(--accent); }

/* ── CATEGORÍAS ── */
.cn-cat-grid {
  display: flex; flex-direction: column; gap: 6px; margin-top: 4px;
}
.cn-cat-check-item {
  display: flex; align-items: center; gap: 8px; padding: 7px 10px;
  border-radius: 8px; cursor: pointer; font-size: 13px; color: var(--text);
  transition: background .15s; border: 1px solid transparent;
}
.cn-cat-check-item:hover { background: rgba(239,51,99,.08); }
.cn-cat-check-item input[type="checkbox"] { accent-color: var(--accent); width: 15px; height: 15px; cursor: pointer; flex-shrink: 0; }

/* ── FECHAS ── */
.cn-schedule-fields { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.cn-date-input {
  display: flex; align-items: center; gap: 8px;
  padding: 9px 12px; border: 1px solid var(--border);
  border-radius: 8px; background: var(--bg); color: var(--text); font-size: 13px;
}
.cn-date-input i { color: var(--muted); font-size: 15px; }
.cn-date-input input[type="datetime-local"] {
  border: none; background: none; color: var(--text);
  font-size: 13px; padding: 0; outline: none; width: 100%; font-family: inherit;
}

/* ── UPLOAD ZONE ── */
.cn-zone-label {
  font-size: 11px; font-weight: 600; color: var(--muted);
  text-align: center; margin-bottom: 5px; text-transform: uppercase; letter-spacing:.04em;
}
.upload-zone {
  border: 2px dashed var(--border); border-radius: 10px;
  background: var(--bg); display: flex; flex-direction: column;
  align-items: center; justify-content: center; gap: 5px;
  cursor: pointer; position: relative; overflow: hidden;
  color: var(--muted); font-size: 0.82rem; text-align: center;
  padding: 28px 12px; transition: border-color .2s, background .2s;
}
.upload-zone:hover { border-color: var(--accent); background: rgba(239,51,99,.04); }
.upload-zone.has-image { border-style: solid; border-color: var(--accent); padding: 0; }
.cn-zone-icon { font-size: 26px; }

/* ── CROPPER INLINE ── */
.crop-inline-wrap {
  margin-top: 14px; border: 1px solid var(--border);
  border-radius: 10px; overflow: hidden; display: none;
}
.crop-inline-head {
  display: flex; align-items: center; justify-content: space-between;
  padding: 10px 14px; border-bottom: 1px solid var(--border);
  font-size: 13px; font-weight: 600; color: var(--text);
}
.crop-inline-body { padding: 12px; background: var(--bg); }
.crop-inline-body img { max-width: 100%; display: block; border-radius: 6px; }
.crop-inline-foot {
  display: flex; gap: 8px; padding: 10px 14px;
  border-top: 1px solid var(--border);
}

/* ── PREVIEW ── */
.cp-preview-wrap { display: none; margin-top: 14px; }
.cp-preview-label {
  font-size: 11px; font-weight: 600; color: var(--muted);
  text-transform: uppercase; letter-spacing:.04em; margin-bottom: 6px;
}
.cp-preview-img {
  width: 100%; border-radius: 10px; border: 1px solid var(--border);
  display: block;
}

/* ── BOTÓN PUBLICAR ── */
.cn-publish-btn {
  width: 100%; padding: 13px; background: var(--accent); color: #fff;
  border: none; border-radius: 10px; font-size: 15px; font-weight: 700;
  cursor: pointer; display: flex; align-items: center; justify-content: center;
  gap: 8px; transition: background .2s, transform .15s; font-family: inherit;
}
.cn-publish-btn:hover { background: #d42a55; transform: translateY(-2px); }
.cn-publish-btn:active { transform: translateY(0); }

/* ── TOAST VALIDACIÓN ── */
.cn-toast {
  position: fixed; bottom: 28px; left: 50%; transform: translateX(-50%);
  background: #1a1a2e; color: #fff; border: 1.5px solid var(--accent);
  border-radius: 10px; padding: 13px 20px; z-index: 9999;
  font-size: 13px; min-width: 270px; max-width: 400px;
  box-shadow: 0 8px 28px rgba(0,0,0,.5); display: none;
}
.cn-toast.show { display: block; animation: toastIn .22s ease; }
@keyframes toastIn {
  from { opacity:0; transform: translateX(-50%) translateY(12px); }
  to   { opacity:1; transform: translateX(-50%) translateY(0); }
}
.cn-toast-title { font-weight: 700; color: var(--accent); margin-bottom: 7px; }
.cn-toast ul { margin: 0; padding-left: 16px; }
.cn-toast li { margin: 3px 0; }

/* ── BOTONES SECUNDARIOS ── */
.cn-btn-secondary {
  padding: 8px 16px; border-radius: 8px; font-size: 13px; font-weight: 600;
  cursor: pointer; border: 1px solid var(--border); background: var(--bg);
  color: var(--text); font-family: inherit; transition: border-color .2s;
}
.cn-btn-secondary:hover { border-color: var(--accent); }
.cn-btn-accent {
  padding: 8px 16px; border-radius: 8px; font-size: 13px; font-weight: 600;
  cursor: pointer; border: none; background: var(--accent);
  color: #fff; font-family: inherit;
}
.cn-btn-accent:hover { background: #d42a55; }
</style>

<div class="admin-container">

  <div class="cn-breadcrumb">
    <a href="publicidad.php">Publicidad</a>
    <i class="bi bi-chevron-right"></i>
    <span>Crear Publicidad</span>
  </div>

  <h1 class="cn-page-title">Nueva Publicidad</h1>

  <form id="formPublicidad" action="./../../controllers/guardar_publicidad.php" method="POST" enctype="multipart/form-data">
    <input type="hidden" name="autor" value="<?= $fila['id_u'] ?? '' ?>">
    <input type="hidden" name="imagenCrop" id="imagenCrop" value="">

    <div class="cn-wrap">

      <div class="cn-left-col">

        <!-- INFORMACIÓN BÁSICA -->
        <div class="cn-section" id="sec-info">
          <div class="cn-section-header">
            <div class="cn-section-icon"><i class="bi bi-info-circle"></i></div>
            <div>
              <p class="cn-section-title">Información Básica</p>
            </div>
            <i class="bi bi-chevron-down cn-section-toggle"></i>
          </div>
          <div class="cn-section-body">
            <div class="cn-field">
              <label for="Titulo">Título</label>
              <input class="cn-input" type="text" id="Titulo" name="Titulo" placeholder="Nombre de la publicidad..." required>
            </div>
            <div class="cn-field">
              <label for="url">URL de destino</label>
              <p class="cn-hint-text">A dónde va a redirigir al hacer clic</p>
              <input class="cn-input" type="text" id="url" name="url" placeholder="https://..." required>
            </div>
          </div>
        </div>

        <!-- CONFIGURACIÓN -->
        <div class="cn-section" id="sec-config">
          <div class="cn-section-header">
            <div class="cn-section-icon"><i class="bi bi-sliders"></i></div>
            <div>
              <p class="cn-section-title">Configuración</p>
            </div>
            <i class="bi bi-chevron-down cn-section-toggle"></i>
          </div>
          <div class="cn-section-body">
            <div class="cn-field">
              <label for="tipo">Tipo de publicidad</label>
              <select class="cn-input" id="tipo" name="tipo" required>
                <option value="1">Banner Publicitario (16:9)</option>
                <option value="2">Cuadro Publicitario (1:1)</option>
              </select>
            </div>
            <div class="cn-field">
              <label for="estado">Estado</label>
              <select class="cn-input" id="estado" name="estado" required>
                <option value="1">Activo</option>
                <option value="0">Inactivo</option>
              </select>
            </div>
          </div>
        </div>

        <!-- CATEGORÍAS -->
        <div class="cn-section" id="sec-cats">
          <div class="cn-section-header">
            <div class="cn-section-icon"><i class="bi bi-tag"></i></div>
            <div>
              <p class="cn-section-title">Categorías</p>
            </div>
            <i class="bi bi-chevron-down cn-section-toggle"></i>
          </div>
          <div class="cn-section-body">
            <div class="cn-cat-grid">
              <?php foreach($categorias as $c): ?>
              <label class="cn-cat-check-item">
                <input type="checkbox" name="Categorias[]" value="<?= $c['id_c'] ?>">
                <?= htmlspecialchars($c['nombre']) ?>
              </label>
              <?php endforeach; ?>
            </div>
          </div>
        </div>

        <!-- VIGENCIA -->
        <div class="cn-section" id="sec-vigencia">
          <div class="cn-section-header">
            <div class="cn-section-icon"><i class="bi bi-calendar-range"></i></div>
            <div>
              <p class="cn-section-title">Vigencia</p>
              <p class="cn-section-sub">Período de publicación</p>
            </div>
            <i class="bi bi-chevron-down cn-section-toggle"></i>
          </div>
          <div class="cn-section-body">
            <div class="cn-schedule-fields">
              <div>
                <label style="font-size:12px;font-weight:600;color:var(--muted);display:block;margin-bottom:6px;">Inicio</label>
                <div class="cn-date-input">
                  <i class="bi bi-calendar3"></i>
                  <input type="datetime-local" id="fechaInicio" name="fechaInicio" required>
                </div>
              </div>
              <div>
                <label style="font-size:12px;font-weight:600;color:var(--muted);display:block;margin-bottom:6px;">Fin</label>
                <div class="cn-date-input">
                  <i class="bi bi-calendar-x"></i>
                  <input type="datetime-local" id="fechaFin" name="fechaFin" required>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- BOTÓN PUBLICAR -->
        <div style="margin-top:4px;">
          <button type="submit" class="cn-publish-btn" name="guardarPublicidad">
            <i class="bi bi-send"></i> Guardar publicidad
          </button>
        </div>

      </div><!-- /cn-left-col -->

      <!-- MULTIMEDIA -->
      <div class="cn-section" id="sec-media">
        <div class="cn-section-header">
          <div class="cn-section-icon"><i class="bi bi-images"></i></div>
          <div>
            <p class="cn-section-title">Imagen</p>
            <p class="cn-section-sub">El recorte se ajusta al tipo seleccionado</p>
          </div>
          <i class="bi bi-chevron-down cn-section-toggle"></i>
        </div>
        <div class="cn-section-body">

          <!-- Zona de subida -->
          <p class="cn-zone-label">Imagen publicitaria</p>
          <div class="upload-zone" id="uploadZone" onclick="document.getElementById('imagen').click()">
            <i class="bi bi-cloud-arrow-up cn-zone-icon"></i>
            <span>Haz clic para seleccionar imagen</span>
            <span style="font-size:11px">PNG, JPG, WEBP</span>
          </div>
          <input type="file" id="imagen" name="imagen" accept="image/*" required style="display:none;">

          <!-- Cropper inline -->
          <div class="crop-inline-wrap" id="cropperContainer">
            <div class="crop-inline-head">
              <span><i class="bi bi-crop"></i> Recortar imagen</span>
            </div>
            <div class="crop-inline-body">
              <img id="cropImg" style="max-width:100%;">
            </div>
            <div class="crop-inline-foot">
              <button type="button" id="cropConfirmBtn" class="cn-btn-accent"><i class="bi bi-check-lg"></i> Confirmar recorte</button>
              <button type="button" id="cropCancelBtn" class="cn-btn-secondary">Cancelar</button>
            </div>
          </div>

          <!-- Vista previa post-crop -->
          <div class="cp-preview-wrap" id="previewContainer">
            <p class="cp-preview-label">Vista previa</p>
            <img id="previewImg" class="cp-preview-img">
            <button type="button" class="cn-btn-secondary" style="margin-top:10px;width:100%;"
              onclick="document.getElementById('imagen').click()">
              <i class="bi bi-arrow-repeat"></i> Cambiar imagen
            </button>
          </div>

        </div>
      </div><!-- /sec-media -->

    </div><!-- /cn-wrap -->
  </form>

</div>

<!-- ── TOAST VALIDACIÓN ── -->
<div class="cn-toast" id="cnToast">
  <div class="cn-toast-title"><i class="bi bi-exclamation-circle"></i> Faltan campos requeridos:</div>
  <ul id="cnToastList"></ul>
</div>

<script>
let cropper = null;

function getAspectRatio() {
    const tipo = document.getElementById('tipo').value;
    return tipo === '1' ? 16/9 : 1;
}

/* Mostrar nombre en zona de upload */
document.getElementById('imagen').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = function(event) {
        const cropImg = document.getElementById('cropImg');
        cropImg.src = event.target.result;
        document.getElementById('cropperContainer').style.display = 'block';
        document.getElementById('previewContainer').style.display = 'none';

        if (cropper) cropper.destroy();

        cropper = new Cropper(cropImg, {
            aspectRatio: getAspectRatio(),
            autoCropArea: 1,
            responsive: true,
            restore: true,
            guides: true,
            center: true,
            highlight: true,
            cropBoxMovable: true,
            cropBoxResizable: true,
            toggleDragModeOnDblclick: true,
        });
    };
    reader.readAsDataURL(file);
});

document.getElementById('tipo').addEventListener('change', function() {
    if (cropper) cropper.setAspectRatio(getAspectRatio());
});

document.getElementById('cropConfirmBtn').addEventListener('click', function(e) {
    e.preventDefault();
    if (!cropper) return;

    const canvas = cropper.getCroppedCanvas({
        maxWidth: 2560,
        maxHeight: 2560,
        imageSmoothingEnabled: true,
        imageSmoothingQuality: 'high',
    });

    const data64 = canvas.toDataURL('image/png');
    document.getElementById('imagenCrop').value = data64;

    const previewImg = document.getElementById('previewImg');
    previewImg.src = data64;
    document.getElementById('previewContainer').style.display = 'block';
    document.getElementById('cropperContainer').style.display = 'none';
});

document.getElementById('cropCancelBtn').addEventListener('click', function(e) {
    e.preventDefault();
    document.getElementById('imagen').value = '';
    document.getElementById('imagenCrop').value = '';
    document.getElementById('cropperContainer').style.display = 'none';
    document.getElementById('previewContainer').style.display = 'none';
    if (cropper) { cropper.destroy(); cropper = null; }
});

/* Collapse de secciones */
document.querySelectorAll('.cn-section-header').forEach(header => {
    header.addEventListener('click', e => {
        if (e.target.closest('input, select, button, a')) return;
        header.closest('.cn-section').classList.toggle('collapsed');
    });
});

/* ── Validación ── */
function showToast(errors) {
    const toast = document.getElementById('cnToast');
    const list  = document.getElementById('cnToastList');
    list.innerHTML = errors.map(e => `<li>${e}</li>`).join('');
    toast.classList.add('show');
    clearTimeout(toast._timer);
    toast._timer = setTimeout(() => toast.classList.remove('show'), 5000);
}

function validateForm() {
    const errors = [];

    const titulo = document.getElementById('Titulo').value.trim();
    if (!titulo) errors.push('Título de la publicidad');

    const url = document.getElementById('url').value.trim();
    if (!url) {
        errors.push('URL de destino');
    } else {
        try { new URL(url); } catch (_) { errors.push('URL de destino no es válida (debe empezar con https://)'); }
    }

    const inicio = document.getElementById('fechaInicio').value;
    if (!inicio) errors.push('Fecha de inicio');

    const fin = document.getElementById('fechaFin').value;
    if (!fin) {
        errors.push('Fecha de fin');
    } else if (inicio && fin <= inicio) {
        errors.push('La fecha de fin debe ser posterior a la de inicio');
    }

    const imagen = document.getElementById('imagenCrop').value;
    if (!imagen) errors.push('Imagen (sube y confirma el recorte)');

    return errors;
}

document.getElementById('formPublicidad').addEventListener('submit', function(e) {
    const errors = validateForm();
    if (errors.length) {
        e.preventDefault();
        showToast(errors);
    }
});
</script>

<?php include("./../layout/footerAdmin.php"); ?>
