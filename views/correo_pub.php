<?php
include('./../layout/headerAdmin.php');
include('./../controllers/aclcontroller.php');
$ACl = $_SESSION['ACL']['publicidad']??[
    "crear" => false,
    "leer" => false,
    "editar" => false,
    "eliminar" => false
];
if(!$ACl['crear']) {
    header("Location: admin.php");
    exit();
}
proteger('publicidad','crear');
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

/* ── FECHAS ── */
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

/* ── BOTÓN PUBLICAR ── */
.cn-publish-btn {
  width: 100%; padding: 13px; background: var(--accent); color: #fff;
  border: none; border-radius: 10px; font-size: 15px; font-weight: 700;
  cursor: pointer; display: flex; align-items: center; justify-content: center;
  gap: 8px; transition: background .2s, transform .15s; font-family: inherit;
}
.cn-publish-btn:hover { background: #d42a55; transform: translateY(-2px); }
.cn-publish-btn:active { transform: translateY(0); }

/* ── PREVIEW ── */
#preview img { max-width: 100%; border-radius: 8px; margin-top: 10px; display: block; border: 1px solid var(--border); }
</style>

<div class="admin-container">
  <div class="cn-breadcrumb">
    <a href="correos.php">Correos Publicitarios</a>
    <i class="bi bi-chevron-right"></i>
    <span>Nuevo Correo</span>
  </div>

  <h1 class="cn-page-title">Alta de correo publicitario</h1>

  <form action="./../controllers/crearCorreoPub.php" method="POST" enctype="multipart/form-data">
    <div class="cn-wrap">

      <div class="cn-left-col">
        <!-- INFORMACIÓN BÁSICA -->
        <div class="cn-section" id="sec-info">
          <div class="cn-section-header">
            <div class="cn-section-icon"><i class="bi bi-envelope-paper"></i></div>
            <div>
              <p class="cn-section-title">Contenido del Correo</p>
            </div>
            <i class="bi bi-chevron-down cn-section-toggle"></i>
          </div>
          <div class="cn-section-body">
            <div class="cn-field">
              <label for="titulo">Asunto / Título</label>
              <input class="cn-input" type="text" id="titulo" name="titulo" placeholder="Asunto del correo..." required>
            </div>
            <div class="cn-field">
              <label for="contenido">Mensaje</label>
              <textarea class="cn-input" id="contenido" name="contenido" rows="5" placeholder="Escribe el cuerpo del correo aquí..." required></textarea>
            </div>
            <div class="cn-field">
              <label for="url">URL de destino (Opcional)</label>
              <p class="cn-hint-text">Si quieres que hagan clic en la imagen o un botón</p>
              <input class="cn-input" type="url" id="url" name="url" placeholder="https://...">
            </div>
          </div>
        </div>

        <!-- PROGRAMACIÓN -->
        <div class="cn-section" id="sec-vigencia">
          <div class="cn-section-header">
            <div class="cn-section-icon"><i class="bi bi-clock-history"></i></div>
            <div>
              <p class="cn-section-title">Programar Envío</p>
              <p class="cn-section-sub">¿Cuándo se enviará este correo?</p>
            </div>
            <i class="bi bi-chevron-down cn-section-toggle"></i>
          </div>
          <div class="cn-section-body">
            <div class="cn-field">
              <label style="font-size:12px;font-weight:600;color:var(--muted);display:block;margin-bottom:6px;">Fecha y hora de envío</label>
              <div class="cn-date-input">
                <i class="bi bi-calendar-event"></i>
                <input type="datetime-local" id="envio" name="envio" required>
              </div>
            </div>
          </div>
        </div>

        <!-- BOTÓN -->
        <div style="margin-top:4px;">
          <button type="submit" class="cn-publish-btn">
            <i class="bi bi-send-check"></i> Guardar Correo
          </button>
        </div>

      </div><!-- /cn-left-col -->

      <!-- MULTIMEDIA -->
      <div class="cn-section" id="sec-media">
        <div class="cn-section-header">
          <div class="cn-section-icon"><i class="bi bi-image"></i></div>
          <div>
            <p class="cn-section-title">Imagen Adjunta</p>
            <p class="cn-section-sub">Se mostrará en el cuerpo del correo</p>
          </div>
          <i class="bi bi-chevron-down cn-section-toggle"></i>
        </div>
        <div class="cn-section-body">
          <p class="cn-zone-label">Banner publicitario</p>
          <div class="upload-zone" id="uploadZone" onclick="document.getElementById('imagenCorreo').click()">
            <i class="bi bi-cloud-arrow-up cn-zone-icon"></i>
            <span>Haz clic para seleccionar imagen</span>
            <span style="font-size:11px">Recomendado: 600px de ancho</span>
          </div>
          <input type="file" id="imagenCorreo" name="imagenCorreo" accept="image/*" required style="display:none;">
          
          <!-- Contenedor para vista previa directa -->
          <div id="preview" style="display:none; text-align:center;">
             <p class="cn-zone-label" style="margin-top:15px; margin-bottom:5px;">Vista Previa</p>
             <img id="imgPreview" src="" alt="Vista Previa" style="max-width:100%; border-radius:8px; border:1px solid var(--border);">
             <button type="button" class="cn-btn-secondary" style="margin-top:10px;width:100%; padding:8px; border-radius:8px; background:var(--bg); border:1px solid var(--border); color:var(--text); cursor:pointer;"
                onclick="document.getElementById('imagenCorreo').click()">
                <i class="bi bi-arrow-repeat"></i> Cambiar imagen
             </button>
          </div>
        </div>
      </div><!-- /sec-media -->

    </div><!-- /cn-wrap -->
  </form>

</div>

<script>
// Collapse de secciones
document.querySelectorAll('.cn-section-header').forEach(header => {
    header.addEventListener('click', e => {
        if (e.target.closest('input, select, button, a')) return;
        header.closest('.cn-section').classList.toggle('collapsed');
    });
});

// Preview de imagen
document.getElementById('imagenCorreo').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('imgPreview').src = e.target.result;
            document.getElementById('preview').style.display = 'block';
            document.getElementById('uploadZone').style.display = 'none';
        }
        reader.readAsDataURL(file);
    }
});
</script>

<?php include('./../layout/footerAdmin.php'); ?>
