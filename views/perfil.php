<?php
include("./../layout/header.php");
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
<?php
require_once("./../data/conexion.php");
if(!isset($_SESSION['usuario'])){
    header('Location: ' . basePath() . '/login');
    exit;
}
$tipoUsuario = $_SESSION['tipo'] ?? 'lector';
if($tipoUsuario === 'admin'){
    $stmtUser = $con->prepare("SELECT *, registro AS creado FROM usuarios WHERE usuario = ?");
} else {
    $stmtUser = $con->prepare("SELECT * FROM lectores WHERE usuario = ?");
}
$stmtUser->bind_param("s", $_SESSION['usuario']);
$stmtUser->execute();
$user = $stmtUser->get_result()->fetch_assoc();
$palabras  = explode(' ', $user['nombre']);
$iniciales = strtoupper(substr($palabras[0],0,1) . (isset($palabras[1]) ? substr($palabras[1],0,1) : ''));
$fechaRegistro = ($user['creado'] ?? $user['registro'] ?? null) ? date('M Y', strtotime($user['creado'] ?? $user['registro'])) : 'N/A';
$avatarActual = null;
if($user['avatar_id'] ?? null){
    $stmtAv = $con->prepare("SELECT imagen FROM avatares_perfil WHERE id_avatar = ?");
    $stmtAv->bind_param("i", $user['avatar_id']);
    $stmtAv->execute();
    $avRow = $stmtAv->get_result()->fetch_assoc();
    if($avRow) $avatarActual = $avRow['imagen'];
}
$avatares = $con->query("SELECT * FROM avatares_perfil WHERE activo = 1 ORDER BY creado DESC")->fetch_all(MYSQLI_ASSOC);
$ent = $user['entidad'] ?? '';
$estadosMX = ['AGU'=>'Aguascalientes','BCN'=>'Baja California','BCS'=>'Baja California Sur','CAM'=>'Campeche','CHP'=>'Chiapas','CHH'=>'Chihuahua','CMX'=>'Ciudad de México','COA'=>'Coahuila','COL'=>'Colima','DUR'=>'Durango','GUA'=>'Guanajuato','GRO'=>'Guerrero','HID'=>'Hidalgo','JAL'=>'Jalisco','MEX'=>'Estado de México','MIC'=>'Michoacán','MOR'=>'Morelos','NAY'=>'Nayarit','NLE'=>'Nuevo León','OAX'=>'Oaxaca','PUE'=>'Puebla','QUE'=>'Querétaro','ROO'=>'Quintana Roo','SLP'=>'San Luis Potosí','SIN'=>'Sinaloa','SON'=>'Sonora','TAB'=>'Tabasco','TAM'=>'Tamaulipas','TLA'=>'Tlaxcala','VER'=>'Veracruz','YUC'=>'Yucatán','ZAC'=>'Zacatecas'];
?>

<!-- Notificaciones -->
<?php if(isset($_GET['ok'])): ?>
<div class="perfil-toast perfil-toast--ok"><i class="bi bi-check-circle-fill"></i> Perfil actualizado correctamente.</div>
<?php endif; ?>
<?php if(isset($_GET['error'])): ?>
<?php $errores = ['1'=>'La contraseña actual es incorrecta.','2'=>'Error al actualizar.','3'=>'Ese nombre de usuario ya está en uso.','4'=>'El nombre de usuario contiene caracteres inválidos.','5'=>'Contraseña incorrecta. No se eliminó la cuenta.','6'=>'Error al eliminar la cuenta.']; ?>
<div class="perfil-toast perfil-toast--err"><i class="bi bi-exclamation-circle-fill"></i> <?= $errores[$_GET['error']] ?? 'Error desconocido.' ?></div>
<?php endif; ?>

