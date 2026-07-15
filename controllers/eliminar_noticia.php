<?php
session_start();
include("./aclcontroller.php");
proteger('noticias','eliminar');
include("../data/conexion.php");
require_once("../views/helpers/activity_log.php");

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

    $stmt = $con->prepare("UPDATE noticias SET eliminado_en = NOW(), eliminado_por = ? WHERE id = ? AND eliminado_en IS NULL");
    $stmt->bind_param("ii", $eliminadoPor, $id);
    $ok = $stmt->execute();
    if ($ok) {
        logActivity($con, 'eliminar', 'noticias', 'Envió a la papelera noticia ID ' . $id);
    }
    $stmt->close();

    if ($from === 'publica') {
        header("Location: ../index.php");
    } else {
        $destino = ($from === 'borradores') ? 'borradores' : 'contenidos';
        header("Location: ../views/$destino.php?" . ($ok ? "msg=eliminado" : "error=no_eliminado"));
    }
} else {
    header("Location: ../views/contenidos.php");
}
?>
