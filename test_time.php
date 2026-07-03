<?php
include("data/conexion.php");
$slug = "dsadsad-dsdas";
$sql = "
    SELECT n.id, n.titulo, n.slug, n.fecha_publicacion, NOW() as db_now, (n.fecha_publicacion <= NOW()) as is_published
    FROM noticias n
    WHERE n.slug = ?
";
$stmt = $con->prepare($sql);
$stmt->bind_param("s", $slug);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
echo "PHP Date: " . date("Y-m-d H:i:s") . "\n";
echo "PHP Timezone: " . date_default_timezone_get() . "\n";
var_dump($res);
?>
