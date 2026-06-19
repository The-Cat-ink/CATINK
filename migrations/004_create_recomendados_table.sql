-- Create table for curated news recommendations (Lo que más recomendamos)
CREATE TABLE IF NOT EXISTS `recomendados` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `noticia_id` INT NOT NULL,
  `orden` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_noticia_recomendada` (`noticia_id`),
  FOREIGN KEY (`noticia_id`) REFERENCES `noticias` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
