-- Migración: Posiciones (huecos) de publicidad
-- Fecha: 2026-07-15
-- Descripción: Permite elegir en qué posiciones exactas del sitio se muestra
--              cada anuncio (portada: tras carrusel / arriba de estrenos / etc.,
--              publicación: inicio / medio / final / lateral).
--
--   Regla de negocio: un anuncio SIN filas aquí es "random" y es elegible para
--   cualquier hueco que coincida con su forma (tipo). Un anuncio CON filas solo
--   aparece en esas posiciones. La forma (largo/cuadrado) la sigue dando
--   `publicidad.tipo`; las posiciones son coherentes con esa forma.

CREATE TABLE IF NOT EXISTS `publicidad_posicion` (
  `publicidad_id` int(11) NOT NULL,
  `posicion` varchar(40) NOT NULL,
  PRIMARY KEY (`publicidad_id`, `posicion`),
  KEY `idx_pub_posicion` (`posicion`),
  CONSTRAINT `fk_pub_posicion_pub` FOREIGN KEY (`publicidad_id`) REFERENCES `publicidad` (`id_pub`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
