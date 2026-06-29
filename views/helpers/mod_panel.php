<?php
// ============================================================
// PANEL DE MODERACIÓN (solo superadmin)
// Variables esperadas antes del include:
//   $modTipo   : 'lector' | 'admin'
//   $modUserId : id del usuario objetivo
//   $modBanRow : fila con baneado_hasta, baneado_permanente, baneado_motivo
// ============================================================
if (!esSuperAdminActual()) return;
require_once(__DIR__ . '/moderacion.php');

$modBaneado   = estaBaneado($modBanRow);
$modEstadoTxt = textoBaneo($modBanRow);
$modDuraciones = duracionesBaneo();
?>
<div class="mod-panel" id="modPanel"
     data-tipo="<?= htmlspecialchars($modTipo) ?>"
     data-userid="<?= (int)$modUserId ?>"
     data-base="<?= basePath() ?>">
  <div class="mod-panel-head">
    <span class="mod-panel-title"><i class="bi bi-shield-lock-fill"></i> Moderación</span>
    <span class="mod-estado <?= $modBaneado ? 'is-banned' : '' ?>" id="modEstado">
      <?= $modBaneado ? '<i class="bi bi-slash-circle"></i> ' . htmlspecialchars($modEstadoTxt) : 'Cuenta activa' ?>
    </span>
  </div>

  <div class="mod-panel-body">
    <div class="mod-ban-form">
      <label class="mod-label">Duración de la suspensión</label>
      <select id="modDuracion" class="input mod-select">
        <?php foreach ($modDuraciones as $key => $d): ?>
          <option value="<?= $key ?>"><?= htmlspecialchars($d['label']) ?></option>
        <?php endforeach; ?>
      </select>
      <input type="text" id="modMotivo" class="input mod-motivo" maxlength="255" placeholder="Motivo (opcional)">
      <div class="mod-actions">
        <button type="button" class="mod-btn mod-btn-ban" id="modBtnBan">
          <i class="bi bi-slash-circle"></i> Suspender
        </button>
        <button type="button" class="mod-btn mod-btn-unban" id="modBtnUnban" <?= $modBaneado ? '' : 'style="display:none;"' ?>>
          <i class="bi bi-check-circle"></i> Quitar suspensión
        </button>
      </div>
    </div>
  </div>
</div>

<style>
.mod-panel {
  margin-top: 18px; border: 1px solid var(--accent);
  border-radius: 12px; padding: 16px 18px; background: rgba(239,51,99,0.05);
}
.mod-panel-head { display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; margin-bottom: 12px; }
.mod-panel-title { font-weight: 700; color: var(--accent); }
.mod-estado { font-size: 0.85rem; color: var(--muted); }
.mod-estado.is-banned { color: #ef3333; font-weight: 600; }
.mod-ban-form { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; }
.mod-select { max-width: 200px; }
.mod-motivo { flex: 1; min-width: 180px; }
.mod-actions { display: flex; gap: 8px; }
.mod-btn {
  border: none; border-radius: 8px; padding: 9px 16px; font-weight: 600;
  font-size: 0.88rem; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;
}
.mod-btn-ban { background: #ef3333; color: #fff; }
.mod-btn-ban:hover { background: #d32a2a; }
.mod-btn-unban { background: transparent; color: #28a745; border: 1px solid #28a745; }
.mod-btn-unban:hover { background: #28a745; color: #fff; }
.mod-btn:disabled { opacity: 0.6; cursor: not-allowed; }
</style>

<script>
(() => {
  const panel = document.getElementById('modPanel');
  if (!panel) return;
  const base = panel.dataset.base;
  const tipo = panel.dataset.tipo;
  const userId = panel.dataset.userid;
  const estadoEl = document.getElementById('modEstado');
  const btnBan = document.getElementById('modBtnBan');
  const btnUnban = document.getElementById('modBtnUnban');
  const selDur = document.getElementById('modDuracion');
  const inMotivo = document.getElementById('modMotivo');

  async function postMod(body) {
    const res = await fetch(base + '/controllers/moderar_usuario.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body
    });
    return res.json();
  }

  btnBan.addEventListener('click', async () => {
    const durTxt = selDur.options[selDur.selectedIndex].text;
    if (!confirm(`¿Suspender a este usuario por: ${durTxt}?`)) return;
    btnBan.disabled = true;
    try {
      const data = await postMod(`action=ban&tipo=${tipo}&user_id=${userId}&duracion=${encodeURIComponent(selDur.value)}&motivo=${encodeURIComponent(inMotivo.value)}`);
      if (data.ok) {
        estadoEl.innerHTML = '<i class="bi bi-slash-circle"></i> ' + (data.estado || 'Suspendido');
        estadoEl.classList.add('is-banned');
        btnUnban.style.display = '';
      }
      alert(data.msg);
    } catch (e) { alert('Error de red.'); }
    btnBan.disabled = false;
  });

  btnUnban.addEventListener('click', async () => {
    if (!confirm('¿Quitar la suspensión a este usuario?')) return;
    btnUnban.disabled = true;
    try {
      const data = await postMod(`action=unban&tipo=${tipo}&user_id=${userId}`);
      if (data.ok) {
        estadoEl.innerHTML = 'Cuenta activa';
        estadoEl.classList.remove('is-banned');
        btnUnban.style.display = 'none';
      }
      alert(data.msg);
    } catch (e) { alert('Error de red.'); }
    btnUnban.disabled = false;
  });
})();
</script>
