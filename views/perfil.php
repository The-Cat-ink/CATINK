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
// Obtener datos reales del usuario según su tipo
$tipoUsuario = $_SESSION['tipo'] ?? 'lector';
if($tipoUsuario === 'admin'){
    $stmtUser = $con->prepare("SELECT *, registro AS creado FROM usuarios WHERE usuario = ?");
} else {
    $stmtUser = $con->prepare("SELECT * FROM lectores WHERE usuario = ?");
}
$stmtUser->bind_param("s", $_SESSION['usuario']);
$stmtUser->execute();
$user = $stmtUser->get_result()->fetch_assoc();
// Generar iniciales
$palabras = explode(' ', $user['nombre']);
$iniciales = strtoupper(substr($palabras[0],0,1) . (isset($palabras[1]) ? substr($palabras[1],0,1) : ''));
// Fecha de registro
$fechaRegistro = ($user['creado'] ?? $user['registro'] ?? null) ? date('M Y', strtotime($user['creado'] ?? $user['registro'])) : 'N/A';
// Obtener avatar actual
$avatarActual = null;
if($user['avatar_id'] ?? null){
    $stmtAv = $con->prepare("SELECT imagen FROM avatares_perfil WHERE id_avatar = ?");
    $stmtAv->bind_param("i", $user['avatar_id']);
    $stmtAv->execute();
    $avRow = $stmtAv->get_result()->fetch_assoc();
    if($avRow) $avatarActual = $avRow['imagen'];
}
// Obtener avatares disponibles
$avatares = $con->query("SELECT * FROM avatares_perfil WHERE activo = 1 ORDER BY creado DESC")->fetch_all(MYSQLI_ASSOC);
?>
<div class="container">
  <h1 class="text-center" style="margin:30px 0 20px;">Perfil de Usuario</h1>
  <?php if(isset($_GET['ok'])): ?>
    <p style="color:#28a745; text-align:center; margin-bottom:16px;">Perfil actualizado correctamente.</p>
  <?php endif; ?>
  <?php if(isset($_GET['deleted'])): ?>
    <p style="color:#28a745; text-align:center; margin-bottom:16px;">Tu cuenta ha sido eliminada. ¡Hasta pronto!</p>
  <?php endif; ?>
  <?php if(isset($_GET['error'])): ?>
    <p style="color:#EF3363; text-align:center; margin-bottom:16px;">
      <?php
        $errores = [
          '1'  => 'La contraseña actual es incorrecta.',
          '2'  => 'Error al actualizar.',
          '3'  => 'Ese nombre de usuario ya está en uso.',
          '4'  => 'El nombre de usuario solo puede contener letras, números, puntos y guiones bajos.',
          '5'  => 'Contraseña incorrecta. No se eliminó la cuenta.',
          '6'  => 'Error al eliminar la cuenta.',
        ];
        echo $errores[$_GET['error']] ?? 'Error desconocido.';
      ?>
    </p>
  <?php endif; ?>
  <div class="perfil-wrapper">
    <!-- COLUMNA IZQUIERDA: Avatar + Info -->
    <div class="perfil-sidebar">
      <div class="perfil-avatar-wrap">
        <div class="perfil-avatar" id="perfilAvatar">
          <?php if($tipoUsuario === 'admin' && !empty($user['foto_personal'])): ?>
            <img src="<?= imageUrl($user['foto_personal']) ?>" alt="Foto personal">
          <?php elseif($avatarActual): ?>
            <img src="<?= imageUrl($avatarActual) ?>" alt="Avatar">
          <?php else: ?>
            <span id="perfilInitials"><?= htmlspecialchars($iniciales) ?></span>
          <?php endif; ?>
        </div>
        <button type="button" class="perfil-camera" id="openAvatarPicker">
          <i class="bi bi-camera-fill"></i>
        </button>
      </div>
      <h3 class="perfil-nombre"><?= htmlspecialchars($user['nombre']) ?></h3>
      <span class="perfil-username">@<?= htmlspecialchars($user['usuario']) ?></span>
      <div class="perfil-meta-info">
        <div class="perfil-meta-row">
          <span class="perfil-meta-label">Miembro desde</span>
          <span class="perfil-meta-value"><?= $fechaRegistro ?></span>
        </div>
      </div>
      <a href="<?= basePath() ?>/controllers/logoutcontroller.php" class="perfil-logout-btn" data-turbo="false">
        <i class="bi bi-box-arrow-right"></i> Cerrar sesión
      </a>
    </div>
    <!-- COLUMNA DERECHA: Formulario -->
    <div class="perfil-form">
      <form id="perfilForm" action="<?= basePath() ?>/controllers/perfilcontroller.php" method="POST" enctype="multipart/form-data">
        <div class="form-group">
          <label for="nombre_usuario">Nombre de Usuario</label>
          <div style="position:relative;">
            <span style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:var(--muted); font-weight:600;">@</span>
            <input type="text" id="nombre_usuario" name="nombre_usuario" class="input" value="<?= htmlspecialchars($user['usuario']) ?>" required
              pattern="[a-zA-Z0-9_.]{3,30}" title="De 3 a 30 caracteres: letras, números, puntos y guiones bajos."
              style="padding-left:28px;">
          </div>
          <small style="color:var(--muted); font-size:0.78rem;">3–30 caracteres. Solo letras, números, puntos y guiones bajos.</small>
        </div>
        <div class="form-group">
          <label for="correo">Correo Electrónico</label>
          <input type="email" id="correo" name="correo" class="input" value="<?= htmlspecialchars($user['correo']) ?>" required>
        </div>
        <div class="form-group">
          <button type="button" class="input" id="togglePass" style="cursor:pointer; text-align:left; color:var(--accent); font-weight:600; border:1px solid var(--border);">
            <i class="bi bi-pencil-square"></i> Editar Contraseña
          </button>
          <div id="passFields" style="display:none; margin-top:10px;">
            <div class="form-group">
              <label for="pass_actual">Contraseña Actual</label>
              <input type="password" id="pass_actual" name="pass_actual" class="input" placeholder="Tu contraseña actual...">
            </div>
            <div class="form-group">
              <label for="pass_nueva">Nueva Contraseña</label>
              <input type="password" id="pass_nueva" name="pass_nueva" class="input" placeholder="Nueva contraseña...">
            </div>
          </div>
        </div>
        <div class="perfil-row">
          <div class="form-group perfil-half">
            <label for="sexo">Sexo</label>
            <select id="sexo" name="sexo" class="input">
              <option value="">Seleccionar...</option>
              <option value="masculino" <?= ($user['sexo'] ?? '') == 'masculino' ? 'selected' : '' ?>>Masculino</option>
              <option value="femenino" <?= ($user['sexo'] ?? '') == 'femenino' ? 'selected' : '' ?>>Femenino</option>
              <option value="otro" <?= ($user['sexo'] ?? '') == 'otro' ? 'selected' : '' ?>>Otro</option>
            </select>
          </div>
          <div class="form-group perfil-half">
            <label for="nacimiento">Fecha de Nacimiento</label>
            <input type="date" id="nacimiento" name="nacimiento" class="input" value="<?= htmlspecialchars($user['fecha_nacimiento'] ?? '') ?>">
          </div>
        </div>
        <div class="form-group">
          <label for="entidad">Entidad Federativa</label>
          <?php $ent = $user['entidad'] ?? ''; ?>
          <select id="entidad" name="entidad" class="input">
            <option value="">Seleccionar...</option>
            <option value="AGU" <?= $ent=='AGU'?'selected':'' ?>>Aguascalientes</option>
            <option value="BCN" <?= $ent=='BCN'?'selected':'' ?>>Baja California</option>
            <option value="BCS" <?= $ent=='BCS'?'selected':'' ?>>Baja California Sur</option>
            <option value="CAM" <?= $ent=='CAM'?'selected':'' ?>>Campeche</option>
            <option value="CHP" <?= $ent=='CHP'?'selected':'' ?>>Chiapas</option>
            <option value="CHH" <?= $ent=='CHH'?'selected':'' ?>>Chihuahua</option>
            <option value="CMX" <?= $ent=='CMX'?'selected':'' ?>>Ciudad de México</option>
            <option value="COA" <?= $ent=='COA'?'selected':'' ?>>Coahuila</option>
            <option value="COL" <?= $ent=='COL'?'selected':'' ?>>Colima</option>
            <option value="DUR" <?= $ent=='DUR'?'selected':'' ?>>Durango</option>
            <option value="GUA" <?= $ent=='GUA'?'selected':'' ?>>Guanajuato</option>
            <option value="GRO" <?= $ent=='GRO'?'selected':'' ?>>Guerrero</option>
            <option value="HID" <?= $ent=='HID'?'selected':'' ?>>Hidalgo</option>
            <option value="JAL" <?= $ent=='JAL'?'selected':'' ?>>Jalisco</option>
            <option value="MEX" <?= $ent=='MEX'?'selected':'' ?>>Estado de México</option>
            <option value="MIC" <?= $ent=='MIC'?'selected':'' ?>>Michoacán</option>
            <option value="MOR" <?= $ent=='MOR'?'selected':'' ?>>Morelos</option>
            <option value="NAY" <?= $ent=='NAY'?'selected':'' ?>>Nayarit</option>
            <option value="NLE" <?= $ent=='NLE'?'selected':'' ?>>Nuevo León</option>
            <option value="OAX" <?= $ent=='OAX'?'selected':'' ?>>Oaxaca</option>
            <option value="PUE" <?= $ent=='PUE'?'selected':'' ?>>Puebla</option>
            <option value="QUE" <?= $ent=='QUE'?'selected':'' ?>>Querétaro</option>
            <option value="ROO" <?= $ent=='ROO'?'selected':'' ?>>Quintana Roo</option>
            <option value="SLP" <?= $ent=='SLP'?'selected':'' ?>>San Luis Potosí</option>
            <option value="SIN" <?= $ent=='SIN'?'selected':'' ?>>Sinaloa</option>
            <option value="SON" <?= $ent=='SON'?'selected':'' ?>>Sonora</option>
            <option value="TAB" <?= $ent=='TAB'?'selected':'' ?>>Tabasco</option>
            <option value="TAM" <?= $ent=='TAM'?'selected':'' ?>>Tamaulipas</option>
            <option value="TLA" <?= $ent=='TLA'?'selected':'' ?>>Tlaxcala</option>
            <option value="VER" <?= $ent=='VER'?'selected':'' ?>>Veracruz</option>
            <option value="YUC" <?= $ent=='YUC'?'selected':'' ?>>Yucatán</option>
            <option value="ZAC" <?= $ent=='ZAC'?'selected':'' ?>>Zacatecas</option>
          </select>
        </div>
        <?php if($tipoUsuario === 'admin'): ?>
        <!-- PERFIL PÚBLICO (solo editores) -->
        <hr style="margin: 24px 0; border-color: var(--border);">
        <h3 style="margin-bottom: 16px; font-size: 1.1rem;"><i class="bi bi-person-badge"></i> Perfil Público</h3>
        <div class="form-group">
          <label>Foto Personal</label>
          <label class="perfil-drop-zone" id="fotoDropZone">
            <input type="file" name="foto_personal" id="fotoFileInput" accept="image/jpeg,image/png,image/webp" style="display:none;">
            <?php if(!empty($user['foto_personal'])): ?>
              <img id="fotoPreview" src="<?= imageUrl($user['foto_personal']) ?>" style="max-width:100%; max-height:150px; border-radius:8px; object-fit:cover;">
              <div class="perfil-drop-icon" style="display:none;"><i class="bi bi-cloud-arrow-up"></i></div>
              <div class="perfil-drop-text" style="display:none;">Arrastra una imagen o <span>haz clic aquí</span></div>
            <?php else: ?>
              <img id="fotoPreview" style="display:none; max-width:100%; max-height:150px; border-radius:8px; object-fit:cover;">
              <div class="perfil-drop-icon"><i class="bi bi-cloud-arrow-up"></i></div>
              <div class="perfil-drop-text">Arrastra una imagen o <span>haz clic aquí</span></div>
            <?php endif; ?>
            <div class="perfil-drop-hint">JPG, PNG o WEBP</div>
          </label>
        </div>
        <div class="form-group">
          <label for="biografia">Biografía</label>
          <textarea id="biografia" name="biografia" class="input" rows="4" style="resize:vertical;" placeholder="Cuéntale al mundo sobre ti..."><?= htmlspecialchars($user['biografia'] ?? '') ?></textarea>
        </div>
        <div class="perfil-row">
          <div class="form-group perfil-half">
            <label for="link_twitter"><i class="bi bi-twitter-x"></i> Twitter / X</label>
            <input type="url" id="link_twitter" name="link_twitter" class="input" value="<?= htmlspecialchars($user['link_twitter'] ?? '') ?>" placeholder="https://x.com/tu_usuario">
          </div>
          <div class="form-group perfil-half">
            <label for="link_instagram"><i class="bi bi-instagram"></i> Instagram</label>
            <input type="url" id="link_instagram" name="link_instagram" class="input" value="<?= htmlspecialchars($user['link_instagram'] ?? '') ?>" placeholder="https://instagram.com/tu_usuario">
          </div>
        </div>
        <a href="<?= basePath() ?>/autor/<?= $user['id_u'] ?>" target="_blank" style="display:inline-block; margin-bottom: 16px; color: var(--accent); font-size: 0.9rem; text-decoration: none;">
          <i class="bi bi-eye"></i> Ver mi perfil público
        </a>
        <?php endif; ?>
        <button type="submit" class="btn-perfil-save">Guardar Cambios</button>
      </form>

      <!-- ZONA DE PELIGRO -->
      <div class="perfil-danger-zone" id="dangerZone">
        <h3 style="color:#ef3333; font-size:1rem; margin-bottom:8px;"><i class="bi bi-exclamation-triangle-fill"></i> Zona de Peligro</h3>
        <p style="color:var(--muted); font-size:0.88rem; margin-bottom:16px;">Al eliminar tu cuenta se borrarán permanentemente todos tus datos, comentarios y likes. Esta acción <strong>no se puede deshacer</strong>.</p>
        <button type="button" class="btn-delete-account" id="btnEliminarCuenta">
          <i class="bi bi-trash3-fill"></i> Eliminar mi cuenta
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Modal confirmación eliminar cuenta -->
<div id="modalEliminar" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.7); z-index:9999; justify-content:center; align-items:center;">
  <div style="background:var(--card-bg); border:1px solid #ef3333; border-radius:16px; padding:32px; max-width:420px; width:92%; box-shadow:0 8px 40px rgba(239,51,51,0.2);">
    <h3 style="color:#ef3333; margin-bottom:8px;"><i class="bi bi-exclamation-octagon-fill"></i> ¿Seguro que quieres eliminar tu cuenta?</h3>
    <p style="color:var(--muted); font-size:0.9rem; margin-bottom:20px;">Escribe tu contraseña para confirmar. Esta acción es irreversible.</p>
    <form id="formEliminar" action="<?= basePath() ?>/controllers/eliminar_cuenta.php" method="POST">
      <input type="password" name="pass_confirmar" id="passConfirmar" class="input" placeholder="Tu contraseña actual..." required
        style="width:100%; margin-bottom:16px; padding:10px 14px; border-radius:8px; border:1px solid var(--border); background:var(--bg); color:var(--text);">
      <div style="display:flex; gap:12px;">
        <button type="button" id="cancelarEliminar" style="flex:1; padding:10px; border-radius:8px; border:1px solid var(--border); background:transparent; color:var(--text); cursor:pointer; font-size:0.9rem;">Cancelar</button>
        <button type="submit" style="flex:1; padding:10px; border-radius:8px; border:none; background:#ef3333; color:#fff; font-weight:700; cursor:pointer; font-size:0.9rem;"><i class="bi bi-trash3-fill"></i> Eliminar cuenta</button>
      </div>
    </form>
  </div>
