<?php
include("./../layout/headerAdmin.php");
$ACL = $_SESSION['ACL']['usuarios']??[
    'crear' => 1,
    'leer' => 2,
    'editar' => 4,
    'eliminar' => 8,
];
if (!($ACL['leer'])) {
    header("Location: admin.php");
    exit();
}
include("./../data/conexion.php");
$id = (int)($_GET['id'] ?? 0);
$stmt = $con->prepare("SELECT * FROM usuarios WHERE id_u = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$usuario = $stmt->get_result()->fetch_assoc();
if (!$usuario) {
    header("Location: usuarios.php");
    exit();
}

// Mismos módulos y orden que crearu.php / editaru.php
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
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Datos del usuario <?= $usuario['nombre'] ?></h1>
    </div>
    <div class="card">
        <div class="card-header">
            <h5 class="card-title">Detalles del usuario</h5>
        </div>
        <div class="card-body">
            <style>
            .perm-chip {
                display: inline-flex;
                align-items: center;
                gap: 5px;
                padding: 4px 10px;
                border-radius: 999px;
                font-size: 0.8rem;
                font-weight: 500;
                border: 1px solid var(--border);
                background: var(--bg);
                color: var(--muted, #888);
            }
            .perm-chip.on {
                border-color: var(--accent);
                background: rgba(239, 51, 99, 0.08);
                color: var(--accent);
            }
            </style>
            <div class="form-group" style="margin-bottom: 24px;">
                <p class="card-text"><strong>ID:</strong> <?= htmlspecialchars($usuario['id_u']) ?></p>
                <p class="card-text"><strong>Nombre:</strong> <?= htmlspecialchars($usuario['nombre']) ?></p>
                <p class="card-text"><strong>Usuario:</strong> <?= htmlspecialchars($usuario['usuario']) ?></p>
                <p class="card-text"><strong>Email:</strong> <?= htmlspecialchars($usuario['correo']) ?></p>
            </div>

            <h5 style="font-weight:600; margin-bottom:15px;"><i class="bi bi-shield-lock-fill text-accent"></i> Permisos</h5>
            <div class="row-permisos" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px;">
                <?php foreach ($modulosConfig as $slug => $nombre):
                    $permActual = (int)($usuario['perm_' . $slug] ?? 0); ?>
                <div class="col-permiso" style="max-width: 100%;">
                    <div class="card" style="border: 1px solid var(--border); border-radius: 12px; background: var(--card-bg); overflow: hidden; height: 100%; display: flex; flex-direction: column;">
                        <div class="card-header d-flex justify-content-between align-items-center" style="padding: 12px 16px; border-bottom: 1px solid var(--border); background: rgba(0,0,0,0.02);">
                            <h6 class="mb-0" style="font-weight:600; font-size: 0.95rem; margin: 0;"><?= $nombre ?></h6>
                            <?php if ($permActual === 0): ?>
                                <span style="font-size:0.75rem; color:var(--muted, #888);">Sin acceso</span>
                            <?php elseif ($permActual === 15): ?>
                                <span style="font-size:0.75rem; color:var(--accent); font-weight:600;">Completo</span>
                            <?php endif; ?>
                        </div>
                        <div class="card-body" style="padding: 16px; display:flex; flex-wrap:wrap; gap:6px;">
                            <?php foreach ($acciones as $bit => $etiqueta): ?>
                                <span class="perm-chip <?= ($permActual & $bit) ? 'on' : '' ?>">
                                    <?= ($permActual & $bit) ? '&check;' : '&times;' ?> <?= $etiqueta ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="card-footer">
            <a href="./../views/usuarios.php" class="btn btn-secondary">Volver</a>
            <?php if ($ACL['editar']) : ?>
                <a href="./../views/editaru.php?id=<?= $usuario['id_u'] ?>" class="btn btn-secondary">Editar</a>
            <?php endif; ?>
            <?php if ($ACL['eliminar']) : ?>
                <button class="btn btn-delete-usuario" data-id="<?= $usuario['id_u'] ?>" data-nombre="<?= $usuario['nombre'] ?>" title="Eliminar Usuario">Eliminar</button>
            <?php endif; ?>
        </div>
    </div>
</div>
<!-- Modal eliminacion usuario -->
<div id="modalOverlayU" class="crop-modal" style="display: none;">
    <div class="crop-modal-content">
        <h3 id="modalTitleU">Confirmar eliminación</h3>
        <p>¿Estás seguro de que deseas eliminar este usuario? Esta acción no se puede deshacer.</p>
        <form id="modalFormU" action="./../controllers/eliminarusuario.php?id=<?= $usuario['id_u'] ?>" method="POST">
            <input type="hidden" name="id" id="modalIdU">
            <div class="crop-actions">
                <button type="button" class="btn btn-secondary btn-cancel">Cancelar</button>
                <button type="submit" class="btn btn-danger">Eliminar</button>
            </div>
        </form>
    </div>
</div>
<?php include("./../layout/footerAdmin.php"); ?>