<!-- HERO BANNER -->
<div class="perfil-hero">
  <div class="perfil-hero-bg"></div>
  <div class="perfil-hero-content">
    <!-- Avatar -->
    <div class="perfil-avatar-wrap">
      <div class="perfil-avatar" id="perfilAvatar">
        <?php if($tipoUsuario === 'admin' && !empty($user['foto_personal'])): ?>
          <img src="<?= imageUrl($user['foto_personal']) ?>" alt="Foto personal">
        <?php elseif($avatarActual): ?>
          <img src="<?= imageUrl($avatarActual) ?>" alt="Avatar">
        <?php else: ?>
          <span><?= htmlspecialchars($iniciales) ?></span>
        <?php endif; ?>
      </div>
      <button type="button" class="perfil-camera" id="openAvatarPicker" title="Cambiar avatar">
        <i class="bi bi-camera-fill"></i>
      </button>
    </div>
    <!-- Info -->
    <div class="perfil-hero-info">
      <h1 class="perfil-hero-nombre"><?= htmlspecialchars($user['nombre']) ?></h1>
      <p class="perfil-hero-username">@<?= htmlspecialchars($user['usuario']) ?></p>
      <div class="perfil-hero-badges">
        <span class="perfil-badge"><i class="bi bi-calendar3"></i> Miembro desde <?= $fechaRegistro ?></span>
        <?php if($tipoUsuario === 'admin'): ?>
          <span class="perfil-badge perfil-badge--admin"><i class="bi bi-shield-check-fill"></i> Editor</span>
        <?php endif; ?>
      </div>
    </div>
    <!-- Cerrar sesión -->
    <a href="<?= basePath() ?>/controllers/logoutcontroller.php" class="perfil-hero-logout" data-turbo="false">
      <i class="bi bi-box-arrow-right"></i> Cerrar sesión
    </a>
  </div>
</div>

