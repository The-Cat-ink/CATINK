<?php
/**
 * Simple File-based Cache Helper
 * Útil para servidores compartidos donde Redis/Memcached no están garantizados.
 */

function get_cache($key, $ttl_seconds) {
    $cache_dir = __DIR__ . '/../../data/cache/';
    $file = $cache_dir . md5($key) . '.cache';

    if (file_exists($file)) {
        // Verificar TTL
        if ((time() - filemtime($file)) < $ttl_seconds) {
            $data = file_get_contents($file);
            if ($data !== false) {
                return unserialize($data);
            }
        } else {
            // Eliminar si expiró para mantener limpio
            @unlink($file);
        }
    }
    return false;
}

function set_cache($key, $data) {
    $cache_dir = __DIR__ . '/../../data/cache/';
    if (!is_dir($cache_dir)) {
        @mkdir($cache_dir, 0777, true);
    }
    
    $file = $cache_dir . md5($key) . '.cache';
    $temp_file = $file . '.tmp.' . uniqid();
    
    // Escribir en un archivo temporal primero para evitar lecturas corruptas por race conditions
    if (file_put_contents($temp_file, serialize($data), LOCK_EX) !== false) {
        rename($temp_file, $file);
    }
}

function delete_cache($key = '') {
    if (empty($key)) {
        clear_cache_by_prefix();
        return;
    }
    $cache_dir = __DIR__ . '/../../data/cache/';
    $file = $cache_dir . md5($key) . '.cache';
    if (file_exists($file)) {
        @unlink($file);
    }
}

function clear_cache_by_prefix($prefix = '') {
    $cache_dir = __DIR__ . '/../../data/cache/';
    if (!is_dir($cache_dir)) return;
    
    $files = glob($cache_dir . '*.cache');
    foreach ($files as $file) {
        if (is_file($file)) {
            @unlink($file);
        }
    }
}
