<?php
require_once(__DIR__ . "/data/conexion.php");

echo "<h2>Test de Filtro de Palabras</h2>";
echo "<pre>";

// Verificar si la tabla existe
$stmt = $con->prepare("SELECT COUNT(*) as count FROM filtro_diccionario");
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

echo "Total de palabras baneadas: " . $result['count'] . "\n\n";

if ($result['count'] > 0) {
    echo "Palabras baneadas:\n";
    $stmt = $con->prepare("SELECT palabra_baneada, reemplazo FROM filtro_diccionario LIMIT 10");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        echo "  - '" . $row['palabra_baneada'] . "' → '" . $row['reemplazo'] . "'\n";
    }
} else {
    echo "⚠️ No hay palabras baneadas en la BD\n";
}

echo "</pre>";
?>
