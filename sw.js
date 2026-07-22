const CACHE_NAME = 'catink-offline-v1';
const STATIC_ASSETS = [
  './',
  './offline.html',
  './CSS/styles.css',
  './CSS/scripts.js',
  './CSS/offline-manager.js',
  './img/catink-icon.png',
  './img/logo.png',
  './catink-icon.ico',
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

  // Ignorar peticiones a servicios externos no cacheables
  if (url.origin.includes('googlesyndication.com') || url.origin.includes('googletagmanager.com')) {
    return;
  }

  // Estrategia para páginas HTML (Navegación)
  if (request.mode === 'navigate' || request.headers.get('accept')?.includes('text/html')) {
    event.respondWith(
      fetch(request)
        .then(response => {
          // Si responde bien, clonar en cache para accesibilidad posterior
          const responseClone = response.clone();
          caches.open(CACHE_NAME).then(cache => cache.put(request, responseClone));
          return response;
        })
        .catch(async () => {
          // Si falla la red (offline), buscar en cache o entregar fallback offline.html
          const cachedResponse = await caches.match(request);
          if (cachedResponse) return cachedResponse;
          
          const offlinePage = await caches.match('./offline.html');
          return offlinePage || new Response('<h1>Sin Conexión</h1><p>No se pudo cargar la página y no tienes conexión a internet.</p>', {
            headers: { 'Content-Type': 'text/html; charset=utf-8' }
          });
        })
    );
    return;
  }

  // Estrategia Stale-While-Revalidate para recursos estáticos (CSS, JS, imágenes)
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
