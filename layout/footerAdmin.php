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

// Verificar parámetros de URL para mostrar notificaciones
const params = new URLSearchParams(window.location.search);
if (params.has('success')) {
    const successType = params.get('success');
    let message = 'Operación completada';
    
    if (successType === 'correo_enviado') {
        message = 'Correo enviado exitosamente';
    } else if (successType === 'correos_enviados') {
        const count = params.get('count') || '0';
        message = `${count} correo(s) enviado(s) exitosamente`;
    } else if (successType === 'programacion_actualizada') {
        message = 'Programación actualizada correctamente';
    }
    
    showToast(message, 'success');
}
if (params.has('error')) {
    const errorType = params.get('error');
    const messages = {
        'permisos': 'No tienes permisos para realizar esta acción',
        'id': 'ID no proporcionado',
        'id_invalido': 'ID inválido',
        'db': 'Error en la base de datos',
        'no_encontrado': 'Suscriptor no encontrado',
        'sin_noticias': 'No hay noticias para enviar',
        'plantilla': 'Error cargando plantilla',
        'envio': 'Error al enviar el correo'
    };
    showToast(messages[errorType] || 'Ocurrió un error', 'error');
}
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
