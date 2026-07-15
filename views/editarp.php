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
?>
<script>
    const ACL = <?= json_encode($ACL) ?>;
</script>
<div class="container">
    <h2>Editar Publicidad</h2>
    <div class="mt-3">
        <a href="./../views/publicidad.php" class="btn btn-secondary"><i class="bi bi-arrow-return-left"></i> Volver</a>
    </div>
    <form action="./../controllers/editar_publicidad.php" method="POST" enctype="multipart/form-data" novalidate>
        <div class="form-card card">
            <input type="hidden" name="id_pub" value="<?= $publicidad['id_pub'] ?>">
            
            <div class="form-group">
                <label for="Titulo" >Título</label>
                <input type="text" id="Titulo" name="Titulo" value="<?= htmlspecialchars($publicidad['titulo']) ?>" required>
            </div>
            <div class="form-group">
                <label for="tipo">Tipo de publicidad</label>
                <!-- Nota: En guardar_publicidad.php no veo que se guarde el 'tipo' en la BD explícitamente en el INSERT mostrado, 
                     pero estaba en el formulario. Asumiré que tal vez no se guardó o falta la columna. 
                     Si existe la columna en la BD, debería preseleccionarse. 
                     Por ahora dejaré el select genérico. -->
                <select id="tipo" name="tipo" required>
                    <?php if ($publicidad['tipo'] == 1): ?>
                        <option value="1" selected>Banner Publicitario</option>
                        <option value="2">Cuadro Publicitario</option>
                    <?php else: ?>
                        <option value="2" selected>Cuadro Publicitario</option>
                        <option value="1">Banner Publicitario</option>
                    <?php endif; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="imagen">Imagen Actual</label>
                <div>
                    <img src="<?= imageUrl($publicidad['imagen']) ?>" alt="Imagen Actual" style="max-width: 200px; margin-bottom: 10px;" loading="lazy" decoding="async">
                </div>
                <label for="imagen">Cambiar Imagen (Opcional)</label>
                <input type="file" id="imagen" name="imagen" accept="image/*">
                <input type="hidden" name="imagenCrop" id="imagenCrop" value="">
                
                <div id="previewContainer" style="display:none; margin-top: 20px;">
                    <h4>Vista previa:</h4>
                    <img id="previewImg" style="max-width: 100%; border: 1px solid #ccc;">
                </div>
            </div>
            <div class="form-group">
                <label for="url" >Url</label>
                <span>A donde va a redireccionar</span>
                <input type="text" id="url" name="url" value="<?= htmlspecialchars($publicidad['url']) ?>" required>
            </div>
            <div class="form-group">
                <label for="estado" >Estado</label>
                <span>Activo o Inactivo</span>
                <select id="estado" name="estado" required>
                    <option value="1" <?= $publicidad['activo'] == 1 ? 'selected' : '' ?>>Activo</option>
                    <option value="0" <?= $publicidad['activo'] == 0 ? 'selected' : '' ?>>Inactivo</option>
                </select>
            </div>
            <div class="form-group">
                <label for="Categorias">Categorías</label>
                <div class="checkbox-group">
                    <?php
                        foreach($categorias as $c):
                            $checked = in_array($c['id_c'], $categoriasSeleccionadas) ? 'checked' : '';
                    ?>
                        <label>
                            <input type="checkbox" name="Categorias[]" value="<?=$c['id_c']?>" <?= $checked ?>>
                            <?=$c['nombre']?>
                        </label>
                    <?php
                        endforeach;
                    ?>
                </div>
            </div>
            <div class="form-group">
                <label style="font-weight:600;display:block;margin-bottom:6px;">Fecha de Inicio</label>
                <div style="display: flex; gap: 6px; align-items: center; flex-wrap: wrap; margin-bottom: 15px;">
                    <select id="fechaInicio_dia" class="input" style="width: 70px; margin-bottom: 0;" required>
                        <option value="" disabled selected>Día</option>
                    </select>
                    <select id="fechaInicio_mes" class="input" style="width: 100px; margin-bottom: 0;" required>
                        <option value="" disabled selected>Mes</option>
                    </select>
                    <select id="fechaInicio_anio" class="input" style="width: 85px; margin-bottom: 0;" required>
                        <option value="" disabled selected>Año</option>
                    </select>
                    <span style="color: var(--muted); margin: 0 4px; font-size: 13px;">a las</span>
                    <select id="fechaInicio_hora" class="input" style="width: 70px; margin-bottom: 0;" required>
                        <option value="" disabled selected>Hora</option>
                    </select>
                    <span style="color: var(--muted)">:</span>
                    <select id="fechaInicio_min" class="input" style="width: 70px; margin-bottom: 0;" required>
                        <option value="" disabled selected>Min</option>
                    </select>
                </div>
                <input type="hidden" id="fechaInicio" name="fechaInicio">

                <label style="font-weight:600;display:block;margin-bottom:6px;">Fecha de Fin</label>
                <div style="display: flex; gap: 6px; align-items: center; flex-wrap: wrap;">
                    <select id="fechaFin_dia" class="input" style="width: 70px; margin-bottom: 0;" required>
                        <option value="" disabled selected>Día</option>
                    </select>
                    <select id="fechaFin_mes" class="input" style="width: 100px; margin-bottom: 0;" required>
                        <option value="" disabled selected>Mes</option>
                    </select>
                    <select id="fechaFin_anio" class="input" style="width: 85px; margin-bottom: 0;" required>
                        <option value="" disabled selected>Año</option>
                    </select>
                    <span style="color: var(--muted); margin: 0 4px; font-size: 13px;">a las</span>
                    <select id="fechaFin_hora" class="input" style="width: 70px; margin-bottom: 0;" required>
                        <option value="" disabled selected>Hora</option>
                    </select>
                    <span style="color: var(--muted)">:</span>
                    <select id="fechaFin_min" class="input" style="width: 70px; margin-bottom: 0;" required>
                        <option value="" disabled selected>Min</option>
                    </select>
                </div>
                <input type="hidden" id="fechaFin" name="fechaFin">
            </div>
            <div class="form-actions">
                <?php if ($ACL['editar']): ?>
                    <button type="submit" class="btn btn-accent" name="actualizarPublicidad">
                        Actualizar publicidad
                    </button>
                <?php endif; ?>
            </div>
        </div>
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
      <button type="button" class="btn btn-secondary" onclick="closeCrop()">Cancelar</button>
      <button type="button" class="btn btn-accent" onclick="confirmCrop()">
        <i class="bi bi-check-lg"></i> Confirmar recorte
      </button>
    </div>
  </div>
</div>

<script>
let cropper = null;

function getAspectRatio() {
    const tipo = document.getElementById('tipo').value;
    return tipo === '1' ? 16/9 : 1;
}

/* Mostrar modal de cropper al seleccionar archivo */
document.getElementById('imagen').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = function(event) {
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
        cropImg.src = event.target.result;
    };
    reader.readAsDataURL(file);
});

document.getElementById('tipo').addEventListener('change', function() {
    if (cropper) cropper.setAspectRatio(getAspectRatio());
});

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
