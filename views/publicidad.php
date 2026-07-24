<?php
    include("./../layout/headerAdmin.php");
    include("./../data/conexion.php");
    $ACL = $_SESSION['ACL']['publicidad'] ?? [
        "crear" => false,
        "leer" => false,
        "editar" => false,
        "eliminar" => false
    ];
    if(!$ACL['leer']) {
        header("Location: admin.php");
        exit();
    }
?>
<?php
    $q = trim($_GET['q'] ?? '');
    if($q !== ''){
        $like = "%$q%";
        $sql = "SELECT * FROM publicidad WHERE titulo LIKE ? ORDER BY fecha_inicio DESC";
        $result = $con->prepare($sql);
        $result->bind_param("s", $like);
    } else {
        $sql = "SELECT * FROM publicidad ORDER BY fecha_inicio DESC";
        $result = $con->prepare($sql);
    }
    $result->execute();
    $res = $result->get_result();
    $publicidades = $res->fetch_all(MYSQLI_ASSOC);

    $hoy = date('Y-m-d');

    function estadoPublicidad($pub, $hoy) {
        if (isset($pub['activo']) && intval($pub['activo']) === 0) return 'inactiva';
        $ini = !empty($pub['fecha_inicio']) ? date('Y-m-d', strtotime($pub['fecha_inicio'])) : null;
        $fin = !empty($pub['fecha_fin'])    ? date('Y-m-d', strtotime($pub['fecha_fin']))    : null;
        if ($ini !== null && $ini > $hoy) return 'programada';
        if ($fin !== null && $fin >= $hoy) return 'activa';
        return 'vencida';
    }

    $totalPublicidad = count($publicidades);
    $activas     = count(array_filter($publicidades, fn($p) => estadoPublicidad($p, $hoy) === 'activa'));
    $programadas = count(array_filter($publicidades, fn($p) => estadoPublicidad($p, $hoy) === 'programada'));
    $inactivas   = count(array_filter($publicidades, fn($p) => estadoPublicidad($p, $hoy) === 'inactiva'));
    $vencidas    = $totalPublicidad - $activas - $programadas - $inactivas;
