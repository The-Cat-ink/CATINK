<?php
/**
 * Script para crear el enlace simbólico (symlink) en producción en Hostinger.
 * Esto permite servir los archivos de uploads directamente desde el servidor web,
 * evitando que cada imagen pase por el script PHP serve-image.php, reduciendo
 * drásticamente el tiempo de carga del sitio.
 */

$target = '/home/u780114275/domains/catink.com.mx/uploads';
$link = '/home/u780114275/domains/catink.com.mx/public_html/uploads';

echo "<h3>Configurador de Enlace Simbólico (Symlink)</h3>";

// Si existe un directorio real (no un enlace), lo renombramos como respaldo
if (file_exists($link) && !is_link($link)) {
    $backupLink = $link . '_backup_' . time();
    if (rename($link, $backupLink)) {
        echo "<p style='color:orange;'>Se detectó un directorio real existente en: <strong>$link</strong>.</p>";
        echo "<p style='color:orange;'>Se ha renombrado temporalmente a: <strong>$backupLink</strong> para liberar la ruta.</p>";
    } else {
        echo "<p style='color:red;'>Error: No se pudo renombrar el directorio existente. Intenta renombrarlo o borrarlo manualmente vía FTP/Administrador de archivos.</p>";
        exit;
    }
}

if (file_exists($link)) {
    if (is_link($link)) {
        echo "<p style='color:green;'>El enlace simbólico ya existe en: <strong>$link</strong></p>";
        echo "<p>Apunta a: <strong>" . readlink($link) . "</strong></p>";
    } else {
        echo "<p style='color:red;'>El archivo o enlace sigue existiendo en $link.</p>";
    }
} else {
    // Intentar crear el enlace simbólico
    if (symlink($target, $link)) {
        echo "<p style='color:green;'><strong>¡Enlace simbólico creado con éxito!</strong></p>";
        echo "<p>De: <strong>$link</strong> -> apuntando a: <strong>$target</strong></p>";
    } else {
        echo "<p style='color:red;'>Error: No se pudo crear el enlace simbólico. Verifica los permisos de escritura en la carpeta public_html.</p>";
    }
}
?>
