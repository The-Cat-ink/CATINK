<?php require_once(__DIR__ . "/../views/helpers/urlhelper.php"); ?>
<!-- Banner Flotante de Cookies Premium CatInk -->
<div id="cookie-modal" class="cn-cookie-banner" style="display:none;" aria-label="Aviso de Cookies">
  <div class="cn-cookie-banner-inner">
    <div class="cn-cookie-header">
      <div class="cn-cookie-badge-icon">
        <i class="bi bi-cookie"></i>
      </div>
      <div>
        <h4 class="cn-cookie-title">Privacidad & Cookies en CatInk</h4>
        <p class="cn-cookie-desc">
          Utilizamos cookies para mejorar tu experiencia de lectura, analizar nuestro tráfico y adaptar la publicidad a tus gustos.
        </p>
      </div>
    </div>

    <!-- Panel de Preferencias Desplegable -->
    <div id="cn-cookie-preferences" class="cn-cookie-prefs" style="display:none;">
      <div class="cn-cookie-pref-item">
        <div class="cn-cookie-pref-info">
          <strong><i class="bi bi-shield-check text-success"></i> Esenciales (Técnicas)</strong>
          <span>Requeridas para la sesión, lectura sin conexión (PWA) y seguridad.</span>
        </div>
        <input type="checkbox" checked disabled style="accent-color:var(--accent); width:18px; height:18px;">
      </div>
      <div class="cn-cookie-pref-item">
        <div class="cn-cookie-pref-info">
          <strong><i class="bi bi-bar-chart-line-fill text-primary"></i> Métricas & Analítica</strong>
          <span>Nos ayudan a entender qué noticias son las más leídas y populares.</span>
        </div>
        <input type="checkbox" id="ck-pref-analytics" checked style="accent-color:var(--accent); width:18px; height:18px; cursor:pointer;">
      </div>
      <div class="cn-cookie-pref-item">
        <div class="cn-cookie-pref-info">
          <strong><i class="bi bi-badge-ad-fill text-accent"></i> Publicidad & Embebidos</strong>
          <span>Permite ver reproductores de video (YouTube, X) y anuncios personalizados.</span>
        </div>
        <input type="checkbox" id="ck-pref-marketing" checked style="accent-color:var(--accent); width:18px; height:18px; cursor:pointer;">
      </div>
    </div>

    <div class="cn-cookie-footer">
      <div class="cn-cookie-links">
        <a href="<?= basePath() . '/cookies' ?>"><i class="bi bi-file-earmark-text me-1"></i> Política de Cookies</a>
      </div>
      <div class="cn-cookie-actions">
        <button type="button" class="btn-ck-outline" id="btn-ck-toggle-prefs" onclick="toggleCookiePrefs()">
          <i class="bi bi-sliders me-1"></i> Preferencias
        </button>
        <button type="button" class="btn-ck-secondary" onclick="negarCookies()">
          Solo necesarias
        </button>
        <button type="button" class="btn-ck-primary" onclick="aceptarCookies()">
          <i class="bi bi-check2-circle me-1"></i> Aceptar todas
        </button>
      </div>
    </div>
  </div>