?>
<div class="container-fluid px-3 py-2">

    <!-- ── ENCABEZADO Y BÚSQUEDA ───────────────────────────────── -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <h1 style="font-weight:900; font-size:1.8rem; margin:0; color:var(--text); letter-spacing:-0.02em;">
                    Gestión de Publicidad
                </h1>
                <span class="badge" style="background:rgba(239,51,99,0.12); color:var(--accent); border:1px solid rgba(239,51,99,0.25); border-radius:20px; padding:4px 10px; font-weight:800; font-size:0.72rem;">
                    <?= $totalPublicidad ?> Campañas
                </span>
            </div>
            <p class="text-muted m-0" style="font-size:0.88rem;">Administra los banners publicitarios, fechas de vigencia y métricas de impresiones/clics.</p>
        </div>

        <form method="GET" class="d-flex align-items-center gap-2 m-0">
            <div style="position:relative; width:280px;">
                <i class="bi bi-search" style="position:absolute; left:14px; top:50%; transform:translateY(-50%); color:var(--muted); font-size:0.9rem;"></i>
                <input type="search" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Buscar campaña..." class="cn-input" style="padding-left:38px; padding-right:<?= $q ? '36px' : '14px' ?>; border-radius:12px; font-size:0.88rem;">
                <?php if($q): ?>
                    <a href="./publicidad.php" style="position:absolute; right:12px; top:50%; transform:translateY(-50%); color:var(--muted); text-decoration:none; font-weight:bold; font-size:1.1rem;">&times;</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- ── TARJETAS DE ESTADÍSTICAS RÁPIDAS ────────────────────── -->
    <div class="cn-stats-grid mb-4">
        <div class="card border-0 shadow-sm p-3 h-100" style="background:var(--card-bg); border-radius:16px; border:1px solid var(--border)!important;">
            <div class="d-flex align-items-center gap-3">
                <div style="width:44px; height:44px; border-radius:12px; background:rgba(99,102,241,0.12); color:#6366f1; display:flex; align-items:center; justify-content:center; font-size:1.3rem; flex-shrink:0;">
                    <i class="bi bi-megaphone-fill"></i>
                </div>
                <div>
                    <div style="font-size:1.4rem; font-weight:900; color:var(--text); line-height:1;"><?= $totalPublicidad ?></div>
                    <div style="font-size:0.75rem; font-weight:700; color:var(--muted); margin-top:4px; text-transform:uppercase; letter-spacing:0.04em;">Total Campañas</div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm p-3 h-100" style="background:var(--card-bg); border-radius:16px; border:1px solid var(--border)!important;">
            <div class="d-flex align-items-center gap-3">
                <div style="width:44px; height:44px; border-radius:12px; background:rgba(16,185,129,0.12); color:#10b981; display:flex; align-items:center; justify-content:center; font-size:1.3rem; flex-shrink:0;">
                    <i class="bi bi-play-circle-fill"></i>
                </div>
                <div>
                    <div style="font-size:1.4rem; font-weight:900; color:var(--text); line-height:1;"><?= $activas ?></div>
                    <div style="font-size:0.75rem; font-weight:700; color:var(--muted); margin-top:4px; text-transform:uppercase; letter-spacing:0.04em;">Activas</div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm p-3 h-100" style="background:var(--card-bg); border-radius:16px; border:1px solid var(--border)!important;">
            <div class="d-flex align-items-center gap-3">
                <div style="width:44px; height:44px; border-radius:12px; background:rgba(245,158,11,0.12); color:#f59e0b; display:flex; align-items:center; justify-content:center; font-size:1.3rem; flex-shrink:0;">
                    <i class="bi bi-calendar-event-fill"></i>
                </div>
                <div>
                    <div style="font-size:1.4rem; font-weight:900; color:var(--text); line-height:1;"><?= $programadas ?></div>
                    <div style="font-size:0.75rem; font-weight:700; color:var(--muted); margin-top:4px; text-transform:uppercase; letter-spacing:0.04em;">Programadas</div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm p-3 h-100" style="background:var(--card-bg); border-radius:16px; border:1px solid var(--border)!important;">
            <div class="d-flex align-items-center gap-3">
                <div style="width:44px; height:44px; border-radius:12px; background:rgba(239,51,99,0.12); color:var(--accent); display:flex; align-items:center; justify-content:center; font-size:1.3rem; flex-shrink:0;">
                    <i class="bi bi-stop-circle-fill"></i>
                </div>
                <div>
                    <div style="font-size:1.4rem; font-weight:900; color:var(--text); line-height:1;"><?= $vencidas ?></div>
                    <div style="font-size:0.75rem; font-weight:700; color:var(--muted); margin-top:4px; text-transform:uppercase; letter-spacing:0.04em;">Vencidas</div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── TOOLBAR DE FILTROS Y ACCIÓN ─────────────────────────── -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
        <div class="d-flex align-items-center gap-2" id="pubFilterTabs">
            <button type="button" class="btn btn-sm btn-accent filter-pub-btn active" data-filter="all" style="border-radius:10px; font-weight:800; padding:6px 16px;">
                Todas (<?= $totalPublicidad ?>)
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary filter-pub-btn" data-filter="activa" style="border-radius:10px; font-weight:700; padding:6px 14px; background:var(--card-bg);">
                Activas (<?= $activas ?>)
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary filter-pub-btn" data-filter="programada" style="border-radius:10px; font-weight:700; padding:6px 14px; background:var(--card-bg);">
                Programadas (<?= $programadas ?>)
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary filter-pub-btn" data-filter="vencida" style="border-radius:10px; font-weight:700; padding:6px 14px; background:var(--card-bg);">
                Vencidas (<?= $vencidas ?>)
            </button>
        </div>

        <?php if($ACL['crear']): ?>
            <a href="crearp.php" class="btn btn-accent px-4 py-2" style="border-radius:12px; font-weight:800; font-size:0.9rem; box-shadow:0 4px 15px rgba(239,51,99,0.3);">
                <i class="bi bi-plus-lg me-1"></i> Nueva Publicidad
            </a>
        <?php endif; ?>
    </div>

    <!-- ── TABLA DE CAMPAÑAS ───────────────────────────────────── -->
    <div class="card border-0 shadow-sm mb-4" style="background:var(--card-bg); border-radius:18px; border:1px solid var(--border)!important; overflow:hidden;">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle m-0 pub-table" style="color:var(--text);">
                    <thead>
                        <tr>
                            <th style="width:120px;">Banner</th>
                            <th>Campaña & Enlace</th>
                            <th style="width:170px;">Tipo de Elemento</th>
                            <th style="width:140px;">Estado</th>
                            <th style="width:200px;">Vigencia</th>
                            <?php if(!empty($ACL['editar']) || !empty($ACL['eliminar']) || !empty($ACL['leer'])): ?>
                                <th style="width:140px; text-align:right;">Acciones</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($publicidades)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="bi bi-megaphone" style="font-size:2.5rem; opacity:0.3; display:block; margin-bottom:8px;"></i>
                                    No se encontraron campañas de publicidad registradas.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach($publicidades as $pub):
                                $estado = estadoPublicidad($pub, $hoy);
                            ?>
                                <tr class="pub-row" data-estado="<?= $estado ?>">
                                    <td>
                                        <div style="width:100px; height:56px; border-radius:10px; overflow:hidden; border:1px solid var(--border); background:var(--bg); display:flex; align-items:center; justify-content:center; padding:4px;">
                                            <img src="<?= imageUrl($pub['imagen']) ?>" alt="Publicidad" style="max-width:100%; max-height:100%; object-fit:contain;" loading="lazy">
                                        </div>
                                    </td>
                                    <td>
                                        <strong style="font-weight:800; font-size:0.95rem; color:var(--text); display:block; margin-bottom:2px;">
                                            <?= htmlspecialchars($pub['titulo']) ?>
                                        </strong>
                                        <?php if(!empty($pub['url'])): ?>
                                            <a href="<?= htmlspecialchars($pub['url']) ?>" target="_blank" rel="noopener" style="font-size:0.78rem; font-weight:700; color:var(--accent); text-decoration:none; display:inline-flex; align-items:center; gap:4px; word-break:break-all;">
                                                <?= htmlspecialchars(mb_strimwidth($pub['url'], 0, 45, '...')) ?> <i class="bi bi-box-arrow-up-right" style="font-size:0.7rem;"></i>
                                            </a>
                                        <?php else: ?>
                                            <span style="font-size:0.75rem; color:var(--muted);">Sin enlace externo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge" style="background:var(--bg); color:var(--text); border:1px solid var(--border); font-size:0.78rem; font-weight:700; padding:6px 10px; border-radius:8px; white-space:nowrap;">
                                            <i class="bi <?= $pub['tipo'] == 1 ? 'bi-aspect-ratio-fill text-accent' : 'bi-square-fill text-warning' ?> me-1"></i>
                                            <?= $pub['tipo'] == 1 ? 'Banner Largo' : 'Banner Cuadrado' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if(!empty($ACL['editar'])): ?>
                                            <button type="button" class="btn btn-link p-0 text-decoration-none btn-toggle-pub" data-id="<?= $pub['id_pub'] ?>" title="Clic para cambiar estado activo/inactivo" style="border:none; background:none;">
                                        <?php endif; ?>

                                        <?php if($estado === 'activa'): ?>
                                            <span class="badge" style="background:rgba(16,185,129,0.12); color:#10b981; border:1px solid rgba(16,185,129,0.25); font-size:0.78rem; font-weight:800; padding:6px 12px; border-radius:20px; white-space:nowrap; cursor:pointer;">
                                                <i class="bi bi-circle-fill" style="font-size:0.4rem; vertical-align:middle; margin-right:4px;"></i> Activa
                                            </span>
                                        <?php elseif($estado === 'programada'): ?>
                                            <span class="badge" style="background:rgba(245,158,11,0.12); color:#f59e0b; border:1px solid rgba(245,158,11,0.25); font-size:0.78rem; font-weight:800; padding:6px 12px; border-radius:20px; white-space:nowrap; cursor:pointer;">
                                                <i class="bi bi-clock-fill me-1"></i> Programada
                                            </span>
                                        <?php elseif($estado === 'inactiva'): ?>
                                            <span class="badge" style="background:rgba(148,163,184,0.12); color:#64748b; border:1px solid rgba(148,163,184,0.25); font-size:0.78rem; font-weight:800; padding:6px 12px; border-radius:20px; white-space:nowrap; cursor:pointer;">
                                                <i class="bi bi-pause-circle-fill me-1"></i> Inactiva / Pausada
                                            </span>
                                        <?php else: ?>
                                            <span class="badge" style="background:rgba(239,51,99,0.12); color:var(--accent); border:1px solid rgba(239,51,99,0.25); font-size:0.78rem; font-weight:800; padding:6px 12px; border-radius:20px; white-space:nowrap; cursor:pointer;">
                                                <i class="bi bi-stop-circle-fill me-1"></i> Vencida
                                            </span>
                                        <?php endif; ?>

                                        <?php if(!empty($ACL['editar'])): ?>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div style="font-size:0.8rem; font-weight:600; color:var(--text); white-space:nowrap;">
                                            <div><i class="bi bi-calendar-event me-1 text-muted"></i> <?= date("d/m/Y", strtotime($pub['fecha_inicio'])) ?></div>
                                            <div style="color:var(--muted); font-size:0.75rem;"><i class="bi bi-calendar-check me-1"></i> <?= date("d/m/Y", strtotime($pub['fecha_fin'])) ?></div>
                                        </div>
                                    </td>
                                    <?php if(!empty($ACL['editar']) || !empty($ACL['eliminar']) || !empty($ACL['leer'])): ?>
                                        <td style="text-align:right;">
                                            <div class="d-inline-flex gap-1 flex-nowrap align-items-center justify-content-end" style="white-space:nowrap;">
                                                <?php if($ACL['editar']): ?>
                                                    <a href="editarp.php?id=<?= $pub['id_pub'] ?>" class="btn btn-sm btn-outline-secondary" style="border-radius:8px; width:32px; height:32px; padding:0; display:inline-flex; align-items:center; justify-content:center; background:var(--bg);" title="Editar">
                                                        <i class="bi bi-pencil-square"></i>
                                                    </a>
                                                <?php endif; ?>
                                                <?php if($ACL['leer']): ?>
                                                    <a href="verp.php?id=<?= $pub['id_pub'] ?>" class="btn btn-sm btn-outline-secondary" style="border-radius:8px; width:32px; height:32px; padding:0; display:inline-flex; align-items:center; justify-content:center; background:var(--bg);" title="Ver Estadísticas">
                                                        <i class="bi bi-bar-chart-fill text-accent"></i>
                                                    </a> 
                                                <?php endif; ?>
                                                <?php if($ACL['eliminar']): ?>
                                                    <button type="button" class="btn btn-sm btn-outline-danger btn-delete-publicidad" data-id="<?= $pub['id_pub'] ?>" data-titulo="<?= htmlspecialchars($pub['titulo']) ?>" style="border-radius:8px; width:32px; height:32px; padding:0; display:inline-flex; align-items:center; justify-content:center; background:var(--bg);" title="Eliminar">
                                                        <i class="bi bi-trash-fill"></i>
                                                    </button>
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
</div>

