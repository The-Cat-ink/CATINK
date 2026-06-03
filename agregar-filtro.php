<?php
require_once(__DIR__ . "/data/conexion.php");

echo "<h2>Agregar Palabras al Filtro</h2>";
echo "<pre>";

// Palabras prohibidas comunes en español
$palabras = [
    ['palabra' => 'pendejo', 'reemplazo' => '***'],
    ['palabra' => 'idiota', 'reemplazo' => '***'],
    ['palabra' => 'imbécil', 'reemplazo' => '***'],
    ['palabra' => 'estúpido', 'reemplazo' => '***'],
    ['palabra' => 'mierda', 'reemplazo' => '***'],
    ['palabra' => 'puta', 'reemplazo' => '***'],
    ['palabra' => 'puto', 'reemplazo' => '***'],
    ['palabra' => 'cabrón', 'reemplazo' => '***'],
    ['palabra' => 'bastardo', 'reemplazo' => '***'],
    ['palabra' => 'hijo de puta', 'reemplazo' => '***'],
    ['palabra' => 'maricón', 'reemplazo' => '***'],
    ['palabra' => 'boludo', 'reemplazo' => '***'],
    ['palabra' => 'pelotudo', 'reemplazo' => '***'],
    ['palabra' => 'boludo', 'reemplazo' => '***'],
    ['palabra' => 'choto', 'reemplazo' => '***'],
];

$stmt = $con->prepare("INSERT INTO filtro_diccionario (palabra_baneada, reemplazo) VALUES (?, ?)");

$count = 0;
foreach ($palabras as $p) {
    $stmt->bind_param("ss", $p['palabra'], $p['reemplazo']);
    if ($stmt->execute()) {
        echo "✓ Agregada: " . $p['palabra'] . "\n";
        $count++;
    } else {
        echo "✗ Error al agregar: " . $p['palabra'] . "\n";
    }
}

echo "\nTotal agregadas: $count\n";
echo "</pre>";
?>
