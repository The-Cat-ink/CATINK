<?php
include('./../layout/headerAdmin.php');
include('./../controllers/aclcontroller.php');
$ACL = $_SESSION['ACL']['correos']??[
    "crear" => false,
    "leer" => false,
    "editar" => false,
    "eliminar" => false
];
if(!$ACL['crear']) {
    header("Location: admin.php");
    exit();
}
proteger('correos','crear', false);
?>

<style>
/* ── SPLIT-SCREEN STUDIO LAYOUT ── */
.cn-breadcrumb {
  display: flex; align-items: center; gap: 6px;
  font-size: 13px; color: var(--muted); margin-bottom: 16px;
}
.cn-breadcrumb a { color: var(--muted); text-decoration: none; }
.cn-breadcrumb a:hover { color: var(--accent); }
.cn-breadcrumb span { color: var(--accent); }
.cn-page-title { font-size: 1.5rem; font-weight: 800; margin: 0 0 20px; color: var(--text); }

.studio-grid {
  display: grid;
  grid-template-columns: minmax(0, 520px) minmax(0, 1fr);
  gap: 20px;
  align-items: start;
}
@media (max-width: 1080px) {
  .studio-grid { grid-template-columns: 1fr; }
}

/* ── FORMULARIO Y SECCIONES ── */
.cn-section {
  background: var(--card-bg); border: 1px solid var(--border);
  border-radius: 16px; overflow: hidden; margin-bottom: 16px;
  transition: box-shadow .2s;
}
.cn-section-header {
  display: flex; align-items: center; gap: 10px;
  padding: 14px 18px; cursor: pointer; user-select: none;
  border-bottom: 1px solid var(--border); transition: background .15s;
}
.cn-section-header:hover { background: rgba(239,51,99,.04); }
.cn-section-icon {
  width: 32px; height: 32px; border-radius: 8px;
  background: rgba(239,51,99,0.12); color: var(--accent);
  display: flex; align-items: center; justify-content: center;
  font-size: 15px; flex-shrink: 0;
}
.cn-section-title { font-size: 0.92rem; font-weight: 700; color: var(--text); margin: 0; }
.cn-section-sub   { font-size: 11px; color: var(--muted); margin: 0; }
.cn-section-body { padding: 18px; }

.cn-field { margin-bottom: 14px; }
.cn-field:last-child { margin-bottom: 0; }
.cn-field label { display: block; font-size: 12px; font-weight: 700; color: var(--text); margin-bottom: 5px; text-transform: uppercase; letter-spacing: 0.03em; }
.cn-field .cn-hint-text { font-size: 11px; color: var(--muted); margin-top: 3px; }
.cn-input {
  width: 100%; padding: 10px 12px; border-radius: 10px;
  border: 1px solid var(--border); background: var(--bg); color: var(--text);
  font-size: 13.5px; transition: border-color .2s; font-family: inherit;
}
.cn-input:focus { outline: none; border-color: var(--accent); }

/* Presets */
.preset-btn {
  background: var(--bg); border: 1px solid var(--border); color: var(--text);
  padding: 6px 12px; border-radius: 8px; font-size: 11.5px; font-weight: 700;
  cursor: pointer; transition: all 0.2s ease; display: inline-flex; align-items: center; gap: 4px;
}
.preset-btn:hover { border-color: var(--accent); color: var(--accent); }

/* Date Input */
.cn-date-input {
  display: flex; align-items: center; gap: 8px;
  padding: 9px 12px; border: 1px solid var(--border);
  border-radius: 10px; background: var(--bg); color: var(--text); font-size: 13px;
}
.cn-date-input i { color: var(--muted); font-size: 15px; }
.cn-date-input input[type="datetime-local"] {
  border: none; background: none; color: var(--text);
  font-size: 13px; padding: 0; outline: none; width: 100%; font-family: inherit;
}

/* Upload zone */
.upload-zone {
  border: 2px dashed var(--border); border-radius: 10px;
  background: var(--bg); display: flex; flex-direction: column;
  align-items: center; justify-content: center; gap: 5px;
  cursor: pointer; color: var(--muted); font-size: 0.82rem; text-align: center;
  padding: 20px 12px; transition: border-color .2s, background .2s;
}
.upload-zone:hover { border-color: var(--accent); background: rgba(239,51,99,.04); }

