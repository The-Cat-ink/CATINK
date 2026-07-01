<?php
// ============================================================
// HELPERS DE MODERACIÓN / BANEO
// Solo el superadmin puede banear/desbanear. Aplica a
// lectores (comentaristas) y usuarios (admins/editores).
// ============================================================

// Presets de duración, en orden ascendente (horas → días → ... → permanente).
// clave => ['label' => texto visible, 'modify' => expresión para DateTime::modify, null = permanente]
function duracionesBaneo() {
    return [
        '1h'   => ['label' => '1 hora',      'modify' => '+1 hour'],
        '6h'   => ['label' => '6 horas',     'modify' => '+6 hours'],
        '12h'  => ['label' => '12 horas',    'modify' => '+12 hours'],
        '1d'   => ['label' => '1 día',       'modify' => '+1 day'],
        '3d'   => ['label' => '3 días',      'modify' => '+3 days'],
        '1w'   => ['label' => '1 semana',    'modify' => '+1 week'],
        '2w'   => ['label' => '2 semanas',   'modify' => '+2 weeks'],
        '1mo'  => ['label' => '1 mes',       'modify' => '+1 month'],
        '3mo'  => ['label' => '3 meses',     'modify' => '+3 months'],
        '6mo'  => ['label' => '6 meses',     'modify' => '+6 months'],
        '1y'   => ['label' => '1 año',       'modify' => '+1 year'],
        'perm' => ['label' => 'Permanente',  'modify' => null],
    ];
}

// ¿La fila (lector o usuario) está baneada ahora mismo?
// Espera las columnas baneado_permanente y baneado_hasta.
function estaBaneado($row) {
    if (!empty($row['baneado_permanente'])) {
        return true;
    }
    if (!empty($row['baneado_hasta']) && strtotime($row['baneado_hasta']) > time()) {
        return true;
    }
    return false;
}

// Texto legible del estado de baneo (o null si no está baneado).
function textoBaneo($row) {
    if (!empty($row['baneado_permanente'])) {
        return 'Suspendido permanentemente';
    }
    if (!empty($row['baneado_hasta']) && strtotime($row['baneado_hasta']) > time()) {
        return 'Suspendido hasta el ' . date('d M Y, H:i', strtotime($row['baneado_hasta']));
    }
    return null;
}

// Devuelve el estado de baneo de un usuario consultando la BD.
// $tipo: 'lector' | 'admin'. Devuelve la fila con columnas de baneo o null.
function obtenerBaneo($con, $tipo, $userId) {
    $userId = (int)$userId;
    $sql = $tipo === 'lector'
        ? "SELECT baneado_hasta, baneado_permanente, baneado_motivo FROM lectores WHERE id = ?"
        : "SELECT baneado_hasta, baneado_permanente, baneado_motivo FROM usuarios WHERE id_u = ?";
    // Fail-soft: si la migración de baneo aún no se aplicó en este entorno,
    // prepare() falla; devolvemos null (= sin baneo) en vez de romper la página.
    $stmt = @$con->prepare($sql);
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param("i", $userId);
    if (!@$stmt->execute()) {
        return null;
    }
    return $stmt->get_result()->fetch_assoc();
}

// ¿El visitante actual es superadmin?
function esSuperAdminActual() {
    return isset($_SESSION['superadmin']) && $_SESSION['superadmin'] === true;
}

// Inserta una notificación para un usuario (lector o admin).
// $tipo: 'lector' | 'admin'. Fail-soft: si la tabla aún no existe
// (migración pendiente), no rompe la acción principal.
function crearNotificacion($con, $tipo, $userId, $titulo, $mensaje, $tipoNotif = 'general') {
    $userId = (int)$userId;
    if ($userId <= 0 || !in_array($tipo, ['lector', 'admin'], true)) {
        return false;
    }
    $sql = "INSERT INTO notificaciones (tipo_usuario, user_id, tipo, titulo, mensaje)
            VALUES (?, ?, ?, ?, ?)";
    $stmt = @$con->prepare($sql);
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param("sisss", $tipo, $userId, $tipoNotif, $titulo, $mensaje);
    return @$stmt->execute();
}

// Verifica si el actor de la sesión (lector logueado) está baneado para
// comentar/like/reportar. Devuelve el texto del baneo o null si puede actuar.
function baneoLectorActual($con) {
    if (($_SESSION['tipo'] ?? null) !== 'lector' || !isset($_SESSION['id_lector'])) {
        return null;
    }
    $estado = obtenerBaneo($con, 'lector', (int)$_SESSION['id_lector']);
    return $estado ? textoBaneo($estado) : null;
}
