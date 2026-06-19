-- Create table for curated expected releases (Lo que más esperamos)
CREATE TABLE IF NOT EXISTS `esperamos` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `noticia_id` INT NOT NULL,
  `orden` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_noticia_esperada` (`noticia_id`),
  FOREIGN KEY (`noticia_id`) REFERENCES `noticias` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
