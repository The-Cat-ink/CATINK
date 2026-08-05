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
// URL absoluta del sitio (https://dominio + subcarpeta), para enlaces que
// viajan fuera del navegador: correos, sitemap, metadatos.
function siteUrl(){
    $host = $_SERVER['HTTP_HOST'] ?? '';
    if (strpos($host, 'localhost') !== false) {
        $esquema = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        return $esquema . '://' . $host . basePath();
    }
    // En producción la URL es fija a propósito. No se deduce de HTTP_HOST,
    // que lo controla quien hace la petición: podría pedir un reset con otra
    // cabecera Host y recibir el enlace apuntando a un dominio suyo.
    $url = function_exists('env') ? env('APP_URL', 'https://www.catink.com.mx') : 'https://www.catink.com.mx';
    return rtrim($url, '/');
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
function newsUrl($slugOrId){
    // Formato corto y principal: /n/{slug}
    // Si es un ID numérico (fallback de noticias sin slug), codificarlo
    if ($slugOrId === null || $slugOrId === '') {
        return basePath() . "/n/";
    }
    // Si es un número (ID sin slug), codificarlo como base64 URL-safe
    if (is_numeric($slugOrId)) {
        return basePath() . "/n/" . encodeId(intval($slugOrId));
    }
    return basePath() . "/n/" . $slugOrId;
}
// Helper para generar URL desde una fila completa de noticia
function newsUrlFromRow($row){
    $slug = $row['slug'] ?? null;
    $id = $row['id'] ?? null;
    // Si existe slug, usarlo
    if ($slug !== null && $slug !== '') {
        return basePath() . "/n/" . $slug;
    }
    // Si no, usar ID codificado
    if ($id !== null) {
        return basePath() . "/n/" . encodeId($id);
    }
    return basePath() . "/n/";
}
function newsUrlLong($slugOrId){
    // Formato largo más legible: /noticia/{slug}
    if ($slugOrId === null || $slugOrId === '') {
        return basePath() . "/noticia/";
    }
    if (is_numeric($slugOrId)) {
        return basePath() . "/noticia/" . encodeId(intval($slugOrId));
    }
    return basePath() . "/noticia/" . $slugOrId;
}
function newsUrlAlt($slugOrId){
    // Formato alternativo: /news/{slug}
    if ($slugOrId === null || $slugOrId === '') {
        return basePath() . "/news/";
    }
    if (is_numeric($slugOrId)) {
        return basePath() . "/news/" . encodeId(intval($slugOrId));
    }
    return basePath() . "/news/" . $slugOrId;
}
// URLs para categorías - soporta múltiples formatos
function categoryUrl($nombre){
    // Formato: /categoria/{nombre}
    // (string) evita el deprecado de urlencode(null) en PHP 8.1
    return basePath() . "/categoria/" . urlencode((string)$nombre);
}
function categoryUrlLong($nombre){
    // Formato largo: /noticias/categoria/{nombre}
    return basePath() . "/noticias/categoria/" . urlencode((string)$nombre);
}
// URLs para búsqueda - soporta múltiples formatos
function searchUrl($termino){
    // Formato: /buscar/{termino}
    return basePath() . "/buscar/" . urlencode((string)$termino);
}
function searchUrlLong($termino){
    // Formato largo: /noticias/buscar/{termino}
    return basePath() . "/noticias/buscar/" . urlencode((string)$termino);
}
function authorUrl($id){
    return basePath() . "/autor/" . intval($id);
}
// Perfil público de un lector (comentarista)
function readerUrl($id){
    return basePath() . "/usuario/" . intval($id);
}
// Devuelve la URL del perfil público del autor de un comentario.
// Editores → /autor/{id}, lectores → /usuario/{id}. null si no hay autor.
function commentAuthorUrl($com){
    if (!empty($com['usuario_id'])) {
        return authorUrl($com['usuario_id']);
    }
    if (!empty($com['lector_id'])) {
        return readerUrl($com['lector_id']);
    }
    return null;
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
    
    // Mapear rutas de publicidad antiguas a la nueva carpeta segura de ad-blockers
    if (strpos($path, 'uploads/publicidad/') === 0) {
        $path = str_replace('uploads/publicidad/', 'uploads/spots/', $path);
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
            $path = 'uploads/spots/' . $filename;
        }
    }
    
    // Si es una ruta en /img/, servir directamente (siempre en la carpeta pública)
    if (strpos($path, 'img/') === 0) {
        return basePath() . "/" . $path;
    }
    
    // Si es una ruta en /uploads/
    if (strpos($path, 'uploads/') === 0) {
        $host = $_SERVER['HTTP_HOST'] ?? '';
        $isLocal = strpos($host, 'localhost') !== false || strpos($host, 'catink.test') !== false;
        
        // Comprobar si el archivo físico realmente existe en la carpeta pública (ya sea por symlink o copiado)
        $publicFilePath = isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] . '/' . $path : '';
        $existsInPublic = !empty($publicFilePath) && file_exists($publicFilePath) && is_file($publicFilePath);
        
        if ($isLocal || $existsInPublic) {
            return basePath() . "/" . $path;
        }
        
        // Fallback a serve-image.php en producción si el archivo no está en la carpeta pública
        return basePath() . "/serve-image.php?file=" . urlencode($path);
    }
    
    // Por defecto, devolver placeholder
    return basePath() . "/img/placeholder.svg";
}