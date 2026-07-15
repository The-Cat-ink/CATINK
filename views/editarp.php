<?php
    include("./../layout/headerAdmin.php");
    include("./../controllers/aclcontroller.php");
    $ACL = $_SESSION['ACL']['publicidad'] ?? [
        "crear" => false,
        "leer" => false,
        "editar" => false,
        "eliminar" => false
    ];
    if (!$ACL['editar']) {
        header("Location: publicidad.php");
        exit;
    }
    proteger('publicidad','editar');
    include("./../data/conexion.php");
    if (!isset($_GET['id'])) {
        header("Location: publicidad.php");
        exit;
    }
    $id_pub = $_GET['id'];
    // Obtener datos de la publicidad
    $stmt = $con->prepare("SELECT * FROM publicidad WHERE id_pub = ?");
    $stmt->bind_param("i", $id_pub);
    $stmt->execute();
    $result = $stmt->get_result();
    $publicidad = $result->fetch_assoc();

    if (!$publicidad) {
        header("Location: publicidad.php");
        exit;
    }
    // Obtener categorías seleccionadas
    $stmtCat = $con->prepare("SELECT categoria_id FROM publicidad_categoria WHERE publicidad_id = ?");
    $stmtCat->bind_param("i", $id_pub);
    $stmtCat->execute();
    $resultCat = $stmtCat->get_result();
    $categoriasSeleccionadas = [];
    while ($row = $resultCat->fetch_assoc()) {
        $categoriasSeleccionadas[] = $row['categoria_id'];
    }
    // Obtener todas las categorías
    $categoriasResult = $con->query("SELECT id_c, nombre FROM categorias ORDER BY nombre ASC");
    $categorias = [];
    while($row = $categoriasResult->fetch_assoc()){
        $categorias[] = $row;
    }
    // Obtener posiciones (secciones) asignadas actualmente
    $stmtPos = $con->prepare("SELECT posicion FROM publicidad_posicion WHERE publicidad_id = ?");
    $stmtPos->bind_param("i", $id_pub);
    $stmtPos->execute();
    $resPos = $stmtPos->get_result();
    $posicionesSeleccionadas = [];
    while ($row = $resPos->fetch_assoc()) $posicionesSeleccionadas[] = $row['posicion'];
?>
<script>
    const ACL = <?= json_encode($ACL) ?>;
</script>
<style>
/* Estilos .cn-* del formulario de publicidad están en CSS/admin.css
   (compartidos con crearp.php). Aquí solo el override page-specific. */
.admin-container { max-width: none !important; padding: 0 !important; }
</style>