<!-- TABS -->
<div class="perfil-page">
  <div class="perfil-tabs" role="tablist">
    <button class="perfil-tab active" data-tab="cuenta" role="tab"><i class="bi bi-person-fill"></i> Cuenta</button>
    <button class="perfil-tab" data-tab="personal" role="tab"><i class="bi bi-info-circle-fill"></i> Información</button>
    <?php if($tipoUsuario === 'admin'): ?>
    <button class="perfil-tab" data-tab="publico" role="tab"><i class="bi bi-globe2"></i> Perfil Público</button>
    <?php endif; ?>
    <button class="perfil-tab perfil-tab--danger" data-tab="peligro" role="tab"><i class="bi bi-shield-exclamation"></i> Seguridad</button>
  </div>

  <form id="perfilForm" action="<?= basePath() ?>/controllers/perfilcontroller.php" method="POST" enctype="multipart/form-data">

    <!-- TAB: CUENTA -->
    <div class="perfil-tab-panel active" id="tab-cuenta">
      <div class="perfil-card">
        <div class="perfil-card-header">
          <i class="bi bi-person-fill"></i>
          <div>
            <h2>Datos de Cuenta</h2>
            <p>Tu nombre de usuario y correo electrónico.</p>
          </div>
        </div>
        <div class="perfil-fields">
          <div class="perfil-field-group">
            <label for="nombre_usuario">Nombre de Usuario</label>
            <div class="perfil-input-prefix">
              <span class="prefix">@</span>
              <input type="text" id="nombre_usuario" name="nombre_usuario" class="input" value="<?= htmlspecialchars($user['usuario']) ?>"
                required pattern="[a-zA-Z0-9_.]{3,30}" title="3–30 caracteres: letras, números, puntos y guiones bajos.">
            </div>
            <small>3–30 caracteres. Solo letras, números, puntos y guiones bajos.</small>
          </div>
          <div class="perfil-field-group">
            <label for="correo">Correo Electrónico</label>
            <input type="email" id="correo" name="correo" class="input" value="<?= htmlspecialchars($user['correo']) ?>" required>
          </div>
          <div class="perfil-field-group">
            <label>Contraseña</label>
            <button type="button" id="togglePass" class="perfil-toggle-pass">
              <i class="bi bi-key-fill"></i> Cambiar contraseña
              <i class="bi bi-chevron-down" id="togglePassIcon" style="margin-left:auto;transition:transform .2s;"></i>
            </button>
            <div id="passFields" style="display:none;">
              <div class="perfil-fields" style="margin-top:12px;">
                <div class="perfil-field-group">
                  <label for="pass_actual">Contraseña Actual</label>
                  <input type="password" id="pass_actual" name="pass_actual" class="input" placeholder="••••••••">
                </div>
                <div class="perfil-field-group">
                  <label for="pass_nueva">Nueva Contraseña</label>
                  <input type="password" id="pass_nueva" name="pass_nueva" class="input" placeholder="••••••••">
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="perfil-card-footer">
          <button type="submit" class="perfil-btn-save"><i class="bi bi-floppy-fill"></i> Guardar Cambios</button>
        </div>
      </div>
    </div>

    <!-- TAB: INFORMACIÓN PERSONAL -->
    <div class="perfil-tab-panel" id="tab-personal">
      <div class="perfil-card">
        <div class="perfil-card-header">
          <i class="bi bi-info-circle-fill"></i>
          <div>
            <h2>Información Personal</h2>
            <p>Datos opcionales sobre ti.</p>
          </div>
        </div>
        <div class="perfil-fields">
          <div class="perfil-fields-row">
            <div class="perfil-field-group">
              <label for="sexo">Sexo</label>
              <select id="sexo" name="sexo" class="input">
                <option value="">Seleccionar...</option>
                <option value="masculino" <?= ($user['sexo'] ?? '') == 'masculino' ? 'selected' : '' ?>>Masculino</option>
                <option value="femenino" <?= ($user['sexo'] ?? '') == 'femenino' ? 'selected' : '' ?>>Femenino</option>
                <option value="otro" <?= ($user['sexo'] ?? '') == 'otro' ? 'selected' : '' ?>>Otro</option>
              </select>
            </div>
            <div class="perfil-field-group">
              <label for="nacimiento">Fecha de Nacimiento</label>
              <input type="date" id="nacimiento" name="nacimiento" class="input" value="<?= htmlspecialchars($user['fecha_nacimiento'] ?? '') ?>">
            </div>
          </div>
          <div class="perfil-field-group">
            <label for="entidad">Entidad Federativa</label>
            <select id="entidad" name="entidad" class="input">
              <option value="">Seleccionar...</option>
              <?php foreach($estadosMX as $clave => $nombre): ?>
              <option value="<?= $clave ?>" <?= $ent == $clave ? 'selected' : '' ?>><?= $nombre ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="perfil-card-footer">
          <button type="submit" class="perfil-btn-save"><i class="bi bi-floppy-fill"></i> Guardar Cambios</button>
        </div>
      </div>
    </div>

    <!-- TAB: PERFIL PÚBLICO (solo admins) -->
    <?php if($tipoUsuario === 'admin'): ?>
    <div class="perfil-tab-panel" id="tab-publico">
      <div class="perfil-card">
        <div class="perfil-card-header">
          <i class="bi bi-globe2"></i>
          <div>
            <h2>Perfil Público</h2>
            <p>Lo que verán los lectores en tu página de autor.</p>
          </div>
        </div>
        <div class="perfil-fields">
          <div class="perfil-field-group">
            <label for="biografia">Biografía</label>
            <textarea id="biografia" name="biografia" class="input" rows="4" style="resize:vertical;" placeholder="Cuéntale al mundo sobre ti..."><?= htmlspecialchars($user['biografia'] ?? '') ?></textarea>
          </div>
          <div class="perfil-fields-row">
            <div class="perfil-field-group">
              <label for="link_twitter"><i class="bi bi-twitter-x"></i> Twitter / X</label>
              <input type="url" id="link_twitter" name="link_twitter" class="input" value="<?= htmlspecialchars($user['link_twitter'] ?? '') ?>" placeholder="https://x.com/tu_usuario">
            </div>
            <div class="perfil-field-group">
              <label for="link_instagram"><i class="bi bi-instagram"></i> Instagram</label>
              <input type="url" id="link_instagram" name="link_instagram" class="input" value="<?= htmlspecialchars($user['link_instagram'] ?? '') ?>" placeholder="https://instagram.com/tu_usuario">
            </div>
          </div>
          <a href="<?= basePath() ?>/autor/<?= $user['id_u'] ?>" target="_blank" class="perfil-link-publico">
            <i class="bi bi-box-arrow-up-right"></i> Ver mi perfil público
          </a>
        </div>
        <div class="perfil-card-footer">
          <button type="submit" class="perfil-btn-save"><i class="bi bi-floppy-fill"></i> Guardar Cambios</button>
        </div>
      </div>
    </div>
    <?php endif; ?>

  </form><!-- /form -->

  <!-- TAB: SEGURIDAD / ZONA DE PELIGRO (fuera del form para evitar submit accidental) -->
  <div class="perfil-tab-panel" id="tab-peligro">
    <div class="perfil-card perfil-card--danger">
      <div class="perfil-card-header">
        <i class="bi bi-shield-exclamation" style="color:#ef3333;"></i>
        <div>
          <h2 style="color:#ef3333;">Zona de Peligro</h2>
          <p>Acciones irreversibles en tu cuenta.</p>
        </div>
      </div>
      <div class="perfil-danger-row">
        <div>
          <strong>Eliminar mi cuenta</strong>
          <p>Se borrarán permanentemente tus datos, comentarios y likes. Esta acción <strong>no se puede deshacer</strong>.</p>
        </div>
        <button type="button" class="perfil-btn-eliminar" id="btnEliminarCuenta">
          <i class="bi bi-trash3-fill"></i> Eliminar cuenta
        </button>
      </div>
    </div>
  </div>

