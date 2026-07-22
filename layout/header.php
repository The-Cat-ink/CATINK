<?php
if(session_status() === PHP_SESSION_NONE){
    session_start();
}
require_once(__DIR__ . "/../data/conexion.php");
require_once(__DIR__ . "/../views/helpers/urlhelper.php");
require_once(__DIR__ . "/../views/helpers/publicidadhelper.php");
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

// Auto-disparador pasivo de correos programados (fallback para tareas cron)
if (!isset($_SESSION['cron_check_time']) || (time() - $_SESSION['cron_check_time']) > 300) {
    $_SESSION['cron_check_time'] = time();
    $cronTriggerScript = true;
}

// =========================
// Obtener datos del usuario actual
// =========================
$usuarioActual = null;
$fotoPersonal = null;
$avatarActual = null;
if(isset($_SESSION['usuario']) && isset($_SESSION['tipo'])){
    $tipo = $_SESSION['tipo'];
    $usuario = $_SESSION['usuario'];
    
    if($tipo === 'admin'){
        $stmtUser = $con->prepare("SELECT u.*, a.imagen as avatar_imagen FROM usuarios u LEFT JOIN avatares_perfil a ON u.avatar_id = a.id_avatar WHERE u.usuario = ?");
        $stmtUser->bind_param("s", $usuario);
        $stmtUser->execute();
        $usuarioActual = $stmtUser->get_result()->fetch_assoc();
    } else {
        $stmtUser = $con->prepare("SELECT l.*, a.imagen as avatar_imagen FROM lectores l LEFT JOIN avatares_perfil a ON l.avatar_id = a.id_avatar WHERE l.usuario = ?");
        $stmtUser->bind_param("s", $usuario);
        $stmtUser->execute();
        $usuarioActual = $stmtUser->get_result()->fetch_assoc();
    }
    
    if($usuarioActual){
        $fotoPersonal = $usuarioActual['foto_personal'] ?? null;
        $avatarActual = $usuarioActual['avatar_imagen'] ?? null;
        if($tipo === 'lector'){
            require_once(__DIR__ . "/../views/helpers/moderacion.php");
            $lectorBaneado = estaBaneado($usuarioActual);
            $lectorBanText = textoBaneo($usuarioActual);
            $lectorApelado = (int)($usuarioActual['apelado'] ?? 0);
        }
    }
}
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
  <?php if(!empty($noTurboCache)): ?>
  <!-- Evita que Turbo Drive sirva una instantánea cacheada con datos viejos (formularios/perfil) -->
  <meta name="turbo-cache-control" content="no-cache">
  <?php endif; ?>
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
  <!-- Manifest PWA -->
  <link rel="manifest" href="<?= basePath() ?>/manifest.json">
  <meta name="theme-color" content="#121216">
  <!-- Favicon -->
  <link rel="icon" href="<?= basePath() ?>/catink-icon.ico?v=2" type="image/x-icon">
  <link rel="icon" href="<?= basePath() ?>/img/catink-icon.png?v=2" type="image/png">
  <link rel="apple-touch-icon" href="<?= basePath() ?>/img/catink-icon.png?v=2">
  <!-- JSON-LD: Menú -->
  <script type="application/ld+json">
    <?= json_encode($menuJson, JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT) ?>
  </script>
  <!-- CSS / JS -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= basePath() ?>/CSS/styles.css?v=<?= filemtime(__DIR__ . '/../CSS/styles.css') ?>">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <script async src="https://www.instagram.com/embed.js"></script>
  <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-8588111729852920"
     crossorigin="anonymous"></script>
  <script src="<?= basePath() ?>/CSS/offline-manager.js?v=<?= filemtime(__DIR__ . '/../CSS/offline-manager.js') ?>"></script>
  <script src="https://cdn.jsdelivr.net/npm/@hotwired/turbo@7.3.0/dist/turbo.es2017-umd.js" defer></script>
</head>
<body>
<!-- Google Tag Manager (noscript) -->