.cn-publish-btn {
  width: 100%; padding: 14px; background: var(--accent); color: #fff;
  border: none; border-radius: 12px; font-size: 15px; font-weight: 800;
  cursor: pointer; display: flex; align-items: center; justify-content: center;
  gap: 8px; transition: background .2s, transform .15s; box-shadow: 0 6px 20px rgba(239, 51, 99, 0.35);
}
.cn-publish-btn:hover { background: #d42a55; transform: translateY(-2px); }

/* ── PREVISUALIZADOR EN VIVO (RIGHT COL) ── */
.preview-card {
  position: sticky; top: 80px;
  background: var(--card-bg); border: 1px solid var(--border);
  border-radius: 18px; overflow: hidden;
  box-shadow: 0 10px 30px rgba(0,0,0,0.15);
  display: flex; flex-direction: column;
  height: calc(100vh - 100px); min-height: 560px;
}
.preview-toolbar {
  padding: 12px 18px; background: rgba(0,0,0,0.04);
  border-bottom: 1px solid var(--border);
  display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;
}
[data-bs-theme="dark"] .preview-toolbar { background: rgba(255,255,255,0.03); }
.preview-title { font-size: 0.88rem; font-weight: 800; color: var(--text); margin: 0; display: flex; align-items: center; gap: 8px; }
.preview-controls { display: flex; align-items: center; gap: 8px; }

.device-btn {
  background: var(--bg); border: 1px solid var(--border); color: var(--text);
  padding: 5px 10px; border-radius: 8px; font-size: 12px; font-weight: 700; cursor: pointer;
}
.device-btn.active { border-color: var(--accent); color: var(--accent); background: rgba(239,51,99,0.08); }

.preview-body {
  flex: 1; background: #070b13; padding: 20px;
  display: flex; justify-content: center; align-items: flex-start;
  overflow-y: auto; position: relative;
}
.preview-iframe-wrapper {
  width: 100%; max-width: 600px; height: 100%; min-height: 480px;
  transition: max-width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  display: flex; justify-content: center;
}
.preview-iframe-wrapper.is-mobile {
  max-width: 380px;
}
.preview-iframe {
  width: 100%; height: 100%; min-height: 480px; border: none; border-radius: 12px;
  background: #ffffff; box-shadow: 0 12px 40px rgba(0,0,0,0.4);
}
</style>

<div class="admin-container">
  <div class="cn-breadcrumb">
    <a href="correos.php">Correos Publicitarios</a>
    <i class="bi bi-chevron-right"></i>
    <span>Estudio de Plantillas</span>
  </div>

  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
      <h1 class="cn-page-title mb-1">Nuevo Correo Publicitario</h1>
      <p class="text-muted m-0" style="font-size:0.85rem;">Diseña y previsualiza en tiempo real el resultado final en correo electrónico.</p>
    </div>
    
    <!-- Presets Rápidos -->
    <div class="d-flex gap-1 flex-wrap">
      <button type="button" class="preset-btn" onclick="applyPreset('promocional')"><i class="bi bi-megaphone-fill"></i> Promocional</button>
      <button type="button" class="preset-btn" onclick="applyPreset('boletin')"><i class="bi bi-newspaper"></i> Boletín</button>
      <button type="button" class="preset-btn" onclick="applyPreset('oferta')"><i class="bi bi-percent"></i> Oferta</button>
      <button type="button" class="preset-btn" onclick="applyPreset('aviso')"><i class="bi bi-bell-fill"></i> Aviso</button>
    </div>
  </div>

  <form action="./../controllers/crearCorreoPub.php" method="POST" enctype="multipart/form-data" id="correoForm">
    <div class="studio-grid">

      <!-- COLUMNA IZQUIERDA: FORMULARIO DE EDICIÓN -->
      <div class="cn-left-col">

        <!-- CONFIGURACIÓN DE CABECERA -->
        <div class="cn-section">
          <div class="cn-section-header">
            <div class="cn-section-icon"><i class="bi bi-sliders"></i></div>
            <div>
              <p class="cn-section-title">Encabezado y Estilo</p>
              <p class="cn-section-sub">Asunto, distintivo y plantilla de color</p>
            </div>
          </div>
          <div class="cn-section-body">
            <div class="cn-field">
              <label for="titulo">Asunto del Correo *</label>
              <input class="cn-input" type="text" id="titulo" name="titulo" placeholder="Ej: ¡Descuento exclusivo de aniversario!" required>
            </div>
            
            <div class="row g-2">
              <div class="col-6">
                <div class="cn-field">
                  <label for="badge">Distintivo (Badge)</label>
                  <input class="cn-input" type="text" id="badge" name="badge" value="Anuncio / Promoción" placeholder="Ej: Promoción Especial">
                </div>
              </div>
              <div class="col-6">
                <div class="cn-field">
                  <label for="theme">Tema de Color</label>
                  <select class="cn-input" id="theme" name="theme">
                    <option value="light" selected>☀️ Tema Claro (Light)</option>
                    <option value="dark">🌙 Tema Oscuro (Dark)</option>
                  </select>
                </div>
              </div>
            </div>

            <div class="cn-field">
              <label for="preheader">Texto Pre-encabezado (Opcional)</label>
              <input class="cn-input" type="text" id="preheader" name="preheader" placeholder="Breve resumen visible en la bandeja de entrada...">
            </div>
          </div>
        </div>

        <!-- CUERPO Y MENSAJE -->
        <div class="cn-section">
          <div class="cn-section-header">
            <div class="cn-section-icon"><i class="bi bi-envelope-paper"></i></div>
            <div>
              <p class="cn-section-title">Cuerpo del Mensaje</p>
              <p class="cn-section-sub">Texto principal del correo</p>
            </div>
          </div>
          <div class="cn-section-body">
            <div class="cn-field">
              <label for="contenido">Mensaje Principal *</label>
              <textarea class="cn-input" id="contenido" name="contenido" rows="6" placeholder="Escribe el cuerpo de tu correo aquí..." required></textarea>
            </div>

            <div class="row g-2">
              <div class="col-6">
                <div class="cn-field">
                  <label for="cta_text">Texto del Botón CTA</label>
                  <input class="cn-input" type="text" id="cta_text" name="cta_text" value="Ver promoción" placeholder="Ej: Ver oferta">
                </div>
              </div>
              <div class="col-6">
                <div class="cn-field">
                  <label for="url">URL de Destino</label>
                  <input class="cn-input" type="url" id="url" name="url" placeholder="https://catink.com.mx/...">
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- ADJUNTO Y PROGRAMACIÓN -->
        <div class="cn-section">
          <div class="cn-section-header">
            <div class="cn-section-icon"><i class="bi bi-image"></i></div>
            <div>
              <p class="cn-section-title">Banner Publicitario y Programación</p>
            </div>
          </div>
          <div class="cn-section-body">
            <div class="cn-field mb-3">
              <label>Imagen Adjunta</label>
              <div class="upload-zone" id="uploadZone" onclick="document.getElementById('imagenCorreo').click()">
                <i class="bi bi-cloud-arrow-up fs-3"></i>
                <span style="font-weight:700;">Haz clic para seleccionar imagen banner</span>
                <span style="font-size:11px">Recomendado: 600px de ancho</span>
              </div>
              <input type="file" id="imagenCorreo" name="imagenCorreo" accept="image/*" style="position: absolute; opacity: 0; width: 1px; height: 1px; pointer-events: none;">
            </div>

            <div class="cn-field">
              <label for="envio">Fecha y Hora de Envío Programado *</label>
              <div class="cn-date-input">
                <i class="bi bi-calendar-event"></i>
                <input type="datetime-local" id="envio" name="envio" required>
              </div>
            </div>
          </div>
        </div>

        <!-- BOTÓN DE GUARDADO -->
        <div class="mb-4">
          <button type="submit" class="cn-publish-btn">
            <i class="bi bi-send-check-fill"></i> Guardar y Programar Correo
          </button>
        </div>

      </div><!-- /cn-left-col -->

      <!-- COLUMNA DERECHA: PREVISUALIZADOR EN TIEMPO REAL -->
      <div class="cn-right-col">
        <div class="preview-card">
          <div class="preview-toolbar">
            <div class="preview-title">
              <i class="bi bi-broadcast" style="color:var(--accent); font-size:1.1rem;"></i>
              <span>Vista Previa en Tiempo Real</span>
            </div>
            
            <div class="preview-controls">
              <button type="button" class="device-btn active" id="btnDeviceDesktop" onclick="setDevice('desktop')">
                <i class="bi bi-display me-1"></i> Desktop
              </button>
              <button type="button" class="device-btn" id="btnDeviceMobile" onclick="setDevice('mobile')">
                <i class="bi bi-phone me-1"></i> Móvil
              </button>
            </div>
          </div>

          <div class="preview-body">
            <div class="preview-iframe-wrapper" id="previewWrapper">
              <iframe class="preview-iframe" id="livePreviewIframe"></iframe>
            </div>
          </div>
        </div>
      </div><!-- /cn-right-col -->

    </div><!-- /studio-grid -->
  </form>
</div>

<script>
let currentImgBase64 = '';

// presets prediseñados
function applyPreset(type) {
    const titulo = document.getElementById('titulo');
    const badge = document.getElementById('badge');
    const contenido = document.getElementById('contenido');
    const cta_text = document.getElementById('cta_text');
    const url = document.getElementById('url');
    const theme = document.getElementById('theme');

    switch (type) {
        case 'promocional':
            titulo.value = '¡Gran Venta Especial de Aniversario CatInk!';
            badge.value = 'Anuncio Patrocinado';
            contenido.value = 'Querida comunidad geek, traemos para ti alianzas exclusivas con las mejores tiendas de cómics, manga y figuras coleccionables de México.\n\nAprovecha un 25% de descuento en tus compras usando el código CATINK2026.';
            cta_text.value = 'Ver Promoción Exclusiva';
            url.value = 'https://catink.com.mx';
            theme.value = 'light';
            break;
        case 'boletin':
            titulo.value = 'Resumen Informativo CatInk News';
            badge.value = 'Boletín Semanal';
            contenido.value = 'No te pierdas las novedades más relevantes de la semana en el mundo del anime, los videojuegos y el entretenimiento geek.\n\nHaz clic abajo para explorar las reseñas y análisis completos.';
            cta_text.value = 'Explorar Noticias';
            url.value = 'https://catink.com.mx';
            theme.value = 'light';
            break;
        case 'oferta':
            titulo.value = '⚡ ¡Descuento de Tiempo Limitado!';
            badge.value = 'Oferta Relámpago';
            contenido.value = 'Solo por las próximas 24 horas, accede a precios preferenciales en nuestras suscripciones y membresías especiales.\n\n¡No dejes pasar esta oportunidad única!';
            cta_text.value = 'Reclamar Descuento';
            url.value = 'https://catink.com.mx';
            theme.value = 'dark';
            break;
        case 'aviso':
            titulo.value = 'Aviso Importante para nuestra comunidad';
            badge.value = 'Comunicado Oficial';
            contenido.value = 'Queremos informarte sobre las próximas actualizaciones en nuestros términos de servicio y mejoras en la plataforma CatInk News.';
            cta_text.value = 'Leer Comunicado';
            url.value = 'https://catink.com.mx/terminos';
            theme.value = 'light';
            break;
    }
    triggerLivePreview();
}

// Establecer fecha por defecto (mañana misma hora)
const fechaEnvio = document.getElementById('envio');
if (fechaEnvio && !fechaEnvio.value) {
    const now = new Date();
    now.setHours(now.getHours() + 24);
    fechaEnvio.value = now.toISOString().slice(0, 16);
}

// Preview de imagen
document.getElementById('imagenCorreo').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(evt) {
            currentImgBase64 = evt.target.result;
            triggerLivePreview();
        };
        reader.readAsDataURL(file);
    }
});

