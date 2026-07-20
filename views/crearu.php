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

<style>
/* ── ESTILOS MATRIZ DE PERMISOS Y FORMULARIO MODERNO ── */
.user-form-card {
    border: 1px solid var(--border);
    border-radius: 14px;
    background: var(--card-bg);
    padding: 24px;
    margin-bottom: 24px;
    box-shadow: 0 4px 18px rgba(0, 0, 0, 0.03);
}

.user-form-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
}

@media (max-width: 768px) {
    .user-form-grid {
        grid-template-columns: 1fr;
    }
}

.user-field-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.user-field-group.full-width {
    grid-column: 1 / -1;
}

.user-field-group label {
    font-size: 0.88rem;
    font-weight: 700;
    color: var(--text);
    margin: 0;
}

.user-field-group span.hint {
    font-size: 0.78rem;
    color: var(--muted);
    margin-bottom: 2px;
}

.user-field-input {
    width: 100%;
    padding: 10px 14px;
    border-radius: 9px;
    border: 1px solid var(--border);
    background: var(--bg);
    color: var(--text);
    font-size: 0.92rem;
    transition: all 0.2s ease;
}

.user-field-input:focus {
    outline: none;
    border-color: var(--accent);
    box-shadow: 0 0 0 3px rgba(239, 51, 99, 0.12);
}

/* ── BOTONES DE ACCIÓN Y PILS ── */
.perm-action-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 7px 14px;
    font-size: 0.83rem;
    font-weight: 600;
    border-radius: 999px;
    border: 1px solid var(--border);
    background: var(--bg);
    color: var(--text);
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    line-height: 1;
}
.perm-action-btn:hover {
    border-color: var(--accent);
    color: var(--accent);
    background: rgba(239, 51, 99, 0.06);
    transform: translateY(-1px);
}
.perm-action-btn.btn-all {
    background: rgba(239, 51, 99, 0.1);
    color: var(--accent);
    border-color: rgba(239, 51, 99, 0.25);
}
.perm-action-btn.btn-all:hover {
    background: var(--accent);
    color: #ffffff;
    border-color: var(--accent);
    box-shadow: 0 4px 12px rgba(239, 51, 99, 0.25);
}
.perm-action-btn.btn-clear {
    background: rgba(108, 117, 125, 0.08);
    color: var(--muted);
    border-color: var(--border);
}
.perm-action-btn.btn-clear:hover {
    background: #6c757d;
    color: #ffffff;
    border-color: #6c757d;
}
.perm-submit-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 24px;
    font-size: 0.9rem;
    font-weight: 700;
    border-radius: 999px;
    background: linear-gradient(135deg, #EF3363 0%, #ff527b 100%);
    color: #ffffff;
    border: none;
    cursor: pointer;
    box-shadow: 0 4px 14px rgba(239, 51, 99, 0.35);
    transition: all 0.2s ease;
}
.perm-submit-btn:hover {
    transform: translateY(-2px) scale(1.02);
    box-shadow: 0 6px 20px rgba(239, 51, 99, 0.45);
}

/* ── TABLA MATRIZ DE PERMISOS ── */
.perm-matrix-card {
    border: 1px solid var(--border);
    border-radius: 14px;
    background: var(--card-bg);
    overflow: hidden;
    margin-bottom: 24px;
    box-shadow: 0 4px 18px rgba(0,0,0,0.03);
}

.perm-matrix-table {
    width: 100%;
    border-collapse: collapse;
}

.perm-matrix-table th {
    background: rgba(0, 0, 0, 0.02);
    padding: 14px 16px;
    font-size: 0.82rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: var(--muted);
    border-bottom: 1px solid var(--border);
}

.perm-matrix-table td {
    padding: 12px 16px;
    border-bottom: 1px solid var(--border);
    vertical-align: middle;
}

.perm-matrix-table tr:last-child td {
    border-bottom: none;
}

.perm-matrix-table tr:hover td {
    background: rgba(239, 51, 99, 0.02);
}

.col-header-toggle {
    cursor: pointer;
    user-select: none;
    transition: color 0.15s;
}
.col-header-toggle:hover {
    color: var(--accent) !important;
}

.mod-icon-box {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 15px;
    flex-shrink: 0;
}

/* Switches para Matriz */
.matrix-switch {
    position: relative;
    display: inline-block;
    width: 40px;
    height: 22px;
    margin: 0;
}

.matrix-switch input.form-switch-input {
    opacity: 0;
    width: 0;
    height: 0;
}

.matrix-switch-slider {
    position: absolute;
    cursor: pointer;
    top: 0; left: 0; right: 0; bottom: 0;
    background-color: rgba(120, 120, 128, 0.2);
    transition: .25s;
    border-radius: 22px;
}