</div>
<!-- Fin del contenido principal -->
</main>
<!-- Script local: reemplaza comportamientos de Bootstrap (colapso, tema, carrusel) -->
<script src="<?= basePath() ?>/CSS/scripts.js?v=<?= filemtime(__DIR__ . '/../CSS/scripts.js') ?>"></script>
<script src="<?= basePath() ?>/CSS/offline-manager.js?v=<?= filemtime(__DIR__ . '/../CSS/offline-manager.js') ?>"></script>
<script async src="https://platform.twitter.com/widgets.js"></script>
<script>
  var basePath = '<?= basePath() ?>';

  // Registrar Service Worker para PWA y soporte offline
  if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
      navigator.serviceWorker.register(basePath + '/sw.js')
        .then(reg => console.log('[ServiceWorker] Registrado con éxito:', reg.scope))
        .catch(err => console.warn('[ServiceWorker] Error al registrar:', err));
    });
  }

  function initSearch(inputId, clearBtnId, searchBtnId, resultsBoxId) {
    const input = document.getElementById(inputId);
    const clearBtn = document.getElementById(clearBtnId);
    const searchBtn = document.getElementById(searchBtnId);
    const resultsBox = document.getElementById(resultsBoxId);

    if (!input) return;

    const form = input.closest('.nav-search');
    const isDesplegable = form && form.classList.contains('buscador-desplegable');

    function performSearch() {
      const q = input.value.trim();
      if (q.length >= 2) {
        window.location.href = basePath + `/buscar/${encodeURIComponent(q)}`;
      }
    }

    input.addEventListener('keypress', function (e) {
      if (e.key === 'Enter') { e.preventDefault(); performSearch(); }
    });

    if (clearBtn) {
      clearBtn.addEventListener('click', function (e) {
        e.preventDefault(); input.value = '';
        if (resultsBox) resultsBox.style.display = 'none';
        input.focus();
      });
    }

    if (searchBtn) {
      searchBtn.addEventListener('click', function (e) {
        e.preventDefault();
        if (isDesplegable) {
          if (!form.classList.contains('open')) {
            form.classList.add('open');
            input.focus();
          } else {
            const q = input.value.trim();
            if (q.length >= 2) {
              performSearch();
            } else {
              form.classList.remove('open');
              if (resultsBox) resultsBox.style.display = 'none';
              input.blur();
            }
          }
        } else {
          performSearch();
        }
      });
    }

    if (resultsBox) {
      let timeout = null;
      input.addEventListener('input', function () {
        const q = input.value.trim();
        clearTimeout(timeout);
        if (q.length < 2) { resultsBox.style.display = 'none'; return; }
        // Mostrar esqueleto de carga inmediatamente
        resultsBox.innerHTML = `
          <div class="search-skeleton-container">
            <div class="search-skeleton-item">
              <div class="skeleton-shimmer skeleton-shimmer-thumb"></div>
              <div class="skeleton-shimmer skeleton-shimmer-text"></div>
            </div>
            <div class="search-skeleton-item">
              <div class="skeleton-shimmer skeleton-shimmer-thumb"></div>
              <div class="skeleton-shimmer skeleton-shimmer-text"></div>
            </div>
            <div class="search-skeleton-item">
              <div class="skeleton-shimmer skeleton-shimmer-thumb"></div>
              <div class="skeleton-shimmer skeleton-shimmer-text"></div>
            </div>
          </div>
        `;
        resultsBox.style.display = 'block';

        timeout = setTimeout(() => {
          fetch(basePath + `/api/search.php?q=` + encodeURIComponent(q))
            .then(res => res.json())
            .then(data => {
              resultsBox.innerHTML = '';
              if (data.length === 0) {
                const div = document.createElement('div');
                div.classList.add('search-item');
                div.style.cursor = 'default';
                div.style.justifyContent = 'center';
                div.style.color = 'var(--muted, #999)';
                div.style.fontSize = '0.9rem';
                div.style.padding = '12px';
                div.innerHTML = `<i class="bi bi-exclamation-circle" style="margin-right: 6px;"></i> No se encontraron resultados`;
                resultsBox.appendChild(div);
              } else {
                data.forEach(item => {
                  const div = document.createElement('div');
                  div.classList.add('search-item');
                  const img = document.createElement('img');
                  img.src = item.imagen;
                  img.alt = "";
                  img.classList.add('img-search');
                  const span = document.createElement('span');
                  span.textContent = item.titulo;
                  div.appendChild(img);
                  div.appendChild(span);
                  div.addEventListener('click', () => { window.location.href = item.url; });
                  resultsBox.appendChild(div);
                });
              }
              resultsBox.style.display = 'block';
            });
        }, 300);
      });
    }
  }

  initSearch('searchInput', 'clearBtn', 'searchBtn', 'searchResults');
  initSearch('searchInputMobile', 'clearBtnMobile', 'searchBtnMobile', 'searchResultsMobile');

  // Registrar el click listener global de búsqueda sólo una vez para evitar fugas de memoria
  if (!window.searchClickBound) {
    window.searchClickBound = true;
    document.addEventListener('click', (e) => {
      const resultsBox = document.getElementById('searchResults');
      const resultsBoxMobile = document.getElementById('searchResultsMobile');
      if (resultsBox && !e.target.closest('.nav-search')) {
        resultsBox.style.display = 'none';
      }
      if (resultsBoxMobile && !e.target.closest('.nav-search')) {
        resultsBoxMobile.style.display = 'none';
      }
      // Cerrar buscadores desplegables si se hace click fuera
      document.querySelectorAll('.buscador-desplegable').forEach(form => {
        if (!form.contains(e.target)) {
          form.classList.remove('open');
        }
      });
    });
  }
