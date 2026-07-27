const CACHE_NAME = 'catink-offline-v4';
const STATIC_ASSETS = [
  '/',
  '/offline.html',
  '/CSS/styles.css',
  '/CSS/scripts.js',
  '/CSS/offline-manager.js',
  '/img/catink-icon.png',
  '/img/logo.png',
  '/catink-icon.ico',
  'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css',
  'https://fonts.googleapis.com/css2?family=Baloo+2:wght@400;500;600;700;800&display=swap'
];

// Instalación: Precargar shell de la aplicación
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME).then(cache => {
      console.log('[ServiceWorker] Precargando shell offline...');
      return cache.addAll(STATIC_ASSETS).catch(err => {
        console.warn('[ServiceWorker] Error precargando algunos recursos:', err);
      });
    }).then(() => self.skipWaiting())
  );
});

// Activación: Limpieza de cachés antiguas
self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(keys => {
      return Promise.all(
        keys.filter(key => key !== CACHE_NAME).map(key => caches.delete(key))
      );
    }).then(() => self.clients.claim())
  );
});

// Intercepción de peticiones de red
self.addEventListener('fetch', event => {
  const request = event.request;
  const url = new URL(request.url);

  // Ignorar peticiones que no sean GET (como POST de comentarios o likes)
  if (request.method !== 'GET') return;

  // EXCLUIR RUTAS DEL PANEL DE ADMINISTRACIÓN, PUBLICIDAD Y CONTROLADORES INTERNOS
  // Esto evita falsos positivos de "Modo Sin Conexión" causados por bloqueadores de anuncios (uBlock, Brave, AdGuard)
  // o por errores de servidor en páginas de administración.
  if (
    url.pathname.includes('/views/') || 
    url.pathname.includes('/controllers/') || 
    url.pathname.includes('/data/') ||
    url.pathname.includes('/layout/') ||
    url.pathname.includes('admin') ||
    url.pathname.includes('publicidad') ||
    url.pathname.includes('campanas') ||
    url.pathname.includes('crearp') ||
    url.pathname.includes('editarp') ||
    url.pathname.includes('verp') ||
    url.pathname.includes('login')
  ) {
    return;
  }

  // Ignorar peticiones a servicios externos no cacheables
  if (url.origin.includes('googlesyndication.com') || url.origin.includes('googletagmanager.com') || url.origin.includes('google-analytics.com')) {
    return;
  }

  // Estrategia para páginas HTML públicas (Navegación del sitio)
  if (request.mode === 'navigate' || request.headers.get('accept')?.includes('text/html')) {
    event.respondWith(
      fetch(request)
        .then(response => {
          // Si responde bien (status 200), clonar en caché para accesibilidad posterior
          if (response && response.status === 200) {
            const responseClone = response.clone();
            caches.open(CACHE_NAME).then(cache => cache.put(request, responseClone));
          }
          return response;
        })
        .catch(async () => {
          // Si falla realmente la red (offline), buscar en caché o entregar fallback /offline.html
          const cachedResponse = await caches.match(request);
          if (cachedResponse) return cachedResponse;
          
          const offlinePage = await caches.match('/offline.html');
          return offlinePage || new Response('<h1>Sin Conexión</h1><p>No tienes conexión a internet para cargar esta página.</p>', {
            headers: { 'Content-Type': 'text/html; charset=utf-8' }
          });
        })
    );
    return;
  }

  // Estrategia Stale-While-Revalidate para recursos estáticos (CSS, JS, imágenes públicas)
  event.respondWith(
    caches.match(request).then(cachedResponse => {
      const fetchPromise = fetch(request).then(networkResponse => {
        if (networkResponse && networkResponse.status === 200) {
          const responseClone = networkResponse.clone();
          caches.open(CACHE_NAME).then(cache => cache.put(request, responseClone));
        }
        return networkResponse;
      }).catch(() => cachedResponse);

      return cachedResponse || fetchPromise;
    })
  );
});