</div>
<script>
  // Lógica del modal de eliminación de cuenta
  const btnEliminar = document.getElementById('btnEliminarCuenta');
  const modalEliminar = document.getElementById('modalEliminar');
  const cancelarEliminar = document.getElementById('cancelarEliminar');
  if (btnEliminar && modalEliminar) {
    btnEliminar.addEventListener('click', () => { modalEliminar.style.display = 'flex'; });
    cancelarEliminar.addEventListener('click', () => { modalEliminar.style.display = 'none'; });
    modalEliminar.addEventListener('click', (e) => { if (e.target === modalEliminar) modalEliminar.style.display = 'none'; });
  }
</script>
<!-- Modal selector de avatar -->
<div id="avatarModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6); z-index:9999; display:none; justify-content:center; align-items:center;">
  <div style="background:var(--bg); border-radius:12px; padding:24px; max-width:400px; width:90%; max-height:80vh; overflow-y:auto;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
      <h3 style="margin:0;">Elige tu avatar</h3>
      <button id="closeAvatarModal" style="background:none; border:none; font-size:1.5rem; cursor:pointer; color:var(--text);">&times;</button>
    </div>
    <?php if($tipoUsuario === 'admin'): ?>
    <div style="margin-bottom:16px; padding:12px; background:rgba(239,51,99,0.1); border-radius:8px; border:1px solid var(--accent);">
      <label class="avatar-upload-btn" style="display:flex; align-items:center; gap:8px; cursor:pointer; color:var(--accent); font-weight:600;">
        <i class="bi bi-cloud-arrow-up"></i>
        <span>Subir foto personal</span>
        <input type="file" id="modalFotoInput" accept="image/jpeg,image/png,image/webp" style="display:none;">
      </label>
      <div id="modalFotoPreview" style="display:none; margin-top:12px;">
        <div style="max-height:300px; overflow:hidden; border-radius:8px;">
          <img id="modalFotoImg" style="max-width:100%; display:block;">
        </div>
        <div style="margin-top:8px; display:flex; gap:8px;">
          <button type="button" id="confirmCropBtn" class="btn-perfil-save" style="flex:1; font-size:0.9rem;">Confirmar recorte</button>
          <button type="button" id="cancelCropBtn" style="flex:1; padding:8px 16px; border:1px solid var(--border); background:var(--bg); color:var(--text); border-radius:8px; cursor:pointer;">Cancelar</button>
        </div>
      </div>
      <input type="hidden" id="cropX" name="crop_x">
      <input type="hidden" id="cropY" name="crop_y">
      <input type="hidden" id="cropWidth" name="crop_width">
      <input type="hidden" id="cropHeight" name="crop_height">
    </div>
    <?php endif; ?>
    <div style="display:grid; grid-template-columns:repeat(3, 1fr); gap:12px;">
      <?php if(empty($avatares)): ?>
        <p style="grid-column:1/-1; color:var(--muted); text-align:center;">No hay avatares disponibles aún.</p>
      <?php endif; ?>
      <?php foreach($avatares as $av): ?>
        <div class="avatar-option <?= ($user['avatar_id'] ?? 0) == $av['id_avatar'] ? 'avatar-selected' : '' ?>" data-id="<?= $av['id_avatar'] ?>" style="cursor:pointer; border-radius:12px; overflow:hidden; border:3px solid transparent; transition:border-color 0.2s;">
          <img src="<?= imageUrl($av['imagen']) ?>" style="width:100%; height:100px; object-fit:cover; display:block;">
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<style>
.avatar-option:hover { border-color: var(--accent) !important; }
.avatar-option.avatar-selected { border-color: var(--accent) !important; }
.perfil-drop-zone {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  border: 2px dashed var(--border);
  border-radius: 12px;
  padding: 24px;
  cursor: pointer;
  transition: border-color 0.2s, background 0.2s;
  text-align: center;
}
.perfil-drop-zone:hover,
.perfil-drop-zone.dragover {
  border-color: var(--accent);
  background: rgba(239, 51, 99, 0.04);
}
.perfil-drop-icon { font-size: 2.5rem; color: var(--accent); margin-bottom: 8px; }
.perfil-drop-text { font-size: 0.95rem; color: var(--muted); }
.perfil-drop-text span { color: var(--accent); font-weight: 600; text-decoration: underline; }
.perfil-drop-hint { font-size: 0.8rem; color: var(--muted); margin-top: 4px; }
.perfil-logout-btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  margin-top: 20px;
  padding: 9px 18px;
  border-radius: 8px;
  font-size: 0.9rem;
  font-weight: 600;
  color: var(--accent);
  border: 1px solid var(--accent);
  text-decoration: none;
  transition: background 0.15s, color 0.15s;
}
.perfil-logout-btn:hover {
  background: var(--accent);
  color: #fff;
}
</style>

