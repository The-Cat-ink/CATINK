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

// Generar slug a partir de un título (URL amigable)
function generateSlug($titulo){
    // Convertir a minúsculas
    $slug = mb_strtolower($titulo, 'UTF-8');
    // Reemplazar caracteres especiales y espacios con guiones
    $slug = preg_replace('/[áàäâã]/u', 'a', $slug);
    $slug = preg_replace('/[éèëê]/u', 'e', $slug);
    $slug = preg_replace('/[íìïî]/u', 'i', $slug);
    $slug = preg_replace('/[óòöôõ]/u', 'o', $slug);
    $slug = preg_replace('/[úùüû]/u', 'u', $slug);
    $slug = preg_replace('/[ñ]/u', 'n', $slug);
    $slug = preg_replace('/[ç]/u', 'c', $slug);
    $slug = preg_replace('/[^a-z0-9\s-]/u', '', $slug);
    $slug = preg_replace('/[\s-]+/', '-', $slug);
    $slug = trim($slug, '-');
    return $slug;
}
// URLs para noticias - soporta múltiples formatos
function newsUrl($slug){
    // Formato corto y principal: /n/{slug}
    // Fallback a ID codificado si slug es NULL (noticias existentes)
    if ($slug === null || $slug === '') {
        // Intentar obtener ID del contexto global si existe
        global $noticia;
        if (isset($noticia['id'])) {
            return basePath() . "/n/" . encodeId($noticia['id']);
        }
        return basePath() . "/n/";
    }
    return basePath() . "/n/" . $slug;
}
function newsUrlLong($slug){
    // Formato largo más legible: /noticia/{slug}
    if ($slug === null || $slug === '') {
        global $noticia;
        if (isset($noticia['id'])) {
            return basePath() . "/noticia/" . encodeId($noticia['id']);
        }
        return basePath() . "/noticia/";
    }
    return basePath() . "/noticia/" . $slug;
}
function newsUrlAlt($slug){
    // Formato alternativo: /news/{slug}
    if ($slug === null || $slug === '') {
        global $noticia;
        if (isset($noticia['id'])) {
            return basePath() . "/news/" . encodeId($noticia['id']);
        }
        return basePath() . "/news/";
    }
    return basePath() . "/news/" . $slug;
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