<div class="admin-container">

  <div class="cn-breadcrumb">
    <a href="publicidad.php">Publicidad</a>
    <i class="bi bi-chevron-right"></i>
    <span>Editar Publicidad</span>
  </div>

  <h1 class="cn-page-title">Editar Publicidad</h1>

  <form id="formPublicidad" action="./../controllers/editar_publicidad.php" method="POST" enctype="multipart/form-data" novalidate>
    <input type="hidden" name="id_pub" value="<?= $publicidad['id_pub'] ?>">
    <input type="hidden" name="imagenCrop" id="imagenCrop" value="">

    <div class="cn-wrap">

      <div class="cn-left-col">

        <!-- INFORMACIÓN BÁSICA -->
        <div class="cn-section" id="sec-info">
          <div class="cn-section-header">
            <div class="cn-section-icon"><i class="bi bi-info-circle"></i></div>
            <div><p class="cn-section-title">Información Básica</p></div>
            <i class="bi bi-chevron-down cn-section-toggle"></i>
          </div>
          <div class="cn-section-body">
            <div class="cn-field">
              <label for="Titulo">Título</label>
              <input class="cn-input" type="text" id="Titulo" name="Titulo" value="<?= htmlspecialchars($publicidad['titulo']) ?>" placeholder="Nombre de la publicidad..." required>
            </div>
            <div class="cn-field">
              <label for="url">URL de destino</label>
              <p class="cn-hint-text">A dónde va a redirigir al hacer clic</p>
              <input class="cn-input" type="text" id="url" name="url" value="<?= htmlspecialchars($publicidad['url']) ?>" placeholder="https://..." required>
            </div>
          </div>
        </div>

        <!-- CONFIGURACIÓN -->
        <div class="cn-section" id="sec-config">
          <div class="cn-section-header">
            <div class="cn-section-icon"><i class="bi bi-sliders"></i></div>
            <div><p class="cn-section-title">Configuración</p></div>
            <i class="bi bi-chevron-down cn-section-toggle"></i>
          </div>
          <div class="cn-section-body">
            <div class="cn-field">
              <label for="tipo">Tipo de publicidad</label>
              <select class="cn-input" id="tipo" name="tipo" required>
                <option value="1" <?= $publicidad['tipo'] == 1 ? 'selected' : '' ?>>Banner Publicitario (4:1)</option>
                <option value="2" <?= $publicidad['tipo'] == 2 ? 'selected' : '' ?>>Cuadro Publicitario (1:1)</option>
              </select>
            </div>
            <div class="cn-field">
              <label for="estado">Estado</label>
              <select class="cn-input" id="estado" name="estado" required>
                <option value="1" <?= $publicidad['activo'] == 1 ? 'selected' : '' ?>>Activo</option>
                <option value="0" <?= $publicidad['activo'] == 0 ? 'selected' : '' ?>>Inactivo</option>
              </select>
            </div>
          </div>
        </div>

        <!-- CATEGORÍAS -->
        <div class="cn-section" id="sec-cats">
          <div class="cn-section-header">
            <div class="cn-section-icon"><i class="bi bi-tag"></i></div>
            <div><p class="cn-section-title">Categorías</p></div>
            <i class="bi bi-chevron-down cn-section-toggle"></i>
          </div>
          <div class="cn-section-body">
            <div class="cn-cat-grid">
              <?php foreach($categorias as $c): $checked = in_array($c['id_c'], $categoriasSeleccionadas) ? 'checked' : ''; ?>
              <label class="cn-cat-check-item">
                <input type="checkbox" name="Categorias[]" value="<?= $c['id_c'] ?>" <?= $checked ?>>
                <?= htmlspecialchars($c['nombre']) ?>
              </label>
              <?php endforeach; ?>
            </div>
          </div>
        </div>

        <!-- POSICIONES / SECCIONES -->
        <div class="cn-section" id="sec-posiciones">
          <div class="cn-section-header">
            <div class="cn-section-icon"><i class="bi bi-grid-3x3-gap"></i></div>
            <div>
              <p class="cn-section-title">Secciones donde mostrar</p>
              <p class="cn-section-sub">Si no marcas ninguna, se muestra aleatoriamente en todos los huecos de su forma</p>
            </div>
            <i class="bi bi-chevron-down cn-section-toggle"></i>
          </div>
          <div class="cn-section-body">
            <?php $posGrupos = posicionesPublicidad(); ?>
            <?php foreach($posGrupos as $forma => $posiciones): ?>
              <div class="cn-pos-group" data-forma="<?= $forma ?>"<?= $forma === 'cuadrado' ? ' style="display:none;"' : '' ?>>
                <div class="cn-cat-grid">
                  <?php foreach($posiciones as $key => $label): $cp = in_array($key, $posicionesSeleccionadas) ? 'checked' : ''; ?>
                  <label class="cn-cat-check-item">
                    <input type="checkbox" name="posiciones[]" value="<?= $key ?>" <?= $cp ?>>
                    <?= htmlspecialchars($label) ?>
                  </label>
                  <?php endforeach; ?>
                </div>
              </div>
            <?php endforeach; ?>
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
            <div class="cn-field">
              <label style="font-size:12px;font-weight:600;color:var(--text);display:block;margin-bottom:6px;">Fecha de Inicio</label>
              <div style="display: flex; gap: 6px; align-items: center; flex-wrap: wrap;">
                <select id="fechaInicio_dia" class="cn-input" style="width: 70px; padding: 9px 6px;" required><option value="" disabled selected>Día</option></select>
                <select id="fechaInicio_mes" class="cn-input" style="width: 100px; padding: 9px 6px;" required><option value="" disabled selected>Mes</option></select>
                <select id="fechaInicio_anio" class="cn-input" style="width: 85px; padding: 9px 6px;" required><option value="" disabled selected>Año</option></select>
                <span style="color: var(--muted); margin: 0 4px; font-size: 13px;">a las</span>
                <select id="fechaInicio_hora" class="cn-input" style="width: 70px; padding: 9px 6px;" required><option value="" disabled selected>Hora</option></select>
                <span style="color: var(--muted)">:</span>
                <select id="fechaInicio_min" class="cn-input" style="width: 70px; padding: 9px 6px;" required><option value="" disabled selected>Min</option></select>
              </div>
              <input type="hidden" id="fechaInicio" name="fechaInicio">
            </div>

            <div class="cn-field" style="margin-top: 18px;">
              <label style="font-size:12px;font-weight:600;color:var(--text);display:block;margin-bottom:6px;">Fecha de Fin</label>
              <div style="display: flex; gap: 6px; align-items: center; flex-wrap: wrap;">
                <select id="fechaFin_dia" class="cn-input" style="width: 70px; padding: 9px 6px;" required><option value="" disabled selected>Día</option></select>
                <select id="fechaFin_mes" class="cn-input" style="width: 100px; padding: 9px 6px;" required><option value="" disabled selected>Mes</option></select>
                <select id="fechaFin_anio" class="cn-input" style="width: 85px; padding: 9px 6px;" required><option value="" disabled selected>Año</option></select>
                <span style="color: var(--muted); margin: 0 4px; font-size: 13px;">a las</span>
                <select id="fechaFin_hora" class="cn-input" style="width: 70px; padding: 9px 6px;" required><option value="" disabled selected>Hora</option></select>
                <span style="color: var(--muted)">:</span>
                <select id="fechaFin_min" class="cn-input" style="width: 70px; padding: 9px 6px;" required><option value="" disabled selected>Min</option></select>
              </div>
              <input type="hidden" id="fechaFin" name="fechaFin">
            </div>
          </div>
        </div>

        <!-- BOTÓN ACTUALIZAR -->
        <?php if ($ACL['editar']): ?>
        <div style="margin-top:4px;">
          <button type="submit" class="cn-publish-btn" name="actualizarPublicidad">
            <i class="bi bi-check-lg"></i> Actualizar publicidad
          </button>
        </div>
        <?php endif; ?>

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

          <!-- Imagen actual -->
          <p class="cn-zone-label">Imagen actual</p>
          <img src="<?= imageUrl($publicidad['imagen']) ?>" alt="Imagen actual" class="cp-preview-img" style="margin-bottom:16px;" loading="lazy" decoding="async">

          <!-- Zona de subida (cambiar) -->
          <p class="cn-zone-label">Cambiar imagen (opcional)</p>
          <div class="upload-zone" id="uploadZone" onclick="document.getElementById('imagen').click()">
            <i class="bi bi-cloud-arrow-up cn-zone-icon"></i>
            <span>Haz clic para seleccionar imagen</span>
            <span style="font-size:11px">PNG, JPG, WEBP</span>
          </div>
          <input type="file" id="imagen" name="imagen" accept="image/*" style="display:none;">

          <!-- Vista previa post-crop -->
          <div class="cp-preview-wrap" id="previewContainer">
            <p class="cp-preview-label">Vista previa (nueva imagen)</p>
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

