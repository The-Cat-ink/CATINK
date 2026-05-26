<?php
include(__DIR__ . "/../layout/headerAdmin.php");
include(__DIR__ . "/../data/conexion.php");
require_once(__DIR__ . "/helpers/urlhelper.php");

// Obtener todos los avatares
$avatares = $con->query("SELECT * FROM avatares_perfil ORDER BY creado DESC")->fetch_all(MYSQLI_ASSOC);
?>
<h1><i class="bi bi-person-circle"></i> Fotos de Perfil</h1>
<p style="color:var(--muted);">Sube imágenes preestablecidas que los usuarios podrán elegir como foto de perfil.</p>
<br>
<!-- Formulario de subida -->
<div class="card" style="max-width:500px; margin-bottom:24px;">
  <div class="card-body">
    <label class="file-drop-zone" id="dropZone">
      <input type="file" id="avatarFile" name="imagen" accept="image/*" hidden>
      <div class="file-drop-icon"><i class="bi bi-cloud-arrow-up"></i></div>
      <div class="file-drop-text">Arrastra una imagen o <span>haz clic aquí</span></div>
      <div class="file-drop-hint">PNG, JPG o WEBP</div>
      <img id="filePreview" class="file-drop-preview" style="display:none;">
    </label>
    <button type="button" id="btnSubirAvatar" class="btn btn-accent"><i class="bi bi-upload"></i> Subir</button>
    <p id="uploadMsg" style="margin-top:10px;"></p>
  </div>
</div>

<!-- Galería de avatares -->
<h3>Avatares disponibles</h3>
<br>
<div class="avatares-grid">
  <?php if(empty($avatares)): ?>
    <p style="color:var(--muted);">No hay avatares. Sube el primero.</p>
  <?php endif; ?>
  <?php foreach($avatares as $av): ?>
    <div class="avatar-card <?= $av['activo'] ? '' : 'avatar-disabled' ?>" id="avatar-<?= $av['id_avatar'] ?>">
      <img src="./../img/avatares/<?= htmlspecialchars($av['imagen']) ?>" alt="Avatar">
      <div class="avatar-actions">
        <button class="btn-toggle-avatar" data-id="<?= $av['id_avatar'] ?>" data-estado="<?= $av['activo'] ?>" title="<?= $av['activo'] ? 'Desactivar' : 'Activar' ?>">
          <i class="bi <?= $av['activo'] ? 'bi-eye-fill' : 'bi-eye-slash' ?>"></i>
        </button>
        <button class="btn-delete-avatar" data-id="<?= $av['id_avatar'] ?>" title="Eliminar">
          <i class="bi bi-trash-fill"></i>
        </button>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<style>