</div><!-- /perfil-page -->

<!-- MODAL: Confirmar eliminación -->
<div id="modalEliminar" class="perfil-modal-overlay" style="display:none;">
  <div class="perfil-modal perfil-modal--danger">
    <div class="perfil-modal-icon"><i class="bi bi-exclamation-octagon-fill"></i></div>
    <h3>¿Eliminar tu cuenta?</h3>
    <p>Esta acción es permanente e irreversible. Escribe tu contraseña para confirmar.</p>
    <form id="formEliminar" action="<?= basePath() ?>/controllers/eliminar_cuenta.php" method="POST">
      <input type="password" name="pass_confirmar" id="passConfirmar" class="input" placeholder="Tu contraseña..." required
        style="width:100%; margin-bottom:16px;">
      <div style="display:flex; gap:10px;">
        <button type="button" id="cancelarEliminar" class="perfil-modal-btn-cancel">Cancelar</button>
        <button type="submit" class="perfil-modal-btn-confirm"><i class="bi bi-trash3-fill"></i> Eliminar cuenta</button>
      </div>
    </form>
  </div>
</div>

<!-- MODAL: Selector de avatar -->
<div id="avatarModal" class="perfil-modal-overlay" style="display:none;">
  <div class="perfil-modal">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
      <h3 style="margin:0; font-size:1.1rem;">Elige tu avatar</h3>
      <button id="closeAvatarModal" style="background:none; border:none; font-size:1.5rem; cursor:pointer; color:var(--text);">&times;</button>
    </div>
    <?php if($tipoUsuario === 'admin'): ?>
    <div style="margin-bottom:16px; padding:12px; background:rgba(239,51,99,0.08); border-radius:10px; border:1px solid var(--accent);">
      <label class="avatar-upload-btn" style="display:flex; align-items:center; gap:8px; cursor:pointer; color:var(--accent); font-weight:600;">
        <i class="bi bi-cloud-arrow-up"></i> Subir foto personal
        <input type="file" id="modalFotoInput" accept="image/jpeg,image/png,image/webp" style="display:none;">
      </label>
      <div id="modalFotoPreview" style="display:none; margin-top:12px;">
        <div style="max-height:280px; overflow:hidden; border-radius:8px;">
          <img id="modalFotoImg" style="max-width:100%; display:block;">
        </div>
        <div style="margin-top:10px; display:flex; gap:8px;">
          <button type="button" id="confirmCropBtn" class="perfil-btn-save" style="flex:1; font-size:0.88rem; padding:9px;">Confirmar recorte</button>
          <button type="button" id="cancelCropBtn" style="flex:1; padding:9px 16px; border:1px solid var(--border); background:var(--bg); color:var(--text); border-radius:8px; cursor:pointer;">Cancelar</button>
        </div>
      </div>
    </div>
    <?php endif; ?>
    <div style="display:grid; grid-template-columns:repeat(3, 1fr); gap:10px;">
      <?php if(empty($avatares)): ?>
        <p style="grid-column:1/-1; color:var(--muted); text-align:center;">No hay avatares disponibles aún.</p>
      <?php endif; ?>
      <?php foreach($avatares as $av): ?>
        <div class="avatar-option <?= ($user['avatar_id'] ?? 0) == $av['id_avatar'] ? 'avatar-selected' : '' ?>" data-id="<?= $av['id_avatar'] ?>"
          style="cursor:pointer; border-radius:10px; overflow:hidden; border:3px solid transparent; transition:border-color 0.2s;">
          <img src="<?= imageUrl($av['imagen']) ?>" style="width:100%; height:90px; object-fit:cover; display:block;">
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<style>
/* ============================================================
   PERFIL — Diseño 2.0
   ============================================================ */

