<?php
// ============================================================
// Helper de publicidad: posiciones (huecos) + selección de anuncio
// ============================================================
// La forma del anuncio la da `publicidad.tipo` (1 = largo/horizontal,
// 2 = cuadrado). Las posiciones son coherentes con esa forma.

if (!function_exists('posicionesPublicidad')) {
    /**
     * Catálogo de posiciones agrupadas por forma. La clave se guarda en
     * `publicidad_posicion.posicion`; el valor es la etiqueta para el admin.
     */
    function posicionesPublicidad() {
        return [
            'largo' => [ // tipo 1
                'home_top'       => 'Portada: tras el carrusel',
                'home_estrenos'  => 'Portada: arriba de Estrenos',
                'home_novedades' => "Portada: arriba de 'No te lo pierdas'",
                'home_recientes' => 'Portada: debajo de Recientes',
                'pub_inicio'     => 'Publicación: inicio',
                'pub_medio'      => 'Publicación: en medio',
                'pub_final'      => 'Publicación: final',
            ],
            'cuadrado' => [ // tipo 2
                'lateral'        => 'Laterales (sidebar)',
                'home_lateral'   => 'Portada: junto a banners (index)',
            ],
        ];
    }

    /** Devuelve el grupo de posiciones ('largo' | 'cuadrado') según el tipo. */
    function formaPublicidad($tipo) {
        return ((int)$tipo === 2) ? 'cuadrado' : 'largo';
    }
}

if (!function_exists('obtenerPublicidad')) {
    /**
     * Selecciona un anuncio para una posición concreta.
     *
     * Reglas:
     *  - Debe estar activo y vigente (fecha_inicio <= ahora <= fecha_fin).
     *  - Debe coincidir con el tipo (forma) del hueco.
     *  - Es elegible si tiene asignada ESA posición, O si no tiene ninguna
     *    posición asignada (pool "random").
     *  - Entre los elegibles se elige uno al azar.
     *
     * @return array|null Fila de publicidad o null si no hay ninguno.
     */
    function obtenerPublicidad($con, $posicion, $tipo, $excludeIds = []) {
        $tipo = (int)$tipo;
        
        if (!is_array($excludeIds)) {
            $excludeIds = $excludeIds ? [$excludeIds] : [];
        }
        $excludeIds = array_map('intval', $excludeIds);
        
        $excludeCond = "";
        if (!empty($excludeIds)) {
            $idsStr = implode(',', $excludeIds);
            $excludeCond = " AND p.id_pub NOT IN ($idsStr) ";
        }
        
        $sql = "
            SELECT p.*
            FROM publicidad p
            WHERE p.activo = 1
              AND p.tipo = ?
              AND (p.fecha_inicio IS NULL OR p.fecha_inicio <= NOW())
              AND (p.fecha_fin    IS NULL OR p.fecha_fin    >= NOW())
              AND (
                    EXISTS (SELECT 1 FROM publicidad_posicion pp
                             WHERE pp.publicidad_id = p.id_pub AND pp.posicion = ?)
                 OR NOT EXISTS (SELECT 1 FROM publicidad_posicion pp2
                                 WHERE pp2.publicidad_id = p.id_pub)
                   )
              $excludeCond
            ORDER BY RAND()
            LIMIT 1
        ";
        $stmt = $con->prepare($sql);
        if (!$stmt) return null;
        $stmt->bind_param("is", $tipo, $posicion);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
}
