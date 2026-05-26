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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 2. Columnas nuevas en usuarios (si no existen)
-- fecha_nacimiento
ALTER TABLE `usuarios` ADD COLUMN IF NOT EXISTS `fecha_nacimiento` date DEFAULT NULL AFTER `sexo`;

-- sexo
ALTER TABLE `usuarios` ADD COLUMN IF NOT EXISTS `sexo` enum('masculino','femenino','otro') DEFAULT NULL AFTER `correo`;

-- entidad (ubicación)
ALTER TABLE `usuarios` ADD COLUMN IF NOT EXISTS `entidad` varchar(3) DEFAULT NULL AFTER `fecha_nacimiento`;

-- avatar_id (referencia al avatar seleccionado)
ALTER TABLE `usuarios` ADD COLUMN IF NOT EXISTS `avatar_id` int(11) DEFAULT NULL AFTER `entidad`;

-- 3. Tabla de lectores (usuarios públicos, separada de administradores)
CREATE TABLE IF NOT EXISTS `lectores` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) NOT NULL,
  `usuario` varchar(100) NOT NULL,
  `correo` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `sexo` enum('masculino','femenino','otro') DEFAULT NULL,
  `fecha_nacimiento` date DEFAULT NULL,
  `entidad` varchar(3) DEFAULT NULL,
  `avatar_id` int(11) DEFAULT NULL,
  `creado` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_lectores_usuario` (`usuario`),
  UNIQUE KEY `uq_lectores_correo` (`correo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 4. Migrar lectores existentes de usuarios → lectores
--    (usuarios públicos = todos los perm_* en 0)
INSERT IGNORE INTO `lectores` (`nombre`, `usuario`, `correo`, `password_hash`, `sexo`, `fecha_nacimiento`, `entidad`, `avatar_id`, `creado`)
SELECT `nombre`, `usuario`, `correo`, `pass`, `sexo`, `fecha_nacimiento`, `entidad`, `avatar_id`, `registro`
FROM `usuarios`
WHERE `perm_categorias` = 0
  AND `perm_noticias` = 0
  AND `perm_publicidad` = 0
  AND `perm_suscripciones` = 0
  AND `perm_usuarios` = 0
  AND `perm_correos` = 0
  AND `perm_videos` = 0;

-- 5. (OPCIONAL) Eliminar lectores migrados de la tabla usuarios
--    Descomenta SOLO después de verificar que la migración fue correcta
-- DELETE FROM `usuarios`
-- WHERE `perm_categorias` = 0
--   AND `perm_noticias` = 0
--   AND `perm_publicidad` = 0
--   AND `perm_suscripciones` = 0
--   AND `perm_usuarios` = 0
--   AND `perm_correos` = 0
--   AND `perm_videos` = 0;
