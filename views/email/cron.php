<?php
/**
 * Cron Master Orchestrator para CatInk
 * Ejecuta la programación diaria de noticias y la emisión de correos publicitarios.
 * 
 * Uso vía Cron cPanel o CLI:
 *   php /ruta/a/catink/views/email/cron.php
 * 
 * Uso vía HTTP:
 *   https://www.catink.com.mx/views/email/cron.php?key=TU_CLAVE
 */
date_default_timezone_set("America/Mexico_City");

// Validar clave de seguridad si se accede vía web
$cronKey = getenv('CRON_KEY') ?: 'catink_cron_secret';
if (php_sapi_name() !== 'cli') {
    $providedKey = $_GET['key'] ?? '';
    if (!empty($cronKey) && $providedKey !== $cronKey && getenv('APP_ENV') === 'production') {
        http_response_code(403);
        die("Acceso no autorizado.\n");
    }
}

header('Content-Type: text/plain; charset=utf-8');

echo "==================================================\n";
echo "INICIO DE CRON CATINK: " . date("Y-m-d H:i:s") . "\n";
echo "==================================================\n\n";

echo "--- [1] EJECUTANDO RESUMEN DIARIO DE NOTICIAS ---\n";
try {
    require __DIR__ . "/correo.php";
} catch (Throwable $e) {
    echo "ERROR en correo.php: " . $e->getMessage() . "\n";
}

echo "\n--- [2] EJECUTANDO CORREOS PUBLICITARIOS PROGRAMADOS ---\n";
try {
    require __DIR__ . "/correoPublicidad.php";
} catch (Throwable $e) {
    echo "ERROR en correoPublicidad.php: " . $e->getMessage() . "\n";
}

echo "\n==================================================\n";
echo "FIN DE CRON CATINK: " . date("Y-m-d H:i:s") . "\n";
echo "==================================================\n";
