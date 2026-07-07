<?php
include("./../layout/headerAdmin.php");
include("./../data/conexion.php");

if (empty($ACL['leer'])) {
    header("Location: admin.php");
    exit();
}

require_once(__DIR__ . "/helpers/moderacion.php");

// Configuración de paginación
$porPagina = 10;
$pagina = (int)($_GET['page'] ?? 1);
if ($pagina < 1) $pagina = 1;
$offset = ($pagina - 1) * $porPagina;

// Parámetros de ordenamiento y búsqueda
$q = trim($_GET['q'] ?? '');
$orden = $_GET['orden'] ?? 'fecha';
if (!in_array($orden, ['fecha', 'alfabetico'])) {
    $orden = 'fecha';
}

// 1. Condiciones de búsqueda
$whereClause = "";
$params = [];
$types = "";

if ($q !== '') {
    $whereClause = "WHERE l.nombre LIKE ? OR l.usuario LIKE ? OR l.correo LIKE ?";
    $like = "%$q%";
    $params = [$like, $like, $like];
    $types = "sss";
}

// 2. Obtener el total de lectores (para paginador)
$countSql = "SELECT COUNT(*) as total FROM lectores l $whereClause";
$stmtCount = $con->prepare($countSql);
if ($q !== '') {
    $stmtCount->bind_param($types, ...$params);
}
$stmtCount->execute();
$totalLectores = $stmtCount->get_result()->fetch_assoc()['total'];
$totalPaginas = ceil($totalLectores / $porPagina);
if ($totalPaginas < 1) $totalPaginas = 1;
if ($pagina > $totalPaginas) {
    $pagina = $totalPaginas;
    $offset = ($pagina - 1) * $porPagina;
}

// 3. Obtener lectores ordenados y paginados
$orderBy = "ORDER BY l.creado DESC";
if ($orden === 'alfabetico') {
    $orderBy = "ORDER BY l.nombre ASC";
}

$sql = "
    SELECT l.*, a.imagen AS avatar_img
    FROM lectores l
    LEFT JOIN avatares_perfil a ON a.id_avatar = l.avatar_id
    $whereClause
    $orderBy
    LIMIT ? OFFSET ?
";

$stmt = $con->prepare($sql);
if ($q !== '') {
    $bindParams = array_merge($params, [$porPagina, $offset]);
    $bindTypes = $types . "ii";
    $stmt->bind_param($bindTypes, ...$bindParams);
} else {
    $stmt->bind_param("ii", $porPagina, $offset);
}
$stmt->execute();
$lectores = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$esSuper = esSuperAdminActual();
?>

