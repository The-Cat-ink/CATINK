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
?>