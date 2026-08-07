/**
 * CatInk - Engine de Lectura Sin Conexión (IndexedDB & Network Status)
 */
window.CatInkOffline = (function() {
  const DB_NAME = 'CatInkOfflineDB';
  const DB_VERSION = 1;
  const STORE_NAME = 'articles';

  let dbPromise = null;

  function initDB() {
    if (dbPromise) return dbPromise;

    dbPromise = new Promise((resolve, reject) => {
      if (!('indexedDB' in window)) {
        console.warn('[CatInkOffline] IndexedDB no soportado.');
        return reject('IndexedDB no soportado');
      }

      const request = indexedDB.open(DB_NAME, DB_VERSION);

      request.onupgradeneeded = function(e) {
        const db = e.target.result;
        if (!db.objectStoreNames.contains(STORE_NAME)) {
          const store = db.createObjectStore(STORE_NAME, { keyPath: 'id' });
          store.createIndex('slug', 'slug', { unique: true });
          store.createIndex('saved_at', 'saved_at', { unique: false });
        }
      };

      request.onsuccess = function(e) {
        resolve(e.target.result);
      };

      request.onerror = function(e) {
        console.error('[CatInkOffline] Error al abrir IndexedDB:', e.target.error);
        reject(e.target.error);
      };
    });

    return dbPromise;
  }

  /**
   * Guardar o actualizar artículo en IndexedDB
   */
  async function saveArticle(article) {
    try {
      const db = await initDB();
      return new Promise((resolve, reject) => {
        const tx = db.transaction(STORE_NAME, 'readwrite');
        const store = tx.objectStore(STORE_NAME);

        const dataToSave = {
          id: parseInt(article.id),
          slug: article.slug,
          titulo: article.titulo,
          descripcion: article.descripcion,
          contenido: article.contenido,
          cover_image: article.cover_image || null,
          autor_nombre: article.autor_nombre || 'CatInk',
          autor_foto: article.autor_foto || null,
          categorias: article.categorias || [],
          fecha_publicacion: article.fecha_publicacion || new Date().toISOString(),
          saved_at: new Date().toISOString()
        };

        const request = store.put(dataToSave);

        request.onsuccess = function() {
          updateBadgeCounters();
          resolve(dataToSave);
        };

        request.onerror = function(e) {
          reject(e.target.error);
        };
      });
    } catch (err) {
      console.error('[CatInkOffline] Error al guardar artículo:', err);
      throw err;
    }
  }

  /**
   * Eliminar artículo por ID
   */
  async function removeArticle(id) {
    try {
      const db = await initDB();
      return new Promise((resolve, reject) => {
        const tx = db.transaction(STORE_NAME, 'readwrite');
        const store = tx.objectStore(STORE_NAME);
        const request = store.delete(parseInt(id));

        request.onsuccess = function() {
          updateBadgeCounters();
          resolve(true);
        };

        request.onerror = function(e) {
          reject(e.target.error);
        };
      });
    } catch (err) {
      console.error('[CatInkOffline] Error al eliminar artículo:', err);
      return false;
    }
  }

  /**
   * Comprobar si un artículo está guardado por ID
   */
  async function isArticleSaved(id) {
    if (!id) return false;
    try {
      const db = await initDB();
      return new Promise((resolve) => {
        const tx = db.transaction(STORE_NAME, 'readonly');
        const store = tx.objectStore(STORE_NAME);
        const request = store.get(parseInt(id));

        request.onsuccess = function() {
          resolve(!!request.result);
        };

        request.onerror = function() {
          resolve(false);
        };
      });
    } catch (err) {
      return false;
    }
  }

  /**
   * Obtener todos los artículos guardados de forma segura
   */
  async function getAllArticles() {
    try {
      const db = await initDB();
      return new Promise((resolve) => {
        const tx = db.transaction(STORE_NAME, 'readonly');
        const store = tx.objectStore(STORE_NAME);
        let request;
        
        if (store.indexNames && store.indexNames.contains('saved_at')) {
          request = store.index('saved_at').getAll();
        } else {
          request = store.getAll();
        }

        request.onsuccess = function() {
          const result = (request.result || []).reverse();
          resolve(result);
        };

        request.onerror = function() {
          resolve([]);
        };
      });
    } catch (err) {
      console.warn('[CatInkOffline] Error al obtener artículos:', err);
      return [];
    }
  }

  /**
   * Obtener un artículo por ID o Slug de forma segura
   */
  async function getArticleByIdOrSlug(idOrSlug) {
    try {
      const db = await initDB();
      return new Promise((resolve) => {
        const tx = db.transaction(STORE_NAME, 'readonly');
        const store = tx.objectStore(STORE_NAME);

        if (!isNaN(idOrSlug) && Number.isInteger(Number(idOrSlug))) {
          const req = store.get(parseInt(idOrSlug));
          req.onsuccess = () => resolve(req.result || null);
          req.onerror = () => resolve(null);
        } else if (store.indexNames && store.indexNames.contains('slug')) {
          const index = store.index('slug');
          const req = index.get(String(idOrSlug));
          req.onsuccess = () => resolve(req.result || null);
          req.onerror = () => resolve(null);
        } else {
          const req = store.getAll();
          req.onsuccess = () => {
            const list = req.result || [];
            resolve(list.find(a => a.slug === String(idOrSlug)) || null);
          };
          req.onerror = () => resolve(null);
        }
      });
    } catch (err) {
      console.warn('[CatInkOffline] Error al obtener artículo:', err);
      return null;
    }
  }

  /**
   * Vaciar toda la base de datos de artículos offline
   */
  async function clearAllArticles() {
    try {
      const db = await initDB();
      return new Promise((resolve, reject) => {
        const tx = db.transaction(STORE_NAME, 'readwrite');
        const store = tx.objectStore(STORE_NAME);
        const request = store.clear();

        request.onsuccess = function() {
          updateBadgeCounters();
          resolve(true);
        };

        request.onerror = function(e) {
          reject(e.target.error);
        };
      });
    } catch (err) {
      console.error('[CatInkOffline] Error al vaciar artículos:', err);
      return false;
    }
  }

  /**
   * Calcular memoria usada por las lecturas offline (en MB)
   */
  async function getStorageEstimateMB() {
    try {
      const articles = await getAllArticles();
      const jsonStr = JSON.stringify(articles);
      const bytes = new Blob([jsonStr]).size;
      return (bytes / (1024 * 1024)).toFixed(2);
    } catch (e) {
      return "0.00";
    }
  }

  /**
   * Actualizar contadores dinámicos en el DOM
   */
  async function updateBadgeCounters() {
    try {
      const articles = await getAllArticles();
      const count = articles.length;
      document.querySelectorAll('.offline-badge-count').forEach(el => {
        el.textContent = count;
        el.style.display = count > 0 ? 'inline-flex' : 'none';
      });
    } catch (e) {
      // Silencioso si falla
    }
  }

  /**
   * Mostrar Toast emergente de estado de conexión
   */
  function showStatusToast(message, isOffline = false) {
    let toast = document.getElementById('catinkNetworkToast');
    if (!toast) {
      toast = document.createElement('div');
      toast.id = 'catinkNetworkToast';
      toast.style.cssText = `
        position: fixed;
        bottom: 24px;
        left: 50%;
        transform: translateX(-50%) translateY(100px);
        background: #1a1a20;
        color: #ffffff;
        padding: 12px 22px;
        border-radius: 30px;
        font-family: 'Baloo 2', sans-serif;
        font-weight: 700;
        font-size: 0.95rem;
        display: flex;
        align-items: center;
        gap: 10px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        border: 1px solid rgba(255,255,255,0.15);
        z-index: 99999;
        transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.4s ease;
        opacity: 0;
      `;
      document.body.appendChild(toast);
    }

    const icon = isOffline
      ? '<i class="bi bi-wifi-off" style="color: #EF3363; font-size: 1.2rem;"></i>'
      : '<i class="bi bi-wifi" style="color: #2ecc71; font-size: 1.2rem;"></i>';

    toast.innerHTML = `${icon} <span>${message}</span>`;
    
    requestAnimationFrame(() => {
      toast.style.transform = 'translateX(-50%) translateY(0)';
      toast.style.opacity = '1';
    });

    setTimeout(() => {
      toast.style.transform = 'translateX(-50%) translateY(100px)';
      toast.style.opacity = '0';
    }, 4000);
  }

  // Inicializar detectores de estado de red
  window.addEventListener('online', () => {
    showStatusToast('Conexión restablecida', false);
  });

  window.addEventListener('offline', () => {
    showStatusToast('Sin conexión a internet. Modo lectura offline activado.', true);
  });

  function triggerReady() {
    // Usar un microtask/timeout para dar tiempo a que los otros
    // listeners de DOMContentLoaded (de cada vista) se registren primero.
    // Así catink-offline-ready siempre llega DESPUÉS de que guardados.php
    // o news.php hayan registrado su listener.
    setTimeout(() => {
      updateBadgeCounters();
      window.dispatchEvent(new CustomEvent('catink-offline-ready'));
    }, 0);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', triggerReady);
  } else {
    triggerReady();
  }

  return {
    saveArticle,
    removeArticle,
    isArticleSaved,
    getAllArticles,
    getArticleByIdOrSlug,
    clearAllArticles,
    getStorageEstimateMB,
    updateBadgeCounters,
    showStatusToast
  };
})();