<div class="container-fluid">
    <!-- Encabezado y Buscador -->
    <div class="d-flex justify-content-between align-items-center mb-4" style="flex-wrap: wrap; gap: 12px;">
        <h1 style="margin:0;">Administración de lectores</h1>
        <form method="GET" class="admin-search-form" style="display:flex; align-items:center; gap:8px;">
            <input type="hidden" name="orden" value="<?= htmlspecialchars($orden) ?>">
            <i class="bi bi-search admin-search-icon"></i>
            <input type="search" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Buscar por nombre, usuario o email..." class="admin-search-input">
            <?php if ($q): ?>
                <a href="./lectores.php?orden=<?= htmlspecialchars($orden) ?>" class="admin-search-clear">&times;</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Barra de Herramientas / Ordenamiento -->
    <div class="contenidos-toolbar" style="margin-bottom: 20px;">
        <div class="dropdown" style="position: relative; display: inline-block;">
            <button type="button" class="btn btn-secondary dropdown-toggle" id="dropdownOrderBtn" style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 16px; border-radius: 8px; cursor: pointer; border: 1px solid var(--border); background: var(--card-bg); color: var(--text); font-weight: 500;">
                <i class="bi bi-sort-down"></i> Ordenar por: <strong style="color: var(--accent); margin-left: 4px;"><?= $orden === 'fecha' ? 'Fecha de Registro' : 'Alfabético' ?></strong>
            </button>
            <div id="dropdownOrderMenu" style="display: none; position: absolute; top: 100%; left: 0; z-index: 1000; background: var(--card-bg); border: 1px solid var(--border); border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); min-width: 200px; margin-top: 5px; padding: 5px 0;">
                <a href="./lectores.php?orden=fecha<?= $q ? '&q='.urlencode($q) : '' ?>" class="dropdown-item" style="display: flex; align-items: center; gap: 8px; padding: 10px 16px; color: var(--text); text-decoration: none; font-size: 0.9rem; transition: background 0.2s;">
                    <i class="bi bi-calendar3"></i> Fecha de Registro
                </a>
                <a href="./lectores.php?orden=alfabetico<?= $q ? '&q='.urlencode($q) : '' ?>" class="dropdown-item" style="display: flex; align-items: center; gap: 8px; padding: 10px 16px; color: var(--text); text-decoration: none; font-size: 0.9rem; transition: background 0.2s;">
                    <i class="bi bi-sort-alpha-down"></i> Alfabético
                </a>
            </div>
        </div>
    </div>
    
    <style>
    .dropdown-item:hover {
        background-color: var(--accent) !important;
        color: #fff !important;
    }
    </style>

    <!-- Tabla de Contenido -->
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="contenidos-table">
                    <thead>
                        <tr>
                            <th>Avatar</th>
                            <th>Usuario</th>
                            <th>Nombre</th>
                            <th>Email</th>
                            <th>Estado</th>
                            <?php if ($ACL['editar']): ?>
                                <th>Acciones</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($lectores)): ?>
                            <tr>
                                <td colspan="6" style="text-align:center; padding:30px; color:var(--muted);">
                                    No se encontraron lectores.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($lectores as $l):
                                $bpal = explode(' ', $l['nombre']);
                                $bini = strtoupper(substr($bpal[0], 0, 1) . (isset($bpal[1]) ? substr($bpal[1], 0, 1) : ''));
                                $banned = estaBaneado($l);
                                $banText = textoBaneo($l);
                            ?>
                            <tr>
                                <!-- Avatar -->
                                <td>
                                    <?php if (!empty($l['avatar_img'])): ?>
                                        <img src="<?= imageUrl($l['avatar_img']) ?>" alt="Avatar"
                                             style="width:40px; height:40px; border-radius:50%; object-fit:cover; display:block;">
                                    <?php else: ?>
                                        <div style="width:40px; height:40px; border-radius:50%; background:var(--accent); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:bold;">
                                            <?= htmlspecialchars($bini) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <!-- Usuario -->
                                <td>
                                    <span class="estado-badge estado-programado" style="background:rgba(99,102,241,0.1); color:#6366f1;">
                                        @<?= htmlspecialchars($l['usuario']) ?>
                                    </span>
                                </td>
                                <!-- Nombre -->
                                <td>
                                    <strong class="table-title"><?= htmlspecialchars($l['nombre']) ?></strong>
                                </td>
                                <!-- Email -->
                                <td>
                                    <span style="font-size:0.9rem; color:var(--text);"><?= htmlspecialchars($l['correo']) ?></span>
                                </td>
                                <!-- Estado -->
                                <td>
                                    <?php if ($banned): ?>
                                        <span class="estado-badge estado-bloqueado" style="background:rgba(239,67,67,0.1); color:#ef4343; cursor:pointer;" title="<?= htmlspecialchars($banText) ?>">
                                            <i class="bi bi-slash-circle"></i> Suspendido
                                        </span>
                                    <?php else: ?>
                                        <span class="estado-badge estado-activo" style="background:rgba(16,185,129,0.1); color:#10b981;">
                                            <i class="bi bi-person-check"></i> Activo
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <!-- Acciones -->
                                <?php if ($ACL['editar']): ?>
                                    <td>
                                        <div class="noticias-actions" style="border-top:none; padding:0; justify-content:flex-start; gap:8px;">
                                            <!-- Promover -->
                                            <button type="button" class="btn btn-view btn-promover"
                                                    data-id="<?= $l['id'] ?>"
                                                    data-nombre="<?= htmlspecialchars($l['nombre'], ENT_QUOTES) ?>"
                                                    title="Promover a Administrador">
                                                <i class="bi bi-shield-plus"></i> Promover
                                            </button>

                                            <!-- Banear / Desbanear -->
                                            <?php if ($esSuper): ?>
                                                <?php if ($banned): ?>
                                                    <button type="button" class="btn btn-edit btn-unban"
                                                            data-id="<?= $l['id'] ?>"
                                                            title="Quitar Suspensión">
                                                        <i class="bi bi-check-circle"></i> Desbanear
                                                    </button>
                                                <?php else: ?>
                                                    <button type="button" class="btn btn-delete btn-ban"
                                                            data-id="<?= $l['id'] ?>"
                                                            data-nombre="<?= htmlspecialchars($l['nombre'], ENT_QUOTES) ?>"
                                                            title="Suspender Lector">
                                                        <i class="bi bi-slash-circle"></i> Banear
                                                    </button>
                                                <?php endif; ?>
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

    <!-- Paginación -->
    <?php if ($totalPaginas > 1):
        $baseUrl = "./lectores.php?orden=" . urlencode($orden) . ($q ? "&q=" . urlencode($q) : "") . "&";
    ?>
        <div class="pagination-wrapper">
            <ul class="pagination">
                <?php if ($pagina > 1): ?>
                    <li>
                        <a href="<?= $baseUrl ?>page=<?= $pagina - 1 ?>">« Anterior</a>
                    </li>
                <?php endif; ?>
                <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                    <li class="<?= $i == $pagina ? 'active' : '' ?>">
                        <a href="<?= $baseUrl ?>page=<?= $i ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
                <?php if ($pagina < $totalPaginas): ?>
                    <li>
                        <a href="<?= $baseUrl ?>page=<?= $pagina + 1 ?>">Siguiente »</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    <?php endif; ?>