<script>
// Toggle contraseña
document.getElementById('togglePass').addEventListener('click', function(){
  const fields = document.getElementById('passFields');
  if(fields.style.display === 'none'){
    fields.style.display = 'block';
    this.innerHTML = '<i class="bi bi-x-circle"></i> Cancelar';
  } else {
    fields.style.display = 'none';
    this.innerHTML = '<i class="bi bi-pencil-square"></i> Editar Contraseña';
    document.getElementById('pass_actual').value = '';
    document.getElementById('pass_nueva').value = '';
  }
});

// Modal avatar
const modal = document.getElementById('avatarModal');
document.getElementById('openAvatarPicker').addEventListener('click', ()=> {
  modal.style.display = 'flex';
});
document.getElementById('closeAvatarModal').addEventListener('click', ()=> {
  modal.style.display = 'none';
});
modal.addEventListener('click', (e)=> {
  if(e.target === modal) modal.style.display = 'none';
});

// Seleccionar avatar
document.querySelectorAll('.avatar-option').forEach(el => {
  el.addEventListener('click', async function(){
    const id = this.dataset.id;
    const res = await fetch('<?= basePath() ?>/controllers/avatar_seleccionar.php', {
      method:'POST',
      headers:{'Content-Type':'application/x-www-form-urlencoded'},
      body: `avatar_id=${id}`
    });
    const data = await res.json();
    if(data.ok) location.reload();
  });
});

