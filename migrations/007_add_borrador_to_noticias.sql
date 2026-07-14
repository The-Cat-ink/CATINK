-- Migración: Borradores de noticias
-- Fecha: 2026-07-10
-- Descripción: Permite guardar una noticia como borrador (trabajo en progreso)
--              sin publicarla. Un borrador NO debe publicarse automáticamente
--              (a diferencia de una noticia programada, que se publica al llegar
--              su fecha).
--
--   * borrador = 1  → la noticia es un borrador y no aparece en el sitio.
--   * fecha_publicacion = NULL para los borradores. Como en SQL `NULL <= NOW()`
--     es falso, los borradores quedan excluidos automáticamente de TODAS las
--     consultas públicas que ya filtran con `fecha_publicacion <= NOW()` sin
--     necesidad de modificarlas. Por eso la columna pasa a aceptar NULL.

ALTER TABLE noticias ADD COLUMN borrador TINYINT(1) NOT NULL DEFAULT 0 AFTER seccion_estreno;

-- Permitir NULL en fecha_publicacion (los borradores aún no tienen fecha)
ALTER TABLE noticias MODIFY fecha_publicacion DATETIME NULL DEFAULT NULL;

-- Índice para listar rápidamente los borradores
CREATE INDEX idx_noticias_borrador ON noticias(borrador);