</div>

<!-- Modal: Suspender Lector -->
<div id="modalBan" class="crop-modal" style="display:none;">
    <div class="crop-modal-content">
        <h3><i class="bi bi-slash-circle" style="color:#ef3333;"></i> Suspender a <span id="banNombre"></span></h3>
        <p style="color:var(--muted); font-size:0.9rem; margin:5px 0 15px;">
            El usuario no podrá comentar, dar me gusta ni reportar mientras dure la suspensión.
        </p>
        <div style="margin-bottom:12px;">
            <label for="banDuracion" style="display:block; font-size:0.85rem; font-weight:600; margin-bottom:6px; color:var(--text);">Duración</label>
            <select id="banDuracion" style="width:100%; padding:8px; border:1px solid var(--border); border-radius:8px; font-size:0.85rem; background:var(--card-bg); color:var(--text);">
                <?php foreach(duracionesBaneo() as $key => $d): ?>
                    <option value="<?= $key ?>"><?= htmlspecialchars($d['label']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div style="margin-bottom:16px;">
            <label for="banMotivo" style="display:block; font-size:0.85rem; font-weight:600; margin-bottom:6px; color:var(--text);">Motivo</label>
            <input type="text" id="banMotivo" maxlength="255" placeholder="Motivo de la suspensión (opcional)"
                   style="width:100%; padding:8px; border:1px solid var(--border); border-radius:8px; font-size:0.85rem; background:var(--card-bg); color:var(--text);">
        </div>
        <div class="crop-actions" style="display:flex; justify-content:flex-end; gap:8px;">
            <button type="button" id="btnCancelBan" class="btn btn-secondary">Cancelar</button>
            <button type="button" id="btnConfirmBan" class="btn btn-accent"><i class="bi bi-slash-circle"></i> Suspender</button>
        </div>
    </div>
</div>

<!-- Modal: Promover a Administrador -->
<div id="modalPromover" class="crop-modal" style="display: none;">
    <div class="crop-modal-content">
        <h3><i class="bi bi-shield-plus" style="color:var(--accent);"></i> Promover a Administrador</h3>
        <p style="color:var(--muted); font-size:0.9rem; margin:10px 0 20px; line-height:1.5;">
            ¿Estás seguro de que deseas promover al lector <strong id="promoverNombre"></strong> a administrador?
            <br><br>
            <strong>Esta acción:</strong>
            <br>
            • Creará un usuario administrador con las mismas credenciales (usuario, correo, contraseña).
            <br>
            • Conservará sus comentarios asociándolos a su nuevo perfil administrativo.
            <br>
            • Eliminará el registro del catálogo público de lectores.
        </p>
        <div class="crop-actions" style="display:flex; justify-content:flex-end; gap:8px;">
            <button type="button" id="btnCancelPromover" class="btn btn-secondary">Cancelar</button>
            <button type="button" id="btnConfirmPromover" class="btn btn-accent"><i class="bi bi-shield-plus"></i> Confirmar Promoción</button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // ── DROPDOWN: ORDENAR POR ──
    const orderBtn = document.getElementById('dropdownOrderBtn');
    const orderMenu = document.getElementById('dropdownOrderMenu');
    if (orderBtn && orderMenu) {
        orderBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            const show = orderMenu.style.display === 'block';
            orderMenu.style.display = show ? 'none' : 'block';
        });
        document.addEventListener('click', () => {
            orderMenu.style.display = 'none';
        });
    }

    // ── MODAL: SUSPENDER ──
    const modalBan = document.getElementById('modalBan');
    const banNombre = document.getElementById('banNombre');
    const banDuracion = document.getElementById('banDuracion');
    const banMotivo = document.getElementById('banMotivo');
    const btnConfirmBan = document.getElementById('btnConfirmBan');
    const btnCancelBan = document.getElementById('btnCancelBan');
    let banLectorId = null;

    // Abrir Modal Ban
    document.querySelectorAll('.btn-ban').forEach(btn => {
        btn.addEventListener('click', () => {
            banLectorId = btn.dataset.id;
            banNombre.textContent = btn.dataset.nombre;
            banDuracion.selectedIndex = 0;
            banMotivo.value = '';
            modalBan.style.display = 'flex';
        });
    });

    // Cerrar Modal Ban
    const cerrarModalBan = () => {
        modalBan.style.display = 'none';
        banLectorId = null;
    };
    btnCancelBan.addEventListener('click', cerrarModalBan);
    modalBan.addEventListener('click', e => { if (e.target === modalBan) cerrarModalBan(); });

    // Confirmar Ban
    btnConfirmBan.addEventListener('click', async () => {
        if (!banLectorId) return;
        btnConfirmBan.disabled = true;
        try {
            const res = await fetch('./../controllers/moderar_usuario.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=ban&tipo=lector&user_id=${banLectorId}&duracion=${encodeURIComponent(banDuracion.value)}&motivo=${encodeURIComponent(banMotivo.value)}`
            });
            const data = await res.json();
            if (data.ok) {
                alert(data.msg || 'Lector suspendido correctamente.');
                location.reload();
            } else {
                alert(data.msg || 'No se pudo suspender al lector.');
            }
        } catch (err) {
            alert('Error de red.');
        } finally {
            btnConfirmBan.disabled = false;
        }
    });

    // ── QUITAR SUSPENSIÓN (DESBANEAR) ──
    document.querySelectorAll('.btn-unban').forEach(btn => {
        btn.addEventListener('click', async () => {
            if (!confirm('¿Estás seguro de que deseas quitar la suspensión a este lector?')) return;
            btn.disabled = true;
            try {
                const res = await fetch('./../controllers/moderar_usuario.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=unban&tipo=lector&user_id=${btn.dataset.id}`
                });
                const data = await res.json();
                if (data.ok) {
                    alert(data.msg || 'Suspensión retirada correctamente.');
                    location.reload();
                } else {
                    alert(data.msg || 'No se pudo quitar la suspensión.');
                }
            } catch (err) {
                alert('Error de red.');
            } finally {
                btn.disabled = false;
            }
        });
    });

    // ── MODAL: PROMOVER A ADMIN ──
    const modalPromover = document.getElementById('modalPromover');
    const promoverNombre = document.getElementById('promoverNombre');
    const btnConfirmPromover = document.getElementById('btnConfirmPromover');
    const btnCancelPromover = document.getElementById('btnCancelPromover');
    let promoverLectorId = null;

    // Abrir Modal Promover
    document.querySelectorAll('.btn-promover').forEach(btn => {
        btn.addEventListener('click', () => {
            promoverLectorId = btn.dataset.id;
            promoverNombre.textContent = btn.dataset.nombre;
            modalPromover.style.display = 'flex';
        });
    });

    // Cerrar Modal Promover
    const cerrarModalPromover = () => {
        modalPromover.style.display = 'none';
        promoverLectorId = null;
    };
    btnCancelPromover.addEventListener('click', cerrarModalPromover);
    modalPromover.addEventListener('click', e => { if (e.target === modalPromover) cerrarModalPromover(); });

    // Confirmar Promoción
    btnConfirmPromover.addEventListener('click', async () => {
        if (!promoverLectorId) return;
        btnConfirmPromover.disabled = true;
        try {
            const res = await fetch('./../controllers/promover_lector.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `id=${promoverLectorId}`
            });
            const data = await res.json();
            if (data.success) {
                alert(data.success);
                location.reload();
            } else {
                alert(data.error || 'No se pudo promover al lector.');
            }
        } catch (err) {
            alert('Error de red.');
        } finally {
            btnConfirmPromover.disabled = false;
        }
    });
});
</script>

<?php include("./../layout/footerAdmin.php"); ?>
