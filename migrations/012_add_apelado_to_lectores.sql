-- 012_add_apelado_to_lectores.sql
-- Agregar la columna `apelado` a la tabla de lectores.
--
-- controllers/apelar_lector.php la escribe (apelado = 1) al aceptar la
-- apelacion unica, y consultar_baneo.php / header.php la leen para decidir si
-- se le ofrece el boton "Apelar" al lector suspendido.
--
-- Sin esta columna, el UPDATE de apelar_lector.php lanza
-- "Unknown column 'apelado' in 'field list'": el controlador muere con un
-- fatal, responde HTML en vez de JSON y el front lo muestra como
-- "Error de conexion al intentar apelar".
--
-- 0 = aun tiene disponible su apelacion unica
-- 1 = ya la uso

ALTER TABLE `lectores`
ADD COLUMN `apelado` TINYINT(1) NOT NULL DEFAULT 0 AFTER `intentos_profanos`;
