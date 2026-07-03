<?php
// test_ckeditor5.php - Demo de CKEditor 5 estilo Google Docs (Editor Desacoplado)
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demo CKEditor 5 (Estilo Google Docs) - CatInk</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
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
            max-width: 1700px;
            margin: 0 auto;
            width: 95%;
            display: grid;
            grid-template-columns: 1.25fr 0.75fr;
            gap: 30px;
        }

        @media (max-width: 1200px) {
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

        /* ── ESTRUCTURA ESTILO GOOGLE DOCS ── */
        .document-editor {
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            background: #f8fafc;
        }

        /* Barra de herramientas de CKEditor */
        .document-editor__toolbar {
            z-index: 1;
            border-bottom: 1px solid #cbd5e1;
        }
        
        .document-editor__toolbar .ck-toolbar {
            border: none !important;
            background: #f1f5f9 !important;
        }

        /* Contenedor gris simulador de escritorio */
        .document-editor__editable-container {
            padding: 30px 15px;
            background: #e2e8f0;
            height: 480px;
            overflow-y: auto;
            display: flex;
            justify-content: center;
            align-items: flex-start;
        }

        /* La hoja de papel blanca (Google Docs) */
        .document-editor__editable-container .ck-editor__editable {
            width: 100%;
            max-width: 750px;
            min-height: 600px;
            padding: 40px 50px !important;
            border: 1px solid #cbd5e1 !important;
            border-radius: 4px !important;
            background: white !important;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            color: #0f172a !important;
            font-family: var(--font-family) !important;
            line-height: 1.6;
            outline: none !important;
        }

        /* Preview area */
        .code-preview {
            background: #0f172a;
            border-radius: 10px;
            padding: 20px;
            font-family: monospace;
            font-size: 0.85rem;
            color: #38bdf8;
            white-space: pre-wrap;
            word-break: break-all;
            flex-grow: 1;
            overflow-y: auto;
            height: 520px;
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
            <i class="bi bi-file-earmark-word"></i>
            <span>CatInk Playground</span>
        </a>
        <div style="display: flex; gap: 15px;">
            <a href="test_quill2.php" class="back-btn"><i class="bi bi-feather"></i> Probar Quill 2.0</a>
            <a href="test_editorjs.php" class="back-btn"><i class="bi bi-box"></i> Probar Editor.js</a>
        </div>
    </header>

    <main class="main-container">
        <!-- Columna Izquierda: Editor -->
        <div class="editor-card">
            <div class="card-header">
                <h2 class="card-title"><i class="bi bi-file-text-fill"></i> CKEditor 5 (Estilo Documento)</h2>
                <span class="badge">Google Docs Feel</span>
            </div>
            
            <div class="document-editor">
                <!-- Barra de herramientas separada (Decoupled) -->
                <div class="document-editor__toolbar"></div>
                
                <!-- Contenedor del "escritorio" -->
                <div class="document-editor__editable-container">
                    <!-- La "hoja de papel" editable -->
                    <div id="editor">
                        <h2>Espectacular Editor estilo Documento</h2>
                        <p>Prueba a escribir en este espacio. Este es el editor desacoplado de <strong>CKEditor 5</strong>, diseñado para emular la barra de herramientas superior y los márgenes de página de Google Docs.</p>
                        <p>Los editores tradicionales como CKEditor 5 son excelentes cuando los redactores están acostumbrados al flujo de Word.</p>
                    </div>
                </div>
            </div>
            
            <button class="btn-action" id="btnGuardar">
                <i class="bi bi-floppy"></i> Simular Guardado
            </button>
        </div>

        <!-- Columna Derecha: HTML Generado -->
        <div class="preview-card">
            <div class="card-header">
                <h2 class="card-title"><i class="bi bi-code-slash"></i> HTML Limpio Generado</h2>
            </div>
            
            <pre class="code-preview" id="code-output"></pre>
        </div>
    </main>

    <!-- Modal de Éxito -->
    <div class="modal" id="successModal">
        <div class="modal-content">
            <div class="modal-icon"><i class="bi bi-check-circle-fill"></i></div>
            <h3 style="margin-top: 0; font-family: var(--font-title); font-size: 1.5rem;">¡Nota guardada! (HTML)</h3>
            <p style="color: var(--text-muted);">CKEditor 5 ha procesado tu documento y generado el HTML limpio. Revisa el código final en la columna derecha.</p>
            <button class="modal-close" onclick="closeModal()">Entendido</button>
        </div>
    </div>

    <!-- CDN de CKEditor 5 Decoupled Document Build -->
    <script src="https://cdn.ckeditor.com/ckeditor5/41.4.2/decoupled-document/ckeditor.js"></script>

    <script>
        const codeOutput = document.getElementById('code-output');
        let editorInstance = null;

        // Inicializar DecoupledEditor
        DecoupledEditor
            .create(document.querySelector('#editor'), {
                placeholder: 'Comienza a redactar tu artículo aquí...'
            })
            .then(editor => {
                editorInstance = editor;
                
                // Anclar la barra de herramientas al contenedor superior
                const toolbarContainer = document.querySelector('.document-editor__toolbar');
                toolbarContainer.appendChild(editor.ui.view.toolbar.element);
                
                // Actualizar la vista previa inicial
                codeOutput.textContent = editor.getData();

                // Escuchar cambios
                editor.model.document.on('change:data', () => {
                    codeOutput.textContent = editor.getData();
                });
            })
            .catch(error => {
                console.error('Error inicializando CKEditor 5:', error);
            });

        // Control del Modal
        const modal = document.getElementById('successModal');
        document.getElementById('btnGuardar').addEventListener('click', () => {
            if (editorInstance) {
                console.log(editorInstance.getData());
                modal.style.display = 'flex';
            }
        });

        function closeModal() {
            modal.style.display = 'none';
        }
    </script>
</body>
</html>
