-- Migración: Agregar fecha_expiracion a logos_marcas
-- Fecha: 2026-06-15
-- Descripción: Permite definir hasta cuándo se muestra un logo.
--              NULL = sin vencimiento. La query en nosotros.php filtra automáticamente.

ALTER TABLE logos_marcas
    ADD COLUMN fecha_expiracion DATETIME NULL DEFAULT NULL AFTER activo;
