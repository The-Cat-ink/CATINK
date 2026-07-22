<?php
include("./../layout/headerAdmin.php");
include("./../controllers/aclcontroller.php");
proteger('noticias', 'editar');
include("./../data/conexion.php");
require_once("./../views/helpers/urlhelper.php");

// Buscar si hay consulta de búsqueda
$q = trim($_GET['q'] ?? '');

// Obtener todas las vacantes
$sqlVac = "SELECT * FROM vacantes_equipo";
if ($q !== '') {
    $like = $con->real_escape_string("%$q%");
    $sqlVac .= " WHERE titulo LIKE '$like' OR tag LIKE '$like' OR subtitulo_italic LIKE '$like'";
}
$sqlVac .= " ORDER BY orden ASC, id ASC";
$resVac = $con->query($sqlVac);
$vacantes = $resVac ? $resVac->fetch_all(MYSQLI_ASSOC) : [];

// Si existen duplicados en el orden, corregir y normalizar la secuencia automáticamente (1, 2, 3...)
if (!empty($vacantes) && $q === '') {
    $hasDuplicates = false;
    $seenOrders = [];
    foreach ($vacantes as $v) {
        if (in_array($v['orden'], $seenOrders)) {
            $hasDuplicates = true;
            break;
        }
        $seenOrders[] = $v['orden'];
    }
    if ($hasDuplicates) {
        $upd = $con->prepare("UPDATE vacantes_equipo SET orden = ? WHERE id = ?");
        foreach ($vacantes as $idx => $v) {
            $seqOrder = $idx + 1;
            $upd->bind_param("ii", $seqOrder, $v['id']);
            $upd->execute();
            $vacantes[$idx]['orden'] = $seqOrder;
        }
    }
}

// Obtener todas las solicitudes recibidas
$resSol = $con->query("
    SELECT s.*, v.titulo AS vacante_titulo, v.tag AS vacante_tag
    FROM solicitudes_vacantes s
    LEFT JOIN vacantes_equipo v ON s.vacante_id = v.id
    ORDER BY s.fecha_solicitud DESC
");
$solicitudes = $resSol ? $resSol->fetch_all(MYSQLI_ASSOC) : [];

$totalVacantes = count($vacantes);
$totalAbiertas = count(array_filter($vacantes, fn($v) => $v['estado'] == 1));
$totalSolicitudes = count($solicitudes);
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4" style="flex-wrap: wrap; gap: 12px;">
        <h1 style="margin:0;">Gestión de Vacantes y Empleos</h1>
        <form method="GET" class="admin-search-form" style="display:flex; align-items:center; gap:8px;">
            <i class="bi bi-search admin-search-icon"></i>
            <input type="search" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Buscar vacante..." class="admin-search-input">
            <?php if($q): ?><a href="./vacantes.php" class="admin-search-clear">&times;</a><?php endif; ?>
        </form>
    </div>

    <!-- Estadísticas rápidas estilo CatInk -->
    <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); margin-bottom: 24px;">
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(239, 51, 99, 0.1); color: #EF3363;"><i class="bi bi-briefcase-fill"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?= $totalVacantes ?></span>
                <span class="stat-label">Vacantes Configuradas</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(16,185,129,0.1); color: #10b981;"><i class="bi bi-check-circle-fill"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?= $totalAbiertas ?></span>
                <span class="stat-label">Vacantes Abiertas</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(99,102,241,0.1); color: #6366f1;"><i class="bi bi-people-fill"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?= $totalSolicitudes ?></span>
                <span class="stat-label">Postulaciones Recibidas</span>
            </div>
        </div>
    </div>