<?php if (isset($_SESSION['usuario']) && isset($_SESSION['tipo']) && $_SESSION['tipo'] === 'lector'): ?>
  <!-- Estilos del Banner y Modal de Suspensión (Siempre disponibles para este tipo de usuario) -->
  <style>
  /* Estilos del Banner */
  .ban-alert-banner {
    position: fixed;
    bottom: 20px;
    left: 50%;
    transform: translateX(-50%);
    width: 90%;
    max-width: 900px;
    background: linear-gradient(135deg, #EF3363 0%, #c12248 100%);
    color: #ffffff;
    padding: 16px 24px;
    border-radius: 12px;
    box-shadow: 0 8px 24px rgba(239, 51, 99, 0.3);
    z-index: 9999;
    animation: slideUp 0.5s cubic-bezier(0.16, 1, 0.3, 1);
  }
  .ban-alert-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
    flex-wrap: wrap;
  }
  .ban-alert-text {
    display: flex;
    align-items: center;
    gap: 12px;
    flex: 1;
    font-size: 0.95rem;
    line-height: 1.4;
  }
  .ban-alert-icon {
    font-size: 1.4rem;
    flex-shrink: 0;
  }
  .ban-alert-btn {
    background: #ffffff;
    color: #EF3363;
    border: none;
    padding: 10px 20px;
    border-radius: 8px;
    font-weight: 700;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.2s ease;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
  }
  .ban-alert-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(0,0,0,0.15);
    background: #fdfdfd;
  }
  
  /* Estilos del Modal */
  .appeal-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.6);
    backdrop-filter: blur(4px);
    z-index: 10000;
    display: flex;
    align-items: center;
    justify-content: center;
    animation: fadeIn 0.3s ease;
  }
  .appeal-modal-content {
    background: var(--card-bg);
    border: 1px solid var(--border);
    border-radius: 16px;
    padding: 24px;
    width: 90%;
    max-width: 450px;
    box-shadow: 0 12px 36px rgba(0,0,0,0.25);
    animation: scaleUp 0.3s cubic-bezier(0.16, 1, 0.3, 1);
  }
  .appeal-modal-content h3 {
    font-weight: 700;
    font-size: 1.25rem;
    margin-bottom: 8px;
    color: var(--text);
  }
  
  @keyframes slideUp {
    from { transform: translate(-50%, 100px); opacity: 0; }
    to { transform: translate(-50%, 0); opacity: 1; }
  }
  @keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
  }
  <?php if (!empty($cronTriggerScript)): ?>
    <script>
        if (navigator.sendBeacon) {
            navigator.sendBeacon('<?= basePath() ?>/views/email/cron.php');
        } else {
            fetch('<?= basePath() ?>/views/email/cron.php', { cache: 'no-store' });
        }
    </script>
  <?php endif; ?>
  </style>

  <!-- HTML inicial si ya está baneado al cargar la página -->
  <?php if (!empty($lectorBaneado)): ?>
    <div class="ban-alert-banner" id="banAlertBanner" data-turbo-permanent>
      <div class="ban-alert-content">
        <div class="ban-alert-text">
          <i class="bi bi-exclamation-triangle-fill ban-alert-icon"></i>
          <span>
            <strong>Tu cuenta está suspendida.</strong> <?= htmlspecialchars($lectorBanText) ?>. 
            <?php if (!empty($usuarioActual['baneado_motivo'])): ?>
              Motivo: <em style="color: rgba(255,255,255,0.85);">"<?= htmlspecialchars($usuarioActual['baneado_motivo']) ?>"</em>.
            <?php endif; ?>
            <?php if ($lectorApelado === 0): ?>
              Como es tu primera suspensión, tienes la oportunidad de apelar una única vez para recuperar el acceso de forma inmediata.
            <?php else: ?>
              Ya has utilizado tu única oportunidad de apelación.
            <?php endif; ?>
          </span>
        </div>
        <?php if ($lectorApelado === 0): ?>
          <button type="button" class="ban-alert-btn" id="btnOpenAppeal" onclick="document.getElementById('modalApelar').style.display='flex'">Apelar ahora</button>
        <?php endif; ?>
      </div>
    </div>

    <?php if ($lectorApelado === 0): ?>
      <!-- Modal de Apelación -->
      <div id="modalApelar" class="appeal-modal" style="display:none;" data-turbo-permanent>
        <div class="appeal-modal-content">
          <h3 style="margin-top:0;"><i class="bi bi-shield-exclamation" style="color:var(--accent);"></i> Apelar Suspensión</h3>
          <p style="margin: 10px 0 15px; font-size: 0.9rem; line-height: 1.4; color: var(--muted);">
            Para ser desbaneado de forma inmediata y automática por única ocasión, debes aceptar nuestro reglamento escribiendo la palabra <strong>acepto</strong> a continuación:
          </p>
          <div style="margin-bottom:15px;">
            <input type="text" id="appealInput" placeholder="Escribe 'acepto' aquí..." style="width:100%; padding:10px; border:1px solid var(--border); border-radius:8px; font-size:0.9rem; background:var(--card-bg); color:var(--text); text-align:center; font-weight:bold;">
            <p id="appealError" style="color:#EF3363; font-size:0.8rem; margin-top:5px; display:none; font-weight:600; text-align:center;">Debes escribir exactamente la palabra "acepto"</p>
          </div>
          <div style="display:flex; justify-content:flex-end; gap:8px;">
            <button type="button" class="btn-perfil-save" style="background:var(--muted); padding: 8px 16px; font-size: 0.9rem; width: auto;" id="btnCancelAppeal" onclick="document.getElementById('modalApelar').style.display='none'">Cancelar</button>
            <button type="button" id="btnConfirmAppeal" class="btn-perfil-save" style="padding: 8px 16px; font-size: 0.9rem; width: auto;"><i class="bi bi-check-circle"></i> Confirmar</button>
          </div>
        </div>
      </div>
    <?php endif; ?>
  <?php endif; ?>

  <!-- Script de Validación y Polling en Tiempo Real -->
  <script>
  function setupAppealScripts() {
    const btnConfirm = document.getElementById('btnConfirmAppeal');
    const input = document.getElementById('appealInput');
    const err = document.getElementById('appealError');
    const modal = document.getElementById('modalApelar');
    const btnCancel = document.getElementById('btnCancelAppeal');
    if (!btnConfirm || !input || !err || !modal) return;
    
    if (btnCancel) {
      btnCancel.addEventListener('click', () => {
        modal.style.display = 'none';
      });
    }
    
    if (!btnConfirm._hasListener) {
      btnConfirm._hasListener = true;
      
      modal.addEventListener('click', (e) => {
        if (e.target === modal) {
          modal.style.display = 'none';
        }
      });
      
      btnConfirm.addEventListener('click', async () => {
        const val = input.value.trim().toLowerCase();
        if (val !== 'acepto') {
          err.style.display = 'block';
          input.focus();
          return;
        }
        err.style.display = 'none';
        btnConfirm.disabled = true;
        
        try {
          const res = await fetch('<?= basePath() ?>/controllers/apelar_lector.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
          });

          if (!res.ok) {
            throw new Error('HTTP ' + res.status);
          }

          const data = await res.json();

          function showAppealToast(msg, type) {
            let container = document.querySelector('.toast-container');
            if (!container) {
              container = document.createElement('div');
              container.className = 'toast-container';
              document.body.appendChild(container);
            }
            const toast = document.createElement('div');
            toast.className = 'toast-msg toast-' + (type || 'success');
            toast.textContent = msg;
            container.appendChild(toast);
            setTimeout(() => toast.remove(), 3200);
          }

          if (data.success) {
            modal.style.display = 'none';
            showAppealToast(data.success, 'success');
            setTimeout(() => { location.reload(); }, 1600);
          } else {
            showAppealToast(data.error || 'No se pudo procesar la apelación.', 'error');
            btnConfirm.disabled = false;
          }
        } catch(e) {
          (function(){
            let container = document.querySelector('.toast-container');
            if (!container) {
              container = document.createElement('div');
              container.className = 'toast-container';
              document.body.appendChild(container);
            }
            const toast = document.createElement('div');
            toast.className = 'toast-msg toast-error';
            toast.textContent = 'Error de conexión al intentar apelar. Inténtalo de nuevo.';
            container.appendChild(toast);
            setTimeout(() => toast.remove(), 3200);
          })();
          btnConfirm.disabled = false;
        }
      });
    }
  }

  document.addEventListener('turbo:load', () => {
    // Evitar múltiples intervalos
    if (window.banCheckInterval) {
      clearInterval(window.banCheckInterval);
    }
    
    setupAppealScripts();
    
    // Si ya existe el botón en la página inicializada por PHP, le enlazamos evento
    const btnOpenInit = document.getElementById('btnOpenAppeal');
    if (btnOpenInit) {
      btnOpenInit.addEventListener('click', () => {
        const m = document.getElementById('modalApelar');
        if (m) m.style.display = 'flex';
      });
    }
    
    const checkBanStatus = async () => {
      try {
        const res = await fetch('<?= basePath() ?>/controllers/consultar_baneo.php');
        const data = await res.json();
        
        if (data.banned) {
          let banner = document.getElementById('banAlertBanner');
          if (!banner) {
            // Crear banner
            banner = document.createElement('div');
            banner.className = 'ban-alert-banner';
            banner.id = 'banAlertBanner';
            banner.setAttribute('data-turbo-permanent', '');
            
            const motivoHtml = data.motivo ? ` Motivo: <em style="color: rgba(255,255,255,0.85);">"${data.motivo}"</em>.` : '';
            const apeladoMsg = data.apelado === 0 
              ? 'Como es tu primera suspensión, tienes la oportunidad de apelar una única vez para recuperar el acceso de forma inmediata.'
              : 'Ya has utilizado tu única oportunidad de apelación.';
            
            const appealBtn = data.apelado === 0
              ? `<button type="button" class="ban-alert-btn" id="btnOpenAppeal">Apelar ahora</button>`
              : '';
              
            banner.innerHTML = `
              <div class="ban-alert-content">
                <div class="ban-alert-text">
                  <i class="bi bi-exclamation-triangle-fill ban-alert-icon"></i>
                  <span>
                    <strong>Tu cuenta está suspendida.</strong> ${data.ban_text}.${motivoHtml} ${apeladoMsg}
                  </span>
                </div>
                ${appealBtn}
              </div>
            `;
            document.body.appendChild(banner);
            
            // Vincular el botón dinámico de apertura
            const btnOpen = document.getElementById('btnOpenAppeal');
            if (btnOpen) {
              btnOpen.addEventListener('click', () => {
                const m = document.getElementById('modalApelar');
                if (m) m.style.display = 'flex';
              });
            }
          }
          
          if (data.apelado === 0) {
            let modal = document.getElementById('modalApelar');
            if (!modal) {
              modal = document.createElement('div');
              modal.id = 'modalApelar';
              modal.className = 'appeal-modal';
              modal.style.display = 'none';
              modal.setAttribute('data-turbo-permanent', '');
              modal.innerHTML = `
                <div class="appeal-modal-content">
                  <h3 style="margin-top:0;"><i class="bi bi-shield-exclamation" style="color:var(--accent);"></i> Apelar Suspensión</h3>
                  <p style="margin: 10px 0 15px; font-size: 0.9rem; line-height: 1.4; color: var(--muted);">
                    Para ser desbaneado de forma inmediata y automática por única ocasión, debes aceptar nuestro reglamento escribiendo la palabra <strong>acepto</strong> a continuación:
                  </p>
                  <div style="margin-bottom:15px;">
                    <input type="text" id="appealInput" placeholder="Escribe 'acepto' aquí..." style="width:100%; padding:10px; border:1px solid var(--border); border-radius:8px; font-size:0.9rem; background:var(--card-bg); color:var(--text); text-align:center; font-weight:bold;">
                    <p id="appealError" style="color:#EF3363; font-size:0.8rem; margin-top:5px; display:none; font-weight:600; text-align:center;">Debes escribir exactamente la palabra "acepto"</p>
                  </div>
                  <div style="display:flex; justify-content:flex-end; gap:8px;">
                    <button type="button" class="btn-perfil-save" style="background:var(--muted); padding: 8px 16px; font-size: 0.9rem; width: auto;" id="btnCancelAppeal">Cancelar</button>
                    <button type="button" id="btnConfirmAppeal" class="btn-perfil-save" style="padding: 8px 16px; font-size: 0.9rem; width: auto;"><i class="bi bi-check-circle"></i> Confirmar</button>
                  </div>
                </div>
              `;
              document.body.appendChild(modal);
            }
            setupAppealScripts();
          }
        } else {
          // Si no está baneado, quitar banner y modal si existen
          const banner = document.getElementById('banAlertBanner');
          if (banner) banner.remove();
          const modal = document.getElementById('modalApelar');
          if (modal) modal.remove();
        }
      } catch(e) {
        console.error("Error al consultar baneo:", e);
      }
    };
    
    checkBanStatus();
    window.banCheckInterval = setInterval(checkBanStatus, 5000); // Polling cada 5 segundos
  });
  </script>