<!-- Modal de Confirmación para Eliminar -->
<div id="modalOverlayP" class="modal-nativo" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; overflow:auto; background-color:rgba(0,0,0,0.6); backdrop-filter:blur(4px); justify-content:center; align-items:center;">
    <div class="modal-content-nativo" style="max-width:440px; border-radius:18px; background:var(--card-bg); overflow:hidden; margin:auto; border:1px solid var(--border);">
        <div class="modal-header-nativo" style="padding:16px 20px; border-bottom:1px solid var(--border); display:flex; justify-content:space-between; align-items:center;">
            <h5 class="m-0 font-weight-bold" style="font-weight:800; color:var(--text);"><i class="bi bi-trash-fill text-danger me-2"></i> Confirmar Eliminación</h5>
            <span class="btn-cancel" style="font-size:24px; font-weight:bold; cursor:pointer; color:var(--muted);">&times;</span>
        </div>
        <div class="modal-body-nativo" style="padding:20px;">
            <p style="color:var(--text); font-size:0.9rem; margin-bottom:15px; font-weight:600;">
                ¿Estás seguro de que deseas eliminar la campaña <strong id="modalTitlePText" style="color:var(--accent);"></strong>?
            </p>
            <p class="text-muted small mb-4">Esta acción no se puede deshacer y la imagen del banner adjunto se eliminará permanentemente del servidor.</p>
            <form id="modalFormP" action="../controllers/eliminarp.php" method="POST">
                <input type="hidden" name="id" id="modalIdP">
                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-secondary px-3 btn-cancel" style="border-radius:10px; font-weight:700;">Cancelar</button>
                    <button type="submit" class="btn btn-danger px-4" style="border-radius:10px; font-weight:800;"><i class="bi bi-trash-fill me-1"></i> Eliminar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.pub-table thead th {
    background: rgba(0, 0, 0, 0.03) !important;
    color: var(--text) !important;
    font-size: 0.75rem !important;
    font-weight: 800 !important;
    text-transform: uppercase !important;
    letter-spacing: 0.05em !important;
    border-bottom: 1px solid var(--border) !important;
    padding: 12px 16px !important;
}
[data-bs-theme="dark"] .pub-table thead th {
    background: rgba(255, 255, 255, 0.04) !important;
}
</style>