// Drop zone foto personal
(() => {
  const zone = document.getElementById('fotoDropZone');
  const input = document.getElementById('fotoFileInput');
  if (!zone || !input) return;
  const preview = document.getElementById('fotoPreview');
  const icon = zone.querySelector('.perfil-drop-icon');
  const text = zone.querySelector('.perfil-drop-text');

  function showPreview(file) {
    const reader = new FileReader();
    reader.onload = (e) => {
      preview.src = e.target.result;
      preview.style.display = 'block';
      if (icon) icon.style.display = 'none';
      if (text) text.style.display = 'none';
    };
    reader.readAsDataURL(file);
  }

  input.addEventListener('change', () => {
    if (input.files[0]) showPreview(input.files[0]);
  });

  zone.addEventListener('dragover', (e) => {
    e.preventDefault();
    zone.classList.add('dragover');
  });
  zone.addEventListener('dragleave', () => zone.classList.remove('dragover'));
  zone.addEventListener('drop', (e) => {
    e.preventDefault();
    zone.classList.remove('dragover');
    if (e.dataTransfer.files[0]) {
      input.files = e.dataTransfer.files;
      showPreview(e.dataTransfer.files[0]);
    }
  });
})();

// Modal foto personal upload con cropper
(() => {
  const modalInput = document.getElementById('modalFotoInput');
  const modalPreview = document.getElementById('modalFotoPreview');
  const modalImg = document.getElementById('modalFotoImg');
  const mainInput = document.getElementById('fotoFileInput');
  const mainPreview = document.getElementById('fotoPreview');
  const mainIcon = document.querySelector('.perfil-drop-icon');
  const mainText = document.querySelector('.perfil-drop-text');
  const confirmCropBtn = document.getElementById('confirmCropBtn');
  const cancelCropBtn = document.getElementById('cancelCropBtn');
  const cropX = document.getElementById('cropX');
  const cropY = document.getElementById('cropY');
  const cropWidth = document.getElementById('cropWidth');
  const cropHeight = document.getElementById('cropHeight');

  if (!modalInput || !mainInput) return;

  let cropper = null;

  modalInput.addEventListener('change', () => {
    if (modalInput.files[0]) {
      const file = modalInput.files[0];
      const reader = new FileReader();
      reader.onload = (e) => {
        modalImg.src = e.target.result;
        modalPreview.style.display = 'block';
        
        // Destruir cropper anterior si existe
        if (cropper) {
          cropper.destroy();
        }
        
        // Inicializar cropper
        cropper = new Cropper(modalImg, {
          aspectRatio: 1,
          viewMode: 1,
          dragMode: 'none',
          movable: false,
          zoomable: false,
          rotatable: false,
          scalable: false,
          autoCropArea: 0.8,
          restore: false,
          guides: true,
          center: true,
          highlight: true,
          cropBoxMovable: true,
          cropBoxResizable: true,
          toggleDragModeOnDblclick: false,
        });
      };
      reader.readAsDataURL(file);
    }
  });

  confirmCropBtn.addEventListener('click', async () => {
    if (cropper) {
      const canvas = cropper.getCroppedCanvas({
        width: 500,
        height: 500,
      });
      
      canvas.toBlob(async (blob) => {
        const formData = new FormData();
        formData.append('foto_personal', new File([blob], 'cropped_photo.webp', { type: 'image/webp' }));
        
        try {
          const response = await fetch('<?= basePath() ?>/controllers/subir_foto_personal.php', {
            method: 'POST',
            body: formData
          });
          
          const result = await response.json();
          
          if (result.ok) {
            // Actualizar avatar en sidebar
            const perfilAvatar = document.getElementById('perfilAvatar');
            if (perfilAvatar) {
              const imgUrl = '<?= basePath() ?>/serve-image.php?file=' + encodeURIComponent(result.imagen);
              perfilAvatar.innerHTML = `<img src="${imgUrl}" alt="Foto personal">`;
            }
            
            // Mostrar mensaje de éxito
            const successMsg = document.createElement('div');
            successMsg.style.cssText = 'color:#28a745; text-align:center; margin-bottom:16px;';
            successMsg.textContent = 'Foto actualizada correctamente';
            document.querySelector('.container').insertBefore(successMsg, document.querySelector('.container').firstChild);
            
            // Cerrar modal
            document.getElementById('avatarModal').style.display = 'none';
            
            // Destruir cropper
            cropper.destroy();
            cropper = null;
            modalPreview.style.display = 'none';
            
            // Ocultar foto personal en formulario principal
            if (mainPreview) mainPreview.style.display = 'none';
            if (mainIcon) mainIcon.style.display = 'block';
            if (mainText) mainText.style.display = 'block';
          } else {
            alert('Error al subir foto: ' + (result.error || 'Error desconocido'));
          }
        } catch (error) {
          alert('Error al subir foto: ' + error.message);
        }
      }, 'image/webp', 0.92);
    }
  });

  cancelCropBtn.addEventListener('click', () => {
    if (cropper) {
      cropper.destroy();
      cropper = null;
    }
    modalPreview.style.display = 'none';
    modalInput.value = '';
  });
})();
</script>
<?php include("./../layout/footer.php"); ?>
