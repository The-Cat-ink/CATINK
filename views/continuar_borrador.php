<?php
// ============================================================
// CONTINUAR BORRADOR
// Vista para retomar un borrador desde donde se quedó. Es el formulario de
// "Alta de noticia" (crear.php) precargado con el borrador: mantiene el
// programador, el botón de publicar y el autoguardado sobre ESE mismo
// borrador. No se usa editar.php, que es la pantalla de noticias ya
// publicadas y por eso oculta el programador.
// ============================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include(__DIR__ . "/../data/conexion.php");

$BORRADOR_ID = intval($_GET['id'] ?? 0);

// El borrador debe existir y seguir siéndolo (si ya se publicó, no hay nada que continuar).
$ok = false;
if ($BORRADOR_ID > 0) {
    $chk = $con->prepare("SELECT id FROM noticias WHERE id = ? AND borrador = 1 AND eliminado_en IS NULL");
    $chk->bind_param("i", $BORRADOR_ID);
    $chk->execute();
    $ok = $chk->get_result()->num_rows > 0;
}
if (!$ok) {
    header("Location: borradores.php?error=1");
    exit();
}

// crear.php lee $BORRADOR_ID y se precarga con él.
include(__DIR__ . "/crear.php");
