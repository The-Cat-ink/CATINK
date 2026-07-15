<?php
ob_start();
header('Content-Type: application/json');
session_start();
include("./aclcontroller.php");
proteger('videos','editar');
include("../data/conexion.php");
require_once("../views/helpers/activity_log.php");

$id = $_POST['id_v'] ?? '';
$url = $_POST['url_v'] ?? '';
$activo = $_POST['activo'] ?? 0;
if (empty($id) || empty($url)) {
    echo json_encode([
        'success' => false,
        'error' => 'ID o URL vacía'
    ]);
    exit;
}
$stmt = $con->prepare("UPDATE videos SET url_v = ?, activo = ? WHERE id_v = ?");
$stmt->bind_param("sii", $url, $activo, $id);
if($stmt->execute()){
    logActivity($con, 'editar', 'videos', 'Actualizó video ID ' . $id . ': ' . mb_substr($url, 0, 80));
    echo json_encode(['success' => true]);
} else {
    echo json_encode([
        'success' => false,
        'error' => $stmt->error
    ]);
}
exit;