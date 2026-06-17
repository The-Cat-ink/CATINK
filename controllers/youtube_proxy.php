<?php
header('Content-Type: application/json');
require_once(__DIR__ . "/../data/conexion.php");
require_once(__DIR__ . "/../data/env.php");

$apiKey = env('YOUTUBE_API_KEY');
if (empty($apiKey)) {
    echo json_encode(["error" => "YouTube API Key not configured"]);
    exit;
}

// 1. Obtener la playlist ID de la base de datos
$res = $con->query("SELECT valor FROM secciones WHERE nombre = 'videos' LIMIT 1");
$row = $res->fetch_assoc();
$rawPlaylist = $row['valor'] ?? '';

function getPlaylistId($url_or_id) {
    if (preg_match('/list=([A-Za-z0-9_-]+)/', $url_or_id, $matches)) {
        return $matches[1];
    }
    return trim($url_or_id);
}

$playlistId = getPlaylistId($rawPlaylist);
if (empty($playlistId)) {
    echo json_encode(["error" => "No playlist ID configured"]);
    exit;
}

// 2. Caching Setup
$cacheDir = __DIR__ . '/../cache/youtube';
if (!file_exists($cacheDir)) {
    mkdir($cacheDir, 0777, true);
}
$cacheFile = $cacheDir . '/playlist_' . md5($playlistId) . '.json';
$cacheTime = 7 * 24 * 60 * 60; // 7 days cache

if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < $cacheTime)) {
    echo file_get_contents($cacheFile);
    exit;
}

// Helper para parsear la duración ISO 8601 a formato H:M:S o M:S
function parseISO8601Duration($duration) {
    try {
        $interval = new DateInterval($duration);
        $hours = $interval->h;
        $minutes = $interval->i;
        $seconds = $interval->s;
        if ($hours > 0) {
            return sprintf('%d:%02d:%02d', $hours, $minutes, $seconds);
        } else {
            return sprintf('%d:%02d', $minutes, $seconds);
        }
    } catch (Exception $e) {
        return "0:00";
    }
}

// 3. Consultar los items de la playlist
$playlistUrl = "https://www.googleapis.com/youtube/v3/playlistItems?part=snippet&maxResults=10&playlistId=" . urlencode($playlistId) . "&key=" . urlencode($apiKey);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $playlistUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    echo json_encode(["error" => "Failed to fetch playlist from YouTube. Code: $httpCode"]);
    exit;
}

$data = json_decode($response, true);
if (empty($data['items'])) {
    echo json_encode(["videos" => []]);
    exit;
}

$videos = [];
$videoIds = [];

foreach ($data['items'] as $item) {
    $yid = $item['snippet']['resourceId']['videoId'] ?? '';
    if (empty($yid)) continue;
    
    $title = $item['snippet']['title'] ?? '';
    $thumb = $item['snippet']['thumbnails']['medium']['url'] ?? 
             $item['snippet']['thumbnails']['default']['url'] ?? 
             "https://img.youtube.com/vi/$yid/mqdefault.jpg";
             
    $videos[$yid] = [
        "id" => $yid,
        "title" => $title,
        "thumbnail" => $thumb,
        "duration" => ""
    ];
    $videoIds[] = $yid;
}

// 4. Consultar las duraciones de los videos recolectados
if (!empty($videoIds)) {
    $idsString = implode(',', $videoIds);
    $videosUrl = "https://www.googleapis.com/youtube/v3/videos?part=contentDetails&id=" . urlencode($idsString) . "&key=" . urlencode($apiKey);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $videosUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $resVideos = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $videosData = json_decode($resVideos, true);
        if (!empty($videosData['items'])) {
            foreach ($videosData['items'] as $vItem) {
                $yid = $vItem['id'] ?? '';
                $isoDuration = $vItem['contentDetails']['duration'] ?? '';
                if (!empty($yid) && isset($videos[$yid])) {
                    $videos[$yid]['duration'] = parseISO8601Duration($isoDuration);
                }
            }
        }
    }
}

$result = [
    "playlistId" => $playlistId,
    "videos" => array_values($videos)
];

$output = json_encode($result);
file_put_contents($cacheFile, $output);
echo $output;
