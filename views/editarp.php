<?php
    include("./../layout/headerAdmin.php");
    include("./../controllers/aclcontroller.php");
    $ACL = $_SESSION['ACL']['publicidad'] ?? [
        "crear" => false,
        "leer" => false,
        "editar" => false,
        "eliminar" => false
    ];
    if (!$ACL['editar']) {
        header("Location: publicidad.php");
        exit;
    }
    proteger('publicidad','editar');
    include("./../data/conexion.php");
    if (!isset($_GET['id'])) {
        header("Location: publicidad.php");
        exit;
    }
    $id_pub = $_GET['id'];
    // Obtener datos de la publicidad
    $stmt = $con->prepare("SELECT * FROM publicidad WHERE id_pub = ?");
    $stmt->bind_param("i", $id_pub);
    $stmt->execute();
    $result = $stmt->get_result();
    $publicidad = $result->fetch_assoc();

    if (!$publicidad) {
        header("Location: publicidad.php");
        exit;
    }
    // Obtener categorías seleccionadas
    $stmtCat = $con->prepare("SELECT categoria_id FROM publicidad_categoria WHERE publicidad_id = ?");
    $stmtCat->bind_param("i", $id_pub);
    $stmtCat->execute();
    $resultCat = $stmtCat->get_result();
    $categoriasSeleccionadas = [];
    while ($row = $resultCat->fetch_assoc()) {
        $categoriasSeleccionadas[] = $row['categoria_id'];
    }
    // Obtener todas las categorías
    $categoriasResult = $con->query("SELECT id_c, nombre FROM categorias ORDER BY nombre ASC");
    $categorias = [];
    while($row = $categoriasResult->fetch_assoc()){
        $categorias[] = $row;
    }
?>
<script>
    const ACL = <?= json_encode($ACL) ?>;
</script>
<div class="container">
    <h2>Editar Publicidad</h2>
    <div class="mt-3">
        <a href="./../views/publicidad.php" class="btn btn-secondary"><i class="bi bi-arrow-return-left"></i> Volver</a>
    </div>
    <form action="./../../controllers/editar_publicidad.php" method="POST" enctype="multipart/form-data">
        <div class="form-card card">
            <input type="hidden" name="id_pub" value="<?= $publicidad['id_pub'] ?>">
            
            <div class="form-group">
                <label for="Titulo" >Título</label>
                <input type="text" id="Titulo" name="Titulo" value="<?= htmlspecialchars($publicidad['titulo']) ?>" required>
            </div>
            <div class="form-group">
                <label for="tipo">Tipo de publicidad</label>
                <!-- Nota: En guardar_publicidad.php no veo que se guarde el 'tipo' en la BD explícitamente en el INSERT mostrado, 
                     pero estaba en el formulario. Asumiré que tal vez no se guardó o falta la columna. 
                     Si existe la columna en la BD, debería preseleccionarse. 
                     Por ahora dejaré el select genérico. -->
                <select id="tipo" name="tipo" required>
                    <?php if ($publicidad['tipo'] == 1): ?>
                        <option value="1" selected>Banner Publicitario</option>
                        <option value="2">Cuadro Publicitario</option>
                    <?php else: ?>
                        <option value="2" selected>Cuadro Publicitario</option>
                        <option value="1">Banner Publicitario</option>
                    <?php endif; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="imagen">Imagen Actual</label>
                <div>
                    <img src="<?= imageUrl($publicidad['imagen']) ?>" alt="Imagen Actual" style="max-width: 200px; margin-bottom: 10px;" loading="lazy" decoding="async">
                </div>
                <label for="imagen">Cambiar Imagen (Opcional)</label>
                <input type="file" id="imagen" name="imagen" accept="image/*">
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
                <input type="text" id="url" name="url" value="<?= htmlspecialchars($publicidad['url']) ?>" required>
            </div>
            <div class="form-group">
                <label for="estado" >Estado</label>
                <span>Activo o Inactivo</span>
                <select id="estado" name="estado" required>
                    <option value="1" <?= $publicidad['activo'] == 1 ? 'selected' : '' ?>>Activo</option>
                    <option value="0" <?= $publicidad['activo'] == 0 ? 'selected' : '' ?>>Inactivo</option>
                </select>
            </div>
            <div class="form-group">
                <label for="Categorias">Categorías</label>
                <div class="checkbox-group">
                    <?php
                        foreach($categorias as $c):
                            $checked = in_array($c['id_c'], $categoriasSeleccionadas) ? 'checked' : '';
                    ?>
                        <label>
                            <input type="checkbox" name="Categorias[]" value="<?=$c['id_c']?>" <?= $checked ?>>
                            <?=$c['nombre']?>
                        </label>
                    <?php
                        endforeach;
                    ?>
                </div>
            </div>
            <div class="form-group">
                <label for="fechaInicio">Fecha de inicio</label>
                <input type="datetime-local" id="fechaInicio" name="fechaInicio" value="<?= date('Y-m-d\TH:i', strtotime($publicidad['fecha_inicio'])) ?>" required>
                <label for="fechaFin">Fecha de fin</label>
                <input type="datetime-local" id="fechaFin" name="fechaFin" value="<?= date('Y-m-d\TH:i', strtotime($publicidad['fecha_fin'])) ?>" required>
            </div>
            <div class="form-actions">
                <?php if ($ACL['editar']): ?>
                    <button type="submit" class="btn btn-accent" name="actualizarPublicidad">
                        Actualizar publicidad
                    </button>
                <?php endif; ?>
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
