/*
  Archivo: admin.js
  Propósito: Lógica exclusiva del panel de administración
  Incluye:
  - Previsualización de imágenes
  - Cropper.js (imagen principal, galería y editor Quill)
  - Editor Quill
  - Manejo de estado / programación de publicaciones
  - Validaciones defensivas (no rompe si un elemento no existe)
*/
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
  // Toggle de tema: interruptor con id 'themeToggle'
  const themeToggle = document.getElementById('themeToggle');
  function applyTheme(theme) {
    document.documentElement.setAttribute('data-bs-theme', theme);
    if (themeToggle) {
      themeToggle.checked = theme === 'dark';
    }
  }
  if (themeToggle) {
    themeToggle.addEventListener('change', () => {
      const next = themeToggle.checked ? 'dark' : 'light';
      applyTheme(next);
      localStorage.setItem('theme', next);
    });
  }
  const saved = localStorage.getItem('theme') || 'light';
  applyTheme(saved);
  // Toggle Sidebar (Mobile)
  const sidebarToggle = document.getElementById('sidebarToggle');
  const sidebar = document.querySelector('.sidebar');
   const sidebarBackdrop = document.getElementById('sidebarBackdrop');
  const setSidebarState = (isOpen) => {
    sidebar.classList.toggle('active', isOpen);
    sidebarBackdrop?.classList.toggle('active', isOpen);
    document.body.classList.toggle('sidebar-open', isOpen);
  };
  if (sidebarToggle && sidebar) {
    sidebarToggle.addEventListener('click', () => {
      setSidebarState(!sidebar.classList.contains('active'));
    });
    sidebarBackdrop?.addEventListener('click', () => setSidebarState(false));
    // Close sidebar when clicking outside (optional but good for UX)
    document.addEventListener('click', (e) => {
        if (window.innerWidth < 768 && 
            sidebar.classList.contains('active') && 
            !sidebar.contains(e.target) && 
            e.target !== sidebarToggle &&
            !sidebarToggle.contains(e.target)) {
            setSidebarState(false);
        }
    });
    window.addEventListener('resize', () => {
      if (window.innerWidth >= 768) {
        setSidebarState(false);
      }
    });
  }
