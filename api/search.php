<?php
require_once(__DIR__ . "/../data/conexion.php");
require_once(__DIR__ . "/../views/helpers/urlhelper.php");

header('Content-Type: application/json');

$q = isset($_GET['q']) ? trim($_GET['q']) : '';

if(strlen($q) < 2){
    echo json_encode([]);
    exit;
}

$words = array_filter(explode(' ', $q));
if (empty($words)) {
    echo json_encode([]);
    exit;
}

$searchTerms = [];
foreach ($words as $word) {
    $wordClean = preg_replace('/[+\-><()~*\"@]+/', '', $word);
    if (strlen($wordClean) >= 2) {
        $searchTerms[] = "+{$wordClean}*";
    }
}

if (empty($searchTerms)) {
    $firstWord = preg_replace('/[+\-><()~*\"@]+/', '', reset($words));
    if (strlen($firstWord) > 0) {
        $searchTerms[] = "+{$firstWord}*";
    }
}

$searchQuery = implode(' ', $searchTerms);

$stmt = $con->prepare("
    SELECT id, titulo, slug, crop3 
    FROM noticias 
    WHERE MATCH(titulo, descripcion, contenido) AGAINST(? IN BOOLEAN MODE)
    ORDER BY id DESC
    LIMIT 10
");

$stmt->bind_param("s", $searchQuery);
$stmt->execute();
$result = $stmt->get_result();

$data = [];

while($row = $result->fetch_assoc()){
    $data[] = [
        "titulo" => $row['titulo'],
        "imagen" => imageUrl($row['crop3']),
        "url" => newsUrlFromRow($row)
    ];
}

echo json_encode($data);