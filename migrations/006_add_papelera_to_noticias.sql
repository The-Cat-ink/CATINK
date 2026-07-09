-- Migración: Papelera de noticias (soft delete)
-- Fecha: 2026-07-07
-- Descripción: En lugar de borrar físicamente una noticia, se marca como eliminada.
--              Guarda quién la eliminó (eliminado_por) y cuándo (eliminado_en).
--              Solo el superadmin puede restaurarla o eliminarla definitivamente.

ALTER TABLE noticias ADD COLUMN eliminado_en DATETIME NULL DEFAULT NULL AFTER ultima_edicion;
ALTER TABLE noticias ADD COLUMN eliminado_por INT NULL DEFAULT NULL AFTER eliminado_en;

ALTER TABLE noticias
    ADD CONSTRAINT fk_noticias_eliminado_por
    FOREIGN KEY (eliminado_por) REFERENCES usuarios(id_u) ON DELETE SET NULL;

-- Índice para filtrar rápidamente las noticias activas (eliminado_en IS NULL)
CREATE INDEX idx_noticias_eliminado_en ON noticias(eliminado_en);