/* ===============================
   PREVISUALIZACIÓN DE IMÁGENES
================================ */
(() => {
  const input = document.getElementById('imagenCorreo');
  const preview = document.getElementById('preview');
  if (!input || !preview) return;
  let archivosTemporales;
  try {
    archivosTemporales = new DataTransfer();
  } catch {
    archivosTemporales = { files: [], items: { add(){} } };
  }
  input.addEventListener('change', () => {
    Array.from(input.files).forEach(file => {
      // evitar duplicados
      for (let i = 0; i < archivosTemporales.files.length; i++) {
        if (archivosTemporales.files[i].name === file.name) return;
      }
      archivosTemporales.items.add(file);
      const reader = new FileReader();
      reader.onload = e => {
        const div = document.createElement('div');
        div.classList.add('preview-item');
        div.innerHTML = `<img src="${e.target.result}"><span>${file.name}</span>`;
        preview.appendChild(div);
      };
      reader.readAsDataURL(file);
    });
    input.files = archivosTemporales.files;
    input.name = "imagenCorreo";
  });
})();
/* ===============================
   PREVISUALIZACIÓN DE VIDEO
================================ */
(() => {
  const videoInput = document.getElementById('video_url');
  const videoPreview = document.getElementById('videoPreview');
  if (!videoInput || !videoPreview) return;
  videoInput.addEventListener('input', () => {
    const url = videoInput.value.trim();
    videoPreview.innerHTML = '';
    if (!url) return;
    // YouTube
    if (url.includes('youtube.com') || url.includes('youtu.be')) {
      const id = url.includes('youtu.be')
        ? url.split('/').pop()
        : new URL(url).searchParams.get('v');
      if (id) {
        videoPreview.innerHTML =
          `<iframe src="https://www.youtube.com/embed/${id}" allowfullscreen></iframe>`;
      }
      return;
    }
    // Vimeo
    if (url.includes('vimeo.com')) {
      const id = url.split('/').pop();
      videoPreview.innerHTML =
        `<iframe src="https://player.vimeo.com/video/${id}" allowfullscreen></iframe>`;
      return;
    }
    // Video directo
    if (url.match(/\.(mp4|webm|ogg)$/)) {
      videoPreview.innerHTML =
        `<video controls><source src="${url}"></video>`;
      return;
    }
    videoPreview.innerHTML = `<p class="error">No se pudo previsualizar el video</p>`;
  });
})();
/* ===============================
   CROP IMAGEN PRINCIPAL (GALERÍA)
================================ */
(() => {
  // Configuración de los 3 croppers independientes
  const cropConfigs = [
    {
      num: 1,
      name: 'Original',
      ratio: NaN, // Sin ratio fijo
      minWidth: 1600,
      inputId: 'imageInputCrop1',
      imgId: 'cropperImage1',
      previewId: 'preview1',
      confirmBtnSelector: '[data-crop="1"].crop-btn-confirm',
      resetBtnSelector: '[data-crop="1"].crop-btn-reset'
    },
    {
      num: 2,
      name: 'Banner',
      ratio: 21 / 6,
      minWidth: 1920,
      inputId: 'imageInputCrop2',
      imgId: 'cropperImage2',
      previewId: 'preview2',
      confirmBtnSelector: '[data-crop="2"].crop-btn-confirm',
      resetBtnSelector: '[data-crop="2"].crop-btn-reset'
    },
    {
      num: 3,
      name: 'Miniatura',
      ratio: 16 / 9,
      minWidth: 800,
      inputId: 'imageInputCrop3',
      imgId: 'cropperImage3',
      previewId: 'preview3',
      confirmBtnSelector: '[data-crop="3"].crop-btn-confirm',
      resetBtnSelector: '[data-crop="3"].crop-btn-reset'
    }
  ];

  // Objeto para almacenar los croppers
  const croppers = {};
  
  // Detectar si estamos en editar o crear
  const isEditForm = !!document.getElementById('formEdicion');
  const isCreateForm = !!document.getElementById('formPublicacion');

  // Inicializar cada cropper
  cropConfigs.forEach(config => {
    const input = document.getElementById(config.inputId);
    const img = document.getElementById(config.imgId);
    const preview = document.getElementById(config.previewId);
    const confirmBtn = document.querySelector(config.confirmBtnSelector);
    const resetBtn = document.querySelector(config.resetBtnSelector);
    const hiddenInput = document.getElementById(`crop${config.num}`);
    const cropperContainer = img?.closest('.cropper-container');
    const cropActions = confirmBtn?.closest('.crop-actions');
    const imageSection = input?.closest('.crop-image-section');

    if (!input || !img || !preview) return;

    // En editar, ocultar cropper-container por defecto
    if (isEditForm && cropperContainer) {
      cropperContainer.style.display = 'none';
    }

    // Cargar imagen
    input.addEventListener('change', e => {
      const file = e.target.files[0];
      if (!file) return;

      const reader = new FileReader();
      reader.onload = () => {
        img.src = reader.result;
        
        // Mostrar cropper-container
        if (cropperContainer) cropperContainer.style.display = 'block';
        
        // Destruir cropper anterior si existe
        if (croppers[config.num]) {
          croppers[config.num].destroy();
        }

        // Crear nuevo cropper
        croppers[config.num] = new Cropper(img, {
          viewMode: 0,          // permite mover la imagen libremente
          autoCropArea: 0.9,    // el recuadro no ocupa el 100% para poder ver qué hay alrededor
          aspectRatio: config.ratio,
          cropBoxResizable: true,  // también puede redimensionarse
          dragMode: 'move',
          responsive: true,
          guides: true,
          background: false
        });
      };
      reader.readAsDataURL(file);
    });

    // Confirmar recorte
    confirmBtn?.addEventListener('click', () => {
      const cropper = croppers[config.num];
      if (!cropper) {
        alert(`Por favor, carga una imagen primero para ${config.name}`);
        return;
      }

      const cropData = cropper.getData();
      const imageData = cropper.getImageData();

      const naturalWidth = imageData.naturalWidth;
      const naturalHeight = imageData.naturalHeight;

      if (config.minWidth && cropData.width < config.minWidth) {
        alert(`${config.name}: el recorte es menor a ${config.minWidth}px de ancho. La imagen podría verse pixelada en las vistas grandes.`);
      }

      let targetWidth = cropData.width;
      let targetHeight = cropData.height;

      if (config.ratio && Number.isFinite(config.ratio)) {
        targetWidth = Math.min(targetWidth, config.minWidth || targetWidth, naturalWidth);
        targetHeight = targetWidth / config.ratio;
        if (targetHeight > naturalHeight) {
          targetHeight = naturalHeight;
          targetWidth = targetHeight * config.ratio;
        }
      } else {
        if (config.minWidth) {
          targetWidth = Math.min(targetWidth, config.minWidth, naturalWidth);
        } else {
          targetWidth = Math.min(targetWidth, naturalWidth);
        }
        targetHeight = Math.min(targetHeight, naturalHeight);
      }

      targetWidth = Math.max(1, Math.round(targetWidth));
      targetHeight = Math.max(1, Math.round(targetHeight));

      const canvas = cropper.getCroppedCanvas({
        width: targetWidth,
        height: targetHeight,
        imageSmoothingEnabled: true,
        imageSmoothingQuality: 'high'
      });

      // Usar PNG sin compresión para mantener calidad máxima
      const dataUrl = canvas.toDataURL('image/png');

      // Guardar en input hidden
      if (hiddenInput) {
        hiddenInput.value = dataUrl;
      }

      // Mostrar preview
      preview.innerHTML = `<img src="${dataUrl}" style="width: auto; max-height:150px; object-fit:contain;">`;
      
      // Ocultar solo el cropper-container (pero mantener visible los botones)
      if (cropperContainer) cropperContainer.style.display = 'none';
    });

    // Reset
    resetBtn?.addEventListener('click', () => {
      input.value = '';
      preview.innerHTML = '';
      if (hiddenInput) {
        hiddenInput.value = '';
      }
      if (croppers[config.num]) {
        croppers[config.num].destroy();
        croppers[config.num] = null;
        img.src = '';
      }
      
      // Mostrar cropper-container y crop-actions nuevamente
      if (cropperContainer) cropperContainer.style.display = 'block';
    });
  });
})();
/* ===============================
   EDITOR CKEDITOR 5
================================ */
const editorElement = document.getElementById('editor');
let editor = null;

