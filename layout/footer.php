<?php require_once(__DIR__ . "/../views/helpers/urlhelper.php"); ?>
<!-- Modal de cookies -->
<div id="cookie-modal" class="cookie-modal" style="display:none;">
  <div class="cookie-content">
    <h2>Uso de Cookies</h2>
    <p>Utilizamos cookies para publicidad, análisis y contenido embebido. 
       Puede aceptar o rechazar el uso de cookies según su preferencia.</p>
    <div class="cookie-buttons">
      <button onclick="aceptarCookies()">Aceptar</button>
      <button onclick="negarCookies()">Negar</button>
      <a href="<?= basePath() . '/cookies' ?>" class="leer-mas">Leer más</a>
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
              modal.style.display = "flex";
          }
      }
  }
  document.addEventListener("DOMContentLoaded", handleInstagramAndCookies);
  document.addEventListener("turbo:load", handleInstagramAndCookies);

  function aceptarCookies(){
      document.cookie = "cookies_decision=aceptadas; path=/; max-age=" + (60*60*24*365);
      ocultarModal();
      cargarCookies();
      location.reload(); // para contenido embebido
  }

  function negarCookies(){
      document.cookie = "cookies_decision=negadas; path=/; max-age=" + (60*60*24*365);
      ocultarModal();
  }

  function ocultarModal(){
      const modal = document.getElementById("cookie-modal");
      if(modal) modal.style.display = "none";
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
    <div class="row align-items-start">
      <!-- Logo / descripción -->
      <div class="col-lg-4 col-md-12 mb-4 mb-lg-0">
        <img id="logo" src="" alt="CatInk Logo" class="footer-logo mb-3">
        <p class="footer-text">
          Noticias, anime, videojuegos y cultura digital. Todo lo que te apasiona en un solo lugar.
        </p>
      </div>
      <!-- Páginas hermanas -->
      <div class="col-lg-4 col-md-6 mb-4 mb-lg-0">
        <h4 class="footer-title">Enlaces de Interés</h4>
        <ul class="footer-links">
          <li><a href="<?= basePath() . '/sobre-nosotros' ?>"><i class="bi bi-info-circle-fill"></i> <span>Nosotros</span></a></li>
          <li><a href="<?= basePath() . '/terminos-condiciones' ?>"><i class="bi bi-file-earmark-text-fill"></i> <span>Términos y Condiciones</span></a></li>
          <li><a href="<?= basePath() . '/privacidad' ?>"><i class="bi bi-shield-lock-fill"></i> <span>Aviso de Privacidad</span></a></li>
          <li><a href="<?= basePath() . '/solicitud' ?>"><i class="bi bi-briefcase-fill"></i> <span>Únete al Equipo</span></a></li>
          <li><a href="<?= basePath() . '/suscripcion' ?>" aria-label="Suscríbete"><i class="bi bi-bell-fill"></i> <span>Suscríbete</span></a></li>
          <li><a href="<?= basePath() . '/contactanos' ?>"><i class="bi bi-envelope-fill"></i> <span>Contáctanos</span></a></li>
        </ul>
      </div>
      <!-- Redes sociales -->
      <div class="col-lg-4 col-md-6">
        <h4 class="footer-title">Síguenos</h4>
        <p class="footer-text mb-3">Conéctate con nosotros en nuestras redes oficiales:</p>
        <div class="social-links">
          <a href="https://www.facebook.com/TheCatink?locale=es_LA" aria-label="Facebook" target="_blank" rel="noopener" class="social-btn facebook"><i class="bi bi-facebook"></i></a>
          <a href="https://x.com/The_Catink/" aria-label="Twitter / X" target="_blank" rel="noopener" class="social-btn twitter"><i class="bi bi-twitter-x"></i></a>
          <a href="https://www.instagram.com/the.catink/" aria-label="Instagram" target="_blank" rel="noopener" class="social-btn instagram"><i class="bi bi-instagram"></i></a>
          <a href="https://www.youtube.com/@thecatink" aria-label="YouTube" target="_blank" rel="noopener" class="social-btn youtube"><i class="bi bi-youtube"></i></a>
          <a href="https://www.tiktok.com/@thecatink" aria-label="TikTok" target="_blank" rel="noopener" class="social-btn tiktok"><i class="bi bi-tiktok"></i></a>
        </div>
      </div>
    </div>
  </div>
  <!-- Derechos -->
  <div class="footer-bottom">
    <div class="container text-center">
      <small>
        © <?= date('Y') ?> <strong>CatInk</strong>. Todos los derechos reservados.
      </small>
    </div>
  </div>
</footer>
</body>
</html>