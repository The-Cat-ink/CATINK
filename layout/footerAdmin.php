<!-- Fin del contenido principal (admin) -->
</main>
<!-- Contenedor de notificaciones Toast -->
<div id="toastContainer" style="position: fixed; top: 20px; right: 20px; z-index: 9999;"></div>
<!-- Script local para comportamientos generales en admin -->
<script src="<?= basePath() ?>/CSS/admin.js?v=<?= filemtime(__DIR__ . '/../CSS/admin.js') ?>"></script>
<script>
function showToast(message, type = 'success', duration = 3000) {
    const container = document.getElementById('toastContainer');
    const toast = document.createElement('div');
    
    const bgColor = type === 'success' ? '#28a745' : type === 'error' ? '#dc3545' : '#17a2b8';
    const textColor = '#fff';
    
    toast.style.cssText = `
        background-color: ${bgColor};
        color: ${textColor};
        padding: 12px 20px;
        border-radius: 6px;
        margin-bottom: 10px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        font-size: 14px;
        animation: slideIn 0.3s ease-in-out;
        min-width: 250px;
    `;
    
    toast.textContent = message;
    container.appendChild(toast);
    
    setTimeout(() => {
        toast.style.animation = 'slideOut 0.3s ease-in-out';
        setTimeout(() => toast.remove(), 300);
    }, duration);
}

// Estilos de animación
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

// Detectar parámetros de redirección en la URL para mostrar notificaciones toast automáticamente
document.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    const msg = urlParams.get('msg');
    const error = urlParams.get('error');
    let urlChanged = false;
    
    if (msg) {
        let text = '';
        const path = window.location.pathname.toLowerCase();
        const isPage = path.includes('paginas.php');
        const isPub = path.includes('publicidad.php') || path.includes('editarp.php') || path.includes('crearp.php');
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
