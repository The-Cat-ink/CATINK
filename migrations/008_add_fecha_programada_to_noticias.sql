-- Migración: Fecha programada en borradores
-- Fecha: 2026-07-13
-- Descripción: Guarda la fecha/hora de publicación que el editor eligió en el
--              programador MIENTRAS la nota todavía es un borrador.
--
--   No se puede reutilizar `fecha_publicacion` para esto: los borradores la
--   tienen en NULL a propósito, porque las consultas públicas filtran con
--   `fecha_publicacion <= NOW()` y un borrador con fecha se publicaría solo al
--   llegar la hora. `fecha_programada` es solo un apunte: no la lee ninguna
--   consulta pública, únicamente repuebla el programador al continuar el
--   borrador. Al guardar desde el editor, la fecha pasa a `fecha_publicacion`
--   (borrador = 0) y esta columna se limpia.

ALTER TABLE noticias ADD COLUMN fecha_programada DATETIME NULL DEFAULT NULL AFTER borrador;
