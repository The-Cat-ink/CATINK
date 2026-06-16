-- Add review fields to noticias table
ALTER TABLE noticias 
ADD COLUMN tipo_publicacion ENUM('noticia', 'review') NOT NULL DEFAULT 'noticia' AFTER id,
ADD COLUMN calificacion DECIMAL(3,1) DEFAULT NULL AFTER tipo_publicacion,
ADD COLUMN pros TEXT DEFAULT NULL AFTER calificacion,
ADD COLUMN contras TEXT DEFAULT NULL AFTER pros;
