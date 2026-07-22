<?php
include("./../layout/headerAdmin.php");
include("./../controllers/aclcontroller.php");
proteger('noticias', 'editar');
include("./../data/conexion.php");
require_once("./../views/helpers/urlhelper.php");

// Obtener todas las vacantes
$resVac = $con->query("SELECT * FROM vacantes_equipo ORDER BY orden ASC, id ASC");
$vacantes = $resVac ? $resVac->fetch_all(MYSQLI_ASSOC) : [];

// Obtener todas las solicitudes recibidas con información de vacante
$resSol = $con->query("
    SELECT s.*, v.titulo AS vacante_titulo, v.tag AS vacante_tag
    FROM solicitudes_vacantes s
    LEFT JOIN vacantes_equipo v ON s.vacante_id = v.id
    ORDER BY s.fecha_solicitud DESC
");
$solicitudes = $resSol ? $resSol->fetch_all(MYSQLI_ASSOC) : [];

$totalVacantes = count($vacantes);
$totalSolicitudes = count($solicitudes);
?>

<style>
.vacantes-tabs {
  display: flex;
  gap: 12px;
  margin-bottom: 24px;
  border-bottom: 1px solid var(--border, rgba(255,255,255,0.1));
  padding-bottom: 12px;
}
.vacante-tab-btn {
  background: transparent;
  color: var(--muted, #888);
  border: 1px solid transparent;
  padding: 10px 20px;
  border-radius: 10px;
  font-weight: 700;
  font-size: 0.95rem;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 8px;
  transition: all 0.2s ease;
}
.vacante-tab-btn.active {
  background: rgba(239, 51, 99, 0.15);
  color: var(--accent, #EF3363);
  border-color: rgba(239, 51, 99, 0.3);
}
.vacante-badge-status {
  padding: 3px 10px;
  border-radius: 6px;
  font-size: 0.75rem;
  font-weight: 800;
  text-transform: uppercase;
}
.status-abierta { background: rgba(46, 204, 113, 0.15); color: #2ecc71; border: 1px solid rgba(46,204,113,0.3); }
.status-cerrada { background: rgba(231, 76, 60, 0.15); color: #e74c3c; border: 1px solid rgba(231,76,60,0.3); }

.status-pendiente { background: rgba(241, 196, 15, 0.15); color: #f1c40f; }
.status-revisado { background: rgba(52, 152, 219, 0.15); color: #3498db; }
.status-aceptado { background: rgba(46, 204, 113, 0.15); color: #2ecc71; }
.status-rechazado { background: rgba(231, 76, 60, 0.15); color: #e74c3c; }
</style>

<div class="container-fluid" style="padding-top: 20px; padding-bottom: 50px;">
  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 14px;">
    <div>
      <h2 style="margin: 0; font-size: 1.8rem; font-weight: 800;">Gestión de Vacantes y Empleos</h2>
      <p style="margin: 0; color: var(--muted); font-size: 0.9rem;">Administra las posiciones abiertas en CatInk y revisa las candidaturas recibidas</p>
    </div>
    
    <button id="btnNuevaVacante" class="btn btn-accent" style="display: inline-flex; align-items: center; gap: 8px; font-weight: 700; padding: 10px 20px; border-radius: 10px; background: var(--accent); color: #fff; border: none; cursor: pointer;">
      <i class="bi bi-plus-lg"></i> Crear Nueva Vacante
    </button>
  </div>

  <!-- Pestañas -->
  <div class="vacantes-tabs">
    <button class="vacante-tab-btn active" data-tab="vacantesList">
      <i class="bi bi-briefcase-fill"></i> Vacantes de Empleo (<?= $totalVacantes ?>)
    </button>
    <button class="vacante-tab-btn" data-tab="solicitudesList">
      <i class="bi bi-people-fill"></i> Postulaciones Recibidas (<?= $totalSolicitudes ?>)
    </button>
  </div>

  <!-- Pestaña 1: Lista de Vacantes -->
  <div id="tab-vacantesList" class="vacante-tab-content">
    <div class="card shadow-sm" style="background: var(--card-bg, #1a1a20); border: 1px solid var(--border, rgba(255,255,255,0.1)); border-radius: 12px; overflow: hidden;">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-dark table-hover mb-0" style="vertical-align: middle;">
            <thead>
              <tr style="border-bottom: 1px solid var(--border);">
                <th style="padding: 14px 18px;">Orden</th>
                <th>Etiqueta / Tag</th>
                <th>Puesto / Título</th>
                <th>Subtítulo en Cursiva</th>
                <th>Modalidad</th>
                <th>Estado</th>
                <th style="text-align: right; padding-right: 18px;">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($vacantes)): ?>
                <tr><td colspan="7" style="text-align: center; color: var(--muted); padding: 40px;">No hay vacantes configuradas. Haz clic en "Crear Nueva Vacante".</td></tr>
              <?php else: ?>
                <?php foreach ($vacantes as $vac): ?>
                  <tr>
                    <td style="padding: 14px 18px; font-weight: 700; width: 60px;"><?= $vac['orden'] ?></td>
                    <td><span style="font-size: 0.8rem; font-weight: 800; color: var(--accent);"><?= htmlspecialchars($vac['tag']) ?></span></td>
                    <td><strong style="font-size: 1rem; color: #fff;"><?= htmlspecialchars($vac['titulo']) ?></strong></td>
                    <td><span style="font-style: italic; color: var(--muted); font-size: 0.9rem;"><?= htmlspecialchars($vac['subtitulo_italic'] ?? '') ?></span></td>
                    <td><span style="font-size: 0.85rem; color: var(--muted);"><?= htmlspecialchars($vac['modalidad']) ?></span></td>
                    <td>
                      <span class="vacante-badge-status <?= $vac['estado'] == 1 ? 'status-abierta' : 'status-cerrada' ?>">
                        <?= $vac['estado'] == 1 ? 'Abierta' : 'Pausada' ?>
                      </span>
                    </td>
                    <td style="text-align: right; padding-right: 18px;">
                      <button class="btn-edit-vacante" data-vacante='<?= json_encode($vac, JSON_HEX_APOS|JSON_HEX_QUOT) ?>' style="background: rgba(255,255,255,0.08); color: #fff; border: none; padding: 6px 12px; border-radius: 6px; font-size: 0.85rem; font-weight: 700; cursor: pointer; margin-right: 6px;">
                        <i class="bi bi-pencil"></i>
                      </button>
                      <button class="btn-toggle-vacante" data-id="<?= $vac['id'] ?>" style="background: rgba(255,255,255,0.08); color: var(--muted); border: none; padding: 6px 12px; border-radius: 6px; font-size: 0.85rem; font-weight: 700; cursor: pointer; margin-right: 6px;" title="Pausar/Activar">
                        <i class="bi bi-power"></i>
                      </button>
                      <button class="btn-delete-vacante" data-id="<?= $vac['id'] ?>" style="background: rgba(239,51,99,0.15); color: var(--accent); border: none; padding: 6px 12px; border-radius: 6px; font-size: 0.85rem; font-weight: 700; cursor: pointer;">
                        <i class="bi bi-trash"></i>
                      </button>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- Pestaña 2: Solicitudes Recibidas -->
  <div id="tab-solicitudesList" class="vacante-tab-content" style="display: none;">
    <div class="card shadow-sm" style="background: var(--card-bg, #1a1a20); border: 1px solid var(--border, rgba(255,255,255,0.1)); border-radius: 12px; overflow: hidden;">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-dark table-hover mb-0" style="vertical-align: middle;">
            <thead>
              <tr style="border-bottom: 1px solid var(--border);">
                <th style="padding: 14px 18px;">Postulante</th>
                <th>Vacante Solicitada</th>
                <th>Fecha</th>
                <th>Motivación</th>
                <th>Estado</th>
                <th style="text-align: right; padding-right: 18px;">CV Adjunto</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($solicitudes)): ?>
                <tr><td colspan="6" style="text-align: center; color: var(--muted); padding: 40px;">No se han recibido postulaciones de candidatos aún.</td></tr>
              <?php else: ?>
                <?php foreach ($solicitudes as $sol): ?>
                  <tr>
                    <td style="padding: 14px 18px;">
                      <strong style="color: #fff; display: block; font-size: 0.95rem;"><?= htmlspecialchars($sol['nombre']) ?></strong>
                      <span style="font-size: 0.8rem; color: var(--muted);"><i class="bi bi-envelope"></i> <?= htmlspecialchars($sol['email']) ?></span>
                      <?php if (!empty($sol['telefono'])): ?>
                        &bull; <span style="font-size: 0.8rem; color: var(--muted);"><i class="bi bi-phone"></i> <?= htmlspecialchars($sol['telefono']) ?></span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <span style="font-weight: 700; color: var(--accent); font-size: 0.9rem;"><?= htmlspecialchars($sol['vacante_titulo'] ?? 'General') ?></span>
                    </td>
                    <td><span style="font-size: 0.85rem; color: var(--muted);"><?= date('d/m/Y H:i', strtotime($sol['fecha_solicitud'])) ?></span></td>
                    <td style="max-width: 250px;">
                      <div style="font-size: 0.85rem; color: #ddd; max-height: 50px; overflow-y: auto; white-space: pre-wrap; font-style: italic;">
                        <?= htmlspecialchars($sol['razon']) ?>
                      </div>
                    </td>
                    <td>
                      <select class="form-select form-select-sm select-sol-estado" data-id="<?= $sol['id'] ?>" style="background: #111; color: #fff; border: 1px solid var(--border); font-size: 0.8rem; font-weight: 700; border-radius: 6px;">
                        <option value="pendiente" <?= $sol['estado'] == 'pendiente' ? 'selected' : '' ?>>🟡 Pendiente</option>
                        <option value="revisado" <?= $sol['estado'] == 'revisado' ? 'selected' : '' ?>>🔵 Revisado</option>
                        <option value="aceptado" <?= $sol['estado'] == 'aceptado' ? 'selected' : '' ?>>🟢 Aceptado</option>
                        <option value="rechazado" <?= $sol['estado'] == 'rechazado' ? 'selected' : '' ?>>🔴 Rechazado</option>
                      </select>
                    </td>
                    <td style="text-align: right; padding-right: 18px;">
                      <a href="<?= basePath() ?>/<?= htmlspecialchars($sol['cv_archivo']) ?>" target="_blank" class="btn btn-sm btn-outline-light" style="font-weight: 700; border-radius: 6px; display: inline-flex; align-items: center; gap: 6px;">
                        <i class="bi bi-file-earmark-pdf-fill" style="color: #EF3363;"></i> Descargar CV
                      </a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal Formulario de Vacante -->
<div id="vacanteModal" class="crop-modal" style="display: none; align-items: center; justify-content: center; z-index: 10000;">
  <div class="crop-modal-content" style="max-width: 600px; width: 90%; background: var(--card-bg, #1a1a20); color: #fff; border-radius: 14px; border: 1px solid var(--border); padding: 24px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid var(--border); padding-bottom: 12px;">
      <h3 id="vacanteModalTitle" style="margin: 0; font-size: 1.25rem; font-weight: 800;">Crear Nueva Vacante</h3>
      <button type="button" id="closeVacanteModal" style="background: none; border: none; color: #888; font-size: 1.4rem; cursor: pointer;">&times;</button>
    </div>

    <form id="formVacante">
      <input type="hidden" id="vacanteId" name="id" value="0">
      
      <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 12px; margin-bottom: 14px;">
        <div>
          <label style="font-size: 0.85rem; font-weight: 700; color: var(--muted); display: block; margin-bottom: 4px;">Orden</label>
          <input type="number" id="vacanteOrden" name="orden" value="1" min="1" class="form-control" style="background: #111; color: #fff; border: 1px solid var(--border);" required>
        </div>
        <div>
          <label style="font-size: 0.85rem; font-weight: 700; color: var(--muted); display: block; margin-bottom: 4px;">Tag (ej: 01 · ANIME & MANGA)</label>
          <input type="text" id="vacanteTag" name="tag" placeholder="01 · CULTURA POP" class="form-control" style="background: #111; color: #fff; border: 1px solid var(--border);" required>
        </div>
      </div>

      <div style="margin-bottom: 14px;">
        <label style="font-size: 0.85rem; font-weight: 700; color: var(--muted); display: block; margin-bottom: 4px;">Título de la Vacante (ej: EDITOR)</label>
        <input type="text" id="vacanteTitulo" name="titulo" placeholder="EDITOR" class="form-control" style="background: #111; color: #fff; border: 1px solid var(--border);" required>
      </div>

      <div style="margin-bottom: 14px;">
        <label style="font-size: 0.85rem; font-weight: 700; color: var(--muted); display: block; margin-bottom: 4px;">Subtítulo en Cursiva (ej: Da forma al relato.)</label>
        <input type="text" id="vacanteSubtitulo" name="subtitulo_italic" placeholder="Crea contenido único." class="form-control" style="background: #111; color: #fff; border: 1px solid var(--border);">
      </div>

      <div style="margin-bottom: 14px;">
        <label style="font-size: 0.85rem; font-weight: 700; color: var(--muted); display: block; margin-bottom: 4px;">Modalidad (ej: 100% Remoto · Tiempo completo)</label>
        <input type="text" id="vacanteModalidad" name="modalidad" value="100% Remoto · Tiempo completo" class="form-control" style="background: #111; color: #fff; border: 1px solid var(--border);">
      </div>

      <div style="margin-bottom: 20px;">
        <label style="font-size: 0.85rem; font-weight: 700; color: var(--muted); display: block; margin-bottom: 4px;">Descripción Completa del Puesto</label>
        <textarea id="vacanteDescripcion" name="descripcion" rows="4" class="form-control" style="background: #111; color: #fff; border: 1px solid var(--border);" required></textarea>
      </div>

      <div style="display: flex; justify-content: flex-end; gap: 10px;">
        <button type="button" id="btnCancelVacante" class="btn btn-secondary" style="font-weight: 700;">Cancelar</button>
        <button type="submit" class="btn btn-accent" style="font-weight: 700; background: var(--accent); color: #fff;">Guardar Vacante</button>
      </div>
    </form>
  </div>
</div>

<script>
function initVacantesAdmin() {
  const modal = document.getElementById('vacanteModal');
  const btnNueva = document.getElementById('btnNuevaVacante');
  const btnClose = document.getElementById('closeVacanteModal');
  const btnCancel = document.getElementById('btnCancelVacante');
  const form = document.getElementById('formVacante');

  if (!btnNueva || !form) return;

  // Pestañas
  document.querySelectorAll('.vacante-tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.vacante-tab-btn').forEach(b => b.classList.remove('active'));
      document.querySelectorAll('.vacante-tab-content').forEach(c => c.style.display = 'none');
      btn.classList.add('active');
      document.getElementById('tab-' + btn.dataset.tab).style.display = 'block';
    });
  });

  function openModal(data = null) {
    if (data) {
      document.getElementById('vacanteModalTitle').textContent = 'Editar Vacante #' + data.id;
      document.getElementById('vacanteId').value = data.id;
      document.getElementById('vacanteOrden').value = data.orden || 1;
      document.getElementById('vacanteTag').value = data.tag || '';
      document.getElementById('vacanteTitulo').value = data.titulo || '';
      document.getElementById('vacanteSubtitulo').value = data.subtitulo_italic || '';
      document.getElementById('vacanteModalidad').value = data.modalidad || '100% Remoto · Tiempo completo';
      document.getElementById('vacanteDescripcion').value = data.descripcion || '';
    } else {
      document.getElementById('vacanteModalTitle').textContent = 'Crear Nueva Vacante';
      form.reset();
      document.getElementById('vacanteId').value = 0;
    }
    modal.style.display = 'flex';
  }

  btnNueva?.addEventListener('click', () => openModal());
  btnClose?.addEventListener('click', () => modal.style.display = 'none');
  btnCancel?.addEventListener('click', () => modal.style.display = 'none');

  document.querySelectorAll('.btn-edit-vacante').forEach(btn => {
    btn.addEventListener('click', function() {
      const data = JSON.parse(this.dataset.vacante);
      openModal(data);
    });
  });

  // Form Submit
  form.addEventListener('submit', function(e) {
    e.preventDefault();
    const payload = {
      id: parseInt(document.getElementById('vacanteId').value),
      orden: parseInt(document.getElementById('vacanteOrden').value),
      tag: document.getElementById('vacanteTag').value,
      titulo: document.getElementById('vacanteTitulo').value,
      subtitulo_italic: document.getElementById('vacanteSubtitulo').value,
      modalidad: document.getElementById('vacanteModalidad').value,
      descripcion: document.getElementById('vacanteDescripcion').value
    };

    fetch(BASE_PATH + '/controllers/vacantes_guardar.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    })
    .then(r => r.json())
    .then(res => {
      if (res.success) {
        alert(res.message);
        window.location.reload();
      } else {
        alert(res.error || 'Error al guardar vacante');
      }
    });
  });

  // Toggle & Delete
  document.querySelectorAll('.btn-toggle-vacante').forEach(btn => {
    btn.addEventListener('click', function() {
      const id = this.dataset.id;
      fetch(BASE_PATH + '/controllers/vacantes_eliminar.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: id, action: 'toggle' })
      })
      .then(r => r.json())
      .then(res => {
        if (res.success) window.location.reload();
        else alert(res.error);
      });
    });
  });

  document.querySelectorAll('.btn-delete-vacante').forEach(btn => {
    btn.addEventListener('click', function() {
      const id = this.dataset.id;
      if (!confirm('¿Estás seguro de que deseas eliminar esta vacante de empleo?')) return;
      fetch(BASE_PATH + '/controllers/vacantes_eliminar.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: id, action: 'delete' })
      })
      .then(r => r.json())
      .then(res => {
        if (res.success) window.location.reload();
        else alert(res.error);
      });
    });
  });

  // Cambio de estado de solicitudes
  document.querySelectorAll('.select-sol-estado').forEach(sel => {
    sel.addEventListener('change', function() {
      const id = this.dataset.id;
      const estado = this.value;
      fetch(BASE_PATH + '/controllers/solicitud_estado.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: id, estado: estado })
      })
      .then(r => r.json())
      .then(res => {
        if (!res.success) alert(res.error);
      });
    });
  });
}

document.addEventListener('DOMContentLoaded', initVacantesAdmin);
document.addEventListener('turbo:load', initVacantesAdmin);
</script>

<?php include("./../layout/footerAdmin.php"); ?>
