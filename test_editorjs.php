<?php
// test_editorjs.php - Demo de Editor.js (Estructura de bloques JSON)
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demo Editor.js - CatInk</title>
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

        /* Contenedor del Editor.js en tema oscuro */
        .editorjs-wrapper {
            background: #ffffff;
            color: #1e293b;
            border-radius: 10px;
            padding: 30px 20px;
            min-height: 450px;
            max-height: 550px;
            overflow-y: auto;
        }

        /* Ajustes internos para que se vea limpio */
        .ce-block {
            font-size: 1.05rem;
            line-height: 1.6;
        }
        
        .ce-toolbar__content, .ce-block__content {
            max-width: 650px;
        }

        .ce-header {
            font-family: var(--font-title);
            font-weight: 700;
            color: #0f172a;
        }

        /* JSON Output */
        .json-preview {
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
            max-height: 520px;
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

        /* Tip para EditorJS */
        .help-tip {
            font-size: 0.85rem;
            color: var(--text-muted);
            background: rgba(255,255,255,0.02);
            border: 1px solid var(--border-color);
            padding: 10px 15px;
            border-radius: 8px;
            line-height: 1.4;
        }

        .help-tip strong {
            color: var(--text-main);
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
            <i class="bi bi-box"></i>
            <span>CatInk Playground</span>
        </a>
        <div style="display: flex; gap: 15px;">
            <a href="test_quill2.php" class="back-btn"><i class="bi bi-feather"></i> Probar Quill 2.0</a>
            <a href="test_ckeditor5.php" class="back-btn"><i class="bi bi-file-earmark-word"></i> Probar CKEditor 5</a>
        </div>
    </header>

    <main class="main-container">
        <!-- Columna Izquierda: Editor -->
        <div class="editor-card">
            <div class="card-header">
                <h2 class="card-title"><i class="bi bi-distribute-vertical"></i> Editor.js (Por Bloques)</h2>
                <span class="badge">Estructura Limpia</span>
            </div>
            
            <div class="help-tip">
                <i class="bi bi-info-circle"></i> En Editor.js, cada elemento (título, párrafo, imagen, etc.) es un bloque individual. Presiona <strong>Enter</strong> para crear uno nuevo o escribe <strong>"/"</strong> para abrir el menú de bloques.
            </div>

            <div class="editorjs-wrapper">
                <!-- Div donde se monta EditorJS -->
                <div id="editorjs"></div>
            </div>
            
            <button class="btn-action" id="btnGuardar">
                <i class="bi bi-floppy"></i> Obtener JSON e Imprimir
            </button>
        </div>

        <!-- Columna Derecha: JSON Generado -->
        <div class="preview-card">
            <div class="card-header">
                <h2 class="card-title"><i class="bi bi-braces"></i> JSON de Datos Generado</h2>
            </div>
            
            <pre class="json-preview" id="json-output">// Modifica el editor para ver el JSON estructurado aquí en vivo...</pre>
        </div>
    </main>

    <!-- Modal de Éxito -->
    <div class="modal" id="successModal">
        <div class="modal-content">
            <div class="modal-icon"><i class="bi bi-check-circle-fill"></i></div>
            <h3 style="margin-top: 0; font-family: var(--font-title); font-size: 1.5rem;">¡Estructura de bloques JSON guardada!</h3>
            <p style="color: var(--text-muted);">Los datos estructurados han sido generados en limpio. Este formato elimina cualquier bug de renderización en móvil.</p>
            <button class="modal-close" onclick="closeModal()">Entendido</button>
        </div>
    </div>

    <!-- CDN de Editor.js y Plugins Clave -->
    <script src="https://cdn.jsdelivr.net/npm/@editorjs/editorjs@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/@editorjs/header@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/@editorjs/list@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/@editorjs/simple-image@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/@editorjs/embed@latest"></script>

    <script>
        const jsonOutput = document.getElementById('json-output');

        // Inicializar Editor.js
        const editor = new EditorJS({
            holder: 'editorjs',
            placeholder: 'Presiona "/" para insertar títulos, listas, imágenes...',
            tools: {
                header: {
                    class: Header,
                    inlineToolbar: ['link']
                },
                list: {
                    class: EditorjsList,
                    inlineToolbar: true
                },
                image: SimpleImage,
                embed: {
                    class: Embed,
                    inlineToolbar: true
                }
            },
            data: {
                blocks: [
                    {
                        type: "header",
                        data: {
                            text: "Bienvenido a Editor.js",
                            level: 2
                        }
                    },
                    {
                        type: "paragraph",
                        data: {
                            text: "Este es un editor basado en bloques. A diferencia de Quill o CKEditor que devuelven HTML crudo con etiquetas y clases difíciles de formatear, Editor.js genera un JSON estructurado que le da total control al programador."
                        }
                    },
                    {
                        type: "list",
                        data: {
                            style: "unordered",
                            items: [
                                "Formato 100% limpio sin tags HTML huérfanos.",
                                "Renderizado óptimo en aplicaciones móviles nativas.",
                                "Validación segura a nivel de servidor."
                            ]
                        }
                    }
                ]
            },
            onChange: (api, event) => {
                // Actualizar el JSON cada vez que cambie algo
                editor.save().then((outputData) => {
                    jsonOutput.textContent = JSON.stringify(outputData, null, 4);
                }).catch((error) => {
                    console.log('Saving failed: ', error)
                });
            }
        });

        // Mostrar JSON inicial al cargar
        editor.isReady.then(() => {
            editor.save().then((outputData) => {
                jsonOutput.textContent = JSON.stringify(outputData, null, 4);
            });
        });

        // Control del Modal
        const modal = document.getElementById('successModal');
        document.getElementById('btnGuardar').addEventListener('click', () => {
            editor.save().then((outputData) => {
                console.log(outputData);
                modal.style.display = 'flex';
            });
        });

        function closeModal() {
            modal.style.display = 'none';
        }
    </script>
</body>
</html>
