<?php
include("./../layout/headerAdmin.php");
include("./../data/conexion.php");

if (empty($ACL['leer'])) {
    header("Location: admin.php");
    exit();
}

$q = trim($_GET['q'] ?? '');
if ($q !== '') {
    $like = "%$q%";
    $stmt = $con->prepare("SELECT * FROM usuarios WHERE nombre LIKE ? OR usuario LIKE ? OR correo LIKE ? ORDER BY registro DESC");
    $stmt->bind_param("sss", $like, $like, $like);
} else {
    $stmt = $con->prepare("SELECT * FROM usuarios ORDER BY registro DESC");
}
$stmt->execute();
$usuarios = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$totalUsuarios = count($usuarios);
$ultimos7dias  = count(array_filter($usuarios, fn($u) => strtotime($u['registro']) >= strtotime('-7 days')));
$superadmins   = count(array_filter($usuarios, fn($u) => $u['id_u'] == 1 || !empty($u['superadmin'])));

$esSuper = !empty($_SESSION['superadmin']);
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4" style="flex-wrap: wrap; gap: 12px;">
        <h1 style="margin:0;">Gestión de administradores/editores</h1>
        <form method="GET" class="admin-search-form" style="display:flex; align-items:center; gap:8px;">
            <i class="bi bi-search admin-search-icon"></i>
            <input type="search" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Buscar por nombre, usuario o email..." class="admin-search-input">
            <?php if ($q): ?><a href="./usuarios.php" class="admin-search-clear">&times;</a><?php endif; ?>
        </form>
    </div>

    <!-- Estadísticas rápidas -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(99,102,241,0.1); color: #6366f1;"><i class="bi bi-people-fill"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?= $totalUsuarios ?></span>
                <span class="stat-label">Total Usuarios</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(16,185,129,0.1); color: #10b981;"><i class="bi bi-person-plus-fill"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?= $ultimos7dias ?></span>
                <span class="stat-label">Nuevos (7 días)</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(245,158,11,0.1); color: #f59e0b;"><i class="bi bi-shield-lock-fill"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?= $superadmins ?></span>
                <span class="stat-label">Admins</span>
            </div>
        </div>
    </div>

    <!-- Toolbar -->
    <div class="contenidos-toolbar">
        <div></div>
        <div class="contenidos-actions">
            <?php if ($ACL['crear']): ?>
                <a href="./crearu.php" class="btn btn-accent"><i class="bi bi-plus-lg"></i> Crear nuevo</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="card shadow-sm" id="panelUsuarios">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="contenidos-table">
                    <thead>
                        <tr>
                            <th>Avatar</th>
                            <th>Nombre</th>
                            <th>Usuario</th>
                            <th>Email</th>
                            <th>Fecha Registro</th>
                            <?php if ($ACL['editar'] || $ACL['eliminar'] || $ACL['leer']): ?>
                                <th>Acciones</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($usuarios)): ?>
                            <tr><td colspan="6" style="text-align:center; padding:30px; color:var(--muted);">No se encontraron usuarios.</td></tr>
                        <?php else: ?>
                            <?php foreach ($usuarios as $u): ?>
                            <tr>
                                <td>
                                    <?php if ($ACL['editar']): ?>
                                    <div class="avatar-wrap" data-id="<?= $u['id_u'] ?>"
                                         data-nombre="<?= htmlspecialchars($u['nombre'], ENT_QUOTES) ?>"
                                         data-foto="<?= htmlspecialchars($u['foto_personal'] ?? '', ENT_QUOTES) ?>"
                                         title="Cambiar foto" style="cursor:pointer; position:relative; display:inline-block;">
                                    <?php endif; ?>
                                        <?php if (!empty($u['foto_personal'])): ?>
                                            <img src="<?= imageUrl($u['foto_personal']) ?>" alt="Foto de perfil"
                                                 style="width:40px; height:40px; border-radius:50%; object-fit:cover; display:block;">
                                        <?php else: ?>
                                            <div style="width:40px; height:40px; border-radius:50%; background:var(--accent); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:bold;">
                                                <?= strtoupper(mb_substr($u['nombre'], 0, 1)) ?>
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($ACL['editar']): ?>
                                            <div class="avatar-overlay"><i class="bi bi-camera-fill"></i></div>
                                    </div>
                                    <?php endif; ?>
                                </td>
                                <td><strong class="table-title"><?= htmlspecialchars($u['nombre']) ?></strong></td>
                                <td><span class="estado-badge estado-programado" style="background:rgba(99,102,241,0.1); color:#6366f1;">@<?= htmlspecialchars($u['usuario']) ?></span></td>
                                <td><span style="font-size:0.9rem; color:var(--text);"><?= htmlspecialchars($u['correo']) ?></span></td>
                                <td class="table-date"><?= date('d/m/Y', strtotime($u['registro'])) ?></td>
                                <?php if ($ACL['editar'] || $ACL['eliminar'] || $ACL['leer']): ?>
                                    <td>
                                        <div class="noticias-actions" style="border-top:none; padding:0; justify-content:flex-start;">
                                            <?php if ($ACL['editar']): ?>
                                                <a href="./editaru.php?id=<?= $u['id_u'] ?>" class="btn btn-edit" title="Editar Usuario"><i class="bi bi-pencil-square"></i></a>
                                            <?php endif; ?>
                                            <?php if ($ACL['leer']): ?>
                                                <a href="./veru.php?id=<?= $u['id_u'] ?>" class="btn btn-view" title="Ver Usuario"><i class="bi bi-eye"></i></a>
                                            <?php endif; ?>
                                            <?php if ($ACL['eliminar'] && $u['id_u'] != 1): ?>
                                                <button type="button" class="btn btn-delete btn-delete-usuario"
                                                    data-id="<?= $u['id_u'] ?>"
                                                    data-nombre="<?= htmlspecialchars($u['nombre'], ENT_QUOTES) ?>"
                                                    title="Eliminar Usuario"><i class="bi bi-trash"></i></button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                <?php endif; ?>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php if ($esSuper): ?>
    <!-- PANEL: BANEOS (movido desde el perfil del superadmin) -->
    <div class="card shadow-sm" id="panelBaneos" data-base="<?= basePath() ?>" style="display:none;">
        <div class="umod-header">
            <i class="bi bi-shield-lock-fill"></i>
            <div>
                <h2>Gestión de baneos</h2>
                <p>Consulta usuarios suspendidos y activos, y modera sus cuentas.</p>
            </div>
        </div>

        <!-- Toggle Suspendidos / Activos -->
        <div class="umod-toggle">
            <button type="button" class="umod-tgl active" data-view="suspendidos">
                <i class="bi bi-slash-circle"></i> Suspendidos
                <span class="umod-count" id="susCount" <?= count($baneados) ? '' : 'style="display:none;"' ?>><?= count($baneados) ?></span>
            </button>
            <button type="button" class="umod-tgl" data-view="activos">
                <i class="bi bi-person-check"></i> Activos
            </button>
        </div>

        <!-- Vista: Suspendidos -->
        <div class="umod-view" id="viewSuspendidos">
            <div class="umod-list" id="modList">
                <?php if(empty($baneados)): ?>
                    <div class="umod-empty" id="modEmpty">
                        <i class="bi bi-check-circle"></i>
                        <p>No hay usuarios suspendidos en este momento.</p>
                    </div>
                <?php else: foreach($baneados as $b):
                    $bpal = explode(' ', $b['nombre']);
                    $bini = strtoupper(substr($bpal[0],0,1) . (isset($bpal[1]) ? substr($bpal[1],0,1) : ''));
                    $estadoTxt = textoBaneo($b);
                    $perfilUrl = $b['tipo'] === 'admin'
                        ? basePath() . '/autor/' . (int)$b['id']
                        : basePath() . '/usuario/' . (int)$b['id'];
                ?>
                <div class="umod-row" data-tipo="<?= $b['tipo'] ?>" data-userid="<?= (int)$b['id'] ?>">
                    <?php if(!empty($b['avatar_img'])): ?>
                        <img class="umod-avatar" src="<?= imageUrl($b['avatar_img']) ?>" alt="Avatar de <?= htmlspecialchars($b['nombre']) ?>">
                    <?php else: ?>
                        <div class="umod-avatar"><?= htmlspecialchars($bini) ?></div>
                    <?php endif; ?>
                    <div class="umod-info">
                        <div class="umod-nombre">
                            <?= htmlspecialchars($b['nombre']) ?>
                            <span class="umod-badge umod-badge--<?= $b['tipo'] ?>">
                                <?= $b['tipo'] === 'admin' ? 'Editor' : 'Lector' ?>
                            </span>
                        </div>
                        <div class="umod-user">@<?= htmlspecialchars($b['usuario']) ?></div>
                        <div class="umod-estado"><i class="bi bi-slash-circle"></i> <?= htmlspecialchars($estadoTxt) ?></div>
                        <?php if(!empty($b['baneado_motivo'])): ?>
                            <div class="umod-motivo"><i class="bi bi-chat-quote"></i> <?= htmlspecialchars($b['baneado_motivo']) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="umod-acciones">
                        <a href="<?= $perfilUrl ?>" class="umod-ver" title="Ver perfil" target="_blank"><i class="bi bi-box-arrow-up-right"></i> Ver</a>
                        <button type="button" class="umod-unban"><i class="bi bi-check-circle"></i> Quitar suspensión</button>
                    </div>
                </div>
                <?php endforeach; endif; ?>
            </div>
        </div>

        <!-- Vista: Activos -->
        <div class="umod-view" id="viewActivos" style="display:none;">
            <div class="umod-search">
                <i class="bi bi-search"></i>
                <input type="text" id="modBuscarActivos" placeholder="Buscar por nombre o @usuario..." autocomplete="off">
            </div>
            <div class="umod-list" id="modListActivos">
                <div class="umod-empty" id="modActivosEmpty">
                    <i class="bi bi-people"></i>
                    <p>Cargando usuarios activos...</p>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php if ($esSuper): ?>
