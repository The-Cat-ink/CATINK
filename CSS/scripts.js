/*
  Archivo: scripts.js
  Propósito: funcionalidades JavaScript nativas que reemplazan comportamientos de Bootstrap.
  Contiene:
    - soporte para transiciones de página rápidas via Turbo Drive
    - toggle de colapso para el menú (data-bs-toggle="collapse")
    - toggle de tema (lee/guarda en localStorage y aplica data-bs-theme)
    - carrusel mínimo con evento compatible 'slide.bs.carousel'
    - animación de progreso de indicadores (círculos SVG)
    - scroll horizontal de video-carousel
    - toggle de dropdown de usuario
*/

// Variables y funciones globales de control de eventos para evitar duplicados
let lastScroll = 0;

function handleNavbarScroll() {
  const navbar = document.querySelector('.navbar');
  if (!navbar) return;
  const curr = window.scrollY;

  if (curr > lastScroll && curr > 80) {
    navbar.classList.add('nav-hidden');
  } else {
    navbar.classList.remove('nav-hidden');
  }
  lastScroll = curr;
}

function handleDropdownOutsideClick(e) {
  const userDropdownBtn = document.getElementById('userDropdownBtn');
  const userDropdownMenu = document.getElementById('userDropdownMenu');
  if (userDropdownBtn && userDropdownMenu) {
    if (!userDropdownBtn.contains(e.target) && !userDropdownMenu.contains(e.target)) {
      userDropdownMenu.classList.remove('show');
    }
  }
}

function handleDropdownEscape(e) {
  if (e.key === 'Escape') {
    const userDropdownMenu = document.getElementById('userDropdownMenu');
    if (userDropdownMenu) {
      userDropdownMenu.classList.remove('show');
    }
  }
}

