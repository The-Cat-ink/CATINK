-- =========================================================
-- Migración: Tabla de registro de actividad del panel admin
-- Ejecutar una sola vez en producción y en local
-- =========================================================
CREATE TABLE IF NOT EXISTS `activity_log` (
  `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `user_id`     INT           NOT NULL DEFAULT 0,
  `username`    VARCHAR(80)   NOT NULL DEFAULT '',
  `accion`      VARCHAR(30)   NOT NULL,
  `modulo`      VARCHAR(40)   NOT NULL,
  `descripcion` VARCHAR(255)  NOT NULL,
  `ip`          VARCHAR(45)   NULL,
  `created_at`  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user`   (`user_id`),
  KEY `idx_modulo` (`modulo`),
  KEY `idx_fecha`  (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
