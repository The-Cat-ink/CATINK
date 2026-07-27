<?php
/**
 * CatInk Social Networks Helper
 * Obtiene y gestiona las redes sociales oficiales desde la BD.
 */
if (!function_exists('getCatInkSocials')) {
    function getCatInkSocials($onlyActive = true) {
        global $con;
        if (!$con) {
            @include(__DIR__ . "/../../data/conexion.php");
        }
        if (!$con) return [];

        // Auto-migración defensiva
        @$con->query("CREATE TABLE IF NOT EXISTS `redes_sociales` (
          `id_red` int(11) NOT NULL AUTO_INCREMENT,
          `nombre` varchar(100) NOT NULL,
          `url` varchar(255) NOT NULL,
          `icono` varchar(100) NOT NULL DEFAULT 'bi-link-45deg',
          `icono_img` varchar(255) DEFAULT NULL,
          `color` varchar(30) DEFAULT '#EF3363',
          `orden` int(11) NOT NULL DEFAULT 0,
          `activo` tinyint(1) NOT NULL DEFAULT 1,
          `creado` timestamp NOT NULL DEFAULT current_timestamp(),
          PRIMARY KEY (`id_red`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Poblar por defecto si está vacía
        $check = @$con->query("SELECT COUNT(*) AS total FROM redes_sociales");
        $rowCheck = $check ? $check->fetch_assoc() : null;
        if ($rowCheck && intval($rowCheck['total']) === 0) {
            @$con->query("INSERT INTO `redes_sociales` (`nombre`, `url`, `icono`, `color`, `orden`, `activo`) VALUES
                ('Facebook', 'https://www.facebook.com/TheCatink?locale=es_LA', 'bi-facebook', '#1877F2', 1, 1),
                ('X / Twitter', 'https://x.com/The_Catink/', 'bi-twitter-x', '#ffffff', 2, 1),
                ('Instagram', 'https://www.instagram.com/the.catink/', 'bi-instagram', '#E1306C', 3, 1),
                ('YouTube', 'https://www.youtube.com/@thecatink', 'bi-youtube', '#FF0000', 4, 1),
                ('TikTok', 'https://www.tiktok.com/@thecatink', 'bi-tiktok', '#00f2ea', 5, 1)");
        }

        $where = $onlyActive ? "WHERE activo = 1" : "";
        $res = @$con->query("SELECT * FROM redes_sociales $where ORDER BY orden ASC, id_red ASC");
        $socials = [];
        if ($res && method_exists($res, 'fetch_all')) {
            $socials = $res->fetch_all(MYSQLI_ASSOC);
        } else if ($res) {
            while ($row = $res->fetch_assoc()) {
                $socials[] = $row;
            }
        }
        return $socials;
    }
}