<style>
.contenidos-tabs .tab-btn {
  border: none !important;
  outline: none !important;
  box-shadow: none !important;
  cursor: pointer;
  background: rgba(0,0,0,0.04);
  padding: 8px 18px;
  border-radius: 8px;
  font-weight: 700;
  font-size: 0.9rem;
  color: var(--muted, #64748b);
  transition: all 0.2s ease;
}
[data-bs-theme="dark"] .contenidos-tabs .tab-btn {
  background: rgba(255,255,255,0.06);
  color: var(--muted, #94a3b8);
}
.contenidos-tabs .tab-btn.active {
  background: var(--accent, #EF3363) !important;
  color: #ffffff !important;
}
.sortable-ghost {
  opacity: 0.4;
  background-color: var(--bg-subtle, #f1f5f9) !important;
  border: 2px dashed var(--accent, #EF3363) !important;
  user-select: none !important;
  -webkit-user-select: none !important;
}
.sortable-drag {
  opacity: 0.95;
  background: var(--card-bg, #ffffff) !important;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
  user-select: none !important;
  -webkit-user-select: none !important;
}
.vacante-row,
.vacante-row td {
  user-select: none;
  -webkit-user-select: none;
}
.vacante-row td:first-child,
.bi-grip-vertical {
  user-select: none !important;
  -webkit-user-select: none !important;
  cursor: grab;
}
.vacante-row.sortable-drag,
.vacante-row.sortable-ghost {
  cursor: grabbing !important;
}
</style>

    <!-- Toolbar oficial de CatInk -->
    <div class="contenidos-toolbar">
        <div class="contenidos-tabs">
            <button type="button" class="tab-btn active" id="tabBtnVacantes" onclick="switchVacanteTab('vacantesList')">Todas las vacantes (<?= $totalVacantes ?>)</button>
            <button type="button" class="tab-btn" id="tabBtnSolicitudes" onclick="switchVacanteTab('solicitudesList')">Postulaciones recibidas (<?= $totalSolicitudes ?>)</button>
        </div>
        <div class="contenidos-actions">
            <button type="button" id="btnCrearVacante" class="btn btn-accent"><i class="bi bi-plus-lg"></i> Crear vacante</button>
        </div>
    </div>

    <!-- Pestaña 1: Vacantes -->
    <div id="sectionVacantesList">
        <div class="card shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="contenidos-table">
                        <thead>
                            <tr>
                                <th style="width: 40px; text-align:center;"><i class="bi bi-arrows-move"></i></th>
                                <th style="width: 50px;">Orden</th>
                                <th>Etiqueta / Tag</th>
                                <th>Puesto / Título</th>
                                <th>Subtítulo en Cursiva</th>
                                <th>Modalidad</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="vacantesTbody">
                            <?php if (empty($vacantes)): ?>
                                <tr><td colspan="8" style="text-align:center; padding:30px; color:var(--muted);">No se encontraron vacantes. Haz clic en "Crear vacante".</td></tr>
                            <?php else: ?>
                                <?php foreach ($vacantes as $row): ?>
                                    <tr data-id="<?= $row['id'] ?>" class="vacante-row">
                                        <td style="cursor: grab; text-align: center; color:var(--muted);"><i class="bi bi-grip-vertical"></i></td>
                                        <td><strong style="color:var(--text);" class="vac-orden-num"><?= $row['orden'] ?></strong></td>
                                        <td><span class="estado-badge" style="background:rgba(239,51,99,0.1); color:#EF3363; font-weight:800;"><?= htmlspecialchars($row['tag']) ?></span></td>
                                        <td><strong class="table-title"><?= htmlspecialchars($row['titulo']) ?></strong></td>
                                        <td><span style="font-style:italic; color:var(--muted);"><?= htmlspecialchars($row['subtitulo_italic'] ?? '') ?></span></td>
                                        <td><span style="font-size:0.85rem; color:var(--muted);"><?= htmlspecialchars($row['modalidad']) ?></span></td>
                                        <td>
                                            <?php if ($row['estado'] == 1): ?>
                                                <span class="estado-badge estado-publicado">Abierta</span>
                                            <?php else: ?>
                                                <span class="estado-badge estado-borrador" style="background:rgba(239,68,68,0.1); color:#ef4444;">Pausada</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="noticias-actions" style="border-top:none; padding:0; justify-content:flex-start;">
                                                <button type="button" class="btn btn-edit btn-editar-vac" 
                                                    data-id="<?= $row['id'] ?>"
                                                    data-orden="<?= $row['orden'] ?>"
                                                    data-tag="<?= htmlspecialchars($row['tag'], ENT_QUOTES) ?>"
                                                    data-titulo="<?= htmlspecialchars($row['titulo'], ENT_QUOTES) ?>"
                                                    data-subtitulo="<?= htmlspecialchars($row['subtitulo_italic'] ?? '', ENT_QUOTES) ?>"
                                                    data-modalidad="<?= htmlspecialchars($row['modalidad'], ENT_QUOTES) ?>"
                                                    data-descripcion="<?= htmlspecialchars($row['descripcion'], ENT_QUOTES) ?>"
                                                    title="Editar Vacante y Contenido">
                                                    <i class="bi bi-pencil-square"></i>
                                                </button>

                                                <button type="button" class="btn btn-edit btn-toggle-vac" 
                                                    data-id="<?= $row['id'] ?>" 
                                                    title="Pausar / Activar">
                                                    <i class="bi bi-power"></i>
                                                </button>

                                                <button type="button" class="btn btn-delete btn-eliminar-vac" 
                                                    data-id="<?= $row['id'] ?>" 
                                                    data-titulo="<?= htmlspecialchars($row['titulo'], ENT_QUOTES) ?>" 
                                                    title="Eliminar Vacante">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
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

    <!-- Pestaña 2: Solicitudes de Postulantes -->
    <div id="sectionSolicitudesList" style="display: none;">
        <div class="card shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="contenidos-table">
                        <thead>
                            <tr>
                                <th>Postulante</th>
                                <th>Vacante Solicitada</th>
                                <th>Fecha</th>
                                <th>Motivación / Presentación</th>
                                <th>Estado</th>
                                <th>CV Adjunto</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($solicitudes)): ?>
                                <tr><td colspan="6" style="text-align:center; padding:30px; color:var(--muted);">No se han recibido postulaciones de candidatos todavía.</td></tr>
                            <?php else: ?>
                                <?php foreach ($solicitudes as $sol): ?>
                                    <tr>
                                        <td>
                                            <strong class="table-title" style="display:block;"><?= htmlspecialchars($sol['nombre']) ?></strong>
                                            <span style="font-size:0.8rem; color:var(--muted);"><i class="bi bi-envelope"></i> <?= htmlspecialchars($sol['email']) ?></span>
                                            <?php if (!empty($sol['telefono'])): ?>
                                                &bull; <span style="font-size:0.8rem; color:var(--muted);"><i class="bi bi-telephone"></i> <?= htmlspecialchars($sol['telefono']) ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="estado-badge" style="background:rgba(239,51,99,0.1); color:#EF3363; font-weight:700;">
                                                <?= htmlspecialchars($sol['vacante_titulo'] ?? 'General') ?>
                                            </span>
                                        </td>
                                        <td><span style="font-size:0.85rem; color:var(--muted);"><?= date('d/m/Y H:i', strtotime($sol['fecha_solicitud'])) ?></span></td>
                                        <td style="max-width: 280px;">
                                            <div style="font-size:0.85rem; color:var(--text); max-height: 60px; overflow-y: auto; white-space: pre-wrap; font-style: italic;">
                                                <?= htmlspecialchars($sol['razon']) ?>
                                            </div>
                                        </td>
                                        <td>
                                            <select class="form-select form-select-sm select-sol-estado" data-id="<?= $sol['id'] ?>" style="background: var(--card-bg); color: var(--text); border: 1px solid var(--border); font-size: 0.8rem; font-weight: 700; border-radius: 6px;">
                                                <option value="pendiente" <?= $sol['estado'] == 'pendiente' ? 'selected' : '' ?>>🟡 Pendiente</option>
                                                <option value="revisado" <?= $sol['estado'] == 'revisado' ? 'selected' : '' ?>>🔵 Revisado</option>
                                                <option value="aceptado" <?= $sol['estado'] == 'aceptado' ? 'selected' : '' ?>>🟢 Aceptado</option>
                                                <option value="rechazado" <?= $sol['estado'] == 'rechazado' ? 'selected' : '' ?>>🔴 Rechazado</option>
                                            </select>
                                        </td>
                                        <td>
                                            <a href="<?= basePath() ?>/<?= htmlspecialchars($sol['cv_archivo']) ?>" target="_blank" class="btn btn-sm btn-accent" style="padding: 4px 10px; font-size: 0.8rem; border-radius: 6px;">
                                                <i class="bi bi-file-earmark-pdf-fill"></i> Descargar CV
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

<!-- MODAL ESTILO CATINK (CREAR / EDITAR VACANTE) -->
<div id="vacanteModal" class="crop-modal" style="display: none; align-items: center; justify-content: center; z-index: 10000; background: rgba(0,0,0,0.6); backdrop-filter: blur(4px);">
    <div class="crop-modal-content" style="max-width: 650px; width: 92%; padding: 28px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid var(--border); padding-bottom: 12px;">
            <h3 id="modalVacanteTitle" style="margin: 0; font-size: 1.3rem; font-weight: 800;">Crear Vacante de Empleo</h3>
            <button type="button" id="closeVacanteModal" style="background: none; border: none; color: var(--muted); font-size: 1.5rem; cursor: pointer;">&times;</button>
        </div>

        <form id="formVacanteModal">
            <input type="hidden" id="vacanteId" name="id" value="0">
            <input type="hidden" id="vacanteOrden" name="orden" value="1">
            
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 0.82rem; font-weight: 700; text-transform: uppercase; color: var(--muted); margin-bottom: 6px;">Tag / Etiqueta (ej: 01 · ANIME & MANGA) *</label>
                <input type="text" id="vacanteTag" name="tag" placeholder="01 · CULTURA POP" required style="width: 100%; padding: 10px 14px; border: 1px solid var(--border); border-radius: 8px; background: var(--bg-subtle, #f8fafc); color: var(--text);">
            </div>

            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 0.82rem; font-weight: 700; text-transform: uppercase; color: var(--muted); margin-bottom: 6px;">Título del Puesto (ej: EDITOR) *</label>
                <input type="text" id="vacanteTitulo" name="titulo" placeholder="EDITOR" required style="width: 100%; padding: 10px 14px; border: 1px solid var(--border); border-radius: 8px; background: var(--bg-subtle, #f8fafc); color: var(--text);">
            </div>

            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 0.82rem; font-weight: 700; text-transform: uppercase; color: var(--muted); margin-bottom: 6px;">Subtítulo en Cursiva (ej: Da forma al relato.)</label>
                <input type="text" id="vacanteSubtitulo" name="subtitulo_italic" placeholder="Crea contenido único." style="width: 100%; padding: 10px 14px; border: 1px solid var(--border); border-radius: 8px; background: var(--bg-subtle, #f8fafc); color: var(--text);">
            </div>

            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 0.82rem; font-weight: 700; text-transform: uppercase; color: var(--muted); margin-bottom: 6px;">Modalidad de Trabajo</label>
                <input type="text" id="vacanteModalidad" name="modalidad" value="100% Remoto · Tiempo completo" style="width: 100%; padding: 10px 14px; border: 1px solid var(--border); border-radius: 8px; background: var(--bg-subtle, #f8fafc); color: var(--text);">
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 0.82rem; font-weight: 700; text-transform: uppercase; color: var(--muted); margin-bottom: 6px;">Modificar Contenido y Descripción Completa del Puesto *</label>
                <textarea id="vacanteDescripcion" name="descripcion" rows="5" required style="width: 100%; padding: 12px 14px; border: 1px solid var(--border); border-radius: 8px; background: var(--bg-subtle, #f8fafc); color: var(--text); resize: vertical; line-height: 1.5;" placeholder="Detalla los requisitos, tareas y beneficios del puesto..."></textarea>
            </div>

            <div class="crop-actions" style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" id="closeVacanteBtn" class="btn btn-secondary">Cancelar</button>
                <button type="submit" class="btn btn-accent"><i class="bi bi-check-lg"></i> Guardar Vacante</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
const BASE_PATH = '<?= basePath() ?>';

function showToast(msg, type = 'success') {
  let container = document.querySelector('.toast-container');
  if (!container) {
    container = document.createElement('div');
    container.className = 'toast-container';
    document.body.appendChild(container);
  }
  const toast = document.createElement('div');
  toast.className = 'toast-msg' + (type ? ' toast-' + type : '');
  
  let icon = 'bi-check-circle-fill';
  if (type === 'error' || type === 'danger') icon = 'bi-exclamation-circle-fill';
  if (type === 'warning') icon = 'bi-exclamation-triangle-fill';

  toast.innerHTML = `<div style="display:flex; align-items:center; gap:10px;"><i class="bi ${icon}" style="font-size:1.1rem; color:${type === 'error' ? '#ef4444' : '#10b981'};"></i> <span>${msg}</span></div>`;
  container.appendChild(toast);
  
  setTimeout(() => {
    toast.style.transition = 'all 0.3s ease';
    toast.style.opacity = '0';
    toast.style.transform = 'translateY(-10px)';
    setTimeout(() => toast.remove(), 300);
  }, 3000);
}

function switchVacanteTab(tabName) {
  const btnVac = document.getElementById('tabBtnVacantes');
  const btnSol = document.getElementById('tabBtnSolicitudes');
  const secVac = document.getElementById('sectionVacantesList');
  const secSol = document.getElementById('sectionSolicitudesList');

  if (tabName === 'vacantesList') {
    btnVac.classList.add('active');
    btnSol.classList.remove('active');
    secVac.style.display = 'block';
    secSol.style.display = 'none';
  } else {
    btnSol.classList.add('active');
    btnVac.classList.remove('active');
    secSol.style.display = 'block';
    secVac.style.display = 'none';
  }
}

function initVacantesView() {
  const modal = document.getElementById('vacanteModal');
  const btnCrear = document.getElementById('btnCrearVacante');
  const btnClose = document.getElementById('closeVacanteModal');
  const btnCloseBtn = document.getElementById('closeVacanteBtn');
  const form = document.getElementById('formVacanteModal');
  const tbody = document.getElementById('vacantesTbody');

  if (tbody && typeof Sortable !== 'undefined') {
    if (tbody._sortable) {
      try { tbody._sortable.destroy(); } catch(e){}
    }
    tbody._sortable = new Sortable(tbody, {
      animation: 150,
      handle: 'td:first-child',
      ghostClass: 'sortable-ghost',
      dragClass: 'sortable-drag',
      forceFallback: true,
      fallbackOnBody: true,
      axis: 'y',
      onEnd: function () {
        guardarOrdenVacantes();
      }
    });
  }

  function guardarOrdenVacantes() {
    const rows = document.querySelectorAll('.vacante-row');
    const orden = [];
    rows.forEach((row, index) => {
      const newOrden = index + 1;
      const numEl = row.querySelector('.vac-orden-num');
      if (numEl) numEl.textContent = newOrden;
      orden.push({
        id: parseInt(row.dataset.id),
        orden: newOrden
      });
    });

    fetch(BASE_PATH + '/controllers/vacantes_reordenar.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ vacantes: orden })
    })
    .then(r => r.json())
    .then(res => {
      if (res.success) {
        showToast('Orden de vacantes actualizado', 'success');
      } else {
        showToast(res.error || 'Error al reordenar vacantes', 'error');
      }
    });
  }

  if (!modal || !form) return;

  function openModal(data = null) {
    if (data) {
      document.getElementById('modalVacanteTitle').textContent = 'Editar Vacante: ' + data.titulo;
      document.getElementById('vacanteId').value = data.id;
      document.getElementById('vacanteOrden').value = data.orden || 1;
      document.getElementById('vacanteTag').value = data.tag || '';
      document.getElementById('vacanteTitulo').value = data.titulo || '';
      document.getElementById('vacanteSubtitulo').value = data.subtitulo_italic || '';
      document.getElementById('vacanteModalidad').value = data.modalidad || '100% Remoto · Tiempo completo';
      document.getElementById('vacanteDescripcion').value = data.descripcion || '';
    } else {
      document.getElementById('modalVacanteTitle').textContent = 'Crear Nueva Vacante de Empleo';
      form.reset();
      document.getElementById('vacanteId').value = 0;
    }
    modal.style.display = 'flex';
  }

  btnCrear?.addEventListener('click', () => openModal());
  btnClose?.addEventListener('click', () => modal.style.display = 'none');
  btnCloseBtn?.addEventListener('click', () => modal.style.display = 'none');

  // Delegación de eventos para botones Editar
  document.querySelectorAll('.btn-editar-vac').forEach(btn => {
    btn.onclick = function() {
      const data = {
        id: this.dataset.id,
        orden: this.dataset.orden,
        tag: this.dataset.tag,
        titulo: this.dataset.titulo,
        subtitulo_italic: this.dataset.subtitulo,
        modalidad: this.dataset.modalidad,
        descripcion: this.dataset.descripcion
      };
      openModal(data);
    };
  });

  // Toggle estado
  document.querySelectorAll('.btn-toggle-vac').forEach(btn => {
    btn.onclick = function() {
      const id = this.dataset.id;
      fetch(BASE_PATH + '/controllers/vacantes_eliminar.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: id, action: 'toggle' })
      })
      .then(r => r.json())
      .then(res => {
        if (res.success) {
          showToast('Estado de vacante actualizado', 'success');
          setTimeout(() => window.location.reload(), 400);
        } else {
          showToast(res.error || 'Error al cambiar estado', 'error');
        }
      });
    };
  });

  // Eliminar
  document.querySelectorAll('.btn-eliminar-vac').forEach(btn => {
    btn.onclick = function() {
      const id = this.dataset.id;
      const titulo = this.dataset.titulo;
      if (!confirm(`¿Estás seguro de que deseas eliminar la vacante "${titulo}"?`)) return;

      fetch(BASE_PATH + '/controllers/vacantes_eliminar.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: id, action: 'delete' })
      })
      .then(r => r.json())
      .then(res => {
        if (res.success) {
          showToast('Vacante eliminada correctamente', 'success');
          setTimeout(() => window.location.reload(), 400);
        } else {
          showToast(res.error || 'Error al eliminar vacante', 'error');
        }
      });
    };
  });

  // Form Submit
  form.onsubmit = function(e) {
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
        showToast(res.message, 'success');
        modal.style.display = 'none';
        setTimeout(() => window.location.reload(), 500);
      } else {
        showToast(res.error || 'Error al guardar vacante', 'error');
      }
    });
  };

  // Cambio de estado de solicitudes
  document.querySelectorAll('.select-sol-estado').forEach(sel => {
    sel.onchange = function() {
      const id = this.dataset.id;
      const estado = this.value;
      fetch(BASE_PATH + '/controllers/solicitud_estado.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: id, estado: estado })
      })
      .then(r => r.json())
      .then(res => {
        if (res.success) {
          showToast('Estado de postulación actualizado', 'success');
        } else {
          showToast(res.error || 'Error al actualizar estado', 'error');
        }
      });
    };
  });
}

document.addEventListener('DOMContentLoaded', initVacantesView);
document.addEventListener('turbo:load', initVacantesView);
document.addEventListener('turbo:render', initVacantesView);
</script>

<?php include("./../layout/footerAdmin.php"); ?>
