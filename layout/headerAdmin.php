<?php
if(session_status() == PHP_SESSION_NONE){
    session_start();
}
require_once "./../views/helpers/helper.php";
require_once "./../views/helpers/urlhelper.php";
require_once "./../views/helpers/acl.php";
if (!isset($_SESSION["usuario"])) {
    header("Location: ./../index.php");
    exit();
}
//detectar modulo automaticamente
$archivoActual = basename($_SERVER['PHP_SELF'], ".php");
$mapVistaModulo = [
    'cats' => 'categorias',
    'contenidos' => 'noticias',
    'publicidad' => 'publicidad',
    'suscripciones' => 'suscripciones',
    'usuarios' => 'usuarios',
    'correos' => 'correos',
    'videos' => 'videos'
];
$superadmin = $_SESSION['superadmin'] ?? false;
// cargar acl globalmente
$ACL = null;
if(isset($mapVistaModulo[$archivoActual])){
    $ACL = cargarACL($mapVistaModulo[$archivoActual]);
}
// proteccion para admin.php
if($archivoActual === "admin" && !$superadmin){
    // si no tiene lectura en ningun modulo → fuera
    $tieneAcceso = false;
    foreach($_SESSION['ACL'] as $mod){
        if($mod['leer']){
            $tieneAcceso = true;
            break;
        }
    }
    if(!$tieneAcceso){
        session_destroy();
        header("Location: ./../index.php");
        exit();
    }
}
$usuario = $_SESSION['usuario'];
include("./../data/conexion.php");
// Obtener usuario desde la base de datos actualizada
$stmt = $con->prepare("SELECT id_u, nombre, usuario FROM usuarios WHERE usuario = ?");
$stmt->bind_param("s", $usuario);
$stmt->execute();
$result = $stmt->get_result();
$fila = $result->fetch_assoc();
if (!$fila) {
    // Si el usuario no existe en la BD, destruir sesión y redirigir
    session_destroy();
    header("Location: ./../index.php");
    exit();
}
?>
<!doctype html>
<html lang="es" data-bs-theme="light">
<head>
  <script>document.documentElement.setAttribute('data-bs-theme', localStorage.getItem('theme') || 'light');</script>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>CatInk News - Admin</title>
  <link rel="stylesheet" href="./../CSS/styles.css?v=<?= filemtime(__DIR__ . '/../CSS/styles.css') ?>">
  <link rel="stylesheet" href="./../CSS/admin.css?v=<?= filemtime(__DIR__ . '/../CSS/admin.css') ?>">
  <link rel="icon" type="image/png" href="./../img/catink-icon.png">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="https://unpkg.com/cropperjs@1.6.2/dist/cropper.min.css">
  <script src="https://unpkg.com/cropperjs@1.6.2/dist/cropper.min.js"></script>
  <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
  <script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
  <script src="https://unpkg.com/quill-image-resize-module/image-resize.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0"></script>
    <?php if($ACL): ?>
        <script>
            const ACL = {
                crear: <?= $ACL['crear'] ? 'true' : 'false' ?>,
                leer: <?= $ACL['leer'] ? 'true' : 'false' ?>,
                editar: <?= $ACL['editar'] ? 'true' : 'false' ?>,
                eliminar: <?= $ACL['eliminar'] ? 'true' : 'false' ?>
            };
        </script>
    <?php endif; ?>
