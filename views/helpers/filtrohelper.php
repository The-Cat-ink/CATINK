<?php
// Helper compartido para filtrado de contenido
// Función para filtrar palabras prohibidas con detección de variaciones
function filtrarPalabras($con, $texto) {
    // Evitar N+1 queries cacheando el diccionario en memoria durante la ejecución
    static $diccionario = null;
    if ($diccionario === null) {
        $stmt = $con->prepare("SELECT palabra_baneada, reemplazo FROM filtro_diccionario");
        $stmt->execute();
        $diccionario = [];
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $diccionario[] = $row;
        }
    }

    foreach ($diccionario as $row) {
        $palabra = $row['palabra_baneada'];
        $patron = '';
        
        // Usar mb_strlen para no romper caracteres multibyte (tildes, Ñ, etc.)
        $len = mb_strlen($palabra, 'UTF-8');
        for ($i = 0; $i < $len; $i++) {
            $char = mb_substr($palabra, $i, 1, 'UTF-8');
            
            if ($char === ' ') {
                $patron .= '\\s+';
                continue;
            }
            
            // Variaciones comunes por letra
            switch (mb_strtolower($char, 'UTF-8')) {
                case 'a': $charPatron = '[aá4@]'; break;
                case 'e': $charPatron = '[eé3]'; break;
                case 'i': $charPatron = '[ií1!]'; break;
                case 'o': $charPatron = '[oó0]'; break;
                case 'u': $charPatron = '[uú]'; break;
                case 's': $charPatron = '[s5$]'; break;
                case 'l': $charPatron = '[l1!]'; break;
                case 'g': $charPatron = '[g9]'; break;
                case 'z': $charPatron = '[z2]'; break;
                case 't': $charPatron = '[t7]'; break;
                case 'b': $charPatron = '[b8]'; break;
                default:  $charPatron = preg_quote($char, '/'); break;
            }
            
            // Permitir letras repetidas (ej: pppendejo) y separadores no-alfanuméricos opcionales (ej: p.e.n.d.e.j.o)
            if ($i < $len - 1) {
                $patron .= $charPatron . '+[\\W_]*';
            } else {
                $patron .= $charPatron . '+'; // Última letra no necesita separador
            }
        }
        
        // Usar límites de palabra \b para evitar el problema de Scunthorpe (ej. computadoras)
        $pattern = '/\b' . $patron . '\b/iu';
        $texto = preg_replace($pattern, $row['reemplazo'], $texto);
    }
    
    return $texto;
}
