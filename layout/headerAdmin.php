<?php
if(session_status() == PHP_SESSION_NONE){
    session_start();
}
require_once "./../views/helpers/helper.php";
require_once "./../views/helpers/urlhelper.php";
require_once "./../views/helpers/acl.php";
require_once "./../views/helpers/publicidadhelper.php";
if (!isset($_SESSION["usuario"])) {
    header("Location: ./../index.php");
    exit();
}
//detectar modulo automaticamente
$archivoActual = basename($_SERVER['PHP_SELF'], ".php");
$mapVistaModulo = [
    'cats' => 'categorias',
    'contenidos' => 'noticias',
    'recomendados' => 'recomendados',
    'esperamos' => 'esperamos',
    'publicidad' => 'publicidad',
    'suscripciones' => 'suscripciones',
    'usuarios' => 'usuarios',
    'lectores' => 'lectores',
    'correos' => 'correos',
    'videos' => 'videos',
    'paginas' => 'paginas',
    'actividad' => 'actividad',
    'papelera' => 'papelera',
    'avatares' => 'avatares'
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
// Guardar id_u en la sesión para usarlo en controladores
$_SESSION['id_u'] = $fila['id_u'];

// Auto-disparador pasivo de correos programados (fallback para tareas cron)
if (!isset($_SESSION['cron_check_time']) || (time() - $_SESSION['cron_check_time']) > 300) {
    $_SESSION['cron_check_time'] = time();
    $cronTriggerScript = true;
}
?>
<!doctype html>
<html lang="es" data-bs-theme="light">
<head>
  <script>document.documentElement.setAttribute('data-bs-theme', localStorage.getItem('theme') || 'light');</script>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>CatInk News - Admin</title>
  <link rel="stylesheet" href="<?= basePath() ?>/CSS/styles.css?v=<?= filemtime(__DIR__ . '/../CSS/styles.css') ?>">
  <link rel="stylesheet" href="<?= basePath() ?>/CSS/admin.css?v=<?= filemtime(__DIR__ . '/../CSS/admin.css') ?>">
  <link rel="icon" type="image/png" href="<?= basePath() ?>/img/catink-icon.png">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="https://unpkg.com/cropperjs@1.6.2/dist/cropper.min.css">
  <script src="https://unpkg.com/cropperjs@1.6.2/dist/cropper.min.js"></script>
  <script src="https://cdn.ckeditor.com/ckeditor5/41.4.2/decoupled-document/ckeditor.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0"></script>
  <script>window.ADMIN_BASE = '<?= basePath() ?>';</script>
  <script src="https://cdn.jsdelivr.net/npm/@hotwired/turbo@7.3.0/dist/turbo.es2017-umd.js" defer></script>
  <?php
  // Vistas con JS inline no idempotente para Turbo (init en DOMContentLoaded,
  // const de nivel superior) → forzar recarga completa al navegar hacia ellas.
  $turboReloadPages = [
      'admin', 'crear', 'continuar_borrador', 'crearp', 'crearu', 'editar', 'editarp', 'editaru',
      'cats', 'contenidos', 'borradores', 'recomendados', 'esperamos', 'publicidad',
      'correos', 'suscripciones', 'usuarios', 'lectores', 'videos', 'avatares'
  ];
  if (in_array($archivoActual, $turboReloadPages, true)): ?>
  <meta name="turbo-visit-control" content="reload">
  <?php endif; ?>
    <?php if($ACL): ?>
        <script>
            window.ACL = {
                crear: <?= $ACL['crear'] ? 'true' : 'false' ?>,
                leer: <?= $ACL['leer'] ? 'true' : 'false' ?>,
                editar: <?= $ACL['editar'] ? 'true' : 'false' ?>,
                eliminar: <?= $ACL['eliminar'] ? 'true' : 'false' ?>
            };
        </script>
    <?php endif; ?>
    <?php if (!empty($cronTriggerScript)): ?>
        <script>
            if (navigator.sendBeacon) {
                navigator.sendBeacon('<?= basePath() ?>/views/email/cron.php');
            } else {
                fetch('<?= basePath() ?>/views/email/cron.php', { cache: 'no-store' });
            }
        </script>
    <?php endif; ?>
</head>
<body class="has-sidebar">
<div class="sidebar">
    <div class="logotipo">
        <a href="<?= basePath() ?>/">
            <img class="logo-icon" src="<?= basePath() ?>/img/catink-icon.png" alt="Catink">
            <img class="logo-full" src="<?= basePath() ?>/img/logo.png" alt="Logo">
        </a>
    </div>
    <ul class="sidebar-menu">
        <li class="sidebar-menu-item"></li>
        <li class="sidebar-menu-item">
            <a href="./admin.php" class="sidebar-menu-link" data-tooltip="Inicio">
                <i class="bi bi-house"></i> <span class="sb-label">Inicio</span>
            </a>
        </li>
        <?php if (($_SESSION['ACL']['noticias']['crear']?? false)): ?>
            <li class="sidebar-menu-item">
                <a href="<?= basePath() ?>/views/crear.php" class="sidebar-menu-link" data-tooltip="Crear Noticia">
                    <i class="bi bi-pencil-square"></i> <span class="sb-label">Crear Noticia</span>
                </a>
            </li>
        <?php endif; ?>
        <?php if (($_SESSION['ACL']['noticias']['leer']?? false)): ?>
            <li class="sidebar-menu-item">
                <a href="./borradores.php" class="sidebar-menu-link" data-tooltip="Borradores">
                    <i class="bi bi-journal-text"></i> <span class="sb-label">Borradores</span>
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
        <?php if (($_SESSION['ACL']['recomendados']['leer']?? false)): ?>
            <li class="sidebar-menu-item">
                <a href="./recomendados.php" class="sidebar-menu-link" data-tooltip="Recomendados">
                    <i class="bi bi-star"></i> <span class="sb-label">Recomendados</span>
                </a>
            </li>
        <?php endif; ?>
        <?php if (($_SESSION['ACL']['esperamos']['leer']?? false)): ?>
            <li class="sidebar-menu-item">
                <a href="./esperamos.php" class="sidebar-menu-link" data-tooltip="Lo más Esperado">
                    <i class="bi bi-hourglass-split"></i> <span class="sb-label">Lo más Esperado</span>
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
                <a href="./usuarios.php" class="sidebar-menu-link" data-tooltip="Administradores/Editores">
                    <i class="bi bi-person"></i> <span class="sb-label">Administradores/Editores</span>
                </a>
            </li>
        <?php endif; ?>
        <?php if (($_SESSION['ACL']['lectores']['leer']?? false)): ?>
            <li class="sidebar-menu-item">
                <a href="./lectores.php" class="sidebar-menu-link" data-tooltip="Lectores">
                    <i class="bi bi-person-badge"></i> <span class="sb-label">Lectores</span>
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
        <?php if (($_SESSION['ACL']['avatares']['leer']?? false)): ?>
            <li class="sidebar-menu-item">
                <a href="./avatares.php" class="sidebar-menu-link" data-tooltip="Fotos de Perfil">
                    <i class="bi bi-person-circle"></i> <span class="sb-label">Fotos de Perfil</span>
                </a>
            </li>
        <?php endif; ?>
        <?php if (($_SESSION['ACL']['papelera']['leer']?? false)): ?>
            <li class="sidebar-menu-item">
                <a href="./papelera.php" class="sidebar-menu-link" data-tooltip="Papelera">
                    <i class="bi bi-trash3"></i> <span class="sb-label">Papelera</span>
                </a>
            </li>
        <?php endif; ?>
        <?php if (($_SESSION['ACL']['actividad']['leer']?? false)): ?>
            <li class="sidebar-menu-item">
                <a href="./actividad.php" class="sidebar-menu-link" data-tooltip="Registro de Actividad">
                    <i class="bi bi-clock-history"></i> <span class="sb-label">Actividad</span>
                </a>
            </li>
        <?php endif; ?>
    </ul>
    <div class="sidebar-footer">
      <label class="theme-switch-icons sidebar-theme-switch" title="Cambiar tema">
        <input type="checkbox" id="themeToggle" class="theme-switch-input">
        <span class="theme-switch-icons-track">
          <span class="theme-switch-icons-icon theme-switch-icons-sun">
            <i class="bi bi-sun"></i>
          </span>
          <span class="theme-switch-icons-icon theme-switch-icons-moon">
            <i class="bi bi-moon-stars"></i>
          </span>
          <span class="theme-switch-icons-thumb"></span>
        </span>
      </label>
      <a href="./../controllers/logoutcontroller.php" class="sidebar-menu-link" data-tooltip="Salir" data-turbo="false">
          <i class="bi bi-box-arrow-right"></i> <span class="sb-label">Salir</span>
      </a>
    </div>
</div>
<div id="sidebarBackdrop" class="sidebar-backdrop"></div>
<main class="site-main">
  <button id="sidebarToggle" class="btn btn-outline-secondary d-md-none mb-3 sidebar-toggle-mobile">
    <i class="bi bi-list"></i> Menú
  </button>
