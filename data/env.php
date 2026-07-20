<?php
/**
 * Carga variables del archivo .env en $_ENV y getenv()
 */
if (!function_exists('loadEnv')) {
    function loadEnv($path = null) {
        if ($path === null) {
            // 1. Raíz del proyecto (local dev)
            $path = __DIR__ . '/../.env';
            // 2. Un nivel arriba de public_html (producción Hostinger)
            if (!file_exists($path)) {
                $path = $_SERVER['DOCUMENT_ROOT'] . '/../.env';
            }
        }
        if (!file_exists($path)) {
            return;
        }
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#') continue;
            if (strpos($line, '=') === false) continue;
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }
    loadEnv();
}

if (!function_exists('env')) {
    function env($key, $default = null) {
        $valor = $_ENV[$key] ?? getenv($key);
        if ($valor === null || $valor === false || $valor === '') {
            return $default;
        }
        return $valor;
    }
}

$timezone = env('APP_TIMEZONE', 'America/Mexico_City');
if (!empty($timezone)) {
    date_default_timezone_set($timezone);
}

// Ocultar errores en producción
if (env('APP_ENV', 'production') === 'production') {
    error_reporting(0);
    ini_set('display_errors', '0');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
}
