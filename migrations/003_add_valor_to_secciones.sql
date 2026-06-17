-- Migración: Añadir columna 'valor' a la tabla 'secciones'
ALTER TABLE `secciones` ADD COLUMN `valor` VARCHAR(255) DEFAULT NULL;

-- Asignar una lista de reproducción de YouTube por defecto para la sección de videos (opcional, ej. PLMC9KNkIncKvYin_USF1QeqG50KB1K1uD)
UPDATE `secciones` SET `valor` = 'PLMC9KNkIncKvYin_USF1QeqG50KB1K1uD' WHERE `nombre` = 'videos';
