<!-- Fin del contenido principal (admin) -->
</main>
<!-- Contenedor de notificaciones Toast -->
<div id="toastContainer" style="position: fixed; top: 20px; right: 20px; z-index: 9999;"></div>
<!-- Script local para comportamientos generales en admin -->
<script src="<?= basePath() ?>/CSS/admin.js?v=<?= filemtime(__DIR__ . '/../CSS/admin.js') ?>"></script>
<script>
function showToast(message, type = 'success', duration = 3200) {
    const container = document.getElementById('toastContainer');
    const toast = document.createElement('div');
    
    const isError = type === 'error';
    const isInfo = type === 'info';
    const icon = isError ? 'bi-exclamation-octagon-fill' : (isInfo ? 'bi-info-circle-fill' : 'bi-check-circle-fill');
    const bg = isError ? 'linear-gradient(135deg, #ef4444 0%, #dc2626 100%)' : (isInfo ? 'linear-gradient(135deg, #3b82f6 0%, #2563eb 100%)' : 'linear-gradient(135deg, #10b981 0%, #059669 100%)');
    
    toast.style.cssText = `
        background: ${bg};
        color: #ffffff;
        padding: 12px 18px;
        border-radius: 12px;
        margin-bottom: 10px;
        box-shadow: 0 8px 24px rgba(0,0,0,0.2);
        font-size: 0.88rem;
        font-weight: 700;
        animation: slideIn 0.3s ease-in-out;
        min-width: 260px;
        display: flex;
        align-items: center;
        gap: 10px;
    `;
    
    toast.innerHTML = `<i class="bi ${icon}" style="font-size:1.1rem;"></i> <span>${message}</span>`;
    container.appendChild(toast);
    
    setTimeout(() => {
        toast.style.animation = 'slideOut 0.3s ease-in-out';
        setTimeout(() => toast.remove(), 300);
    }, duration);
}