.matrix-switch-slider:before {
    position: absolute;
    content: "";
    height: 16px;
    width: 16px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    transition: .25s;
    border-radius: 50%;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.matrix-switch input:checked + .matrix-switch-slider {
    background-color: #10b981;
}

.matrix-switch input:checked + .matrix-switch-slider:before {
    transform: translateX(18px);
}

.btn-check-icon {
    background: transparent;
    border: 1px solid var(--border);
    color: var(--muted);
    font-size: 13px;
    cursor: pointer;
    padding: 3px 8px;
    border-radius: 6px;
    transition: all 0.15s;
}

.btn-check-icon:hover {
    color: #10b981;
    border-color: #10b981;
    background: rgba(16, 185, 129, 0.08);
}

.btn-check-icon.btn-uncheck:hover {
    color: #ef4444;
    border-color: #ef4444;
    background: rgba(239, 68, 68, 0.08);
}
</style>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4" style="flex-wrap: wrap; gap: 12px;">
        <div>
            <h1 style="margin:0; font-weight:700;">Crear Nuevo Usuario</h1>
            <p class="text-muted mb-0" style="font-size:0.88rem;">Agrega un administrador o editor y configura sus accesos.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="usuarios.php" class="btn btn-secondary" style="border-radius:999px; padding:8px 18px; font-weight:600;"><i class="bi bi-arrow-left"></i> Volver</a>
            <?php if($ACL['crear']): ?>
                <button type="submit" form="formUsuario" class="perm-submit-btn">
                    <i class="bi bi-person-plus-fill"></i> Crear Usuario
                </button>
            <?php endif; ?>
        </div>
    </div>

    <form id="formUsuario" action="./../controllers/altausuarios.php" method="POST">
        
        <!-- DATOS DEL USUARIO -->
        <div class="user-form-card">
            <h5 style="margin-top:0; margin-bottom:18px; font-weight:700; display:flex; align-items:center; gap:8px;">
                <i class="bi bi-person-badge-fill text-accent"></i> Datos del Usuario
            </h5>
            
            <div class="user-form-grid">
                <div class="user-field-group">
                    <label for="nombre">Nombre Completo</label>
                    <span class="hint">Ej. Sam Okonma</span>
                    <input type="text" id="nombre" name="nombre" class="user-field-input" placeholder="Nombre completo..." required>
                </div>
                
                <div class="user-field-group">
                    <label for="usuario">Nombre de Usuario</label>
                    <span class="hint">Identificador para iniciar sesión</span>
                    <input type="text" id="usuario" name="usuario" class="user-field-input" placeholder="root_sam" required>
                    <small id="usuarioEstado" style="font-weight:600; font-size:0.8rem; margin-top:2px;"></small>
                </div>

                <div class="user-field-group full-width">
                    <label for="email">Correo Electrónico</label>
                    <span class="hint">Dirección de correo oficial</span>
                    <input type="email" id="email" name="email" class="user-field-input" placeholder="correo@ejemplo.com" required>
                </div>

                <div class="user-field-group">
                    <label for="password">Contraseña</label>
                    <span class="hint">Asigna una contraseña segura</span>
                    <input type="password" id="password" name="password" class="user-field-input" placeholder="••••••••" required>
                </div>

                <div class="user-field-group">
                    <label for="confirm_password">Confirmar Contraseña</label>
                    <span class="hint">Repite la contraseña</span>
                    <input type="password" id="confirm_password" name="confirm_password" class="user-field-input" placeholder="••••••••" required>
                    <small id="errorPassword" style="color:#dc3545; font-weight:600; font-size:0.8rem; display:none; margin-top:2px;">
                        Las contraseñas no coinciden
                    </small>
                </div>
            </div>
        </div>

        <!-- ROLES Y MATRIZ DE PERMISOS -->
        <div class="perm-matrix-card">
            <div class="d-flex justify-content-between align-items-center" style="padding: 16px 20px; border-bottom: 1px solid var(--border); background: rgba(0,0,0,0.02); flex-wrap:wrap; gap:12px;">
                <div>
                    <h5 class="mb-0" style="font-weight: 700; font-size: 1.05rem; display: flex; align-items: center; gap: 8px;">
                        <i class="bi bi-shield-check text-accent"></i> Asignación de Rol y Permisos
                    </h5>
                    <p class="text-muted mb-0" style="font-size: 0.8rem; margin-top: 2px;">Selecciona una plantilla de rol o marca los accesos individualmente.</p>
                </div>

                <div style="display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
                    <select id="roleSelector" class="form-control" style="max-width: 260px; padding: 8px 14px; border-radius: 999px; border: 1px solid var(--border); background: var(--bg); color: var(--text); font-size:0.85rem; font-weight:600;">
                        <option value="custom" selected>Personalizado (Manual)</option>
                        <option value="superadmin">Superadministrador (Total)</option>
                        <option value="admin">Administrador General</option>
                        <option value="editor">Editor de Contenidos</option>
                        <option value="moderador">Moderador de Comunidad</option>
                    </select>

                    <div style="display:flex; gap:6px; align-items:center; flex-wrap: wrap;">
                        <button type="button" class="perm-action-btn btn-all" onclick="bulkCheckAll(true)"><i class="bi bi-check-all" style="font-size:1.15em;"></i> Marcar todo</button>
                        <button type="button" class="perm-action-btn btn-clear" onclick="bulkCheckAll(false)"><i class="bi bi-x-circle"></i> Desmarcar todo</button>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="perm-matrix-table">
                    <thead>
                        <tr>
                            <th style="padding-left: 20px;">Módulo / Sección</th>
                            <th class="text-center col-header-toggle" onclick="toggleColumnAction(2)" title="Clic para alternar todos los 'Ver'">
                                <span class="d-inline-flex align-items-center gap-1" style="color:#0284c7;"><i class="bi bi-eye-fill"></i> Ver</span>
                            </th>
                            <th class="text-center col-header-toggle" onclick="toggleColumnAction(1)" title="Clic para alternar todos los 'Crear'">
                                <span class="d-inline-flex align-items-center gap-1" style="color:#16a34a;"><i class="bi bi-plus-circle-fill"></i> Crear</span>
                            </th>
                            <th class="text-center col-header-toggle" onclick="toggleColumnAction(4)" title="Clic para alternar todos los 'Editar'">
                                <span class="d-inline-flex align-items-center gap-1" style="color:#d97706;"><i class="bi bi-pencil-fill"></i> Editar</span>
                            </th>
                            <th class="text-center col-header-toggle" onclick="toggleColumnAction(8)" title="Clic para alternar todos los 'Eliminar'">
                                <span class="d-inline-flex align-items-center gap-1" style="color:#dc2626;"><i class="bi bi-trash-fill"></i> Eliminar</span>
                            </th>
                            <th class="text-center" style="width: 100px; padding-right: 20px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $modulosIconos = [
                            'publicidad' => ['nombre' => 'Publicidad', 'icon' => 'bi-megaphone-fill', 'color' => '#8b5cf6'],
                            'noticias' => ['nombre' => 'Noticias', 'icon' => 'bi-newspaper', 'color' => '#ec4899'],
                            'categorias' => ['nombre' => 'Categorías', 'icon' => 'bi-tags-fill', 'color' => '#3b82f6'],
                            'correos' => ['nombre' => 'Correos Publicitarios', 'icon' => 'bi-envelope-paper-fill', 'color' => '#f59e0b'],
                            'suscripciones' => ['nombre' => 'Suscripciones', 'icon' => 'bi-people-fill', 'color' => '#10b981'],
                            'usuarios' => ['nombre' => 'Administradores / Editores', 'icon' => 'bi-person-gear', 'color' => '#ef3363'],
                            'videos' => ['nombre' => 'Videos', 'icon' => 'bi-play-btn-fill', 'color' => '#dc2626'],
                            'lectores' => ['nombre' => 'Lectores / Comunidad', 'icon' => 'bi-person-badge-fill', 'color' => '#06b6d4'],
                            'recomendados' => ['nombre' => 'Recomendados', 'icon' => 'bi-star-fill', 'color' => '#f59e0b'],
                            'esperamos' => ['nombre' => 'Próximos Estrenos', 'icon' => 'bi-hourglass-split', 'color' => '#6366f1'],
                            'paginas' => ['nombre' => 'Páginas y Logos', 'icon' => 'bi-file-earmark-code-fill', 'color' => '#64748b'],
                            'actividad' => ['nombre' => 'Bitácora de Actividades', 'icon' => 'bi-journal-text', 'color' => '#059669'],
                            'papelera' => ['nombre' => 'Papelera de Noticias', 'icon' => 'bi-trash-fill', 'color' => '#dc2626'],
                            'avatares' => ['nombre' => 'Fotos de Perfil / Avatares', 'icon' => 'bi-person-circle', 'color' => '#8b5cf6']
                        ];
                        $acciones = [2 => 'Ver', 1 => 'Crear', 4 => 'Editar', 8 => 'Eliminar'];

                        foreach ($modulosIconos as $slug => $mInfo):
                        ?>
                        <tr>
                            <td style="padding-left: 20px;">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="mod-icon-box" style="background: <?= $mInfo['color'] ?>15; color: <?= $mInfo['color'] ?>;">
                                        <i class="bi <?= $mInfo['icon'] ?>"></i>
                                    </div>
                                    <span class="fw-semibold text-color" style="font-size: 0.9rem;"><?= $mInfo['nombre'] ?></span>
                                </div>
                            </td>
                            <?php foreach ($acciones as $bit => $etiqueta): ?>
                            <td class="text-center">
                                <label class="matrix-switch">
                                    <input type="checkbox" name="<?= $slug ?>[]" value="<?= $bit ?>" class="form-switch-input mod-chk-<?= $slug ?>" data-action="<?= $bit ?>">
                                    <span class="matrix-switch-slider"></span>
                                </label>
                            </td>
                            <?php endforeach; ?>
                            <td class="text-center" style="padding-right: 20px;">
                                <div class="d-inline-flex gap-1">
                                    <button type="button" class="btn-check-icon" title="Marcar todo en <?= $mInfo['nombre'] ?>" onclick="checkModule('<?= $slug ?>', true)"><i class="bi bi-check-lg"></i></button>
                                    <button type="button" class="btn-check-icon btn-uncheck" title="Desmarcar todo en <?= $mInfo['nombre'] ?>" onclick="checkModule('<?= $slug ?>', false)"><i class="bi bi-x-lg"></i></button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2 mb-5">
            <a href="usuarios.php" class="btn btn-secondary" style="border-radius:999px; padding:10px 22px; font-weight:600;">Cancelar</a>
            <?php if($ACL['crear']): ?>
                <button type="submit" class="perm-submit-btn">
                    <i class="bi bi-person-plus-fill"></i> Crear Usuario
                </button>
            <?php endif; ?>
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

    // Helper global para alternar columnas completas en la matriz
    window.toggleColumnAction = function(actionBit) {
        const inputs = document.querySelectorAll(`.form-switch-input[data-action="${actionBit}"]`);
        const allChecked = Array.from(inputs).every(i => i.checked);
        inputs.forEach(i => i.checked = !allChecked);
    };

    window.bulkCheckAll = function(checked) {
        document.querySelectorAll('.form-switch-input').forEach(chk => chk.checked = checked);
    };

    window.bulkCheckAction = function(actionBit) {
        document.querySelectorAll(`.form-switch-input[data-action="${actionBit}"]`).forEach(chk => chk.checked = true);
    };

    window.checkModule = function(slug, checked) {
        document.querySelectorAll(`.mod-chk-${slug}`).forEach(chk => chk.checked = checked);
    };

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
            suscripciones: [2],
            usuarios: [],
            videos: [2],
            lectores: [1, 2, 4, 8],
            recomendados: [2],
            esperamos: [2],
            paginas: [],
            actividad: [2],
            papelera: [],
            avatares: [1, 2, 4, 8]
        }
    };

    const roleSelector = document.getElementById('roleSelector');
    roleSelector.addEventListener('change', (e) => {
        const selectedRole = e.target.value;
        if (selectedRole === 'custom') return;

        bulkCheckAll(false);
        const rolePerms = roles[selectedRole];
        if (rolePerms) {
            Object.keys(rolePerms).forEach(modulo => {
                const acciones = rolePerms[modulo];
                acciones.forEach(accion => {
                    const chk = document.querySelector(`.mod-chk-${modulo}[data-action="${accion}"]`);
                    if (chk) chk.checked = true;
                });
            });
        }
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

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        let valido = true;

        if (passInput.value !== confirmInput.value) {
            errorPassword.style.display = 'block';
            valido = false;
        } else {
            errorPassword.style.display = 'none';
        }

        if (!usuarioValido) {
            showToast('El nombre de usuario no es válido o ya existe', 'error');
            valido = false;
        }

        if (!valido) return;

        const btns = form.querySelectorAll('button[type="submit"]');
        btns.forEach(b => b.disabled = true);

        try {
            const res = await fetch(form.action, { method: 'POST', body: new FormData(form) });
            let data = null;
            try { data = await res.json(); } catch (_) {}

            if (!data) {
                showToast('El servidor respondió con un error (' + res.status + ')', 'error');
                btns.forEach(b => b.disabled = false);
                return;
            }
            if (data.success) {
                showToast(data.success, 'success');
                setTimeout(() => { window.location.href = 'usuarios.php'; }, 1200);
            } else {
                showToast(data.error || 'No se pudo crear el usuario', 'error');
                btns.forEach(b => b.disabled = false);
            }
        } catch (_) {
            showToast('Error de conexión al crear el usuario', 'error');
            btns.forEach(b => b.disabled = false);
        }
    });

});
</script>
<?php include("./../layout/footerAdmin.php"); ?>