<!-- ── CROP MODAL (REUTILIZADO DE CREAR NOTICIA) ── -->
<div class="crop-modal-overlay" id="cropModal">
  <div class="crop-modal-box">
    <div class="crop-modal-head">
      <i class="bi bi-crop"></i>
      <span id="cropModalTitle">Recortar imagen</span>
      <button type="button" onclick="closeCrop()">✕</button>
    </div>
    <div class="crop-modal-body">
      <div class="crop-area"><img id="cropImg" src=""></div>
    </div>
    <div class="crop-modal-foot">
      <button type="button" class="cn-btn-secondary" onclick="closeCrop()">Cancelar</button>
      <button type="button" class="cn-btn-accent" onclick="confirmCrop()">
        <i class="bi bi-check-lg"></i> Confirmar recorte
      </button>
    </div>
  </div>
</div>

<script>
let cropper = null;
let originalImageData = null; // imagen original (sin recortar) para re-recortar al cambiar de tipo

function getAspectRatio() {
    const tipo = document.getElementById('tipo').value;
    // Banner largo: recorte alargado tipo leaderboard (4:1). Cuadrado: 1:1.
    return tipo === '1' ? 4/1 : 1;
}

/* Abre el recortador con una imagen (dataURL) y la proporción del tipo actual */
function openCropper(dataURL) {
    const cropImg = document.getElementById('cropImg');
    const cropArea = document.querySelector('.crop-area');
    if (cropper) { cropper.destroy(); cropper = null; }

    cropImg.src = '';
    document.getElementById('cropModal').classList.add('open');

    cropImg.onload = function() {
        const maxW = cropArea.clientWidth || 640;
        const imgRatio = cropImg.naturalWidth / cropImg.naturalHeight;
        cropArea.style.height = Math.min(Math.round(maxW / imgRatio), 480) + 'px';

        cropper = new Cropper(cropImg, {
            aspectRatio: getAspectRatio(),
            viewMode: 1,
            autoCropArea: 0.98,
            movable: false,
            zoomable: false,
            cropBoxResizable: true,
            dragMode: 'move',
            responsive: true,
            guides: true,
            background: false,
        });
    };
    cropImg.src = dataURL;
}

