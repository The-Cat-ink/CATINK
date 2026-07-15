-- Migración: Comentarios anidados (respuestas)
-- Fecha: 2026-07-15
-- Descripción: Permite responder a un comentario. Se agrega `parent_id`, que
--              apunta al comentario raíz del hilo (NULL = comentario de primer
--              nivel). Se usa un modelo de hilo de UN nivel: responder a una
--              respuesta ancla al mismo comentario raíz (no crea niveles más
--              profundos), lo que mantiene la UI legible en móvil. El contexto
--              de a quién se responde se conserva con una mención @usuario en el
--              texto.

ALTER TABLE `comentarios` ADD COLUMN IF NOT EXISTS `parent_id` int(11) DEFAULT NULL AFTER `usuario_id`;
ALTER TABLE `comentarios` ADD KEY `idx_comentarios_parent` (`parent_id`);