</script>
<script>
  function toggleCookiePrefs() {
      const prefs = document.getElementById("cn-cookie-preferences");
      const btn = document.getElementById("btn-ck-toggle-prefs");
      if (prefs) {
          const isHidden = (prefs.style.display === "none");
          prefs.style.display = isHidden ? "flex" : "none";
          if (btn) {
              btn.innerHTML = isHidden ? '<i class="bi bi-chevron-up me-1"></i> Ocultar' : '<i class="bi bi-sliders me-1"></i> Preferencias';
          }
      }
  }

  function handleInstagramAndCookies() {
      if(window.instgrm){
          window.instgrm.Embeds.process();
      }
      const modal = document.getElementById("cookie-modal");
      if (modal) {
          if(document.cookie.includes("cookies_decision=")){
              modal.style.display = "none";
              if(document.cookie.includes("cookies_decision=aceptadas")) cargarCookies();
          } else {
              modal.style.display = "block";
          }
      }
  }
  document.addEventListener("DOMContentLoaded", handleInstagramAndCookies);
  document.addEventListener("turbo:load", handleInstagramAndCookies);

  function aceptarCookies(){
      document.cookie = "cookies_decision=aceptadas; path=/; max-age=" + (60*60*24*365);
      ocultarModal();
      cargarCookies();
      location.reload();
  }

  function negarCookies(){
      document.cookie = "cookies_decision=negadas; path=/; max-age=" + (60*60*24*365);
      ocultarModal();
  }

  function ocultarModal(){
      const modal = document.getElementById("cookie-modal");
      if(modal) {
          modal.style.animation = "cookieBannerSlideUp 0.3s reverse ease-in";
          setTimeout(() => { modal.style.display = "none"; }, 280);
      }
  }

  function cargarCookies(){
      // GOOGLE ADSENSE solo si se aceptó
      if(document.cookie.includes("cookies_decision=aceptadas")){
          if(!document.getElementById("adsense-script")){
              var ads = document.createElement('script');
              ads.id="adsense-script";
              ads.src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js";
              ads.async=true;
              document.body.appendChild(ads);
          }
      }
  }
</script>
<script>
    function updateClass() {
        const isMobile = window.innerWidth <= 768;
        // Cards
        document.querySelectorAll(".cardSpecial").forEach(card => {
            if (isMobile) {
                card.classList.add("card");
            } else {
                card.classList.remove("card");
            }
        });
        // Imágenes
        document.querySelectorAll(".imgCard").forEach(img => {
            if (isMobile) {
                img.classList.add("card-img-left");
                img.classList.remove("card-img-left-rounded");
                img.style.setProperty("max-height", "30px", "important");
            } else {
                img.classList.remove("card-img-left");
                img.classList.add("card-img-left-rounded");
                img.style.removeProperty("max-height");
            }
        });
        // Titulos
        document.querySelectorAll(".linkCard").forEach(title =>{
            if (isMobile) {
                title.classList.remove("title-limit-2");
            } else {
                title.classList.add("title-limit-2");
            }
        })
    }
    // Ejecutar al cargar
    updateClass();
    // Ejecutar al redimensionar y al cargar nuevas vistas en Turbo sin acumular escuchadores
    window.removeEventListener("resize", updateClass);
    window.addEventListener("resize", updateClass);
    document.removeEventListener("turbo:load", updateClass);
    document.addEventListener("turbo:load", updateClass);