/* Mostrar modal de cropper al seleccionar archivo */
document.getElementById('imagen').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = function(event) {
        originalImageData = event.target.result;
        openCropper(originalImageData);
    };
    reader.readAsDataURL(file);
});

document.getElementById('tipo').addEventListener('change', function() {
    updatePosGroups();
    if (cropper) {
        // Recortador abierto: basta con cambiar la proporción
        cropper.setAspectRatio(getAspectRatio());
    } else if (originalImageData) {
        // Ya había una imagen recortada: reabrir para recortar con la nueva forma
        openCropper(originalImageData);
    }
});

// Muestra solo las posiciones que coinciden con la forma del tipo elegido.
function updatePosGroups() {
    const forma = document.getElementById('tipo').value === '2' ? 'cuadrado' : 'largo';
    document.querySelectorAll('.cn-pos-group').forEach(g => {
        const match = g.dataset.forma === forma;
        g.style.display = match ? '' : 'none';
        if (!match) g.querySelectorAll('input[type=checkbox]').forEach(cb => cb.checked = false);
    });
}
updatePosGroups();

function confirmCrop() {
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

    const uploadZone = document.getElementById('uploadZone');
    if (uploadZone) {
        uploadZone.innerHTML = `<img src="${data64}" style="max-height:150px; border-radius:6px; margin-bottom:5px;"><br><span>Cambiar imagen</span>`;
        uploadZone.classList.add('has-image');
    }

    closeCrop();
}

function closeCrop() {
    document.getElementById('cropModal').classList.remove('open');
    if (cropper) { cropper.destroy(); cropper = null; }
    document.getElementById('cropImg').src = '';
    document.querySelector('.crop-area').style.height = '';
    document.getElementById('imagen').value = '';
}