</head>
<body class="has-sidebar">
<div class="sidebar">
    <div class="logotipo">
        <a href="./../index.php">
            <img class="logo-full" src="./../img/logo.png" alt="Logo">
            <img class="logo-icon" src="./../img/catink-icon.png" alt="Catink">
        </a>
    </div>
    <div id="user">
        <h4><?= htmlspecialchars($fila['usuario']) ?></h4>
    </div>
    <ul class="sidebar-menu">
        <li class="sidebar-menu-item"></li>
        <?php if ($superadmin): ?>
            <li class="sidebar-menu-item">
                <a href="./admin.php" class="sidebar-menu-link" data-tooltip="Inicio">
                    <i class="bi bi-house"></i> <span class="sb-label">Inicio</span>
                </a>
            </li>
        <?php endif; ?>
        <?php if (($_SESSION['ACL']['categorias']['leer']?? false)): ?>
            <li class="sidebar-menu-item">
                <a href="./cats.php" class="sidebar-menu-link" data-tooltip="Categorías">
                    <i class="bi bi-grid"></i> <span class="sb-label">Categorias</span>
                </a>
            </li>
        <?php endif; ?>
        <?php if (($_SESSION['ACL']['noticias']['leer']?? false)): ?>
            <li class="sidebar-menu-item">
                <a href="./contenidos.php" class="sidebar-menu-link" data-tooltip="Contenido">
                    <i class="bi bi-newspaper"></i> <span class="sb-label">Contenido</span>
                </a>
            </li>
        <?php endif; ?>
        <?php if (($_SESSION['ACL']['publicidad']['leer']?? false)): ?>
            <li class="sidebar-menu-item">
                <a href="./publicidad.php" class="sidebar-menu-link" data-tooltip="Publicidad">
                    <i class="bi bi-megaphone"></i> <span class="sb-label">Publicidad</span>
                </a>
            </li>
        <?php endif; ?>
        <?php if (($_SESSION['ACL']['correos']['leer']?? false)): ?>
            <li class="sidebar-menu-item">
                <a href="./correos.php" class="sidebar-menu-link" data-tooltip="Correos">
                    <i class="bi bi-envelope"></i> <span class="sb-label">Correos</span>
                </a>
            </li>
        <?php endif; ?>
        <?php if (($_SESSION['ACL']['suscripciones']['leer']?? false)): ?>
            <li class="sidebar-menu-item">
                <a href="./suscripciones.php" class="sidebar-menu-link" data-tooltip="Suscripciones">
                    <i class="bi bi-people"></i> <span class="sb-label">Suscripciones</span>
                </a>
            </li>
        <?php endif; ?>
        <?php if (($_SESSION['ACL']['usuarios']['leer']?? false)): ?>
            <li class="sidebar-menu-item">
                <a href="./usuarios.php" class="sidebar-menu-link" data-tooltip="Usuarios">
                    <i class="bi bi-person"></i> <span class="sb-label">Usuarios</span>
                </a>
            </li>
        <?php endif; ?>
        <?php if (($_SESSION['ACL']['videos']['leer']?? false)): ?>
            <li class="sidebar-menu-item">
                <a href="./videos.php" class="sidebar-menu-link" data-tooltip="Videos">
                    <i class="bi bi-play-circle"></i> <span class="sb-label">Videos</span>
                </a>
            </li>
        <?php endif; ?>
        <?php if ($superadmin || ($_SESSION['ACL']['usuarios']['leer']?? false)): ?>
            <li class="sidebar-menu-item">
                <a href="./avatares.php" class="sidebar-menu-link" data-tooltip="Fotos de Perfil">
                    <i class="bi bi-person-circle"></i> <span class="sb-label">Fotos de Perfil</span>
                </a>
            </li>
        <?php endif; ?>
    </ul>
    <div class="sidebar-footer">
      <button id="themeToggle" class="sidebar-menu-link theme-toggle-btn" title="Cambiar tema">
          <i class="bi bi-moon theme-icon"></i> <span class="sb-label">Cambiar tema</span>
      </button>
      <a href="./../controllers/logoutcontroller.php" class="sidebar-menu-link" data-tooltip="Salir">
          <i class="bi bi-box-arrow-right"></i> <span class="sb-label">Salir</span>
      </a>
    </div>
</div>
<div id="sidebarBackdrop" class="sidebar-backdrop"></div>
<main class="site-main">
  <button id="sidebarToggle" class="btn btn-outline-secondary d-md-none mb-3 siderbar-toggle-mobile">
    <i class="bi bi-list"></i> Menú
  </button>
