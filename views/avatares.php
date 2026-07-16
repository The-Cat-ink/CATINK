include(__DIR__ . "/../layout/headerAdmin.php");
include(__DIR__ . "/../data/conexion.php");

if (empty($ACL['leer'])) {
    header("Location: admin.php");
    exit();
}
require_once(__DIR__ . "/helpers/urlhelper.php");

// Obtener todos los avatares
$avatares = $con->query("SELECT * FROM avatares_perfil ORDER BY creado DESC")->fetch_all(MYSQLI_ASSOC);

$totalAvatares = count($avatares);
$activos = count(array_filter($avatares, fn($a) => $a['activo'] == 1));
$inactivos = $totalAvatares - $activos;
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4" style="flex-wrap: wrap; gap: 12px;">
        <div>
            <h1 style="margin:0;"><i class="bi bi-person-circle text-accent"></i> Fotos de Perfil</h1>
            <p style="color:var(--muted); margin-top:5px;">Sube imágenes preestablecidas que los usuarios podrán elegir como foto de perfil.</p>
        </div>
    </div>

    <!-- Estadísticas rápidas -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(99,102,241,0.1); color: #6366f1;"><i class="bi bi-images"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?= $totalAvatares ?></span>
                <span class="stat-label">Total Avatares</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(16,185,129,0.1); color: #10b981;"><i class="bi bi-eye-fill"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?= $activos ?></span>
                <span class="stat-label">Visibles (Activos)</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(239,51,99,0.1); color: #EF3363;"><i class="bi bi-eye-slash-fill"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?= $inactivos ?></span>
                <span class="stat-label">Ocultos (Inactivos)</span>
            </div>
        </div>
    </div>

    <!-- Formulario de subida -->
    <div class="card shadow-sm mb-4" style="max-width:500px;">
        <div class="card-body">
            <h5 class="card-title" style="margin-top:0;">Subir Nuevo Avatar</h5>
            <label class="file-drop-zone" id="dropZone">
                <input type="file" id="avatarFile" name="imagen" accept="image/*" hidden>
                <div class="file-drop-icon"><i class="bi bi-cloud-arrow-up"></i></div>
                <div class="file-drop-text">Arrastra una imagen o <span>haz clic aquí</span></div>
                <div class="file-drop-hint">PNG, JPG o WEBP</div>
                <img id="filePreview" class="file-drop-preview" style="display:none;">
            </label>
            <div style="display:flex; justify-content:flex-end;">
                <button type="button" id="btnSubirAvatar" class="btn btn-accent"><i class="bi bi-upload"></i> Subir Avatar</button>
            </div>
            <p id="uploadMsg" style="margin-top:10px; font-size:0.85rem; font-weight:600; text-align:right;"></p>
        </div>
    </div>

    <!-- Toolbar -->
    <div class="contenidos-toolbar">
        <div class="contenidos-tabs">
            <span class="tab-btn active">Galería de Avatares Disponibles</span>
        </div>
    </div>

    <!-- Galería de avatares -->
    <div class="card shadow-sm">
        <div class="card-body p-4">
            <div class="avatares-grid">
                <?php if(empty($avatares)): ?>
                    <p style="color:var(--muted); text-align:center; grid-column: 1 / -1; padding:20px;">No hay avatares. Sube el primero.</p>
                <?php endif; ?>
                <?php foreach($avatares as $av): ?>
                    <div class="avatar-card <?= $av['activo'] ? '' : 'avatar-disabled' ?>" id="avatar-<?= $av['id_avatar'] ?>">
                        <img src="<?= imageUrl($av['imagen']) ?>" alt="Avatar" loading="lazy" decoding="async">
                        <div class="avatar-actions">
                            <button class="btn-toggle-avatar" data-id="<?= $av['id_avatar'] ?>" data-estado="<?= $av['activo'] ?>" title="<?= $av['activo'] ? 'Ocultar a usuarios' : 'Mostrar a usuarios' ?>">
                                <i class="bi <?= $av['activo'] ? 'bi-eye-fill' : 'bi-eye-slash' ?>"></i>
                            </button>
                            <button class="btn-delete-avatar" data-id="<?= $av['id_avatar'] ?>" title="Eliminar definitivamente">
                                <i class="bi bi-trash-fill"></i>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<style>
.avatares-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
  gap: 16px;
}
.avatar-card {
  position: relative;
  border-radius: 12px;
  overflow: hidden;
  border: 2px solid var(--border);
  transition: opacity 0.2s, transform 0.2s, box-shadow 0.2s;
  background: var(--bg);
}
.avatar-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
.avatar-card img {
  width: 100%;
  aspect-ratio: 1 / 1;
  object-fit: cover;
  display: block;
}
.avatar-card.avatar-disabled {
  opacity: 0.5;
  border-style: dashed;
}
.avatar-actions {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  display: flex;
  justify-content: center;
  gap: 12px;
  padding: 8px;
  background: linear-gradient(to top, rgba(0,0,0,0.8), transparent);
}
.avatar-actions button {
  background: rgba(255,255,255,0.2);
  border: none;
  color: #fff;
  width: 32px;
  height: 32px;
  border-radius: 50%;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 1.1rem;
  cursor: pointer;
  transition: background 0.2s, color 0.2s, transform 0.1s;
  backdrop-filter: blur(4px);
}
.avatar-actions button:hover {
    transform: scale(1.1);
}
.avatar-actions .btn-delete-avatar:hover { background: #EF3363; }
.avatar-actions .btn-toggle-avatar:hover { background: #f59e0b; }

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
  const btn = this;
  if(!fileInput.files.length){
    msg.style.color = '#EF3363';
    msg.textContent = 'Selecciona una imagen primero.';
    return;
  }
  
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Subiendo...';
  
  const form = new FormData();
  form.append('imagen', fileInput.files[0]);
  try {
    const res = await fetch('./../controllers/avatar_subir.php', { method:'POST', body: form });
    const data = await res.json();
    if(data.ok){
      msg.style.color = '#10b981';
      msg.textContent = 'Avatar subido correctamente.';
      setTimeout(()=> location.reload(), 800);
    } else {
      msg.style.color = '#EF3363';
      msg.textContent = data.error || 'Error al subir.';
      btn.disabled = false;
      btn.innerHTML = '<i class="bi bi-upload"></i> Subir Avatar';
    }
  } catch(err){
    msg.style.color = '#EF3363';
    msg.textContent = 'Error de conexión: ' + err.message;
    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-upload"></i> Subir Avatar';
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
    if(!confirm('¿Eliminar este avatar definitivamente?')) return;
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
<?php include(__DIR__ . "/../layout/footerAdmin.php"); ?>
