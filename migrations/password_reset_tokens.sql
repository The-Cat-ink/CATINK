-- Tabla para almacenar tokens de reset de contraseña
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
