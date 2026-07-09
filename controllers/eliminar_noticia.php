<?php
session_start();
include("./aclcontroller.php");
proteger('noticias','eliminar');
include("../data/conexion.php");
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
    $stmt = $con->prepare("UPDATE noticias SET eliminado_en = NOW(), eliminado_por = ? WHERE id = ? AND eliminado_en IS NULL");
    $stmt->bind_param("ii", $eliminadoPor, $id);
    if ($stmt->execute()) {
        header("Location: ../views/contenidos.php?msg=eliminado");
    } else {
        header("Location: ../views/contenidos.php?error=no_eliminado");
    }
    $stmt->close();
} else {
    header("Location: ../views/contenidos.php");
}
?>
