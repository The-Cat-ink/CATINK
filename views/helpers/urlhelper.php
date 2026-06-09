<?php
function basePath(){
    $host = $_SERVER['HTTP_HOST'];
    // entorno local
    if(strpos($host, 'localhost') !== false){
        return "/CATINK";
    }
    // producción
    return "";
}
function encodeId($id){
    return rtrim(strtr(base64_encode($id), '+/', '-_'), '=');
}
function decodeId($hash){
    return base64_decode(strtr($hash, '-_', '+/'));
}
// URLs para noticias - soporta múltiples formatos
function newsUrl($id){
    // Formato corto y principal: /n/{hash}
    return basePath() . "/n/" . encodeId($id);
}
function newsUrlLong($id){
    // Formato largo más legible: /noticia/{hash}
    return basePath() . "/noticia/" . encodeId($id);
}
function newsUrlAlt($id){
    // Formato alternativo: /news/{hash}
    return basePath() . "/news/" . encodeId($id);
}
// URLs para categorías - soporta múltiples formatos
function categoryUrl($nombre){
    // Formato: /categoria/{nombre}
    return basePath() . "/categoria/" . urlencode($nombre);
}
function categoryUrlLong($nombre){
    // Formato largo: /noticias/categoria/{nombre}
    return basePath() . "/noticias/categoria/" . urlencode($nombre);
}
// URLs para búsqueda - soporta múltiples formatos
function searchUrl($termino){
    // Formato: /buscar/{termino}
    return basePath() . "/buscar/" . urlencode($termino);
}
function searchUrlLong($termino){
    // Formato largo: /noticias/buscar/{termino}
    return basePath() . "/noticias/buscar/" . urlencode($termino);
}
function authorUrl($id){
    return basePath() . "/autor/" . intval($id);
}
function topUrl(){
    return basePath() . "/top";
}
function recientesUrl(){
    return basePath() . "/recientes";
}
function popularUrl(){
    return basePath() . "/popular";
}

// ============================
// FUNCIÓN PARA SERVIR IMÁGENES
// ============================
// Convierte rutas de BD (uploads/noticias/...) a URLs accesibles
function imageUrl($path) {
    if (empty($path)) {
        return basePath() . "/img/placeholder.svg";
    }
    
    // Si ya es una URL completa, devolverla
    if (strpos($path, 'http') === 0) {
        return $path;
    }
    
    // Convertir rutas antiguas de img/ a uploads/
    if (strpos($path, 'img/avatares/') === 0 || strpos($path, 'img/editores/') === 0 || strpos($path, 'img/publicidad/') === 0) {
        // Extraer el nombre del archivo
        $filename = basename($path);
        // Determinar la carpeta correcta
        if (strpos($path, 'img/avatares/') === 0) {
            $path = 'uploads/avatares/' . $filename;
        } else if (strpos($path, 'img/editores/') === 0) {
            $path = 'uploads/editores/' . $filename;
        } else if (strpos($path, 'img/publicidad/') === 0) {
            $path = 'uploads/publicidad/' . $filename;
        }
    }
    
    // Si es una ruta en /img/, servir a través de serve-image.php (para logos, placeholders, etc)
    if (strpos($path, 'img/') === 0) {
        return basePath() . "/serve-image.php?file=" . urlencode($path);
    }
    
    // Si es una ruta en /uploads/, servir a través de serve-image.php
    if (strpos($path, 'uploads/') === 0) {
        return basePath() . "/serve-image.php?file=" . urlencode($path);
    }
    
    // Por defecto, devolver placeholder
    return basePath() . "/img/placeholder.svg";
}