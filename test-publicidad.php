<?php
require_once(__DIR__ . "/views/helpers/urlhelper.php");
require_once(__DIR__ . "/data/conexion.php");

echo "<h2>Test de Publicidades</h2>";
echo "<pre>";

$stmt = $con->prepare("SELECT id_pub, titulo, imagen, tipo FROM publicidad WHERE activo = 1 LIMIT 5");
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    echo "\n=== Publicidad ID: " . $row['id_pub'] . " ===\n";
    echo "Título: " . $row['titulo'] . "\n";
    echo "Tipo: " . ($row['tipo'] == 1 ? 'Banner' : 'Cuadro') . "\n";
    echo "Imagen BD: " . $row['imagen'] . "\n";
    echo "URL generada: " . imageUrl($row['imagen']) . "\n";
}

echo "</pre>";
?>
