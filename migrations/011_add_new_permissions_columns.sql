-- 011_add_new_permissions_columns.sql
-- Agregar nuevas columnas de permisos para el rediseño granular del sistema ACL en la tabla de usuarios administradores

ALTER TABLE `usuarios` 
ADD COLUMN `perm_lectores` INT NOT NULL DEFAULT 0 AFTER `perm_videos`,
ADD COLUMN `perm_recomendados` INT NOT NULL DEFAULT 0 AFTER `perm_lectores`,
ADD COLUMN `perm_esperamos` INT NOT NULL DEFAULT 0 AFTER `perm_recomendados`,
ADD COLUMN `perm_paginas` INT NOT NULL DEFAULT 0 AFTER `perm_esperamos`,
ADD COLUMN `perm_actividad` INT NOT NULL DEFAULT 0 AFTER `perm_paginas`,
ADD COLUMN `perm_papelera` INT NOT NULL DEFAULT 0 AFTER `perm_actividad`,
ADD COLUMN `perm_avatares` INT NOT NULL DEFAULT 0 AFTER `perm_papelera`;

-- Asignar full acceso (15) en las nuevas columnas a los superadministradores existentes
UPDATE `usuarios` 
SET 
  `perm_lectores` = 15,
  `perm_recomendados` = 15,
  `perm_esperamos` = 15,
  `perm_paginas` = 15,
  `perm_actividad` = 15,
  `perm_papelera` = 15,
  `perm_avatares` = 15
WHERE 
  `perm_categorias` = 15 
  AND `perm_noticias` = 15 
  AND `perm_publicidad` = 15 
  AND `perm_suscripciones` = 15 
  AND `perm_usuarios` = 15 
  AND `perm_correos` = 15 
  AND `perm_videos` = 15;
