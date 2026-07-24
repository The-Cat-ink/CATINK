<?php
if(session_status() === PHP_SESSION_NONE){
    session_start();
}
$pageTitle = "Lecturas Sin Conexión - Mis Guardados";
$pageDescription = "Accede a tus noticias, artículos y novedades de anime guardados para leer sin conexión a internet.";
include("./../layout/header.php");
?>

<style>
.offline-library-header {
  background: linear-gradient(135deg, rgba(239, 51, 99, 0.1) 0%, rgba(18, 18, 22, 0.95) 100%);
  border: 1px solid var(--border, rgba(255, 255, 255, 0.1));
  border-radius: 16px;
  padding: 30px 24px;
  margin-bottom: 30px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 20px;
}
.offline-library-title {
  display: flex;
  align-items: center;
  gap: 14px;
}
.offline-library-title i {
  font-size: 2.2rem;
  color: var(--accent, #EF3363);
}
.offline-library-title h1 {
  margin: 0;
  font-size: 1.8rem;
  font-weight: 800;
}
.offline-library-stats {
  display: flex;
  align-items: center;
  gap: 16px;
  background: rgba(0, 0, 0, 0.3);
  padding: 10px 18px;
  border-radius: 12px;
  border: 1px solid var(--border, rgba(255, 255, 255, 0.08));
}
.offline-stat-item {
  display: flex;
  align-items: center;
  gap: 8px;
  font-weight: 700;
  font-size: 0.9rem;
}
.offline-stat-item i {
  color: var(--accent, #EF3363);
}
.offline-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 24px;
  margin-bottom: 40px;
}
.offline-card {
  background: var(--card-bg, #1e1e24);
  border: 1px solid var(--border, rgba(255, 255, 255, 0.1));
  border-radius: 14px;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.offline-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 12px 24px rgba(0,0,0,0.3);
}
.offline-card-img {
  width: 100%;
  height: 180px;
  object-fit: cover;
  background: #25252d;
}
.offline-card-body {
  padding: 18px;
  display: flex;
  flex-direction: column;
  flex: 1;
}
.offline-card-tag {
  align-self: flex-start;
  background: rgba(239, 51, 99, 0.15);
  color: var(--accent, #EF3363);
  font-size: 0.75rem;
  font-weight: 800;
  padding: 4px 10px;
  border-radius: 6px;
  text-transform: uppercase;
  margin-bottom: 10px;
}
.offline-card-title {
  font-size: 1.15rem;
  font-weight: 700;
  line-height: 1.3;
  margin-bottom: 8px;
  color: var(--text, #fff);
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
.offline-card-desc {
  font-size: 0.88rem;
  color: var(--muted, #888);
  line-height: 1.4;
  margin-bottom: 16px;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
.offline-card-footer {
  margin-top: auto;
  padding-top: 14px;
  border-top: 1px solid var(--border, rgba(255, 255, 255, 0.08));
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.offline-btn-read {
  background: var(--accent, #EF3363);
  color: #ffffff;
  padding: 8px 16px;
  border-radius: 8px;
  font-weight: 700;
  font-size: 0.85rem;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  border: none;
  cursor: pointer;
}
.offline-btn-delete {
  background: transparent;
  color: var(--muted, #888);
  border: 1px solid var(--border, rgba(255, 255, 255, 0.15));
  padding: 8px 12px;
  border-radius: 8px;
  font-size: 0.85rem;
  cursor: pointer;
  transition: all 0.2s ease;
}
.offline-btn-delete:hover {
  color: #ef3363;
  border-color: #ef3363;
  background: rgba(239, 51, 99, 0.1);
}
.offline-empty-state {
  text-align: center;
  padding: 60px 20px;
  background: var(--card-bg, #1e1e24);
  border-radius: 16px;
  border: 1px dashed var(--border, rgba(255,255,255,0.15));
  margin: 20px 0 40px;
}
.offline-empty-state i {
  font-size: 3.5rem;
  color: var(--muted, #888);
  margin-bottom: 16px;
  opacity: 0.6;
}
</style>

<div class="container" style="margin-top: 30px; margin-bottom: 50px;">
  <!-- Header de Biblioteca Offline -->
  <div class="offline-library-header">
    <div class="offline-library-title">
      <i class="bi bi-bookmark-heart-fill"></i>
      <div>
        <h1>Lecturas Sin Conexión</h1>
        <p style="margin: 0; color: var(--muted); font-size: 0.9rem;">Noticias y publicaciones guardadas en tu dispositivo</p>
      </div>
    </div>

    <div class="offline-library-stats">
      <div class="offline-stat-item">
        <i class="bi bi-file-text-fill"></i>
        <span id="statArticleCount">0 guardados</span>
      </div>
      <span style="opacity: 0.3;">|</span>
      <div class="offline-stat-item">
        <i class="bi bi-hdd-fill"></i>
        <span id="statStorageSize">0.00 MB</span>
      </div>
      <button id="btnClearAllOffline" style="margin-left: 10px; background: transparent; color: var(--muted); border: none; cursor: pointer; font-size: 0.85rem; font-weight: 700;" title="Vaciar todas las lecturas">
        <i class="bi bi-trash"></i> Vaciar
      </button>
    </div>
  </div>

  <!-- Vista Lectura Modal si hay parámetro slug -->
  <div id="offlineReaderView" style="display: none; background: var(--card-bg, #1a1a20); border: 1px solid var(--border, rgba(255,255,255,0.1)); border-radius: 16px; padding: 30px; margin-bottom: 40px;">
    <button id="btnCloseReader" style="background: rgba(255,255,255,0.08); color: var(--text); border: 1px solid var(--border); padding: 8px 16px; border-radius: 8px; font-weight: 700; cursor: pointer; margin-bottom: 20px; display: inline-flex; align-items: center; gap: 8px;">
      <i class="bi bi-arrow-left"></i> Volver a mis guardados
    </button>
    <div id="offlineReaderContent"></div>
  </div>

  <!-- Rejilla de Tarjetas -->
  <div id="offlineArticlesContainer">
    <div class="offline-empty-state">
      <i class="bi bi-arrow-repeat spin"></i>
      <p>Cargando lecturas guardadas...</p>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const container = document.getElementById('offlineArticlesContainer');
  const readerView = document.getElementById('offlineReaderView');
  const readerContent = document.getElementById('offlineReaderContent');
  const btnCloseReader = document.getElementById('btnCloseReader');
  const statCount = document.getElementById('statArticleCount');
  const statSize = document.getElementById('statStorageSize');
  const btnClearAll = document.getElementById('btnClearAllOffline');

  async function renderLibrary() {
    try {
      if (!window.CatInkOffline) {
        throw new Error("CatInkOffline no disponible");
      }

      const articles = await CatInkOffline.getAllArticles();
      const mb = await CatInkOffline.getStorageEstimateMB();
      
      if (statCount) statCount.textContent = `${articles.length} guardados`;
      if (statSize) statSize.textContent = `${mb} MB`;

      if (!articles || articles.length === 0) {
        container.innerHTML = `
          <div class="offline-empty-state">
            <i class="bi bi-bookmark-plus" style="font-size: 3.2rem; color: var(--accent, #EF3363); display: block; margin-bottom: 12px;"></i>
            <h2 style="font-weight: 800; font-size: 1.4rem; margin-bottom: 8px;">Aún no tienes lecturas guardadas</h2>
            <p style="color: var(--muted); max-width: 480px; margin: 0 auto 20px; font-size: 0.92rem; line-height: 1.5;">
              Guarda tus noticias favoritas presionando el botón <strong>«Guardar sin conexión»</strong> en cualquier artículo para leerlas sin necesidad de internet.
            </p>
            <a href="${typeof basePath !== 'undefined' ? basePath : ''}/" class="btn btn-accent" style="display: inline-flex; align-items: center; gap: 8px; font-weight: 800; padding: 10px 22px; border-radius: 12px; color: #fff; background: var(--accent, #EF3363); text-decoration: none; box-shadow: 0 4px 15px rgba(239,51,99,0.3);">
              <i class="bi bi-newspaper"></i> Explorar Noticias
            </a>
          </div>`;
        return;
      }

      let html = '<div class="offline-grid">';
      articles.forEach(art => {
        const cover = art.cover_image || `${typeof basePath !== 'undefined' ? basePath : ''}/img/placeholder.svg`;
        const cat = Array.isArray(art.categorias) && art.categorias.length ? art.categorias[0] : 'Noticia';
        const dateStr = art.fecha_publicacion ? new Date(art.fecha_publicacion).toLocaleDateString() : '';

        html += `
          <div class="offline-card" data-id="${art.id}">
            <img src="${cover}" alt="${art.titulo}" class="offline-card-img" onerror="this.src='${typeof basePath !== 'undefined' ? basePath : ''}/img/placeholder.svg'">
            <div class="offline-card-body">
              <span class="offline-card-tag">${cat}</span>
              <h3 class="offline-card-title">${art.titulo}</h3>
              <p class="offline-card-desc">${art.descripcion || ''}</p>
              <div class="offline-card-footer">
                <button class="offline-btn-read" data-id="${art.id}">
                  <i class="bi bi-book-half"></i> Leer Offline
                </button>
                <button class="offline-btn-delete" data-id="${art.id}" title="Eliminar de mi dispositivo">
                  <i class="bi bi-trash"></i>
                </button>
              </div>
            </div>
          </div>
        `;
      });
      html += '</div>';
      container.innerHTML = html;

      // Event listeners para leer
      container.querySelectorAll('.offline-btn-read').forEach(btn => {
        btn.addEventListener('click', function() {
          const id = this.dataset.id;
          openReader(id);
        });
      });

      // Event listeners para eliminar
      container.querySelectorAll('.offline-btn-delete').forEach(btn => {
        btn.addEventListener('click', async function() {
          const id = this.dataset.id;
          if (confirm('¿Deseas eliminar este artículo de tus lecturas offline?')) {
            await CatInkOffline.removeArticle(id);
            renderLibrary();
          }
        });
      });
    } catch (err) {
      console.warn('[CatInkOffline] renderLibrary error:', err);
      if (statCount) statCount.textContent = `0 guardados`;
      if (statSize) statSize.textContent = `0.00 MB`;
      container.innerHTML = `
        <div class="offline-empty-state">
          <i class="bi bi-bookmark-plus" style="font-size: 3.2rem; color: var(--accent, #EF3363); display: block; margin-bottom: 12px;"></i>
          <h2 style="font-weight: 800; font-size: 1.4rem; margin-bottom: 8px;">Aún no tienes lecturas guardadas</h2>
          <p style="color: var(--muted); max-width: 480px; margin: 0 auto 20px; font-size: 0.92rem; line-height: 1.5;">
            Guarda tus noticias favoritas presionando el botón <strong>«Guardar sin conexión»</strong> en cualquier artículo para leerlas sin necesidad de internet.
          </p>
          <a href="${typeof basePath !== 'undefined' ? basePath : ''}/" class="btn btn-accent" style="display: inline-flex; align-items: center; gap: 8px; font-weight: 800; padding: 10px 22px; border-radius: 12px; color: #fff; background: var(--accent, #EF3363); text-decoration: none; box-shadow: 0 4px 15px rgba(239,51,99,0.3);">
            <i class="bi bi-newspaper"></i> Explorar Noticias
          </a>
        </div>`;
    }
  }

  function openReader(id) {
    if (!window.CatInkOffline) return;
    CatInkOffline.getArticleByIdOrSlug(id).then(art => {
      if (!art) return;
      
      const cover = art.cover_image ? `<img src="${art.cover_image}" style="width: 100%; max-height: 400px; object-fit: cover; border-radius: 12px; margin-bottom: 20px;">` : '';
      const cats = Array.isArray(art.categorias) ? art.categorias.map(c => `<span class="news-tag">${c}</span>`).join(' ') : '';
      
      readerContent.innerHTML = `
        <article class="noticia-offline-full">
          ${cover}
          <div style="margin-bottom: 12px;">${cats}</div>
          <h1 style="font-size: 2rem; font-weight: 800; margin-bottom: 16px;">${art.titulo}</h1>
          <p style="font-size: 1.1rem; color: var(--muted); line-height: 1.5; margin-bottom: 20px; border-left: 3px solid var(--accent); padding-left: 14px;">${art.descripcion}</p>
          <div style="display: flex; gap: 10px; font-size: 0.9rem; color: var(--muted); margin-bottom: 30px;">
            <span>Por <strong>${art.autor_nombre}</strong></span> &bull; <span>${art.fecha_publicacion}</span>
          </div>
          <hr style="border-color: var(--border); margin-bottom: 30px;">
          <div class="contenido-noticia" style="font-size: 1.05rem; line-height: 1.7;">
            ${art.contenido}
          </div>
        </article>
      `;

      container.style.display = 'none';
      readerView.style.display = 'block';
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
  }

  btnCloseReader?.addEventListener('click', () => {
    readerView.style.display = 'none';
    container.style.display = 'block';
  });

  btnClearAll?.addEventListener('click', async () => {
    if (confirm('¿Vaciar todas tus noticias guardadas offline?')) {
      if (window.CatInkOffline) {
        await CatInkOffline.clearAllArticles();
      }
      renderLibrary();
    }
  });

  renderLibrary();
});
</script>

<?php include("./../layout/footer.php"); ?>
