<?php
include("./../layout/header.php");
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
  <?php if(isset($_GET['error'])): ?>
    <p style="color:#EF3363; text-align:center; margin-bottom:16px;">
      <?php
        $errores = ['1'=>'La contraseña actual es incorrecta.','2'=>'Error al actualizar.'];
        echo $errores[$_GET['error']] ?? 'Error desconocido.';
      ?>
    </p>
  <?php endif; ?>
  <div class="perfil-wrapper">
    <!-- COLUMNA IZQUIERDA: Avatar + Info -->
    <div class="perfil-sidebar">
      <div class="perfil-avatar-wrap">
        <div class="perfil-avatar" id="perfilAvatar">
          <?php if($avatarActual): ?>
            <img src="<?= basePath() ?>/img/avatares/<?= htmlspecialchars($avatarActual) ?>" alt="Avatar">
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
    </div>
    <!-- COLUMNA DERECHA: Formulario -->
    <div class="perfil-form">
      <form id="perfilForm" action="<?= basePath() ?>/controllers/perfilcontroller.php" method="POST" enctype="multipart/form-data">
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
          <?php if(!empty($user['foto_personal'])): ?>
            <div style="margin-bottom: 10px;">
              <img src="<?= basePath() ?>/<?= htmlspecialchars($user['foto_personal']) ?>" alt="Tu foto" style="width: 100px; height: 100px; object-fit: cover; border-radius: 50%; border: 3px solid var(--accent);">
            </div>
          <?php endif; ?>
          <input type="file" name="foto_personal" accept="image/jpeg,image/png,image/webp" class="input">
          <small style="color: var(--muted); display: block; margin-top: 4px;">JPG, PNG o WEBP. Se convertirá a WebP automáticamente.</small>
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
    </div>
  </div>
</div>
<!-- Modal selector de avatar -->
<div id="avatarModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6); z-index:9999; display:none; justify-content:center; align-items:center;">
  <div style="background:var(--bg); border-radius:12px; padding:24px; max-width:400px; width:90%; max-height:80vh; overflow-y:auto;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
      <h3 style="margin:0;">Elige tu avatar</h3>
      <button id="closeAvatarModal" style="background:none; border:none; font-size:1.5rem; cursor:pointer; color:var(--text);">&times;</button>
    </div>
    <div style="display:grid; grid-template-columns:repeat(3, 1fr); gap:12px;">
      <?php if(empty($avatares)): ?>
        <p style="grid-column:1/-1; color:var(--muted); text-align:center;">No hay avatares disponibles aún.</p>
      <?php endif; ?>
      <?php foreach($avatares as $av): ?>
        <div class="avatar-option <?= ($user['avatar_id'] ?? 0) == $av['id_avatar'] ? 'avatar-selected' : '' ?>" data-id="<?= $av['id_avatar'] ?>" style="cursor:pointer; border-radius:12px; overflow:hidden; border:3px solid transparent; transition:border-color 0.2s;">
          <img src="<?= basePath() ?>/img/avatares/<?= htmlspecialchars($av['imagen']) ?>" style="width:100%; height:100px; object-fit:cover; display:block;">
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<style>
.avatar-option:hover { border-color: var(--accent) !important; }
.avatar-option.avatar-selected { border-color: var(--accent) !important; }
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
</script>
<?php include("./../layout/footer.php"); ?>