// Modal de confirmación global customizado CatInk (reemplazo elegante de confirm())
window.cnConfirm = function({ title = '¿Estás seguro?', message = '', confirmText = 'Confirmar', cancelText = 'Cancelar', isDanger = true }) {
    return new Promise((resolve) => {
        let modal = document.getElementById('cnConfirmModalGlobal');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'cnConfirmModalGlobal';
            modal.className = 'modal-nativo';
            modal.style.cssText = 'display:none; position:fixed; z-index:99999; left:0; top:0; width:100%; height:100%; overflow:auto; background-color:rgba(0,0,0,0.6); backdrop-filter:blur(5px); justify-content:center; align-items:center;';
            modal.innerHTML = `
                <div class="modal-content-nativo" style="max-width:440px; border-radius:18px; background:var(--card-bg); overflow:hidden; margin:auto; border:1.5px solid ${isDanger ? 'rgba(239,68,68,0.4)' : 'var(--border)'}; box-shadow:0 12px 40px rgba(0,0,0,0.3);">
                    <div id="cnConfirmHeader" style="padding:16px 20px; border-bottom:1px solid var(--border); display:flex; justify-content:space-between; align-items:center; background:${isDanger ? 'linear-gradient(135deg, #ef4444 0%, #dc2626 100%)' : 'var(--card-bg)'}; color:${isDanger ? '#ffffff' : 'var(--text)'};">
                        <h5 id="cnConfirmTitle" class="m-0 font-weight-bold" style="font-weight:800; font-size:1.05rem;"></h5>
                        <span id="cnConfirmClose" style="font-size:24px; font-weight:bold; cursor:pointer; opacity:0.8;">&times;</span>
                    </div>
                    <div style="padding:22px;">
                        <p id="cnConfirmMessage" style="color:var(--text); font-size:0.92rem; margin-bottom:20px; font-weight:600; line-height:1.5;"></p>
                        <div style="display:flex; justify-content:flex-end; gap:10px;">
                            <button type="button" id="cnConfirmBtnCancel" class="btn btn-secondary px-3" style="border-radius:10px; font-weight:700;">Cancelar</button>
                            <button type="button" id="cnConfirmBtnOk" class="btn px-4" style="border-radius:10px; font-weight:800;"></button>
                        </div>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
        }

        const titleEl = document.getElementById('cnConfirmTitle');
        const msgEl = document.getElementById('cnConfirmMessage');
        const btnOk = document.getElementById('cnConfirmBtnOk');
        const btnCancel = document.getElementById('cnConfirmBtnCancel');
        const btnClose = document.getElementById('cnConfirmClose');
        const headerEl = document.getElementById('cnConfirmHeader');

        if (isDanger) {
            headerEl.style.background = 'linear-gradient(135deg, #ef4444 0%, #dc2626 100%)';
            headerEl.style.color = '#ffffff';
            btnOk.className = 'btn btn-danger px-4';
            btnOk.style.boxShadow = '0 4px 14px rgba(239,68,68,0.35)';
            titleEl.innerHTML = `<i class="bi bi-exclamation-triangle-fill me-2"></i> ${title}`;
        } else {
            headerEl.style.background = 'var(--card-bg)';
            headerEl.style.color = 'var(--text)';
            btnOk.className = 'btn btn-accent px-4';
            btnOk.style.boxShadow = '0 4px 14px rgba(239,51,99,0.35)';
            titleEl.innerHTML = `<i class="bi bi-info-circle-fill text-accent me-2"></i> ${title}`;
        }

        msgEl.textContent = message;
        btnOk.textContent = confirmText;
        btnCancel.textContent = cancelText;

        modal.style.display = 'flex';

        function cleanup(result) {
            modal.style.display = 'none';
            btnOk.removeEventListener('click', onOk);
            btnCancel.removeEventListener('click', onCancel);
            btnClose.removeEventListener('click', onCancel);
            resolve(result);
        }

        function onOk() { cleanup(true); }
        function onCancel() { cleanup(false); }

        btnOk.addEventListener('click', onOk);
        btnCancel.addEventListener('click', onCancel);
        btnClose.addEventListener('click', onCancel);
    });
};

// Estilos de animación
(function() {
  const style = document.createElement('style');
  style.textContent = `
      @keyframes slideIn {
          from {
              transform: translateX(400px);
              opacity: 0;
          }
          to {
              transform: translateX(0);
              opacity: 1;
          }
      }
      @keyframes slideOut {
          from {
              transform: translateX(0);
              opacity: 1;
          }
          to {
              transform: translateX(400px);
              opacity: 0;
          }
      }
  `;
  document.head.appendChild(style);
})();

// Detectar parámetros de redirección en la URL para mostrar notificaciones toast automáticamente
document.addEventListener('turbo:load', () => {
    const urlParams = new URLSearchParams(window.location.search);
    const msg = urlParams.get('msg');
    const error = urlParams.get('error');
    let urlChanged = false;
    
    if (msg) {
        let text = '';
        const path = window.location.pathname.toLowerCase();
        const isPage = path.includes('paginas.php');
        const isPub = path.includes('publicidad.php') || path.includes('campanas.php') || path.includes('editarp.php') || path.includes('crearp.php');
        const isUser = path.includes('usuarios.php') || path.includes('editaru.php') || path.includes('crearu.php');

        if (msg === 'actualizado') {
            if (isPage) {
                text = 'Página actualizada correctamente';
            } else if (isPub) {
                text = 'Publicidad actualizada correctamente';
            } else if (isUser) {
                text = 'Usuario actualizado correctamente';
            } else {
                text = 'Noticia actualizada correctamente';
            }
        } else if (msg === 'creado') {
            if (isPage) {
                text = 'Página creada correctamente';
            } else if (isPub) {
                text = 'Publicidad creada correctamente';
            } else if (isUser) {
                text = 'Usuario creado correctamente';
            } else {
                text = 'Noticia creada correctamente';
            }
        } else if (msg === 'eliminado') {
            if (isPage) {
                text = 'Página eliminada correctamente';
            } else if (isPub) {
                text = 'Publicidad eliminada correctamente';
            } else if (isUser) {
                text = 'Usuario eliminado correctamente';
            } else {
                text = 'Noticia eliminada correctamente';
            }
        }

        if (text) {
            showToast(text, 'success');
            urlParams.delete('msg');
            urlChanged = true;
        }
    }
    
    if (error) {
        let text = '';
        if (error === 'no_eliminado') {
            text = 'No se pudo eliminar la noticia';
        } else if (error === 'permisos') {
            text = 'No tienes permisos para realizar esta acción';
        } else if (error === 'pass') {
            text = 'Las contraseñas no coinciden o son incorrectas';
        } else {
            if (error.length > 3) {
                text = decodeURIComponent(error);
            } else {
                text = 'Ocurrió un error al procesar la solicitud';
            }
        }
        if (text) {
            showToast(text, 'error');
            urlParams.delete('error');
            urlChanged = true;
        }
    }

    if (urlChanged) {
        const newSearch = urlParams.toString();
        const newUrl = window.location.pathname + (newSearch ? '?' + newSearch : '') + window.location.hash;
        window.history.replaceState({}, document.title, newUrl);
    }
});
</script>
<!-- Pie de página de administración -->
<footer class="site-footer mt-5">
  <div class="footer-bottom">
    <div class="container text-center">
      <small>© 2026 CatInk. Administración.</small>
    </div>
  </div>
</footer>
</body>
</html>