if (editorElement) {
  // Adaptador de imágenes base64 para CKEditor 5
  class Base64UploadAdapter {
    constructor(loader) {
      this.loader = loader;
    }
    upload() {
      return this.loader.file
        .then(file => new Promise((resolve, reject) => {
          const reader = new FileReader();
          reader.onload = () => {
            resolve({ default: reader.result });
          };
          reader.onerror = error => reject(error);
          reader.readAsDataURL(file);
        }));
    }
    abort() {}
  }

  function Base64UploadAdapterPlugin(editorInstance) {
    editorInstance.plugins.get('FileRepository').createUploadAdapter = (loader) => {
      return new Base64UploadAdapter(loader);
    };
  }

  // Inicializar DecoupledEditor
  DecoupledEditor
    .create(editorElement, {
      extraPlugins: [Base64UploadAdapterPlugin],
      placeholder: 'Comienza a escribir tu nota aquí...',
      language: 'es',
      toolbar: {
        items: [
          'heading',
          '|',
          'bold', 'italic', 'underline', 'strikethrough',
          '|',
          'fontSize', 'fontColor', 'fontBackgroundColor',
          '|',
          'alignment',
          '|',
          'numberedList', 'bulletedList',
          '|',
          'outdent', 'indent',
          '|',
          'link', 'uploadImage', 'blockQuote', 'insertTable', 'mediaEmbed',
          '|',
          'undo', 'redo'
        ]
      }
    })
    .then(newEditor => {
      editor = newEditor;
      window.editor = newEditor;

      // Anclar la barra de herramientas al contenedor superior
      const toolbarContainer = document.querySelector('.document-editor__toolbar');
      if (toolbarContainer) {
        toolbarContainer.appendChild(editor.ui.view.toolbar.element);
      }
      
      // Si hay contenido preexistente en el div, CKEditor lo carga automáticamente.
      const editorContent = document.getElementById('editorContent');
      if (editorContent && editorContent.textContent.trim().length > 0) {
        editor.setData(editorContent.innerHTML);
      }
    })
    .catch(error => {
      console.error('Error inicializando CKEditor 5:', error);
    });
}

