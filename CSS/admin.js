/*
  Archivo: admin.js
  Propósito: Lógica exclusiva del panel de administración (Turbo-compatible)
  Incluye:
  - Previsualización de imágenes
  - Cropper.js (imagen principal, galería y editor Quill)
  - Editor Quill / CKEditor 5
  - Manejo de estado / programación de publicaciones
  - Validaciones defensivas
  - Sidebar plegable desktop/mobile con limpieza de listeners globales
*/

(function () {
  // --- VARIABLES Y CONFIGURACIONES DE ALTO NIVEL ---
  let collapseTimer;
  const desktopMQ = window.matchMedia('(min-width: 769px)');

  // Tema
  function applyTheme(theme) {
    document.documentElement.setAttribute('data-bs-theme', theme);
    const themeToggle = document.getElementById('themeToggle');
    if (themeToggle) {
      themeToggle.checked = theme === 'dark';
    }
  }

  // Sidebar Desktop - Funciones de Control
  const setCollapsed = (collapsed) => {
    const sidebarEl = document.querySelector('.sidebar');
    const body = document.body;
    if (sidebarEl) sidebarEl.classList.toggle('is-collapsed', collapsed);
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
    const sidebarEl = document.querySelector('.sidebar');
    if (!sidebarEl) return;

    if (desktopMQ.matches) {
      if (sidebarEl.matches(':hover')) {
        setCollapsed(false);
      } else {
        setCollapsed(true);
      }
    } else {
      clearTimeout(collapseTimer);
      setCollapsed(false);
    }
  };

  const handleWindowBlur = () => {
    if (desktopMQ.matches) {
      clearTimeout(collapseTimer);
      collapse();
    }
  };

  const handleVisibilityChange = () => {
    if (document.hidden) {
      if (desktopMQ.matches) {
        clearTimeout(collapseTimer);
        collapse();
      }
    } else {
      syncState();
    }
  };

  // Sidebar Mobile - Funciones de Control
  const setSidebarState = (isOpen) => {
    const sidebar = document.querySelector('.sidebar');
    const sidebarBackdrop = document.getElementById('sidebarBackdrop');
    if (sidebar) sidebar.classList.toggle('active', isOpen);
    sidebarBackdrop?.classList.toggle('active', isOpen);
    document.body.classList.toggle('sidebar-open', isOpen);
  };

  const handleMobileSidebarOutsideClick = (e) => {
    const sidebar = document.querySelector('.sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    if (!sidebar || !sidebarToggle) return;

    if (window.innerWidth < 768 && 
        sidebar.classList.contains('active') && 
        !sidebar.contains(e.target) && 
        e.target !== sidebarToggle &&
        !sidebarToggle.contains(e.target)) {
        setSidebarState(false);
    }
  };

  const handleMobileSidebarResize = () => {
    if (window.innerWidth >= 768) {
      setSidebarState(false);
    }
  };

  const toggleMobileSidebar = () => {
    const sidebar = document.querySelector('.sidebar');
    if (sidebar) {
      setSidebarState(!sidebar.classList.contains('active'));
    }
  };

  const closeMobileSidebar = () => {
    setSidebarState(false);
  };

  // Modal Deletions - Click fuera
  const handleCropModalOutsideClick = (event) => {
    const overlay = event.target.closest(".crop-modal");
    if (!overlay) return;
    if (event.target === overlay) {
      overlay.style.display = "none";
    }
  };


  // --- INICIALIZADOR PRINCIPAL (SE EJECUTA EN CADA TURBO:LOAD) ---
  function initAdmin() {
    console.log('Turbo: Inicializando panel de administrador (admin.js)');

    // 1. Toggle de colapso de Bootstrap nativo simplificado
    document.querySelectorAll('[data-bs-toggle="collapse"]').forEach(btn => {
      btn.addEventListener('click', function(e) {
        const targetSelector = btn.getAttribute('data-bs-target');
        if (!targetSelector) return;
        const target = document.querySelector(targetSelector);
        if (!target) return;
        target.classList.toggle('show');
      });
    });

    // 2. Toggle de tema
    const themeToggle = document.getElementById('themeToggle');
    if (themeToggle) {
      themeToggle.addEventListener('change', () => {
        const next = themeToggle.checked ? 'dark' : 'light';
        applyTheme(next);
        localStorage.setItem('theme', next);
      });
    }
    const saved = localStorage.getItem('theme') || 'light';
    applyTheme(saved);

    // 3. Toggle Sidebar (Mobile)
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.querySelector('.sidebar');
    const sidebarBackdrop = document.getElementById('sidebarBackdrop');

    if (sidebarToggle && sidebar) {
      sidebarToggle.removeEventListener('click', toggleMobileSidebar);
      sidebarToggle.addEventListener('click', toggleMobileSidebar);
      
      sidebarBackdrop?.removeEventListener('click', closeMobileSidebar);
      sidebarBackdrop?.addEventListener('click', closeMobileSidebar);

      // Limpiar y registrar eventos del documento/ventana para móvil
      document.removeEventListener('click', handleMobileSidebarOutsideClick);
      document.addEventListener('click', handleMobileSidebarOutsideClick);

      window.removeEventListener('resize', handleMobileSidebarResize);
      window.addEventListener('resize', handleMobileSidebarResize);
    }

    // 4. Previsualización de imágenes
    try { initImagenCorreoPreview(); } catch(e) { console.error('Error initImagenCorreoPreview:', e); }

    // 5. Previsualización de video
    try { initVideoPreview(); } catch(e) { console.error('Error initVideoPreview:', e); }

    // 6. Croppers principales
    try { initMainCroppers(); } catch(e) { console.error('Error initMainCroppers:', e); }

    // 7. Editor CKEditor 5
    try { initCKEditor(); } catch(e) { console.error('Error initCKEditor:', e); }

    // 8. Modals de eliminación
    try { initDeleteModals(); } catch(e) { console.error('Error initDeleteModals:', e); }

    // 9. Modals de validación de fechas (editar.php)
    try { initValidationModals(); } catch(e) { console.error('Error initValidationModals:', e); }

    // 10. Cropper de publicidad
    try { initPublicidadCropper(); } catch(e) { console.error('Error initPublicidadCropper:', e); }

    // 11. Sidebar Desktop
    try { initDesktopSidebar(); } catch(e) { console.error('Error initDesktopSidebar:', e); }
  }

  // --- SUBMODULOS ---

  function initImagenCorreoPreview() {
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
  }

  function initVideoPreview() {
    const videoInput = document.getElementById('video_url');
    const videoPreview = document.getElementById('videoPreview');
    if (!videoInput || !videoPreview) return;
    videoInput.addEventListener('input', () => {
      const url = videoInput.value.trim();
      videoPreview.innerHTML = '';
      if (!url) return;
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
      if (url.includes('vimeo.com')) {
        const id = url.split('/').pop();
        videoPreview.innerHTML =
          `<iframe src="https://player.vimeo.com/video/${id}" allowfullscreen></iframe>`;
        return;
      }
      if (url.match(/\.(mp4|webm|ogg)$/)) {
        videoPreview.innerHTML =
          `<video controls><source src="${url}"></video>`;
        return;
      }
      videoPreview.innerHTML = `<p class="error">No se pudo previsualizar el video</p>`;
    });
  }

  function initMainCroppers() {
    const cropConfigs = [
      {
        num: 1,
        name: 'Original',
        ratio: NaN,
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

    const croppers = {};
    const isEditForm = !!document.getElementById('formEdicion');

    cropConfigs.forEach(config => {
      const input = document.getElementById(config.inputId);
      const img = document.getElementById(config.imgId);
      const preview = document.getElementById(config.previewId);
      const confirmBtn = document.querySelector(config.confirmBtnSelector);
      const resetBtn = document.querySelector(config.resetBtnSelector);
      const hiddenInput = document.getElementById(`crop${config.num}`);
      const cropperContainer = img?.closest('.cropper-container');

      if (!input || !img || !preview) return;

      if (isEditForm && cropperContainer) {
        cropperContainer.style.display = 'none';
      }

      input.addEventListener('change', e => {
        const file = e.target.files[0];
        if (!file) return;

        const reader = new FileReader();
        reader.onload = () => {
          img.src = reader.result;
          if (cropperContainer) cropperContainer.style.display = 'block';
          if (croppers[config.num]) {
            croppers[config.num].destroy();
          }
          croppers[config.num] = new Cropper(img, {
            viewMode: 0,
            autoCropArea: 0.9,
            aspectRatio: config.ratio,
            cropBoxResizable: true,
            dragMode: 'move',
            responsive: true,
            guides: true,
            background: false
          });
        };
        reader.readAsDataURL(file);
      });

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

        const dataUrl = canvas.toDataURL('image/png');
        if (hiddenInput) hiddenInput.value = dataUrl;
        preview.innerHTML = `<img src="${dataUrl}" style="width: auto; max-height:150px; object-fit:contain;">`;
        if (cropperContainer) cropperContainer.style.display = 'none';
      });

      resetBtn?.addEventListener('click', () => {
        input.value = '';
        preview.innerHTML = '';
        if (hiddenInput) hiddenInput.value = '';
        if (croppers[config.num]) {
          croppers[config.num].destroy();
          croppers[config.num] = null;
          img.src = '';
        }
        if (cropperContainer) cropperContainer.style.display = 'block';
      });
    });
  }

  function initCKEditor() {
    const editorElement = document.getElementById('editor');
    if (!editorElement) return;

    class ServerUploadAdapter {
      constructor(loader) {
        this.loader = loader;
      }
      upload() {
        return this.loader.file
          .then(file => new Promise((resolve, reject) => {
            const data = new FormData();
            data.append('imagen', file);
            fetch((window.ADMIN_BASE || '') + '/controllers/subir_imagen_contenido.php', {
              method: 'POST',
              body: data
            })
              .then(r => r.json())
              .then(res => {
                if (res && res.url) resolve({ default: res.url });
                else reject((res && res.error) || 'No se pudo subir la imagen');
              })
              .catch(() => reject('Error de red al subir la imagen'));
          }));
      }
      abort() {}
    }

    function ServerUploadAdapterPlugin(editorInstance) {
      editorInstance.plugins.get('FileRepository').createUploadAdapter = (loader) => {
        return new ServerUploadAdapter(loader);
      };
    }

    DecoupledEditor
      .create(editorElement, {
        extraPlugins: [ServerUploadAdapterPlugin],
        placeholder: 'Comienza a escribir tu nota aquí...',
        language: 'es',
        toolbar: {
          items: [
            'heading', '|', 'bold', 'italic', 'underline', 'strikethrough', '|',
            'fontSize', 'fontColor', 'fontBackgroundColor', '|',
            'alignment', '|', 'numberedList', 'bulletedList', '|',
            'outdent', 'indent', '|', 'link', 'uploadImage', 'blockQuote', 'insertTable', 'mediaEmbed', '|',
            'undo', 'redo'
          ]
        }
      })
      .then(newEditor => {
        window.editor = newEditor;
        const toolbarContainer = document.querySelector('.document-editor__toolbar');
        if (toolbarContainer) {
          toolbarContainer.appendChild(newEditor.ui.view.toolbar.element);
        }
      })
      .catch(error => {
        console.error('Error inicializando CKEditor 5:', error);
      });

    const form = document.getElementById('formPublicacion') || document.getElementById('formEdicion');
    const contenidoInput = document.getElementById('contenido');
    if (form && contenidoInput) {
      form.addEventListener('submit', () => {
        if (window.editor) {
          let html = window.editor.getData();
          html = html.replace(/<oembed url="([^"]+)"><\/oembed>/gi, 
                              '<div class="social-embed" data-url="$1"></div>');
          html = html.replace(/<div class="social-embed"[^>]*data-url="([^"]+)"[^>]*>.*?<\/div>/gi,
                              '<div class="social-embed" data-url="$1"></div>');
          contenidoInput.value = html;
        }
      });
    }
  }

  function initDeleteModals() {
    const modalConfigs = [
      {
        selector: ".btn-delete",
        overlayId: "modalOverlay",
        titleId: "modalTitle",
        inputId: "modalId",
        formatTitle: title => `¿Enviar la noticia "${title}" a la papelera?`,
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
      },
      {
        selector: ".btn-delete-def",
        overlayId: "modalOverlay",
        titleId: "modalTitle",
        inputId: "modalId",
        formatTitle: title => `¿Eliminar definitivamente "${title}"?`,
        dataKey: "titulo"
      }
    ];

    document.querySelectorAll(".crop-modal").forEach(modal => {
      modal.style.display = "none";
    });

    document.body.removeEventListener("click", handleCropModalOutsideClick);
    document.body.addEventListener("click", handleCropModalOutsideClick);

    // Event delegation para abrir modales de borrado
    document.body.addEventListener("click", event => {
      const button = event.target.closest(".btn-delete, .btn-delete-publicidad, .btn-delete-usuario, .btn-delete-def");
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
        if (overlay) overlay.style.display = "none";
      });
    });

    document.querySelectorAll(".crop-modal-content").forEach(modalContent => {
      modalContent.addEventListener("click", e => e.stopPropagation());
    });
  }

  function initValidationModals() {
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

    const modalContent = document.querySelector(".crop-modal-content");
    modalContent?.addEventListener("click", e => e.stopPropagation());
  }

  function initPublicidadCropper() {
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
      if (tipo == "1") return 21 / 6;
      if (tipo == "2") return 1 / 1;
      return NaN;
    }

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

    tipoPublicidad.addEventListener("change", () => {
      if (cropper) cropper.setAspectRatio(getAspectRatio());
    });

    cropBtn.addEventListener("click", () => {
      if (!cropper) return;
      const canvas = cropper.getCroppedCanvas({
        width: 1920,
        height: 960,
        imageSmoothingQuality: 'high',
      });
      const croppedImage = canvas.toDataURL("image/jpeg", 0.98);
      resultPreview.src = croppedImage;
      inputCrop.value = croppedImage;
    });

    resetBtn.addEventListener("click", () => {
      if (cropper) cropper.reset();
      resultPreview.src = "";
      inputCrop.value = "";
    });
  }

  function initDesktopSidebar() {
    const sidebarEl = document.querySelector('.sidebar');
    if (!sidebarEl) return;

    // Escuchadores de hover para expandir/colapsar
    sidebarEl.addEventListener('mouseenter', () => {
      if (desktopMQ.matches) expand();
    });
    sidebarEl.addEventListener('mouseleave', handleMouseLeave);

    // Resaltar la página activa en el sidebar
    const activePage = window.location.pathname.split('/').pop().toLowerCase();
    document.querySelectorAll('.sidebar-menu-link').forEach(link => {
      const hrefAttr = link.getAttribute('href');
      if (hrefAttr) {
        const linkPage = hrefAttr.split('/').pop().toLowerCase();
        if (activePage === linkPage) {
          link.classList.add('active');
        } else {
          link.classList.remove('active');
        }
      }
    });

    // Limpiar y registrar eventos globales de desktop
    window.removeEventListener('resize', syncState);
    window.addEventListener('resize', syncState, { passive: true });

    window.removeEventListener('blur', handleWindowBlur);
    window.addEventListener('blur', handleWindowBlur);

    document.removeEventListener('visibilitychange', handleVisibilityChange);
    document.addEventListener('visibilitychange', handleVisibilityChange);

    window.removeEventListener('focus', syncState);
    window.addEventListener('focus', syncState);

    if (desktopMQ.addEventListener) {
      desktopMQ.removeEventListener('change', syncState);
      desktopMQ.addEventListener('change', syncState);
    } else {
      desktopMQ.removeListener(syncState);
      desktopMQ.addListener(syncState);
    }

    // Inicializar estado
    syncState();
  }


  document.addEventListener('turbo:load', initAdmin);
})();