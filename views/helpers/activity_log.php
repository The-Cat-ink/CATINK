<?php
// ================================================================
// HELPER: Registro de Actividad del Panel de Administración
// Uso: logActivity($con, 'crear', 'noticias', 'Creó la noticia «Título»')
// Fail-soft: si la tabla no existe no interrumpe la acción principal.
// ================================================================

function logActivity($con, string $accion, string $modulo, string $descripcion): void {
    // Resolver actor desde sesión
    if (session_status() === PHP_SESSION_NONE) {
        @session_start();
    }

    $userId   = (int)($_SESSION['id_u'] ?? 0);
    $username = $_SESSION['usuario'] ?? 'sistema';

    // IP del cliente (soporte para proxies)
    $ip = $_SERVER['HTTP_CLIENT_IP']
        ?? (isset($_SERVER['HTTP_X_FORWARDED_FOR'])
            ? explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]
            : ($_SERVER['REMOTE_ADDR'] ?? null));
    $ip = $ip ? trim($ip) : null;

    // Truncar descripción por seguridad
    $descripcion = mb_substr($descripcion, 0, 255);
    $accion      = mb_substr($accion, 0, 30);
    $modulo      = mb_substr($modulo, 0, 40);

    // INSERT fail-soft: si la tabla no existe o hay error, ignoramos silenciosamente
    $stmt = @$con->prepare(
        "INSERT INTO activity_log (user_id, username, accion, modulo, descripcion, ip)
         VALUES (?, ?, ?, ?, ?, ?)"
    );
    if (!$stmt) return;

    $stmt->bind_param("isssss", $userId, $username, $accion, $modulo, $descripcion, $ip);
    @$stmt->execute();
    $stmt->close();
}
