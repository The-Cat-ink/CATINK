-- =============================================
-- MIGRACIONES PARA PRODUCCIÓN (Hostinger)
-- Ejecutar en phpMyAdmin de Hostinger
-- =============================================

-- 1. Tabla de avatares (si no existe)
CREATE TABLE IF NOT EXISTS `avatares_perfil` (
  `id_avatar` int(11) NOT NULL AUTO_INCREMENT,
  `imagen` varchar(255) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `creado` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_avatar`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 1b. Fix: si la tabla ya existe con columna 'id', renombrarla a 'id_avatar'
-- (Ejecutar manualmente SOLO si la tabla ya fue creada con 'id')
-- ALTER TABLE `avatares_perfil` CHANGE COLUMN `id` `id_avatar` int(11) NOT NULL AUTO_INCREMENT;

-- 2. Columnas nuevas en usuarios (si no existen)
-- sexo (debe ir primero porque fecha_nacimiento lo referencia con AFTER)
ALTER TABLE `usuarios` ADD COLUMN IF NOT EXISTS `sexo` enum('masculino','femenino','otro') DEFAULT NULL AFTER `correo`;

-- fecha_nacimiento
ALTER TABLE `usuarios` ADD COLUMN IF NOT EXISTS `fecha_nacimiento` date DEFAULT NULL AFTER `sexo`;

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

-- 5. Perfiles públicos de editores
ALTER TABLE `usuarios` ADD COLUMN IF NOT EXISTS `biografia` TEXT DEFAULT NULL AFTER `avatar_id`;
ALTER TABLE `usuarios` ADD COLUMN IF NOT EXISTS `foto_personal` VARCHAR(255) DEFAULT NULL AFTER `biografia`;
ALTER TABLE `usuarios` ADD COLUMN IF NOT EXISTS `link_twitter` VARCHAR(255) DEFAULT NULL AFTER `foto_personal`;
ALTER TABLE `usuarios` ADD COLUMN IF NOT EXISTS `link_instagram` VARCHAR(255) DEFAULT NULL AFTER `link_twitter`;

-- 7. Filtro de diccionario (palabras prohibidas)
CREATE TABLE IF NOT EXISTS `filtro_diccionario` (
  `id_palabra` int(11) NOT NULL AUTO_INCREMENT,
  `palabra_baneada` varchar(255) NOT NULL,
  `reemplazo` varchar(255) DEFAULT '***',
  PRIMARY KEY (`id_palabra`),
  UNIQUE KEY `uq_palabra` (`palabra_baneada`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 8. Configuración de comentarios por noticia
CREATE TABLE IF NOT EXISTS `config_comentarios` (
  `id_config` int(11) NOT NULL AUTO_INCREMENT,
  `noticia_id` int(11) NOT NULL,
  `permitir_comentarios` tinyint(1) NOT NULL DEFAULT 1,
  `moderacion_previa` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id_config`),
  UNIQUE KEY `uq_config_noticia` (`noticia_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 9. Comentarios de lectores
CREATE TABLE IF NOT EXISTS `comentarios` (
  `id_comentario` int(11) NOT NULL AUTO_INCREMENT,
  `noticia_id` int(11) NOT NULL,
  `lector_id` int(11) NOT NULL,
  `contenido` text NOT NULL,
  `fecha_publicacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `estado` enum('activo','oculto','eliminado') NOT NULL DEFAULT 'activo',
  PRIMARY KEY (`id_comentario`),
  KEY `idx_comentarios_noticia` (`noticia_id`),
  KEY `idx_comentarios_lector` (`lector_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 10. Historial de ediciones de comentarios
CREATE TABLE IF NOT EXISTS `historial_ediciones` (
  `id_edicion` int(11) NOT NULL AUTO_INCREMENT,
  `comentario_id` int(11) NOT NULL,
  `contenido_anterior` text NOT NULL,
  `fecha_edicion` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_edicion`),
  KEY `idx_historial_comentario` (`comentario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 11. Likes en comentarios
CREATE TABLE IF NOT EXISTS `likes_comentarios` (
  `id_like` int(11) NOT NULL AUTO_INCREMENT,
  `comentario_id` int(11) NOT NULL,
  `lector_id` int(11) NOT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_like`),
  UNIQUE KEY `uq_like_comentario_lector` (`comentario_id`, `lector_id`),
  KEY `idx_likes_comentario` (`comentario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 12. Reportes de comentarios
CREATE TABLE IF NOT EXISTS `reportes_comentarios` (
  `id_reporte` int(11) NOT NULL AUTO_INCREMENT,
  `comentario_id` int(11) NOT NULL,
  `lector_id` int(11) NOT NULL,
  `motivo` varchar(255) NOT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp(),
  `estatus` enum('pendiente','revisado','descartado') NOT NULL DEFAULT 'pendiente',
  PRIMARY KEY (`id_reporte`),
  KEY `idx_reportes_comentario` (`comentario_id`),
  KEY `idx_reportes_lector` (`lector_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 13. Soporte para comentarios de admins/editores
ALTER TABLE `comentarios` MODIFY `lector_id` int(11) DEFAULT NULL;
ALTER TABLE `comentarios` ADD COLUMN IF NOT EXISTS `usuario_id` int(11) DEFAULT NULL AFTER `lector_id`;

-- 14. (OPCIONAL) Eliminar lectores migrados de la tabla usuarios
--    Descomenta SOLO después de verificar que la migración fue correcta
-- DELETE FROM `usuarios`
-- WHERE `perm_categorias` = 0
--   AND `perm_noticias` = 0
--   AND `perm_publicidad` = 0
--   AND `perm_suscripciones` = 0
--   AND `perm_usuarios` = 0
--   AND `perm_correos` = 0
--   AND `perm_videos` = 0;

-- 15. Auditoría de noticias (rastrear quién creó y editó)
ALTER TABLE `noticias` ADD COLUMN IF NOT EXISTS `creado_por` INT NULL AFTER `fecha_publicacion`;
ALTER TABLE `noticias` ADD COLUMN IF NOT EXISTS `editado_por` INT NULL AFTER `creado_por`;
ALTER TABLE `noticias` ADD COLUMN IF NOT EXISTS `ultima_edicion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `editado_por`;

-- Foreign keys para auditoría
ALTER TABLE `noticias` ADD CONSTRAINT IF NOT EXISTS `fk_noticias_creado_por` FOREIGN KEY (`creado_por`) REFERENCES `usuarios`(`id_u`) ON DELETE SET NULL;
ALTER TABLE `noticias` ADD CONSTRAINT IF NOT EXISTS `fk_noticias_editado_por` FOREIGN KEY (`editado_por`) REFERENCES `usuarios`(`id_u`) ON DELETE SET NULL;

-- 5. Tabla para tokens de recuperación de contraseña
CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL UNIQUE,
  `tipo_usuario` enum('admin','lector') NOT NULL,
  `creado` timestamp NOT NULL DEFAULT current_timestamp(),
  `expira` timestamp NOT NULL,
  `usado` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  INDEX `idx_email_token` (`email`, `token`),
  INDEX `idx_expira` (`expira`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 6. Agregar columna de orden a categorías
ALTER TABLE `categorias` ADD COLUMN IF NOT EXISTS `orden` INT DEFAULT 0 AFTER `nombre`;

-- 7. Agregar columna de orden a videos (para drag & drop de reordenamiento)
ALTER TABLE `videos` ADD COLUMN IF NOT EXISTS `orden` INT DEFAULT 0 AFTER `activo`;

-- 8. Agregar columna slug para URLs amigables en noticias
ALTER TABLE `noticias` ADD COLUMN IF NOT EXISTS `slug` VARCHAR(255) DEFAULT NULL AFTER `titulo`;
ALTER TABLE `noticias` ADD INDEX IF NOT EXISTS `idx_slug` (`slug`);

-- 9. Tabla para los logos
CREATE TABLE IF NOT EXISTS `logos_marcas` (
    `id_logo`  INT          NOT NULL AUTO_INCREMENT,
    `imagen`   VARCHAR(255) NOT NULL,
    `nombre`   VARCHAR(150) NOT NULL DEFAULT '',
    `activo`   TINYINT(1)   NOT NULL DEFAULT 1,
    `creado`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id_logo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Permite definir hasta cuándo se muestra un logo.
ALTER TABLE `logos_marcas`
    ADD COLUMN `fecha_expiracion` DATETIME NULL DEFAULT NULL AFTER `activo`;

-- 11. Orden de logos de marcas colaboradoras (drag & drop en paginas.php)
ALTER TABLE `logos_marcas` ADD COLUMN IF NOT EXISTS `orden` INT NOT NULL DEFAULT 0 AFTER `activo`;
SET @r := 0;
UPDATE `logos_marcas` SET `orden` = (@r := @r + 1) ORDER BY `creado` ASC;

-- 12. Agregar columna valor a la tabla secciones y setear default para videos
ALTER TABLE `secciones` ADD COLUMN IF NOT EXISTS `valor` VARCHAR(255) DEFAULT NULL;
UPDATE `secciones` SET `valor` = 'PLMC9KNkIncKvYin_USF1QeqG50KB1K1uD' WHERE `nombre` = 'videos' AND `valor` IS NULL;
