<?php
include("./../layout/header.php");
include("./../data/conexion.php");

// Obtener todas las vacantes activas ordenadas
$resVac = $con->query("SELECT * FROM vacantes_equipo WHERE estado = 1 ORDER BY orden ASC, id ASC");
$vacantes = $resVac ? $resVac->fetch_all(MYSQLI_ASSOC) : [];
$totalVacantes = count($vacantes);
?>

<style>
/* Estilos Principales de Únete al Equipo */
.unete-container {
  max-width: 1280px;
  margin: 0 auto;
  padding: 30px 20px 60px;
}

.unete-section-header {
  text-align: center;
  margin-bottom: 32px;
}

.unete-badge-header {
  display: inline-block;
  background: rgba(239, 51, 99, 0.12);
  color: var(--accent, #EF3363);
  padding: 5px 16px;
  border-radius: 20px;
  font-size: 0.8rem;
  font-weight: 800;
  letter-spacing: 1.5px;
  text-transform: uppercase;
  margin-bottom: 10px;
}

.unete-main-title {
  font-size: 2.6rem;
  font-weight: 900;
  color: var(--text, #111827);
  margin: 0 0 8px;
  letter-spacing: -0.5px;
}

.unete-sub-desc {
  color: var(--muted, #64748b);
  font-size: 1.05rem;
  margin: 0;
  font-weight: 500;
}

/* Acordeón Desktop */
.unete-accordion-desktop {
  display: flex;
  width: 100%;
  min-height: 520px;
  border: 1px solid var(--border, #e2e8f0);
  border-radius: 20px;
  overflow: hidden;
  background: var(--card-bg, #ffffff);
  box-shadow: 0 10px 30px rgba(0,0,0,0.05);
}

.unete-col {
  flex: 1;
  border-right: 1px solid var(--border, #e2e8f0);
  padding: 32px 28px;
  transition: flex 0.45s cubic-bezier(0.16, 1, 0.3, 1), background 0.3s ease;
  cursor: pointer;
  position: relative;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
}

.unete-col:last-child {
  border-right: none;
}

.unete-col.active {
  flex: 3.5;
  background: rgba(239, 51, 99, 0.03);
  cursor: default;
}

[data-bs-theme="dark"] .unete-col.active {
  background: rgba(239, 51, 99, 0.12);
}

/* Estado Colapsado (Texto Vertical) */
.unete-col-collapsed {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: space-between;
  height: 100%;
  opacity: 1;
  transition: opacity 0.3s ease;
}

.unete-col.active .unete-col-collapsed {
  display: none;
}

.unete-num-small {
  font-size: 0.9rem;
  font-weight: 900;
  color: var(--accent, #EF3363);
}

.unete-vertical-text {
  writing-mode: vertical-rl;
  transform: rotate(180deg);
  font-size: 1.2rem;
  font-weight: 900;
  letter-spacing: 3px;
  text-transform: uppercase;
  color: var(--muted, #64748b);
  white-space: nowrap;
}

/* Estado Expandido (Detalle Completo) */
.unete-col-expanded {
  display: none;
  flex-direction: column;
  justify-content: space-between;
  height: 100%;
  animation: fadeInContent 0.4s ease forwards;
}

.unete-col.active .unete-col-expanded {
  display: flex;
}

@keyframes fadeInContent {
  from { opacity: 0; transform: translateY(8px); }
  to { opacity: 1; transform: translateY(0); }
}

.unete-badge-tag {
  display: inline-block;
  background: rgba(239, 51, 99, 0.12);
  color: var(--accent, #EF3363);
  border: 1px solid rgba(239, 51, 99, 0.25);
  padding: 4px 14px;
  border-radius: 20px;
  font-size: 0.78rem;
  font-weight: 800;
  letter-spacing: 0.5px;
  margin-bottom: 20px;
}

.unete-role-title {
  font-size: 3.2rem;
  font-weight: 900;
  line-height: 1;
  margin: 0 0 8px;
  color: var(--text, #111827);
  letter-spacing: -1px;
}

.unete-role-italic {
  font-size: 1.25rem;
  font-style: italic;
  color: var(--accent, #EF3363);
  margin-bottom: 20px;
  display: block;
  font-weight: 600;
}

.unete-role-desc {
  font-size: 1.02rem;
  line-height: 1.6;
  color: var(--muted, #475569);
  max-width: 480px;
  margin-bottom: 30px;
}

.btn-postularme-action {
  background: var(--card-bg, #ffffff);
  color: var(--text, #111827);
  border: 2px solid var(--border, #cbd5e1);
  padding: 14px 28px;
  border-radius: 30px;
  font-weight: 800;
  font-size: 0.92rem;
  letter-spacing: 1px;
  text-transform: uppercase;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 10px;
  box-shadow: 0 4px 14px rgba(0,0,0,0.06);
  transition: all 0.25s ease;
  width: fit-content;
}

.btn-postularme-action:hover {
  background: var(--accent, #EF3363);
  color: #ffffff;
  border-color: var(--accent, #EF3363);
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(239, 51, 99, 0.3);
}

/* Versión Móvil */
.unete-accordion-mobile {
  display: none;
  flex-direction: column;
  gap: 14px;
}

@media (max-width: 991px) {
  .unete-accordion-desktop { display: none; }
  .unete-accordion-mobile { display: flex; }
  .unete-role-title { font-size: 2.2rem; }
}

.mobile-vacante-card {
  background: var(--card-bg, #ffffff);
  border: 1px solid var(--border, #e2e8f0);
  border-radius: 14px;
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0,0,0,0.03);
}

.mobile-vacante-header {
  padding: 18px 20px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  cursor: pointer;
  font-weight: 800;
}

.mobile-vacante-body {
  display: none;
  padding: 0 20px 20px;
  border-top: 1px solid var(--border, #e2e8f0);
  margin-top: 8px;
  padding-top: 16px;
}

/* Modal de Postulación de Alta Calidad */
.modal-postulacion-overlay {
  position: fixed;
  inset: 0;
  z-index: 10000;
  background: rgba(0, 0, 0, 0.7);
  backdrop-filter: blur(6px);
  display: none;
  align-items: center;
  justify-content: center;
  padding: 20px;
  overflow-y: auto;
}

.modal-postulacion-box {
  background: var(--card-bg, #ffffff);
  color: var(--text, #111827);
  border-radius: 20px;
  border: 1px solid var(--border, #e2e8f0);
  max-width: 580px;
  width: 100%;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.35);
  padding: 30px;
  position: relative;
  margin: auto;
}

[data-bs-theme="dark"] .modal-postulacion-box {
  background: #181820;
  color: #f8fafc;
  border-color: rgba(255, 255, 255, 0.12);
}

.modal-form-label {
  font-size: 0.82rem;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: var(--muted, #64748b);
  display: block;
  margin-bottom: 6px;
}

.modal-form-input {
  background: var(--bg-subtle, #f8fafc);
  border: 1.5px solid var(--border, #cbd5e1);
  color: var(--text, #0f172a);
  border-radius: 10px;
  padding: 12px 16px;
  font-size: 0.95rem;
  width: 100%;
  transition: border-color 0.2s, box-shadow 0.2s;
  outline: none;
}

[data-bs-theme="dark"] .modal-form-input {
  background: #20202a;
  border-color: rgba(255, 255, 255, 0.15);
  color: #f8fafc;
}

.modal-form-input:focus {
  border-color: #EF3363;
  box-shadow: 0 0 0 3px rgba(239, 51, 99, 0.15);
}

.btn-submit-postulacion {
  background: linear-gradient(135deg, #EF3363 0%, #d62250 100%);
  color: #ffffff;
  border: none;
  padding: 14px 28px;
  border-radius: 12px;
  font-weight: 800;
  font-size: 1rem;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  box-shadow: 0 4px 16px rgba(239, 51, 99, 0.35);
  transition: transform 0.2s, box-shadow 0.2s;
  width: 100%;
}

.btn-submit-postulacion:hover {
  transform: translateY(-1px);
  box-shadow: 0 6px 20px rgba(239, 51, 99, 0.45);
}
</style>

<div class="unete-container">

  <!-- Encabezado Limpio de la Sección -->
  <div class="unete-section-header">
    <span class="unete-badge-header">Únete al Equipo</span>
    <h1 class="unete-main-title"><?= $totalVacantes ?> Posiciones Abiertas</h1>
    <p class="unete-sub-desc">Selecciona un rol para conocer los detalles e iniciar tu postulación</p>
  </div>

  <!-- Vista Desktop: Acordeón Horizontal Expandible -->
  <div class="unete-accordion-desktop" id="desktopAccordion">
    <?php if (empty($vacantes)): ?>
      <div style="padding: 60px; text-align: center; width: 100%; color: var(--muted);">
        <i class="bi bi-info-circle" style="font-size: 2rem; display: block; margin-bottom: 12px; color: var(--accent);"></i>
        Actualmente no hay vacantes abiertas. ¡Vuelve a consultar pronto!
      </div>
    <?php else: ?>
      <?php foreach ($vacantes as $index => $vac): ?>
        <div class="unete-col <?= $index === 0 ? 'active' : '' ?>" data-index="<?= $index ?>">
          
          <!-- Estado Colapsado (Texto Vertical) -->
          <div class="unete-col-collapsed">
            <span class="unete-num-small">0<?= $index + 1 ?></span>
            <span class="unete-vertical-text"><?= htmlspecialchars($vac['titulo']) ?></span>
            <span style="height: 14px; width: 2px; background: var(--accent); opacity: 0.5;"></span>
          </div>

          <!-- Estado Expandido (Detalle Completo) -->
          <div class="unete-col-expanded">
            <div>
              <span class="unete-badge-tag">● <?= htmlspecialchars($vac['tag']) ?></span>
              <h1 class="unete-role-title"><?= htmlspecialchars($vac['titulo']) ?></h1>
              <?php if (!empty($vac['subtitulo_italic'])): ?>
                <span class="unete-role-italic"><?= htmlspecialchars($vac['subtitulo_italic']) ?></span>
              <?php endif; ?>
              <div style="width: 40px; height: 3px; background: var(--accent); margin-bottom: 20px; border-radius: 2px;"></div>
              <p class="unete-role-desc"><?= htmlspecialchars($vac['descripcion']) ?></p>
            </div>

            <div>
              <button type="button" class="btn-postularme-action btn-open-modal-postulacion" data-vacante-id="<?= $vac['id'] ?>" data-vacante-titulo="<?= htmlspecialchars($vac['titulo']) ?> (<?= htmlspecialchars($vac['tag']) ?>)">
                <span>POSTULARME</span> <i class="bi bi-arrow-right"></i>
              </button>
            </div>
          </div>

        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <!-- Vista Móvil: Acordeón Desplegable Vertical -->
  <div class="unete-accordion-mobile">
    <?php foreach ($vacantes as $index => $vac): ?>
      <div class="mobile-vacante-card">
        <div class="mobile-vacante-header" onclick="toggleMobileCard(this)">
          <div>
            <span style="color: var(--accent); margin-right: 8px;">0<?= $index + 1 ?> &bull;</span>
            <span style="font-size: 1.1rem; color: var(--text);"><?= htmlspecialchars($vac['titulo']) ?></span>
          </div>
          <i class="bi bi-chevron-down mobile-chevron" style="transition: transform 0.3s ease; color: var(--accent);"></i>
        </div>

        <div class="mobile-vacante-body">
          <span class="unete-badge-tag" style="margin-bottom: 10px;">● <?= htmlspecialchars($vac['tag']) ?></span>
          <?php if (!empty($vac['subtitulo_italic'])): ?>
            <div style="font-style: italic; color: var(--accent); font-size: 0.95rem; margin-bottom: 8px; font-weight: 600;"><?= htmlspecialchars($vac['subtitulo_italic']) ?></div>
          <?php endif; ?>
          <p style="font-size: 0.92rem; color: var(--muted); line-height: 1.5; margin-bottom: 16px;"><?= htmlspecialchars($vac['descripcion']) ?></p>
          
          <button type="button" class="btn-postularme-action btn-open-modal-postulacion" data-vacante-id="<?= $vac['id'] ?>" data-vacante-titulo="<?= htmlspecialchars($vac['titulo']) ?>" style="width: 100%; justify-content: center;">
            <span>POSTULARME</span> <i class="bi bi-arrow-right"></i>
          </button>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

</div>

<!-- MODAL FORMULARIO DE POSTULACIÓN DE ALTA CALIDAD -->
<div id="modalPostulacion" class="modal-postulacion-overlay">
  <div class="modal-postulacion-box">
    
    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; border-bottom: 1px solid var(--border, #e2e8f0); padding-bottom: 16px;">
      <div>
        <h3 style="margin: 0 0 4px; font-size: 1.4rem; font-weight: 900; color: var(--text);">Enviar Postulación</h3>
        <span id="modalPuestoTitle" style="font-size: 0.9rem; color: #EF3363; font-weight: 800;"></span>
      </div>
      <button type="button" id="closeModalPostulacion" style="background: none; border: none; color: var(--muted, #64748b); font-size: 1.8rem; cursor: pointer; line-height: 1;">&times;</button>
    </div>

    <form id="formPostularVacante" enctype="multipart/form-data">
      <input type="hidden" id="postularVacanteId" name="vacante_id" value="0">

      <div style="margin-bottom: 16px;">
        <label class="modal-form-label">Nombre Completo *</label>
        <input type="text" name="nombre" class="modal-form-input" placeholder="Ej. Carlos Martínez" required>
      </div>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 16px;">
        <div>
          <label class="modal-form-label">Correo Electrónico *</label>
          <input type="email" name="email" class="modal-form-input" placeholder="tu@correo.com" required>
        </div>
        <div>
          <label class="modal-form-label">Teléfono (Opcional)</label>
          <input type="tel" name="telefono" class="modal-form-input" placeholder="+52 55 1234 5678">
        </div>
      </div>

      <div style="margin-bottom: 16px;">
        <label class="modal-form-label">¿Por qué deseas unirte a CatInk? *</label>
        <textarea name="razon" rows="3" class="modal-form-input" placeholder="Cuéntanos sobre tu experiencia o motivación para este rol..." required style="resize: vertical;"></textarea>
      </div>

      <div style="margin-bottom: 24px;">
        <label class="modal-form-label">Adjunta tu CV (PDF o Word) *</label>
        <input type="file" name="cv" accept=".pdf,.doc,.docx" class="modal-form-input" required>
      </div>

      <div style="display: flex; gap: 12px; justify-content: flex-end;">
        <button type="button" id="btnCancelPostulacion" style="background: transparent; color: var(--muted); border: 1px solid var(--border); padding: 12px 24px; border-radius: 12px; font-weight: 700; cursor: pointer;">Cancelar</button>
        <button type="submit" id="btnSubmitPostulacion" class="btn-submit-postulacion">
          <span id="btnSubmitText">Enviar Solicitud</span> <i class="bi bi-send-fill"></i>
        </button>
      </div>
    </form>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  // Acordeón Desktop
  const cols = document.querySelectorAll('.unete-col');
  cols.forEach(col => {
    col.addEventListener('mouseenter', () => {
      cols.forEach(c => c.classList.remove('active'));
      col.classList.add('active');
    });
    col.addEventListener('click', (e) => {
      if (e.target.closest('.btn-open-modal-postulacion')) return;
      cols.forEach(c => c.classList.remove('active'));
      col.classList.add('active');
    });
  });

  // Modal de Postulación
  const modal = document.getElementById('modalPostulacion');
  const closeModal = document.getElementById('closeModalPostulacion');
  const cancelModal = document.getElementById('btnCancelPostulacion');
  const form = document.getElementById('formPostularVacante');
  const modalTitle = document.getElementById('modalPuestoTitle');
  const inputVacanteId = document.getElementById('postularVacanteId');
  const btnSubmit = document.getElementById('btnSubmitPostulacion');
  const btnSubmitText = document.getElementById('btnSubmitText');

  document.querySelectorAll('.btn-open-modal-postulacion').forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.stopPropagation();
      const vacId = btn.dataset.vacanteId;
      const vacTitle = btn.dataset.vacanteTitulo;
      inputVacanteId.value = vacId;
      modalTitle.textContent = 'Puesto: ' + vacTitle;
      modal.style.display = 'flex';
    });
  });

  closeModal?.addEventListener('click', () => modal.style.display = 'none');
  cancelModal?.addEventListener('click', () => modal.style.display = 'none');
  window.addEventListener('click', (e) => {
    if (e.target === modal) modal.style.display = 'none';
  });

  // Envío AJAX
  form.addEventListener('submit', function(e) {
    e.preventDefault();
    btnSubmit.disabled = true;
    btnSubmitText.textContent = 'Enviando...';
    btnSubmit.querySelector('i').className = 'bi bi-arrow-repeat spin';

    const formData = new FormData(form);

    fetch(BASE_PATH + '/controllers/postular_vacante.php', {
      method: 'POST',
      body: formData
    })
    .then(r => r.json())
    .then(res => {
      btnSubmit.disabled = false;
      btnSubmitText.textContent = 'Enviar Solicitud';
      btnSubmit.querySelector('i').className = 'bi bi-send-fill';

      if (res.success) {
        alert(res.message);
        modal.style.display = 'none';
        form.reset();
      } else {
        alert(res.error || 'Ocurrió un error al enviar la solicitud.');
      }
    })
    .catch(err => {
      btnSubmit.disabled = false;
      btnSubmitText.textContent = 'Enviar Solicitud';
      btnSubmit.querySelector('i').className = 'bi bi-send-fill';
      alert('Error de conexión al enviar la solicitud.');
    });
  });
});

function toggleMobileCard(header) {
  const card = header.parentElement;
  const body = card.querySelector('.mobile-vacante-body');
  const chevron = header.querySelector('.mobile-chevron');
  if (body.style.display === 'block') {
    body.style.display = 'none';
    if (chevron) chevron.style.transform = 'rotate(0deg)';
  } else {
    body.style.display = 'block';
    if (chevron) chevron.style.transform = 'rotate(180deg)';
  }
}
</script>

<?php include("./../layout/footer.php"); ?>