// Controladores de dispositivo
function setDevice(mode) {
    const wrapper = document.getElementById('previewWrapper');
    const btnD = document.getElementById('btnDeviceDesktop');
    const btnM = document.getElementById('btnDeviceMobile');
    if (mode === 'mobile') {
        wrapper.classList.add('is-mobile');
        btnM.classList.add('active');
        btnD.classList.remove('active');
    } else {
        wrapper.classList.remove('is-mobile');
        btnD.classList.add('active');
        btnM.classList.remove('active');
    }
}

// Renderizado en vivo AJAX con Debounce
let debounceTimer;
function triggerLivePreview() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        const formData = new FormData();
        formData.append('titulo', document.getElementById('titulo').value || 'Asunto del Correo');
        formData.append('badge', document.getElementById('badge').value || '');
        formData.append('preheader', document.getElementById('preheader').value || '');
        formData.append('theme', document.getElementById('theme').value || 'light');
        formData.append('contenido', document.getElementById('contenido').value || 'Escribe el mensaje en el formulario...');
        formData.append('cta_text', document.getElementById('cta_text').value || '');
        formData.append('url', document.getElementById('url').value || '');
        formData.append('img_url', currentImgBase64);

        fetch('<?= basePath() ?>/controllers/preview_email_ajax.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.text())
        .then(html => {
            const iframe = document.getElementById('livePreviewIframe');
            if (iframe) {
                iframe.srcdoc = html;
            }
        })
        .catch(err => console.error("Error al actualizar la vista previa:", err));
    }, 50);
}

// Escuchar cambios en todos los inputs del formulario
document.querySelectorAll('#correoForm input, #correoForm textarea, #correoForm select').forEach(elem => {
    elem.addEventListener('input', triggerLivePreview);
    elem.addEventListener('change', triggerLivePreview);
});

// Disparar renderizado inicial inmediato de forma garantizada
triggerLivePreview();
document.addEventListener('DOMContentLoaded', triggerLivePreview);
document.addEventListener('turbo:load', triggerLivePreview);
window.addEventListener('load', triggerLivePreview);
</script>

<?php include('./../layout/footerAdmin.php'); ?>
