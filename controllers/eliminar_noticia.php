<?php
session_start();
include(__DIR__ . "/aclcontroller.php");
proteger('noticias','eliminar');
include(__DIR__ . "/../data/conexion.php");
require_once(__DIR__ . "/../views/helpers/activity_log.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = intval($_POST['id']);

    // ==========================
    // Identificar quién elimina
    // ==========================
    $eliminadoPor = $_SESSION['id_u'] ?? null;
    if (!$eliminadoPor && !empty($_SESSION['usuario'])) {
        $stmtU = $con->prepare("SELECT id_u FROM usuarios WHERE usuario = ?");
        $stmtU->bind_param("s", $_SESSION['usuario']);
        $stmtU->execute();
        $resU = $stmtU->get_result();
        if ($filaU = $resU->fetch_assoc()) {
            $eliminadoPor = (int)$filaU['id_u'];
        }
        $stmtU->close();
    }

    // ==========================
    // SOFT DELETE: enviar a la papelera
    // No se borran imágenes ni datos; solo se marca como eliminada.
    // Solo el superadmin podrá restaurarla o eliminarla definitivamente.
    // ==========================
    // Volver a la vista de origen. Si se eliminó desde la página pública de la
    // noticia (from=publica) se regresa al inicio del sitio, ya que la noticia
    // deja de ser visible; en el panel se vuelve a contenidos/borradores.
    $from = $_POST['from'] ?? '';

    $ok = false;
    $detalle = '';

    $stmt = @$con->prepare("UPDATE noticias SET eliminado_en = NOW(), eliminado_por = ? WHERE id = ? AND eliminado_en IS NULL");
    if (!$stmt) {
        // La consulta ni siquiera se pudo preparar (p. ej. faltan las columnas
        // de la papelera en la base de datos de este entorno).
        $detalle = $con->error;
    } else {
        $stmt->bind_param("ii", $eliminadoPor, $id);
        if (!$stmt->execute()) {
            $detalle = $stmt->error;
        } elseif ($stmt->affected_rows < 1) {
            $detalle = 'La noticia no existe o ya estaba eliminada.';
        } else {
            $ok = true;
        }
        $stmt->close();
    }

    if ($ok) {
        logActivity($con, 'eliminar', 'noticias', 'Envió a la papelera noticia ID ' . $id);
    }
    
    require_once(__DIR__ . "/../views/helpers/cachehelper.php");
    clear_cache_by_prefix();

    if ($from === 'publica') {
        // El sitio público no tiene banner de avisos: se usa el toast del header.
        if ($ok) {
            $_SESSION['flash'] = ['tipo' => 'success', 'texto' => 'Noticia eliminada correctamente.'];
        } else {
            $texto = 'No se pudo eliminar la noticia.';
            // El detalle técnico solo se muestra al superadmin.
            if (!empty($_SESSION['superadmin']) && $detalle !== '') {
                $texto .= ' ' . $detalle;
            }
            $_SESSION['flash'] = ['tipo' => 'error', 'texto' => $texto];
        }
        header("Location: ../index.php");
    } else {
        $destino = ($from === 'borradores') ? 'borradores' : 'contenidos';
        header("Location: ../views/$destino.php?" . ($ok ? "msg=eliminado" : "error=no_eliminado"));
    }
} else {
    header("Location: ../views/contenidos.php");
}
?>
