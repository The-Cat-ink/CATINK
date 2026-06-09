<?php
if(session_status() === PHP_SESSION_NONE){
    session_start();
}
require_once(__DIR__ . "/../data/conexion.php");
require_once(__DIR__ . "/../views/helpers/urlhelper.php");
// =========================
// Obtener categorías únicas
// =========================
$stmtCats = $con->prepare("SELECT nombre FROM categorias ORDER BY orden ASC, nombre ASC");
$stmtCats->execute();
$resultCats = $stmtCats->get_result();
$categorias = $resultCats->fetch_all(MYSQLI_ASSOC);
// =========================
// Obtener estado de secciones
// =========================
$stmt = $con->prepare("SELECT * FROM secciones");
$stmt->execute();
$result = $stmt->get_result();
$secciones = [];
while($row = $result->fetch_assoc()) {
    $secciones[$row['nombre']] = $row;
}
// Defaults para secciones no configuradas
if(!isset($secciones['publicidad'])) $secciones['publicidad'] = ['estado' => 0];
if(!isset($secciones['videos'])) $secciones['videos'] = ['estado' => 0];
// =========================
// Generar JSON-LD del menú
// =========================
$menuItems = [];
foreach($categorias as $cat) {
    $menuItems[] = [
        "@type" => "SiteNavigationElement",
        "name" => $cat['nombre'],
        "url" => categoryUrl($cat['nombre'])
    ];
}
$menuJson = [
    "@context" => "https://schema.org",
    "@graph" => $menuItems
];
?>
<!doctype html>
<html lang="es" data-bs-theme="light">
<head>
  <script>document.documentElement.setAttribute('data-bs-theme', localStorage.getItem('theme') || 'light');</script>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Google Tag Manager -->
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
    j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
    'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,'script','dataLayer','GTM-NT5RHZXX');</script>
  <!-- End Google Tag Manager -->
  <title><?= isset($pageTitle)? htmlspecialchars($pageTitle) . " - CatInk" : "CatInk | Noticias de Anime, Manga y Más" ?></title>
  <meta name="description" content="<?= isset($pageDescription)? htmlspecialchars($pageDescription) : "Cat Ink: sitio especializado en anime, manga y series. Noticias, avances, reseñas y contenido actualizado para fans del entretenimiento." ?>">
  <!-- Open Graph -->
  <meta property="og:title" content="<?= isset($pageTitle) ? htmlspecialchars($pageTitle) : 'CatInk | Noticias de Anime, Manga y Series' ?>">
  <meta property="og:description" content="<?= isset($pageDescription) ? htmlspecialchars($pageDescription) : 'Noticias, avances y reseñas del mundo del anime y manga.' ?>">
  <meta property="og:url" content="<?= $canonical ?? 'https://www.catink.com.mx/' ?>">
  <meta property="og:type" content="website">
  <meta property="og:image" content="<?= $ogImage ?? 'https://www.catink.com.mx/img/catink-og.png' ?>">
  <meta property="og:image:width" content="1200">
  <meta property="og:image:height" content="630">
  <!-- Twitter Card -->
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:site" content="@The_Catink">
  <meta name="twitter:title" content="<?= htmlspecialchars($pageTitle ?? 'CatInk') ?>">
  <meta name="twitter:description" content="<?= htmlspecialchars($pageDescription ?? 'Noticias de anime y manga') ?>">
  <meta name="twitter:image" content="<?= $ogImage ?? 'https://www.catink.com.mx/img/catink-og.png' ?>">
  <meta name="google-adsense-account" content="ca-pub-8588111729852920">
  <!-- Canonical -->
  <link rel="canonical" href="<?= $canonical ?? 'https://www.catink.com.mx/' ?>">
  <!-- Favicon -->
  <link rel="icon" href="<?= basePath() ?>/catink-icon.ico?v=2" type="image/x-icon">
  <link rel="icon" href="<?= basePath() ?>/img/catink-icon.png?v=2" type="image/png">
  <link rel="apple-touch-icon" href="<?= basePath() ?>/img/catink-icon.png?v=2">
  <!-- JSON-LD: Menú -->
  <script type="application/ld+json">
    <?= json_encode($menuJson, JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT) ?>
  </script>
  <!-- CSS / JS -->
  <link rel="stylesheet" href="<?= basePath() ?>/CSS/styles.css?v=<?= filemtime(__DIR__ . '/../CSS/styles.css') ?>">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
  <script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
  <script src="https://unpkg.com/quill-image-resize-module/image-resize.min.js"></script>
  <script async src="https://www.instagram.com/embed.js"></script>
  <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-8588111729852920"
     crossorigin="anonymous"></script>