</script>
<!-- Pie de página: columnas, enlaces y barra inferior -->
<footer class="site-footer mt-5">
  <div class="container">
    <div class="row g-4 align-items-start">
      <!-- Columna 1: Marca y Redes -->
      <div class="col-lg-4 col-md-4 col-12 mb-4 mb-md-0">
        <img id="logo" src="" alt="CatInk Logo" class="footer-logo mb-3">
        <p class="footer-text mb-3">
          Noticias, anime, videojuegos y cultura digital. Todo lo que te apasiona en un solo lugar.
        </p>
        <div class="social-links">
          <?php 
          require_once(__DIR__ . '/../views/helpers/socialhelper.php');
          $catInkSocials = getCatInkSocials(true);
          foreach ($catInkSocials as $soc): 
              $ic = $soc['icono'];
              $isImg = !empty($soc['icono_img']) || (strpos($ic, 'http') === 0 || strpos($ic, 'data:image') === 0 || strpos($ic, '/') === 0);
          ?>
              <a href="<?= htmlspecialchars($soc['url']) ?>" 
                 target="_blank" 
                 rel="noopener" 
                 aria-label="<?= htmlspecialchars($soc['nombre']) ?>" 
                 class="social-btn" 
                 title="<?= htmlspecialchars($soc['nombre']) ?>"
                 style="--soc-color: <?= htmlspecialchars($soc['color'] ?: '#EF3363') ?>;">
                  <?php if ($isImg): ?>
                      <img src="<?= htmlspecialchars($soc['icono_img'] ?: $ic) ?>" alt="" style="width:18px; height:18px; object-fit:contain;">
                  <?php else: ?>
                      <i class="bi <?= htmlspecialchars($ic) ?>"></i>
                  <?php endif; ?>
              </a>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Columna 2: Enlaces de Interés -->
      <div class="col-lg-4 col-md-4 col-12 mb-4 mb-md-0">
        <h4 class="footer-title">Enlaces de Interés</h4>
        <ul class="footer-links">
          <li><a href="<?= basePath() . '/sobre-nosotros' ?>"><i class="bi bi-info-circle-fill"></i> <span>Nosotros</span></a></li>
          <li><a href="<?= basePath() . '/terminos-condiciones' ?>"><i class="bi bi-file-earmark-text-fill"></i> <span>Términos y Condiciones</span></a></li>
          <li><a href="<?= basePath() . '/privacidad' ?>"><i class="bi bi-shield-lock-fill"></i> <span>Aviso de Privacidad</span></a></li>
          <li><a href="<?= basePath() . '/solicitud' ?>"><i class="bi bi-briefcase-fill"></i> <span>Únete al Equipo</span></a></li>
          <li><a href="<?= basePath() . '/suscripcion' ?>"><i class="bi bi-bell-fill"></i> <span>Suscríbete</span></a></li>
          <li><a href="<?= basePath() . '/contactanos' ?>"><i class="bi bi-envelope-fill"></i> <span>Contáctanos</span></a></li>
        </ul>
      </div>

      <!-- Columna 3: Suscripción Rápida -->
      <div class="col-lg-4 col-md-4 col-12">
        <h4 class="footer-title">Entérate Primero</h4>
        <p class="footer-text mb-3">
          Recibe las noticias más importantes de anime, videojuegos y cultura pop directo en tu correo.
        </p>
        <form action="<?= basePath() . '/suscripcion' ?>" method="GET" class="footer-newsletter-form">
          <div class="footer-input-group">
            <input type="email" name="email" placeholder="Tu correo electrónico..." required class="footer-email-input">
            <button type="submit" class="btn-footer-subscribe" title="Suscribirme">
              <i class="bi bi-send-fill"></i>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Derechos -->
  <div class="footer-bottom mt-4">
    <div class="container d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
      <small>© <?= date('Y') ?> <strong>CatInk</strong>. Todos los derechos reservados.</small>
      <small class="text-muted">Cultura Pop &amp; Entretenimiento Digital</small>
    </div>
  </div>
</footer>
</body>
</html>