.avatares-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
  gap: 16px;
  margin-top: 12px;
}
.avatar-card {
  position: relative;
  border-radius: 12px;
  overflow: hidden;
  border: 2px solid var(--border);
  transition: opacity 0.2s;
}
.avatar-card img {
  width: 100%;
  height: 120px;
  object-fit: cover;
  display: block;
}
.avatar-card.avatar-disabled {
  opacity: 0.4;
}
.avatar-actions {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  display: flex;
  justify-content: center;
  gap: 8px;
  padding: 6px;
  background: rgba(0,0,0,0.6);
}
.avatar-actions button {
  background: none;
  border: none;
  color: #fff;
  font-size: 1.1rem;
  cursor: pointer;
  transition: color 0.2s;
}
.avatar-actions .btn-delete-avatar:hover { color: #EF3363; }
.avatar-actions .btn-toggle-avatar:hover { color: #ffc107; }

/* File Drop Zone */
.file-drop-zone {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 32px 20px;
  border: 2px dashed var(--border, #ddd);
  border-radius: 12px;
  cursor: pointer;
  transition: all 0.3s;
  background: var(--bg, #fafafa);
  margin-bottom: 16px;
  position: relative;
}
.file-drop-zone:hover,
.file-drop-zone.dragover {
  border-color: #EF3363;
  background: rgba(239, 51, 99, 0.04);
}
.file-drop-icon {
  font-size: 2.5rem;
  color: #EF3363;
  margin-bottom: 8px;
}
.file-drop-text {
  font-size: 0.95rem;
  color: var(--muted, #666);
}
.file-drop-text span {
  color: #EF3363;
  font-weight: 600;
  text-decoration: underline;
}
.file-drop-hint {
  font-size: 0.8rem;
  color: var(--muted, #999);
  margin-top: 4px;
}
.file-drop-preview {
  max-width: 100%;
  max-height: 150px;
  border-radius: 8px;
  margin-top: 12px;
  object-fit: contain;
}
</style>

<script>
// Drop zone logic
const dropZone = document.getElementById('dropZone');
const fileInput = document.getElementById('avatarFile');
const filePreview = document.getElementById('filePreview');
const dropIcon = dropZone.querySelector('.file-drop-icon');
const dropText = dropZone.querySelector('.file-drop-text');
const dropHint = dropZone.querySelector('.file-drop-hint');

dropZone.addEventListener('dragover', (e) => {
  e.preventDefault();
  dropZone.classList.add('dragover');
});
dropZone.addEventListener('dragleave', () => {
  dropZone.classList.remove('dragover');
});
dropZone.addEventListener('drop', (e) => {
  e.preventDefault();
  dropZone.classList.remove('dragover');
  if (e.dataTransfer.files.length) {
    fileInput.files = e.dataTransfer.files;
    showPreview(fileInput.files[0]);
  }
});
fileInput.addEventListener('change', () => {
  if (fileInput.files.length) showPreview(fileInput.files[0]);
});

function showPreview(file) {
  const reader = new FileReader();
  reader.onload = (e) => {
    filePreview.src = e.target.result;
    filePreview.style.display = 'block';
    dropIcon.style.display = 'none';
    dropText.innerHTML = '<strong>' + file.name + '</strong>';
    dropHint.textContent = (file.size / 1024).toFixed(1) + ' KB';
  };
  reader.readAsDataURL(file);
}

// Subir avatar
document.getElementById('btnSubirAvatar').addEventListener('click', async function(){
  const msg = document.getElementById('uploadMsg');
  if(!fileInput.files.length){
    msg.style.color = '#EF3363';
    msg.textContent = 'Selecciona una imagen primero.';
    return;
  }
  const form = new FormData();
  form.append('imagen', fileInput.files[0]);
  try {
    const res = await fetch('./../controllers/avatar_subir.php', { method:'POST', body: form });
    const data = await res.json();
    if(data.ok){
      msg.style.color = '#28a745';
      msg.textContent = 'Avatar subido correctamente.';
      setTimeout(()=> location.reload(), 800);
    } else {
      msg.style.color = '#EF3363';
      msg.textContent = data.error || 'Error al subir.';
    }
  } catch(err){
    msg.style.color = '#EF3363';
    msg.textContent = 'Error de conexión: ' + err.message;
  }
});

// Toggle activo/inactivo
document.querySelectorAll('.btn-toggle-avatar').forEach(btn => {
  btn.addEventListener('click', async function(){
    const id = this.dataset.id;
    const res = await fetch('./../controllers/avatar_toggle.php', {
      method:'POST',
      headers:{'Content-Type':'application/x-www-form-urlencoded'},
      body: `id=${id}`
    });
    const data = await res.json();
    if(data.ok) location.reload();
  });
});

// Eliminar
document.querySelectorAll('.btn-delete-avatar').forEach(btn => {
  btn.addEventListener('click', async function(){
    if(!confirm('¿Eliminar este avatar?')) return;
    const id = this.dataset.id;
    const res = await fetch('./../controllers/avatar_eliminar.php', {
      method:'POST',
      headers:{'Content-Type':'application/x-www-form-urlencoded'},
      body: `id=${id}`
    });
    const data = await res.json();
    if(data.ok) document.getElementById('avatar-'+id).remove();
  });
});
</script>
<?php include(__DIR__ . "/../layout/footer.php"); ?>
