<?php
include("./../layout/headerAdmin.php");
include("./../controllers/aclcontroller.php");
$ACL = $_SESSION['ACL']['usuarios']??[
    'crear' => false,
    'leer' => false,
    'editar' => false,
    'eliminar' => false,
];
if (!$ACL['editar']) {
    header("Location: admin.php");
    exit();
}
proteger('usuarios', 'editar');
include("./../data/conexion.php");
$id = $_GET['id'];
$stmt = $con->prepare("SELECT * FROM usuarios WHERE id_u=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Editar Usuario</h1>
    </div>
    <a href="./../views/usuarios.php" class="btn btn-secondary"><i class="bi bi-arrow-return-left"></i> Regresar</a>
    <form id="editUserForm" action="./../controllers/editarusuario.php?id=<?= $id ?>" method="POST">
        <div class="form-card card">
            <input type="hidden" name="id" value="<?= $id ?>">
            <div class="form-group">
                <label>Nombre</label>
                <input type="text" name="nombre" value="<?= $user['nombre'] ?>" required>
            </div>
            <div class="form-group">
                <label>Usuario</label>
                <input type="text" name="usuario" value="<?= $user['usuario'] ?>" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" value="<?= $user['correo'] ?>" required>
            </div>
            <!-- CONTRASEÑA OPCIONAL -->
            <div class="form-group">
                <label>Nueva Contraseña (opcional)</label>
                <input type="text" name="password">
                <label>Confirmar Contraseña</label>
                <input type="text" name="confirm_password">
            </div>
            <style>
            .form-switch {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 6px 12px;
                border-radius: 8px;
                background: var(--bg);
                border: 1px solid var(--border);
                margin-bottom: 6px;
                cursor: pointer;
                transition: all 0.2s ease;
            }
            .form-switch:hover {
                border-color: var(--accent);
                background: rgba(239, 51, 99, 0.02);
            }
            .form-switch span {
                font-weight: 500;
                font-size: 0.9rem;
                color: var(--text);
            }
            .form-switch-input {
                appearance: none;
                width: 38px;
                height: 20px;
                background-color: #ccc;
                border-radius: 20px;
                position: relative;
                outline: none;
                cursor: pointer;
                transition: background-color 0.2s ease;
                border: none;
                margin: 0 !important;
            }
            .form-switch-input:checked {
                background-color: #28a745;
            }
            .form-switch-input::before {
                content: '';
                position: absolute;
                width: 14px;
                height: 14px;
                border-radius: 50%;
                background-color: white;
                top: 3px;
                left: 3px;
                transition: transform 0.2s ease;
            }
            .form-switch-input:checked::before {
                transform: translateX(18px);
            }
            .btn-check-icon {
                background: transparent;
                border: none;
                color: var(--muted);
                font-size: 1.1rem;
                cursor: pointer;
                padding: 2px 6px;
                border-radius: 4px;
                transition: all 0.2s;
            }
            .btn-check-icon:hover {
                color: #28a745;
                background: rgba(40, 167, 69, 0.1);
            }
            .btn-check-icon.btn-uncheck:hover {
                color: #dc3545;
                background: rgba(220, 53, 69, 0.1);
            }
            </style>

            <div class="form-group" style="margin-top: 24px;">
                <?php
                    function check($perm, $bit){ return ($perm & $bit) ? 'checked' : ''; }
                ?>
                <div class="card shadow-sm mb-4" style="border: 1px solid var(--border); border-radius: 12px; background: var(--card-bg); padding: 20px;">
                    <h5 style="margin-top:0; margin-bottom:15px; font-weight:600;"><i class="bi bi-shield-lock-fill text-accent"></i> Asignación Rápida de Rol</h5>
                    <p class="text-muted small">Selecciona una plantilla para autocompletar los permisos o personalízalos manualmente.</p>
                    <div style="display:flex; gap:16px; align-items:center; flex-wrap:wrap; margin-top: 15px;">
                        <select id="roleSelector" class="form-control" style="max-width: 320px; padding: 10px 14px; border-radius: 8px; border: 1px solid var(--border); background: var(--bg); color: var(--text);">
                            <option value="custom" selected>Personalizado (Selección manual)</option>
                            <option value="superadmin">Superadministrador (Acceso total)</option>
                            <option value="admin">Administrador General</option>
                            <option value="editor">Editor de Contenidos</option>
                            <option value="moderador">Moderador de Comunidad</option>
                        </select>

                        <div style="display:flex; gap:8px; flex-wrap: wrap;">
                            <button type="button" class="btn btn-sm btn-accent" style="padding: 8px 12px; font-size:0.85rem;" onclick="bulkCheckAll(true)">Marcar Todo</button>
                            <button type="button" class="btn btn-sm btn-secondary" style="padding: 8px 12px; font-size:0.85rem; background:#6c757d; border:none; color:#fff;" onclick="bulkCheckAll(false)">Desmarcar Todo</button>
                            <button type="button" class="btn btn-sm btn-outline-accent" style="padding: 8px 12px; font-size:0.85rem; border: 1px solid var(--accent); background: transparent; color: var(--accent);" onclick="bulkCheckAction(2)">Marcar todos los 'Ver'</button>
                            <button type="button" class="btn btn-sm btn-outline-accent" style="padding: 8px 12px; font-size:0.85rem; border: 1px solid var(--accent); background: transparent; color: var(--accent);" onclick="bulkCheckAction(1)">Marcar todos los 'Crear'</button>
                        </div>
                    </div>
                </div>

                <div class="row-permisos" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px;">
                    <?php
                    // Mismos modulos y orden que crearu.php. Si el formulario no
                    // manda un modulo, el controlador lo interpreta como 0 y borra
                    // el permiso: deben estar los 14 siempre.
                    $modulosConfig = [
                        'publicidad' => 'Publicidad',
                        'noticias' => 'Noticias',
                        'categorias' => 'Categorías',
                        'correos' => 'Correos',
                        'suscripciones' => 'Suscripciones',
                        'usuarios' => 'Administradores/Editores',
                        'videos' => 'Videos',
                        'lectores' => 'Lectores',
                        'recomendados' => 'Recomendados',
                        'esperamos' => 'Próximos Estrenos',
                        'paginas' => 'Páginas y Logos',
                        'actividad' => 'Bitácora de Actividades',
                        'papelera' => 'Papelera de Noticias',
                        'avatares' => 'Fotos de Perfil'
                    ];
                    $acciones = [1 => 'Crear', 2 => 'Ver', 4 => 'Editar', 8 => 'Eliminar'];

                    foreach ($modulosConfig as $slug => $nombre):
                        $permActual = (int)($user['perm_' . $slug] ?? 0);
                    ?>
                    <div class="col-permiso" style="max-width: 100%;">
                        <div class="card" style="border: 1px solid var(--border); border-radius: 12px; background: var(--card-bg); overflow: hidden; height: 100%; display: flex; flex-direction: column;">
                            <div class="card-header d-flex justify-content-between align-items-center" style="padding: 12px 16px; border-bottom: 1px solid var(--border); background: rgba(0,0,0,0.02);">
                                <h6 class="mb-0" style="font-weight:600; font-size: 0.95rem; margin: 0;"><?= $nombre ?></h6>
                                <div style="display:flex; gap:6px;">
                                    <button type="button" class="btn-check-icon" title="Marcar todos en este módulo" onclick="checkModule('<?= $slug ?>', true)">&check;</button>
                                    <button type="button" class="btn-check-icon btn-uncheck" title="Desmarcar todos en este módulo" onclick="checkModule('<?= $slug ?>', false)">&times;</button>
                                </div>
                            </div>
                            <div class="card-body" style="padding: 16px; flex-grow: 1; display: flex; flex-direction: column; justify-content: center;">
                                <?php foreach ($acciones as $bit => $etiqueta): ?>
                                <label class="form-switch">
                                    <span><?= $etiqueta ?></span>
                                    <input type="checkbox" name="<?= $slug ?>[]" value="<?= $bit ?>" class="form-switch-input mod-chk-<?= $slug ?>" data-action="<?= $bit ?>" <?= check($permActual, $bit) ?>>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="form-actions">
                <?php if($ACL['editar']): ?>
                    <button type="submit" class="btn btn-accent">Actualizar Usuario</button>
                <?php endif; ?>
            </div>
        </div>
    </form>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('editUserForm');
    if (!form) return;

    // Mismas plantillas de rol que crearu.php.
    const roles = {
        superadmin: {
            publicidad: [1, 2, 4, 8], noticias: [1, 2, 4, 8], categorias: [1, 2, 4, 8],
            correos: [1, 2, 4, 8], suscripciones: [1, 2, 4, 8], usuarios: [1, 2, 4, 8],
            videos: [1, 2, 4, 8], lectores: [1, 2, 4, 8], recomendados: [1, 2, 4, 8],
            esperamos: [1, 2, 4, 8], paginas: [1, 2, 4, 8], actividad: [1, 2, 4, 8],
            papelera: [1, 2, 4, 8], avatares: [1, 2, 4, 8]
        },
        admin: {
            publicidad: [1, 2, 4, 8], noticias: [1, 2, 4, 8], categorias: [1, 2, 4, 8],
            correos: [1, 2, 4, 8], suscripciones: [1, 2, 4, 8], usuarios: [2],
            videos: [1, 2, 4, 8], lectores: [1, 2, 4, 8], recomendados: [1, 2, 4, 8],
            esperamos: [1, 2, 4, 8], paginas: [1, 2, 4, 8], actividad: [2],
            papelera: [2], avatares: [1, 2, 4, 8]
        },
        editor: {
            publicidad: [2], noticias: [1, 2, 4], categorias: [1, 2, 4],
            correos: [2], suscripciones: [2], usuarios: [],
            videos: [1, 2, 4], lectores: [2], recomendados: [1, 2, 4],
            esperamos: [1, 2, 4], paginas: [2], actividad: [],
            papelera: [2], avatares: [2]
        },
        moderador: {
            publicidad: [], noticias: [2], categorias: [2],
            correos: [], suscripciones: [2, 4], usuarios: [],
            videos: [2], lectores: [2, 4], recomendados: [2],
            esperamos: [2], paginas: [], actividad: [],
            papelera: [], avatares: []
        }
    };

    const updateRoleSelector = () => {
        const roleSelector = document.getElementById('roleSelector');
        if (!roleSelector) return;
        let matchedRole = 'custom';
        for (const [roleName, rolePerms] of Object.entries(roles)) {
            let isMatch = true;
            for (const [slug, actions] of Object.entries(rolePerms)) {
                const checkedVals = Array.from(document.querySelectorAll(`.mod-chk-${slug}:checked`)).map(i => parseInt(i.value));
                if (checkedVals.length !== actions.length || !checkedVals.every(v => actions.includes(v))) {
                    isMatch = false;
                    break;
                }
            }
            if (isMatch) { matchedRole = roleName; break; }
        }
        roleSelector.value = matchedRole;
    };

    const applyRole = (roleName) => {
        if (roleName === 'custom') return;
        const rolePerms = roles[roleName];
        if (!rolePerms) return;
        document.querySelectorAll('.form-switch-input').forEach(i => i.checked = false);
        for (const [slug, actions] of Object.entries(rolePerms)) {
            actions.forEach(actionVal => {
                const input = document.querySelector(`.mod-chk-${slug}[data-action="${actionVal}"]`);
                if (input) input.checked = true;
            });
        }
    };

    window.checkModule = (slug, checked) => {
        document.querySelectorAll(`.mod-chk-${slug}`).forEach(i => i.checked = checked);
        updateRoleSelector();
    };
    window.bulkCheckAll = (checked) => {
        document.querySelectorAll('.form-switch-input').forEach(i => i.checked = checked);
        updateRoleSelector();
    };
    window.bulkCheckAction = (actionVal) => {
        document.querySelectorAll(`.form-switch-input[data-action="${actionVal}"]`).forEach(i => i.checked = true);
        updateRoleSelector();
    };

    const roleSelector = document.getElementById('roleSelector');
    if (roleSelector) {
        roleSelector.addEventListener('change', () => applyRole(roleSelector.value));
    }
    document.querySelectorAll('.form-switch-input').forEach(input => {
        input.addEventListener('change', updateRoleSelector);
    });
    // Al abrir, refleja en el selector el rol que ya tiene el usuario.
    updateRoleSelector();

    // El controlador responde JSON: hay que enviarlo por fetch. Con un submit
    // normal Turbo intercepta la respuesta, no encuentra HTML ni redirección y
    // la descarta en silencio, dejando el botón sin efecto aparente.
    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const btn = form.querySelector('button[type="submit"]');
        if (btn) btn.disabled = true;

        try {
            const res = await fetch(form.action, { method: 'POST', body: new FormData(form) });
            let data = null;
            try { data = await res.json(); } catch (_) {}

            if (!data) {
                showToast('El servidor respondió con un error (' + res.status + ')', 'error');
                if (btn) btn.disabled = false;
                return;
            }
            if (data.success) {
                showToast(data.success, 'success');
                setTimeout(() => { window.location.href = 'usuarios.php'; }, 1200);
            } else {
                showToast(data.error || 'No se pudo actualizar el usuario', 'error');
                if (btn) btn.disabled = false;
            }
        } catch (_) {
            showToast('Error de conexión al actualizar el usuario', 'error');
            if (btn) btn.disabled = false;
        }
    });
});
</script>
<?php include("./../layout/footerAdmin.php"); ?>
