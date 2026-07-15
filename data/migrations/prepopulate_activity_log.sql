-- =====================================================================
-- Migración opcional: Pre-poblar el registro de actividad con datos históricos
-- Ejecutar en local o en producción para rellenar el historial existente
-- =====================================================================

-- 1. Registrar la creación de usuarios administradores/editores
INSERT INTO activity_log (user_id, username, accion, modulo, descripcion, ip, created_at)
SELECT 
  1, 
  'sistema', 
  'crear', 
  'usuarios', 
  CONCAT('Se registró el usuario administrador «', usuario, '» (', nombre, ')'), 
  '127.0.0.1', 
  registro
FROM usuarios;

-- 2. Registrar la creación de noticias y borradores
INSERT INTO activity_log (user_id, username, accion, modulo, descripcion, ip, created_at)
SELECT 
  COALESCE(n.creado_por, 1), 
  COALESCE(u.usuario, 'sistema'), 
  IF(n.borrador = 1, 'borrador', 'crear'), 
  'noticias', 
  CONCAT('Creó ', IF(n.borrador = 1, 'borrador', 'noticia'), ' «', LEFT(n.titulo, 80), '» (ID ', n.id, ')'), 
  '127.0.0.1', 
  COALESCE(n.fecha_creacion, n.fecha_publicacion, NOW())
FROM noticias n
LEFT JOIN usuarios u ON n.creado_por = u.id_u;

-- 3. Registrar ediciones/actualizaciones de noticias
INSERT INTO activity_log (user_id, username, accion, modulo, descripcion, ip, created_at)
SELECT 
  COALESCE(n.editado_por, 1), 
  COALESCE(u.usuario, 'sistema'), 
  'editar', 
  'noticias', 
  CONCAT('Actualizó la noticia ID ', n.id, ': «', LEFT(n.titulo, 80), '»'), 
  '127.0.0.1', 
  n.ultima_edicion
FROM noticias n
LEFT JOIN usuarios u ON n.editado_por = u.id_u
WHERE n.ultima_edicion IS NOT NULL 
  AND n.ultima_edicion != n.fecha_creacion;

-- 4. Registrar envío a la papelera de noticias
INSERT INTO activity_log (user_id, username, accion, modulo, descripcion, ip, created_at)
SELECT 
  COALESCE(n.eliminado_por, 1), 
  COALESCE(u.usuario, 'sistema'), 
  'eliminar', 
  'noticias', 
  CONCAT('Envió a la papelera noticia ID ', n.id), 
  '127.0.0.1', 
  n.eliminado_en
FROM noticias n
LEFT JOIN usuarios u ON n.eliminado_por = u.id_u
WHERE n.eliminado_en IS NOT NULL;

-- 5. Registrar la creación de publicidad/spots
INSERT INTO activity_log (user_id, username, accion, modulo, descripcion, ip, created_at)
SELECT 
  1, 
  'sistema', 
  'crear', 
  'publicidad', 
  CONCAT('Creó publicidad «', LEFT(titulo, 80), '» (ID ', id_pub, ')'), 
  '127.0.0.1', 
  creado
FROM publicidad;