/* verifica el contenido del editor antes de enviar el formulario */
const form = document.getElementById('formPublicacion') || document.getElementById('formEdicion');
const contenidoInput = document.getElementById('contenido');
if (form && contenidoInput) {
  form.addEventListener('submit', () => {
    if (window.editor) {
      let html = window.editor.getData();
      
      // Convertir etiquetas oembed de CKEditor a los divs social-embed que el backend procesa
      html = html.replace(/<oembed url="([^"]+)"><\/oembed>/gi, 
                          '<div class="social-embed" data-url="$1"></div>');
      // Limpiar también cualquier social-embed con contenido residual para dejarlo limpio
      html = html.replace(/<div class="social-embed"[^>]*data-url="([^"]+)"[^>]*>.*?<\/div>/gi,
                          '<div class="social-embed" data-url="$1"></div>');
      
      contenidoInput.value = html;
    }
  });
}
// modal-delete
function initDeleteModals() {
    const modalConfigs = [
        {
            selector: ".btn-delete",
            overlayId: "modalOverlay",
            titleId: "modalTitle",
            inputId: "modalId",
            formatTitle: title => `¿Eliminar la noticia "${title}"?`,
            dataKey: "titulo"
        },
        {
            selector: ".btn-delete-publicidad",
            overlayId: "modalOverlayP",
            titleId: "modalTitleP",
            inputId: "modalIdP",
            formatTitle: title => `¿Eliminar la publicidad "${title}"?`,
            dataKey: "titulo"
        },
        {
            selector: ".btn-delete-usuario",
            overlayId: "modalOverlayU",
            titleId: "modalTitleU",
            inputId: "modalIdU",
            formatTitle: title => `¿Eliminar el usuario "${title}"?`,
            dataKey: "nombre"
        }
    ];

    const configByClass = {};
    modalConfigs.forEach(config => {
        configByClass[config.selector.replace('.', '')] = config;
    });

    document.body.addEventListener("click", event => {
        const button = event.target.closest(
            ".btn-delete, .btn-delete-publicidad, .btn-delete-usuario"
        );
        if (!button) return;
        event.preventDefault();

        const config = modalConfigs.find(c => button.matches(c.selector));
        if (!config) return;

        const overlay = document.getElementById(config.overlayId);
        const title = document.getElementById(config.titleId);
        const input = document.getElementById(config.inputId);
        if (!overlay || !title || !input) return;

        const dataValue = button.dataset[config.dataKey] || "";
        title.textContent = config.formatTitle(dataValue);
        input.value = button.dataset.id || "";
        overlay.style.display = "flex";
    });

    document.querySelectorAll(".btn-cancel").forEach(cancelBtn => {
        cancelBtn.addEventListener("click", event => {
            event.preventDefault();
            event.stopPropagation();
            const overlay = cancelBtn.closest(".crop-modal");
            if (overlay) {
                overlay.style.display = "none";
            }
        });
    });

    document.body.addEventListener("click", event => {
        const overlay = event.target.closest(".crop-modal");
        if (!overlay) return;
        if (event.target === overlay) {
            overlay.style.display = "none";
        }
    });

    document.querySelectorAll(".crop-modal-content").forEach(modalContent => {
        modalContent.addEventListener("click", e => e.stopPropagation());
    });
}