<script>
document.addEventListener("DOMContentLoaded", () => {
    // Filtro interactivo de pestañas (Client-Side)
    const filterBtns = document.querySelectorAll(".filter-pub-btn");
    const rows = document.querySelectorAll(".pub-row");

    filterBtns.forEach(btn => {
        btn.addEventListener("click", () => {
            filterBtns.forEach(b => {
                b.classList.remove("active", "btn-accent");
                b.classList.add("btn-outline-secondary");
            });
            btn.classList.add("active", "btn-accent");
            btn.classList.remove("btn-outline-secondary");

            const filter = btn.dataset.filter;
            rows.forEach(row => {
                if (filter === "all" || row.dataset.estado === filter) {
                    row.style.display = "";
                } else {
                    row.style.display = "none";
                }
            });
        });
    });

    // Toggle activo/inactivo de campaña al hacer clic en el badge de estado
    document.querySelectorAll(".btn-toggle-pub").forEach(btn => {
        btn.addEventListener("click", async function(e) {
            e.preventDefault();
            const id = this.dataset.id;
            try {
                const res = await fetch('./../controllers/publicidad_toggle.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `id=${id}`
                });
                const data = await res.json();
                if (data.ok) {
                    showToast(`Campaña "${data.titulo}" ${data.activo ? 'activada' : 'desactivada (pausada)'} correctamente`, data.activo ? 'success' : 'info');
                    setTimeout(() => location.reload(), 350);
                } else {
                    showToast(data.error || 'Error al cambiar estado de la campaña', 'error');
                }
            } catch (err) {
                showToast('Error de conexión al cambiar estado', 'error');
            }
        });
    });

    // Modal de eliminación
    const modalOverlayP = document.getElementById("modalOverlayP");
    const modalIdP = document.getElementById("modalIdP");
    const modalTitlePText = document.getElementById("modalTitlePText");

    document.querySelectorAll(".btn-delete-publicidad").forEach(btn => {
        btn.addEventListener("click", () => {
            modalIdP.value = btn.dataset.id;
            modalTitlePText.textContent = `"${btn.dataset.titulo}"`;
            modalOverlayP.style.display = "flex";
        });
    });

    document.querySelectorAll(".btn-cancel").forEach(btn => {
        btn.addEventListener("click", () => {
            modalOverlayP.style.display = "none";
        });
    });

    window.addEventListener("click", (e) => {
        if (e.target === modalOverlayP) {
            modalOverlayP.style.display = "none";
        }
    });
});
</script>

<?php
    include("./../layout/footerAdmin.php");
?>