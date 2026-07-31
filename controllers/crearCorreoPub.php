<?php
session_start();
include("./aclcontroller.php");
proteger('correos','crear', false);
include("../data/conexion.php");
include("../views/helpers/img.php");

$titulo     = $_POST['titulo'] ?? '';
$badge      = $_POST['badge'] ?? 'Anuncio / Promoción';
$preheader  = $_POST['preheader'] ?? '';
$theme      = $_POST['theme'] ?? 'light';
$contenido  = $_POST['contenido'] ?? '';
$url        = $_POST['url'] ?? '';
$cta_text   = $_POST['cta_text'] ?? 'Ver promoción';
$envio      = $_POST['envio'] ?? date('Y-m-d H:i:s');
$envio      = str_replace('T', ' ', $envio);

$carpetaDestino = __DIR__ . "/../img/correo";
if (!is_dir($carpetaDestino)) {
    mkdir($carpetaDestino, 0777, true);
}

$imagenNombre = '';
if (isset($_FILES['imagenCorreo']) && $_FILES['imagenCorreo']['error'] === 0) {
    $img = $_FILES['imagenCorreo'];
    $imagenNombre = convertirImagenAWebp(
        $img,
        $carpetaDestino,
        1200,
        80
    );
}

$stmt = $con->prepare("
    INSERT INTO correos_publicitarios 
    (titulo, badge, preheader, theme, contenido, imagen, url_c, cta_text, envio) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
");
$stmt->bind_param(
    "sssssssss",
    $titulo,
    $badge,
    $preheader,
    $theme,
    $contenido,
    $imagenNombre,
    $url,
    $cta_text,
    $envio
);

if ($stmt->execute()) {
    $insertedId = $stmt->insert_id;
    require_once("../views/helpers/activity_log.php");
    logActivity($con, 'crear', 'correos', 'Creó correo publicitario «' . mb_substr($titulo, 0, 80) . '»');

    if (isset($_POST['enviar_ahora']) && $_POST['enviar_ahora'] === '1') {
        // Forzar fecha de envío a la hora actual y ejecutar el procesador de correo
        $con->query("UPDATE correos_publicitarios SET envio = NOW(), enviado = 0 WHERE id_correo = {$insertedId}");
        @ob_start();
        require_once(__DIR__ . "/../views/email/correoPublicidad.php");
        @ob_end_clean();
    }

    header("Location: ../views/correos.php?success=1");
    exit();
} else {
    header("Location: ../views/correos.php?error=1");
    exit();
}