<?php endif; ?>
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
      <?php if(isset($_SESSION['usuario'])): ?>
        <div class="nav-logout-mobile">
          <a class="nav-link nav-logout-link" href="<?= basePath() ?>/controllers/logoutcontroller.php" data-turbo="false">
            <i class="bi bi-box-arrow-right"></i> Cerrar sesión
          </a>
        </div>
      <?php else: ?>
        <div class="nav-logout-mobile">
          <a class="nav-link" href="<?= basePath() ?>/login">
            <i class="bi bi-person"></i> Iniciar sesión
          </a>
        </div>
      <?php endif; ?>
    </div>
    <div class="nav-actions">
      <!-- LECTURAS OFFLINE LINK -->
      <a href="<?= basePath() ?>/guardados" class="btn btn-outline-secondary" title="Lecturas Sin Conexión" aria-label="Lecturas Sin Conexión" style="position: relative; display: inline-flex; align-items: center; justify-content: center;">
        <i class="bi bi-bookmark-check-fill"></i>
        <span class="offline-badge-count" style="display: none; position: absolute; top: -5px; right: -5px; background: var(--accent, #EF3363); color: #fff; border-radius: 50%; font-size: 0.65rem; font-weight: 800; width: 18px; height: 18px; align-items: center; justify-content: center; line-height: 1;">0</span>
      </a>

      <!-- SWITCH DE TEMA PILL-SHAPED -->
      <div class="theme-switch-pill" title="Cambiar tema" tabindex="0" role="button" aria-label="Alternar modo oscuro o claro">
        <span class="theme-icon active" id="themeIconSun">
          <i class="bi bi-sun-fill"></i>
        </span>
        <span class="theme-icon" id="themeIconMoon">
          <i class="bi bi-moon-stars-fill"></i>
        </span>
      </div>

      <?php if(isset($_SESSION['usuario'])): ?>
        <?php if(($_SESSION['ACL']['noticias']['crear'] ?? false) || ($_SESSION['superadmin'] ?? false)): ?>
          <a href="<?= basePath() ?>/views/crear.php" class="btn btn-outline-secondary" title="Crear Noticia" aria-label="Crear publicación">
            <i class="bi bi-pencil-square"></i>
          </a>
        <?php endif; ?>

        <?php if($_SESSION['superadmin'] ?? false): ?>
          <a href="<?= basePath() ?>/views/admin.php" class="btn-admin-panel" title="Panel de Administración" aria-label="Panel de administración">
            <i class="bi bi-grid-1x2"></i>
          </a>
        <?php endif; ?>

        <div class="user-dropdown">
          <button class="user-avatar-link" id="userDropdownBtn" title="Mi Perfil">
            <span class="user-avatar">
              <?php if($fotoPersonal): ?>
                <img src="<?= imageUrl($fotoPersonal) ?>" alt="Foto personal" style="width:100%; height:100%; object-fit:cover; display:block;">
              <?php elseif($avatarActual): ?>
                <img src="<?= imageUrl($avatarActual) ?>" alt="Avatar" style="width:100%; height:100%; object-fit:cover; display:block;">
              <?php else: ?>
                <i class="bi bi-person"></i>
              <?php endif; ?>
            </span>
          </button>
          <div class="user-dropdown-menu" id="userDropdownMenu">
            <a href="<?= basePath() ?>/perfil" class="dropdown-item">
              <i class="bi bi-person-circle"></i> Mi Perfil
            </a>
            <div class="dropdown-divider"></div>
            <div class="dropdown-label">Categorías</div>
            <?php foreach($categorias as $cat): ?>
              <a href="<?= basePath() ?>/categoria/<?= urlencode($cat['nombre']) ?>" class="dropdown-item">
                <i class="bi bi-tag"></i> <?= htmlspecialchars($cat['nombre']) ?>
              </a>
            <?php endforeach; ?>
            <div class="dropdown-divider"></div>
            <div class="dropdown-label">Enlaces de interés</div>
            <a href="<?= basePath() ?>/nosotros" class="dropdown-item">
              <i class="bi bi-building-fill"></i> Nosotros
            </a>
            <a href="<?= basePath() ?>/terminos" class="dropdown-item">
              <i class="bi bi-file-earmark-text-fill"></i> Términos y Condiciones
            </a>
            <a href="<?= basePath() ?>/privacidad" class="dropdown-item">
              <i class="bi bi-file-lock-fill"></i> Aviso de privacidad
            </a>
            <a href="<?= basePath() ?>/solicitud" class="dropdown-item">
              <i class="bi bi-briefcase-fill"></i> Únete a nuestro equipo
            </a>
            <a href="<?= basePath() ?>/suscripcion" class="dropdown-item">
              <i class="bi bi-bookmark-star-fill"></i> Suscríbete
            </a>
            <a href="<?= basePath() ?>/contactanos" class="dropdown-item">
              <i class="bi bi-envelope-fill"></i> Contáctanos
            </a>
            <div class="dropdown-divider"></div>
            <div class="dropdown-label">Síguenos</div>
            <div class="dropdown-social-links">
              <a href="https://www.facebook.com/TheCatink?locale=es_LA" aria-label="Facebook" target="_blank"><i class="bi bi-facebook"></i></a>
              <a href="https://x.com/The_Catink/" aria-label="Twitter / X" target="_blank"><i class="bi bi-twitter-x"></i></a>
              <a href="https://www.instagram.com/the.catink/" aria-label="Instagram" target="_blank"><i class="bi bi-instagram"></i></a>
              <a href="https://www.youtube.com/@thecatink" aria-label="YouTube" target="_blank"><i class="bi bi-youtube"></i></a>
              <a href="https://www.tiktok.com/@thecatink" aria-label="TikTok" target="_blank"><i class="bi bi-tiktok"></i></a>
            </div>
            <div class="dropdown-divider"></div>
            <a href="<?= basePath() ?>/controllers/logoutcontroller.php" class="dropdown-item dropdown-logout" data-turbo="false">
              <i class="bi bi-box-arrow-right"></i> Cerrar Sesión
            </a>
          </div>
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
<?php if (!empty($_SESSION['flash'])):
  $flash = $_SESSION['flash'];
  unset($_SESSION['flash']); ?>
  <div class="toast-container">
    <div class="toast-msg toast-<?= $flash['tipo'] === 'error' ? 'error' : 'success' ?>">
      <?= htmlspecialchars($flash['texto']) ?>
    </div>
  </div>
  <script>
    setTimeout(() => {
      const t = document.querySelector('.toast-container');
      if (t) t.remove();
    }, 4000);
  </script>
<?php endif; ?>
<!-- Inicio del contenido principal de la página -->
<main class="site-main">