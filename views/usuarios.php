<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "1 - Iniciando usuarios.php<br>";

include("./../layout/headerAdmin.php");
echo "2 - Header cargado<br>";

include("./../views/helpers/urlhelper.php");
echo "3 - urlhelper cargado<br>";

include("./../data/conexion.php");
echo "4 - Conexión establecida<br>";

$stmt = $con->prepare("SELECT * FROM usuarios LIMIT 1");
$stmt->execute();
$res = $stmt->get_result();
$usuarios = $res->fetch_all(MYSQLI_ASSOC);

echo "5 - Usuarios encontrados: " . count($usuarios) . "<br>";
echo "6 - Fin del script<br>";
?>
<div class="container-fluid">
    <h1>Usuarios</h1>
    <p>Si ves esto, el script funcionó.</p>
</div>
<?php include("./../layout/footerAdmin.php"); ?>