/* TOAST */
.perfil-toast {
  position: fixed; top: 80px; left: 50%; transform: translateX(-50%);
  padding: 12px 24px; border-radius: 50px; font-size: .9rem; font-weight: 600;
  display: flex; align-items: center; gap: 8px;
  z-index: 10000; box-shadow: 0 4px 24px rgba(0,0,0,.18);
  animation: toastIn .3s ease;
}
@keyframes toastIn { from { opacity:0; transform:translateX(-50%) translateY(-10px); } to { opacity:1; transform:translateX(-50%) translateY(0); } }
.perfil-toast--ok  { background:#1a7f37; color:#fff; }
.perfil-toast--err { background:#cf222e; color:#fff; }

/* HERO */
.perfil-hero {
  position: relative; overflow: hidden;
  padding: 60px 24px 80px;
  margin-bottom: -48px;
}
.perfil-hero-bg {
  position: absolute; inset: 0;
  background: linear-gradient(135deg, var(--accent) 0%, #7c1d5e 100%);
  opacity: .12;
}
.perfil-hero::after {
  content:''; position:absolute; bottom:0; left:0; right:0; height:60px;
  background: var(--bg);
  clip-path: ellipse(55% 100% at 50% 100%);
}
.perfil-hero-content {
  position: relative; z-index: 1;
  display: flex; align-items: center; gap: 28px;
  max-width: 800px; margin: 0 auto;
  flex-wrap: wrap;
}
.perfil-hero-info { flex: 1; min-width: 200px; }
.perfil-hero-nombre { font-size: 1.8rem; font-weight: 800; margin: 0 0 4px; }
.perfil-hero-username { color: var(--muted); font-size: .95rem; margin: 0 0 12px; }
.perfil-hero-badges { display: flex; flex-wrap: wrap; gap: 8px; }
.perfil-badge {
  display: inline-flex; align-items: center; gap: 5px;
  padding: 4px 12px; border-radius: 50px; font-size: .78rem; font-weight: 600;
  background: var(--card-bg); border: 1px solid var(--border); color: var(--muted);
}
.perfil-badge--admin { background: rgba(239,51,99,.12); border-color: var(--accent); color: var(--accent); }
.perfil-hero-logout {
  display: inline-flex; align-items: center; gap: 7px;
  padding: 9px 18px; border-radius: 50px; font-size: .88rem; font-weight: 600;
  border: 1.5px solid var(--accent); color: var(--accent); text-decoration: none;
  transition: background .15s, color .15s;
  white-space: nowrap;
}
.perfil-hero-logout:hover { background: var(--accent); color: #fff; }

/* AVATAR */
.perfil-avatar-wrap { position: relative; flex-shrink: 0; }
.perfil-avatar {
  width: 96px; height: 96px; border-radius: 50%;
  background: linear-gradient(135deg, var(--accent), #7c1d5e);
  display: flex; align-items: center; justify-content: center;
  font-size: 2.4rem; font-weight: 800; color: #fff;
  overflow: hidden;
  border: 4px solid var(--bg);
  box-shadow: 0 4px 20px rgba(239,51,99,.3);
}
.perfil-avatar img { width: 100%; height: 100%; object-fit: cover; }
.perfil-camera {
  position: absolute; bottom: 2px; right: 2px;
  width: 30px; height: 30px; border-radius: 50%;
  background: var(--accent); color: #fff; border: 2px solid var(--bg);
  font-size: .85rem; cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  transition: transform .15s;
}
.perfil-camera:hover { transform: scale(1.12); }

/* PAGE & TABS */
.perfil-page {
  max-width: 760px; margin: 0 auto 60px;
  padding: 0 16px;
}
.perfil-tabs {
  display: flex; gap: 4px; flex-wrap: wrap;
  border-bottom: 2px solid var(--border);
  margin-bottom: 28px;
}
.perfil-tab {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 10px 18px; border-radius: 8px 8px 0 0;
  border: none; background: transparent;
  font-size: .88rem; font-weight: 600; cursor: pointer;
  color: var(--muted); transition: color .15s, background .15s;
  border-bottom: 2px solid transparent; margin-bottom: -2px;
}
.perfil-tab:hover { color: var(--text); }
.perfil-tab.active { color: var(--accent); border-bottom-color: var(--accent); }
.perfil-tab--danger:hover { color: #ef3333; }
.perfil-tab--danger.active { color: #ef3333; border-bottom-color: #ef3333; }

/* PANELS */
.perfil-tab-panel { display: none; }
.perfil-tab-panel.active { display: block; }

/* CARD */
.perfil-card {
  background: var(--card-bg);
  border: 1px solid var(--border);
  border-radius: 16px;
  overflow: hidden;
}
.perfil-card--danger { border-color: rgba(239,51,51,.3); }
.perfil-card-header {
  display: flex; align-items: flex-start; gap: 14px;
  padding: 22px 24px;
  border-bottom: 1px solid var(--border);
  font-size: 1.6rem; color: var(--accent);
}
.perfil-card--danger .perfil-card-header { border-color: rgba(239,51,51,.2); }
.perfil-card-header h2 { margin: 0 0 2px; font-size: 1.05rem; font-weight: 700; }
.perfil-card-header p  { margin: 0; font-size: .83rem; color: var(--muted); }
.perfil-card-footer {
  padding: 16px 24px;
  border-top: 1px solid var(--border);
  display: flex; justify-content: flex-end;
}

/* FIELDS */
.perfil-fields { padding: 20px 24px; display: flex; flex-direction: column; gap: 18px; }
.perfil-fields-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.perfil-field-group { display: flex; flex-direction: column; gap: 6px; }
.perfil-field-group label { font-size: .85rem; font-weight: 600; color: var(--text); }
.perfil-field-group small { font-size: .76rem; color: var(--muted); }
.perfil-input-prefix { position: relative; display: flex; align-items: center; }
.perfil-input-prefix .prefix {
  position: absolute; left: 12px; font-weight: 700; color: var(--muted); pointer-events: none;
}
.perfil-input-prefix .input { padding-left: 28px; }

/* Toggle pass */
.perfil-toggle-pass {
  display: flex; align-items: center; gap: 8px;
  padding: 11px 14px; border-radius: 8px;
  border: 1px solid var(--border); background: var(--card-bg);
  color: var(--accent); font-weight: 600; font-size: .9rem; cursor: pointer;
  transition: background .15s;
}
.perfil-toggle-pass:hover { background: rgba(239,51,99,.07); }

/* Danger zone row */
.perfil-danger-row {
  display: flex; align-items: center; justify-content: space-between; gap: 24px;
  padding: 20px 24px; flex-wrap: wrap;
}
.perfil-danger-row > div > strong { display: block; margin-bottom: 4px; }
.perfil-danger-row > div > p { margin: 0; font-size: .85rem; color: var(--muted); }

/* Buttons */
.perfil-btn-save {
  display: inline-flex; align-items: center; gap: 7px;
  padding: 10px 22px; border-radius: 8px;
  background: var(--accent); color: #fff; border: none;
  font-size: .9rem; font-weight: 700; cursor: pointer;
  transition: opacity .15s, transform .12s;
}
.perfil-btn-save:hover { opacity: .9; transform: translateY(-1px); }
.perfil-btn-eliminar {
  display: inline-flex; align-items: center; gap: 7px;
  padding: 10px 20px; border-radius: 8px;
  border: 1.5px solid #ef3333; background: transparent;
  color: #ef3333; font-weight: 700; font-size: .88rem; cursor: pointer;
  white-space: nowrap; transition: background .15s, color .15s;
}
.perfil-btn-eliminar:hover { background: #ef3333; color: #fff; }

/* Link público */
.perfil-link-publico {
  display: inline-flex; align-items: center; gap: 6px;
  color: var(--accent); font-size: .88rem; font-weight: 600; text-decoration: none;
}
.perfil-link-publico:hover { text-decoration: underline; }

/* MODAL */
.perfil-modal-overlay {
  position: fixed; inset: 0; z-index: 9999;
  background: rgba(0,0,0,.65);
  display: flex; align-items: center; justify-content: center;
  backdrop-filter: blur(4px);
}
.perfil-modal {
  background: var(--card-bg); border-radius: 20px;
  padding: 32px; max-width: 440px; width: 92%;
  box-shadow: 0 20px 60px rgba(0,0,0,.3);
  animation: modalIn .25s ease;
}
@keyframes modalIn { from { opacity:0; transform:scale(.95); } to { opacity:1; transform:scale(1); } }
.perfil-modal--danger { border: 1.5px solid rgba(239,51,51,.4); }
.perfil-modal-icon { font-size: 2.5rem; color: #ef3333; text-align: center; margin-bottom: 12px; }
.perfil-modal h3 { margin: 0 0 8px; font-size: 1.1rem; text-align: center; }
.perfil-modal p { color: var(--muted); font-size: .9rem; text-align: center; margin-bottom: 20px; }
.perfil-modal-btn-cancel {
  flex: 1; padding: 10px; border-radius: 8px;
  border: 1px solid var(--border); background: transparent; color: var(--text); cursor: pointer; font-size: .9rem;
}
.perfil-modal-btn-confirm {
  flex: 1; padding: 10px; border-radius: 8px;
  border: none; background: #ef3333; color: #fff; font-weight: 700; cursor: pointer; font-size: .9rem;
  display: flex; align-items: center; justify-content: center; gap: 7px;
}

/* AVATAR PICKER */
.avatar-option:hover { border-color: var(--accent) !important; }
.avatar-option.avatar-selected { border-color: var(--accent) !important; }

/* Responsive */
@media (max-width: 600px) {
  .perfil-hero-content { flex-direction: column; text-align: center; }
  .perfil-hero-badges { justify-content: center; }
  .perfil-fields-row { grid-template-columns: 1fr; }
  .perfil-danger-row { flex-direction: column; align-items: flex-start; }
  .perfil-tabs { gap: 2px; }
  .perfil-tab { padding: 8px 12px; font-size: .8rem; }
}
</style>

<script>
// ---- TABS ----
document.querySelectorAll('.perfil-tab').forEach(tab => {
  tab.addEventListener('click', () => {
    document.querySelectorAll('.perfil-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.perfil-tab-panel').forEach(p => p.classList.remove('active'));
    tab.classList.add('active');
    const panel = document.getElementById('tab-' + tab.dataset.tab);
    if (panel) panel.classList.add('active');
  });
});

// ---- TOGGLE CONTRASEÑA ----
document.getElementById('togglePass').addEventListener('click', function(){
  const fields = document.getElementById('passFields');
  const icon   = document.getElementById('togglePassIcon');
  const open   = fields.style.display === 'none';
  fields.style.display = open ? 'block' : 'none';
  icon.style.transform  = open ? 'rotate(180deg)' : 'rotate(0deg)';
  if (!open) {
    document.getElementById('pass_actual').value = '';
    document.getElementById('pass_nueva').value  = '';
  }
});

// ---- MODAL ELIMINAR ----
const btnEliminar    = document.getElementById('btnEliminarCuenta');
const modalEliminar  = document.getElementById('modalEliminar');
const cancelarEliminar = document.getElementById('cancelarEliminar');
if (btnEliminar && modalEliminar) {
  btnEliminar.addEventListener('click', () => { modalEliminar.style.display = 'flex'; });
  cancelarEliminar.addEventListener('click', () => { modalEliminar.style.display = 'none'; });
  modalEliminar.addEventListener('click', e => { if (e.target === modalEliminar) modalEliminar.style.display = 'none'; });
}

// ---- MODAL AVATAR ----
const modalAv = document.getElementById('avatarModal');
document.getElementById('openAvatarPicker').addEventListener('click', () => { modalAv.style.display = 'flex'; });
document.getElementById('closeAvatarModal').addEventListener('click', () => { modalAv.style.display = 'none'; });
modalAv.addEventListener('click', e => { if (e.target === modalAv) modalAv.style.display = 'none'; });

// ---- SELECCIONAR AVATAR ----
document.querySelectorAll('.avatar-option').forEach(el => {
  el.addEventListener('click', async function(){
    const res  = await fetch('<?= basePath() ?>/controllers/avatar_seleccionar.php', {
      method: 'POST', headers: {'Content-Type':'application/x-www-form-urlencoded'},
      body: `avatar_id=${this.dataset.id}`
    });
    const data = await res.json();
    if (data.ok) location.reload();
  });
});

// ---- TOAST AUTO-HIDE ----
const toast = document.querySelector('.perfil-toast');
if (toast) setTimeout(() => { toast.style.opacity = '0'; toast.style.transition = 'opacity .5s'; setTimeout(() => toast.remove(), 500); }, 4000);

// ---- UPLOAD FOTO PERSONAL (admin) ----
(() => {
  const modalInput  = document.getElementById('modalFotoInput');
  const modalPreview = document.getElementById('modalFotoPreview');
  const modalImg    = document.getElementById('modalFotoImg');
  const confirmBtn  = document.getElementById('confirmCropBtn');
  const cancelBtn   = document.getElementById('cancelCropBtn');
  if (!modalInput) return;
  let cropper = null;

  modalInput.addEventListener('change', () => {
    if (!modalInput.files[0]) return;
    const reader = new FileReader();
    reader.onload = e => {
      modalImg.src = e.target.result;
      modalPreview.style.display = 'block';
      if (cropper) cropper.destroy();
      cropper = new Cropper(modalImg, { aspectRatio:1, viewMode:1, dragMode:'none', movable:false, zoomable:false, autoCropArea:0.8 });
    };
    reader.readAsDataURL(modalInput.files[0]);
  });

  confirmBtn && confirmBtn.addEventListener('click', async () => {
    if (!cropper) return;
    const canvas = cropper.getCroppedCanvas({ width:500, height:500 });
    canvas.toBlob(async blob => {
      const fd = new FormData();
      fd.append('foto_personal', new File([blob], 'photo.webp', {type:'image/webp'}));
      const res  = await fetch('<?= basePath() ?>/controllers/subir_foto_personal.php', { method:'POST', body:fd });
      const data = await res.json();
      if (data.ok) {
        const pa = document.getElementById('perfilAvatar');
        if (pa) pa.innerHTML = `<img src="<?= basePath() ?>/serve-image.php?file=${encodeURIComponent(data.imagen)}" alt="">`;
        modalAv.style.display = 'none';
        cropper.destroy(); cropper = null;
        modalPreview.style.display = 'none';
      } else { alert('Error: ' + (data.error || 'desconocido')); }
    }, 'image/webp', 0.92);
  });

  cancelBtn && cancelBtn.addEventListener('click', () => {
    if (cropper) { cropper.destroy(); cropper = null; }
    modalPreview.style.display = 'none';
    modalInput.value = '';
  });
})();
</script>
<?php include("./../layout/footer.php"); ?>