<!-- Modal suspender usuario (desde la lista de activos) -->
<div id="modSuspenderModal" class="crop-modal" style="display:none;">
    <div class="crop-modal-content">
        <h3><i class="bi bi-slash-circle" style="color:#ef3333;"></i> Suspender a <span id="modSuspNombre"></span></h3>
        <p style="color:var(--muted); font-size:0.9rem; margin:5px 0 15px;">El usuario no podrá comentar, dar me gusta ni reportar mientras dure la suspensión.</p>
        <div style="margin-bottom:12px;">
            <label style="display:block; font-size:0.85rem; font-weight:600; margin-bottom:6px; color:var(--text);">Duración</label>
            <select id="modSuspDuracion" style="width:100%; padding:8px; border:1px solid var(--border); border-radius:8px; font-size:0.85rem; background:var(--card-bg); color:var(--text);">
                <?php foreach(duracionesBaneo() as $key => $d): ?>
                    <option value="<?= $key ?>"><?= htmlspecialchars($d['label']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <input type="text" id="modSuspMotivo" maxlength="255" placeholder="Motivo (opcional)"
               style="width:100%; padding:8px; border:1px solid var(--border); border-radius:8px; font-size:0.85rem; background:var(--card-bg); color:var(--text); margin-bottom:16px;">
        <div class="crop-actions" style="display:flex; justify-content:flex-end; gap:8px;">
            <button type="button" id="modSuspCancel" class="btn btn-secondary">Cancelar</button>
            <button type="button" id="modSuspConfirm" class="btn btn-accent"><i class="bi bi-slash-circle"></i> Suspender</button>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Modal foto de perfil -->
<div id="modalFoto" class="crop-modal" style="display:none;">
    <div class="card" style="max-width:380px; width:100%;">
        <div class="crop-modal-content">
            <h3><i class="bi bi-camera-fill"></i> Foto de perfil — <span id="fotoNombre"></span></h3>
            <div style="text-align:center; margin:16px 0;">
                <div id="fotoPreviewWrap" style="width:100px; height:100px; border-radius:50%; overflow:hidden; margin:0 auto; background:var(--accent); display:flex; align-items:center; justify-content:center;">
                    <img id="fotoPreview" src="" alt="" style="width:100%; height:100%; object-fit:cover; display:none;">
                    <span id="fotoInicial" style="color:#fff; font-size:2rem; font-weight:bold;"></span>
                </div>
            </div>
            <div style="margin-bottom:16px;">
                <label style="display:block; font-size:0.85rem; font-weight:600; margin-bottom:6px; color:var(--text);">Subir nueva foto</label>
                <input type="file" id="fotoInput" accept="image/*"
                       style="width:100%; padding:8px; border:1px solid var(--border); border-radius:8px; font-size:0.85rem; background:var(--card-bg); color:var(--text);">
            </div>
            <div style="display:flex; justify-content:space-between; gap:8px;">
                <button type="button" id="btnEliminarFoto" class="btn btn-delete" style="display:none;">
                    <i class="bi bi-trash"></i> Eliminar foto
                </button>
                <div style="display:flex; gap:8px; margin-left:auto;">
                    <button type="button" id="modalFotoClose" class="btn btn-secondary">Cancelar</button>
                    <button type="button" id="btnGuardarFoto" class="btn btn-accent" disabled>Guardar</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal eliminación usuario -->
<div id="modalOverlayU" class="crop-modal" style="display: none;">
    <div class="crop-modal-content">
        <h3 id="modalTitleU"><i class="bi bi-trash"></i> Confirmar eliminación</h3>
        <p style="color:var(--muted); font-size:0.9rem; margin-bottom:15px; margin-top:5px;">¿Estás seguro de que deseas eliminar este usuario? Esta acción no se puede deshacer.</p>
        <input type="hidden" id="modalIdU">
        <div class="crop-actions" style="display:flex; justify-content:flex-end; gap:8px;">
            <button type="button" class="btn btn-secondary btn-cancel-u">Cancelar</button>
            <button type="button" id="btnConfirmDelete" class="btn btn-accent">Eliminar</button>
        </div>
    </div>
</div>

<?php include("./../layout/footerAdmin.php"); ?>

<style>
.avatar-wrap:hover .avatar-overlay { opacity: 1; }
.avatar-overlay {
    position: absolute; inset: 0;
    border-radius: 50%;
    background: rgba(0,0,0,0.45);
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-size: 1rem;
    opacity: 0; transition: opacity .2s;
    pointer-events: none;
}

/* ============================================================
   PANEL DE BANEOS (movido desde el perfil del superadmin)
   ============================================================ */
.umod-count {
  background:#ef3333; color:#fff; font-size:.7rem; font-weight:700;
  min-width:18px; height:18px; padding:0 5px; border-radius:9px;
  display:inline-flex; align-items:center; justify-content:center; margin-left:2px;
}
.tab-btn.active .umod-count { background:#fff; color:var(--accent); }
.umod-header {
  display:flex; align-items:flex-start; gap:14px;
  padding:22px 24px; border-bottom:1px solid var(--border);
  font-size:1.6rem; color:var(--accent);
}
.umod-header h2 { margin:0 0 2px; font-size:1.05rem; font-weight:700; color:var(--text); }
.umod-header p  { margin:0; font-size:.83rem; color:var(--muted); }
.umod-toggle { display:flex; gap:6px; padding:14px 16px 0; flex-wrap:wrap; }
.umod-tgl {
  display:inline-flex; align-items:center; gap:6px;
  padding:8px 16px; border-radius:50px; cursor:pointer;
  background:transparent; border:1px solid var(--border); color:var(--muted);
  font-size:.85rem; font-weight:600; transition:background .15s, color .15s, border-color .15s;
}
.umod-tgl:hover { border-color:var(--accent); color:var(--accent); }
.umod-tgl.active { background:var(--accent); border-color:var(--accent); color:#fff; }
.umod-tgl.active .umod-count { background:#fff; color:var(--accent); }
.umod-search {
  display:flex; align-items:center; gap:8px; padding:14px 16px 4px;
  position:relative;
}
.umod-search i { position:absolute; left:28px; color:var(--muted); pointer-events:none; }
.umod-search input {
  width:100%; padding:9px 12px 9px 34px;
  border:1px solid var(--border); border-radius:8px; font-size:.88rem;
  background:var(--card-bg); color:var(--text);
}
.umod-list { padding:12px 16px; display:flex; flex-direction:column; gap:10px; }
.umod-empty { text-align:center; color:var(--muted); padding:32px 0; }
.umod-empty i { font-size:2rem; color:#1a7f37; display:block; margin-bottom:8px; }
.umod-empty p { margin:0; font-size:.92rem; }
.umod-row {
  display:flex; align-items:center; gap:14px;
  padding:14px 16px; border-radius:12px;
  background:var(--bg); border:1px solid var(--border);
}
.umod-avatar {
  width:44px; height:44px; border-radius:50%; flex-shrink:0;
  background:linear-gradient(135deg, var(--accent), #7c1d5e);
  color:#fff; font-weight:700; font-size:.95rem;
  display:flex; align-items:center; justify-content:center;
  object-fit:cover;
}
.umod-info { flex:1; min-width:0; }
.umod-nombre { font-weight:700; color:var(--text); display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
.umod-badge { font-size:.68rem; font-weight:700; padding:2px 8px; border-radius:50px; text-transform:uppercase; letter-spacing:.3px; }
.umod-badge--admin  { background:rgba(239,51,99,.12); color:var(--accent); }
.umod-badge--lector { background:rgba(120,120,120,.15); color:var(--muted); }
.umod-user { font-size:.82rem; color:var(--muted); margin-top:1px; }
.umod-estado { font-size:.82rem; color:#ef3333; font-weight:600; margin-top:5px; display:flex; align-items:center; gap:5px; }
.umod-motivo { font-size:.8rem; color:var(--muted); margin-top:3px; display:flex; align-items:center; gap:5px; }
.umod-acciones { display:flex; flex-direction:column; gap:7px; flex-shrink:0; align-items:stretch; }
.umod-ver {
  display:inline-flex; align-items:center; justify-content:center; gap:5px;
  font-size:.8rem; font-weight:600; color:var(--muted); text-decoration:none;
  border:1px solid var(--border); border-radius:8px; padding:6px 12px;
}
.umod-ver:hover { border-color:var(--accent); color:var(--accent); }
.umod-unban {
  display:inline-flex; align-items:center; justify-content:center; gap:5px;
  font-size:.8rem; font-weight:600; cursor:pointer;
  background:transparent; color:#1a7f37; border:1px solid #1a7f37;
  border-radius:8px; padding:6px 12px; white-space:nowrap;
}
.umod-unban:hover { background:#1a7f37; color:#fff; }
.umod-unban:disabled { opacity:.6; cursor:not-allowed; }
.umod-suspend {
  display:inline-flex; align-items:center; justify-content:center; gap:5px;
  font-size:.8rem; font-weight:600; cursor:pointer;
  background:transparent; color:#ef3333; border:1px solid #ef3333;
  border-radius:8px; padding:6px 12px; white-space:nowrap;
}
.umod-suspend:hover { background:#ef3333; color:#fff; }
.umod-suspend:disabled { opacity:.6; cursor:not-allowed; }
@media (max-width: 600px) {
  .umod-row { flex-wrap:wrap; }
  .umod-acciones { flex-direction:row; width:100%; }
  .umod-acciones > * { flex:1; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {

    // ── Modal foto de perfil ─────────────────────────────────────
    const modalFoto    = document.getElementById('modalFoto');
    const fotoInput    = document.getElementById('fotoInput');
    const fotoPreview  = document.getElementById('fotoPreview');
    const fotoInicial  = document.getElementById('fotoInicial');
    const btnGuardar   = document.getElementById('btnGuardarFoto');
    const btnEliminar  = document.getElementById('btnEliminarFoto');
    let fotoUserId = null;

    document.querySelectorAll('.avatar-wrap').forEach(wrap => {
        wrap.addEventListener('click', () => {
            fotoUserId = wrap.dataset.id;
            const foto = wrap.dataset.foto;
            const nombre = wrap.dataset.nombre;

            document.getElementById('fotoNombre').textContent = nombre;
            fotoInput.value = '';
            btnGuardar.disabled = true;

            if (foto) {
                fotoPreview.src = `<?= basePath() ?>/serve-image.php?file=${encodeURIComponent(foto)}`;
                fotoPreview.style.display = 'block';
                fotoInicial.style.display = 'none';
                btnEliminar.style.display = 'inline-flex';
            } else {
                fotoPreview.style.display = 'none';
                fotoInicial.textContent = nombre.charAt(0).toUpperCase();
                fotoInicial.style.display = 'block';
                btnEliminar.style.display = 'none';
            }

            modalFoto.style.display = 'flex';
        });
    });

    fotoInput.addEventListener('change', () => {
        const file = fotoInput.files[0];
        if (!file) { btnGuardar.disabled = true; return; }
        btnGuardar.disabled = false;
        const reader = new FileReader();
        reader.onload = e => {
            fotoPreview.src = e.target.result;
            fotoPreview.style.display = 'block';
            fotoInicial.style.display = 'none';
        };
        reader.readAsDataURL(file);
    });

    btnGuardar.addEventListener('click', () => {
        const fd = new FormData();
        fd.append('id_u', fotoUserId);
        fd.append('foto', fotoInput.files[0]);
        fetch('./../controllers/cambiar_foto_usuario.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(d => {
                if (d.success) { showToast(d.success, 'success'); setTimeout(() => location.reload(), 900); }
                else showToast(d.error || 'Error', 'error');
            })
            .catch(() => showToast('Error en la petición', 'error'));
        modalFoto.style.display = 'none';
    });

    btnEliminar.addEventListener('click', () => {
        const fd = new FormData();
        fd.append('id_u', fotoUserId);
        fd.append('eliminar', '1');
        fetch('./../controllers/cambiar_foto_usuario.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(d => {
                if (d.success) { showToast(d.success, 'success'); setTimeout(() => location.reload(), 900); }
                else showToast(d.error || 'Error', 'error');
            })
            .catch(() => showToast('Error en la petición', 'error'));
        modalFoto.style.display = 'none';
    });

    document.getElementById('modalFotoClose').addEventListener('click', () => { modalFoto.style.display = 'none'; });
    modalFoto.addEventListener('click', e => { if (e.target === modalFoto) modalFoto.style.display = 'none'; });

    // ── Modal eliminación usuario ────────────────────────────────
    const modal = document.getElementById('modalOverlayU');

    document.querySelectorAll('.btn-delete-usuario').forEach(btn => {
        btn.addEventListener('click', () => {
            document.getElementById('modalIdU').value = btn.dataset.id;
            document.getElementById('modalTitleU').innerHTML =
                `<i class="bi bi-trash"></i> Eliminar "${btn.dataset.nombre}"`;
            modal.style.display = 'flex';
        });
    });

    document.querySelector('.btn-cancel-u').addEventListener('click', () => {
        modal.style.display = 'none';
    });
    modal.addEventListener('click', e => { if (e.target === modal) modal.style.display = 'none'; });

    document.getElementById('btnConfirmDelete').addEventListener('click', () => {
        const id = document.getElementById('modalIdU').value;
        const fd = new FormData();
        fd.append('id', id);
        fetch('./../controllers/eliminarusuario.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(d => {
                if (d.success) { showToast(d.success, 'success'); setTimeout(() => location.reload(), 1000); }
                else showToast(d.error || 'Error al eliminar', 'error');
            })
            .catch(() => showToast('Error en la petición', 'error'));
        modal.style.display = 'none';
    });
});
</script>


