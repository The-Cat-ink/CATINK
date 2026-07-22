-- Migración 013: Tabla para el Historial de Ediciones de Noticias / Publicaciones
CREATE TABLE IF NOT EXISTS `historial_ediciones_noticias` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `noticia_id` INT NOT NULL,
  `usuario_id` INT NULL,
  `titulo` VARCHAR(255) NOT NULL,
  `descripcion` TEXT NOT NULL,
  `contenido` LONGTEXT NOT NULL,
  `motivo_cambio` VARCHAR(255) NULL,
  `fecha_edicion` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_historial_noticia` (`noticia_id`),
  INDEX `idx_historial_usuario` (`usuario_id`),
  CONSTRAINT `fk_hist_noticia` FOREIGN KEY (`noticia_id`) REFERENCES `noticias` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