// ---- FECHA Y HORA DROPDOWNS ----
function initDateTimeDropdowns(prefix, initialValue) {
  const diaSel = document.getElementById(prefix + '_dia');
  const mesSel = document.getElementById(prefix + '_mes');
  const anioSel = document.getElementById(prefix + '_anio');
  const horaSel = document.getElementById(prefix + '_hora');
  const minSel = document.getElementById(prefix + '_min');
  const hiddenInput = document.getElementById(prefix);

  if (!diaSel || !mesSel || !anioSel || !horaSel || !minSel || !hiddenInput) return;

  // Populate days
  for (let d = 1; d <= 31; d++) {
    const opt = document.createElement('option');
    const val = String(d).padStart(2, '0');
    opt.value = val;
    opt.textContent = val;
    diaSel.appendChild(opt);
  }

  // Populate months
  const meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
  meses.forEach((m, idx) => {
    const opt = document.createElement('option');
    const val = String(idx + 1).padStart(2, '0');
    opt.value = val;
    opt.textContent = m;
    mesSel.appendChild(opt);
  });

  // Populate years
  const currentYear = new Date().getFullYear();
  let startYear = currentYear;
  let endYear = currentYear + 10;
  if (initialValue) {
    const parts = initialValue.split(/[T ]/);
    if (parts.length >= 1) {
      const dateParts = parts[0].split('-');
      if (dateParts.length === 3) {
        const initYear = parseInt(dateParts[0]);
        if (!isNaN(initYear)) {
          startYear = Math.min(initYear, currentYear);
          endYear = Math.max(initYear + 5, currentYear + 10);
        }
      }
    }
  }

  for (let y = startYear; y <= endYear; y++) {
    const opt = document.createElement('option');
    opt.value = y;
    opt.textContent = y;
    anioSel.appendChild(opt);
  }

  // Populate hours
  for (let h = 0; h < 24; h++) {
    const opt = document.createElement('option');
    const val = String(h).padStart(2, '0');
    opt.value = val;
    opt.textContent = val;
    horaSel.appendChild(opt);
  }

  // Populate minutes
  for (let m = 0; m < 60; m++) {
    const opt = document.createElement('option');
    const val = String(m).padStart(2, '0');
    opt.value = val;
    opt.textContent = val;
    minSel.appendChild(opt);
  }

  function updateHiddenDateTime() {
    const dia = diaSel.value;
    const mes = mesSel.value;
    const anio = anioSel.value;
    const hora = horaSel.value;
    const min = minSel.value;
    if (dia && mes && anio && hora && min) {
      hiddenInput.value = `${anio}-${mes}-${dia} ${hora}:${min}:00`;
    } else {
      hiddenInput.value = '';
    }
  }

  diaSel.addEventListener('change', updateHiddenDateTime);
  mesSel.addEventListener('change', updateHiddenDateTime);
  anioSel.addEventListener('change', updateHiddenDateTime);
  horaSel.addEventListener('change', updateHiddenDateTime);
  minSel.addEventListener('change', updateHiddenDateTime);

  // Set initial value
  if (initialValue) {
    const parts = initialValue.split(/[T ]/);
    if (parts.length >= 1) {
      const dateParts = parts[0].split('-');
      if (dateParts.length === 3) {
        anioSel.value = dateParts[0];
        mesSel.value = dateParts[1];
        diaSel.value = dateParts[2];
      }
    }
    if (parts.length >= 2) {
      const timeParts = parts[1].split(':');
      if (timeParts.length >= 2) {
        horaSel.value = timeParts[0];
        minSel.value = timeParts[1];
      }
    }
    updateHiddenDateTime();
  }
}

// Inicializar dropdowns con las fechas guardadas
function initEditarpFechas() {
  const initInicio = '<?= $publicidad['fecha_inicio'] ?>';
  const initFin = '<?= $publicidad['fecha_fin'] ?>';
  initDateTimeDropdowns('fechaInicio', initInicio);
  initDateTimeDropdowns('fechaFin', initFin);
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initEditarpFechas);
} else {
  initEditarpFechas();
}
document.addEventListener('turbo:load', initEditarpFechas);

// Forzar actualización de campos ocultos de fecha antes de enviar el formulario
document.querySelector('form[action*="editar_publicidad"]').addEventListener('submit', function() {
    ['fechaInicio', 'fechaFin'].forEach(prefix => {
        const dia  = document.getElementById(prefix + '_dia')?.value;
        const mes  = document.getElementById(prefix + '_mes')?.value;
        const anio = document.getElementById(prefix + '_anio')?.value;
        const hora = document.getElementById(prefix + '_hora')?.value;
        const min  = document.getElementById(prefix + '_min')?.value;
        if (dia && mes && anio && hora && min) {
            document.getElementById(prefix).value = `${anio}-${mes}-${dia} ${hora}:${min}:00`;
        }
    });
});
</script>
<?php
    include("./../layout/footerAdmin.php");
?>
