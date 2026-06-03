<?php
    include("./../layout/headerAdmin.php");
    include("./../controllers/aclcontroller.php");
    proteger('publicidad','crear');
    include("./../data/conexion.php");
    $categoriasResult = $con->query("SELECT id_c, nombre FROM categorias ORDER BY nombre ASC");
    $categorias = [];
    while($row = $categoriasResult->fetch_assoc()){
        $categorias[] = $row;
    }
?>
<div class="container">
    <h2>Crear Publicidad</h2>
    <form action="./../../controllers/guardar_publicidad.php" method="POST" enctype="multipart/form-data">
        <div class="form-card card">
            <input type="hidden" name="autor" value="<?= $fila['id_u'] ?>">
            <div class="form-group">
                <label for="Titulo" >Título</label>
                <input type="text" id="Titulo" name="Titulo" required>
            </div>
            <div class="form-group">
                <label for="tipo">Tipo de publicadad</label>
                <select id="tipo" name="tipo" required>
                    <option value="1">Banner Publicitario</option>
                    <option value="2">Cuadro Publicitario</option>
                </select>
            </div>
            <div class="form-group">
                <label for="imagen">Imagen</label>
                <input type="file" id="imagen" name="imagen" accept="image/*" required>
                <input type="hidden" name="imagenCrop" id="imagenCrop" value="">
                <div id="cropperContainer" style="display:none; margin-top: 20px;">
                    <img id="cropImg" style="max-width: 100%;">
                    <div style="margin-top: 10px; display: flex; gap: 10px;">
                        <button type="button" id="cropConfirmBtn" class="btn btn-accent">Confirmar recorte</button>
                        <button type="button" id="cropCancelBtn" class="btn btn-secondary">Cancelar</button>
                    </div>
                </div>
                <div id="previewContainer" style="display:none; margin-top: 20px;">
                    <h4>Vista previa:</h4>
                    <img id="previewImg" style="max-width: 100%; border: 1px solid #ccc;">
                </div>
            </div>
            <div class="form-group">
                <label for="url" >Url</label>
                <span>A donde va a redireccionar</span>
                <input type="text" id="url" name="url" required>
            </div>
            <div class="form-group">
                <label for="estado" >Estado</label>
                <span>Activo o Inactivo</span>
                <select id="estado" name="estado" required>
                    <option value="1">Activo</option>
                    <option value="0">Inactivo</option>
                </select>
            </div>
            <div class="form-group">
                <label for="Categorias">Categorías</label>
                <div class="checkbox-group">
                    <?php
                        foreach($categorias as $c):
                    ?>
                        <label>
                            <input type="checkbox" name="Categorias[]" value="<?=$c['id_c']?>">
                            <?=$c['nombre']?>
                        </label>
                    <?php
                        endforeach;
                    ?>
                </div>
            </div>
            <div class="form-group">
                <label for="fechaInicio">Fecha de inicio</label>
                <input type="datetime-local" id="fechaInicio" name="fechaInicio" required>
                <label for="fechaFin">Fecha de fin</label>
                <input type="datetime-local" id="fechaFin" name="fechaFin" required>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-accent" name="guardarPublicidad">
                    Guardar publicidad
                </button>
            </div>
        </div>
    </form>
</div>

<script>
let cropper = null;

function getAspectRatio() {
    const tipo = document.getElementById('tipo').value;
    // 1 = Banner (16:9), 2 = Cuadro (1:1)
    return tipo === '1' ? 16/9 : 1;
}

document.getElementById('imagen').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (!file) return;
    
    const reader = new FileReader();
    reader.onload = function(event) {
        const cropImg = document.getElementById('cropImg');
        cropImg.src = event.target.result;
        document.getElementById('cropperContainer').style.display = 'block';
        
        if (cropper) {
            cropper.destroy();
        }
        
        const aspectRatio = getAspectRatio();
        cropper = new Cropper(cropImg, {
            aspectRatio: aspectRatio,
            autoCropArea: 1,
            responsive: true,
            restore: true,
            guides: true,
            center: true,
            highlight: true,
            cropBoxMovable: true,
            cropBoxResizable: true,
            toggleDragModeOnDblclick: true,
        });
    };
    reader.readAsDataURL(file);
});

document.getElementById('tipo').addEventListener('change', function() {
    if (cropper) {
        const aspectRatio = getAspectRatio();
        cropper.setAspectRatio(aspectRatio);
    }
});

document.getElementById('cropConfirmBtn').addEventListener('click', function(e) {
    e.preventDefault();
    
    if (!cropper) return;
    
    const canvas = cropper.getCroppedCanvas({
        maxWidth: 2560,
        maxHeight: 2560,
        imageSmoothingEnabled: true,
        imageSmoothingQuality: 'high',
    });
    
    const data64 = canvas.toDataURL('image/png');
    document.getElementById('imagenCrop').value = data64;
    
    const previewImg = document.getElementById('previewImg');
    previewImg.src = data64;
    document.getElementById('previewContainer').style.display = 'block';
    document.getElementById('cropperContainer').style.display = 'none';
});

document.getElementById('cropCancelBtn').addEventListener('click', function(e) {
    e.preventDefault();
    
    document.getElementById('imagen').value = '';
    document.getElementById('imagenCrop').value = '';
    document.getElementById('cropperContainer').style.display = 'none';
    document.getElementById('previewContainer').style.display = 'none';
    
    if (cropper) {
        cropper.destroy();
        cropper = null;
    }
});
</script>

<?php
    include("./../layout/footerAdmin.php");
?>