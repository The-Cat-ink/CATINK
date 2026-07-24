<?php
include_once(__DIR__ . "/../layout/header.php");
include_once(__DIR__ . "/../data/conexion.php");

$result = @$con->query("SELECT contenido_pag FROM paginas WHERE nombre_pag='cookies'");
$row = ($result && $result !== true && method_exists($result, 'fetch_assoc')) ? $result->fetch_assoc() : ['contenido_pag' => ''];
?>
<div class="container-fluid">
    <div class="post-content">
        <div class="ql-editor">
            <?php echo $row['contenido_pag']; ?>
        </div>
    </div>
</div>
<?php
include("./../layout/footer.php");
?>