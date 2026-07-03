<?php
// Helper compartido para filtrado de contenido

// Carga (y cachea en memoria durante la ejecución) el diccionario de palabras
// baneadas. Evita N+1 queries cuando se llama varias veces en un mismo request.
function cargarDiccionario($con) {
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
    return $diccionario;
}

// Construye el patrón regex tolerante a variaciones (leet, letras repetidas,
// separadores) para una palabra baneada dada.
function construirPatronPalabra($palabra) {
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
    return '/\b' . $patron . '\b/iu';
}

// Función para filtrar palabras prohibidas con detección de variaciones
function filtrarPalabras($con, $texto) {
    foreach (cargarDiccionario($con) as $row) {
        $pattern = construirPatronPalabra($row['palabra_baneada']);
        $texto = preg_replace($pattern, $row['reemplazo'], $texto);
    }

    return $texto;
}

// Devuelve las coincidencias (tal como las escribió el usuario) del diccionario
// dentro del texto. Sirve para informarle exactamente qué palabras se detectaron.
function palabrasBaneadasEncontradas($con, $texto) {
    $encontradas = [];
    foreach (cargarDiccionario($con) as $row) {
        $pattern = construirPatronPalabra($row['palabra_baneada']);
        if (preg_match($pattern, $texto, $m)) {
            $encontradas[] = trim($m[0]);
        }
    }
    return $encontradas;
}
