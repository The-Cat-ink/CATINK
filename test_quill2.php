<?php
// test_quill2.php - Demo de Quill v2.0
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demo Quill v2.0 - CatInk</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Quill 2.0.2 Snow Theme CSS -->
    <link href="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css" rel="stylesheet">
    
    <style>
        :root {
            --bg-gradient: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%);
            --panel-bg: rgba(30, 41, 59, 0.7);
            --accent-pink: #ef3363;
            --accent-hover: #ff4c76;
            --border-color: rgba(255, 255, 255, 0.1);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --font-family: 'Plus Jakarta Sans', sans-serif;
            --font-title: 'Outfit', sans-serif;
        }

        body {
            margin: 0;
            padding: 0;
            background: var(--bg-gradient);
            color: var(--text-main);
            font-family: var(--font-family);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
        }

        .header {
            padding: 20px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--border-color);
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(12px);
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: var(--text-main);
        }

        .logo i {
            color: var(--accent-pink);
            font-size: 2rem;
            filter: drop-shadow(0 0 8px rgba(239, 51, 99, 0.5));
        }

        .logo span {
            font-family: var(--font-title);
            font-weight: 800;
            font-size: 1.5rem;
            letter-spacing: 0.5px;
        }

        .back-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            padding: 8px 16px;
            border-radius: 8px;
            border: 1px solid var(--border-color);
            background: rgba(255, 255, 255, 0.02);
        }

        .back-btn:hover {
            color: var(--text-main);
            background: rgba(255, 255, 255, 0.08);
            border-color: var(--accent-pink);
        }

        .main-container {
            flex-grow: 1;
            padding: 40px;
            max-width: 1600px;
            margin: 0 auto;
            width: 95%;
            display: grid;
            grid-template-columns: 1.2fr 0.8fr;
            gap: 30px;
        }

        @media (max-width: 1024px) {
            .main-container {
                grid-template-columns: 1fr;
            }
        }

        .editor-card, .preview-card {
            background: var(--panel-bg);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 24px;
            backdrop-filter: blur(20px);
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.3);
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-title {
            font-family: var(--font-title);
            font-weight: 700;
            font-size: 1.4rem;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-title i {
            color: var(--accent-pink);
        }

        .badge {
            background: rgba(239, 51, 99, 0.15);
            color: var(--accent-pink);
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            border: 1px solid rgba(239, 51, 99, 0.25);
        }

        /* Personalización de Quill 2.0 en tema oscuro */
        .quill-editor-wrapper {
            background: #ffffff;
            color: #1e293b;
            border-radius: 10px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            min-height: 450px;
        }

        .ql-toolbar.ql-snow {
            border: none !important;
            background: #f1f5f9;
            border-bottom: 1px solid #cbd5e1 !important;
            padding: 10px 15px !important;
        }

        .ql-container.ql-snow {
            border: none !important;
            font-family: var(--font-family);
            font-size: 1rem;
            flex-grow: 1;
            overflow-y: auto;
        }

        .ql-editor {
            min-height: 380px;
            padding: 20px !important;
            line-height: 1.6;
        }

        /* Preview area */
        .preview-area {
            background: rgba(15, 23, 42, 0.4);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            padding: 20px;
            flex-grow: 1;
            max-height: 470px;
            overflow-y: auto;
            color: #cbd5e1;
        }

        .preview-area img {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
        }

        .preview-area h1, .preview-area h2, .preview-area h3 {
            font-family: var(--font-title);
            color: var(--text-main);
        }

        .preview-area a {
            color: var(--accent-pink);
        }

        .code-preview {
            background: #0f172a;
            border-radius: 8px;
            padding: 15px;
            font-family: monospace;
            font-size: 0.85rem;
            color: #38bdf8;
            white-space: pre-wrap;
            word-break: break-all;
            max-height: 150px;
            overflow-y: auto;
            border: 1px solid rgba(56, 189, 248, 0.15);
        }

        .btn-action {
            background: var(--accent-pink);
            color: white;
            border: none;
            padding: 12px 24px;
            font-size: 1rem;
            font-weight: 600;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-action:hover {
            background: var(--accent-hover);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(239, 51, 99, 0.3);
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(15, 23, 42, 0.8);
            backdrop-filter: blur(8px);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background: #1e293b;
            border: 1px solid var(--border-color);
            padding: 30px;
            border-radius: 16px;
            max-width: 500px;
            width: 90%;
            text-align: center;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5);
            animation: scaleUp 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes scaleUp {
            from { transform: scale(0.9); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }

        .modal-icon {
            font-size: 3.5rem;
            color: #10b981;
            margin-bottom: 15px;
        }

        .modal-close {
            background: rgba(255,255,255,0.05);
            border: 1px solid var(--border-color);
            color: var(--text-main);
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            margin-top: 20px;
            transition: all 0.2s ease;
        }

        .modal-close:hover {
            background: rgba(255,255,255,0.1);
        }
    </style>
</head>
<body>

    <header class="header">
        <a href="#" class="logo">
            <i class="bi bi-feather"></i>
            <span>CatInk Playground</span>
        </a>
        <div style="display: flex; gap: 15px;">
            <a href="test_editorjs.php" class="back-btn"><i class="bi bi-box"></i> Probar Editor.js</a>
            <a href="test_ckeditor5.php" class="back-btn"><i class="bi bi-file-earmark-word"></i> Probar CKEditor 5</a>
        </div>
    </header>

    <main class="main-container">
        <!-- Columna Izquierda: Editor -->
        <div class="editor-card">
            <div class="card-header">
                <h2 class="card-title"><i class="bi bi-file-earmark-richtext"></i> Quill Editor</h2>
                <span class="badge">Versión 2.0.2</span>
            </div>
            
            <div class="quill-editor-wrapper">
                <!-- Div donde se monta el editor -->
                <div id="editor-container">
                    <h2>¿Listo para actualizar a Quill v2.0?</h2>
                    <p>Esta es una demostración en vivo de la versión más reciente de Quill. Escribe lo que quieras, agrega <strong>negrita</strong>, <em>cursiva</em>, listas o incluso inserta imágenes.</p>
                    <p>Quill 2 ofrece una experiencia mucho más rápida en móviles y corrige múltiples problemas históricos de alineación de texto.</p>
                </div>
            </div>
            
            <button class="btn-action" id="btnGuardar">
                <i class="bi bi-floppy"></i> Simular Guardado
            </button>
        </div>

        <!-- Columna Derecha: Vista Previa y Código -->
        <div class="preview-card">
            <div class="card-header">
                <h2 class="card-title"><i class="bi bi-eye"></i> Vista Previa en Vivo</h2>
            </div>
            
            <div class="preview-area ql-editor" id="live-preview">
                <!-- Aquí se inyecta el HTML renderizado -->
            </div>

            <div class="card-header" style="margin-top: 10px;">
                <h2 class="card-title" style="font-size: 1.1rem;"><i class="bi bi-code-slash"></i> Código HTML Generado</h2>
            </div>
            <pre class="code-preview" id="code-output"></pre>
        </div>
    </main>

    <!-- Modal de Éxito -->
    <div class="modal" id="successModal">
        <div class="modal-content">
            <div class="modal-icon"><i class="bi bi-check-circle-fill"></i></div>
            <h3 style="margin-top: 0; font-family: var(--font-title); font-size: 1.5rem;">¡Nota simulada con éxito!</h3>
            <p style="color: var(--text-muted);">El editor ha generado el HTML correspondiente. Puedes ver el resultado final y el código generado en la columna derecha.</p>
            <button class="modal-close" onclick="closeModal()">Entendido</button>
        </div>
    </div>

    <!-- Quill 2.0.2 Library JS -->
    <script src="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js"></script>

    <script>
        // Inicializar Quill 2.0
        const quill = new Quill('#editor-container', {
            theme: 'snow',
            modules: {
                toolbar: [
                    [{ 'header': [1, 2, 3, false] }],
                    ['bold', 'italic', 'underline', 'strike'],
                    [{ 'color': [] }, { 'background': [] }],
                    [{ 'align': [] }],
                    ['link', 'image', 'video'],
                    ['blockquote', 'code-block'],
                    [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                    ['clean']
                ]
            }
        });

        const previewArea = document.getElementById('live-preview');
        const codeOutput = document.getElementById('code-output');

        // Función para actualizar la vista previa
        function updatePreview() {
            const html = quill.root.innerHTML;
            previewArea.innerHTML = html;
            codeOutput.textContent = html;
        }

        // Escuchar cambios en el editor
        quill.on('text-change', function() {
            updatePreview();
        });

        // Cargar vista previa inicial
        updatePreview();

        // Control del Modal
        const modal = document.getElementById('successModal');
        document.getElementById('btnGuardar').addEventListener('click', () => {
            modal.style.display = 'flex';
        });

        function closeModal() {
            modal.style.display = 'none';
        }
    </script>
</body>
</html>