// Escuchar evento turbo:load en lugar de DOMContentLoaded
document.addEventListener('turbo:load', function() {
  console.log('Turbo: Página cargada e inicializando scripts.js');

  // Configurar retardo de la barra de progreso de Turbo a 100ms
  if (window.Turbo) {
    window.Turbo.setProgressBarDelay(100);
  }

  // 1. Limpieza de intervalos anteriores para evitar fugas de memoria (Memory Leaks)
  if (window.carouselTimer) {
    clearInterval(window.carouselTimer);
    window.carouselTimer = null;
  }

  // 2. Clickable cards: click en cualquier parte navega a la noticia
  document.querySelectorAll('[data-url]').forEach(card => {
    card.addEventListener('click', function(e) {
      if (e.target.closest('a')) return;
      const url = card.getAttribute('data-url');
      if (url) {
        // Usar navegación de Turbo si está disponible para mantener la fluidez
        if (window.Turbo) {
          window.Turbo.visit(url);
        } else {
          window.location.href = url;
        }
      }
    });
  });

  // 3. Toggle de colapso: busca botones con data-bs-toggle="collapse" y alterna la clase .show
  document.querySelectorAll('[data-bs-toggle="collapse"]').forEach(btn => {
    btn.addEventListener('click', function(e) {
      const targetSelector = btn.getAttribute('data-bs-target');
      if (!targetSelector) return;
      const target = document.querySelector(targetSelector);
      if (!target) return;
      target.classList.toggle('show');
    });
  });

  // 4. Toggle de tema: interruptor pill-shaped
  const themeSwitchPill = document.querySelector('.theme-switch-pill');
  const themeIconSun = document.getElementById('themeIconSun');
  const themeIconMoon = document.getElementById('themeIconMoon');

  function applyTheme(theme) {
    document.documentElement.setAttribute('data-bs-theme', theme);
    if (themeIconSun && themeIconMoon) {
      if (theme === 'dark') {
        themeIconSun.classList.remove('active');
        themeIconMoon.classList.add('active');
      } else {
        themeIconSun.classList.add('active');
        themeIconMoon.classList.remove('active');
      }
    }
  }

  if (themeSwitchPill) {
    themeSwitchPill.addEventListener('click', () => {
      const currentTheme = document.documentElement.getAttribute('data-bs-theme') || 'light';
      const next = currentTheme === 'dark' ? 'light' : 'dark';
      applyTheme(next);
      localStorage.setItem('theme', next);
    });
  }

  const saved = localStorage.getItem('theme') || 'light';
  applyTheme(saved);

  // 5. Scroll del Navbar (auto-hide/show) con remoción de listener anterior
  window.removeEventListener('scroll', handleNavbarScroll);
  const navbar = document.querySelector('.navbar');
  if (navbar) {
    window.addEventListener('scroll', handleNavbarScroll, { passive: true });
  }

  // 6. Carrusel Principal (Home)
  const carousel = document.getElementById('carouselExampleCaptions');
  if (carousel) {
    const items = Array.from(carousel.querySelectorAll('.carousel-item'));
    let current = items.findIndex(i => i.classList.contains('active'));
    if (current < 0) current = 0;
    const interval = parseInt(carousel.getAttribute('data-bs-interval')) || 10000;

    function showSlide(index) {
      if (index < 0) index = items.length - 1;
      if (index >= items.length) index = 0;
      items.forEach((it, idx) => {
        it.classList.toggle('active', idx === index);
      });
      current = index;
      const ev = new CustomEvent('slide.bs.carousel', { detail: { to: index } });
      carousel.dispatchEvent(ev);
    }

    function startAuto() {
      stopAuto();
      window.carouselTimer = setInterval(() => showSlide(current + 1), interval);
    }
    
    function stopAuto() {
      if (window.carouselTimer) {
        clearInterval(window.carouselTimer);
        window.carouselTimer = null;
      }
    }

    document.querySelectorAll('.custom-indicators button').forEach((btn, idx) => {
      btn.addEventListener('click', () => {
        showSlide(idx);
        startAuto();
      });
    });

    showSlide(current);
    startAuto();

    // Animación de progreso de indicadores SVG
    const indicators = document.querySelectorAll('.indicator-avatar circle');
    const duration = interval;
    function startProgress(index) {
      indicators.forEach(circle => {
        circle.style.transition = 'none';
        circle.style.strokeDashoffset = '100';
      });
      if (!indicators[index]) return;
      void indicators[index].offsetWidth;
      indicators[index].style.transition = `stroke-dashoffset ${duration}ms linear`;
      indicators[index].style.strokeDashoffset = '0';
    }

    carousel.addEventListener('slide.bs.carousel', (e) => {
      const to = (e && e.detail && typeof e.detail.to === 'number') ? e.detail.to : 0;
      startProgress(to);
    });
    startProgress(0);
  }

  // 7. Video Carousel Horizontal Scroll
  const videoCarousel = document.querySelector(".video-carousel");
  const nextBtn = document.querySelector(".next-slide");
  const prevBtn = document.querySelector(".prev-slide");
  if (videoCarousel && nextBtn) {
    nextBtn.addEventListener("click", () => {
      videoCarousel.scrollBy({ left: videoCarousel.offsetWidth, behavior: "smooth" });
    });
  }
  if (videoCarousel && prevBtn) {
    prevBtn.addEventListener("click", () => {
      videoCarousel.scrollBy({ left: -videoCarousel.offsetWidth, behavior: "smooth" });
    });
  }

  // 8. User dropdown toggle (Dropdown de Usuario)
  document.removeEventListener('click', handleDropdownOutsideClick);
  document.removeEventListener('keydown', handleDropdownEscape);

  const userDropdownBtn = document.getElementById('userDropdownBtn');
  const userDropdownMenu = document.getElementById('userDropdownMenu');

  if (userDropdownBtn && userDropdownMenu) {
    userDropdownBtn.addEventListener('click', function(e) {
      e.stopPropagation();
      userDropdownMenu.classList.toggle('show');
    });
    document.addEventListener('click', handleDropdownOutsideClick);
    document.addEventListener('keydown', handleDropdownEscape);
  }
});