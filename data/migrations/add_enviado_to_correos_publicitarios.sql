-- Migración para añadir soporte de estado de envío a correos_publicitarios
ALTER TABLE `correos_publicitarios` 
ADD COLUMN IF NOT EXISTS `enviado` TINYINT(1) NOT NULL DEFAULT 0,
ADD COLUMN IF NOT EXISTS `fecha_enviado` DATETIME DEFAULT NULL;
