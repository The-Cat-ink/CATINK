-- Migración: Crear tabla logos_marcas
-- Fecha: 2026-06-15
-- Descripción: Almacena los logos de marcas colaboradoras mostrados en la página "Nosotros"
--              con animación de scroll horizontal automático.

CREATE TABLE IF NOT EXISTS logos_marcas (
    id_logo  INT          NOT NULL AUTO_INCREMENT,
    imagen   VARCHAR(255) NOT NULL,
    nombre   VARCHAR(150) NOT NULL DEFAULT '',
    activo   TINYINT(1)   NOT NULL DEFAULT 1,
    creado   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_logo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
