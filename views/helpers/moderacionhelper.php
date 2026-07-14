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

// ============================================================
// MATCHING DIFUSO (Levenshtein) ACOTADO A INSULTOS DUROS
// ------------------------------------------------------------
// El diccionario de la BD + los patrones regex ya cubren leet, letras
// repetidas y separadores para su lista de palabras. Lo que NO cubren son
// las variaciones por typo/evasión (letras omitidas, insertadas, cambiadas
// o transpuestas: "pndejo", "pendejp", "cabroon"). Para eso usamos
// Levenshtein, pero SOLO contra una lista curada de groserías inequívocas.
// Aplicar difuso sobre las ~1358 palabras de la BD (muchas cortas o de salud:
// "acre", "abuso", "alcohol"...) dispararía muchísimos falsos positivos.
// ============================================================

// Insultos duros en forma base normalizada (minúsculas, sin acentos, sin leet)
// mapeados a la distancia de Levenshtein máxima permitida para cada uno.
// La tolerancia es POR PALABRA (no global) para evitar colisiones con palabras
// legítimas cercanas. Regla usada al curar: se deja 0 (solo exacto tras
// normalizar) cuando existe una palabra común a distancia 1 (p. ej. mamada↔manada,
// verga↔verja, joto↔foto, mamon↔jamon); se sube a 1-2 solo cuando no hay colisión.
if (!function_exists('insultosDurosBase')) {
    function insultosDurosBase() {
        return [
            'pendejo'       => 1,
            'pendeja'       => 1,
            'pendejada'     => 2,
            'pendejadas'    => 2,
            'puta'          => 0,
            'puto'          => 0,
            'putazo'        => 1,
            'putona'        => 1,
            'verga'         => 0,   // colisiona con "verja"
            'vergazo'       => 1,
            'vergota'       => 1,
            'cabron'        => 1,
            'cabrona'       => 1,
            'cabronazo'     => 2,
            'culero'        => 1,
            'culera'        => 1,
            'mierda'        => 1,
            'pinche'        => 0,   // colisiona con "pincha"
            'maricon'       => 1,
            'marica'        => 0,
            'gilipollas'    => 2,
            'gilipolla'     => 2,
            'malparido'     => 2,
            'malparida'     => 2,
            'hijueputa'     => 2,
            'hijoeputa'     => 2,
            'chingada'      => 1,
            'chingado'      => 1,
            'chingar'       => 1,
            'chingas'       => 1,
            'chingon'       => 1,
            'mamada'        => 0,   // colisiona con "manada"
            'mamadas'       => 0,   // colisiona con "manadas"
            'mamon'         => 0,   // colisiona con "jamon"
            'pelotudo'      => 1,
            'pelotuda'      => 1,
            'boludo'        => 1,
            'estupido'      => 1,
            'estupida'      => 1,
            'imbecil'       => 1,
            'conchatumadre' => 2,
        ];
    }
}

// Normaliza para el matching difuso: minúsculas, sin acentos/ñ, leet→letra base,
// solo letras y espacios, y colapsa letras repetidas ("puuuta" → "puta").
if (!function_exists('normalizarParaDifuso')) {
    function normalizarParaDifuso($texto) {
        $t = mb_strtolower($texto, 'UTF-8');
        $t = str_replace(
            ['á', 'é', 'í', 'ó', 'ú', 'ü', 'ñ'],
            ['a', 'e', 'i', 'o', 'u', 'u', 'n'],
            $t
        );
        // Mapear leet/símbolos comunes a su letra base
        $t = strtr($t, [
            '4' => 'a', '@' => 'a', '3' => 'e', '1' => 'i', '!' => 'i',
            '0' => 'o', '5' => 's', '$' => 's', '7' => 't', '8' => 'b',
            '9' => 'g', '2' => 'z',
        ]);
        // Dejar solo letras a-z y espacios (lo demás pasa a separador)
        $t = preg_replace('/[^a-z\s]/', ' ', $t);
        // Colapsar cualquier letra repetida 2+ veces a una sola
        $t = preg_replace('/(.)\1+/', '$1', $t);
        // Colapsar espacios
        $t = preg_replace('/\s+/', ' ', $t);
        return trim($t);
    }
}

// Devuelve los insultos duros detectados por similitud difusa en el texto.
// Tras normalizar, todo es ASCII a-z, por lo que strlen() == nº de caracteres
// y levenshtein() (que trabaja por bytes) es correcto y seguro.
if (!function_exists('insultosDurosDetectados')) {
    function insultosDurosDetectados($texto) {
        $norm = normalizarParaDifuso($texto);
        if ($norm === '') {
            return [];
        }
        $tokens = explode(' ', $norm);
        $lista  = insultosDurosBase();
        $hits   = [];

        foreach ($tokens as $tok) {
            if ($tok === '' || strlen($tok) < 3) {
                continue; // tokens muy cortos generan falsos positivos
            }
            foreach ($lista as $base => $maxDist) {
                // Si la diferencia de longitud ya excede el umbral, es imposible
                // que la distancia sea <= maxDist: descartar sin calcular.
                if (abs(strlen($base) - strlen($tok)) > $maxDist) {
                    continue;
                }
                if ($maxDist === 0) {
                    if ($tok === $base) {
                        $hits[$base] = $base;
                        break;
                    }
                } elseif (levenshtein($tok, $base) <= $maxDist) {
                    $hits[$base] = $base;
                    break;
                }
            }
        }

        return array_values($hits);
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

        // 1b. Matching difuso (Levenshtein) contra la lista curada de insultos duros
        if (!empty(insultosDurosDetectados($texto))) {
            return true;
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

        // Coincidencias por similitud difusa (Levenshtein) de insultos duros.
        // Se reporta la forma base canónica (p. ej. "pndejo" → "pendejo").
        foreach (insultosDurosDetectados($texto) as $p) {
            $encontradas[] = $p;
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
