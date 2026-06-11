<?php
// Helper compartido para filtrado de contenido
// Función para filtrar palabras prohibidas con detección de variaciones
function filtrarPalabras($con, $texto) {
    $stmt = $con->prepare("SELECT palabra_baneada, reemplazo FROM filtro_diccionario");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $palabra = $row['palabra_baneada'];
        
        // Crear patrón que detecte variaciones (números, caracteres especiales)
        $patron = '';
        for ($i = 0; $i < strlen($palabra); $i++) {
            $char = $palabra[$i];
            
            if ($char === ' ') {
                $patron .= '\\s+';
                continue;
            }
            
            // Agregar variaciones comunes para cada letra
            switch (strtolower($char)) {
                case 'a': $patron .= '[aá4@]'; break;
                case 'e': $patron .= '[eé3]'; break;
                case 'i': $patron .= '[ií1!]'; break;
                case 'o': $patron .= '[oó0]'; break;
                case 'u': $patron .= '[uú]'; break;
                case 's': $patron .= '[s5$]'; break;
                case 'l': $patron .= '[l1!]'; break;
                case 'g': $patron .= '[g9]'; break;
                case 'z': $patron .= '[z2]'; break;
                case 't': $patron .= '[t7]'; break;
                case 'b': $patron .= '[b8]'; break;
                default: $patron .= preg_quote($char, '/'); break;
            }
        }
        
        // Buscar la palabra con variaciones (sin límites de palabra para detectar variaciones)
        $pattern = '/' . $patron . '/iu';
        $texto = preg_replace($pattern, $row['reemplazo'], $texto);
    }
    return $texto;
}
