-- Migración: Agregar campos de auditoría a tabla noticias
-- Fecha: 2026-06-03
-- Descripción: Rastrear quién creó y editó cada noticia, y cuándo

ALTER TABLE noticias ADD COLUMN creado_por INT NULL AFTER fecha_publicacion;
ALTER TABLE noticias ADD COLUMN editado_por INT NULL AFTER creado_por;
ALTER TABLE noticias ADD COLUMN ultima_edicion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER editado_por;

ALTER TABLE noticias ADD CONSTRAINT fk_noticias_creado_por FOREIGN KEY (creado_por) REFERENCES usuarios(id_u) ON DELETE SET NULL;
ALTER TABLE noticias ADD CONSTRAINT fk_noticias_editado_por FOREIGN KEY (editado_por) REFERENCES usuarios(id_u) ON DELETE SET NULL;
