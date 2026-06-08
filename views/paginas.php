<?php
    include("./../layout/headerAdmin.php");
    include("./../data/conexion.php");
    $sql = "SELECT * FROM paginas"; 
    $result = $con->query($sql);
    
    $paginas = [];
    while($row = $result->fetch_assoc()){
        $paginas[] = $row;
    }
    $totalPaginas = count($paginas);
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4" style="flex-wrap: wrap; gap: 12px;">
        <h1 style="margin:0;">Gestión de Páginas Legales</h1>
    </div>

    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'actualizado'): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert" style="background:rgba(16,185,129,0.1); color:#10b981; border:1px solid rgba(16,185,129,0.2); border-radius:8px;">
            <i class="bi bi-check-circle-fill"></i> Página actualizada correctamente.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Estadísticas rápidas -->
    <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));">
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(99,102,241,0.1); color: #6366f1;"><i class="bi bi-file-earmark-text"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?= $totalPaginas ?></span>
                <span class="stat-label">Páginas de Sistema</span>
            </div>
        </div>
    </div>

    <!-- Toolbar -->
    <div class="contenidos-toolbar">
        <div class="contenidos-tabs">
            <span class="tab-btn active">Todas las páginas</span>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="contenidos-table">
                    <thead>
                        <tr>
                            <th>Nombre de la Sección</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($paginas as $row): ?>
                            <tr>
                                <td>
                                    <strong class="table-title" style="text-transform: capitalize;">
                                        <i class="bi bi-file-text text-muted" style="margin-right:5px;"></i> <?= htmlspecialchars($row['nombre_pag']) ?>
                                    </strong>
                                </td>
                                <td>
                                    <div class="noticias-actions" style="border-top:none; padding:0; justify-content:flex-start;">
                                        <button 
                                            class="btn btn-edit btnEditar" 
                                            data-id="<?= $row['id_pag'] ?>" 
                                            data-nombre="<?= htmlspecialchars($row['nombre_pag']) ?>"
                                            data-contenido="<?= base64_encode($row['contenido_pag']) ?>" title="Editar Contenido">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div id="modalPagina" class="crop-modal" style="display: none;">
    <div class="crop-modal-content" style="max-width: 800px; width:95%;">
        <h3><i class="bi bi-pencil-square"></i> Editar Página</h3>
        
        <form id="formPagina" action="./../controllers/pagina.php" method="POST">
            <input type="hidden" name="id" id="pagina_id">
            
            <div style="margin-bottom: 16px;">
                <label for="nombre" style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text);">Sección</label>
                <select name="nombre" id="nombre" style="width: 100%; padding: 10px 14px; border: 1px solid var(--border); border-radius: 8px; font-size: 0.9rem; background: var(--bg); color: var(--text);">
                    <option value="nosotros">Nosotros</option>
                    <option value="terminos">Términos y condiciones</option>
                    <option value="privacidad">Aviso de privacidad</option>
                    <option value="cookies">Política de cookies</option>
                </select>
            </div>

            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text);">Contenido</label>
                <!-- TOOLBAR QUILL -->
                <div class="editor-toolbar ql-toolbar ql-snow" style="border-radius: 8px 8px 0 0; background: var(--card-bg);">
                    <!-- Fuente -->
                    <select class="ql-font" title="Fuente">
                        <option value="arial" selected>Arial</option>
                        <option value="times">Times New Roman</option>
                        <option value="roboto">Roboto</option>
                        <option value="courier">Courier</option>
                    </select>
                    <!-- Tamaño -->
                    <select class="ql-size" title="Tamaño">
                        <option value="small">Pequeño</option>
                        <option selected>Normal</option>
                        <option value="large">Grande</option>
                        <option value="huge">Muy grande</option>
                    </select>
                    <!-- Estilos -->
                    <button class="ql-bold" title="Negritas"></button>
                    <button class="ql-italic" title="Cursiva"></button>
                    <button class="ql-underline" title="Subrayado"></button>
                    <button class="ql-strike" title="Tachado"></button>

                    <!-- Color -->
                    <select class="ql-color" title="Color"></select>
                    <select class="ql-background" title="Fondo"></select>

                    <!-- Alineación -->
                    <select class="ql-align" title="Alineación"></select>
                    <!-- Listas -->
                    <button class="ql-list" value="ordered" title="Lista ordenada"></button>
                    <button class="ql-list" value="bullet" title="Lista desordenada"></button>

                    <!-- Sangría -->
                    <button class="ql-indent" value="-1" title="Reducir sangría"></button>
                    <button class="ql-indent" value="+1" title="Aumentar sangría"></button>

                    <!-- Limpiar formato -->
                    <button class="ql-clean" title="Limpiar formato"></button>
                </div>
                <div id="editorpag" class="editor-content" style="border-radius: 0 0 8px 8px; border: 1px solid var(--border); border-top: none;"></div>
                <!-- AQUI SE GUARDA QUILL -->
                <input type="hidden" name="contenido" id="contenido">
            </div>

            <div class="crop-actions">
                <button type="button" class="btn btn-secondary" id="modalClosePag">Cancelar</button>
                <button type="submit" class="btn btn-accent"><i class="bi bi-save"></i> Guardar Cambios</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    var quillpag = new Quill('#editorpag', {
        theme: 'snow',
        placeholder: 'Escribe el contenido aquí...',
        modules: {
            toolbar: {
                container: '.editor-toolbar'
            }
        }
    });

    const modalPagina = document.getElementById("modalPagina");
    const modalClosePag = document.getElementById("modalClosePag");

    document.querySelectorAll(".btnEditar").forEach(btn => {
        btn.addEventListener("click", function(){
            document.getElementById("pagina_id").value = this.dataset.id;
            
            // Asignar select option
            const selectNombre = document.getElementById("nombre");
            for(let i=0; i<selectNombre.options.length; i++){
                if(selectNombre.options[i].value === this.dataset.nombre) {
                    selectNombre.selectedIndex = i;
                    break;
                }
            }
            
            let contenido = atob(this.dataset.contenido);
            modalPagina.style.display = "flex"; // Usa flex en el nuevo crop-modal
            
            setTimeout(() => {
                quillpag.setContents([]);
                quillpag.clipboard.dangerouslyPasteHTML(contenido);
            }, 100);
        });
    });

    document.getElementById("formPagina").addEventListener("submit", function(){
        document.getElementById("contenido").value = quillpag.root.innerHTML;
    });

    modalClosePag.addEventListener('click', () => {
        modalPagina.style.display = "none";
    });

    window.addEventListener('click', (e) => {
        if(e.target === modalPagina) {
            modalPagina.style.display = "none";
        }
    });
});
</script>
<?php include("./../layout/footerAdmin.php"); ?>