if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initDeleteModals);
} else {
    initDeleteModals();
}
// modal validacion (solo editar.php — crear.php maneja su propio submit)
document.addEventListener("DOMContentLoaded", () => {
    if (document.getElementById("formPublicacion")) return;
    const modalTime = document.getElementById("timeModalOverlay");
    const autoAdjustBtn = document.getElementById("autoAdjustBtn");
    const manualAdjustBtn = document.getElementById("manualAdjustBtn");
    const fechaInput = document.getElementsByName("fecha_publicacion")[0];
    const guardarNoticiaBtns = document.getElementsByName("guardarNoticia");
    const modalForm = document.getElementById("formEdicion");
    if (!autoAdjustBtn || !manualAdjustBtn || !modalTime || !modalForm) return;
    function getLocalDatetimeString(date = new Date()) {
      const offset = date.getTimezoneOffset();
      const local = new Date(date.getTime() - offset * 60000);
      return local.toISOString().slice(0,16);
    } 
    guardarNoticiaBtns.forEach(btn => {
        btn.addEventListener("click", (e) => {
            e.preventDefault();
            const ahora = getLocalDatetimeString();
            const fechaSeleccionada = fechaInput.value;
            if (fechaSeleccionada < ahora) {
                modalTime.style.display = "flex";
            } else {
                modalForm.requestSubmit();
            }
        });
    });
    autoAdjustBtn.addEventListener("click", () => {
        fechaInput.value = getLocalDatetimeString();
        modalTime.style.display = "none";
        modalForm.requestSubmit();
    });
    manualAdjustBtn.addEventListener("click", () => {
        modalTime.style.display = "none";
    });
    // Evitar cerrar modal al hacer click dentro
    const modalContent = document.querySelector(".crop-modal-content");
    modalContent?.addEventListener("click", e => e.stopPropagation());
});
// Crop de publicidad
(() => {
  let cropper;
  const inputImage = document.getElementById("imagen");
  const imagePreview = document.getElementById("imagePreview");
  const resultPreview = document.getElementById("resultPreview");
  const cropBtn = document.getElementById("cropBtn");
  const resetBtn = document.getElementById("resetBtn");
  const tipoPublicidad = document.getElementById("tipo");
  const inputCrop = document.getElementById("imagenCrop");
  if (!inputImage || !cropBtn || !resetBtn || !tipoPublicidad) return;

  // Tamaños según tipo
  function getAspectRatio() {
      let tipo = tipoPublicidad.value;
      if (tipo == "1") return 21 / 6; // Banner
      if (tipo == "2") return 1 / 1;  // Cuadro
      return NaN;
  }

  // Cargar imagen
  inputImage.addEventListener("change", (e) => {
      const file = e.target.files[0];
      if (!file) return;

      const reader = new FileReader();
      reader.onload = () => {
          imagePreview.src = reader.result;
          imagePreview.style.display = "block";

          if (cropper) cropper.destroy();

          cropper = new Cropper(imagePreview, {
              aspectRatio: getAspectRatio(),
              viewMode: 1,
              autoCropArea: 1,
              cropBoxResizable: false,
              dragMode: 'move',
              responsive: true,
              guides: true,
              background: false
          });
      };
      reader.readAsDataURL(file);
  });

  // Cambiar tipo publicidad en tiempo real
  tipoPublicidad.addEventListener("change", () => {
      if (cropper) {
          cropper.setAspectRatio(getAspectRatio());
      }
  });

  // Recortar
  cropBtn.addEventListener("click", () => {
      if (!cropper) return;

      const canvas = cropper.getCroppedCanvas({
          width: 1920,
          height: 960,
          imageSmoothingQuality: 'high',
      });

      const croppedImage = canvas.toDataURL("image/jpeg", 0.98);
      resultPreview.src = croppedImage;
      inputCrop.value = croppedImage; // enviar al backend
  });

  // Deshacer
  resetBtn.addEventListener("click", () => {
      if (cropper) cropper.reset();
      resultPreview.src = "";
      inputCrop.value = "";
  });
})();

/* ─────────────────────────────────────────
   SIDEBAR PLEGABLE — DESKTOP
   - Hover: expande automáticamente, colapsa al salir
   - Controlado por clases
───────────────────────────────────────── */
(function () {
  const sidebarEl = document.querySelector('.sidebar');
  if (!sidebarEl) return;

  const body = document.body;
  const desktopMQ = window.matchMedia('(min-width: 769px)');
  let collapseTimer;

  const setCollapsed = (collapsed) => {
    sidebarEl.classList.toggle('is-collapsed', collapsed);
    body.classList.toggle('sidebar-collapsed', collapsed);
  };

  const collapse = () => {
    if (!desktopMQ.matches) return;
    setCollapsed(true);
  };

  const expand = () => {
    clearTimeout(collapseTimer);
    setCollapsed(false);
  };

  const handleMouseLeave = () => {
    if (!desktopMQ.matches) return;
    clearTimeout(collapseTimer);
    collapse();
  };

  const syncState = () => {
    if (desktopMQ.matches) {
      // Iniciar colapsado por defecto en desktop
      setCollapsed(true);
    } else {
      clearTimeout(collapseTimer);
      setCollapsed(false);
    }
  };

  sidebarEl.addEventListener('mouseenter', () => {
    if (desktopMQ.matches) expand();
  });
  sidebarEl.addEventListener('mouseleave', handleMouseLeave);

  if (desktopMQ.addEventListener) {
    desktopMQ.addEventListener('change', syncState);
  } else {
    desktopMQ.addListener(syncState);
  }
  // Resaltar la página activa en el sidebar
  const activePage = window.location.pathname.split('/').pop().toLowerCase();
  document.querySelectorAll('.sidebar-menu-link').forEach(link => {
    const hrefAttr = link.getAttribute('href');
    if (hrefAttr) {
      const linkPage = hrefAttr.split('/').pop().toLowerCase();
      if (activePage === linkPage) {
        link.classList.add('active');
      }
    }
  });

  window.addEventListener('resize', syncState, { passive: true });
  syncState();
})();