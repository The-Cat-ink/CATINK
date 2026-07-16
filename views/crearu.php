<?php
    include("./../layout/headerAdmin.php");
    include("./../controllers/aclcontroller.php");
    $ACL = $_SESSION['ACL']['usuarios']??[
        'crear' => false,
        'leer' => false,
        'editar' => false,
        'eliminar' => false,
    ];
    if (!$ACL['crear']) {
        header("Location: admin.php");
        exit();
    }
    proteger('usuarios', 'crear');
    include("./../data/conexion.php");
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Crear Usuario</h1>
        <a href="usuarios.php" class="btn btn-accent"><i class="bi bi-arrow-left"></i> Volver</a>
    </div>
    <form id="formUsuario" action="./../controllers/altausuarios.php" method="POST">
        <div class="form-card card">
            <div class="form-group">
                <label for="nombre">Nombre</label>
                <span>Nombre completo</span>
                <input type="text" id="nombre" name="nombre" required>
            </div>
            <div class="form-group">
                <label for="usuario">Usuario</label>
                <span>Nombre de usuario</span>
                <input type="text" id="usuario" name="usuario" required>
                <small id="usuarioEstado"></small>
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <span>Correo electrónico</span>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="password">Contraseña</label>
                <span>Contraseña</span>
                <input type="text" id="password" name="password" required>
                <span>Confirma contraseña</span>
                <input type="text" id="confirm_password" name="confirm_password" required>
                <small id="errorPassword" style="color:#dc3545; display:none;">
                    Las contraseñas no coinciden
                </small>
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

                    foreach ($modulosConfig as $slug => $nombre):
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
                                <label class="form-switch">
                                    <span>Crear</span>
                                    <input type="checkbox" name="<?= $slug ?>[]" value="1" class="form-switch-input mod-chk-<?= $slug ?>" data-action="1">
                                </label>
                                <label class="form-switch">
                                    <span>Ver</span>
                                    <input type="checkbox" name="<?= $slug ?>[]" value="2" class="form-switch-input mod-chk-<?= $slug ?>" data-action="2">
                                </label>
                                <label class="form-switch">
                                    <span>Editar</span>
                                    <input type="checkbox" name="<?= $slug ?>[]" value="4" class="form-switch-input mod-chk-<?= $slug ?>" data-action="4">
                                </label>
                                <label class="form-switch">
                                    <span>Eliminar</span>
                                    <input type="checkbox" name="<?= $slug ?>[]" value="8" class="form-switch-input mod-chk-<?= $slug ?>" data-action="8">
                                </label>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="form-actions" style="margin-top: 24px;">
                <?php if($ACL['crear']): ?>
                    <button type="submit" class="btn btn-accent" style="padding: 10px 24px; font-weight:600; border-radius: 8px;">Crear Usuario</button>
                <?php endif; ?>
            </div>
        </div>
    </form>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {

    const form = document.getElementById('formUsuario');
    const inputUsuario = document.getElementById('usuario');
    const estado = document.getElementById('usuarioEstado');

    const passInput = document.getElementById('password');
    const confirmInput = document.getElementById('confirm_password');
    const errorPassword = document.getElementById('errorPassword');

    let usuarioValido = false;

    // Roles y sus permisos correspondientes
    const roles = {
        superadmin: {
            publicidad: [1, 2, 4, 8],
            noticias: [1, 2, 4, 8],
            categorias: [1, 2, 4, 8],
            correos: [1, 2, 4, 8],
            suscripciones: [1, 2, 4, 8],
            usuarios: [1, 2, 4, 8],
            videos: [1, 2, 4, 8],
            lectores: [1, 2, 4, 8],
            recomendados: [1, 2, 4, 8],
            esperamos: [1, 2, 4, 8],
            paginas: [1, 2, 4, 8],
            actividad: [1, 2, 4, 8],
            papelera: [1, 2, 4, 8],
            avatares: [1, 2, 4, 8]
        },
        admin: {
            publicidad: [1, 2, 4, 8],
            noticias: [1, 2, 4, 8],
            categorias: [1, 2, 4, 8],
            correos: [1, 2, 4, 8],
            suscripciones: [1, 2, 4, 8],
            usuarios: [2],
            videos: [1, 2, 4, 8],
            lectores: [1, 2, 4, 8],
            recomendados: [1, 2, 4, 8],
            esperamos: [1, 2, 4, 8],
            paginas: [1, 2, 4, 8],
            actividad: [2],
            papelera: [2],
            avatares: [1, 2, 4, 8]
        },
        editor: {
            publicidad: [2],
            noticias: [1, 2, 4],
            categorias: [1, 2, 4],
            correos: [2],
            suscripciones: [2],
            usuarios: [],
            videos: [1, 2, 4],
            lectores: [2],
            recomendados: [1, 2, 4],
            esperamos: [1, 2, 4],
            paginas: [2],
            actividad: [],
            papelera: [2],
            avatares: [2]
        },
        moderador: {
            publicidad: [],
            noticias: [2],
            categorias: [2],
            correos: [],
            suscripciones: [2, 4],
            usuarios: [],
            videos: [2],
            lectores: [2, 4],
            recomendados: [2],
            esperamos: [2],
            paginas: [],
            actividad: [],
            papelera: [],
            avatares: []
        }
    };

    window.checkModule = (slug, checked) => {
        document.querySelectorAll(`.mod-chk-${slug}`).forEach(input => {
            input.checked = checked;
        });
        updateRoleSelector();
    };

    window.bulkCheckAll = (checked) => {
        document.querySelectorAll('.form-switch-input').forEach(input => {
            input.checked = checked;
        });
        updateRoleSelector();
    };

    window.bulkCheckAction = (actionVal) => {
        document.querySelectorAll(`.form-switch-input[data-action="${actionVal}"]`).forEach(input => {
            input.checked = true;
        });
        updateRoleSelector();
    };

    const updateRoleSelector = () => {
        const roleSelector = document.getElementById('roleSelector');
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
            if (isMatch) {
                matchedRole = roleName;
                break;
            }
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

    const roleSelector = document.getElementById('roleSelector');
    roleSelector.addEventListener('change', () => {
        applyRole(roleSelector.value);
    });

    document.querySelectorAll('.form-switch-input').forEach(input => {
        input.addEventListener('change', () => {
            updateRoleSelector();
        });
    });

    // Validación usuario en tiempo real
    inputUsuario.addEventListener('keyup', () => {
        const usuario = inputUsuario.value.trim();

        if (usuario.length < 3) {
            estado.textContent = 'El usuario debe tener al menos 3 caracteres';
            estado.style.color = '#ffc107';
            usuarioValido = false;
            return;
        }

        fetch(`./../controllers/validar_usuario.php?usuario=${usuario}`)
            .then(res => res.json())
            .then(data => {
                if (data.existe) {
                    estado.textContent = '❌ Usuario no disponible';
                    estado.style.color = '#dc3545';
                    usuarioValido = false;
                } else {
                    estado.textContent = '✅ Usuario disponible';
                    estado.style.color = '#198754';
                    usuarioValido = true;
                }
            })
            .catch(() => {
                estado.textContent = 'Error al validar usuario';
                estado.style.color = '#dc3545';
                usuarioValido = false;
            });
    });

    // Validación final y envío.
    // El controlador responde JSON, así que el formulario se envía siempre por
    // fetch: un submit normal lo intercepta Turbo, que espera HTML o una
    // redirección, y descarta la respuesta sin avisar (el botón parecía muerto).
    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        let valido = true;

        // Contraseñas
        if (passInput.value !== confirmInput.value) {
            errorPassword.style.display = 'block';
            valido = false;
        } else {
            errorPassword.style.display = 'none';
        }

        // Usuario
        if (!usuarioValido) {
            showToast('El nombre de usuario no es válido o ya existe', 'error');
            valido = false;
        }

        if (!valido) return;

        const btn = form.querySelector('button[type="submit"]');
        btn.disabled = true;

        try {
            const res = await fetch(form.action, { method: 'POST', body: new FormData(form) });
            let data = null;
            try { data = await res.json(); } catch (_) {}

            if (!data) {
                showToast('El servidor respondió con un error (' + res.status + ')', 'error');
                btn.disabled = false;
                return;
            }
            if (data.success) {
                showToast(data.success, 'success');
                setTimeout(() => { window.location.href = 'usuarios.php'; }, 1200);
            } else {
                showToast(data.error || 'No se pudo crear el usuario', 'error');
                btn.disabled = false;
            }
        } catch (_) {
            showToast('Error de conexión al crear el usuario', 'error');
            btn.disabled = false;
        }
    });

});
</script>
<?php include("./../layout/footerAdmin.php"); ?>