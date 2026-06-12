/*
  Archivo: scripts.js
  Propósito: funcionalidades JavaScript nativas que reemplazan comportamientos de Bootstrap.
  Contiene:
    - toggle de colapso para el menú (data-bs-toggle="collapse")
    - toggle de tema (lee/guarda en localStorage y aplica data-bs-theme)
    - carrusel mínimo con evento compatible 'slide.bs.carousel'
    - animación de progreso de indicadores (círculos SVG)
*/
document.addEventListener('DOMContentLoaded', function() {
  // Clickable cards: click en cualquier parte navega a la noticia
  document.querySelectorAll('[data-url]').forEach(card => {
    card.addEventListener('click', function(e) {
      if (e.target.closest('a')) return;
      window.location.href = card.getAttribute('data-url');
    });
  });

  // Toggle de colapso: busca botones con data-bs-toggle="collapse" y alterna la clase .show
  document.querySelectorAll('[data-bs-toggle="collapse"]').forEach(btn => {
    btn.addEventListener('click', function(e) {
      const targetSelector = btn.getAttribute('data-bs-target');
      if (!targetSelector) return;
      const target = document.querySelector(targetSelector);
      if (!target) return;
      target.classList.toggle('show');
    });
  });


  // Toggle de tema: interruptor pill-shaped
  const themeSwitchPill = document.querySelector('.theme-switch-pill');
  const themeIconSun = document.getElementById('themeIconSun');
  const themeIconMoon = document.getElementById('themeIconMoon');

  function applyTheme(theme) {
    // Aplica el atributo en el elemento <html> para que CSS use las variables
    document.documentElement.setAttribute('data-bs-theme', theme);
    // Actualiza el estado del switch pill-shaped
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

  // Auto-hide navbar on scroll down, show on scroll up + transparent hero mode on index page
  const navbar = document.querySelector('.navbar');
  const isIndexPage = document.querySelector('.carousel-wrapper') !== null;

  if (navbar) {
    // Si estamos en la home y al tope, iniciar transparente
    if (isIndexPage && window.scrollY < 50) {
      navbar.classList.add('navbar-hero');
    }

    let lastScroll = window.scrollY;
    window.addEventListener('scroll', () => {
      const curr = window.scrollY;

      // Toggle transparente al inicio
      if (isIndexPage) {
        if (curr > 50) {
          navbar.classList.remove('navbar-hero');
        } else {
          navbar.classList.add('navbar-hero');
        }
      }

      if (curr > lastScroll && curr > 80) {
        navbar.classList.add('nav-hidden');
      } else {
        navbar.classList.remove('nav-hidden');
      }
      lastScroll = curr;
    }, { passive: true });
  }

  // Carrusel mínimo: mantiene las slides en DOM y alterna la clase .active
  const carousel = document.getElementById('carouselExampleCaptions');
  if (carousel) {
    const items = Array.from(carousel.querySelectorAll('.carousel-item'));
    console.log('Carrusel encontrado, items:', items.length);
    let current = items.findIndex(i => i.classList.contains('active'));
    if (current < 0) current = 0;
    const interval = parseInt(carousel.getAttribute('data-bs-interval')) || 10000;
    let timer = null;

    // Muestra la slide indicada y dispara un evento compatible con Bootstrap
    function showSlide(index) {
      if (index < 0) index = items.length - 1;
      if (index >= items.length) index = 0;
      console.log('Mostrando slide:', index);
      items.forEach((it, idx) => {
        it.classList.toggle('active', idx === index);
      });
      current = index;
      const ev = new CustomEvent('slide.bs.carousel', { detail: { to: index } });
      carousel.dispatchEvent(ev);
    }

    function startAuto() {
      stopAuto();
      console.log('Iniciando auto-play con intervalo:', interval);
      timer = setInterval(() => showSlide(current + 1), interval);
    }
    function stopAuto() { if (timer) { clearInterval(timer); timer = null; } }

    // Manejo de indicadores personalizados: al hacer click se muestra la slide correspondiente
    document.querySelectorAll('.custom-indicators button').forEach((btn, idx) => {
      btn.addEventListener('click', () => {
        showSlide(idx);
        startAuto();
      });
    });

    // Inicia carrusel automático
    showSlide(current);
    startAuto();
  } else {
    console.log('Carrusel NO encontrado');
  }

  // Animación de progreso de indicadores: dibuja stroke-dashoffset en los círculos SVG
  const indicators = document.querySelectorAll('.indicator-avatar circle');
  const duration = parseInt(carousel?.getAttribute('data-bs-interval')) || 7000;
  function startProgress(index) {
    indicators.forEach(circle => {
      circle.style.transition = 'none';
      circle.style.strokeDashoffset = '100';
    });
    if (!indicators[index]) return;
    void indicators[index].offsetWidth; // forzar reflow para reiniciar transición
    indicators[index].style.transition = `stroke-dashoffset ${duration}ms linear`;
    indicators[index].style.strokeDashoffset = '0';
  }

  // Conecta el evento del carrusel con la animación de progreso
  if (carousel) {
    carousel.addEventListener('slide.bs.carousel', (e) => {
      const to = (e && e.detail && typeof e.detail.to === 'number') ? e.detail.to : 0;
      startProgress(to);
    });
    // Inicio inicial del progreso
    startProgress(0);
  }
});
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

// User dropdown toggle
const userDropdownBtn = document.getElementById('userDropdownBtn');
const userDropdownMenu = document.getElementById('userDropdownMenu');

if (userDropdownBtn && userDropdownMenu) {
  userDropdownBtn.addEventListener('click', function(e) {
    e.stopPropagation();
    userDropdownMenu.classList.toggle('show');
  });

  // Cerrar menú al hacer click fuera
  document.addEventListener('click', function(e) {
    if (!userDropdownBtn.contains(e.target) && !userDropdownMenu.contains(e.target)) {
      userDropdownMenu.classList.remove('show');
    }
  });

  // Cerrar menú al presionar Escape
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
      userDropdownMenu.classList.remove('show');
    }
  });
}