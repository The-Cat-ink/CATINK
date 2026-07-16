<?php
include("./../layout/headerAdmin.php");
include("./../data/conexion.php");

if (empty($ACL['leer'])) {
    header("Location: admin.php");
    exit();
}

// Filtros
$f_modulo      = trim($_GET['modulo'] ?? '');
$f_accion      = trim($_GET['accion'] ?? '');
$f_usuario     = trim($_GET['usuario'] ?? '');
$f_fecha_desde = trim($_GET['fecha_desde'] ?? '');
$f_fecha_hasta = trim($_GET['fecha_hasta'] ?? '');

// Paginación
$limit = 50;
$page = max(1, intval($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

// Construir condiciones SQL
$condiciones = [];
$params = [];
$types = "";

if ($f_modulo !== '') {
    $condiciones[] = "modulo = ?";
    $params[] = $f_modulo;
    $types .= "s";
}
if ($f_accion !== '') {
    $condiciones[] = "accion = ?";
    $params[] = $f_accion;
    $types .= "s";
}
if ($f_usuario !== '') {
    $condiciones[] = "username = ?";
    $params[] = $f_usuario;
    $types .= "s";
}
if ($f_fecha_desde !== '') {
    $condiciones[] = "created_at >= ?";
    $params[] = $f_fecha_desde . " 00:00:00";
    $types .= "s";
}
if ($f_fecha_hasta !== '') {
    $condiciones[] = "created_at <= ?";
    $params[] = $f_fecha_hasta . " 23:59:59";
    $types .= "s";
}

$where_clause = "";
if (!empty($condiciones)) {
    $where_clause = "WHERE " . implode(" AND ", $condiciones);
}

// 1. Obtener lista de usuarios distintos para el filtro
$users_res = $con->query("SELECT DISTINCT username FROM activity_log ORDER BY username ASC");
$usernames = [];
if ($users_res) {
    while ($urow = $users_res->fetch_assoc()) {
        $usernames[] = $urow['username'];
    }
}

// 2. Obtener estadísticas rápidas
$total_logs = 0;
$logs_hoy = 0;
$logs_semana = 0;

$count_total_res = $con->query("SELECT COUNT(*) AS total FROM activity_log");
if ($count_total_res) {
    $total_logs = (int)$count_total_res->fetch_assoc()['total'];
}

$count_hoy_res = $con->query("SELECT COUNT(*) AS total FROM activity_log WHERE created_at >= CURDATE()");
if ($count_hoy_res) {
    $logs_hoy = (int)$count_hoy_res->fetch_assoc()['total'];
}

$count_semana_res = $con->query("SELECT COUNT(*) AS total FROM activity_log WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
if ($count_semana_res) {
    $logs_semana = (int)$count_semana_res->fetch_assoc()['total'];
}

// 3. Contar total de registros filtrados para la paginación
$total_filtrados = 0;
if (!empty($condiciones)) {
    $stmt_count = $con->prepare("SELECT COUNT(*) AS total FROM activity_log $where_clause");
    if ($stmt_count) {
        if (!empty($params)) {
            $stmt_count->bind_param($types, ...$params);
        }
        $stmt_count->execute();
        $total_filtrados = (int)$stmt_count->get_result()->fetch_assoc()['total'];
        $stmt_count->close();
    }
} else {
    $total_filtrados = $total_logs;
}

$total_pages = ceil($total_filtrados / $limit);

// 4. Consultar los registros reales de log
$sql_logs = "SELECT * FROM activity_log $where_clause ORDER BY created_at DESC LIMIT ? OFFSET ?";
$stmt_logs = $con->prepare($sql_logs);

$logs = [];
if ($stmt_logs) {
    if (!empty($condiciones)) {
        $bind_types = $types . "ii";
        $bind_params = array_merge($params, [$limit, $offset]);
        $stmt_logs->bind_param($bind_types, ...$bind_params);
    } else {
        $stmt_logs->bind_param("ii", $limit, $offset);
    }
    $stmt_logs->execute();
    $logs = $stmt_logs->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_logs->close();
}

// Helper para badges
function getBadgeClassAndIcon($accion) {
    switch ($accion) {
        case 'crear':
        case 'borrador':
            return ['bg-success-subtle text-success border-success-subtle', 'bi-plus-circle-fill'];
        case 'editar':
        case 'reprogramar':
            return ['bg-warning-subtle text-warning border-warning-subtle', 'bi-pencil-fill'];
        case 'eliminar':
        case 'banear':
            return ['bg-danger-subtle text-danger border-danger-subtle', 'bi-trash-fill'];
        case 'restaurar':
        case 'desbanear':
        case 'apelar':
            return ['bg-info-subtle text-info border-info-subtle', 'bi-arrow-counterclockwise'];
        default:
            return ['bg-secondary-subtle text-secondary border-secondary-subtle', 'bi-gear-fill'];
    }
}
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4" style="flex-wrap: wrap; gap: 12px;">
        <h1 style="margin:0;"><i class="bi bi-clock-history"></i> Registro de Actividad</h1>
        <p style="margin: 0; color: var(--muted); font-size: 0.9rem;">Historial completo de acciones administrativas</p>
    </div>

    <!-- Grid de Estadísticas -->
    <div class="stats-grid mb-4">
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(99,102,241,0.1); color: #6366f1;"><i class="bi bi-database"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?= number_format($total_logs) ?></span>
                <span class="stat-label">Total Logs</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(16,185,129,0.1); color: #10b981;"><i class="bi bi-calendar-check"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?= number_format($logs_hoy) ?></span>
                <span class="stat-label">Acciones Hoy</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(245,158,11,0.1); color: #f59e0b;"><i class="bi bi-graph-up"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?= number_format($logs_semana) ?></span>
                <span class="stat-label">Últimos 7 días</span>
            </div>
        </div>
    </div>

    <!-- Barra de Filtros -->
    <div class="card shadow-sm mb-4" style="background: var(--card-bg); border: 1px solid var(--border); border-radius: 12px;">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-2 col-sm-6">
                    <label class="form-label" style="font-size: 0.85rem; font-weight: 600; color: var(--text);">Módulo</label>
                    <select name="modulo" class="input" style="margin-bottom:0; width: 100%; height: 38px; padding: 6px 12px;">
                        <option value="">Todos</option>
                        <option value="noticias" <?= $f_modulo === 'noticias' ? 'selected' : '' ?>>Noticias</option>
                        <option value="publicidad" <?= $f_modulo === 'publicidad' ? 'selected' : '' ?>>Publicidad</option>
                        <option value="usuarios" <?= $f_modulo === 'usuarios' ? 'selected' : '' ?>>Usuarios Admin</option>
                        <option value="categorias" <?= $f_modulo === 'categorias' ? 'selected' : '' ?>>Categorías</option>
                        <option value="videos" <?= $f_modulo === 'videos' ? 'selected' : '' ?>>Videos</option>
                        <option value="logos" <?= $f_modulo === 'logos' ? 'selected' : '' ?>>Logos Marcas</option>
                        <option value="correos" <?= $f_modulo === 'correos' ? 'selected' : '' ?>>Correos</option>
                        <option value="suscripciones" <?= $f_modulo === 'suscripciones' ? 'selected' : '' ?>>Suscripciones</option>
                        <option value="recomendados" <?= $f_modulo === 'recomendados' ? 'selected' : '' ?>>Recomendados</option>
                        <option value="esperados" <?= $f_modulo === 'esperados' ? 'selected' : '' ?>>Lo más Esperado</option>
                        <option value="moderacion" <?= $f_modulo === 'moderacion' ? 'selected' : '' ?>>Moderación (Baneos)</option>
                        <option value="lectores" <?= $f_modulo === 'lectores' ? 'selected' : '' ?>>Lectores (Apelaciones)</option>
                    </select>
                </div>
                <div class="col-md-2 col-sm-6">
                    <label class="form-label" style="font-size: 0.85rem; font-weight: 600; color: var(--text);">Acción</label>
                    <select name="accion" class="input" style="margin-bottom:0; width: 100%; height: 38px; padding: 6px 12px;">
                        <option value="">Todas</option>
                        <option value="crear" <?= $f_accion === 'crear' ? 'selected' : '' ?>>Crear</option>
                        <option value="editar" <?= $f_accion === 'editar' ? 'selected' : '' ?>>Editar</option>
                        <option value="eliminar" <?= $f_accion === 'eliminar' ? 'selected' : '' ?>>Eliminar</option>
                        <option value="restaurar" <?= $f_accion === 'restaurar' ? 'selected' : '' ?>>Restaurar</option>
                        <option value="reprogramar" <?= $f_accion === 'reprogramar' ? 'selected' : '' ?>>Reprogramar</option>
                        <option value="banear" <?= $f_accion === 'banear' ? 'selected' : '' ?>>Banear</option>
                        <option value="desbanear" <?= $f_accion === 'desbanear' ? 'selected' : '' ?>>Desbanear</option>
                        <option value="apelar" <?= $f_accion === 'apelar' ? 'selected' : '' ?>>Apelar</option>
                        <option value="borrador" <?= $f_accion === 'borrador' ? 'selected' : '' ?>>Borrador</option>
                    </select>
                </div>
                <div class="col-md-2 col-sm-6">
                    <label class="form-label" style="font-size: 0.85rem; font-weight: 600; color: var(--text);">Usuario</label>
                    <select name="usuario" class="input" style="margin-bottom:0; width: 100%; height: 38px; padding: 6px 12px;">
                        <option value="">Todos</option>
                        <?php foreach ($usernames as $uname): ?>
                            <option value="<?= htmlspecialchars($uname) ?>" <?= $f_usuario === $uname ? 'selected' : '' ?>><?= htmlspecialchars($uname) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 col-sm-6">
                    <label class="form-label" style="font-size: 0.85rem; font-weight: 600; color: var(--text);">Desde</label>
                    <input type="date" name="fecha_desde" value="<?= htmlspecialchars($f_fecha_desde) ?>" class="input" style="margin-bottom:0; width: 100%; height: 38px; padding: 6px 12px;">
                </div>
                <div class="col-md-2 col-sm-6">
                    <label class="form-label" style="font-size: 0.85rem; font-weight: 600; color: var(--text);">Hasta</label>
                    <input type="date" name="fecha_hasta" value="<?= htmlspecialchars($f_fecha_hasta) ?>" class="input" style="margin-bottom:0; width: 100%; height: 38px; padding: 6px 12px;">
                </div>
                <div class="col-md-2 col-sm-12 d-flex gap-2">
                    <button type="submit" class="btn btn-accent w-100" style="height: 38px; display: flex; align-items: center; justify-content: center; gap: 6px;">
                        <i class="bi bi-funnel"></i> Filtrar
                    </button>
                    <?php if ($f_modulo || $f_accion || $f_usuario || $f_fecha_desde || $f_fecha_hasta): ?>
                        <a href="./actividad.php" class="btn btn-secondary" style="height: 38px; display: flex; align-items: center; justify-content: center;" title="Limpiar Filtros">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla de Logs -->
    <div class="card shadow-sm" style="background: var(--card-bg); border: 1px solid var(--border); border-radius: 12px; overflow: hidden;">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="contenidos-table">
                    <thead>
                        <tr>
                            <th style="padding: 16px 20px;">Fecha y Hora</th>
                            <th>Usuario</th>
                            <th>Acción</th>
                            <th>Módulo</th>
                            <th>Descripción</th>
                            <th style="text-align: right; padding-right: 20px;">Dirección IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="6" class="text-center" style="padding: 40px; color: var(--muted);">
                                    <i class="bi bi-info-circle" style="font-size: 2rem; display: block; margin-bottom: 10px;"></i>
                                    No se encontraron registros de actividad con los filtros seleccionados.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($logs as $log): 
                                $badgeData = getBadgeClassAndIcon($log['accion']);
                                ?>
                                <tr style="border-bottom: 1px solid var(--border);">
                                    <td style="padding: 16px 20px; font-weight: 500; font-size: 0.85rem; white-space: nowrap; color: var(--text);">
                                        <?= date('d M Y, H:i:s', strtotime($log['created_at'])) ?>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="avatar-mini" style="width: 24px; height: 24px; border-radius: 50%; background: var(--accent); color: white; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: bold; text-transform: uppercase;">
                                                <?= mb_substr($log['username'], 0, 2) ?>
                                            </div>
                                            <span style="font-weight: 600; color: var(--text);"><?= htmlspecialchars($log['username']) ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge border <?= $badgeData[0] ?>" style="padding: 5px 10px; border-radius: 6px; font-size: 0.75rem; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;">
                                            <i class="bi <?= $badgeData[1] ?>"></i>
                                            <?= ucfirst($log['accion']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge" style="background: rgba(255, 255, 255, 0.05); color: var(--text); border: 1px solid var(--border); padding: 5px 10px; border-radius: 6px; font-size: 0.75rem; text-transform: capitalize;">
                                            <?= htmlspecialchars($log['modulo']) ?>
                                        </span>
                                    </td>
                                    <td style="font-size: 0.88rem; color: var(--text); max-width: 320px; overflow: hidden; text-overflow: ellipsis; white-space: normal; line-height: 1.4;">
                                        <?= htmlspecialchars($log['descripcion']) ?>
                                    </td>
                                    <td style="text-align: right; padding-right: 20px; color: var(--muted); font-family: monospace; font-size: 0.85rem;">
                                        <?= htmlspecialchars($log['ip'] ?? 'N/D') ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Paginación -->
    <?php if ($total_pages > 1): ?>
        <div class="d-flex justify-content-between align-items-center mt-4 mb-5" style="flex-wrap: wrap; gap: 12px;">
            <p style="margin: 0; color: var(--muted); font-size: 0.9rem;">
                Mostrando <?= count($logs) ?> registros de un total de <?= number_format($total_filtrados) ?>
            </p>
            <div class="pagination-container" style="display: flex; gap: 4px;">
                <?php
                // Query string sin 'page'
                $query_params = $_GET;
                unset($query_params['page']);
                $query_string = http_build_query($query_params);
                $url_prefix = "./actividad.php?" . ($query_string ? $query_string . "&" : "") . "page=";
                ?>
                
                <?php if ($page > 1): ?>
                    <a href="<?= $url_prefix . ($page - 1) ?>" class="btn btn-secondary" style="padding: 6px 12px;"><i class="bi bi-chevron-left"></i></a>
                <?php endif; ?>

                <?php
                $start = max(1, $page - 2);
                $end = min($total_pages, $page + 2);
                
                if ($start > 1) {
                    echo '<a href="'.$url_prefix.'1" class="btn btn-secondary" style="padding: 6px 12px;">1</a>';
                    if ($start > 2) {
                        echo '<span style="color: var(--muted); align-self: center; margin: 0 4px;">...</span>';
                    }
                }

                for ($i = $start; $i <= $end; $i++) {
                    $active_class = ($i === $page) ? 'btn-accent' : 'btn-secondary';
                    echo '<a href="'.$url_prefix.$i.'" class="btn '.$active_class.'" style="padding: 6px 12px;">'.$i.'</a>';
                }

                if ($end < $total_pages) {
                    if ($end < $total_pages - 1) {
                        echo '<span style="color: var(--muted); align-self: center; margin: 0 4px;">...</span>';
                    }
                    echo '<a href="'.$url_prefix.$total_pages.'" class="btn btn-secondary" style="padding: 6px 12px;">'.$total_pages.'</a>';
                }
                ?>

                <?php if ($page < $total_pages): ?>
                    <a href="<?= $url_prefix . ($page + 1) ?>" class="btn btn-secondary" style="padding: 6px 12px;"><i class="bi bi-chevron-right"></i></a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include("./../layout/footerAdmin.php"); ?>
