<?php
session_start();
include("./../data/conexion.php");

$id_u = $_SESSION['id_u'] ?? 0;
if ($id_u <= 0) {
    header("Location: ./../views/login.php");
    exit();
}

$id_red = intval($_GET['id'] ?? 0);
if ($id_red > 0) {
    $stmt = $con->prepare("DELETE FROM redes_sociales WHERE id_red=?");
    $stmt->bind_param("i", $id_red);
    $stmt->execute();
}

header("Location: ./../views/paginas.php?msg=red_eliminada");
exit();