</head>
<body>
<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-NT5RHZXX"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->
<?php $__pag = basename($_SERVER['PHP_SELF'], '.php'); ?>
<?php if($__pag !== 'login' && $__pag !== 'registro'): ?>
<nav class="navbar">
  <div class="container-fluid">
    <a class="navbar-brand" href="<?= basePath() . '/' ?>">
      <img id="logo" src="" alt="CatInk Logo">
    </a>
    <form class="nav-search buscador-desplegable d-none d-md-flex" onsubmit="return false;">
      <input
        type="text"
        id="searchInput"
        placeholder="Buscar noticias..."
        autocomplete="off"
        class="search-input input-desplegable"
      >
      <div id="searchResults" class="search-results"></div>
      <button type="button" id="clearBtn" class="search-btn clear-btn btn-oculto" title="Limpiar búsqueda">
        <i class="bi bi-x-lg"></i>
      </button>
      <button type="button" id="searchBtn" class="search-btn btn-lupa" title="Buscar">
        <i class="bi bi-search"></i>
      </button>
    </form>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
      data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <div class="d-md-none" style="padding: 10px 12px 4px; width: 100%;">
        <form class="nav-search" onsubmit="return false;">
          <input type="text" id="searchInputMobile" placeholder="Buscar noticias..." autocomplete="off" class="search-input">
          <div id="searchResultsMobile" class="search-results"></div>
          <button type="button" id="clearBtnMobile" class="search-btn clear-btn btn-oculto" title="Limpiar búsqueda">
            <i class="bi bi-x-lg"></i>
          </button>
          <button type="button" id="searchBtnMobile" class="search-btn btn-lupa" title="Buscar">
            <i class="bi bi-search"></i>
          </button>
        </form>
      </div>
      <ul class="navbar-nav align-items-center">
        <?php foreach ($categorias as $cat): ?>
          <li class="nav-item">
            <a class="nav-link" href="<?= categoryUrl($cat['nombre']) ?>">
              <?= htmlspecialchars($cat['nombre']) ?>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
    <div class="nav-actions">
      <!-- SWITCH DE TEMA PILL-SHAPED -->
      <div class="theme-switch-pill" title="Cambiar tema">
        <span class="theme-icon active" id="themeIconSun">
          <i class="bi bi-sun"></i>
        </span>
        <span class="theme-icon" id="themeIconMoon">
          <i class="bi bi-moon"></i>
        </span>
      </div>

      <?php if(isset($_SESSION['usuario'])): ?>
        <?php if(($_SESSION['ACL']['noticias']['crear'] ?? false) || ($_SESSION['superadmin'] ?? false)): ?>
          <a href="<?= basePath() ?>/views/crear.php" class="btn btn-outline-secondary" title="Crear Noticia">
            <i class="bi bi-pencil"></i>
          </a>
        <?php endif; ?>

        <?php if($_SESSION['superadmin'] ?? false): ?>
          <a href="<?= basePath() ?>/views/admin.php" class="btn-admin-panel" title="Panel de Administración">
            <i class="bi bi-speedometer2"></i>
          </a>
        <?php endif; ?>

        <div class="user-dropdown">
          <span class="user-avatar">
            <i class="bi bi-person"></i>
          </span>
          <span class="user-name"><?= htmlspecialchars($_SESSION['usuario']) ?></span>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="<?= basePath() ?>/perfil"><i class="bi bi-person-circle"></i> Mi Perfil</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item text-danger" href="<?= basePath() ?>/controllers/logoutcontroller.php"><i class="bi bi-box-arrow-right"></i> Cerrar sesión</a></li>
          </ul>
        </div>
      <?php else: ?>
        <a href="<?= basePath() ?>/login" class="btn btn-outline-secondary" title="Iniciar sesión">
          <i class="bi bi-person"></i>
        </a>
      <?php endif; ?>
    </div>
  </div>
</nav>
<?php endif; ?>
<!-- Inicio del contenido principal de la página -->
<main class="site-main">