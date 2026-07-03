<?php
/**
 * Helper de moderación de comentarios usando la API gratuita de Vector Profanity
 */

// Lista de patrones regex de lenguaje ofensivo. Centralizada para que la
// usen tanto la detección (esContenidoInapropiado) como el detalle de qué
// palabras se encontraron (palabrasOfensivasDetectadas).
if (!function_exists('patronesOfensivos')) {
    function patronesOfensivos() {
        return [
            // Autolesión / Suicidio
            '/\b(suicid[a-z]*|matat[e]?|desviv[a-z]*|kys)\b/i',
            // Maricón / Marica
            '/\b(maric[a-z]*)\b/i',
            // Joto
            '/\b(jot[a-z]*)\b/i',
            // Puta / Puto y derivados
            '/\b(put(a|o|ita|ito|azo|it4|h|ero|erio|as|os))\b/i',
            // Verga
            '/\b(verg(a|as|azo|ota|on))\b/i',
            // Pendejo
            '/\b(pendej[a-z]*)\b/i',
            // Cabrón
            '/\b(cabron[a-z]*)\b/i',
            // Culo / Culero
            '/\b(cul[oó](s|ero|era|azo|on)?)\b/i',
            // Mierda
            '/\b(mierd[a-z]*)\b/i',
            // Pito / Pitote
            '/\b(pit[oó](s|te|tes|illo|azo|on)?)\b/i',
            // Polla / Pollón
            '/\b(poll(a|as|on|onas))\b/i',
            // Pelotudo
            '/\b(pelotud[a-z]*)\b/i',
            // Prostituta / Prostitución
            '/\b(prostitu[a-z]*)\b/i',
            // Estúpido
            '/\b(estupid[a-z]*)\b/i',
            // Perra
            '/\b(perr(a|as|illa|illas))\b/i',
            // Cojones
            '/\b(cojon[es]*)\b/i',
            // Pilín
            '/\b(pilin)\b/i',
            // Groserías comunes en inglés
            '/\b(bitch|asshole|fuck|fucker|fucking|shit|shitty|cunt)\b/i',
            // Hostia / Ostia
            '/\b(hostia|ostia)\b/i',
            // Gilipollas
            '/\b(gilipoll[a-z]*|gilipol[a-z]*)\b/i',
            // Hijueputa / Hijo de puta
            '/\b(hijueput[a-z]*|hijoeput[a-z]*|hijo\s+de\s+puta)\b/i',
            // Ñonga / Nonga
            '/\b([ñn]onga)\b/i',
            // Malparido / Mísero
            '/\b(malparid[ao][s]?|miser[ao][s]?)\b/i',
            // Jergas específicas o términos despectivos reportados
            '/\b(amargasaurio|aguada)\b/i'
        ];
    }
}

// Normaliza el texto (minúsculas y sin acentos) para el chequeo por regex.
if (!function_exists('normalizarTextoModeracion')) {
    function normalizarTextoModeracion($texto) {
        $textoNormalizado = mb_strtolower($texto, 'UTF-8');
        $buscarAcentos    = ['á', 'é', 'í', 'ó', 'ú', 'ü', 'ñ'];
        $quitarAcentos    = ['a', 'e', 'i', 'o', 'u', 'u', 'n'];
        return str_replace($buscarAcentos, $quitarAcentos, $textoNormalizado);
    }
}

if (!function_exists('esContenidoInapropiado')) {
    /**
     * Evalúa si un texto contiene lenguaje profano u ofensivo.
     * Realiza un chequeo local en español y complementa con la API de Vector Profanity (threshold de 0.7).
     * Realiza una petición POST a vector.profanity.dev con un timeout estricto de 2 segundos.
     * En caso de error o timeout, realiza una caída suave (fail-soft) retornando false.
     *
     * @param string $texto El contenido del comentario a evaluar.
     * @return bool True si contiene lenguaje ofensivo, False en caso contrario o si falla la API.
     */
    function esContenidoInapropiado($texto, $con = null) {
        $texto = trim($texto);
        if (empty($texto)) {
            return false;
        }

        // 0. Chequeo contra el diccionario completo de la base de datos si la conexión está disponible
        if ($con) {
            require_once(__DIR__ . '/filtrohelper.php');
            if (filtrarPalabras($con, $texto) !== $texto) {
                return true;
            }
        }

        // 1. Chequeo local por expresiones regulares optimizadas (evita falsos positivos como disputa o capítulo)
        $textoNormalizado = normalizarTextoModeracion($texto);
        foreach (patronesOfensivos() as $pattern) {
            if (preg_match($pattern, $textoNormalizado)) {
                return true;
            }
        }

        // 2. Consulta a la API de Vector Profanity
        $url = 'https://vector.profanity.dev';
        $payload = json_encode(['message' => $texto]);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        // Timeout estricto de 2 segundos para evitar retrasar al servidor o al usuario
        curl_setopt($ch, CURLOPT_TIMEOUT, 2);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            $error_msg = curl_error($ch);
            error_log("Moderation API Error (cURL): " . $error_msg);
            curl_close($ch);
            return false; // Fail-soft: permitir
        }

        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code !== 200) {
            error_log("Moderation API Error (HTTP Status $http_code): " . $response);
            return false; // Fail-soft
        }

        $data = json_decode($response, true);
        if (isset($data['isProfanity'])) {
            // Consideramos inapropiado si la API dice que es profano
            // O si la puntuación de profanidad es de 0.7 o superior
            $score = isset($data['score']) ? (float)$data['score'] : 0.0;
            return (bool)$data['isProfanity'] || $score >= 0.7;
        }

        return false;
    }
}

if (!function_exists('palabrasOfensivasDetectadas')) {
    /**
     * Devuelve la lista de palabras/expresiones ofensivas detectadas en el texto
     * (tal como las escribió el usuario), combinando el diccionario de la BD y los
     * patrones locales. Sirve para decirle al usuario exactamente qué se detectó.
     * Nota: si la detección proviene únicamente de la API externa, puede volver vacío.
     *
     * @return string[] Palabras únicas encontradas.
     */
    function palabrasOfensivasDetectadas($texto, $con = null) {
        $texto = trim($texto);
        $encontradas = [];
        if ($texto === '') {
            return $encontradas;
        }

        // Coincidencias del diccionario de la base de datos
        if ($con) {
            require_once(__DIR__ . '/filtrohelper.php');
            foreach (palabrasBaneadasEncontradas($con, $texto) as $p) {
                $encontradas[] = $p;
            }
        }

        // Coincidencias de los patrones locales
        $textoNormalizado = normalizarTextoModeracion($texto);
        foreach (patronesOfensivos() as $pattern) {
            if (preg_match($pattern, $textoNormalizado, $m)) {
                $encontradas[] = trim($m[0]);
            }
        }

        // Normalizar: sin vacíos, sin duplicados (case-insensitive)
        $unicas = [];
        foreach ($encontradas as $p) {
            $p = trim($p);
            if ($p === '') continue;
            $clave = mb_strtolower($p, 'UTF-8');
            $unicas[$clave] = $p;
        }
        return array_values($unicas);
    }
}

if (!function_exists('textoPalabrasOfensivas')) {
    /**
     * Formatea la lista de palabras detectadas para mostrarla al usuario,
     * entre comillas y separadas por comas. Limita la cantidad para no saturar.
     */
    function textoPalabrasOfensivas($palabras, $max = 5) {
        $palabras = array_slice($palabras, 0, $max);
        if (empty($palabras)) {
            return '';
        }
        return implode(', ', array_map(function ($p) {
            return '“' . $p . '”';
        }, $palabras));
    }
}
