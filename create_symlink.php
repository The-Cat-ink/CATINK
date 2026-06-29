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

if (file_exists($link)) {
    if (is_link($link)) {
        echo "<p style='color:green;'>El enlace simbólico ya existe en: <strong>$link</strong></p>";
        echo "<p>Apunta a: <strong>" . readlink($link) . "</strong></p>";
    } else {
        echo "<p style='color:orange;'>Existe un directorio o archivo real en: <strong>$link</strong>. No se puede crear el enlace simbólico encima.</p>";
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
