-- Migración 014: Tablas para Gestión de Vacantes y Solicitudes de Empleo
CREATE TABLE IF NOT EXISTS `vacantes_equipo` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `orden` INT DEFAULT 1,
  `tag` VARCHAR(100) NOT NULL,
  `titulo` VARCHAR(100) NOT NULL,
  `subtitulo_italic` VARCHAR(255) NULL,
  `descripcion` TEXT NOT NULL,
  `modalidad` VARCHAR(100) DEFAULT '100% Remoto · Tiempo Completo',
  `estado` TINYINT DEFAULT 1,
  `creado_en` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `actualizado_en` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_vacantes_estado` (`estado`),
  INDEX `idx_vacantes_orden` (`orden`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `solicitudes_vacantes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `vacante_id` INT NULL,
  `nombre` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `telefono` VARCHAR(50) NULL,
  `razon` TEXT NOT NULL,
  `cv_archivo` VARCHAR(255) NOT NULL,
  `estado` ENUM('pendiente', 'revisado', 'aceptado', 'rechazado') DEFAULT 'pendiente',
  `fecha_solicitud` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_solicitud_vacante` (`vacante_id`),
  INDEX `idx_solicitud_estado` (`estado`),
  CONSTRAINT `fk_solicitud_vacante` FOREIGN KEY (`vacante_id`) REFERENCES `vacantes_equipo` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar vacantes iniciales
INSERT INTO `vacantes_equipo` (`orden`, `tag`, `titulo`, `subtitulo_italic`, `descripcion`, `modalidad`, `estado`) VALUES
(1, '01 · ANIME & MANGA', 'EDITOR', 'Da forma al relato.', 'Lidera la voz editorial de CatInk. Curarás análisis de temporada, reseñas de manga y coberturas especiales que definen nuestra línea editorial.', '100% Remoto · Tiempo completo', 1),
(2, '02 · REDACCIÓN', 'REDACTOR', 'Historias que conectan.', 'Escribe noticias de último minuto, artículos de opinión y resúmenes de episodios sobre el universo del anime, manga y videojuegos.', '100% Remoto · Tiempo completo', 1),
(3, '03 · SOCIAL MEDIA', 'COMMUNITY', 'Crea comunidad.', 'Gestiona nuestras redes sociales, interactúa con la comunidad de CatInk en X, Facebook e Instagram, creando memes y dinámicas virales.', '100% Remoto · Tiempo completo', 1),
(4, '04 · DISEÑO Y MARCA', 'DISEÑADOR', 'Impacto visual.', 'Diseña la identidad gráfica de noticias, banners promocionales y elementos visuales para nuestras publicaciones y redes sociales.', '100% Remoto · Tiempo completo', 1),
(5, '05 · DESARROLLO WEB', 'DEVELOPER', 'Tecnología de punta.', 'Desarrolla nuevas funciones para la plataforma web de CatInk (PHP, JS, PWA) y optimiza el rendimiento y experiencia de usuario.', '100% Remoto · Tiempo completo', 1)
ON DUPLICATE KEY UPDATE `orden` = VALUES(`orden`);
