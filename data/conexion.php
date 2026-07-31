<?php
    require_once(__DIR__ . '/env.php');
    //datos de coneccion
    $server = env('DB_HOST', 'localhost');
    $user   = env('DB_USER', 'root');
    $pass   = env('DB_PASS', '');
    $dbname = env('DB_NAME', 'u780114275_cat_ink');
    //sentencia de coneccion
    $con=new mysqli($server,$user,$pass,$dbname);
    if($con->connect_error){
        die("la coneccion fallo: ".$con->connect_error);
    }
    mysqli_set_charset($con, "utf8mb4");
    // Alinea la zona horaria de MySQL con la de PHP: NOW() debe coincidir con las
    // fechas que guardamos (fecha_publicacion) o las notas programadas se publican antes.
    $offset = (new DateTime('now', new DateTimeZone(date_default_timezone_get())))->format('P');
    $stmtTz = $con->prepare("SET time_zone = ?");
    if ($stmtTz) {
        $stmtTz->bind_param("s", $offset);
        $stmtTz->execute();
        $stmtTz->close();
    }

    // Helper para auto-migración segura de columnas si no existen
    if (!function_exists('asegurarColumna')) {
        function asegurarColumna($con, $tabla, $columna, $definicion) {
            try {
                $res = $con->query("SHOW COLUMNS FROM `$tabla` LIKE '$columna'");
                if ($res && $res->num_rows === 0) {
                    $con->query("ALTER TABLE `$tabla` ADD COLUMN `$columna` $definicion");
                }
            } catch (\Throwable $e) {}
        }
    }

    asegurarColumna($con, 'recomendados', 'url', 'VARCHAR(500) NULL AFTER imagen');
    asegurarColumna($con, 'esperamos', 'url', 'VARCHAR(500) NULL AFTER imagen');
    asegurarColumna($con, 'correos_publicitarios', 'badge', "VARCHAR(100) NULL DEFAULT 'Anuncio / Promoción' AFTER titulo");
    asegurarColumna($con, 'correos_publicitarios', 'preheader', "VARCHAR(255) NULL DEFAULT NULL AFTER badge");
    asegurarColumna($con, 'correos_publicitarios', 'theme', "VARCHAR(20) NULL DEFAULT 'light' AFTER preheader");
    asegurarColumna($con, 'correos_publicitarios', 'cta_text', "VARCHAR(100) NULL DEFAULT 'Ver promoción' AFTER url_c");
    asegurarColumna($con, 'noticias', 'ficha_id', "INT NULL AFTER id");

    // Auto-creación de tablas de Fichas y Estrenos
    $con->query("CREATE TABLE IF NOT EXISTS `fichas` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `titulo` VARCHAR(255) NOT NULL,
        `titulo_jp` VARCHAR(255) NULL,
        `slug` VARCHAR(255) NOT NULL UNIQUE,
        `categoria` VARCHAR(50) NOT NULL DEFAULT 'anime',
        `estudio` VARCHAR(255) NULL,
        `director` VARCHAR(255) NULL,
        `fecha_lanzamiento` DATE NULL,
        `precio_plataforma` VARCHAR(255) NULL,
        `formato` VARCHAR(100) NULL,
        `duracion` VARCHAR(100) NULL,
        `episodios` VARCHAR(50) NULL,
        `clasificacion_edad` VARCHAR(100) NULL,
        `voces` VARCHAR(255) NULL,
        `subtitulos` VARCHAR(255) NULL,
        `generos` VARCHAR(255) NULL,
        `sinopsis` TEXT NULL,
        `poster_img` VARCHAR(500) NULL,
        `banner_img` VARCHAR(500) NULL,
        `trailer_url` VARCHAR(500) NULL,
        `plataformas` VARCHAR(255) NULL,
        `rating_oficial` DECIMAL(3,1) DEFAULT 8.5,
        `estado` VARCHAR(50) DEFAULT 'Estreno',
        `temporada` VARCHAR(50) DEFAULT 'Verano 2026',
        `creado_en` DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $con->query("CREATE TABLE IF NOT EXISTS `fichas_votos` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `ficha_id` INT NOT NULL,
        `lector_id` INT NOT NULL,
        `puntuacion` INT NOT NULL,
        `fecha` DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY `ficha_lector` (`ficha_id`, `lector_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $con->query("CREATE TABLE IF NOT EXISTS `fichas_favoritos` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `ficha_id` INT NOT NULL,
        `lector_id` INT NOT NULL,
        `fecha` DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY `fav_ficha_lector` (`ficha_id`, `lector_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
?>