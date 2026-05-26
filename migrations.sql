-- =============================================
-- MIGRACIONES PARA PRODUCCIÓN (Hostinger)
-- Ejecutar en phpMyAdmin de Hostinger
-- =============================================

-- 1. Tabla de avatares (si no existe)
CREATE TABLE IF NOT EXISTS `avatares_perfil` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `imagen` varchar(255) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `creado` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Columnas nuevas en usuarios (si no existen)
-- fecha_nacimiento
ALTER TABLE `usuarios` ADD COLUMN IF NOT EXISTS `fecha_nacimiento` date DEFAULT NULL AFTER `sexo`;

-- sexo
ALTER TABLE `usuarios` ADD COLUMN IF NOT EXISTS `sexo` enum('masculino','femenino','otro') DEFAULT NULL AFTER `correo`;

-- entidad (ubicación)
ALTER TABLE `usuarios` ADD COLUMN IF NOT EXISTS `entidad` varchar(3) DEFAULT NULL AFTER `fecha_nacimiento`;

-- avatar_id (referencia al avatar seleccionado)
ALTER TABLE `usuarios` ADD COLUMN IF NOT EXISTS `avatar_id` int(11) DEFAULT NULL AFTER `entidad`;
